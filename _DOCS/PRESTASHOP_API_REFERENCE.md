# PrestaShop API Reference

**Version:** PrestaShop 8.x / 9.x
**Type:** RESTful Web Services API
**Format:** XML (input), XML/JSON (output)
**Authentication:** API Key (HTTP Basic Auth)
**Source:** Context7 + DevDocs PrestaShop Project

---

## 📋 Spis Treści

1. [Wprowadzenie](#wprowadzenie)
2. [Architektura API](#architektura-api)
3. [Autentykacja](#autentykacja)
4. [CQRS Pattern](#cqrs-pattern)
5. [HTTP Methods](#http-methods)
6. [Query Parameters](#query-parameters)
7. [Resource Schemas](#resource-schemas)
8. [Cache Optimization](#cache-optimization)
9. [Dostępne Resources](#dostepne-resources)
10. [Przykłady Użycia](#przyklady-uzycia)
11. [Błędy i Obsługa](#bledy-i-obsluga)
12. [Best Practices](#best-practices)
13. [Pułapki i Ostrzeżenia](#pulapki-i-ostrzezenia)

---

## Wprowadzenie

PrestaShop Web Services API to RESTful API umożliwiające integrację z PrestaShop poprzez HTTP requests. API używa **Command Query Responsibility Segregation (CQRS)** pattern i wymaga **XML jako format input** dla operacji modyfikujących dane.

### Kluczowe Cechy

- **RESTful:** HTTP methods (GET, POST, PUT, PATCH, DELETE)
- **XML-First:** XML wymagany dla POST/PUT/PATCH (JSON tylko dla GET)
- **Stateless:** Każde request niezależne (API key w każdym)
- **Hypermedia:** Linki do powiązanych resources w response
- **Versionless:** Brak API versioning - zmiany backward-compatible
- **Rate Limiting:** Brak oficjalnego limitu, zależy od hostingu

---

## Architektura API

### CQRS (Command Query Responsibility Segregation)

PrestaShop 8.x/9.x używa CQRS pattern do separacji operacji read (Query) i write (Command).

**Query Operations:**
- `CQRSGet` - Pobieranie pojedynczego resource
- `CQRSGetList` - Pobieranie listy resources

**Command Operations:**
- `CQRSCreate` - Tworzenie nowego resource (POST)
- `CQRSUpdate` - Pełna aktualizacja (PUT)
- `CQRSPartialUpdate` - Częściowa aktualizacja (PATCH)
- `CQRSDelete` - Usuwanie resource (DELETE)

```php
// Przykład struktury CQRS w PrestaShop 8.x/9.x
namespace PrestaShopBundle\Api\QueryHandler;

class CQRSGet
{
    public function __construct(
        private readonly string $resource,
        private readonly int $id
    ) {}
}

class CQRSGetList
{
    public function __construct(
        private readonly string $resource,
        private readonly array $filters = [],
        private readonly array $sort = [],
        private readonly int $limit = 50,
        private readonly int $offset = 0
    ) {}
}
```

### API Endpoint Structure

**Base URL:**
```
https://example.com/api/[resource]/[id]
```

**Przykłady:**
```
GET    /api/products           - Lista produktów
GET    /api/products/123       - Pojedynczy produkt
POST   /api/products           - Nowy produkt
PUT    /api/products/123       - Pełna aktualizacja
PATCH  /api/products/123       - Częściowa aktualizacja
DELETE /api/products/123       - Usunięcie produktu
```

---

## Autentykacja

### 1. API Key jako Username (Recommended)

API Key używany jako **username** w HTTP Basic Authentication (bez password).

**cURL Example:**
```bash
curl -X GET \
  "https://example.com/api/products" \
  -u "YOUR_API_KEY:"
```

**PHP Example:**
```php
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://example.com/api/products');
curl_setopt($curl, CURLOPT_USERPWD, 'YOUR_API_KEY:');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);
```

**Laravel HTTP Client:**
```php
use Illuminate\Support\Facades\Http;

$response = Http::withBasicAuth(config('prestashop.api_key'), '')
    ->get('https://example.com/api/products');
```

### 2. Authorization Header

API Key w header `Authorization` jako Base64 encoded.

**cURL Example:**
```bash
# Base64 encode: echo -n "YOUR_API_KEY:" | base64
curl -X GET \
  "https://example.com/api/products" \
  -H "Authorization: Basic $(echo -n 'YOUR_API_KEY:' | base64)"
```

**PHP Example:**
```php
$apiKey = 'YOUR_API_KEY';
$headers = [
    'Authorization: Basic ' . base64_encode($apiKey . ':')
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://example.com/api/products');
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);
```

### 3. URL-Based (Deprecated, ale działa)

API Key jako query parameter `ws_key`.

**Example:**
```
GET https://example.com/api/products?ws_key=YOUR_API_KEY
```

**⚠️ Ostrzeżenie:** Nie zalecane ze względów bezpieczeństwa (API key w URL, logi serwera, historia przeglądarki).

### Generowanie API Key

**Admin Panel:**
1. Advanced Parameters → Webservice
2. Add new key
3. Wybierz permissions (GET, POST, PUT, DELETE per resource)
4. Skopiuj wygenerowany klucz

**Permissions Pattern:**
```
products: GET, POST, PUT, DELETE
categories: GET, POST, PUT, DELETE
stock_availables: GET, PUT (readonly for POST/DELETE)
manufacturers: GET (readonly)
```

---

## CQRS Pattern

### CQRSGet - Pobieranie Pojedynczego Resource

**Endpoint:**
```
GET /api/[resource]/[id]
```

**Query Parameters:**
- `output_format` - Format output (XML, JSON)
- `display` - Pola do wyświetlenia (full, [field1,field2,...])
- `language` - ID języka dla multilang fields

**Example:**
```bash
curl -X GET \
  "https://example.com/api/products/123?output_format=JSON&display=full" \
  -u "YOUR_API_KEY:"
```

**Response (JSON):**
```json
{
  "product": {
    "id": 123,
    "id_manufacturer": 5,
    "reference": "ABC-123",
    "price": "99.99",
    "active": "1",
    "name": [
      {"id": "1", "value": "Product Name EN"},
      {"id": "2", "value": "Nazwa Produktu PL"}
    ]
  }
}
```

### CQRSGetList - Pobieranie Listy Resources

**Endpoint:**
```
GET /api/[resource]
```

**Query Parameters:**
- `filter[field]` - Filtrowanie ([field]=[value], [field]=[from..to])
- `display` - Pola do wyświetlenia
- `sort` - Sortowanie ([field]_ASC, [field]_DESC)
- `limit` - Limit wyników (offset,count)
- `language` - ID języka

**Example:**
```bash
curl -X GET \
  "https://example.com/api/products?filter[active]=1&filter[price]=[50..100]&sort=name_ASC&limit=0,50&output_format=JSON" \
  -u "YOUR_API_KEY:"
```

**Response (JSON):**
```json
{
  "products": [
    {
      "id": 123,
      "reference": "ABC-123",
      "name": "Product 1"
    },
    {
      "id": 124,
      "reference": "ABC-124",
      "name": "Product 2"
    }
  ]
}
```

### CQRSCreate - Tworzenie Nowego Resource

**Endpoint:**
```
POST /api/[resource]
```

**Content-Type:** `application/xml` (REQUIRED!)

**Body:** XML ze schema blank jako template

**Workflow:**
1. GET `/api/[resource]?schema=blank` - Pobierz template XML
2. Wypełnij wymagane pola
3. POST XML do `/api/[resource]`
4. Otrzymaj ID nowo utworzonego resource

**Example:**
```bash
# 1. Pobierz blank schema
curl -X GET \
  "https://example.com/api/products?schema=blank" \
  -u "YOUR_API_KEY:"

# Response: XML template z pustymi polami

# 2. Wypełnij i wyślij
curl -X POST \
  "https://example.com/api/products" \
  -u "YOUR_API_KEY:" \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id_manufacturer>5</id_manufacturer>
    <reference>NEW-PROD-001</reference>
    <price>149.99</price>
    <active>1</active>
    <name>
      <language id="1">New Product</language>
    </name>
    <description>
      <language id="1"><![CDATA[Product description]]></language>
    </description>
  </product>
</prestashop>'
```

**Response (201 Created):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product id="456" xlink:href="https://example.com/api/products/456">
    <id>456</id>
    <reference>NEW-PROD-001</reference>
    <!-- ... full resource data ... -->
  </product>
</prestashop>
```

### CQRSUpdate - Pełna Aktualizacja Resource

**Endpoint:**
```
PUT /api/[resource]/[id]
```

**Content-Type:** `application/xml` (REQUIRED!)

**Body:** Pełny XML resource (wszystkie pola)

**Workflow:**
1. GET `/api/[resource]/[id]` - Pobierz aktualny stan
2. Zmodyfikuj pola w XML
3. PUT całego XML z powrotem

**Example:**
```bash
# 1. Pobierz aktualny stan
curl -X GET \
  "https://example.com/api/products/456" \
  -u "YOUR_API_KEY:"

# 2. Zmodyfikuj XML i wyślij
curl -X PUT \
  "https://example.com/api/products/456" \
  -u "YOUR_API_KEY:" \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id>456</id>
    <id_manufacturer>5</id_manufacturer>
    <reference>NEW-PROD-001</reference>
    <price>159.99</price>
    <active>1</active>
    <!-- ALL fields required! -->
  </product>
</prestashop>'
```

**⚠️ Important:** PUT wymaga WSZYSTKICH pól. Pominięte pola zostaną ustawione na NULL/default!

### CQRSPartialUpdate - Częściowa Aktualizacja

**Endpoint:**
```
PATCH /api/[resource]/[id]
```

**Content-Type:** `application/xml` (REQUIRED!)

**Body:** XML tylko z polami do aktualizacji

**Example:**
```bash
curl -X PATCH \
  "https://example.com/api/products/456" \
  -u "YOUR_API_KEY:" \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id>456</id>
    <price>179.99</price>
  </product>
</prestashop>'
```

**✅ Advantage:** Tylko zmienione pola, reszta pozostaje bez zmian.

### CQRSDelete - Usuwanie Resource

**Endpoint:**
```
DELETE /api/[resource]/[id]
```

**Example:**
```bash
curl -X DELETE \
  "https://example.com/api/products/456" \
  -u "YOUR_API_KEY:"
```

**Response (200 OK):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <message>Successfully deleted</message>
</prestashop>
```

**⚠️ Note:** Niektóre resources używają soft delete (field `deleted=1`) zamiast physical delete.

---

## HTTP Methods

### Kompletna Tabela HTTP Methods dla Resources

| Resource | GET | POST | PUT | PATCH | DELETE | HEAD |
|----------|-----|------|-----|-------|--------|------|
| addresses | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| carriers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| cart_rules | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| carts | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| categories | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| combinations | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| configurations | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ |
| contacts | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| content_management_system | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| countries | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| currencies | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| customer_messages | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| customer_threads | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| customers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| customizations | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| deliveries | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| employees | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| groups | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| guests | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| image_types | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| images | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| languages | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| manufacturers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| messages | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_carriers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_details | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_histories | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_invoices | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_payments | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_slip | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| order_states | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| orders | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| price_ranges | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_customization_fields | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_feature_values | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_features | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_option_values | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_options | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| product_suppliers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| products | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| search | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| shop_groups | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| shop_urls | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| shops | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| specific_price_rules | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| specific_prices | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| states | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| stock_availables | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ |
| stock_movement_reasons | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| stock_movements | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| stores | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| suppliers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| tags | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| tax_rule_groups | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| tax_rules | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| taxes | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| translated_configurations | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ |
| weight_ranges | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| zones | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Read-Only Resources (GET/HEAD only):**
- `search` - Search endpoint

**Limited Write Resources:**
- `configurations` - No POST/DELETE (tylko GET, PUT, PATCH)
- `translated_configurations` - No POST/DELETE
- `stock_availables` - No POST/DELETE (managed by PrestaShop)
- `images` - No PATCH (use POST for upload, DELETE for removal)

---

## Query Parameters

### display - Kontrola Pól Output

**Syntax:**
```
?display=[field1,field2,...]
?display=full
```

**Options:**
- `display=full` - Wszystkie pola (default dla single resource GET)
- `display=[id,reference,name]` - Tylko wybrane pola
- Brak parametru - Podstawowe pola (default dla list GET)

**Example:**
```bash
# Tylko ID i reference
curl "https://example.com/api/products?display=[id,reference]" -u "API_KEY:"

# Wszystkie pola
curl "https://example.com/api/products/123?display=full" -u "API_KEY:"
```

**Response (display=[id,reference]):**
```json
{
  "products": [
    {"id": 123, "reference": "ABC-123"},
    {"id": 124, "reference": "ABC-124"}
  ]
}
```

### filter - Filtrowanie Resources

**Syntax:**
```
?filter[field]=[value]
?filter[field]=[value1|value2|value3]
?filter[field]=[min..max]
?filter[field]=%[wildcard]%
```

**Operators:**
- `[value]` - Exact match
- `[value1|value2]` - OR (multiple values)
- `[min..max]` - Range (inclusive)
- `%[wildcard]%` - LIKE search (SQL wildcard)
- `![value]` - NOT equal (negate)

**Examples:**
```bash
# Exact match
curl "https://example.com/api/products?filter[active]=1" -u "API_KEY:"

# Multiple values (OR)
curl "https://example.com/api/products?filter[id_manufacturer]=[1|3|5]" -u "API_KEY:"

# Range
curl "https://example.com/api/products?filter[price]=[50..100]" -u "API_KEY:"

# LIKE search
curl "https://example.com/api/products?filter[reference]=%ABC%" -u "API_KEY:"

# NOT equal
curl "https://example.com/api/products?filter[active]=![0]" -u "API_KEY:"

# Multiple filters (AND)
curl "https://example.com/api/products?filter[active]=1&filter[price]=[50..100]" -u "API_KEY:"
```

**⚠️ Note:** Filters działają tylko na top-level fields, nie na nested associations.

### sort - Sortowanie Results

**Syntax:**
```
?sort=[field]_ASC
?sort=[field]_DESC
?sort=[field1]_ASC,[field2]_DESC
```

**Examples:**
```bash
# Ascending
curl "https://example.com/api/products?sort=name_ASC" -u "API_KEY:"

# Descending
curl "https://example.com/api/products?sort=price_DESC" -u "API_KEY:"

# Multiple sort
curl "https://example.com/api/products?sort=active_DESC,name_ASC" -u "API_KEY:"
```

### limit - Paginacja Results

**Syntax:**
```
?limit=[count]
?limit=[offset],[count]
```

**Examples:**
```bash
# Pierwsze 50
curl "https://example.com/api/products?limit=50" -u "API_KEY:"

# Offset 100, następne 50
curl "https://example.com/api/products?limit=100,50" -u "API_KEY:"

# Paginacja (strona 3, 25 per page)
curl "https://example.com/api/products?limit=50,25" -u "API_KEY:"
```

**Paginacja Pattern:**
```php
$perPage = 50;
$page = 3;
$offset = ($page - 1) * $perPage; // 100

$url = "https://example.com/api/products?limit={$offset},{$perPage}";
```

### language - Multilang Fields

**Syntax:**
```
?language=[id_lang]
```

**Example:**
```bash
# Polski (id_lang = 2)
curl "https://example.com/api/products?language=2&display=full" -u "API_KEY:"
```

**Response:**
```json
{
  "products": [
    {
      "id": 123,
      "name": "Nazwa produktu",
      "description": "Opis po polsku"
    }
  ]
}
```

**⚠️ Note:** Bez parametru `language` - zwraca WSZYSTKIE języki jako array.

### output_format - Format Response

**Syntax:**
```
?output_format=JSON
?output_format=XML
```

**Alternative:** Header `Io-Format: JSON`

**Examples:**
```bash
# Query parameter
curl "https://example.com/api/products?output_format=JSON" -u "API_KEY:"

# Header
curl "https://example.com/api/products" \
  -H "Io-Format: JSON" \
  -u "API_KEY:"
```

**Default:** XML (bez parametru)

### schema - Resource Schema

**Syntax:**
```
?schema=blank
?schema=synopsis
```

**schema=blank:**
- Empty XML template for creating new resource
- Wszystkie pola z default values
- Use for POST (CQRSCreate)

**schema=synopsis:**
- Detailed schema z typami, required, maxLength, etc.
- Dokumentacja fields
- Use for documentation/validation

**Examples:**
```bash
# Blank template
curl "https://example.com/api/products?schema=blank" -u "API_KEY:"

# Detailed schema
curl "https://example.com/api/products?schema=synopsis" -u "API_KEY:"
```

**Response (schema=synopsis excerpt):**
```xml
<field name="reference" required="false" maxSize="64">
  <description>Product reference code</description>
  <type>String</type>
  <readonly>false</readonly>
</field>
```

### Kombinowanie Parameters

**Example - Complex Query:**
```bash
curl "https://example.com/api/products?\
filter[active]=1&\
filter[price]=[50..100]&\
filter[id_manufacturer]=[1|3|5]&\
sort=price_ASC&\
limit=0,50&\
display=[id,reference,name,price]&\
output_format=JSON" \
-u "API_KEY:"
```

---

## Resource Schemas

### blank - Template dla Tworzenia

**Usage:**
```bash
GET /api/[resource]?schema=blank
```

**Purpose:** Empty XML template z default values dla wszystkich pól.

**Example Request:**
```bash
curl "https://example.com/api/products?schema=blank" -u "API_KEY:"
```

**Example Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <id></id>
    <id_manufacturer></id_manufacturer>
    <id_supplier></id_supplier>
    <id_category_default></id_category_default>
    <new></new>
    <cache_default_attribute></cache_default_attribute>
    <id_default_image></id_default_image>
    <id_default_combination></id_default_combination>
    <id_tax_rules_group></id_tax_rules_group>
    <position_in_category></position_in_category>
    <manufacturer_name></manufacturer_name>
    <quantity></quantity>
    <type></type>
    <reference></reference>
    <supplier_reference></supplier_reference>
    <location></location>
    <width></width>
    <height></height>
    <depth></depth>
    <weight></weight>
    <quantity_discount></quantity_discount>
    <ean13></ean13>
    <isbn></isbn>
    <upc></upc>
    <mpn></mpn>
    <cache_is_pack></cache_is_pack>
    <cache_has_attachments></cache_has_attachments>
    <is_virtual></is_virtual>
    <state></state>
    <additional_delivery_times></additional_delivery_times>
    <delivery_in_stock>
      <language id="1"></language>
    </delivery_in_stock>
    <delivery_out_stock>
      <language id="1"></language>
    </delivery_out_stock>
    <product_type></product_type>
    <on_sale></on_sale>
    <online_only></online_only>
    <ecotax></ecotax>
    <minimal_quantity></minimal_quantity>
    <low_stock_threshold></low_stock_threshold>
    <low_stock_alert></low_stock_alert>
    <price></price>
    <wholesale_price></wholesale_price>
    <unity></unity>
    <unit_price_ratio></unit_price_ratio>
    <additional_shipping_cost></additional_shipping_cost>
    <customizable></customizable>
    <text_fields></text_fields>
    <uploadable_files></uploadable_files>
    <active></active>
    <redirect_type></redirect_type>
    <id_type_redirected></id_type_redirected>
    <available_for_order></available_for_order>
    <available_date></available_date>
    <show_condition></show_condition>
    <condition></condition>
    <show_price></show_price>
    <indexed></indexed>
    <visibility></visibility>
    <advanced_stock_management></advanced_stock_management>
    <date_add></date_add>
    <date_upd></date_upd>
    <pack_stock_type></pack_stock_type>
    <meta_description>
      <language id="1"></language>
    </meta_description>
    <meta_keywords>
      <language id="1"></language>
    </meta_keywords>
    <meta_title>
      <language id="1"></language>
    </meta_title>
    <link_rewrite>
      <language id="1"></language>
    </link_rewrite>
    <name>
      <language id="1"></language>
    </name>
    <description>
      <language id="1"></language>
    </description>
    <description_short>
      <language id="1"></language>
    </description_short>
    <available_now>
      <language id="1"></language>
    </available_now>
    <available_later>
      <language id="1"></language>
    </available_later>
    <associations>
      <categories>
        <category>
          <id></id>
        </category>
      </categories>
      <images>
        <image>
          <id></id>
        </image>
      </images>
      <combinations>
        <combination>
          <id></id>
        </combination>
      </combinations>
      <product_option_values>
        <product_option_value>
          <id></id>
        </product_option_value>
      </product_option_values>
      <product_features>
        <product_feature>
          <id></id>
          <id_feature_value></id_feature_value>
        </product_feature>
      </product_features>
      <tags>
        <tag>
          <id></id>
        </tag>
      </tags>
      <stock_availables>
        <stock_available>
          <id></id>
          <id_product_attribute></id_product_attribute>
        </stock_available>
      </stock_availables>
      <attachments>
        <attachment>
          <id></id>
        </attachment>
      </attachments>
      <accessories>
        <product>
          <id></id>
        </product>
      </accessories>
      <product_bundle>
        <product>
          <id></id>
          <quantity></quantity>
        </product>
      </product_bundle>
    </associations>
  </product>
</prestashop>
```

### synopsis - Detailed Field Schema

**Usage:**
```bash
GET /api/[resource]?schema=synopsis
```

**Purpose:** Complete field documentation z typami, validation rules, constraints.

**Example Request:**
```bash
curl "https://example.com/api/products?schema=synopsis" -u "API_KEY:"
```

**Example Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product>
    <field name="id" required="false" readOnly="true" hidden="true">
      <description>Unique identifier (auto-generated)</description>
      <type>Integer</type>
    </field>
    <field name="id_manufacturer" format="isUnsignedId" required="false">
      <description>Manufacturer ID</description>
      <type>Integer</type>
    </field>
    <field name="reference" format="isReference" maxSize="64" required="false">
      <description>Product reference code</description>
      <type>String</type>
    </field>
    <field name="price" format="isPrice" required="true">
      <description>Product price (excluding tax)</description>
      <type>Float</type>
    </field>
    <field name="active" format="isBool" required="false">
      <description>Product enabled (0 or 1)</description>
      <type>Boolean</type>
    </field>
    <field name="name" format="isGenericName" maxSize="255" required="true">
      <description>Product name</description>
      <type>String</type>
      <language>true</language>
    </field>
    <field name="description" format="isCleanHtml" required="false">
      <description>Product description (HTML allowed)</description>
      <type>HTML</type>
      <language>true</language>
    </field>
    <!-- ... more fields ... -->
  </product>
</prestashop>
```

**Field Attributes:**
- `name` - Field name
- `required` - Czy wymagane (true/false)
- `readOnly` - Czy tylko do odczytu (true/false)
- `hidden` - Czy ukryte (true/false)
- `format` - Validation function name (isUnsignedId, isReference, isPrice, isBool, etc.)
- `maxSize` - Max długość string
- `language` - Czy multilang (true jeśli tak)

---

## Cache Optimization

### Content-Sha1 Header

PrestaShop API wspiera cache optimization poprzez `Content-Sha1` header.

**Workflow:**

1. **Initial Request:**
```bash
curl -I "https://example.com/api/products/123" -u "API_KEY:"
```

**Response Headers:**
```
HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8
Content-Sha1: 7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r
```

2. **Subsequent Request z Cache Check:**
```bash
curl "https://example.com/api/products/123" \
  -H "Local-Content-Sha1: 7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r" \
  -u "API_KEY:"
```

**Response jeśli NIE zmienione:**
```
HTTP/1.1 304 Not Modified
```

**Response jeśli zmienione:**
```
HTTP/1.1 200 OK
Content-Sha1: NEW_SHA1_HERE
[updated XML/JSON]
```

### Implementation Pattern (Laravel)

```php
use Illuminate\Support\Facades\Cache;

class PrestaShopApiClient
{
    public function getProduct(int $id): array
    {
        $cacheKey = "prestashop_product_{$id}_sha1";
        $localSha1 = Cache::get($cacheKey);

        $headers = [];
        if ($localSha1) {
            $headers['Local-Content-Sha1'] = $localSha1;
        }

        $response = Http::withBasicAuth(config('prestashop.api_key'), '')
            ->withHeaders($headers)
            ->get("https://example.com/api/products/{$id}");

        if ($response->status() === 304) {
            // Not modified, use local cache
            return Cache::get("prestashop_product_{$id}");
        }

        if ($response->successful()) {
            $data = $response->json();
            $newSha1 = $response->header('Content-Sha1');

            // Update cache
            Cache::put($cacheKey, $newSha1, now()->addHours(24));
            Cache::put("prestashop_product_{$id}", $data, now()->addHours(24));

            return $data;
        }

        throw new \Exception("API request failed: " . $response->status());
    }
}
```

**Benefits:**
- Reduced bandwidth (no body transfer on 304)
- Faster response (no XML parsing needed)
- Less server load (quick SHA1 comparison)

---

## Dostępne Resources

### Complete Resource List (70+ resources)

**Products & Catalog:**
- `products` - Product entities
- `categories` - Product categories
- `combinations` - Product variants/combinations
- `manufacturers` - Product manufacturers
- `suppliers` - Product suppliers
- `product_suppliers` - Product-supplier associations
- `product_options` - Attribute groups (Color, Size, etc.)
- `product_option_values` - Attribute values (Red, Blue, XL, etc.)
- `product_features` - Feature groups (Material, Weight, etc.)
- `product_feature_values` - Feature values
- `product_customization_fields` - Customization fields
- `images` - Product images
- `image_types` - Image type configurations
- `tags` - Product tags

**Stock Management:**
- `stock_availables` - Stock quantities per product/combination/shop
- `stock_movements` - Stock movement history
- `stock_movement_reasons` - Reasons for stock movements

**Pricing:**
- `specific_prices` - Specific price rules per product
- `specific_price_rules` - Global price rule definitions
- `cart_rules` - Cart/voucher rules

**Orders:**
- `orders` - Order entities
- `order_details` - Order line items
- `order_carriers` - Order shipping info
- `order_histories` - Order status change history
- `order_invoices` - Invoice data
- `order_payments` - Payment transactions
- `order_slip` - Credit slips/refunds
- `order_states` - Order status definitions

**Customers:**
- `customers` - Customer accounts
- `addresses` - Customer addresses
- `groups` - Customer groups
- `guests` - Guest checkout data
- `carts` - Shopping carts

**Shipping:**
- `carriers` - Shipping carriers
- `deliveries` - Delivery zones/costs
- `price_ranges` - Price-based shipping ranges
- `weight_ranges` - Weight-based shipping ranges

**Locations:**
- `countries` - Country definitions
- `states` - State/province definitions
- `zones` - Geographic zones

**Multi-Store:**
- `shops` - Shop instances
- `shop_groups` - Shop group definitions
- `shop_urls` - Shop URL configurations

**Configuration:**
- `configurations` - System configuration values
- `translated_configurations` - Multilang configuration values
- `languages` - Language definitions
- `currencies` - Currency definitions

**Content:**
- `content_management_system` - CMS pages
- `contacts` - Contact form configurations
- `messages` - Order messages
- `customer_messages` - Customer service messages
- `customer_threads` - Customer service threads

**Tax:**
- `taxes` - Tax definitions
- `tax_rules` - Tax rule assignments
- `tax_rule_groups` - Tax rule groups

**Employees:**
- `employees` - Back office users

**Other:**
- `stores` - Physical store locations
- `customizations` - Product customizations
- `attachments` - File attachments
- `search` - Search endpoint (read-only)

---

## Przykłady Użycia

### 1. Tworzenie Nowego Produktu

```php
use Illuminate\Support\Facades\Http;

class PrestaShopProductCreator
{
    private string $apiKey;
    private string $apiUrl;

    public function createProduct(array $data): int
    {
        // 1. Get blank schema
        $blank = $this->getBlankSchema();

        // 2. Fill required fields
        $xml = $this->buildProductXml($blank, $data);

        // 3. POST to API
        $response = Http::withBasicAuth($this->apiKey, '')
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->send('POST', "{$this->apiUrl}/products", [
                'body' => $xml
            ]);

        if (!$response->successful()) {
            throw new \Exception("Product creation failed: " . $response->body());
        }

        // 4. Extract ID from response
        $xmlResponse = simplexml_load_string($response->body());
        $productId = (int) $xmlResponse->product['id'];

        return $productId;
    }

    private function getBlankSchema(): string
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("{$this->apiUrl}/products?schema=blank");

        return $response->body();
    }

    private function buildProductXml(string $blank, array $data): string
    {
        $xml = simplexml_load_string($blank);

        // Remove empty nodes
        $product = $xml->product;
        unset($product->id); // Auto-generated

        // Set required fields
        $product->reference = $data['reference'];
        $product->price = $data['price'];
        $product->active = 1;

        // Set manufacturer
        if (!empty($data['id_manufacturer'])) {
            $product->id_manufacturer = $data['id_manufacturer'];
        }

        // Set multilang fields
        $product->name->language[0] = $data['name'];
        $product->name->language[0]['id'] = 1; // English

        if (!empty($data['description'])) {
            $dom = dom_import_simplexml($product->description->language[0]);
            $dom->appendChild($dom->ownerDocument->createCDATASection($data['description']));
            $product->description->language[0]['id'] = 1;
        }

        // Link rewrite (URL slug)
        $product->link_rewrite->language[0] = $this->sanitizeUrlSlug($data['name']);
        $product->link_rewrite->language[0]['id'] = 1;

        // Set categories
        if (!empty($data['categories'])) {
            $product->id_category_default = $data['categories'][0];

            $categories = $product->associations->categories;
            $categories->category = null; // Clear blank

            foreach ($data['categories'] as $catId) {
                $category = $categories->addChild('category');
                $category->addChild('id', $catId);
            }
        }

        return $xml->asXML();
    }

    private function sanitizeUrlSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}

// Usage
$creator = new PrestaShopProductCreator();
$productId = $creator->createProduct([
    'reference' => 'NEW-PROD-001',
    'name' => 'New Product Name',
    'description' => '<p>Product description with <strong>HTML</strong></p>',
    'price' => 149.99,
    'id_manufacturer' => 5,
    'categories' => [2, 5, 8]
]);

echo "Created product ID: {$productId}";
```

### 2. Aktualizacja Stock dla Produktu

```php
class PrestaShopStockUpdater
{
    public function updateStock(int $productId, int $quantity, int $shopId = 1): void
    {
        // 1. Get stock_available ID
        $stockId = $this->getStockAvailableId($productId, $shopId);

        // 2. Get current stock data
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("{$this->apiUrl}/stock_availables/{$stockId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to get stock: " . $response->body());
        }

        // 3. Modify quantity
        $xml = simplexml_load_string($response->body());
        $xml->stock_available->quantity = $quantity;

        // 4. PUT updated XML
        $updateResponse = Http::withBasicAuth($this->apiKey, '')
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->send('PUT', "{$this->apiUrl}/stock_availables/{$stockId}", [
                'body' => $xml->asXML()
            ]);

        if (!$updateResponse->successful()) {
            throw new \Exception("Stock update failed: " . $updateResponse->body());
        }
    }

    private function getStockAvailableId(int $productId, int $shopId): int
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("{$this->apiUrl}/stock_availables", [
                'filter[id_product]' => $productId,
                'filter[id_shop]' => $shopId,
                'filter[id_product_attribute]' => 0, // Main product (no combination)
                'display' => 'full',
                'output_format' => 'JSON'
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to find stock_available: " . $response->body());
        }

        $data = $response->json();

        if (empty($data['stock_availables'])) {
            throw new \Exception("Stock available not found for product {$productId}");
        }

        return (int) $data['stock_availables'][0]['id'];
    }
}

// Usage
$updater = new PrestaShopStockUpdater();
$updater->updateStock(productId: 123, quantity: 50, shopId: 1);
```

### 3. Sync Produktów z Paginacją

```php
class PrestaShopProductSync
{
    private const PER_PAGE = 50;

    public function syncAllProducts(): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $products = $this->fetchPage($page);

            if (empty($products)) {
                $hasMore = false;
                break;
            }

            foreach ($products as $product) {
                $this->syncProduct($product);
            }

            $page++;
        }
    }

    private function fetchPage(int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $response = Http::withBasicAuth($this->apiKey, '')
            ->timeout(60)
            ->get("{$this->apiUrl}/products", [
                'display' => 'full',
                'filter[active]' => 1,
                'limit' => "{$offset}," . self::PER_PAGE,
                'output_format' => 'JSON'
            ]);

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->status());
        }

        $data = $response->json();

        return $data['products'] ?? [];
    }

    private function syncProduct(array $prestashopProduct): void
    {
        // Map PrestaShop product to local Product model
        $localProduct = Product::updateOrCreate(
            ['prestashop_id' => $prestashopProduct['id']],
            [
                'sku' => $prestashopProduct['reference'],
                'name' => $prestashopProduct['name'][0]['value'] ?? '',
                'price' => (float) $prestashopProduct['price'],
                'active' => (bool) $prestashopProduct['active'],
                'manufacturer_id' => $prestashopProduct['id_manufacturer'],
                'synced_at' => now()
            ]
        );

        Log::info("Synced product: {$localProduct->sku}");
    }
}
```

### 4. Bulk Create z Error Handling

```php
class BulkProductCreator
{
    public function createProducts(array $products): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($products as $index => $productData) {
            try {
                $productId = $this->createProduct($productData);

                $results['success'][] = [
                    'index' => $index,
                    'reference' => $productData['reference'],
                    'prestashop_id' => $productId
                ];

                // Rate limiting (shared hosting protection)
                usleep(500000); // 0.5s delay

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'reference' => $productData['reference'],
                    'error' => $e->getMessage()
                ];

                Log::error("Product creation failed", [
                    'reference' => $productData['reference'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    private function createProduct(array $data): int
    {
        // Implementation from example 1
        // ...
    }
}

// Usage
$creator = new BulkProductCreator();
$results = $creator->createProducts([
    ['reference' => 'PROD-001', 'name' => 'Product 1', 'price' => 99.99],
    ['reference' => 'PROD-002', 'name' => 'Product 2', 'price' => 149.99],
    ['reference' => 'PROD-003', 'name' => 'Product 3', 'price' => 199.99],
]);

echo "Success: " . count($results['success']) . "\n";
echo "Failed: " . count($results['failed']) . "\n";
```

---

## Błędy i Obsługa

### HTTP Status Codes

**Success:**
- `200 OK` - Request successful (GET, PUT, PATCH, DELETE)
- `201 Created` - Resource created (POST)
- `304 Not Modified` - Cache hit (Content-Sha1 match)

**Client Errors:**
- `400 Bad Request` - Invalid XML, missing required fields
- `401 Unauthorized` - Invalid API key
- `403 Forbidden` - API key lacks permission for operation
- `404 Not Found` - Resource doesn't exist
- `405 Method Not Allowed` - HTTP method not supported for resource
- `409 Conflict` - Duplicate resource (e.g., existing reference)

**Server Errors:**
- `500 Internal Server Error` - PrestaShop error
- `503 Service Unavailable` - Server overloaded

### Error Response Format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <errors>
    <error>
      <code>400</code>
      <message>
        <![CDATA[
        property Product->reference (length exceeds 64 characters)
        ]]>
      </message>
    </error>
  </errors>
</prestashop>
```

### Common Errors

#### 1. manufacturer_name jest readonly

**Error:**
```xml
<message>property Product->manufacturer_name is read only. Please remove this property</message>
```

**Fix:**
Usuń pole `manufacturer_name` z XML. Użyj `id_manufacturer` zamiast.

```php
// ❌ WRONG
$xml->product->manufacturer_name = 'Samsung';

// ✅ CORRECT
$xml->product->id_manufacturer = 5;
```

#### 2. XML Format Invalid

**Error:**
```xml
<message>XML error: Opening and ending tag mismatch</message>
```

**Fix:**
- Sprawdź poprawność XML syntax
- Użyj `simplexml_load_string()` do walidacji przed wysłaniem
- Ensure proper encoding (UTF-8)

```php
// Validate XML before sending
$xml = simplexml_load_string($xmlString);
if ($xml === false) {
    throw new \Exception("Invalid XML: " . implode(", ", libxml_get_errors()));
}
```

#### 3. Missing Required Field

**Error:**
```xml
<message>property Product->name is required</message>
```

**Fix:**
Dodaj wymagane pole. Sprawdź schema=synopsis dla listy required fields.

```bash
# Check required fields
curl "https://example.com/api/products?schema=synopsis" -u "API_KEY:" | grep "required=\"true\""
```

#### 4. Association Not Found

**Error:**
```xml
<message>This category (id = 999) does not exist</message>
```

**Fix:**
- Sprawdź czy referenced resource istnieje PRZED utworzeniem
- Użyj HEAD request do weryfikacji

```php
// Verify category exists
$response = Http::withBasicAuth($apiKey, '')
    ->head("{$apiUrl}/categories/{$categoryId}");

if ($response->status() === 404) {
    throw new \Exception("Category {$categoryId} does not exist");
}
```

#### 5. Rate Limit (Hostido/Shared Hosting)

**Symptom:** Timeouts, 503 errors, slow responses

**Fix:**
- Add delays between requests (500ms - 1s)
- Use exponential backoff on errors
- Batch operations during off-peak hours

```php
use Illuminate\Support\Facades\Http;

class RateLimitedApiClient
{
    private const DELAY_MS = 500;
    private const MAX_RETRIES = 3;

    public function request(string $method, string $url, array $options = []): Response
    {
        $attempt = 1;

        while ($attempt <= self::MAX_RETRIES) {
            try {
                $response = Http::withBasicAuth($this->apiKey, '')
                    ->send($method, $url, $options);

                if ($response->successful()) {
                    usleep(self::DELAY_MS * 1000);
                    return $response;
                }

                if ($response->status() === 503) {
                    // Exponential backoff
                    $delay = self::DELAY_MS * pow(2, $attempt - 1);
                    usleep($delay * 1000);
                    $attempt++;
                    continue;
                }

                throw new \Exception("API error: " . $response->status());

            } catch (\Exception $e) {
                if ($attempt >= self::MAX_RETRIES) {
                    throw $e;
                }
                $attempt++;
            }
        }
    }
}
```

---

## Best Practices

### 1. ALWAYS Use XML for POST/PUT/PATCH

```php
// ❌ WRONG: JSON for POST
Http::asJson()->post($url, $data);

// ✅ CORRECT: XML for POST
Http::withHeaders(['Content-Type' => 'application/xml'])
    ->send('POST', $url, ['body' => $xmlString]);
```

### 2. Verify Referenced Resources BEFORE Create

```php
// ✅ GOOD: Check manufacturer exists
$manufacturerExists = Http::withBasicAuth($apiKey, '')
    ->head("{$apiUrl}/manufacturers/{$manufacturerId}")
    ->successful();

if (!$manufacturerExists) {
    throw new \Exception("Manufacturer {$manufacturerId} not found");
}

// Now safe to create product with id_manufacturer
```

### 3. Use blank Schema as Template

```php
// ✅ BEST PRACTICE: Start with blank
$blank = Http::withBasicAuth($apiKey, '')
    ->get("{$apiUrl}/products?schema=blank")
    ->body();

$xml = simplexml_load_string($blank);
// Modify only needed fields
// Submit
```

### 4. Handle Multilang Fields Properly

```php
// ✅ CORRECT: Multilang with language ID
$xml->product->name->language[0] = 'English Name';
$xml->product->name->language[0]['id'] = 1;

$xml->product->name->language[1] = 'Polish Name';
$xml->product->name->language[1]['id'] = 2;
```

### 5. Use display Parameter for Performance

```php
// ❌ INEFFICIENT: Full data when only need ID + SKU
$response = Http::get("{$apiUrl}/products");

// ✅ EFFICIENT: Only needed fields
$response = Http::get("{$apiUrl}/products?display=[id,reference]");
```

### 6. Implement Caching with Content-Sha1

```php
// ✅ PERFORMANCE: Cache SHA1 to avoid redundant transfers
$sha1 = Cache::get("product_{$id}_sha1");

$response = Http::withBasicAuth($apiKey, '')
    ->withHeaders($sha1 ? ['Local-Content-Sha1' => $sha1] : [])
    ->get("{$apiUrl}/products/{$id}");

if ($response->status() === 304) {
    return Cache::get("product_{$id}"); // Use cached
}

// Update cache with new SHA1
Cache::put("product_{$id}_sha1", $response->header('Content-Sha1'));
Cache::put("product_{$id}", $response->json());
```

### 7. Add Rate Limiting for Shared Hosting

```php
// ✅ SAFE: 500ms delay between requests
foreach ($products as $product) {
    $this->createProduct($product);
    usleep(500000); // 0.5s
}
```

### 8. Log All API Errors

```php
try {
    $response = Http::post($url, $data);
} catch (\Exception $e) {
    Log::error('PrestaShop API Error', [
        'url' => $url,
        'method' => 'POST',
        'error' => $e->getMessage(),
        'data' => $data
    ]);
    throw $e;
}
```

### 9. Use Transactions for Multi-Step Operations

```php
DB::transaction(function () use ($productData) {
    // 1. Create PrestaShop product
    $prestashopId = $this->createPrestaShopProduct($productData);

    // 2. Create local product
    $product = Product::create([
        'prestashop_id' => $prestashopId,
        'sku' => $productData['reference']
    ]);

    // 3. Sync stock
    $this->updateStock($prestashopId, $productData['quantity']);
});
```

### 10. Test with schema=synopsis First

```bash
# ✅ ALWAYS check field requirements before implementation
curl "https://example.com/api/products?schema=synopsis" -u "API_KEY:" > product_schema.xml
```

---

## Pułapki i Ostrzeżenia

### 1. XML Input REQUIRED dla POST/PUT/PATCH

**⚠️ CRITICAL:** JSON NIE DZIAŁA dla operacji modyfikujących dane!

```php
// ❌ NIE ZADZIAŁA!
Http::asJson()->post($url, ['name' => 'Product']);

// ✅ MUSI BYĆ XML!
Http::withHeaders(['Content-Type' => 'application/xml'])
    ->send('POST', $url, ['body' => $xmlString]);
```

### 2. manufacturer_name jest READONLY

**⚠️ ALWAYS REMOVE!** Używaj `id_manufacturer` zamiast.

```php
// ❌ BŁĄD!
$xml->product->manufacturer_name = 'Samsung';

// ✅ POPRAWNIE!
$xml->product->id_manufacturer = 5;
unset($xml->product->manufacturer_name); // Remove readonly field
```

### 3. stock_availables is AUTO-MANAGED

**⚠️ NO POST/DELETE!** PrestaShop tworzy automatycznie przy tworzeniu produktu.

```php
// ❌ NIE TWÓRZ stock_available przez POST!
Http::post("{$apiUrl}/stock_availables", $xml);

// ✅ TYLKO UPDATE (PUT/PATCH) istniejącego!
Http::put("{$apiUrl}/stock_availables/{$id}", $xml);
```

### 4. link_rewrite REQUIRED dla Multilang

**⚠️ MUST PROVIDE!** URL slug dla każdego języka.

```php
// ❌ BRAK link_rewrite = BŁĄD!
$xml->product->name->language[0] = 'Product Name';

// ✅ link_rewrite WYMAGANY!
$xml->product->name->language[0] = 'Product Name';
$xml->product->link_rewrite->language[0] = 'product-name';
```

### 5. PUT Wymaga WSZYSTKICH Pól

**⚠️ POMINIĘTE POLA = NULL!**

```php
// ❌ ZŁE: PUT tylko z price
$xml->product->price = 99.99;
Http::put($url, $xml);
// Result: Inne pola ustawione na NULL!

// ✅ DOBRE: GET → modify → PUT
$current = Http::get("{$apiUrl}/products/{$id}")->body();
$xml = simplexml_load_string($current);
$xml->product->price = 99.99;
Http::put("{$apiUrl}/products/{$id}", $xml->asXML());
```

**Alternatywa:** Użyj PATCH dla partial updates.

### 6. CDATA dla HTML Fields

**⚠️ REQUIRED!** HTML w `description` musi być w CDATA.

```php
// ❌ ZŁE: Plain HTML
$xml->product->description->language[0] = '<p>Description</p>';

// ✅ DOBRE: CDATA wrapper
$dom = dom_import_simplexml($xml->product->description->language[0]);
$dom->appendChild($dom->ownerDocument->createCDATASection('<p>Description</p>'));
```

### 7. Associations Są Łatwe do Zepsucia

**⚠️ BE CAREFUL!** Associations mają nested structure.

```php
// ❌ ZŁE: Bezpośrednie przypisanie
$xml->product->associations->categories = [2, 5, 8];

// ✅ DOBRE: Proper XML structure
$categories = $xml->product->associations->categories;
foreach ([2, 5, 8] as $catId) {
    $category = $categories->addChild('category');
    $category->addChild('id', $catId);
}
```

### 8. ID Not Sent in POST

**⚠️ REMOVE ID!** ID jest auto-generated.

```php
// ❌ ZŁE: Zawiera ID w POST
$xml->product->id = 123;
Http::post($url, $xml);

// ✅ DOBRE: Usuń ID przed POST
unset($xml->product->id);
Http::post($url, $xml);
```

### 9. Shared Hosting Rate Limits

**⚠️ HOSTIDO!** Agresywne rate limiting na shared hosting.

```php
// ❌ ZŁE: Bombardowanie API
foreach ($products as $product) {
    $this->createProduct($product);
}

// ✅ DOBRE: Delay między requests
foreach ($products as $product) {
    $this->createProduct($product);
    usleep(500000); // 500ms delay
}
```

### 10. Filter Na Associations NIE DZIAŁA

**⚠️ LIMITATION!** Filters tylko na top-level fields.

```php
// ❌ NIE ZADZIAŁA: Filter na association
Http::get("{$apiUrl}/products?filter[associations.categories.id]=5");

// ✅ ZADZIAŁA: Filter top-level, then filter in code
$products = Http::get("{$apiUrl}/products?display=full")->json();
$filtered = array_filter($products, function($p) {
    return in_array(5, array_column($p['associations']['categories'], 'id'));
});
```

---

## 📚 Resources

**Official Documentation:**
- DevDocs: https://devdocs.prestashop-project.org/8/webservice/
- Context7: `/prestashop/docs` (trust 8.2, 3289 snippets)

**Related PPM Docs:**
- [PRESTASHOP_XML_INTEGRATION.md](.claude/skills/prestashop-xml-integration/SKILL.md) - XML workflow skill
- [PRESTASHOP_DATABASE_STRUCTURE.md](_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md) - Database schema
- [PROJECT_KNOWLEDGE.md](_DOCS/PROJECT_KNOWLEDGE.md) - PPM architecture

**PrestaShop Webservice Library (PHP):**
```bash
composer require prestashop/prestashop-webservice-lib
```

**Alternative:**
Use Laravel HTTP client (recommended for PPM) with examples in this document.

---

**Last Updated:** 2025-11-05
**PrestaShop Versions:** 8.x, 9.x
**PPM Integration Status:** Ready for ETAP_07 implementation
