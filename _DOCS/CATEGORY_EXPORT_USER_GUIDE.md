# 📦 PRZEWODNIK: Eksport Produktów na PrestaShop z Kategoriami

**Ostatnia aktualizacja:** 2025-11-18
**Wersja:** 1.0
**Status:** ✅ Production-ready

---

## 🎯 CEL DOKUMENTU

Kompleksowy przewodnik jak poprawnie eksportować produkty z PPM na PrestaShop wraz z kategoriami, uwzględniając:
- Per-shop category configuration
- Source of truth priority (pivot table → cache)
- Synchronizację kategorii podczas eksportu
- Troubleshooting typowych problemów

---

## 📋 WYMAGANIA WSTĘPNE

### 1. Konfiguracja Sklepu PrestaShop

**Panel:** `/admin/shops`

Upewnij się, że sklep ma:
- ✅ Poprawne dane połączenia (URL, API Key)
- ✅ Test połączenia przeszedł pomyślnie
- ✅ Mapowanie kategorii PPM → PrestaShop skonfigurowane
- ✅ Status sklepu: Aktywny

**Weryfikacja:**
```
Admin Panel → Sklepy → [Nazwa sklepu] → Test Connection → ✅ Success
```

### 2. Mapowanie Kategorii

**Wymagane:** Przed eksportem produktu musisz zmapować kategorie PPM na kategorie PrestaShop.

**Lokalizacja:** `/admin/shops/[shop_id]/categories`

**Przykład:**
```
PPM Kategoria: "Pit Bike 140cc" (ID: 100)
  ↓
PrestaShop Kategoria: "Pit Bikes" (ID: 9)
```

---

## 🚀 WORKFLOW EKSPORTU PRODUKTU Z KATEGORIAMI

### KROK 1: Przygotowanie Produktu w PPM

**Panel:** `/admin/products/[product_id]/edit`

#### A. Ustaw Kategorie Domyślne (Zakładka "Kategorie")

1. Otwórz produkt w trybie edycji
2. Przejdź do zakładki **"Kategorie"**
3. Wybierz **"Dane domyślne"** (globalny tab)
4. Zaznacz kategorie dla produktu (max 10)
5. Wybierz **kategorię główną** (primary)
6. Kliknij **"Zapisz"**

**Efekt:**
- Kategorie zapisane w pivot table: `product_categories` WHERE `shop_id = NULL`
- To są domyślne kategorie dla WSZYSTKICH sklepów (fallback)

---

### KROK 2: Konfiguracja Kategorii Per-Shop (OPCJONALNE)

**Jeśli chcesz inne kategorie dla konkretnego sklepu:**

1. Pozostań w zakładce **"Kategorie"**
2. Przejdź do zakładki **"[Nazwa sklepu]"** (np. "B2B Test DEV")
3. Zaznacz kategorie **specyficzne dla tego sklepu**
4. Wybierz **kategorię główną** dla sklepu
5. Kliknij **"Zapisz"**

**Efekt:**
- Kategorie zapisane w pivot table: `product_categories` WHERE `shop_id = [shop_id]`
- System użyje TYCH kategorii podczas eksportu do tego sklepu
- **Priorytet:** Per-shop categories > Default categories

**⚠️ UWAGA:** Jeśli nie ustawisz per-shop categories, system użyje domyślnych.

---

### KROK 3: Synchronizacja Produktu na PrestaShop

#### Metoda A: Sync Now (Manualna synchronizacja)

1. Otwórz produkt w trybie edycji
2. Przejdź do zakładki **"Sklepy"**
3. Znajdź sklep docelowy na liście
4. Kliknij przycisk **"SYNC NOW"** przy sklepie
5. System dodaje job do kolejki
6. Poczekaj na wykonanie (patrz: monitorowanie)

**Timing:**
- Queue worker: przetwarza co ~30 sekund
- Średni czas: 5-30 sekund (zależnie od złożoności produktu)

---

#### Metoda B: Bulk Sync (Masowa synchronizacja)

**Panel:** `/admin/shops/[shop_id]/sync`

1. Przejdź do panelu synchronizacji sklepu
2. Zaznacz produkty do synchronizacji
3. Kliknij **"Bulk Sync Products"**
4. System utworzy job dla każdego produktu
5. Monitoruj postęp w panelu

