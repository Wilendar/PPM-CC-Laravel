# Debug Logging Best Practices - PPM-CC-Laravel

**Dokument:** Przewodnik debug logging - extensive development, minimal production
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Zasady Development → Debug Logging

---

## ⚠️ KRYTYCZNA ZASADA

**Podczas developmentu używaj zaawansowanych logów, po weryfikacji przez użytkownika je usuń!**

---

## 📊 DEVELOPMENT PHASE - Extensive Logging

### KIEDY: Podczas implementacji nowej funkcjonalności lub debugowania problemu

### CO LOGOWAĆ:

```php
// ✅ DEVELOPMENT - Zaawansowane logi z pełnym kontekstem

Log::debug('removeFromShop CALLED', [
    'shop_id' => $shopId,
    'shop_id_type' => gettype($shopId),
    'exportedShops_BEFORE' => $this->exportedShops,
    'exportedShops_types' => array_map('gettype', $this->exportedShops),
    'shopsToRemove_BEFORE' => $this->shopsToRemove,
]);

Log::debug('Save: Filtering shops to create', [
    'exportedShops' => $this->exportedShops,
    'shopsToRemove' => $this->shopsToRemove,
    'shopsToCreate' => $shopsToCreate,
]);

Log::debug('exportedShops AFTER array_values', [
    'exportedShops' => $this->exportedShops,
    'exportedShops_types' => array_map('gettype', $this->exportedShops),
]);

Log::debug('removeFromShop COMPLETED', [
    'exportedShops_AFTER' => $this->exportedShops,
    'shopsToRemove_AFTER' => $this->shopsToRemove,
]);
```

---

## 🏭 PRODUCTION PHASE - Minimal Logging

### KIEDY: Po weryfikacji przez użytkownika że wszystko działa

### CO POZOSTAWIĆ:

```php
// ✅ PRODUCTION - Tylko istotne operacje i błędy

Log::info('Shop marked for DB deletion on save', [
    'product_id' => $this->product?->id,
    'shop_id' => $shopId,
    'shopData_id' => $this->shopData[$shopId]['id'],
]);

Log::warning('removeFromShop ABORTED - shop not found', [
    'shop_id' => $shopId,
]);

Log::error('Product save failed', [
    'error' => $e->getMessage(),
    'product_id' => $this->product?->id,
    'trace' => $e->getTraceAsString(),
]);
```

### CO USUNĄĆ:

```php
// ❌ USUŃ po weryfikacji

Log::debug('...'); // Wszystkie logi debug
Log::debug('exportedShops_BEFORE', ...); // Stan przed operacją
Log::debug('exportedShops_types', ...); // Informacje o typach
Log::debug('removeFromShop CALLED', ...); // Function entry logs
Log::debug('removeFromShop COMPLETED', ...); // Function exit logs
```

---

## 🔄 WORKFLOW

### 1. Development: Dodaj `Log::debug()` z pełnym kontekstem

```php
public function removeFromShop(int $shopId): void
{
    $shopId = (int) $shopId;

    Log::debug('removeFromShop CALLED', [
        'shop_id' => $shopId,
        'shop_id_type' => gettype($shopId),
        'exportedShops_BEFORE' => $this->exportedShops,
        'exportedShops_types' => array_map('gettype', $this->exportedShops),
        'shopsToRemove_BEFORE' => $this->shopsToRemove,
    ]);

    $key = array_search($shopId, $this->exportedShops, false);

    Log::debug('array_search result', [
        'key' => $key,
        'key_type' => gettype($key),
    ]);

    if ($key === false) {
        Log::debug('removeFromShop ABORTED - shop not in exportedShops', [
            'shop_id' => $shopId,
            'exportedShops' => $this->exportedShops,
        ]);
        return;
    }

    unset($this->exportedShops[$key]);
    $this->exportedShops = array_values($this->exportedShops);

    Log::debug('exportedShops AFTER array_values', [
        'exportedShops' => $this->exportedShops,
        'exportedShops_types' => array_map('gettype', $this->exportedShops),
    ]);

    if (isset($this->shopData[$shopId]['id']) && $this->shopData[$shopId]['id'] !== null) {
        $this->shopsToRemove[] = $shopId;
        Log::debug('Added to shopsToRemove', [
            'shop_id' => $shopId,
            'shopsToRemove' => $this->shopsToRemove,
        ]);
    }

    Log::debug('removeFromShop COMPLETED', [
        'exportedShops_AFTER' => $this->exportedShops,
        'shopsToRemove_AFTER' => $this->shopsToRemove,
    ]);
}
```

### 2. Deploy na produkcję: Wszystkie logi zostają (dla testów)

```bash
# Deploy with debug logs
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/ProductForm.php" ...
plink ... "php artisan cache:clear"
```

