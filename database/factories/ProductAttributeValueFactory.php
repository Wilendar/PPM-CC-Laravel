<?php

namespace Database\Factories;

use App\Models\ProductAttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ProductAttributeValueFactory - FAZA C: Factory dla wartości EAV
 * 
 * Generuje realistyczne wartości atrybutów:
 * - Type-specific value generation (text, number, boolean, etc.)
 * - Inheritance logic (master product → variants)
 * - Automotive-specific values (models, part numbers)
 * - Validation-compliant data
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAttributeValue>
 * @since FAZA C - Media & Relations Implementation
 */
class ProductAttributeValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductAttributeValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null, // Default to master product attribute
            'attribute_id' => ProductAttribute::factory(),
            
            // Value fields (only one will be filled based on attribute type)
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_json' => null,
            
            // Inheritance logic
            'is_inherited' => false,
            'is_override' => false,
            
            // Validation
            'is_valid' => true,
            'validation_error' => null,
        ];
    }

    /**
     * Indicate that this is a text attribute value.
     */
    public function textValue(string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_text' => $value ?? $this->faker->words(3, true),
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_json' => null,
        ]);
    }

    /**
     * Indicate that this is a numeric attribute value.
     */
    public function numericValue(float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_text' => null,
            'value_number' => $value ?? $this->faker->randomFloat(2, 0, 1000),
            'value_boolean' => null,
            'value_date' => null,
            'value_json' => null,
        ]);
    }

    /**
     * Indicate that this is a boolean attribute value.
     */
    public function booleanValue(bool $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => $value ?? $this->faker->boolean(),
            'value_date' => null,
            'value_json' => null,
        ]);
    }

    /**
     * Indicate that this is a date attribute value.
     */
    public function dateValue(\DateTime $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => $value ?? $this->faker->date(),
            'value_json' => null,
        ]);
    }

    /**
     * Indicate that this is a JSON attribute value.
     */
    public function jsonValue(array $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_json' => $value ?? [
                'key1' => $this->faker->word(),
                'key2' => $this->faker->numberBetween(1, 100),
                'key3' => $this->faker->boolean(),
            ],
        ]);
    }

    /**
     * Automotive Model value (multiselect JSON).
     */
    public function automotiveModels(array $models = null): static
    {
        $defaultModels = $models ?? [
            'yamaha_yz250f_2023',
            'honda_crf450r_2023',
            'ktm_450sxf_2023',
        ];
        
        return $this->jsonValue($defaultModels);
    }

    /**
     * Automotive Original part number.
     */
    public function originalPartNumber(string $partNumber = null): static
    {
        $partNumber = $partNumber ?? strtoupper($this->faker->bothify('##?#?##'));
        return $this->textValue($partNumber);
    }

    /**
     * Automotive Replacement part numbers.
     */
    public function replacementPartNumbers(string $partNumbers = null): static
    {
        $partNumbers = $partNumbers ?? implode(', ', [
            strtoupper($this->faker->bothify('##?#?##')),
            strtoupper($this->faker->bothify('##?#?##')),
        ]);
        
        return $this->textValue($partNumbers);
    }

    /**
     * Color value.
     */
    public function colorValue(string $color = null): static
    {
        $color = $color ?? $this->faker->randomElement([
            'black', 'white', 'red', 'blue', 'green', 'yellow', 'orange'
        ]);
        
        return $this->textValue($color);
    }

    /**
     * Size value.
     */
    public function sizeValue(string $size = null): static
    {
        $size = $size ?? $this->faker->randomElement(['xs', 's', 'm', 'l', 'xl', 'xxl']);
        return $this->textValue($size);
    }

    /**
     * Material value.
     */
    public function materialValue(string $material = null): static
    {
        $material = $material ?? $this->faker->randomElement([
            'plastic', 'metal', 'aluminum', 'carbon', 'rubber'
        ]);
        
        return $this->textValue($material);
    }

    /**
     * Weight value (with reasonable range).
     */
    public function weightValue(float $weight = null): static
    {
        $weight = $weight ?? $this->faker->randomFloat(3, 0.001, 50.0); // 1g to 50kg
        return $this->numericValue($weight);
    }

    /**
     * Indicate that this value belongs to a variant.
     */
    public function forVariant(ProductVariant $variant = null): static
    {
        $variant = $variant ?? ProductVariant::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'product_id' => $variant->product_id, // Always reference master product
            'product_variant_id' => $variant->id,
        ]);
    }

    /**
     * Indicate that this value belongs to a master product.
     */
    public function forProduct(Product $product = null): static
    {
        $product = $product ?? Product::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);
    }

    /**
     * Indicate that this is an inherited value.
     */
    public function inherited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inherited' => true,
            'is_override' => false,
        ]);
    }

    /**
     * Indicate that this is an override value.
     */
    public function override(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inherited' => false,
            'is_override' => true,
        ]);
    }

    /**
     * Indicate that this value has validation errors.
     */
    public function withValidationError(string $error = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => false,
            'validation_error' => $error ?? $this->faker->randomElement([
                'Value too long',
                'Invalid format',
                'Required value missing',
                'Value out of range',
            ]),
        ]);
    }

    /**
     * Create value for specific attribute by code.
     */
    public function forAttributeCode(string $code): static
    {
        return $this->state(function (array $attributes) use ($code) {
            // Create or find attribute with specific code
            $attribute = ProductAttribute::where('code', $code)->first() 
                ?? ProductAttribute::factory()->create(['code' => $code]);
            
            $updates = ['attribute_id' => $attribute->id];
            
            // Set appropriate value based on common attribute codes
            switch ($code) {
                case 'model':
                    $updates = array_merge($updates, [
                        'value_json' => ['yamaha_yz250f_2023', 'honda_crf450r_2023'],
                        'value_text' => null,
                        'value_number' => null,
                        'value_boolean' => null,
                        'value_date' => null,
                    ]);
                    break;
                    
                case 'original':
                    $partNumber = strtoupper($this->faker->bothify('##?#?##'));
                    $updates = array_merge($updates, [
                        'value_text' => $partNumber,
                        'value_number' => null,
                        'value_boolean' => null,
                        'value_date' => null,
                        'value_json' => null,
                    ]);
                    break;
                    
                case 'color':
                    $color = $this->faker->randomElement(['black', 'white', 'red', 'blue']);
                    $updates = array_merge($updates, [
                        'value_text' => $color,
                        'value_number' => null,
                        'value_boolean' => null,
                        'value_date' => null,
                        'value_json' => null,
                    ]);
                    break;
                    
                case 'weight':
                    $weight = $this->faker->randomFloat(3, 0.1, 10.0);
                    $updates = array_merge($updates, [
                        'value_number' => $weight,
                        'value_text' => null,
                        'value_boolean' => null,
                        'value_date' => null,
                        'value_json' => null,
                    ]);
                    break;
                    
                default:
                    // Default to text value
                    $updates = array_merge($updates, [
                        'value_text' => $this->faker->words(2, true),
                        'value_number' => null,
                        'value_boolean' => null,
                        'value_date' => null,
                        'value_json' => null,
                    ]);
            }
            
            return $updates;
        });
    }

    /**
     * Create a complete set of automotive attributes for a product.
     */
    public function automotiveSet(Product $product): array
    {
        return [
            // Model compatibility
            static::factory()
                ->forProduct($product)
                ->forAttributeCode('model')
                ->automotiveModels(),
                
            // Original part number
            static::factory()
                ->forProduct($product)
                ->forAttributeCode('original')
                ->originalPartNumber(),
                
            // Replacement part numbers
            static::factory()
                ->forProduct($product)
                ->forAttributeCode('replacement')
                ->replacementPartNumbers(),
        ];
    }

    /**
     * Configure the factory after making.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductAttributeValue $attributeValue) {
            // Ensure only one value field is set based on attribute type
            if ($attributeValue->attribute) {
                $this->setValueByAttributeType($attributeValue);
            }
        });
    }

    /**
     * Set appropriate value field based on attribute type.
     */
    private function setValueByAttributeType(ProductAttributeValue $attributeValue): void
    {
        $attribute = $attributeValue->attribute;
        
        // Clear all value fields first
        $attributeValue->value_text = null;
        $attributeValue->value_number = null;
        $attributeValue->value_boolean = null;
        $attributeValue->value_date = null;
        $attributeValue->value_json = null;
        
        // Set appropriate field based on attribute type
        switch ($attribute->attribute_type) {
            case 'text':
                $attributeValue->value_text = $this->faker->words(3, true);
                break;
                
            case 'number':
                $attributeValue->value_number = $this->faker->randomFloat(2, 0, 1000);
                break;
                
            case 'boolean':
                $attributeValue->value_boolean = $this->faker->boolean();
                break;
                
            case 'date':
                $attributeValue->value_date = $this->faker->date();
                break;
                
            case 'select':
                if ($attribute->has_options) {
                    $options = collect($attribute->options_parsed)->pluck('value')->toArray();
                    $attributeValue->value_text = $this->faker->randomElement($options);
                } else {
                    $attributeValue->value_text = $this->faker->word();
                }
                break;
                
            case 'multiselect':
            case 'json':
                if ($attribute->has_options) {
                    $options = collect($attribute->options_parsed)->pluck('value')->toArray();
                    $selectedCount = $this->faker->numberBetween(1, min(3, count($options)));
                    $attributeValue->value_json = $this->faker->randomElements($options, $selectedCount);
                } else {
                    $attributeValue->value_json = [$this->faker->word(), $this->faker->word()];
                }
                break;
                
            default:
                $attributeValue->value_text = $this->faker->words(2, true);
        }
    }
}