# ARCHITECT PLANNING REPORT: ETAP_07b FAZA 1 - PrestaShop Category API Integration

**Date**: 2025-11-19
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Task**: Design comprehensive architecture for PrestaShop Category API Integration
**Estimated Time**: 8-12h implementation (after planning approval)
**Dependencies**: ETAP_07 (PrestaShop API clients), ETAP_05 (Products), CategoryMapper

---

## EXECUTIVE SUMMARY

This report provides comprehensive architectural design for **FAZA 1: PrestaShop Category API Integration** - the foundation of ETAP_07b Category System Redesign.

**Problem:** Shop TAB currently shows PPM categories instead of PrestaShop categories, causing sync failures and data inconsistency.

**Solution:** Create PrestaShopCategoryService to fetch category tree from PrestaShop API, cache it (15min TTL), and display in ProductForm UI.

**Key Deliverables:**
1. PrestaShopCategoryService (NEW) - API integration + caching
2. UI "Odśwież kategorie" button - Manual cache refresh
3. Updated Shop TAB - Display PrestaShop categories instead of PPM

**Architecture Reviewed:**
- ✅ Laravel 12.x cache patterns (Context7)
- ✅ PrestaShop API category endpoints (Context7)
- ✅ Existing CategoryMapper (15min TTL, Cache::remember)
- ✅ Existing BasePrestaShopClient (retry logic, error handling)

---

## 1. ARCHITECTURE DESIGN

### 1.1 System Context

**Current Architecture (BROKEN):**
```
ProductForm (Shop TAB)
  ↓
Shows: PPM categories from `categories` table
  ↓
User selects: [60, 61] (PPM IDs)
  ↓
Saved to: product_categories pivot (shop_id=X, category_id=60)
  ↓
During sync: CategoryMapper::mapToPrestaShop(60, shop=X)
  ↓
Result: NULL (no mapping exists) ❌
  ↓
FALLBACK: Uses stale cache from product_shop_data.category_mappings ❌
```

**Target Architecture (NEW):**
```
ProductForm (Shop TAB)
  ↓
Shows: PrestaShop categories via PrestaShopCategoryService
  ↓
PrestaShopCategoryService::getCachedCategoryTree(shop)
  ↓
Cache HIT? → Return cached tree (15min TTL)
Cache MISS? → fetchCategoriesFromShop() → API call → Cache → Return
  ↓
User clicks "Odśwież kategorie" → clearCache() → fetchCategoriesFromShop()
  ↓
User selects PrestaShop categories → ProductForm saves to pivot
  ↓
During sync: Categories GUARANTEED to exist in PrestaShop ✅
```

### 1.2 Component Design: PrestaShopCategoryService

**Location:** `app/Services/PrestaShop/PrestaShopCategoryService.php`

**Namespace:** `App\Services\PrestaShop`

**Dependencies:**
- BasePrestaShopClient (via PrestaShop8Client/PrestaShop9Client)
- Laravel Cache facade
- PrestaShopShop model
- Category model (PPM)

**Class Structure:**

```php
<?php

namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Exceptions\PrestaShopAPIException;

/**
 * PrestaShop Category Service
 *
 * ETAP_07b FAZA 1 - PrestaShop Category API Integration
 *
 * Fetches category tree from PrestaShop API, caches it,
 * and provides formatted tree for ProductForm UI.
 *
 * Features:
 * - Pull category tree from PrestaShop /api/categories
 * - Cache with 15min TTL (consistent with CategoryMapper)
 * - Manual refresh invalidation
 * - Hierarchical tree formatting (parent-child)
 * - PrestaShop 8.x & 9.x compatibility
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07b FAZA 1
 */
class PrestaShopCategoryService
{
    /**
     * Cache TTL in seconds (15 minutes - consistent with CategoryMapper)
     */
    private const CACHE_TTL = 900;

    /**
     * Cache key prefix for category tree
     */
    private const CACHE_PREFIX = 'prestashop_category_tree';

    /**
     * PrestaShop API client factory
     *
     * @var PrestaShopClientFactory
     */
    private PrestaShopClientFactory $clientFactory;

    /**
     * Constructor
     */
    public function __construct(PrestaShopClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * Fetch categories from PrestaShop API
     *
     * Calls /api/categories endpoint with full display
     *
     * @param PrestaShopShop $shop Shop instance
     * @return Collection Category collection from PrestaShop
     * @throws PrestaShopAPIException On API errors
     */
    public function fetchCategoriesFromShop(PrestaShopShop $shop): Collection;

    /**
     * Sync categories to cache
     *
     * Fetches from API and stores in cache with TTL
     *
     * @param PrestaShopShop $shop Shop instance
     * @return void
     * @throws PrestaShopAPIException On API errors
     */
    public function syncCategoriesToCache(PrestaShopShop $shop): void;

    /**
     * Get cached category tree (formatted for UI)
     *
     * Returns hierarchical tree structure for Alpine.js consumption
     *
     * Format:
     * [
     *   'id' => int,
     *   'name' => string,
     *   'parent_id' => int|null,
     *   'level' => int,
     *   'children' => array,
     *   'is_active' => bool,
     *   'is_mapped' => bool, // NEW: Czy zmapowana w CategoryMapper
     * ]
     *
     * @param PrestaShopShop $shop Shop instance
     * @return array Hierarchical category tree
     */
    public function getCachedCategoryTree(PrestaShopShop $shop): array;

    /**
     * Clear category tree cache for shop
     *
     * Called when user clicks "Odśwież kategorie"
     *
     * @param PrestaShopShop $shop Shop instance
     * @return void
     */
    public function clearCache(PrestaShopShop $shop): void;

    /**
     * Check if category exists in PrestaShop
     *
     * @param int $categoryId PrestaShop category ID
     * @param PrestaShopShop $shop Shop instance
     * @return bool True if exists
     */
    public function categoryExists(int $categoryId, PrestaShopShop $shop): bool;

    /**
     * Create category in PrestaShop (FUTURE - FAZA 3)
     *
     * NOTE: Implementation deferred to FAZA 3 (Auto-Create Missing Categories)
     *
     * @param Category $ppmCategory PPM category to create
     * @param PrestaShopShop $shop Shop instance
     * @param int|null $parentId PrestaShop parent category ID
     * @return int Created PrestaShop category ID
     */
    public function createCategoryInShop(
        Category $ppmCategory,
        PrestaShopShop $shop,
        ?int $parentId = null
    ): int;

    // Private helper methods
    private function getCacheKey(int $shopId): string;
    private function buildCategoryTree(Collection $categories): array;
    private function addMappingStatus(array $tree, PrestaShopShop $shop): array;
}
```

