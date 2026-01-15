<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * VariantAttributeTrait - Attribute Management for Product Variants
 *
 * Handles: Attribute selection, values lookup, attribute type definitions
 *
 * EXTRACTED FROM: ProductFormVariants.php (1369 lines -> 6 traits)
 * LINE COUNT TARGET: < 120 lines (CLAUDE.md compliance)
 *
 * DEPENDENCIES:
 * - VariantValidation trait (validateVariantAttributes)
 * - AttributeType, AttributeValue models
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait VariantAttributeTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Variant attributes data [attribute_type_id] => value
     *
     * Used for attribute selection in create/edit modals
     *
     * @var array
     */
    public array $variantAttributes = [];

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available attribute types for variant creation
     */
    public function getAttributeTypes(): Collection
    {
        return AttributeType::where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get attribute values for specific attribute type
     */
    public function getAttributeValues(int $attributeTypeId): Collection
    {
        return AttributeValue::where('attribute_type_id', $attributeTypeId)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('value')
            ->get();
    }

    /**
     * Load variant attributes for editing
     */
    public function loadVariantAttributes(ProductVariant $variant): void
    {
        $this->variantAttributes = [];

        foreach ($variant->attributes as $attr) {
            $this->variantAttributes[$attr->attribute_type_id] = $attr->value;
        }
    }

    /**
     * Reset variant attributes
     */
    public function resetVariantAttributes(): void
    {
        $this->variantAttributes = [];
    }

    /**
     * Set variant attribute value
     *
     * CRITICAL: This method is used instead of Livewire's $set() because
     * $set('variantAttributes.1', value) doesn't work for arrays with numeric keys.
     * Wire:click="$set('array.numericKey', value)" is buggy in Livewire 3.x.
     *
     * @param int|string $typeId Attribute type ID
     * @param int|string|null $valueId Attribute value ID
     */
    public function setVariantAttribute($typeId, $valueId): void
    {
        Log::info('[VARIANT ATTR] setVariantAttribute called', [
            'typeId' => $typeId,
            'valueId' => $valueId,
            'before' => $this->variantAttributes,
        ]);

        // Ensure numeric keys
        $typeId = (int) $typeId;
        $valueId = $valueId !== null && $valueId !== '' ? (int) $valueId : null;

        if ($valueId !== null) {
            $this->variantAttributes[$typeId] = $valueId;
        } else {
            unset($this->variantAttributes[$typeId]);
        }

        Log::info('[VARIANT ATTR] setVariantAttribute after', [
            'variantAttributes' => $this->variantAttributes,
        ]);

        // ETAP_05f: Trigger Auto SKU regeneration if method exists
        if (method_exists($this, 'onVariantAttributeChanged')) {
            $this->onVariantAttributeChanged();
        }
    }

    /**
     * Get attribute type name by ID
     */
    public function getAttributeTypeName(int $attributeTypeId): ?string
    {
        $type = AttributeType::find($attributeTypeId);
        return $type?->name;
    }

    /**
     * Get formatted attributes for variant display
     */
    public function getFormattedAttributes(ProductVariant $variant): array
    {
        $formatted = [];

        foreach ($variant->attributes as $attr) {
            $typeName = $this->getAttributeTypeName($attr->attribute_type_id);
            if ($typeName) {
                $formatted[] = [
                    'type' => $typeName,
                    'value' => $attr->value,
                    'code' => $attr->value_code,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Check if variant has specific attribute
     */
    public function variantHasAttribute(ProductVariant $variant, int $attributeTypeId): bool
    {
        return $variant->attributes()
            ->where('attribute_type_id', $attributeTypeId)
            ->exists();
    }

    /**
     * Get attribute value for variant by type
     */
    public function getVariantAttributeValue(ProductVariant $variant, int $attributeTypeId): ?string
    {
        $attr = $variant->attributes()
            ->where('attribute_type_id', $attributeTypeId)
            ->first();

        return $attr?->value;
    }
}
