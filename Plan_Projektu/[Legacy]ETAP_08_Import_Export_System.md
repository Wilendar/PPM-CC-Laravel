# ❌ ETAP 08: SYSTEM IMPORTU/EKSPORTU CSV/XLSX

## PLAN RAMOWY ETAPU

- ❌ FAZA 1: Template Generator Service
- ❌ FAZA 2: Import Engine Service
- ❌ FAZA 3: Export Engine Service
- ❌ FAZA 4: UI/UX Implementation
- ❌ FAZA 5: Queue Jobs Implementation
- ❌ FAZA 5b: Testy integracyjne systemu importu/eksportu

---


**Status Ogólny:** ❌ NOT STARTED
**Źródło:** ETAP_05a SEKCJA 5 (CSV IMPORT/EXPORT SYSTEM)
**Cel:** Uniwersalny system importu/eksportu produktów przez CSV/XLSX (niezależny od PrestaShop API)
**Czas szacowany:** 21-27h (4-5 dni roboczych)
**Dependencies:**
- ✅ ETAP_05a SEKCJA 0-4 (Database schema, models, services) - COMPLETED
- ⏳ ETAP_07 FAZA 5 (VariantImportValidationService) - można używać shared validation
**Agent:** laravel-expert + livewire-specialist

---

## 🎯 ZAKRES ETAPU

System CSV/XLSX Import/Export umożliwia:
- **Import produktów** z plików Excel/CSV (warianty + cechy + dopasowania)
- **Eksport produktów** do Excel/CSV w różnych formatach
- **Template Generator** - dynamiczne szablony per ProductType i Shop
- **Column Mapping** - auto-detection + manual override + saved templates
- **Compatibility Field Parsing** - obsługa pipe-delimited values
- **Validation** - comprehensive rules + error reporting
- **Queue Jobs** - chunked import dla dużych plików (>100 rows)

**Oddzielenie od ETAP_07:**
- ETAP_07: PrestaShop API integration (fetch/push przez REST API)
- ETAP_08: CSV/XLSX file-based import/export (universal, dla wszystkich źródeł)

---

## ❌ FAZA 1: TEMPLATE GENERATOR SERVICE (3-4h)

**Agent:** laravel-expert
**Dependency:** None
**Status:** ❌ NOT STARTED

**Cel:** Dynamiczne generowanie szablonów CSV based on context (ProductType + Shop + FeatureSet)

### 1.1 TemplateGenerator Service (2-3h)

**Lokalizacja:** `app/Services/Import/TemplateGenerator.php`

**Public Methods:**

```php
/**
 * Generate CSV template dla specific product type i shop
 *
 * @param ProductType $productType
 * @param PrestaShopShop|null $shop
 * @param bool $includeCompatibility
 * @return array [columns, sample_row]
 */
public function generateTemplate(
    ProductType $productType,
    ?PrestaShopShop $shop = null,
    bool $includeCompatibility = false
): array;

/**
 * Get columns dla Części zamiennych
 */
protected function getPartColumns(PrestaShopShop $shop): array
{
    return [
        'SKU',
        'Name',
        'Manufacturer',
        'Price',
        'Stock',
        'Compatibility_Original', // "YCF 50|YCF 88"
        'Compatibility_Replacement', // "Honda CRF50"
        'Model_Auto', // read-only (suma)
        // + dynamic features based on feature_set
        'Feature_Material',
        'Feature_Weight',
    ];
}

/**
 * Get columns dla Pojazdów
 */
protected function getVehicleColumns(): array
{
    return [
        'SKU',
        'Name',
        'Manufacturer',
        'Price',
        'Stock',
        'Feature_Model',
        'Feature_Year',
        'Feature_Engine',
        'Feature_VIN',
    ];
}
```

**Example Output (Części zamienne + YCF shop):**

```csv
SKU,Name,Manufacturer,Price,Stock,Compatibility_Original,Compatibility_Replacement,Model_Auto,Feature_Material,Feature_Weight
BRK-001,"Klocki hamulcowe","OEM Parts",89.99,25,"YCF 50|YCF 88","Honda CRF50","YCF 50|YCF 88|Honda CRF50","Ceramic","0.5kg"
```