**File Size Estimate:** ~250 lines (within CLAUDE.md 300-line limit)

### 1.3 Integration with Existing Components

#### 1.3.1 BasePrestaShopClient Integration

PrestaShopCategoryService REUSES existing infrastructure:

```php
// In fetchCategoriesFromShop():
$client = $this->clientFactory->make($shop);

// Call /api/categories with full display
$response = $client->makeRequest('GET', '/categories', [], [
    'query' => [
        'display' => 'full',
        'filter[active]' => '1', // Only active categories
    ]
]);
```

**Benefits:**
- ✅ Automatic retry logic (3 attempts, exponential backoff)
- ✅ Error handling (PrestaShopAPIException)
- ✅ Logging (request/response)
- ✅ PrestaShop 8.x & 9.x compatibility

#### 1.3.2 CategoryMapper Integration

**NEW method in CategoryMapper:**

```php
/**
 * Check if category is mapped and get mapping status
 *
 * @param int $prestashopId PrestaShop category ID
 * @param PrestaShopShop $shop Shop instance
 * @return array|null ['ppm_id' => int, 'ppm_name' => string] or null
 */
public function getMappingStatus(int $prestashopId, PrestaShopShop $shop): ?array
{
    $ppmId = $this->mapFromPrestaShop($prestashopId, $shop);

    if ($ppmId === null) {
        return null;
    }

    $category = Category::find($ppmId);

    return [
        'ppm_id' => $ppmId,
        'ppm_name' => $category ? $category->name : 'Unknown',
    ];
}
```

**Usage in PrestaShopCategoryService:**

```php
private function addMappingStatus(array $tree, PrestaShopShop $shop): array
{
    $mapper = app(CategoryMapper::class);

    return array_map(function ($node) use ($mapper, $shop) {
        $mapping = $mapper->getMappingStatus($node['id'], $shop);

        $node['is_mapped'] = $mapping !== null;
        $node['ppm_mapping'] = $mapping;

        if (isset($node['children'])) {
            $node['children'] = $this->addMappingStatus($node['children'], $shop);
        }

        return $node;
    }, $tree);
}
```

**UI Display:**
- Green badge: Mapped (kategoria zmapowana w PPM)
- Gray badge: Unmapped (kategoria TYLKO w PrestaShop)

#### 1.3.3 ProductForm Integration

**NEW Livewire methods in ProductForm:**

```php
/**
 * Refresh categories from PrestaShop
 *
 * Invalidates cache and re-fetches from API
 *
 * @return void
 */
public function refreshCategoriesFromShop(): void
{
    if ($this->activeShopId === null) {
        $this->addError('shop', 'Select shop first');
        return;
    }

    $shop = PrestaShopShop::find($this->activeShopId);

    if (!$shop) {
        $this->addError('shop', 'Shop not found');
        return;
    }

    try {
        $service = app(PrestaShopCategoryService::class);
        $service->clearCache($shop);
        $service->syncCategoriesToCache($shop);

        $this->dispatch('shop-categories-reloaded', shopId: $shop->id);
        $this->dispatch('notify', message: 'Categories refreshed successfully', type: 'success');
    } catch (\Exception $e) {
        Log::error('Failed to refresh categories', [
            'shop_id' => $shop->id,
            'error' => $e->getMessage(),
        ]);

        $this->addError('categories', 'Failed to refresh categories: ' . $e->getMessage());
    }
}

/**
 * Get PrestaShop category tree for shop
 *
 * @param int $shopId Shop ID
 * @return array Category tree
 */
public function getPrestaShopCategoryTree(int $shopId): array
{
    $shop = PrestaShopShop::find($shopId);

    if (!$shop) {
        return [];
    }

    try {
        $service = app(PrestaShopCategoryService::class);
        return $service->getCachedCategoryTree($shop);
    } catch (\Exception $e) {
        Log::error('Failed to get category tree', [
            'shop_id' => $shop->id,
            'error' => $e->getMessage(),
        ]);

        return [];
    }
}
```

