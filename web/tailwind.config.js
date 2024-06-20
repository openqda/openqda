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
    darkMode: ['class', '[data-mode="dark"]'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // custom colors generated with https://uicolors.app/create
            // see also https://www.figma.com/community/plugin/1242548152689430610/tailwind-css-color-generator
            colors: {
                cerulean: {
                    //DEFAULT: '#017db3', // 700
                    '50': '#f0faff',
                    '100': '#e0f4fe',
                    '200': '#b9eafe',
                    '300': '#7bdbfe',
                    '400': '#35c8fb',
                    '500': '#0bb1ec',
                    '600': '#008fca',
                    '700': '#017db3',
                    '800': '#056187',
                    '900': '#0b4f6f',
                    '950': '#07324a',
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
                },
                silver: {
                    //DEFAULT: '#c5c5c5', // 300
                    '50': '#f7f7f7',
                    '100': '#ededed',
                    '200': '#dfdfdf',
                    '300': '#c5c5c5',
                    '400': '#adadad',
                    '500': '#999999',
                    '600': '#888888',
                    '700': '#7b7b7b',
                    '800': '#676767',
                    '900': '#545454',
                    '950': '#363636',
                },
                red: {
                    '50': '#fff1f3',
                    '100': '#ffdfe5',
                    '200': '#ffc4ce',
                    '300': '#ff9cad',
                    '400': '#ff637e',
                    '500': '#ff3256',
                    '600': '#ef1339',
                    '700': '#d50c2f', //
                    '800': '#a60e28',
                    '900': '#891328',
                    '950': '#4b0410',
                },

            },
        },
    },
    plugins: [forms, typography],
    safelist: [{
        // https://stackoverflow.com/questions/71647859/tailwind-css-certain-custom-colors-are-not-working
        pattern: /(bg|text|border)-(cerulean|porsche|silver)/
    }]
};
