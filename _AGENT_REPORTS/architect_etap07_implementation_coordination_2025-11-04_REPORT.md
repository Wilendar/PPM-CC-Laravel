# RAPORT KOORDYNACJI IMPLEMENTACJI ETAP_07
**Data:** 2025-11-04 10:00
**Agent:** architect (Expert Planning Manager)
**Zadanie:** Strategia delegacji zadań ETAP_07 - PrestaShop API Integration

---

## 🎯 EXECUTIVE SUMMARY

### 📊 AKTUALNY STATUS

**COMPLETED:** 43h (FAZA 1-2, FAZA 3A)
**IN PROGRESS:** FAZA 3B-3C (7h remaining, 75% complete)
**NOT STARTED:** FAZA 4-5 (52-70h) + FAZA 6-9 (38-47h future)

### 💡 STRATEGIC DECISION: **PARALLEL TRACK STRATEGY**

**Reasoning:**
1. **FAZA 3 completion** (7h) = Infrastructure testing & monitoring - can run PARALLEL with FAZA 5 foundation
2. **FAZA 5 MVP** = 12 independent tasks - supports multi-agent parallel work
3. **User urgency** - MVP required ASAP, cannot wait 7h for FAZA 3 completion
4. **Resource optimization** - 5+ agents available, avoid sequential bottleneck

**RECOMMENDATION:**
- ✅ Start FAZA 5 Tasks 1-2-3 **IMMEDIATELY** (parallel with FAZA 3 completion)
- ✅ Complete FAZA 3B-3C as Track A (1 day)
- ✅ Execute FAZA 5 as Tracks B-C-D (7-10 days)
- ❌ Skip FAZA 4 for now (defer to post-MVP phase)

### ⏰ TIMELINE

**Total Estimated Time:** 55-70h (9-12 dni roboczych, 1 developer equivalent)
**Parallel Execution:** 7-10 dni kalendarzowych (multi-agent)

**Milestones:**
- **Day 1:** FAZA 3 completion + FAZA 5 foundation (Tracks A+B start)
- **Day 3-5:** FAZA 5 core services + UI (Track C)
- **Day 7-10:** FAZA 5 testing + deployment (Track D)

### 🔥 CRITICAL PATH DEPENDENCIES

```
FAZA 3B.3-3B.4 (testing) ─────────────────────┐
                                               ├──→ FAZA 5.11 (deployment)
FAZA 5.1 (DB) ──→ FAZA 5.4 (services) ──→ FAZA 5.9 (testing) ─┘
        ↓                 ↓
    FAZA 5.3 (validation) FAZA 5.5 (UI)
        ↓                 ↓
    FAZA 5.2 (API)    FAZA 5.6 (queue)
```

**Critical Path:** FAZA 5.1 → 5.4 → 5.9 → 5.11 (database → services → testing → deployment)

---

## 📋 AGENT DELEGATION MATRIX

### 🏗️ TRACK A: INFRASTRUCTURE COMPLETION (FAZA 3)

**Goal:** Complete FAZA 3B-3C (testing, monitoring, optimization)
**Duration:** 7h (1 dzień)
**Can Run Parallel With:** Track B (FAZA 5 foundation)

| Agent | Task | Duration | Dependencies | Can Run In Parallel With |
|-------|------|----------|--------------|--------------------------|
| **debugger** | 3B.3 Sync Logic Verification | 1-2h | FAZA 3B.2 (done ✅) | Track B all tasks |
| **laravel-expert** | 3B.4 Product Sync Status Update | 1-2h | 3B.3 complete | Track B: 5.2 (prestashop-api-expert) |
| **laravel-expert** | 3C.1 Queue Health Monitoring | 1-2h | None | Track B all tasks |
| **laravel-expert** | 3C.2 Performance Optimization | 1-2h | 3C.1 complete | Track B: 5.2 (prestashop-api-expert) |
| **debugger** | 3C.3 Error Recovery | 1-2h | 3C.2 complete | Track B: 5.2 (prestashop-api-expert) |

**Deliverables:**
- ✅ SyncProductToPrestaShop job verified + tested
- ✅ ProductTransformer::toPrestaShop() tested
- ✅ UI refresh post-sync working
- ✅ Dashboard queue widgets operational
- ✅ Rate limiting + retry logic implemented
- ✅ Manual retry button w UI

**Total Track A:** 5-10h (sequential), but PARALLELIZABLE with Track B (Day 1)

---

### 🏗️ TRACK B: MVP FOUNDATION (FAZA 5 Tasks 1-3)

**Goal:** Database schema, API methods, validation layer
**Duration:** 12-15h (2 dni, parallel execution)
**Can Run Parallel With:** Track A (FAZA 3 completion)

| Agent | Task | Duration | Dependencies | Can Run In Parallel With |
|-------|------|----------|--------------|--------------------------|
| **laravel-expert** | 5.1 Database Schema Extensions | 3-4h | None | All Track A + 5.2 + 5.3 |
| **prestashop-api-expert** | 5.2 PrestaShop API Methods Extension | 4-5h | None | All Track A + 5.1 + 5.3 |
| **laravel-expert** | 5.3 Validation Service Layer | 5-6h | None | All Track A + 5.1 + 5.2 |

**Deliverables Track B:**
- ✅ 5 migrations deployed + verified locally (import_batches, import_templates, conflict_logs, export_batches, variant_images extension)
- ✅ PrestaShop8Client extended with 4 new methods (combinations CRUD)
- ✅ VariantImportValidationService with 15+ test cases
- ✅ Unit tests passing for all new code

**Total Track B:** 12-15h (parallel execution: ~5-6h wall-clock if 3 agents)

**⚠️ CRITICAL:** Track B MUST complete before Track C can start!

---

### 🏗️ TRACK C: MVP CORE IMPLEMENTATION (FAZA 5 Tasks 4-5)

**Goal:** Import/Export services + UI/UX
**Duration:** 22-27h (3-4 dni, parallel execution)
**Dependencies:** Track B complete (database + API methods exist)

| Agent | Task | Duration | Dependencies | Can Run In Parallel With |
|-------|------|----------|--------------|--------------------------|
| **laravel-expert** | 5.4.1 XlsxVariantImportService | 6-7h | Track B (5.3 validation) | 5.4.2 (prestashop), 5.5 (livewire) 50% |
| **prestashop-api-expert** | 5.4.2 PrestaShopVariantImportService | 3-4h | Track B (5.2 API, 5.3 validation) | 5.4.1 (laravel), 5.5 (livewire) |
| **laravel-expert** + **prestashop-api-expert** | 5.4.3 VariantExportService | 3-4h | 5.4.1 + 5.4.2 complete | 5.5 (livewire) 50% |
| **livewire-specialist** | 5.5.1 Import Wizard Component | 4h | 5.4.1 complete | 5.5.2, 5.5.3, 5.5.4 |
| **livewire-specialist** | 5.5.2 Export Wizard Component | 3h | 5.4.3 complete | 5.5.1, 5.5.3, 5.5.4 |
| **livewire-specialist** + **laravel-expert** | 5.5.3 Conflict Resolution Panel | 3h | Track B (conflict_logs) | 5.5.1, 5.5.2, 5.5.4 |
| **livewire-specialist** | 5.5.4 Progress Tracking Component | 2h | Track B (import_batches) | 5.5.1, 5.5.2, 5.5.3 |
| **frontend-specialist** | 5.5.5 Frontend Verification (MANDATORY) | 1h | 5.5.1-5.5.4 complete | None (final step) |

