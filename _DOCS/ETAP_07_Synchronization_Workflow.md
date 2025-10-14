# ETAP_07 - Synchronization Workflow

**Data utworzenia:** 2025-10-01
**Scope:** Workflow synchronizacji produktów i kategorii PPM ↔ PrestaShop
**Status:** 🛠️ FAZA 1 - PPM → PrestaShop (bez zdjęć)

**📌 Ten dokument jest częścią:** [Plan_Projektu/ETAP_07_Prestashop_API.md](../Plan_Projektu/ETAP_07_Prestashop_API.md)

**📚 Powiązane dokumenty:**
- **[ETAP_07_Prestashop_API.md](../Plan_Projektu/ETAP_07_Prestashop_API.md)** - High-level plan całego ETAP_07 (wszystkie fazy)
- **[ETAP_07_FAZA_1_Implementation_Plan.md](ETAP_07_FAZA_1_Implementation_Plan.md)** - Szczegółowy plan implementacji FAZA 1
- **[Struktura_Bazy_Danych.md](Struktura_Bazy_Danych.md)** - Sekcja ETAP_07 (3 nowe tabele)
- **[Struktura_Plikow_Projektu.md](Struktura_Plikow_Projektu.md)** - Sekcja ETAP_07 (struktura folderów)

---

## 📋 SPIS TREŚCI

