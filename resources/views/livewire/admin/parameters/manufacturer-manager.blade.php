<div>
    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        {{-- Search and Filters --}}
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Szukaj marki..."
                       class="form-input-dark w-full pl-10">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select wire:model.live="statusFilter" class="form-select-dark">
                <option value="all">Wszystkie statusy</option>
                <option value="active">Aktywne</option>
                <option value="inactive">Nieaktywne</option>
            </select>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            {{-- Import from PrestaShop Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import z PrestaShop
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 rounded-lg bg-gray-800 border border-gray-700 shadow-lg z-50">
                    <div class="p-2">
                        <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase">Wybierz sklep</div>
                        @foreach($this->shops as $shop)
                            <button wire:click="importFromPrestaShop({{ $shop->id }})"
                                    @click="open = false"
                                    class="w-full flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-md transition-colors">
                                <span class="flex-1 text-left">{{ $shop->name }}</span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Add Button --}}
            <button wire:click="openCreateModal" class="btn-enterprise-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Dodaj marke
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-400">Wszystkie marki</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-green-400">{{ $this->stats['active'] }}</div>
            <div class="text-sm text-gray-400">Aktywne</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-gray-500">{{ $this->stats['inactive'] }}</div>
            <div class="text-sm text-gray-400">Nieaktywne</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nazwa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kod</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Produkty</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Sklepy</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($this->manufacturers as $manufacturer)
                    <tr class="hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($manufacturer->logo_path)
                                    <img src="{{ asset('storage/' . $manufacturer->logo_path) }}" alt="{{ $manufacturer->name }}" class="w-8 h-8 object-contain rounded">
                                @else
                                    <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                                        <span class="text-xs font-bold text-gray-400">{{ strtoupper(substr($manufacturer->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $manufacturer->name }}</div>
                                    @if($manufacturer->website)
                                        <a href="{{ $manufacturer->website }}" target="_blank" class="text-xs text-blue-400 hover:underline">{{ parse_url($manufacturer->website, PHP_URL_HOST) }}</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">{{ $manufacturer->code }}</code>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm text-gray-300">{{ $manufacturer->products_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="openShopsModal({{ $manufacturer->id }})"
                                    class="inline-flex items-center gap-1 text-sm text-blue-400 hover:text-blue-300">
                                <span>{{ $manufacturer->shops_count }}</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($manufacturer->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-400">Aktywna</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-400">Nieaktywna</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($manufacturer->shops_count > 0)
                                    <button wire:click="openSyncModal({{ $manufacturer->id }})"
                                            class="p-1.5 text-gray-400 hover:text-blue-400 rounded hover:bg-gray-700"
                                            title="Synchronizacja">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="openEditModal({{ $manufacturer->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-gray-700"
                                        title="Edytuj">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $manufacturer->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-400 rounded hover:bg-gray-700"
                                        title="Usun">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Brak marek. Kliknij "Dodaj marke" aby utworzyc pierwsza.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create/Edit Modal - ETAP 07g Extended --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-700">
                    <form wire:submit="save">
                        <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 max-h-[80vh] overflow-y-auto">
                            <h3 class="text-lg font-medium text-white mb-4">
                                {{ $editingId ? 'Edytuj marke' : 'Dodaj nowa marke' }}
                            </h3>

                            <div class="space-y-4">
                                {{-- Logo Upload Section --}}
                                <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600">
                                    <label class="block text-sm font-medium text-gray-300 mb-3">Logo producenta</label>
                                    <div class="flex items-start gap-4">
                                        {{-- Preview --}}
                                        <div class="flex-shrink-0">
                                            @if($logoUpload)
                                                <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-contain rounded-lg bg-gray-800 border border-gray-600">
                                            @elseif($existingLogoUrl)
                                                <img src="{{ $existingLogoUrl }}" alt="Logo" class="w-20 h-20 object-contain rounded-lg bg-gray-800 border border-gray-600">
                                            @else
                                                <div class="w-20 h-20 rounded-lg bg-gray-800 border border-gray-600 flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        {{-- Upload Controls --}}
                                        <div class="flex-1">
                                            <input type="file" wire:model="logoUpload" accept="image/*" class="hidden" id="logo-upload-{{ $editingId ?? 'new' }}">
                                            <label for="logo-upload-{{ $editingId ?? 'new' }}"
                                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg cursor-pointer transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                </svg>
                                                Wybierz plik
                                            </label>
                                            @if($existingLogoUrl)
                                                <button type="button" wire:click="deleteLogo" class="ml-2 text-sm text-red-400 hover:text-red-300">Usun logo</button>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, WEBP. Max 2MB.</p>
                                            @error('logoUpload') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                            <div wire:loading wire:target="logoUpload" class="text-xs text-blue-400 mt-1">Ladowanie...</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Basic Info Section --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Name --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa *</label>
                                        <input type="text" wire:model="formData.name" class="form-input-dark w-full" required>
                                        @error('formData.name') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Code --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Kod (auto)</label>
                                        <input type="text" wire:model="formData.code" class="form-input-dark w-full" placeholder="np. moretti">
                                        @error('formData.code') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Website & Link Rewrite --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Strona WWW</label>
                                        <input type="url" wire:model="formData.website" class="form-input-dark w-full" placeholder="https://...">
                                        @error('formData.website') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">URL SEO (link_rewrite)</label>
                                        <input type="text" wire:model="formData.ps_link_rewrite" class="form-input-dark w-full" placeholder="nazwa-marki">
                                        <p class="text-xs text-gray-500 mt-1">Tylko male litery, cyfry i myslniki</p>
                                        @error('formData.ps_link_rewrite') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Opis pelny</label>
                                    <textarea wire:model="formData.description" rows="3" class="form-input-dark w-full" placeholder="Pelny opis producenta..."></textarea>
                                </div>

                                {{-- Short Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Opis krotki</label>
                                    <textarea wire:model="formData.short_description" rows="2" class="form-input-dark w-full" placeholder="Krotki opis (max 1000 znakow)"></textarea>
                                    @error('formData.short_description') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                </div>

                                {{-- SEO Section --}}
                                <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600">
                                    <h4 class="text-sm font-medium text-gray-300 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        Ustawienia SEO
                                    </h4>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-1">Meta Title</label>
                                            <input type="text" wire:model="formData.meta_title" class="form-input-dark w-full" placeholder="Tytul strony producenta (max 255)">
                                            @error('formData.meta_title') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-1">Meta Description</label>
                                            <textarea wire:model="formData.meta_description" rows="2" class="form-input-dark w-full" placeholder="Opis dla wyszukiwarek (max 512)"></textarea>
                                            @error('formData.meta_description') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-1">Meta Keywords</label>
                                            <input type="text" wire:model="formData.meta_keywords" class="form-input-dark w-full" placeholder="slowo1, slowo2, slowo3">
                                            @error('formData.meta_keywords') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Sort & Active --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Kolejnosc</label>
                                        <input type="number" wire:model="formData.sort_order" class="form-input-dark w-full" min="0">
                                    </div>
                                    <div class="flex items-center pt-6">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model="formData.is_active" class="form-checkbox-dark mr-2">
                                            <span class="text-sm text-gray-300">Aktywna</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                            <button type="submit" class="btn-enterprise-primary w-full sm:w-auto sm:ml-3">
                                {{ $editingId ? 'Zapisz zmiany' : 'Dodaj marke' }}
                            </button>
                            <button type="button" wire:click="closeModal" class="btn-enterprise-secondary w-full sm:w-auto mt-3 sm:mt-0">
                                Anuluj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Shops Assignment Modal --}}
    @if($showShopsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeShopsModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-700">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-white mb-4">Przypisz do sklepow</h3>

                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($this->shops as $shop)
                                <label class="flex items-center p-3 rounded-lg bg-gray-700/50 hover:bg-gray-700 cursor-pointer transition-colors">
                                    <input type="checkbox"
                                           wire:click="toggleShop({{ $shop->id }})"
                                           @checked(in_array($shop->id, $selectedShopIds))
                                           class="form-checkbox-dark mr-3">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-white">{{ $shop->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $shop->domain }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                        <button wire:click="saveShopAssignments" class="btn-enterprise-primary w-full sm:w-auto sm:ml-3">
                            Zapisz przypisania
                        </button>
                        <button wire:click="closeShopsModal" class="btn-enterprise-secondary w-full sm:w-auto mt-3 sm:mt-0">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeDeleteModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-700">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-white">Usun marke</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-400">
                                        Czy na pewno chcesz usunac marke <strong class="text-white">{{ $deleteName }}</strong>?
                                        Ta operacja jest nieodwracalna.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                        <button wire:click="delete" class="btn-enterprise-danger w-full sm:w-auto sm:ml-3">
                            Usun
                        </button>
                        <button wire:click="closeDeleteModal" class="btn-enterprise-secondary w-full sm:w-auto mt-3 sm:mt-0">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Sync Modal - ETAP 07g --}}
    @if($showSyncModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeSyncModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-700">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Synchronizacja ze sklepami
                            </h3>
                            @if($isSyncing)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900/50 text-blue-400">
                                    <svg class="animate-spin -ml-1 mr-2 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Synchronizacja...
                                </span>
                            @endif
                        </div>

                        {{-- Shop list with sync status --}}
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @forelse($shopSyncDetails as $shopId => $details)
                                <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-gray-600 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-white">{{ $details['shop_name'] }}</div>
                                                @if($details['ps_manufacturer_id'])
                                                    <div class="text-xs text-gray-500">PS ID: {{ $details['ps_manufacturer_id'] }}</div>
                                                @else
                                                    <div class="text-xs text-yellow-500">Nie zsynchronizowano</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Sync status badge --}}
                                        @php
                                            $status = $details['sync_status'] ?? 'pending';
                                        @endphp
                                        @if($status === 'synced')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Zsynchronizowany
                                            </span>
                                        @elseif($status === 'error')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-900/50 text-red-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Blad
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-900/50 text-yellow-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                </svg>
                                                Oczekuje
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Logo sync status --}}
                                    <div class="flex items-center justify-between text-xs text-gray-400 mb-3 pb-3 border-b border-gray-600">
                                        <span>Logo:</span>
                                        @if($details['logo_synced'])
                                            <span class="text-green-400">Zsynchronizowane {{ $details['logo_synced_at'] ? \Carbon\Carbon::parse($details['logo_synced_at'])->diffForHumans() : '' }}</span>
                                        @else
                                            <span class="text-gray-500">Nie zsynchronizowane</span>
                                        @endif
                                    </div>

                                    {{-- Error message --}}
                                    @if($details['sync_error'])
                                        <div class="mb-3 p-2 bg-red-900/30 border border-red-800 rounded text-xs text-red-400">
                                            {{ $details['sync_error'] }}
                                        </div>
                                    @endif

                                    {{-- Action buttons --}}
                                    <div class="flex flex-wrap gap-2">
                                        <button wire:click="syncToShop({{ $shopId }})"
                                                @disabled($isSyncing)
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                            Wyslij do sklepu
                                        </button>

                                        <button wire:click="importLogoFromShop({{ $shopId }})"
                                                @disabled($isSyncing || !$details['ps_manufacturer_id'])
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 bg-gray-600 hover:bg-gray-500 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="{{ $details['ps_manufacturer_id'] ? 'Pobierz logo z PrestaShop' : 'Producent nie istnieje w sklepie' }}">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Pobierz logo
                                        </button>

                                        <button wire:click="syncLogoToShop({{ $shopId }})"
                                                @disabled($isSyncing || !$details['ps_manufacturer_id'])
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 bg-gray-600 hover:bg-gray-500 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="{{ $details['ps_manufacturer_id'] ? 'Wyslij logo do PrestaShop' : 'Najpierw zsynchronizuj producenta' }}">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            Wyslij logo
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <p>Brak przypisanych sklepow.</p>
                                    <p class="text-xs mt-1">Najpierw przypisz producenta do sklepow.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-gray-800/50 px-4 py-3 sm:px-6 border-t border-gray-700">
                        <button wire:click="closeSyncModal" class="btn-enterprise-secondary w-full">
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
