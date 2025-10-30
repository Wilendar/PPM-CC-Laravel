{{-- Admin User Form Component Template --}}
<div x-data="userFormManager()" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">
                @if($isEditing)
                    Edytuj użytkownika: {{ $user->full_name }}
                @else
                    Dodaj nowego użytkownika
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if($isEditing)
                    Zaktualizuj informacje o użytkowniku systemu PPM
                @else
                    Utwórz nowe konto użytkownika systemu PPM
                @endif
            </p>
        </div>
        
        <div class="mt-4 lg:mt-0 flex items-center space-x-3">
            {{-- Draft Status --}}
            @if(!$isEditing && $draftSaved)
                <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Zapisano {{ $lastSaveTime }}
                </div>
            @endif
            
            {{-- Back Button --}}
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Wróć do listy
            </a>
        </div>
    </div>

    {{-- Multi-Step Progress Bar --}}
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
        <div class="flex items-center justify-between mb-8">
            @foreach($stepTitles as $stepNumber => $stepTitle)
                <div class="flex items-center {{ $stepNumber < count($stepTitles) ? 'flex-1' : '' }}">
                    {{-- Step Circle --}}
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors duration-200
                                {{ $currentStep > $stepNumber ? 'bg-green-500 border-green-500 text-white' : 
                                   ($currentStep === $stepNumber ? 'bg-blue-500 border-blue-500 text-white' : 
                                   'bg-gray-700 border-gray-600 text-gray-500 dark:text-gray-400') }}">
                        @if($currentStep > $stepNumber)
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <span class="text-sm font-semibold">{{ $stepNumber }}</span>
                        @endif
                    </div>
                    
                    {{-- Step Title --}}
                    <div class="ml-3">
                        <button wire:click="goToStep({{ $stepNumber }})"
                                @if($stepNumber <= $currentStep) 
                                    class="text-sm font-medium text-white hover:text-blue-600 dark:hover:text-blue-400"
                                @else
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400 cursor-not-allowed"
                                @endif>
                            {{ $stepTitle }}
                        </button>
                    </div>
                    
                    {{-- Progress Line --}}
                    @if($stepNumber < count($stepTitles))
                        <div class="flex-1 mx-4">
                            <div class="h-0.5 bg-gray-200 dark:bg-gray-600 rounded">
                                <div class="h-0.5 rounded transition-all duration-300 
                                           {{ $currentStep > $stepNumber ? 'bg-green-500 w-full' : 'bg-gray-200 dark:bg-gray-600 w-0' }}">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step Content --}}
        <form wire:submit.prevent="save">
            {{-- STEP 1: Basic Information --}}
            @if($currentStep === 1)
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-white">Informacje podstawowe</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- First Name --}}
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">
                                Imię *
                            </label>
                            <input type="text" 
                                   wire:model.live.debounce.300ms="first_name" 
                                   id="first_name"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="Wprowadź imię">
                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Last Name --}}
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">
                                Nazwisko *
                            </label>
                            <input type="text" 
                                   wire:model.live.debounce.300ms="last_name" 
                                   id="last_name"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="Wprowadź nazwisko">
                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">
                                Adres email *
                            </label>
                            <input type="email" 
                                   wire:model.live.debounce.300ms="email" 
                                   id="email"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="email@example.com">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">
                                Telefon
                            </label>
                            <input type="tel" 
                                   wire:model="phone" 
                                   id="phone"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="+48 123 456 789">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Company --}}
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-300 mb-1">
                                Firma
                            </label>
                            <input type="text" 
                                   wire:model="company" 
                                   id="company"
                                   list="company-suggestions"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="Nazwa firmy">
                            
                            <datalist id="company-suggestions">
                                @foreach($companySuggestions as $suggestion)
                                    <option value="{{ $suggestion }}">
                                @endforeach
                            </datalist>
                            @error('company') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Position --}}
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-300 mb-1">
                                Stanowisko
                            </label>
                            <input type="text" 
                                   wire:model="position" 
                                   id="position"
                                   class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white"
                                   placeholder="Stanowisko w firmie">
                            @error('position') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- STEP 2: Access and Security --}}
            @if($currentStep === 2)
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-white">Dostęp i bezpieczeństwo</h3>
                    
                    {{-- Account Status --}}
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" 
                               wire:model="is_active" 
                               id="is_active"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <label for="is_active" class="text-sm font-medium text-gray-300">
                            Konto aktywne
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            (użytkownik może się logować)
                        </span>
                    </div>

                    {{-- Password Section --}}
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="text-md font-medium text-white mb-4">Hasło</h4>
                        
                        @if($isEditing)
                            <div class="mb-4">
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" 
                                           wire:model="password" 
                                           value="change"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-sm font-medium text-gray-300">
                                        Zmień hasło
                                    </span>
                                </label>
                            </div>
                        @endif

                        @if(!$isEditing || $password === 'change')
                            {{-- Password Generation Option --}}
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" 
                                           wire:model="generate_password" 
                                           id="generate_password"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <label for="generate_password" class="text-sm font-medium text-gray-300">
                                        Wygeneruj bezpieczne hasło automatycznie
                                    </label>
                                </div>

                                @if($generate_password)
                                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-md p-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-blue-800 dark:text-blue-200">
                                                Bezpieczne hasło zostanie wygenerowane automatycznie podczas zapisu
                                            </span>
                                            <button type="button" 
                                                    wire:click="generateSecurePassword"
                                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                                                Podgląd hasła
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    {{-- Manual Password Input --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-300 mb-1">
                                                Hasło *
                                            </label>
                                            <div class="relative">
                                                <input type="password" 
                                                       wire:model.live.debounce.300ms="password" 
                                                       id="password"
                                                       x-ref="passwordInput"
                                                       class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white pr-10">
                                                <button type="button" 
                                                        @click="togglePasswordVisibility('passwordInput')"
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">
                                                Potwierdź hasło *
                                            </label>
                                            <div class="relative">
                                                <input type="password" 
                                                       wire:model.live.debounce.300ms="password_confirmation" 
                                                       id="password_confirmation"
                                                       x-ref="passwordConfirmationInput"
                                                       class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white pr-10">
                                                <button type="button" 
                                                        @click="togglePasswordVisibility('passwordConfirmationInput')"
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            @error('password_confirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Send Credentials Option --}}
                        @if(!$isEditing)
                            <div class="mt-4 flex items-center space-x-3">
                                <input type="checkbox" 
                                       wire:model="send_credentials" 
                                       id="send_credentials"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <label for="send_credentials" class="text-sm font-medium text-gray-300">
                                    Wyślij dane dostępu na email
                                </label>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    (email z hasłem zostanie wysłany do użytkownika)
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- STEP 3: Roles and Permissions --}}
            @if($currentStep === 3)
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-white">Role i uprawnienia</h3>
                    
                    {{-- Role Selection --}}
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="text-md font-medium text-white mb-4">Wybierz role *</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($roles as $role)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 hover:bg-gray-700 transition-colors">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input type="checkbox" 
                                               wire:click="toggleRole('{{ $role->name }}')"
                                               @if(in_array($role->name, $selected_roles)) checked @endif
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 mt-0.5">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-white">
                                                {{ $role->name }}
                                            </div>
                                            @if($role->description)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $role->description }}
                                                </div>
                                            @endif
                                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                {{ $role->permissions->count() }} uprawnień
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('selected_roles') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Permission Preview --}}
                    @if(!empty($selected_roles))
                        <div class="border border-green-200 dark:border-green-700 rounded-lg p-4 bg-green-50 dark:bg-green-900">
                            <h4 class="text-md font-medium text-green-900 dark:text-green-100 mb-3">
                                Uprawnienia z wybranych ról ({{ count($inheritedPermissions) }})
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($inheritedPermissions as $permission)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100">
                                        {{ $permission }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Additional Permissions --}}
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-medium text-white">Dodatkowe uprawnienia</h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Uprawnienia poza rolami
                            </span>
                        </div>
                        
                        @foreach($permissionsByModule as $module => $modulePermissions)
                            <div class="mb-6 last:mb-0">
                                <h5 class="text-sm font-medium text-gray-300 mb-3 capitalize">
                                    {{ $module }}
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                    @foreach($modulePermissions as $permission)
                                        <label class="flex items-center space-x-2 text-sm">
                                            <input type="checkbox" 
                                                   wire:click="togglePermission('{{ $permission->name }}')"
                                                   @if(in_array($permission->name, $custom_permissions)) checked @endif
                                                   @if(in_array($permission->name, $inheritedPermissions)) disabled @endif
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50
                                                          {{ in_array($permission->name, $inheritedPermissions) ? 'opacity-50' : '' }}">
                                            <span class="{{ in_array($permission->name, $inheritedPermissions) ? 'text-gray-400 dark:text-gray-500' : 'text-gray-300' }}">
                                                {{ str_replace($module . '.', '', $permission->name) }}
                                                @if(in_array($permission->name, $inheritedPermissions))
                                                    <span class="text-xs text-green-600">(z roli)</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- All Permissions Summary --}}
                    @if(!empty($allUserPermissions))
                        <div class="border border-blue-200 dark:border-blue-700 rounded-lg p-4 bg-blue-50 dark:bg-blue-900">
                            <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-3">
                                Wszystkie uprawnienia użytkownika ({{ count($allUserPermissions) }})
                            </h4>
                            <div class="text-xs text-blue-700 dark:text-blue-200 space-y-1">
                                @foreach(collect($allUserPermissions)->sort() as $permission)
                                    <span class="inline-block mr-3">{{ $permission }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- STEP 4: Preferences and Summary --}}
            @if($currentStep === 4)
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-white">Preferencje i podsumowanie</h3>
                    
                    {{-- Avatar Upload --}}
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="text-md font-medium text-white mb-4">Avatar użytkownika</h4>
                        
                        <div class="flex items-start space-x-6">
                            {{-- Current Avatar --}}
                            <div class="flex-shrink-0">
                                <div class="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center overflow-hidden">
                                    @if($avatar)
                                        <img src="{{ $avatar->temporaryUrl() }}" class="w-full h-full object-cover" alt="New avatar">
                                    @elseif($existing_avatar)
                                        <img src="{{ Storage::url($existing_avatar) }}" class="w-full h-full object-cover" alt="Current avatar">
                                    @else
                                        <span class="text-2xl font-medium text-gray-500 dark:text-gray-400">
                                            {{ substr($first_name, 0, 1) }}{{ substr($last_name, 0, 1) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Upload Controls --}}
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <label class="cursor-pointer inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                        </svg>
                                        Wybierz plik
                                        <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                                    </label>
                                    
                                    @if($avatar || $existing_avatar)
                                        <button type="button" 
                                                wire:click="removeAvatar"
                                                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-gray-800 hover:bg-red-50">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Usuń avatar
                                        </button>
                                    @endif
                                </div>
                                
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Maksymalny rozmiar: 2MB. Dozwolone formaty: JPG, JPEG, PNG, GIF, WEBP.
                                </div>
                                
                                @error('avatar') <div class="mt-1 text-red-500 text-sm">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- User Preferences --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Language --}}
                        <div>
                            <label for="preferred_language" class="block text-sm font-medium text-gray-300 mb-1">
                                Język interfejsu *
                            </label>
                            <select wire:model="preferred_language" 
                                    id="preferred_language"
                                    class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                @foreach($languageOptions as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('preferred_language') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Timezone --}}
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-300 mb-1">
                                Strefa czasowa *
                            </label>
                            <select wire:model="timezone" 
                                    id="timezone"
                                    class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                @foreach($timezoneOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('timezone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date Format --}}
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-gray-300 mb-1">
                                Format daty *
                            </label>
                            <select wire:model="date_format" 
                                    id="date_format"
                                    class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                @foreach($dateFormatOptions as $value => $example)
                                    <option value="{{ $value }}">{{ $example }}</option>
                                @endforeach
                            </select>
                            @error('date_format') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Notification Settings --}}
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="text-md font-medium text-white mb-4">Ustawienia powiadomień</h4>
                        
                        <div class="space-y-3">
                            @foreach(['email_notifications' => 'Powiadomienia email', 
                                     'sync_notifications' => 'Powiadomienia synchronizacji', 
                                     'stock_alerts' => 'Alerty stanów magazynowych', 
                                     'import_notifications' => 'Powiadomienia importu', 
                                     'browser_notifications' => 'Powiadomienia przeglądarki'] as $setting => $label)
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" 
                                           wire:model="notification_settings.{{ $setting }}"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-sm text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="border border-blue-200 dark:border-blue-700 rounded-lg p-4 bg-blue-50 dark:bg-blue-900">
                        <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-4">Podsumowanie</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Użytkownik:</strong> {{ $first_name }} {{ $last_name }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Email:</strong> {{ $email }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Firma:</strong> {{ $company ?: 'Nie podano' }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Status:</strong> {{ $is_active ? 'Aktywny' : 'Nieaktywny' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Role:</strong> {{ implode(', ', $selected_roles) }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Dodatkowe uprawnienia:</strong> {{ count($custom_permissions) }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Całkowite uprawnienia:</strong> {{ count($allUserPermissions) }}
                                </div>
                                <div class="text-blue-700 dark:text-blue-200">
                                    <strong>Język:</strong> {{ $languageOptions[$preferred_language] ?? $preferred_language }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="flex justify-between pt-6 border-t border-gray-700">
                <div>
                    @if($currentStep > 1)
                        <button type="button" 
                                wire:click="previousStep"
                                class="inline-flex items-center px-4 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Poprzedni
                        </button>
                    @endif
                </div>

                <div class="flex space-x-3">
                    {{-- Load Draft Button (only for new users) --}}
                    @if(!$isEditing && session('user_form_draft'))
                        <button type="button" 
                                wire:click="loadDraft"
                                class="inline-flex items-center px-4 py-2 border border-orange-300 shadow-sm text-sm font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l4-4m0 4V4a3 3 0 00-3-3H7a3 3 0 00-3 3v12"></path>
                            </svg>
                            Załaduj szkic
                        </button>
                    @endif

                    @if($currentStep < $maxSteps)
                        <button type="button" 
                                wire:click="nextStep"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Dalej
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    @else
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            @if($isEditing)
                                Zaktualizuj użytkownika
                            @else
                                Utwórz użytkownika
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Loading State --}}
    <div wire:loading class="fixed inset-0 z-50 bg-black bg-opacity-25 flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-white">
                @if($currentStep < $maxSteps)
                    Sprawdzanie danych...
                @else
                    @if($isEditing)
                        Aktualizowanie użytkownika...
                    @else
                        Tworzenie użytkownika...
                    @endif
                @endif
            </span>
        </div>
    </div>
