# Product Variant Validation Guide

**Version:** 1.0
**Created:** 2025-10-30
**ETAP:** 05b Phase 6 - Zadanie 7

## 📋 Overview

This guide documents the comprehensive validation system for product variant operations in PPM-CC-Laravel.

**Components:**
- `UniqueSKU` custom validation rule
- `VariantValidation` trait with reusable validation methods
- Polish validation messages (`lang/pl/validation.php`)
- Unit tests for SKU uniqueness validation

---

## 🔧 UniqueSKU Validation Rule

### Purpose

Validates SKU uniqueness across **both** `products` and `product_variants` tables to prevent duplicate SKUs in the system.

### Features

- ✅ Cross-table validation (products + product_variants)
- ✅ Case-insensitive comparison
- ✅ Ignore current record during updates
- ✅ Polish error messages
- ✅ Empty SKU handling (defers to 'required' rule)

### File Location

```
app/Rules/UniqueSKU.php
```

### Usage Examples

#### 1. Validate New Product/Variant SKU

```php
use App\Rules\UniqueSKU;

// In FormRequest or Livewire component
$this->validate([
    'sku' => ['required', 'string', 'max:100', new UniqueSKU()],
]);
```

#### 2. Validate Product SKU Update

```php
use App\Rules\UniqueSKU;

// Ignore current product during update
$this->validate([
    'sku' => ['required', 'string', 'max:100', new UniqueSKU($productId)],
]);
```

#### 3. Validate Variant SKU Update

```php
use App\Rules\UniqueSKU;

// Ignore current variant during update
$this->validate([
    'sku' => ['required', 'string', 'max:100', new UniqueSKU(null, $variantId)],
]);
```

#### 4. In Laravel FormRequest

```php
namespace App\Http\Requests;

use App\Rules\UniqueSKU;
use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
{
    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                new UniqueSKU(null, $variantId),
            ],
            'name' => 'required|string|max:255',
            // ... other rules
        ];
    }
}
```

### Constructor Parameters

```php
public function __construct(?int $ignoreProductId = null, ?int $ignoreVariantId = null)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ignoreProductId` | `int\|null` | Product ID to ignore during validation (for product updates) |
| `$ignoreVariantId` | `int\|null` | Variant ID to ignore during validation (for variant updates) |

### Error Message

**Polish:** `"SKU '{value}' jest już używane przez inny produkt lub wariant."`

**Example:** `"SKU 'ABC-123' jest już używane przez inny produkt lub wariant."`

### Validation Logic

1. Empty SKU → Pass (handled by 'required' rule)
2. Check `products` table → If exists (and not ignored) → Fail
3. Check `product_variants` table → If exists (and not ignored) → Fail
4. No conflicts → Pass

**Case-Insensitive:** `LOWER(sku)` comparison ensures `ABC-123` = `abc-123` = `Abc-123`

---

## 🎯 VariantValidation Trait

### Purpose

Centralized validation methods for all variant-related operations, ensuring consistency across Livewire components.

### File Location

```
app/Http/Livewire/Products/Management/Traits/VariantValidation.php
```

### Usage in Livewire Components

```php
namespace App\Http\Livewire\Products\Management;

use App\Http\Livewire\Products\Management\Traits\VariantValidation;
use Livewire\Component;

class ProductForm extends Component
{
    use VariantValidation;

    public array $variantData = [];
    public array $variantPrice = [];
    public array $variantStock = [];

    public function saveVariant()
    {
        // Validate variant data
        $validatedData = $this->validateVariantCreate($this->variantData);

        // Validate pricing
        $validatedPrice = $this->validateVariantPrice($this->variantPrice);

        // Validate stock
        $validatedStock = $this->validateVariantStock($this->variantStock);

        // Save variant...
    }
}
```

### Available Methods

#### 1. `validateVariantCreate(array $data): array`

Validates variant creation data (SKU, name, position, flags).

**Rules:**
- `sku`: required, max 100, alphanumeric + dashes/underscores, unique
- `name`: required, max 255, letters/numbers/spaces/dashes/underscores
- `is_active`: boolean
- `is_default`: boolean
- `position`: nullable, integer, 0-9999

