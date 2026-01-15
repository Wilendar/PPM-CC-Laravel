# RAPORT: Integracja Import PPM z Eksportem PrestaShop

**Data**: 2025-12-08
**Agent**: prestashop-api-expert
**Zadanie**: Zaprojektowanie integracji workflow publikacji produktów z panelu importu do PrestaShop

---

## 1. ANALIZA ISTNIEJĄCEJ ARCHITEKTURY

### 1.1 Obecne Job-y PrestaShop

**`SyncProductToPrestaShop`** (Individual Sync Job)
- **Purpose**: Synchronizacja pojedynczego produktu PPM → PrestaShop
- **Features**:
  - Unique job lock (prevents duplicates)
  - Exponential backoff retry (3 attempts: 30s, 1min, 5min)
  - Priority support (high/normal/low)
  - Integration z `ProductSyncStrategy`
  - Tracking przez `SyncJob` model
  - Pending media changes support (ETAP_07d)
- **Constructor**: `(Product $product, PrestaShopShop $shop, ?int $userId = null, array $pendingMediaChanges = [])`
- **Status**: ✅ Fully implemented z media + features sync

**`BulkSyncProducts`** (Batch Orchestrator Job)
- **Purpose**: Dispatches multiple `SyncProductToPrestaShop` jobs jako Laravel Batch
- **Features**:
  - Priority grouping (high → normal → low)
  - Batch tracking z callbacks
  - JobProgress integration dla UI
  - Sync mode support (full_sync, prices_only, stock_only, etc.)
  - Memory efficient (chunks products)
- **Constructor**: `(Collection $products, PrestaShopShop $shop, ?string $batchName = null, ?int $userId = null, string $syncMode = 'full_sync')`
- **Status**: ✅ Fully implemented z progress bar

**`PrestaShopImportService`** (Import z PrestaShop → PPM)
- **Purpose**: Reverse flow - import produktów z PrestaShop API do PPM
- **Features**:
  - Single product import
  - Category import z parent handling
  - Full category tree import
  - ProductShopData baseline creation
  - Feature import (ETAP_07e)
- **Status**: ✅ Używany w Import Modal (ETAP_07c)

### 1.2 Istniejące Services

**`ProductSyncStrategy`** (Core Sync Logic)
- **Purpose**: Implements ISyncStrategy dla product synchronization
- **Key Methods**:
  - `syncToPrestaShop()` - Main sync logic
  - `validateBeforeSync()` - Pre-sync validation
  - `needsSync()` - Checksum-based change detection
  - `calculateChecksum()` - Multi-dimensional data hash
  - `syncMediaIfEnabled()` - ETAP_07d media sync
  - `syncFeaturesIfEnabled()` - ETAP_07e features sync
- **Integration Points**:
  - `ProductTransformer` - PPM → PrestaShop data conversion
  - `CategoryMapper` - Category ID mapping
  - `PriceGroupMapper` - Price group mapping
  - `WarehouseMapper` - Stock calculation
  - `PrestaShopPriceExporter` - specific_prices sync
  - `CategoryAssociationService` - Direct DB category sync (workaround for API bug)
  - `MediaSyncService` - REPLACE ALL strategy dla images
  - `PrestaShopFeatureSyncService` - Feature sync

**`PrestaShop8Client`** (API Client)
- **Product Methods**: getProduct, createProduct, updateProduct, deleteProduct
- **Category Methods**: getCategories, getCategory, deleteCategory
- **Stock Methods**: getStock, updateStock
- **Price Methods**: getSpecificPrices, createSpecificPrice, updateSpecificPrice, deleteSpecificPrice
- **Image Methods** (ETAP_07d): uploadProductImage, deleteProductImage, setProductImageCover
- **Feature Methods** (ETAP_07e): getProductFeatures, createProductFeature, getProductFeatureValues, createProductFeatureValue
- **Combinations Methods** (ETAP_05c): getCombinations, createCombination, updateCombination
- **Format**: XML dla POST/PUT/PATCH (PrestaShop requirement)
- **Status**: ✅ Fully implemented

---

## 2. WORKFLOW PUBLIKACJI - FULL FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────┐
│                    IMPORT PANEL (User Interface)                     │
├─────────────────────────────────────────────────────────────────────┤
│  1. User fills product data (name, SKU, description, price, etc.)   │
│  2. User uploads images (optional)                                   │
│  3. User selects vehicle compatibilities                             │
│  4. User selects target PrestaShop shops (kafelki/tiles)            │
│  5. User clicks "Publikuj" button                                    │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│              PUBLISHSERVICE (New Orchestration Layer)                │
├─────────────────────────────────────────────────────────────────────┤
│  STEP 1: VALIDATION                                                  │
│  ├─ Validate required fields (SKU, name, price)                     │
│  ├─ Validate at least ONE shop selected                             │
│  ├─ Validate images exist (if uploaded)                             │
│  └─ Validate categories exist in target shops                       │
│                                                                       │
│  STEP 2: CREATE PRODUCT IN PPM                                       │
│  ├─ Create Product record                                            │
│  ├─ Create ProductPrice records (all price groups)                  │
│  ├─ Create Stock records (default warehouse)                        │
│  ├─ Attach Media records (if uploaded)                              │
│  ├─ Create VehicleCompatibility records                             │
│  └─ Create ProductFeature records (if provided)                     │
│                                                                       │
│  STEP 3: SHOP-SPECIFIC DATA PREPARATION                             │
│  ├─ For EACH selected shop:                                          │
│  │   ├─ Create ProductShopData record                               │
│  │   ├─ Copy default data (name, description, price)                │
│  │   ├─ Build category_mappings (PPM → PrestaShop IDs)              │
│  │   ├─ Filter vehicle compatibilities (shop-specific bans)         │
│  │   ├─ Prepare pending_media_changes (all media → 'sync')          │
│  │   └─ Set sync_status = 'pending'                                 │
│  └─ END FOR EACH                                                     │
│                                                                       │
│  STEP 4: DISPATCH EXPORT JOBS                                        │
│  ├─ For EACH selected shop:                                          │
│  │   ├─ Dispatch SyncProductToPrestaShop(                           │
│  │   │     $product,                                                 │
│  │   │     $shop,                                                    │
│  │   │     $userId = auth()->id(),                                  │
│  │   │     $pendingMediaChanges = [                                 │
│  │   │         'mediaId:shopId' => 'sync',                          │
│  │   │         ...                                                   │
│  │   │     ]                                                         │
│  │   │   )                                                           │
│  │   └─ Update ProductShopData.sync_status = 'syncing'              │
│  └─ END FOR EACH                                                     │
│                                                                       │
│  STEP 5: MOVE TO HISTORY                                             │
│  └─ Update import_status = 'published' in import tracking           │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│           QUEUE SYSTEM (Laravel Queue Worker)                        │
├─────────────────────────────────────────────────────────────────────┤
│  PER SHOP - PARALLEL EXECUTION:                                      │
│                                                                       │
│  SyncProductToPrestaShop JOB:                                        │
│  ├─ Create SyncJob record (tracking)                                │
│  ├─ Call ProductSyncStrategy::syncToPrestaShop()                    │
│  │   ├─ Validate product data                                        │
│  │   ├─ Calculate checksum (detect changes)                         │
│  │   ├─ Transform data via ProductTransformer                       │
│  │   ├─ Determine operation (CREATE vs UPDATE)                      │
│  │   ├─ Call PrestaShop8Client::createProduct() OR updateProduct()  │
│  │   ├─ Extract external_id (PrestaShop product ID)                 │
│  │   ├─ Update ProductShopData:                                     │
│  │   │   ├─ prestashop_product_id = external_id                     │
│  │   │   ├─ sync_status = 'synced'                                  │
│  │   │   ├─ checksum = new_checksum                                 │
│  │   │   └─ last_success_sync_at = now()                            │
│  │   ├─ Sync categories (CategoryAssociationService)                │
│  │   ├─ Sync prices (PrestaShopPriceExporter)                       │
│  │   ├─ Sync media (MediaSyncService - REPLACE ALL)                 │
│  │   │   ├─ Delete all existing images                              │
│  │   │   ├─ Upload selected images                                  │
│  │   │   └─ Set cover image (is_primary)                            │
│  │   └─ Sync features (PrestaShopFeatureSyncService)                │
│  ├─ Update SyncJob (success/failure)                                │
│  └─ Create SyncLog entry                                             │
│                                                                       │
│  ON SUCCESS:                                                          │
│  └─ ProductShopData.sync_status = 'synced'                          │
│                                                                       │
│  ON FAILURE:                                                          │
│  ├─ ProductShopData.sync_status = 'error'                           │
│  ├─ ProductShopData.error_message = exception message               │
│  └─ Retry job (exponential backoff: 30s, 1min, 5min)                │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. ODPOWIEDZI NA PYTANIA

