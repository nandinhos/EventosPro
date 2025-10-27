<?php

namespace App\Http\Controllers;

use App\Models\AgencyFixedCost;
use App\Models\CostCenter;
use Illuminate\Http\Request;

class AgencyCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $costs = AgencyFixedCost::with('costCenter')->orderBy('reference_month', 'desc')->get();

        $groupedCosts = $costs->groupBy(function ($cost) {
            return $cost->reference_month->format('Y-m');
        });

        return view('agency-costs.index', ['groupedCosts' => $groupedCosts]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $costCenters = CostCenter::orderBy('name')->get();

        return view('agency-costs.create', compact('costCenters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'description' => 'required|string|max:255',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'monthly_value' => 'required|numeric|min:0',
            'reference_month' => 'required|date_format:Y-m',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Convert the month input to the first day of the month
        $validatedData['reference_month'] = $validatedData['reference_month'].'-01';

        AgencyFixedCost::create($validatedData);

        return redirect()->route('agency-costs.index')->with('success', 'Custo operacional salvo com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(AgencyFixedCost $agencyCost)
    {
        return view('agency-costs.show', ['cost' => $agencyCost]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgencyFixedCost $agencyCost)
    {
        $costCenters = CostCenter::orderBy('name')->get();

        return view('agency-costs.edit', compact('agencyCost', 'costCenters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AgencyFixedCost $agencyCost)
    {
        $validatedData = $request->validate([
            'description' => 'required|string|max:255',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'monthly_value' => 'required|numeric|min:0',
            'reference_month' => 'required|date_format:Y-m',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Convert the month input to the first day of the month
        $validatedData['reference_month'] = $validatedData['reference_month'].'-01';

        $agencyCost->update($validatedData);

        return redirect()->route('agency-costs.index')->with('success', 'Custo operacional atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgencyFixedCost $agencyCost)
    {
        $agencyCost->delete();

        return redirect()->route('agency-costs.index')->with('success', 'Custo operacional removido com sucesso!');
    }
}
