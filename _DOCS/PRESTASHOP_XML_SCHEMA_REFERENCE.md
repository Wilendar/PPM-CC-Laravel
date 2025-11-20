# PrestaShop Web Services API - XML Schema Reference

**Wersja:** PrestaShop 8.x / 9.x
**Data:** 2025-11-05
**Źródło:** Context7 MCP + Official PrestaShop DevDocs

---

## 📖 OVERVIEW

PrestaShop Web Services API wymaga **XML** dla wszystkich operacji POST/PUT/PATCH.

**⚠️ KRYTYCZNE:**
- API **NIE** przyjmuje JSON na wejściu (tylko XML)
- API **MOŻE** zwracać JSON na wyjściu (od wersji 8.1)
- Wszystkie wartości muszą być opakowane w `<![CDATA[...]]>`
- Root element zawsze: `<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">`

---

## 🚫 READONLY FIELDS (NIE WYSYŁAĆ)

**Błąd API:** `parameter "manufacturer_name" not writable. Please remove this attribute of this XML`

### Lista Readonly Fields

| Pole | Typ | Powód | Alternatywa |
|------|-----|-------|-------------|
| `manufacturer_name` | string | ❌ Generowane z `id_manufacturer` | ✅ Użyj `id_manufacturer` |
| `supplier_name` | string | ❌ Generowane z `id_supplier` | ✅ Użyj `id_supplier` |
| `quantity` | int | ❌ Zarządzane przez stock_availables | ✅ Użyj `/api/stock_availables/{id}` |
| `date_add` | datetime | ❌ Auto-generowane przy CREATE | - |
| `date_upd` | datetime | ❌ Auto-aktualizowane przy UPDATE | - |
| `link_rewrite` | string | ❌ Readonly w PS 9.x | Auto-generowane z `name` |
| `cache_default_attribute` | int | ❌ Cache wariantu | - |
| `cache_is_pack` | bool | ❌ Cache typu pack | - |
| `cache_has_attachments` | bool | ❌ Cache załączników | - |
| `position_in_category` | int | ❌ Pozycja w kategorii | Zarządzane przez kategorie |

---

## ✅ REQUIRED FIELDS (WYMAGANE)

Minimalne pola do utworzenia produktu:

```xml
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <!-- REQUIRED #1: Nazwa (multilang) -->
        <name>
            <language id="1"><![CDATA[Product Name]]></language>
        </name>

        <!-- REQUIRED #2: Cena netto (bez VAT) -->
        <price><![CDATA[19.99]]></price>

        <!-- REQUIRED #3: Domyślna kategoria -->
        <id_category_default><![CDATA[2]]></id_category_default>

        <!-- RECOMMENDED: Kategorie (associations) -->
        <associations>
            <categories>
                <category>
                    <id><![CDATA[2]]></id>
                </category>
            </categories>
        </associations>
    </product>
</prestashop>
```

---

## 📝 WRITABLE FIELDS (MOŻNA WYSYŁAĆ)

### A. IDENTYFIKATORY (ID FIELDS)

**✅ Użyj ID zamiast nazw:**

```xml
<id_manufacturer><![CDATA[5]]></id_manufacturer>        <!-- ✅ Zamiast manufacturer_name -->
<id_supplier><![CDATA[3]]></id_supplier>                <!-- ✅ Zamiast supplier_name -->
<id_category_default><![CDATA[2]]></id_category_default><!-- ✅ REQUIRED -->
<id_tax_rules_group><![CDATA[1]]></id_tax_rules_group>  <!-- ✅ Grupa VAT -->
<id_default_image><![CDATA[10]]></id_default_image>     <!-- ✅ Domyślny obraz -->
<id_default_combination><![CDATA[8]]></id_default_combination> <!-- ✅ Wariant -->
<id_shop_default><![CDATA[1]]></id_shop_default>        <!-- ✅ Multi-store -->
```

### B. REFERENCJE I KODY

```xml
<reference><![CDATA[ABC-123]]></reference>              <!-- ✅ SKU produktu -->
<supplier_reference><![CDATA[SUP-456]]></supplier_reference> <!-- ✅ SKU dostawcy -->
<ean13><![CDATA[1234567890123]]></ean13>                <!-- ✅ EAN-13 barcode -->
<isbn><![CDATA[978-3-16-148410-0]]></isbn>              <!-- ✅ ISBN (książki) -->
<upc><![CDATA[123456789012]]></upc>                     <!-- ✅ UPC barcode -->
<mpn><![CDATA[MPN-789]]></mpn>                          <!-- ✅ Manufacturer Part Number -->
```