### 3.1 INTEGRACJA Z ISTNIEJĄCYMI JOB-AMI

**Q: Jakie istniejące Job-y wykorzystać?**

**A: Wykorzystaj `SyncProductToPrestaShop` - GOTOWY DO UŻYCIA!**

✅ **ZALETY:**
- Już implementuje WSZYSTKO czego potrzebujesz:
  - Unique job lock (prevents duplicate syncs)
  - Retry logic z exponential backoff
  - Integration z ProductSyncStrategy (full sync pipeline)
  - Media sync support (`$pendingMediaChanges` parameter)
  - Features sync support (ETAP_07e)
  - User tracking (`$userId` parameter)
  - SyncJob + SyncLog tracking
  - Priority support

❌ **NIE TWÓRZ** nowego Job-a dla "first time export" - `SyncProductToPrestaShop` już obsługuje CREATE vs UPDATE automatically!

**Q: Czy potrzebny nowy Job dla "first time export"?**

**A: NIE! `ProductSyncStrategy::syncToPrestaShop()` automatycznie wykrywa:**
```php
$isUpdate = !empty($syncStatus->prestashop_product_id);

if ($isUpdate) {
    $response = $client->updateProduct($syncStatus->prestashop_product_id, $productData);
    $operation = 'update';
} else {
    $response = $client->createProduct($productData);
    $operation = 'create';
}
```

**Q: Jak obsłużyć batch export wielu produktów naraz?**

**A: Użyj `BulkSyncProducts` TYLKO jeśli:**
- Import wielu produktów jednocześnie (bulk CSV import)
- Potrzebujesz progress bar w UI (JobProgress integration)
- Chcesz groupować po priority (high → normal → low)

**DLA POJEDYNCZEGO PRODUKTU Z IMPORTU:**
- Dispatch `SyncProductToPrestaShop` bezpośrednio dla każdego shop
- Nie używaj `BulkSyncProducts` wrapper (overkill dla 1 produktu × N shops)

---

### 3.2 SHOP-SPECIFIC DATA

**Q: Jak przekazać shop_ids do Job-a?**

**A: `SyncProductToPrestaShop` przyjmuje `PrestaShopShop $shop` w constructor:**
```php
// W PublishService - dispatch ODDZIELNY job dla KAŻDEGO shop
foreach ($selectedShops as $shop) {
    SyncProductToPrestaShop::dispatch(
        $product,
        $shop,
        auth()->id(), // User ID
        $pendingMediaChanges // Media checkbox states
    );
}
```

**Q: Jak obsłużyć różne ceny/opisy per shop?**

**A: ProductShopData + ProductSyncStrategy już to robi:**

1. **Dane domyślne** (z Product model):
   - Używane gdy ProductShopData nie ma override
   - ProductTransformer czyta z `Product` fields

2. **Dane per-shop** (z ProductShopData):
   ```php
   // ProductTransformer sprawdza shop-specific override
   $shopData = $model->dataForShop($shop->id)->first();
   if ($shopData && $shopData->name) {
       $name = $shopData->name; // Override
   } else {
       $name = $model->name; // Default
   }
   ```

3. **Ceny per-shop**:
   - Stored in `shop_mappings` table:
     ```sql
     shop_mappings (
         shop_id, mapping_type = 'price_group',
         ppm_value = 'detaliczna',
         prestashop_id = 3 -- PrestaShop customer group ID
     )
     ```
   - `PriceGroupMapper` mapuje PPM price groups → PrestaShop customer groups
   - `PrestaShopPriceExporter` tworzy `specific_prices` entries

**Q: Jak obsłużyć filtrowanie dopasowań per shop?**

**A: Shop-specific vehicle compatibility bans:**
```php
// VehicleCompatibility ma pole `banned_shops` (JSON array)
$banned_shops = [1, 5, 7]; // Shop IDs where this compatibility is hidden

// ProductTransformer filters compatibilities:
$compatibilities = $model->compatibilities()
    ->whereDoesntHave('bannedShops', function($q) use ($shop) {
        $q->where('shop_id', $shop->id);
    })
    ->get();
```

**IMPLEMENTATION W PUBLISHSERVICE:**
```php
protected function filterCompatibilitiesForShop(Product $product, PrestaShopShop $shop): Collection
{
    return $product->compatibilities->filter(function($compat) use ($shop) {
        $bannedShops = $compat->banned_shops ?? [];
        return !in_array($shop->id, $bannedShops);
    });
}
```

---

### 3.3 WALIDACJA PRZED EKSPORTEM

**Q: Co walidować przed utworzeniem Job-a?**

**A: MANDATORY VALIDATION w PublishService:**

```php
protected function validateForPublication(array $productData, array $shopIds): array
{
    $errors = [];

    // 1. Required fields
    if (empty($productData['sku'])) {
        $errors[] = 'SKU is required';
    }

    if (empty($productData['name'])) {
        $errors[] = 'Product name is required';
    }

    if (empty($productData['price'])) {
        $errors[] = 'Product price is required';
    }

    // 2. At least ONE shop selected
    if (empty($shopIds)) {
        $errors[] = 'Please select at least one PrestaShop shop';
    }

    // 3. Shops exist and are active
    foreach ($shopIds as $shopId) {
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            $errors[] = "Shop ID {$shopId} does not exist";
        } elseif (!$shop->is_active) {
            $errors[] = "Shop '{$shop->name}' is not active";
        }
    }

    // 4. Duplicate SKU check
    $existingProduct = Product::where('sku', $productData['sku'])->first();
    if ($existingProduct) {
        $errors[] = "Product with SKU '{$productData['sku']}' already exists (ID: {$existingProduct->id})";
    }

    // 5. Images exist (if provided)
    if (!empty($productData['image_paths'])) {
        foreach ($productData['image_paths'] as $imagePath) {
            if (!file_exists($imagePath)) {
                $errors[] = "Image not found: {$imagePath}";
            }
        }
    }

    return $errors;
}
```

