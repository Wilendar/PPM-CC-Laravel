{{-- Category Node Partial - Recursive Category Tree Display --}}
{{-- ETAP_07 FAZA 2B.2 - ProductForm PrestaShop Category Picker --}}

<div class="category-node" style="margin-left: {{ $level * 20 }}px;" wire:key="cat-node-{{ $shopId }}-{{ $category['id'] }}-{{ $level }}">
    <label class="flex items-center py-1 hover:bg-gray-700 hover:bg-opacity-30 px-2 rounded cursor-pointer transition-colors duration-150">
        {{-- Checkbox for category selection --}}
        <input type="checkbox"
               wire:model.live="shopData.{{ $shopId }}.prestashop_categories"
               value="{{ $category['id'] }}"
               id="ps-cat-{{ $shopId }}-{{ $category['id'] }}"
               class="form-checkbox h-4 w-4 text-mpp-orange border-gray-300 dark:border-gray-600 rounded focus:ring-mpp-orange focus:ring-2 transition-colors">

        {{-- Category name --}}
        <span class="ml-2 text-sm text-white flex-1">{{ $category['name'] }}</span>

        {{-- Children count badge --}}
        @if(count($category['children']) > 0)
            <span class="ml-2 text-xs text-gray-400 bg-gray-700 bg-opacity-50 px-2 py-0.5 rounded-full">
                {{ count($category['children']) }} {{ count($category['children']) === 1 ? 'podkategoria' : 'podkategorii' }}
            </span>
        @endif
    </label>

    {{-- Recursively render children --}}
    @if(count($category['children']) > 0)
        <div class="children-container">
            @foreach($category['children'] as $child)
                @include('livewire.products.partials.category-node', [
                    'category' => $child,
                    'shopId' => $shopId,
                    'level' => $level + 1
                ])
            @endforeach
        </div>
    @endif
</div>
