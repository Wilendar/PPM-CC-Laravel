@extends('layouts.auth')

@section('title', 'PPM - Prestashop Product Manager')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    {{-- Background Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 dark:from-gray-900 dark:via-blue-900 dark:to-indigo-900"></div>
    
    {{-- Geometric Background Elements --}}
    <div class="absolute inset-0">
        <div class="absolute top-10 left-10 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-blue-300/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-indigo-300/5 rounded-full blur-2xl"></div>
    </div>
    
    {{-- Main Content --}}
    <div class="relative z-10 flex flex-col justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
            
            {{-- Logo/Brand Section --}}
            <div class="mb-8" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 200)">
                <div 
                    x-show="loaded" 
                    x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 scale-90 -translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-md rounded-2xl mb-6 border border-white/20"
                >
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M19 11H5m14-7H5a2 2 0 00-2 2v14c0 1.1.9 2 2 2h14a2 2 0 002-2V6a2 2 0 00-2-2zM9 11l2 2 4-4"/>
                    </svg>
                </div>
                
                <h1 
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-1000 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white mb-4"
                >
                    <span class="bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">PPM</span>
                </h1>
                
                <h2 
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-1000 delay-300"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-blue-100 mb-6"
                >
                    Prestashop Product Manager
                </h2>
            </div>
            
            {{-- Hero Description --}}
            <div 
                x-data="{ loaded: false }" 
                x-init="setTimeout(() => loaded = true, 800)"
                x-show="loaded"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 translate-y-6"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mb-12 space-y-6"
            >
                <p class="text-xl sm:text-2xl text-blue-100/90 max-w-3xl mx-auto leading-relaxed">
                    System zarządzania produktami klasy enterprise dla 
                    <span class="font-semibold text-white">MPP TRADE</span>
                </p>
                
                <div class="text-lg text-blue-200/80 max-w-2xl mx-auto space-y-2">
                    <p>Centralny hub do zarządzania produktami na wielu sklepach Prestashop jednocześnie</p>
                    <p class="text-base text-blue-300/70">Import • Export • Synchronizacja • Analityka</p>
                </div>
            </div>
            
            {{-- CTA Section --}}
            <div 
                x-data="{ loaded: false }" 
                x-init="setTimeout(() => loaded = true, 1600)"
                x-show="loaded"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 translate-y-8"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <a 
                    href="{{ route('login') }}" 
                    class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-full 
                           hover:bg-blue-50 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-white/50 
                           transition-all duration-300 shadow-2xl hover:shadow-3xl"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Zaloguj się do systemu
                </a>
                
                <p class="mt-6 text-blue-200/70 text-sm">
                    Bezpieczne logowanie przez Google Workspace lub Microsoft Entra ID
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Custom Styles --}}
<style>
    .backdrop-blur-md {
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    
    .shadow-3xl {
        box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
    }
    
    .hover\:scale-105:hover {
        transform: scale(1.05);
    }
    
    .focus\:ring-4:focus {
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.5);
    }
    
    .bg-gradient-to-r {
        background: linear-gradient(to right, var(--tw-gradient-stops));
    }
    
    .from-white {
        --tw-gradient-from: #fff;
        --tw-gradient-to: rgb(255 255 255 / 0);
        --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
    }
    
    .to-blue-100 {
        --tw-gradient-to: #dbeafe;
    }
    
    .bg-clip-text {
        background-clip: text;
        -webkit-background-clip: text;
    }
    
    .text-transparent {
        color: transparent;
    }
</style>
@endsection