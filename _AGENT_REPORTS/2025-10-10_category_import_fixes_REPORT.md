# RAPORT NAPRAW: Category Deletion & Product Re-Import Fixes

**Data**: 2025-10-10
**Agent**: Main Assistant (Claude Code)
**Zadanie**: Naprawa usuwania kategorii + re-import produktów z kategoriami

---

## ✅ WYKONANE NAPRAWY

### 1. CRITICAL FIX: Category Deletion - Orphaned Shop Mappings

**Problem**:
- Kategorie były usuwane z tabeli `categories` ✅
- Ale `shop_mappings` pozostawały w bazie ❌
- Modal importu pokazywał "Wszystkie kategorie już istnieją!" mimo że kategorie nie istniały
- CategoryPreviewModal sprawdzał `shop_mappings` i znajdował 23 orphaned records

**Root Cause**:
- `BulkDeleteCategoriesJob` usuwał:
  - Product associations z `product_categories` ✅
  - Categories z `categories` ✅
  - **NIE usuwał** shop_mappings ❌

**Rozwiązanie**:
```php
// app/Jobs/Categories/BulkDeleteCategoriesJob.php

protected function deleteShopMappings(array $categoryIds): int
{
    // Convert category IDs to strings (ppm_value is string column)
    $ppmValues = array_map('strval', $categoryIds);

    // Delete category mappings from shop_mappings table
    $deletedCount = DB::table('shop_mappings')
        ->where('mapping_type', 'category')
        ->whereIn('ppm_value', $ppmValues)
        ->delete();

    return $deletedCount;
}
```

**Wynik**:
- ✅ Usunięto 23 orphaned mappings z produkcji
- ✅ BulkDeleteCategoriesJob teraz usuwa shop_mappings podczas delete
- ✅ Modal importu działa poprawnie (nie pokazuje "już istnieją" dla nieistniejących kategorii)

**Pliki**:
- `app/Jobs/Categories/BulkDeleteCategoriesJob.php` - Dodano metodę `deleteShopMappings()`
- `_TOOLS/check_shop_mappings.php` - Narzędzie diagnostyczne
- `_TOOLS/cleanup_orphaned_mappings.php` - Jednorazowy cleanup

---

### 2. SQL Error Fix: Column 'ppm_id' vs 'ppm_value'

**Problem**:
```sql
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'ppm_id' in 'WHERE'
```

**Root Cause**:
- Tabela `shop_mappings` używa kolumny `ppm_value` (string)
- Błędnie używałem nieistniejącej kolumny `ppm_id`

**Rozwiązanie**:
- Poprawiono SQL query: `whereIn('ppm_value', $ppmValues)`
- Dodano konwersję IDs na stringi: `array_map('strval', $categoryIds)`

**Wynik**:
- ✅ SQL error naprawiony
- ✅ Delete kategorii działa poprawnie

---

### 3. CRITICAL FIX: Re-Import Products - Categories Not Updated

**Problem**:
- Re-import tych samych SKU **nie aktualizował** kategorii w:
  - "Dane domyślne" (główne kategorie produktu)
  - Zakładka sklepu (shopData)
- Problem występował TYLKO dla existing products
- Nowe produkty importowały się z kategoriami poprawnie ✅

**Root Cause**:
```php
// app/Jobs/PrestaShop/BulkImportProducts.php (PRZED)

// Check if product already exists
$existingProduct = Product::where('sku', $sku)->first();

if ($existingProduct) {
    Log::info('Product already exists - skipped');
    return 'skipped_duplicate';  // ❌ SKIP zamiast UPDATE!
}

// Ten kod nigdy nie był wywoływany dla existing products:
$product = $importService->importProductFromPrestaShop($prestashopProductId, $this->shop);
```

**Dlaczego to był problem**:
- `PrestaShopImportService.importProductFromPrestaShop()` ma pełną logikę:
  - UPDATE Product record ✅
  - Sync ProductPrice ✅
  - Sync Stock ✅
  - **syncProductCategories()** - REPLACE wszystkich kategorii ✅
  - UPDATE ProductSyncStatus ✅
  - UPDATE ProductShopData ✅
