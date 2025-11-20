# RAPORT PRACY AGENTA: debugger

**Data**: 2025-11-14
**Agent**: debugger
**Zadanie**: ETAP_07 FAZA 5.2 - CRITICAL BUG Diagnosis: Tax Rate NOT Syncing to PrestaShop
**Priorytet**: 🔴 KRYTYCZNY (blokuje production tax rate synchronization)

---

## 🚨 EXECUTIVE SUMMARY

**Status Diagnozy**: ✅ **ROOT CAUSE ZIDENTYFIKOWANY**

**Problem**: Zmiana stawki VAT w Shop Tab nie jest poprawnie wysyłana do PrestaShop. JOB nie zawiera danych o stawce VAT.

**Root Cause**: `ProductTransformer->mapTaxRate()` używa `$product->tax_rate` (globalny default) zamiast `$shopData->tax_rate_override` (shop-specific override z FAZY 5.2).

**Lokalizacja Błędu**: `app/Services/PrestaShop/ProductTransformer.php` - Line 130

**Severity**: CRITICAL - Produkty synchronizowane z błędną stawką VAT (zawsze 23% zamiast wybranej stawki).

**Impact**: Business logic broken - sklepy PrestaShop otrzymują nieprawidłowe stawki VAT → błędne ceny dla klientów.

---

## 🔍 SYSTEMATIC DIAGNOSIS

### STEP 1: Verify Database Write (ProductForm → product_shop_data) ✅

**Hypothesis**: `saveShopSpecificData()` nie zapisuje `tax_rate_override` do DB.

**Evidence (Phase 2 Report - livewire_specialist_faza_5_2_phase2_livewire_2025-11-14_REPORT.md)**:

```php
// File: app/Http/Livewire/Products/Management/ProductForm.php
// Lines 2867-2872

'tax_rate_override' => $this->shopTaxRateOverrides[$this->activeShopId] ?? null,
```

**Analysis**:
- ✅ Livewire Phase 2 CORRECTLY implemented save logic
- ✅ Property `shopTaxRateOverrides` populated from UI dropdown
- ✅ Saved to `ProductShopData->tax_rate_override` column
- ✅ NULL value = inherit from product default (expected behavior)

**Conclusion**: Database write layer **DZIAŁA POPRAWNIE** ✅

---

### STEP 2: Verify Job Payload (SyncProductToPrestaShop) ✅

**Hypothesis**: Job nie przekazuje `tax_rate_override` do sync strategy.

**Evidence (Code Analysis - app/Jobs/PrestaShop/SyncProductToPrestaShop.php)**:

```php
// Lines 112-166
public function handle(
    ProductSyncStrategy $strategy,
    PrestaShopClientFactory $factory
): void {
    // ...
    $result = $strategy->syncToPrestaShop($this->product, $client, $this->shop);
    // ...
}
```

**Analysis**:
- ✅ Job receives `Product $product` instance (line 45)
- ✅ Job receives `PrestaShopShop $shop` instance (line 50)
- ✅ Job passes both to `ProductSyncStrategy->syncToPrestaShop()`
- ✅ Product model has access to `dataForShop($shopId)` relationship → ProductShopData
- ✅ ProductShopData contains `tax_rate_override` column

**Conclusion**: Job payload **ZAWIERA WSZYSTKIE POTRZEBNE DANE** ✅

---

### STEP 3: Verify ProductSyncStrategy Integration ✅

**Hypothesis**: ProductSyncStrategy nie przekazuje shop-specific data do ProductTransformer.

**Evidence (Code Analysis - app/Services/PrestaShop/Sync/ProductSyncStrategy.php)**:

```php
// Line 129
$productData = $this->transformer->transformForPrestaShop($model, $client);
```

