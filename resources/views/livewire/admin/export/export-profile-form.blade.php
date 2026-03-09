<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 rounded-lg border border-green-700 bg-green-900/30 px-4 py-3 text-sm text-green-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
            {{ session('error') }}
        </div>
    @endif
    @error('general')
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">
            {{ $profileId ? 'Edytuj profil eksportu' : 'Nowy profil eksportu' }}
        </h1>
        <a href="{{ route('admin.export.index') }}" wire:navigate
           class="inline-flex items-center gap-2 text-sm text-gray-400 transition-colors hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Powrot do listy
        </a>
    </div>

    {{-- Stepper / Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center" wire:key="step-indicator-{{ $i }}">
                    <button wire:click="goToStep({{ $i }})"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors
                                   {{ $i <= $currentStep ? 'bg-[#e0ac7e] text-gray-900' : 'bg-gray-700 text-gray-400' }}
                                   {{ $i <= $currentStep ? 'cursor-pointer hover:bg-[#c9956a]' : 'cursor-default' }}">
                        @if($i < $currentStep)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $i }}
                        @endif
                    </button>
                    <span class="ml-2 hidden text-sm sm:inline {{ $i <= $currentStep ? 'text-white' : 'text-gray-500' }}">
                        {{ $this->getStepTitle($i) }}
                    </span>
                </div>
                @if($i < $totalSteps)
                    <div class="mx-2 h-px flex-1 {{ $i < $currentStep ? 'bg-[#e0ac7e]' : 'bg-gray-700' }}"></div>
                @endif
            @endfor
        </div>
    </div>

    {{-- Step Content --}}
    <div class="rounded-xl border border-gray-700 bg-gray-800/50 p-6">

        {{-- STEP 1: Basic Info --}}
        @if($currentStep === 1)
            <div class="space-y-5">
                <h2 class="mb-4 text-lg font-semibold text-white">Informacje podstawowe</h2>

                {{-- Name --}}
                <div>
                    <label for="profile-name" class="mb-1 block text-sm font-medium text-gray-300">Nazwa profilu *</label>
                    <input wire:model="name" id="profile-name" type="text"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                           placeholder="np. Feed Google Shopping">
                    @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Format --}}
                <div>
                    <label for="profile-format" class="mb-1 block text-sm font-medium text-gray-300">Format eksportu *</label>
                    <select wire:model="format" id="profile-format"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        @foreach($this->getFormatOptions() as $key => $option)
                            <option value="{{ $key }}">{{ $option['label'] }} - {{ $option['description'] }}</option>
                        @endforeach
                    </select>
                    @error('format') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Schedule --}}
                <div>
                    <label for="profile-schedule" class="mb-1 block text-sm font-medium text-gray-300">Harmonogram generowania</label>
                    <select wire:model="schedule" id="profile-schedule"
                            class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <option value="manual">Reczny</option>
                        <option value="1h">Co godzine</option>
                        <option value="6h">Co 6 godzin</option>
                        <option value="12h">Co 12 godzin</option>
                        <option value="24h">Raz dziennie</option>
                    </select>
                    @error('schedule') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Collapsible Security Section --}}
                <div x-data="{ securityOpen: false }" class="mt-6 border-t border-gray-700 pt-4">
                    <button @click="securityOpen = !securityOpen" type="button"
                            class="flex w-full items-center justify-between text-sm font-medium text-gray-300 transition-colors hover:text-white">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Ustawienia bezpieczenstwa (opcjonalne)
                        </span>
                        <svg class="h-4 w-4 transition-transform" :class="securityOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="securityOpen" x-collapse class="mt-4 space-y-4">
                        {{-- Token Expiry --}}
                        <div>
                            <label for="token-expiry" class="mb-1 block text-sm font-medium text-gray-300">Waznosc tokena</label>
                            <select wire:model="tokenExpiryDays" id="token-expiry"
                                    class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <option value="">Bez wygasania</option>
                                <option value="7">7 dni</option>
                                <option value="30">30 dni</option>
                                <option value="90">90 dni</option>
                                <option value="180">180 dni</option>
                                <option value="365">1 rok</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Po wygasnieciu token przestanie dzialac. Mozna go pozniej zregenerowac.</p>
                        </div>

                        {{-- IP Whitelist --}}
                        <div>
                            <label for="allowed-ips" class="mb-1 block text-sm font-medium text-gray-300">IP Whitelist</label>
                            <textarea wire:model="allowedIpsText" id="allowed-ips"
                                      class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                                      placeholder="Jedno IP na linie, np.&#10;192.168.1.1&#10;10.0.0.5"
                                      rows="4"></textarea>
                            <p class="mt-1 text-xs text-gray-500">Pozostaw puste = dostep z kazdego IP. Tylko poprawne adresy IP beda zapisane.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 2: Field Selection --}}
        @if($currentStep === 2)
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Wybor pol do eksportu</h2>
                        <p class="mt-1 text-sm text-gray-400">Wybrane pola: <span class="font-medium text-[#e0ac7e]">{{ $this->getSelectedCount() }}</span></p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="selectAllFields" class="text-sm font-medium text-[#e0ac7e] transition-colors hover:text-[#c9956a]">
                            Zaznacz wszystkie
                        </button>
                        <span class="text-gray-600">|</span>
                        <button wire:click="deselectAllFields" class="text-sm text-gray-400 transition-colors hover:text-white">
                            Odznacz wszystkie
                        </button>
                    </div>
                </div>

                @error('selectedFields')
                    <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
                        {{ $message }}
                    </div>
                @enderror

                <div class="space-y-5">
                    @foreach($availableFieldGroups as $groupKey => $group)
                        <div wire:key="field-group-{{ $groupKey }}" class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-white">
                                    {{ $group['label'] }}
                                    <span class="ml-2 text-xs font-normal text-gray-400">
                                        ({{ $this->getGroupSelectedCount($groupKey) }}/{{ count($group['fields'] ?? []) }})
                                    </span>
                                </h3>
                                <div class="flex gap-3">
                                    <button wire:click="selectAllInGroup('{{ $groupKey }}')"
                                            class="text-xs text-gray-400 transition-colors hover:text-white">Wszystkie</button>
                                    <button wire:click="deselectAllInGroup('{{ $groupKey }}')"
                                            class="text-xs text-gray-400 transition-colors hover:text-white">Zadne</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach($group['fields'] as $fieldKey => $field)
                                    <label wire:key="field-{{ $groupKey }}-{{ $fieldKey }}"
                                           class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                        <input type="checkbox"
                                               wire:click="toggleField('{{ $fieldKey }}')"
                                               {{ $this->isFieldSelected($fieldKey) ? 'checked' : '' }}
                                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="text-sm text-gray-300">{{ $field['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- STEP 3: Filters --}}
        @if($currentStep === 3)
            <div class="space-y-5">
                <h2 class="mb-4 text-lg font-semibold text-white">Filtry produktow</h2>
                <p class="mb-4 text-sm text-gray-400">Okresl, ktore produkty maja byc uwzglednione w eksporcie. Wszystkie filtry sa opcjonalne.</p>

                {{-- Active & Stock toggles --}}
                <div class="flex flex-wrap gap-6">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" wire:model.live="filterIsActive"
                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="text-sm text-gray-300">Tylko aktywne produkty</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" wire:model.live="filterHasStock"
                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="text-sm text-gray-300">Tylko z dostepnym stanem</span>
                    </label>
                </div>

                {{-- Manufacturer --}}
                <div>
                    <label for="filter-manufacturer" class="mb-1 block text-sm font-medium text-gray-300">Producent</label>
                    <input wire:model.live.debounce.300ms="filterManufacturer" id="filter-manufacturer" type="text"
                           class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                           placeholder="Filtruj po producencie...">
                    @if(!empty($availableManufacturers) && !empty($filterManufacturer))
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach(collect($availableManufacturers)->filter(fn($m) => stripos($m, $filterManufacturer) !== false)->take(10) as $mfr)
                                <button wire:click="$set('filterManufacturer', '{{ addslashes($mfr) }}')"
                                        class="rounded bg-gray-700 px-2 py-1 text-xs text-gray-300 transition-colors hover:bg-gray-600 hover:text-white">
                                    {{ $mfr }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Categories --}}
                @if(!empty($availableCategories))
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-300">
                            Kategorie
                            @if(!empty($filterCategoryIds))
                                <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterCategoryIds) }} wybranych)</span>
                            @endif
                        </label>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-700 bg-gray-800/30 p-3">
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($availableCategories as $category)
                                    <label wire:key="filter-cat-{{ $category['id'] }}"
                                           class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-1.5 transition-colors hover:bg-gray-700">
                                        <input type="checkbox"
                                               wire:click="toggleCategory({{ $category['id'] }})"
                                               {{ in_array((string) $category['id'], $filterCategoryIds) ? 'checked' : '' }}
                                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="text-xs text-gray-300">{{ $category['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Shops --}}
                @if(!empty($availableShops))
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-300">
                            Sklepy PrestaShop
                            @if(!empty($filterShopIds))
                                <span class="ml-1 text-xs text-[#e0ac7e]">({{ count($filterShopIds) }} wybranych)</span>
                            @endif
                        </label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach($availableShops as $shop)
                                <label wire:key="filter-shop-{{ $shop['id'] }}"
                                       class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                    <input type="checkbox"
                                           wire:click="toggleShop({{ $shop['id'] }})"
                                           {{ in_array((string) $shop['id'], $filterShopIds) ? 'checked' : '' }}
                                           class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="text-sm text-gray-300">{{ $shop['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- STEP 4: Price Groups & Warehouses --}}
        @if($currentStep === 4)
            <div>
                <h2 class="mb-4 text-lg font-semibold text-white">Grupy cenowe i magazyny</h2>
                <p class="mb-5 text-sm text-gray-400">Wybierz, ktore grupy cenowe i magazyny uwzglednic w eksporcie.</p>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Price Groups --}}
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-white">
                            Grupy cenowe
                            @if(!empty($selectedPriceGroups))
                                <span class="ml-1 text-xs font-normal text-[#e0ac7e]">({{ count($selectedPriceGroups) }})</span>
                            @endif
                        </h3>
                        @if(!empty($availablePriceGroups))
                            <div class="space-y-2">
                                @foreach($availablePriceGroups as $pg)
                                    <label wire:key="pg-{{ $pg['id'] }}"
                                           class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                        <input type="checkbox" value="{{ $pg['id'] }}" wire:model.live="selectedPriceGroups"
                                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="text-sm text-gray-300">{{ $pg['name'] }}</span>
                                        <span class="ml-auto text-xs text-gray-500">{{ $pg['code'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Brak aktywnych grup cenowych.</p>
                        @endif
                    </div>

                    {{-- Warehouses --}}
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-white">
                            Magazyny
                            @if(!empty($selectedWarehouses))
                                <span class="ml-1 text-xs font-normal text-[#e0ac7e]">({{ count($selectedWarehouses) }})</span>
                            @endif
                        </h3>
                        @if(!empty($availableWarehouses))
                            <div class="space-y-2">
                                @foreach($availableWarehouses as $wh)
                                    <label wire:key="wh-{{ $wh['id'] }}"
                                           class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                                        <input type="checkbox" value="{{ $wh['id'] }}" wire:model.live="selectedWarehouses"
                                               class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="text-sm text-gray-300">{{ $wh['name'] }}</span>
                                        <span class="ml-auto text-xs text-gray-500">{{ $wh['code'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Brak aktywnych magazynow.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 5: Preview --}}
        @if($currentStep === 5)
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Podglad eksportu</h2>
                        <p class="mt-1 text-sm text-gray-400">
                            Podglad pierwszych 5 produktow
                            @if($previewCount > 0)
                                <span class="text-[#e0ac7e]">(z {{ number_format($previewCount) }} znalezionych)</span>
                            @endif
                        </p>
                    </div>
                    <button wire:click="loadPreview"
                            wire:loading.attr="disabled"
                            wire:target="loadPreview"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-600 px-3 py-1.5 text-sm text-gray-300 transition-colors hover:bg-gray-700 hover:text-white">
                        <svg wire:loading.remove wire:target="loadPreview" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="loadPreview" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Odswiez podglad
                    </button>
                </div>

                {{-- Summary --}}
                <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
                        <p class="text-xs text-gray-400">Produkty</p>
                        <p class="text-lg font-bold text-white">{{ number_format($previewCount) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
                        <p class="text-xs text-gray-400">Kolumny</p>
                        <p class="text-lg font-bold text-white">{{ $this->getSelectedCount() }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
                        <p class="text-xs text-gray-400">Format</p>
                        <p class="text-lg font-bold text-white uppercase">{{ $format }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
                        <p class="text-xs text-gray-400">Harmonogram</p>
                        <p class="text-lg font-bold text-white">{{ $schedule === 'manual' ? 'Reczny' : $schedule }}</p>
                    </div>
                </div>

                {{-- Preview Table --}}
                @if(count($previewProducts) > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-700">
                        <table class="w-full text-sm text-gray-300">
                            <thead>
                                <tr class="border-b border-gray-700 bg-gray-800/80">
                                    @foreach(array_keys($previewProducts[0] ?? []) as $header)
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-400">
                                            {{ $header }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewProducts as $index => $row)
                                    <tr wire:key="preview-row-{{ $index }}" class="border-b border-gray-700/50 transition-colors hover:bg-gray-800/30">
                                        @foreach($row as $value)
                                            <td class="whitespace-nowrap px-3 py-2 text-xs">
                                                {{ Str::limit((string) $value, 50) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-gray-700 bg-gray-800/30 px-6 py-8 text-center">
                        <svg class="mx-auto mb-3 h-10 w-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Brak produktow spelniajacych kryteria filtrowania.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Navigation Buttons --}}
    <div class="mt-6 flex items-center justify-between">
        <div>
            @if($currentStep > 1)
                <button wire:click="previousStep"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-600 px-4 py-2 text-sm text-gray-300 transition-colors hover:bg-gray-700 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Poprzedni krok
                </button>
            @endif
        </div>
        <div class="flex gap-3">
            @if($currentStep < $totalSteps)
                <button wire:click="nextStep"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#e0ac7e] px-6 py-2 text-sm font-semibold text-gray-900 transition-colors hover:bg-[#c9956a]">
                    Nastepny krok
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="save" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ $profileId ? 'Zapisz zmiany' : 'Utworz profil' }}
                </button>
            @endif
        </div>
    </div>
</div>
