# RAPORT PRACY AGENTA: prestashop-api-expert

**Data**: 2025-12-09 (Timestamp dokładny z wykonania analiz)
**Agent**: prestashop-api-expert
**Zadanie**: Analiza struktury features w bazie PrestaShop dla dopasowań pojazdów

## ✅ WYKONANE PRACE

### 1. Analiza Struktury Tabel Features w PrestaShop

#### 1.1 Główne Tabele Features

PrestaShop używa 7 tabel do zarządzania features:

```
ps_feature              - Główna tabela features (ID, position)
ps_feature_flag         - Flagi features
ps_feature_lang         - Tłumaczenia nazw features (multilang)
ps_feature_product      - Relacja many-to-many (product ↔ feature_value)
ps_feature_shop         - Multi-shop features
ps_feature_value        - Wartości features (ID, custom flag)
ps_feature_value_lang   - Tłumaczenia wartości features (multilang)
```

#### 1.2 Struktura Szczegółowa Tabel

**ps_feature:**
```sql
Field       | Type                  | Key  | Extra
------------|----------------------|------|---------------
id_feature  | int(10) unsigned     | PRI  | auto_increment
position    | int(10) unsigned     |      |
```

**ps_feature_lang:**
```sql
Field       | Type                  | Key  | Extra
------------|----------------------|------|-------
id_feature  | int(10) unsigned     | PRI  |
id_lang     | int(10) unsigned     | PRI  |
name        | varchar(128)         |      | NULL
```

**ps_feature_value:**
```sql
Field            | Type                  | Key  | Extra
-----------------|----------------------|------|---------------
id_feature_value | int(10) unsigned     | PRI  | auto_increment
id_feature       | int(10) unsigned     | MUL  |
custom           | tinyint(3) unsigned  |      | NULL
```

**ps_feature_value_lang:**
```sql
Field            | Type                  | Key  | Extra
-----------------|----------------------|------|-------
id_feature_value | int(10) unsigned     | PRI  |
id_lang          | int(10) unsigned     | PRI  |
value            | varchar(255)         |      | NULL
```

**ps_feature_product (KLUCZOWA RELACJA):**
```sql
Field            | Type                  | Key  | Extra
-----------------|----------------------|------|-------
id_feature       | int(10) unsigned     | PRI  |
id_product       | int(10) unsigned     | PRI  |
id_feature_value | int(10) unsigned     | PRI  |
```

**INDEKSY:**
- PRIMARY KEY: (id_feature, id_product, id_feature_value)
- INDEX: id_feature_value
- INDEX: id_product

**KARDINALNOŚĆ:** 106,074 total records (very high volume!)

### 2. Features Dopasowań Pojazdów

#### 2.1 Identyfikacja Features

Znaleziono 3 features odpowiedzialne za dopasowania pojazdów:

| ID  | Nazwa      | Position | Feature Values | Produkty |
|-----|------------|----------|----------------|----------|
| 431 | Oryginał   | 71       | 103            | 6,693    |
| 432 | Model      | 72       | 103            | 7,717    |
| 433 | Zamiennik  | 74       | 99             | 2,319    |

**TOTAL:** 305 feature values, 16,729 product assignments

#### 2.2 Przykładowe Feature Values

**Oryginał (id_feature=431):**
```
id_feature_value: 2141, value: "KAYO K2 250 ENDURO"
id_feature_value: 2142, value: "KAYO K2 PRO ENDURO"
id_feature_value: 2145, value: "KAYO K2L"
id_feature_value: 2147, value: "KAYO T2 ENDURO"
id_feature_value: 2290, value: "KAYO Mini GP 150"
id_feature_value: 2331, value: "MRF 120 SM"
id_feature_value: 2336, value: "MRF 80 RUNNER"
...
```

**Model (id_feature=432):**
```
id_feature_value: 2143, value: "KAYO K2 250 ENDURO"
id_feature_value: 2144, value: "KAYO K2 PRO ENDURO"
id_feature_value: 2146, value: "KAYO K2L"
id_feature_value: 2229, value: "MRF 80 RUNNER"
id_feature_value: 2230, value: "MRF 80 RUNNER 2023"
...
```

**Zamiennik (id_feature=433):**
```
(99 unique vehicle compatibility values)
```

### 3. Relacja Features → Products

#### 3.1 Model Danych

PrestaShop używa **many-to-many** relationship:

