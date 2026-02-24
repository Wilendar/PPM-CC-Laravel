{{--
    Product Type Badge Component
    Displays a colored badge with dynamic color from database (ProductType model)

    Uses inline styles with dynamic HEX colors (same pattern as integration-status-badge.blade.php)
    because colors come from DB and cannot be predefined as CSS custom properties.

    @param \App\Models\ProductType|null $type - ProductType model instance
    @param string|null $name - Override badge text (takes priority over $type->name)
    @param string|null $color - Override badge color HEX (takes priority over $type->label_color)
    @param string $size - Badge size: 'xs', 'sm' (default), 'md'

    Usage (with model):
    <x-product-type-badge :type="$product->productType" />

    Usage (manual):
    <x-product-type-badge name="Motoryzacja" color="#3b82f6" />

    Usage (small):
    <x-product-type-badge :type="$type" size="xs" />

    @since 2026-02-24
    @see resources/views/components/integration-status-badge.blade.php
--}}

@props(['type' => null, 'name' => null, 'color' => null, 'size' => 'sm'])

@php
    $badgeName = $name ?? ($type?->name ?? 'Nieznany');
    $badgeColor = $color ?? ($type?->label_color ?? '#6b7280');

    // Convert HEX to RGB for rgba() background
    $r = hexdec(substr($badgeColor, 1, 2));
    $g = hexdec(substr($badgeColor, 3, 2));
    $b = hexdec(substr($badgeColor, 5, 2));

    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-[10px] rounded',
        'sm' => 'px-2 py-0.5 rounded-full text-xs',
        'md' => 'px-2.5 py-1 rounded-md text-xs',
        default => 'px-2 py-0.5 rounded-full text-xs',
    };
@endphp

<span class="inline-flex items-center font-medium {{ $sizeClasses }}"
      style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.15); color: {{ $badgeColor }}; border: 1px solid rgba({{ $r }},{{ $g }},{{ $b }},0.3);"
      {{ $attributes }}>
    {{ $badgeName }}
</span>
