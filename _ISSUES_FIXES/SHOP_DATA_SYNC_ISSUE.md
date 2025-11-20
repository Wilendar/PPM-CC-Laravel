# Shop Data Sync Issue - Root Cause Analysis

**Date:** 2025-11-06
**Reporter:** User (kamil)
**Status:** 🔍 ANALYZED - Solution designed
**Severity:** HIGH - Incorrect UI state leading to user confusion
**Related Files:**
- `app/Http/Livewire/Products/Management/ProductForm.php`
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`
- `resources/views/livewire/products/management/product-form.blade.php`

---

## 🐛 PROBLEM SUMMARY

### User Report
User changed product name in "Dane domyślne" (default data):
- **OLD:** `Test Auto-Fix Required Fields 1762422647`
- **NEW:** `[ZMIANA] Test Auto-Fix Required Fields 1762422647` (added prefix)

**Expected behavior:**
- Opening shop TAB should FETCH current data from PrestaShop API
- Show comparison: PPM vs PrestaShop
- Indicate conflict if data differs
- Allow user to resolve: Keep PPM / Use PrestaShop / Manual merge

**Actual behavior:**
- Shop TAB shows inherited default data (with `[ZMIANA]` prefix)
- UI shows "zgodne" (matched) status WITHOUT checking PrestaShop
- PrestaShop actually has OLD name (without `[ZMIANA]`)
- Sync status stuck on "Oczekuje" (pending) - correctly waiting for sync
- User sees ILLUSION that data matches

### Additional Button Behavior Requirements

**1. "Zapisz zmiany" button:**
- **Default mode ("Dane domyślne"):** Save changes LOCALLY only (NO sync job)
- **Shop mode (shop TAB):** Save changes + create sync job ONLY for THIS shop
- Currently: Always creates sync job for ALL shops (incorrect)

**2. "Synchronizuj sklepy" button:**
- Should: Pull current data from PrestaShop → PPM (immediate, forced)
- Should: Refresh ProductForm shop TABs without closing form
- Should: Work independently from background jobs
- Should: NOT show "trwa aktualizacja" blocking message
- Currently: Unknown behavior (needs investigation)

---

## 🔬 ROOT CAUSE ANALYSIS

### Database State (CORRECT ✅)
```sql
-- product_shop_data for product 11018, shop 1
name: NULL                    -- Inherits from default (correct)
sync_status: pending          -- Needs sync (correct)
prestashop_product_id: 9764   -- Linked to PrestaShop (correct)
```

### PrestaShop State (via API)
```json
{
  "product": {
    "id": 9764,
    "reference": "TEST-AUTOFIX-1762422647",
    "name": "Test Auto-Fix Required Fields 1762422647"  // OLD name
  }
}
```

### PPM Default State
```php
// products table
name: "[ZMIANA] Test Auto-Fix Required Fields 1762422647"  // NEW name with prefix
```

### What Code Does (ProductForm.php)

**1. PrestaShop Data IS Fetched ✅**
```php
// Line 3514: loadProductDataFromPrestaShop()
public function loadProductDataFromPrestaShop(int $shopId, bool $forceReload = false): void
{
    // ...
    $prestashopData = $client->getProduct($shopData->prestashop_product_id);  // Line 3540

    // Cache in component state (line 3548-3561)
    $this->loadedShopData[$shopId] = [
        'name' => data_get($prestashopData, 'name.0.value') ?? data_get($prestashopData, 'name'),
        // ... other fields
    ];
}
```

**Called when:**
- Line 1427: `switchToShop()` → if shop not loaded yet
- Line 1661: `updatedActiveShopId()` hook → if shop not loaded yet

**2. Shop Data Loaded to Form ❌ (IGNORES PrestaShop data!)**
```php
// Line 1498: loadShopDataToForm()
private function loadShopDataToForm(int $shopId): void
{
    // Line 1502: Gets shop-specific value from DB (product_shop_data)
    // If NULL, falls back to default data
    // NEVER compares with $this->loadedShopData[$shopId]
    $this->name = $this->getShopValue($shopId, 'name') ?? $this->name;

    // Result: Shows inherited default value "[ZMIANA] ..."
    // PrestaShop has "Test Auto-Fix ..." but user doesn't see it!
}
```

**3. UI Shows Inherited Value ❌ (NO comparison!)**
```blade
{{-- product-form.blade.php --}}
<input wire:model="name" />  {{-- Shows inherited default value --}}

