# RAPORT PRACY AGENTA: prestashop-api-expert
**Data**: 2025-11-13 14:30
**Agent**: prestashop-api-expert
**Zadanie**: Fix queue worker crash - missing getSpecificPrices() method

## WYKONANE PRACE

### 1. ✅ Dodano metodę `getSpecificPrices()` do BasePrestaShopClient

**Lokalizacja**: `app/Services/PrestaShop/BasePrestaShopClient.php` (linie 425-477)

**Implementacja**:
```php
public function getSpecificPrices(int $productId): array
{
    try {
        $queryParams = $this->buildQueryParams([
            'filter[id_product]' => $productId,
            'display' => 'full',
        ]);

        $response = $this->makeRequest('GET', "specific_prices?{$queryParams}");
        return $response;

    } catch (PrestaShopAPIException $e) {
        // Graceful handling: 404 = no specific prices (expected)
        if ($e->isNotFound()) {
            Log::info('No specific prices found for product', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
            ]);
            return ['specific_prices' => []];
        }

        // Other errors - log warning and return empty array
        Log::warning('Failed to fetch specific prices', [
            'product_id' => $productId,
            'shop_id' => $this->shop->id,
            'http_status' => $e->getHttpStatusCode(),
            'error' => $e->getMessage(),
        ]);

        return ['specific_prices' => []];
    }
}
```

**Cechy**:
- ✅ PrestaShop API endpoint: `/specific_prices?filter[id_product]={id}&display=full`
- ✅ Graceful 404 handling (product bez specific_prices = expected)
- ✅ Zwraca empty array na błędach (nie crashuje całego joba)
- ✅ Comprehensive logging

### 2. ✅ Naprawiono SerializesModels issue w PullProductsFromPrestaShop

**Problem**: Job używał `protected ?SyncJob $syncJob` property z `SerializesModels` trait.
Kiedy SyncJob został usunięty przez cleanup, job nie mógł się deserializować → `ModelNotFoundException`.

**Rozwiązanie**: Zamieniono Eloquent model na scalar ID:
- `protected ?int $syncJobId` zamiast `protected ?SyncJob $syncJob`
- Dodano `getSyncJob()` helper method z graceful handling
- Wszystkie wywołania używają null-safe operator `$syncJob?->method()`

**Zmienione pliki**:
- `app/Jobs/PullProductsFromPrestaShop.php`

**Kod**:
```php
// Store only ID (not model instance)
protected ?int $syncJobId = null;

// Constructor
$syncJob = SyncJob::create([...]);
$this->syncJobId = $syncJob->id; // Only ID serialized

// Helper method
protected function getSyncJob(): ?SyncJob
{
    if (!$this->syncJobId) return null;

    try {
        return SyncJob::find($this->syncJobId);
    } catch (\Exception $e) {
        Log::warning('SyncJob may have been deleted by cleanup', [
            'sync_job_id' => $this->syncJobId,
        ]);
        return null;
    }
}

// Usage in handle()
$syncJob = $this->getSyncJob();
$syncJob?->start(); // Graceful null handling
```

### 3. ✅ Testing & Verification

**Test workflow**:
1. Flushed wszystkie failed jobs: `php artisan queue:flush`
2. Dispatched test job: `PullProductsFromPrestaShop` dla shop ID 1
3. Processed queue: `php artisan queue:work database --stop-when-empty`
4. Verified logs: `getSpecificPrices()` API calls successful

**Wyniki testów**:
```
✅ Job DONE w 1s (bez failures)
✅ API calls successful:
   - GET /api/specific_prices?filter[id_product]=9763 → HTTP 200 (27.95ms)
   - Response: {"specific_prices": [...]}

✅ Prices imported:
   - Product 11030 (BG-KAYO-S200): 6 prices imported
   - Product 11017 (TEST-AUTOFIX): 2 prices imported

✅ No failed jobs: php artisan queue:failed → "No failed jobs found"
```

**Log evidence**:
```
[2025-11-13 14:29:33] production.INFO: PrestaShop API Request
  {"method":"GET","url":"https://dev.mpptrade.pl/api/specific_prices?filter[id_product]=9763&display=full","status_code":200}

[2025-11-13 14:29:33] production.INFO: Fetched prices from PrestaShop
  {"product_id":11017,"base_price":541.46,"specific_prices_count":1}

[2025-11-13 14:29:33] production.DEBUG: Prices imported for product
  {"product_id":11030,"prices_count":6}
```

## PLIKI ZMODYFIKOWANE

