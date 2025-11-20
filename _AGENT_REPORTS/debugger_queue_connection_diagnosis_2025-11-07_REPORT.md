# DIAGNOSTIC REPORT: Import z PrestaShop - Brak Tracking + Brak Stanow Magazynowych

**Date**: 2025-11-07 15:00
**Agent**: debugger
**Severity**: CRITICAL - System funkcjonalny ale brak widocznosci i integralnosci danych

---

## EXECUTIVE SUMMARY

Przeprowadzona kompleksowa analiza produkcji wykazala **2 KRYTYCZNE PROBLEMY**:

1. **PROBLEM #1**: Import z PrestaShop NIE pojawia sie jako JOB w `/admin/shops/sync`
2. **PROBLEM #2**: Stany magazynowe NIE zostaly pobrane podczas importu

**ROOT CAUSES:**
- Problem #1: `PullProductsFromPrestaShop` job **NIGDY NIE JEST URUCHAMIANY** (brak w scheduler, brak w UI)
- Problem #2: Warehouse mapping jest poprawny, ALE job nigdy nie wykonuje importu stock

**IMPACT:**
- Użytkownicy NIE widza postępu importu z PrestaShop
- Product_stock tabela pozostaje pusta po imporcie
- Brak synchronizacji stanow PrestaShop → PPM

---

## PHASE 1: PRODUCTION DATABASE DIAGNOSTICS

### 1.1 SYNC_JOBS TABLE ANALYSIS

**Total sync_jobs entries**: 84
**Recent activity**: Product sync jobs (PPM → PrestaShop)

**KRYTYCZNE ODKRYCIE:**
```json
{
  "id": 84,
  "job_type": "product_sync",
  "job_name": "Sync Product #10980 to B2B Test DEV",
  "source_type": "ppm",
  "source_id": "10980",
  "target_type": "prestashop",
  "target_id": "1",
  "status": "failed"
}
```

**ANALIZA:**
- ✅ SyncJob entries dla `product_sync` (PPM → PrestaShop) - ISTNIEJA
- ❌ ZERO SyncJob entries dla `pull_products` (PrestaShop → PPM) - **NIE MA**
- ❌ Brak job_type: "import_products" lub "pull_from_prestashop"

**WNIOSEK #1:** `PullProductsFromPrestaShop` job **NIE tworzy wpisow w sync_jobs table**

---

### 1.2 WAREHOUSES TABLE ANALYSIS

**Total warehouses**: 6
**Warehouses:**
- ID 1: `mpptrade` (MPPTRADE) - **is_default: 1**, is_active: 1
- ID 2: `pitbike` (Pitbike.pl) - is_default: 0, is_active: 1
- ID 3: `cameraman` (Cameraman) - is_default: 0, is_active: 1
- ID 4: `otopit` (Otopit) - is_default: 0, is_active: 1
- ID 5: `infms` (INFMS) - is_default: 0, is_active: 1
- ID 6: `returns` (Reklamacje) - is_default: 0, is_active: 1

**PrestaShop Mapping:**
```json
{
  "shop_1": {
    "warehouse_id": null,
    "location_id": null,
    "stock_available_id": null,
    "name": "MPP TRADE Main",
    "sync_enabled": false
  }
}
```

**ANALIZA:**
- ✅ Default warehouse (MPPTRADE) jest poprawnie zdefiniowany
- ⚠️ prestashop_mapping zawiera strukturę dla shop_1, shop_2, ALE:
  - `warehouse_id`: **null** (brak konkretnego mapowania)
  - `sync_enabled`: **false** (sync wylaczony)

**WNIOSEK #2:** Warehouse mapping jest zdefiniowany, ale fallback na default warehouse powinien działac

---

### 1.3 LARAVEL JOBS QUEUE

**Total jobs in queue**: 2
**Recent jobs:**
- `App\Jobs\PrestaShop\ExpirePendingCategoryPreview` (queue: default, attempts: 0)

**ANALIZA:**
- ❌ ZERO `PullProductsFromPrestaShop` jobs w kolejce
- ❌ ZERO `PullProductsFromPrestaShop` jobs w historii

**WNIOSEK #3:** Job **NIGDY NIE BYŁ URUCHOMIONY**

---

### 1.4 PRODUCT_STOCK TABLE

**Recent stock updates (last 24h)**: 6
**Updated product**: MINICROSS-ABT-125EN - PITGANG 125XD Enduro
**Updated at**: 2025-11-07 13:10:44 - 13:10:52

**Stock records:**
- Warehouse ID 1 (MPPTRADE): quantity = **999**
- Warehouse ID 2-6: quantity = **0**
- **erp_mapping**: EMPTY (`""`)

