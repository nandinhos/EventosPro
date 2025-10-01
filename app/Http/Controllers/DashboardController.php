<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth');
        $this->dashboardService = $dashboardService;
    }

    /**
     * Exibe o dashboard principal.
     */
    public function index(Request $request): View
    {
        // Aplica os filtros da requisição, se houver
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        // Obtém os dados do dashboard usando o serviço
        $data = $this->dashboardService
            ->setFilters($filters)
            ->getDashboardData();

        // Adiciona os filtros atuais à view
        $data['filters'] = $filters;

        return view('dashboard', $data);
    }
}