**Analysis**:
- ✅ Strategy calls `ProductTransformer->transformForPrestaShop($product, $client)`
- ✅ Transformer has access to `$shop` via `$client->getShop()` (line 62)
- ✅ Transformer retrieves `$shopData` via `$product->dataForShop($shop->id)->first()` (line 65)
- ✅ Transformer uses `getEffectiveValue($shopData, $product, 'field')` pattern for other fields

**Conclusion**: ProductSyncStrategy **POPRAWNIE PRZEKAZUJE DANE** ✅

---

### STEP 4: Verify ProductTransformer Tax Mapping ❌ **ROOT CAUSE**

**Hypothesis**: ProductTransformer nie mapuje `tax_rate_override` → `id_tax_rules_group`.

**Evidence (Code Analysis - app/Services/PrestaShop/ProductTransformer.php)**:

#### **Line 130 (PROBLEM):**
```php
// Tax (PrestaShop tax_rules_group_id)
'id_tax_rules_group' => $this->mapTaxRate($product->tax_rate, $shop),
                                            ^^^^^^^^^^^^^^^^^^^
                                            ❌ BŁĄD: Używa globalnego default!
```

#### **Line 65 (AVAILABLE DATA):**
```php
// Get shop-specific data or fallback to product defaults
$shopData = $product->dataForShop($shop->id)->first();
```

#### **Line 294-323 (mapTaxRate Method - CORRECT LOGIC, WRONG INPUT):**
```php
private function mapTaxRate(float $taxRate, PrestaShopShop $shop): int
{
    // Round tax rate to nearest standard rate
    $roundedRate = match (true) {
        $taxRate >= 23 => 23,
        $taxRate >= 8 && $taxRate < 23 => 8,
        $taxRate >= 5 && $taxRate < 8 => 5,
        $taxRate < 5 => 0,
        default => 23,
    };

    // 1. Try shop-configured mapping (preferred - no API calls)
    $configuredId = match ($roundedRate) {
        23 => $shop->tax_rules_group_id_23,
        8 => $shop->tax_rules_group_id_8,
        5 => $shop->tax_rules_group_id_5,
        0 => $shop->tax_rules_group_id_0,
        default => null,
    };

    if ($configuredId !== null) {
        return $configuredId;
    }
    // ... auto-detection fallback ...
}
```

**Analysis**:

1. **mapTaxRate() logic is CORRECT** ✅
   - Properly rounds tax rate (23, 8, 5, 0)
   - Maps to PrestaShop tax_rules_group_id via shop configuration
   - Has auto-detection fallback

2. **INPUT is WRONG** ❌
   - Receives: `$product->tax_rate` (global default - ALWAYS 23.00)
   - Should receive: `$shopData->getEffectiveTaxRate()` (shop override OR product default)

3. **Available Methods (Phase 1 Backend - ProductShopData Model)**:
   ```php
   // Line 787-790 (app/Models/ProductShopData.php)
   public function getEffectiveTaxRate(): float
   {
       return $this->tax_rate_override ?? $this->product->tax_rate ?? 23.00;
   }
   ```

4. **Pattern Used for Other Fields (CORRECT APPROACH)**:
   ```php
   // Line 78 (SKU example)
   'reference' => $this->getEffectiveValue($shopData, $product, 'sku'),

   // Line 83-85 (Name example)
   'name' => $this->buildMultilangField(
       $this->getEffectiveValue($shopData, $product, 'name'),
       $defaultLangId
   ),
   ```

**Conclusion**: ProductTransformer **NIE UŻYWA** shop-specific tax_rate_override! ❌

---

## 🎯 ROOT CAUSE IDENTIFIED

### Exact Failure Point

**File**: `app/Services/PrestaShop/ProductTransformer.php`
**Line**: 130
**Method**: `transformForPrestaShop()`

### Current (BUGGY) Code:

```php
// Line 130
'id_tax_rules_group' => $this->mapTaxRate($product->tax_rate, $shop),
                                            ^^^^^^^^^^^^^^^^^^
                                            PROBLEM: Global default (always 23.00)
```