**Blade UI (product-form.blade.php):**

```blade
{{-- Shop TAB - Categories Section --}}
<div class="categories-section">
    <div class="section-header">
        <h3>Kategorie (PrestaShop)</h3>
        <button type="button" wire:click="refreshCategoriesFromShop" class="btn-refresh">
            <span wire:loading.remove wire:target="refreshCategoriesFromShop">
                &#x21BB; Odśwież kategorie
            </span>
            <span wire:loading wire:target="refreshCategoriesFromShop">
                &#x231B; Refreshing...
            </span>
        </button>
    </div>

    <div class="category-tree" x-data="categoryTreeComponent(@js($this->getPrestaShopCategoryTree($activeShopId)))">
        {{-- Alpine.js tree rendering --}}
        {{-- See: resources/views/components/category-tree.blade.php --}}
        <x-category-tree :categories="$categories" />
    </div>
</div>
```

### 1.4 Cache Strategy

**Cache Driver:** Database (fallback) or Redis (if available)

**Rationale:**
- Production hostido.net.pl: No Redis, uses database cache
- Development: Redis preferred for performance
- Laravel Cache facade abstracts driver (no code change needed)

**Cache Key Structure:**

```php
private function getCacheKey(int $shopId): string
{
    return self::CACHE_PREFIX . ":{$shopId}";
}

// Examples:
// prestashop_category_tree:1  (Shop 1 - Pitbike.pl)
// prestashop_category_tree:5  (Shop 5 - Test KAYO)
```

**TTL Strategy:**

| Scenario | TTL | Rationale |
|----------|-----|-----------|
| Normal operation | 15 min (900s) | Consistent with CategoryMapper, balances freshness vs performance |
| Manual refresh | 0s (invalidate) | User-triggered, forces API call |
| API error | 5 min (300s) | Short TTL for error state, retry sooner |

**Cache Invalidation Triggers:**

1. Manual refresh (user clicks "Odśwież kategorie")
2. Category creation in PrestaShop (FAZA 3 - future)
3. Shop modification (admin changes PrestaShop URL/API key)

**Cache Warming Strategy:**

```php
// In PrestaShopShop model boot():
protected static function booted()
{
    static::updated(function ($shop) {
        // Clear cache when shop credentials change
        if ($shop->isDirty(['prestashop_url', 'api_key'])) {
            app(PrestaShopCategoryService::class)->clearCache($shop);
        }
    });
}
```

**Performance Optimization:**

Laravel 12.x flexible cache (stale-while-revalidate):

```php
public function getCachedCategoryTree(PrestaShopShop $shop): array
{
    $cacheKey = $this->getCacheKey($shop->id);

    // Use flexible cache: serve stale data while revalidating
    return Cache::flexible($cacheKey, [self::CACHE_TTL, self::CACHE_TTL * 2], function () use ($shop) {
        $categories = $this->fetchCategoriesFromShop($shop);
        $tree = $this->buildCategoryTree($categories);
        return $this->addMappingStatus($tree, $shop);
    });
}
```

**Benefits:**
- First request after expiration: Returns stale cache, triggers background refresh
- Subsequent requests: Get fresh data
- No user waiting for API call

---

## 2. DATA FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────────┐
│ USER INTERFACE (ProductForm - Shop TAB)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [Dropdown: Shop X]  [Button: Odśwież kategorie]                │
│                                                                  │
│  Categories (PrestaShop):                                        │
│  ☑ Root Category (9)                      [Mapped ✓]            │
│    ☑ Vehicles (15)                        [Mapped ✓]            │
│      ☐ Buggy (800)                        [Unmapped]            │
│      ☑ ATV (981)                          [Mapped ✓]            │
│                                                                  │
│  Legend: [Mapped ✓] = exists in CategoryMapper                  │
│          [Unmapped] = PrestaShop only, no PPM mapping           │
│                                                                  │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │ User Action: Load Shop TAB  │
         └─────────────┬───────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ LIVEWIRE COMPONENT (ProductForm)                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  public function getPrestaShopCategoryTree(int $shopId): array  │