{{-- NO comparison with PrestaShop data --}}
{{-- NO conflict indicator --}}
{{-- NO "zgodne/niezgodne" status based on actual PrestaShop data --}}
```

---

## 🎯 SOLUTION DESIGN

### Phase 1: UI Comparison View (CRITICAL)

**Add comparison UI in shop TAB:**

```blade
{{-- resources/views/livewire/products/management/product-form.blade.php --}}
{{-- Add after shop TAB selector, before form fields --}}

@if($activeShopId && isset($loadedShopData[$activeShopId]))
    <div class="prestashop-comparison-panel">
        <h4 class="comparison-header">
            🔄 Porównanie: PPM ↔ PrestaShop
        </h4>

        @php
            $ppmValue = $name; // Current form value (PPM)
            $psValue = $loadedShopData[$activeShopId]['name'] ?? null;
            $matches = $ppmValue === $psValue;
        @endphp

        <div class="comparison-grid">
            <div class="comparison-column">
                <span class="comparison-label">PPM (Dane):</span>
                <div class="comparison-value">{{ $ppmValue }}</div>
            </div>
            <div class="comparison-column">
                <span class="comparison-label">PrestaShop (Aktualnie):</span>
                <div class="comparison-value">{{ $psValue ?? 'Brak danych' }}</div>
            </div>
        </div>

        @if(!$matches)
            <div class="conflict-indicator">
                ⚠️ KONFLIKT: Dane różnią się!
            </div>

            <div class="conflict-actions">
                <button wire:click="usePPMData('{{ $activeShopId }}')"
                        class="btn-primary"
                        title="Zachowaj dane z PPM i zsynchronizuj do PrestaShop">
                    ← Użyj PPM (Sync → PS)
                </button>
                <button wire:click="usePrestaShopData('{{ $activeShopId }}')"
                        class="btn-secondary"
                        title="Pobierz aktualne dane z PrestaShop do PPM">
                    → Użyj PrestaShop (Pull → PPM)
                </button>
            </div>
        @else
            <div class="match-indicator">
                ✅ Zgodne
            </div>
        @endif
    </div>
@endif
```

**Add CSS (resources/css/admin/components.css):**

```css
.prestashop-comparison-panel {
    background: #eff6ff;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.comparison-header {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #1e40af;
}

.comparison-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.comparison-column {
    background: white;
    padding: 0.75rem;
    border-radius: 4px;
}

.comparison-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    display: block;
    margin-bottom: 0.25rem;
}

.comparison-value {
    font-size: 0.875rem;
    color: #0f172a;
    word-break: break-word;
}

.conflict-indicator {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.match-indicator {
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #16a34a;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
}

.conflict-actions {
    display: flex;
    gap: 0.5rem;
}
```

### Phase 2: Conflict Resolution Methods

**Add to ProductForm.php:**

```php
/**
 * Use PPM data (current form value) and mark for sync to PrestaShop
 *
 * User decision: Keep PPM data, sync to PrestaShop
 */
public function usePPMData(int $shopId): void
{
    try {
        // Keep current form values (no changes to form)
        // Update sync_status to "pending" to trigger sync
        $this->product->shopData()
            ->where('shop_id', $shopId)
            ->update(['sync_status' => 'pending']);

        Log::info('User chose to use PPM data for shop', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'name' => $this->name,
        ]);

        session()->flash('message', 'Zachowano dane z PPM. Produkt zostanie zsynchronizowany z PrestaShop.');

    } catch (\Exception $e) {
        Log::error('Failed to mark for sync', [
            'error' => $e->getMessage(),
            'shop_id' => $shopId,
        ]);
        session()->flash('error', 'Błąd podczas oznaczania produktu do synchronizacji');
    }
}

/**
 * Pull data from PrestaShop to PPM (overwrite form with PrestaShop values)
 *
 * User decision: Use PrestaShop data, pull to PPM
 */
