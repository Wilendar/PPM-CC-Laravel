{{-- Admin User List Component Template --}}
<div x-data="userListManager()" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">
                Zarządzanie Użytkownikami
            </h1>
            <p class="mt-1 text-sm text-gray-400">
                Zarządzaj użytkownikami, rolami i uprawnieniami w systemie PPM
            </p>
        </div>
        
        <div class="mt-4 lg:mt-0 flex flex-col sm:flex-row gap-3">
            {{-- Quick Stats --}}
            <div class="flex items-center space-x-4 text-sm">
                <span class="text-gray-400">
                    <span class="font-medium">{{ $stats['total'] }}</span> total
                </span>
                <span class="text-emerald-400">
                    <span class="font-medium">{{ $stats['active'] }}</span> aktywnych
                </span>
                <span class="text-red-400">
                    <span class="font-medium">{{ $stats['inactive'] }}</span> nieaktywnych
                </span>
            </div>
            
            {{-- Create User Button --}}
            <a href="{{ route('admin.users.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Dodaj Użytkownika
            </a>
        </div>
    </div>

    {{-- Search and Filters Bar --}}
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Search Input --}}
            <div class="flex-1 max-w-lg">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Szukaj użytkowników (nazwa, email, firma)..."
                           class="block w-full pl-10 pr-3 py-2 border border-gray-600 rounded-md leading-5 bg-gray-700 placeholder-gray-400 focus:outline-none focus:placeholder-gray-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    
                    {{-- Clear search button --}}
                    @if($search)
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button wire:click="$set('search', '')" class="text-gray-400 hover:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-3">
                {{-- Filters Toggle --}}
                <button wire:click="$toggle('showFilters')" 
                        class="inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :class="{ 'bg-blue-900/50 border-blue-500 text-blue-300': @this.showFilters }">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                    </svg>
                    Filtry
                    @if($roleFilter !== 'all' || $companyFilter !== 'all' || $statusFilter !== 'all' || $lastLoginFilter !== 'all')
                        <span class="ml-1 bg-blue-900/50 text-blue-300 text-xs px-2 py-1 rounded-full">●</span>
                    @endif
                </button>

                {{-- Column Settings --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        Kolumny
                    </button>

                    {{-- Column Settings Dropdown --}}
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         @click.outside="open = false"
                         class="absolute right-0 z-50 mt-2 w-56 rounded-md bg-gray-700 shadow-lg ring-1 ring-black ring-opacity-5">
                        
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-medium text-white">Widoczne kolumny</h3>
                                <div class="flex space-x-1">
                                    <button wire:click="showAllColumns" class="text-xs text-blue-600 hover:text-blue-500">Wszystkie</button>
                                    <span class="text-gray-300">|</span>
                                    <button wire:click="hideAllColumns" class="text-xs text-gray-600 hover:text-gray-500">Żadne</button>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                @foreach($visibleColumns as $column => $visible)
                                    @if($column !== 'name' && $column !== 'actions') {{-- Always keep name and actions --}}
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   wire:click="toggleColumn('{{ $column }}')"
                                                   @if($visible) checked @endif
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-300">
                                                {{ ucfirst(str_replace('_', ' ', $column)) }}
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bulk Actions --}}
                @if(count($selectedUsers) > 0)
                    <button wire:click="openBulkModal" 
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Akcje ({{ count($selectedUsers) }})
                    </button>
                @endif
            </div>
        </div>

        {{-- Expanded Filters --}}
        @if($showFilters)
            <div class="mt-6 pt-6 border-t border-gray-600">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Role Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Rola</label>
                        <select wire:model.live="roleFilter" 
                                class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white text-sm">
                            <option value="all">Wszystkie role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Company Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Firma</label>
                        <select wire:model.live="companyFilter" 
                                class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white text-sm">
                            <option value="all">Wszystkie firmy</option>
                            @foreach($companies as $company)
                                <option value="{{ $company }}">{{ $company }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                        <select wire:model.live="statusFilter" 
                                class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white text-sm">
                            <option value="all">Wszystkie statusy</option>
                            <option value="active">Aktywni</option>
                            <option value="inactive">Nieaktywni</option>
                        </select>
                    </div>

                    {{-- Last Login Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Ostatnie logowanie</label>
                        <select wire:model.live="lastLoginFilter" 
                                class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white text-sm">
                            <option value="all">Wszystkie</option>
                            <option value="today">Dziś</option>
                            <option value="week">Ostatni tydzień</option>
                            <option value="month">Ostatni miesiąc</option>
                            <option value="never">Nigdy</option>
                        </select>
                    </div>
                </div>

                {{-- Clear Filters Button --}}
                <div class="mt-4 flex justify-end">
                    <button wire:click="clearFilters" 
                            class="inline-flex items-center px-3 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Wyczyść filtry
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Users Table --}}
    <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700 overflow-hidden">
        @if($users->count() > 0)
            {{-- Table Controls --}}
            <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    {{-- Select All Checkbox --}}
                    <label class="flex items-center">
                        <input type="checkbox" 
                               wire:model.live="selectAll"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-400">
                            @if(count($selectedUsers) > 0)
                                Wybrano {{ count($selectedUsers) }} użytkowników
                            @else
                                Zaznacz wszystkich
                            @endif
                        </span>
                    </label>
                </div>

                <div class="flex items-center space-x-3">
                    {{-- Per Page Selector --}}
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-400">Pokaż:</span>
                        <select wire:model.live="perPage" 
                                class="rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white text-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-600">
                    <thead class="bg-gray-700">
                        <tr>
                            {{-- Select Column --}}
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-8">
                                {{-- Checkbox handled above --}}
                            </th>

                            {{-- Avatar Column --}}
                            @if($visibleColumns['avatar'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-16">
                                    Avatar
                                </th>
                            @endif

                            {{-- Name Column (Always visible) --}}
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <button wire:click="sortBy('first_name')" class="flex items-center space-x-1 hover:text-gray-100">
                                    <span>Nazwa</span>
                                    @if($sortField === 'first_name')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                        @endif
                                    @endif
                                </button>
                            </th>

                            {{-- Email Column --}}
                            @if($visibleColumns['email'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('email')" class="flex items-center space-x-1 hover:text-gray-100">
                                        <span>Email</span>
                                        @if($sortField === 'email')
                                            @if($sortDirection === 'asc')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                            @endif
                                        @endif
                                    </button>
                                </th>
                            @endif

                            {{-- Company Column --}}
                            @if($visibleColumns['company'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('company')" class="flex items-center space-x-1 hover:text-gray-100">
                                        <span>Firma</span>
                                        @if($sortField === 'company')
                                            @if($sortDirection === 'asc')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                            @endif
                                        @endif
                                    </button>
                                </th>
                            @endif

                            {{-- Roles Column --}}
                            @if($visibleColumns['roles'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Role
                                </th>
                            @endif

                            {{-- Status Column --}}
                            @if($visibleColumns['status'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('is_active')" class="flex items-center space-x-1 hover:text-gray-100">
                                        <span>Status</span>
                                        @if($sortField === 'is_active')
                                            @if($sortDirection === 'asc')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                            @endif
                                        @endif
                                    </button>
                                </th>
                            @endif

                            {{-- Last Login Column --}}
                            @if($visibleColumns['last_login'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('last_login_at')" class="flex items-center space-x-1 hover:text-gray-100">
                                        <span>Ostatnie logowanie</span>
                                        @if($sortField === 'last_login_at')
                                            @if($sortDirection === 'asc')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                            @endif
                                        @endif
                                    </button>
                                </th>
                            @endif

                            {{-- Created At Column --}}
                            @if($visibleColumns['created_at'])
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('created_at')" class="flex items-center space-x-1 hover:text-gray-100">
                                        <span>Utworzony</span>
                                        @if($sortField === 'created_at')
                                            @if($sortDirection === 'asc')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path></svg>
                                            @endif
                                        @endif
                                    </button>
                                </th>
                            @endif

                            {{-- Actions Column (Always visible) --}}
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-gray-800 divide-y divide-gray-600">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-700 transition-colors duration-150 ease-in-out">
                                {{-- Select Checkbox --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           wire:model.live="selectedUsers" 
                                           value="{{ $user->id }}"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                </td>

                                {{-- Avatar Column --}}
                                @if($visibleColumns['avatar'])
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            @if($user->avatar)
                                                <img class="h-10 w-10 rounded-full object-cover" 
                                                     src="{{ Storage::url($user->avatar) }}" 
                                                     alt="{{ $user->full_name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-200">
                                                        {{ $user->initials }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endif

                                {{-- Name Column --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-white">
                                                <a href="{{ route('admin.users.show', $user) }}" 
                                                   class="hover:text-blue-400">
                                                    {{ $user->full_name }}
                                                </a>
                                            </div>
                                            @if($user->position)
                                                <div class="text-sm text-gray-400">
                                                    {{ $user->position }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Email Column --}}
                                @if($visibleColumns['email'])
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">{{ $user->email }}</div>
                                        @if($user->email_verified_at)
                                            <div class="text-xs text-emerald-400 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Zweryfikowany
                                            </div>
                                        @else
                                            <div class="text-xs text-amber-400 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                Niezweryfikowany
                                            </div>
                                        @endif
                                    </td>
                                @endif

                                {{-- Company Column --}}
                                @if($visibleColumns['company'])
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">{{ $user->company ?? '-' }}</div>
                                    </td>
                                @endif

                                {{-- Roles Column --}}
                                @if($visibleColumns['roles'])
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($user->roles as $role)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                             role-{{ strtolower($role->name) }}" 
                                                      style="background-color: var(--role-bg); color: var(--role-color)">
                                                    {{ $role->name }}
                                                </span>
                                            @empty
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-300">
                                                    Brak ról
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>
                                @endif

                                {{-- Status Column --}}
                                @if($visibleColumns['status'])
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleUserStatus({{ $user->id }})"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-150
                                                       {{ $user->is_active ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-700' : 'bg-red-900/50 text-red-300 border border-red-700' }}
                                                       hover:opacity-75">
                                            <span class="w-2 h-2 mr-1 rounded-full {{ $user->is_active ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                                            {{ $user->is_active ? 'Aktywny' : 'Nieaktywny' }}
                                        </button>
                                    </td>
                                @endif

                                {{-- Last Login Column --}}
                                @if($visibleColumns['last_login'])
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                        @if($user->last_login_at)
                                            <div>{{ $user->last_login_at->format('d.m.Y H:i') }}</div>
                                            <div class="text-xs text-gray-400">
                                                {{ $user->last_login_at->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-gray-500">Nigdy</span>
                                        @endif
                                    </td>
                                @endif

                                {{-- Created At Column --}}
                                @if($visibleColumns['created_at'])
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        <div>{{ $user->created_at->format('d.m.Y') }}</div>
                                        <div class="text-xs">{{ $user->created_at->diffForHumans() }}</div>
                                    </td>
                                @endif

                                {{-- Actions Column --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        {{-- View User --}}
                                        <a href="{{ route('admin.users.show', $user) }}" 
                                           class="text-blue-400 hover:text-blue-300"
                                           title="Podgląd użytkownika">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>

                                        {{-- Edit User --}}
                                        <a href="{{ route('admin.users.edit', $user) }}" 
                                           class="text-indigo-400 hover:text-indigo-300"
                                           title="Edytuj użytkownika">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>

                                        {{-- Impersonate User --}}
                                        @can('impersonate', App\Models\User::class)
                                            @if($user->id !== auth()->id() && !$user->hasRole('Admin'))
                                                <button wire:click="impersonateUser({{ $user->id }})"
                                                        onclick="confirm('Czy na pewno chcesz przejąć tożsamość tego użytkownika?') || event.stopImmediatePropagation()"
                                                        class="text-purple-400 hover:text-purple-300"
                                                        title="Przejmij tożsamość użytkownika">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endcan

                                        {{-- Delete User --}}
                                        @can('delete', $user)
                                            @if($user->id !== auth()->id())
                                                <button wire:click="deleteUser({{ $user->id }})"
                                                        onclick="confirm('Czy na pewno chcesz usunąć tego użytkownika? Ta akcja jest nieodwracalna.') || event.stopImmediatePropagation()"
                                                        class="text-red-400 hover:text-red-300"
                                                        title="Usuń użytkownika">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-700">
                {{ $users->links() }}
            </div>

        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-white">Brak użytkowników</h3>
                <p class="mt-1 text-sm text-gray-400">
                    @if($search || $roleFilter !== 'all' || $companyFilter !== 'all' || $statusFilter !== 'all' || $lastLoginFilter !== 'all')
                        Nie znaleziono użytkowników spełniających kryteria wyszukiwania.
                    @else
                        Brak użytkowników w systemie.
                    @endif
                </p>
                <div class="mt-6">
                    @if($search || $roleFilter !== 'all' || $companyFilter !== 'all' || $statusFilter !== 'all' || $lastLoginFilter !== 'all')
                        <button wire:click="clearFilters" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Wyczyść filtry
                        </button>
                    @else
                        <a href="{{ route('admin.users.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Dodaj pierwszego użytkownika
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Bulk Actions Modal --}}
    @if($showBulkModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeBulkModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-900">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                Akcje masowe
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-400">
                                    Wybrano {{ count($selectedUsers) }} użytkowników. Wybierz akcję do wykonania:
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 space-y-3">
                        {{-- Bulk Action Selection --}}
                        <div>
                            <select wire:model="bulkAction" 
                                    class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                <option value="">Wybierz akcję...</option>
                                <option value="activate">Aktywuj użytkowników</option>
                                <option value="deactivate">Dezaktywuj użytkowników</option>
                                <option value="assign_role">Przypisz rolę</option>
                                <option value="export">Eksportuj użytkowników</option>
                                <option value="delete">Usuń użytkowników</option>
                            </select>
                        </div>

                        {{-- Role Selection for Bulk Role Assignment --}}
                        @if($bulkAction === 'assign_role')
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">
                                    Wybierz rolę do przypisania:
                                </label>
                                <select wire:model="bulkRoleId" 
                                        class="block w-full rounded-md border-gray-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-700 text-white">
                                    <option value="">Wybierz rolę...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex space-x-3">
                            <button wire:click="executeBulkAction" 
                                    @if(!$bulkAction || ($bulkAction === 'assign_role' && !$bulkRoleId)) disabled @endif
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                Wykonaj
                            </button>
                            <button wire:click="closeBulkModal" 
                                    type="button" 
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-gray-700 border border-gray-600 rounded-md font-semibold text-xs text-gray-300 uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Anuluj
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading States --}}
    <div wire:loading class="fixed inset-0 z-40 bg-black bg-opacity-25 flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-white">Ładowanie...</span>
        </div>
    </div>
</div>

{{-- Alpine.js Component --}}
<script>
function userListManager() {
    return {
        init() {
            console.log('User List Manager initialized');
        }
    }
}
</script>

@push('scripts')
<script>
    // Auto-refresh active users count every 5 minutes
    setInterval(() => {
        if (!document.hidden) {
            @this.call('$refresh');
        }
    }, 300000);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+A - Select all visible users
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            @this.set('selectAll', !@this.selectAll);
        }
        
        // Delete - Delete selected users
        if (e.key === 'Delete' && @this.selectedUsers.length > 0) {
            e.preventDefault();
            if (confirm('Czy na pewno chcesz usunąć wybranych użytkowników?')) {
                @this.set('bulkAction', 'delete');
                @this.call('executeBulkAction');
            }
        }
    });
</script>
@endpush