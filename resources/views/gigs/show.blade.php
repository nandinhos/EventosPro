<x-app-layout>
    @php
        // Necessário para a diretiva @js do Alpine, uma forma mais segura de passar dados
        //use Illuminate\Support\Js;
    @endphp

    {{-- Cabeçalho da Página e Botões de Ação --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Detalhes da Gig #{{ $gig->id }}: {{ $gig->artist->name ?? 'N/A' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $gig->gig_date->format('d/m/Y') }} - {{ $gig->location_event_details }}
            </p>
        </div>
        <div class="flex space-x-2 items-center">
            <a href="{{ route('gigs.index', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
            </a>
            <a href="{{ route('gigs.edit', ['gig' => $gig] + $backUrlParams) }}" class="bg-primary-500 hover:bg-primary-600 text-white px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
        </div>
    </div>

    {{-- Grid Principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Incluindo todos os parciais e passando os dados corretos --}}
            @include('gigs._show_general_info', ['gig' => $gig])
            
            @include('gigs._show_financial_summary', ['gig' => $gig, 'financialData' => $financialData])
            
            @include('gigs._show_payments', ['gig' => $gig, 'payments' => $gig->payments])
            
            {{-- ** CORREÇÃO AQUI: Passando a variável $costCenters ** --}}
            @include('gigs._show_costs', ['gig' => $gig, 'costCenters' => $costCenters])

            @include('gigs._show_final_settlements', [
    'gig' => $gig,
    'settlement' => $gig->settlement,
    'calculatedGrossCashBrl' => $financialData['calculatedGrossCashBrl'],
    'calculatedAgencyGrossCommissionBrl' => $financialData['calculatedAgencyGrossCommissionBrl'],
    'calculatedArtistNetPayoutBrl' => $financialData['calculatedArtistNetPayoutBrl'],
    'calculatedBookerCommissionBrl' => $financialData['calculatedBookerCommissionBrl'],
    'calculatedArtistInvoiceValueBrl' => $financialData['calculatedArtistInvoiceValueBrl'],
    'backUrlParams' => $backUrlParams,
    'calculatedTotalConfirmedExpensesBrl' => $financialData['calculatedTotalConfirmedExpensesBrl']
])
            @include('settlements._settle_artist_modal', ['gig' => $gig])
            @include('settlements._settle_booker_modal', ['gig' => $gig])
             <x-modals.request-nf-modal />
        </div>
        <div class="lg:col-span-1 space-y-6">
            @include('gigs._show_activity_logs', ['activityLogs' => $activityLogs])
        </div>
    </div>

    {{-- Modais --}}
    @include('settlements._settle_artist_modal', ['gig' => $gig])
    @include('settlements._settle_booker_modal', ['gig' => $gig])
</x-app-layout>


@push('scripts')
<script>
    function costsManager(gigId, costCentersInitial) {
        return {
            loading: true,
            costsByCenter: [],
            totalConfirmedCosts: 0,
            totalPendingCosts: 0,
            costCentersData: costCentersInitial,
            
            showCostFormModal: false,
            isEditMode: false,
            costFormData: {},

            showConfirmModal: false,
            confirmCostData: { id: null, description: '', date: '{{ today()->format("Y-m-d") }}' },
            
            formatCurrency(value, withSymbol = true) {
                const num = parseFloat(value);
                if (isNaN(num)) return withSymbol ? 'R$ 0,00' : '0,00';
                const options = withSymbol ? { style: 'currency', currency: 'BRL' } : { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                return num.toLocaleString('pt-BR', options);
            },

            async fetchCosts() {
                this.loading = true;
                try {
                    const response = await fetch(`/gigs/${gigId}/costs-json`);
                    if (!response.ok) throw new Error('Falha ao buscar custos');
                    const data = await response.json();
                    this.costsByCenter = data;
                    this.calculateTotals();
                } catch (error) { 
                    console.error('Erro buscando custos:', error);
                } finally { 
                    this.loading = false; 
                }
            },

            calculateTotals() {
                let confirmed = 0, pending = 0;
                (this.costsByCenter || []).forEach(group => {
                    (group.costs || []).forEach(cost => {
                        const costValue = parseFloat(cost.value) || 0;
                        if (cost.is_confirmed) { confirmed += costValue; } else { pending += costValue; }
                    });
                });
                this.totalConfirmedCosts = confirmed;
                this.totalPendingCosts = pending;
            },

            async performAction(url, method, body = null) {
                try {
                    const response = await fetch(url, {
                        method: method.toUpperCase(),
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: body ? JSON.stringify(body) : null
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        let errorMessage = result.message || 'Ocorreu um erro.';
                        if (response.status === 422 && result.errors) {
                            errorMessage += '\n\n- ' + Object.values(result.errors).map(err => err[0]).join('\n- ');
                        }
                        throw new Error(errorMessage);
                    }
                    await this.fetchCosts();
                    this.$dispatch('financial-data-changed', { gigId: gigId });
                } catch (error) {
                    console.error(`Erro na ação ${method} ${url}:`, error);
                    alert(error.message);
                }
            },
            
            unconfirmCost(costId) {
                if (confirm('Reverter confirmação desta despesa?')) {
                    this.performAction(`/gigs/${gigId}/costs/${costId}/unconfirm`, 'PATCH');
                }
            },
            toggleInvoice(costId) {
                this.performAction(`/gigs/${gigId}/costs/${costId}/toggle-invoice`, 'PATCH');
            },
            deleteCost(costId) {
                if (confirm('Tem certeza que deseja excluir esta despesa?')) {
                    this.performAction(`/gigs/${gigId}/costs/${costId}`, 'DELETE');
                }
            },
            
            openNewCostModal() {
                this.isEditMode = false;
                this.costFormData = { id: null, cost_center_id: '', description: '', value: '', currency: 'BRL', expense_date: '{{ today()->format("Y-m-d") }}', notes: '' };
                this.showCostFormModal = true;
                this.$nextTick(() => document.getElementById('modal_cost_center_id')?.focus());
            },
            openEditCostModal(cost) {
                this.isEditMode = true;
                this.costFormData = { 
                    ...cost,
                    cost_center_id: String(cost.cost_center_id || '') 
                };
                this.showCostFormModal = true;
                this.$nextTick(() => document.getElementById('modal_cost_center_id')?.focus());
            },
            openConfirmCostModal(cost) {
                this.confirmCostData = { id: cost.id, description: cost.description || 'Despesa', date: '{{ today()->format("Y-m-d") }}' };
                this.showConfirmModal = true;
                this.$nextTick(() => document.getElementById('confirmation_date_input')?.focus());
            },

            async submitConfirmForm() {
                const url = `/gigs/${gigId}/costs/${this.confirmCostData.id}/confirm`;
                const body = { confirmed_at_date: this.confirmCostData.date };
                
                await this.performAction(url, 'PATCH', body);
                this.showConfirmModal = false;
            },
            async submitCostForm() {
                const url = this.isEditMode 
                    ? `/gigs/${gigId}/costs/${this.costFormData.id}`
                    : `/gigs/${gigId}/costs`;
                const method = this.isEditMode ? 'PUT' : 'POST';
                
                await this.performAction(url, method, this.costFormData);
                this.showCostFormModal = false;
            }
        };
    }
</script>
@endpush