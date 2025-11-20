# PrestaShop Import Crash na 404 Error

**Status:** ✅ RESOLVED (2025-11-12)
**Severity:** Medium
**Component:** PrestaShop Integration / Import Job
**Bug ID:** BUG #8

---

## Problem Description

Import job `PullProductsFromPrestaShop` crashował całkowicie przy pierwszym napotkaniu błędu 404 (produkt usunięty z PrestaShop).

### Symptoms

- Import job kończy się wyjątkiem przy pierwszym 404 error
- Pozostałe produkty nie są importowane (total failure)
- `prestashop_product_id` pozostaje w bazie mimo że produkt nie istnieje w PrestaShop
- Brak możliwości re-sync produktu w przyszłości
- Logs zawierają `Log::error` z PrestaShopAPIException 404

### Root Cause

**Brak graceful handling dla produktów usuniętych z PrestaShop.**

**Scenariusz błędu:**
1. Produkt zsynchronizowany PPM → PrestaShop (otrzymał `prestashop_product_id`)
2. `product_shop_data.prestashop_product_id` = 123
3. Produkt usunięty z PrestaShop (manualnie lub przez API)
4. PPM nie wie o usunięciu (brak webhooków)
5. Import job próbuje pobrać produkt ID 123
6. PrestaShop API zwraca **404 Not Found**
7. `BasePrestaShopClient::makeRequest()` rzuca `PrestaShopAPIException`
8. Cały import job FAILS (line 250-268 w `PullProductsFromPrestaShop.php`)

### Affected Code

**Before Fix:**

```php
// PullProductsFromPrestaShop.php (lines 213-220)
} catch (\Exception $e) {
    Log::error('Failed to pull product from PrestaShop', [
        'product_id' => $product->id,
        'shop_id' => $this->shop->id,
        'error' => $e->getMessage(),
    ]);
    $errors++;
    // Problem: prestashop_product_id NOT cleared!
}
```

**Problem:** Generic catch nie rozróżnia między 404 (permanent error) vs inne błędy (temporary/retryable).

---

## Solution Implemented

**Rozwiązanie #1: Graceful 404 Handling** (z 3 alternatywnych rozwiązań - patrz raport diagnozy)

### Architecture

**Flow:**
```
PullProductsFromPrestaShop Job
  ↓
  try {
    getProduct(prestashop_product_id)
    importPrices()
    importStock()
  }
  ↓
  catch (PrestaShopAPIException $e) {
    ↓
    if ($e->isNotFound()) {  ← NEW LOGIC
      ↓
      [UNLINK PRODUCT]
      prestashop_product_id = NULL
      sync_status = 'not_synced'
      last_sync_error = 'Product deleted from PrestaShop (404)'
      ↓
      continue;  ← Don't crash entire job
    }
  }
```

### Code Changes

#### 1. PrestaShopAPIException - Added Helper Method

**File:** `app/Exceptions/PrestaShopAPIException.php`

```php
/**
 * Check if this is a 404 Not Found error
 *
 * @return bool True if HTTP 404 error
 */
public function isNotFound(): bool
{
    return $this->httpStatusCode === 404;
}
```

**Benefits:**
- Czytelność: `$e->isNotFound()` > `$e->getHttpStatusCode() === 404`
- Reusability: Może być używane w innych jobsach/services

---

#### 2. PullProductsFromPrestaShop - Graceful 404 Handling

**File:** `app/Jobs/PullProductsFromPrestaShop.php`

**Added import:**
```php
use App\Exceptions\PrestaShopAPIException;
```

**Main catch block:**
```php
} catch (PrestaShopAPIException $e) {
    // BUG #8 FIX #1: GRACEFUL 404 HANDLING
    if ($e->isNotFound()) {
        Log::warning('Product not found in PrestaShop (404), unlinking', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'shop_id' => $this->shop->id,
            'prestashop_product_id' => $shopData->prestashop_product_id,
            'action' => 'unlinked',
        ]);

        // Clear PrestaShop link - allow re-sync in future
        $shopData->update([
            'prestashop_product_id' => null,
            'sync_status' => 'not_synced',
            'last_sync_error' => 'Product deleted from PrestaShop (404)',
        ]);

        $errors++;
        continue; // Skip to next product
    }

    // Other PrestaShop API errors (rate limit, auth, server error)
    Log::error('PrestaShop API error during pull', [
        'product_id' => $product->id,
        'shop_id' => $this->shop->id,
        'error_code' => $e->getHttpStatusCode(),
        'error_category' => $e->getErrorCategory(),
        'error' => $e->getMessage(),
    ]);
    $errors++;
    continue;
}
```

