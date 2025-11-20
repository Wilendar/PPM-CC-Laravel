# ARCHITECTURE REPORT: Przeprojektowanie Systemu Magazynów PPM

**Data**: 2025-11-07
**Agent**: Planning Manager & Project Plan Keeper
**Zadanie**: Kompleksowe przeprojektowanie architektury systemu magazynów PPM-CC-Laravel

---

## 📋 EXECUTIVE SUMMARY

### Cel Projektu
Całkowita przebudowa systemu magazynów PPM z obecnego modelu statycznego (6 predefiniowanych magazynów) na dynamiczny model zorientowany na sklepy PrestaShop z inteligentnym dziedziczeniem stanów magazynowych.

### Główne Zmiany
1. **MPPTRADE** staje się jedynym stałym magazynem (Master Warehouse)
2. **Wszystkie pozostałe statyczne magazyny USUWANE** (Pitbike, Cameraman, Otopit, INFMS, Reklamacje)
3. **Dynamiczne magazyny** tworzone automatycznie dla każdego podłączonego sklepu PrestaShop
4. **Dwa tryby synchronizacji**:
   - **Inherit FROM MASTER** → PPM (MPPTRADE) jest master, sklepy dziedziczą stany
   - **Pull FROM SHOP** → PrestaShop jest master, PPM pobiera stany co 30 min (cron)

### Korzyści
- ✅ **Automatyzacja**: Magazyny tworzone automatycznie przy pierwszym imporcie
- ✅ **Elastyczność**: Toggle per sklep (inherit vs pull)
- ✅ **Czytelność**: Jawna relacja magazyn ↔ sklep PrestaShop
- ✅ **Skalowalność**: Nieograniczona liczba sklepów bez zmian w kodzie
- ✅ **Data Integrity**: Jasny master/slave relationship

### Zakres Pracy
- 2 migracje bazy danych
- 2 nowe service classes
- 1 nowy job + modyfikacje 2 istniejących
- Zmiany UI w 3 miejscach
- Seeder updates
- Tests updates

**Szacowany czas implementacji**: ~18 godzin

---

## 🏗️ CURRENT vs NEW ARCHITECTURE

### CURRENT ARCHITECTURE (TO BE REMOVED)

```
┌─────────────────────────────────────────────────────┐
│               WAREHOUSES TABLE                       │
├─────────────────────────────────────────────────────┤
│ ✓ MPPTRADE (code: mpptrade, is_default: true)      │
│ ✓ Pitbike.pl (code: pitbike)                       │
│ ✓ Cameraman (code: cameraman)                      │
│ ✓ Otopit (code: otopit)                            │
│ ✓ INFMS (code: infms)                              │
│ ✓ Reklamacje (code: returns)                       │
└─────────────────────────────────────────────────────┘
                      ↓
         ┌──────────────────────────┐
         │   PRODUCT_STOCK TABLE    │
         ├──────────────────────────┤
         │ product_id + warehouse_id│
         │ quantity                 │
         │ reserved_quantity        │
         │ available_quantity       │
         └──────────────────────────┘
```

**PROBLEMS:**
- ❌ Brak powiązania magazyn ↔ sklep PrestaShop
- ❌ Wszystkie magazyny są statyczne (hardcoded w seederze)
- ❌ Brak logiki dziedziczenia stanów
- ❌ Brak automatycznej synchronizacji z PrestaShop
- ❌ Nieczytelne mapowanie (warehouse.prestashop_mapping JSON)

---

### NEW ARCHITECTURE (DYNAMIC & SCALABLE)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        WAREHOUSES TABLE (NEW)                            │
├─────────────────────────────────────────────────────────────────────────┤
│ ✓ MPPTRADE (code: mpptrade, is_master: TRUE, is_default: TRUE)         │
│   └─ Główny magazyn PPM (Single Source of Truth)                       │
│                                                                          │
│ ✓ Shop 1 Warehouse (code: shop_1_warehouse, shop_id: 1)                │
│   ├─ inherit_from_master: TRUE ☑                                       │
│   └─ Dziedziczenie stanów z MPPTRADE (UNIDIRECTIONAL: MPPTRADE → Shop)│
│                                                                          │
│ ✓ Shop 2 Warehouse (code: shop_2_warehouse, shop_id: 2)                │
│   ├─ inherit_from_master: FALSE ☐                                      │
│   └─ Pull stanów z PrestaShop API (UNIDIRECTIONAL: Shop → PPM)        │
└─────────────────────────────────────────────────────────────────────────┘
```

#### NEW FIELDS (warehouses table)
```sql
is_master BOOLEAN DEFAULT FALSE           -- MPPTRADE = TRUE
shop_id BIGINT NULLABLE (FK → prestashop_shops)  -- NULL dla MPPTRADE, NOT NULL dla shop warehouses
inherit_from_master BOOLEAN DEFAULT FALSE  -- Toggle dziedziczenia stanów
```

---

### WORKFLOW DIAGRAMS

#### WORKFLOW A: Inherit FROM MASTER = TRUE (☑)

```
USER EDITS PRODUCT IN PPM
         ↓
┌────────────────────────────────────────┐
│ User saves product in PPM              │
│ Updates product_stock for MPPTRADE     │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ AUTO DISPATCH JOB:                     │
│ SyncStockToPrestaShop                  │
│ (for EACH shop with inherit=TRUE)      │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 1. Get stock from MPPTRADE warehouse   │
│    (quantity = 100)                    │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 2. Copy to Shop warehouse              │
│    product_stock.quantity = 100        │
│    (Shop warehouse is READ-ONLY)       │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 3. Sync to PrestaShop API              │
│    PUT /api/stock_availables/{id}      │
│    <quantity>100</quantity>            │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ PrestaShop shop displays: 100 items    │
└────────────────────────────────────────┘
```

**REGUŁY:**
- Shop warehouse **NIE MA własnych stanów** (read-only, kopie z MPPTRADE)
- Zmiana stanu w shop warehouse jest **ZABRONIONA** (frontend disable edycji)
- Sync jest **UNIDIRECTIONAL**: MPPTRADE → Shop (PPM is master)

---

#### WORKFLOW B: Inherit FROM MASTER = FALSE (☐)

```
CRON JOB (every 30 minutes)
         ↓