### 3. User Testing: Użytkownik weryfikuje funkcjonalność

```
User: "Testuje funkcję usuwania sklepów..."
User: "Działa idealnie! ✅"
```

### 4. User Confirmation: ✅ "działa idealnie"

**TERAZ możesz przystąpić do cleanup!**

### 5. Cleanup: Usuń `Log::debug()`, zostaw tylko `Log::info/warning/error`

```php
public function removeFromShop(int $shopId): void
{
    $shopId = (int) $shopId;

    $key = array_search($shopId, $this->exportedShops, false);
    if ($key === false) {
        Log::warning('Shop removal failed - not in list', ['shop_id' => $shopId]);
        return;
    }

    unset($this->exportedShops[$key]);
    $this->exportedShops = array_values($this->exportedShops);

    if (isset($this->shopData[$shopId]['id']) && $this->shopData[$shopId]['id'] !== null) {
        $this->shopsToRemove[] = $shopId;
        Log::info('Shop marked for deletion', [
            'product_id' => $this->product?->id,
            'shop_id' => $shopId,
        ]);
    }
}
```

### 6. Final Deploy: Clean version bez debug logów

```bash
# Deploy clean version
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/ProductForm.php" ...
plink ... "php artisan cache:clear"
```

---

## 📋 PRODUCTION LOGGING RULES

### ✅ ZOSTAW:

#### `Log::info()` - Ważne operacje biznesowe

```php
// CREATE operations
Log::info('Product created', [
    'product_id' => $product->id,
    'sku' => $product->sku,
    'user_id' => auth()->id(),
]);

// UPDATE operations
Log::info('Product updated', [
    'product_id' => $product->id,
    'changed_fields' => array_keys($product->getDirty()),
]);

// DELETE operations
Log::info('Product deleted', [
    'product_id' => $product->id,
    'sku' => $product->sku,
]);

// SYNC operations
Log::info('Product synced to PrestaShop', [
    'product_id' => $product->id,
    'shop_id' => $shopId,
    'prestashop_product_id' => $prestashopProductId,
]);
```

#### `Log::warning()` - Nietypowe sytuacje które nie są błędami

```php
// Unexpected but handled situations
Log::warning('Shop removal failed - not in list', [
    'shop_id' => $shopId,
    'available_shops' => $this->exportedShops,
]);

Log::warning('Product import skipped - duplicate SKU', [
    'sku' => $importedSku,
    'existing_product_id' => $existingProduct->id,
]);

Log::warning('PrestaShop API rate limit approaching', [
    'remaining_requests' => $remainingRequests,
    'reset_time' => $resetTime,
]);
```

#### `Log::error()` - Wszystkie błędy i exceptions

```php
// Critical errors
Log::error('Product save failed', [
    'error' => $e->getMessage(),
    'product_id' => $this->product?->id,
    'trace' => $e->getTraceAsString(),
]);

Log::error('PrestaShop API connection failed', [
    'shop_id' => $shopId,
    'error' => $e->getMessage(),
    'url' => $apiUrl,
]);

Log::error('Database transaction rolled back', [
    'operation' => 'bulk_product_sync',
    'error' => $e->getMessage(),
    'affected_products' => $productIds,
]);
```

### ❌ USUŃ:

```php
// ❌ Log::debug() - Wszelkie debug logi
Log::debug('Function CALLED', ...);
Log::debug('Function COMPLETED', ...);

// ❌ Logi typu "BEFORE/AFTER"
Log::debug('exportedShops_BEFORE', ...);
Log::debug('exportedShops_AFTER', ...);

// ❌ Logi z typami danych
Log::debug('shop_id_type', ['type' => gettype($shopId)]);
Log::debug('exportedShops_types', array_map('gettype', $this->exportedShops));

// ❌ Logi "CALLED/COMPLETED"
Log::debug('removeFromShop CALLED', ...);
Log::debug('removeFromShop COMPLETED', ...);

// ❌ Intermediate step logging
Log::debug('array_search result', ...);
Log::debug('After array_values', ...);
```

---

## 📊 EXAMPLE - Before/After

### ❌ DEVELOPMENT VERSION (verbose)