### C. WYMIARY I WAGA

```xml
<width><![CDATA[10.5]]></width>   <!-- ✅ cm -->
<height><![CDATA[20.0]]></height> <!-- ✅ cm -->
<depth><![CDATA[15.0]]></depth>   <!-- ✅ cm (PrestaShop używa 'depth' nie 'length') -->
<weight><![CDATA[2.5]]></weight>  <!-- ✅ kg -->
```

### D. CENY

```xml
<price><![CDATA[19.99]]></price>                        <!-- ✅ REQUIRED - Cena netto -->
<wholesale_price><![CDATA[12.00]]></wholesale_price>    <!-- ✅ Cena hurtowa -->
<ecotax><![CDATA[0.50]]></ecotax>                       <!-- ✅ Opłata ekologiczna -->
<unit_price_ratio><![CDATA[1.0]]></unit_price_ratio>    <!-- ✅ Stosunek ceny jednostkowej -->
<additional_shipping_cost><![CDATA[5.00]]></additional_shipping_cost> <!-- ✅ Dodatkowy koszt -->
```

### E. STOCK

**⚠️ UWAGA:** Stock zarządzany przez osobny endpoint `/stock_availables`!

```xml
<!-- Product fields (informacyjne) -->
<minimal_quantity><![CDATA[1]]></minimal_quantity>          <!-- ✅ Minimalna ilość zamówienia -->
<low_stock_threshold><![CDATA[5]]></low_stock_threshold>    <!-- ✅ Próg niskiego stanu -->
<low_stock_alert><![CDATA[1]]></low_stock_alert>            <!-- ✅ Alert (0/1) -->
<advanced_stock_management><![CDATA[0]]></advanced_stock_management> <!-- ✅ Zaawansowane (0/1) -->

<!-- Faktyczny stock - osobny endpoint! -->
<!-- PUT /api/stock_availables/{id} -->
<!-- <stock_available><id>123</id><quantity>100</quantity></stock_available> -->
```

### F. STATUS I WIDOCZNOŚĆ

```xml
<active><![CDATA[1]]></active>                      <!-- ✅ Aktywny (0/1) -->
<available_for_order><![CDATA[1]]></available_for_order> <!-- ✅ Dostępny do zamówienia -->
<show_price><![CDATA[1]]></show_price>              <!-- ✅ Pokaż cenę -->
<online_only><![CDATA[0]]></online_only>            <!-- ✅ Tylko online -->
<on_sale><![CDATA[0]]></on_sale>                    <!-- ✅ W promocji -->
<condition><![CDATA[new]]></condition>              <!-- ✅ new/used/refurbished -->
<show_condition><![CDATA[1]]></show_condition>      <!-- ✅ Pokaż stan -->
<visibility><![CDATA[both]]></visibility>           <!-- ✅ both/catalog/search/none -->
<indexed><![CDATA[1]]></indexed>                    <!-- ✅ Indeksowany w wyszukiwarce -->
```

### G. TYP PRODUKTU

```xml
<type><![CDATA[standard]]></type>      <!-- ✅ standard/pack/virtual/combinations -->
<is_virtual><![CDATA[0]]></is_virtual> <!-- ✅ Produkt wirtualny (0/1) -->
<customizable><![CDATA[0]]></customizable> <!-- ✅ Personalizacja (liczba pól) -->
<text_fields><![CDATA[0]]></text_fields>   <!-- ✅ Liczba pól tekstowych -->
<uploadable_files><![CDATA[0]]></uploadable_files> <!-- ✅ Liczba plików -->
```

### H. SEO (MULTILANG)

```xml
<meta_title>
    <language id="1"><![CDATA[Product Title for SEO]]></language>
</meta_title>
<meta_description>
    <language id="1"><![CDATA[Product description for search engines]]></language>
</meta_description>
<meta_keywords>
    <language id="1"><![CDATA[keyword1, keyword2, keyword3]]></language>
</meta_keywords>
<link_rewrite>
    <language id="1"><![CDATA[product-url-slug]]></language>
</link_rewrite>
```

### I. OPISY (MULTILANG)

```xml
<name>  <!-- ✅ REQUIRED -->
    <language id="1"><![CDATA[Product Name]]></language>
</name>
<description>
    <language id="1"><![CDATA[<p>Full HTML description</p>]]></language>
</description>
<description_short>
    <language id="1"><![CDATA[<p>Short description</p>]]></language>
</description_short>
<available_now>
    <language id="1"><![CDATA[In stock]]></language>
</available_now>
<available_later>
    <language id="1"><![CDATA[Available soon]]></language>
</available_later>
```

