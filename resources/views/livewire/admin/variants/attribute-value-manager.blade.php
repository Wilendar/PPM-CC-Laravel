{{-- AttributeValueManager - FAZA 5 PERFORMANCE OPTIMIZED --}}
@php
    // PERFORMANCE: Get cached data once at start of render
    $attrType = $this->getAttributeType();
    $values = $this->getValues();
    $usageStats = $this->getUsageStats();
    $unusedCount = $this->getUnusedValuesCount();
    $isColor = $this->getIsColorType();
@endphp
<div>
    @teleport('body')
    <div x-data="{ show: @entangle('showModal'), cid: '{{ $this->getId() }}' }"
         x-show="show" x-cloak
         @keydown.escape.window="Livewire.find(cid).call('closeModal')"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="Livewire.find(cid).call('closeModal')"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-5xl w-full max-h-[90vh] border border-gray-700 flex flex-col" @click.stop>

                    {{-- Header with Search/Filter Toolbar --}}
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-white">Zarzadzanie Wartosciami</h3>
                                @if($attrType)
                                    <p class="text-sm text-gray-400 mt-1">
                                        Grupa: <span class="font-semibold text-blue-400">{{ $attrType->name }}</span>
                                        ({{ $attrType->display_type }})
                                        | <span class="text-gray-300">{{ $values->count() }}</span> wartosci
                                        @if($unusedCount > 0)
                                            | <span class="text-yellow-400">{{ $unusedCount }} nieuzywanych</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <button wire:click="openCreateModal" class="btn-enterprise-primary">+ Dodaj Wartosc</button>
                        </div>

                        {{-- Search & Filter Bar --}}
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex-1 min-w-[200px]">
                                <input type="text" wire:model.live.debounce.300ms="search"
                                       class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       placeholder="Szukaj po nazwie lub kodzie...">
                            </div>
                            <select wire:model.live="filterStatus"
                                    class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500">
                                <option value="all">Wszystkie</option>
                                <option value="used">Uzywane</option>
                                <option value="unused">Nieuzywane</option>
                            </select>
                            <select wire:model.live="sortField"
                                    class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:border-blue-500">
                                <option value="position">Pozycja</option>
                                <option value="label">Nazwa</option>
                                <option value="code">Kod</option>
                            </select>
                            <button wire:click="resetFilters" class="btn-enterprise-sm text-gray-400 hover:text-white">Reset</button>

                            {{-- Bulk Actions --}}
                            @if(count($selectedValues) > 0)
                                <button wire:click="deleteSelectedValues"
                                        wire:confirm="Usunac {{ count($selectedValues) }} wybranych wartosci? (tylko nieuzywane zostana usuniete)"
                                        class="btn-enterprise-sm bg-red-500/20 hover:bg-red-500/30 border-red-500/40 text-red-400">
                                    Usun wybrane ({{ count($selectedValues) }})
                                </button>
                            @endif
                            @if($unusedCount > 0)
                                <button wire:click="deleteUnusedValues"
                                        wire:confirm="Usunac wszystkie {{ $unusedCount }} nieuzywanych wartosci?"
                                        class="btn-enterprise-sm bg-orange-500/20 hover:bg-orange-500/30 border-orange-500/40 text-orange-400">
                                    Usun nieuzywane
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Values Table --}}
                    <div class="px-6 py-4 overflow-y-auto flex-1">
                        @if($values->count() > 0)
                            <table class="w-full">
                                <thead class="text-left text-xs text-gray-400 uppercase bg-gray-900/50">
                                    <tr>
                                        <th class="px-3 py-2 w-8">
                                            {{-- PERFORMANCE: wire:model (not .live) - no auto-refresh --}}
                                            <input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll"
                                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                                        </th>
                                        @if($isColor)<th class="px-3 py-2 w-12">Kolor</th>@endif
                                        <th class="px-3 py-2 cursor-pointer hover:text-gray-200" wire:click="sortBy('label')">
                                            Nazwa @if($sortField === 'label')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                                        </th>
                                        <th class="px-3 py-2 cursor-pointer hover:text-gray-200" wire:click="sortBy('code')">
                                            Kod @if($sortField === 'code')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                                        </th>
                                        <th class="px-3 py-2 text-center">Produkty</th>
                                        <th class="px-3 py-2">Sync Status</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2 text-right">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    @foreach($values as $value)
                                        @php
                                            $stats = $usageStats->get($value->id) ?? ['variants_count' => 0, 'products_count' => 0];
                                            $isUnused = $stats['products_count'] === 0;
                                        @endphp
                                        <tr wire:key="val-{{ $value->id }}"
                                            class="hover:bg-gray-700/30 transition-colors {{ $isUnused ? 'opacity-60' : '' }}">
                                            <td class="px-3 py-3">
                                                {{-- PERFORMANCE: wire:model (not .live) - no auto-refresh on each checkbox --}}
                                                <input type="checkbox" wire:model="selectedValues" value="{{ $value->id }}"
                                                       class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                                            </td>
                                            @if($isColor)
                                                <td class="px-3 py-3">
                                                    @if($value->color_hex)
                                                        <div class="w-8 h-8 rounded border-2 border-gray-600" style="background-color: {{ $value->color_hex }}"></div>
                                                    @else
                                                        <div class="w-8 h-8 rounded border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-xs">?</div>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-3 py-3">
                                                <span class="font-medium text-gray-200">{{ $value->label }}</span>
                                                @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        @if($value->auto_prefix_enabled)<span class="text-blue-400">{{ $value->auto_prefix }}-</span>@endif
                                                        SKU
                                                        @if($value->auto_suffix_enabled)<span class="text-green-400">-{{ $value->auto_suffix }}</span>@endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 font-mono text-sm text-gray-400">{{ $value->code }}</td>
                                            <td class="px-3 py-3 text-center">
                                                @if($stats['products_count'] > 0)
                                                    <button wire:click="openProductsModal({{ $value->id }})"
                                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500/30 transition-colors">
                                                        {{ $stats['products_count'] }} prod.
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-500">brak</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($value->prestashopMappings as $mapping)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded {{ $mapping->getStatusBadgeClass() }}">
                                                            {{ $mapping->getStatusIcon() }} {{ $mapping->shop->name ?? '?' }}
                                                        </span>
                                                    @endforeach
                                                    @if($value->prestashopMappings->isEmpty())
                                                        <span class="text-xs text-gray-500">brak mapowania</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                @if($value->is_active)
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-500/20 text-green-400 border border-green-500/30">Active</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-500/20 text-gray-400 border border-gray-500/30">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button wire:click="openSyncModal({{ $value->id }})"
                                                            class="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded transition-colors" title="Sync">🔄</button>
                                                    <button wire:click="openEditModal({{ $value->id }})"
                                                            class="p-1.5 text-gray-400 hover:bg-gray-600 rounded transition-colors" title="Edytuj">✏️</button>
                                                    @if($isUnused)
                                                        <button wire:click="delete({{ $value->id }})" wire:confirm="Usunac {{ $value->label }}?"
                                                                class="p-1.5 text-red-400 hover:bg-red-500/20 rounded transition-colors" title="Usun">🗑️</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-12">
                                <div class="text-5xl mb-4 opacity-50">📝</div>
                                <h4 class="text-lg font-semibold text-gray-300 mb-2">Brak wartosci</h4>
                                <p class="text-gray-400 mb-4">Dodaj pierwsza wartosc dla tej grupy</p>
                                <button wire:click="openCreateModal" class="btn-enterprise-primary">+ Dodaj Pierwsza Wartosc</button>
                            </div>
                        @endif
                    </div>

                    {{-- Edit Form --}}
                    @if($showEditForm)
                        @include('livewire.admin.variants.partials.value-edit-form')
                    @endif

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                        <button wire:click="closeModal" class="btn-enterprise-secondary">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport

    {{-- Products Modal --}}
    @include('livewire.admin.variants.partials.products-modal')

    {{-- Sync Modal --}}
    @include('livewire.admin.variants.partials.sync-modal')

    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-cloak
             class="fixed bottom-4 right-4 bg-green-500/20 border border-green-500/30 text-green-400 px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('message') }}
        </div>
    @endif
</div>
