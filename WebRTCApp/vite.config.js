import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        // Allow requests from service hostname inside Docker (vite) and local browser
        allowedHosts: ['vite', 'localhost'],
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            // Do not bind the websocket server to a specific host that
            // may not be available inside the container (EADDRNOTAVAIL).
            // Only set the client port/protocol here; the client host
            // is resolved from the page (or via VITE_DEV_SERVER_URL).
            port: Number(process.env.VITE_HMR_PORT) || 5173,
            protocol: 'ws'
        }
    },
    build: {
        // OPTIMIZACIÓN: Configuraciones de build para performance
        rollupOptions: {
            output: {
                // Code splitting por chunks
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    ui: ['@headlessui/react'],
                    utils: ['@inertiajs/react']
                }
            }
        },
        // Optimizaciones de assets
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            }
        },
        // Configuración de chunks
        chunkSizeWarningLimit: 1000,
        // Compresión de assets
        assetsInlineLimit: 4096
    },
    // OPTIMIZACIÓN: Configuraciones adicionales
    optimizeDeps: {
        include: ['react', 'react-dom', '@inertiajs/react', '@headlessui/react']
    }
});
