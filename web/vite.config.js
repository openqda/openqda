/// <reference types="vitest" />
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
        include: ['resources/js/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
        exclude: [
            '**/node_modules/**',
            'public/',
            '**/dist/**',
            '**/cypress/**',
            '**/.{idea,git,cache,output,temp}/**',
            'stories',
            '.storybook',
            'app/',
            'bootstrap/',
            'config/',
            'database/',
            'routes/',
            '**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*'],
        // enable jest-like global test APIs
        globals: true,
        // simulate DOM with happy-dom
        environment: 'happy-dom',
        coverage: {
            enabled: true,
            subdir: true,
            include: ['resources/js/**/*'],
            provider: 'v8',
            reporter: ['text', 'html']
        },
    }
});
