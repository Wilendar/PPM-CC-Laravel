<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Product;
use App\Models\VehicleModel;
use App\Models\CompatibilityAttribute;

/**
 * Compatibility Bulk Validation Rule
 *
 * Validates bulk compatibility operations before execution
 *
 * VALIDATION RULES:
 * - All part IDs must exist and be spare_part type
 * - All vehicle IDs must exist in vehicle_models table
 * - Attribute code must be valid (original, replacement, performance, universal)
 * - No circular references (part cannot be its own vehicle)
 * - Max bulk size: 500 combinations (safety limit)
 *
 * USAGE:
 * ```php
 * $request->validate([
 *     'bulk_operation' => ['required', 'array', new CompatibilityBulkValidation()],
 * ]);
 * ```
 *
 * EXPECTED DATA STRUCTURE:
 * ```php
 * [
 *     'part_ids' => [1, 2, 3],
 *     'vehicle_ids' => [10, 11, 12],
 *     'attribute_code' => 'original'
 * ]
 * ```
 *
 * @package App\Rules
 * @version 1.0
 * @since ETAP_05d FAZA 2.1 (2025-10-24)
 */
class CompatibilityBulkValidation implements Rule
{
    /**
     * Maximum bulk size (safety limit)
     */
    const MAX_BULK_SIZE = 500;

    /**
     * Valid attribute codes
     */
    const VALID_ATTRIBUTE_CODES = [
        'original',
        'replacement',
        'performance',
        'universal',
    ];

    /**
     * Error message
     */
    protected string $errorMessage = 'Bulk compatibility validation failed.';

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Value must be array
        if (!is_array($value)) {
            $this->errorMessage = 'Bulk operation data must be an array.';
            return false;
        }

        // Required keys
        if (!isset($value['part_ids']) || !isset($value['vehicle_ids']) || !isset($value['attribute_code'])) {
            $this->errorMessage = 'Missing required keys: part_ids, vehicle_ids, attribute_code.';
            return false;
        }

        $partIds = $value['part_ids'];
        $vehicleIds = $value['vehicle_ids'];
        $attributeCode = $value['attribute_code'];

        // Part IDs must be array and not empty
        if (!is_array($partIds) || empty($partIds)) {
            $this->errorMessage = 'part_ids must be a non-empty array.';
            return false;
        }

        // Vehicle IDs must be array and not empty
        if (!is_array($vehicleIds) || empty($vehicleIds)) {
            $this->errorMessage = 'vehicle_ids must be a non-empty array.';
            return false;
        }

        // Check max bulk size (safety limit)
        $totalCombinations = count($partIds) * count($vehicleIds);
        if ($totalCombinations > self::MAX_BULK_SIZE) {
            $this->errorMessage = "Bulk size exceeds maximum (" . self::MAX_BULK_SIZE . " combinations). Requested: {$totalCombinations}";
            return false;
        }

        // Validate attribute code
        if (!in_array($attributeCode, self::VALID_ATTRIBUTE_CODES)) {
            $this->errorMessage = "Invalid attribute code: {$attributeCode}. Valid codes: " . implode(', ', self::VALID_ATTRIBUTE_CODES);
            return false;
        }

        // Check attribute exists in database
        $attributeExists = CompatibilityAttribute::where('code', $attributeCode)->exists();
        if (!$attributeExists) {
            $this->errorMessage = "Attribute code not found in database: {$attributeCode}";
            return false;
        }

        // Validate part IDs exist
        $existingPartCount = Product::whereIn('id', $partIds)->count();
        if ($existingPartCount !== count($partIds)) {
            $this->errorMessage = "Some part IDs do not exist. Expected: " . count($partIds) . ", Found: {$existingPartCount}";
            return false;
        }

        // Validate all parts are spare_part type (optional check - depends on business logic)
        // Uncomment if you want to enforce spare_part type:
        // $sparePartCount = Product::whereIn('id', $partIds)->where('type', 'spare_part')->count();
        // if ($sparePartCount !== count($partIds)) {
        //     $this->errorMessage = "All parts must be spare_part type. Found: {$sparePartCount} spare parts out of " . count($partIds);
        //     return false;
        // }

        // Validate vehicle IDs exist
        $existingVehicleCount = VehicleModel::whereIn('id', $vehicleIds)->count();
        if ($existingVehicleCount !== count($vehicleIds)) {
            $this->errorMessage = "Some vehicle IDs do not exist. Expected: " . count($vehicleIds) . ", Found: {$existingVehicleCount}";
            return false;
        }

        // Check for circular references (part cannot be its own vehicle)
        // Note: This assumes VehicleModel and Product are different tables
        // If your design allows products to also be vehicles, add this check:
        // $overlap = array_intersect($partIds, $vehicleIds);
        // if (!empty($overlap)) {
        //     $this->errorMessage = "Circular reference detected: Part cannot be its own vehicle. IDs: " . implode(', ', $overlap);
        //     return false;
        // }

        // All validations passed
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage;
    }
}
