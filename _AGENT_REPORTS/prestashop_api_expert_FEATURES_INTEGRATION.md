# RAPORT INTEGRACJI: PrestaShop Features API

**Data:** 2025-12-02
**Agent:** prestashop-api-expert
**Zadanie:** Analiza struktury PrestaShop Features API i propozycja integracji z PPM-CC-Laravel

---

## EXECUTIVE SUMMARY

Przeanalizowano pełną architekturę systemu features w PrestaShop 8.x/9.x oraz PPM-CC-Laravel. Określono mapowanie między modelami, zaproponowano strategię synchronizacji dwukierunkowej oraz zdefiniowano XML templates dla wszystkich operacji CRUD.

**Status:** ✅ Analiza zakończona - gotowy blueprint do implementacji

**Kluczowe ustalenia:**
1. **Mapowanie 1:1 możliwe** - struktury PPM i PrestaShop są kompatybilne
2. **Custom values wspierane** - PrestaShop ma pole `custom` w `ps_feature_value`
3. **Multi-language REQUIRED** - wszystkie features/values muszą mieć tłumaczenia
4. **Conflict resolution potrzebny** - różne wartości, różne ID między sklepami

---

## 1. MAPOWANIE TABEL PPM ↔ PRESTASHOP

### 1.1 Feature Type (PPM) → ps_feature (PrestaShop)

**PPM: `feature_types`**
```sql
CREATE TABLE feature_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE,           -- Unikalny kod (power, capacity, color)
    name VARCHAR(255),                  -- Nazwa typu cechy
    value_type ENUM('text', 'number', 'bool', 'select'),
    unit VARCHAR(50) NULL,              -- Jednostka miary (W, L, kg)
    `group` VARCHAR(100) NULL,          -- Grupa (Podstawowe, Silnik, Wymiary)
    is_active BOOLEAN DEFAULT 1,
    position INT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**PrestaShop: `ps_feature` + `ps_feature_lang`**
```sql
CREATE TABLE ps_feature (
    id_feature INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE ps_feature_lang (
    id_feature INT UNSIGNED NOT NULL,
    id_lang INT UNSIGNED NOT NULL,
    name VARCHAR(128) DEFAULT NULL,
    PRIMARY KEY (id_feature, id_lang),
    KEY (id_lang, name)
);
```

**MAPOWANIE:**
| PPM Field | PrestaShop Field | Uwagi |
|-----------|------------------|-------|
| `id` | - | PPM internal ID (nie wysyłane) |
| `code` | - | **NIE MA ODPOWIEDNIKA w PS** - używany tylko w PPM |
| `name` | `ps_feature_lang.name` | **MULTI-LANGUAGE** (id_lang: 1=PL, 2=EN) |
| `position` | `ps_feature.position` | Kolejność wyświetlania |
| `value_type`, `unit`, `group`, `is_active` | - | **METADATA PPM** - nie synchronizowane |

**KLUCZOWA TABELA MAPOWANIA (nowa):**
```sql
CREATE TABLE shop_feature_mappings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,                    -- FK: prestashop_shops.id
    feature_type_id INT NOT NULL,            -- FK: feature_types.id
    prestashop_feature_id INT NOT NULL,      -- ID cechy w PrestaShop
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (shop_id, feature_type_id),
    UNIQUE KEY (shop_id, prestashop_feature_id)
);
```

---

### 1.2 Feature Value (PPM) → ps_feature_value (PrestaShop)

**PPM: `feature_values`**
```sql
CREATE TABLE feature_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    feature_type_id INT NOT NULL,          -- FK: feature_types.id
    value VARCHAR(255),                    -- Wartość (Czarny, Biały, 1000W)
    is_active BOOLEAN DEFAULT 1,
    position INT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**PrestaShop: `ps_feature_value` + `ps_feature_value_lang`**
```sql
CREATE TABLE ps_feature_value (
    id_feature_value INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_feature INT UNSIGNED NOT NULL,
    custom TINYINT UNSIGNED DEFAULT NULL,  -- 1 = custom value, NULL = predefined
    position INT UNSIGNED NOT NULL DEFAULT 0,
    KEY feature (id_feature)
);

CREATE TABLE ps_feature_value_lang (
    id_feature_value INT UNSIGNED NOT NULL,
    id_lang INT UNSIGNED NOT NULL,
    value VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id_feature_value, id_lang)
);
```

