<?php

namespace App\Services\PrestaShop\Transformers;

use App\Models\FeatureType;
use App\Models\ProductFeature;
use App\Models\PrestashopFeatureMapping;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Log;

/**
 * FeatureTransformer
 *
 * ETAP_07e FAZA 4.1.2 - Data transformation layer for PrestaShop Features
 *
 * Handles conversion between PPM FeatureType/ProductFeature and PrestaShop product_features format.
 * Supports multilang names and CDATA encoding for XML compatibility.
 *
 * @package App\Services\PrestaShop\Transformers
 * @version 1.0
 * @since 2025-12-03
 */
class FeatureTransformer
{
    /**
     * PrestaShop API client (injected for value lookups)
     */
    protected ?PrestaShop8Client $client = null;

    /*
    |--------------------------------------------------------------------------
    | FEATURE TYPE TRANSFORMATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Transform FeatureType to PrestaShop product_feature format
     *
     * Creates multilang structure required by PrestaShop API.
     * Uses CDATA wrapping for special characters safety.
     *
     * @param FeatureType $featureType PPM feature type
     * @param int $langId PrestaShop language ID (default: 1 = Polish)
     * @return array PrestaShop format: ['name' => [multilang], 'position' => int]
     *
     * @example Output:
     * [
     *     'name' => ['language' => [['attrs' => ['id' => '1'], 'value' => 'Moc']]],
     *     'position' => 5
     * ]
     */
    public function transformFeatureTypeToPS(FeatureType $featureType, int $langId = 1): array
    {
        // Build name with unit if applicable (e.g., "Moc (W)")
        $name = $featureType->prestashop_name ?? $featureType->name;

        // Include unit in name if present
        if ($featureType->unit && !str_contains($name, "({$featureType->unit})")) {
            $name = "{$name} ({$featureType->unit})";
        }

        return [
            'name' => $this->buildMultilangField($name, $langId),
            'position' => $featureType->position ?? 0,
        ];
    }

