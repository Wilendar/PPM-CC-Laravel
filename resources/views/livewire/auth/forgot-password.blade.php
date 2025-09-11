<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <h1 class="text-3xl font-bold text-gray-900">PPM</h1>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Resetowanie hasła
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Wpisz swój adres email, aby otrzymać link do resetowania hasła
            </p>
        </div>

        @if($emailSent)
            {{-- Success State --}}
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">
                            Email został wysłany!
                        </h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>
                                Link do resetowania hasła został wysłany na adres <strong>{{ $email }}</strong>. 
                                Sprawdź swoją skrzynkę odbiorczą i folder spam.
                            </p>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <button 
                                    type="button" 
                                    wire:click="resendEmail"
                                    @if($loading) disabled @endif
                                    class="bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600
                                           @if($loading) opacity-50 cursor-not-allowed @endif"
                                >
                                    @if($loading)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Wysyłanie...
                                    @else
                                        Wyślij ponownie
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Instructions --}}
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Instrukcja
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Sprawdź swoją skrzynkę odbiorczą (również folder spam)</li>
                                <li>Kliknij link w otrzymanym emailu</li>
                                <li>Ustaw nowe hasło zgodnie z wymaganiami bezpieczeństwa</li>
                                <li>Link jest ważny przez 60 minut</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Reset Form --}}
            <form wire:submit.prevent="sendResetLink" class="mt-8 space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Adres email
                    </label>
                    <input 
                        wire:model.lazy="email"
                        id="email"
                        name="email" 
                        type="email" 
                        autocomplete="email"
                        required 
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border 
                               @error('email') border-red-300 @else border-gray-300 @enderror
                               placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                               focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Wprowadź adres email przypisany do Twojego konta"
                    >
                    @error('email')
                        <div class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <button 
                        type="submit"
                        @if($loading) disabled @endif
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                               text-sm font-medium rounded-md text-white
                               @if($loading) 
                                   bg-gray-400 cursor-not-allowed 
                               @else 
                                   bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                               @endif 
                               focus:outline-none transition duration-150 ease-in-out"
                    >
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Wysyłanie linku...
                        @else
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Wyślij link resetujący
                        @endif
                    </button>
                </div>
            </form>

            {{-- Security Information --}}
            <div class="rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Informacja o bezpieczeństwie
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>
                                Ze względów bezpieczeństwa, otrzymasz email niezależnie od tego, czy podany adres istnieje w naszej bazie danych.
                                Link do resetowania hasła jest ważny przez 60 minut.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Back to Login --}}
        <div class="text-center">
            <a href="{{ route('login') }}" 
               class="font-medium text-blue-600 hover:text-blue-500 flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Powrót do logowania
            </a>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="sendResetLink,resendEmail" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-900">Wysyłanie linku resetującego...</span>
                </div>
            </div>
        </div>
    </div>
</div>