# ETAP 14: Refactoring Systemu Jobów

**Status:** ❌ Nie rozpoczete
**Priorytet:** Sredni
**Szacowany czas:** 2-3 dni
**Data utworzenia:** 2025-12-22

---

## Cel Etapu

Optymalizacja systemu 37 jobów Laravel Queue:
- Eliminacja duplikatów i redundancji
- Standaryzacja formatów result_summary
- Naprawienie brakujacych wpisów scheduler
- Konsolidacja podobnych funkcjonalnosci

---

## Analiza Obecnego Stanu

### Zidentyfikowane Problemy

| Problem | Opis | Wpływ |
|---------|------|-------|
| Duplikaty Pull | BulkPullProducts vs PullProductsFromPrestaShop | Nieczytelny kod |
| Duplikaty Backup | BackupDatabaseJob + ScheduledBackupJob | Redundancja |
| Brak scheduler | ScheduledBackupJob nie ma wpisu w routes/console.php | Backupy nie działaja! |
| Niestandarowe result_summary | Kazdy job ma inny format | Trudna analiza |

### Mapowanie Uzytych Jobów

| Job | Lokalizacja użycia | Linia |
|-----|-------------------|-------|
| BulkPullProducts | ProductForm.php | 6965 |
| PullSingleProductFromPrestaShop | ProductFormShopTabs.php | 257 |
| BackupDatabaseJob | ScheduledBackupJob.php | 80 (internal) |
| CategoryCreationJob | CategoryAutoCreateService.php | 174 |
| CategoryCreationJob | ProductFormSaver.php | 486 |

---

## FAZA 1: Standaryzacja Result Summary

**Status:** ❌ Nie rozpoczete

### 1.1 Utworzenie interfejsu i traita
❌ PLIK: `app/Contracts/ResultSummaryInterface.php`
```php
interface ResultSummaryInterface {
    public function getResultSummary(): array;
    public function getStandardizedSummary(): array;
}
```

❌ PLIK: `app/Jobs/Concerns/HasStandardResultSummary.php`
```php
trait HasStandardResultSummary {
    protected int $totalItems = 0;
    protected int $processedItems = 0;
    protected int $successfulItems = 0;
    protected int $failedItems = 0;
    protected int $skippedItems = 0;
    protected array $errors = [];
    protected array $details = [];
    protected int $startTime;

    public function getStandardizedSummary(): array {
        return [
            'total' => $this->totalItems,
            'processed' => $this->processedItems,
            'successful' => $this->successfulItems,
            'failed' => $this->failedItems,
            'skipped' => $this->skippedItems,
            'errors' => array_slice($this->errors, 0, 50),
            'details' => $this->details,
            'duration_ms' => $this->getDurationMs(),
            'job_type' => class_basename(static::class),
        ];
    }
}
```

### 1.2 Migracja istniejacych jobów

❌ `app/Jobs/PrestaShop/SyncProductsJob.php` - dodac trait
❌ `app/Jobs/PrestaShop/BulkImportProducts.php` - dodac trait
❌ `app/Jobs/PrestaShop/BulkSyncProducts.php` - dodac trait
❌ `app/Jobs/PullProductsFromPrestaShop.php` - dodac trait
❌ `app/Jobs/PrestaShop/SyncProductToPrestaShop.php` - dodac trait

### 1.3 Aktualizacja UI display

❌ `resources/views/livewire/admin/shops/sync-controller.blade.php`
- Linie 1770-2000: dodac obsluge nowego formatu
- Zachowac backward compatibility ze starym formatem

---

## FAZA 2: Konsolidacja Backup Jobs

**Status:** ❌ Nie rozpoczete

### 2.1 Rozszerzenie ScheduledBackupJob

❌ `app/Jobs/ScheduledBackupJob.php`
```php
class ScheduledBackupJob implements ShouldQueue
{
    public function __construct(
        public bool $immediate = false,
        public ?string $backupType = 'full'
    ) {}

    public function handle()
    {
        // Logika z BackupDatabaseJob przeniesiona tutaj
        if ($this->immediate) {
            // Natychmiastowy backup
        } else {
            // Zaplanowany backup
        }
    }
}
```

### 2.2 Usuniecie BackupDatabaseJob

❌ USUN: `app/Jobs/BackupDatabaseJob.php`
❌ Zaktualizuj wszystkie referencje (sprawdzic czy sa jakies poza ScheduledBackupJob)

### 2.3 Naprawienie Scheduler

❌ `routes/console.php` - DODAC:
```php
Schedule::job(new ScheduledBackupJob())->daily()->at('03:00');
```

---

## FAZA 3: Konsolidacja Pull Jobs

