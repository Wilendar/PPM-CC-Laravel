# Livewire Development Guidelines

**Type:** domain
**Enforcement:** require
**Priority:** critical
**Version:** 1.0.0
**Last Updated:** 2025-11-04

---

## Quick Reference

This skill provides comprehensive Livewire 3.x development patterns for the PPM project. The project uses Livewire as the PRIMARY UI framework with 60+ components implementing complex features like product management, category trees, variant systems, and PrestaShop integration.

**Key Principles:**
- Single Responsibility Components (max 300 lines)
- Trait Composition over Monolithic Classes
- Service Injection for Business Logic
- Wire:model optimization (defer/blur)
- Alpine.js coordination for UI state

---

## When to Use This Skill

**Auto-triggers on:**
- Keywords: `livewire`, `component`, `wire:`, `@livewire`, `alpine`
- File edits: `app/Livewire/**/*.php`, `resources/views/livewire/**/*.blade.php`
- Code patterns: `extends Component`, `wire:model`, `wire:click`

**Manually invoke when:**
- Creating new Livewire components
- Refactoring large components (> 300 lines)
- Implementing reactive forms
- Debugging Livewire lifecycle issues
- Integrating Alpine.js with Livewire

---

## Core Principles

### 1. Single Responsibility Principle

**RULE:** Each component should have ONE clear purpose.

**Example - ProductForm Component (REFACTORED):**
```php
// ❌ WRONG: Monolithic component (2000+ lines)
class ProductForm extends Component
{
    // 2000 lines of mixed concerns...
}

// ✅ CORRECT: Single Responsibility with Trait Composition
class ProductForm extends Component
{
    use ProductFormValidation;      // Validation rules
    use ProductFormUpdates;         // Reactive updates
    use ProductFormComputed;        // Computed properties
    use ProductFormVariants;        // Variant logic

    // Only coordination logic here (~100 lines)

    public function save()
    {
        $this->validate();
        app(ProductFormSaver::class)->save($this);
    }
}
```

**File Size Limits:**
- **Standard:** Max 300 lines per component
- **Exceptional:** Max 500 lines (requires justification)
- **If larger:** Extract traits or create sub-components

---

### 2. Trait Composition Pattern

**RULE:** Extract logic into traits for reusability and maintainability.

**Common Trait Types:**
- **Validation traits:** `ComponentNameValidation`
- **Update traits:** `ComponentNameUpdates` (reactive updates)
- **Computed traits:** `ComponentNameComputed` (computed properties)
- **Domain traits:** `ComponentNameDomainLogic` (specific feature logic)

**Example - ProductForm Traits:**

```php
// app/Livewire/Products/Management/Traits/ProductFormValidation.php
trait ProductFormValidation
{
    public function rules()
    {
        return [
            'product.nazwa' => 'required|max:255',
            'product.sku' => [
                'required',
                new UniqueSKU($this->product->id ?? null)
            ],
            // ... more validation rules
        ];
    }

    protected function validationAttributes()
    {
        return [
            'product.nazwa' => 'Nazwa produktu',
            'product.sku' => 'SKU',
        ];
    }
}

// app/Livewire/Products/Management/Traits/ProductFormUpdates.php
trait ProductFormUpdates
{
    public function updatedProductProductTypeId($value)
    {
        // React to product type change
        $this->resetDynamicFields();
    }

    public function updatedProductHasVariants($value)
    {
        // React to has_variants toggle
        if (!$value) {
            $this->clearVariants();
        }
    }
}

// app/Livewire/Products/Management/Traits/ProductFormComputed.php
trait ProductFormComputed
{
    #[Computed]
    public function availableCategories()
    {
        return Category::active()->orderBy('nazwa')->get();
    }

    #[Computed]
    public function productTypeFields()
    {
        return $this->product->productType?->fields ?? [];
    }
}
```

**See:** `resources/traits.md` for more patterns

---

### 3. Service Injection Pattern

**RULE:** Business logic belongs in Services, NOT in components.

**Example - ProductForm with Services:**

```php
class ProductForm extends Component
{
    public Product $product;

    // ❌ WRONG: Business logic in component
    public function save()
    {
        $this->validate();

        // 100 lines of complex business logic...
        DB::transaction(function () {
            $this->product->save();
            $this->syncCategories();
            $this->syncShopData();
            $this->syncVariants();
            $this->updatePrices();
            // ... more complex logic
        });
    }

    // ✅ CORRECT: Service injection
    public function save()
    {
        $this->validate();

        app(ProductFormSaver::class)->save($this);

        $this->dispatch('product-saved', $this->product->id);
    }
}
```