│  {                                                               │
│      $service = app(PrestaShopCategoryService::class);          │
│      return $service->getCachedCategoryTree($shop);             │
│  }                                                               │
│                                                                  │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ PRESTASHOP CATEGORY SERVICE                                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  public function getCachedCategoryTree(PrestaShopShop $shop)    │
│  {                                                               │
│      $cacheKey = "prestashop_category_tree:{$shop->id}";        │
│                                                                  │
│      // Laravel flexible cache (stale-while-revalidate)         │
│      return Cache::flexible($cacheKey, [900, 1800], fn() =>     │
│          $this->fetchAndBuildTree($shop)                        │
│      );                                                          │
│  }                                                               │
│                                                                  │
└──────────────────────┬──────────────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼ Cache HIT                   ▼ Cache MISS
┌───────────────────┐         ┌───────────────────────────────────┐
│ Return Cached     │         │ fetchCategoriesFromShop()         │
│ Category Tree     │         ├───────────────────────────────────┤
│ (instant)         │         │                                    │
└───────────────────┘         │  1. Get PrestaShop API client      │
                              │     $client = factory->make($shop) │
                              │                                    │
                              │  2. Call /api/categories           │
                              │     GET /api/categories?display=full│
                              │                                    │
                              │  3. Parse XML response             │
                              │     <categories>                    │
                              │       <category>                    │
                              │         <id>9</id>                  │
                              │         <name>Root</name>           │
                              │         <id_parent>0</id_parent>    │
                              │       </category>                   │
                              │       ...                           │
                              │     </categories>                   │
                              │                                    │
                              │  4. Build hierarchical tree        │
                              │     buildCategoryTree($categories)  │
                              │                                    │
                              │  5. Add mapping status             │
                              │     addMappingStatus($tree, $shop)  │
                              │     → Calls CategoryMapper          │
                              │                                    │
                              │  6. Store in cache (TTL 15min)     │
                              │                                    │
                              │  7. Return tree                    │
                              │                                    │
                              └─────────────┬──────────────────────┘
                                            │
                                            ▼
                              ┌──────────────────────────────────┐
                              │ CACHE LAYER                      │
                              ├──────────────────────────────────┤
                              │ Key: prestashop_category_tree:1  │
                              │ TTL: 900s (15 minutes)           │
                              │ Value: [hierarchical tree array] │
                              └──────────────────────────────────┘
```

**Manual Refresh Flow:**

```
User clicks "Odśwież kategorie"
  ↓
refreshCategoriesFromShop()
  ↓
PrestaShopCategoryService::clearCache($shop)
  ↓
Cache::forget("prestashop_category_tree:{$shop->id}")
  ↓
PrestaShopCategoryService::syncCategoriesToCache($shop)
  ↓
fetchCategoriesFromShop() → API call → Cache → Success
  ↓
dispatch('shop-categories-reloaded') → Alpine.js tree refreshes
  ↓