### Expected (FIXED) Code:

```php
// Option A: Direct call to getEffectiveTaxRate() (RECOMMENDED)
'id_tax_rules_group' => $this->mapTaxRate(
    $shopData?->getEffectiveTaxRate() ?? $product->tax_rate,
    $shop
),

// Option B: Extract to variable for clarity (ALTERNATIVE)
$effectiveTaxRate = $shopData?->getEffectiveTaxRate() ?? $product->tax_rate;
// ...
'id_tax_rules_group' => $this->mapTaxRate($effectiveTaxRate, $shop),
```

### Why Bug Exists

**Phase 1 (Backend)**: ✅ Created `ProductShopData->getEffectiveTaxRate()` method
**Phase 2 (Livewire)**: ✅ Implemented `saveShopSpecificData()` with `tax_rate_override` save
**Phase 3 (Frontend)**: ✅ UI dropdown allows selecting shop-specific tax rate
**Phase 4 (Sync Integration)**: ❌ **MISSED** - ProductTransformer not updated to use new method

**Architecture Compliance**:
- ProductTransformer uses `getEffectiveValue($shopData, $product, 'field')` pattern for ALL fields (SKU, name, description)
- Tax rate should follow SAME pattern
- `getEffectiveValue()` returns `$shopData->field ?? $product->field`
- Tax rate equivalent: `$shopData->getEffectiveTaxRate()` (returns override OR product default)

---

## 📊 DIAGNOSIS EVIDENCE SUMMARY

### Layer 1: Database Write (ProductForm) ✅
- **Status**: DZIAŁA POPRAWNIE
- **Evidence**: Phase 2 report confirms `tax_rate_override` saved to DB
- **File**: `app/Http/Livewire/Products/Management/ProductForm.php` (line 2870)

### Layer 2: Job Dispatch (SyncProductToPrestaShop) ✅
- **Status**: DZIAŁA POPRAWNIE
- **Evidence**: Job receives `Product` instance with access to `dataForShop()` relationship
- **File**: `app/Jobs/PrestaShop/SyncProductToPrestaShop.php` (line 166)

### Layer 3: Sync Strategy (ProductSyncStrategy) ✅
- **Status**: DZIAŁA POPRAWNIE
- **Evidence**: Strategy passes `$product` and `$client` to transformer
- **File**: `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` (line 129)

### Layer 4: Data Transformation (ProductTransformer) ❌ **FAILURE POINT**
- **Status**: BUG ZIDENTYFIKOWANY
- **Evidence**: Line 130 uses `$product->tax_rate` instead of `$shopData->getEffectiveTaxRate()`
- **File**: `app/Services/PrestaShop/ProductTransformer.php` (line 130)

---

## 🔧 FIX SPECIFICATION

### Next Agent: **laravel-expert** (Backend Layer Fix)

### Task: Update ProductTransformer to use shop-specific tax_rate_override

### File to Modify:
`app/Services/PrestaShop/ProductTransformer.php`

### Exact Change Required:

**Location**: Line 130 (inside `transformForPrestaShop()` method)

**BEFORE (BUGGY):**
```php
// Tax (PrestaShop tax_rules_group_id)
'id_tax_rules_group' => $this->mapTaxRate($product->tax_rate, $shop),
```

**AFTER (FIXED):**
```php
// Tax (PrestaShop tax_rules_group_id) - FAZA 5.2 Integration (2025-11-14)
// Use shop-specific tax_rate_override if set, otherwise fall back to product default
$effectiveTaxRate = $shopData?->getEffectiveTaxRate() ?? $product->tax_rate;
'id_tax_rules_group' => $this->mapTaxRate($effectiveTaxRate, $shop),
```

**Alternative (Inline - More Compact):**
```php
// Tax (PrestaShop tax_rules_group_id) - FAZA 5.2 Integration (2025-11-14)
'id_tax_rules_group' => $this->mapTaxRate(
    $shopData?->getEffectiveTaxRate() ?? $product->tax_rate,
    $shop
),
```

