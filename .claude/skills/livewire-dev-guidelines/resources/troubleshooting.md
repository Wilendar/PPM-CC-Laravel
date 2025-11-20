# Livewire Troubleshooting Guide

**Last Updated:** 2025-11-04
**Skill:** livewire-dev-guidelines
**Source:** PPM Project _ISSUES_FIXES/

---

## Overview

This guide documents 9 known Livewire issues encountered in the PPM project, with root causes and proven solutions. These patterns are based on real debugging sessions (some requiring 2+ hours to resolve).

---

## Issue #1: wire:snapshot Rendering in Blade Templates

**Symptom:**
```blade
{{-- Output in browser: --}}
wire:snapshot="..." wire:effects="..." wire:id="..."

{{-- Instead of being attributes, they render as text! --}}
```

**Root Cause:**
Livewire dependency injection conflict. When component constructor has dependencies, Livewire can't properly hydrate the component, causing wire: directives to render as plain text instead of attributes.

**Example of Problem Code:**
```php
// ❌ PROBLEMATIC
class ProductForm extends Component
{
    public function __construct(
        protected ProductService $productService,  // ❌ DI in constructor
    ) {}

    public Product $product;

    public function mount(?int $productId = null)
    {
        $this->product = $productId
            ? Product::findOrFail($productId)
            : new Product();
    }
}
```

**Solution:**
Use `app()` helper or property injection instead of constructor injection.

```php
// ✅ CORRECT: No constructor DI
class ProductForm extends Component
{
    public Product $product;

    public function mount(?int $productId = null)
    {
        $this->product = $productId
            ? Product::findOrFail($productId)
            : new Product();
    }

    public function save()
    {
        $this->validate();

        // Use app() helper for services
        app(ProductService::class)->save($this->product);

        $this->redirect(route('admin.products.index'));
    }
}
```

**Alternative: Property Injection (Livewire 3)**
```php
// ✅ ALSO CORRECT: Property injection (Livewire 3 feature)
class ProductForm extends Component
{
    public Product $product;

    // Livewire injects service on each request
    protected function getProductServiceProperty()
    {
        return app(ProductService::class);
    }

    public function save()
    {
        $this->validate();
        $this->productService->save($this->product);
    }
}
```

**Prevention:**
- NEVER use constructor dependency injection in Livewire components
- Use `app()` helper or property injection instead
- Add this to your pre-commit hook/linter

---

## Issue #2: CategoryPicker Cross-Contamination

**Symptom:**
When multiple CategoryPicker components are used on the same page, selecting a category in one picker updates ALL pickers to the same selection.

**Root Cause:**
Shared property names without unique component instances. Livewire uses property names as keys for state hydration. When multiple components have the same property names, they can interfere with each other.

**Example of Problem Code:**
```php
// ❌ PROBLEMATIC
class CategoryPicker extends Component
{
    public $selectedCategories = [];  // ❌ Same name in all instances

    public function toggleCategory($categoryId)
    {
        // This updates ALL instances!
        $this->selectedCategories[] = $categoryId;
    }
}
```

**Blade Usage:**
```blade
{{-- Both pickers share the same state! --}}
<livewire:category-picker :selected="$productCategories" />
<livewire:category-picker :selected="$filterCategories" />
```

**Solution:**
Use unique `wire:key` for each component instance.

```blade
{{-- ✅ CORRECT: Unique wire:key --}}
<livewire:category-picker
    wire:key="product-category-picker"
    :selected="$productCategories" />

<livewire:category-picker
    wire:key="filter-category-picker"
    :selected="$filterCategories" />
```

**Alternative: Component-Level Unique ID:**
```php
// ✅ ALSO CORRECT: Generate unique ID in mount
class CategoryPicker extends Component
{
    public $selectedCategories = [];
    public $pickerId;  // Unique identifier

    public function mount(array $selected = [])
    {
        $this->pickerId = uniqid('picker_');
        $this->selectedCategories = $selected;
    }

    public function render()
    {
        return view('livewire.category-picker', [
            'uniqueId' => $this->pickerId,  // Pass to view
        ]);
    }
}
```

**Prevention:**
- ALWAYS use `wire:key` on reusable components
- Use unique, descriptive keys (not just numbers)
- Test with multiple instances on same page

---

## Issue #3: Button Inside Form Causes Unwanted Submit

**Symptom:**
Clicking a button inside a Livewire form triggers form submission, even though the button is meant for a different action (e.g., "Add Variant" button).

