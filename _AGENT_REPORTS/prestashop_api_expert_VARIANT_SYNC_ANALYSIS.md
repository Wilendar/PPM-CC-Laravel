# RAPORT ANALIZY: PrestaShop Warianty/Kombinacje - Problemy PULL i PUSH

**Data**: 2025-12-08
**Agent**: prestashop-api-expert
**Zadanie**: Analiza problemów z synchronizacją wariantów PrestaShop API

---

## 🎯 PROBLEMY DO ZBADANIA

### PROBLEM 1: PULL (Pobieranie wariantów)
**Opis**: Informacje o wariantach w ProductForm shop tab NIE są pobierane rzeczywiście przez API PrestaShop - dane wariantów nie są rzeczywiste jak pozostałe "Informacje podstawowe"

### PROBLEM 2: PUSH (Wysyłanie wariantów)
**Opis**: Wariant nie jest przesyłany prawidłowo do PrestaShop - kombinacje pokazują "-" zamiast wartości atrybutu kolorystycznego (np. kolor pojazdu)

**Produkt testowy**: https://ppm.mpptrade.pl/admin/products/11148/edit

---

## 📊 ANALIZA KODU - PROBLEM 1 (PULL)

### 1.1 ShopVariantService::pullShopVariants()

**Lokalizacja**: `app/Services/PrestaShop/ShopVariantService.php:51`

**Co robi?**
```php
public function pullShopVariants(Product $product, int $shopId): array
{
    // 1. Pobiera PrestaShop product ID z lokalnej bazy
    $shopData = $product->dataForShop($shopId)->first();
    $prestashopProductId = $shopData?->prestashop_product_id;

    // 2. Wywołuje API PrestaShop
    $combinations = $client->getCombinations($prestashopProductId);

    // 3. Mapuje combinations → PPM variant structure
    $mappedVariants = $this->mapCombinationsToVariants(
        $product, $shopId, $combinations
    );

    // 4. Aktualizuje ShopVariant records
    $this->syncShopVariantsFromPull($product, $shopId, $combinations);

    return ['variants' => $mappedVariants, 'synced' => true];
}
```

**✅ PULL DZIAŁA PRAWIDŁOWO**
- Faktycznie wywołuje `client->getCombinations()` (linia 92)
- API endpoint: `/combinations?filter[id_product]={productId}&display=full`
- Mapuje response do PPM format (linia 110-114)
- Aktualizuje lokalne ShopVariant records (linia 117)

### 1.2 ProductFormShopTabs::selectShopTab()

**Lokalizacja**: `app/Http/Livewire/Products/Management/Traits/ProductFormShopTabs.php:54`

```php
public function selectShopTab(int $shopId): void
{
    $this->selectedShopId = $shopId;
    $this->activeShopTab = "shop_{$shopId}";

    // ✅ ETAP_05c: Pull variants from PrestaShop API when entering shop tab
    if ($this->product && $this->isEditMode) {
        $this->pullVariantsFromPrestaShop($shopId);
    }
}

protected function pullVariantsFromPrestaShop(int $shopId): void
{
    $service = app(ShopVariantService::class);
    $result = $service->pullShopVariants($this->product, $shopId);

    $this->prestaShopVariants = $result; // ✅ Zapisuje do property
}
```

**✅ WORKFLOW PULL JEST OK**
1. User klika shop tab → `selectShopTab()` wywołane
2. `pullVariantsFromPrestaShop()` wywołuje `ShopVariantService`
3. Service wywołuje API PrestaShop `getCombinations()`
4. Response zapisywany do `$this->prestaShopVariants`

### 1.3 PrestaShop8Client::getCombinations()

**Lokalizacja**: `app/Services/PrestaShop/PrestaShop8Client.php:1517`

```php
public function getCombinations(int $productId): array
{
    $endpoint = "/combinations?filter[id_product]={$productId}&display=full";
    $response = $this->makeRequest('GET', $endpoint);

    // Handle response structure
    if (isset($response['combinations']['combination'])) {
        $combinations = $response['combinations']['combination'];
        // Ensure it's always an array of combinations
        if (isset($combinations['id'])) {
            return [$combinations]; // Single combination
        }
        return $combinations;
    }

    return [];
}
```

