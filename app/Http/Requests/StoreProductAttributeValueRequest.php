<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductAttribute;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * StoreProductAttributeValueRequest - FAZA C: Validation dla EAV system
 * 
 * Zabezpiecza dodawanie/edycję wartości atrybutów:
 * - EAV validation (attribute existence, value type)
 * - Business rules (inheritance logic, variant constraints)
 * - Type-specific validation (text, number, boolean, date, json)
 * - Performance validation (prevent duplicate values)
 * 
 * @package App\Http\Requests
 * @since FAZA C - Media & Relations Implementation
 */
class StoreProductAttributeValueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage product attributes
        // Admin, Manager, Editor can manage attributes
        return $this->user() && in_array($this->user()->role, ['admin', 'manager', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Core relationships
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
            'attribute_id' => [
                'required',
                'integer',
                'exists:product_attributes,id',
            ],
            
            // Universal value field (will be processed based on attribute type)
            'value' => [
                'nullable', // Can be null for inherited values
            ],
            
            // Inheritance flags
            'is_inherited' => [
                'nullable',
                'boolean',
            ],
            'is_override' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'ID produktu jest wymagane.',
            'product_id.exists' => 'Wybrany produkt nie istnieje.',
            
            'product_variant_id.exists' => 'Wybrany wariant produktu nie istnieje.',
            
            'attribute_id.required' => 'ID atrybutu jest wymagane.',
            'attribute_id.exists' => 'Wybrany atrybut nie istnieje.',
            
            'value.required' => 'Wartość atrybutu jest wymagana.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produkt',
            'product_variant_id' => 'wariant produktu',
            'attribute_id' => 'atrybut',
            'value' => 'wartość',
            'is_inherited' => 'dziedziczenie',
            'is_override' => 'nadpisanie',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate product-variant relationship
            $this->validateProductVariantRelationship($validator);
            
            // Validate attribute value based on attribute type
            $this->validateAttributeValue($validator);
            
            // Validate inheritance logic
            $this->validateInheritanceLogic($validator);
            
            // Validate uniqueness (prevent duplicate attribute values)
            $this->validateUniqueness($validator);
            
            // Validate required attributes
            $this->validateRequiredAttribute($validator);
        });
    }

    /**
     * Validate product-variant relationship
     */
    private function validateProductVariantRelationship($validator): void
    {
        $productId = $this->input('product_id');
        $variantId = $this->input('product_variant_id');
        
        if ($productId && $variantId) {
            $variant = ProductVariant::find($variantId);
            
            if ($variant && $variant->product_id !== (int) $productId) {
                $validator->errors()->add('product_variant_id', 'Wariant nie należy do wybranego produktu.');
            }
        }
    }

    /**
     * Validate attribute value based on attribute type
     */
    private function validateAttributeValue($validator): void
    {
        $attributeId = $this->input('attribute_id');
        $value = $this->input('value');
        
        if ($attributeId && $value !== null) {
            $attribute = ProductAttribute::find($attributeId);
            
            if ($attribute) {
                $validation = $attribute->validateValue($value);
                
                if (!$validation['valid']) {
                    foreach ($validation['errors'] as $error) {
                        $validator->errors()->add('value', $error);
                    }
                }
                
                // Additional type-specific validation
                $this->validateValueByType($validator, $attribute, $value);
            }
        }
    }

    /**
     * Validate value by specific attribute type
     */
    private function validateValueByType($validator, ProductAttribute $attribute, $value): void
    {
        switch ($attribute->attribute_type) {
            case 'text':
                if (!is_string($value)) {
                    $validator->errors()->add('value', 'Wartość musi być tekstem.');
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    $validator->errors()->add('value', 'Wartość musi być liczbą.');
                }
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, ['0', '1', 0, 1, true, false], true)) {
                    $validator->errors()->add('value', 'Wartość musi być prawda/fałsz.');
                }
                break;
                
            case 'date':
                if (!strtotime($value)) {
                    $validator->errors()->add('value', 'Wartość musi być prawidłową datą.');
                }
                break;
                
            case 'select':
                if ($attribute->has_options) {
                    $validOptions = collect($attribute->options_parsed)->pluck('value')->toArray();
                    if (!in_array($value, $validOptions)) {
                        $validator->errors()->add('value', 'Wybrana wartość nie jest dostępną opcją.');
                    }
                }
                break;
                
            case 'multiselect':
                if (!is_array($value)) {
                    $validator->errors()->add('value', 'Wartość musi być tablicą opcji.');
                } elseif ($attribute->has_options) {
                    $validOptions = collect($attribute->options_parsed)->pluck('value')->toArray();
                    $invalidValues = array_diff($value, $validOptions);
                    
                    if (!empty($invalidValues)) {
                        $validator->errors()->add('value', 'Niektóre wybrane wartości nie są dostępnymi opcjami.');
                    }
                }
                break;
                
            case 'json':
                if (is_string($value) && json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE) {
                    $validator->errors()->add('value', 'Wartość musi być prawidłowym JSON.');
                }
                break;
        }
    }

    /**
     * Validate inheritance logic
     */
    private function validateInheritanceLogic($validator): void
    {
        $isInherited = $this->boolean('is_inherited');
        $isOverride = $this->boolean('is_override');
        $variantId = $this->input('product_variant_id');
        $value = $this->input('value');
        
        // Inheritance only makes sense for variants
        if ($isInherited && !$variantId) {
            $validator->errors()->add('is_inherited', 'Dziedziczenie jest możliwe tylko dla wariantów produktu.');
        }
        
        // Cannot be both inherited and override
        if ($isInherited && $isOverride) {
            $validator->errors()->add('is_override', 'Wartość nie może jednocześnie dziedziczyć i nadpisywać.');
        }
        
        // If inherited, value should come from master (handled in model)
        // If override, must have explicit value
        if ($isOverride && ($value === null || $value === '')) {
            $validator->errors()->add('value', 'Nadpisanie wymaga podania wartości.');
        }
    }

    /**
     * Validate uniqueness (prevent duplicate attribute values)
     */
    private function validateUniqueness($validator): void
    {
        $productId = $this->input('product_id');
        $variantId = $this->input('product_variant_id');
        $attributeId = $this->input('attribute_id');
        
        if ($productId && $attributeId) {
            $query = \App\Models\ProductAttributeValue::where('product_id', $productId)
                ->where('attribute_id', $attributeId);
                
            if ($variantId) {
                $query->where('product_variant_id', $variantId);
            } else {
                $query->whereNull('product_variant_id');
            }
            
            // If this is an update (has route parameter), exclude current record
            if ($this->route('productAttributeValue')) {
                $query->where('id', '!=', $this->route('productAttributeValue')->id);
            }
            
            if ($query->exists()) {
                $validator->errors()->add('attribute_id', 'Ten atrybut już ma przypisaną wartość dla tego produktu/wariantu.');
            }
        }
    }

    /**
     * Validate required attributes
     */
    private function validateRequiredAttribute($validator): void
    {
        $attributeId = $this->input('attribute_id');
        $value = $this->input('value');
        $isInherited = $this->boolean('is_inherited');
        
        if ($attributeId) {
            $attribute = ProductAttribute::find($attributeId);
            
            if ($attribute && $attribute->is_required) {
                // Required attribute must have value or be inherited
                if (($value === null || $value === '') && !$isInherited) {
                    $validator->errors()->add('value', 'Ten atrybut jest wymagany i musi mieć wartość.');
                }
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string boolean values
        if ($this->has('is_inherited')) {
            $this->merge([
                'is_inherited' => $this->boolean('is_inherited'),
            ]);
        }
        
        if ($this->has('is_override')) {
            $this->merge([
                'is_override' => $this->boolean('is_override'),
            ]);
        }
        
        // Handle empty string values
        if ($this->input('value') === '') {
            $this->merge([
                'value' => null,
            ]);
        }
        
        // Convert JSON string to array for multiselect
        if ($this->has('value') && is_string($this->input('value'))) {
            $attributeId = $this->input('attribute_id');
            
            if ($attributeId) {
                $attribute = ProductAttribute::find($attributeId);
                
                if ($attribute && in_array($attribute->attribute_type, ['multiselect', 'json'])) {
                    $decodedValue = json_decode($this->input('value'), true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->merge([
                            'value' => $decodedValue,
                        ]);
                    }
                }
            }
        }
    }
}