**Deliverables Track C:**
- ✅ 3 service classes operational (XlsxVariantImportService, PrestaShopVariantImportService, VariantExportService)
- ✅ 4 Livewire components + Blade views deployed
- ✅ Import/Export wizards functional (XLSX + PrestaShop API)
- ✅ Conflict resolution panel operational
- ✅ Progress tracking real-time
- ✅ Frontend verified with screenshots

**Total Track C:** 22-27h (parallel execution: ~10-12h wall-clock if 3 agents)

---

### 🏗️ TRACK D: MVP COMPLETION (FAZA 5 Tasks 6-12)

**Goal:** Queue jobs, conflict resolution, testing, deployment
**Duration:** 18-20h (2-3 dni, orchestrated execution)
**Dependencies:** Track C complete (services + UI exist)

| Agent | Task | Duration | Dependencies | Can Run In Parallel With |
|-------|------|----------|--------------|--------------------------|
| **laravel-expert** | 5.6 Queue Jobs Implementation | 4-5h | Track C (services) | 5.7 (livewire) 50%, 5.8 (laravel) |
| **livewire-specialist** | 5.7 Conflict Resolution System | 3-4h | Track B (conflict_logs), 5.5.3 (UI) | 5.6 (laravel) 50%, 5.8 (laravel) |
| **laravel-expert** | 5.8 Image Lazy Caching System | 3-4h | Track B (variant_images ext) | 5.6, 5.7 |
| **debugger** + **coding-style-agent** | 5.9 Testing & E2E Verification | 8-10h | 5.6-5.7-5.8 complete | None (sequential) |
| **coding-style-agent** + **architect** | 5.10 Integration & Code Review | 4-5h | 5.9 (tests passing) | None (sequential) |
| **deployment-specialist** | 5.11 Documentation & Deployment | 4-5h | 5.10 (review passed) | None (sequential) |
| **architect** | 5.12 Agent Report & Knowledge Transfer | 2h | 5.11 (deployment done) | None (sequential) |

**Deliverables Track D:**
- ✅ 3 queue jobs operational (VariantImportJob, VariantExportJob, CacheVariantImageJob)
- ✅ ConflictResolutionService with 4 strategies
- ✅ VariantImageService + cleanup command
- ✅ 6 E2E scenarios PASSED (35+ unit tests, 23+ feature tests)
- ✅ Code review approved (file size <300 linii, no hardcoding)
- ✅ Production deployment complete + verified
- ✅ User guide published (_DOCS/USER_GUIDE_VARIANT_IMPORT_EXPORT.md)
- ✅ Agent reports consolidated

**Total Track D:** 18-20h (sequential execution due to dependencies)

---

## 🎯 EXECUTION PHASES (TIMELINE)

### **PHASE 0: PRE-FLIGHT CHECKS (1h) - DAY 0**

**Agent:** architect + Context7 integration

**Tasks:**
1. ✅ Context7 docs lookup: Laravel 12.x queue patterns
2. ✅ Context7 docs lookup: Livewire 3.x wizard components
3. ✅ Context7 docs lookup: PrestaShop 8.x combinations API
4. ✅ Verify ETAP_05a SEKCJA 0-4 completion status
5. ✅ Verify Phase 6 Wave 0-4 completion status
6. ✅ Brief top 3 agents (laravel-expert, prestashop-api-expert, debugger)

**Checkpoint:** All agents have Context7-verified patterns + ready to start

---

### **PHASE 1: PARALLEL FOUNDATION (Day 1-2) - 12-15h wall-clock**

**Goal:** Complete Track A (FAZA 3) + Track B (FAZA 5 foundation) in parallel

**Day 1 Morning (4h):**
- **laravel-expert** → 5.1 Database Schema Extensions (3-4h) **[PRIORITY 1]**
- **prestashop-api-expert** → 5.2 PrestaShop API Methods Extension (start, 4h total) **[PRIORITY 2]**
- **debugger** → 3B.3 Sync Logic Verification (1-2h) **[PRIORITY 3]**

**Day 1 Afternoon (4h):**
- **laravel-expert** → 5.3 Validation Service Layer (start, 5-6h total)
- **prestashop-api-expert** → 5.2 continued (finish)
- **laravel-expert** (second) → 3B.4 Product Sync Status Update (1-2h)

**Day 2 Morning (4h):**
- **laravel-expert** → 5.3 Validation Service Layer (finish)
- **laravel-expert** (second) → 3C.1 Queue Health Monitoring (1-2h)
- **debugger** → Verify Track B outputs (migrations, API methods, validation tests)

**Day 2 Afternoon (3h):**
- **laravel-expert** → 3C.2 Performance Optimization (1-2h)
- **debugger** → 3C.3 Error Recovery (1-2h)

**Checkpoint Day 2 End:**
- ✅ Track A (FAZA 3) = **100% COMPLETE**
- ✅ Track B (FAZA 5 Tasks 1-3) = **100% COMPLETE**
- ✅ 5 migrations deployed locally + tested
- ✅ PrestaShop8Client extended + unit tested
- ✅ VariantImportValidationService operational + 15 tests passing
- ✅ Queue monitoring dashboard widgets working
- ✅ Ready for Track C (services + UI implementation)

**⚠️ DECISION GATE:** Architect reviews Track B quality before Track C start!

---

### **PHASE 2: CORE SERVICES & UI (Day 3-5) - 22-27h wall-clock**

**Goal:** Complete Track C (FAZA 5 Tasks 4-5) - Import/Export services + UI/UX

**Day 3 (8h):**
- **laravel-expert** → 5.4.1 XlsxVariantImportService (6-7h start)
- **prestashop-api-expert** → 5.4.2 PrestaShopVariantImportService (3-4h complete)
- **livewire-specialist** → 5.5.4 Progress Tracking Component (2h) - can start early (only needs import_batches table)

**Day 4 (8h):**
- **laravel-expert** → 5.4.1 XlsxVariantImportService (finish)
- **laravel-expert** + **prestashop-api-expert** → 5.4.3 VariantExportService (3-4h)
- **livewire-specialist** → 5.5.1 Import Wizard Component (4h start)

**Day 5 (6h):**
- **livewire-specialist** → 5.5.1 Import Wizard Component (finish)
- **livewire-specialist** → 5.5.2 Export Wizard Component (3h)
- **livewire-specialist** + **laravel-expert** → 5.5.3 Conflict Resolution Panel (3h start)

**Day 5 Evening (2h):**
- **livewire-specialist** → 5.5.3 Conflict Resolution Panel (finish)
- **frontend-specialist** → 5.5.5 Frontend Verification (1h) **MANDATORY**

**Checkpoint Day 5 End:**
- ✅ Track C (FAZA 5 Tasks 4-5) = **100% COMPLETE**
- ✅ 3 service classes deployed + tested
- ✅ 4 Livewire components + Blade views operational
- ✅ Import/Export wizards functional (manual testing passed)
- ✅ Frontend verified with screenshots
- ✅ Ready for Track D (queue jobs + testing)

**⚠️ DECISION GATE:** coding-style-agent pre-review before Track D start!