**Q: Jak obsłużyć produkty bez kategorii w danym sklepie?**

**A: ETAP_07b Category Mapping System:**

**OPTION A: Assign to default category (RECOMMENDED)**
```php
protected function ensureCategoryMapping(Product $product, PrestaShopShop $shop): void
{
    $shopData = ProductShopData::firstOrCreate(
        ['product_id' => $product->id, 'shop_id' => $shop->id]
    );

    // Check if product has category mappings for this shop
    if (!$shopData->hasCategoryMappings()) {
        // Get shop's default category from shop_mappings
        $defaultMapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('is_default', true)
            ->first();

        if ($defaultMapping) {
            // Assign to default category
            $shopData->category_mappings = [
                'ui' => ['selected' => [$defaultMapping->ppm_value], 'primary' => $defaultMapping->ppm_value],
                'mappings' => [$defaultMapping->ppm_value => $defaultMapping->prestashop_id],
                'metadata' => ['auto_assigned' => true, 'reason' => 'no_category']
            ];
            $shopData->save();
        } else {
            // FALLBACK: Use PrestaShop's root category (ID 2)
            $shopData->category_mappings = [
                'ui' => ['selected' => [2], 'primary' => 2],
                'mappings' => [2 => 2],
                'metadata' => ['auto_assigned' => true, 'reason' => 'no_default_mapping']
            ];
            $shopData->save();
        }
    }
}
```

**OPTION B: Fail validation (STRICT)**
```php
protected function validateCategoryMappings(Product $product, array $shopIds): array
{
    $errors = [];

    foreach ($shopIds as $shopId) {
        $shopData = ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$shopData || !$shopData->hasCategoryMappings()) {
            $shop = PrestaShopShop::find($shopId);
            $errors[] = "Product must have at least one category assigned for shop '{$shop->name}'";
        }
    }

    return $errors;
}
```

**Q: Jak obsłużyć brak obrazków?**

**A: OPTIONAL VALIDATION (images are not mandatory):**

```php
protected function validateImages(Product $product): array
{
    $warnings = [];

    $mediaCount = Media::where('mediable_type', Product::class)
        ->where('mediable_id', $product->id)
        ->active()
        ->count();

    if ($mediaCount === 0) {
        $warnings[] = 'Product has no images - PrestaShop will use placeholder';
    }

    // Check if at least one image is marked as primary
    $hasPrimary = Media::where('mediable_type', Product::class)
        ->where('mediable_id', $product->id)
        ->where('is_primary', true)
        ->active()
        ->exists();

    if ($mediaCount > 0 && !$hasPrimary) {
        $warnings[] = 'No primary image selected - first image will be used as cover';
    }

    return $warnings;
}
```

---

### 3.4 ERROR HANDLING

**Q: Co jeśli eksport na jeden sklep się powiedzie, a na drugi nie?**

**A: INDEPENDENT SHOP SYNC (by design):**

✅ **ZALETY oddzielnych Job-ów per shop:**
- Shop A fails → Shop B still succeeds
- Shop A retries independently (exponential backoff)
- User sees per-shop sync status in ProductShopData

**TRACKING:**
```php
// ProductShopData per shop tracks status independently
ProductShopData::where('product_id', $product->id)->get();
// Result:
// [
//   {shop_id: 1, sync_status: 'synced', last_success_sync_at: '2025-12-08 10:00:00'},
//   {shop_id: 2, sync_status: 'error', error_message: 'API timeout', retry_count: 2}
// ]
```

**Q: Jak informować użytkownika o statusie?**

**A: MULTI-LEVEL FEEDBACK:**

**1. Immediate feedback (PublishService response):**
```php
public function publishProduct(Product $product, array $shopIds): array
{
    $results = [];

    foreach ($shopIds as $shopId) {
        $shop = PrestaShopShop::find($shopId);

        try {
            // Dispatch job
            SyncProductToPrestaShop::dispatch($product, $shop, auth()->id(), $pendingMediaChanges);

            $results[$shopId] = [
                'status' => 'queued',
                'shop_name' => $shop->name,
                'message' => 'Export job queued successfully'
            ];
        } catch (\Exception $e) {
            $results[$shopId] = [
                'status' => 'dispatch_failed',
                'shop_name' => $shop->name,
                'message' => 'Failed to queue export: ' . $e->getMessage()
            ];
        }
    }

    return $results;
}
```

**2. Real-time status (Livewire wire:poll):**
```php
// ProductForm component
public function getSyncStatusForShops()
{
    return ProductShopData::where('product_id', $this->product->id)
        ->with('shop')
        ->get()
        ->map(fn($shopData) => [
            'shop_name' => $shopData->shop->name,
            'status' => $shopData->sync_status,
            'last_sync' => $shopData->last_success_sync_at?->diffForHumans(),
            'error' => $shopData->error_message,
            'progress' => $this->calculateProgress($shopData)
        ]);
}
```

**3. Notification system (FUTURE - opcjonalne):**
```php
// W SyncProductToPrestaShop::handle() po success
if ($this->userId) {
    $user = User::find($this->userId);
    $user->notify(new ProductSyncSuccessNotification($this->product, $this->shop));
}

// Po permanent failure (SyncProductToPrestaShop::failed())
if ($this->userId) {
    $user = User::find($this->userId);
    $user->notify(new ProductSyncFailedNotification($this->product, $this->shop, $exception));
}
```

**Q: Retry strategy?**

**A: ALREADY IMPLEMENTED w SyncProductToPrestaShop:**

```php
// Job configuration
public int $tries = 3; // Total 3 attempts
public function backoff(): array
{
    return [30, 60, 300]; // 30s, 1min, 5min
}
public function retryUntil(): Carbon
{
    return now()->addHours(24); // Max 24h retry window
}

// Automatic retry by Laravel Queue
// Attempt 1 fails → wait 30s → Attempt 2
// Attempt 2 fails → wait 60s → Attempt 3
// Attempt 3 fails → call failed() method → ProductShopData.sync_status = 'error'
```

**MANUAL RETRY (by user):**
```php
// ProductForm component
public function retrySyncForShop($shopId)
{
    $shop = PrestaShopShop::find($shopId);

    // Reset error state
    $shopData = ProductShopData::where('product_id', $this->product->id)
        ->where('shop_id', $shopId)
        ->first();

    $shopData->update([
        'sync_status' => 'pending',
        'error_message' => null,
        'retry_count' => 0
    ]);

    // Re-dispatch job
    SyncProductToPrestaShop::dispatch(
        $this->product,
        $shop,
        auth()->id(),
        session()->get("pending_media_changes.{$this->product->id}", [])
    );

    session()->flash('success', "Retry queued for {$shop->name}");
}
```

---

### 3.5 ZDJĘCIA

**Q: Jak przekazać zdjęcia do PrestaShop?**

**A: ETAP_07d MEDIA SYNC SYSTEM - ALREADY IMPLEMENTED:**

