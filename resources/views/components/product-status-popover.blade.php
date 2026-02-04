{{--
    Product Status Popover Component
    Comprehensive popover showing all product issues with Alpine.js

    @param ProductStatusDTO $status - The product status DTO
    @param Product $product - The product model (for accessing relations)

    Usage:
    <x-product-status-popover :status="$status" :product="$product" />

    @since 2026-02-04
    @see Plan_Projektu/synthetic-mixing-thunder.md
--}}

@props(['status', 'product'])

@php
    use App\DTOs\ProductStatusDTO;

    $hasIssues = $status->hasAnyIssues();
    $severity = $status->getSeverity();
    $issueLabels = ProductStatusDTO::getIssueLabels();
    $issueColors = ProductStatusDTO::getIssueColors();

    // Severity colors
    $severityConfig = match($severity) {
        'critical' => ['bg' => 'bg-red-900/50', 'border' => 'border-red-700', 'text' => 'text-red-400'],
        'warning' => ['bg' => 'bg-yellow-900/50', 'border' => 'border-yellow-700', 'text' => 'text-yellow-400'],
        default => ['bg' => 'bg-green-900/50', 'border' => 'border-green-700', 'text' => 'text-green-400'],
    };

    // Color map for issue types
    $colorMap = [
        'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-400'],
        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-400'],
        'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-400'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-400'],
        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-400'],
        'gray' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-400'],
        'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-400'],
    ];
@endphp

<div x-data="{
        open: false,
        popoverStyle: {},
        updatePosition() {
            const btn = this.$refs.trigger;
            const rect = btn.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const showAbove = spaceBelow < 300;

            this.popoverStyle = {
                position: 'fixed',
                left: rect.left + 'px',
                top: showAbove ? 'auto' : (rect.bottom + 8) + 'px',
                bottom: showAbove ? (window.innerHeight - rect.top + 8) + 'px' : 'auto',
                zIndex: 99999
            };
        }
     }"
     @mouseenter="updatePosition(); open = true"
     @mouseleave="open = false"
     class="relative inline-flex">

    {{-- Trigger Button - Shows summary icon --}}
    <button type="button"
            x-ref="trigger"
            class="inline-flex items-center justify-center w-6 h-6 rounded border {{ $severityConfig['bg'] }} {{ $severityConfig['border'] }} {{ $severityConfig['text'] }} cursor-pointer transition-all hover:scale-105"
            @click.stop="updatePosition(); open = !open">
        @if($hasIssues)
            {{-- Warning/Error icon with count --}}
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 text-[10px] font-bold rounded-full bg-red-500 text-white flex items-center justify-center">
                {{ $status->getIssueCount() > 9 ? '9+' : $status->getIssueCount() }}
            </span>
        @else
            {{-- Check icon for OK --}}
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        @endif
    </button>

    {{-- Popover Content (teleported to body for overflow escape) --}}
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.stop
             @mouseenter="open = true"
             @mouseleave="open = false"
             class="w-72"
             :style="popoverStyle"
             style="display: none;">

            <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="px-3 py-2 border-b border-gray-700 {{ $severityConfig['bg'] }}">
                <h4 class="text-sm font-medium text-white flex items-center gap-2">
                    @if($hasIssues)
                        <svg class="w-4 h-4 {{ $severityConfig['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        {{ $status->getIssueCount() }} {{ $status->getIssueCount() === 1 ? 'problem' : 'problemów' }}
                    @else
                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Wszystko w porządku
                    @endif
                </h4>
            </div>

            @if($hasIssues)
                <div class="p-3 space-y-3 max-h-64 overflow-y-auto">
                    {{-- Global Issues --}}
                    @if($status->hasGlobalIssues())
                        <div>
                            <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Problemy ogólne</h5>
                            <ul class="space-y-1">
                                @foreach($status->getActiveGlobalIssues() as $issue)
                                    @php
                                        $color = $issueColors[$issue] ?? 'gray';
                                        $colorClass = $colorMap[$color] ?? $colorMap['gray'];
                                    @endphp
                                    <li class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full {{ $colorClass['bg'] }} flex-shrink-0"></span>
                                        <span class="{{ $colorClass['text'] }}">{{ $issueLabels[$issue] ?? $issue }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Shop Issues --}}
                    @if(!empty($status->shopIssues))
                        <div>
                            <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Sklepy PrestaShop</h5>
                            <ul class="space-y-2">
                                @foreach($status->shopIssues as $shopId => $issues)
                                    @php
                                        $shopData = $product->shopData->firstWhere('shop_id', $shopId);
                                        $shop = $shopData?->shop;
                                        $shopColor = $shop?->label_color ?? '#06b6d4';
                                    @endphp
                                    <li class="text-xs">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $shopColor }};"></span>
                                            <span class="font-medium text-white">{{ $shop?->name ?? "Sklep #{$shopId}" }}</span>
                                        </div>
                                        <ul class="ml-3.5 space-y-0.5 text-gray-400">
                                            @foreach($issues as $issue)
                                                <li>• {{ $issueLabels[$issue] ?? $issue }}</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ERP Issues --}}
                    @if(!empty($status->erpIssues))
                        <div>
                            <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Systemy ERP</h5>
                            <ul class="space-y-2">
                                @foreach($status->erpIssues as $erpId => $issues)
                                    @php
                                        $erpData = $product->erpData->firstWhere('erp_connection_id', $erpId);
                                        $erp = $erpData?->erpConnection;
                                        $erpColor = $erp?->label_color ?? '#f97316';
                                    @endphp
                                    <li class="text-xs">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $erpColor }};"></span>
                                            <span class="font-medium text-white">{{ $erp?->instance_name ?? "ERP #{$erpId}" }}</span>
                                        </div>
                                        <ul class="ml-3.5 space-y-0.5 text-gray-400">
                                            @foreach($issues as $issue)
                                                <li>• {{ $issueLabels[$issue] ?? $issue }}</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Variant Issues --}}
                    @if(!empty($status->variantIssues))
                        <div>
                            <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                Warianty ({{ count($status->variantIssues) }})
                            </h5>
                            <ul class="space-y-1.5">
                                @foreach($status->variantIssues as $variantId => $issues)
                                    @php
                                        $variant = $product->variants->firstWhere('id', $variantId);
                                    @endphp
                                    <li class="text-xs">
                                        <span class="font-medium text-purple-400">{{ $variant?->sku ?? "#{$variantId}" }}</span>
                                        <span class="text-gray-400">
                                            - {{ collect($issues)->map(fn($i) => $issueLabels[$i] ?? $i)->join(', ') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Footer with action --}}
                <div class="px-3 py-2 border-t border-gray-700 bg-gray-800/50">
                    <a href="{{ route('products.edit', $product) }}"
                       class="text-xs text-orange-400 hover:text-orange-300 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edytuj produkt
                    </a>
                </div>
            @else
                {{-- No issues content --}}
                <div class="p-4 text-center">
                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400">Produkt jest kompletny i zsynchronizowany.</p>
                </div>
            @endif
        </div>
    </div>
    </template>
</div>
