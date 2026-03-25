import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.jsx',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Tenant App (Barangay Dashboard) - Purple/Indigo theme
                tenant: {
                    primary: '#635bff',
                    'primary-light': '#ecf0ff',
                    'sidebar': '#121621',
                    'accent': '#0ea5e9',
                    'success': '#10b981',
                    'warning': '#f59e0b',
                    'danger': '#ef4444',
                    'bg': '#f8fafc',
                },
                // Central App (City Admin) - Deep Blue theme
                central: {
                    primary: '#1e40af',
                    'primary-light': '#dbeafe',
                    'sidebar': '#0f172a',
                    'accent': '#06b6d4',
                    'success': '#059669',
                    'warning': '#d97706',
                    'danger': '#dc2626',
                    'bg': '#f0f9ff',
                },
                // Legacy devias colors (for backward compatibility)
                devias: {
                    primary: '#635bff',
                    'primary-light': '#ecf0ff',
                    'sidebar': '#121621',
                },
            },
            borderRadius: {
                devias: '8px',
            },
        },
    },
    plugins: [],
};
