---
name: baselinker-api-integration
description: "Use when integrating with Baselinker API - critical knowledge about text_fields format, parameter encoding, and common pitfalls"
version: 1.2.0
author: Claude Code
created: 2026-01-19
updated: 2026-01-19
tags: [baselinker, erp, api, integration, sync]
---

# Baselinker API Integration Skill

## Overview

Ten skill zawiera **KRYTYCZNĄ wiedzę** o integracji z Baselinker API, szczególnie o formacie parametrów i typowych błędach które powodują że API zwraca SUCCESS ale dane NIE SĄ aktualizowane.

---

## KRYTYCZNE ZASADY

### 1. ZAKAZ PODWÓJNEGO JSON ENCODING

**PROBLEM:** Jeśli `makeRequest()` już wykonuje `json_encode($parameters)`, to przekazywanie `text_fields` jako JSON string powoduje **PODWÓJNE ENKODOWANIE**.

```php
// ❌ BŁĘDNE - podwójne enkodowanie!
$textFields = json_encode([
    'name' => $productData['name'],
    'description' => $productData['description'],
]);

$response = $this->makeRequest($config, 'addInventoryProduct', [
    'text_fields' => $textFields,  // STRING -> zostanie ponownie JSON encoded!
]);

// WYNIK: {"text_fields":"{\"name\":\"Product\",\"description\":\"Desc\"}"}
// API interpretuje jako LITERAL STRING, nie jako obiekt!
```

```php
// ✅ POPRAWNE - PHP array
$textFields = [
    'name' => $productData['name'],
    'description' => $productData['description'],
];

$response = $this->makeRequest($config, 'addInventoryProduct', [
    'text_fields' => $textFields,  // ARRAY -> poprawnie JSON encoded
]);

// WYNIK: {"text_fields":{"name":"Product","description":"Desc"}}
// API poprawnie interpretuje jako nested object!
```

### 2. OBJAW BŁĘDU

**KRYTYCZNE:** API zwraca `status: SUCCESS` ale produkt **NIE JEST aktualizowany** w Baselinker!

Jeśli widzisz:
- Log pokazuje "SUCCESS"
- Dane w Baselinker pozostają niezmienione
- Brak error message

**PRZYCZYNA:** Prawie na pewno podwójne JSON encoding parametrów.

### 3. CREATE vs UPDATE: `product_id` Behavior

**KRYTYCZNE:** `product_id` określa czy to CREATE czy UPDATE!

```php
// ❌ BŁĘDNE dla CREATE - przekazywanie SKU jako product_id
$response = $this->makeRequest($config, 'addInventoryProduct', [
    'inventory_id' => $inventoryId,
    'product_id' => $productData['sku'],  // BL szuka produktu o tym ID i NIE ZNAJDUJE!
    // ...
]);

// WYNIK: ERROR_PRODUCT_ID - "No product with ID [SKU]"
```

```php
// ✅ POPRAWNE dla CREATE - NIE przekazuj product_id
$response = $this->makeRequest($config, 'addInventoryProduct', [
    'inventory_id' => $inventoryId,
    // NO product_id! - Baselinker utworzy nowy produkt i przypisze ID
    'sku' => $productData['sku'],
    // ...
]);

// WYNIK: SUCCESS, product_id = nowo utworzony ID z Baselinker
```

**ZASADA:**
| Operacja | product_id | Rezultat |
|----------|------------|----------|
| **CREATE** | **PUSTY/brak** | BL tworzy nowy produkt i zwraca nowe ID |
| **UPDATE** | **ID z BL** | BL aktualizuje istniejący produkt |
| CREATE z SKU | ❌ | ERROR_PRODUCT_ID - BL szuka tego ID |

### 4. IMAGES FORMAT - WYMAGANY PREFIX `url:`

**KRYTYCZNE:** Baselinker API wymaga specyficznego formatu dla obrazków!

```php
// ❌ BŁĘDNE - surowy URL
$imagesObject->{(string)$index} = 'https://example.com/image.jpg';

// WYNIK: API zwraca "Invalid data format for images."
```

```php
// ✅ POPRAWNE - z prefixem "url:"
$imagesObject->{(string)$index} = 'url:https://example.com/image.jpg';

// WYNIK: SUCCESS - obrazek załadowany do Baselinker
```

**LIMIT:** Maksymalnie **16 obrazków** (pozycje 0-15). Więcej = błąd "Too many pictures provided (limit 16)".

**FORMAT WARTOŚCI:**
| Prefix | Opis | Limit |
|--------|------|-------|
| `url:` | URL obrazka (musi być publiczny) | 1000 znaków |
| `data:` | Base64 encoded obrazek | 2MB |
| `""` | Pusty string - usuń obrazek z pozycji | - |