**Service Implementation:**
```php
// app/Livewire/Products/Management/Services/ProductFormSaver.php
class ProductFormSaver
{
    public function __construct(
        protected ProductMultiStoreManager $multiStore,
        protected ProductCategoryManager $categories,
    ) {}

    public function save(ProductForm $component): void
    {
        DB::transaction(function () use ($component) {
            $component->product->save();

            $this->categories->sync($component->product, $component->selectedCategories);
            $this->multiStore->syncShopData($component->product, $component->shopData);

            // ... more orchestrated operations
        });
    }
}
```

**Benefits:**
- Testable (mock services easily)
- Reusable (same service in different components)
- Maintainable (business logic in one place)
- Single Responsibility (component = UI, service = logic)

**See:** `resources/services.md` for more patterns

---

### 4. Lifecycle Hooks Mastery

**Key Lifecycle Methods:**
- `mount()` - Component initialization
- `updated($property, $value)` - Any property changed
- `updatedPropertyName($value)` - Specific property changed
- `hydrate()` - Before each request (except initial mount)
- `dehydrate()` - After each request

**Example - CategoryTree Component:**

```php
class CategoryTree extends Component
{
    public $categories = [];
    public $expandedNodes = [];

    // 1. Mount: Initialize component
    public function mount()
    {
        $this->loadCategories();
        $this->loadExpandedState();
    }

    // 2. Hydrate: Restore state on each request
    public function hydrate()
    {
        // Restore expanded nodes from session
        $this->expandedNodes = session('category_tree.expanded', []);
    }

    // 3. Updated: React to property changes
    public function updatedExpandedNodes($value)
    {
        // Save expanded state
        session(['category_tree.expanded' => $this->expandedNodes]);
    }

    // 4. Dehydrate: Save state after each request
    public function dehydrate()
    {
        // Clean up temporary data
        unset($this->temporaryData);
    }
}
```

**See:** `resources/lifecycle.md` for complete reference

---

### 5. Wire:model Optimization

**RULE:** Use `.defer` or `.blur` to reduce server round-trips.

```blade
{{-- ❌ WRONG: Live updates on every keystroke --}}
<input type="text" wire:model="product.nazwa">

{{-- ✅ GOOD: Deferred update (on submit/blur) --}}
<input type="text" wire:model.defer="product.nazwa">

{{-- ✅ BETTER: Update on blur (better UX for validation) --}}
<input type="text" wire:model.blur="product.nazwa">

{{-- ✅ BEST: Live updates only when needed (search, filters) --}}
<input type="text" wire:model.live.debounce.300ms="searchQuery">
```

**Performance Impact:**
- `wire:model` (live): 100 keystrokes = 100 server requests
- `wire:model.defer`: 100 keystrokes = 1 server request (on submit)
- `wire:model.blur`: 100 keystrokes = 1 server request (on blur)
- `wire:model.live.debounce.300ms`: 100 keystrokes = ~10 requests (debounced)

**See:** `resources/wire-model.md` for complete guide

---

## Common Patterns

### Pattern 1: Master-Detail Form (ProductForm)

**Use Case:** Complex form with multiple tabs and sub-sections

**Structure:**
```php
// Main component: Coordination
class ProductForm extends Component
{
    use ProductFormValidation;
    use ProductFormUpdates;
    use ProductFormComputed;

    public Product $product;
    public int $activeTab = 0;

    public function mount(?int $productId = null)
    {
        $this->product = $productId
            ? Product::findOrFail($productId)
            : new Product();
    }

    public function save()
    {
        $this->validate();
        app(ProductFormSaver::class)->save($this);

        session()->flash('message', 'Produkt zapisany!');
        $this->redirect(route('admin.products.index'));
    }

    public function render()
    {
        return view('livewire.products.management.product-form');
    }
}
```

**Blade Template - Tab Structure:**
```blade
<div>
    <div class="tabs-enterprise">
        <button wire:click="$set('activeTab', 0)"
                class="{{ $activeTab === 0 ? 'active' : '' }}">
            Podstawowe
        </button>
        <button wire:click="$set('activeTab', 1)"
                class="{{ $activeTab === 1 ? 'active' : '' }}">
            Kategorie
        </button>
        <!-- More tabs... -->
    </div>

    <form wire:submit="save">
        @if($activeTab === 0)
            @include('livewire.products.management.tabs.basic')
        @elseif($activeTab === 1)
            @include('livewire.products.management.tabs.categories')
        @endif

        <button type="submit" class="btn-enterprise-primary">
            Zapisz
        </button>
    </form>
</div>
```

**See:** `resources/component-structure.md`

---

### Pattern 2: Reactive Tree with Drag & Drop (CategoryTree)

**Use Case:** Hierarchical data with drag & drop reordering

