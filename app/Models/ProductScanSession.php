<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * ProductScanSession Model
 *
 * ETAP_10: Product Scan System - Scan Sessions
 *
 * Model przechowuje informacje o sesji skanowania produktow.
 * Obsluguje trzy typy skanow: powiazania, brakujace w PPM, brakujace w zrodle.
 * Wspiera wiele zrodel: Subiekt GT, Baselinker, Dynamics, PrestaShop.
 *
 * Features:
 * - Typy skanow: links, missing_in_ppm, missing_in_source
 * - Zrodla: subiekt_gt, baselinker, dynamics, prestashop
 * - Progress tracking z real-time updates
 * - Statystyki: total_scanned, matched, unmatched, errors
 * - Historia skanow z result_summary
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_10 - Product Scan System
 */
class ProductScanSession extends Model
{
    use HasFactory;

    protected $table = 'product_scan_sessions';

    // ==========================================
    // SCAN TYPE CONSTANTS
    // ==========================================

    /** Skan powiazania produktow PPM bez linkow do zrodla */
    public const SCAN_LINKS = 'links';

    /** Skan produktow w zrodle, ktore nie istnieja w PPM */
    public const SCAN_MISSING_PPM = 'missing_in_ppm';

    /** Skan produktow PPM, ktore nie istnieja w zrodle */
    public const SCAN_MISSING_SOURCE = 'missing_in_source';

    // ==========================================
    // SOURCE TYPE CONSTANTS
    // ==========================================

    /** Subiekt GT ERP system */
    public const SOURCE_SUBIEKT = 'subiekt_gt';

    /** Baselinker ERP system */
    public const SOURCE_BASELINKER = 'baselinker';

    /** Microsoft Dynamics ERP system */
    public const SOURCE_DYNAMICS = 'dynamics';

    /** PrestaShop e-commerce platform */
    public const SOURCE_PRESTASHOP = 'prestashop';

    // ==========================================
    // STATUS CONSTANTS
    // ==========================================

    /** Oczekuje na uruchomienie */
    public const STATUS_PENDING = 'pending';

    /** W trakcie skanowania */
    public const STATUS_RUNNING = 'running';

    /** Zakonczony pomyslnie */
    public const STATUS_COMPLETED = 'completed';

    /** Zakonczony z bledem */
    public const STATUS_FAILED = 'failed';

    /** Anulowany przez uzytkownika */
    public const STATUS_CANCELLED = 'cancelled';

    // ==========================================
    // FILLABLE & CASTS
    // ==========================================

    protected $fillable = [
        // Session identification
        'scan_type',
        'source_type',
        'source_id',

        // Status tracking
        'status',
        'started_at',
        'completed_at',

        // Statistics
        'total_scanned',
        'matched_count',
        'unmatched_count',
        'errors_count',

        // Results and errors
        'result_summary',
        'error_message',

        // Related entities
        'sync_job_id',
        'user_id',
    ];

    protected $casts = [
        // JSON fields
        'result_summary' => 'array',

        // Datetime fields
        'started_at' => 'datetime',
        'completed_at' => 'datetime',

        // Integer fields
        'total_scanned' => 'integer',
        'matched_count' => 'integer',
        'unmatched_count' => 'integer',
        'errors_count' => 'integer',
        'source_id' => 'integer',
        'sync_job_id' => 'integer',
        'user_id' => 'integer',
    ];

    // ==========================================
    // STATIC METHODS - Available Options
    // ==========================================

    /**
     * Get available scan types for UI
     *
     * @return array<string, string>
     */
    public static function getAvailableScanTypes(): array
    {
        return [
            self::SCAN_LINKS => 'Skan powiazania',
            self::SCAN_MISSING_PPM => 'Brakujace w PPM',
            self::SCAN_MISSING_SOURCE => 'Brakujace w zrodle',
        ];
    }

    /**
     * Get available source types for UI
     * ETAP_10: Uses ERPConnection::getErpTypeLabel() for centralized ERP names
     *
     * @return array<string, string>
     */
    public static function getAvailableSourceTypes(): array
    {
        return [
            self::SOURCE_SUBIEKT => ERPConnection::getErpTypeLabel(ERPConnection::ERP_SUBIEKT_GT),
            self::SOURCE_BASELINKER => ERPConnection::getErpTypeLabel(ERPConnection::ERP_BASELINKER),
            self::SOURCE_DYNAMICS => ERPConnection::getErpTypeLabel(ERPConnection::ERP_DYNAMICS),
            self::SOURCE_PRESTASHOP => 'PrestaShop',
        ];
    }

