<?php

namespace App\Jobs\Features;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\FeatureTemplate;
use App\Models\FeatureType;
use App\Models\Product;
use App\Services\JobProgressService;
use App\Services\Product\FeatureManager;

/**
 * BulkAssignFeaturesJob - Bulk assign feature templates to products
 *
 * ETAP_07e FAZA 2: Background job with progress tracking
 *
 * Features:
 * - Background processing via Laravel Queue
 * - Progress tracking with JobProgressService
 * - Supports add_features / replace_features modes
 * - Scope filtering (all_vehicles / by_category)
 *
 * Usage:
 * ```php
 * $jobId = \Str::uuid()->toString();
 * BulkAssignFeaturesJob::dispatch($templateId, $scope, $categoryId, $action, $jobId);
 * ```
 *
 * @package App\Jobs\Features
 * @version 1.0
 * @since ETAP_07e_FAZA_2
 */
class BulkAssignFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Template ID to apply
     */
    protected int $templateId;

    /**
     * Scope: all_vehicles | by_category
     */
    protected string $scope;

    /**
     * Category ID (if scope = by_category)
     */
    protected ?int $categoryId;

    /**
     * Action: add_features | replace_features
     */
    protected string $action;

    /**
     * Pre-generated job ID (UUID) for progress tracking
     */
    protected ?string $jobId;

    /**
     * User who initiated the job
     */
    protected ?int $userId;

    /**
     * Number of tries for the job
     */
    public int $tries = 3;

    /**
     * Timeout for the job (10 minutes)
     */
    public int $timeout = 600;

    /**
     * Create a new job instance
     *
     * @param int $templateId Feature template ID
     * @param string $scope all_vehicles|by_category
     * @param int|null $categoryId Category ID (required if scope = by_category)
     * @param string $action add_features|replace_features
     * @param string|null $jobId Pre-generated UUID for progress tracking
     * @param int|null $userId User who initiated the job
     */
    public function __construct(
        int $templateId,
        string $scope = 'all_vehicles',
        ?int $categoryId = null,
        string $action = 'add_features',
        ?string $jobId = null,
        ?int $userId = null
    ) {
        $this->templateId = $templateId;
        $this->scope = $scope;
        $this->categoryId = $categoryId;
        $this->action = $action;
        $this->jobId = $jobId;
        $this->userId = $userId ?? auth()->id();

        Log::info('BulkAssignFeaturesJob: Created', [
            'template_id' => $templateId,
            'scope' => $scope,
            'category_id' => $categoryId,
            'action' => $action,
            'job_id' => $jobId,
            'user_id' => $this->userId,
        ]);
    }

    /**
     * Execute the job
     */
    public function handle(JobProgressService $progressService): void
    {
        $startTime = microtime(true);
        $progressId = null;

        Log::info('BulkAssignFeaturesJob: Started', [
            'template_id' => $this->templateId,
            'scope' => $this->scope,
            'category_id' => $this->categoryId,
            'action' => $this->action,
            'job_id' => $this->jobId,
        ]);

        try {
            // Load template
            $template = FeatureTemplate::find($this->templateId);

            if (!$template) {
                throw new \InvalidArgumentException("Template not found: {$this->templateId}");
            }

            // Get products matching scope
            // NOTE: Removed is_vehicle filter as column doesn't exist
            $query = Product::query();

            if ($this->scope === 'by_category' && $this->categoryId) {
                $query->where('category_id', $this->categoryId);
            }

            $productIds = $query->pluck('id')->toArray();
            $total = count($productIds);

            Log::info('BulkAssignFeaturesJob: Products found', [
                'template_id' => $this->templateId,
                'template_name' => $template->name,
                'scope' => $this->scope,
                'total_products' => $total,
            ]);

            if ($total === 0) {
                Log::warning('BulkAssignFeaturesJob: No products to process');

                if ($this->jobId) {
                    $progressId = $progressService->startPendingJob($this->jobId, 0);
                    if ($progressId) {
                        $progressService->markCompleted($progressId, [
                            'processed' => 0,
                            'skipped' => 0,
                            'message' => 'Brak produktow do przetworzenia',
                        ]);
                    }
                }

                return;
            }

            // Start progress tracking
            if ($this->jobId) {
                $progressId = $progressService->startPendingJob($this->jobId, $total);

                if (!$progressId) {
                    Log::warning('Pending progress not found, creating new', ['job_id' => $this->jobId]);
                    $progressId = $progressService->createJobProgress(
                        $this->jobId,
                        null, // No shop context
                        'bulk_assign_features',
                        $total
                    );
                }
            } else {
                $progressId = $progressService->createJobProgress(
                    $this->job->getJobId(),
                    null,
                    'bulk_assign_features',
                    $total
                );
            }

            // Store metadata
            $progressService->updateMetadata($progressId, [
                'template_id' => $this->templateId,
                'template_name' => $template->name,
                'scope' => $this->scope,
                'action' => $this->action,
                'user_id' => $this->userId,
            ]);

            // Convert template features to FeatureManager format
            $templateFeatures = $this->convertToFeatureManagerFormat($template->features);

            // Get FeatureManager service
            $featureManager = app(FeatureManager::class);

            $processed = 0;
            $errors = [];

            // Process products in batches
            foreach (array_chunk($productIds, 100) as $batchIds) {
                $products = Product::whereIn('id', $batchIds)->get();

                foreach ($products as $product) {
                    try {
                        if ($this->action === 'replace_features') {
                            $featureManager->setFeatures($product, $templateFeatures);
                        } else {
                            foreach ($templateFeatures as $featureData) {
                                $featureManager->addFeature($product, $featureData);
                            }
                        }

                        $processed++;

                    } catch (\Exception $e) {
                        $errors[] = [
                            'sku' => $product->sku ?? "ID:{$product->id}",
                            'message' => $e->getMessage(),
                        ];

                        Log::warning('BulkAssignFeaturesJob: Product error', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Update progress every 10 products
                    if ($processed % 10 === 0 && $progressId) {
                        $progressService->updateProgress($progressId, $processed, $errors);
                        $errors = []; // Reset errors after batch update
                    }
                }
            }

            // Final progress update
            if ($progressId) {
                $progressService->updateProgress($progressId, $total, $errors);
            }

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // Mark as completed
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'processed' => $processed,
                    'errors_count' => count($errors),
                    'template_name' => $template->name,
                    'action' => $this->action,
                    'execution_time_ms' => $executionTime,
                ]);
            }

            Log::info('BulkAssignFeaturesJob: Completed', [
                'template_id' => $this->templateId,
                'template_name' => $template->name,
                'total' => $total,
                'processed' => $processed,
                'errors' => count($errors),
                'execution_time_ms' => $executionTime,
            ]);

        } catch (\Exception $e) {
            if ($progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::error('BulkAssignFeaturesJob: Failed', [
                'template_id' => $this->templateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Convert template format to FeatureManager format
     */
    private function convertToFeatureManagerFormat(array $templateFeatures): array
    {
        $converted = [];

        foreach ($templateFeatures as $feature) {
            // Find or create FeatureType
            $featureType = FeatureType::firstOrCreate(
                ['code' => strtolower(str_replace(' ', '_', $feature['name']))],
                [
                    'name' => $feature['name'],
                    'value_type' => $feature['type'] ?? 'text',
                    'is_active' => true,
                ]
            );

            $converted[] = [
                'feature_type_id' => $featureType->id,
                'feature_value_id' => null,
                'custom_value' => $feature['default'] ?? null,
            ];
        }

        return $converted;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkAssignFeaturesJob: Permanent failure', [
            'template_id' => $this->templateId,
            'scope' => $this->scope,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
