import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // L'app n'a jamais eu de bouton pour basculer le thème sombre — le
    // mode sombre ne suivait donc que la préférence système du visiteur,
    // faisant apparaître du gris très foncé/noir chez certains sans aucun
    // contrôle possible. Passage en stratégie "class" (jamais activée,
    // aucun code n'ajoute la classe .dark) pour neutraliser ces variantes
    // partout et garder l'identité orange / blanc / vert imposée.
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Couleurs de la Côte d'Ivoire (orange / blanc / vert),
                // reprises de l'identité visuelle des sites institutionnels
                // ivoiriens (ex. fonctionpublique.gouv.ci).
                primary: {
                    50: '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#f97316',
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },
                secondary: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
            },
        },
    },

    plugins: [forms],
};
