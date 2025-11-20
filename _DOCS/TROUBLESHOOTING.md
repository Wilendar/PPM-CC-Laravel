# TROUBLESHOOTING GUIDE - PPM Project

**Cel:** Centralny przewodnik rozwiązywania problemów w projekcie PPM-CC-Laravel
**Ostatnia aktualizacja:** 2025-11-04

---

## 📋 SPIS TREŚCI

1. [Quick Reference - Top Issues](#quick-reference)
2. [Livewire Issues](#livewire-issues)
3. [CSS & Frontend Issues](#css-frontend-issues)
4. [Deployment Issues](#deployment-issues)
5. [PrestaShop Integration Issues](#prestashop-integration-issues)
6. [Database & Performance Issues](#database-performance-issues)
7. [Debugging Techniques](#debugging-techniques)
8. [Prevention Checklist](#prevention-checklist)
9. [Skill-Specific Guides](#skill-specific-guides)

---

## 🔥 QUICK REFERENCE

### Top 5 Critical Issues

| Issue | Quick Fix | Detailed Guide |
|-------|-----------|----------------|
| **wire:snapshot renders as text** | Remove constructor DI, use `app()` helper | [LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md) |
| **Missing styles after deployment** | Deploy ALL assets, not just changed files | [CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md](../_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md) |
| **ViteException: Unable to locate file** | Add styles to EXISTING CSS files | [VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md](../_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md) |
| **CategoryPicker cross-contamination** | Add unique `wire:key` to each picker | [CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md](../_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md) |
| **Button in form causes submit** | Add explicit `type="button"` | [BUTTON_IN_FORM_WITHOUT_TYPE.md](../_ISSUES_FIXES/BUTTON_IN_FORM_WITHOUT_TYPE.md) |

### Emergency Checklist

```bash
# 1. Clear all Laravel caches
php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear

# 2. Check production CSS files exist (HTTP 200)
curl -I https://ppm.mpptrade.pl/public/build/assets/app-*.css

# 3. Verify Livewire is loaded
# Browser console: typeof Livewire !== 'undefined'

# 4. Check for console errors
# Use: node _TOOLS/full_console_test.cjs

# 5. Review Laravel logs
tail -f storage/logs/laravel.log
```

---

## 🎯 LIVEWIRE ISSUES

### Issue #1: wire:snapshot Renders as Text

**Symptom:** Livewire component shows raw `wire:snapshot` code instead of UI

**Example:**
```html
<!-- PROBLEM: This renders literally on page -->
<div wire:snapshot="{&quot;data&quot;:{...}}"></div>
```

**Root Cause:** Constructor dependency injection conflicts with Livewire 3.x hydration

**Solution:**
```php
// ❌ WRONG
class MyComponent extends Component
{
    public function __construct(
        private ProductService $productService // ← Constructor DI = problem!
    ) {}
}

// ✅ CORRECT
class MyComponent extends Component
{
    public function mount()
    {
        $productService = app(ProductService::class); // ← Use app() helper
        // ... use service
    }
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md)

---

### Issue #2: CategoryPicker Cross-Contamination

**Symptom:** Multiple CategoryPicker instances share the same state

**Example:**
```blade
{{-- PROBLEM: Both pickers update together --}}
<livewire:category-picker :selected="$categoryA" />
<livewire:category-picker :selected="$categoryB" />
```

**Root Cause:** Missing unique `wire:key`

**Solution:**
```blade
{{-- ✅ CORRECT: Each picker has unique wire:key --}}
<livewire:category-picker :selected="$categoryA" wire:key="picker-a" />
<livewire:category-picker :selected="$categoryB" wire:key="picker-b" />
```

**📖 Detailed Guide:** [_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md](../_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md)

---

### Issue #3: Button in Form Without type="button"

**Symptom:** Action button inside form causes unwanted submit (closes modal, redirects page)

**Example:**
```html
<!-- ❌ WRONG: Default type="submit" triggers form submit -->
<form wire:submit="saveProduct">
    <button wire:click="addVariant">Add Variant</button>
</form>
```

**Root Cause:** HTML `<button>` defaults to `type="submit"` when inside `<form>`

**Solution:**
```html
<!-- ✅ CORRECT: Explicit type="button" prevents submit -->
<form wire:submit="saveProduct">
    <button type="button" wire:click="addVariant">Add Variant</button>
    <button type="submit">Save Product</button>
</form>
```

**📖 Detailed Guide:** [_ISSUES_FIXES/BUTTON_IN_FORM_WITHOUT_TYPE.md](../_ISSUES_FIXES/BUTTON_IN_FORM_WITHOUT_TYPE.md)

---

### Issue #4: Missing wire:key in Loops

**Symptom:** Wrong items get updated/deleted in Livewire loops

**Example:**
```blade
{{-- ❌ WRONG: Livewire can't track which row is which --}}
@foreach($products as $product)
    <tr>
        <td>{{ $product->name }}</td>
        <td><button wire:click="delete({{ $product->id }})">Delete</button></td>
    </tr>
@endforeach
```

**Root Cause:** Livewire needs stable identifiers to track DOM elements

**Solution:**
```blade
{{-- ✅ CORRECT: wire:key with stable ID --}}
@foreach($products as $product)
    <tr wire:key="product-{{ $product->id }}">
        <td>{{ $product->name }}</td>
        <td><button wire:click="delete({{ $product->id }})">Delete</button></td>
    </tr>
@endforeach
```

**📖 Detailed Guide:** [livewire-dev-guidelines/resources/troubleshooting.md](../.claude/skills/guidelines/livewire-dev-guidelines/resources/troubleshooting.md)

---

### Issue #5: Livewire Events - emit() vs dispatch()

**Symptom:** `emit()` method doesn't work in Livewire 3.x

**Root Cause:** Livewire 3.x replaced `emit()` with `dispatch()`

**Solution:**
```php
// ❌ Livewire 2.x (deprecated)
$this->emit('productUpdated', $productId);

// ✅ Livewire 3.x
$this->dispatch('productUpdated', productId: $productId);
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md)

---

### Issue #6: wire:poll with Conditional Rendering

**Symptom:** `wire:poll` doesn't work inside `@if` blocks

**Root Cause:** Livewire can't maintain polling interval when element is conditionally rendered

**Solution:**
```blade
{{-- ❌ WRONG: Polling stops when condition changes --}}
@if($showProgress)
    <div wire:poll.1s="checkProgress">{{ $progress }}%</div>
@endif

{{-- ✅ CORRECT: Always render, hide with CSS --}}
<div wire:poll.1s="checkProgress" class="{{ $showProgress ? '' : 'hidden' }}">
    {{ $progress }}%
</div>
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md)

---

### Issue #7: Dependency Injection Conflicts

**Symptom:** Non-nullable property error in Livewire 3.x components

**Root Cause:** Livewire 3.x hydration conflicts with constructor DI

**Solution:**
```php
// ❌ WRONG: Constructor DI
private ProductService $service; // ← Non-nullable property error

// ✅ CORRECT: Property DI or app() helper
#[Locked]
public ProductService $service; // Option 1: Property injection

public function mount()
{
    $service = app(ProductService::class); // Option 2: app() helper
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md)

---

### Issue #8: x-teleport with wire:id

**Symptom:** `wire:click` doesn't work on teleported elements

**Root Cause:** Livewire needs `wire:id` to track teleported elements

**Solution:**
```blade
{{-- ❌ WRONG: wire:click doesn't work --}}
<div x-teleport="body">
    <button wire:click="close">Close</button>
</div>

{{-- ✅ CORRECT: Add wire:id --}}
<div x-teleport="body" wire:id="modal-{{ $componentId }}">
    <button wire:click="close">Close</button>
</div>
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md)

---

### Issue #9: Dispatching from Queue Jobs

**Symptom:** Livewire events don't reach browser from queue jobs

**Root Cause:** Queue jobs run outside HTTP request context

**Solution:**
```php
// ❌ WRONG: Can't dispatch Livewire events from queue
public function handle()
{
    ProcessProductJob::dispatch($productId);
    $this->dispatch('processingStarted'); // ← Won't work!
}

// ✅ CORRECT: Poll for job status instead
public function handle()
{
    ProcessProductJob::dispatch($productId);
    session()->put('job_started', true);
}

// In component: Use wire:poll to check job status
public function checkJobStatus()
{
    if (session()->get('job_started')) {
        // Update UI based on job status
    }
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md)

---

## 🎨 CSS & FRONTEND ISSUES

### Issue #1: CSS Incomplete Deployment

**Symptom:** ENTIRE APPLICATION loses styles after deployment (not just new features)

**Example User Report:** "W całej Aplikacji PPM wywaliły się style!"

**Root Cause:** Deployed only "changed" CSS file, but Vite rebuilds ALL files with new hashes

**Critical Understanding:**
```bash
npm run build
# ✅ Builds ALL files with NEW hashes (content-based hashing)
# app-C7f3nhBa.css     → app-Bd75e5PJ.css  (NEW HASH!)
# components-OLD.css   → components-NEW.css (NEW HASH!)
# layout-OLD.css       → layout-NEW.css    (NEW HASH!)

# ❌ WRONG: Deploy only "changed" file
pscp components-NEW.css host:/path/
# → Manifest points to app-Bd75e5PJ.css
# → File doesn't exist on server
# → HTTP 404 → No styles!

# ✅ CORRECT: Deploy ALL files
pscp -r public/build/assets/* host:/path/
```

**Solution Checklist:**
```powershell
# 1. Build locally
npm run build

# 2. Deploy ALL assets (not selective!)
pscp -r "public\build\assets\*" host:/path/

# 3. Deploy manifest
pscp "public\build\.vite\manifest.json" host:/public/build/manifest.json

# 4. Clear caches
php artisan view:clear && cache:clear

# 5. Verify HTTP 200 for ALL core files
curl -I https://ppm.mpptrade.pl/public/build/assets/app-*.css
curl -I https://ppm.mpptrade.pl/public/build/assets/components-*.css
curl -I https://ppm.mpptrade.pl/public/build/assets/layout-*.css
```

**📖 Detailed Guide:** [_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md](../_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md)

---

### Issue #2: Vite Manifest - New CSS Files

**Symptom:** `ViteException: Unable to locate file` when adding NEW CSS file to `vite.config.js`

**Root Cause:** Laravel Vite helper caching issues on production (shared hosting, no Node.js)

**Critical Understanding:**
- **Vite runs ONLY locally** (Windows machine)
- **Production has NO Node.js/Vite** (Hostido shared hosting)
- Problem is in **Laravel Vite helper (PHP)**, not Vite (JavaScript)

**Solution: Add to EXISTING files instead**
```css
/* ❌ WRONG: Create new file */
/* resources/css/components/my-new-modal.css */

/* ✅ CORRECT: Add to existing file */
/* resources/css/admin/components.css */

/* ========================================
   MY NEW MODAL (Added 2025-11-04)
   ======================================== */
.my-new-modal {
    z-index: 11;
    background: var(--color-bg-primary);
}
```

**Existing Files (Safe to Extend):**
- `resources/css/admin/components.css` - Admin UI components, modals
- `resources/css/admin/layout.css` - Layout, grid, sidebar
- `resources/css/products/category-form.css` - Product forms
- `resources/css/components/category-picker.css` - Pickers

**When NEW file is acceptable:**
- Large module (>200 lines of styles)
- After user consultation
- Full production test completed

**📖 Detailed Guide:** [_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md](../_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md)

---

### Issue #3: CSS z-index Stacking Context

**Symptom:** Modal/dropdown appears BEHIND other elements despite `z-index: 9999`

**Root Cause:** CSS stacking context conflicts

**Solution:**
```css
/* ❌ WRONG: Inline styles or arbitrary Tailwind */
<div style="z-index: 9999;">...</div>
<div class="z-[9999]">...</div>

/* ✅ CORRECT: Dedicated CSS classes with design token scale */

/* resources/css/admin/components.css */
:root {
    --z-dropdown: 1000;
    --z-modal-overlay: 1050;
    --z-modal-content: 1051;
    --z-tooltip: 1070;
}

.dropdown-menu {
    z-index: var(--z-dropdown);
}

.modal-overlay {
    z-index: var(--z-modal-overlay);
}

.modal-content {
    z-index: var(--z-modal-content);
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md](../_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md)

---

### Issue #4: CSS Import Missing from Layout

**Symptom:** New CSS classes don't work, even after deployment

**Root Cause:** CSS file not imported in `admin.blade.php`

**Solution:**
```blade
{{-- resources/views/layouts/admin.blade.php --}}

{{-- ❌ MISSING: New CSS file not in @vite --}}
@vite([
    'resources/css/app.css',
    'resources/css/admin/layout.css',
    // 'resources/css/admin/components.css' ← MISSING!
])

{{-- ✅ CORRECT: All CSS files imported --}}
@vite([
    'resources/css/app.css',
    'resources/css/admin/layout.css',
    'resources/css/admin/components.css',
    'resources/css/products/category-form.css',
])
```

**📖 Detailed Guide:** [_ISSUES_FIXES/CSS_IMPORT_MISSING_FROM_LAYOUT.md](../_ISSUES_FIXES/CSS_IMPORT_MISSING_FROM_LAYOUT.md)

---

### Issue #5: Modal DOM Nesting

**Symptom:** Modal doesn't appear or appears with wrong positioning

**Root Cause:** Modal rendered inside nested DOM with `position: relative`

**Solution:**
```blade
{{-- ❌ WRONG: Modal nested inside form/card --}}
<div class="card" style="position: relative;">
    <form>
        @if($showModal)
            <div class="modal">...</div>
        @endif
    </form>
</div>

{{-- ✅ CORRECT: Modal at body level with x-teleport --}}
<div class="card">
    <form>
        <button @click="showModal = true">Open Modal</button>
    </form>
</div>

@if($showModal)
    <div x-teleport="body" class="modal">...</div>
@endif
```

**📖 Detailed Guide:** [_ISSUES_FIXES/MODAL_DOM_NESTING_ISSUE.md](../_ISSUES_FIXES/MODAL_DOM_NESTING_ISSUE.md)

---

### Issue #6: Sidebar Grid Layout

**Symptom:** Sidebar overlaps main content or doesn't stay fixed

**Root Cause:** Incorrect CSS Grid implementation

**Solution:**
```css
/* ✅ CORRECT: Fixed sidebar with CSS Grid */
.admin-layout {
    display: grid;
    grid-template-columns: 240px 1fr; /* Sidebar width + flexible content */
    grid-template-rows: 1fr;
    min-height: 100vh;
}

.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.main-content {
    overflow-x: hidden;
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md](../_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md)

---

## 🚀 DEPLOYMENT ISSUES

### Standard Deployment Checklist

```powershell
# ====================================
# PRODUCTION DEPLOYMENT CHECKLIST
# ====================================

# 1. LOCAL BUILD (if CSS/JS changed)
npm run build

# 2. DEPLOY ASSETS (ALL files, not selective!)
pscp -r "public\build\assets\*" host:/path/
pscp "public\build\.vite\manifest.json" host:/public/build/manifest.json

# 3. DEPLOY CODE
pscp -r "app\*" host:/path/app/
pscp -r "resources\*" host:/path/resources/

# 4. CLEAR CACHES
plink host -batch "php artisan view:clear && cache:clear && config:clear"

# 5. VERIFY HTTP 200 (critical files)
curl -I https://ppm.mpptrade.pl/public/build/assets/app-*.css
curl -I https://ppm.mpptrade.pl/public/build/assets/components-*.css

# 6. SCREENSHOT VERIFICATION (mandatory)
node _TOOLS/full_console_test.cjs

# 7. USER CONFIRMATION
# Wait for user: "działa idealnie" or report issues
```

### Common Deployment Errors

| Error | Cause | Solution |
|-------|-------|----------|
| HTTP 404 on CSS | Incomplete asset deployment | Deploy ALL assets, not just changed |
| ViteException | New CSS file added | Add to existing file instead |
| Styles not updating | Cache not cleared | Clear ALL caches (view+app+config) |
| Livewire errors | Code/Blade mismatch | Deploy both app/ and resources/ |
| Console errors | Missing JS files | Deploy all build/assets/*.js |

---

## 🔌 PRESTASHOP INTEGRATION ISSUES

### Issue #1: No API Access (E2E Blocker)

**Symptom:** Cannot test PrestaShop integration end-to-end

**Root Cause:** No access to PrestaShop API (blocker in PROJECT_KNOWLEDGE.md)

**Solution: Enterprise Fallback Pattern**
```php
// ✅ CORRECT: Real API + Fallback simulation
class PrestaShopProductSync
{
    public function sync(Product $product)
    {
        if (config('prestashop.api_enabled')) {
            // Real API call
            return $this->apiClient->syncProduct($product);
        } else {
            // Enterprise fallback - simulated response
            Log::info('[SIMULATION] PrestaShop sync', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);

            return new SimulatedSyncResult([
                'success' => true,
                'prestashop_id' => 'SIM_' . $product->id,
                'simulated' => true,
            ]);
        }
    }
}
```

**📖 Detailed Guide:** [_ISSUES_FIXES/PRESTASHOP_E2E_NO_API_ACCESS_BLOCKER.md](../_ISSUES_FIXES/PRESTASHOP_E2E_NO_API_ACCESS_BLOCKER.md)

---

### Issue #2: API Integration Pattern

**Symptom:** Inconsistent API integration implementations

**Root Cause:** No standard pattern for external API integrations

**Solution: Consistent Pattern**
```php
// All external integrations follow this pattern:
// 1. Client class (API communication)
// 2. Transformer (API ↔ PPM data format)
// 3. Mapper (field mapping)
// 4. Service (business logic)
// 5. Fallback (simulation when API unavailable)

// Example:
PrestaShopClient::class         // HTTP client
PrestaShopTransformer::class    // Data transformation
PrestaShopProductMapper::class  // Field mapping
PrestaShopSyncService::class    // Business logic
SimulatedPrestaShopClient::class // Fallback
```

**📖 Detailed Guide:** [_ISSUES_FIXES/API_INTEGRATION_PATTERN_ISSUE.md](../_ISSUES_FIXES/API_INTEGRATION_PATTERN_ISSUE.md)

---

## 💾 DATABASE & PERFORMANCE ISSUES

### Issue #1: N+1 Queries in Computed Properties

**Symptom:** Slow page load, hundreds of duplicate queries

**Root Cause:** Missing eager loading in Livewire computed properties

**Solution:**
```php
// ❌ WRONG: N+1 query problem
#[Computed]
public function products()
{
    return Product::all(); // ← Lazy loads relationships!
}

// In Blade: Triggers N+1
@foreach($this->products as $product)
    {{ $product->category->name }} // ← N queries!
@endforeach

// ✅ CORRECT: Eager load relationships
#[Computed]
public function products()
{
    return Product::with(['category', 'variants', 'prices'])->get();
}
```

**Debug N+1:**
```bash
# Enable query log
php artisan debugbar:enable

# Or use Laravel Telescope
php artisan telescope:install
```

---

### Issue #2: Hard-coded IDs

**Symptom:** Code breaks when database is reset or migrated

**Root Cause:** Hard-coded product/category IDs instead of SKU/slug lookups

**Solution:**
```php
// ❌ WRONG: Hard-coded ID
$product = Product::find(123); // ← Breaks when DB resets!

// ✅ CORRECT: SKU lookup (PPM is SKU-first!)
$product = Product::where('sku', 'ABC-123')->first();

// ❌ WRONG: Hard-coded category ID
$category = Category::find(5);

// ✅ CORRECT: Slug lookup
$category = Category::where('slug', 'electronics')->first();
```

**📖 Detailed Guide:** [_ISSUES_FIXES/HARDCODE_SIMULATION_ISSUE.md](../_ISSUES_FIXES/HARDCODE_SIMULATION_ISSUE.md)

---

## 🔍 DEBUGGING TECHNIQUES

### 1. Livewire Debugging

**Browser Console:**
```javascript
// Check Livewire is loaded
typeof Livewire !== 'undefined'

// Inspect component data
Livewire.all() // All components on page

// Listen to Livewire events
Livewire.on('productUpdated', (data) => {
    console.log('Product updated:', data);
});

// Trigger component refresh
Livewire.all()[0].$wire.$refresh()
```

**PHP Debugging:**
```php
// In Livewire component
public function mount()
{
    Log::debug('Component mounted', [
        'properties' => $this->all(),
        'id' => $this->getId(),
    ]);
}

// Lifecycle hooks debugging
public function hydrate()
{
    Log::debug('Component hydrated');
}

public function dehydrate()
{
    Log::debug('Component dehydrated', [
        'properties' => $this->all(),
    ]);
}
```

---

### 2. CSS Debugging

**Browser DevTools:**
```javascript
// Find element's computed z-index
getComputedStyle(document.querySelector('.modal')).zIndex

// List all elements with z-index
[...document.querySelectorAll('*')]
    .filter(el => getComputedStyle(el).zIndex !== 'auto')
    .map(el => ({
        element: el,
        zIndex: getComputedStyle(el).zIndex
    }))
```

**Vite Build Debugging:**
```bash
# Check manifest entries
cat public/build/.vite/manifest.json | jq .

# List built CSS files
ls -lh public/build/assets/*.css

# Check CSS file is accessible
curl -I https://ppm.mpptrade.pl/public/build/assets/app-*.css
```

---

### 3. Database Query Debugging

```php
// Enable query log
DB::enableQueryLog();

// Run queries
$products = Product::with('category')->get();

// Dump queries
dd(DB::getQueryLog());

// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 100) { // > 100ms
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

---

### 4. Frontend Console Monitoring

**Use PPM Verification Tool:**
```bash
# Full verification with console monitoring
node _TOOLS/full_console_test.cjs

# Custom URL and tab
node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products" --show --tab=Warianty

# Options:
# --show        = Show browser (not headless)
# --tab=X       = Click specific tab
# --no-click    = Don't click tabs (just capture)
```

**Manual Browser Console:**
```javascript
// Check for errors
window.addEventListener('error', (e) => {
    console.error('Page error:', e.message, e.filename, e.lineno);
});

// Check for console errors
const originalError = console.error;
console.error = function(...args) {
    // Log to external service or localStorage
    originalError.apply(console, args);
};
```

---

### 5. Deployment Verification

**HTTP Status Checks:**
```powershell
# Check all critical CSS files
$cssFiles = @(
    'app-*.css',
    'layout-*.css',
    'components-*.css',
    'category-form-*.css',
    'category-picker-*.css'
)

foreach ($file in $cssFiles) {
    $url = "https://ppm.mpptrade.pl/public/build/assets/$file"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -Method Head
        Write-Host "✅ $file : HTTP $($response.StatusCode)" -ForegroundColor Green
    } catch {
        Write-Host "❌ $file : HTTP $($_.Exception.Response.StatusCode.Value__)" -ForegroundColor Red
    }
}
```

**Laravel Cache Status:**
```bash
# Check what's cached
php artisan cache:table # Show cache table contents

# Clear specific cache
php artisan cache:forget key

# Clear all caches
php artisan optimize:clear
```

---

## 🛡️ PREVENTION CHECKLIST

### Before Coding

- [ ] Read relevant skill guide (livewire-dev-guidelines, frontend-dev-guidelines)
- [ ] Check if similar feature exists (reuse patterns)
- [ ] Plan trait composition if component will be >200 lines
- [ ] Identify which CSS file to extend (NO new files without approval)

### During Coding

**Livewire:**
- [ ] NO constructor DI (use `app()` helper or property injection)
- [ ] ALWAYS add `wire:key` to loops and reusable components
- [ ] Use `type="button"` on ALL buttons inside forms (unless submit button)
- [ ] Eager load relationships in computed properties (prevent N+1)
- [ ] Use `$this->dispatch()` not `$this->emit()` (Livewire 3.x)

**CSS:**
- [ ] NO inline styles (`style="..."`)
- [ ] NO arbitrary Tailwind values (`z-[9999]`, `bg-[#...]`)
- [ ] Use design tokens (`:root` CSS variables)
- [ ] Add to EXISTING CSS files (not new files)
- [ ] Use dedicated CSS classes in `resources/css/`

**Database:**
- [ ] Use SKU lookups, not hard-coded IDs
- [ ] Eager load relationships (prevent N+1)
- [ ] Add indexes for frequently queried fields
- [ ] Use transactions for multi-table updates

### Before Committing

- [ ] Run local build: `npm run build` (if CSS/JS changed)
- [ ] No console errors in browser
- [ ] No Laravel errors in `storage/logs/laravel.log`
- [ ] PHPStan passes: `composer phpstan`
- [ ] Screenshot verification if UI changed

### Before Deployment

- [ ] Deploy ALL assets (not just "changed" files)
- [ ] Upload manifest to ROOT location
- [ ] Clear ALL caches (view+app+config)
- [ ] Verify HTTP 200 for ALL core CSS files
- [ ] Run `full_console_test.cjs` on production URL
- [ ] Wait for user confirmation ("działa idealnie")

### After User Confirms

- [ ] Remove debug logging (`Log::debug()` lines)
- [ ] Update plan status (❌ → ✅)
- [ ] Create agent report if >2h debugging
- [ ] Update _ISSUES_FIXES/ if new issue discovered

---

## 📚 SKILL-SPECIFIC GUIDES

### Livewire Development

**Main Skill:** `.claude/skills/guidelines/livewire-dev-guidelines/SKILL.md`

**Resources:**
- [Trait Composition Guide](../.claude/skills/guidelines/livewire-dev-guidelines/resources/traits.md) - ProductForm refactoring pattern (2182 → 250 lines)
- [Troubleshooting Guide](../.claude/skills/guidelines/livewire-dev-guidelines/resources/troubleshooting.md) - 9 known Livewire issues with solutions

**Key Principles:**
1. Single Responsibility (max 300 lines per component)
2. Trait Composition (use traits for large components)
3. Service Injection (business logic in services, not components)
4. Lifecycle Hooks Mastery (mount, hydrate, updated, dehydrate)
5. Wire:model Optimization (defer/blur/debounce)

---

### Frontend Development

**Main Skill:** `.claude/skills/guidelines/frontend-dev-guidelines/SKILL.md`

**Key Rules:**
- ❌ **ZAKAZ:** Inline styles (`style="..."`)
- ❌ **ZAKAZ:** Arbitrary Tailwind (`z-[9999]`, `bg-[#...]`)
- ✅ **WYMAGANE:** Dedicated CSS classes in `resources/css/`
- ✅ **WYMAGANE:** Screenshot verification after changes

**Design Token System:**
```css
:root {
    /* Z-index scale */
    --z-dropdown: 1000;
    --z-modal-overlay: 1050;
    --z-modal-content: 1051;
    --z-tooltip: 1070;

    /* Colors */
    --color-brand-500: #e0ac7e; /* MPP Orange */
    --color-gray-900: #111827;
    --color-success: #10b981;
    --color-error: #ef4444;
}
```

---

### Deployment

**Skills:**
- `hostido-deployment` - Production deployment to Hostido
- `frontend-verification` - Mandatory screenshot verification

**Guides:**
- [Frontend Verification Guide](FRONTEND_VERIFICATION_GUIDE.md)
- [Debug Logging Guide](DEBUG_LOGGING_GUIDE.md)
- [CSS Styling Guide](CSS_STYLING_GUIDE.md)

---

## 📖 DETAILED ISSUE REPORTS

All detailed issue reports are in `_ISSUES_FIXES/` directory:

**Livewire:**
- [LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md)
- [CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md](../_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md)
- [LIVEWIRE_EMIT_DISPATCH_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md)
- [LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md)
- [LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md)
- [LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md)
- [LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md](../_ISSUES_FIXES/LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md)
- [BUTTON_IN_FORM_WITHOUT_TYPE.md](../_ISSUES_FIXES/BUTTON_IN_FORM_WITHOUT_TYPE.md)

**CSS/Frontend:**
- [CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md](../_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md)
- [VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md](../_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md)
- [CSS_STACKING_CONTEXT_ISSUE.md](../_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md)
- [CSS_IMPORT_MISSING_FROM_LAYOUT.md](../_ISSUES_FIXES/CSS_IMPORT_MISSING_FROM_LAYOUT.md)
- [MODAL_DOM_NESTING_ISSUE.md](../_ISSUES_FIXES/MODAL_DOM_NESTING_ISSUE.md)
- [SIDEBAR_GRID_LAYOUT_FIX.md](../_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md)

**Integration:**
- [PRESTASHOP_E2E_NO_API_ACCESS_BLOCKER.md](../_ISSUES_FIXES/PRESTASHOP_E2E_NO_API_ACCESS_BLOCKER.md)
- [API_INTEGRATION_PATTERN_ISSUE.md](../_ISSUES_FIXES/API_INTEGRATION_PATTERN_ISSUE.md)

**Other:**
- [HARDCODE_SIMULATION_ISSUE.md](../_ISSUES_FIXES/HARDCODE_SIMULATION_ISSUE.md)
- [DEBUG_LOGGING_BEST_PRACTICES.md](../_ISSUES_FIXES/DEBUG_LOGGING_BEST_PRACTICES.md)

---

## 🔗 ADDITIONAL RESOURCES

### Documentation
- [PROJECT_KNOWLEDGE.md](PROJECT_KNOWLEDGE.md) - Complete architecture overview
- [FRONTEND_VERIFICATION_GUIDE.md](FRONTEND_VERIFICATION_GUIDE.md) - Screenshot verification workflow
- [CSS_STYLING_GUIDE.md](CSS_STYLING_GUIDE.md) - Complete CSS styling rules
- [DEBUG_LOGGING_GUIDE.md](DEBUG_LOGGING_GUIDE.md) - Debug logging best practices

### Tools
- `_TOOLS/full_console_test.cjs` - Console monitoring + screenshots
- `_TOOLS/screenshot_page.cjs` - Quick screenshot capture
- PHPStan - Static analysis (`composer phpstan`)
- Laravel Telescope - Query debugging

### External Resources
- [Livewire 3.x Documentation](https://livewire.laravel.com/docs)
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)

---

**Last Updated:** 2025-11-04
**Maintained By:** PPM Development Team
**Questions:** Reference this guide + detailed issue reports in _ISSUES_FIXES/
