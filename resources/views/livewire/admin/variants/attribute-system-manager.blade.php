<div class="enterprise-card">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-h2">System Atrybutow</h2>
            <p class="text-gray-400 text-sm mt-1">Zarzadzaj typami atrybutow z synchronizacja PrestaShop</p>
        </div>
        <button wire:click="openCreateModal" class="btn-enterprise-primary">
            ➕ Dodaj Grupe
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="search-filter-bar mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Szukaj</label>
                <input type="text"
                       wire:model.live.debounce.300ms="searchQuery"
                       class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30"
                       placeholder="Nazwa lub kod...">
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30">
                    <option value="all">Wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="inactive">Nieaktywne</option>
                </select>
            </div>

            {{-- Sync Filter (Future Enhancement) --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Sync PrestaShop</label>
                <select wire:model.live="syncFilter"
                        class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30">
                    <option value="all">Wszystkie</option>
                    <option value="synced">Zsynchronizowane</option>
                    <option value="pending">Oczekujace</option>
                    <option value="missing">Brak w PS</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Attribute Types Grid (3 cols desktop, 2 tablet, 1 mobile) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->attributeTypes as $type)
            <div wire:key="attr-type-{{ $type->id }}"
                 class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-mpp-orange transition-colors">

                {{-- Type Header --}}
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-gray-200">{{ $type->name }}</h3>
                        <p class="text-xs text-gray-400 mt-1">Code: <span class="font-mono">{{ $type->code }}</span></p>
                    </div>
                    @if($type->is_active)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                            ● Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-500/20 text-gray-400 border border-gray-500/30">
                            ○ Inactive
                        </span>
                    @endif
                </div>

                {{-- PrestaShop Sync Status --}}
                <div class="mb-3">
                    <p class="text-xs text-gray-400 mb-1">PrestaShop Sync:</p>
                    <div class="flex flex-wrap gap-1">
                        @php
                            $syncStatuses = $this->getSyncStatusForType($type->id);
                        @endphp
                        @forelse($syncStatuses as $shopId => $status)
                            <span wire:key="sync-badge-{{ $type->id }}-{{ $shopId }}"
                                  class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full
                                @if($status['status'] === 'synced') sync-badge-synced
                                @elseif($status['status'] === 'pending') sync-badge-pending
                                @else sync-badge-missing
                                @endif">
                                @if($status['status'] === 'synced') ✅
                                @elseif($status['status'] === 'pending') ⚠️
                                @else ❌
                                @endif
                                {{ $status['shop_name'] }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">Brak sklepow PS</span>
                        @endforelse
                    </div>
                    @if(!empty($syncStatuses))
                        <button wire:click="openSyncModal({{ $type->id }})"
                                class="text-xs text-mpp-orange hover:text-mpp-orange-dark mt-1">
                            Szczegoly sync →
                        </button>
                    @endif
                </div>

                {{-- Type Stats --}}
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Wartosci:</span>
                        <span class="text-blue-400 font-medium">{{ $type->values_count }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Produktow:</span>
                        <span class="text-purple-400 font-medium">{{ $this->getProductsCountForType($type->id) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Display:</span>
                        <span class="text-gray-300">{{ ucfirst($type->display_type) }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button wire:click="openEditModal({{ $type->id }})"
                            class="btn-enterprise-sm flex-1">
                        ⚙️ Edytuj
                    </button>
                    <button wire:click="manageValues({{ $type->id }})"
                            class="btn-enterprise-sm flex-1 bg-mpp-orange/20 hover:bg-mpp-orange/30 border-mpp-orange/40">
                        📝 Wartości
                    </button>
                    <button wire:click="delete({{ $type->id }})"
                            wire:confirm="Czy na pewno chcesz usunac te grupe atrybutow?"
                            class="btn-enterprise-sm bg-red-500/20 hover:bg-red-500/30 border-red-500/40">
                        🗑️
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-6xl mb-4 opacity-50">📦</div>
                <h3 class="text-xl font-semibold text-gray-300 mb-2">
                    Brak grup atrybutow
                </h3>
                <p class="text-gray-400 mb-4">
                    Utworz pierwsza grupe atrybutow dla wariantow
                </p>
                <button wire:click="openCreateModal" class="btn-enterprise-primary">
                    ➕ Dodaj Pierwsza Grupe
                </button>
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Modal --}}
    @teleport('body')
    <div x-data="{
            show: @entangle('showModal'),
            componentId: '{{ $this->getId() }}'
         }"
         x-show="show"
         x-cloak
         @keydown.escape.window="Livewire.find(componentId).call('closeModal')"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm z-0"
             @click="Livewire.find(componentId).call('closeModal')"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] border border-gray-700 flex flex-col" @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-xl font-semibold text-white">
                        {{ $editingTypeId ? 'Edytuj Grupe Atrybutow' : 'Nowa Grupa Atrybutow' }}
                    </h3>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4 overflow-y-auto flex-1">

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa *</label>
                        <input type="text"
                               wire:model.live.debounce.300ms="formData.name"
                               class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30 transition-colors"
                               placeholder="np. Kolor, Rozmiar, Material">
                        @error('formData.name')
                            <span class="text-red-400 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Code --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kod *</label>
                        <input type="text"
                               wire:model.live.debounce.300ms="formData.code"
                               class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30 transition-colors font-mono"
                               placeholder="np. color, size, material"
                               pattern="[a-z_]+">
                        <p class="text-xs text-gray-400 mt-1">
                            Tylko male litery i podkreslenia (a-z, _)
                        </p>
                        @error('formData.code')
                            <span class="text-red-400 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Display Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Typ Wyswietlania *</label>
                        <select wire:model.live="formData.display_type"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30 transition-colors">
                            <option value="dropdown">Lista rozwijana (Dropdown)</option>
                            <option value="radio">Radio buttons</option>
                            <option value="color">Probnik koloru (Color)</option>
                            <option value="button">Przyciski (Buttons)</option>
                        </select>
                    </div>

                    {{-- Position --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kolejnosc</label>
                        <input type="number"
                               wire:model.live="formData.position"
                               class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-mpp-orange focus:ring-1 focus:ring-mpp-orange/30 transition-colors"
                               min="0">
                    </div>

                    {{-- Is Active --}}
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="formData.is_active"
                               class="rounded border-gray-600 bg-gray-700 text-mpp-orange focus:ring-mpp-orange/30 focus:ring-offset-gray-800">
                        <span class="ml-2 text-sm text-gray-300">Aktywna</span>
                    </label>

                    @if($errors->has('save'))
                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm">
                            {{ $errors->first('save') }}
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                    <button @click="show = false" class="btn-enterprise-secondary">
                        ❌ Anuluj
                    </button>
                    <button @click="$wire.save()"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="btn-enterprise-primary">
                        <span wire:loading.remove wire:target="save">💾 Zapisz</span>
                        <span wire:loading wire:target="save">Zapisywanie...</span>
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>
    @endteleport

    {{-- Products Using Modal --}}
    @teleport('body')
    <div x-data="{
            show: @entangle('showProductsModal'),
            componentId: '{{ $this->getId() }}'
         }"
         x-show="show"
         x-cloak
         @keydown.escape.window="Livewire.find(componentId).call('closeProductsModal')"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm z-0"
             @click="Livewire.find(componentId).call('closeProductsModal')"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] border border-gray-700 flex flex-col" @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-xl font-semibold text-white">
                        Produkty uzywajace tej grupy atrybutow
                    </h3>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    @if($this->productsUsingType->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->productsUsingType as $product)
                                <div wire:key="product-using-{{ $product->id }}"
                                     class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-700">
                                    <div>
                                        <p class="font-mono text-sm text-blue-400">{{ $product->sku }}</p>
                                        <p class="text-gray-300">{{ $product->name }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        {{ $product->variants_count ?? 0 }} wariantow
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 py-8">
                            Brak produktow uzywajacych tej grupy atrybutow
                        </p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                    <button @click="show = false" class="btn-enterprise-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>
    @endteleport

    {{-- Sync Status Modal --}}
    @teleport('body')
    <div x-data="{
            show: @entangle('showSyncModal'),
            componentId: '{{ $this->getId() }}'
         }"
         x-show="show"
         x-cloak
         @keydown.escape.window="Livewire.find(componentId).call('closeSyncModal')"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm z-0"
             @click="Livewire.find(componentId).call('closeSyncModal')"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] border border-gray-700 flex flex-col" @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-xl font-semibold text-white">
                        PrestaShop Sync Status
                    </h3>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    @if($selectedTypeIdForSync)
                        @php
                            $syncStatuses = $this->getSyncStatusForType($selectedTypeIdForSync);
                        @endphp

                        <div class="space-y-3">
                            @forelse($syncStatuses as $shopId => $status)
                                <div wire:key="sync-detail-{{ $shopId }}" class="sync-status-detail">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-gray-200">{{ $status['shop_name'] }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                            @if($status['status'] === 'synced') sync-badge-synced
                                            @elseif($status['status'] === 'pending') sync-badge-pending
                                            @else sync-badge-missing
                                            @endif">
                                            @if($status['status'] === 'synced') ✅ Synced
                                            @elseif($status['status'] === 'pending') ⚠️ Pending
                                            @else ❌ Missing
                                            @endif
                                        </span>
                                    </div>

                                    <div class="text-sm space-y-1">
                                        @if($status['ps_id'])
                                            <p class="text-gray-400">PrestaShop ID: <span class="text-blue-400">{{ $status['ps_id'] }}</span></p>
                                        @endif
                                        @if($status['last_sync'])
                                            <p class="text-gray-400">Last sync: <span class="text-gray-300">{{ $status['last_sync'] }}</span></p>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex gap-2">
                                        @if($status['status'] === 'synced')
                                            <button @click="$wire.syncToShop({{ $selectedTypeIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/40 text-blue-400 rounded">
                                                🔄 Re-sync
                                            </button>
                                        @elseif($status['status'] === 'pending')
                                            <button @click="$wire.syncToShop({{ $selectedTypeIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-yellow-500/20 hover:bg-yellow-500/30 border border-yellow-500/40 text-yellow-400 rounded">
                                                ⚡ Force Sync
                                            </button>
                                        @else
                                            <button @click="$wire.syncToShop({{ $selectedTypeIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-green-500/20 hover:bg-green-500/30 border border-green-500/40 text-green-400 rounded">
                                                ➕ Create in PS
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-400 py-8">
                                    Brak sklepow PrestaShop
                                </p>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                    <button @click="show = false" class="btn-enterprise-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>
    @endteleport

    {{-- Embed AttributeValueManager (hidden until triggered) --}}
    <livewire:admin.variants.attribute-value-manager />

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             x-cloak
             class="fixed bottom-4 right-4 bg-green-500/20 border border-green-500/30 text-green-400 px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             x-cloak
             class="fixed bottom-4 right-4 bg-red-500/20 border border-red-500/30 text-red-400 px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif
</div>
