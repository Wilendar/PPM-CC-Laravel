# RAPORT ARCHITEKTURY: Per-Shop Variants System

**Data**: 2025-12-04 14:30
**Agent**: architect
**Zadanie**: Zaprojektowanie architektury per-shop variants dla PPM-CC-Laravel

---

## EXECUTIVE SUMMARY

Aktualna implementacja BŁĘDNIE blokuje tworzenie wariantów w kontekście sklepu. Niniejszy dokument przedstawia szczegółową architekturę systemu per-shop variants, który umożliwi:

1. **Jeden produkt → wiele sklepów → każdy sklep może mieć INNE warianty**
2. **Dziedziczenie**: Sklep bez własnych wariantów dziedziczy z "Dane domyślne"
3. **Live data**: Shop tab = LIVE data z PrestaShop (pullShopData)
4. **Async sync**: Save → JOB → blokada UI "oczekiwanie na synchronizację"

---

## 1. DATABASE SCHEMA

### 1.1. Nowa Tabela: `shop_variants`

**Cel**: Przechowywanie wariantów specyficznych per sklep z możliwością ADD/OVERRIDE/DELETE

```sql
CREATE TABLE `shop_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

  -- Relations
  `shop_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK to prestashop_shops.id',
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK to products.id',
  `variant_id` BIGINT UNSIGNED NULL COMMENT 'FK to product_variants.id (NULL dla nowych wariantów specyficznych dla sklepu)',

  -- PrestaShop Integration
  `prestashop_combination_id` BIGINT UNSIGNED NULL COMMENT 'External ID z PrestaShop (ps_product_attribute.id_product_attribute)',

  -- Operation Type (ADD/OVERRIDE/DELETE)
  `operation_type` ENUM('ADD', 'OVERRIDE', 'DELETE', 'INHERIT') NOT NULL DEFAULT 'INHERIT'
    COMMENT 'ADD=nowy wariant dla sklepu, OVERRIDE=zmiana istniejącego, DELETE=ukrycie w sklepie, INHERIT=dziedzicz z product_variants',

  -- Variant Data (JSON - przechowuje dane wariantu jeśli operation_type=ADD lub OVERRIDE)
  `variant_data` JSON NULL COMMENT 'Pełne dane wariantu: {sku, name, attributes, prices, stock, images}',

  -- Synchronization
  `sync_status` ENUM('pending', 'in_progress', 'synced', 'failed') NOT NULL DEFAULT 'pending',
  `last_sync_at` TIMESTAMP NULL,
  `sync_error_message` TEXT NULL,

  -- Audit
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL COMMENT 'Soft deletes',

  -- Indexes
  INDEX `idx_shop_variants_shop_product` (`shop_id`, `product_id`),
  INDEX `idx_shop_variants_variant` (`variant_id`),
  INDEX `idx_shop_variants_prestashop` (`prestashop_combination_id`),
  INDEX `idx_shop_variants_sync_status` (`sync_status`),

  -- Foreign Keys
  FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE SET NULL,

  -- Constraints
  UNIQUE KEY `uk_shop_variant` (`shop_id`, `product_id`, `variant_id`),
  CHECK (JSON_VALID(`variant_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Per-shop variant overrides with ADD/OVERRIDE/DELETE operations';
