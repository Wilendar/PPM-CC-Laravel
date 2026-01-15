---
name: "prestashop-xml-integration"
description: "PrestaShop Web Services API integration - XML format requirements, readonly fields, manufacturer lookup, CREATE vs UPDATE patterns."
---

# PrestaShop XML Integration Workflow

## 🎯 Overview

PrestaShop Web Services API (v8.x/9.x) wymaga specyficznego formatu XML dla wszystkich operacji POST/PUT/PATCH, pomimo że może zwracać dane w JSON.

Ten workflow zapewnia:
- **Poprawną konwersję** PHP arrays → PrestaShop XML
- **Obsługę readonly fields** (manufacturer_name, supplier_name, etc.)
- **Auto-lookup manufacturer/supplier** z automatycznym tworzeniem jeśli nie istnieją
- **Różnice CREATE vs UPDATE** (id injection dla UPDATE)
- **CDATA wrapping** wszystkich wartości
- **Multilang fields** handling
- **Error handling** dla typowych błędów 400/500

Workflow składa się z **4 głównych faz**:
1. **Przygotowanie danych** - filtrowanie readonly, walidacja required
2. **Konwersja do XML** - arrayToXml() z CDATA wrapping
3. **Obsługa specjalnych przypadków** - manufacturer lookup, id injection
4. **Weryfikacja i wysyłka** - validate XML, proper headers, error handling

---

## 🚀 Kiedy używać tego Workflow

Użyj `prestashop-xml-integration` gdy:
- Implementujesz **integrację z PrestaShop API** 8.x lub 9.x
- Dostajesz błąd **"Start tag expected, '<' not found"** (500 error)
- Dostajesz błąd **"parameter X not writable"** (400 error)
- Dostajesz błąd **"id is required when modifying a resource"** (400 error)
- Implementujesz **createProduct()**, **updateProduct()** lub inne POST/PUT/PATCH
- Musisz obsłużyć **manufacturer_name → id_manufacturer** conversion
- Pracujesz z **multilang fields** w PrestaShop

**Wymagania wstępne**:
- [ ] Projekt Laravel z HTTP Client
- [ ] Dostęp do PrestaShop Web Services API (API key)
- [ ] BasePrestaShopClient z metodą arrayToXml() (lub będzie utworzona)
- [ ] Znajomość dokumentacji: _DOCS/PRESTASHOP_XML_SCHEMA_REFERENCE.md

---

## 📊 PRZEGLĄD WORKFLOW

```
┌─────────────────────────────────────────────────┐
│ FAZA 1: PRZYGOTOWANIE DANYCH                    │
│ ↓ Filtruj readonly fields, waliduj required     │
├─────────────────────────────────────────────────┤
│ FAZA 2: KONWERSJA DO XML                        │
│ ↓ arrayToXml(), CDATA wrapping, multilang       │
├─────────────────────────────────────────────────┤
│ FAZA 3: SPECJALNE PRZYPADKI                     │
│ ↓ Manufacturer lookup, id injection (UPDATE)    │
├─────────────────────────────────────────────────┤
│ FAZA 4: WERYFIKACJA I WYSYŁKA                   │
│ ↓ Validate XML, proper headers, error handling  │
└─────────────────────────────────────────────────┘
```

**Szacowany czas wykonania**: 5-10 minut (pierwszy raz), 2-3 minuty (kolejne implementacje)
**Poziom złożoności**: Medium-High

---

## 📋 FAZA 1: PRZYGOTOWANIE DANYCH

### Cel Fazy
Przygotować dane wejściowe przez:
- Usunięcie readonly fields (które spowodują 400 error)
- Walidację required fields
- Konwersję name-based references na ID-based (manufacturer_name → id_manufacturer)

### Instrukcje Szczegółowe

#### 1.1 Identyfikacja i Usunięcie Readonly Fields

**8 READONLY FIELDS** (NIE WYSYŁAĆ do PrestaShop):

| Pole | Powód | Alternatywa |
|------|-------|-------------|
| `manufacturer_name` | ❌ Generated from id_manufacturer | ✅ Użyj `id_manufacturer` |
| `supplier_name` | ❌ Generated from id_supplier | ✅ Użyj `id_supplier` |
| `date_add` | ❌ Auto-generated on CREATE | - |
| `date_upd` | ❌ Auto-updated on UPDATE | - |
| `link_rewrite` | ❌ Auto-generated from name | - |
| `cache_*` | ❌ Internal PrestaShop cache | - |

```php
// ❌ WRONG - wyśle readonly field
$productData = [
    'product' => [
        'name' => [...],
        'manufacturer_name' => 'ACME Corp',  // ← READONLY!
        'price' => 99.99,
        'date_add' => now(),  // ← READONLY!
    ]
];

// ✅ CORRECT - tylko writable fields
$productData = [
    'product' => [
        'name' => [...],
        'id_manufacturer' => 5,  // ← ID zamiast name
        'price' => 99.99,
        // date_add będzie auto-generated
    ]
];
```

