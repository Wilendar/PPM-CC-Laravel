# Livewire Trait Composition Patterns

**Last Updated:** 2025-11-04
**Skill:** livewire-dev-guidelines

---

## Overview

Trait composition is THE key pattern for managing large Livewire components in the PPM project. The ProductForm component was refactored from 2182 lines to 250 lines using this pattern!

---

## When to Extract Traits

**Guidelines:**
- Component > 200 lines → Consider extracting traits
- Component > 300 lines → MUST extract traits
- Component > 500 lines → VIOLATION (emergency refactoring needed)

**Signs you need traits:**
- Multiple concerns mixed together
- Scrolling required to understand component
- Difficult to test individual features
- Validation rules > 50 lines
- Multiple reactive updatedProperty methods

---

## Trait Naming Conventions

### Pattern: `{ComponentName}{Concern}`

**Common trait types:**

1. **Validation Traits:**
   - `ComponentNameValidation`
   - Contains: `rules()`, `validationAttributes()`, `messages()`

2. **Update Traits:**
   - `ComponentNameUpdates`
   - Contains: `updatedPropertyName()` methods

3. **Computed Traits:**
   - `ComponentNameComputed`
   - Contains: `#[Computed]` properties

4. **Domain Logic Traits:**
   - `ComponentNameDomainLogic` (specific feature name)
   - Example: `ProductFormVariants`, `ProductFormCategories`

---

## Real Example: ProductForm Refactoring

### BEFORE (Monolithic - 2182 lines)

```php
// app/Livewire/Products/Management/ProductForm.php
class ProductForm extends Component
{
    // Properties (100+ lines)
    public Product $product;
    public $selectedCategories = [];
    public $shopData = [];
    public $variantAttributes = [];
    public $activeTab = 0;
    // ... 50+ more properties

    // Validation (300+ lines)
    public function rules()
    {
        return [
            // 100+ validation rules
        ];
    }

    public function validationAttributes()
    {
        // 50+ attribute names
    }

    public function messages()
    {
        // 50+ custom messages
    }

    // Reactive Updates (400+ lines)
    public function updatedProductProductTypeId($value) { /* ... */ }
    public function updatedProductHasVariants($value) { /* ... */ }
    public function updatedProductCena($value) { /* ... */ }
    // ... 20+ more updated methods

    // Computed Properties (300+ lines)
    public function getAvailableCategoriesProperty() { /* ... */ }
    public function getProductTypesProperty() { /* ... */ }
    public function getPriceGroupsProperty() { /* ... */ }
    // ... 15+ more computed properties

    // Business Logic (800+ lines)
    public function save() { /* ... 200 lines ... */ }
    public function syncCategories() { /* ... 150 lines ... */ }
    public function syncVariants() { /* ... 200 lines ... */ }
    public function syncShopData() { /* ... 150 lines ... */ }
    // ... more complex methods

    // UI Helpers (282+ lines)
    public function addVariant() { /* ... */ }
    public function removeVariant() { /* ... */ }
    public function toggleShop() { /* ... */ }
    // ... 20+ more UI helpers
}
```

---

### AFTER (Trait Composition - 250 lines)

#### Main Component (Coordination Only)

```php
// app/Livewire/Products/Management/ProductForm.php
class ProductForm extends Component
{
    use ProductFormValidation;      // Validation rules (120 lines)
    use ProductFormUpdates;         // Reactive updates (150 lines)
    use ProductFormComputed;        // Computed properties (180 lines)
    use ProductFormVariants;        // Variant logic (200 lines)

    // Core properties (only component-level state)
    public Product $product;
    public int $activeTab = 0;
    public bool $showDeleteModal = false;

    // Lifecycle
    public function mount(?int $productId = null)
    {
        $this->product = $productId
            ? Product::with(['categories', 'productType', 'variants'])->findOrFail($productId)
            : new Product();

        $this->initializeShopData();
        $this->initializeCategories();
    }

    // Main action (orchestration only)
    public function save()
    {
        $this->validate();

        app(ProductFormSaver::class)->save($this);

        session()->flash('message', 'Produkt został zapisany pomyślnie!');
        $this->redirect(route('admin.products.index'));
    }

    // Render
    public function render()
    {
        return view('livewire.products.management.product-form')
            ->layout('layouts.admin');
    }
}
```