- [Ogólny Przegląd Synchronizacji](#ogólny-przegląd-synchronizacji)
- [Workflow Synchronizacji Produktu](#workflow-synchronizacji-produktu)
- [Workflow Synchronizacji Kategorii](#workflow-synchronizacji-kategorii)
- [Data Transformation Flow](#data-transformation-flow)
- [Error Handling & Retry Logic](#error-handling--retry-logic)
- [Conflict Resolution](#conflict-resolution)
- [Performance Optimization](#performance-optimization)

---

## 🔄 OGÓLNY PRZEGLĄD SYNCHRONIZACJI

### Kierunki Synchronizacji:

#### FAZA 1 (Current):
```
PPM (Master) ──→ PrestaShop (Slave)
    │
    ├─→ Produkty (name, sku, description, price, stock)
    ├─→ Kategorie (hierarchy, names)
    ├─→ Mapowania (categories, price_groups, warehouses)
    └─→ Status tracking
```

#### FAZA 2 (Future):
```
PPM (Master) ←──→ PrestaShop (Partial Slave)
    │
    ├─→ Products → PS (primary direction)
    ├─← Stock ← PS (stock updates from PS)
    ├─← Orders ← PS (order data import)
    └─→ Images → PS (media sync)
```

### Trigery Synchronizacji:

| Trigger | Typ | Opis | FAZA |
|---------|-----|------|------|
| **Manual Button** | User action | "Synchronizuj teraz" w ProductForm | ✅ FAZA 1 |
| **Bulk Sync** | User action | "Sync All Products" w ShopManager | ✅ FAZA 1 |
| **Auto on Save** | Event | ProductObserver → saved event | ❌ FAZA 2 |
| **Scheduled Sync** | Cron | Laravel scheduler - co godzinę | ❌ FAZA 2 |
| **Webhook** | PS → PPM | PrestaShop webhook event | ❌ FAZA 3 |

---

## 📦 WORKFLOW SYNCHRONIZACJI PRODUKTU

### Diagram Flow:

```
┌─────────────────────────────────────────────────────────────────┐
│ USER ACTION: Click "Synchronizuj do {Shop}"                     │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ LIVEWIRE: ProductForm->syncToShop($shopId)                      │
│  ├─ Validate: Product has required fields                       │
│  ├─ Validate: Shop is active and connected                      │
│  └─ Dispatch: SyncProductToPrestaShop job                       │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ QUEUE JOB: SyncProductToPrestaShop::handle()                    │
│  ├─ Load: Product with relationships (prices, stock, category)  │
│  ├─ Load: Shop configuration                                    │
│  ├─ Create: API Client via Factory                              │
│  └─ Execute: ProductSyncStrategy->syncToPrestaShop()            │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ STRATEGY: ProductSyncStrategy->syncToPrestaShop()               │
│  ├─ Start Transaction                                           │
│  ├─ Get/Create: ProductSyncStatus record                        │
│  ├─ Update Status: 'syncing'                                    │
│  ├─ Calculate: Current checksum                                 │
│  ├─ Check: If changed since last sync                           │
│  └─ Continue to Transformation                                  │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ TRANSFORMATION: ProductTransformer->transformForPrestaShop()    │
│  ├─ Map: Category (PPM ID → PrestaShop ID)                      │
│  ├─ Map: Price Group (detaliczna → PS customer group)           │
│  ├─ Map: Warehouse (MPPTRADE → PS location)                     │
│  ├─ Transform: Product data to PS format                        │
│  │   ├─ name → multilang array                                  │
│  │   ├─ description → multilang HTML                            │
│  │   ├─ reference → SKU                                         │
│  │   ├─ price → decimal with tax                                │
│  │   ├─ quantity → stock from mapped warehouse                  │
│  │   └─ id_category_default → mapped category                   │
│  └─ Return: Transformed array                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ API CALL: PrestaShop8Client->createProduct() OR updateProduct() │
│  ├─ Check: If prestashop_product_id exists in sync_status       │
│  ├─ IF NEW:                                                      │
│  │   └─ POST /api/products                                      │
│  ├─ IF EXISTS:                                                   │
│  │   └─ PUT /api/products/{id}                                  │
│  ├─ Retry: 3 attempts with exponential backoff                  │
│  ├─ Log: Request/response to sync_logs                          │
│  └─ Return: PrestaShop response                                 │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ RESPONSE HANDLING: ProductSyncStrategy->updateSyncStatus()      │
│  ├─ IF SUCCESS:                                                  │
│  │   ├─ Store: prestashop_product_id                            │
│  │   ├─ Update Status: 'synced'                                 │
│  │   ├─ Update: last_success_sync_at                            │
│  │   ├─ Store: checksum                                         │
│  │   ├─ Reset: retry_count = 0                                  │
│  │   └─ Commit Transaction                                      │
│  ├─ IF ERROR:                                                    │
│  │   ├─ Update Status: 'error'                                  │
│  │   ├─ Store: error_message                                    │
│  │   ├─ Increment: retry_count                                  │
│  │   ├─ Log: SyncLog with error details                         │
│  │   └─ Rollback Transaction                                    │
│  └─ Dispatch Event: ProductSyncCompleted                        │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ UI UPDATE: Livewire refresh component                           │
│  ├─ Display: Success/Error message                              │
│  ├─ Update: Sync status badge                                   │
│  └─ Refresh: Sync status cards                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Szczegółowe Kroki:

#### 1. Inicjalizacja Synchronizacji

```php
// ProductForm.php
public function syncToShop($shopId)
{
    // 1. Walidacja
    if (!$this->product->exists) {
        $this->addError('sync', 'Zapisz produkt przed synchronizacją');
        return;
    }

    $shop = PrestaShopShop::findOrFail($shopId);

    if (!$shop->sync_enabled) {
        $this->addError('sync', 'Synchronizacja wyłączona dla tego sklepu');
        return;
    }

    // 2. Dispatch job do queue
    SyncProductToPrestaShop::dispatch($this->product, $shop);

    // 3. UI feedback
    $this->dispatch('sync-started', [
        'product_id' => $this->product->id,
        'shop_id' => $shop->id
    ]);

    session()->flash('message', "Synchronizacja rozpoczęta: {$shop->name}");
}
```

#### 2. Queue Job Execution

```php
// SyncProductToPrestaShop.php
public function handle(ProductSyncStrategy $strategy)
{
    try {
        // Load z relacjami (avoid N+1)
        $product = $this->product->load([
            'category',
            'prices',
            'stock',
            'shopData' => fn($q) => $q->where('shop_id', $this->shop->id)
        ]);

        // Create API client
        $client = PrestaShopClientFactory::create($this->shop);

        // Execute sync strategy
        $result = $strategy->syncToPrestaShop($product, $client, $this->shop);

        // Log success
        Log::info('Product synced to PrestaShop', [
            'product_id' => $product->id,
            'shop_id' => $this->shop->id,
            'ps_product_id' => $result['ps_product_id']
        ]);

    } catch (\Exception $e) {
        // Log error
        Log::error('Product sync failed', [
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Re-throw to trigger failed()
        throw $e;
    }
}

public function failed(\Exception $exception)
{
    // Mark jako error w bazie
    ProductSyncStatus::updateOrCreate([
        'product_id' => $this->product->id,
        'shop_id' => $this->shop->id
    ], [
        'sync_status' => 'error',
        'error_message' => $exception->getMessage(),
        'retry_count' => DB::raw('retry_count + 1')
    ]);

    // Powiadom użytkownika (opcjonalnie)
    // Notification::send(...);
}
```

#### 3. Sync Strategy

```php
// ProductSyncStrategy.php
public function syncToPrestaShop(Product $product, BasePrestaShopClient $client, PrestaShopShop $shop): array
{
    DB::beginTransaction();

    try {
        // 1. Get/Create sync status
        $syncStatus = ProductSyncStatus::firstOrCreate([
            'product_id' => $product->id,
            'shop_id' => $shop->id
        ]);

        // 2. Mark as syncing
        $syncStatus->update(['sync_status' => 'syncing']);

        // 3. Calculate checksum (detect changes)
        $currentChecksum = $this->calculateChecksum($product);

        // Skip if unchanged
        if ($syncStatus->checksum === $currentChecksum && $syncStatus->prestashop_product_id) {
            Log::info('Product unchanged, skipping sync', [
                'product_id' => $product->id,
                'checksum' => $currentChecksum
            ]);

            $syncStatus->update(['sync_status' => 'synced']);
            DB::commit();
            return ['skipped' => true];
        }

        // 4. Transform data
        $productData = $this->transformer->transformForPrestaShop($product, $client, $shop);

        // 5. API call
        if ($syncStatus->prestashop_product_id) {
            // UPDATE existing
            $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
        } else {
            // CREATE new
            $response = $client->createProduct($productData);
            $syncStatus->prestashop_product_id = $response['product']['id'];
        }

        // 6. Update sync status
        $syncStatus->update([
            'sync_status' => 'synced',
            'last_success_sync_at' => now(),
            'checksum' => $currentChecksum,
            'error_message' => null,
            'retry_count' => 0
        ]);

        DB::commit();

        return [
            'success' => true,
            'ps_product_id' => $syncStatus->prestashop_product_id
        ];

    } catch (\Exception $e) {
        DB::rollBack();

        // Store error
        $syncStatus->update([
            'sync_status' => 'error',
            'error_message' => $e->getMessage()
        ]);

        throw $e;
    }
}

protected function calculateChecksum(Product $product): string
{
    $data = [
        'name' => $product->name,
        'description' => $product->description,
        'sku' => $product->sku,
        'category_id' => $product->category_id,
        'prices' => $product->prices->map(fn($p) => [
            'group' => $p->price_group,
            'price' => $p->price
        ])->toArray(),
        'stock' => $product->stock->map(fn($s) => [
            'warehouse' => $s->warehouse_code,
            'qty' => $s->quantity
        ])->toArray(),
        'updated_at' => $product->updated_at->timestamp
    ];

    return md5(json_encode($data));
}
```

#### 4. Data Transformation

```php
// ProductTransformer.php
public function transformForPrestaShop(Product $product, BasePrestaShopClient $client, PrestaShopShop $shop): array
{
    return [
        // Multilang fields
        'name' => [
            'language' => [
                ['id' => 1, 'value' => $product->name],
                ['id' => 2, 'value' => $product->name_en ?? $product->name]
            ]
        ],

        'description' => [
            'language' => [
                ['id' => 1, 'value' => $product->description ?? ''],
                ['id' => 2, 'value' => $product->description_en ?? $product->description ?? '']
            ]
        ],

        'description_short' => [
            'language' => [
                ['id' => 1, 'value' => Str::limit($product->description ?? '', 400)],
                ['id' => 2, 'value' => Str::limit($product->description_en ?? $product->description ?? '', 400)]
            ]
        ],

        // Basic fields
        'reference' => $product->sku,
        'active' => $product->is_active ? 1 : 0,
        'visibility' => 'both', // both, catalog, search, none

        // Price
        'price' => $this->transformPrice($product, $shop),

        // Stock
        'quantity' => $this->transformStock($product, $shop),

        // Categories
        'id_category_default' => $this->mapCategory($product->category_id, $shop),
        'associations' => [
            'categories' => $this->mapAllCategories($product, $shop)
        ],

        // Dimensions & Weight
        'weight' => $product->weight ?? 0,
        'width' => $product->width ?? 0,
        'height' => $product->height ?? 0,
        'depth' => $product->depth ?? 0,

        // Meta
        'meta_description' => [
            'language' => [
                ['id' => 1, 'value' => $product->meta_description ?? ''],
                ['id' => 2, 'value' => $product->meta_description_en ?? '']
            ]
        ],

        'meta_keywords' => [
            'language' => [
                ['id' => 1, 'value' => $product->meta_keywords ?? ''],
                ['id' => 2, 'value' => $product->meta_keywords_en ?? '']
            ]
        ],

        'link_rewrite' => [
            'language' => [
                ['id' => 1, 'value' => Str::slug($product->name)],
                ['id' => 2, 'value' => Str::slug($product->name_en ?? $product->name)]
            ]
        ],

        // SEO
        'available_for_order' => 1,
        'show_price' => 1,
        'online_only' => 0,

        // Advanced
        'id_manufacturer' => $this->mapManufacturer($product, $shop),
        'id_supplier' => $this->mapSupplier($product, $shop),
        'ean13' => $product->ean13 ?? '',
        'isbn' => $product->isbn ?? '',
        'upc' => $product->upc ?? '',
    ];
}

protected function transformPrice(Product $product, PrestaShopShop $shop): float
{
    // Get price mapping for shop
    $priceMapping = $shop->mappings()
        ->where('mapping_type', 'price_group')
        ->where('ppm_value', 'detaliczna')
        ->first();

    // Get price for mapped group
    $price = $product->prices
        ->where('price_group', 'detaliczna')
        ->first();

    return $price ? (float) $price->price : 0.00;
}

protected function transformStock(Product $product, PrestaShopShop $shop): int
{
    // Get warehouse mapping for shop
    $warehouseMapping = $shop->mappings()
        ->where('mapping_type', 'warehouse')
        ->first();

    if (!$warehouseMapping) {
        // Fallback: sum all warehouses
        return $product->stock->sum('quantity');
    }

    // Get stock from mapped warehouse
    $stock = $product->stock
        ->where('warehouse_code', $warehouseMapping->ppm_value)
        ->first();

    return $stock ? (int) $stock->quantity : 0;
}

protected function mapCategory(int $categoryId, PrestaShopShop $shop): ?int
{
    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', 'category')
        ->where('ppm_value', $categoryId)
        ->first();

    return $mapping?->prestashop_id;
}
```

---

## 🏷️ WORKFLOW SYNCHRONIZACJI KATEGORII

### Specyfika Kategorii:
- **Hierarchia:** 5 poziomów zagnieżdżenia (Category → Category4)
- **Zależności:** Parent category MUSI istnieć przed child
- **Kolejność:** Sync od root do leaf (top-down)

### Diagram Flow:

```
┌─────────────────────────────────────────────────────────────────┐
│ USER ACTION: Click "Synchronizuj Hierarchię Kategorii"          │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ LIVEWIRE: ShopManager->syncCategoriesToShop($shopId)            │
│  └─ Dispatch: SyncCategoryHierarchyToPrestaShop job             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ STRATEGY: CategorySyncStrategy->syncCategoryHierarchy()         │
│  ├─ Load: ALL categories ordered by level (0→4)                 │
│  ├─ FOR EACH level (0, 1, 2, 3, 4):                             │
│  │   ├─ Get categories at this level                            │
│  │   └─ FOR EACH category:                                      │
│  │       ├─ Ensure parent exists in PrestaShop                  │
│  │       ├─ Transform category data                             │
│  │       ├─ Create/Update in PrestaShop                         │
│  │       └─ Create mapping (PPM ID → PS ID)                     │
│  └─ Return: Sync summary                                        │
└─────────────────────────────────────────────────────────────────┘
```

### Kod Implementacji:

```php
// CategorySyncStrategy.php
public function syncCategoryHierarchy(PrestaShopShop $shop): array
{
    $client = PrestaShopClientFactory::create($shop);
    $synced = [];
    $errors = [];

    // Sortuj po level (0→4) aby parent zawsze był przed child
    $categories = Category::orderBy('level')
        ->orderBy('parent_id')
        ->orderBy('name')
        ->get();

    foreach ($categories as $category) {
        try {
            // 1. Ensure parent mapped (jeśli nie root)
            if ($category->parent_id) {
                $parentMapping = $this->ensureParentExists($category, $shop, $client);

                if (!$parentMapping) {
                    throw new \Exception("Parent category not found: {$category->parent_id}");
                }
            }

            // 2. Check if already mapped
            $existingMapping = ShopMapping::where('shop_id', $shop->id)
                ->where('mapping_type', 'category')
                ->where('ppm_value', $category->id)
                ->first();

            // 3. Transform data
            $categoryData = $this->transformer->transformForPrestaShop($category, $client, $shop);

            // 4. Create or update in PrestaShop
            if ($existingMapping && $existingMapping->prestashop_id) {
                // UPDATE
                $response = $client->updateCategory($existingMapping->prestashop_id, $categoryData);
                $psId = $existingMapping->prestashop_id;
            } else {
                // CREATE
                $response = $client->createCategory($categoryData);
                $psId = $response['category']['id'];

                // 5. Create mapping
                ShopMapping::create([
                    'shop_id' => $shop->id,
                    'mapping_type' => 'category',
                    'ppm_value' => $category->id,
                    'prestashop_id' => $psId,
                    'prestashop_value' => $category->name,
                    'is_active' => true
                ]);
            }

            $synced[] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'ps_id' => $psId
            ];

        } catch (\Exception $e) {
            $errors[] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'error' => $e->getMessage()
            ];

            Log::error('Category sync failed', [
                'category_id' => $category->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    return [
        'synced' => count($synced),
        'errors' => count($errors),
        'details' => [
            'synced' => $synced,
            'errors' => $errors
        ]
    ];
}

protected function ensureParentExists(Category $category, PrestaShopShop $shop, BasePrestaShopClient $client): ?ShopMapping
{
    if (!$category->parent_id) {
        return null; // Root category
    }

    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', 'category')
        ->where('ppm_value', $category->parent_id)
        ->first();

    // Parent musi być już zsynchronizowany (top-down approach)
    if (!$mapping || !$mapping->prestashop_id) {
        throw new \Exception("Parent category not synced yet: {$category->parent_id}");
    }

    return $mapping;
}
```

---

## 🔀 DATA TRANSFORMATION FLOW

### Product Data Flow:

```
┌────────────────────────┐
│ PPM Product Model      │
│ ────────────────────   │
│ • id                   │
│ • sku                  │
│ • name                 │
│ • description          │
│ • category_id          │
│ • is_active            │
│ • weight/dimensions    │
│ • created_at           │
│ • updated_at           │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐
│ Relationships Load     │
│ ────────────────────   │
│ • prices (hasMany)     │
│ • stock (hasMany)      │
│ • category (belongsTo) │
│ • variants (hasMany)   │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐
│ Mapping Layer          │
│ ────────────────────   │
│ • Category Mapper      │
│ • Price Group Mapper   │
│ • Warehouse Mapper     │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐
│ ProductTransformer     │
│ ────────────────────   │
│ • name → multilang[]   │
│ • desc → multilang[]   │
│ • category → PS ID     │
│ • price → mapped price │
│ • stock → mapped stock │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐
│ PrestaShop API Format  │
│ ────────────────────   │
│ {                      │
│   "name": {            │
│     "language": [...]  │
│   },                   │
│   "reference": "SKU",  │
│   "price": 123.45,     │
│   ...                  │
│ }                      │
└────────────────────────┘
```

---

## ⚠️ ERROR HANDLING & RETRY LOGIC

### Error Categories:

| Error Type | HTTP | Action | Retry | User Notification |
|------------|------|--------|-------|-------------------|
| **Network Timeout** | - | Retry | 3x | Po 3 próbach |
| **Invalid Credentials** | 401 | Stop | No | Natychmiast |
| **Rate Limit** | 429 | Backoff | 5x | Po wyczerpaniu |
| **Invalid Data** | 400 | Stop | No | Natychmiast |
| **Resource Not Found** | 404 | Check mapping | 1x | Jeśli persist |
| **Server Error** | 500 | Retry | 3x | Po 3 próbach |
| **Conflict** | 409 | Resolve | Manual | Natychmiast |

### Retry Strategy:

```php
// SyncProductToPrestaShop.php
public $tries = 3;
public $timeout = 120;
public $backoff = [5, 30, 120]; // seconds

public function retryUntil()
{
    return now()->addHour(); // Max 1h retry window
}
```

### Error Logging:

```php
// ProductSyncStrategy.php
catch (\Exception $e) {
    // Store w sync_logs
    SyncLog::create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'operation' => 'sync_product',
        'direction' => 'ppm_to_ps',
        'status' => 'error',
        'message' => $e->getMessage(),
        'request_data' => json_encode($productData),
        'response_data' => json_encode($e->getResponse() ?? []),
        'http_status_code' => $e->getCode(),
    ]);

    // Update sync status
    ProductSyncStatus::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->update([
            'sync_status' => 'error',
            'error_message' => $e->getMessage(),
            'retry_count' => DB::raw('retry_count + 1')
        ]);

    throw $e;
}
```

---

## 🔀 CONFLICT RESOLUTION

### Conflict Scenarios:

#### 1. Product Modified in Both Systems:
```
PPM Product updated_at: 2025-10-01 10:00
PS Product updated_at: 2025-10-01 10:30

RESOLUTION (FAZA 1): PPM wins (master)
RESOLUTION (FAZA 2): Manual conflict UI
```

#### 2. Category Mapping Missing:
```
Product.category_id = 15
ShopMapping not found for category 15

RESOLUTION:
1. Check if parent categories synced
2. Sync category hierarchy first
3. Retry product sync
```

#### 3. PrestaShop Product Deleted:
```
ProductSyncStatus.prestashop_product_id = 123
API returns 404 Not Found

RESOLUTION:
1. Clear prestashop_product_id
2. Create new product
3. Update mapping
```

---

## ⚡ PERFORMANCE OPTIMIZATION

### Batch Processing:

```php
// BulkSyncProducts.php
public function handle()
{
    // Chunk products dla memory efficiency
    $this->products->chunk(50)->each(function ($chunk) {
        foreach ($chunk as $product) {
            // Dispatch individual jobs
            SyncProductToPrestaShop::dispatch($product, $this->shop);
        }
    });
}
```

### Eager Loading:

```php
// Avoid N+1 queries
$products = Product::with([
    'category',
    'prices' => fn($q) => $q->where('price_group', 'detaliczna'),
    'stock' => fn($q) => $q->where('quantity', '>', 0),
    'syncStatus' => fn($q) => $q->where('shop_id', $shop->id)
])->get();
```

### Caching:

```php
// Cache mappings dla performance
$categoryMappings = Cache::remember(
    "shop_{$shop->id}_category_mappings",
    now()->addHours(24),
    fn() => ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', 'category')
        ->get()
        ->keyBy('ppm_value')
);
```

### Queue Priority:

```
High Priority Queue (prestashop_high):
- Manual sync requests (user-initiated)
- Error retries

Default Queue (prestashop_default):
- Scheduled sync
- Bulk operations
```

---

## 📊 MONITORING & METRICS

### Key Metrics:

| Metric | Tracking | Alert Threshold |
|--------|----------|-----------------|
| **Sync Success Rate** | sync_logs | < 95% |
| **Average Sync Time** | execution_time_ms | > 5000ms |
| **Failed Syncs** | sync_status = error | > 10 per hour |
| **Queue Depth** | Queue size | > 1000 jobs |
| **API Rate Limit** | Response headers | > 90% usage |

### Logging Points:

```php
// 1. Sync Started
Log::info('Product sync started', ['product_id' => $id, 'shop_id' => $shop]);

// 2. Data Transformed
Log::debug('Product data transformed', ['product_id' => $id, 'data' => $transformed]);

// 3. API Call Made
Log::info('PrestaShop API called', ['endpoint' => $url, 'method' => $method]);

// 4. Sync Completed
Log::info('Product sync completed', ['product_id' => $id, 'ps_id' => $psId, 'duration_ms' => $duration]);

// 5. Sync Failed
Log::error('Product sync failed', ['product_id' => $id, 'error' => $error, 'attempt' => $attempt]);
```

---

## 🎯 SUCCESS CRITERIA

### Synchronizacja Produktu:
- ✅ Product utworzony/zaktualizowany w PrestaShop
- ✅ Wszystkie dane zmapowane poprawnie
- ✅ Sync status = 'synced'
- ✅ Checksum zapisany
- ✅ Log success w sync_logs
- ✅ UI pokazuje sync badge ✅

### Synchronizacja Kategorii:
- ✅ Cała hierarchia zsynchronizowana
- ✅ Parent-child relationships zachowane
- ✅ Wszystkie mappings utworzone
- ✅ Brak błędów w sync_logs

---

**NOTES:**
- FAZA 1 focus: PPM → PrestaShop (jednokierunkowa)
- FAZA 2 będzie dodawać: PS → PPM, images sync, webhooks
- Wszystkie operacje async (queue jobs)
- Extensive logging dla debugowania
- Enterprise-grade error handling

**Autor:** Claude Code - architect agent
**Data:** 2025-10-01
**Wersja:** 1.0
