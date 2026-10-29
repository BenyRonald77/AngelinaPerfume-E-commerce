import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Livewire/**/*.php',
        './app/View/**/*.php',
    ],

    theme: {
        extend: {
            // Palet toko (CLAUDE.md). Cocoa #551902 dan rose #DF7783 sengaja tidak dipakai.
            colors: {
                cream: '#FFF4F3',
                blush: '#F9B6BA',
                'dusty-pink': '#DE9DA9',
                ink: '#3B2A25',
                'ink-soft': '#5A4842',
            },
            fontFamily: {
                sans: ['"League Spartan"', ...defaultTheme.fontFamily.sans],
                logo: ['Pacifico', 'cursive'],
                display: ['Shrikhand', 'serif'],
            },
        },
    },

    plugins: [forms],
};