- Ale nigdy nie była wywoływana dla existing products!

**Rozwiązanie**:
```php
// app/Jobs/PrestaShop/BulkImportProducts.php (PO)

// Check if product already exists (dla logów)
$existingProduct = Product::where('sku', $sku)->first();
$isUpdate = (bool) $existingProduct;

// ZAWSZE wywołaj importService (który zrobi CREATE lub UPDATE)
$product = $importService->importProductFromPrestaShop(
    $prestashopProductId,
    $this->shop
);

return $isUpdate ? 'updated' : 'imported';
```

**Wynik**:
- ✅ Re-import existing SKU działa jako UPDATE
- ✅ Kategorie są sync'owane (REPLACE) przy każdym import
- ✅ Progress tracking: imported/updated/skipped
- ✅ Logi pokazują "Product updated successfully"

**Pliki**:
- `app/Jobs/PrestaShop/BulkImportProducts.php` - Usunięto skip logic, dodano UPDATE tracking

---

## 📊 STATYSTYKI NAPRAW

### Category Deletion Fix:
- **Orphaned mappings usunięte**: 23
- **Workflow steps dodane**: 1 (deleteShopMappings)
- **Deployment**: ✅ Produkcja

### Re-Import Fix:
- **Skip logic usunięty**: ✅
- **UPDATE tracking dodany**: ✅ (imported/updated/skipped)
- **Deployment**: ✅ Produkcja

---

## 🧪 WERYFIKACJA

### Test 1: Usuwanie kategorii
```
✅ Kategorie usuwane z tabeli categories
✅ Product associations usuwane z product_categories
✅ Shop mappings usuwane z shop_mappings
✅ Progress bar pokazuje postęp
✅ Auto-refresh działa (bez F5)
```

**Logi**:
```
BulkDeleteCategoriesJob: Shop mappings deleted | deleted_count: 2
BulkDeleteCategoriesJob COMPLETED | deleted: 5, mappings_deleted: 2
```

### Test 2: Re-import produktów
```
✅ Existing products są UPDATE'owane (nie skipowane)
✅ Kategorie sync'owane w "Dane domyślne"
✅ Kategorie sync'owane w zakładce sklepu
✅ Progress pokazuje: X imported, Y updated, Z skipped
```

**Logi**:
```
Product updated successfully | operation: update
Product categories synced | category_count: 3
BulkImportProducts job completed | imported: 0, updated: 15, skipped: 0
```

---

## ⚠️ NASTĘPNE KROKI

### Issue Znalezione Podczas Sesji:
1. **UI Issue**: Kategorie w ProductForm nie pokazują hierarchii (brak wcięć, struktury drzewka)
2. **Cleanup**: Nieużywana sekcja "Kategorie PrestaShop" w zakładce sklepu do usunięcia

**Status**: Zaplanowane do naprawy w następnej iteracji

---

## 📁 PLIKI ZMODYFIKOWANE

### Backend:
- `app/Jobs/Categories/BulkDeleteCategoriesJob.php`
  - Dodano: `deleteShopMappings()` method
  - Dodano: Wywołanie `deleteShopMappings()` w transaction
  - Poprawiono: SQL query (ppm_value zamiast ppm_id)

- `app/Jobs/PrestaShop/BulkImportProducts.php`
  - Usunięto: Skip logic dla existing products
  - Dodano: UPDATE tracking (`$updated` counter)
  - Dodano: Logi z operation type (create/update)
  - Poprawiono: Success rate calculation

### Tools:
- `_TOOLS/check_shop_mappings.php` - Narzędzie diagnostyczne (utworzone)
- `_TOOLS/cleanup_orphaned_mappings.php` - Cleanup script (utworzone)
- `_TOOLS/check_categories.php` - Category verification (utworzone)

---

## 🎯 REZULTAT

**Wszystkie naprawy wdrożone na produkcję i zweryfikowane przez użytkownika.**

**User Confirmation**: "ok import działa teraz poprawnie"

✅ **SUKCES** - Critical bugs naprawione, workflow działa zgodnie z założeniami.
