# CSV IMPORT/EXPORT SYSTEM - TESTING CHECKLIST

**Created:** 2025-10-20
**Agent:** frontend-specialist
**Module:** FAZA 6 - CSV Import/Export System

---

## PREREQUISITES

### Environment Setup
- [ ] Local development environment running (php artisan serve)
- [ ] Database accessible and migrations run
- [ ] Test products exist in database (minimum 3 products with SKUs)
- [ ] Composer dependencies installed (maatwebsite/excel, phpoffice/phpspreadsheet)
- [ ] Storage directory writable (`storage/app/temp` exists with 755 permissions)

### Test Data Preparation
- [ ] Create test product with variants (Product ID: ___)
- [ ] Create test product with features (Product ID: ___)
- [ ] Create test product with compatibility (Product ID: ___)
- [ ] Prepare test CSV files with valid data
- [ ] Prepare test CSV files with INVALID data (for error testing)

---

## A) TEMPLATE DOWNLOAD TESTING

### Test A1: Variants Template Download
**URL:** `/admin/csv/templates/variants`

- [ ] Navigate to template download URL
- [ ] File downloads successfully (`szablon_variants_YYYY-MM-DD.csv`)
- [ ] Open in Excel - no encoding errors (Polish characters OK)
- [ ] Verify headers present: SKU, Parent SKU, Attribute columns, Price columns, Stock columns
- [ ] Verify 3 example rows populated
- [ ] Column descriptions readable (Polish)
- [ ] UTF-8 BOM present (Excel compatibility)

**Expected Result:** Valid CSV with Polish headers + 3 example rows

---

### Test A2: Features Template Download
**URL:** `/admin/csv/templates/features`

- [ ] Navigate to template download URL
- [ ] File downloads successfully (`szablon_features_YYYY-MM-DD.csv`)
- [ ] Open in Excel - Polish characters display correctly
- [ ] Verify headers: SKU, Feature Type columns (dynamic based on DB)
- [ ] Verify 3 example rows
- [ ] Boolean values shown as TAK/NIE (not 1/0)

**Expected Result:** Valid features template with dynamic columns

---

### Test A3: Compatibility Template Download
**URL:** `/admin/csv/templates/compatibility`

- [ ] Navigate to template download URL
- [ ] File downloads successfully (`szablon_compatibility_YYYY-MM-DD.csv`)
- [ ] Verify headers: SKU, Brand, Model, Year From, Year To, Engine Type, etc.
- [ ] Verify 3 example rows with realistic data
- [ ] Year range validation visible in examples

**Expected Result:** Valid compatibility template

---

## B) IMPORT FLOW TESTING

### Test B1: Upload Valid CSV (Variants)
**URL:** `/admin/csv/import/variants`

**Preparation:**
1. Download variants template
2. Fill 10 rows with VALID data:
   - Parent SKU exists in products table
   - Variant SKUs unique
   - Numeric prices
   - Valid attribute types
   - Valid price group names
   - Valid warehouse names

**Test Steps:**
- [ ] Navigate to `/admin/csv/import/variants`
- [ ] See upload dropzone with MPP TRADE styling
- [ ] Click "Wybierz plik" or drag & drop CSV
- [ ] Upload progress indicator appears
- [ ] File parses successfully

**Expected Result:** Redirects to preview step

---

### Test B2: Column Auto-Detection
**After successful upload (B1)**

- [ ] "Mapowanie kolumn" table visible
- [ ] CSV columns correctly mapped to DB fields (SKU → product_sku, etc.)
- [ ] Example values displayed (first row preview)
- [ ] All required columns detected
- [ ] No missing column warnings

**Expected Result:** Correct auto-mapping of all columns

---

### Test B3: Data Preview (10 rows)
**After column mapping**

- [ ] "Podgląd danych" table visible
- [ ] First 10 rows displayed with row numbers (2-11)
- [ ] Status column shows "OK" badges (green)
- [ ] Data truncated for long fields (max-w-xs)
- [ ] Scrollable table (overflow-x-auto)

**Expected Result:** Clean preview of first 10 rows

---

### Test B4: Validation (All Valid)
**With valid data**

- [ ] Statistics cards display: Total 10, Valid 10, Errors 0, Conflicts 0
- [ ] No validation errors section visible
- [ ] "Wykonaj import" button ENABLED
- [ ] Button shows row count (10 wierszy)

**Expected Result:** No errors, import button enabled

---

### Test B5: Upload CSV with Errors
**Preparation:**
1. Create CSV with 10 rows (5 valid, 5 invalid):
   - Row 2: Missing SKU
   - Row 4: Invalid price ("abc" instead of number)
   - Row 6: Parent SKU not exists
   - Row 8: Duplicate variant SKU
   - Row 10: Invalid warehouse name