**STRUKTURA `images`:**
```php
// Obiekt (NIE array!) z numerycznymi kluczami 0-15
$imagesObject = new \stdClass();
$imagesObject->{'0'} = 'url:https://example.com/img1.jpg';
$imagesObject->{'1'} = 'url:https://example.com/img2.jpg';
$imagesObject->{'5'} = '';  // Usuń obrazek z pozycji 5

// JSON result: {"0":"url:https://...","1":"url:https://...","5":""}
```

**DLACZEGO stdClass?**
PHP array z sekwencyjnymi kluczami numerycznymi (`[0 => 'a', 1 => 'b']`) enkoduje się jako JSON array `["a","b"]`.
Baselinker wymaga JSON object `{"0":"a","1":"b"}` - użyj `stdClass`!

---

### 5. FORMAT `text_fields` wg Dokumentacji Baselinker

```php
// Baselinker API: addInventoryProduct / updateInventoryProduct
// text_fields to OBIEKT z kluczami w formacie: field_name|lang|source_id

$textFields = [
    'name' => 'Product Name',                    // Domyślna nazwa
    'name|de' => 'Produktname',                  // Nazwa niemiecka
    'name|en|amazon_0' => 'Amazon Product Name', // Nazwa dla Amazon EN
    'description' => 'Product description',
    'description_extra1' => 'Short description',
    'description_extra2' => 'Extra field 2',
    'description_extra3' => 'Extra field 3',
    'description_extra4' => 'Extra field 4',
];
```

---

## IMPLEMENTACJA W PPM

### BaselinkerService.php - Prawidłowy wzorzec

```php
/**
 * app/Services/ERP/BaselinkerService.php
 */
protected function createBaselinkerProduct(ERPConnection $connection, Product $product, string $inventoryId): array
{
    $productData = $this->buildBaselinkerProductData($connection, $product);

    // CRITICAL: text_fields as PHP array - makeRequest() calls json_encode($parameters)
    // so passing array here results in proper nested JSON: {"text_fields":{"name":"..."}}
    // NOT double-encoded: {"text_fields":"{\"name\":\"...\"}"}
    $textFields = [
        'name' => $productData['name'],
        'description' => $productData['description'],
        'description_extra1' => $productData['description_extra1'],
    ];

    $response = $this->makeRequest(
        $connection->connection_config,
        'addInventoryProduct',
        [
            'inventory_id' => $inventoryId,
            'product_id' => $productData['sku'],
            'text_fields' => $textFields,  // PHP array - proper format!
            'sku' => $productData['sku'],
            'ean' => $productData['ean'],
            // ... other fields
        ]
    );
}
```

### makeRequest() - Jeden json_encode dla całości

```php
protected function makeRequest(array $config, string $method, array $parameters): array
{
    $response = Http::timeout($this->timeout)
        ->asForm()
        ->post($this->baseUrl, [
            'token' => $config['api_token'],
            'method' => $method,
            'parameters' => json_encode($parameters)  // TUTAJ jest JSON encode!
        ]);

    // ...
}
```

---

## DEBUGOWANIE

### Sprawdzenie co jest wysyłane do API

```php
Log::debug('Baselinker API request', [
    'method' => $method,
    'parameters_raw' => $parameters,
    'parameters_json' => json_encode($parameters),
    'text_fields_type' => gettype($parameters['text_fields'] ?? null),
]);
```

### Czerwone flagi w logach

```
// ❌ BŁĘDNE - text_fields jako string z escaped quotes
"text_fields": "{\"name\":\"Product Name\"}"

// ✅ POPRAWNE - text_fields jako nested object
"text_fields": {"name": "Product Name"}
```

---

## BASELINKER API REFERENCE

### addInventoryProduct

| Parametr | Typ | Opis |
|----------|-----|------|
| `inventory_id` | string | ID magazynu |
| `product_id` | string | ID produktu (może być SKU) |
| `parent_id` | int | 0 dla głównego produktu |
| `is_bundle` | bool | Czy jest zestawem |
| `ean` | string | Kod EAN |
| `sku` | string | SKU produktu |
| `tax_rate` | float | Stawka VAT (23, 8, 5, 0) |
| `weight` | float | Waga w kg |
| `height` | float | Wysokość w cm |
| `width` | float | Szerokość w cm |
| `length` | float | Długość w cm |
| `text_fields` | **object** | Pola tekstowe (MUSI być obiekt!) |
| `images` | array | Tablica URL obrazków |
| `links` | object | Powiązania z kanałami |
| `category_id` | int | ID kategorii BL |

### Pola text_fields

| Klucz | Opis |
|-------|------|
| `name` | Nazwa produktu |
| `description` | Pełny opis HTML |
| `description_extra1` | Krótki opis |
| `description_extra2-4` | Dodatkowe pola |
| `name\|LANG` | Nazwa w języku (pl, en, de, etc.) |
| `name\|LANG\|SOURCE` | Nazwa dla konkretnego źródła |

---

## HISTORIA BŁĘDÓW I ROZWIĄZAŃ

### BUG #1: Product not updating despite SUCCESS (2026-01-19)

