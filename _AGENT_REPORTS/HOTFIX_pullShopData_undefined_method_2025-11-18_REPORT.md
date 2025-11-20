# HOTFIX: pullShopData() - Call to undefined method PrestaShopService::getProduct()

**Data:** 2025-11-18 18:45
**Priorytet:** 🔥 CRITICAL
**Status:** ✅ DEPLOYED

---

## 🎯 PROBLEM

**User Report:** "Wczytaj z aktualnego sklepu" powoduje błąd `Error: Call to undefined method App\Services\PrestaShop\PrestaShopService::getProduct()`

**Symptoms:**
- Kliknięcie przycisku "Wczytaj z aktualnego sklepu"
- Fatal error PHP
- Button "Wczytaj z aktualnego sklepu" nie działa

**Root Cause:**
```php
// pullShopData() Line 3910 - BEFORE FIX
$prestaShopService = app(\App\Services\PrestaShop\PrestaShopService::class);
$productData = $prestaShopService->getProduct($shop, $this->product->sku);  // ❌ Method doesn't exist!
```

**Problem:**
- `PrestaShopService` NIE MA metody `getProduct($shop, $sku)`
- Właściwa architektura: `PrestaShopClientFactory::create($shop)` → `$client->getProduct($id)`

---

## ✅ ROZWIĄZANIE

### FIX 2025-11-18 (#6): Use PrestaShopClientFactory + SKU Search Fallback

**1. Proper Client Creation - Lines 3908-3910:**
```php
// FIX 2025-11-18 (#6): Use PrestaShopClientFactory instead of PrestaShopService
// (PrestaShopService doesn't have getProduct() method)
$client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
```

**2. Smart Product Lookup Strategy - Lines 3912-3946:**
```php
// Get ProductShopData to check for prestashop_product_id
$productShopData = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('shop_id', $shopId)
    ->first();

// Try to fetch product from PrestaShop
$prestashopData = null;

if ($productShopData && $productShopData->prestashop_product_id) {
    // Product already synced - fetch by ID
    try {
        $prestashopData = $client->getProduct($productShopData->prestashop_product_id);
    } catch (\Exception $e) {
        // Product not found by ID - try search by SKU
        Log::warning('[PULL SHOP DATA] Product not found by ID, trying SKU search', [
            'prestashop_id' => $productShopData->prestashop_product_id,
            'sku' => $this->product->sku,
        ]);
    }
}

// If not found by ID, search by SKU (reference)
if (!$prestashopData) {
    $products = $client->getProducts(['filter[reference]' => $this->product->sku]);

    if (empty($products)) {
        $this->activeJobStatus = 'failed';
        $this->jobResult = 'error';
        $this->dispatch('error', message: 'Nie znaleziono produktu w sklepie PrestaShop (SKU: ' . $this->product->sku . ')');
        return;
    }

    // Get full product data for first match
    $prestashopData = $client->getProduct($products[0]['id']);
}
```

**3. Response Unwrapping - Lines 3948-3951:**
```php
// Unwrap nested response (PrestaShop API wraps in 'product' key)
if (isset($prestashopData['product'])) {
    $prestashopData = $prestashopData['product'];
}
```

**4. Data Extraction - Lines 3953-3961:**
```php
// Extract essential data
$productData = [
    'id' => $prestashopData['id'] ?? null,
    'name' => data_get($prestashopData, 'name.0.value') ?? data_get($prestashopData, 'name'),
    'description_short' => data_get($prestashopData, 'description_short.0.value') ?? data_get($prestashopData, 'description_short'),
    'description' => data_get($prestashopData, 'description.0.value') ?? data_get($prestashopData, 'description'),
    'price' => $prestashopData['price'] ?? null,
    'active' => $prestashopData['active'] ?? null,
];
```

---

## 🧪 FLOW ANALYSIS

### BEFORE FIX:
```
1. User clicks "Wczytaj z aktualnego sklepu"
   ↓
2. pullShopData($shopId) → app(PrestaShopService::class)
   ↓
3. $prestaShopService->getProduct($shop, $sku)  ❌ Method doesn't exist!
   ↓
4. FATAL ERROR: Call to undefined method
```

### AFTER FIX:
```
1. User clicks "Wczytaj z aktualnego sklepu"
   ↓
2. pullShopData($shopId) → PrestaShopClientFactory::create($shop) ✅
   ↓
3. Try Strategy #1: getProduct($prestashop_product_id) (if already synced)
   ├─ SUCCESS → Use fetched data ✅
   └─ FAIL → Try Strategy #2
   ↓
4. Try Strategy #2: getProducts(filter[reference]=SKU) + getProduct(first_match_id)
   ├─ FOUND → Use fetched data ✅
   └─ NOT FOUND → Error: "Nie znaleziono produktu"
   ↓
5. Update ProductShopData with fetched data
   ↓
6. Reload form + update cache
   ↓
7. Show success message ✅
```

---

## 📊 BENEFITS