**Result:** 250 lines total (was 2182!)

---

#### Trait 1: ProductFormValidation (120 lines)

```php
// app/Livewire/Products/Management/Traits/ProductFormValidation.php
<?php

namespace App\Livewire\Products\Management\Traits;

use App\Rules\UniqueSKU;
use Illuminate\Validation\Rule;

trait ProductFormValidation
{
    public function rules(): array
    {
        return [
            // Basic Info
            'product.nazwa' => 'required|max:255',
            'product.sku' => [
                'required',
                'max:100',
                new UniqueSKU($this->product->id ?? null),
            ],
            'product.product_type_id' => 'nullable|exists:product_types,id',

            // Pricing
            'product.cena' => 'nullable|numeric|min:0|max:999999.99',
            'product.cena_promocyjna' => 'nullable|numeric|min:0|max:999999.99|lt:product.cena',

            // Stock
            'product.ilosc' => 'nullable|integer|min:0',
            'product.stan_magazynowy' => 'nullable|in:dostepny,niedostepny,na_zamowienie',

            // Variants
            'product.has_variants' => 'boolean',
            'variantAttributes' => 'array',
            'variantAttributes.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'variantAttributes.*.attribute_value_id' => 'required|exists:attribute_values,id',

            // Categories
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'exists:categories,id',

            // Shop Data
            'shopData.*.active' => 'boolean',
            'shopData.*.nazwa_override' => 'nullable|max:255',
            'shopData.*.cena_override' => 'nullable|numeric|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'product.nazwa' => 'Nazwa produktu',
            'product.sku' => 'SKU',
            'product.product_type_id' => 'Typ produktu',
            'product.cena' => 'Cena',
            'product.cena_promocyjna' => 'Cena promocyjna',
            'product.ilosc' => 'Ilość',
            'product.stan_magazynowy' => 'Stan magazynowy',
            'product.has_variants' => 'Ma warianty',
            'selectedCategories' => 'Kategorie',
            'shopData.*.active' => 'Aktywny w sklepie',
            'shopData.*.nazwa_override' => 'Nazwa w sklepie',
            'shopData.*.cena_override' => 'Cena w sklepie',
        ];
    }

    protected function messages(): array
    {
        return [
            'product.nazwa.required' => 'Nazwa produktu jest wymagana.',
            'product.sku.required' => 'SKU jest wymagane.',
            'product.cena_promocyjna.lt' => 'Cena promocyjna musi być niższa od ceny regularnej.',
            'selectedCategories.*.exists' => 'Wybrana kategoria nie istnieje.',
        ];
    }

    /**
     * Real-time validation for specific fields
     */
    public function updated($propertyName)
    {
        // Validate on blur for important fields
        if (in_array($propertyName, ['product.nazwa', 'product.sku', 'product.cena'])) {
            $this->validateOnly($propertyName);
        }
    }
}
```

---

#### Trait 2: ProductFormUpdates (150 lines)