**Użycie:** Synchronizacja wielu produktów jednocześnie (np. 50+ produktów)

---

### KROK 4: Monitorowanie Synchronizacji

#### A. Status w Zakładce "Sklepy"

**Panel:** `/admin/products/[product_id]/edit` → Zakładka "Sklepy"

**Statusy:**
- 🔄 **Pending** - Job w kolejce, oczekuje na wykonanie
- 🔄 **Syncing** - Job w trakcie przetwarzania
- ✅ **Synchronized** - Produkt zsynchronizowany pomyślnie
- ❌ **Failed** - Błąd synchronizacji (sprawdź logi)

**Ikony:**
- 🟢 **Green badge** - Synchronized
- 🟡 **Yellow badge** - Pending/Syncing
- 🔴 **Red badge** - Failed

---

#### B. Panel Sync Jobs

**Panel:** `/admin/shops/[shop_id]/sync-jobs`

**Informacje:**
- Lista wszystkich jobs synchronizacji
- Status wykonania
- Szczegóły błędów (jeśli wystąpiły)
- Timestamp (kiedy job został utworzony)

---

### KROK 5: Weryfikacja w PrestaShop

1. Zaloguj się do **PrestaShop Admin Panel**
2. Przejdź do **Catalog → Products**
3. Znajdź produkt (po SKU lub nazwie)
4. Otwórz produkt
5. Sprawdź zakładkę **"Categories"**

**Weryfikacja:**
- ✅ Produkt przypisany do poprawnych kategorii
- ✅ Primary category (główna) ustawiona prawidłowo
- ✅ Wszystkie wybrane kategorie widoczne

**PrestaShop Database Check (zaawansowane):**
```sql
-- Sprawdź kategorie produktu
SELECT pc.*, c.name
FROM ps_category_product pc
JOIN ps_category_lang c ON pc.id_category = c.id_category
WHERE pc.id_product = [prestashop_product_id]
  AND c.id_lang = 1;
```

---

## 🏗️ ARCHITEKTURA TECHNICZNA (dla deweloperów)

### Dual Category Representation

PPM używa **dwóch reprezentacji kategorii** dla każdego produktu:

#### 1. **Pivot Table (PRIMARY SOURCE OF TRUTH)**

**Tabela:** `product_categories`

**Struktura:**
```sql
product_id | category_id | shop_id | is_primary | sort_order
11034      | 100         | 1       | 1          | 0
11034      | 105         | 1       | 0          | 1
11034      | 42          | NULL    | 1          | 0  -- Default
11034      | 99          | NULL    | 0          | 1  -- Default
```

**Logika:**
- `shop_id = NULL` → Dane domyślne (fallback dla wszystkich sklepów)
- `shop_id = X` → Kategorie specyficzne dla sklepu X
- `is_primary = 1` → Główna kategoria (jedna per shop/default)

---

#### 2. **Cache (SECONDARY - Performance Optimization)**

**Tabela:** `product_shop_data.category_mappings`

**Format:** JSON Option A
```json
{
  "ui": {
    "selected": [100, 105],
    "primary": 100
  },
  "mappings": {
    "100": 9,
    "105": 14
  },
  "metadata": {
    "last_updated": "2025-11-18T15:00:00+00:00",
    "source": "manual"
  }
}
```

**Źródła:**
- `source: "manual"` - Zapisane przez użytkownika w UI
- `source: "pull"` - Pobrane z PrestaShop podczas importu
- `source: "sync"` - Aktualizowane podczas synchronizacji

---

### Priority Logic w ProductTransformer

**PRIORITY 1: Shop-specific categories (pivot table)**
```php
$shopCategories = $product->categoriesForShop($shopId, false)
    ->pluck('categories.id')
    ->toArray();

// If found → Use these categories (FRESH USER DATA)
```

**PRIORITY 2: Cache fallback**
```php
$shopData = $product->dataForShop($shopId)->first();
if ($shopData && $shopData->hasCategoryMappings()) {
    $prestashopIds = extractPrestaShopIds($shopData->category_mappings);
    // Use cached mappings (backward compatibility)
}
```

**PRIORITY 3: Global categories**
```php
$categoryIds = $product->categories()
    ->pluck('categories.id')
    ->toArray();

// Final fallback - default categories
```

