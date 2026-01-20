# Plan: Integracja ERP Subiekt GT w PPM

## Cel
Implementacja pełnej integracji z ERP Subiekt GT (InsERT) w PPM-CC-Laravel przez bezpośrednie połączenie z bazą danych SQL Server. Integracja ma być funkcjonalnie identyczna z istniejącymi integracjami Baselinker i PrestaShop (1:1).

## Status Architektury

### ✅ UKOŃCZONE (2026-01-20):

**REST API Wrapper (sapi.mpptrade.pl):**
- ✅ REST API .NET 8 działające na `https://sapi.mpptrade.pl`
- ✅ Endpointy: `/api/health`, `/api/products`, `/api/stock`, `/api/warehouses`, `/api/price-levels`
- ✅ Odkrycie tabeli `tw_Parametr` z nazwami poziomów cenowych
- ✅ `/api/price-levels` zwraca prawdziwe nazwy (Detaliczna, MRF-MPP, HuHa, etc.)
- ✅ `SubiektRestApiClient.php` - klient HTTP dla REST API
- ✅ Dokumentacja w `.claude/rules/erp/subiekt-*.md`
- ✅ Skill `subiekt-gt-integration` zaktualizowany do v1.1.0

**Pliki REST API:**
- `_TOOLS/SubiektGT_REST_API_DotNet/` - kod źródłowy
- `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` - klient Laravel

**Database Schema Discovery:**
- ✅ `tw_Parametr` - tabela z nazwami poziomów cenowych
- ✅ Mapowanie: `twp_NazwaCeny[N]` → `tc_CenaNetto[N-1]` (offset by 1!)
- ✅ Narzędzie `_TOOLS/explore_simple/search_price_names_pyodbc.py`

### Już istnieje (z poprzednich prac):
- `ERPSyncServiceInterface` - interfejs do implementacji
- `SubiektGTService` - **PLACEHOLDER** (zwraca not_implemented)
- `ERPServiceManager` - factory obsługuje `subiekt_gt`
- `ERPConnection` model z `ERP_SUBIEKT_GT` constant
- `ProductErpData` model dla per-ERP data
- `IntegrationMapping` dla mapowań
- `SyncJob` model dla tracking
- ERP Tab w ProductForm (już pokazuje tab Subiekt GT)
- Panel `/admin/integrations` (wizard z placeholder dla Subiekt)

### Do implementacji:
- `SubiektGTService` - pełna implementacja (~600-800 linii)
- `SubiektQueryBuilder` - SQL queries helper
- `SubiektDataTransformer` - mapowanie danych
- Job `PullProductsFromSubiektGT` - batch pull
- Job `DetectSubiektGTChanges` - scheduled polling
- UI: Connection config form (SQL Server)
- UI: Warehouse/Price Group mapping

## Specyfika Subiekt GT (różnice vs Baselinker/PrestaShop)

| Aspekt | Baselinker/PrestaShop | Subiekt GT |
|--------|----------------------|------------|
| Komunikacja | REST API | SQL Server (pdo_sqlsrv) |
| Zdjęcia | Sync obsługiwany | **BRAK** - Subiekt nie przechowuje |
| Warianty | Natywne warianty | **Oddzielne produkty** w tw__Towar |
| Webhooks | Dostępne | **BRAK** - polling required |
| Zapis danych | API POST/PUT | SQL lub Sfera API (COM) |
| Change detection | date_upd field | `tw_DataMod` column |

## Connection Config Schema

```php
'connection_config' => [
    // Mode: sql_direct (read-only) | sfera_dll | rest_api
    'connection_mode' => 'rest_api',  // REKOMENDOWANE dla PPM

    // ✅ REST API (sapi.mpptrade.pl) - ZALECANE
    'rest_api_url' => 'https://sapi.mpptrade.pl',
    'rest_api_key' => 'YOUR_API_KEY',
    'rest_api_verify_ssl' => false,  // CRITICAL: self-signed cert!
    'rest_api_timeout' => 30,

    // SQL Server (alternatywnie)
    'db_host' => '(local)\INSERTGT',
    'db_port' => 1433,
    'db_database' => 'NazwaFirmy',
    'db_username' => 'sa',
    'db_password' => '',
    'db_trust_certificate' => true,

    // Mappings
    'default_warehouse_id' => 1,
    'default_price_type_id' => 0,  // 0-9 = tc_CenaNetto0..9
    'warehouse_mappings' => [],    // PPM ID => Subiekt mag_Id
    'price_group_mappings' => [],  // PPM ID => Subiekt price level (0-9)

    // Flags
    'create_missing_products' => false,  // false dla sql_direct
    'sync_ean' => true,
    'sync_weight' => true,
]
```

---

## Fazy Implementacji

### FAZA 1: Core Service (~2 dni)

**Pliki do utworzenia/modyfikacji:**

