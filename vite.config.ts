import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';

export default defineConfig({
    ssr: {
        // Bundle autocontenido para el servidor SSR: sin node_modules en runtime.
        noExternal: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                local('Instrument Sans', {
                    src: 'resources/fonts/instrument-sans/*.{woff2,woff}',
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        // Análisis del bundle: ACTIVAR con ANALYZE=1 (genera dist/stats.html).
        // Útil para detectar dependencias pesadas en el paquete de producción.
        ...(process.env.ANALYZE === '1' ? [visualizer({ filename: 'dist/stats.html', gzipSize: true })] : []),
    ],
});
