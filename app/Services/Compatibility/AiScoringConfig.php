<?php

namespace App\Services\Compatibility;

use App\Models\SystemSetting;

/**
 * AiScoringConfig Service
 *
 * Proxy service for Smart Matching AI scoring configuration.
 * Reads configuration from SystemSetting (key-value store),
 * falls back to sensible defaults when not configured.
 *
 * IMPORTANT: Key names MUST NOT contain 'password', 'secret', 'key', 'token'
 * substrings - SystemSetting::shouldEncrypt() would encrypt them.
 * That's why we use 'tkn' instead of 'token' in key names.
 */
class AiScoringConfig
{
    protected const CATEGORY = 'smart_matching';

    /**
     * Configurable parameters with default values.
     *
     * Key naming convention: snake_case, prefixed by purpose group.
     * 'tkn' used instead of 'token' to avoid SystemSetting encryption trigger.
     */
    protected const DEFAULTS = [
        // Thresholds
        'min_confidence_threshold' => 0.50,
        'auto_apply_threshold' => 0.90,
        'max_suggestions_per_product' => 50,

        // Layer 0: Vehicle Type Match bonus
        // Applied when product name contains vehicle type keyword AND vehicle matches that type
        'weight_type_match' => 0.35,

        // Layer 2: Model Detection (VehicleModelDetector)
        'weight_model_alias_exact' => 0.60,
        'weight_model_alias_sku' => 0.45,
        'weight_model_tkn_match' => 0.40,
        'weight_model_tkn_ngram' => 0.35,
        'min_tkn_length' => 3,
        'min_tkn_matches' => 2,

        // Layer 3: Brand Detection (BrandDetector) - supplementary only!
        // Brand alone MUST NOT exceed min_confidence_threshold.
        'weight_brand_manufacturer_exact' => 0.15,
        'weight_brand_name_contains' => 0.10,
        'weight_brand_sku_contains' => 0.10,

        // Layer 4-5: Description/Category
        'weight_description_match' => 0.10,
        'weight_category_match' => 0.10,
    ];

