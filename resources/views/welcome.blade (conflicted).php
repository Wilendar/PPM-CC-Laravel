@extends('layouts.auth')

@section('title', 'PPM - Prestashop Product Manager')

@push('head')
    @vite(['resources/css/auth/landing-animations.css'])
    <meta name="description" content="PPM - System zarzadzania produktami klasy enterprise dla MPP TRADE. Multi-store management, integracje ERP, zaawansowana analityka.">
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

    {{-- Grid Pattern --}}
    <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>

    {{-- Main Content --}}
    <div class="relative z-10 flex flex-col justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">

            {{-- Logo --}}
            <div class="mb-8">
                <div class="mb-6 landing-logo">
                    <img
                        src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png"
                        alt="MPP TRADE Logo"
                        class="landing-logo-img"
                        onerror="this.style.display='none'"
                    >
                </div>

                {{-- Motto (per-word reveal) --}}
                <div class="text-lg sm:text-xl font-semibold mb-8 brand-mantra">
                    @foreach(['///', 'TWORZYMY', 'PASJE', '///', 'DOSTARCZAMY', 'EMOCJE', '///'] as $i => $word)
                        <span class="landing-motto-word inline-block" style="--word-index: {{ $i }}">{{ $word }}&nbsp;</span>
                    @endforeach
                </div>

                <h2 class="text-lg sm:text-xl text-gray-300 mb-6 landing-subtitle">
                    Prestashop Product Manager
                </h2>
            </div>

            {{-- Hero Description --}}
            <div class="mb-12 space-y-6 landing-description">
                <p class="text-xl sm:text-2xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
                    System zarzadzania produktami klasy enterprise dla
                    <span class="font-semibold text-white">MPP TRADE</span>
                </p>

                <div class="text-lg text-gray-400 max-w-2xl mx-auto space-y-2">
                    <p>Centralny hub do zarzadzania produktami na wielu sklepach Prestashop jednoczesnie</p>
                    <p class="text-base text-gray-500">Import &bull; Export &bull; Synchronizacja &bull; Analityka</p>
                </div>
            </div>

            {{-- Features Grid --}}
            <div class="mb-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                    {{-- Multi-Store Management --}}
                    <div class="landing-card bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                        <div class="icon-chip mb-4 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Multi-Store</h3>
                        <p class="text-gray-400 text-sm">Zarzadzanie wieloma sklepami z jednego miejsca</p>
                    </div>

                    {{-- Enterprise Integration --}}
                    <div class="landing-card bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                        <div class="icon-chip mb-4 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Integracje ERP</h3>
                        <p class="text-gray-400 text-sm">Baselinker, Subiekt GT, Microsoft Dynamics</p>
                    </div>

                    {{-- Advanced Analytics --}}
                    <div class="landing-card bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                        <div class="icon-chip mb-4 mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Analityka</h3>
                        <p class="text-gray-400 text-sm">Zaawansowane raporty i business intelligence</p>
                    </div>
                </div>
            </div>

            {{-- CTA Section --}}
            <div class="landing-cta">
                <a
                    href="/login"
                    class="landing-cta-btn inline-flex items-center px-8 py-4 font-semibold rounded-full
                           text-white hover:scale-105 focus:outline-none focus:ring-4 focus:ring-orange-500/50
                           transition-all duration-300 shadow-2xl btn-enterprise-primary"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Zaloguj sie do systemu
                </a>

                <p class="mt-6 text-gray-400 text-sm landing-footer-text">
                    Bezpieczne logowanie przez Google Workspace lub Microsoft Entra ID
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
