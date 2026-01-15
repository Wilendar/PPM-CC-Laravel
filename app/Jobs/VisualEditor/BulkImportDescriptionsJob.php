<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\VisualEditor\BlockRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Bulk Import Descriptions Job.
 *
 * Imports visual descriptions from a JSON file.
 * Matches products by SKU and creates/updates ProductDescription records.
 *
 * Features:
 * - Chunked processing (50 descriptions per batch)
 * - Progress tracking via JobProgressService
 * - SKU-based product matching
 * - Block structure validation
 * - HTML re-rendering after import
 *
 * @package App\Jobs\VisualEditor
 * @since ETAP_07f Faza 6.2 - Bulk Operations
 */
class BulkImportDescriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of descriptions per processing batch.
     */
    private const CHUNK_SIZE = 50;

    /**
     * Number of times job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param string $filePath Path to JSON file on local disk
     * @param int $shopId Target shop for import
     * @param int $userId User who initiated the import
     * @param int|null $progressId Pre-created progress ID (optional)
     * @param array $options Import options (overwrite, skip_existing, etc.)
     */
    public function __construct(
        public string $filePath,
        public int $shopId,
        public int $userId,
        public ?int $progressId = null,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        JobProgressService $progressService,
        BlockRenderer $blockRenderer
    ): void {
        Log::info('BulkImportDescriptionsJob started', [
            'file_path' => $this->filePath,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'progress_id' => $this->progressId,
            'options' => $this->options,
        ]);

        // Default options
        $overwriteExisting = $this->options['overwrite'] ?? true;
        $skipExisting = $this->options['skip_existing'] ?? false;

        try {
            // Validate and load file
            $importData = $this->loadAndValidateFile();
            $descriptions = $importData['descriptions'] ?? [];
            $totalDescriptions = count($descriptions);

            if ($totalDescriptions === 0) {
                throw new \RuntimeException('Plik eksportu nie zawiera zadnych opisow');
            }

            // Create progress tracking if not pre-created
            $progressId = $this->progressId;
            $shop = PrestaShopShop::find($this->shopId);

            if (!$shop) {
                throw new \RuntimeException("Sklep o ID {$this->shopId} nie istnieje");
            }

            if (!$progressId) {
                $progressId = $progressService->createJobProgress(
                    $this->job->getJobId(),
                    $shop,
                    'description_import',
                    $totalDescriptions
                );
            } else {
                $progressService->startPendingJob($this->job->getJobId(), $totalDescriptions);
            }

            // Update metadata with import info
            $progressService->updateMetadata($progressId, [
                'source_shop' => $importData['export_info']['shop_name'] ?? 'Unknown',
                'exported_at' => $importData['export_info']['exported_at'] ?? null,
                'total_in_file' => $totalDescriptions,
            ]);

            $processed = 0;
            $errors = [];
            $importedCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;

            // Pre-fetch all SKUs for matching
            $skus = array_column($descriptions, 'sku');
            $productsBySku = Product::whereIn('sku', $skus)
                ->pluck('id', 'sku')
                ->toArray();

            // Process in chunks
            $chunks = array_chunk($descriptions, self::CHUNK_SIZE);

            foreach ($chunks as $chunkIndex => $chunkDescriptions) {
                foreach ($chunkDescriptions as $descData) {
                    try {
                        $result = $this->importDescription(
                            $descData,
                            $productsBySku,
                            $shop,
                            $blockRenderer,
                            $overwriteExisting,
                            $skipExisting
                        );

                        switch ($result) {
                            case 'imported':
                                $importedCount++;
                                break;
                            case 'updated':
                                $updatedCount++;
                                break;
                            case 'skipped':
                                $skippedCount++;
                                break;
                        }
                    } catch (Throwable $e) {
                        $errors[] = [
                            'sku' => $descData['sku'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];

                        Log::warning('BulkImportDescriptionsJob: blad importu opisu', [
                            'sku' => $descData['sku'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $processed++;
                }

                // Update progress after each chunk
                if ($progressId) {
                    $progressService->updateProgress($progressId, $processed, $errors);
                }

                Log::debug('BulkImportDescriptionsJob: chunk processed', [
                    'chunk_index' => $chunkIndex,
                    'processed' => $processed,
                    'total' => $totalDescriptions,
                    'imported' => $importedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                ]);
            }

            // Mark as completed
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'total_processed' => $processed,
                    'imported_count' => $importedCount,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'error_count' => count($errors),
                    'shop_name' => $shop->name,
                ]);
            }

            Log::info('BulkImportDescriptionsJob completed', [
                'shop_id' => $this->shopId,
                'processed' => $processed,
                'imported' => $importedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'errors' => count($errors),
            ]);

        } catch (Throwable $e) {
            if (isset($progressId) && $progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::error('BulkImportDescriptionsJob failed', [
                'file_path' => $this->filePath,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Load and validate import file.
     */
    private function loadAndValidateFile(): array
    {
        if (!Storage::disk('local')->exists($this->filePath)) {
            throw new \RuntimeException("Plik importu nie istnieje: {$this->filePath}");
        }

        $content = Storage::disk('local')->get($this->filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Nieprawidlowy format JSON: ' . json_last_error_msg());
        }

        // Validate structure
        if (!isset($data['descriptions']) || !is_array($data['descriptions'])) {
            throw new \RuntimeException('Brak tablicy "descriptions" w pliku importu');
        }

        // Check version compatibility
        $version = $data['export_info']['version'] ?? '1.0';
        if (version_compare($version, '1.0', '<')) {
            throw new \RuntimeException("Nieobslugiwana wersja formatu: {$version}");
        }

        return $data;
    }

    /**
     * Import a single description.
     *
     * @return string Result: 'imported', 'updated', or 'skipped'
     */
    private function importDescription(
        array $descData,
        array $productsBySku,
        PrestaShopShop $shop,
        BlockRenderer $blockRenderer,
        bool $overwriteExisting,
        bool $skipExisting
    ): string {
        $sku = $descData['sku'] ?? null;

        if (!$sku) {
            throw new \RuntimeException('Brak SKU w danych opisu');
        }

        // Find product by SKU
        $productId = $productsBySku[$sku] ?? null;

        if (!$productId) {
            throw new \RuntimeException("Nie znaleziono produktu o SKU: {$sku}");
        }

        // Validate blocks
        $blocks = $descData['blocks'] ?? [];
        if (!is_array($blocks)) {
            throw new \RuntimeException('Nieprawidlowa struktura blokow');
        }

        $this->validateBlocksStructure($blocks);

        // Check existing description
        $existingDescription = ProductDescription::where('product_id', $productId)
            ->where('shop_id', $shop->id)
            ->first();

        if ($existingDescription) {
            if ($skipExisting) {
                return 'skipped';
            }

            if (!$overwriteExisting && !empty($existingDescription->blocks_json)) {
                return 'skipped';
            }

            // Update existing
            $existingDescription->blocks_json = $blocks;
            $existingDescription->template_id = null; // Imported descriptions are not linked to templates
            $existingDescription->save();

            // Re-render HTML
            $html = $blockRenderer->generateCleanHtml($existingDescription);
            $existingDescription->setRenderedHtml($html);

            return 'updated';
        }

        // Create new description
        $description = ProductDescription::create([
            'product_id' => $productId,
            'shop_id' => $shop->id,
            'blocks_json' => $blocks,
            'template_id' => null,
        ]);

        // Render HTML
        $html = $blockRenderer->generateCleanHtml($description);
        $description->setRenderedHtml($html);

        return 'imported';
    }

    /**
     * Validate blocks structure.
     */
    private function validateBlocksStructure(array $blocks): void
    {
        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                throw new \RuntimeException("Blok {$index}: nieprawidlowa struktura");
            }

            if (!isset($block['type'])) {
                throw new \RuntimeException("Blok {$index}: brak typu");
            }

            // Basic type validation
            $allowedTypes = [
                'heading', 'text', 'feature_card', 'spec_table', 'merit_list', 'info_card',
                'image', 'image_gallery', 'video_embed', 'parallax_image', 'picture_element',
                'slider', 'accordion', 'tabs', 'cta_button',
                'hero_banner', 'two_column', 'three_column', 'grid_section', 'full_width',
            ];

            if (!in_array($block['type'], $allowedTypes)) {
                Log::warning("Import: nieznany typ bloku '{$block['type']}' w bloku {$index}");
                // Don't throw - allow unknown types for forward compatibility
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('BulkImportDescriptionsJob failed permanently', [
            'file_path' => $this->filePath,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