**Inner catches (price/stock import):**
```php
} catch (PrestaShopAPIException $priceError) {
    // BUG #8 FIX #1: 404 = product deleted, re-throw to trigger unlinking
    if ($priceError->isNotFound()) {
        throw $priceError; // Re-throw to outer catch
    }

    // Other errors - log but continue
    Log::warning('Failed to import prices (non-404)', [...]);
}
```

**Benefits:**
- 404 → automatic unlinking (clear prestashop_product_id)
- Import job continues with other products (no total failure)
- Log::warning dla 404 (expected behavior), Log::error dla innych błędów
- Audit trail (last_sync_error zawiera przyczynę)

---

#### 3. PrestaShopPriceImporter & PrestaShopStockImporter - Re-throw Pattern

**Files:**
- `app/Services/PrestaShop/PrestaShopPriceImporter.php`
- `app/Services/PrestaShop/PrestaShopStockImporter.php`

**Changed catch block:**
```php
} catch (\App\Exceptions\PrestaShopAPIException $e) {
    // BUG #8 FIX #1: Re-throw PrestaShop API exceptions (including 404)
    // Caller (PullProductsFromPrestaShop) will handle 404 specifically
    Log::info('PrestaShop API error during price import', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'http_status' => $e->getHttpStatusCode(),
        'is_404' => $e->isNotFound(),
        'error' => $e->getMessage(),
    ]);

    throw $e; // Re-throw to caller

} catch (\Exception $e) {
    Log::error('Price import failed (generic error)', [...]);
    throw $e;
}
```

**Benefits:**
- Centralized 404 handling w job (nie w każdym service)
- Importer services nie muszą wiedzieć o unlinking logic
- Clear separation of concerns

---

## Testing

### Unit Tests

**Script:** `_TEMP/test_bug8_fix_404_handling_unit.php`

**Results:** ✅ ALL TESTS PASSED (7/7)

```
✅ PrestaShopAPIException::isNotFound() exists
✅ isNotFound() returns true for 404
✅ isNotFound() returns false for non-404 (500, 401)
✅ PullProductsFromPrestaShop imports PrestaShopAPIException
✅ PullProductsFromPrestaShop has 404 handling logic
✅ PrestaShopPriceImporter re-throws PrestaShopAPIException
✅ PrestaShopStockImporter re-throws PrestaShopAPIException
```

### Integration Tests

**Script:** `_TEMP/test_bug8_fix_404_handling.php`

**Status:** ⚠️ Not executed - No active PrestaShop shop with valid API key

**Manual Test Procedure:**
```bash
# 1. Usuń produkt z PrestaShop (admin panel)
# 2. Uruchom import job:
php artisan tinker
>>> $shop = App\Models\PrestaShopShop::find(SHOP_ID);
>>> PullProductsFromPrestaShop::dispatchSync($shop);

# 3. Sprawdź logi:
tail -f storage/logs/laravel.log | grep "404"

# 4. Zweryfikuj unlinking:
>>> $shopData = App\Models\ProductShopData::where('product_id', PRODUCT_ID)
                ->where('shop_id', SHOP_ID)->first();
>>> $shopData->prestashop_product_id; // Should be NULL
>>> $shopData->sync_status; // Should be 'not_synced'
>>> $shopData->last_sync_error; // Should contain "404"
```

---

## Impact

### Before Fix
- ❌ Import job crashuje przy pierwszym 404
- ❌ Pozostałe produkty nie są importowane
- ❌ Nieprawidłowy link w bazie (prestashop_product_id dla nieistniejącego produktu)
- ❌ Brak audit trail (dlaczego import failed)

