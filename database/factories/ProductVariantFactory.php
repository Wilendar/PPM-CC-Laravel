<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ProductVariantFactory - Factory dla generowania test data wariantów produktów
 * 
 * Enterprise-grade factory dla ProductVariant model:
 * - Master-Variant relationship generation
 * - Realistic variant names i SKUs
 * - Inheritance patterns (prices, stock, attributes)
 * - Business logic compliant generation
 * 
 * Usage:
 * ProductVariant::factory()->create() - pojedynczy wariant
 * ProductVariant::factory()->forProduct($productId)->create() - wariant dla produktu
 * ProductVariant::factory()->withOwnPrices()->create() - wariant z własnymi cenami
 * ProductVariant::factory()->clothingSize()->create() - wariant rozmiaru odzieży
 * ProductVariant::factory()->colorVariant()->create() - wariant kolorystyczny
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 * @package Database\Factories
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class ProductVariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     * 
     * Generates realistic variant data dla PPM-CC-Laravel:
     * - Master-variant SKU relationship
     * - Sensible inheritance defaults
     * - Realistic variant names
     * - Proper sort ordering
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random master product or create one if none exists
        $masterProduct = Product::where('is_variant_master', true)->inRandomOrder()->first();
        
        if (!$masterProduct) {
            $masterProduct = Product::factory()->withVariants()->create();
        }

        $variantName = $this->generateVariantName($masterProduct->product_type);
        $variantSku = $this->generateVariantSKU($masterProduct->sku);

        return [
            // === MASTER-VARIANT RELATIONSHIP ===
            'product_id' => $masterProduct->id,

            // === VARIANT IDENTIFICATION ===
            'variant_sku' => $variantSku,
            'variant_name' => $variantName,
            'ean' => $this->faker->optional(0.3)->ean13(),
            'sort_order' => $this->faker->numberBetween(0, 50),

            // === INHERITANCE CONTROL ===
            // Sensible defaults based on business logic
            'inherit_prices' => $this->faker->boolean(70), // 70% inherit prices
            'inherit_stock' => $this->faker->boolean(20), // 20% inherit stock (most have own)
            'inherit_attributes' => $this->faker->boolean(80), // 80% inherit attributes

            // === STATUS & VISIBILITY ===
            'is_active' => $this->faker->boolean(85), // 85% active variants
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Specific Variant Types
    |--------------------------------------------------------------------------
    */

    /**
     * Create variant for specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            if (!$product->is_variant_master) {
                // Auto-mark product as variant master
                $product->update(['is_variant_master' => true]);
            }

            return [
                'product_id' => $product->id,
                'variant_sku' => $this->generateVariantSKU($product->sku),
                'variant_name' => $this->generateVariantName($product->product_type),
            ];
        });
    }

    /**
     * Create clothing size variants (XS, S, M, L, XL, XXL)
     */
    public function clothingSize(): static
    {
        return $this->state(function (array $attributes) {
            $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '46', '48', '50', '52', '54', '56'];
            $size = $this->faker->randomElement($sizes);

            return [
                'variant_name' => "Rozmiar {$size}",
                'variant_sku' => 'SIZE' . $size,
                'inherit_prices' => true, // Sizes usually inherit prices
                'inherit_stock' => false, // But have own stock
                'inherit_attributes' => true,
                'sort_order' => array_search($size, $sizes) ?: 0,
            ];
        });
    }

    /**
     * Create color variants
     */
    public function colorVariant(): static
    {
        return $this->state(function (array $attributes) {
            $colors = [
                'Czarny' => 'BLK',
                'Biały' => 'WHT', 
                'Czerwony' => 'RED',
                'Niebieski' => 'BLU',
                'Zielony' => 'GRN',
                'Żółty' => 'YEL',
                'Szary' => 'GRY',
                'Pomarańczowy' => 'ORG',
                'Fioletowy' => 'PUR',
                'Brązowy' => 'BRN'
            ];

            $colorName = $this->faker->randomElement(array_keys($colors));
            $colorCode = $colors[$colorName];

            return [
                'variant_name' => $colorName,
                'variant_sku' => $colorCode,
                'inherit_prices' => true, // Colors usually inherit prices
                'inherit_stock' => false, // But have own stock
                'inherit_attributes' => true,
            ];
        });
    }

    /**
     * Create engine capacity variants (for vehicles)
     */
    public function engineCapacity(): static
    {
        return $this->state(function (array $attributes) {
            $capacities = ['50cc', '125cc', '150cc', '200cc', '250cc', '300cc', '400cc', '450cc', '500cc', '650cc'];
            $capacity = $this->faker->randomElement($capacities);

            return [
                'variant_name' => $capacity,
                'variant_sku' => str_replace('cc', 'CC', $capacity),
                'inherit_prices' => false, // Different engines = different prices
                'inherit_stock' => false, // Different stock
                'inherit_attributes' => false, // Different specs
            ];
        });
    }

    /**
     * Create variants with own prices (not inherited)
     */
    public function withOwnPrices(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'inherit_prices' => false,
                'inherit_stock' => $this->faker->boolean(50), // 50/50 on stock
                'inherit_attributes' => $this->faker->boolean(70), // Usually inherit attributes
            ];
        });
    }

    /**
     * Create variants with own stock (not inherited)
     */
    public function withOwnStock(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'inherit_prices' => $this->faker->boolean(80), // Usually inherit prices
                'inherit_stock' => false,
                'inherit_attributes' => $this->faker->boolean(70),
            ];
        });
    }

    /**
     * Create completely independent variants (no inheritance)
     */
    public function independent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'inherit_prices' => false,
                'inherit_stock' => false,
                'inherit_attributes' => false,
            ];
        });
    }

    /**
     * Create active variants only
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Create inactive variants
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Data Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate variant SKU based on master product SKU
     */
    private function generateVariantSKU(string $masterSku): string
    {
        $suffixes = [
            'V1', 'V2', 'V3', 'A', 'B', 'C', 'X', 'Y', 'Z',
            'BLK', 'WHT', 'RED', 'BLU', 'GRN', 'S', 'M', 'L', 'XL'
        ];

        $suffix = $this->faker->randomElement($suffixes);
        
        // Ensure uniqueness by adding random number if needed
        $baseSku = $suffix;
        $counter = 1;
        
        while (ProductVariant::where('variant_sku', $baseSku)->exists()) {
            $baseSku = $suffix . $counter;
            $counter++;
        }

        return $baseSku;
    }

    /**
     * Generate realistic variant name based on product type
     */
    private function generateVariantName(string $productType): string
    {
        switch ($productType) {
            case 'vehicle':
                $variants = [
                    '50cc Standard', '125cc Sport', '250cc Pro', '400cc Max',
                    'Elektryczny', 'Automatik', 'Manual', 'Sport Edition',
                    'Touring', 'Off-road', 'Street'
                ];
                break;

            case 'clothing':
                $variants = [
                    'Rozmiar XS', 'Rozmiar S', 'Rozmiar M', 'Rozmiar L', 'Rozmiar XL', 'Rozmiar XXL',
                    'Czarny', 'Biały', 'Czerwony', 'Niebieski', 'Zielony',
                    'Damski', 'Męski', 'Unisex', 'Dziecięcy'
                ];
                break;

            case 'spare_part':
                $variants = [
                    'Oryginalny', 'Zamiennik', 'Premium', 'Standard', 'Heavy Duty',
                    'Lewy', 'Prawy', 'Przedni', 'Tylny', 'Górny', 'Dolny',
                    'Komplet', 'Pojedynczy', 'Zestaw', 'Kit'
                ];
                break;

            case 'other':
            default:
                $variants = [
                    'Standardowy', 'Premium', 'Pro', 'Max', 'Plus',
                    'Mały', 'Średni', 'Duży', 'XL', 'Kompaktowy',
                    'Uniwersalny', 'Specjalny', 'Limited Edition'
                ];
                break;
        }

        return $this->faker->randomElement($variants);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ProductVariant $variant) {
            // Ensure master product is marked as variant master
            $product = $variant->product;
            if ($product && !$product->is_variant_master) {
                $product->update(['is_variant_master' => true]);
            }

            // Auto-set sort order based on existing variants count
            if (!$variant->sort_order) {
                $existingCount = ProductVariant::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->count();
                
                $variant->sort_order = $existingCount + 1;
                $variant->save();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPER METHODS - Bulk Creation
    |--------------------------------------------------------------------------
    */

    /**
     * Create multiple variants for a product
     * 
     * Usage: ProductVariantFactory::createVariantsForProduct($product, ['S', 'M', 'L'])
     */
    public static function createVariantsForProduct(Product $product, array $variantNames): array
    {
        if (!$product->is_variant_master) {
            $product->update(['is_variant_master' => true]);
        }

        $variants = [];
        $sortOrder = 1;

        foreach ($variantNames as $variantName) {
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'variant_name' => $variantName,
                'variant_sku' => strtoupper(substr($variantName, 0, 3)),
                'sort_order' => $sortOrder,
            ]);

            $variants[] = $variant;
            $sortOrder++;
        }

        return $variants;
    }

    /**
     * Create clothing size variants for a product
     */
    public static function createClothingSizes(Product $product): array
    {
        return static::createVariantsForProduct($product, [
            'XS', 'S', 'M', 'L', 'XL', 'XXL'
        ]);
    }

    /**
     * Create color variants for a product
     */
    public static function createColorVariants(Product $product): array
    {
        return static::createVariantsForProduct($product, [
            'Czarny', 'Biały', 'Czerwony', 'Niebieski', 'Zielony'
        ]);
    }

    /**
     * Create engine capacity variants for a vehicle
     */
    public static function createEngineVariants(Product $product): array
    {
        return static::createVariantsForProduct($product, [
            '125cc', '250cc', '400cc', '500cc'
        ]);
    }
}