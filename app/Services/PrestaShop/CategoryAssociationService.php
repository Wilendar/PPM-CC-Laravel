<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * Category Association Service for PrestaShop
 *
 * WORKAROUND 2025-11-05: PrestaShop 8 Web Services API ignores associations.categories
 *
 * Bug Description:
 * - PrestaShop API accepts product XML with <associations><categories>
 * - API returns HTTP 200 (success)
 * - BUT categories are NOT saved to ps_category_product table
 * - This makes products invisible in PrestaShop admin panel
 *
 * Workaround:
 * After successful API sync, this service connects to PrestaShop database
 * and inserts category associations directly to ps_category_product table
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 BUGFIX
 */
class CategoryAssociationService
{
    /**
     * Ensure product has category associations in PrestaShop database
     *
     * WORKAROUND: Direct database manipulation because API ignores associations
     *
     * @param Product $product PPM product
     * @param int $prestashopProductId PrestaShop product ID
     * @param array $categoryIds PrestaShop category IDs to associate
     * @param PrestaShopShop $shop Shop instance
     * @return bool Success status
     */
    public function ensureProductCategories(
        Product $product,
        int $prestashopProductId,
        array $categoryIds,
        PrestaShopShop $shop
    ): bool {
        if (empty($categoryIds)) {
            Log::warning('No categories to associate', [
                'product_id' => $product->id,
                'prestashop_id' => $prestashopProductId,
                'shop_id' => $shop->id,
            ]);
            return true;
        }

        try {
            $pdo = $this->connectToPrestaShop($shop);

            // Check existing associations
            $existingCategories = $this->getProductCategories($pdo, $prestashopProductId);

            Log::debug('Checking product categories', [
                'product_id' => $product->id,
                'prestashop_id' => $prestashopProductId,
                'requested_categories' => $categoryIds,
                'existing_categories' => $existingCategories,
                'shop_id' => $shop->id,
            ]);

            // Add missing categories
            $added = 0;
            foreach ($categoryIds as $categoryId) {
                if (!in_array($categoryId, $existingCategories)) {
                    $this->addCategoryAssociation($pdo, $prestashopProductId, $categoryId);
                    $added++;
                }
            }

            // Update id_category_default if needed
            $this->updateDefaultCategory($pdo, $prestashopProductId, $categoryIds[0]);

            if ($added > 0) {
                Log::info('Added category associations via direct database', [
                    'product_id' => $product->id,
                    'prestashop_id' => $prestashopProductId,
                    'categories_added' => $added,
                    'total_categories' => count($categoryIds),
                    'shop_id' => $shop->id,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to ensure product categories', [
                'product_id' => $product->id,
                'prestashop_id' => $prestashopProductId,
                'error' => $e->getMessage(),
                'shop_id' => $shop->id,
            ]);

            return false;
        }
    }

    /**
     * Connect to PrestaShop database
     *
     * @param PrestaShopShop $shop Shop instance
     * @return PDO Database connection
     * @throws \Exception On connection failure
     */
    private function connectToPrestaShop(PrestaShopShop $shop): PDO
    {
        // Validate database credentials
        if (empty($shop->db_host) || empty($shop->db_name) || empty($shop->db_user)) {
            throw new \Exception('Database credentials not configured for this shop. Please update shop settings.');
        }

        // Model automatically decrypts db_password (encrypted cast)
        $password = $shop->db_password;

        try {
            $pdo = new PDO(
                "mysql:host={$shop->db_host};dbname={$shop->db_name};charset=utf8mb4",
                $shop->db_user,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            Log::debug('Connected to PrestaShop database', [
                'shop_id' => $shop->id,
                'db_host' => $shop->db_host,
                'db_name' => $shop->db_name,
            ]);

            return $pdo;

        } catch (PDOException $e) {
            Log::error('Failed to connect to PrestaShop database', [
                'shop_id' => $shop->id,
                'db_host' => $shop->db_host,
                'db_name' => $shop->db_name,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to PrestaShop database: {$e->getMessage()}");
        }
    }

    /**
     * Get product's existing category associations
     *
     * @param PDO $pdo Database connection
     * @param int $productId PrestaShop product ID
     * @return array Array of category IDs
     */
    private function getProductCategories(PDO $pdo, int $productId): array
    {
        $stmt = $pdo->prepare("
            SELECT id_category
            FROM ps_category_product
            WHERE id_product = ?
            ORDER BY position
        ");
        $stmt->execute([$productId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Add category association to product
     *
     * @param PDO $pdo Database connection
     * @param int $productId PrestaShop product ID
     * @param int $categoryId PrestaShop category ID
     * @return void
     */
    private function addCategoryAssociation(PDO $pdo, int $productId, int $categoryId): void
    {
        // Get next position
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(position), -1) + 1
            FROM ps_category_product
            WHERE id_product = ?
        ");
        $stmt->execute([$productId]);
        $position = $stmt->fetchColumn();

        // Insert association
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO ps_category_product (id_product, id_category, position)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$productId, $categoryId, $position]);
    }

    /**
     * Update product's default category
     *
     * @param PDO $pdo Database connection
     * @param int $productId PrestaShop product ID
     * @param int $defaultCategoryId Default category ID
     * @return void
     */
    private function updateDefaultCategory(PDO $pdo, int $productId, int $defaultCategoryId): void
    {
        $stmt = $pdo->prepare("
            UPDATE ps_product
            SET id_category_default = ?
            WHERE id_product = ?
        ");
        $stmt->execute([$defaultCategoryId, $productId]);
    }
}
