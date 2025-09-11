<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ProductFactory - Factory dla generowania test data produktów
 * 
 * Enterprise-grade factory dla Product model:
 * - Realistyczne dane biznesowe (SKU, nazwy, wymiary)
 * - Różne typy produktów (vehicle, spare_part, clothing, other)
 * - SEO-friendly slugs i metadata
 * - Performance-optimized dla bulk generation
 * 
 * Usage:
 * Product::factory()->create() - pojedynczy produkt
 * Product::factory()->count(100)->create() - 100 produktów
 * Product::factory()->vehicle()->create() - produkt typu vehicle
 * Product::factory()->withVariants()->create() - produkt z wariantami
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 * @package Database\Factories
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     * 
     * Generates realistic product data dla PPM-CC-Laravel:
     * - SKU format: 3-4 letters + 4-6 digits
     * - Realistic product names dla automotive industry
     * - Proper dimensions i weights
     * - SEO-optimized slugs
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productTypes = ['vehicle', 'spare_part', 'clothing', 'other'];
        $manufacturers = [
            'YAMAHA', 'HONDA', 'SUZUKI', 'KAWASAKI', 'KTM', 'HUSQVARNA',
            'POLARIS', 'CAN-AM', 'ARCTIC CAT', 'BOMBARDIER', 'PIAGGIO',
            'APRILIA', 'DUCATI', 'BMW', 'HARLEY-DAVIDSON', 'TRIUMPH'
        ];

        $name = $this->generateProductName();
        $sku = $this->generateSKU();

        return [
            // === PRIMARY IDENTITY ===
            'sku' => $sku,
            'slug' => Str::slug($name) . '-' . strtolower(substr($sku, -4)),
            
            // === BASIC PRODUCT INFO ===
            'name' => $name,
            'short_description' => $this->faker->optional(0.8)->sentence(
                $this->faker->numberBetween(8, 15)
            ),
            'long_description' => $this->faker->optional(0.6)->paragraphs(
                $this->faker->numberBetween(2, 4),
                true
            ),
            
            // === PRODUCT CLASSIFICATION ===
            'product_type' => $this->faker->randomElement($productTypes),
            'manufacturer' => $this->faker->optional(0.7)->randomElement($manufacturers),
            'supplier_code' => $this->faker->optional(0.6)->regexify('[A-Z]{2,3}[0-9]{3,5}'),
            
            // === PHYSICAL PROPERTIES ===
            'weight' => $this->faker->optional(0.8)->randomFloat(2, 0.01, 50.00),
            'height' => $this->faker->optional(0.7)->randomFloat(2, 1.0, 200.0),
            'width' => $this->faker->optional(0.7)->randomFloat(2, 1.0, 150.0),
            'length' => $this->faker->optional(0.7)->randomFloat(2, 1.0, 300.0),
            'ean' => $this->faker->optional(0.4)->ean13(),
            'tax_rate' => $this->faker->randomElement([23.00, 8.00, 5.00, 0.00]),
            
            // === PRODUCT STATUS & VARIANTS ===
            'is_active' => $this->faker->boolean(85), // 85% active
            'is_variant_master' => $this->faker->boolean(20), // 20% have variants
            'sort_order' => $this->faker->numberBetween(0, 1000),
            
            // === SEO METADATA ===
            'meta_title' => $this->faker->optional(0.5)->sentence(
                $this->faker->numberBetween(4, 8)
            ),
            'meta_description' => $this->faker->optional(0.4)->sentence(
                $this->faker->numberBetween(10, 20)
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Specific Product Types
    |--------------------------------------------------------------------------
    */

    /**
     * Create vehicle-type products
     */
    public function vehicle(): static
    {
        return $this->state(function (array $attributes) {
            $vehicleNames = [
                'Quad ATV 250cc', 'Motocykl Cross 125cc', 'Skuter 50cc',
                'Buggy 150cc', 'Pit Bike 140cc', 'Mini Quad 50cc',
                'ATV Utility 400cc', 'Dirt Bike 250cc', 'Go-Kart 200cc'
            ];

            return [
                'product_type' => 'vehicle',
                'name' => $this->faker->randomElement($vehicleNames) . ' ' . $this->faker->year(),
                'weight' => $this->faker->randomFloat(2, 50.0, 300.0),
                'is_variant_master' => $this->faker->boolean(40), // Vehicles often have variants
                'tax_rate' => 23.00, // Standard VAT for vehicles
            ];
        });
    }

    /**
     * Create spare-part-type products
     */
    public function sparePart(): static
    {
        return $this->state(function (array $attributes) {
            $partNames = [
                'Filtr powietrza', 'Świeca zapłonowa', 'Łańcuch napędowy',
                'Klocki hamulcowe', 'Opona przednia', 'Opona tylna',
                'Amortyzator przedni', 'Amortyzator tylny', 'Tłok silnika',
                'Uszczelka głowicy', 'Sprzęgło kompletne', 'Zestaw naprawczy'
            ];

            return [
                'product_type' => 'spare_part',
                'name' => $this->faker->randomElement($partNames) . ' ' . $this->faker->regexify('[A-Z0-9]{3,6}'),
                'weight' => $this->faker->randomFloat(3, 0.01, 10.0),
                'is_variant_master' => $this->faker->boolean(30), // Some parts have variants
            ];
        });
    }

    /**
     * Create clothing-type products
     */
    public function clothing(): static
    {
        return $this->state(function (array $attributes) {
            $clothingItems = [
                'Kask motocyklowy', 'Kurtka motocyklowa', 'Spodnie motocyklowe',
                'Rękawice motocyklowe', 'Buty motocyklowe', 'Ochraniacze kolan',
                'Kamizelka odblaskowa', 'Kombinezon przeciwdeszczowy'
            ];

            return [
                'product_type' => 'clothing',
                'name' => $this->faker->randomElement($clothingItems) . ' ' . $this->faker->word(),
                'weight' => $this->faker->randomFloat(3, 0.1, 3.0),
                'is_variant_master' => $this->faker->boolean(80), // Clothing usually has size/color variants
                'tax_rate' => 23.00,
            ];
        });
    }

    /**
     * Create products marked as variant masters
     */
    public function withVariants(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_variant_master' => true,
            ];
        });
    }

    /**
     * Create active products only
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
     * Create inactive products only
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create products with full SEO metadata
     */
    public function withSEO(): static
    {
        return $this->state(function (array $attributes) {
            $name = $attributes['name'] ?? $this->generateProductName();
            
            return [
                'meta_title' => $name . ' - ' . $this->faker->company() . ' - Sklep motocyklowy',
                'meta_description' => $this->faker->sentence(15) . ' Dostawa w 24h. Najlepsze ceny.',
            ];
        });
    }

    /**
     * Create premium products with full data
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'short_description' => $this->faker->sentences(2, true),
                'long_description' => $this->faker->paragraphs(4, true),
                'weight' => $this->faker->randomFloat(3, 0.1, 50.0),
                'height' => $this->faker->randomFloat(2, 5.0, 100.0),
                'width' => $this->faker->randomFloat(2, 5.0, 80.0),
                'length' => $this->faker->randomFloat(2, 5.0, 150.0),
                'ean' => $this->faker->ean13(),
                'supplier_code' => $this->faker->regexify('[A-Z]{3}[0-9]{4}'),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Data Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate realistic SKU dla automotive industry
     */
    private function generateSKU(): string
    {
        $prefixes = ['YAM', 'HON', 'SUZ', 'KAW', 'KTM', 'HUS', 'POL', 'CAN', 'ARC', 'BOM'];
        $prefix = $this->faker->randomElement($prefixes);
        $number = $this->faker->numberBetween(1000, 99999);
        
        return $prefix . $number;
    }

    /**
     * Generate realistic product name dla automotive industry
     */
    private function generateProductName(): string
    {
        $adjectives = ['Original', 'Premium', 'Performance', 'Heavy Duty', 'Professional', 'Sport'];
        $partTypes = [
            'Filtr oleju', 'Świeca zapłonowa', 'Łańcuch', 'Opona', 'Amortyzator',
            'Tłok', 'Cylinder', 'Sprzęgło', 'Hamulce', 'Kierownica', 'Siodło',
            'Zbiornik paliwa', 'Wydechy', 'Płyty sprzęgła', 'Klocki hamulcowe'
        ];
        $models = ['125cc', '250cc', '400cc', '450cc', '500cc', '650cc', 'XL', 'Pro', 'Max'];

        $adjective = $this->faker->optional(0.6)->randomElement($adjectives);
        $partType = $this->faker->randomElement($partTypes);
        $model = $this->faker->optional(0.7)->randomElement($models);

        $parts = array_filter([$adjective, $partType, $model]);
        
        return implode(' ', $parts);
    }

    /**
     * Configure the model factory.
     * 
     * Laravel 12.x factory configuration
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            // Auto-create slug if not set
            if (!$product->slug) {
                $product->slug = Str::slug($product->name) . '-' . strtolower(substr($product->sku, -4));
                $product->save();
            }
        });
    }
}