dispatch('notify', 'Categories refreshed successfully')
```

---

## 3. IMPLEMENTATION BREAKDOWN

### Phase 1: PrestaShopCategoryService Core (4-5h)

**Task 1.1: Create Service File & Constructor**
- File: `app/Services/PrestaShop/PrestaShopCategoryService.php`
- Inject PrestaShopClientFactory
- Define constants (CACHE_TTL, CACHE_PREFIX)
- **Estimated:** 30min

**Task 1.2: Implement fetchCategoriesFromShop()**
- Call `/api/categories?display=full&filter[active]=1`
- Parse XML response to Collection
- Handle API errors (PrestaShopAPIException)
- Log request/response
- **Estimated:** 1-1.5h

**Task 1.3: Implement buildCategoryTree()**
- Convert flat collection to hierarchical tree
- Recursive algorithm: parent-child relationships
- Calculate level (depth in tree)
- Sort by position (PrestaShop order)
- **Estimated:** 1-1.5h

**Task 1.4: Implement getCachedCategoryTree()**
- Use Cache::flexible() (stale-while-revalidate)
- TTL: 900s primary, 1800s grace
- Call buildCategoryTree()
- Call addMappingStatus()
- **Estimated:** 1h

**Task 1.5: Implement Cache Management**
- clearCache() - invalidate single shop
- getCacheKey() - key generation
- syncCategoriesToCache() - force refresh
- **Estimated:** 30min

**Deliverable:** Fully functional PrestaShopCategoryService (85% complete)

---

### Phase 2: CategoryMapper Integration (1-1.5h)

**Task 2.1: Add getMappingStatus() to CategoryMapper**
- Query shop_mappings for PrestaShop ID
- Return PPM category ID + name
- Handle unmapped categories (return null)
- **Estimated:** 30min

**Task 2.2: Implement addMappingStatus() in Service**
- Call CategoryMapper::getMappingStatus()
- Add 'is_mapped' and 'ppm_mapping' to each node
- Recursive processing for children
- **Estimated:** 30min

**Task 2.3: Update CategoryMapper Cache Warming**
- Add shop_mappings observer
- Clear PrestaShopCategoryService cache when mapping changes
- **Estimated:** 30min

**Deliverable:** Full integration with CategoryMapper (95% complete)

---

### Phase 3: ProductForm UI Integration (2-2.5h)

**Task 3.1: Add Livewire Methods to ProductForm**
- refreshCategoriesFromShop() - manual refresh
- getPrestaShopCategoryTree() - get cached tree
- Handle errors, dispatch events
- **Estimated:** 45min

**Task 3.2: Update Blade Template**
- Add "Odśwież kategorie" button
- Wire:click + wire:loading spinner
- Call getPrestaShopCategoryTree()
- **Estimated:** 30min

**Task 3.3: Create/Update Alpine.js Component**
- categoryTreeComponent() - tree rendering
- Expand/collapse nodes
- Checkbox selection
- Display mapping status badges
- **Estimated:** 1-1.5h

**Deliverable:** Fully functional UI (100% complete)

---

### Phase 4: Testing & Debugging (1.5-2h)

**Task 4.1: Unit Tests**
- PrestaShopCategoryService::buildCategoryTree()
- Cache key generation
- Mapping status logic
- **Estimated:** 45min

**Task 4.2: Integration Tests**
- API call to Shop 1 (Pitbike.pl)
- API call to Shop 5 (Test KAYO)
- Cache hit/miss scenarios
- **Estimated:** 45min

**Task 4.3: Manual Testing**
- Load ProductForm with Shop 1
- Verify tree structure matches PrestaShop admin
- Click "Odśwież kategorie" → verify refresh
- Verify mapping status badges
- **Estimated:** 30min

**Deliverable:** Production-ready, tested service

---

**TOTAL ESTIMATED TIME:** 8-11 hours

---

## 4. RISK ASSESSMENT

### 4.1 Performance Risks

**Risk P1: Large Category Trees (>1000 categories)**

**Impact:** HIGH
**Probability:** MEDIUM

**Scenario:** Shop with extensive category hierarchy causes:
- API response > 5MB
- Cache storage > 10MB
- Tree building timeout (>30s)

**Mitigation:**
1. API pagination: Fetch categories in batches (limit=100)
2. Lazy loading: Load children on-demand (expand node triggers API call)
3. Cache compression: gzcompress() before storage
4. Tree simplification: Only cache essential fields (id, name, parent_id)

**Monitoring:**
```php
Log::info('Category tree built', [
    'shop_id' => $shop->id,
    'category_count' => $categories->count(),
    'build_time_ms' => $buildTime,
    'cache_size_kb' => strlen(serialize($tree)) / 1024,
]);
```

---

**Risk P2: Cache Storage Limits (Database Cache)**

**Impact:** MEDIUM
**Probability:** LOW

**Scenario:** Database cache table grows too large:
- Hostido shared hosting: Limited storage
- Multiple shops: 10 shops x 2MB tree = 20MB
- TTL expiration: Old entries not cleaned

**Mitigation:**
1. Laravel cache cleanup: `php artisan cache:prune-stale-tags`
2. Aggressive TTL: 15min (auto-cleanup)
3. Monitor cache table size:
   ```sql
   SELECT COUNT(*), SUM(LENGTH(value)) FROM cache;
   ```
4. Fallback: If cache fails, return empty array (graceful degradation)

---

### 4.2 Compatibility Risks

**Risk C1: PrestaShop 8.x vs 9.x API Differences**

**Impact:** HIGH
**Probability:** MEDIUM

**Scenario:** Category API response differs between versions:
- Field names (id_parent vs parent_id)
- XML structure (nested vs flat)
- Language handling (multi-lang fields)

**Mitigation:**
1. Use BasePrestaShopClient abstraction
2. Test against both PrestaShop 8.x (Shop 1) and 9.x (Shop 5)
3. Normalize response in fetchCategoriesFromShop():
   ```php
   $normalized = [
       'id' => $raw['id'] ?? $raw['category_id'],
       'parent_id' => $raw['id_parent'] ?? $raw['parent_id'] ?? 0,
       'name' => $this->extractLanguageName($raw['name']),
   ];
   ```

**Testing:**
- Shop 1 (Pitbike.pl): PrestaShop 8.x → Verify tree
- Shop 5 (Test KAYO): PrestaShop 9.x → Verify tree
- Compare structures → Document differences

---

**Risk C2: Backward Compatibility with CategoryMapper**

**Impact:** CRITICAL
**Probability:** LOW

**Scenario:** New service breaks existing category mapping system:
- product_categories pivot still uses PPM IDs
- CategoryMapper::mapToPrestaShop() fails
- Product sync jobs break

**Mitigation:**
1. DO NOT modify CategoryMapper core logic
2. ONLY ADD getMappingStatus() method (new, non-breaking)
3. product_categories pivot UNCHANGED (still PPM IDs)
4. PrestaShopCategoryService is READ-ONLY (no writes to mappings)

**Validation:**
- Run existing sync jobs after FAZA 1 → must work
- No changes to product_categories table structure
- CategoryMapper tests still pass

---

### 4.3 Error Handling Risks

**Risk E1: PrestaShop API Unavailable**

**Impact:** HIGH
**Probability:** LOW

**Scenario:** PrestaShop API down/slow:
- Network timeout
- API key invalid
- PrestaShop server error (500)

**Mitigation:**
1. BasePrestaShopClient retry logic (3 attempts, exponential backoff)
2. Catch PrestaShopAPIException:
   ```php
   try {
       $categories = $this->fetchCategoriesFromShop($shop);
   } catch (PrestaShopAPIException $e) {
       Log::error('PrestaShop API error', [
           'shop_id' => $shop->id,
           'error' => $e->getMessage(),
       ]);

       // Return cached data if available (stale is better than nothing)
       return Cache::get($this->getCacheKey($shop->id), []);
   }
   ```
3. UI error message: "Failed to load categories. Using cached data."

---

**Risk E2: Malformed API Response**

**Impact:** MEDIUM
**Probability:** LOW

**Scenario:** PrestaShop returns invalid XML:
- Incomplete category data
- Missing required fields (id, name)
- Circular parent references

**Mitigation:**
1. Validate response structure:
   ```php
   if (!isset($category['id'], $category['name'])) {
       Log::warning('Invalid category data', ['category' => $category]);
       continue; // Skip invalid category
   }
   ```
2. Circular reference detection:
   ```php
   private function buildCategoryTree(Collection $categories, int $maxDepth = 10): array
   {
       // Prevent infinite loops
       if ($maxDepth <= 0) {
           Log::warning('Max depth reached - circular reference?');
           return [];
       }
       // ...
   }
   ```

---

## 5. TESTING STRATEGY

### 5.1 Unit Tests

**Test Suite:** `tests/Unit/Services/PrestaShop/PrestaShopCategoryServiceTest.php`

**Coverage:**

```php
class PrestaShopCategoryServiceTest extends TestCase
{
    /**
     * Test buildCategoryTree() with flat collection
     */
    public function test_build_category_tree_simple(): void
    {
        $categories = collect([
            ['id' => 1, 'parent_id' => 0, 'name' => 'Root'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Child 1'],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Child 2'],
        ]);

        $tree = $this->service->buildCategoryTree($categories);

        $this->assertCount(1, $tree); // One root
        $this->assertCount(2, $tree[0]['children']); // Two children
    }