```

### 1.2. Schema Explanation

**Operacje (operation_type):**

1. **INHERIT** (domyślny): Sklep dziedziczy wariant z `product_variants` (brak overrides)
2. **ADD**: Nowy wariant TYLKO dla tego sklepu (nie istnieje w `product_variants`)
3. **OVERRIDE**: Zmiana danych istniejącego wariantu dla sklepu (nadpisuje `product_variants`)
4. **DELETE**: Ukrycie wariantu w sklepie (istnieje w `product_variants`, ale nie eksportowany)

**variant_data JSON Structure:**

```json
{
  "sku": "VARIANT-SKU-123",
  "name": "XL Czerwony",
  "is_active": true,
  "is_default": false,
  "position": 1,
  "attributes": [
    {
      "attribute_type_id": 1,
      "value_id": 10,
      "color_hex": "#FF0000"
    }
  ],
  "prices": [
    {
      "price_group_id": 1,
      "price": 99.99,
      "price_type": "fixed"
    }
  ],
  "stock": [
    {
      "warehouse_id": 1,
      "quantity": 50,
      "reserved": 10
    }
  ],
  "images": [
    {
      "media_id": 123,
      "position": 1
    }
  ]
}
```

### 1.3. Migration SQL

**File**: `database/migrations/2025_12_04_000001_create_shop_variants_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05c: Per-Shop Variants System
     *
     * Creates shop_variants table for per-shop variant customization.
     *
     * FEATURES:
     * - ADD: New variants specific to shop
     * - OVERRIDE: Modify existing variants per shop
     * - DELETE: Hide variants in specific shop
     * - INHERIT: Use default variants from product_variants
     * - Sync tracking per shop
     * - PrestaShop combinations mapping
     */
    public function up(): void
    {
        Schema::create('shop_variants', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete()
                  ->comment('FK to prestashop_shops.id');

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete()
                  ->comment('FK to products.id');

            $table->foreignId('variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete()
                  ->comment('FK to product_variants.id (NULL for ADD operations)');

            // PrestaShop Integration
            $table->unsignedBigInteger('prestashop_combination_id')
                  ->nullable()
                  ->comment('External ID from PrestaShop (ps_product_attribute.id_product_attribute)');

            // Operation Type
            $table->enum('operation_type', ['ADD', 'OVERRIDE', 'DELETE', 'INHERIT'])
                  ->default('INHERIT')
                  ->comment('ADD=new variant, OVERRIDE=modify existing, DELETE=hide, INHERIT=use default');

            // Variant Data (JSON)
            $table->json('variant_data')
                  ->nullable()
                  ->comment('Full variant data: {sku, name, attributes, prices, stock, images}');

            // Synchronization
            $table->enum('sync_status', ['pending', 'in_progress', 'synced', 'failed'])
                  ->default('pending');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('sync_error_message')->nullable();

            // Audit
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['shop_id', 'product_id'], 'idx_shop_variants_shop_product');
            $table->index('variant_id', 'idx_shop_variants_variant');
            $table->index('prestashop_combination_id', 'idx_shop_variants_prestashop');
            $table->index('sync_status', 'idx_shop_variants_sync_status');

            // Unique Constraint
            $table->unique(['shop_id', 'product_id', 'variant_id'], 'uk_shop_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_variants');
    }
};
```

---

## 2. MODEL RELATIONSHIPS

### 2.1. New Model: `ShopVariant`

**File**: `app/Models/ShopVariant.php`

**Key Features:**
- Relationships: shop, product, baseVariant
- Operation type helpers: isAddOperation(), isOverrideOperation(), isDeleteOperation()
- Effective data merge: getEffectiveVariantData()
- Sync status tracking: markAsSynced(), markAsFailed()

**Core Implementation:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopVariant extends Model
{
    use SoftDeletes;

    protected $table = 'shop_variants';

    protected $fillable = [
        'shop_id', 'product_id', 'variant_id', 'prestashop_combination_id',
        'operation_type', 'variant_data', 'sync_status', 'last_sync_at', 'sync_error_message',
    ];

    protected $casts = [
        'variant_data' => 'array',
        'last_sync_at' => 'datetime',
    ];

    // Relationships
    public function shop(): BelongsTo { return $this->belongsTo(PrestaShopShop::class, 'shop_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
    public function baseVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'variant_id'); }

    // Operation Type Helpers
    public function isAddOperation(): bool { return $this->operation_type === 'ADD'; }
    public function isOverrideOperation(): bool { return $this->operation_type === 'OVERRIDE'; }
    public function isDeleteOperation(): bool { return $this->operation_type === 'DELETE'; }
    public function isInheritOperation(): bool { return $this->operation_type === 'INHERIT'; }

    /**
     * Get effective variant data (merged with base variant if OVERRIDE)
     */
    public function getEffectiveVariantData(): array
    {
        if ($this->isAddOperation()) {
            return $this->variant_data ?? [];
        }

        if ($this->isOverrideOperation() && $this->baseVariant) {
            $baseData = $this->baseVariant->toArray();
            $overrides = $this->variant_data ?? [];
            return array_merge($baseData, $overrides);
        }

        if ($this->isInheritOperation() && $this->baseVariant) {
            return $this->baseVariant->toArray();
        }

        return [];
    }

    // Sync Helpers
    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'sync_error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error_message' => $errorMessage,
        ]);
    }
}
```

### 2.2. Updates to Existing Models

