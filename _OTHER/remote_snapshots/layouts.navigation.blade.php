<nav class="space-y-1">
    <a href="{{ route('dashboard') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
        Dashboard
    </a>
    @auth
        @if(method_exists(auth()->user(), 'hasAnyRole') && auth()->user()->hasAnyRole(['Admin','admin']))
            <a href="{{ route('admin.dashboard') }}"
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                Admin Panel
            </a>
        @endif
    @endauth
</nav>
