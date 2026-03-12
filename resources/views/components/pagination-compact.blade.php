@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginacja" class="pagination-compact">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-compact__btn pagination-compact__btn--disabled" aria-disabled="true">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Poprzednia
            </span>
        @else
            <button wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                    class="pagination-compact__btn">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Poprzednia
            </button>
        @endif

        {{-- Page numbers --}}
        <div class="pagination-compact__pages">
            @php
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();

                // Show max 5 pages around current
                $start = max(1, $currentPage - 2);
                $end = min($lastPage, $currentPage + 2);

                // Adjust range to always show 5 pages when possible
                if ($end - $start < 4) {
                    if ($start === 1) {
                        $end = min($lastPage, $start + 4);
                    } elseif ($end === $lastPage) {
                        $start = max(1, $end - 4);
                    }
                }
            @endphp

            @if ($start > 1)
                <button wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')"
                        class="pagination-compact__page">1</button>
                @if ($start > 2)
                    <span class="pagination-compact__dots">&hellip;</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $currentPage)
                    <span class="pagination-compact__page pagination-compact__page--active"
                          aria-current="page">{{ $page }}</span>
                @else
                    <button wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                            class="pagination-compact__page">{{ $page }}</button>
                @endif
            @endfor

            @if ($end < $lastPage)
                @if ($end < $lastPage - 1)
                    <span class="pagination-compact__dots">&hellip;</span>
                @endif
                <button wire:click="gotoPage({{ $lastPage }}, '{{ $paginator->getPageName() }}')"
                        class="pagination-compact__page">{{ $lastPage }}</button>
            @endif
        </div>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                    class="pagination-compact__btn">
                Nastepna
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @else
            <span class="pagination-compact__btn pagination-compact__btn--disabled" aria-disabled="true">
                Nastepna
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </span>
        @endif
    </nav>
@endif
