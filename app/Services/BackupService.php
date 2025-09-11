<?php

namespace App\Services;

use App\Models\BackupJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Carbon\Carbon;

class BackupService
{
    /**
     * Katalog dla backupów
     */
    private const BACKUP_DIR = 'backups';

    /**
     * Utwórz nowy backup
     */
    public function createBackup(string $type, array $configuration = []): BackupJob
    {
        $config = array_merge(
            BackupJob::getDefaultConfiguration($type),
            $configuration
        );

        $backup = BackupJob::create([
            'name' => $this->generateBackupName($type),
            'type' => $type,
            'configuration' => $config,
            'created_by' => auth()->id(),
        ]);

        return $backup;
    }

    /**
     * Wykonaj backup
     */
    public function executeBackup(BackupJob $backup): bool
    {
        try {
            $backup->markAsStarted();
            Log::info("Starting backup: {$backup->name}");

            $filePath = null;
            $sizeBytes = 0;

            switch ($backup->type) {
                case BackupJob::TYPE_DATABASE:
                    [$filePath, $sizeBytes] = $this->backupDatabase($backup);
                    break;

                case BackupJob::TYPE_FILES:
                    [$filePath, $sizeBytes] = $this->backupFiles($backup);
                    break;

                case BackupJob::TYPE_FULL:
                    [$filePath, $sizeBytes] = $this->backupFull($backup);
                    break;

                default:
                    throw new \Exception("Nieznany typ backupu: {$backup->type}");
            }

            $backup->markAsCompleted($filePath, $sizeBytes);
            Log::info("Backup completed: {$backup->name}, size: {$sizeBytes} bytes");

            // Usuń stare backupy zgodnie z polityką retencji
            $this->cleanupOldBackups($backup->configuration['retention_days'] ?? 30);

            return true;

        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error("Backup failed: {$backup->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Backup bazy danych
     */
    private function backupDatabase(BackupJob $backup): array
    {
        $config = $backup->configuration;
        $filename = 'database_' . date('Y-m-d_H-i-s') . '.sql';
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Utwórz katalog temp jeśli nie istnieje
        File::ensureDirectoryExists(dirname($tempPath));

        $dbConfig = config('database.connections.' . config('database.default'));
        
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s %s %s > %s',
            $dbConfig['host'],
            $dbConfig['port'] ?? 3306,
            $dbConfig['username'],
            $dbConfig['password'],
            $config['include_structure'] ? '' : '--no-create-info',
            $config['include_data'] ? '' : '--no-data',
            $dbConfig['database'],
            escapeshellarg($tempPath)
        );

        // Wykluczenie tabel jeśli skonfigurowane
        if (!empty($config['exclude_tables'])) {
            $excludes = array_map(function($table) use ($dbConfig) {
                return '--ignore-table=' . $dbConfig['database'] . '.' . $table;
            }, $config['exclude_tables']);
            
            $command = str_replace(
                $dbConfig['database'],
                implode(' ', $excludes) . ' ' . $dbConfig['database'],
                $command
            );
        }

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Mysqldump failed with code: ' . $returnCode);
        }

        if (!file_exists($tempPath)) {
            throw new \Exception('Backup file was not created');
        }

        // Przeniesienie i kompresja jeśli włączona
        $finalPath = $this->moveAndCompressFile($tempPath, $filename, $config);
        $sizeBytes = filesize(storage_path('app/' . $finalPath));

        return [$finalPath, $sizeBytes];
    }

    /**
     * Backup plików
     */
    private function backupFiles(BackupJob $backup): array
    {
        $config = $backup->configuration;
        $filename = 'files_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $filename);
        
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create zip file');
        }

        $basePath = base_path();
        $filesToBackup = [];

        // Storage/uploads
        if ($config['include_uploads']) {
            $uploadsPath = storage_path('app/public');
            if (is_dir($uploadsPath)) {
                $this->addDirectoryToZip($zip, $uploadsPath, 'uploads/', $config);
            }
        }

        // Logi (opcjonalnie)
        if ($config['include_logs']) {
            $logsPath = storage_path('logs');
            if (is_dir($logsPath)) {
                $this->addDirectoryToZip($zip, $logsPath, 'logs/', $config);
            }
        }

        // Config files
        $configPath = base_path('config');
        $this->addDirectoryToZip($zip, $configPath, 'config/', $config);

        // .env file
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $zip->addFile($envPath, '.env');
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            throw new \Exception('Zip file was not created');
        }