    /**
     * Transform PrestaShop product_feature to FeatureType data
     *
     * Extracts data from PS API response and maps to FeatureType fillable fields.
     *
     * @param array $psFeature PrestaShop feature data from API
     * @return array FeatureType fillable data (code, name, prestashop_name, etc.)
     *
     * @example Input:
     * ['id' => 5, 'name' => ['language' => [['id' => 1, 'value' => 'Moc']]], 'position' => 3]
     *
     * @example Output:
     * ['code' => 'ps_import_5', 'name' => 'Moc', 'prestashop_name' => 'Moc', ...]
     */
    public function transformPSToFeatureType(array $psFeature): array
    {
        $name = $this->extractMultilangValue($psFeature['name'] ?? []);
        $psId = $psFeature['id'] ?? null;

        // Generate unique code based on PS ID
        $code = $psId ? "ps_import_{$psId}" : 'ps_import_' . uniqid();

        // Try to detect unit from name (e.g., "Moc (W)" => unit = "W")
        $unit = $this->extractUnitFromName($name);
        $cleanName = $unit ? $this->removeUnitFromName($name, $unit) : $name;

        return [
            'code' => $code,
            'name' => $cleanName,
            'prestashop_name' => $name, // Store original PS name
            'value_type' => FeatureType::VALUE_TYPE_TEXT, // Default to text, can be adjusted
            'unit' => $unit,
            'is_active' => true,
            'position' => $psFeature['position'] ?? 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT FEATURES TRANSFORMATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Build product_features associations for PrestaShop product
     *
     * Creates associations array for product features sync.
     * Resolves PPM feature values to PS feature_value IDs via mappings.
     *
     * @param int $productId PPM Product ID
     * @param PrestaShopShop $shop Target PrestaShop shop
     * @param PrestaShop8Client $client API client for value creation
     * @return array Associations array for PS API
     *
     * @example Output:
     * [
     *     ['id' => 5, 'id_feature_value' => 123],
     *     ['id' => 8, 'id_feature_value' => 456],
     * ]
     */
    public function buildProductFeaturesAssociations(
        int $productId,
        PrestaShopShop $shop,
        PrestaShop8Client $client
    ): array {
        $this->client = $client;
        $associations = [];

        // FIX 2025-12-03: OPCJA B - Per-shop features priority
        // 1. First check ProductShopData.attribute_mappings.features (per-shop storage)
        // 2. Fall back to global product_features table

        $shopData = \App\Models\ProductShopData::where('product_id', $productId)
            ->where('shop_id', $shop->id)
            ->first();

        $attributeMappings = $shopData?->attribute_mappings ?? [];
        $perShopFeatures = $attributeMappings['features'] ?? [];

        if (!empty($perShopFeatures)) {
            // OPTION B: Use per-shop features from attribute_mappings
            Log::info('[FEATURE TRANSFORMER] Using per-shop features from attribute_mappings', [
                'product_id' => $productId,
                'shop_id' => $shop->id,
                'per_shop_features_count' => count($perShopFeatures),
            ]);

            return $this->buildAssociationsFromArray($productId, $shop, $perShopFeatures, $client);
        }

        // FALLBACK: Use global product_features table
        Log::debug('[FEATURE TRANSFORMER] No per-shop features, using global product_features', [
            'product_id' => $productId,
            'shop_id' => $shop->id,
        ]);

        // Load product features with relationships
        $productFeatures = ProductFeature::where('product_id', $productId)
            ->with(['featureType', 'featureValue'])
            ->get();

        foreach ($productFeatures as $productFeature) {
            $featureType = $productFeature->featureType;
            if (!$featureType) {
                Log::warning('[FEATURE TRANSFORMER] No FeatureType for ProductFeature', [
                    'product_feature_id' => $productFeature->id,
                    'product_id' => $productId,
                ]);
                continue;
            }

            // Get PrestaShop mapping for this feature type
            $mapping = PrestashopFeatureMapping::where('feature_type_id', $featureType->id)
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->first();

            if (!$mapping) {
                Log::debug('[FEATURE TRANSFORMER] No mapping for FeatureType', [
                    'feature_type_id' => $featureType->id,
                    'feature_name' => $featureType->name,
                    'shop_id' => $shop->id,
                ]);
                continue;
            }

            // Transform value to PS format
            $valueText = $this->transformValueToPS(
                $productFeature->getValue(),
                $featureType
            );

            if (empty($valueText)) {
                Log::debug('[FEATURE TRANSFORMER] Empty value, skipping', [
                    'feature_type_id' => $featureType->id,
                    'product_id' => $productId,
                ]);
                continue;
            }

            // Get or create PS feature value ID
            try {
                $psFeatureValueId = $client->getOrCreateProductFeatureValue(
                    $mapping->prestashop_feature_id,
                    $valueText,
                    1 // Default language ID
                );

                $associations[] = [
                    'id' => $mapping->prestashop_feature_id,
                    'id_feature_value' => $psFeatureValueId,
                ];

                Log::debug('[FEATURE TRANSFORMER] Association built', [
                    'ps_feature_id' => $mapping->prestashop_feature_id,
                    'ps_feature_value_id' => $psFeatureValueId,
                    'value_text' => $valueText,
                ]);
            } catch (\Exception $e) {
                Log::error('[FEATURE TRANSFORMER] Failed to get/create feature value', [
                    'feature_type_id' => $featureType->id,
                    'value_text' => $valueText,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FEATURE TRANSFORMER] Product associations built (from global features)', [
            'product_id' => $productId,
            'shop_id' => $shop->id,
            'associations_count' => count($associations),
        ]);

        return $associations;
    }

    /**
     * Build associations from per-shop features array (OPCJA B)
     *
     * FIX 2025-12-03: Build PrestaShop associations from per-shop features
     * stored in ProductShopData.attribute_mappings.features
     *
     * @param int $productId
     * @param PrestaShopShop $shop
     * @param array $featuresArray [feature_type_id => value, ...]
     * @param PrestaShop8Client $client
     * @return array
     */
    protected function buildAssociationsFromArray(
        int $productId,
        PrestaShopShop $shop,
        array $featuresArray,
        PrestaShop8Client $client
    ): array {
        $associations = [];

        foreach ($featuresArray as $featureTypeId => $value) {
            $featureType = FeatureType::find($featureTypeId);
            if (!$featureType) {
                Log::warning('[FEATURE TRANSFORMER] FeatureType not found for per-shop feature', [
                    'feature_type_id' => $featureTypeId,
                    'product_id' => $productId,
                    'shop_id' => $shop->id,
                ]);
                continue;
            }

            // Get PrestaShop mapping for this feature type
            $mapping = PrestashopFeatureMapping::where('feature_type_id', $featureTypeId)
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->first();

            if (!$mapping) {
                Log::debug('[FEATURE TRANSFORMER] No mapping for per-shop FeatureType', [
                    'feature_type_id' => $featureTypeId,
                    'feature_name' => $featureType->name,
                    'shop_id' => $shop->id,
                ]);
                continue;
            }

            // Transform value to PS format
            $valueText = $this->transformValueToPS($value, $featureType);

            if (empty($valueText)) {
                Log::debug('[FEATURE TRANSFORMER] Empty per-shop value, skipping', [
                    'feature_type_id' => $featureTypeId,
                    'product_id' => $productId,
                ]);
                continue;
            }

            // Get or create PS feature value ID
            try {
                $psFeatureValueId = $client->getOrCreateProductFeatureValue(
                    $mapping->prestashop_feature_id,
                    $valueText,
                    1 // Default language ID
                );

                $associations[] = [
                    'id' => $mapping->prestashop_feature_id,
                    'id_feature_value' => $psFeatureValueId,
                ];

                Log::debug('[FEATURE TRANSFORMER] Per-shop association built', [
                    'ps_feature_id' => $mapping->prestashop_feature_id,
                    'ps_feature_value_id' => $psFeatureValueId,
                    'value_text' => $valueText,
                    'source' => 'attribute_mappings',
                ]);
            } catch (\Exception $e) {
                Log::error('[FEATURE TRANSFORMER] Failed to get/create per-shop feature value', [
                    'feature_type_id' => $featureTypeId,
                    'value_text' => $valueText,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FEATURE TRANSFORMER] Product associations built (from per-shop features)', [
            'product_id' => $productId,
            'shop_id' => $shop->id,
            'associations_count' => count($associations),
            'source' => 'attribute_mappings',
        ]);

        return $associations;
    }

    /**
     * Transform product feature value to PrestaShop value string
     *
     * Handles different value types (text, number, bool, select).
     * Normalizes and formats values for PS compatibility.
     *
     * @param mixed $value PPM feature value (string, int, float, bool, null)
     * @param FeatureType $featureType Feature type for context (unit, value_type)
     * @return string PrestaShop-compatible value string
     *
     * @example:
     * - Number with unit: 1500 + unit "W" => "1500 W"
     * - Boolean: true => "Tak", false => "Nie"
     * - Text: "Czerwony" => "Czerwony"
     */
    public function transformValueToPS($value, FeatureType $featureType): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Boolean values
        if ($featureType->isBoolean()) {
            return $value ? 'Tak' : 'Nie';
        }

        // Numeric values - format with unit
        // ETAP_07e FIX: Only add unit if actual value is numeric (not text like "Nie dotyczy")
        if ($featureType->isNumeric()) {
            // Check if actual value is numeric before adding unit
            // Values like "Nie dotyczy" should NOT get unit appended
            $stringValue = trim((string) $value);
            $isActuallyNumeric = is_numeric($stringValue) ||
                                 preg_match('/^[\d\s.,]+$/', $stringValue);

            if ($isActuallyNumeric) {
                $formattedValue = $this->formatNumericValue($value);

                if ($featureType->unit) {
                    return "{$formattedValue} {$featureType->unit}";
                }

                return $formattedValue;
            }

            // Non-numeric value for numeric feature type - return as plain text
            return $stringValue;
        }

        // Text/Select values - return as string
        return trim((string) $value);
    }

    /*
    |--------------------------------------------------------------------------
    | MULTILANG HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Build multilang field structure for PrestaShop API
     *
     * PrestaShop requires multilang fields in format:
     * ['language' => [['attrs' => ['id' => '1'], 'value' => '...']]]
     *
     * @param string $value Field value
     * @param int $langId Language ID (default: 1)
     * @return array Multilang structure
     */
    public function buildMultilangField(string $value, int $langId = 1): array
    {
        return [
            'language' => [
                [
                    'attrs' => ['id' => (string) $langId],
                    'value' => $value,
                ],
            ],
        ];
    }

    /**
     * Extract value from PrestaShop multilang field
     *
     * Handles various PS response formats:
     * - Simple string: "value"
     * - Array with language: ['language' => [['id' => 1, 'value' => 'v']]]
     * - Array without structure: ['value']
     *
     * @param mixed $multilangField PS multilang field
     * @param int $preferredLangId Preferred language ID (default: 1)
     * @return string Extracted value
     */
    public function extractMultilangValue($multilangField, int $preferredLangId = 1): string
    {
        // Already a string
        if (is_string($multilangField)) {
            return $multilangField;
        }

        // Not an array - convert to string
        if (!is_array($multilangField)) {
            return (string) $multilangField;
        }

        // Standard multilang format: ['language' => [...]]
        if (isset($multilangField['language'])) {
            $languages = $multilangField['language'];

            // Single language
            if (isset($languages['id'])) {
                return $languages['value'] ?? '';
            }

            // Multiple languages array
            if (is_array($languages)) {
                foreach ($languages as $lang) {
                    if (isset($lang['id']) && (int) $lang['id'] === $preferredLangId) {
                        return $lang['value'] ?? '';
                    }
                }

                // Return first available if preferred not found
                $first = reset($languages);
                return is_array($first) ? ($first['value'] ?? '') : (string) $first;
            }
        }

        // Try direct value access
        if (isset($multilangField['value'])) {
            return $multilangField['value'];
        }

        // Return first element if array
        $first = reset($multilangField);
        return is_string($first) ? $first : '';
    }

    /*
    |--------------------------------------------------------------------------
    | VALUE NORMALIZATION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Format numeric value for display
     *
     * - Removes unnecessary decimal places (1.00 => 1)
     * - Preserves meaningful decimals (1.5 => 1.5)
     * - Handles large numbers
     *
     * @param mixed $value Numeric value
     * @return string Formatted string
     */
    protected function formatNumericValue($value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        $floatVal = (float) $value;

        // Check if it's a whole number
        if ($floatVal == (int) $floatVal) {
            return number_format($floatVal, 0, ',', ' ');
        }

        // Determine decimal places (max 3)
        $decimals = strlen(substr(strrchr((string) $floatVal, '.'), 1));
        $decimals = min($decimals, 3);

        return number_format($floatVal, $decimals, ',', ' ');
    }

    /**
     * Extract unit from feature name
     *
     * Parses names like "Moc (W)" => "W"
     *
     * @param string $name Feature name
     * @return string|null Extracted unit or null
     */
    protected function extractUnitFromName(string $name): ?string
    {
        // Match pattern: "Name (unit)" or "Name [unit]"
        if (preg_match('/\(([^)]+)\)\s*$/', $name, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/\[([^\]]+)\]\s*$/', $name, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Remove unit suffix from feature name
     *
     * "Moc (W)" => "Moc"
     *
     * @param string $name Full name
     * @param string $unit Unit to remove
     * @return string Clean name
     */
    protected function removeUnitFromName(string $name, string $unit): string
    {
        // Remove "(unit)" or "[unit]" suffix
        $name = preg_replace('/\s*\(' . preg_quote($unit, '/') . '\)\s*$/', '', $name);
        $name = preg_replace('/\s*\[' . preg_quote($unit, '/') . '\]\s*$/', '', $name);

        return trim($name);
    }

    /*
    |--------------------------------------------------------------------------
    | BATCH TRANSFORMATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Transform multiple FeatureTypes to PS format
     *
     * @param iterable $featureTypes Collection of FeatureType
     * @param int $langId Language ID
     * @return array Array of PS feature data
     */
    public function transformFeatureTypesToPS(iterable $featureTypes, int $langId = 1): array
    {
        $result = [];

        foreach ($featureTypes as $featureType) {
            $result[$featureType->id] = $this->transformFeatureTypeToPS($featureType, $langId);
        }

        return $result;
    }

    /**
     * Transform multiple PS features to FeatureType data
     *
     * @param array $psFeatures Array of PS feature data
     * @return array Array of FeatureType fillable data
     */
    public function transformPSFeaturesToFeatureTypes(array $psFeatures): array
    {
        $result = [];

        foreach ($psFeatures as $psFeature) {
            $psId = $psFeature['id'] ?? null;
            if ($psId) {
                $result[$psId] = $this->transformPSToFeatureType($psFeature);
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Validate feature value against FeatureType rules
     *
     * @param mixed $value Value to validate
     * @param FeatureType $featureType Feature type with validation rules
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateValue($value, FeatureType $featureType): array
    {
        $errors = [];

        // Required check (if validation_rules has 'required')
        $rules = $featureType->getValidationRulesArray();
        if (isset($rules['required']) && $rules['required'] && empty($value)) {
            $errors[] = "Wartosc cechy '{$featureType->name}' jest wymagana.";
        }

        // Numeric validation
        if ($featureType->isNumeric() && !empty($value)) {
            if (!is_numeric($value)) {
                $errors[] = "Wartosc cechy '{$featureType->name}' musi byc liczba.";
            } else {
                // Min/max validation
                if (isset($rules['min']) && $value < $rules['min']) {
                    $errors[] = "Wartosc cechy '{$featureType->name}' musi byc >= {$rules['min']}.";
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    $errors[] = "Wartosc cechy '{$featureType->name}' musi byc <= {$rules['max']}.";
                }
            }
        }

        // Max length for text
        if (!$featureType->isNumeric() && !$featureType->isBoolean() && !empty($value)) {
            $maxLength = $rules['max_length'] ?? 255;
            if (strlen((string) $value) > $maxLength) {
                $errors[] = "Wartosc cechy '{$featureType->name}' przekracza max dlugosc ({$maxLength}).";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