### 1. Proper Architecture
- ✅ Uses `PrestaShopClientFactory` (correct pattern)
- ✅ Consistent with `loadShopDataToForm()` (Line 5164)
- ✅ Works with both PrestaShop8Client and PrestaShop9Client

### 2. Smart Fallback Strategy
- ✅ **Strategy #1:** Fetch by prestashop_product_id (faster, if available)
- ✅ **Strategy #2:** Search by SKU + fetch by ID (fallback)
- ✅ Handles edge cases (product deleted in PrestaShop, ID mismatch)

### 3. Error Handling
- ✅ Graceful degradation (fallback to SKU search)
- ✅ Clear error messages for user
- ✅ Logging for diagnostics

---

## 📦 DEPLOYMENT

### Files Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (226 kB)
   - Lines 3908-3967: Complete rewrite of pullShopData() product fetching logic

### Deployment Steps:
```bash
# 1. Upload ProductForm.php
pscp -i $HostidoKey -P 64321 "app\Http\Livewire\Products\Management\ProductForm.php" \
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

# 2. Clear caches
plink ... -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"
```

### Production Status:
- ✅ File uploaded (226 kB)
- ✅ Caches cleared
- ✅ Zero errors in Laravel logs
- ⏳ Awaiting user testing

---

## 🧪 TESTING GUIDE

### Test Case: "Wczytaj z aktualnego sklepu"

**URL:** https://ppm.mpptrade.pl/admin/products/11033/edit

**Scenario #1: Product Already Synced**
1. Hard refresh: **Ctrl+Shift+R**
2. Przełącz na sklep który MA produkt zsynchronizowany (prestashop_product_id exists)
3. Kliknij **"Wczytaj z aktualnego sklepu"**

**Expected:**
- ✅ Button shows loading state
- ✅ No errors
- ✅ Success message: "Wczytano dane ze sklepu [nazwa]"
- ✅ Form fields populated with PrestaShop data
- ✅ "Szczegóły synchronizacji" shows updated timestamp

**Scenario #2: Product NOT Synced (SKU Search)**
1. Hard refresh: **Ctrl+Shift+R**
2. Przełącz na sklep gdzie produkt istnieje w PrestaShop (po SKU) ale NIE ma prestashop_product_id w PPM
3. Kliknij **"Wczytaj z aktualnego sklepu"**

**Expected:**
- ✅ SKU search successful (fallback strategy)
- ✅ Success message: "Wczytano dane ze sklepu [nazwa]"
- ✅ Form fields populated
- ✅ prestashop_product_id NOW saved to ProductShopData

**Scenario #3: Product NOT Found**
1. Hard refresh: **Ctrl+Shift+R**
2. Przełącz na sklep gdzie produkt NIE ISTNIEJE w PrestaShop
3. Kliknij **"Wczytaj z aktualnego sklepu"**

**Expected:**
- ✅ Error message: "Nie znaleziono produktu w sklepie PrestaShop (SKU: XXX)"
- ✅ No fatal error
- ✅ Form state unchanged

**Verification (Backend):**
```powershell
plink ... "tail -100 storage/logs/laravel.log" | grep "PULL SHOP DATA"
```

Expected logs:
```
[ETAP_13 SINGLE SHOP PULL] Product data pulled successfully
prestashop_id: 123
shop_name: Test KAYO
```

---

## 🔗 SESSION CHAIN

**ETAP_13 Fix Chain (2025-11-18 Session):**
1-12. [Previous fixes - queue worker, button types, targeted save, false positives, status typo]
13. ✅ **pullShopData() Client Fix** (FIX #6) ← THIS REPORT

**Total Session Fixes:** 13 critical issues resolved
**Production Status:** All features deployed, awaiting user verification

---

## 📁 FILES

### Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (Lines 3908-3967)

### Reports:
1-12. [Previous session reports]
13. `_AGENT_REPORTS/HOTFIX_pullShopData_undefined_method_2025-11-18_REPORT.md` ← **THIS REPORT**

---

## 📋 NEXT STEPS

### IMMEDIATE (User)
- [ ] **Manual Testing - Scenario #1** (Product synced): Verify pullShopData works with prestashop_product_id
- [ ] **Manual Testing - Scenario #2** (SKU search): Verify fallback strategy works
- [ ] **Manual Testing - Scenario #3** (Not found): Verify graceful error handling

### CONSOLIDATED TESTING (All Fixes)
After individual testing, verify ALL 3 fixes together:
- [ ] **FIX #4:** "Dodaj do sklepu" → ONLY selected shop gets job
- [ ] **FIX #5:** "Oczekujące zmiany" → NO false positives (Cena, Opis)
- [ ] **FIX #6:** "Wczytaj z aktualnego sklepu" → Works without errors

---

**Report Generated:** 2025-11-18 18:50
**Status:** ✅ DEPLOYED - Ready for user testing
**Next Action:** User tests FIX #6 → Confirms all 3 fixes work → "działa idealnie" → Debug cleanup → ETAP_13 COMPLETE

**Key Achievement:** Eliminated fatal error in pullShopData() + implemented smart fallback strategy (ID + SKU search)
