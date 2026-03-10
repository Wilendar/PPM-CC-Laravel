<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * WorkerHeartbeat Model
 *
 * Tracks active queue workers processing import/sync jobs.
 * Used by WorkerGuardService to prevent duplicate workers
 * and detect dead workers via heartbeat timeout.
 *
 * @property int $id
 * @property int|null $job_progress_id
 * @property string $job_id
 * @property int $worker_pid
 * @property string $worker_type
 * @property string|null $queue_name
 * @property string $status
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $last_heartbeat_at
 * @property \Carbon\Carbon|null $finished_at
 * @property array|null $metadata
 */
class WorkerHeartbeat extends Model
{
    protected $table = 'worker_heartbeats';

    protected $fillable = [
        'job_progress_id',
        'job_id',
        'worker_pid',
        'worker_type',
        'queue_name',
        'status',
        'started_at',
        'last_heartbeat_at',
        'finished_at',
        'metadata',
    ];

    protected $casts = [
        'job_progress_id' => 'integer',
        'worker_pid' => 'integer',
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    // --- Relationships ---

    public function jobProgress(): BelongsTo
    {
        return $this->belongsTo(JobProgress::class, 'job_progress_id');
    }

    // --- Scopes ---

    /**
     * Workers actively processing with recent heartbeat (< threshold seconds)
     */
    public function scopeAlive(Builder $query, int $thresholdSeconds = 120): Builder
    {
        return $query->where('status', 'processing')
            ->where('last_heartbeat_at', '>', now()->subSeconds($thresholdSeconds));
    }

    /**
     * Workers marked as processing but heartbeat expired
     */
    public function scopeDead(Builder $query, int $thresholdSeconds = 120): Builder
    {
        return $query->where('status', 'processing')
            ->where('last_heartbeat_at', '<', now()->subSeconds($thresholdSeconds));
    }

    /**
     * Filter by job_id
     */
    public function scopeForJob(Builder $query, string $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    // --- Helpers ---

    /**
     * Check if this worker is still alive (heartbeat within threshold)
     */
    public function isAlive(int $thresholdSeconds = 120): bool
    {
        return $this->status === 'processing'
            && $this->last_heartbeat_at
            && $this->last_heartbeat_at->gt(now()->subSeconds($thresholdSeconds));
    }

    /**
     * Send heartbeat - update last_heartbeat_at timestamp
     */
    public function sendHeartbeat(): void
    {
        $this->update(['last_heartbeat_at' => now()]);
    }

    /**
     * Mark worker as finished (idle)
     */
    public function markFinished(): void
    {
        $this->update([
            'status' => 'idle',
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark worker as dead
     */
    public function markDead(): void
    {
        $this->update([
            'status' => 'dead',
            'finished_at' => now(),
        ]);
    }
}