**1. Media przechowywane w PPM (Media model):**
```php
Media::where('mediable_type', Product::class)
    ->where('mediable_id', $product->id)
    ->active()
    ->get();
```

**2. Pending media changes z checkboxes (GalleryTab):**
```php
// Format: ['mediaId:shopId' => 'sync'|'unsync']
$pendingMediaChanges = [
    '123:1' => 'sync',   // Media ID 123 → Shop ID 1 (checked)
    '124:1' => 'sync',   // Media ID 124 → Shop ID 1 (checked)
    '123:2' => 'unsync', // Media ID 123 → Shop ID 2 (unchecked)
];

// Passed to SyncProductToPrestaShop constructor
SyncProductToPrestaShop::dispatch($product, $shop, $userId, $pendingMediaChanges);
```

**3. ProductSyncStrategy::syncMediaIfEnabled():**
```php
// REPLACE ALL strategy (ETAP_07d)
// 1. Delete ALL existing images from PrestaShop
// 2. Upload ONLY selected images (where 'sync' action)
// 3. Set cover image based on is_primary flag

$syncService = app(MediaSyncService::class);
$result = $syncService->replaceAllImages($product, $shop, $selectedMedia);
```

**4. MediaSyncService implementation:**
```php
public function replaceAllImages(Product $product, PrestaShopShop $shop, Collection $selectedMedia): array
{
    $client = PrestaShopClientFactory::create($shop);
    $psProductId = ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->first()?->prestashop_product_id;

    // Step 1: Delete all existing images
    $deleted = $client->deleteAllProductImages($psProductId);

    // Step 2: Upload selected images
    $uploaded = 0;
    $errors = [];
    $firstImageId = null;

    foreach ($selectedMedia as $media) {
        try {
            $imagePath = Storage::disk('public')->path($media->file_path);

            $response = $client->uploadProductImage($psProductId, $imagePath, $media->file_name);
            $psImageId = $response['id'] ?? null;

            if ($psImageId) {
                // Update Media model with PrestaShop mapping
                $media->setPrestaShopMapping($shop->id, [
                    'ps_image_id' => $psImageId,
                    'synced_at' => now()->toIso8601String()
                ]);

                $uploaded++;
                $firstImageId = $firstImageId ?? $psImageId;
            }
        } catch (\Exception $e) {
            $errors[] = "Failed to upload {$media->file_name}: " . $e->getMessage();
        }
    }

    // Step 3: Set cover image (primary)
    $coverSet = false;
    $primaryMedia = $selectedMedia->firstWhere('is_primary', true);

    if ($primaryMedia) {
        $mapping = $primaryMedia->prestashop_mapping["store_{$shop->id}"] ?? [];
        $psImageId = $mapping['ps_image_id'] ?? null;

        if ($psImageId) {
            $coverSet = $client->setProductImageCover($psProductId, $psImageId);
        }
    } elseif ($firstImageId) {
        // Fallback: set first uploaded image as cover
        $coverSet = $client->setProductImageCover($psProductId, $firstImageId);
    }

    return [
        'deleted' => $deleted,
        'uploaded' => $uploaded,
        'errors' => $errors,
        'cover_set' => $coverSet
    ];
}
```

**Q: Kolejność zdjęć (główne jako cover)?**

**A: is_primary flag + setProductImageCover():**
```php
// Media model
$primaryMedia = Media::where('mediable_type', Product::class)
    ->where('mediable_id', $product->id)
    ->where('is_primary', true)
    ->active()
    ->first();

// PrestaShop8Client
$client->setProductImageCover($psProductId, $psImageId);
// Uses PATCH /products/{id} with id_default_image field
```

**Q: Zdjęcia wariantów?**

**A: ETAP_05c Variants System (FUTURE):**
```php
// VariantImage model stores variant-specific images
VariantImage::where('variant_id', $variantId)->get();

// PrestaShop8Client::setCombinationImages()
$client->setCombinationImages($psCombinationId, [$psImageId1, $psImageId2]);
```

---

## 4. PROPOZYCJA API DLA PUBLISHSERVICE

### 4.1 Service Structure

