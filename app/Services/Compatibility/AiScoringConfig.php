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
        'min_confidence_threshold' => 0.40,
        'auto_apply_threshold' => 0.90,
        'max_suggestions_per_product' => 50,

        // Layer 2: Model Detection (VehicleModelDetector)
        'weight_model_alias_exact' => 0.40,
        'weight_model_alias_sku' => 0.25,
        'weight_model_tkn_match' => 0.30,
        'weight_model_tkn_ngram' => 0.35,
        'min_tkn_length' => 3,
        'min_tkn_matches' => 2,

        // Layer 3: Brand Detection (BrandDetector)
        'weight_brand_manufacturer_exact' => 0.50,
        'weight_brand_name_contains' => 0.20,
        'weight_brand_sku_contains' => 0.20,

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