```php
// app/Livewire/Products/Management/Traits/ProductFormUpdates.php
<?php

namespace App\Livewire\Products\Management\Traits;

trait ProductFormUpdates
{
    /**
     * React to product type change
     */
    public function updatedProductProductTypeId($value): void
    {
        if (!$value) {
            return;
        }

        $productType = ProductType::find($value);

        // Reset dynamic fields if product type changed
        if ($this->product->product_type_id !== $value) {
            $this->product->dynamic_fields = [];
        }

        // Update available attributes
        $this->dispatch('product-type-changed', $value);
    }

    /**
     * React to has_variants toggle
     */
    public function updatedProductHasVariants($value): void
    {
        if (!$value) {
            // Clear variants when disabling
            $this->variantAttributes = [];
            $this->dispatch('variants-cleared');
        } else {
            // Initialize variant structure
            $this->initializeVariantAttributes();
        }
    }

    /**
     * React to price change
     */
    public function updatedProductCena($value): void
    {
        // Validate promotional price is less than regular price
        if ($this->product->cena_promocyjna && $this->product->cena_promocyjna >= $value) {
            $this->addError('product.cena_promocyjna', 'Cena promocyjna musi być niższa od ceny regularnej.');
        } else {
            $this->resetErrorBag('product.cena_promocyjna');
        }

        // Update price groups
        $this->dispatch('price-changed', $value);
    }

    /**
     * React to promotional price change
     */
    public function updatedProductCenaPromocyjna($value): void
    {
        // Validate promotional price is less than regular price
        if ($value && $this->product->cena && $value >= $this->product->cena) {
            $this->addError('product.cena_promocyjna', 'Cena promocyjna musi być niższa od ceny regularnej.');
        } else {
            $this->resetErrorBag('product.cena_promocyjna');
        }
    }

    /**
     * React to stock quantity change
     */
    public function updatedProductIlosc($value): void
    {
        // Auto-update stock status based on quantity
        if ($value > 0) {
            $this->product->stan_magazynowy = 'dostepny';
        } else {
            $this->product->stan_magazynowy = 'niedostepny';
        }
    }

    /**
     * React to category selection change
     */
    public function updatedSelectedCategories($value): void
    {
        // Validate category hierarchy
        $this->validateCategoryHierarchy();

        // Dispatch event for category preview update
        $this->dispatch('categories-updated', $value);
    }

    /**
     * Helper: Validate category hierarchy
     */
    protected function validateCategoryHierarchy(): void
    {
        $categories = Category::whereIn('id', $this->selectedCategories)->get();

        // Check for parent-child conflicts
        foreach ($categories as $category) {
            if ($category->parent_id && in_array($category->parent_id, $this->selectedCategories)) {
                $this->addError('selectedCategories', 'Nie można wybrać kategorii i jej nadrzędnej kategorii jednocześnie.');
                return;
            }
        }

        $this->resetErrorBag('selectedCategories');
    }

    /**
     * Helper: Initialize variant attributes
     */
    protected function initializeVariantAttributes(): void
    {
        $this->variantAttributes = [
            [
                'attribute_type_id' => null,
                'attribute_value_id' => null,
            ]
        ];
    }
}
```

---

#### Trait 3: ProductFormComputed (180 lines)

```php
// app/Livewire/Products/Management/Traits/ProductFormComputed.php
<?php

namespace App\Livewire\Products\Management\Traits;

use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use App\Models\AttributeType;

trait ProductFormComputed
{
    /**
     * Get available categories
     */
    #[Computed]
    public function availableCategories()
    {
        return Category::active()
            ->orderBy('sort_order')
            ->orderBy('nazwa')
            ->get()
            ->toTree();
    }

    /**
     * Get available product types
     */
    #[Computed]
    public function productTypes()
    {
        return ProductType::active()
            ->orderBy('nazwa')
            ->get();
    }

    /**
     * Get price groups
     */
    #[Computed]
    public function priceGroups()
    {
        return PriceGroup::active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get warehouses
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::active()
            ->orderBy('nazwa')
            ->get();
    }

    /**
     * Get PrestaShop shops
     */
    #[Computed]
    public function prestaShops()
    {
        return PrestaShopShop::active()
            ->orderBy('nazwa')
            ->get();
    }

    /**
     * Get available attribute types for current product type
     */
    #[Computed]
    public function availableAttributeTypes()
    {
        if (!$this->product->product_type_id) {
            return collect();
        }

        return AttributeType::active()
            ->where('product_type_id', $this->product->product_type_id)
            ->with('attributeValues')
            ->orderBy('nazwa')
            ->get();
    }

    /**
     * Get dynamic fields for current product type
     */
    #[Computed]
    public function productTypeFields()
    {
        if (!$this->product->productType) {
            return [];
        }

        return $this->product->productType->fields ?? [];
    }

    /**
     * Check if product has pending changes
     */
    #[Computed]
    public function hasPendingChanges()
    {
        return $this->product->shopData()
            ->where('has_pending_changes', true)
            ->exists();
    }

    /**
     * Get sync status summary
     */
    #[Computed]
    public function syncStatusSummary()
    {
        $shopData = $this->product->shopData;

        return [
            'total_shops' => $shopData->count(),
            'synced' => $shopData->where('sync_status', 'synced')->count(),
            'pending' => $shopData->where('sync_status', 'pending')->count(),
            'failed' => $shopData->where('sync_status', 'failed')->count(),
        ];
    }
}
```

---

## Pattern: Service Injection with Traits

**When you need business logic in traits**, inject services instead of implementing logic directly.

