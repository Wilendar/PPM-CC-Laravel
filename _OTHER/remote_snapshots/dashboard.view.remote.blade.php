@extends('layouts.app')

@section('title', 'PPM Dashboard - Frontend Stack Test')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ testCounter: 0 }">
    
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                PPM Dashboard
            </h1>
            <p class="text-gray-600 dark:text-gray-300">
                Frontend Stack Test - Laravel 12.x + Livewire 3.x + TailwindCSS + Alpine.js
            </p>
        </div>
        
        <button @click="$store.darkMode.toggle()" 
                class="btn btn-secondary flex items-center space-x-2">
            <span x-text="$store.darkMode.on ? 'Tryb jasny' : 'Tryb ciemny'"></span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">TailwindCSS</h3>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">? Dzia³a</div>
            <p class="text-gray-600 dark:text-gray-300">Responsive design z dark mode support.</p>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Alpine.js</h3>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2" x-text="Alpine ? '? Dzia³a' : '? Error'">? Dzia³a</div>
            <p class="text-gray-600 dark:text-gray-300">Interaktywne komponenty.</p>
            
            <div class="mt-4">
                <button @click="testCounter++" 
                        class="btn btn-primary text-sm">
                    Klikniêcia: <span x-text="testCounter">0</span>
                </button>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Vite Build</h3>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">? Dzia³a</div>
            <p class="text-gray-600 dark:text-gray-300">Assets zoptymalizowane.</p>
        </div>
    </div>

    <div class="card p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Demo Notyfikacji</h2>
        
        <div class="space-y-4">
            <button @click="$store.notifications.success('Operacja wykonana pomyœlnie!')" 
                    class="btn btn-primary">
                Success Notification
            </button>
            <button @click="$store.notifications.error('Wyst¹pi³ b³¹d.')" 
                    class="btn bg-red-600 text-white hover:bg-red-700">
                Error Notification  
            </button>
        </div>
    </div>

    <div class="card p-6 bg-primary-50 dark:bg-primary-900/20">
        <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100">Frontend Stack Gotowy!</h3>
        <p class="text-primary-700 dark:text-primary-200">
            Laravel 12.x + Livewire 3.x + TailwindCSS 4.0 + Alpine.js 3.x
        </p>
    </div>
</div>
@endsection
