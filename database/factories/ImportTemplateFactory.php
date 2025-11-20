<?php

namespace Database\Factories;

use App\Models\ImportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ImportTemplateFactory - Factory for generating import template test data
 *
 * Enterprise-grade factory for ImportTemplate model:
 * - Realistic column mapping configurations
 * - Shared vs private templates
 * - Popular templates with usage tracking
 * - Business logic compliant mappings
 *
 * Usage:
 * ImportTemplate::factory()->create() - single template
 * ImportTemplate::factory()->shared()->create() - shared template
 * ImportTemplate::factory()->popular()->create() - popular template (high usage)
 * ImportTemplate::factory()->variantTemplate()->create() - variant-specific template
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportTemplate>
 * @package Database\Factories
 * @version 1.0
 * @since ETAP_07 FAZA 5 - Import/Export System
 */
class ImportTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ImportTemplate::class;

    /**
     * Define the model's default state.
     *
     * Generates realistic import template data for PPM-CC-Laravel:
     * - Sensible column mappings
     * - Realistic template names
     * - Usage statistics
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $templateTypes = [
            'VARIANTS_BASIC' => [
                'A' => 'sku',
                'B' => 'name',
                'C' => 'variant.attributes.Kolor',
                'D' => 'variant.attributes.Rozmiar',
                'E' => 'price.detaliczna',
            ],
            'VARIANTS_FULL' => [
                'A' => 'sku',
                'B' => 'name',
                'C' => 'variant.attributes.Kolor',
                'D' => 'variant.attributes.Rozmiar',
                'E' => 'price.detaliczna',
                'F' => 'price.dealer_standard',
                'G' => 'stock.MPPTRADE',
                'H' => 'variant.ean',
            ],
            'PRODUCTS_SIMPLE' => [
                'A' => 'sku',
                'B' => 'name',
                'C' => 'category',
                'D' => 'price.detaliczna',
                'E' => 'stock.MPPTRADE',
            ],
            'PRESTASHOP_SYNC' => [
                'A' => 'sku',
                'B' => 'name',
                'C' => 'description',
                'D' => 'price.detaliczna',
                'E' => 'stock.MPPTRADE',
                'F' => 'category',
                'G' => 'image_url',
            ],
        ];

        $templateName = $this->faker->randomElement(array_keys($templateTypes));
        $mapping = $templateTypes[$templateName];

        return [
            'user_id' => User::factory(),
            'name' => $templateName . '_v' . $this->faker->numberBetween(1, 5),
            'description' => $this->faker->optional(0.7)->sentence(),
            'mapping_config' => $mapping,
            'is_shared' => $this->faker->boolean(30), // 30% shared
            'usage_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Sharing States
    |--------------------------------------------------------------------------
    */

    /**
     * Shared template (visible to all users)
     */
    public function shared(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => true,
                'usage_count' => $this->faker->numberBetween(10, 100), // Shared templates tend to be popular
            ];
        });
    }

    /**
     * Private template (owner only)
     */
    public function private(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => false,
                'usage_count' => $this->faker->numberBetween(0, 20),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Usage Popularity
    |--------------------------------------------------------------------------
    */

    /**
     * Popular template (high usage count)
     */
    public function popular(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'usage_count' => $this->faker->numberBetween(50, 200),
                'is_shared' => true, // Popular templates are usually shared
            ];
        });
    }

    /**
     * Unused template (never used)
     */
    public function unused(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'usage_count' => 0,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Template Types
    |--------------------------------------------------------------------------
    */

    /**
     * Variant template (variant-specific mapping)
     */
    public function variantTemplate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'VARIANTS_' . strtoupper($this->faker->word()) . '_v1',
                'mapping_config' => [
                    'A' => 'sku',
                    'B' => 'name',
                    'C' => 'variant.attributes.Kolor',
                    'D' => 'variant.attributes.Rozmiar',
                    'E' => 'variant.ean',
                    'F' => 'price.detaliczna',
                    'G' => 'price.dealer_standard',
                    'H' => 'stock.MPPTRADE',
                    'I' => 'stock.Pitbike.pl',
                ],
            ];
        });
    }

    /**
     * Product template (product-level mapping, no variants)
     */
    public function productTemplate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'PRODUCTS_' . strtoupper($this->faker->word()) . '_v1',
                'mapping_config' => [
                    'A' => 'sku',
                    'B' => 'name',
                    'C' => 'description',
                    'D' => 'category',
                    'E' => 'price.detaliczna',
                    'F' => 'stock.MPPTRADE',
                    'G' => 'ean',
                ],
            ];
        });
    }

    /**
     * PrestaShop template (PrestaShop-specific fields)
     */
    public function prestashopTemplate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'PRESTASHOP_SYNC_v' . $this->faker->numberBetween(1, 3),
                'mapping_config' => [
                    'A' => 'sku',
                    'B' => 'name',
                    'C' => 'description',
                    'D' => 'price.detaliczna',
                    'E' => 'stock.MPPTRADE',
                    'F' => 'category',
                    'G' => 'image_url',
                    'H' => 'meta_title',
                    'I' => 'meta_description',
                ],
            ];
        });
    }

    /**
     * Minimal template (only required fields)
     */
    public function minimal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'MINIMAL_TEMPLATE_v1',
                'mapping_config' => [
                    'A' => 'sku',
                    'B' => 'name',
                    'C' => 'price.detaliczna',
                ],
            ];
        });
    }
}
