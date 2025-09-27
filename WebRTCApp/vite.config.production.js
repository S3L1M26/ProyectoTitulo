import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: false, // Disabled in production
        }),
        react(),
    ],
    
    // ===========================================
    // PRODUCTION BUILD CONFIGURATION
    // ===========================================
    build: {
        // Output directory
        outDir: 'public/build',
        emptyOutDir: true,
        
        // Optimize for production
        minify: 'terser',
        sourcemap: false,
        
        // Chunk optimization
        rollupOptions: {
            output: {
                // Split vendor chunks for better caching
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    inertia: ['@inertiajs/react'],
                    utils: ['axios', 'crypto-js', 'jssip'],
                },
                
                // Consistent file naming
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/\.(css)$/.test(assetInfo.name)) {
                        return `css/[name]-[hash].${ext}`;
                    }
                    if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
                        return `images/[name]-[hash].${ext}`;
                    }
                    if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
                        return `fonts/[name]-[hash].${ext}`;
                    }
                    return `assets/[name]-[hash].${ext}`;
                },
            },
        },
        
        // Bundle size optimization
        chunkSizeWarningLimit: 1000,
        
        // Terser options for JS minification
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
                drop_debugger: true,
            },
        },
        
        // CSS optimization
        cssCodeSplit: true,
        cssMinify: true,
    },
    
    // ===========================================
    // OPTIMIZATION
    // ===========================================
    define: {
        // Remove development code in production
        __DEV__: false,
    },
    
    // ===========================================
    // PREVIEW CONFIGURATION (for testing builds)
    // ===========================================
    preview: {
        port: 4173,
        host: '0.0.0.0',
    },
});