    /**
     * Get a single config value.
     *
     * @throws \InvalidArgumentException If key is not defined in DEFAULTS
     */
    public function get(string $configKey): float|int
    {
        if (!array_key_exists($configKey, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Unknown AI scoring config key: {$configKey}");
        }

        $value = SystemSetting::get(
            "smart_matching.{$configKey}",
            self::DEFAULTS[$configKey]
        );

        return is_float(self::DEFAULTS[$configKey])
            ? (float) $value
            : (int) $value;
    }

    /**
     * Set a single config value.
     *
     * @throws \InvalidArgumentException If key is not defined in DEFAULTS
     */
    public function set(string $configKey, float|int $value): void
    {
        if (!array_key_exists($configKey, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Unknown AI scoring config key: {$configKey}");
        }

        SystemSetting::set(
            "smart_matching.{$configKey}",
            (string) $value,
            self::CATEGORY,
            is_float(self::DEFAULTS[$configKey]) ? 'string' : 'integer',
            $this->getDescription($configKey)
        );
    }

    /**
     * Get all current values (DB value or default fallback).
     *
     * @return array<string, float|int>
     */
    public function all(): array
    {
        $result = [];
        foreach (self::DEFAULTS as $configKey => $default) {
            $result[$configKey] = $this->get($configKey);
        }

        return $result;
    }

    /**
     * Reset all values to defaults (removes DB overrides).
     */
    public function resetToDefaults(): void
    {
        foreach (self::DEFAULTS as $configKey => $default) {
            SystemSetting::where('key', "smart_matching.{$configKey}")->delete();
        }
    }

    /**
     * Get default values map.
     *
     * @return array<string, float|int>
     */
    public function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Field definitions grouped for UI rendering.
     * Each field has label, min, max, step and optional description.
     *
     * @return array<string, array{label: string, fields: array}>
     */
    public function getFieldDefinitions(): array
    {
        return [
            'thresholds' => [
                'label' => 'Progi decyzyjne',
                'fields' => [
                    'min_confidence_threshold' => [
                        'label' => 'Minimalny prog pewnosci',
                        'min' => 0.10,
                        'max' => 0.90,
                        'step' => 0.05,
                        'description' => 'Sugestie ponizej tego progu sa odrzucane',
                    ],
                    'auto_apply_threshold' => [
                        'label' => 'Prog auto-zatwierdzania',
                        'min' => 0.50,
                        'max' => 1.00,
                        'step' => 0.05,
                        'description' => 'Sugestie powyzej tego progu sa zatwierdzane automatycznie',
                    ],
                    'max_suggestions_per_product' => [
                        'label' => 'Max sugestii per produkt',
                        'min' => 5,
                        'max' => 200,
                        'step' => 5,
                        'description' => 'Ograniczenie liczby sugestii na produkt',
                    ],
                ],
            ],
            'type_detection' => [
                'label' => 'Detekcja typu pojazdu (Layer 0)',
                'fields' => [
                    'weight_type_match' => [
                        'label' => 'Bonus dopasowania typu',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                        'description' => 'Bonus gdy typ pojazdu w nazwie produktu pasuje do typu pojazdu (np. buggy, quad)',
                    ],
                ],
            ],
            'model_detection' => [
                'label' => 'Detekcja modelu (Layer 2)',
                'fields' => [
                    'weight_model_alias_exact' => [
                        'label' => 'Alias dokladny w nazwie',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'weight_model_alias_sku' => [
                        'label' => 'Alias w SKU',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'weight_model_tkn_match' => [
                        'label' => 'Dopasowanie tokenowe',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'weight_model_tkn_ngram' => [
                        'label' => 'N-gram tokenowy',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'min_tkn_length' => [
                        'label' => 'Min dlugosc wyrazu',
                        'min' => 2,
                        'max' => 10,
                        'step' => 1,
                    ],
                    'min_tkn_matches' => [
                        'label' => 'Min pasujacych wyrazow',
                        'min' => 1,
                        'max' => 5,
                        'step' => 1,
                    ],
                ],
            ],
            'brand_detection' => [
                'label' => 'Detekcja marki (Layer 3)',
                'fields' => [
                    'weight_brand_manufacturer_exact' => [
                        'label' => 'Producent dokladny',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'weight_brand_name_contains' => [
                        'label' => 'Marka w nazwie',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                    'weight_brand_sku_contains' => [
                        'label' => 'Marka w SKU',
                        'min' => 0.0,
                        'max' => 1.0,
                        'step' => 0.05,
                    ],
                ],
            ],
            'supplementary' => [
                'label' => 'Sygnaly uzupelniajace (Layer 4-5)',
                'fields' => [
                    'weight_description_match' => [
                        'label' => 'Dopasowanie opisu',
                        'min' => 0.0,
                        'max' => 0.50,
                        'step' => 0.05,
                    ],
                    'weight_category_match' => [
                        'label' => 'Dopasowanie kategorii',
                        'min' => 0.0,
                        'max' => 0.50,
                        'step' => 0.05,
                    ],
                ],
            ],
        ];
    }

    /**
     * Default vehicle type definitions for Smart Matching type filter.
     * Each type has: key, label (display), prefix (vehicle name prefix), keywords (search in product name).
     */
    protected const DEFAULT_VEHICLE_TYPES = [
        ['key' => 'buggy', 'label' => 'Buggy', 'prefix' => 'buggy', 'keywords' => ['buggy']],
        ['key' => 'dirt_bike', 'label' => 'Dirt Bike', 'prefix' => 'dirt bike', 'keywords' => ['dirt bike', 'dirt-bike', 'dirtbike']],
        ['key' => 'pit_bike', 'label' => 'Pit Bike', 'prefix' => 'pit bike', 'keywords' => ['pit bike', 'pit-bike', 'pitbike']],
        ['key' => 'quad', 'label' => 'Quad', 'prefix' => 'quad', 'keywords' => ['quad', 'atv']],
        ['key' => 'mini_gp', 'label' => 'Mini GP', 'prefix' => 'mini gp', 'keywords' => ['mini gp', 'mini-gp', 'minigp']],
    ];

    /**
     * Get vehicle type definitions (from DB or defaults).
     *
     * @return array<array{key: string, label: string, prefix: string, keywords: string[]}>
     */
    public function getVehicleTypes(): array
    {
        $json = SystemSetting::get('smart_matching.vehicle_types', null);

        if ($json === null) {
            return self::DEFAULT_VEHICLE_TYPES;
        }

        $types = is_string($json) ? json_decode($json, true) : $json;

        return is_array($types) ? $types : self::DEFAULT_VEHICLE_TYPES;
    }

    /**
     * Save vehicle type definitions to DB.
     */
    public function setVehicleTypes(array $types): void
    {
        SystemSetting::set(
            'smart_matching.vehicle_types',
            json_encode($types, JSON_UNESCAPED_UNICODE),
            self::CATEGORY,
            'json',
            'Definicje typow pojazdow dla filtra Smart Matching'
        );
    }

    /**
     * Get default vehicle type definitions.
     */
    public function getDefaultVehicleTypes(): array
    {
        return self::DEFAULT_VEHICLE_TYPES;
    }

    /**
     * Reset vehicle types to defaults.
     */
    public function resetVehicleTypesToDefaults(): void
    {
        SystemSetting::where('key', 'smart_matching.vehicle_types')->delete();
    }

    /**
     * Build keyword map from vehicle types (for SmartSuggestionEngine).
     *
     * @return array<string, string[]> e.g. ['buggy' => ['buggy'], 'quad' => ['quad', 'atv']]
     */
    public function getVehicleTypeKeywords(): array
    {
        $map = [];
        foreach ($this->getVehicleTypes() as $type) {
            $map[$type['key']] = $type['keywords'];
        }
        return $map;
    }

    /**
     * Build prefix map from vehicle types (for SmartSuggestionEngine).
     *
     * @return array<string, string> e.g. ['buggy' => 'buggy', 'dirt_bike' => 'dirt bike']
     */
    public function getVehicleTypePrefixes(): array
    {
        $map = [];
        foreach ($this->getVehicleTypes() as $type) {
            $map[$type['key']] = $type['prefix'];
        }
        return $map;
    }

    /**
     * Get human-readable description for a config key.
     */
    protected function getDescription(string $configKey): string
    {
        $definitions = $this->getFieldDefinitions();

        foreach ($definitions as $group) {
            if (isset($group['fields'][$configKey]['description'])) {
                return $group['fields'][$configKey]['description'];
            }
            if (isset($group['fields'][$configKey]['label'])) {
                return $group['fields'][$configKey]['label'];
            }
        }

        return "Smart Matching AI: {$configKey}";
    }
}
