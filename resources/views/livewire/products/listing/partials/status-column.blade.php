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
            {{-- Main popover trigger with full details --}}
            <x-product-status-popover :status="$status" :product="$product" />

            {{-- Quick visual indicators (icons only for at-a-glance view) --}}
            @if($status->hasAnyIssues())
                {{-- Global issues as icons --}}
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

                {{-- Shop issues as colored badges --}}
                @foreach($status->shopIssues as $shopId => $issues)
                    @php
                        $shopData = $product->shopData->firstWhere('shop_id', $shopId);
                        $shop = $shopData?->shop;
                    @endphp
                    @if($shop)
                        <x-integration-status-badge
                            :name="$shop->name"
                            :color="$shop->label_color"
                            :icon="$shop->label_icon ?? 'shopping-cart'"
                            :issues="$issues"
                            type="shop"
                        />
                    @endif
                @endforeach

                {{-- ERP issues as colored badges --}}
                @foreach($status->erpIssues as $erpId => $issues)
                    @php
                        $erpData = $product->erpData->firstWhere('erp_connection_id', $erpId);
                        $erp = $erpData?->erpConnection;
                    @endphp
                    @if($erp)
                        <x-integration-status-badge
                            :name="$erp->instance_name"
                            :color="$erp->label_color"
                            :icon="$erp->label_icon ?? 'database'"
                            :issues="$issues"
                            type="erp"
                        />
                    @endif
                @endforeach

                {{-- Variant issues summary --}}
                @if(!empty($status->variantIssues))
                    <x-product-status-icon type="variant_issues" :count="count($status->variantIssues)" />
                @endif
            @else
                {{-- All OK badge --}}
                <x-product-status-icon type="ok" />
            @endif
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
