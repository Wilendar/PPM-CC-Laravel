<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreProductVariantRequest - Validation dla tworzenia wariantów produktów
 * 
 * Enterprise validation rules dla ProductVariant model:
 * - Master-Variant relationship validation
 * - SKU uniqueness i format validation
 * - Inheritance logic validation
 * - Business rules compliance
 * 
 * @package App\Http\Requests
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class StoreProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/policies
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Variant-specific validation rules:
     * - Product ID existence i variant master validation
     * - Variant SKU uniqueness across all variants
     * - Inheritance flags consistency
     * - Naming requirements
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // === MASTER-VARIANT RELATIONSHIP ===
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            // === VARIANT IDENTITY ===
            'variant_sku' => [
                'required',
                'string',
                'max:100',
                'unique:product_variants,variant_sku',
                'regex:/^[A-Z0-9\-_]+$/', // Only uppercase letters, numbers, hyphens, underscores
            ],
            'variant_name' => [
                'required',
                'string',
                'max:200',
                'min:2',
            ],
            'ean' => [
                'nullable',
                'string',
                'max:20',
                'unique:product_variants,ean', // EAN unique across variants
                'regex:/^[0-9]+$/', // Only numeric characters
            ],

            // === ORDERING ===
            'sort_order' => [
                'integer',
                'min:0',
                'max:9999',
            ],

            // === INHERITANCE CONTROL ===
            'inherit_prices' => [
                'boolean',
            ],
            'inherit_stock' => [
                'boolean',
            ],
            'inherit_attributes' => [
                'boolean',
            ],

            // === STATUS ===
            'is_active' => [
                'boolean',
            ],
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
            'product_id' => 'produkt główny',
            'variant_sku' => 'SKU wariantu',
            'variant_name' => 'nazwa wariantu',
            'ean' => 'kod EAN',
            'sort_order' => 'kolejność sortowania',
            'inherit_prices' => 'dziedziczenie cen',
            'inherit_stock' => 'dziedziczenie stanów',
            'inherit_attributes' => 'dziedziczenie atrybutów',
            'is_active' => 'status aktywności',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Produkt główny jest wymagany.',
            'product_id.exists' => 'Wybrany produkt główny nie istnieje.',
            
            'variant_sku.required' => 'SKU wariantu jest wymagane.',
            'variant_sku.unique' => 'Wariant z tym SKU już istnieje.',
            'variant_sku.regex' => 'SKU wariantu może zawierać tylko wielkie litery, cyfry, myślniki i podkreślenia.',
            'variant_sku.max' => 'SKU wariantu nie może być dłuższe niż 100 znaków.',
            
            'variant_name.required' => 'Nazwa wariantu jest wymagana.',
            'variant_name.min' => 'Nazwa wariantu musi mieć minimum 2 znaki.',
            'variant_name.max' => 'Nazwa wariantu nie może być dłuższa niż 200 znaków.',
            
            'ean.unique' => 'Kod EAN już istnieje dla innego wariantu.',
            'ean.regex' => 'Kod EAN może zawierać tylko cyfry.',
            'ean.max' => 'Kod EAN nie może być dłuższy niż 20 znaków.',
            
            'sort_order.integer' => 'Kolejność sortowania musi być liczbą całkowitą.',
            'sort_order.min' => 'Kolejność sortowania nie może być ujemna.',
            'sort_order.max' => 'Kolejność sortowania jest za duża.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Variant SKU normalization
            'variant_sku' => strtoupper(trim($this->variant_sku ?? '')),
            
            // Variant name cleanup
            'variant_name' => trim($this->variant_name ?? ''),
            
            // EAN cleanup
            'ean' => $this->ean ? preg_replace('/[^0-9]/', '', $this->ean) : null,
            
            // Boolean normalization with sensible defaults
            'inherit_prices' => $this->boolean('inherit_prices', true), // Default: inherit prices
            'inherit_stock' => $this->boolean('inherit_stock', false), // Default: own stock
            'inherit_attributes' => $this->boolean('inherit_attributes', true), // Default: inherit attributes
            'is_active' => $this->boolean('is_active', true), // Default: active
            
            // Integer normalization
            'sort_order' => $this->integer('sort_order', 0),
            'product_id' => $this->filled('product_id') ? (int)$this->product_id : null,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom variant-specific validation
            
            // Check if product is marked as variant master
            if ($this->filled('product_id')) {
                $product = \App\Models\Product::find($this->product_id);
                
                if ($product && !$product->is_variant_master) {
                    $validator->errors()->add(
                        'product_id',
                        'Wybrany produkt nie jest oznaczony jako produkt z wariantami. Najpierw oznacz produkt jako "ma warianty".'
                    );
                }
            }
            
            // Validate SKU doesn't conflict with master product SKU
            if ($this->filled('variant_sku') && $this->filled('product_id')) {
                $product = \App\Models\Product::find($this->product_id);
                
                if ($product && $this->variant_sku === $product->sku) {
                    $validator->errors()->add(
                        'variant_sku',
                        'SKU wariantu nie może być identyczne z SKU produktu głównego.'
                    );
                }
                
                // Check for conflicting full SKU format (MASTER-VARIANT)
                $fullSku = $product ? $product->sku . '-' . $this->variant_sku : null;
                if ($fullSku && \App\Models\Product::where('sku', $fullSku)->exists()) {
                    $validator->errors()->add(
                        'variant_sku',
                        'Pełne SKU (' . $fullSku . ') konflikuje z istniejącym produktem.'
                    );
                }
            }
            
            // Validate EAN checksum if provided
            if ($this->filled('ean') && !$this->isValidEAN($this->ean)) {
                $validator->errors()->add(
                    'ean',
                    'Kod EAN ma nieprawidłowy format lub sumę kontrolną.'
                );
            }
            
            // Check variant name uniqueness within same product
            if ($this->filled('variant_name') && $this->filled('product_id')) {
                $existingVariantName = \App\Models\ProductVariant::where('product_id', $this->product_id)
                    ->where('variant_name', $this->variant_name)
                    ->exists();
                
                if ($existingVariantName) {
                    $validator->errors()->add(
                        'variant_name',
                        'Wariant o tej nazwie już istnieje dla tego produktu.'
                    );
                }
            }
            
            // Business logic: Warn about inheritance consistency
            if (!$this->boolean('inherit_prices') && !$this->boolean('inherit_stock')) {
                // This is just informational - not an error
                // Could be used for UI warnings
            }
        });
    }

    /**
     * Check if EAN is valid (basic validation)
     */
    private function isValidEAN(?string $ean): bool
    {
        if (!$ean) return true;
        
        $ean = preg_replace('/[^0-9]/', '', $ean);
        
        if (!in_array(strlen($ean), [8, 13])) {
            return false;
        }
        
        if (strlen($ean) === 13) {
            $checksum = 0;
            for ($i = 0; $i < 12; $i++) {
                $checksum += (int)$ean[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            $calculatedCheck = (10 - ($checksum % 10)) % 10;
            return $calculatedCheck == (int)$ean[12];
        }
        
        return true;
    }

    /**
     * Get validated data with computed fields
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Add computed full SKU for reference
        if (!empty($validated['product_id']) && !empty($validated['variant_sku'])) {
            $product = \App\Models\Product::find($validated['product_id']);
            if ($product) {
                $validated['computed_full_sku'] = $product->sku . '-' . $validated['variant_sku'];
            }
        }
        
        return $key ? data_get($validated, $key, $default) : $validated;
    }
}