# SKU Architecture Guide - PPM-CC-Laravel

**Dokument:** Szczegółowy przewodnik architektury SKU jako głównego klucza produktu
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Architektura Aplikacji

---

## 🔑 KRYTYCZNA ZASADA ARCHITEKTURY: SKU jako Główny Klucz Produktu

### ⚠️ FUNDAMENTALNA REGUŁA - ZAWSZE PRZESTRZEGAJ:

**SKU (Stock Keeping Unit) jest UNIWERSALNYM IDENTYFIKATOREM produktu w całej aplikacji PPM-CC-Laravel.**

---

## 🤔 DLACZEGO SKU, nie external ID?

Produkt w PPM może mieć:
- ❌ **Różne ID w różnych sklepach PrestaShop** (`prestashop_product_id`: 4017, 5234, 1092...)
- ❌ **Różne ID w różnych systemach ERP** (Baselinker ID, Subiekt GT ID, Dynamics ID...)
- ❌ **Brak external ID** (produkt dodany ręcznie przez użytkownika)
- ✅ **ZAWSZE TEN SAM SKU** - jedyny wspólny wyznacznik tego samego produktu fizycznego!

---

## 📋 OBOWIĄZKOWA ZASADA: SKU FIRST

### ✅ ZAWSZE używaj SKU jako PRIMARY lookup method dla:

- Wyszukiwania produktu w bazie PPM
- Porównywania produktów między sklepami
- Conflict detection podczas importu/re-importu
- Synchronizacji danych między systemami (PrestaShop ↔ PPM ↔ ERP)
- Mapowania produktów z external systems

### ❌ External IDs są WTÓRNE (secondary/fallback lookup):

- `ProductShopData.prestashop_product_id` - tylko dla konkretnego sklepu
- `ERP mappings` - tylko dla konkretnego systemu ERP
- **Użyj ich TYLKO jeśli produkt nie ma SKU** (ekstremalnie rzadkie!)

---

## ✅ PRZYKŁAD PRAWIDŁOWEGO WORKFLOW

```php
// ✅ CORRECT: PRIMARY - Search by SKU from PrestaShop reference
$sku = $prestashopProduct['reference'] ?? null; // SKU from PrestaShop
if ($sku) {
    $product = Product::where('sku', $sku)->first();

    if ($product) {
        // ✅ Product EXISTS in PPM
        // Może być:
        // - Dodany ręcznie (bez ProductShopData)
        // - Z innego sklepu (ProductShopData.shop_id !== $currentShopId)
        // - Z tego samego sklepu (RE-IMPORT)
        // → CONFLICT DETECTION scenario
    } else {
        // ✅ Product NOT in PPM
        // → FIRST IMPORT scenario
    }
}

// ❌ FALLBACK: Only if product has NO SKU (extremely rare)
if (!$product) {
    $productShopData = ProductShopData::where('prestashop_product_id', $prestashopProductId)->first();
    if ($productShopData) {
        $product = Product::find($productShopData->product_id);
    }
}
```

---

## ❌ BŁĘDNY PATTERN (DO UNIKANIA)

```php
// ❌ WRONG: Search by shop-specific ID FIRST
$productShopData = ProductShopData::where('shop_id', $shopId)
    ->where('prestashop_product_id', $prestashopProductId)
    ->first();

// To POMIJA:
// - Produkty ręcznie dodane (brak ProductShopData)
// - Produkty z innych sklepów (inny shop_id)
// - Cross-shop scenarios
// → FALSE "first import" when it's actually RE-IMPORT!
```

### 🚨 PROBLEM Z TYM PODEJŚCIEM:

1. **Pominięcie produktów ręcznych** - produkty dodane przez użytkownika bez importu nie mają ProductShopData
2. **Ignorowanie cross-shop** - produkt może już istnieć w PPM z innego sklepu
3. **False positive "first import"** - system myśli że to nowy produkt, gdy to re-import
4. **Duplikacja produktów** - ten sam produkt fizyczny może mieć wiele wpisów w bazie

---

## 🗄️ DATABASE SCHEMA

```sql
-- products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(255) UNIQUE NOT NULL,  -- ✅ BUSINESS PRIMARY KEY
    name VARCHAR(255),
    description TEXT,
    -- ... inne kolumny
);

-- product_shop_data table (pivot)
CREATE TABLE product_shop_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,  -- FK → products.id
    shop_id INT NOT NULL,     -- FK → prestashop_shops.id
    prestashop_product_id INT,  -- ❌ SECONDARY mapping per shop
    -- ... inne kolumny
    UNIQUE KEY unique_product_per_shop (product_id, shop_id)
);

-- ERP mappings (similar pattern)
CREATE TABLE product_erp_mappings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,  -- FK → products.id
    erp_system VARCHAR(50),   -- 'baselinker', 'subiekt_gt', 'dynamics'
    erp_product_id VARCHAR(255),  -- ❌ SECONDARY mapping per ERP
    -- ... inne kolumny
);
```

---

## 📊 KONSEKWENCJE DLA KODU

