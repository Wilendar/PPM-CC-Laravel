<?php

namespace App\Services\Product;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\VariantAttribute;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AttributeTypeService - Manages AttributeType CRUD operations
 *
 * RESPONSIBILITY: AttributeType (grupa wariantów) creation, update, deletion
 *
 * FEATURES:
 * - Create new attribute type (Kolor, Rozmiar, Material)
 * - Update existing attribute type
 * - Delete attribute type with product usage protection
 * - Get products using attribute type
 * - Reorder attribute types (drag & drop support)
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns
 * - DB transactions for data integrity
 * - Comprehensive error handling + logging
 * - CLAUDE.md: <300 lines (currently ~200 lines)
 *
 * USAGE:
 * ```php
 * $service = app(AttributeTypeService::class);
 *
 * // Create attribute type
 * $type = $service->createAttributeType([
 *     'name' => 'Kolor',
 *     'code' => 'color',
 *     'display_type' => 'color'
 * ]);
 * ```
 *
 * RELATED:
 * - Plan_Projektu/ETAP_05b_Produkty_Warianty.md - Phase 2 (Service Split)
 * - app/Models/AttributeType.php
 * - app/Services/Product/AttributeValueService.php
 * - app/Services/Product/AttributeUsageService.php
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_05b Phase 2 (2025-10-24)
 */