---

### **PHASE 3: QUEUE JOBS & ADVANCED FEATURES (Day 6-7) - 10-13h wall-clock**

**Goal:** Complete Track D Tasks 6-7-8 (Queue jobs, conflict resolution, image caching)

**Day 6 (8h):**
- **laravel-expert** → 5.6 Queue Jobs Implementation (4-5h)
- **livewire-specialist** → 5.7 Conflict Resolution System (3-4h) - parallel 50%
- **laravel-expert** (second) → 5.8 Image Lazy Caching System (start, 3-4h total)

**Day 7 (4h):**
- **laravel-expert** → 5.8 Image Lazy Caching System (finish)
- **debugger** → Verify queue jobs functionality (manual test)

**Checkpoint Day 7 End:**
- ✅ 3 queue jobs operational + tested
- ✅ ConflictResolutionService with 4 strategies
- ✅ VariantImageService + cleanup command
- ✅ Manual testing passed (import >50 products → queue triggered)
- ✅ Ready for E2E testing

---

### **PHASE 4: TESTING & QUALITY (Day 8-9) - 12-15h wall-clock**

**Goal:** Complete Track D Task 9-10 (E2E testing + code review)

**Day 8 (8h):**
- **debugger** → 5.9 Testing & E2E Verification (start, 8-10h total)
  - Scenario 1: XLSX Import - Happy Path (1.5h)
  - Scenario 2: XLSX Import - Duplicate SKU Conflict (1.5h)
  - Scenario 3: PrestaShop API Import (2h)
  - Scenario 4: XLSX Export (1.5h)

**Day 9 (5h):**
- **debugger** → 5.9 continued (finish)
  - Scenario 5: PrestaShop API Export (2h)
  - Scenario 6: Queue Jobs - Large Import (1.5h)
- **coding-style-agent** → 5.10 Integration & Code Review (start, 4-5h total)

**Day 9 Afternoon (3h):**
- **coding-style-agent** + **architect** → 5.10 continued (finish)
  - File size compliance check
  - Separation of concerns review
  - Performance review
  - Security review

**Checkpoint Day 9 End:**
- ✅ All 6 E2E scenarios PASSED
- ✅ 35+ unit tests passing
- ✅ 23+ feature tests passing
- ✅ Code review approved
- ✅ Refactoring complete (if any files >300 linii)
- ✅ Ready for deployment

**⚠️ GO/NO-GO GATE:** Architect final approval before production deployment!

---

### **PHASE 5: DEPLOYMENT & DOCUMENTATION (Day 10-11) - 6-7h wall-clock**

**Goal:** Complete Track D Task 11-12 (Deployment + agent reports)

**Day 10 (5h):**
- **deployment-specialist** → 5.11 Documentation & Deployment
  - Database backup production (30 min)
  - Deploy 5 migrations (1h)
  - Deploy services + Livewire components (1.5h)
  - Clear cache + verify routes (30 min)
  - Post-deployment verification (frontend-verification skill) (1h)
  - Create user guide (_DOCS/USER_GUIDE_VARIANT_IMPORT_EXPORT.md) (1h)

**Day 11 (2h):**
- **architect** → 5.12 Agent Report & Knowledge Transfer
  - Consolidate all agent reports
  - Create Phase 6.5 completion report
  - Document lessons learned
  - Update knowledge base (_ISSUES_FIXES/ if needed)
  - Prepare handoff notes

**Checkpoint Day 11 End:**
- ✅ Production deployment complete + verified
- ✅ User guide published
- ✅ All agent reports in _AGENT_REPORTS/
- ✅ CLAUDE.md updated (if new patterns)
- ✅ Plan_Projektu/ETAP_07_Prestashop_API.md updated (FAZA 5 marked ✅)
- ✅ **FAZA 5 = 100% COMPLETE**

---

## 📝 DETAILED AGENT BRIEFS (TOP 3 PRIORITY)

### 🥇 AGENT BRIEF #1: laravel-expert

**Task:** FAZA 5.1 Database Schema Extensions
**Priority:** 🔴 CRITICAL (blocks all other FAZA 5 work)
**Duration:** 3-4h
**Dependencies:** None (can start immediately)
**Can Run In Parallel With:** 5.2 (prestashop-api-expert), 5.3 (self), Track A all tasks

---

#### **OBJECTIVE:**

Create 5 new migration files + extend 1 existing table dla FAZA 5 Variant Import/Export System.

---

#### **CONTEXT7 REQUIREMENTS (MANDATORY):**

Before starting, use Context7 MCP to verify Laravel 12.x migration patterns:

```bash
# Step 1: Resolve Laravel docs
mcp__context7__resolve-library-id "Laravel 12.x"
# Expected: /websites/laravel_12_x

# Step 2: Get migration patterns
mcp__context7__get-library-docs
  library_id: "/websites/laravel_12_x"
  topic: "database migrations, foreign keys, JSON columns, indexes"
  tokens: 3000
```

**MUST VERIFY:**
- ✅ Laravel 12.x JSON column syntax
- ✅ Foreign key constraint patterns
- ✅ Index naming conventions
- ✅ Timestamp column best practices
- ✅ Migration rollback safety

---

#### **DELIVERABLES (6 files):**

**1. migration: `import_batches` table**

**File:** `database/migrations/2025_11_04_100001_create_import_batches_table.php`

**Schema:**
```php
Schema::create('import_batches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->enum('import_type', ['xlsx', 'prestashop_api']);
    $table->string('filename', 255)->nullable(); // dla XLSX
    $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('set null'); // dla PrestaShop API
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->integer('total_rows')->unsigned()->default(0);
    $table->integer('processed_rows')->unsigned()->default(0);
    $table->integer('imported_products')->unsigned()->default(0);
    $table->integer('failed_products')->unsigned()->default(0);
    $table->integer('conflicts_count')->unsigned()->default(0);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index('created_at');
});
```

**IMPORTANT:**
- Use `enum()` not `string()` for status/type fields (enterprise data integrity)
- ALL counters = `unsigned()` + `default(0)` (no negative counts)
- `error_message` = `text()` not `string()` (long error messages)
- Index on `(user_id, status)` for dashboard queries
- Index on `created_at` for date range filters

---

**2. migration: `import_templates` table**

**File:** `database/migrations/2025_11_04_100002_create_import_templates_table.php`

**Schema:**
```php
Schema::create('import_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->json('mapping_config'); // Format: {"excel_column": "product_field"}
    $table->boolean('is_shared')->default(false); // shared across all users?
    $table->integer('usage_count')->unsigned()->default(0);
    $table->timestamps();

    $table->index(['user_id', 'is_shared']);
    $table->index('usage_count'); // dla sorting by popularity
});
```

**IMPORTANT:**
- `mapping_config` = `json()` column (complex nested mapping)
- `is_shared` dla admin-created templates (available to all users)
- `usage_count` dla tracking popular templates (show in UI first)

---

**3. migration: `conflict_logs` table**

**File:** `database/migrations/2025_11_04_100003_create_conflict_logs_table.php`

