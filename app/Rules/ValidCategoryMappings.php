<?php

namespace App\Rules;

use App\Services\CategoryMappingsValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * ValidCategoryMappings - Custom Validation Rule
 *
 * Validates category_mappings Option A structure
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
 *
 * Validates:
 * - Option A structure (ui + mappings + metadata)
 * - ui.selected is array of integers
 * - ui.primary is integer and exists in selected
 * - mappings keys are string representations of ui.selected IDs
 * - mappings values are integers (PrestaShop IDs)
 * - All ui.selected IDs have corresponding mappings entries
 * - metadata.source is valid enum
 *
 * Usage:
 * ```php
 * $request->validate([
 *     'category_mappings' => ['required', 'array', new ValidCategoryMappings()],
 * ]);
 * ```
 *
 * @package App\Rules
 * @version 2.0
 * @since 2025-11-18 (Category Mappings Architecture Refactoring)
 */
class ValidCategoryMappings implements ValidationRule
{
    /**
     * @var CategoryMappingsValidator
     */
    private CategoryMappingsValidator $validator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->validator = app(CategoryMappingsValidator::class);
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute The attribute name being validated
     * @param mixed $value The value of the attribute
     * @param Closure $fail The failure callback
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Must be an array
        if (!is_array($value)) {
            $fail("The {$attribute} must be an array.");
            return;
        }

        // Empty array is valid (will be converted to empty structure)
        if (empty($value)) {
            return;
        }

        // Validate using CategoryMappingsValidator
        try {
            $this->validator->validate($value);
        } catch (InvalidArgumentException $e) {
            $fail("The {$attribute} has invalid structure: " . $e->getMessage());
        }
    }

    /**
     * Get the validation error message (legacy method for Laravel < 10)
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute has invalid category mappings structure.';
    }
}
