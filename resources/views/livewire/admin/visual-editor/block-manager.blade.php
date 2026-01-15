{{-- Block Manager View - ETAP_07f FAZA 9 --}}
<div class="p-6">
    {{-- Header Section with Stats --}}
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-100">Zarzadzanie Blokami</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Konfiguracja blokow dostepnych w edytorze opisow
                </p>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-100">{{ $this->usageStats['total'] }}</div>
                <div class="text-xs text-gray-500">Wszystkich blokow</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-green-400">{{ $this->usageStats['active'] }}</div>
                <div class="text-xs text-gray-500">Aktywnych</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-500">{{ $this->usageStats['inactive'] }}</div>
                <div class="text-xs text-gray-500">Nieaktywnych</div>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                <div class="text-2xl font-bold text-blue-400">{{ $this->usageStats['total_usages'] }}</div>
                <div class="text-xs text-gray-500">Uzyc w opisach</div>
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
                        placeholder="Szukaj blokow..."
                    >
                </div>
            </div>

            {{-- Category Filter --}}
            <div class="w-full lg:w-48">
                <select
                    wire:model.live="categoryFilter"
                    class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Wszystkie kategorie</option>
                    @foreach($this->blockCategories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Active Only Toggle --}}
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="showOnlyActive"
                    class="w-4 h-4 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500"
                >
                <span class="text-sm text-gray-300">Tylko aktywne</span>
            </label>

            {{-- Reset Filters --}}
            @if($search || $categoryFilter || $showOnlyActive)
                <button
                    wire:click="resetFilters"
                    class="px-3 py-2 text-sm text-gray-400 hover:text-white transition flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reset
                </button>
            @endif
        </div>
    </div>

    {{-- Blocks Table --}}
    @if($this->filteredBlocks->count() > 0)
        <div class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-700 bg-gray-900/50">
                        <th class="w-12 px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            {{-- Sort handle column --}}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Blok
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Kategoria
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Typ
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Aktywny
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Uzycia
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody wire:sort="updateSortOrder" class="divide-y divide-gray-700">
                    @foreach($this->filteredBlocks as $block)
                        <tr
                            wire:key="block-{{ $block->id }}"
                            wire:sort:item="{{ $block->id }}"
                            class="hover:bg-gray-700/30 transition-colors group"
                        >
                            {{-- Sort Handle --}}
                            <td class="px-4 py-3">
                                <div wire:sort:handle class="cursor-move text-gray-500 hover:text-gray-300 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                    </svg>
                                </div>
                            </td>

                            {{-- Icon + Name --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-700/50 text-gray-400">
                                        @if($block->icon)
                                            <i class="fa {{ $block->icon }} text-lg"></i>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-200">{{ $block->name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $block->id }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Category Badge --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border {{ $this->getCategoryColor($block->category) }}">
                                    <i class="fa {{ $this->getCategoryIcon($block->category) }} mr-1.5 text-[10px]"></i>
                                    {{ $block->category_label }}
                                </span>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3">
                                <code class="px-2 py-1 text-xs bg-gray-900 text-gray-300 rounded font-mono">
                                    {{ $block->type }}
                                </code>
                            </td>

                            {{-- Active Toggle --}}
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="toggleBlockActive({{ $block->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800
                                        {{ $block->is_active ? 'bg-green-500' : 'bg-gray-600' }}"
                                    role="switch"
                                    aria-checked="{{ $block->is_active ? 'true' : 'false' }}"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                            {{ $block->is_active ? 'translate-x-6' : 'translate-x-1' }}"
                                    ></span>
                                </button>
                            </td>

                            {{-- Usage Count --}}
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs bg-gray-700/50 text-gray-300 rounded">
                                    {{ $this->getBlockUsageCount($block->id) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right" wire:sort:ignore>
                                <button
                                    wire:click="previewBlock({{ $block->id }})"
                                    class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-700 rounded-lg transition"
                                    title="Podglad"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-700 mb-4">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-200 mb-2">Brak blokow</h3>
            <p class="text-gray-400 mb-6 max-w-md mx-auto">
                @if($search || $categoryFilter || $showOnlyActive)
                    Nie znaleziono blokow pasujacych do wybranych filtrow.
                @else
                    Nie zdefiniowano jeszcze zadnych blokow w systemie.
                @endif
            </p>
            @if($search || $categoryFilter || $showOnlyActive)
                <button wire:click="resetFilters" class="btn-enterprise-secondary">
                    Wyczysc filtry
                </button>
            @endif
        </div>
    @endif

    {{-- Preview Modal --}}
    @if($showPreviewModal && $this->selectedBlock)
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
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden border border-gray-700 flex flex-col">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-700/50 text-gray-400">
                                @if($this->selectedBlock->icon)
                                    <i class="fa {{ $this->selectedBlock->icon }} text-lg"></i>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-100">{{ $this->selectedBlock->name }}</h3>
                                <p class="text-sm text-gray-400">Podglad definicji bloku</p>
                            </div>
                        </div>
                        <button wire:click="closePreviewModal" class="text-gray-400 hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 overflow-y-auto flex-1">
                        {{-- Block Info --}}
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Typ</div>
                                <code class="text-sm text-gray-200 font-mono">{{ $this->selectedBlock->type }}</code>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Kategoria</div>
                                <div class="text-sm text-gray-200">{{ $this->selectedBlock->category_label }}</div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Status</div>
                                <div class="text-sm {{ $this->selectedBlock->is_active ? 'text-green-400' : 'text-gray-500' }}">
                                    {{ $this->selectedBlock->is_active ? 'Aktywny' : 'Nieaktywny' }}
                                </div>
                            </div>
                            <div class="bg-gray-900 rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Kolejnosc</div>
                                <div class="text-sm text-gray-200">{{ $this->selectedBlock->sort_order }}</div>
                            </div>
                        </div>

                        {{-- Default Settings --}}
                        @if($this->selectedBlock->default_settings)
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-300 mb-3">Domyslne ustawienia</h4>
                                <pre class="p-4 bg-gray-900 rounded-lg text-xs text-gray-400 overflow-x-auto font-mono">{{ json_encode($this->selectedBlock->default_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        {{-- Schema --}}
                        @if($this->selectedBlock->schema)
                            <div>
                                <h4 class="text-sm font-semibold text-gray-300 mb-3">Schemat (schema)</h4>
                                <pre class="p-4 bg-gray-900 rounded-lg text-xs text-gray-400 overflow-x-auto font-mono max-h-64">{{ json_encode($this->selectedBlock->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end shrink-0">
                        <button wire:click="closePreviewModal" class="btn-enterprise-primary">
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