**ANALIZA:**
- ✅ Product_stock tabela jest dostępna i działa
- ⚠️ Recent updates pochodza z LOCAL EDITS (nie z PrestaShop import)
- ❌ **erp_mapping jest PUSTE** (brak danych z PrestaShop)

**WNIOSEK #4:** Stock updates NIE pochodza z `PrestaShopStockImporter`

---

### 1.5 FAILED_JOBS TABLE

**Total failed jobs**: 2
**Failed job type**: `App\Jobs\PrestaShop\SyncProductsJob`
**Exception**:
```
Error: Call to protected method App\Services\PrestaShop\PrestaShopService::syncSingleProduct()
from scope App\Jobs\PrestaShop\SyncProductsJob
```

**ANALIZA:**
- ⚠️ Failed jobs dotyczą **PPM → PrestaShop** sync (nie import)
- ✅ ZERO failed `PullProductsFromPrestaShop` jobs (bo nigdy nie był uruchomiony)

---

### 1.6 LARAVEL LOGS ANALYSIS

**Searched for**: "PrestaShop.*PPM pull", "Starting stock import", "Stock imported for product"
**Result**: **ZERO MATCHES** w ostatnich 1000 linii logow

**ANALIZA:**
- ❌ Brak logow z `PullProductsFromPrestaShop::handle()`
- ❌ Brak logow z `PrestaShopStockImporter::importStockForProduct()`
- ✅ Logi z ProductTransformer (PPM → PrestaShop direction)

**WNIOSEK #5:** `PullProductsFromPrestaShop` job **NIGDY NIE BYŁ WYKONANY**

---

## PHASE 2: CODE ANALYSIS

### 2.1 PullProductsFromPrestaShop JOB

**File**: `app/Jobs/PullProductsFromPrestaShop.php`

**Struktura:**
```php
class PullProductsFromPrestaShop implements ShouldQueue
{
    public function __construct(public PrestaShopShop $shop) {}

    public function handle(): void
    {
        // 1. Log start
        Log::info('Starting PrestaShop → PPM pull', [...]);

        // 2. Create clients
        $client = PrestaShopClientFactory::create($this->shop);
        $priceImporter = app(PrestaShopPriceImporter::class);
        $stockImporter = app(PrestaShopStockImporter::class);

        // 3. Get products linked to shop
        $productsToSync = Product::whereHas('shopData', ...)->get();

        // 4. Import prices + stock per product
        foreach ($productsToSync as $product) {
            // Update product_shop_data
            // Import prices: $priceImporter->importPricesForProduct(...)
            // Import stock: $stockImporter->importStockForProduct(...)
        }

        // 5. Log completion
        Log::info('PrestaShop → PPM pull completed', [...]);
    }
}
```

**KRYTYCZNE ODKRYCIE:**
```php
// ❌ PROBLEM: Brak SyncJob tracking!
// Job NIE tworzy wpisu w sync_jobs table
// Job NIE update'uje progress during execution
```

**POROWNANIE z SyncProductToPrestaShop:**
- SyncProductToPrestaShop: **TAK** - tworzy SyncJob w konstruktorze
- PullProductsFromPrestaShop: **NIE** - brak SyncJob integration

---

### 2.2 PrestaShopStockImporter SERVICE

**File**: `app/Services/PrestaShop/PrestaShopStockImporter.php`

**Workflow:**
1. Get product_shop_data → prestashop_product_id
2. Fetch stock_availables from PrestaShop API
3. Map PrestaShop shop → PPM warehouse (via `mapShopToWarehouse()`)
4. Update/create product_stock records

**Mapping Strategy:**
```php
protected function mapShopToWarehouse(PrestaShopShop $shop, int $prestashopShopId): ?int
{
    // 1. Check warehouses.prestashop_mapping for shop mapping
    foreach ($warehouses as $warehouse) {
        if (isset($mapping["shop_{$prestashopShopId}"])) {
            return $warehouse->id; // ✅ Found mapping
        }
    }

    // 2. Use default warehouse
    $defaultWarehouse = Warehouse::where('is_default', true)->first();
    return $defaultWarehouse->id; // ✅ MPPTRADE (ID: 1)

    // 3. Fallback to MPPTRADE by code
    $mpptrade = Warehouse::where('code', 'mpptrade')->first();
    return $mpptrade->id;
}
```

**ANALIZA:**
- ✅ Logika mapping jest POPRAWNA
- ✅ Default warehouse fallback działa
- ✅ Kod jest bezpieczny i logowany

**PROBLEM:** Service jest poprawny, ALE job **NIGDY NIE BYŁ URUCHOMIONY**

---

### 2.3 DEPLOYMENT VERIFICATION

**Grep search**: `PullProductsFromPrestaShop::dispatch`
**Results**: TYLKO w `_AGENT_REPORTS/` i `_ISSUES_FIXES/`

