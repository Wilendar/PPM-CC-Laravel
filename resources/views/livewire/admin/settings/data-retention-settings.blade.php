<div>
    {{-- Messages --}}
    @if($message)
    <div class="mb-6"
         x-data="{ show: true }"
         x-init="setTimeout(() => { show = false; $wire.call('resetMessages') }, 5000)"
         x-show="show"
         x-transition>
        <div class="p-4 rounded-md {{ $messageType === 'success' ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-700' : ($messageType === 'error' ? 'bg-red-900/50 text-red-300 border border-red-700' : 'bg-blue-900/50 text-blue-300 border border-blue-700') }}">
            <div class="flex items-center">
                <i class="fas {{ $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') }} mr-3"></i>
                <span>{{ $message }}</span>
                <button wire:click="resetMessages" class="ml-auto text-sm underline opacity-70 hover:opacity-100">Zamknij</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Section 1: Database Retention --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-database mr-2" style="color: var(--mpp-primary)"></i>
            Retencja danych w bazie
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Tabela</th>
                        <th class="text-center py-3 px-4 text-gray-400 font-medium">Retencja (dni)</th>
                        <th class="text-right py-3 px-4 text-gray-400 font-medium">Rozmiar</th>
                        <th class="text-right py-3 px-4 text-gray-400 font-medium">Rekordy</th>
                        <th class="text-center py-3 px-4 text-gray-400 font-medium">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($retentionConfig as $table => $config)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors"
                        wire:key="retention-row-{{ $table }}"
                        x-data="{ editDays: {{ $config['retention_days'] }}, editing: false }">
                        <td class="py-3 px-4">
                            <span class="text-gray-200 font-mono text-xs">{{ $table }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <template x-if="!editing">
                                <button @click="editing = true"
                                        class="text-gray-300 hover:text-white cursor-pointer px-3 py-1 rounded bg-gray-700 hover:bg-gray-600 transition-colors">
                                    {{ $config['retention_days'] }}
                                </button>
                            </template>
                            <template x-if="editing">
                                <div class="flex items-center justify-center gap-2">
                                    <input type="number" x-model.number="editDays" min="1" max="365"
                                           class="w-20 form-input-enterprise text-center text-sm py-1"
                                           @keydown.enter="$wire.saveRetention('{{ $table }}', editDays); editing = false"
                                           @keydown.escape="editing = false">
                                    <button @click="$wire.saveRetention('{{ $table }}', editDays); editing = false"
                                            class="text-emerald-400 hover:text-emerald-300">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button @click="editing = false" class="text-gray-400 hover:text-gray-300">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <span class="text-gray-300 font-mono text-xs">
                                {{ ($tableStats[$table]['size_mb'] ?? 0) }} MB
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <span class="text-gray-300 font-mono text-xs">
                                {{ number_format($tableStats[$table]['row_count'] ?? 0) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button wire:click="cleanupTable('{{ $table }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Na pewno wyczysc tabele {{ $table }}?"
                                    class="btn-enterprise-sm btn-enterprise-danger">
                                <i class="fas fa-trash-alt mr-1"></i>
                                <span wire:loading.remove wire:target="cleanupTable('{{ $table }}')">Wyczysc</span>
                                <span wire:loading wire:target="cleanupTable('{{ $table }}')">...</span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 2: Archiving --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-archive mr-2" style="color: var(--mpp-primary)"></i>
            Archiwizacja
        </h3>

        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-5 space-y-5">
            {{-- Archive toggle --}}
            <div class="flex items-center justify-between"
                 x-data="{ archiveEnabled: {{ $this->retentionService->isArchiveEnabled() ? 'true' : 'false' }} }">
                <div>
                    <span class="text-gray-200">Archiwizacja przed usunieciem</span>
                    <p class="text-xs text-gray-500">Eksportuj dane do JSON.gz przed automatycznym czyszczeniem</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="archiveEnabled"
                           @change="$wire.toggleArchive(archiveEnabled)"
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            {{-- Sync cleanup toggle --}}
            <div class="flex items-center justify-between border-t border-gray-700 pt-4"
                 x-data="{ syncEnabled: {{ $this->retentionService->isSyncCleanupEnabled() ? 'true' : 'false' }} }">
                <div>
                    <span class="text-gray-200">Auto-cleanup sync_jobs</span>
                    <p class="text-xs text-gray-500">Automatyczne czyszczenie starych zadan synchronizacji (scheduler)</p>
                    <p class="text-xs text-yellow-400/80 mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Aktualnie: {{ $this->retentionService->isSyncCleanupEnabled() ? 'WLACZONY' : 'WYLACZONY' }}
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="syncEnabled"
                           @change="$wire.toggleSyncCleanup(syncEnabled)"
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            {{-- Archive retention --}}
            <div class="flex items-center justify-between border-t border-gray-700 pt-4"
                 x-data="{ archiveDays: {{ $this->retentionService->getArchiveRetentionDays() }}, editing: false }">
                <div>
                    <span class="text-gray-200">Retencja archiwow</span>
                    <p class="text-xs text-gray-500">Ile dni przechowywac pliki archiwalne</p>
                </div>
                <div class="flex items-center gap-3">
                    <template x-if="!editing">
                        <button @click="editing = true"
                                class="text-gray-300 hover:text-white px-3 py-1 rounded bg-gray-700 hover:bg-gray-600 transition-colors text-sm">
                            <span x-text="archiveDays"></span> dni
                        </button>
                    </template>
                    <template x-if="editing">
                        <div class="flex items-center gap-2">
                            <input type="number" x-model.number="archiveDays" min="30" max="730"
                                   class="w-24 form-input-enterprise text-center text-sm py-1"
                                   @keydown.enter="$wire.saveArchiveRetention(archiveDays); editing = false"
                                   @keydown.escape="editing = false">
                            <button @click="$wire.saveArchiveRetention(archiveDays); editing = false"
                                    class="text-emerald-400 hover:text-emerald-300">
                                <i class="fas fa-check"></i>
                            </button>
                            <button @click="editing = false" class="text-gray-400 hover:text-gray-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Format info --}}
            <div class="flex items-center justify-between border-t border-gray-700 pt-4">
                <div>
                    <span class="text-gray-200">Format archiwow</span>
                    <p class="text-xs text-gray-500">Skompresowany JSON (gzip)</p>
                </div>
                <span class="text-gray-400 text-sm font-mono bg-gray-700 px-3 py-1 rounded">JSON.gz</span>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 border-t border-gray-700 pt-4">
                <button wire:click="archiveAll"
                        wire:loading.attr="disabled"
                        wire:confirm="Na pewno zarchiwizowac wszystkie tabele?"
                        class="btn-enterprise-primary btn-enterprise-sm">
                    <i class="fas fa-archive mr-2" wire:loading.remove wire:target="archiveAll"></i>
                    <i class="fas fa-spinner fa-spin mr-2" wire:loading wire:target="archiveAll"></i>
                    Archiwizuj wszystkie tabele
                </button>
            </div>

            {{-- Archive stats --}}
            @if(!empty($archiveStats['tables']))
            <div class="border-t border-gray-700 pt-4">
                <p class="text-xs text-gray-500 mb-2">
                    Statystyki: {{ $archiveStats['total_files'] ?? 0 }} plikow,
                    {{ \App\Services\ArchiveService::formatBytes($archiveStats['total_size'] ?? 0) }}
                </p>
            </div>
            @endif
        </div>
    </div>

    {{-- Section 3: Archive Files --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-file-archive mr-2" style="color: var(--mpp-primary)"></i>
                Pliki archiwalne
            </h3>
            <button wire:click="cleanupOldArchives"
                    wire:confirm="Na pewno usunac stare archiwa?"
                    class="btn-enterprise-secondary btn-enterprise-sm">
                <i class="fas fa-broom mr-1"></i> Usun stare archiwa
            </button>
        </div>

        @if(count($archives) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Plik</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Tabela</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Data</th>
                        <th class="text-right py-3 px-4 text-gray-400 font-medium">Rozmiar</th>
                        <th class="text-center py-3 px-4 text-gray-400 font-medium">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archives as $archive)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors"
                        wire:key="archive-row-{{ $archive['filename'] }}">
                        <td class="py-3 px-4">
                            <span class="text-gray-200 font-mono text-xs">{{ $archive['filename'] }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-gray-300 text-xs">{{ $archive['table'] }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-gray-300 text-xs">{{ $archive['date']->format('Y-m-d H:i') }}</span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <span class="text-gray-300 font-mono text-xs">{{ \App\Services\ArchiveService::formatBytes($archive['size']) }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="downloadArchive('{{ $archive['filename'] }}')"
                                        class="text-blue-400 hover:text-blue-300 text-xs" title="Pobierz">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button wire:click="deleteArchive('{{ $archive['filename'] }}')"
                                        wire:confirm="Na pewno usunac {{ $archive['filename'] }}?"
                                        class="text-red-400 hover:text-red-300 text-xs" title="Usun">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8 bg-gray-800/30 rounded-lg border border-gray-700">
            <i class="fas fa-archive text-3xl text-gray-600 mb-3"></i>
            <p class="text-gray-500">Brak plikow archiwalnych</p>
            <p class="text-gray-600 text-xs mt-1">Archiwa pojawia sie po uruchomieniu archiwizacji</p>
        </div>
        @endif
    </div>
</div>