```php
<?php

namespace App\Services\Import;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\Media;
use App\Models\VehicleCompatibility;
use App\Models\ProductFeature;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Product Publish Service
 *
 * Orchestrates product creation in PPM + export to selected PrestaShop shops
 * Used by Import Panel after user fills product data and clicks "Publikuj"
 *
 * Workflow:
 * 1. Validate product data + selected shops
 * 2. Create Product + related records in PPM (transaction)
 * 3. Create ProductShopData for each selected shop
 * 4. Dispatch SyncProductToPrestaShop jobs (parallel)
 * 5. Return status report
 *
 * @package App\Services\Import
 */
class ProductPublishService
{
    /**
     * Publish product to PPM + export to selected PrestaShop shops
     *
     * @param array $productData Product data from import panel
     * @param array $shopIds Selected PrestaShop shop IDs
     * @param array $options Additional options
     * @return array Status report with product ID + shop sync results
     * @throws \InvalidArgumentException On validation errors
     * @throws \Exception On database errors
     */
    public function publishProduct(array $productData, array $shopIds, array $options = []): array
    {
        // STEP 1: Validation
        $validationErrors = $this->validate($productData, $shopIds);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException(implode(', ', $validationErrors));
        }

        // STEP 2: Create product in PPM (transaction)
        $product = $this->createProductInPPM($productData);

        // STEP 3: Shop-specific data preparation
        $selectedShops = PrestaShopShop::whereIn('id', $shopIds)
            ->where('is_active', true)
            ->get();

        foreach ($selectedShops as $shop) {
            $this->prepareShopData($product, $shop, $productData);
        }

        // STEP 4: Dispatch export jobs
        $syncResults = $this->dispatchExportJobs($product, $selectedShops, $options);

        // STEP 5: Move to history
        if (isset($options['import_record_id'])) {
            $this->updateImportHistory($options['import_record_id'], $product);
        }

        return [
            'success' => true,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'shops_dispatched' => count($syncResults['dispatched']),
            'shops_failed' => count($syncResults['failed']),
            'sync_results' => $syncResults
        ];
    }

    /**
     * Validate product data before publication
     */
    protected function validate(array $productData, array $shopIds): array
    {
        $errors = [];

        // Required fields
        if (empty($productData['sku'])) {
            $errors[] = 'SKU is required';
        }

        if (empty($productData['name'])) {
            $errors[] = 'Product name is required';
        }

        if (!isset($productData['price']) || $productData['price'] <= 0) {
            $errors[] = 'Valid product price is required';
        }

        // At least one shop
        if (empty($shopIds)) {
            $errors[] = 'Please select at least one PrestaShop shop';
        }

        // Shops exist and are active
        $activeShops = PrestaShopShop::whereIn('id', $shopIds)
            ->where('is_active', true)
            ->count();

        if ($activeShops !== count($shopIds)) {
            $errors[] = 'Some selected shops are inactive or do not exist';
        }

        // Duplicate SKU check
        if (!empty($productData['sku'])) {
            $existingProduct = Product::where('sku', $productData['sku'])->first();
            if ($existingProduct) {
                $errors[] = "Product with SKU '{$productData['sku']}' already exists (ID: {$existingProduct->id})";
            }
        }

        // Images exist (if provided)
        if (!empty($productData['image_paths'])) {
            foreach ($productData['image_paths'] as $imagePath) {
                if (!Storage::disk('public')->exists($imagePath)) {
                    $errors[] = "Image not found: {$imagePath}";
                }
            }
        }

        return $errors;
    }

    /**
     * Create product in PPM database
     */
    protected function createProductInPPM(array $productData): Product
    {
        return DB::transaction(function() use ($productData) {
            // 1. Create Product record
            $product = Product::create([
                'sku' => $productData['sku'],
                'name' => $productData['name'],
                'slug' => \Str::slug($productData['name']),
                'short_description' => $productData['short_description'] ?? null,
                'long_description' => $productData['long_description'] ?? null,
                'product_type_id' => $productData['product_type_id'] ?? null,
                'manufacturer' => $productData['manufacturer'] ?? null,
                'ean' => $productData['ean'] ?? null,
                'weight' => $productData['weight'] ?? 0,
                'height' => $productData['height'] ?? 0,
                'width' => $productData['width'] ?? 0,
                'length' => $productData['length'] ?? 0,
                'tax_rate' => $productData['tax_rate'] ?? 23.0,
                'is_active' => true,
            ]);

            // 2. Create ProductPrice records (all price groups)
            if (isset($productData['price'])) {
                $priceGroups = \App\Models\PriceGroup::all();

                foreach ($priceGroups as $priceGroup) {
                    // Use provided price for all groups initially
                    // (can be overridden later per group)
                    $priceNet = $productData['price'];
                    $priceGross = $priceNet * (1 + ($product->tax_rate / 100));

                    ProductPrice::create([
                        'product_id' => $product->id,
                        'price_group_id' => $priceGroup->id,
                        'price_net' => $priceNet,
                        'price_gross' => $priceGross,
                        'currency' => 'PLN'
                    ]);
                }
            }

            // 3. Create Stock records (default warehouse)
            if (isset($productData['stock_quantity'])) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_code' => 'MPPTRADE', // Default warehouse
                    'quantity' => $productData['stock_quantity'],
                    'reserved' => 0,
                    'available' => $productData['stock_quantity']
                ]);
            }

            // 4. Attach Media records (if uploaded)
            if (!empty($productData['media_ids'])) {
                foreach ($productData['media_ids'] as $index => $mediaId) {
                    $media = Media::find($mediaId);
                    if ($media) {
                        $media->update([
                            'mediable_type' => Product::class,
                            'mediable_id' => $product->id,
                            'sort_order' => $index,
                            'is_primary' => $index === 0, // First image as primary
                            'is_active' => true
                        ]);
                    }
                }
            }

            // 5. Create VehicleCompatibility records
            if (!empty($productData['vehicle_compatibility_ids'])) {
                foreach ($productData['vehicle_compatibility_ids'] as $compatId) {
                    VehicleCompatibility::create([
                        'product_id' => $product->id,
                        'vehicle_model_id' => $compatId,
                        'banned_shops' => [] // No bans initially
                    ]);
                }
            }

            // 6. Create ProductFeature records (if provided)
            if (!empty($productData['features'])) {
                foreach ($productData['features'] as $featureData) {
                    ProductFeature::create([
                        'product_id' => $product->id,
                        'feature_type_id' => $featureData['feature_type_id'],
                        'value' => $featureData['value']
                    ]);
                }
            }

            // 7. Assign categories (default)
            if (!empty($productData['category_ids'])) {
                $product->categories()->attach($productData['category_ids']);
            }

            Log::info('Product created in PPM', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name
            ]);

            return $product;
        });
    }

    /**
     * Prepare shop-specific data
     */
    protected function prepareShopData(Product $product, PrestaShopShop $shop, array $productData): void
    {
        // Create ProductShopData for this shop
        $shopData = ProductShopData::create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,

            // Copy default data (can be overridden later)
            'sku' => $product->sku,
            'name' => $product->name,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,

            // Status
            'is_active' => true,
            'is_published' => false, // Will be true after successful sync
            'sync_status' => ProductShopData::STATUS_PENDING,
            'sync_direction' => ProductShopData::DIRECTION_PPM_TO_PS,

            // Category mappings (build from product categories + shop mappings)
            'category_mappings' => $this->buildCategoryMappings($product, $shop)
        ]);

        Log::debug('ProductShopData prepared', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'category_mappings' => $shopData->category_mappings
        ]);
    }

    /**
     * Build category_mappings for shop
     */
    protected function buildCategoryMappings(Product $product, PrestaShopShop $shop): array
    {
        $ppmCategoryIds = $product->categories->pluck('id')->toArray();

        if (empty($ppmCategoryIds)) {
            // No categories - use shop default
            $defaultMapping = ShopMapping::where('shop_id', $shop->id)
                ->where('mapping_type', 'category')
                ->where('is_default', true)
                ->first();

            if ($defaultMapping) {
                return [
                    'ui' => ['selected' => [$defaultMapping->ppm_value], 'primary' => $defaultMapping->ppm_value],
                    'mappings' => [$defaultMapping->ppm_value => $defaultMapping->prestashop_id],
                    'metadata' => ['auto_assigned' => true, 'reason' => 'no_category']
                ];
            }

            // Fallback: PrestaShop root category
            return [
                'ui' => ['selected' => [2], 'primary' => 2],
                'mappings' => [2 => 2],
                'metadata' => ['auto_assigned' => true, 'reason' => 'no_mapping']
            ];
        }

        // Map PPM categories to PrestaShop categories
        $mappings = [];
        $prestashopIds = [];

        foreach ($ppmCategoryIds as $ppmCategoryId) {
            $mapping = ShopMapping::where('shop_id', $shop->id)
                ->where('mapping_type', 'category')
                ->where('ppm_value', $ppmCategoryId)
                ->first();

            if ($mapping) {
                $mappings[$ppmCategoryId] = $mapping->prestashop_id;
                $prestashopIds[] = $mapping->prestashop_id;
            }
        }

        if (empty($prestashopIds)) {
            // No mappings found - fallback to default
            return $this->buildCategoryMappings(Product::make(), $shop); // Recursion
        }

        return [
            'ui' => ['selected' => $prestashopIds, 'primary' => $prestashopIds[0]],
            'mappings' => $mappings,
            'metadata' => ['auto_assigned' => false]
        ];
    }

    /**
     * Dispatch export jobs to selected shops
     */
    protected function dispatchExportJobs(Product $product, Collection $shops, array $options): array
    {
        $results = [
            'dispatched' => [],
            'failed' => []
        ];

        // Prepare pending media changes (all media → 'sync')
        $pendingMediaChanges = $this->buildPendingMediaChanges($product, $shops);

        foreach ($shops as $shop) {
            try {
                // Update ProductShopData status
                ProductShopData::where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->update(['sync_status' => ProductShopData::STATUS_SYNCING]);

                // Dispatch job
                SyncProductToPrestaShop::dispatch(
                    $product,
                    $shop,
                    auth()->id(), // User ID
                    $pendingMediaChanges[$shop->id] ?? [] // Media changes for this shop
                );

                $results['dispatched'][] = [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'status' => 'queued',
                    'message' => 'Export job queued successfully'
                ];

                Log::info('Export job dispatched', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'user_id' => auth()->id()
                ]);

            } catch (\Exception $e) {
                // Mark as failed in ProductShopData
                ProductShopData::where('product_id', $product->id)
                    ->where('shop_id', $shop->id)
                    ->update([
                        'sync_status' => ProductShopData::STATUS_ERROR,
                        'error_message' => 'Failed to dispatch job: ' . $e->getMessage()
                    ]);

                $results['failed'][] = [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'status' => 'dispatch_failed',
                    'message' => $e->getMessage()
                ];

                Log::error('Failed to dispatch export job', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Build pending media changes for all shops
     */
    protected function buildPendingMediaChanges(Product $product, Collection $shops): array
    {
        $allChanges = [];

        $media = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->active()
            ->get();

        foreach ($shops as $shop) {
            $shopChanges = [];

            foreach ($media as $mediaItem) {
                // Mark all media as 'sync' for this shop
                $shopChanges["{$mediaItem->id}:{$shop->id}"] = 'sync';
            }

            $allChanges[$shop->id] = $shopChanges;
        }

        return $allChanges;
    }

    /**
     * Update import history record
     */
    protected function updateImportHistory(int $importRecordId, Product $product): void
    {
        // TODO: Update import tracking table (if exists)
        // ImportRecord::where('id', $importRecordId)
        //     ->update([
        //         'status' => 'published',
        //         'product_id' => $product->id,
        //         'published_at' => now()
        //     ]);
    }
}
```

