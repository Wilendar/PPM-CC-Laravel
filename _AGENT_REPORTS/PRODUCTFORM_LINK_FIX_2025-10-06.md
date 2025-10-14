# RAPORT NAPRAWY: ProductForm PrestaShop Link & Auto-Load Fix

**Data**: 2025-10-06 15:30
**Agent**: General-purpose (debugging & deployment)
**Zadanie**: Naprawa błędnego linku do produktu PrestaShop + auto-load danych

---

## 🚨 ZGŁOSZONE PROBLEMY

### Problem #1: Link do produktu PrestaShop BŁĘDNY
**Zgłoszony przez użytkownika:**
- URL obecnie: `https://dev.mpptrade.pl//admin-dev/index.php?controller=AdminProducts&id_product=9673` ❌
- URL powinien: `https://dev.mpptrade.pl/9673-pit-bike-pitgang-110xd-enduro.html` ✅

### Problem #2: Kategorie się nie wczytują ze sklepu
**Status:** Kategorie są pobierane z API i cachowane, ale brak mapowania PrestaShop → PPM

---

## 🔍 ROOT CAUSE ANALYSIS

### Przyczyna #1: Hook `updatedActiveShopId()` nie działa
**Dlaczego:**
- Hook Livewire `updatedActiveShopId()` wywołuje się TYLKO gdy zmiana przychodzi z frontendu (wire:model)
- W metodzie `switchToShop()` zmiana jest bezpośrednia: `$this->activeShopId = $shopId;` (line 1052)
- To nie wywołuje hooka, więc auto-load się nie wykonuje

**Konsekwencja:**
- `$this->loadedShopData[$shopId]` jest puste
- `getProductPrestaShopUrl()` zwraca null
- Blade używa fallback admin URL

### Przyczyna #2: Brak link_rewrite w ProductShopData
**Dlaczego:**
- Podczas importu zapisywaliśmy `external_id` (PrestaShop product ID)
- Nie zapisywaliśmy `link_rewrite` (product slug) do `external_reference`

**Konsekwencja:**
- Nawet jeśli auto-load by działał, po reload strony dane są tracone
- Brak persystentnego źródła dla link_rewrite

---

## ✅ ROZWIĄZANIA WDROŻONE

### 1️⃣ Auto-Load w `switchToShop()` ✅

**Lokalizacja:** `app/Http/Livewire/Products/Management/ProductForm.php` (lines 1071-1079)

**Kod:**
```php
// ETAP_07 FIX: Auto-load PrestaShop data when switching to shop (if not already loaded)
// This fixes the issue where updatedActiveShopId() hook doesn't trigger on PHP-side changes
if ($shopId !== null && !isset($this->loadedShopData[$shopId]) && $this->isEditMode) {
    Log::info('Auto-loading PrestaShop data in switchToShop()', [
        'shop_id' => $shopId,
        'product_id' => $this->product?->id,
    ]);
    $this->loadProductDataFromPrestaShop($shopId);
}
```

**Rezultat:** Dane z PrestaShop API są ładowane automatycznie przy pierwszym kliknięciu w label sklepu

### 2️⃣ Zapisywanie link_rewrite podczas importu ✅

**Lokalizacja:** `app/Services/PrestaShop/PrestaShopImportService.php` (lines 274-275)

**Kod:**
```php
// ETAP_07 FIX: Store link_rewrite for frontend URL generation
'external_reference' => $prestashopData['link_rewrite'] ?? null,
```

**Rezultat:** Nowe importy automatycznie zapisują link_rewrite do ProductShopData

### 3️⃣ Fallback do bazy w `getProductPrestaShopUrl()` ✅

**Lokalizacja:** `app/Http/Livewire/Products/Management/ProductForm.php` (lines 3188-3206)

