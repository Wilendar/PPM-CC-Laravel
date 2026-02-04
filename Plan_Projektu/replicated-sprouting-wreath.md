# PLAN: Naprawa pobierania danych ERP (Ceny i Stany magazynowe) w ProductForm

**Data:** 2026-01-21
**Produkt testowy:** https://ppm.mpptrade.pl/admin/products/11183/edit
**Status:** PLAN

---

## 1. IDENTYFIKACJA PROBLEMU

### Symptomy
- Zakładki "Stany magazynowe" i "Ceny" w ProductForm NIE pokazują danych z ERP
- Dane ERP są wyświetlane jako "Tylko odczyt" w panelu `erp-connection-data.blade.php`
- Formularze cen i stanów (`prices-tab.blade.php`, `stock-tab.blade.php`) pozostają puste

### Root Cause Analysis

**Problem jest w `ProductFormERPTabs.php::overrideFormFieldsWithErpData()` (linie 261-341):**

Ta metoda ładuje z `external_data` TYLKO pola podstawowe:
- Basic: sku, name, ean, manufacturer, supplier_code
- Descriptions: short_description, long_description, meta_title, meta_description
- Physical: weight, height, width, length, tax_rate, is_active

**BRAKUJE mapowania:**
- `external_data['prices']` → `$this->prices[$groupId]` (Livewire property)
- `external_data['stock']` → `$this->stock[$warehouseId]` (Livewire property)

### Dowody w kodzie
1. `erp-connection-data.blade.php:262-284` - pokazuje stock jako "Tylko odczyt" z `$externalData['stock']`
2. `erp-connection-data.blade.php:287-304` - pokazuje prices jako "Tylko odczyt" z `$externalData['prices']`
3. `prices-tab.blade.php:83` - używa `wire:model.defer="prices.{{ $groupId }}.net"` z `$this->prices`
4. `stock-tab.blade.php:87` - używa `wire:model.defer="stock.{{ $warehouseId }}.quantity"` z `$this->stock`

---

## 1.5 SZCZEGÓŁOWA ANALIZA - DLACZEGO BRAKUJE DANYCH

### Problem w `updateProductErpDataFromRestApi()` (linia 1257-1293)

Metoda zapisuje do `external_data` tylko **pojedyncze wartości**:
```php
$externalData = [
    'price_net' => $subiektProduct->priceNet,      // TYLKO JEDNA CENA!
    'price_gross' => $subiektProduct->priceGross,  // TYLKO JEDNA CENA!
    'stock_quantity' => $subiektProduct->stock,    // TYLKO JEDEN STAN!
    // ... brak tablicy prices[] i stock[]
];
```

**BRAKUJE:**
```php
'prices' => [
    0 => ['net' => 100.00, 'gross' => 123.00],  // price_level 0
    1 => ['net' => 120.00, 'gross' => 147.60],  // price_level 1
    // ...
],
'stock' => [
    1 => ['quantity' => 50, 'reserved' => 10],  // warehouse_id 1
    2 => ['quantity' => 30, 'reserved' => 5],   // warehouse_id 2
    // ...
],
```

### Źródła danych w Subiekt GT REST API

| Endpoint | Zwraca |
|----------|--------|
| `/api/products/{id}` | Pojedynczą cenę dla domyślnego priceLevel |
| `/api/prices/{id}` | **WSZYSTKIE** ceny dla produktu (11 poziomów) |
| `/api/stock/{id}` | **WSZYSTKIE** stany magazynowe |

**Wniosek:** Trzeba wywołać dodatkowe endpointy `/api/prices/{id}` i `/api/stock/{id}` podczas pull.

---

## 2. PLAN IMPLEMENTACJI

### ETAP 1: Dodać metody mapowania ERP → PPM

**Plik:** `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php`

#### 1.1 Dodać helper do mapowania price levels

