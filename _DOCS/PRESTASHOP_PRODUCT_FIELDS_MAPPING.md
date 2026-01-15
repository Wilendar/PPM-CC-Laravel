# PrestaShop Product Fields vs PPM - Kompleksowa Mapa Pól

**Data utworzenia**: 2025-12-05
**Wersja**: 1.0
**Autor**: Claude Code Agent
**Status**: KRYTYCZNA DOKUMENTACJA po incydencie wymazania produktów

## 🔥 KONTEKST: Incydent 2025-12-05

Bug w `PrestaShopFeatureSyncService.php` powodował wymazanie danych produktów przy synchronizacji cech.
**Root cause**: PrestaShop PUT **ZASTĘPUJE** cały zasób - brakujące pola = PUSTE wartości.

---

## 📋 LEGENDA STATUSÓW

| Status | Znaczenie |
|--------|-----------|
| ✅ SYNC | Pole jest synchronizowane w OBIE strony |
| ➡️ EXPORT | Pole jest eksportowane do PrestaShop |
| ⬅️ IMPORT | Pole jest importowane z PrestaShop |
| ❌ BRAK | Pole nie jest synchronizowane |
| 🔧 TODO | Pole wymaga implementacji |
| ⚠️ READONLY | Pole tylko do odczytu (PS nie akceptuje w PUT) |

---

## 🏪 PRESTASHOP: Pełna Lista Pól Produktu

### 1. POLA IDENTYFIKACYJNE

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `id` | int | `external_id` (ProductShopData) | - | ✅ SYNC | ID w PrestaShop, zapisywany w product_shop_data |
| `reference` | string(64) | `sku` | Podstawowe | ✅ SYNC | **KRYTYCZNE** - SKU produktu |
| `ean13` | string(13) | `ean` | Podstawowe | ✅ SYNC | Kod EAN |
| `isbn` | string(32) | - | - | ❌ BRAK | ISBN dla książek |
| `upc` | string(12) | - | - | ❌ BRAK | UPC dla US market |
| `mpn` | string(40) | - | - | ❌ BRAK | Manufacturer Part Number |
| `supplier_reference` | string(64) | `supplier_code` | Podstawowe | 🔧 TODO | Kod dostawcy |

### 2. POLA MULTILINGUAL (name, description)

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `name` | string[lang] | `name` | Podstawowe | ✅ SYNC | **KRYTYCZNE** - Nazwa produktu |
| `description` | text[lang] | `long_description` | Opis | ✅ SYNC | Długi opis HTML |
| `description_short` | text[lang] | `short_description` | Opis | ✅ SYNC | Krótki opis (max 800 znaków) |
| `link_rewrite` | string[lang] | `slug` | SEO | ✅ SYNC | **KRYTYCZNE** - URL slug |
| `meta_title` | string[lang] | `meta_title` | SEO | ✅ SYNC | SEO tytuł |
| `meta_description` | text[lang] | `meta_description` | SEO | ✅ SYNC | SEO opis |
| `meta_keywords` | string[lang] | - | - | ❌ BRAK | Deprecated w PS 8.x+ |
| `available_now` | string[lang] | - | - | ❌ BRAK | Tekst "Dostępny" |
| `available_later` | string[lang] | - | - | ❌ BRAK | Tekst "Wkrótce dostępny" |
| `delivery_in_stock` | string[lang] | - | - | ❌ BRAK | Czas dostawy (w magazynie) |
| `delivery_out_stock` | string[lang] | - | - | ❌ BRAK | Czas dostawy (brak w magazynie) |