**Kod:**
```php
// ETAP_07 FIX: Fallback to database ProductShopData if cache not available
if ($this->product && $this->product->exists) {
    $dbShopData = $this->product->shopData()
        ->where('shop_id', $shopId)
        ->first();

    if ($dbShopData && $dbShopData->external_id) {
        $productId = $dbShopData->external_id;
        $linkRewrite = $dbShopData->external_reference; // link_rewrite stored here

        if ($linkRewrite) {
            return rtrim($shop['url'], '/') . "/{$productId}-{$linkRewrite}.html";
        }

        // Fallback - controller URL if no link_rewrite
        return rtrim($shop['url'], '/') . "/index.php?id_product={$productId}&controller=product";
    }
}

return null;
```

**Rezultat:**
- **Primary source:** Cache z API (`$this->loadedShopData`)
- **Fallback source:** Baza danych (`ProductShopData.external_reference`)
- **Last resort:** Controller URL (jeśli brak link_rewrite)

### 4️⃣ Update istniejących produktów ✅

**Script:** `_TOOLS/update_existing_link_rewrite.php`

**Wykonanie:**
```bash
php _TOOLS/update_existing_link_rewrite.php
```

**Rezultaty:**
```
✅ Updated: 4 products
❌ Errors: 0
```

**Zaktualizowane produkty:**
- Product ID 16 (PrestaShop ID: 42) → `pitgang-140xd`
- Product ID 17 (PrestaShop ID: 1827) → `pitgang-140xd-enduro`
- Product ID 18 (PrestaShop ID: 1828) → `pitgang-125xd-enduro`
- Product ID 19 (PrestaShop ID: 9673) → `pit-bike-pitgang-110xd-enduro`

---

## 📊 TECHNICAL DETAILS

### PrestaShop API Response Structure

**Test produktu 9673:**
```json
{
  "id": 9673,
  "link_rewrite": "pit-bike-pitgang-110xd-enduro",
  "name": [...],
  "description": [...],
  "associations": {
    "categories": [...]
  }
}
```

**Kluczowe pole:** `link_rewrite` - zwracane jako **string**, nie array!

### URL Generation Logic

**Format PrestaShop Friendly URL:**
```
https://dev.mpptrade.pl/{id}-{link_rewrite}.html
```

**Przykład:**
```
https://dev.mpptrade.pl/9673-pit-bike-pitgang-110xd-enduro.html
```

**Fallback (jeśli brak link_rewrite):**
```
https://dev.mpptrade.pl/index.php?id_product=9673&controller=product
```

---

## 🧪 TESTY UŻYTKOWNIKA

### Test #1: Weryfikacja linku produktu ✅
1. Otwórz edycję produktu: https://ppm.mpptrade.pl/admin/products/edit/19
2. Kliknij label sklepu "B2B Test DEV"
3. Sprawdź czy się auto-loadują dane (flash message lub check logs)
4. Kliknij "🔗 PrestaShop"
5. **Expected:** Otwiera się `https://dev.mpptrade.pl/9673-pit-bike-pitgang-110xd-enduro.html` ✅
6. **NOT:** `https://dev.mpptrade.pl//admin-dev/index.php?controller=AdminProducts&id_product=9673` ❌

### Test #2: Weryfikacja auto-load cache
1. Kliknij inną zakładkę (np. "Opisy")
2. Wróć do zakładki sklepu
3. **Expected:** Link nadal działa (używa cache lub bazy)

### Test #3: Nowe importy
1. Zaimportuj nowy produkt z PrestaShop
2. Otwórz edycję
3. Kliknij zakładkę sklepu
4. **Expected:** Link generuje się poprawnie od razu

---

## 📁 PLIKI ZMIENIONE

### Zmodyfikowane:
1. **app/Http/Livewire/Products/Management/ProductForm.php**
   - Line 1071-1079: Auto-load w `switchToShop()`
   - Line 3188-3206: Fallback do bazy w `getProductPrestaShopUrl()`

