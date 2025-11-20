<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * UniqueSKU Validation Rule
 *
 * Validates SKU uniqueness across both products and product_variants tables.
 *
 * VALIDATION RULES:
 * - SKU must be unique in products table
 * - SKU must be unique in product_variants table
 * - Case-insensitive comparison
 * - Ignore current record during update
 *
 * USAGE:
 * ```php
 * // For new product/variant
 * $request->validate([
 *     'sku' => ['required', new UniqueSKU()],
 * ]);
 *
 * // For updating existing product
 * $request->validate([
 *     'sku' => ['required', new UniqueSKU($productId)],
 * ]);
 *
 * // For updating existing variant
 * $request->validate([
 *     'sku' => ['required', new UniqueSKU(null, $variantId)],
 * ]);
 * ```
 *
 * @package App\Rules
 * @version 1.0
 * @since ETAP_05b Phase 6 (2025-10-30)
 */
class UniqueSKU implements Rule
{
    /**
     * Product ID to ignore during validation (for updates)
     *
     * @var int|null
     */
    protected ?int $ignoreProductId;

    /**
     * Variant ID to ignore during validation (for updates)
     *
     * @var int|null
     */
    protected ?int $ignoreVariantId;

    /**
     * The SKU value being validated (for error message)
     *
     * @var string
     */
    protected string $skuValue = '';

    /**
     * Create a new rule instance.
     *
     * @param int|null $ignoreProductId Product ID to ignore (for product updates)
     * @param int|null $ignoreVariantId Variant ID to ignore (for variant updates)
     */
    public function __construct(?int $ignoreProductId = null, ?int $ignoreVariantId = null)
    {
        $this->ignoreProductId = $ignoreProductId;
        $this->ignoreVariantId = $ignoreVariantId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Store value for error message
        $this->skuValue = (string) $value;

        // Empty SKU is handled by 'required' rule
        if (empty($value)) {
            return true;
        }

        // Check products table
        $productExists = DB::table('products')
            ->whereRaw('LOWER(sku) = ?', [strtolower($value)])
            ->when($this->ignoreProductId, fn($query) => $query->where('id', '!=', $this->ignoreProductId))
            ->exists();

        if ($productExists) {
            return false;
        }

        // Check product_variants table
        $variantExists = DB::table('product_variants')
            ->whereRaw('LOWER(sku) = ?', [strtolower($value)])
            ->when($this->ignoreVariantId, fn($query) => $query->where('id', '!=', $this->ignoreVariantId))
            ->exists();

        return !$variantExists;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "SKU '{$this->skuValue}' jest już używane przez inny produkt lub wariant.";
    }
}
