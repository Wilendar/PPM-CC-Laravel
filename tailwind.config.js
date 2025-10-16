/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Http/Livewire/**/*.php",
  ],
  safelist: [
    // Grid layout classes for Alpine.js :class bindings (admin sidebar)
    'lg:grid',
    'lg:grid-cols-[16rem_1fr]',
    'lg:grid-cols-[4rem_1fr]',
    'lg:static',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        'mpp-orange': '#e0ac7e',
        'mpp-orange-dark': '#d1975a',
        'dark-primary': '#ffffff',
        'dark-secondary': '#f3f4f6',
        // Brand color palette (MPP Orange) - full Tailwind scale
        'brand': {
          50: '#fdf6f0',
          100: '#fae8d6',
          200: '#f6d1ad',
          300: '#f0b583',
          400: '#ea9e64',
          500: '#e0ac7e', // Main MPP Orange
          600: '#d1975a',
          700: '#b87d43',
          800: '#9f6836',
          900: '#875628',
        },
      },
    },
  },
  plugins: [],
}