**Root Cause:**
HTML default behavior: `<button>` without explicit `type` defaults to `type="submit"`. When clicked, it submits the nearest parent form.

**Example of Problem Code:**
```blade
{{-- ❌ PROBLEMATIC --}}
<form wire:submit="save">
    <input wire:model="product.nazwa" />

    {{-- This button submits the form! --}}
    <button wire:click="addVariant">
        Dodaj wariant
    </button>

    <button type="submit">Zapisz</button>
</form>
```

**Solution:**
Explicitly set `type="button"` on non-submit buttons.

```blade
{{-- ✅ CORRECT --}}
<form wire:submit="save">
    <input wire:model="product.nazwa" />

    {{-- This button does NOT submit the form --}}
    <button type="button" wire:click="addVariant">
        Dodaj wariant
    </button>

    <button type="submit">Zapisz</button>
</form>
```

**Prevention:**
- ALWAYS specify `type="button"` for action buttons
- Only use `type="submit"` for actual submit buttons
- Add ESLint rule to catch missing `type` attributes

---

## Issue #4: Missing wire:key in Loops Causes Update Issues

**Symptom:**
When adding/removing items in a list, Livewire updates the wrong items or duplicates items.

**Example:**
- User deletes item #2
- Livewire deletes item #3 instead
- Or: Item #2 disappears but its data appears in item #3

**Root Cause:**
Livewire can't track individual items without `wire:key`. It uses array indices by default, which change when items are added/removed.

**Example of Problem Code:**
```blade
{{-- ❌ PROBLEMATIC --}}
@foreach($variants as $index => $variant)
    <div>
        {{ $variant['nazwa'] }}
        <button wire:click="deleteVariant({{ $index }})">
            Usuń
        </button>
    </div>
@endforeach
```

**What happens:**
1. Initial state: [Variant A, Variant B, Variant C] (indices: 0, 1, 2)
2. Delete Variant B (index 1)
3. New state: [Variant A, Variant C] (indices: 0, 1)
4. Livewire sees index 2 disappeared, so it removes the last item (Variant C)!

**Solution:**
Always use `wire:key` with a unique, stable identifier.

```blade
{{-- ✅ CORRECT: wire:key with unique ID --}}
@foreach($variants as $variant)
    <div wire:key="variant-{{ $variant->id }}">
        {{ $variant->nazwa }}
        <button type="button" wire:click="deleteVariant({{ $variant->id }})">
            Usuń
        </button>
    </div>
@endforeach
```

**For Arrays Without IDs:**
```blade
{{-- ✅ CORRECT: Generate unique keys --}}
@foreach($dynamicFields as $index => $field)
    <div wire:key="field-{{ $index }}-{{ $field['name'] }}">
        {{-- Use combination of index + unique field value --}}
    </div>
@endforeach
```

**Prevention:**
- ALWAYS use `wire:key` in `@foreach` loops
- Use stable identifiers (IDs, not array indices)
- Test add/remove operations thoroughly

---

## Issue #5: CSS z-index Stacking Context Conflicts

**Symptom:**
Modals appear behind other elements, or dropdowns are cut off by parent containers.

**Root Cause:**
CSS stacking context conflicts. When multiple elements have z-index, the stacking order depends on their stacking contexts, not just the z-index value.

**Example of Problem Code:**
```blade
{{-- ❌ PROBLEMATIC --}}
<div class="relative z-10">  {{-- Parent creates stacking context --}}
    <div class="modal z-[9999]">  {{-- Modal trapped in parent context! --}}
        Modal content
    </div>
</div>
```

**Why This Fails:**
- Parent `relative z-10` creates new stacking context
- Child `z-[9999]` only works within parent's context
- Modal can't appear above elements outside parent

**Solution:**
Use fixed positioning and dedicated CSS classes.

```blade
{{-- ✅ CORRECT --}}
<div class="relative">  {{-- No z-index on parent --}}
    <button>Open Modal</button>
</div>

{{-- Modal outside parent, uses dedicated CSS class --}}
<div class="modal-overlay">  {{-- Dedicated CSS with proper z-index --}}
    <div class="modal-content">
        Modal content
    </div>
</div>
```

**CSS:**
```css
/* resources/css/components/modal.css */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: var(--z-modal-overlay);  /* 1050 */
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: relative;
    z-index: var(--z-modal-content);  /* 1051 */
    margin: 50px auto;
    max-width: 600px;
}
```