**Sub-tasks:**
- ❌ 1.1.1 Create TemplateGenerator service class
- ❌ 1.1.2 Implement getPartColumns() method
- ❌ 1.1.3 Implement getVehicleColumns() method
- ❌ 1.1.4 Implement dynamic feature column generation (based on feature_set)
- ❌ 1.1.5 Generate sample row dla preview
- ❌ 1.1.6 Unit tests (5 test cases)

**Deliverables:**
└── 📁 PLIK: app/Services/Import/TemplateGenerator.php (~150 linii)
└── 📁 PLIK: tests/Unit/Services/TemplateGeneratorTest.php

---

### 1.2 Predefined Templates (1h)

**Status:** ❌ NOT STARTED

**3 predefined templates:**
1. **POJAZDY** - Vehicle products (Model, Year, Engine, VIN)
2. **CZĘŚCI** - Spare parts (Compatibility, Features)
3. **ODZIEŻ** - Apparel (Warianty: Kolor, Rozmiar, Material)

**Sub-tasks:**
- ❌ 1.2.1 Create template config files (config/import_templates.php)
- ❌ 1.2.2 Seeder dla import_templates table (3 templates)
- ❌ 1.2.3 Template selection UI (dropdown w Import Wizard)

**Deliverables:**
└── 📁 PLIK: config/import_templates.php
└── 📁 PLIK: database/seeders/ImportTemplatesSeeder.php

---

## ❌ FAZA 2: IMPORT ENGINE SERVICE (5-6h)

**Agent:** laravel-expert
**Dependency:** FAZA 1 (templates exist), ETAP_07 FAZA 5.3 (VariantImportValidationService - optional shared)
**Status:** ❌ NOT STARTED

**Cel:** Parsowanie CSV, mapping, validation, import products

### 2.1 ImportEngine Service (4-5h)

**Lokalizacja:** `app/Services/Import/ImportEngine.php`

**Public Methods:**

```php
/**
 * Import products from CSV
 *
 * @param UploadedFile $file
 * @param array $mapping [csv_column => ppm_field, ...]
 * @param PrestaShopShop|null $shop Context dla compatibility
 * @return array [success_count, error_count, errors[]]
 */
public function importFromCsv(UploadedFile $file, array $mapping, ?PrestaShopShop $shop = null): array;

/**
 * Parse compatibility field
 *
 * Input: "YCF 50|YCF 88|Honda CRF50"
 * Output: [
 *   ['vehicle' => 'YCF 50', 'id' => 100],
 *   ['vehicle' => 'YCF 88', 'id' => 101],
 *   ['vehicle' => 'Honda CRF50', 'id' => 200],
 * ]
 */
protected function parseCompatibilityField(string $value, PrestaShopShop $shop): array;

/**
 * Import single row
 */
protected function importRow(array $row, array $mapping, PrestaShopShop $shop): bool;

/**
 * Validate row before import
 */
protected function validateRow(array $row, array $mapping): array; // errors[]
```

**Import Flow:**

```
1. Upload CSV file
2. Auto-detect columns (or user mapping UI)
3. Preview first 5 rows
4. User confirms mapping
5. Validation pass (all rows)
6. Import (chunked 100 rows per batch)
7. Progress bar (Livewire polling)
8. Summary report (X imported, Y errors)
```

**Key Features:**
- **Laravel Excel** integration (PhpSpreadsheet)
- **Chunked reading** (100 rows per chunk - memory efficient)
- **Compatibility parsing** (pipe-delimited → vehicle_compatibility records)
- **Feature parsing** (dynamic columns → product_features records)
- **Error aggregation** (collect ALL errors, not fail-fast)
- **Queue integration** (>100 rows → CsvImportJob)

**Sub-tasks:**
- ❌ 2.1.1 Create ImportEngine service class
- ❌ 2.1.2 Implement importFromCsv() method (Laravel Excel)
- ❌ 2.1.3 Implement auto-detect column mapping (smart detection)
- ❌ 2.1.4 Implement parseCompatibilityField() method
- ❌ 2.1.5 Implement importRow() method (create Product + relations)
- ❌ 2.1.6 Implement validateRow() method (SKU, required fields, formats)
- ❌ 2.1.7 Integration z VariantImportValidationService (if available from ETAP_07 FAZA 5.3)
- ❌ 2.1.8 Error reporting system (collect errors per row)
- ❌ 2.1.9 Unit tests (10 test cases)

