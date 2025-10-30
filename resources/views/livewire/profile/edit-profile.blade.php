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

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button 
                wire:click="$set('activeTab', 'personal')"
                class="py-2 px-1 border-b-2 font-medium text-sm 
                       @if($activeTab === 'personal') 
                           border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400
                       @else 
                           border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                       @endif
                       transition-colors duration-200"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Dane osobiste
            </button>

            <button 
                wire:click="$set('activeTab', 'preferences')"
                class="py-2 px-1 border-b-2 font-medium text-sm
                       @if($activeTab === 'preferences') 
                           border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400
                       @else 
                           border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                       @endif
                       transition-colors duration-200"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Preferencje
            </button>

            <button 
                wire:click="$set('activeTab', 'notifications')"
                class="py-2 px-1 border-b-2 font-medium text-sm
                       @if($activeTab === 'notifications') 
                           border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400
                       @else 
                           border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                       @endif
                       transition-colors duration-200"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h10v-1a3 3 0 00-3-3H7a3 3 0 00-3 3v1zM12 3a4 4 0 00-4 4v4l-2 2h12l-2-2V7a4 4 0 00-4-4z"></path>
                </svg>
                Powiadomienia
            </button>

            <button 
                wire:click="$set('activeTab', 'security')"
                class="py-2 px-1 border-b-2 font-medium text-sm
                       @if($activeTab === 'security') 
                           border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400
                       @else 
                           border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                       @endif
                       transition-colors duration-200"
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
                                    <div class="h-20 w-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
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
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-md file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-blue-50 file:text-blue-700
                                                  hover:file:bg-blue-100
                                                  dark:file:bg-blue-900 dark:file:text-blue-300
                                                  dark:hover:file:bg-blue-800">
                                </div>
                                @error('avatar')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                
                                @if($current_avatar)
                                    <button type="button" 
                                            wire:click="removeAvatar"
                                            class="mt-2 text-sm text-red-600 hover:text-red-500">
                                        Usuń zdjęcie
                                    </button>
                                @endif
                                
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('company')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('position')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                   class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                    class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="light">Jasny</option>
                                <option value="dark">Ciemny</option>
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
                                    class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                                    class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                                    class="mt-1 block w-full border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                                <p class="text-sm text-gray-500 dark:text-gray-400">Otrzymuj powiadomienia o ważnych zdarzeniach w systemie</p>
                            </div>
                            <button type="button" 
                                    wire:click="$toggle('email_notifications')"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                                           @if($email_notifications) bg-blue-600 @else bg-gray-200 dark:bg-gray-600 @endif">
                                <span class="sr-only">Toggle email notifications</span>
                                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-gray-800 shadow transform ring-0 transition ease-in-out duration-200 
                                             @if($email_notifications) translate-x-5 @else translate-x-0 @endif"></span>
                            </button>
                        </div>

                        {{-- Browser Notifications --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Powiadomienia przeglądarki</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Wyświetlaj powiadomienia w przeglądarce</p>
                            </div>
                            <button type="button" 
                                    wire:click="$toggle('browser_notifications')"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                                           @if($browser_notifications) bg-blue-600 @else bg-gray-200 dark:bg-gray-600 @endif">
                                <span class="sr-only">Toggle browser notifications</span>
                                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-gray-800 shadow transform ring-0 transition ease-in-out duration-200 
                                             @if($browser_notifications) translate-x-5 @else translate-x-0 @endif"></span>
                            </button>
                        </div>

                        {{-- Mobile Notifications --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Powiadomienia mobilne</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Otrzymuj powiadomienia push na urządzenia mobilne</p>
                            </div>
                            <button type="button" 
                                    wire:click="$toggle('mobile_notifications')"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                                           @if($mobile_notifications) bg-blue-600 @else bg-gray-200 dark:bg-gray-600 @endif">
                                <span class="sr-only">Toggle mobile notifications</span>
                                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-gray-800 shadow transform ring-0 transition ease-in-out duration-200 
                                             @if($mobile_notifications) translate-x-5 @else translate-x-0 @endif"></span>
                            </button>
                        </div>

                        {{-- Marketing Emails --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Marketing i promocje</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Otrzymuj informacje o nowościach i promocjach</p>
                            </div>
                            <button type="button" 
                                    wire:click="$toggle('marketing_emails')"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                                           @if($marketing_emails) bg-blue-600 @else bg-gray-200 dark:bg-gray-600 @endif">
                                <span class="sr-only">Toggle marketing emails</span>
                                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-gray-800 shadow transform ring-0 transition ease-in-out duration-200 
                                             @if($marketing_emails) translate-x-5 @else translate-x-0 @endif"></span>
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
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aktualizuj swoje hasło dla większego bezpieczeństwa</p>
                            </div>
                            <button type="button" 
                                    wire:click="togglePasswordSection"
                                    class="px-4 py-2 border border-gray-600 rounded-md text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                @if($showPasswordSection)
                                    Anuluj zmianę hasła
                                @else
                                    Zmień hasło
                                @endif
                            </button>
                        </div>

                        @if($showPasswordSection)
                            <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                                {{-- Current Password --}}
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-300">
                                        Obecne hasło *
                                    </label>
                                    <input wire:model.lazy="current_password" 
                                           type="password" 
                                           id="current_password" 
                                           class="mt-1 block w-full border-gray-600 dark:bg-gray-600 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('current_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                           class="mt-1 block w-full border-gray-600 dark:bg-gray-600 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    
                                    {{-- Password Strength --}}
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
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300">
                                        Potwierdź nowe hasło *
                                    </label>
                                    <input wire:model.lazy="password_confirmation" 
                                           type="password" 
                                           id="password_confirmation" 
                                           class="mt-1 block w-full border-gray-600 dark:bg-gray-600 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Role Information --}}
                    <div class="border-t border-gray-700 pt-6">
                        <h4 class="text-sm font-medium text-white mb-2">Uprawnienia konta</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach(Auth::user()->roles as $role)
                                <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                           @switch($role->name)
                                               @case('Admin') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                                               @case('Manager') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 @break
                                               @case('Editor') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                                               @case('Warehouseman') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                                               @case('Salesperson') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @break
                                               @case('Claims') bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200 @break
                                               @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                           @endswitch">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
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
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white 
                           @if($loading) 
                               bg-gray-400 cursor-not-allowed 
                           @else 
                               bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                           @endif 
                           focus:outline-none transition duration-150 ease-in-out">
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
    <div wire:loading.flex wire:target="save,removeAvatar" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 items-center justify-center">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
            <div class="flex items-center">
                <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-100">Przetwarzanie...</span>
            </div>
        </div>
    </div>
</div>