**Z-index Scale (PPM Project):**
```css
/* tailwind.config.js or CSS variables */
--z-base: 0;
--z-dropdown: 1000;
--z-sticky: 1020;
--z-fixed: 1030;
--z-modal-backdrop: 1040;
--z-modal-overlay: 1050;
--z-modal-content: 1051;
--z-popover: 1060;
--z-tooltip: 1070;
```

**Prevention:**
- NEVER use arbitrary z-index values (`z-[9999]`)
- Use dedicated CSS classes for modals/dropdowns
- Follow project's z-index scale
- Test with browser DevTools (check stacking contexts)

---

## Issue #6: Vite Manifest Caching in Production

**Symptom:**
After deploying new frontend changes, users see old CSS/JS. Hard refresh (Ctrl+F5) shows new version, but normal refresh doesn't.

**Root Cause:**
Browser caches `manifest.json` or old asset files. Vite generates new hashed filenames on each build, but if manifest.json is cached, Laravel serves old asset URLs.

**Example:**
```
Old manifest: app-abc123.css
New manifest: app-xyz789.css
Browser cached: manifest.json (still references abc123)
Result: User sees old styles!
```

**Solution 1: Clear Server-Side Caches**
```bash
# After deployment, run:
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# If using OPcache:
php artisan opcache:clear
```

**Solution 2: Verify Vite Manifest Location**
```php
// Check that manifest.json is in correct location
// Should be: public/build/manifest.json
ls -la public/build/manifest.json
```

**Solution 3: Add Cache Headers (Nginx/Apache)**
```nginx
# Nginx: Prevent caching of manifest.json
location = /build/manifest.json {
    expires -1;
    add_header Cache-Control "no-cache, no-store, must-revalidate";
}

# Cache hashed assets forever (they have unique names)
location ~* /build/assets/.*\.(css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

**Solution 4: Deployment Script**
```bash
#!/bin/bash
# deploy.sh

# Build frontend
npm run build

# Clear Laravel caches
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Restart PHP-FPM (to clear OPcache)
sudo systemctl restart php8.3-fpm

echo "✅ Deployment complete! Manifest and caches cleared."
```

**Prevention:**
- Include cache clearing in deployment scripts
- Test deployment process on staging first
- Verify manifest.json updates after deployment
- Use deployment automation (not manual FTP uploads)

---

## Issue #7: Eloquent Model Property Not Updating in Livewire

**Symptom:**
Changing a model property in Livewire doesn't reflect in the UI or database.

**Example:**
```php
public function updatePrice($newPrice)
{
    $this->product->cena = $newPrice;  // Doesn't work!
}
```

**Root Cause:**
Livewire doesn't track nested object properties automatically. You must explicitly tell Livewire that the model changed.

**Solution 1: Use `$this->product = clone $this->product`**
```php
public function updatePrice($newPrice)
{
    $this->product->cena = $newPrice;
    $this->product = clone $this->product;  // Force Livewire to detect change

    // Or save immediately:
    $this->product->save();
}
```

**Solution 2: Use Separate Properties**
```php
// Instead of binding directly to model:
// wire:model="product.cena"  ❌

// Use separate property:
public $cena;

public function mount()
{
    $this->cena = $this->product->cena;
}

public function save()
{
    $this->product->cena = $this->cena;
    $this->product->save();
}
```

**Solution 3: Use `#[Locked]` for Model, Separate Props for Form**
```php
use Livewire\Attributes\Locked;

class ProductForm extends Component
{
    #[Locked]
    public Product $product;

    // Form properties (not model properties)
    public $nazwa;
    public $cena;
    public $sku;

    public function mount(?int $productId = null)
    {
        $this->product = $productId ? Product::findOrFail($productId) : new Product();

        // Populate form properties
        $this->nazwa = $this->product->nazwa;
        $this->cena = $this->product->cena;
        $this->sku = $this->product->sku;
    }

    public function save()
    {
        $this->validate();

        // Transfer form properties to model
        $this->product->fill([
            'nazwa' => $this->nazwa,
            'cena' => $this->cena,
            'sku' => $this->sku,
        ]);

        $this->product->save();
    }
}
```

**Prevention:**
- Don't bind wire:model directly to Eloquent model properties
- Use separate form properties
- Or use `clone` to force change detection
- Or save immediately after changes

---

## Issue #8: Component Not Re-rendering After Property Update

**Symptom:**
Property changes in component method, but view doesn't update.

**Example:**
```php
public $count = 0;

public function increment()
{
    $this->count++;  // Property changes...
    // But view doesn't update!
}
```

**Root Causes:**

**Cause 1: Property Not Public**
```php
// ❌ WRONG
protected $count = 0;  // Livewire can't track private/protected!

// ✅ CORRECT
public $count = 0;
```

