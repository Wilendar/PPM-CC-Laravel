<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <h1 class="text-3xl font-bold text-white">PPM</h1>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                Ustaw nowe hasło
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Wprowadź swoje nowe hasło dla konta {{ $email }}
            </p>
        </div>

        {{-- Reset Password Form --}}
        <form wire:submit.prevent="resetPassword" class="mt-8 space-y-6">
            {{-- Hidden fields --}}
            <input type="hidden" wire:model="token">
            <input type="hidden" wire:model="email">

            {{-- Email Display (Read-only) --}}
            <div>
                <label for="email_display" class="block text-sm font-medium text-gray-700">
                    Adres email
                </label>
                <input 
                    type="email"
                    value="{{ $email }}"
                    readonly
                    class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-200 bg-gray-50
                           text-gray-600 rounded-md focus:outline-none sm:text-sm cursor-not-allowed"
                >
            </div>

            {{-- New Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Nowe hasło *
                </label>
                <input 
                    wire:model.lazy="password"
                    id="password"
                    name="password" 
                    type="password" 
                    autocomplete="new-password"
                    required 
                    class="mt-1 appearance-none relative block w-full px-3 py-2 border 
                           @error('password') border-red-300 @else border-gray-300 @enderror
                           placeholder-gray-500 text-white rounded-md focus:outline-none 
                           focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Minimum 8 znaków"
                >
                @error('password')
                    <div class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</div>
                @enderror
                
                {{-- Password Strength Indicator --}}
                @if(strlen($password) > 0)
                    <div class="mt-2 flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ ($passwordStrength / 5) * 100 }}%"></div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded {{ $passwordStrengthColor }}">
                            {{ $passwordStrengthText }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Potwierdź nowe hasło *
                </label>
                <input 
                    wire:model.lazy="password_confirmation"
                    id="password_confirmation"
                    name="password_confirmation" 
                    type="password" 
                    autocomplete="new-password"
                    required 
                    class="mt-1 appearance-none relative block w-full px-3 py-2 border 
                           @error('password') border-red-300 @else border-gray-300 @enderror
                           placeholder-gray-500 text-white rounded-md focus:outline-none 
                           focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Powtórz nowe hasło"
                >
            </div>

            {{-- Password Requirements --}}
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Wymagania dla hasła
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li class="{{ strlen($password) >= 8 ? 'text-green-600' : '' }}">
                                    Co najmniej 8 znaków
                                </li>
                                <li class="{{ preg_match('/[a-z]/', $password) ? 'text-green-600' : '' }}">
                                    Zawiera małe litery
                                </li>
                                <li class="{{ preg_match('/[A-Z]/', $password) ? 'text-green-600' : '' }}">
                                    Zawiera duże litery
                                </li>
                                <li class="{{ preg_match('/[0-9]/', $password) ? 'text-green-600' : '' }}">
                                    Zawiera cyfry
                                </li>
                                <li class="{{ preg_match('/[^A-Za-z0-9]/', $password) ? 'text-green-600' : '' }}">
                                    Zawiera znaki specjalne
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Token Error --}}
            @error('token')
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Link nieprawidłowy lub wygasł
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ $message }}</p>
                                <div class="mt-4">
                                    <a href="{{ route('password.request') }}" 
                                       class="font-medium underline text-red-700 hover:text-red-600">
                                        Poproś o nowy link resetujący
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @enderror

            {{-- Submit Button --}}
            <div>
                <button 
                    type="submit"
                    @if($loading) disabled @endif
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                           text-sm font-medium rounded-md text-white
                           @if($loading) 
                               bg-gray-400 cursor-not-allowed 
                           @else 
                               bg-green-600 hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-500
                           @endif 
                           focus:outline-none transition duration-150 ease-in-out"
                >
                    @if($loading)
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Resetowanie hasła...
                    @else
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 01-2 2m2-2h-6m6 0v6a2 2 0 01-2 2H9a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2z"></path>
                        </svg>
                        Ustaw nowe hasło
                    @endif
                </button>
            </div>
        </form>

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

        {{-- Security Notice --}}
        <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Bezpieczeństwo
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>
                            Po zmianie hasła zostaną wylogowane wszystkie inne sesje na różnych urządzeniach. 
                            Zostaniesz automatycznie zalogowany na tym urządzeniu.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="resetPassword" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 items-center justify-center">
            <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-white">Ustawianie nowego hasła...</span>
                </div>
            </div>
        </div>
    </div>
</div>