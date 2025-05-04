import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Mantém o dark mode
    content: [
        // Caminhos onde o Tailwind deve procurar por classes para NÃO removê-las no build
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php', // Paginação Laravel
        './storage/framework/views/*.php', // Views cacheadas
        './resources/views/**/*.blade.php', // SUAS VIEWS BLADE (IMPORTANTE!)
        './resources/js/**/*.js', // Arquivos JS (se usar classes Tailwind no JS)
        // Adicione outros caminhos se necessário (ex: para componentes Livewire)
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans], // Fonte padrão do Breeze
            },
            colors: { // Adicione sua paleta primary aqui
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                }
            }
        },
    },

    plugins: [forms],
};