### Validation:

**Add Debug Logging (Temporary):**
```php
// AFTER calculating effective tax rate, BEFORE mapTaxRate() call
Log::debug('[FAZA 5.2 FIX] ProductTransformer tax rate calculation', [
    'product_id' => $product->id,
    'shop_id' => $shop->id,
    'product_tax_rate' => $product->tax_rate,
    'shop_tax_rate_override' => $shopData?->tax_rate_override,
    'effective_tax_rate' => $effectiveTaxRate,
    'mapped_group_id' => $this->mapTaxRate($effectiveTaxRate, $shop),
]);
```

**Expected Log Output (Product 11033, Shop "B2B Test DEV", User selects 8%):**
```
[2025-11-14 XX:XX:XX] [FAZA 5.2 FIX] ProductTransformer tax rate calculation
    product_id: 11033
    shop_id: 1
    product_tax_rate: 23.00          ← Global default
    shop_tax_rate_override: 8.00     ← User's choice in Shop Tab
    effective_tax_rate: 8.00         ← Correctly resolved
    mapped_group_id: 2               ← PrestaShop tax_rules_group_id for 8%
```

### Integration Points:

**Phase 1 Backend (ALREADY IMPLEMENTED):**
- ✅ `ProductShopData->getEffectiveTaxRate()` method exists (line 787-790)
- ✅ Returns `tax_rate_override ?? product->tax_rate ?? 23.00`
- ✅ Handles NULL override (inherit from product default)

**Phase 2 Livewire (ALREADY IMPLEMENTED):**
- ✅ `saveShopSpecificData()` saves `tax_rate_override` to DB
- ✅ UI dropdown allows selecting shop-specific tax rate

**Phase 3 Frontend (ALREADY IMPLEMENTED):**
- ✅ Blade template renders dropdown with available tax rates
- ✅ Livewire reactive updates on selection change

**Phase 4 Sync Integration (THIS FIX):**
- ❌ ProductTransformer MUST use `getEffectiveTaxRate()` method
- ❌ Currently uses `$product->tax_rate` (global default)

---

## 🧪 TESTING STRATEGY

### Pre-Deployment Test (Local/Development)

**Test Product**: Create test product with variants
**Test Shop**: "B2B Test DEV" (Shop ID 1) - has 4 tax rules configured

**Scenario 1: Default Tax Rate (No Override)**
1. Create product with `tax_rate = 23.00`
2. Do NOT set shop override (leave NULL)
3. Sync to PrestaShop
4. Expected: `id_tax_rules_group = 6` (23% VAT for Shop 1)

**Scenario 2: Shop Override (8%)**
1. Edit product, switch to Shop Tab
2. Select "VAT 8.00%" from dropdown
3. Save → `tax_rate_override = 8.00` in DB
4. Sync to PrestaShop
5. Expected: `id_tax_rules_group = 2` (8% VAT for Shop 1)

**Scenario 3: Change Override (8% → 5%)**
1. Edit product, Shop Tab
2. Change from 8% to 5%
3. Save → `tax_rate_override = 5.00`
4. Sync to PrestaShop
5. Expected: `id_tax_rules_group = 3` (5% VAT for Shop 1)

**Scenario 4: Clear Override (Inherit Default)**
1. Edit product, Shop Tab
2. Select "Użyj domyślnej PPM (23.00%)"
3. Save → `tax_rate_override = NULL`
4. Sync to PrestaShop
5. Expected: `id_tax_rules_group = 6` (23% VAT - product default)

### Production Test (After Deployment)

**Test Product**: ID 11033 (real product with shop data)
**Test Shop**: "B2B Test DEV" (Shop ID 1)

