<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ProductAttributeFactory - FAZA C: Factory dla generowania atrybutów EAV
 * 
 * Generuje realistyczne atrybuty dla systemu automotive:
 * - Automotive attributes (Model, Oryginał, Zamiennik)
 * - Different attribute types (text, select, multiselect, etc.)
 * - Validation rules i options
 * - Display groups i sorting
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAttribute>
 * @since FAZA C - Media & Relations Implementation
 */
class ProductAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Kolor',
            'Rozmiar', 
            'Materiał',
            'Waga',
            'Długość',
            'Szerokość',
            'Wysokość',
            'Marka',
            'Stan',
            'Pochodzenie',
        ]);
        
        return [
            'name' => $name,
            'code' => strtolower(str_replace(' ', '_', $name)) . '_' . $this->faker->unique()->numberBetween(1, 1000),
            'attribute_type' => $this->faker->randomElement(['text', 'number', 'boolean', 'select', 'multiselect', 'date']),
            'is_required' => $this->faker->boolean(30), // 30% required
            'is_filterable' => $this->faker->boolean(70), // 70% filterable  
            'is_variant_specific' => $this->faker->boolean(60), // 60% can vary between variants
            'sort_order' => $this->faker->numberBetween(0, 100),
            'display_group' => $this->faker->randomElement(['general', 'technical', 'compatibility', 'shipping']),
            'validation_rules' => null, // Will be set by specific states
            'options' => null, // Will be set by specific states
            'default_value' => $this->faker->optional(0.3)->word(),
            'help_text' => $this->faker->optional(0.5)->sentence(),
            'unit' => null, // Will be set by specific states
            'format_pattern' => null,
            'is_active' => true,
        ];
    }

    /**
     * Automotive Model attribute (multiselect).
     */
    public function automotiveModel(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Model',
            'code' => 'model',
            'attribute_type' => 'multiselect',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false, // Models are usually product-wide
            'sort_order' => 10,
            'display_group' => 'compatibility',
            'help_text' => 'Modele pojazdów kompatybilnych z tym produktem',
            'options' => [
                ['value' => 'yamaha_yz250f_2023', 'label' => 'Yamaha YZ250F 2023'],
                ['value' => 'yamaha_yz250f_2024', 'label' => 'Yamaha YZ250F 2024'],
                ['value' => 'honda_crf450r_2023', 'label' => 'Honda CRF450R 2023'],
                ['value' => 'honda_crf450r_2024', 'label' => 'Honda CRF450R 2024'],
                ['value' => 'ktm_450sxf_2023', 'label' => 'KTM 450 SX-F 2023'],
                ['value' => 'ktm_450sxf_2024', 'label' => 'KTM 450 SX-F 2024'],
                ['value' => 'husqvarna_fc450_2023', 'label' => 'Husqvarna FC450 2023'],
                ['value' => 'husqvarna_fc450_2024', 'label' => 'Husqvarna FC450 2024'],
            ],
        ]);
    }

    /**
     * Automotive Original part number (text).
     */
    public function automotiveOriginal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Oryginał',
            'code' => 'original',
            'attribute_type' => 'text',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false,
            'sort_order' => 11,
            'display_group' => 'compatibility',
            'help_text' => 'Numer części oryginalnej (OEM)',
            'validation_rules' => [
                'max_length' => 50,
                'pattern' => '/^[A-Z0-9\-]+$/i',
            ],
        ]);
    }

    /**
     * Automotive Replacement part number (text).
     */
    public function automotiveReplacement(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Zamiennik',
            'code' => 'replacement',
            'attribute_type' => 'text',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false,
            'sort_order' => 12,
            'display_group' => 'compatibility',
            'help_text' => 'Numery zamienników aftermarket',
            'validation_rules' => [
                'max_length' => 200,
            ],
        ]);
    }

    /**
     * Color attribute (select).
     */
    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kolor',
            'code' => 'color',
            'attribute_type' => 'select',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => true, // Colors usually vary by variant
            'sort_order' => 20,
            'display_group' => 'general',
            'help_text' => 'Kolor produktu',
            'options' => [
                ['value' => 'black', 'label' => 'Czarny'],
                ['value' => 'white', 'label' => 'Biały'],
                ['value' => 'red', 'label' => 'Czerwony'],
                ['value' => 'blue', 'label' => 'Niebieski'],
                ['value' => 'green', 'label' => 'Zielony'],
                ['value' => 'yellow', 'label' => 'Żółty'],
                ['value' => 'orange', 'label' => 'Pomarańczowy'],
                ['value' => 'silver', 'label' => 'Srebrny'],
                ['value' => 'gray', 'label' => 'Szary'],
            ],
        ]);
    }

    /**
     * Size attribute (select).
     */
    public function size(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Rozmiar',
            'code' => 'size',
            'attribute_type' => 'select',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => true,
            'sort_order' => 21,
            'display_group' => 'general',
            'help_text' => 'Rozmiar produktu',
            'options' => [
                ['value' => 'xs', 'label' => 'XS'],
                ['value' => 's', 'label' => 'S'],
                ['value' => 'm', 'label' => 'M'],
                ['value' => 'l', 'label' => 'L'],
                ['value' => 'xl', 'label' => 'XL'],
                ['value' => 'xxl', 'label' => 'XXL'],
                ['value' => 'xxxl', 'label' => 'XXXL'],
            ],
        ]);
    }

    /**
     * Material attribute (select).
     */
    public function material(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Materiał',
            'code' => 'material',
            'attribute_type' => 'select',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false,
            'sort_order' => 30,
            'display_group' => 'technical',
            'help_text' => 'Materiał wykonania',
            'options' => [
                ['value' => 'plastic', 'label' => 'Plastik'],
                ['value' => 'metal', 'label' => 'Metal'],
                ['value' => 'aluminum', 'label' => 'Aluminium'],
                ['value' => 'carbon', 'label' => 'Carbon'],
                ['value' => 'rubber', 'label' => 'Guma'],
                ['value' => 'leather', 'label' => 'Skóra'],
                ['value' => 'fabric', 'label' => 'Tkanina'],
            ],
        ]);
    }

    /**
     * Weight attribute (number with unit).
     */
    public function weight(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Waga',
            'code' => 'weight',
            'attribute_type' => 'number',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false,
            'sort_order' => 40,
            'display_group' => 'technical',
            'help_text' => 'Waga produktu',
            'unit' => 'kg',
            'validation_rules' => [
                'min_value' => 0,
                'max_value' => 1000,
            ],
        ]);
    }

    /**
     * Boolean attribute (compatibility, availability, etc.).
     */
    public function compatibility(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kompatybilny',
            'code' => 'compatible',
            'attribute_type' => 'boolean',
            'is_required' => false,
            'is_filterable' => true,
            'is_variant_specific' => false,
            'sort_order' => 50,
            'display_group' => 'compatibility',
            'help_text' => 'Czy produkt jest kompatybilny',
            'default_value' => 'true',
        ]);
    }

    /**
     * Date attribute (manufacturing date, expiry, etc.).
     */
    public function manufacturingDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Data produkcji',
            'code' => 'manufacturing_date',
            'attribute_type' => 'date',
            'is_required' => false,
            'is_filterable' => false,
            'is_variant_specific' => false,
            'sort_order' => 60,
            'display_group' => 'technical',
            'help_text' => 'Data produkcji produktu',
        ]);
    }

    /**
     * JSON attribute (complex data, specifications).
     */
    public function specifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Specyfikacja',
            'code' => 'specifications',
            'attribute_type' => 'json',
            'is_required' => false,
            'is_filterable' => false,
            'is_variant_specific' => false,
            'sort_order' => 70,
            'display_group' => 'technical',
            'help_text' => 'Szczegółowa specyfikacja techniczna',
        ]);
    }

    /**
     * Required attribute.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Non-filterable attribute.
     */
    public function notFilterable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_filterable' => false,
        ]);
    }

    /**
     * Variant-specific attribute.
     */
    public function variantSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_variant_specific' => true,
        ]);
    }

    /**
     * Inactive attribute.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory after making.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductAttribute $attribute) {
            // Set appropriate validation rules based on attribute type
            if (empty($attribute->validation_rules)) {
                $attribute->validation_rules = $this->generateValidationRules($attribute);
            }
            
            // Set default unit for numeric attributes
            if ($attribute->attribute_type === 'number' && empty($attribute->unit)) {
                $attribute->unit = $this->faker->randomElement(['kg', 'cm', 'mm', 'L', 'szt', 'g']);
            }
        });
    }

    /**
     * Generate validation rules based on attribute type.
     */
    private function generateValidationRules(ProductAttribute $attribute): ?array
    {
        return match ($attribute->attribute_type) {
            'text' => [
                'max_length' => $this->faker->randomElement([50, 100, 200, 500]),
                'min_length' => $this->faker->optional(0.3)->numberBetween(1, 10),
            ],
            'number' => [
                'min_value' => $this->faker->optional(0.5)->numberBetween(0, 10),
                'max_value' => $this->faker->randomElement([100, 1000, 10000]),
            ],
            'date' => [
                'after' => $this->faker->optional(0.3)->date(),
                'before' => $this->faker->optional(0.3)->date('+1 year'),
            ],
            default => null,
        };
    }
}