```php
/**
 * Map ERP price level to PPM price_group_id using connection mappings
 *
 * @param int|string $erpPriceLevel ERP price level (0-10 for Subiekt GT)
 * @param ERPConnection $connection
 * @return int|null PPM price_group_id or null if no mapping
 */
protected function mapErpPriceLevelToPpmGroup($erpPriceLevel, ERPConnection $connection): ?int
{
    $config = $connection->connection_config ?? [];
    $mappings = $config['price_group_mappings'] ?? [];

    // Mappings format: ['ppm_group_id' => 'erp_price_level', ...]
    $ppmGroupId = array_search((string)$erpPriceLevel, array_map('strval', $mappings));

    return $ppmGroupId !== false ? (int)$ppmGroupId : null;
}
```

#### 1.2 Dodać helper do mapowania warehouses

```php
/**
 * Map ERP warehouse to PPM warehouse_id using connection mappings
 *
 * @param int|string $erpWarehouseId ERP warehouse ID
 * @param ERPConnection $connection
 * @return int|null PPM warehouse_id or null if no mapping
 */
protected function mapErpWarehouseToPpmWarehouse($erpWarehouseId, ERPConnection $connection): ?int
{
    $config = $connection->connection_config ?? [];
    $mappings = $config['warehouse_mappings'] ?? [];

    // Mappings format: ['ppm_warehouse_id' => 'erp_warehouse_id', ...]
    $ppmWarehouseId = array_search((string)$erpWarehouseId, array_map('strval', $mappings));

    return $ppmWarehouseId !== false ? (int)$ppmWarehouseId : null;
}
```

### ETAP 2: Rozszerzyć `overrideFormFieldsWithErpData()`

**Plik:** `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php`
**Lokalizacja:** Po linii 340 (przed zamknięciem metody)

```php
// === PRICES FROM ERP ===
$externalPrices = $erpData->external_data['prices'] ?? [];
if (!empty($externalPrices)) {
    $connection = $erpData->erpConnection ?? ERPConnection::find($erpData->erp_connection_id);

    foreach ($externalPrices as $erpPriceLevel => $priceData) {
        $ppmGroupId = $this->mapErpPriceLevelToPpmGroup($erpPriceLevel, $connection);

        if ($ppmGroupId !== null && isset($this->prices[$ppmGroupId])) {
            // Handle different price data formats
            if (is_array($priceData)) {
                $this->prices[$ppmGroupId]['net'] = $priceData['net'] ?? $priceData['price_net'] ?? null;
                $this->prices[$ppmGroupId]['gross'] = $priceData['gross'] ?? $priceData['price_gross'] ?? null;
            } else {
                // Simple numeric value = net price
                $this->prices[$ppmGroupId]['net'] = (float)$priceData;
                // Calculate gross using tax_rate
                $taxRate = $this->tax_rate ?? 23.0;
                $this->prices[$ppmGroupId]['gross'] = round((float)$priceData * (1 + $taxRate / 100), 2);
            }
        }
    }

    Log::debug('ERP prices loaded to form', [
        'product_id' => $this->product->id,
        'erp_prices_count' => count($externalPrices),
        'mapped_prices' => array_filter($this->prices, fn($p) => $p['net'] !== null),
    ]);
}

// === STOCK FROM ERP ===
$externalStock = $erpData->external_data['stock'] ?? [];
if (!empty($externalStock)) {
    $connection = $erpData->erpConnection ?? ERPConnection::find($erpData->erp_connection_id);

    foreach ($externalStock as $erpWarehouseId => $stockData) {
        $ppmWarehouseId = $this->mapErpWarehouseToPpmWarehouse($erpWarehouseId, $connection);

        if ($ppmWarehouseId !== null && isset($this->stock[$ppmWarehouseId])) {
            if (is_array($stockData)) {
                $this->stock[$ppmWarehouseId]['quantity'] = $stockData['quantity'] ?? $stockData['available'] ?? 0;
                $this->stock[$ppmWarehouseId]['reserved'] = $stockData['reserved'] ?? 0;
            } else {
                // Simple numeric value = quantity
                $this->stock[$ppmWarehouseId]['quantity'] = (int)$stockData;
            }
        }
    }

    Log::debug('ERP stock loaded to form', [
        'product_id' => $this->product->id,
        'erp_stock_count' => count($externalStock),
        'mapped_stock' => array_filter($this->stock, fn($s) => $s['quantity'] > 0),
    ]);
}
```

### ETAP 3: Naprawić `pullProductDataFromErp()` dla Subiekt GT

