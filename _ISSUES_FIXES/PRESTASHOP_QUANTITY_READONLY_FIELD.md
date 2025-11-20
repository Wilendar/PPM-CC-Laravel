# PRESTASHOP API - QUANTITY READONLY FIELD

**Data zgłoszenia:** 2025-11-05
**Status:** ✅ ROZWIĄZANY
**Priorytet:** 🔴 KRYTYCZNY
**Kategoria:** PrestaShop API Integration
**Czas debugowania:** ~1.5h

---

## 📋 PROBLEM

### Objawy

```
Błąd: Unexpected error during PrestaShop API request:
PrestaShop API error (400): parameter "quantity" not writable.
Please remove this attribute of this XML

Liczba prób: 52 / 3
```

Synchronizacja produktów PPM → PrestaShop kończy się błędem 400 podczas tworzenia/aktualizacji produktów.

### Kontekst

- **ETAP_07 FAZA 3B** - Export/Sync PPM → PrestaShop
- Błąd występuje przy każdej próbie synchronizacji produktu
- PrestaShop API zwraca status 400 Bad Request
- Problem dotyczy wszystkich produktów (52 próby nieudane)

---

## 🔍 ROOT CAUSE ANALYSIS

### Przyczyna

**ProductTransformer::transformForPrestaShop()** wysyłał pole `quantity` w XML dla produktu:

```php
// BŁĄD - ProductTransformer.php:112 (stara wersja)
'quantity' => $this->warehouseMapper->calculateStockForShop($product, $shop),
```

### Dlaczego to błąd?

Zgodnie z **oficjalną dokumentacją PrestaShop API** (Context7):

1. **Products Resource** - `quantity` jest **READONLY field**
   - NIE MA w Parameters dla POST/PUT
   - Występuje TYLKO w Response (GET)

2. **Stock Management** - quantity musi być zarządzane przez osobny zasób:
   - Endpoint: `/api/stock_availables`
   - Metoda: `PUT /api/stock_availables/{id_stock_available}`

3. **XML Structure** dla products NIE MOŻE zawierać `quantity`:
```xml
<!-- ❌ BŁĄD -->
<prestashop>
  <product>
    <reference>SKU-123</reference>
    <quantity>10</quantity> <!-- BŁĄD: readonly field! -->
  </product>
</prestashop>

<!-- ✅ POPRAWNIE -->
<prestashop>
  <product>
    <reference>SKU-123</reference>
    <!-- quantity NIE MOŻE być tutaj -->
  </product>
</prestashop>
```

### Inne Readonly Fields w Products API

**Z dokumentacji PrestaShop 8.x:**

- ❌ `quantity` - zarządzane przez stock_availables
- ❌ `manufacturer_name` - ✅ już naprawione (używamy id_manufacturer)
- ❌ `cache_default_attribute` - auto-generated
- ❌ `id_default_image` - auto-generated
- ❌ `id_default_combination` - readonly (używane tylko w specjalnych przypadkach)
- ❌ `position_in_category` - zarządzane przez associations

---

## ✅ ROZWIĄZANIE

### Implementacja

**1. Usunięto `quantity` z ProductTransformer::transformForPrestaShop()**

```php
// app/Services/PrestaShop/ProductTransformer.php:111-116

// BUGFIX 2025-11-05: 'quantity' is READONLY in PrestaShop products API
// Stock must be managed through separate /api/stock_availables endpoint
// DO NOT send 'quantity' in product POST/PUT - causes error:
// "parameter 'quantity' not writable. Please remove this attribute of this XML"
// To update stock: Use updateStock() method AFTER product creation/update
// 'quantity' => ... // REMOVED - causes PrestaShop API error!
```

**2. Dodano komentarz w buildCombinationXml() dla jasności**

```php
// app/Services/PrestaShop/PrestaShop8Client.php:893-895

// NOTE: 'quantity' IS WRITABLE for combinations (unlike products where it's readonly)
// Combinations have their own stock, separate from base product
$combination->addChild('quantity', $data['quantity'] ?? 0);
```

### Gdzie quantity JEST writable (pozostawione bez zmian)

✅ **Combinations Resource** - quantity JEST writable:
```php
// PrestaShop8Client::buildCombinationXml():892
$combination->addChild('quantity', $data['quantity'] ?? 0); // OK!
```

✅ **Stock Availables Resource** - quantity JEST writable:
```php
// PrestaShop8Client::updateStock():175
'stock_available' => [
    'id' => $stockId,
    'quantity' => $quantity  // OK!
]
```

