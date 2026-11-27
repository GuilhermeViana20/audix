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
                'resources/js/exportarDocxPragas.js',
                'resources/js/maiusculas.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // O worker do pdf.js vem como .mjs; alguns servidores (ex.: Hostinger) entregam .mjs
                // como text/plain e o navegador recusa o módulo. Em .js, o MIME é sempre de JavaScript.
                assetFileNames: (asset) => {
                    const nome = asset.names?.[0] ?? asset.name ?? '';
                    return nome.endsWith('.mjs') ? 'assets/[name]-[hash].js' : 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
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
