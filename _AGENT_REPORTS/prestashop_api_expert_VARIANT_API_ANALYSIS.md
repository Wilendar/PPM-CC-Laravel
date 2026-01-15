# RAPORT ANALIZY: PrestaShop API dla Systemu Wariantów

**Data**: 2025-12-03
**Agent**: prestashop-api-expert
**Zadanie**: Analiza PrestaShop API dla systemu wariantów produktowych i strategia integracji z PPM

---

## ✅ STRESZCZENIE WYKONAWCZE

Przeanalizowano strukturę PrestaShop API 8.x/9.x dla produktów wariantowych (combinations). Zidentyfikowano wszystkie wymagane pola, przygotowano przykłady XML, zaprojektowano mapowanie PPM ↔ PrestaShop oraz strategię synchronizacji.

**KLUCZOWE USTALENIA:**
- ✅ PrestaShop używa terminu "combinations" dla wariantów
- ✅ Każdy combination = `ps_product_attribute` (główna tabela) + `ps_product_attribute_combination` (atrybuty)
- ✅ API endpoint: `/api/combinations` (POST/GET/PUT/PATCH/DELETE)
- ✅ Stock wariantów zarządzany przez `ps_stock_available` (AUTO-GENERATED przy CREATE)
- ✅ Zdjęcia wariantów przez associations + `/api/images/products/{id}/{id_combination}`
- ✅ Wymagane: `id_product`, `associations.product_option_values` (minimum 1 attribute)

---

## 📊 1. STRUKTURA DANYCH PRESTASHOP

### 1.1 Tabele Bazy Danych

#### Główne Tabele Wariantów

| Tabela | Purpose | Kluczowe Pola | Primary Key |
|--------|---------|---------------|-------------|
| `ps_product_attribute` | Główne dane kombinacji | `id_product_attribute`, `id_product`, `reference`, `ean13`, `price` (impact), `quantity`, `default_on` | `id_product_attribute` |
| `ps_product_attribute_combination` | Atrybuty kombinacji | `id_attribute`, `id_product_attribute` | `(id_attribute, id_product_attribute)` |
| `ps_stock_available` | Stany magazynowe | `id_product`, `id_product_attribute`, `id_shop`, `quantity`, `depends_on_stock` | `(id_product, id_product_attribute, id_shop, id_shop_group)` |
| `ps_product_attribute_image` | Zdjęcia kombinacji | `id_product_attribute`, `id_image` | `(id_product_attribute, id_image)` |

#### Tabele Atrybutów (Attribute System)

| Tabela | Purpose | Kluczowe Pola | Primary Key |
|--------|---------|---------------|-------------|
| `ps_attribute_group` | Grupy atrybutów (Color, Size) | `id_attribute_group`, `group_type`, `position` | `id_attribute_group` |
| `ps_attribute_group_lang` | Tłumaczenia grup | `id_attribute_group`, `id_lang`, `name`, `public_name` | `(id_attribute_group, id_lang)` |
| `ps_attribute` | Wartości atrybutów (Red, XL) | `id_attribute`, `id_attribute_group`, `color` (hex), `position` | `id_attribute` |
| `ps_attribute_lang` | Tłumaczenia wartości | `id_attribute`, `id_lang`, `name` | `(id_attribute, id_lang)` |

### 1.2 Schema ps_product_attribute

**Pełna struktura pól:**

```sql
CREATE TABLE `ps_product_attribute` (
  `id_product_attribute` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product` INT(10) UNSIGNED NOT NULL,

  -- Identifiers
  `reference` VARCHAR(64) DEFAULT NULL,
  `supplier_reference` VARCHAR(64) DEFAULT NULL,
  `ean13` VARCHAR(13) DEFAULT NULL,
  `isbn` VARCHAR(32) DEFAULT NULL,
  `upc` VARCHAR(12) DEFAULT NULL,
  `mpn` VARCHAR(40) DEFAULT NULL,

  -- Location & Stock
  `location` VARCHAR(255) DEFAULT NULL,
  `quantity` INT(10) DEFAULT 0,
  `minimal_quantity` INT(10) UNSIGNED DEFAULT 1,
  `low_stock_threshold` INT(10) DEFAULT NULL,
  `low_stock_alert` TINYINT(1) DEFAULT 0,

  -- Pricing (IMPACT on base product price)
  `price` DECIMAL(20,6) DEFAULT 0.000000,        -- Price IMPACT (add to product.price)
  `wholesale_price` DECIMAL(20,6) DEFAULT 0.000000,
  `ecotax` DECIMAL(17,6) DEFAULT 0.000000,
  `unit_price_impact` DECIMAL(20,6) DEFAULT 0.000000,

  -- Physical
  `weight` DECIMAL(20,6) DEFAULT 0.000000,       -- Weight IMPACT (add to product.weight)

  -- Availability
  `default_on` TINYINT(1) UNSIGNED DEFAULT NULL, -- Is default combination
  `available_date` DATE DEFAULT '0000-00-00',

  PRIMARY KEY (`id_product_attribute`),
  KEY `id_product` (`id_product`),
  KEY `reference` (`reference`),
  KEY `supplier_reference` (`supplier_reference`),
  KEY `id_product_id_product_attribute` (`id_product_attribute`, `id_product`)
);
```

**⚠️ KLUCZOWE UWAGI:**

1. **PRICE IMPACT MODEL:**
   - `ps_product_attribute.price` = RÓŻNICA w cenie (nie cena absolutna!)
   - Final price = `ps_product.price` + `ps_product_attribute.price`
   - Przykład: Product base price = 100 PLN, Combination price = 10 PLN → Customer pays 110 PLN

2. **QUANTITY:**
   - `ps_product_attribute.quantity` istnieje, ale jest **READONLY**
   - Faktyczny stan zarządzany przez `ps_stock_available`
   - PrestaShop AUTO-generuje `stock_available` przy CREATE combination

3. **DEFAULT COMBINATION:**
   - `default_on` = 1 dla domyślnego wariantu (pokazywany jako pierwszy)
   - TYLKO JEDEN combination per product może mieć `default_on = 1`

### 1.3 Schema ps_product_attribute_combination

**Link atrybutów do kombinacji:**

```sql
CREATE TABLE `ps_product_attribute_combination` (
  `id_attribute` INT(10) UNSIGNED NOT NULL,
  `id_product_attribute` INT(10) UNSIGNED NOT NULL,

  PRIMARY KEY (`id_attribute`, `id_product_attribute`),
  KEY `id_product_attribute` (`id_product_attribute`)
);
```

**Przykład:**
```
Product: T-Shirt (id_product = 1)

Attribute Groups:
- Color (id_attribute_group = 1)
  - Red (id_attribute = 10)
  - Blue (id_attribute = 11)
- Size (id_attribute_group = 2)
  - S (id_attribute = 20)
  - M (id_attribute = 21)
  - L (id_attribute = 22)

Combinations (ps_product_attribute):
- T-Shirt Red S (id_product_attribute = 100, reference = "TSHIRT-RED-S", price = 0.00)
- T-Shirt Red M (id_product_attribute = 101, reference = "TSHIRT-RED-M", price = 5.00)
- T-Shirt Blue L (id_product_attribute = 102, reference = "TSHIRT-BLUE-L", price = 10.00)