### 4.2 Usage Example

```php
// W Import Panel Livewire Component:

use App\Services\Import\ProductPublishService;

class ImportPanel extends Component
{
    public array $productData = [];
    public array $selectedShopIds = [];

    public function __construct(
        protected ProductPublishService $publishService
    ) {}

    public function publish()
    {
        try {
            $result = $this->publishService->publishProduct(
                $this->productData,
                $this->selectedShopIds,
                ['import_record_id' => $this->importRecordId ?? null]
            );

            session()->flash('success',
                "Product '{$result['name']}' published successfully! " .
                "Export queued for {$result['shops_dispatched']} shops."
            );

            // Redirect to product edit page
            return redirect()->route('products.edit', $result['product_id']);

        } catch (\InvalidArgumentException $e) {
            session()->flash('error', 'Validation error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Product publication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Failed to publish product: ' . $e->getMessage());
        }
    }
}
```

---

## 5. PRZYKŁADY KODU DISPATCH

### 5.1 Dispatch Single Product to Multiple Shops

```php
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Models\Product;
use App\Models\PrestaShopShop;

// Get product
$product = Product::find(123);

// Get selected shops
$selectedShops = PrestaShopShop::whereIn('id', [1, 2, 5])
    ->where('is_active', true)
    ->get();

// Prepare pending media changes (all media marked as 'sync')
$pendingMediaChanges = [];
$media = Media::where('mediable_type', Product::class)
    ->where('mediable_id', $product->id)
    ->active()
    ->get();

foreach ($selectedShops as $shop) {
    foreach ($media as $mediaItem) {
        $pendingMediaChanges["{$mediaItem->id}:{$shop->id}"] = 'sync';
    }
}

// Dispatch job for EACH shop
foreach ($selectedShops as $shop) {
    SyncProductToPrestaShop::dispatch(
        $product,
        $shop,
        auth()->id(), // User who triggered
        $pendingMediaChanges // All media changes (will be filtered per shop in job)
    );

    Log::info('Export job dispatched', [
        'product_id' => $product->id,
        'sku' => $product->sku,
        'shop_id' => $shop->id,
        'shop_name' => $shop->name,
        'user_id' => auth()->id()
    ]);
}

// Update ProductShopData status
ProductShopData::whereIn('shop_id', $selectedShops->pluck('id'))
    ->where('product_id', $product->id)
    ->update(['sync_status' => ProductShopData::STATUS_SYNCING]);
```

### 5.2 Dispatch with Custom Options

```php
// High priority sync (for urgent products)
$job = new SyncProductToPrestaShop($product, $shop, auth()->id(), $pendingMediaChanges);
$job->onQueue('prestashop_high'); // Use high priority queue
$job->dispatch();

// Delayed dispatch (schedule for later)
SyncProductToPrestaShop::dispatch($product, $shop, auth()->id(), $pendingMediaChanges)
    ->delay(now()->addMinutes(5));

// Sync to specific connection
SyncProductToPrestaShop::dispatch($product, $shop, auth()->id(), $pendingMediaChanges)
    ->onConnection('redis');
```

### 5.3 Dispatch with Job Chaining

```php
use Illuminate\Support\Facades\Bus;

// Sync product → then sync images → then sync features
Bus::chain([
    new SyncProductToPrestaShop($product, $shop, auth()->id(), $pendingMediaChanges),
    new SyncProductImages($product, $shop),
    new SyncProductFeatures($product, $shop)
])->dispatch();
```

### 5.4 Dispatch with Batch (Multiple Products)

```php
use App\Jobs\PrestaShop\BulkSyncProducts;
use Illuminate\Support\Collection;

// Get multiple products from import
$products = Product::whereIn('id', [100, 101, 102, 103, 104])->get();

// Get target shop
$shop = PrestaShopShop::find(1);

// Dispatch bulk sync with progress tracking
BulkSyncProducts::dispatch(
    $products,
    $shop,
    'Import Batch #123', // Batch name
    auth()->id(),
    'full_sync' // Sync mode
);
```

---

## 6. ERROR HANDLING STRATEGY

### 6.1 Multi-Level Error Handling

```
┌─────────────────────────────────────────────────────────────────────┐
│                    LEVEL 1: PRE-DISPATCH VALIDATION                  │
├─────────────────────────────────────────────────────────────────────┤
│  PublishService::validate()                                          │
│  ├─ Catch errors BEFORE creating Product/Job                        │
│  ├─ Return user-friendly error messages                             │
│  └─ Prevent invalid data from entering system                       │
│                                                                       │
│  Examples:                                                            │
│  - "SKU is required"                                                 │
│  - "Product with SKU 'ABC123' already exists"                       │
│  - "Shop 'B2B Test' is not active"                                  │
│  - "Image not found: storage/temp/image.jpg"                        │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                   LEVEL 2: JOB DISPATCH ERRORS                       │
├─────────────────────────────────────────────────────────────────────┤
│  PublishService::dispatchExportJobs()                                │
│  ├─ Try-catch around dispatch()                                     │
│  ├─ Mark shop as 'dispatch_failed' if error                         │
│  └─ Continue with other shops (non-blocking)                        │
│                                                                       │
│  Examples:                                                            │
│  - "Queue connection failed"                                         │
│  - "Job serialization error"                                        │
│  - "Redis timeout"                                                   │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                  LEVEL 3: JOB EXECUTION ERRORS                       │
├─────────────────────────────────────────────────────────────────────┤
│  SyncProductToPrestaShop::handle()                                   │
│  ├─ Try-catch around ProductSyncStrategy::syncToPrestaShop()        │
│  ├─ Update SyncJob status to 'failed'                               │
│  ├─ Re-throw exception → Laravel retry mechanism                    │
│  └─ Retry 3x with backoff (30s, 1min, 5min)                         │
│                                                                       │
│  Examples:                                                            │
│  - "PrestaShop API timeout"                                          │
│  - "Product validation failed: name is required"                    │
│  - "Category mapping not found"                                     │
│  - "Image upload failed: file too large"                            │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                 LEVEL 4: PERMANENT FAILURE HANDLING                  │
├─────────────────────────────────────────────────────────────────────┤
│  SyncProductToPrestaShop::failed()                                   │
│  ├─ Called after ALL retry attempts exhausted                       │
│  ├─ Update ProductShopData.sync_status = 'error'                    │
│  ├─ Store full error message + retry count                          │
│  ├─ Create SyncLog entry                                             │
│  └─ Notify user (optional)                                           │
│                                                                       │
│  User actions:                                                        │
│  - View error in ProductForm → Shop Tab                             │
│  - Fix underlying issue (e.g., add category mapping)                │
│  - Click "Retry Sync" button → re-dispatch job                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.2 Error Recovery Patterns

**Pattern 1: Automatic Retry (Transient Errors)**
```php
// Network timeouts, API rate limits, temporary DB issues
// → Handled by Laravel Queue retry mechanism (3x with backoff)

