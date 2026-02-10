<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'period',
        'report_date',
        'data',
        'metadata',
        'summary',
        'status',
        'generated_at',
        'generated_by',
        'generation_time_seconds',
        'data_points_count',
    ];

    protected $casts = [
        'report_date' => 'date',
        'data' => 'array',
        'metadata' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Type constants
     */
    public const TYPE_USAGE_ANALYTICS = 'usage_analytics';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_BUSINESS_INTELLIGENCE = 'business_intelligence';
    public const TYPE_INTEGRATION_PERFORMANCE = 'integration_performance';
    public const TYPE_SECURITY_AUDIT = 'security_audit';

    /**
     * Period constants
     */
    public const PERIOD_DAILY = 'daily';
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';

    /**
     * Status constants
     */
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the user who generated this report
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope for completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by period
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Get report type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_USAGE_ANALYTICS => 'Analytics użycia',
            self::TYPE_PERFORMANCE => 'Performance',
            self::TYPE_BUSINESS_INTELLIGENCE => 'Business Intelligence',
            self::TYPE_INTEGRATION_PERFORMANCE => 'Performance integracji',
            self::TYPE_SECURITY_AUDIT => 'Audyt bezpieczeństwa',
        };
    }

    /**
     * Get period label
     */
    public function getPeriodLabel(): string
    {
        return match ($this->period) {
            self::PERIOD_DAILY => 'Dzienny',
            self::PERIOD_WEEKLY => 'Tygodniowy',
            self::PERIOD_MONTHLY => 'Miesięczny',
            self::PERIOD_QUARTERLY => 'Kwartalny',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'text-green-400',
            self::STATUS_GENERATING => 'text-blue-400',
            self::STATUS_FAILED => 'text-red-400',
        };
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'fas fa-check-circle',
            self::STATUS_GENERATING => 'fas fa-spinner fa-spin',
            self::STATUS_FAILED => 'fas fa-exclamation-triangle',
        };
    }

    /**
     * Mark report as completed
     */
    public function markCompleted(array $data, ?string $summary = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'data' => $data,
            'summary' => $summary,
            'generated_at' => now(),
            'generation_time_seconds' => $this->created_at->diffInSeconds(now()),
            'data_points_count' => count($data),
        ]);
    }

    /**
     * Mark report as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => array_merge($this->metadata ?? [], [
                'error' => $errorMessage,
                'failed_at' => now(),
            ]),
        ]);
    }

    /**
     * Get data value by key
     */
    public function getDataValue(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Check if report has data
     */
    public function hasData(): bool
    {
        return !empty($this->data) && $this->status === self::STATUS_COMPLETED;
    }
}