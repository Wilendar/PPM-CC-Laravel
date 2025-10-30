<?php

namespace App\Services\Product;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Support\Collection;

/**
 * AttributeManager Facade
 *
 * Delegation facade for AttributeType and AttributeValue operations
 *
 * REFACTORED: Split 499 lines → ~100 lines (CLAUDE.md compliance)
 * Delegates to specialized services:
 * - AttributeTypeService: AttributeType CRUD + product usage
 * - AttributeValueService: AttributeValue CRUD + reorder
 * - AttributeUsageService: Product/variant usage tracking
 *
 * USAGE:
 * ```php
 * $manager = app(AttributeManager::class);
 *
 * // Create attribute type (delegates to AttributeTypeService)
 * $type = $manager->createAttributeType([...]);
 *
 * // Create attribute value (delegates to AttributeValueService)
 * $value = $manager->createAttributeValue($typeId, [...]);
 * ```
 *
 * @package App\Services\Product
 * @version 2.0
 * @since ETAP_05b Phase 2.1 (2025-10-24)
 */
class AttributeManager
{
    protected AttributeTypeService $typeService;
    protected AttributeValueService $valueService;
    protected AttributeUsageService $usageService;

    public function __construct(
        AttributeTypeService $typeService,
        AttributeValueService $valueService,
        AttributeUsageService $usageService
    ) {
        $this->typeService = $typeService;
        $this->valueService = $valueService;
        $this->usageService = $usageService;
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE TYPE OPERATIONS (delegates to AttributeTypeService)
    |--------------------------------------------------------------------------
    */

    /**
     * Create new attribute type
     *
     * @param array $data Attribute type data
     * @return AttributeType
     * @throws \Exception
     */
    public function createAttributeType(array $data): AttributeType
    {
        return $this->typeService->createAttributeType($data);
    }

    /**
     * Update existing attribute type
     *
     * @param AttributeType $type
     * @param array $data
     * @return AttributeType
     * @throws \Exception
     */
    public function updateAttributeType(AttributeType $type, array $data): AttributeType
    {
        return $this->typeService->updateAttributeType($type, $data);
    }

    /**
     * Delete attribute type
     *
     * @param AttributeType $type
     * @param bool $force Force delete even if products using it
     * @return bool
     * @throws \Exception
     */
    public function deleteAttributeType(AttributeType $type, bool $force = false): bool
    {
        return $this->typeService->deleteAttributeType($type, $force);
    }

    /**
     * Get products using this attribute type
     *
     * @param int $typeId
     * @return Collection
     */
    public function getProductsUsingAttributeType(int $typeId): Collection
    {
        return $this->usageService->getProductsUsingAttributeType($typeId);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE VALUE OPERATIONS (delegates to AttributeValueService)
    |--------------------------------------------------------------------------
    */

    /**
     * Create new attribute value
     *
     * @param int $typeId
     * @param array $data
     * @return AttributeValue
     * @throws \Exception
     */
    public function createAttributeValue(int $typeId, array $data): AttributeValue
    {
        return $this->valueService->createAttributeValue($typeId, $data);
    }

    /**
     * Update existing attribute value
     *
     * @param AttributeValue $value
     * @param array $data
     * @return AttributeValue
     * @throws \Exception
     */
    public function updateAttributeValue(AttributeValue $value, array $data): AttributeValue
    {
        return $this->valueService->updateAttributeValue($value, $data);
    }

    /**
     * Delete attribute value
     *
     * @param AttributeValue $value
     * @return bool
     * @throws \Exception
     */
    public function deleteAttributeValue(AttributeValue $value): bool
    {
        return $this->valueService->deleteAttributeValue($value);
    }

    /**
     * Reorder attribute values (drag & drop)
     *
     * @param int $typeId
     * @param array $valueIdsOrdered
     * @return bool
     * @throws \Exception
     */
    public function reorderAttributeValues(int $typeId, array $valueIdsOrdered): bool
    {
        return $this->valueService->reorderAttributeValues($typeId, $valueIdsOrdered);
    }

    /**
     * Get variants using this attribute value
     *
     * @param int $valueId
     * @return Collection
     */
    public function getVariantsUsingAttributeValue(int $valueId): Collection
    {
        return $this->usageService->getVariantsUsingAttributeValue($valueId);
    }

    /**
     * Get products using this attribute value
     *
     * @param int $valueId
     * @return Collection
     */
    public function getProductsUsingAttributeValue(int $valueId): Collection
    {
        return $this->usageService->getProductsUsingAttributeValue($valueId);
    }
}