**MAPOWANIE:**
| PPM Field | PrestaShop Field | Uwagi |
|-----------|------------------|-------|
| `id` | - | PPM internal ID |
| `feature_type_id` | `ps_feature_value.id_feature` | **Via shop_feature_mappings** |
| `value` | `ps_feature_value_lang.value` | **MULTI-LANGUAGE** |
| `position` | `ps_feature_value.position` | Kolejność |
| `is_active` | - | **METADATA PPM** |
| - | `ps_feature_value.custom` | **NULL** (predefined values) |

**KLUCZOWA TABELA MAPOWANIA (nowa):**
```sql
CREATE TABLE shop_feature_value_mappings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,                        -- FK: prestashop_shops.id
    feature_value_id INT NOT NULL,               -- FK: feature_values.id
    prestashop_feature_value_id INT NOT NULL,    -- ID wartości w PrestaShop
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (shop_id, feature_value_id),
    UNIQUE KEY (shop_id, prestashop_feature_value_id)
);
```

---

### 1.3 Product Feature (PPM) → ps_feature_product (PrestaShop)

**PPM: `product_features`**
```sql
CREATE TABLE product_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,                -- FK: products.id
    feature_type_id INT NOT NULL,           -- FK: feature_types.id
    feature_value_id INT NULL,              -- FK: feature_values.id (jeśli select)
    custom_value VARCHAR(255) NULL,         -- Custom value (jeśli nie select)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (product_id, feature_type_id)
);
```

**PrestaShop: `ps_feature_product`**
```sql
CREATE TABLE ps_feature_product (
    id_feature INT UNSIGNED NOT NULL,
    id_product INT UNSIGNED NOT NULL,
    id_feature_value INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_feature, id_product, id_feature_value),
    KEY id_feature_value (id_feature_value),
    KEY id_product (id_product)
);
```

**MAPOWANIE:**
| PPM Field | PrestaShop Field | Uwagi |
|-----------|------------------|-------|
| `product_id` | `ps_feature_product.id_product` | **Via product_sync_status** |
| `feature_type_id` | `ps_feature_product.id_feature` | **Via shop_feature_mappings** |
| `feature_value_id` | `ps_feature_product.id_feature_value` | **Via shop_feature_value_mappings** |
| `custom_value` | **NEW ps_feature_value** | **Tworzy nowy custom value** (custom=1) |

**SCENARIUSZE:**

1. **Predefined value (feature_value_id SET):**
   - Użyj `shop_feature_value_mappings` → `id_feature_value`

2. **Custom value (custom_value SET):**
   - **CREATE** nowy `ps_feature_value` z `custom=1`
   - **INSERT** do `ps_feature_value_lang`
   - **USE** nowego `id_feature_value`

---

## 2. XML TEMPLATES - CRUD OPERATIONS

### 2.1 GET Operations

#### 2.1.1 List All Features
```http
GET /api/product_features?display=full
```

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_features>
        <product_feature id="1" xlink:href="https://example.com/api/product_features/1">
            <id>1</id>
            <position>0</position>
        </product_feature>
        <product_feature id="2" xlink:href="https://example.com/api/product_features/2">
            <id>2</id>
            <position>1</position>
        </product_feature>
    </product_features>
</prestashop>
```

#### 2.1.2 Get Single Feature with Languages
```http
GET /api/product_features/1
```

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature>
        <id>1</id>
        <position>0</position>
        <name>
            <language id="1"><![CDATA[Moc]]></language>
            <language id="2"><![CDATA[Power]]></language>
        </name>
    </product_feature>
</prestashop>
```

#### 2.1.3 List Feature Values for Feature
```http
GET /api/product_feature_values?filter[id_feature]=[5]&display=full
```

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_values>
        <product_feature_value id="15" xlink:href="https://example.com/api/product_feature_values/15">
            <id>15</id>
            <id_feature>5</id_feature>
            <custom></custom>
            <position>0</position>
        </product_feature_value>
        <product_feature_value id="16" xlink:href="https://example.com/api/product_feature_values/16">
            <id>16</id>
            <id_feature>5</id_feature>
            <custom>1</custom>
            <position>1</position>
        </product_feature_value>
    </product_feature_values>
</prestashop>
```

#### 2.1.4 Get Feature Value with Translations
```http
GET /api/product_feature_values/15
```

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id>15</id>
        <id_feature>5</id_feature>
        <custom></custom>
        <position>0</position>
        <value>
            <language id="1"><![CDATA[Czarny]]></language>
            <language id="2"><![CDATA[Black]]></language>
        </value>
    </product_feature_value>
</prestashop>
```

