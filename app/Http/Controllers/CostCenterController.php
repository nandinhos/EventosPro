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

        $query = CostCenter::withCount('gigCosts');

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

        // Se não usar cor personalizada, define como null
        if (! $request->has('use_custom_color')) {
            $data['color'] = null;
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

        $costCenter->loadCount('gigCosts');

        return view('cost-centers.edit', compact('costCenter'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CostCenterRequest $request, CostCenter $costCenter)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        // Se não usar cor personalizada, define como null
        if (! $request->has('use_custom_color')) {
            $data['color'] = null;
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
        if ($costCenter->gigCosts()->count() > 0) {
            return redirect()->route('cost-centers.index')->with('error', 'Não é possível excluir este centro de custo pois existem '.$costCenter->gigCosts()->count().' despesas associadas a ele.');
        }

        $costCenter->delete();

        return redirect()->route('cost-centers.index')->with('success', 'Centro de custo excluído com sucesso!');
    }
}