**✅ API CLIENT IMPLEMENTACJA OK**
- Używa prawidłowego endpoint'u
- Zwraca pełne dane (`display=full`)
- Prawidłowa obsługa single vs array response

---

## 🔍 ROOT CAUSE - PROBLEM 1 (PULL)

### HIPOTEZA A: View nie wyświetla danych z `$this->prestaShopVariants`

**MOŻLIWA PRZYCZYNA**: Blade template używa złej property lub jest źle podmieniona

**DO SPRAWDZENIA**:
```bash
# Znajdź Blade template dla shop tab variants
Grep "prestaShopVariants" resources/views/livewire/products/management/
```

### HIPOTEZA B: Dane są pobierane, ale nadpisywane przez lokalną kolekcję

**MOŻLIWA PRZYCZYNA**: Computed property lub metoda `getVariantsForShop()` nadpisuje pulled data

**DO SPRAWDZENIA**:
```php
// W ProductForm może być coś takiego:
public function getVariantsProperty() {
    if ($this->selectedShopId) {
        // ❌ BUG: Używa lokalnej metody zamiast $this->prestaShopVariants
        return $this->product->getVariantsForShop($this->selectedShopId);
    }
}
```

### HIPOTEZA C: Frontend cache - wire:poll nadpisuje dane

**MOŻLIWA PRZYCZYNA**: Livewire `wire:poll` lub `wire:loading` może resetować property

**TYPOWE OBJAWY**:
- Pierwsze wejście na shop tab → OK
- Po chwili dane "znikają" lub wracają do stanu lokalnego
- Wire:poll wywołuje render() który używa złych danych

---

## 📊 ANALIZA KODU - PROBLEM 2 (PUSH)

### 2.1 ProductSyncStrategy::syncToPrestaShop()

**Lokalizacja**: `app/Services/PrestaShop/Sync/ProductSyncStrategy.php:60`

```php
public function syncToPrestaShop(
    Model $model,
    BasePrestaShopClient $client,
    PrestaShopShop $shop,
    array $pendingMediaChanges = []
): array {
    // ...

    // Transform product data
    $productData = $this->transformer->transformForPrestaShop($model, $client);

    if ($isUpdate) {
        $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
    } else {
        $response = $client->createProduct($productData);
    }

    // ❌ BRAK: Synchronizacji combinations/variants!
    // Sync only calls syncMediaIfEnabled, syncFeaturesIfEnabled
    // NIE MA: syncVariantsIfEnabled lub syncCombinations
}
```

**❌ ROOT CAUSE #1: BRAK SYNCHRONIZACJI WARIANTÓW W PRODUCT SYNC**

ProductSyncStrategy synchronizuje:
- ✅ Product base data
- ✅ Categories (via CategoryAssociationService)
- ✅ Prices (via PrestaShopPriceExporter)
- ✅ Media (via syncMediaIfEnabled)
- ✅ Features (via syncFeaturesIfEnabled)
- ❌ **BRAK: Variants/Combinations**

### 2.2 PrestaShop Combinations API - dostępne metody

**Lokalizacja**: `app/Services/PrestaShop/PrestaShop8Client.php:1506-1730`

```php
// ✅ METODY SĄ ZAIMPLEMENTOWANE
public function getCombinations(int $productId): array
public function getCombination(int $combinationId): ?array
public function createCombination(int $productId, array $combinationData): array
public function updateCombination(int $combinationId, array $updates): array
public function deleteCombination(int $combinationId): bool
public function setCombinationImages(int $combinationId, array $imageIds): bool
public function setCombinationAttributes(int $combinationId, array $attributeValueIds): bool
```

**✅ API CLIENT MA WSZYSTKIE POTRZEBNE METODY**
- CREATE/UPDATE/DELETE combinations
- Set images dla combination
- **Set attributes (product_option_values)** ← TO JEST KLUCZOWE!

### 2.3 Brakujący VariantSyncService

**OCZEKIWANE**: `app/Services/PrestaShop/VariantSyncService.php` lub `PrestaShopVariantSyncService.php`

**RZECZYWISTOŚĆ**: Plik NIE ISTNIEJE!

```bash
Grep "class.*VariantSync" app/Services/PrestaShop/
# Result: No files found
```

**❌ ROOT CAUSE #2: BRAK DEDYKOWANEGO SERWISU DO SYNCHRONIZACJI WARIANTÓW**

