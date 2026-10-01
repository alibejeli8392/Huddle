import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: '#262626',
                muted: '#8e8e8e',
                line: '#dbdbdb',
                'line-soft': '#efefef',
                wash: '#fafafa',
                hover: '#f2f2f2',
                heart: '#ed4956',
            },
        },
    },

    plugins: [forms],
};