**Schema:**
```php
Schema::create('conflict_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('import_batch_id')->constrained('import_batches')->onDelete('cascade');
    $table->string('sku', 100); // conflicting SKU
    $table->enum('conflict_type', ['duplicate_sku', 'data_mismatch', 'validation_failed']);
    $table->json('existing_data'); // current product data
    $table->json('new_data'); // imported product data
    $table->enum('resolution_status', ['pending', 'resolved', 'ignored'])->default('pending');
    $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('resolved_at')->nullable();
    $table->text('resolution_notes')->nullable();
    $table->timestamps();

    $table->index(['import_batch_id', 'resolution_status']);
    $table->index('sku'); // search conflicts by SKU
});
```

**IMPORTANT:**
- Link to `import_batch_id` (group conflicts per import session)
- Store BOTH `existing_data` + `new_data` (side-by-side comparison UI)
- `resolution_status` dla tracking which conflicts resolved
- `resolution_notes` dla audit trail (why user chose specific strategy)

---

**4. migration: `export_batches` table**

**File:** `database/migrations/2025_11_04_100004_create_export_batches_table.php`

**Schema:**
```php
Schema::create('export_batches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->enum('export_type', ['xlsx', 'prestashop_api']);
    $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('set null'); // dla PrestaShop export
    $table->string('filename', 255)->nullable(); // dla XLSX
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->integer('total_products')->unsigned()->default(0);
    $table->integer('exported_products')->unsigned()->default(0);
    $table->integer('failed_products')->unsigned()->default(0);
    $table->json('filters')->nullable(); // Export filters (category, shop, has_variants, etc.)
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index('created_at');
});
```

**IMPORTANT:**
- Similar structure to `import_batches` (consistency)
- `filters` = `json()` column (complex filter conditions)
- `filename` dla XLSX exports (download link)

---

**5. migration: `extend_variant_images_table`**

**File:** `database/migrations/2025_11_04_100005_extend_variant_images_table.php`

**Schema:**
```php
Schema::table('variant_images', function (Blueprint $table) {
    $table->string('image_url', 500)->nullable()->after('image_path'); // PrestaShop URL
    $table->boolean('is_cached')->default(false)->after('image_url'); // Cached locally?
    $table->string('cache_path', 255)->nullable()->after('is_cached'); // Local cache path
    $table->timestamp('cached_at')->nullable()->after('cache_path'); // Cache timestamp

    $table->index('is_cached'); // Query cached vs non-cached images
});
```

**IMPORTANT:**
- Add columns AFTER specific existing columns (deterministic order)
- `image_url` = 500 chars (long PrestaShop URLs)
- `is_cached` index dla filtering uncached images (lazy cache job)

---

**6. Eloquent Models (NEW - if not exist, UPDATE if exist):**

**⚠️ CHECK FIRST:** Verify if these models already exist:

```bash
# Check existing models
ls app/Models/ImportBatch.php
ls app/Models/ImportTemplate.php
ls app/Models/ConflictLog.php
ls app/Models/ExportBatch.php
```

**If NOT exist, create:**

**File:** `app/Models/ImportBatch.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'user_id', 'import_type', 'filename', 'shop_id', 'status',
        'total_rows', 'processed_rows', 'imported_products', 'failed_products',
        'conflicts_count', 'started_at', 'completed_at', 'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'imported_products' => 'integer',
        'failed_products' => 'integer',
        'conflicts_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ConflictLog::class);
    }

    // Helper: Is import still running?
    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    // Helper: Progress percentage
    public function progressPercentage(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
```

**Similar models for:** `ImportTemplate.php`, `ConflictLog.php`, `ExportBatch.php`

**⚠️ FILE SIZE RULE:** Each model max 150 linii (use traits if logic grows)

---

#### **ACCEPTANCE CRITERIA:**

**✅ MUST PASS:**

1. **Migrations run successfully:**
   ```powershell
   php artisan migrate
   # No errors, all 5 tables + 4 columns created
   ```

2. **Database schema verification:**
   ```sql
   DESCRIBE import_batches;
   DESCRIBE import_templates;
   DESCRIBE conflict_logs;
   DESCRIBE export_batches;
   DESCRIBE variant_images; -- should have 4 new columns
   ```

3. **Eloquent models functional:**
   ```php
   php artisan tinker
   > ImportBatch::create(['user_id' => 1, 'import_type' => 'xlsx', 'status' => 'pending']);
   > ImportBatch::first()->conflicts; // should return empty collection
   ```

4. **Rollback safety:**
   ```powershell
   php artisan migrate:rollback --step=5
   # All 5 tables dropped, variant_images columns removed
   ```

5. **Code style compliance:**
   - ✅ No hardcoded values (all configurable)
   - ✅ File size <300 linii per migration
   - ✅ Proper foreign key constraints
   - ✅ Index on frequently queried columns

---

#### **CONTEXT FOR NEXT AGENT:**

After completing this task, provide:

1. **Summary:** "5 migrations + 1 extension deployed locally + 4 Eloquent models created. All tables verified w `php artisan tinker`."
2. **Files created:** List absolute paths (for next agent imports)
3. **Schema decisions:** Document any deviations from spec (e.g., column name changes)
4. **Blockers:** Any issues encountered (foreign key constraints, existing table conflicts)

**RAPORT:** `_AGENT_REPORTS/laravel_expert_faza5_task1_database_schema_2025-11-04_REPORT.md`

---

### 🥈 AGENT BRIEF #2: prestashop-api-expert

**Task:** FAZA 5.2 PrestaShop API Methods Extension
**Priority:** 🔴 CRITICAL (blocks Task 4.2 PrestaShop import)
**Duration:** 4-5h
**Dependencies:** None (can start immediately, parallel with 5.1)
**Can Run In Parallel With:** 5.1 (laravel-expert), 5.3 (laravel-expert), Track A all tasks

---

#### **OBJECTIVE:**

Extend `PrestaShop8Client` with 4 new methods dla PrestaShop Combinations API (product variants CRUD).

---

#### **CONTEXT7 REQUIREMENTS (MANDATORY):**

Before starting, use Context7 MCP to verify PrestaShop API patterns:

```bash
# Step 1: Resolve PrestaShop docs
mcp__context7__resolve-library-id "PrestaShop 8.x API"
# Expected: /prestashop/docs

# Step 2: Get combinations API docs
mcp__context7__get-library-docs
  library_id: "/prestashop/docs"
  topic: "combinations API, product attributes, ps_product_attribute, XML endpoints"
  tokens: 3000
```

**MUST VERIFY:**
- ✅ PrestaShop 8.x combinations API endpoints
- ✅ XML request/response format dla combinations
- ✅ Associations structure (attributes, values)
- ✅ Error handling patterns (404, 401, 500)
- ✅ Multi-store support (id_shop parameter)

---

#### **DELIVERABLES:**

**File to extend:** `app/Services/PrestaShop/PrestaShop8Client.php`

**⚠️ CRITICAL:** Read existing file first to understand structure!

```bash
# Step 1: Read existing PrestaShop8Client
Read app/Services/PrestaShop/PrestaShop8Client.php
```

**Expected existing methods:**
- `getProducts($filters)` - fetch products
- `getCategories($filters)` - fetch categories
- `createProduct($data)` - create product
- `updateProduct($id, $data)` - update product
- etc.

---

#### **METHOD 1: getProductCombinations()**

**Purpose:** Fetch all combinations (variants) dla specific product

