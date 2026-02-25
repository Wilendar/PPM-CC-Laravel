# PPM - Product List Documentation

> **Wersja:** 3.0.0
> **Data:** 2026-02-25
> **Status:** Production Ready
> **Changelog:** Kompletna przebudowa UX - refactoring na traity/partiale, nowe kolumny Cena/Stan, search bar, category tree, filter presets, column customizer

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Properties](#4-properties)
5. [Metody Publiczne](#5-metody-publiczne)
6. [Computed Properties](#6-computed-properties)
7. [Eventy i Polling](#7-eventy-i-polling)
8. [UI - Blade View](#8-ui---blade-view)
9. [Import z PrestaShop](#9-import-z-prestashop)
10. [Import z ERP](#10-import-z-erp)
11. [Bulk Operations](#11-bulk-operations)
12. [Integracja z CategoryPreviewModal](#12-integracja-z-categorypreviewmodal)
13. [Nowe Funkcjonalnosci v3.0](#13-nowe-funkcjonalnosci-v30)
14. [Troubleshooting](#14-troubleshooting)
15. [Changelog](#15-changelog)

---

## 1. Overview

### 1.1 Opis modulu

**ProductList** to glowny komponent listowania produktow w systemie PPM. Wyswietla tabele/siatke produktow z zaawansowanym filtrowaniem, sortowaniem, bulk akcjami, importem z PrestaShop i ERP, podgladem produktow oraz integracjami sync/publish.

W wersji 3.0 komponent zostal rozbity z monolitu (~3300 LOC) na modularna architekture traitow (~443 LOC core + 8 traitow) z 20+ partialami blade.

**URL Panelu:** `/admin/products`

### 1.2 Statystyki

| Metryka | v2.0 | v3.0 |
|---------|------|------|
| ProductList.php | ~3316 LOC | ~443 LOC |
| product-list.blade.php | ~2381 LOC | ~184 LOC |
| Traity | 1 | 8 |
| Blade partiale | 4 | 20+ |
| Kolumny tabeli | 10 | 12 (+ Cena, Stan) |
| Filtry | 11 | 15 (+ grupa cenowa, magazyn, zakresy) |
| CSS pliki | 0 dedykowanych | 2 (search, columns) |
| Nowe modele | 0 | 1 (UserFilterPreset) |

### 1.3 Kluczowe funkcjonalnosci

**Istniejace (v2.0):**
- Filtrowanie wielopoziomowe - SKU/nazwa, kategoria, status, typ, cena, data, integracja, media, status danych
- Dwa tryby widoku - tabela z wariantami (expandable rows) + siatka kart
- Bulk operations - aktywacja/deaktywacja, kategorie, eksport CSV, wysylanie, usuwanie
- Import z PrestaShop - tryby all/category/individual z CategoryPreviewModal
- Import z ERP - BaseLinker z wyszukiwaniem
- Quick actions - preview, sync, publish, duplikacja, zmiana statusu
- Real-time polling + progress tracking

**Nowe (v3.0):**
- **Search bar w headerze** - widoczny zawsze, nie wymaga otwierania filtrow
- **Category tree dropdown** - hierarchiczne drzewo z wyszukiwaniem (Alpine.js)
- **Kolumna Cena** - domyslna cena z tooltipem wszystkich grup cenowych, przelacznik N/B
- **Kolumna Stan** - domyslny stan z tooltipem WSZYSTKICH magazynow, limit (minimum_stock)
- **Dynamiczne filtry** - typy produktow, statusy integracji z DB (nie hardcoded)
- **Rozszerzone filtry** - grupa cenowa + zakres cen, magazyn + zakres stanow
- **Sticky header tabeli** - naglowki przypiete przy scrollowaniu
- **Kolorowanie wierszy** - error (czerwony), warning (zolty), syncing (niebieski)
- **Filter presets** - zapisywanie/wczytywanie zestawow filtrow z DB
- **Column customizer** - ukrywanie/pokazywanie kolumn (localStorage)
- **Persystencja netto/brutto** - zapamietywanie trybu wyswietlania cen w session

---

## 2. Architektura Plikow

### 2.1 Livewire Component + Traity

| Plik | Linie | Opis |
|------|-------|------|
| `ProductList.php` | ~443 | Core: mount, render, listeners, lifecycle |
| `Traits/ProductListFilters.php` | ~380 | Filtry, wyszukiwanie, buildProductQuery, applySorting |
| `Traits/ProductListColumns.php` | ~370 | Sort, pagination, computed products/categories, Cena/Stan logika |
| `Traits/ProductListBulkActions.php` | ~811 | Bulk operacje, selekcja, modals |
| `Traits/ProductListBulkCategories.php` | ~460 | Bulk kategorie (assign/remove/move) |
| `Traits/ProductListPrestaShopImport.php` | ~657 | Import z PrestaShop |
| `Traits/ProductListERPImport.php` | ~461 | Import z ERP BaseLinker |
| `Traits/ProductListQuickActions.php` | ~320 | Quick actions per produkt |
| `Traits/ProductListPreferences.php` | ~46 | Session persistence (perPage, viewMode, sort, priceDisplayMode) |
| `Traits/ProductListPresets.php` | ~120 | Saved filter presets (DB) |

### 2.2 Blade Views

```
resources/views/livewire/products/listing/
+-- product-list.blade.php              # Glowny layout (~184 LOC)
+-- partials/
    +-- header-bar.blade.php            # Tytul, search bar, akcje, view toggle
    +-- filters-panel.blade.php         # Panel filtrow (15 filtrow)
    +-- bulk-actions-bar.blade.php      # Select all + bulk actions
    +-- table-view.blade.php            # Tabela z thead
    +-- table-row.blade.php             # Wiersz produktu (Cena/Stan tooltips)
    +-- grid-view.blade.php             # Widok siatki
    +-- variant-row.blade.php           # Wiersz wariantu
    +-- category-tree-dropdown.blade.php # Hierarchiczne drzewo kategorii (Alpine)
    +-- column-customizer.blade.php     # Dropdown ukrywania kolumn (Alpine)
    +-- filter-presets.blade.php        # Dropdown presetow filtrow
    +-- status-column.blade.php         # Kolumna "Zgodnosc"
    +-- status-filters.blade.php        # Status danych filtry
    +-- preview-modal.blade.php         # Quick preview
    +-- quick-send-modal.blade.php      # Wyslij na sklepy
    +-- delete-modal.blade.php          # Potwierdzenie usuwania
    +-- bulk-delete-modal.blade.php     # Bulk delete
    +-- bulk-assign-categories-modal.blade.php
    +-- bulk-remove-categories-modal.blade.php
    +-- bulk-move-categories-modal.blade.php
    +-- import-prestashop-modal.blade.php
    +-- import-prestashop-mode-all.blade.php
    +-- import-prestashop-mode-category.blade.php
    +-- import-prestashop-mode-individual.blade.php
    +-- category-analysis-overlay.blade.php
    +-- erp-import-modal.blade.php
```

### 2.3 CSS

| Plik | Opis |
|------|------|
| `resources/css/products/product-list-search.css` | Search bar w headerze, category tree dropdown |
| `resources/css/products/product-list-columns.css` | Tooltips (cena/stan), stock indicators, sticky header, row coloring |

### 2.4 Model + Migracja

| Plik | Opis |
|------|------|
| `app/Models/UserFilterPreset.php` | Saved filter presets per user |
| `database/migrations/2026_02_25_120000_create_user_filter_presets_table.php` | Tabela user_filter_presets |

### 2.5 Serwisy (bez zmian)

| Serwis | Przeznaczenie |
|--------|---------------|
| ProductStatusAggregator | Oblicza ProductStatusDTO per produkt |
| JobProgressService | Aktywne/ostatnie joby importu/synca |
| PrestaShopClientFactory | Fabryka klientow PS8/PS9 |

---

## 3. Schema Bazy Danych

### 3.1 NOWA Tabela: `user_filter_presets`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `user_id` | BIGINT UNSIGNED | NO | - | FK -> `users(id)` CASCADE |
| `name` | VARCHAR(255) | NO | - | Nazwa presetu |
| `context` | VARCHAR(50) | NO | `product_list` | Kontekst (reuse w innych listach) |
| `filters` | JSON | NO | - | Serializowany stan filtrow |
| `is_default` | BOOLEAN | NO | false | Automatycznie ladowany przy mount |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

**UNIQUE:** `(user_id, name, context)`
**INDEX:** `(user_id, context)`

### 3.2 Tabela: `products` (bez zmian)

Patrz v2.0 dokumentacja.

---

## 4. Properties

### 4.1 Filtrowanie (ProductListFilters trait)

```php
public string $search = '';
public string $categoryFilter = '';
public string $statusFilter = 'all';
public string $stockFilter = 'all';
public string $productTypeFilter = 'all';
public float $priceMin = 0;
public float $priceMax = 10000;
public string $dateFrom = '';
public string $dateTo = '';
public string $dateType = 'created_at';
public string $integrationFilter = 'all';
public string $mediaFilter = 'all';
public ?string $dataStatusFilter = null;
public array $issueTypeFilters = [];
// NOWE v3.0:
public string $priceGroupFilter = '';         // Filtr grupy cenowej
public ?int $stockMin = null;                 // Min stan magazynowy
public ?int $stockMax = null;                 // Max stan magazynowy
public string $stockWarehouseFilter = '';     // Filtr magazynu
```

### 4.2 Sortowanie i wyswietlanie (ProductListColumns trait)

```php
public string $sortBy = 'updated_at';
public string $sortDirection = 'desc';
public int $perPage = 25;
public string $viewMode = 'table';
// NOWE v3.0:
public string $priceDisplayMode = 'netto';    // netto/brutto (persisted w session)
```

### 4.3 Presets (ProductListPresets trait)

```php
public bool $showPresetModal = false;
public string $newPresetName = '';
public bool $newPresetIsDefault = false;
```

---

## 5. Metody Publiczne

### 5.1 NOWE metody v3.0

| Metoda | Trait | Opis |
|--------|-------|------|
| `togglePriceDisplay()` | Columns | Przelacza netto/brutto + save session |
| `getDefaultPriceForProduct(Product)` | Columns | Cena z domyslnej grupy cenowej |
| `getAllPricesForProduct(Product)` | Columns | Dane tooltipa cen (grupa, netto, brutto, is_default) |
| `getDefaultStockForProduct(Product)` | Columns | Stan z domyslnego magazynu |
| `getAllStockForProduct(Product)` | Columns | Dane tooltipa stanow (WSZYSTKIE aktywne magazyny) |
| `formatPrice(?float)` | Columns | Formatowanie ceny "1 234,56 zl" |
| `getStockIndicatorClass(?int)` | Columns | CSS klasa koloru stanu (high/low/zero) |
| `saveCurrentFiltersAsPreset()` | Presets | Zapis biezacych filtrow do DB |
| `applyPreset(int)` | Presets | Wczytanie presetu z DB |
| `deletePreset(int)` | Presets | Usuniecie presetu |
| `loadDefaultPresetOnMount()` | Presets | Auto-load domyslnego presetu |

### 5.2 NOWE computed properties v3.0

| Property | Trait | Typ | Opis |
|----------|-------|-----|------|
| `availableProductTypes` | Filters | Collection | Dynamiczne typy produktow z DB |
| `availablePriceGroups` | Filters | Collection | Dynamiczne grupy cenowe z DB |
| `availableWarehouses` | Filters | Collection | Dynamiczne magazyny z DB |
| `syncStatusOptions` | Filters | array | Statusy synchronizacji (6 opcji) |
| `allActiveWarehouses` | Columns | Collection | Cache magazynow dla tooltipow (anti-N+1) |
| `savedPresets` | Presets | Collection | Presety filtrow z DB |

---

## 6. Eager Loading (rozszerzone v3.0)

```php
productType:id,name,slug
shopData → shop:id,name,label_color,label_icon
erpData → erpConnection:id,instance_name,erp_type,label_color,label_icon
prices:id,product_id,price_group_id,price_net,price_gross
prices.priceGroup:id,name,code,is_active,is_default          // ROZSZERZONE: +code,is_default
stock:id,product_id,warehouse_id,quantity,reserved_quantity,available_quantity,minimum_stock,is_active  // ROZSZERZONE
stock.warehouse:id,name,code,is_default                       // ROZSZERZONE: +code
media (where is_active=true)
variants + variants.images/prices/stock/attributes
```

---

## 7. UI - Blade View (v3.0)

### 7.1 Nowa struktura layoutu

```
Sticky Header (z-40)
  +-- Title + [NOWY: Search Bar] + Primary Actions (Dodaj, Import PS, Import ERP)
  +-- [NOWY: View Toggle] + [NOWY: Column Customizer] + [NOWY: Filter Presets] + Filters Toggle
  +-- @if($showFilters) Filters Panel
  |     +-- Kategoria (NOWY: tree dropdown) | Status | Stan | Typ
  |     +-- [NOWY: Grupa cenowa] | Zakres cen | [NOWY: Magazyn] | [NOWY: Zakres stanow]
  |     +-- Typ daty | Data od/do | Status integracji | Status mediow
  |     +-- Status zgodnosci danych (badge filtry)
  +-- Selection Banners + Bulk Actions Bar

Polling divs (wire:poll 5s/3s)
  +-- @if activeJobProgress → JobProgressBar

Main Content
  +-- @if(table) → @include table-view
  |     +-- THEAD: [NOWY: sticky] checkbox, foto, SKU, Nazwa, Typ, Producent,
  |     |          [NOWY: Cena N/B], [NOWY: Stan], Status, Zgodnosc, Data, Akcje
  |     +-- @forelse → @include table-row per product
  |           +-- [NOWY: Cena tooltip z x-teleport (grupy cenowe)]
  |           +-- [NOWY: Stan tooltip z x-teleport (wszystkie magazyny + limity)]
  |           +-- [NOWY: Row coloring - error/warning/syncing]
  +-- @else → @include grid-view

MODALS (20+ @include partials)
```

### 7.2 Tooltip pozycjonowanie

Tooltipy cen i stanow uzywaja `x-teleport="body"` + `position: fixed` z smart flip:
- **Normal** (pod tekstem): `top: span.bottom + 2px`
- **Flipped** (nad tekstem): `bottom: (viewport - span.top + 2)px; top: auto`

Flip aktywuje sie gdy tooltip nie zmiesci sie pod komurka (estymacja 200px cena / 350px stan).

### 7.3 Stock Tooltip - kolumny

| Kolumna | Opis |
|---------|------|
| Magazyn | Nazwa (domyslny oznaczony * pomaranczowym) |
| Stan | quantity |
| Rez. | reserved_quantity |
| Dost. | available_quantity (kolorowany: zielony/zolty/czerwony) |
| Limit | minimum_stock (z ProductStock lub Warehouse.default_minimum_stock) |

Tooltip pokazuje WSZYSTKIE aktywne magazyny (nawet bez rekordow stock - wtedy 0).

### 7.4 Price Tooltip

Pokazuje wszystkie grupy cenowe. Domyslna grupa oznaczona * pomaranczowym (jak magazyn).
Tryb netto/brutto persisted w session.

---

## 8-12. (Sekcje Import PS, ERP, Bulk, CategoryPreview - BEZ ZMIAN)

Patrz v2.0 dokumentacja powyzej.

---

## 13. Nowe Funkcjonalnosci v3.0

### 13.1 Category Tree Dropdown

- Alpine.js component `categoryTreeDropdown()` z `@script` directive
- Osobne query Category (nie zalezy od computed $categories)
- Server-side budowanie drzewa + JSON do Alpine
- Client-side filtering (searchTerm), expand/collapse, 4 poziomy glebokosci
- `$wire.set('categoryFilter', id)` na klikniecie

### 13.2 Column Customizer

- Alpine.js `columnCustomizer()` z localStorage persistence
- 11 kolumn (3 required: SKU, Nazwa, Akcje)
- `CustomEvent 'columns-changed'` broadcastowany do table-view
- Reset do domyslnych

### 13.3 Filter Presets

- Model `UserFilterPreset` z JSON `filters` column
- 12 properties serializowanych (search, category, status, stock, type, price, dates, integration, media)
- Domyslny preset auto-ladowany w mount()
- CRUD via Livewire methods

### 13.4 Row Coloring

CSS klasy bazujace na `ProductStatusDTO`:
- `.product-row--error` - severity CRITICAL (czerwone tlo + lewa krawedz)
- `.product-row--warning` - severity WARNING (zolte tlo + lewa krawedz)
- `.product-row--syncing` - aktywny sync job (niebieskie tlo + lewa krawedz)

### 13.5 Sticky Header

```css
.product-list-table thead { position: sticky; top: 0; z-index: 10; background: #1f2937; }
```

---

## 14. Troubleshooting

### 14.1 Tooltip nie pojawia sie on hover

**Przyczyna:** `backdrop-filter` na rodzicu tworzy nowy containing block, psuiac `position: fixed`.

**Rozwiazanie:** Tooltipy uzywaja `x-teleport="body"` (Alpine 3.x) - przenosza DOM na `<body>`.

### 14.2 Tooltip pojawia sie w zlym miejscu

**Przyczyna:** `$el.getBoundingClientRect()` zwraca pozycje `<td>` (pelna wysokosc wiersza).

**Rozwiazanie:** Uzywamy `$refs.stockVal.getBoundingClientRect()` na `<span>` z wartoscia.

### 14.3 Flipped tooltip ucina sie na gorze

**Przyczyna:** Estymacja wysokosci tooltipa za duza/mala.

**Rozwiazanie:** CSS `bottom` zamiast `top` dla flipped - dolna krawedz tooltipa przylegla do tekstu.

### 14.4 Netto/brutto resetuje sie po odswiezeniu

**Przyczyna:** `priceDisplayMode` nie byl persisted.

**Rozwiazanie:** Dodany do `saveUserPreferences()` / `loadUserPreferences()` (session).

### 14.5 Stock tooltip pokazuje 1 magazyn zamiast wszystkich

**Przyczyna:** `getAllStockForProduct()` filtrowal tylko istniejace rekordy stock.

**Rozwiazanie:** Teraz iteruje WSZYSTKIE aktywne magazyny (`allActiveWarehouses` computed), padding 0 dla brakujacych.

Patrz tez v2.0 troubleshooting (sekcje 13.1-13.5 ponizej).

### 14.6-14.10 (z v2.0)

- Produkty nie wyswietlaja sie po imporcie → sprawdz event `progress-completed` + polling
- Typ "Inne" zamiast poprawnego → sprawdz `shop_category_type_mappings`
- CategoryPreview nie pojawia sie → sprawdz polling 3s + timeout 15min
- Bulk nie dziala → sprawdz queue worker + `failed_jobs`
- ERP nie znajduje → min 3 znaki + aktywne polaczenie

---

## 15. Changelog

### v3.0.0 (2026-02-25) - UX Overhaul

**Refactoring:**
- ProductList.php: 3316 → 443 LOC (8 traitow wyekstrahowanych)
- product-list.blade.php: 2381 → 184 LOC (20+ partiali)
- Laczna redukcja: -5503 linii z monolitow

**Nowe funkcjonalnosci:**
- Search bar w headerze (zawsze widoczny)
- Category tree dropdown z wyszukiwaniem (Alpine.js)
- Kolumna Cena z tooltipem grup cenowych + przelacznik N/B
- Kolumna Stan z tooltipem WSZYSTKICH magazynow + limit (minimum_stock)
- Dynamiczne filtry z DB (typy produktow, statusy integracji)
- Rozszerzone filtry: grupa cenowa + zakres, magazyn + zakres stanow
- Sticky header tabeli
- Row coloring (error/warning/syncing z ProductStatusDTO)
- Filter presets - zapis/odczyt z DB (UserFilterPreset model)
- Column customizer (localStorage)
- Persystencja netto/brutto w session

**Nowe pliki:**
- 8 traitow PHP w `Traits/`
- 20+ partiali blade w `partials/`
- 2 pliki CSS (product-list-search.css, product-list-columns.css)
- Model UserFilterPreset + migracja

**Tooltip system:**
- `x-teleport="body"` (fix: backdrop-filter containing block)
- Smart flip: pod tekstem lub nad tekstem (CSS bottom)
- Pozycjonowanie na `<span>` (nie `<td>`)

### v2.0.0 (2026-02-23)

- Kompletny audyt z danymi faktycznymi
- Integracja z CategoryPreviewModal
- CategoryTypeMapper cascade

### v1.0.0 (2026-02-13)

- Inicjalna wersja dokumentacji

---

## Appendix A: Kluczowe pliki

| Typ | Sciezka |
|-----|---------|
| Main Component | `app/Http/Livewire/Products/Listing/ProductList.php` |
| Filters Trait | `app/Http/Livewire/Products/Listing/Traits/ProductListFilters.php` |
| Columns Trait | `app/Http/Livewire/Products/Listing/Traits/ProductListColumns.php` |
| BulkActions Trait | `app/Http/Livewire/Products/Listing/Traits/ProductListBulkActions.php` |
| Presets Trait | `app/Http/Livewire/Products/Listing/Traits/ProductListPresets.php` |
| ERP Trait | `app/Http/Livewire/Products/Listing/Traits/ProductListERPImport.php` |
| Main Blade | `resources/views/livewire/products/listing/product-list.blade.php` |
| Table Row | `resources/views/livewire/products/listing/partials/table-row.blade.php` |
| Header Bar | `resources/views/livewire/products/listing/partials/header-bar.blade.php` |
| Filters Panel | `resources/views/livewire/products/listing/partials/filters-panel.blade.php` |
| Category Tree | `resources/views/livewire/products/listing/partials/category-tree-dropdown.blade.php` |
| Column CSS | `resources/css/products/product-list-columns.css` |
| Search CSS | `resources/css/products/product-list-search.css` |
| Preset Model | `app/Models/UserFilterPreset.php` |

## Appendix B: Related Jobs (bez zmian)

| Job | Queue | Cel |
|-----|-------|-----|
| `BulkImportProducts` | default | Import produktow z PS |
| `AnalyzeMissingCategories` | default | Analiza brakujacych kategorii |
| `BulkCreateCategories` | default | Tworzenie zatwierdzonych kategorii |
| `ExpirePendingCategoryPreview` | default | Timeout 15 min |
| `BaselinkerSyncJob` | default | Import z ERP BaseLinker |
| `BulkAssignCategories` | default | Bulk assign kategorii (>50) |
| `BulkRemoveCategories` | default | Bulk remove kategorii (>50) |
| `BulkMoveCategories` | default | Bulk move kategorii (>50) |
