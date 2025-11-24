{{-- Product Info (Edit Mode) --}}
@if($isEditMode)
    <div class="enterprise-card p-6 mt-6">
        <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
            Informacje o produkcie
        </h4>
        <div class="space-y-3 text-sm">
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
                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                    <span class="text-dark-muted">Sklepy:</span>
                    <span class="text-dark-primary font-semibold">{{ count($exportedShops) }}</span>
                </div>
            @endif
        </div>
    </div>
@endif
