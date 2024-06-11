import {defineConfig} from "vite";
import laravel, {refreshPaths} from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        sourcemap: true
    },
    test: {
        // enable jest-like global test APIs
        globals: true,
        // simulate DOM with happy-dom
        environment: 'happy-dom',
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html']
        },
    }
});
