<div class="min-h-screen bg-main-gradient">
    {{-- Header Section --}}
    <div class="sticky top-0 z-40 glass-effect border-b border-primary shadow-lg">
        <div class="px-6 sm:px-8 lg:px-12 py-4">
            {{-- Title & Action Bar --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Zarządzanie typami produktów</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Konfiguruj edytowalne typy produktów w systemie
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="showCreateModal"
                           class="btn-primary inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Dodaj typ produktu
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    @if($successMessage)
        <div class="mx-6 sm:mx-8 lg:mx-12 mt-4">
            <div class="bg-green-900/20 border border-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-green-200">{{ $successMessage }}</span>
                    <button wire:click="clearMessages" class="ml-auto text-green-400 hover:text-green-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="mx-6 sm:mx-8 lg:mx-12 mt-4">
            <div class="bg-red-900/20 border border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-red-200">{{ $errorMessage }}</span>
                    <button wire:click="clearMessages" class="ml-auto text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="px-6 sm:px-8 lg:px-12 py-6">
        {{-- Controls Bar --}}
        <div class="glass-effect rounded-lg p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                {{-- Search --}}
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input wire:model.live="search"
                               type="text"
                               placeholder="Wyszukaj typy produktów..."
                               class="block w-full pl-10 pr-3 py-2 border border-gray-600 rounded-md bg-card text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"/>
                    </div>
                </div>

                {{-- Bulk Actions --}}
                @if(!empty($selected))
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-400">{{ count($selected) }} zaznaczonych</span>
                        <button wire:click="bulkActivate"
                               class="btn-secondary px-3 py-1 text-sm">
                            Aktywuj
                        </button>
                        <button wire:click="bulkDeactivate"
                               class="btn-secondary px-3 py-1 text-sm">
                            Dezaktywuj
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Product Types Table --}}
        <div class="glass-effect rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox"
                                       wire:model.live="selectAll"
                                       class="rounded border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Typ produktu
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Slug
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Produkty
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Kolejność
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($productTypes as $type)
                            <tr class="hover:bg-gray-800/50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox"
                                           wire:model.live="selected"
                                           value="{{ $type->id }}"
                                           class="rounded border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($type->icon)
                                            <i class="{{ $type->icon }} w-5 h-5 mr-3 text-gray-400"></i>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-white">{{ $type->name }}</div>
                                            @if($type->description)
                                                <div class="text-xs text-gray-400">{{ Str::limit($type->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-400 font-mono">{{ $type->slug }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button wire:click="toggleStatus({{ $type->id }})"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200
                                                   {{ $type->is_active
                                                      ? 'bg-green-800 text-green-200 hover:bg-green-700'
                                                      : 'bg-red-800 text-red-200 hover:bg-red-700' }}">
                                        @if($type->is_active)
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Aktywny
                                        @else
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Nieaktywny
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-800 text-orange-200">
                                        {{ $type->products_count }} produktów
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                    {{ $type->sort_order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button wire:click="showEditModal({{ $type->id }})"
                                               class="text-orange-400 hover:text-orange-300 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="showDeleteModal({{ $type->id }})"
                                               class="text-red-400 hover:text-red-300 transition-colors duration-200
                                                      {{ $type->products_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <h3 class="text-sm font-medium">Brak typów produktów</h3>
                                        <p class="text-sm mt-1">Dodaj pierwszy typ produktu, aby rozpocząć.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-card rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-white">Dodaj nowy typ produktu</h3>
                        <p class="mt-1 text-sm text-gray-400">Utwórz nowy edytowalny typ produktu.</p>
                    </div>

                    <div class="space-y-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa typu *</label>
                            <input wire:model.live="name"
                                   type="text"
                                   placeholder="np. Części zamienne"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            @error('name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Slug URL *</label>
                            <input wire:model.live="slug"
                                   type="text"
                                   placeholder="czesci-zamienne"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono">
                            @error('slug') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                            <textarea wire:model="description"
                                     rows="3"
                                     placeholder="Krótki opis typu produktu..."
                                     class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"></textarea>
                            @error('description') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Icon --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Ikona (CSS class)</label>
                            <input wire:model="icon"
                                   type="text"
                                   placeholder="fas fa-cog"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            @error('icon') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Active Status --}}
                        <div class="flex items-center">
                            <input wire:model="is_active"
                                   type="checkbox"
                                   id="is_active"
                                   class="rounded border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <label for="is_active" class="ml-2 text-sm text-gray-300">Typ aktywny</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button wire:click="closeModal"
                               class="btn-secondary px-4 py-2">
                            Anuluj
                        </button>
                        <button wire:click="create"
                               class="btn-primary px-4 py-2">
                            Utwórz typ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal && $selectedType)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-card rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-white">Edytuj typ produktu</h3>
                        <p class="mt-1 text-sm text-gray-400">Modyfikuj właściwości typu produktu.</p>
                    </div>

                    <div class="space-y-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa typu *</label>
                            <input wire:model.live="name"
                                   type="text"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            @error('name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Slug URL *</label>
                            <input wire:model.live="slug"
                                   type="text"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono">
                            @error('slug') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Opis</label>
                            <textarea wire:model="description"
                                     rows="3"
                                     class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"></textarea>
                            @error('description') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Icon --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Ikona (CSS class)</label>
                            <input wire:model="icon"
                                   type="text"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            @error('icon') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Sort Order --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Kolejność</label>
                            <input wire:model="sort_order"
                                   type="number"
                                   min="0"
                                   class="block w-full px-3 py-2 border border-gray-600 rounded-md bg-card text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            @error('sort_order') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Active Status --}}
                        <div class="flex items-center">
                            <input wire:model="is_active"
                                   type="checkbox"
                                   id="edit_is_active"
                                   class="rounded border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <label for="edit_is_active" class="ml-2 text-sm text-gray-300">Typ aktywny</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button wire:click="closeModal"
                               class="btn-secondary px-4 py-2">
                            Anuluj
                        </button>
                        <button wire:click="update"
                               class="btn-primary px-4 py-2">
                            Zapisz zmiany
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $selectedType)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-card rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-white">Usuń typ produktu</h3>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-400">
                            Czy na pewno chcesz usunąć typ produktu <strong class="text-white">{{ $selectedType->name }}</strong>?
                        </p>
                        @if($selectedType->products_count > 0)
                            <div class="mt-3 p-3 bg-red-900/20 border border-red-800 rounded-lg">
                                <p class="text-sm text-red-200">
                                    <strong>Uwaga:</strong> Ten typ ma przypisanych {{ $selectedType->products_count }} produktów.
                                    Nie można usunąć typu produktu, który jest używany przez produkty.
                                </p>
                            </div>
                        @else
                            <p class="text-xs text-gray-500 mt-2">Ta operacja jest nieodwracalna.</p>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button wire:click="closeModal"
                               class="btn-secondary px-4 py-2">
                            Anuluj
                        </button>
                        <button wire:click="delete"
                               {{ $selectedType->products_count > 0 ? 'disabled' : '' }}
                               class="bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg transition-colors duration-200">
                            Usuń typ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Custom Styles --}}
    <style>
        /* MPP TRADE Color Scheme - Orange Theme */
        :root {
            --mpp-primary: #e0ac7e;
            --mpp-primary-dark: #d1975a;
            --mpp-primary-light: #f4dcc6;
        }

        .bg-main-gradient {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }

        .glass-effect {
            background: rgba(31, 41, 55, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bg-card {
            background: rgba(31, 41, 55, 0.95);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--mpp-primary) 0%, var(--mpp-primary-dark) 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--mpp-primary-dark) 0%, #b8834a 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(224, 172, 126, 0.3);
        }

        .btn-secondary {
            background: rgba(75, 85, 99, 0.8);
            color: #d1d5db;
            border: 1px solid #4b5563;
        }

        .btn-secondary:hover {
            background: rgba(75, 85, 99, 1);
            border-color: var(--mpp-primary);
        }

        .border-primary {
            border-color: var(--mpp-primary);
        }

        .text-primary {
            color: var(--mpp-primary);
        }
    </style>
</div>