public function usePrestaShopData(int $shopId): void
{
    try {
        if (!isset($this->loadedShopData[$shopId])) {
            session()->flash('error', 'Brak danych z PrestaShop');
            return;
        }

        $psData = $this->loadedShopData[$shopId];

        // Overwrite form with PrestaShop data
        $this->name = $psData['name'] ?? $this->name;
        $this->short_description = $psData['description_short'] ?? $this->short_description;
        $this->long_description = $psData['description'] ?? $this->long_description;
        $this->slug = $psData['link_rewrite'] ?? $this->slug;
        $this->weight = $psData['weight'] ?? $this->weight;
        $this->ean = $psData['ean13'] ?? $this->ean;
        $this->is_active = (bool)($psData['active'] ?? $this->is_active);

        // Update product_shop_data with PrestaShop values
        $this->product->shopData()
            ->where('shop_id', $shopId)
            ->update([
                'name' => $psData['name'] ?? null,
                'slug' => $psData['link_rewrite'] ?? null,
                'short_description' => $psData['description_short'] ?? null,
                'long_description' => $psData['description'] ?? null,
                'sync_status' => 'synced', // Now in sync
                'last_pulled_at' => now(),
            ]);

        Log::info('User chose to use PrestaShop data', [
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
            'name' => $psData['name'] ?? null,
        ]);

        session()->flash('message', 'Wczytano dane z PrestaShop do PPM.');

    } catch (\Exception $e) {
        Log::error('Failed to pull PrestaShop data', [
            'error' => $e->getMessage(),
            'shop_id' => $shopId,
        ]);
        session()->flash('error', 'Błąd podczas pobierania danych z PrestaShop');
    }
}
```

### Phase 3: Refactor "Zapisz zmiany" Button

**Update ProductFormSaver.php:**

```php
/**
 * Main save method - decides whether to save default or shop-specific
 *
 * BUGFIX 2025-11-06: Different behavior for default vs shop mode
 * - Default mode: Save local only (NO sync job)
 * - Shop mode: Save local + create sync job ONLY for this shop
 */
public function save(): void
{
    try {
        if ($this->component->activeShopId === null) {
            // DEFAULT MODE: Save to products table only
            $this->saveDefaultMode();

            Log::info('Saved default data (local only, no sync)', [
                'product_id' => $this->component->product?->id,
            ]);

            session()->flash('message', 'Zapisano dane domyślne.');

        } else {
            // SHOP MODE: Save to product_shop_data + create sync job for THIS shop
            $this->saveShopMode($this->component->activeShopId);

            Log::info('Saved shop data and queued sync job', [
                'product_id' => $this->component->product?->id,
                'shop_id' => $this->component->activeShopId,
            ]);

            session()->flash('message', 'Zapisano dane sklepu. Synchronizacja została dodana do kolejki.');
        }

    } catch (\Exception $e) {
        Log::error('Save failed', [
            'error' => $e->getMessage(),
            'active_shop_id' => $this->component->activeShopId,
        ]);

        session()->flash('error', 'Błąd podczas zapisywania: ' . $e->getMessage());
    }
}

/**
 * Save in default mode (products table only, no sync)
 */
private function saveDefaultMode(): void
{
    // Save to products table
    $this->component->product->update([
        'name' => $this->component->name,
        'short_description' => $this->component->short_description,
        'long_description' => $this->component->long_description,
        // ... other fields
    ]);

    // Update defaultData for UI reactivity
    $this->component->defaultData['name'] = $this->component->name;
    $this->component->defaultData['short_description'] = $this->component->short_description;
    // ... other fields
}

/**
 * Save in shop mode (product_shop_data + sync job for THIS shop only)
 */
private function saveShopMode(int $shopId): void
{
    // Save to product_shop_data
    $this->component->product->shopData()
        ->updateOrCreate(
            ['shop_id' => $shopId],
            [
                'name' => $this->component->name,
                'slug' => $this->component->slug,
                'short_description' => $this->component->short_description,
                'long_description' => $this->component->long_description,
                'sync_status' => 'pending',
                // ... other fields
            ]
        );

    // Create sync job ONLY for THIS shop
    $shop = PrestaShopShop::find($shopId);

    if ($shop) {
        SyncProductToPrestaShop::dispatch($this->component->product, $shop);

        Log::info('Dispatched sync job for single shop', [
            'product_id' => $this->component->product->id,
            'shop_id' => $shopId,
            'shop_name' => $shop->name,
        ]);
    }
}
```

### Phase 4: Refactor "Synchronizuj sklepy" Button

**Add new method to ProductForm.php:**

```php
/**
 * "Synchronizuj sklepy" button - Immediate pull from PrestaShop
 *
 * BUGFIX 2025-11-06: Pull current data from PrestaShop → PPM (immediate, forced)
 * - Fetch current data from PrestaShop API for ALL shops
 * - Update product_shop_data with fresh data
 * - Refresh UI (shop TABs) without closing form
 * - Work independently from background jobs
 */
