import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    darkMode: 'selector',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // custom colors generated with https://uicolors.app/create
            // see also https://www.figma.com/community/plugin/1242548152689430610/tailwind-css-color-generator
            colors: {
                primary: {
                  'l': '#347aad',
                  'd': '#eaeaea',
                },
                secondary: {
                  'l': '#0898dd',
                    'd': '#00ffff'
                },
                background: {
                    'l': '#d3d3d3',
                    'd': '#090909'
                },
                surface: {
                  'l': '#f3f3f4',
                  'd': '#1a1a1a'
                },
                label: {
                  'l': '#1a1a1a',
                  'lhover': '#ffffff',
                  'd': '#ffffff',
                  'dhover': '#1a1a1a'
                },
                passive: {
                  'l': '#7a7a7a',
                  'd': '#a7a7a7'
                },
                outline: {
                    'l': '#1a1a1a',
                    'd': '#ffffff',
                },
                danger: {
                  'l': '#891328',
                  'd':  '#e88627'
                },
                porsche: {
                    //DEFAULT: '#eea95b', // 400
                    '50': '#fef8ee',
                    '100': '#fcf0d8',
                    '200': '#f8ddb0',
                    '300': '#f2c37f',
                    '400': '#eea95b',
                    '500': '#e88627',
                    '600': '#d96c1d',
                    '700': '#b4531a',
                    '800': '#90421c',
                    '900': '#74381a',
                    '950': '#3e1b0c',
                }
            },
        },
    },
    plugins: [forms, typography],
    safelist: [{
        // https://stackoverflow.com/questions/71647859/tailwind-css-certain-custom-colors-are-not-working
        pattern: /(bg|text|border)-(cerulean|porsche|silver)/
    }]
};