**Cause 2: Property Not Declared**
```php
// ❌ WRONG
public function increment()
{
    $this->count++;  // Property not declared in class!
}

// ✅ CORRECT
class Counter extends Component
{
    public $count = 0;  // Declare property

    public function increment()
    {
        $this->count++;
    }
}
```

**Cause 3: Computed Property Not Using #[Computed] Attribute**
```php
// ❌ WRONG (Livewire 2 syntax)
public function getCountProperty()
{
    return $this->count * 2;
}

// ✅ CORRECT (Livewire 3 syntax)
use Livewire\Attributes\Computed;

#[Computed]
public function count()
{
    return $this->count * 2;
}
```

**Solution:**
- Ensure properties are `public`
- Declare all properties in class body
- Use `#[Computed]` attribute for computed properties (Livewire 3)
- Check browser console for Livewire errors

---

## Issue #9: Session Data Lost After Livewire Request

**Symptom:**
Data stored in session disappears after Livewire request.

**Example:**
```php
public function storeData()
{
    session(['user_preference' => 'dark_mode']);
    // Data is gone on next request!
}
```

**Root Cause:**
Livewire doesn't automatically persist session data. You must call `session()->save()` or use flash data.

**Solution 1: Use `session()->put()` + `session()->save()`**
```php
public function storeData()
{
    session()->put('user_preference', 'dark_mode');
    session()->save();  // Explicit save
}
```

**Solution 2: Use Flash Data**
```php
public function storeData()
{
    session()->flash('message', 'Data saved!');
    // Flash data automatically persists for next request only
}
```

**Solution 3: Use Component Properties Instead**
```php
class UserSettings extends Component
{
    public $theme = 'dark';

    public function mount()
    {
        $this->theme = session('user_preference', 'light');
    }

    public function updateTheme($newTheme)
    {
        $this->theme = $newTheme;
        session()->put('user_preference', $newTheme);
        session()->save();
    }
}
```

**Prevention:**
- Call `session()->save()` after session writes
- Or use flash data for one-time messages
- Or store in component properties
- Test session persistence after Livewire actions

---

## Debugging Techniques

### 1. Enable Livewire Debug Mode

```blade
{{-- Add to layout --}}
@livewireScripts
<script>
    Livewire.onError((message) => {
        console.error('Livewire Error:', message);
        return false; // Prevent default error handling
    });
</script>
```

### 2. Log Lifecycle Events

```php
class ProductForm extends Component
{
    public function hydrate()
    {
        logger('HYDRATE: ' . json_encode($this->all()));
    }

    public function dehydrate()
    {
        logger('DEHYDRATE: ' . json_encode($this->all()));
    }

    public function updated($property, $value)
    {
        logger("UPDATED: {$property} = " . json_encode($value));
    }
}
```

### 3. Use Browser DevTools

**Check Livewire Requests:**
- Open Network tab
- Filter by "livewire/update"
- Check request payload and response

**Check Wire Directives:**
```javascript
// In browser console
console.log(Livewire.all());  // All Livewire components
console.log(Livewire.find('component-id'));  // Specific component
```

### 4. Use Laravel Telescope

```bash
# Install Telescope (if not installed)
composer require laravel/telescope

# View Livewire requests in Telescope UI
# http://localhost/telescope/requests
```

---

## Prevention Checklist

Before deploying Livewire component:

- [ ] No constructor dependency injection
- [ ] `wire:key` on all reusable components
- [ ] `wire:key` in all `@foreach` loops
- [ ] `type="button"` on non-submit buttons
- [ ] No arbitrary z-index values (use dedicated CSS)
- [ ] Form properties separate from Eloquent model
- [ ] All properties declared as `public`
- [ ] `#[Computed]` attribute on computed properties
- [ ] `session()->save()` after session writes
- [ ] Deployment script clears caches

---

## Related Resources

- **component-structure.md** - File organization
- **lifecycle.md** - Lifecycle hooks details
- **performance.md** - Optimization patterns

---

## Getting Help

**If you encounter a new Livewire issue:**

1. Check this troubleshooting guide first
2. Check `_ISSUES_FIXES/` directory for similar issues
3. Enable debug logging (see Debugging Techniques above)
4. Document the issue if resolution takes > 2 hours (use issue-documenter skill)
5. Update this guide with the solution

---

**Last Updated:** 2025-11-04
**Maintainer:** PPM Development Team
**Contributing:** Add new issues as they're discovered and solved
