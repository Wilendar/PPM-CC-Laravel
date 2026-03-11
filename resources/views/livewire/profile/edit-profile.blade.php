<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">
            Profil użytkownika
        </h1>
        <p class="mt-1 text-sm text-gray-400">
            Zarządzaj swoimi danymi osobistymi, preferencjami i ustawieniami bezpieczeństwa
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded-md text-green-300 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded-md text-red-300 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                wire:click="$set('activeTab', 'personal')"
                class="admin-tab @if($activeTab === 'personal') admin-tab--active @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Dane osobiste
            </button>

            <button
                wire:click="$set('activeTab', 'preferences')"
                class="admin-tab @if($activeTab === 'preferences') admin-tab--active @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Preferencje
            </button>

            <button
                wire:click="$set('activeTab', 'notifications')"
                class="admin-tab @if($activeTab === 'notifications') admin-tab--active @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h10v-1a3 3 0 00-3-3H7a3 3 0 00-3 3v1zM12 3a4 4 0 00-4 4v4l-2 2h12l-2-2V7a4 4 0 00-4-4z"></path>
                </svg>
                Powiadomienia
            </button>

            <button
                wire:click="$set('activeTab', 'security')"
                class="admin-tab @if($activeTab === 'security') admin-tab--active @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Bezpieczeństwo
            </button>
        </nav>
    </div>

    <form wire:submit.prevent="save">
        {{-- Personal Information Tab --}}
        @if($activeTab === 'personal')
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Informacje osobiste
                    </h3>

                    {{-- Avatar Section --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Zdjęcie profilowe
                        </label>

                        <div class="flex items-center space-x-6">
                            {{-- Current Avatar --}}
                            <div class="shrink-0">
                                @if($current_avatar)
                                    <img src="{{ Storage::url($current_avatar) }}"
                                         alt="Current avatar"
                                         class="h-20 w-20 object-cover rounded-full">
                                @else
                                    <div class="h-20 w-20 rounded-full bg-gray-600 flex items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Upload Controls --}}
                            <div class="flex-1">
                                <div class="mt-2">
                                    <input wire:model="avatar" type="file" accept="image/*"
                                           class="file-input-enterprise">
                                </div>
                                @error('avatar')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror

                                @if($current_avatar)
                                    <button type="button"
                                            wire:click="removeAvatar"
                                            class="mt-2 text-sm text-red-400 hover:text-red-300">
                                        Usuń zdjęcie
                                    </button>
                                @endif

                                <p class="mt-1 text-sm text-gray-400">
                                    JPG, PNG lub GIF do 2MB
                                </p>
                            </div>

                            {{-- Preview of new avatar --}}
                            @if($avatar)
                                <div class="shrink-0">
                                    <img src="{{ $avatar->temporaryUrl() }}"
                                         alt="New avatar preview"
                                         class="h-20 w-20 object-cover rounded-full border-2 border-green-400">
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Personal Info Form --}}
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        {{-- First Name --}}
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-300">
                                Imię *
                            </label>
                            <input wire:model.lazy="first_name"
                                   type="text"
                                   id="first_name"
                                   class="mt-1 form-input-enterprise">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Last Name --}}
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-300">
                                Nazwisko *
                            </label>
                            <input wire:model.lazy="last_name"
                                   type="text"
                                   id="last_name"
                                   class="mt-1 form-input-enterprise">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="sm:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-300">
                                Adres email *
                            </label>
                            <input wire:model.lazy="email"
                                   type="email"
                                   id="email"
                                   @if(!$canEditEmail) disabled @endif
                                   class="mt-1 form-input-enterprise @if(!$canEditEmail) opacity-60 cursor-not-allowed @endif">
                            @if(!$canEditEmail)
                                <p class="mt-1 text-xs text-amber-400">Zmiana email wymaga uprawnien administratora</p>
                            @endif
                            @error('email')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Company --}}
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-300">
                                Firma
                            </label>
                            <input wire:model.lazy="company"
                                   type="text"
                                   id="company"
                                   class="mt-1 form-input-enterprise">
                            @error('company')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Position --}}
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-300">
                                Stanowisko
                            </label>
                            <input wire:model.lazy="position"
                                   type="text"
                                   id="position"
                                   class="mt-1 form-input-enterprise">
                            @error('position')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="sm:col-span-2">
                            <label for="phone" class="block text-sm font-medium text-gray-300">
                                Telefon
                            </label>
                            <input wire:model.lazy="phone"
                                   type="tel"
                                   id="phone"
                                   class="mt-1 form-input-enterprise">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Preferences Tab --}}
        @if($activeTab === 'preferences')
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Preferencje interfejsu
                    </h3>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        {{-- Theme --}}
                        <div>
                            <label for="theme" class="block text-sm font-medium text-gray-300">
                                Motyw
                            </label>
                            <select wire:model="theme"
                                    id="theme"
                                    class="mt-1 form-input-enterprise">
                                <option value="dark">Ciemny (aktywny)</option>
                                <option value="auto">Automatyczny</option>
                            </select>
                        </div>

                        {{-- Language --}}
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-300">
                                Język
                            </label>
                            <select wire:model="language"
                                    id="language"
                                    class="mt-1 form-input-enterprise">
                                <option value="pl">Polski</option>
                                <option value="en">English</option>
                            </select>
                        </div>

                        {{-- Date Format --}}
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-gray-300">
                                Format daty
                            </label>
                            <select wire:model="date_format"
                                    id="date_format"
                                    class="mt-1 form-input-enterprise">
                                <option value="d.m.Y">31.12.2023</option>
                                <option value="Y-m-d">2023-12-31</option>
                                <option value="m/d/Y">12/31/2023</option>
                            </select>
                        </div>

                        {{-- Timezone --}}
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-300">
                                Strefa czasowa
                            </label>
                            <select wire:model="timezone"
                                    id="timezone"
                                    class="mt-1 form-input-enterprise">
                                <option value="Europe/Warsaw">Europa/Warszawa (CET/CEST)</option>
                                <option value="UTC">UTC</option>
                                <option value="Europe/London">Europa/Londyn (GMT/BST)</option>
                                <option value="America/New_York">Ameryka/Nowy_Jork (EST/EDT)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Notifications Tab --}}
        @if($activeTab === 'notifications')
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Ustawienia powiadomień
                    </h3>

                    <div class="space-y-6">
                        {{-- Email Notifications --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Powiadomienia email</h4>
                                <p class="text-sm text-gray-400">Otrzymuj powiadomienia o ważnych zdarzeniach w systemie</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('email_notifications')"
                                    class="toggle-switch @if($email_notifications) toggle-switch--active @endif">
                                <span class="sr-only">Toggle email notifications</span>
                                <span class="toggle-switch__dot"></span>
                            </button>
                        </div>

                        {{-- Browser Notifications --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Powiadomienia przeglądarki</h4>
                                <p class="text-sm text-gray-400">Wyświetlaj powiadomienia w przeglądarce</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('browser_notifications')"
                                    class="toggle-switch @if($browser_notifications) toggle-switch--active @endif">
                                <span class="sr-only">Toggle browser notifications</span>
                                <span class="toggle-switch__dot"></span>
                            </button>
                        </div>

                        {{-- Mobile Notifications --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Powiadomienia mobilne</h4>
                                <p class="text-sm text-gray-400">Otrzymuj powiadomienia push na urządzenia mobilne</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('mobile_notifications')"
                                    class="toggle-switch @if($mobile_notifications) toggle-switch--active @endif">
                                <span class="sr-only">Toggle mobile notifications</span>
                                <span class="toggle-switch__dot"></span>
                            </button>
                        </div>

                        {{-- Marketing Emails --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Marketing i promocje</h4>
                                <p class="text-sm text-gray-400">Otrzymuj informacje o nowościach i promocjach</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('marketing_emails')"
                                    class="toggle-switch @if($marketing_emails) toggle-switch--active @endif">
                                <span class="sr-only">Toggle marketing emails</span>
                                <span class="toggle-switch__dot"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Security Tab --}}
        @if($activeTab === 'security')
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Bezpieczeństwo konta
                    </h3>

                    {{-- Password Change Section --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-sm font-medium text-white">Zmiana hasła</h4>
                                <p class="text-sm text-gray-400">Aktualizuj swoje hasło dla większego bezpieczeństwa</p>
                            </div>
                            <button type="button"
                                    wire:click="togglePasswordSection"
                                    class="btn-enterprise-secondary">
                                @if($showPasswordSection)
                                    Anuluj zmianę hasła
                                @else
                                    Zmień hasło
                                @endif
                            </button>
                        </div>

                        @if($showPasswordSection)
                            <div class="space-y-4 p-4 bg-gray-700 rounded-md">
                                {{-- Current Password --}}
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-300">
                                        Obecne hasło *
                                    </label>
                                    <input wire:model.lazy="current_password"
                                           type="password"
                                           id="current_password"
                                           class="mt-1 form-input-enterprise">
                                    @error('current_password')
                                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- New Password --}}
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-300">
                                        Nowe hasło *
                                    </label>
                                    <input wire:model.lazy="password"
                                           type="password"
                                           id="password"
                                           class="mt-1 form-input-enterprise">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                    @enderror

                                    {{-- Password Strength --}}
                                    @if(strlen($password) > 0)
                                        <div class="mt-2 flex items-center space-x-2">
                                            <div class="password-strength-bar" style="--progress: {{ ($passwordStrength / 5) * 100 }}%">
                                                <div class="password-strength-fill"></div>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded {{ $passwordStrengthColor }}">
                                                {{ $passwordStrengthText }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Password Confirmation --}}
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300">
                                        Potwierdź nowe hasło *
                                    </label>
                                    <input wire:model.lazy="password_confirmation"
                                           type="password"
                                           id="password_confirmation"
                                           class="mt-1 form-input-enterprise">
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Role Information --}}
                    <div class="border-t border-gray-700 pt-6">
                        <h4 class="text-sm font-medium text-white mb-2">Uprawnienia konta</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach(Auth::user()->roles as $role)
                                <span class="role-badge
                                           @switch($role->name)
                                               @case('Admin') role-badge--admin @break
                                               @case('Manager') role-badge--manager @break
                                               @case('Edytor') role-badge--editor @break
                                               @case('Magazyn') role-badge--warehouse @break
                                               @case('Handlowy') role-badge--sales @break
                                               @case('Reklamacje') role-badge--claims @break
                                               @default role-badge--default
                                           @endswitch">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                        <p class="mt-2 text-sm text-gray-400">
                            Skontaktuj się z administratorem w celu zmiany uprawnień.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Save Button --}}
        <div class="mt-6 flex justify-end">
            <button type="submit"
                    @if($loading) disabled @endif
                    class="btn-enterprise-primary @if($loading) opacity-50 cursor-not-allowed @endif">
                @if($loading)
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Zapisywanie...
                @else
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Zapisz zmiany
                @endif
            </button>
        </div>
    </form>

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="save,removeAvatar" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full layer-overlay items-center justify-center">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg border border-gray-700">
            <div class="flex items-center">
                <svg class="animate-spin h-6 w-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-100">Przetwarzanie...</span>
            </div>
        </div>
    </div>
</div>