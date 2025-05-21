
<div class="max-w-6xl mx-auto px-4 py-8" x-data="{
        tabsOrder: ['contract', 'event', 'costs', 'commissions', 'tags'],
        activeTab: '{{ old('activeTab') ?: 'contract' }}',
        pageTitle: '{{ $submitLabel ?? 'Nova Gig' }}',
        formErrors: {},
        validateForm() {
            this.formErrors = {};
            let isValid = true;

            // Validação da aba Evento
            if (!document.getElementById('artist_id').value) {
                this.formErrors.artist_id = 'O artista é obrigatório';
                isValid = false;
            }
            if (!document.getElementById('gig_date').value) {
                this.formErrors.gig_date = 'A data do evento é obrigatória';
                isValid = false;
            }
            if (!document.getElementById('location_event_details').value) {
                this.formErrors.location_event_details = 'O local do evento é obrigatório';
                isValid = false;
            }
            if (!document.getElementById('cache_value').value) {
                this.formErrors.cache_value = 'O valor do cachê é obrigatório';
                isValid = false;
            }

            // Validação da aba Custos
            const costCenters = document.getElementsByName('cost_center_id[]');
            const costValues = document.getElementsByName('value[]');
            const costDescriptions = document.getElementsByName('description[]');

            for (let i = 0; i < costCenters.length; i++) {
                if (costCenters[i].value && (!costValues[i].value || !costDescriptions[i].value)) {
                    this.formErrors.costs = 'Todos os campos de despesa devem ser preenchidos';
                    isValid = false;
                    break;
                }
            }

            return isValid;
        },
        nextTab() {
            const currentIndex = this.tabsOrder.indexOf(this.activeTab);
            if (currentIndex < this.tabsOrder.length - 1) {
                this.activeTab = this.tabsOrder[currentIndex + 1];
            }
        },
        previousTab() {
            const currentIndex = this.tabsOrder.indexOf(this.activeTab);
            if (currentIndex > 0) {
                this.activeTab = this.tabsOrder[currentIndex - 1];
            }
        }
    }">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6" x-text="pageTitle"></h1>

        <!-- Navegação por Abas -->
        <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700 mb-6">
            <button @click="activeTab = 'contract'" :class="{ 'border-primary-500 text-primary-600': activeTab === 'contract' }" class="px-3 py-2 font-medium text-sm">Contrato</button>
            <button @click="activeTab = 'event'" :class="{ 'border-primary-500 text-primary-600': activeTab === 'event' }" class="px-3 py-2 font-medium text-sm">Evento</button>
            <button @click="activeTab = 'costs'" :class="{ 'border-primary-500 text-primary-600': activeTab === 'costs' }" class="px-3 py-2 font-medium text-sm">Despesas</button>
            <button @click="activeTab = 'commissions'" :class="{ 'border-primary-500 text-primary-600': activeTab === 'commissions' }" class="px-3 py-2 font-medium text-sm">Comissões</button>
            <button @click="activeTab = 'tags'" :class="{ 'border-primary-500 text-primary-600': activeTab === 'tags' }" class="px-3 py-2 font-medium text-sm">Tags</button>
        </nav>

        <!-- Formulário Principal -->
        <form :action="$el.getAttribute('data-action')" method="POST" class="space-y-8" data-action="{{ $action ?? '#' }}" @submit.prevent="if(validateForm()) $el.submit();">
            @csrf
            @if(isset($method) && $method == 'PUT')
                @method('PUT')
            @endif

            <!-- Conteúdo das Abas -->
            <div>
                <div x-show="activeTab === 'contract'" class="space-y-4">
                    <x-gigs.tabs.contract />
                </div>

                <div x-show="activeTab === 'event'" class="space-y-4">
                    <x-gigs.tabs.event :artists="$artists" :bookers="$bookers" />
                </div>

                <div x-show="activeTab === 'costs'" class="space-y-4">
                    <x-gigs.tabs.costs :costCenters="$costCenters" />
                </div>

                <div x-show="activeTab === 'commissions'" class="space-y-4">
                    <x-gigs.tabs.commissions />
                </div>

                <div x-show="activeTab === 'tags'" class="space-y-4">
                    <x-gigs.tabs.tags :tags="$tags" :selectedTags="$selectedTags ?? []" />
                </div>

                <!-- Botões de Navegação -->
                <div class="flex justify-between pt-6">
                    <button type="button"
                            @click="previousTab()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
                        Anterior
                    </button>
                    <button type="button"
                            @click="nextTab()"
                            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                        Próximo
                    </button>
                </div>

                <!-- Botão Finalizar -->
                <div x-show="activeTab === 'tags'" class="pt-6 flex justify-end">
                    <div>
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-save mr-1"></i> {{ $submitLabel ?? 'Salvar Gig' }}
                    </button>
                    <div x-show="Object.keys(formErrors).length > 0" class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
                        <p class="font-medium">Por favor, corrija os seguintes erros:</p>
                        <ul class="list-disc list-inside mt-2">
                            <template x-for="(error, key) in formErrors" :key="key">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </div>
                </div>
            </div>
        </form>
    </div>