**Steps**:
1. Edit product 11033
2. Switch to Shop Tab → "B2B Test DEV"
3. Change tax rate to 8%
4. Save
5. Trigger sync (manual or auto)
6. Read production logs:
   ```powershell
   plink ... "tail -100 storage/logs/laravel.log" | Select-String "FAZA 5.2 FIX"
   ```
7. Verify PrestaShop product:
   - API GET /products/[external_id]
   - Check `<id_tax_rules_group>2</id_tax_rules_group>`

---

## ⚠️ PROBLEMY/BLOKERY

**BRAK** - Diagnosis completed successfully.

**Dependencies Met**:
- ✅ Phase 1 Backend: `getEffectiveTaxRate()` method implemented
- ✅ Phase 2 Livewire: `saveShopSpecificData()` saves override
- ✅ Phase 3 Frontend: UI dropdown functional
- ✅ ProductTransformer: `mapTaxRate()` logic correct
- ✅ PrestaShopShop: `tax_rules_group_id_XX` columns configured

**Ready for Fix**: All components in place, single-line fix required.

---

## 📋 NASTĘPNE KROKI

### Immediate (laravel-expert)

1. **Update ProductTransformer.php** (5 min):
   - Line 130: Replace `$product->tax_rate` with `$shopData?->getEffectiveTaxRate() ?? $product->tax_rate`
   - Add debug logging (temporary)
   - Verify no other usages of `$product->tax_rate` in transformer

2. **Local Test** (10 min):
   - Run all 4 test scenarios
   - Verify debug logs show correct values
   - Confirm `id_tax_rules_group` mapped correctly

3. **Code Review** (5 min):
   - Verify fix aligns with existing pattern (`getEffectiveValue()`)
   - Ensure null-safe operator `?->` used
   - Check PHPDoc comments updated

### Deployment (deployment-specialist)

1. **Upload Fixed File** (3 min):
   ```powershell
   pscp ProductTransformer.php → production
   php artisan cache:clear
   ```

2. **Production Test** (5 min):
   - Edit product 11033
   - Change tax rate to 8%
   - Trigger sync
   - Verify logs

3. **Verify PrestaShop** (3 min):
   - API GET product
   - Check `id_tax_rules_group` field
   - Compare with expected value

### Cleanup (After User Confirms)

1. **Remove Debug Logs** (2 min):
   - Remove `[FAZA 5.2 FIX]` debug log
   - Keep only production logs (`Log::info/warning/error`)

2. **Update Plan** (2 min):
   - Mark FAZA 5.2 as ✅ COMPLETED
   - Update report with final status

**Total Estimated Time**: 35 minutes (fix + test + deploy + verify)

---

## 📁 PLIKI

### Files Analyzed (Read-Only)

**Backend Layer:**
- `app/Models/ProductShopData.php` - Verified `getEffectiveTaxRate()` method exists ✅
- `app/Models/Product.php` - Verified `dataForShop()` relationship ✅
- `app/Services/TaxRateService.php` - Verified Phase 1 implementation ✅

**Livewire Layer:**
- `app/Http/Livewire/Products/Management/ProductForm.php` - Verified save logic ✅

**Job Layer:**
- `app/Jobs/PrestaShop/SyncProductToPrestaShop.php` - Verified job payload ✅

**Sync Layer:**
- `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` - Verified strategy integration ✅
- `app/Services/PrestaShop/ProductTransformer.php` - **IDENTIFIED BUG** ❌

### File to Modify (Fix Required)

**app/Services/PrestaShop/ProductTransformer.php**
- Line 130: Replace `$product->tax_rate` with `$shopData?->getEffectiveTaxRate() ?? $product->tax_rate`
- Status: ❌ BUG IDENTIFIED → Ready for fix by laravel-expert

### Reports Read

**Architecture:**
- `_AGENT_REPORTS/architect_faza_5_2_tax_rate_productform_2025-11-14_REPORT.md`