#### 2.1.5 Get Product Features (associations)
```http
GET /api/products/123?display=[associations,id]
```

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <id>123</id>
        <associations>
            <product_features>
                <product_feature>
                    <id>5</id>
                    <id_feature_value>15</id_feature_value>
                </product_feature>
                <product_feature>
                    <id>8</id>
                    <id_feature_value>42</id_feature_value>
                </product_feature>
            </product_features>
        </associations>
    </product>
</prestashop>
```

---

### 2.2 POST Operations (CREATE)

#### 2.2.1 Create Feature
```http
POST /api/product_features
Content-Type: application/xml
```

**Request Body:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature>
        <position>0</position>
        <name>
            <language id="1"><![CDATA[Moc]]></language>
            <language id="2"><![CDATA[Power]]></language>
        </name>
    </product_feature>
</prestashop>
```

**Response (201 Created):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature>
        <id>25</id>
        <position>0</position>
        <name>
            <language id="1"><![CDATA[Moc]]></language>
            <language id="2"><![CDATA[Power]]></language>
        </name>
    </product_feature>
</prestashop>
```

#### 2.2.2 Create Feature Value (Predefined)
```http
POST /api/product_feature_values
Content-Type: application/xml
```

**Request Body:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id_feature>25</id_feature>
        <custom></custom>
        <position>0</position>
        <value>
            <language id="1"><![CDATA[1000W]]></language>
            <language id="2"><![CDATA[1000W]]></language>
        </value>
    </product_feature_value>
</prestashop>
```

**Response (201 Created):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id>150</id>
        <id_feature>25</id_feature>
        <custom></custom>
        <position>0</position>
        <value>
            <language id="1"><![CDATA[1000W]]></language>
            <language id="2"><![CDATA[1000W]]></language>
        </value>
    </product_feature_value>
</prestashop>
```

#### 2.2.3 Create Feature Value (Custom)
```http
POST /api/product_feature_values
Content-Type: application/xml
```

**Request Body:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id_feature>25</id_feature>
        <custom>1</custom>
        <position>99</position>
        <value>
            <language id="1"><![CDATA[1250W (custom)]]></language>
            <language id="2"><![CDATA[1250W (custom)]]></language>
        </value>
    </product_feature_value>
</prestashop>
```

---

### 2.3 PUT Operations (UPDATE)

#### 2.3.1 Update Feature
```http
PUT /api/product_features/25
Content-Type: application/xml
```

**CRITICAL:** Use GET-MODIFY-PUT pattern!

**Step 1 - GET existing:**
```http
GET /api/product_features/25
```

**Step 2 - MERGE changes:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature>
        <id>25</id>
        <position>5</position>
        <name>
            <language id="1"><![CDATA[Moc silnika]]></language>
            <language id="2"><![CDATA[Engine Power]]></language>
        </name>
    </product_feature>
</prestashop>
```

**Step 3 - PUT:**
```http
PUT /api/product_features/25
```

#### 2.3.2 Update Feature Value
```http
PUT /api/product_feature_values/150
Content-Type: application/xml
```

**Request Body (GET-MODIFY-PUT):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id>150</id>
        <id_feature>25</id_feature>
        <custom></custom>
        <position>1</position>
        <value>
            <language id="1"><![CDATA[1000W (zmodyfikowane)]]></language>
            <language id="2"><![CDATA[1000W (modified)]]></language>
        </value>
    </product_feature_value>
</prestashop>
```

---

### 2.4 Associate Features to Product (via Product Update)

```http
PUT /api/products/123
Content-Type: application/xml
```

**CRITICAL:** Use GET-MODIFY-PUT + preserve existing associations!

**Request Body:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <id>123</id>
        <!-- ...other product fields... -->
        <associations>
            <product_features>
                <product_feature>
                    <id>25</id>
                    <id_feature_value>150</id_feature_value>
                </product_feature>
                <product_feature>
                    <id>8</id>
                    <id_feature_value>42</id_feature_value>
                </product_feature>
            </product_features>
            <!-- ...other associations... -->
        </associations>
    </product>