┌────────────────────────────────────────┐
│ PullStockFromPrestaShop Cron           │
│ (for shops with inherit=FALSE)         │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 1. Fetch stock from PrestaShop API     │
│    GET /api/stock_availables/{id}      │
│    <quantity>75</quantity>             │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 2. Update Shop warehouse in PPM        │
│    product_stock.quantity = 75         │
│    (Shop warehouse HAS OWN stock)      │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ PPM displays shop stock: 75 items      │
│ (READ-ONLY dla user - nie można        │
│  edytować, sklep jest master)          │
└────────────────────────────────────────┘
```

**REGUŁY:**
- Sklep jest master, PPM jest slave
- PPM **NIE MODYFIKUJE** stanów w tym sklepie (tylko READ)
- User może **CZYTAĆ** stany w PPM, ale nie może ich zmieniać
- Sync jest **UNIDIRECTIONAL**: Shop → PPM (PrestaShop is master)

---

## 📊 DATABASE SCHEMA CHANGES

### MIGRATION 1: Modify warehouses table

**File**: `database/migrations/2025_11_07_100000_add_master_warehouse_fields.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOWA ARCHITEKTURA: Dynamic shop warehouses z inherit logic
     *
     * Changes:
     * 1. Add is_master field (MPPTRADE = TRUE)
     * 2. Add shop_id FK → prestashop_shops (NULL dla MPPTRADE, NOT NULL dla shop warehouses)
     * 3. Add inherit_from_master toggle (kontroluje sync direction)
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Master warehouse flag (tylko MPPTRADE)
            $table->boolean('is_master')
                ->default(false)
                ->after('is_default')
                ->comment('Główny magazyn PPM (MPPTRADE)');

            // PrestaShop shop association (dynamiczne magazyny)
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->after('code')
                ->comment('PrestaShop shop ID (NULL dla MPPTRADE)');

            // Inherit logic toggle
            $table->boolean('inherit_from_master')
                ->default(false)
                ->after('shop_id')
                ->comment('TRUE = dziedziczenie z MPPTRADE, FALSE = pull z PrestaShop');

            // Foreign key constraint
            $table->foreign('shop_id')
                ->references('id')
                ->on('prestashop_shops')
                ->onDelete('cascade')
                ->comment('Cascade delete: sklep usunięty → warehouse usunięty');

            // Performance index
            $table->index('shop_id', 'idx_warehouses_shop_id');
            $table->index(['is_master', 'is_active'], 'idx_warehouses_master_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['shop_id']);

            // Drop indexes
            $table->dropIndex('idx_warehouses_shop_id');
            $table->dropIndex('idx_warehouses_master_active');

            // Drop columns
            $table->dropColumn(['is_master', 'shop_id', 'inherit_from_master']);
        });
    }
};
```

---

### MIGRATION 2: Data migration (drop old warehouses)

**File**: `database/migrations/2025_11_07_100001_migrate_warehouse_data.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\ProductStock;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * DATA MIGRATION: Clean old static warehouses
     *
     * WARNING: To jest BREAKING CHANGE!
     * - Usuwa wszystkie warehouses OPRÓCZ MPPTRADE
     * - Przenosi stany z usuniętych magazynów do MPPTRADE (optional merge logic)
     * - Ustawia MPPTRADE jako is_master = TRUE
     */
    public function up(): void
    {
        // 1. Mark MPPTRADE as master warehouse
        DB::table('warehouses')
            ->where('code', 'mpptrade')
            ->update([
                'is_master' => true,
                'is_default' => true,
                'updated_at' => now(),
            ]);

        Log::info('MPPTRADE marked as master warehouse');

        // 2. Get MPPTRADE warehouse ID
        $mpptrade = Warehouse::where('code', 'mpptrade')->first();

        if (!$mpptrade) {
            throw new \Exception('CRITICAL: MPPTRADE warehouse not found! Cannot proceed with migration.');
        }

        // 3. OLD WAREHOUSES TO DELETE
        $oldWarehouseCodes = ['pitbike', 'cameraman', 'otopit', 'infms', 'returns'];
        $oldWarehouses = Warehouse::whereIn('code', $oldWarehouseCodes)->get();

        Log::info('Found old warehouses to migrate/delete', [
            'count' => $oldWarehouses->count(),
            'codes' => $oldWarehouses->pluck('code')->toArray(),
        ]);

        // 4. STRATEGY A: Delete old product_stock records (simple, data loss)
        // WARNING: To usuwa wszystkie stany magazynowe z tych magazynów!
        foreach ($oldWarehouses as $warehouse) {
            $stockCount = ProductStock::where('warehouse_id', $warehouse->id)->count();

            Log::warning('Deleting product_stock records', [
                'warehouse_id' => $warehouse->id,
                'warehouse_code' => $warehouse->code,
                'stock_records_count' => $stockCount,
            ]);

            ProductStock::where('warehouse_id', $warehouse->id)->delete();
        }

        // 5. Delete old warehouses
        $deletedCount = Warehouse::whereIn('code', $oldWarehouseCodes)->delete();

        Log::info('Old warehouses deleted', [
            'deleted_count' => $deletedCount,
        ]);

        // 6. Validate final state
        $remainingWarehouses = Warehouse::count();
        $masterCount = Warehouse::where('is_master', true)->count();

        if ($masterCount !== 1) {
            throw new \Exception("VALIDATION FAILED: Expected exactly 1 master warehouse, found {$masterCount}");
        }

        Log::info('Warehouse migration completed successfully', [
            'remaining_warehouses' => $remainingWarehouses,
            'master_warehouse' => $mpptrade->code,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Rollback is NOT POSSIBLE (data already deleted)
     * You must restore from database backup if rollback needed!
     */
    public function down(): void
    {
        throw new \Exception('Rollback not supported - restore from database backup if needed!');
    }
};
```

**ALTERNATIVE STRATEGY B (Complex, preserves data):**

```php
// 4. STRATEGY B: Merge old stock into MPPTRADE (SUM quantities)
// Complex logic - preserves data but mixes different warehouse stocks
foreach ($oldWarehouses as $warehouse) {
    $stockRecords = ProductStock::where('warehouse_id', $warehouse->id)->get();

    foreach ($stockRecords as $oldStock) {
        // Check if MPPTRADE already has stock for this product
        $mpptrade Stock = ProductStock::where('product_id', $oldStock->product_id)
            ->where('product_variant_id', $oldStock->product_variant_id)
            ->where('warehouse_id', $mpptrade->id)
            ->first();

        if ($mpptradeStock) {
            // Merge: SUM quantities
            $mpptradeStock->increment('quantity', $oldStock->quantity);
            Log::info('Merged stock into MPPTRADE', [
                'product_id' => $oldStock->product_id,
                'old_warehouse' => $warehouse->code,
                'added_quantity' => $oldStock->quantity,
                'new_total' => $mpptradeStock->quantity,
            ]);
        } else {
            // Move: Change warehouse_id to MPPTRADE
            $oldStock->update(['warehouse_id' => $mpptrade->id]);
            Log::info('Moved stock to MPPTRADE', [
                'product_id' => $oldStock->product_id,
                'old_warehouse' => $warehouse->code,
                'quantity' => $oldStock->quantity,
            ]);
        }
    }
}
```

**DECISION REQUIRED:** User musi zdecydować którą strategię wybrać!

---

### SCHEMA COMPARISON

#### BEFORE (OLD)
```sql
warehouses:
  id | name      | code      | is_default | is_active | ...
  ---+-----------+-----------+------------+-----------+-----
  1  | MPPTRADE  | mpptrade  | TRUE       | TRUE      | ...
  2  | Pitbike   | pitbike   | FALSE      | TRUE      | ...
  3  | Cameraman | cameraman | FALSE      | TRUE      | ...
  4  | Otopit    | otopit    | FALSE      | TRUE      | ...
  5  | INFMS     | infms     | FALSE      | TRUE      | ...
  6  | Returns   | returns   | FALSE      | TRUE      | ...
```

#### AFTER (NEW)
```sql
warehouses:
  id | name              | code               | is_default | is_master | shop_id | inherit_from_master | is_active
  ---+-------------------+--------------------+------------+-----------+---------+---------------------+----------
  1  | MPPTRADE          | mpptrade           | TRUE       | TRUE      | NULL    | FALSE               | TRUE
  7  | Shop 1 Warehouse  | shop_1_warehouse   | FALSE      | FALSE     | 1       | TRUE                | TRUE
  8  | Shop 2 Warehouse  | shop_2_warehouse   | FALSE      | FALSE     | 2       | FALSE               | TRUE
```

**NOTES:**
- ✅ MPPTRADE: `is_master=TRUE`, `shop_id=NULL` (statyczny)
- ✅ Shop warehouses: `is_master=FALSE`, `shop_id=NOT NULL` (dynamiczne)
- ✅ `inherit_from_master=TRUE` → PPM is master (MPPTRADE → Shop sync)
- ✅ `inherit_from_master=FALSE` → PrestaShop is master (Shop → PPM pull)

---

## 🔧 SERVICE LAYER DESIGN

### SERVICE 1: WarehouseFactory

**File**: `app/Services/Warehouse/WarehouseFactory.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

/**
 * WarehouseFactory Service
 *
 * NOWA ARCHITEKTURA: Dynamic warehouse creation dla shop'ów PrestaShop
 *
 * Responsibility:
 * - Automatyczne tworzenie warehouse'ów dla nowych shop'ów
 * - Idempotent operations (safe to call multiple times)
 * - Business rules validation
 *
 * Usage:
 * ```php
 * $factory = app(WarehouseFactory::class);
 * $warehouse = $factory->getOrCreateShopWarehouse($shop);
 * ```
 *
 * @package App\Services\Warehouse
 * @version 1.0
 * @since 2025-11-07
 */
class WarehouseFactory
{
    /**
     * Create shop warehouse on first product import
     *
     * WORKFLOW:
     * 1. Generate warehouse code: shop_{shop_id}_warehouse
     * 2. Create warehouse record
     * 3. Set inherit_from_master based on parameter
     * 4. Return warehouse instance
     *
     * @param PrestaShopShop $shop Shop instance
     * @param bool $inheritFromMaster Inherit stock from MPPTRADE? (default: TRUE)
     * @return Warehouse Created warehouse
     * @throws \Exception On validation errors
     */
    public function createShopWarehouse(
        PrestaShopShop $shop,
        bool $inheritFromMaster = true
    ): Warehouse
    {
        // Validate shop is active
        if (!$shop->is_active) {
            throw new \Exception("Cannot create warehouse for inactive shop: {$shop->name}");
        }

        // Generate warehouse code
        $code = "shop_{$shop->id}_warehouse";
        $name = "{$shop->name} Warehouse";

        Log::info('Creating shop warehouse', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'warehouse_code' => $code,
            'inherit_from_master' => $inheritFromMaster,
        ]);

        // Create warehouse
        $warehouse = Warehouse::create([
            'name' => $name,
            'code' => $code,
            'shop_id' => $shop->id,
            'is_master' => false,  // Tylko MPPTRADE jest master
            'is_default' => false, // Tylko MPPTRADE jest default
            'inherit_from_master' => $inheritFromMaster,
            'is_active' => true,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 0,
            'sort_order' => 1000 + $shop->id, // Shop warehouses na końcu listy
            // Address fields from shop URL
            'notes' => "Dynamically created warehouse for PrestaShop shop: {$shop->name}",
        ]);

        Log::info('Shop warehouse created successfully', [
            'warehouse_id' => $warehouse->id,
            'warehouse_code' => $warehouse->code,
            'shop_id' => $shop->id,
        ]);

        return $warehouse;
    }

    /**
     * Get or create shop warehouse (idempotent)
     *
     * SAFE TO CALL MULTIPLE TIMES:
     * - If warehouse exists → return existing
     * - If warehouse doesn't exist → create new
     *
     * @param PrestaShopShop $shop Shop instance
     * @param bool $inheritFromMaster Inherit from MPPTRADE? (default: TRUE, only used for creation)
     * @return Warehouse Warehouse instance (existing or new)
     */
    public function getOrCreateShopWarehouse(
        PrestaShopShop $shop,
        bool $inheritFromMaster = true
    ): Warehouse
    {
        // Try to find existing warehouse
        $warehouse = Warehouse::where('shop_id', $shop->id)->first();

        if ($warehouse) {
            Log::debug('Found existing shop warehouse', [
                'warehouse_id' => $warehouse->id,
                'shop_id' => $shop->id,
            ]);
            return $warehouse;
        }

        // Create new warehouse
        return $this->createShopWarehouse($shop, $inheritFromMaster);
    }

    /**
     * Get master warehouse (MPPTRADE)
     *
     * @return Warehouse MPPTRADE warehouse
     * @throws \Exception If master warehouse not found
     */
    public function getMasterWarehouse(): Warehouse
    {
        $master = Warehouse::where('is_master', true)->first();

        if (!$master) {
            throw new \Exception('CRITICAL: Master warehouse (MPPTRADE) not found!');
        }

        return $master;
    }

    /**
     * Validate warehouse can be deleted
     *
     * BUSINESS RULES:
     * - Master warehouse CANNOT be deleted
     * - Shop warehouses with stock CANNOT be deleted (must be empty first)
     * - Shop warehouses without stock CAN be deleted
     *
     * @param Warehouse $warehouse Warehouse to validate
     * @return bool TRUE if can delete, FALSE otherwise
     */
    public function canDeleteWarehouse(Warehouse $warehouse): bool
    {
        // Master warehouse CANNOT be deleted
        if ($warehouse->is_master) {
            return false;
        }

        // Warehouse with stock CANNOT be deleted
        if ($warehouse->stock()->exists()) {
            return false;
        }

        return true;
    }
}
```

---

### SERVICE 2: StockInheritanceService

**File**: `app/Services/Warehouse/StockInheritanceService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductStock;
use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * StockInheritanceService
 *
 * NOWA ARCHITEKTURA: Stock inheritance logic (MPPTRADE → Shop warehouses)
 *
 * Responsibility:
 * - Copy stock from MPPTRADE → Shop warehouses
 * - Handle inherit_from_master = TRUE logic
 * - Bulk operations dla performance
 *
 * Usage:
 * ```php
 * $service = app(StockInheritanceService::class);
 * $service->inheritStockFromMaster($product, $shopWarehouse);
 * ```
 *
 * @package App\Services\Warehouse
 * @version 1.0
 * @since 2025-11-07
 */
class StockInheritanceService
{
    /**
     * Copy stock from MPPTRADE → Shop warehouse
     *
     * WORKFLOW:
     * 1. Get stock from master warehouse (MPPTRADE)
     * 2. Update/create stock in shop warehouse
     * 3. Mark shop warehouse stock as read-only (via metadata)
     *
     * Called when:
     * - inherit_from_master = TRUE
     * - User saves product in PPM
     * - Manual sync triggered
     *
     * @param Product $product Product to sync stock for
     * @param Warehouse $shopWarehouse Target shop warehouse
     * @return void
     * @throws \Exception On validation errors
     */
    public function inheritStockFromMaster(Product $product, Warehouse $shopWarehouse): void
    {
        // Validate shop warehouse has inherit enabled
        if (!$shopWarehouse->inherit_from_master) {
            Log::warning('Warehouse does not have inherit_from_master enabled', [
                'warehouse_id' => $shopWarehouse->id,
                'warehouse_code' => $shopWarehouse->code,
            ]);
            return;
        }

        // Get master warehouse
        $masterWarehouse = Warehouse::where('is_master', true)->first();

        if (!$masterWarehouse) {
            throw new \Exception('CRITICAL: Master warehouse not found!');
        }

        // Get stock from master
        $masterStock = ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $masterWarehouse->id)
            ->first();

        if (!$masterStock) {
            Log::info('No stock in master warehouse, skipping inherit', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);
            return;
        }

        Log::info('Inheriting stock from master', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'master_quantity' => $masterStock->quantity,
            'target_warehouse' => $shopWarehouse->code,
        ]);

        // Update or create shop warehouse stock
        ProductStock::updateOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => null, // Main product only (variants handled separately)
                'warehouse_id' => $shopWarehouse->id,
            ],
            [
                'quantity' => $masterStock->quantity,
                'reserved_quantity' => 0, // Shop warehouses don't reserve
                'is_active' => true,
                'track_stock' => true,
                'notes' => 'Inherited from MPPTRADE master warehouse',
                'erp_mapping' => [
                    'inherited_from' => [
                        'warehouse_id' => $masterWarehouse->id,
                        'warehouse_code' => $masterWarehouse->code,
                        'last_inherited_at' => now()->toISOString(),
                        'source_quantity' => $masterStock->quantity,
                    ],
                ],
            ]
        );

        Log::info('Stock inherited successfully', [
            'product_id' => $product->id,
            'warehouse_id' => $shopWarehouse->id,
            'quantity' => $masterStock->quantity,
        ]);
    }

    /**
     * Get all shops inheriting from master
     *
     * @return Collection<PrestaShopShop> Shops with inherit enabled
     */
    public function getInheritingShops(): Collection
    {
        $inheritingWarehouses = Warehouse::where('inherit_from_master', true)
            ->whereNotNull('shop_id')
            ->with('shop') // Eager load relationship (assuming we add it)
            ->get();

        // Extract shop IDs
        $shopIds = $inheritingWarehouses->pluck('shop_id')->filter()->unique();

        // Get shop instances
        return PrestaShopShop::whereIn('id', $shopIds)->get();
    }

    /**
     * Bulk inherit stock for multiple products
     *
     * PERFORMANCE: Use for bulk sync operations
     *
     * @param Collection<Product> $products Products to sync
     * @param Warehouse $shopWarehouse Target warehouse
     * @return array ['success' => int, 'failed' => int]
     */
    public function bulkInheritStock(Collection $products, Warehouse $shopWarehouse): array
    {
        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $this->inheritStockFromMaster($product, $shopWarehouse);
                $success++;
            } catch (\Exception $e) {
                Log::error('Failed to inherit stock for product', [
                    'product_id' => $product->id,
                    'warehouse_id' => $shopWarehouse->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Check if shop warehouse is in sync with master
     *
     * @param Product $product Product to check
     * @param Warehouse $shopWarehouse Shop warehouse
     * @return bool TRUE if quantities match, FALSE if out of sync
     */
    public function isInSync(Product $product, Warehouse $shopWarehouse): bool
    {
        $masterWarehouse = Warehouse::where('is_master', true)->first();

        $masterStock = ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $masterWarehouse->id)
            ->first();

        $shopStock = ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $shopWarehouse->id)
            ->first();

        // If no stock in either → in sync
        if (!$masterStock && !$shopStock) {
            return true;
        }

        // If only one has stock → out of sync
        if (!$masterStock || !$shopStock) {
            return false;
        }

        // Compare quantities
        return $masterStock->quantity === $shopStock->quantity;
    }
}
```

---

### MODIFICATIONS: PrestaShopStockImporter

**File**: `app/Services/PrestaShop/PrestaShopStockImporter.php`

**Changes needed:**

```php
// BEFORE (OLD):
protected function mapShopToWarehouse(PrestaShopShop $shop, int $prestashopShopId): ?int
{
    // Try to find warehouse with prestashop_mapping for this shop
    $warehouses = Warehouse::where('is_active', true)->get();

    foreach ($warehouses as $warehouse) {
        $mapping = $warehouse->prestashop_mapping ?? [];
        $shopKey = "shop_{$prestashopShopId}";
        if (isset($mapping[$shopKey])) {
            return $warehouse->id;
        }
    }

    // Fallback to default warehouse
    $defaultWarehouse = Warehouse::where('is_default', true)->first();
    return $defaultWarehouse?->id;
}

