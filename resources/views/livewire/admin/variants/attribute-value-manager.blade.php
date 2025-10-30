{{-- AttributeValueManager Component - Single Root Element --}}
<div>
    {{-- Main Modal --}}
    @teleport('body')
    <div x-data="{
            show: @entangle('showModal'),
            componentId: '{{ $this->getId() }}'
         }"
     x-show="show"
     x-cloak
     @keydown.escape.window="Livewire.find(componentId).call('closeModal')"
     class="fixed inset-0 z-50">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm z-0"
         @click="Livewire.find(componentId).call('closeModal')"></div>

    <div class="relative z-10 h-full overflow-y-auto">
        {{-- Modal --}}
        <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] border border-gray-700 flex flex-col"
             @click.stop>

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-xl font-semibold text-white">
                        Zarzadzanie Wartosciami
                    </h3>
                    @if($this->attributeType)
                        <p class="text-sm text-gray-400 mt-1">
                            Grupa: <span class="font-semibold text-blue-400">{{ $this->attributeType->name }}</span>
                            ({{ $this->attributeType->display_type }})
                        </p>
                    @endif
                </div>
                <button @click="$wire.openCreateModal()"
                        class="btn-enterprise-primary">
                    ➕ Dodaj Wartosc
                </button>
            </div>

            {{-- Body - Values List --}}
            <div class="px-6 py-4 overflow-y-auto flex-1">
                @if($this->values->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->values as $value)
                            <div wire:key="attr-value-{{ $value->id }}"
                                 class="value-row-enhanced">

                                {{-- Value Info --}}
                                <div class="flex items-center gap-4 flex-1">
                                    {{-- Color Preview (if color type) --}}
                                    @if($this->isColorType && $value->color_hex)
                                        <div class="w-10 h-10 rounded-lg border-2 border-gray-600"
                                             style="background-color: {{ $value->color_hex }}"></div>
                                    @endif

                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-200">{{ $value->label }}</h4>
                                        <p class="text-xs text-gray-400 font-mono">
                                            Code: {{ $value->code }}
                                            @if($value->color_hex)
                                                <span class="ml-2">| Color: {{ $value->color_hex }}</span>
                                            @endif
                                        </p>

                                        {{-- NEW Phase 5: PrestaShop Sync Status Badges --}}
                                        <div class="value-sync-status-row">
                                            @foreach($this->getSyncStatusForValue($value->id) as $shopId => $status)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full
                                                    @if($status['status'] === 'synced') bg-green-500/20 text-green-400 border border-green-500/30
                                                    @elseif($status['status'] === 'pending') bg-yellow-500/20 text-yellow-400 border border-yellow-500/30
                                                    @else bg-red-500/20 text-red-400 border border-red-500/30
                                                    @endif">
                                                    @if($status['status'] === 'synced') ✅
                                                    @elseif($status['status'] === 'pending') ⚠️
                                                    @else ❌
                                                    @endif
                                                    {{ $status['shop_name'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Status Badge --}}
                                    @if($value->is_active)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                                            ● Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-500/20 text-gray-400 border border-gray-500/30">
                                            ○ Inactive
                                        </span>
                                    @endif

                                    {{-- NEW Phase 5: Products Count Badge --}}
                                    <span class="products-count-badge inline-flex items-center px-2 py-1 text-xs font-medium rounded-full">
                                        📦 {{ $this->getProductsCountForValue($value->id) }} produktow
                                    </span>
                                </div>

                                {{-- Actions --}}
                                <div class="sync-actions">
                                    {{-- NEW Phase 5: Products Using Button --}}
                                    <button @click="$wire.openProductsModal({{ $value->id }})"
                                            class="btn-enterprise-sm bg-purple-500/20 hover:bg-purple-500/30 border-purple-500/40 text-purple-400">
                                        📋 Produkty
                                    </button>

                                    {{-- NEW Phase 5: Sync Status Button --}}
                                    <button @click="$wire.openSyncModal({{ $value->id }})"
                                            class="btn-enterprise-sm bg-blue-500/20 hover:bg-blue-500/30 border-blue-500/40 text-blue-400">
                                        🔄 Sync
                                    </button>

                                    {{-- Existing: Edit Button --}}
                                    <button @click="$wire.openEditModal({{ $value->id }})"
                                            class="btn-enterprise-sm">
                                        ⚙️ Edit
                                    </button>

                                    {{-- Existing: Delete Button --}}
                                    <button @click="confirm('Czy na pewno chcesz usunac te wartosc?') && $wire.delete({{ $value->id }})"
                                            class="btn-enterprise-sm bg-red-500/20 hover:bg-red-500/30 border-red-500/40 text-red-400">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4 opacity-50">📝</div>
                        <h4 class="text-xl font-semibold text-gray-300 mb-2">
                            Brak wartosci
                        </h4>
                        <p class="text-gray-400 mb-4">
                            Dodaj pierwsza wartosc dla tej grupy atrybutow
                        </p>
                        <button @click="$wire.openCreateModal()" class="btn-enterprise-primary">
                            ➕ Dodaj Pierwsza Wartosc
                        </button>
                    </div>
                @endif
            </div>

            {{-- Create/Edit Form (Nested within modal) --}}
            @if($showEditForm)
                <div class="px-6 py-4 border-t border-gray-700 bg-gray-900/50">
                    <h4 class="text-lg font-semibold text-white mb-4">
                        {{ $editingValueId ? 'Edytuj Wartosc' : 'Nowa Wartosc' }}
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Code --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Kod *</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="formData.code"
                                   class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors font-mono"
                                   placeholder="np. red, xl, cotton"
                                   pattern="[a-z0-9_-]+">
                            <p class="text-xs text-gray-400 mt-1">
                                Male litery, cyfry, myslniki, podkreslenia
                            </p>
                            @error('formData.code')
                                <span class="text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Label --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Etykieta *</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="formData.label"
                                   class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                   placeholder="np. Czerwony, XL, Bawelna">
                            @error('formData.label')
                                <span class="text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Color Picker (only if color type) - Phase 3 AttributeColorPicker Integration --}}
                        @if($this->isColorType)
                            <div class="col-span-2">
                                <livewire:components.attribute-color-picker
                                    :color="$formData['color_hex']"
                                    label="Kolor Atrybutu"
                                    :required="false"
                                    wire:key="color-picker-{{ $editingValueId ?? 'new' }}"
                                />
                            </div>
                        @endif

                        {{-- Position --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Kolejnosc</label>
                            <input type="number"
                                   wire:model.live="formData.position"
                                   class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                   min="0">
                        </div>
                    </div>

                    {{-- Is Active --}}
                    <div class="mt-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="formData.is_active"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-800">
                            <span class="ml-2 text-sm text-gray-300">Aktywna</span>
                        </label>
                    </div>

                    {{-- Error messages --}}
                    @if($errors->has('save'))
                        <div class="mt-4 bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm">
                            {{ $errors->first('save') }}
                        </div>
                    @endif

                    {{-- Form Actions --}}
                    <div class="mt-4 flex justify-end gap-3">
                        <button @click="$wire.cancelEdit()"
                                class="btn-enterprise-secondary">
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
            @endif

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-700 flex justify-end flex-shrink-0">
                <button @click="show = false; $wire.closeModal()"
                        class="btn-enterprise-secondary">
                    Zamknij
                </button>
            </div>
        </div>
        </div>
    </div>
</div>
@endteleport

    {{-- NEW Phase 5: Products Using Modal --}}
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
                        Produkty uzywajace tej wartosci
                    </h3>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    @if($this->productsUsingValue->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->productsUsingValue as $product)
                                <div wire:key="product-using-value-{{ $product['id'] }}"
                                     class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-700">
                                    <div>
                                        <p class="font-mono text-sm text-blue-400">{{ $product['sku'] }}</p>
                                        <p class="text-gray-300">{{ $product['name'] }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        {{ $product['variants_count'] ?? 0 }} wariantow
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 py-8">
                            Brak produktow uzywajacych tej wartosci
                        </p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                    <button @click="show = false; $wire.closeProductsModal()"
                            class="btn-enterprise-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>
    @endteleport

    {{-- NEW Phase 5: Sync Status Modal --}}
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
                    @if($selectedValueIdForSync)
                        @php
                            $value = \App\Models\AttributeValue::find($selectedValueIdForSync);
                            $syncStatuses = $this->getSyncStatusForValue($selectedValueIdForSync);
                        @endphp

                        <div class="mb-4 p-3 bg-gray-900 rounded-lg border border-gray-700">
                            <p class="text-sm text-gray-400">Wartosc:</p>
                            <p class="font-semibold text-gray-200">{{ $value->label }} ({{ $value->code }})</p>
                            @if($value->color_hex)
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="w-6 h-6 rounded border border-gray-600"
                                         style="background-color: {{ $value->color_hex }}"></div>
                                    <span class="text-xs text-gray-400">{{ $value->color_hex }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @foreach($syncStatuses as $shopId => $status)
                                <div class="bg-gray-900 rounded-lg border border-gray-700 p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-gray-200">{{ $status['shop_name'] }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                            @if($status['status'] === 'synced') bg-green-500/20 text-green-400 border border-green-500/30
                                            @elseif($status['status'] === 'pending') bg-yellow-500/20 text-yellow-400 border border-yellow-500/30
                                            @else bg-red-500/20 text-red-400 border border-red-500/30
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
                                            <p class="text-gray-400">Last sync: <span class="text-gray-300">{{ $status['last_sync']->format('Y-m-d H:i') }}</span></p>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex gap-2">
                                        @if($status['status'] === 'synced')
                                            <button @click="$wire.syncValueToShop({{ $selectedValueIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/40 text-blue-400 rounded">
                                                🔄 Re-sync
                                            </button>
                                        @elseif($status['status'] === 'pending')
                                            <button @click="$wire.syncValueToShop({{ $selectedValueIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-yellow-500/20 hover:bg-yellow-500/30 border border-yellow-500/40 text-yellow-400 rounded">
                                                ⚡ Force Sync
                                            </button>
                                        @else
                                            <button @click="$wire.syncValueToShop({{ $selectedValueIdForSync }}, {{ $shopId }})"
                                                    class="text-xs px-3 py-1 bg-green-500/20 hover:bg-green-500/30 border border-green-500/40 text-green-400 rounded">
                                                ➕ Create in PS
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                    <button @click="show = false; $wire.closeSyncModal()"
                            class="btn-enterprise-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>
    @endteleport

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
</div>  {{-- End AttributeValueManager Component Root --}}
