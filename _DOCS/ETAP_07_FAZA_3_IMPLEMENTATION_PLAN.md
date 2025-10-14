# ETAP_07 FAZA 3: WIDOCZNY STATUS SYNC + IMPORT UI + QUEUE SETUP

**Status:** 🛠️ IN PROGRESS (2025-10-03)
**Priority:** 🔴 KRYTYCZNE (user requirement)
**Estimated Time:** 12-16 godzin

---

## KONTEKST I UZASADNIENIE

**User Requirement (2025-10-03):**
> "Informacja czy produkt znajduje się na sklepie czy nie jest kluczowa nie tylko dla użytkownika ale i dla samej aplikacji PPM. Aplikacja MUSI zapisywać sobie numer ID produktu na prestashop jeżeli eksportuje produkt, lub gdy pobiera go ze sklepu prestashop, aby wiedzieć że produkt tam istnieje i nie tworzyć kolejnych kopii. Ułatwi to synchronizację danych i zoptymalizuje działanie aplikacji, która będzie wiedziała od razu gdzie szukać produktu powiązanego!"

**Diagnoza:**

✅ **Backend JUŻ DZIAŁA:**
- `product_sync_status` table MA pole `prestashop_product_id` ✅
- `ProductSyncStrategy` ZAPISUJE PrestaShop ID przy synchronizacji ✅
  ```php
  $syncStatus->update([
      'prestashop_product_id' => $externalId, // ProductSyncStrategy.php:134
  ]);
  ```
- Logika UPDATE vs CREATE działa na podstawie `prestashop_product_id` ✅
  ```php
  $isUpdate = !empty($syncStatus->prestashop_product_id); // Linia 107
  ```
- Checksum-based change detection działa ✅
- SyncProductToPrestaShop Job dispatches correctly ✅

❌ **CO BRAKUJE:**
- UI NIE pokazuje statusu synchronizacji użytkownikowi
- UI NIE pokazuje PrestaShop product ID
- Brak import UI (lista produktów z PrestaShop)
- Queue worker nie działa permanentnie
- DWA systemy statusów (ProductShopData.sync_status vs ProductSyncStatus - rozłączne)

---

## SEKCJA 1: WIDOCZNY STATUS SYNC W UI

### Zadanie 1.1: Computed Properties w ProductForm

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`

**Dodać metody:**

```php
/**
 * Get sync status for specific shop
 */
public function getSyncStatusForShop(int $shopId): ?ProductSyncStatus
{
    if (!$this->product) {
        return null;
    }

    return ProductSyncStatus::where('product_id', $this->product->id)
        ->where('shop_id', $shopId)
        ->first();
}

/**
 * Get sync status display data for shop
 * Returns formatted data for UI display
 */
public function getSyncStatusDisplay(int $shopId): array
{
    $syncStatus = $this->getSyncStatusForShop($shopId);

    if (!$syncStatus) {
        return [
            'status' => 'not_synced',
            'icon' => '⚪',
            'class' => 'text-gray-400',
            'text' => 'Nie synchronizowano',
            'prestashop_id' => null,
        ];
    }

    return match($syncStatus->sync_status) {
        'synced' => [
            'status' => 'synced',
            'icon' => '✅',
            'class' => 'text-green-600',
            'text' => 'Zsynchronizowany',
            'prestashop_id' => $syncStatus->prestashop_product_id,
            'last_sync' => $syncStatus->last_success_sync_at?->diffForHumans(),
        ],
        'pending' => [
            'status' => 'pending',
            'icon' => '⏳',
            'class' => 'text-yellow-600',
            'text' => 'Oczekuje',
            'prestashop_id' => $syncStatus->prestashop_product_id,
        ],
        'syncing' => [
            'status' => 'syncing',
            'icon' => '🔄',
            'class' => 'text-blue-600',
            'text' => 'Synchronizacja...',
            'prestashop_id' => $syncStatus->prestashop_product_id,
        ],
        'error' => [
            'status' => 'error',
            'icon' => '❌',
            'class' => 'text-red-600',
            'text' => 'Błąd',
            'prestashop_id' => $syncStatus->prestashop_product_id,
            'error_message' => $syncStatus->error_message,
            'retry_count' => $syncStatus->retry_count,
        ],
        'conflict' => [
            'status' => 'conflict',
            'icon' => '⚠️',
            'class' => 'text-orange-600',
            'text' => 'Konflikt',
            'prestashop_id' => $syncStatus->prestashop_product_id,
        ],
        default => [
            'status' => 'unknown',
            'icon' => '❓',
            'class' => 'text-gray-400',
            'text' => 'Nieznany',
            'prestashop_id' => $syncStatus->prestashop_product_id,
        ],
    };
}