Combination Details (ps_product_attribute_combination):
- id_product_attribute=100: [(id_attribute=10), (id_attribute=20)]  // Red + S
- id_product_attribute=101: [(id_attribute=10), (id_attribute=21)]  // Red + M
- id_product_attribute=102: [(id_attribute=11), (id_attribute=22)]  // Blue + L
```

### 1.4 Stock Management

**ps_stock_available schema:**

```sql
CREATE TABLE `ps_stock_available` (
  `id_stock_available` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product` INT(11) UNSIGNED NOT NULL,
  `id_product_attribute` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- 0 = main product, >0 = combination
  `id_shop` INT(11) UNSIGNED NOT NULL,
  `id_shop_group` INT(11) UNSIGNED NOT NULL,

  `quantity` INT(11) NOT NULL DEFAULT 0,
  `depends_on_stock` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `out_of_stock` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `location` VARCHAR(255) NOT NULL DEFAULT '',

  PRIMARY KEY (`id_stock_available`),
  UNIQUE KEY `product_sqlstock` (`id_product`, `id_product_attribute`, `id_shop`, `id_shop_group`),
  KEY `id_shop` (`id_shop`),
  KEY `id_shop_group` (`id_shop_group`),
  KEY `id_product` (`id_product`),
  KEY `id_product_attribute` (`id_product_attribute`)
);
```

**⚠️ KRYTYCZNE ZASADY:**

1. **AUTO-GENERATION:** PrestaShop automatycznie tworzy `stock_available` przy CREATE combination
2. **UPDATE STOCK:** Używaj `PUT /api/stock_availables/{id}` (NIE direct SQL!)
3. **FIND STOCK:** Query by `id_product` + `id_product_attribute` + `id_shop`
4. **MAIN vs COMBINATION:**
   - `id_product_attribute = 0` → Stock głównego produktu (bez wariantów)
   - `id_product_attribute > 0` → Stock konkretnego wariantu

---

## 📋 2. API ENDPOINTS - COMBINATIONS

### 2.1 Dostępne Operacje

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/combinations` | List all combinations | ✅ |
| GET | `/api/combinations/{id}` | Get single combination | ✅ |
| GET | `/api/combinations?schema=blank` | Get blank XML template | ✅ |
| GET | `/api/combinations?schema=synopsis` | Get field documentation | ✅ |
| POST | `/api/combinations` | Create new combination | ✅ |
| PUT | `/api/combinations/{id}` | Full update (ALL fields) | ✅ |
| PATCH | `/api/combinations/{id}` | Partial update (changed fields) | ✅ |
| DELETE | `/api/combinations/{id}` | Delete combination | ✅ |

### 2.2 Query Parameters

**GET /api/combinations (list):**
```
?display=[id,reference,price]         # Select specific fields
?filter[id_product]=[123]             # Filter by product
?filter[reference]=[SKU-123]          # Filter by reference
?limit=0,50                           # Pagination (offset, limit)
?sort=[id_product_attribute_ASC]      # Sorting
?output_format=JSON                   # JSON output (default: XML)
```

**GET /api/combinations/{id} (single):**
```
?display=full                         # All fields (default)
?output_format=JSON                   # JSON output
```

### 2.3 Wymagane Pola dla CREATE

**MINIMUM REQUIRED:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id_product` | integer | ✅ MANDATORY | Parent product ID |
| `associations.product_option_values` | array | ✅ MANDATORY | At least 1 attribute value |

**OPTIONAL (ale zalecane):**

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `reference` | string(64) | NULL | SKU wariantu |
| `ean13` | string(13) | NULL | Barcode |
| `mpn` | string(40) | NULL | Manufacturer Part Number |
| `price` | decimal(20,6) | 0.000000 | Price IMPACT (not absolute!) |
| `wholesale_price` | decimal(20,6) | 0.000000 | Cost price |
| `weight` | decimal(20,6) | 0.000000 | Weight IMPACT |
| `quantity` | integer | 0 | Initial stock (deprecated - use stock_available!) |
| `default_on` | boolean | 0 | Is default combination |
| `minimal_quantity` | integer | 1 | Min order quantity |
| `available_date` | date | NULL | Availability date |

**READONLY (NIE wysyłać w POST/PUT!):**

- `id` (AUTO-INCREMENT)
- `quantity` (managed by stock_available)
- `date_add` (AUTO-GENERATED)
- `date_upd` (AUTO-GENERATED)

### 2.4 Associations Structure

**product_option_values (atrybuty):**

```xml
<associations>
  <product_option_values nodeType="product_option_value" api="product_option_values">
    <product_option_value>
      <id><![CDATA[10]]></id>  <!-- id_attribute (np. Red) -->
    </product_option_value>
    <product_option_value>
      <id><![CDATA[20]]></id>  <!-- id_attribute (np. Size M) -->
    </product_option_value>
  </product_option_values>
</associations>
```

**⚠️ UWAGA:** Musisz wcześniej utworzyć `attribute_group` + `attribute` (lub użyć istniejących!)

**images (zdjęcia wariantu):**

```xml
<associations>
  <images>
    <image>
      <id><![CDATA[123]]></id>  <!-- id_image -->
    </image>
  </images>
</associations>
```

---

## 🔧 3. PRZYKŁADY XML - OPERACJE CRUD

### 3.1 CREATE Combination (POST)

**Endpoint:** `POST /api/combinations`

**XML Payload:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <combination>
    <!-- REQUIRED: Parent product -->
    <id_product><![CDATA[123]]></id_product>

    <!-- IDENTIFIERS -->
    <reference><![CDATA[TSHIRT-RED-M]]></reference>
    <ean13><![CDATA[1234567890123]]></ean13>
    <mpn><![CDATA[MPN-RED-M]]></mpn>
    <supplier_reference><![CDATA[SUPP-RED-M]]></supplier_reference>

    <!-- PRICING (IMPACT on base product price!) -->
    <price><![CDATA[10.000000]]></price>              <!-- Add 10 PLN to base price -->
    <wholesale_price><![CDATA[5.000000]]></wholesale_price>
    <ecotax><![CDATA[0.000000]]></ecotax>
    <unit_price_impact><![CDATA[0.000000]]></unit_price_impact>

    <!-- PHYSICAL -->
    <weight><![CDATA[0.500000]]></weight>             <!-- Add 0.5kg to base weight -->

    <!-- STOCK & AVAILABILITY -->
    <minimal_quantity><![CDATA[1]]></minimal_quantity>
    <low_stock_threshold><![CDATA[5]]></low_stock_threshold>
    <low_stock_alert><![CDATA[1]]></low_stock_alert>
    <default_on><![CDATA[0]]></default_on>            <!-- Not default -->
    <available_date><![CDATA[2025-01-01]]></available_date>

    <!-- LOCATION -->
    <location><![CDATA[Warehouse A, Shelf 5]]></location>

    <!-- REQUIRED: Associations (at least 1 attribute!) -->
    <associations>
      <product_option_values nodeType="product_option_value" api="product_option_values">
        <!-- Attribute 1: Color = Red -->
        <product_option_value>
          <id><![CDATA[10]]></id>
        </product_option_value>
        <!-- Attribute 2: Size = M -->
        <product_option_value>
          <id><![CDATA[21]]></id>
        </product_option_value>
      </product_option_values>

      <!-- OPTIONAL: Images for this combination -->
      <images>
        <image>
          <id><![CDATA[456]]></id>
        </image>
        <image>
          <id><![CDATA[457]]></id>
        </image>
      </images>
    </associations>
  </combination>
</prestashop>
```

**Response (201 Created):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <combination id="789" xlink:href="https://example.com/api/combinations/789">
    <id><![CDATA[789]]></id>
    <id_product><![CDATA[123]]></id_product>
    <reference><![CDATA[TSHIRT-RED-M]]></reference>
    <!-- ... rest of fields ... -->
  </combination>
</prestashop>
```

**⚠️ POST-CREATE ACTIONS:**

1. ✅ PrestaShop AUTO-tworzy `ps_stock_available` (quantity = 0)
2. ✅ Jeśli to pierwszy combination → AUTO-ustawia `default_on = 1`
3. ⚠️ MUSISZ ręcznie zaktualizować stock przez `/api/stock_availables/{id}`

### 3.2 UPDATE Combination (PUT - Full)

**⚠️ WARNING:** PUT wymaga WSZYSTKICH pól! Brakujące pola = NULL!

**Endpoint:** `PUT /api/combinations/{id}`

**Workflow:**

1. Pobierz current state: `GET /api/combinations/{id}`
2. Modify XML
3. Remove readonly fields
4. Send PUT

**Example (PHP):**

```php
// 1. Fetch current state
$response = Http::withBasicAuth($apiKey, '')
    ->get("https://example.com/api/combinations/789");

$xml = simplexml_load_string($response->body());

// 2. Modify fields
$xml->combination->reference = 'TSHIRT-RED-M-V2';
$xml->combination->price = 15.000000; // New price impact

// 3. Remove readonly
unset($xml->combination->id);
unset($xml->combination->date_add);
unset($xml->combination->date_upd);

// 4. PUT updated XML
$response = Http::withBasicAuth($apiKey, '')
    ->withHeaders(['Content-Type' => 'application/xml'])
    ->put("https://example.com/api/combinations/789", [
        'body' => $xml->asXML()
    ]);
```

### 3.3 PARTIAL UPDATE (PATCH - Recommended!)

**✅ ZALECANE:** PATCH dla update tylko zmienionych pól

**Endpoint:** `PATCH /api/combinations/{id}`

**XML Payload (minimal):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <combination>
    <id><![CDATA[789]]></id>

    <!-- Only changed fields -->
    <price><![CDATA[15.000000]]></price>
    <ean13><![CDATA[9876543210987]]></ean13>
  </combination>
</prestashop>
```

**PHP Example:**

```php
function patchCombination(int $id, array $changes): void
{
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>');
    $combination = $xml->addChild('combination');
    $combination->addChild('id', $id);

    foreach ($changes as $field => $value) {
        $combination->addChild($field, htmlspecialchars($value));
    }

    Http::withBasicAuth($apiKey, '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->patch("https://example.com/api/combinations/{$id}", [
            'body' => $xml->asXML()
        ]);
}

