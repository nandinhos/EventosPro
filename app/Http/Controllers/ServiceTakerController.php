<?php

namespace App\Http\Controllers;

use App\Models\ServiceTaker;
use Illuminate\Http\Request;

class ServiceTakerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceTaker::query();

        // Pesquisa por nome ou documento
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('organization', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        $serviceTakers = $query->orderBy('organization')->paginate(20);

        return view('service-takers.index', compact('serviceTakers'));
    }

    /**
     * Return list for AJAX/Select2.
     */
    public function list(Request $request)
    {
        $query = ServiceTaker::query();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('organization', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        return $query->select('id', 'organization', 'document')
            ->orderBy('organization')
            ->limit(5)
            ->get()
            ->map(fn ($st) => [
                'id' => $st->id,
                'text' => $st->organization.($st->document ? " ({$st->document})" : ''),
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('service-takers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string|max:255',
            'document' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:50',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $serviceTaker = ServiceTaker::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $serviceTaker->id]);
        }

        return redirect()
            ->route('service-takers.index')
            ->with('success', 'Tomador de serviço criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceTaker $serviceTaker)
    {
        $serviceTaker->load('gigs');

        return view('service-takers.show', compact('serviceTaker'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceTaker $serviceTaker)
    {
        return view('service-takers.edit', compact('serviceTaker'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceTaker $serviceTaker)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string|max:255',
            'document' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:50',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $serviceTaker->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('service-takers.index')
            ->with('success', 'Tomador de serviço atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceTaker $serviceTaker)
    {
        $serviceTaker->delete();

        return redirect()
            ->route('service-takers.index')
            ->with('success', 'Tomador de serviço removido com sucesso.');
    }

    /**
     * Show CSV import form.
     */
    public function showImportForm()
    {
        return view('service-takers.import');
    }

    /**
     * Import service takers from CSV.
     * Handles null/empty fields properly.
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $rows = array_map('str_getcsv', file($file->path()));

        // Remove header
        $header = array_map('trim', array_shift($rows));

        // Expected columns
        $expectedColumns = [
            'organization', 'document', 'street', 'postal_code',
            'city', 'country', 'company_phone', 'contact', 'email', 'phone',
        ];

        // Validate header
        $missingColumns = array_diff($expectedColumns, $header);
        if (count($missingColumns) > 0) {
            return back()->withErrors([
                'file' => 'Colunas faltando no CSV: '.implode(', ', $missingColumns),
            ]);
        }

        $created = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }

            // Pad row if needed
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            }

            // Combine with header
            $data = array_combine($header, $row);

            // Convert empty strings to null
            $data = array_map(fn ($value) => trim($value) === '' ? null : trim($value), $data);

            // Filter only expected columns
            $data = array_intersect_key($data, array_flip($expectedColumns));

            try {
                ServiceTaker::create($data);
                $created++;
            } catch (\Exception $e) {
                $errors[] = 'Linha '.($index + 2).': '.$e->getMessage();
            }
        }

        $message = "{$created} tomadores importados com sucesso.";
        if (count($errors) > 0) {
            $message .= ' '.count($errors).' erros: '.implode('; ', array_slice($errors, 0, 3));
        }

        return redirect()
            ->route('service-takers.index')
            ->with($created > 0 ? 'success' : 'error', $message);
    }
}