**Test Steps:**
- [ ] Upload error CSV file
- [ ] File parses and reaches preview

**Validation Section:**
- [ ] Statistics show: Total 10, Valid 5, Errors 5
- [ ] "Błędy walidacji (5)" section visible
- [ ] Errors grouped by row number
- [ ] Error messages in Polish
- [ ] Specific field errors shown:
   - "Wiersz 2: Pole SKU jest wymagane"
   - "Wiersz 4: Price must be numeric"
   - "Wiersz 6: Parent SKU not found in database"
   - etc.
- [ ] "Pobierz raport błędów" button visible
- [ ] "Wykonaj import" button DISABLED (disabled:opacity-50)

**Expected Result:** Detailed error list, import blocked

---

### Test B6: Error Report Download
**After validation errors (B5)**

- [ ] Click "Pobierz raport błędów"
- [ ] CSV file downloads (`import_errors_YYYY-MM-DD_HH-MM-SS.csv`)
- [ ] Open file - contains:
   - Header row: Wiersz | Kolumna | Błąd
   - All 5 errors listed with row/column/message
   - Polish encoding correct

**Expected Result:** Downloadable error report CSV

---

### Test B7: Conflict Detection (Duplicate SKUs)
**Preparation:**
1. Create CSV with variant SKU that ALREADY EXISTS in DB
2. Upload file

**Conflict Resolution Section:**
- [ ] Statistics show: Conflicts > 0
- [ ] "Rozwiązywanie konfliktów" section visible
- [ ] 3 radio options displayed:
   - [ ] "Pomiń - Nie importuj duplikatów"
   - [ ] "Nadpisz - Zastąp istniejące dane nowymi wartościami"
   - [ ] "Aktualizuj zmiany - Aktualizuj tylko pola które się różnią"
- [ ] Default selection: "Pomiń"
- [ ] List of conflicts shown (variant SKU + details)

**Test Conflict Resolution:**
- [ ] Select "Nadpisz" → verify selection saved (wire:model)
- [ ] Select "Aktualizuj zmiany" → verify selection saved

**Expected Result:** Conflict resolution UI functional

---

### Test B8: Execute Import (Valid Data)
**Preparation:** Upload valid 10-row CSV

**Test Steps:**
- [ ] Click "Wykonaj import (10 wierszy)"
- [ ] Button disabled during processing (wire:loading)
- [ ] "Import w trakcie..." screen appears with:
   - [ ] Spinning loader animation
   - [ ] Progress bar (50% width animation)
   - [ ] "Przetwarzanie 10 wierszy..." message
- [ ] Wait for completion

**Completion Screen:**
- [ ] Green checkmark icon displayed
- [ ] "Import zakończony pomyślnie!" message
- [ ] Summary shows: Pomyślne 10, Błędy 0, Całkowite 10
- [ ] "Importuj kolejny plik" button visible
- [ ] "Powrót do panelu" button visible

**Database Verification:**
- [ ] Query `product_variants` table → 10 new rows exist
- [ ] Query `variant_attributes` table → attributes created
- [ ] Query `variant_prices` table → prices created
- [ ] Query `variant_stock` table → stock created
- [ ] All SKUs match uploaded CSV

**Expected Result:** Successful import with DB records created

---

### Test B9: Batch Processing (Large File)
**Preparation:**
1. Create CSV with 250 rows (test batch size of 100)

**Test Steps:**
- [ ] Upload 250-row CSV
- [ ] Validation passes
- [ ] Execute import
- [ ] Monitor processing (should process in 3 batches: 100, 100, 50)
- [ ] Completion shows 250 successful

**Database Verification:**
- [ ] All 250 rows imported correctly
- [ ] No duplicates or missing records
- [ ] Transaction integrity maintained

**Expected Result:** Batch processing works (100 rows/batch)

---

## C) EXPORT FLOW TESTING

### Test C1: Export Single Product Variants
**Preparation:** Use product with 5 variants

**Test Steps:**
- [ ] Navigate to `/admin/products/{product_id}/export/variants`
- [ ] XLSX file downloads (`warianty_{SKU}_YYYY-MM-DD.xlsx`)
- [ ] Open in Excel

**File Verification:**
- [ ] Sheet name: "Warianty"
- [ ] Header row with Polish column names
- [ ] 5 data rows (matching variants count)
- [ ] Data formatted correctly:
   - [ ] Decimal prices: `123,45 zł` (comma separator, currency)
   - [ ] Boolean: `TAK/NIE` (not 1/0)
   - [ ] Dates: `Y-m-d` format (2025-10-20)
- [ ] All variant attributes present
- [ ] Price groups correct
- [ ] Stock levels correct