**Plik:** `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php`
**Problem:** Metoda używa tylko `BaselinkerService` - trzeba dodać obsługę Subiekt GT

**Zmiana w metodzie `pullProductDataFromErp()` (linia ~498):**

```php
// Zamiast hardcoded BaselinkerService:
// $service = app(BaselinkerService::class);

// Użyj factory pattern:
$service = match ($connection->erp_type) {
    'baselinker' => app(BaselinkerService::class),
    'subiekt_gt' => app(\App\Services\ERP\SubiektGTService::class),
    'dynamics' => app(\App\Services\ERP\DynamicsService::class),
    default => throw new \RuntimeException("Unknown ERP type: {$connection->erp_type}"),
};

$result = $service->syncProductFromERP($connection, $erpData->external_id);
```

### ETAP 4: Naprawić `updateProductErpDataFromRestApi()` - dodać prices i stock

**Plik:** `app/Services/ERP/SubiektGTService.php`
**Metoda:** `updateProductErpDataFromRestApi()` (linia 1257)

**Problem:** Metoda nie pobiera wszystkich cen i stanów.

**Rozwiązanie:** Dodać wywołania do `/api/prices/{id}` i `/api/stock/{id}`:

```php
protected function updateProductErpDataFromRestApi(Product $product, ERPConnection $connection, object $subiektProduct): ProductErpData
{
    // ETAP 4 FIX: Pobierz WSZYSTKIE ceny i stany
    $allPrices = [];
    $allStock = [];

    if ($this->restApiClient && isset($subiektProduct->id)) {
        try {
            // Fetch all prices (11 levels)
            $pricesResponse = $this->restApiClient->getProductPrices($subiektProduct->id);
            if (!empty($pricesResponse['data'])) {
                foreach ($pricesResponse['data'] as $priceData) {
                    $level = $priceData['price_level'] ?? $priceData['priceLevel'] ?? null;
                    if ($level !== null) {
                        $allPrices[$level] = [
                            'net' => $priceData['price_net'] ?? $priceData['priceNet'] ?? null,
                            'gross' => $priceData['price_gross'] ?? $priceData['priceGross'] ?? null,
                        ];
                    }
                }
            }

            // Fetch all stock levels
            $stockResponse = $this->restApiClient->getProductStock($subiektProduct->id);
            if (!empty($stockResponse['data'])) {
                foreach ($stockResponse['data'] as $stockData) {
                    $warehouseId = $stockData['warehouse_id'] ?? $stockData['warehouseId'] ?? null;
                    if ($warehouseId !== null) {
                        $allStock[$warehouseId] = [
                            'quantity' => $stockData['quantity'] ?? $stockData['available'] ?? 0,
                            'reserved' => $stockData['reserved'] ?? 0,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch all prices/stock from Subiekt API', [
                'product_id' => $subiektProduct->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    $externalData = [
        // ... existing fields ...
        'prices' => $allPrices,   // NOWE: tablica wszystkich cen
        'stock' => $allStock,     // NOWE: tablica wszystkich stanów
    ];

    // ... rest of method
}
```

### ETAP 5: Upewnić się, że REST API zwraca pełne dane

**Plik:** `_TOOLS/SubiektGT_REST_API_DotNet/Program.cs`

Sprawdzić endpointy:
- `GET /api/prices/{productId}` - musi zwracać wszystkie 11 poziomów cen
- `GET /api/stock/{productId}` - musi zwracać stany dla wszystkich magazynów

**Format odpowiedzi `/api/prices/{id}`:**
```json
{
    "success": true,
    "data": [
        {"price_level": 0, "price_net": 100.00, "price_gross": 123.00},
        {"price_level": 1, "price_net": 120.00, "price_gross": 147.60},
        ...
    ]
}
```

**Format odpowiedzi `/api/stock/{id}`:**
```json
{
    "success": true,
    "data": [
        {"warehouse_id": 1, "warehouse_name": "MPPTRADE", "quantity": 50, "reserved": 10},
        {"warehouse_id": 2, "warehouse_name": "Pitbike", "quantity": 30, "reserved": 5},
        ...
    ]
}
```

---

