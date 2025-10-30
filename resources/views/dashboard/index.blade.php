@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Welcome Message --}}
        <div class="mb-8">
            <div class="bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if(Auth::user()->avatar)
                                <img src="{{ Storage::url(Auth::user()->avatar) }}" 
                                     alt="{{ Auth::user()->first_name }}" 
                                     class="h-12 w-12 rounded-full object-cover">
                            @else
                                <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                    <span class="text-lg font-medium text-blue-600 dark:text-blue-400">
                                        {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Witaj ponownie
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-white">
                                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm text-gray-500 dark:text-gray-400">
                                        <span class="sr-only">Rola: </span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                   @foreach(Auth::user()->roles as $role)
                                                       @switch($role->name)
                                                           @case('Admin') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 @break
                                                           @case('Manager') bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200 @break
                                                           @case('Editor') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200 @break
                                                           @case('Warehouseman') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 @break
                                                           @case('Salesperson') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200 @break
                                                           @case('Claims') bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-200 @break
                                                           @default bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200
                                                       @endswitch
                                                   @endforeach">
                                            {{ Auth::user()->getRoleNames()->first() ?? 'User' }}
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Ostatnie logowanie
                                </div>
                                <div class="text-sm font-medium text-white">
                                    {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->format('d.m.Y H:i') : 'Pierwsze logowanie' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="mb-8">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Products Count --}}
                @can('products.read')
                <div class="bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Produkty w systemie
                                    </dt>
                                    <dd class="text-lg font-medium text-white">
                                        12,547
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('products.index') }}" class="font-medium text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                Zobacz wszystkie produkty
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Categories Count --}}
                @can('categories.read')
                <div class="bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Kategorie aktywne
                                    </dt>
                                    <dd class="text-lg font-medium text-white">
                                        1,247
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('categories.index') }}" class="font-medium text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                Zarządzaj kategoriami
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Integrations Status --}}
                @can('integrations.read')
                <div class="bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Status synchronizacji
                                    </dt>
                                    <dd class="text-lg font-medium text-white">
                                        Aktywna
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('sync.status') }}" class="font-medium text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                Szczegóły synchronizacji
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- System Status --}}
                @role('Admin')
                <div class="bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        System PPM
                                    </dt>
                                    <dd class="text-lg font-medium text-white">
                                        Operacyjny
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                        <div class="text-sm">
                            <a href="{{ route('admin.system') }}" class="font-medium text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                Panel administracyjny
                            </a>
                        </div>
                    </div>
                </div>
                @endrole
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mb-8">
            <h3 class="text-lg leading-6 font-medium text-white mb-4">
                Szybkie akcje
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Product Actions --}}
                @can('products.create')
                <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 ring-4 ring-white dark:ring-gray-800">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">
                            <a href="{{ route('products.create') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Dodaj nowy produkt
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Utwórz nowy produkt z pełną specyfikacją, cenami i zdjęciami.
                        </p>
                    </div>
                    <span class="pointer-events-none absolute top-6 right-6 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500" aria-hidden="true">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z"></path>
                        </svg>
                    </span>
                </div>
                @endcan

                {{-- Import Actions --}}
                @can('products.import')
                <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-300 ring-4 ring-white dark:ring-gray-800">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">
                            <a href="{{ route('import.index') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Import produktów
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Importuj produkty z plików Excel lub synchronizuj z ERP.
                        </p>
                    </div>
                    <span class="pointer-events-none absolute top-6 right-6 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500" aria-hidden="true">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z"></path>
                        </svg>
                    </span>
                </div>
                @endcan

                {{-- Search Products --}}
                @can('products.read')
                <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-purple-500 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-purple-50 dark:bg-purple-900 text-purple-700 dark:text-purple-300 ring-4 ring-white dark:ring-gray-800">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">
                            <a href="{{ route('search') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Wyszukaj produkty
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Inteligentna wyszukiwarka z filtrami i podpowiedziami.
                        </p>
                    </div>
                    <span class="pointer-events-none absolute top-6 right-6 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500" aria-hidden="true">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z"></path>
                        </svg>
                    </span>
                </div>
                @endcan
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Recent Products --}}
            @can('products.read')
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white">
                        Ostatnio dodane produkty
                    </h3>
                    <div class="mt-6 flow-root">
                        <ul role="list" class="-my-5 divide-y divide-gray-700">
                            @for($i = 1; $i <= 5; $i++)
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $i }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate">
                                            Przykładowy produkt {{ $i }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            SKU: PROD-{{ str_pad($i, 6, '0', STR_PAD_LEFT) }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 text-sm text-gray-500 dark:text-gray-400">
                                        {{ now()->subHours($i)->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                            @endfor
                        </ul>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('products.index') }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600">
                            Zobacz wszystkie produkty
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            {{-- System Status --}}
            <div class="bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-white">
                        Status systemu
                    </h3>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-2 w-2 bg-green-400 rounded-full mr-3"></div>
                                <span class="text-sm text-white">Baza danych</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Operacyjna</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-2 w-2 bg-green-400 rounded-full mr-3"></div>
                                <span class="text-sm text-white">API Prestashop</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Połączone</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-2 w-2 bg-yellow-400 rounded-full mr-3"></div>
                                <span class="text-sm text-white">Synchronizacja ERP</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">W trakcie</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-2 w-2 bg-green-400 rounded-full mr-3"></div>
                                <span class="text-sm text-white">Import/Export</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Gotowy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection