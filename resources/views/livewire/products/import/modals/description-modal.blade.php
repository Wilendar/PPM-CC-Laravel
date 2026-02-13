{{-- ETAP_06 FAZA 6.5.4: DescriptionModal - Opisy produktu (short/long description) --}}
{{-- Per-shop PrestaShop tabs with inheritance from default --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="description-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 id="description-modal-title" class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Opisy produktu
                        </h3>
                        @if($pendingProduct)
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $pendingProduct->sku }} - {{ $pendingProduct->name ?? '(brak nazwy)' }}
                        </p>
                        @endif
                    </div>
                    <button wire:click="closeModal"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-6">

                    {{-- Skip descriptions option --}}
                    <div class="p-4 rounded-lg transition-colors
                                {{ $skipDescriptions ? 'bg-red-900/30 border border-red-700' : 'bg-gray-700/30' }}">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="skipDescriptions"
                                   class="form-checkbox-dark">
                            <span class="ml-3">
                                <span class="text-white text-sm font-medium">
                                    Publikuj bez opisow
                                </span>
                                <span class="block text-xs text-gray-400 mt-0.5">
                                    Produkt zostanie opublikowany bez opisu krotkiego i dlugiego
                                </span>
                            </span>
                        </label>

                        @if($skipDescriptions)
                        <div class="mt-3 p-2 bg-red-800/30 rounded text-xs text-red-300 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Opisy nie zostana dodane przy publikacji</span>
                        </div>
                        @endif
                    </div>

                    {{-- Tab bar (visible when PS shops are available) --}}
                    @if(!empty($availableShops))
                    <div class="import-desc-tabs">
                        {{-- Default tab --}}
                        <button wire:click="setActiveTab('default')"
                                class="import-desc-tab {{ $activeTab === 'default' ? 'import-desc-tab-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Domyslny
                            @php
                                $defaultFilled = collect([$shortDescription, $longDescription])->filter(fn($v) => !empty(trim($v ?? '')))->count();
                            @endphp
                            <span class="import-desc-tab-badge {{ $defaultFilled > 0 ? 'import-desc-tab-badge-custom' : 'import-desc-tab-badge-empty' }}">
                                {{ $defaultFilled }}/2
                            </span>
                        </button>

                        {{-- PS shop tabs with per-shop label colors --}}
                        @foreach($availableShops as $shop)
                            @php
                                $shopStatus = $this->getShopDescriptionStatus($shop['id']);
                                $shopFilled = $this->getShopFilledCount($shop['id']);
                                $isActive = $activeTab === 'shop_' . $shop['id'];
                                $shopColor = $shop['label_color'] ?? '#06b6d4';
                                // Convert hex to RGB for rgba() usage
                                $r = hexdec(substr($shopColor, 1, 2));
                                $g = hexdec(substr($shopColor, 3, 2));
                                $b = hexdec(substr($shopColor, 5, 2));
                            @endphp
                            <button wire:click="setActiveTab('shop_{{ $shop['id'] }}')"
                                    wire:key="desc-tab-{{ $shop['id'] }}"
                                    class="import-desc-tab {{ $isActive ? 'import-desc-tab-shop-active' : '' }}"
                                    @if($isActive)
                                    style="background-color: rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.15); color: {{ $shopColor }}; border: 1px solid rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.4);"
                                    @endif>
                                <span class="import-desc-tab-dot" style="background-color: {{ $shopColor }};"></span>
                                {{ $shop['name'] }}
                                <span class="import-desc-tab-badge {{ $shopStatus === 'custom' ? 'import-desc-tab-badge-custom' : 'import-desc-tab-badge-inherited' }}">
                                    {{ $shopStatus === 'custom' ? $shopFilled . '/2' : 'Dziedziczy' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                    @endif

                    {{-- Tab content: Default --}}
                    @if($activeTab === 'default')
                        @include('livewire.products.import.modals.partials.description-tab-content', [
                            'tabType' => 'default',
                            'shopId' => null,
                            'shopName' => null,
                            'shortModel' => 'shortDescription',
                            'longModel' => 'longDescription',
                            'shortValue' => $shortDescription,
                            'longValue' => $longDescription,
                            'skipDescriptions' => $skipDescriptions,
                        ])
                    @endif

                    {{-- Tab content: Per-shop --}}
                    @foreach($availableShops as $shop)
                        @if($activeTab === 'shop_' . $shop['id'])
                            @include('livewire.products.import.modals.partials.description-tab-content', [
                                'tabType' => 'shop',
                                'shopId' => $shop['id'],
                                'shopName' => $shop['name'],
                                'shortModel' => 'shopDescriptions.' . $shop['id'] . '.short',
                                'longModel' => 'shopDescriptions.' . $shop['id'] . '.long',
                                'shortValue' => $shopDescriptions[$shop['id']]['short'] ?? '',
                                'longValue' => $shopDescriptions[$shop['id']]['long'] ?? '',
                                'skipDescriptions' => $skipDescriptions,
                            ])
                        @endif
                    @endforeach

                    {{-- Quick actions (default tab only) --}}
                    @if($activeTab === 'default' && !$skipDescriptions && ($shortDescription || $longDescription))
                    <div class="flex items-center gap-3 pt-2 border-t border-gray-700">
                        <button type="button"
                                wire:click="clearDescriptions"
                                wire:confirm="Wyczyscic oba opisy domyslne?"
                                class="text-xs text-red-400 hover:text-red-300 transition-colors">
                            Wyczysc opisy
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <button type="button"
                                wire:click="closeModal"
                                class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Status indicator --}}
                        @if($skipDescriptions)
                        <span class="text-xs text-red-400 mr-2">
                            Brak opisow
                        </span>
                        @elseif(!empty($availableShops))
                        <span class="text-xs text-gray-400 mr-2">
                            {{ $this->footerStatus }}
                        </span>
                        @elseif($shortDescription || $longDescription)
                        <span class="text-xs text-green-400 mr-2">
                            {{ (trim($shortDescription) ? '1' : '0') + (trim($longDescription) ? '1' : '0') }}/2 opisow
                        </span>
                        @endif

                        <button type="button"
                                wire:click="saveDescriptions"
                                @disabled($isProcessing)
                                class="disabled:opacity-50 disabled:cursor-not-allowed
                                       {{ $skipDescriptions
                                           ? 'btn-enterprise-danger'
                                           : 'btn-enterprise-success' }}">
                            @if($isProcessing)
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Zapisywanie...
                            @else
                                {{ $skipDescriptions ? 'Zapisz (bez opisow)' : 'Zapisz opisy' }}
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