</prestashop>
```

---

### 2.5 DELETE Operations

#### 2.5.1 Delete Feature
```http
DELETE /api/product_features/25
```

**⚠️ WARNING:** CASCADE delete do `ps_feature_lang`, `ps_feature_value`, `ps_feature_product`!

#### 2.5.2 Delete Feature Value
```http
DELETE /api/product_feature_values/150
```

**⚠️ WARNING:** Usunie też z `ps_feature_product`!

#### 2.5.3 Remove Feature from Product
```http
PUT /api/products/123
Content-Type: application/xml
```

**Strategy:** GET product → REMOVE feature z associations → PUT

---

## 3. SYNCHRONIZATION WORKFLOWS

### 3.1 Import Features from PrestaShop to PPM

**Use Case:** Pobranie istniejących features z PrestaShop do PPM (first-time setup)

**Workflow:**

```php
class ImportFeaturesFromPrestaShop
{
    /**
     * STEP 1: Import Features (ps_feature → FeatureType)
     */
    public function importFeatures(PrestaShopShop $shop): void
    {
        $client = PrestaShopClientFactory::create($shop);

        // GET all features
        $response = $client->makeRequest('GET', '/product_features?display=full');
        $features = $response['product_features']['product_feature'] ?? [];

        foreach ($features as $psFeature) {
            $id_feature = $psFeature['id'];

            // GET feature with translations
            $featureDetail = $client->makeRequest('GET', "/product_features/{$id_feature}");
            $name = $featureDetail['product_feature']['name']['language'][0]['value'] ?? 'Unknown';

            // CREATE or UPDATE in PPM
            $featureType = FeatureType::firstOrCreate(
                ['code' => 'ps_feature_' . $id_feature], // Temporary code
                [
                    'name' => $name,
                    'value_type' => 'select', // Default
                    'is_active' => true,
                    'position' => $psFeature['position'] ?? 0,
                ]
            );

            // SAVE mapping
            ShopFeatureMapping::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'feature_type_id' => $featureType->id,
                ],
                [
                    'prestashop_feature_id' => $id_feature,
                ]
            );
        }
    }

    /**
     * STEP 2: Import Feature Values (ps_feature_value → FeatureValue)
     */
    public function importFeatureValues(PrestaShopShop $shop): void
    {
        $client = PrestaShopClientFactory::create($shop);

        $mappings = ShopFeatureMapping::where('shop_id', $shop->id)->get();

        foreach ($mappings as $mapping) {
            $id_feature = $mapping->prestashop_feature_id;

            // GET all values for this feature
            $response = $client->makeRequest('GET', "/product_feature_values?filter[id_feature]=[{$id_feature}]&display=full");
            $values = $response['product_feature_values']['product_feature_value'] ?? [];

            foreach ($values as $psValue) {
                // SKIP custom values (will be created per-product)
                if (!empty($psValue['custom'])) {
                    continue;
                }

                $id_feature_value = $psValue['id'];

                // GET value with translations
                $valueDetail = $client->makeRequest('GET', "/product_feature_values/{$id_feature_value}");
                $value = $valueDetail['product_feature_value']['value']['language'][0]['value'] ?? 'Unknown';

                // CREATE in PPM
                $featureValue = FeatureValue::firstOrCreate(
                    [
                        'feature_type_id' => $mapping->feature_type_id,
                        'value' => $value,
                    ],
                    [
                        'is_active' => true,
                        'position' => $psValue['position'] ?? 0,
                    ]
                );

                // SAVE mapping
                ShopFeatureValueMapping::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'feature_value_id' => $featureValue->id,
                    ],
                    [
                        'prestashop_feature_value_id' => $id_feature_value,
                    ]
                );
            }
        }
    }
}
```

**Execution:**
```php
$importer = new ImportFeaturesFromPrestaShop();
$shop = PrestaShopShop::find(1);

$importer->importFeatures($shop);
$importer->importFeatureValues($shop);
```

---

### 3.2 Export Features to PrestaShop (Product Sync)

**Use Case:** Synchronizacja cech produktu z PPM do PrestaShop podczas sync produktu

**Workflow:**

```php
class ProductFeatureSyncService
{
    /**
     * Sync product features to PrestaShop
     *
     * CALLED BY: ProductSyncStrategy::syncToPrestaShop()
     */
    public function syncFeatures(Product $product, PrestaShopShop $shop, int $prestashop_product_id): array
    {
        $client = PrestaShopClientFactory::create($shop);
        $associations = [];

        foreach ($product->productFeatures as $productFeature) {
            // STEP 1: Ensure feature exists in PrestaShop
            $id_feature = $this->ensureFeature($productFeature->featureType, $shop, $client);

            // STEP 2: Ensure feature value exists in PrestaShop
            $id_feature_value = $this->ensureFeatureValue($productFeature, $shop, $client, $id_feature);

            // STEP 3: Build association
            $associations[] = [
                'id' => $id_feature,
                'id_feature_value' => $id_feature_value,
            ];
        }

        return $associations;
    }