**PRIORITY 4: PrestaShop Home category**
```php
return [['id' => 2]]; // PrestaShop default (Home)
```

---

### Synchronization Flow

```
[User Action: Save Product with Categories]
         ↓
[1. Save to Pivot Table]
   - product_categories (shop_id = X or NULL)
   - Detach existing + Attach new
         ↓
[2. Sync Cache]
   - CategoryMappingsConverter::fromPivotData()
   - product_shop_data.category_mappings = Option A format
         ↓
[3. Dispatch Sync Job]
   - SyncProductToPrestaShop job created
   - Added to queue (default)
         ↓
[4. Queue Worker Processes Job]
   - ProductTransformer::buildCategoryAssociations()
   - PRIORITY 1: Read from pivot table (shop_id = X)
   - Map PPM IDs → PrestaShop IDs via CategoryMapper
         ↓
[5. Send to PrestaShop API]
   - PUT /api/products/[id]
   - <associations><categories><category><id>9</id></category></categories></associations>
         ↓
[6. Update Status]
   - product_shop_data.sync_status = 'synchronized'
   - Timestamp updated
```

---

## 🔧 TROUBLESHOOTING

### Problem 1: Kategorie nie synchronizują się na PrestaShop

**Symptom:** Produkt zsynchronizowany, ale kategorie są puste lub nieprawidłowe.

**Diagnoza:**
1. Sprawdź logi Laravel:
```bash
tail -100 storage/logs/laravel.log | grep -E '\[CATEGORY SYNC\]|\[CATEGORY CACHE\]'
```

2. Sprawdź pivot table:
```sql
SELECT * FROM product_categories
WHERE product_id = [id] AND (shop_id = [shop_id] OR shop_id IS NULL);
```

3. Sprawdź cache:
```sql
SELECT category_mappings FROM product_shop_data
WHERE product_id = [id] AND shop_id = [shop_id];
```

**Rozwiązania:**

**A. Brak mapowania kategorii PPM → PrestaShop**
```
Admin Panel → Sklepy → [Shop] → Categories → Map categories
```

**B. Kategorie nie zapisały się do pivot table**
```
1. Edytuj produkt
2. Zakładka Kategorie → Sklepy
3. Wybierz kategorie ponownie
4. Zapisz produkt
```

**C. Cache jest stary (nie zsynchronizowany z pivot)**
```php
// W tinker:
$product = Product::find([id]);
$shop = PrestaShopShop::find([shop_id]);

// Force cache refresh
$converter = app(CategoryMappingsConverter::class);
$ppmIds = DB::table('product_categories')
    ->where('product_id', $product->id)
    ->where('shop_id', $shop->id)
    ->pluck('category_id')->toArray();

$mappings = $converter->fromPivotData($ppmIds, $shop);

ProductShopData::updateOrCreate([
    'product_id' => $product->id,
    'shop_id' => $shop->id
], [
    'category_mappings' => $mappings
]);
```

---

### Problem 2: SQL Error "Column 'id' in SELECT is ambiguous"

**Symptom:**
```
SQLSTATE[23000]: Column 'id' in SELECT is ambiguous
WHERE shop_id IS NULL AND shop_id = 1
```

**Root Cause:** Bug naprawiony w wersji 2025-11-18.

**Rozwiązanie:** Upewnij się, że używasz najnowszej wersji ProductTransformer:
- ProductTransformer używa `categoriesForShop($shopId, false)` zamiast `categories()->wherePivot()`
- `pluck()` ma table prefix: `pluck('categories.id')`

**Weryfikacja:**
```bash
grep "categoriesForShop" app/Services/PrestaShop/ProductTransformer.php
# Powinno zwrócić linię z Priority 1 logic
```

---

### Problem 3: Sync Job w statusie "Pending" przez długi czas

**Symptom:** Job nie wykonuje się mimo upływu kilku minut.

**Diagnoza:**
1. Sprawdź queue worker:
```bash
# Na serwerze production
ps aux | grep "artisan queue:work"
```

2. Sprawdź jobs table:
```sql
SELECT * FROM jobs WHERE queue = 'default' ORDER BY id DESC LIMIT 5;
```

**Rozwiązanie:**

**A. Queue worker nie działa**
```bash
# Uruchom queue worker (production - Hostido)
cd domains/ppm.mpptrade.pl/public_html
nohup php artisan queue:work --queue=default --tries=3 --timeout=300 > /dev/null 2>&1 &
```

