import './bootstrap'; // Importa o bootstrap do axios, etc.
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

// Importar componentes Alpine
import expenseRowDetail from './components/expense-row-detail';

// Importar o Chart.js da pasta node_modules
import { Chart, registerables } from 'chart.js';

// Registrar todos os componentes do Chart.js (controllers, escalas, etc.)
Chart.register(...registerables);

import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Chart = Chart; // Torna Chart.js disponível globalmente se precisar usá-lo em x-init, etc.
window.Swal = Swal;

Alpine.plugin(focus);
Alpine.plugin(collapse);

Alpine.start();


// Você pode inicializar gráficos aqui ou em componentes Alpine específicos
// Exemplo: document.addEventListener('livewire:init', () => { /* inicializar gráficos */ });
// Ou diretamente em <canvas x-init="...">                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          

document.addEventListener('alpine:init', () => {
    // Registrar componente de despesa
    Alpine.data('expenseRowDetail', expenseRowDetail);

    Alpine.data('tabs', () => ({
        tabsOrder: ['contract', 'event', 'costs', 'commissions', 'tags'],
        activeTab: 'contract',
        pageTitle: '',
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
    }));
});