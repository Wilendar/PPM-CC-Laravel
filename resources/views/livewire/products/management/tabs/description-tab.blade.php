{{-- resources/views/livewire/products/management/tabs/description-tab.blade.php --}}
<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">Opisy i SEO</h3>

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

    <div class="space-y-6">
        {{-- Short Description --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="short_description" class="block text-sm font-medium text-gray-300">
                    Krótki opis
                    {{-- Status indicator --}}
                    @php
                            $shortDescIndicator = $this->getFieldStatusIndicator('short_description');
                        @endphp
                    @if($shortDescIndicator['show'])
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shortDescIndicator['class'] }}">
                            {{ $shortDescIndicator['text'] }}
                        </span>
                    @endif
                </label>
                <span class="text-sm {{ $shortDescriptionWarning ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $shortDescriptionCount }}/800
                </span>
            </div>
            <textarea wire:model.live="short_description"
                      id="short_description"
                      rows="4"
                      placeholder="Krótki opis produktu widoczny w listach i kartach produktów..."
                      class="{{ $this->getFieldClasses('short_description') }} @error('short_description') !border-red-500 @enderror {{ $shortDescriptionWarning ? '!border-orange-500 focus:!border-orange-500 focus:!ring-orange-500' : '' }}"></textarea>
            @if($shortDescriptionWarning)
                <p class="mt-1 text-sm text-orange-600 dark:text-orange-400">Przekraczasz zalecany limit znaków</p>
            @endif
            @error('short_description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Long Description --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="long_description" class="block text-sm font-medium text-gray-300">
                    Długi opis
                    @php
                            $longDescIndicator = $this->getFieldStatusIndicator('long_description');
                        @endphp
                    @if($longDescIndicator['show'])
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $longDescIndicator['class'] }}">
                            {{ $longDescIndicator['text'] }}
                        </span>
                    @endif
                </label>
                <span class="text-sm {{ $longDescriptionWarning ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $longDescriptionCount }}/21844
                </span>
            </div>
            <textarea wire:model.live="long_description"
                      id="long_description"
                      rows="8"
                      placeholder="Szczegółowy opis produktu z specyfikacją techniczną, zastosowaniem, kompatybilnością..."
                      class="{{ $this->getFieldClasses('long_description') }} @error('long_description') !border-red-500 @enderror {{ $longDescriptionWarning ? '!border-orange-500 focus:!border-orange-500 focus:!ring-orange-500' : '' }}"></textarea>
            @if($longDescriptionWarning)
                <p class="mt-1 text-sm text-orange-600 dark:text-orange-400">Przekraczasz zalecany limit znaków</p>
            @endif
            @error('long_description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- SEO Fields --}}
        <div class="border-t border-gray-700 pt-6">
            <h4 class="text-md font-medium text-white mb-4">Optymalizacja SEO</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Meta Title --}}
                <div class="md:col-span-2">
                    <label for="meta_title" class="block text-sm font-medium text-gray-300 mb-2">
                        Tytuł SEO (meta title)
                        @php
                            $metaTitleIndicator = $this->getFieldStatusIndicator('meta_title');
                        @endphp
                        @if($metaTitleIndicator['show'])
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $metaTitleIndicator['class'] }}">
                                {{ $metaTitleIndicator['text'] }}
                            </span>
                        @endif
                    </label>
                    <input wire:model.live="meta_title"
                           type="text"
                           id="meta_title"
                           placeholder="Tytuł strony produktu dla wyszukiwarek"
                           class="{{ $this->getFieldClasses('meta_title') }} @error('meta_title') !border-red-500 @enderror">
                    @error('meta_title')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Meta Description --}}
                <div class="md:col-span-2">
                    <label for="meta_description" class="block text-sm font-medium text-gray-300 mb-2">
                        Opis SEO (meta description)
                        @php
                            $metaDescIndicator = $this->getFieldStatusIndicator('meta_description');
                        @endphp
                        @if($metaDescIndicator['show'])
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $metaDescIndicator['class'] }}">
                                {{ $metaDescIndicator['text'] }}
                            </span>
                        @endif
                    </label>
                    <textarea wire:model.live="meta_description"
                              id="meta_description"
                              rows="3"
                              placeholder="Opis produktu widoczny w wynikach wyszukiwania Google"
                              class="{{ $this->getFieldClasses('meta_description') }} @error('meta_description') !border-red-500 @enderror"></textarea>
                    @error('meta_description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