```
Product (1) ←→ (N) ps_feature_product (N) ←→ (1) Feature Value
```

**KRYTYCZNE:** Jeden produkt może mieć WIELE wartości dla TEGO SAMEGO feature!

#### 3.2 Przykłady Produktów z Dopasowaniami

**Produkt: DIRT-718 (id=164)**
- SKU: DIRT-718
- Nazwa: "Linka ssania dirt bike K2 150/K2 250/K2 PRO 250 KAYO"
- Oryginał: KAYO K2 250 ENDURO, KAYO K2 PRO ENDURO
- Model: KAYO K2 250 ENDURO, KAYO K2 PRO ENDURO
- Zamiennik: NULL

**Produkt: DIRT-609 (id=166)**
- SKU: DIRT-609
- Nazwa: "Włącznik rozrusznika dirt bike KAYO"
- Oryginał: KAYO K2 250 ENDURO, KAYO K2 PRO ENDURO, KAYO K2L, KAYO K4 ENDURO, KAYO K5 300 ENDURO, KAYO K5 300 SM, KAYO K6-R ENDURO, KAYO KT250 ENDURO, KAYO T2 ENDURO, KAYO T4 250 ENDURO, KAYO T4 300 ENDURO (11 wartości!)
- Model: (11 wartości, identyczne jak Oryginał)
- Zamiennik: NULL

**Produkt: DIRT-255 (id=168)**
- SKU: DIRT-255
- Nazwa: "Kierownica fatbar dirt bike Kayo"
- Oryginał: 9 vehicle models
- Model: 10 vehicle models
- Zamiennik: KAYO T4 300 ENDURO (różnica!)

### 4. Format API PrestaShop Web Services

#### 4.1 Endpoint Structure

**GET Product:**
```
GET https://test.kayomoto.pl/api/products/{id}?ws_key={KEY}
```

**Response Formats:**
- XML (default): `&output_format=XML` (or omit parameter)
- JSON: `&output_format=JSON`

#### 4.2 XML Format (Native PrestaShop)

```xml
<associations>
  <product_features nodeType="product_feature" api="product_features">
    <product_feature xlink:href="https://test.kayomoto.pl/api/product_features/281">
      <id><![CDATA[281]]></id>
      <id_feature_value xlink:href="https://test.kayomoto.pl/api/product_feature_values/19803">
        <![CDATA[19803]]>
      </id_feature_value>
    </product_feature>
    <product_feature xlink:href="https://test.kayomoto.pl/api/product_features/282">
      <id><![CDATA[282]]></id>
      <id_feature_value xlink:href="https://test.kayomoto.pl/api/product_feature_values/19802">
        <![CDATA[19802]]>
      </id_feature_value>
    </product_feature>
  </product_features>
</associations>
```

**STRUKTURA:**
- `<product_features>` - Array container
- `<product_feature>` - Single feature assignment
  - `<id>` - **NOT id_feature!** This is the auto-increment ID from ps_feature_product
  - `<id_feature_value>` - Feature value ID (links to ps_feature_value)

**⚠️ UWAGA:** `<id>` w API to **composite key ID**, NIE `id_feature`!

#### 4.3 JSON Format

```json
{
  "product": {
    "associations": {
      "product_features": [
        {
          "id": "281",
          "id_feature_value": "19803"
        },
        {
          "id": "282",
          "id_feature_value": "19802"
        }
      ]
    }
  }
}
```

### 5. Zapytania SQL do Analizy Dopasowań

#### 5.1 Lista Wszystkich Features Dopasowań

```sql
SELECT
    f.id_feature,
    fl.name,
    f.position,
    COUNT(DISTINCT fv.id_feature_value) as value_count,
    COUNT(DISTINCT fp.id_product) as product_count
FROM ps_feature f
JOIN ps_feature_lang fl ON f.id_feature = fl.id_feature
LEFT JOIN ps_feature_value fv ON f.id_feature = fv.id_feature
LEFT JOIN ps_feature_product fp ON f.id_feature = fp.id_feature
WHERE f.id_feature IN (431, 432, 433)
  AND fl.id_lang = 1
GROUP BY f.id_feature, fl.name, f.position;
```

**OUTPUT:**
```
id_feature | name      | position | value_count | product_count
-----------|-----------|----------|-------------|---------------
431        | Oryginał  | 71       | 103         | 6,693
432        | Model     | 72       | 103         | 7,717
433        | Zamiennik | 74       | 99          | 2,319
```

