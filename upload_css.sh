#!/bin/bash

# Upload CSS content
cat > resources/css/app.css << 'EOFCSS'
@import 'tailwindcss';

@plugin '@tailwindcss/forms';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
    
    --color-gray-50: #f9fafb;
    --color-gray-100: #f3f4f6;
    --color-gray-200: #e5e7eb;
    --color-gray-300: #d1d5db;
    --color-gray-400: #9ca3af;
    --color-gray-500: #6b7280;
    --color-gray-600: #4b5563;
    --color-gray-700: #374151;
    --color-gray-800: #1f2937;
    --color-gray-900: #111827;
    --color-gray-950: #030712;
    
    --color-primary-50: #eff6ff;
    --color-primary-100: #dbeafe;
    --color-primary-200: #bfdbfe;
    --color-primary-300: #93c5fd;
    --color-primary-400: #60a5fa;
    --color-primary-500: #3b82f6;
    --color-primary-600: #2563eb;
    --color-primary-700: #1d4ed8;
    --color-primary-800: #1e40af;
    --color-primary-900: #1e3a8a;
    --color-primary-950: #172554;
    
    --color-success-50: #f0fdf4;
    --color-success-500: #22c55e;
    --color-success-600: #16a34a;
    --color-success-700: #15803d;
    
    --color-warning-50: #fffbeb;
    --color-warning-500: #f59e0b;
    --color-warning-600: #d97706;
    --color-warning-700: #b45309;
    
    --color-danger-50: #fef2f2;
    --color-danger-500: #ef4444;
    --color-danger-600: #dc2626;
    --color-danger-700: #b91c1c;
}

.btn {
    @apply inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-primary {
    @apply bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 shadow-sm hover:shadow-md;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500 shadow-sm hover:shadow-md dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600;
}

.card {
    @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-md;
}

.form-input {
    @apply block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:border-primary-400;
}

.table {
    @apply w-full border-collapse bg-white dark:bg-gray-800;
}

.table th {
    @apply px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600;
}

.table td {
    @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-600;
}
EOFCSS