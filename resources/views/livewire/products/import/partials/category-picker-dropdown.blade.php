{{-- ETAP_06 FAZA 5: Category picker - 3 kaskadowe dropdowny (L3 -> L4 -> L5) --}}
{{-- Baza (L1) i Wszystko (L2) sa auto-includowane przy zapisie --}}
{{-- NO x-teleport - breaks Livewire snapshots! Use high z-index + fixed positioning instead --}}
@php
    $pickerId = 'category-picker-' . $product->id;
    $productId = $product->id;
@endphp

    <div id="{{ $pickerId }}"
         class="import-dropdown-fixed-container"
         x-data="{
             visible: false,
             productId: {{ $productId }},
             init() {
                 // Use double RAF to ensure DOM is fully laid out after Livewire re-render
                 requestAnimationFrame(() => {
                     requestAnimationFrame(() => {
                         this.visible = true;
                         this.positionDropdown();
                     });
                 });
             },
             positionDropdown() {
                 const trigger = document.querySelector('tr[wire\\:key=\'pending-product-' + this.productId + '\'] .category-picker-trigger');
                 if (!trigger) return;

                 const rect = trigger.getBoundingClientRect();
                 const dropdown = this.$el;
                 const dropdownHeight = dropdown.offsetHeight || 400;

                 let top = rect.bottom + 4;
                 if (top + dropdownHeight > window.innerHeight - 20) {
                     top = rect.top - dropdownHeight - 4;
                 }

                 let left = rect.left;
                 if (left + 320 > window.innerWidth - 20) {
                     left = window.innerWidth - 340;
                 }

                 dropdown.style.top = top + 'px';
                 dropdown.style.left = left + 'px';
             }
         }"
         x-init="init()"
         x-show="visible"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @resize.window="positionDropdown()"
         @scroll.window="positionDropdown()"
         @click.outside="$wire.closeCategoryPicker()"
         @keydown.escape.window="$wire.closeCategoryPicker()"
         wire:key="category-picker-{{ $product->id }}"
         wire:ignore.self>

    {{-- Header --}}
    <div class="px-4 py-3 border-b border-gray-700 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-200">Wybierz kategorie</h3>
                <p class="text-xs text-gray-500 mt-0.5">Baza i Wszystko sa dodawane automatycznie</p>
            </div>
            <button wire:click="closeCategoryPicker"
                    class="text-gray-400 hover:text-white p-1 rounded hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Cascading dropdowns --}}
    <div class="p-4 space-y-3 flex-1 overflow-y-auto">
        @php
            $categoriesL3 = $this->getCategoriesL3();
            $categoriesL4 = $this->getCategoriesL4();
            $categoriesL5 = $this->getCategoriesL5();
        @endphp

        {{-- Level 3 dropdown (pierwszy widoczny poziom) --}}
        <div>
            <label class="block text-xs font-medium text-gray-400 mb-1">Kategoria L3</label>
            <select wire:model.live="selectedL3"
                    class="form-select-dark w-full text-sm">
                <option value="">-- wybierz --</option>
                @foreach($categoriesL3 as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Level 4 dropdown (zalezny od L3) --}}
        @if($selectedL3 && $categoriesL4->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Kategoria L4</label>
                <select wire:model.live="selectedL4"
                        class="form-select-dark w-full text-sm">
                    <option value="">-- wybierz (opcjonalnie) --</option>
                    @foreach($categoriesL4 as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Level 5 dropdown (zalezny od L4) --}}
        @if($selectedL4 && $categoriesL5->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Kategoria L5</label>
                <select wire:model.live="selectedL5"
                        class="form-select-dark w-full text-sm">
                    <option value="">-- wybierz (opcjonalnie) --</option>
                    @foreach($categoriesL5 as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Preview wybranej sciezki --}}
        @if($selectedL3)
            <div class="mt-3 pt-3 border-t border-gray-700/50">
                <span class="text-xs text-gray-500">Sciezka kategorii:</span>
                <div class="text-xs text-gray-300 mt-1 font-mono">
                    <span class="text-gray-500">Baza > Wszystko ></span>
                    @php
                        $l3Cat = $categoriesL3->find($selectedL3);
                        $l4Cat = $selectedL4 ? $categoriesL4->find($selectedL4) : null;
                        $l5Cat = $selectedL5 ? $categoriesL5->find($selectedL5) : null;
                    @endphp
                    {{ $l3Cat?->name ?? '' }}
                    @if($l4Cat)
                        > {{ $l4Cat->name }}
                    @endif
                    @if($l5Cat)
                        > {{ $l5Cat->name }}
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Footer z przyciskami --}}
    <div class="px-4 py-3 border-t border-gray-700 flex items-center justify-between bg-gray-800/50 flex-shrink-0">
        <span class="text-xs text-gray-500">
            @if($selectedL3)
                <svg class="w-3 h-3 inline text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Kategoria wybrana
            @else
                Wybierz kategorie
            @endif
        </span>
        <div class="flex items-center gap-2">
            <button wire:click="closeCategoryPicker"
                    class="px-3 py-1.5 text-xs text-gray-400 hover:text-white bg-gray-700 hover:bg-gray-600 rounded transition-colors">
                Anuluj
            </button>
            <button wire:click="saveCategories"
                    class="px-3 py-1.5 text-xs text-white bg-blue-600 hover:bg-blue-500 rounded transition-colors
                           {{ !$selectedL3 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    @if(!$selectedL3) disabled @endif>
                Zapisz
            </button>
        </div>
    </div>
    </div>
