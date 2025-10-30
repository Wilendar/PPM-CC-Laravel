{{-- User Menu Dropdown --}}
@auth
<div x-data="{ open: false }" class="relative">
    {{-- Dropdown Toggle --}}
    <button @click="open = !open" 
            class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            id="user-menu-button"
            aria-expanded="false"
            aria-haspopup="true">
        <span class="sr-only">Open user menu</span>
        
        {{-- User Avatar --}}
        @if(Auth::user()->avatar)
            <img src="{{ Storage::url(Auth::user()->avatar) }}" 
                 alt="{{ Auth::user()->first_name }}" 
                 class="h-8 w-8 rounded-full object-cover">
        @else
            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                <span class="text-xs font-medium text-gray-300">
                    {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                </span>
            </div>
        @endif
        
        {{-- Dropdown Arrow --}}
        <svg class="ml-2 -mr-0.5 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-gray-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-700 z-50"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="user-menu-button">
        
        {{-- User Info Section --}}
        <div class="px-4 py-3" role="none">
            <div class="flex items-center">
                {{-- Large Avatar --}}
                <div class="flex-shrink-0">
                    @if(Auth::user()->avatar)
                        <img src="{{ Storage::url(Auth::user()->avatar) }}" 
                             alt="{{ Auth::user()->first_name }}" 
                             class="h-12 w-12 rounded-full object-cover">
                    @else
                        <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-lg font-medium text-gray-300">
                                {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                            </span>
                        </div>
                    @endif
                </div>
                
                {{-- User Details --}}
                <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">
                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ Auth::user()->email }}
                    </p>
                    
                    {{-- Role Badges --}}
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach(Auth::user()->roles as $role)
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                       @switch($role->name)
                                           @case('Admin') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 @break
                                           @case('Manager') bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200 @break
                                           @case('Editor') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200 @break
                                           @case('Warehouseman') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 @break
                                           @case('Salesperson') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200 @break
                                           @case('Claims') bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-200 @break
                                           @default bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200
                                       @endswitch">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Section --}}
        <div class="py-1" role="none">
            <a href="{{ route('profile.edit') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profil użytkownika
            </a>

            <a href="{{ route('profile.sessions') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Aktywne sesje
                {{-- Active sessions count --}}
                <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    3
                </span>
            </a>

            <a href="{{ route('profile.activity') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Historia aktywności
            </a>
        </div>

        {{-- Admin Section --}}
        @role('Admin')
        <div class="py-1" role="none">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Administracja
            </div>
            
            <a href="{{ route('admin.dashboard') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Panel administracyjny
            </a>

            <a href="{{ route('admin.system-info') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Informacje o systemie
            </a>
        </div>
        @endrole

        {{-- Help & Support --}}
        <div class="py-1" role="none">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Pomoc
            </div>
            
            <a href="{{ route('help.index') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Centrum pomocy
            </a>

            <a href="{{ route('help.shortcuts') }}" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a1 1 0 01-1-1V9a1 1 0 011-1h1a2 2 0 100-4H4a1 1 0 01-1-1V4a1 1 0 011-1h3a1 1 0 011 1v1z"></path>
                </svg>
                Skróty klawiszowe
            </a>

            <a href="mailto:support@mpptrade.eu" 
               class="group flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" 
               role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Kontakt z pomocą techniczną
            </a>
        </div>

        {{-- Logout Section --}}
        <div class="py-1" role="none">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="group flex items-center w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 dark:hover:text-red-400" 
                        role="menuitem">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-red-500" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Wyloguj się
                </button>
            </form>
        </div>

        {{-- Footer Info --}}
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50">
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <div class="flex justify-between">
                    <span>Ostatnie logowanie:</span>
                    <span>{{ Auth::user()->last_login_at ? Auth::user()->last_login_at->format('d.m.Y H:i') : 'Nigdy' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>IP:</span>
                    <span>{{ request()->ip() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Sesja wygasa:</span>
                    <span id="session-countdown">Za {{ config('session.lifetime') }} min</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Session Countdown Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownElement = document.getElementById('session-countdown');
    const sessionLifetime = {{ config('session.lifetime') }}; // minutes
    let remainingTime = sessionLifetime * 60; // convert to seconds
    
    function updateCountdown() {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        if (remainingTime > 0) {
            countdownElement.textContent = `Za ${minutes}:${seconds.toString().padStart(2, '0')}`;
            remainingTime--;
        } else {
            countdownElement.textContent = 'Sesja wygasła';
            countdownElement.classList.add('text-red-600');
        }
    }
    
    // Update every second
    setInterval(updateCountdown, 1000);
});
</script>
@endauth