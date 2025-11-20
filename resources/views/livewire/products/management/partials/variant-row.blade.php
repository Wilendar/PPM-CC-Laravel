{{-- ETAP_05b PHASE 6: Variant Row (PPM UI Standards Compliant) --}}
{{-- $variant variable passed from parent --}}

<td class="px-6 py-4">
    <div class="flex items-center space-x-2">
        <span class="font-mono text-sm text-gray-300">{{ $variant->sku }}</span>
        @if($variant->is_default)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-900/30 text-orange-200 border border-orange-700/50">
                <i class="fas fa-star text-xs mr-1"></i>
                Domyślny
            </span>
        @endif
    </div>
</td>

<td class="px-6 py-4">
    <span class="text-sm text-white">{{ $variant->name }}</span>
</td>

<td class="px-6 py-4">
    <div class="flex flex-wrap gap-2">
        @if($variant->attributes && $variant->attributes->count() > 0)
            @foreach($variant->attributes as $attribute)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-700 text-gray-200">
                    {{ $attribute->attributeType->name ?? 'N/A' }}: {{ $attribute->value }}
                </span>
            @endforeach
        @else
            <span class="text-xs text-gray-500 italic">Brak atrybutów</span>
        @endif
    </div>
</td>

<td class="px-6 py-4">
    @if($variant->is_active)
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-900/30 text-green-200 border border-green-700/50">
            <i class="fas fa-check-circle mr-1"></i>
            Aktywny
        </span>
    @else
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-700 text-gray-300 border border-gray-600">
            <i class="fas fa-times-circle mr-1"></i>
            Nieaktywny
        </span>
    @endif
</td>

<td class="px-6 py-4">
    <div class="flex items-center justify-end space-x-2">
        <button type="button"
                @click="$dispatch('edit-variant', {variantId: {{ $variant->id }}})"
                class="btn-enterprise-secondary text-xs px-3 py-1.5"
                title="Edytuj wariant">
            <i class="fas fa-edit"></i>
        </button>

        <button type="button"
                @click="$dispatch('duplicate-variant', {variantId: {{ $variant->id }}})"
                class="btn-enterprise-secondary text-xs px-3 py-1.5"
                title="Duplikuj wariant">
            <i class="fas fa-copy"></i>
        </button>

        @if(!$variant->is_default)
            <button wire:click="setDefaultVariant({{ $variant->id }})"
                    class="btn-enterprise-secondary text-xs px-3 py-1.5"
                    title="Ustaw jako domyślny">
                <i class="fas fa-star"></i>
            </button>
        @endif

        <button wire:click="deleteVariant({{ $variant->id }})"
                wire:confirm="Czy na pewno usunąć wariant '{{ $variant->name }}'?"
                class="text-xs px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                title="Usuń wariant">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</td>
