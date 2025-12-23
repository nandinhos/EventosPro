<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportGigsRequest;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Services\GigImportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GigImportController extends Controller
{
    public function __construct(protected GigImportService $importService) {}

    /**
     * Exibe o formulário de importação.
     */
    public function showForm(): View
    {
        return view('gigs.import', [
            'expectedColumns' => $this->importService->getExpectedColumns(),
        ]);
    }

    /**
     * Baixa o template de exemplo.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Importar Gigs');

        // Cabeçalhos
        $columns = array_keys($this->importService->getExpectedColumns());
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col.'1', $column);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Linha de exemplo
        $exampleData = $this->getExampleData();
        $col = 'A';
        foreach ($exampleData as $value) {
            $sheet->setCellValue($col.'2', $value);
            $col++;
        }

        // Segunda linha de exemplo
        $exampleData2 = $this->getExampleData2();
        $col = 'A';
        foreach ($exampleData2 as $value) {
            $sheet->setCellValue($col.'3', $value);
            $col++;
        }

        // Estilizar cabeçalho
        $lastCol = chr(ord('A') + count($columns) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF6366F1'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
        ]);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_importacao_gigs.xlsx');
    }

    /**
     * Exibe preview dos dados antes de importar.
     */
    public function preview(ImportGigsRequest $request): View
    {
        $file = $request->file('file');
        $path = $file->store('imports');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        $result = $this->importService->parseFile($fullPath);

        // Salva o path na sessão para uso na importação
        session(['import_file_path' => $fullPath]);

        return view('gigs.import-preview', [
            'rows' => $result['rows'],
            'errors' => $result['errors'],
            'summary' => $result['summary'],
            'artists' => Artist::orderBy('name')->pluck('name', 'id'),
            'bookers' => Booker::orderBy('name')->pluck('name', 'id'),
            'costCenters' => CostCenter::active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /**
     * Processa a importação.
     */
    public function import(Request $request)
    {
        $filePath = session('import_file_path');

        if (! $filePath || ! file_exists($filePath)) {
            return redirect()
                ->route('gigs.import.form')
                ->with('error', 'Arquivo de importação não encontrado. Por favor, faça upload novamente.');
        }

        $result = $this->importService->import($filePath);

        // Remove o arquivo temporário
        @unlink($filePath);
        session()->forget('import_file_path');

        if ($result['success'] > 0) {
            $message = "{$result['success']} gig(s) importada(s) com sucesso.";
            if ($result['failed'] > 0) {
                $message .= " {$result['failed']} linha(s) com erro.";
            }

            return redirect()
                ->route('gigs.index')
                ->with('success', $message)
                ->with('import_errors', $result['errors']);
        }

        return redirect()
            ->route('gigs.import.form')
            ->with('error', 'Nenhuma gig foi importada. Verifique os erros abaixo.')
            ->with('import_errors', $result['errors']);
    }

    /**
     * Dados de exemplo para o template.
     */
    protected function getExampleData(): array
    {
        $data = [
            'Maria Bethânia',           // artista
            'João Produções',           // booker
            'Teatro Municipal SP',      // tomador_servico
            '15/01/2025',               // data_evento
            'Show de Ano Novo - SP',    // local_evento
            '50000',                    // valor_contrato
            'BRL',                      // moeda
            'CONT-2025-001',            // numero_contrato
            '10/01/2025',               // data_contrato
            'assinado',                 // status_contrato
        ];

        // Adicionar despesas de exemplo
        for ($i = 1; $i <= GigImportService::MAX_EXPENSES_PER_ROW; $i++) {
            if ($i === 1) {
                $data[] = 'Hospedagem';     // centro_custo
                $data[] = 'Hotel Premium';  // descricao
                $data[] = '1500';           // valor
                $data[] = 'BRL';            // moeda
                $data[] = '';               // data
                $data[] = 'sim';            // confirmada
                $data[] = 'nao';            // reembolsavel
                $data[] = '';               // notas
            } else {
                $data = array_merge($data, array_fill(0, 8, ''));
            }
        }

        return $data;
    }

    /**
     * Segunda linha de dados de exemplo.
     */
    protected function getExampleData2(): array
    {
        $data = [
            'Gilberto Gil',            // artista
            '',                         // booker (agência)
            '',                         // tomador_servico
            '20/02/2025',               // data_evento
            'Festival Verão - RJ',      // local_evento
            '10000',                    // valor_contrato
            'USD',                      // moeda
            '',                         // numero_contrato
            '',                         // data_contrato
            'em_negociacao',            // status_contrato
        ];

        // Adicionar despesas de exemplo (duas despesas)
        for ($i = 1; $i <= GigImportService::MAX_EXPENSES_PER_ROW; $i++) {
            if ($i === 1) {
                $data[] = 'Transporte';         // centro_custo
                $data[] = 'Passagem aérea';     // descricao
                $data[] = '2000';               // valor
                $data[] = 'BRL';                // moeda
                $data[] = '';                   // data
                $data[] = 'nao';                // confirmada
                $data[] = 'sim';                // reembolsavel
                $data[] = 'Ida e volta';        // notas
            } elseif ($i === 2) {
                $data[] = 'Alimentação';        // centro_custo
                $data[] = 'Rider técnico';      // descricao
                $data[] = '800';                // valor
                $data[] = 'BRL';                // moeda
                $data[] = '20/02/2025';         // data
                $data[] = 'nao';                // confirmada
                $data[] = 'nao';                // reembolsavel
                $data[] = '';                   // notas
            } else {
                $data = array_merge($data, array_fill(0, 8, ''));
            }
        }

        return $data;
    }
}