    /**
     * Ensure feature exists in PrestaShop (create if missing)
     */
    protected function ensureFeature(FeatureType $featureType, PrestaShopShop $shop, $client): int
    {
        // CHECK mapping
        $mapping = ShopFeatureMapping::where('shop_id', $shop->id)
            ->where('feature_type_id', $featureType->id)
            ->first();

        if ($mapping) {
            return $mapping->prestashop_feature_id;
        }

        // CREATE in PrestaShop
        $xml = $this->buildFeatureXml($featureType);
        $response = $client->makeRequest('POST', '/product_features', [], [
            'body' => $xml,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        $id_feature = $response['product_feature']['id'];

        // SAVE mapping
        ShopFeatureMapping::create([
            'shop_id' => $shop->id,
            'feature_type_id' => $featureType->id,
            'prestashop_feature_id' => $id_feature,
        ]);

        return $id_feature;
    }

    /**
     * Ensure feature value exists in PrestaShop (create if missing)
     */
    protected function ensureFeatureValue(ProductFeature $productFeature, PrestaShopShop $shop, $client, int $id_feature): int
    {
        // CASE 1: Predefined value
        if ($productFeature->feature_value_id) {
            $mapping = ShopFeatureValueMapping::where('shop_id', $shop->id)
                ->where('feature_value_id', $productFeature->feature_value_id)
                ->first();

            if ($mapping) {
                return $mapping->prestashop_feature_value_id;
            }

            // CREATE predefined value
            $xml = $this->buildFeatureValueXml($id_feature, $productFeature->featureValue->value, false);
            $response = $client->makeRequest('POST', '/product_feature_values', [], [
                'body' => $xml,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);

            $id_feature_value = $response['product_feature_value']['id'];

            // SAVE mapping
            ShopFeatureValueMapping::create([
                'shop_id' => $shop->id,
                'feature_value_id' => $productFeature->feature_value_id,
                'prestashop_feature_value_id' => $id_feature_value,
            ]);

            return $id_feature_value;
        }

        // CASE 2: Custom value
        if ($productFeature->custom_value) {
            // CHECK if custom value already exists
            $response = $client->makeRequest('GET', "/product_feature_values?filter[id_feature]=[{$id_feature}]&display=full");
            $values = $response['product_feature_values']['product_feature_value'] ?? [];

            foreach ($values as $psValue) {
                if (!empty($psValue['custom']) && $psValue['value']['language'][0]['value'] === $productFeature->custom_value) {
                    return $psValue['id'];
                }
            }

            // CREATE custom value
            $xml = $this->buildFeatureValueXml($id_feature, $productFeature->custom_value, true);
            $response = $client->makeRequest('POST', '/product_feature_values', [], [
                'body' => $xml,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);

            return $response['product_feature_value']['id'];
        }

        throw new \Exception('ProductFeature must have either feature_value_id or custom_value');
    }

    /**
     * Build XML for creating feature
     */
    protected function buildFeatureXml(FeatureType $featureType): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature>
        <position>{$featureType->position}</position>
        <name>
            <language id="1"><![CDATA[{$featureType->name}]]></language>
            <language id="2"><![CDATA[{$featureType->name}]]></language>
        </name>
    </product_feature>
</prestashop>
XML;
    }

    /**
     * Build XML for creating feature value
     */
    protected function buildFeatureValueXml(int $id_feature, string $value, bool $custom): string
    {
        $customTag = $custom ? '<custom>1</custom>' : '<custom></custom>';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product_feature_value>
        <id_feature>{$id_feature}</id_feature>
        {$customTag}
        <position>0</position>
        <value>
            <language id="1"><![CDATA[{$value}]]></language>
            <language id="2"><![CDATA[{$value}]]></language>
        </value>
    </product_feature_value>
</prestashop>
XML;
    }
}
```

**Integration with ProductTransformer:**
```php
class ProductTransformer
{
    protected ProductFeatureSyncService $featureSyncService;

    public function transformForPrestaShop(Product $product, BasePrestaShopClient $client): array
    {
        $shop = $client->getShop();
        $prestashop_product_id = $product->syncStatus($shop->id)->prestashop_product_id;

        // Get feature associations
        $featureAssociations = $this->featureSyncService->syncFeatures($product, $shop, $prestashop_product_id);

        return [
            // ...other product fields...
            'associations' => [
                'product_features' => $featureAssociations,
                // ...other associations...
            ],
        ];
    }
}
```

---

### 3.3 Conflict Resolution

**Scenarios:**

#### 3.3.1 Same FeatureType, Different Values in Different Shops

**Problem:** Product ma `Moc: 1000W` w sklepie A, ale `Moc: 1200W` w sklepie B

**Solution:** Store-specific features (rozszerzenie modelu)

```sql
CREATE TABLE product_shop_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    shop_id INT NOT NULL,
    feature_type_id INT NOT NULL,
    feature_value_id INT NULL,
    custom_value VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (product_id, shop_id, feature_type_id)
);
```

**Logic:**
1. **SYNC:** Check `product_shop_features` first
2. **FALLBACK:** Use global `product_features` if no shop-specific
3. **DISPLAY:** Admin UI shows both (global + overrides)

#### 3.3.2 PrestaShop Feature Deleted, PPM Still Has It

**Problem:** Feature usunięte w PS, ale PPM ma mapping

**Solution:** Validation before sync

```php
protected function validateFeatureMapping(ShopFeatureMapping $mapping, $client): bool
{
    try {
        $client->makeRequest('GET', "/product_features/{$mapping->prestashop_feature_id}");
        return true;
    } catch (PrestaShopAPIException $e) {
        if ($e->getCode() === 404) {
            // Feature deleted in PrestaShop
            $mapping->delete();
            return false;
        }
        throw $e;
    }
}
```

#### 3.3.3 Same Feature Name, Different IDs in Different Shops

**Problem:** "Kolor" ma ID=5 w sklepie A, ID=8 w sklepie B

**Solution:** Per-shop mappings (już zaimplementowane w `shop_feature_mappings`)

```php
// CORRECT:
$mapping_shopA = ShopFeatureMapping::where('shop_id', 1)
    ->where('feature_type_id', $colorFeatureType->id)
    ->first();
// → prestashop_feature_id = 5

$mapping_shopB = ShopFeatureMapping::where('shop_id', 2)
    ->where('feature_type_id', $colorFeatureType->id)
    ->first();
// → prestashop_feature_id = 8
```

---

## 4. POTENCJALNE PROBLEMY I ROZWIĄZANIA

### 4.1 Multi-Language Support

**Problem:** PPM ma single language, PrestaShop wymaga multi-language

**Solution:**
```php
protected function buildMultiLanguageTag(string $value, array $languageIds = [1, 2]): string
{
    $tags = '';
    foreach ($languageIds as $langId) {
        $tags .= "<language id=\"{$langId}\"><![CDATA[{$value}]]></language>\n";
    }
    return $tags;
}
```

**Future Enhancement:** Dodać tabele `feature_types_lang`, `feature_values_lang` w PPM

---

### 4.2 Custom Values Duplication

**Problem:** Ten sam custom value tworzony wielokrotnie

**Solution:** Cache custom values per sync session

```php
protected array $customValueCache = [];

protected function getOrCreateCustomValue(int $id_feature, string $value, $client): int
{
    $cacheKey = "{$id_feature}:{$value}";

    if (isset($this->customValueCache[$cacheKey])) {
        return $this->customValueCache[$cacheKey];
    }

    // CHECK if exists
    $response = $client->makeRequest('GET', "/product_feature_values?filter[id_feature]=[{$id_feature}]&display=full");
    $values = $response['product_feature_values']['product_feature_value'] ?? [];

    foreach ($values as $psValue) {
        if (!empty($psValue['custom']) && $psValue['value']['language'][0]['value'] === $value) {
            $this->customValueCache[$cacheKey] = $psValue['id'];
            return $psValue['id'];
        }
    }

    // CREATE new
    $xml = $this->buildFeatureValueXml($id_feature, $value, true);
    $response = $client->makeRequest('POST', '/product_feature_values', [], [
        'body' => $xml,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);

    $id = $response['product_feature_value']['id'];
    $this->customValueCache[$cacheKey] = $id;

    return $id;
}
```

---

### 4.3 Position Conflicts

**Problem:** Różne `position` w PPM vs PrestaShop

**Solution:** Sync position podczas update

```php
public function syncFeaturePositions(PrestaShopShop $shop): void
{
    $mappings = ShopFeatureMapping::where('shop_id', $shop->id)
        ->with('featureType')
        ->get();

    foreach ($mappings as $mapping) {
        $featureType = $mapping->featureType;
        $client = PrestaShopClientFactory::create($shop);

        // GET existing
        $psFeature = $client->makeRequest('GET', "/product_features/{$mapping->prestashop_feature_id}");

        // UPDATE position
        $psFeature['product_feature']['position'] = $featureType->position;

        $xml = $this->arrayToXml($psFeature);
        $client->makeRequest('PUT', "/product_features/{$mapping->prestashop_feature_id}", [], [
            'body' => $xml,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }
}
```

---

### 4.4 Orphaned Features (deleted in PPM, still in PS)

**Problem:** Feature usunięta w PPM, ale mapping + PS feature pozostają

**Solution:** Cleanup job

```php
class CleanupOrphanedFeatureMappings
{
    public function handle(): void
    {
        // Features
        $orphanedFeatures = ShopFeatureMapping::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('feature_types')
                ->whereColumn('feature_types.id', 'shop_feature_mappings.feature_type_id');
        })->get();

        foreach ($orphanedFeatures as $mapping) {
            Log::warning('Orphaned feature mapping found', [
                'shop_id' => $mapping->shop_id,
                'feature_type_id' => $mapping->feature_type_id,
                'prestashop_feature_id' => $mapping->prestashop_feature_id,
            ]);

            // OPTIONAL: Delete from PrestaShop
            // $client = PrestaShopClientFactory::create($mapping->shop);
            // $client->makeRequest('DELETE', "/product_features/{$mapping->prestashop_feature_id}");

            $mapping->delete();
        }

        // Feature Values
        $orphanedValues = ShopFeatureValueMapping::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('feature_values')
                ->whereColumn('feature_values.id', 'shop_feature_value_mappings.feature_value_id');
        })->get();

        foreach ($orphanedValues as $mapping) {
            Log::warning('Orphaned feature value mapping found', [
                'shop_id' => $mapping->shop_id,
                'feature_value_id' => $mapping->feature_value_id,
                'prestashop_feature_value_id' => $mapping->prestashop_feature_value_id,
            ]);

            $mapping->delete();
        }
    }
}
```

---

### 4.5 Rate Limiting on Bulk Import

**Problem:** Import 500 features × 20 values = 10,000+ API calls

**Solution:** Batch operations + throttling

```php
class ImportFeaturesFromPrestaShop
{
    protected int $requestsPerHour = 3600; // PrestaShop default
    protected int $requestCount = 0;
    protected Carbon $windowStart;