| Plik | Akcja | Linii |
|------|-------|-------|
| `app/Services/ERP/SubiektGTService.php` | REFACTOR | ~600 |
| `app/Services/ERP/SubiektGT/SubiektQueryBuilder.php` | CREATE | ~250 |
| `app/Services/ERP/SubiektGT/SubiektDataTransformer.php` | CREATE | ~150 |
| `config/database.php` | MODIFY | +15 |

**Zadania:**
- [ ] 1.1: Utworzyć strukturę folderów `app/Services/ERP/SubiektGT/`
- [ ] 1.2: Implementować `SubiektQueryBuilder` z SQL queries:
  - `getProductById()`, `getProductBySKU()`, `getAllProducts()`
  - `getProductStock()`, `getProductPrices()`
  - `getWarehouses()`, `getPriceTypes()`
- [ ] 1.3: Implementować `SubiektDataTransformer`:
  - `subiektToPPM()` - transformacja danych
  - `ppmToSubiekt()` - odwrotna transformacja
- [ ] 1.4: Refaktorować `SubiektGTService`:
  - `testConnection()` - SELECT @@VERSION
  - `testAuthentication()` - test dostępu do tabel
  - `getSupportedFeatures()` - images: false, variants: false

### FAZA 2: Read Operations (~2 dni)

**Implementacja metod PULL (Subiekt → PPM):**

- [ ] 2.1: `syncProductFromERP()` - pull pojedynczego produktu
- [ ] 2.2: `pullAllProducts()` - batch pull z chunking
- [ ] 2.3: `syncStock()` - pobranie stanów magazynowych
- [ ] 2.4: `syncPrices()` - pobranie cen (wszystkie grupy cenowe)
- [ ] 2.5: Integracja z `ProductErpData` - zapis external_data, last_pull_at
- [ ] 2.6: Integracja z `IntegrationMapping` - mapowanie external_id

### FAZA 3: Write Operations (~2 dni)

**Implementacja metod PUSH (PPM → Subiekt) - opcjonalne:**

Uwaga: Zapis do Subiekt GT wymaga Sfera API lub REST wrapper. W trybie `sql_direct` będzie tylko read-only z walidacją istnienia produktu.

- [ ] 3.1: `syncProductToERP()` - tryb read-only: walidacja + mapping
- [ ] 3.2: `syncAllProducts()` - batch validation
- [ ] 3.3: (Opcjonalnie) REST API wrapper integration dla pełnego PUSH

### FAZA 4: Jobs & Scheduling (~1.5 dnia)

**Pliki do utworzenia:**

| Plik | Akcja |
|------|-------|
| `app/Jobs/ERP/PullProductsFromSubiektGT.php` | CREATE |
| `app/Jobs/ERP/DetectSubiektGTChanges.php` | CREATE |
| `routes/console.php` | MODIFY |

**Zadania:**
- [ ] 4.1: Utworzyć `PullProductsFromSubiektGT`:
  - ShouldQueue, ShouldBeUnique
  - Tryby: 'full', 'incremental', 'stock_only'
  - Change detection przez `tw_DataMod`
  - Progress tracking via SyncJob
- [ ] 4.2: Utworzyć `DetectSubiektGTChanges`:
  - Lekki job sprawdzający COUNT(*) zmian
  - Dispatch `PullProductsFromSubiektGT` jeśli zmiany > 0
- [ ] 4.3: Skonfigurować scheduler:
  - Incremental: co 15 minut
  - Full: co 6 godzin
  - Stock: co 5 minut (opcjonalnie)

### FAZA 5: UI Integration (~1.5 dnia)

**Pliki do modyfikacji:**

| Plik | Zakres |
|------|--------|
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | Step 2 wizard (+60 linii) |
| `app/Http/Livewire/Admin/ERP/ERPManager.php` | $subiektConfig, walidacja |
| `resources/views/livewire/products/management/partials/erp-connection-data.blade.php` | Hide images, variants as products |

**Zadania:**
- [ ] 5.1: Connection Config Form (Step 2 wizard):
  - SQL Server host, port, database
  - Username, password
  - Connection mode selector
- [ ] 5.2: Test Connection Results:
  - Wersja SQL Server
  - Liczba produktów
  - Lista magazynów i grup cenowych
- [ ] 5.3: Warehouse Mapping UI
- [ ] 5.4: Price Group Mapping UI
- [ ] 5.5: ERP Tab w ProductForm:
  - Ukryć sekcję Images dla Subiekt GT
  - Warianty → "Produkty powiązane w Subiekt GT"
  - Stany per-warehouse

### FAZA 6: Testing & Verification (~1 dzień)

- [ ] 6.1: Test connection z prawdziwą bazą Subiekt GT
- [ ] 6.2: Test pull produktów (single + batch)
- [ ] 6.3: Test sync status tracking
- [ ] 6.4: Test scheduler jobs
- [ ] 6.5: Weryfikacja UI w Chrome DevTools
- [ ] 6.6: Test error handling (connection failure, timeout)

