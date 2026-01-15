{{-- Template Manager View - ETAP_07f FAZA 5 --}}
<div>
    {{-- Header Section with Stats --}}
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-100">Zarzadzanie Szablonami</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Tworzenie i zarzadzanie szablonami opisow produktow
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Import Button --}}
                <button wire:click="openImportModal" class="btn-enterprise-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                {{-- Create Button --}}
                <button wire:click="openCreateModal" class="btn-enterprise-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nowy Szablon
                </button>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-100">{{ $this->usageStats['total'] }}</div>
                <div class="text-xs text-gray-500">Wszystkich</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-green-400">{{ $this->usageStats['global'] }}</div>
                <div class="text-xs text-gray-500">Globalnych</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-blue-400">{{ $this->usageStats['shop_specific'] }}</div>
                <div class="text-xs text-gray-500">Per-sklep</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-sm font-medium text-gray-300 truncate">{{ $this->usageStats['most_used'] }}</div>
                <div class="text-xs text-gray-500">Najpopularniejszy</div>
            </div>
        </div>
    </div>

    {{-- Filters Row --}}
    <div class="mb-6 p-4 bg-gray-800 border border-gray-700 rounded-lg">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            {{-- Search Input --}}
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        placeholder="Szukaj szablonow..."
                    >
                </div>
            </div>

            {{-- Shop Filter --}}
            <div class="w-full lg:w-48">
                <select
                    wire:model.live="shopFilter"
                    class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Wszystkie sklepy</option>
                    <option value="0">Tylko globalne</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category Filter --}}
            <div class="w-full lg:w-48">
                <select
                    wire:model.live="categoryFilter"
                    class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Wszystkie kategorie</option>
                    @foreach($this->templateCategories as $key => $category)
                        <option value="{{ $key }}">{{ $category['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset Filters --}}
            @if($search || $shopFilter !== null || $categoryFilter)
                <button
                    wire:click="$set('search', ''); $set('shopFilter', null); $set('categoryFilter', null)"
                    class="btn-enterprise-ghost text-gray-400 hover:text-white"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reset
                </button>
            @endif
        </div>

        {{-- Active Filters Summary --}}
        @if($search || $shopFilter !== null || $categoryFilter)
            <div class="mt-3 pt-3 border-t border-gray-700 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Aktywne filtry:</span>
                @if($search)
                    <span class="inline-flex items-center px-2 py-1 text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 rounded-full">
                        Szukaj: "{{ $search }}"
                    </span>
                @endif
                @if($shopFilter !== null)
                    <span class="inline-flex items-center px-2 py-1 text-xs bg-purple-500/20 text-purple-400 border border-purple-500/30 rounded-full">
                        Sklep: {{ $shopFilter == 0 ? 'Tylko globalne' : ($this->shops->firstWhere('id', $shopFilter)?->name ?? 'N/A') }}
                    </span>
                @endif
                @if($categoryFilter)
                    <span class="inline-flex items-center px-2 py-1 text-xs bg-green-500/20 text-green-400 border border-green-500/30 rounded-full">
                        Kategoria: {{ $this->templateCategories[$categoryFilter]['label'] ?? $categoryFilter }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Templates Grid --}}
    @if($this->filteredTemplates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($this->filteredTemplates as $template)
                <div
                    wire:key="template-{{ $template->id }}"
                    class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden hover:border-gray-600 transition-colors group"
                >
                    {{-- Thumbnail / Preview --}}
                    <div class="relative h-40 bg-gray-900 overflow-hidden">
                        @if($template->thumbnail_path)
                            <img
                                src="{{ asset('storage/' . $template->thumbnail_path) }}"
                                alt="{{ $template->name }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        @else
                            {{-- Placeholder with blocks preview --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                    </svg>
                                    <span class="block mt-2 text-sm text-gray-600">
                                        {{ $template->block_count }} blokow
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- Quick Actions Overlay --}}
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button
                                wire:click="previewTemplate({{ $template->id }})"
                                class="p-2 bg-gray-800/90 hover:bg-gray-700 text-gray-200 rounded-lg transition"
                                title="Podglad"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button
                                wire:click="duplicateTemplate({{ $template->id }})"
                                class="p-2 bg-gray-800/90 hover:bg-gray-700 text-gray-200 rounded-lg transition"
                                title="Duplikuj"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="p-4">
                        {{-- Title --}}
                        <h3 class="text-lg font-semibold text-gray-100 truncate mb-2">
                            {{ $template->name }}
                        </h3>

                        {{-- Description --}}
                        @if($template->description)
                            <p class="text-sm text-gray-400 line-clamp-2 mb-3">{{ $template->description }}</p>
                        @endif

                        {{-- Badges --}}
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            {{-- Category Badge --}}
                            @if($template->category)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30 rounded-full">
                                    <i class="fa {{ $this->getCategoryIcon($template->category) }} mr-1 text-[10px]"></i>
                                    {{ $this->getCategoryLabel($template->category) }}
                                </span>
                            @endif

                            {{-- Shop Badge --}}
                            @if($template->shop_id)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-500/20 text-blue-400 border border-blue-500/30 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    {{ $template->shop?->name ?? 'Sklep' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Globalny
                                </span>
                            @endif
                        </div>

                        {{-- Meta Info --}}
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center gap-3">
                                {{-- Usage Count --}}
                                <span class="flex items-center" title="Uzycia">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    {{ $template->getUsageCount() }}
                                </span>

                                {{-- Blocks Count --}}
                                <span class="flex items-center" title="Bloki">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                    {{ $template->block_count }}
                                </span>
                            </div>

                            {{-- Created Info --}}
                            <span title="{{ $template->created_at?->format('Y-m-d H:i') }}">
                                {{ $template->created_at?->diffForHumans() ?? 'n/a' }}
                            </span>
                        </div>

                        {{-- Creator Info --}}
                        @if($template->creator)
                            <div class="mt-2 pt-2 border-t border-gray-700/50 flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $template->creator->name ?? 'Nieznany' }}
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer Actions --}}
                    <div class="px-4 pb-4 pt-0 flex items-center justify-between">
                        <button
                            wire:click="exportTemplate({{ $template->id }})"
                            class="text-xs text-gray-400 hover:text-gray-200 flex items-center transition"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Eksportuj
                        </button>

                        <button
                            wire:click="deleteTemplate({{ $template->id }})"
                            wire:confirm="Czy na pewno chcesz usunac ten szablon?"
                            class="text-xs text-red-400 hover:text-red-300 flex items-center transition"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Usun
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($this->filteredTemplates->hasPages())
            <div class="mt-6">
                {{ $this->filteredTemplates->links() }}
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-700 mb-4">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-200 mb-2">Brak szablonow</h3>
            <p class="text-gray-400 mb-6 max-w-md mx-auto">
                @if($search || $shopFilter !== null || $categoryFilter)
                    Nie znaleziono szablonow pasujacych do wybranych filtrow.
                @else
                    Nie masz jeszcze zadnych szablonow opisow produktow. Utworz pierwszy szablon, aby przyspieszyc tworzenie opisow.
                @endif
            </p>
            @if($search || $shopFilter !== null || $categoryFilter)
                <button
                    wire:click="$set('search', ''); $set('shopFilter', null); $set('categoryFilter', null)"
                    class="btn-enterprise-secondary"
                >
                    Wyczysc filtry
                </button>
            @else
                <button wire:click="openCreateModal" class="btn-enterprise-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Utworz Pierwszy Szablon
                </button>
            @endif
        </div>
    @endif

    {{-- Create Template Modal --}}
    @if($showCreateModal)
        @teleport('body')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            @keydown.escape.window="$wire.closeCreateModal()"
            class="fixed inset-0 z-50"
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeCreateModal"></div>
            <div class="relative z-10 h-full flex items-center justify-center p-4">
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full border border-gray-700">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-100">Nowy Szablon</h3>
                        <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa szablonu *</label>
                            <input
                                type="text"
                                wire:model="formName"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="np. Szablon motocykl cross"
                            >
                            @error('formName') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                            <textarea
                                wire:model="formDescription"
                                rows="3"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Opcjonalny opis szablonu..."
                            ></textarea>
                        </div>

                        {{-- Shop --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Sklep</label>
                            <select
                                wire:model="formShopId"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Globalny (wszystkie sklepy)</option>
                                @foreach($this->shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Globalne szablony sa dostepne dla wszystkich sklepow</p>
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Kategoria</label>
                            <select
                                wire:model="formCategory"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                                @foreach($this->templateCategories as $key => $category)
                                    <option value="{{ $key }}">{{ $category['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeCreateModal" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button wire:click="createTemplate" class="btn-enterprise-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Utworz Szablon
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Preview Modal --}}
    @if($showPreviewModal && $this->selectedTemplate)
        @teleport('body')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            @keydown.escape.window="$wire.closePreviewModal()"
            class="fixed inset-0 z-50"
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closePreviewModal"></div>
            <div class="relative z-10 h-full flex items-center justify-center p-4">
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden border border-gray-700 flex flex-col">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between shrink-0">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-100">{{ $this->selectedTemplate->name }}</h3>
                            <p class="text-sm text-gray-400">Podglad szablonu</p>
                        </div>
                        <button wire:click="closePreviewModal" class="text-gray-400 hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 overflow-y-auto flex-1">
                        {{-- Template Info --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Kategoria</div>
                                <div class="text-sm text-gray-200">{{ $this->getCategoryLabel($this->selectedTemplate->category ?? 'other') }}</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Sklep</div>
                                <div class="text-sm text-gray-200">{{ $this->selectedTemplate->shop?->name ?? 'Globalny' }}</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Liczba blokow</div>
                                <div class="text-sm text-gray-200">{{ $this->selectedTemplate->block_count }}</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Uzycia</div>
                                <div class="text-sm text-gray-200">{{ $this->selectedTemplate->getUsageCount() }}</div>
                            </div>
                        </div>

                        {{-- Blocks List --}}
                        <h4 class="text-sm font-semibold text-gray-300 mb-3">Struktura blokow</h4>
                        <div class="space-y-2">
                            @forelse($this->selectedTemplate->blocks as $index => $block)
                                <div class="flex items-center gap-3 p-3 bg-gray-900 rounded-lg">
                                    <span class="flex items-center justify-center w-6 h-6 rounded bg-gray-700 text-xs text-gray-400">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-200">{{ $block['type'] ?? 'Unknown' }}</div>
                                        @if(!empty($block['content']['title'] ?? $block['content']['text'] ?? null))
                                            <div class="text-xs text-gray-500 truncate">
                                                {{ \Illuminate\Support\Str::limit($block['content']['title'] ?? $block['content']['text'] ?? '', 60) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 text-gray-500">
                                    Szablon nie zawiera jeszcze zadnych blokow
                                </div>
                            @endforelse
                        </div>

                        {{-- Description --}}
                        @if($this->selectedTemplate->description)
                            <div class="mt-6">
                                <h4 class="text-sm font-semibold text-gray-300 mb-2">Opis</h4>
                                <p class="text-sm text-gray-400">{{ $this->selectedTemplate->description }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-between shrink-0">
                        <button
                            wire:click="exportTemplate({{ $this->selectedTemplate->id }})"
                            class="btn-enterprise-secondary"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Eksportuj
                        </button>
                        <div class="flex gap-3">
                            <button
                                wire:click="duplicateTemplate({{ $this->selectedTemplate->id }})"
                                class="btn-enterprise-secondary"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Duplikuj
                            </button>
                            <button wire:click="closePreviewModal" class="btn-enterprise-primary">
                                Zamknij
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Import Modal --}}
    @if($showImportModal)
        @teleport('body')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            @keydown.escape.window="$wire.closeImportModal()"
            class="fixed inset-0 z-50"
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeImportModal"></div>
            <div class="relative z-10 h-full flex items-center justify-center p-4">
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full border border-gray-700">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-100">Import Szablonu</h3>
                        <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-4">
                        <p class="text-sm text-gray-400 mb-4">
                            Wklej dane JSON szablonu wyeksportowanego wczesniej.
                        </p>

                        {{-- JSON Input --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Dane JSON *</label>
                            <textarea
                                wire:model="importJson"
                                rows="10"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 font-mono text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder='{"name": "...", "blocks": [...]}'
                            ></textarea>
                            @error('importJson') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Target Shop --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Przypisz do sklepu</label>
                            <select
                                wire:model="formShopId"
                                class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Globalny (wszystkie sklepy)</option>
                                @foreach($this->shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeImportModal" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button wire:click="importTemplate" class="btn-enterprise-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Importuj
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>

@script
<script>
    // Handle JSON export download
    $wire.on('download-json', ({ filename, content }) => {
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
</script>
@endscript
