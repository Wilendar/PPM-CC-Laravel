{{-- Single category item (flat list mode) - FAZA 9.7b --}}
@php
    $catId = (int) ($category['id'] ?? 0);
    $catName = $category['name'] ?? 'Unknown';
    $catLevel = (int) ($category['level'] ?? 0);
    $isSelected = in_array($catId, $selectedCategoryIds);
@endphp

<label class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-colors
              {{ $isSelected ? 'bg-purple-900/30 border border-purple-700/50' : 'hover:bg-gray-700/50' }}">
    <input type="checkbox"
           wire:click="toggleCategory({{ $catId }})"
           @checked($isSelected)
           class="form-checkbox-dark w-4 h-4 rounded border-gray-600 text-purple-500 focus:ring-purple-500">
    <span class="flex-1 text-sm {{ $isSelected ? 'text-white font-medium' : 'text-gray-300' }}">
        {{ $catName }}
    </span>
    @if($catLevel > 0)
        <span class="text-xs text-gray-500">L{{ $catLevel }}</span>
    @endif
</label>