    public function __construct()
    {
        $this->windowStart = now();
    }

    protected function throttle(): void
    {
        $this->requestCount++;

        if ($this->requestCount >= $this->requestsPerHour) {
            $elapsed = now()->diffInSeconds($this->windowStart);
            $sleepTime = 3600 - $elapsed;

            if ($sleepTime > 0) {
                Log::info('Rate limit reached, sleeping', ['seconds' => $sleepTime]);
                sleep($sleepTime);
            }

            $this->requestCount = 0;
            $this->windowStart = now();
        }
    }

    public function importFeatures(PrestaShopShop $shop): void
    {
        $client = PrestaShopClientFactory::create($shop);

        $response = $client->makeRequest('GET', '/product_features?display=full');
        $this->throttle();

        $features = $response['product_features']['product_feature'] ?? [];

        foreach ($features as $psFeature) {
            $featureDetail = $client->makeRequest('GET', "/product_features/{$psFeature['id']}");
            $this->throttle();

            // ...process...
        }
    }
}
```

---

## 5. MIGRACJE BAZY DANYCH (NEW TABLES)

### 5.1 ShopFeatureMapping

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_feature_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('prestashop_shops')->onDelete('cascade');
            $table->foreignId('feature_type_id')->constrained('feature_types')->onDelete('cascade');
            $table->unsignedInteger('prestashop_feature_id');
            $table->timestamps();

            $table->unique(['shop_id', 'feature_type_id']);
            $table->unique(['shop_id', 'prestashop_feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_feature_mappings');
    }
};
```

### 5.2 ShopFeatureValueMapping

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_feature_value_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('prestashop_shops')->onDelete('cascade');
            $table->foreignId('feature_value_id')->constrained('feature_values')->onDelete('cascade');
            $table->unsignedInteger('prestashop_feature_value_id');
            $table->timestamps();