**Symptom:**
- API zwraca `status: SUCCESS`
- Produkt w Baselinker NIE jest aktualizowany
- Brak error message

**Root Cause:**
`text_fields` przekazywane jako `json_encode([...])` zamiast PHP array.
`makeRequest()` robi kolejny `json_encode($parameters)` = podwójne enkodowanie.

**Solution:**
```php
// PRZED (BŁĘDNE)
$textFields = json_encode(['name' => $name]);

// PO (POPRAWNE)
$textFields = ['name' => $name];
```

**Files Fixed:**
- `app/Services/ERP/BaselinkerService.php:createBaselinkerProduct()`
- `app/Services/ERP/BaselinkerService.php:updateBaselinkerProduct()`

### BUG #2: ERROR_PRODUCT_ID when creating new product (2026-01-19)

**Symptom:**
- API zwraca `status: ERROR`
- `error_code: ERROR_PRODUCT_ID`
- `error_message: No product with ID [SKU]`

**Root Cause:**
Kod przekazywał `product_id => $productData['sku']` przy CREATE nowego produktu.
Baselinker interpretuje `product_id` jako **istniejący ID produktu** do UPDATE.
Gdy produkt nie istnieje → ERROR.

**Solution:**
```php
// PRZED (BŁĘDNE) - dla CREATE
'product_id' => $productData['sku'],  // BL szuka tego ID!

// PO (POPRAWNE) - dla CREATE
// NIE przekazuj product_id! Baselinker nada nowe ID
```

**Files Fixed:**
- `app/Services/ERP/BaselinkerService.php:createBaselinkerProduct()`

### BUG #3: Invalid data format for images (2026-01-19)

**Symptom:**
- API zwraca `status: ERROR`
- `error_code: ERROR_INVALID_DATA`
- `error_message: Invalid data format for images.`
- Produkt NIE jest tworzony/aktualizowany

**Root Cause:**
Obrazki były przekazywane jako surowe URL bez wymaganego prefixu `url:`.
Baselinker API wymaga format: `"url:https://example.com/image.jpg"`, NIE `"https://example.com/image.jpg"`.

**Solution:**
```php
// PRZED (BŁĘDNE)
$imagesObject->{(string)$index} = $imageUrl;

// PO (POPRAWNE) - dodaj prefix "url:"
$imagesObject->{(string)$index} = 'url:' . $imageUrl;
```

**Dodatkowy problem - JSON array vs object:**
PHP array z sekwencyjnymi numerycznymi kluczami staje się JSON array `[]`.
Baselinker wymaga JSON object `{}` - użyj `stdClass`.

**Files Fixed:**
- `app/Services/ERP/BaselinkerService.php:buildBaselinkerProductData()`

---

## CHECKLIST PRZED IMPLEMENTACJĄ

- [ ] `text_fields` jest PHP array, NIE json_encode() string
- [ ] `makeRequest()` robi JEDEN json_encode dla całych $parameters
- [ ] Logi pokazują `text_fields` jako nested object, nie escaped string
- [ ] **CREATE: NIE przekazuj product_id** (BL nada nowy ID)
- [ ] **UPDATE: przekazuj product_id z BL** (NIE SKU!)
- [ ] **IMAGES: używaj stdClass** (nie PHP array) dla JSON object format
- [ ] **IMAGES: dodaj prefix `url:`** do każdego URL obrazka
- [ ] Przetestowano na produkcji że dane faktycznie się aktualizują
- [ ] Sprawdzono w panelu Baselinker że zmiany są widoczne

---

## LINKI

- [Baselinker API Docs](https://api.baselinker.com/)
- [addInventoryProduct](https://api.baselinker.com/index.php?method=addInventoryProduct)
- [updateInventoryProductsStock](https://api.baselinker.com/index.php?method=updateInventoryProductsStock)
- [getInventoryProductsData](https://api.baselinker.com/index.php?method=getInventoryProductsData)

---

## CHANGELOG

### v1.2.0 (2026-01-19)
- [CRITICAL] Dodano zasadę #4: IMAGES format - wymagany prefix `url:`
- [CRITICAL] Udokumentowano wymaganie używania `stdClass` dla images (JSON object vs array)
- [HISTORY] Zapisano rozwiązanie BUG #3: Invalid data format for images
- [CHECKLIST] Dodano punkty dotyczące obrazków

### v1.1.0 (2026-01-19)
- [CRITICAL] Dodano zasadę #3: CREATE vs UPDATE product_id behavior
- [HISTORY] Zapisano rozwiązanie BUG #2: ERROR_PRODUCT_ID
- [CHECKLIST] Dodano punkty dotyczące product_id

### v1.0.0 (2026-01-19)
- [INIT] Stworzono skill z krytyczną wiedzą o text_fields format
- [CRITICAL] Udokumentowano bug podwójnego JSON encoding
- [DOCS] Dodano przykłady prawidłowej implementacji
- [HISTORY] Zapisano rozwiązanie BUG #1