### 3. POLA CENOWE

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `price` | float | `ProductPrice.price_net` | Ceny | ✅ SYNC | Cena netto bazowa |
| `wholesale_price` | float | `ProductPrice (grupa zakupu)` | Ceny | 🔧 TODO | Cena zakupu |
| `unit_price` | float | - | - | ❌ BRAK | Cena jednostkowa |
| `unity` | string | - | - | ❌ BRAK | Jednostka miary |
| `unit_price_ratio` | float | - | - | ❌ BRAK | Ratio ceny jednostkowej |
| `ecotax` | float | - | - | ❌ BRAK | Podatek ekologiczny |
| `on_sale` | bool | - | - | ❌ BRAK | Flaga promocji |
| `id_tax_rules_group` | int | `tax_rate` (mapped) | Ceny | ✅ SYNC | Grupa podatkowa |
| `additional_shipping_cost` | float | - | - | ❌ BRAK | Dodatkowy koszt wysyłki |

### 4. POLA FIZYCZNE

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `weight` | float | `weight` | Fizyczne | ✅ SYNC | Waga w kg |
| `width` | float | `width` | Fizyczne | ✅ SYNC | Szerokość w cm |
| `height` | float | `height` | Fizyczne | ✅ SYNC | Wysokość w cm |
| `depth` | float | `length` | Fizyczne | ✅ SYNC | Głębokość (PrestaShop = depth, PPM = length) |

### 5. POLA STATUSU I WIDOCZNOŚCI

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `active` | bool | `is_active` | Podstawowe | ✅ SYNC | Czy produkt aktywny |
| `visibility` | enum | - | - | ➡️ EXPORT | both/catalog/search/none (hardcoded: "both") |
| `available_for_order` | bool | - | - | ➡️ EXPORT | Czy można zamawiać (hardcoded: 1) |
| `show_price` | bool | - | - | ➡️ EXPORT | Czy pokazywać cenę (hardcoded: 1) |
| `online_only` | bool | - | - | ❌ BRAK | Tylko sprzedaż online |
| `condition` | enum | - | - | ❌ BRAK | new/used/refurbished |
| `show_condition` | bool | - | - | ❌ BRAK | Czy pokazywać stan |

### 6. POLA KATEGORII I RELACJI

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `id_category_default` | int | `category_mappings.ui.primary` | Kategorie | ✅ SYNC | **KRYTYCZNE** - Domyślna kategoria |
| `associations.categories` | array | `category_mappings` | Kategorie | ✅ SYNC | Lista kategorii |
| `id_manufacturer` | int | `manufacturer` (unmapped) | Podstawowe | 🔧 TODO | ID producenta |
| `id_supplier` | int | - | - | ❌ BRAK | ID dostawcy |
| `associations.images` | array | `media` | Galeria | ⬅️ IMPORT | Obrazy (import przez osobny endpoint) |
| `associations.product_features` | array | `ProductFeature` | Atrybuty | ✅ SYNC | Cechy produktu |
| `associations.combinations` | array | `ProductVariant` | Warianty | 🔧 TODO | Kombinacje/warianty |
| `associations.product_option_values` | array | - | - | 🔧 TODO | Wartości opcji |

### 7. POLA MAGAZYNOWE (⚠️ READONLY w API!)

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `quantity` | int | `Stock.quantity` | Magazyn | ⚠️ READONLY | UWAGA: Nie można ustawić przez /products! |
| `minimal_quantity` | int | - | - | ➡️ EXPORT | Min. ilość do zamówienia (hardcoded: 1) |
| `low_stock_threshold` | int | - | - | ❌ BRAK | Próg niskiego stanu |
| `low_stock_alert` | bool | - | - | ❌ BRAK | Alert niskiego stanu |
| `out_of_stock` | int | - | - | ❌ BRAK | Zachowanie przy braku stanu |
| `depends_on_stock` | bool | - | - | ❌ BRAK | Zależność od magazynu |
| `location` | string | `Stock.warehouse_id` (indirect) | Magazyn | ❌ BRAK | Lokalizacja w magazynie |