**ProductVariant Model** - Dodaj relationship:

```php
/**
 * Shop-specific overrides
 */
public function shopOverrides(): HasMany
{
    return $this->hasMany(ShopVariant::class, 'variant_id');
}

/**
 * Get variants for specific shop (with overrides applied)
 */
public function getForShop(int $shopId): array
{
    $shopOverride = $this->shopOverrides()->where('shop_id', $shopId)->first();

    if (!$shopOverride) {
        return $this->toArray(); // No override - return base
    }

    if ($shopOverride->isDeleteOperation()) {
        return []; // Hidden in this shop
    }

    return $shopOverride->getEffectiveVariantData(); // OVERRIDE - merge
}
```

**Product Model** - Dodaj relationship:

```php
/**
 * Shop-specific variants
 */
public function shopVariants(): HasMany
{
    return $this->hasMany(ShopVariant::class, 'product_id');
}

/**
 * Get all variants for specific shop (base + shop-specific)
 */
public function getVariantsForShop(int $shopId): Collection
{
    // 1. Get base variants (not deleted in shop)
    $baseVariants = $this->variants()
                         ->whereDoesntHave('shopOverrides', function ($query) use ($shopId) {
                             $query->where('shop_id', $shopId)->where('operation_type', 'DELETE');
                         })
                         ->get();

    // 2. Apply overrides
    $variantsWithOverrides = $baseVariants->map(function ($variant) use ($shopId) {
        return $variant->getForShop($shopId);
    })->filter();

    // 3. Add shop-specific variants (ADD operations)
    $shopOnlyVariants = $this->shopVariants()
                             ->where('shop_id', $shopId)
                             ->where('operation_type', 'ADD')
                             ->get()
                             ->map(fn($sv) => $sv->getEffectiveVariantData());

    return $variantsWithOverrides->merge($shopOnlyVariants);
}
```

---

## 3. SERVICE ARCHITECTURE

### 3.1. ShopVariantService

**File**: `app/Services/ShopVariantService.php`

**Responsibilities**:
- Merge base variants with shop overrides
- Create/Update/Delete shop-specific variants
- Pull variants from PrestaShop
- Prepare data for sync jobs