// AFTER (NEW):
protected function mapShopToWarehouse(PrestaShopShop $shop, int $prestashopShopId): ?int
{
    // NOWA LOGIKA: Get or create shop warehouse
    $factory = app(\App\Services\Warehouse\WarehouseFactory::class);
    $warehouse = $factory->getOrCreateShopWarehouse($shop, inheritFromMaster: false); // Pull mode by default

    return $warehouse->id;
}

// AND in importStockForProduct method, ADD CHECK:
public function importStockForProduct(Product $product, PrestaShopShop $shop): array
{
    // Get shop warehouse
    $factory = app(\App\Services\Warehouse\WarehouseFactory::class);
    $shopWarehouse = $factory->getOrCreateShopWarehouse($shop);

    // CHECK: Skip import if warehouse inherits from master
    if ($shopWarehouse->inherit_from_master) {
        Log::info('Skipping stock import - warehouse inherits from master', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'warehouse_code' => $shopWarehouse->code,
        ]);
        return []; // Don't import - shop dziedziczenie z MPPTRADE
    }

    // Continue with import (original logic)...
}
```

---

## 🚀 JOB LAYER DESIGN

### NEW JOB: SyncStockToPrestaShop

**File**: `app/Jobs/PrestaShop/SyncStockToPrestaShop.php`

```php
<?php