### J. ASSOCIATIONS (POWIĄZANIA)

```xml
<associations>
    <!-- Kategorie (REQUIRED - musi zawierać id_category_default) -->
    <categories>
        <category>
            <id><![CDATA[2]]></id>  <!-- id_category_default -->
        </category>
        <category>
            <id><![CDATA[5]]></id>  <!-- dodatkowa kategoria -->
        </category>
    </categories>

    <!-- Obrazy -->
    <images>
        <image>
            <id><![CDATA[10]]></id>
        </image>
        <image>
            <id><![CDATA[11]]></id>
        </image>
    </images>

    <!-- Warianty (combinations) -->
    <combinations>
        <combination>
            <id><![CDATA[15]]></id>
        </combination>
    </combinations>

    <!-- Cechy produktu -->
    <product_features>
        <product_feature>
            <id><![CDATA[1]]></id>                      <!-- ID cechy -->
            <id_feature_value><![CDATA[3]]></id_feature_value> <!-- ID wartości -->
        </product_feature>
    </product_features>

    <!-- Tagi -->
    <tags>
        <tag>
            <id><![CDATA[5]]></id>
        </tag>
    </tags>
</associations>
```

---

## 🔧 UPDATE vs CREATE

### POST (Tworzenie nowego produktu)

```http
POST /api/products
Content-Type: application/xml

<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <!-- Wszystkie wymagane pola -->
    <name>...</name>
    <price>...</price>
    <id_category_default>...</id_category_default>
  </product>
</prestashop>
```

### PUT (Pełna aktualizacja - wszystkie pola)

**⚠️ KRYTYCZNE:** PUT wymaga `<id>` w XML!

```http
PUT /api/products/123
Content-Type: application/xml

<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id><![CDATA[123]]></id>  <!-- ⚠️ WYMAGANE dla UPDATE -->
    <!-- WSZYSTKIE pola, nawet niezmienione -->
    <name>...</name>
    <price>...</price>
    <!-- ... -->
  </product>
</prestashop>
```

### PATCH (Częściowa aktualizacja - tylko zmienione pola)

```http
PATCH /api/products/123
Content-Type: application/xml

<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id><![CDATA[123]]></id>
    <!-- TYLKO zmienione pola -->
    <price><![CDATA[99.99]]></price>
  </product>
</prestashop>
```

---

## 📊 PEŁNY PRZYKŁAD XML - PRODUCT CREATE

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <!-- ================ REQUIRED FIELDS ================ -->
        <name>
            <language id="1"><![CDATA[Example Product]]></language>
        </name>
        <price><![CDATA[19.99]]></price>
        <id_category_default><![CDATA[2]]></id_category_default>

        <!-- ================ IDENTIFIERS ================ -->
        <id_manufacturer><![CDATA[5]]></id_manufacturer>
        <id_supplier><![CDATA[3]]></id_supplier>
        <id_tax_rules_group><![CDATA[1]]></id_tax_rules_group>

        <!-- ================ REFERENCES ================ -->
        <reference><![CDATA[ABC-123]]></reference>
        <supplier_reference><![CDATA[SUP-456]]></supplier_reference>
        <ean13><![CDATA[1234567890123]]></ean13>
        <upc><![CDATA[123456789012]]></upc>
        <mpn><![CDATA[MPN-789]]></mpn>

        <!-- ================ DIMENSIONS ================ -->
        <width><![CDATA[10.5]]></width>
        <height><![CDATA[20.0]]></height>
        <depth><![CDATA[15.0]]></depth>
        <weight><![CDATA[2.5]]></weight>

        <!-- ================ PRICES ================ -->
        <wholesale_price><![CDATA[12.00]]></wholesale_price>
        <ecotax><![CDATA[0.50]]></ecotax>

        <!-- ================ STOCK ================ -->
        <minimal_quantity><![CDATA[1]]></minimal_quantity>
        <low_stock_threshold><![CDATA[5]]></low_stock_threshold>
        <low_stock_alert><![CDATA[1]]></low_stock_alert>

        <!-- ================ STATUS ================ -->
        <active><![CDATA[1]]></active>
        <available_for_order><![CDATA[1]]></available_for_order>
        <show_price><![CDATA[1]]></show_price>
        <visibility><![CDATA[both]]></visibility>
        <condition><![CDATA[new]]></condition>

        <!-- ================ TYPE ================ -->
        <type><![CDATA[standard]]></type>
        <is_virtual><![CDATA[0]]></is_virtual>

        <!-- ================ SEO (MULTILANG) ================ -->
        <meta_title>
            <language id="1"><![CDATA[Product SEO Title]]></language>
        </meta_title>
        <meta_description>
            <language id="1"><![CDATA[Product SEO description]]></language>
        </meta_description>
        <link_rewrite>
            <language id="1"><![CDATA[example-product]]></language>
        </link_rewrite>

        <!-- ================ DESCRIPTIONS (MULTILANG) ================ -->
        <description>
            <language id="1"><![CDATA[<p>Full HTML description</p>]]></language>
        </description>
        <description_short>
            <language id="1"><![CDATA[<p>Short description</p>]]></language>
        </description_short>

        <!-- ================ ASSOCIATIONS ================ -->
        <associations>
            <categories>
                <category>
                    <id><![CDATA[2]]></id>
                </category>
                <category>
                    <id><![CDATA[5]]></id>
                </category>
            </categories>
            <product_features>
                <product_feature>
                    <id><![CDATA[1]]></id>
                    <id_feature_value><![CDATA[3]]></id_feature_value>
                </product_feature>
            </product_features>
        </associations>
    </product>