**Example:**
```php
$validatedData = $this->validateVariantCreate([
    'sku' => 'VARIANT-ABC-123',
    'name' => 'Wariant ABC - Rozmiar L',
    'is_active' => true,
    'is_default' => false,
    'position' => 1,
]);
```

#### 2. `validateVariantUpdate(int $variantId, array $data): array`

Validates variant update data (same rules as create, but ignores current variant SKU).

**Example:**
```php
$validatedData = $this->validateVariantUpdate($variantId, [
    'sku' => 'VARIANT-ABC-123', // Can be same as current variant
    'name' => 'Updated Name',
    'is_active' => false,
]);
```

#### 3. `validateVariantAttributes(array $data): array`

Validates variant attribute assignments (color, size, etc.).

**Rules:**
- `attribute_type_id`: required, exists in `attribute_types` table
- `attribute_value_id`: required, exists in `attribute_values` table

**Example:**
```php
$validatedAttributes = $this->validateVariantAttributes([
    'attribute_type_id' => 1, // Color
    'attribute_value_id' => 5, // Red
]);
```

#### 4. `validateVariantPrice(array $data): array`

Validates variant pricing data (price, special price, date ranges).

**Rules:**
- `price`: required, numeric, >= 0, max 2 decimals, <= 999,999.99
- `price_special`: nullable, numeric, >= 0, max 2 decimals, < price
- `special_from`: nullable, date, <= special_to
- `special_to`: nullable, date, >= special_from

**Example:**
```php
$validatedPrice = $this->validateVariantPrice([
    'price' => 99.99,
    'price_special' => 79.99,
    'special_from' => '2025-11-01',
    'special_to' => '2025-11-30',
]);
```

#### 5. `validateVariantStock(array $data): array`

Validates variant stock data (warehouse, quantity, reserved).

**Rules:**
- `warehouse_id`: required, exists in `warehouses` table
- `quantity`: required, integer, 0-999,999
- `reserved`: required, integer, >= 0, <= quantity

**Example:**
```php
$validatedStock = $this->validateVariantStock([
    'warehouse_id' => 1, // MPPTRADE
    'quantity' => 100,
    'reserved' => 25,
]);
```

#### 6. `validateVariantImage($file): void`

Validates variant image upload.

**Rules:**
- File type: jpg, jpeg, png, webp
- Max size: 10MB
- Dimensions: 200x200 to 5000x5000 pixels

**Example:**
```php
// In Livewire component with file upload
public $variantImage;

public function saveImage()
{
    $this->validateVariantImage($this->variantImage);

    // Save image...
}
```

#### 7. `validateImageAspectRatio($file): bool`

Additional validation for image aspect ratio (0.5 to 2.0).

**Example:**
```php
if (!$this->validateImageAspectRatio($this->variantImage)) {
    $this->addError('variantImage', 'Proporcje zdjęcia są nieprawidłowe (zbyt wąskie lub zbyt szerokie).');
    return;
}
```

#### 8. `validateBulkVariantOperation(array $data): array`

Validates bulk variant operations (activate, deactivate, delete, set_default).

**Rules:**
- `variant_ids`: required, array, 1-100 elements, each exists in `product_variants`
- `action`: required, in: activate, deactivate, delete, set_default

**Example:**
```php
$validatedBulk = $this->validateBulkVariantOperation([
    'variant_ids' => [1, 2, 3, 4],
    'action' => 'activate',
]);
```

#### 9. `getVariantRules(?int $variantId = null): array`

Returns validation rules for use with Livewire's `$rules` property.

**Example:**
```php
class ProductForm extends Component
{
    use VariantValidation;

    public array $variantData = [];

    protected function rules()
    {
        return array_merge(
            $this->getVariantRules($this->editingVariantId),
            [
                // ... other rules
            ]
        );
    }
}
```

#### 10. `getVariantMessages(): array`

Returns Polish validation messages for use with Livewire's `$messages` property.

**Example:**
```php
class ProductForm extends Component
{
    use VariantValidation;

    protected function messages()
    {
        return array_merge(
            $this->getVariantMessages(),
            [
                // ... other custom messages
            ]
        );
    }
}
```

---

## 🌍 Polish Validation Messages