```php
public function save(): void
{
    Log::debug('save CALLED', [
        'product_id' => $this->product?->id,
        'exportedShops' => $this->exportedShops,
        'shopsToRemove' => $this->shopsToRemove,
    ]);

    try {
        DB::transaction(function () {
            Log::debug('Transaction started', [
                'product_id' => $this->product?->id,
            ]);

            // Save product
            $this->product->save();

            Log::debug('Product saved', [
                'product_id' => $this->product->id,
            ]);

            // Filter shops to create
            $shopsToCreate = array_filter(
                $this->exportedShops,
                fn($shopId) => !in_array($shopId, $this->shopsToRemove, true)
            );

            Log::debug('Filtering shops to create', [
                'exportedShops' => $this->exportedShops,
                'shopsToRemove' => $this->shopsToRemove,
                'shopsToCreate' => $shopsToCreate,
            ]);

            // Create shop data
            foreach ($shopsToCreate as $shopId) {
                Log::debug('Creating shop data', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product->id,
                ]);

                ProductShopData::create([
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
            }

            // Remove shop data
            foreach ($this->shopsToRemove as $shopId) {
                Log::debug('Removing shop data', [
                    'shop_id' => $shopId,
                    'product_id' => $this->product->id,
                ]);

                ProductShopData::where('product_id', $this->product->id)
                    ->where('shop_id', $shopId)
                    ->delete();
            }

            Log::debug('Transaction completed', [
                'product_id' => $this->product->id,
            ]);
        });

        Log::debug('save COMPLETED', [
            'product_id' => $this->product->id,
        ]);

        session()->flash('success', 'Product saved successfully');
    } catch (\Exception $e) {
        Log::error('save FAILED', [
            'error' => $e->getMessage(),
            'product_id' => $this->product?->id,
            'trace' => $e->getTraceAsString(),
        ]);

        session()->flash('error', 'Failed to save product');
    }
}
```

### ✅ PRODUCTION VERSION (clean)

```php
public function save(): void
{
    try {
        DB::transaction(function () {
            // Save product
            $this->product->save();

            // Filter shops to create
            $shopsToCreate = array_filter(
                $this->exportedShops,
                fn($shopId) => !in_array($shopId, $this->shopsToRemove, true)
            );

            // Create shop data
            foreach ($shopsToCreate as $shopId) {
                ProductShopData::create([
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
            }

            // Remove shop data
            foreach ($this->shopsToRemove as $shopId) {
                ProductShopData::where('product_id', $this->product->id)
                    ->where('shop_id', $shopId)
                    ->delete();
            }
        });

        Log::info('Product saved successfully', [
            'product_id' => $this->product->id,
            'shops_added' => count(array_filter(
                $this->exportedShops,
                fn($shopId) => !in_array($shopId, $this->shopsToRemove, true)
            )),
            'shops_removed' => count($this->shopsToRemove),
        ]);

        session()->flash('success', 'Product saved successfully');
    } catch (\Exception $e) {
        Log::error('Product save failed', [
            'error' => $e->getMessage(),
            'product_id' => $this->product?->id,
            'trace' => $e->getTraceAsString(),
        ]);

        session()->flash('error', 'Failed to save product');
    }
}
```

---

## 🔍 MONITORING PRODUCTION LOGS

### Laravel Log Levels (priorytetyzacja)

```php
// Emergency: System is unusable
Log::emergency('Database connection completely failed');

// Alert: Action must be taken immediately
Log::alert('Payment gateway unreachable for 5+ minutes');

// Critical: Critical conditions
Log::critical('Disk space below 5%');

// Error: Runtime errors (NIE wymagające natychmiastowej akcji)
Log::error('Product save failed', [...]);

// Warning: Exceptional occurrences (NIE błędy)
Log::warning('Shop removal failed - not in list', [...]);

// Notice: Normal but significant events
Log::notice('Admin user logged in from new IP', [...]);

// Info: Interesting events (business operations)
Log::info('Product synced to PrestaShop', [...]);

// Debug: Detailed debug information (PRODUCTION: NIE UŻYWAĆ)
Log::debug('Function called with params', [...]);
```

### Viewing Logs on Production

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Tail latest logs (last 50 lines)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Search for specific errors
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "grep 'ERROR' domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | tail -n 20"

# Follow logs in real-time
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey `
  "tail -f domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
```

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Główne zasady debug logging
- **_ISSUES_FIXES/DEBUG_LOGGING_BEST_PRACTICES.md** - Extended issues guide
- **_DOCS/DEPLOYMENT_GUIDE.md** - Deployment workflow

---

## 🧹 CLEANUP CHECKLIST

Po weryfikacji przez użytkownika:

- [ ] Usuń wszystkie `Log::debug()` calls
- [ ] Usuń logi "CALLED/COMPLETED"
- [ ] Usuń logi "BEFORE/AFTER"
- [ ] Usuń logi z `gettype()` / `array_map('gettype')`
- [ ] Zachowaj `Log::info()` dla operacji biznesowych
- [ ] Zachowaj `Log::warning()` dla nietypowych sytuacji
- [ ] Zachowaj `Log::error()` dla wszystkich błędów
- [ ] Deploy clean version
- [ ] Verify logs readable and concise

---

**PAMIĘTAJ:** Development = Extensive, Production = Minimal!