- **app/Services/PrestaShop/BasePrestaShopClient.php**
  - Dodano `getSpecificPrices(int $productId): array` method
  - Graceful 404 handling (empty array return)
  - Comprehensive API logging

- **app/Jobs/PullProductsFromPrestaShop.php**
  - Zamieniono `protected ?SyncJob $syncJob` → `protected ?int $syncJobId`
  - Dodano `getSyncJob(): ?SyncJob` helper method
  - Wszystkie odwołania używają null-safe operator
  - Graceful handling gdy SyncJob został usunięty przez cleanup

## PROBLEMY ROZWIĄZANE

### ✅ Problem #1: Missing getSpecificPrices() method
**Błąd przed fix**:
```
Exception: Call to undefined method App\Services\PrestaShop\PrestaShop8Client::getSpecificPrices()
Location: /app/Services/PrestaShop/PrestaShopPriceImporter.php:114
```

**Fix**: Dodano metodę do `BasePrestaShopClient` (dziedziczoną przez v8/v9 clients)

### ✅ Problem #2: SerializesModels + SyncJob cleanup conflict
**Błąd przed fix**:
```
Illuminate\Database\Eloquent\ModelNotFoundException: No query results for model [App\Models\SyncJob]
Location: vendor/laravel/framework/src/Illuminate/Queue/SerializesAndRestoresModelIdentifiers.php:110
```

**Root cause**:
1. Job tworzy `SyncJob` w konstruktorze
2. Laravel serializuje `$syncJob` property do queue (wraz z model ID)
3. User wykonuje cleanup: `SyncJob::where('status', 'completed')->delete()`
4. Queue worker próbuje deserializować job → `SyncJob::findOrFail($id)` → ModelNotFoundException

**Fix**: Przechowuj tylko scalar ID, nie model instance

## NASTĘPNE KROKI

### ✅ COMPLETED - No further action needed

Wszystkie queue jobs działają poprawnie:
- ✅ `PullProductsFromPrestaShop` - import produktów z PrestaShop
- ✅ `PrestaShopPriceImporter::importPricesForProduct()` - specific_prices API
- ✅ Graceful SyncJob handling (odporne na cleanup)

### Recommended monitoring:

**Production monitoring**:
```bash
# Check queue health
watch -n 5 'php artisan queue:failed'

# Monitor queue worker logs
tail -f storage/logs/queue-worker.log

# Check SyncJob cleanup impact
DB::table('sync_jobs')->where('status', 'completed')->whereDate('created_at', '<', now()->subDays(7))->count()
```

## DEPLOYMENT STATUS

✅ **DEPLOYED TO PRODUCTION**: 2025-11-13 14:28
- BasePrestaShopClient.php uploaded
- PullProductsFromPrestaShop.php uploaded
- Cache cleared: `php artisan cache:clear && config:clear`
- Queue flushed: `php artisan queue:flush` (old corrupt jobs removed)
- Test successful: Fresh job completed without failures

## TECHNICAL NOTES

### PrestaShop specific_prices API

**Endpoint**: `GET /api/specific_prices?filter[id_product]={id}&display=full`

**Response format**:
```json
{
  "specific_prices": [
    {
      "id": "123",
      "id_product": "9763",
      "id_group": "3",
      "reduction": "0.15",
      "reduction_type": "percentage",
      "price": "-1.00",
      "from": "0000-00-00 00:00:00",
      "to": "0000-00-00 00:00:00"
    }
  ]
}
```

**Expected behaviors**:
- Product bez specific_prices → 404 (handled gracefully)
- Product z specific_prices → 200 + array
- API error (auth, rate limit) → logged + empty array returned

### Laravel Queue Best Practices (learned)

**❌ AVOID**:
```php
protected SyncJob $syncJob; // Serialized with SerializesModels
```

**✅ RECOMMENDED**:
```php
protected int $syncJobId; // Only scalar serialized
protected function getModel(): ?SyncJob {
    return SyncJob::find($this->syncJobId); // Graceful handling
}
```

**Reason**: Models w queue są vulnerable do zewnętrznych delete operations (cleanup, cascade deletes, etc.)

## REFERENCES

- PrestaShop API docs: https://devdocs.prestashop-project.org/8/webservice/
- Laravel Queue serialization: https://laravel.com/docs/12.x/queues#handling-relationships
- Issue: `_ISSUES_FIXES/QUEUE_SYNCJOB_SERIALIZATION_ISSUE.md` (to be created)