**Implementation:**
```php
class CategoryTree extends Component
{
    public $categories = [];
    public $expandedNodes = [];

    #[On('category-moved')]
    public function handleCategoryMoved($categoryId, $newParentId, $newPosition)
    {
        DB::transaction(function () use ($categoryId, $newParentId, $newPosition) {
            $category = Category::findOrFail($categoryId);

            $category->update([
                'parent_id' => $newParentId,
                'sort_order' => $newPosition,
            ]);

            // Reorder siblings
            $this->reorderSiblings($newParentId);
        });

        $this->loadCategories();
        $this->dispatch('category-tree-updated');
    }

    public function render()
    {
        return view('livewire.admin.categories.category-tree');
    }
}
```

**Alpine.js Integration:**
```blade
<div x-data="categoryTreeData()">
    <div x-sortable="handleSort">
        @foreach($categories as $category)
            <div x-sortable:item="{{ $category->id }}"
                 class="category-node">
                {{ $category->nazwa }}
            </div>
        @endforeach
    </div>
</div>

<script>
function categoryTreeData() {
    return {
        handleSort(item, position) {
            @this.handleCategoryMoved(
                item.dataset.categoryId,
                item.dataset.parentId,
                position
            );
        }
    }
}
</script>
```

**See:** `resources/alpine-integration.md`

---

### Pattern 3: Reusable Component (CategoryPicker)

**Use Case:** Reusable component with events

**Component:**
```php
class CategoryPicker extends Component
{
    public $selectedCategories = [];
    public $shopId = null;
    public $multiple = true;

    public function mount(array $selected = [], ?int $shopId = null, bool $multiple = true)
    {
        $this->selectedCategories = $selected;
        $this->shopId = $shopId;
        $this->multiple = $multiple;
    }

    public function toggleCategory($categoryId)
    {
        if ($this->multiple) {
            if (in_array($categoryId, $this->selectedCategories)) {
                $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
            } else {
                $this->selectedCategories[] = $categoryId;
            }
        } else {
            $this->selectedCategories = [$categoryId];
        }

        // Emit event to parent
        $this->dispatch('categories-selected', $this->selectedCategories);
    }

    #[Computed]
    public function availableCategories()
    {
        $query = Category::active();

        if ($this->shopId) {
            $query->forShop($this->shopId);
        }

        return $query->orderBy('nazwa')->get();
    }

    public function render()
    {
        return view('livewire.products.category-picker');
    }
}
```

**Usage in Parent Component:**
```blade
<livewire:products.category-picker
    :selected="$product->categories->pluck('id')->toArray()"
    :shop-id="$currentShop->id"
    :multiple="true"
    @categories-selected="$set('selectedCategories', $event.detail)" />
```

**See:** `resources/component-structure.md`

---

### Pattern 4: Real-time Progress Tracking (JobProgressBar)

**Use Case:** Display job progress in real-time

**Component:**
```php
class JobProgressBar extends Component
{
    public $jobId;
    public $progress = 0;
    public $status = 'pending';

    public function mount($jobId)
    {
        $this->jobId = $jobId;
        $this->loadProgress();
    }

    public function loadProgress()
    {
        $job = SyncJob::find($this->jobId);

        if (!$job) {
            return;
        }

        $this->progress = $job->progress;
        $this->status = $job->status;

        if ($this->status === 'completed') {
            $this->dispatch('job-completed', $this->jobId);
        }
    }

    public function render()
    {
        return view('livewire.components.job-progress-bar');
    }
}
```

**Blade Template with Polling:**
```blade
<div wire:poll.1s="loadProgress">
    <div class="progress-bar">
        <div class="progress-fill" style="width: {{ $progress }}%"></div>
    </div>

    <div class="progress-status">
        @if($status === 'pending')
            Oczekuje...
        @elseif($status === 'processing')
            Przetwarzanie... {{ $progress }}%
        @elseif($status === 'completed')
            ✅ Ukończono!
        @elseif($status === 'failed')
            ❌ Błąd!
        @endif
    </div>
</div>
```

**See:** `resources/performance.md` for polling best practices

---

## Anti-Patterns (ZAKAZ!)

### ❌ Anti-Pattern 1: Monolithic Components

**Problem:** Component with 2000+ lines handling multiple concerns

```php
// ❌ WRONG
class ProductForm extends Component
{
    // 500 lines of properties
    // 500 lines of validation
    // 500 lines of business logic
    // 500 lines of UI helpers
    // Total: 2000+ lines of spaghetti
}
```

**Solution:** Extract into traits and services

```php
// ✅ CORRECT
class ProductForm extends Component
{
    use ProductFormValidation;    // 100 lines
    use ProductFormUpdates;       // 80 lines
    use ProductFormComputed;      // 60 lines

    // Main component: 100 lines (coordination only)
}
```

---

### ❌ Anti-Pattern 2: Business Logic in Components

**Problem:** Complex business logic directly in component methods

