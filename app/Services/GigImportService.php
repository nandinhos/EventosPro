<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\ServiceTaker;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GigImportService
{
    /**
     * Número máximo de despesas por linha na planilha.
     */
    public const MAX_EXPENSES_PER_ROW = 5;

    /**
     * Cache de lookup para evitar queries repetidas.
     */
    protected array $artistCache = [];

    protected array $bookerCache = [];

    protected array $costCenterCache = [];

    protected array $serviceTakerCache = [];

    /**
     * Mapeamento de colunas esperadas no arquivo.
     */
    public function getExpectedColumns(): array
    {
        $baseColumns = [
            // Aba Principais
            'artista' => 'Nome do Artista (obrigatório)',
            'booker' => 'Nome do Booker (deixe vazio para Agência)',
            'tomador_servico' => 'Nome/Organização do Tomador de Serviço',
            'data_evento' => 'Data do Evento (DD/MM/AAAA) (obrigatório)',
            'local_evento' => 'Local / Descrição do Evento (obrigatório)',

            // Aba Financeiro
            'valor_contrato' => 'Valor do Contrato (obrigatório)',
            'moeda' => 'Moeda (BRL, USD, EUR, SEK) (obrigatório)',
            'numero_contrato' => 'Número do Contrato',
            'data_contrato' => 'Data do Contrato (DD/MM/AAAA)',
            'status_contrato' => 'Status (sem_contrato, assinado, em_negociacao)',
        ];

        // Adicionar colunas de despesas (até MAX_EXPENSES_PER_ROW)
        for ($i = 1; $i <= self::MAX_EXPENSES_PER_ROW; $i++) {
            $baseColumns["despesa_{$i}_centro_custo"] = "Centro de Custo da Despesa {$i}";
            $baseColumns["despesa_{$i}_descricao"] = "Descrição da Despesa {$i}";
            $baseColumns["despesa_{$i}_valor"] = "Valor da Despesa {$i}";
            $baseColumns["despesa_{$i}_moeda"] = "Moeda da Despesa {$i} (padrão: BRL)";
            $baseColumns["despesa_{$i}_data"] = "Data da Despesa {$i} (padrão: data evento)";
            $baseColumns["despesa_{$i}_confirmada"] = 'Confirmada? (sim/nao)';
            $baseColumns["despesa_{$i}_reembolsavel"] = 'Reembolsável via NF? (sim/nao)';
            $baseColumns["despesa_{$i}_notas"] = "Notas da Despesa {$i}";
        }

        return $baseColumns;
    }

    /**
     * Parseia o arquivo e retorna os dados para preview.
     *
     * @return array{rows: Collection, errors: array, summary: array}
     */
    public function parseFile(string $filePath): array
    {
        $rows = Excel::toCollection(null, $filePath)->first();

        if ($rows->isEmpty()) {
            return [
                'rows' => collect(),
                'errors' => ['O arquivo está vazio.'],
                'summary' => ['total' => 0, 'valid' => 0, 'invalid' => 0],
            ];
        }

        // Primeira linha são os cabeçalhos
        $headers = $rows->first()->map(fn ($h) => $this->normalizeHeader($h))->toArray();
        $dataRows = $rows->slice(1);

        $parsedRows = collect();
        $errors = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2; // +2 porque índice começa em 0 e pulamos cabeçalho
            $rowData = $this->mapRowToData($headers, $row->toArray());
            $rowErrors = $this->validateRow($rowData, $rowNumber);

            $parsedRows->push([
                'row_number' => $rowNumber,
                'data' => $rowData,
                'errors' => $rowErrors,
                'is_valid' => empty($rowErrors),
            ]);

            if (empty($rowErrors)) {
                $validCount++;
            } else {
                $invalidCount++;
                $errors = array_merge($errors, $rowErrors);
            }
        }

        return [
            'rows' => $parsedRows,
            'errors' => $errors,
            'summary' => [
                'total' => $parsedRows->count(),
                'valid' => $validCount,
                'invalid' => $invalidCount,
            ],
        ];
    }

    /**
     * Importa as gigs do arquivo.
     *
     * @return array{success: int, failed: int, errors: array}
     */
    public function import(string $filePath): array
    {
        $parseResult = $this->parseFile($filePath);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($parseResult['rows'] as $row) {
            if (! $row['is_valid']) {
                $failedCount++;
                $errors = array_merge($errors, $row['errors']);

                continue;
            }

            try {
                DB::transaction(function () use ($row) {
                    $gig = $this->createGig($row['data']);
                    $this->createExpenses($gig, $row['data']);
                });
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Linha {$row['row_number']}: Erro ao criar gig - {$e->getMessage()}";
                Log::error("GigImport: Erro na linha {$row['row_number']}", [
                    'error' => $e->getMessage(),
                    'data' => $row['data'],
                ]);
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Normaliza o cabeçalho para formato snake_case.
     */
    protected function normalizeHeader(?string $header): string
    {
        if ($header === null) {
            return '';
        }

        // Remove acentos e converte para snake_case
        $header = iconv('UTF-8', 'ASCII//TRANSLIT', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim($header, '_');

        return $header;
    }

    /**
     * Mapeia uma linha do Excel para o array de dados.
     */
    protected function mapRowToData(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $index => $header) {
            if (! empty($header) && isset($row[$index])) {
                $data[$header] = $row[$index];
            }
        }

        return $data;
    }

    /**
     * Valida uma linha e retorna os erros encontrados.
     */
    protected function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];
        $prefix = "Linha {$rowNumber}:";

        // Campos obrigatórios
        if (empty($data['artista'])) {
            $errors[] = "{$prefix} Artista é obrigatório.";
        } elseif (! $this->findArtist($data['artista'])) {
            $errors[] = "{$prefix} Artista '{$data['artista']}' não encontrado.";
        }

        if (empty($data['data_evento'])) {
            $errors[] = "{$prefix} Data do Evento é obrigatória.";
        } elseif (! $this->parseDate($data['data_evento'])) {
            $errors[] = "{$prefix} Data do Evento inválida: '{$data['data_evento']}'.";
        }

        if (empty($data['local_evento'])) {
            $errors[] = "{$prefix} Local / Descrição do Evento é obrigatório.";
        }

        if (empty($data['valor_contrato'])) {
            $errors[] = "{$prefix} Valor do Contrato é obrigatório.";
        } elseif (! is_numeric($this->parseNumber($data['valor_contrato']))) {
            $errors[] = "{$prefix} Valor do Contrato inválido: '{$data['valor_contrato']}'.";
        }

        if (empty($data['moeda'])) {
            $errors[] = "{$prefix} Moeda é obrigatória.";
        } elseif (! in_array(strtoupper($data['moeda']), ['BRL', 'USD', 'EUR', 'SEK'])) {
            $errors[] = "{$prefix} Moeda inválida: '{$data['moeda']}'. Use BRL, USD, EUR ou SEK.";
        }

        // Validar booker se informado
        if (! empty($data['booker']) && strtolower(trim($data['booker'])) !== 'agência' && trim($data['booker']) !== '') {
            if (! $this->findBooker($data['booker'])) {
                $errors[] = "{$prefix} Booker '{$data['booker']}' não encontrado.";
            }
        }

        // Validar despesas
        for ($i = 1; $i <= self::MAX_EXPENSES_PER_ROW; $i++) {
            $centroCusto = $data["despesa_{$i}_centro_custo"] ?? null;
            $valor = $data["despesa_{$i}_valor"] ?? null;

            // Se tem centro de custo, valor é obrigatório
            if (! empty($centroCusto)) {
                if (! $this->findCostCenter($centroCusto)) {
                    $errors[] = "{$prefix} Centro de Custo '{$centroCusto}' (Despesa {$i}) não encontrado.";
                }
                if (empty($valor)) {
                    $errors[] = "{$prefix} Valor da Despesa {$i} é obrigatório quando Centro de Custo é informado.";
                }
            }
        }

        return $errors;
    }

    /**
     * Cria a Gig a partir dos dados.
     */
    protected function createGig(array $data): Gig
    {
        $artist = $this->findArtist($data['artista']);
        $booker = ! empty($data['booker']) && strtolower(trim($data['booker'])) !== 'agência'
            ? $this->findBooker($data['booker'])
            : null;
        $serviceTaker = ! empty($data['tomador_servico'])
            ? $this->findServiceTaker($data['tomador_servico'])
            : null;

        return Gig::create([
            'artist_id' => $artist->id,
            'booker_id' => $booker?->id,
            'service_taker_id' => $serviceTaker?->id,
            'gig_date' => $this->parseDate($data['data_evento']),
            'location_event_details' => $data['local_evento'],
            'cache_value' => $this->parseNumber($data['valor_contrato']),
            'currency' => strtoupper($data['moeda']),
            'contract_number' => $data['numero_contrato'] ?? null,
            'contract_date' => ! empty($data['data_contrato']) ? $this->parseDate($data['data_contrato']) : null,
            'contract_status' => $this->parseContractStatus($data['status_contrato'] ?? null),
            'payment_status' => 'a_vencer',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => $artist->default_commission_rate ?? 20,
            'booker_commission_type' => 'percent',
            'booker_commission_rate' => $booker?->default_commission_rate ?? 0,
        ]);
    }

    /**
     * Cria as despesas da Gig.
     */
    protected function createExpenses(Gig $gig, array $data): void
    {
        for ($i = 1; $i <= self::MAX_EXPENSES_PER_ROW; $i++) {
            $centroCusto = $data["despesa_{$i}_centro_custo"] ?? null;
            $valor = $data["despesa_{$i}_valor"] ?? null;

            if (empty($centroCusto) || empty($valor)) {
                continue;
            }

            $costCenter = $this->findCostCenter($centroCusto);
            if (! $costCenter) {
                continue;
            }

            GigCost::create([
                'gig_id' => $gig->id,
                'cost_center_id' => $costCenter->id,
                'description' => $data["despesa_{$i}_descricao"] ?? null,
                'value' => $this->parseNumber($valor),
                'currency' => strtoupper($data["despesa_{$i}_moeda"] ?? 'BRL'),
                'expense_date' => ! empty($data["despesa_{$i}_data"])
                    ? $this->parseDate($data["despesa_{$i}_data"])
                    : $gig->gig_date,
                'is_confirmed' => $this->parseBoolean($data["despesa_{$i}_confirmada"] ?? 'nao'),
                'is_invoice' => $this->parseBoolean($data["despesa_{$i}_reembolsavel"] ?? 'nao'),
                'notes' => $data["despesa_{$i}_notas"] ?? null,
            ]);
        }
    }

    /**
     * Encontra um artista pelo nome.
     */
    protected function findArtist(string $name): ?Artist
    {
        $normalizedName = $this->normalizeName($name);

        if (isset($this->artistCache[$normalizedName])) {
            return $this->artistCache[$normalizedName];
        }

        $artist = Artist::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
        $this->artistCache[$normalizedName] = $artist;

        return $artist;
    }

    /**
     * Encontra um booker pelo nome.
     */
    protected function findBooker(string $name): ?Booker
    {
        $normalizedName = $this->normalizeName($name);

        if (isset($this->bookerCache[$normalizedName])) {
            return $this->bookerCache[$normalizedName];
        }

        $booker = Booker::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
        $this->bookerCache[$normalizedName] = $booker;

        return $booker;
    }

    /**
     * Encontra um centro de custo pelo nome.
     */
    protected function findCostCenter(string $name): ?CostCenter
    {
        $normalizedName = $this->normalizeName($name);

        if (isset($this->costCenterCache[$normalizedName])) {
            return $this->costCenterCache[$normalizedName];
        }

        $costCenter = CostCenter::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
        $this->costCenterCache[$normalizedName] = $costCenter;

        return $costCenter;
    }

    /**
     * Encontra um tomador de serviço pelo nome/organização.
     */
    protected function findServiceTaker(string $name): ?ServiceTaker
    {
        $normalizedName = $this->normalizeName($name);

        if (isset($this->serviceTakerCache[$normalizedName])) {
            return $this->serviceTakerCache[$normalizedName];
        }

        $serviceTaker = ServiceTaker::whereRaw('LOWER(organization) = ?', [strtolower(trim($name))])->first();
        $this->serviceTakerCache[$normalizedName] = $serviceTaker;

        return $serviceTaker;
    }

    /**
     * Normaliza um nome para uso como chave de cache.
     */
    protected function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * Faz parse de uma data.
     */
    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Se for número (Excel armazena datas como números)
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp(($value - 25569) * 86400);
            }

            // Tenta formatos comuns
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y'];
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback para parse genérico
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Faz parse de um número.
     */
    protected function parseNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Remove espaços e formatação brasileira
        $value = str_replace([' ', '.'], '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    /**
     * Faz parse de um boolean.
     */
    protected function parseBoolean(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['sim', 'yes', 'true', '1', 's', 'y']);
    }

    /**
     * Faz parse do status do contrato.
     */
    protected function parseContractStatus(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'sem_contrato';
        }

        $value = strtolower(trim($value));
        $mapping = [
            'assinado' => 'assinado',
            'em_negociacao' => 'em_negociacao',
            'em negociacao' => 'em_negociacao',
            'em negociação' => 'em_negociacao',
            'negociacao' => 'em_negociacao',
            'sem_contrato' => 'sem_contrato',
            'sem contrato' => 'sem_contrato',
            'n/a' => 'sem_contrato',
        ];

        return $mapping[$value] ?? 'sem_contrato';
    }
}