// Usage
patchCombination(789, [
    'price' => 15.00,
    'ean13' => '9876543210987'
]);
```

### 3.4 DELETE Combination

**Endpoint:** `DELETE /api/combinations/{id}`

**Request:**

```http
DELETE /api/combinations/789 HTTP/1.1
Host: example.com
Authorization: Basic [base64(api_key:)]
```

**PHP Example:**

```php
Http::withBasicAuth($apiKey, '')
    ->delete("https://example.com/api/combinations/789");
```

**⚠️ CASCADE EFFECTS:**

PrestaShop automatycznie usuwa:
- ✅ `ps_product_attribute_combination` (attribute links)
- ✅ `ps_stock_available` (stock records)
- ✅ `ps_product_attribute_image` (image associations)
- ⚠️ Actual images (`ps_image`) **NIE są usuwane** (mogą być współdzielone!)

### 3.5 UPDATE Stock

**Endpoint:** `PATCH /api/stock_availables/{id}`

**Workflow:**

1. Find stock ID:
```php
GET /api/stock_availables?filter[id_product]=[123]&filter[id_product_attribute]=[789]&filter[id_shop]=[1]
```

2. PATCH quantity:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <stock_available>
    <id><![CDATA[456]]></id>
    <quantity><![CDATA[50]]></quantity>
  </stock_available>
</prestashop>
```

**PHP Helper:**

```php
function updateCombinationStock(int $productId, int $combinationId, int $shopId, int $quantity): void
{
    // 1. Find stock_available ID
    $response = Http::withBasicAuth($apiKey, '')
        ->get("https://example.com/api/stock_availables", [
            'filter[id_product]' => "[{$productId}]",
            'filter[id_product_attribute]' => "[{$combinationId}]",
            'filter[id_shop]' => "[{$shopId}]",
            'display' => '[id]',
            'output_format' => 'JSON'
        ]);

    $stockId = $response->json()['stock_availables'][0]['id'] ?? null;

    if (!$stockId) {
        throw new \Exception("Stock not found for combination {$combinationId}");
    }

    // 2. PATCH quantity
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>');
    $stock = $xml->addChild('stock_available');
    $stock->addChild('id', $stockId);
    $stock->addChild('quantity', $quantity);

    Http::withBasicAuth($apiKey, '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->patch("https://example.com/api/stock_availables/{$stockId}", [
            'body' => $xml->asXML()
        ]);
}

// Usage
updateCombinationStock(123, 789, 1, 50); // Set to 50 units
```

### 3.6 ADD Images to Combination

**Endpoint:** `POST /api/images/products/{id_product}/{id_combination}`

**Request (multipart/form-data):**

```http
POST /api/images/products/123/789 HTTP/1.1
Host: example.com
Authorization: Basic [base64(api_key:)]
Content-Type: multipart/form-data; boundary=----Boundary

------Boundary
Content-Disposition: form-data; name="image"; filename="red-m.jpg"
Content-Type: image/jpeg

[binary image data]
------Boundary--
```

**PHP Example:**

```php
use Illuminate\Support\Facades\Http;

function addCombinationImage(int $productId, int $combinationId, string $imagePath): int
{
    $response = Http::withBasicAuth($apiKey, '')
        ->attach('image', file_get_contents($imagePath), basename($imagePath))
        ->post("https://example.com/api/images/products/{$productId}/{$combinationId}");

    if (!$response->successful()) {
        throw new \Exception("Image upload failed: " . $response->body());
    }

    // Parse response to get image ID
    $xml = simplexml_load_string($response->body());
    $imageId = (int) $xml->image->id;

    return $imageId;
}

// Usage
$imageId = addCombinationImage(123, 789, storage_path('app/images/red-m.jpg'));
```

**⚠️ UWAGA:** Zdjęcia dodane do combination są **AUTOMATYCZNIE linkowane** przez PrestaShop

---

## 🔄 4. MAPOWANIE PPM ↔ PRESTASHOP

### 4.1 Model Mapping Table

