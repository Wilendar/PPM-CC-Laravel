{{-- Export Summary Bar --}}
@php
    $catCount = count($filterCategoryIds);
    $totalProducts = $this->exportProductsCount;
    $excludedCount = count($excludedProductIds);
    $exportCount = max(0, $totalProducts - $excludedCount);
@endphp

<div class="export-filter-browser__summary">
    <div class="export-filter-browser__summary-item">
        <span>Kategorie:</span>
        <span class="export-filter-browser__summary-value">{{ $catCount }}</span>
    </div>
    <div class="export-filter-browser__summary-item">
        <span>Produkty:</span>
        <span class="export-filter-browser__summary-value">{{ number_format($totalProducts, 0, ',', ' ') }}</span>
    </div>
    @if($excludedCount > 0)
        <div class="export-filter-browser__summary-item">
            <span>Wykluczone:</span>
            <span class="export-filter-browser__summary-value export-filter-browser__summary-value--excluded">{{ $excludedCount }}</span>
        </div>
    @endif
    <div class="export-filter-browser__summary-item">
        <span>Do eksportu:</span>
        <span class="export-filter-browser__summary-value export-filter-browser__summary-value--export">{{ number_format($exportCount, 0, ',', ' ') }}</span>
    </div>
</div>