### 8. POLA TECHNICZNE/SYSTEMOWE

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `state` | int | - | - | ➡️ EXPORT | Stan produktu (hardcoded: 1) |
| `redirect_type` | string | - | - | ➡️ EXPORT | Typ przekierowania (hardcoded: "301-category") |
| `id_shop_default` | int | `shop.prestashop_shop_id` | - | ➡️ EXPORT | Domyślny sklep |
| `additional_delivery_times` | int | - | - | ➡️ EXPORT | Dodatkowe czasy dostawy (hardcoded: 1) |
| `product_type` | enum | - | - | ❌ BRAK | standard/pack/virtual |
| `cache_is_pack` | bool | - | - | ⚠️ READONLY | Cache czy paczka |
| `cache_has_attachments` | bool | - | - | ⚠️ READONLY | Cache czy ma załączniki |
| `cache_default_attribute` | int | - | - | ⚠️ READONLY | Cache domyślny atrybut |
| `date_add` | datetime | `created_at` | - | ⚠️ READONLY | Data utworzenia |
| `date_upd` | datetime | `updated_at` | - | ⚠️ READONLY | Data modyfikacji |

### 9. POLA ZAAWANSOWANE

| PrestaShop Field | Typ | PPM Field | ProductForm Tab | Status | Notatki |
|-----------------|-----|-----------|-----------------|--------|---------|
| `is_virtual` | bool | - | - | ❌ BRAK | Produkt wirtualny |
| `customizable` | int | - | - | ❌ BRAK | Czy customizable |
| `text_fields` | int | - | - | ❌ BRAK | Ilość pól tekstowych |
| `uploadable_files` | int | - | - | ❌ BRAK | Ilość plików do uploadu |
| `indexed` | bool | - | - | ⚠️ READONLY | Czy zindeksowany |
| `advanced_stock_management` | bool | - | - | ❌ BRAK | Zaawansowane magazynowanie |
| `pack_stock_type` | int | - | - | ❌ BRAK | Typ stanu paczki |

---

## 📦 PPM ProductForm - Mapowanie Zakładek

### Tab: Podstawowe (basic-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| SKU | `Product.sku` | ✅ TAK | → reference |
| Nazwa | `Product.name` | ✅ TAK | → name[lang] |
| Typ produktu | `Product.product_type_id` | ❌ NIE | Wewnętrzna klasyfikacja |
| Producent | `Product.manufacturer` | 🔧 TODO | → id_manufacturer (wymaga mappera) |
| EAN | `Product.ean` | ✅ TAK | → ean13 |
| Aktywny | `Product.is_active` | ✅ TAK | → active |
| Wyróżniony | `Product.is_featured` | ❌ NIE | Wewnętrzna flaga |

### Tab: Opis (description-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Krótki opis | `Product.short_description` | ✅ TAK | → description_short[lang] |
| Długi opis | `Product.long_description` | ✅ TAK | → description[lang] |
| Meta tytuł | `Product.meta_title` | ✅ TAK | → meta_title[lang] |
| Meta opis | `Product.meta_description` | ✅ TAK | → meta_description[lang] |

### Tab: Fizyczne (physical-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Waga | `Product.weight` | ✅ TAK | → weight |
| Wysokość | `Product.height` | ✅ TAK | → height |
| Szerokość | `Product.width` | ✅ TAK | → width |
| Długość | `Product.length` | ✅ TAK | → depth |

### Tab: Ceny (prices-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Cena netto | `ProductPrice.price_net` | ✅ TAK | → price |
| Stawka VAT | `Product.tax_rate` | ✅ TAK | → id_tax_rules_group (mapped) |
| Grupa cenowa | `ProductPrice.price_group_id` | ❌ NIE | Wewnętrzne grupy cenowe |

### Tab: Magazyn (stock-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Ilość | `Stock.quantity` | ❌ NIE | ⚠️ READONLY w PS! Wymaga /stock_availables |
| Magazyn | `Stock.warehouse_id` | ❌ NIE | Wewnętrzne magazyny |
| Rezerwacja | `Stock.reserved_quantity` | ❌ NIE | Wewnętrzne rezerwacje |

### Tab: Galeria (gallery-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Zdjęcia | `Media` | 🔧 TODO | → POST /images/products/{id} |
| Główne zdjęcie | `Media.is_cover` | 🔧 TODO | → cover parameter |

