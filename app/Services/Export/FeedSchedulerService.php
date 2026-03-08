<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ExportProfile;
use App\Jobs\GenerateFeedJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * FeedSchedulerService - Scheduled feed generation orchestrator.
 *
 * Determines which ExportProfiles are due for automatic generation
 * and dispatches GenerateFeedJob for each.
 *
 * Logic:
 * - Profile is due when: is_active AND schedule != 'manual' AND
 *   (next_generation_at <= now() OR (next_generation_at is null AND last_generated_at is null))
 * - After dispatch, next_generation_at is set to prevent re-processing
 *
 * @package App\Services\Export
 * @since ETAP_07f - Export & Feed System
 */
class FeedSchedulerService
{
    /**
     * Get profiles that are due for generation.
     *
     * A profile is due when:
     * 1. It is active (is_active = true)
     * 2. It has a non-manual schedule
     * 3. Either:
     *    a) next_generation_at has passed (<= now()), OR
     *    b) Both next_generation_at and last_generated_at are null (never generated)
     */
    public function getProfilesDueForGeneration(): Collection
    {
        return ExportProfile::active()
            ->where('schedule', '!=', 'manual')
            ->where(function ($query) {
                $query->where('next_generation_at', '<=', now())
                      ->orWhere(function ($q) {
                          $q->whereNull('next_generation_at')
                            ->whereNull('last_generated_at');
                      });
            })
            ->get();
    }

    /**
     * Dispatch feed generation job for a given profile.
     */
    public function generateFeed(ExportProfile $profile): void
    {
        GenerateFeedJob::dispatch($profile->id);
    }

    /**
     * Calculate next generation time based on profile schedule.
     *
     * Returns null for manual schedules (no auto-generation).
     */
    public function calculateNextGenerationTime(ExportProfile $profile): ?Carbon
    {
        $minutes = ExportProfile::SCHEDULE_MINUTES[$profile->schedule] ?? null;

        if ($minutes === null) {
            return null;
        }

        return now()->addMinutes($minutes);
    }

    /**
     * Process all due profiles: dispatch jobs and set next_generation_at.
     *
     * @return int Count of dispatched jobs
     */
    public function processScheduledFeeds(): int
    {
        $profiles = $this->getProfilesDueForGeneration();
        $count = 0;

        foreach ($profiles as $profile) {
            $this->generateFeed($profile);

            // Set next_generation_at to prevent re-processing before job completes
            $profile->update([
                'next_generation_at' => $this->calculateNextGenerationTime($profile),
            ]);

            Log::info('FeedSchedulerService: Feed generation dispatched', [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'schedule' => $profile->schedule,
                'next_generation_at' => $profile->next_generation_at?->toDateTimeString(),
            ]);

            $count++;
        }

        return $count;
    }
}
