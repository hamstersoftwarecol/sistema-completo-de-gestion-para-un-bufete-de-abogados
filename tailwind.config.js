import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const shade = (n) => `rgb(var(--primary-${n}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
        './config/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Color primario configurable desde Configuración → Apariencia.
                primary: {
                    50: shade(50), 100: shade(100), 200: shade(200), 300: shade(300), 400: shade(400),
                    500: shade(500), 600: shade(600), 700: shade(700), 800: shade(800), 900: shade(900), 950: shade(950),
                },
            },
        },
    },

    plugins: [forms],
};