class AttributeTypeService
{
    /**
     * Create new attribute type
     *
     * @param array $data Attribute type data (name, code, display_type, position)
     * @return AttributeType Created attribute type
     * @throws \InvalidArgumentException On validation failure
     * @throws \Exception On database error
     */
    public function createAttributeType(array $data): AttributeType
    {
        return DB::transaction(function () use ($data) {
            Log::info('AttributeTypeService::createAttributeType CALLED', [
                'name' => $data['name'] ?? null,
                'code' => $data['code'] ?? null,
            ]);

            try {
                // Validation
                if (empty($data['name'])) {
                    throw new \InvalidArgumentException('Attribute type name is required');
                }
                if (empty($data['code'])) {
                    throw new \InvalidArgumentException('Attribute type code is required');
                }

                // Check for duplicate code
                if (AttributeType::where('code', $data['code'])->exists()) {
                    throw new \InvalidArgumentException("Attribute type with code '{$data['code']}' already exists");
                }

                // Create attribute type
                $type = AttributeType::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'display_type' => $data['display_type'] ?? 'dropdown',
                    'position' => $data['position'] ?? 0,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                Log::info('AttributeTypeService::createAttributeType COMPLETED', [
                    'type_id' => $type->id,
                    'code' => $type->code,
                ]);

                return $type;

            } catch (\Exception $e) {
                Log::error('AttributeTypeService::createAttributeType FAILED', [
                    'code' => $data['code'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update existing attribute type
     *
     * @param AttributeType $type Attribute type to update
     * @param array $data Updated data
     * @return AttributeType Updated attribute type
     * @throws \InvalidArgumentException On validation failure
     * @throws \Exception On database error
     */
    public function updateAttributeType(AttributeType $type, array $data): AttributeType
    {
        return DB::transaction(function () use ($type, $data) {
            Log::info('AttributeTypeService::updateAttributeType CALLED', [
                'type_id' => $type->id,
                'code' => $type->code,
            ]);

            try {
                // Check for duplicate code (excluding current type)
                if (isset($data['code']) && $data['code'] !== $type->code) {
                    if (AttributeType::where('code', $data['code'])->where('id', '!=', $type->id)->exists()) {
                        throw new \InvalidArgumentException("Attribute type with code '{$data['code']}' already exists");
                    }
                }

                // Update attribute type
                $type->update([
                    'name' => $data['name'] ?? $type->name,
                    'code' => $data['code'] ?? $type->code,
                    'display_type' => $data['display_type'] ?? $type->display_type,
                    'position' => $data['position'] ?? $type->position,
                    'is_active' => $data['is_active'] ?? $type->is_active,
                ]);

                Log::info('AttributeTypeService::updateAttributeType COMPLETED', [
                    'type_id' => $type->id,
                ]);

                return $type->fresh();

            } catch (\Exception $e) {
                Log::error('AttributeTypeService::updateAttributeType FAILED', [
                    'type_id' => $type->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete attribute type
     *
     * @param AttributeType $type Attribute type to delete
     * @param bool $force Force delete even if products using it
     * @return bool Success status
     * @throws \RuntimeException If products using and $force = false
     * @throws \Exception On database error
     */
    public function deleteAttributeType(AttributeType $type, bool $force = false): bool
    {
        return DB::transaction(function () use ($type, $force) {
            Log::info('AttributeTypeService::deleteAttributeType CALLED', [
                'type_id' => $type->id,
                'code' => $type->code,
                'force' => $force,
            ]);

            try {
                // Check products using this type
                $products = $this->getProductsUsingAttributeType($type->id);

                if ($products->count() > 0 && !$force) {
                    throw new \RuntimeException(
                        "Cannot delete attribute type '{$type->name}' - used by {$products->count()} products. Use force=true to cascade delete."
                    );
                }

                if ($force && $products->count() > 0) {
                    Log::warning('AttributeTypeService::deleteAttributeType FORCE DELETE', [
                        'type_id' => $type->id,
                        'products_affected' => $products->count(),
                    ]);

                    // Cascade delete: delete all variant attributes using this type's values
                    $valueIds = AttributeValue::where('attribute_type_id', $type->id)->pluck('id');
                    VariantAttribute::whereIn('value_id', $valueIds)->delete();
                }

                // Delete type (cascade delete configured in migration handles values)
                $result = $type->delete();

                Log::info('AttributeTypeService::deleteAttributeType COMPLETED', [
                    'type_id' => $type->id,
                ]);

                return $result;

            } catch (\Exception $e) {
                Log::error('AttributeTypeService::deleteAttributeType FAILED', [
                    'type_id' => $type->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get products using this attribute type
     *
     * @param int $typeId Attribute type ID
     * @return Collection Collection of products with usage data
     */
    public function getProductsUsingAttributeType(int $typeId): Collection
    {
        // Get all values for this type
        $valueIds = AttributeValue::where('attribute_type_id', $typeId)->pluck('id');

        if ($valueIds->isEmpty()) {
            return collect();
        }

        // Get variants using these values
        $variantIds = VariantAttribute::whereIn('value_id', $valueIds)
            ->distinct()
            ->pluck('variant_id');

        if ($variantIds->isEmpty()) {
            return collect();
        }

        // Get products from variants
        return ProductVariant::whereIn('id', $variantIds)
            ->with('product:id,sku,name')
            ->get()
            ->groupBy('product_id')
            ->map(function ($variants, $productId) {
                $product = $variants->first()->product;
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'variant_count' => $variants->count(),
                ];
            })
            ->values();
    }

    /**
     * Reorder attribute types (drag & drop)
     *
     * @param array $typeIdsOrdered Array of type IDs in new order
     * @return bool Success status
     * @throws \Exception On database error
     */
    public function reorderAttributeTypes(array $typeIdsOrdered): bool
    {
        return DB::transaction(function () use ($typeIdsOrdered) {
            Log::info('AttributeTypeService::reorderAttributeTypes CALLED', [
                'types_count' => count($typeIdsOrdered),
            ]);

            try {
                foreach ($typeIdsOrdered as $index => $typeId) {
                    AttributeType::where('id', $typeId)
                        ->update(['position' => $index + 1]); // Position starts at 1
                }

                Log::info('AttributeTypeService::reorderAttributeTypes COMPLETED');

                return true;

            } catch (\Exception $e) {
                Log::error('AttributeTypeService::reorderAttributeTypes FAILED', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
}
