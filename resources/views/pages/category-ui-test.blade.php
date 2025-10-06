{{-- UI Test Page for Category Fixes --}}
@extends('layouts.test')

@section('title', 'Test UI Naprawek - Kategorie')
@section('breadcrumb', 'Test UI')

@section('content')
<div class="p-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Test UI Naprawek - Kategorie</h1>

        {{-- Test Font Awesome Icons --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test Font Awesome Icons</h2>
            <div class="grid grid-cols-6 gap-4">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <i class="fas fa-folder text-3xl text-blue-600 mb-2"></i>
                    <p class="text-sm">fa-folder</p>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <i class="fas fa-edit text-3xl text-green-600 mb-2"></i>
                    <p class="text-sm">fa-edit</p>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                    <i class="fas fa-plus text-3xl text-purple-600 mb-2"></i>
                    <p class="text-sm">fa-plus</p>
                </div>
                <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                    <i class="fas fa-trash text-3xl text-red-600 mb-2"></i>
                    <p class="text-sm">fa-trash</p>
                </div>
                <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                    <i class="fas fa-eye text-3xl text-yellow-600 mb-2"></i>
                    <p class="text-sm">fa-eye</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                    <i class="fas fa-ellipsis-v text-3xl text-gray-600 mb-2"></i>
                    <p class="text-sm">fa-ellipsis-v</p>
                </div>
            </div>
        </div>

        {{-- Test Dropdown Z-Index --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test Dropdown Z-Index</h2>
            <div class="relative">

                {{-- Background Elements to Test Overlay --}}
                <div class="bg-gradient-to-r from-blue-100 to-indigo-100 p-8 rounded-2xl mb-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">Element w tle (powinien być pod dropdown)</h3>

                    {{-- Test Dropdown --}}
                    <div x-data="{ open: false }" class="relative inline-block">
                        <button @click="open = !open" @click.away="open = false"
                                class="inline-flex items-center px-4 py-2 bg-white border-2 border-gray-300 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-ellipsis-v mr-2"></i>
                            Kliknij aby otworzyć dropdown
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-90"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-90"
                             class="dropdown-fix origin-top-right right-0 mt-2 w-80 rounded-2xl shadow-2xl bg-white dark:bg-gray-800
                                    ring-1 ring-black ring-opacity-5 border-2 border-gray-100 dark:border-gray-700"
                             style="display: none; z-index: 999999 !important; position: fixed !important;">

                            <div class="p-6">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Test Dropdown Menu</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                        <i class="fas fa-edit text-blue-600"></i>
                                        <span>Opcja 1 - Edytuj</span>
                                    </div>
                                    <div class="flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                        <i class="fas fa-plus text-green-600"></i>
                                        <span>Opcja 2 - Dodaj</span>
                                    </div>
                                    <div class="flex items-center space-x-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                        <i class="fas fa-trash text-red-600"></i>
                                        <span>Opcja 3 - Usuń</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 text-sm text-gray-500">
                                    Z-index: 99999 - Ten dropdown powinien być na wierzchu!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Overlapping Elements --}}
                <div class="bg-gradient-to-r from-purple-100 to-pink-100 p-8 rounded-2xl mb-4">
                    <h3 class="text-lg font-semibold text-purple-800">Element nakładający się</h3>
                    <p class="text-purple-600">Ten element testuje czy dropdown pojawia się nad nim.</p>
                </div>

                <div class="bg-gradient-to-r from-green-100 to-emerald-100 p-8 rounded-2xl">
                    <h3 class="text-lg font-semibold text-green-800">Kolejny element</h3>
                    <p class="text-green-600">I jeszcze jeden element testowy.</p>
                </div>
            </div>
        </div>

        {{-- Test Results --}}
        <div class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 p-6 rounded-2xl">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Test Results
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span>Jeśli widzisz ikony powyżej - Font Awesome działa ✓</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span>Jeśli dropdown pojawia się nad innymi elementami - Z-index naprawiony ✓</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span>Jeśli animacje działają płynnie - CSS transitions OK ✓</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush