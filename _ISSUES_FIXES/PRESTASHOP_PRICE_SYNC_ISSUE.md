# PrestaShop Price Sync Issue

**Date:** 2025-11-14
**Severity:** 🔥 CRITICAL
**Status:** 🛠️ IN PROGRESS

---

## 📋 SYMPTOMY

**Zgłoszone przez użytkownika:**
1. Wyeksportowana tylko cena Detaliczna netto, która została wpisana jako BRUTTO
2. Ceny specyficzne się nie zaktualizowały wcale
3. PrestaShop zapisuje ceny specyficzne WYŁĄCZNIE jako netto

---

## 🔍 ROOT CAUSE ANALYSIS

### Problem #1: Readonly Field `manufacturer_name`

**Location:** `app/Services/PrestaShop/ProductTransformer.php:118`

```php
// ❌ BŁĄD
'manufacturer_name' => $product->manufacturer ?? '',
```

**Issue:** `manufacturer_name` jest **readonly field** w PrestaShop API

**PrestaShop Error:** `parameter "manufacturer_name" not writable. Please remove this attribute of this XML`

**Fix:**
```php
// ✅ POPRAWNIE - użyj id_manufacturer zamiast manufacturer_name
'id_manufacturer' => $this->mapManufacturer($product->manufacturer, $shop),
```

---

### Problem #2: Brak Synchronizacji Specific Prices

**Location:** `app/Services/PrestaShop/ProductTransformer.php:92`

**Current Implementation:**
```php
// TYLKO jedna cena (defaultPriceGroup)
'price' => $this->calculatePrice($product, $shop),
```

**Issue:** ProductTransformer wysyła **TYLKO** jedną cenę bazową, nie synchronizuje:
- Pozostałe grupy cenowe PPM (Dealer Standard, Dealer Premium, Warsztat, etc.)
- PrestaShop specific_prices (discounts, group prices)

**PrestaShop Architecture:**
- `products.price` = cena bazowa (zwykle najwyższa)
- `ps_specific_price` records = ceny dla grup klientów, przeceny, promocje

**PPM Architecture:**
- 8 grup cenowych: Detaliczna, Dealer Standard, Dealer Premium, Warsztat Standard, Warsztat Premium, Szkółka, Komis, Drop
- `product_prices` table: `product_id + price_group_id`
- Mapowanie grup: `shop_mappings` (TYPE_PRICE_GROUP)

**Problem:** Brak logiki do eksportu PPM `product_prices` → PrestaShop `specific_prices`

---

### Problem #3: Cena Brutto vs Netto (FALSE ALARM)

**User Report:** "Cena netto została wpisana jako brutto"

**Actual Behavior:** ProductTransformer poprawnie wysyła cenę NETTO:
```php
return (float) $price->price_net; // Line 214
```

**PrestaShop Requirement:** `<price>` = cena NETTO (bez VAT)

**Possible Cause:** Błąd w `id_tax_rules_group` mapping lub PrestaShop tax configuration

**Status:** Wymaga weryfikacji z użytkownikiem - sprawdzić tax_rate mapping

---

## 🛠️ ROZWIĄZANIE

### Fix #1: Remove `manufacturer_name` Readonly Field

**File:** `app/Services/PrestaShop/ProductTransformer.php`

**Change:**
```php
// PRZED (Line 118)
'manufacturer_name' => $product->manufacturer ?? '',

// PO
'id_manufacturer' => $this->mapManufacturer($product->manufacturer, $shop),
```

**New Method:**
```php
private function mapManufacturer(?string $manufacturerName, PrestaShopShop $shop): ?int
{
    if (!$manufacturerName) {
        return null;
    }

    // TODO: Implement manufacturer mapper
    // - Check if manufacturer exists in PrestaShop
    // - Create if missing
    // - Return id_manufacturer
    // Similar pattern as CategoryMapper

    Log::warning('Manufacturer mapping not implemented yet', [
        'manufacturer' => $manufacturerName,
        'shop_id' => $shop->id,
    ]);

    return null;
}
```

---

### Fix #2: Implement Specific Prices Sync

**Architecture:**

```
PPM product_prices (8 price groups)
         ↓
   PriceGroupMapper
         ↓
PrestaShop specific_prices API
         ↓
ps_specific_price table
```

**Implementation Plan:**

1. **Create `PrestaShopPriceExporter` Service**
   - Mirror of `PrestaShopPriceImporter`
   - Export PPM `product_prices` → PrestaShop `specific_prices`