### ✅ IMPLEMENTACJE WYMAGAJĄCE SKU FIRST:

#### 1. **Conflict Detection**
```php
// CORRECT
$existingProduct = Product::where('sku', $importedSku)->first();
if ($existingProduct) {
    // Handle conflict: update vs skip vs user decision
}
```

#### 2. **Import/Export**
```php
// CORRECT - Import
foreach ($importedProducts as $row) {
    $sku = $row['SKU'] ?? $row['reference'];
    $product = Product::firstOrCreate(['sku' => $sku], [...]);
}

// CORRECT - Export
$productsToExport = Product::whereIn('sku', $selectedSkus)->get();
```

#### 3. **Synchronizacja Multi-Store**
```php
// CORRECT
$product = Product::where('sku', $prestashopReference)->first();
if ($product) {
    // Sync to ProductShopData for specific shop
    $product->syncToShop($shopId, $prestashopProductId);
}
```

#### 4. **ERP Integration**
```php
// CORRECT
$product = Product::where('sku', $baselinkerSku)->first();
if ($product) {
    // Map to ERP system
    $product->erpMappings()->updateOrCreate([
        'erp_system' => 'baselinker',
    ], [
        'erp_product_id' => $baselinkerProductId,
    ]);
}
```

#### 5. **Product Lookup (Search/API)**
```php
// CORRECT - API endpoint
Route::get('/api/products/{sku}', function ($sku) {
    return Product::where('sku', $sku)->firstOrFail();
});

// CORRECT - Search
$results = Product::where('sku', 'LIKE', "%{$query}%")
    ->orWhere('name', 'LIKE', "%{$query}%")
    ->get();
```

---

## 🎯 SCENARIUSZE UŻYCIA

### Scenariusz 1: Import z PrestaShop (pierwszy raz)
```php
$sku = $prestashopProduct['reference'];
$product = Product::where('sku', $sku)->first();

if (!$product) {
    // ✅ FIRST IMPORT - create new product
    $product = Product::create([
        'sku' => $sku,
        'name' => $prestashopProduct['name'],
        // ...
    ]);
}

// Link to shop
$product->shopData()->create([
    'shop_id' => $shopId,
    'prestashop_product_id' => $prestashopProduct['id'],
]);
```

### Scenariusz 2: Re-import (produkt już istnieje)
```php
$sku = $prestashopProduct['reference'];
$product = Product::where('sku', $sku)->first();

if ($product) {
    // ✅ RE-IMPORT - update existing product
    $product->update([
        'name' => $prestashopProduct['name'],
        // ...
    ]);

    // Check if already linked to this shop
    $shopData = $product->shopData()->where('shop_id', $shopId)->first();
    if (!$shopData) {
        // Link to new shop
        $product->shopData()->create([
            'shop_id' => $shopId,
            'prestashop_product_id' => $prestashopProduct['id'],
        ]);
    }
}
```

### Scenariusz 3: Produkt z wielu sklepów
```php
$sku = 'PART-12345';
$product = Product::where('sku', $sku)->first();

// Ten sam produkt może mieć różne PrestaShop IDs w różnych sklepach:
// Shop A: prestashop_product_id = 4017
// Shop B: prestashop_product_id = 5234
// Shop C: prestashop_product_id = 1092

// ✅ SKU pozostaje CONSTANT - to jest klucz!
```

### Scenariusz 4: Produkt ręczny (bez external ID)
```php
// User tworzy produkt ręcznie w PPM
$product = Product::create([
    'sku' => 'CUSTOM-001',
    'name' => 'Custom Product',
    // ... NO prestashop_product_id, NO erp_id
]);

// Później można eksportować do PrestaShop
$prestashopId = $prestashopApi->createProduct($product);
$product->shopData()->create([
    'shop_id' => $shopId,
    'prestashop_product_id' => $prestashopId,
]);
```

---

## 🔍 CHECKLIST IMPLEMENTACJI

Przed implementacją funkcjonalności związanej z produktami, sprawdź:

- [ ] Czy używam SKU jako PRIMARY lookup?
- [ ] Czy external IDs (PrestaShop/ERP) są SECONDARY/FALLBACK?
- [ ] Czy obsługuję scenariusz produktu ręcznego (bez external ID)?
- [ ] Czy obsługuję cross-shop scenarios?
- [ ] Czy conflict detection opiera się na SKU?
- [ ] Czy synchronizacja multi-store używa SKU?
- [ ] Czy import/export używa SKU jako klucza?

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Główne zasady projektu
- **_ISSUES_FIXES/PHP_TYPE_JUGGLING_ISSUE.md** - Obsługa mixed types w SKU
- **Plan_Projektu/ETAP_06_IMPORT_EXPORT.md** - Import workflow
- **Plan_Projektu/ETAP_07_PRESTASHOP_API.md** - PrestaShop sync

---

**PAMIĘTAJ:** SKU to fundament architektury PPM-CC-Laravel. Bez przestrzegania tej zasady system nie działa poprawnie!
