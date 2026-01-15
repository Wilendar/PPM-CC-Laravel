{{-- Simple Pagination for Dark Theme --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-800 border border-gray-700 cursor-not-allowed rounded-lg">
                    Poprzednia
                </span>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700">
                    Poprzednia
                </button>
            @endif

            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700">
                    Nastepna
                </button>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-gray-800 border border-gray-700 cursor-not-allowed rounded-lg">
                    Nastepna
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-400">
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    -
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    z
                    <span class="font-medium">{{ $paginator->total() }}</span>
                </p>
            </div>

            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1.5 text-sm text-gray-500 bg-gray-800 border border-gray-700 rounded cursor-not-allowed">
                        &laquo;
                    </span>
                @else
                    <button wire:click="previousPage" class="px-3 py-1.5 text-sm text-gray-300 bg-gray-800 border border-gray-700 rounded hover:bg-gray-700 transition-colors">
                        &laquo;
                    </button>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-3 py-1.5 text-sm text-gray-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="px-3 py-1.5 text-sm font-medium text-blue-400 bg-blue-500/20 border border-blue-500/40 rounded">
                                    {{ $page }}
                                </span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="px-3 py-1.5 text-sm text-gray-300 bg-gray-800 border border-gray-700 rounded hover:bg-gray-700 transition-colors">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button wire:click="nextPage" class="px-3 py-1.5 text-sm text-gray-300 bg-gray-800 border border-gray-700 rounded hover:bg-gray-700 transition-colors">
                        &raquo;
                    </button>
                @else
                    <span class="px-3 py-1.5 text-sm text-gray-500 bg-gray-800 border border-gray-700 rounded cursor-not-allowed">
                        &raquo;
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