### Tab: Atrybuty/Cechy (attributes-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Cechy produktu | `ProductFeature` | ✅ TAK | → associations.product_features |

### Tab: Warianty (variants-tab.blade.php)
| Pole UI | Model Field | Eksportowane | Notatki |
|---------|-------------|--------------|---------|
| Warianty | `ProductVariant` | 🔧 TODO | → associations.combinations |
| Atrybuty wariantów | `VariantAttribute` | 🔧 TODO | → product_option_values |

---

## 🎯 PODSUMOWANIE STATUSU SYNCHRONIZACJI

### ✅ W PEŁNI ZSYNCHRONIZOWANE (19 pól)
1. reference (SKU)
2. name
3. description
4. description_short
5. link_rewrite
6. meta_title
7. meta_description
8. price
9. weight
10. width
11. height
12. depth
13. active
14. id_category_default
15. associations.categories
16. id_tax_rules_group
17. id_shop_default (export only)
18. state (export only)
19. associations.product_features

### ➡️ TYLKO EKSPORT - Hardcoded (7 pól)
1. visibility ("both")
2. available_for_order (1)
3. show_price (1)
4. minimal_quantity (1)
5. redirect_type ("301-category")
6. state (1)
7. additional_delivery_times (1)

### 🔧 WYMAGA IMPLEMENTACJI (8 pól)
1. id_manufacturer - ManufacturerMapper
2. supplier_reference - SupplierMapper
3. ean13 → upc, isbn, mpn (pozostałe kody)
4. wholesale_price - cena zakupu
5. associations.images - MediaSyncService
6. associations.combinations - VariantSyncService
7. product_option_values - AttributeSyncService
8. quantity → /stock_availables endpoint

### ❌ NIE PLANOWANE (pozostałe ~40 pól)
- unity, unit_price, ecotax (jednostki, eko-podatek)
- online_only, condition (warunki)
- is_virtual, customizable (typy specjalne)
- available_now/later, delivery_in/out_stock (teksty dostawy)
- meta_keywords (deprecated)
- low_stock_*, out_of_stock (alerty magazynowe)
- advanced_stock_management (zaawansowane magazynowanie)

---

## 🔴 KRYTYCZNE POLA - Nigdy nie zostawiaj pustych!

**Przy KAŻDYM PUT/UPDATE do PrestaShop te pola MUSZĄ być wypełnione:**

1. **reference** - SKU produktu
2. **name** - Nazwa (multilang)
3. **link_rewrite** - URL slug (multilang)
4. **id_category_default** - Domyślna kategoria
5. **price** - Cena bazowa
6. **active** - Status aktywności
7. **state** - Stan (musi być 1)
8. **minimal_quantity** - Minimalna ilość (musi być 1)

**Brak tych pól = Produkt "znika" z panelu admina PrestaShop!**

---

## 📝 ZALECENIA

### Immediate Actions
1. ✅ **DONE** - Fix GET-MODIFY-PUT pattern w `PrestaShopFeatureSyncService.php`
2. 🔧 **TODO** - Utworzyć `ManufacturerMapper` service
3. 🔧 **TODO** - Implementować sync obrazów przez `/images/products/{id}`
4. 🔧 **TODO** - Implementować sync stanów przez `/stock_availables`

### Future Enhancements
1. Dodać pola tekstów dostępności (available_now/later)
2. Dodać condition (new/used/refurbished)
3. Rozważyć wholesale_price dla cen zakupu
4. Implementować pack products jeśli potrzebne

---

## 📚 REFERENCJE

- PrestaShop 8.x Web Services: https://devdocs.prestashop-project.org/8/webservice/
- PPM ProductTransformer: `app/Services/PrestaShop/ProductTransformer.php`
- PPM Product Model: `app/Models/Product.php`
- PPM ProductShopData: `app/Models/ProductShopData.php`
- Bug Fix Reference: `_ISSUES_FIXES/FEATURE_SYNC_WIPES_PRODUCT_DATA.md`