**Signature:**
```php
/**
 * Get all combinations (variants) for a product
 *
 * @param int $productId PrestaShop product ID
 * @param int|null $shopId Shop ID (null = default shop)
 * @return array<int, array> Array of combination data
 * @throws PrestaShopApiException
 */
public function getProductCombinations(int $productId, ?int $shopId = null): array
```

**Implementation:**
```php
public function getProductCombinations(int $productId, ?int $shopId = null): array
{
    // Build URL: /api/combinations?filter[id_product]={productId}&display=full
    $url = $this->baseUrl . '/api/combinations';
    $params = [
        'filter[id_product]' => $productId,
        'display' => 'full',
    ];

    if ($shopId) {
        $params['id_shop'] = $shopId;
    }

    // Make GET request
    $response = $this->makeRequest('GET', $url, ['query' => $params]);

    // Parse XML response
    $xml = simplexml_load_string($response);

    // Handle empty result (no combinations)
    if (!isset($xml->combinations->combination)) {
        return [];
    }

    // Convert XML to array
    $combinations = [];
    foreach ($xml->combinations->combination as $combo) {
        $combinations[] = [
            'id_product_attribute' => (int) $combo->id,
            'id_product' => (int) $combo->id_product,
            'reference' => (string) $combo->reference,
            'ean13' => (string) $combo->ean13,
            'quantity' => (int) $combo->quantity,
            'price' => (float) $combo->price, // differential price
            'minimal_quantity' => (int) $combo->minimal_quantity,
            'default_on' => (bool) $combo->default_on,
            'attributes' => $this->parseAttributeAssociations($combo->associations->product_option_values ?? null),
        ];
    }

    return $combinations;
}

/**
 * Parse attribute associations from XML
 *
 * @param \SimpleXMLElement|null $attributesXml
 * @return array<int, int> Array of [id_attribute_group => id_attribute]
 */
private function parseAttributeAssociations(?\SimpleXMLElement $attributesXml): array
{
    if (!$attributesXml) {
        return [];
    }

    $attributes = [];
    foreach ($attributesXml->product_option_value as $attrValue) {
        $attributes[] = (int) $attrValue->id;
    }

    return $attributes;
}
```

**Unit Test (MANDATORY):**

**File:** `tests/Unit/Services/PrestaShop8ClientCombinationsTest.php`

```php
public function test_get_product_combinations_returns_array()
{
    $client = $this->createMockClient();
    $combinations = $client->getProductCombinations(456, 1);

    $this->assertIsArray($combinations);
    $this->assertArrayHasKey('id_product_attribute', $combinations[0]);
    $this->assertArrayHasKey('reference', $combinations[0]);
    $this->assertArrayHasKey('attributes', $combinations[0]);
}

public function test_get_product_combinations_handles_empty_result()
{
    $client = $this->createMockClientWithEmptyResponse();
    $combinations = $client->getProductCombinations(999, 1);

    $this->assertIsArray($combinations);
    $this->assertEmpty($combinations);
}
```

---

#### **METHOD 2: createProductWithCombinations()**

**Purpose:** Create new product WITH combinations (variants) in single API call

**Signature:**
```php
/**
 * Create product with combinations (variants)
 *
 * @param array $productData Product base data
 * @param array<int, array> $combinations Array of combination data
 * @param int|null $shopId Shop ID (null = default shop)
 * @return int Created product ID
 * @throws PrestaShopApiException
 */
public function createProductWithCombinations(array $productData, array $combinations, ?int $shopId = null): int
```

**Implementation:**
```php
public function createProductWithCombinations(array $productData, array $combinations, ?int $shopId = null): int
{
    // STEP 1: Set product type to "combinations"
    $productData['type'] = 'combinations'; // CRITICAL!

    // STEP 2: Build combinations XML
    $combinationsXml = '<combinations>';
    foreach ($combinations as $combo) {
        $combinationsXml .= '<combination>';
        $combinationsXml .= '<id_product_attribute><![CDATA[0]]></id_product_attribute>'; // 0 = new
        $combinationsXml .= '<reference><![CDATA[' . ($combo['reference'] ?? '') . ']]></reference>';
        $combinationsXml .= '<ean13><![CDATA[' . ($combo['ean13'] ?? '') . ']]></ean13>';
        $combinationsXml .= '<quantity><![CDATA[' . ($combo['quantity'] ?? 0) . ']]></quantity>';
        $combinationsXml .= '<price><![CDATA[' . ($combo['price'] ?? 0) . ']]></price>';

        // Attributes association
        if (!empty($combo['attributes'])) {
            $combinationsXml .= '<associations><product_option_values>';
            foreach ($combo['attributes'] as $attrId) {
                $combinationsXml .= '<product_option_value><id>' . $attrId . '</id></product_option_value>';
            }
            $combinationsXml .= '</product_option_values></associations>';
        }

        $combinationsXml .= '</combination>';
    }
    $combinationsXml .= '</combinations>';

    // STEP 3: Add combinations to product data
    $productData['associations']['combinations'] = $combinationsXml;

    // STEP 4: Create product via existing createProduct() method
    $productId = $this->createProduct($productData, $shopId);

    return $productId;
}
```

**⚠️ CRITICAL GOTCHAS:**
1. **product.type MUST = "combinations"** (not "simple")
2. **id_product_attribute = 0** dla new combinations
3. **Attributes = PrestaShop attribute IDs** (not PPM attribute_value_id)
4. **Price = differential** (offset from base product price, not absolute)

**Unit Test:**
```php
public function test_create_product_with_combinations_sets_type_combinations()
{
    $client = $this->createMockClient();
    $productData = ['name' => 'Test Product'];
    $combinations = [
        ['reference' => 'TEST-RED-XL', 'quantity' => 10, 'attributes' => [15, 28]],
    ];

    $productId = $client->createProductWithCombinations($productData, $combinations, 1);

    $this->assertIsInt($productId);
    $this->assertGreaterThan(0, $productId);
}
```

---

#### **METHOD 3: updateCombination()**

**Purpose:** Update existing combination (price, quantity, reference, etc.)

**Signature:**
```php
/**
 * Update existing combination
 *
 * @param int $combinationId PrestaShop combination ID (id_product_attribute)
 * @param array $data Combination data to update
 * @param int|null $shopId Shop ID (null = default shop)
 * @return bool Success status
 * @throws PrestaShopApiException
 */
public function updateCombination(int $combinationId, array $data, ?int $shopId = null): bool
```

**Implementation:**
```php
public function updateCombination(int $combinationId, array $data, ?int $shopId = null): bool
{
    // Build URL: /api/combinations/{id}
    $url = $this->baseUrl . '/api/combinations/' . $combinationId;

    // Add shop param if provided
    if ($shopId) {
        $url .= '?id_shop=' . $shopId;
    }

    // Build XML payload
    $xml = '<prestashop><combination>';
    $xml .= '<id><![CDATA[' . $combinationId . ']]></id>';

    foreach ($data as $key => $value) {
        $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
    }

    $xml .= '</combination></prestashop>';

    // Make PUT request
    $response = $this->makeRequest('PUT', $url, ['body' => $xml]);

    // Verify response (PrestaShop returns updated XML)
    $responseXml = simplexml_load_string($response);
    if (!isset($responseXml->combination->id)) {
        throw new PrestaShopApiException('Failed to update combination: Invalid response');
    }

    return true;
}
```