| PPM Model | PPM Field | PrestaShop Table | PrestaShop Field | Notes |
|-----------|-----------|------------------|------------------|-------|
| **ProductVariant** | `id` | `ps_product_attribute` | `id_product_attribute` | Primary key mapping |
| | `product_id` | | `id_product` | Parent product link |
| | `sku` | | `reference` | ⚠️ SKU-first! Primary identifier |
| | `name` | - | - | ⚠️ Generated from attributes (not stored in PS) |
| | `is_active` | | - | ⚠️ No direct field (use quantity > 0 || available_date) |
| | `is_default` | | `default_on` | Default combination flag |
| | `position` | | - | ⚠️ No direct equivalent |
| **VariantAttribute** | `variant_id` | `ps_product_attribute_combination` | `id_product_attribute` | Link to combination |
| | `attribute_type_id` | `ps_attribute` | `id_attribute_group` | Via ps_attribute lookup |
| | `value` | `ps_attribute_lang` | `name` | Attribute value text |
| | `color_hex` | `ps_attribute` | `color` | Hex color (if group_type=color) |
| **VariantPrice** | `variant_id` | `ps_product_attribute` | `id_product_attribute` | Link |
| | `price_group_id` | - | - | ⚠️ PPM-only (no PS equivalent) |
| | `price` | | `price` | ⚠️ IMPACT value (not absolute!) |
| | `special_price` | `ps_specific_price` | `price` | Via separate table |
| | `special_price_from` | | `from` | Promo start date |
| | `special_price_to` | | `to` | Promo end date |
| **VariantStock** | `variant_id` | `ps_stock_available` | `id_product_attribute` | Link |
| | `warehouse_id` | | `id_shop` | ⚠️ Map warehouse → shop |
| | `quantity` | | `quantity` | Total stock |
| | `reserved` | - | - | ⚠️ PPM-only (no PS native field) |
| **VariantImage** | `variant_id` | `ps_product_attribute_image` | `id_product_attribute` | Link |
| | `image_id` | | `id_image` | Image reference |
| | `position` | `ps_image` | `position` | Display order |
| | `is_cover` | | `cover` | Cover image flag |

### 4.2 Field Transformation Rules

#### 4.2.1 Price Transformation

**PPM → PrestaShop:**

```php
// PPM stores ABSOLUTE price per variant
// PrestaShop needs PRICE IMPACT (differential)

function transformPriceForPrestaShop(ProductVariant $variant, Product $product): float
{
    $basePrice = $product->getPrice('detaliczna'); // Base product price
    $variantPrice = $variant->getPriceForGroup('detaliczna'); // Variant absolute price

    $priceImpact = $variantPrice - $basePrice; // Calculate differential

    return round($priceImpact, 6); // PrestaShop uses 6 decimal places
}

// Example:
// Product base = 100 PLN
// Variant (XL) = 120 PLN
// PrestaShop price = +20 PLN
```

**PrestaShop → PPM:**

```php
function transformPriceFromPrestaShop(array $psProduct, array $psCombination): float
{
    $basePrice = (float) $psProduct['price'];
    $priceImpact = (float) $psCombination['price'];

    return $basePrice + $priceImpact; // Absolute variant price
}

// Example:
// PrestaShop product.price = 100 PLN
// PrestaShop combination.price = +20 PLN
// PPM variant price = 120 PLN
```

#### 4.2.2 SKU Generation

**PPM → PrestaShop:**

```php
function generateVariantSku(Product $product, ProductVariant $variant): string
{
    // Option 1: Use existing variant SKU
    if ($variant->sku) {
        return $variant->sku;
    }

    // Option 2: Generate from product SKU + attributes
    $attributes = $variant->attributes->pluck('value')->join('-');
    return strtoupper("{$product->sku}-{$attributes}");
}

// Example:
// Product SKU = "TSHIRT"
// Attributes = ["Red", "M"]
// Variant SKU = "TSHIRT-RED-M"
```

#### 4.2.3 Attribute Mapping

**PPM → PrestaShop (attribute IDs):**

```php
function mapAttributesToPrestaShop(ProductVariant $variant, int $shopId): array
{
    $attributeIds = [];

    foreach ($variant->attributes as $variantAttribute) {
        // Find PrestaShop attribute by value
        $mapping = DB::table('prestashop_attribute_mappings')
            ->where('ppm_attribute_type_id', $variantAttribute->attribute_type_id)
            ->where('ppm_value', $variantAttribute->value)
            ->where('shop_id', $shopId)
            ->first();

        if (!$mapping) {
            throw new \Exception("No PrestaShop mapping for attribute: {$variantAttribute->value}");
        }

        $attributeIds[] = $mapping->prestashop_attribute_id;
    }

    return $attributeIds;
}
```

**PrestaShop → PPM:**

```php
function mapAttributesFromPrestaShop(array $psCombination, int $shopId): array
{
    $attributes = [];

    foreach ($psCombination['associations']['product_option_values'] as $psAttribute) {
        $attributeId = (int) $psAttribute['id'];

        // Lookup in mapping table
        $mapping = DB::table('prestashop_attribute_mappings')
            ->where('prestashop_attribute_id', $attributeId)
            ->where('shop_id', $shopId)
            ->first();

        if (!$mapping) {
            throw new \Exception("No PPM mapping for PrestaShop attribute ID: {$attributeId}");
        }

        $attributes[] = [
            'attribute_type_id' => $mapping->ppm_attribute_type_id,
            'value' => $mapping->ppm_value,
            'color_hex' => $mapping->color_hex
        ];
    }

    return $attributes;
}
```

#### 4.2.4 Stock Aggregation

**PPM → PrestaShop (warehouse to shop):**

```php
function transformStockForPrestaShop(ProductVariant $variant, int $shopId): int
{
    // Find warehouse mapped to this shop
    $warehouseMapping = DB::table('shop_mappings')
        ->where('shop_id', $shopId)
        ->where('mapping_type', 'warehouse')
        ->first();

    if (!$warehouseMapping) {
        // No mapping = sum all warehouses
        return $variant->stock->sum('quantity') - $variant->stock->sum('reserved');
    }

    // Use mapped warehouse only
    $stock = $variant->stock()
        ->where('warehouse_id', $warehouseMapping->ppm_value)
        ->first();

    return $stock ? ($stock->quantity - $stock->reserved) : 0;
}
```

**PrestaShop → PPM:**

```php
function transformStockFromPrestaShop(array $psStockAvailable, int $warehouseId): array
{
    return [
        'warehouse_id' => $warehouseId,
        'quantity' => (int) $psStockAvailable['quantity'],
        'reserved' => 0 // PrestaShop doesn't track reservations
    ];
}
```

### 4.3 Database Schema - Mapping Tables

**Recommended tables:**

```sql
-- Attribute value mappings
CREATE TABLE `prestashop_attribute_mappings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT UNSIGNED NOT NULL,
  `ppm_attribute_type_id` INT UNSIGNED NOT NULL,
  `ppm_value` VARCHAR(255) NOT NULL,
  `prestashop_attribute_id` INT UNSIGNED NOT NULL,
  `color_hex` VARCHAR(7) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `ppm_mapping` (`shop_id`, `ppm_attribute_type_id`, `ppm_value`),
  UNIQUE KEY `ps_mapping` (`shop_id`, `prestashop_attribute_id`)
);

-- Variant sync status
CREATE TABLE `variant_sync_status` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `variant_id` INT UNSIGNED NOT NULL,
  `shop_id` INT UNSIGNED NOT NULL,
  `prestashop_combination_id` INT UNSIGNED NULL,
  `sync_status` ENUM('pending', 'syncing', 'synced', 'error') DEFAULT 'pending',
  `last_sync_at` TIMESTAMP NULL,
  `checksum` VARCHAR(32) NOT NULL COMMENT 'MD5 of variant data for change detection',
  `error_message` TEXT NULL,
  `retry_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `variant_shop` (`variant_id`, `shop_id`),
  KEY `shop_status` (`shop_id`, `sync_status`)
);
```

---

## ⚙️ 5. STRATEGIA SYNCHRONIZACJI

### 5.1 Sync Workflow Overview

