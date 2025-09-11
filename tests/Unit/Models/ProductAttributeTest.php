<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;

/**
 * ProductAttributeTest - FAZA C: Unit tests dla ProductAttribute Model
 * 
 * Testuje funkcjonalności EAV system:
 * - Attribute definitions
 * - Validation rules parsing
 * - Options management
 * - Business logic methods
 * - Query scopes
 * 
 * @package Tests\Unit\Models
 * @since FAZA C - Media & Relations Implementation
 */
class ProductAttributeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_unique_code_from_name()
    {
        $attribute = ProductAttribute::factory()->make(['code' => null, 'name' => 'Test Attribute']);
        $attribute->save();

        $this->assertStringContainsString('test_attribute', $attribute->code);
        $this->assertNotNull($attribute->code);
    }

    /** @test */
    public function it_prevents_duplicate_codes()
    {
        ProductAttribute::factory()->create(['code' => 'test_code']);
        
        $attribute2 = ProductAttribute::factory()->make(['code' => null, 'name' => 'Test Code']);
        $attribute2->save();

        $this->assertStringStartsWith('test_code_', $attribute2->code);
        $this->assertNotEquals('test_code', $attribute2->code);
    }

    /** @test */
    public function it_parses_validation_rules_correctly()
    {
        $attribute = ProductAttribute::factory()->create([
            'attribute_type' => 'text',
            'validation_rules' => [
                'min_length' => 5,
                'max_length' => 50,
                'pattern' => '/^[A-Z]+$/',
                'required' => true,
            ],
        ]);

        $rules = $attribute->validation_rules_parsed;
        
        $this->assertContains('min:5', $rules);
        $this->assertContains('max:50', $rules);
        $this->assertContains('regex:/^[A-Z]+$/', $rules);
        $this->assertContains('required', $rules);
    }

    /** @test */
    public function it_adds_type_based_validation_rules()
    {
        $numericAttribute = ProductAttribute::factory()->create(['attribute_type' => 'number']);
        $this->assertContains('numeric', $numericAttribute->validation_rules_parsed);

        $booleanAttribute = ProductAttribute::factory()->create(['attribute_type' => 'boolean']);
        $this->assertContains('boolean', $booleanAttribute->validation_rules_parsed);

        $dateAttribute = ProductAttribute::factory()->create(['attribute_type' => 'date']);
        $this->assertContains('date', $dateAttribute->validation_rules_parsed);
    }

    /** @test */
    public function it_validates_select_options_in_rules()
    {
        $attribute = ProductAttribute::factory()->create([
            'attribute_type' => 'select',
            'options' => [
                ['value' => 'red', 'label' => 'Red'],
                ['value' => 'blue', 'label' => 'Blue'],
            ],
        ]);

        $rules = $attribute->validation_rules_parsed;
        
        $this->assertContains('in:red,blue', $rules);
    }

    /** @test */
    public function it_parses_options_correctly()
    {
        $attribute = ProductAttribute::factory()->create([
            'options' => [
                ['value' => 'small', 'label' => 'Small', 'description' => 'Small size'],
                'medium', // Simple string format
                ['value' => 'large', 'label' => 'Large'],
            ],
        ]);

        $parsed = $attribute->options_parsed;
        
        $this->assertCount(3, $parsed);
        $this->assertEquals('small', $parsed[0]['value']);
        $this->assertEquals('Small', $parsed[0]['label']);
        $this->assertEquals('Small size', $parsed[0]['description']);
        
        $this->assertEquals('medium', $parsed[1]['value']);
        $this->assertEquals('medium', $parsed[1]['label']);
        
        $this->assertEquals('large', $parsed[2]['value']);
        $this->assertEquals('Large', $parsed[2]['label']);
    }

    /** @test */
    public function it_identifies_attributes_with_options()
    {
        $selectAttribute = ProductAttribute::factory()->create([
            'attribute_type' => 'select',
            'options' => [['value' => 'test', 'label' => 'Test']],
        ]);
        $this->assertTrue($selectAttribute->has_options);

        $multiselectAttribute = ProductAttribute::factory()->create([
            'attribute_type' => 'multiselect',
            'options' => [['value' => 'test', 'label' => 'Test']],
        ]);
        $this->assertTrue($multiselectAttribute->has_options);

        $textAttribute = ProductAttribute::factory()->create(['attribute_type' => 'text']);
        $this->assertFalse($textAttribute->has_options);
    }

    /** @test */
    public function it_identifies_select_types()
    {
        $selectAttribute = ProductAttribute::factory()->create(['attribute_type' => 'select']);
        $this->assertTrue($selectAttribute->is_select);
        $this->assertFalse($selectAttribute->is_multiselect);

        $multiselectAttribute = ProductAttribute::factory()->create(['attribute_type' => 'multiselect']);
        $this->assertFalse($multiselectAttribute->is_select);
        $this->assertTrue($multiselectAttribute->is_multiselect);
    }

    /** @test */
    public function it_generates_display_name_with_unit()
    {
        $attribute = ProductAttribute::factory()->create([
            'name' => 'Weight',
            'unit' => 'kg',
        ]);

        $this->assertEquals('Weight (kg)', $attribute->display_name);

        $attributeWithoutUnit = ProductAttribute::factory()->create(['name' => 'Color', 'unit' => null]);
        $this->assertEquals('Color', $attributeWithoutUnit->display_name);
    }

    /** @test */
    public function it_scopes_active_attributes()
    {
        $activeAttribute = ProductAttribute::factory()->create(['is_active' => true]);
        $inactiveAttribute = ProductAttribute::factory()->inactive()->create();

        $activeResults = ProductAttribute::active()->get();
        
        $this->assertTrue($activeResults->contains($activeAttribute));
        $this->assertFalse($activeResults->contains($inactiveAttribute));
    }

    /** @test */
    public function it_scopes_by_attribute_type()
    {
        $textAttribute = ProductAttribute::factory()->create(['attribute_type' => 'text']);
        $numberAttribute = ProductAttribute::factory()->create(['attribute_type' => 'number']);

        $textResults = ProductAttribute::byType('text')->get();
        
        $this->assertTrue($textResults->contains($textAttribute));
        $this->assertFalse($textResults->contains($numberAttribute));
    }

    /** @test */
    public function it_scopes_automotive_attributes()
    {
        $modelAttribute = ProductAttribute::factory()->automotiveModel()->create();
        $originalAttribute = ProductAttribute::factory()->automotiveOriginal()->create();
        $colorAttribute = ProductAttribute::factory()->color()->create();

        $automotiveResults = ProductAttribute::automotive()->get();
        
        $this->assertTrue($automotiveResults->contains($modelAttribute));
        $this->assertTrue($automotiveResults->contains($originalAttribute));
        $this->assertFalse($automotiveResults->contains($colorAttribute));
    }

    /** @test */
    public function it_scopes_vehicle_compatibility_attributes()
    {
        $modelAttribute = ProductAttribute::factory()->automotiveModel()->create();
        $originalAttribute = ProductAttribute::factory()->automotiveOriginal()->create();
        $replacementAttribute = ProductAttribute::factory()->automotiveReplacement()->create();
        $colorAttribute = ProductAttribute::factory()->color()->create();

        $compatibilityResults = ProductAttribute::vehicleCompatibility()->get();
        
        $this->assertTrue($compatibilityResults->contains($modelAttribute));
        $this->assertTrue($compatibilityResults->contains($originalAttribute));
        $this->assertTrue($compatibilityResults->contains($replacementAttribute));
        $this->assertFalse($compatibilityResults->contains($colorAttribute));
    }

    /** @test */
    public function it_scopes_filterable_attributes()
    {
        $filterableAttribute = ProductAttribute::factory()->create(['is_filterable' => true]);
        $nonFilterableAttribute = ProductAttribute::factory()->notFilterable()->create();

        $filterableResults = ProductAttribute::filterable()->get();
        
        $this->assertTrue($filterableResults->contains($filterableAttribute));
        $this->assertFalse($filterableResults->contains($nonFilterableAttribute));
    }

    /** @test */
    public function it_scopes_required_attributes()
    {
        $requiredAttribute = ProductAttribute::factory()->required()->create();
        $optionalAttribute = ProductAttribute::factory()->create(['is_required' => false]);

        $requiredResults = ProductAttribute::required()->get();
        
        $this->assertTrue($requiredResults->contains($requiredAttribute));
        $this->assertFalse($requiredResults->contains($optionalAttribute));
    }

    /** @test */
    public function it_scopes_by_display_group()
    {
        $generalAttribute = ProductAttribute::factory()->create(['display_group' => 'general']);
        $technicalAttribute = ProductAttribute::factory()->create(['display_group' => 'technical']);

        $generalResults = ProductAttribute::byGroup('general')->get();
        
        $this->assertTrue($generalResults->contains($generalAttribute));
        $this->assertFalse($generalResults->contains($technicalAttribute));
    }

    /** @test */
    public function it_validates_values_correctly()
    {
        $attribute = ProductAttribute::factory()->create([
            'attribute_type' => 'text',
            'validation_rules' => ['max_length' => 10],
        ]);

        $validResult = $attribute->validateValue('short');
        $this->assertTrue($validResult['valid']);
        $this->assertEmpty($validResult['errors']);

        $invalidResult = $attribute->validateValue('this is too long text');
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['errors']);
    }

    /** @test */
    public function it_can_add_options_to_select_attributes()
    {
        $attribute = ProductAttribute::factory()->color()->create();
        
        $result = $attribute->addOption('purple', 'Purple', 'A nice purple color');
        
        $this->assertTrue($result);
        
        $options = $attribute->fresh()->options_parsed;
        $purpleOption = collect($options)->firstWhere('value', 'purple');
        
        $this->assertNotNull($purpleOption);
        $this->assertEquals('Purple', $purpleOption['label']);
        $this->assertEquals('A nice purple color', $purpleOption['description']);
    }

    /** @test */
    public function it_prevents_duplicate_options()
    {
        $attribute = ProductAttribute::factory()->color()->create();
        
        $result = $attribute->addOption('black', 'Black'); // Already exists in color factory
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_remove_options()
    {
        $attribute = ProductAttribute::factory()->color()->create();
        
        $result = $attribute->removeOption('black');
        
        $this->assertTrue($result);
        
        $options = $attribute->fresh()->options_parsed;
        $blackOption = collect($options)->firstWhere('value', 'black');
        
        $this->assertNull($blackOption);
    }

    /** @test */
    public function it_can_get_values_count()
    {
        $attribute = ProductAttribute::factory()->create();
        ProductAttributeValue::factory()->count(5)->create(['attribute_id' => $attribute->id]);

        $count = $attribute->getValuesCount();
        
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_can_get_unique_values()
    {
        $attribute = ProductAttribute::factory()->create();
        
        ProductAttributeValue::factory()->textValue('value1')->create(['attribute_id' => $attribute->id]);
        ProductAttributeValue::factory()->textValue('value2')->create(['attribute_id' => $attribute->id]);
        ProductAttributeValue::factory()->textValue('value1')->create(['attribute_id' => $attribute->id]); // Duplicate

        $uniqueValues = $attribute->getUniqueValues();
        
        $this->assertCount(2, $uniqueValues);
        $this->assertTrue($uniqueValues->contains('value1'));
        $this->assertTrue($uniqueValues->contains('value2'));
    }

    /** @test */
    public function it_can_clone_attributes()
    {
        $original = ProductAttribute::factory()->color()->create(['name' => 'Original Color']);
        
        $clone = $original->cloneAttribute('Cloned Color', 'cloned_color');
        
        $this->assertNotEquals($original->id, $clone->id);
        $this->assertEquals('Cloned Color', $clone->name);
        $this->assertEquals('cloned_color', $clone->code);
        $this->assertEquals($original->attribute_type, $clone->attribute_type);
        $this->assertEquals($original->options, $clone->options);
    }

    /** @test */
    public function it_uses_code_as_route_key()
    {
        $attribute = new ProductAttribute();
        $this->assertEquals('code', $attribute->getRouteKeyName());
    }

    /** @test */
    public function it_resolves_route_binding_by_code_or_id()
    {
        $attribute = ProductAttribute::factory()->create(['code' => 'test_code']);
        
        // Test resolution by code
        $foundByCode = (new ProductAttribute())->resolveRouteBinding('test_code');
        $this->assertEquals($attribute->id, $foundByCode->id);
        
        // Test resolution by ID
        $foundById = (new ProductAttribute())->resolveRouteBinding($attribute->id);
        $this->assertEquals($attribute->id, $foundById->id);
    }

    /** @test */
    public function it_correctly_identifies_helper_methods()
    {
        $requiredAttribute = ProductAttribute::factory()->required()->create();
        $this->assertTrue($requiredAttribute->isRequired());

        $filterableAttribute = ProductAttribute::factory()->create(['is_filterable' => true]);
        $this->assertTrue($filterableAttribute->isFilterable());
    }
}