---

## 📊 IMPACT

### Przed Fix

- ❌ 0% produktów zsynchronizowanych
- ❌ 52 nieudane próby
- ❌ Status: error w product_shop_data
- ❌ Brak produktów w PrestaShop

### Po Fix

- ✅ Produkty synchronizują się poprawnie
- ✅ Brak błędów 400 Bad Request
- ✅ Status: synced w product_shop_data
- ✅ Produkty widoczne w PrestaShop

### Files Modified

```
app/Services/PrestaShop/ProductTransformer.php (linia 111-116)
app/Services/PrestaShop/PrestaShop8Client.php (linia 893-895)
_ISSUES_FIXES/PRESTASHOP_QUANTITY_READONLY_FIELD.md (nowy plik)
```

---

## 🔧 STOCK MANAGEMENT WORKFLOW

### Current Implementation (Po Fix)

**Krok 1: Create/Update Product (BEZ quantity)**
```php
$productData = $transformer->transformForPrestaShop($product, $client);
// $productData NIE ZAWIERA 'quantity'

$response = $client->createProduct($productData);
$productId = $response['product']['id'];
```

**Krok 2: Update Stock (OSOBNO przez stock_availables)**
```php
// Future implementation (ETAP_07 FAZA 3B.4 lub FAZA 4.3)
$stockId = $client->getStock($productId); // GET stock_available ID
$client->updateStock($stockId, $quantity); // PUT stock_availables/{id}
```

### Future Enhancement (ETAP_07 FAZA 4.3)

Implementacja automatycznej aktualizacji stock po product sync:

```php
// ProductSyncStrategy::syncToPrestaShop() - FUTURE
if ($isUpdate) {
    $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
    $operation = 'update';

    // NEW: Update stock after product update
    $this->updateProductStock($client, $syncStatus->prestashop_product_id, $product, $shop);
}
```

---

## 🚨 PREVENTION CHECKLIST

**Przed wysłaniem danych do PrestaShop API:**

- [ ] Sprawdź oficjalną dokumentację dla danego resource
- [ ] Zidentyfikuj readonly fields (brak w Parameters, tylko w Response)
- [ ] Usuń readonly fields z XML payload
- [ ] Użyj osobnych endpoints dla stock/price management
- [ ] Dodaj komentarze BUGFIX z datą i wyjaśnieniem

**Readonly fields do unikania w products:**
- [ ] quantity (use stock_availables)
- [ ] manufacturer_name (use id_manufacturer)
- [ ] cache_default_attribute
- [ ] id_default_image
- [ ] position_in_category

---

## 📚 REFERENCES

**Dokumentacja:**
- [PrestaShop 8 Products API](https://devdocs.prestashop-project.org/8/webservice/resources/products) - Context7
- [PrestaShop 8 Stock Availables](https://devdocs.prestashop-project.org/8/webservice/resources/stocks) - Context7
- [PrestaShop 8 Combinations](https://devdocs.prestashop-project.org/8/webservice/resources/combinations) - Context7

**Related Files:**
- `app/Services/PrestaShop/ProductTransformer.php` - Product transformation logic
- `app/Services/PrestaShop/PrestaShop8Client.php` - PrestaShop API client
- `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` - Sync orchestration

**Related Issues:**
- None (first occurrence)

---

## ✅ VERIFICATION

**Test Case 1: Product Creation**
```php
// Given: Product with SKU and all required fields
$product = Product::factory()->create(['sku' => 'TEST-001']);

// When: Sync to PrestaShop
$result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

// Then: No errors, product created
$this->assertTrue($result['success']);
$this->assertNotNull($result['external_id']);
```

**Test Case 2: Product Update**
```php
// Given: Product already synced to PrestaShop
$product = Product::factory()->create();
$syncStatus = ProductShopData::factory()->create([
    'product_id' => $product->id,
    'prestashop_product_id' => 123,
]);

// When: Update product and re-sync
$product->update(['name' => 'Updated Name']);
$result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

// Then: No errors, product updated
$this->assertTrue($result['success']);
$this->assertEquals('update', $result['operation']);
```

---

**Author:** Claude Code AI (PPM-CC-Laravel)
**Reviewed:** Kamil Wiliński
**Status:** ✅ Verified Working
**Next Steps:** Implement automatic stock sync (ETAP_07 FAZA 4.3)
