# PPM-CC-Laravel: System Jobów (Queue Jobs)

## Spis treści
1. [Przegląd architektury](#przegląd-architektury)
2. [PrestaShop Sync Jobs](#prestashop-sync-jobs)
3. [Import/Pull Jobs](#importpull-jobs)
4. [Category Jobs](#category-jobs)
5. [Media Jobs](#media-jobs)
6. [Features Jobs](#features-jobs)
7. [Visual Editor Jobs](#visual-editor-jobs)
8. [System Jobs](#system-jobs)
9. [Product Category Jobs](#product-category-jobs)
10. [Uruchamianie i monitoring](#uruchamianie-i-monitoring)

---

## Przegląd architektury

### Kierunki synchronizacji
```
PPM → PrestaShop (PUSH/EXPORT)
PrestaShop → PPM (PULL/IMPORT)
```

### Typy jobów
| Typ | Opis |
|-----|------|
| `ShouldQueue` | Standardowy job w kolejce |
| `ShouldBeUnique` | Job z blokadą duplikatów (jeden na raz per klucz) |
| `dispatchSync()` | Natychmiastowe wykonanie (SYNC NOW) |
| `dispatch()` | Dodanie do kolejki |

### Kolejki
- `default` - główna kolejka (CRON compatible)
- `prestashop-sync` - dedykowana dla PrestaShop (opcjonalnie)

---

## PrestaShop Sync Jobs

### 1. `SyncProductToPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/SyncProductToPrestaShop.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje pojedynczy produkt z PPM do PrestaShop.

**Cechy:**
- `ShouldBeUnique` - zapobiega duplikatom
- Exponential backoff retry
- Obsługa pending media changes
- Progress tracking

**Parametry:**
```php
SyncProductToPrestaShop::dispatch(
    Product $product,
    PrestaShopShop $shop,
    ?int $userId = null,
    array $pendingMediaChanges = [],
    ?string $preGeneratedJobId = null
);
```

**Użycie:**
- Zapis produktu w ProductForm
- Bulk sync pojedynczych produktów
- SYNC NOW fallback

---

### 2. `SyncProductsJob`
**Ścieżka:** `app/Jobs/PrestaShop/SyncProductsJob.php`

**Kierunek:** PPM → PrestaShop (BULK PUSH)

**Opis:** Główny job do bulk synchronizacji produktów. Przyjmuje istniejący `SyncJob` do śledzenia.

**Parametry:**
```php
SyncProductsJob::dispatch(SyncJob $syncJob);
SyncProductsJob::dispatchSync($syncJob); // SYNC NOW
```

**Cechy:**
- Batch processing z memory management
- Rate limiting per shop
- Progress tracking w real-time
- Performance metrics

---

### 3. `BulkSyncProducts`
**Ścieżka:** `app/Jobs/PrestaShop/BulkSyncProducts.php`

**Kierunek:** PPM → PrestaShop (BULK PUSH)

**Opis:** Dispatchuje wiele `SyncProductToPrestaShop` jobów dla wybranej kolekcji produktów.

**Parametry:**
```php
BulkSyncProducts::dispatch(
    Collection $products,
    PrestaShopShop $shop,
    string $batchName = 'bulk_sync',
    ?int $userId = null,
    string $syncMode = 'full_sync' // full_sync|prices_only|stock_only|descriptions_only|categories_only
);
```

**Tryby sync:**
- `full_sync` - wszystkie dane
- `prices_only` - tylko ceny
- `stock_only` - tylko stany magazynowe
- `descriptions_only` - tylko opisy
- `categories_only` - tylko kategorie

---

### 4. `SyncShopVariantsToPrestaShopJob`
**Ścieżka:** `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje warianty produktu (combinations) do PrestaShop.

**Parametry:**
```php
SyncShopVariantsToPrestaShopJob::dispatch(Product $product, PrestaShopShop $shop);
```

---

### 5. `SyncCategoryToPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/SyncCategoryToPrestaShop.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje pojedynczą kategorię do PrestaShop.

**Cechy:**
- `ShouldBeUnique` - jeden sync per kategoria

**Parametry:**
```php
SyncCategoryToPrestaShop::dispatch(Category $category, PrestaShopShop $shop);
```

---

### 6. `SyncAttributeGroupWithPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/SyncAttributeGroupWithPrestaShop.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje grupę atrybutów (np. "Kolor", "Rozmiar") do PrestaShop.

**Cechy:**
- `ShouldBeUnique`

---

### 7. `SyncAttributeValueWithPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/SyncAttributeValueWithPrestaShop.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje wartość atrybutu (np. "Czerwony", "XL") do PrestaShop.

**Cechy:**
- `ShouldBeUnique`

---

### 8. `DeleteProductFromPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/DeleteProductFromPrestaShop.php`

**Kierunek:** PPM → PrestaShop (DELETE)

**Opis:** Usuwa produkt z PrestaShop.

**Cechy:**
- `ShouldBeUnique` - zapobiega wielokrotnemu usunięciu

---

## Import/Pull Jobs

### 9. `PullProductsFromPrestaShop`
**Ścieżka:** `app/Jobs/PullProductsFromPrestaShop.php`

**Kierunek:** PrestaShop → PPM (PULL)

**Opis:** Cykliczny job pobierający dane produktów z PrestaShop do PPM dla walidacji.

**KLUCZOWA FUNKCJA:**
- Pobiera WSZYSTKIE dane produktu (nie tylko ceny/stany)
- Zapisuje do `product_shop_data` (NIE do `products`)
- Walidator porównuje dane PPM vs PrestaShop
- Uruchamiany co 6h przez scheduler

**Parametry:**
```php
PullProductsFromPrestaShop::dispatch(PrestaShopShop $shop);
PullProductsFromPrestaShop::dispatch($shop, $existingSyncJob); // SYNC NOW
```

**Result Summary:**
```php
[
    'synced' => 50,
    'conflicts' => 2,
    'prices_imported' => 150,
    'stock_imported' => 50,
    'errors' => 1,
    'fields_updated' => ['name' => 50, 'slug' => 50, ...],
    'total_fields_updated' => 700,
    'job_type' => 'pull_for_validation'
]
```

---

### 10. `BulkImportProducts`
**Ścieżka:** `app/Jobs/PrestaShop/BulkImportProducts.php`

**Kierunek:** PrestaShop → PPM (IMPORT)

**Opis:** Import produktów z PrestaShop do PPM. Tworzy NOWE produkty w PPM.

**Tryby importu:**
- `all` - wszystkie produkty ze sklepu
- `category` - produkty z określonej kategorii
- `individual` - konkretne produkty po ID

**Parametry:**
```php
BulkImportProducts::dispatch($shop, 'all');
BulkImportProducts::dispatch($shop, 'category', ['category_id' => 5, 'include_subcategories' => true]);
BulkImportProducts::dispatch($shop, 'individual', ['product_ids' => [1, 2, 3]]);
```

**Cechy:**
- Automatic SKU conflict detection
- Progress tracking
- Notification on completion
- Extended result_summary

---

### 11. `BulkPullProducts`
**Ścieżka:** `app/Jobs/PrestaShop/BulkPullProducts.php`

**Kierunek:** PrestaShop → PPM (PULL)

**Opis:** Bulk pull danych produktów z PrestaShop. Aktualizuje ISTNIEJĄCE produkty.

---

### 12. `PullSingleProductFromPrestaShop`
**Ścieżka:** `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php`

**Kierunek:** PrestaShop → PPM (PULL)

**Opis:** Pobiera dane pojedynczego produktu z PrestaShop do PPM.

**Parametry:**
```php
PullSingleProductFromPrestaShop::dispatch(Product $product, PrestaShopShop $shop);
```

---

## Category Jobs

### 13. `CategoryCreationJob`
**Ścieżka:** `app/Jobs/PrestaShop/CategoryCreationJob.php`

**Kierunek:** PPM → PrestaShop (CREATE)

**Opis:** Tworzy nową kategorię w PrestaShop na podstawie danych z PPM.

---

### 14. `BulkCreateCategories`
**Ścieżka:** `app/Jobs/PrestaShop/BulkCreateCategories.php`

**Kierunek:** PPM → PrestaShop (BULK CREATE)

**Opis:** Tworzy wiele kategorii w PrestaShop.

---

### 15. `AnalyzeMissingCategories`
**Ścieżka:** `app/Jobs/PrestaShop/AnalyzeMissingCategories.php`

**Kierunek:** Analiza

**Opis:** Analizuje brakujące kategorie między PPM a PrestaShop.

---

### 16. `ExpirePendingCategoryPreview`
**Ścieżka:** `app/Jobs/PrestaShop/ExpirePendingCategoryPreview.php`

**Kierunek:** System

**Opis:** Wygasza pending category previews (cleanup).

---

### 17. `BulkDeleteCategoriesJob`
**Ścieżka:** `app/Jobs/Categories/BulkDeleteCategoriesJob.php`

**Kierunek:** PPM → PrestaShop (DELETE)

**Opis:** Usuwa wiele kategorii z PrestaShop.

---

## Media Jobs

### 18. `ProcessMediaUpload`
**Ścieżka:** `app/Jobs/Media/ProcessMediaUpload.php`

**Kierunek:** PPM Internal

**Opis:** Przetwarza upload media (resize, thumbnails, optimization).

---

### 19. `BulkMediaUpload`
**Ścieżka:** `app/Jobs/Media/BulkMediaUpload.php`

**Kierunek:** PPM Internal

**Opis:** Bulk upload wielu plików media.

---

### 20. `PushMediaToPrestaShop`
**Ścieżka:** `app/Jobs/Media/PushMediaToPrestaShop.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Wysyła media (zdjęcia) do PrestaShop.

---

### 21. `SyncMediaFromPrestaShop`
**Ścieżka:** `app/Jobs/Media/SyncMediaFromPrestaShop.php`

**Kierunek:** PrestaShop → PPM (PULL)

**Opis:** Pobiera media z PrestaShop do PPM.

---

## Features Jobs

### 22. `SyncFeaturesJob`
**Ścieżka:** `app/Jobs/Features/SyncFeaturesJob.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje cechy produktu (features) do PrestaShop.

---

### 23. `ImportFeaturesFromPSJob`
**Ścieżka:** `app/Jobs/Features/ImportFeaturesFromPSJob.php`

**Kierunek:** PrestaShop → PPM (IMPORT)

**Opis:** Importuje cechy produktu z PrestaShop do PPM.

---

### 24. `BulkAssignFeaturesJob`
**Ścieżka:** `app/Jobs/Features/BulkAssignFeaturesJob.php`

**Kierunek:** PPM Internal

**Opis:** Masowo przypisuje cechy do produktów.

---

## Visual Editor Jobs

### 25. `SyncDescriptionToPrestaShopJob`
**Ścieżka:** `app/Jobs/VisualEditor/SyncDescriptionToPrestaShopJob.php`

**Kierunek:** PPM → PrestaShop (PUSH)

**Opis:** Synchronizuje visual description do PrestaShop.

---

### 26. `BulkSyncDescriptionsJob`
**Ścieżka:** `app/Jobs/VisualEditor/BulkSyncDescriptionsJob.php`

**Kierunek:** PPM → PrestaShop (BULK PUSH)

**Opis:** Bulk sync opisów wizualnych do PrestaShop.

---

### 27. `BulkApplyTemplateJob`
**Ścieżka:** `app/Jobs/VisualEditor/BulkApplyTemplateJob.php`

**Kierunek:** PPM Internal

**Opis:** Masowo aplikuje template opisu do produktów.

---

### 28. `BulkExportDescriptionsJob`
**Ścieżka:** `app/Jobs/VisualEditor/BulkExportDescriptionsJob.php`

**Kierunek:** PPM → Export

**Opis:** Eksportuje opisy wizualne do pliku.

---

### 29. `BulkImportDescriptionsJob`
**Ścieżka:** `app/Jobs/VisualEditor/BulkImportDescriptionsJob.php`

**Kierunek:** Import → PPM

**Opis:** Importuje opisy wizualne z pliku.

---

## System Jobs

### 30. `BackupDatabaseJob`
**Ścieżka:** `app/Jobs/BackupDatabaseJob.php`

**Opis:** Wykonuje backup bazy danych.

---

### 31. `ScheduledBackupJob`
**Ścieżka:** `app/Jobs/ScheduledBackupJob.php`

**Opis:** Zaplanowany backup (CRON).

---

### 32. `MaintenanceTaskJob`
**Ścieżka:** `app/Jobs/MaintenanceTaskJob.php`

**Opis:** Wykonuje zadania maintenance (cleanup, optimization).

---

### 33. `SendNotificationJob`
**Ścieżka:** `app/Jobs/SendNotificationJob.php`

**Opis:** Wysyła notyfikacje do użytkowników.

---

### 34. `GenerateReportJob`
**Ścieżka:** `app/Jobs/GenerateReportJob.php`

**Opis:** Generuje raporty w tle.

---

## Product Category Jobs

### 35. `BulkAssignCategories`
**Ścieżka:** `app/Jobs/Products/BulkAssignCategories.php`

**Kierunek:** PPM Internal

**Opis:** Masowo przypisuje kategorie do produktów.

---

### 36. `BulkRemoveCategories`
**Ścieżka:** `app/Jobs/Products/BulkRemoveCategories.php`

**Kierunek:** PPM Internal

**Opis:** Masowo usuwa kategorie z produktów.

---

### 37. `BulkMoveCategories`
**Ścieżka:** `app/Jobs/Products/BulkMoveCategories.php`

**Kierunek:** PPM Internal

**Opis:** Masowo przenosi produkty między kategoriami.

---

## Uruchamianie i monitoring

### Scheduler (CRON)
```php
// routes/console.php
Schedule::call(function () {
    foreach ($activeShops as $shop) {
        PullProductsFromPrestaShop::dispatch($shop);
    }
})->everySixHours();
```

### SYNC NOW (natychmiastowe)
```php
// Używa dispatchSync() zamiast dispatch()
PullProductsFromPrestaShop::dispatchSync($shop, $existingSyncJob);
SyncProductsJob::dispatchSync($syncJob);
```

### Monitoring
- **Panel:** `/admin/shops/sync`
- **Model:** `SyncJob` - tracking jobów
- **Progress:** `JobProgress` - real-time progress bar

### Komendy
```bash
php artisan queue:work         # Worker
php artisan queue:listen       # Listener (dev)
php artisan queue:restart      # Restart workers
php artisan queue:failed       # Lista failed jobs
php artisan queue:retry all    # Retry failed jobs
```

---

## Podsumowanie

| Kategoria | Ilość | Kierunek |
|-----------|-------|----------|
| PrestaShop Sync (PUSH) | 8 | PPM → PS |
| Import/Pull | 4 | PS → PPM |
| Categories | 5 | Mix |
| Media | 4 | Mix |
| Features | 3 | Mix |
| Visual Editor | 5 | Mix |
| System | 5 | Internal |
| Product Categories | 3 | Internal |
| **RAZEM** | **37** | |

---

*Dokumentacja wygenerowana: 2025-12-22*
*Projekt: PPM-CC-Laravel*