    /**
     * Test buildCategoryTree() with nested hierarchy
     */
    public function test_build_category_tree_nested(): void
    {
        // Test 3 levels deep
    }

    /**
     * Test cache key generation
     */
    public function test_get_cache_key(): void
    {
        $key = $this->invokePrivateMethod('getCacheKey', [123]);

        $this->assertEquals('prestashop_category_tree:123', $key);
    }

    /**
     * Test addMappingStatus() integration
     */
    public function test_add_mapping_status(): void
    {
        // Mock CategoryMapper
        // Verify is_mapped and ppm_mapping added
    }
}
```

**Estimated:** 45min (5-6 test cases)

---

### 5.2 Integration Tests

**Test Suite:** `tests/Feature/Services/PrestaShop/PrestaShopCategoryServiceIntegrationTest.php`

**Coverage:**

```php
class PrestaShopCategoryServiceIntegrationTest extends TestCase
{
    /**
     * Test API call to real PrestaShop (Shop 5 - Test KAYO)
     *
     * @group integration
     * @group external-api
     */
    public function test_fetch_categories_from_shop_real_api(): void
    {
        $shop = PrestaShopShop::find(5); // Test KAYO

        $categories = $this->service->fetchCategoriesFromShop($shop);

        $this->assertInstanceOf(Collection::class, $categories);
        $this->assertGreaterThan(0, $categories->count());
    }

    /**
     * Test cache hit scenario
     */
    public function test_get_cached_category_tree_cache_hit(): void
    {
        // Pre-populate cache
        Cache::put('prestashop_category_tree:1', ['mock_tree'], 900);

        // Call service
        $tree = $this->service->getCachedCategoryTree($shop);

        // Verify cache was used (no API call)
        $this->assertEquals(['mock_tree'], $tree);
    }

