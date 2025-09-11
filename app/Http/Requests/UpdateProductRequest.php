<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateProductRequest - Validation dla aktualizacji produktów
 * 
 * Enterprise validation rules dla Product model updates:
 * - SKU uniqueness z wykluczeniem current record
 * - Partial updates support
 * - Business logic preservation
 * - Security sanitization
 * 
 * @package App\Http\Requests
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class UpdateProductRequest extends FormRequest
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
     * Update-specific rules:
     * - SKU uniqueness excluding current product
     * - Optional fields dla partial updates
     * - Variant master logic preservation
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->product_id;

        return [
            // === CORE PRODUCT IDENTITY ===
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
                'regex:/^[A-Z0-9\-_]+$/',
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:500',
                'min:3',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                Rule::unique('products', 'slug')->ignore($productId),
                'regex:/^[a-z0-9\-]+$/',
            ],

            // === PRODUCT DESCRIPTIONS ===
            'short_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:800',
            ],
            'long_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:21844',
            ],

            // === PRODUCT CLASSIFICATION ===
            'product_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['vehicle', 'spare_part', 'clothing', 'other']),
            ],
            'manufacturer' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
            ],
            'supplier_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            // === PHYSICAL PROPERTIES ===
            'weight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999',
            ],
            'height' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'width' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'length' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'ean' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
            ],
            'tax_rate' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            // === PRODUCT STATUS ===
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'is_variant_master' => [
                'sometimes',
                'boolean',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            // === SEO METADATA ===
            'meta_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:300',
            ],
            'meta_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:300',
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
            'sku' => 'SKU produktu',
            'name' => 'nazwa produktu',
            'slug' => 'slug URL',
            'short_description' => 'krótki opis',
            'long_description' => 'długi opis',
            'product_type' => 'typ produktu',
            'manufacturer' => 'producent',
            'supplier_code' => 'kod dostawcy',
            'weight' => 'waga',
            'height' => 'wysokość',
            'width' => 'szerokość',
            'length' => 'długość',
            'ean' => 'kod EAN',
            'tax_rate' => 'stawka VAT',
            'is_active' => 'status aktywności',
            'is_variant_master' => 'czy ma warianty',
            'sort_order' => 'kolejność sortowania',
            'meta_title' => 'tytuł SEO',
            'meta_description' => 'opis SEO',
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
            'sku.required' => 'SKU produktu jest wymagane.',
            'sku.unique' => 'Produkt z tym SKU już istnieje.',
            'sku.regex' => 'SKU może zawierać tylko wielkie litery, cyfry, myślniki i podkreślenia.',
            'sku.max' => 'SKU nie może być dłuższe niż 100 znaków.',
            
            'name.required' => 'Nazwa produktu jest wymagana.',
            'name.min' => 'Nazwa produktu musi mieć minimum 3 znaki.',
            'name.max' => 'Nazwa produktu nie może być dłuższa niż 500 znaków.',
            
            'slug.unique' => 'Slug URL już istnieje.',
            'slug.regex' => 'Slug może zawierać tylko małe litery, cyfry i myślniki.',
            
            'short_description.max' => 'Krótki opis nie może przekraczać 800 znaków.',
            'long_description.max' => 'Długi opis nie może przekraczać 21844 znaków.',
            
            'product_type.required' => 'Typ produktu jest wymagany.',
            'product_type.in' => 'Nieprawidłowy typ produktu.',
            
            'manufacturer.max' => 'Nazwa producenta nie może być dłuższa niż 200 znaków.',
            'supplier_code.max' => 'Kod dostawcy nie może być dłuższy niż 100 znaków.',
            
            'weight.numeric' => 'Waga musi być liczbą.',
            'weight.min' => 'Waga nie może być ujemna.',
            'weight.max' => 'Waga jest za duża.',
            
            'height.numeric' => 'Wysokość musi być liczbą.',
            'height.min' => 'Wysokość nie może być ujemna.',
            
            'width.numeric' => 'Szerokość musi być liczbą.',
            'width.min' => 'Szerokość nie może być ujemna.',
            
            'length.numeric' => 'Długość musi być liczbą.',
            'length.min' => 'Długość nie może być ujemna.',
            
            'ean.regex' => 'Kod EAN może zawierać tylko cyfry.',
            'ean.max' => 'Kod EAN nie może być dłuższy niż 20 znaków.',
            
            'tax_rate.required' => 'Stawka VAT jest wymagana.',
            'tax_rate.numeric' => 'Stawka VAT musi być liczbą.',
            'tax_rate.min' => 'Stawka VAT nie może być ujemna.',
            'tax_rate.max' => 'Stawka VAT nie może przekraczać 100%.',
            
            'sort_order.integer' => 'Kolejność sortowania musi być liczbą całkowitą.',
            'sort_order.min' => 'Kolejność sortowania nie może być ujemna.',
            
            'meta_title.max' => 'Tytuł SEO nie może być dłuższy niż 300 znaków.',
            'meta_description.max' => 'Opis SEO nie może być dłuższy niż 300 znaków.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Only normalize fields that are present in the request
        if ($this->has('sku')) {
            $data['sku'] = strtoupper(trim($this->sku));
        }

        if ($this->has('name')) {
            $data['name'] = trim($this->name);
        }

        if ($this->has('manufacturer')) {
            $data['manufacturer'] = $this->manufacturer ? trim($this->manufacturer) : null;
        }

        if ($this->has('supplier_code')) {
            $data['supplier_code'] = $this->supplier_code ? strtoupper(trim($this->supplier_code)) : null;
        }

        if ($this->has('ean')) {
            $data['ean'] = $this->ean ? preg_replace('/[^0-9]/', '', $this->ean) : null;
        }

        if ($this->has('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }

        if ($this->has('is_variant_master')) {
            $data['is_variant_master'] = $this->boolean('is_variant_master');
        }

        if ($this->has('sort_order')) {
            $data['sort_order'] = $this->integer('sort_order', 0);
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');

            // Prevent disabling variant master if it has active variants
            if ($product && $this->has('is_variant_master') && !$this->boolean('is_variant_master')) {
                if ($product->variants()->where('is_active', true)->exists()) {
                    $validator->errors()->add(
                        'is_variant_master',
                        'Nie można wyłączyć opcji wariantów dla produktu z aktywnymi wariantami.'
                    );
                }
            }

            // Validate EAN if provided
            if ($this->filled('ean') && !$this->isValidEAN($this->ean)) {
                $validator->errors()->add(
                    'ean',
                    'Kod EAN ma nieprawidłowy format lub sumę kontrolną.'
                );
            }

            // Warn about deactivating product with active variants
            if ($product && $this->has('is_active') && !$this->boolean('is_active')) {
                $activeVariantsCount = $product->variants()->where('is_active', true)->count();
                if ($activeVariantsCount > 0) {
                    // This could be a warning rather than error - depending on business logic
                    $validator->errors()->add(
                        'is_active',
                        "Uwaga: Dezaktywacja tego produktu wpłynie na {$activeVariantsCount} aktywnych wariantów."
                    );
                }
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
}