```php
// ❌ WRONG
public function syncToPrestaShop()
{
    // 200 lines of PrestaShop API calls, transformations, error handling...
}
```

**Solution:** Use Services

```php
// ✅ CORRECT
public function syncToPrestaShop()
{
    app(PrestaShopSyncService::class)->syncProduct($this->product);

    $this->dispatch('sync-initiated');
}
```

---

### ❌ Anti-Pattern 3: N+1 Queries in Computed Properties

**Problem:** Computed property triggers queries in loops

```php
// ❌ WRONG
#[Computed]
public function productsWithCategories()
{
    return Product::all(); // N+1 when accessing $product->categories
}
```

**Solution:** Eager load relationships

```php
// ✅ CORRECT
#[Computed]
public function productsWithCategories()
{
    return Product::with('categories', 'productType')->get();
}
```

---

### ❌ Anti-Pattern 4: Forgetting wire:key in Loops

**Problem:** Livewire can't track items properly without wire:key

```blade
{{-- ❌ WRONG --}}
@foreach($products as $product)
    <div>{{ $product->nazwa }}</div>
@endforeach

{{-- ✅ CORRECT --}}
@foreach($products as $product)
    <div wire:key="product-{{ $product->id }}">
        {{ $product->nazwa }}
    </div>
@endforeach
```

---

### ❌ Anti-Pattern 5: Not Using defer/blur on Forms

**Problem:** Every keystroke = server request

```blade
{{-- ❌ WRONG: 100 keystrokes = 100 requests --}}
<input type="text" wire:model="product.nazwa">

{{-- ✅ CORRECT: 100 keystrokes = 1 request --}}
<input type="text" wire:model.blur="product.nazwa">
```

---

## Scripts & Tools

### Test Livewire Component
```bash
# Test component rendering
php artisan livewire:test ProductForm
```

### Debug Livewire Lifecycle
```php
// Add to component for debugging
public function hydrate()
{
    logger('Hydrate: ' . json_encode($this->all()));
}

public function dehydrate()
{
    logger('Dehydrate: ' . json_encode($this->all()));
}
```

---

## Resource Files

- **component-structure.md** - File organization, naming conventions
- **traits.md** - Trait composition patterns (ProductForm example)
- **services.md** - Service injection patterns
- **validation.md** - Livewire validation + real-time validation
- **lifecycle.md** - Lifecycle hooks comprehensive guide
- **wire-model.md** - Data binding patterns (live/defer/blur)
- **wire-actions.md** - Actions, parameters, confirmation modals
- **alpine-integration.md** - Alpine.js + Livewire coordination
- **performance.md** - Lazy loading, polling, wire:key, optimization
- **troubleshooting.md** - 9 known Livewire issues + solutions

---

## Related Skills

- **frontend-dev-guidelines** - When working with Blade templates, CSS, Alpine.js
- **laravel-dev-guidelines** - When implementing services, models, controllers
- **ppm-architecture-compliance** - For project-specific patterns
- **livewire-troubleshooting** - When encountering Livewire bugs

---

## Project-Specific Notes

### PPM Livewire Components Structure

**Main Modules:**
- `Dashboard/` - Admin dashboard widgets
- `Products/` - Product management (60% of components!)
  - `Listing/ProductList` - Main product grid
  - `Management/ProductForm` - 12-tab form (refactored!)
  - `Categories/CategoryTree` - 5-level tree
  - `CategoryPicker` - Reusable picker
- `Admin/` - Admin panel features
  - `Shops/` - PrestaShop shop management
  - `Variants/` - Attribute system
  - `Compatibility/` - Vehicle compatibility
  - `Users/` - User management

**Component Count:** 60+ components total

**Largest Components (Post-Refactoring):**
- `ProductForm` - 250 lines (was 2182!)
- `CategoryTree` - 180 lines
- `ProductList` - 220 lines

**Smallest Components:**
- `ErrorDetailsModal` - 45 lines
- `JobProgressBar` - 60 lines
- `CategoryPreviewModal` - 80 lines

---

## Success Checklist

Before finishing Livewire component work, verify:

- [ ] Component < 300 lines (or justified if > 300)
- [ ] Business logic extracted to Services
- [ ] Traits used for code organization (if > 200 lines)
- [ ] wire:model uses .defer or .blur (except search/filters)
- [ ] wire:key present in all @foreach loops
- [ ] Computed properties eager load relationships
- [ ] No N+1 queries (test with Telescope)
- [ ] Events use $this->dispatch() (Livewire 3 syntax)
- [ ] Validation rules in dedicated trait or method
- [ ] Alpine.js integration follows coordination pattern

---

**Skill Version:** 1.0.0
**Last Updated:** 2025-11-04
**Maintainer:** PPM Development Team
**Feedback:** Update this skill based on real-world usage patterns
