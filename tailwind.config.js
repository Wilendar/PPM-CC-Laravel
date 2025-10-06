/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Http/Livewire/**/*.php",
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

