# SYNC LOGIC VERIFICATION INSTRUCTIONS

FAZA 3B.3 - Weryfikacja działania sync logic dla eksportu PPM → PrestaShop

## PRZEGLĄD TESTÓW

Utworzone 4 skrypty testowe do weryfikacji 3 komponentów:
1. **SyncProductToPrestaShop Job** - Wykonanie zadania w queue
2. **ProductTransformer** - Transformacja danych PPM → PrestaShop
3. **Error Handling & Logging** - Obsługa błędów i logowanie

---

## WYMAGANIA WSTĘPNE

### 1. Środowisko

```powershell
# Sprawdź czy vendor/ istnieje
Test-Path "vendor/autoload.php"

# Jeśli NIE - zainstaluj dependencies
composer install

# Sprawdź PHP
php --version  # Wymagane: PHP 8.3+

# Sprawdź połączenie z bazą
php artisan tinker --execute="DB::select('SELECT 1');"
```

### 2. Baza Danych

Wymagane tabele:
- `products` (z relationships: categories, prices, stocks)
- `prestashop_shops` (przynajmniej 1 aktywny sklep)
- `product_shop_data` (sync status tracking)
- `sync_logs` (audit trail)
- `price_groups`
- `warehouses`
- `categories`

```sql
-- Sprawdź strukturę kluczowych tabel
SELECT COUNT(*) as shop_count FROM prestashop_shops WHERE is_active = 1;
SELECT COUNT(*) as product_count FROM products;
SELECT COUNT(*) as sync_status_count FROM product_shop_data;
SELECT COUNT(*) as sync_log_count FROM sync_logs;
```

### 3. Queue Configuration

```bash
# Sprawdź konfigurację queue
php artisan config:show queue

# Sprawdź czy Redis działa (jeśli używany)
php artisan tinker --execute="Illuminate\Support\Facades\Cache::get('test');"

# Lub użyj database driver (fallback)
# .env: QUEUE_CONNECTION=database
```

---

## TEST 1: SETUP - Tworzenie Produktu Testowego

**Cel:** Utworzenie produktu z pełnymi danymi do testów sync

**Skrypt:** `_TOOLS/prepare_sync_test_product.php`

**Wykonanie:**

```powershell
# Uruchom skrypt
php _TOOLS/prepare_sync_test_product.php
```

**Oczekiwany Output:**

```
=== PREPARE SYNC TEST PRODUCT ===

✅ Product Type: Czesc zamienna (ID: 2)
✅ Product created: ID 123, SKU: TEST-SYNC-1699123456
✅ Price Group: Detaliczna (ID: 1)
✅ Price added: 99.99 PLN (net)
✅ Warehouse: MPPTRADE (ID: 1)
✅ Stock added: 50 units in MPPTRADE
✅ Category: Kategoria Glowna (ID: 1)
✅ Category attached: Kategoria Glowna

=== PRODUCT CREATED SUCCESSFULLY ===
Product ID: 123
SKU: TEST-SYNC-1699123456
...
```

**Weryfikacja:**

```sql
-- Sprawdź utworzony produkt
SELECT * FROM products WHERE sku LIKE 'TEST-SYNC-%' ORDER BY id DESC LIMIT 1;

-- Sprawdź relationships
SELECT * FROM product_prices WHERE product_id = <ID>;
SELECT * FROM stocks WHERE product_id = <ID>;
SELECT * FROM product_categories WHERE product_id = <ID>;
```

**Zapisz Product ID dla kolejnych testów!**

---

## TEST 2: JOB EXECUTION - Dispatch i Wykonanie

**Cel:** Weryfikacja działania SyncProductToPrestaShop job

**Skrypt:** `_TOOLS/test_sync_job_dispatch.php`

**Wykonanie:**

```powershell
# Terminal 1: Uruchom queue worker
php artisan queue:work --verbose --tries=1

# Terminal 2: Dispatch job
php _TOOLS/test_sync_job_dispatch.php <PRODUCT_ID> [SHOP_ID]

# Przykład:
php _TOOLS/test_sync_job_dispatch.php 123 1
```

