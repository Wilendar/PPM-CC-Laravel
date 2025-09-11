<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <h1 class="text-3xl font-bold text-gray-900">PPM</h1>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Utwórz nowe konto
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Dołącz do systemu zarządzania produktami PPM
            </p>
        </div>

        {{-- Progress Steps --}}
        <div class="flex justify-center">
            <nav aria-label="Progress" class="flex items-center">
                @for($i = 1; $i <= $maxSteps; $i++)
                    <div class="flex items-center">
                        <div class="relative">
                            @if($step > $i)
                                {{-- Completed Step --}}
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @elseif($step === $i)
                                {{-- Current Step --}}
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">{{ $i }}</span>
                                </div>
                            @else
                                {{-- Future Step --}}
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <span class="text-gray-600 text-sm font-medium">{{ $i }}</span>
                                </div>
                            @endif
                        </div>
                        @if($i < $maxSteps)
                            <div class="ml-4 mr-4 w-16 h-0.5 {{ $step > $i ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                        @endif
                    </div>
                @endfor
            </nav>
        </div>

        {{-- Multi-Step Form --}}
        <form wire:submit.prevent="register" class="mt-8 space-y-6">
            {{-- Step 1: Personal Information --}}
            @if($step === 1)
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informacje osobiste</h3>
                    
                    {{-- First Name & Last Name Row --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">
                                Imię *
                            </label>
                            <input 
                                wire:model.lazy="first_name"
                                id="first_name"
                                name="first_name" 
                                type="text" 
                                required 
                                class="mt-1 appearance-none relative block w-full px-3 py-2 border 
                                       @error('first_name') border-red-300 @else border-gray-300 @enderror
                                       placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                       focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Twoje imię"
                            >
                            @error('first_name')
                                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">
                                Nazwisko *
                            </label>
                            <input 
                                wire:model.lazy="last_name"
                                id="last_name"
                                name="last_name" 
                                type="text" 
                                required 
                                class="mt-1 appearance-none relative block w-full px-3 py-2 border 
                                       @error('last_name') border-red-300 @else border-gray-300 @enderror
                                       placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                       focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Twoje nazwisko"
                            >
                            @error('last_name')
                                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Adres email *
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
                            placeholder="twoj@email.pl"
                        >
                        @error('email')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Hasło *
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
                                   placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                   focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Minimum 8 znaków"
                        >
                        @error('password')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
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

                    {{-- Password Confirmation --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Potwierdź hasło *
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
                                   placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                   focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Powtórz hasło"
                        >
                    </div>
                </div>
            @endif

            {{-- Step 2: Company Information --}}
            @if($step === 2)
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informacje o firmie (opcjonalne)</h3>
                    
                    {{-- Company --}}
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700">
                            Nazwa firmy
                        </label>
                        <input 
                            wire:model.lazy="company"
                            id="company"
                            name="company" 
                            type="text" 
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300
                                   placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                   focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="np. MPP TRADE"
                        >
                        @error('company')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Position --}}
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700">
                            Stanowisko
                        </label>
                        <input 
                            wire:model.lazy="position"
                            id="position"
                            name="position" 
                            type="text" 
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300
                                   placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                   focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="np. Kierownik sprzedaży"
                        >
                        @error('position')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Numer telefonu
                        </label>
                        <input 
                            wire:model.lazy="phone"
                            id="phone"
                            name="phone" 
                            type="tel" 
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300
                                   placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                                   focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="+48 123 456 789"
                        >
                        @error('phone')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800">
                                    <strong>Informacja:</strong> Użytkownicy z domen @mpptrade.eu otrzymują automatycznie uprawnienia kierownicze.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Step 3: Legal Agreements --}}
            @if($step === 3)
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Akceptacja regulaminów</h3>
                    
                    {{-- Terms of Service --}}
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                wire:model="terms_accepted"
                                id="terms_accepted"
                                name="terms_accepted" 
                                type="checkbox" 
                                required
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded
                                       @error('terms_accepted') border-red-300 @enderror"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms_accepted" class="font-medium text-gray-700">
                                Akceptuję <a href="/regulamin" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Regulamin świadczenia usług</a> *
                            </label>
                            @error('terms_accepted')
                                <div class="mt-1 text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Privacy Policy --}}
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                wire:model="privacy_accepted"
                                id="privacy_accepted"
                                name="privacy_accepted" 
                                type="checkbox" 
                                required
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded
                                       @error('privacy_accepted') border-red-300 @enderror"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="privacy_accepted" class="font-medium text-gray-700">
                                Akceptuję <a href="/polityka-prywatnosci" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Politykę prywatności</a> *
                            </label>
                            @error('privacy_accepted')
                                <div class="mt-1 text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Marketing Consent --}}
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                wire:model="marketing_accepted"
                                id="marketing_accepted"
                                name="marketing_accepted" 
                                type="checkbox" 
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="marketing_accepted" class="font-medium text-gray-700">
                                Zgadzam się na otrzymywanie informacji marketingowych o nowych funkcjach systemu PPM
                            </label>
                            <p class="text-gray-500 text-xs mt-1">
                                Opcjonalne. Możesz zmienić to ustawienie w dowolnym momencie w profilu użytkownika.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="flex justify-between pt-6">
                {{-- Previous Button --}}
                @if($step > 1)
                    <button 
                        type="button"
                        wire:click="previousStep"
                        class="group relative flex justify-center py-2 px-4 border border-gray-300 
                               text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Poprzedni
                    </button>
                @else
                    <div></div> {{-- Spacer --}}
                @endif

                {{-- Next/Submit Button --}}
                @if($step < $maxSteps)
                    <button 
                        type="button"
                        wire:click="nextStep"
                        class="group relative flex justify-center py-2 px-4 border border-transparent 
                               text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Dalej
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @else
                    <button 
                        type="submit"
                        @if($loading) disabled @endif
                        class="group relative flex justify-center py-2 px-4 border border-transparent 
                               text-sm font-medium rounded-md text-white
                               @if($loading) 
                                   bg-gray-400 cursor-not-allowed 
                               @else 
                                   bg-green-600 hover:bg-green-700 
                               @endif
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        @if($loading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Tworzenie konta...
                        @else
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Utwórz konto
                        @endif
                    </button>
                @endif
            </div>
        </form>

        {{-- Login Link --}}
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Masz już konto? 
                <a href="{{ route('login') }}" 
                   class="font-medium text-blue-600 hover:text-blue-500">
                    Zaloguj się
                </a>
            </p>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="register" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-900">Tworzenie konta użytkownika...</span>
                </div>
            </div>
        </div>
    </div>
</div>