**Status:** ❌ Nie rozpoczete

### 3.1 Rozszerzenie PullProductsFromPrestaShop

❌ `app/Jobs/PullProductsFromPrestaShop.php`
```php
public function __construct(
    public PrestaShopShop $shop,
    ?SyncJob $existingSyncJob = null,
    public ?array $productIds = null  // NOWE: null = all, array = specific
) {
    // ...
}

public function handle()
{
    if ($this->productIds !== null) {
        // Pull tylko wybranych produktów
        $products = $this->shop->products()
            ->whereIn('id', $this->productIds)
            ->get();
    } else {
        // Pull wszystkich
        $products = $this->shop->products;
    }
    // ...
}
```

### 3.2 Aktualizacja ProductForm.php

❌ `app/Http/Livewire/Products/Management/ProductForm.php`
- Linia 6965: Zamien `BulkPullProducts::dispatch(...)` na:
```php
PullProductsFromPrestaShop::dispatch(
    $shop,
    null,  // no existing sync job
    [$productId1, $productId2]  // specific products
);
```

### 3.3 Usuniecie BulkPullProducts

❌ USUN: `app/Jobs/PrestaShop/BulkPullProducts.php`

### 3.4 Zachowanie PullSingleProductFromPrestaShop

✅ ZACHOWAC: `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php`
- Rozny cel: instant UI refresh bez tworzenia SyncJob
- Uzycie w ProductFormShopTabs.php dla natychmiastowego odswiezenia

---

## FAZA 4: Dokumentacja i Testy

**Status:** ❌ Nie rozpoczete

### 4.1 Aktualizacja dokumentacji

❌ `_DOCS/JOBS_DOCUMENTATION.md` - zaktualizowac po zmianach
❌ Dodac sekcje o standaryzacji result_summary

### 4.2 Testy jednostkowe

❌ PLIK: `tests/Unit/Jobs/HasStandardResultSummaryTest.php`
❌ PLIK: `tests/Unit/Jobs/ScheduledBackupJobTest.php`
❌ Zaktualizowac istniejace testy dla PullProductsFromPrestaShop

---

## Tabela Wpływu na Funkcjonalnosc

| Obecna funkcja | Po refaktoringu | Status |
|----------------|-----------------|--------|
| Bulk pull all products | `PullProductsFromPrestaShop::dispatch($shop)` | Bez zmian |
| Bulk pull specific products | `PullProductsFromPrestaShop::dispatch($shop, null, $ids)` | NOWE |
| Single instant refresh | `PullSingleProductFromPrestaShop::dispatch(...)` | Bez zmian |
| Scheduled backup | `ScheduledBackupJob` + scheduler entry | NAPRAWIONE |
| Manual backup | `ScheduledBackupJob::dispatch(immediate: true)` | NOWE API |
| Category creation | `CategoryCreationJob` | Bez zmian |
| Sync product to PS | `SyncProductToPrestaShop` | Bez zmian |
| Bulk import | `BulkImportProducts` | Bez zmian |

---

## Kolejnosc Wdrozenia

```
FAZA 1 (Result Summary)
    ↓
  TEST na produkcji
    ↓
FAZA 2 (Backup Jobs)
    ↓
  TEST na produkcji
    ↓
FAZA 3 (Pull Jobs)
    ↓
  TEST na produkcji
    ↓
FAZA 4 (Dokumentacja)
```

**WAZNE:** Kazda faza jest niezalezna i moze byc wdrozona osobno!

---

## Metryki Sukcesu

| Metryka | Przed | Po |
|---------|-------|-----|
| Liczba jobów | 37 | 35 (-2) |
| Duplikaty funkcjonalnosci | 3 | 0 |
| Standaryzacja result_summary | 0% | 100% |
| Scheduler backups | BRAK | Aktywny |

---

## Ryzyka i Mitygacja

| Ryzyko | Prawdopodobienstwo | Mitygacja |
|--------|-------------------|-----------|
| Utrata funkcji pull | Niskie | Testy przed usuniecie BulkPullProducts |
| Niezgodnosc UI | Srednie | Backward compatibility w blade |
| Scheduler failure | Niskie | Test lokalnie przed deploy |

---

## Pliki Krytyczne

1. `app/Jobs/PullProductsFromPrestaShop.php` - rozszerzenie
2. `app/Jobs/ScheduledBackupJob.php` - konsolidacja
3. `app/Http/Livewire/Products/Management/ProductForm.php` - zmiana dispatch
4. `resources/views/livewire/admin/shops/sync-controller.blade.php` - UI
5. `routes/console.php` - scheduler

---

*Dokumentacja utworzona: 2025-12-22*
*Projekt: PPM-CC-Laravel*