**Deliverables:**
└── 📁 PLIK: app/Services/Import/ImportEngine.php (~300 linii)
└── 📁 PLIK: tests/Unit/Services/ImportEngineTest.php
└── 📁 PLIK: tests/Feature/CsvImportTest.php

---

### 2.2 Column Mapping System (1h)

**Status:** ❌ NOT STARTED

**Features:**
- **Auto-detection** - smart matching (SKU, Name, Price, Stock, etc.)
- **Manual override** - user can remap columns w UI
- **Save as template** - custom mappings saved to import_templates table
- **Template library** - reuse saved mappings dla repeated imports

**Sub-tasks:**
- ❌ 2.2.1 Implement column auto-detection algorithm
- ❌ 2.2.2 Mapping UI component (drag-drop or dropdown per column)
- ❌ 2.2.3 Save custom mapping as template
- ❌ 2.2.4 Load template mapping

**Deliverables:**
└── 📁 PLIK: app/Services/Import/ColumnMapper.php (~100 linii)
└── 📁 PLIK: app/Http/Livewire/Admin/Import/ColumnMappingStep.php

---

## ❌ FAZA 3: EXPORT ENGINE SERVICE (4-5h)

**Agent:** laravel-expert
**Dependency:** None
**Status:** ❌ NOT STARTED

**Cel:** Eksport produktów do CSV w różnych formatach

### 3.1 ExportEngine Service (3-4h)

**Lokalizacja:** `app/Services/Export/ExportEngine.php`

**Public Methods:**

```php
/**
 * Export products to CSV
 *
 * @param Collection<Product> $products
 * @param string $format 'prestashop' | 'human_readable'
 * @param PrestaShopShop|null $shop Context dla compatibility
 * @return string CSV content
 */
public function exportToCsv(Collection $products, string $format = 'human_readable', ?PrestaShopShop $shop = null): string;

/**
 * Export compatibility data only
 */
public function exportCompatibility(Collection $parts, PrestaShopShop $shop): string;

/**
 * Format compatibility dla human-readable CSV
 *
 * Output: "YCF 50 (Oryginał)|YCF 88 (Oryginał)|Honda CRF50 (Zamiennik)"
 */
protected function formatCompatibilityForCsv(Product $part, int $shopId): string;
```

**Format A: PrestaShop Compatible**

```csv
SKU,Name,Manufacturer,Price,Stock,Feature_Model_1,Feature_Model_2,Feature_Model_3
BRK-001,"Klocki",OEM,89.99,25,"YCF 50","YCF 88","Honda CRF50"
```

(Multiple columns dla multi-value features - PrestaShop wymaga osobnych kolumn)

**Format B: Human Readable**

```csv
SKU,Name,Manufacturer,Price,Stock,Model
BRK-001,"Klocki",OEM,89.99,25,"YCF 50|YCF 88|Honda CRF50"
```

(Pipe-delimited multi-values - easier to read/edit)

**Key Features:**
- **Laravel Excel** export (PhpSpreadsheet)
- **2 format types** (PrestaShop compatible vs human readable)
- **Compatibility formatting** (original/replacement indicators)
- **Feature values export** (all features per product)
- **Variant export** (include all variants per product)
- **Column selection** (user chooses which columns to export)
- **Filters** (shop, categories, has_variants, etc.)

**Sub-tasks:**
- ❌ 3.1.1 Create ExportEngine service class
- ❌ 3.1.2 Implement exportToCsv() method (Laravel Excel)
- ❌ 3.1.3 Implement PrestaShop format export (multiple columns dla multi-value)
- ❌ 3.1.4 Implement Human Readable format export (pipe-delimited)
- ❌ 3.1.5 Implement exportCompatibility() method
- ❌ 3.1.6 Implement formatCompatibilityForCsv() method
- ❌ 3.1.7 Column selection logic (user preferences)
- ❌ 3.1.8 Unit tests (6 test cases)

**Deliverables:**
└── 📁 PLIK: app/Services/Export/ExportEngine.php (~250 linii)
└── 📁 PLIK: tests/Unit/Services/ExportEngineTest.php
└── 📁 PLIK: tests/Feature/CsvExportTest.php

---

### 3.2 Export Templates (1h)

**Status:** ❌ NOT STARTED

**Features:**
- **Predefined export templates** (same 3 as import: POJAZDY, CZĘŚCI, ODZIEŻ)
- **Custom export templates** (user saves column selection + format preferences)
- **Template library** (reuse dla repeated exports)