**Expected Result:** Correctly formatted XLSX export

---

### Test C2: Export Single Product Features
**Preparation:** Use product with features

**Test Steps:**
- [ ] Navigate to `/admin/products/{product_id}/export/features`
- [ ] XLSX file downloads (`cechy_{SKU}_YYYY-MM-DD.xlsx`)
- [ ] Open in Excel

**File Verification:**
- [ ] Sheet name: "Cechy"
- [ ] Header row with feature type names (dynamic columns)
- [ ] 1 data row (product features as single row)
- [ ] Feature values correctly exported

**Expected Result:** Features export with dynamic columns

---

### Test C3: Export Single Product Compatibility
**Preparation:** Use product with 10 compatibility records

**Test Steps:**
- [ ] Navigate to `/admin/products/{product_id}/export/compatibility`
- [ ] XLSX file downloads (`dopasowania_{SKU}_YYYY-MM-DD.xlsx`)
- [ ] Open in Excel

**File Verification:**
- [ ] Sheet name: "Dopasowania"
- [ ] 10 data rows (matching compatibility count)
- [ ] Vehicle models correct (Brand + Model + Year)
- [ ] Compatibility attributes present
- [ ] Source tracking visible

**Expected Result:** Complete compatibility export

---

### Test C4: Multi-Sheet Export (Multiple Products)
**Preparation:** Select 3 products with variants, features, and compatibility

**Test Steps:**
- [ ] POST to `/admin/csv/export/multiple` with:
   ```json
   {
     "product_ids": [1, 2, 3],
     "include_variants": true,
     "include_features": true,
     "include_compatibility": true
   }
   ```
- [ ] XLSX file downloads (`eksport_produktow_YYYY-MM-DD.xlsx`)
- [ ] Open in Excel

**File Verification:**
- [ ] 3 sheets present: "Warianty", "Cechy", "Dopasowania"
- [ ] Each sheet has correct data from all 3 products
- [ ] No data mixing between products
- [ ] Totals match database counts

**Expected Result:** Multi-sheet export with correct data separation

---

### Test C5: Large Export with ZIP Compression
**Preparation:** Create export scenario with >1000 rows

**Test Steps:**
- [ ] Export all products with variants (>1000 total rows)
- [ ] ZIP file downloads (not raw XLSX)
- [ ] Extract ZIP → contains XLSX file
- [ ] Open XLSX → all data present

**Expected Result:** ZIP compression triggers for large exports

---

## D) ERROR HANDLING & EDGE CASES

### Test D1: Invalid File Upload
**Test Steps:**
- [ ] Upload .txt file (not CSV/XLSX)
- [ ] Error message: "Dozwolone formaty: CSV, TXT, XLSX"
- [ ] Upload 15MB file (exceeds 10MB limit)
- [ ] Error message: "Maksymalny rozmiar pliku to 10MB"

**Expected Result:** Validation prevents invalid files

---

### Test D2: Missing Required Columns
**Preparation:**
1. Create CSV without "SKU" column

**Test Steps:**
- [ ] Upload CSV
- [ ] Error flash message: "Brakujące wymagane kolumny: SKU"
- [ ] Stays on upload step (does not proceed to preview)

**Expected Result:** Missing column detection works

---

### Test D3: Empty CSV File
**Preparation:**
1. Create CSV with only header row (no data)

**Test Steps:**
- [ ] Upload empty CSV
- [ ] Error message or stays on upload with warning
- [ ] Does not crash

**Expected Result:** Graceful handling of empty file

---

### Test D4: Malformed CSV (Encoding Issues)
**Preparation:**
1. Create CSV with incorrect encoding (Windows-1252 instead of UTF-8)

**Test Steps:**
- [ ] Upload malformed CSV
- [ ] Polish characters may display incorrectly BUT no crash
- [ ] Error message suggests checking encoding

**Expected Result:** No crash, encoding hint provided

---

### Test D5: Database Transaction Rollback
**Preparation:**
1. Create CSV with 150 rows
2. Introduce error in row 125 (will cause batch rollback)

**Test Steps:**
- [ ] Upload CSV
- [ ] Execute import
- [ ] Import fails on batch 2 (rows 101-125)

**Database Verification:**
- [ ] Query database → batch 1 (rows 1-100) committed
- [ ] Batch 2 (rows 101-125) rolled back (NOT in database)
- [ ] No partial data from failed batch

**Expected Result:** Transaction rollback on batch failure

---

### Test D6: Concurrent Imports
**Test Steps:**
- [ ] Open 2 browser tabs
- [ ] Upload CSV in tab 1
- [ ] Upload CSV in tab 2 simultaneously
- [ ] Both imports complete independently