            $table->unique(['shop_id', 'feature_value_id']);
            $table->unique(['shop_id', 'prestashop_feature_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_feature_value_mappings');
    }
};
```

### 5.3 ProductShopFeatures (Optional - for store-specific features)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_shop_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('prestashop_shops')->onDelete('cascade');
            $table->foreignId('feature_type_id')->constrained('feature_types')->onDelete('cascade');
            $table->foreignId('feature_value_id')->nullable()->constrained('feature_values')->onDelete('set null');
            $table->string('custom_value')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'shop_id', 'feature_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shop_features');
    }
};
```

---

## 6. MODELS (NEW)

### 6.1 ShopFeatureMapping

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopFeatureMapping extends Model
{
    protected $fillable = [
        'shop_id',
        'feature_type_id',
        'prestashop_feature_id',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'feature_type_id' => 'integer',
        'prestashop_feature_id' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function featureType(): BelongsTo
    {
        return $this->belongsTo(FeatureType::class, 'feature_type_id');
    }
}
```

### 6.2 ShopFeatureValueMapping

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopFeatureValueMapping extends Model
{
    protected $fillable = [
        'shop_id',
        'feature_value_id',
        'prestashop_feature_value_id',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'feature_value_id' => 'integer',
        'prestashop_feature_value_id' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id');
    }
}
```

---

## 7. IMPLEMENTACJA - ROADMAP

### PHASE 1: Foundation (2-3 dni)
- [ ] Migracje: `shop_feature_mappings`, `shop_feature_value_mappings`
- [ ] Models: `ShopFeatureMapping`, `ShopFeatureValueMapping`
- [ ] BasePrestaShopClient methods: `getFeatures()`, `getFeatureValues()`, `createFeature()`, `createFeatureValue()`

### PHASE 2: Import (2-3 dni)
- [ ] Service: `ImportFeaturesFromPrestaShop`
- [ ] Command: `php artisan prestashop:import-features {shop_id}`
- [ ] Throttling + error handling
- [ ] Admin UI: Import button

### PHASE 3: Export/Sync (3-4 dni)
- [ ] Service: `ProductFeatureSyncService`
- [ ] Integration z `ProductTransformer`
- [ ] Integration z `ProductSyncStrategy`
- [ ] Testing z real products

### PHASE 4: Conflict Resolution (2-3 dni)
- [ ] Validation before sync
- [ ] Orphaned mappings cleanup
- [ ] Custom value caching
- [ ] Position synchronization

### PHASE 5: Advanced (optional, 2-3 dni)
- [ ] Store-specific features (`product_shop_features`)
- [ ] Multi-language support w PPM
- [ ] Bulk import optimization
- [ ] Admin UI dla mappings

**Total Estimated Time:** 11-16 dni (2-3 tygodnie)

---

## 8. TESTING CHECKLIST

### Unit Tests
- [ ] `ShopFeatureMapping` model
- [ ] `ShopFeatureValueMapping` model
- [ ] XML builders (feature, feature_value)
- [ ] Multi-language tag generation

### Integration Tests
- [ ] Import features from PrestaShop
- [ ] Import feature values from PrestaShop
- [ ] Create feature in PrestaShop
- [ ] Create feature value in PrestaShop (predefined + custom)
- [ ] Sync product features
- [ ] Conflict resolution scenarios

### Manual Testing
- [ ] Import z real PrestaShop shop
- [ ] Sync product z features do PrestaShop
- [ ] Verify w PrestaShop admin panel
- [ ] Test multi-shop (różne ID dla tej samej cechy)
- [ ] Test custom values
- [ ] Test orphaned mappings cleanup

---

## 9. DOCUMENTATION REFERENCES

**PrestaShop API:**
- [Database Structure - PrestaShop DevDocs](https://devdocs.prestashop-project.org/9/development/database/structure/)
- [PrestaShop GitHub db_structure.sql](https://github.com/PrestaShop/PrestaShop/blob/develop/install-dev/data/db_structure.sql)
- [Create Product A-Z Tutorial](https://github.com/prestashop/docs/blob/9.x/webservice/tutorials/create-product-az.md)

**Context7 Snippets:**
- `/prestashop/docs` - 3289 snippets (trust 8.2)
- Product Feature Creation API
- Product Feature Value Creation API
- Associate Feature to Product - XML

---

## 10. CONCLUSION

**Gotowe do implementacji:** ✅

System features w PrestaShop jest dobrze zdefiniowany i kompatybilny z obecną architekturą PPM. Mapowanie 1:1 jest możliwe z minimalnymi modyfikacjami (2 nowe tabele mappings).

**Kluczowe zalety:**
- ✅ Custom values supported (via `ps_feature_value.custom`)
- ✅ Multi-language ready (via `_lang` tables)
- ✅ Position management (via `position` fields)
- ✅ Per-shop mappings (via `shop_feature_mappings`)

**Kluczowe wyzwania:**
- ⚠️ Multi-language (PPM single, PS multi) - rozwiązane przez duplication
- ⚠️ Custom values duplication - rozwiązane przez caching
- ⚠️ Rate limiting na bulk import - rozwiązane przez throttling
- ⚠️ Orphaned mappings - rozwiązane przez cleanup job

**Next Steps:**
1. Review raportu przez stakeholdera
2. Approve implementacji
3. Start PHASE 1 (Foundation)
4. Iteracyjna implementacja wg roadmap

---

**END OF REPORT**