Architektura ma:
- ✅ `ProductSyncStrategy` - synchronizuje product base
- ✅ `PrestaShopPriceExporter` - synchronizuje specific_prices
- ✅ `CategoryAssociationService` - synchronizuje categories
- ✅ `FeatureSyncService` - synchronizuje product_features
- ❌ **BRAK: `VariantSyncService`** - do synchronizacji combinations

---

## 🔍 ROOT CAUSE - PROBLEM 2 (PUSH)

### ROOT CAUSE #2A: Brak wywołania synchronizacji combinations

**LOKALIZACJA**: `ProductSyncStrategy::syncToPrestaShop()` linia ~320

**PROBLEM**: Po sync'u produktu, media, features - NIE MA wywołania sync'u variants

**OCZEKIWANE**:
```php
// After syncFeaturesIfEnabled (line 323)
$this->syncVariantsIfEnabled($model, $shop, $externalId, $client);
```

**RZECZYWISTOŚĆ**: Brak tego wywołania!

### ROOT CAUSE #2B: Brak mappingu PPM variant attributes → PrestaShop product_option_values

**STRUKTURA PPM**:
```
product_variants (id, product_id, sku, name, is_active)
└─ variant_attributes (variant_id, attribute_type_id, value_id)
   ├─ attribute_types (id, name) ← np. "Kolor pojazdu"
   └─ attribute_values (id, attribute_type_id, value) ← np. "Czerwony"
```

**STRUKTURA PRESTASHOP**:
```
ps_product_attribute (id_product_attribute, id_product, reference)
└─ ps_product_attribute_combination (id_product_attribute, id_attribute)
   └─ ps_attribute (id_attribute, id_attribute_group, color)
      └─ ps_attribute_group (id_attribute_group, group_type)
```

**WYMAGANE MAPOWANIE**:
```php
// PPM → PrestaShop
attribute_types.id → ps_attribute_group.id_attribute_group (via mapping table)
attribute_values.id → ps_attribute.id_attribute (via mapping table)

// Example:
PPM: variant_attributes { attribute_type_id: 15 (Kolor pojazdu), value_id: 200 (Czerwony) }
→ MAPPING: attribute_type_mappings { ppm_type_id: 15, ps_group_id: 3 (Color) }
→ MAPPING: attribute_value_mappings { ppm_value_id: 200, ps_attribute_id: 456 (Red) }
→ PrestaShop API: setCombinationAttributes(combinationId, [456])
```

**PROBLEM**: Te mapping tables prawdopodobnie NIE ISTNIEJĄ!

---

## 📋 PODSUMOWANIE ROOT CAUSES

### PROBLEM 1 (PULL) - Data są pobierane, ale źle wyświetlane

**ROOT CAUSE**: View/Frontend logic używa złej property lub computed property nadpisuje pulled data

**LOKALIZACJA**:
- Blade template dla shop tab variants section
- Livewire computed properties w ProductForm
- Możliwy wire:poll conflict

**REKOMENDACJA**: Sprawdź Blade template i computed properties

---

### PROBLEM 2 (PUSH) - Combinations nie są synchronizowane

**ROOT CAUSE #1**: Brak wywołania synchronizacji variants w `ProductSyncStrategy::syncToPrestaShop()`

**ROOT CAUSE #2**: Brak dedykowanego `VariantSyncService` do obsługi combinations

**ROOT CAUSE #3**: Brak mapping tables dla PPM attributes → PrestaShop product_option_values

**REKOMENDACJA**: Implementacja kompleksowa:

1. **Utworzyć VariantSyncService** (wzorowanego na FeatureSyncService):
   ```php
   class VariantSyncService
   {
       public function syncVariantsForProduct(
           Product $product,
           PrestaShopShop $shop,
           int $prestashopProductId,
           BasePrestaShopClient $client
       ): array
   }
   ```

2. **Utworzyć AttributeMapper** (mapping PPM → PrestaShop):
   ```php
   class AttributeMapper
   {
       public function mapAttributeGroup(int $ppmTypeId, PrestaShopShop $shop): ?int
       public function mapAttributeValue(int $ppmValueId, PrestaShopShop $shop): ?int
       public function getOrCreatePrestaShopAttribute(...)
   }
   ```

