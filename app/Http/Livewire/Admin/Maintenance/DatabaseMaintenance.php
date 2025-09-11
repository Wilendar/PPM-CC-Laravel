<?php

namespace App\Http\Livewire\Admin\Maintenance;

use App\Models\MaintenanceTask;
use App\Services\MaintenanceService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class DatabaseMaintenance extends Component
{
    use WithPagination, AuthorizesRequests;

    /**
     * Aktywny tab
     */
    public $activeTab = 'tasks';

    /**
     * Parametry nowego zadania
     */
    public $newTaskType = 'database_optimization';
    public $newTaskName = '';
    public $newTaskScheduled = '';
    public $isRecurring = false;
    public $recurrenceRule = 'daily';
    public $taskConfiguration = [];

    /**
     * Filtry
     */
    public $filterType = '';
    public $filterStatus = '';
    public $search = '';

    /**
     * Status i komunikaty
     */
    public $isLoading = false;
    public $message = '';
    public $messageType = '';

    /**
     * Statystyki
     */
    public $stats = [];

    /**
     * System health
     */
    public $systemHealth = [];

    /**
     * Services
     */
    protected $maintenanceService;

    public function boot(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function mount()
    {
        $this->authorize('admin.maintenance.manage');
        $this->loadStats();
        $this->loadSystemHealth();
        $this->resetTaskConfiguration();
        $this->newTaskScheduled = now()->addHour()->format('Y-m-d\TH:i');
    }

    public function render()
    {
        $tasks = $this->getTasks();

        return view('livewire.admin.maintenance.database-maintenance', [
            'tasks' => $tasks,
            'taskTypes' => MaintenanceTask::getAvailableTypes(),
            'recurrenceOptions' => $this->getRecurrenceOptions(),
        ])->layout('layouts.admin');
    }

    /**
     * Zmiana aktywnego tabu
     */
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetMessages();
        
        if ($tab === 'health') {
            $this->loadSystemHealth();
        }
    }

    /**
     * Pobierz listę zadań
     */
    private function getTasks()
    {
        $query = MaintenanceTask::with('creator')->latest();

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('error_message', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(20);
    }

    /**
     * Załaduj statystyki
     */
    private function loadStats()
    {
        $this->stats = $this->maintenanceService->getMaintenanceStats();
    }

    /**
     * Załaduj system health
     */
    private function loadSystemHealth()
    {
        // Uruchom podstawowe kontrole zdrowia systemu
        $task = MaintenanceTask::create([
            'name' => 'System Health Check',
            'type' => MaintenanceTask::TYPE_STATS_UPDATE,
            'status' => MaintenanceTask::STATUS_PENDING,
            'scheduled_at' => now(),
            'configuration' => [],
            'created_by' => auth()->id(),
        ]);

        $success = $this->maintenanceService->executeTask($task);
        
        if ($success && $task->result_data) {
            $this->systemHealth = $task->result_data;
        } else {
            $this->systemHealth = [
                'error' => 'Nie udało się pobrać informacji o zdrowiu systemu'
            ];
        }

        // Usuń tymczasowe zadanie
        $task->delete();
    }

    /**
     * Reset konfiguracji zadania
     */
    private function resetTaskConfiguration()
    {
        $this->taskConfiguration = MaintenanceTask::getDefaultConfiguration($this->newTaskType);
    }

    /**
     * Zmiana typu zadania
     */
    public function updatedNewTaskType($value)
    {
        $this->resetTaskConfiguration();
    }

    /**
     * Utwórz nowe zadanie
     */
    public function createTask()
    {
        try {
            $this->validate([
                'newTaskType' => 'required|string',
                'newTaskName' => 'nullable|string|max:255',
                'newTaskScheduled' => 'required|date|after:now',
                'isRecurring' => 'boolean',
                'recurrenceRule' => 'required_if:isRecurring,true|string',
            ]);

            $this->isLoading = true;

            // Nazwij zadanie jeśli nie podano nazwy
            $taskName = $this->newTaskName ?: 
                       MaintenanceTask::getAvailableTypes()[$this->newTaskType] . ' - ' . 
                       Carbon::parse($this->newTaskScheduled)->format('d.m.Y H:i');

            $task = MaintenanceTask::create([
                'name' => $taskName,
                'type' => $this->newTaskType,
                'scheduled_at' => $this->newTaskScheduled,
                'configuration' => $this->taskConfiguration,
                'is_recurring' => $this->isRecurring,
                'recurrence_rule' => $this->isRecurring ? $this->recurrenceRule : null,
                'created_by' => auth()->id(),
            ]);

            $this->showMessage('Zadanie maintenance zostało zaplanowane', 'success');
            $this->resetForm();
            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas tworzenia zadania: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Wykonaj zadanie natychmiast
     */
    public function executeTask($taskId)
    {
        try {
            $task = MaintenanceTask::findOrFail($taskId);
            
            if (!$task->canRun()) {
                $this->showMessage('Zadanie nie może być wykonane w tym momencie', 'error');
                return;
            }

            $this->isLoading = true;

            // Wykonaj zadanie
            $success = $this->maintenanceService->executeTask($task);

            if ($success) {
                $this->showMessage('Zadanie zostało wykonane pomyślnie', 'success');
            } else {
                $this->showMessage('Zadanie zakończyło się niepowodzeniem', 'error');
            }

            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas wykonywania: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Usuń zadanie
     */
    public function deleteTask($taskId)
    {
        try {
            $task = MaintenanceTask::findOrFail($taskId);
            
            if ($task->status === MaintenanceTask::STATUS_RUNNING) {
                $this->showMessage('Nie można usunąć zadania które jest obecnie wykonywane', 'error');
                return;
            }

            $task->delete();
            $this->showMessage('Zadanie zostało usunięte', 'success');
            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas usuwania: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Uruchom natychmiastową optymalizację bazy
     */
    public function runQuickOptimization()
    {
        try {
            $this->isLoading = true;

            $task = MaintenanceTask::create([
                'name' => 'Quick Database Optimization - ' . now()->format('H:i:s'),
                'type' => MaintenanceTask::TYPE_DB_OPTIMIZATION,
                'scheduled_at' => now(),
                'configuration' => [
                    'optimize_tables' => true,
                    'analyze_tables' => true,
                    'rebuild_indexes' => false,
                    'vacuum_tables' => false,
                ],
                'created_by' => auth()->id(),
            ]);

            $success = $this->maintenanceService->executeTask($task);

            if ($success) {
                $this->showMessage('Szybka optymalizacja została wykonana', 'success');
                $this->loadSystemHealth(); // Odśwież informacje o systemie
            } else {
                $this->showMessage('Optymalizacja nie powiodła się', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas optymalizacji: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Wyczyść logi
     */
    public function cleanupLogs()
    {
        try {
            $this->isLoading = true;

            $task = MaintenanceTask::create([
                'name' => 'Log Cleanup - ' . now()->format('H:i:s'),
                'type' => MaintenanceTask::TYPE_LOG_CLEANUP,
                'scheduled_at' => now(),
                'configuration' => [
                    'retention_days' => 30,
                    'compress_old_logs' => true,
                    'delete_empty_logs' => true,
                    'max_log_size_mb' => 100,
                ],
                'created_by' => auth()->id(),
            ]);

            $success = $this->maintenanceService->executeTask($task);

            if ($success) {
                $this->showMessage('Czyszczenie logów zostało wykonane', 'success');
            } else {
                $this->showMessage('Czyszczenie logów nie powiodło się', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas czyszczenia: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Wyczyść cache
     */
    public function clearAllCache()
    {
        try {
            $this->isLoading = true;

            $task = MaintenanceTask::create([
                'name' => 'Cache Cleanup - ' . now()->format('H:i:s'),
                'type' => MaintenanceTask::TYPE_CACHE_CLEANUP,
                'scheduled_at' => now(),
                'configuration' => [
                    'clear_application_cache' => true,
                    'clear_view_cache' => true,
                    'clear_route_cache' => true,
                    'clear_config_cache' => false, // Nie czyścimy config w production
                ],
                'created_by' => auth()->id(),
            ]);

            $success = $this->maintenanceService->executeTask($task);

            if ($success) {
                $this->showMessage('Cache został wyczyszczony', 'success');
                $this->loadSystemHealth(); // Odśwież informacje
            } else {
                $this->showMessage('Czyszczenie cache nie powiodło się', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas czyszczenia cache: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Uruchom kontrolę bezpieczeństwa
     */
    public function runSecurityCheck()
    {
        try {
            $this->isLoading = true;

            $task = MaintenanceTask::create([
                'name' => 'Security Check - ' . now()->format('H:i:s'),
                'type' => MaintenanceTask::TYPE_SECURITY_CHECK,
                'scheduled_at' => now(),
                'configuration' => [
                    'check_file_permissions' => true,
                    'check_config_security' => true,
                    'check_dependencies' => true,
                    'check_ssl_certificates' => false,
                ],
                'created_by' => auth()->id(),
            ]);

            $success = $this->maintenanceService->executeTask($task);

            if ($success) {
                $this->showMessage('Kontrola bezpieczeństwa została wykonana', 'success');
            } else {
                $this->showMessage('Kontrola bezpieczeństwa wykryła problemy', 'warning');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas kontroli bezpieczeństwa: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Uruchom zaległe zadania
     */
    public function runDueTasks()
    {
        try {
            $this->isLoading = true;

            $results = $this->maintenanceService->runDueTasks();
            
            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            if ($totalCount === 0) {
                $this->showMessage('Brak zaległych zadań do wykonania', 'info');
            } else {
                $this->showMessage("Wykonano {$successCount} z {$totalCount} zaległych zadań", 'success');
            }

            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas wykonywania zadań: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Odśwież statystyki
     */
    public function refreshStats()
    {
        $this->loadStats();
        $this->loadSystemHealth();
        $this->resetPage();
        $this->showMessage('Statystyki zostały odświeżone', 'info');
    }

    /**
     * Reset formularza
     */
    private function resetForm()
    {
        $this->newTaskName = '';
        $this->newTaskType = 'database_optimization';
        $this->newTaskScheduled = now()->addHour()->format('Y-m-d\TH:i');
        $this->isRecurring = false;
        $this->recurrenceRule = 'daily';
        $this->resetTaskConfiguration();
    }

    /**
     * Opcje powtarzania
     */
    private function getRecurrenceOptions()
    {
        return [
            'daily' => 'Codziennie',
            'weekly' => 'Tygodniowo',
            'monthly' => 'Miesięcznie',
        ];
    }

    /**
     * Pokaż wiadomość
     */
    private function showMessage($message, $type = 'info')
    {
        $this->message = $message;
        $this->messageType = $type;
        $this->emit('messageShown');
    }

    /**
     * Resetuj wiadomości
     */
    public function resetMessages()
    {
        $this->message = '';
        $this->messageType = '';
    }

    /**
     * Formatuj rozmiar
     */
    public function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Listeners
     */
    protected $listeners = [
        'taskCompleted' => 'refreshStats',
        'confirmDelete' => 'deleteTask',
        'confirmExecute' => 'executeTask',
    ];
}