@extends('layouts.auth')

@section('title', 'Logowanie - PPM')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    {{-- Background Gradient matching welcome page --}}
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>
    
    {{-- Geometric Background Elements --}}
    <div class="absolute inset-0">
        <div class="absolute top-10 left-10 w-72 h-72 bg-gray-800/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-orange-300/10 rounded-full blur-3xl"></div>
    </div>
    
    {{-- Grid Pattern --}}
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    
    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full space-y-8">
            {{-- Header --}}
            <div class="text-center" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 200)">
                
                <img 
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-800 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png" 
                    alt="MPP TRADE Logo" 
                    style="width: 100%; max-width: 900px; height: 200px; object-fit: cover; margin: 0 auto 15px;"
                    onerror="this.style.display='none'"
                >
                
                <div 
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-800 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="text-2xl sm:text-3xl font-semibold mb-6 text-center mx-auto max-w-4xl"
                    style="color: #e0ac7e;"
                >
                    /// TWORZYMY PASJE /// DOSTARCZAMY EMOCJE ///
                </div>
                
                <h2 
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-800 delay-300"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="text-xl font-semibold text-white mb-2"
                >
                    Zaloguj się do swojego konta
                </h2>
                
                <p class="text-gray-400 text-sm mb-2">
                    Prestashop Product Manager - System zarządzania produktami
                </p>
                
                {{-- Back to home link --}}
                <a 
                    href="{{ route('home') }}"
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-800 delay-400"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="inline-flex items-center text-gray-400 hover:text-white text-sm transition-colors duration-200 mb-6"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Powrót na stronę główną
                </a>
            </div>

            {{-- Flash Messages --}}
            @if(session('error'))
                <div 
                    x-data="{ show: true }" 
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-red-900/20 backdrop-blur-md border border-red-400/30 rounded-xl p-4 mb-6"
                >
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-red-200">{{ session('error') }}</p>
                        </div>
                        <button @click="show = false" class="ml-auto text-red-300 hover:text-red-200">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Login Form --}}
            <div 
                x-data="{ loaded: false }" 
                x-init="setTimeout(() => loaded = true, 600)"
                x-show="loaded"
                x-transition:enter="transition ease-out duration-800"
                x-transition:enter-start="opacity-0 translate-y-8"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="bg-gray-800/30 backdrop-blur-md rounded-2xl p-8 border border-gray-600/30 shadow-2xl"
                style="width: 384px; max-width: calc(100vw - 2rem); margin: 0 auto;"
            >
                <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
                    @csrf
                    
                    {{-- Email Field --}}
                    <div class="flex flex-col items-center">
                        <label for="email" class="block text-sm font-medium text-white mb-2">Adres email</label>
                        <input 
                            id="email"
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required 
                            value="{{ old('email') }}"
                            class="w-80 max-w-full mx-auto px-4 py-3 bg-gray-700/30 backdrop-blur-sm border border-gray-500/50 rounded-lg 
                                   @error('email') border-red-400/50 @else border-gray-500/50 @enderror
                                   placeholder-gray-400 text-white focus:outline-none focus:ring-2 
                                   focus:ring-orange-500/50 focus:border-orange-500/50 transition-all duration-200
                                   hover:bg-gray-700/50 hover:border-gray-400/60"
                            style="background: rgba(55, 65, 81, 0.3);" 
                            placeholder="Wprowadź adres email"
                            @error('email') aria-invalid="true" @enderror
                        >
                        @error('email')
                            <div class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Password Field --}}
                    <div x-data="{ showPassword: false }" class="flex flex-col items-center">
                        <label for="password" class="block text-sm font-medium text-white mb-2">Hasło</label>
                        <div class="relative">
                            <input 
                                id="password"
                                name="password" 
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password" 
                                required 
                                class="w-80 max-w-full mx-auto px-4 py-3 pr-12 bg-gray-700/30 backdrop-blur-sm border border-gray-500/50 rounded-lg 
                                       @error('password') border-red-400/50 @else border-gray-500/50 @enderror
                                       placeholder-gray-400 text-white focus:outline-none focus:ring-2 
                                       focus:ring-orange-500/50 focus:border-orange-500/50 transition-all duration-200
                                       hover:bg-gray-700/50 hover:border-gray-400/60"
                                style="background: rgba(55, 65, 81, 0.3);" 
                                placeholder="Wprowadź hasło"
                                @error('password') aria-invalid="true" @enderror
                            >
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition-colors duration-200"
                                :title="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                            >
                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Remember Me & Forgot Password --}}
                    <div class="flex flex-col items-center space-y-3 w-80 mx-auto">
                        <div class="flex items-center">
                            <input 
                                id="remember" 
                                name="remember" 
                                type="checkbox" 
                                class="h-4 w-4 bg-gray-700/30 border-gray-500/50 rounded focus:ring-orange-500/50 focus:ring-2"
                                style="accent-color: #e0ac7e;"
                            >
                            <label for="remember" class="ml-3 block text-sm text-white hover:text-gray-200 transition-colors duration-200">
                                Zapamiętaj mnie
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" 
                               class="font-medium text-gray-400 hover:text-white transition-colors duration-200">
                                Zapomniałeś hasła?
                            </a>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-center">
                        <button 
                            type="submit" 
                            x-data="{ loading: false }"
                            @click="loading = true"
                            :disabled="loading"
                            :class="loading ? 'opacity-75 cursor-not-allowed' : ''"
                            class="group relative w-80 max-w-full mx-auto flex justify-center items-center py-3 px-4 
                                   text-sm font-semibold rounded-xl text-white overflow-hidden
                                   focus:outline-none focus:ring-4 focus:ring-orange-400/50 
                                   transition-all duration-300 ease-out
                                   shadow-lg hover:shadow-2xl hover:shadow-orange-500/25
                                   transform hover:scale-105 hover:-translate-y-0.5
                                   active:scale-95 active:translate-y-0
                                   disabled:transform-none disabled:hover:scale-100 disabled:hover:translate-y-0
                                   before:absolute before:inset-0 before:bg-gradient-to-r 
                                   before:from-transparent before:via-white/20 before:to-transparent
                                   before:translate-x-[-100%] before:skew-x-12
                                   hover:before:translate-x-[100%] before:transition-transform before:duration-700"
                            style="background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c4824a 100%);
                                   box-shadow: inset 0 1px 0 rgba(255,255,255,0.2), inset 0 -1px 0 rgba(0,0,0,0.1);"
                        >
                            <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            
                            <div x-show="loading" class="flex items-center mr-2">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                            </div>
                            
                            <span x-text="loading ? 'Logowanie...' : 'Zaloguj się do PPM'"></span>
                        </button>
                    </div>

                    {{-- Additional Info --}}
                    <div class="text-center">
                        <p class="text-xs text-gray-400">
                            Bezpieczne logowanie • Enterprise System
                        </p>
                        <div class="flex items-center justify-center space-x-4 mt-3">
                            <div class="flex items-center text-gray-500 text-xs">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                SSL Secured
                            </div>
                            <div class="flex items-center text-gray-500 text-xs">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Enterprise Grade
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Custom Styles for Login --}}
<style>
    .bg-grid-pattern {
        background-image: 
            linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 50px 50px;
    }
    
    /* Enhanced backdrop blur support */
    .backdrop-blur-md {
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    
    /* Custom focus styles */
    input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 0 0 2px rgba(255, 255, 255, 0.5);
    }
    
    /* Enhanced Button Effects */
    button[type="submit"] {
        position: relative;
        overflow: hidden;
        background-size: 200% 200%;
        animation: gradientShift 3s ease infinite;
    }
    
    button[type="submit"]:hover {
        animation-duration: 1.5s;
        box-shadow: 
            0 15px 35px rgba(224, 172, 126, 0.4),
            0 5px 15px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    
    button[type="submit"]:active {
        box-shadow: 
            0 5px 15px rgba(224, 172, 126, 0.3),
            inset 0 3px 7px rgba(0, 0, 0, 0.2);
    }
    
    /* Gradient animation */
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    /* Ripple effect */
    button[type="submit"]::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    button[type="submit"]:active::after {
        width: 300px;
        height: 300px;
    }
    
    /* Checkbox custom styling */
    input[type="checkbox"]:checked {
        background-color: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    /* Loading state */
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Smooth transitions */
    * {
        scroll-behavior: smooth;
    }
    
    /* Form accessibility improvements */
    input:focus-visible {
        outline: 2px solid rgba(255, 255, 255, 0.8);
        outline-offset: 2px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .bg-grid-pattern {
            background-size: 30px 30px;
        }
    }
    
    /* Performance optimizations */
    input[type="email"], input[type="password"] {
        transform: translateZ(0);
        will-change: transform;
    }
</style>

{{-- Enhanced SEO and Accessibility --}}
@push('head')
    <meta name="description" content="Zaloguj się do PPM - Prestashop Product Manager. Bezpieczny dostęp do systemu zarządzania produktami enterprise dla MPP TRADE.">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#2563eb">
    
    {{-- Preload critical resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@endpush
@endsection