```php
trait ProductFormVariants
{
    /**
     * Generate variants from attributes
     */
    public function generateVariants(): void
    {
        if (!$this->product->has_variants || empty($this->variantAttributes)) {
            return;
        }

        // ❌ WRONG: Business logic in trait
        // $combinations = $this->calculateCombinations($this->variantAttributes);
        // foreach ($combinations as $combo) { /* ... */ }

        // ✅ CORRECT: Service injection
        app(VariantGenerator::class)->generate($this->product, $this->variantAttributes);

        $this->dispatch('variants-generated');
    }

    /**
     * Delete variant
     */
    public function deleteVariant(int $variantId): void
    {
        // ✅ Service handles business logic
        app(VariantManager::class)->delete($variantId);

        $this->dispatch('variant-deleted', $variantId);
    }
}
```

---

## Testing Traits

**Unit Testing Traits:**

```php
// tests/Unit/Livewire/Traits/ProductFormValidationTest.php
use App\Livewire\Products\Management\ProductForm;
use Livewire\Livewire;

test('validates required product name', function () {
    Livewire::test(ProductForm::class)
        ->set('product.nazwa', '')
        ->call('save')
        ->assertHasErrors(['product.nazwa' => 'required']);
});

test('validates unique SKU', function () {
    $existingProduct = Product::factory()->create(['sku' => 'TEST-123']);

    Livewire::test(ProductForm::class)
        ->set('product.sku', 'TEST-123')
        ->call('save')
        ->assertHasErrors(['product.sku']);
});
```

---

## Benefits of Trait Composition

**1. Maintainability:**
- Each concern in separate file
- Easy to find and update specific logic
- No scrolling through 2000+ line files

**2. Testability:**
- Test traits in isolation
- Mock services easily
- Clear separation of concerns

**3. Reusability:**
- Traits can be shared across components
- Example: `ValidationRulesTrait` used in multiple forms

**4. Readability:**
- Main component shows high-level structure
- Traits show detailed implementation
- Clear naming convention

**5. Performance:**
- PHP includes traits at compile time (zero runtime overhead)
- No impact on Livewire performance

---

## Common Mistakes

### ❌ Mistake 1: Too Many Traits

**Problem:** 20+ traits makes component hard to understand

```php
// ❌ TOO MANY
class ProductForm extends Component
{
    use Trait1, Trait2, Trait3, Trait4, Trait5,
        Trait6, Trait7, Trait8, Trait9, Trait10,
        Trait11, Trait12, Trait13, Trait14, Trait15,
        Trait16, Trait17, Trait18, Trait19, Trait20;
}
```

**Solution:** Group related traits into larger traits

```php
// ✅ REASONABLE
class ProductForm extends Component
{
    use ProductFormValidation;  // Groups all validation
    use ProductFormUpdates;     // Groups all reactive updates
    use ProductFormComputed;    // Groups all computed properties
    use ProductFormVariants;    // Groups variant-specific logic
}
```

---

### ❌ Mistake 2: Traits with State

**Problem:** Traits defining their own properties

```php
// ❌ WRONG
trait ProductFormVariants
{
    public $variantData = [];  // ❌ Trait-specific state

    public function addVariant() { /* ... */ }
}
```

**Solution:** Properties in main component, logic in trait

```php
// ✅ CORRECT
class ProductForm extends Component
{
    use ProductFormVariants;

    public $variantData = [];  // ✅ Component state
}

trait ProductFormVariants
{
    // Only methods, no properties
    public function addVariant() { /* ... */ }
}
```

---

## Refactoring Checklist

When refactoring a large component into traits:

- [ ] Identify distinct concerns (validation, updates, computed, domain logic)
- [ ] Create trait files with clear naming convention
- [ ] Move validation to `ComponentNameValidation` trait
- [ ] Move `updated*()` methods to `ComponentNameUpdates` trait
- [ ] Move computed properties to `ComponentNameComputed` trait
- [ ] Move domain logic to service classes (not traits!)
- [ ] Keep only coordination logic in main component
- [ ] Verify component < 300 lines
- [ ] Test all functionality still works
- [ ] Update documentation

---

## Related Resources

- **services.md** - Service injection patterns
- **component-structure.md** - File organization
- **validation.md** - Validation patterns
- **troubleshooting.md** - Common trait issues

---

**Last Updated:** 2025-11-04
**Maintainer:** PPM Development Team