```
┌─────────────────────────────────────────────────────────────┐
│ FAZA 1: Preparation                                         │
│ ├─ Validate product exists in PrestaShop                    │
│ ├─ Ensure product_type = 'combinations'                     │
│ ├─ Map attributes (PPM → PrestaShop IDs)                    │
│ └─ Calculate checksum for change detection                  │
└─────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────┐
│ FAZA 2: Combination Sync (CREATE or UPDATE)                │
│ ├─ Check if combination exists (by prestashop_combination_id)│
│ ├─ IF NOT EXISTS: POST /api/combinations                   │
│ ├─ IF EXISTS: PATCH /api/combinations/{id}                 │
│ └─ Update variant_sync_status                              │
└─────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────┐
│ FAZA 3: Stock Sync                                         │
│ ├─ Find stock_available ID (by product + combination + shop)│
│ ├─ Calculate stock (warehouse mapping)                     │
│ ├─ PATCH /api/stock_availables/{id}                       │
│ └─ Log stock sync operation                               │
└─────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────┐
│ FAZA 4: Image Sync (if changed)                           │
│ ├─ Compare current images vs PrestaShop                    │
│ ├─ DELETE removed images                                   │
│ ├─ POST new images                                         │
│ └─ UPDATE image associations                               │
└─────────────────────────────────────────────────────────────┘
                        ↓
                  ✅ SYNC COMPLETE
```

### 5.2 Change Detection Strategy

**Checksum-Based Detection:**

```php
function calculateVariantChecksum(ProductVariant $variant): string
{
    $data = [
        'sku' => $variant->sku,
        'name' => $variant->name,
        'is_default' => $variant->is_default,
        'attributes' => $variant->attributes->map(fn($a) => [
            'type' => $a->attribute_type_id,
            'value' => $a->value
        ])->toArray(),
        'prices' => $variant->prices->map(fn($p) => [
            'group' => $p->price_group_id,
            'price' => $p->price
        ])->toArray(),
        'stock' => $variant->stock->sum('quantity'),
        'images' => $variant->images->pluck('id')->toArray(),
        'updated_at' => $variant->updated_at->timestamp
    ];

    return md5(json_encode($data));
}
```

**Sync Decision Logic:**

```php
function shouldSyncVariant(ProductVariant $variant, int $shopId): bool
{
    $syncStatus = VariantSyncStatus::where('variant_id', $variant->id)
        ->where('shop_id', $shopId)
        ->first();

    if (!$syncStatus) {
        return true; // Never synced
    }

    $currentChecksum = calculateVariantChecksum($variant);

    if ($syncStatus->checksum !== $currentChecksum) {
        return true; // Data changed
    }

    if ($syncStatus->sync_status === 'error' && $syncStatus->retry_count < 3) {
        return true; // Retry failed sync
    }

    return false; // No sync needed
}
```

### 5.3 Conflict Resolution Strategies

**Scenario 1: SKU Conflict (variant exists with different attributes)**

```php
function handleSkuConflict(ProductVariant $variant, array $psExisting): string
{
    // Strategy A: Append suffix
    $newSku = $variant->sku . '-V' . time();

    // Strategy B: Use PrestaShop ID as suffix
    $newSku = $variant->sku . '-PS' . $psExisting['id'];

    // Strategy C: Prompt user (recommended for enterprise)
    throw new VariantConflictException(
        "Variant SKU conflict: {$variant->sku} already exists in PrestaShop with different attributes",
        [
            'ppm_variant' => $variant->toArray(),
            'prestashop_variant' => $psExisting,
            'suggested_sku' => $newSku
        ]
    );
}
```

**Scenario 2: Attribute Not Mapped**

```php
function handleUnmappedAttribute(VariantAttribute $attribute, int $shopId): int
{
    // Strategy A: Auto-create in PrestaShop
    $psAttributeId = $this->createPrestaShopAttribute($attribute, $shopId);

    // Strategy B: Use fallback attribute (e.g., "Other")
    $fallbackMapping = DB::table('prestashop_attribute_mappings')
        ->where('shop_id', $shopId)
        ->where('ppm_value', 'Other')
        ->first();

    if ($fallbackMapping) {
        return $fallbackMapping->prestashop_attribute_id;
    }

    // Strategy C: Fail sync (recommended for data integrity)
    throw new UnmappedAttributeException(
        "No PrestaShop mapping for attribute: {$attribute->value}",
        ['attribute' => $attribute->toArray(), 'shop_id' => $shopId]
    );
}
```

**Scenario 3: Stock Mismatch (PPM vs PrestaShop)**

```php
function resolveStockConflict(ProductVariant $variant, array $psStock, string $resolution): int
{
    return match($resolution) {
        'use_ppm' => $this->updatePrestaShopStock($variant, $psStock['id']),
        'use_prestashop' => $this->updatePPMStock($variant, $psStock['quantity']),
        'average' => $this->syncAverageStock($variant, $psStock),
        'manual' => throw new ManualReviewRequiredException('Stock conflict requires manual review'),
    };
}
```

### 5.4 Multi-Shop Sync Strategy

**Sequential vs Parallel:**

```php
// Strategy A: Sequential (safer for shared hosting)
function syncVariantToShops(ProductVariant $variant, array $shopIds): array
{
    $results = [];

    foreach ($shopIds as $shopId) {
        try {
            $this->syncVariantToShop($variant, $shopId);
            $results[$shopId] = 'success';
        } catch (\Exception $e) {
            $results[$shopId] = 'error: ' . $e->getMessage();
        }

        usleep(500000); // 500ms delay (rate limiting)
    }

    return $results;
}

// Strategy B: Parallel (for VPS/dedicated server)
function syncVariantToShopsParallel(ProductVariant $variant, array $shopIds): array
{
    $jobs = [];

    foreach ($shopIds as $shopId) {
        $jobs[] = SyncVariantToShop::dispatch($variant->id, $shopId);
    }

    // Wait for completion
    return Bus::batch($jobs)->dispatch();
}
```

### 5.5 Error Handling & Retry Logic

```php
class VariantSyncService
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function syncWithRetry(ProductVariant $variant, int $shopId): void
    {
        $attempt = 1;

        while ($attempt <= $this->maxRetries) {
            try {
                $this->syncVariant($variant, $shopId);

                // Success - update status
                VariantSyncStatus::updateOrCreate(
                    ['variant_id' => $variant->id, 'shop_id' => $shopId],
                    [
                        'sync_status' => 'synced',
                        'last_sync_at' => now(),
                        'checksum' => calculateVariantChecksum($variant),
                        'error_message' => null,
                        'retry_count' => 0
                    ]
                );

                return; // Success!

            } catch (PrestaShopApiException $e) {
                // Don't retry client errors (4xx)
                if ($e->httpStatus >= 400 && $e->httpStatus < 500) {
                    $this->logError($variant, $shopId, $e, $attempt);
                    throw $e;
                }

                // Retry server errors (5xx)
                if ($attempt >= $this->maxRetries) {
                    $this->logError($variant, $shopId, $e, $attempt);
                    throw $e;
                }

                usleep($this->retryDelay * pow(2, $attempt - 1) * 1000);
                $attempt++;
            }
        }
    }

    protected function logError(ProductVariant $variant, int $shopId, \Exception $e, int $attempt): void
    {
        VariantSyncStatus::updateOrCreate(
            ['variant_id' => $variant->id, 'shop_id' => $shopId],
            [
                'sync_status' => 'error',
                'error_message' => $e->getMessage(),
                'retry_count' => $attempt
            ]
        );

        Log::error('Variant sync failed', [
            'variant_id' => $variant->id,
            'shop_id' => $shopId,
            'attempt' => $attempt,
            'error' => $e->getMessage()
        ]);
    }
}
```

