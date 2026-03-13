{{-- Product Info (Edit Mode) --}}
@if($isEditMode)
    <div class="enterprise-card p-6 mt-6">
        <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
            Informacje o produkcie
        </h4>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-dark-muted">Nazwa:</span>
                <span class="text-dark-primary font-semibold truncate ml-2" title="{{ $name }}">{{ Str::limit($name, 30) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-dark-muted">SKU:</span>
                <span class="text-dark-primary font-semibold">{{ $sku }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-dark-muted">Status:</span>
                <div x-data="{
                    open: false,
                    posTop: '0px', posLeft: '0px',
                    toggle() {
                        if (!this.open) {
                            const r = this.$refs.btn.getBoundingClientRect();
                            const s = document.documentElement.clientWidth / window.innerWidth;
                            this.posTop = (r.bottom / s + 4) + 'px';
                            this.posLeft = ((r.right / s) - 192) + 'px';
                        }
                        this.open = !this.open;
                    }
                }">
                    @php
                        $currentStatus = \App\Models\ProductStatus::find($product_status_id);
                    @endphp
                    <button x-ref="btn" @click="toggle()" type="button"
                            class="product-status-select inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-700">
                        <span class="product-status-dot" style="background-color: {{ $currentStatus?->color ?? '#6b7280' }}"></span>
                        {{ $currentStatus?->name ?? 'Brak statusu' }}
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <template x-teleport="body">
                        <div x-show="open" @click.away="open = false" x-transition
                             class="product-status-teleport-dropdown"
                             :style="'top:' + posTop + ';left:' + posLeft">
                            @foreach($this->availableStatuses as $status)
                                <button wire:click="changeProductStatus({{ $status['id'] }})"
                                        @click="open = false" type="button"
                                        class="product-status-option w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors
                                               {{ ($product_status_id ?? null) == $status['id'] ? 'bg-gray-700/50' : '' }}">
                                    <span class="product-status-dot" style="background-color: {{ $status['color'] }}"></span>
                                    {{ $status['name'] }}
                                    @if(!$status['is_active_equivalent'])
                                        <span class="ml-auto text-xs text-red-400">(nieaktywny)</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </template>
                </div>
            </div>
            @if(!empty($exportedShops))
                <div class="flex justify-between items-start py-2 border-b border-gray-700">
                    <span class="text-dark-muted">Sklepy:</span>
                    <div class="flex flex-wrap gap-1 justify-end ml-2">
                        @foreach($exportedShops as $shopId)
                            @php
                                $shop = \App\Models\PrestaShopShop::find($shopId);
                            @endphp
                            @if($shop)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border"
                                      style="{{ $shop->label_badge_style }}">
                                    <i class="fas fa-{{ $shop->label_icon }} mr-1"></i>
                                    {{ $shop->name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            @if($product && $product->erpData->isNotEmpty())
                <div class="flex justify-between items-start py-2 border-b border-gray-700">
                    <span class="text-dark-muted">ERP:</span>
                    <div class="flex flex-wrap gap-1 justify-end ml-2">
                        @foreach($product->erpData as $erpEntry)
                            @php
                                $conn = \App\Models\ERPConnection::find($erpEntry->erp_connection_id);
                            @endphp
                            @if($conn)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border"
                                      style="{{ $conn->label_badge_classes }}">
                                    <i class="fas fa-{{ $conn->label_icon }} mr-1"></i>
                                    {{ $conn->instance_name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
