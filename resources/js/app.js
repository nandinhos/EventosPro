import './bootstrap'; // Importa o bootstrap do axios, etc.
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto'; // Importa Chart.js

window.Alpine = Alpine;
window.Chart = Chart; // Torna Chart.js disponível globalmente se precisar usá-lo em x-init, etc.

Alpine.start();

// Você pode inicializar gráficos aqui ou em componentes Alpine específicos
// Exemplo: document.addEventListener('livewire:init', () => { /* inicializar gráficos */ });
// Ou diretamente em <canvas x-init="...">                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          