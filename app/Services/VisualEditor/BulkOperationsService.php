<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Jobs\VisualEditor\BulkApplyTemplateJob;
use App\Jobs\VisualEditor\BulkExportDescriptionsJob;
use App\Jobs\VisualEditor\BulkImportDescriptionsJob;
use App\Models\DescriptionTemplate;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bulk Operations Service for Visual Description Editor.
 *
 * Provides high-level API for bulk operations on visual descriptions:
 * - Apply template to multiple products
 * - Export descriptions to JSON
 * - Import descriptions from JSON
 * - Query exportable products
 *
 * @package App\Services\VisualEditor
 * @since ETAP_07f Faza 6.2 - Bulk Operations
 */
class BulkOperationsService
{
    public function __construct(
        private JobProgressService $progressService
    ) {}

    /**
     * Apply template to multiple products.
     *
     * Dispatches a background job and returns progress ID for tracking.
     *
     * @param int $templateId Template to apply
     * @param array $productIds Products to process
     * @param int $shopId Target shop
     * @param int|null $userId User who initiated (null = system)
     * @return array{progress_id: int, job_id: string}
     */
    public function applyTemplateToProducts(
        int $templateId,
        array $productIds,
        int $shopId,
        ?int $userId = null
    ): array {
        $userId = $userId ?? auth()->id() ?? 0;

        // Validate template exists
        $template = DescriptionTemplate::find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException("Szablon o ID {$templateId} nie istnieje");
        }

