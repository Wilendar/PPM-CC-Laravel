@extends('layouts.test')

@section('title', 'Dropdown Debug - Alpine x-teleport test')
@section('breadcrumb', 'Dropdown Debug')

@section('content')
<div class="p-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Alpine.js x-teleport Debug Test</h1>

        <!-- Test 1: Sprawdzenie czy x-teleport działa -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test 1: x-teleport Basic Test</h2>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="px-4 py-2 bg-blue-600 text-white rounded">
                    Toggle Test Dropdown
                </button>

                <!-- Dropdown z x-teleport -->
                <div x-show="open"
                     x-teleport="body"
                     class="fixed top-10 right-10 w-64 p-4 bg-red-500 text-white rounded-lg shadow-2xl z-[9999]"
                     style="z-index: 9999 !important;">
                    <p><strong>x-teleport Test</strong></p>
                    <p>Jeśli to widzisz w prawym górnym rogu - x-teleport działa!</p>
                    <p>Sprawdź w DevTools czy ten div jest w &lt;body&gt;</p>
                </div>
            </div>
        </div>

        <!-- Test 2: Porównanie bez x-teleport -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test 2: Bez x-teleport (normalny dropdown)</h2>
            <div x-data="{ open2: false }" class="relative">
                <button @click="open2 = !open2" class="px-4 py-2 bg-green-600 text-white rounded">
                    Toggle Normal Dropdown
                </button>

                <!-- Dropdown bez x-teleport -->
                <div x-show="open2"
                     class="absolute top-full mt-2 left-0 w-64 p-4 bg-green-700 text-white rounded-lg shadow-2xl z-[9999]"
                     style="z-index: 9999 !important;">
                    <p><strong>Normal Dropdown</strong></p>
                    <p>Ten dropdown może być ukryty przez stacking context</p>
                </div>
            </div>
        </div>

        <!-- Test 3: Konkurencyjne elementy z wysokim z-index -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test 3: Konkurencyjne elementy</h2>

            <!-- Element z wysokim z-index symulujący problem -->
            <div class="relative" style="z-index: 100;">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-8 rounded-xl text-white shadow-xl">
                    <h3 class="text-lg font-bold">Element z z-index: 100</h3>
                    <p>Ten element może blokować dropdown</p>
                </div>
            </div>

            <!-- Element z transform (tworzy stacking context) -->
            <div class="transform rotate-1 mt-4" style="z-index: 50;">
                <div class="bg-gradient-to-r from-blue-500 to-teal-500 p-8 rounded-xl text-white shadow-xl">
                    <h3 class="text-lg font-bold">Element z transform rotate (stacking context)</h3>
                    <p>CSS transform tworzy nowy stacking context</p>
                </div>
            </div>
        </div>

        <!-- Test 4: Alpine.js version check -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Test 4: Alpine.js Info</h2>
            <div x-data="{ version: Alpine?.version || 'Nieznana' }">
                <p><strong>Alpine.js Version:</strong> <span x-text="version"></span></p>
                <p><strong>x-teleport Support:</strong>
                    <span x-text="typeof Alpine?.directive === 'function' ? 'Prawdopodobnie TAK' : 'Prawdopodobnie NIE'"></span>
                </p>
            </div>
        </div>

        <!-- Instrukcje debugowania -->
        <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Instrukcje debugowania:</h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li>Otwórz Developer Tools (F12)</li>
                <li>Kliknij "Toggle Test Dropdown" (niebieski przycisk)</li>
                <li>Sprawdź czy czerwony div pojawia się w prawym górnym rogu</li>
                <li>W Elements tab sprawdź czy czerwony div jest bezpośrednio w &lt;body&gt;</li>
                <li>Jeśli NIE - x-teleport nie działa</li>
                <li>Jeśli TAK - problem z pozycjonowaniem/CSS w oryginalnym dropdown</li>
            </ol>
        </div>
    </div>
</div>
@endsection