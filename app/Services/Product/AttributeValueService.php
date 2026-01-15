<?php

namespace App\Services\Product;

use App\Models\AttributeValue;
use App\Models\VariantAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AttributeValueService - Manages AttributeValue CRUD operations
 *
 * RESPONSIBILITY: AttributeValue (konkretne wartości grup) creation, update, deletion
 *
 * FEATURES:
 * - Create new attribute value (Czerwony, S, Bawełna)
 * - Update existing attribute value
 * - Delete attribute value with variant usage protection
 * - Get variants using attribute value
 * - Reorder attribute values (drag & drop support)
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns
 * - DB transactions for data integrity
 * - Comprehensive error handling + logging
 * - CLAUDE.md: <300 lines (currently ~150 lines)
 *
 * USAGE:
 * ```php
 * $service = app(AttributeValueService::class);
 *
 * // Create attribute value
 * $value = $service->createAttributeValue($typeId, [
 *     'code' => 'red',
 *     'label' => 'Czerwony',
 *     'color_hex' => '#ff0000'
 * ]);
 * ```
 *
 * RELATED:
 * - Plan_Projektu/ETAP_05b_Produkty_Warianty.md - Phase 2 (Service Split)
 * - app/Models/AttributeValue.php
 * - app/Services/Product/AttributeTypeService.php
 * - app/Services/Product/AttributeUsageService.php
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_05b Phase 2 (2025-10-24)
 */