        // Validate shop exists
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            throw new \InvalidArgumentException("Sklep o ID {$shopId} nie istnieje");
        }

        // Validate products exist
        $existingCount = Product::whereIn('id', $productIds)->count();
        if ($existingCount === 0) {
            throw new \InvalidArgumentException('Nie znaleziono zadnych produktow');
        }

        // Pre-create progress record for immediate UI feedback
        $jobId = Str::uuid()->toString();
        $progressId = $this->progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'template_apply',
            count($productIds)
        );

        // Set initial metadata
        $this->progressService->updateMetadata($progressId, [
            'template_id' => $templateId,
            'template_name' => $template->name,
            'shop_name' => $shop->name,
            'products_count' => count($productIds),
            'initiated_by' => $userId,
        ]);

        // Dispatch job
        BulkApplyTemplateJob::dispatch(
            $templateId,
            $productIds,
            $shopId,
            $userId,
            $progressId
        );

        Log::info('BulkOperationsService: applyTemplateToProducts dispatched', [
            'template_id' => $templateId,
            'shop_id' => $shopId,
            'products_count' => count($productIds),
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ]);

        return [
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ];
    }

    /**
     * Export descriptions to JSON file.
     *
     * @param array $productIds Products to export
     * @param int $shopId Target shop
     * @param int|null $userId User who initiated
     * @return array{progress_id: int, job_id: string}
     */
    public function exportDescriptions(
        array $productIds,
        int $shopId,
        ?int $userId = null
    ): array {
        $userId = $userId ?? auth()->id() ?? 0;

        // Validate shop
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            throw new \InvalidArgumentException("Sklep o ID {$shopId} nie istnieje");
        }

        // Pre-create progress record
        $jobId = Str::uuid()->toString();
        $progressId = $this->progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'description_export',
            count($productIds)
        );

        $this->progressService->updateMetadata($progressId, [
            'shop_name' => $shop->name,
            'products_count' => count($productIds),
            'initiated_by' => $userId,
        ]);

        // Dispatch job
        BulkExportDescriptionsJob::dispatch(
            $productIds,
            $shopId,
            $userId,
            $progressId
        );

        Log::info('BulkOperationsService: exportDescriptions dispatched', [
            'shop_id' => $shopId,
            'products_count' => count($productIds),
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ]);

        return [
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ];
    }

    /**
     * Import descriptions from JSON file.
     *
     * @param string $filePath Path to JSON file on local disk
     * @param int $shopId Target shop for import
     * @param int|null $userId User who initiated
     * @param array $options Import options (overwrite, skip_existing)
     * @return array{progress_id: int, job_id: string}
     */
    public function importDescriptions(
        string $filePath,
        int $shopId,
        ?int $userId = null,
        array $options = []
    ): array {
        $userId = $userId ?? auth()->id() ?? 0;

        // Validate file exists
        if (!Storage::disk('local')->exists($filePath)) {
            throw new \InvalidArgumentException("Plik nie istnieje: {$filePath}");
        }

        // Validate shop
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            throw new \InvalidArgumentException("Sklep o ID {$shopId} nie istnieje");
        }

        // Pre-read file to get count for progress
        $content = Storage::disk('local')->get($filePath);
        $data = json_decode($content, true);
        $descriptionCount = count($data['descriptions'] ?? []);

        if ($descriptionCount === 0) {
            throw new \InvalidArgumentException('Plik nie zawiera opisow do importu');
        }

        // Pre-create progress record
        $jobId = Str::uuid()->toString();
        $progressId = $this->progressService->createPendingJobProgress(
            $jobId,
            $shop,
            'description_import',
            $descriptionCount
        );

        $this->progressService->updateMetadata($progressId, [
            'shop_name' => $shop->name,
            'file_path' => $filePath,
            'descriptions_count' => $descriptionCount,
            'source_shop' => $data['export_info']['shop_name'] ?? 'Unknown',
            'initiated_by' => $userId,
            'options' => $options,
        ]);

        // Dispatch job
        BulkImportDescriptionsJob::dispatch(
            $filePath,
            $shopId,
            $userId,
            $progressId,
            $options
        );

        Log::info('BulkOperationsService: importDescriptions dispatched', [
            'file_path' => $filePath,
            'shop_id' => $shopId,
            'descriptions_count' => $descriptionCount,
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ]);

        return [
            'progress_id' => $progressId,
            'job_id' => $jobId,
        ];
    }

    /**
     * Get products that have visual descriptions for a shop.
     *
     * @param int $shopId Shop to check
     * @param array $filters Optional filters (has_template, block_type, etc.)
     * @return Collection
     */
    public function getExportableProducts(int $shopId, array $filters = []): Collection
    {
        $query = ProductDescription::where('shop_id', $shopId)
            ->whereNotNull('blocks_json')
            ->where('blocks_json', '!=', '[]')
            ->with(['product:id,sku,name', 'template:id,name']);

        // Filter by template
        if (isset($filters['has_template'])) {
            if ($filters['has_template']) {
                $query->whereNotNull('template_id');
            } else {
                $query->whereNull('template_id');
            }
        }

        // Filter by specific template
        if (isset($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        // Filter by minimum block count
        if (isset($filters['min_blocks'])) {
            $query->whereRaw('JSON_LENGTH(blocks_json) >= ?', [$filters['min_blocks']]);
        }

        return $query->get()->map(function ($description) {
            return [
                'product_id' => $description->product_id,
                'sku' => $description->product->sku ?? 'unknown',
                'name' => $description->product->name ?? 'Unknown',
                'block_count' => count($description->blocks_json ?? []),
                'template_name' => $description->template->name ?? null,
                'has_rendered_html' => !empty($description->rendered_html),
                'updated_at' => $description->updated_at->toIso8601String(),
            ];
        });
    }

    /**
     * Get products without visual descriptions for a shop.
     *
     * @param int $shopId Shop to check
     * @param int $limit Maximum number of products to return
     * @return Collection
     */
    public function getProductsWithoutDescriptions(int $shopId, int $limit = 100): Collection
    {
        $productIdsWithDescriptions = ProductDescription::where('shop_id', $shopId)
            ->whereNotNull('blocks_json')
            ->where('blocks_json', '!=', '[]')
            ->pluck('product_id');

        return Product::whereNotIn('id', $productIdsWithDescriptions)
            ->select('id', 'sku', 'name', 'category_id')
            ->with('category:id,name')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category_name' => $product->category->name ?? null,
                ];
            });
    }

    /**
     * Get bulk operation statistics for a shop.
     *
     * @param int $shopId Shop to check
     * @return array Statistics
     */
    public function getStatistics(int $shopId): array
    {
        $totalProducts = Product::count();

        $descriptionsQuery = ProductDescription::where('shop_id', $shopId);
        $totalDescriptions = (clone $descriptionsQuery)->count();

        $withBlocks = (clone $descriptionsQuery)
            ->whereNotNull('blocks_json')
            ->where('blocks_json', '!=', '[]')
            ->count();

        $withTemplate = (clone $descriptionsQuery)
            ->whereNotNull('template_id')
            ->count();

        $needsRendering = (clone $descriptionsQuery)
            ->needsRerender()
            ->count();

        return [
            'total_products' => $totalProducts,
            'total_descriptions' => $totalDescriptions,
            'with_visual_blocks' => $withBlocks,
            'with_template' => $withTemplate,
            'needs_rendering' => $needsRendering,
            'coverage_percentage' => $totalProducts > 0
                ? round(($withBlocks / $totalProducts) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get available templates for bulk apply.
     *
     * @param int|null $shopId Filter by shop (null = all including global)
     * @return Collection
     */
    public function getAvailableTemplates(?int $shopId = null): Collection
    {
        $query = DescriptionTemplate::query();

        if ($shopId) {
            $query->forShop($shopId);
        }

        return $query->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'block_count' => $template->block_count,
                    'is_global' => $template->is_global,
                    'shop_id' => $template->shop_id,
                    'usage_count' => $template->getUsageCount(),
                ];
            });
    }

    /**
     * Get list of available export files.
     *
     * @return array List of export files with metadata
     */
    public function getExportFiles(): array
    {
        $files = Storage::disk('local')->files('exports/descriptions');

        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.json'))
            ->map(function ($file) {
                $size = Storage::disk('local')->size($file);
                $lastModified = Storage::disk('local')->lastModified($file);

                return [
                    'path' => $file,
                    'name' => basename($file),
                    'size_bytes' => $size,
                    'size_human' => $this->formatBytes($size),
                    'created_at' => date('Y-m-d H:i:s', $lastModified),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    /**
     * Delete an export file.
     *
     * @param string $filePath Path to file
     * @return bool Success
     */
    public function deleteExportFile(string $filePath): bool
    {
        if (!str_starts_with($filePath, 'exports/descriptions/')) {
            throw new \InvalidArgumentException('Nieprawidlowa sciezka pliku');
        }

        if (!Storage::disk('local')->exists($filePath)) {
            return false;
        }

        return Storage::disk('local')->delete($filePath);
    }

    /**
     * Format bytes to human-readable string.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