// Job configuration in SyncProductToPrestaShop
public int $tries = 3;
public function backoff(): array {
    return [30, 60, 300]; // 30s, 1min, 5min
}
```

**Pattern 2: Manual Retry (Permanent Errors)**
```php
// Data validation failures, missing mappings, configuration issues
// → Requires user intervention to fix root cause

// ProductForm Livewire component
public function retrySyncForShop($shopId)
{
    // 1. Reset error state
    $shopData = ProductShopData::where('product_id', $this->product->id)
        ->where('shop_id', $shopId)
        ->first();

    $shopData->update([
        'sync_status' => 'pending',
        'error_message' => null,
        'retry_count' => 0
    ]);

    // 2. Re-dispatch job
    $shop = PrestaShopShop::find($shopId);
    SyncProductToPrestaShop::dispatch(
        $this->product,
        $shop,
        auth()->id(),
        session()->get("pending_media_changes.{$this->product->id}", [])
    );

    session()->flash('success', "Retry queued for {$shop->name}");
}
```

**Pattern 3: Graceful Degradation (Non-Fatal Errors)**
```php
// Optional features fail but main sync succeeds
// → Log warning, continue with partial success

// ProductSyncStrategy::syncToPrestaShop()
try {
    $this->syncMediaIfEnabled($product, $shop, $externalId, $pendingMediaChanges);
} catch (\Exception $e) {
    // Log error but don't fail product sync
    Log::error('[MEDIA SYNC] Failed (non-fatal)', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'error' => $e->getMessage()
    ]);
}

// Same pattern for features sync
try {
    $this->syncFeaturesIfEnabled($product, $shop, $externalId, $client);
} catch (\Exception $e) {
    Log::error('[FEATURE SYNC] Failed (non-fatal)', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'error' => $e->getMessage()
    ]);
}

// Result: Product created in PrestaShop, images/features partially synced
// User can fix issues later and re-sync specific components
```

**Pattern 4: Per-Shop Independence**
```php
// Shop A fails → Shop B continues independently

// PublishService::dispatchExportJobs()
foreach ($shops as $shop) {
    try {
        SyncProductToPrestaShop::dispatch($product, $shop, auth()->id(), $pendingMediaChanges);
        $results['dispatched'][] = ['shop_id' => $shop->id, 'status' => 'queued'];
    } catch (\Exception $e) {
        $results['failed'][] = ['shop_id' => $shop->id, 'status' => 'dispatch_failed'];
        // Continue with next shop (non-blocking)
    }
}

// User sees mixed results:
// ✅ Shop A: Export queued successfully
// ✅ Shop B: Export queued successfully
// ❌ Shop C: Failed to dispatch job (Redis timeout)
```

### 6.3 Error Logging & Monitoring

**SyncLog Model** (Audit Trail)
```php
// Created for EVERY sync attempt (success or failure)
SyncLog::create([
    'shop_id' => $shop->id,
    'product_id' => $product->id,
    'operation' => 'sync_product',
    'direction' => 'ppm_to_ps',
    'status' => 'error', // 'success' | 'error' | 'warning'
    'message' => $exception->getMessage(),
    'execution_time_ms' => round($duration * 1000, 2),
    'created_at' => now()
]);
```

**SyncJob Model** (Job Tracking)
```php
// Created at start of job, updated throughout execution
$syncJob = SyncJob::create([
    'job_id' => \Str::uuid(),
    'job_type' => SyncJob::JOB_PRODUCT_SYNC,
    'job_name' => "Sync Product #{$product->id} to {$shop->name}",
    'source_type' => SyncJob::TYPE_PPM,
    'source_id' => $product->id,
    'target_type' => SyncJob::TYPE_PRESTASHOP,
    'target_id' => $shop->id,
    'status' => SyncJob::STATUS_PENDING,
    'trigger_type' => SyncJob::TRIGGER_MANUAL, // 'manual' | 'scheduled' | 'event'
    'user_id' => auth()->id(),
    'queue_name' => 'default',
    'queue_job_id' => $this->job->getJobId(),
    'total_items' => 1,
    'processed_items' => 0,
    'successful_items' => 0,
    'failed_items' => 0,
    'error_summary' => null,
    'error_details' => null,
    'stack_trace' => null,
    'scheduled_at' => now(),
    'started_at' => null,
    'completed_at' => null
]);

// On failure
$syncJob->fail(
    errorMessage: $e->getMessage(),
    errorDetails: $e->getFile() . ':' . $e->getLine(),
    stackTrace: $e->getTraceAsString()
);
```

**ProductShopData** (Current Status)
```php
// Always up-to-date with latest sync status
ProductShopData {
    sync_status: 'error',
    error_message: 'PrestaShop API returned 500: Internal Server Error',
    retry_count: 3,
    last_sync_at: '2025-12-08 10:05:00',
    last_success_sync_at: null // Never succeeded
}
```

### 6.4 User-Facing Error Display

**ProductForm - Shop Tab:**
```blade
@foreach ($shopSyncStatuses as $shopStatus)
    <div class="shop-sync-card {{ $shopStatus['status_class'] }}">
        <h4>{{ $shopStatus['shop_name'] }}</h4>

        @if ($shopStatus['sync_status'] === 'synced')
            <span class="badge badge-success">✓ Synced</span>
            <small>Last sync: {{ $shopStatus['last_sync'] }}</small>
        @elseif ($shopStatus['sync_status'] === 'syncing')
            <span class="badge badge-warning">⏳ Syncing...</span>
            <div wire:poll.5s>Checking status...</div>
        @elseif ($shopStatus['sync_status'] === 'error')
            <span class="badge badge-danger">✗ Error</span>
            <div class="error-message">{{ $shopStatus['error_message'] }}</div>
            <button wire:click="retrySyncForShop({{ $shopStatus['shop_id'] }})">
                Retry Sync
            </button>
        @else
            <span class="badge badge-secondary">○ Pending</span>
        @endif
    </div>
