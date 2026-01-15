{{-- resources/views/livewire/products/management/tabs/visual-description-tab.blade.php --}}
{{-- ETAP_07f Faza 6: Visual Description Editor Integration Tab --}}

<div class="tab-content active space-y-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white">Opis Wizualny</h3>

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

    @php
        $visualInfo = method_exists($this, 'visualDescriptionInfo') ? $this->visualDescriptionInfo : ['exists' => false];
        $hasVisualDesc = $visualInfo['exists'] ?? false;
        $blockCount = $visualInfo['block_count'] ?? 0;
        $lastModified = $visualInfo['last_modified'] ?? null;
        $templateName = $visualInfo['template_name'] ?? null;
        $needsRender = $visualInfo['needs_rendering'] ?? false;
    @endphp

    {{-- Check if shop is selected --}}
    @if(!$activeShopId && empty($exportedShops))
        <div class="rounded-xl border border-yellow-600/30 bg-yellow-900/20 p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-yellow-200 font-medium">Brak przypisanego sklepu</h4>
                    <p class="text-yellow-400/70 text-sm mt-1">
                        Aby tworzyc opisy wizualne, najpierw przypisz produkt do sklepu w zakladce "Sklepy".
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Visual Description Content --}}
            @if($hasVisualDesc)
                {{-- Has Visual Description --}}
                <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 overflow-hidden">
                    @if($blockCount > 0)
                        {{-- Header with blocks info --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50">
                            <div class="flex items-center space-x-4">
                                <div class="p-2 bg-blue-500/20 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-white font-medium">Opis wizualny aktywny</h4>
                                    <div class="flex items-center space-x-3 text-sm text-gray-400 mt-1">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                            {{ $blockCount }} {{ $blockCount === 1 ? 'blok' : ($blockCount < 5 ? 'bloki' : 'blokow') }}
                                        </span>
                                        @if($lastModified)
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $lastModified }}
                                            </span>
                                        @endif
                                        @if($templateName)
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Szablon: {{ $templateName }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center space-x-2">
                                <button type="button"
                                        wire:click="openVersionHistory"
                                        class="px-3 py-2 text-sm font-medium text-gray-300 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors flex items-center gap-1"
                                        title="Historia wersji">
                                    <i class="fas fa-history"></i>
                                    @if($this->versionHistoryInfo['version_count'] ?? 0 > 0)
                                        <span class="text-xs text-gray-400">({{ $this->versionHistoryInfo['version_count'] }})</span>
                                    @endif
                                </button>
                                <button type="button"
                                        wire:click="refreshVisualPreview"
                                        class="px-3 py-2 text-sm font-medium text-gray-300 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors"
                                        title="Odswiez podglad">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button type="button"
                                        wire:click="openVisualEditor"
                                        class="btn-enterprise-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edytuj w edytorze
                                </button>
                            </div>
                        </div>

                        {{-- Preview Section with Shop CSS --}}
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <h5 class="text-sm font-medium text-gray-400">Podglad opisu</h5>
                                    @if($this->hasShopCss)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-green-900/30 text-green-400 border border-green-700/30" title="Podglad ze stylami sklepu PrestaShop">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            CSS sklepu
                                        </span>
                                        <button type="button"
                                                wire:click="refreshShopCss"
                                                class="text-xs text-gray-500 hover:text-gray-300 transition-colors"
                                                title="Odswiez CSS sklepu">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-gray-700/50 text-gray-500" title="CSS sklepu nie skonfigurowany">
                                            Bez stylowania sklepu
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($needsRender)
                                        <span class="px-2 py-1 text-xs bg-yellow-900/30 text-yellow-400 rounded-full">
                                            Wymaga re-renderowania
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Preview Container --}}
                            @if(isset($visualPreviewHtml) && $visualPreviewHtml)
                                @php
                                    $shopCss = $this->shopCssContent ?? '';
                                    // Build iframe srcdoc with shop CSS
                                    $iframeContent = '<!DOCTYPE html><html><head><meta charset="utf-8">';
                                    $iframeContent .= '<style>body{font-family:Inter,system-ui,sans-serif;margin:0;padding:16px;background:#fff;color:#333;line-height:1.6;}</style>';
                                    if ($shopCss) {
                                        $iframeContent .= '<style>' . $shopCss . '</style>';
                                    }
                                    $iframeContent .= '</head><body>';
                                    $iframeContent .= $visualPreviewHtml;
                                    $iframeContent .= '</body></html>';
                                @endphp
                                <div class="rounded-lg border border-slate-700/30 overflow-hidden bg-white"
                                     x-data="{ iframeContent: @js($iframeContent) }"
                                     x-init="$nextTick(() => { $refs.previewFrame.srcdoc = iframeContent; })">
                                    <iframe
                                        x-ref="previewFrame"
                                        class="w-full border-0"
                                        style="min-height: 300px; max-height: 500px;"
                                        sandbox="allow-same-origin"
                                        title="Podglad opisu produktu"
                                        @load="$el.style.height = Math.min($el.contentWindow.document.body.scrollHeight + 40, 500) + 'px'">
                                    </iframe>
                                </div>
                                @if($this->shopCssUrl)
                                    <p class="text-xs text-gray-600 mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        CSS: {{ Str::limit($this->shopCssUrl, 60) }}
                                    </p>
                                @endif
                            @else
                                <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-700/30">
                                    <p class="text-gray-500 text-sm italic">
                                        Brak podgladu - kliknij "Odswiez podglad" lub edytuj opis w edytorze wizualnym
                                    </p>
                                </div>
                            @endif
                        </div>

                        @error('css_refresh')
                            <div class="mx-6 mb-4 rounded-lg border border-red-600/30 bg-red-900/20 p-3">
                                <p class="text-red-400 text-sm">{{ $message }}</p>
                            </div>
                        @enderror

                        {{-- Sync to Standard Description --}}
                        <div class="px-6 py-4 border-t border-slate-700/50 bg-slate-800/20">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="text-sm font-medium text-gray-300">Synchronizacja z opisem standardowym</h5>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Skopiuj wyrenderowany HTML do pola "Dlugi opis" (zakladka Opisy i SEO)
                                    </p>
                                </div>
                                <button type="button"
                                        wire:click="syncVisualToStandard"
                                        class="btn-enterprise-secondary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Synchronizuj
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Empty description (no blocks yet) --}}
                        <div class="px-6 py-4 border-b border-slate-700/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="p-2 bg-yellow-500/20 rounded-lg">
                                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-medium">Opis wizualny utworzony</h4>
                                        <p class="text-sm text-gray-400 mt-1">
                                            Opis nie zawiera jeszcze zadnych blokow. Otworz edytor aby dodac tresc.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button"
                                            wire:click="openVersionHistory"
                                            class="px-3 py-2 text-sm font-medium text-gray-300 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors flex items-center gap-1"
                                            title="Historia wersji">
                                        <i class="fas fa-history"></i>
                                        @if(($this->versionHistoryInfo['version_count'] ?? 0) > 0)
                                            <span class="text-xs text-gray-400">({{ $this->versionHistoryInfo['version_count'] }})</span>
                                        @endif
                                    </button>
                                    <button type="button"
                                            wire:click="openVisualEditor"
                                            class="btn-enterprise-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Otworz edytor
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- PrestaShop Sync Section (ETAP_07f 6.1.4) - Always visible when description exists --}}
                    @php
                        $syncInfo = method_exists($this, 'visualSyncInfo') ? $this->visualSyncInfo : [
                            'sync_enabled' => false,
                            'target_field' => 'description',
                            'include_css' => true,
                            'last_synced' => null,
                            'needs_sync' => false,
                            'can_sync' => false,
                        ];
                    @endphp
                    <div class="px-6 py-4 border-t border-slate-700/50">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-green-500/20 rounded-lg">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-white">Synchronizacja z PrestaShop</h5>
                                    <p class="text-xs text-gray-500">
                                        Wyslij opis wizualny do sklepu PrestaShop
                                    </p>
                                </div>
                            </div>
                            {{-- Sync Enable Toggle --}}
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       wire:click="togglePrestaShopSync"
                                       @checked($syncInfo['sync_enabled'])
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>

                        @if($syncInfo['sync_enabled'])
                            <div class="space-y-4 mt-4 pt-4 border-t border-slate-700/30">
                                {{-- Target Field Select --}}
                                <div class="flex items-center justify-between">
                                    <label class="text-sm text-gray-400">Pole docelowe</label>
                                    <select wire:change="setPrestaShopTargetField($event.target.value)"
                                            class="form-select-dark text-sm px-3 py-1.5 bg-slate-700 border-slate-600 rounded-lg text-gray-200 focus:border-green-500 focus:ring-green-500/30">
                                        <option value="description" @selected($syncInfo['target_field'] === 'description')>Dlugi opis</option>
                                        <option value="description_short" @selected($syncInfo['target_field'] === 'description_short')>Krotki opis</option>
                                        <option value="both" @selected($syncInfo['target_field'] === 'both')>Oba opisy</option>
                                    </select>
                                </div>

                                {{-- Include CSS Toggle --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm text-gray-400">Dolacz style CSS</label>
                                        <p class="text-xs text-gray-600">Osadzaj inline styles w HTML</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               wire:click="toggleInlineCss"
                                               @checked($syncInfo['include_css'])
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>

                                {{-- Sync Status --}}
                                <div class="flex items-center justify-between pt-2 border-t border-slate-700/30">
                                    <div class="flex items-center space-x-3">
                                        @if($syncInfo['last_synced'])
                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-green-900/30 text-green-400 border border-green-700/30">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Ostatnia sync: {{ $syncInfo['last_synced'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-yellow-900/30 text-yellow-400 border border-yellow-700/30">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Nigdy nie synchronizowano
                                            </span>
                                        @endif
                                        @if($syncInfo['needs_sync'])
                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-orange-900/30 text-orange-400 border border-orange-700/30">
                                                Wymaga synchronizacji
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Sync Now Button --}}
                                    @if($syncInfo['can_sync'])
                                        <button type="button"
                                                wire:click="syncToPrestaShopNow"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                class="btn-enterprise-primary">
                                            <svg class="w-4 h-4 mr-2" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <span wire:loading.remove wire:target="syncToPrestaShopNow">Synchronizuj teraz</span>
                                            <span wire:loading wire:target="syncToPrestaShopNow">Synchronizowanie...</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">
                                            Produkt nie jest zsynchronizowany z PrestaShop
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            @else
                {{-- No Visual Description at all --}}
                <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-700/50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <h4 class="text-lg font-medium text-white mb-2">Brak opisu wizualnego</h4>
                    <p class="text-gray-400 text-sm mb-6 max-w-md mx-auto">
                        Nie utworzono jeszcze opisu wizualnego dla tego produktu. Mozesz utworzyc nowy opis lub zastosowac szablon.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center space-y-3 sm:space-y-0 sm:space-x-3">
                        <button type="button"
                                wire:click="createVisualDescription"
                                class="btn-enterprise-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Stworz nowy opis
                        </button>
                    </div>
                </div>
            @endif

            {{-- Template Selection Section --}}
            @php
                $templates = method_exists($this, 'availableTemplates') ? $this->availableTemplates : [];
            @endphp
            @if(count($templates) > 0)
                <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-6">
                    <h4 class="text-md font-medium text-white mb-4">Zastosuj szablon</h4>
                    <p class="text-sm text-gray-400 mb-4">
                        Wybierz szablon, aby szybko utworzyc strukture opisu wizualnego.
                        @if($hasVisualDesc)
                            <span class="text-yellow-400">Uwaga: to nadpisze istniejacy opis!</span>
                        @endif
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($templates as $template)
                            <div class="p-4 border border-slate-600/50 rounded-lg hover:border-blue-500/50 hover:bg-slate-700/30 transition-colors cursor-pointer"
                                 wire:click="applyTemplateToVisual({{ $template['id'] }})"
                                 wire:loading.class="opacity-50 pointer-events-none">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h5 class="text-sm font-medium text-white">{{ $template['name'] }}</h5>
                                        @if(!empty($template['description']))
                                            <p class="text-xs text-gray-400 mt-1">{{ Str::limit($template['description'], 60) }}</p>
                                        @endif
                                        @if(!empty($template['category']))
                                            <span class="inline-block mt-2 px-2 py-0.5 text-xs bg-slate-700/50 text-gray-400 rounded">
                                                {{ $template['category'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Error Display --}}
            @error('visual_editor')
                <div class="rounded-lg border border-red-600/30 bg-red-900/20 p-4">
                    <p class="text-red-400 text-sm">{{ $message }}</p>
                </div>
            @enderror
            @error('visual_description')
                <div class="rounded-lg border border-red-600/30 bg-red-900/20 p-4">
                    <p class="text-red-400 text-sm">{{ $message }}</p>
                </div>
            @enderror
            @error('visual_sync')
                <div class="rounded-lg border border-red-600/30 bg-red-900/20 p-4">
                    <p class="text-red-400 text-sm">{{ $message }}</p>
                </div>
            @enderror
            @error('visual_template')
                <div class="rounded-lg border border-red-600/30 bg-red-900/20 p-4">
                    <p class="text-red-400 text-sm">{{ $message }}</p>
                </div>
            @enderror
            @error('version_history')
                <div class="rounded-lg border border-red-600/30 bg-red-900/20 p-4">
                    <p class="text-red-400 text-sm">{{ $message }}</p>
                </div>
            @enderror
        </div>
    @endif

    {{-- Version History Modal --}}
    @include('livewire.products.management.partials.version-history-modal')
</div>
