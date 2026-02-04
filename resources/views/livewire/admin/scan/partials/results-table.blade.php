{{-- Results Table --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    @if($results->isEmpty())
        {{-- Empty State --}}
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="text-sm font-medium text-white mb-2">Brak wyników</h3>
            <p class="text-xs text-gray-400">
                @if(!$selectedSourceType || !$selectedSourceId)
                    Wybierz źródło i uruchom skan
                @else
                    Uruchom skan aby wyświetlić wyniki
                @endif
            </p>
        </div>
    @else
        {{-- Table --}}
        <table class="w-full text-xs">
            <thead class="bg-gray-700/50">
                <tr class="border-b border-gray-600">
                    <th class="text-left py-2 px-3 text-gray-300 font-medium w-10">
                        <input type="checkbox"
                               wire:model.live="selectAll"
                               wire:click="toggleSelectAll"
                               class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                    </th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">SKU</th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">Nazwa</th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">External ID</th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">Powiązania</th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">Status</th>
                    <th class="text-left py-2 px-3 text-gray-300 font-medium">Rozwiązanie</th>
                    <th class="text-right py-2 px-3 text-gray-300 font-medium">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr wire:key="result-{{ $result->id }}"
                        class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors duration-150">
                        {{-- Checkbox --}}
                        <td class="py-2 px-3">
                            @if($result->isPending())
                                <input type="checkbox"
                                       wire:model.live="selectedResults"
                                       value="{{ $result->id }}"
                                       class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            @endif
                        </td>

                        {{-- SKU --}}
                        <td class="py-2 px-3">
                            <span class="font-mono text-white">{{ $result->sku ?? '-' }}</span>
                        </td>

                        {{-- Name --}}
                        <td class="py-2 px-3">
                            <span class="text-gray-300 truncate max-w-xs block" title="{{ $result->name }}">
                                {{ Str::limit($result->name, 40) ?? '-' }}
                            </span>
                        </td>

                        {{-- External ID --}}
                        <td class="py-2 px-3">
                            <span class="font-mono text-gray-400">{{ $result->external_id ?? '-' }}</span>
                        </td>

                        {{-- Powiązania (Links) --}}
                        <td class="py-2 px-3">
                            @php
                                $links = $result->ppm_data['links'] ?? [];
                                $erpLinks = $links['erp'] ?? [];
                                $shopLinks = $links['shops'] ?? [];
                                $hasLinks = count($erpLinks) > 0 || count($shopLinks) > 0;

                                // Default colors (fallback)
                                $defaultErpColor = '#f97316'; // orange-500
                                $defaultShopColor = '#06b6d4'; // cyan-500
                            @endphp
                            @if($hasLinks)
                                <div class="flex flex-wrap gap-1">
                                    {{-- ERP Links with custom colors --}}
                                    @foreach($erpLinks as $erp)
                                        @php
                                            $erpColor = $erp['label_color'] ?? $defaultErpColor;
                                        @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                              style="background-color: {{ $erpColor }}20; color: {{ $erpColor }}; border: 1px solid {{ $erpColor }}50;"
                                              title="ERP: {{ $erp['connection_name'] }}">
                                            {{ Str::limit($erp['connection_name'], 15) }}
                                        </span>
                                    @endforeach
                                    {{-- Shop Links with custom colors --}}
                                    @foreach($shopLinks as $shop)
                                        @php
                                            $shopColor = $shop['label_color'] ?? $defaultShopColor;
                                        @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                              style="background-color: {{ $shopColor }}20; color: {{ $shopColor }}; border: 1px solid {{ $shopColor }}50;"
                                              title="Shop: {{ $shop['shop_name'] }}">
                                            {{ Str::limit($shop['shop_name'], 15) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- Match Status --}}
                        <td class="py-2 px-3">
                            @php
                                $matchColors = [
                                    'matched' => 'bg-green-600/20 text-green-400 border-green-500/30',
                                    'unmatched' => 'bg-yellow-600/20 text-yellow-400 border-yellow-500/30',
                                    'conflict' => 'bg-red-600/20 text-red-400 border-red-500/30',
                                    'multiple' => 'bg-blue-600/20 text-blue-400 border-blue-500/30',
                                    'already_linked' => 'bg-purple-600/20 text-purple-400 border-purple-500/30',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $matchColors[$result->match_status] ?? 'bg-gray-700 text-gray-300' }}">
                                {{ $result->getMatchStatusLabel() }}
                            </span>
                        </td>

                        {{-- Resolution Status --}}
                        <td class="py-2 px-3">
                            @php
                                $resolutionColors = [
                                    'pending' => 'bg-yellow-600/20 text-yellow-400 border-yellow-500/30',
                                    'linked' => 'bg-green-600/20 text-green-400 border-green-500/30',
                                    'created' => 'bg-blue-600/20 text-blue-400 border-blue-500/30',
                                    'ignored' => 'bg-gray-600/20 text-gray-400 border-gray-500/30',
                                    'error' => 'bg-red-600/20 text-red-400 border-red-500/30',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $resolutionColors[$result->resolution_status] ?? 'bg-gray-700 text-gray-300' }}">
                                {{ $result->getResolutionStatusLabel() }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="py-2 px-3 text-right">
                            @if($result->isPending())
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Link action (for links tab) --}}
                                    @if($activeTab === 'links' && $result->ppm_product_id)
                                        <button wire:click="linkResult({{ $result->id }}, {{ $result->ppm_product_id }})"
                                                class="px-2 py-1 text-xs text-green-400 hover:text-green-300 hover:bg-green-900/30 rounded"
                                                title="Połącz z produktem PPM">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                {{-- Link/chain icon --}}
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Create action (for missing_ppm tab) --}}
                                    @if($activeTab === 'missing_ppm')
                                        <button wire:click="createResult({{ $result->id }})"
                                                class="px-2 py-1 text-xs text-blue-400 hover:text-blue-300 hover:bg-blue-900/30 rounded"
                                                title="Utwórz jako draft">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Publish action (for missing_source tab) --}}
                                    @if($activeTab === 'missing_source' && $result->ppm_product_id)
                                        <button wire:click="publishToSource({{ $result->id }})"
                                                class="px-2 py-1 text-xs text-green-400 hover:text-green-300 hover:bg-green-900/30 rounded"
                                                title="Publikuj do źródła">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Ignore action --}}
                                    <button wire:click="ignoreResult({{ $result->id }})"
                                            class="px-2 py-1 text-xs text-gray-400 hover:text-gray-300 hover:bg-gray-700 rounded"
                                            title="Ignoruj">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </div>
                            @elseif($result->isLinked() && $result->ppm_product_id)
                                <a href="{{ route('products.edit', $result->ppm_product_id) }}"
                                   class="px-2 py-1 text-xs text-[#e0ac7e] hover:text-[#e0ac7e]/80"
                                   title="Zobacz produkt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($results->hasPages())
            <div class="px-4 py-3 border-t border-gray-700 bg-gray-800/50">
                {{ $results->links() }}
            </div>
        @endif
    @endif
</div>
