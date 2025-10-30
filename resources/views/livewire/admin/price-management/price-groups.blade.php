<div class="px-6">
    {{-- Header Section --}}
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-h1 font-bold" style="color: #e0ac7e;">
                    Grupy Cenowe
                </h1>
                <p class="text-gray-400 mt-2">
                    Zarządzanie grupami cenowymi systemu PPM-CC-Laravel
                </p>
            </div>

            @can('prices.groups')
            <button wire:click="create" class="btn-enterprise-primary">
                <i class="fas fa-plus mr-2"></i>Nowa Grupa
            </button>
            @endcan
        </div>
    </div>

    {{-- Stats Cards - Enterprise Gradient Pattern (4 cards) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">

        {{-- Card 1 (Blue) - Total Groups --}}
        <div class="group relative">
            <div class="bg-gradient-to-br from-blue-600/30 via-blue-700/20 to-blue-900/30 backdrop-blur-xl rounded-2xl border border-blue-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-blue-500/20 relative overflow-hidden">
                {{-- Background glow --}}
                <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                <div class="relative z-10 flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            {{-- Icon glow --}}
                            <div class="absolute inset-0 rounded-2xl bg-blue-400 opacity-50 blur-lg animate-pulse"></div>
                        </div>
                    </div>
                    <div class="ml-6 flex-1">
                        <p class="text-4xl font-black mb-1" style="color: #60a5fa !important;">
                            {{ number_format($totalGroups) }}
                        </p>
                        <p class="text-sm font-bold tracking-wide uppercase text-white">
                            Łącznie grup
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2 (Green) - Active Groups --}}
        <div class="group relative">
            <div class="bg-gradient-to-br from-green-600/30 via-green-700/20 to-green-900/30 backdrop-blur-xl rounded-2xl border border-green-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-green-500/20 relative overflow-hidden">
                {{-- Background glow --}}
                <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                <div class="relative z-10 flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-400 via-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{-- Icon glow --}}
                            <div class="absolute inset-0 rounded-2xl bg-green-400 opacity-50 blur-lg animate-pulse"></div>
                        </div>
                    </div>
                    <div class="ml-6 flex-1">
                        <p class="text-4xl font-black mb-1" style="color: #34d399 !important;">
                            {{ number_format($activeGroups) }}
                        </p>
                        <p class="text-sm font-bold tracking-wide uppercase text-white">
                            Aktywne grupy
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3 (Purple) - Default Group --}}
        <div class="group relative">
            <div class="bg-gradient-to-br from-purple-600/30 via-purple-700/20 to-purple-900/30 backdrop-blur-xl rounded-2xl border border-purple-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-purple-500/20 relative overflow-hidden">
                {{-- Background glow --}}
                <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                <div class="relative z-10 flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-400 via-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            {{-- Icon glow --}}
                            <div class="absolute inset-0 rounded-2xl bg-purple-400 opacity-50 blur-lg animate-pulse"></div>
                        </div>
                    </div>
                    <div class="ml-6 flex-1">
                        <p class="text-4xl font-black mb-1 truncate" style="color: #a78bfa !important;">
                            {{ $defaultGroup ? $defaultGroup->name : 'Brak' }}
                        </p>
                        <p class="text-sm font-bold tracking-wide uppercase text-white">
                            Grupa domyślna
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4 (Orange/Brown) - Default Margin --}}
        <div class="group relative">
            <div class="bg-gradient-to-br from-orange-600/30 via-orange-700/20 to-orange-900/30 backdrop-blur-xl rounded-2xl border border-orange-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-orange-500/20 relative overflow-hidden">
                {{-- Background glow --}}
                <div class="absolute inset-0 bg-gradient-to-br from-orange-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                <div class="relative z-10 flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                            </svg>
                            {{-- Icon glow --}}
                            <div class="absolute inset-0 rounded-2xl bg-orange-400 opacity-50 blur-lg animate-pulse"></div>
                        </div>
                    </div>
                    <div class="ml-6 flex-1">
                        <p class="text-4xl font-black mb-1" style="color: #fb923c !important;">
                            {{ $defaultGroup ? number_format($defaultGroup->margin_percentage, 1) . '%' : 'N/A' }}
                        </p>
                        <p class="text-sm font-bold tracking-wide uppercase text-white">
                            Domyślna marża
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters and Search --}}
    <div class="enterprise-card mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
            <div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" class="w-full pl-10 pr-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Szukaj grup cenowych..."
                           wire:model.live.debounce.300ms="search">
                </div>
            </div>

            <div>
                <select class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-blue-500"
                        wire:model.live="filterActive">
                    <option value="all">Wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="inactive">Nieaktywne</option>
                </select>
            </div>

            <div>
                <select class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-blue-500"
                        wire:model.live="sortBy">
                    <option value="sort_order">Sortuj według porządku</option>
                    <option value="name">Sortuj według nazwy</option>
                    <option value="margin_percentage">Sortuj według marży</option>
                    <option value="products_count">Sortuj według liczby produktów</option>
                    <option value="created_at">Sortuj według daty</option>
                </select>
            </div>

            @if(!empty($selectedGroups))
            <div>
                <div class="flex gap-2">
                    <select class="flex-1 px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white text-sm focus:ring-2 focus:ring-blue-500"
                            wire:model="bulkAction">
                        <option value="">Wybierz akcję...</option>
                        <option value="activate">Aktywuj wybrane</option>
                        <option value="deactivate">Dezaktywuj wybrane</option>
                    </select>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                            wire:click="executeBulkAction">
                        Wykonaj
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Price Groups Table --}}
    <div class="enterprise-card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700 border-b border-gray-600">
                    <tr>
                        @can('prices.groups')
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-10">
                            <input type="checkbox" class="rounded border-gray-500 text-blue-600 focus:ring-blue-500"
                                   wire:model="selectAll"
                                   @if(count($selectedGroups) === count($priceGroups)) checked @endif>
                        </th>
                        @endcan

                        <th wire:click="sortBy('sort_order')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                            <i class="fas fa-sort-numeric-down mr-2"></i>Porządek
                            @if($sortBy === 'sort_order')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>

                        <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                            <i class="fas fa-tag mr-2"></i>Grupa Cenowa
                            @if($sortBy === 'name')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>

                        <th wire:click="sortBy('margin_percentage')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                            <i class="fas fa-percentage mr-2"></i>Marża
                            @if($sortBy === 'margin_percentage')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>

                        <th wire:click="sortBy('products_count')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white">
                            <i class="fas fa-cube mr-2"></i>Produkty
                            @if($sortBy === 'products_count')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-2"></i>Status
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-tools mr-2"></i>Akcje
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    @forelse($priceGroups as $group)
                    <tr class="hover:bg-gray-750 transition-colors duration-150">
                        @can('prices.groups')
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="rounded border-gray-500 text-blue-600 focus:ring-blue-500"
                                   wire:model="selectedGroups" value="{{ $group->id }}">
                        </td>
                        @endcan

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-gray-200">
                                {{ $group->sort_order }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($group->is_default)
                                    <i class="fas fa-star text-yellow-400 mr-2" title="Grupa domyślna"></i>
                                @endif

                                <div>
                                    <div class="text-sm font-semibold text-white">{{ $group->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $group->code }}</div>
                                    @if($group->description)
                                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($group->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($group->margin_percentage)
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
                                        {{ number_format($group->margin_percentage, 1) }}%
                                    </span>
                                    @php
                                        $examplePrice = $group->calculatePrice(100);
                                    @endphp
                                    @if($examplePrice['net'] > 0)
                                        <span class="text-xs text-gray-400">
                                            (100 → {{ number_format($examplePrice['net'], 2) }} PLN)
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-sm text-gray-500">Brak marży</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-600 text-white">
                                    {{ $group->prices_count }}
                                </span>
                                @if($group->prices_count > 0)
                                    <span class="text-xs text-gray-400">produktów</span>
                                @else
                                    <span class="text-xs text-gray-500">brak cen</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col space-y-1">
                                @if($group->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">
                                        Aktywna
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-gray-300">
                                        Nieaktywna
                                    </span>
                                @endif

                                @if($group->is_default)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-600 text-white">
                                        Domyślna
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @can('prices.groups')
                                <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
                                        wire:click="edit({{ $group->id }})"
                                        title="Edytuj">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan

                                @can('prices.groups')
                                @if($group->canDelete())
                                <button class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
                                        wire:click="confirmDelete({{ $group->id }})"
                                        title="Usuń">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @else
                                <button class="px-3 py-1.5 bg-gray-600 text-gray-400 rounded-lg cursor-not-allowed text-sm" disabled
                                        title="Nie można usunąć - grupa ma przypisane ceny lub jest domyślna">
                                    <i class="fas fa-lock"></i>
                                </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-tags text-gray-500 text-5xl mb-4"></i>
                            <p class="text-gray-400 mb-4">Brak grup cenowych spełniających kryteria wyszukiwania.</p>
                            @can('prices.groups')
                            <button wire:click="create" class="btn-enterprise-primary">
                                <i class="fas fa-plus mr-2"></i>Utwórz pierwszą grupę
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($priceGroups->hasPages())
        <div class="px-6 py-4 border-t border-gray-700">
            {{ $priceGroups->links() }}
        </div>
        @endif
    </div>

    {{-- Create/Edit Form Modal --}}
    @if($showForm)
    <div x-data="{ isOpen: @entangle('showForm') }"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center"
         aria-modal="true"
         role="dialog">

        <!-- Background Overlay -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isOpen = false"
             class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Container -->
        <div class="p-4 text-center sm:p-0 relative z-50">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop
                 class="relative transform rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl bg-gradient-to-br from-gray-800 via-gray-900 to-gray-800 border border-gray-700/50">

                <!-- Modal Header - DARK GRADIENT -->
                <div class="px-6 py-4 border-b border-gray-700/50 bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H4a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H2a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"></path>
                            </svg>
                            {{ $editMode ? 'Edytuj Grupę Cenową' : 'Nowa Grupa Cenowa' }}
                        </h3>
                        <button @click="isOpen = false"
                                class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                            <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body - SCROLLABLE -->
                <div class="px-6 py-6 max-h-[calc(100vh-20rem)] overflow-y-auto">
                    <form wire:submit.prevent="save" id="priceGroupForm">
                        {{-- Row 1: Nazwa grupy (full width) --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Nazwa grupy cenowej <span class="text-red-400">*</span>
                            </label>
                            <input type="text"
                                   wire:model="name"
                                   placeholder="np. Dealer Premium"
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Row 2: Kod grupy (full width z przyciskiem) --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Kod grupy <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       wire:model="code"
                                       placeholder="dealer_premium"
                                       class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <button type="button"
                                        wire:click="generateCode"
                                        title="Generuj kod z nazwy"
                                        class="px-3 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Marża domyślna (%) <span class="text-gray-500">(opcjonalnie)</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="number"
                                           step="0.01"
                                           min="-100"
                                           max="999.99"
                                           wire:model.live="margin_percentage"
                                           placeholder="0.00"
                                           class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    <span class="px-3 py-2 bg-gray-600 text-gray-300 rounded-lg">%</span>
                                </div>
                                @error('margin_percentage')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror

                                @if($margin_percentage)
                                    @php $example = $this->calculateExamplePrice(); @endphp
                                    <p class="mt-1 text-xs text-gray-400">
                                        Przykład: 100 PLN → {{ number_format($example['net'], 2) }} PLN netto
                                        ({{ number_format($example['gross'], 2) }} PLN brutto)
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Porządek sortowania
                                </label>
                                <input type="number"
                                       wire:model="sort_order"
                                       placeholder="1"
                                       min="1"
                                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <p class="mt-1 text-xs text-gray-400">Określa kolejność wyświetlania</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Opis grupy (opcjonalnie)
                            </label>
                            <textarea wire:model="description"
                                      rows="3"
                                      placeholder="Opis grupy cenowej..."
                                      class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox"
                                           wire:model="is_active"
                                           class="w-4 h-4 rounded border-gray-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-gray-900 cursor-pointer">
                                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">
                                        Grupa aktywna
                                    </span>
                                </label>
                            </div>

                            <div>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox"
                                           wire:model="is_default"
                                           class="w-4 h-4 rounded border-gray-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-gray-900 cursor-pointer">
                                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">
                                        Grupa domyślna
                                    </span>
                                </label>
                                <p class="mt-1 text-xs text-gray-400 ml-6">Tylko jedna grupa może być domyślna</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30 flex items-center justify-between">
                    <button @click="isOpen = false"
                            type="button"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200">
                        Anuluj
                    </button>
                    <button type="submit"
                            form="priceGroupForm"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="save">
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"></path>
                            </svg>
                            {{ $editMode ? 'Zaktualizuj Grupę' : 'Utwórz Grupę' }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Zapisywanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($deleteConfirmation)
    <div x-data="{ isOpen: @entangle('deleteConfirmation') }"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center"
         aria-modal="true"
         role="dialog">

        <!-- Background Overlay -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isOpen = false"
             class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Container -->
        <div class="p-4 text-center sm:p-0 relative z-50">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 @click.stop
                 class="relative transform overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg bg-gradient-to-br from-gray-800 via-gray-900 to-gray-800 border border-red-500/30">

                <!-- Modal Header - RED THEME -->
                <div class="px-6 py-4 border-b border-red-500/30 bg-gradient-to-r from-gray-800 via-red-900/20 to-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Potwierdzenie Usunięcia
                        </h3>
                        <button @click="isOpen = false"
                                class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                            <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6">
                    <div class="text-center mb-4">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-900/20 mb-4">
                            <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-2">Czy na pewno chcesz usunąć tę grupę cenową?</h4>
                        <p class="text-sm text-gray-400">
                            Ta operacja jest nieodwracalna i usunie wszystkie powiązane ceny.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30 flex items-center justify-between">
                    <button @click="isOpen = false"
                            type="button"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200">
                        Anuluj
                    </button>
                    <button wire:click="delete"
                            wire:loading.attr="disabled"
                            wire:target="delete"
                            type="button"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-red-600 hover:bg-red-700 transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="delete">
                            <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Usuń Grupę
                        </span>
                        <span wire:loading wire:target="delete" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Usuwanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Flash Messages --}}
    @if(session('message'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Sukces</strong>
                </div>
                <div class="toast-body">
                    {{ session('message') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Błąd</strong>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif
</div>