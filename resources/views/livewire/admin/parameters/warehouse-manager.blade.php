<div>
    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        {{-- Search and Filters --}}
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Szukaj magazynu..."
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

            <select wire:model.live="typeFilter" class="form-select-dark">
                <option value="all">Wszystkie typy</option>
                <option value="master">Glowne</option>
                <option value="shop_linked">Polaczone ze sklepem</option>
                <option value="custom">Niestandardowe</option>
            </select>
        </div>

        {{-- Add Button --}}
        <button wire:click="openCreateModal" class="btn-enterprise-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Dodaj magazyn
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-400">Wszystkie</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-green-400">{{ $this->stats['active'] }}</div>
            <div class="text-sm text-gray-400">Aktywne</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-blue-400">{{ $this->stats['master'] }}</div>
            <div class="text-sm text-gray-400">Glowne</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-purple-400">{{ $this->stats['shop_linked'] }}</div>
            <div class="text-sm text-gray-400">Polaczone</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Magazyn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kod</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Lokalizacja</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($this->warehouses as $warehouse)
                    <tr class="hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white flex items-center gap-2">
                                        {{ $warehouse->name }}
                                        @if($warehouse->is_default)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-900/50 text-yellow-400">Domyslny</span>
                                        @endif
                                    </div>
                                    @if($warehouse->shop)
                                        <div class="text-xs text-blue-400">{{ $warehouse->shop->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">{{ $warehouse->code }}</code>
                        </td>
                        <td class="px-4 py-3">
                            @switch($warehouse->type)
                                @case('master')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900/50 text-blue-400">Glowny</span>
                                    @break
                                @case('shop_linked')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-900/50 text-purple-400">Polaczony</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-400">Custom</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-300">
                                @if($warehouse->city)
                                    {{ $warehouse->city }}
                                    @if($warehouse->country && $warehouse->country !== 'PL')
                                        <span class="text-gray-500">({{ $warehouse->country }})</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($warehouse->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-400">Aktywny</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-400">Nieaktywny</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(!$warehouse->is_default && $warehouse->is_active)
                                    <button wire:click="setAsDefault({{ $warehouse->id }})"
                                            class="p-1.5 text-gray-400 hover:text-yellow-400 rounded hover:bg-gray-700"
                                            title="Ustaw jako domyslny">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="openEditModal({{ $warehouse->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-gray-700"
                                        title="Edytuj">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $warehouse->id }})"
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            Brak magazynow. Kliknij "Dodaj magazyn" aby utworzyc pierwszy.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-700">
                    <form wire:submit="save">
                        <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 max-h-[70vh] overflow-y-auto">
                            <h3 class="text-lg font-medium text-white mb-4">
                                {{ $editingId ? 'Edytuj magazyn' : 'Dodaj nowy magazyn' }}
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa *</label>
                                    <input type="text" wire:model="formData.name" class="form-input-dark w-full" required>
                                    @error('formData.name') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                </div>

                                {{-- Code --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Kod</label>
                                    <input type="text" wire:model="formData.code" class="form-input-dark w-full" placeholder="auto-generowany">
                                    @error('formData.code') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                                </div>

                                {{-- Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Typ</label>
                                    <select wire:model="formData.type" class="form-select-dark w-full">
                                        <option value="custom">Niestandardowy</option>
                                        <option value="master">Glowny</option>
                                        <option value="shop_linked">Polaczony ze sklepem</option>
                                    </select>
                                </div>

                                {{-- Shop (for shop_linked) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Sklep (opcjonalnie)</label>
                                    <select wire:model="formData.shop_id" class="form-select-dark w-full">
                                        <option value="">-- wybierz sklep --</option>
                                        @foreach($this->shops as $shop)
                                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Address --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Adres</label>
                                    <input type="text" wire:model="formData.address" class="form-input-dark w-full">
                                </div>

                                {{-- City --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Miasto</label>
                                    <input type="text" wire:model="formData.city" class="form-input-dark w-full">
                                </div>

                                {{-- Postal Code --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Kod pocztowy</label>
                                    <input type="text" wire:model="formData.postal_code" class="form-input-dark w-full">
                                </div>

                                {{-- Contact Person --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Osoba kontaktowa</label>
                                    <input type="text" wire:model="formData.contact_person" class="form-input-dark w-full">
                                </div>

                                {{-- Phone --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Telefon</label>
                                    <input type="text" wire:model="formData.phone" class="form-input-dark w-full">
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                                    <input type="email" wire:model="formData.email" class="form-input-dark w-full">
                                </div>

                                {{-- Sort Order --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Kolejnosc</label>
                                    <input type="number" wire:model="formData.sort_order" class="form-input-dark w-full" min="0">
                                </div>

                                {{-- Notes --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Notatki</label>
                                    <textarea wire:model="formData.notes" rows="2" class="form-input-dark w-full"></textarea>
                                </div>

                                {{-- Flags --}}
                                <div class="md:col-span-2 flex flex-wrap gap-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="formData.is_active" class="form-checkbox-dark mr-2">
                                        <span class="text-sm text-gray-300">Aktywny</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="formData.is_default" class="form-checkbox-dark mr-2">
                                        <span class="text-sm text-gray-300">Domyslny</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="formData.allow_negative_stock" class="form-checkbox-dark mr-2">
                                        <span class="text-sm text-gray-300">Pozwol na ujemne stany</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                            <button type="submit" class="btn-enterprise-primary w-full sm:w-auto sm:ml-3">
                                {{ $editingId ? 'Zapisz zmiany' : 'Dodaj magazyn' }}
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
                                <h3 class="text-lg leading-6 font-medium text-white">Usun magazyn</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-400">
                                        Czy na pewno chcesz usunac magazyn <strong class="text-white">{{ $deleteName }}</strong>?
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
</div>
