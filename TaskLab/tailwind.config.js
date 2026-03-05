import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                tasklab: {
                    bg: '#020617', // main background (slate-950)
                    'bg-muted': '#0b1220', // cards / panels
                    text: '#e5f2ff',
                    muted: '#9ca3af',
                    primary: '#2563eb',
                    'primary-soft': '#1d4ed8',
                    accent: '#38bdf8',
                    danger: '#ef4444',
                    warning: '#f59e0b',
                    success: '#22c55e',
                },
            },
            borderRadius: {
                lg: '0.75rem',
                xl: '1rem',
            },
            boxShadow: {
                card: '0 10px 25px -15px rgba(15, 23, 42, 0.8)',
            },
        },
    },

    plugins: [forms],
};
