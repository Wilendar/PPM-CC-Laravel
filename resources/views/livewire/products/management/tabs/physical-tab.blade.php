{{-- resources/views/livewire/products/management/tabs/physical-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">Właściwości fizyczne</h3>

        {{-- Active Shop Indicator --}}
        @if($activeShopId !== null && isset($availableShops))
            @php
                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
            @endphp
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                </span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Dimensions Section --}}
        <div class="md:col-span-2">
            <h4 class="text-md font-medium text-white mb-4">Wymiary</h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Height --}}
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-300 mb-2">
                        Wysokość (cm)
                        @php
                            $heightIndicator = $this->getFieldStatusIndicator('height');
                        @endphp
                        @if($heightIndicator['show'])
                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $heightIndicator['class'] }}">
                                {{ $heightIndicator['text'] }}
                            </span>
                        @endif
                    </label>
                    <input wire:model.live="height"
                           type="number"
                           id="height"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="{{ $this->getFieldClasses('height') }} @error('height') !border-red-500 @enderror">
                    @error('height')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Width --}}
                <div>
                    <label for="width" class="block text-sm font-medium text-gray-300 mb-2">
                        Szerokość (cm)
                        @php
                            $widthIndicator = $this->getFieldStatusIndicator('width');
                        @endphp
                        @if($widthIndicator['show'])
                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $widthIndicator['class'] }}">
                                {{ $widthIndicator['text'] }}
                            </span>
                        @endif
                    </label>
                    <input wire:model.live="width"
                           type="number"
                           id="width"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="{{ $this->getFieldClasses('width') }} @error('width') !border-red-500 @enderror">
                    @error('width')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Length --}}
                <div>
                    <label for="length" class="block text-sm font-medium text-gray-300 mb-2">
                        Długość (cm)
                        @php
                            $lengthIndicator = $this->getFieldStatusIndicator('length');
                        @endphp
                        @if($lengthIndicator['show'])
                            <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium {{ $lengthIndicator['class'] }}">
                                {{ $lengthIndicator['text'] }}
                            </span>
                        @endif
                    </label>
                    <input wire:model.live="length"
                           type="number"
                           id="length"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="{{ $this->getFieldClasses('length') }} @error('length') !border-red-500 @enderror">
                    @error('length')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Calculated Volume --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Objętość (m³)
                    </label>
                    <div class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-400">
                        {{ $calculatedVolume ? number_format($calculatedVolume, 6) : '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Weight --}}
        <div>
            <label for="weight" class="block text-sm font-medium text-gray-300 mb-2">
                Waga (kg)
                @php
                            $weightIndicator = $this->getFieldStatusIndicator('weight');
                        @endphp
                @if($weightIndicator['show'])
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $weightIndicator['class'] }}">
                        {{ $weightIndicator['text'] }}
                    </span>
                @endif
            </label>
            <input wire:model.live="weight"
                   type="number"
                   id="weight"
                   step="0.001"
                   min="0"
                   placeholder="0.000"
                   class="{{ $this->getFieldClasses('weight') }} @error('weight') !border-red-500 @enderror">
            @error('weight')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tax Rate REMOVED - RELOCATED TO BASIC TAB (FAZA 5.2 - 2025-11-14) --}}

        {{-- Physical Properties Info --}}
        <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h5 class="font-medium text-blue-900 dark:text-blue-200">Informacje o wymiarach</h5>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        Wymiary są używane do obliczania kosztów wysyłki, optymalizacji pakowania oraz integracji z systemami logistycznymi.
                        Wszystkie wymiary podawaj w centymetrach (cm), wagę w kilogramach (kg).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
