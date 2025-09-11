<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BackupJob extends Model
{
    /**
     * Tabela dla zadań backupu
     */
    protected $table = 'backup_jobs';

    /**
     * Pola masowo przypisywalne
     */
    protected $fillable = [
        'name',             // nazwa backupu
        'type',             // database, files, full
        'status',           // pending, running, completed, failed
        'size_bytes',       // rozmiar backupu w bajtach
        'file_path',        // ścieżka do pliku backupu
        'started_at',       // kiedy rozpoczął się backup
        'completed_at',     // kiedy zakończył się backup
        'error_message',    // wiadomość błędu jeśli failed
        'configuration',    // JSON z konfiguracją backupu
        'created_by',       // kto zlecił backup
    ];

    /**
     * Kastowanie typów
     */
    protected $casts = [
        'configuration' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    /**
     * Stałe dla statusów
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Stałe dla typów backupu
     */
    public const TYPE_DATABASE = 'database';
    public const TYPE_FILES = 'files';
    public const TYPE_FULL = 'full';

    /**
     * Relacja do użytkownika który zlecił
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope - aktywne backupy
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * Scope - ukończone backupy
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope - nieudane backupy
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope - według typu
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Accessor - czas trwania backupu
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Accessor - formatowany rozmiar pliku
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size_bytes) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($this->size_bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Accessor - status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray'
        };
    }

    /**
     * Accessor - status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_RUNNING => 'Trwa',
            self::STATUS_COMPLETED => 'Ukończony',
            self::STATUS_FAILED => 'Nieudany',
            default => 'Nieznany'
        };
    }

    /**
     * Accessor - typ backupu label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DATABASE => 'Baza danych',
            self::TYPE_FILES => 'Pliki',
            self::TYPE_FULL => 'Pełny',
            default => 'Nieznany'
        };
    }

    /**
     * Sprawdź czy backup można pobrać
     */
    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_COMPLETED && 
               $this->file_path && 
               file_exists(storage_path($this->file_path));
    }

    /**
     * Sprawdź czy backup można usunąć
     */
    public function isDeletable(): bool
    {
        return !in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * Oznacz backup jako rozpoczęty
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Oznacz backup jako ukończony
     */
    public function markAsCompleted(string $filePath, int $sizeBytes): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'file_path' => $filePath,
            'size_bytes' => $sizeBytes,
            'error_message' => null,
        ]);
    }

    /**
     * Oznacz backup jako nieudany
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Pobierz ścieżkę do pliku backupu
     */
    public function getFullPath(): ?string
    {
        return $this->file_path ? storage_path($this->file_path) : null;
    }

    /**
     * Usuń plik backupu z dysku
     */
    public function deleteBackupFile(): bool
    {
        if (!$this->file_path) {
            return true;
        }

        $fullPath = $this->getFullPath();
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true;
    }

    /**
     * Domyślna konfiguracja backupu
     */
    public static function getDefaultConfiguration(string $type): array
    {
        $base = [
            'compress' => true,
            'encrypt' => false,
            'retention_days' => 30,
        ];

        return match($type) {
            self::TYPE_DATABASE => array_merge($base, [
                'include_structure' => true,
                'include_data' => true,
                'exclude_tables' => ['sessions', 'cache', 'job_batches'],
            ]),
            
            self::TYPE_FILES => array_merge($base, [
                'include_uploads' => true,
                'include_logs' => false,
                'include_cache' => false,
                'max_file_size_mb' => 100,
            ]),
            
            self::TYPE_FULL => array_merge($base, [
                'include_database' => true,
                'include_files' => true,
                'include_config' => true,
            ]),
            
            default => $base
        };
    }
}