@endforeach
```

---

## 7. PODSUMOWANIE - FINAL RECOMMENDATIONS

### 7.1 Co Wykorzystać (GOTOWE)

✅ **`SyncProductToPrestaShop` Job**
- Używaj BEZPOŚREDNIO dla każdego shop
- NIE twórz nowego Job-a dla first-time export
- Przekazuj `$pendingMediaChanges` z ALL media marked as 'sync'

✅ **`ProductSyncStrategy`**
- WSZYSTKO już zaimplementowane (product, categories, prices, media, features)
- Checksum-based change detection (automatic CREATE vs UPDATE)
- Non-blocking errors dla optional features (media, features)

✅ **`ProductShopData` Model**
- Używaj do tracking sync status per shop
- Przechowuj shop-specific overrides (name, description, categories)
- `category_mappings` field (Option A format) dla categories

✅ **`PrestaShop8Client`**
- Pełny API coverage (products, images, features, combinations)
- XML formatting (PrestaShop requirement)
- Retry logic + error handling

✅ **`BulkSyncProducts` Job**
- Używaj TYLKO dla bulk import (multiple products at once)
- NIE używaj dla single product × multiple shops (overkill)

### 7.2 Co Stworzyć (NOWE)

🆕 **`ProductPublishService`** (Core Orchestrator)
- Validation before product creation
- Product creation in PPM (transaction)
- Shop-specific data preparation (ProductShopData)
- Job dispatch orchestration
- Status reporting

🆕 **Import Panel UI Integration**
- Livewire component dla import form
- Shop selection tiles (checkboxes)
- "Publikuj" button → calls `ProductPublishService::publishProduct()`
- Real-time sync status display (wire:poll)

🆕 **Import History Tracking** (Optional)
- `import_records` table dla tracking imports
- Status: draft → published
- Link to created Product

### 7.3 Workflow Po Publikacji

```
USER CLICKS "PUBLIKUJ"
        ↓
PublishService::publishProduct()
        ↓
Create Product in PPM (transaction)
        ↓
Create ProductShopData for each shop
        ↓
Dispatch SyncProductToPrestaShop × N shops (parallel)
        ↓
Jobs execute in background (queue worker)
        ↓
ProductShopData.sync_status updated (per shop)
        ↓
User sees status in ProductForm → Shop Tab
        ↓
If error → User fixes issue → Clicks "Retry"
        ↓
If success → Product visible in PrestaShop front-end
```

### 7.4 Kluczowe Decyzje Architektoniczne

1. **INDEPENDENT SHOP SYNC**: Każdy shop = oddzielny Job (non-blocking failures)
2. **REUSE EXISTING JOBS**: NIE twórz nowych Job-ów, wykorzystaj `SyncProductToPrestaShop`
3. **VALIDATION FIRST**: Sprawdź dane PRZED utworzeniem Product/Job (fail-fast)
4. **GRACEFUL DEGRADATION**: Optional features (media, features) don't block main sync
5. **USER-DRIVEN RETRY**: Permanent errors wymagają user intervention + manual retry
6. **COMPREHENSIVE LOGGING**: SyncLog (audit) + SyncJob (tracking) + ProductShopData (status)

### 7.5 Następne Kroki

1. ✅ Implement `ProductPublishService` (według propozycji w sekcji 4.1)
2. ✅ Create Import Panel Livewire component (form + shop selection)
3. ✅ Test end-to-end flow (import → publish → sync → verify in PrestaShop)
4. ✅ Add error handling UI (retry buttons, error messages)
5. ⏳ OPTIONAL: Import history tracking + notifications

---

## 8. PRZYKŁADOWE SCENARIUSZE

### Scenariusz 1: Sukces - Wszystkie Sklepy

```
USER: Uzupełnia produkt "Silnik 1500W" + wybiera 3 sklepy (B2B, Retail, Outlet)
      ↓
PublishService: Tworzy Product ID=500 + ProductShopData × 3
      ↓
Dispatch: 3 × SyncProductToPrestaShop jobs
      ↓
Queue: Jobs wykonują się w ciągu 30s
      ↓
RESULT: ProductShopData wszystkie = 'synced'
        - B2B: prestashop_product_id = 9755
        - Retail: prestashop_product_id = 9756
        - Outlet: prestashop_product_id = 9757
      ↓
USER: Widzi 3 × ✓ badge w ProductForm → Shop Tab
```

### Scenariusz 2: Partial Failure - Jeden Sklep Fails

```
USER: Uzupełnia produkt "Pompka hydrauliczna" + wybiera 2 sklepy (B2B, Retail)
      ↓
PublishService: Tworzy Product ID=501 + ProductShopData × 2
      ↓
Dispatch: 2 × SyncProductToPrestaShop jobs
      ↓
Queue: B2B job succeeds, Retail job fails (PrestaShop API timeout)
      ↓
Retry: Retail job retries 3x (30s, 1min, 5min intervals)
      ↓
All Retries Fail: Retail ProductShopData.sync_status = 'error'
      ↓
RESULT:
        - B2B: ✓ synced (prestashop_product_id = 9758)
        - Retail: ✗ error ("PrestaShop API timeout after 30s")
      ↓
USER: Widzi mixed status
      - Klika "Retry Sync" dla Retail
      - Popup: "Sync queued for Retail"
      - Po 10s: Retail = ✓ synced
```

### Scenariusz 3: Validation Failure - Przed Job Dispatch

```
USER: Uzupełnia produkt bez SKU + wybiera 1 sklep
      ↓
PublishService: validate() zwraca errors = ["SKU is required"]
      ↓
UI: Flash message = "Validation error: SKU is required"
      ↓
RESULT: NIE utworzono Product, NIE wywołano Job-ów
      ↓
USER: Uzupełnia SKU → klika "Publikuj" ponownie → SUCCESS
```

---

## ✅ WYKONANE PRACE

1. ✅ Przeanalizowano istniejące Job-y (`SyncProductToPrestaShop`, `BulkSyncProducts`)
2. ✅ Przeanalizowano Services (`ProductSyncStrategy`, `PrestaShop8Client`)
3. ✅ Zaprojektowano pełny workflow publikacji (diagram)
4. ✅ Odpowiedziano na wszystkie 5 pytań użytkownika
5. ✅ Opracowano propozycję API dla `ProductPublishService`
6. ✅ Przygotowano przykłady dispatch Job-ów
7. ✅ Opracowano strategię error handling (4 poziomy)
8. ✅ Przygotowano scenariusze testowe

## 📋 NASTĘPNE KROKI

1. Implementacja `ProductPublishService` zgodnie z propozycją
2. Utworzenie Import Panel Livewire component
3. Testy end-to-end workflow (import → publish → sync)
4. Implementacja UI dla error handling (retry buttons)
5. Opcjonalnie: Import history tracking

## 📁 PLIKI DO UTWORZENIA

1. `app/Services/Import/ProductPublishService.php` - Core orchestrator (według sekcji 4.1)
2. `app/Http/Livewire/Import/ImportPanel.php` - Import form UI
3. `resources/views/livewire/import/import-panel.blade.php` - Import form template
4. `database/migrations/XXXX_create_import_records_table.php` - Optional history tracking

---

**Agent**: prestashop-api-expert
**Status**: ✅ COMPLETED
**Czas wykonania**: Comprehensive analysis + full design
