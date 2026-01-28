@extends('layouts.admin')

@section('title', 'Profil - Admin PPM')
@section('breadcrumb', 'Profil')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Profile Header --}}
    <div class="bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-700/50 shadow-lg overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-[#e0ac7e]/20 to-transparent p-6 border-b border-gray-700/50">
            <div class="flex items-center space-x-6">
                {{-- Avatar --}}
                @if(auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                         alt="Avatar"
                         class="w-20 h-20 rounded-full object-cover border-2 border-[#e0ac7e]/50 shadow-lg">
                @else
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#e0ac7e] to-[#c49a6c] flex items-center justify-center text-3xl font-bold text-gray-900 shadow-lg">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @endif

                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-white">{{ auth()->user()->name ?? 'Nieznany' }}</h1>
                    <p class="text-gray-400 mt-1">{{ auth()->user()->email ?? '' }}</p>

                    @if(auth()->user()->roles->count() > 0)
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach(auth()->user()->roles as $role)
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            @if($role->name === 'Admin') bg-red-500/20 text-red-400 border border-red-500/30
                            @elseif($role->name === 'Manager') bg-orange-500/20 text-orange-400 border border-orange-500/30
                            @else bg-blue-500/20 text-blue-400 border border-blue-500/30
                            @endif">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="hidden sm:block">
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center px-4 py-2 bg-[#e0ac7e] hover:bg-[#c49a6c] text-gray-900 font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edytuj profil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Account Information --}}
        <div class="bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-700/50 shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700/50">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Informacje o koncie
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nazwa</label>
                    <p class="text-white font-medium">{{ auth()->user()->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email</label>
                    <p class="text-white font-medium">{{ auth()->user()->email ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Data rejestracji</label>
                    <p class="text-white font-medium">{{ auth()->user()->created_at?->format('d.m.Y H:i') ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Ostatnia aktualizacja</label>
                    <p class="text-white font-medium">{{ auth()->user()->updated_at?->format('d.m.Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Permissions Summary --}}
        <div class="bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-700/50 shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700/50">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Uprawnienia
                </h2>
            </div>
            <div class="p-6">
                @php
                    $permissions = auth()->user()->getAllPermissions();
                    $groupedPermissions = $permissions->groupBy(function($perm) {
                        return explode('.', $perm->name)[0];
                    });
                @endphp

                @if($groupedPermissions->count() > 0)
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($groupedPermissions as $module => $perms)
                    <div class="bg-gray-900/50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-[#e0ac7e] capitalize">{{ $module }}</span>
                            <span class="text-xs text-gray-500">{{ $perms->count() }} uprawnien</span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @foreach($perms as $perm)
                            <span class="px-2 py-0.5 text-xs bg-gray-700/50 text-gray-300 rounded">
                                {{ explode('.', $perm->name)[1] ?? $perm->name }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-400 text-center py-4">Brak przypisanych uprawnien</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6 bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-700/50 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700/50">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Szybkie akcje
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center p-4 bg-gray-900/50 hover:bg-gray-700/50 rounded-lg border border-gray-700/50 hover:border-[#e0ac7e]/30 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3 group-hover:bg-blue-500/30 transition-colors">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-medium">Edytuj dane</p>
                        <p class="text-gray-400 text-sm">Zmien nazwe lub email</p>
                    </div>
                </a>

                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center p-4 bg-gray-900/50 hover:bg-gray-700/50 rounded-lg border border-gray-700/50 hover:border-[#e0ac7e]/30 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center mr-3 group-hover:bg-green-500/30 transition-colors">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-medium">Dashboard</p>
                        <p class="text-gray-400 text-sm">Wroc do panelu</p>
                    </div>
                </a>

                @can('permissions.view')
                <a href="{{ route('admin.permissions.index') }}"
                   class="flex items-center p-4 bg-gray-900/50 hover:bg-gray-700/50 rounded-lg border border-gray-700/50 hover:border-[#e0ac7e]/30 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center mr-3 group-hover:bg-purple-500/30 transition-colors">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-medium">Uprawnienia</p>
                        <p class="text-gray-400 text-sm">Zarzadzaj dostepem</p>
                    </div>
                </a>
                @endcan

                <form method="POST" action="{{ route('logout') }}" class="contents">
                    @csrf
                    <button type="submit"
                            class="flex items-center p-4 bg-gray-900/50 hover:bg-red-900/30 rounded-lg border border-gray-700/50 hover:border-red-500/30 transition-all group w-full text-left">
                        <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center mr-3 group-hover:bg-red-500/30 transition-colors">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium">Wyloguj</p>
                            <p class="text-gray-400 text-sm">Zakoncz sesje</p>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