    /**
     * Get available statuses for UI
     *
     * @return array<string, string>
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_RUNNING => 'W trakcie',
            self::STATUS_COMPLETED => 'Zakonczony',
            self::STATUS_FAILED => 'Blad',
            self::STATUS_CANCELLED => 'Anulowany',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get scan results for this session
     */
    public function results(): HasMany
    {
        return $this->hasMany(ProductScanResult::class, 'scan_session_id');
    }

    /**
     * Get the user who started this scan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get ERP connection if source is ERP type
     */
    public function erpConnection(): BelongsTo
    {
        return $this->belongsTo(ERPConnection::class, 'source_id');
    }

    /**
     * Get PrestaShop shop if source is prestashop
     */
    public function prestashopShop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'source_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to get recent sessions (last 30 days)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope to filter by scan type
     */
    public function scopeByType($query, string $scanType)
    {
        return $query->where('scan_type', $scanType);
    }

    /**
     * Scope to filter by source type
     */
    public function scopeBySource($query, string $sourceType, ?int $sourceId = null)
    {
        $query->where('source_type', $sourceType);

        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }

        return $query;
    }

    /**
     * Scope to get running sessions
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope to get completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed sessions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get pending sessions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ==========================================
    // STATUS HELPERS
    // ==========================================

    /**
     * Check if scan is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if scan is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if scan has failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if scan is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if scan was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if scan is active (pending or running)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    // ==========================================
    // PROGRESS & DURATION HELPERS
    // ==========================================

    /**
     * Get scan duration in seconds
     *
     * @return int|null Duration in seconds or null if not completed
     */
    public function getDuration(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? Carbon::now();

        return $endTime->diffInSeconds($this->started_at);
    }

    /**
     * Get scan duration in human readable format
     */
    public function getDurationForHumans(): string
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return 'Nie rozpoczety';
        }

        if ($duration < 60) {
            return "{$duration} sek.";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return "{$minutes} min. {$seconds} sek.";
    }

    /**
     * Get progress percentage
     *
     * @return float Progress 0-100
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_scanned === 0) {
            return $this->isCompleted() ? 100.0 : 0.0;
        }

        $processed = $this->matched_count + $this->unmatched_count + $this->errors_count;

        return round(($processed / $this->total_scanned) * 100, 2);
    }

    // ==========================================
    // STATUS MANAGEMENT METHODS
    // ==========================================

    /**
     * Mark session as running
     */
    public function markAsRunning(): self
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Mark session as completed
     *
     * @param array|null $resultSummary Optional summary data
     */
    public function markAsCompleted(?array $resultSummary = null): self
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
        ];

        if ($resultSummary !== null) {
            $updateData['result_summary'] = $resultSummary;
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Mark session as failed
     *
     * @param string $errorMessage Error description
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => Carbon::now(),
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Mark session as cancelled
     */
    public function markAsCancelled(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Update statistics counters
     *
     * @param int $totalScanned Total products to scan
     * @param int $matched Matched products count
     * @param int $unmatched Unmatched products count
     * @param int $errors Errors count
     */
    public function updateStatistics(
        int $totalScanned,
        int $matched,
        int $unmatched,
        int $errors
    ): self {
        $this->update([
            'total_scanned' => $totalScanned,
            'matched_count' => $matched,
            'unmatched_count' => $unmatched,
            'errors_count' => $errors,
        ]);

        return $this;
    }

    /**
     * Increment matched count
     */
    public function incrementMatched(): self
    {
        $this->increment('matched_count');

        return $this;
    }

    /**
     * Increment unmatched count
     */
    public function incrementUnmatched(): self
    {
        $this->increment('unmatched_count');

        return $this;
    }

    /**
     * Increment errors count
     */
    public function incrementErrors(): self
    {
        $this->increment('errors_count');

        return $this;
    }

    // ==========================================
    // DISPLAY HELPERS
    // ==========================================

    /**
     * Get scan type label for UI
     */
    public function getScanTypeLabel(): string
    {
        return self::getAvailableScanTypes()[$this->scan_type] ?? $this->scan_type;
    }

    /**
     * Get source type label for UI
     */
    public function getSourceTypeLabel(): string
    {
        return self::getAvailableSourceTypes()[$this->source_type] ?? $this->source_type;
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabel(): string
    {
        return self::getAvailableStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get status with icon for UI
     */
    public function getStatusWithIcon(): string
    {
        $icons = [
            self::STATUS_PENDING => '⏳',
            self::STATUS_RUNNING => '🔄',
            self::STATUS_COMPLETED => '✓',
            self::STATUS_FAILED => '✗',
            self::STATUS_CANCELLED => '⏹',
        ];

        $icon = $icons[$this->status] ?? '?';

        return $icon . ' ' . $this->getStatusLabel();
    }

    /**
     * Get status badge CSS class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_RUNNING => 'badge-info',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-secondary',
        };
    }
}