**Sub-tasks:**
- ❌ 3.2.1 Create export_templates config
- ❌ 3.2.2 UI dla save custom export template
- ❌ 3.2.3 Template selection w Export Wizard

**Deliverables:**
└── 📁 PLIK: config/export_templates.php
└── 📁 PLIK: app/Http/Livewire/Admin/Export/TemplateSelectorStep.php

---

## ❌ FAZA 4: UI/UX IMPLEMENTATION (6-8h)

**Agent:** livewire-specialist + frontend-specialist
**Dependency:** FAZA 2, FAZA 3 (services exist)
**Status:** ❌ NOT STARTED

**Cel:** User-friendly import/export wizards w admin panel

### 4.1 Import Wizard (Livewire) (3-4h)

**Lokalizacja:** `app/Http/Livewire/Admin/Import/CsvImportWizard.php`

**Steps:**
1. **Upload** - file upload (CSV/XLSX)
2. **Mapping** - auto-detect + manual override + template selection
3. **Preview** - first 5 rows preview z mapped columns
4. **Execute** - start import + progress bar

**Features:**
- **File validation** (max 10MB, CSV/XLSX only)
- **Template selection** (POJAZDY, CZĘŚCI, ODZIEŻ, or custom)
- **Column mapping UI** (dropdown per column or drag-drop)
- **Preview table** (5 rows with mapped data)
- **Error display** (validation errors per row)
- **Progress bar** (Livewire polling dla queue jobs)
- **Summary** (X imported, Y errors, download error report)

**Sub-tasks:**
- ❌ 4.1.1 Create CsvImportWizard Livewire component
- ❌ 4.1.2 Step 1: File upload form + validation
- ❌ 4.1.3 Step 2: Column mapping UI (auto-detect + manual)
- ❌ 4.1.4 Step 3: Preview table (first 5 rows)
- ❌ 4.1.5 Step 4: Execute import + progress tracking
- ❌ 4.1.6 Error reporting UI (download CSV z errors)
- ❌ 4.1.7 Frontend verification (screenshots) - MANDATORY

**Deliverables:**
└── 📁 PLIK: app/Http/Livewire/Admin/Import/CsvImportWizard.php (~200 linii)
└── 📁 PLIK: resources/views/livewire/admin/import/csv-import-wizard.blade.php
└── 📁 PLIK: resources/css/admin/import-wizard.css

---

### 4.2 Export Wizard (Livewire) (3-4h)

**Lokalizacja:** `app/Http/Livewire/Admin/Export/CsvExportWizard.php`

**Steps:**
1. **Filters** - shop, categories, has_variants, date range
2. **Columns** - select which columns to export + format type
3. **Template** - choose predefined or custom template
4. **Execute** - generate CSV + download

**Features:**
- **Filter UI** (shop selector, category picker, checkboxes)
- **Column selection** (checkbox list of all available columns)
- **Format selector** (PrestaShop Compatible vs Human Readable)
- **Template management** (save current selection as template)
- **Preview** (row count estimate before export)
- **Download** (CSV file generation + auto-download)

**Sub-tasks:**
- ❌ 4.2.1 Create CsvExportWizard Livewire component
- ❌ 4.2.2 Step 1: Filter form (shop, categories, etc.)
- ❌ 4.2.3 Step 2: Column selection UI (checkboxes)
- ❌ 4.2.4 Step 3: Template selection/save
- ❌ 4.2.5 Step 4: Execute export + download
- ❌ 4.2.6 Format selector (PrestaShop vs Human Readable)
- ❌ 4.2.7 Frontend verification (screenshots) - MANDATORY

**Deliverables:**
└── 📁 PLIK: app/Http/Livewire/Admin/Export/CsvExportWizard.php (~180 linii)
└── 📁 PLIK: resources/views/livewire/admin/export/csv-export-wizard.blade.php
└── 📁 PLIK: resources/css/admin/export-wizard.css

---

## ❌ FAZA 5: QUEUE JOBS IMPLEMENTATION (3-4h)

**Agent:** laravel-expert
**Dependency:** FAZA 2, FAZA 3 (services exist)
**Status:** ❌ NOT STARTED

**Cel:** Background processing dla dużych plików (>100 rows)

### 5.1 CsvImportJob (2-3h)

**Lokalizacja:** `app/Jobs/CsvImportJob.php`

