{{-- Single product row in export table --}}
@php
    $thumbnail = $product->media->first();
    $price = $product->validPrices->first();
    $stockTotal = $product->activeStock->sum('available_quantity');
    $statusName = $product->productStatus?->name ?? ($product->is_active ? 'Aktywny' : 'Nieaktywny');
    $statusColor = $product->productStatus?->color ?? ($product->is_active ? '#34d399' : '#6b7280');
@endphp

<tr wire:key="export-row-{{ $product->id }}"
    class="{{ $isExcluded ? 'export-filter-browser__row--excluded' : '' }}">

    {{-- Checkbox --}}
    <td class="export-filter-browser__checkbox-cell">
        <input type="checkbox"
               wire:click="toggleProductExclusion({{ $product->id }})"
               @checked(!$isExcluded)
               class="checkbox-enterprise">
    </td>

    {{-- Mini image --}}
    <td class="export-filter-browser__mini-cell">
        @if($thumbnail && $thumbnail->url)
            <img src="{{ $thumbnail->url }}" alt="" class="export-filter-browser__mini-img" loading="lazy">
        @else
            <div class="export-filter-browser__mini-placeholder">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </td>

    {{-- SKU --}}
    <td>
        <span class="export-filter-browser__sku">{{ $product->sku ?? '-' }}</span>
    </td>

    {{-- Name --}}
    <td>
        <span class="export-filter-browser__name" title="{{ $product->name }}">{{ $product->name }}</span>
    </td>

    {{-- Price --}}
    <td>
        @if($price)
            <span class="export-filter-browser__price">
                {{ number_format($price->price_net, 2, ',', ' ') }}
            </span>
        @else
            <span class="export-filter-browser__price text-gray-600">-</span>
        @endif
    </td>

    {{-- Stock --}}
    <td>
        @php
            $stockClass = 'export-filter-browser__stock';
            if ($stockTotal <= 0) $stockClass .= ' export-filter-browser__stock--zero';
            elseif ($stockTotal <= 5) $stockClass .= ' export-filter-browser__stock--low';
            else $stockClass .= ' export-filter-browser__stock--ok';
        @endphp
        <span class="{{ $stockClass }}">{{ (int) $stockTotal }}</span>
    </td>

    {{-- Status --}}
    <td>
        <span class="export-filter-browser__status">
            <span class="export-filter-browser__status-dot {{ $product->is_active ? 'export-filter-browser__status-dot--active' : 'export-filter-browser__status-dot--inactive' }}"></span>
            {{ $statusName }}
        </span>
    </td>
</tr>
