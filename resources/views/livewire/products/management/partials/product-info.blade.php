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
                <span class="text-dark-primary">
                    @if($is_active ?? false)
                        <i class="fas fa-check-circle text-green-400 mr-1"></i> Aktywny
                    @else
                        <i class="fas fa-times-circle text-red-400 mr-1"></i> Nieaktywny
                    @endif
                </span>
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
