import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/layout.css',
                'resources/css/admin/components.css',
                'resources/css/admin/category-tree.css',
                'resources/css/products/category-form.css',
                'resources/css/products/product-form.css',
                'resources/css/products/media-gallery.css',
                'resources/css/admin/media-admin.css',
                'resources/css/admin/feature-browser.css',
                'resources/css/components/category-picker.css',
                'resources/css/products/compatibility-tiles.css',
                'resources/css/visual-editor/editor.css',
                'resources/css/visual-editor/preview.css',
                'resources/js/app.js',
            ],
            refresh: [
                'resources/views/**',
                'app/Http/Livewire/**',
                'resources/js/**',
            ],
        }),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/alpinejs')) {
                        return 'alpine';
                    }
                }
            }
        }
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});