**Features:**
- **ShouldQueue** interface
- **Chunked processing** (100 rows per iteration)
- **Progress tracking** (update job progress in DB)
- **Error handling** (collect errors, log, notify user)
- **Retry logic** (3 attempts with exponential backoff)

**Sub-tasks:**
- ❌ 5.1.1 Create CsvImportJob class (ShouldQueue)
- ❌ 5.1.2 Implement handle() method (call ImportEngine)
- ❌ 5.1.3 Implement progress tracking (update import_batches table)
- ❌ 5.1.4 Implement failed() method (error notification)
- ❌ 5.1.5 Unit tests (success, failure, retry scenarios)

**Deliverables:**
└── 📁 PLIK: app/Jobs/CsvImportJob.php (~120 linii)
└── 📁 PLIK: tests/Unit/Jobs/CsvImportJobTest.php

---

### 5.2 CsvExportJob (1-2h)

**Lokalizacja:** `app/Jobs/CsvExportJob.php`

**Features:**
- **ShouldQueue** interface (dla >1000 products)
- **Chunked export** (500 products per chunk)
- **File storage** (store w storage/app/exports/)
- **Download notification** (email with download link)

**Sub-tasks:**
- ❌ 5.2.1 Create CsvExportJob class (ShouldQueue)
- ❌ 5.2.2 Implement handle() method (call ExportEngine)
- ❌ 5.2.3 Implement progress tracking (update export_batches table)
- ❌ 5.2.4 Implement file storage + cleanup (7-day retention)
- ❌ 5.2.5 Unit tests (4 test cases)

**Deliverables:**
└── 📁 PLIK: app/Jobs/CsvExportJob.php (~100 linii)
└── 📁 PLIK: tests/Unit/Jobs/CsvExportJobTest.php

---

## ❌ FAZA 5: TESTY INTEGRACYJNE (3-4h)

**Agent:** debugger + laravel-expert
**Dependency:** FAZA 1-4 (services + UI deployed)
**Status:** ❌ NOT STARTED

**Cel:** End-to-end testing z prawdziwymi plikami XLSX + database assertions

### 5.1 Integration Test Suite (3-4h)

**Zakres:** End-to-end testing z RefreshDatabase + prawdziwe pliki XLSX + DB assertions

**Testy do utworzenia:**

#### Test 1: ImportBatchTest.php (1h)
**Scope:** Import flow end-to-end
- ✅ Create batch record
- ✅ Process XLSX file
- ✅ Verify products created in database
- ✅ Check conflict logs
- ✅ Verify status transitions (pending → processing → completed)
- ✅ Test rollback on validation failure

#### Test 2: ExportBatchTest.php (1h)
**Scope:** Export flow end-to-end
- ✅ Export products to XLSX
- ✅ Verify file structure (columns, headers)
- ✅ Check filters applied (shop, categories)
- ✅ Validate data accuracy (SKU, Name, Price, Stock)
- ✅ Test PrestaShop Compatible format
- ✅ Test Human Readable format

#### Test 3: ConflictResolutionTest.php (0.5h)
**Scope:** Duplicate SKU handling
- ✅ Import product with existing SKU
- ✅ Verify conflict logged in conflict_logs
- ✅ Test resolution strategies:
  - use_new (replace existing)
  - use_existing (skip import)
  - merge (update existing with new data)

#### Test 4: ValidationTest.php (0.5h)
**Scope:** Data validation
- ✅ Invalid XLSX structure (missing columns)
- ✅ Missing required fields (SKU, Name)
- ✅ Data type mismatches (Price not numeric, Stock not integer)
- ✅ Verify error reporting (per-row errors)

### Approach

**Testing Framework:**
- **RefreshDatabase trait** - fresh DB for each test
- **Real XLSX files** - stored in tests/Fixtures/
- **Database assertions** - verify correct data stored
- **File assertions** - verify exported file structure

**Test Fixtures:**
```
tests/Fixtures/
├── valid_products.xlsx (10 products, all valid)
├── invalid_products.xlsx (5 products, validation errors)
├── duplicate_sku_products.xlsx (3 products, 1 duplicate)
└── large_file.xlsx (500 products, queue job test)
```

