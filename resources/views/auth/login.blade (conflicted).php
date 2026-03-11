@extends('layouts.auth')

@section('title', 'Logowanie - PPM')

@push('head')
    @vite(['resources/css/auth/landing-animations.css'])
    <meta name="description" content="Zaloguj sie do PPM - Prestashop Product Manager. Bezpieczny dostep do systemu zarzadzania produktami enterprise dla MPP TRADE.">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#1f2937">
@endpush

@section('content')
<div
    class="relative min-h-screen overflow-hidden landing-animate"
    x-data
    x-init="$nextTick(() => $el.classList.add('is-loaded'))"
>
    {{-- Background Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>

    {{-- Animated Orbs --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="landing-orb landing-orb--1"></div>
        <div class="landing-orb landing-orb--2"></div>
        <div class="landing-orb landing-orb--3"></div>
    </div>

    {{-- Particles --}}
    <div class="login-particles">
        <div class="login-particle"></div>
        <div class="login-particle"></div>
        <div class="login-particle"></div>
        <div class="login-particle"></div>
        <div class="login-particle"></div>
        <div class="login-particle"></div>
    </div>

    {{-- Grid Pattern --}}
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full space-y-8">
            {{-- Header --}}
            <div class="text-center">
                <div class="landing-logo">
                    <img
                        src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png"
                        alt="MPP TRADE Logo"
                        class="landing-logo-img"
                        onerror="this.style.display='none'"
                    >
                </div>

                {{-- Motto (per-word reveal) --}}
                <div class="text-2xl sm:text-3xl font-semibold mb-6 text-center mx-auto max-w-4xl brand-mantra">
                    @foreach(['///', 'TWORZYMY', 'PASJE', '///', 'DOSTARCZAMY', 'EMOCJE', '///'] as $i => $word)
                        <span class="landing-motto-word inline-block" style="--word-index: {{ $i }}">{{ $word }}&nbsp;</span>
                    @endforeach
                </div>

                <h2 class="text-xl font-semibold text-white mb-2 landing-subtitle">
                    Zaloguj sie do swojego konta
                </h2>

                <p class="text-gray-400 text-sm mb-2">
                    Prestashop Product Manager - System zarzadzania produktami
                </p>

                {{-- Back to home link --}}
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center text-gray-400 hover:text-white text-sm transition-colors duration-200 mb-6 landing-footer-text"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Powrot na strone glowna
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

            {{-- Login Form (VISIBLE IMMEDIATELY - no delay!) --}}
            <div class="login-glass-card">
                <form method="POST" action="{{ route('login.store') }}" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
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
                            class="login-input-glow w-80 max-w-full mx-auto px-4 py-3
                                   @error('email') border-red-400/50 @enderror
                                   placeholder-gray-400"
                            placeholder="Wprowadz adres email"
                            @error('email') aria-invalid="true" @enderror
                        >
                        @error('email')
                            <div class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password Field --}}
                    <div x-data="{ showPassword: false }" class="flex flex-col items-center">
                        <label for="password" class="block text-sm font-medium text-white mb-2">Haslo</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="login-input-glow w-80 max-w-full mx-auto px-4 py-3 pr-12
                                       @error('password') border-red-400/50 @enderror
                                       placeholder-gray-400"
                                placeholder="Wprowadz haslo"
                                @error('password') aria-invalid="true" @enderror
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition-colors duration-200"
                                :title="showPassword ? 'Ukryj haslo' : 'Pokaz haslo'"
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
                                class="checkbox-enterprise h-4 w-4"
                            >
                            <label for="remember" class="ml-3 block text-sm text-white hover:text-gray-200 transition-colors duration-200">
                                Zapamietaj mnie
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#"
                               class="font-medium text-gray-400 hover:text-white transition-colors duration-200">
                                Zapomniales hasla?
                            </a>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-center">
                        <button
                            type="submit"
                            :disabled="loading"
                            :class="loading ? 'opacity-75 cursor-not-allowed' : ''"
                            class="login-cta-shine group relative w-80 max-w-full mx-auto flex justify-center items-center py-3 px-4
                                   text-sm font-semibold rounded-xl text-white
                                   focus:outline-none focus:ring-4 focus:ring-orange-400/50
                                   transition-all duration-300 ease-out
                                   disabled:transform-none disabled:hover:scale-100"
                        >
                            <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>

                            <div x-show="loading" class="flex items-center mr-2">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                            </div>

                            <span x-text="loading ? 'Logowanie...' : 'Zaloguj sie do PPM'"></span>
                        </button>
                    </div>

                    {{-- OAuth Error --}}
                    @error('oauth')
                        <div class="mt-3 text-sm text-red-300 text-center">{{ $message }}</div>
                    @enderror

                    {{-- Divider --}}
                    <div class="relative my-5">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-600/50"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 text-gray-500 bg-gray-800/30 rounded">lub</span>
                        </div>
                    </div>

                    {{-- Microsoft Login Button --}}
                    <div class="flex justify-center">
                        <a href="{{ route('auth.microsoft.redirect') }}"
                           class="microsoft-login-btn w-80 max-w-full">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" viewBox="0 0 21 21">
                                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                            </svg>
                            Zaloguj przez Microsoft
                        </a>
                    </div>

                    {{-- Additional Info --}}
                    <div class="text-center">
                        <p class="text-xs text-gray-400">
                            Bezpieczne logowanie &bull; Enterprise System
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
@endsection