**Checked files:**
- ❌ `app/Http/Livewire/Admin/Shops/SyncController.php` - BRAK dispatch
- ❌ `routes/console.php` - BRAK scheduled task
- ❌ `app/Console/Commands/` - BRAK artisan command

**WNIOSEK KOŃCOWY:** Job jest zaimplementowany, ALE:
- NIE MA UI button do manual trigger
- NIE MA scheduler dla automatic runs
- NIE MA artisan command dla CLI

---

## PHASE 3: ROOT CAUSE IDENTIFICATION

### ROOT CAUSE #1: PullProductsFromPrestaShop NIE jest uruchamiany

**Dowody:**
1. ZERO wpisow w sync_jobs table dla job_type: "pull_products"
2. ZERO logow z PullProductsFromPrestaShop::handle()
3. ZERO jobs w Laravel jobs queue
4. ZERO dispatch() calls w codebase (poza raportami)

**Przyczyna:**
- Job został zaimplementowany w FAZA 9 Phase 2 (2025-11-06)
- Job został wdrożony na produkcję
- ALE **BRAK INTEGRACJI Z UI** (brak button w SyncController)
- ALE **BRAK SCHEDULER** (brak w routes/console.php)

**Impact:**
- Użytkownicy nie mogą wykonać importu PrestaShop → PPM
- UI pokazuje tylko sync PPM → PrestaShop
- Import stock/prices nigdy się nie dzieje

---

### ROOT CAUSE #2: SyncJob tracking nie jest zaimplementowany

**Dowody:**
1. PullProductsFromPrestaShop NIE wywołuje `SyncJob::create()`
2. Brak update progress during execution
3. UI nie widzi tych jobs (bo nie ma ich w sync_jobs)

**Porownanie:**
```php
// SyncProductToPrestaShop - ✅ HAS SyncJob tracking
protected ?SyncJob $syncJob = null;

public function __construct(Product $product, PrestaShopShop $shop)
{
    $this->syncJob = SyncJob::create([
        'job_type' => 'product_sync',
        'source_type' => 'ppm',
        'target_type' => 'prestashop',
        'status' => 'pending',
        // ... full metadata
    ]);
}

// PullProductsFromPrestaShop - ❌ NO SyncJob tracking
public function __construct(public PrestaShopShop $shop) {}
// Brak SyncJob creation!
```

**Impact:**
- Nawet jeśli job zostanie uruchomiony → UI nie widzi postępu
- Brak historii importow w /admin/shops/sync
- Brak możliwości monitorowania/retry

---

### ROOT CAUSE #3: Stock import logika jest poprawna, ALE niewykonana

**Dowody:**
1. PrestaShopStockImporter jest poprawnie zaimplementowany
2. Warehouse mapping fallback działa (MPPTRADE is_default=1)
3. Product_stock table działa (manualne updates widoczne)
4. ALE erp_mapping jest PUSTE (brak danych z PrestaShop)

**Przyczyna:**
- `PullProductsFromPrestaShop` wywołuje `$stockImporter->importStockForProduct()`
- Service działa poprawnie
- ALE job nigdy nie był uruchomiony → stock import nie miał miejsca

**Impact:**
- Stany w PPM nie są zsynchronizowane z PrestaShop
- Użytkownicy muszą ręcznie wprowadzać stany
- Brak spójności między systemami

---

## PHASE 4: RECOMMENDED FIXES

### FIX #1: Add SyncJob tracking do PullProductsFromPrestaShop

**PRIORITY:** CRITICAL
**EFFORT:** Medium (2-3 hours)
**FILE**: `app/Jobs/PullProductsFromPrestaShop.php`

**KEY CHANGES:**
1. Add `protected ?SyncJob $syncJob = null;` property
2. Create SyncJob in constructor with job_type: 'import_products'
3. Update status: pending → running → completed/failed
4. Update progress every 10 products (processed_items, progress_percentage)
5. Add failed() method to handle job failures

**REFERENCE**: See SyncProductToPrestaShop.php for full SyncJob integration pattern

---

### FIX #2: Add UI button do trigger import w SyncController

**PRIORITY:** HIGH
**EFFORT:** Small (1-2 hours)
**FILES**:
- `app/Http/Livewire/Admin/Shops/SyncController.php`
- `resources/views/livewire/admin/shops/sync-controller.blade.php`
- `resources/css/admin/components.css`

**KEY CHANGES:**

**A. Backend Method:**
```php
public function importFromShop(int $shopId): void
{
    $shop = PrestaShopShop::findOrFail($shopId);
    $this->authorize('manage-prestashop-shops');
    PullProductsFromPrestaShop::dispatch($shop);
    // Success notification + refresh jobs
}
```