namespace App\Jobs\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\Warehouse;
use App\Models\ProductStock;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\Warehouse\StockInheritanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Stock to PrestaShop Job
 *
 * NOWA ARCHITEKTURA: Push stock from MPPTRADE → PrestaShop API
 *
 * Triggered when:
 * - User saves product in PPM (ProductForm)
 * - Shop has inherit_from_master = TRUE
 * - Bulk sync initiated by admin
 *
 * WORKFLOW:
 * 1. Get stock from MPPTRADE master warehouse
 * 2. Copy stock to shop warehouse (via StockInheritanceService)
 * 3. Sync stock to PrestaShop API (PUT stock_availables)
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since 2025-11-07
 */
class SyncStockToPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job configuration
     */
    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public PrestaShopShop $shop
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting stock sync MPPTRADE → PrestaShop', [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        try {
            // 1. Get shop warehouse
            $shopWarehouse = Warehouse::where('shop_id', $this->shop->id)->first();

            if (!$shopWarehouse) {
                Log::warning('Shop warehouse not found, cannot sync stock', [
                    'shop_id' => $this->shop->id,
                ]);
                return;
            }

            // 2. Validate inherit_from_master
            if (!$shopWarehouse->inherit_from_master) {
                Log::info('Warehouse does not inherit from master, skipping sync', [
                    'warehouse_code' => $shopWarehouse->code,
                    'shop_id' => $this->shop->id,
                ]);
                return;
            }

            // 3. Get stock from MPPTRADE
            $masterWarehouse = Warehouse::where('is_master', true)->first();

            if (!$masterWarehouse) {
                throw new \Exception('CRITICAL: Master warehouse not found!');
            }

            $masterStock = ProductStock::where('product_id', $this->product->id)
                ->where('warehouse_id', $masterWarehouse->id)
                ->first();

            if (!$masterStock) {
                Log::info('No stock in master warehouse, setting quantity to 0', [
                    'product_id' => $this->product->id,
                ]);
                $quantity = 0;
            } else {
                $quantity = $masterStock->quantity;
            }

            Log::info('Master warehouse stock retrieved', [
                'product_id' => $this->product->id,
                'master_quantity' => $quantity,
            ]);

            // 4. Copy stock to shop warehouse (inherit logic)
            $inheritanceService = app(StockInheritanceService::class);
            $inheritanceService->inheritStockFromMaster($this->product, $shopWarehouse);

            // 5. Get PrestaShop product ID
            $shopData = $this->product->shopData()
                ->where('shop_id', $this->shop->id)
                ->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::warning('Product not linked to PrestaShop, cannot sync stock', [
                    'product_id' => $this->product->id,
                    'shop_id' => $this->shop->id,
                ]);
                return;
            }

            $prestashopProductId = $shopData->prestashop_product_id;

            // 6. Sync to PrestaShop API
            $client = PrestaShopClientFactory::create($this->shop);

            // Get stock_available ID from PrestaShop
            $stockData = $client->getStock($prestashopProductId);

            if (!isset($stockData['stock_available']['id'])) {
                Log::error('Stock available ID not found in PrestaShop', [
                    'product_id' => $this->product->id,
                    'prestashop_product_id' => $prestashopProductId,
                ]);
                throw new \Exception('Stock available ID not found');
            }

            $stockAvailableId = (int) $stockData['stock_available']['id'];

            // Update stock via API
            $client->updateStock($prestashopProductId, $quantity, $stockAvailableId);

            Log::info('Stock synced to PrestaShop successfully', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'quantity' => $quantity,
                'stock_available_id' => $stockAvailableId,
            ]);

            // 7. Update product_shop_data sync status
            $shopData->update([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync stock to PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync status to error
            $shopData = $this->product->shopData()
                ->where('shop_id', $this->shop->id)
                ->first();

            if ($shopData) {
                $shopData->update([
                    'sync_status' => 'error',
                    'sync_error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncStockToPrestaShop job failed permanently', [
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'attempts' => $this->attempts(),
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

---

### MODIFICATIONS: PullProductsFromPrestaShop

**File**: `app/Jobs/PullProductsFromPrestaShop.php`

**Changes needed:**

```php
// ADD at the beginning of handle() method:

public function handle(): void
{
    Log::info('Starting PrestaShop → PPM pull', [
        'shop_id' => $this->shop->id,
        'shop_name' => $this->shop->name,
    ]);

    // === NEW: Get shop warehouse and check inherit mode ===
    $shopWarehouse = Warehouse::where('shop_id', $this->shop->id)->first();

    if ($shopWarehouse && $shopWarehouse->inherit_from_master) {
        Log::info('Skipping pull - shop inherits from master (MPPTRADE)', [
            'shop_id' => $this->shop->id,
            'warehouse_code' => $shopWarehouse->code,
        ]);
        return; // Don't pull if shop inherits from MPPTRADE
    }
    // === END NEW ===

    // Continue with original logic...
    $client = PrestaShopClientFactory::create($this->shop);
    // ...
}
```

---

### NEW CRON JOB: PullStockFromPrestaShop

**File**: `routes/console.php`

```php
use App\Jobs\PrestaShop\PullStockFromPrestaShop;
use App\Models\PrestaShopShop;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    // Get all shops with warehouses that DON'T inherit from master
    $warehouses = Warehouse::where('inherit_from_master', false)
        ->whereNotNull('shop_id')
        ->where('is_active', true)
        ->get();

    foreach ($warehouses as $warehouse) {
        $shop = PrestaShopShop::find($warehouse->shop_id);

        if ($shop && $shop->is_active) {
            Log::info('Dispatching stock pull job', [
                'shop_id' => $shop->id,
                'warehouse_code' => $warehouse->code,
            ]);

            PullStockFromPrestaShop::dispatch($shop);
        }
    }
})->everyThirtyMinutes()->name('pull-stock-from-prestashop');
```

**File**: `app/Jobs/PrestaShop/PullStockFromPrestaShop.php`

```php
<?php

namespace App\Jobs\PrestaShop;

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopStockImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pull Stock from PrestaShop Job
 *
 * NOWA ARCHITEKTURA: Pull stock from PrestaShop API → PPM
 *
 * Triggered:
 * - Cron job every 30 minutes
 * - Only for shops with inherit_from_master = FALSE
 *
 * WORKFLOW:
 * 1. Get all products linked to shop
 * 2. Fetch stock from PrestaShop API
 * 3. Update shop warehouse stock in PPM
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since 2025-11-07
 */
class PullStockFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes for bulk operation

    public function __construct(
        public PrestaShopShop $shop
    ) {}

    public function handle(): void
    {
        Log::info('Starting stock pull PrestaShop → PPM', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        $stockImporter = app(PrestaShopStockImporter::class);

        // Get all products linked to this shop
        $products = Product::whereHas('shopData', function($query) {
            $query->where('shop_id', $this->shop->id)
                  ->whereNotNull('prestashop_product_id');
        })->get();

        $imported = 0;
        $errors = 0;

        foreach ($products as $product) {
            try {
                $result = $stockImporter->importStockForProduct($product, $this->shop);

                if (count($result) > 0) {
                    $imported++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to pull stock for product', [
                    'product_id' => $product->id,
                    'shop_id' => $this->shop->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        Log::info('Stock pull completed', [
            'shop_id' => $this->shop->id,
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }
}
```

---

## 🎨 UI/UX CHANGES

### CHANGE 1: Warehouse Management UI

**Location**: `resources/views/admin/warehouses/index.blade.php` (new file)

**Design**:

```html
<div class="warehouse-list">
    <h1>Warehouses Management</h1>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Linked Shop</th>
                <th>Stock Sync</th>
                <th>Quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- MPPTRADE (Master) -->
            <tr class="warehouse-master">
                <td>
                    <strong>MPPTRADE</strong>
                    <span class="badge badge-primary">MASTER</span>
                </td>
                <td>mpptrade</td>
                <td>Static</td>
                <td>—</td>
                <td>—</td>
                <td>1,234 items</td>
                <td>
                    <a href="/admin/warehouses/1/edit" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>

            <!-- Shop 1 Warehouse (Inherit = TRUE) -->
            <tr class="warehouse-shop">
                <td>Shop 1 Warehouse</td>
                <td>shop_1_warehouse</td>
                <td>Dynamic</td>
                <td>
                    <a href="/admin/shops/1">Pitbike.pl</a>
                </td>
                <td>
                    <div class="inherit-toggle">
                        <label class="toggle">
                            <input type="checkbox" checked onchange="toggleInherit(1)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="inherit-label">
                            <strong>☑ Inherit from MPPTRADE</strong>
                            <small>PPM is master → Push to shop</small>
                        </span>
                    </div>
                </td>
                <td>
                    1,234 items
                    <span class="badge badge-success">🔄 Synced</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" disabled title="Stock is read-only (inherited)">
                        Edit (disabled)
                    </button>
                </td>
            </tr>

            <!-- Shop 2 Warehouse (Inherit = FALSE) -->
            <tr class="warehouse-shop">
                <td>Shop 2 Warehouse</td>
                <td>shop_2_warehouse</td>
                <td>Dynamic</td>
                <td>
                    <a href="/admin/shops/2">Cameraman.pl</a>
                </td>
                <td>
                    <div class="inherit-toggle">
                        <label class="toggle">
                            <input type="checkbox" onchange="toggleInherit(2)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="inherit-label">
                            <strong>☐ Pull from PrestaShop</strong>
                            <small>Shop is master → Pull every 30 min</small>
                        </span>
                    </div>
                </td>
                <td>
                    567 items
                    <span class="badge badge-info">📥 Pulled 5m ago</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" disabled title="Stock is read-only (pulled from shop)">
                        View
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
function toggleInherit(warehouseId) {
    // AJAX call to update warehouse.inherit_from_master
    fetch(`/admin/warehouses/${warehouseId}/toggle-inherit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({})
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload(); // Reload to show updated state
          }
      });
}
</script>
```

**CSS** (`resources/css/admin/warehouses.css`):

```css
.warehouse-master {
    background-color: #f0f8ff;
    font-weight: 600;
}

.warehouse-shop {
    background-color: #fafafa;
}

.inherit-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

.toggle input:checked + .toggle-slider {
    background-color: #4CAF50;
}

.toggle input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.inherit-label {
    display: flex;
    flex-direction: column;
}

.inherit-label small {
    color: #666;
    font-size: 11px;
}
```

---

### CHANGE 2: Product Form - Stock Tab

**Location**: `resources/views/livewire/products/management/product-form.blade.php`

**Changes**:

```html
<!-- Stock Tab -->
<div class="tab-pane" id="stock">
    <h3>Stock Levels</h3>

    <div class="stock-grid">
        @foreach($warehouses as $warehouse)
            <div class="stock-card {{ $warehouse->is_master ? 'stock-master' : 'stock-shop' }}">
                <div class="stock-header">
                    <h4>{{ $warehouse->name }}</h4>

                    @if($warehouse->is_master)
                        <span class="badge badge-primary">MASTER</span>
                    @elseif($warehouse->inherit_from_master)
                        <span class="badge badge-success">🔄 Synced from MPPTRADE</span>
                    @else
                        <span class="badge badge-info">📥 Pulled from Shop</span>
                    @endif
                </div>

                <div class="stock-body">
                    @php
                        $stock = $product->stock()->where('warehouse_id', $warehouse->id)->first();
                        $quantity = $stock ? $stock->quantity : 0;
                        $isReadOnly = !$warehouse->is_master; // Shop warehouses są read-only
                    @endphp

                    <div class="form-group">
                        <label>Quantity</label>
                        <input
                            type="number"
                            wire:model="stock.{{ $warehouse->id }}.quantity"
                            value="{{ $quantity }}"
                            class="form-control {{ $isReadOnly ? 'readonly-input' : '' }}"
                            {{ $isReadOnly ? 'readonly' : '' }}
                        >

                        @if($isReadOnly && $warehouse->inherit_from_master)
                            <small class="text-muted">
                                ℹ️ This stock is automatically synced from MPPTRADE. Edit MPPTRADE stock to update.
                            </small>
                        @elseif($isReadOnly && !$warehouse->inherit_from_master)
                            <small class="text-muted">
                                ℹ️ This stock is pulled from PrestaShop every 30 minutes. Edit in PrestaShop to update.
                            </small>
                        @endif
                    </div>

                    @if($stock && $stock->last_movement_at)
                        <small class="text-muted">
                            Last updated: {{ $stock->last_movement_at->diffForHumans() }}
                        </small>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.stock-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stock-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
}

.stock-master {
    background-color: #f0f8ff;
    border-color: #007bff;
}

.stock-shop {
    background-color: #fafafa;
}

.stock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.readonly-input {
    background-color: #f5f5f5;
    cursor: not-allowed;
}
</style>
```

---

### CHANGE 3: Shop Settings Page

**Location**: `resources/views/admin/shops/edit.blade.php`

**Add section**:

```html
<div class="form-section">
    <h3>Warehouse Settings</h3>

    <div class="form-group">
        <label class="checkbox-label">
            <input
                type="checkbox"
                wire:model="shop.warehouse_inherit_from_master"
                {{ $shop->warehouse ? ($shop->warehouse->inherit_from_master ? 'checked' : '') : '' }}
            >
            <strong>Inherit stock from MPPTRADE master warehouse</strong>
        </label>
        <small class="text-muted">
            When enabled, stock changes in PPM will be automatically synced to this shop (PPM → PrestaShop).
        </small>
    </div>

    <div class="form-group">
        <label class="checkbox-label">
            <input
                type="checkbox"
                wire:model="shop.warehouse_pull_from_shop"
                {{ $shop->warehouse ? (!$shop->warehouse->inherit_from_master ? 'checked' : '') : '' }}
            >
            <strong>Use shop's own stock (pull from PrestaShop)</strong>
        </label>
        <small class="text-muted">
            When enabled, stock will be pulled from PrestaShop every 30 minutes (PrestaShop → PPM).
        </small>
    </div>

    <div class="alert alert-info">
        <strong>ℹ️ Note:</strong> You must choose ONE option:
        <ul>
            <li><strong>Inherit from MPPTRADE</strong> = PPM is master (push stock TO PrestaShop)</li>
            <li><strong>Pull from PrestaShop</strong> = PrestaShop is master (pull stock FROM PrestaShop)</li>
        </ul>
    </div>

    @if($shop->warehouse)
        <div class="warehouse-info">
            <h4>Associated Warehouse</h4>
            <table class="table">
                <tr>
                    <th>Warehouse Code:</th>
                    <td>{{ $shop->warehouse->code }}</td>
                </tr>
                <tr>
                    <th>Current Mode:</th>
                    <td>
                        @if($shop->warehouse->inherit_from_master)
                            <span class="badge badge-success">☑ Inherit from MPPTRADE</span>
                        @else
                            <span class="badge badge-info">☐ Pull from PrestaShop</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Total Stock Items:</th>
                    <td>{{ $shop->warehouse->stock()->count() }}</td>
                </tr>
            </table>
        </div>
    @else
        <div class="alert alert-warning">
            ⚠️ Warehouse will be created automatically on first product import.
        </div>
    @endif
</div>
```

---

## 📅 IMPLEMENTATION PLAN

### PHASE 1: Database (Estimated: 2h)

**Tasks:**
1. ✅ Create migration: `2025_11_07_100000_add_master_warehouse_fields.php`
2. ✅ Create migration: `2025_11_07_100001_migrate_warehouse_data.php`
3. ⚠️ **CRITICAL DECISION**: Choose data migration strategy (Strategy A vs B)
4. ✅ Test migrations on local database
5. ✅ Update Warehouse model with new fields
6. ✅ Update WarehouseSeeder (remove old warehouses, keep only MPPTRADE)
7. ✅ Test seeder on fresh database

**Files to create:**
- `database/migrations/2025_11_07_100000_add_master_warehouse_fields.php`
- `database/migrations/2025_11_07_100001_migrate_warehouse_data.php`

**Files to modify:**
- `database/seeders/WarehouseSeeder.php`
- `app/Models/Warehouse.php` (add fillable fields, relationships)

**Validation:**
```bash
php artisan migrate:fresh --seed
php artisan tinker
>>> Warehouse::count() // Should be 1 (MPPTRADE only)
>>> Warehouse::where('is_master', true)->count() // Should be 1
```

---

### PHASE 2: Services (Estimated: 4h)

**Tasks:**
1. ✅ Create `WarehouseFactory` service
2. ✅ Create `StockInheritanceService` service
3. ✅ Add Warehouse→Shop relationship to models
4. ✅ Modify `PrestaShopStockImporter::mapShopToWarehouse()`
5. ✅ Modify `PrestaShopStockImporter::importStockForProduct()` (add inherit check)
6. ✅ Write unit tests for services

**Files to create:**
- `app/Services/Warehouse/WarehouseFactory.php`
- `app/Services/Warehouse/StockInheritanceService.php`
- `tests/Unit/Services/WarehouseFactoryTest.php`
- `tests/Unit/Services/StockInheritanceServiceTest.php`

**Files to modify:**
- `app/Services/PrestaShop/PrestaShopStockImporter.php`
- `app/Models/Warehouse.php` (add shop() relationship)
- `app/Models/PrestaShopShop.php` (add warehouse() relationship)

**Validation:**
```php
$factory = app(\App\Services\Warehouse\WarehouseFactory::class);
$shop = PrestaShopShop::first();
$warehouse = $factory->getOrCreateShopWarehouse($shop);
assert($warehouse->shop_id === $shop->id);
assert($warehouse->code === "shop_{$shop->id}_warehouse");
```

---

### PHASE 3: Jobs (Estimated: 3h)

**Tasks:**
1. ✅ Create `SyncStockToPrestaShop` job
2. ✅ Modify `PullProductsFromPrestaShop` job (add inherit check)
3. ✅ Create `PullStockFromPrestaShop` job
4. ✅ Add cron schedule to `routes/console.php`
5. ✅ Write job tests
6. ✅ Test job dispatching and execution

**Files to create:**
- `app/Jobs/PrestaShop/SyncStockToPrestaShop.php`
- `app/Jobs/PrestaShop/PullStockFromPrestaShop.php`
- `tests/Feature/Jobs/SyncStockToPrestaShopTest.php`
- `tests/Feature/Jobs/PullStockFromPrestaShopTest.php`

**Files to modify:**
- `app/Jobs/PullProductsFromPrestaShop.php`
- `routes/console.php`

**Validation:**
```php
// Dispatch job manually
$product = Product::first();
$shop = PrestaShopShop::first();
SyncStockToPrestaShop::dispatch($product, $shop);

// Check queue
php artisan queue:work --once

// Check logs
tail -f storage/logs/laravel.log
```

---

### PHASE 4: UI (Estimated: 5h)

**Tasks:**
1. ✅ Create warehouse management UI (`admin/warehouses/index.blade.php`)
2. ✅ Add inherit toggle to warehouse list
3. ✅ Update Product Form stock tab (read-only logic)
4. ✅ Add warehouse settings to shop edit page
5. ✅ Create CSS for warehouse UI
6. ✅ Add routes for warehouse management
7. ✅ Create WarehouseController with toggle action
8. ✅ Test UI interactions (toggle, edit, view)

**Files to create:**
- `resources/views/admin/warehouses/index.blade.php`
- `resources/views/admin/warehouses/edit.blade.php`
- `resources/css/admin/warehouses.css`
- `app/Http/Controllers/Admin/WarehouseController.php`

**Files to modify:**
- `resources/views/livewire/products/management/product-form.blade.php`
- `resources/views/admin/shops/edit.blade.php`
- `routes/web.php` (add warehouse routes)
- `vite.config.js` (add warehouses.css)

**Validation:**
- Visit `/admin/warehouses` → see MPPTRADE + shop warehouses
- Toggle inherit → verify DB update
- Edit product → verify stock fields readonly for shop warehouses
- Edit shop → verify warehouse settings section

---

### PHASE 5: Testing (Estimated: 4h)

**Tasks:**
1. ✅ Unit tests dla WarehouseFactory
2. ✅ Unit tests dla StockInheritanceService
3. ✅ Integration tests dla SyncStockToPrestaShop job
4. ✅ Integration tests dla inherit workflow
5. ✅ Integration tests dla pull workflow
6. ✅ Manual testing on local environment
7. ✅ Performance testing (bulk sync)
8. ✅ Edge cases testing (missing data, API errors)

**Test Scenarios:**
```php
// Test 1: Auto-create warehouse on first import
$shop = PrestaShopShop::factory()->create();
$product = Product::factory()->create();
// Import product → should auto-create warehouse

// Test 2: Inherit mode (MPPTRADE → Shop)
$warehouse = Warehouse::where('shop_id', $shop->id)->first();
$warehouse->update(['inherit_from_master' => true]);
// Update product stock in MPPTRADE → should sync to shop

// Test 3: Pull mode (Shop → PPM)
$warehouse->update(['inherit_from_master' => false]);
// Run cron → should pull stock from PrestaShop

// Test 4: Toggle inherit
// Toggle warehouse inherit → verify sync direction changes
```

**Files to create:**
- `tests/Feature/WarehouseInheritWorkflowTest.php`
- `tests/Feature/WarehousePullWorkflowTest.php`
- `tests/Feature/WarehouseFactoryIntegrationTest.php`

---

### TIMELINE SUMMARY

| Phase | Tasks | Estimated Time | Dependencies |
|-------|-------|----------------|--------------|
| **Phase 1: Database** | Migrations, seeders, models | 2h | None |
| **Phase 2: Services** | WarehouseFactory, StockInheritanceService | 4h | Phase 1 |
| **Phase 3: Jobs** | SyncStockToPrestaShop, modifications | 3h | Phase 1, 2 |
| **Phase 4: UI** | Warehouse management, product form, shop settings | 5h | Phase 1, 2 |
| **Phase 5: Testing** | Unit, integration, manual tests | 4h | Phase 1-4 |
| **TOTAL** | | **18h** | |

**Breakdown by Role:**
- **Backend (Laravel)**: 9h (Phases 1-3)
- **Frontend (Blade/Livewire)**: 5h (Phase 4)
- **Testing & QA**: 4h (Phase 5)

---

## ⚠️ RISKS & MITIGATIONS

### RISK 1: Data Loss During Migration

**Risk Level**: 🔴 **CRITICAL**

**Description**: Usunięcie starych magazynów (pitbike, cameraman, etc.) spowoduje utratę wszystkich stanów magazynowych dla tych magazynów.

**Mitigation:**
1. ✅ **Backup bazy danych** przed migracją (MANDATORY!)
2. ✅ Zaimplementować Strategy B (merge stocks) zamiast Strategy A (delete)
3. ✅ Create manual SQL script do przywrócenia danych jeśli coś pójdzie źle
4. ✅ Test migration na COPY production database przed deployment

**Rollback Plan:**
```sql
-- Restore from backup
mysql -u user -p ppm_database < backup_before_migration.sql
```

---

### RISK 2: Breaking Change for Existing Integrations

**Risk Level**: 🟠 **HIGH**

**Description**: Zmiana warehouse architecture może zepsuć istniejące integracje ERP lub PrestaShop, które używają warehouse.prestashop_mapping.

**Mitigation:**
1. ✅ Keep `prestashop_mapping` JSON field (backward compatibility)
2. ✅ Add deprecation warning in code comments
3. ✅ Provide migration guide dla custom integrations
4. ✅ Test ALL existing PrestaShop sync flows

**Migration Guide:**
```php
// OLD CODE (deprecated):
$mapping = $warehouse->prestashop_mapping["shop_1"];

// NEW CODE:
$warehouse = Warehouse::where('shop_id', 1)->first();
```

---

### RISK 3: Race Conditions in Stock Sync

**Risk Level**: 🟡 **MEDIUM**

**Description**: Jeśli 2 joby próbują syncować ten sam produkt w tym samym czasie → potential data corruption.

**Mitigation:**
1. ✅ Use database transactions in StockInheritanceService
2. ✅ Add unique queue job IDs (prevent duplicate dispatch)
3. ✅ Implement pessimistic locking dla ProductStock updates:
   ```php
   DB::transaction(function() use ($product, $warehouse) {
       $stock = ProductStock::lockForUpdate()
           ->where('product_id', $product->id)
           ->where('warehouse_id', $warehouse->id)
           ->first();

       $stock->update(['quantity' => $newQuantity]);
   });
   ```
4. ✅ Add job middleware `WithoutOverlapping`

---

### RISK 4: Performance Degradation on Bulk Sync

**Risk Level**: 🟡 **MEDIUM**

**Description**: Syncing 10,000+ products → warehouse per shop może zająć dużo czasu i zasobów.

**Mitigation:**
1. ✅ Use chunked queries dla bulk operations:
   ```php
   Product::chunk(100, function($products) {
       // Process batch
   });
   ```
2. ✅ Implement queue batching:
   ```php
   Bus::batch([
       new SyncStockToPrestaShop($product1, $shop),
       new SyncStockToPrestaShop($product2, $shop),
       // ...
   ])->dispatch();
   ```
3. ✅ Add progress tracking dla long-running operations
4. ✅ Optimize database queries (eager loading, indexes)

---

### RISK 5: User Confusion About Warehouse Types

**Risk Level**: 🟢 **LOW**

**Description**: Users mogą nie rozumieć różnicy między inherit mode vs pull mode.

**Mitigation:**
1. ✅ Add clear UI labels and tooltips
2. ✅ Create user documentation (screenshots + examples)
3. ✅ Add confirmation dialog when toggling inherit
4. ✅ Show sync status badges in UI
5. ✅ Add admin notification system dla sync errors

---

## 🔄 ROLLBACK PLAN

### IF MIGRATION FAILS

**Step 1: Stop all queue workers**
```bash
php artisan queue:clear
php artisan horizon:terminate
```

**Step 2: Restore database from backup**
```bash
# Production
mysql -u host379076_ppm -p host379076_ppm < backup_before_migration_2025-11-07.sql

# Local
php artisan migrate:rollback --step=2
```

**Step 3: Restore old warehouse seeder**
```bash
git checkout HEAD~1 database/seeders/WarehouseSeeder.php
php artisan db:seed --class=WarehouseSeeder
```

**Step 4: Verify data integrity**
```php
php artisan tinker
>>> Warehouse::count() // Should be 6 (old structure)
>>> ProductStock::count() // Should match pre-migration count
```

---

### IF PRODUCTION ISSUES OCCUR

**Symptoms:**
- Stock not syncing to PrestaShop
- Missing warehouse data
- Queue jobs failing
- API errors

**Emergency Actions:**

1. **Disable new jobs:**
   ```php
   // In routes/console.php
   // Comment out cron:
   // Schedule::call(...)->everyThirtyMinutes();
   ```

2. **Switch to manual mode:**
   ```sql
   -- Set ALL warehouses to manual (no auto-sync)
   UPDATE warehouses SET inherit_from_master = FALSE WHERE shop_id IS NOT NULL;
   ```

3. **Investigate logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "warehouse\|stock\|sync"
   ```

4. **Hotfix deployment:**
   - Fix identified issue
   - Deploy hotfix
   - Re-enable cron
   - Monitor for 1 hour

---

## ✅ SUCCESS CRITERIA

### FUNCTIONAL CRITERIA

1. ✅ **MPPTRADE is master warehouse**
   - `Warehouse::where('is_master', true)->count() === 1`
   - `Warehouse::where('code', 'mpptrade')->first()->is_master === true`

2. ✅ **Old warehouses removed**
   - `Warehouse::whereIn('code', ['pitbike', 'cameraman', 'otopit', 'infms', 'returns'])->count() === 0`

3. ✅ **Dynamic warehouses created**
   - Import product from shop → warehouse auto-created
   - `Warehouse::where('shop_id', $shop->id)->exists() === true`

4. ✅ **Inherit mode works**
   - Update stock in MPPTRADE → shop warehouse updated
   - PrestaShop API shows updated stock
   - Sync job completes without errors

5. ✅ **Pull mode works**
   - Cron runs → stock pulled from PrestaShop
   - PPM warehouse updated with PrestaShop values
   - No errors in logs

6. ✅ **UI shows correct data**
   - Warehouse list displays MPPTRADE + shop warehouses
   - Toggle inherit works (DB updated)
   - Product form stock tab shows read-only fields
   - Shop settings page shows warehouse info

---

### PERFORMANCE CRITERIA

1. ✅ **Sync job completion time**
   - Single product sync: < 5 seconds
   - Bulk sync (100 products): < 2 minutes
   - Full shop sync (1000 products): < 15 minutes

2. ✅ **Database query optimization**
   - Warehouse lookups: < 10ms (indexed queries)
   - Stock updates: < 50ms (with transactions)
   - Bulk operations: chunked (memory < 256MB)

3. ✅ **Queue throughput**
   - Jobs processed: > 10/second
   - Failed jobs: < 1%
   - Retry rate: < 5%

---

### DATA INTEGRITY CRITERIA

1. ✅ **No duplicate warehouses**
   - One warehouse per shop
   - Warehouse codes unique

2. ✅ **Stock consistency**
   - Shop warehouse quantity === MPPTRADE quantity (inherit mode)
   - Shop warehouse quantity === PrestaShop quantity (pull mode)

3. ✅ **Audit trail**
   - All sync operations logged
   - Error tracking functional
   - Timestamps accurate

---

## 📊 POST-DEPLOYMENT MONITORING

### METRICS TO TRACK

**Week 1:**
- Sync job success rate (target: > 95%)
- Average sync time per product (target: < 5s)
- Failed jobs count (target: < 10/day)
- User-reported issues (target: < 5/week)

**Week 2-4:**
- Stock accuracy (random sample verification)
- API error rate (target: < 1%)
- Database performance (query time)
- User adoption rate (shops using new system)

**Monitoring Tools:**
```bash
# Check sync job stats
php artisan tinker
>>> \App\Models\SyncJob::where('job_type', 'stock_sync')
    ->whereDate('created_at', today())
    ->selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();

# Check warehouse stats
>>> Warehouse::where('shop_id', '!=', null)->count();
>>> Warehouse::where('inherit_from_master', true)->count();
```

---

## 📝 DOCUMENTATION UPDATES NEEDED

### User Documentation

1. **Admin Guide**: "How to Manage Warehouses"
   - Rozdział: Creating shop warehouses
   - Rozdział: Inherit vs Pull modes
   - Rozdział: Troubleshooting sync issues

2. **FAQ**:
   - Q: "Where did my old warehouses go?"
   - Q: "How do I switch between inherit and pull mode?"
   - Q: "Why can't I edit stock for shop warehouses?"

### Developer Documentation

1. **Architecture Guide**: Update warehouse section
2. **API Reference**: Document WarehouseFactory, StockInheritanceService
3. **Migration Guide**: Step-by-step dla updating custom integrations

---

## 🎯 CONCLUSION

### Summary

To jest **BARDZO DUŻA zmiana architekturalna** która całkowicie przeprojektowuje system magazynów PPM z modelu statycznego na dynamiczny model zorientowany na sklepy PrestaShop.

**Key Highlights:**
- ✅ MPPTRADE becomes Single Source of Truth (master warehouse)
- ✅ Dynamic warehouse creation dla każdego sklepu PrestaShop
- ✅ Inteligentne dziedziczenie stanów (inherit mode)
- ✅ Automatyczny pull stanów z PrestaShop (pull mode)
- ✅ Clear master/slave relationship (data integrity)

### Approval Required

**CRITICAL**: User MUSI zaaprobować tę zmianę przed implementacją!

**Questions for User:**
1. ✅ Zgoda na usunięcie starych magazynów (pitbike, cameraman, etc.)?
2. ✅ Preferowana strategia migracji danych (Strategy A: delete vs Strategy B: merge)?
3. ✅ Zgoda na breaking changes w istniejących integracjach?
4. ✅ Akceptacja 18h implementation time?
5. ✅ Zgoda na potencjalne ryzyko data loss (z backup planem)?

### Next Steps

**IF APPROVED:**
1. Create detailed subtasks in project management tool
2. Schedule implementation (recommend dedicated 3-day sprint)
3. Prepare production database backup
4. Notify stakeholders about upcoming changes
5. Begin Phase 1 (Database)

**IF REJECTED:**
1. Discuss alternative approaches
2. Identify specific concerns
3. Propose incremental implementation plan
4. Re-design architecture based on feedback

---

## 📁 FILES TO CREATE

### Migrations
- `database/migrations/2025_11_07_100000_add_master_warehouse_fields.php`
- `database/migrations/2025_11_07_100001_migrate_warehouse_data.php`

### Services
- `app/Services/Warehouse/WarehouseFactory.php`
- `app/Services/Warehouse/StockInheritanceService.php`

### Jobs
- `app/Jobs/PrestaShop/SyncStockToPrestaShop.php`
- `app/Jobs/PrestaShop/PullStockFromPrestaShop.php`

### Controllers
- `app/Http/Controllers/Admin/WarehouseController.php`

### Views
- `resources/views/admin/warehouses/index.blade.php`
- `resources/views/admin/warehouses/edit.blade.php`

### CSS
- `resources/css/admin/warehouses.css`

### Tests
- `tests/Unit/Services/WarehouseFactoryTest.php`
- `tests/Unit/Services/StockInheritanceServiceTest.php`
- `tests/Feature/Jobs/SyncStockToPrestaShopTest.php`
- `tests/Feature/Jobs/PullStockFromPrestaShopTest.php`
- `tests/Feature/WarehouseInheritWorkflowTest.php`
- `tests/Feature/WarehousePullWorkflowTest.php`

---

## 📁 FILES TO MODIFY

### Models
- `app/Models/Warehouse.php` (add fillable, relationships, scopes)
- `app/Models/PrestaShopShop.php` (add warehouse() relationship)

### Services
- `app/Services/PrestaShop/PrestaShopStockImporter.php`

### Jobs
- `app/Jobs/PullProductsFromPrestaShop.php`

### Seeders
- `database/seeders/WarehouseSeeder.php`

### Routes
- `routes/console.php` (add cron)
- `routes/web.php` (add warehouse routes)

### Views
- `resources/views/livewire/products/management/product-form.blade.php`
- `resources/views/admin/shops/edit.blade.php`

### Config
- `vite.config.js` (add warehouses.css)

---

**END OF ARCHITECTURE REPORT**

---

**Date**: 2025-11-07
**Agent**: Planning Manager & Project Plan Keeper
**Status**: ✅ **READY FOR USER APPROVAL**