**Key Methods:**

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopVariant;
use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopVariantService
{
    /**
     * Get all variants for product in specific shop
     * (base variants + shop overrides + shop-only variants)
     */
    public function getVariantsForShop(Product $product, PrestaShopShop $shop): Collection
    {
        return $product->getVariantsForShop($shop->id);
    }

    /**
     * Pull variants from PrestaShop and create/update shop_variants
     *
     * CRITICAL: Called when user enters shop tab (live data)
     */
    public function pullShopVariants(Product $product, PrestaShopShop $shop): Collection
    {
        Log::info('Pulling shop variants from PrestaShop', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
        ]);

        try {
            // 1. Get PrestaShop product ID
            $shopData = $product->shopData()->where('shop_id', $shop->id)->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::warning('Cannot pull variants - no PrestaShop product ID');
                return collect();
            }

            // 2. Fetch combinations from PrestaShop
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
            $combinations = $client->getCombinations($shopData->prestashop_product_id);

            Log::info('Fetched combinations from PrestaShop', [
                'combinations_count' => count($combinations),
            ]);

            // 3. Sync to shop_variants table
            return $this->syncShopVariantsFromPrestaShop($product, $shop, $combinations);

        } catch (\Exception $e) {
            Log::error('Failed to pull shop variants', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync PrestaShop combinations to shop_variants table
     */
    protected function syncShopVariantsFromPrestaShop(
        Product $product,
        PrestaShopShop $shop,
        array $combinations
    ): Collection {
        $shopVariants = collect();

        DB::transaction(function () use ($product, $shop, $combinations, &$shopVariants) {
            foreach ($combinations as $combo) {
                // Find matching base variant by attributes
                $baseVariant = $this->findMatchingBaseVariant($product, $combo);

                // Create or update shop_variant
                $shopVariant = ShopVariant::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'product_id' => $product->id,
                        'prestashop_combination_id' => $combo['id_product_attribute'],
                    ],
                    [
                        'variant_id' => $baseVariant?->id,
                        'operation_type' => $baseVariant ? 'INHERIT' : 'ADD',
                        'variant_data' => $this->transformPrestaShopCombination($combo),
                        'sync_status' => 'synced',
                        'last_sync_at' => now(),
                    ]
                );

                $shopVariants->push($shopVariant);
            }
        });

        return $shopVariants;
    }

    /**
     * Create shop-specific variant (ADD operation)
     */
    public function createShopVariant(
        Product $product,
        PrestaShopShop $shop,
        array $variantData
    ): ShopVariant {
        return ShopVariant::create([
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'operation_type' => 'ADD',
            'variant_data' => $variantData,
            'sync_status' => 'pending',
        ]);
    }

    /**
     * Override base variant for shop (OVERRIDE operation)
     */
    public function overrideVariantForShop(
        ProductVariant $baseVariant,
        PrestaShopShop $shop,
        array $overrides
    ): ShopVariant {
        return ShopVariant::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'product_id' => $baseVariant->product_id,
                'variant_id' => $baseVariant->id,
            ],
            [
                'operation_type' => 'OVERRIDE',
                'variant_data' => $overrides,
                'sync_status' => 'pending',
            ]
        );
    }

    /**
     * Delete (hide) variant in shop (DELETE operation)
     */
    public function deleteVariantInShop(
        ProductVariant $baseVariant,
        PrestaShopShop $shop
    ): ShopVariant {
        return ShopVariant::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'product_id' => $baseVariant->product_id,
                'variant_id' => $baseVariant->id,
            ],
            [
                'operation_type' => 'DELETE',
                'variant_data' => null,
                'sync_status' => 'pending',
            ]
        );
    }

    /**
     * Reset shop variant to inherit from base (INHERIT operation)
     */
    public function resetToInherit(ShopVariant $shopVariant): void
    {
        if ($shopVariant->isAddOperation()) {
            $shopVariant->delete(); // Cannot reset ADD - must delete
        } else {
            $shopVariant->update([
                'operation_type' => 'INHERIT',
                'variant_data' => null,
                'sync_status' => 'pending',
            ]);
        }
    }

    /**
     * Transform PrestaShop combination to variant_data format
     */
    protected function transformPrestaShopCombination(array $combination): array
    {
        return [
            'sku' => $combination['reference'] ?? '',
            'name' => $combination['name'] ?? '',
            'is_active' => true,
            'position' => $combination['position'] ?? 0,
            'attributes' => $combination['attributes'] ?? [],
            'prices' => [['price_group_id' => 1, 'price' => $combination['price'] ?? 0]],
            'stock' => [['warehouse_id' => 1, 'quantity' => $combination['quantity'] ?? 0]],
            'images' => $combination['images'] ?? [],
        ];
    }

    /**
     * Find matching base variant by attributes (simplified)
     */
    protected function findMatchingBaseVariant(Product $product, array $combination): ?ProductVariant
    {
        // TODO: Implement attribute matching algorithm
        return null;
    }
}
```

---

## 4. PRESTASHOP API INTEGRATION

### 4.1. New Methods in BasePrestaShopClient

**File**: `app/Services/PrestaShop/BasePrestaShopClient.php`

Dodaj następujące metody:

```php
/**
 * Get all combinations (variants) for product
 */
public function getCombinations(int $productId): array
{
    $endpoint = "/products/{$productId}/combinations";
    $response = $this->makeRequest('GET', $endpoint);

    if (isset($response['combinations']['combination'])) {
        $combinations = $response['combinations']['combination'];

        // Ensure array (single item = associative)
        if (isset($combinations['id'])) {
            $combinations = [$combinations];
        }

        return array_map(fn($combo) => $this->parseCombination($combo), $combinations);
    }

    return [];
}

/**
 * Create combination for product
 */
public function createCombination(int $productId, array $combinationData): array
{
    $xmlBody = $this->arrayToXml(['combination' => $combinationData]);

    $response = $this->makeRequest(
        'POST',
        "/products/{$productId}/combinations",
        [],
        ['body' => $xmlBody, 'headers' => ['Content-Type' => 'application/xml']]
    );

    if (isset($response['combination'])) {
        return $this->parseCombination($response['combination']);
    }

    throw new \Exception('Failed to create combination');
}

/**
 * Update combination (GET-MODIFY-PUT pattern)
 */