### File Location

```
lang/pl/validation.php
```

### Features

- ✅ Complete Polish translations for Laravel validation rules
- ✅ Custom attribute names (sku → SKU, name → nazwa, etc.)
- ✅ Component-specific custom messages (variantData, variantPrice, variantStock, etc.)

### Custom Attribute Names

```php
'attributes' => [
    'sku' => 'SKU',
    'name' => 'nazwa',
    'is_active' => 'aktywny',
    'price' => 'cena',
    'quantity' => 'ilość',
    'warehouse_id' => 'magazyn',
    // ... more
],
```

### Custom Messages Examples

**Variant Data:**
```php
'variantData.sku.required' => 'SKU wariantu jest wymagane.',
'variantData.name.max' => 'Nazwa wariantu nie może przekraczać :max znaków.',
```

**Variant Price:**
```php
'variantPrice.price_special.lt' => 'Cena promocyjna musi być niższa niż cena regularna.',
```

**Variant Stock:**
```php
'variantStock.reserved.lte' => 'Zarezerwowana ilość nie może przekraczać dostępnej.',
```

---

## 🧪 Unit Tests

### File Location

```
tests/Unit/Rules/UniqueSKUTest.php
```

### Test Coverage

1. ✅ New SKU passes validation (no conflicts)
2. ✅ SKU fails when exists in `products` table
3. ✅ SKU fails when exists in `product_variants` table
4. ✅ Case-insensitive validation
5. ✅ Update same product (ignore current)
6. ✅ Update same variant (ignore current)
7. ✅ Update to another product's SKU fails
8. ✅ Update to another variant's SKU fails
9. ✅ Empty SKU passes (defers to 'required')
10. ✅ Error message contains SKU value
11. ✅ Cross-table validation (variant vs product)
12. ✅ Cross-table validation (product vs variant)
13. ✅ Edge case: multiple ignores

### Running Tests

```bash
# Run all UniqueSKU tests
php artisan test --filter=UniqueSKUTest

# Run specific test
php artisan test --filter=UniqueSKUTest::test_sku_fails_when_exists_in_products_table

# Run with coverage
php artisan test --filter=UniqueSKUTest --coverage
```

### Expected Output

```
PASS  Tests\Unit\Rules\UniqueSKUTest
✓ new sku passes validation
✓ sku fails when exists in products table
✓ sku fails when exists in variants table
✓ sku validation is case insensitive
✓ sku passes when updating same product
✓ sku passes when updating same variant
✓ sku fails when updating to another products sku
✓ sku fails when updating to another variants sku
✓ empty sku passes validation
✓ error message contains sku value
✓ variant sku fails when conflicts with product sku
✓ product sku fails when conflicts with variant sku
✓ multiple ignores edge case

Tests:  13 passed
Time:   1.23s
```

---

## 📝 Integration Examples

### Example 1: ProductForm Livewire Component

```php
namespace App\Http\Livewire\Products\Management;

use App\Http\Livewire\Products\Management\Traits\VariantValidation;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use VariantValidation, WithFileUploads;

    public $product;
    public array $variantData = [];
    public array $variantPrice = [];
    public array $variantStock = [];
    public $variantImage;

    public function createVariant()
    {
        try {
            // Validate all variant data
            $validatedData = $this->validateVariantCreate($this->variantData);
            $validatedPrice = $this->validateVariantPrice($this->variantPrice);
            $validatedStock = $this->validateVariantStock($this->variantStock);

            if ($this->variantImage) {
                $this->validateVariantImage($this->variantImage);

                if (!$this->validateImageAspectRatio($this->variantImage)) {
                    $this->addError('variantImage', 'Proporcje zdjęcia są nieprawidłowe.');
                    return;
                }
            }

            // Create variant...
            $variant = $this->product->variants()->create($validatedData);
            $variant->prices()->create($validatedPrice);
            $variant->stock()->create($validatedStock);

            if ($this->variantImage) {
                $path = $this->variantImage->store('variants', 'public');
                $variant->images()->create(['path' => $path]);
            }

            session()->flash('success', 'Wariant utworzony pomyślnie.');
            $this->resetVariantForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }
}
```

