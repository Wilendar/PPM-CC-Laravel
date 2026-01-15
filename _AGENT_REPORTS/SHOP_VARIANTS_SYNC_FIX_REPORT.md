# RAPORT: Shop Variants Sync Fix

**Data**: 2025-12-05 15:20
**Problem**: Warianty dodane w SHOP TAB nie wysyłają się na PrestaShop

## PROBLEM ZGŁOSZONY

Użytkownik zgłosił krytyczny bug: warianty dodane w zakładce sklepu (SHOP TAB) nie są synchronizowane do PrestaShop.

## ANALIZA

### Przepływ danych:
1. UI (Shop Tab) → `pendingVariantCreates/Updates/Deletes`
2. Save → `commitShopVariants()` → ShopVariant records
3. `dispatchShopVariantsSync()` → `SyncShopVariantsToPrestaShopJob`
4. Job → PrestaShop API

### Znalezione problemy:

#### BUG #1: Kolejka 'prestashop' nie skonfigurowana
```php
// BŁĄD: $this->onQueue('prestashop')
// config/queue.php nie ma połączenia 'prestashop'
```
**Skutek**: 9 jobów utknęło w nieistniejącej kolejce

#### BUG #2: Nieprawidłowa inicjalizacja klienta API
```php
// BŁĄD: new PrestaShop8Client($shop->api_url, $shop->api_key, $shop->id)
// BasePrestaShopClient oczekuje obiektu PrestaShopShop
```
**Skutek**: `Argument #1 ($shop) must be of type PrestaShopShop, null given`

#### BUG #3: OVERRIDE bez combination_id
```php
// BŁĄD: OVERRIDE wymaga prestashop_combination_id
// Nowy wariant nie ma jeszcze ID kombinacji w PrestaShop
```
**Skutek**: `No PrestaShop combination ID for OVERRIDE operation`

## ROZWIĄZANIA

### FIX #1: Usunięcie onQueue('prestashop')
```php
public function __construct(int $productId, int $shopId, array $variantIds = [])
{
    $this->productId = $productId;
    $this->shopId = $shopId;
    $this->variantIds = $variantIds;
    // FIX 2025-12-05: Removed $this->onQueue('prestashop')
    // Using default queue instead (database driver with 'default' queue name)
}
```

### FIX #2: Poprawna inicjalizacja PrestaShop8Client
```php
// FIX 2025-12-05: BasePrestaShopClient expects PrestaShopShop object
$client = new PrestaShop8Client($shop);
```

### FIX #3: Fallback z OVERRIDE do ADD
```php
case 'OVERRIDE':
    // FIX 2025-12-05: Check if combination exists in PrestaShop
    $combinationId = $shopVariant->prestashop_combination_id
        ?? $shopVariant->baseVariant?->prestashop_combination_id;

    if (!$combinationId) {
        Log::info('[SyncShopVariantsJob] OVERRIDE without combination_id - falling back to ADD');
        $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
    } else {
        $shopVariant->prestashop_combination_id = $combinationId;
        $this->handleOverrideOperation($client, $shopVariant);
    }
    break;
```

## WERYFIKACJA

### Logi po naprawie:
```
[SyncShopVariantsJob] START {"product_id":11148,"shop_id":1}
[SyncShopVariantsJob] OVERRIDE without combination_id - falling back to ADD
[SyncShopVariantsJob] ADD successful {"prestashop_combination_id":13188}
[SyncShopVariantsJob] COMPLETE {"success":1,"failed":0}
```

### Status shop_variants:
- pending: 0
- in_progress: 0
- synced: 1
- failed: 0

## PLIKI ZMODYFIKOWANE

| Plik | Opis zmian |
|------|------------|
| `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php` | 3 fixy (queue, client, fallback) |

## SKRYPTY POMOCNICZE (do usunięcia)

- `_TEMP/check_shop_variants_status.php`
- `_TEMP/cleanup_broken_jobs.php`
- `_TEMP/dispatch_sync_job.php`
- `_TEMP/reset_and_test.php`
- `_TEMP/deploy_and_full_test.ps1`
- `_TEMP/process_all_queue.ps1`
- `_TEMP/run_reset_test.ps1`

## STATUS

✅ **NAPRAWIONE** - Shop variants synchronizują się poprawnie do PrestaShop
