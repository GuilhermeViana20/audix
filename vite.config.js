import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/carregando.js',
                'resources/js/toast.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',

        cors: {
            origin: 'http://localhost:8080',
        },

        hmr: {
            host: 'localhost',
            clientPort: 5173,
        },

        watch: {
            usePolling: true,
            interval: 300,
        },
    },
});
