{{-- Create Modal - Tworzenie nowego podmiotu --}}
@if($showCreateModal)
    <div class="supplier-panel__modal-overlay" wire:click.self="$set('showCreateModal', false)">
        <div class="supplier-panel__modal" wire:click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-white">
                    Dodaj
                    @switch($activeTab)
                        @case('supplier')
                            nowego dostawce
                            @break
                        @case('manufacturer')
                            nowego producenta
                            @break
                        @case('importer')
                            nowego importera
                            @break
                        @default
                            nowy podmiot
                    @endswitch
                </h3>
                <button wire:click="$set('showCreateModal', false)"
                        class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <div class="space-y-5">
                    {{-- Logo Upload --}}
                    <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600">
                        <label class="block text-sm font-medium text-gray-300 mb-3">Logo</label>
                        <div class="flex items-start gap-4">
                            {{-- Preview --}}
                            <div class="flex-shrink-0">
                                @if($logoUpload)
                                    <img src="{{ $logoUpload->temporaryUrl() }}"
                                         alt="Preview"
                                         class="w-20 h-20 object-contain rounded-lg bg-gray-800 border border-gray-600">
                                @else
                                    <div class="w-20 h-20 rounded-lg bg-gray-800 border border-gray-600 border-dashed flex items-center justify-center">
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
                                       id="logo-create-new">
                                <label for="logo-create-new"
                                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg cursor-pointer transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    Wybierz plik
                                </label>
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
                                   wire:model="formData.company"
                                   class="form-input-dark w-full"
                                   placeholder="Nazwa firmy">
                            @error('formData.company') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
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
                    </div>

                    {{-- Active Checkbox --}}
                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model="formData.is_active"
                                   class="form-checkbox-dark mr-2"
                                   checked>
                            <span class="text-sm text-gray-300">Aktywny</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                <button wire:click="$set('showCreateModal', false)"
                        class="btn-enterprise-secondary">
                    Anuluj
                </button>
                <button wire:click="createEntity"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="createEntity">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Zapisz
                    </span>
                    <span wire:loading wire:target="createEntity" class="flex items-center">
                        <svg class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Zapisywanie...
                    </span>
                </button>
            </div>
        </div>
    </div>
@endif
