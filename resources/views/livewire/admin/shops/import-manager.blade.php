<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">

    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
    </div>

    <!-- Page Header -->
    <div class="relative backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); z-index: 10000;">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Logo and Title -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            IMPORT MANAGEMENT
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            Zarządzanie importem danych z PrestaShop - SEKCJA 2.2.2.2
                        </p>
                    </div>
                </div>

                <!-- Back Button -->
                <a href="{{ route('admin.shops') }}"
                   class="relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                   style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8)); border: 1px solid rgba(224, 172, 126, 0.5);"
                   onmouseover="this.style.background='linear-gradient(45deg, rgba(209, 151, 90, 0.9), rgba(194, 132, 73, 0.9))'"
                   onmouseout="this.style.background='linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8))'">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Powrót do sklepów
                </a>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-8">

        <!-- Import Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Całkowite importy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_imports'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktywne importy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['active_imports'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj ukończone</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['completed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj błędy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['failed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Wymagają walidacji</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['pending_validation'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Rollbacks dostępne</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['rollbacks_available'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Configuration Panel - SEKCJA 2.2.2.2 -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Konfiguracja Importu
                </h3>
                <span class="text-xs text-gray-400">SEKCJA 2.2.2.2 - Import Management</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Import Types - 2.2.2.2.1 -->
                <div>
                    <label class="block text-sm font-medium text-white mb-3">Typy danych do importu</label>
                    <div class="space-y-2">
                        @foreach(['products' => 'Produkty', 'categories' => 'Kategorie', 'customers' => 'Klienci', 'orders' => 'Zamówienia'] as $type => $label)
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.defer="selectedImportTypes"
                                       value="{{ $type }}"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Import Mode -->
                <div>
                    <label for="importMode" class="block text-sm font-medium text-white mb-2">Tryb importu</label>
                    <select id="importMode"
                            wire:model.defer="importMode"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                        <option value="create_new">Tylko nowe rekordy</option>
                        <option value="update_existing">Tylko aktualizacje</option>
                        <option value="create_and_update">Nowe + aktualizacje</option>
                    </select>
                </div>

                <!-- Data Validation - 2.2.2.2.2 -->
                <div>
                    <label class="block text-sm font-medium text-white mb-3">Walidacja danych</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="validationEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz walidację</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="strictValidation"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Ścisła walidacja</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="skipInvalidRecords"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Pomiń nieprawidłowe</span>
                        </label>
                    </div>
                </div>

                <!-- Rollback Settings - 2.2.2.2.4 -->
                <div>
                    <label class="block text-sm font-medium text-white mb-3">Ustawienia rollback</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="enableRollback"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz rollback</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.defer="autoRollbackOnFailure"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Auto rollback przy błędzie</span>
                        </label>
                    </div>
                    <div class="mt-3">
                        <label for="maxRollbackDays" class="block text-sm font-medium text-gray-300 mb-1">Retencja (dni)</label>
                        <input type="number"
                               id="maxRollbackDays"
                               wire:model.defer="maxRollbackDays"
                               min="1" max="30"
                               class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Shop Selection -->
        <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">

            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Wybór sklepów PrestaShop
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($shops as $shop)
                <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-4 hover:bg-opacity-60 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <input type="checkbox"
                                       wire:click="toggleShopSelection({{ $shop->id }})"
                                       @if(in_array($shop->id, $selectedShops)) checked @endif
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e] mr-3">
                                <h4 class="font-medium text-white">{{ $shop->name }}</h4>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ $shop->url }}</p>
                            <div class="flex items-center mt-2">
                                @if($shop->connection_status === 'connected')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Połączony
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Błąd połączenia
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if(count($selectedShops) > 0)
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-600">
                    <span class="text-sm text-gray-400">
                        Wybrano: {{ count($selectedShops) }} sklepów
                    </span>

                    <div class="flex items-center space-x-3">
                        <!-- Import Preview Button - 2.2.2.2.3 -->
                        <button wire:click="startImportPreview"
                                wire:loading.attr="disabled"
                                class="relative px-6 py-2 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                                style="background: linear-gradient(45deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.8)); border: 1px solid rgba(59, 130, 246, 0.5);">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="startImportPreview">Podgląd importu</span>
                            <span wire:loading wire:target="startImportPreview">Generowanie...</span>
                        </button>

                        <!-- Schedule Import Button - 2.2.2.2.5 -->
                        <button wire:click="showScheduleModal"
                                class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Zaplanuj import
                        </button>

                        <!-- Execute Import Button - 2.2.2.2.1 -->
                        <button wire:click="executeImport"
                                wire:loading.attr="disabled"
                                class="relative px-6 py-2 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                                style="background: linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8)); border: 1px solid rgba(34, 197, 94, 0.5);">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <span wire:loading.remove wire:target="executeImport">Uruchom import</span>
                            <span wire:loading wire:target="executeImport">Uruchamianie...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Import Preview Modal - 2.2.2.2.3 -->
        @if($showImportPreview)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="closeImportPreview"></div>

                <!-- Modal -->
                <div class="inline-block align-bottom bg-gray-800 rounded-lg px-6 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-white">Podgląd importu danych</h3>
                            <p class="text-sm text-gray-400 mt-1">Przegląd zmian przed wykonaniem importu</p>
                        </div>
                        <button wire:click="closeImportPreview" class="p-2 text-gray-400 hover:text-white transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Preview Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($previewData as $shopId => $data)
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <h4 class="text-lg font-semibold text-white mb-4">{{ $data['shop_name'] }}</h4>

                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Całkowite rekordy:</span>
                                    <span class="text-white font-medium">{{ $data['total_records'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Szacowany czas:</span>
                                    <span class="text-white font-medium">{{ $data['estimated_duration'] }}</span>
                                </div>
                            </div>

                            @if(isset($previewSummary[$shopId]))
                            <div class="mt-4 pt-4 border-t border-gray-600">
                                <h5 class="text-sm font-medium text-white mb-2">Podsumowanie zmian:</h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-green-400">Nowe rekordy:</span>
                                        <span class="text-white">{{ $previewSummary[$shopId]['new_records'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-blue-400">Aktualizacje:</span>
                                        <span class="text-white">{{ $previewSummary[$shopId]['updated_records'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-yellow-400">Konflikty:</span>
                                        <span class="text-white">{{ $previewSummary[$shopId]['conflict_records'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-red-400">Błędy walidacji:</span>
                                        <span class="text-white">{{ $previewSummary[$shopId]['validation_issues'] }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if(isset($changesSummary[$shopId]))
                            <div class="mt-4 pt-4 border-t border-gray-600">
                                <h5 class="text-sm font-medium text-white mb-2">Szczegóły per typ:</h5>
                                <div class="space-y-2 text-xs">
                                    @foreach($changesSummary[$shopId] as $type => $changes)
                                        @if($changes['new'] > 0 || $changes['updated'] > 0 || $changes['conflicts'] > 0)
                                        <div class="bg-gray-700 bg-opacity-40 rounded p-2">
                                            <div class="font-medium text-white mb-1 capitalize">{{ ucfirst($type) }}:</div>
                                            <div class="flex justify-between text-gray-300">
                                                <span>N: {{ $changes['new'] }}</span>
                                                <span>U: {{ $changes['updated'] }}</span>
                                                <span>C: {{ $changes['conflicts'] }}</span>
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-600">
                        <button wire:click="closeImportPreview"
                                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                            Anuluj
                        </button>
                        <button wire:click="executeImport"
                                class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Potwierdź i wykonaj import
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Schedule Modal - 2.2.2.2.5 -->
        @if($showScheduleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="hideScheduleModal"></div>

                <!-- Modal -->
                <div class="inline-block align-bottom bg-gray-800 rounded-lg px-6 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-white">Planowanie importu</h3>
                            <p class="text-sm text-gray-400 mt-1">Zaplanuj import na czas poza szczytem</p>
                        </div>
                        <button wire:click="hideScheduleModal" class="p-2 text-gray-400 hover:text-white transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Schedule Options -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">Godzina</label>
                                <input type="number"
                                       wire:model.defer="scheduledHour"
                                       min="0" max="23"
                                       class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">Minuta</label>
                                <input type="number"
                                       wire:model.defer="scheduledMinute"
                                       min="0" max="59"
                                       class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.defer="repeatSchedule"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Powtarzaj import</span>
                            </label>
                        </div>

                        @if($repeatSchedule)
                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Częstotliwość</label>
                            <select wire:model.defer="scheduleFrequency"
                                    class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                                <option value="daily">Codziennie</option>
                                <option value="weekly">Tygodniowo</option>
                                <option value="monthly">Miesięcznie</option>
                            </select>
                        </div>
                        @endif

                        <div class="bg-blue-900 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-blue-300 mb-2">Godziny poza szczytem</h4>
                                    <p class="text-sm text-blue-200">
                                        Import zostanie automatycznie zaplanowany w godzinach 22:00 - 6:00 dla optymalnej wydajności.
                                        Aktualne okno: {{ implode(' - ', $offPeakHours) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-600">
                        <button wire:click="hideScheduleModal"
                                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                            Anuluj
                        </button>
                        <button wire:click="executeImport"
                                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Zaplanuj import
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Active Import Jobs -->
        @if(count($activeImportJobs) > 0)
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">

                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aktywne importy
                </h3>

                <div class="space-y-3">
                    @foreach($activeImportJobs as $job)
                        <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-white">{{ $job['job_name'] ?? 'Import PrestaShop' }}</h4>
                                    <p class="text-sm text-gray-400">ID: {{ $job['job_id'] }}</p>
                                </div>

                                <div class="flex items-center space-x-3">
                                    @if(isset($importProgress[$job['job_id']]))
                                        <div class="w-32 bg-gray-700 rounded-full h-2">
                                            <div class="bg-[#e0ac7e] h-2 rounded-full transition-all duration-300"
                                                 style="width: {{ $importProgress[$job['job_id']]['progress'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm text-white">{{ $importProgress[$job['job_id']]['progress'] ?? 0 }}%</span>
                                    @endif

                                    <button wire:click="cancelImportJob('{{ $job['job_id'] }}')"
                                            class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200"
                                            title="Anuluj import">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Import Jobs -->
        <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Ostatnie importy
                </h3>

                @if(count($rollbackHistory) > 0)
                <div class="text-sm text-gray-400">
                    Rollbacks dostępne: {{ $stats['rollbacks_available'] }}
                </div>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($recentJobs as $job)
                    <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-white">{{ $job->job_name }}</h4>
                                <p class="text-sm text-gray-400">{{ $job->created_at->diffForHumans() }} • {{ $job->prestashopShop->name ?? 'N/A' }}</p>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="text-right">
                                    @if($job->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Ukończone
                                        </span>
                                    @elseif($job->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Błąd
                                        </span>
                                    @elseif(in_array($job->status, ['pending', 'running']))
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            W trakcie
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($job->status) }}
                                        </span>
                                    @endif

                                    @if($job->records_processed && $job->records_total)
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ $job->records_processed }}/{{ $job->records_total }} rekordów
                                        </div>
                                    @endif
                                </div>

                                <!-- Rollback Button - 2.2.2.2.4 -->
                                @if($job->status === 'completed' && $enableRollback && $job->rollback_data && $job->created_at->gte(now()->subDays($maxRollbackDays)))
                                    <button wire:click="rollbackImport({{ $job->id }})"
                                            class="p-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors duration-200"
                                            onclick="return confirm('Czy na pewno chcesz wykonać rollback tego importu?')"
                                            title="Rollback importu">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-gray-400">Brak wcześniejszych importów</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-300 mb-2">Błędy:</h3>
                        <ul class="text-sm text-red-200 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<script>
function importManager() {
    return {
        init() {
            // Initialize import management functionality
        }
    }
}
</script>