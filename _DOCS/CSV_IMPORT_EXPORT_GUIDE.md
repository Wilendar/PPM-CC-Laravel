# CSV IMPORT/EXPORT SYSTEM - USER GUIDE

**Created:** 2025-10-20
**Module:** FAZA 6 - CSV Import/Export System
**Version:** 1.0

---

## TABLE OF CONTENTS

1. [Overview](#overview)
2. [Accessing CSV Tools](#accessing-csv-tools)
3. [CSV Format Specification](#csv-format-specification)
4. [Template Download](#template-download)
5. [Import Workflow](#import-workflow)
6. [Export Workflow](#export-workflow)
7. [Error Handling](#error-handling)
8. [Conflict Resolution](#conflict-resolution)
9. [Bulk Operations Tips](#bulk-operations-tips)
10. [Troubleshooting](#troubleshooting)

---

## OVERVIEW

System CSV Import/Export umożliwia masowe zarządzanie:
- **Wariantami produktów** (rozmiary, kolory, atrybuty)
- **Cechami produktów** (właściwości techniczne)
- **Dopasowaniami pojazdów** (kompatybilność z modelami aut)

### Key Features:
✅ **SKU-first architecture** - SKU jest głównym identyfikatorem
✅ **Polish localization** - nagłówki i komunikaty po polsku
✅ **Auto-column detection** - automatyczne mapowanie kolumn
✅ **Validation before import** - wykrywanie błędów przed zapisem do DB
✅ **Conflict resolution** - obsługa duplikatów (pomiń/nadpisz/aktualizuj)
✅ **Batch processing** - wydajne przetwarzanie dużych plików (100 rows/batch)
✅ **Multi-sheet Excel** - eksport do XLSX z wieloma arkuszami

---

## ACCESSING CSV TOOLS

### Admin Navigation
1. Zaloguj się jako **Admin** lub **Manager**
2. Nawiguj do `/admin/csv/import`
3. Lub kliknij link "Import CSV" w admin dashboard

### Direct URLs:
- **Import:** `https://ppm.mpptrade.pl/admin/csv/import/{type}`
  - Types: `variants`, `features`, `compatibility`
- **Templates:** `https://ppm.mpptrade.pl/admin/csv/templates/{type}`

---

## CSV FORMAT SPECIFICATION

### General Rules:
- **Encoding:** UTF-8 with BOM (dla kompatybilności z Excel)
- **Delimiter:** `;` (średnik) - standard dla polskiej wersji Excel
- **Line breaks:** Windows (CRLF) lub Unix (LF)
- **Max file size:** 10MB
- **Supported formats:** `.csv`, `.xlsx`

### Polish Localization:
| Type | Polish Format | Notes |
|------|---------------|-------|
| **Boolean** | `TAK` / `NIE` | NIE używaj `1/0` lub `true/false` |
| **Decimal** | `123,45` | Przecinek jako separator dziesiętny |
| **Currency** | `123,45 zł` | Z symbolem waluty (opcjonalnie) |
| **Date** | `2025-10-20` | Format: Y-m-d (ISO 8601) |

---

## TEMPLATE DOWNLOAD

### Step 1: Wybierz typ szablonu

Dostępne szablony:
1. **Warianty** (`variants`) - dla produktów z różnymi wariantami (rozmiar, kolor)
2. **Cechy** (`features`) - właściwości techniczne produktów
3. **Dopasowania** (`compatibility`) - kompatybilność z pojazdami

### Step 2: Pobierz szablon

**Metoda A: Strona Import**
1. Przejdź do `/admin/csv/import`
2. Kliknij odpowiedni przycisk:
   - "Warianty" (niebieski)
   - "Cechy" (zielony)
   - "Dopasowania" (fioletowy)

**Metoda B: Direct URL**
```
https://ppm.mpptrade.pl/admin/csv/templates/variants
https://ppm.mpptrade.pl/admin/csv/templates/features
https://ppm.mpptrade.pl/admin/csv/templates/compatibility
```

### Step 3: Otwórz w Excel

- Plik pobierze się jako `szablon_{type}_YYYY-MM-DD.csv`
- Otwórz w Microsoft Excel lub LibreOffice Calc
- Sprawdź polskie znaki (powinny być poprawne dzięki UTF-8 BOM)
- Szablon zawiera 3 przykładowe wiersze z realistycznymi danymi

---

## CSV VARIANTS FORMAT

### Required Columns:

| Column Name | Type | Description | Example |
|-------------|------|-------------|---------|
| `SKU` | string | Unikalny SKU wariantu | `ABC-001-RED-L` |
| `Parent SKU` | string | SKU produktu nadrzędnego | `ABC-001` |

### Dynamic Attribute Columns:

System automatycznie wykrywa kolumny atrybutów na podstawie konfiguracji DB:
- `Rozmiar` → Attribute Type "Size"
- `Kolor` → Attribute Type "Color"
- `Material` → Attribute Type "Material"

### Dynamic Price Columns:

| Column Pattern | Description | Example |
|----------------|-------------|---------|
| `Cena {PriceGroupName}` | Cena dla grupy cenowej | `Cena Detaliczna` |
| | | `Cena Dealer Standard` |

**Format:** `123,45` (przecinek!)

### Dynamic Stock Columns:

| Column Pattern | Description | Example |
|----------------|-------------|---------|
| `Stan {WarehouseName}` | Stan magazynowy | `Stan MPPTRADE` |
| | | `Stan Pitbike.pl` |

**Format:** liczba całkowita (np. `150`)

### Example Variants CSV:

```csv
SKU;Parent SKU;Rozmiar;Kolor;Cena Detaliczna;Cena Dealer Standard;Stan MPPTRADE;Stan Pitbike.pl
ABC-001-RED-L;ABC-001;L;Czerwony;299,99;249,99;50;30
ABC-001-RED-XL;ABC-001;XL;Czerwony;299,99;249,99;40;20
ABC-001-BLUE-L;ABC-001;L;Niebieski;299,99;249,99;60;25
```

---

## CSV FEATURES FORMAT

### Required Columns:

| Column Name | Type | Description | Example |
|-------------|------|-------------|---------|
| `SKU` | string | SKU produktu lub wariantu | `ABC-001` |

### Dynamic Feature Columns:

System automatycznie wykrywa feature types:
- `Waga` → Feature Type "Weight" (value: `1,5 kg`)
- `Moc silnika` → Feature Type "Engine Power" (value: `125 kW`)
- `Pojemność` → Feature Type "Volume" (value: `2,0 L`)

**Format:** dowolny string (może zawierać jednostki)

### Example Features CSV:

```csv
SKU;Waga;Moc silnika;Pojemność;Pochodzenie
ABC-001;1,5 kg;125 kW;2,0 L;Niemcy
ABC-002;2,3 kg;150 kW;2,5 L;Włochy
```

---

## CSV COMPATIBILITY FORMAT

### Required Columns:

| Column Name | Type | Description | Example |
|-------------|------|-------------|---------|
| `SKU` | string | SKU części | `ABC-001` |
| `Brand` | string | Marka pojazdu | `Toyota` |
| `Model` | string | Model pojazdu | `Corolla` |
| `Year From` | integer | Rok produkcji od | `2015` |
| `Year To` | integer | Rok produkcji do | `2020` |

### Optional Columns:

| Column Name | Type | Description | Example |
|-------------|------|-------------|---------|
| `Engine Type` | string | Typ silnika | `1.6 VVT-i` |
| `Body Type` | string | Typ nadwozia | `Sedan` |
| `Fuel Type` | string | Rodzaj paliwa | `Benzyna` |
| `Source` | string | Źródło danych | `TecDoc` |
| `Notes` | text | Uwagi | `Tylko wersja europejska` |

### Example Compatibility CSV:

```csv
SKU;Brand;Model;Year From;Year To;Engine Type;Fuel Type;Source
ABC-001;Toyota;Corolla;2015;2020;1.6 VVT-i;Benzyna;TecDoc
ABC-001;Honda;Civic;2016;2021;1.5 VTEC;Benzyna;Własne dane
ABC-002;Volkswagen;Golf;2013;2019;1.4 TSI;Benzyna;TecDoc
```

---

## IMPORT WORKFLOW

### Step 1: Upload File

1. Navigate to `/admin/csv/import/{type}`
2. **Wybierz plik** lub **przeciągnij i upuść** CSV/XLSX
3. Czekaj na upload (max 10MB)

**Visual Feedback:**
- Dropzone zmienia kolor przy drag-over
- Spinner podczas parsowania: "Przetwarzanie pliku..."

---

### Step 2: Column Mapping & Preview

System automatycznie wykrywa kolumny:

**Mapowanie kolumn:**
- Tabela pokazuje: `CSV Column → Detected Field → Example Value`
- Sprawdź czy wszystkie wymagane kolumny wykryte

**Podgląd danych:**
- Pierwsze 10 wierszy z numerami (2-11)
- Status walidacji per wiersz (OK / Błąd)

**Statystyki:**
- Całkowite wiersze: liczba rekordów w pliku
- Poprawne: wiersze bez błędów
- Błędy: wiersze z błędami walidacji
- Konflikty: duplikaty SKU

---

### Step 3: Validation Errors (if any)

Jeśli system wykryje błędy:

**Sekcja "Błędy walidacji (X)":**
- Lista błędów grouped by row number
- Format: `Wiersz 15, Kolumna 'Price': must be numeric (got 'abc')`
- Przycisk "Pobierz raport błędów" → CSV z listą błędów

**Akcje:**
1. Napraw błędy w oryginalnym pliku CSV
2. Wgraj ponownie poprawiony plik
3. LUB kliknij "Anuluj" i wrócz do edycji

⚠️ **Przycisk "Wykonaj import" jest ZABLOKOWANY** gdy są błędy!

---

### Step 4: Conflict Resolution

Jeśli wykryto duplikaty SKU:

**Sekcja "Rozwiązywanie konfliktów (X)":**

Wybierz akcję:
1. **Pomiń** - Nie importuj duplikatów (pozostaw istniejące dane)
2. **Nadpisz** - Zastąp WSZYSTKIE pola istniejących rekordów nowymi wartościami
3. **Aktualizuj zmiany** - Aktualizuj TYLKO pola które się różnią

**Przykład:**
```
Istniejący rekord: SKU "ABC-001-RED-L", Price 299.99, Stock 50
Import CSV:        SKU "ABC-001-RED-L", Price 319.99, Stock 50

- Pomiń:           Pozostaje 299.99, 50 (brak zmian)
- Nadpisz:         Zmienia na 319.99, 50 (wszystko)
- Aktualizuj:      Zmienia tylko price → 319.99 (stock bez zmian bo identyczny)
```

---

### Step 5: Execute Import

1. Sprawdź statystyki (liczba wierszy do importu)
2. Kliknij **"Wykonaj import (X wierszy)"**
3. Czekaj na przetwarzanie:
   - Spinner animation
   - Progress bar (50% width)
   - "Przetwarzanie X wierszy..."

**Batch Processing:**
- System przetwarza po 100 wierszy jednocześnie
- Dla 500 wierszy: 5 batches (100+100+100+100+100)
- Transaction rollback jeśli cały batch fails

---

### Step 6: Completion

**Ekran sukcesu:**
- Zielona ikona checkmark
- "Import zakończony pomyślnie!"
- Podsumowanie:
  - Pomyślne: X
  - Błędy: Y
  - Całkowite: Z

**Akcje:**
- **"Importuj kolejny plik"** → Resetuje wizard (powrót do upload step)
- **"Powrót do panelu"** → Przekierowanie do `/admin`

---

## EXPORT WORKFLOW

### A) Export Single Product

**Use Case:** Eksport wariantów/cech/dopasowań dla JEDNEGO produktu

**URLs:**
```
/admin/products/{product_id}/export/variants
/admin/products/{product_id}/export/features
/admin/products/{product_id}/export/compatibility
```

**Optional Parameter:** `?format=csv` lub `?format=xlsx` (default: xlsx)

**Output:**
- File name: `warianty_{SKU}_YYYY-MM-DD.xlsx`
- Single sheet with product data
- Polish formatting (TAK/NIE, 123,45 zł, etc.)

---

### B) Bulk Export (Multiple Products)

**Use Case:** Eksport danych dla WIELU produktów jednocześnie

**Endpoint:** POST `/admin/csv/export/multiple`

**Request Body:**
```json
{
  "product_ids": [1, 2, 3, 5, 8],
  "include_variants": true,
  "include_features": true,
  "include_compatibility": true
}
```

**Output:**
- File name: `eksport_produktow_YYYY-MM-DD.xlsx`
- **3 sheets:**
  - `Warianty` - All variants from selected products
  - `Cechy` - All features
  - `Dopasowania` - All compatibility records
- Polish localization in all sheets

---

### C) Export Format Details

**Excel XLSX Features:**
- Multi-sheet support (separate sheet per data type)
- Proper column widths (auto-sized)
- Header row styled (bold, background color)
- Data formatting:
  - Prices: `123,45 zł` (currency symbol)
  - Booleans: `TAK/NIE` (readable)
  - Dates: `2025-10-20` (ISO format)

**CSV Features:**
- UTF-8 BOM encoding (Excel-compatible)
- Polish characters supported (ąęćńóśźż)
- Delimiter: `;` (semicolon - Polish Excel standard)

---

## ERROR HANDLING

### Common Validation Errors:

| Error Message | Cause | Solution |
|---------------|-------|----------|
| `Pole SKU jest wymagane` | Missing SKU column or empty value | Add SKU to all rows |
| `Price must be numeric` | Invalid price format (e.g., "abc") | Use numeric format: `123,45` |
| `Parent SKU not found in database` | Variant references non-existent product | Create parent product first |
| `Duplicate variant SKU` | SKU already exists in variants table | Use conflict resolution or change SKU |
| `Invalid warehouse name` | Warehouse doesn't exist in DB | Check available warehouses in admin |
| `Invalid price group` | Price group not configured | Create price group first |
| `Year From must be less than Year To` | Invalid year range (e.g., 2020-2015) | Fix year order |

---

### Error Report Download:

**When validation fails:**
1. Kliknij "Pobierz raport błędów"
2. Plik CSV pobierze się: `import_errors_YYYY-MM-DD_HH-MM-SS.csv`

**Report Format:**
```csv
Wiersz;Kolumna;Błąd
2;SKU;Pole SKU jest wymagane
4;Price;Price must be numeric (got 'abc')
6;Parent SKU;Parent SKU not found in database
```

**Use Report To:**
- Identify exact row numbers with errors
- Fix specific fields causing validation failures
- Track down data quality issues

---

## CONFLICT RESOLUTION

### Understanding Conflicts:

**Conflict occurs when:**
- Import CSV contains SKU that ALREADY EXISTS in database
- For variants: `variant_sku` unique constraint
- For products: `product_sku` unique constraint

### Resolution Strategies:

#### 1️⃣ **POMIŃ** (Skip)
**Use When:** Chcesz zachować istniejące dane bez zmian

**Behavior:**
- Duplikaty są pomijane (NOT imported)
- Istniejące rekordy pozostają bez zmian
- Tylko nowe SKU są importowane

**Example:**
```
Database:  SKU "ABC-001" → Price 100 zł
Import:    SKU "ABC-001" → Price 200 zł
Result:    SKU "ABC-001" → Price 100 zł (unchanged)
```

---

#### 2️⃣ **NADPISZ** (Overwrite)
**Use When:** Chcesz całkowicie zastąpić stare dane nowymi

**Behavior:**
- WSZYSTKIE pola istniejącego rekordu są nadpisywane
- Nawet jeśli nowe wartości są identyczne
- Full replacement

**Example:**
```
Database:  SKU "ABC-001" → Price 100 zł, Stock 50
Import:    SKU "ABC-001" → Price 200 zł, Stock 50
Result:    SKU "ABC-001" → Price 200 zł, Stock 50 (both updated)
```

---

#### 3️⃣ **AKTUALIZUJ ZMIANY** (Update Only Changed)
**Use When:** Chcesz aktualizować tylko zmienione pola (najlepszy wybór!)

**Behavior:**
- System porównuje field-by-field
- Aktualizuje TYLKO pola które się różnią
- Pozostałe pola pozostają bez zmian
- Wydajniejsze (fewer DB queries)

**Example:**
```
Database:  SKU "ABC-001" → Price 100 zł, Stock 50, Name "Produkt A"
Import:    SKU "ABC-001" → Price 200 zł, Stock 50  (brak Name w CSV)
Result:    SKU "ABC-001" → Price 200 zł, Stock 50, Name "Produkt A" (only price updated)
```

### Best Practices:

✅ **Default Strategy:** "Aktualizuj zmiany" (most efficient)
✅ **Full Re-import:** Use "Nadpisz" when you want to reset all data
✅ **Partial Import:** Use "Pomiń" when adding only new products

---

## BULK OPERATIONS TIPS

### 1. Large File Optimization

**For files with >1000 rows:**
- Upload during **off-peak hours** (22:00 - 6:00)
- Batch processing automatically handles performance
- Expect ~1 second per 100 rows (1000 rows ≈ 10 seconds)

**Memory Limits:**
- System uses **stream parsing** (nie ładuje całego pliku do pamięci)
- Max file size: 10MB (OK dla ~50,000 rows variants)

---

### 2. Data Preparation Best Practices

**Before Import:**
1. ✅ Validate data in Excel (sort, filter, find duplicates)
2. ✅ Use template column order (prevents mapping issues)
3. ✅ Remove empty rows at end of file
4. ✅ Check encoding is UTF-8 (File → Save As → Encoding: UTF-8)

**Data Quality:**
- ✅ Trim whitespace from SKUs (no leading/trailing spaces)
- ✅ Consistent decimal format: `123,45` (comma!)
- ✅ Validate year ranges (Year From < Year To)
- ✅ Check parent SKUs exist (query products table first)

---

### 3. Incremental Import Strategy

**Scenario:** Monthly product updates (new variants + price changes)

**Workflow:**
1. Export current data: `/admin/csv/export/multiple`
2. Modify exported file (add new rows, update prices)
3. Re-import with "Aktualizuj zmiany" strategy
4. System updates only changed fields + adds new SKUs

**Benefits:**
- ✅ No data loss (existing records safe)
- ✅ Faster processing (only diffs updated)
- ✅ Audit trail preserved

---

### 4. Multi-Sheet Excel Workflow

**For complex imports:**
1. Use **separate sheets** in single Excel file:
   - Sheet 1: Warianty
   - Sheet 2: Cechy
   - Sheet 3: Dopasowania

2. Export each sheet as CSV separately
3. Import each CSV to corresponding type

**Alternative:** Use bulk export format as template (already has 3 sheets)

---

## TROUBLESHOOTING

### Problem: Upload fails with "Max file size exceeded"

**Solution:**
- Split file into smaller chunks (<10MB each)
- Or compress with ZIP (system auto-extracts >1000 rows)

---

### Problem: Polish characters display as "�����"

**Solution:**
- Save CSV as **UTF-8 with BOM** in Excel:
  - File → Save As → CSV UTF-8 (*.csv)
- Or use LibreOffice: Save As → Character Set: Unicode (UTF-8)

---

### Problem: Validation error "Parent SKU not found"

**Diagnosis:**
- Variant references product that doesn't exist

**Solution:**
1. Check SKU spelling (case-sensitive!)
2. Query products table: `SELECT sku FROM products WHERE sku = 'ABC-001'`
3. Create parent product first, then import variants

---

### Problem: Import button disabled (grayed out)

**Diagnosis:**
- Validation errors present

**Solution:**
1. Scroll to "Błędy walidacji" section
2. Download error report CSV
3. Fix errors in original file
4. Re-upload corrected CSV

---

### Problem: "Duplicate variant SKU" conflict

**Diagnosis:**
- Variant with same SKU already exists

**Solution:**
1. Choose conflict resolution strategy:
   - Pomiń (keep existing)
   - Nadpisz (replace)
   - Aktualizuj (update changed fields)
2. Or change SKU to unique value

---

### Problem: Export downloads empty file

**Diagnosis:**
- No data exists for selected product

**Solution:**
- Verify product has variants/features/compatibility in database
- Check product ID is correct
- Try exporting different product

---

### Problem: Import succeeds but data not visible

**Diagnosis:**
- Cache issue or database transaction delay

**Solution:**
1. Clear browser cache (Ctrl+Shift+R)
2. Refresh product page
3. Query database directly: `SELECT * FROM product_variants WHERE parent_sku = 'ABC-001'`

---

### Problem: Batch processing timeout

**Diagnosis:**
- Very large file (>5000 rows) causing PHP timeout

**Solution:**
1. Split file into smaller batches (1000 rows each)
2. Import sequentially
3. Or increase PHP `max_execution_time` (requires server access)

---

## SUPPORT & FEEDBACK

### Reporting Bugs:
- Email: admin@mpptrade.pl
- Include:
  - Error message (screenshot)
  - CSV file sample (first 10 rows)
  - Browser version
  - Steps to reproduce

### Feature Requests:
- Submit via admin panel: Feedback button
- Or GitHub issues (if open-source)

### Documentation Updates:
- This guide version: 1.0 (2025-10-20)
- Check for updates: `/admin/help/csv-import`

---

**END OF GUIDE**