**Oczekiwany Output (Terminal 2):**

```
=== TEST SYNC JOB DISPATCH ===

✅ Product found:
  ID: 123
  SKU: TEST-SYNC-1699123456
  Name: Test Product For Sync Verification
  Active: Yes
  Categories: 1
  Prices: 1
  Stock records: 1

✅ Shop found:
  ID: 1
  Name: Pitbike.pl
  Domain: www.pitbike.pl
  Active: Yes
  Version: 8

✅ Job dispatched successfully!
```

**Oczekiwany Output (Terminal 1 - Queue Worker):**

```
[YYYY-MM-DD HH:MM:SS] Processing: App\Jobs\PrestaShop\SyncProductToPrestaShop
[YYYY-MM-DD HH:MM:SS] Processed:  App\Jobs\PrestaShop\SyncProductToPrestaShop
```

**Weryfikacja:**

```sql
-- Check product_shop_data
SELECT
    product_id,
    shop_id,
    sync_status,
    prestashop_product_id,
    last_sync_at,
    error_message
FROM product_shop_data
WHERE product_id = <PRODUCT_ID> AND shop_id = <SHOP_ID>;

-- Expected: sync_status = 'synced', prestashop_product_id IS NOT NULL

-- Check sync_logs
SELECT
    operation,
    direction,
    status,
    message,
    execution_time_ms,
    created_at
FROM sync_logs
WHERE product_id = <PRODUCT_ID> AND shop_id = <SHOP_ID>
ORDER BY created_at DESC
LIMIT 5;

-- Expected: status = 'success', operation = 'sync_product'
```

**Monitor Laravel Log:**

```powershell
# Terminal 3 (optional): Watch logs
Get-Content storage/logs/laravel.log -Wait -Tail 50 | Select-String "sync"
```

**Szukaj w logach:**
- `Product sync job started`
- `Product transformed for PrestaShop`
- `Product synced successfully to PrestaShop`
- `Product sync job completed successfully`

---

## TEST 3: TRANSFORMER OUTPUT - Weryfikacja Danych

**Cel:** Sprawdzenie poprawności transformacji PPM → PrestaShop

**Skrypt:** `_TOOLS/test_product_transformer.php`

**Wykonanie:**

```powershell
php _TOOLS/test_product_transformer.php <PRODUCT_ID> [SHOP_ID]

# Przykład:
php _TOOLS/test_product_transformer.php 123 1
```

**Oczekiwany Output:**

```
=== TEST PRODUCT TRANSFORMER ===

✅ Product loaded:
  ID: 123
  SKU: TEST-SYNC-1699123456
  Name: Test Product For Sync Verification

✅ Shop loaded:
  ID: 1
  Name: Pitbike.pl
  Version: 8

✅ PrestaShop client created (version 8)

Transforming product...
✅ Transformation completed

=== TRANSFORMED DATA ===
{
  "product": {
    "reference": "TEST-SYNC-1699123456",
    "ean13": "5901699123456",
    "name": [
      {
        "id": 1,
        "value": "Test Product For Sync Verification"
      }
    ],
    "description_short": [...],
    "description": [...],
    "price": 99.99,
    "weight": 1.5,
    "active": 1,
    "associations": {
      "categories": [
        {"id": 5}
      ]
    },
    ...
  }
}

=== FIELD VERIFICATION ===
✅ reference: "TEST-SYNC-1699123456" (expected: TEST-SYNC-1699123456)
✅ name: Array(1 items) - Valid multilingual format (expected: multilingual array)
✅ price: 99.99 (expected: float > 0)
✅ active: 1 (expected: 0 or 1)
✅ weight: 1.5 (expected: float)
✅ description: Array(1 items) - Valid multilingual format (expected: multilingual array)
✅ description_short: Array(1 items) - Valid multilingual format (expected: multilingual array)
✅ associations.categories: Array(1 items) (expected: array of category IDs)

=== PRESTASHOP REQUIRED FIELDS CHECK ===
✅ reference: Present
✅ price: Present
✅ name: Present
✅ active: Present

=== VERIFICATION SUMMARY ===
✅ All required fields present
✅ Transformer output is valid for PrestaShop API

READY FOR SYNC!
```

