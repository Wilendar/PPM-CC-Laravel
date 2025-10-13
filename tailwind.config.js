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
      },
    },
  },
  plugins: [],
}

