<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MaintenanceTask extends Model
{
    /**
     * Tabela dla zadań maintenance
     */
    protected $table = 'maintenance_tasks';

    /**
     * Pola masowo przypisywalne
     */
    protected $fillable = [
        'name',             // nazwa zadania
        'type',             // database_optimization, cleanup, security_check, etc.
        'status',           // pending, running, completed, failed
        'scheduled_at',     // kiedy ma zostać wykonane
        'started_at',       // kiedy rozpoczęło się
        'completed_at',     // kiedy zakończyło się
        'duration_seconds', // czas trwania w sekundach
        'result_data',      // JSON z wynikami
        'error_message',    // komunikat błędu
        'configuration',    // JSON z konfiguracją
        'is_recurring',     // czy to zadanie cykliczne
        'recurrence_rule',  // reguła powtarzania (cron format)
        'next_run_at',      // następne wykonanie dla recurring tasks
        'created_by',       // kto zlecił zadanie
    ];

    /**
     * Kastowanie typów
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_run_at' => 'datetime',
        'result_data' => 'array',
        'configuration' => 'array',
        'is_recurring' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    /**
     * Stałe dla statusów
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Stałe dla typów zadań
     */
    public const TYPE_DB_OPTIMIZATION = 'database_optimization';
    public const TYPE_LOG_CLEANUP = 'log_cleanup';
    public const TYPE_CACHE_CLEANUP = 'cache_cleanup';
    public const TYPE_SECURITY_CHECK = 'security_check';
    public const TYPE_FILE_CLEANUP = 'file_cleanup';
    public const TYPE_INDEX_REBUILD = 'index_rebuild';
    public const TYPE_STATS_UPDATE = 'stats_update';

    /**
     * Relacja do użytkownika
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope - zadania oczekujące
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope - zadania aktywne
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * Scope - zadania ukończone
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope - zadania cykliczne
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope - zadania do wykonania
     */
    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
                    ->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope - według typu
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Accessor - formatowany czas trwania
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Accessor - status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_SKIPPED => 'gray',
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
            self::STATUS_RUNNING => 'Wykonuje się',
            self::STATUS_COMPLETED => 'Ukończone',
            self::STATUS_FAILED => 'Błąd',
            self::STATUS_SKIPPED => 'Pominięte',
            default => 'Nieznane'
        };
    }

    /**
     * Accessor - typ label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DB_OPTIMIZATION => 'Optymalizacja bazy danych',
            self::TYPE_LOG_CLEANUP => 'Czyszczenie logów',
            self::TYPE_CACHE_CLEANUP => 'Czyszczenie cache',
            self::TYPE_SECURITY_CHECK => 'Kontrola bezpieczeństwa',
            self::TYPE_FILE_CLEANUP => 'Czyszczenie plików',
            self::TYPE_INDEX_REBUILD => 'Odbudowa indeksów',
            self::TYPE_STATS_UPDATE => 'Aktualizacja statystyk',
            default => 'Nieznany typ'
        };
    }

    /**
     * Oznacz zadanie jako rozpoczęte
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Oznacz zadanie jako ukończone
     */
    public function markAsCompleted(array $resultData = []): void
    {
        $duration = null;
        if ($this->started_at) {
            $duration = now()->diffInSeconds($this->started_at);
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'result_data' => $resultData,
            'error_message' => null,
        ]);

        // Jeśli to zadanie cykliczne, zaplanuj następne
        if ($this->is_recurring && $this->recurrence_rule) {
            $this->scheduleNext();
        }
    }

    /**
     * Oznacz zadanie jako nieudane
     */
    public function markAsFailed(string $errorMessage): void
    {
        $duration = null;
        if ($this->started_at) {
            $duration = now()->diffInSeconds($this->started_at);
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
        ]);

        // Dla zadań cyklicznych także planujemy następne (może się uda następnym razem)
        if ($this->is_recurring && $this->recurrence_rule) {
            $this->scheduleNext();
        }
    }

    /**
     * Zaplanuj następne wykonanie dla zadania cyklicznego
     */
    private function scheduleNext(): void
    {
        if (!$this->recurrence_rule) {
            return;
        }

        // Podstawowa implementacja - można rozszerzyć o pełny cron parser
        $nextRun = match($this->recurrence_rule) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => $this->parseRecurrenceRule(),
        };

        if ($nextRun) {
            static::create([
                'name' => $this->name,
                'type' => $this->type,
                'status' => self::STATUS_PENDING,
                'scheduled_at' => $nextRun,
                'configuration' => $this->configuration,
                'is_recurring' => true,
                'recurrence_rule' => $this->recurrence_rule,
                'created_by' => $this->created_by,
            ]);

            $this->update(['next_run_at' => $nextRun]);
        }
    }

    /**
     * Parsowanie zaawansowanych reguł cron (uproszczona wersja)
     */
    private function parseRecurrenceRule(): ?Carbon
    {
        // TODO: Implementacja pełnego parsera cron
        // Na razie zwracamy null dla nieznanych reguł
        return null;
    }

    /**
     * Sprawdź czy zadanie można wykonać teraz
     */
    public function canRun(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->scheduled_at <= now();
    }

    /**
     * Pobierz domyślną konfigurację dla typu zadania
     */
    public static function getDefaultConfiguration(string $type): array
    {
        return match($type) {
            self::TYPE_DB_OPTIMIZATION => [
                'optimize_tables' => true,
                'analyze_tables' => true,
                'rebuild_indexes' => false,
                'vacuum_tables' => false,
            ],
            
            self::TYPE_LOG_CLEANUP => [
                'retention_days' => 30,
                'compress_old_logs' => true,
                'delete_empty_logs' => true,
                'max_log_size_mb' => 100,
            ],
            
            self::TYPE_CACHE_CLEANUP => [
                'clear_application_cache' => true,
                'clear_view_cache' => true,
                'clear_route_cache' => true,
                'clear_config_cache' => false,
            ],
            
            self::TYPE_SECURITY_CHECK => [
                'check_file_permissions' => true,
                'check_config_security' => true,
                'check_dependencies' => true,
                'check_ssl_certificates' => false,
            ],
            
            self::TYPE_FILE_CLEANUP => [
                'cleanup_temp_files' => true,
                'cleanup_old_uploads' => false,
                'max_file_age_days' => 90,
                'min_free_space_gb' => 1,
            ],
            
            default => []
        };
    }

    /**
     * Lista dostępnych typów zadań
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_DB_OPTIMIZATION => 'Optymalizacja bazy danych',
            self::TYPE_LOG_CLEANUP => 'Czyszczenie logów',
            self::TYPE_CACHE_CLEANUP => 'Czyszczenie cache',
            self::TYPE_SECURITY_CHECK => 'Kontrola bezpieczeństwa',
            self::TYPE_FILE_CLEANUP => 'Czyszczenie plików',
            self::TYPE_INDEX_REBUILD => 'Odbudowa indeksów',
            self::TYPE_STATS_UPDATE => 'Aktualizacja statystyk',
        ];
    }
}