**B. Job zablokowany (reserved_at not NULL)**
```sql
-- Reset zablokowanych jobs
UPDATE jobs SET reserved_at = NULL, attempts = 0
WHERE queue = 'default' AND reserved_at IS NOT NULL;
```

**C. Job failed (sprawdź failed_jobs)**
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;
```

---

### Problem 4: Kategorie różnią się między sklepami

**Symptom:** Sklep A ma inne kategorie niż Sklep B dla tego samego produktu.

**Oczekiwane zachowanie:** To **NORMALNE** - system wspiera per-shop categories!

**Wyjaśnienie:**
- Sklep A: Per-shop categories (priority 1) → `product_categories` WHERE `shop_id = A`
- Sklep B: Default categories (priority 3) → `product_categories` WHERE `shop_id IS NULL`

**Jeśli to nie zamierzone:**
1. Usuń per-shop categories dla Sklep A
2. System użyje default categories dla obu sklepów

**Jak usunąć per-shop categories:**
```sql
DELETE FROM product_categories
WHERE product_id = [id] AND shop_id = [shop_id];
```

---

## ✅ BEST PRACTICES

### 1. Zawsze Ustawiaj Dane Domyślne

**Zalecenie:** Przed konfiguracją per-shop categories, ustaw dane domyślne.

**Dlaczego:**
- Fallback dla nowych sklepów
- Konsystencja dla wszystkich sklepów bez custom config
- Łatwiejsze zarządzanie przy wielu sklepach

**Workflow:**
1. Ustaw kategorie domyślne (zakładka "Dane domyślne")
2. Zapisz
3. POTEM (opcjonalnie) konfiguruj per-shop

---

### 2. Testuj Eksport na Test Shop

**Zalecenie:** Przed masową synchronizacją przetestuj na test shop.

**Setup:**
1. Stwórz sklep testowy "Test DEV" w PPM
2. Skonfiguruj połączenie z PrestaShop test instance
3. Zsynchronizuj 2-3 produkty testowe
4. Zweryfikuj kategorie w PrestaShop
5. Jeśli OK → przejdź do produkcji

---

### 3. Monitoruj Logi Podczas Pierwszej Synchronizacji

**Zalecenie:** Przy pierwszej synchronizacji sklepu obserwuj logi w czasie rzeczywistym.

**Jak:**
```bash
# SSH na serwer production
tail -f storage/logs/laravel.log | grep -E '\[CATEGORY SYNC\]|\[CATEGORY CACHE\]'
```

**Czego szukać:**
- `[CATEGORY SYNC] Using shop-specific categories from pivot` → ✅ OK
- `[CATEGORY SYNC] Fallback: Using category_mappings cache` → ⚠️ Brak per-shop, używa cache
- `[CATEGORY SYNC] Using product default categories` → ℹ️ Używa default (brak per-shop)
- `[CATEGORY SYNC] Category mapping not found` → ❌ Brak mapowania PPM → PrestaShop

---

### 4. Mapuj Kategorie PRZED Importem Produktów

**Zalecenie:** Skonfiguruj mapowanie kategorii zanim zaczniesz synchronizować produkty.

**Dlaczego:**
- Brak mapowania = brak kategorii na PrestaShop
- Re-sync wszystkich produktów po dodaniu mappingu jest kosztowny

**Workflow:**
1. Admin Panel → Sklepy → [Shop] → Categories
2. Zmapuj WSZYSTKIE używane kategorie PPM
3. Zapisz mapowanie
4. POTEM rozpocznij synchronizację produktów

---

### 5. Używaj Primary Category

**Zalecenie:** Zawsze ustawiaj primary category dla produktu.

**Dlaczego:**
- PrestaShop wymaga jednej "domyślnej" kategorii
- Wpływa na URL produktu w PrestaShop
- Decyduje o głównej kategorii w breadcrumbs

**Jak:**
- W zakładce Kategorie zaznacz radio button przy głównej kategorii
- System automatycznie ustawi `is_primary = 1` w pivot table

---

## ❓ FAQ

### Q1: Czy mogę zmienić kategorie produktu już zsynchronizowanego?

**Odpowiedź:** TAK.

**Workflow:**
1. Edytuj produkt w PPM
2. Zmień kategorie (zakładka Kategorie)
3. Zapisz produkt → cache automatycznie zsynchronizowany
4. Kliknij "SYNC NOW" dla sklepu
5. System zaktualizuje kategorie w PrestaShop

---

### Q2: Co się stanie jeśli usunę mapowanie kategorii?

**Odpowiedź:** Produkty w tej kategorii NIE zostaną zsynchronizowane na PrestaShop (brak kategorii).

**Zalecenie:**
- Przed usunięciem mappingu sprawdź, które produkty używają tej kategorii
- Przenieś produkty do innych kategorii
- Usuń mapowanie

---

### Q3: Ile kategorii może mieć produkt?

**Odpowiedź:**
- **PPM:** Max 10 kategorii per produkt (validation)
- **PrestaShop:** Brak twardego limitu, ale zalecane 3-5 kategorii

---

### Q4: Czy kategorie synchronizują się automatycznie?

**Odpowiedź:** NIE (obecnie).

**Aktualny workflow:**
1. Zmień kategorie w PPM
2. Kliknij "SYNC NOW" manualnie
3. System zsynchronizuje zmiany

**Planowane:** Auto-sync po zapisie produktu (future feature).

---

### Q5: Czy mogę synchronizować produkt do wielu sklepów jednocześnie?

**Odpowiedź:** TAK (poprzez Bulk Sync).

**Workflow:**
1. Panel Sync (`/admin/shops/bulk-sync`)
2. Wybierz sklepy docelowe
3. Wybierz produkty
4. Kliknij "Bulk Sync"
5. System utworzy job dla każdej kombinacji produkt-sklep

---

### Q6: Co się dzieje gdy product nie ma kategorii?

**Odpowiedź:** System użyje PrestaShop default category (Home, ID: 2).

**Log:**
```
[CATEGORY SYNC] No categories found, using default (Home)
product_id: 11034, shop_id: 1
```

**Zalecenie:** Zawsze przypisuj produkty do min. 1 kategorii.

---

## 📚 DODATKOWE ZASOBY

### Dokumentacja Techniczna

- **Architektura kategorii:** `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md`
- **Issue report:** `_ISSUES_FIXES/CATEGORY_SYNC_STALE_CACHE_ISSUE.md`
- **Compliance report:** `_AGENT_REPORTS/COMPLIANCE_REPORT_category_sync_stale_cache_fixes_2025-11-18.md`
- **Plan projektu:** `Plan_Projektu/ETAP_07_Prestashop_API.md`

### Pliki Kodu

- **ProductTransformer:** `app/Services/PrestaShop/ProductTransformer.php` (buildCategoryAssociations)
- **ProductFormSaver:** `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` (syncShopCategories)
- **CategoryMappingsConverter:** `app/Services/CategoryMappingsConverter.php` (fromPivotData)
- **HasCategories Trait:** `app/Models/Concerns/Product/HasCategories.php` (relationships)

### Test Scripts

- **E2E Test:** `_TEMP/test_category_sync_e2e.php` (full workflow test)
- **SQL Fix Test:** `_TEMP/test_sql_fix.php` (relationship validation)

---

## 📝 CHANGELOG

### 2025-11-18 - v1.0 (Initial Release)

**Changes:**
- ✅ Initial documentation created
- ✅ Workflow step-by-step guide
- ✅ Architecture section (dual representation)
- ✅ Troubleshooting common issues
- ✅ Best practices
- ✅ FAQ

**Based on:**
- CATEGORY_SYNC_STALE_CACHE_ISSUE fixes (2025-11-18)
- E2E testing results (Product 11034, Shop 1)
- Production deployment verification

**Contributors:**
- Claude Code (documentation)
- User feedback (workflow validation)

---

## 📞 WSPARCIE

**W przypadku problemów:**

1. Sprawdź sekcję **Troubleshooting** w tym dokumencie
2. Sprawdź logi Laravel: `storage/logs/laravel.log`
3. Sprawdź status queue: `php artisan queue:failed`
4. Sprawdź dokumentację techniczną (sekcja Dodatkowe Zasoby)

**Zgłaszanie błędów:**
- GitHub Issues: `PPM-CC-Laravel` repository
- Include: log fragment, SKU produktu, shop ID, expected vs actual behavior

---

**Koniec dokumentu** 📄
