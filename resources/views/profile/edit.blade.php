@extends('layouts.admin')

@section('title', 'Edytuj profil - Admin PPM')
@section('breadcrumb', 'Edytuj profil')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('profile.show') }}"
           class="inline-flex items-center text-gray-400 hover:text-[#e0ac7e] transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Powrot do profilu
        </a>
    </div>

    {{-- Edit Profile Form --}}
    <div class="bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-700/50 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700/50 bg-gradient-to-r from-[#e0ac7e]/20 to-transparent">
            <h1 class="text-xl font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-3 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edytuj profil
            </h1>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            {{-- Avatar Upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-3">Zdjecie profilowe</label>
                <div class="flex items-center space-x-6">
                    {{-- Current Avatar Preview --}}
                    <div class="flex-shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                 alt="Avatar"
                                 class="w-20 h-20 rounded-full object-cover border-2 border-[#e0ac7e]/50">
                        @else
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#e0ac7e] to-[#c49a6c] flex items-center justify-center text-2xl font-bold text-gray-900">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center space-x-4">
                            <label for="avatar" class="cursor-pointer px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors inline-flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Wybierz zdjecie
                                <input type="file"
                                       name="avatar"
                                       id="avatar"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       class="hidden"
                                       onchange="previewAvatar(this)">
                            </label>

                            @if(auth()->user()->avatar)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="remove_avatar" value="1" class="sr-only peer">
                                <span class="px-3 py-2 text-sm text-red-400 hover:text-red-300 peer-checked:bg-red-900/30 peer-checked:text-red-300 rounded-lg transition-colors inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Usun zdjecie
                                </span>
                            </label>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF lub WebP. Max 2MB.</p>
                        @error('avatar')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Preview container --}}
                <div id="avatar-preview-container" class="hidden mt-4">
                    <p class="text-sm text-gray-400 mb-2">Podglad nowego zdjecia:</p>
                    <img id="avatar-preview" src="" alt="Preview" class="w-20 h-20 rounded-full object-cover border-2 border-green-500/50">
                </div>
            </div>

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nazwa</label>
                <input type="text"
                       name="name"
                       id="name"
                       value="{{ old('name', auth()->user()->name) }}"
                       class="w-full px-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-[#e0ac7e]/50 focus:border-[#e0ac7e] transition-colors"
                       required>
                @error('name')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email', auth()->user()->email) }}"
                       class="w-full px-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-[#e0ac7e]/50 focus:border-[#e0ac7e] transition-colors"
                       required>
                @error('email')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Password (for verification) --}}
            <div class="pt-4 border-t border-gray-700/50">
                <h3 class="text-lg font-medium text-white mb-4">Zmiana hasla (opcjonalne)</h3>

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">Aktualne haslo</label>
                        <input type="password"
                               name="current_password"
                               id="current_password"
                               class="w-full px-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-[#e0ac7e]/50 focus:border-[#e0ac7e] transition-colors"
                               autocomplete="current-password">
                        @error('current_password')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Nowe haslo</label>
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full px-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-[#e0ac7e]/50 focus:border-[#e0ac7e] transition-colors"
                               autocomplete="new-password">
                        @error('password')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">Potwierdz nowe haslo</label>
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               class="w-full px-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-[#e0ac7e]/50 focus:border-[#e0ac7e] transition-colors"
                               autocomplete="new-password">
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-700/50">
                <a href="{{ route('profile.show') }}"
                   class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Anuluj
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-[#e0ac7e] hover:bg-[#c49a6c] text-gray-900 font-medium rounded-lg transition-colors">
                    Zapisz zmiany
                </button>
            </div>
        </form>
    </div>

    {{-- Delete Account Section --}}
    <div class="mt-6 bg-gray-800/60 backdrop-blur-sm rounded-xl border border-red-900/30 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-red-900/30 bg-red-900/10">
            <h2 class="text-lg font-semibold text-red-400 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Strefa niebezpieczna
            </h2>
        </div>
        <div class="p-6">
            <p class="text-gray-400 mb-4">
                Po usunieciu konta wszystkie dane zostana trwale usuniete. Ta operacja jest nieodwracalna.
            </p>
            <button type="button"
                    onclick="confirm('Czy na pewno chcesz usunac swoje konto? Ta operacja jest nieodwracalna.') && document.getElementById('delete-account-form').submit()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                Usun konto
            </button>
            <form id="delete-account-form" method="POST" action="{{ route('profile.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function previewAvatar(input) {
    const preview = document.getElementById('avatar-preview');
    const container = document.getElementById('avatar-preview-container');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            container.classList.remove('hidden');
        }

        reader.readAsDataURL(input.files[0]);
    } else {
        container.classList.add('hidden');
    }
}
</script>
@endpush
@endsection