/**
 * Retry failed sync for shop
 */
public function retrySync(int $shopId): void
{
    if (!$this->product) {
        return;
    }

    $syncStatus = $this->getSyncStatusForShop($shopId);
    if (!$syncStatus) {
        return;
    }

    // Reset error and dispatch new job
    $syncStatus->update([
        'sync_status' => 'pending',
        'error_message' => null,
    ]);

    $shop = PrestaShopShop::find($shopId);
    SyncProductToPrestaShop::dispatch($this->product, $shop);

    $this->dispatch('notify', [
        'type' => 'success',
        'message' => 'Synchronizacja została wznowiona',
    ]);
}
```

**Dodać do importów:**
```php
use App\Models\ProductSyncStatus;
```

---

### Zadanie 1.2: Relation w Product Model

**Plik:** `app/Models/Product.php`

**Dodać:**
```php
/**
 * Get sync status for all shops
 */
public function syncStatuses(): HasMany
{
    return $this->hasMany(ProductSyncStatus::class);
}

/**
 * Get sync status for specific shop
 */
public function syncStatusForShop(int $shopId): ?ProductSyncStatus
{
    return $this->syncStatuses()
        ->where('shop_id', $shopId)
        ->first();
}
```

**Dodać do importów:**
```php
use App\Models\ProductSyncStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

---

### Zadanie 1.3: UI Components - Shop Tab Status Badge

**Plik:** `resources/views/livewire/products/management/product-form.blade.php`

**Znaleźć sekcję shop tabs i dodać status badge:**

```blade
{{-- W sekcji gdzie renderowane są taby sklepów --}}
@foreach($availableShops as $shop)
    @php
        $syncDisplay = $this->getSyncStatusDisplay($shop->id);
    @endphp

    <button
        wire:click="setActiveShop({{ $shop->id }})"
        class="shop-tab {{ $activeShopId === $shop->id ? 'active' : '' }}"
    >
        <span class="shop-name">{{ $shop->shop_name }}</span>

        {{-- STATUS BADGE --}}
        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs {{ $syncDisplay['class'] }}">
            {{ $syncDisplay['icon'] }} {{ $syncDisplay['text'] }}
        </span>

        {{-- PrestaShop ID badge (if exists) --}}
        @if($syncDisplay['prestashop_id'])
            <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">
                #{{ $syncDisplay['prestashop_id'] }}
            </span>
        @endif
    </button>
@endforeach
```

---

### Zadanie 1.4: Sync Status Panel (Detailed)

**Plik:** `resources/views/livewire/products/management/product-form.blade.php`

**Dodać po headerze taba sklepu:**

```blade
{{-- Detailed Sync Status Panel --}}
@if($activeShopId && $isEditMode)
    @php
        $syncDisplay = $this->getSyncStatusDisplay($activeShopId);
        $syncStatus = $this->getSyncStatusForShop($activeShopId);
        $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
    @endphp

    <div class="mb-4 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            {{-- Status Info --}}
            <div class="flex items-center space-x-3">
                <span class="text-2xl">{{ $syncDisplay['icon'] }}</span>
                <div>
                    <h4 class="font-semibold {{ $syncDisplay['class'] }}">
                        Status synchronizacji: {{ $syncDisplay['text'] }}
                    </h4>

                    @if($syncDisplay['prestashop_id'])
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            PrestaShop ID: <strong>#{{ $syncDisplay['prestashop_id'] }}</strong>
                        </p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-500">
                            Produkt nie został jeszcze zsynchronizowany z tym sklepem
                        </p>
                    @endif

                    @if(isset($syncDisplay['last_sync']))
                        <p class="text-xs text-gray-500">
                            Ostatnia synchronizacja: {{ $syncDisplay['last_sync'] }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex space-x-2">
                @if($syncDisplay['status'] === 'error')
                    <button
                        wire:click="retrySync({{ $activeShopId }})"
                        class="btn-secondary text-sm"
                    >
                        🔄 Ponów synchronizację
                    </button>
                @endif

                @if($syncDisplay['prestashop_id'])
                    <a
                        href="{{ $currentShop->shop_url }}/admin-dev/index.php?controller=AdminProducts&id_product={{ $syncDisplay['prestashop_id'] }}"
                        target="_blank"
                        class="btn-secondary text-sm"
                    >
                        🔗 Otwórz w PrestaShop
                    </a>
                @endif
            </div>
        </div>

        {{-- Error Message Display --}}
        @if($syncDisplay['status'] === 'error' && isset($syncDisplay['error_message']))
            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                <p class="text-sm text-red-700 dark:text-red-400">
                    <strong>Błąd:</strong> {{ $syncDisplay['error_message'] }}
                </p>
                @if(isset($syncDisplay['retry_count']))
                    <p class="text-xs text-red-600 dark:text-red-500 mt-1">
                        Liczba prób: {{ $syncDisplay['retry_count'] }}
                    </p>
                @endif
            </div>
        @endif
    </div>
@endif
```

