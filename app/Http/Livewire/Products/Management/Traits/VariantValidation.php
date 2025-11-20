<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Rules\UniqueSKU;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * VariantValidation Trait
 *
 * Centralized validation rules for product variant operations.
 *
 * VALIDATION CATEGORIES:
 * - Variant data (SKU, name, position, status)
 * - Variant attributes (color, size, etc.)
 * - Variant pricing (price, special price, dates)
 * - Variant stock (quantity, reserved, warehouse)
 * - Variant images (upload, dimensions, format)
 *
 * USAGE IN LIVEWIRE COMPONENTS:
 * ```php
 * use App\Http\Livewire\Products\Management\Traits\VariantValidation;
 *
 * class ProductForm extends Component
 * {
 *     use VariantValidation;
 *
 *     public function saveVariant()
 *     {
 *         $this->validateVariantCreate($this->variantData);
 *         // ... save logic
 *     }
 * }
 * ```
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since ETAP_05b Phase 6 (2025-10-30)
 */
trait VariantValidation
{
    /**
     * Validate variant creation data
     *
     * @param array $data Variant data to validate
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateVariantCreate(array $data): array
    {
        return Validator::make($data, [
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/',
                new UniqueSKU(),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-_0-9]+$/u',
            ],
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'nullable|integer|min:0|max:9999',
        ], [
            'sku.required' => 'SKU wariantu jest wymagane.',
            'sku.max' => 'SKU nie może przekraczać 100 znaków.',
            'sku.regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
            'name.required' => 'Nazwa wariantu jest wymagana.',
            'name.max' => 'Nazwa wariantu nie może przekraczać 255 znaków.',
            'name.regex' => 'Nazwa wariantu zawiera niedozwolone znaki.',
            'position.integer' => 'Pozycja musi być liczbą całkowitą.',
            'position.min' => 'Pozycja nie może być ujemna.',
            'position.max' => 'Pozycja nie może przekraczać 9999.',
        ])->validate();
    }

    /**
     * Validate variant update data
     *
     * @param int $variantId Variant ID being updated
     * @param array $data Variant data to validate
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateVariantUpdate(int $variantId, array $data): array
    {
        return Validator::make($data, [
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/',
                new UniqueSKU(null, $variantId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-_0-9]+$/u',
            ],
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'nullable|integer|min:0|max:9999',
        ], [
            'sku.required' => 'SKU wariantu jest wymagane.',
            'sku.max' => 'SKU nie może przekraczać 100 znaków.',
            'sku.regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
            'name.required' => 'Nazwa wariantu jest wymagana.',
            'name.max' => 'Nazwa wariantu nie może przekraczać 255 znaków.',
            'name.regex' => 'Nazwa wariantu zawiera niedozwolone znaki.',
            'position.integer' => 'Pozycja musi być liczbą całkowitą.',
            'position.min' => 'Pozycja nie może być ujemna.',
            'position.max' => 'Pozycja nie może przekraczać 9999.',
        ])->validate();
    }

    /**
     * Validate variant attributes data
     *
     * @param array $data Attributes data to validate
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateVariantAttributes(array $data): array
    {
        return Validator::make($data, [
            'attribute_type_id' => 'required|integer|exists:attribute_types,id',
            'attribute_value_id' => 'required|integer|exists:attribute_values,id',
        ], [
            'attribute_type_id.required' => 'Typ atrybutu jest wymagany.',
            'attribute_type_id.exists' => 'Wybrany typ atrybutu nie istnieje.',
            'attribute_value_id.required' => 'Wartość atrybutu jest wymagana.',
            'attribute_value_id.exists' => 'Wybrana wartość atrybutu nie istnieje.',
        ])->validate();
    }

    /**
     * Validate variant price data
     *
     * @param array $data Price data to validate
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateVariantPrice(array $data): array
    {
        return Validator::make($data, [
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'price_special' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
                'lt:price',
            ],
            'special_from' => [
                'nullable',
                'date',
                'before_or_equal:special_to',
            ],
            'special_to' => [
                'nullable',
                'date',
                'after_or_equal:special_from',
            ],
        ], [
            'price.required' => 'Cena jest wymagana.',
            'price.numeric' => 'Cena musi być liczbą.',
            'price.min' => 'Cena nie może być ujemna.',
            'price.max' => 'Cena nie może przekraczać 999,999.99.',
            'price.regex' => 'Cena może mieć maksymalnie 2 miejsca po przecinku.',
            'price_special.numeric' => 'Cena promocyjna musi być liczbą.',
            'price_special.min' => 'Cena promocyjna nie może być ujemna.',
            'price_special.max' => 'Cena promocyjna nie może przekraczać 999,999.99.',
            'price_special.regex' => 'Cena promocyjna może mieć maksymalnie 2 miejsca po przecinku.',
            'price_special.lt' => 'Cena promocyjna musi być niższa niż cena regularna.',
            'special_from.date' => 'Data rozpoczęcia promocji jest nieprawidłowa.',
            'special_from.before_or_equal' => 'Data rozpoczęcia musi być wcześniejsza lub równa dacie zakończenia.',
            'special_to.date' => 'Data zakończenia promocji jest nieprawidłowa.',
            'special_to.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
        ])->validate();
    }

    /**
     * Validate variant stock data
     *
     * @param array $data Stock data to validate
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateVariantStock(array $data): array
    {
        return Validator::make($data, [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'reserved' => [
                'required',
                'integer',
                'min:0',
                'lte:quantity',
            ],
        ], [
            'warehouse_id.required' => 'Magazyn jest wymagany.',
            'warehouse_id.exists' => 'Wybrany magazyn nie istnieje.',
            'quantity.required' => 'Ilość jest wymagana.',
            'quantity.integer' => 'Ilość musi być liczbą całkowitą.',
            'quantity.min' => 'Ilość nie może być ujemna.',
            'quantity.max' => 'Ilość nie może przekraczać 999,999.',
            'reserved.required' => 'Ilość zarezerwowana jest wymagana.',
            'reserved.integer' => 'Ilość zarezerwowana musi być liczbą całkowitą.',
            'reserved.min' => 'Ilość zarezerwowana nie może być ujemna.',
            'reserved.lte' => 'Zarezerwowana ilość nie może przekraczać dostępnej.',
        ])->validate();
    }

    /**
     * Validate variant image upload
     *
     * @param mixed $file File to validate
     * @return void
     * @throws ValidationException
     */
    protected function validateVariantImage($file): void
    {
        Validator::make(['image' => $file], [
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240', // 10MB in KB
                'dimensions:min_width=200,min_height=200,max_width=5000,max_height=5000',
            ],
        ], [
            'image.required' => 'Zdjęcie jest wymagane.',
            'image.image' => 'Plik musi być zdjęciem.',
            'image.mimes' => 'Dozwolone formaty: JPG, JPEG, PNG, WEBP.',
            'image.max' => 'Maksymalny rozmiar pliku to 10MB.',
            'image.dimensions' => 'Wymiary zdjęcia muszą być pomiędzy 200x200 a 5000x5000 pikseli.',
        ])->validate();
    }

    /**
     * Validate aspect ratio of uploaded image (additional check)
     *
     * @param mixed $file File to validate
     * @return bool True if aspect ratio is acceptable
     */
    protected function validateImageAspectRatio($file): bool
    {
        if (!$file || !method_exists($file, 'dimensions')) {
            return true;
        }

        try {
            [$width, $height] = getimagesize($file->getRealPath());
            $aspectRatio = $width / $height;

            // Aspect ratio between 0.5 (tall) and 2.0 (wide)
            return $aspectRatio >= 0.5 && $aspectRatio <= 2.0;
        } catch (\Exception $e) {
            // If cannot determine dimensions, allow it (dimensions validation will catch it)
            return true;
        }
    }

    /**
     * Validate bulk variant operations data
     *
     * @param array $data Bulk operation data
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateBulkVariantOperation(array $data): array
    {
        return Validator::make($data, [
            'variant_ids' => 'required|array|min:1|max:100',
            'variant_ids.*' => 'required|integer|exists:product_variants,id',
            'action' => 'required|in:activate,deactivate,delete,set_default',
        ], [
            'variant_ids.required' => 'Lista wariantów jest wymagana.',
            'variant_ids.array' => 'Lista wariantów musi być tablicą.',
            'variant_ids.min' => 'Wybierz co najmniej jeden wariant.',
            'variant_ids.max' => 'Maksymalnie 100 wariantów w jednej operacji.',
            'variant_ids.*.exists' => 'Wybrany wariant nie istnieje.',
            'action.required' => 'Akcja jest wymagana.',
            'action.in' => 'Nieprawidłowa akcja. Dostępne: activate, deactivate, delete, set_default.',
        ])->validate();
    }

    /**
     * Get validation rules for variant data (for use with Livewire's $rules property)
     *
     * @param int|null $variantId Variant ID (for updates)
     * @return array Validation rules
     */
    protected function getVariantRules(?int $variantId = null): array
    {
        return [
            'variantData.sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/',
                $variantId ? new UniqueSKU(null, $variantId) : new UniqueSKU(),
            ],
            'variantData.name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-_0-9]+$/u',
            ],
            'variantData.is_active' => 'boolean',
            'variantData.is_default' => 'boolean',
            'variantData.position' => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * Get validation messages for variant data (for use with Livewire's $messages property)
     *
     * @return array Validation messages
     */
    protected function getVariantMessages(): array
    {
        return [
            'variantData.sku.required' => 'SKU wariantu jest wymagane.',
            'variantData.sku.max' => 'SKU nie może przekraczać 100 znaków.',
            'variantData.sku.regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
            'variantData.name.required' => 'Nazwa wariantu jest wymagana.',
            'variantData.name.max' => 'Nazwa wariantu nie może przekraczać 255 znaków.',
            'variantData.name.regex' => 'Nazwa wariantu zawiera niedozwolone znaki.',
            'variantData.position.integer' => 'Pozycja musi być liczbą całkowitą.',
            'variantData.position.min' => 'Pozycja nie może być ujemna.',
            'variantData.position.max' => 'Pozycja nie może przekraczać 9999.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | WAVE 3 VALIDATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Validate variant prices grid data (Wave 3 Task 2)
     *
     * @param array $prices [variant_id => [price_group_key => price]]
     * @return void
     * @throws \Exception
     */
    protected function validateVariantPricesGrid(array $prices): void
    {
        foreach ($prices as $variantId => $priceGroups) {
            foreach ($priceGroups as $priceGroupKey => $price) {
                if (!is_numeric($price) || $price < 0) {
                    throw new \Exception("Nieprawidłowa cena dla wariantu {$variantId}, grupa {$priceGroupKey}. Cena musi być liczbą dodatnią.");
                }

                if ($price > 999999.99) {
                    throw new \Exception("Cena dla wariantu {$variantId} przekracza maksymalną wartość 999,999.99");
                }
            }
        }
    }

    /**
     * Validate variant stock grid data (Wave 3 Task 2)
     *
     * @param array $stock [variant_id => [warehouse_id => quantity]]
     * @return void
     * @throws \Exception
     */
    protected function validateVariantStockGrid(array $stock): void
    {
        foreach ($stock as $variantId => $warehouses) {
            foreach ($warehouses as $warehouseId => $quantity) {
                // Allow string numbers from form inputs
                if (!is_int($quantity) && !is_numeric($quantity)) {
                    throw new \Exception("Nieprawidłowy stan dla wariantu {$variantId}, magazyn {$warehouseId}. Stan musi być liczbą całkowitą.");
                }

                $quantity = (int) $quantity;

                if ($quantity < 0) {
                    throw new \Exception("Stan dla wariantu {$variantId}, magazyn {$warehouseId} nie może być ujemny.");
                }

                if ($quantity > 999999) {
                    throw new \Exception("Stan dla wariantu {$variantId}, magazyn {$warehouseId} przekracza maksymalną wartość 999,999.");
                }
            }
        }
    }

    /**
     * Validate variant image upload (Wave 3 Task 3)
     *
     * Enhanced version with detailed error messages
     *
     * @param mixed $image File to validate
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateVariantImageUpload($image): void
    {
        Validator::make(['image' => $image], [
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:5120', // 5MB in KB
                'dimensions:min_width=200,min_height=200,max_width=5000,max_height=5000',
            ],
        ], [
            'image.required' => 'Zdjęcie jest wymagane.',
            'image.image' => 'Plik musi być zdjęciem.',
            'image.mimes' => 'Dozwolone formaty: JPG, JPEG, PNG, GIF, WEBP.',
            'image.max' => 'Maksymalny rozmiar pliku to 5MB.',
            'image.dimensions' => 'Wymiary zdjęcia muszą być pomiędzy 200x200 a 5000x5000 pikseli.',
        ])->validate();
    }

    /**
     * Validate variant attributes data for creation/update (Wave 3 Task 1)
     *
     * @param array $attributes [attribute_type_id => value]
     * @return void
     * @throws \Exception
     */
    protected function validateVariantAttributesData(array $attributes): void
    {
        foreach ($attributes as $attributeTypeId => $value) {
            if (!is_numeric($attributeTypeId) || $attributeTypeId <= 0) {
                throw new \Exception("Nieprawidłowy ID typu atrybutu: {$attributeTypeId}");
            }

            if (empty($value) || !is_string($value)) {
                throw new \Exception("Wartość atrybutu dla typu {$attributeTypeId} musi być niepustym tekstem.");
            }

            if (strlen($value) > 255) {
                throw new \Exception("Wartość atrybutu dla typu {$attributeTypeId} przekracza maksymalną długość 255 znaków.");
            }
        }
    }
}
