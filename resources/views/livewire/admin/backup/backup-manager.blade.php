<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Zarządzanie Backupami</h1>
            <p class="text-gray-400">Tworzenie, przywracanie i zarządzanie kopiami zapasowymi</p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    wire:click="refreshBackups" 
                    class="px-4 py-2 text-sm border border-gray-600 rounded-md hover:bg-gray-700 text-gray-300">
                <i class="fas fa-sync mr-2"></i>Odśwież
            </button>
            
            <button type="button" 
                    wire:click="runTestBackup" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50">
                <i class="fas fa-vial mr-2"></i>Test Backup
            </button>
        </div>
    </div>

    <!-- Messages -->
    @if($message)
    <div class="mb-6">
        <div class="p-4 rounded-md {{ $messageType === 'success' ? 'bg-green-900/30 text-green-300 border border-green-700' : ($messageType === 'error' ? 'bg-red-900/30 text-red-300 border border-red-700' : 'bg-blue-900/30 text-blue-300 border border-blue-700') }}">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-{{ $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') }} mr-2"></i>
                    {{ $message }}
                </div>
                <button wire:click="resetMessages" class="text-sm underline">Zamknij</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-900/30 rounded-lg">
                    <i class="fas fa-database text-blue-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Wszystkie backupy</p>
                    <p class="text-2xl font-semibold text-white">{{ $stats['total_backups'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-900/30 rounded-lg">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Ukończone</p>
                    <p class="text-2xl font-semibold text-white">{{ $stats['completed_backups'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-900/30 rounded-lg">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Nieudane</p>
                    <p class="text-2xl font-semibold text-white">{{ $stats['failed_backups'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-900/30 rounded-lg">
                    <i class="fas fa-hdd text-purple-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Rozmiar</p>
                    <p class="text-2xl font-semibold text-white">{{ $this->formatFileSize($stats['total_size_bytes'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk usage -->
    <div class="bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-white mb-4">Użycie dysku</h3>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span>Wolne miejsce</span>
                <span>{{ $this->formatFileSize($diskSpace['free_bytes']) }}</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $this->getDiskUsagePercentage($diskSpace) }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <span>{{ $this->formatFileSize($diskSpace['used_bytes']) }} używane</span>
                <span>{{ $this->formatFileSize($diskSpace['total_bytes']) }} całkowite</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-gray-800 shadow-sm rounded-lg">
        <div class="border-b border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6">
                <button type="button" 
                        wire:click="switchTab('backups')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'backups' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-500' }}">
                    <i class="fas fa-list mr-2"></i>Lista Backupów
                </button>
                
                <button type="button" 
                        wire:click="switchTab('create')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'create' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-500' }}">
                    <i class="fas fa-plus mr-2"></i>Utwórz Backup
                </button>
                
                <button type="button" 
                        wire:click="switchTab('settings')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'settings' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-500' }}">
                    <i class="fas fa-cog mr-2"></i>Ustawienia
                </button>
            </nav>
        </div>

        <!-- Tab content -->
        <div class="p-6">
            @if($activeTab === 'backups')
                <!-- Filters -->
                <div class="flex space-x-4 mb-6">
                    <div class="flex-1">
                        <input type="text" 
                               wire:model.debounce.300ms="search"
                               placeholder="Szukaj backupów..."
                               class="w-full px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <select wire:model="filterType" class="px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Wszystkie typy</option>
                        @foreach($backupTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    <select wire:model="filterStatus" class="px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Wszystkie statusy</option>
                        <option value="pending">Oczekuje</option>
                        <option value="running">Trwa</option>
                        <option value="completed">Ukończony</option>
                        <option value="failed">Nieudany</option>
                    </select>
                    
                    <button type="button" 
                            wire:click="cleanupOldBackups"
                            wire:confirm="Czy na pewno chcesz usunąć stare backupy?"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        <i class="fas fa-trash mr-2"></i>Wyczyść stare
                    </button>
                </div>

                <!-- Backups table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Backup
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Typ / Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rozmiar / Czas
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data utworzenia
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            @forelse($backups as $backup)
                            <tr class="hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                            <i class="fas fa-{{ $backup->type === 'database' ? 'database' : ($backup->type === 'files' ? 'folder' : 'archive') }} text-gray-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-white">{{ $backup->name }}</div>
                                            @if($backup->creator)
                                            <div class="text-sm text-gray-500">przez {{ $backup->creator->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-700 text-gray-200">
                                            {{ $backup->type_label }}
                                        </span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-{{ $backup->status_color }}-100 text-{{ $backup->status_color }}-800">
                                            {{ $backup->status_label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                    <div class="space-y-1">
                                        <div>{{ $backup->formatted_size ?: 'N/A' }}</div>
                                        @if($backup->duration)
                                        <div class="text-gray-500">{{ $backup->formatted_duration }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                    <div class="space-y-1">
                                        <div>{{ $backup->created_at->format('d.m.Y H:i') }}</div>
                                        @if($backup->completed_at)
                                        <div class="text-gray-500">Ukończony: {{ $backup->completed_at->format('H:i') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        @if($backup->isDownloadable())
                                        <button type="button" 
                                                wire:click="downloadBackup({{ $backup->id }})"
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        @endif
                                        
                                        @if($backup->status === 'completed')
                                        <button type="button" 
                                                wire:click="verifyBackup({{ $backup->id }})"
                                                class="text-green-600 hover:text-green-900"
                                                title="Weryfikuj">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        
                                        @if($backup->type === 'database')
                                        <button type="button" 
                                                wire:click="$emit('confirmRestore', {{ $backup->id }})"
                                                class="text-orange-600 hover:text-orange-900"
                                                title="Przywróć">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        @endif
                                        @endif
                                        
                                        @if($backup->isDeletable())
                                        <button type="button" 
                                                wire:click="$emit('confirmDelete', {{ $backup->id }})"
                                                class="text-red-600 hover:text-red-900"
                                                title="Usuń">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            @if($backup->error_message)
                            <tr class="bg-red-900/20">
                                <td colspan="5" class="px-6 py-2 text-sm text-red-300">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ $backup->error_message }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center">
                                    <i class="fas fa-database text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">Brak backupów</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $backups->links() }}
                </div>

            @elseif($activeTab === 'create')
                <!-- Create backup form -->
                <div class="max-w-2xl">
                    <h3 class="text-lg font-medium text-white mb-4">Utwórz nowy backup</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Typ backupu</label>
                            <select wire:model="newBackupType" 
                                    class="w-full px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($backupTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa backupu (opcjonalnie)</label>
                            <input type="text" 
                                   wire:model="newBackupName"
                                   placeholder="Zostanie wygenerowana automatycznie"
                                   class="w-full px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- Configuration options -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-white">Opcje konfiguracji</h4>
                            
                            @foreach($backupConfiguration as $key => $value)
                            <div class="flex items-center justify-between">
                                <label class="text-sm text-gray-300">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                
                                @if(is_bool($value))
                                <input type="checkbox" 
                                       wire:model="backupConfiguration.{{ $key }}"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                @else
                                <input type="text" 
                                       wire:model="backupConfiguration.{{ $key }}"
                                       class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                @endif
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" 
                                    wire:click="resetForm"
                                    class="px-4 py-2 border border-gray-600 rounded-md bg-gray-700 text-white text-sm hover:bg-gray-700">
                                Reset
                            </button>
                            
                            <button type="button" 
                                    wire:click="createBackup" 
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="createBackup">
                                    <i class="fas fa-play mr-2"></i>Utwórz Backup
                                </span>
                                <span wire:loading wire:target="createBackup">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Tworzenie...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'settings')
                <!-- Backup settings -->
                <div class="max-w-2xl">
                    <h3 class="text-lg font-medium text-white mb-4">Ustawienia backupów</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Częstotliwość automatycznych backupów</label>
                            <select wire:model="settings.backup_frequency" 
                                    class="w-full px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="manual">Tylko ręczne</option>
                                <option value="daily">Codziennie</option>
                                <option value="weekly">Tygodniowo</option>
                                <option value="monthly">Miesięcznie</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Przechowywanie backupów (dni)</label>
                            <input type="number" 
                                   wire:model="settings.backup_retention_days"
                                   min="1" 
                                   max="365"
                                   class="w-full px-3 py-2 border border-gray-600 rounded-md bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       wire:model="settings.backup_compress"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-300">Kompresja backupów (zalecane)</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       wire:model="settings.backup_encrypt"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-300">Szyfrowanie backupów</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       wire:model="settings.backup_include_logs"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-300">Uwzględnij pliki logów</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       wire:model="settings.backup_auto_cleanup"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-300">Automatyczne usuwanie starych backupów</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="button" 
                                    wire:click="saveSettings"
                                    class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>Zapisz ustawienia
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Auto-hide messages
        Livewire.on('messageShown', () => {
            setTimeout(() => {
                @this.resetMessages();
            }, 5000);
        });

        // Confirm dialogs
        Livewire.on('confirmDelete', (backupId) => {
            if (confirm('Czy na pewno chcesz usunąć ten backup? Ta operacja jest nieodwracalna.')) {
                @this.deleteBackup(backupId);
            }
        });

        Livewire.on('confirmRestore', (backupId) => {
            if (confirm('UWAGA: Przywracanie backupu zastąpi całą bazę danych. Czy na pewno chcesz kontynuować?')) {
                @this.restoreBackup(backupId);
            }
        });

        // Handle download
        Livewire.on('downloadFile', (url) => {
            window.location.href = url;
        });
    });
</script>
@endpush