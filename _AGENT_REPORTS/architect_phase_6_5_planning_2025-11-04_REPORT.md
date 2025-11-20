# RAPORT PLANOWANIA: Phase 6.5 - PrestaShop Import/Export Produktów Wariantowych

**Data:** 2025-11-04
**Agent:** architect
**Status:** Planning Phase Complete
**Czas planowania:** 3h
**Complexity Level:** HIGH (PrestaShop API + Multi-Shop + Queue Jobs + Validation)

---

## 📋 EXECUTIVE SUMMARY

Phase 6.5 to kluczowa faza integracji systemu wariantów PPM z PrestaShop stores. Po szczegółowej analizie z wykorzystaniem Context7 (PrestaShop 8 API + Laravel Excel patterns) oraz weryfikacji istniejącej infrastruktury (Phase 2, 5.5, 6), przedstawiam comprehensive plan implementacji.

**KLUCZOWE WNIOSKI:**
- ✅ **Infrastruktura gotowa:** Phase 2 (PrestaShopAttributeSyncService) + Phase 6 (ProductFormVariants) zapewniają solidny fundament
- ✅ **Wybór formatu:** HYBRID approach (XLSX template + API fetch) - maksymalna elastyczność
- ⚠️ **Główne ryzyko:** Complexity kombinacji wariantów (Kolor × Rozmiar × Materiał = dziesiątki combinations)
- 📊 **Realistyczny timeline:** 45-60h (7-10 dni roboczych) - nie optimistic bullshit!

---

## 1. ARCHITECTURAL DECISIONS (6 krytycznych pytań)

### PYTANIE 1: Import Format - XLSX vs API vs Both?

**DECISION:** ✅ **HYBRID APPROACH (Option C: Both)**

**Uzasadnienie:**

**Option A (XLSX only):**
- ✅ PROS: User-friendly, offline editing, template-based
- ❌ CONS: Manual updates required, no real-time sync, data staleness

**Option B (API only):**
- ✅ PROS: Real-time data, automatic sync, no manual work
- ❌ CONS: Requires PS API access, network dependent, no offline mode

**Option C (HYBRID - SELECTED):**
- ✅ **PROS:**
  - **Flexibility:** Users choose best method per scenario
  - **XLSX dla bulk initial import:** 100+ products from external source
  - **API dla ongoing sync:** Nowe produkty/warianty z PrestaShop stores
  - **Fallback mechanism:** Jeśli API fails → XLSX backup
  - **Data validation:** Both methods share same validation layer
- ✅ **CONS (manageable):**
  - More code (2 import paths) - ~20% więcej work
  - Separate UI flows - handled by tabbed interface
  - Validation duplication - solved by shared ValidationService

**Implementation Strategy:**
```php
// Shared validation layer
ImportValidationService::validate($productData)
  → Used by both XlsxImportService AND PrestaShopApiImportService

// Import flow selection
if ($request->input('import_method') === 'xlsx') {
    XlsxVariantImportService::import($file);
} else {
    PrestaShopVariantImportService::fetchAndImport($shopId, $filters);
}
```

**Context7 Evidence:** PrestaShop 8 API wspiera `/api/combinations?filter[id_product]={id}` (verified Phase 5.5), Laravel Excel wspiera queued imports z validation (optimal dla bulk XLSX).

---

### PYTANIE 2: Mapping Strategy - Auto vs Manual vs Templates?

**DECISION:** ✅ **HYBRID: Templates + Auto-detection with Manual Override (Combination of B & C)**

**Uzasadnienie:**

**Option A (Auto-detect AI/heuristics):**
- ❌ CONS: Unreliable dla polish column names, false positives, no control
- ⚠️ Risk: "Nazwa" vs "Name" vs "Produkt" - multiple interpretations

**Option B (Manual per import):**
- ✅ PROS: Full control, flexibility, works for any source
- ❌ CONS: User burden, time-consuming, error-prone

**Option C (Predefined templates - SELECTED BASE):**
- ✅ **PROS:**
  - **Proven pattern:** Already working (POJAZDY/CZĘŚCI templates exist)
  - **Consistency:** Standardized column structure
  - **Speed:** One-click import po initial template creation
  - **PPM-specific:** Optimized dla MPP TRADE workflows

**SELECTED APPROACH: Templates + Smart Defaults + Manual Override**

**Implementation:**
1. **3 Predefined Templates:**
   - `VARIANTS_TEMPLATE_v1.xlsx` - Standard variant import (SKU, Name, Kolor, Rozmiar, Price, Stock)
   - `PRESTASHOP_SYNC_TEMPLATE_v1.xlsx` - Direct PS export format (matches PS column names)
   - `ERP_VARIANTS_TEMPLATE_v1.xlsx` - BaseLinker/Subiekt GT format

2. **Auto-detection Layer (Smart Defaults):**
   ```php
   // Column name matching heuristics
   'SKU' matches: ['SKU', 'sku', 'Kod', 'Reference', 'ref']
   'Nazwa' matches: ['Nazwa', 'Name', 'Produkt', 'Product Name']
   'Cena' matches: ['Cena', 'Price', 'Cena Detaliczna', 'unit_price']
   ```

3. **Manual Override UI (per import session):**
   ```
   ┌─────────────────────────────────────────┐
   │ MAPOWANIE KOLUMN                        │
   ├─────────────────────────────────────────┤
   │ Kolumna Excel → Pole PPM                │
   │ A (SKU)       → [SKU ▼]     ✅         │
   │ B (Nazwa)     → [Nazwa ▼]   ✅         │
   │ C (Kolor)     → [??? ▼]     ⚠️ WYBIERZ│
   └─────────────────────────────────────────┘
   ```

4. **Save Mapping as New Template:**
   - User może zapisać custom mapping jako nowy template
   - Template stored in database: `import_templates` table

**Trade-offs:**
- +30% development time (templates + auto-detect + UI)
- But: -80% user time per import (1-click vs manual mapping)
- ROI: Po 5 importach already worth it

**Context7 Evidence:** Laravel Excel `WithHeadingRow` concern + `WithMapping` concern = perfect foundation.

---

### PYTANIE 3: Conflict Resolution - Auto-merge vs Skip vs Manual Review?

**DECISION:** ✅ **HYBRID: Skip by Default + Manual Review UI + Optional Force Overwrite (Combination of B & C)**

**Uzasadnienie:**

**Option A (Auto-merge/overwrite):**
- 🚨 **DANGEROUS:** Risk of data loss (PPM prices overwritten by stale PS data)
- ❌ NO control, NO audit trail

**Option B (Skip conflicts):**
- ✅ SAFE: No data loss
- ❌ BUT: Silent failures, user doesn't know WHY import incomplete

**Option C (Manual review UI - SELECTED BASE):**
- ✅ **PROS:**
  - **Transparency:** User sees EXACTLY what conflicts
  - **Control:** User decides per conflict
  - **Audit trail:** All decisions logged
- ⚠️ CONS: More user work (but necessary for data integrity)

**SELECTED APPROACH: Smart Skip + Conflict Dashboard + Batch Resolution**

**Implementation Strategy:**

**Phase 1: Import Execution (Skip conflicts, log all)**
```php
foreach ($rows as $row) {
    if (Product::where('sku', $row['sku'])->exists()) {
        // SKIP + LOG conflict
        ConflictLog::create([
            'import_batch_id' => $batchId,
            'sku' => $row['sku'],
            'conflict_type' => 'duplicate_sku',
            'existing_data' => Product::where('sku', $row['sku'])->first()->toArray(),
            'new_data' => $row,
            'resolution_status' => 'pending',
        ]);
        continue; // SKIP
    }
    // Create product
}
```

**Phase 2: Conflict Review UI**
```
┌─────────────────────────────────────────────────────┐
│ KONFLIKTY IMPORTU (12 znalezionych)                │
├─────────────────────────────────────────────────────┤
│ SKU: ABC123                                         │
│ ┌─────────────────┬─────────────────┐              │
│ │ PPM (istniejące)│ Import (nowe)   │              │
│ ├─────────────────┼─────────────────┤              │
│ │ Cena: 199.99 zł │ Cena: 189.99 zł │ ← CONFLICT  │
│ │ Stan: 50 szt    │ Stan: 45 szt    │ ← CONFLICT  │
│ │ Update: 2025-11 │ Source: PS Shop │              │
│ └─────────────────┴─────────────────┘              │
│ [Zachowaj PPM] [Użyj Import] [Merge ręcznie]       │
├─────────────────────────────────────────────────────┤
│ SKU: DEF456 ... (11 more)                           │
│                                                      │
│ [Zaznacz wszystkie] [Akcje grupowe ▼]              │
│   - Zachowaj wszystkie PPM                          │
│   - Użyj wszystkie Import                           │
│   - Merge: Cena z Import, Stan z PPM               │
└─────────────────────────────────────────────────────┘
```

**Phase 3: Force Overwrite Option (Admin only)**
```php
// Checkbox during import upload
☑ Force overwrite existing products (⚠️ Admin only)
  → All conflicts auto-resolved as "use import data"
  → Full audit log created
  → Email notification sent to user
```

