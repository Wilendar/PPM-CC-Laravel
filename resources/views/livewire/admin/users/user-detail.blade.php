{{-- Admin User Detail Component Template --}}
<div x-data="userDetailManager()" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center space-x-4">
            {{-- Avatar --}}
            <div class="flex-shrink-0">
                @if($user->avatar)
                    <img class="h-16 w-16 rounded-full object-cover" 
                         src="{{ Storage::url($user->avatar) }}" 
                         alt="{{ $user->full_name }}">
                @else
                    <div class="h-16 w-16 rounded-full bg-gray-600 flex items-center justify-center">
                        <span class="text-xl font-medium text-gray-300">
                            {{ $user->initials }}
                        </span>
                    </div>
                @endif
            </div>
            
            <div>
                <h1 class="text-3xl font-bold text-white">
                    {{ $user->full_name }}
                </h1>
                <div class="flex items-center space-x-3 mt-1">
                    <p class="text-sm text-gray-400">
                        {{ $user->email }}
                    </p>
                    
                    {{-- Status Badges --}}
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                 {{ $user->is_active ? 'bg-green-900/50 text-green-300' : 'bg-red-900/50 text-red-300' }}">
                        <span class="w-2 h-2 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                        {{ $user->is_active ? 'Aktywny' : 'Nieaktywny' }}
                    </span>
                    
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900/50 text-blue-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Zweryfikowany
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-900/50 text-orange-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Niezweryfikowany
                        </span>
                    @endif
                    
                    {{-- Primary Role Badge --}}
                    @if($user->roles->count() > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                     role-{{ strtolower($user->getPrimaryRole()) }}" 
                              style="background-color: var(--role-bg); color: var(--role-color)">
                            {{ $user->getPrimaryRole() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="mt-4 lg:mt-0 flex flex-col sm:flex-row gap-3">
            {{-- Quick Actions --}}
            <div class="flex space-x-2">
                @can('update', $user)
                    <button wire:click="toggleUserStatus"
                            class="inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600">
                        @if($user->is_active)
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                            </svg>
                            Dezaktywuj
                        @else
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Aktywuj
                        @endif
                    </button>
                    
                    <button wire:click="openPasswordResetModal"
                            class="inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Reset hasła
                    </button>
                @endcan
                
                @can('impersonate', App\Models\User::class)
                    @if($user->id !== auth()->id() && !$user->hasRole('Admin'))
                        <button wire:click="impersonateUser"
                                onclick="confirm('Czy na pewno chcesz przejąć tożsamość tego użytkownika?') || event.stopImmediatePropagation()"
                                class="inline-flex items-center px-3 py-2 border border-purple-700 shadow-sm text-sm font-medium rounded-md text-purple-300 bg-purple-900/30 hover:bg-purple-900/50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Przejmij tożsamość
                        </button>
                    @endif
                @endcan
            </div>
            
            <div class="flex space-x-2">
                <a href="{{ route('admin.users.edit', $user) }}" 
                   class="btn-enterprise-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edytuj
                </a>
                
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Wróć do listy
                </a>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - User Information --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Basic Information Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">
                        Informacje podstawowe
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    {{-- First Name --}}
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                Imię
                            </label>
                            @if($editMode['first_name'] ?? false)
                                <div class="flex items-center space-x-2">
                                    <input type="text" 
                                           wire:model="editData.first_name" 
                                           class="flex-1 text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <button wire:click="saveEdit('first_name')" 
                                            class="text-green-600 hover:text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="cancelEdit('first_name')" 
                                            class="text-gray-600 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-white">{{ $user->first_name }}</span>
                                    @can('update', $user)
                                        <button wire:click="startEdit('first_name')" 
                                                class="text-blue-600 hover:text-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Last Name --}}
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                Nazwisko
                            </label>
                            @if($editMode['last_name'] ?? false)
                                <div class="flex items-center space-x-2">
                                    <input type="text" 
                                           wire:model="editData.last_name" 
                                           class="flex-1 text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <button wire:click="saveEdit('last_name')" 
                                            class="text-green-600 hover:text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="cancelEdit('last_name')" 
                                            class="text-gray-600 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-white">{{ $user->last_name }}</span>
                                    @can('update', $user)
                                        <button wire:click="startEdit('last_name')" 
                                                class="text-blue-600 hover:text-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                Telefon
                            </label>
                            @if($editMode['phone'] ?? false)
                                <div class="flex items-center space-x-2">
                                    <input type="tel" 
                                           wire:model="editData.phone" 
                                           class="flex-1 text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <button wire:click="saveEdit('phone')" 
                                            class="text-green-600 hover:text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="cancelEdit('phone')" 
                                            class="text-gray-600 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-white">{{ $user->phone ?: '-' }}</span>
                                    @can('update', $user)
                                        <button wire:click="startEdit('phone')" 
                                                class="text-blue-600 hover:text-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Company --}}
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                Firma
                            </label>
                            @if($editMode['company'] ?? false)
                                <div class="flex items-center space-x-2">
                                    <input type="text" 
                                           wire:model="editData.company" 
                                           class="flex-1 text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <button wire:click="saveEdit('company')" 
                                            class="text-green-600 hover:text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="cancelEdit('company')" 
                                            class="text-gray-600 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-white">{{ $user->company ?: '-' }}</span>
                                    @can('update', $user)
                                        <button wire:click="startEdit('company')" 
                                                class="text-blue-600 hover:text-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Position --}}
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                Stanowisko
                            </label>
                            @if($editMode['position'] ?? false)
                                <div class="flex items-center space-x-2">
                                    <input type="text" 
                                           wire:model="editData.position" 
                                           class="flex-1 text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <button wire:click="saveEdit('position')" 
                                            class="text-green-600 hover:text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="cancelEdit('position')" 
                                            class="text-gray-600 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-white">{{ $user->position ?: '-' }}</span>
                                    @can('update', $user)
                                        <button wire:click="startEdit('position')" 
                                                class="text-blue-600 hover:text-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Information Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">
                        Informacje o koncie
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-400">
                            Email zweryfikowany
                        </span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-white">
                                {{ $user->email_verified_at ? 'Tak' : 'Nie' }}
                            </span>
                            @can('update', $user)
                                <button wire:click="toggleEmailVerified"
                                        class="text-blue-600 hover:text-blue-500 text-xs">
                                    {{ $user->email_verified_at ? 'Oznacz jako niezweryfikowany' : 'Oznacz jako zweryfikowany' }}
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-400">
                            Data rejestracji
                        </span>
                        <span class="text-sm text-white">
                            {{ $user->created_at->format('d.m.Y H:i') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-400">
                            Ostatnie logowanie
                        </span>
                        <span class="text-sm text-white">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d.m.Y H:i') }}
                                <div class="text-xs text-gray-500">
                                    {{ $user->last_login_at->diffForHumans() }}
                                </div>
                            @else
                                Nigdy
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-400">
                            Język interfejsu
                        </span>
                        <span class="text-sm text-white">
                            {{ $user->preferred_language === 'pl' ? 'Polski' : 'English' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-400">
                            Strefa czasowa
                        </span>
                        <span class="text-sm text-white">
                            {{ $user->timezone ?: 'Europe/Warsaw' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Statistics Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">
                        Statystyki
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-4">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-400">
                                Łącznie logowań
                            </dt>
                            <dd class="text-sm text-white">
                                {{ $userStats['total_logins'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-400">
                                Dni od rejestracji
                            </dt>
                            <dd class="text-sm text-white">
                                {{ $userStats['account_age_days'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-400">
                                Aktywne sesje
                            </dt>
                            <dd class="text-sm text-white">
                                {{ $userStats['active_sessions'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-400">
                                Wszystkich uprawnień
                            </dt>
                            <dd class="text-sm text-white">
                                {{ $userStats['total_permissions'] }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Right Column - Roles, Permissions, Activity --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Roles and Permissions Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Role i uprawnienia
                        </h3>
                        <div class="flex items-center space-x-2">
                            <button wire:click="changePermissionGrouping('module')"
                                    class="text-sm px-3 py-1 rounded {{ $permissionGroupBy === 'module' ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:text-gray-800' }}">
                                Moduły
                            </button>
                            <button wire:click="changePermissionGrouping('role')"
                                    class="text-sm px-3 py-1 rounded {{ $permissionGroupBy === 'role' ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:text-gray-800' }}">
                                Role
                            </button>
                            <button wire:click="togglePermissionView"
                                    class="text-sm text-blue-600 hover:text-blue-500">
                                {{ $showAllPermissions ? 'Ukryj szczegóły' : 'Pokaż wszystkie' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4">
                    {{-- User Roles --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-300 mb-3">
                            Przypisane role ({{ $user->roles->count() }})
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @forelse($user->roles as $role)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                             role-{{ strtolower($role->name) }}" 
                                      style="background-color: var(--role-bg); color: var(--role-color)">
                                    {{ $role->name }}
                                    <span class="ml-2 text-xs opacity-75">
                                        ({{ $role->permissions->count() }} uprawnień)
                                    </span>
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">
                                    Brak przypisanych ról
                                </span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Direct Permissions --}}
                    @if(!empty($directPermissions))
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">
                                Dodatkowe uprawnienia ({{ count($directPermissions) }})
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($directPermissions as $permission)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-900/50 text-orange-300">
                                        {{ $permission }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- All Permissions (when expanded) --}}
                    @if($showAllPermissions)
                        <div>
                            <h4 class="text-sm font-medium text-gray-300 mb-3">
                                Wszystkie uprawnienia użytkownika ({{ count($allUserPermissions) }})
                            </h4>
                            
                            @if($permissionGroupBy === 'module')
                                {{-- Group by Module --}}
                                <div class="space-y-4">
                                    @foreach($permissionsByModule as $module => $modulePermissions)
                                        <div class="border border-gray-600 rounded-lg p-4">
                                            <h5 class="text-sm font-medium text-white mb-2 capitalize">
                                                {{ $module }}
                                            </h5>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($modulePermissions as $permissionData)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                                 {{ $permissionData['direct'] ? 'bg-orange-900/50 text-orange-300' : 'bg-blue-900/50 text-blue-300' }}">
                                                        {{ $permissionData['action'] }}
                                                        @if($permissionData['from_role'])
                                                            <span class="ml-1 text-xs opacity-75">({{ $permissionData['from_role'] }})</span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- Group by Role --}}
                                <div class="space-y-4">
                                    @foreach($rolePermissions as $roleName => $permissions)
                                        <div class="border border-gray-600 rounded-lg p-4">
                                            <h5 class="text-sm font-medium text-white mb-2 role-{{ strtolower($roleName) }}" 
                                                style="color: var(--role-color)">
                                                {{ $roleName }} ({{ count($permissions) }} uprawnień)
                                            </h5>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($permissions as $permission)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-900/50 text-blue-300">
                                                        {{ $permission }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if(!empty($directPermissions))
                                        <div class="border border-gray-600 rounded-lg p-4">
                                            <h5 class="text-sm font-medium text-white mb-2">
                                                Dodatkowe uprawnienia ({{ count($directPermissions) }})
                                            </h5>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($directPermissions as $permission)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-900/50 text-orange-300">
                                                        {{ $permission }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Activity Timeline Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Historia aktywności
                        </h3>
                        <div class="flex items-center space-x-2">
                            <select wire:model="activityDays" wire:change="updateActivityFilter($event.target.value, activityType)"
                                    class="text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                <option value="7">Ostatnie 7 dni</option>
                                <option value="30">Ostatnie 30 dni</option>
                                <option value="90">Ostatnie 90 dni</option>
                            </select>
                            <select wire:model="activityType" wire:change="updateActivityFilter(activityDays, $event.target.value)"
                                    class="text-sm rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                <option value="all">Wszystkie</option>
                                <option value="login">Logowania</option>
                                <option value="update">Aktualizacje</option>
                                <option value="export">Eksporty</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4">
                    @if($userActivity->count() > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($userActivity as $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-600" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-gray-800
                                                                 {{ $activity['type'] === 'login' ? 'bg-green-500' : ($activity['type'] === 'update' ? 'bg-blue-500' : 'bg-purple-500') }}">
                                                        @if($activity['type'] === 'login')
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                                            </svg>
                                                        @elseif($activity['type'] === 'update')
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        @else
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-white">
                                                            {{ $activity['description'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-400">
                                                            IP: {{ $activity['ip_address'] }}
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-400">
                                                        <time datetime="{{ $activity['created_at']->toISOString() }}">
                                                            {{ $activity['created_at']->format('d.m.Y H:i') }}
                                                        </time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-white">Brak aktywności</h3>
                            <p class="mt-1 text-sm text-gray-400">
                                Brak aktywności w wybranym okresie.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Login History Card --}}
            <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">
                        Historia logowań
                    </h3>
                </div>
                <div class="px-6 py-4">
                    @if($loginHistory->count() > 0)
                        <div class="space-y-4">
                            @foreach($loginHistory as $login)
                                <div class="flex items-center justify-between p-4 bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            @if($login['device_type'] === 'Mobile')
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a1 1 0 001-1V4a1 1 0 00-1-1H8a1 1 0 00-1 1v16a1 1 0 001 1z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-white flex items-center">
                                                {{ $login['browser'] }}
                                                @if($login['is_current'])
                                                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-900/50 text-green-300">
                                                        Aktualna sesja
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-400">
                                                {{ $login['ip_address'] }} • {{ $login['location'] }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right text-sm text-gray-400">
                                        <div>{{ $login['login_at']->format('d.m.Y H:i') }}</div>
                                        @if($login['logout_at'])
                                            <div class="text-xs">Wylogowano: {{ $login['logout_at']->format('H:i') }}</div>
                                        @else
                                            <div class="text-xs text-green-600">Aktywna</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-white">Brak historii logowań</h3>
                            <p class="mt-1 text-sm text-gray-400">
                                Użytkownik nie logował się jeszcze do systemu.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Password Reset Modal --}}
    @if($showPasswordResetModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePasswordModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-900">
                            <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                Reset hasła użytkownika
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-400">
                                    Wprowadź nowe hasło dla użytkownika: <strong>{{ $user->full_name }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    @if(!$passwordResetSent)
                        <div class="mt-5">
                            <div class="flex items-center space-x-3 mb-4">
                                <input type="password" 
                                       wire:model="newPassword" 
                                       placeholder="Nowe hasło"
                                       class="flex-1 rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                <button type="button" 
                                        wire:click="generatePassword"
                                        class="px-3 py-2 border border-gray-600 rounded-md text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600">
                                    Generuj
                                </button>
                            </div>
                            @error('newPassword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button wire:click="resetPassword" 
                                    type="button" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
                                Zmień hasło
                            </button>
                            <button wire:click="closePasswordModal" 
                                    type="button" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Anuluj
                            </button>
                        </div>
                    @else
                        <div class="mt-5">
                            <div class="rounded-md bg-green-900 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-300">
                                            Hasło zostało pomyślnie zmienione!
                                        </p>
                                        <p class="mt-1 text-sm text-green-300">
                                            Email informacyjny zostanie wysłany do użytkownika.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6">
                            <button wire:click="closePasswordModal" 
                                    type="button" 
                                    class="w-full btn-enterprise-primary sm:text-sm">
                                Zamknij
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading class="fixed inset-0 z-40 bg-black bg-opacity-25 flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-white">Ładowanie...</span>
        </div>
    </div>
</div>

{{-- Alpine.js Component --}}
<script>
function userDetailManager() {
    return {
        init() {
            console.log('User Detail Manager initialized');
        }
    }
}

// Listen for password generation event
window.addEventListener('password-generated', event => {
    const password = event.detail.password;
    
    // Show password in alert with copy option
    if (confirm(`Wygenerowane hasło: ${password}\n\nKliknij OK aby skopiować do schowka.`)) {
        navigator.clipboard.writeText(password).then(() => {
            alert('Hasło zostało skopiowane do schowka.');
        });
    }
});
</script>

@push('scripts')
<script>
    // Auto-refresh data every 5 minutes
    setInterval(() => {
        if (!document.hidden) {
            @this.call('$refresh');
        }
    }, 300000);
</script>
@endpush