**Weryfikacja Manualnie:**

Sprawdź PrestaShop Admin Panel:
1. Zaloguj się do backend PrestaShop
2. Catalog → Products
3. Wyszukaj SKU: `TEST-SYNC-1699123456`
4. Sprawdź:
   - ✅ Nazwa produktu poprawna
   - ✅ Cena zgodna (99.99 PLN)
   - ✅ Kategoria przypisana
   - ✅ Stan magazynowy (50 szt.)
   - ✅ Status aktywny

**Screenshot:** Zrób screenshot strony produktu w PrestaShop dla raportu

---

## TEST 4: ERROR HANDLING - Obsługa Błędów

**Cel:** Weryfikacja wykrywania błędów i logowania

**Skrypt:** `_TOOLS/test_sync_error_handling.php`

**Wykonanie:**

```powershell
# Terminal 1: Queue worker (jeśli nie działa)
php artisan queue:work --verbose --tries=1

# Terminal 2: Uruchom testy błędów
php _TOOLS/test_sync_error_handling.php
```

**Oczekiwany Output:**

```
=== TEST SYNC ERROR HANDLING ===

TEST CASE 1: Missing product name
--------------------------------------------------
Created test product: ID 124, SKU ERROR-TEST-NAME-1699123500
Using shop: Pitbike.pl (ID: 1)
Job dispatched (will process in queue worker)
⏳ Queue worker needs to process this job
Check sync_logs table after queue:work for error entry

TEST CASE 2: Missing product SKU
--------------------------------------------------
Created test product: ID 125, Name: Test Product Without SKU
Job dispatched (will process in queue worker)

TEST CASE 3: Inactive product (is_active = false)
--------------------------------------------------
Created test product: ID 126, SKU ERROR-TEST-INACTIVE-1699123501
Product is_active: false
Job dispatched (will process in queue worker)

TEST CASE 4: Verify sync_logs table structure
--------------------------------------------------
Recent sync logs (last 5 product syncs):
❌ [2025-11-04 10:30:15] Product 124 → Shop 1 Status: error | Product name is required for PrestaShop sync (product ID: 124) | 45ms
❌ [2025-11-04 10:30:16] Product 125 → Shop 1 Status: error | Product SKU is required for PrestaShop sync (product ID: 125) | 42ms
❌ [2025-11-04 10:30:17] Product 126 → Shop 1 Status: error | Product must be active to sync | 38ms
✅ [2025-11-04 10:25:00] Product 123 → Shop 1 Status: success | Product created successfully | 1523ms

=== ERROR HANDLING TEST SUMMARY ===
{
  "test1_missing_name": {
    "status": "pending_queue",
    "product_id": 124,
    "expected": "Error in sync_logs: Product name required"
  },
  ...
}
```

**Weryfikacja:**

```sql
-- Sprawdź błędy w sync_logs
SELECT
    product_id,
    status,
    message,
    execution_time_ms,
    created_at
FROM sync_logs
WHERE status = 'error'
ORDER BY created_at DESC
LIMIT 10;

-- Expected: 3 error entries z odpowiednimi error messages

-- Sprawdź product_shop_data error tracking
SELECT
    product_id,
    sync_status,
    error_message,
    retry_count,
    last_sync_at
FROM product_shop_data
WHERE sync_status = 'error'
ORDER BY last_sync_at DESC;

-- Expected: sync_status = 'error', error_message populated
```

---

## ACCEPTANCE CRITERIA CHECKLIST

Po wykonaniu wszystkich testów sprawdź:

### Job Execution
- [ ] Job dispatched bez błędów
- [ ] Queue worker przetwarza job
- [ ] Job completes successfully (status: processed)
- [ ] Brak exceptions w Laravel log
- [ ] Execution time < 10s (dla małego produktu)

