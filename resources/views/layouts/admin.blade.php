<!DOCTYPE html>
<html lang="pl" x-data="{ 
    darkMode: localStorage.getItem('darkMode') !== 'false',
    userMenuOpen: false,
    quick: false,
    q: '',
    init() {
        // Set dark mode as default for admin panel
        if (localStorage.getItem('darkMode') === null) {
            this.darkMode = true;
            localStorage.setItem('darkMode', 'true');
        }
        this.$watch('darkMode', value => localStorage.setItem('darkMode', value))
    }
}" :class="{ 'dark': darkMode }" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - PPM Management')</title>
    
    <!-- Styles -->
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Complete Tailwind CSS for Admin Panel --}}
    <style>
        /* Layout & Display */
        .flex { display: flex; }
        .grid { display: grid; }
        .hidden { display: none; }
        .block { display: block; }
        .inline-flex { display: inline-flex; }
        .inline-block { display: inline-block; }
        
        /* Flexbox */
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .flex-col { flex-direction: column; }
        .flex-1 { flex: 1 1 0%; }
        .flex-shrink-0 { flex-shrink: 0; }
        
        /* Grid */
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .gap-4 { gap: 1rem; }
        .gap-6 { gap: 1.5rem; }
        .gap-8 { gap: 2rem; }
        
        /* Spacing */
        .space-x-2 > * + * { margin-left: 0.5rem; }
        .space-x-3 > * + * { margin-left: 0.75rem; }
        .space-x-4 > * + * { margin-left: 1rem; }
        .space-x-6 > * + * { margin-left: 1.5rem; }
        .space-x-8 > * + * { margin-left: 2rem; }
        .space-y-2 > * + * { margin-top: 0.5rem; }
        .space-y-3 > * + * { margin-top: 0.75rem; }
        .space-y-4 > * + * { margin-top: 1rem; }
        .space-y-6 > * + * { margin-top: 1.5rem; }
        
        /* Padding */
        .p-2 { padding: 0.5rem; }
        .p-3 { padding: 0.75rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .p-8 { padding: 2rem; }
        .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .px-8 { padding-left: 2rem; padding-right: 2rem; }
        .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .pt-1 { padding-top: 0.25rem; }
        .pb-4 { padding-bottom: 1rem; }
        .pl-3 { padding-left: 0.75rem; }
        
        /* Margin */
        .m-0 { margin: 0; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-6 { margin-top: 1.5rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .ml-2 { margin-left: 0.5rem; }
        .ml-3 { margin-left: 0.75rem; }
        .ml-4 { margin-left: 1rem; }
        .mr-1 { margin-right: 0.25rem; }
        .mr-2 { margin-right: 0.5rem; }
        .mr-3 { margin-right: 0.75rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .-mt-1 { margin-top: -0.25rem; }
        
        /* Width & Height */
        .w-3 { width: 0.75rem; }
        .w-4 { width: 1rem; }
        .w-5 { width: 1.25rem; }
        .w-6 { width: 1.5rem; }
        .w-8 { width: 2rem; }
        .w-10 { width: 2.5rem; }
        .w-12 { width: 3rem; }
        .w-16 { width: 4rem; }
        .w-20 { width: 5rem; }
        .w-32 { width: 8rem; }
        .w-48 { width: 12rem; }
        .w-64 { width: 16rem; }
        .w-full { width: 100%; }
        .h-3 { height: 0.75rem; }
        .h-4 { height: 1rem; }
        .h-5 { height: 1.25rem; }
        .h-6 { height: 1.5rem; }
        .h-8 { height: 2rem; }
        .h-10 { height: 2.5rem; }
        .h-12 { height: 3rem; }
        .h-16 { height: 4rem; }
        .h-20 { height: 5rem; }
        .h-32 { height: 8rem; }
        .h-48 { height: 12rem; }
        .h-full { height: 100%; }
        .min-h-screen { min-height: 100vh; }
        .min-w-0 { min-width: 0; }
        .max-w-2xl { max-width: 42rem; }
        .max-w-4xl { max-width: 56rem; }
        .max-w-7xl { max-width: 80rem; }
        
        /* Position */
        .relative { position: relative; }
        .absolute { position: absolute; }
        .fixed { position: fixed; }
        .static { position: static; }
        .sticky { position: sticky; }
        .inset-0 { inset: 0; }
        .top-0 { top: 0; }
        .top-1 { top: 0.25rem; }
        .top-4 { top: 1rem; }
        .right-0 { right: 0; }
        .right-4 { right: 1rem; }
        .bottom-0 { bottom: 0; }
        .left-0 { left: 0; }
        .-top-0\\.5 { top: -0.125rem; }
        .-right-0\\.5 { right: -0.125rem; }
        
        /* Z-Index */
        .z-10 { z-index: 10; }
        .z-20 { z-index: 20; }
        .z-30 { z-index: 30; }
        .z-40 { z-index: 40; }
        .z-50 { z-index: 50; }
        
        /* Typography */
        .text-xs { font-size: 0.75rem; line-height: 1rem; }
        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .text-base { font-size: 1rem; line-height: 1.5rem; }
        .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .leading-4 { line-height: 1rem; }
        .leading-5 { line-height: 1.25rem; }
        .leading-6 { line-height: 1.5rem; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .uppercase { text-transform: uppercase; }
        .capitalize { text-transform: capitalize; }
        .tracking-tight { letter-spacing: -0.025em; }
        .tracking-wide { letter-spacing: 0.025em; }
        
        /* Colors */
        .text-white { color: rgb(255 255 255); }
        .text-gray-50 { color: rgb(249 250 251); }
        .text-gray-100 { color: rgb(243 244 246); }
        .text-gray-200 { color: rgb(229 231 235); }
        .text-gray-300 { color: rgb(209 213 219); }
        .text-gray-400 { color: rgb(156 163 175); }
        .text-gray-500 { color: rgb(107 114 128); }
        .text-gray-600 { color: rgb(75 85 99); }
        .text-gray-700 { color: rgb(55 65 81); }
        .text-gray-800 { color: rgb(31 41 55); }
        .text-gray-900 { color: rgb(17 24 39); }
        .text-red-100 { color: rgb(254 226 226); }
        .text-red-400 { color: rgb(248 113 113); }
        .text-red-600 { color: rgb(220 38 38); }
        .text-red-800 { color: rgb(153 27 27); }
        .text-green-400 { color: rgb(74 222 128); }
        .text-green-600 { color: rgb(22 163 74); }
        .text-green-800 { color: rgb(22 101 52); }
        .text-blue-400 { color: rgb(96 165 250); }
        .text-blue-500 { color: rgb(59 130 246); }
        .text-blue-600 { color: rgb(37 99 235); }
        .text-yellow-400 { color: rgb(250 204 21); }
        .text-orange-500 { color: rgb(249 115 22); }
        
        /* Backgrounds */
        .bg-transparent { background-color: transparent; }
        .bg-white { background-color: rgb(255 255 255); }
        .bg-gray-50 { background-color: rgb(249 250 251); }
        .bg-gray-100 { background-color: rgb(243 244 246); }
        .bg-gray-200 { background-color: rgb(229 231 235); }
        .bg-gray-300 { background-color: rgb(209 213 219); }
        .bg-gray-400 { background-color: rgb(156 163 175); }
        .bg-gray-500 { background-color: rgb(107 114 128); }
        .bg-gray-600 { background-color: rgb(75 85 99); }
        .bg-gray-700 { background-color: rgb(55 65 81); }
        .bg-gray-800 { background-color: rgb(31 41 55); }
        .bg-gray-900 { background-color: rgb(17 24 39); }
        .bg-red-50 { background-color: rgb(254 242 242); }
        .bg-red-400 { background-color: rgb(248 113 113); }
        .bg-red-600 { background-color: rgb(220 38 38); }
        .bg-green-50 { background-color: rgb(240 253 244); }
        .bg-green-400 { background-color: rgb(74 222 128); }
        .bg-green-500 { background-color: rgb(34 197 94); }
        .bg-blue-50 { background-color: rgb(239 246 255); }
        .bg-blue-400 { background-color: rgb(96 165 250); }
        .bg-blue-500 { background-color: rgb(59 130 246); }
        .bg-blue-600 { background-color: rgb(37 99 235); }
        .bg-indigo-500 { background-color: rgb(99 102 241); }
        .bg-purple-500 { background-color: rgb(168 85 247); }
        .bg-yellow-400 { background-color: rgb(250 204 21); }
        .bg-orange-500 { background-color: rgb(249 115 22); }
        
        /* Background transparency */
        .bg-white\/10 { background-color: rgb(255 255 255 / 0.1); }
        .bg-gray-800\/30 { background-color: rgb(31 41 55 / 0.3); }
        .bg-gray-800\/50 { background-color: rgb(31 41 55 / 0.5); }
        .bg-gray-700\/50 { background-color: rgb(55 65 81 / 0.5); }
        .bg-black { background-color: rgb(0 0 0); }
        .bg-opacity-25 { --tw-bg-opacity: 0.25; }
        
        /* Borders */
        .border { border-width: 1px; }
        .border-t { border-top-width: 1px; }
        .border-b { border-bottom-width: 1px; }
        .border-l-4 { border-left-width: 4px; }
        .border-b-2 { border-bottom-width: 2px; }
        .border-transparent { border-color: transparent; }
        .border-gray-100 { border-color: rgb(243 244 246); }
        .border-gray-200 { border-color: rgb(229 231 235); }
        .border-gray-300 { border-color: rgb(209 213 219); }
        .border-gray-600 { border-color: rgb(75 85 99); }
        .border-gray-700 { border-color: rgb(55 65 81); }
        .border-red-400 { border-color: rgb(248 113 113); }
        .border-green-400 { border-color: rgb(74 222 128); }
        
        /* Border Radius */
        .rounded { border-radius: 0.25rem; }
        .rounded-md { border-radius: 0.375rem; }
        .rounded-lg { border-radius: 0.5rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .rounded-full { border-radius: 9999px; }
        
        /* Shadows */
        .shadow { box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1); }
        .shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
        .shadow-md { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
        .shadow-xl { box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
        .shadow-2xl { box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); }
        
        /* Ring */
        .ring-1 { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
        .ring-black { --tw-ring-color: rgb(0 0 0); }
        .ring-opacity-5 { --tw-ring-opacity: 0.05; }
        
        /* Backdrop */
        .backdrop-blur-md { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        
        /* Transitions */
        .transition { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .transition-opacity { transition-property: opacity; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .transition-transform { transition-property: transform; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .duration-75 { transition-duration: 75ms; }
        .duration-150 { transition-duration: 150ms; }
        .duration-200 { transition-duration: 200ms; }
        .duration-300 { transition-duration: 300ms; }
        .duration-500 { transition-duration: 500ms; }
        .duration-1000 { transition-duration: 1000ms; }
        .ease-in { transition-timing-function: cubic-bezier(0.4, 0, 1, 1); }
        .ease-out { transition-timing-function: cubic-bezier(0, 0, 0.2, 1); }
        .ease-in-out { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Transforms */
        .scale-95 { transform: scale(0.95); }
        .scale-100 { transform: scale(1); }
        .scale-105 { transform: scale(1.05); }
        .transform { transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y)); }
        
        /* Focus & Hover States */
        .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
        .focus\:ring-2:focus { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
        .focus\:ring-4:focus { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
        .focus\:ring-inset:focus { --tw-ring-inset: inset; }
        .focus\:ring-offset-2:focus { --tw-ring-offset-width: 2px; }
        
        .hover\:bg-gray-100:hover { background-color: rgb(243 244 246); }
        .hover\:bg-gray-200:hover { background-color: rgb(229 231 235); }
        .hover\:bg-gray-700:hover { background-color: rgb(55 65 81); }
        .hover\:text-gray-200:hover { color: rgb(229 231 235); }
        .hover\:text-gray-500:hover { color: rgb(107 114 128); }
        .hover\:text-gray-700:hover { color: rgb(55 65 81); }
        .hover\:text-gray-900:hover { color: rgb(17 24 39); }
        .hover\:text-white:hover { color: rgb(255 255 255); }
        .hover\:text-red-600:hover { color: rgb(220 38 38); }
        .hover\:text-green-600:hover { color: rgb(22 163 74); }
        .hover\:shadow-lg:hover { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
        .hover\:scale-105:hover { transform: scale(1.05); }
        .hover\:border-gray-300:hover { border-color: rgb(209 213 219); }
        
        /* Dark mode styles */
        .dark .dark\:bg-gray-50 { background-color: rgb(249 250 251); }
        .dark .dark\:bg-gray-100 { background-color: rgb(243 244 246); }
        .dark .dark\:bg-gray-200 { background-color: rgb(229 231 235); }
        .dark .dark\:bg-gray-600 { background-color: rgb(75 85 99); }
        .dark .dark\:bg-gray-700 { background-color: rgb(55 65 81); }
        .dark .dark\:bg-gray-800 { background-color: rgb(31 41 55); }
        .dark .dark\:bg-gray-900 { background-color: rgb(17 24 39); }
        .dark .dark\:text-white { color: rgb(255 255 255); }
        .dark .dark\:text-gray-100 { color: rgb(243 244 246); }
        .dark .dark\:text-gray-200 { color: rgb(229 231 235); }
        .dark .dark\:text-gray-300 { color: rgb(209 213 219); }
        .dark .dark\:text-gray-400 { color: rgb(156 163 175); }
        .dark .dark\:text-gray-500 { color: rgb(107 114 128); }
        .dark .dark\:border-gray-600 { border-color: rgb(75 85 99); }
        .dark .dark\:border-gray-700 { border-color: rgb(55 65 81); }
        .dark .dark\:hover\:bg-gray-700:hover { background-color: rgb(55 65 81); }
        .dark .dark\:hover\:text-gray-200:hover { color: rgb(229 231 235); }
        .dark .dark\:hover\:text-white:hover { color: rgb(255 255 255); }
        
        /* Responsive utilities */
        @media (min-width: 640px) {
            .sm\:px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
            .sm\:text-left { text-align: left; }
            .sm\:block { display: block; }
        }
        
        @media (min-width: 768px) {
            .md\:block { display: block; }
            .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        
        @media (min-width: 1024px) {
            .lg\:px-8 { padding-left: 2rem; padding-right: 2rem; }
            .lg\:block { display: block; }
            .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        
        @media (min-width: 1280px) {
            .xl\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        
        /* Custom utility classes */
        .antialiased { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .overflow-hidden { overflow: hidden; }
        .overflow-y-auto { overflow-y: auto; }
        .cursor-pointer { cursor: pointer; }
    </style>
    
    <!-- Custom admin styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* PPM Brand Colors - MPP TRADE Theme */
        :root {
            --ppm-primary: #2563eb;
            --ppm-primary-dark: #1d4ed8;
            --ppm-secondary: #059669;
            --ppm-accent: #e0ac7e;           /* MPP TRADE Orange */
            --ppm-accent-dark: #d1975a;     /* MPP TRADE Orange Dark */
        }
        
        /* MPP TRADE Background Pattern - like homepage */
        .admin-bg {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            min-height: 100vh;
        }
        
        .bg-grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        /* Custom scrollbar dla admin panel */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* Animate dashboard widgets */
        .widget-enter {
            animation: slideInUp 0.3s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Pulse animation for live data */
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }
    </style>
    
    @livewireStyles
    
    <!-- Additional head content -->
    @stack('head')
</head>
<body class="admin-bg bg-grid-pattern text-gray-100 antialiased bg-gray-900 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-gray-800/50 backdrop-blur-md shadow-lg border-b border-gray-700/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left side - Logo and main nav -->
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                <span class="text-white font-bold text-sm">PPM</span>
                            </div>
                            <span class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">
                                Admin Panel
                            </span>
                        </a>
                    </div>
                    
                    <!-- Main navigation -->
                    <div class="hidden md:ml-8 md:flex md:space-x-8">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="border-b-2 {{ request()->routeIs('admin.dashboard') ? 'text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium" 
                           style="{{ request()->routeIs('admin.dashboard') ? 'border-color: #e0ac7e;' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 7 4-4 4 4"></path>
                            </svg>
                            Dashboard
                        </a>
                        
                        <a href="{{ route('admin.users') }}" 
                           class="border-b-2 {{ request()->routeIs('admin.users*') ? 'text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium"
                           style="{{ request()->routeIs('admin.users*') ? 'border-color: #e0ac7e;' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Użytkownicy
                        </a>
                        
                        <a href="{{ route('admin.integrations') }}" 
                           class="border-b-2 {{ request()->routeIs('admin.integrations*') ? 'text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium"
                           style="{{ request()->routeIs('admin.integrations*') ? 'border-color: #e0ac7e;' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                            Integracje
                        </a>
                        
                        <a href="{{ route('admin.settings') }}" 
                           class="border-b-2 {{ request()->routeIs('admin.settings*') ? 'text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 text-sm font-medium"
                           style="{{ request()->routeIs('admin.settings*') ? 'border-color: #e0ac7e;' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Ustawienia
                        </a>
                    </div>
                </div>
                
                <!-- Right side - Search, Quick actions, User menu -->
                <div class="flex items-center space-x-4">
                    <!-- Global search -->
                    <div class="hidden lg:block">
                        <input type="search" x-model="q" placeholder="Szukaj..."
                               class="w-64 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-1.5"
                               style="--tw-ring-color: #e0ac7e; --tw-border-opacity: 1;" 
                               onFocus="this.style.borderColor='#e0ac7e'; this.style.boxShadow='0 0 0 3px rgba(224, 172, 126, 0.1)'"
                               onBlur="this.style.borderColor=''; this.style.boxShadow=''"
                               @keydown.enter.window="window.location.href='{{ url('/admin') }}?q='+encodeURIComponent(q)" />
                    </div>

                    <!-- Quick actions toggle -->
                    <button @click="quick = true" title="Szybkie skróty"
                            class="p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset"
                            style="--tw-ring-color: #e0ac7e;"
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" 
                            class="p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset"
                            style="--tw-ring-color: #e0ac7e;"
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    
                    <!-- Notifications (placeholder) -->
                    <button class="relative p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset"
                            style="--tw-ring-color: #e0ac7e;"
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a50.002 50.002 0 00-7-7A50.002 50.002 0 003.5 13.5L0 17h5m10 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <!-- Notification badge -->
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                            3
                        </span>
                    </button>
                    
                    <!-- User menu dropdown -->
                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="flex items-center space-x-3 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                style="--tw-ring-color: #e0ac7e;"
                            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'A' }}
                                </span>
                            </div>
                            <div class="hidden md:block text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ auth()->check() ? auth()->user()->name : 'Admin' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ auth()->check() ? auth()->user()->getRoleNames()->first() : 'Administrator' }}
                                </div>
                            </div>
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div x-show="userMenuOpen" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.away="userMenuOpen = false"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 focus:outline-none" style="z-index: 99999 !important;">
                            
                            <a href="{{ route('profile.show') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Profil
                                </div>
                            </a>
                            
                            <a href="{{ route('dashboard') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                    </svg>
                                    Dashboard użytkownika
                                </div>
                            </a>
                            
                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Wyloguj się
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</nav>

    <!-- Quick Access Off-canvas -->
    <div x-data="{ open:false }" x-init="$watch('$root.quick', v => open=v)" x-show="open" class="fixed inset-0 z-40" aria-modal="true">
        <div class="absolute inset-0 bg-black bg-opacity-40" @click="$root.quick=false; open=false"></div>
        <aside class="absolute left-0 top-0 h-full w-72 bg-white dark:bg-gray-800 shadow-xl p-4 border-r border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Szybkie skróty</h3>
                <button @click="$root.quick=false; open=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav class="space-y-2 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.users') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Użytkownicy</span>
                </a>
                <a href="{{ route('admin.shops') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Sklepy PrestaShop</span>
                </a>
                <a href="{{ route('admin.integrations') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Integracje ERP</span>
                </a>
                <a href="{{ route('admin.system-settings.index') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Ustawienia</span>
                </a>
                <a href="{{ route('admin.backup.index') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Backup</span>
                </a>
                <a href="{{ route('admin.maintenance.index') }}" class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span>Maintenance</span>
                </a>
            </nav>
        </aside>
    </div>
    
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4 m-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400 dark:text-green-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-4 m-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400 dark:text-red-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Main Content -->
    <main class="flex-1 bg-gray-900 min-h-screen">
        {{ $slot }}
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800/50 backdrop-blur-md border-t border-gray-700/30 mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-400">
                    © {{ date('Y') }} PPM Management System. Wszystkie prawa zastrzeżone.
                </div>
                <div class="text-sm text-gray-400">
                    Wersja: {{ config('app.version', '1.0.0') }} | 
                    Środowisko: {{ app()->environment() }}
                </div>
            </div>
        </div>
    </footer>
    
    @livewireScripts
    
    <!-- Additional scripts -->
    @stack('scripts')
    
    <!-- Live reload for development -->
    @if(app()->environment('local'))
        <script>
            // Development live reload (jeśli używane)
            if (typeof window.livewire !== 'undefined') {
                console.log('Livewire loaded successfully');
            }
        </script>
    @endif
</body>
</html>
