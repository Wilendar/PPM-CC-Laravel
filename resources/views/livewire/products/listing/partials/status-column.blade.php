{{--
    Product Status Column Partial
    Displays compact status indicators with popover for details

    @param Product $product - The product model
    @param ProductStatusDTO $status - The product status DTO

    Usage in product-list.blade.php:
    @include('livewire.products.listing.partials.status-column', [
        'product' => $product,
        'status' => $this->productStatuses[$product->id] ?? null
    ])

    @since 2026-02-04
    @see Plan_Projektu/synthetic-mixing-thunder.md
--}}

@php
    use App\DTOs\ProductStatusDTO;
@endphp

<td class="px-3 py-2" @click.stop>
    <div class="flex flex-wrap items-center gap-1 max-w-[200px]">
        @if($status)
            {{-- GRACE PERIOD: Awaiting validation state --}}
            @if($status->isAwaitingValidation)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-yellow-900/30 text-yellow-400 border border-yellow-700/50"
                      title="Oczekiwanie na pelny import - walidacja za {{ $status->gracePeriodExpiresAt ? $status->gracePeriodExpiresAt->diffForHumans() : 'chwile' }}">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="hidden sm:inline">Import...</span>
                </span>

                {{-- Still show connected integrations during grace period --}}
                @if($status->hasConnectedIntegrations())
                    @foreach($status->connectedShops as $shopId => $shopInfo)
                        <x-integration-status-badge
                            :name="$shopInfo['name']"
                            :color="'#' . ltrim($shopInfo['color'], '#')"
                            :icon="$shopInfo['icon']"
                            :hasIssues="false"
                            :issues="[]"
                            type="shop"
                        />
                    @endforeach
                    @foreach($status->connectedErps as $erpId => $erpInfo)
                        <x-integration-status-badge
                            :name="$erpInfo['name']"
                            :color="'#' . ltrim($erpInfo['color'], '#')"
                            :icon="$erpInfo['icon']"
                            :hasIssues="false"
                            :issues="[]"
                            type="erp"
                        />
                    @endforeach
                @endif
            @else
                {{-- NORMAL: Standard validation display --}}

                {{-- Main popover trigger with full details --}}
                <x-product-status-popover :status="$status" :product="$product" />

                {{-- Global issues as icons (if any) --}}
                @if($status->globalIssues[ProductStatusDTO::ISSUE_ZERO_PRICE] ?? false)
                    <x-product-status-icon type="zero_price" />
                @endif

                @if($status->globalIssues[ProductStatusDTO::ISSUE_LOW_STOCK] ?? false)
                    <x-product-status-icon type="low_stock" />
                @endif

                @if($status->globalIssues[ProductStatusDTO::ISSUE_NO_IMAGES] ?? false)
                    <x-product-status-icon type="no_images" />
                @endif

                @if($status->globalIssues[ProductStatusDTO::ISSUE_NOT_IN_PRESTASHOP] ?? false)
                    <x-product-status-icon type="not_in_prestashop" />
                @endif

                {{-- ALL connected integrations (always show, with OK checkmark, issue count, or sync status) --}}
            @if($status->hasConnectedIntegrations())
                {{-- Connected shops --}}
                @foreach($status->connectedShops as $shopId => $shopInfo)
                    <x-integration-status-badge
                        :name="$shopInfo['name']"
                        :color="'#' . ltrim($shopInfo['color'], '#')"
                        :icon="$shopInfo['icon']"
                        :hasIssues="$shopInfo['hasIssues']"
                        :issues="$status->shopIssues[$shopId] ?? []"
                        :syncStatus="$shopInfo['syncStatus'] ?? null"
                        type="shop"
                    />
                @endforeach

                {{-- Connected ERPs --}}
                @foreach($status->connectedErps as $erpId => $erpInfo)
                    <x-integration-status-badge
                        :name="$erpInfo['name']"
                        :color="'#' . ltrim($erpInfo['color'], '#')"
                        :icon="$erpInfo['icon']"
                        :hasIssues="$erpInfo['hasIssues']"
                        :issues="$status->erpIssues[$erpId] ?? []"
                        :syncStatus="$erpInfo['syncStatus'] ?? null"
                        type="erp"
                    />
                @endforeach
            @elseif(!$status->hasGlobalIssues())
                {{-- No integrations and no global issues - show generic OK --}}
                <span class="inline-flex items-center justify-center h-6 px-1.5 rounded text-xs bg-gray-700/30 text-gray-500 border border-gray-600" title="Brak integracji">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </span>
            @endif

                {{-- Variant issues summary --}}
                @if(!empty($status->variantIssues))
                    <x-product-status-icon type="variant_issues" :count="count($status->variantIssues)" />
                @endif
            @endif {{-- END: Normal validation display --}}
        @else
            {{-- Loading/No data state --}}
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-700/50 text-gray-400">
                <svg class="w-3 h-3 animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
        @endif
    </div>
</td>
