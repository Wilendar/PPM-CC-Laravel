<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExportProfile;
use App\Models\ExportProfileLog;
use App\Services\Export\ProductExportService;
use App\Services\Export\FeedGeneratorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateFeedJob - Async feed file generation.
 *
 * Dispatched from:
 * - ExportProfile Livewire panel (manual regeneration)
 * - Scheduler (automatic feed refresh based on profile schedule)
 *
 * Generates a feed file, updates profile stats, and logs the result.
 * Supports retry with backoff on failure.
 *
 * @package App\Jobs
 * @since ETAP_07f - Export & Feed System
 */
class GenerateFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying on failure.
     */
    public int $backoff = 60;

    /**
     * Maximum execution time in seconds (5 minutes).
     */
    public int $timeout = 300;

    public function __construct(
        public int $profileId,
        public ?int $userId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ProductExportService $exportService,
        FeedGeneratorFactory $generatorFactory,
    ): void {
        $profile = ExportProfile::findOrFail($this->profileId);
        $startTime = microtime(true);

        try {
            $products = $exportService->getProducts($profile);
            $generator = $generatorFactory->make($profile->format);

            // Delete old file if exists
            if ($profile->file_path && file_exists($profile->file_path)) {
                unlink($profile->file_path);
            }

            $filePath = $generator->generate($products, $profile);
            $duration = (int) (microtime(true) - $startTime);

            $profile->update([
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'product_count' => count($products),
                'generation_duration' => $duration,
                'last_generated_at' => now(),
                'next_generation_at' => $profile->schedule !== 'manual'
                    ? now()->addMinutes(ExportProfile::SCHEDULE_MINUTES[$profile->schedule] ?? 1440)
                    : null,
            ]);

            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action' => 'generated',
                'user_id' => $this->userId,
                'product_count' => count($products),
                'file_size' => filesize($filePath),
                'duration' => $duration,
            ]);

            Log::info('Feed generated', [
                'profile' => $profile->name,
                'products' => count($products),
                'duration' => $duration,
            ]);
        } catch (\Throwable $e) {
            $duration = (int) (microtime(true) - $startTime);

            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action' => 'error',
                'user_id' => $this->userId,
                'error_message' => $e->getMessage(),
                'duration' => $duration,
            ]);

            Log::error('Feed generation failed', [
                'profile' => $profile->name,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw for retry
        }
    }

    /**
     * Tags for queue monitoring (Horizon).
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'feed',
            'feed-profile:' . $this->profileId,
        ];
    }
}