**B. Frontend Button:**
```blade
<button wire:click="importFromShop({{ $shop->id }})"
        class="btn-enterprise-secondary">
    <i class="fas fa-download"></i> Import ← PrestaShop
</button>
```

---

### FIX #3: Add scheduled task dla automatic imports

**PRIORITY:** MEDIUM
**EFFORT:** Small (30 minutes)
**FILE**: `routes/console.php`

**Implementation:**
```php
Schedule::call(function () {
    $activeShops = PrestaShopShop::where('is_active', true)
        ->where('connection_status', 'connected')
        ->get();

    foreach ($activeShops as $shop) {
        if ($shop->auto_sync_products) {
            PullProductsFromPrestaShop::dispatch($shop);
        }
    }
})
    ->name('prestashop:pull-products')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();
```

---

### FIX #4: Add artisan command dla CLI control

**PRIORITY:** LOW (nice to have)
**EFFORT:** Small (1 hour)
**FILE**: `app/Console/Commands/PullProductsFromPrestaShopCommand.php` (NEW)

**Implementation:**
```php
class PullProductsFromPrestaShopCommand extends Command
{
    protected $signature = 'prestashop:pull-products
                            {shop_id? : PrestaShop shop ID}
                            {--all : Import from all active shops}';

    protected $description = 'Import products, prices, and stock FROM PrestaShop TO PPM';
}
```

---

## PHASE 5: IMPLEMENTATION PRIORITY

**RECOMMENDED ORDER:**

1. **FIX #1** (CRITICAL) - Add SyncJob tracking
   - Umożliwia tracking w sync_jobs table
   - Visibility w UI
   - Duration: 2-3 hours

2. **FIX #2** (HIGH) - Add UI button
   - Umożliwia manual trigger
   - User-friendly import
   - Duration: 1-2 hours

3. **FIX #3** (MEDIUM) - Add scheduler
   - Automatic imports co 6h
   - Zero manual intervention
   - Duration: 30 minutes

4. **FIX #4** (LOW) - Add CLI command
   - Power user / DevOps convenience
   - Duration: 1 hour

**TOTAL EFFORT:** ~5-7 hours development + testing

---

## PHASE 6: STOCK IMPORT VALIDATION

**Po wdrożeniu FIX #1-#3, zweryfikuj:**

1. **Warehouse mapping:**
   ```php
   DB::table('warehouses')->where('code', 'mpptrade')->first(['id', 'is_default']);
   ```

2. **Stock import execution:**
   ```bash
   php artisan prestashop:pull-products 1
   tail -f storage/logs/laravel.log | grep "Stock imported for product"
   ```

3. **Product_stock verification:**
   ```php
   DB::table('product_stock')
     ->whereNotNull('erp_mapping')
     ->where('erp_mapping', '!=', '')
     ->count();
   ```

4. **Expected result:**
   - Product_stock records with populated erp_mapping
   - Quantity values from PrestaShop
   - Logs showing successful stock import

---

## DELIVERABLES

**Completed:**
- ✅ Full production diagnostics
- ✅ Root cause analysis (both problems)
- ✅ Code review (PullProductsFromPrestaShop + PrestaShopStockImporter)
- ✅ Comprehensive fix recommendations

**Ready for implementation:**
- FIX #1: SyncJob tracking (detailed specs)
- FIX #2: UI button (code snippets)
- FIX #3: Scheduler (code snippets)
- FIX #4: CLI command (code snippets)

**FILES TO MODIFY:**
- `app/Jobs/PullProductsFromPrestaShop.php` - Add SyncJob tracking + progress updates
- `app/Http/Livewire/Admin/Shops/SyncController.php` - Add `importFromShop()` method
- `resources/views/livewire/admin/shops/sync-controller.blade.php` - Add import button
- `resources/css/admin/components.css` - Add button styling
- `routes/console.php` - Add scheduled task
- `app/Console/Commands/PullProductsFromPrestaShopCommand.php` - NEW FILE

---

## NEXT STEPS

**IMMEDIATE ACTIONS:**

1. **User decision required:**
   - Czy implementować wszystkie FIX (#1-#4)?
   - Czy tylko CRITICAL + HIGH (#1-#2)?
   - Czy chcesz manual testing przed wdrożeniem?

2. **After approval:**
   - Implement fixes (laravel-expert)
   - Deploy to production (deployment-specialist)
   - Verify with PPM Verification Tool (frontend-specialist)
   - Document in CLAUDE.md

**MONITORING:**
- Watch sync_jobs table for import_products entries
- Monitor Laravel logs for stock import success
- Verify product_stock erp_mapping population

---

**STATUS:** ✅ DIAGNOSTICS COMPLETE - AWAITING USER DECISION ON FIX IMPLEMENTATION
