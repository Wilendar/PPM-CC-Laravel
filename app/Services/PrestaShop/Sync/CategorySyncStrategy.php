<?php

namespace App\Services\PrestaShop\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\CategoryTransformer;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use App\Exceptions\PrestaShopAPIException;

/**
 * Category Sync Strategy
 *
 * Implements ISyncStrategy dla Category model synchronization PPM → PrestaShop
 *
 * @package App\Services\PrestaShop\Sync
 */
class CategorySyncStrategy implements ISyncStrategy
{
    public function __construct(
        private CategoryTransformer $transformer
    ) {}

    public function syncToPrestaShop(
        Model $model,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): array {
        if (!$model instanceof Category) {
            throw new \InvalidArgumentException('Model must be instance of Category');
        }

        $startTime = microtime(true);

        // Validate
        $validationErrors = $this->validateBeforeSync($model, $shop);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Category validation failed: ' . implode(', ', $validationErrors));
        }

        // Ensure parent exists first (recursive)
        if ($model->parent_id) {
            $this->ensureParentExists($model, $shop, $client);
        }

        // Check existing mapping
        $existingMapping = $this->getMapping($model, $shop);

        DB::beginTransaction();

        try {
            // Transform category data
            $categoryData = $this->prepareCategoryData($model, $shop, $client);

            // Create or update
            $isUpdate = $existingMapping !== null;

            if ($isUpdate) {
                $response = $client->updateCategory($existingMapping->prestashop_id, $categoryData);
                $operation = 'update';
            } else {
                $response = $client->createCategory($categoryData);
                $operation = 'create';
            }

            // Extract PrestaShop category ID
            $externalId = $this->extractExternalId($response);
            if (!$externalId) {
                throw new PrestaShopAPIException('Failed to extract category ID from response');
            }

            // Create or update mapping
            $this->createOrUpdateMapping($model, $shop, $externalId);

            DB::commit();

            Log::info('Category synced successfully', [
                'category_id' => $model->id,
                'shop_id' => $shop->id,
                'external_id' => $externalId,
                'operation' => $operation,
            ]);

            return [
                'success' => true,
                'external_id' => $externalId,
                'message' => "Category {$operation}d successfully",
                'operation' => $operation,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleSyncError($e, $model, $shop);
            throw $e;
        }
    }

    /**
     * Sync complete category hierarchy
     */
    public function syncCategoryHierarchy(
        PrestaShopShop $shop,
        BasePrestaShopClient $client
    ): array {
        $results = ['synced' => 0, 'errors' => 0, 'details' => []];

        // Sync level by level
        for ($level = 0; $level <= Category::MAX_LEVEL; $level++) {
            $categories = Category::where('is_active', true)
                ->where('level', $level)
                ->orderBy('sort_order')
                ->get();

            foreach ($categories as $category) {
                try {
                    $result = $this->syncToPrestaShop($category, $client, $shop);
                    if ($result['success']) {
                        $results['synced']++;
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Category hierarchy sync failed', [
                        'category_id' => $category->id,
                        'level' => $level,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $results;
    }

    private function ensureParentExists(
        Category $category,
        PrestaShopShop $shop,
        BasePrestaShopClient $client
    ): void {
        if (!$category->parent_id) {
            return;
        }

        $parent = $category->parent;
        if (!$parent) {
            throw new \InvalidArgumentException("Parent category not found: {$category->parent_id}");
        }

        // Check if parent already mapped
        $parentMapping = $this->getMapping($parent, $shop);
        if ($parentMapping) {
            return; // Parent exists
        }

        // Recursively sync parent
        $this->syncToPrestaShop($parent, $client, $shop);
    }

    private function prepareCategoryData(
        Category $category,
        PrestaShopShop $shop,
        BasePrestaShopClient $client
    ): array {
        $data = $this->transformer->transformForPrestaShop($category, $client);

        // Map parent category
        if ($category->parent_id) {
            $parentMapping = $this->getMapping($category->parent, $shop);
            if ($parentMapping) {
                $data['id_parent'] = $parentMapping->prestashop_id;
            } else {
                $data['id_parent'] = 2; // Fallback to PrestaShop 'Home'
            }
        } else {
            $data['id_parent'] = 2; // Root category
        }

        return $data;
    }

    public function calculateChecksum(Model $model, PrestaShopShop $shop): string
    {
        if (!$model instanceof Category) {
            throw new \InvalidArgumentException('Model must be instance of Category');
        }

        $data = [
            'name' => $model->name,
            'description' => $model->description,
            'parent_id' => $model->parent_id,
            'is_active' => $model->is_active,
        ];

        ksort($data);
        return hash('sha256', json_encode($data));
    }

    public function handleSyncError(
        \Exception $exception,
        Model $model,
        PrestaShopShop $shop
    ): void {
        if (!$model instanceof Category) {
            return;
        }

        Log::error('Category sync failed', [
            'category_id' => $model->id,
            'shop_id' => $shop->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function validateBeforeSync(Model $model, PrestaShopShop $shop): array
    {
        if (!$model instanceof Category) {
            return ['Model must be instance of Category'];
        }

        $errors = [];

        if (empty($model->name)) {
            $errors[] = 'Category name is required';
        }

        if (!$model->is_active) {
            $errors[] = 'Category must be active to sync';
        }

        return $errors;
    }

    public function needsSync(Model $model, PrestaShopShop $shop): bool
    {
        if (!$model instanceof Category) {
            return false;
        }

        // Always sync if no mapping exists
        $mapping = $this->getMapping($model, $shop);
        return $mapping === null;
    }

    private function getMapping(Category $category, PrestaShopShop $shop): ?ShopMapping
    {
        return ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('ppm_value', (string)$category->id)
            ->first();
    }

    private function createOrUpdateMapping(
        Category $category,
        PrestaShopShop $shop,
        int $externalId
    ): ShopMapping {
        return ShopMapping::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'mapping_type' => 'category',
                'ppm_value' => (string)$category->id,
            ],
            [
                'prestashop_id' => $externalId,
                'prestashop_value' => $category->name,
            ]
        );
    }

    private function extractExternalId(array $response): ?int
    {
        if (isset($response['category']['id'])) {
            return (int) $response['category']['id'];
        }

        if (isset($response['id'])) {
            return (int) $response['id'];
        }

        return null;
    }
}