**Unit Test:**
```php
public function test_update_combination_updates_quantity()
{
    $client = $this->createMockClient();
    $result = $client->updateCombination(789, ['quantity' => 50], 1);

    $this->assertTrue($result);
}

public function test_update_combination_throws_exception_on_invalid_id()
{
    $this->expectException(PrestaShopApiException::class);

    $client = $this->createMockClient();
    $client->updateCombination(999999, ['quantity' => 10], 1);
}
```

---

#### **METHOD 4: deleteCombination()**

**Purpose:** Delete combination (with PrestaShop safety check)

**Signature:**
```php
/**
 * Delete combination (soft delete recommended)
 *
 * @param int $combinationId PrestaShop combination ID
 * @param int|null $shopId Shop ID (null = default shop)
 * @return bool Success status
 * @throws PrestaShopApiException
 */
public function deleteCombination(int $combinationId, ?int $shopId = null): bool
```

**Implementation:**
```php
public function deleteCombination(int $combinationId, ?int $shopId = null): bool
{
    // SAFETY CHECK: Verify combination exists before deletion
    $combinations = $this->getProductCombinations($productId, $shopId);
    $exists = collect($combinations)->contains('id_product_attribute', $combinationId);

    if (!$exists) {
        throw new PrestaShopApiException("Combination {$combinationId} does not exist");
    }

    // Build URL: /api/combinations/{id}
    $url = $this->baseUrl . '/api/combinations/' . $combinationId;

    if ($shopId) {
        $url .= '?id_shop=' . $shopId;
    }

    // Make DELETE request
    $response = $this->makeRequest('DELETE', $url);

    // PrestaShop returns empty response on success
    return true;
}
```

**⚠️ SAFETY:** Always verify combination exists before deletion (avoid orphan products)

**Unit Test:**
```php
public function test_delete_combination_deletes_existing_combination()
{
    $client = $this->createMockClient();
    $result = $client->deleteCombination(789, 1);

    $this->assertTrue($result);
}

public function test_delete_combination_throws_exception_if_not_exists()
{
    $this->expectException(PrestaShopApiException::class);

    $client = $this->createMockClient();
    $client->deleteCombination(999999, 1);
}
```

---

#### **ERROR HANDLING:**

**All methods MUST handle:**

1. **401 Unauthorized:** Invalid API key
2. **404 Not Found:** Product/combination does not exist
3. **500 Internal Server Error:** PrestaShop API error
4. **XML Parse Error:** Malformed response

**Exception Class (if not exist):**

**File:** `app/Exceptions/PrestaShopApiException.php`
```php
<?php

namespace App\Exceptions;

use Exception;

class PrestaShopApiException extends Exception
{
    protected $httpCode;
    protected $psErrorMessage;

    public function __construct(string $message, int $httpCode = 0, ?string $psErrorMessage = null)
    {
        parent::__construct($message);
        $this->httpCode = $httpCode;
        $this->psErrorMessage = $psErrorMessage;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getPrestaShopErrorMessage(): ?string
    {
        return $this->psErrorMessage;
    }
}
```

---

#### **ACCEPTANCE CRITERIA:**

**✅ MUST PASS:**

1. **All 4 methods implemented** w `PrestaShop8Client.php`
2. **Unit tests (minimum 10 test cases):** Cover success, empty results, exceptions
3. **Manual test against real PrestaShop API:**
   ```php
   php artisan tinker
   > $client = app(\App\Services\PrestaShop\PrestaShop8Client::class);
   > $combinations = $client->getProductCombinations(456, 1);
   > dd($combinations); // should return array of combinations
   ```
4. **Error handling verified:** Test 404, 401 responses
5. **Code style compliance:** No hardcoded URLs, all configurable, file size <300 linii

---

#### **CONTEXT FOR NEXT AGENT:**

After completing:

1. **Summary:** "4 methods added to PrestaShop8Client: getProductCombinations, createProductWithCombinations, updateCombination, deleteCombination. All unit tested + manual PrestaShop API verified."
2. **Gotchas:** Document any PrestaShop API quirks discovered (e.g., XML format issues, multi-store behavior)
3. **Blockers:** Any PrestaShop API limitations (rate limiting, timeout issues)

**RAPORT:** `_AGENT_REPORTS/prestashop_api_expert_faza5_task2_combinations_api_2025-11-04_REPORT.md`

---

### 🥉 AGENT BRIEF #3: debugger

**Task:** FAZA 3B.3 Sync Logic Verification
**Priority:** 🟡 MEDIUM (FAZA 3 completion, NOT blocking FAZA 5)
**Duration:** 1-2h
**Dependencies:** FAZA 3B.2.5 (real-time progress tracking fixed ✅)
**Can Run In Parallel With:** All Track B tasks (FAZA 5.1, 5.2, 5.3)

---

#### **OBJECTIVE:**

Verify that existing `SyncProductToPrestaShop` job executes correctly and `ProductTransformer::toPrestaShop()` produces valid PrestaShop XML.

---

#### **CONTEXT:**

FAZA 3B.1-3B.2.5 already completed:
- ✅ Queue worker fixed (default queue)
- ✅ Status badges w ProductList working
- ✅ Real-time progress tracking fixed (wire:poll.3s, counter 1/5 not 0/5)

**Remaining work:** Test actual sync execution (does product data sync correctly to PrestaShop?)

---

#### **DELIVERABLES:**

**1. Manual Test Scenario: Sync Single Product**

**Steps:**
```powershell
# Step 1: Identify test product
php artisan tinker
> $product = Product::where('sku', 'TEST-SYNC-001')->first();
> $product->id; // note product ID

# Step 2: Dispatch sync job manually
> dispatch(new \App\Jobs\PrestaShop\SyncProductToPrestaShop($product->id, 1)); // shop_id=1

# Step 3: Check queue execution
php artisan queue:work --stop-when-empty

# Step 4: Verify sync status
> ProductSyncStatus::where('product_id', $product->id)->first()->status; // should be 'synced'

# Step 5: Verify PrestaShop (check PS admin panel)
# Login to PrestaShop → Products → Search "TEST-SYNC-001"
# Verify: Name, Price, Stock, Description match PPM data
```

**Expected Result:**
- ✅ Job executes without errors
- ✅ `product_sync_status.status` = 'synced'
- ✅ `product_sync_status.prestashop_product_id` populated
- ✅ PrestaShop product data matches PPM data

---

**2. Verify ProductTransformer::toPrestaShop() Output**

**Create Test Script:** `_TOOLS/verify_product_transformer.php`

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Services\PrestaShop\ProductTransformer;

// Get sample product
$product = Product::with(['categories', 'prices', 'stocks'])->first();

if (!$product) {
    echo "No products found in database\n";
    exit(1);
}

// Transform to PrestaShop format
$transformer = new ProductTransformer();
$psData = $transformer->toPrestaShop($product, 1); // shop_id=1

// Verify structure
echo "=== PRODUCT TRANSFORMER OUTPUT ===\n";
echo "Product ID: {$product->id}\n";
echo "SKU: {$product->sku}\n";
echo "\n=== PrestaShop Data Structure ===\n";
print_r($psData);

// Critical checks
$required = ['name', 'reference', 'type', 'price', 'id_category_default'];
$missing = array_diff($required, array_keys($psData));

