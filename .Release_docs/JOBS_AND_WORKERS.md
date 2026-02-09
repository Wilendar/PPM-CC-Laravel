# PPM - Jobs & Workers Documentation

> **Wersja:** 1.7
> **Data:** 2026-02-09
> **Status:** Production Ready
> **Changelog:** Bidirectional UVE Sync - ProductShopData jako single source of truth dla opisow

---

## Spis tresci

1. [Podsumowanie](#1-podsumowanie)
2. [Architektura Queue](#2-architektura-queue)
3. [Scheduled Tasks (Cykliczne Joby)](#3-scheduled-tasks-cykliczne-joby)
4. [Katalog JOBow](#4-katalog-jobow)
5. [Modele Trackingowe](#5-modele-trackingowe)
6. [Konfiguracja](#6-konfiguracja)
7. [Deployment](#7-deployment)
8. [Monitorowanie](#8-monitorowanie)

---

## 1. Podsumowanie

PPM-CC-Laravel wykorzystuje **49 zdefiniowanych JOBow** zorganizowanych w 9 kategorii funkcjonalnych + serwisy sync (SubiektGTService).

### Statystyki

| Kategoria | Ilosc | Glowne funkcje |
|-----------|-------|----------------|
| System | 5 | Backup, Maintenance, Notifications, Reports |
| PrestaShop Sync | 11 | Product/Category/Attribute/Variant sync |
| Media | 3 | Upload, conversion, PS push (Smart Diff Sync) |
| ERP | 7 | Baselinker, Subiekt GT (dynamic sync + variant inheritance), Dynamics |
| VisualEditor | 5 | Description sync, templates |
| Features | 3 | Feature sync to PrestaShop |
| Categories | 1 | Bulk category operations |
| Products | 3 | Category assignment |
| Import/Pull | 3 | Data import (PS, Subiekt GT change detection) |
| **RAZEM** | **49** | |

### Serwisy sync (nie-jobowe)

| Serwis | Przeznaczenie |
|--------|---------------|
| SubiektGTService | Push/Pull produktow + wariantow z dziedziczeniem pol (ETAP_15) |
| ShopVariantService | Mapowanie wariantow PPM ↔ PrestaShop combinations |
| PrestaShop8Client | API client dla PrestaShop Web Services |
| SubiektRestApiClient | REST API client dla sapi.mpptrade.pl |
| **SmartMediaSyncService** | Inteligentny diff-based sync obrazkow do PrestaShop (v1.6) |
| **MediaDiffCalculator** | Oblicza diff desired vs current PS state (v1.6) |
| **MediaSyncService** | Legacy replace-all sync + metody reuse (upload, delete, cover) |

### Nowe w v1.6 (Smart Diff Media Sync)

- **SmartMediaSyncService** - Nowy serwis orkiestrujacy inteligentny sync obrazkow:
  - Zamiast `replaceAllImages()` (delete ALL + re-upload ALL) → diff-based sync
  - **Brak zmian** → SKIP (0 API calls) - najczestszy scenariusz
  - **Nowe zdjecia** → upload TYLKO nowych (stare IDs stabilne)
  - **Usuniete zdjecia** → delete TYLKO usunietych z PS
  - **Zmiana cover** → PATCH cover (1 API call)
  - **Zmiana sort_order** → position update via custom PS module
- **MediaDiffCalculator** - Oblicza diff miedzy desired media state a current PS state:
  - `toUpload` - media bez PS mapping (nowe)
  - `toDelete` - media z PS mapping ale NIE w desired (usuniete, wlacznie z soft-deleted via `withTrashed()`)
  - `unchanged` - media z valid PS mapping i w desired
  - `coverChanged` - detekcja zmiany primary image
  - `orderChanged` - detekcja zmiany sort_order vs `synced_sort_order` w mapping
- **MediaSyncDiff DTO** - Immutable DTO z wynikiem diff (`app/DTOs/Media/MediaSyncDiff.php`)
- **Strategy Toggle** - `SystemSetting::get('media.sync_strategy', 'smart')`:
  - `smart` (default) → SmartMediaSyncService (diff-based)
  - `replace_all` → stara logika replaceAllImages (fallback)
- **synced_sort_order** - Nowe pole w `prestashop_mapping` trackujace sort_order w momencie sync
- **PPM Manager Module** - Custom PrestaShop module `ppmimagemanager` do update position obrazkow
  - Endpoint: `POST /module/ppmimagemanager/api`
  - `PrestaShop8Client::updateImagePositions()` - nowa metoda
  - Graceful fallback: jesli modul nie zainstalowany → skip position update
- **FIX: GalleryTab race condition** - `handleBeforeProductSave()` nie wywoluje juz `pushToPrestaShop()` bezposrednio (eliminacja race condition z sync jobem)
- **FIX: savePendingChangesToShop** - Teraz przekazuje pending media changes z session do sync job (4th param)
- **FIX: Soft-deleted media detection** - `MediaDiffCalculator::findMediaToDelete()` uzywa `withTrashed()` do wykrywania soft-deleted media z PS mapping

### Nowe w v1.7 (Bidirectional UVE Sync + BUG#1-3 fixes)

- **Bidirectional UVE ↔ Textarea Sync** - UVE rendered HTML automatycznie zapisywany do ProductShopData.long_description:
  - UVE `save()` → auto-write do `ProductShopData` (single source of truth)
  - Textarea edycja → wykrywanie zewnetrznych zmian w UVE (`detectExternalEdits()`)
  - `syncVisualToStandard()` teraz persystuje do DB (nie tylko in-memory)
- **ProductTransformer Simplification** - Usuniety Visual Description bypass:
  - `getVisualDescription()` call usuniety z `transformForPrestaShop()`
  - Opis pobierany wylacznie przez `getEffectiveValue()` z ProductShopData
  - ProductShopData = SINGLE SOURCE OF TRUTH dla PrestaShop sync
- **BUG#1 FIX: defaultData sync** - `switchToShop()` teraz synchronizuje pending default edits do `defaultData[]` przy opuszczaniu default tab (walidator `getShopFieldStatusInternal()` poprawnie porownuje)
- **BUG#2 FIX: cross-context save** - `saveCurrentContextOnly()` teraz persystuje pending default changes rowniez przy zapisie z shop context (eliminacja utraty danych przy redirect)
- **BUG#3 FIX: character counts** - `updateCharacterCounts()` wywolywane w `loadProductData()` i `loadDefaultDataToForm()` (inicjalizacja przy mount zamiast 0/800)

### Nowe w v1.5 (ETAP_15 - BusinessPartner + Variant Field Inheritance)

- **BusinessPartner Panel** - Nowy panel `/admin/suppliers` z zakladkami DOSTAWCA/PRODUCENT/IMPORTER/BRAK
- **BusinessPartner Model** - Unified model z type field (supplier/manufacturer/importer) zastepujacy oddzielne tabele
- **ProductForm FK Fields** - 3 nowe dropdowny w formularzu produktu: Dostawca, Producent, Importer (supplier_id, manufacturer_id, importer_id)
- **Variant Field Inheritance (Subiekt GT)** - `buildVariantSyncData()` dziedziczy WSZYSTKIE pola rozszerzone z master produktu:
  - Producent (`manufacturer_id` → `tw_IdProducenta` via BusinessPartner → kh_Id)
  - Dostawca (`supplier_id` → `tw_IdPodstDostawca` via BusinessPartner → kh_Id)
  - Kod CN (`cn_code` → `tw_Pole5`)
  - Material (`material` → `tw_Pole1`)
  - Symbol z wada (`defect_symbol` → `tw_Pole3`)
  - Zastosowanie (`application` → `tw_Pole4`)
  - Sklep internetowy (`shop_internet` → `tw_SklepInternet`)
  - Mechanizm podzielonej platnosci (`split_payment` → `tw_MechanizmPodzielonejPlatnosci`)
  - Kod dostawcy (`supplier_code` → `tw_DostSymbol`)
  - Kod EAN (`ean` → `tw_PodstKodKresk`) - wariant nie ma wlasnego EAN w PPM
- **mapExtendedFields() reuse** - Warianty uzyja tej samej metody co master products (zero duplikacji kodu)
- **resolveBusinessPartnerToSubiektContractor()** - Resolver PPM BusinessPartner ID → Subiekt GT kh_Id (kontrahent)
- **FIX: ProductForm save paths** - manufacturer_id/supplier_id/importer_id dodane do 5 metod zapisu (saveProduct, saveAndClose, quickSave, duplicateProduct, createProduct)

### Nowe w v1.4 (ETAP_14 - Variant Price & Stock Sync)

- **syncVariantPrice()** - Nowa metoda w `SyncShopVariantsToPrestaShopJob` synchronizujaca ceny wariantow do PrestaShop jako `price_impact` (delta = cena_wariantu - cena_bazowa_produktu)
- **syncVariantStock()** - Nowa metoda synchronizujaca stany magazynowe wariantow do PrestaShop via `stock_availables` API
- **getStockForCombination()** - Nowa metoda w `PrestaShop8Client` pobierajaca `stock_available` dla konkretnej kombinacji (id_product_attribute)
- **updateStock() GET-MERGE-PUT** - Przepisany `PrestaShop8Client::updateStock()` na wzorzec GET→MERGE→PUT (PS API wymaga WSZYSTKICH pol w PUT)
- **ShopVariantService** - `mapCombinationsToVariants()` teraz laduje lokalne warianty PPM z cenami/stanami (identyczne dane w PrestaShop Tab i Default Tab)
- **VariantModalsTrait** - Nowe modale do edycji cen i stanow wariantow (klikalne ceny/stany w liscie wariantow)
- **VariantCrudTrait FIX** - `getShopOverridesForDisplay()` poprawione mapowanie `quantity` → `stock` z relacji Eloquent

### Nowe w v1.3 (ETAP_08 FAZA 7 - Extended Fields)

- **Bidirectional Extended Fields Sync** - Pelna synchronizacja rozszerzonych pol
- **pull_basic_data Fields** - 15 pol synchronizowanych (nazwa, opis, waga, EAN, + 9 rozszerzonych)
- **ManufacturerName + SupplierCode** - Producent i Kod dostawcy teraz poprawnie pobierane z Subiekt
- **Boolean Fields** - tw_SklepInternet, tw_MechanizmPodzielonejPlatnosci -> shop_internet, split_payment
- **FIX: pullProductDataFromErp** - Teraz poprawnie wywoluje overrideFormFieldsWithErpData()
- **FIX: subiektToPPM transformer** - Dodane mapowanie extended fields do erp_data

### Nowe w v1.2 (ETAP_08 FAZA 7 - Performance)

- **Batch Parallel HTTP Requests** - `Http::pool()` dla rownoleglego fetch cen/stockow
- **110x Performance Improvement** - 92s → 0.08s (prices), 0.19s (stock) dla 13 SKU
- **Data Transformation** - Konwersja API format → PHP expected format
- **linked_only Mode** - Sync tylko linked produktow (13) zamiast calej bazy Subiekt GT (5000+)
- **Smart Comparison** - Update tylko gdy PPM ≠ Subiekt (tolerancja 0.01 PLN dla cen)
- **REST API Endpoints** - Nowe: `/api/prices/sku/{sku}`, `/api/stock/sku/{sku}`

### Nowe w v1.1 (ETAP_08 FAZA 6)

- **Dynamic ERP Sync Scheduler** - 3 niezalezne czestotliwosci (prices, stock, basic_data)
- **SyncJob Stats Fix** - prawidlowe mapowanie metadata do kolumn UI
- **UI Improvements** - "PPM" uppercase, "← Import" badge dla pull_* jobs

### Modele Trackingowe

| Model | Tabela | Przeznaczenie |
|-------|--------|---------------|
| SyncJob | `sync_jobs` | Kompleksowy tracking synchronizacji |
| JobProgress | `job_progress` | Real-time UI progress bar |
| BackupJob | `backup_jobs` | Status backupow |
| MaintenanceTask | `maintenance_tasks` | Status zadan konserwacyjnych |
| ProductErpData | `product_erp_data` | Status sync ERP per produkt |

---

## 2. Architektura Queue

### 2.1 Sterownik

```
Domyslny: DATABASE (Laravel Queue z tabela jobs)
Shared Hosting: Brak queue worker - uzycie onConnection('sync')
```

### 2.2 Nazwy Kolejek

| Kolejka | Przeznaczenie | Joby |
|---------|---------------|------|
| `default` | Standardowe joby | SendNotificationJob, GenerateReportJob |
| `prestashop_sync` | Batch sync PS | BulkSyncProducts |
| `prestashop_high` | Priorytetowe PS | High priority products |
| `prestashop` | Description sync | SyncDescriptionToPrestaShopJob |
| `erp_high` | Priorytetowe ERP | Featured products |
| `erp_default` | Standardowe ERP | SyncProductToERP, BaselinkerSyncJob |
| `backups-heavy` | Full backup | BackupDatabaseJob (full) |
| `backups-medium` | Files backup | BackupDatabaseJob (files) |
| `backups-light` | DB only backup | BackupDatabaseJob (database) |
| `maintenance-*` | Konserwacja | MaintenanceTaskJob |
| `scheduled-backups` | Planowane backupy | ScheduledBackupJob |

### 2.3 Retry Logic

| JOB | Max Tries | Backoff | Unique |
|-----|-----------|---------|--------|
| SyncProductToPrestaShop | 3 | 30s, 60s, 300s | 1h |
| SyncCategoryToPrestaShop | 3 | 30s, 60s, 300s | 1h |
| SyncProductToERP | 3 | 60s, 180s, 600s | 1h |
| BackupDatabaseJob | 1 | 60s, 300s, 900s | - |
| MaintenanceTaskJob | 2 | 300s, 900s | - |
| BulkSyncProducts | 1 | - | - |

---

## 3. Scheduled Tasks (Cykliczne Joby)

**Lokalizacja:** `routes/console.php`

Wszystkie scheduled tasks sa definiowane w Laravel Scheduler i uruchamiane przez CRON:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 3.1 PrestaShop Data Pull (Shop Tab)

#### prestashop:pull-products-scheduled
```
Harmonogram: Dynamiczny z SystemSettings (domyslnie co 6h)
Warunek: sync.schedule.enabled = true
Job: PullProductsFromPrestaShop

Konfiguracja (Admin > System Settings):
- sync.schedule.frequency: hourly|daily|weekly|every_six_hours
- sync.schedule.hour: godzina dla daily/weekly (0-23)
- sync.schedule.days_of_week: dni dla weekly
- sync.schedule.only_connected: tylko polaczone sklepy
- sync.schedule.skip_maintenance: pomijaj w maintenance mode

Co robi:
1. Pobiera aktywne sklepy z auto_sync_products=true
2. Sprawdza czy nie ma juz pending/running joba (deduplication)
3. Dispatch PullProductsFromPrestaShop dla kazdego sklepu

Dane pobierane:
- Produkty: nazwa, opis, meta dane, waga, wymiary, EAN
- Ceny: specific_prices z PrestaShop -> product_prices
- Stany: stock_availables -> product_stock
- Konflikt detection: porownanie PPM vs PrestaShop

Optymalizacja (2026-01-19):
- date_upd pre-fetch: sprawdza czy produkt zmienil sie w PS
- Produkty bez zmian sa pomijane (skipped counter)
```

### 3.2 Subiekt GT ERP Sync

#### subiekt-gt:change-detection
```
Harmonogram: Co 15 minut
Job: DetectSubiektGTChanges
Timeout: 60s

Co robi:
1. Wykonuje lightweight COUNT query na tw__Towar (tw_DataMod)
2. Jesli zmiany >= threshold, dispatch PullProductsFromSubiektGT
3. Aktualizuje connection health status

Charakterystyka:
- Bardzo szybki (single SQL query)
- Nie blokuje kolejki (queue: default)
- Automatyczny incremental pull przy zmianach
```

#### erp:dynamic-sync (ETAP_08 FAZA 6 - 2026-01-26)
```
Harmonogram: Co 15 minut (sprawdza ktore typy sync powinny sie uruchomic)
Job: PullProductsFromSubiektGT (z roznym mode)
Timeout: 3600s (1h)

NOWA ARCHITEKTURA: 3 niezalezne czestotliwosci sync per ERPConnection:
- price_sync_frequency - Sync cen (tc_CenaNetto1..10)
- stock_sync_frequency - Sync stanow magazynowych (st_Stan)
- basic_data_sync_frequency - Sync danych podstawowych (nazwa, opis)

Dostepne czestotliwosci (ERPConnection::FREQ_*):
- 15_min   -> Co 15 minut
- 30_min   -> Co 30 minut
- hourly   -> Co godzine
- 6_hours  -> Co 6 godzin (domyslnie dla prices/stock)
- daily    -> Codziennie o 2:00 (domyslnie dla basic_data)

Logika schedulera (routes/console.php):
1. Co 15 min sprawdza wszystkie aktywne ERPConnections
2. Dla kazdego typu sync sprawdza czy czestotliwosc odpowiada aktualnej minucie
3. Jesli tak - dispatch PullProductsFromSubiektGT z odpowiednim mode

Przyklad:
- price_sync_frequency = 'hourly' -> sync uruchamiany gdy now()->minute === 0
- stock_sync_frequency = '30_min' -> sync uruchamiany gdy now()->minute % 30 === 0
- basic_data_sync_frequency = 'daily' -> sync uruchamiany o 2:00

Job Types (widoczne w SyncController UI):
- pull_prices    -> Badge "← Import" + "PPM"
- pull_stock     -> Badge "← Import" + "PPM"
- pull_basic_data -> Badge "← Import" + "PPM"

SyncJob Stats Tracking (FIX 2026-01-26):
- total_items: liczba produktow do przetworzenia
- processed_items: przetworzone (successful + skipped)
- successful_items: zaktualizowane produkty
- failed_items: bledy
- duration_seconds: czas wykonania
- progress_percentage: 0-100%

Batch Parallel Fetch (ETAP_08 FAZA 7):
- linked_only mode: sync tylko produktow z ProductErpData
- Http::pool() dla rownoleglych requests (110x szybciej)
- Smart comparison: pomija gdy PPM == Subiekt
- REST API: /api/prices/sku/{sku}, /api/stock/sku/{sku}

Performance (13 linked SKU):
- pull_prices: 0.08s (vs 92s sekwencyjnie)
- pull_stock:  0.19s (vs ~92s sekwencyjnie)
```

#### subiekt-gt:full-sync (LEGACY - zastapione przez erp:dynamic-sync)
```
Harmonogram: Co 6 godzin (zastapione dynamicznym schedulerem)
Job: PullProductsFromSubiektGT
Timeout: 3600s (1h)

UWAGA: Od 2026-01-26 uzywaj erp:dynamic-sync z osobnymi czestotliwosciami!

Co robi:
1. Pobiera wszystkie aktywne polaczenia Subiekt GT
2. Pomija jesli jest pending/running job
3. Dispatch PullProductsFromSubiektGT (mode: full)

Dane pobierane:
- Produkty: Symbol (SKU), Nazwa, Opis, EAN
- Ceny: tc_CenaNetto1..10 (poziomy cenowe)
- Stany: st_Stan z sl_Magazyn
```

### 3.3 Cleanup Tasks

| Task | Harmonogram | Opis |
|------|-------------|------|
| `category-preview:cleanup` | Co godzine | Usuwa expired category preview records |
| `jobs:cleanup-stuck --minutes=30` | Co godzine | Resetuje stuck jobs (pending >30min) |
| `logs:archive --keep-days=30` | Codziennie 00:01 | Archiwizacja starych logow |
| `sync:cleanup` | Codziennie 02:00 | Czysci sync_jobs (opcjonalne) |
| `telescope:prune --hours=48` | Codziennie 03:00 | **KRYTYCZNE:** Telescope entries (roznie do GB!) |
| `price-history:cleanup --days=90` | Codziennie 03:30 | **KRYTYCZNE:** Price history (JSON columns!) |
| `logs:cleanup` | Codziennie 04:00 | sync_logs, integration_logs, failed_jobs |
| `db:health-check --alert` | Codziennie 06:00 | Monitorowanie rozmiaru tabel + alerty |

### 3.4 Queue Worker (Shared Hosting)

```
Harmonogram: Co minute
Komenda: queue:work database --queue=erp_default,erp_high,default,sync --once --max-time=55

Przeznaczenie:
- Shared hosting (Hostido) nie moze uruchomic daemona queue:work
- Scheduler uruchamia worker co minute z --once
- Przetwarza JEDEN job i konczy
- --max-time=55 zapobiega overlapping z kolejnym CRON
```

### 3.5 Konfiguracja SystemSettings

**Admin Panel > System Settings > Sync Schedule (PrestaShop):**

| Klucz | Typ | Domyslnie | Opis |
|-------|-----|-----------|------|
| `sync.schedule.enabled` | bool | true | Wlacz/wylacz auto-sync |
| `sync.schedule.frequency` | enum | every_six_hours | hourly, daily, weekly, every_six_hours |
| `sync.schedule.hour` | int | 2 | Godzina dla daily/weekly (0-23) |
| `sync.schedule.days_of_week` | array | [mon-fri] | Dni dla weekly |
| `sync.schedule.only_connected` | bool | true | Tylko sklepy z connection_status=connected |
| `sync.schedule.skip_maintenance` | bool | true | Pomijaj w maintenance mode |

**Media Sync Strategy (v1.6):**

| Klucz | Typ | Domyslnie | Opis |
|-------|-----|-----------|------|
| `media.sync_strategy` | enum | smart | `smart` = diff-based, `replace_all` = legacy delete-all/re-upload |

**Zmiana strategii:**
```php
SystemSetting::set('media.sync_strategy', 'replace_all'); // fallback
SystemSetting::set('media.sync_strategy', 'smart');       // default
```

**PPM Manager Module (PrestaShop - position updates):**

| Klucz | Lokalizacja | Opis |
|-------|-------------|------|
| API Key | PS Module Config | Generowany przy instalacji modulu `ppmimagemanager` |
| Endpoint | `POST /module/ppmimagemanager/api` | Update image positions + cache clear |

**ERPConnection Sync Frequencies (ETAP_08 FAZA 6 - Subiekt GT):**

Kazde ERPConnection ma 3 niezalezne czestotliwosci sync:

| Kolumna | Typ | Domyslnie | Opis |
|---------|-----|-----------|------|
| `price_sync_frequency` | enum | 6_hours | Czestotliwosc sync cen |
| `stock_sync_frequency` | enum | 6_hours | Czestotliwosc sync stanow |
| `basic_data_sync_frequency` | enum | daily | Czestotliwosc sync danych podstawowych |

**Dostepne wartosci (ERPConnection::FREQ_*):**

| Stala | Wartosc | Kiedy uruchamiany |
|-------|---------|-------------------|
| `FREQ_15_MIN` | '15_min' | Co 15 minut |
| `FREQ_30_MIN` | '30_min' | Co 30 minut (minute % 30 === 0) |
| `FREQ_HOURLY` | 'hourly' | Co godzine (minute === 0) |
| `FREQ_6_HOURS` | '6_hours' | Co 6 godzin (hour % 6 === 0, minute === 0) |
| `FREQ_DAILY` | 'daily' | Codziennie o 2:00 |

**Konfiguracja w Admin Panel > ERP Manager:**
- Edytuj ERPConnection
- Ustaw osobne czestotliwosci dla cen, stanow i danych podstawowych
- Scheduler automatycznie dispatch'uje odpowiednie joby

### 3.6 Diagram Przeplywu Danych

```
                    SCHEDULER (CRON co minute)
                              |
        +---------------------+---------------------+
        |                     |                     |
  [PrestaShop Pull]    [Subiekt GT]          [Cleanup]
   (co 6h domyslnie)   (co 15min detect)     (co godzine/dzien)
        |                     |
        v                     v
+------------------+   +------------------+
| PullProductsFrom |   | DetectSubiektGT  |
| PrestaShop       |   | Changes          |
+------------------+   +------------------+
        |                     |
        v                     v (jesli zmiany)
+------------------+   +------------------+
| ProductShopData  |   | PullProductsFrom |
| (Shop Tab)       |   | SubiektGT        |
+------------------+   +------------------+
        |                     |
        v                     v
+------------------+   +------------------+
| product_prices   |   | ProductErpData   |
| product_stock    |   | (ERP Tab)        |
+------------------+   +------------------+
```

---

## 4. Katalog JOBow

### 4.1 System Jobs

#### BackupDatabaseJob
```
Sciezka: app/Jobs/BackupDatabaseJob.php
Kolejka: backups-heavy|medium|light (dynamiczna)
Timeout: 1800s (30 min)
Tries: 1

Przeznaczenie:
- Wykonuje backup bazy danych lub plikow
- Tworzy archiwum ZIP
- Aktualizuje BackupJob model

Dane wejsciowe:
- BackupJob $backupJob

Serwisy:
- BackupService
```

#### MaintenanceTaskJob
```
Sciezka: app/Jobs/MaintenanceTaskJob.php
Kolejka: maintenance-heavy|medium|light (dynamiczna)
Timeout: 300-7200s (dynamiczny)
Tries: 2

Przeznaczenie:
- Optymalizacja bazy danych
- Czyszczenie logow i cache
- Przebudowa indeksow

Dane wejsciowe:
- MaintenanceTask $task

Typy zadan:
- DB_OPTIMIZATION -> heavy (7200s)
- INDEX_REBUILD -> heavy (7200s)
- LOG_CLEANUP -> medium (1800s)
- FILE_CLEANUP -> medium (1800s)
- CACHE_CLEANUP -> light (300s)
- SECURITY_CHECK -> light (300s)
- STATS_UPDATE -> light (300s)
```

#### ScheduledBackupJob
```
Sciezka: app/Jobs/ScheduledBackupJob.php
Kolejka: scheduled-backups
Timeout: 7200s (2h)
Tries: 2

Przeznaczenie:
- Automatyczne zaplanowane backupy
- Uruchamiane przez Laravel Scheduler
```

#### SendNotificationJob
```
Sciezka: app/Jobs/SendNotificationJob.php
Kolejka: default

Przeznaczenie:
- Wysylanie emaili z notyfikacjami admin
```

#### GenerateReportJob
```
Sciezka: app/Jobs/GenerateReportJob.php
Kolejka: default

Przeznaczenie:
- Generowanie raportow systemowych
```

---

### 4.2 PrestaShop Sync Jobs

#### SyncProductToPrestaShop (CORE)
```
Sciezka: app/Jobs/PrestaShop/SyncProductToPrestaShop.php
Kolejka: default
Timeout: 300s
Tries: 3
Backoff: 30s, 60s, 300s
Unique: 1h (product_id + shop_id)

Przeznaczenie:
- Synchronizacja pojedynczego produktu PPM -> PrestaShop
- Obsluga pending media changes
- Progress tracking w UI

Dane wejsciowe:
- Product $product
- PrestaShopShop $shop
- ?int $userId (NULL = SYSTEM)
- array $pendingMediaChanges
- ?string $preGeneratedJobId

Wywolanie z:
- ProductForm::syncToPrestaShop()
- BulkSyncProducts dispatcher
- CompatibilityManagement panel

Opis Source (v1.7):
- ProductShopData.long_description = SINGLE SOURCE OF TRUTH
- UVE auto-writes rendered HTML do ProductShopData
- ProductTransformer uzywa getEffectiveValue() (nie getVisualDescription())
- Textarea edits + UVE edits -> oba trafiaja do ProductShopData

Tracking:
- Tworzy SyncJob rekord
- Updates JobProgress
- Updates ProductShopData.sync_status
```

#### BulkSyncProducts
```
Sciezka: app/Jobs/PrestaShop/BulkSyncProducts.php
Kolejka: prestashop_sync
Timeout: 300s
Tries: 1

Przeznaczenie:
- Batch dispatcher dla wielu produktow
- Sortuje po priority (high -> normal -> low)
- Uzywa Bus::batch() z callbacks

Dane wejsciowe:
- Collection $products
- PrestaShopShop $shop
- string $batchName
- ?int $userId
- string $syncMode (full_sync, prices_only, stock_only, descriptions_only, categories_only)

Sync Modes:
- full_sync: Wszystkie dane produktu
- prices_only: Tylko ceny
- stock_only: Tylko stany magazynowe
- descriptions_only: Tylko opisy
- categories_only: Tylko kategorie
```

#### SyncCategoryToPrestaShop
```
Sciezka: app/Jobs/PrestaShop/SyncCategoryToPrestaShop.php
Kolejka: default
Timeout: 300s
Tries: 3
Backoff: 30s, 60s, 300s
Unique: 1h

Przeznaczenie:
- Synchronizacja kategorii z hierarchia
- Tworzy/aktualizuje kategorie w PrestaShop
```

#### BulkPullProducts
```
Sciezka: app/Jobs/PrestaShop/BulkPullProducts.php
Kolejka: prestashop_sync
Timeout: 300s
Tries: 1

Przeznaczenie:
- Pobiera jeden produkt ze WSZYSTKICH sklepow
- Uzywa Bus::batch()
```

#### SyncShopVariantsToPrestaShopJob (ETAP_14)
```
Sciezka: app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php
Kolejka: default
Timeout: 300s
Tries: 3

Przeznaczenie:
- Synchronizacja wariantow produktu (combinations) do PrestaShop
- Obsluga operacji ADD/OVERRIDE/DELETE per wariant
- Sync cen jako price_impact (delta od ceny bazowej produktu)
- Sync stanow magazynowych via stock_availables API

Dane wejsciowe:
- int $productId
- int $shopId
- ?string $preGeneratedJobId

Metody pomocnicze (FIX 2026-01-29):
- syncVariantPrice() - Oblicza price_impact = variant_price - base_price,
  aktualizuje combination.price w PrestaShop
- syncVariantStock() - Pobiera stock_available dla combination,
  aktualizuje ilosc przez GET-MERGE-PUT pattern

Wylanie z handleAddOperation() i handleOverrideOperation():
1. Tworzenie/aktualizacja combination (atrybuty, obrazy)
2. syncVariantPrice() - sync ceny
3. syncVariantStock() - sync stanu
4. markAsSynced()

Serwisy:
- PrestaShop8Client::getStockForCombination()
- PrestaShop8Client::updateStock() (GET-MERGE-PUT)
- PrestaShop8Client::updateCombination()
```

#### Inne PrestaShop Jobs
- `DeleteProductFromPrestaShop` - Usuwanie produktu
- `BulkImportProducts` - Batch import z PS
- `AnalyzeMissingCategories` - Analiza brakujacych kategorii
- `PullSingleProductFromPrestaShop` - Pull jednego produktu
- `SyncProductsJob` - Legacy batch sync
- `ExpirePendingCategoryPreview` - Wygasanie preview kategorii

---

### 4.3 Media Jobs

#### Smart Media Sync Architecture (v1.6)

```
FLOW: ProductForm save -> SyncProductToPrestaShop job -> ProductSyncStrategy
      -> syncMediaIfEnabled() -> SmartMediaSyncService / replaceAllImages (fallback)

Strategy Toggle: SystemSetting::get('media.sync_strategy', 'smart')
  'smart'       -> SmartMediaSyncService (diff-based, default)
  'replace_all' -> MediaSyncService::replaceAllImages() (legacy fallback)

Smart Sync Flow:
1. MediaDiffCalculator::calculateDiff(desired, shopId) -> MediaSyncDiff
2. if diff->isEmpty() -> SKIP (0 API calls, log "No changes detected")
3. toDelete  -> deleteProductImage() per image + clear mapping
4. toUpload  -> ensureJpegForUpload() + uploadProductImage() + save mapping
5. coverChanged -> setProductImageCover()
6. orderChanged -> updateImagePositions() via ppmimagemanager module

Serwisy:
- SmartMediaSyncService (app/Services/Media/SmartMediaSyncService.php)
- MediaDiffCalculator (app/Services/Media/MediaDiffCalculator.php)
- MediaSyncDiff DTO (app/DTOs/Media/MediaSyncDiff.php)
- MediaSyncService (app/Services/Media/MediaSyncService.php) - reuse metod

Reuse z MediaSyncService:
- ensureJpegForUpload() - WebP -> JPEG conversion
- cleanupTempJpeg() - Cleanup temp files
- throttle() - Rate limiting 500ms
- getPrestaShopProductId() - PS product ID lookup

Reuse z PrestaShop8Client:
- deleteProductImage() - Delete single (404=success)
- uploadProductImage() - Upload + deprecation fallback
- setProductImageCover() - PATCH cover
- updateImagePositions() - NEW: via ppmimagemanager module

Edge Cases:
- PS image usuniety zewnetrznie -> 404 przy delete = sukces, wyczysc mapping
- Soft-deleted media -> withTrashed() wykrywa i kasuje z PS
- Race condition -> mutex lock per product (istniejacy pattern)
- PS module nie zainstalowany -> graceful fail, skip position update
- Pierwszy sync -> toUpload=ALL, toDelete=empty
```

#### PushMediaToPrestaShop
```
Sciezka: app/Jobs/Media/PushMediaToPrestaShop.php
Kolejka: default
Timeout: 300s
Tries: 3
Connection: sync (SYNCHRONOUS!)

Przeznaczenie:
- Upload obrazkow do PrestaShop API (direct push z GalleryTab)
- Sortuje: is_primary -> sort_order
- Generuje UUID dla sync connections

UWAGA: onConnection('sync') = natychmiastowe wykonanie
(wymagane na shared hosting bez queue worker)

Dane wejsciowe:
- Product $product
- PrestaShopShop $shop
- array $mediaIds
```

#### ProcessMediaUpload
```
Sciezka: app/Jobs/Media/ProcessMediaUpload.php
Kolejka: default
Timeout: 120s
Tries: 3

Przeznaczenie:
- Generowanie thumbnails
- Konwersja do WebP
- Optymalizacja rozmiaru
```

#### BulkMediaUpload
```
Sciezka: app/Jobs/Media/BulkMediaUpload.php
Kolejka: default
Timeout: 600s
Tries: 1

Przeznaczenie:
- Batch upload z folderu
- Progress tracking
```

---

### 4.4 ERP Jobs

#### SyncProductToERP (CORE)
```
Sciezka: app/Jobs/ERP/SyncProductToERP.php
Kolejka: erp_high|erp_default
Timeout: 300s
Tries: 3
Backoff: 60s, 180s, 600s
Unique: 1h

Przeznaczenie:
- Synchronizacja produktu PPM -> ERP
- Obsluguje: Baselinker, Subiekt GT, Dynamics
- Subiekt GT: warianty traktowane jako oddzielne produkty (tw__Towar)
- Subiekt GT: warianty dziedzicza pola z master produktu (ETAP_15)

Dane wejsciowe:
- Product $product
- ERPConnection $erpConnection
- ?SyncJob $syncJob
- array $syncOptions:
  * stock_columns: ['quantity', 'minimum']
  * sync_prices: true/false
  * sync_stock: true/false

Subiekt GT Variant Sync (ETAP_15):
- Wywoluje SubiektGTService::syncProductVariantsToSubiekt()
- Warianty tworzone/aktualizowane jako osobne tw__Towar w Subiekt
- tw_Pole8 = parent_sku (link do master produktu)
- Warianty dziedzicza z master: producent, dostawca, KodCN, material,
  symbol z wada, zastosowanie, shop_internet, split_payment,
  kod dostawcy, EAN

Tracking:
- Updates ProductErpData.sync_status
- Creates/updates SyncJob
```

#### SyncAllProductsToERP
```
Sciezka: app/Jobs/ERP/SyncAllProductsToERP.php
Kolejka: erp_default
Timeout: 600s
Tries: 3

Przeznaczenie:
- Bulk sync wszystkich produktow
- Resumable (resumeFromSku)
- Batch processing (100 per chunk)

Rate Limiting:
- 1s per product
- Memory check: stop >256MB
```

#### BaselinkerSyncJob
```
Sciezka: app/Jobs/ERP/BaselinkerSyncJob.php
Kolejka: erp_default
Timeout: 600s
Tries: 3

Przeznaczenie:
- Orchestrator dla Baselinker
- Obsluguje: full, products, stock, prices, pull

Rate Limiting:
- 0.1s between products
- Progress update co 10 items
```

#### SubiektGTSyncJob
```
Sciezka: app/Jobs/ERP/SubiektGTSyncJob.php
Kolejka: erp_default
Status: PLACEHOLDER (sync odbywa sie przez SyncProductToERP + SubiektGTService)

Przeznaczenie:
- Legacy placeholder - zawsze failuje z komunikatem "not yet implemented"
- FAKTYCZNA synchronizacja odbywa sie przez:
  * SyncProductToERP -> SubiektGTService::pushProductToSubiekt()
  * PullProductsFromSubiektGT -> SubiektGTService::pullProductDataFromErp()

UWAGA: Caly sync Subiekt GT jest obslugiwany przez SubiektGTService
(~4700 linii), NIE przez ten job placeholder.
```

#### SubiektGTService - Variant Sync Methods (ETAP_15)
```
Sciezka: app/Services/ERP/SubiektGTService.php

syncProductVariantsToSubiekt(ERPConnection, Product):
- Iteruje warianty produktu master (is_variant_master = true)
- Dla kazdego wariantu: buildVariantSyncData() -> createProduct/updateProductBySku
- Sprawdza istnienie wariantu w Subiekt (productExists)
- tw_Pole8 = parent_sku (link do master)

buildVariantSyncData(ProductVariant, parentSku, ?Product, config):
- Pola wlasne wariantu: name, pole8 (parent link), is_active
- Pola dziedziczone z master (via mapExtendedFields()):
  * manufacturer_id -> manufacturer_contractor_id (kh_Id w Subiekt)
  * supplier_id -> supplier_contractor_id (kh_Id w Subiekt)
  * cn_code -> pole5 (tw_Pole5)
  * material -> pole1 (tw_Pole1)
  * defect_symbol -> pole3 (tw_Pole3)
  * application -> pole4 (tw_Pole4)
  * shop_internet -> shop_internet (tw_SklepInternet)
  * split_payment -> split_payment (tw_MechanizmPodzielonejPlatnosci)
  * supplier_code -> supplier_code (tw_DostSymbol)
- EAN z master produktu (wariant nie ma wlasnego EAN w PPM)
- Reuse: mapExtendedFields() - ta sama metoda co dla master products

resolveBusinessPartnerToSubiektContractor(int bpId, config):
- Zamienia PPM BusinessPartner.id na Subiekt kh_Id (kontrahent)
- Uzywa business_partner.subiekt_contractor_id jesli ustawione
- Fallback: wyszukiwanie po nazwie w kh__Kontrahent

OGRANICZENIA Subiekt GT:
- tw_IdPodstDostawca (supplier) - dostepne
- tw_IdProducenta (manufacturer) - dostepne
- Importer - BRAK odpowiednika w Subiekt GT
```

#### Inne ERP Jobs
- `DynamicsSyncJob` - Microsoft Dynamics
- `PullProductFromERP` - Pull z ERP do PPM
- `DetectSubiektGTChanges` - Detekcja zmian w Subiekt

#### SubiektRestApiClient - Batch Methods (ETAP_08 FAZA 7)
```
Sciezka: app/Services/ERP/SubiektGT/SubiektRestApiClient.php

Batch Parallel Methods:
- batchFetchPricesBySku(array $skus): array
- batchFetchStockBySku(array $skus): array

Implementacja (Http::pool()):
Http::pool(fn(Pool $pool) =>
    collect($skus)->map(fn($sku) =>
        $pool->as($sku)
             ->withHeaders(['X-API-Key' => $apiKey])
             ->timeout(30)
             ->get("{$baseUrl}/api/prices/sku/{$sku}")
    )->all()
);

Data Transformation:
// Prices: API -> PHP
[{priceLevel:1, priceNet:222, priceGross:273}]
    -> [1 => ['net' => 222.0, 'gross' => 273.0]]

// Stock: API -> PHP
[{warehouseId:1, quantity:50, reserved:5}]
    -> [1 => ['quantity' => 50.0, 'reserved' => 5.0, 'min' => 0.0, 'max' => 0.0]]

REST API Endpoints (sapi.mpptrade.pl):
- GET /api/prices/sku/{sku} - Wszystkie poziomy cenowe dla SKU
- GET /api/stock/sku/{sku}  - Stany magazynowe dla SKU

Performance Metrics:
- 13 SKU batch fetch: 0.08s (prices), 0.19s (stock)
- Sequential fetch: ~92s (110x wolniej)
- Parallel efficiency: ~95% (minimal overhead)
```

#### Variant Field Inheritance Map (ETAP_15 - PPM → Subiekt GT)
```
Warianty w Subiekt GT sa oddzielnymi produktami (tw__Towar).
Dziedzicza WSZYSTKIE pola rozszerzone z master produktu PPM.

Mapowanie pol (buildVariantSyncData -> mapExtendedFields -> buildProductWriteBody):

| PPM Product Field  | PHP Key                    | Subiekt GT Column              | API PascalCase              |
|--------------------|----------------------------|--------------------------------|-----------------------------|
| name (variant own) | name                       | tw_Nazwa                       | Name                        |
| sku (variant own)  | sku                        | tw_Symbol                      | (URL param)                 |
| is_active (own)    | is_active                  | tw_Aktywny                     | IsActive                    |
| --- pole8 link     | pole8                      | tw_Pole8                       | Pole8                       |
| ean (master)       | ean                        | tw_PodstKodKresk               | Ean                         |
| material (master)  | pole1                      | tw_Pole1                       | Pole1                       |
| defect_symbol (m)  | pole3                      | tw_Pole3                       | Pole3                       |
| application (m)    | pole4                      | tw_Pole4                       | Pole4                       |
| cn_code (master)   | pole5                      | tw_Pole5                       | Pole5                       |
| supplier_code (m)  | supplier_code              | tw_DostSymbol                  | SupplierCode                |
| shop_internet (m)  | shop_internet              | tw_SklepInternet               | ShopInternet                |
| split_payment (m)  | split_payment              | tw_MechanizmPodzielonejPlatnosci | SplitPayment              |
| supplier_id (m)    | supplier_contractor_id     | tw_IdPodstDostawca             | SupplierContractorId        |
| manufacturer_id(m) | manufacturer_contractor_id | tw_IdProducenta                | ManufacturerContractorId    |
| importer_id (m)    | --- BRAK w Subiekt GT ---  | ---                            | ---                         |

(m) = dziedziczone z master produktu
Resolver: PPM BusinessPartner.id -> subiekt_contractor_id -> kh_Id w kh__Kontrahent

Implementacja:
- SubiektGTService::buildVariantSyncData() (linia ~4401)
- SubiektGTService::mapExtendedFields() (linia ~3413)
- SubiektRestApiClient::buildProductWriteBody() (linia ~485)
```

---

### 4.5 VisualEditor Jobs

#### SyncDescriptionToPrestaShopJob
```
Sciezka: app/Jobs/VisualEditor/SyncDescriptionToPrestaShopJob.php
Kolejka: prestashop
Timeout: 120s

Przeznaczenie:
- Render blocks -> HTML
- Sync do PrestaShop
```

#### Description Flow (v1.7 - Bidirectional Sync)
```
UVE save() → ProductDescription::updateOrCreate() → auto-write ProductShopData.long_description
                                                          ↓
ProductForm textarea → wire:model.live → ProductShopData.long_description
                                                          ↓
SyncProductToPrestaShop → ProductTransformer::getEffectiveValue() → PrestaShop API

ProductShopData = SINGLE SOURCE OF TRUTH (nie ProductDescription.rendered_html)
```

#### Inne VisualEditor Jobs
- `BulkSyncDescriptionsJob` - Batch sync opisow
- `BulkApplyTemplateJob` - Aplikowanie szablonow
- `BulkExportDescriptionsJob` - Export opisow
- `BulkImportDescriptionsJob` - Import opisow
- `SyncProductCssJob` - Sync CSS

---

### 4.6 Features Jobs

#### SyncFeaturesJob
```
Sciezka: app/Jobs/Features/SyncFeaturesJob.php
Timeout: 600s
Tries: 3
Batch size: 50

Przeznaczenie:
- Batch feature sync do PrestaShop

Rate Limiting:
- 500ms miedzy chunkami
- Progress update co 10 produktow
```

#### Inne Features Jobs
- `BulkAssignFeaturesJob` - Przypisanie features
- `ImportFeaturesFromPSJob` - Import z PS

---

### 4.7 Categories Jobs

- `BulkDeleteCategoriesJob` - Bulk delete kategorii

---

### 4.8 Products Jobs

- `BulkAssignCategories` - Przypisanie kategorii
- `BulkRemoveCategories` - Usuniecie kategorii
- `BulkMoveCategories` - Przeniesienie produktow

---

### 4.9 Import/Pull Jobs

#### PullProductsFromPrestaShop
```
Sciezka: app/Jobs/PullProductsFromPrestaShop.php
Kolejka: default
Timeout: 300s
Tries: 3
Unique: 1h (shop_id)

Przeznaczenie:
- Pobieranie produktow z PrestaShop do PPM (Shop Tab)
- Aktualizacja ProductShopData, product_prices, product_stock

Dane wejsciowe:
- PrestaShopShop $shop

Tracking:
- Tworzy SyncJob z job_type='import_products'
- Updates total_items, processed_items, successful_items
```

#### PullProductsFromSubiektGT
```
Sciezka: app/Jobs/ERP/PullProductsFromSubiektGT.php
Kolejka: erp-sync
Timeout: 3600s (1h)
Tries: 3
Backoff: 60s, 300s, 600s
Unique: 1h (connection_id + mode)

Przeznaczenie:
- Pobieranie produktow z Subiekt GT do PPM (ERP Tab)
- Obsluguje 4 tryby sync (mode parameter)

Dane wejsciowe:
- int $connectionId - ERPConnection ID
- string $mode - 'full', 'incremental', 'stock_only', 'prices', 'stock', 'basic_data'
- ?string $since - Timestamp dla incremental (ISO 8601)
- int $limit - Max produktow (default 5000)
- int $batchSize - Chunk size (default 100)
- ?int $syncJobId - SyncJob ID dla trackingu

Job Types (ETAP_08 FAZA 6):
- pull_prices     -> Sync tylko cen (tc_CenaNetto1..10)
- pull_stock      -> Sync tylko stanow (st_Stan)
- pull_basic_data -> Sync danych podstawowych (pelna lista ponizej)

### PULL_BASIC_DATA - Szczegolowa lista pol (ETAP_08 FAZA 7) ###

**ProductErpData (podstawowe):**
| Subiekt GT | PPM Field | Opis |
|------------|-----------|------|
| tw_Nazwa | name | Nazwa produktu |
| tw_NazwaFiskalna | short_description | Nazwa fiskalna (max 40 znakow) |
| tw_Opis | long_description | Opis pelny |
| tw_WagaBrutto | weight | Waga brutto |
| tw_KodKreskowy | ean | Kod EAN |
| tw_Aktywny | is_active | Czy aktywny |

**Product (rozszerzone pola):**
| Subiekt GT | PPM Field | Opis |
|------------|-----------|------|
| tw_Pole1 | material | Material produktu |
| tw_Pole3 | defect_symbol | Symbol z wada |
| tw_Pole4 | application | Zastosowanie |
| tw_Pole5 | cn_code | Kod CN (celny) |
| tw_Uwagi | notes | Uwagi/notatki |
| ManufacturerName (JOIN) | manufacturer | Nazwa producenta |
| tw_DostSymbol | supplier_code | Kod dostawcy |
| tw_SklepInternet | shop_internet | Widocznosc w sklepie internetowym |
| tw_MechanizmPodzielonejPlatnosci | split_payment | Mechanizm podzielonej platnosci |

**Product FK (BusinessPartner - ETAP_15):**
| Subiekt GT | PPM Field | Opis |
|------------|-----------|------|
| tw_IdPodstDostawca (kh_Id) | supplier_id (BusinessPartner) | Dostawca (kontrahent w Subiekt) |
| tw_IdProducenta (kh_Id) | manufacturer_id (BusinessPartner) | Producent (kontrahent w Subiekt) |
| --- BRAK --- | importer_id (BusinessPartner) | Importer (brak odpowiednika w Subiekt GT) |

**PUSH:** `SubiektGTService::mapExtendedFields()` + `resolveBusinessPartnerToSubiektContractor()`
**PULL:** `SubiektGTService::updateProductBasicDataFromErp()`

### BATCH PARALLEL FETCH (ETAP_08 FAZA 7) ###

Architektura (linked_only mode):
1. Pobiera tylko produkty z linked ProductErpData (13 zamiast 5000+)
2. Batch parallel HTTP requests przez Laravel Http::pool()
3. Data transformation: API format -> PHP expected format
4. Smart comparison: update tylko gdy roznice

Performance (13 linked SKU):
- Prices: 0.08s (vs 92s sekwencyjnie) = 110x szybciej
- Stock:  0.19s (vs ~92s sekwencyjnie) = ~480x szybciej

REST API Endpoints (sapi.mpptrade.pl):
- /api/prices/sku/{sku} -> Ceny dla SKU (11 poziomow cenowych)
- /api/stock/sku/{sku}  -> Stany magazynowe dla SKU

Data Transformation (SubiektRestApiClient):
- Prices API: [{priceLevel:1, priceNet:222}] -> [1 => ['net'=>222, 'gross'=>273]]
- Stock API:  [{warehouseId:1, quantity:50}] -> [1 => ['quantity'=>50, 'reserved'=>0]]

Smart Comparison (tolerancja):
- Ceny: |PPM - Subiekt| <= 0.01 PLN = skip (bez update)
- Stock: PPM == Subiekt = skip
- Logowanie: "Skipping price update - values identical"

Linked Only Mode:
- Pobiera produkty z: ProductErpData::where('erp_connection_id', $id)
- Nie skanuje calej bazy Subiekt GT (5000+ produktow)
- Sync dotyczy tylko produktow z ustawionym external_id

### END BATCH PARALLEL FETCH ###

SyncJob Stats Mapping (FIX 2026-01-26):
- metadata['total'] -> total_items
- metadata['imported'] + metadata['updated'] -> successful_items
- metadata['imported'] + metadata['updated'] + metadata['skipped'] -> processed_items
- metadata['duration_seconds'] -> duration_seconds
- Automatyczne progress_percentage (0-100%)

UI Display:
- Badge "PPM" (uppercase, nie "Ppm")
- Badge "← Import" dla job_type zaczynajacych sie od 'pull_'

Tracking:
- Updates SyncJob.status, total_items, processed_items, successful_items
- Updates ERPConnection.last_sync_at, next_scheduled_sync
- Logs to IntegrationLog
```

#### DetectSubiektGTChanges
```
Sciezka: app/Jobs/ERP/DetectSubiektGTChanges.php
Kolejka: default
Timeout: 60s
Tries: 3

Przeznaczenie:
- Lightweight change detection w Subiekt GT
- Dispatch incremental pull jesli zmiany wykryte

Dane wejsciowe:
- int $connectionId - ERPConnection ID
```

---

## 5. Modele Trackingowe

### 5.1 SyncJob

```php
// Tabela: sync_jobs

// Typy jobow
const JOB_PRODUCT_SYNC = 'product_sync';
const JOB_CATEGORY_SYNC = 'category_sync';
const JOB_MEDIA_SYNC = 'media_sync';
const JOB_FEATURE_SYNC = 'feature_sync';
const JOB_ERP_SYNC = 'erp_sync';

// ERP Pull Job Types (ETAP_08 FAZA 6)
// Uzywane przez PullProductsFromSubiektGT z dynamicznym schedulerem
'pull_prices'     // Sync cen z ERP
'pull_stock'      // Sync stanow magazynowych z ERP
'pull_basic_data' // Sync danych podstawowych z ERP
'import_products' // Pull z PrestaShop

// Statusy
const STATUS_PENDING = 'pending';
const STATUS_RUNNING = 'running';
const STATUS_COMPLETED = 'completed';
const STATUS_FAILED = 'failed';
const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

// Zrodla/Cele
const SOURCE_PPM = 'PPM';
const SOURCE_PRESTASHOP = 'PRESTASHOP';
const SOURCE_ERP_BASELINKER = 'ERP_BASELINKER';
const SOURCE_ERP_SUBIEKT = 'ERP_SUBIEKT';
const SOURCE_ERP_DYNAMICS = 'ERP_DYNAMICS';

// Kluczowe pola
- job_id: UUID (Laravel Job ID)
- job_type: string
- status: string
- source_type, target_type: string
- total_items, processed_items, successful_items, failed_items: int
- user_id: ?int (who triggered)
- error_message, error_details: text
- memory_peak_mb, cpu_time_seconds: float
- started_at, completed_at: timestamp

// Metody
start(): void
updateProgress(int $processed, int $successful, int $failed): void
complete(): void
completeWithErrors(): void
fail(string $message, ?array $details): void
updatePerformanceMetrics(): void
```

### 5.2 JobProgress

```php
// Tabela: job_progress

// Typy jobow
- import, sync, export
- category_delete, bulk_export
- stock_sync, price_sync
- media_push, erp_import

// Statusy
const STATUS_PENDING = 'pending';
const STATUS_RUNNING = 'running';
const STATUS_COMPLETED = 'completed';
const STATUS_FAILED = 'failed';
const STATUS_AWAITING_USER = 'awaiting_user';
const STATUS_CANCELLED = 'cancelled';

// Kluczowe pola
- job_id: string (Laravel Queue job ID)
- job_type: string
- shop_id: ?int
- user_id: ?int
- status: string
- current_count, total_count: int
- error_count: int
- error_details: json
- metadata: json
- action_button: json
- started_at, completed_at: timestamp

// Atrybuty computed
progress_percentage: int (0-100)
duration_seconds: int

// Metody
updateProgress(int $current, ?array $errors): void
updateStatus(string $status): void
markCompleted(?array $metadata): void
markFailed(string $message, ?array $details): void
setActionButton(string $action, string $label, ?string $route, ?array $params): void
updateMetadata(array $data): void
```

### 5.3 ProductErpData

```php
// Tabela: product_erp_data

// Statusy sync
const STATUS_PENDING = 'pending';
const STATUS_SYNCING = 'syncing';
const STATUS_SYNCED = 'synced';
const STATUS_ERROR = 'error';

// Kierunki sync
const DIRECTION_BIDIRECTIONAL = 'bidirectional';
const DIRECTION_PUSH_ONLY = 'push_only';
const DIRECTION_PULL_ONLY = 'pull_only';

// Kluczowe pola
- product_id: int (FK)
- erp_connection_id: int (FK)
- external_id: string (ID w ERP)
- sync_status: string
- sync_direction: string
- pending_fields: json
- last_sync_at, last_push_at: timestamp
- error_message: text
```

---

## 6. Konfiguracja

### 6.1 config/queue.php

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',  // Natychmiastowe wykonanie
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
    ],

    'failed' => [
        'driver' => 'database-uuids',
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
```

### 6.2 .env

```env
QUEUE_CONNECTION=database
```

### 6.3 Tabele bazodanowe

```sql
-- Laravel Queue tables
jobs (id, queue, payload, attempts, reserved_at, available_at, created_at)
failed_jobs (id, uuid, connection, queue, payload, exception, failed_at)
job_batches (id, name, total_jobs, pending_jobs, failed_jobs, ...)

-- Custom PPM tables
sync_jobs (...)
job_progress (...)
backup_jobs (...)
maintenance_tasks (...)
product_erp_data (...)
```

---

## 7. Deployment

### 7.1 Hostido Shared Hosting

**Ograniczenia:**
- Brak queue worker (brak stale uruchomionego procesu)
- Brak Node.js
- Tylko database driver

**Rozwiazania:**

1. **Synchroniczne wykonanie** dla krytycznych jobow:
```php
// PushMediaToPrestaShop
public function __construct(...)
{
    $this->onConnection('sync');  // Natychmiastowe wykonanie
}
```

2. **CRON dla kolejki** (alternatywa):
```
* * * * * cd /path && php artisan queue:work --once
```

3. **Database driver** - nie wymaga zewnetrznych zaleznosci

### 7.2 Uruchamianie Queue Worker

```bash
# Development
php artisan queue:work

# Z konkretna kolejka
php artisan queue:work --queue=erp_high,erp_default,default

# Jednorazowe wykonanie (dla CRON)
php artisan queue:work --once

# Z timeout
php artisan queue:work --timeout=300
```

---

## 8. Monitorowanie

### 8.1 Komendy diagnostyczne

```bash
# Sprawdz zaleglosci w kolejce
php artisan queue:monitor

# Lista failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Retry konkretny job
php artisan queue:retry {job_id}

# Flush wszystkich failed
php artisan queue:flush
```

### 8.2 Logi

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Grep po job errors
grep -i "job failed" storage/logs/laravel.log
```

### 8.3 Baza danych

```sql
-- Zaleglosci w kolejce
SELECT queue, COUNT(*) FROM jobs GROUP BY queue;

-- Failed jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;

-- Active syncs
SELECT * FROM sync_jobs WHERE status IN ('pending', 'running');

-- Job progress
SELECT * FROM job_progress WHERE status = 'running';
```

### 8.4 UI Monitoring

- **Admin Panel** -> Sync Status (real-time progress bars)
- **Admin Panel** -> ERP Manager (connection health)
- **Admin Panel** -> Backup Manager (backup status)
- **Admin Panel** -> Maintenance (task status)

---

## Appendix A: Dispatch Patterns

### A.1 Z Livewire Component

```php
// ProductForm.php
public function syncToPrestaShop(): void
{
    SyncProductToPrestaShop::dispatch(
        $this->product,
        $this->activeShop,
        auth()->id(),
        $this->pendingMediaChanges,
        $this->preGeneratedJobId
    );
}
```

### A.2 Z Service

```php
// ERPServiceManager.php
public function syncProduct(Product $product, ERPConnection $connection): void
{
    SyncProductToERP::dispatch($product, $connection, null, [
        'sync_prices' => true,
        'sync_stock' => true,
    ]);
}
```

### A.3 Batch Dispatch

```php
// BulkSyncProducts.php
Bus::batch(
    $products->map(fn($p) => new SyncProductToPrestaShop($p, $shop, $userId))
)
->then(fn($batch) => $this->onComplete($batch))
->catch(fn($batch, $e) => $this->onError($batch, $e))
->finally(fn($batch) => $this->onFinish($batch))
->allowFailures()
->dispatch();
```

---

## Appendix B: Error Handling Pattern

```php
// W Job::failed()
public function failed(\Throwable $exception): void
{
    // 1. Log error
    Log::error('Job failed', [
        'job' => static::class,
        'exception' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
    ]);

    // 2. Update tracking models
    $this->productShopData->update(['sync_status' => 'error']);

    // 3. Create failure log
    SyncLog::create([
        'product_id' => $this->product->id,
        'status' => 'error',
        'message' => $exception->getMessage(),
    ]);

    // 4. Notify progress tracker
    if ($this->jobProgress) {
        $this->jobProgress->markFailed($exception->getMessage());
    }
}
```

---

## Appendix C: Unique Job Pattern

```php
// Implementacja ShouldBeUnique
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SyncProductToPrestaShop implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;  // 1 hour

    public function uniqueId(): string
    {
        return "product_{$this->product->id}_shop_{$this->shop->id}";
    }
}
```

---

**Koniec dokumentacji Jobs & Workers**