---

## SEKCJA 2: IMPORT PRODUKTÓW Z PRESTASHOP

### Zadanie 2.1: Import UI Button

**Plik:** `resources/views/livewire/products/management/product-form.blade.php`

**Dodać button import w headerze taba sklepu:**

```blade
{{-- W headerze taba sklepu --}}
<div class="mb-4 flex justify-between items-center">
    <h3 class="text-lg font-semibold">
        Produkty sklepu: {{ $currentShop->shop_name }}
    </h3>

    <button
        wire:click="showImportProductsModal"
        class="btn-secondary text-sm inline-flex items-center"
    >
        📥 Importuj z PrestaShop
    </button>
</div>
```

### Zadanie 2.2: Import Modal

**Plik:** `resources/views/livewire/products/management/product-form.blade.php`

**Dodać na końcu pliku (przed zamknięciem div):**

```blade
{{-- Import Products Modal --}}
@if($showImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" wire:click="closeImportModal"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full p-6 shadow-xl">
                <h3 class="text-xl font-bold mb-4">
                    Import produktów z PrestaShop: {{ $currentShop->shop_name ?? '' }}
                </h3>

                {{-- Search products --}}
                <div class="mb-4">
                    <input
                        wire:model.live.debounce.500ms="importSearch"
                        type="text"
                        placeholder="Szukaj produktów w PrestaShop po nazwie lub SKU..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                    >
                </div>

                {{-- Loading state --}}
                <div wire:loading wire:target="loadPrestashopProducts,importSearch" class="text-center py-8">
                    <p class="text-gray-600 dark:text-gray-400">
                        🔄 Ładowanie produktów z PrestaShop...
                    </p>
                </div>

                {{-- Products list --}}
                <div wire:loading.remove wire:target="loadPrestashopProducts,importSearch">
                    @if(!empty($prestashopProducts))
                        <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">ID PS</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">SKU</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">Nazwa</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">Akcja</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($prestashopProducts as $psProduct)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 text-sm">#{{ $psProduct['id'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-sm font-mono">{{ $psProduct['reference'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $psProduct['name'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                @if($this->productExistsInPPM($psProduct['reference'] ?? null))
                                                    <span class="text-yellow-600 dark:text-yellow-400">⚠️ Istnieje w PPM</span>
                                                @else
                                                    <span class="text-green-600 dark:text-green-400">✅ Nowy</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button
                                                    wire:click="previewImportProduct({{ $psProduct['id'] ?? 0 }})"
                                                    class="btn-primary text-xs px-3 py-1"
                                                >
                                                    📋 Importuj
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                            Brak produktów do wyświetlenia
                        </p>
                    @endif
                </div>

                {{-- Close button --}}
                <div class="mt-4 flex justify-end">
                    <button
                        wire:click="closeImportModal"
                        class="btn-secondary"
                    >
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

### Zadanie 2.3: Import Logic w ProductForm

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`

**Dodać properties:**
```php
public bool $showImportModal = false;
public string $importSearch = '';
public array $prestashopProducts = [];
```

**Dodać metody:**