#### 5.2 Produkty z Dopasowaniami (Grouped)

```sql
SELECT
    p.id_product,
    p.reference,
    pl.name as product_name,
    GROUP_CONCAT(DISTINCT CASE WHEN fp.id_feature = 431 THEN fvl.value END
                 SEPARATOR ', ') as oryginal,
    GROUP_CONCAT(DISTINCT CASE WHEN fp.id_feature = 432 THEN fvl.value END
                 SEPARATOR ', ') as model,
    GROUP_CONCAT(DISTINCT CASE WHEN fp.id_feature = 433 THEN fvl.value END
                 SEPARATOR ', ') as zamiennik
FROM ps_product p
JOIN ps_product_lang pl ON p.id_product = pl.id_product
LEFT JOIN ps_feature_product fp ON p.id_product = fp.id_product
    AND fp.id_feature IN (431, 432, 433)
LEFT JOIN ps_feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value
    AND fvl.id_lang = 1
WHERE pl.id_lang = 1
GROUP BY p.id_product, p.reference, pl.name
HAVING oryginal IS NOT NULL OR model IS NOT NULL OR zamiennik IS NOT NULL
LIMIT 10;
```

#### 5.3 Feature Values dla Konkretnego Feature

```sql
SELECT
    fv.id_feature_value,
    fv.id_feature,
    fvl.value,
    COUNT(fp.id_product) as product_count
FROM ps_feature_value fv
JOIN ps_feature_value_lang fvl ON fv.id_feature_value = fvl.id_feature_value
LEFT JOIN ps_feature_product fp ON fv.id_feature_value = fp.id_feature_value
WHERE fv.id_feature = 431  -- Oryginał
  AND fvl.id_lang = 1
GROUP BY fv.id_feature_value, fv.id_feature, fvl.value
ORDER BY product_count DESC, fvl.value ASC;
```

#### 5.4 Weryfikacja Duplikatów Feature Values

```sql
SELECT
    fv.id_feature,
    fvl.value,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(fv.id_feature_value) as value_ids
FROM ps_feature_value fv
JOIN ps_feature_value_lang fvl ON fv.id_feature_value = fvl.id_feature_value
WHERE fv.id_feature IN (431, 432, 433)
  AND fvl.id_lang = 1
GROUP BY fv.id_feature, fvl.value
HAVING COUNT(*) > 1;
```

### 6. Kluczowe Wnioski dla Integracji PPM ↔ PrestaShop

#### 6.1 Model Synchronizacji

**PPM → PrestaShop:**

```php
// PPM Structure:
VehicleCompatibility {
    product_id: int
    brand: string
    model: string
    type: enum('original', 'replacement', 'compatible')
}

// PrestaShop Mapping:
type='original'     → ps_feature.id_feature = 431
type='replacement'  → ps_feature.id_feature = 433
type='compatible'   → ps_feature.id_feature = 432

// Value Mapping:
brand + model → ps_feature_value_lang.value (lookup/create)
```

#### 6.2 API Sync Strategy

**CREATE/UPDATE Product Features:**

```xml
<product>
  <associations>
    <product_features>
      <product_feature>
        <id></id> <!-- EMPTY dla nowych -->
        <id_feature_value>2141</id_feature_value> <!-- KAYO K2 250 ENDURO -->
      </product_feature>
      <product_feature>
        <id></id>
        <id_feature_value>2142</id_feature_value> <!-- KAYO K2 PRO ENDURO -->
      </product_feature>
    </product_features>
  </associations>
</product>
```

**CRITICAL:**
- Empty `<id>` dla nowych features
- Multiple `<product_feature>` nodes dla tego samego `id_feature` (allowed!)
- PrestaShop auto-generates composite IDs

#### 6.3 Performance Considerations

**HIGH VOLUME DATA:**
- 106,074 total feature-product assignments
- 16,729 assignments tylko dla dopasowań (431, 432, 433)
- Średnio 2.5 feature values per product

**RECOMMENDATIONS:**
1. **Batch Operations:** Use PrestaShop 9.x bulk API (if available)
2. **Caching:** Cache feature value lookups (brand+model → id_feature_value)
3. **Incremental Sync:** Tylko zmienione dopasowania (checksum tracking)
4. **Indexing:** Existing indexes on id_product, id_feature_value (OK for performance)

#### 6.4 Data Integrity Rules