class AttributeValueService
{
    /**
     * Create new attribute value
     *
     * @param int $typeId Attribute type ID
     * @param array $data Value data (code, label, color_hex, position)
     * @return AttributeValue Created attribute value
     * @throws \InvalidArgumentException On validation failure
     * @throws \Exception On database error
     */
    public function createAttributeValue(int $typeId, array $data): AttributeValue
    {
        return DB::transaction(function () use ($typeId, $data) {
            Log::info('AttributeValueService::createAttributeValue CALLED', [
                'type_id' => $typeId,
                'code' => $data['code'] ?? null,
            ]);

            try {
                // Validation
                if (empty($data['code'])) {
                    throw new \InvalidArgumentException('Attribute value code is required');
                }
                if (empty($data['label'])) {
                    throw new \InvalidArgumentException('Attribute value label is required');
                }

                // Check for duplicate code per type
                if (AttributeValue::where('attribute_type_id', $typeId)->where('code', $data['code'])->exists()) {
                    throw new \InvalidArgumentException("Attribute value with code '{$data['code']}' already exists for this type");
                }

                // Auto-increment position if not provided
                if (!isset($data['position'])) {
                    $maxPosition = AttributeValue::where('attribute_type_id', $typeId)->max('position') ?? 0;
                    $data['position'] = $maxPosition + 1;
                }

                // Create attribute value
                $value = AttributeValue::create([
                    'attribute_type_id' => $typeId,
                    'code' => $data['code'],
                    'label' => $data['label'],
                    'color_hex' => $data['color_hex'] ?? null,
                    'auto_prefix' => $data['auto_prefix'] ?? null,
                    'auto_prefix_enabled' => $data['auto_prefix_enabled'] ?? false,
                    'auto_suffix' => $data['auto_suffix'] ?? null,
                    'auto_suffix_enabled' => $data['auto_suffix_enabled'] ?? false,
                    'position' => $data['position'],
                    'is_active' => $data['is_active'] ?? true,
                ]);

                Log::info('AttributeValueService::createAttributeValue COMPLETED', [
                    'value_id' => $value->id,
                    'code' => $value->code,
                ]);

                return $value;

            } catch (\Exception $e) {
                Log::error('AttributeValueService::createAttributeValue FAILED', [
                    'type_id' => $typeId,
                    'code' => $data['code'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update existing attribute value
     *
     * @param AttributeValue $value Attribute value to update
     * @param array $data Updated data
     * @return AttributeValue Updated attribute value
     * @throws \InvalidArgumentException On validation failure
     * @throws \Exception On database error
     */
    public function updateAttributeValue(AttributeValue $value, array $data): AttributeValue
    {
        return DB::transaction(function () use ($value, $data) {
            Log::info('AttributeValueService::updateAttributeValue CALLED', [
                'value_id' => $value->id,
                'code' => $value->code,
            ]);

            try {
                // Check for duplicate code per type (excluding current value)
                if (isset($data['code']) && $data['code'] !== $value->code) {
                    if (AttributeValue::where('attribute_type_id', $value->attribute_type_id)
                        ->where('code', $data['code'])
                        ->where('id', '!=', $value->id)
                        ->exists()) {
                        throw new \InvalidArgumentException("Attribute value with code '{$data['code']}' already exists for this type");
                    }
                }

                // Update attribute value
                $value->update([
                    'code' => $data['code'] ?? $value->code,
                    'label' => $data['label'] ?? $value->label,
                    'color_hex' => $data['color_hex'] ?? $value->color_hex,
                    'auto_prefix' => array_key_exists('auto_prefix', $data) ? $data['auto_prefix'] : $value->auto_prefix,
                    'auto_prefix_enabled' => array_key_exists('auto_prefix_enabled', $data) ? $data['auto_prefix_enabled'] : $value->auto_prefix_enabled,
                    'auto_suffix' => array_key_exists('auto_suffix', $data) ? $data['auto_suffix'] : $value->auto_suffix,
                    'auto_suffix_enabled' => array_key_exists('auto_suffix_enabled', $data) ? $data['auto_suffix_enabled'] : $value->auto_suffix_enabled,
                    'position' => $data['position'] ?? $value->position,
                    'is_active' => $data['is_active'] ?? $value->is_active,
                ]);

                Log::info('AttributeValueService::updateAttributeValue COMPLETED', [
                    'value_id' => $value->id,
                ]);

                return $value->fresh();

            } catch (\Exception $e) {
                Log::error('AttributeValueService::updateAttributeValue FAILED', [
                    'value_id' => $value->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete attribute value
     *
     * @param AttributeValue $value Attribute value to delete
     * @return bool Success status
     * @throws \RuntimeException If variants using this value
     * @throws \Exception On database error
     */
    public function deleteAttributeValue(AttributeValue $value): bool
    {
        return DB::transaction(function () use ($value) {
            Log::info('AttributeValueService::deleteAttributeValue CALLED', [
                'value_id' => $value->id,
                'code' => $value->code,
            ]);

            try {
                // Check variants using this value
                $variants = $this->getVariantsUsingAttributeValue($value->id);

                if ($variants->count() > 0) {
                    throw new \RuntimeException(
                        "Cannot delete attribute value '{$value->label}' - used by {$variants->count()} variants"
                    );
                }

                // Delete value
                $result = $value->delete();

                Log::info('AttributeValueService::deleteAttributeValue COMPLETED', [
                    'value_id' => $value->id,
                ]);

                return $result;

            } catch (\Exception $e) {
                Log::error('AttributeValueService::deleteAttributeValue FAILED', [
                    'value_id' => $value->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Reorder attribute values (drag & drop)
     *
     * @param int $typeId Attribute type ID
     * @param array $valueIdsOrdered Array of value IDs in new order
     * @return bool Success status
     * @throws \Exception On database error
     */
    public function reorderAttributeValues(int $typeId, array $valueIdsOrdered): bool
    {
        return DB::transaction(function () use ($typeId, $valueIdsOrdered) {
            Log::info('AttributeValueService::reorderAttributeValues CALLED', [
                'type_id' => $typeId,
                'values_count' => count($valueIdsOrdered),
            ]);

            try {
                foreach ($valueIdsOrdered as $index => $valueId) {
                    AttributeValue::where('id', $valueId)
                        ->where('attribute_type_id', $typeId)
                        ->update(['position' => $index + 1]); // Position starts at 1
                }

                Log::info('AttributeValueService::reorderAttributeValues COMPLETED', [
                    'type_id' => $typeId,
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('AttributeValueService::reorderAttributeValues FAILED', [
                    'type_id' => $typeId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get variants using this attribute value
     *
     * @param int $valueId Attribute value ID
     * @return Collection Collection of variants with product data
     */
    public function getVariantsUsingAttributeValue(int $valueId): Collection
    {
        return VariantAttribute::where('value_id', $valueId)
            ->with(['variant.product:id,sku,name', 'variant:id,product_id,sku,name'])
            ->get()
            ->map(function ($variantAttr) {
                return [
                    'id' => $variantAttr->variant->id,
                    'sku' => $variantAttr->variant->sku,
                    'name' => $variantAttr->variant->name,
                    'product_sku' => $variantAttr->variant->product->sku,
                    'product_name' => $variantAttr->variant->product->name,
                ];
            });
    }
}
