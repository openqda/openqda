/// <reference types="vitest" />
import {defineConfig} from "vite";
import laravel, {refreshPaths} from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        tailwindcss(),
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
                compilerOptions: {
                    isCustomElement: (tag) => ['altcha-widget'].includes(tag),
                }
            },
        }),
    ],
    build: {
        sourcemap: true
    },
    server: {
        watch: {
            ignored: [
                '.idea',
                '.deploy',
                'app/**',
                'vendor/**',
                'bootstrap/**',
                'config/**',
                'data/**',
                'database/**',
                'docker/**',
                'routes/**',
                'storage/**',
                'stories/**'
            ]
        }
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