```php
/**
 * Show import products modal
 */
public function showImportProductsModal(): void
{
    $this->showImportModal = true;
    $this->loadPrestashopProducts();
}

/**
 * Close import modal
 */
public function closeImportModal(): void
{
    $this->showImportModal = false;
    $this->importSearch = '';
    $this->prestashopProducts = [];
}

/**
 * Load products from PrestaShop
 * Called when modal opens or search changes
 */
public function loadPrestashopProducts(): void
{
    if (!$this->activeShopId) {
        return;
    }

    try {
        $shop = PrestaShopShop::find($this->activeShopId);
        if (!$shop) {
            throw new \RuntimeException('Shop not found');
        }

        $client = app(PrestaShopClientFactory::class)->create($shop);

        // Build filters
        $filters = [
            'display' => 'full',
            'limit' => 50,
        ];

        if (!empty($this->importSearch)) {
            $filters['filter[name]'] = "%{$this->importSearch}%";
        }

        $response = $client->getProducts($filters);

        // Extract products from response (handle both nested and flat structure)
        if (isset($response['products']) && is_array($response['products'])) {
            $this->prestashopProducts = $response['products'];
        } elseif (isset($response[0])) {
            $this->prestashopProducts = $response;
        } else {
            $this->prestashopProducts = [];
        }

        Log::info('Loaded PrestaShop products for import', [
            'shop_id' => $this->activeShopId,
            'count' => count($this->prestashopProducts),
            'search' => $this->importSearch,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to load PrestaShop products', [
            'shop_id' => $this->activeShopId,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Nie udało się pobrać produktów z PrestaShop: ' . $e->getMessage(),
        ]);

        $this->prestashopProducts = [];
    }
}

/**
 * Check if product exists in PPM by SKU
 */
public function productExistsInPPM(?string $sku): bool
{
    if (!$sku) {
        return false;
    }

    return Product::where('sku', $sku)->exists();
}

/**
 * Preview/Import product from PrestaShop
 * TODO: Implement full import workflow with preview
 */
public function previewImportProduct(int $prestashopProductId): void
{
    $this->dispatch('notify', [
        'type' => 'info',
        'message' => 'Import produktu #' . $prestashopProductId . ' - funkcja w implementacji (FAZA 3.2)',
    ]);
}
```

**Dodać watcher:**
```php
/**
 * Watch import search changes
 */
public function updatedImportSearch(): void
{
    $this->loadPrestashopProducts();
}
```

---

## SEKCJA 3: QUEUE WORKER PERMANENT SETUP

### Opcja A: Supervisor (Recommended)

**Konfiguracja:**
```ini
; /etc/supervisor/conf.d/ppm-queue-worker.conf

[program:ppm-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/host379076/domains/ppm.mpptrade.pl/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=host379076
numprocs=2
redirect_stderr=true
stdout_logfile=/home/host379076/domains/ppm.mpptrade.pl/public_html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Komendy:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ppm-queue-worker:*
sudo supervisorctl status ppm-queue-worker:*
```

### Opcja B: CRON (Fallback)

**Crontab entry:**
```bash
* * * * * cd /home/host379076/domains/ppm.mpptrade.pl/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

**Test:**
```bash
cd /home/host379076/domains/ppm.mpptrade.pl/public_html
php artisan queue:work --tries=3 --timeout=300
```

---

## DEPLOYMENT CHECKLIST

### Backend Changes:
- [ ] Dodać metody do ProductForm.php (getSyncStatusForShop, getSyncStatusDisplay, retrySync)
- [ ] Dodać properties do ProductForm.php (showImportModal, importSearch, prestashopProducts)
- [ ] Dodać import methods do ProductForm.php (showImportProductsModal, loadPrestashopProducts, etc.)
- [ ] Dodać relation syncStatuses() do Product.php model
- [ ] Dodać import do ProductForm: `use App\Models\ProductSyncStatus;`

### Frontend Changes:
- [ ] Zaktualizować shop tabs z status badges
- [ ] Dodać Sync Status Panel w product-form.blade.php
- [ ] Dodać Import button w header
- [ ] Dodać Import Modal na końcu product-form.blade.php

### Infrastructure:
- [ ] Skonfigurować supervisor dla queue worker
- [ ] LUB dodać CRON entry dla queue:work
- [ ] Przetestować queue worker (php artisan queue:work)

### Deployment:
- [ ] Upload ProductForm.php
- [ ] Upload Product.php
- [ ] Upload product-form.blade.php
- [ ] php artisan cache:clear
- [ ] php artisan view:clear
- [ ] php artisan config:clear

### Testing:
- [ ] Test: Sync status visible w shop tabs
- [ ] Test: PrestaShop ID visible w UI
- [ ] Test: Error status pokazuje retry button
- [ ] Test: Import modal opens i ładuje produkty
- [ ] Test: Queue worker przetwarza Jobs
- [ ] E2E: Pełny workflow sync produktu → verify status w UI

---

**SZACOWANY CZAS:** 12-16 godzin
**AGENTS:** frontend-specialist + livewire-specialist

**NASTĘPNY KROK:** Implementacja przez agents w parallel.