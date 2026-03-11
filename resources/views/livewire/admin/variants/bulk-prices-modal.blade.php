<div x-data="{ show: @entangle('showModal') }"
     x-show="show"
     class="modal-overlay"
     style="display: none;"
     x-cloak>

    <div class="modal-overlay-bg" @click="show = false"></div>

    <div class="modal-content bulk-prices-modal">
        {{-- Modal Header --}}
        <div class="modal-header">
            <h3 class="text-h3">Masowa aktualizacja cen</h3>
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
                    {{-- Price Groups Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Wybierz grupy cenowe
                            @if(!empty($selectedPriceGroups))
                                <span class="text-blue-400">(Wybrano: {{ count($selectedPriceGroups) }})</span>
                            @endif
                        </label>

                        <div class="mb-3">
                            <label class="flex items-center space-x-2 text-gray-300 cursor-pointer hover:text-blue-400 transition-standard">
                                <input type="checkbox"
                                       wire:click="toggleSelectAllGroups"
                                       {{ count($selectedPriceGroups) === $this->priceGroups->count() ? 'checked' : '' }}
                                       class="form-checkbox">
                                <span class="font-medium">Wszystkie grupy</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            @foreach($this->priceGroups as $group)
                                <label class="flex items-center space-x-2 text-gray-300 cursor-pointer hover:text-blue-400 transition-standard"
                                       wire:key="group-{{ $group->id }}">
                                    <input type="checkbox"
                                           wire:model.live="selectedPriceGroups"
                                           value="{{ $group->id }}"
                                           class="form-checkbox">
                                    <span>{{ $group->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('selectedPriceGroups')
                            <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Change Type Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">Typ zmiany</label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="change-type-option" wire:key="type-set">
                                <input type="radio" wire:model.live="changeType" value="set" class="form-radio">
                                <div>
                                    <span class="font-medium">Ustaw</span>
                                    <p class="text-xs text-gray-400">Ustaw dokładną cenę</p>
                                </div>
                            </label>

                            <label class="change-type-option" wire:key="type-increase">
                                <input type="radio" wire:model.live="changeType" value="increase" class="form-radio">
                                <div>
                                    <span class="font-medium">Zwiększ</span>
                                    <p class="text-xs text-gray-400">Dodaj do aktualnej ceny</p>
                                </div>
                            </label>

                            <label class="change-type-option" wire:key="type-decrease">
                                <input type="radio" wire:model.live="changeType" value="decrease" class="form-radio">
                                <div>
                                    <span class="font-medium">Zmniejsz</span>
                                    <p class="text-xs text-gray-400">Odejmij od aktualnej ceny</p>
                                </div>
                            </label>

                            <label class="change-type-option" wire:key="type-percentage">
                                <input type="radio" wire:model.live="changeType" value="percentage" class="form-radio">
                                <div>
                                    <span class="font-medium">Procent</span>
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
                            Wartość {{ $changeType === 'percentage' ? '(%)' : '(PLN)' }}
                        </label>
                        <input type="number"
                               wire:model.live="amount"
                               step="0.01"
                               min="0"
                               class="form-input w-full"
                               placeholder="0.00">
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
                            Zaktualizujesz <strong class="text-blue-400">{{ $this->previewData['total_updates'] ?? 0 }} cen</strong>
                            w <strong class="text-blue-400">{{ $this->previewData['total_variants'] ?? 0 }} wariantach</strong>
                        </p>
                    </div>

                    <div class="overflow-auto max-h-96 border border-gray-700 rounded-lg">
                        <table class="table-enterprise">
                            <thead class="sticky top-0 bg-gray-800">
                                <tr>
                                    <th>SKU Wariantu</th>
                                    <th>Grupa cenowa</th>
                                    <th>Aktualna cena</th>
                                    <th>Nowa cena</th>
                                    <th>Różnica</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($this->previewData['items'] ?? []) as $item)
                                    <tr wire:key="preview-{{ $item['variant_id'] }}-{{ $item['price_group_id'] }}">
                                        <td>{{ $item['variant_sku'] }}</td>
                                        <td>{{ $item['price_group_name'] }}</td>
                                        <td>{{ number_format($item['current_price'], 2) }} PLN</td>
                                        <td class="font-bold">{{ number_format($item['new_price'], 2) }} PLN</td>
                                        <td class="price-difference-{{ $item['difference_color'] }}">
                                            {{ $item['difference'] >= 0 ? '+' : '' }}{{ number_format($item['difference'], 2) }} PLN
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