### After Fix
- ✅ Import job kontynuuje po 404 (graceful degradation)
- ✅ Automatyczne unlinking produktów usuniętych z PrestaShop
- ✅ Możliwość re-sync w przyszłości (prestashop_product_id = NULL)
- ✅ Clear audit trail (last_sync_error = "Product deleted from PrestaShop (404)")
- ✅ Proper logging (Log::warning dla 404, Log::error dla innych błędów)

---

## Prevention

### Best Practices Applied

1. **Exception hierarchy** - Specific catch (`PrestaShopAPIException`) przed generic (`\Exception`)
2. **Re-throw pattern** - Inner services re-throw exceptions do caller'a
3. **Graceful degradation** - Jeden błędny produkt nie crashuje całego importu
4. **HTTP 404 = permanent error** - Nie retry, tylko unlink
5. **Helper methods** - `isNotFound()` dla czytelności
6. **Comprehensive logging** - Różne log levels dla różnych błędów

### Future Enhancements (Optional)

1. **Pre-Import Validation** (Rozwiązanie #3):
   ```php
   // BasePrestaShopClient.php
   public function productExists(int $productId): bool
   {
       try {
           $this->makeRequest('GET', "/products/{$productId}?display=[id]");
           return true;
       } catch (PrestaShopAPIException $e) {
           return !$e->isNotFound();
       }
   }
   ```
   **Benefit:** Wykryj 404 przed price/stock import (early detection)
   **Trade-off:** Dodatkowy API call dla każdego produktu

2. **Webhook Support:**
   - PrestaShop webhook: product.delete → notify PPM
   - Immediate unlinking (nie czekaj na scheduled import job)

3. **Admin Notification:**
   ```php
   AdminNotification::create([
       'type' => 'warning',
       'title' => 'Product unlinked from PrestaShop',
       'message' => "Product {$product->sku} was deleted from {$shop->name}",
   ]);
   ```

---

## Related Issues

### BUG #8.1: DecryptException for Test Shop

**Status:** Partially Fixed (shop deactivated)

**Problem:** Test shop "Test Shop Sync Verification" has plaintext API key (not encrypted)

**Fix:**
```bash
php artisan tinker
>>> $shop = App\Models\PrestaShopShop::where('name', 'Test Shop Sync Verification')->first();
>>> $shop->api_key = encrypt('TEST_SYNC_VERIFICATION_API_KEY_686a2e59c5eda506d6bfb0c7492169d1');
>>> $shop->save();
>>> $shop->is_active = true; // Re-activate after fixing encryption
>>> $shop->save();
```

**Alternative:** Usuń test shop jeśli nie jest używany

---

## References

- **Diagnosis Report:** `_AGENT_REPORTS/debugger_bug8_404_import_2025-11-12_REPORT.md`
- **Implementation Report:** `_AGENT_REPORTS/laravel_expert_bug8_fix1_graceful_404_2025-11-12_REPORT.md`
- **Debug Logging Guide:** `_ISSUES_FIXES/DEBUG_LOGGING_BEST_PRACTICES.md`
- **Unit Test Script:** `_TEMP/test_bug8_fix_404_handling_unit.php`
- **Integration Test Script:** `_TEMP/test_bug8_fix_404_handling.php`

---

## Quick Reference

### How to Test Fix

```bash
# Run unit tests
php _TEMP/test_bug8_fix_404_handling_unit.php

# Run integration test (requires active shop + product with invalid PS ID)
php _TEMP/test_bug8_fix_404_handling.php
```

### How to Verify in Production

```bash
# Check logs for 404 handling
tail -f storage/logs/laravel.log | grep "404"

# Find unlinked products
php artisan tinker
>>> App\Models\ProductShopData::whereNull('prestashop_product_id')
       ->where('last_sync_error', 'like', '%404%')->get();
```

### How to Re-sync Unlinked Product

```bash
# In admin panel: Products → Edit Product → Shops Tab → Select Shop → Save
# This will trigger new sync and create new prestashop_product_id
```

---

**Fixed By:** laravel_expert agent
**Date:** 2025-11-12
**Version:** Laravel 12.x
**Status:** ✅ RESOLVED - Ready for production deployment
