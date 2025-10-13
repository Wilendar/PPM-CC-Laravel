<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportJob Model
 *
 * ETAP_04 Panel Administracyjny - Sekcja 2.2.2.2: Import Management
 *
 * Model reprezentujący zadanie importu danych z PrestaShop stores.
 * Obsługuje import scheduling, validation, rollback i real-time monitoring.
 *
 * @property string $job_id UUID zadania
 * @property string $job_type Typ zadania (prestashop_import, rollback)
 * @property string $job_name Nazwa zadania
 * @property string $source_type Źródło danych (prestashop)
 * @property string $target_type Cel danych (ppm)
 * @property int $source_id ID sklepu PrestaShop
 * @property string $trigger_type Sposób wywołania (manual, scheduled)
 * @property int|null $user_id ID użytkownika który uruchomił import
 * @property \Illuminate\Support\Carbon|null $scheduled_at Data zaplanowania
 * @property \Illuminate\Support\Carbon|null $started_at Data rozpoczęcia
 * @property \Illuminate\Support\Carbon|null $completed_at Data zakończenia
 * @property array $job_config Konfiguracja importu (JSON)
 * @property array|null $rollback_data Dane dla rollback (JSON)
 * @property string $status Status (pending, running, validating, validation_required, completed, failed, cancelled, scheduled)
 * @property int|null $progress Postęp w procentach (0-100)
 * @property string|null $error_message Komunikat błędu
 * @property int|null $records_total Całkowita liczba rekordów
 * @property int|null $records_processed Liczba przetworzonych rekordów
 * @property int|null $records_failed Liczba błędnych rekordów
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\PrestaShopShop $prestashopShop
 * @property-read \App\Models\User|null $user
 */
class ImportJob extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'import_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_id',
        'job_type',
        'job_name',
        'source_type',
        'target_type',
        'source_id',
        'trigger_type',
        'user_id',
        'scheduled_at',
        'started_at',
        'completed_at',
        'job_config',
        'rollback_data',
        'status',
        'progress',
        'error_message',
        'records_total',
        'records_processed',
        'records_failed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'job_config' => 'array',
        'rollback_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'records_total' => 'integer',
        'records_processed' => 'integer',
        'records_failed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid job statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_VALIDATING = 'validating';
    const STATUS_VALIDATION_REQUIRED = 'validation_required';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SCHEDULED = 'scheduled';

    /**
     * Valid job types
     */
    const TYPE_PRESTASHOP_IMPORT = 'prestashop_import';
    const TYPE_ROLLBACK = 'rollback';

    /**
     * Relationship: PrestaShop Shop (source)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prestashopShop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'source_id');
    }

    /**
     * Relationship: User who triggered the import
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Active imports (pending, running, validating)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_VALIDATING,
            self::STATUS_VALIDATION_REQUIRED,
        ]);
    }

    /**
     * Scope: Completed imports
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Failed imports
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Scheduled imports
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope: Rollback-able imports (completed within rollback window with rollback data)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days for rollback window
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRollbackable($query, $days = 7)
    {
        return $query->where('status', self::STATUS_COMPLETED)
                     ->where('created_at', '>=', now()->subDays($days))
                     ->whereNotNull('rollback_data');
    }

    /**
     * Check if import is in progress
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_VALIDATING,
        ]);
    }

    /**
     * Check if import is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if import failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if import can be rolled back
     *
     * @param int $maxDays Maximum days for rollback
     * @return bool
     */
    public function canRollback($maxDays = 7): bool
    {
        return $this->isCompleted()
            && $this->rollback_data !== null
            && $this->created_at->gte(now()->subDays($maxDays));
    }

    /**
     * Mark import as started
     *
     * @return void
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    /**
     * Mark import as failed
     *
     * @param string $errorMessage
     * @return void
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
     * Update import progress
     *
     * @param int $progress Progress percentage (0-100)
     * @return void
     */
    public function updateProgress(int $progress): void
    {
        $this->update([
            'progress' => max(0, min(100, $progress)),
        ]);
    }
}