</prestashop>
```

---

## 🏢 MANUFACTURER HANDLING

**❌ BŁĄD:**
```xml
<manufacturer_name><![CDATA[Brand Name]]></manufacturer_name>
```
**Wynik:** `parameter "manufacturer_name" not writable`

**✅ POPRAWNIE:**
```xml
<id_manufacturer><![CDATA[5]]></id_manufacturer>
```

### Workflow: Name → ID

```php
// 1. Sprawdź czy manufacturer istnieje
GET /api/manufacturers?filter[name]=Brand%20Name&display=[id,name]

// 2a. Jeśli istnieje - użyj ID
<id_manufacturer><![CDATA[5]]></id_manufacturer>

// 2b. Jeśli NIE istnieje - utwórz nowego
POST /api/manufacturers
<prestashop>
  <manufacturer>
    <name><![CDATA[Brand Name]]></name>
    <active><![CDATA[1]]></active>
  </manufacturer>
</prestashop>

// 3. Użyj zwróconego ID w produkcie
```

---

## 📚 DOSTĘPNE RESOURCES (80+)

### Product Management
- `products` - Produkty
- `combinations` - Warianty produktów
- `product_options` - Grupy atrybutów (np. Size, Color)
- `product_option_values` - Wartości atrybutów (np. Large, Red)
- `product_features` - Cechy produktów
- `categories` - Kategorie
- `manufacturers` - Producenci
- `suppliers` - Dostawcy
- `images` - Obrazy

### Orders & Commerce
- `orders` - Zamówienia
- `order_details` - Pozycje zamówień
- `order_invoices` - Faktury
- `carts` - Koszyki
- `cart_rules` - Reguły koszyka (promocje)
- `customers` - Klienci
- `addresses` - Adresy

### Stock Management
- `stock_availables` - Dostępność magazynowa
- `stock_movements` - Ruchy magazynowe
- `warehouses` - Magazyny
- `warehouse_product_locations` - Lokalizacje produktów w magazynach

### Configuration
- `configurations` - Konfiguracja sklepu
- `shops` - Sklepy (multi-store)
- `shop_groups` - Grupy sklepów
- `languages` - Języki
- `currencies` - Waluty
- `countries` - Kraje
- `zones` - Strefy
- `taxes` - Podatki
- `tax_rules` - Reguły podatkowe

---

## 🔗 REFERENCJE

**Context7 Libraries:**
- `/prestashop/docs` (2098 snippets, trust 8.2)
- `/websites/devdocs_prestashop-project_8` (1861 snippets, trust 7.5)
- `/websites/devdocs_prestashop-project_9` (2252 snippets, trust 7.5)

**Official Documentation:**
- PrestaShop 8: https://devdocs.prestashop-project.org/8/webservice/
- PrestaShop 9: https://devdocs.prestashop-project.org/9/webservice/
- API Reference: `/api/?schema=synopsis`
- Blank Schema: `/api/?schema=blank`

**Key Pages:**
- Getting Started: `/webservice/getting-started/`
- Testing Access: `/webservice/tutorials/testing-access/`
- API Reference: `/webservice/reference/`
- Cheat Sheet: `/webservice/cheat-sheet/`

---

**Dokumentacja wygenerowana:** 2025-11-05
**Projekt:** PPM-CC-Laravel
**Kontekst:** ETAP_07 PrestaShop Integration
