<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PendingProduct;
use App\Services\Import\ProductPublicationService;
use App\Services\Import\PublicationTargetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * PublishScheduledProducts - FAZA 9.5
 *
 * Artisan command to auto-publish scheduled pending products.
 * Runs every minute via Laravel Scheduler.
 *
 * Workflow:
 * 1. Find PendingProducts: publish_status='scheduled' AND scheduled_publish_at <= now()
 * 2. Validate each product is ready (completion 100%)
 * 3. Change status: scheduled -> publishing
 * 4. Call ProductPublicationService::publishSingle()
 * 5. Dispatch sync jobs based on publication_targets
 * 6. Update status: publishing -> published (or failed)
 *
 * @package App\Console\Commands
 */
class PublishScheduledProducts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:publish-scheduled
                            {--dry-run : Only show what would be published, without executing}
                            {--limit=50 : Maximum products to publish per run}';

    /**
     * The console command description.
     */
    protected $description = 'Publish scheduled pending products whose publish date has been reached';

    /**
     * Execute the console command.
     */
    public function handle(
        ProductPublicationService $publicationService,
        PublicationTargetService $targetService
    ): int {
        $limit = (int) $this->option('limit') ?: config('import.scheduler.batch_limit', 50);
        $isDryRun = (bool) $this->option('dry-run');

        // Find products ready for scheduled publish
        $products = PendingProduct::where('publish_status', 'scheduled')
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->whereNull('published_at')
            ->where('completion_percentage', 100)
            ->orderBy('scheduled_publish_at', 'asc')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->components->info('No scheduled products ready for publication.');
            return self::SUCCESS;
        }

        $this->components->info("Found {$products->count()} scheduled product(s) ready for publication.");

        if ($isDryRun) {
            $this->showDryRunTable($products);
            return self::SUCCESS;
        }

        $published = 0;
        $failed = 0;

        foreach ($products as $product) {
            $this->components->task("Publishing: {$product->sku}", function () use (
                $product,
                $publicationService,
                $targetService,
                &$published,
                &$failed
            ) {
                try {
                    // Mark as publishing
                    $product->publish_status = 'publishing';
                    $product->save();

                    // Publish via service
                    $result = $publicationService->publishSingle($product, false);

                    if ($result['success']) {
                        $product->publish_status = 'published';
                        $product->save();

                        // Dispatch target-specific sync jobs
                        $resolvedTargets = $targetService->resolveTargets(
                            $product->publication_targets
                        );
                        $targetService->dispatchSyncJobs($result['product'], $resolvedTargets);

                        $published++;

                        Log::info('PublishScheduledProducts: Published', [
                            'pending_id' => $product->id,
                            'product_id' => $result['product']->id,
                            'sku' => $product->sku,
                        ]);

                        return true;
                    }

                    // Publication failed
                    $product->publish_status = 'failed';
                    $product->save();
                    $failed++;

                    Log::warning('PublishScheduledProducts: Failed', [
                        'pending_id' => $product->id,
                        'sku' => $product->sku,
                        'errors' => $result['errors'],
                    ]);

                    return false;

                } catch (\Exception $e) {
                    $product->publish_status = 'failed';
                    $product->save();
                    $failed++;

                    Log::error('PublishScheduledProducts: Exception', [
                        'pending_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);

                    return false;
                }
            });
        }

        $this->newLine();
        $this->components->info("Results: {$published} published, {$failed} failed.");

        Log::info('PublishScheduledProducts: Batch complete', [
            'total' => $products->count(),
            'published' => $published,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Show dry-run table without executing
     */
    protected function showDryRunTable($products): void
    {
        $rows = $products->map(fn(PendingProduct $p) => [
            $p->id,
            $p->sku,
            $p->name ? mb_substr($p->name, 0, 30) : '-',
            $p->scheduled_publish_at?->format('Y-m-d H:i'),
            $p->completion_percentage . '%',
            json_encode($p->publication_targets ?? []),
        ])->toArray();

        $this->table(
            ['ID', 'SKU', 'Name', 'Scheduled At', 'Completion', 'Targets'],
            $rows
        );

        $this->components->warn("DRY RUN: No products were published.");
    }
}
