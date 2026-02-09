{{--
    Integration Status Badge Component
    Displays a compact badge for per-integration status (OK, issues, or syncing)

    Uses label_color and label_icon from ERPConnection/PrestaShopShop
    per INTEGRATION_LABELS.md specification

    @param string $name - Integration name (shop/ERP instance name)
    @param string $color - Hex color from label_color (#RRGGBB)
    @param string $icon - Icon name from label_icon
    @param bool $hasIssues - Whether the integration has issues (default: true for backward compat)
    @param array $issues - Array of issue types ['basic', 'desc', 'physical', 'images']
    @param string $type - 'shop' or 'erp'
    @param string|null $syncStatus - Active sync status ('pending', 'running', or null)

    Usage (with issues):
    <x-integration-status-badge
        :name="$shop->name"
        :color="$shop->label_color"
        :icon="$shop->label_icon"
        :hasIssues="true"
        :issues="['basic', 'desc']"
        type="shop"
    />

    Usage (OK status):
    <x-integration-status-badge
        :name="$shop->name"
        :color="$shop->label_color"
        :icon="$shop->label_icon"
        :hasIssues="false"
        :issues="[]"
        type="shop"
    />

    Usage (syncing):
    <x-integration-status-badge
        :name="$shop->name"
        :color="$shop->label_color"
        :icon="$shop->label_icon"
        :hasIssues="false"
        :issues="[]"
        type="shop"
        syncStatus="running"
    />

    @since 2026-02-04
    @updated 2026-02-05 - Added syncStatus support for active sync jobs
    @see .Release_docs/INTEGRATION_LABELS.md
    @see Plan_Projektu/synthetic-mixing-thunder.md (section 11.7)
--}}

@props(['name', 'color', 'icon', 'hasIssues' => true, 'issues' => [], 'type' => 'shop', 'syncStatus' => null])

@php
    // Issue labels in Polish
    $issueLabels = [
        'basic' => 'Dane podstawowe',
        'desc' => 'Opisy',
        'physical' => 'Wymiary/waga',
        'images' => 'Zdjęcia',
        'attributes' => 'Atrybuty',
        'compatibility' => 'Dopasowania',
        'no_desc' => 'Brak opisu',
    ];

    // Sync status labels
    $syncStatusLabels = [
        'pending' => 'Oczekuje na synchronizację',
        'running' => 'Synchronizacja w toku',
    ];

    // Determine state: syncing takes priority over issues
    $isSyncing = !empty($syncStatus) && in_array($syncStatus, ['pending', 'running']);

    // Build tooltip text based on status
    if ($isSyncing) {
        $tooltipText = "{$name}: " . ($syncStatusLabels[$syncStatus] ?? 'Synchronizacja...');
    } elseif ($hasIssues && !empty($issues)) {
        $tooltipIssues = collect($issues)
            ->map(fn($issue) => $issueLabels[$issue] ?? $issue)
            ->join(', ');
        $tooltipText = "{$name}: {$tooltipIssues}";
    } else {
        $tooltipText = "{$name} - OK";
    }

    // Default color if not provided
    $color = $color ?? ($type === 'shop' ? '#06b6d4' : '#f97316');

    // Style adjustments based on state
    // Syncing: use yellow/amber tones
    if ($isSyncing) {
        $bgOpacity = '25';
        $borderOpacity = '50';
        // Use amber color for syncing state
        $syncColor = '#f59e0b';
    } else {
        $bgOpacity = $hasIssues ? '30' : '15';
        $borderOpacity = $hasIssues ? '60' : '40';
        $syncColor = null;
    }

    // Icon SVG paths based on icon name
    $iconPaths = [
        'database' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
        'shopping-cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'shopping-bag' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'cube' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'cog' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'globe' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'server' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
        'link' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'cloud' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
    ];

    $iconPath = $iconPaths[$icon] ?? $iconPaths['cog'];
@endphp

@if($isSyncing)
    {{-- SYNCING STATE: yellow/amber badge with spinner --}}
    <span class="inline-flex items-center justify-center gap-0.5 h-6 px-1.5 rounded text-xs cursor-help transition-colors hover:opacity-80"
          style="background-color: {{ $syncColor }}{{ $bgOpacity }}; color: {{ $syncColor }}; border: 1px solid {{ $syncColor }}{{ $borderOpacity }};"
          title="{{ $tooltipText }}">
        {{-- Integration icon --}}
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>

        {{-- Spinner icon for syncing --}}
        <svg class="w-3.5 h-3.5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
            @if($syncStatus === 'pending')
                {{-- Clock icon for pending --}}
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            @else
                {{-- Rotating arrows for running --}}
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            @endif
        </svg>
    </span>
@else
    {{-- NORMAL STATE: OK or issues --}}
    <span class="inline-flex items-center justify-center gap-0.5 h-6 px-1.5 rounded text-xs cursor-help transition-colors hover:opacity-80"
          style="background-color: {{ $color }}{{ $bgOpacity }}; color: {{ $color }}; border: 1px solid {{ $color }}{{ $borderOpacity }};"
          title="{{ $tooltipText }}">
        {{-- Integration icon --}}
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>

        {{-- Status: checkmark (OK) or issue count --}}
        @if($hasIssues && count($issues) > 0)
            <span class="font-semibold text-[10px] leading-none">{{ count($issues) }}</span>
        @else
            {{-- Checkmark for OK status --}}
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        @endif
    </span>
@endif