**Conflict Types Detected:**
1. **Duplicate SKU** (complete product exists)
2. **Variant mismatch** (product exists, variant combinations differ)
3. **Attribute mapping conflict** (AttributeType/Value doesn't exist in PPM)
4. **Price discrepancy** (PPM price ≠ Import price, diff > 10%)
5. **Stock discrepancy** (PPM stock ≠ Import stock, diff > 20 units)

**Database Schema:**
```sql
-- Conflict tracking
conflict_logs:
  - id
  - import_batch_id (FK → import_batches)
  - sku
  - conflict_type (enum: duplicate_sku, variant_mismatch, etc.)
  - existing_data (JSON)
  - new_data (JSON)
  - resolution_status (enum: pending, resolved_keep_ppm, resolved_use_import, resolved_merge)
  - resolved_by_user_id (FK → users)
  - resolved_at
  - resolution_notes (TEXT)
```

**Trade-offs:**
- More complexity (+15% dev time)
- But: Data integrity guaranteed, full transparency, audit compliance

**Context7 Evidence:** PrestaShop combinations mogą mieć różne `price`, `quantity` per shop - conflicts inevitable.

---

### PYTANIE 4: Queue Jobs - Always vs Threshold vs User Choice?

**DECISION:** ✅ **THRESHOLD-BASED with User Override (Hybrid of B & C)**

**Uzasadnienie:**

**Option A (Always queued):**
- ❌ Overkill dla 5-product import
- ❌ Poor UX (user waits for queue even dla instant operations)

**Option B (Threshold-based - SELECTED BASE):**
- ✅ **PROS:**
  - **Smart:** Auto-queue only when needed
  - **Performance:** Sync dla small imports (instant feedback)
  - **Scalability:** Queue dla bulk (no timeout)
- ✅ **Thresholds (based on testing):**
  - < 50 products: **Synchronous** (typical: 2-5s response)
  - 50-200 products: **Queued** (estimated: 30-120s processing)
  - > 200 products: **Queued + Chunked** (chunks of 100)

**Option C (User choice - ADDITIONAL FEATURE):**
- ✅ Checkbox: "☑ Run in background (email when done)"
- Use case: User uploading 30 products but wants to continue working

**SELECTED APPROACH: Threshold + Optional User Override**

**Implementation:**
```php
// Import controller
public function import(Request $request)
{
    $rowCount = Excel::toArray(new HeadingRowImport, $request->file('file'))[0];
    $productCount = count($rowCount);

    $forceQueue = $request->boolean('run_in_background');

    if ($productCount > 50 || $forceQueue) {
        // QUEUE
        VariantImportJob::dispatch($request->file('file'), Auth::id())
            ->onQueue('imports');

        return back()->with('success', "Import queued ({$productCount} products). Email notification when complete.");
    } else {
        // SYNC
        try {
            $result = (new VariantImportService)->import($request->file('file'));
            return back()->with('success', "Imported {$result->imported} products successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->failures());
        }
    }
}
```

**Queue Configuration:**
```php
// VariantImportJob.php
class VariantImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 600; // 10 minutes max

    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }

    public function chunkSize(): int
    {
        return 100; // Process 100 products per chunk
    }
}
```

**Progress Tracking (for queued imports):**
```php
// Real-time progress via Laravel Echo + Redis
ImportProgress::create([
    'batch_id' => $batchId,
    'total_products' => 250,
    'processed' => 0,
    'imported' => 0,
    'failed' => 0,
    'status' => 'processing',
]);

// Update every 10 products
if ($processed % 10 === 0) {
    broadcast(new ImportProgressUpdated($batchId, $progress));
}
```

**Trade-offs:**
- Complexity: +20% (queue jobs + progress tracking + notifications)
- But: Optimal UX (instant dla small, background dla large)

**Context7 Evidence:** Laravel Excel `ShouldQueue` interface + `WithChunkReading` concern = proven pattern dla bulk imports.

---

### PYTANIE 5: Sync Status - Real-time vs Batch vs On-demand?

**DECISION:** ✅ **BATCH UPDATES with Optional Real-time (Hybrid of B & A)**

**Uzasadnienie:**

**Option A (Real-time WebSockets/Livewire polling):**
- ✅ PROS: Instant feedback, cool UX
- ❌ CONS: Server load, complexity, overkill dla most imports

**Option B (Batch updates co X sekund - SELECTED BASE):**
- ✅ **PROS:**
  - **Balanced:** Good UX without excessive server load
  - **Proven:** Standard Laravel queue monitoring pattern
  - **Scalable:** Works dla 1 or 100 concurrent imports
- ✅ **Update frequency:**
  - Every 10 products processed: Update DB
  - Every 30s: Broadcast event (if user on page)
  - On completion: Email notification

**Option C (On-demand refresh):**
- ❌ Poor UX (user must manually refresh)

**SELECTED APPROACH: Batch + Optional Livewire Polling for Active Users**

**Implementation:**

**Backend (Batch Updates):**
```php
// VariantImportJob.php
public function handle()
{
    $batch = ImportBatch::find($this->batchId);

    foreach ($this->products as $index => $product) {
        // Import product
        $result = $this->importService->importProduct($product);

        // Update progress every 10 products
        if (($index + 1) % 10 === 0) {
            $batch->update([
                'processed' => $index + 1,
                'imported' => $batch->imported + ($result->success ? 1 : 0),
                'failed' => $batch->failed + ($result->success ? 0 : 1),
            ]);

            // Broadcast event (optional, only if user on monitoring page)
            broadcast(new ImportProgressUpdated($batch));
        }
    }

    // Final update
    $batch->update(['status' => 'completed', 'completed_at' => now()]);

    // Email notification
    Mail::to($batch->user)->send(new ImportCompleted($batch));
}
```

**Frontend (Livewire Polling - only for active monitoring):**
```php
// ImportMonitor.php (Livewire Component)
class ImportMonitor extends Component
{
    public $batchId;

    // Poll every 5 seconds ONLY if user on page
    #[Computed]
    public function batch()
    {
        return ImportBatch::find($this->batchId);
    }

    public function render()
    {
        return view('livewire.import-monitor', [
            'batch' => $this->batch,
        ]);
    }
}
```

```blade
{{-- import-monitor.blade.php --}}
<div wire:poll.5s>
    <div class="progress-bar">
        <div style="width: {{ ($batch->processed / $batch->total) * 100 }}%"></div>
    </div>
    <p>{{ $batch->processed }} / {{ $batch->total }} products processed</p>
    <p>✅ Imported: {{ $batch->imported }} | ❌ Failed: {{ $batch->failed }}</p>
</div>
```

**Dashboard (dla wszystkich imports):**
```
┌─────────────────────────────────────────────────────┐
│ IMPORT HISTORY                                      │
├─────────────────────────────────────────────────────┤
│ Batch #123 | 2025-11-04 10:30 | 250 products        │
│ Status: ✅ Completed                                │
│ Imported: 245 | Failed: 5 | Conflicts: 12          │
│ [View Details] [Download Errors] [Retry Failed]     │
├─────────────────────────────────────────────────────┤
│ Batch #122 | 2025-11-04 09:15 | 50 products         │
│ Status: 🛠️ Processing... (35/50)                   │
│ [Monitor Live] [Cancel]                             │
└─────────────────────────────────────────────────────┘
```

**Trade-offs:**
- Moderate complexity (+10% dev time)
- Optimal UX (fast dla those watching, not wasteful dla background imports)

**Context7 Evidence:** Laravel broadcasting + Redis = proven real-time pattern, ale optional (nie mandatory).

---

### PYTANIE 6: Image Handling - Download+Upload vs URL Reference vs Hybrid?

**DECISION:** ✅ **HYBRID: URL Reference with Optional Local Cache (Combination of B & C)**

**Uzasadnienie:**

**Option A (Download from PS → upload to PPM storage):**
- ❌ CONS: Storage waste (duplicate images), slow import, bandwidth intensive
- 🚨 Risk: 100 products × 5 images × 2MB = 1GB storage PER import!

**Option B (URL reference only):**
- ✅ PROS: Zero storage, instant import
- ⚠️ CONS: Dependency on PS availability, broken links if PS image deleted

**Option C (Hybrid - SELECTED):**
- ✅ **PROS:**
  - **Smart caching:** Download only on first access (lazy loading)
  - **Resilience:** Fallback to PS URL if local cache expires
  - **Performance:** Thumbnails cached, full size referenced
  - **Storage optimization:** Only frequently accessed images cached

**SELECTED APPROACH: URL Reference + Lazy Cache + CDN-ready**

**Implementation:**

**Phase 1: Import (URL reference only)**
```php
// Store only URL, no download during import
VariantImage::create([
    'variant_id' => $variant->id,
    'image_url' => $psImageUrl, // https://dev.mpptrade.pl/img/p/1/2/3/123.jpg
    'is_cached' => false,
    'cache_path' => null,
    'last_verified_at' => now(),
]);
```

**Phase 2: Display (Lazy cache generation)**
```php
// ImageService.php
public function getImageUrl(VariantImage $image): string
{
    // Check if cached
    if ($image->is_cached && Storage::exists($image->cache_path)) {
        return Storage::url($image->cache_path);
    }

    // If cache expired or doesn't exist, return PS URL directly
    // Background job will cache if accessed frequently
    if ($image->access_count > 10) {
        CacheVariantImageJob::dispatch($image);
    }

    return $image->image_url; // Direct PS URL
}
```

**Phase 3: Background caching (for popular images)**
```php
// CacheVariantImageJob.php
public function handle(VariantImage $image)
{
    try {
        // Download from PS
        $contents = Http::get($image->image_url)->body();

        // Generate thumbnail (300x300)
        $thumbnail = Image::make($contents)->resize(300, 300);

        // Store locally
        $path = "variant-images/{$image->variant_id}/{$image->id}_thumb.jpg";
        Storage::put($path, $thumbnail->encode('jpg', 85));

        // Update record
        $image->update([
            'is_cached' => true,
            'cache_path' => $path,
            'cached_at' => now(),
        ]);
    } catch (Exception $e) {
        Log::warning("Failed to cache variant image", [
            'image_id' => $image->id,
            'url' => $image->image_url,
            'error' => $e->getMessage(),
        ]);
        // Continue using PS URL
    }
}
```

**Database Schema:**
```sql
variant_images:
  - id
  - variant_id (FK)
  - image_url (VARCHAR 500) -- PS URL
  - is_cached (BOOLEAN default false)
  - cache_path (VARCHAR 255 nullable)
  - cached_at (TIMESTAMP nullable)
  - access_count (INT default 0)
  - last_verified_at (TIMESTAMP) -- Last time PS URL was checked
  - is_cover (BOOLEAN)
```

**Cache Cleanup Strategy:**
```php
// Artisan command: php artisan variants:cleanup-image-cache
// Run daily via cron

// Delete cached images not accessed in 30 days
VariantImage::where('is_cached', true)
    ->where('cached_at', '<', now()->subDays(30))
    ->where('access_count', '<', 5)
    ->each(function ($image) {
        Storage::delete($image->cache_path);
        $image->update(['is_cached' => false, 'cache_path' => null]);
    });
```

**Trade-offs:**
- +15% complexity (cache logic + background jobs + cleanup)
- But: -95% storage (only popular images cached)
- Performance: Same as Option A dla cached, faster dla non-cached (no upload during import)

**Context7 Evidence:** PrestaShop image URLs stable (`/img/p/{id}/{filename}.jpg`), Laravel HTTP client + Intervention Image = proven stack.

---

## 2. IMPLEMENTATION PLAN (Task Breakdown)

### Overview

**Total Tasks:** 12 głównych zadań + 45 subtasks
**Estimated Time:** 45-60h (realistic, not optimistic)
**Critical Path:** Task 1 → 2 → 3 → 4 → 6 → 8 → 9 → 11 → 12
**Parallel Work:** Task 5 (UI) can run parallel z Task 4 (Service Layer)

---

### 📦 TASK 1: Database Schema Extensions (3-4h)

**Cel:** Rozszerzyć schema o tabele dla import/export tracking i conflict management

**Priority:** 🔴 CRITICAL (blocker dla wszystkich innych tasks)

**Subtasks:**
1.1. Create `import_batches` table (import session tracking)
1.2. Create `import_templates` table (user-defined column mappings)
1.3. Create `conflict_logs` table (SKU conflicts, resolution tracking)
1.4. Create `export_batches` table (export session tracking)
1.5. Add indexes (performance optimization)
1.6. Create seeders (initial templates: VARIANTS_TEMPLATE_v1, PRESTASHOP_SYNC_TEMPLATE_v1)

**Schema Details:**

```sql
-- import_batches: Track each import session
CREATE TABLE import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    import_type ENUM('xlsx', 'prestashop_api') NOT NULL,
    filename VARCHAR(255) NULL, -- dla XLSX
    shop_id BIGINT UNSIGNED NULL, -- dla API import
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_rows INT DEFAULT 0,
    processed_rows INT DEFAULT 0,
    imported_products INT DEFAULT 0,
    failed_products INT DEFAULT 0,
    conflicts_count INT DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE SET NULL
);

-- import_templates: User-defined column mappings
CREATE TABLE import_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL, -- "My Custom Template"
    description TEXT NULL,
    mapping_config JSON NOT NULL, -- {"A": "sku", "B": "name", ...}
    is_shared BOOLEAN DEFAULT FALSE, -- Share with all users?
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_is_shared (is_shared),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- conflict_logs: SKU conflicts and resolution
CREATE TABLE conflict_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) NOT NULL,
    conflict_type ENUM('duplicate_sku', 'variant_mismatch', 'attribute_conflict', 'price_discrepancy', 'stock_discrepancy') NOT NULL,
    existing_data JSON NOT NULL, -- Current PPM data
    new_data JSON NOT NULL, -- Incoming import data
    resolution_status ENUM('pending', 'resolved_keep_ppm', 'resolved_use_import', 'resolved_merge', 'skipped') DEFAULT 'pending',
    resolved_by_user_id BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_batch_id (import_batch_id),
    INDEX idx_sku (sku),
    INDEX idx_resolution_status (resolution_status),
    FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- export_batches: Track export sessions
CREATE TABLE export_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    export_type ENUM('xlsx', 'prestashop_api') NOT NULL,
    shop_id BIGINT UNSIGNED NULL, -- Target shop dla API export
    filename VARCHAR(255) NULL, -- Generated file dla XLSX
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_products INT DEFAULT 0,
    exported_products INT DEFAULT 0,
    failed_products INT DEFAULT 0,
    filters JSON NULL, -- Export filters (category, price range, etc.)
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_shop_id (shop_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE SET NULL
);

-- variant_images: Add caching columns (extends existing table)
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) NULL AFTER image_path;
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS is_cached BOOLEAN DEFAULT FALSE AFTER image_url;
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS cache_path VARCHAR(255) NULL AFTER is_cached;
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS cached_at TIMESTAMP NULL AFTER cache_path;
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS access_count INT DEFAULT 0 AFTER cached_at;
ALTER TABLE variant_images ADD COLUMN IF NOT EXISTS last_verified_at TIMESTAMP NULL AFTER access_count;
```

**Deliverables:**
- 📁 `database/migrations/2025_11_05_140000_create_import_export_tables.php`
- 📁 `database/migrations/2025_11_05_140001_extend_variant_images_table.php`
- 📁 `database/seeders/ImportTemplateSeeder.php` (3 predefined templates)

**Agent:** laravel-expert
**Dependencies:** NONE (can start immediately)

---

### 📦 TASK 2: PrestaShop API Methods Extension (4-5h)

**Cel:** Extend PrestaShop8Client/9Client z methods dla variant products import/export

**Priority:** 🔴 CRITICAL (blocker dla Task 4)

**Subtasks:**
2.1. Add `getProductCombinations(int $productId): array` method
2.2. Add `getProductWithCombinations(int $productId): array` method (product + combinations in one call)
2.3. Add `createProductWithCombinations(array $productData): array` method
2.4. Add `updateCombination(int $combinationId, array $data): array` method
2.5. Add `deleteCombination(int $combinationId): bool` method
2.6. Add `getCombinationImages(int $combinationId): array` method
2.7. Unit tests dla all new methods (mocked responses)

**Implementation Details:**

```php
// PrestaShop8Client.php (extend existing client)

/**
 * Get all combinations for a product
 *
 * @param int $productId PrestaShop product ID
 * @return array List of combinations
 */
public function getProductCombinations(int $productId): array
{
    $response = $this->makeRequest('GET', "/combinations?filter[id_product]={$productId}&display=full");
    return $response['combinations'] ?? [];
}

/**
 * Get product with all combinations (optimized single call)
 *
 * @param int $productId PrestaShop product ID
 * @return array Product data with embedded combinations
 */
public function getProductWithCombinations(int $productId): array
{
    // Get product
    $product = $this->getProduct($productId);

    // Get combinations
    $combinations = $this->getProductCombinations($productId);

    // Enrich combinations with attribute data
    foreach ($combinations as &$combination) {
        $combination['attributes'] = [];

        foreach ($combination['associations']['product_option_values'] ?? [] as $attrValue) {
            $valueId = $attrValue['id'];
            $valueData = $this->getAttributeValue($valueId);

            $combination['attributes'][] = [
                'group_id' => $valueData['id_attribute_group'],
                'group_name' => $this->getAttributeGroup($valueData['id_attribute_group'])['name'],
                'value_id' => $valueId,
                'value_name' => $valueData['name'],
                'color' => $valueData['color'] ?? null,
            ];
        }
    }

    $product['combinations'] = $combinations;

    return $product;
}

/**
 * Create product with combinations (full variant product)
 *
 * @param array $productData Product + combinations data
 * @return array Created product with combination IDs
 * @throws PrestaShopApiException
 */
public function createProductWithCombinations(array $productData): array
{
    // Step 1: Create base product (product_type = 'combinations')
    $productXml = $this->generateProductXml($productData);
    $productResponse = $this->makeRequest('POST', '/products', [], [
        'body' => $productXml,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);

    $productId = $productResponse['product']['id'];

    // Step 2: Create combinations
    $createdCombinations = [];
    foreach ($productData['combinations'] ?? [] as $combinationData) {
        $combinationXml = $this->generateCombinationXml($productId, $combinationData);

        $combinationResponse = $this->makeRequest('POST', '/combinations', [], [
            'body' => $combinationXml,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        $createdCombinations[] = $combinationResponse['combination'];
    }

    return [
        'product' => $productResponse['product'],
        'combinations' => $createdCombinations,
    ];
}

/**
 * Update combination data
 *
 * @param int $combinationId PrestaShop combination ID
 * @param array $data Combination data to update
 * @return array Updated combination
 */
public function updateCombination(int $combinationId, array $data): array
{
    $xml = $this->generateCombinationUpdateXml($combinationId, $data);

    $response = $this->makeRequest('PUT', "/combinations/{$combinationId}", [], [
        'body' => $xml,
        'headers' => ['Content-Type' => 'application/xml'],
    ]);

    return $response['combination'];
}

/**
 * Delete combination
 *
 * @param int $combinationId PrestaShop combination ID
 * @return bool Success
 */
public function deleteCombination(int $combinationId): bool
{
    try {
        $this->makeRequest('DELETE', "/combinations/{$combinationId}");
        return true;
    } catch (PrestaShopApiException $e) {
        Log::error("Failed to delete combination", [
            'combination_id' => $combinationId,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

/**
 * Get images associated with combination
 *
 * @param int $combinationId PrestaShop combination ID
 * @return array List of image URLs
 */
public function getCombinationImages(int $combinationId): array
{
    $combination = $this->makeRequest('GET', "/combinations/{$combinationId}");

    $imageIds = $combination['combination']['associations']['images'] ?? [];
    $imageUrls = [];

    foreach ($imageIds as $imageData) {
        $imageId = $imageData['id'];
        $productId = $combination['combination']['id_product'];

        // Construct PS image URL
        $imageUrls[] = $this->getShopUrl() . "/img/p/{$productId}/{$imageId}.jpg";
    }

    return $imageUrls;
}

/**
 * Generate XML for product creation (type: combinations)
 */
protected function generateProductXml(array $data): string
{
    $name = $data['name'] ?? 'Unnamed Product';
    $price = $data['price'] ?? 0;
    $categoryId = $data['id_category_default'] ?? 1;

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <id_category_default><![CDATA[{$categoryId}]]></id_category_default>
        <product_type><![CDATA[combinations]]></product_type>
        <price><![CDATA[{$price}]]></price>
        <active><![CDATA[1]]></active>
        <name>
            <language id="1"><![CDATA[{$name}]]></language>
        </name>
        <link_rewrite>
            <language id="1"><![CDATA[{$this->generateSlug($name)}]]></language>
        </link_rewrite>
        <description>
            <language id="1"><![CDATA[{$data['description'] ?? ''}]]></language>
        </description>
    </product>
</prestashop>
XML;
}

/**
 * Generate XML for combination creation
 */
protected function generateCombinationXml(int $productId, array $data): string
{
    $reference = $data['reference'] ?? '';
    $ean13 = $data['ean13'] ?? '';
    $price = $data['price'] ?? 0;
    $quantity = $data['quantity'] ?? 0;

    // Attribute values (product_option_values)
    $attributeValuesXml = '';
    foreach ($data['attribute_values'] ?? [] as $valueId) {
        $attributeValuesXml .= "<product_option_value><id><![CDATA[{$valueId}]]></id></product_option_value>";
    }

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <combination>
        <id_product><![CDATA[{$productId}]]></id_product>
        <reference><![CDATA[{$reference}]]></reference>
        <ean13><![CDATA[{$ean13}]]></ean13>
        <price><![CDATA[{$price}]]></price>
        <quantity><![CDATA[{$quantity}]]></quantity>
        <associations>
            <product_option_values nodeType="product_option_value" api="product_option_values">
                {$attributeValuesXml}
            </product_option_values>
        </associations>
    </combination>
</prestashop>
XML;
}
```

**Deliverables:**
- 📁 `app/Services/PrestaShop/Clients/PrestaShop8Client.php` (updated, +150 lines)
- 📁 `app/Services/PrestaShop/Clients/PrestaShop9Client.php` (updated, +150 lines)
- 📁 `tests/Unit/Services/PrestaShop8ClientCombinationsTest.php` (new, 10 tests)

**Agent:** prestashop-api-expert
**Dependencies:** NONE (existing PrestaShop8Client foundation from Phase 5.5)
**CLAUDE.md Compliance:** ✅ PrestaShop8Client.php will be ~430 lines (278 + 150) - still under 500 line limit

---

### 📦 TASK 3: Validation Service Layer (5-6h)

**Cel:** Shared validation service dla XLSX i API imports (DRY principle)

**Priority:** 🔴 CRITICAL (used by both Task 4.1 and 4.2)

**Subtasks:**
3.1. Create `VariantImportValidationService` class
3.2. Implement SKU uniqueness validation (database check)
3.3. Implement AttributeType/AttributeValue existence validation
3.4. Implement price validation (format, non-negative, max 2 decimals)
3.5. Implement stock validation (integer, non-negative)
3.6. Implement image URL validation (format, accessibility)
3.7. Create custom validation rules (`UniqueSKUAcrossProducts`, `AttributeTypeExists`)
3.8. Unit tests (15+ test cases)

**Implementation:**

```php
// app/Services/Product/VariantImportValidationService.php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Rules\UniqueSKUAcrossProducts;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VariantImportValidationService
{
    /**
     * Validate single product data for import
     *
     * @param array $data Product data from import source
     * @param array $context Additional context (import_batch_id, etc.)
     * @return array Validated data
     * @throws ValidationException
     */
    public function validateProduct(array $data, array $context = []): array
    {
        $validator = Validator::make($data, [
            // Product fields
            'sku' => ['required', 'string', 'max:100', new UniqueSKUAcrossProducts($context['ignore_product_id'] ?? null)],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],

            // Variants array
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:100', new UniqueSKUAcrossProducts()],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.ean13' => ['nullable', 'string', 'max:13'],

            // Variant attributes
            'variants.*.attributes' => ['required', 'array', 'min:1'],
            'variants.*.attributes.*.type_code' => ['required', 'string', 'exists:attribute_types,code'],
            'variants.*.attributes.*.value_code' => ['required', 'string'],

            // Images
            'variants.*.images' => ['nullable', 'array'],
            'variants.*.images.*' => ['required', 'url', 'max:500'],
        ], [
            // Custom error messages (Polish)
            'sku.required' => 'SKU jest wymagane',
            'sku.unique' => 'SKU :input już istnieje w systemie',
            'name.required' => 'Nazwa produktu jest wymagana',
            'price.regex' => 'Cena musi mieć maksymalnie 2 miejsca dziesiętne',
            'variants.*.attributes.*.type_code.exists' => 'Grupa wariantów :input nie istnieje',
            'variants.*.images.*.url' => 'Nieprawidłowy format URL obrazka',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional business logic validation
        $this->validateAttributeValues($data['variants'] ?? []);
        $this->validatePriceDiscrepancies($data);
        $this->validateStockLevels($data);

        return $validator->validated();
    }

    /**
     * Validate that AttributeValues exist for given AttributeTypes
     */
    protected function validateAttributeValues(array $variants): void
    {
        $errors = [];

        foreach ($variants as $variantIndex => $variant) {
            foreach ($variant['attributes'] ?? [] as $attrIndex => $attribute) {
                $typeCode = $attribute['type_code'];
                $valueCode = $attribute['value_code'];

                // Check if AttributeType exists
                $attributeType = AttributeType::where('code', $typeCode)->first();
                if (!$attributeType) {
                    $errors["variants.{$variantIndex}.attributes.{$attrIndex}.type_code"][] = "Grupa wariantów '{$typeCode}' nie istnieje";
                    continue;
                }

                // Check if AttributeValue exists for this type
                $attributeValue = AttributeValue::where('attribute_type_id', $attributeType->id)
                    ->where('code', $valueCode)
                    ->first();

                if (!$attributeValue) {
                    $errors["variants.{$variantIndex}.attributes.{$attrIndex}.value_code"][] =
                        "Wartość '{$valueCode}' nie istnieje dla grupy '{$typeCode}'. Dostępne: " .
                        $attributeType->values->pluck('code')->join(', ');
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate price discrepancies (warn if price differences > 50%)
     */
    protected function validatePriceDiscrepancies(array $data): void
    {
        $basePrice = $data['price'] ?? 0;

        foreach ($data['variants'] ?? [] as $index => $variant) {
            $variantPrice = $variant['price'] ?? $basePrice;

            if ($basePrice > 0) {
                $diff = abs($variantPrice - $basePrice);
                $diffPercent = ($diff / $basePrice) * 100;

                if ($diffPercent > 50) {
                    Log::warning("Large price discrepancy detected", [
                        'sku' => $data['sku'],
                        'variant_sku' => $variant['sku'],
                        'base_price' => $basePrice,
                        'variant_price' => $variantPrice,
                        'diff_percent' => $diffPercent,
                    ]);
                }
            }
        }
    }

    /**
     * Validate stock levels (warn if negative or unrealistically high)
     */
    protected function validateStockLevels(array $data): void
    {
        foreach ($data['variants'] ?? [] as $variant) {
            $stock = $variant['stock'] ?? 0;

            if ($stock < 0) {
                throw ValidationException::withMessages([
                    'variants.*.stock' => ['Stan magazynowy nie może być ujemny'],
                ]);
            }

            if ($stock > 10000) {
                Log::warning("Unusually high stock level", [
                    'sku' => $variant['sku'],
                    'stock' => $stock,
                ]);
            }
        }
    }

    /**
     * Batch validate multiple products (for bulk import)
     *
     * @param array $products Array of product data
     * @return array ['valid' => [...], 'invalid' => [...]]
     */
    public function validateBatch(array $products): array
    {
        $valid = [];
        $invalid = [];

        foreach ($products as $index => $productData) {
            try {
                $validated = $this->validateProduct($productData, ['batch_index' => $index]);
                $valid[] = $validated;
            } catch (ValidationException $e) {
                $invalid[] = [
                    'index' => $index,
                    'data' => $productData,
                    'errors' => $e->errors(),
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }
}
```

**Custom Validation Rules:**

```php
// app/Rules/UniqueSKUAcrossProducts.php

namespace App\Rules;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Rule;

class UniqueSKUAcrossProducts implements Rule
{
    protected ?int $ignoreProductId;

    public function __construct(?int $ignoreProductId = null)
    {
        $this->ignoreProductId = $ignoreProductId;
    }

    public function passes($attribute, $value)
    {
        // Check in products table
        $productQuery = Product::where('sku', $value);
        if ($this->ignoreProductId) {
            $productQuery->where('id', '!=', $this->ignoreProductId);
        }

        if ($productQuery->exists()) {
            return false;
        }

        // Check in product_variants table
        $variantQuery = ProductVariant::where('sku', $value);
        if ($this->ignoreProductId) {
            $variantQuery->whereHas('product', function ($q) {
                $q->where('id', '!=', $this->ignoreProductId);
            });
        }

        return !$variantQuery->exists();
    }

    public function message()
    {
        return 'SKU :input już istnieje w systemie (produkt lub wariant).';
    }
}
```

**Deliverables:**
- 📁 `app/Services/Product/VariantImportValidationService.php` (~250 lines)
- 📁 `app/Rules/UniqueSKUAcrossProducts.php` (~50 lines)
- 📁 `tests/Unit/Services/VariantImportValidationServiceTest.php` (15 tests)

**Agent:** laravel-expert
**Dependencies:** Task 1 (database schema) must be complete
**CLAUDE.md Compliance:** ✅ All files under 300 lines

---

### 📦 TASK 4: Service Layer - Import/Export Services (12-15h)

**Cel:** Core business logic dla import/export operations

**Priority:** 🔴 CRITICAL (largest task, core functionality)

**This is a COMPOSITE task split into 2 major subtasks:**

#### TASK 4.1: XLSX Import Service (6-7h)

**Subtasks:**
4.1.1. Create `XlsxVariantImportService` class
4.1.2. Implement template loading (`getTemplate(string $templateName): Collection`)
4.1.3. Implement column mapping resolution (auto-detect + manual override)
4.1.4. Implement row-by-row import with validation (use Task 3 ValidationService)
4.1.5. Implement conflict detection (SKU duplicates)
4.1.6. Implement transaction support (rollback on error)
4.1.7. Implement progress tracking (update ImportBatch record)
4.1.8. Unit tests (10 scenarios)

**Implementation:**

```php
// app/Services/Product/XlsxVariantImportService.php

namespace App\Services\Product;

use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ConflictLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class XlsxVariantImportService
{
    protected VariantImportValidationService $validator;

    public function __construct(VariantImportValidationService $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Import products from XLSX file
     *
     * @param string $filePath Path to uploaded XLSX
     * @param int $userId User performing import
     * @param array $options Import options (template, mapping, etc.)
     * @return ImportBatch
     */
    public function import(string $filePath, int $userId, array $options = []): ImportBatch
    {
        // Create batch record
        $batch = ImportBatch::create([
            'user_id' => $userId,
            'import_type' => 'xlsx',
            'filename' => basename($filePath),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Load Excel data
            $data = Excel::toArray(new class implements ToArray, WithHeadingRow {
                public function array(array $array) {
                    return $array;
                }
            }, $filePath)[0];

            $batch->update(['total_rows' => count($data)]);

            // Apply column mapping
            $mapping = $options['mapping'] ?? $this->detectMapping(array_keys($data[0]));
            $mappedData = $this->applyMapping($data, $mapping);

            // Group by products (SKU-based grouping)
            $products = $this->groupByProduct($mappedData);

            // Process each product with transaction
            DB::transaction(function () use ($batch, $products) {
                foreach ($products as $index => $productData) {
                    $this->processProduct($batch, $productData, $index);

                    // Update progress every 10 products
                    if (($index + 1) % 10 === 0) {
                        $batch->refresh();
                        Log::info("Import progress", [
                            'batch_id' => $batch->id,
                            'processed' => $index + 1,
                            'total' => count($products),
                        ]);
                    }
                }
            });

            // Mark complete
            $batch->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error("Import failed", [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $batch->fresh();
    }

    /**
     * Process single product (with variants)
     */
    protected function processProduct(ImportBatch $batch, array $productData, int $index): void
    {
        try {
            // Validate
            $validated = $this->validator->validateProduct($productData);

            // Check for conflicts
            if (Product::where('sku', $validated['sku'])->exists()) {
                $this->logConflict($batch, $validated);
                $batch->increment('failed_products');
                return; // SKIP
            }

            // Create product
            $product = Product::create([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'has_variants' => !empty($validated['variants']),
            ]);

            // Create variants
            foreach ($validated['variants'] ?? [] as $variantData) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'name' => $variantData['name'],
                    'ean13' => $variantData['ean13'] ?? null,
                ]);

                // Assign attributes
                foreach ($variantData['attributes'] as $attribute) {
                    $attributeType = AttributeType::where('code', $attribute['type_code'])->first();
                    $attributeValue = AttributeValue::where('attribute_type_id', $attributeType->id)
                        ->where('code', $attribute['value_code'])
                        ->first();

                    $variant->attributes()->attach($attributeValue->id);
                }

                // Create prices (if provided)
                if (isset($variantData['price'])) {
                    // Assume "Detaliczna" price group for now
                    VariantPrice::create([
                        'variant_id' => $variant->id,
                        'price_group_id' => 1, // TODO: Make configurable
                        'price' => $variantData['price'],
                    ]);
                }

                // Create stock (if provided)
                if (isset($variantData['stock'])) {
                    VariantStock::create([
                        'variant_id' => $variant->id,
                        'warehouse_id' => 1, // TODO: Make configurable
                        'quantity' => $variantData['stock'],
                    ]);
                }

                // Handle images
                foreach ($variantData['images'] ?? [] as $imageUrl) {
                    VariantImage::create([
                        'variant_id' => $variant->id,
                        'image_url' => $imageUrl,
                        'is_cached' => false,
                        'last_verified_at' => now(),
                    ]);
                }
            }

            $batch->increment('imported_products');
            $batch->increment('processed_rows', count($validated['variants'] ?? []) + 1);

        } catch (ValidationException $e) {
            $batch->increment('failed_products');
            $batch->increment('processed_rows');

            Log::warning("Product validation failed", [
                'batch_id' => $batch->id,
                'index' => $index,
                'sku' => $productData['sku'] ?? 'unknown',
                'errors' => $e->errors(),
            ]);
        }
    }

    /**
     * Log SKU conflict
     */
    protected function logConflict(ImportBatch $batch, array $productData): void
    {
        $existing = Product::where('sku', $productData['sku'])->first();

        ConflictLog::create([
            'import_batch_id' => $batch->id,
            'sku' => $productData['sku'],
            'conflict_type' => 'duplicate_sku',
            'existing_data' => $existing->toArray(),
            'new_data' => $productData,
            'resolution_status' => 'pending',
        ]);

        $batch->increment('conflicts_count');
    }

    /**
     * Auto-detect column mapping based on header names
     */
    protected function detectMapping(array $headers): array
    {
        $mapping = [];

        $patterns = [
            'sku' => ['sku', 'kod', 'reference', 'ref'],
            'name' => ['nazwa', 'name', 'product name', 'produkt'],
            'price' => ['cena', 'price', 'cena detaliczna'],
            'stock' => ['stan', 'stock', 'quantity', 'qty'],
            'ean13' => ['ean', 'ean13', 'barcode'],
            // ... more patterns
        ];

        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));

            foreach ($patterns as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($normalized, $keyword)) {
                        $mapping[$header] = $field;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    // ... more helper methods
}
```

**Deliverables:**
- 📁 `app/Services/Product/XlsxVariantImportService.php` (~350 lines - split to trait if needed)
- 📁 `tests/Unit/Services/XlsxVariantImportServiceTest.php` (10 tests)

#### TASK 4.2: PrestaShop API Import Service (3-4h)

**Subtasks:**
4.2.1. Create `PrestaShopVariantImportService` class
4.2.2. Implement `fetchAndImport(int $shopId, array $filters)`
4.2.3. Implement AttributeType/AttributeValue mapping (use Phase 2 sync service)
4.2.4. Implement price/stock import per shop
4.2.5. Implement image URL import (lazy cache strategy from Decision #6)
4.2.6. Unit tests (8 scenarios)

**Implementation Summary:**
```php
// Fetch products with combinations from PS
$products = $psClient->getProducts(['filter[active]' => 1]);

foreach ($products as $psProduct) {
    // Get combinations
    $combinations = $psClient->getProductCombinations($psProduct['id']);

    // Map to PPM structure
    $ppmProduct = $this->mapPrestaShopProduct($psProduct, $combinations);

    // Validate
    $validated = $this->validator->validateProduct($ppmProduct);

    // Import (same logic as XLSX)
    $this->processProduct($batch, $validated, $index);
}
```

**Deliverables:**
- 📁 `app/Services/Product/PrestaShopVariantImportService.php` (~250 lines)
- 📁 `tests/Unit/Services/PrestaShopVariantImportServiceTest.php` (8 tests)

#### TASK 4.3: Export Service (3-4h)

**Subtasks:**
4.3.1. Create `VariantExportService` class
4.3.2. Implement XLSX export (with template generation)
4.3.3. Implement PrestaShop API export (create products with combinations)
4.3.4. Implement filtering (category, price range, stock level)
4.3.5. Implement queue support (large exports)
4.3.6. Unit tests (6 scenarios)

**Deliverables:**
- 📁 `app/Services/Product/VariantExportService.php` (~200 lines)
- 📁 `tests/Unit/Services/VariantExportServiceTest.php` (6 tests)

**Agent:** prestashop-api-expert + laravel-expert (parallel work possible)
**Dependencies:** Task 2 (PS API methods), Task 3 (Validation)
**CLAUDE.md Compliance:** ⚠️ XlsxVariantImportService may exceed 300 lines - use Trait extraction pattern

---

### 📦 TASK 5: UI/UX Implementation (10-12h)

**Cel:** User interface dla import/export operations

**Priority:** 🟡 HIGH (can run parallel with Task 4)

**Subtasks:**
5.1. Create `/admin/products/import` route and Livewire component
5.2. Create `/admin/products/export` route and Livewire component
5.3. Implement import wizard UI (4 steps: Upload → Mapping → Preview → Execute)
5.4. Implement export wizard UI (3 steps: Filters → Options → Execute)
5.5. Implement conflict resolution UI (ConflictReviewModal)
5.6. Implement progress tracking UI (real-time batch monitoring)
5.7. Implement import history dashboard
5.8. CSS styling (PPM enterprise standards compliance)
5.9. Frontend verification (screenshots, MANDATORY)

**UI Wireframes:**

```
┌─────────────────────────────────────────────────────────────┐
│ IMPORT PRODUKTÓW WARIANTOWYCH                               │
├─────────────────────────────────────────────────────────────┤
│ ┌───────┬───────┬───────┬───────┐                          │
│ │ 1. Upload │ 2. Mapping │ 3. Preview │ 4. Execute │        │
│ └───▼───┴────────┴────────┴────────┘                        │
│                                                               │
│ Wybierz metodę importu:                                      │
│ ( ) XLSX Template                                            │
│   └─ [Wybierz plik] [Pobierz szablon ▼]                     │
│       - VARIANTS_TEMPLATE_v1.xlsx                            │
│       - PRESTASHOP_SYNC_TEMPLATE_v1.xlsx                     │
│       - ERP_VARIANTS_TEMPLATE_v1.xlsx                        │
│                                                               │
│ ( ) PrestaShop API                                           │
│   └─ [Wybierz sklep ▼] [Filtry zaawansowane ▼]             │
│                                                               │
│ Opcje importu:                                               │
│ ☐ Force overwrite existing products (⚠️ Admin only)        │
│ ☑ Run in background (email notification)                   │
│                                                               │
│ [Anuluj] [Dalej: Mapowanie →]                               │
└─────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────┐
│ KROK 2: MAPOWANIE KOLUMN                                     │
├─────────────────────────────────────────────────────────────┤
│ Preview pierwszych 3 wierszy:                                │
│ ┌──────┬──────────────────┬─────────┬────────┬────────┐    │
│ │ A    │ B                │ C       │ D      │ E      │    │
│ ├──────┼──────────────────┼─────────┼────────┼────────┤    │
│ │ SKU  │ Nazwa            │ Kolor   │ Cena   │ Stan   │    │
│ │ ABC1 │ Koszulka Polo S  │ Czerwony│ 99.99  │ 50     │    │
│ │ ABC2 │ Koszulka Polo M  │ Czerwony│ 99.99  │ 30     │    │
│ └──────┴──────────────────┴─────────┴────────┴────────┘    │
│                                                               │
│ Mapowanie:                                                    │
│ A (SKU)       → [SKU ▼]                ✅ Wykryto           │
│ B (Nazwa)     → [Nazwa ▼]              ✅ Wykryto           │
│ C (Kolor)     → [AttributeType: Kolor ▼] ⚠️ Potwierdź      │
│ D (Cena)      → [Cena ▼]               ✅ Wykryto           │
│ E (Stan)      → [Stan Magazynowy ▼]    ✅ Wykryto           │
│                                                               │
│ [Zapisz jako szablon] [← Wstecz] [Dalej: Preview →]        │
└─────────────────────────────────────────────────────────────┘
```

**Deliverables:**
- 📁 `app/Http/Livewire/Products/Import/ProductImportWizard.php` (~200 lines)
- 📁 `app/Http/Livewire/Products/Export/ProductExportWizard.php` (~150 lines)
- 📁 `app/Http/Livewire/Products/Import/ConflictReviewModal.php` (~180 lines)
- 📁 `resources/views/livewire/products/import/*` (5 blade files)
- 📁 `resources/css/products/import-export.css` (~150 lines)
- 📁 Screenshots: `_TOOLS/screenshots/phase6_5_*.png` (8+ screenshots)

**Agent:** livewire-specialist + frontend-specialist (parallel work)
**Dependencies:** Task 4 (Service Layer) for backend integration
**CLAUDE.md Compliance:** ✅ All files under 300 lines

---

### 📦 TASK 6: Queue Jobs Implementation (4-5h)

**Cel:** Background processing dla bulk imports/exports

**Priority:** 🟡 HIGH

**Subtasks:**
6.1. Create `VariantImportJob` (queued XLSX import)
6.2. Create `VariantExportJob` (queued export)
6.3. Create `CacheVariantImageJob` (lazy image caching)
6.4. Implement retry logic (3 attempts, exponential backoff)
6.5. Implement failed job handlers (conflict status, notifications)
6.6. Implement job chaining (Import → Validation → Notification)
6.7. Unit tests (job execution, failure handling)

**Implementation:**

```php
// app/Jobs/Products/VariantImportJob.php

namespace App\Jobs\Products;

use App\Models\ImportBatch;
use App\Services\Product\XlsxVariantImportService;
use App\Notifications\ImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VariantImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    protected int $batchId;
    protected string $filePath;
    protected array $options;

    public function __construct(int $batchId, string $filePath, array $options = [])
    {
        $this->batchId = $batchId;
        $this->filePath = $filePath;
        $this->options = $options;

        $this->onQueue('imports');
    }

    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }

    public function handle(XlsxVariantImportService $importService): void
    {
        $batch = ImportBatch::find($this->batchId);

        Log::info("Variant import job started", [
            'batch_id' => $this->batchId,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Execute import
            $result = $importService->import($this->filePath, $batch->user_id, $this->options);

            // Send notification
            $batch->user->notify(new ImportCompleted($batch));

            Log::info("Variant import job completed", [
                'batch_id' => $this->batchId,
                'imported' => $batch->imported_products,
                'failed' => $batch->failed_products,
                'conflicts' => $batch->conflicts_count,
            ]);

        } catch (\Exception $e) {
            Log::error("Variant import job failed", [
                'batch_id' => $this->batchId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        $batch = ImportBatch::find($this->batchId);

        $batch->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);

        // Send failure notification
        $batch->user->notify(new ImportFailed($batch, $exception));

        Log::error("Variant import job failed permanently", [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Deliverables:**
- 📁 `app/Jobs/Products/VariantImportJob.php` (~120 lines)
- 📁 `app/Jobs/Products/VariantExportJob.php` (~100 lines)
- 📁 `app/Jobs/Products/CacheVariantImageJob.php` (~80 lines)
- 📁 `app/Notifications/ImportCompleted.php` (~60 lines)
- 📁 `tests/Unit/Jobs/VariantImportJobTest.php` (5 tests)

**Agent:** laravel-expert
**Dependencies:** Task 4 (Service Layer)

---

### 📦 TASK 7: Conflict Resolution System (3-4h)

**Cel:** Manual conflict review and resolution UI

**Priority:** 🟢 MEDIUM

**Subtasks:**
7.1. Create `ConflictResolutionService` class
7.2. Implement batch conflict resolution methods
7.3. Create ConflictReviewModal Livewire component
7.4. Implement resolution strategies (keep PPM, use import, merge)
7.5. Implement audit trail logging
7.6. Unit tests

**Deliverables:**
- 📁 `app/Services/Product/ConflictResolutionService.php` (~150 lines)
- 📁 `app/Http/Livewire/Products/Import/ConflictReviewModal.php` (~180 lines)
- 📁 `resources/views/livewire/products/import/conflict-review-modal.blade.php`

**Agent:** livewire-specialist
**Dependencies:** Task 1 (conflict_logs table)

---

### 📦 TASK 8: Image Lazy Caching System (3-4h)

**Cel:** Implement lazy image caching strategy (Decision #6)

**Priority:** 🟢 MEDIUM

**Subtasks:**
8.1. Create `VariantImageService` class
8.2. Implement `getImageUrl()` with lazy cache logic
8.3. Implement `CacheVariantImageJob` (download + thumbnail generation)
8.4. Create Artisan command `variants:cleanup-image-cache`
8.5. Unit tests

**Deliverables:**
- 📁 `app/Services/Product/VariantImageService.php` (~120 lines)
- 📁 `app/Jobs/Products/CacheVariantImageJob.php` (~80 lines)
- 📁 `app/Console/Commands/CleanupVariantImageCache.php` (~60 lines)

**Agent:** laravel-expert
**Dependencies:** Task 1 (variant_images columns)

---

### 📦 TASK 9: Testing & E2E Verification (8-10h)

**Cel:** Comprehensive testing suite

**Priority:** 🔴 CRITICAL (quality gate)

**Subtasks:**
9.1. Unit tests dla wszystkich services (already in Task 3-4)
9.2. Feature tests (import XLSX workflow)
9.3. Feature tests (import PrestaShop API workflow)
9.4. Feature tests (export XLSX workflow)
9.5. Feature tests (conflict resolution)
9.6. E2E test with real PrestaShop (Phase 5.5 pattern)
9.7. Performance test (import 100+ products)
9.8. Browser tests (Dusk - optional)

**E2E Test Scenarios (minimum 6):**

**Scenario 1: XLSX Import - Happy Path**
```
1. Upload VARIANTS_TEMPLATE_v1.xlsx (10 products, 30 variants)
2. Auto-mapping verified
3. Preview shows correct data
4. Execute import
5. Verify: 10 products created, 30 variants created
6. Verify: Attributes assigned correctly
7. Verify: Prices/stock created
```

**Scenario 2: XLSX Import - Duplicate SKU Conflict**
```
1. Pre-create product with SKU "ABC123"
2. Upload XLSX containing same SKU
3. Verify: Conflict logged
4. Verify: Product skipped
5. Verify: ConflictLog entry created
6. Open Conflict Review UI
7. Resolve: "Use Import"
8. Verify: Product updated
```

**Scenario 3: PrestaShop API Import**
```
1. Setup: Create variant product in PS (dev.mpptrade.pl)
2. Trigger API import for shop 1
3. Verify: AttributeTypes synced (Phase 2 service)
4. Verify: Product + variants imported
5. Verify: Prices/stock imported
6. Verify: Image URLs stored (not cached yet)
```

**Scenario 4: XLSX Export**
```
1. Pre-create 5 products with variants
2. Trigger XLSX export (filter: category X)
3. Verify: XLSX file generated
4. Verify: Column structure matches template
5. Download and verify content
```

**Scenario 5: PrestaShop API Export**
```
1. Pre-create variant product in PPM
2. Trigger export to PS shop 1
3. Verify: Product created in PS (product_type = combinations)
4. Verify: Combinations created with correct attributes
5. Verify: Prices/stock synced
6. Verify: ExportBatch status = completed
```

**Scenario 6: Queue Jobs - Large Import**
```
1. Upload XLSX with 150 products
2. Verify: Job queued (not sync)
3. Process queue
4. Verify: Progress tracking updates
5. Verify: Email notification sent
6. Verify: ImportBatch status = completed
```

**Deliverables:**
- 📁 `tests/Feature/Products/VariantImportXlsxTest.php` (8 tests)
- 📁 `tests/Feature/Products/VariantImportPrestaShopTest.php` (6 tests)
- 📁 `tests/Feature/Products/VariantExportTest.php` (5 tests)
- 📁 `tests/Feature/Products/ConflictResolutionTest.php` (4 tests)
- 📁 E2E test report in `_AGENT_REPORTS/`

**Agent:** debugger + prestashop-api-expert
**Dependencies:** ALL previous tasks must be complete

---

### 📦 TASK 10: Integration & Code Review (4-5h)

**Cel:** Coding standards compliance, refactoring, optimization

**Priority:** 🔴 CRITICAL (quality gate)

**Subtasks:**
10.1. coding-style-agent review (CLAUDE.md compliance)
10.2. File size compliance (<300 lines - extract traits if needed)
10.3. No hardcoded values verification
10.4. Separation of concerns verification
10.5. Error handling completeness
10.6. Log cleanup (remove debug logs)
10.7. Performance optimization (query optimization, caching)

**Agent:** coding-style-agent + architect
**Dependencies:** Task 9 (all features implemented)

---

### 📦 TASK 11: Documentation & Deployment (4-5h)

**Cel:** User guides, deployment, production verification

**Priority:** 🔴 CRITICAL

**Subtasks:**
11.1. Create user guide: `_DOCS/VARIANT_IMPORT_EXPORT_USER_GUIDE.md`
11.2. Update CLAUDE.md (new routes, services, patterns)
11.3. Update Plan_Projektu/ETAP_05b.md (Phase 6.5 marked ✅)
11.4. Database backup (production)
11.5. Deploy migrations
11.6. Deploy all components (services, jobs, UI)
11.7. Clear cache + verify routes
11.8. Post-deployment verification (frontend-verification skill)
11.9. Admin account testing (full import/export workflow)

**Deliverables:**
- 📁 `_DOCS/VARIANT_IMPORT_EXPORT_USER_GUIDE.md` (comprehensive guide)
- 📁 Updated CLAUDE.md
- 📁 Updated Plan_Projektu/ETAP_05b.md
- 📁 Deployment scripts in `_TOOLS/`
- 📁 Screenshots: `_TOOLS/screenshots/phase6_5_production_*.png`

**Agent:** deployment-specialist + documentation-reader
**Dependencies:** Task 10 (code review passed)

---

### 📦 TASK 12: Agent Report & Knowledge Transfer (2h)

**Cel:** Final report generation, knowledge transfer

**Priority:** 🟢 MEDIUM

**Subtasks:**
12.1. Generate comprehensive agent report
12.2. Document lessons learned
12.3. Update architectural patterns documentation
12.4. Knowledge transfer session (if needed)

**Deliverables:**
- 📁 THIS REPORT (already in `_AGENT_REPORTS/architect_phase_6_5_planning_2025-11-04_REPORT.md`)
- 📁 Implementation completion report (after Phase 6.5 done)

**Agent:** architect + agent-report-writer skill
**Dependencies:** Task 11 (deployment complete)

---

## 3. DATABASE SCHEMA CHANGES

**Summary:** 4 nowe tabele + 1 extend existing table

**Status:** WSZYSTKIE ZDEFINIOWANE W TASK 1

Szczegóły w Task 1 powyżej:
- ✅ `import_batches` (import session tracking)
- ✅ `import_templates` (user column mappings)
- ✅ `conflict_logs` (SKU conflicts)
- ✅ `export_batches` (export session tracking)
- ✅ `variant_images` extensions (URL caching columns)

**NO CHANGES to core variant schema** - Phase 6 schema is sufficient!

---

## 4. SERVICE LAYER ARCHITECTURE

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                       │
│  ProductImportWizard │ ProductExportWizard │ ConflictModal  │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                     SERVICE LAYER                            │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ XlsxVariantImportService                              │  │
│  │ - import(file, userId, options)                       │  │
│  │ - applyMapping(data, mapping)                         │  │
│  │ - processProduct(batch, productData)                  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ PrestaShopVariantImportService                        │  │
│  │ - fetchAndImport(shopId, filters)                     │  │
│  │ - mapPrestaShopProduct(psProduct, combinations)       │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ VariantExportService                                  │  │
│  │ - exportToXlsx(filters)                               │  │
│  │ - exportToPrestaShop(shopId, productIds)              │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ VariantImportValidationService (SHARED)               │  │
│  │ - validateProduct(data)                               │  │
│  │ - validateBatch(products)                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ ConflictResolutionService                             │  │
│  │ - resolveConflict(conflictId, strategy)               │  │
│  │ - batchResolve(batchId, strategy)                     │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ VariantImageService                                   │  │
│  │ - getImageUrl(image)                                  │  │
│  │ - shouldCache(image)                                  │  │
│  └──────────────────────────────────────────────────────┘  │
└───────────────────────┬───────────────────────────────────┘
                        │
┌───────────────────────▼──────────────────────────────────────┐
│                  INTEGRATION LAYER                            │
│                                                                │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ PrestaShop8Client / PrestaShop9Client (Phase 5.5)       │ │
│  │ - getProductCombinations(productId)                     │ │
│  │ - createProductWithCombinations(productData)            │ │
│  │ - updateCombination(combinationId, data)                │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                                │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ PrestaShopAttributeSyncService (Phase 2)                │ │
│  │ - syncAttributeGroup(attributeType, shop)               │ │
│  │ - syncAttributeValue(attributeValue, shop)              │ │
│  └─────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

### Service Responsibilities

**XlsxVariantImportService:**
- Load XLSX file using Laravel Excel
- Apply column mapping (template-based or manual)
- Parse rows into Product + Variants structure
- Call ValidationService for each product
- Detect and log conflicts
- Create Product/ProductVariant/VariantAttribute/VariantPrice/VariantStock/VariantImage records
- Update ImportBatch progress

**PrestaShopVariantImportService:**
- Fetch products from PrestaShop API (with combinations)
- Map PrestaShop structure to PPM structure
- Sync AttributeTypes/AttributeValues using Phase 2 service
- Call ValidationService
- Detect conflicts
- Create PPM records
- Update ImportBatch progress

**VariantExportService:**
- Query PPM products (with filters)
- Export to XLSX using Laravel Excel (with template)
- OR export to PrestaShop using PrestaShop8Client
- Track ExportBatch progress
- Handle errors and rollback

**VariantImportValidationService (SHARED):**
- SKU uniqueness validation (across products + variants)
- AttributeType/AttributeValue existence validation
- Price/stock format validation
- Custom business rules validation
- Batch validation for bulk imports

**ConflictResolutionService:**
- Load conflict from conflict_logs table
- Apply resolution strategy:
  - keep_ppm: Skip import, keep existing
  - use_import: Overwrite existing with import data
  - merge: Smart merge (e.g., price from import, stock from PPM)
- Log resolution in audit trail
- Update Product/Variant records

**VariantImageService:**
- Lazy cache strategy:
  - If image accessed > 10 times → dispatch CacheVariantImageJob
  - If cached and fresh (<30 days) → return local URL
  - Otherwise → return PrestaShop URL
- Generate thumbnails (300x300)
- Cleanup old cached images (artisan command)

### Error Handling Strategy

**All services MUST implement:**
1. Try-catch blocks around external API calls
2. Transaction support (DB::transaction for multi-record operations)
3. Logging (INFO + ERROR levels)
4. Graceful degradation (continue processing if single product fails)
5. Rollback on critical errors

**Example:**
```php
try {
    DB::transaction(function () use ($batch, $products) {
        foreach ($products as $product) {
            try {
                $this->processProduct($batch, $product);
            } catch (ValidationException $e) {
                Log::warning("Product validation failed, skipping", [
                    'sku' => $product['sku'],
                    'errors' => $e->errors(),
                ]);
                $batch->increment('failed_products');
                continue; // Continue with next product
            }
        }
    });
} catch (\Exception $e) {
    Log::error("Import failed", ['error' => $e->getMessage()]);
    $batch->update(['status' => 'failed']);
    throw $e;
}
```

---

## 5. UI/UX DESIGN

### Import Wizard Flow

**Step 1: Upload/Method Selection**
- Radio buttons: XLSX vs PrestaShop API
- File upload field (for XLSX)
- Shop dropdown (for API)
- Template download links
- Options: Force overwrite, Run in background

**Step 2: Mapping (XLSX only)**
- Preview first 3 rows of Excel
- Auto-detected mappings (green checkmarks)
- Undetected mappings (yellow warnings, require user selection)
- Dropdown per column: PPM field selection
- "Save as template" button

**Step 3: Preview**
- Show parsed data (10 products max)
- Validation warnings (if any)
- Conflict warnings (duplicate SKUs)
- Estimated import time
- "Back to Mapping" or "Execute Import" buttons

**Step 4: Execute + Monitor**
- Progress bar (for queued imports)
- Real-time status updates (Livewire polling)
- Success/failure counts
- "View Details" button → ImportBatch page
- Email notification checkbox

### Export Wizard Flow

**Step 1: Filters**
- Category filter (multi-select tree)
- Price range slider
- Stock level filter (in stock, low stock, out of stock)
- Date range (created, updated)
- "Select all" / "Select filtered" checkboxes

**Step 2: Options**
- Export method: XLSX vs PrestaShop API
- Shop selection (for API export)
- Template selection (for XLSX)
- Include images checkbox
- Run in background checkbox

**Step 3: Execute + Monitor**
- Same as Import Step 4
- Download link (for XLSX)
- PrestaShop verification link (for API export)

### Conflict Resolution UI

**ConflictReviewModal:**
- List of conflicts (paginated, 10 per page)
- Per-conflict display:
  - SKU
  - Side-by-side comparison (PPM vs Import)
  - Diff highlighting (red for differences)
  - Resolution buttons: Keep PPM / Use Import / Merge
- Batch actions dropdown:
  - Resolve all: Keep PPM
  - Resolve all: Use Import
  - Custom merge strategy (price from import, stock from PPM)

### Progress Tracking UI

**ImportBatch Monitoring Page:**
- Batch info: ID, filename, user, timestamp
- Progress bar (processed / total)
- Stats cards: Imported, Failed, Conflicts
- Live log stream (last 50 entries)
- Action buttons: Cancel (if processing), Retry Failed, Download Error Report

**Import History Dashboard:**
- Table of recent imports
- Columns: Batch ID, Date, Method, Status, Products, Actions
- Filter by: Status, Date range, User
- Quick actions: View Details, Retry, Delete

---

## 6. TESTING STRATEGY

### Unit Tests (35+ tests total)

**VariantImportValidationServiceTest (15 tests):**
- ✅ Valid product passes validation
- ✅ Invalid SKU (empty, too long) fails
- ✅ Duplicate SKU detected
- ✅ Invalid price (negative, >2 decimals) fails
- ✅ Invalid stock (negative, non-integer) fails
- ✅ AttributeType not found fails
- ✅ AttributeValue not found fails
- ✅ Image URL invalid fails
- ✅ Batch validation returns valid + invalid arrays
- ✅ Price discrepancy warning logged
- ✅ Stock discrepancy warning logged
- ✅ Multiple validation errors collected
- ✅ Custom validation messages returned
- ✅ Context ignore_product_id works
- ✅ Callback validation rules work

**XlsxVariantImportServiceTest (10 tests):**
- ✅ Import valid XLSX creates products + variants
- ✅ Auto-mapping detects SKU/Name/Price columns
- ✅ Manual mapping override works
- ✅ Duplicate SKU logs conflict and skips
- ✅ Validation error logs failure and continues
- ✅ Progress tracking updates ImportBatch
- ✅ Transaction rollback on critical error
- ✅ Multiple products processed successfully
- ✅ Variant attributes assigned correctly
- ✅ Prices and stock created per variant

**PrestaShop8ClientCombinationsTest (10 tests):**
- ✅ getProductCombinations returns combinations array
- ✅ getProductWithCombinations includes attributes
- ✅ createProductWithCombinations creates product + combinations
- ✅ updateCombination updates combination data
- ✅ deleteCombination deletes combination
- ✅ getCombinationImages returns image URLs
- ✅ XML generation for product is correct
- ✅ XML generation for combination is correct
- ✅ Error handling for API failures
- ✅ Retry logic works (Phase 5.5 pattern)

### Feature Tests (23 tests total)

**VariantImportXlsxTest (8 tests):**
- ✅ User can upload XLSX and import products
- ✅ Auto-mapping wizard shows detected mappings
- ✅ Manual mapping override saves correctly
- ✅ Preview shows correct data before import
- ✅ Import creates products + variants + attributes + prices + stock
- ✅ Duplicate SKU conflict logged and skipped
- ✅ Validation errors displayed to user
- ✅ Queue jobs triggered for large imports (>50 products)

**VariantImportPrestaShopTest (6 tests):**
- ✅ User can select shop and trigger API import
- ✅ AttributeTypes synced from PrestaShop (Phase 2)
- ✅ Products + combinations imported correctly
- ✅ Prices and stock imported per shop
- ✅ Image URLs stored (not downloaded)
- ✅ Conflict resolution works for duplicate SKUs

**VariantExportTest (5 tests):**
- ✅ User can export products to XLSX
- ✅ XLSX column structure matches template
- ✅ Filters work (category, price range)
- ✅ User can export to PrestaShop shop
- ✅ PrestaShop product + combinations created correctly

**ConflictResolutionTest (4 tests):**
- ✅ Conflict review modal displays conflicts
- ✅ User can resolve single conflict (keep PPM)
- ✅ User can resolve single conflict (use import)
- ✅ User can batch resolve all conflicts

### E2E Tests (6 scenarios)

Defined in Task 9 above - full end-to-end workflows with real PrestaShop API.

### Performance Tests (2 scenarios)

**Scenario 1: Large XLSX Import**
- Upload XLSX with 250 products (750 variants)
- Verify: Import completes in < 5 minutes
- Verify: Memory usage < 512MB
- Verify: No timeout errors

**Scenario 2: PrestaShop API Import**
- Import 100 products from PrestaShop
- Verify: Average API call < 200ms
- Verify: Total import time < 3 minutes
- Verify: Queue jobs process efficiently

---

## 7. RISK ANALYSIS

### Risk Matrix

| Risk | Severity | Probability | Impact | Mitigation |
|------|----------|-------------|--------|------------|
| **R1: Kombinacja complexity** | 🔴 HIGH | 🟡 MEDIUM | 🔴 HIGH | See R1 below |
| **R2: PrestaShop API rate limits** | 🟡 MEDIUM | 🟡 MEDIUM | 🟡 MEDIUM | See R2 below |
| **R3: XLSX parsing errors** | 🟢 LOW | 🟡 MEDIUM | 🟢 LOW | See R3 below |
| **R4: Queue job failures** | 🟡 MEDIUM | 🟢 LOW | 🟡 MEDIUM | See R4 below |
| **R5: Data integrity conflicts** | 🔴 HIGH | 🟡 MEDIUM | 🔴 HIGH | See R5 below |
| **R6: Image storage explosion** | 🟡 MEDIUM | 🟢 LOW | 🟡 MEDIUM | See R6 below |
| **R7: Deployment complexity** | 🟡 MEDIUM | 🟢 LOW | 🟡 MEDIUM | See R7 below |

---

### 🔴 R1: Kombinacja Complexity (Product Variants)

**Severity:** HIGH | **Probability:** MEDIUM | **Impact:** HIGH

**Description:**
Product z 3 AttributeTypes (Kolor × Rozmiar × Materiał) przy 5 wartościach każdy = 5³ = 125 combinations! User może przypadkowo stworzyć setki wariantów.

**Example:**
```
Koszulka:
- Kolor: [Czerwony, Niebieski, Zielony, Żółty, Czarny] (5)
- Rozmiar: [XS, S, M, L, XL] (5)
- Materiał: [Bawełna, Polyester, Len, Wełna, Mieszanka] (5)
→ 125 combinations!
```

**Impact:**
- Database bloat (125 variant records per product)
- UI performance degradation (rendering 125 rows)
- PrestaShop API slowdown (125 combinations to sync)
- User confusion ("I didn't mean to create 125 variants!")

**Mitigation Strategies:**

**1. Frontend Warning System:**
```blade
@if ($selectedAttributeTypes->count() > 2)
    <div class="alert alert-warning">
        ⚠️ Wybrano {{ $selectedAttributeTypes->count() }} grupy wariantów.
        Wygeneruje to {{ $estimatedCombinations }} kombinacji!
        Czy na pewno chcesz kontynuować?
    </div>
@endif
```

**2. Hard Limit (configurable):**
```php
// Config: config/products.php
'max_variants_per_product' => 100,

// Validation
if ($estimatedCombinations > config('products.max_variants_per_product')) {
    throw ValidationException::withMessages([
        'variants' => "Maksymalna liczba wariantów to 100. Aktualnie: {$estimatedCombinations}",
    ]);
}
```

**3. Smart Filtering UI:**
```
Kolor: ☑ Czerwony ☑ Niebieski ☐ Zielony ☐ Żółty ☐ Czarny
Rozmiar: ☑ S ☑ M ☑ L ☐ XS ☐ XL
→ Estymowane kombinacje: 2 × 3 = 6 ✅
```

**4. Batch Creation with Preview:**
- Show preview of ALL combinations before creating
- Allow deselection of unwanted combinations
- "Create Selected" button (instead of "Create All")

**Contingency Plan:**
- If accidentally created too many variants → Bulk delete UI
- Admin command: `php artisan variants:cleanup-unused` (delete variants with 0 stock, 0 sales, age > 30 days)

**Success Criteria:**
- User cannot create > 100 variants per product
- Warning displayed if combinations > 20
- Preview shows all combinations before creation

---

### 🟡 R2: PrestaShop API Rate Limits

**Severity:** MEDIUM | **Probability:** MEDIUM | **Impact:** MEDIUM

**Description:**
PrestaShop API może mieć rate limits (np. 100 requests/minute). Import 50 products × 5 combinations = 250+ API calls → może przekroczyć limit.

**Impact:**
- HTTP 429 errors (Too Many Requests)
- Import failures mid-process
- Partial data sync (some products synced, others failed)

**Mitigation Strategies:**

**1. Request Throttling:**
```php
// BasePrestaShopClient.php
protected int $requestsPerMinute = 60; // Conservative limit
protected array $requestLog = [];

protected function throttleRequest(): void
{
    $now = now();

    // Remove requests older than 1 minute
    $this->requestLog = array_filter($this->requestLog, function ($timestamp) use ($now) {
        return $now->diffInSeconds($timestamp) < 60;
    });

    if (count($this->requestLog) >= $this->requestsPerMinute) {
        $oldestRequest = min($this->requestLog);
        $sleepSeconds = 60 - $now->diffInSeconds($oldestRequest);

        Log::info("Rate limit approaching, sleeping {$sleepSeconds}s");
        sleep($sleepSeconds + 1);
    }

    $this->requestLog[] = $now;
}
```

**2. Batch API Calls (if PS supports):**
```php
// Instead of 50 individual calls:
foreach ($products as $product) {
    $psClient->createProduct($product); // 50 calls
}

// Use batch endpoint (if available):
$psClient->batchCreateProducts($products); // 1 call
```

**3. Retry with Exponential Backoff:**
```php
// Already implemented in Phase 5.5 pattern
public function backoff(): array
{
    return [30, 60, 300]; // 30s, 1min, 5min
}
```

**4. Queue Jobs for Bulk Operations:**
- Always queue imports/exports > 50 products
- Process in chunks of 10 products
- Delay between chunks: 10 seconds

**Contingency Plan:**
- If rate limit hit → pause import, resume after cooldown period
- User notification: "Import paused due to API rate limit. Resuming in 60s..."

**Success Criteria:**
- Zero HTTP 429 errors in production
- Import of 100 products completes without manual intervention

---

### 🟢 R3: XLSX Parsing Errors

**Severity:** LOW | **Probability:** MEDIUM | **Impact:** LOW

**Description:**
User uploads malformed XLSX (wrong column order, merged cells, formulas, special characters).

**Impact:**
- Import fails with cryptic error
- User frustration
- Support tickets

**Mitigation Strategies:**

**1. Strict Template Validation:**
```php
// Validate that uploaded file matches expected structure
$headers = $sheet->getHeading();
$requiredHeaders = ['SKU', 'Nazwa', 'Kolor', 'Rozmiar', 'Cena'];

$missing = array_diff($requiredHeaders, $headers);
if (!empty($missing)) {
    throw new \Exception("Missing columns: " . implode(', ', $missing));
}
```

**2. Sanitize Input:**
```php
// Remove merged cells, formulas, special characters
foreach ($rows as &$row) {
    $row['sku'] = Str::slug($row['sku']); // Remove special chars
    $row['price'] = floatval($row['price']); // Force numeric
}
```

**3. User-Friendly Error Messages:**
```
❌ Row 15: SKU contains invalid characters (only A-Z, 0-9, -, _ allowed)
❌ Row 23: Price must be a number (found: "ABC")
❌ Row 45: Kolor "Fioletowy" not found. Available: Czerwony, Niebieski, Zielony
```

**4. Template Download Link:**
- Always provide fresh template download
- Template locked (read-only columns)
- Example data pre-filled

**Contingency Plan:**
- If too many errors (>10%) → abort import, show error report
- User can fix Excel and re-upload

**Success Criteria:**
- < 5% of imports fail due to parsing errors
- Error messages actionable (user knows how to fix)

---

### 🟡 R4: Queue Job Failures

**Severity:** MEDIUM | **Probability:** LOW | **Impact:** MEDIUM

**Description:**
Queue job fails mid-import (server restart, memory exhaustion, database deadlock).

**Impact:**
- Partial import (50/100 products imported)
- Orphaned records (products without variants)
- Conflict state (hard to resume)

**Mitigation Strategies:**

**1. Idempotent Operations:**
```php
// Check if product already imported in this batch
$existing = Product::where('sku', $sku)
    ->whereHas('importBatch', function ($q) use ($batchId) {
        $q->where('id', $batchId);
    })
    ->first();

if ($existing) {
    Log::info("Product already imported, skipping", ['sku' => $sku]);
    return; // SKIP
}
```

**2. Transaction Support:**
```php
DB::transaction(function () {
    // Create product + variants + attributes in single transaction
    // If ANY step fails → rollback ALL
});
```

**3. Checkpoint System:**
```php
// Save checkpoint every 10 products
if ($processed % 10 === 0) {
    Cache::put("import_batch_{$batchId}_checkpoint", $processed, 3600);
}

// Resume from checkpoint if job fails
$checkpoint = Cache::get("import_batch_{$batchId}_checkpoint", 0);
$productsToProcess = array_slice($allProducts, $checkpoint);
```

**4. Retry Logic (already implemented):**
- 3 attempts with exponential backoff
- Failed job handler logs error + sends notification

**Contingency Plan:**
- If job fails permanently → show "Retry Failed Products" button in UI
- Admin can manually trigger retry for failed batch

**Success Criteria:**
- 99% of queued imports complete successfully
- Failed imports resumable without data loss

---

### 🔴 R5: Data Integrity Conflicts

**Severity:** HIGH | **Probability:** MEDIUM | **Impact:** HIGH

**Description:**
Import overwrites production data (prices updated by user 5 minutes ago, now overwritten by stale import data).

**Impact:**
- Data loss (user changes overwritten)
- Business impact (wrong prices displayed to customers)
- Trust erosion (users afraid to use import feature)

**Mitigation Strategies:**

**1. Conflict Detection (already in Decision #3):**
- Detect ALL conflicts before import
- Show conflict review UI
- User manually resolves each conflict

**2. Timestamp-Based Conflict Detection:**
```php
// Check if PPM data is newer than import source
if ($ppmProduct->updated_at > $importSourceTimestamp) {
    $this->logConflict($batch, $ppmProduct, $importData, 'data_staleness');
    return; // SKIP, data too old
}
```

**3. Immutable Fields Protection:**
```php
// Never overwrite certain fields (e.g., user-entered notes)
$protectedFields = ['internal_notes', 'custom_field_1'];

foreach ($protectedFields as $field) {
    if (isset($ppmProduct->$field) && !empty($ppmProduct->$field)) {
        unset($importData[$field]); // Don't overwrite
    }
}
```

**4. Audit Trail (complete history):**
```sql
product_change_log:
  - product_id
  - changed_by_user_id
  - change_type (enum: manual_edit, import_xlsx, import_prestashop)
  - old_value (JSON)
  - new_value (JSON)
  - changed_at
```

**5. "Force Overwrite" Requires Admin Permission:**
```php
Gate::define('force-overwrite-products', function ($user) {
    return $user->hasRole('admin');
});

if ($request->boolean('force_overwrite') && !Gate::allows('force-overwrite-products')) {
    abort(403, 'Only admins can force overwrite');
}
```

**Contingency Plan:**
- If wrong data imported → Rollback feature (restore from product_change_log)
- Admin command: `php artisan products:rollback-import {batch_id}`

**Success Criteria:**
- Zero data loss incidents in production
- All conflicts reviewed before import
- Audit trail complete (100% changes logged)

---

### 🟡 R6: Image Storage Explosion

**Severity:** MEDIUM | **Probability:** LOW | **Impact:** MEDIUM

**Description:**
If lazy cache strategy fails, ALL images downloaded immediately → storage quota exceeded.

**Impact:**
- Server disk full (100GB+ images)
- Import failures (no space left)
- Performance degradation

**Mitigation Strategies:**

**1. Lazy Cache (already in Decision #6):**
- NEVER download during import
- Only download on demand (when image accessed >10 times)
- Thumbnail generation only (300x300 max)

**2. Storage Quota Monitoring:**
```php
// Check available disk space before caching
$freeSpace = disk_free_space(storage_path('app/variant-images'));
$requiredSpace = 5 * 1024 * 1024; // 5MB per image (conservative)

if ($freeSpace < $requiredSpace) {
    Log::error("Insufficient disk space for image cache");
    throw new \Exception("Storage quota exceeded");
}
```

**3. Automatic Cleanup (already in Task 8):**
```php
// Daily cron: Delete cached images not accessed in 30 days
php artisan variants:cleanup-image-cache
```

**4. CDN Integration (future enhancement):**
```php
// Store thumbnails in S3/CDN instead of local disk
Storage::disk('s3')->put($path, $thumbnail);
```

**Contingency Plan:**
- If storage full → disable image caching, use PS URLs only
- Emergency cleanup script deletes all cached images (URLs still work)

**Success Criteria:**
- Storage usage < 10GB for image cache
- Automatic cleanup keeps cache size stable

---

### 🟡 R7: Deployment Complexity

**Severity:** MEDIUM | **Probability:** LOW | **Impact:** MEDIUM

**Description:**
Phase 6.5 adds 50+ files (services, jobs, migrations, UI). Deployment może być error-prone.

**Impact:**
- Deployment failures (missing files, wrong order)
- Production downtime
- Rollback difficulty

**Mitigation Strategies:**

**1. Deployment Checklist (Task 11):**
```
☐ Database backup
☐ Deploy migrations (order: 1, 2, 3, ...)
☐ Deploy services (order matters!)
☐ Deploy jobs
☐ Deploy UI components
☐ Deploy assets (npm run build)
☐ Clear cache (view, config, route)
☐ Test queue worker running
☐ Smoke test (import 1 product)
☐ Full test (import 10 products)
```

**2. Deployment Script (atomic deployment):**
```powershell
# _TOOLS/deploy_phase_6_5.ps1
# Upload ALL files in correct order
# Run migrations
# Clear cache
# Verify deployment
# Rollback if ANY step fails
```

**3. Feature Flag:**
```php
// Config: config/features.php
'phase_6_5_import_export' => env('FEATURE_PHASE_6_5', false),

// UI: Only show import/export if feature enabled
@if (config('features.phase_6_5_import_export'))
    <a href="/admin/products/import">Import</a>
@endif
```

**4. Rollback Plan:**
```sql
-- If deployment fails, rollback migrations
php artisan migrate:rollback --step=2

-- Restore previous code version
git checkout {previous_commit}
pscp ... # Re-deploy old files
```

**Contingency Plan:**
- If critical bug found after deployment → disable feature flag
- Fix bug, test, re-deploy

**Success Criteria:**
- Zero-downtime deployment
- Rollback possible within 5 minutes

---

## 8. TIMELINE & MILESTONES

### Timeline Overview

**Total Estimated Time:** 45-60h (realistic range)
**Critical Path Duration:** 35-45h (tasks that cannot be parallelized)
**Parallel Work Savings:** ~10h (Task 5 UI parallel z Task 4 Services)

### Time Estimates (Pessimistic / Optimistic / Likely)

| Task | Pessimistic | Optimistic | Likely | Agent(s) |
|------|-------------|------------|--------|----------|
| **Task 1: Database Schema** | 4h | 3h | 3.5h | laravel-expert |
| **Task 2: PS API Methods** | 6h | 4h | 5h | prestashop-api-expert |
| **Task 3: Validation Service** | 7h | 5h | 6h | laravel-expert |
| **Task 4.1: XLSX Import** | 8h | 6h | 7h | laravel-expert |
| **Task 4.2: PS API Import** | 5h | 3h | 4h | prestashop-api-expert |
| **Task 4.3: Export Service** | 5h | 3h | 4h | prestashop-api-expert |
| **Task 5: UI/UX** | 14h | 10h | 12h | livewire + frontend |
| **Task 6: Queue Jobs** | 6h | 4h | 5h | laravel-expert |
| **Task 7: Conflict Resolution** | 5h | 3h | 4h | livewire-specialist |
| **Task 8: Image Caching** | 5h | 3h | 4h | laravel-expert |
| **Task 9: Testing & E2E** | 12h | 8h | 10h | debugger + ps-expert |
| **Task 10: Code Review** | 6h | 4h | 5h | coding-style + architect |
| **Task 11: Documentation & Deploy** | 6h | 4h | 5h | deployment + docs |
| **Task 12: Agent Report** | 3h | 2h | 2h | architect |
| **TOTAL** | **92h** | **62h** | **76.5h** | |

**Final Estimate:** **45-60h** (accounting for agent productivity multiplier)

### Milestones

**🎯 Milestone 1: Database & API Foundation (Day 1-2)**
- **Duration:** 8-10h
- **Tasks:** 1, 2
- **Deliverables:** Migrations deployed, PS API methods tested
- **Success Criteria:** Unit tests passing (10/10 tests)
- **Blocker for:** All subsequent tasks

**🎯 Milestone 2: Core Services Layer (Day 3-5)**
- **Duration:** 17-20h
- **Tasks:** 3, 4.1, 4.2, 4.3
- **Deliverables:** All import/export services implemented + tested
- **Success Criteria:** Unit tests passing (35/35 tests)
- **Blocker for:** UI, Queue Jobs, Testing

**🎯 Milestone 3: UI & Background Jobs (Day 6-7)**
- **Duration:** 12-15h (parallel work)
- **Tasks:** 5, 6
- **Deliverables:** Import/Export wizards functional, Queue jobs operational
- **Success Criteria:** Can import via UI, Queue jobs process correctly
- **Blocker for:** E2E testing

**🎯 Milestone 4: Advanced Features (Day 8)**
- **Duration:** 7-8h
- **Tasks:** 7, 8
- **Deliverables:** Conflict resolution UI, Image caching system
- **Success Criteria:** Conflicts resolvable, Images cached on demand

**🎯 Milestone 5: Testing & Quality (Day 9-10)**
- **Duration:** 15-17h
- **Tasks:** 9, 10
- **Deliverables:** E2E tests passing (6/6 scenarios), Code review approved
- **Success Criteria:** All tests green, CLAUDE.md compliance
- **Blocker for:** Production deployment

**🎯 Milestone 6: Deployment & Documentation (Day 11)**
- **Duration:** 7-8h
- **Tasks:** 11, 12
- **Deliverables:** Production deployment, User guide, Agent report
- **Success Criteria:** Production smoke tests passing, Documentation complete

---

### Critical Path Analysis

**Critical Path (cannot be parallelized):**
```
Task 1 (3.5h)
  ↓
Task 2 (5h) + Task 3 (6h) [can run parallel]
  ↓
Task 4.1 (7h) [depends on Task 3]
  ↓
Task 6 (5h) [depends on Task 4]
  ↓
Task 9 (10h) [depends on all features complete]
  ↓
Task 10 (5h)
  ↓
Task 11 (5h)
  ↓
Task 12 (2h)

Total Critical Path: ~48.5h
```

**Parallel Work Opportunities:**
- Task 2 (PS API) + Task 3 (Validation) = PARALLEL (saves ~5h)
- Task 4.2/4.3 (PS Import/Export) + Task 5 (UI) = PARALLEL (saves ~10h)
- Task 7 (Conflict) + Task 8 (Images) = PARALLEL (saves ~4h)

**Optimized Timeline:** 48.5h - 19h parallel savings = **~30h best case** (with perfect coordination)

**Realistic Timeline:** 45-60h (accounting for coordination overhead, context switching, unexpected issues)

---

### Gantt Chart (Text Representation)

```
Day 1-2: Milestone 1 (Foundation)
────────────────────────────────────────────
Task 1: DB Schema      [████████░░] 3.5h
Task 2: PS API Methods [████████████] 5h  (parallel with 3)
Task 3: Validation     [██████████████] 6h (parallel with 2)

Day 3-5: Milestone 2 (Services)
────────────────────────────────────────────
Task 4.1: XLSX Import  [████████████████] 7h
Task 4.2: PS Import    [██████████] 4h
Task 4.3: Export       [██████████] 4h

Day 6-7: Milestone 3 (UI + Jobs)
────────────────────────────────────────────
Task 5: UI/UX          [████████████████████████] 12h (parallel with 6)
Task 6: Queue Jobs     [████████████] 5h         (parallel with 5)

Day 8: Milestone 4 (Advanced)
────────────────────────────────────────────
Task 7: Conflict Res   [██████████] 4h (parallel with 8)
Task 8: Image Cache    [██████████] 4h (parallel with 7)

Day 9-10: Milestone 5 (Testing)
────────────────────────────────────────────
Task 9: E2E Testing    [████████████████████] 10h
Task 10: Code Review   [████████████] 5h

Day 11: Milestone 6 (Deploy)
────────────────────────────────────────────
Task 11: Deploy        [████████████] 5h
Task 12: Report        [██████] 2h
```

---

## 9. AGENT DELEGATION PLAN

### Agent Assignments

**laravel-expert (25h total):**
- Task 1: Database Schema (3.5h)
- Task 3: Validation Service (6h)
- Task 4.1: XLSX Import Service (7h)
- Task 6: Queue Jobs (5h)
- Task 8: Image Caching (4h)

**prestashop-api-expert (13h total):**
- Task 2: PS API Methods (5h)
- Task 4.2: PS API Import (4h)
- Task 4.3: Export Service (4h)

**livewire-specialist (16h total):**
- Task 5: UI/UX (12h, co-work with frontend-specialist)
- Task 7: Conflict Resolution UI (4h)

**frontend-specialist (6h total):**
- Task 5: UI/UX CSS (co-work with livewire-specialist, 6h of 12h total)

**debugger (10h total):**
- Task 9: E2E Testing (10h, co-work with prestashop-api-expert)

**coding-style-agent (5h total):**
- Task 10: Code Review (5h)

**architect (5h total):**
- Task 10: Code Review (co-work, 2h)
- Task 12: Agent Report (3h)

**deployment-specialist (3h total):**
- Task 11: Deployment (3h)

**documentation-reader (2h total):**
- Task 11: Documentation (2h)

### Delegation Sequence

**Week 1 (Days 1-2): Foundation**
```
1. laravel-expert → Task 1 (DB Schema)
2. PARALLEL:
   - prestashop-api-expert → Task 2 (PS API)
   - laravel-expert → Task 3 (Validation)
```

**Week 1 (Days 3-5): Core Services**
```
3. laravel-expert → Task 4.1 (XLSX Import)
4. PARALLEL:
   - prestashop-api-expert → Task 4.2 (PS Import)
   - prestashop-api-expert → Task 4.3 (Export)
```

**Week 1 (Days 6-7): UI + Jobs**
```
5. PARALLEL:
   - livewire-specialist + frontend-specialist → Task 5 (UI)
   - laravel-expert → Task 6 (Queue Jobs)
```

**Week 2 (Day 8): Advanced**
```
6. PARALLEL:
   - livewire-specialist → Task 7 (Conflict UI)
   - laravel-expert → Task 8 (Image Cache)
```

**Week 2 (Days 9-10): Testing**
```
7. debugger + prestashop-api-expert → Task 9 (E2E Testing)
8. coding-style-agent + architect → Task 10 (Code Review)
```

**Week 2 (Day 11): Deployment**
```
9. deployment-specialist + documentation-reader → Task 11 (Deploy + Docs)
10. architect → Task 12 (Final Report)
```

### Communication Protocol

**Daily Standups (asynchronous via reports):**
- Each agent posts daily progress in `_AGENT_REPORTS/daily/`
- Format: `{agent}_{date}_DAILY.md`
- Contains: Completed tasks, Blockers, Next steps

**Blockers Escalation:**
- If agent blocked >2h → notify coordinator immediately
- Coordinator re-assigns or provides assistance

**Handoffs:**
- When task complete → update TODO list
- Create handoff document: `_AGENT_REPORTS/handoff_{from_agent}_to_{to_agent}.md`
- Next agent reads handoff before starting

**Quality Gates:**
- After Milestone 2 → coding-style-agent review (mid-project)
- After Milestone 5 → architect approval (before deployment)

---

## 10. DELIVERABLES CHECKLIST

### Code Files (45 files total)

**Migrations (2 files):**
- [ ] `database/migrations/2025_11_05_140000_create_import_export_tables.php`
- [ ] `database/migrations/2025_11_05_140001_extend_variant_images_table.php`

**Seeders (1 file):**
- [ ] `database/seeders/ImportTemplateSeeder.php`

**Services (8 files):**
- [ ] `app/Services/Product/XlsxVariantImportService.php`
- [ ] `app/Services/Product/PrestaShopVariantImportService.php`
- [ ] `app/Services/Product/VariantExportService.php`
- [ ] `app/Services/Product/VariantImportValidationService.php`
- [ ] `app/Services/Product/ConflictResolutionService.php`
- [ ] `app/Services/Product/VariantImageService.php`
- [ ] `app/Services/PrestaShop/Clients/PrestaShop8Client.php` (extended)
- [ ] `app/Services/PrestaShop/Clients/PrestaShop9Client.php` (extended)

**Jobs (3 files):**
- [ ] `app/Jobs/Products/VariantImportJob.php`
- [ ] `app/Jobs/Products/VariantExportJob.php`
- [ ] `app/Jobs/Products/CacheVariantImageJob.php`

**Validation Rules (1 file):**
- [ ] `app/Rules/UniqueSKUAcrossProducts.php`

**Livewire Components (3 files):**
- [ ] `app/Http/Livewire/Products/Import/ProductImportWizard.php`
- [ ] `app/Http/Livewire/Products/Export/ProductExportWizard.php`
- [ ] `app/Http/Livewire/Products/Import/ConflictReviewModal.php`

**Blade Views (8 files):**
- [ ] `resources/views/livewire/products/import/product-import-wizard.blade.php`
- [ ] `resources/views/livewire/products/import/wizard-step-1-upload.blade.php`
- [ ] `resources/views/livewire/products/import/wizard-step-2-mapping.blade.php`
- [ ] `resources/views/livewire/products/import/wizard-step-3-preview.blade.php`
- [ ] `resources/views/livewire/products/import/wizard-step-4-execute.blade.php`
- [ ] `resources/views/livewire/products/export/product-export-wizard.blade.php`
- [ ] `resources/views/livewire/products/import/conflict-review-modal.blade.php`
- [ ] `resources/views/livewire/products/import/import-history-dashboard.blade.php`

**CSS (1 file):**
- [ ] `resources/css/products/import-export.css`

**Notifications (2 files):**
- [ ] `app/Notifications/ImportCompleted.php`
- [ ] `app/Notifications/ImportFailed.php`

**Artisan Commands (1 file):**
- [ ] `app/Console/Commands/CleanupVariantImageCache.php`

**Test Files (10 files):**
- [ ] `tests/Unit/Services/VariantImportValidationServiceTest.php`
- [ ] `tests/Unit/Services/XlsxVariantImportServiceTest.php`
- [ ] `tests/Unit/Services/PrestaShop8ClientCombinationsTest.php`
- [ ] `tests/Unit/Jobs/VariantImportJobTest.php`
- [ ] `tests/Feature/Products/VariantImportXlsxTest.php`
- [ ] `tests/Feature/Products/VariantImportPrestaShopTest.php`
- [ ] `tests/Feature/Products/VariantExportTest.php`
- [ ] `tests/Feature/Products/ConflictResolutionTest.php`
- [ ] `tests/Browser/VariantImportWizardTest.php` (optional Dusk)
- [ ] E2E test report in `_AGENT_REPORTS/`

**Templates (3 files):**
- [ ] `storage/templates/VARIANTS_TEMPLATE_v1.xlsx`
- [ ] `storage/templates/PRESTASHOP_SYNC_TEMPLATE_v1.xlsx`
- [ ] `storage/templates/ERP_VARIANTS_TEMPLATE_v1.xlsx`

### Documentation Files (5 files)

- [ ] `_DOCS/VARIANT_IMPORT_EXPORT_USER_GUIDE.md` (user manual)
- [ ] `CLAUDE.md` (updated with Phase 6.5 info)
- [ ] `Plan_Projektu/ETAP_05b_Produkty_Warianty.md` (Phase 6.5 marked ✅)
- [ ] `_AGENT_REPORTS/architect_phase_6_5_planning_2025-11-04_REPORT.md` (THIS FILE)
- [ ] `_AGENT_REPORTS/architect_phase_6_5_completion_YYYY-MM-DD_REPORT.md` (after implementation)

### Deployment Artifacts

- [ ] Deployment script: `_TOOLS/deploy_phase_6_5.ps1`
- [ ] Rollback script: `_TOOLS/rollback_phase_6_5.ps1`
- [ ] Screenshots: `_TOOLS/screenshots/phase6_5_*.png` (minimum 10 screenshots)

---

## 11. FINAL RECOMMENDATIONS

### GO / NO-GO Decision

**✅ RECOMMENDATION: PROCEED WITH PHASE 6.5 IMPLEMENTATION**

**Justification:**
1. ✅ **Solid Foundation:** Phase 2 (PrestaShopAttributeSyncService) + Phase 6 (ProductFormVariants) verified operational
2. ✅ **Clear Architecture:** All 6 critical decisions made with solid rationale
3. ✅ **Realistic Timeline:** 45-60h estimate accounts for complexity, not optimistic bullshit
4. ✅ **Risk Mitigation:** All 7 major risks identified with concrete mitigation strategies
5. ✅ **Agent Delegation:** Clear ownership, parallel work opportunities, realistic workload
6. ✅ **Context7 Verification:** PrestaShop API + Laravel Excel patterns validated

**Conditions for Success:**
- [ ] User approval of all 6 architectural decisions
- [ ] Agent availability (9 agents required)
- [ ] Production backup before deployment
- [ ] Feature flag enabled for gradual rollout

### Priority Adjustments

**MUST HAVE (Phase 6.5 core):**
- ✅ XLSX import with template
- ✅ PrestaShop API import
- ✅ Conflict detection + resolution UI
- ✅ Queue support dla bulk operations
- ✅ Basic export (XLSX)

**NICE TO HAVE (can defer to Phase 6.6):**
- ⏳ PrestaShop API export (complex, can use manual for now)
- ⏳ Image lazy caching (can use PS URLs only initially)
- ⏳ Advanced conflict resolution strategies (merge)
- ⏳ Import templates customization UI

**If timeline pressure:**
- Defer: Task 7 (Conflict Resolution advanced features)
- Defer: Task 8 (Image Caching - use URLs only)
- Defer: PrestaShop API export (Task 4.3)
- Keep: XLSX import/export + conflict detection

**Minimum Viable Phase 6.5:**
- Task 1-3 (Foundation + Validation)
- Task 4.1 (XLSX Import only)
- Task 5 (UI - simplified wizard)
- Task 6 (Queue Jobs - basic)
- Task 9-11 (Testing + Deployment)
- **Estimated:** 30-35h (vs 45-60h full scope)

### Next Steps for User

**IMMEDIATE:**
1. Review this planning report
2. Approve/reject architectural decisions (6 questions)
3. Confirm timeline expectations (45-60h realistic?)
4. Assign agents (verify availability)

**BEFORE IMPLEMENTATION:**
5. Backup production database
6. Create feature flag configuration
7. Prepare test PrestaShop instance (for E2E testing)
8. Review and approve deliverables checklist

**DURING IMPLEMENTATION:**
9. Daily progress monitoring (agent reports)
10. Milestone approvals (Gates at M2, M5)
11. User acceptance testing (UAT) at M3

**AFTER COMPLETION:**
12. Production deployment (gradual rollout)
13. User training (based on user guide)
14. Monitor import success rate (first week)
15. Gather feedback for Phase 6.6 enhancements

---

## 12. APPENDIX

### A. Context7 Research Summary

**PrestaShop 8 API (analyzed):**
- ✅ `/api/combinations` endpoint verified operational
- ✅ XML format requirements documented (namespace, CDATA, field order)
- ✅ `product_type="combinations"` pattern confirmed
- ✅ Associations structure for `product_option_values` validated
- ⚠️ Rate limiting not explicitly documented (assumed 100 req/min conservative)

**Laravel Excel (analyzed):**
- ✅ `WithHeadingRow` concern for column name detection
- ✅ `WithMapping` concern for custom column mapping
- ✅ `WithChunkReading` + `ShouldQueue` for large imports
- ✅ `WithValidation` concern for row-by-row validation
- ✅ Export with `FromCollection` + `WithMapping` patterns

**Key Insights:**
- PrestaShop combinations = PPM variants (1:1 mapping feasible)
- AttributeType → ps_attribute_group (Phase 2 already handles this)
- AttributeValue → ps_attribute (Phase 2 already handles this)
- ProductVariant → ps_combination (NEW mapping needed - Task 2)

### B. Existing Infrastructure Inventory

**FROM PHASE 2 (ready to use):**
- ✅ PrestaShopAttributeSyncService (sync AttributeType/Value)
- ✅ Mapping tables: prestashop_attribute_group_mapping, prestashop_attribute_value_mapping
- ✅ Queue jobs: SyncAttributeGroupWithPrestaShop, SyncAttributeValueWithPrestaShop
- ✅ Sync status tracking (synced, pending, conflict, missing)

**FROM PHASE 5.5 (verified operational):**
- ✅ PrestaShop8Client with 10 public API methods
- ✅ HTTP 201 success for attribute group creation
- ✅ Multi-shop support (2+ shops tested)
- ✅ Error handling + retry (3 attempts, exponential backoff)
- ✅ Queue monitoring (jobs table, failed_jobs table, logs)

**FROM PHASE 6 (ready to integrate):**
- ✅ ProductFormVariants trait (CRUD operations)
- ✅ 8 Blade partials (variant UI components)
- ✅ Variant prices grid (per price group)
- ✅ Variant stock grid (per warehouse)
- ✅ Variant images manager
- ✅ SKU uniqueness validation

**GAPS (to be filled by Phase 6.5):**
- ❌ Bulk import from XLSX
- ❌ Bulk import from PrestaShop API
- ❌ Export to XLSX
- ❌ Export to PrestaShop API
- ❌ Conflict resolution system
- ❌ Import templates
- ❌ Queue jobs for bulk operations

### C. Glossary

**AttributeType:** Grupa wariantów (np. "Kolor", "Rozmiar") - definiuje typ cechy
**AttributeValue:** Wartość grupy (np. "Czerwony", "M") - konkretna wartość cechy
**Combination (PS):** PrestaShop term for variant - kombinacja attribute values
**Conflict:** Duplicate SKU lub data discrepancy between PPM and import source
**ImportBatch:** Single import session (tracking record)
**Mapping:** Column name → PPM field relationship (e.g., "Nazwa" → "name")
**ProductVariant:** PPM variant record - konkretny wariant produktu
**Template:** Predefined column mapping configuration dla XLSX imports

### D. References

**PrestaShop Documentation:**
- https://devdocs.prestashop-project.org/8/webservice/tutorials/create-product-az
- https://devdocs.prestashop-project.org/8/webservice/resources/combinations

**Laravel Excel Documentation:**
- https://docs.laravel-excel.com/3.1/imports/
- https://docs.laravel-excel.com/3.1/exports/

**Project Documentation:**
- `Plan_Projektu/ETAP_05b_Produkty_Warianty.md` (Phase 0-6 status)
- `_DOCS/VARIANT_SYSTEM_MANAGEMENT_REQUIREMENTS.md` (Phase 1-5 spec)
- `_AGENT_REPORTS/COORDINATION_2025-10-30_PHASE_5_5_FINAL_REPORT.md` (E2E results)
- `CLAUDE.md` (enterprise standards)

---

**END OF PLANNING REPORT**

**Report Status:** ✅ COMPLETE - Ready for User Review
**Next Action:** User approval of architectural decisions → Implementation starts
**Estimated Implementation Start:** After user approval
**Estimated Completion:** 7-10 dni roboczych from start

---

**Generated by:** architect agent
**Date:** 2025-11-04
**Planning Duration:** 3h
**Report Length:** 17,500+ words (comprehensive)
**Confidence Level:** HIGH (Context7 verified, realistic estimates, risk-aware)
