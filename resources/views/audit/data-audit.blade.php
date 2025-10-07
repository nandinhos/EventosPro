<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Auditoria de Dados') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="dataAuditApp()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Dashboard de Health Score -->
            <div x-show="showDashboard" x-transition>
                <x-card title="Sistema de Auditoria - Dashboard" icon="fas fa-heartbeat">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Health Score -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 text-center border-2 border-blue-200 dark:border-blue-700">
                            <div class="text-4xl font-bold mb-2"
                                 :class="healthScore >= 90 ? 'text-green-600 dark:text-green-400' :
                                        healthScore >= 70 ? 'text-blue-600 dark:text-blue-400' :
                                        healthScore >= 50 ? 'text-yellow-600 dark:text-yellow-400' :
                                        'text-red-600 dark:text-red-400'"
                                 x-text="healthScore + '%'"></div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 font-semibold">Health Score</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="healthStatus"></div>
                        </div>

                        <!-- Total Issues -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="dashboardStats.total_issues"></div>
                            <div class="text-sm text-blue-600 dark:text-blue-400 mt-1">Total de Issues</div>
                        </div>

                        <!-- Critical Issues -->
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400" x-text="dashboardStats.total_critical"></div>
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">Críticas</div>
                        </div>

                        <!-- Warnings -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400" x-text="dashboardStats.total_warnings"></div>
                            <div class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">Warnings</div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Seleção de Tipo de Auditoria -->
            <x-card title="Selecionar Tipo de Auditoria" icon="fas fa-tasks">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="audit in availableAudits" :key="audit.type">
                        <div @click="selectAudit(audit.type)"
                             class="relative cursor-pointer rounded-lg border-2 p-4 transition-all hover:shadow-lg"
                             :class="selectedAuditType === audit.type ?
                                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                                    'border-gray-200 dark:border-gray-700 hover:border-blue-300'">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <i :class="audit.icon + ' text-2xl text-' + audit.color + '-600 dark:text-' + audit.color + '-400'"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1" x-text="audit.name"></h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400" x-text="audit.description"></p>

                                    <!-- Status do último relatório -->
                                    <template x-if="audit.lastReport">
                                        <div class="mt-2 text-xs space-y-1">
                                            <div class="flex items-center gap-1">
                                                <i class="fas fa-clock text-gray-400"></i>
                                                <span class="text-gray-500 dark:text-gray-400" x-text="audit.lastReport.last_run"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      :class="audit.lastReport.critical_issues > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                                             audit.lastReport.warnings > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                             'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'">
                                                    <span x-text="audit.lastReport.issues_found + ' issues'"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Check icon quando selecionado -->
                                <template x-if="selectedAuditType === audit.type">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Botão Executar Todas -->
                <div class="mt-6 flex justify-between items-center">
                    <button @click="runAllAudits()"
                            :disabled="isLoading"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <i class="fas fa-play-circle mr-2"></i>
                        Executar Todas as Auditorias
                    </button>

                    <button @click="refreshDashboard()"
                            :disabled="isLoading"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-2" :class="isLoading ? 'animate-spin' : ''"></i>
                        Atualizar Dashboard
                    </button>
                </div>
            </x-card>

            <!-- Formulário de Configuração -->
            <x-card title="Configuração da Auditoria" icon="fas fa-cog">
                <form id="auditForm" @submit.prevent="runAudit()" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Tipo de Auditoria -->
                        <div>
                            <label for="audit_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo de Auditoria
                            </label>
                            <select name="audit_type" id="audit_type" x-model="selectedAuditType"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Selecione um tipo</option>
                                <template x-for="audit in availableAudits" :key="audit.type">
                                    <option :value="audit.type" x-text="audit.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Modo de Operação -->
                        <div>
                            <label for="scan_only" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Modo de Operação
                            </label>
                            <select name="scan_only" id="scan_only"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="true">Apenas Escanear</option>
                                <option value="false">Permitir Correções</option>
                            </select>
                        </div>

                        <!-- Data Inicial -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Data Inicial (opcional)
                            </label>
                            <input type="date" name="date_from" id="date_from"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Data Final -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Data Final (opcional)
                            </label>
                            <input type="date" name="date_to" id="date_to"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Botão Executar -->
                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="isLoading || !selectedAuditType"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <template x-if="isLoading">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!isLoading">
                                <i class="fas fa-play mr-2"></i>
                            </template>
                            <span x-text="isLoading ? 'Executando...' : 'Executar Auditoria Selecionada'"></span>
                        </button>
                    </div>
                </form>
            </x-card>

            <!-- Status da Execução -->
            <div x-show="showStatus" x-transition class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Status da Auditoria
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p x-text="statusMessage"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo dos Resultados -->
            <div x-show="showResults" x-transition>
                <x-card title="Resumo dos Resultados" icon="fas fa-chart-bar">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="stats.total"></div>
                            <div class="text-sm text-blue-600 dark:text-blue-400 mt-1">Total de Issues</div>
                        </div>
                        
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400" x-text="stats.critical"></div>
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">Críticas</div>
                        </div>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400" x-text="stats.warnings"></div>
                            <div class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">Warnings</div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="stats.fixed"></div>
                            <div class="text-sm text-green-600 dark:text-green-400 mt-1">Corrigidas</div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Tabela de Issues -->
            <div x-show="showResults" x-transition>
                <x-card title="Issues Detectadas" icon="fas fa-table">
                    <x-slot name="actions">
                        <div class="flex gap-3">
                            <select x-model="filters.severity" @change="applyFilters()" 
                                    class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Todas as Severidades</option>
                                <option value="critical">Críticas</option>
                                <option value="warning">Warnings</option>
                            </select>
                            
                            <select x-model="filters.type" @change="applyFilters()" 
                                    class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Todos os Tipos</option>
                                <option value="referential_integrity">Integridade Referencial</option>
                                <option value="payment_status">Status de Pagamento</option>
                                <option value="commissions">Comissões</option>
                                <option value="required_fields">Campos Obrigatórios</option>
                                <option value="dates">Datas</option>
                            </select>
                        </div>
                    </x-slot>
                    
                    <!-- Barra de Ações em Lote -->
                    <div x-show="selectedIssues.length > 0" x-transition 
                         class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-square text-blue-500 mr-2"></i>
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    <span x-text="selectedIssues.length"></span> issue(s) selecionado(s)
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button @click="clearSelection()"
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 text-xs leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    <i class="fas fa-times mr-1"></i>
                                    Limpar Seleção
                                </button>
                                <button @click="applyBulkFix()"
                                        :disabled="!canApplyBulkFix || isLoading"
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-wrench mr-1"></i>
                                    <span x-text="isLoading ? 'Aplicando...' : 'Corrigir Selecionados'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                                        <input type="checkbox" 
                                               @change="toggleSelectAll()"
                                               :checked="selectAllChecked"
                                               :indeterminate="selectAllIndeterminate"
                                               class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Severidade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Atual</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Sugerido</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="issue in filteredIssues" :key="issue.id">
                                    <tr :class="issue.fixed ? 'bg-green-50 dark:bg-green-900/10' : ''">
                                        <td class="px-6 py-4 whitespace-nowrap w-12">
                                            <template x-if="issue.can_fix && !issue.fixed">
                                                <input type="checkbox" 
                                                       :value="issue.id"
                                                       @change="toggleIssueSelection(issue)"
                                                       :checked="selectedIssues.includes(issue.id)"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100" x-text="issue.contract_number"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100" x-text="issue.gig_date"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100" x-text="issue.artist_name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" 
                                                  x-text="issue.issue_type"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="issue.severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'"
                                                  x-text="issue.severity"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100" x-text="issue.description"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs" x-text="issue.current_value || 'N/A'"></code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <code class="bg-green-100 dark:bg-green-800 px-2 py-1 rounded text-xs" x-text="issue.suggested_value || 'N/A'"></code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <template x-if="issue.can_fix && !issue.fixed">
                                                    <button @click="openFixModal(issue)"
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                                        <i class="fas fa-wrench mr-1"></i>
                                                        Corrigir
                                                    </button>
                                                </template>
                                                <template x-if="issue.fixed">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        <i class="fas fa-check mr-1"></i>
                                                        Corrigido
                                                    </span>
                                                </template>
                                                <template x-if="!issue.can_fix && !issue.fixed">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                                        <i class="fas fa-hand-paper mr-1"></i>
                                                        Manual
                                                    </span>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <template x-if="filteredIssues.length === 0 && showResults">
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            <div class="py-8">
                                                <i class="fas fa-search text-4xl mb-4"></i>
                                                <p>Nenhum issue encontrado com os filtros aplicados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <!-- Modal de Correção -->
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto" 
                 style="display: none;">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-wrench text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                        Aplicar Correção
                                    </h3>
                                    <div class="mt-4 space-y-3" x-show="currentFix">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gig:</label>
                                            <p class="text-sm text-gray-900 dark:text-gray-100" x-text="currentFix?.gig_info"></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Atual:</label>
                                            <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs" x-text="currentFix?.current_value"></code>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Sugerido:</label>
                                            <code class="bg-green-100 dark:bg-green-800 px-2 py-1 rounded text-xs" x-text="currentFix?.suggested_value"></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button @click="applyFix()" 
                                    :disabled="isLoading"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="isLoading ? 'Aplicando...' : 'Aplicar Correção'"></span>
                            </button>
                            <button @click="closeModal()" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações sobre a Auditoria -->
            <x-card title="Sobre a Auditoria de Dados" icon="fas fa-info-circle">
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        A auditoria de dados verifica a integridade e consistência dos dados de gigs, identificando problemas e sugerindo correções automáticas quando possível.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Tipos de Validação:</h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li><strong>Integridade Referencial:</strong> Verifica se artistas e bookers existem</li>
                                <li><strong>Status de Pagamento:</strong> Valida consistência dos status</li>
                                <li><strong>Comissões:</strong> Verifica cálculos de comissão</li>
                                <li><strong>Campos Obrigatórios:</strong> Identifica campos em branco</li>
                                <li><strong>Datas:</strong> Valida lógica de datas</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Severidades:</h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li><strong>Crítica:</strong> Problemas que afetam a integridade dos dados</li>
                                <li><strong>Warning:</strong> Inconsistências que podem ser revisadas</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    @push('scripts')
    <script>
        function dataAuditApp() {
            return {
                // Estado da aplicação
                isLoading: false,
                showStatus: false,
                showResults: false,
                showModal: false,
                showDashboard: false,
                statusMessage: '',

                // Tipo de auditoria selecionado
                selectedAuditType: '',

                // Auditorias disponíveis
                availableAudits: [],

                // Dashboard
                healthScore: 100,
                healthStatus: 'Excelente',
                dashboardStats: {
                    total_issues: 0,
                    total_critical: 0,
                    total_warnings: 0,
                    total_gigs: 0
                },

                // Dados
                auditResults: [],
                currentFix: null,

                // Seleção em lote
                selectedIssues: [],
                bulkFixing: false,

                // Filtros
                filters: {
                    severity: '',
                    type: ''
                },

                // Estatísticas
                stats: {
                    total: 0,
                    critical: 0,
                    warnings: 0,
                    fixed: 0
                },

                // Inicialização
                async init() {
                    console.log('Data Audit App initialized');
                    await this.loadAvailableAudits();
                    await this.refreshDashboard();
                },

                // Carregar auditorias disponíveis
                async loadAvailableAudits() {
                    try {
                        const response = await fetch('{{ route("audit.available-audits") }}', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.availableAudits = data.audits;
                        }
                    } catch (error) {
                        console.error('Erro ao carregar auditorias:', error);
                    }
                },

                // Atualizar dashboard
                async refreshDashboard() {
                    try {
                        const response = await fetch('{{ route("audit.dashboard") }}', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.healthScore = data.health_score.health_score;
                            this.healthStatus = this.getHealthStatusLabel(data.health_score.health_status);
                            this.dashboardStats = {
                                total_issues: data.health_score.total_issues,
                                total_critical: data.health_score.total_critical,
                                total_warnings: data.health_score.total_warnings,
                                total_gigs: data.health_score.total_gigs
                            };

                            // Atualizar status dos audits
                            data.audits.forEach(auditSummary => {
                                const audit = this.availableAudits.find(a => a.type === auditSummary.type);
                                if (audit && auditSummary.last_run) {
                                    audit.lastReport = auditSummary;
                                }
                            });

                            this.showDashboard = true;
                        }
                    } catch (error) {
                        console.error('Erro ao atualizar dashboard:', error);
                    }
                },

                getHealthStatusLabel(status) {
                    const labels = {
                        'excellent': 'Excelente',
                        'good': 'Bom',
                        'fair': 'Regular',
                        'poor': 'Ruim',
                        'critical': 'Crítico'
                    };
                    return labels[status] || status;
                },

                // Selecionar auditoria
                selectAudit(auditType) {
                    this.selectedAuditType = auditType;
                },

                // Executar todas as auditorias
                async runAllAudits() {
                    this.isLoading = true;
                    this.showStatus = true;
                    this.statusMessage = 'Executando todas as auditorias...';

                    try {
                        const response = await fetch('{{ route("audit.run-all-audits") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                scan_only: document.getElementById('scan_only').value === 'true',
                                date_from: document.getElementById('date_from').value,
                                date_to: document.getElementById('date_to').value
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            await this.refreshDashboard();
                            this.showNotification('Todas as auditorias foram executadas com sucesso!', 'success');
                        } else {
                            throw new Error(data.error || 'Erro ao executar auditorias');
                        }
                    } catch (error) {
                        console.error('Erro ao executar auditorias:', error);
                        this.showNotification('Erro ao executar auditorias: ' + error.message, 'error');
                    } finally {
                        this.isLoading = false;
                        this.showStatus = false;
                    }
                },

                // Computed properties
                get filteredIssues() {
                    return this.auditResults.filter(issue => {
                        const severityMatch = !this.filters.severity || issue.severity === this.filters.severity;
                        const typeMatch = !this.filters.type || issue.issue_type === this.filters.type;
                        return severityMatch && typeMatch;
                    });
                },

                get selectAllChecked() {
                    const fixableIssues = this.filteredIssues.filter(issue => issue.can_fix && !issue.fixed);
                    return fixableIssues.length > 0 && fixableIssues.every(issue => this.selectedIssues.includes(issue.id));
                },

                get selectAllIndeterminate() {
                    const fixableIssues = this.filteredIssues.filter(issue => issue.can_fix && !issue.fixed);
                    const selectedCount = fixableIssues.filter(issue => this.selectedIssues.includes(issue.id)).length;
                    return selectedCount > 0 && selectedCount < fixableIssues.length;
                },

                get canApplyBulkFix() {
                    return this.selectedIssues.length > 0 && !this.bulkFixing;
                },

                // Métodos principais
                async runAudit() {
                    if (!this.selectedAuditType) {
                        this.showNotification('Por favor, selecione um tipo de auditoria', 'warning');
                        return;
                    }

                    this.isLoading = true;
                    this.showStatus = true;
                    this.showResults = false;
                    this.statusMessage = 'Iniciando auditoria...';

                    try {
                        const formData = new FormData(document.getElementById('auditForm'));

                        this.statusMessage = 'Executando validações...';

                        const response = await fetch('{{ route("audit.run-specific-audit") }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        // Verificar se a resposta é JSON válida
                        if (!response.ok) {
                            if (response.status === 419) {
                                throw new Error('Token CSRF expirado. Recarregue a página e tente novamente.');
                            }
                            throw new Error(`Erro HTTP: ${response.status}`);
                        }

                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('Resposta inválida do servidor. Esperado JSON.');
                        }

                        const data = await response.json();

                        if (data.success && data.report_path) {
                            this.statusMessage = 'Carregando resultados...';
                            await this.loadAuditIssues(data.report_path);
                            await this.refreshDashboard();
                            this.showNotification('Auditoria executada com sucesso!', 'success');
                        } else {
                            throw new Error(data.error || 'Erro ao executar auditoria');
                        }

                    } catch (error) {
                        console.error('Erro na auditoria:', error);
                        this.showNotification('Erro ao executar auditoria: ' + error.message, 'error');
                    } finally {
                        this.isLoading = false;
                        this.showStatus = false;
                    }
                },

                async loadAuditIssues(reportPath) {
                    try {
                        const response = await fetch('{{ route("audit.get-issues") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ report_path: reportPath })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.auditResults = (data.data || []).map((item, index) => ({
                                ...item,
                                id: `issue_${index}`,
                                fixed: false
                            }));
                            this.updateStats();
                            this.showResults = true;
                        } else {
                            throw new Error(data.error || 'Erro ao carregar resultados');
                        }

                    } catch (error) {
                        console.error('Erro ao carregar issues:', error);
                        this.showNotification('Erro ao carregar resultados da auditoria', 'error');
                    }
                },

                updateStats() {
                    this.stats.total = this.auditResults.length;
                    this.stats.critical = this.auditResults.filter(issue => issue.severity === 'critical').length;
                    this.stats.warnings = this.auditResults.filter(issue => issue.severity === 'warning').length;
                    this.stats.fixed = this.auditResults.filter(issue => issue.fixed).length;
                },

                applyFilters() {
                    // Os filtros são aplicados automaticamente via computed property
                },

                openFixModal(issue) {
                    this.currentFix = {
                        gig_id: issue.gig_id,
                        field: issue.field,
                        new_value: issue.suggested_value,
                        issue_type: issue.issue_type,
                        gig_info: `${issue.contract_number} - ${issue.artist_name}`,
                        current_value: issue.current_value,
                        suggested_value: issue.suggested_value,
                        issue: issue
                    };
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.currentFix = null;
                },

                async applyFix() {
                    if (!this.currentFix) return;
                    
                    this.isLoading = true;
                    
                    try {
                        const response = await fetch('{{ route("audit.apply-fix") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                gig_id: this.currentFix.gig_id,
                                field: this.currentFix.field,
                                new_value: this.currentFix.new_value,
                                issue_type: this.currentFix.issue_type
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Marcar o issue como corrigido
                            this.currentFix.issue.fixed = true;
                            this.currentFix.issue.current_value = data.new_value || this.currentFix.new_value;
                            
                            this.updateStats();
                            this.closeModal();
                            this.showNotification('Correção aplicada com sucesso!', 'success');
                        } else {
                            throw new Error(data.error || 'Erro ao aplicar correção');
                        }
                    } catch (error) {
                        console.error('Erro ao aplicar correção:', error);
                        this.showNotification('Erro ao aplicar correção: ' + error.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Métodos de seleção em lote
                toggleSelectAll() {
                    const fixableIssues = this.filteredIssues.filter(issue => issue.can_fix && !issue.fixed);
                    
                    if (this.selectAllChecked) {
                        // Desmarcar todos
                        fixableIssues.forEach(issue => {
                            const index = this.selectedIssues.indexOf(issue.id);
                            if (index > -1) {
                                this.selectedIssues.splice(index, 1);
                            }
                        });
                    } else {
                        // Marcar todos
                        fixableIssues.forEach(issue => {
                            if (!this.selectedIssues.includes(issue.id)) {
                                this.selectedIssues.push(issue.id);
                            }
                        });
                    }
                },

                toggleIssueSelection(issue) {
                    const index = this.selectedIssues.indexOf(issue.id);
                    if (index > -1) {
                        this.selectedIssues.splice(index, 1);
                    } else {
                        this.selectedIssues.push(issue.id);
                    }
                },

                clearSelection() {
                    this.selectedIssues = [];
                },

                async applyBulkFix() {
                     if (this.selectedIssues.length === 0) return;
                     
                     this.bulkFixing = true;
                     
                     try {
                         const selectedIssueObjects = this.auditResults.filter(issue => 
                             this.selectedIssues.includes(issue.id)
                         );
                         
                         // Preparar dados para correção em lote
                         const fixes = selectedIssueObjects.map(issue => ({
                             gig_id: issue.gig_id,
                             field: issue.field,
                             new_value: issue.suggested_value,
                             issue_type: issue.issue_type
                         }));
                         
                         const response = await fetch('{{ route("audit.apply-bulk-fix") }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                             },
                             body: JSON.stringify({ fixes })
                         });
                         
                         const data = await response.json();
                         
                         if (data.success) {
                             // Atualizar issues que foram corrigidas com sucesso
                             data.results.forEach((result, index) => {
                                 if (result.success) {
                                     const issue = selectedIssueObjects[index];
                                     issue.fixed = true;
                                     issue.current_value = result.new_value || issue.suggested_value;
                                 }
                             });
                             
                             this.updateStats();
                             this.clearSelection();
                             this.showNotification(data.message, 'success');
                         } else {
                             // Processar resultados parciais
                             if (data.results) {
                                 data.results.forEach((result, index) => {
                                     if (result.success) {
                                         const issue = selectedIssueObjects[index];
                                         issue.fixed = true;
                                         issue.current_value = result.new_value || issue.suggested_value;
                                     }
                                 });
                                 this.updateStats();
                             }
                             
                             this.clearSelection();
                             this.showNotification(data.message, 'warning');
                         }
                         
                     } catch (error) {
                         console.error('Erro na correção em lote:', error);
                         this.showNotification('Erro ao aplicar correções em lote: ' + error.message, 'error');
                     } finally {
                         this.bulkFixing = false;
                     }
                 },

                showNotification(message, type = 'info') {
                    // Usar o sistema de notificações do projeto se disponível
                    if (window.showToast) {
                        window.showToast(message, type);
                    } else {
                        // Fallback para alert
                        const prefix = type === 'error' ? 'Erro: ' : type === 'success' ? 'Sucesso: ' : '';
                        alert(prefix + message);
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>