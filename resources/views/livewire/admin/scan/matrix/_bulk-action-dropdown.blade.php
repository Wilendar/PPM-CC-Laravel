{{--
    Partial: _bulk-action-dropdown.blade.php
    Dropdown jednej bulk action (Powiaz/Eksportuj/Ignoruj) z lista zrodel.

    Zmienne:
    - $actionAll: string - wire:click action dla "Wszystkie" (np. 'link_all')
    - $actionSource: string - wire:click action per source (np. 'link_source')
    - $label: string - tekst na przycisku (np. 'Powiaz')
    - $colorBg: string - Tailwind bg class (np. 'bg-blue-900/30')
    - $colorText: string - Tailwind text class (np. 'text-blue-400')
    - $colorBorder: string - Tailwind border class
    - $colorAccent: string - Tailwind accent color class
    - $icon: string - SVG path(s) for icon
    - $sources: array - from parent (component property)
--}}

<div x-data="{ open: false }" class="relative">
    {{-- Trigger button --}}
    <button @click="open = !open"
            class="flex items-center gap-1.5 px-3 py-1.5 {{ $colorBg }} {{ $colorText }} text-xs font-medium rounded-lg border {{ $colorBorder }} transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
        {{ $label }}
        <svg class="w-3 h-3 ml-0.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="absolute top-full left-0 mt-1 w-60 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 py-1">

        {{-- Wszystkie platformy --}}
        <button wire:click="bulkAction('{{ $actionAll }}')" @click="open = false"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm {{ $colorAccent }} font-medium hover:bg-gray-700/60 transition-colors">
            <span class="w-2.5 h-2.5 rounded-full bg-gradient-to-r from-blue-400 via-green-400 to-orange-400 flex-shrink-0"></span>
            Wszystkie platformy
        </button>

        {{-- Separator --}}
        <div class="border-t border-gray-700 my-1"></div>

        {{-- Poszczegolne zrodla --}}
        @foreach($sources as $source)
            @php
                $sourceKey = $source['type'] . '_' . $source['id'];
                $isShop = $source['is_shop'] ?? false;
                $sourceColor = $source['color'] ?? ($isShop ? '#06b6d4' : '#ea580c');
                $typeBadge = $isShop ? 'PS' : 'ERP';
            @endphp
            <button wire:click="bulkAction('{{ $actionSource }}', '{{ $sourceKey }}')" @click="open = false"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-700/60 hover:text-white transition-colors group">
                {{-- Colored dot matching column header --}}
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $sourceColor }};"></span>
                {{-- Source name --}}
                <span class="truncate">{{ $source['name'] }}</span>
                {{-- Type badge --}}
                <span class="ml-auto flex-shrink-0 text-[10px] px-1.5 py-0.5 rounded font-medium
                    {{ $isShop ? 'bg-cyan-900/40 text-cyan-400' : 'bg-orange-900/40 text-orange-400' }}">
                    {{ $typeBadge }}
                </span>
            </button>
        @endforeach
    </div>
</div>