### 5.6 Bulk Operations Strategy

**Batch Sync for Performance:**

```php
class BulkVariantSyncService
{
    protected int $batchSize = 50;
    protected int $batchDelay = 2000; // 2s delay between batches

    public function syncProductVariants(Product $product, int $shopId): array
    {
        $variants = $product->variants()
            ->active()
            ->get();

        $results = [
            'total' => $variants->count(),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($variants->chunk($this->batchSize) as $batch) {
            foreach ($batch as $variant) {
                try {
                    // Check if sync needed
                    if (!shouldSyncVariant($variant, $shopId)) {
                        $results['skipped']++;
                        continue;
                    }

                    // Sync variant
                    $this->variantSyncService->syncWithRetry($variant, $shopId);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Delay between batches (rate limiting)
            if ($variants->count() > $this->batchSize) {
                usleep($this->batchDelay * 1000);
            }
        }

        return $results;
    }
}
```

---

## 🏗️ 6. SERWISY DO IMPLEMENTACJI

### 6.1 Service Architecture

```
app/Services/PrestaShop/Variant/
├── PrestaShopVariantSyncService.php       # Main orchestration
├── VariantTransformer.php                 # Data transformation PPM ↔ PS
├── VariantConflictResolver.php           # Conflict resolution strategies
├── VariantStockSyncService.php           # Stock-specific sync
├── VariantImageSyncService.php           # Image-specific sync
├── VariantImportService.php              # Import PS → PPM
├── VariantExportService.php              # Export PPM → PS
└── Mappers/
    ├── VariantAttributeMapper.php        # Attribute mapping
    └── VariantPriceMapper.php            # Price transformation
```

### 6.2 PrestaShopVariantSyncService

**Purpose:** Główny serwis orchestracji synchronizacji wariantów

**Responsibilities:**
- Koordynacja procesu sync (4-fazowy workflow)
- Change detection (checksum-based)
- Error handling i retry logic
- Multi-shop sync orchestration
- Logging i monitoring

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class PrestaShopVariantSyncService
{
    public function __construct(
        protected PrestaShopClientFactory $clientFactory,
        protected VariantTransformer $transformer,
        protected VariantAttributeMapper $attributeMapper,
        protected VariantStockSyncService $stockSyncService,
        protected VariantImageSyncService $imageSyncService,
        protected VariantConflictResolver $conflictResolver
    ) {}

    /**
     * Sync single variant to PrestaShop
     */
    public function syncVariant(ProductVariant $variant, PrestaShopShop $shop): bool
    {
        // FAZA 1: Preparation
        $this->validateProduct($variant->product, $shop);
        $mappedAttributes = $this->attributeMapper->mapToPrestaShop($variant, $shop);

        // FAZA 2: Combination Sync
        $combinationId = $this->syncCombination($variant, $shop, $mappedAttributes);

        // FAZA 3: Stock Sync
        $this->stockSyncService->syncStock($variant, $combinationId, $shop);

        // FAZA 4: Image Sync
        $this->imageSyncService->syncImages($variant, $combinationId, $shop);

        // Update sync status
        $this->updateSyncStatus($variant, $shop, $combinationId, 'synced');

        return true;
    }

    /**
     * Sync all variants for a product
     */
    public function syncProductVariants(Product $product, PrestaShopShop $shop): array;

    /**
     * Import variant from PrestaShop
     */
    public function importVariant(int $combinationId, PrestaShopShop $shop): ProductVariant;

    /**
     * Delete variant from PrestaShop
     */
    public function deleteVariant(ProductVariant $variant, PrestaShopShop $shop): bool;

    /**
     * Check if variant needs sync (change detection)
     */
    protected function needsSync(ProductVariant $variant, PrestaShopShop $shop): bool;

    /**
     * Calculate checksum for change detection
     */
    protected function calculateChecksum(ProductVariant $variant): string;
}
```

### 6.3 VariantTransformer

**Purpose:** Transformacja danych między PPM a PrestaShop format

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantTransformer
{
    /**
     * Transform PPM variant to PrestaShop XML
     */
    public function toPrestaShopXml(
        ProductVariant $variant,
        array $mappedAttributes,
        PrestaShopShop $shop
    ): string
    {
        // Get blank schema
        $xml = $this->getBlankCombinationXml($shop);

        // Fill basic fields
        $xml->combination->id_product = $variant->product->prestashop_id;
        $xml->combination->reference = $variant->sku;
        $xml->combination->ean13 = $variant->ean13 ?? '';

        // Transform price (IMPACT model!)
        $priceImpact = $this->calculatePriceImpact($variant, $shop);
        $xml->combination->price = number_format($priceImpact, 6, '.', '');

        // Default combination
        $xml->combination->default_on = $variant->is_default ? 1 : 0;

        // Associations (attributes)
        foreach ($mappedAttributes as $attributeId) {
            $attr = $xml->combination->associations->product_option_values->addChild('product_option_value');
            $attr->addChild('id', $attributeId);
        }

        return $xml->asXML();
    }

    /**
     * Transform PrestaShop combination to PPM variant
     */
    public function fromPrestaShop(array $psCombination, Product $product, PrestaShopShop $shop): array
    {
        return [
            'product_id' => $product->id,
            'sku' => $psCombination['reference'],
            'name' => $this->generateVariantName($psCombination, $shop),
            'is_default' => (bool) $psCombination['default_on'],
            'prestashop_combination_id' => $psCombination['id']
        ];
    }

    /**
     * Calculate price impact (PPM absolute → PS differential)
     */
    protected function calculatePriceImpact(ProductVariant $variant, PrestaShopShop $shop): float;

    /**
     * Generate variant name from attributes
     */
    protected function generateVariantName(array $psCombination, PrestaShopShop $shop): string;
}
```

### 6.4 VariantAttributeMapper

**Purpose:** Mapowanie atrybutów PPM ↔ PrestaShop

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant\Mappers;

class VariantAttributeMapper
{
    /**
     * Map PPM attributes to PrestaShop attribute IDs
     */
    public function mapToPrestaShop(ProductVariant $variant, PrestaShopShop $shop): array
    {
        $attributeIds = [];

        foreach ($variant->attributes as $attribute) {
            $mapping = DB::table('prestashop_attribute_mappings')
                ->where('shop_id', $shop->id)
                ->where('ppm_attribute_type_id', $attribute->attribute_type_id)
                ->where('ppm_value', $attribute->value)
                ->first();

            if (!$mapping) {
                // Handle unmapped attribute
                $attributeIds[] = $this->handleUnmapped($attribute, $shop);
            } else {
                $attributeIds[] = $mapping->prestashop_attribute_id;
            }
        }

        return $attributeIds;
    }

    /**
     * Map PrestaShop attributes to PPM format
     */
    public function mapFromPrestaShop(array $psAttributes, PrestaShopShop $shop): array;

    /**
     * Create mapping for new attribute
     */
    public function createMapping(
        int $ppmAttributeTypeId,
        string $ppmValue,
        int $prestashopAttributeId,
        PrestaShopShop $shop
    ): void;

