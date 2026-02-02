{{-- Entity Form - Prawa kolumna gorna czesc --}}
@php
    $entity = $this->selectedEntity;
    $typeBadgeClass = match($activeTab) {
        'supplier' => 'bg-blue-900/50 text-blue-400',
        'manufacturer' => 'bg-green-900/50 text-green-400',
        'importer' => 'bg-purple-900/50 text-purple-400',
        default => 'bg-gray-700 text-gray-400',
    };
    $typeLabel = $tabs[$activeTab]['label_singular'] ?? $activeTab;
@endphp

<div class="enterprise-card">
    {{-- Card Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            @if($entity->logo_path)
                <img src="{{ asset('storage/' . $entity->logo_path) }}"
                     alt="{{ $entity->name }}"
                     class="w-10 h-10 object-contain rounded-lg bg-gray-700/50">
            @else
                <div class="w-10 h-10 rounded-lg bg-gray-700/50 flex items-center justify-center">
                    <span class="text-sm font-bold text-gray-400">{{ strtoupper(mb_substr($entity->name, 0, 2)) }}</span>
                </div>
            @endif
            <div>
                <h2 class="text-lg font-semibold text-white">{{ $entity->name }}</h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeBadgeClass }}">
                    {{ $typeLabel }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="saveEntityDetails"
                    wire:loading.attr="disabled"
                    class="btn-enterprise-primary">
                <span wire:loading.remove wire:target="saveEntityDetails">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Zapisz zmiany
                </span>
                <span wire:loading wire:target="saveEntityDetails" class="flex items-center">
                    <svg class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Zapisywanie...
                </span>
            </button>

            <button wire:click="confirmDelete({{ $entity->id }})"
                    class="btn-enterprise-secondary supplier-panel__btn-danger"
                    title="Usun podmiot">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Form Fields --}}
    <div class="space-y-6">
        {{-- Logo Upload --}}
        <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600">
            <label class="block text-sm font-medium text-gray-300 mb-3">Logo</label>
            <div class="flex items-start gap-4">
                {{-- Preview --}}
                <div class="flex-shrink-0">
                    @if($entity->logo_path)
                        <img src="{{ asset('storage/' . $entity->logo_path) }}"
                             alt="Logo"
                             class="w-20 h-20 object-contain rounded-lg bg-gray-800 border border-gray-600">
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
                    <input type="file"
                           wire:model="logoUpload"
                           accept="image/*"
                           class="hidden"
                           id="logo-edit-{{ $entity->id }}">
                    <label for="logo-edit-{{ $entity->id }}"
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg cursor-pointer transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Wybierz plik
                    </label>
                    @if($entity->logo_path)
                        <button type="button" wire:click="deleteLogo" class="ml-2 text-sm text-red-400 hover:text-red-300">
                            Usun logo
                        </button>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, WEBP. Max 2MB.</p>
                    @error('logoUpload') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="logoUpload" class="text-xs text-blue-400 mt-1">Ladowanie...</div>
                </div>
            </div>
        </div>

        {{-- Basic Info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Nazwa --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa <span class="text-red-400">*</span></label>
                <input type="text"
                       wire:model="formData.name"
                       class="form-input-dark w-full"
                       placeholder="Nazwa podmiotu"
                       required>
                @error('formData.name') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            {{-- Firma --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Firma</label>
                <input type="text"
                       wire:model="formData.company_name"
                       class="form-input-dark w-full"
                       placeholder="Nazwa firmy">
                @error('formData.company_name') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Address --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Adres</label>
                <input type="text"
                       wire:model="formData.address"
                       class="form-input-dark w-full"
                       placeholder="Ulica i numer">
                @error('formData.address') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Kod pocztowy</label>
                <input type="text"
                       wire:model="formData.postal_code"
                       class="form-input-dark w-full"
                       placeholder="00-000">
                @error('formData.postal_code') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Miasto</label>
                <input type="text"
                       wire:model="formData.city"
                       class="form-input-dark w-full"
                       placeholder="Miasto">
                @error('formData.city') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Kraj</label>
                <input type="text"
                       wire:model="formData.country"
                       class="form-input-dark w-full"
                       placeholder="Polska">
                @error('formData.country') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Contact --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email"
                       wire:model="formData.email"
                       class="form-input-dark w-full"
                       placeholder="email@example.com">
                @error('formData.email') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Telefon</label>
                <input type="tel"
                       wire:model="formData.phone"
                       class="form-input-dark w-full"
                       placeholder="+48 000 000 000">
                @error('formData.phone') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Strona WWW</label>
                <input type="url"
                       wire:model="formData.website"
                       class="form-input-dark w-full"
                       placeholder="https://...">
                @error('formData.website') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox"
                           wire:model="formData.is_active"
                           class="form-checkbox-dark mr-2">
                    <span class="text-sm text-gray-300">Aktywny</span>
                </label>
            </div>
        </div>

        {{-- SEO Section (only for manufacturer/importer) --}}
        @if(in_array($activeTab, ['manufacturer', 'importer']))
            <div x-data="{ seoOpen: false }" class="bg-gray-700/30 rounded-lg border border-gray-600">
                <button @click="seoOpen = !seoOpen"
                        type="button"
                        class="w-full flex items-center justify-between p-4 text-left">
                    <h4 class="text-sm font-medium text-gray-300 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Ustawienia SEO
                    </h4>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': seoOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="seoOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Meta Title</label>
                        <input type="text"
                               wire:model="formData.meta_title"
                               class="form-input-dark w-full"
                               placeholder="Tytul strony (max 255)">
                        @error('formData.meta_title') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Meta Description</label>
                        <textarea wire:model="formData.meta_description"
                                  rows="2"
                                  class="form-input-dark w-full"
                                  placeholder="Opis dla wyszukiwarek (max 512)"></textarea>
                        @error('formData.meta_description') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Meta Keywords</label>
                        <input type="text"
                               wire:model="formData.meta_keywords"
                               class="form-input-dark w-full"
                               placeholder="slowo1, slowo2, slowo3">
                        @error('formData.meta_keywords') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">URL SEO (link_rewrite)</label>
                        <input type="text"
                               wire:model="formData.ps_link_rewrite"
                               class="form-input-dark w-full"
                               placeholder="nazwa-podmiotu">
                        <p class="text-xs text-gray-500 mt-1">Tylko male litery, cyfry i myslniki</p>
                        @error('formData.ps_link_rewrite') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Delete Confirmation --}}
    @if($showDeleteConfirm ?? false)
        <div class="mt-6 p-4 bg-red-900/20 border border-red-800/50 rounded-lg">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-red-400">Potwierdzenie usuniecia</h4>
                    <p class="text-sm text-gray-400 mt-1">
                        Czy na pewno chcesz usunac <strong class="text-white">{{ $entity->name }}</strong>? Ta operacja jest nieodwracalna.
                    </p>
                    <div class="flex items-center gap-2 mt-3">
                        <button wire:click="delete"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-secondary supplier-panel__btn-danger-confirm">
                            <span wire:loading.remove wire:target="delete">Tak, usun</span>
                            <span wire:loading wire:target="delete">Usuwanie...</span>
                        </button>
                        <button wire:click="$set('showDeleteConfirm', false)"
                                class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