**Implementacja w ProductTransformer.php**:
```php
public function toPrestaShop(Product $product, ...): array
{
    return [
        'product' => [
            // ✅ WRITABLE FIELDS ONLY
            'name' => $this->prepareMultilangField($product->name),
            'id_manufacturer' => $this->getManufacturerId($product->manufacturer, $client, $shop),
            'price' => $product->price,
            'active' => 1,

            // ❌ NIE DODAWAJ:
            // 'manufacturer_name' => $product->manufacturer,
            // 'date_add' => $product->created_at,
            // 'date_upd' => $product->updated_at,
        ]
    ];
}
```

#### 1.2 Walidacja Required Fields

**3 REQUIRED FIELDS** (minimum dla CREATE):

```php
// Minimum viable product dla CREATE
$productData = [
    'product' => [
        'name' => [['id' => 1, 'value' => 'Product Name']],  // REQUIRED - multilang
        'price' => 99.99,                                     // REQUIRED
        'id_category_default' => 2,                           // REQUIRED
    ]
];
```

**Walidacja przed wysyłką**:
```php
private function validateRequiredFields(array $data): void
{
    if (!isset($data['product'])) {
        throw new \InvalidArgumentException('Missing product wrapper');
    }

    $product = $data['product'];
    $required = ['name', 'price', 'id_category_default'];

    foreach ($required as $field) {
        if (!isset($product[$field])) {
            throw new \InvalidArgumentException("Missing required field: {$field}");
        }
    }

    // Validate multilang name structure
    if (!is_array($product['name']) || !isset($product['name'][0]['id'])) {
        throw new \InvalidArgumentException('name must be multilang array: [["id" => 1, "value" => "..."]]');
    }
}
```

#### 1.3 Finalizacja Fazy 1

Checklist przed przejściem do Fazy 2:
- [ ] Wszystkie readonly fields usunięte z danych
- [ ] Required fields obecne i poprawne
- [ ] manufacturer_name → id_manufacturer (if applicable)
- [ ] supplier_name → id_supplier (if applicable)
- [ ] Multilang fields w poprawnym formacie: `[['id' => X, 'value' => 'Y']]`

### Output Fazy 1
```php
// Clean array ready for XML conversion
$cleanData = [
    'product' => [
        'name' => [['id' => 1, 'value' => 'Product Name EN'], ['id' => 2, 'value' => 'Product Name FR']],
        'price' => 99.99,
        'id_category_default' => 2,
        'id_manufacturer' => 5,
        'active' => 1,
        // ... other writable fields only
    ]
];
```

---

## 📋 FAZA 2: KONWERSJA DO XML

### Cel Fazy
Przekonwertować PHP array na PrestaShop XML format z:
- Proper root element `<prestashop>`
- CDATA wrapping wszystkich wartości
- Obsługa multilang fields
- Obsługa associations (categories, images, features)
- Singularization (categories → category)

### Instrukcje Szczegółowe

#### 2.1 Implementacja BasePrestaShopClient::arrayToXml()

Jeśli metoda nie istnieje, dodaj do `app/Services/PrestaShop/BasePrestaShopClient.php`:

```php
/**
 * Convert array to PrestaShop XML format
 *
 * CRITICAL: PrestaShop Web Services API requires XML for POST/PUT/PATCH
 * As of PrestaShop 8.1, API can output JSON but NOT accept JSON inputs!
 *
 * @param array $data Data to convert
 * @return string XML string
 */
public function arrayToXml(array $data): string
{
    // Create root element with PrestaShop namespace
    $xml = new \SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>'
    );

    // Convert array to XML recursively
    $this->buildXmlFromArray($data, $xml);

    return $xml->asXML();
}

/**
 * Recursively build XML from array
 */
private function buildXmlFromArray(array $data, \SimpleXMLElement $xml): void
{
    foreach ($data as $key => $value) {
        if ($value === null) {
            continue;  // Skip null values
        }

        if (is_array($value)) {
            // Multilang field: [['id' => 1, 'value' => 'Text']]
            if ($this->isMultilangField($value)) {
                $fieldElement = $xml->addChild($key);
                foreach ($value as $langData) {
                    $langElement = $fieldElement->addChild('language');
                    $langElement->addAttribute('id', $langData['id']);
                    $this->addCDataChild($langElement, $langData['value']);
                }
            }
            // Indexed array: [['id' => 1], ['id' => 2]]
            elseif ($this->isIndexedArray($value)) {
                $containerElement = $xml->addChild($key);
                $singularKey = $this->singularize($key);
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $itemElement = $containerElement->addChild($singularKey);
                        $this->buildXmlFromArray($item, $itemElement);
                    } else {
                        $this->addCDataChild($containerElement->addChild($singularKey), $item);
                    }
                }
            }
            // Nested associative array
            else {
                $childElement = $xml->addChild($key);
                $this->buildXmlFromArray($value, $childElement);
            }
        }
        // Simple values - wrap in CDATA
        else {
            $this->addCDataChild($xml->addChild($key), $value);
        }
    }
}

/**
 * Add CDATA child to XML element
 */
private function addCDataChild(\SimpleXMLElement $element, $value): void
{
    $node = dom_import_simplexml($element);
    $doc = $node->ownerDocument;
    $node->appendChild($doc->createCDATASection((string) $value));
}

/**
 * Check if array is multilang field
 */
private function isMultilangField(array $data): bool
{
    if (empty($data)) {
        return false;
    }

    $first = reset($data);
    return is_array($first) && isset($first['id']) && isset($first['value']);
}

/**
 * Check if array is indexed
 */
private function isIndexedArray(array $data): bool
{
    if (empty($data)) {
        return false;
    }

    return array_keys($data) === range(0, count($data) - 1);
}

/**
 * Singularize plural words for XML elements
 */
private function singularize(string $word): string
{
    // categories → category
    if (str_ends_with($word, 'ies')) {
        return substr($word, 0, -3) . 'y';
    }

    // products → product
    if (str_ends_with($word, 's')) {
        return substr($word, 0, -1);
    }

    return $word;
}
```