        // Przeniesienie do finalnego katalogu
        $finalPath = self::BACKUP_DIR . '/' . $filename;
        Storage::put($finalPath, file_get_contents($zipPath));
        unlink($zipPath);

        $sizeBytes = Storage::size($finalPath);

        return [$finalPath, $sizeBytes];
    }

    /**
     * Pełny backup (baza + pliki)
     */
    private function backupFull(BackupJob $backup): array
    {
        $config = $backup->configuration;
        $filename = 'full_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $filename);
        
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create zip file');
        }

        // Backup bazy danych do tymczasowego pliku
        if ($config['include_database']) {
            $dbBackup = BackupJob::create([
                'name' => 'temp_db_for_full_backup',
                'type' => BackupJob::TYPE_DATABASE,
                'configuration' => $config,
                'created_by' => $backup->created_by,
            ]);

            [$dbPath, $dbSize] = $this->backupDatabase($dbBackup);
            $zip->addFile(storage_path('app/' . $dbPath), 'database.sql');
            
            // Usuń tymczasowy backup bazy
            Storage::delete($dbPath);
            $dbBackup->delete();
        }

        // Dodaj pliki
        if ($config['include_files']) {
            // Kod podobny do backupFiles() ale dodawanie bezpośrednio do zip
            $uploadsPath = storage_path('app/public');
            if (is_dir($uploadsPath)) {
                $this->addDirectoryToZip($zip, $uploadsPath, 'files/uploads/', $config);
            }
        }

        // Dodaj konfigurację
        if ($config['include_config']) {
            $configPath = base_path('config');
            $this->addDirectoryToZip($zip, $configPath, 'config/', $config);
            
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $zip->addFile($envPath, '.env');
            }
        }

        $zip->close();

        // Przeniesienie do finalnego katalogu
        $finalPath = self::BACKUP_DIR . '/' . $filename;
        Storage::put($finalPath, file_get_contents($zipPath));
        unlink($zipPath);

        $sizeBytes = Storage::size($finalPath);

        return [$finalPath, $sizeBytes];
    }

    /**
     * Dodaj katalog do ZIP z filtrowaniem
     */
    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath, array $config): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // Sprawdź rozmiar pliku
                if (isset($config['max_file_size_mb'])) {
                    $fileSizeMB = $file->getSize() / 1024 / 1024;
                    if ($fileSizeMB > $config['max_file_size_mb']) {
                        continue;
                    }
                }

                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourcePath) + 1);
                
                $zip->addFile($filePath, $zipPath . $relativePath);
            }
        }
    }

    /**
     * Przenieś i skompresuj plik jeśli potrzeba
     */
    private function moveAndCompressFile(string $tempPath, string $filename, array $config): string
    {
        if ($config['compress']) {
            $compressedFilename = pathinfo($filename, PATHINFO_FILENAME) . '.gz';
            $compressedPath = storage_path('app/temp/' . $compressedFilename);
            
            $data = file_get_contents($tempPath);
            file_put_contents($compressedPath, gzencode($data));
            
            unlink($tempPath);
            $tempPath = $compressedPath;
            $filename = $compressedFilename;
        }

        $finalPath = self::BACKUP_DIR . '/' . $filename;
        Storage::put($finalPath, file_get_contents($tempPath));
        unlink($tempPath);

        return $finalPath;
    }

    /**
     * Generuj nazwę backupu
     */
    private function generateBackupName(string $type): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $typeLabel = match($type) {
            BackupJob::TYPE_DATABASE => 'DB',
            BackupJob::TYPE_FILES => 'Files',
            BackupJob::TYPE_FULL => 'Full',
            default => 'Unknown'
        };
        
        return "PPM_Backup_{$typeLabel}_{$timestamp}";
    }

    /**
     * Usuń stare backupy
     */
    private function cleanupOldBackups(int $retentionDays): void
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        $oldBackups = BackupJob::where('created_at', '<', $cutoffDate)
                              ->where('status', BackupJob::STATUS_COMPLETED)
                              ->get();

        foreach ($oldBackups as $backup) {
            // Usuń plik z dysku
            $backup->deleteBackupFile();
            
            // Usuń rekord z bazy
            $backup->delete();
            
            Log::info("Cleaned up old backup: {$backup->name}");
        }
    }

    /**
     * Pobierz statystyki backupów
     */
    public function getBackupStats(): array
    {
        return [
            'total_backups' => BackupJob::count(),
            'completed_backups' => BackupJob::completed()->count(),
            'failed_backups' => BackupJob::failed()->count(),
            'active_backups' => BackupJob::active()->count(),
            'total_size_bytes' => BackupJob::completed()->sum('size_bytes'),
            'last_successful_backup' => BackupJob::completed()
                                                ->latest('completed_at')
                                                ->first()?->completed_at,
        ];
    }

    /**
     * Sprawdź dostępne miejsce na dysku
     */
    public function getDiskSpace(): array
    {
        $storagePath = storage_path();
        
        return [
            'free_bytes' => disk_free_space($storagePath),
            'total_bytes' => disk_total_space($storagePath),
            'used_bytes' => disk_total_space($storagePath) - disk_free_space($storagePath),
        ];
    }

    /**
     * Pobierz URL do pobrania backupu
     */
    public function getDownloadUrl(BackupJob $backup): ?string
    {
        if (!$backup->isDownloadable()) {
            return null;
        }

        // W prawdziwej implementacji można by użyć signed URLs
        return route('admin.backup.download', $backup->id);
    }

    /**
     * Weryfikuj integralność backupu
     */
    public function verifyBackup(BackupJob $backup): bool
    {
        if (!$backup->file_path) {
            return false;
        }

        $fullPath = $backup->getFullPath();
        
        if (!file_exists($fullPath)) {
            return false;
        }

        // Sprawdź czy rozmiar się zgadza
        $actualSize = filesize($fullPath);
        if ($actualSize !== $backup->size_bytes) {
            return false;
        }

        // Dla ZIP files, sprawdź czy można otworzyć
        if (str_ends_with($backup->file_path, '.zip')) {
            $zip = new ZipArchive();
            $result = $zip->open($fullPath, ZipArchive::CHECKCONS);
            $zip->close();
            
            return $result === TRUE;
        }

        return true;
    }

    /**
     * Przywróć backup (podstawowa implementacja)
     */
    public function restoreBackup(BackupJob $backup): bool
    {
        if ($backup->type !== BackupJob::TYPE_DATABASE) {
            throw new \Exception('Restore is only supported for database backups');
        }

        if (!$backup->isDownloadable()) {
            throw new \Exception('Backup file is not available');
        }

        try {
            $fullPath = $backup->getFullPath();
            
            // Rozpakuj jeśli potrzeba
            $sqlFile = $fullPath;
            if (str_ends_with($fullPath, '.gz')) {
                $sqlFile = str_replace('.gz', '', $fullPath);
                $data = gzdecode(file_get_contents($fullPath));
                file_put_contents($sqlFile, $data);
            }

            $dbConfig = config('database.connections.' . config('database.default'));
            
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                $dbConfig['host'],
                $dbConfig['port'] ?? 3306,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['database'],
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $returnCode);

            // Usuń rozpakowany plik jeśli był tworzony
            if ($sqlFile !== $fullPath) {
                unlink($sqlFile);
            }

            if ($returnCode !== 0) {
                throw new \Exception('MySQL restore failed with code: ' . $returnCode);
            }

            Log::info("Backup restored successfully: {$backup->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Backup restore failed: {$backup->name}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}