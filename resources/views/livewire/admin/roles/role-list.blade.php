{{-- Admin Role List Component Template --}}
<div x-data="roleListManager()" class="space-y-6">
    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">
                Zarzadzanie Rolami
            </h1>
            <p class="mt-1 text-sm text-gray-400">
                Zarzadzaj rolami uzytkownikow, uprawnieniami i hierarchia dostepu
            </p>
        </div>

        <div class="mt-4 lg:mt-0 flex flex-col sm:flex-row gap-3">
            {{-- Quick Stats --}}
            <div class="flex items-center space-x-4 text-sm">
                <span class="text-gray-400">
                    <span class="font-medium text-white">{{ count($roles) }}</span> rol
                </span>
                <span class="text-blue-400">
                    <span class="font-medium">{{ $permissions->count() }}</span> uprawnien
                </span>
            </div>

            {{-- Create Role Button --}}
            <button wire:click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Dodaj Role
            </button>
        </div>
    </div>

    {{-- View Mode Toggle & Search --}}
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
                           placeholder="Szukaj rol (nazwa, opis)..."
                           class="block w-full pl-10 pr-3 py-2 border border-gray-600 rounded-md leading-5 bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm text-white">

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
                {{-- View Mode Buttons --}}
                <div class="flex rounded-md shadow-sm">
                    <button wire:click="setViewMode('list')"
                            class="px-3 py-2 text-sm font-medium rounded-l-md border transition-colors
                                   {{ $viewMode === 'list' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-700 text-gray-300 border-gray-600 hover:bg-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                    </button>
                    <button wire:click="setViewMode('matrix')"
                            class="px-3 py-2 text-sm font-medium border-t border-b transition-colors
                                   {{ $viewMode === 'matrix' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-700 text-gray-300 border-gray-600 hover:bg-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>
                    <button wire:click="setViewMode('hierarchy')"
                            class="px-3 py-2 text-sm font-medium rounded-r-md border transition-colors
                                   {{ $viewMode === 'hierarchy' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-700 text-gray-300 border-gray-600 hover:bg-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </button>
                </div>

                {{-- Toggle Usage Stats --}}
                <button wire:click="toggleUsageStats"
                        class="inline-flex items-center px-3 py-2 border shadow-sm text-sm font-medium rounded-md transition-colors
                               {{ $showUsageStats ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-700 text-gray-300 border-gray-600 hover:bg-gray-600' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Statystyki
                </button>

                {{-- Compare Roles Button --}}
                @if(count($compareRoles) >= 2)
                    <button wire:click="openComparisonModal"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Porownaj ({{ count($compareRoles) }})
                    </button>
                @endif
            </div>
        </div>

        {{-- Role Templates Section --}}
        <div class="mt-6 pt-6 border-t border-gray-700">
            <h3 class="text-sm font-medium text-gray-300 mb-3">Szybkie tworzenie z szablonow:</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($roleTemplates as $templateKey => $template)
                    <button wire:click="createFromTemplate('{{ $templateKey }}')"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-600 rounded-full text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 hover:border-gray-500 transition-colors">
                        <svg class="w-4 h-4 mr-2 text-{{ $template['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ $template['name'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Usage Stats Panel --}}
    @if($showUsageStats)
        <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
            <h3 class="text-lg font-medium text-white mb-4">Statystyki uzycia rol</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                @foreach($roleUsageStats as $roleId => $stats)
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full mb-2
                                    bg-{{ $stats['color'] }}-900/30">
                            <span class="text-{{ $stats['color'] }}-400 font-bold">
                                {{ $stats['level'] }}
                            </span>
                        </div>
                        <div class="text-sm font-medium text-white truncate" title="{{ $stats['name'] }}">
                            {{ $stats['name'] }}
                        </div>
                        <div class="mt-2 flex justify-center space-x-3 text-xs">
                            <span class="text-gray-400">
                                <span class="font-medium text-white">{{ $stats['users_count'] }}</span> uzyt.
                            </span>
                            <span class="text-gray-400">
                                <span class="font-medium text-white">{{ $stats['permissions_count'] }}</span> upr.
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Content based on View Mode --}}
    @if($viewMode === 'list')
        {{-- List View --}}
        <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700 overflow-hidden">
            @if(count($roles) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-8">
                                    {{-- Checkbox for comparison --}}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    <button wire:click="sortBy('name')" class="flex items-center space-x-1 hover:text-white">
                                        <span>Nazwa</span>
                                        @if($sortField === 'name')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                @if($sortDirection === 'asc')
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                                @else
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                                @endif
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Guard
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Poziom
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Uzytkownicy
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Uprawnienia
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            @foreach($roles as $role)
                                <tr class="hover:bg-gray-700 transition-colors duration-150">
                                    {{-- Comparison Checkbox --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               wire:click="@if(in_array($role->id, $compareRoles)) removeFromComparison({{ $role->id }}) @else addToComparison({{ $role->id }}) @endif"
                                               @if(in_array($role->id, $compareRoles)) checked @endif
                                               @if(count($compareRoles) >= 4 && !in_array($role->id, $compareRoles)) disabled @endif
                                               class="rounded border-gray-500 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50 disabled:opacity-50">
                                    </td>

                                    {{-- Name & Description --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @php
                                                $roleColor = $role->color ?? 'gray';
                                            @endphp
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full mr-3
                                                         bg-{{ $roleColor }}-900/30">
                                                <span class="text-{{ $roleColor }}-400 font-bold text-sm">
                                                    {{ $role->level ?? 7 }}
                                                </span>
                                            </span>
                                            <div>
                                                <div class="text-sm font-medium text-white flex items-center">
                                                    {{ $role->name }}
                                                    @if($role->is_system ?? false)
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-900/30 text-yellow-400">
                                                            Systemowa
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($role->description)
                                                    <div class="text-sm text-gray-400 truncate max-w-xs" title="{{ $role->description }}">
                                                        {{ $role->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Guard Name --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-300">
                                            {{ $role->guard_name }}
                                        </span>
                                    </td>

                                    {{-- Level --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $level = $role->level ?? 7;
                                            $hierarchyInfo = $roleHierarchy[$level] ?? ['name' => 'User', 'color' => 'gray'];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                     bg-{{ $hierarchyInfo['color'] }}-900/30 text-{{ $hierarchyInfo['color'] }}-400">
                                            {{ $level }} - {{ $hierarchyInfo['name'] }}
                                        </span>
                                    </td>

                                    {{-- Users Count --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            {{ $role->users_count ?? 0 }}
                                        </div>
                                    </td>

                                    {{-- Permissions Count --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                            </svg>
                                            {{ $role->permissions->count() }}
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            {{-- Edit --}}
                                            <button wire:click="openEditModal({{ $role->id }})"
                                                    class="text-indigo-400 hover:text-indigo-300"
                                                    title="Edytuj role">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>

                                            {{-- Delete --}}
                                            @if(!($role->is_system ?? false) && ($role->users_count ?? 0) === 0)
                                                <button wire:click="openDeleteModal({{ $role->id }})"
                                                        class="text-red-400 hover:text-red-300"
                                                        title="Usun role">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="text-gray-600 cursor-not-allowed"
                                                      title="{{ $role->is_system ?? false ? 'Nie mozna usunac roli systemowej' : 'Rola ma przypisanych uzytkownikow' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-white">Brak rol</h3>
                    <p class="mt-1 text-sm text-gray-400">
                        @if($search)
                            Nie znaleziono rol spelniajacych kryteria wyszukiwania.
                        @else
                            Rozpocznij od utworzenia pierwszej roli.
                        @endif
                    </p>
                    <div class="mt-6">
                        @if($search)
                            <button wire:click="$set('search', '')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Wyczysc wyszukiwanie
                            </button>
                        @else
                            <button wire:click="openCreateModal"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Dodaj pierwsza role
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @elseif($viewMode === 'matrix')
        {{-- Matrix View --}}
        <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Macierz uprawnien</h3>
                <p class="text-sm text-gray-400">Kliknij na komorke aby przelaczac uprawnienie</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-700 z-10">
                                Modul / Uprawnienie
                            </th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider whitespace-nowrap">
                                    <div class="flex flex-col items-center">
                                        @php
                                            $roleColor = $role->color ?? 'gray';
                                        @endphp
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mb-1
                                                     bg-{{ $roleColor }}-900/30">
                                            <span class="text-{{ $roleColor }}-400 font-bold text-xs">
                                                {{ $role->level ?? 7 }}
                                            </span>
                                        </span>
                                        <span class="truncate max-w-20" title="{{ $role->name }}">{{ $role->name }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        @foreach($permissionsByModule as $moduleName => $modulePermissions)
                            {{-- Module Header Row --}}
                            <tr class="bg-gray-700/50">
                                <td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 text-sm font-semibold text-white">
                                    {{ ucfirst($moduleName) }}
                                </td>
                            </tr>

                            @foreach($modulePermissions as $permission)
                                <tr class="hover:bg-gray-700/30">
                                    <td class="px-4 py-2 text-sm text-gray-300 sticky left-0 bg-gray-800 z-10 whitespace-nowrap">
                                        {{ str_replace($moduleName . '.', '', $permission->name) }}
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="togglePermissionForRole({{ $role->id }}, '{{ $permission->name }}')"
                                                    class="w-6 h-6 rounded transition-colors
                                                           {{ ($permissionMatrix[$role->id][$permission->name] ?? false)
                                                              ? 'bg-green-500 hover:bg-green-600'
                                                              : 'bg-gray-600 hover:bg-gray-500' }}">
                                                @if($permissionMatrix[$role->id][$permission->name] ?? false)
                                                    <svg class="w-4 h-4 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @endif
                                            </button>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($viewMode === 'hierarchy')
        {{-- Hierarchy View --}}
        <div class="bg-gray-800 shadow-sm rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-medium text-white mb-6">Hierarchia rol (7 poziomow)</h3>

            <div class="space-y-4">
                @foreach($roleHierarchy as $level => $hierarchyInfo)
                    @php
                        $rolesAtLevel = collect($roles)->filter(function($role) use ($level) {
                            return ($role->level ?? 7) === $level;
                        });
                    @endphp

                    <div class="flex items-start">
                        {{-- Level indicator --}}
                        <div class="flex-shrink-0 w-24 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                        bg-{{ $hierarchyInfo['color'] }}-900/30">
                                <span class="text-{{ $hierarchyInfo['color'] }}-400 font-bold text-lg">
                                    {{ $level }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                {{ $hierarchyInfo['name'] }}
                            </div>
                        </div>

                        {{-- Connector line --}}
                        <div class="flex-shrink-0 w-8 flex items-center justify-center">
                            <div class="w-0.5 h-full bg-gray-600 {{ $level < 7 ? '' : 'opacity-0' }}"></div>
                        </div>

                        {{-- Roles at this level --}}
                        <div class="flex-1">
                            @if($rolesAtLevel->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($rolesAtLevel as $role)
                                        <div class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-600 bg-gray-700">
                                            <span class="font-medium text-white">{{ $role->name }}</span>
                                            <span class="ml-2 text-xs text-gray-400">
                                                ({{ $role->users_count ?? 0 }} uzyt.)
                                            </span>
                                            <button wire:click="openEditModal({{ $role->id }})"
                                                    class="ml-2 text-gray-400 hover:text-white">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-gray-500 italic py-2">
                                    Brak rol na tym poziomie
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModals"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="saveRole">
                        <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="mb-4">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                    {{ $showCreateModal ? 'Utworz nowa role' : 'Edytuj role' }}
                                </h3>
                            </div>

                            <div class="space-y-4">
                                {{-- Role Name --}}
                                <div>
                                    <label for="roleName" class="block text-sm font-medium text-gray-300">
                                        Nazwa roli <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           id="roleName"
                                           wire:model="roleName"
                                           class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                           placeholder="np. Content Manager">
                                    @error('roleName')
                                        <span class="text-red-400 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Guard Name --}}
                                <div>
                                    <label for="roleGuardName" class="block text-sm font-medium text-gray-300">
                                        Guard
                                    </label>
                                    <select id="roleGuardName"
                                            wire:model="roleGuardName"
                                            class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="web">web</option>
                                        <option value="api">api</option>
                                    </select>
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label for="roleDescription" class="block text-sm font-medium text-gray-300">
                                        Opis
                                    </label>
                                    <textarea id="roleDescription"
                                              wire:model="roleDescription"
                                              rows="2"
                                              class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                              placeholder="Krotki opis roli i jej przeznaczenia"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    {{-- Level --}}
                                    <div>
                                        <label for="roleLevel" class="block text-sm font-medium text-gray-300">
                                            Poziom hierarchii <span class="text-red-500">*</span>
                                        </label>
                                        <select id="roleLevel"
                                                wire:model="roleLevel"
                                                class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            @foreach($roleHierarchy as $level => $info)
                                                <option value="{{ $level }}">{{ $level }} - {{ $info['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Color --}}
                                    <div>
                                        <label for="roleColor" class="block text-sm font-medium text-gray-300">
                                            Kolor
                                        </label>
                                        <select id="roleColor"
                                                wire:model="roleColor"
                                                class="mt-1 block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="red">Czerwony</option>
                                            <option value="orange">Pomaranczowy</option>
                                            <option value="yellow">Zolty</option>
                                            <option value="green">Zielony</option>
                                            <option value="teal">Morski</option>
                                            <option value="blue">Niebieski</option>
                                            <option value="indigo">Indygo</option>
                                            <option value="purple">Fioletowy</option>
                                            <option value="pink">Rozowy</option>
                                            <option value="gray">Szary</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- System Role Checkbox --}}
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           id="isSystemRole"
                                           wire:model="isSystemRole"
                                           class="h-4 w-4 rounded border-gray-500 text-blue-600 focus:ring-blue-500 bg-gray-700">
                                    <label for="isSystemRole" class="ml-2 block text-sm text-gray-300">
                                        Rola systemowa (nie mozna usunac)
                                    </label>
                                </div>

                                {{-- Permissions Selection --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Uprawnienia
                                    </label>
                                    <div class="max-h-64 overflow-y-auto border border-gray-600 rounded-md p-3 bg-gray-700">
                                        @foreach($permissionsByModule as $moduleName => $modulePermissions)
                                            <div class="mb-4 last:mb-0">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h4 class="text-sm font-semibold text-white">{{ ucfirst($moduleName) }}</h4>
                                                    <button type="button"
                                                            wire:click="$set('selectedPermissions', array_merge($selectedPermissions, {{ json_encode($modulePermissions->pluck('name')->toArray()) }}))"
                                                            class="text-xs text-blue-400 hover:text-blue-300">
                                                        Zaznacz wszystkie
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    @foreach($modulePermissions as $permission)
                                                        <label class="flex items-center space-x-2 text-sm">
                                                            <input type="checkbox"
                                                                   wire:model="selectedPermissions"
                                                                   value="{{ $permission->name }}"
                                                                   class="h-4 w-4 rounded border-gray-500 text-blue-600 focus:ring-blue-500 bg-gray-700">
                                                            <span class="text-gray-300 truncate" title="{{ $permission->name }}">
                                                                {{ str_replace($moduleName . '.', '', $permission->name) }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-2 text-xs text-gray-400">
                                        Wybrano: {{ count($selectedPermissions) }} uprawnien
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $showCreateModal ? 'Utworz' : 'Zapisz zmiany' }}
                            </button>
                            <button type="button"
                                    wire:click="closeModals"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Anuluj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $editingRole)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModals"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                    Usun role
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-400">
                                        Czy na pewno chcesz usunac role <strong class="text-white">{{ $editingRole->name }}</strong>?
                                        Ta akcja jest nieodwracalna.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="deleteRole"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Usun
                        </button>
                        <button wire:click="closeModals"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Role Comparison Modal --}}
    @if($showComparisonModal && count($compareRolesData) >= 2)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModals"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                Porownanie rol
                            </h3>
                            <button wire:click="closeModals" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                            Uprawnienie
                                        </th>
                                        @foreach($compareRolesData as $roleData)
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                @php
                                                    $roleColor = $roleData['role']->color ?? 'gray';
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                             bg-{{ $roleColor }}-900/30 text-{{ $roleColor }}-400">
                                                    {{ $roleData['role']->name }}
                                                </span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-gray-800 divide-y divide-gray-700">
                                    @php
                                        $allPermissions = collect($compareRolesData)->flatMap(function($data) {
                                            return $data['permissions']->keys();
                                        })->unique()->sort();
                                    @endphp

                                    @foreach($allPermissions as $permissionName)
                                        @php
                                            $hasAny = false;
                                            $hasAll = true;
                                            foreach($compareRolesData as $roleData) {
                                                if ($roleData['permissions'][$permissionName] ?? false) {
                                                    $hasAny = true;
                                                } else {
                                                    $hasAll = false;
                                                }
                                            }
                                            $isDifferent = $hasAny && !$hasAll;
                                        @endphp
                                        <tr class="{{ $isDifferent ? 'bg-yellow-900/20' : '' }}">
                                            <td class="px-4 py-2 text-sm text-gray-300 whitespace-nowrap">
                                                {{ $permissionName }}
                                                @if($isDifferent)
                                                    <span class="ml-1 text-yellow-400 text-xs">*</span>
                                                @endif
                                            </td>
                                            @foreach($compareRolesData as $roleData)
                                                <td class="px-4 py-2 text-center">
                                                    @if($roleData['permissions'][$permissionName] ?? false)
                                                        <svg class="w-5 h-5 mx-auto text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-sm text-gray-400">
                            <span class="text-yellow-400">*</span> - Uprawnienie rozni sie miedzy rolami
                        </div>
                    </div>

                    <div class="bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeModals"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:w-auto sm:text-sm">
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading class="fixed inset-0 z-40 bg-black bg-opacity-25 flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-white">Ladowanie...</span>
        </div>
    </div>
</div>

{{-- Alpine.js Component --}}
<script>
function roleListManager() {
    return {
        init() {
            console.log('Role List Manager initialized');
        }
    }
}
</script>