**Example Test Case:**
```php
public function test_import_valid_products_creates_records()
{
    // Arrange
    $file = new UploadedFile(
        tests_path('Fixtures/valid_products.xlsx'),
        'valid_products.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    // Act
    $batch = ImportBatch::factory()->create();
    $service = app(ImportEngine::class);
    $result = $service->importFromCsv($file, $this->getDefaultMapping(), null);

    // Assert
    $this->assertEquals(10, $result['success_count']);
    $this->assertEquals(0, $result['error_count']);
    $this->assertDatabaseCount('products', 10);
    $this->assertDatabaseHas('products', ['sku' => 'BRK-001']);
}
```

**Sub-tasks:**
- ❌ 5.1.1 Create test fixtures (4 XLSX files)
- ❌ 5.1.2 Implement ImportBatchTest.php (10 test methods)
- ❌ 5.1.3 Implement ExportBatchTest.php (8 test methods)
- ❌ 5.1.4 Implement ConflictResolutionTest.php (5 test methods)
- ❌ 5.1.5 Implement ValidationTest.php (6 test methods)
- ❌ 5.1.6 Run all tests + verify 100% pass rate

**Deliverables:**
└── 📁 PLIK: tests/Fixtures/ (4 XLSX files)
└── 📁 PLIK: tests/Feature/Import/ImportBatchTest.php (~300 linii)
└── 📁 PLIK: tests/Feature/Export/ExportBatchTest.php (~250 linii)
└── 📁 PLIK: tests/Feature/Import/ConflictResolutionTest.php (~150 linii)
└── 📁 PLIK: tests/Feature/Import/ValidationTest.php (~180 linii)

**Success Criteria:**
- ✅ All 29 test methods pass (100% success rate)
- ✅ No database pollution (RefreshDatabase works correctly)
- ✅ Real XLSX files processed without errors
- ✅ All business logic scenarios covered

**Note:** Tests będą utworzone DOPIERO gdy features będą wdrożone (zgodnie z project rule: "only tests for DEPLOYED features")

---

## 📊 ESTIMATED TOTAL EFFORT

| Faza | Czas szacowany | Priorytet | Komponenty |
|------|----------------|-----------|------------|
| **FAZA 1** | 3-4h | 🟡 HIGH | TemplateGenerator, Predefined Templates |
| **FAZA 2** | 5-6h | 🔴 CRITICAL | ImportEngine, ColumnMapper |
| **FAZA 3** | 4-5h | 🟡 HIGH | ExportEngine, Export Templates |
| **FAZA 4** | 6-8h | 🔴 CRITICAL | Import Wizard, Export Wizard |
| **FAZA 5** | 3-4h | 🟡 HIGH | CsvImportJob, CsvExportJob |

**TOTAL:** 21-27h (średnio 24h - 4-5 dni roboczych)

---

## 🎯 SUCCESS CRITERIA

**ETAP_08 zostanie uznany za ukończony gdy:**

### ✅ Functional Requirements

1. **Import Functionality:**
   - ✅ User może zaimportować produkty z CSV/XLSX (warianty + cechy + dopasowania)
   - ✅ Auto-detection column mapping działa (smart matching)
   - ✅ Manual override column mapping działa
   - ✅ Template selection działa (POJAZDY, CZĘŚCI, ODZIEŻ)
   - ✅ Compatibility field parsing działa (pipe-delimited → vehicle_compatibility)
   - ✅ Validation errors displayed w UI (per row)
   - ✅ Large files (>100 rows) processed przez queue jobs

2. **Export Functionality:**
   - ✅ User może wyeksportować produkty do CSV/XLSX
   - ✅ PrestaShop Compatible format działa
   - ✅ Human Readable format działa
   - ✅ Column selection działa (user chooses columns)
   - ✅ Filters działają (shop, categories, has_variants)
   - ✅ Export templates saved and reused

3. **UI/UX:**
   - ✅ Import Wizard 4 steps functional
   - ✅ Export Wizard 4 steps functional
   - ✅ Progress bars dla queue jobs (Livewire polling)
   - ✅ Summary notifications (X imported/exported, Y errors)
   - ✅ Error report download (CSV z błędami)

### ✅ Technical Requirements

4. **Code Quality:**
   - ✅ Wszystkie komponenty deployed na produkcję
   - ✅ Zero errors w Laravel logs
   - ✅ Code follows Laravel 12.x best practices (Context7 verified)
   - ✅ Laravel Excel integration correct
   - ✅ NO hardcoded values, NO mock data

