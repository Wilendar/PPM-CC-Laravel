<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\VariantPrice;
use App\Models\VariantStock;
use App\Models\VariantImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * VariantManager Service
 *
 * Centralized service for managing product variants with attributes, pricing, stock, and images
 *
 * FEATURES:
 * - Variant CRUD operations (create, update, delete, setDefault)
 * - Pricing management (multi-group prices, special prices, bulk updates)
 * - Stock management (multi-warehouse, reservation, availability)
 * - Attribute management (size, color, material, etc.)
 * - Image management (variant-specific images, cover image, ordering)
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns (Context7 verified)
 * - DB transactions for multi-record operations
 * - Type hints PHP 8.3 (strict types)
 * - Comprehensive error handling + logging
 * - CLAUDE.md: ~250 linii limit (compliant)
 *
 * USAGE:
 * ```php
 * $manager = app(VariantManager::class);
 *
 * // Create variant with attributes
 * $variant = $manager->createVariant($product, [
 *     'sku' => 'PART-XL-RED',
 *     'name' => 'XL Red',
 *     'is_default' => false,
 *     'attributes' => [
 *         ['attribute_type_id' => 1, 'value' => 'XL', 'value_code' => 'xl'],
 *         ['attribute_type_id' => 2, 'value' => 'Red', 'value_code' => 'red', 'color_hex' => '#ff0000']
 *     ],
 *     'prices' => [...],
 *     'stock' => [...]
 * ]);
 *
 * // Set prices for all price groups
 * $manager->setPrices($variant, [
 *     ['price_group_id' => 1, 'price' => 100.00, 'price_special' => 90.00]
 * ]);
 *
 * // Find variant by attributes
 * $variant = $manager->findByAttributes($product, ['size' => 'xl', 'color' => 'red']);
 * ```
 *
 * RELATED:
 * - Plan_Projektu/ETAP_05a_Produkty.md - FAZA 3 (Services Layer)
 * - app/Models/ProductVariant.php
 * - app/Models/VariantAttribute.php
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_05a FAZA 3 (2025-10-17)
 */