public function syncShopsImmediate(): void
{
    $this->isLoadingShopData = true;

    try {
        $shopsToSync = $this->product->shopData()
            ->whereNotNull('prestashop_product_id')
            ->get();

        $synced = 0;
        $errors = 0;

        foreach ($shopsToSync as $shopData) {
            try {
                $shop = PrestaShopShop::find($shopData->shop_id);

                if (!$shop) {
                    $errors++;
                    continue;
                }

                // Fetch from PrestaShop API
                $client = PrestaShopClientFactory::create($shop);
                $psData = $client->getProduct($shopData->prestashop_product_id);

                if (isset($psData['product'])) {
                    $psData = $psData['product'];
                }

                // Update product_shop_data with fresh PrestaShop data
                $shopData->update([
                    'name' => $psData['name'] ?? null,
                    'slug' => $psData['link_rewrite'] ?? null,
                    'short_description' => $psData['description_short'] ?? null,
                    'long_description' => $psData['description'] ?? null,
                    'last_pulled_at' => now(),
                    'sync_status' => 'synced', // Mark as synced
                ]);

                // Update cached data for UI refresh
                $this->loadedShopData[$shopData->shop_id] = [
                    'prestashop_id' => $shopData->prestashop_product_id,
                    'name' => $psData['name'] ?? null,
                    'description_short' => $psData['description_short'] ?? null,
                    'description' => $psData['description'] ?? null,
                    'link_rewrite' => $psData['link_rewrite'] ?? null,
                    'weight' => $psData['weight'] ?? null,
                    'ean13' => $psData['ean13'] ?? null,
                    'reference' => $psData['reference'] ?? null,
                    'price' => $psData['price'] ?? null,
                    'active' => $psData['active'] ?? null,
                ];

                $synced++;

            } catch (\Exception $e) {
                Log::error('Failed to sync shop in immediate pull', [
                    'shop_id' => $shopData->shop_id,
                    'product_id' => $this->product->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        // Reload form data for current shop TAB
        if ($this->activeShopId !== null) {
            $this->loadShopDataToForm($this->activeShopId);
        }

        Log::info('Immediate shop sync completed', [
            'product_id' => $this->product->id,
            'synced' => $synced,
            'errors' => $errors,
        ]);

        session()->flash('message', "Pobrano dane z {$synced} sklepów. Błędów: {$errors}");

    } catch (\Exception $e) {
        Log::error('Immediate shop sync failed', [
            'product_id' => $this->product->id,
            'error' => $e->getMessage(),
        ]);

        session()->flash('error', 'Błąd podczas synchronizacji: ' . $e->getMessage());

    } finally {
        $this->isLoadingShopData = false;
    }
}
```

**Update blade template button:**

```blade
{{-- Change from old syncShops() to new syncShopsImmediate() --}}
<button wire:click="syncShopsImmediate"
        wire:loading.attr="disabled"
        class="btn-primary">
    <span wire:loading.remove wire:target="syncShopsImmediate">
        🔄 Synchronizuj sklepy
    </span>
    <span wire:loading wire:target="syncShopsImmediate">
        ⏳ Pobieranie danych...
    </span>
</button>
```

### Phase 5: Background Job - Cyclic PrestaShop → PPM Pull

**Create `app/Jobs/PullProductsFromPrestaShop.php`:**

```php
<?php

namespace App\Jobs;

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullProductsFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PrestaShopShop $shop
    ) {}

    public function handle(): void
    {
        Log::info('Starting PrestaShop → PPM pull', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        $client = PrestaShopClientFactory::create($this->shop);

        // Get all products linked to this shop
        $productsToSync = Product::whereHas('shopData', function($query) {
            $query->where('shop_id', $this->shop->id)
                  ->whereNotNull('prestashop_product_id');
        })->get();

        $synced = 0;
        $errors = 0;

        foreach ($productsToSync as $product) {
            try {
                $shopData = $product->shopData()
                    ->where('shop_id', $this->shop->id)
                    ->first();

                if (!$shopData->prestashop_product_id) {
                    continue;
                }

                // Fetch from PrestaShop
                $psData = $client->getProduct($shopData->prestashop_product_id);

                if (isset($psData['product'])) {
                    $psData = $psData['product'];
                }

                // Update product_shop_data with current PrestaShop values
                $shopData->update([
                    'name' => $psData['name'] ?? null,
                    'slug' => $psData['link_rewrite'] ?? null,
                    'short_description' => $psData['description_short'] ?? null,
                    'long_description' => $psData['description'] ?? null,
                    'last_pulled_at' => now(),
                    'sync_status' => 'synced', // Now in sync
                ]);

                $synced++;

            } catch (\Exception $e) {
                Log::error('Failed to pull product from PrestaShop', [
                    'product_id' => $product->id,
                    'shop_id' => $this->shop->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        Log::info('PrestaShop → PPM pull completed', [
            'shop_id' => $this->shop->id,
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }
}
```

**Schedule in `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule): void
{
    // Pull from PrestaShop every 6 hours for all active shops
    $schedule->call(function () {
        $shops = PrestaShopShop::where('is_active', true)->get();

        foreach ($shops as $shop) {
            PullProductsFromPrestaShop::dispatch($shop);
        }
    })->everySixHours()->name('pull-prestashop-data');
}
```

### Phase 6: Migration - Add `last_pulled_at` Column

**Create migration:**

```bash
php artisan make:migration add_last_pulled_at_to_product_shop_data
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->timestamp('last_pulled_at')
                  ->nullable()
                  ->after('last_sync_at')
                  ->comment('Last time PrestaShop data was pulled to PPM');
        });
    }

    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('last_pulled_at');
        });
    }
};
```

---

## ✅ SUCCESS CRITERIA

1. ✅ Opening shop TAB fetches current PrestaShop data via API
2. ✅ UI shows comparison: PPM value vs PrestaShop value
3. ✅ Conflict indicator visible when data differs
4. ✅ "Zgodne" indicator only when data actually matches
5. ✅ User can choose: Keep PPM / Use PrestaShop
6. ✅ "Zapisz zmiany" in default mode: Save local only (NO sync job)
7. ✅ "Zapisz zmiany" in shop mode: Save + sync job ONLY for this shop
8. ✅ "Synchronizuj sklepy": Immediate pull PrestaShop → PPM
9. ✅ "Synchronizuj sklepy": Refresh UI without closing form
10. ✅ Background job pulls PrestaShop → PPM every 6 hours
11. ✅ `last_pulled_at` timestamp tracks pull operations
12. ✅ Test product TEST-AUTOFIX-1762422647 shows correct conflict

---

## 📋 IMPLEMENTATION CHECKLIST

### Phase 1: UI Comparison View
- [ ] Add comparison panel in product-form.blade.php
- [ ] Add CSS classes to components.css
- [ ] Implement `usePPMData()` method
- [ ] Implement `usePrestaShopData()` method
- [ ] Test conflict detection on TEST-AUTOFIX-1762422647

### Phase 2: Button Refactoring
- [ ] Refactor ProductFormSaver::save() - split default/shop logic
- [ ] Implement saveDefaultMode() - local only
- [ ] Implement saveShopMode() - local + job for ONE shop
- [ ] Implement syncShopsImmediate() - pull all shops now
- [ ] Update blade button: syncShops → syncShopsImmediate
- [ ] Test "Zapisz zmiany" in both modes
- [ ] Test "Synchronizuj sklepy" immediate pull

### Phase 3: Background Job
- [ ] Create PullProductsFromPrestaShop job
- [ ] Add to scheduler (every 6 hours)
- [ ] Test job execution manually
- [ ] Verify logs

### Phase 4: Migration
- [ ] Create migration for last_pulled_at column
- [ ] Run migration locally
- [ ] Deploy migration to production
- [ ] Verify column exists

### Phase 5: Integration Testing
- [ ] Test full workflow on TEST-AUTOFIX-1762422647
- [ ] Test conflict resolution (Keep PPM)
- [ ] Test conflict resolution (Use PrestaShop)
- [ ] Test "Zapisz zmiany" default mode
- [ ] Test "Zapisz zmiany" shop mode
- [ ] Test "Synchronizuj sklepy" immediate
- [ ] Test background job (6h cycle)

### Phase 6: Deployment
- [ ] Deploy all changes to production
- [ ] Run migration
- [ ] Clear caches
- [ ] User verification

---

## 📖 LESSONS LEARNED

1. **Data fetching exists ≠ Data displayed:**
   Code was fetching PrestaShop data correctly but NOT showing it in UI

2. **Inheritance creates illusion:**
   When shop data is NULL (inherit), UI shows default value without indicating it's inherited vs fetched

3. **Status indicators need verification:**
   "Zgodne" status should be based on actual comparison, not just database state

4. **Button semantics matter:**
   "Zapisz zmiany" should have different behavior for default vs shop mode
   "Synchronizuj sklepy" should be immediate pull, not background job trigger

5. **Pull AND Push needed:**
   System needs both:
   - PPM → PrestaShop sync (push, already exists)
   - PrestaShop → PPM pull (MISSING - now designed)

6. **Separate concerns:**
   - Local saves (products table, no sync)
   - Shop saves (product_shop_data + sync job)
   - Immediate pulls (UI refresh, user-triggered)
   - Background pulls (scheduled, system-triggered)

---

**Next Steps:**
Start with Phase 1 (UI comparison view) for immediate user visibility of conflicts.
Then Phase 2 (button refactoring) for correct save/sync behavior.