5. **Testing:**
   - ✅ Unit tests pass (services, jobs)
   - ✅ Feature tests pass (E2E import/export flows)
   - ✅ Edge cases handled (invalid CSV, missing columns, large files)
   - ✅ Manual UI testing completed

6. **Performance:**
   - ✅ Import 1000 rows completes in <5 min
   - ✅ Export 1000 products completes in <3 min
   - ✅ Memory efficient (chunked processing)
   - ✅ Queue system operational

7. **Documentation:**
   - ✅ ETAP_08 plan updated (wszystkie fazy marked ✅)
   - ✅ File paths dodane do planu
   - ✅ User guide created (import/export workflows)
   - ✅ Code documentation (PHPDoc comments)

### ✅ User Acceptance

8. **User Satisfaction:**
   - ✅ User confirmed: "CSV import działa idealnie"
   - ✅ User confirmed: "CSV export działa jak należy"
   - ✅ User confirmed: "Wszystkie requirements spełnione"

---

## 🚀 DEPLOYMENT CHECKLIST

**Pre-Deployment:**
- [ ] All unit tests passing (35+ test cases)
- [ ] All feature tests passing (8+ E2E scenarios)
- [ ] Frontend verification completed (screenshots)
- [ ] Code review by coding-style-agent (MANDATORY)
- [ ] Database backup created

**Deployment:**
- [ ] Deploy migrations (import_templates, export_templates tables if needed)
- [ ] Deploy services (TemplateGenerator, ImportEngine, ExportEngine, ColumnMapper)
- [ ] Deploy Livewire components (CsvImportWizard, CsvExportWizard)
- [ ] Deploy queue jobs (CsvImportJob, CsvExportJob)
- [ ] Deploy Blade views (import/export wizards)
- [ ] Deploy CSS (admin/import-wizard.css, admin/export-wizard.css)
- [ ] Run seeders (ImportTemplatesSeeder)
- [ ] Clear cache (artisan cache:clear, config:clear, view:clear)
- [ ] Verify routes (/admin/import/csv, /admin/export/csv)

**Post-Deployment:**
- [ ] Test import (upload CSV → verify products created)
- [ ] Test export (generate CSV → verify data accuracy)
- [ ] Test queue jobs (import >100 rows → verify background processing)
- [ ] Test error handling (invalid CSV → verify error display)
- [ ] Monitor Laravel logs (check for errors)
- [ ] User acceptance testing (real-world scenarios)

---

## 📚 CROSS-REFERENCES

**Related Plans:**
- **ETAP_05a SEKCJA 0-4**: Database schema, models, services (dependency)
- **ETAP_07 FAZA 5.3**: VariantImportValidationService (shared validation layer)
- **ETAP_07**: PrestaShop API integration (separate track)

**Documentation:**
- `_DOCS/CSV_IMPORT_EXPORT_GUIDE.md` (to be created w FAZA 4)
- `_DOCS/COLUMN_MAPPING_REFERENCE.md` (to be created w FAZA 2)
- `config/import_templates.php` (template definitions)

**Agent Reports:**
- `_AGENT_REPORTS/laravel_expert_etap08_implementation_YYYY-MM-DD.md` (to be created)
- `_AGENT_REPORTS/livewire_specialist_etap08_wizards_YYYY-MM-DD.md` (to be created)

---

## 🎯 PRIORITIZATION & TIMELINE

**Recommended Implementation Order:**

**Week 1 (FAZA 1-2):**
1. FAZA 1: Template Generator (3-4h) - Day 1
2. FAZA 2: Import Engine (5-6h) - Day 1-2

**Week 2 (FAZA 3-5):**
3. FAZA 3: Export Engine (4-5h) - Day 3
4. FAZA 4: UI/UX Wizards (6-8h) - Day 3-4
5. FAZA 5: Queue Jobs (3-4h) - Day 4-5

**Testing & Deployment:**
6. Testing (4-5h) - Day 5
7. Deployment (2-3h) - Day 5

---

**Ostatnia aktualizacja:** 2025-11-04 (wydzielenie z ETAP_05a SEKCJA 5)
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński
**Następny krok:** Po ukończeniu ETAP_07 FAZA 5 (System Wariantów)
**Status dependency:** ETAP_07 ma priorytet (PrestaShop integration), ETAP_08 może być równolegle po FAZA 5.3