---

## Kluczowe Pliki

### Do Implementacji:
```
app/Services/ERP/SubiektGTService.php          # GŁÓWNY - refactor placeholder
app/Services/ERP/SubiektGT/SubiektQueryBuilder.php    # NOWY
app/Services/ERP/SubiektGT/SubiektDataTransformer.php # NOWY
app/Jobs/ERP/PullProductsFromSubiektGT.php     # NOWY
app/Jobs/ERP/DetectSubiektGTChanges.php        # NOWY
```

### Wzorce (do replikacji):
```
app/Services/ERP/BaselinkerService.php         # 2606 linii - wzorzec serwisu
app/Jobs/PullProductsFromPrestaShop.php        # 652 linii - wzorzec pull
app/Jobs/ERP/SyncProductToERP.php              # 307 linii - wzorzec job
```

### Dokumentacja:
```
.claude/skills/subiekt-gt-integration/SKILL.md # SQL queries, table schema
```

---

## SQL Queries Reference

```sql
-- Produkty z cenami i stanami
SELECT t.tw_Id, t.tw_Symbol, t.tw_Nazwa, t.tw_DataMod,
       c.tc_CenaNetto0, c.tc_CenaBrutto0,
       ISNULL(s.st_Stan, 0) as stan
FROM tw__Towar t
LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId
LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = 1
WHERE t.tw_Aktywny = 1

-- Change detection (dla incremental sync)
SELECT tw_Id, tw_Symbol, tw_DataMod
FROM tw__Towar
WHERE tw_DataMod > @last_sync_timestamp AND tw_Aktywny = 1

-- Magazyny
SELECT mag_Id, mag_Symbol, mag_Nazwa FROM sl_Magazyn WHERE mag_Aktywny = 1

-- ✅ NAZWY POZIOMÓW CENOWYCH (ODKRYTE 2026-01-20)
-- Tabela tw_Parametr zawiera nazwy wszystkich 10 poziomów cenowych!
-- UWAGA: twp_NazwaCeny[N] odpowiada tc_CenaNetto[N-1] (offset by 1!)
SELECT TOP 1
    twp_NazwaCeny1 AS PriceLevel0,   -- tc_CenaNetto0 = "Detaliczna"
    twp_NazwaCeny2 AS PriceLevel1,   -- tc_CenaNetto1 = "MRF-MPP"
    twp_NazwaCeny3 AS PriceLevel2,   -- tc_CenaNetto2 = "Szkółka-Komis-Drop"
    twp_NazwaCeny4 AS PriceLevel3,   -- tc_CenaNetto3 = "z magazynu"
    twp_NazwaCeny5 AS PriceLevel4,   -- tc_CenaNetto4 = "Warsztat"
    twp_NazwaCeny6 AS PriceLevel5,   -- tc_CenaNetto5 = "Standard"
    twp_NazwaCeny7 AS PriceLevel6,   -- tc_CenaNetto6 = "Premium"
    twp_NazwaCeny8 AS PriceLevel7,   -- tc_CenaNetto7 = "HuHa"
    twp_NazwaCeny9 AS PriceLevel8,   -- tc_CenaNetto8 = "Warsztat Premium"
    twp_NazwaCeny10 AS PriceLevel9   -- tc_CenaNetto9 = "Pracownik"
FROM tw_Parametr
```

---

## Weryfikacja

Po implementacji sprawdzić:

1. **Connection Test:**
   - [ ] Panel `/admin/integrations` → Add Subiekt GT → Test Connection → Success
   - [ ] Wyświetla wersję SQL, liczbę produktów, magazyny

2. **Pull Products:**
   - [ ] Manual "Sync Now" → produkty pobrane do PPM
   - [ ] SyncJob status tracking działa
   - [ ] ProductErpData.external_data wypełnione

3. **ERP Tab w ProductForm:**
   - [ ] Tab "Subiekt GT" widoczny
   - [ ] Sekcja Images ukryta
   - [ ] Warianty wyświetlane jako osobne produkty
   - [ ] Sync status badges działają

4. **Scheduler:**
   - [ ] DetectSubiektGTChanges uruchamia się co 15 min
   - [ ] PullProductsFromSubiektGT uruchamia się gdy zmiany wykryte

---

## Uwagi

1. **Tryb SQL Direct:** Domyślnie read-only. Zapis do Subiekt GT wymaga Sfera API lub REST wrapper na serwerze Windows.

2. **Zdjęcia:** Subiekt GT nie przechowuje zdjęć - `getSupportedFeatures()['images'] = false`. UI ukrywa sekcję Images.

3. **Warianty:** Każdy wariant PPM to osobny produkt w Subiekt GT. Mapowanie przez SKU rodzica.

4. **Hosting:** PPM jest na Hostido (Linux) - połączenie SQL Server wymaga sterowników `pdo_sqlsrv` lub tunelu.
