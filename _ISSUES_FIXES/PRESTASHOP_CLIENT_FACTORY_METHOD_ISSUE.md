# PrestaShop Client Factory Method Issue

**Date:** 2025-11-14
**Severity:** 🔥 CRITICAL
**Status:** ✅ RESOLVED

---

## 📋 SYMPTOMY

**User Report:**
```
Error Message:
Call to undefined method App\Services\PrestaShop\PrestaShopClientFactory::make()

Error Details:
/home/host379076/domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/PrestaShopPriceExporter.php:84
```

**Kontekst:** Błąd występuje podczas eksportu cen produktu do PrestaShop (specific_prices sync)

---

## 🔍 ROOT CAUSE

### Problem: Incorrect Factory Method Call

**Location:** `app/Services/PrestaShop/PrestaShopPriceExporter.php:84`

**Błędny kod:**
```php
// ❌ BŁĄD - Line 54 (constructor)
public function __construct(
    protected PrestaShopClientFactory $clientFactory,  // Factory jako dependency injection
    protected PriceGroupMapper $priceGroupMapper
) {}

// ❌ BŁĄD - Line 84
$client = $this->clientFactory->make($shop);  // make() nie istnieje!
```

**PrestaShopClientFactory Architecture:**
```php
class PrestaShopClientFactory
{
    // ✅ POPRAWNA metoda - STATIC
    public static function create(PrestaShopShop $shop): BasePrestaShopClient
    {
        return match($shop->version) {
            '8' => new PrestaShop8Client($shop),
            '9' => new PrestaShop9Client($shop),
            default => throw new InvalidArgumentException(...)
        };
    }
}
```

**Root Cause:**
- Factory ma **TYLKO metody statyczne** (`create()`, `createMultiple()`, `createForAllActiveShops()`)
- Próba wywołania `$this->clientFactory->make()` na nieistniejącej metodzie instancyjnej
- Factory nie powinien być dependency injection - tylko statyczne wywołanie

---

## 🛠️ ROZWIĄZANIE

### Fix: Use Static Factory Method

**File:** `app/Services/PrestaShop/PrestaShopPriceExporter.php`

**Changes:**

**1. Remove Factory from Constructor:**
```php
// PRZED (Line 47-56)
public function __construct(
    protected PrestaShopClientFactory $clientFactory,  // ❌ USUNĄĆ
    protected PriceGroupMapper $priceGroupMapper
) {}

// PO
public function __construct(
    protected PriceGroupMapper $priceGroupMapper  // ✅ Tylko mapper
) {}
```

**2. Use Static Factory Call:**
```php
// PRZED (Line 84)
$client = $this->clientFactory->make($shop);  // ❌ BŁĄD

// PO
$client = PrestaShopClientFactory::create($shop);  // ✅ POPRAWNIE
```

---

## 📚 CORRECT USAGE PATTERNS

### Pattern 1: Static Factory Call (Services)

**Use Case:** Service potrzebuje client dla konkretnego shop

```php
class PrestaShopPriceExporter
{
    public function exportPricesForProduct(Product $product, PrestaShopShop $shop, int $prestashopProductId): array
    {
        // ✅ Direct static call
        $client = PrestaShopClientFactory::create($shop);

        // Use client for API operations
        $existingPrices = $client->getSpecificPrices($prestashopProductId);
        // ...
    }
}
```

**Inne przykłady:**
- `PrestaShopAttributeSyncService` (lines 54, 137, 226)
- `PrestaShopImportService`
- `PrestaShopStockImporter`

### Pattern 2: Client as Parameter (Strategy Pattern)

**Use Case:** Strategy otrzymuje client z zewnątrz (np. od SyncService)

```php
class ProductSyncStrategy implements ISyncStrategy
{
    // Client NIE tworzony wewnątrz - przekazywany jako parametr
    public function syncToPrestaShop(
        Model $model,
        BasePrestaShopClient $client,  // ✅ Received from outside
        PrestaShopShop $shop
    ): array {
        // Use client directly - no factory needed
        $productData = $this->transformer->transform($model, $shop);
        $response = $client->updateProduct($externalId, $productData);
        // ...
    }
}
```