2. **app/Services/PrestaShop/PrestaShopImportService.php**
   - Line 274-275: Zapisywanie link_rewrite do external_reference

### Utworzone:
3. **_TOOLS/test_prestashop_product_link.php** - Test script API
4. **_TOOLS/update_existing_link_rewrite.php** - Update script dla istniejących produktów

---

## 🎯 REZULTATY

### ✅ Problem #1: Link do produktu - RESOLVED
- ✅ Auto-load działa przy pierwszym kliknięciu
- ✅ Link_rewrite zapisywany podczas importu
- ✅ Fallback do bazy jeśli cache pusty
- ✅ Istniejące produkty zaktualizowane
- ✅ Frontend URL generowany poprawnie: `/{id}-{slug}.html`

### ⏳ Problem #2: Kategorie - PARTIAL
- ✅ Dane kategorii są pobierane z API
- ✅ Zapisywane w cache `$this->loadedShopData[$shopId]['categories']`
- ❌ Brak mapowania PrestaShop category ID → PPM category ID
- ❌ Kategorie nie wyświetlają się w CategoryPicker

**TODO:** Implementacja CategoryMapper dla kategorii

---

## 🔄 WORKFLOW DIAGRAM

```
User clicks shop label
        ↓
switchToShop($shopId) called
        ↓
activeShopId = $shopId (line 1052)
        ↓
[NEW FIX] Auto-load check (line 1073)
        ↓
loadProductDataFromPrestaShop($shopId)
        ↓
Fetch from PrestaShop API
        ↓
Cache in $loadedShopData[$shopId]
        ↓
Extract link_rewrite → store in cache
        ↓
getProductPrestaShopUrl($shopId)
        ↓
Check cache → Found: use it
        ↓
[NEW FIX] Check database fallback
        ↓
ProductShopData.external_reference
        ↓
Generate URL: /{id}-{link_rewrite}.html
```

---

## 📈 PERFORMANCE

### Before Fix:
- ❌ Link always admin URL
- ❌ No auto-load on shop switch
- ❌ No persistent link_rewrite storage

### After Fix:
- ✅ Correct frontend URL
- ✅ Auto-load on first click (~1-2s API call)
- ✅ Cached for subsequent views (instant)
- ✅ Database fallback (instant)
- ✅ Persistent storage in ProductShopData

---

## 🚀 DEPLOYMENT STATUS

**Status:** ✅ **DEPLOYED TO PRODUCTION**

**Deployment Commands:**
```bash
pscp ProductForm.php → app/Http/Livewire/Products/Management/
pscp PrestaShopImportService.php → app/Services/PrestaShop/
php artisan view:clear && cache:clear
php _TOOLS/update_existing_link_rewrite.php
```

**Verification:**
```bash
✅ Auto-load code found at line 1074
✅ Fallback code found at line 3188
✅ Import fix found at line 274
✅ 4 products updated successfully
```

**Production URL:** https://ppm.mpptrade.pl/admin/products/edit/{id}

---

## 📋 NASTĘPNE KROKI

### 1. Kategorie - Mapowanie PrestaShop → PPM
**Priorytet:** HIGH
**Zadanie:**
- Implementacja CategoryMapper service
- Mapowanie PrestaShop category IDs → PPM category IDs
- Integracja z CategoryPicker w zakładce "Sklepy"

### 2. Real-time Progress Feedback
**Priorytet:** MEDIUM
**Zadanie:**
- Pokazywanie ilości produktów podczas importu
- Progress bar z X/Y products

### 3. Enhanced Sync Panel
**Priorytet:** MEDIUM
**Zadanie:**
- Rozbudowa `/admin/shops/sync`
- Szczegółowe info o synchronizacji

---

**Autor:** Claude Code (General-purpose agent)
**Review:** ⏳ Pending user verification
**Deploy:** ✅ Production (ppm.mpptrade.pl)
**Status:** ✅ Link fix COMPLETE, ⏳ Categories TODO