### Transformer Output
- [ ] Wszystkie required fields present (reference, name, price, active)
- [ ] Multilingual fields w poprawnym formacie
- [ ] Category associations populated
- [ ] Price calculated correctly
- [ ] Active status = 0 or 1
- [ ] No hardcoded values

### Error Handling
- [ ] Missing name → Error detected & logged
- [ ] Missing SKU → Error detected & logged
- [ ] Inactive product → Validation fails
- [ ] sync_logs table populated with errors
- [ ] product_shop_data.error_message populated
- [ ] retry_count incremented

### Database Updates
- [ ] product_shop_data created/updated
- [ ] sync_status = 'synced' (success) or 'error' (failure)
- [ ] prestashop_product_id populated (success case)
- [ ] last_sync_at updated
- [ ] checksum calculated

### PrestaShop Integration
- [ ] Product visible in PrestaShop admin
- [ ] SKU matches
- [ ] Name matches
- [ ] Price matches
- [ ] Category assigned
- [ ] Stock quantity correct

---

## TROUBLESHOOTING

### Queue Worker nie przetwarza jobów

```bash
# Sprawdź queue connection
php artisan queue:failed

# Sprawdź Redis (jeśli używany)
redis-cli ping

# Użyj database queue (fallback)
# .env: QUEUE_CONNECTION=database
php artisan queue:table
php artisan migrate
```

### Vendor/autoload.php missing

```bash
composer install
```

### Brak połączenia z bazą

```bash
# Sprawdź .env
php artisan config:clear
php artisan cache:clear

# Test connection
php artisan tinker --execute="DB::select('SELECT 1');"
```

### PrestaShop API error

```bash
# Sprawdź credentials w prestashop_shops table
SELECT id, name, domain, api_key, is_active FROM prestashop_shops;

# Test API manually
curl -H "Authorization: Basic <API_KEY>" "https://domain.com/api/products?display=full&limit=1"
```

---

## RAPORTOWANIE WYNIKÓW

Po zakończeniu testów:

1. **Zbierz Screenshots:**
   - PrestaShop admin panel (synced product)
   - Queue worker output (job processing)
   - Database queries (sync_logs, product_shop_data)

2. **Sprawdź Logs:**
   - `storage/logs/laravel.log` - Sync operations
   - Queue worker output - Job execution

3. **SQL Queries dla Raportu:**

```sql
-- Summary statistics
SELECT
    sync_status,
    COUNT(*) as count
FROM product_shop_data
GROUP BY sync_status;

SELECT
    status,
    COUNT(*) as count,
    AVG(execution_time_ms) as avg_time_ms
FROM sync_logs
WHERE operation = 'sync_product'
GROUP BY status;

-- Recent syncs
SELECT
    p.id,
    p.sku,
    psd.sync_status,
    psd.prestashop_product_id,
    psd.last_sync_at,
    sl.execution_time_ms
FROM products p
LEFT JOIN product_shop_data psd ON p.id = psd.product_id
LEFT JOIN sync_logs sl ON p.id = sl.product_id
WHERE p.sku LIKE 'TEST-SYNC-%'
ORDER BY p.id DESC;
```

4. **Utwórz Agent Report:**
   - `_AGENT_REPORTS/debugger_faza3_sync_verification_2025-11-04_REPORT.md`
   - Użyj template z zadania

---

## CLEANUP (po testach)

```sql
-- Usuń test products
DELETE FROM products WHERE sku LIKE 'TEST-SYNC-%';
DELETE FROM products WHERE sku LIKE 'ERROR-TEST-%';

-- Opcjonalnie: usuń test logs (zachowaj dla audytu)
-- DELETE FROM sync_logs WHERE product_id IN (SELECT id FROM products WHERE sku LIKE 'TEST-%');
-- DELETE FROM product_shop_data WHERE product_id IN (SELECT id FROM products WHERE sku LIKE 'TEST-%');
```

---

## NEXT STEPS

Po weryfikacji FAZA 3B.3:
1. Dokumentuj findings w agent report
2. Identify issues dla FAZA 3B.4 (Product Sync Status Update)
3. Propose improvements dla sync logic
4. Update Plan_Projektu z completed tasks