if (!empty($missing)) {
    echo "\n❌ ERROR: Missing required fields: " . implode(', ', $missing) . "\n";
    exit(1);
}

echo "\n✅ ProductTransformer output valid\n";
```

**Execute:**
```powershell
php _TOOLS/verify_product_transformer.php
```

**Expected Output:**
```
=== PRODUCT TRANSFORMER OUTPUT ===
Product ID: 123
SKU: TEST-SKU-001

=== PrestaShop Data Structure ===
Array
(
    [name] => Test Product Name
    [reference] => TEST-SKU-001
    [type] => simple
    [price] => 99.99
    [id_category_default] => 5
    [description] => <p>Product description</p>
    [quantity] => 10
    ...
)

✅ ProductTransformer output valid
```

---

**3. Error Handling Verification**

**Test Scenario: Sync Product with Missing Required Field**

```php
php artisan tinker
> $product = new Product(['sku' => 'INVALID']); // missing name, type
> $product->save();
> dispatch(new \App\Jobs\PrestaShop\SyncProductToPrestaShop($product->id, 1));
> php artisan queue:work --stop-when-empty

// Check sync_logs for error
> SyncLog::where('product_id', $product->id)->latest()->first()->error_message;
// Expected: "Missing required field: name"
```

**Expected Result:**
- ✅ Job fails gracefully (no crash)
- ✅ Error logged to `sync_logs` table
- ✅ `product_sync_status.status` = 'error'
- ✅ `product_sync_status.last_error` populated with error message

---

**4. Logging Verification**

**Check sync_logs table:**
```sql
SELECT * FROM sync_logs
WHERE product_id = 123
ORDER BY created_at DESC
LIMIT 5;
```

**Expected columns:**
- `id`, `product_id`, `shop_id`, `action`, `status`, `response_data`, `error_message`, `created_at`

**Verify:**
- ✅ All sync operations logged
- ✅ Success logs include `prestashop_product_id`
- ✅ Error logs include error message + stack trace

---

#### **ACCEPTANCE CRITERIA:**

**✅ MUST PASS:**

1. **Manual sync test:** Product syncs to PrestaShop successfully
2. **ProductTransformer test:** Output contains all required fields
3. **Error handling test:** Invalid product fails gracefully with error logged
4. **Logging test:** All sync operations logged to `sync_logs` table
5. **No crashes:** No unhandled exceptions during sync

---

#### **ISSUES TO LOG:**

If you encounter ANY issues, document in:

**File:** `_ISSUES_FIXES/SYNC_LOGIC_VERIFICATION_ISSUES_2025-11-04.md`

**Format:**
```markdown
# SYNC LOGIC VERIFICATION ISSUES

## Issue 1: ProductTransformer Missing Field

**Symptom:** PrestaShop API returns 400 error "Missing field: id_category_default"

**Root Cause:** ProductTransformer::toPrestaShop() does not include category mapping

**Fix:** Add category mapping logic in ProductTransformer line 85

**Status:** ✅ FIXED (or ⏳ IN PROGRESS)
```

---

#### **CONTEXT FOR NEXT AGENT:**

After completing:

1. **Summary:** "SyncProductToPrestaShop job verified + ProductTransformer output validated. All sync operations logging correctly. [X issues found and fixed]."
2. **Issues:** List any bugs discovered + fixed
3. **Blockers:** Any PrestaShop API issues preventing sync (rate limiting, timeout)

**RAPORT:** `_AGENT_REPORTS/debugger_faza3b3_sync_logic_verification_2025-11-04_REPORT.md`

---

## ⚠️ RISK ANALYSIS & MITIGATION

### 🔴 HIGH RISK

**Risk 1: Database Migration Conflicts**

**Scenario:** Migrations fail because tables already exist (partial previous attempt)

**Probability:** MEDIUM (30%)

**Impact:** HIGH (blocks all FAZA 5 work)

**Mitigation:**
1. **Pre-flight check:** Run `php artisan migrate:status` before starting
2. **Rollback strategy:** `php artisan migrate:rollback --step=5` if conflicts
3. **Fresh migration:** Drop conflicting tables manually if needed
4. **Backup:** Backup local database before migration

**Contingency:** If migration fails, architect reviews schema + creates custom migration fix script

---

**Risk 2: PrestaShop API Rate Limiting**

**Scenario:** PrestaShop API returns 429 Too Many Requests during testing

**Probability:** MEDIUM (40%)

**Impact:** HIGH (blocks testing)

**Mitigation:**
1. **Implement rate limiting:** Add `sleep(1)` between API calls (Task 5.2)
2. **Use test environment:** Configure separate PrestaShop test instance (not production)
3. **Cache responses:** Mock API responses dla unit tests (avoid hitting API)
4. **Queue throttling:** Laravel queue rate limiting (10 jobs/minute)

**Contingency:** If rate limiting encountered, switch to mock API dla testing (defer real API testing to deployment phase)

---

**Risk 3: Context7 MCP Unavailable**

**Scenario:** Context7 API down during agent work

**Probability:** LOW (10%)

**Impact:** MEDIUM (delays agent start, but work can continue with cached docs)

**Mitigation:**
1. **Pre-cache docs:** Run Context7 lookups in Phase 0 (pre-flight)
2. **Fallback to CLAUDE.md:** Use existing patterns from _ISSUES_FIXES/ and CLAUDE.md
3. **Defer verification:** Complete implementation first, verify against Context7 later

**Contingency:** If Context7 unavailable, architect provides cached Laravel 12.x + PrestaShop 8.x patterns from previous sessions

---

### 🟡 MEDIUM RISK

**Risk 4: Agent Overload (Too Many Parallel Tasks)**

**Scenario:** 3 agents working parallel → confusion, duplicate work, communication gaps

**Probability:** MEDIUM (40%)

**Impact:** MEDIUM (delays, rework needed)

**Mitigation:**
1. **Clear task boundaries:** Each agent has distinct files to modify (no overlap)
2. **Sequential checkpoints:** Architect reviews Track B before Track C starts
3. **Mandatory reports:** Every agent writes report before next agent starts
4. **Slack time:** Add 20% buffer to timeline (realistic delays)

**Contingency:** If agents blocked, architect switches to sequential execution (slower but safer)

---

**Risk 5: File Size Violations (>300 linii)**

**Scenario:** Services grow beyond 300 linii limit during implementation

**Probability:** HIGH (60%)

**Impact:** LOW (refactoring needed, but not blocking)

**Mitigation:**
1. **Pre-planning:** Break large services into traits/helper classes
2. **coding-style-agent review:** Mandatory review in Track D Task 10
3. **Refactoring phase:** Dedicated time dla splitting oversized files

**Contingency:** If file >300 linii, coding-style-agent creates refactoring task (extract to trait/service)

---

### 🟢 LOW RISK

**Risk 6: PrestaShop API XML Parsing Issues**

**Scenario:** PrestaShop returns malformed XML → parser crashes

**Probability:** LOW (15%)

**Impact:** MEDIUM (error handling needed)

**Mitigation:**
1. **Try-catch all XML parsing:** Use `simplexml_load_string()` with error suppression
2. **Validation:** Check XML structure before parsing
3. **Logging:** Log raw XML response on parse error

**Contingency:** If XML parse errors frequent, create XmlParser helper class with robust error handling

---

## 📊 DEPENDENCIES GRAPH

```
[PHASE 0: Pre-flight] (1h)
    ↓