### Example 2: FormRequest Validation

```php
namespace App\Http\Requests;

use App\Rules\UniqueSKU;
use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
{
    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/',
                new UniqueSKU(null, $variantId),
            ],
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'quantity' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'SKU wariantu jest wymagane.',
            'sku.regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
            'price.regex' => 'Cena może mieć maksymalnie 2 miejsca po przecinku.',
        ];
    }
}
```

---

## 🚨 Common Validation Scenarios

### Scenario 1: Create New Variant

```php
// User creates new variant with SKU "VARIANT-001"
$this->validateVariantCreate([
    'sku' => 'VARIANT-001',
    'name' => 'Rozmiar L - Czerwony',
    'is_active' => true,
    'is_default' => false,
]);

// ✅ Passes if "VARIANT-001" doesn't exist in products or variants
// ❌ Fails if "VARIANT-001" exists in products table
// ❌ Fails if "variant-001" exists in variants table (case-insensitive)
```

### Scenario 2: Update Existing Variant

```php
// User updates variant ID 5 with same SKU "VARIANT-001"
$this->validateVariantUpdate(5, [
    'sku' => 'VARIANT-001', // Same as current
    'name' => 'Updated Name',
]);

// ✅ Passes because ignoring current variant (ID 5)
```

### Scenario 3: Update Variant to Another SKU

```php
// User updates variant ID 5 from "VARIANT-001" to "VARIANT-002"
$this->validateVariantUpdate(5, [
    'sku' => 'VARIANT-002',
    'name' => 'Updated Name',
]);

// ✅ Passes if "VARIANT-002" doesn't exist
// ❌ Fails if "VARIANT-002" exists in products or other variants
```

### Scenario 4: Price Validation

```php
$this->validateVariantPrice([
    'price' => 99.99,
    'price_special' => 79.99,
    'special_from' => '2025-11-01',
    'special_to' => '2025-11-30',
]);

// ✅ Passes: special price < regular price, valid date range
```

```php
$this->validateVariantPrice([
    'price' => 99.99,
    'price_special' => 120.00, // Higher than regular!
]);

// ❌ Fails: 'price_special.lt' → "Cena promocyjna musi być niższa niż cena regularna."
```

### Scenario 5: Stock Validation

```php
$this->validateVariantStock([
    'warehouse_id' => 1,
    'quantity' => 100,
    'reserved' => 25,
]);

// ✅ Passes: reserved (25) <= quantity (100)
```

```php
$this->validateVariantStock([
    'warehouse_id' => 1,
    'quantity' => 100,
    'reserved' => 150, // More than available!
]);

// ❌ Fails: 'reserved.lte' → "Zarezerwowana ilość nie może przekraczać dostępnej."
```

---

## 📊 File Summary

| File | Lines | Purpose |
|------|-------|---------|
| `app/Rules/UniqueSKU.php` | 115 | Custom SKU uniqueness validation rule |
| `app/Http/Livewire/Products/Management/Traits/VariantValidation.php` | 340 | Centralized variant validation methods |
| `lang/pl/validation.php` | 280 | Polish validation messages |
| `tests/Unit/Rules/UniqueSKUTest.php` | 275 | Unit tests for UniqueSKU rule |
| **Total** | **1,010** | Complete validation system |

---

## 🔗 Related Documentation

- **ETAP_05b Plan:** `Plan_Projektu/ETAP_05b_Produkty_Warianty.md`
- **Database Schema:** `_DOCS/Struktura_Bazy_Danych.md`
- **Laravel Validation Docs:** https://laravel.com/docs/12.x/validation
- **Livewire Validation Docs:** https://livewire.laravel.com/docs/validation

---

## ✅ Validation Best Practices

1. **Always use VariantValidation trait** in Livewire components for consistency
2. **Validate early** before database operations
3. **Use Polish messages** for better UX (lang/pl/validation.php)
4. **Test validation rules** with unit tests
5. **Handle ValidationException** gracefully in UI
6. **Combine with JavaScript validation** for instant feedback (but always validate server-side!)
7. **Document custom rules** with usage examples
8. **Keep validation logic DRY** (Don't Repeat Yourself) using traits/rules

---

**END OF GUIDE**
