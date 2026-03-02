# PPM - Category Preview Modal Documentation

> **Wersja:** 1.1.0
> **Data:** 2026-02-24
> **Status:** Production Ready
> **Changelog:** v1.1.0 - Fix 3 bugow drzewka kategorii PPM + dynamiczny progress bar

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Properties](#4-properties)
5. [Metody Publiczne](#5-metody-publiczne)
6. [Workflow Importu](#6-workflow-importu)
7. [Tab: Drzewko Kategorii](#7-tab-drzewko-kategorii)
8. [Tab: Typy Produktow](#8-tab-typy-produktow)
9. [Eventy i Integracja](#9-eventy-i-integracja)
10. [CategoryTypeMapper Cascade](#10-categorytypemapper-cascade)
11. [Powiazane Joby](#11-powiazane-joby)
12. [Troubleshooting](#12-troubleshooting)
13. [Changelog](#13-changelog)

---

## 1. Overview

### 1.1 Opis modulu

**CategoryPreviewModal** to komponent Livewire odpowiedzialny za **podglad i zatwierdzanie kategorii** przed bulk importem produktow z PrestaShop. Jest centralnym elementem workflow importu PS → PPM, laczcym `AnalyzeMissingCategories` job z `BulkCreateCategories` i `BulkImportProducts`. Obsluguje drzewo kategorii PS, porownanie z PPM, wykrywanie typow produktow oraz rozwiazywanie konfliktow.

**Wyswietlany w:** ProductList jako modal overlay (dispatch `show-category-preview`)

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Komponent Livewire | 1 (najwiekszy plik w projekcie) |
| Modele | 3 (CategoryPreview, ShopMapping, ShopCategoryTypeMapping) |
| Serwisy | 3 (CategoryComparisonService, CategoryTypeMapper, ProductTypeDetector) |
| Joby | 3 (BulkCreateCategories, BulkImportProducts, ExpirePendingCategoryPreview) |
| Blade Views | 1 + CSS |
| Linie kodu (backend) | ~3285 |
| Linie kodu (frontend) | ~800 |
| Zakladki UI | 2 (Drzewko Kategorii, Typy Produktow) |

### 1.3 Kluczowe funkcjonalnosci

- **Drzewo kategorii PS** - hierarchiczny podglad kategorii znalezionych w produktach PS
- **Porownanie PS vs PPM** - oznaczanie istniejacych/brakujacych kategorii
- **Wykrywanie typow produktow** - cascade: ShopCategoryTypeMapping → ProductTypeDetector → fallback
- **Selekcja kategorii** - checkboxy do wyboru ktore kategorie utworzyc w PPM
- **Zatwierdzanie importu** - dispatch BulkCreateCategories → BulkImportProducts
- **Skip categories** - mozliwosc pominiecia tworzenia kategorii (import bez nich)
- **Paginacja typow** - 25 produktow na strone w tab "Typy Produktow"
- **Auto-expire** - 15 min timeout na zatwierdzenie (ExpirePendingCategoryPreview job)
- **Warianty** - konfiguracja importu wariantow (`importVariantsEnabled`, `variantImportConfig`)

### 1.4 Workflow

```
AnalyzeMissingCategories job
    |
    v
CategoryPreview (DB record - status: pending)
    |
    v
CategoryPreviewReady event → ProductList polling (3s)
    |
    v
dispatch('show-category-preview', previewId)
    |
    v
CategoryPreviewModal.show(previewId)
    |
    +--- Tab 1: Drzewko Kategorii (selekcja)
    +--- Tab 2: Typy Produktow (podglad detekcji)
    |
    v
User clicks "Zatwierdz"
    |
    +--- approve() → BulkCreateCategories::dispatch()
    |                      → BulkImportProducts::dispatch(skip_category_analysis=true)
    |
    +--- approveSkipCategories() → BulkImportProducts::dispatch() (bez kategorii)
    |
    v
preview.markApproved() → JobProgress update
```

---

## 2. Architektura Plikow

### 2.1 Livewire Component

| Plik | Linie | Opis |
|------|-------|------|
| `app/Http/Livewire/Components/CategoryPreviewModal.php` | ~3285 | Glowny komponent - drzewo, typy, approve |

### 2.2 Modele

| Model | Plik | Linie | Tabela |
|-------|------|-------|--------|
| CategoryPreview | `app/Models/CategoryPreview.php` | ~513 | `category_preview` |
| ShopMapping | `app/Models/ShopMapping.php` | - | `shop_mappings` |
| ShopCategoryTypeMapping | `app/Models/ShopCategoryTypeMapping.php` | ~105 | `shop_category_type_mappings` |

### 2.3 Serwisy

| Serwis | Plik | Linie | Przeznaczenie |
|--------|------|-------|---------------|
| CategoryTypeMapper | `app/Services/Import/CategoryTypeMapper.php` | ~169 | Cascade: PS mapping → keywords → fallback |
| ProductTypeDetector | `app/Services/Import/ProductTypeDetector.php` | ~170 | Keyword matching na nazwach kategorii |
| CategoryComparisonService | `app/Services/CategoryComparisonService.php` | - | Porownanie PS vs PPM categorii |

### 2.4 Blade Views

```
resources/views/livewire/components/
+-- category-preview-modal.blade.php                         # Modal z zakladkami (~800 LOC)
+-- partials/category-preview-tree-node.blade.php            # Rekurencyjny node drzewa PPM
```

### 2.5 CSS

```
resources/css/components/
+-- category-preview-modal.css          # Style modalu
```

---

## 3. Schema Bazy Danych

### 3.1 Tabela: `category_preview`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `job_id` | UUID | NO | - | ID joba AnalyzeMissingCategories |
| `shop_id` | BIGINT UNSIGNED | NO | - | FK -> `prestashop_shops.id` CASCADE |
| `category_tree_json` | JSON | NO | - | Hierarchiczne drzewo kategorii |
| `total_categories` | INT UNSIGNED | NO | 0 | |
| `user_selection_json` | JSON | YES | NULL | Wybrane przez usera kategorie |
| `import_context_json` | JSON | YES | NULL | `{analyzed_products, product_ids, mode, options}` |
| `status` | ENUM | NO | 'pending' | pending/approved/rejected/expired |
| `expires_at` | TIMESTAMP | NO | - | Auto: now()+1h |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

**Indeksy:** `idx_job_shop` (job_id, shop_id), `idx_shop_status` (shop_id, status), (expires_at)

### 3.2 Tabela: `shop_category_type_mappings`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `shop_id` | BIGINT UNSIGNED | NO | - | FK -> `prestashop_shops.id` |
| `category_id` | BIGINT UNSIGNED | NO | - | **PS category ID** (NOT PPM!) |
| `product_type_id` | BIGINT UNSIGNED | NO | - | FK -> `product_types.id` |
| `include_children` | BOOLEAN | NO | true | Dziedziczenie na dzieci |
| `priority` | SMALLINT | NO | 50 | Wyzszy = wazniejszy |
| `is_active` | BOOLEAN | NO | true | |
| `created_by` | BIGINT UNSIGNED | YES | NULL | FK -> `users.id` |

**UNIQUE:** `(shop_id, category_id)`

### 3.3 Tabela: `shop_mappings`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `shop_id` | BIGINT UNSIGNED | NO | - | FK -> `prestashop_shops.id` |
| `mapping_type` | ENUM | NO | - | category/attribute/feature/warehouse/... |
| `ppm_value` | VARCHAR(255) | NO | - | PPM ID lub nazwa |
| `prestashop_id` | BIGINT UNSIGNED | NO | - | PS entity ID |
| `prestashop_value` | VARCHAR(255) | YES | NULL | PS nazwa |
| `is_active` | BOOLEAN | NO | true | |

**UNIQUE:** `(shop_id, mapping_type, ppm_value)`

### 3.4 Relacje miedzy tabelami

```
category_preview
    |--- N:1 ---> prestashop_shops
    |--- 1:1 ---> job_progress (via job_id)

shop_category_type_mappings
    |--- N:1 ---> prestashop_shops
    |--- N:1 ---> product_types
    |--- N:1 ---> users (created_by)

shop_mappings
    |--- N:1 ---> prestashop_shops
```

---

## 4. Properties

### 4.1 Stan modalu

```php
public bool $isOpen = false;
public ?int $previewId = null;
public array $categoryTree = [];
public int $totalCount = 0;
public ?int $shopId = null;
public string $shopName = '';
public string $modalInstanceId = '';     // Anti-duplicate
```

### 4.2 Selekcja kategorii

```php
public array $selectedCategoryIds = [];
public array $manuallySelectedCategories = [];
public bool $skipCategories = false;
public bool $isApproving = false;
```

### 4.3 Zakladki i paginacja

```php
public string $activeTab = 'categories'; // 'categories' | 'product_types'
public int $productTypesPage = 1;
```

### 4.4 Porownanie i konflikty

```php
public array $comparisonTree = [];
public array $comparisonSummary = [];
public bool $showComparisonView = false;
public array $detectedConflicts = [];
public bool $showConflicts = false;
public bool $showConflictResolutionModal = false;
```

### 4.5 Warianty

```php
public bool $importVariantsEnabled = false;
public array $variantImportConfig = [];
public int $estimatedVariantsCount = 0;
```

### 4.6 Drzewo kategorii

```php
public array $expandedNodes = [];
public bool $showNoCategoryWarning = false;
public int $productsWithoutCategoriesCount = 0;
```

---

## 5. Metody Publiczne

### 5.1 Cykl zycia

| Metoda | Opis |
|--------|------|
| `show(int $previewId)` | Laduje CategoryPreview, buduje merged PPM tree, reconciliation licznikow, auto-expand |
| `close()` | Reset calego stanu, zamkniecie modalu |
| `toggleNode(int\|string $id)` | Zwiń/rozwiń node w drzewie PPM |

### 5.2 Selekcja kategorii

| Metoda | Opis |
|--------|------|
| `toggleCategory(int $categoryId)` | Zaznacz/odznacz kategorie |
| `isCategorySelected(int $categoryId)` | Sprawdz stan zaznaczenia |
| `selectAll()` | Zaznacz wszystkie kategorie |
| `deselectAll()` | Odznacz wszystkie |

### 5.3 Zatwierdzanie

| Metoda | Opis |
|--------|------|
| `approve()` | Zatwierdza wybor → `BulkCreateCategories::dispatch()` |
| `toggleSkipCategories()` | Przelacza flage pominiecia |

### 5.4 Zakladki i nawigacja

| Metoda | Opis |
|--------|------|
| `setActiveTab(string $tab)` | Przelacza zakladke (categories/product_types) |
| `setProductTypesPage(int $page)` | Nawigacja stron w tab "Typy Produktow" |

### 5.5 Logika `approve()`

```php
// Walidacja selekcji
$preview->setUserSelection($selectedCategoryIds);
$preview->markApproved();

// Dispatch z kontekstem importu
BulkCreateCategories::dispatch(
    previewId: $preview->id,
    selectedIds: $selectedCategoryIds,
    importContext: $preview->import_context_json
);
```

---

## 6. Workflow Importu

### 6.1 Kompletny flow

```
User klika "Importuj z PrestaShop" w ProductList
    |
    v
BulkImportProducts::dispatch(shop, mode, options)
    |
    +--- shouldAnalyzeCategories() == true
    |        |
    |        v
    |    AnalyzeMissingCategories::dispatch(productIds, shop, jobId)
    |        |
    |        +--- Pobiera produkty z PS API
    |        +--- Wyciaga category IDs z associations
    |        +--- Porownuje z ShopMapping (existing vs missing)
    |        +--- Buduje drzewo kategorii
    |        +--- Wzbogaca analyzedProducts o nazwy kategorii
    |        +--- Tworzy CategoryPreview record (status: pending)
    |        +--- Dispatches CategoryPreviewReady event
    |        +--- Dispatches ExpirePendingCategoryPreview (delay: 15 min)
    |        |
    |        v
    |    ProductList polling (3s) wykrywa nowy CategoryPreview
    |        |
    |        v
    |    dispatch('show-category-preview', previewId)
    |        |
    |        v
    |    CategoryPreviewModal.show(previewId)
    |        +--- Tab 1: Drzewko Kategorii
    |        +--- Tab 2: Typy Produktow (122 items, paginated)
    |        |
    |        v
    |    User zatwierdza
    |        +--- approve() → BulkCreateCategories::dispatch()
    |                              → BulkImportProducts::dispatch(skip_category_analysis=true)
    |
    +--- shouldAnalyzeCategories() == false (skip_category_analysis=true)
             |
             v
         PrestaShopImportService per product
             +--- CategoryTypeMapper cascade
             +--- Features, compatibility, variants import
             +--- ProductShopData + SyncLog
```

### 6.2 import_context_json

```json
{
    "product_ids": [123, 456, 789],
    "analyzed_products": [
        {
            "ps_id": 123,
            "reference": "W-50E-STD-25",
            "name": "Pit Bike YCF W50",
            "categories": [
                {"id": 800, "name": "Pojazdy"},
                {"id": 849, "name": "Elektryczne"}
            ]
        }
    ],
    "mode": "category",
    "options": {
        "category_id": 800,
        "import_with_variants": true
    }
}
```

---

## 7. Tab: Drzewko Kategorii

### 7.1 PPM Tree Merge (v1.1.0)

Drzewko kategorii wyswietla **JEDNO polaczone drzewo PPM** z kategoriami "do dodania" zagniezdzonymi pod istniejacymi rodzicami. Budowanie:

```
show() → buildMergedPpmTree()
           |
           +-- 1. Pobierz pelne drzewo PPM (Category model, root → children)
           +-- 2. getMissingCategoriesFlat() → flatten drzewa PS
           |       +-- resolveParentPathFromPsId() (ShopMapping lookup)
           |       +-- Fallback: Category::where('name', ...) (name matching)
           |       +-- Zwraca flat list z PPM-root-relative parent paths
           +-- 3. mergeMissingIntoTree() → osadza nowe w drzewie PPM
           |       +-- addMissingToParent() - EXACT path matching
           |       +-- findExistingCategoryInTree() - deduplication po nazwie
           +-- Return: single merged tree
```

**Kluczowe metody (v1.1.0):**

| Metoda | Widocznosc | Opis |
|--------|-----------|------|
| `buildMergedPpmTree()` | private | Buduje polaczone drzewo PPM+PS (bez side effectow) |
| `extractToAddIdsFromMergedTree()` | private | Zbiera PS IDs nodow `to_add` z merged tree |
| `updateProgressBarLabel()` | private | Aktualizuje tekst przycisku w progress barze |
| `autoExpandNewBranches()` | protected | Rozszerza expandedNodes o nody z nowymi kategoriami |

### 7.2 Reconciliation w show() (v1.1.0)

Po zbudowaniu merged tree, `show()` wykonuje reconciliation:

```php
$mergedTree = $this->buildMergedPpmTree();
$this->autoExpandNewBranches($mergedTree);

$visibleNewIds = $this->extractToAddIdsFromMergedTree($mergedTree);
$this->selectedCategoryIds = array_intersect($rawIds, $visibleNewIds);
$this->totalCount = count($visibleNewIds);

$this->updateProgressBarLabel($preview, $this->totalCount);
```

Zapewnia ze:
- `selectedCategoryIds` odpowiada TYLKO kategoriom widocznym w drzewie (po deduplication)
- `totalCount` jest spojny z faktyczna liczba nodow "do dodania"
- Progress bar dynamicznie aktualizowany z poprawna liczba
- `expandedNodes` ustawione PRZED Livewire snapshot (nie w computed property)

### 7.3 Rozwiazywanie sciezek (v1.1.0)

`flattenMissingCategories()` uzywa 2-stopniowej strategii do rozwiazywania sciezek:

1. **ShopMapping lookup** - `resolveParentPathFromPsId()` - gdy istnieja mappingi
2. **Name matching** - `Category::where('name', $name)` - fallback dla sklepow bez mappingow

Dzieki temu kategorie "do dodania" sa poprawnie osadzane pod istniejacymi rodzicami PPM (np. "Czesci do Buggy" pod "Baza > Wszystko > Czesci zamienne").

### 7.4 Struktura node'a w merged tree

```json
{
    "id": 350,                           // PPM ID (existing) lub "new_137" (to_add)
    "name": "Czesci zamienne",
    "level": 2,
    "status": "existing",               // "existing" | "to_add"
    "children": [...],
    "prestashop_id": null,              // null (existing) lub PS ID (to_add)
    "has_new_descendants": true,
    "is_product_category": false,
    "has_product_descendants": true
}
```

### 7.5 Oznaczenie "exists_in_ppm"

Sprawdzane przez `ShopMapping`:
1. `ShopMapping` exists for `prestashop_id` + `shop_id` + `mapping_type=category`
2. AND referenced PPM `Category` (via `ppm_value`) actually exists in DB

### 7.6 Selekcja

- Checkboxy per kategoria (toggle green/gray)
- "Zaznacz wszystkie" / "Odznacz" przyciski
- Domyslnie zaznaczone: BRAKUJACE kategorie widoczne w merged tree
- `selectAll()` reconciluje z merged tree (v1.1.0)

---

## 8. Tab: Typy Produktow

### 8.1 Kolumny

| Kolumna | Zrodlo | Opis |
|---------|--------|------|
| SKU | `analyzed_products[].reference` | Referencja PS |
| NAZWA | `analyzed_products[].name` | Nazwa produktu PS |
| WYKRYTY TYP | Cascade detection | Badge z kolorem |

### 8.2 Cascade detekcji typu (priorytet)

```
Per produkt:
  1. ShopCategoryTypeMapping::active()
       ->forShop($shopId)
       ->whereIn('category_id', $psCategoryIds)  // PS category IDs!
       ->byPriority()
       ->first()
     → Jesli match: uzyj mapping.productType

  2. ProductTypeDetector::detectWithInfo($categoryNames)
     → Keywords: pojazd/pojazdy/pit bike/quad/buggy/elektryczne...
     → Keywords: czesc/czesci/zamienn...
     → Keywords: akcesori, olej, odziez, outlet

  3. Fallback: "Inne" (slug: inne, color: default)
```

### 8.3 Paginacja

- 25 produktow na strone
- Nawigacja: `setProductTypesPage(int $page)`
- Tlotal count w naglowku zakladki: "Typy Produktow **122**"

---

## 9. Eventy i Integracja

### 9.1 Odbierane eventy

| Event | Metoda | Zrodlo |
|-------|--------|--------|
| `show-category-preview` | `show(int $previewId)` | ProductList |

### 9.2 Wysylane eventy

| Event | Metoda | Dane |
|-------|--------|------|
| `success` | `approve()` | `message` |
| `error` | `approve()` | `message` |
| `user-action-completed` | `approve()` | `jobId` (ukrywa przycisk w JobProgressBar) |

### 9.3 Integracja z ProductList

```
ProductList.checkForPendingCategoryPreviews() [polling 3s]
    → query CategoryPreview::pending()->forShop()
    → if found → dispatch('show-category-preview', previewId)
    → CategoryPreviewModal.show(previewId)
```

---

## 10. CategoryTypeMapper Cascade

### 10.1 Architektura

```
+---------------------------+
| CategoryPreviewModal      |
| (Tab: Typy Produktow)     |
+---------------------------+
         |
         | PS category IDs per product
         v
+---------------------------+     +---------------------------+
| ShopCategoryTypeMapping   | --> | ProductType               |
| (admin-defined mapping)   |     | (pojazd, czesc-zamienna)  |
+---------------------------+     +---------------------------+
         |
         | nie znaleziono? fallback
         v
+---------------------------+     +---------------------------+
| ProductTypeDetector       | --> | ProductType               |
| (keyword matching)        |     | (dopasowanie po slowach)  |
+---------------------------+     +---------------------------+
         |
         | nie znaleziono?
         v
     "Inne" (fallback)
```

### 10.2 Kluczowa roznica: PS ID vs PPM ID

**KRYTYCZNE:** `shop_category_type_mappings.category_id` przechowuje **PrestaShop category ID** (np. 800), NIE PPM category ID!

- Panel admin (`/admin/product-parameters?activeTab=category-type-mappings`) laduje kategorie PS z API
- Uzytkownik wybiera PS kategorie i mapuje na ProductType
- Mapping jest per-shop (kazdy sklep ma inne kategorie PS)

### 10.3 Slowa kluczowe ProductTypeDetector

| Typ | Slowa kluczowe |
|-----|---------------|
| Pojazd | pojazd, pojazdy, pit bike, pitbike, dirt bike, quad, quady, buggy, homologacja, mini gp, motorower, supermoto, elektryczne, electric |
| Czesc zamienna | czesc, czesci, zamienn, zamienne, zamiennik |
| Akcesoria | akcesori, akcesorium, akcesoria |
| Oleje i chemia | olej, oleje, chemia, klej, plyn, smar |
| Odziez | odziez, stroj, komin, t-shirt, nakolannik, koszul, czapk, okular |
| Outlet | outlet |

---

## 11. Powiazane Joby

### 11.1 AnalyzeMissingCategories

| Parametr | Wartosc |
|----------|---------|
| Plik | `app/Jobs/PrestaShop/AnalyzeMissingCategories.php` |
| Linie | ~812 |
| Queue | default |
| Timeout | 600s (10 min) |
| Tries | 3 |
| Input | `$productIds, $shop, $jobId, $originalImportOptions` |
| Output | `CategoryPreview` record + `CategoryPreviewReady` event |

### 11.2 BulkCreateCategories

| Parametr | Wartosc |
|----------|---------|
| Plik | `app/Jobs/PrestaShop/BulkCreateCategories.php` |
| Linie | ~401 |
| Queue | default |
| Timeout | 900s (15 min) |
| Tries | 3 |
| Input | `$previewId, $selectedCategoryIds, $originalImportOptions` |
| Output | Kategorie w PPM → dispatch `BulkImportProducts` |

### 11.3 ExpirePendingCategoryPreview

| Parametr | Wartosc |
|----------|---------|
| Plik | `app/Jobs/PrestaShop/ExpirePendingCategoryPreview.php` |
| Linie | ~164 |
| Queue | default (delayed 15 min) |
| Timeout | 60s |
| Input | `$previewId` |
| Output | CategoryPreview status → expired, JobProgress → failed |

### 11.4 BulkImportProducts

| Parametr | Wartosc |
|----------|---------|
| Plik | `app/Jobs/PrestaShop/BulkImportProducts.php` |
| Linie | ~1065 |
| Queue | default |
| Timeout | 900s (15 min) |
| Tries | 3 |
| Input | `$shop, $mode, $options, $jobId` |
| Output | Products imported → progress-completed event |

---

## 12. Troubleshooting

### 12.1 Modal nie otwiera sie po imporcie

**Przyczyna:** Polling `checkForPendingCategoryPreviews()` nie wykrywa nowego preview lub wire:poll jest w @if.

**Rozwiazanie:**
1. Sprawdz czy `CategoryPreview` record istnieje: `SELECT * FROM category_preview WHERE status='pending'`
2. Sprawdz czy polling div jest POZA @if w blade
3. Sprawdz logi: `AnalyzeMissingCategories` errors
4. Sprawdz czy preview nie expired (15 min timeout)

### 12.2 WYKRYTY TYP pokazuje "Inne" zamiast poprawnego

**Przyczyna:** Brak mappingu w `shop_category_type_mappings` lub ProductTypeDetector nie ma odpowiednich keywords.

**Rozwiazanie:**
1. Sprawdz `/admin/product-parameters?activeTab=category-type-mappings`
2. Sprawdz czy mapping istnieje dla danego `shop_id` + PS `category_id`
3. Sprawdz czy `is_active = true`
4. Jesli brak mappingu: ProductTypeDetector uzywa nazw kategorii PPM (via ShopMapping) - sprawdz czy te nazwy zawieraja keywords

### 12.3 "Zatwierdz" nie dziala / modal zamraza sie

**Przyczyna:** `CategoryPreview` juz nie jest w statusie `pending` (expired lub juz approved).

**Rozwiazanie:**
1. Sprawdz `category_preview.status` - jesli `expired`, trzeba ponownie uruchomic import
2. Sprawdz `category_preview.expires_at` - jesli przeszly, extend: `$preview->extendExpiration(1)`
3. Sprawdz logi `BulkCreateCategories` na bledy

### 12.4 Preview nie wygasa (wisi w stanie pending)

**Przyczyna:** `ExpirePendingCategoryPreview` job nie zostal dispatched lub queue worker nie dziala.

**Rozwiazanie:**
1. Sprawdz `jobs` table: `SELECT * FROM jobs WHERE payload LIKE '%ExpirePending%'`
2. Sprawdz czy queue worker dziala: `php artisan queue:work`
3. Manualnie expire: `CategoryPreview::find(X)->markExpired()`

### 12.5 Drzewko zwinięte / toggle nie działa (FIXED v1.1.0)

**Przyczyna (historyczna):** `autoExpandNewBranches()` wywoływane w computed property `getFullPpmTreeProperty()`. Zmiany `expandedNodes` tracone po Livewire snapshot. Blade template mial override `($isNew && $hasChildren)` wymuszajacy rozwinieciee.

**Rozwiazanie:** `autoExpandNewBranches()` przeniesione do `show()` (przed snapshot). Blade usuniety override. Toggle teraz operuje wylacznie na `expandedNodes`.

### 12.6 Licznik rozny od widocznych kategorii (FIXED v1.1.0)

**Przyczyna (historyczna):** `extractNewCategoryIds()` liczyl z surowego PS tree (10), ale `mergeMissingIntoTree()` deduplikowal czesc (np. "Wszystko" juz istnieje w PPM). Wynik: "Wybrano: 10" ale widocznych np. 7.

**Rozwiazanie:** Reconciliation w `show()` - `extractToAddIdsFromMergedTree()` liczy z merged tree. Progress bar aktualizowany dynamicznie.

### 12.7 Duplikaty kategorii / dwa drzewka (FIXED v1.1.0)

**Przyczyna (historyczna):** `flattenMissingCategories()` budowal sciezki PS-relative ("Wszystko > Czesci zamienne"), ale `addMissingToParent()` oczekiwal PPM-relative ("Baza > Wszystko > Czesci zamienne"). Mismatch powodowal ze kategorie trafialy jako orphan root nodes.

**Rozwiazanie:** Name-based PPM resolution w `flattenMissingCategories()` - kazda istniejaca kategoria rozwiazywana do pelnej sciezki PPM (najpierw ShopMapping, fallback: `Category::where('name', ...)`).

### 12.8 Warianty nie sa importowane

**Przyczyna:** `importVariantsEnabled = false` w modal lub `import_with_variants = false` w options.

**Rozwiazanie:**
1. Sprawdz checkbox "Importuj warianty" w modal
2. Sprawdz `import_context_json.options.import_with_variants`
3. Sprawdz logi `BulkImportProducts` - sekcja variant sync

---

## 13. Changelog

### v1.1.0 (2026-02-24)

- **BUG1 FIX:** Drzewko auto-rozwijane - `autoExpandNewBranches()` przeniesione z computed property do `show()` (Livewire snapshot issue)
- **BUG2 FIX:** Toggle zwijania/rozwijania - usuniety override `($isNew && $hasChildren)` z blade template `category-preview-tree-node.blade.php`
- **BUG3 FIX:** Spojne liczniki - `extractToAddIdsFromMergedTree()` reconciliation + progress bar update
- **NOWE:** `buildMergedPpmTree()` - wyodrebniona metoda budowania drzewa (bez side effectow)
- **NOWE:** `extractToAddIdsFromMergedTree()` - liczy faktycznie widoczne kategorie "do dodania"
- **NOWE:** `updateProgressBarLabel()` - dynamicznie aktualizuje tekst przycisku w progress barze
- **FIX:** `flattenMissingCategories()` - 2-stopniowa strategia rozwiazywania sciezek PPM (ShopMapping + name match)
- **FIX:** Kategorie prawidlowo zagniezdzone w JEDNYM drzewie PPM (bez duplikatow i oddzielnych podrzew)
- **Pliki:** `CategoryPreviewModal.php` (+155/-32), `category-preview-tree-node.blade.php` (1 linia)

### v1.0.0 (2026-02-23)

- **Inicjalna dokumentacja** - kompletny audyt CategoryPreviewModal
- **CategoryTypeMapper cascade** - dokumentacja 3-stopniowej detekcji typow
- **Fix 2026-02-23:** CategoryPreviewModal teraz uzywa `ShopCategoryTypeMapping` jako priorytet (wczesniej TYLKO `ProductTypeDetector`)
- **Fix 2026-02-23:** `ProductTypeDetector` rozszerzony o keywords: `pojazd`, `pojazdy`, `elektryczne`, `electric`
- **Fix 2026-02-23:** `PrestaShopImportService` - re-resolve type dla produktow z fallback "inne" przy re-imporcie

---

## Appendix A: Kluczowe pliki

| Typ | Sciezka |
|-----|---------|
| Modal Component | `app/Http/Livewire/Components/CategoryPreviewModal.php` |
| Blade View | `resources/views/livewire/components/category-preview-modal.blade.php` |
| CSS | `resources/css/components/category-preview-modal.css` |
| Model CategoryPreview | `app/Models/CategoryPreview.php` |
| Model ShopCategoryTypeMapping | `app/Models/ShopCategoryTypeMapping.php` |
| Service CategoryTypeMapper | `app/Services/Import/CategoryTypeMapper.php` |
| Service ProductTypeDetector | `app/Services/Import/ProductTypeDetector.php` |
| Job AnalyzeMissing | `app/Jobs/PrestaShop/AnalyzeMissingCategories.php` |
| Job BulkCreate | `app/Jobs/PrestaShop/BulkCreateCategories.php` |
| Job Expire | `app/Jobs/PrestaShop/ExpirePendingCategoryPreview.php` |
| Admin Panel | `/admin/product-parameters?activeTab=category-type-mappings` |
