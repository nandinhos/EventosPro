// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Seus pontos de entrada CSS e JS
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            // Atualiza o navegador automaticamente durante `npm run dev`
            refresh: true,
        }),
    ],
    // Opcional: Configuração do servidor de desenvolvimento
    server: {
        hmr: {
            host: 'localhost', // Garante que o HMR funcione bem no Docker
        },
        watch: { // Observa mudanças nos Blad e recarrega
            usePolling: true,
            interval: 1000,
        },
        host: '0.0.0.0', // Necessário para acessar de fora do container
        port: 5173, // Porta padrão do Vite
    },
});