**CONSTRAINTS:**
- PRIMARY KEY (id_feature, id_product, id_feature_value) - prevents exact duplicates
- Brak CHECK constraints - możliwe przypisanie tej samej wartości wielokrotnie
- Brak CASCADE DELETE - manual cleanup required

**VALIDATION RULES:**
1. Feature value MUST exist before product assignment
2. Feature MUST be active (check ps_feature_shop)
3. Multi-language values MUST exist (id_lang=1 minimum)

### 7. Przykładowy Kod Integracji

#### 7.1 Transformer: PPM → PrestaShop

```php
class VehicleCompatibilityTransformer
{
    protected array $featureTypeMap = [
        'original' => 431,      // Oryginał
        'compatible' => 432,    // Model
        'replacement' => 433,   // Zamiennik
    ];

    public function transformCompatibilitiesToFeatures(
        Product $product,
        PrestaShopShop $shop
    ): array {
        $features = [];

        foreach ($product->vehicleCompatibilities as $compat) {
            $featureId = $this->featureTypeMap[$compat->type];
            $featureValueId = $this->getOrCreateFeatureValue(
                $featureId,
                "{$compat->brand} {$compat->model}",
                $shop
            );

            $features[] = [
                'id' => '', // Empty for new assignments
                'id_feature_value' => $featureValueId
            ];
        }

        return $features;
    }

    protected function getOrCreateFeatureValue(
        int $featureId,
        string $value,
        PrestaShopShop $shop
    ): int {
        // Check cache first
        $cacheKey = "ps_feature_value:{$shop->id}:{$featureId}:{$value}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Query PrestaShop DB
        $existingValue = DB::connection('prestashop')
            ->table('ps_feature_value as fv')
            ->join('ps_feature_value_lang as fvl', 'fv.id_feature_value', '=', 'fvl.id_feature_value')
            ->where('fv.id_feature', $featureId)
            ->where('fvl.value', $value)
            ->where('fvl.id_lang', 1)
            ->first();

        if ($existingValue) {
            Cache::put($cacheKey, $existingValue->id_feature_value, 3600);
            return $existingValue->id_feature_value;
        }

        // Create via API
        $client = PrestaShopClientFactory::create($shop);
        $response = $client->createFeatureValue($featureId, $value);

        Cache::put($cacheKey, $response['id'], 3600);
        return $response['id'];
    }
}
```

#### 7.2 Sync Service: Product Features

```php
class ProductFeaturesSyncService
{
    public function syncProductFeatures(
        Product $product,
        PrestaShopShop $shop
    ): bool {
        $client = PrestaShopClientFactory::create($shop);
        $transformer = new VehicleCompatibilityTransformer();

        // Get current PrestaShop features
        $psProduct = $client->getProduct($product->prestashop_product_id);
        $currentFeatures = $psProduct['associations']['product_features'] ?? [];

        // Transform PPM compatibilities
        $newFeatures = $transformer->transformCompatibilitiesToFeatures($product, $shop);

        // REPLACE strategy: Clear compatibility features, keep others
        $otherFeatures = collect($currentFeatures)->filter(function($feature) {
            $featureValue = $this->getFeatureValueInfo($feature['id_feature_value']);
            return !in_array($featureValue['id_feature'], [431, 432, 433]);
        })->toArray();

        // Merge: other features + new compatibility features
        $updatedFeatures = array_merge($otherFeatures, $newFeatures);

        // Update via API
        return $client->updateProduct($product->prestashop_product_id, [
            'associations' => [
                'product_features' => $updatedFeatures
            ]
        ]);
    }
}
```

## ⚠️ PROBLEMY/BLOKERY

### 1. Brak Normalnych Relacji Foreign Key

PrestaShop NIE używa CASCADE DELETE:
- Usunięcie produktu → orphaned records w ps_feature_product
- Usunięcie feature value → broken references

**MITIGATION:** Manual cleanup + validation przed sync

### 2. Multi-Language Complexity

Wszystkie features wymagają tłumaczeń dla każdego języka:
- id_lang=1 (Polski) - MANDATORY
- id_lang=2 (Angielski) - OPTIONAL ale zalecany

**MITIGATION:** Default to Polish, duplicate for English

### 3. API `<id>` vs `id_feature` Confusion

API zwraca `<id>` który to composite key, nie `id_feature`:
- NIE MOŻNA użyć `<id>` do identyfikacji feature type
- TRZEBA lookup przez `id_feature_value` → `ps_feature_value.id_feature`