**Kiedy użyć:**
- Strategy pattern (client passed from coordinator)
- Testing (mock client injection)
- Transaction management (shared client)

### Pattern 3: Multiple Clients (Batch Operations)

**Use Case:** Operacje na wielu sklepach jednocześnie

```php
// Create clients for all active shops
$clients = PrestaShopClientFactory::createForAllActiveShops();

foreach ($clients as $shopId => $client) {
    // Process each shop
    $this->syncProductToShop($product, $client, $shopId);
}

// OR for specific shops
$shops = PrestaShopShop::whereIn('id', [1, 2, 3])->get();
$clients = PrestaShopClientFactory::createMultiple($shops->all());
```

---

## ✅ VERIFICATION

### Test the Fix

**1. Test Price Export:**
```php
$product = Product::find(1);
$shop = PrestaShopShop::find(1);
$prestashopProductId = 123;

$exporter = app(PrestaShopPriceExporter::class);
$results = $exporter->exportPricesForProduct($product, $shop, $prestashopProductId);

// Expected: No "Call to undefined method" error
// Expected: Prices exported successfully
```

**2. Verify Factory Methods Exist:**
```php
// All factory methods are STATIC
PrestaShopClientFactory::create($shop);              // ✅ Returns client
PrestaShopClientFactory::createMultiple($shops);     // ✅ Returns array of clients
PrestaShopClientFactory::createForAllActiveShops();  // ✅ Returns all active clients
```

---

## 🚀 DEPLOYMENT

**Date:** 2025-11-14
**Status:** ✅ DEPLOYED

**Files Modified:**
1. `app/Services/PrestaShop/PrestaShopPriceExporter.php`
   - Removed `PrestaShopClientFactory` from constructor (line 54)
   - Changed `$this->clientFactory->make($shop)` → `PrestaShopClientFactory::create($shop)` (line 84)

**Deployment Commands:**
```powershell
pscp -i $HostidoKey -P 64321 `
  "app/Services/PrestaShop/PrestaShopPriceExporter.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/

plink ... -batch "cd domains/... && php artisan cache:clear && php artisan config:clear"
```

**Expected Result:** Price export works without factory method error

---

## 📝 PREVENTION CHECKLIST

### Before Creating New Services:

- [ ] Check if `PrestaShopClientFactory` methods are **STATIC**
- [ ] Use `PrestaShopClientFactory::create($shop)` - NOT `$this->clientFactory->make()`
- [ ] Do NOT inject factory as dependency - use static calls
- [ ] For strategies: receive `BasePrestaShopClient` as parameter
- [ ] For services: create client via static factory call

### Code Review Checklist:

```php
// ❌ WRONG PATTERNS
protected PrestaShopClientFactory $clientFactory;  // NO DI!
$this->clientFactory->make($shop);                 // NO make() method!
$factory = new PrestaShopClientFactory();          // NO instantiation!

// ✅ CORRECT PATTERNS
PrestaShopClientFactory::create($shop);                    // Static call
BasePrestaShopClient $client (parameter)                   // DI in strategies
PrestaShopClientFactory::createForAllActiveShops();        // Batch operations
```

---

## 📚 REFERENCES

- **Factory Class:** `app/Services/PrestaShop/PrestaShopClientFactory.php`
- **Base Client:** `app/Services/PrestaShop/BasePrestaShopClient.php`
- **Version Clients:**
  - `app/Services/PrestaShop/PrestaShop8Client.php`
  - `app/Services/PrestaShop/PrestaShop9Client.php`
- **Usage Examples:**
  - `app/Services/PrestaShop/PrestaShopAttributeSyncService.php` (lines 54, 137, 226)
  - `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` (client as parameter)

---

**Status:** ✅ RESOLVED (2025-11-14)
**Priority:** 🔥 CRITICAL (P0)
**Impact:** Price export now functional - specific_prices sync operational
