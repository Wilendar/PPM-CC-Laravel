<?php

namespace App\Http\Livewire\Products\Management\Traits;

use Illuminate\Validation\ValidationException;

/**
 * ProductFormValidation Trait
 *
 * Handles validation rules and business validation for ProductForm
 * Separated from main component per CLAUDE.md guidelines
 *
 * @package App\Http\Livewire\Products\Management\Traits
 */
trait ProductFormValidation
{
    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES - Live Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Get validation rules for the form
     *
     * @return array
     */
    protected function rules(): array
    {
        $productId = $this->product?->id;

        return [
            // Basic Information
            'sku' => [
                'required',
                'string',
                'max:100',
                $this->isEditMode ? "unique:products,sku,{$productId}" : 'unique:products,sku',
                'regex:/^[A-Z0-9\-_]+$/',
            ],
            'name' => 'required|string|max:500|min:3',
            'slug' => [
                'nullable',
                'string',
                'max:500',
                $this->isEditMode ? "unique:products,slug,{$productId}" : 'unique:products,slug',
                'regex:/^[a-z0-9\-]+$/',
            ],
            'product_type_id' => 'required|exists:product_types,id',
            'manufacturer' => 'nullable|string|max:200',
            'supplier_code' => 'nullable|string|max:100',
            'ean' => 'nullable|string|max:13|regex:/^[0-9]{8,13}$/',
            'is_active' => 'boolean',
            'is_variant_master' => 'boolean',
            'sort_order' => 'integer|min:0|max:999999',

            // Descriptions & SEO
            'short_description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string|max:10000',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',

            // Physical Properties
            'weight' => 'nullable|numeric|min:0|max:999999.99',
            'height' => 'nullable|numeric|min:0|max:999999.99',
            'width' => 'nullable|numeric|min:0|max:999999.99',
            'length' => 'nullable|numeric|min:0|max:999999.99',

            // Tax Rate - Global Default (required for all products)
            'tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,2}(\.\d{1,2})?$/', // Format: XX.XX (e.g., 23.00, 8.00, 5.00, 0.00)
            ],

            // Tax Rate - Shop Overrides (optional, per shop) - FAZA 5.3
            'shopTaxRateOverrides' => 'nullable|array',
            'shopTaxRateOverrides.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,2}(\.\d{1,2})?$/',
            ],

            // Categories
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'exists:categories,id',
            'primaryCategoryId' => 'nullable|exists:categories,id',
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'sku.required' => 'SKU jest wymagane.',
            'sku.unique' => 'SKU musi być unikalne.',
            'sku.regex' => 'SKU może zawierać tylko wielkie litery, cyfry, myślniki i podkreślenia.',
            'name.required' => 'Nazwa produktu jest wymagana.',
            'name.min' => 'Nazwa musi mieć co najmniej 3 znaki.',
            'slug.unique' => 'Slug musi być unikalny.',
            'slug.regex' => 'Slug może zawierać tylko małe litery, cyfry i myślniki.',
            'product_type_id.required' => 'Typ produktu jest wymagany.',
            'product_type_id.exists' => 'Wybrany typ produktu nie istnieje.',
            'ean.regex' => 'EAN musi składać się z 8-13 cyfr.',

            // Tax Rate - Global Default
            'tax_rate.required' => 'Stawka VAT jest wymagana.',
            'tax_rate.numeric' => 'Stawka VAT musi być liczbą.',
            'tax_rate.min' => 'Stawka VAT nie może być ujemna.',
            'tax_rate.max' => 'Stawka VAT nie może przekraczać 100%.',
            'tax_rate.regex' => 'Stawka VAT musi być w formacie XX.XX (np. 23.00).',

            // Tax Rate - Shop Overrides (FAZA 5.3)
            'shopTaxRateOverrides.*.numeric' => 'Stawka VAT dla sklepu musi być liczbą.',
            'shopTaxRateOverrides.*.min' => 'Stawka VAT dla sklepu nie może być ujemna.',
            'shopTaxRateOverrides.*.max' => 'Stawka VAT dla sklepu nie może przekraczać 100%.',
            'shopTaxRateOverrides.*.regex' => 'Stawka VAT dla sklepu musi być w formacie XX.XX (np. 8.00).',

            'primaryCategoryId.exists' => 'Wybrana kategoria główna nie istnieje.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Perform additional business validation
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        // Primary category must be selected if categories exist
        if (!empty($this->selectedCategories) && !$this->primaryCategoryId) {
            throw ValidationException::withMessages([
                'primaryCategoryId' => 'Należy wybrać kategorię główną.'
            ]);
        }

        // Primary category must be in selected categories
        if ($this->primaryCategoryId && !in_array($this->primaryCategoryId, $this->selectedCategories)) {
            throw ValidationException::withMessages([
                'primaryCategoryId' => 'Kategoria główna musi być wybrana z listy kategorii.'
            ]);
        }

        // Variant master name should be descriptive
        if ($this->is_variant_master && strlen($this->name) < 5) {
            throw ValidationException::withMessages([
                'name' => 'Produkty z wariantami powinny mieć opisową nazwę (minimum 5 znaków).'
            ]);
        }

        // EAN validation if provided
        if (!empty($this->ean) && !$this->validateEAN($this->ean)) {
            throw ValidationException::withMessages([
                'ean' => 'Podany kod EAN jest nieprawidłowy.'
            ]);
        }
    }

    /**
     * Validate EAN checksum
     *
     * @param string $ean
     * @return bool
     */
    private function validateEAN(string $ean): bool
    {
        if (!preg_match('/^[0-9]{8}$|^[0-9]{13}$/', $ean)) {
            return false;
        }

        // For EAN-8 and EAN-13 checksum validation
        $sum = 0;
        $length = strlen($ean);

        for ($i = 0; $i < $length - 1; $i++) {
            $digit = (int) $ean[$i];
            $sum += ($length === 13 && $i % 2 === 1) ? $digit * 3 : $digit;
        }

        $checksum = (10 - ($sum % 10)) % 10;
        return $checksum === (int) $ean[$length - 1];
    }
}