    /**
     * Test cache miss scenario
     */
    public function test_get_cached_category_tree_cache_miss(): void
    {
        // Clear cache
        Cache::forget('prestashop_category_tree:1');

        // Mock API client to avoid real call
        // ...

        // Call service → should trigger API call
        $tree = $this->service->getCachedCategoryTree($shop);

        // Verify cache was populated
        $this->assertTrue(Cache::has('prestashop_category_tree:1'));
    }
}
```

**Estimated:** 45min (4-5 test cases)

---

### 5.3 Manual Testing Checklist

**Tester:** prestashop-api-expert (after implementation)

**Test Environment:** Production (ppm.mpptrade.pl)

**Test Cases:**

1. **Load ProductForm with Shop 1 (Pitbike.pl)**
   - Navigate to Products → Edit Product → Shop TAB
   - Select Shop 1 from dropdown
   - ✅ Verify category tree displays PrestaShop categories
   - ✅ Verify tree structure matches PrestaShop admin panel
   - ✅ Verify mapping status badges (green = mapped, gray = unmapped)

2. **Manual Refresh (Shop 1)**
   - Click "Odśwież kategorie" button
   - ✅ Verify spinner appears
   - ✅ Verify tree refreshes (new categories if added in PrestaShop)
   - ✅ Verify success notification

3. **Load ProductForm with Shop 5 (Test KAYO)**
   - Repeat test case 1 for Shop 5
   - ✅ Verify compatibility with PrestaShop 9.x

4. **Cache Expiration**
   - Wait 15 minutes
   - Reload ProductForm → Shop TAB
   - ✅ Verify tree loads (from cache or fresh API call)

5. **Error Handling**
   - Temporarily invalidate PrestaShop API key in database
   - Load ProductForm → Shop TAB
   - ✅ Verify error message appears
   - ✅ Verify no crash (graceful degradation)

6. **Performance**
   - Load ProductForm with Shop 1 (large tree: ~200 categories)
   - ✅ Verify load time < 2s (cache hit)
   - Clear cache → reload
   - ✅ Verify load time < 5s (cache miss + API call)

**Estimated:** 30min

---

## 6. FILE STRUCTURE PROPOSAL

```
app/Services/PrestaShop/
├── BasePrestaShopClient.php         (EXISTING - no changes)
├── PrestaShop8Client.php            (EXISTING - no changes)
├── PrestaShop9Client.php            (EXISTING - no changes)
├── PrestaShopClientFactory.php      (EXISTING - no changes)
├── CategoryMapper.php               (EXISTING - ADD getMappingStatus())
├── PrestaShopCategoryService.php    (NEW - FAZA 1 main deliverable)
└── ... other existing services ...
```

**NEW File Details:**

**File:** `PrestaShopCategoryService.php`
- **Lines:** ~250 (within 300-line limit)
- **Dependencies:** BasePrestaShopClient, CategoryMapper, Cache, Log
- **Public Methods:** 6
- **Private Methods:** 3
- **Test Coverage:** 90%+ (unit + integration)

**MODIFIED File:**

**File:** `CategoryMapper.php`
- **Change:** ADD 1 new method: `getMappingStatus()`
- **Lines Added:** ~20
- **Impact:** Non-breaking (new method only)

**NEW Blade Component (Optional - FAZA 4):**

```
resources/views/components/
└── category-tree.blade.php          (FUTURE - FAZA 4 UI enhancement)
```

---

## 7. COMPATIBILITY MATRIX

| Component | PrestaShop 8.x | PrestaShop 9.x | Status |
|-----------|:-------------:|:-------------:|--------|
| fetchCategoriesFromShop() | ✅ | ✅ | API endpoint same |
| buildCategoryTree() | ✅ | ✅ | XML structure compatible |
| Cache layer | ✅ | ✅ | Agnostic |
| CategoryMapper integration | ✅ | ✅ | Model-based |
| UI (ProductForm) | ✅ | ✅ | Frontend agnostic |

**Test Coverage:**
- Shop 1 (Pitbike.pl): PrestaShop 8.x ✅
- Shop 5 (Test KAYO): PrestaShop 9.x ✅

---

## 8. DEPLOYMENT PLAN

### Step 1: Code Review (30min)
- Architect reviews implementation by prestashop-api-expert
- Check compliance with CLAUDE.md (file size, separation of concerns)
- Verify Context7 patterns used

### Step 2: Local Testing (1h)
- Run unit tests: `php artisan test --filter=PrestaShopCategoryService`
- Run integration tests: `php artisan test --filter=PrestaShopCategoryServiceIntegration --group=external-api`
- Manual test with Shop 5 (local dev)

### Step 3: Production Deployment (45min)
- Upload PrestaShopCategoryService.php via SSH
- Upload modified CategoryMapper.php
- Upload ProductForm.php changes
- Upload Blade template changes
- Clear cache: `php artisan cache:clear && view:clear`

### Step 4: Production Verification (30min)
- Manual testing checklist (see 5.3)
- Monitor logs: `tail -f storage/logs/laravel.log`
- Screenshot: `node _TOOLS/full_console_test.cjs` (mandatory per CLAUDE.md)

**Total Deployment Time:** 3h 15min

---

## 9. NEXT STEPS FOR PRESTASHOP-API-EXPERT

**Handoff Checklist:**

1. ✅ Review this planning report
2. ✅ Ask questions/clarifications if needed
3. ✅ User approval for implementation
4. 🔧 Implement Phase 1: PrestaShopCategoryService Core (4-5h)
5. 🔧 Implement Phase 2: CategoryMapper Integration (1-1.5h)
6. 🔧 Implement Phase 3: ProductForm UI Integration (2-2.5h)
7. 🔧 Implement Phase 4: Testing & Debugging (1.5-2h)
8. 📝 Create implementation report: `prestashop_api_expert_etap07b_faza1_implementation_2025-11-19_REPORT.md`

**Files to Create/Modify:**

**NEW:**
- `app/Services/PrestaShop/PrestaShopCategoryService.php` (~250 lines)

**MODIFIED:**
- `app/Services/PrestaShop/CategoryMapper.php` (ADD getMappingStatus(), ~20 lines)
- `app/Http/Livewire/Products/Management/ProductForm.php` (ADD 2 methods, ~40 lines)
- `resources/views/livewire/products/management/product-form.blade.php` (ADD refresh button, ~30 lines)

**TESTS:**
- `tests/Unit/Services/PrestaShop/PrestaShopCategoryServiceTest.php` (NEW)
- `tests/Feature/Services/PrestaShop/PrestaShopCategoryServiceIntegrationTest.php` (NEW)

**Code Examples:** See Sections 1.2, 1.3, 5.1, 5.2 for detailed implementation patterns

**Context7 References:**
- Laravel 12.x cache: Cache::flexible(), Cache::remember()
- PrestaShop API: GET /api/categories?display=full

**Blockers:** NONE (all dependencies exist, no external approvals needed after user confirms)

---

## 10. SUCCESS CRITERIA

**FAZA 1 is COMPLETE when:**

✅ PrestaShopCategoryService fetches categories from PrestaShop API
✅ Categories are cached with 15min TTL
✅ ProductForm Shop TAB shows PrestaShop categories (not PPM)
✅ "Odśwież kategorie" button works (invalidates cache)
✅ Mapping status badges display correctly (mapped vs unmapped)
✅ Compatible with PrestaShop 8.x AND 9.x
✅ Unit tests pass (90%+ coverage)
✅ Integration tests pass (Shop 1 & Shop 5)
✅ Manual testing checklist complete
✅ Production deployment successful
✅ Screenshot verification (PPM Verification Tool)

**User Acceptance Test:**

1. User opens ProductForm → Shop TAB
2. Selects Shop 1 (Pitbike.pl)
3. Sees PrestaShop category tree (not PPM categories)
4. Clicks "Odśwież kategorie"
5. Tree refreshes with latest PrestaShop data
6. User says: "Działa idealnie!" ✅

---

## 11. ESTIMATED EFFORT BREAKDOWN

| Phase | Task | Estimated | Assignee |
|-------|------|-----------|----------|
| Planning | This report | 3h | architect ✅ |
| Phase 1 | Service Core | 4-5h | prestashop-api-expert |
| Phase 2 | CategoryMapper | 1-1.5h | prestashop-api-expert |
| Phase 3 | ProductForm UI | 2-2.5h | prestashop-api-expert + frontend-specialist |
| Phase 4 | Testing | 1.5-2h | prestashop-api-expert |
| Deployment | Production deploy | 3h | deployment-specialist |
| **TOTAL** | | **15-18h** | (including planning) |

**Implementation Only:** 8-11h (excludes planning & deployment)

---

## 12. APPENDIX

### A. PrestaShop Category API Response Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <categories>
    <category>
      <id>9</id>
      <id_parent>0</id_parent>
      <active>1</active>
      <position>0</position>
      <name>
        <language id="1"><![CDATA[Root Category]]></language>
      </name>
      <link_rewrite>
        <language id="1"><![CDATA[root-category]]></language>
      </link_rewrite>
    </category>
    <category>
      <id>15</id>
      <id_parent>9</id_parent>
      <active>1</active>
      <position>1</position>
      <name>
        <language id="1"><![CDATA[Vehicles]]></language>
      </name>
      <link_rewrite>
        <language id="1"><![CDATA[vehicles]]></language>
      </link_rewrite>
    </category>
    <category>
      <id>800</id>
      <id_parent>15</id_parent>
      <active>1</active>
      <position>0</position>
      <name>
        <language id="1"><![CDATA[Buggy]]></language>
      </name>
      <link_rewrite>
        <language id="1"><![CDATA[buggy]]></language>
      </link_rewrite>
    </category>
  </categories>
</prestashop>
```

