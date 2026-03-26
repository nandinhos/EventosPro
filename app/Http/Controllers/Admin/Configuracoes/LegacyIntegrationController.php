<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Services\LegacyImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LegacyIntegrationController extends Controller
{
    public function __construct(
        protected LegacyImportService $legacyImportService
    ) {}

    public function index()
    {
        return view('admin.configuracoes.backup.integracao', [
            'preview' => null,
            'type' => null,
            'totals' => [
                'contracts' => \App\Models\Gig::count(),
                'receivables' => \App\Models\Payment::count(),
                'payables' => \App\Models\GigCost::count(),
            ]
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt', // Permitir txt caso o json venha sem extensão correta
            'type' => 'required|in:contracts,receivables,payables'
        ]);

        $rawContent = file_get_contents($request->file('file')->path());
        
        // Detectar encoding e converter para UTF-8 se necessário
        $encoding = mb_detect_encoding($rawContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
        }

        $content = json_decode($rawContent, true);
        $type = $request->input('type');
        
        if (!isset($content[$type])) {
            return back()->with('error', "Chave '{$type}' não encontrada no JSON.");
        }

        $preview = match($type) {
            'contracts' => $this->legacyImportService->previewContracts($content['contracts']),
            'receivables' => $this->legacyImportService->previewReceivables($content['receivables']),
            'payables' => $this->legacyImportService->previewPayables($content['payables']),
        };

        return view('admin.configuracoes.backup.integracao', [
            'preview' => $preview,
            'type' => $type,
            'rawData' => json_encode($content[$type])
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'data' => 'required|string',
            'type' => 'required|in:contracts,receivables,payables'
        ]);

        $data = json_decode($request->input('data'), true);
        $type = $request->input('type');

        $result = match($type) {
            'contracts' => $this->legacyImportService->importContracts($data),
            'receivables' => $this->legacyImportService->importReceivables($data),
            'payables' => $this->legacyImportService->importPayables($data),
        };

        $message = "Importação de {$type} concluída: {$result['count']} registros importados.";
        if (count($result['errors']) > 0) {
            return redirect()->route('admin.backup.integracao')
                ->with('success', $message)
                ->with('import_errors', $result['errors']);
        }

        return redirect()->route('admin.backup.integracao')->with('success', $message);
    }

    public function exportMock()
    {
        $data = $this->legacyImportService->exportCurrentGigsAsLegacyFormat();
        
        return Response::make(json_encode($data, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="eventospro-legacy-export-mock.json"',
        ]);
    }
}
