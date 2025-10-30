<?php

namespace App\Services\CSV;

use App\Models\AttributeType;
use App\Models\FeatureType;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * CSV Import Validator Service
 *
 * Validates CSV import data with detailed error messages.
 * Supports SKU-first validation pattern.
 *
 * Features:
 * - Field-level validation rules
 * - SKU existence checks
 * - Related entity validation (attribute types, feature types, etc.)
 * - Duplicate detection
 * - Row-level error tracking
 */
class ImportValidator
{
    /**
     * Validate single row of imported data
     *
     * @param array $rowData Mapped row data
     * @param string $importType Import type (variants, features, compatibility)
     * @param int $rowNumber Row number in CSV (for error messages)
     * @return array [bool $isValid, array $errors]
     */
    public function validateRow(array $rowData, string $importType, int $rowNumber): array
    {
        $rules = $this->getValidationRules($importType, $rowData);
        $messages = $this->getCustomMessages();

        $validator = Validator::make($rowData, $rules, $messages);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }

            Log::warning('ImportValidator: Row validation failed', [
                'row_number' => $rowNumber,
                'errors' => $errors,
            ]);

            return [false, $errors];
        }

        // Additional custom validations
        $customErrors = $this->performCustomValidations($rowData, $importType, $rowNumber);

        if (!empty($customErrors)) {
            Log::warning('ImportValidator: Custom validation failed', [
                'row_number' => $rowNumber,
                'errors' => $customErrors,
            ]);

            return [false, $customErrors];
        }

        return [true, []];
    }

    /**
     * Get validation rules for import type
     *
     * @param string $importType Import type
     * @param array $rowData Row data (used for dynamic rules)
     * @return array Laravel validation rules
     */
    protected function getValidationRules(string $importType, array $rowData): array
    {
        $baseRules = $this->getBaseValidationRules();

        $typeSpecificRules = match ($importType) {
            'variants' => $this->getVariantValidationRules(),
            'features' => $this->getFeatureValidationRules(),
            'compatibility' => $this->getCompatibilityValidationRules(),
            default => [],
        };

        return array_merge($baseRules, $typeSpecificRules);
    }

    /**
     * Get base validation rules (common across all import types)
     *
     * @return array
     */
    protected function getBaseValidationRules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Get variant-specific validation rules
     *
     * @return array
     */
    protected function getVariantValidationRules(): array
    {
        return [
            'parent_sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get feature-specific validation rules
     *
     * @return array
     */
    protected function getFeatureValidationRules(): array
    {
        return [
            // Features are validated dynamically based on feature types
        ];
    }

    /**
     * Get compatibility-specific validation rules
     *
     * @return array
     */
    protected function getCompatibilityValidationRules(): array
    {
        return [
            'vehicle_brand' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:255'],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vehicle_sku' => ['nullable', 'string', 'max:100'],
            'compatibility_type' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
            'is_verified' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    protected function getCustomMessages(): array
    {
        return [
            'required' => 'Pole :attribute jest wymagane.',
            'string' => 'Pole :attribute musi być tekstem.',
            'integer' => 'Pole :attribute musi być liczbą całkowitą.',
            'numeric' => 'Pole :attribute musi być liczbą.',
            'boolean' => 'Pole :attribute musi być wartością TAK/NIE.',
            'max.string' => 'Pole :attribute nie może być dłuższe niż :max znaków.',
            'min.integer' => 'Pole :attribute musi być większe lub równe :min.',
            'max.integer' => 'Pole :attribute musi być mniejsze lub równe :max.',
            'exists' => 'Wybrana wartość :attribute jest nieprawidłowa.',
            'unique' => 'Wartość :attribute już istnieje w systemie.',
        ];
    }

    /**
     * Perform custom validations beyond Laravel's rules
     *
     * @param array $rowData Row data
     * @param string $importType Import type
     * @param int $rowNumber Row number
     * @return array Array of error messages
     */
    protected function performCustomValidations(array $rowData, string $importType, int $rowNumber): array
    {
        $errors = [];

        // SKU existence validation (SKU-first pattern)
        if (isset($rowData['sku'])) {
            $errors = array_merge($errors, $this->validateSku($rowData['sku'], $importType));
        }

        // Parent SKU validation for variants
        if ($importType === 'variants' && isset($rowData['parent_sku'])) {
            $errors = array_merge($errors, $this->validateParentSku($rowData['parent_sku']));
        }

        // Year range validation
        if (isset($rowData['year_from']) && isset($rowData['year_to'])) {
            if ($rowData['year_to'] < $rowData['year_from']) {
                $errors[] = 'Rok do nie może być wcześniejszy niż rok od.';
            }
        }

        // Vehicle SKU validation
        if (isset($rowData['vehicle_sku']) && !empty($rowData['vehicle_sku'])) {
            $errors = array_merge($errors, $this->validateVehicleSku($rowData['vehicle_sku']));
        }

        // Dynamic column validations (attributes, features, prices, stock)
        foreach ($rowData as $field => $value) {
            if (str_starts_with($field, 'attribute:')) {
                $errors = array_merge($errors, $this->validateAttributeField($field, $value));
            } elseif (str_starts_with($field, 'feature:')) {
                $errors = array_merge($errors, $this->validateFeatureField($field, $value));
            } elseif (str_starts_with($field, 'price:')) {
                $errors = array_merge($errors, $this->validatePriceField($field, $value));
            } elseif (str_starts_with($field, 'stock:')) {
                $errors = array_merge($errors, $this->validateStockField($field, $value));
            }
        }

        return $errors;
    }

    /**
     * Validate SKU existence based on import type
     *
     * @param string $sku
     * @param string $importType
     * @return array Error messages
     */
    protected function validateSku(string $sku, string $importType): array
    {
        $errors = [];

        if ($importType === 'variants') {
            // For variants: SKU should be unique among variants
            $existingVariant = ProductVariant::where('sku', $sku)->first();
            if ($existingVariant) {
                $errors[] = "SKU '{$sku}' już istnieje jako wariant (ID: {$existingVariant->id}).";
            }
        } elseif (in_array($importType, ['features', 'compatibility'])) {
            // For features/compatibility: SKU must exist in products or variants table
            $productExists = Product::where('sku', $sku)->exists();
            $variantExists = ProductVariant::where('sku', $sku)->exists();

            if (!$productExists && !$variantExists) {
                $errors[] = "SKU '{$sku}' nie istnieje w systemie. Najpierw zaimportuj produkt/wariant.";
            }
        }

        return $errors;
    }

    /**
     * Validate parent SKU existence
     *
     * @param string $parentSku
     * @return array Error messages
     */
    protected function validateParentSku(string $parentSku): array
    {
        $errors = [];

        $parentProduct = Product::where('sku', $parentSku)->first();
        if (!$parentProduct) {
            $errors[] = "Rodzic SKU '{$parentSku}' nie istnieje w systemie.";
        }

        return $errors;
    }

    /**
     * Validate vehicle SKU existence
     *
     * @param string $vehicleSku
     * @return array Error messages
     */
    protected function validateVehicleSku(string $vehicleSku): array
    {
        $errors = [];

        $vehicle = VehicleModel::where('sku', $vehicleSku)->first();
        if (!$vehicle) {
            $errors[] = "SKU pojazdu '{$vehicleSku}' nie istnieje w systemie.";
        }

        return $errors;
    }

    /**
     * Validate attribute field
     *
     * @param string $field Field name (e.g., "attribute:rozmiar")
     * @param mixed $value Field value
     * @return array Error messages
     */
    protected function validateAttributeField(string $field, $value): array
    {
        $errors = [];

        if ($value === null || $value === '') {
            return $errors; // Empty attributes are allowed
        }

        // Extract attribute slug from field name
        $attributeSlug = str_replace('attribute:', '', $field);

        // Find attribute type by code or name
        $attributeType = AttributeType::where('code', $attributeSlug)->first();
        if (!$attributeType) {
            // Try by normalized name
            $attributeType = AttributeType::active()
                ->get()
                ->first(function ($type) use ($attributeSlug) {
                    return Str::slug($type->name) === $attributeSlug;
                });
        }

        if (!$attributeType) {
            $errors[] = "Typ atrybutu '{$attributeSlug}' nie istnieje w systemie.";
        }

        return $errors;
    }

    /**
     * Validate feature field
     *
     * @param string $field Field name (e.g., "feature:moc")
     * @param mixed $value Field value
     * @return array Error messages
     */
    protected function validateFeatureField(string $field, $value): array
    {
        $errors = [];

        if ($value === null || $value === '') {
            return $errors; // Empty features are allowed
        }

        // Extract feature slug from field name
        $featureSlug = str_replace('feature:', '', $field);

        // Find feature type by code or name
        $featureType = FeatureType::where('code', $featureSlug)->first();
        if (!$featureType) {
            // Try by normalized name
            $featureType = FeatureType::active()
                ->get()
                ->first(function ($type) use ($featureSlug) {
                    return Str::slug($type->name) === $featureSlug;
                });
        }

        if (!$featureType) {
            $errors[] = "Typ cechy '{$featureSlug}' nie istnieje w systemie.";
            return $errors;
        }

        // Validate value type
        if ($featureType->value_type === FeatureType::VALUE_TYPE_NUMBER) {
            if (!is_numeric($value)) {
                $errors[] = "Cecha '{$featureType->name}' wymaga wartości liczbowej (otrzymano: '{$value}').";
            }
        } elseif ($featureType->value_type === FeatureType::VALUE_TYPE_BOOL) {
            $valid = in_array(strtoupper(trim((string) $value)), ['TAK', 'NIE', 'YES', 'NO', 'TRUE', 'FALSE', '1', '0']);
            if (!$valid) {
                $errors[] = "Cecha '{$featureType->name}' wymaga wartości TAK/NIE (otrzymano: '{$value}').";
            }
        }

        return $errors;
    }

    /**
     * Validate price field
     *
     * @param string $field Field name (e.g., "price:detaliczna")
     * @param mixed $value Field value
     * @return array Error messages
     */
    protected function validatePriceField(string $field, $value): array
    {
        $errors = [];

        if ($value === null || $value === '') {
            return $errors; // Empty prices are allowed
        }

        // Validate numeric
        if (!is_numeric($value)) {
            $errors[] = "Cena musi być wartością liczbową (otrzymano: '{$value}').";
            return $errors;
        }

        // Validate positive
        if ($value < 0) {
            $errors[] = "Cena nie może być ujemna (otrzymano: {$value}).";
        }

        // Extract price group slug from field name
        $priceGroupSlug = str_replace('price:', '', $field);

        // Find price group by code or name
        $priceGroup = PriceGroup::where('code', $priceGroupSlug)->first();
        if (!$priceGroup) {
            // Try by normalized name
            $priceGroup = PriceGroup::active()
                ->get()
                ->first(function ($group) use ($priceGroupSlug) {
                    return Str::slug($group->name) === $priceGroupSlug;
                });
        }

        if (!$priceGroup) {
            $errors[] = "Grupa cenowa '{$priceGroupSlug}' nie istnieje w systemie.";
        }

        return $errors;
    }

    /**
     * Validate stock field
     *
     * @param string $field Field name (e.g., "stock:mpptrade")
     * @param mixed $value Field value
     * @return array Error messages
     */
    protected function validateStockField(string $field, $value): array
    {
        $errors = [];

        if ($value === null || $value === '') {
            return $errors; // Empty stock is allowed
        }

        // Validate integer
        if (!is_int($value) && !ctype_digit((string) $value)) {
            $errors[] = "Stan magazynowy musi być liczbą całkowitą (otrzymano: '{$value}').";
            return $errors;
        }

        // Validate non-negative
        if ((int) $value < 0) {
            $errors[] = "Stan magazynowy nie może być ujemny (otrzymano: {$value}).";
        }

        // Extract warehouse slug from field name
        $warehouseSlug = str_replace('stock:', '', $field);

        // Find warehouse by code or name
        $warehouse = Warehouse::where('code', $warehouseSlug)->first();
        if (!$warehouse) {
            // Try by normalized name
            $warehouse = Warehouse::active()
                ->get()
                ->first(function ($wh) use ($warehouseSlug) {
                    return Str::slug($wh->name) === $warehouseSlug;
                });
        }

        if (!$warehouse) {
            $errors[] = "Magazyn '{$warehouseSlug}' nie istnieje w systemie.";
        }

        return $errors;
    }

    /**
     * Validate entire CSV data before import
     *
     * @param array $csvData Array of mapped rows
     * @param string $importType Import type
     * @return array [bool $isValid, array $allErrors]
     */
    public function validateCsvData(array $csvData, string $importType): array
    {
        Log::info('ImportValidator: Validating entire CSV', [
            'import_type' => $importType,
            'row_count' => count($csvData),
        ]);

        $allErrors = [];
        $validRowsCount = 0;

        foreach ($csvData as $rowNumber => $rowData) {
            [$isValid, $errors] = $this->validateRow($rowData, $importType, $rowNumber);

            if (!$isValid) {
                $allErrors[$rowNumber] = $errors;
            } else {
                $validRowsCount++;
            }
        }

        $isValid = empty($allErrors);

        Log::info('ImportValidator: CSV validation completed', [
            'is_valid' => $isValid,
            'valid_rows' => $validRowsCount,
            'error_rows' => count($allErrors),
        ]);

        return [$isValid, $allErrors];
    }
}
