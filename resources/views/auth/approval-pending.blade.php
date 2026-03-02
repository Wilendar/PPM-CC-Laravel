@extends('layouts.auth')

@section('title', 'Oczekiwanie na zatwierdzenie - PPM')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    {{-- Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>
    <div class="absolute inset-0">
        <div class="absolute top-10 left-10 w-72 h-72 bg-gray-800/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-orange-300/10 rounded-full blur-3xl"></div>
    </div>
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>

    {{-- Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <img
                    src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png"
                    alt="MPP TRADE"
                    class="h-12 mx-auto mb-4"
                    onerror="this.style.display='none'"
                >
                <h1 class="text-2xl font-bold text-white">PPM - Panel Zarządzania</h1>
            </div>

            {{-- Alert card --}}
            <div class="enterprise-card border border-yellow-600/40 bg-yellow-900/20">
                <div class="flex items-start gap-4 p-6">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Text --}}
                    <div>
                        <h2 class="text-lg font-semibold text-yellow-300 mb-2">
                            Konto oczekuje na zatwierdzenie
                        </h2>
                        <p class="text-gray-300 text-sm leading-relaxed">
                            Twoje konto zostało zarejestrowane, ale wymaga zatwierdzenia przez administratora
                            systemu przed uzyskaniem dostępu do panelu PPM.
                        </p>
                    </div>
                </div>

                <div class="border-t border-yellow-600/30 px-6 py-4">
                    <p class="text-gray-400 text-sm">
                        Zalogowane konto:
                        <span class="text-gray-200 font-medium">{{ auth()->user()?->email }}</span>
                    </p>
                    <p class="text-gray-500 text-xs mt-1">
                        Skontaktuj się z administratorem systemu lub poczekaj na powiadomienie e-mail
                        o zatwierdzeniu konta.
                    </p>
                </div>
            </div>

            {{-- Warning flash message --}}
            @if (session('warning'))
                <div class="mt-4 p-3 rounded-lg bg-yellow-900/30 border border-yellow-600/40 text-yellow-300 text-sm">
                    {{ session('warning') }}
                </div>
            @endif

            {{-- Logout link --}}
            <div class="text-center mt-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-gray-400 hover:text-gray-200 text-sm transition-colors duration-150">
                        Wyloguj się i użyj innego konta
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
