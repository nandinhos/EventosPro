<?php

namespace App\Http\Controllers;

use App\Http\Requests\CostCenterRequest;
use App\Models\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('manage cost-centers');

        $query = CostCenter::withCount(['gigCosts', 'agencyFixedCosts']);

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $costCenters = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('cost-centers.index', compact('costCenters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('manage cost-centers');

        return view('cost-centers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CostCenterRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        if (! $request->has('use_custom_color')) {
            $data['color'] = null;
        }

        $ghost = CostCenter::onlyTrashed()->where('name', $data['name'])->first();
        if ($ghost) {
            if ($request->has('restore_confirm') && $request->input('restore_confirm') == '1') {
                $ghost->restore();
                $ghost->update($data);

                return redirect()->route('cost-centers.index')->with('success', 'Centro de custo restaurado com sucesso!');
            }

            return back()
                ->with('restore_candidate', [
                    'id' => $ghost->id,
                    'name' => $ghost->name,
                    'deleted_at' => optional($ghost->deleted_at)->toDateTimeString(),
                ])
                ->withInput();
        }

        CostCenter::create($data);

        return redirect()->route('cost-centers.index')->with('success', 'Centro de custo criado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CostCenter $costCenter)
    {
        $this->authorize('manage cost-centers');

        $costCenter->loadCount(['gigCosts', 'agencyFixedCosts']);

        return view('cost-centers.edit', compact('costCenter'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CostCenterRequest $request, CostCenter $costCenter)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        if (! $request->has('use_custom_color')) {
            $data['color'] = null;
        }

        $ghost = CostCenter::onlyTrashed()->where('name', $data['name'])->first();
        if ($ghost && $ghost->id !== $costCenter->id) {
            if ($request->has('restore_confirm') && $request->input('restore_confirm') == '1') {
                $ghost->restore();
                $ghost->update($data);

                return redirect()->route('cost-centers.index')->with('success', 'Centro de custo restaurado com sucesso!');
            }

            return back()
                ->with('restore_candidate', [
                    'id' => $ghost->id,
                    'name' => $ghost->name,
                    'deleted_at' => optional($ghost->deleted_at)->toDateTimeString(),
                ])
                ->withInput();
        }

        $costCenter->update($data);

        return redirect()->route('cost-centers.index')->with('success', 'Centro de custo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CostCenter $costCenter)
    {
        $this->authorize('manage cost-centers');

        // Check if cost center has associated costs
        $gigCostsCount = $costCenter->gigCosts()->count();
        $agencyCostsCount = $costCenter->agencyFixedCosts()->count();
        $totalCosts = $gigCostsCount + $agencyCostsCount;

        if ($totalCosts > 0) {
            $message = 'Não é possível excluir este centro de custo pois existem despesas associadas: ';
            $details = [];
            if ($gigCostsCount > 0) {
                $details[] = "{$gigCostsCount} custos de gigs";
            }
            if ($agencyCostsCount > 0) {
                $details[] = "{$agencyCostsCount} custos operacionais";
            }

            return redirect()->route('cost-centers.index')->with('error', $message.implode(' e ', $details).'.');
        }

        $costCenter->delete();

        return redirect()->route('cost-centers.index')->with('success', 'Centro de custo excluído com sucesso!');
    }

    public function restoreGhost(Request $request)
    {
        $this->authorize('manage cost-centers');

        $ghostId = $request->input('ghost_id');
        $data = $request->validate([
            'ghost_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'use_custom_color' => 'nullable|boolean',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $ghost = CostCenter::onlyTrashed()->findOrFail($ghostId);
        $ghost->restore();

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->has('is_active'),
            'color' => $request->has('use_custom_color') ? ($data['color'] ?? '#6366f1') : null,
        ];

        $ghost->update($payload);

        return redirect()->route('cost-centers.index')->with('success', 'Centro de custo restaurado com sucesso!');
    }
}
