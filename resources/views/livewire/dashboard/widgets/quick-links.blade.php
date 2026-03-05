<div class="dashboard-widget" role="region" aria-label="Szybki dostep">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Szybki dostep</span>
        <div class="dashboard-widget__icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
        </div>
    </div>

    {{-- Links grid - responsive 5 cols on full width --}}
    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3">
        @foreach ($links as $link)
            <a wire:key="qlink-{{ $loop->index }}"
               href="{{ $link['allowed'] ? $link['url'] : '#' }}"
               @class([
                   'group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                   'text-gray-300 hover:bg-gray-700/50 hover:text-white' => $link['allowed'],
                   'sidebar-link-no-access' => !$link['allowed'],
               ])
               @unless($link['allowed'])
                   tabindex="-1"
                   aria-disabled="true"
               @endunless
            >
                <div @class([
                    'flex items-center justify-center w-8 h-8 rounded-lg flex-shrink-0',
                    'bg-gray-700/50 text-gray-400 group-hover:text-white' => $link['allowed'],
                    'bg-gray-800/50 text-gray-600' => !$link['allowed'],
                ])>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="{{ $link['icon'] }}" />
                    </svg>
                </div>
                <span class="truncate">{{ $link['label'] }}</span>
                @unless($link['allowed'])
                    <span class="ml-auto text-xs text-red-400/70 font-semibold whitespace-nowrap">BRAK DOSTEPU</span>
                @endunless
            </a>
        @endforeach
    </div>
</div>
