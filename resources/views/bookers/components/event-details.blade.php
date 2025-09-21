<!-- Componente de Detalhes do Evento -->
<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informações do Evento -->
        <div>
            <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informações do Evento</h5>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">ID do Evento:</span>
                    <span class="text-sm text-gray-900 dark:text-white">#{{ $event['id'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Data do Evento:</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $event['gig_date'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Artista:</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $event['artist_name'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Local:</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $event['location'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor do Cachê:</span>
                    <span class="text-sm text-gray-900 dark:text-white">R$ {{ number_format($event['cache_value_brl'], 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Gestão de Comissões -->
        <div>
            <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Gestão de Comissões</h5>
            
            <!-- Validação de Regra de Negócio -->
            @if($type === 'future' && !$event['can_pay_commission'] && !$event['is_exception'])
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Evento não realizado - Pagamento de comissão bloqueado
                        </span>
                    </div>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                        Para pagar comissão de evento futuro, marque como exceção justificada.
                    </p>
                </div>
            @endif

            <form class="space-y-4 event-commission-form" data-event-id="{{ $event['id'] }}" data-type="{{ $type }}">
                <!-- Status de Pagamento do Booker -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Status de Pagamento do Booker
                    </label>
                    <select name="booker_payment_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="pendente" {{ $event['booker_payment_status'] === 'pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="pago" {{ $event['booker_payment_status'] === 'pago' ? 'selected' : '' }}>Pago</option>
                        <option value="cancelado" {{ $event['booker_payment_status'] === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>

                <!-- Valor da Comissão -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Comissão do Booker (R$)
                    </label>
                    <input type="number" 
                           name="booker_commission_brl" 
                           value="{{ $event['booker_commission_brl'] }}" 
                           step="0.01" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>

                <!-- Exceção para Eventos Futuros -->
                @if($type === 'future')
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="is_exception" 
                                   value="1" 
                                   {{ $event['is_exception'] ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Marcar como exceção justificada
                            </span>
                        </label>
                    </div>

                    <!-- Campo de Justificativa (aparece quando exceção está marcada) -->
                    <div id="exception-justification-{{ $event['id'] }}" class="{{ $event['is_exception'] ? '' : 'hidden' }}" data-event-id="{{ $event['id'] }}">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Justificativa da Exceção
                        </label>
                        <textarea name="exception_notes" 
                                  rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Descreva o motivo da exceção...">{{ $event['exception_notes'] ?? '' }}</textarea>
                    </div>
                @endif

                <!-- Observações Gerais -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Observações
                    </label>
                    <textarea name="notes" 
                              rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                              placeholder="Observações adicionais...">{{ $event['notes'] ?? '' }}</textarea>
                </div>

                <!-- Botões de Ação -->
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" 
                            data-event-id="{{ $type }}-{{ $event['id'] }}"
                            onclick="toggleEventDetails(this.dataset.eventId)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JavaScript para gerenciar a funcionalidade de exceções
document.addEventListener('DOMContentLoaded', function() {
    // Gerenciar exibição do campo de justificativa
    const exceptionCheckbox = document.querySelector('input[name="is_exception"]');
    if (exceptionCheckbox) {
        exceptionCheckbox.addEventListener('change', function() {
            const form = this.closest('form');
            const eventId = form.getAttribute('data-event-id');
            const justificationDiv = document.getElementById('exception-justification-' + eventId);
            if (this.checked) {
                justificationDiv.classList.remove('hidden');
            } else {
                justificationDiv.classList.add('hidden');
            }
        });
    }
});

// Função para alternar exibição dos detalhes do evento
function toggleEventDetails(eventId) {
    const detailsRow = document.getElementById(eventId);
    if (detailsRow) {
        detailsRow.classList.toggle('hidden');
    }
}

// Função para atualizar comissão do evento
function updateEventCommission(event, eventId, type) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Adicionar CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/bookers/events/${eventId}/commission`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            showNotification('Comissão atualizada com sucesso!', 'success');
            
            // Recarregar a página para atualizar os dados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Erro ao atualizar comissão', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar comissão', 'error');
    });
}

// Função para mostrar notificações
function showNotification(message, type) {
    // Implementar sistema de notificação (pode usar Toastr, SweetAlert, etc.)
    alert(message); // Placeholder - substituir por sistema de notificação mais elegante
}
</script>