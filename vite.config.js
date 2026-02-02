import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import angular from '@analogjs/vite-plugin-angular';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.ts'],
            refresh: true,
        }),
        tailwindcss(),
        angular({
            tsconfig: './tsconfig.json'
        })
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
        preserveSymlinks: true
    },
    server: {
        host: '0.0.0.0',
        strictPort: true,
        hmr: {
            host: '144.126.218.214',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/node_modules/**'],
        },
    },
});