    /**
     * Handle unmapped attribute (auto-create or fail)
     */
    protected function handleUnmapped(VariantAttribute $attribute, PrestaShopShop $shop): int;
}
```

### 6.5 VariantStockSyncService

**Purpose:** Synchronizacja stanów magazynowych wariantów

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantStockSyncService
{
    /**
     * Sync variant stock to PrestaShop
     */
    public function syncStock(
        ProductVariant $variant,
        int $combinationId,
        PrestaShopShop $shop
    ): void
    {
        // 1. Find stock_available ID
        $stockId = $this->findStockAvailableId(
            $variant->product->prestashop_id,
            $combinationId,
            $shop->id
        );

        if (!$stockId) {
            throw new \Exception("Stock not found for combination {$combinationId}");
        }

        // 2. Calculate total stock (warehouse mapping)
        $quantity = $this->calculateStock($variant, $shop);

        // 3. PATCH stock
        $this->updatePrestaShopStock($stockId, $quantity, $shop);
    }

    /**
     * Import stock from PrestaShop
     */
    public function importStock(array $psStock, ProductVariant $variant, PrestaShopShop $shop): void;

    /**
     * Find stock_available ID for combination
     */
    protected function findStockAvailableId(int $productId, int $combinationId, int $shopId): ?int;

    /**
     * Calculate stock for PrestaShop (warehouse aggregation)
     */
    protected function calculateStock(ProductVariant $variant, PrestaShopShop $shop): int;
}
```

### 6.6 VariantImageSyncService

**Purpose:** Synchronizacja zdjęć wariantów

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantImageSyncService
{
    /**
     * Sync variant images to PrestaShop
     */
    public function syncImages(
        ProductVariant $variant,
        int $combinationId,
        PrestaShopShop $shop
    ): void
    {
        // 1. Get current PrestaShop images
        $psImages = $this->getPrestaShopImages($combinationId, $shop);

        // 2. Compare with PPM images
        $toAdd = $this->findImagesToAdd($variant->images, $psImages);
        $toRemove = $this->findImagesToRemove($variant->images, $psImages);

        // 3. Delete removed images
        foreach ($toRemove as $imageId) {
            $this->deleteImage($variant->product->prestashop_id, $imageId, $shop);
        }

        // 4. Add new images
        foreach ($toAdd as $image) {
            $this->addImage($variant->product->prestashop_id, $combinationId, $image, $shop);
        }

        // 5. Update cover image
        $this->updateCoverImage($variant, $combinationId, $shop);
    }

    /**
     * Upload single image to PrestaShop
     */
    public function addImage(
        int $productId,
        int $combinationId,
        VariantImage $image,
        PrestaShopShop $shop
    ): int;

    /**
     * Delete image from PrestaShop
     */
    protected function deleteImage(int $productId, int $imageId, PrestaShopShop $shop): void;

    /**
     * Set cover image for variant
     */
    protected function updateCoverImage(ProductVariant $variant, int $combinationId, PrestaShopShop $shop): void;
}
```

### 6.7 VariantImportService

**Purpose:** Import wariantów z PrestaShop do PPM

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantImportService
{
    /**
     * Import single variant from PrestaShop
     */
    public function importVariant(int $combinationId, PrestaShopShop $shop): ProductVariant
    {
        // 1. Fetch from PrestaShop API
        $psCombination = $this->fetchCombination($combinationId, $shop);

        // 2. Find or create parent product
        $product = $this->findOrCreateProduct($psCombination['id_product'], $shop);

        // 3. Transform data
        $variantData = $this->transformer->fromPrestaShop($psCombination, $product, $shop);

        // 4. Create/update variant
        $variant = ProductVariant::updateOrCreate(
            ['prestashop_combination_id' => $combinationId],
            $variantData
        );

        // 5. Import attributes
        $this->importAttributes($variant, $psCombination, $shop);

        // 6. Import prices
        $this->importPrices($variant, $psCombination, $shop);

        // 7. Import stock
        $this->importStock($variant, $psCombination, $shop);

        return $variant;
    }

    /**
     * Import all variants for a product
     */
    public function importProductVariants(int $productId, PrestaShopShop $shop): array;

    /**
     * Bulk import variants (with pagination)
     */
    public function bulkImport(PrestaShopShop $shop, int $limit = 100): array;
}
```

### 6.8 VariantExportService

**Purpose:** Export wariantów z PPM do PrestaShop

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantExportService
{
    /**
     * Export single variant to PrestaShop
     */
    public function exportVariant(ProductVariant $variant, PrestaShopShop $shop): int
    {
        // 1. Validate product exists in PrestaShop
        if (!$variant->product->prestashop_id) {
            throw new \Exception("Parent product not synced to PrestaShop");
        }

        // 2. Map attributes
        $mappedAttributes = $this->attributeMapper->mapToPrestaShop($variant, $shop);

        // 3. Transform to XML
        $xml = $this->transformer->toPrestaShopXml($variant, $mappedAttributes, $shop);

        // 4. POST to PrestaShop
        $client = $this->clientFactory->create($shop);
        $response = $client->createCombination($xml);

        $combinationId = $response['id'];

        // 5. Update sync status
        VariantSyncStatus::updateOrCreate(
            ['variant_id' => $variant->id, 'shop_id' => $shop->id],
            ['prestashop_combination_id' => $combinationId]
        );

        return $combinationId;
    }

    /**
     * Export all variants for a product
     */
    public function exportProductVariants(Product $product, PrestaShopShop $shop): array;

    /**
     * Bulk export variants
     */
    public function bulkExport(array $variantIds, PrestaShopShop $shop): array;
}
```

### 6.9 VariantConflictResolver

**Purpose:** Rozwiązywanie konfliktów synchronizacji

**Key Methods:**

```php
namespace App\Services\PrestaShop\Variant;

class VariantConflictResolver
{
    /**
     * Resolve SKU conflict
     */
    public function resolveSkuConflict(
        ProductVariant $variant,
        array $psExisting,
        string $strategy = 'append_suffix'
    ): string;

    /**
     * Resolve unmapped attribute
     */
    public function resolveUnmappedAttribute(
        VariantAttribute $attribute,
        PrestaShopShop $shop,
        string $strategy = 'auto_create'
    ): int;

    /**
     * Resolve stock mismatch
     */
    public function resolveStockConflict(
        ProductVariant $variant,
        array $psStock,
        string $strategy = 'use_ppm'
    ): int;

    /**
     * Resolve price mismatch
     */
    public function resolvePriceConflict(
        ProductVariant $variant,
        array $psCombination,
        string $strategy = 'use_ppm'
    ): float;
}
```

---

## 📈 7. PERFORMANCE & BEST PRACTICES

### 7.1 Rate Limiting Recommendations

**Hostido Shared Hosting:**
- Delay between requests: 500-1000ms
- Batch size: 25-50 combinations
- Cooling period: 2s between batches
- Max concurrent: 1 request (sequential only)

**VPS/Dedicated Server:**
- Delay between requests: 100-300ms
- Batch size: 100-200 combinations
- Cooling period: 1s between batches
- Max concurrent: 3-5 requests (parallel)

### 7.2 Caching Strategy

```php
// Cache attribute mappings (1 hour)
Cache::remember("ps_attr_mappings_{$shopId}", 3600, function () use ($shopId) {
    return DB::table('prestashop_attribute_mappings')
        ->where('shop_id', $shopId)
        ->get();
});

// Cache product PrestaShop IDs (24 hours)
Cache::remember("product_ps_id_{$productId}", 86400, function () use ($productId) {
    return Product::find($productId)->prestashop_id;
});

