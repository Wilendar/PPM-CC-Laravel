{{-- Entity List - Lewa kolumna (sidebar) --}}
<div class="supplier-panel__entity-list-container">
    {{-- Add Button --}}
    <button wire:click="openCreateModal"
            class="btn-enterprise-primary w-full mb-4">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Dodaj {{ $tabs[$activeTab]['label_singular'] ?? 'podmiot' }}
    </button>

    {{-- Search --}}
    <div class="relative mb-3">
        <input type="text"
               wire:model.live.debounce.300ms="entitySearch"
               placeholder="Szukaj..."
               class="form-input-dark w-full pl-9 text-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        @if($entitySearch)
            <button wire:click="$set('entitySearch', '')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Status Filter --}}
    <div class="mb-4">
        <select wire:model.live="statusFilter" class="form-select-dark w-full text-sm">
            <option value="all">Wszystkie</option>
            <option value="active">Aktywne</option>
            <option value="inactive">Nieaktywne</option>
        </select>
    </div>

    {{-- Entity List --}}
    <div class="supplier-panel__entity-list" wire:loading.class="opacity-50" wire:target="switchTab, entitySearch, statusFilter">
        @forelse($this->entities as $entity)
            <button wire:click="selectEntity({{ $entity->id }})"
                    wire:key="entity-{{ $entity->id }}"
                    class="supplier-panel__entity-item {{ $selectedEntityId === $entity->id ? 'supplier-panel__entity-item--active' : '' }}">
                <div class="flex items-center gap-3">
                    {{-- Logo / Avatar --}}
                    @if($entity->logo_path)
                        <img src="{{ asset('storage/' . $entity->logo_path) }}"
                             alt="{{ $entity->name }}"
                             class="w-9 h-9 object-contain rounded-lg bg-gray-700/50 flex-shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-lg bg-gray-700/50 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-gray-400">{{ strtoupper(mb_substr($entity->name, 0, 2)) }}</span>
                        </div>
                    @endif

                    {{-- Info --}}
                    <div class="flex-1 min-w-0 text-left">
                        <div class="text-sm font-medium text-white truncate">{{ $entity->name }}</div>
                        @if($entity->company)
                            <div class="text-xs text-gray-500 truncate">{{ $entity->company }}</div>
                        @endif
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-500">
                                {{ $entity->products_count ?? 0 }} {{ trans_choice('produktow|produkt|produkty', $entity->products_count ?? 0) }}
                            </span>
                            @if(!$entity->is_active)
                                <span class="supplier-panel__status-dot supplier-panel__status-dot--inactive" title="Nieaktywny"></span>
                            @endif
                        </div>
                    </div>
                </div>
            </button>
        @empty
            <div class="py-8 text-center">
                <svg class="mx-auto h-8 w-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-sm text-gray-500">Brak podmiotow</p>
                @if($entitySearch)
                    <p class="text-xs text-gray-600 mt-1">Sprobuj zmienic kryteria wyszukiwania</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Loading indicator --}}
    <div wire:loading wire:target="selectEntity" class="supplier-panel__loading-bar">
        <div class="supplier-panel__loading-bar-inner"></div>
    </div>
</div>
