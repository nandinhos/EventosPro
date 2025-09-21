<?php

namespace App\Services;

use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CommissionPaymentValidationService
{
    /**
     * Valida se é possível pagar comissão para um evento específico
     * 
     * @param Gig $gig
     * @param bool $allowExceptions Se deve permitir exceções para eventos futuros
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateBookerCommissionPayment(Gig $gig, bool $allowExceptions = false): array
    {
        // Verificar se o evento já foi realizado
        $eventDate = $gig->gig_date;
        $today = Carbon::today();
        
        if ($eventDate->lessThan($today)) {
            return ['valid' => true, 'message' => 'Evento já realizado'];
        }
        
        // Se o evento é futuro e não permite exceções
        if (!$allowExceptions) {
            return [
                'valid' => false, 
                'message' => "Não é possível pagar comissão para evento futuro (Data: {$eventDate->format('d/m/Y')})"
            ];
        }
        
        // Se permite exceções, verificar se há justificativa
        if ($this->hasPaymentException($gig)) {
            return ['valid' => true, 'message' => 'Evento futuro com exceção autorizada'];
        }
        
        return [
            'valid' => false, 
            'message' => "Evento futuro sem exceção autorizada (Data: {$eventDate->format('d/m/Y')})"
        ];
    }
    
    /**
     * Valida se é possível pagar comissão para um artista
     * 
     * @param Gig $gig
     * @param bool $allowExceptions Se deve permitir exceções para eventos futuros
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateArtistPayment(Gig $gig, bool $allowExceptions = false): array
    {
        // Mesma lógica para artistas
        return $this->validateBookerCommissionPayment($gig, $allowExceptions);
    }
    
    /**
     * Valida múltiplos eventos para pagamento em lote
     * 
     * @param Collection|array $gigs
     * @param bool $allowExceptions
     * @return array ['valid_gigs' => Collection, 'invalid_gigs' => Collection, 'errors' => array]
     */
    public function validateBatchPayment($gigs, bool $allowExceptions = false): array
    {
        $validGigs = collect();
        $invalidGigs = collect();
        $errors = [];
        
        foreach ($gigs as $gig) {
            $validation = $this->validateBookerCommissionPayment($gig, $allowExceptions);
            
            if ($validation['valid']) {
                $validGigs->push($gig);
            } else {
                $invalidGigs->push($gig);
                $errors[] = "Gig #{$gig->id} ({$gig->artist->name}): {$validation['message']}";
            }
        }
        
        return [
            'valid_gigs' => $validGigs,
            'invalid_gigs' => $invalidGigs,
            'errors' => $errors
        ];
    }
    
    /**
     * Verifica se há exceção autorizada para pagamento antecipado
     * 
     * @param Gig $gig
     * @return bool
     */
    private function hasPaymentException(Gig $gig): bool
    {
        // Verificar se há settlement com exceção autorizada
        $settlement = $gig->settlement;
        
        if (!$settlement) {
            return false;
        }
        
        // Verificar se há campos específicos para exceção (se existirem)
        // Por enquanto, vamos verificar nas notas se há menção de exceção
        $notes = strtolower($settlement->notes ?: '');
        
        return str_contains($notes, 'exceção') || 
               str_contains($notes, 'excecao') || 
               str_contains($notes, 'antecipado') ||
               str_contains($notes, 'autorizado');
    }
    
    /**
     * Cria uma exceção para pagamento antecipado
     * 
     * @param Gig $gig
     * @param string $reason
     * @param string $authorizedBy
     * @return bool
     */
    public function createPaymentException(Gig $gig, string $reason, string $authorizedBy): bool
    {
        try {
            $settlement = $gig->settlement;
            if (!$settlement) {
                $settlement = new \App\Models\Settlement(['gig_id' => $gig->id]);
            }
            
            $exceptionNote = "\n[EXCEÇÃO AUTORIZADA " . now()->format('d/m/Y H:i') . "]: {$reason} - Autorizado por: {$authorizedBy}";
            $settlement->notes = ($settlement->notes ?: '') . $exceptionNote;
            $settlement->save();
            
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao criar exceção de pagamento para Gig {$gig->id}: " . $e->getMessage());
            return false;
        }
    }
}