public function updateCombination(int $productId, int $combinationId, array $combinationData): array
{
    // 1. GET existing
    $existing = $this->getCombination($productId, $combinationId);

    // 2. MERGE with updates
    $merged = array_merge($existing, $combinationData);
    $merged['id'] = $combinationId; // CRITICAL for UPDATE

    // 3. PUT
    $xmlBody = $this->arrayToXml(['combination' => $merged]);

    $response = $this->makeRequest(
        'PUT',
        "/products/{$productId}/combinations/{$combinationId}",
        [],
        ['body' => $xmlBody, 'headers' => ['Content-Type' => 'application/xml']]
    );

    if (isset($response['combination'])) {
        return $this->parseCombination($response['combination']);
    }

    throw new \Exception('Failed to update combination');
}

/**
 * Delete combination
 */
public function deleteCombination(int $productId, int $combinationId): bool
{
    $this->makeRequest('DELETE', "/products/{$productId}/combinations/{$combinationId}");
    return true;
}

/**
 * Parse combination XML to array
 */
protected function parseCombination(array $combo): array
{
    return [
        'id_product_attribute' => (int)($combo['id'] ?? 0),
        'reference' => $combo['reference'] ?? '',
        'ean13' => $combo['ean13'] ?? '',
        'quantity' => (int)($combo['quantity'] ?? 0),
        'price' => (float)($combo['price'] ?? 0),
        'position' => (int)($combo['position'] ?? 0),
        'attributes' => $this->parseAttributes($combo['associations']['product_option_values'] ?? []),
        'images' => $this->parseImages($combo['associations']['images'] ?? []),
    ];
}
```

### 4.2. PrestaShop API Endpoints

```
GET    /api/products/{id}/combinations              # List all combinations
GET    /api/products/{id}/combinations/{combo_id}   # Get single combination
POST   /api/products/{id}/combinations              # Create combination
PUT    /api/products/{id}/combinations/{combo_id}   # Update combination
DELETE /api/products/{id}/combinations/{combo_id}   # Delete combination
```

---

## 5. SYNC JOB ARCHITECTURE

### 5.1. SyncVariantsToPrestaShopJob

**File**: `app/Jobs/PrestaShop/SyncVariantsToPrestaShopJob.php`

**Workflow:**
1. Fetch all shop_variants with sync_status=pending
2. For each variant:
   - ADD: Create combination in PrestaShop
   - OVERRIDE: Update combination in PrestaShop
   - DELETE: Delete combination in PrestaShop
3. Mark as synced or failed
4. Emit Livewire event for UI update

**Core Implementation:**

```php
<?php