**Database Verification:**
- [ ] No data conflicts
- [ ] Both imports recorded separately

**Expected Result:** Thread-safe concurrent imports

---

## E) UI/UX TESTING

### Test E1: Responsive Design (Mobile)
**Test Steps:**
- [ ] Open import page on mobile (or resize browser to 375px width)
- [ ] Dropzone visible and usable
- [ ] Tables scroll horizontally (overflow-x-auto)
- [ ] Buttons stack vertically
- [ ] Statistics cards stack (grid-cols-1 on mobile)
- [ ] MPP TRADE styling intact (gold accents)

**Expected Result:** Fully responsive on mobile

---

### Test E2: Loading States
**Test Steps:**
- [ ] Upload file → verify "Przetwarzanie pliku..." spinner shows
- [ ] Execute import → verify "Importowanie..." text replaces button text
- [ ] All wire:loading states functional

**Expected Result:** Clear loading feedback

---

### Test E3: Dark Mode Gradient Background
**Visual Verification:**
- [ ] Page has dark gradient background (from-gray-900 via-gray-800 to-black)
- [ ] Animated background elements visible (gold pulse effects)
- [ ] MPP TRADE brand color (#e0ac7e) used consistently
- [ ] Buttons have gold gradients on hover
- [ ] Text readable on dark background

**Expected Result:** MPP TRADE design system followed

---

### Test E4: Step Indicator Navigation
**Test Steps:**
- [ ] Upload step → Step 1 is gold (#e0ac7e), Steps 2-3 gray
- [ ] Preview step → Steps 1-2 green checkmark, Step 3 gray
- [ ] Processing step → Steps 1-2 checkmark, Step 3 gold
- [ ] Complete step → All 3 steps green checkmark

**Expected Result:** Visual progress tracking works

---

### Test E5: Flash Messages
**Test Steps:**
- [ ] Trigger success → green message banner appears
- [ ] Trigger error → red message banner appears
- [ ] Messages auto-dismiss or closeable

**Expected Result:** Consistent flash message styling

---

## F) PERFORMANCE TESTING

### Test F1: Import Performance (1000 rows)
**Test Steps:**
- [ ] Upload CSV with 1000 rows
- [ ] Start timer
- [ ] Execute import
- [ ] Record time to completion

**Acceptance Criteria:**
- [ ] Completes in < 60 seconds (1000 rows)
- [ ] No memory errors
- [ ] No timeout errors

**Expected Result:** Handles 1000 rows efficiently

---

### Test F2: Memory Usage (Large File)
**Test Steps:**
- [ ] Monitor server memory before import
- [ ] Upload 5000-row CSV
- [ ] Execute import
- [ ] Monitor memory during processing

**Acceptance Criteria:**
- [ ] Memory usage stays < 256MB
- [ ] No out-of-memory errors
- [ ] Stream parsing works (not loading entire file to memory)

**Expected Result:** Efficient memory management

---

## G) INTEGRATION TESTING

### Test G1: Product Service Integration
**Verify imports interact correctly with existing services:**
- [ ] Import creates variants via `VariantManager` service (not direct DB)
- [ ] Features created via `FeatureManager`
- [ ] Compatibility via `CompatibilityManager`
- [ ] All business logic respected

**Expected Result:** Services properly integrated

---

### Test G2: Livewire 3.x File Uploads
**Verify Livewire file upload patterns:**
- [ ] `WithFileUploads` trait used
- [ ] `wire:model` on file input
- [ ] `updatedCsvFile()` lifecycle hook fires
- [ ] Temporary files cleaned up after processing

**Expected Result:** Livewire 3.x best practices followed

---

### Test G3: Alpine.js Drag & Drop
**Verify Alpine.js interactivity:**
- [ ] `x-data="{ dragging: false }"` initializes
- [ ] `@dragover.prevent` changes dropzone styling
- [ ] `@drop.prevent` triggers file input
- [ ] No JavaScript errors in console

**Expected Result:** Alpine.js drag & drop functional

---

## COMPLETION CRITERIA

### All Tests Must Pass:
- [ ] A1-A3: Template downloads (3/3)
- [ ] B1-B9: Import flow (9/9)
- [ ] C1-C5: Export flow (5/5)
- [ ] D1-D6: Error handling (6/6)
- [ ] E1-E5: UI/UX (5/5)
- [ ] F1-F2: Performance (2/2)
- [ ] G1-G3: Integration (3/3)

**Total:** 33 test scenarios

### Sign-Off:
- [ ] All critical bugs fixed
- [ ] User acceptance testing completed
- [ ] Documentation updated
- [ ] Ready for production deployment

---

**Tester Name:** _______________
**Date:** _______________
**Signature:** _______________