**Implementation:**
- `_AGENT_REPORTS/laravel_expert_faza_5_2_phase1_backend_2025-11-14_REPORT.md`
- `_AGENT_REPORTS/livewire_specialist_faza_5_2_phase2_livewire_2025-11-14_REPORT.md`
- `_AGENT_REPORTS/livewire_specialist_faza_5_2_shop_tax_rules_fix_2025-11-14_REPORT.md`

---

## 🎓 COMPLIANCE & BEST PRACTICES

### Context7 Integration: ✅

**Laravel 12.x Patterns:**
- ✅ Eloquent relationship usage (`dataForShop()`)
- ✅ Null-safe operator (`?->`) for optional chaining
- ✅ Null coalescing (`??`) for fallback values

### PPM-CC-Laravel Architecture: ✅

**Multi-Store Support:**
- ✅ Shop-specific data inheritance pattern
- ✅ `getEffectiveValue()` pattern for all fields
- ✅ Tax rate should follow SAME pattern

**Service Layer:**
- ✅ ProductTransformer responsible for data transformation
- ✅ TaxRateService handles business logic
- ✅ Clear separation of concerns

### CLAUDE.md Compliance: ✅

**Debug Logging Workflow:**
- ✅ Development: Add `[FAZA 5.2 FIX]` debug logs
- ✅ Wait for user: "działa idealnie"
- ✅ Production: Remove debug logs

**Enterprise Quality:**
- ✅ Minimal change (1 line modified)
- ✅ Follows existing pattern
- ✅ Strong typing maintained

**Systematic Debugging:**
- ✅ 4-layer diagnosis (Database → Job → Strategy → Transformer)
- ✅ Evidence collected at each layer
- ✅ Root cause identified with exact location

---

## 📈 PODSUMOWANIE

**Diagnosis Status**: ✅ **COMPLETED**

**Root Cause**: ProductTransformer uses `$product->tax_rate` (global default) instead of `$shopData->getEffectiveTaxRate()` (shop override).

**Exact Location**: `app/Services/PrestaShop/ProductTransformer.php` - Line 130

**Fix Complexity**: ⭐ TRIVIAL (1 line change)

**Next Agent**: **laravel-expert** (Backend layer fix)

**Estimated Fix Time**: 35 minutes (fix + test + deploy + verify)

**Critical Impact**: 🔴 HIGH
- Business logic broken (wrong VAT rates)
- Production affected (all syncs since FAZA 5.2 deployment)
- User complaint confirmed (tax rate not syncing)

**Ready for Implementation**: ✅ All dependencies met, fix specified, test plan ready.

---

## 🚀 FINAL MESSAGE (DO KOORDYNATORA)

✅ **FAZA 5.2 CRITICAL BUG DIAGNOSIS COMPLETED**

**Root Cause**: ProductTransformer Line 130 - używa `$product->tax_rate` zamiast `$shopData->getEffectiveTaxRate()`

**Location**: `app/Services/PrestaShop/ProductTransformer.php:130`

**Evidence**:
- Database: `product_shop_data.tax_rate_override` zapisuje się poprawnie ✅
- Job payload: CONTAINS Product instance with dataForShop() access ✅
- ProductSyncStrategy: Correctly passes data to transformer ✅
- ProductTransformer: **MISSING shop-specific tax_rate integration** ❌

**Fix Recommendation**:
- **Next Agent**: laravel-expert
- **Task**: Replace `$product->tax_rate` with `$shopData?->getEffectiveTaxRate() ?? $product->tax_rate` in ProductTransformer.php line 130
- **Files to modify**: 1 file, 1 line change
- **ETA**: 35 minutes (fix + test + deploy + verify)

**Report**: `_AGENT_REPORTS/debugger_faza_5_2_tax_rate_sync_critical_bug_2025-11-14_REPORT.md`

**CRITICAL**: This blocks production tax rate sync - **HIGH PRIORITY FIX!**

All evidence documented, fix specified with exact code change, test scenarios prepared.

Ready for laravel-expert implementation.

---

**END OF REPORT**
