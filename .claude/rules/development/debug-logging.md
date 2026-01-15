---
paths: "app/**/*.php"
---

# Development: Debug Logging Best Practices

## Critical Rule
**Development: Extensive logging -> User confirms "dziala idealnie" -> Remove debug logs -> Deploy clean**

## Development Phase (Extensive Logging)
```php
// Add full context during development
Log::debug('removeFromShop CALLED', [
    'shop_id' => $shopId,
    'shop_id_type' => gettype($shopId),
    'exportedShops_BEFORE' => $this->exportedShops,
    'exportedShops_types' => array_map('gettype', $this->exportedShops),
]);

Log::debug('exportedShops AFTER array_values', [
    'exportedShops' => $this->exportedShops,
]);
```

## Production Phase (Keep Only These)
```php
// Log::info() - Important business operations
Log::info('Product created', ['product_id' => $product->id, 'sku' => $product->sku]);
Log::info('Product synced to PrestaShop', ['product_id' => $product->id, 'shop_id' => $shopId]);

// Log::warning() - Unusual but handled situations
Log::warning('Shop removal failed - not in list', ['shop_id' => $shopId]);
Log::warning('Product import skipped - duplicate SKU', ['sku' => $importedSku]);

// Log::error() - All errors and exceptions
Log::error('Product save failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
```

## Remove After User Confirmation
```php
// REMOVE ALL OF THESE:
Log::debug('methodName CALLED', [...]);
Log::debug('methodName COMPLETED', [...]);
Log::debug('variable_BEFORE', [...]);
Log::debug('variable_AFTER', [...]);
gettype($var);
array_map('gettype', $array);
```

## Workflow
1. Development: Add `Log::debug()` with full context
2. Deploy: Keep logs for user testing
3. User Testing: Wait for "dziala idealnie"
4. Cleanup: Remove `Log::debug()`, keep `Log::info/warning/error`
5. Final Deploy: Clean version

## Viewing Production Logs
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
```