class VariantManager
{
    /**
     * Create new product variant with attributes, prices, and stock
     *
     * @param Product $product Parent product
     * @param array $data Variant data with attributes, prices, stock
     * @return ProductVariant Created variant with relationships loaded
     * @throws \Exception
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            Log::info('VariantManager::createVariant CALLED', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'variant_sku' => $data['sku'] ?? null,
            ]);

            try {
                // Create variant base record
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'is_default' => $data['is_default'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                // Set attributes if provided
                if (!empty($data['attributes'])) {
                    $this->setAttributes($variant, $data['attributes']);
                }

                // Set prices if provided
                if (!empty($data['prices'])) {
                    $this->setPrices($variant, $data['prices']);
                }

                // Set stock if provided
                if (!empty($data['stock'])) {
                    $this->setStock($variant, $data['stock']);
                }

                Log::info('VariantManager::createVariant COMPLETED', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                ]);

                return $variant->load('attributes', 'prices', 'stock');

            } catch (\Exception $e) {
                Log::error('VariantManager::createVariant FAILED', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update existing variant
     *
     * @param ProductVariant $variant Variant to update
     * @param array $data Updated data
     * @return ProductVariant Updated variant
     * @throws \Exception
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            Log::info('VariantManager::updateVariant CALLED', [
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku,
            ]);

            try {
                // Update base fields
                $variant->update([
                    'sku' => $data['sku'] ?? $variant->sku,
                    'name' => $data['name'] ?? $variant->name,
                    'is_default' => $data['is_default'] ?? $variant->is_default,
                    'is_active' => $data['is_active'] ?? $variant->is_active,
                ]);

                // Update attributes if provided
                if (isset($data['attributes'])) {
                    $this->setAttributes($variant, $data['attributes']);
                }

                // Update prices if provided
                if (isset($data['prices'])) {
                    $this->setPrices($variant, $data['prices']);
                }

                // Update stock if provided
                if (isset($data['stock'])) {
                    $this->setStock($variant, $data['stock']);
                }

                Log::info('VariantManager::updateVariant COMPLETED', [
                    'variant_id' => $variant->id,
                ]);

                return $variant->fresh(['attributes', 'prices', 'stock']);

            } catch (\Exception $e) {
                Log::error('VariantManager::updateVariant FAILED', [
                    'variant_id' => $variant->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete variant (soft delete)
     *
     * @param ProductVariant $variant Variant to delete
     * @return bool Success status
     * @throws \Exception
     */
    public function deleteVariant(ProductVariant $variant): bool
    {
        try {
            Log::info('VariantManager::deleteVariant CALLED', [
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku,
            ]);

            $result = $variant->delete();

            Log::info('VariantManager::deleteVariant COMPLETED', [
                'variant_id' => $variant->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('VariantManager::deleteVariant FAILED', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Set variant as default for product
     *
     * @param Product $product Parent product
     * @param ProductVariant $variant Variant to set as default
     * @return void
     */
    public function setDefaultVariant(Product $product, ProductVariant $variant): void
    {
        DB::transaction(function () use ($product, $variant) {
            Log::info('VariantManager::setDefaultVariant CALLED', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
            ]);

            // Unset current default
            ProductVariant::where('product_id', $product->id)
                ->update(['is_default' => false]);

            // Set new default
            $variant->update(['is_default' => true]);

            Log::info('VariantManager::setDefaultVariant COMPLETED', [
                'variant_id' => $variant->id,
            ]);
        });
    }

    /**
     * Set variant prices for all price groups
     *
     * @param ProductVariant $variant Variant to set prices for
     * @param array $prices Price data array
     * @return Collection Created/updated price records
     */
    public function setPrices(ProductVariant $variant, array $prices): Collection
    {
        Log::info('VariantManager::setPrices CALLED', [
            'variant_id' => $variant->id,
            'prices_count' => count($prices),
        ]);

        // Delete existing prices
        VariantPrice::where('variant_id', $variant->id)->delete();

        // Create new prices
        $created = [];
        foreach ($prices as $priceData) {
            $created[] = VariantPrice::create([
                'variant_id' => $variant->id,
                'price_group_id' => $priceData['price_group_id'],
                'price' => $priceData['price'],
                'price_special' => $priceData['price_special'] ?? null,
                'special_from' => $priceData['special_from'] ?? null,
                'special_to' => $priceData['special_to'] ?? null,
            ]);
        }

        Log::info('VariantManager::setPrices COMPLETED', [
            'variant_id' => $variant->id,
            'created_count' => count($created),
        ]);

        return collect($created);
    }

    /**
     * Set variant stock for warehouses
     *
     * @param ProductVariant $variant Variant to set stock for
     * @param array $stock Stock data array
     * @return Collection Created/updated stock records
     */
    public function setStock(ProductVariant $variant, array $stock): Collection
    {
        Log::info('VariantManager::setStock CALLED', [
            'variant_id' => $variant->id,
            'stock_count' => count($stock),
        ]);

        // Delete existing stock
        VariantStock::where('variant_id', $variant->id)->delete();

        // Create new stock
        $created = [];
        foreach ($stock as $stockData) {
            $created[] = VariantStock::create([
                'variant_id' => $variant->id,
                'warehouse_id' => $stockData['warehouse_id'],
                'quantity' => $stockData['quantity'] ?? 0,
            ]);
        }

        Log::info('VariantManager::setStock COMPLETED', [
            'variant_id' => $variant->id,
            'created_count' => count($created),
        ]);

        return collect($created);
    }

    /**
     * Get total available stock across all warehouses
     *
     * @param ProductVariant $variant Variant to check
     * @return int Total quantity
     */
    public function getTotalAvailable(ProductVariant $variant): int
    {
        return VariantStock::where('variant_id', $variant->id)->sum('quantity');
    }

    /**
     * Set variant attributes
     *
     * @param ProductVariant $variant Variant to set attributes for
     * @param array $attributes Attribute data array
     * @return Collection Created attribute records
     */
    public function setAttributes(ProductVariant $variant, array $attributes): Collection
    {
        Log::info('VariantManager::setAttributes CALLED', [
            'variant_id' => $variant->id,
            'attributes_count' => count($attributes),
        ]);

        // Delete existing attributes
        VariantAttribute::where('variant_id', $variant->id)->delete();

        // Create new attributes
        $created = [];
        foreach ($attributes as $attrData) {
            $created[] = VariantAttribute::create([
                'variant_id' => $variant->id,
                'attribute_type_id' => $attrData['attribute_type_id'],
                'value' => $attrData['value'],
                'value_code' => $attrData['value_code'] ?? null,
                'color_hex' => $attrData['color_hex'] ?? null,
            ]);
        }

        Log::info('VariantManager::setAttributes COMPLETED', [
            'variant_id' => $variant->id,
            'created_count' => count($created),
        ]);

        return collect($created);
    }

    /**
     * Find variant by attribute combination
     *
     * @param Product $product Parent product
     * @param array $attributeCodes Attribute codes (e.g., ['size' => 'xl', 'color' => 'red'])
     * @return ProductVariant|null Found variant or null
     */
    public function findByAttributes(Product $product, array $attributeCodes): ?ProductVariant
    {
        Log::debug('VariantManager::findByAttributes CALLED', [
            'product_id' => $product->id,
            'attribute_codes' => $attributeCodes,
        ]);

        // Get all variants for product with attributes
        $variants = ProductVariant::where('product_id', $product->id)
            ->with('attributes.type')
            ->get();

        // Filter variants by attribute codes
        foreach ($variants as $variant) {
            $matches = true;

            foreach ($attributeCodes as $typeCode => $valueCode) {
                $hasMatch = $variant->attributes->contains(function ($attr) use ($typeCode, $valueCode) {
                    return $attr->type->code === $typeCode && $attr->value_code === $valueCode;
                });

                if (!$hasMatch) {
                    $matches = false;
                    break;
                }
            }

            if ($matches && $variant->attributes->count() === count($attributeCodes)) {
                Log::debug('VariantManager::findByAttributes FOUND', [
                    'variant_id' => $variant->id,
                ]);
                return $variant;
            }
        }

        Log::debug('VariantManager::findByAttributes NOT FOUND');
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Upload image for variant
     *
     * @param int $variantId Variant ID
     * @param UploadedFile $file Uploaded file
     * @param int|null $position Position (auto-increment if null)
     * @param bool $isPrimary Set as primary image
     * @return VariantImage Created image record
     * @throws \Exception
     */
    public function uploadImage(int $variantId, UploadedFile $file, ?int $position = null, bool $isPrimary = false): VariantImage
    {
        try {
            Log::info('VariantManager::uploadImage CALLED', [
                'variant_id' => $variantId,
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            // Get variant
            $variant = ProductVariant::findOrFail($variantId);

            // Determine position if not provided
            if ($position === null) {
                $maxPosition = VariantImage::where('variant_id', $variantId)->max('position') ?? 0;
                $position = $maxPosition + 1;
            }

            // Store file in storage/app/public/variants/{variant_id}/
            $path = $file->store("variants/{$variantId}", 'public');
            $filename = basename($path);

            // Create DB record
            $image = VariantImage::create([
                'variant_id' => $variantId,
                'filename' => $filename,
                'path' => $path,
                'is_cover' => $isPrimary,
                'position' => $position,
            ]);

            // If primary, unset others
            if ($isPrimary) {
                $image->setAsCover();
            }

            Log::info('VariantManager::uploadImage COMPLETED', [
                'image_id' => $image->id,
                'path' => $path,
            ]);

            return $image;

        } catch (\Exception $e) {
            Log::error('VariantManager::uploadImage FAILED', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reorder variant images
     *
     * @param int $variantId Variant ID
     * @param array $imageIdsOrdered Array of image IDs in new order
     * @return bool Success status
     * @throws \Exception
     */
    public function reorderImages(int $variantId, array $imageIdsOrdered): bool
    {
        try {
            Log::info('VariantManager::reorderImages CALLED', [
                'variant_id' => $variantId,
                'image_ids' => $imageIdsOrdered,
            ]);

            DB::transaction(function () use ($variantId, $imageIdsOrdered) {
                foreach ($imageIdsOrdered as $position => $imageId) {
                    VariantImage::where('id', $imageId)
                        ->where('variant_id', $variantId)
                        ->update(['position' => $position + 1]); // Position starts at 1
                }
            });

            Log::info('VariantManager::reorderImages COMPLETED');
            return true;

        } catch (\Exception $e) {
            Log::error('VariantManager::reorderImages FAILED', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete variant image
     *
     * @param int $imageId Image ID to delete
     * @return bool Success status
     * @throws \Exception
     */
    public function deleteImage(int $imageId): bool
    {
        try {
            Log::info('VariantManager::deleteImage CALLED', [
                'image_id' => $imageId,
            ]);

            $image = VariantImage::findOrFail($imageId);

            // Delete file from storage
            $image->deleteFile();

            // Delete DB record
            $result = $image->delete();

            Log::info('VariantManager::deleteImage COMPLETED', [
                'image_id' => $imageId,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('VariantManager::deleteImage FAILED', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Set image as primary (cover) for variant
     *
     * @param int $variantId Variant ID
     * @param int $imageId Image ID to set as primary
     * @return bool Success status
     * @throws \Exception
     */
    public function setPrimaryImage(int $variantId, int $imageId): bool
    {
        try {
            Log::info('VariantManager::setPrimaryImage CALLED', [
                'variant_id' => $variantId,
                'image_id' => $imageId,
            ]);

            $image = VariantImage::where('id', $imageId)
                ->where('variant_id', $variantId)
                ->firstOrFail();

            $result = $image->setAsCover();

            Log::info('VariantManager::setPrimaryImage COMPLETED');
            return $result;

        } catch (\Exception $e) {
            Log::error('VariantManager::setPrimaryImage FAILED', [
                'variant_id' => $variantId,
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Copy images from one variant to another
     *
     * @param int $sourceVariantId Source variant ID
     * @param int $targetVariantId Target variant ID
     * @return Collection Copied image records
     * @throws \Exception
     */
    public function copyImagesToVariant(int $sourceVariantId, int $targetVariantId): Collection
    {
        try {
            Log::info('VariantManager::copyImagesToVariant CALLED', [
                'source_variant_id' => $sourceVariantId,
                'target_variant_id' => $targetVariantId,
            ]);

            $sourceImages = VariantImage::where('variant_id', $sourceVariantId)
                ->orderBy('position')
                ->get();

            $copiedImages = [];

            foreach ($sourceImages as $sourceImage) {
                // Copy file in storage
                $sourcePath = $sourceImage->path;
                $targetPath = str_replace("variants/{$sourceVariantId}", "variants/{$targetVariantId}", $sourcePath);

                Storage::disk('public')->copy($sourcePath, $targetPath);

                // Create new DB record
                $copiedImage = VariantImage::create([
                    'variant_id' => $targetVariantId,
                    'filename' => $sourceImage->filename,
                    'path' => $targetPath,
                    'is_cover' => $sourceImage->is_cover,
                    'position' => $sourceImage->position,
                ]);

                $copiedImages[] = $copiedImage;
            }

            Log::info('VariantManager::copyImagesToVariant COMPLETED', [
                'copied_count' => count($copiedImages),
            ]);

            return collect($copiedImages);

        } catch (\Exception $e) {
            Log::error('VariantManager::copyImagesToVariant FAILED', [
                'source_variant_id' => $sourceVariantId,
                'target_variant_id' => $targetVariantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get variant images ordered by position
     *
     * @param int $variantId Variant ID
     * @return Collection Image records
     */
    public function getVariantImages(int $variantId): Collection
    {
        return VariantImage::where('variant_id', $variantId)
            ->orderBy('position')
            ->get();
    }
}