## 3. PLIKI DO MODYFIKACJI

| Plik | Zmiany | Priorytet |
|------|--------|-----------|
| `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php` | Dodać helper methods + mapowanie prices/stock w `overrideFormFieldsWithErpData()` + factory pattern w `pullProductDataFromErp()` | HIGH |
| `app/Services/ERP/SubiektGTService.php` | Rozszerzyć `updateProductErpDataFromRestApi()` o pobieranie wszystkich prices/stock | HIGH |
| `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` | Dodać metody `getProductPrices()` i `getProductStock()` jeśli brakuje | MEDIUM |
| `_TOOLS/SubiektGT_REST_API_DotNet/Program.cs` | Sprawdzić/naprawić endpointy `/api/prices/{id}` i `/api/stock/{id}` | LOW (jeśli działa) |

---

## 4. MAPOWANIA ERP → PPM

### Subiekt GT Price Levels → PPM Price Groups
```
Subiekt GT price_level (0-10) → PPM price_group_id
Konfigurowane w: ERPConnection.connection_config['price_group_mappings']
Format: { "ppm_group_id": "erp_price_level" }
```

### Subiekt GT Warehouses → PPM Warehouses
```
Subiekt GT mag_Id → PPM warehouse_id
Konfigurowane w: ERPConnection.connection_config['warehouse_mappings']
Format: { "ppm_warehouse_id": "erp_warehouse_id" }
```

---

## 5. WERYFIKACJA

### Test Case 1: Sprawdzenie danych ERP
1. Otworzyć produkt 11183: https://ppm.mpptrade.pl/admin/products/11183/edit
2. Przejść do zakładki ERP → wybrać połączenie Subiekt GT
3. Kliknąć "Pobierz z ERP"
4. Sprawdzić w konsoli Laravel logs czy `external_data` zawiera `prices` i `stock`

### Test Case 2: Weryfikacja mapowania
1. Po pobraniu danych z ERP
2. Przejść do zakładki "Ceny" - powinny być widoczne ceny z ERP
3. Przejść do zakładki "Stany magazynowe" - powinny być widoczne stany z ERP

### Test Case 3: Chrome DevTools verification
```javascript
// MCP verification steps
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/products/11183/edit" })
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "price input fields" })
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, onlyErrors: true })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

---

## 6. RYZYKA I UWAGI

### Ryzyko 1: Brak skonfigurowanych mapowań
**Mitygacja:** Dodać walidację i komunikat gdy mapowania są puste

### Ryzyko 2: Różne formaty danych z różnych ERP
**Mitygacja:** Obsłużyć różne formaty w kodzie (array vs scalar)

### Ryzyko 3: Nadpisanie lokalnych zmian użytkownika
**Mitygacja:** Dane ERP ładowane tylko przy explicit PULL action, nie automatycznie

---

## 7. ESTYMACJA

- ETAP 1 (helpers): ~30 linii kodu
- ETAP 2 (override prices/stock): ~50 linii kodu
- ETAP 3 (service factory): ~10 linii kodu
- ETAP 4 (SubiektGTService check): ~weryfikacja istniejącego kodu

**Razem:** ~100 linii kodu + testy

---

## 8. KOLEJNOŚĆ IMPLEMENTACJI

1. ✅ Analiza problemu (DONE)
2. ❌ **SubiektGTService** - rozszerzyć `updateProductErpDataFromRestApi()` o pobieranie ALL prices/stock
3. ❌ **ProductFormERPTabs** - dodać helper methods `mapErpPriceLevelToPpmGroup()` i `mapErpWarehouseToPpmWarehouse()`
4. ❌ **ProductFormERPTabs** - rozszerzyć `overrideFormFieldsWithErpData()` o mapowanie prices i stock
5. ❌ **ProductFormERPTabs** - naprawić `pullProductDataFromErp()` - factory pattern dla Subiekt GT
6. ❌ Lokalny test - sprawdzić pobieranie danych z API
7. ❌ Deploy do produkcji
8. ❌ Chrome DevTools verification na https://ppm.mpptrade.pl/admin/products/11183/edit
9. ❌ Usunięcie debug logów po potwierdzeniu "działa idealnie"