**Weryfikacja implementacji**:
```php
// Test simple values
$data = ['product' => ['id' => 123, 'name' => 'Test']];
$xml = $this->arrayToXml($data);
/* Expected:
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <id><![CDATA[123]]></id>
        <name><![CDATA[Test]]></name>
    </product>
</prestashop>
*/

// Test multilang
$data = ['product' => ['name' => [['id' => 1, 'value' => 'EN'], ['id' => 2, 'value' => 'FR']]]];
$xml = $this->arrayToXml($data);
/* Expected:
<product>
    <name>
        <language id="1"><![CDATA[EN]]></language>
        <language id="2"><![CDATA[FR]]></language>
    </name>
</product>
*/
```

#### 2.2 Obsługa Multilang Fields

Multilang fields w PrestaShop używają struktury: `[['id' => lang_id, 'value' => text]]`

```php
// Helper do przygotowania multilang field
private function prepareMultilangField(string $text, array $languages = null): array
{
    // Default: tylko język 1 (English)
    if ($languages === null) {
        return [
            ['id' => 1, 'value' => $text]
        ];
    }

    // Multiple languages
    $multilang = [];
    foreach ($languages as $langId => $langText) {
        $multilang[] = [
            'id' => $langId,
            'value' => $langText ?? $text  // fallback to default
        ];
    }

    return $multilang;
}

// Usage
$productData = [
    'product' => [
        'name' => $this->prepareMultilangField($product->name),
        'description' => $this->prepareMultilangField($product->description),
        'description_short' => $this->prepareMultilangField($product->short_description),
    ]
];
```

#### 2.3 Finalizacja Fazy 2

Checklist:
- [ ] arrayToXml() method implemented and tested
- [ ] All helper methods (buildXmlFromArray, addCDataChild, isMultilangField, isIndexedArray, singularize) implemented
- [ ] Multilang fields correctly formatted
- [ ] XML contains proper root element `<prestashop xmlns:xlink="...">`
- [ ] All values wrapped in CDATA

### Output Fazy 2
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <name>
            <language id="1"><![CDATA[Product Name]]></language>
        </name>
        <price><![CDATA[99.99]]></price>
        <id_category_default><![CDATA[2]]></id_category_default>
        <id_manufacturer><![CDATA[5]]></id_manufacturer>
        <active><![CDATA[1]]></active>
    </product>
</prestashop>
```

---

## 📋 FAZA 3: OBSŁUGA SPECJALNYCH PRZYPADKÓW

### Cel Fazy
Obsłużyć specyficzne wymagania PrestaShop API:
- Manufacturer name → ID lookup (z auto-utworzeniem)
- Supplier name → ID lookup
- UPDATE operations: injection `<id>` element
- CREATE operations: brak `<id>` element

### Instrukcje Szczegółowe

#### 3.1 Manufacturer Name → ID Lookup (z Auto-Create)

**Problem**: PrestaShop nie akceptuje `manufacturer_name`, wymaga `id_manufacturer`

**Rozwiązanie**: Auto-lookup z fallback do auto-create

Dodaj do `ProductTransformer.php`:

```php
/**
 * Get PrestaShop manufacturer ID from name
 *
 * Searches for existing manufacturer or creates new one if not found
 *
 * @param string|null $manufacturerName Manufacturer name from PPM
 * @param BasePrestaShopClient $client PrestaShop API client
 * @param PrestaShopShop $shop Shop configuration
 * @return int|null Manufacturer ID or null
 */
