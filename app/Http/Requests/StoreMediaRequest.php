<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * StoreMediaRequest - FAZA C: Validation dla dodawania mediów
 * 
 * Zabezpiecza upload zdjęć dla produktów i wariantów:
 * - File validation (types, size, dimensions)
 * - Business rules (max 20 images per product)
 * - Security validation (MIME type checking)
 * - Performance validation (file size limits)
 * 
 * @package App\Http\Requests
 * @since FAZA C - Media & Relations Implementation
 */
class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage media
        // Admin, Manager, Editor can add media
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
            // Core file validation
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max(5 * 1024) // 5MB max
                    ->dimensions()
                    ->min(100, 100) // Minimum 100x100
                    ->max(4000, 4000), // Maximum 4000x4000
            ],
            
            // Mediable polymorphic relationship
            'mediable_type' => [
                'required',
                'string',
                'in:App\\Models\\Product,App\\Models\\ProductVariant',
            ],
            'mediable_id' => [
                'required',
                'integer',
                'min:1',
            ],
            
            // Optional metadata
            'alt_text' => [
                'nullable',
                'string',
                'max:300',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'is_primary' => [
                'nullable',
                'boolean',
            ],
            
            // PrestaShop mapping (optional)
            'prestashop_mapping' => [
                'nullable',
                'array',
            ],
            'prestashop_mapping.*' => [
                'array',
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
            'file.required' => 'Plik zdjęcia jest wymagany.',
            'file.mimes' => 'Dozwolone formaty: JPG, JPEG, PNG, WebP, GIF.',
            'file.max' => 'Maksymalny rozmiar pliku to 5MB.',
            'file.dimensions' => 'Minimalne wymiary to 100x100px, maksymalne 4000x4000px.',
            
            'mediable_type.required' => 'Typ obiektu jest wymagany.',
            'mediable_type.in' => 'Nieprawidłowy typ obiektu.',
            'mediable_id.required' => 'ID obiektu jest wymagane.',
            'mediable_id.integer' => 'ID obiektu musi być liczbą.',
            
            'alt_text.max' => 'Tekst alternatywny może mieć maksymalnie 300 znaków.',
            'sort_order.integer' => 'Kolejność sortowania musi być liczbą.',
            'sort_order.min' => 'Kolejność sortowania nie może być ujemna.',
            'sort_order.max' => 'Maksymalna kolejność sortowania to 100.',
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
            'file' => 'plik zdjęcia',
            'mediable_type' => 'typ obiektu',
            'mediable_id' => 'ID obiektu',
            'alt_text' => 'tekst alternatywny',
            'sort_order' => 'kolejność sortowania',
            'is_primary' => 'główne zdjęcie',
            'prestashop_mapping' => 'mapowanie PrestaShop',
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
            // Validate mediable object exists
            $this->validateMediableExists($validator);
            
            // Validate media count limit (max 20 per product)
            $this->validateMediaCountLimit($validator);
            
            // Validate primary image business logic
            $this->validatePrimaryImageLogic($validator);
        });
    }

    /**
     * Validate that mediable object exists
     */
    private function validateMediableExists($validator): void
    {
        $type = $this->input('mediable_type');
        $id = $this->input('mediable_id');
        
        if ($type && $id) {
            $model = app($type);
            $exists = $model::where('id', $id)->exists();
            
            if (!$exists) {
                $validator->errors()->add('mediable_id', 'Wybrany obiekt nie istnieje.');
            }
        }
    }

    /**
     * Validate media count limit (max 20 per product/variant)
     */
    private function validateMediaCountLimit($validator): void
    {
        $type = $this->input('mediable_type');
        $id = $this->input('mediable_id');
        
        if ($type && $id) {
            $currentCount = \App\Models\Media::where('mediable_type', $type)
                ->where('mediable_id', $id)
                ->where('is_active', true)
                ->count();
                
            if ($currentCount >= 20) {
                $validator->errors()->add('file', 'Maksymalna liczba zdjęć na produkt to 20.');
            }
        }
    }

    /**
     * Validate primary image business logic
     */
    private function validatePrimaryImageLogic($validator): void
    {
        $type = $this->input('mediable_type');
        $id = $this->input('mediable_id');
        $isPrimary = $this->boolean('is_primary');
        
        // If explicitly setting as primary, that's fine
        // If not setting as primary, check if this would be the first image (auto-primary)
        if (!$isPrimary && $type && $id) {
            $hasImages = \App\Models\Media::where('mediable_type', $type)
                ->where('mediable_id', $id)
                ->where('is_active', true)
                ->exists();
                
            // First image will automatically become primary (handled in Media model)
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize mediable_type to full class name
        if ($this->has('mediable_type')) {
            $type = $this->input('mediable_type');
            
            // Allow short names and convert to full class names
            $typeMap = [
                'product' => 'App\\Models\\Product',
                'Product' => 'App\\Models\\Product',
                'variant' => 'App\\Models\\ProductVariant',
                'ProductVariant' => 'App\\Models\\ProductVariant',
            ];
            
            if (isset($typeMap[$type])) {
                $this->merge([
                    'mediable_type' => $typeMap[$type],
                ]);
            }
        }
        
        // Set default sort_order if not provided
        if (!$this->has('sort_order')) {
            $type = $this->input('mediable_type');
            $id = $this->input('mediable_id');
            
            if ($type && $id) {
                $maxOrder = \App\Models\Media::where('mediable_type', $type)
                    ->where('mediable_id', $id)
                    ->max('sort_order') ?? -1;
                    
                $this->merge([
                    'sort_order' => $maxOrder + 1,
                ]);
            }
        }
    }
}