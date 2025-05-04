import './bootstrap'; // Importa o bootstrap do axios, etc.
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto'; // Importa Chart.js
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Chart = Chart; // Torna Chart.js disponível globalmente se precisar usá-lo em x-init, etc.
window.Swal = Swal;

Alpine.start();


// Você pode inicializar gráficos aqui ou em componentes Alpine específicos
// Exemplo: document.addEventListener('livewire:init', () => { /* inicializar gráficos */ });
// Ou diretamente em <canvas x-init="...">                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          