[PHASE 1: Day 1-2] ─────────┬─────────────┐
    Track A (FAZA 3)         Track B (FAZA 5.1-5.3)
    ├─ 3B.3 (debugger)       ├─ 5.1 DB Schema (laravel)    [CRITICAL PATH]
    ├─ 3B.4 (laravel)        ├─ 5.2 API Methods (prestashop)
    ├─ 3C.1 (laravel)        └─ 5.3 Validation (laravel)
    ├─ 3C.2 (laravel)                ↓
    └─ 3C.3 (debugger)          [CHECKPOINT]
            ↓                        ↓
    [Track A COMPLETE]      [Track B COMPLETE]
                                     ↓
[PHASE 2: Day 3-5] ──────────────────┘
    Track C (FAZA 5.4-5.5)
    ├─ 5.4.1 XLSX Import (laravel)    [CRITICAL PATH]
    ├─ 5.4.2 PS Import (prestashop)
    ├─ 5.4.3 Export (laravel+prestashop)
    ├─ 5.5.1 Import Wizard (livewire)
    ├─ 5.5.2 Export Wizard (livewire)
    ├─ 5.5.3 Conflict Panel (livewire)
    ├─ 5.5.4 Progress Tracker (livewire)
    └─ 5.5.5 Frontend Verification (frontend)
            ↓
    [Track C COMPLETE]
            ↓
[PHASE 3: Day 6-7]
    Track D (FAZA 5.6-5.8)
    ├─ 5.6 Queue Jobs (laravel)    [CRITICAL PATH]
    ├─ 5.7 Conflict Resolution (livewire)
    └─ 5.8 Image Cache (laravel)
            ↓
[PHASE 4: Day 8-9]
    Track D (FAZA 5.9-5.10)
    ├─ 5.9 Testing (debugger)    [CRITICAL PATH]
    └─ 5.10 Code Review (coding-style)
            ↓
    [GO/NO-GO GATE]
            ↓
[PHASE 5: Day 10-11]
    Track D (FAZA 5.11-5.12)
    ├─ 5.11 Deployment (deployment-specialist)    [CRITICAL PATH]
    └─ 5.12 Agent Report (architect)
            ↓
    [FAZA 5 COMPLETE ✅]
```

**CRITICAL PATH:** 5.1 → 5.4.1 → 5.6 → 5.9 → 5.11 (total 26-31h)

**All other tasks can run parallel with critical path (optimize wall-clock time)**

---

## 🚀 NEXT STEPS (IMMEDIATE ACTIONS)

### **ACTION 1: Pre-Flight Context7 Lookups (architect) - 30 min**

```bash
# Laravel 12.x patterns
mcp__context7__resolve-library-id "Laravel 12.x"
mcp__context7__get-library-docs library_id="/websites/laravel_12_x" topic="migrations, queue jobs, validation" tokens=3000

# Livewire 3.x patterns
mcp__context7__resolve-library-id "Livewire 3.x"
mcp__context7__get-library-docs library_id="/livewire/livewire" topic="wizard components, real-time updates" tokens=2000

# PrestaShop API
mcp__context7__resolve-library-id "PrestaShop 8.x"
mcp__context7__get-library-docs library_id="/prestashop/docs" topic="combinations API, XML format" tokens=2000
```

**Save outputs to:** `_DOCS/CONTEXT7_CACHE_FAZA5_2025-11-04.md` (dla offline reference)

---

### **ACTION 2: Brief Top 3 Agents (architect) - 15 min**

Send agent briefs (from this report) to:
1. **laravel-expert** → FAZA 5.1 Database Schema Extensions (3-4h)
2. **prestashop-api-expert** → FAZA 5.2 PrestaShop API Methods Extension (4-5h)
3. **debugger** → FAZA 3B.3 Sync Logic Verification (1-2h)

**All 3 agents can start in PARALLEL (no dependencies)**

---

### **ACTION 3: Create Tracking Dashboard (architect) - 15 min**

Update `Plan_Projektu/ETAP_07_Prestashop_API.md`:

**Add Section:**
```markdown
## 📊 FAZA 5 EXECUTION TRACKER (2025-11-04 START)

### TRACK A (FAZA 3 Completion)
- ⏳ 3B.3 Sync Logic Verification (debugger) - 0/2h
- ❌ 3B.4 Product Sync Status Update (laravel-expert) - 0/2h
- ❌ 3C.1 Queue Health Monitoring (laravel-expert) - 0/2h
- ❌ 3C.2 Performance Optimization (laravel-expert) - 0/2h
- ❌ 3C.3 Error Recovery (debugger) - 0/2h

### TRACK B (FAZA 5 Foundation)
- ⏳ 5.1 Database Schema Extensions (laravel-expert) - 0/4h
- ⏳ 5.2 PrestaShop API Methods Extension (prestashop-api-expert) - 0/5h
- ❌ 5.3 Validation Service Layer (laravel-expert) - 0/6h

**Day 1 Goal:** Complete 3B.3, 5.1, 5.2 (start 5.3)
**Day 2 Goal:** Complete 5.3, 3B.4, 3C.1-3C.3
```

**Update after each agent completes task** (track progress)

---

## 📁 DELIVERABLES SUMMARY

**This Coordination Report:**
- ✅ Executive summary with strategic decision
- ✅ Agent delegation matrix (4 tracks, 30+ tasks)
- ✅ Execution phases (5 phases, 11 days timeline)
- ✅ Detailed briefs dla top 3 agents (laravel-expert, prestashop-api-expert, debugger)
- ✅ Risk analysis (6 risks + mitigation strategies)
- ✅ Dependencies graph + critical path
- ✅ Next steps (3 immediate actions)

**Total Report:** ~7,500 words

**Files Created:**
- `_AGENT_REPORTS/architect_etap07_implementation_coordination_2025-11-04_REPORT.md` (this file)

**Next Agent:** architect (self) → Execute ACTION 1-3 (Context7 lookups + agent briefing)

---

## 🎯 SUCCESS CRITERIA

**PHASE 1 SUCCESS (Day 2):**
- ✅ Track A (FAZA 3) = 100% complete
- ✅ Track B (FAZA 5.1-5.3) = 100% complete
- ✅ 5 migrations deployed + tested
- ✅ PrestaShop8Client extended + unit tested
- ✅ Validation service operational

**PHASE 5 SUCCESS (Day 11):**
- ✅ FAZA 5 = 100% complete
- ✅ Production deployment verified
- ✅ User guide published
- ✅ All 6 E2E scenarios PASSED
- ✅ All agent reports consolidated

**MVP SUCCESS (User Perspective):**
- ✅ User can import variants from XLSX (wizard)
- ✅ User can import variants from PrestaShop API (wizard)
- ✅ User can export variants to XLSX (download)
- ✅ User can export variants to PrestaShop (queue job)
- ✅ User can resolve SKU conflicts (conflict panel)
- ✅ User can monitor import/export progress (real-time)

---

**RAPORT ZAKOŃCZONY:** 2025-11-04 10:45
**Odpowiedzialny:** Claude Code AI (architect agent)
**Status:** ✅ COORDINATION STRATEGY COMPLETE - READY FOR EXECUTION
