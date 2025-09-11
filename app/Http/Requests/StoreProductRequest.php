<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreProductRequest - Validation dla tworzenia nowych produktów
 * 
 * Enterprise validation rules dla Product model:
 * - SKU uniqueness i format validation
 * - Business rules compliance (max lengths, required fields)
 * - Security sanitization
 * - Performance optimized validation
 * 
 * @package App\Http\Requests
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization: Handle w Controller lub Policy classes
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/policies
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Enterprise validation rules based on business requirements:
     * - SKU: Required, unique, format validation
     * - Descriptions: Max length enforcement  
     * - Dimensions: Numeric validation with proper precision
     * - Business logic: Product type validation, manufacturer cleanup
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // === CORE PRODUCT IDENTITY ===
            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:products,sku',
                'regex:/^[A-Z0-9\-_]+$/', // Only uppercase letters, numbers, hyphens, underscores
            ],
            'name' => [
                'required',
                'string',
                'max:500',
                'min:3',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:500',
                'unique:products,slug',
                'regex:/^[a-z0-9\-]+$/', // URL-safe characters only
            ],

            // === PRODUCT DESCRIPTIONS ===
            'short_description' => [
                'nullable',
                'string',
                'max:800', // Business rule: max 800 characters
            ],
            'long_description' => [
                'nullable',
                'string',
                'max:21844', // Business rule: max 21844 characters (MySQL TEXT limit)
            ],

            // === PRODUCT CLASSIFICATION ===
            'product_type' => [
                'required',
                'string',
                Rule::in(['vehicle', 'spare_part', 'clothing', 'other']),
            ],
            'manufacturer' => [
                'nullable',
                'string',
                'max:200',
            ],
            'supplier_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            // === PHYSICAL PROPERTIES ===
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999', // 8,3 decimal places
            ],
            'height' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99', // 8,2 decimal places
            ],
            'width' => [
                'nullable', 
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'length' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'ean' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/', // Only numeric characters
            ],
            'tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100', // Percentage validation
            ],

            // === PRODUCT STATUS ===
            'is_active' => [
                'boolean',
            ],
            'is_variant_master' => [
                'boolean',
            ],
            'sort_order' => [
                'integer',
                'min:0',
            ],

            // === SEO METADATA ===
            'meta_title' => [
                'nullable',
                'string', 
                'max:300',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:300',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * 
     * User-friendly field names dla error messages
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
     * Custom validation messages w języku polskim
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
     * 
     * Security: Input sanitization i normalization
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // SKU normalization: trim + uppercase
            'sku' => strtoupper(trim($this->sku ?? '')),
            
            // Name cleanup: trim + title case
            'name' => trim($this->name ?? ''),
            
            // Manufacturer cleanup
            'manufacturer' => $this->manufacturer ? trim($this->manufacturer) : null,
            
            // Supplier code normalization
            'supplier_code' => $this->supplier_code ? strtoupper(trim($this->supplier_code)) : null,
            
            // EAN cleanup
            'ean' => $this->ean ? preg_replace('/[^0-9]/', '', $this->ean) : null,
            
            // Boolean normalization
            'is_active' => $this->boolean('is_active', true), // Default to active
            'is_variant_master' => $this->boolean('is_variant_master', false),
            
            // Integer normalization
            'sort_order' => $this->integer('sort_order', 0),
            
            // Tax rate default
            'tax_rate' => $this->filled('tax_rate') ? $this->tax_rate : 23.00,
        ]);
    }

    /**
     * Configure the validator instance.
     * 
     * Advanced validation logic i custom rules
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom business logic validation
            
            // Check if variant master has meaningful name
            if ($this->boolean('is_variant_master') && strlen($this->name) < 5) {
                $validator->errors()->add(
                    'name', 
                    'Produkty z wariantami powinny mieć opisową nazwę (minimum 5 znaków).'
                );
            }
            
            // Validate EAN checksum if provided (basic validation)
            if ($this->filled('ean') && !$this->isValidEAN($this->ean)) {
                $validator->errors()->add(
                    'ean',
                    'Kod EAN ma nieprawidłowy format lub sumę kontrolną.'
                );
            }
            
            // Validate dimensions consistency
            if ($this->hasPhysicalDimensions() && !$this->hasWeight()) {
                $validator->errors()->add(
                    'weight',
                    'Produkty z wymiarami powinny mieć również wagę.'
                );
            }
        });
    }

    /**
     * Check if EAN is valid (basic validation)
     * 
     * Business Logic: Basic EAN-13/EAN-8 validation
     */
    private function isValidEAN(?string $ean): bool
    {
        if (!$ean) return true; // Null/empty is valid
        
        // Remove any non-numeric characters
        $ean = preg_replace('/[^0-9]/', '', $ean);
        
        // Check length (EAN-8 or EAN-13)
        if (!in_array(strlen($ean), [8, 13])) {
            return false;
        }
        
        // Basic checksum validation for EAN-13
        if (strlen($ean) === 13) {
            $checksum = 0;
            for ($i = 0; $i < 12; $i++) {
                $checksum += (int)$ean[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            $calculatedCheck = (10 - ($checksum % 10)) % 10;
            return $calculatedCheck == (int)$ean[12];
        }
        
        // For EAN-8 or other formats, just check numeric format
        return true;
    }

    /**
     * Check if product has physical dimensions
     */
    private function hasPhysicalDimensions(): bool
    {
        return $this->filled('height') || $this->filled('width') || $this->filled('length');
    }

    /**
     * Check if product has weight specified
     */
    private function hasWeight(): bool
    {
        return $this->filled('weight') && $this->weight > 0;
    }
}