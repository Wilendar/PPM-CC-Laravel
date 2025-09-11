<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Konserwacja Systemu</h1>
            <p class="text-gray-600">Zarządzanie zadaniami maintenance i monitorowanie zdrowia systemu</p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    wire:click="refreshStats" 
                    class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-sync mr-2"></i>Odśwież
            </button>
            
            <button type="button" 
                    wire:click="runDueTasks" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50">
                <i class="fas fa-play mr-2"></i>Uruchom zaległe
            </button>
        </div>
    </div>

    <!-- Messages -->
    @if($message)
    <div class="mb-6">
        <div class="p-4 rounded-md {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : ($messageType === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : ($messageType === 'warning' ? 'bg-yellow-50 text-yellow-800 border border-yellow-200' : 'bg-blue-50 text-blue-800 border border-blue-200')) }}">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-{{ $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) }} mr-2"></i>
                    {{ $message }}
                </div>
                <button wire:click="resetMessages" class="text-sm underline">Zamknij</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-tasks text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Wszystkie zadania</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_tasks'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Oczekujące</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_tasks'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Ukończone</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed_tasks'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-sync text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Cykliczne</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['recurring_tasks'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <button type="button" 
                wire:click="runQuickOptimization"
                wire:loading.attr="disabled"
                class="p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors disabled:opacity-50">
            <div class="text-center">
                <i class="fas fa-database text-2xl text-blue-600 mb-2" wire:loading.remove></i>
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2" wire:loading></i>
                <p class="font-medium text-blue-900">Optymalizacja BD</p>
                <p class="text-sm text-blue-600">Szybka optymalizacja bazy danych</p>
            </div>
        </button>
        
        <button type="button" 
                wire:click="cleanupLogs"
                wire:loading.attr="disabled"
                class="p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors disabled:opacity-50">
            <div class="text-center">
                <i class="fas fa-file-alt text-2xl text-green-600 mb-2" wire:loading.remove></i>
                <i class="fas fa-spinner fa-spin text-2xl text-green-600 mb-2" wire:loading></i>
                <p class="font-medium text-green-900">Czyszczenie logów</p>
                <p class="text-sm text-green-600">Usuń stare pliki logów</p>
            </div>
        </button>
        
        <button type="button" 
                wire:click="clearAllCache"
                wire:loading.attr="disabled"
                class="p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors disabled:opacity-50">
            <div class="text-center">
                <i class="fas fa-broom text-2xl text-orange-600 mb-2" wire:loading.remove></i>
                <i class="fas fa-spinner fa-spin text-2xl text-orange-600 mb-2" wire:loading></i>
                <p class="font-medium text-orange-900">Wyczyść cache</p>
                <p class="text-sm text-orange-600">Wyczyść cache aplikacji</p>
            </div>
        </button>
        
        <button type="button" 
                wire:click="runSecurityCheck"
                wire:loading.attr="disabled"
                class="p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors disabled:opacity-50">
            <div class="text-center">
                <i class="fas fa-shield-alt text-2xl text-red-600 mb-2" wire:loading.remove></i>
                <i class="fas fa-spinner fa-spin text-2xl text-red-600 mb-2" wire:loading></i>
                <p class="font-medium text-red-900">Kontrola bezpieczeństwa</p>
                <p class="text-sm text-red-600">Sprawdź bezpieczeństwo systemu</p>
            </div>
        </button>
    </div>

    <!-- Tabs -->
    <div class="bg-white shadow-sm rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
                <button type="button" 
                        wire:click="switchTab('tasks')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-list mr-2"></i>Zadania Maintenance
                </button>
                
                <button type="button" 
                        wire:click="switchTab('create')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'create' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-plus mr-2"></i>Nowe zadanie
                </button>
                
                <button type="button" 
                        wire:click="switchTab('health')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-heartbeat mr-2"></i>Zdrowie systemu
                </button>
            </nav>
        </div>

        <!-- Tab content -->
        <div class="p-6">
            @if($activeTab === 'tasks')
                <!-- Filters -->
                <div class="flex space-x-4 mb-6">
                    <div class="flex-1">
                        <input type="text" 
                               wire:model.debounce.300ms="search"
                               placeholder="Szukaj zadań..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <select wire:model="filterType" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Wszystkie typy</option>
                        @foreach($taskTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    <select wire:model="filterStatus" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Wszystkie statusy</option>
                        <option value="pending">Oczekuje</option>
                        <option value="running">Wykonuje się</option>
                        <option value="completed">Ukończone</option>
                        <option value="failed">Błąd</option>
                        <option value="skipped">Pominięte</option>
                    </select>
                </div>

                <!-- Tasks table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zadanie
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Typ / Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Planowane / Wykonane
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Czas trwania
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tasks as $task)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                            <i class="fas fa-{{ $task->type === 'database_optimization' ? 'database' : ($task->type === 'log_cleanup' ? 'file-alt' : 'cogs') }} text-gray-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $task->name }}</div>
                                            @if($task->creator)
                                            <div class="text-sm text-gray-500">przez {{ $task->creator->name }}</div>
                                            @endif
                                            @if($task->is_recurring)
                                            <div class="text-xs text-blue-600">
                                                <i class="fas fa-sync mr-1"></i>Cykliczne ({{ $task->recurrence_rule }})
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $task->type_label }}
                                        </span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800">
                                            {{ $task->status_label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div>Planowane: {{ $task->scheduled_at->format('d.m.Y H:i') }}</div>
                                        @if($task->completed_at)
                                        <div class="text-gray-500">Ukończone: {{ $task->completed_at->format('d.m.Y H:i') }}</div>
                                        @endif
                                        @if($task->next_run_at)
                                        <div class="text-blue-600">Następne: {{ $task->next_run_at->format('d.m.Y H:i') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $task->formatted_duration ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        @if($task->canRun())
                                        <button type="button" 
                                                wire:click="$emit('confirmExecute', {{ $task->id }})"
                                                class="text-green-600 hover:text-green-900"
                                                title="Wykonaj teraz">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        @endif
                                        
                                        @if($task->result_data)
                                        <button type="button" 
                                                onclick="showTaskResults({{ json_encode($task->result_data) }})"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Zobacz wyniki">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @endif
                                        
                                        @if($task->status !== 'running')
                                        <button type="button" 
                                                wire:click="$emit('confirmDelete', {{ $task->id }})"
                                                class="text-red-600 hover:text-red-900"
                                                title="Usuń">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            @if($task->error_message)
                            <tr class="bg-red-50">
                                <td colspan="5" class="px-6 py-2 text-sm text-red-700">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ $task->error_message }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center">
                                    <i class="fas fa-tasks text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">Brak zadań maintenance</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $tasks->links() }}
                </div>

            @elseif($activeTab === 'create')
                <!-- Create task form -->
                <div class="max-w-2xl">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Utwórz nowe zadanie maintenance</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Typ zadania</label>
                            <select wire:model="newTaskType" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($taskTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nazwa zadania (opcjonalnie)</label>
                            <input type="text" 
                                   wire:model="newTaskName"
                                   placeholder="Zostanie wygenerowana automatycznie"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Planowane wykonanie</label>
                            <input type="datetime-local" 
                                   wire:model="newTaskScheduled"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       wire:model="isRecurring"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-700">Zadanie cykliczne</label>
                            </div>
                            
                            @if($isRecurring)
                            <div class="ml-6">
                                <select wire:model="recurrenceRule" 
                                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($recurrenceOptions as $rule => $label)
                                    <option value="{{ $rule }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Configuration options -->
                        @if(!empty($taskConfiguration))
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900">Opcje konfiguracji</h4>
                            
                            @foreach($taskConfiguration as $key => $value)
                            <div class="flex items-center justify-between">
                                <label class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                
                                @if(is_bool($value))
                                <input type="checkbox" 
                                       wire:model="taskConfiguration.{{ $key }}"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                @else
                                <input type="text" 
                                       wire:model="taskConfiguration.{{ $key }}"
                                       class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" 
                                    wire:click="resetForm"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                Reset
                            </button>
                            
                            <button type="button" 
                                    wire:click="createTask" 
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="createTask">
                                    <i class="fas fa-plus mr-2"></i>Utwórz zadanie
                                </span>
                                <span wire:loading wire:target="createTask">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Tworzenie...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'health')
                <!-- System health -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900">Zdrowie systemu</h3>
                    
                    @if(isset($systemHealth['error']))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                        {{ $systemHealth['error'] }}
                    </div>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @if(isset($systemHealth['stats']))
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-medium text-gray-900 mb-4">Statystyki ogólne</h4>
                            <div class="space-y-3">
                                @foreach($systemHealth['stats'] as $key => $value)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                    <span class="text-sm font-medium">{{ is_numeric($value) ? number_format($value) : $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($systemHealth['performance']))
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-medium text-gray-900 mb-4">Wydajność</h4>
                            <div class="space-y-3">
                                @foreach($systemHealth['performance'] as $key => $value)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                    <span class="text-sm font-medium">{{ is_numeric($value) ? number_format($value, 2) : $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Results modal -->
<div id="resultsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeResultsModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Wyniki zadania</h3>
                    <button onclick="closeResultsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="resultsContent" class="text-sm text-gray-700"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showTaskResults(results) {
        const modal = document.getElementById('resultsModal');
        const content = document.getElementById('resultsContent');
        
        content.innerHTML = '<pre class="whitespace-pre-wrap">' + JSON.stringify(results, null, 2) + '</pre>';
        modal.classList.remove('hidden');
    }
    
    function closeResultsModal() {
        document.getElementById('resultsModal').classList.add('hidden');
    }
    
    document.addEventListener('livewire:load', function () {
        // Auto-hide messages
        Livewire.on('messageShown', () => {
            setTimeout(() => {
                @this.resetMessages();
            }, 5000);
        });

        // Confirm dialogs
        Livewire.on('confirmDelete', (taskId) => {
            if (confirm('Czy na pewno chcesz usunąć to zadanie?')) {
                @this.deleteTask(taskId);
            }
        });

        Livewire.on('confirmExecute', (taskId) => {
            if (confirm('Czy na pewno chcesz wykonać to zadanie teraz?')) {
                @this.executeTask(taskId);
            }
        });
    });
</script>
@endpush