namespace App\Jobs\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ShopVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncVariantsToPrestaShopJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public Product $product;
    public PrestaShopShop $shop;
    public ?int $userId;
    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(Product $product, PrestaShopShop $shop, ?int $userId = null)
    {
        $this->product = $product;
        $this->shop = $shop;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        Log::info('SyncVariantsToPrestaShopJob started', [
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
        ]);

        try {
            // Get PrestaShop product ID
            $shopData = $this->product->shopData()->where('shop_id', $this->shop->id)->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                throw new \Exception('Product not synced to PrestaShop');
            }

            $psProductId = $shopData->prestashop_product_id;
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($this->shop);

            // Get pending variants
            $pendingVariants = ShopVariant::where('product_id', $this->product->id)
                                          ->where('shop_id', $this->shop->id)
                                          ->where('sync_status', 'pending')
                                          ->get();

            DB::transaction(function () use ($pendingVariants, $client, $psProductId) {
                foreach ($pendingVariants as $shopVariant) {
                    $this->syncSingleVariant($shopVariant, $client, $psProductId);
                }
            });

            // Emit event for UI refresh
            event(new \App\Events\VariantsSyncCompleted($this->product->id, $this->shop->id));

        } catch (\Exception $e) {
            Log::error('SyncVariantsToPrestaShopJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncSingleVariant(ShopVariant $shopVariant, $client, int $psProductId): void
    {
        try {
            $shopVariant->update(['sync_status' => 'in_progress']);

            if ($shopVariant->isAddOperation()) {
                // CREATE
                $data = $this->prepareCombinationData($shopVariant);
                $result = $client->createCombination($psProductId, $data);
                $shopVariant->update(['prestashop_combination_id' => $result['id_product_attribute']]);

            } elseif ($shopVariant->isOverrideOperation()) {
                // UPDATE
                if (!$shopVariant->prestashop_combination_id) {
                    throw new \Exception('No PrestaShop combination ID for UPDATE');
                }
                $data = $this->prepareCombinationData($shopVariant);
                $client->updateCombination($psProductId, $shopVariant->prestashop_combination_id, $data);

            } elseif ($shopVariant->isDeleteOperation()) {
                // DELETE
                if ($shopVariant->prestashop_combination_id) {
                    $client->deleteCombination($psProductId, $shopVariant->prestashop_combination_id);
                }
            }

            $shopVariant->markAsSynced();

        } catch (\Exception $e) {
            $shopVariant->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function prepareCombinationData(ShopVariant $shopVariant): array
    {
        $variantData = $shopVariant->getEffectiveVariantData();

        return [
            'reference' => $variantData['sku'] ?? '',
            'quantity' => $variantData['stock'][0]['quantity'] ?? 0,
            'price' => $variantData['prices'][0]['price'] ?? 0,
            'position' => $variantData['position'] ?? 0,
            'associations' => [
                'product_option_values' => array_map(fn($a) => ['id' => $a['value_id']], $variantData['attributes'] ?? []),
            ],
        ];
    }
}
```

---

## 6. UI FLOW & COMPONENT CHANGES

### 6.1. ProductForm Livewire Component

**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Nowe properties:**

```php
public bool $variantsSyncInProgress = false;
public ?int $selectedShopForVariants = null;

protected $listeners = [
    'variantsSyncCompleted' => 'handleVariantsSyncCompleted',
];
```

**Nowe metody:**

```php
/**
 * Select shop tab for variants
 */
public function selectShopTabForVariants(int $shopId): void
{
    $this->selectedShopForVariants = $shopId;
    $this->pullShopVariants($shopId); // Pull live data
}

/**
 * Pull variants from PrestaShop (live data)
 */
public function pullShopVariants(int $shopId): void
{
    $shop = PrestaShopShop::findOrFail($shopId);
    $variantService = app(ShopVariantService::class);
    $variantService->pullShopVariants($this->product, $shop);
    $this->product->refresh();
}

/**
 * Save variants and dispatch sync job
 */
public function saveVariants(): void
{
    $shop = PrestaShopShop::findOrFail($this->selectedShopForVariants);

    SyncVariantsToPrestaShopJob::dispatch($this->product, $shop, auth()->id());

    $this->variantsSyncInProgress = true;
}

/**
 * Handle variants sync completed event
 */
public function handleVariantsSyncCompleted(): void
{
    $this->variantsSyncInProgress = false;
    $this->product->refresh();
}

/**
 * Get variants for current shop tab
 */
public function getVariantsForShopProperty(): Collection
{
    if (!$this->selectedShopForVariants) {
        return $this->product->variants; // Default - base variants
    }

    $variantService = app(ShopVariantService::class);
    $shop = PrestaShopShop::find($this->selectedShopForVariants);

    return $variantService->getVariantsForShop($this->product, $shop);
}
```

### 6.2. Blade Template

**File**: `resources/views/livewire/products/management/tabs/variants-tab.blade.php`

**Struktura UI:**

```blade
{{-- Shop Tabs Navigation --}}
<div class="shop-tabs-nav">
    <button wire:click="selectShopTabForVariants(null)"
            @class(['shop-tab', 'active' => !$selectedShopForVariants])>
        Dane domyślne
    </button>

    @foreach($product->shopData as $shopData)
        <button wire:click="selectShopTabForVariants({{ $shopData->shop_id }})"
                @class(['shop-tab', 'active' => $selectedShopForVariants === $shopData->shop_id])>
            {{ $shopData->shop->name }}
        </button>
    @endforeach
</div>

{{-- Sync Status Banner --}}
@if($variantsSyncInProgress)
    <div class="sync-in-progress-banner">
        <span>Oczekiwanie na synchronizację wariantów...</span>
    </div>
@endif

{{-- Variants Table (disabled podczas sync) --}}
<div @class(['variants-container', 'disabled' => $variantsSyncInProgress])>
    <table>
        {{-- Warianty --}}
    </table>

    <button wire:click="$emit('openVariantModal')"
            @disabled($variantsSyncInProgress)>
        Dodaj wariant
    </button>
</div>

{{-- Save Button --}}
<button wire:click="saveVariants" @disabled($variantsSyncInProgress)>
    Zapisz zmiany
</button>

{{-- Livewire Event Listener --}}
<script>
    window.addEventListener('variants.synced', event => {
        @this.call('handleVariantsSyncCompleted');
    });
</script>
```

---

## 7. UI FLOW DIAGRAM

```
User enters Shop Tab
        ↓
selectShopTabForVariants()
        ↓
pullShopVariants() → LIVE from PrestaShop
        ↓
syncShopVariantsFromPrestaShop()
        ↓
Merge base variants + shop overrides
        ↓
Display variants in UI
        ↓
User edits/adds/deletes variant
        ↓
Update shop_variants (sync_status=pending)
        ↓
User clicks Save
        ↓
Dispatch SyncVariantsToPrestaShopJob
        ↓
Set variantsSyncInProgress=true
        ↓
Disable all inputs + show banner
        ↓
JOB: Process each pending shop_variant
        ↓
ADD → createCombination
OVERRIDE → updateCombination
DELETE → deleteCombination
        ↓
Mark as synced
        ↓
Emit VariantsSyncCompleted event
        ↓
handleVariantsSyncCompleted()
        ↓
Set variantsSyncInProgress=false
        ↓
Enable inputs + refresh data
```

---

## 8. IMPLEMENTATION CHECKLIST

### Phase 1: Database & Models (2-3h)
- [ ] Migration: `shop_variants` table
- [ ] Model: `ShopVariant.php`
- [ ] Relationships: ProductVariant, Product, PrestaShopShop
- [ ] Test relationships

### Phase 2: PrestaShop API (3-4h)
- [ ] BasePrestaShopClient: getCombinations(), createCombination(), updateCombination(), deleteCombination()
- [ ] Helper: parseCombination()
- [ ] Test API calls

### Phase 3: Service Layer (4-5h)
- [ ] ShopVariantService: getVariantsForShop(), pullShopVariants(), CRUD operations
- [ ] Test service methods

### Phase 4: Sync Job (3-4h)
- [ ] SyncVariantsToPrestaShopJob
- [ ] Event: VariantsSyncCompleted
- [ ] Test job dispatch

### Phase 5: Livewire Component (5-6h)
- [ ] ProductForm properties & methods
- [ ] Event listener
- [ ] Test component

### Phase 6: UI Implementation (6-8h)
- [ ] Blade template: shop tabs, sync banner, disabled state
- [ ] CSS styles
- [ ] JavaScript listener
- [ ] Test UI flow

### Phase 7: Testing & Deployment (4-5h)
- [ ] Lokalne testy (ADD/OVERRIDE/DELETE/INHERIT)
- [ ] Deployment do Hostido
- [ ] Chrome DevTools verification
- [ ] User acceptance testing

### Phase 8: Documentation (2-3h)
- [ ] Update CLAUDE.md
- [ ] Update Plan_Projektu
- [ ] Create user guide

---

## 9. ESTIMATED TIMELINE

**Total: 30-38 godzin (4-5 dni full-time)**

---

## 10. RISKS & MITIGATION

### RISK 1: PrestaShop attribute mapping complexity
**Mitigation:** Phase 1 = manual mapping, Phase 2 = automatic

### RISK 2: Sync job performance
**Mitigation:** Batch processing, progress tracking, timeout handling

### RISK 3: UI freeze podczas sync
**Mitigation:** Blokada TYLKO sekcji wariantów, clear feedback, background sync

### RISK 4: Data conflicts
**Mitigation:** Last-write-wins, checksum comparison, manual resolution UI (future)

---

## 11. SUMMARY

Kompletna architektura per-shop variants system:

✅ **Jeden produkt → wiele sklepów → różne warianty per sklep**
✅ **Dziedziczenie**: INHERIT operation
✅ **Live data**: pullShopVariants przy wejściu na tab
✅ **Async sync**: JOB + blokada UI + event listener
✅ **Operations**: ADD, OVERRIDE, DELETE, INHERIT

**Kluczowe komponenty:**
1. Database: `shop_variants` + operation_type + variant_data JSON
2. Models: ShopVariant + relationships
3. Service: ShopVariantService (merge logic + PrestaShop)
4. API: getCombinations + CRUD
5. Job: SyncVariantsToPrestaShopJob
6. UI: Shop tabs + sync banner + disabled state

**Next Steps:**
1. Review architektury
2. Zatwierdzenie
3. Rozpoczęcie implementacji (Phase 1)

---

**Koniec raportu**
