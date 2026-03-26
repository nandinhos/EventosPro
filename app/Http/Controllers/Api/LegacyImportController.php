<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyImportController extends Controller
{
    public function __construct(
        protected LegacyImportService $legacyImportService
    ) {}

    /**
     * Importa contratos legados.
     */
    public function importContracts(Request $request): JsonResponse
    {
        $request->validate([
            'contracts' => 'required|array',
            'contracts.*.numero_contrato' => 'required|string|max:30',
            'contracts.*.artista' => 'required|string',
            'contracts.*.data_evento' => 'required|date',
            'contracts.*.valor_bruto' => 'required|numeric',
            'contracts.*.moeda' => 'nullable|string|max:3',
        ]);

        $result = $this->legacyImportService->importContracts($request->input('contracts'));

        return response()->json([
            'message' => 'Processamento de contratos concluído.',
            'imported_count' => $result['count'],
            'errors' => $result['errors'],
        ], count($result['errors']) > 0 ? 207 : 200);
    }

    /**
     * Importa recebíveis legados.
     */
    public function importReceivables(Request $request): JsonResponse
    {
        $request->validate([
            'receivables' => 'required|array',
            'receivables.*.contrato_ref' => 'required|string|max:30',
            'receivables.*.parcela' => 'required|string',
            'receivables.*.valor' => 'required|numeric',
            'receivables.*.data_vencimento' => 'required|date',
        ]);

        $result = $this->legacyImportService->importReceivables($request->input('receivables'));

        return response()->json([
            'message' => 'Processamento de recebíveis concluído.',
            'imported_count' => $result['count'],
            'errors' => $result['errors'],
        ], count($result['errors']) > 0 ? 207 : 200);
    }

    /**
     * Importa pagamentos legados.
     */
    public function importPayables(Request $request): JsonResponse
    {
        $request->validate([
            'payables' => 'required|array',
            'payables.*.descricao' => 'required|string',
            'payables.*.contraparte' => 'required|string',
            'payables.*.valor_devido' => 'required|numeric',
            'payables.*.data_devida' => 'required|date',
        ]);

        $result = $this->legacyImportService->importPayables($request->input('payables'));

        return response()->json([
            'message' => 'Processamento de pagamentos concluído.',
            'imported_count' => $result['count'],
            'errors' => $result['errors'],
        ], count($result['errors']) > 0 ? 207 : 200);
    }
}