2. **Sync Logic:**
   ```php
   foreach ($product->prices as $productPrice) {
       $prestashopGroupId = $this->priceGroupMapper->mapToPrestaShop(
           $productPrice->price_group_id,
           $shop
       );

       if (!$prestashopGroupId) {
           continue; // Skip unmapped groups
       }

       // CREATE or UPDATE specific_price
       $this->syncSpecificPrice($product, $productPrice, $prestashopGroupId, $shop);
   }
   ```

3. **PrestaShop specific_price XML Structure:**
   ```xml
   <prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
       <specific_price>
           <id_product><![CDATA[123]]></id_product>
           <id_shop><![CDATA[1]]></id_shop>
           <id_currency><![CDATA[0]]></id_currency>  <!-- 0 = all -->
           <id_country><![CDATA[0]]></id_country>    <!-- 0 = all -->
           <id_group><![CDATA[5]]></id_group>        <!-- Customer group ID -->
           <id_customer><![CDATA[0]]></id_customer>  <!-- 0 = all -->
           <id_product_attribute><![CDATA[0]]></id_product_attribute> <!-- 0 = base -->
           <price><![CDATA[99.99]]></price>          <!-- Override price (NETTO) -->
           <from_quantity><![CDATA[1]]></from_quantity>
           <reduction><![CDATA[0.000000]]></reduction>
           <reduction_type><![CDATA[amount]]></reduction_type>
           <from><![CDATA[0000-00-00 00:00:00]]></from>
           <to><![CDATA[0000-00-00 00:00:00]]></to>
       </specific_price>
   </prestashop>
   ```

4. **Integration with ProductSyncStrategy:**
   ```php
   // After product sync
   if ($operation === 'create' || $operation === 'update') {
       $this->priceExporter->exportPricesForProduct($model, $shop, $externalId);
   }
   ```

---

## 📝 IMPLEMENTATION CHECKLIST

### Phase 1: Quick Fixes (MANDATORY)
- [ ] Remove `manufacturer_name` from ProductTransformer
- [ ] Add temporary `id_manufacturer => null` (or implement mapper)
- [ ] Test product update without error

### Phase 2: Specific Prices Sync (CRITICAL)
- [ ] Create `PrestaShopPriceExporter` service
- [ ] Implement `exportPricesForProduct()` method
- [ ] Handle CREATE vs UPDATE specific_prices
- [ ] Delete removed price groups from PrestaShop
- [ ] Integrate with ProductSyncStrategy
- [ ] Test with multiple price groups

### Phase 3: Manufacturer Mapper (FUTURE)
- [ ] Create `ManufacturerMapper` service
- [ ] Implement manufacturer lookup/create
- [ ] Cache manufacturer mappings
- [ ] Integrate with ProductTransformer

---

## 🧪 TESTING PLAN

### Test Case 1: Single Product, Multiple Price Groups

**Setup:**
- Product SKU: TEST-001
- Price Groups:
  - Detaliczna: 100.00 PLN netto
  - Dealer Standard: 85.00 PLN netto
  - Dealer Premium: 75.00 PLN netto

**Expected:**
- products.price = 100.00 (highest price)
- specific_price records:
  - id_group=3 (Dealer Standard) → price=85.00
  - id_group=4 (Dealer Premium) → price=75.00

### Test Case 2: Price Update

**Setup:**
- Existing product with 3 price groups
- Update Dealer Premium: 75.00 → 70.00

**Expected:**
- specific_price UPDATE via API
- Old price=75.00 → New price=70.00

### Test Case 3: Price Group Removal

**Setup:**
- Product has 3 price groups
- Remove Dealer Premium from PPM

**Expected:**
- DELETE specific_price via API
- Only 2 price groups remain in PrestaShop

---

## 📚 REFERENCES

- **PrestaShop API Docs:** `/api/specific_prices?schema=synopsis`
- **Database Schema:** `_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md`
- **Required Fields:** `_DOCS/PRESTASHOP_REQUIRED_FIELDS.md`
- **PriceGroupMapper:** `app/Services/PrestaShop/PriceGroupMapper.php`
- **PrestaShopPriceImporter:** `app/Services/PrestaShop/PrestaShopPriceImporter.php`

---

## 🚀 DEPLOYMENT

1. Deploy `manufacturer_name` fix ASAP (blocks product sync)
2. Deploy specific_prices sync after testing (major feature)
3. Monitor Laravel logs for price sync errors
4. Verify PrestaShop admin UI shows correct prices per group

---

**Status:** Ready for implementation
**Priority:** 🔥 CRITICAL (P0)
**ETA:** Phase 1: 30min, Phase 2: 2-3h