### B. Cached Tree Structure Example

```json
[
  {
    "id": 9,
    "name": "Root Category",
    "parent_id": 0,
    "level": 0,
    "is_active": true,
    "is_mapped": true,
    "ppm_mapping": {
      "ppm_id": 1,
      "ppm_name": "Root"
    },
    "children": [
      {
        "id": 15,
        "name": "Vehicles",
        "parent_id": 9,
        "level": 1,
        "is_active": true,
        "is_mapped": true,
        "ppm_mapping": {
          "ppm_id": 2,
          "ppm_name": "Pojazdy"
        },
        "children": [
          {
            "id": 800,
            "name": "Buggy",
            "parent_id": 15,
            "level": 2,
            "is_active": true,
            "is_mapped": false,
            "ppm_mapping": null,
            "children": []
          }
        ]
      }
    ]
  }
]
```

### C. Laravel Cache Flexible Example (Context7)

```php
// Stale-while-revalidate pattern
$value = Cache::flexible('users', [5, 10], function () {
    return DB::table('users')->get();
});

// Applied to PrestaShopCategoryService:
public function getCachedCategoryTree(PrestaShopShop $shop): array
{
    $cacheKey = $this->getCacheKey($shop->id);

    return Cache::flexible($cacheKey, [900, 1800], function () use ($shop) {
        // This closure runs on cache miss OR expiration
        $categories = $this->fetchCategoriesFromShop($shop);
        $tree = $this->buildCategoryTree($categories);
        return $this->addMappingStatus($tree, $shop);
    });
}
```

**Benefits:**
- First request after expiration: Returns stale cache (instant), triggers background refresh
- Subsequent requests: Get fresh data
- No user waiting for API call

---

## 13. SIGN-OFF

**Architect:** ✅ Planning complete (2025-11-19)
**User Approval:** ⏳ Awaiting confirmation to proceed with implementation
**Next Agent:** prestashop-api-expert (ready to implement)

**Questions for User:**

1. ✅ Approve FAZA 1 implementation as designed?
2. ✅ Proceed with prestashop-api-expert implementation (8-11h)?
3. ⏳ Any specific requirements for UI (category tree display)?

**Once approved, prestashop-api-expert will begin implementation immediately.**

---

**END OF PLANNING REPORT**