3. **Dodać wywołanie w ProductSyncStrategy**:
   ```php
   // After syncFeaturesIfEnabled (line ~323)
   $this->syncVariantsIfEnabled($model, $shop, $externalId, $client);
   ```

4. **Implementacja syncVariantsIfEnabled**:
   ```php
   protected function syncVariantsIfEnabled(
       Product $product,
       PrestaShopShop $shop,
       int $prestashopProductId,
       BasePrestaShopClient $client
   ): void {
       // Get PPM variants
       $variants = $product->variants;

       foreach ($variants as $variant) {
           // Map attributes
           $attributeIds = [];
           foreach ($variant->attributes as $attr) {
               $psAttrId = $this->attributeMapper->mapAttributeValue($attr->value_id, $shop);
               if ($psAttrId) $attributeIds[] = $psAttrId;
           }

           // Create/update combination
           $combinationData = [
               'reference' => $variant->sku,
               'minimal_quantity' => 1,
           ];

           if ($variant->prestashop_combination_id) {
               $client->updateCombination($variant->prestashop_combination_id, $combinationData);
           } else {
               $response = $client->createCombination($prestashopProductId, $combinationData);
               $combinationId = $response['combination']['id'];
           }

           // Set attributes
           $client->setCombinationAttributes($combinationId, $attributeIds);
       }
   }
   ```

---

## 🎯 KONKRETNE PROPOZYCJE NAPRAWY

### NAPRAWA PROBLEM 1 (PULL)

**KROK 1: Zidentyfikuj view**
```bash
Grep "prestaShopVariants\|getVariantsForShop" resources/views/livewire/products/management/
```

**KROK 2: Sprawdź computed properties**
```bash
Grep "getVariantsProperty\|getVariantsForShop" app/Http/Livewire/Products/Management/
```

**KROK 3: Fix view logic**
```blade
{{-- ❌ ZŁE --}}
@foreach($this->product->getVariantsForShop($selectedShopId) as $variant)

{{-- ✅ DOBRE --}}
@foreach(($prestaShopVariants['variants'] ?? collect()) as $variant)
```

---

### NAPRAWA PROBLEM 2 (PUSH)

**ETAP 1: Utworzyć VariantSyncService** (2-3h)
- Lokalizacja: `app/Services/PrestaShop/VariantSyncService.php`
- Wzorować na `FeatureSyncService.php`
- Metody: `syncVariantsForProduct()`, `syncSingleVariant()`

**ETAP 2: Utworzyć AttributeMapper** (3-4h)
- Lokalizacja: `app/Services/PrestaShop/Mappers/AttributeMapper.php`
- Wzorować na `CategoryMapper.php`
- Mapping tables: `attribute_type_mappings`, `attribute_value_mappings`
- Migration: `create_attribute_mappings_tables.php`

**ETAP 3: Integracja w ProductSyncStrategy** (1h)
- Dodać `syncVariantsIfEnabled()` w linii ~323
- Dependency injection dla `VariantSyncService` i `AttributeMapper`

**ETAP 4: Testing** (2h)
- Test product 11148
- Verify combinations created in PrestaShop
- Verify attribute values displayed correctly (nie "-")

**CAŁKOWITY CZAS: 8-10h**

---

## 📁 PLIKI DO UTWORZENIA

```
app/Services/PrestaShop/
├── VariantSyncService.php          # NEW - sync variants logic
└── Mappers/
    └── AttributeMapper.php         # NEW - PPM ↔ PS attribute mapping

database/migrations/
└── 2025_12_08_000001_create_attribute_mappings_tables.php

_DOCS/
└── VARIANT_SYNC_IMPLEMENTATION_GUIDE.md  # Implementation roadmap
```

---

## 🔧 NASTĘPNE KROKI

1. **POTWIERDŹ PROBLEM 1**: Sprawdź Blade template i computed properties dla shop tab variants
2. **ZAPLANUJ PROBLEM 2**: User decision - czy implementować full variant sync teraz czy odłożyć?
3. **CONTEXT7 LOOKUP**: Sprawdź PrestaShop docs dla combinations API best practices
4. **IMPLEMENTACJA**: Jeśli approved, rozpocznij od VariantSyncService

---

**Status**: ✅ Analiza ukończona - czekam na feedback użytkownika
