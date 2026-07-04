/// <reference types="vitest" />
import {defineConfig} from "vite";
import laravel, {refreshPaths} from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                  isCustomElement: (tag) => tag.indexOf('-') >= 0
                }
            },
        }),
        laravel({
            input: "resources/js/app.js",
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
        tailwindcss(),
    ],
    build: {
        sourcemap: true
    },
    server: {
        watch: {
            ignored: [
                '**/.laravel/**',
                '**/.deploy/**',
                '**/.phpunit.cache/**',
                '**/.idea',
                '**/.deploy',
                '**/.git/**',
                '**/dist/**',
                '**/node_modules/**',
                '**/app/**',
                '**/vendor/**',
                '**/bootstrap/**',
                '**/config/**',
                '**/data/**',
                '**/database/**',
                '**/docker/**',
                '**/routes/**',
                '**/storage/**',
                '**/scripts/**',
                '**/stories/**',
            ]
        }
    },
    test: {
        include: ['resources/js/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
        exclude: [
            '**/cypress/**',
            '**/.{idea,git,cache,output,temp}/**',
            '**/stories/**',
            '**/.storybook/**',
            '**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*',
            '**/dist/**',
            '**/node_modules/**',
            '**/app/**',
            '**/bootstrap/**',
            '**/config/**',
            '**/coverage/**',
            '**/data/**',
            '**/database/**',
            '**/docker/**',
            '**/routes/**',
            '**/storage/**',
            '**/scripts/**',
            '**/stories/**',
            '**/vendor/**',
        ],

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
