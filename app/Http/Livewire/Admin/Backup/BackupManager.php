<?php

namespace App\Http\Livewire\Admin\Backup;

use App\Models\BackupJob;
use App\Services\BackupService;
use App\Services\SettingsService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class BackupManager extends Component
{
    use WithPagination, AuthorizesRequests;

    /**
     * Aktywny tab
     */
    public $activeTab = 'backups';

    /**
     * Parametry nowego backupu
     */
    public $newBackupType = 'database';
    public $newBackupName = '';
    public $backupConfiguration = [];

    /**
     * Filtry
     */
    public $filterType = '';
    public $filterStatus = '';
    public $search = '';

    /**
     * Ustawienia backupu
     */
    public $settings = [];

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
     * Services
     */
    protected $backupService;
    protected $settingsService;

    public function boot(BackupService $backupService, SettingsService $settingsService)
    {
        $this->backupService = $backupService;
        $this->settingsService = $settingsService;
    }

    public function mount()
    {
        $this->authorize('admin.backup.manage');
        $this->loadSettings();
        $this->loadStats();
        $this->resetBackupConfiguration();
    }

    public function render()
    {
        $backups = $this->getBackups();
        $diskSpace = $this->backupService->getDiskSpace();

        return view('livewire.admin.backup.backup-manager', [
            'backups' => $backups,
            'diskSpace' => $diskSpace,
            'backupTypes' => $this->getBackupTypes(),
        ])->layout('layouts.admin');
    }

    /**
     * Zmiana aktywnego tabu
     */
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetMessages();
        
        if ($tab === 'settings') {
            $this->loadSettings();
        }
    }

    /**
     * Pobierz listę backupów
     */
    private function getBackups()
    {
        $query = BackupJob::with('creator')->latest();

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
     * Załaduj ustawienia backupu
     */
    private function loadSettings()
    {
        $this->settings = [
            'backup_frequency' => $this->settingsService->get('backup_frequency', 'daily'),
            'backup_retention_days' => $this->settingsService->get('backup_retention_days', 30),
            'backup_compress' => $this->settingsService->get('backup_compress', true),
            'backup_encrypt' => $this->settingsService->get('backup_encrypt', false),
            'backup_include_logs' => $this->settingsService->get('backup_include_logs', false),
            'backup_auto_cleanup' => $this->settingsService->get('backup_auto_cleanup', true),
        ];
    }

    /**
     * Załaduj statystyki
     */
    private function loadStats()
    {
        $this->stats = $this->backupService->getBackupStats();
    }

    /**
     * Reset konfiguracji backupu
     */
    private function resetBackupConfiguration()
    {
        $this->backupConfiguration = BackupJob::getDefaultConfiguration($this->newBackupType);
    }

    /**
     * Zmiana typu backupu
     */
    public function updatedNewBackupType($value)
    {
        $this->resetBackupConfiguration();
    }

    /**
     * Utwórz nowy backup
     */
    public function createBackup()
    {
        try {
            $this->validate([
                'newBackupType' => 'required|in:database,files,full',
                'newBackupName' => 'nullable|string|max:255',
            ]);

            $this->isLoading = true;

            // Utwórz zadanie backupu
            $backup = $this->backupService->createBackup(
                $this->newBackupType,
                $this->backupConfiguration
            );

            // Nazwij backup jeśli nie podano nazwy
            if (!$this->newBackupName) {
                $this->newBackupName = 'Backup_' . ucfirst($this->newBackupType) . '_' . now()->format('Y-m-d_H-i-s');
            }
            
            $backup->update(['name' => $this->newBackupName]);

            // Uruchom backup w tle (w prawdziwej implementacji byłby to Job)
            dispatch(function() use ($backup) {
                $this->backupService->executeBackup($backup);
            })->afterResponse();

            $this->showMessage('Backup został zlecony i zostanie wykonany w tle', 'success');
            $this->resetForm();
            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas tworzenia backupu: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Pobierz backup
     */
    public function downloadBackup($backupId)
    {
        try {
            $backup = BackupJob::findOrFail($backupId);
            
            if (!$backup->isDownloadable()) {
                $this->showMessage('Backup nie jest dostępny do pobrania', 'error');
                return;
            }

            // W prawdziwej implementacji byłby to redirect do route
            $url = $this->backupService->getDownloadUrl($backup);
            $this->dispatch('downloadFile', $url);

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas pobierania: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Usuń backup
     */
    public function deleteBackup($backupId)
    {
        try {
            $backup = BackupJob::findOrFail($backupId);
            
            if (!$backup->isDeletable()) {
                $this->showMessage('Nie można usunąć aktywnego backupu', 'error');
                return;
            }

            // Usuń plik z dysku
            $backup->deleteBackupFile();
            
            // Usuń rekord
            $backup->delete();

            $this->showMessage('Backup został usunięty', 'success');
            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas usuwania: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Weryfikuj backup
     */
    public function verifyBackup($backupId)
    {
        try {
            $backup = BackupJob::findOrFail($backupId);
            $isValid = $this->backupService->verifyBackup($backup);

            if ($isValid) {
                $this->showMessage('Backup jest prawidłowy', 'success');
            } else {
                $this->showMessage('Backup jest uszkodzony lub niekompletny', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas weryfikacji: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Przywróć backup (tylko baza danych)
     */
    public function restoreBackup($backupId)
    {
        try {
            $backup = BackupJob::findOrFail($backupId);
            
            if ($backup->type !== BackupJob::TYPE_DATABASE) {
                $this->showMessage('Przywracanie jest dostępne tylko dla backupów bazy danych', 'error');
                return;
            }

            $this->isLoading = true;

            // W prawdziwej implementacji byłby to Job
            $success = $this->backupService->restoreBackup($backup);

            if ($success) {
                $this->showMessage('Backup został przywrócony pomyślnie. Może być konieczne wylogowanie się.', 'success');
            } else {
                $this->showMessage('Błąd podczas przywracania backupu', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas przywracania: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Zapisz ustawienia backupu
     */
    public function saveSettings()
    {
        try {
            $this->validate([
                'settings.backup_frequency' => 'required|in:manual,daily,weekly,monthly',
                'settings.backup_retention_days' => 'required|integer|min:1|max:365',
                'settings.backup_compress' => 'boolean',
                'settings.backup_encrypt' => 'boolean',
                'settings.backup_include_logs' => 'boolean',
                'settings.backup_auto_cleanup' => 'boolean',
            ]);

            foreach ($this->settings as $key => $value) {
                $this->settingsService->set($key, $value, 'backup');
            }

            $this->showMessage('Ustawienia backupu zostały zapisane', 'success');

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas zapisywania ustawień: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Wyczyść stare backupy
     */
    public function cleanupOldBackups()
    {
        try {
            $retentionDays = $this->settings['backup_retention_days'] ?? 30;
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            
            $oldBackups = BackupJob::where('created_at', '<', $cutoffDate)
                                  ->where('status', BackupJob::STATUS_COMPLETED)
                                  ->get();

            $deletedCount = 0;
            foreach ($oldBackups as $backup) {
                $backup->deleteBackupFile();
                $backup->delete();
                $deletedCount++;
            }

            $this->showMessage("Usunięto {$deletedCount} starych backupów", 'success');
            $this->loadStats();

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas czyszczenia: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Uruchom test backupu
     */
    public function runTestBackup()
    {
        try {
            $this->isLoading = true;

            $backup = $this->backupService->createBackup('database', [
                'include_structure' => true,
                'include_data' => false, // Test bez danych
                'exclude_tables' => ['sessions', 'cache', 'job_batches'],
                'compress' => false,
            ]);

            $backup->update(['name' => 'Test_Backup_' . now()->format('Y-m-d_H-i-s')]);

            // Wykonaj test backup synchronicznie dla małych baz
            $success = $this->backupService->executeBackup($backup);

            if ($success) {
                $this->showMessage('Test backupu zakończony pomyślnie', 'success');
                
                // Usuń testowy backup po weryfikacji
                $backup->deleteBackupFile();
                $backup->delete();
            } else {
                $this->showMessage('Test backupu nie powiódł się', 'error');
            }

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas testu: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Odśwież listę backupów
     */
    public function refreshBackups()
    {
        $this->loadStats();
        $this->resetPage();
        $this->showMessage('Lista backupów została odświeżona', 'info');
    }

    /**
     * Reset formularza
     */
    private function resetForm()
    {
        $this->newBackupName = '';
        $this->newBackupType = 'database';
        $this->resetBackupConfiguration();
    }

    /**
     * Typy backupów
     */
    private function getBackupTypes()
    {
        return [
            'database' => 'Baza danych',
            'files' => 'Pliki',
            'full' => 'Pełny backup',
        ];
    }

    /**
     * Pokaż wiadomość
     */
    private function showMessage($message, $type = 'info')
    {
        $this->message = $message;
        $this->messageType = $type;
        $this->dispatch('messageShown');
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
     * Formatuj rozmiar pliku
     */
    public function formatFileSize($bytes)
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
     * Pobierz procent użycia dysku
     */
    public function getDiskUsagePercentage($diskSpace)
    {
        if ($diskSpace['total_bytes'] > 0) {
            return round(($diskSpace['used_bytes'] / $diskSpace['total_bytes']) * 100, 1);
        }
        return 0;
    }

    /**
     * Listeners
     */
    protected $listeners = [
        'backupCompleted' => 'refreshBackups',
        'confirmDelete' => 'deleteBackup',
        'confirmRestore' => 'restoreBackup',
    ];
}