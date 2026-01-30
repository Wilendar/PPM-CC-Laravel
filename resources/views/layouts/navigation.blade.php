{{-- Main Navigation --}}
<div class="space-y-1">
    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md 
              {{ request()->routeIs('dashboard') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
              }}">
        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('dashboard') ? 'text-blue-500' : 'text-gray-400' }}" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H10a2 2 0 01-2-2V5z"></path>
        </svg>
        Dashboard
    </a>

    {{-- Products (All users can read) --}}
    @can('products.read')
    <a href="{{ route('admin.products.index') }}" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
              {{ request()->routeIs('products.*') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
              }}">
        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('products.*') ? 'text-blue-500' : 'text-gray-400' }}" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        Produkty
        {{-- Product count badge --}}
        <span class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
            12.5k
        </span>
    </a>
    @endcan

    {{-- Categories --}}
    @can('categories.read')
    <a href="{{ route('admin.products.categories.index') }}" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
              {{ request()->routeIs('categories.*') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
              }}">
        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('categories.*') ? 'text-blue-500' : 'text-gray-400' }}" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        Kategorie
    </a>
    @endcan

    {{-- Separator --}}
    <hr class="my-4 border-gray-200 dark:border-gray-700">

    {{-- Manager+ Features --}}
    @hasanyrole('Admin|Manager')
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Zarządzanie
        </h3>
        
        {{-- Import/Export --}}
        @can('products.import')
        <a href="{{ route('admin.products.import') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('import.*')
                      ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('import.*') ? 'text-green-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Import/Export
        </a>
        @endcan

        {{-- CSV Import/Export (NEW SYSTEM - FAZA 6) --}}
        @can('products.import')
        <a href="{{ route('admin.csv.import') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('csv.*')
                      ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('csv.*') ? 'text-green-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            CSV Import/Export
            <span class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                Nowy
            </span>
        </a>
        @endcan

        {{-- Compatibility Management (ETAP_05d FAZA 1) --}}
        @can('products.manage')
        <a href="{{ route('admin.compatibility.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('compatibility.*')
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('compatibility.*') ? 'text-blue-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            Dopasowania Części
        </a>
        @endcan

        {{-- Supplier Management (ETAP_15) --}}
        @can('products.manage')
        <a href="{{ route('admin.suppliers.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.suppliers*')
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.suppliers*') ? 'text-blue-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
            </svg>
            Zarzadzanie dostawcami
        </a>
        @endcan

        {{-- Synchronization --}}
        @can('integrations.sync')
        <a href="{{ route('admin.shops.sync') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('sync.*')
                      ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('sync.*') ? 'text-purple-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Synchronizacja
        </a>
        @endcan
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    @endhasanyrole

    {{-- Role-specific sections --}}
    
    {{-- Admin Only --}}
    @role('Admin')
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">
            Administracja
        </h3>

        {{-- Users Management --}}
        <a href="{{ route('admin.users.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.users*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.users*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            Uzytkownicy
        </a>

        {{-- Roles Management --}}
        <a href="{{ route('admin.roles.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.roles*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.roles*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            Role
        </a>

        {{-- Permissions Management --}}
        <a href="{{ route('admin.permissions.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.permissions*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.permissions*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
            Uprawnienia
        </a>

        {{-- Sessions Management --}}
        <a href="{{ route('admin.sessions') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.sessions*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.sessions*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Sesje
        </a>

        {{-- Security Dashboard --}}
        <a href="{{ route('admin.security.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.security*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.security*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Bezpieczenstwo
        </a>

        {{-- Audit Logs --}}
        <a href="{{ route('admin.activity-log.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.activity-log*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.activity-log*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            Logi audytu
        </a>

        {{-- System Settings --}}
        <a href="{{ route('admin.system-settings.index') }}"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('admin.system*')
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.system*') ? 'text-red-500' : 'text-gray-400' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Ustawienia systemu
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    @endrole

    {{-- Warehouseman Features --}}
    @hasrole('Warehouseman')
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-blue-500 dark:text-blue-400 uppercase tracking-wider">
            Magazyn
        </h3>
        
        <a href="{{ route('admin.deliveries.index') }}" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('warehouse.*') 
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('warehouse.*') ? 'text-blue-500' : 'text-gray-400' }}" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Dostawy
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    @endhasrole

    {{-- Salesperson Features --}}
    @hasrole('Salesperson')
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-purple-500 dark:text-purple-400 uppercase tracking-wider">
            Sprzedaż
        </h3>
        
        <a href="{{ route('admin.orders.index') }}" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('sales.*') 
                      ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('sales.*') ? 'text-purple-500' : 'text-gray-400' }}" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M8 11v6h8v-6M8 11h8"></path>
            </svg>
            Zamówienia
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    @endhasrole

    {{-- Claims Features --}}
    @hasrole('Claims')
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-teal-500 dark:text-teal-400 uppercase tracking-wider">
            Reklamacje
        </h3>
        
        <a href="{{ route('admin.claims.index') }}" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('claims.*') 
                      ? 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('claims.*') ? 'text-teal-500' : 'text-gray-400' }}" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            Panel reklamacji
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    @endhasrole

    {{-- Common features --}}
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Konto
        </h3>
        
        {{-- Profile --}}
        <a href="{{ route('profile.edit') }}" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('profile.*') 
                      ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('profile.*') ? 'text-gray-500' : 'text-gray-400' }}" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Profil użytkownika
        </a>

        {{-- Search --}}
        @can('products.read')
        <a href="{{ route('admin.products.search') }}" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  {{ request()->routeIs('search*') 
                      ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' 
                  }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('search*') ? 'text-gray-500' : 'text-gray-400' }}" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Wyszukiwarka
            <kbd class="ml-auto text-xs text-gray-500 dark:text-gray-400">Ctrl+K</kbd>
        </a>
        @endcan
    </div>
</div>

{{-- Bottom section --}}
<div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <p>PPM v1.0</p>
        <p class="mt-1">© {{ date('Y') }} MPP TRADE</p>
    </div>
</div>