**MITIGATION:** Always use `id_feature_value` as source of truth

### 4. Performance Overhead

106k+ feature assignments w bazie:
- GET product with features = heavy query (JOINS)
- Batch update = multiple API calls (no bulk in PrestaShop 8.x)

**MITIGATION:**
- Cache feature value lookups
- Incremental sync (tylko zmienione produkty)
- Consider PrestaShop 9.x bulk API

## 📋 NASTĘPNE KROKI

### 1. Implementation Tasks

1. **Feature Value Cache Service**
   - Cache layer dla brand+model → id_feature_value mapping
   - TTL: 1 hour (features rzadko się zmieniają)
   - Invalidate on manual feature creation

2. **Compatibility Transformer**
   - PPM VehicleCompatibility → PrestaShop product_features
   - Handle create/update/delete operations
   - Support for multiple values per type

3. **Sync Strategy Implementation**
   - REPLACE strategy (clear compatibility features, keep others)
   - Validation przed sync (feature values exist?)
   - Error handling + retry logic

4. **Database Tracking**
   - Extend `product_sync_status` table:
     - `features_checksum` column
     - `features_last_sync_at` timestamp
   - Track feature sync separately from product sync

### 2. Testing Requirements

1. **Unit Tests:**
   - VehicleCompatibilityTransformer
   - Feature value caching
   - Multiple values per feature type

2. **Integration Tests:**
   - Full sync flow: PPM → PrestaShop
   - Reverse sync: PrestaShop → PPM (import)
   - Conflict resolution (different values in each system)

3. **Performance Tests:**
   - Bulk sync (100+ products with features)
   - Cache hit rate measurement
   - API rate limiting handling

### 3. Documentation Updates

1. Update `_DOCS/PRESTASHOP_API_INTEGRATION.md`:
   - Feature sync architecture
   - API examples with features
   - Performance best practices

2. Create `_DOCS/VEHICLE_COMPATIBILITY_SYNC.md`:
   - Mapping rules (PPM types → PrestaShop features)
   - Sync strategies (REPLACE vs MERGE)
   - Error handling scenarios

## 📁 PLIKI

### Utworzone Pliki
- `_AGENT_REPORTS/prestashop_api_expert_COMPATIBILITY_FEATURES_ANALYSIS.md` - Ten raport

### SQL Queries Reference
Wszystkie zapytania SQL użyte w analizie zostały udokumentowane w sekcji 5 tego raportu.

### Zrzuty Ekranu/Dane
- Analiza wykonana na bazie: `host379076_devmpp` (PrestaShop B2B Test DEV)
- API endpoint: `https://test.kayomoto.pl/api`
- Timestamp: 2025-12-09

## 🎯 PODSUMOWANIE KLUCZOWYCH INFORMACJI

**Features Dopasowań:**
- **431** = Oryginał (103 values, 6,693 products)
- **432** = Model (103 values, 7,717 products)
- **433** = Zamiennik (99 values, 2,319 products)

**Struktura Relacji:**
```
Product ←→ ps_feature_product ←→ ps_feature_value ←→ ps_feature
         (many-to-many)           (value data)         (feature type)
```

**API Format (XML):**
```xml
<product_feature>
  <id></id> <!-- Empty for new -->
  <id_feature_value>2141</id_feature_value>
</product_feature>
```

**Sync Strategy:**
1. Cache feature values (brand+model → ID)
2. Transform PPM compatibilities → PrestaShop format
3. REPLACE strategy (keep non-compatibility features)
4. Batch updates z rate limiting

**Performance:**
- Total: 106,074 feature assignments w bazie
- Dopasowania: 16,729 assignments (15.7%)
- Średnio: 2.5 feature values per product

---

**AGENT COMPLETION STATUS:** ✅ COMPLETED

Analiza struktury features w PrestaShop została ukończona z pełną dokumentacją:
- ✅ Struktura tabel bazy danych
- ✅ Identyfikacja features dopasowań (431, 432, 433)
- ✅ Relacje many-to-many (product ↔ feature_value)
- ✅ Format API (XML/JSON)
- ✅ Przykładowe zapytania SQL
- ✅ Kod integracyjny (PHP transformers)
- ✅ Następne kroki implementacji

**Raport gotowy do użycia przez Laravel Expert i Import/Export Specialist.**
