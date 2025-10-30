<div x-data="{ show: @entangle('showModal') }"
     x-show="show"
     class="modal-overlay"
     style="display: none;"
     x-cloak>

    <div class="modal-overlay-bg" @click="show = false"></div>

    <div class="modal-content bulk-stock-modal">
        {{-- Modal Header --}}
        <div class="modal-header">
            <h3 class="text-h3">Masowa aktualizacja stanów</h3>
            <button type="button" @click="show = false" class="modal-close-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="modal-body">
            @if(!$showPreview)
                {{-- Configuration Form --}}
                <form wire:submit.prevent="calculatePreview">
                    {{-- Warehouse Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Magazyn</label>
                        <select wire:model.live="warehouseId" class="form-select w-full">
                            <option value="">-- Wybierz magazyn --</option>
                            @foreach($this->warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" wire:key="warehouse-{{ $warehouse->id }}">
                                    {{ $warehouse->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouseId')
                            <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Change Type Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">Typ zmiany</label>

                        <div class="grid grid-cols-3 gap-3">
                            <label class="change-type-option" wire:key="type-set">
                                <input type="radio" wire:model.live="changeType" value="set" class="form-radio">
                                <div>
                                    <span class="font-medium">Ustaw</span>
                                    <p class="text-xs text-gray-400">Ustaw dokładny stan</p>
                                </div>
                            </label>

                            <label class="change-type-option" wire:key="type-adjust">
                                <input type="radio" wire:model.live="changeType" value="adjust" class="form-radio">
                                <div>
                                    <span class="font-medium">Dostosuj (+/-)</span>
                                    <p class="text-xs text-gray-400">Dodaj lub odejmij</p>
                                </div>
                            </label>

                            <label class="change-type-option" wire:key="type-percentage">
                                <input type="radio" wire:model.live="changeType" value="percentage" class="form-radio">
                                <div>
                                    <span class="font-medium">Procent (%)</span>
                                    <p class="text-xs text-gray-400">Zmiana procentowa</p>
                                </div>
                            </label>
                        </div>

                        @error('changeType')
                            <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Amount Input --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Wartość {{ $changeType === 'percentage' ? '(%)' : '(szt.)' }}
                        </label>
                        <input type="number"
                               wire:model.live="amount"
                               step="1"
                               class="form-input w-full"
                               placeholder="0">

                        @if($changeType === 'adjust')
                            <p class="text-xs text-gray-400 mt-2">
                                Użyj wartości ujemnych (-10) aby zmniejszyć stan
                            </p>
                        @endif

                        @error('amount')
                            <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="show = false" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button type="submit" class="btn-enterprise-primary">
                            Pokaż podgląd
                        </button>
                    </div>
                </form>

            @else
                {{-- Preview Table --}}
                <div class="mb-6">
                    <div class="bg-blue-900/20 border border-blue-500/30 rounded-lg p-4 mb-4">
                        <p class="text-gray-300">
                            Zaktualizujesz stan <strong class="text-blue-400">{{ $this->previewData['total_variants'] ?? 0 }} wariantów</strong>
                            w magazynie <strong class="text-blue-400">{{ $this->previewData['warehouse_name'] ?? '' }}</strong>
                        </p>
                    </div>

                    <div class="overflow-auto max-h-96 border border-gray-700 rounded-lg">
                        <table class="table-enterprise">
                            <thead class="sticky top-0 bg-gray-800">
                                <tr>
                                    <th>SKU Wariantu</th>
                                    <th>Aktualny stan</th>
                                    <th>Nowy stan</th>
                                    <th>Różnica</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($this->previewData['items'] ?? []) as $item)
                                    <tr wire:key="preview-{{ $item['variant_id'] }}">
                                        <td>{{ $item['variant_sku'] }}</td>
                                        <td>{{ $item['current_stock'] }} szt.</td>
                                        <td class="font-bold">{{ $item['new_stock'] }} szt.</td>
                                        <td class="price-difference-{{ $item['difference_color'] }}">
                                            {{ $item['difference'] >= 0 ? '+' : '' }}{{ $item['difference'] }} szt.
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-between">
                    <button type="button" wire:click="$set('showPreview', false)" class="btn-enterprise-secondary">
                        &larr; Powrót do edycji
                    </button>
                    <div class="flex space-x-3">
                        <button type="button" @click="show = false" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button type="button" wire:click="apply" class="btn-enterprise-success">
                            Zastosuj zmiany
                        </button>
                    </div>
                </div>

                @error('apply')
                    <div class="mt-4 bg-red-900/20 border border-red-500/30 rounded-lg p-4">
                        <p class="text-red-400">{{ $message }}</p>
                    </div>
                @enderror
            @endif
        </div>
    </div>
</div>
