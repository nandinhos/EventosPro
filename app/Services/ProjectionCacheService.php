<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Serviço Centralizado de Cache para Projeções Financeiras.
 *
 * Gerencia o cache de métricas de projeção financeira com TTLs apropriados
 * e métodos centralizados de invalidação.
 *
 * Sprint 3 - Performance Optimization
 */
class ProjectionCacheService
{
    /**
     * TTL padrão para dados estratégicos (1 hora).
     * Dados que mudam raramente: balanço estratégico, métricas globais.
     */
    private const TTL_STRATEGIC = 3600;

    /**
     * TTL padrão para dados operacionais (30 minutos).
     * Dados que mudam com frequência moderada: recebíveis, despesas.
     */
    private const TTL_OPERATIONAL = 1800;

    /**
     * TTL padrão para dados voláteis (15 minutos).
     * Dados que mudam frequentemente: dashboards, relatórios dinâmicos.
     */
    private const TTL_VOLATILE = 900;

    /**
     * Prefixo para todas as chaves de cache de projeções.
     */
    private const CACHE_PREFIX = 'projections:';

    /**
     * Armazena em cache o balanço estratégico.
     *
     * @param  callable  $callback  Função que retorna os dados do balanço
     */
    public function rememberStrategicBalance(callable $callback): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'strategic_balance',
            self::TTL_STRATEGIC,
            $callback
        );
    }

    /**
     * Armazena em cache as contas a receber globais.
     *
     * @param  callable  $callback  Função que retorna os recebíveis
     */
    public function rememberAccountsReceivable(callable $callback): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'global_accounts_receivable',
            self::TTL_OPERATIONAL,
            $callback
        );
    }

    /**
     * Armazena em cache as despesas de gigs.
     *
     * @param  callable  $callback  Função que retorna as despesas
     */
    public function rememberGigExpenses(callable $callback): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'gig_expenses',
            self::TTL_OPERATIONAL,
            $callback
        );
    }

    /**
     * Armazena em cache dados de dashboard voláteis.
     *
     * @param  string  $key  Chave específica do dashboard
     * @param  callable  $callback  Função que retorna os dados
     */
    public function rememberDashboardData(string $key, callable $callback): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX.'dashboard:'.$key,
            self::TTL_VOLATILE,
            $callback
        );
    }

    /**
     * Armazena em cache dados customizados com TTL específico.
     *
     * @param  string  $key  Chave do cache
     * @param  int  $ttl  Tempo de vida em segundos
     * @param  callable  $callback  Função que retorna os dados
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX.$key,
            $ttl,
            $callback
        );
    }

    /**
     * Limpa todo o cache de projeções.
     *
     * Deve ser chamado após operações que afetam múltiplas métricas:
     * - Criação/edição/exclusão de Gigs
     * - Confirmação de pagamentos
     * - Criação de settlements
     * - Mudanças em custos operacionais
     */
    public function clearAll(): void
    {
        Cache::forget(self::CACHE_PREFIX.'strategic_balance');
        Cache::forget(self::CACHE_PREFIX.'global_accounts_receivable');
        Cache::forget(self::CACHE_PREFIX.'gig_expenses');
    }

    /**
     * Limpa cache específico de balanço estratégico.
     */
    public function clearStrategicBalance(): void
    {
        Cache::forget(self::CACHE_PREFIX.'strategic_balance');
    }

    /**
     * Limpa cache específico de recebíveis.
     *
     * Útil quando apenas pagamentos são confirmados.
     */
    public function clearAccountsReceivable(): void
    {
        Cache::forget(self::CACHE_PREFIX.'global_accounts_receivable');
    }

    /**
     * Limpa cache específico de despesas.
     *
     * Útil quando apenas custos de gigs são modificados.
     */
    public function clearGigExpenses(): void
    {
        Cache::forget(self::CACHE_PREFIX.'gig_expenses');
    }

    /**
     * Limpa cache de dashboard por chave específica.
     *
     * @param  string  $key  Chave do dashboard
     */
    public function clearDashboardData(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.'dashboard:'.$key);
    }

    /**
     * Limpa todo o cache de dashboards.
     */
    public function clearAllDashboards(): void
    {
        // Limpa todos os caches que começam com 'projections:dashboard:'
        // Nota: Isso requer suporte a tags ou flush completo do cache
        // Para implementação mais robusta, considere usar cache tags
    }

    /**
     * Retorna as chaves de cache conhecidas para debugging.
     */
    public function getKnownCacheKeys(): array
    {
        return [
            self::CACHE_PREFIX.'strategic_balance',
            self::CACHE_PREFIX.'global_accounts_receivable',
            self::CACHE_PREFIX.'gig_expenses',
        ];
    }

    /**
     * Retorna os TTLs configurados para cada tipo de dado.
     */
    public function getTTLConfig(): array
    {
        return [
            'strategic' => self::TTL_STRATEGIC,
            'operational' => self::TTL_OPERATIONAL,
            'volatile' => self::TTL_VOLATILE,
        ];
    }
}
