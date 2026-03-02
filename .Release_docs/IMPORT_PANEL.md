# PPM - Import Panel Documentation

> **Wersja:** 1.1.0
> **Data:** 2026-02-23
> **Status:** Production Ready
> **Changelog:** Dodano sekcje CategoryTypeMapper cascade, aktualizacja statystyk, referencja do CATEGORY_PREVIEW_MODAL.md i PRODUCT_LIST.md

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Metody Importu](#4-metody-importu)
5. [System Pending Products](#5-system-pending-products)
6. [Modalne Okna](#6-modalne-okna)
7. [Pipeline Publikacji](#7-pipeline-publikacji)
8. [Jobs & Workers](#8-jobs--workers)
9. [Komponenty UI](#9-komponenty-ui)
10. [Uprawnienia](#10-uprawnienia)
11. [Routing](#11-routing)
12. [Troubleshooting](#12-troubleshooting)
13. [Changelog](#13-changelog)

---

## 1. Overview

### 1.1 Opis modulu

Import Panel to **centralny modul pre-publikacyjnej edycji produktow** w systemie PPM. Umozliwia import produktow z roznych zrodel (SKU, CSV, PrestaShop), edycje w stanie draft oraz publikacje do glownej tabeli produktow z synchronizacja do zewnetrznych systemow (PrestaShop, ERP).

**URL Panelu:** `/admin/products/import`

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Komponenty Livewire | 24 |
| Traits | 13 |
| Services | 10 |
| Jobs | 3 |
| Blade Views | 30+ |
| Modalne Okna | 10 |
| Metody Importu | 3 (Column, CSV, PrestaShop Pull) |

### 1.3 Kluczowe funkcjonalnosci

- **Multi-Source Import** - SKU list, CSV upload, PrestaShop pull
- **Draft System** - Edycja przed publikacja z completion tracking
- **Inline Editing** - Edycja bezposrednio w tabeli (SKU, name, type, manufacturer)
- **10 Modalnych Okien** - Images, Variants, Descriptions, Prices, Features, Compatibility, Categories
- **Bulk Operations** - Zaznaczanie wierszy, bulk delete/type/shops/category/publish
- **Publication Pipeline** - 10-step atomic transaction z ProductPublicationService
- **Multi-Target Sync** - Publikacja do PrestaShop (multiple shops) + ERP (multiple connections)
- **Completion Tracking** - Progress bar per produkt (0-100%)
- **Frozen State** - Opublikowane produkty w readonly mode

### 1.4 Workflow

```
┌──────────────────────────────────────────────────────────────┐
│                     IMPORT WORKFLOW                          │
└──────────────────────────────────────────────────────────────┘

   ┌──────────────┐
   │ 1. IMPORT    │ → SKU List / CSV Upload / PrestaShop Pull
   └──────┬───────┘
          │
          ▼
   ┌──────────────┐
   │ 2. DRAFT     │ → pending_products table (JSON columns)
   └──────┬───────┘
          │
          ▼
   ┌──────────────┐
   │ 3. EDIT      │ → Inline editing + 10 modali
   └──────┬───────┘   (images, variants, descriptions, etc.)
          │
          ▼
   ┌──────────────┐
   │ 4. VALIDATE  │ → completion_percentage = 100%
   └──────┬───────┘   (required fields check)
          │
          ▼
   ┌──────────────┐
   │ 5. PUBLISH   │ → ProductPublicationService
   └──────┬───────┘   (10-step DB::transaction)
          │
          ├─────────────────────────┬──────────────────────┐
          │                         │                      │
          ▼                         ▼                      ▼
   ┌──────────────┐        ┌──────────────┐     ┌──────────────┐
   │  products    │        │ PrestaShop   │     │   ERP Sync   │
   │  (table)     │        │   Sync Jobs  │     │     Jobs     │
   └──────────────┘        └──────────────┘     └──────────────┘
```

---

## 2. Architektura Plikow

### 2.1 Glowny Komponent

```
app/Http/Livewire/Products/Import/ProductImportPanel.php (403 linii)
```

**Traits wykorzystywane:**
- `WithPagination` - Laravel Livewire pagination
- `ImportPanelFilters` - Filtry (search, status, type, manufacturer)
- `ImportPanelActions` - Inline edit, delete, duplicate, publish single
- `ImportPanelBulkOperations` - Bulk selection, bulk delete/type/shops/category/publish
- `ImportPanelCategoryShopTrait` - Category columns L3-L8, shop badges
- `ImportPanelPermissionTrait` - Permission checks
- `ImportPanelPublicationTrait` - Publication targets, schedule, publish states

### 2.2 Modalne Okna

| Modal | Plik | Linie | Przeznaczenie |
|-------|------|-------|---------------|
| **ProductImportModal** | `Modals/ProductImportModal.php` | ~600 | Unified import modal (Column + CSV modes) |
| **ImageUploadModal** | `Modals/ImageUploadModal.php` | ~500 | Upload images, copy from SKU, URL import, per-variant assignment |
| **VariantModal** | `Modals/VariantModal.php` | ~450 | Attribute selection, combination generation, SKU suffix/prefix |
| **DescriptionModal** | `Modals/DescriptionModal.php` | ~400 | Default description + per-shop descriptions (tabs) |
| **ImportPricesModal** | `Modals/ImportPricesModal.php` | ~350 | Price groups grid (net/gross, 6+ groups) |
| **FeatureTemplateModal** | `Modals/FeatureTemplateModal.php` | ~300 | Product features (cechy) template |
| **CompatibilityModal** | `Modals/CompatibilityModal.php` | ~300 | Vehicle compatibility (dopasowania) |
| **PrestaShopCategoryPickerModal** | `Modals/PrestaShopCategoryPickerModal.php` | ~250 | Category picker per shop (hierarchical tree) |
| **UnpublishConfirmModal** | `Modals/UnpublishConfirmModal.php` | ~100 | Confirmation for unpublish operation |

**Modal Traits:**

| Trait | Plik | Przeznaczenie |
|-------|------|---------------|
| `ImportModalColumnModeTrait` | `Modals/Traits/` | Column mode (spreadsheet interface) |
| `ImportModalCsvModeTrait` | `Modals/Traits/` | CSV parsing/mapping |
| `ImportModalSwitchesTrait` | `Modals/Traits/` | shop_internet, split_payment, variant_product toggles |
| `HandlesImageUpload` | `Traits/` | Upload/copy/URL logic for images |
| `HandlesVariantImages` | `Traits/` | Per-variant image assignment |

### 2.3 Services

| Service | Plik | Linie | Przeznaczenie |
|---------|------|-------|---------------|
| **ProductPublicationService** | `Services/Import/ProductPublicationService.php` | 1038 | Draft → Product conversion (10-step transaction) |
| **PublicationTargetService** | `Services/Import/PublicationTargetService.php` | 372 | ERP + PrestaShop routing logic |
| **ProductUnpublishService** | `Services/Import/ProductUnpublishService.php` | 264 | Full rollback with external cleanup |
| **BatchImportProcessor** | `Services/Import/BatchImportProcessor.php` | ~300 | Batch processing utilities |
| **ColumnMappingService** | `Services/Import/ColumnMappingService.php` | ~250 | CSV → PendingProduct field mapping |
| **CsvParserService** | `Services/Import/CsvParserService.php` | ~200 | CSV parsing (auto-detect separator) |
| **ExcelParserService** | `Services/Import/ExcelParserService.php` | ~200 | XLSX/XLS parsing via Laravel-Excel |
| **SkuParserService** | `Services/Import/SkuParserService.php` | ~400 (19 methods) | SKU normalization/validation/extraction |
| **SkuValidatorService** | `Services/Import/SkuValidatorService.php` | ~150 | Uniqueness checks, format validation |
| **XlsxTemplateGenerator** | `Services/Import/XlsxTemplateGenerator.php` | ~180 | Template generation for column import |

### 2.4 Jobs

| Job | Plik | Linie | Przeznaczenie |
|-----|------|-------|---------------|
| **BulkImportProducts** | `Jobs/PrestaShop/BulkImportProducts.php` | 1064 | PrestaShop → PPM import (3 modes) |
| **ImportFeaturesFromPSJob** | `Jobs/PrestaShop/ImportFeaturesFromPSJob.php` | ~300 | Features import from PS |
| **BulkImportDescriptionsJob** | `Jobs/PrestaShop/BulkImportDescriptionsJob.php` | ~250 | Descriptions import from PS |

### 2.5 Blade Templates

```
resources/views/livewire/products/import/
├── product-import-panel.blade.php          # Main table view (~800 linii)
├── modals/
│   ├── product-import-modal.blade.php     # Unified import (Column + CSV)
│   ├── image-upload-modal.blade.php       # Image management
│   ├── variant-modal.blade.php            # Variant generation
│   ├── description-modal.blade.php        # Descriptions editor
│   ├── import-prices-modal.blade.php      # Price grid
│   ├── feature-template-modal.blade.php   # Features/cechy
│   ├── compatibility-modal.blade.php      # Vehicle compatibility
│   ├── category-picker-modal.blade.php    # Category tree per shop
│   └── unpublish-confirm-modal.blade.php  # Unpublish confirmation
├── partials/
│   ├── _image-upload-sources.blade.php    # Upload tabs (Upload/URL/Copy/Variant)
│   ├── _variant-preview.blade.php         # Variant preview cards
│   ├── image-card.blade.php               # Single image card with actions
│   ├── image-gallery-grouped.blade.php    # Gallery grouped by variant
│   └── product-row.blade.php              # Table row with inline editing
```

---

## 3. Schema Bazy Danych

### 3.1 pending_products (Draft State)

Glowna tabela draft productow przed publikacja. **Wszystkie dane edytowalne sa w JSON columns** dla elastycznosci.

| Kolumna | Typ | Index | Opis |
|---------|-----|-------|------|
| `id` | BIGINT UNSIGNED | PRIMARY | Auto-increment ID |
| `sku` | VARCHAR(100) | UNIQUE | SKU produktu (unikalne) |
| `name` | VARCHAR(500) | | Nazwa produktu |
| `slug` | VARCHAR(500) | NULLABLE | URL slug |
| `product_type_id` | BIGINT UNSIGNED | FK | FK → product_types |
| `manufacturer` | VARCHAR(200) | NULLABLE | Producent (legacy string) |
| `supplier_code` | VARCHAR(100) | NULLABLE | Kod dostawcy |
| `ean` | VARCHAR(13) | NULLABLE | EAN barcode |
| **JSON Columns:** |
| `category_ids` | JSON | NULLABLE | `[5, 12, 34]` - IDs kategorii PPM |
| `temp_media_paths` | JSON | NULLABLE | `[{path, variant_sku, sort_order}, ...]` + `variant_covers: {"-RED": "path"}` |
| `shop_ids` | JSON | NULLABLE | `[1, 2, 3]` - IDs sklepow PrestaShop |
| `shop_categories` | JSON | NULLABLE | `{1: [101, 202], 2: [301]}` - per-shop categories |
| `shop_descriptions` | JSON | NULLABLE | `{1: {short: "...", long: "..."}, ...}` - per-shop descriptions |
| `variant_data` | JSON | NULLABLE | `{attributes: [], combinations: [{sku, attributes, prices, stock}, ...]}` |
| `compatibility_data` | JSON | NULLABLE | `[{brand, model, year}, ...]` |
| `feature_data` | JSON | NULLABLE | `{feature_id: value, ...}` |
| `price_data` | JSON | NULLABLE | `{price_group_id: {net, gross, margin}, ...}` |
| `publication_targets` | JSON | NULLABLE | `{erp_connections: [1,2], prestashop_shops: [1,2,3]}` |
| **Completion Tracking:** |
| `completion_status` | JSON | NULLABLE | `{required: {field: bool}, optional: {field: bool}}` |
| `completion_percentage` | DECIMAL(5,2) | INDEX | 0.00 - 100.00% (editable progress) |
| `is_ready_for_publish` | BOOLEAN | INDEX | Auto-computed from completion_percentage |
| **Publication State:** |
| `publish_status` | ENUM | INDEX | `draft`, `scheduled`, `publishing`, `published`, `failed` |
| `published_at` | TIMESTAMP | NULLABLE | Timestamp publikacji |
| `published_as_product_id` | BIGINT UNSIGNED | NULLABLE INDEX | FK → products (po publikacji) |
| `published_by` | BIGINT UNSIGNED | NULLABLE | User ID publikujacego |
| **Import Session:** |
| `import_session_id` | BIGINT UNSIGNED | NULLABLE FK | FK → import_sessions |
| `imported_by` | BIGINT UNSIGNED | NULLABLE | User ID importujacego |
| `imported_at` | TIMESTAMP | NULLABLE | Timestamp importu |
| **Skip Flags (with audit trail):** |
| `skip_features` | BOOLEAN | DEFAULT FALSE | Pomijaj features przy publikacji |
| `skip_compatibility` | BOOLEAN | DEFAULT FALSE | Pomijaj compatibility przy publikacji |
| `skip_images` | BOOLEAN | DEFAULT FALSE | Pomijaj images przy publikacji |
| `skip_descriptions` | BOOLEAN | DEFAULT FALSE | Pomijaj descriptions przy publikacji |
| `skip_history` | JSON | NULLABLE | Audit trail skip operations `[{field, changed_at, changed_by}, ...]` |
| **Boolean Flags:** |
| `shop_internet` | BOOLEAN | DEFAULT FALSE | Sklep internetowy (Subiekt GT field) |
| `split_payment` | BOOLEAN | DEFAULT FALSE | Podzielona platnosc (Subiekt GT field) |
| `variant_product` | BOOLEAN | DEFAULT FALSE | Czy produkt ma warianty |
| **Prices:** |
| `base_price` | DECIMAL(10,2) | NULLABLE | Cena bazowa (fallback) |
| `purchase_price` | DECIMAL(10,2) | NULLABLE | Cena zakupu |
| **Timestamps:** |
| `created_at` | TIMESTAMP | | Laravel created_at |
| `updated_at` | TIMESTAMP | | Laravel updated_at |

**Indeksy:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`sku`)
- INDEX (`completion_percentage`)
- INDEX (`is_ready_for_publish`)
- INDEX (`publish_status`)
- INDEX (`published_as_product_id`)
- INDEX (`import_session_id`)
- INDEX (`product_type_id`)

### 3.2 import_sessions

Trackuje sesje importu (batch operations).

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | PRIMARY |
| `uuid` | VARCHAR(36) | UNIQUE UUID |
| `session_name` | VARCHAR(255) | Nazwa sesji (user-defined) |
| `import_method` | ENUM | `paste_sku`, `paste_sku_name`, `csv`, `excel`, `erp`, `modal_import` |
| `import_source_file` | VARCHAR(500) | NULLABLE - sciezka pliku zrodlowego |
| `parsed_data` | JSON | NULLABLE - raw parsed data |
| `total_rows` | INT | Total liczba wierszy |
| `products_created` | INT | Utworzone pending products |
| `products_published` | INT | Opublikowane products |
| `products_failed` | INT | Bledy |
| `products_skipped` | INT | Pominiete (duplicates) |
| `status` | ENUM | `parsing`, `ready`, `publishing`, `completed`, `failed`, `cancelled` |
| `error_log` | JSON | NULLABLE - array of errors |
| `imported_by` | BIGINT UNSIGNED | User ID |
| `started_at` | TIMESTAMP | NULLABLE |
| `completed_at` | TIMESTAMP | NULLABLE |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indeksy:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`uuid`)
- INDEX (`status`)
- INDEX (`import_method`)
- INDEX (`imported_by`)

### 3.3 publish_history (Audit Trail)

Audit trail publikacji pending_products → products.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | PRIMARY |
| `pending_product_id` | BIGINT UNSIGNED | FK → pending_products |
| `product_id` | BIGINT UNSIGNED | FK → products |
| `published_by` | BIGINT UNSIGNED | User ID |
| `published_at` | TIMESTAMP | Timestamp publikacji |
| **Snapshots:** |
| `sku_snapshot` | VARCHAR(100) | SKU at publication time |
| `name_snapshot` | VARCHAR(500) | Name at publication time |
| `published_shops` | JSON | `[1, 2, 3]` - IDs sklepow PS |
| `published_categories` | JSON | `[5, 12, 34]` - IDs kategorii |
| **Sync Tracking:** |
| `sync_jobs_dispatched` | JSON | `{ps: [job_id, ...], erp: [job_id, ...]}` |
| `sync_status` | ENUM | `pending`, `in_progress`, `completed`, `partial`, `failed` |
| `sync_completed_at` | TIMESTAMP | NULLABLE |
| `sync_errors` | JSON | NULLABLE - array of errors |
| **Metadata:** |
| `publish_mode` | ENUM | `single`, `bulk` |
| `batch_id` | VARCHAR(36) | NULLABLE - UUID for bulk publish |
| `processing_time_ms` | INT | NULLABLE - czas przetwarzania |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indeksy:**
- PRIMARY KEY (`id`)
- INDEX (`pending_product_id`)
- INDEX (`product_id`)
- INDEX (`published_by`)
- INDEX (`sync_status`)
- INDEX (`batch_id`)

### 3.4 products (Published State)

Glowna tabela produktow po publikacji (schema juz dokumentowana w PRODUCT_FORM.md).

**Kluczowe kolumny dla Import Panel:**
- `id` - PRIMARY KEY (referenced by `pending_products.published_as_product_id`)
- `sku` - UNIQUE (inherited from `pending_products.sku`)
- `name`, `slug`, `product_type`, `manufacturer`, `ean` - Basic info
- `is_active` - Published products domyslnie `true`
- `is_variant_master` - Inherited from `pending_products.variant_product`
- `short_description`, `long_description` - Inherited from `pending_products.shop_descriptions` (default)
- `created_at`, `updated_at` - Laravel timestamps
- `deleted_at` - Soft deletes

### 3.5 Related Tables (Created During Publication)

**ProductPublicationService tworzy rekordy w:**
- `product_prices` - per price_group (from `pending_products.price_data`)
- `product_variants` - per combination (from `pending_products.variant_data`)
- `media` - per image (from `pending_products.temp_media_paths`)
- `variant_images` - per-variant image assignments (including covers)
- `product_shop_data` - per shop (from `pending_products.shop_descriptions`)
- `shop_variants` - PrestaShop sync tracking per variant+shop
- `category_product` (pivot) - per category (from `pending_products.category_ids`)

### 3.6 Schema Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                       DATABASE SCHEMA                           │
└─────────────────────────────────────────────────────────────────┘

pending_products ──publish──> products
       |                        |
       v                        ├──> product_prices (from price_data)
  import_sessions               ├──> product_shop_data (from shop_descriptions)
  publish_history               ├──> product_variants (from variant_data)
                                │      ├──> variant_images (from temp_media_paths.variant_covers)
                                │      └──> shop_variants (PS sync tracking)
                                ├──> media (from temp_media_paths)
                                ├──> product_erp_data (from publication_targets.erp_connections)
                                └──> category_product (from category_ids)

JSON Column Relationships:
  pending_products.temp_media_paths → media table
  pending_products.variant_data → product_variants table
  pending_products.price_data → product_prices table
  pending_products.shop_descriptions → product_shop_data table
  pending_products.publication_targets → dispatch sync jobs
```

---

## 4. Metody Importu

### 4.1 Column Mode (Spreadsheet Interface)

**Plik:** `ProductImportModal.php` + `ImportModalColumnModeTrait.php`

**Opis:** Interface przypominajacy arkusz kalkulacyjny. Kolumny sa FIXE (SKU, Name) + opcjonalne (Type, Supplier, Manufacturer, EAN, etc.).

**Workflow:**
1. User otwiera modal `/admin/products/import` → "Nowy Import"
2. Wybiera "Tryb Kolumnowy"
3. Wkleja dane z clipboardu (Ctrl+V) lub wpisuje recznie
4. System parsuje wklejone dane (SKU + opcjonalnie Name w dwoch kolumnach)
5. Walidacja: SKU unique check, format validation
6. Klik "Zapisz" → utworzenie pending_products
7. Modal zamyka sie → refresh panelu

**Properties:**
```php
public string $importMode = 'column';  // 'column' vs 'csv'
public array $rows = [];               // [{sku, name, type_id, manufacturer, ...}, ...]
public bool $editMode = false;         // Czy edytujemy istniejacy produkt
public ?int $editingProductId = null;
```

**Metody (ImportModalColumnModeTrait):**
- `addRow()` - Dodaj pusty wiersz
- `removeRow(int $index)` - Usun wiersz
- `saveColumnImport()` - Zapisz wszystkie wiersze jako pending_products
- `handlePasteData(string $pastedText)` - Parse Ctrl+V clipboard
- `validateColumnData()` - Walidacja przed zapisem

**Przyklady:**
```
SKU         | Name              | Type          | Manufacturer
------------|-------------------|---------------|-------------
ABC-123     | Product One       | Czesc Zamienna| Honda
XYZ-456     | Product Two       | Composite     | Yamaha
DEF-789     | Product Three     | Simple        | Kawasaki
```

### 4.2 CSV Mode (File Upload/Paste)

**Plik:** `ProductImportModal.php` + `ImportModalCsvModeTrait.php`

**Opis:** Import z pliku CSV lub paste CSV text. Auto-detect separatora (`,` `;` `\t`). Kolumny mapowane do pol pending_products.

**Workflow:**
1. User wybiera "Tryb CSV"
2. Upload pliku lub paste CSV text
3. `CsvParserService::parse()` → auto-detect separator → parsed rows
4. Modal pokazuje preview (5 pierwszych wierszy)
5. Column mapping: user mapuje kolumny CSV → pending_products fields
   - `Kolumna 1` → `sku`
   - `Kolumna 2` → `name`
   - `Kolumna 3` → `manufacturer`
   - etc.
6. Klik "Importuj" → `ColumnMappingService::mapRowsToPendingProducts()`
7. Batch insert do `pending_products`

**Properties:**
```php
public ?string $csvContent = null;     // Raw CSV text (paste)
public ?string $csvFilePath = null;    // Uploaded file path
public array $csvHeaders = [];         // Header row
public array $csvPreviewRows = [];     // First 5 rows
public array $columnMapping = [];      // ['csv_column_index' => 'pending_product_field']
public string $csvSeparator = ',';     // Auto-detected
```

**Metody (ImportModalCsvModeTrait):**
- `handleCsvUpload()` - Handle file upload
- `handleCsvPaste()` - Handle paste text
- `parseCsvPreview()` - Parse & show preview
- `saveCsvMapping()` - Save column mappings
- `importCsvData()` - Batch import to pending_products

**Services:**
- `CsvParserService::parse($content)` - Parse CSV, auto-detect separator
- `ColumnMappingService::mapRowsToPendingProducts($rows, $mapping)` - Transform CSV rows → PendingProduct arrays

**Supported Formats:**
- CSV (comma-separated)
- TSV (tab-separated)
- SSV (semicolon-separated)
- Auto-detect based on first 3 rows analysis

### 4.3 PrestaShop Pull (Bulk Import Job)

**Plik:** `BulkImportProducts.php` (Job)

**Opis:** Pobiera produkty z PrestaShop API i tworzy pending_products. Trzy tryby importu: all, category, individual.

**Workflow:**
1. User klika "Importuj z PrestaShop" w panelu
2. Modal wyboru trybu:
   - **All Products** - wszystkie produkty ze sklepu
   - **By Category** - produkty z wybranej kategorii PS
   - **Individual Products** - lista konkretnych product IDs
3. Dispatch `BulkImportProducts::dispatch($shopId, $mode, $params)`
4. Job w tle:
   - Fetch products z PrestaShop API (`/api/products?display=full`)
   - Parse XML response
   - Extract: name, description, price, stock, categories, images
   - Create pending_products per produkt
   - Track progress w `ImportSession`
5. Po zakonczeniu: notyfikacja + refresh panelu

**Tryby importu:**

| Mode | Opis | Parametry |
|------|------|-----------|
| `all` | Wszystkie produkty | `shopId` |
| `category` | Produkty z kategorii | `shopId`, `categoryId` |
| `individual` | Konkretne produkty | `shopId`, `productIds[]` |

**Category Analysis (Mode: category):**
Przed importem, job wykonuje analize kategorii:
1. Fetch category tree z PS
2. Zliczanie produktow per kategoria
3. Wykrywanie sub-kategorii
4. User wybiera kategorie z drzewa → import

**Media Sync:**
- Obrazy pobierane z PrestaShop API (`/api/images/products/{id}`)
- Download do `storage/temp/import_media/{session_uuid}/`
- Zapisane w `pending_products.temp_media_paths` jako JSON
- Po publikacji: przeniesienie do `storage/app/public/products/{product_id}/`

**Job Properties:**
```php
public int $shopId;
public string $mode;              // 'all', 'category', 'individual'
public array $params;             // mode-specific params
public ?string $sessionUuid;      // ImportSession UUID
public int $timeout = 3600;       // 1h timeout
public int $tries = 3;
```

**Job Methods:**
- `handle()` - Main execution
- `fetchProducts()` - Fetch z PS API
- `parseProductXml()` - XML → array
- `createPendingProduct()` - array → PendingProduct model
- `downloadImages()` - Download images z PS
- `trackProgress()` - Update ImportSession

---

## 5. System Pending Products

### 5.1 Completion Tracking (Progress Bar)

Kazdy pending_product ma `completion_percentage` (0-100%) obliczany na podstawie **required** i **optional** fields.

**Required Fields (80% wage):**
- `sku` - SKU (MUST)
- `name` - Nazwa (MUST)
- `manufacturer_id` - Producent (MUST)
- `category_ids` - Kategorie (MUST - min 1)
- `product_type_id` - Typ produktu (MUST)
- `publication_targets` - Targets publikacji (MUST - min 1 shop lub ERP)

**Optional Fields (20% wage):**
- `temp_media_paths` - Obrazy (nice-to-have)
- `shop_descriptions` - Opisy per-shop (nice-to-have)
- `base_price` - Cena bazowa (nice-to-have)
- `variant_data` - Warianty (nice-to-have dla variant_product=true)
- `compatibility_data` - Dopasowania (nice-to-have dla type=czesc-zamienna)
- `feature_data` - Cechy (nice-to-have)

**Formula:**
```php
$requiredWeight = 0.80;
$optionalWeight = 0.20;

$requiredCompletion = (count($completedRequired) / count($allRequired)) * 100;
$optionalCompletion = (count($completedOptional) / count($allOptional)) * 100;

$completion_percentage = ($requiredCompletion * $requiredWeight) + ($optionalCompletion * $optionalWeight);
```

**UI Indicators:**
```
[ ████████░░ ] 80% - Gotowe do publikacji (all required fields filled)
[ ██████░░░░ ] 60% - Brakuje manufacturer + categories
[ ███░░░░░░░ ] 30% - Tylko SKU + Name
[ ░░░░░░░░░░ ]  0% - Puste (error state)
```

**Computed Property:**
```php
public function getIsReadyForPublishAttribute(): bool
{
    return $this->completion_percentage === 100.00;
}
```

### 5.2 Skip Flags (Audit Trail)

User moze oznaczyc sekcje jako "pominiete" przy publikacji. Kazda zmiana skip flag jest trackowana w `skip_history` JSON.

**Skip Flags:**
- `skip_features` - Pomijaj cechy/features
- `skip_compatibility` - Pomijaj dopasowania/compatibility
- `skip_images` - Pomijaj obrazy
- `skip_descriptions` - Pomijaj opisy

**Skip History Format:**
```json
{
  "skip_history": [
    {
      "field": "features",
      "skipped": true,
      "changed_at": "2026-02-13T10:30:00Z",
      "changed_by": 8,
      "reason": "Brak danych o cechach produktu"
    },
    {
      "field": "images",
      "skipped": false,
      "changed_at": "2026-02-13T11:00:00Z",
      "changed_by": 8,
      "reason": "Dodano obrazy"
    }
  ]
}
```

**Efekt Skip Flags:**
- W `ProductPublicationService`, kroki publikacji sa warunkowo pomijane:
  - `skip_features` → `createFeatures()` SKIP
  - `skip_compatibility` → `assignCompatibility()` SKIP
  - `skip_images` → `handleMedia()` SKIP
  - `skip_descriptions` → `createShopData()` z pustymi opisami

**UI:**
- Checkbox per sekcja w modalu
- Warning badge w tabeli: "⚠️ Pominiete: Features, Images"
- Completion percentage uwzglednia skip flags (skip = completed)

### 5.3 JSON Column Schemas

#### category_ids (Array of Ints)
```json
[5, 12, 34, 56]
```

#### temp_media_paths (Array of Objects + variant_covers)
```json
{
  "images": [
    {
      "path": "storage/temp/import_media/abc-123/image1.jpg",
      "variant_sku": null,
      "sort_order": 1,
      "is_primary": true
    },
    {
      "path": "storage/temp/import_media/abc-123/image2.jpg",
      "variant_sku": "-RED",
      "sort_order": 2,
      "is_primary": false
    }
  ],
  "variant_covers": {
    "-RED": "storage/temp/import_media/abc-123/image2.jpg",
    "-BLUE": "storage/temp/import_media/abc-123/image3.jpg"
  }
}
```

**Wyjasnienie:**
- `images[]` - Lista wszystkich obrazow
- `variant_sku` - Przypisanie do wariantu (null = shared image)
- `variant_covers` - Mapa wariant → okładka (per-variant cover)

#### variant_data (Attributes + Combinations)
```json
{
  "attributes": [
    {
      "name": "Kolor",
      "values": ["Czerwony", "Niebieski", "Zielony"]
    },
    {
      "name": "Rozmiar",
      "values": ["S", "M", "L"]
    }
  ],
  "combinations": [
    {
      "sku": "ABC-123-RED-S",
      "attributes": {"Kolor": "Czerwony", "Rozmiar": "S"},
      "prices": {"1": {"net": 100, "gross": 123}},
      "stock": {"1": {"quantity": 10, "reserved": 0}}
    },
    {
      "sku": "ABC-123-RED-M",
      "attributes": {"Kolor": "Czerwony", "Rozmiar": "M"},
      "prices": {"1": {"net": 110, "gross": 135.3}},
      "stock": {"1": {"quantity": 5, "reserved": 2}}
    }
  ]
}
```

#### price_data (Per Price Group)
```json
{
  "1": {"net": 100.00, "gross": 123.00, "margin": 20.0},
  "2": {"net": 90.00, "gross": 110.70, "margin": 18.0},
  "3": {"net": 85.00, "gross": 104.55, "margin": 15.0}
}
```

#### publication_targets (ERP + PrestaShop)
```json
{
  "erp_connections": [1, 2],
  "prestashop_shops": [1, 2, 3]
}
```

#### shop_descriptions (Per-Shop Descriptions)
```json
{
  "1": {
    "short_description": "Krotki opis dla sklepu 1",
    "long_description": "<p>Dlugi opis HTML dla sklepu 1</p>"
  },
  "2": {
    "short_description": "Krotki opis dla sklepu 2",
    "long_description": "<p>Dlugi opis HTML dla sklepu 2</p>"
  }
}
```

#### shop_categories (Per-Shop Categories)
```json
{
  "1": [101, 102, 103],
  "2": [201, 202],
  "3": [301]
}
```

**Wyjasnienie:** Shop ID → Array of PrestaShop category IDs

### 5.4 Frozen State (Published Products)

Po publikacji, wiersz w tabeli jest **FROZEN** (readonly). User widzi dane ale NIE moze edytowac.

**CSS Class:** `.import-row-frozen`
```css
.import-row-frozen {
  opacity: 0.7;
  pointer-events: none;
  background-color: rgba(59, 130, 246, 0.05);
}
```

**UI Indicators:**
- Opacity 0.7 (wyszarzony)
- Pointer-events: none (brak hover/click)
- Badge "Opublikowano" (zielony)
- Tylko dostepne akcje: **Publikuj** (re-publish) lub **Cofnij publikacje** (unpublish)

**Unpublish Workflow:**
1. User klika "Cofnij publikacje"
2. Modal potwierdzenia: "Czy na pewno cofnac publikacje? Produkt zostanie usuniety z PrestaShop i ERP."
3. Klik "Tak" → dispatch `ProductUnpublishService`
4. Service:
   - Delete z PrestaShop API (per shop)
   - ERP cleanup (jeśli dostepne)
   - Delete lokalny `products` record (soft delete)
   - Restore `pending_products` status → `draft`
5. Wiersz w tabeli wraca do editable state

---

## 6. Modalne Okna

### 6.1 ProductImportModal (Unified Import)

**Plik:** `Modals/ProductImportModal.php`

**Modes:** Column Mode + CSV Mode (toggle w modalnym headerze)

**Column Mode Fields:**
- SKU (required, unique check)
- Name (required)
- Type (dropdown: product_types)
- Supplier Code
- Manufacturer (text input)
- EAN (8-13 digits)
- shop_internet (checkbox)
- split_payment (checkbox)
- variant_product (checkbox)

**CSV Mode Flow:**
1. Upload file lub paste text
2. Auto-detect separator
3. Preview 5 pierwszych wierszy
4. Column mapping: CSV column → pending_product field
5. Import batch

**Key Bindings:**
```php
wire:model="rows.0.sku"
wire:model="rows.0.name"
wire:model="importMode"    // 'column' | 'csv'
wire:model="csvContent"
```

### 6.2 ImageUploadModal

**Plik:** `Modals/ImageUploadModal.php` + `HandlesImageUpload` trait

**Tabs:**
1. **Upload** - Drag & drop lub file picker (multiple files)
2. **URL Import** - Wklej URL obrazu z internetu
3. **Copy from SKU** - Skopiuj obrazy z innego produktu po SKU
4. **Variant Assignment** - Przypisz obrazy do wariantow

**Upload Tab:**
- Max 10 plikow jednoczesnie
- Allowed formats: jpg, jpeg, png, webp
- Max size: 5MB per file
- Auto-generate thumbnails
- Sort via drag & drop (Alpine.js Sortable)

**URL Import:**
- Input URL
- Validate (HTTP HEAD request)
- Download to temp storage
- Add do `temp_media_paths`

**Copy from SKU:**
- Input SKU zrodlowego produktu
- Fetch images z `products.media` OR `pending_products.temp_media_paths`
- Copy files do nowego temp directory
- Add do `temp_media_paths`

**Variant Assignment Tab:**
- Grouped gallery per variant SKU
- Drag image from "Shared" → "Variant-RED"
- Update `temp_media_paths[].variant_sku`
- Per-variant cover selection (star icon)
- Update `variant_covers` map

**Key Methods:**
```php
handleUpload(array $files)
handleUrlImport(string $url)
copyImagesFromSku(string $sourceSku)
assignImageToVariant(int $imageIndex, ?string $variantSku)
setVariantCover(string $variantSku, int $imageIndex)
```

### 6.3 VariantModal

**Plik:** `Modals/VariantModal.php`

**Purpose:** Generate variant combinations from attributes.

**Workflow:**
1. Define attributes (e.g., Kolor, Rozmiar)
2. Add values per attribute (e.g., Kolor: Czerwony, Niebieski)
3. Click "Generuj kombinacje" → Cartesian product
4. Preview combinations table
5. Set SKU prefix/suffix per combination
6. Save → update `pending_products.variant_data`

**Attribute Definition:**
```php
[
  ['name' => 'Kolor', 'values' => ['Czerwony', 'Niebieski', 'Zielony']],
  ['name' => 'Rozmiar', 'values' => ['S', 'M', 'L', 'XL']],
]
```

**Generated Combinations:**
```
Czerwony + S  → ABC-123-RED-S
Czerwony + M  → ABC-123-RED-M
Niebieski + S → ABC-123-BLUE-S
...
```

**Key Features:**
- Cartesian product algorithm
- SKU auto-generation (prefix + suffix)
- Price/stock placeholders (editable later in table)
- Validation: unique SKUs, no conflicts

**Key Methods:**
```php
addAttribute()
removeAttribute(int $index)
addAttributeValue(int $attrIndex, string $value)
generateCombinations()
saveCombinations()
```

### 6.4 DescriptionModal

**Plik:** `Modals/DescriptionModal.php`

**Purpose:** Edycja krotkich i dlugich opisow per sklep (tabs).

**Tabs:**
1. **Default** - Opis domyslny PPM (wspolny dla wszystkich sklepow bez overrides)
2. **Shop 1** - Override dla sklepu 1
3. **Shop 2** - Override dla sklepu 2
4. etc.

**Fields per Tab:**
- Short Description (textarea, max 800 chars)
- Long Description (textarea, max 21844 chars)
- Character counters (real-time)

**Inheritance Logic:**
- Jezeli shop nie ma own description → inherit z Default
- Badge "Odziedziczone" w shop tab
- Klik "Edytuj" → enable textarea → save as override

**Key Bindings:**
```php
wire:model="descriptions.default.short_description"
wire:model="descriptions.default.long_description"
wire:model="descriptions.1.short_description"   // Shop 1
```

**Save Flow:**
1. Validate character limits
2. Update `pending_products.shop_descriptions` JSON
3. Close modal
4. Show success notification

### 6.5 ImportPricesModal

**Plik:** `Modals/ImportPricesModal.php`

**Purpose:** Grid edycji cen per grupa cenowa.

**UI Layout:**
```
Price Group    | Net Price | Gross Price | Margin (%)
---------------|-----------|-------------|------------
Detaliczna     | 100.00    | 123.00      | 20.0
Dealer Std     | 90.00     | 110.70      | 18.0
Dealer Premium | 85.00     | 104.55      | 15.0
Warsztat       | 95.00     | 116.85      | 19.0
Szkółka        | 88.00     | 108.24      | 17.0
Pracownik      | 80.00     | 98.40       | 15.0
```

**Features:**
- Auto-calculate gross from net (VAT 23%)
- Auto-calculate net from gross
- Margin calculator (%)
- Lock/Unlock per row (prevent accidental changes)
- Batch fill (fill all groups with same value)

**Validation:**
- Net > 0
- Gross > Net
- Margin >= 0

**Key Methods:**
```php
calculateGross(float $net): float
calculateNet(float $gross): float
updatePrice(int $priceGroupId, string $field, float $value)
lockPriceGroup(int $priceGroupId)
unlockPriceGroup(int $priceGroupId)
```

### 6.6 FeatureTemplateModal

**Plik:** `Modals/FeatureTemplateModal.php`

**Purpose:** Przypisywanie cech produktu (key-value pairs) z templatem.

**Workflow:**
1. Select template (optional) - pre-defined features dla typu produktu
2. Add/edit features manually
3. Save → update `pending_products.feature_data`

**Template Example (czesc-zamienna):**
```php
[
  'Material' => 'Stal',
  'Waga (kg)' => '2.5',
  'Kolor' => 'Czarny',
  'Producent OEM' => 'Honda',
  'Numer katalogowy' => '12345-ABC-678',
]
```

**UI:**
```
Feature Name        | Value
--------------------|------------------------
Material            | [Stal          ]
Waga (kg)           | [2.5           ]
Kolor               | [Czarny        ]
+ Dodaj cechę
```

**Key Methods:**
```php
loadTemplate(int $templateId)
addFeature()
removeFeature(int $index)
saveFeatures()
```

### 6.7 CompatibilityModal

**Plik:** `Modals/CompatibilityModal.php`

**Purpose:** Dopasowania czesci zamiennych do pojazdow (brand, model, year).

**Warunek:** Tylko dla `product_type.slug === 'czesc-zamienna'`

**UI:**
```
Marka       | Model        | Rocznik
------------|--------------|----------------
Honda       | CBR 600 RR   | 2005-2010
Yamaha      | YZF-R6       | 2008-2016
Kawasaki    | Ninja 650    | 2017-2023
+ Dodaj dopasowanie
```

**Features:**
- Import z pliku CSV (brand, model, year)
- Bulk delete
- Validation: year range format (YYYY-YYYY lub YYYY)

**Key Methods:**
```php
addCompatibility()
removeCompatibility(int $index)
importFromCsv(string $csvContent)
saveCompatibility()
```

### 6.8 PrestaShopCategoryPickerModal

**Plik:** `Modals/PrestaShopCategoryPickerModal.php`

**Purpose:** Wybor kategorii PrestaShop per sklep (hierarchical tree).

**Workflow:**
1. Select shop (dropdown)
2. Fetch category tree z PrestaShop API
3. Display hierarchical tree (expandable)
4. Checkbox selection (multiple categories)
5. Save → update `pending_products.shop_categories`

**Tree UI:**
```
☑ Electronics
  ☑ Laptops
  ☐ Smartphones
☐ Clothing
  ☐ T-Shirts
  ☐ Jeans
```

**Key Methods:**
```php
loadCategoriesForShop(int $shopId)
toggleCategory(int $categoryId)
expandCategory(int $categoryId)
saveCategoriesForShop()
```

### 6.9 UnpublishConfirmModal

**Plik:** `Modals/UnpublishConfirmModal.php` (partial - prosty modal)

**Purpose:** Potwierdzenie cofniecia publikacji.

**UI:**
```
Czy na pewno cofnac publikacje produktu ABC-123?

UWAGA: Produkt zostanie usuniety z:
- PrestaShop (wszystkie sklepy)
- Systemy ERP (jeśli zsynchronizowane)
- Tabela products (soft delete)

Status pending_products zmieni sie na "draft".

[Anuluj]  [Tak, cofnij publikacje]
```

**Flow:**
1. User klika "Cofnij publikacje"
2. Modal z ostrzezeniem
3. Klik "Tak" → dispatch event `confirmUnpublish`
4. `ProductUnpublishService::handle($pendingProduct)`
5. Success notification + refresh

### 6.10 Podsumowanie Modali

| Modal | Triggerowany przez | Output (pending_products field) |
|-------|--------------------|---------------------------------|
| ProductImportModal | "Nowy Import" button | Multiple rows created |
| ImageUploadModal | Row action "Obrazy" | `temp_media_paths` |
| VariantModal | Row action "Warianty" | `variant_data` |
| DescriptionModal | Row action "Opisy" | `shop_descriptions` |
| ImportPricesModal | Row action "Ceny" | `price_data` |
| FeatureTemplateModal | Row action "Cechy" | `feature_data` |
| CompatibilityModal | Row action "Dopasowania" | `compatibility_data` |
| PrestaShopCategoryPickerModal | Row action "Kategorie PS" | `shop_categories` |
| UnpublishConfirmModal | Published row "Cofnij" | `publish_status` → `draft` |

---

## 7. Pipeline Publikacji

### 7.1 Validation (Pre-Publish)

Przed publikacja, `ProductPublicationService` wykonuje walidacje:

**Required Checks:**
- `completion_percentage === 100.00`
- `sku` NOT NULL i unique w `products`
- `name` NOT NULL
- `product_type_id` EXISTS w `product_types`
- `publication_targets` NOT EMPTY (min 1 shop lub ERP)

**Validation Method:**
```php
public function validateForPublication(PendingProduct $pending): array
{
    $errors = [];

    if ($pending->completion_percentage < 100.00) {
        $errors[] = "Produkt niekompletny ({$pending->completion_percentage}%)";
    }

    if (Product::where('sku', $pending->sku)->exists()) {
        $errors[] = "SKU {$pending->sku} juz istnieje w products";
    }

    if (empty($pending->publication_targets)) {
        $errors[] = "Brak targetow publikacji";
    }

    return $errors; // Empty array = OK
}
```

### 7.2 Publication Targets (Routing Logic)

`publication_targets` JSON okresla gdzie produkt ma byc zsynchronizowany:

```json
{
  "erp_connections": [1, 2],
  "prestashop_shops": [1, 2, 3]
}
```

**PublicationTargetService Workflow:**
1. Parse `publication_targets`
2. Resolve shop IDs → `PrestaShopShop` models
3. Resolve ERP IDs → `ERPConnection` models
4. Return arrays of models

**Dispatch Logic:**
```php
// Po publikacji do products
foreach ($prestashopShops as $shop) {
    SyncProductToPrestaShop::dispatch($product, $shop, $userId);
}

foreach ($erpConnections as $connection) {
    SyncProductToERP::dispatchSync($product, $connection);
}
```

### 7.3 ProductPublicationService (10-Step Transaction)

**Plik:** `Services/Import/ProductPublicationService.php` (1038 linii)

**Main Method:** `publish(PendingProduct $pending): Product`

**Kroki w DB::transaction:**

```
┌─────────────────────────────────────────────────────────────┐
│           ProductPublicationService::publish()              │
└─────────────────────────────────────────────────────────────┘

DB::transaction {

  1. createProductFromPending(PendingProduct $pending): Product
     ↓
     - Insert do `products` (sku, name, slug, type, manufacturer, ean, etc.)
     - Return Product model

  2. createPriceEntries(Product $product, array $priceData): void
     ↓
     - Parse `pending.price_data` JSON
     - Insert do `product_prices` per price_group
     - Format: {price_group_id, product_id, price, is_gross, margin}

  3. createVariants(Product $product, array $variantData): Collection
     ↓
     - Parse `pending.variant_data.combinations`
     - Insert do `product_variants` per combination
     - Return Collection of ProductVariant models

  4. assignCategories(Product $product, array $categoryIds): void
     ↓
     - Parse `pending.category_ids`
     - Sync pivot `category_product` (attach categories)

  5. createShopData(Product $product, array $shopDescriptions, array $shopIds): void
     ↓
     - Parse `pending.shop_descriptions` + `pending.shop_ids`
     - Insert do `product_shop_data` per shop
     - Format: {product_id, shop_id, short_description, long_description, sync_status: 'pending'}

  6. createShopVariants(Product $product, Collection $variants, array $shopIds): void
     ↓
     - For each variant + shop combination
     - Insert do `shop_variants` (product_variant_id, shop_id, sync_status: 'pending')
     - Purpose: PS sync tracking

  7. handleMedia(Product $product, array $tempMediaPaths): Collection
     ↓
     - Parse `pending.temp_media_paths.images`
     - Move files: storage/temp/import_media/{uuid}/ → storage/app/public/products/{product_id}/
     - Insert do `media` table (path, type: 'image', is_primary, sort_order)
     - Return Collection of Media models

  8. assignVariantImages(Product $product, Collection $media, Collection $variants, array $variantCovers): void
     ↓
     - Parse `pending.temp_media_paths.variant_covers`
     - For each media item:
       - If media.variant_sku → find matching ProductVariant
       - Insert do `variant_images` (variant_id, media_id, is_cover)
     - Cover logic:
       - variant_covers["-RED"] = media_id → VariantImage.is_cover = true
       - Fallback: first image per variant
     - **KRYTYCZNE:** Per-variant cover NI inherited parent cover (if variant has own cover)

  9. markAsPublished(PendingProduct $pending, Product $product): void
     ↓
     - Update `pending_products`:
       - publish_status = 'published'
       - published_at = now()
       - published_as_product_id = $product->id
       - published_by = Auth::id()

  10. createPublishHistory(PendingProduct $pending, Product $product, array $dispatchedJobs): void
      ↓
      - Insert do `publish_history`:
        - pending_product_id, product_id, published_by, published_at
        - sku_snapshot, name_snapshot
        - published_shops (JSON), published_categories (JSON)
        - sync_jobs_dispatched (JSON): {ps: [job_id], erp: [job_id]}
        - sync_status = 'pending'

      - Dispatch sync jobs:
        foreach ($prestashopShops as $shop) {
            $jobId = SyncProductToPrestaShop::dispatch($product, $shop)->id;
            $dispatchedJobs['ps'][] = $jobId;
        }

        foreach ($erpConnections as $conn) {
            $jobId = SyncProductToERP::dispatchSync($product, $conn)->id;
            $dispatchedJobs['erp'][] = $jobId;
        }

} // END DB::transaction

return $product;
```

**Transaction Guarantees:**
- **Atomic:** All 10 steps succeed OR all rollback
- **Isolation:** No partial products visible during transaction
- **Durability:** Committed products persistent

**Error Handling:**
```php
try {
    DB::transaction(function () use ($pending) {
        // 10 steps...
    });
} catch (\Exception $e) {
    Log::error('Publication failed', [
        'pending_id' => $pending->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    // Mark as failed
    $pending->update([
        'publish_status' => 'failed',
        'error_message' => $e->getMessage(),
    ]);

    throw $e;
}
```

### 7.4 Variant Images (Per-Variant Cover Logic)

**Problem:** Warianty moga miec wlasne okładki (cover image). Parent product tez ma okładke (primary image). Jak zapobiec konfliktom?

**Rozwiązanie:** `assignVariantImages()` implementuje **smart cover assignment**:

**Rules:**
1. **Shared images** (`variant_sku=null`, `is_primary=false`) → przypisane do WSZYSTKICH wariantow
2. **Variant-specific images** (`variant_sku="-RED"`) → przypisane TYLKO do matching wariantu
3. **Parent cover** (`is_primary=true`, shared) → **SKIP** jesli wariant ma wlasna okładke w `variant_covers`
4. **No draft assignments** → all-to-all fallback (backward compatibility)

**Code Flow:**
```php
// Step 8 w ProductPublicationService
public function assignVariantImages(Product $product, Collection $media, Collection $variants, array $variantCovers): void
{
    foreach ($variants as $variant) {
        $variantSku = $variant->sku;
        $variantSuffix = str_replace($product->sku, '', $variantSku); // e.g., "-RED"

        $hasOwnCover = isset($variantCovers[$variantSuffix]);

        foreach ($media as $image) {
            $shouldAssign = false;

            // Rule 1: Shared images (non-primary)
            if ($image->variant_sku === null && !$image->is_primary) {
                $shouldAssign = true;
            }

            // Rule 2: Variant-specific
            if ($image->variant_sku === $variantSuffix) {
                $shouldAssign = true;
            }

            // Rule 3: Parent cover - SKIP if variant has own cover
            if ($image->is_primary && $image->variant_sku === null) {
                if (!$hasOwnCover) {
                    $shouldAssign = true; // Fallback
                } else {
                    // SKIP - variant ma wlasna okładke
                    continue;
                }
            }

            if ($shouldAssign) {
                $isCover = ($variantCovers[$variantSuffix] ?? null) === $image->id;

                VariantImage::create([
                    'variant_id' => $variant->id,
                    'media_id' => $image->id,
                    'is_cover' => $isCover,
                ]);
            }
        }
    }
}
```

**Efekt:** Shop Tab pokazuje poprawne okładki per wariant (bez nakładania sie parent cover).

### 7.5 Sync Dispatch (Multi-Target)

Po publikacji, `createPublishHistory()` dispatchuje joby synchronizacji:

**PrestaShop Sync:**
```php
foreach ($prestashopShops as $shop) {
    $job = SyncProductToPrestaShop::dispatch($product, $shop, Auth::id());
    $dispatchedJobs['ps'][] = $job->id;
}
```

**Job:** `SyncProductToPrestaShop` (queue: `prestashop_sync`)
- Timeout: 300s
- Tries: 3
- Unique: 1h per product+shop
- Tracked in: `sync_jobs` table

**ERP Sync:**
```php
foreach ($erpConnections as $connection) {
    $job = SyncProductToERP::dispatchSync($product, $connection);
    $dispatchedJobs['erp'][] = $job->id;
}
```

**Job:** `SyncProductToERP` (queue: `erp_default`)
- Timeout: 600s (10min)
- Tries: 3
- Unique: 1h per product+connection
- Tracked in: `product_erp_data` table

**Tracking:**
Dispatch job IDs zapisane w `publish_history.sync_jobs_dispatched`:
```json
{
  "ps": [12345, 12346, 12347],
  "erp": [98765, 98766]
}
```

**Polling (Optional):**
Import Panel moze polowac statusy jobow:
```javascript
// Alpine.js polling
setInterval(async () => {
    await $wire.checkSyncStatus(pendingProductId);
}, 5000);
```

### 7.6 Unpublish (Full Rollback)

**Service:** `ProductUnpublishService` (264 linii)

**Purpose:** Cofniecie publikacji z pelnym cleanupem zewnetrznych systemow.

**Workflow:**
1. Find published `Product` via `pending_products.published_as_product_id`
2. Delete z PrestaShop API (per shop)
   - Jezeli produkt ma external_id w `product_shop_data` → DELETE /api/products/{id}
3. ERP cleanup (jezeli dostepne)
   - BaseLinker: mark as inactive
   - Subiekt GT: N/A (read-only)
   - Dynamics: mark as deleted
4. Delete lokalny `products` record (soft delete)
5. Restore `pending_products`:
   - `publish_status` = 'draft'
   - `published_at` = NULL
   - `published_as_product_id` = NULL
6. Log operation w `publish_history` z `unpublished_at`

**Code:**
```php
public function unpublish(PendingProduct $pending): void
{
    DB::transaction(function () use ($pending) {
        $product = Product::find($pending->published_as_product_id);

        if (!$product) {
            throw new \Exception("Published product not found");
        }

        // 1. Delete from PrestaShop
        $this->deleteFromPrestaShop($product);

        // 2. ERP cleanup
        $this->cleanupERP($product);

        // 3. Soft delete local product
        $product->delete();

        // 4. Restore pending_products
        $pending->update([
            'publish_status' => 'draft',
            'published_at' => null,
            'published_as_product_id' => null,
        ]);

        // 5. Log unpublish
        PublishHistory::where('pending_product_id', $pending->id)
            ->update(['unpublished_at' => now()]);
    });
}
```

**PrestaShop Delete:**
```php
protected function deleteFromPrestaShop(Product $product): void
{
    $shopData = $product->shopData; // HasMany relation

    foreach ($shopData as $data) {
        if ($data->external_id) {
            try {
                $client = new PrestaShop8Client($data->shop);
                $client->deleteProduct($data->external_id);

                Log::info('Product deleted from PS', [
                    'product_id' => $product->id,
                    'shop_id' => $data->shop_id,
                    'ps_id' => $data->external_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('PS delete failed (non-critical)', [
                    'product_id' => $product->id,
                    'shop_id' => $data->shop_id,
                    'error' => $e->getMessage(),
                ]);
                // Non-blocking - continue with other shops
            }
        }
    }
}
```

---

## 8. Jobs & Workers

### 8.1 PrestaShop Jobs

| Job | Queue | Timeout | Tries | Opis |
|-----|-------|---------|-------|------|
| **BulkImportProducts** | `prestashop_sync` | 3600s (1h) | 1 | Import produktow z PS do pending_products (3 modes) |
| **ImportFeaturesFromPSJob** | `prestashop` | 600s (10min) | 2 | Import feature templates z PS |
| **BulkImportDescriptionsJob** | `prestashop` | 1800s (30min) | 2 | Import bulk descriptions z PS |
| **SyncProductToPrestaShop** | `prestashop_sync` | 300s (5min) | 3 | Sync published product → PS shop |
| **SyncShopVariantsToPrestaShopJob** | `prestashop_sync` | 600s (10min) | 3 | Sync wariantow (ceny, stany, covers) |

### 8.2 ERP Jobs

| Job | Queue | Timeout | Tries | Opis |
|-----|-------|---------|-------|------|
| **SyncProductToERP** | `erp_default` | 600s (10min) | 3 | Sync published product → ERP connection |
| **BaselinkerSyncJob** | `erp_default` | 1800s (30min) | 2 | BaseLinker full sync |
| **SubiektGTSyncJob** | `erp_default` | 1800s (30min) | 2 | Subiekt GT full sync (via SubiektGTService) |

### 8.3 BulkImportProducts (Details)

**Modes:**
1. **all** - Wszystkie produkty ze sklepu
2. **category** - Produkty z konkretnej kategorii PS
3. **individual** - Lista product IDs

**Properties:**
```php
public int $shopId;
public string $mode;              // 'all', 'category', 'individual'
public array $params;             // ['categoryId' => 5] or ['productIds' => [1,2,3]]
public ?string $sessionUuid;      // ImportSession UUID
public int $timeout = 3600;
```

**Workflow:**
```php
public function handle(): void
{
    $session = ImportSession::where('uuid', $this->sessionUuid)->firstOrFail();
    $session->update(['status' => 'parsing']);

    try {
        $products = $this->fetchProducts();

        foreach ($products as $psProduct) {
            $this->createPendingProduct($psProduct, $session);

            $session->increment('products_created');
        }

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

    } catch (\Exception $e) {
        $session->update([
            'status' => 'failed',
            'error_log' => json_encode(['error' => $e->getMessage()]),
        ]);

        throw $e;
    }
}
```

**Fetch Methods:**
```php
protected function fetchProducts(): array
{
    $client = new PrestaShop8Client($this->shop);

    switch ($this->mode) {
        case 'all':
            return $client->getProducts(['display' => 'full']);

        case 'category':
            $categoryId = $this->params['categoryId'];
            return $client->getProductsByCategory($categoryId);

        case 'individual':
            $productIds = $this->params['productIds'];
            return collect($productIds)->map(fn($id) => $client->getProduct($id))->all();
    }
}
```

**Create PendingProduct:**
```php
protected function createPendingProduct(array $psProduct, ImportSession $session): void
{
    PendingProduct::create([
        'sku' => $psProduct['reference'],
        'name' => $psProduct['name'],
        'product_type_id' => 1, // Default: Simple
        'manufacturer' => $psProduct['manufacturer_name'] ?? null,
        'ean' => $psProduct['ean13'] ?? null,
        'base_price' => $psProduct['price'] ?? 0,
        'shop_ids' => [$this->shopId],
        'shop_descriptions' => [
            $this->shopId => [
                'short_description' => $psProduct['description_short'] ?? '',
                'long_description' => $psProduct['description'] ?? '',
            ],
        ],
        'temp_media_paths' => $this->downloadImages($psProduct['id']),
        'import_session_id' => $session->id,
        'imported_by' => Auth::id(),
        'imported_at' => now(),
        'completion_percentage' => 50.0, // Partial (brak kategorii, etc.)
    ]);
}
```

### 8.4 Job Tracking (Import Session)

**UI Indicators:**
- Import w trakcie: Progress bar (products_created / total_rows)
- Status badge: `parsing`, `ready`, `publishing`, `completed`, `failed`
- Error log (jesli failed): JSON array errors

**Polling (Optional):**
```javascript
// Alpine.js in modal
x-data="{
    pollingInterval: null,
    startPolling() {
        this.pollingInterval = setInterval(async () => {
            await $wire.checkImportStatus();
            if ($wire.importStatus === 'completed') {
                this.stopPolling();
                $wire.dispatch('importCompleted');
            }
        }, 3000); // 3s
    }
}"
```

---

## 9. Komponenty UI

### 9.1 Resizable Columns (Alpine.js)

**Plik:** `resources/js/resizable-columns.js`

**Feature:** Kolumny tabeli sa resizable (drag handle).

**Implementation:**
```javascript
Alpine.data('resizableTable', () => ({
    columnWidths: {},

    init() {
        this.loadColumnWidths();
        this.attachResizeHandles();
    },

    loadColumnWidths() {
        const saved = localStorage.getItem('import_panel_column_widths');
        this.columnWidths = saved ? JSON.parse(saved) : this.getDefaultWidths();
    },

    saveColumnWidths() {
        localStorage.setItem('import_panel_column_widths', JSON.stringify(this.columnWidths));
    },

    getDefaultWidths() {
        return {
            'sku': 150,
            'name': 300,
            'type': 120,
            'manufacturer': 150,
            'completion': 100,
            'actions': 200,
        };
    },

    resizeColumn(columnKey, newWidth) {
        this.columnWidths[columnKey] = Math.max(20, Math.min(500, newWidth));
        this.saveColumnWidths();
    }
}))
```

**Blade Usage:**
```html
<table x-data="resizableTable">
    <thead>
        <tr>
            <th :style="`width: ${columnWidths.sku}px`">
                SKU
                <div class="resize-handle" @mousedown="startResize('sku', $event)"></div>
            </th>
            <!-- ... other columns -->
        </tr>
    </thead>
</table>
```

### 9.2 Inline Editing

**Feature:** Kliknij cell → editable input → blur/enter → save

**Implementation (Livewire):**
```php
// Trait: ImportPanelActions
public function updateField(int $pendingId, string $field, $value): void
{
    $pending = PendingProduct::findOrFail($pendingId);

    $this->authorize('update', $pending);

    $pending->update([$field => $value]);

    // Recalculate completion
    $pending->recalculateCompletion();

    $this->dispatch('fieldUpdated', ['pendingId' => $pendingId, 'field' => $field]);
}
```

**Blade:**
```html
<td x-data="{ editing: false, value: '{{ $pending->name }}' }">
    <div x-show="!editing" @click="editing = true">
        {{ $pending->name }}
    </div>
    <input
        x-show="editing"
        x-model="value"
        @blur="editing = false; $wire.updateField({{ $pending->id }}, 'name', value)"
        @keydown.enter="editing = false; $wire.updateField({{ $pending->id }}, 'name', value)"
    />
</td>
```

### 9.3 Category Cascade (L3-L8)

**Feature:** Inline dropdowny dla kategorii L3→L4→L5→L6→L7→L8 (kaskadowy wybor).

**Trait:** `ImportPanelCategoryShopTrait`

**Logic:**
1. L3 selected → load L4 children
2. L4 selected → load L5 children
3. etc.

**Blade:**
```html
<!-- L3 Column -->
<select wire:change="updateCategoryLevel({{ $pending->id }}, 3, $event.target.value)">
    <option value="">-- Wybierz L3 --</option>
    @foreach($categoriesL3 as $cat)
        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
    @endforeach
</select>

<!-- L4 Column (populated after L3 selection) -->
<select wire:change="updateCategoryLevel({{ $pending->id }}, 4, $event.target.value)">
    <option value="">-- Wybierz L4 --</option>
    @foreach($this->getCategoriesForLevel($pending, 4) as $cat)
        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
    @endforeach
</select>
```

**Create on Fly:**
- Input field w dropdownzie: "+ Utwórz nowa kategorie"
- Livewire method: `createCategoryInline(int $pendingId, int $level, string $name)`

### 9.4 Publication Targets (Fixed Dropdown Positioning)

**Feature:** Dropdown z checkboxami dla targetow publikacji (PS shops + ERP connections).

**Problem:** Dropdown uciekajacy poza viewport (fixed positioning issue).

**Solution (Alpine.js):**
```javascript
x-data="{
    open: false,
    calculatePosition() {
        const button = this.$refs.button;
        const dropdown = this.$refs.dropdown;
        const rect = button.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;

        if (spaceBelow < 300 && spaceAbove > spaceBelow) {
            // Open upward
            dropdown.style.bottom = '100%';
            dropdown.style.top = 'auto';
        } else {
            // Open downward
            dropdown.style.top = '100%';
            dropdown.style.bottom = 'auto';
        }
    }
}"
@click.outside="open = false"
```

**Blade:**
```html
<div x-data="{ open: false }">
    <button @click="open = !open; calculatePosition()">
        Targets ({{ count($pending->publication_targets) }})
    </button>

    <div x-show="open" x-ref="dropdown" class="absolute z-50">
        <div class="dropdown-content">
            <!-- PrestaShop Shops -->
            @foreach($shops as $shop)
                <label>
                    <input type="checkbox" wire:model="targets.{{ $pending->id }}.prestashop_shops" value="{{ $shop->id }}">
                    {{ $shop->name }}
                </label>
            @endforeach

            <!-- ERP Connections -->
            @foreach($erpConnections as $conn)
                <label>
                    <input type="checkbox" wire:model="targets.{{ $pending->id }}.erp_connections" value="{{ $conn->id }}">
                    {{ $conn->name }}
                </label>
            @endforeach
        </div>
    </div>
</div>
```

### 9.5 Bulk Operations

**Feature:** Zaznacz wiersze (checkbox) → bulk action (delete, type, shops, category, publish).

**Trait:** `ImportPanelBulkOperations`

**UI:**
```
☑ Select All (25)

[✓] ABC-123  Product One   ...
[✓] XYZ-456  Product Two   ...
[ ] DEF-789  Product Three ...

Selected: 2

[Bulk Delete] [Bulk Type] [Bulk Shops] [Bulk Publish]
```

**Methods:**
```php
// Trait: ImportPanelBulkOperations
public array $selectedIds = [];
public bool $selectAll = false;

public function toggleSelectAll(): void
{
    if ($this->selectAll) {
        $this->selectedIds = $this->getPaginatedProducts()->pluck('id')->toArray();
    } else {
        $this->selectedIds = [];
    }
}

public function bulkDelete(): void
{
    $this->authorize('delete', PendingProduct::class);

    PendingProduct::whereIn('id', $this->selectedIds)->delete();

    $this->selectedIds = [];
    $this->dispatch('bulkOperationCompleted');
}

public function bulkUpdateType(int $typeId): void
{
    PendingProduct::whereIn('id', $this->selectedIds)
        ->update(['product_type_id' => $typeId]);

    $this->selectedIds = [];
}

public function bulkPublish(): void
{
    foreach ($this->selectedIds as $id) {
        $pending = PendingProduct::find($id);

        if ($pending->is_ready_for_publish) {
            $service = new ProductPublicationService();
            $service->publish($pending);
        }
    }

    $this->selectedIds = [];
}
```

### 9.6 Filters (Basic + Advanced)

**Trait:** `ImportPanelFilters`

**Basic Filters (Always Visible):**
- Search (SKU, name)
- Status (draft, scheduled, published, failed)
- Type (product_type_id)
- Manufacturer (text input)

**Advanced Filters (Toggle):**
- Completion range (0-100%)
- Date range (imported_at)
- shop_internet checkbox
- split_payment checkbox
- variant_product checkbox

**Properties:**
```php
// Basic
#[Url] public string $search = '';
#[Url] public ?string $filterStatus = null;
#[Url] public ?int $filterType = null;
#[Url] public string $filterManufacturer = '';

// Advanced
public bool $showAdvancedFilters = false;
#[Url] public ?int $completionMin = null;
#[Url] public ?int $completionMax = null;
#[Url] public ?string $dateFrom = null;
#[Url] public ?string $dateTo = null;
#[Url] public ?bool $filterShopInternet = null;
```

**Query:**
```php
public function getFilteredQuery(): Builder
{
    $query = PendingProduct::query();

    // Search
    if ($this->search) {
        $query->where(function($q) {
            $q->where('sku', 'like', "%{$this->search}%")
              ->orWhere('name', 'like', "%{$this->search}%");
        });
    }

    // Status
    if ($this->filterStatus) {
        $query->where('publish_status', $this->filterStatus);
    }

    // Type
    if ($this->filterType) {
        $query->where('product_type_id', $this->filterType);
    }

    // Completion range
    if ($this->completionMin !== null) {
        $query->where('completion_percentage', '>=', $this->completionMin);
    }

    if ($this->completionMax !== null) {
        $query->where('completion_percentage', '<=', $this->completionMax);
    }

    return $query;
}
```

### 9.7 Publish Button States (7 States)

**States:**

| State | CSS Class | Icon | Text | Disabled |
|-------|-----------|------|------|----------|
| **ready** | `btn-success` | ✓ | Publikuj | false |
| **incomplete** | `btn-warning` | ⚠ | Niekompletny (80%) | true |
| **scheduled** | `btn-info` | 🕒 | Zaplanowano | true |
| **publishing** | `btn-primary` | ⏳ | Publikowanie... | true |
| **published** | `btn-secondary` | ✓ | Opublikowano | false (allows re-publish) |
| **failed** | `btn-danger` | ✗ | Blad publikacji | false (allows retry) |
| **disabled** | `btn-disabled` | - | Brak uprawnien | true |

**Blade Logic:**
```html
@php
    $state = match(true) {
        !$this->can('publish', $pending) => 'disabled',
        $pending->publish_status === 'published' => 'published',
        $pending->publish_status === 'publishing' => 'publishing',
        $pending->publish_status === 'scheduled' => 'scheduled',
        $pending->publish_status === 'failed' => 'failed',
        $pending->completion_percentage < 100 => 'incomplete',
        default => 'ready',
    };
@endphp

<button
    wire:click="publishSingle({{ $pending->id }})"
    class="btn {{ $buttonClasses[$state] }}"
    @disabled($buttonDisabled[$state])
>
    {!! $buttonIcons[$state] !!} {{ $buttonTexts[$state] }}
</button>
```

---

## 10. Uprawnienia

**Middleware:** `can:import.products` (Spatie Laravel Permission)

**Permissions (seeded):**

| Permission | Opis |
|------------|------|
| `import.products.view` | Widok Import Panel |
| `import.products.create` | Import nowych produktow (modals) |
| `import.products.edit` | Edycja pending_products (inline + modals) |
| `import.products.delete` | Usuniecie pending_products |
| `import.products.publish` | Publikacja pending → products |
| `import.products.unpublish` | Cofniecie publikacji |
| `import.products.bulk` | Bulk operations |

**Gate Checks (ImportPanelPermissionTrait):**
```php
public function canView(): bool
{
    return Auth::user()->can('import.products.view');
}

public function canCreate(): bool
{
    return Auth::user()->can('import.products.create');
}

public function canEdit(PendingProduct $pending): bool
{
    return Auth::user()->can('import.products.edit');
}

public function canPublish(PendingProduct $pending): bool
{
    return Auth::user()->can('import.products.publish')
        && $pending->is_ready_for_publish;
}
```

**Roles (przykład):**
- **Admin** - ALL permissions
- **Manager** - ALL import permissions
- **Redaktor** - view, edit (NO delete, publish)
- **User** - view only

---

## 11. Routing

**Plik:** `routes/web.php`

| Method | URL | Handler | Name | Permissions |
|--------|-----|---------|------|-------------|
| GET | `/admin/products/import` | ProductImportPanel | `admin.products.import.index` | `import.products.view` |
| POST | `/admin/products/import/create` | ProductImportPanel@save | `admin.products.import.store` | `import.products.create` |
| PATCH | `/admin/products/import/{id}` | ProductImportPanel@updateField | `admin.products.import.update` | `import.products.edit` |
| DELETE | `/admin/products/import/{id}` | ProductImportPanel@delete | `admin.products.import.destroy` | `import.products.delete` |
| POST | `/admin/products/import/{id}/publish` | ProductImportPanel@publishSingle | `admin.products.import.publish` | `import.products.publish` |
| POST | `/admin/products/import/{id}/unpublish` | ProductImportPanel@unpublish | `admin.products.import.unpublish` | `import.products.unpublish` |
| POST | `/admin/products/import/bulk/publish` | ProductImportPanel@bulkPublish | `admin.products.import.bulk.publish` | `import.products.bulk` |
| DELETE | `/admin/products/import/bulk/delete` | ProductImportPanel@bulkDelete | `admin.products.import.bulk.delete` | `import.products.bulk` |

**Route Group:**
```php
Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('products/import')
        ->name('admin.products.import.')
        ->middleware(['can:import.products.view'])
        ->group(function () {
            Route::get('/', ProductImportPanel::class)->name('index');
            // ... other routes
        });
});
```

---

## 12. Troubleshooting

### Issue 1: Import Modal Not Reopening After Import (E2)

**Symptom:** Po kliknieciu "Importuj", modal zamyka sie i NIE otwiera sie ponownie.

**Causa:** `importCompleted` event byl obslugivany 2x:
1. `protected $listeners = ['importCompleted' => 'handleImportCompleted']`
2. `#[On('importCompleted')] public function handleImportCompleted()`

**Fix:** Usunieto z `$listeners`, pozostawiono tylko `#[On]` attribute.

```php
// BEFORE (double-firing)
protected $listeners = [
    'importCompleted' => 'handleImportCompleted',
    // ...
];

#[On('importCompleted')]
public function handleImportCompleted() { ... }

// AFTER (single-firing)
protected $listeners = [
    // 'importCompleted' removed
    // ...
];

#[On('importCompleted')]
public function handleImportCompleted() { ... }
```

### Issue 2: Completion Percentage Not Updating

**Symptom:** Po edycji pol, `completion_percentage` nie aktualizuje sie automatycznie.

**Causa:** Brak wywolania `recalculateCompletion()` po update.

**Fix:** Dodano `recalculateCompletion()` hook w `PendingProduct` model:

```php
// Model: PendingProduct
protected static function booted()
{
    static::saving(function (PendingProduct $pending) {
        $pending->recalculateCompletion();
    });
}

public function recalculateCompletion(): void
{
    $required = ['sku', 'name', 'manufacturer_id', 'category_ids', 'product_type_id', 'publication_targets'];
    $optional = ['temp_media_paths', 'shop_descriptions', 'base_price', 'variant_data'];

    $completedRequired = collect($required)->filter(fn($f) => !empty($this->$f))->count();
    $completedOptional = collect($optional)->filter(fn($f) => !empty($this->->$f))->count();

    $requiredPercentage = ($completedRequired / count($required)) * 80;
    $optionalPercentage = ($completedOptional / count($optional)) * 20;

    $this->completion_percentage = round($requiredPercentage + $optionalPercentage, 2);
    $this->is_ready_for_publish = $this->completion_percentage === 100.00;
}
```

### Issue 3: SKU Unique Validation Failing on Edit

**Symptom:** Edycja SKU na identyczny SKU (brak zmiany) rzuca validation error "SKU juz istnieje".

**Causa:** Validation rule `unique:pending_products,sku` bez `ignore` clause.

**Fix:** Dodano `ignore` w validation:

```php
// Livewire validation
protected function rules(): array
{
    return [
        'sku' => [
            'required',
            'string',
            'max:100',
            Rule::unique('pending_products', 'sku')->ignore($this->editingProductId),
            'regex:/^[A-Z0-9\-_]+$/',
        ],
        // ...
    ];
}
```

### Issue 4: Variant Covers Not Syncing to PrestaShop

**Symptom:** Po publikacji, Shop Tab pokazuje inna okładke dla wariantu niz draft (rodzic zamiast wariantu).

**Causa:** `assignVariantImages()` przypisywalo parent cover (`is_primary=true`) do wszystkich wariantow, nawet tych z wlasna okładka.

**Fix:** Smart cover assignment logic - SKIP parent cover jesli wariant ma wlasna:

```php
// Rule 3: Parent cover - SKIP if variant has own cover
if ($image->is_primary && $image->variant_sku === null) {
    if (!$hasOwnCover) {
        $shouldAssign = true; // Fallback
    } else {
        // SKIP - variant ma wlasna okładke
        continue;
    }
}
```

**Reference:** Publication Pipeline section 7.4

### Issue 5: Frozen Row Still Editable

**Symptom:** Po publikacji, wiersz ma CSS `.import-row-frozen` ale inputy sa klikalne.

**Causa:** `pointer-events: none` na `.import-row-frozen` nie dzialalo z `z-index` conflicts.

**Fix:** Dodano explicit disable na inputy:

```blade
<input
    wire:model="name"
    @disabled($pending->publish_status === 'published')
    class="{{ $pending->publish_status === 'published' ? 'input-disabled' : '' }}"
/>
```

```css
.input-disabled {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
}
```

### Issue 6: Bulk Publish Timeout

**Symptom:** Bulk publish 50+ produktow ends z timeout (max_execution_time 120s).

**Causa:** Synchroniczne przetwarzanie w petli foreach.

**Fix:** Dispatch jobow do kolejki:

```php
// BEFORE (synchroniczne)
public function bulkPublish(): void
{
    foreach ($this->selectedIds as $id) {
        $pending = PendingProduct::find($id);
        $service = new ProductPublicationService();
        $service->publish($pending); // BLOCKING
    }
}

// AFTER (asynchroniczne)
public function bulkPublish(): void
{
    foreach ($this->selectedIds as $id) {
        BulkPublishProductJob::dispatch($id);
    }

    $this->dispatch('bulkPublishStarted', ['count' => count($this->selectedIds)]);
}
```

**New Job:**
```php
class BulkPublishProductJob implements ShouldQueue
{
    public function handle(): void
    {
        $pending = PendingProduct::find($this->pendingId);

        if ($pending && $pending->is_ready_for_publish) {
            $service = new ProductPublicationService();
            $service->publish($pending);
        }
    }
}
```

---

## 13. Changelog

### v1.0.0 (2026-02-13) - Initial Release

**Features:**
- ✅ Import Panel glowny komponent (ProductImportPanel)
- ✅ 3 metody importu: Column Mode, CSV Mode, PrestaShop Pull
- ✅ Draft system (pending_products table) z JSON columns
- ✅ 10 modalnych okien (Images, Variants, Descriptions, Prices, Features, Compatibility, Categories, Unpublish)
- ✅ Inline editing (SKU, name, type, manufacturer)
- ✅ Bulk operations (delete, type, shops, category, publish)
- ✅ Completion tracking (0-100% progress bar)
- ✅ Skip flags z audit trail
- ✅ Publication pipeline (ProductPublicationService 10-step transaction)
- ✅ Multi-target sync (PrestaShop + ERP)
- ✅ Per-variant cover assignment (smart cover logic)
- ✅ Frozen state dla opublikowanych produktow
- ✅ Unpublish z full rollback (ProductUnpublishService)
- ✅ Resizable columns (Alpine.js + localStorage)
- ✅ Advanced filters (completion range, dates, booleans)
- ✅ Publication targets dropdown (fixed positioning)
- ✅ Import Session tracking

**Services:**
- ✅ ProductPublicationService (1038L)
- ✅ PublicationTargetService (372L)
- ✅ ProductUnpublishService (264L)
- ✅ BatchImportProcessor
- ✅ ColumnMappingService
- ✅ CsvParserService
- ✅ ExcelParserService
- ✅ SkuParserService (19 methods)
- ✅ SkuValidatorService
- ✅ XlsxTemplateGenerator

**Jobs:**
- ✅ BulkImportProducts (1064L, 3 modes)
- ✅ ImportFeaturesFromPSJob
- ✅ BulkImportDescriptionsJob

**Traits:**
- ✅ ImportPanelActions (255L)
- ✅ ImportPanelBulkOperations (504L)
- ✅ ImportPanelPublicationTrait (598L)
- ✅ ImportPanelFilters
- ✅ ImportPanelCategoryShopTrait
- ✅ ImportPanelPermissionTrait
- ✅ ImportModalColumnModeTrait
- ✅ ImportModalCsvModeTrait
- ✅ ImportModalSwitchesTrait
- ✅ HandlesImageUpload
- ✅ HandlesVariantImages

**Bugfixes:**
- 🐛 E2: Import modal not reopening (double-firing `importCompleted`)
- 🐛 Completion percentage not auto-updating
- 🐛 SKU unique validation failing on edit
- 🐛 Variant covers not syncing correctly
- 🐛 Frozen row still editable
- 🐛 Bulk publish timeout (synchroniczne → asynchroniczne)

**Known Limitations:**
- ⚠️ Max 10 images per upload session (file picker limit)
- ⚠️ CSV max 5000 rows (memory limit)
- ⚠️ PrestaShop import timeout dla >1000 produktow (use category mode)
- ⚠️ Per-variant covers require PPM Manager PrestaShop module

---

**Koniec dokumentacji Import Panel**
