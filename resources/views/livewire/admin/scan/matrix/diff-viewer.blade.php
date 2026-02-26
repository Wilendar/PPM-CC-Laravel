@php
    // Pobierz NAJNOWSZE wyniki skanowania per zrodlo (deduplikacja)
    $scanResults = \App\Models\ProductScanResult::where('ppm_product_id', $product->id)
        ->whereIn('match_status', ['conflict', 'matched', 'already_linked'])
        ->latest()
        ->get()
        ->unique(fn ($r) => $r->external_source_type . '_' . $r->external_source_id)
        ->values();

    // Nazwy czytelne dla pol produktu
    $fieldLabels = [
        'name'           => 'Nazwa',
        'price'          => 'Cena',
        'price_excl_tax' => 'Cena netto',
        'quantity'       => 'Ilosc',
        'description'    => 'Opis',
        'short_desc'     => 'Krotki opis',
        'ean'            => 'EAN',
        'weight'         => 'Waga',
        'reference'      => 'SKU/Ref',
        'active'         => 'Aktywny',
        'category'       => 'Kategoria',
        'manufacturer'   => 'Producent',
    ];
@endphp

<tr class="matrix-diff-viewer-row">
    <td colspan="999" class="p-0">
        <div
            class="bg-gray-850 border-t border-b border-gray-600"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
        >
            {{-- Naglowek diff viewera --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h4 class="text-sm font-medium text-white">
                        Porownanie danych: <span class="text-yellow-400">{{ $product->sku }}</span>
                    </h4>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Legenda --}}
                    <div class="hidden sm:flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-green-900/60 border border-green-700"></span>
                            PPM
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-900/60 border border-red-700"></span>
                            Zrodlo
                        </span>
                    </div>
                    {{-- Akcja zamkniecia --}}
                    <button
                        wire:click="toggleDiffViewer({{ $product->id }})"
                        class="p-1 text-gray-500 hover:text-gray-300 rounded transition-colors"
                        aria-label="Zamknij podglad roznic"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-4">
                @if($scanResults->isEmpty())
                    {{-- Stan: brak danych skanowania --}}
                    <div class="flex items-start gap-3 text-sm text-gray-500">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-gray-400 font-medium">Brak danych porownawczych</p>
                            <p class="mt-0.5 text-gray-600">
                                Uruchom skan konfliktow aby zobaczyc szczegolowe roznice miedzy danymi PPM a zrodlem.
                            </p>
                        </div>
                    </div>
                @else
                    {{-- Lista wynikow per zrodlo --}}
                    <div class="space-y-4">
                        @foreach($scanResults as $result)
                        @php
                            $diff = $result->diff_data ?? [];
                            $fields = $diff['fields'] ?? [];
                            $sourceName = match($result->external_source_type) {
                                'prestashop'  => 'PrestaShop',
                                'subiekt_gt'  => 'Subiekt GT',
                                'baselinker'  => 'Baselinker',
                                'magento'     => 'Magento',
                                default       => ucfirst(str_replace('_', ' ', $result->external_source_type)),
                            };
                        @endphp

                        <div class="rounded-lg border border-gray-700 overflow-hidden">
                            {{-- Naglowek zrodla --}}
                            <div class="flex items-center justify-between px-3 py-2 bg-gray-800/80 border-b border-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-300">
                                        {{ $sourceName }}
                                        <span class="text-gray-500">#{{ $result->external_source_id }}</span>
                                    </span>
                                    @if(!empty($result->external_product_id))
                                    <span class="text-xs text-gray-600">
                                        &mdash; zewn. ID: {{ $result->external_product_id }}
                                    </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-600">
                                    {{ $result->updated_at?->diffForHumans() ?? '' }}
                                </span>
                            </div>

                            @if(empty($fields))
                                {{-- Wynik konfliktu bez szczegolowych pol --}}
                                <div class="px-3 py-3 text-xs text-gray-500">
                                    Konflikt wykryty, ale brak szczegolowych danych roznic.
                                    Uruchom pelny skan aby zobaczyc porownanie pol.
                                </div>
                            @else
                                {{-- Tabela roznic pol --}}
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-700">
                                            <th class="text-left py-1.5 px-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-1/5">
                                                Pole
                                            </th>
                                            <th class="text-left py-1.5 px-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-2/5">
                                                <span class="flex items-center gap-1">
                                                    <span class="inline-block w-2 h-2 rounded-sm bg-green-600"></span>
                                                    PPM
                                                </span>
                                            </th>
                                            <th class="text-left py-1.5 px-3 text-xs font-medium text-gray-500 uppercase tracking-wide w-2/5">
                                                <span class="flex items-center gap-1">
                                                    <span class="inline-block w-2 h-2 rounded-sm bg-red-600"></span>
                                                    {{ $sourceName }}
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700/50">
                                        @foreach($fields as $fieldName => $fieldDiff)
                                        @php
                                            $label = $fieldLabels[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));
                                            $ppmValue = $fieldDiff['ppm'] ?? null;
                                            $sourceValue = $fieldDiff['source'] ?? null;
                                            $isEmpty = ($ppmValue === null || $ppmValue === '') && ($sourceValue === null || $sourceValue === '');
                                        @endphp
                                        @if(!$isEmpty)
                                        <tr class="hover:bg-gray-800/30">
                                            <td class="py-2 px-3 text-xs text-gray-400 font-medium align-top">
                                                {{ $label }}
                                            </td>
                                            <td class="py-2 px-3 align-top">
                                                @if($ppmValue !== null && $ppmValue !== '')
                                                <span class="text-xs text-green-400 break-words">
                                                    {{ is_array($ppmValue) ? implode(', ', $ppmValue) : $ppmValue }}
                                                </span>
                                                @else
                                                <span class="text-xs text-gray-600 italic">brak</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 align-top">
                                                @if($sourceValue !== null && $sourceValue !== '')
                                                <span class="text-xs text-red-400 break-words">
                                                    {{ is_array($sourceValue) ? implode(', ', $sourceValue) : $sourceValue }}
                                                </span>
                                                @else
                                                <span class="text-xs text-gray-600 italic">brak</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>

                                {{-- Podsumowanie roznic --}}
                                @php $fieldCount = count(array_filter($fields, fn($f) => ($f['ppm'] ?? null) !== ($f['source'] ?? null))); @endphp
                                @if($fieldCount > 0)
                                <div class="px-3 py-2 bg-gray-800/60 border-t border-gray-700 text-xs text-gray-500">
                                    {{ $fieldCount }} {{ $fieldCount === 1 ? 'rozne pole' : ($fieldCount < 5 ? 'rozne pola' : 'roznych pol') }}
                                </div>
                                @endif
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Akcje globalne --}}
                    <div class="mt-4 flex items-center gap-2">
                        <a
                            href="/admin/products/{{ $product->id }}/edit"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-gray-300 bg-gray-700 hover:bg-gray-600 border border-gray-600 rounded transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Edytuj w PPM
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </td>
</tr>