</div>

{{-- Alpine.js Component --}}
<script>
function userFormManager() {
    return {
        init() {
            console.log('User Form Manager initialized');
            
            // Auto-save every 30 seconds for new users
            @if(!$isEditing)
                setInterval(() => {
                    if (!document.hidden) {
                        @this.call('autoSaveDraft');
                    }
                }, 30000);
            @endif
        },
        
        togglePasswordVisibility(inputRef) {
            const input = this.$refs[inputRef];
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
    }
}

// Listen for password generation event
window.addEventListener('password-generated', event => {
    const password = event.detail.password;
    
    // Show password in modal or alert
    if (confirm(`Wygenerowane hasło: ${password}\n\nKliknij OK aby skopiować do schowka.`)) {
        navigator.clipboard.writeText(password).then(() => {
            alert('Hasło zostało skopiowane do schowka.');
        });
    }
});

// Listen for draft saved event
window.addEventListener('draft-saved', event => {
    // Show temporary saved indicator
    const savedIndicator = document.querySelector('[x-show="draftSaved"]');
    if (savedIndicator) {
        setTimeout(() => {
            @this.set('draftSaved', false);
        }, 2000);
    }
});
</script>

@push('scripts')
<script>
    // Form keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S - Auto-save draft
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            @if(!$isEditing)
                @this.call('autoSaveDraft');
            @endif
        }
        
        // Ctrl+Enter - Submit form (only on last step)
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && @this.currentStep === @this.maxSteps) {
            e.preventDefault();
            @this.call('save');
        }
    });

    // Prevent accidentally leaving page with unsaved changes
    @if(!$isEditing)
        let formChanged = false;
        
        document.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Clear flag when form is submitted
        document.addEventListener('submit', function() {
            formChanged = false;
        });
    @endif
</script>
@endpush