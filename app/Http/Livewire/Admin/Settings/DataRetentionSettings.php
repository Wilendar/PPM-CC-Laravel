<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use App\Services\ArchiveService;
use App\Services\RetentionConfigService;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionSettings extends Component
{
    use AuthorizesRequests;

    public array $retentionConfig = [];
    public array $tableStats = [];
    public array $archives = [];
    public array $archiveStats = [];
    public string $message = '';
    public string $messageType = '';
    public bool $isLoading = false;

    protected RetentionConfigService $retentionService;
    protected ArchiveService $archiveService;

    public function boot(RetentionConfigService $retentionService, ArchiveService $archiveService)
    {
        $this->retentionService = $retentionService;
        $this->archiveService = $archiveService;
    }

    public function mount()
    {
        $this->authorize('system.manage');
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.settings.data-retention-settings');
    }

    public function loadData()
    {
        $this->isLoading = true;

        $this->retentionConfig = $this->retentionService->getAllRetentionConfig();
        $this->tableStats = $this->getTableStats();
        $this->archives = $this->archiveService->listArchives()->take(20)->toArray();
        $this->archiveStats = $this->archiveService->getArchiveStats();

        $this->isLoading = false;
    }

    /**
     * Save retention days for a table
     */
    public function saveRetention(string $table, int $days)
    {
        $this->authorize('system.manage');

        if ($days < 1 || $days > 365) {
            $this->showMessage('Retencja musi byc miedzy 1 a 365 dni.', 'error');
            return;
        }

        SystemSetting::set(
            "retention.{$table}.days",
            $days,
            'data_retention',
            'integer',
            "Retention period for {$table}"
        );

        $this->loadData();
        $this->showMessage("Retencja dla {$table} zapisana: {$days} dni.", 'success');

        Log::info('Retention updated', ['table' => $table, 'days' => $days]);
    }

    /**
     * Manual cleanup for specific table
     */
    public function cleanupTable(string $table)
    {
        $this->authorize('system.manage');

        $config = $this->retentionConfig[$table] ?? null;
        if (!$config) {
            $this->showMessage("Tabela {$table} nie jest skonfigurowana.", 'error');
            return;
        }

        $command = $config['command'] ?? null;

        if ($command) {
            try {
                \Artisan::call($command);
                $output = \Artisan::output();
                $this->showMessage("Czyszczenie {$table} zakonczone. {$output}", 'success');
            } catch (\Exception $e) {
                $this->showMessage("Blad czyszczenia: {$e->getMessage()}", 'error');
            }
        } else {
            // Generic cleanup
            $days = $config['retention_days'];
            $dateColumn = $config['date_column'] ?? 'created_at';
            $cutoff = now()->subDays($days);

            $deleted = DB::table($table)
                ->where($dateColumn, '<', $cutoff)
                ->delete();

            $this->showMessage("Usunieto {$deleted} rekordow z {$table}.", 'success');
        }

        $this->loadData();
    }

    /**
     * Archive all tables before cleanup
     */
    public function archiveAll()
    {
        $this->authorize('system.manage');

        if (!$this->archiveService->isEnabled()) {
            $this->showMessage('Archiwizacja jest wylaczona.', 'error');
            return;
        }

        $totalArchived = 0;
        $errors = [];

        foreach ($this->retentionConfig as $table => $config) {
            if (!($config['enabled'] ?? true)) {
                continue;
            }

            try {
                $cutoff = now()->subDays($config['retention_days']);
                $result = $this->archiveService->archive($table, $cutoff, [
                    'date_column' => $config['date_column'] ?? 'created_at',
                    'chunk_size' => $config['chunk_size'] ?? 1000,
                ]);
                $totalArchived += $result['archived'];
            } catch (\Exception $e) {
                $errors[] = "{$table}: {$e->getMessage()}";
            }
        }

        if (empty($errors)) {
            $this->showMessage("Zarchiwizowano {$totalArchived} rekordow ze wszystkich tabel.", 'success');
        } else {
            $this->showMessage("Zarchiwizowano {$totalArchived} rekordow. Bledy: " . implode(', ', $errors), 'error');
        }

        $this->loadData();
    }

    /**
     * Toggle archive enabled
     */
    public function toggleArchive(bool $enabled)
    {
        $this->authorize('system.manage');
        SystemSetting::set('retention.archive_enabled', $enabled, 'data_retention', 'boolean');
        $this->showMessage($enabled ? 'Archiwizacja wlaczona.' : 'Archiwizacja wylaczona.', 'success');
    }

    /**
     * Toggle sync cleanup enabled
     */
    public function toggleSyncCleanup(bool $enabled)
    {
        $this->authorize('system.manage');
        SystemSetting::set('retention.sync_cleanup_enabled', $enabled, 'data_retention', 'boolean');
        $this->showMessage($enabled ? 'Auto-cleanup sync_jobs wlaczony.' : 'Auto-cleanup sync_jobs wylaczony.', 'success');
    }

    /**
     * Save archive retention days
     */
    public function saveArchiveRetention(int $days)
    {
        $this->authorize('system.manage');

        if ($days < 30 || $days > 730) {
            $this->showMessage('Retencja archiwow musi byc miedzy 30 a 730 dni.', 'error');
            return;
        }

        SystemSetting::set('retention.archive_retention_days', $days, 'data_retention', 'integer');
        $this->showMessage("Retencja archiwow: {$days} dni.", 'success');
    }

    /**
     * Download archive file
     */
    public function downloadArchive(string $filename)
    {
        $path = $this->archiveService->downloadPath($filename);

        if (!$path || !file_exists($path)) {
            $this->showMessage('Plik archiwum nie znaleziony.', 'error');
            return;
        }

        return response()->download($path, $filename);
    }

    /**
     * Delete archive file
     */
    public function deleteArchive(string $filename)
    {
        $this->authorize('system.manage');

        $archives = $this->archiveService->listArchives();
        $archive = $archives->firstWhere('filename', $filename);

        if ($archive) {
            \Storage::disk('local')->delete($archive['path']);
            $this->showMessage("Archiwum {$filename} usuniete.", 'success');
            $this->loadData();
        } else {
            $this->showMessage('Plik archiwum nie znaleziony.', 'error');
        }
    }

    /**
     * Cleanup old archives
     */
    public function cleanupOldArchives()
    {
        $this->authorize('system.manage');

        $deleted = $this->archiveService->cleanupOldArchives();
        $this->showMessage("Usunieto {$deleted} starych archiwow.", 'success');
        $this->loadData();
    }

    protected function getTableStats(): array
    {
        $stats = [];
        $tables = array_keys($this->retentionConfig);

        foreach ($tables as $table) {
            try {
                if (!\Schema::hasTable($table)) {
                    continue;
                }

                $size = DB::selectOne("
                    SELECT
                        ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                        table_rows AS row_count
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = ?
                ", [$table]);

                $stats[$table] = [
                    'size_mb' => $size->size_mb ?? 0,
                    'row_count' => $size->row_count ?? 0,
                ];
            } catch (\Exception $e) {
                $stats[$table] = ['size_mb' => 0, 'row_count' => 0];
            }
        }

        return $stats;
    }

    protected function showMessage(string $message, string $type = 'info')
    {
        $this->message = $message;
        $this->messageType = $type;
    }

    public function resetMessages()
    {
        $this->message = '';
        $this->messageType = '';
    }
}
