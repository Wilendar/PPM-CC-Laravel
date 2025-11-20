
                    {{-- Tax Rules Mapping Section (FAZA 5.1) --}}
                    @if ($connectionStatus === 'success')
                        <div class="tax-rules-mapping-section mt-6">
                            <div class="section-header mb-4">
                                <h4 class="text-lg font-semibold text-white flex items-center mb-2">
                                    <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    Mapowanie Grup Podatkowych
                                </h4>
                                <p class="text-sm text-gray-300">
                                    Wybierz grupy podatkowe PrestaShop odpowiadające stawkom VAT w PPM.
                                    <span class="text-red-400 font-medium ml-2">*Stawka 23% jest wymagana</span>
                                </p>
                            </div>

                            {{-- Loading State --}}
                            @if (!isset($taxRulesFetched) || $taxRulesFetched === false)
                                <div class="tax-rules-loading flex items-center justify-center py-6">
                                    <svg class="animate-spin w-5 h-5 text-[#e0ac7e] mr-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-gray-300">Pobieranie grup podatkowych z PrestaShop...</span>
                                </div>
                            @endif

                            {{-- Error State --}}
                            @error('tax_rules')
                                <div class="alert alert-warning p-4 rounded-lg bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 flex items-start mb-4">
                                    <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm text-red-300">{{ $message }}</p>
                                        <button wire:click="fetchTaxRuleGroups"
                                                class="mt-2 text-sm text-red-200 hover:text-red-100 underline transition-colors duration-200">
                                            Spróbuj ponownie
                                        </button>
                                    </div>
                                </div>
                            @enderror

                            {{-- Tax Rules Grid --}}
                            @if (isset($availableTaxRuleGroups) && count($availableTaxRuleGroups) > 0)
                                <div class="tax-rules-grid">
                                    {{-- 23% VAT (Required) --}}
                                    <div class="tax-rule-item required">
                                        <label for="taxRulesGroup23" class="form-label block text-sm font-medium text-white mb-2">
                                            VAT 23% (Standard) <span class="required-asterisk">*</span>
                                        </label>
                                        <select
                                            wire:model.defer="taxRulesGroup23"
                                            id="taxRulesGroup23"
                                            class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 @error('taxRulesGroup23') border-red-500 @enderror"
                                            required>
                                            <option value="">-- Wybierz grupę --</option>
                                            @foreach ($availableTaxRuleGroups as $group)
                                                <option value="{{ $group['id'] }}">
                                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('taxRulesGroup23')
                                            <span class="invalid-feedback text-red-400 text-sm mt-1 block">{{ $message }}</span>
                                        @enderror
                                        @if (isset($taxRulesGroup23) && $taxRulesGroup23)
                                            <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Wybrano
                                            </span>
                                        @endif
                                    </div>

                                    {{-- 8% VAT (Optional) --}}
                                    <div class="tax-rule-item">
                                        <label for="taxRulesGroup8" class="form-label block text-sm font-medium text-white mb-2">
                                            VAT 8% (Obniżona)
                                        </label>
                                        <select
                                            wire:model.defer="taxRulesGroup8"
                                            id="taxRulesGroup8"
                                            class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                            <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                            @foreach ($availableTaxRuleGroups as $group)
                                                <option value="{{ $group['id'] }}">
                                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (isset($taxRulesGroup8) && $taxRulesGroup8)
                                            <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Wybrano
                                            </span>
                                        @endif
                                    </div>

                                    {{-- 5% VAT (Optional) --}}
                                    <div class="tax-rule-item">
                                        <label for="taxRulesGroup5" class="form-label block text-sm font-medium text-white mb-2">
                                            VAT 5% (Super Obniżona)
                                        </label>
                                        <select
                                            wire:model.defer="taxRulesGroup5"
                                            id="taxRulesGroup5"
                                            class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                            <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                            @foreach ($availableTaxRuleGroups as $group)
                                                <option value="{{ $group['id'] }}">
                                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (isset($taxRulesGroup5) && $taxRulesGroup5)
                                            <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Wybrano
                                            </span>
                                        @endif
                                    </div>

                                    {{-- 0% VAT (Optional) --}}
                                    <div class="tax-rule-item">
                                        <label for="taxRulesGroup0" class="form-label block text-sm font-medium text-white mb-2">
                                            VAT 0% (Zwolniona)
                                        </label>
                                        <select
                                            wire:model.defer="taxRulesGroup0"
                                            id="taxRulesGroup0"
                                            class="form-select w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                                            <option value="">-- Wybierz grupę (opcjonalnie) --</option>
                                            @foreach ($availableTaxRuleGroups as $group)
                                                <option value="{{ $group['id'] }}">
                                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (isset($taxRulesGroup0) && $taxRulesGroup0)
                                            <span class="selected-indicator text-green-400 text-sm mt-1 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Wybrano
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Info Card --}}
                                <div class="tax-rules-info mt-4 p-4 rounded-lg bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 flex items-start">
                                    <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-blue-200">Inteligentne domyślne wybory zastosowane na podstawie nazw grup. Możesz je zmienić ręcznie wybierając odpowiednią grupę z listy.</span>
                                </div>
                            @endif
                        </div>
                    @endif