// Invalidate cache after sync
Cache::forget("variant_sync_status_{$variantId}_{$shopId}");
```

### 7.3 Database Optimization

**Indexes:**

```sql
-- Variant sync status
CREATE INDEX idx_variant_shop_status ON variant_sync_status(shop_id, sync_status);
CREATE INDEX idx_variant_checksum ON variant_sync_status(variant_id, checksum);

-- Attribute mappings
CREATE INDEX idx_ppm_mapping ON prestashop_attribute_mappings(shop_id, ppm_attribute_type_id, ppm_value);
CREATE INDEX idx_ps_mapping ON prestashop_attribute_mappings(shop_id, prestashop_attribute_id);
```

### 7.4 Monitoring & Logging

**Key Metrics:**

```php
// Sync success rate
$successRate = VariantSyncStatus::where('shop_id', $shopId)
    ->where('sync_status', 'synced')
    ->count() / VariantSyncStatus::where('shop_id', $shopId)->count() * 100;

// Average sync time
$avgSyncTime = SyncLog::where('operation', 'variant_sync')
    ->where('shop_id', $shopId)
    ->avg('execution_time_ms');

// Error rate
$errorRate = VariantSyncStatus::where('shop_id', $shopId)
    ->where('sync_status', 'error')
    ->count() / VariantSyncStatus::where('shop_id', $shopId)->count() * 100;
```

---

## ⚠️ 8. KNOWN ISSUES & LIMITATIONS

### 8.1 PrestaShop Limitations

1. **Price Impact Model:**
   - PrestaShop nie wspiera absolute prices dla combinations
   - Zawsze wymaga DIFFERENTIAL (impact na base price)
   - Komplikuje mapowanie z PPM (absolute model)

2. **Attribute System:**
   - Brak API dla bulk attribute creation
   - Każdy attribute value wymaga osobnego POST
   - Slow performance dla dużych katalogów

3. **Stock Management:**
   - `ps_product_attribute.quantity` deprecated ale istnieje
   - Faktyczny stock TYLKO przez `stock_available`
   - Auto-generacja może powodować orphaned records

4. **Image Upload:**
   - Brak batch upload API
   - Każde zdjęcie = osobny POST
   - Rate limiting issues na shared hosting

### 8.2 PPM-Specific Challenges

1. **Multiple Price Groups:**
   - PrestaShop nie wspiera natywnie multiple price groups
   - Wymaga użycia `specific_price` dla każdej grupy
   - Komplikuje sync (3x więcej API calls)

2. **Warehouse Mapping:**
   - PrestaShop używa shops, PPM używa warehouses
   - Wymaga mapping table
   - Multi-warehouse → single shop = stock aggregation

3. **Reserved Stock:**
   - PPM trackuje reservations, PrestaShop nie
   - Loss of reservation data przy sync PS → PPM
   - Wymaga oddzielnej tabeli dla tracking

---

## 📝 9. RECOMMENDED IMPLEMENTATION ORDER

### Phase 1: Foundation (Week 1-2)

1. ✅ Create database tables (`variant_sync_status`, `prestashop_attribute_mappings`)
2. ✅ Implement `VariantTransformer` (data transformation)
3. ✅ Implement `VariantAttributeMapper` (attribute mapping)
4. ✅ Basic attribute mapping UI (admin panel)

### Phase 2: Core Sync (Week 3-4)

1. ✅ Implement `PrestaShopVariantSyncService` (main orchestration)
2. ✅ Implement CREATE combination (POST API)
3. ✅ Implement UPDATE combination (PATCH API)
4. ✅ Change detection (checksum-based)
5. ✅ Error handling & retry logic

### Phase 3: Advanced Sync (Week 5-6)

1. ✅ Implement `VariantStockSyncService` (stock sync)
2. ✅ Implement `VariantImageSyncService` (image sync)
3. ✅ Multi-shop sync orchestration
4. ✅ Conflict resolution UI

### Phase 4: Import/Export (Week 7-8)

1. ✅ Implement `VariantImportService` (PS → PPM)
2. ✅ Implement `VariantExportService` (PPM → PS)
3. ✅ Bulk operations (batch sync)
4. ✅ Progress tracking UI

### Phase 5: Testing & Optimization (Week 9-10)

1. ✅ Unit tests (transformation, mapping)
2. ✅ Integration tests (API calls)
3. ✅ Performance optimization (caching, indexing)
4. ✅ Monitoring & logging setup

---

## 📚 10. REFERENCES

### Documentation Sources

**PrestaShop Official:**
- [Web Services API Docs](https://devdocs.prestashop-project.org/8/webservice/)
- [Database Structure Reference](https://devdocs.prestashop-project.org/8/development/database/)
- [Create Product A-Z Tutorial](https://github.com/prestashop/docs/blob/9.x/webservice/tutorials/create-product-az.md)
- [Combinations Resource Schema](https://github.com/prestashop/docs/blob/9.x/webservice/resources/combinations.md)

**Context7 Library:**
- Library ID: `/prestashop/docs`
- Snippets: 3289
- Trust Score: 8.2

**PPM Internal:**
- `_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md`
- `_DOCS/PRESTASHOP_API_REFERENCE.md`
- `.claude/skills/prestashop-api-integration/`
- `.claude/skills/prestashop-database-structure/`

---

## ✅ PODSUMOWANIE

### Co zostało przeanalizowane:

1. ✅ **Struktura PrestaShop:**
   - Tabele `ps_product_attribute`, `ps_product_attribute_combination`, `ps_stock_available`
   - Price impact model (differential, nie absolute)
   - Attribute system (groups + values)

2. ✅ **API Endpoints:**
   - `/api/combinations` (CRUD operations)
   - `/api/stock_availables` (stock updates)
   - `/api/images/products/{id}/{combination}` (image upload)

3. ✅ **Wymagane Pola:**
   - MANDATORY: `id_product`, `associations.product_option_values`
   - RECOMMENDED: `reference`, `ean13`, `price`, `default_on`
   - READONLY: `id`, `date_add`, `date_upd`

4. ✅ **Przykłady XML:**
   - CREATE (POST)
   - UPDATE (PUT/PATCH)
   - DELETE
   - Stock update
   - Image upload

5. ✅ **Mapowanie PPM ↔ PS:**
   - Field mapping table
   - Price transformation (absolute → differential)
   - SKU generation
   - Attribute mapping
   - Stock aggregation

6. ✅ **Strategia Sync:**
   - 4-fazowy workflow (Preparation → Combination → Stock → Images)
   - Change detection (checksum)
   - Conflict resolution strategies
   - Multi-shop orchestration
   - Error handling & retry

7. ✅ **Serwisy:**
   - `PrestaShopVariantSyncService` (orchestration)
   - `VariantTransformer` (data transformation)
   - `VariantAttributeMapper` (attribute mapping)
   - `VariantStockSyncService` (stock sync)
   - `VariantImageSyncService` (image sync)
   - `VariantImportService` (PS → PPM)
   - `VariantExportService` (PPM → PS)
   - `VariantConflictResolver` (conflict handling)

### Następne Kroki:

1. **Review raportu** z user
2. **Implementacja Phase 1** (Foundation)
3. **Testy mappingu** atrybutów
4. **Proof of concept** - sync 1 wariantu do test shop
5. **Iterate** na podstawie feedback

---

**RAPORT ZAKOŃCZONY** ✅

_Generated by: prestashop-api-expert agent_
_Date: 2025-12-03_
_Context7 Library: /prestashop/docs (3289 snippets)_