private function getManufacturerId(
    ?string $manufacturerName,
    BasePrestaShopClient $client,
    PrestaShopShop $shop
): ?int {
    if (!$manufacturerName) {
        return null;
    }

    // STEP 1: Try to find existing manufacturer by name
    try {
        $response = $client->makeRequest('GET', '/manufacturers', [
            'filter[name]' => $manufacturerName,
            'display' => '[id,name]'
        ]);

        // Parse response - check if manufacturer found
        if (isset($response['manufacturers']['manufacturer'])) {
            $manufacturer = $response['manufacturers']['manufacturer'];

            // Single result
            if (isset($manufacturer['id'])) {
                Log::debug('Found existing manufacturer', [
                    'manufacturer_name' => $manufacturerName,
                    'manufacturer_id' => $manufacturer['id'],
                ]);
                return (int) $manufacturer['id'];
            }

            // Multiple results - take first
            if (isset($manufacturer[0]['id'])) {
                Log::debug('Found existing manufacturer (multiple)', [
                    'manufacturer_name' => $manufacturerName,
                    'manufacturer_id' => $manufacturer[0]['id'],
                ]);
                return (int) $manufacturer[0]['id'];
            }
        }
    } catch (\Exception $e) {
        Log::warning('Failed to fetch manufacturer by name', [
            'manufacturer_name' => $manufacturerName,
            'shop_id' => $shop->id,
            'error' => $e->getMessage(),
        ]);
        // Continue to create new manufacturer
    }

    // STEP 2: Manufacturer not found - create new one
    try {
        $xmlData = [
            'manufacturer' => [
                'name' => $manufacturerName,
                'active' => 1,
            ]
        ];

        // Convert to XML using BasePrestaShopClient method
        $xmlBody = $client->arrayToXml($xmlData);

        $response = $client->makeRequest('POST', '/manufacturers', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        $newId = $response['manufacturer']['id'] ?? null;

        if ($newId) {
            Log::info('Created new manufacturer in PrestaShop', [
                'manufacturer_name' => $manufacturerName,
                'manufacturer_id' => $newId,
                'shop_id' => $shop->id,
            ]);

            return (int) $newId;
        }

    } catch (\Exception $e) {
        Log::error('Failed to create manufacturer in PrestaShop', [
            'manufacturer_name' => $manufacturerName,
            'shop_id' => $shop->id,
            'error' => $e->getMessage(),
        ]);
    }

    // STEP 3: If all fails, return null (product will be created without manufacturer)
    return null;
}
```

**Usage w toPrestaShop()**:
```php
public function toPrestaShop(Product $product, ...): array
{
    return [
        'product' => [
            'name' => $this->prepareMultilangField($product->name),

            // ❌ WRONG: 'manufacturer_name' => $product->manufacturer,
            // ✅ CORRECT: Auto-lookup + auto-create
            'id_manufacturer' => $this->getManufacturerId($product->manufacturer, $client, $shop),

            'price' => $product->price,
            // ... other fields
        ]
    ];
}
```

#### 3.2 UPDATE vs CREATE: ID Injection

**CRITICAL DIFFERENCE**:
- **CREATE (POST)**: NIE wysyłaj `<id>` element
- **UPDATE (PUT)**: MUSISZ wysłać `<id>` element w strukturze

```php
// PrestaShop8Client.php

/**
 * Create new product
 */
public function createProduct(array $productData): array
{
    // NO ID INJECTION - let PrestaShop generate
    $xmlBody = $this->arrayToXml($productData);

    return $this->makeRequest('POST', '/products', [], [
        'body' => $xmlBody,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);
}

/**
 * Update existing product
 */
public function updateProduct(int $productId, array $productData): array
{
    // CRITICAL: PrestaShop requires 'id' in product data for UPDATE
    // Inject id at the beginning of product array
    if (isset($productData['product'])) {
        $productData['product'] = array_merge(
            ['id' => $productId],  // ← ID INJECTION
            $productData['product']
        );
    }

    $xmlBody = $this->arrayToXml($productData);

    return $this->makeRequest('PUT', "/products/{$productId}", [], [
        'body' => $xmlBody,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);
}
```

**Resulting XML difference**:

```xml
<!-- CREATE (POST) - NO <id> -->
<prestashop>
    <product>
        <name>...</name>
        <price>99.99</price>
    </product>
</prestashop>

<!-- UPDATE (PUT) - WITH <id> -->
<prestashop>
    <product>
        <id><![CDATA[123]]></id>  <!-- ← REQUIRED FOR UPDATE -->
        <name>...</name>
        <price>99.99</price>
    </product>
</prestashop>
```

#### 3.3 Finalizacja Fazy 3

Checklist:
- [ ] getManufacturerId() implemented with auto-lookup + auto-create
- [ ] getSupplierId() implemented (same pattern as manufacturer)
- [ ] createProduct() does NOT inject id
- [ ] updateProduct() DOES inject id at beginning of structure
- [ ] All UPDATE methods (updateStock, updateAttributeGroup, updateAttributeValue) inject id

### Output Fazy 3
```php
// For CREATE
$xmlBody = $client->arrayToXml([
    'product' => [
        // NO id
        'name' => [...],
        'id_manufacturer' => 5,  // ← Looked up from name
    ]
]);

// For UPDATE
$xmlBody = $client->arrayToXml([
    'product' => [
        'id' => 123,  // ← INJECTED
        'name' => [...],
        'id_manufacturer' => 5,
    ]
]);
```

---

## 📋 FAZA 4: WERYFIKACJA I WYSYŁKA

### Cel Fazy
Wysłać request z proper configuration i obsłużyć typowe błędy

### Instrukcje Szczegółowe

#### 4.1 HTTP Client Configuration

```php
// BasePrestaShopClient.php or PrestaShop8Client.php

public function makeRequest(string $method, string $endpoint, array $params = [], array $options = []): array
{
    // Build URL with query params
    $url = $this->buildUrl($endpoint, $params);

    // Prepare request
    $request = Http::timeout(30)
        ->withBasicAuth($this->apiKey, '')  // PrestaShop uses API key as username
        ->acceptJson();  // Accept JSON response

    // Add custom headers if provided (e.g., Content-Type: application/xml)
    if (isset($options['headers'])) {
        foreach ($options['headers'] as $key => $value) {
            $request = $request->withHeaders([$key => $value]);
        }
    }

    // Send request
    if ($method === 'GET') {
        $response = $request->get($url);
    } elseif ($method === 'POST') {
        // Use raw XML body from options
        $response = $request->send('POST', $url, [
            'body' => $options['body'] ?? '',
        ]);
    } elseif ($method === 'PUT') {
        $response = $request->send('PUT', $url, [
            'body' => $options['body'] ?? '',
        ]);
    } elseif ($method === 'DELETE') {
        $response = $request->delete($url);
    }

    // Handle response
    if ($response->successful()) {
        return $response->json();
    }

    // Error handling
    throw new PrestaShopApiException(
        "PrestaShop API error ({$response->status()}): {$response->body()}",
        $response->status()
    );
}
```

#### 4.2 Error Handling

**Typowe błędy i rozwiązania**:

```php
// 1. Error 500: "Start tag expected, '<' not found"
// CAUSE: Wysłałeś JSON zamiast XML
// FIX: Użyj arrayToXml() + 'Content-Type: application/xml'

try {
    $xmlBody = $client->arrayToXml($productData);
    $response = $client->makeRequest('POST', '/products', [], [
        'body' => $xmlBody,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);
} catch (PrestaShopApiException $e) {
    if (str_contains($e->getMessage(), 'Start tag expected')) {
        Log::error('Sent JSON instead of XML to PrestaShop', [
            'data' => $productData,
        ]);
        throw new \RuntimeException('PrestaShop requires XML format, not JSON');
    }
}

// 2. Error 400: "parameter X not writable"
// CAUSE: Wysłałeś readonly field
// FIX: Usuń readonly field z danych

catch (PrestaShopApiException $e) {
    if (str_contains($e->getMessage(), 'not writable')) {
        // Extract field name from error
        preg_match('/parameter "([^"]+)" not writable/', $e->getMessage(), $matches);
        $field = $matches[1] ?? 'unknown';

        Log::error('Sent readonly field to PrestaShop', [
            'readonly_field' => $field,
            'data' => $productData,
        ]);

        throw new \RuntimeException("Field '{$field}' is readonly in PrestaShop API");
    }
}

// 3. Error 400: "id is required when modifying a resource"
// CAUSE: UPDATE bez <id> w XML structure
// FIX: Inject id before arrayToXml()

catch (PrestaShopApiException $e) {
    if (str_contains($e->getMessage(), 'id is required')) {
        Log::error('UPDATE request missing id in XML structure', [
            'data' => $productData,
        ]);

        throw new \RuntimeException('UPDATE requires id in XML structure - use array_merge()');
    }
}

// 4. Error 404: "This X does not exist"
// CAUSE: Invalid ID or resource not found
// FIX: Verify resource exists before UPDATE

catch (PrestaShopApiException $e) {
    if ($e->getCode() === 404) {
        Log::warning('PrestaShop resource not found', [
            'endpoint' => $endpoint,
            'id' => $productId ?? null,
        ]);

        throw new \RuntimeException('Resource not found in PrestaShop');
    }
}
```

#### 4.3 End-to-End Verification

Po wysłaniu request:

```php
// 1. Verify HTTP 2xx response
if (!$response->successful()) {
    throw new PrestaShopApiException("Request failed with status {$response->status()}");
}

// 2. Verify response contains expected structure
$data = $response->json();
if (!isset($data['product']['id'])) {
    Log::warning('PrestaShop response missing product.id', ['response' => $data]);
}

// 3. Log successful operation
Log::info('PrestaShop API request successful', [
    'operation' => $method,
    'endpoint' => $endpoint,
    'product_id' => $data['product']['id'] ?? null,
    'execution_time_ms' => $response->transferStats->getTransferTime() * 1000,
]);

// 4. Return parsed response
return $data;
```

#### 4.4 Finalizacja Fazy 4

Checklist:
- [ ] HTTP client properly configured (timeout, auth, headers)
- [ ] Content-Type: application/xml header added for POST/PUT/PATCH
- [ ] Error handling dla 400, 404, 500 errors
- [ ] Logging successful operations
- [ ] Response validation (structure check)

### Final Output
```php
// Successful response from PrestaShop
[
    'product' => [
        'id' => 12345,
        'name' => [...],
        'price' => 99.99,
        // ... all fields returned by PrestaShop
    ]
]
```

---

## 🔄 ROLLBACK PROCEDURE

### Rollback z Fazy 4 (po wysyłce)
```markdown
Jeśli request się powiódł ale produkt jest incorrect:

1. Wywołaj DELETE /products/{id} aby usunąć utworzony produkt
2. Lub wywołaj PUT /products/{id} z poprzednią wersją danych (UPDATE rollback)
3. Log rollback operation
4. Powrót do Fazy 3 z poprawionymi danymi
```

### Rollback z Fazy 3
```markdown
1. Jeśli manufacturer lookup failed - nie ma co rollback (read-only operation)
2. Jeśli id injection był błędny - po prostu fix w kodzie
3. Powrót do Fazy 2 z poprawionymi danymi
```

### Rollback z Fazy 2
```markdown
1. Brak side effects - XML generation jest pure function
2. Fix XML conversion logic
3. Re-run Faza 2 z tymi samymi danymi
```

### Rollback z Fazy 1
```markdown
1. Brak side effects - data preparation jest pure function
2. Fix field filtering/validation logic
3. Re-run Faza 1 z tymi samymi input data
```

---

## 📚 PRZYKŁADY UŻYCIA

### Przykład 1: Utworzenie Nowego Produktu (CREATE)

**Kontekst**: PPM Product z manufacturer "ACME Corp" → PrestaShop

**Faza 1 - Prepare Data**:
```php
// ProductTransformer.php
$product = Product::where('sku', 'ABC-123')->first();

$cleanData = [
    'product' => [
        // Required fields
        'name' => [['id' => 1, 'value' => $product->name]],
        'price' => $product->price,
        'id_category_default' => 2,

        // Optional fields
        'id_manufacturer' => $this->getManufacturerId('ACME Corp', $client, $shop),
        'ean13' => $product->ean,
        'reference' => $product->sku,
        'active' => 1,

        // ❌ NO readonly fields
    ]
];
```

**Faza 2 - Convert to XML**:
```php
$xmlBody = $client->arrayToXml($cleanData);
// Result:
// <prestashop>
//   <product>
//     <name><language id="1"><![CDATA[Product Name]]></language></name>
//     <price><![CDATA[99.99]]></price>
//     ...
//   </product>
// </prestashop>
```

**Faza 3 - Special Cases**:
```php
// getManufacturerId() auto-looked up "ACME Corp" → ID 5
// NO id injection (this is CREATE)
```

**Faza 4 - Send Request**:
```php
$response = $client->makeRequest('POST', '/products', [], [
    'body' => $xmlBody,
    'headers' => ['Content-Type' => 'application/xml'],
]);

// Success!
// Response: ['product' => ['id' => 12345, ...]]
```

**Czas wykonania**: 2.3 sekundy
**Rezultat**: ✅ Product created in PrestaShop with ID 12345

---

### Przykład 2: Aktualizacja Produktu (UPDATE)

**Kontekst**: Zmiana ceny produktu PrestaShop ID 12345

**Faza 1 - Prepare Data**:
```php
$productId = 12345;
$cleanData = [
    'product' => [
        'price' => 129.99,  // New price
        // Only fields that changed
    ]
];
```

**Faza 2 - Convert (partial)**:
```php
// Data is clean, ready for XML
```

**Faza 3 - Inject ID for UPDATE**:
```php
// CRITICAL: Inject id for UPDATE
$cleanData['product'] = array_merge(
    ['id' => $productId],  // ← REQUIRED FOR UPDATE
    $cleanData['product']
);

$xmlBody = $client->arrayToXml($cleanData);
// Result:
// <product>
//   <id><![CDATA[12345]]></id>  ← PRESENT
//   <price><![CDATA[129.99]]></price>
// </product>
```

**Faza 4 - Send PUT Request**:
```php
$response = $client->makeRequest('PUT', "/products/{$productId}", [], [
    'body' => $xmlBody,
    'headers' => ['Content-Type' => 'application/xml'],
]);

// Success!
```

**Czas wykonania**: 1.8 sekundy
**Rezultat**: ✅ Product 12345 price updated to 129.99

---

### Przykład 3: Error Recovery - Readonly Field

**Kontekst**: Próba wysłania manufacturer_name (readonly field)

**Faza 1 - BAD Data**:
```php
$badData = [
    'product' => [
        'name' => [...],
        'manufacturer_name' => 'ACME Corp',  // ❌ READONLY!
    ]
];
```

**Faza 4 - Error Response**:
```
PrestaShopApiException: PrestaShop API error (400):
parameter "manufacturer_name" not writable. Please remove this attribute of this XML
```

**Recovery Process**:
1. Catch exception, identify readonly field "manufacturer_name"
2. Log error with problematic data
3. **Rollback to Faza 1**: Remove readonly field, use id_manufacturer instead
4. Lookup manufacturer ID: getManufacturerId('ACME Corp') → 5
5. **Re-run workflow** with clean data
6. ✅ Success on second attempt

**Czas recovery**: 45 seconds (includes manufacturer lookup)
**Rezultat**: ✅ Product created after fixing readonly field

---

## ⚙️ KONFIGURACJA WORKFLOW

```yaml
# config/prestashop-integration.yaml (optional)

workflow:
  name: prestashop-xml-integration
  timeout_minutes: 10
  retry_on_failure: true
  max_retries: 3

  phases:
    phase_1_prepare:
      timeout: 2
      critical: true

    phase_2_convert:
      timeout: 1
      critical: true

    phase_3_special_cases:
      timeout: 5  # Includes external API call (manufacturer lookup)
      critical: true

    phase_4_send:
      timeout: 5
      critical: true

  manufacturer_lookup:
    auto_create: true        # Auto-create if not found
    cache_ttl_seconds: 3600  # Cache lookups for 1 hour

  error_handling:
    log_errors: true
    throw_on_readonly: true
    retry_on_500: true
    max_500_retries: 3

  notifications:
    on_success: false
    on_failure: true
    on_manufacturer_created: true
```

---

## 🔍 TROUBLESHOOTING

### Faza 1 Problems

#### Problem: "Missing required field: name"
**Objawy**: Exception thrown przed wysyłką
**Rozwiązanie**:
```php
// Ensure name is present and multilang
$productData['product']['name'] = [
    ['id' => 1, 'value' => $product->name ?? 'Unnamed Product']
];
```

#### Problem: manufacturer_name present in data
**Objawy**: 400 error "parameter manufacturer_name not writable"
**Rozwiązanie**:
```php
// Replace manufacturer_name with id_manufacturer
unset($productData['product']['manufacturer_name']);
$productData['product']['id_manufacturer'] = $this->getManufacturerId($name, $client, $shop);
```

---

### Faza 2 Problems

#### Problem: "Call to undefined method arrayToXml()"
**Objawy**: PHP fatal error
**Rozwiązanie**:
```markdown
1. Implement arrayToXml() in BasePrestaShopClient (see Faza 2.1)
2. Implement 5 helper methods (buildXmlFromArray, addCDataChild, etc)
3. Make arrayToXml() PUBLIC (not protected)
4. Test with simple array before complex data
```

#### Problem: Multilang fields not converted correctly
**Objawy**: 400 error "Invalid multilang field format"
**Rozwiązanie**:
```php
// Correct structure: [['id' => X, 'value' => 'Y']]
$name = [
    ['id' => 1, 'value' => 'English Name'],
    ['id' => 2, 'value' => 'French Name'],
];

// NOT: ['en' => 'English', 'fr' => 'French']  // ❌ WRONG
```

---

### Faza 3 Problems

#### Problem: "Manufacturer lookup infinite loop"
**Objawy**: Request timeout, many API calls
**Rozwiązanie**:
```php
// Add recursion guard
private function getManufacturerId($name, $client, $shop, $attempt = 1): ?int
{
    if ($attempt > 2) {
        Log::error('Manufacturer lookup exceeded max attempts');
        return null;  // Give up after 2 attempts
    }

    // ... rest of logic with $attempt + 1 for retry
}
```

#### Problem: "id is required when modifying a resource"
**Objawy**: 400 error on UPDATE
**Rozwiązanie**:
```php
// Inject id BEFORE arrayToXml()
if (isset($productData['product'])) {
    $productData['product'] = array_merge(
        ['id' => $productId],
        $productData['product']
    );
}
```

---

### Faza 4 Problems

#### Problem: "Start tag expected, '<' not found"
**Objawy**: 500 error from PrestaShop
**Rozwiązanie**:
```php
// Verify you're sending XML, not JSON
$response = $client->makeRequest('POST', '/products', [], [
    'body' => $xmlBody,  // ← Must be XML string, not array!
    'headers' => ['Content-Type' => 'application/xml'],  // ← CRITICAL
]);
```

#### Problem: "This product does not exist"
**Objawy**: 404 error on UPDATE
**Rozwiązanie**:
```php
// Verify product exists before UPDATE
try {
    $existing = $client->makeRequest('GET', "/products/{$productId}");
} catch (PrestaShopApiException $e) {
    if ($e->getCode() === 404) {
        // Product doesn't exist - use CREATE instead of UPDATE
        return $this->createProduct($productData);
    }
}
```

---

### Cross-Phase Issues

#### Problem: "Encoding issues - special characters broken"
**Objawy**: ą, ę, ć, ń displayed as � or broken
**Rozwiązanie**:
```php
// Ensure UTF-8 encoding throughout
1. Database connection: 'charset' => 'utf8mb4'
2. XML declaration: <?xml version="1.0" encoding="UTF-8"?>
3. PHP: mb_internal_encoding('UTF-8')
4. CDATA wrapping handles special chars automatically
```

#### Problem: "Rate limiting - 429 Too Many Requests"
**Objawy**: 429 error after many requests
**Rozwiązanie**:
```php
// Implement rate limiting
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::attempt(
    "prestashop-api:{$shop->id}",
    $perMinute = 60,
    function() use ($client, $endpoint, $data) {
        return $client->makeRequest('POST', $endpoint, [], $data);
    }
);
```

---

## 📖 BEST PRACTICES

### ✅ DO:
- **Use arrayToXml()** dla WSZYSTKICH POST/PUT/PATCH operations
- **Remove readonly fields** w Fazie 1 (manufacturer_name, date_add, etc)
- **Cache manufacturer lookups** aby uniknąć repeated API calls
- **Inject id for UPDATE** zawsze przed XML conversion
- **Log all API operations** dla debugowania
- **Validate required fields** przed wysyłką (name, price, id_category_default)
- **Use multilang format** dla text fields: `[['id' => 1, 'value' => '...']]`
- **Wrap values in CDATA** (arrayToXml robi to automatically)
- **Test with simple data** przed complex structures
- **Read _DOCS/PRESTASHOP_XML_SCHEMA_REFERENCE.md** przed implementacją

### ❌ DON'T:
- NIE wysyłaj JSON do PrestaShop POST/PUT/PATCH (tylko XML!)
- NIE wysyłaj readonly fields (manufacturer_name, supplier_name, date_add, date_upd)
- NIE skipuj manufacturer lookup (use getManufacturerId())
- NIE wysyłaj id dla CREATE (only for UPDATE)
- NIE używaj arbitrary text bez multilang wrapper
- NIE hardcoduj language IDs (use shop configuration)
- NIE ignoruj 400 errors (they indicate data problems)
- NIE retry 400 errors bez fixing data (będzie fail again)
- NIE deploy bez testowania na dev shop first

---

## 📊 MONITORING I METRYKI

### Real-time Tracking
```php
// Track w job lub service
Log::info('PrestaShop XML workflow started', [
    'phase' => 1,
    'product_sku' => $product->sku,
    'operation' => 'CREATE/UPDATE',
]);

// Track phase transitions
Log::debug('Phase 1 → Phase 2', ['data_size' => count($cleanData)]);
Log::debug('Phase 2 → Phase 3', ['xml_size_bytes' => strlen($xmlBody)]);
Log::debug('Phase 3 → Phase 4', ['manufacturer_id' => $manufacturerId]);

// Track completion
Log::info('PrestaShop XML workflow completed', [
    'prestashop_id' => $response['product']['id'],
    'execution_time_ms' => $executionTime,
]);
```

### Success Metrics
- **Phase 1 Success Rate**: 100% (pure data preparation)
- **Phase 2 Success Rate**: 100% (pure XML conversion)
- **Phase 3 Success Rate**: >98% (includes external manufacturer lookup)
- **Phase 4 Success Rate**: >95% (includes network request)
- **End-to-End Success Rate**: >92%
- **Average Execution Time**: <3 seconds
- **Manufacturer Lookup Time**: <500ms (with caching)

---

## 📊 SYSTEM UCZENIA SIĘ (Automatyczny)

### Tracking Informacji
Automatycznie zbierane:
- Execution time każdej fazy
- Success/failure każdej fazy
- Manufacturer lookup hit/miss ratio
- Most common errors (400/500)
- Retry frequency

### Metryki Sukcesu
- Success rate target: 95% (end-to-end)
- Max execution time: 5 seconds
- User satisfaction target: 4.5/5
- Manufacturer lookup cache hit rate: >80%

### Historia Ulepszeń
<!-- Automatycznie generowane przy każdej aktualizacji -->

---

## 📝 CHANGELOG

### v1.0.0 (2025-11-05)
- [INIT] Początkowa wersja PrestaShop XML Integration workflow
- [FEATURE] 4-fazowy workflow (Prepare → Convert → Special Cases → Send)
- [FEATURE] arrayToXml() implementation z 5 helper methods
- [FEATURE] Manufacturer auto-lookup + auto-create
- [FEATURE] CREATE vs UPDATE differentiation (id injection)
- [FEATURE] Readonly fields filtering
- [FEATURE] Multilang fields support
- [FEATURE] Comprehensive error handling (400, 404, 500)
- [DOCS] Complete examples, troubleshooting, best practices
- [DOCS] Based on _DOCS/PRESTASHOP_XML_SCHEMA_REFERENCE.md

---

## 🏁 PODSUMOWANIE

PrestaShop XML Integration Workflow zapewnia **systematyczne i niezawodne** podejście do integracji z PrestaShop Web Services API, które:

**Kluczowe korzyści**:
- ✅ Eliminuje błędy 500 "Start tag expected" przez proper XML format
- ✅ Eliminuje błędy 400 "not writable" przez readonly fields filtering
- ✅ Automatyzuje manufacturer/supplier lookup z auto-create fallback
- ✅ Obsługuje różnice CREATE vs UPDATE (id injection)
- ✅ Zapewnia proper CDATA wrapping i multilang support
- ✅ Comprehensive error handling dla typowych problemów
- ✅ Rollback capability dla każdej fazy

**Następne kroki po ukończeniu workflow**:
1. Monitor PrestaShop Admin Panel → Product created/updated correctly
2. Verify data w PrestaShop database
3. Test frontend display na sklepie
4. Update ProductShopData sync_status = 'synced'
5. Implement cache dla manufacturer lookups (optional optimization)

**Resources**:
- Official docs: devdocs.prestashop-project.org/8/webservice/
- Project docs: _DOCS/PRESTASHOP_XML_SCHEMA_REFERENCE.md
- Context7 library: /prestashop/docs (trust score 8.2)

---

**Happy PrestaShop Integrating! 🚀**
