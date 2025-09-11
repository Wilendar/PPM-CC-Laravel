<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Using custom requested_at timestamp

    protected $fillable = [
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'user_id',
        'api_key_id',
        'response_code',
        'response_time_ms',
        'response_size_bytes',
        'rate_limit_remaining',
        'rate_limited',
        'request_params',
        'response_headers',
        'error_message',
        'suspicious',
        'security_notes',
        'requested_at',
    ];

    protected $casts = [
        'rate_limited' => 'boolean',
        'request_params' => 'array',
        'response_headers' => 'array',
        'suspicious' => 'boolean',
        'requested_at' => 'datetime',
    ];

    /**
     * Get the user who made the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_code', [200, 299]);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * Scope for rate limited requests
     */
    public function scopeRateLimited($query)
    {
        return $query->where('rate_limited', true);
    }

    /**
     * Scope for suspicious requests
     */
    public function scopeSuspicious($query)
    {
        return $query->where('suspicious', true);
    }

    /**
     * Scope for slow requests
     */
    public function scopeSlow($query, int $threshold = 5000)
    {
        return $query->where('response_time_ms', '>', $threshold);
    }

    /**
     * Scope by endpoint
     */
    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('requested_at', [$from, $to]);
    }

    /**
     * Scope for today's requests
     */
    public function scopeToday($query)
    {
        return $query->whereDate('requested_at', today());
    }

    /**
     * Get response status color class
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->response_code >= 200 && $this->response_code < 300) {
            return 'text-green-600';
        } elseif ($this->response_code >= 300 && $this->response_code < 400) {
            return 'text-yellow-600';
        } elseif ($this->response_code >= 400 && $this->response_code < 500) {
            return 'text-orange-600';
        } else {
            return 'text-red-600';
        }
    }

    /**
     * Get performance rating
     */
    public function getPerformanceRatingAttribute(): string
    {
        if ($this->response_time_ms < 500) {
            return 'excellent';
        } elseif ($this->response_time_ms < 1000) {
            return 'good';
        } elseif ($this->response_time_ms < 3000) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get performance color class
     */
    public function getPerformanceColorAttribute(): string
    {
        return match ($this->performance_rating) {
            'excellent' => 'text-green-600',
            'good' => 'text-blue-600',
            'fair' => 'text-yellow-600',
            'poor' => 'text-red-600',
        };
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response_code >= 200 && $this->response_code < 300;
    }

    /**
     * Check if request was an error
     */
    public function isError(): bool
    {
        return $this->response_code >= 400;
    }

    /**
     * Check if request was slow
     */
    public function isSlow(int $threshold = 5000): bool
    {
        return $this->response_time_ms > $threshold;
    }
}