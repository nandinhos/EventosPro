<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Utils\ValueParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyImportService
{
    /**
     * Importa contratos legados.
     */
    public function importContracts(array $contracts): array
    {
        $importedCount = 0;
        $errors = [];

        foreach ($contracts as $index => $data) {
            try {
                DB::beginTransaction();

                $artist = ValueParser::findArtist($data['artista']);
                if (! $artist) {
                    $errors[] = "Contrato {$index}: Artista '{$data['artista']}' não encontrado.";
                    DB::rollBack();
                    continue;
                }

                $gig = Gig::create([
                    'legal_entity_id' => 4, // Coral
                    'contract_data_status' => 'Legacy',
                    'artist_id' => $artist->id,
                    'contract_number' => $data['numero_contrato'],
                    'gig_date' => $data['data_evento'],
                    'cache_value' => $data['valor_bruto'],
                    'currency' => strtoupper($data['moeda'] ?? 'BRL'),
                    'contract_status' => $data['status'] ?? 'Confirmado',
                    'location_event_details' => trim(($data['venue'] ?? '').' '.($data['city'] ?? '').' '.($data['state'] ?? '')),
                    'payment_status' => 'a_vencer',
                    'artist_payment_status' => 'pendente',
                    'booker_payment_status' => 'pendente',
                ]);

                $importedCount++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Contrato {$index}: ".$e->getMessage();
                Log::error("Legacy Import Error (Contract): ".$e->getMessage());
            }
        }

        return [
            'count' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Preview de contratos legados sem salvar.
     */
    public function previewContracts(array $contracts): array
    {
        $rows = [];
        foreach ($contracts as $index => $data) {
            $artist = ValueParser::findArtist($data['artista']);
            $rows[] = [
                'index' => $index,
                'numero_contrato' => $data['numero_contrato'],
                'artista' => $data['artista'],
                'artist_found' => $artist ? $artist->name : null,
                'contract_found' => null,
                'status' => $artist ? 'valid' : 'error',
                'message' => $artist ? 'Pronto para importar' : "Artista '{$data['artista']}' não encontrado no sistema.",
                'data' => $data
            ];
        }
        return $rows;
    }

    /**
     * Importa recebíveis legados.
     */
    public function importReceivables(array $receivables): array
    {
        $importedCount = 0;
        $errors = [];

        foreach ($receivables as $index => $data) {
            try {
                DB::beginTransaction();

                $gig = Gig::where('contract_number', $data['contrato_ref'])->first();
                if (! $gig) {
                    $errors[] = "Recebível {$index}: Contrato '{$data['contrato_ref']}' não encontrado.";
                    DB::rollBack();
                    continue;
                }

                $paymentData = [
                    'gig_id' => $gig->id,
                    'description' => "Parcela ".$data['parcela'],
                    'due_value' => $data['valor'],
                    'due_date' => $data['data_vencimento'],
                    'currency' => $gig->currency,
                ];

                if (($data['status_pagamento'] ?? '') === 'pago') {
                    $paymentData['received_value_actual'] = $data['valor'];
                    $paymentData['received_date_actual'] = $data['data_pagamento'] ?? $data['data_vencimento'];
                    $paymentData['confirmed_at'] = now();
                }

                Payment::create($paymentData);

                $importedCount++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Recebível {$index}: ".$e->getMessage();
                Log::error("Legacy Import Error (Receivable): ".$e->getMessage());
            }
        }

        return [
            'count' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Preview de recebíveis legados.
     */
    public function previewReceivables(array $receivables): array
    {
        $rows = [];
        foreach ($receivables as $index => $data) {
            $gig = Gig::where('contract_number', $data['contrato_ref'])->first();
            $rows[] = [
                'index' => $index,
                'contrato_ref' => $data['contrato_ref'],
                'parcela' => $data['parcela'],
                'valor' => $data['valor'],
                'contract_found' => $gig ? "Gig #{$gig->id}" : null,
                'artist_found' => null,
                'status' => $gig ? 'valid' : 'error',
                'message' => $gig ? 'Vínculo encontrado' : "Contrato '{$data['contrato_ref']}' não encontrado.",
                'data' => $data
            ];
        }
        return $rows;
    }

    /**
     * Importa pagamentos legados (Payables/Custos).
     */
    public function importPayables(array $payables): array
    {
        $importedCount = 0;
        $errors = [];

        foreach ($payables as $index => $data) {
            try {
                DB::beginTransaction();

                $gigId = null;
                if (! empty($data['ctr_ref'])) {
                    $gig = Gig::where('contract_number', $data['ctr_ref'])->first();
                    $gigId = $gig?->id;
                }

                $costCenter = ValueParser::findCostCenter('Legacy Cost') ?? 
                             ValueParser::findCostCenter('Geral') ?? 
                             DB::table('cost_centers')->first()?->id;

                GigCost::create([
                    'gig_id' => $gigId,
                    'cost_center_id' => $costCenter,
                    'description' => $data['descricao'],
                    'value' => $data['valor_devido'],
                    'expense_date' => $data['data_devida'],
                    'currency' => 'BRL',
                    'notes' => "Contraparte: ".($data['contraparte'] ?? 'N/A'),
                    'is_confirmed' => ($data['status'] ?? '') === 'paid',
                    'confirmed_at' => ($data['status'] ?? '') === 'paid' ? now() : null,
                ]);

                $importedCount++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Pagamento {$index}: ".$e->getMessage();
                Log::error("Legacy Import Error (Payable): ".$e->getMessage());
            }
        }

        return [
            'count' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Preview de pagamentos legados.
     */
    public function previewPayables(array $payables): array
    {
        $rows = [];
        foreach ($payables as $index => $data) {
            $gig = ! empty($data['ctr_ref']) ? Gig::where('contract_number', $data['ctr_ref'])->first() : null;
            $rows[] = [
                'index' => $index,
                'ctr_ref' => $data['ctr_ref'] ?? 'Geral',
                'descricao' => $data['descricao'],
                'valor' => $data['valor_devido'],
                'contract_found' => $gig ? "Gig #{$gig->id}" : ($data['ctr_ref'] ? null : 'N/A (Custo Geral)'),
                'artist_found' => null,
                'status' => ($gig || empty($data['ctr_ref'])) ? 'valid' : 'warning',
                'message' => $gig ? 'Vínculo encontrado' : (empty($data['ctr_ref']) ? 'Custo sem contrato (Geral)' : "Contrato '{$data['ctr_ref']}' não encontrado (será importado como Geral)."),
                'data' => $data
            ];
        }
        return $rows;
    }

    /**
     * Exporta 100% dos registros do banco no formato JSON de integração.
     */
    public function exportCurrentGigsAsLegacyFormat(): array
    {
        // 1. Contratos (Gigs) - Todos os 887
        $gigs = Gig::with('artist')->latest()->get();
        $contracts = $gigs->map(function ($gig) {
            return [
                "numero_contrato" => $gig->contract_number ?? "MOCK-{$gig->id}",
                "artista" => $gig->artist->name ?? 'N/A',
                "data_evento" => $gig->gig_date ? $gig->gig_date->format('Y-m-d') : null,
                "valor_bruto" => (float) $gig->cache_value,
                "moeda" => $gig->currency ?? 'BRL',
                "status" => $gig->contract_status,
                "venue" => $gig->location_event_details,
            ];
        })->toArray();

        // 2. Recebíveis (Payments) - Todos os 2922
        $payments = Payment::with('gig')->get();
        $receivables = $payments->map(function ($payment) {
            return [
                "contrato_ref" => $payment->gig->contract_number ?? "MOCK-{$payment->gig_id}",
                "parcela" => $payment->description,
                "valor" => (float) $payment->due_value,
                "data_vencimento" => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                "status_pagamento" => $payment->confirmed_at ? 'pago' : 'pendente',
                "data_pagamento" => $payment->received_date_actual ? $payment->received_date_actual->format('Y-m-d') : null
            ];
        })->toArray();

        // 3. Pagamentos/Custos (GigCosts) - Todos os 4166
        $costs = GigCost::with('gig')->get();
        $payables = $costs->map(function ($cost) {
            return [
                "ctr_ref" => $cost->gig->contract_number ?? ($cost->gig_id ? "MOCK-{$cost->gig_id}" : null),
                "descricao" => $cost->description,
                "contraparte" => "N/A",
                "valor_devido" => (float) $cost->value,
                "data_devida" => $cost->expense_date ? $cost->expense_date->format('Y-m-d') : null,
                "status" => $cost->is_confirmed ? 'paid' : 'pending'
            ];
        })->toArray();

        return [
            'contracts' => $contracts,
            'receivables' => $receivables,
            'payables' => $payables
        ];
    }
}
