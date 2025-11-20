# RAPORT KOORDYNACJI: ETAP_13 - Sync Panel UX Refactoring

**Data**: 2025-11-17
**Agent**: Expert Planning Manager (architect)
**Zadanie**: Analiza architektury i koordynacja implementacji ETAP_13

---

## EXECUTIVE SUMMARY

Przeprowadzono kompleksową analizę architektury PPM-CC-Laravel ProductForm pod kątem implementacji ETAP_13 (Sync Panel UX Refactoring). Zidentyfikowano obecną strukturę przycisków, istniejące JOBs, gaps w funkcjonalności oraz zdefiniowano szczegółowy plan implementacji dla zespołu agentów.

**STATUS**: ✅ Analiza zakończona - gotowe do delegacji zadań

**KLUCZOWE WNIOSKI:**
- ✅ Obecna struktura jest solidna, ale wymaga refaktoringu UX
- ⚠️ Brakuje BulkPullProducts JOB (istnieje PullProductsFromPrestaShop + BulkImportProducts)
- ✅ Database schema wspiera timestamps (last_pulled_at exists)
- ⚠️ Brakuje last_push_at - wymaga migracji
- ✅ Queue worker config: database driver (fallback - bezpieczny dla Hostido)
- ⚠️ Cron job status nieznany - wymaga weryfikacji na produkcji

---

## 1. ARCHITECTURE ANALYSIS

### 1.1 OBECNA STRUKTURA PRZYCISKÓW

#### SHOP TAB - Dolny Panel (Footer Buttons)

**Lokalizacja**: `resources/views/.../product-form.blade.php:1654-1759` (Footer section)

**Obecne przyciski:**
```blade
1. "Anuluj" → wire:click="cancel"
2. "Przywróć domyślne" → wire:click="resetToDefaults" (@if hasUnsavedChanges)
3. "Aktualizuj na wszystkich sklepach" / "Zaktualizuj na sklepie" → wire:click="syncToShops"
4. "Zapisz wszystkie zmiany" → wire:click="saveAllPendingChanges" (@if hasUnsavedChanges)
5. "Zapisz i Zamknij" → wire:click="saveAndClose"
```

**WYMAGANE ZMIANY (ETAP_13.1):**
- ❌ Brak przycisków "Aktualizuj aktualny sklep" i "Wczytaj z aktualnego sklepu"
- ✅ "Anuluj", "Przywróć domyślne", "Zapisz i Zamknij" - OK (zachować)
- ⚠️ "Aktualizuj na wszystkich sklepach" - DUPLIKAT z Sidepanel (usunąć z footer)

#### SIDEPANEL - Quick Actions

**Lokalizacja**: `resources/views/.../product-form.blade.php:1764-1799`

**Obecne przyciski:**
```blade
1. "Zapisz zmiany" / "Utwórz produkt" → wire:click="saveAndClose"
2. "Synchronizuj sklepy" → wire:click="syncToShops" (@if isEditMode && !empty(exportedShops))
```

**WYMAGANE ZMIANY (ETAP_13.2):**
- ❌ Brak "Aktualizuj sklepy" (export all shops)
- ❌ Brak "Wczytaj ze sklepów" (import all shops)
- ⚠️ "Synchronizuj sklepy" - RENAME do "Aktualizuj sklepy" (clarity)

#### PANEL SYNCHRONIZACJI - Per-Shop Actions

**Lokalizacja**: `resources/views/.../product-form.blade.php:486-505`

**Obecne przyciski:**
```blade
@foreach($shopData as $shopData)
    1. "Aktualizuj sklep" → wire:click="syncShop({{ shop_id }})"
    2. "Wczytaj dane" → wire:click="pullShopData({{ shop_id }})"
@endforeach
```

**OBECNY STAN:**
- ✅ Przyciski istnieją i działają
- ⚠️ Brak dynamicznych timestamps ("Ostatnie wczytanie", "Ostatnia aktualizacja")
- ⚠️ Brak pending changes detection (hardcoded "stawka VAT")

**SZCZEGÓŁY SYNCHRONIZACJI - Sekcja Informacyjna**

**Lokalizacja**: `resources/views/.../product-form.blade.php:440-459`

**Obecny kod:**
```blade
<p class="text-sm text-gray-400">
    <strong>Ostatnia sync:</strong>
    {{ $shopData->last_sync_at?->diffForHumans() ?? 'Nigdy' }}
</p>

@if(!empty($shopData->pending_fields))
    <h5>Oczekujące zmiany ({{ count($shopData->pending_fields) }})</h5>
    <ul>
        @foreach($shopData->pending_fields as $field)
            <li>{{ $field }}</li>
        @endforeach
    </ul>
@endif
```

**PROBLEMY:**
- ⚠️ `last_sync_at` - to last PUSH timestamp, NIE pull!
- ❌ Brakuje "Ostatnie wczytanie danych" (last_pulled_at)
- ✅ `pending_fields` istnieje i działa (Field-Level Tracking 2025-11-07)

---

### 1.2 BACKEND METHODS - ProductForm.php

#### Istniejące Metody Synchronizacji

**1. syncToShops() - Line 3219**
```php
public function syncToShops(): void
{
    if ($this->activeShopId === null) {
        $this->syncToAllShops();  // Dispatch SyncProductToPrestaShop per shop
    } else {
        $this->syncToCurrentShop(); // Dispatch for current shop
    }
}
```
**Status**: ✅ Działa poprawnie
**Użycie**: Footer button + Sidepanel

**2. syncShop($shopId) - Missing in Grep Results**
**Status**: ⚠️ NEEDS VERIFICATION - wire:click exists in Blade but method not found
**Przypuszczenie**: Może być w ProductFormShopTabs trait

**3. pullShopData($shopId) - Missing in Grep Results**
**Status**: ⚠️ NEEDS VERIFICATION - wire:click exists in Blade
**Przypuszczenie**: Trait ProductFormShopTabs

**4. loadProductDataFromPrestaShop($shopId, $forceReload) - Line 4254**
```php
public function loadProductDataFromPrestaShop(int $shopId, bool $forceReload = false): void
{
    // Lazy loading pattern - fetch from PrestaShop API
    // Updates $this->loadedShopData[$shopId]
}
```
**Status**: ✅ Exists
**Użycie**: "Wczytaj z PrestaShop" button (line 295 Blade)

---

### 1.3 JOBS - Existing vs. Required

#### ✅ ISTNIEJĄCE JOBS:

**1. SyncProductToPrestaShop**
- **Lokalizacja**: `app/Jobs/PrestaShop/SyncProductToPrestaShop.php`
- **Funkcja**: Sync single product → PrestaShop (PPM → PS)
- **Usage**: Dispatched by syncToShops()
- **Status**: ✅ DZIAŁA

**2. BulkSyncProducts**
- **Lokalizacja**: `app/Jobs/PrestaShop/BulkSyncProducts.php` (READ)
- **Funkcja**: Batch dispatch SyncProductToPrestaShop for multiple products
- **Features**: Priority handling, batch tracking, progress callbacks
- **Constructor**: `__construct(Collection $products, PrestaShopShop $shop, ?string $batchName, ?int $userId)`
- **Status**: ✅ ISTNIEJE - gotowy do użycia

**3. PullProductsFromPrestaShop**
- **Lokalizacja**: `app/Jobs/PullProductsFromPrestaShop.php`
- **Funkcja**: Pull current product data PrestaShop → PPM
- **Features**: Scheduled job (every 6h), updates last_pulled_at, conflict resolution
- **Usage**: Scheduler
- **Status**: ✅ ISTNIEJE - ale dla scheduled tasks, NOT user-triggered

**4. BulkImportProducts**
- **Lokalizacja**: `app/Jobs/PrestaShop/BulkImportProducts.php`
- **Funkcja**: Import NEW products from PrestaShop (category/all/individual)
- **Usage**: ImportManager Livewire component
- **Status**: ✅ ISTNIEJE - ale to IMPORT (new products), NOT PULL (existing)

**5. PullSingleProductFromPrestaShop**
- **Lokalizacja**: `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php`
- **Funkcja**: Pull single product data PrestaShop → PPM
- **Status**: ✅ PRAWDOPODOBNIE ISTNIEJE (found in Grep)

#### ❌ BRAKUJĄCE JOBS (DO STWORZENIA):

**1. BulkPullProducts** - HIGH PRIORITY
- **Cel**: User-triggered bulk pull (refresh data from ALL shops)
- **Pattern**: Similar to BulkSyncProducts but opposite direction (PS → PPM)
- **Constructor**: `__construct(Product $product, Collection $shops, ?int $userId)`
- **Dispatches**: PullSingleProductFromPrestaShop per shop
- **Potrzebne do**: Sidepanel "Wczytaj ze sklepów" button

---

### 1.4 DATABASE SCHEMA - product_shop_data

#### ✅ ISTNIEJĄCE KOLUMNY (Verified):

**Timestamps:**
```sql
last_sync_at          TIMESTAMP NULL  -- Last sync attempt (PPM → PS push)
last_success_sync_at  TIMESTAMP NULL  -- Last successful sync (PPM → PS)
last_pulled_at        TIMESTAMP NULL  -- Last pull (PS → PPM) - ADDED 2025-11-06
```

**Migration**: `2025_11_06_115218_add_last_pulled_at_to_product_shop_data.php` ✅ EXISTS

**Sync Status:**
```sql
sync_status        VARCHAR(50)  -- pending|syncing|synced|error|conflict|disabled
pending_fields     JSON         -- Field-Level Pending Tracking (2025-11-07)
sync_direction     VARCHAR(50)  -- ppm_to_ps|ps_to_ppm|bidirectional
error_message      TEXT         -- Ostatni błąd synchronizacji
```

**Conflict Resolution:**
```sql
conflict_data         JSON         -- Dane konfliktu
conflict_detected_at  TIMESTAMP    -- Kiedy wykryto konflikt
conflict_log          JSON         -- Historia rozwiązań konfliktów (PROBLEM #9.3)
has_conflicts         BOOLEAN      -- Flag konfliktu
```

#### ❌ BRAKUJĄCE KOLUMNY (DO DODANIA):

**1. last_push_at** - CRITICAL
- **Cel**: Separate timestamp dla PPM → PS push (obecnie używamy last_sync_at)
- **Dlaczego**: `last_sync_at` is ambiguous - doesn't distinguish push vs pull
- **Migration**: `add_last_push_at_to_product_shop_data.php`
- **Schema**:
```sql
last_push_at TIMESTAMP NULL COMMENT 'Last time PPM data was pushed to PrestaShop'
```

**Harmonogram:**
```
last_pulled_at  - PrestaShop → PPM (read)
last_push_at    - PPM → PrestaShop (write) - NEW
last_sync_at    - Generic timestamp (keep for backward compat)
```

---

### 1.5 QUEUE WORKER CONFIG

#### Config File Analysis:

**File**: `config/queue.php`

**Default Connection**:
```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

**Database Driver Config**:
```php
'database' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'default',
    'retry_after' => 90,
],
```

**Status**: ✅ Database driver configured (safe fallback for Hostido shared hosting)

#### Hostido Production - CRITICAL UNKNOWNS:

**Queue Worker**:
- ❓ Czy cron job uruchamia `php artisan queue:work`?
- ❓ Frequency: co minutę? co 5 minut?
- ❓ Timeout: default 60s or custom?
- ❓ Memory limit: default 128MB?

**WYMAGANA WERYFIKACJA**:
```powershell
# Check crontab on Hostido
plink ... -batch "crontab -l | grep queue"

# Check queue:work process
plink ... -batch "ps aux | grep 'queue:work'"
```

**ETAP_13 Dependencies:**
- ⚠️ Wire:poll countdown (0-60s) assumes jobs start within 1 minute
- ⚠️ Real-time updates require frequent queue processing
- ⚠️ Jeśli cron runs every 5min → countdown musi być 0-300s

---

## 2. TASK BREAKDOWN - AGENT DELEGATION

### AGENT ASSIGNMENTS:

```
PHASE 1: Backend Foundation (Sequential)
├─ laravel-expert
│
PHASE 2: Livewire Integration (After Phase 1)
├─ livewire-specialist
│
PHASE 3: UI/UX Implementation (After Phase 2)
├─ frontend-specialist
│
PHASE 4: Deployment (After Phase 3)
└─ deployment-specialist
```

---

### 2.1 LARAVEL-EXPERT TASKS

**Priority**: 🔥 CRITICAL (Foundation)

#### Task 1: Create BulkPullProducts JOB
**File**: `app/Jobs/PrestaShop/BulkPullProducts.php`

**Requirements**:
- Pattern: Mirror BulkSyncProducts architecture
- Constructor: `__construct(Product $product, Collection $shops, ?int $userId)`
- Dispatch: PullSingleProductFromPrestaShop per shop
- Batch tracking: Use Laravel Bus::batch() with callbacks
- Progress: Integrate with JobProgressService
- User tracking: Store userId for audit trail

**Acceptance Criteria**:
- [ ] JOB dispatches PullSingleProductFromPrestaShop for each shop
- [ ] Batch progress tracked with callbacks (then/catch/finally)
- [ ] userId captured for audit
- [ ] Error handling: allowFailures() - don't cancel batch on single failure
- [ ] Logging: info/error levels appropriate

---

#### Task 2: Add last_push_at Migration
**File**: `database/migrations/2025_11_17_add_last_push_at_to_product_shop_data.php`

**Schema**:
```php
Schema::table('product_shop_data', function (Blueprint $table) {
    $table->timestamp('last_push_at')
          ->nullable()
          ->after('last_pulled_at')
          ->comment('Last time PPM data was pushed to PrestaShop');
});
```

**Update Models/Jobs**:
- `app/Models/ProductShopData.php`: Add to $casts, $dates, $fillable
- `app/Jobs/PrestaShop/SyncProductToPrestaShop.php`: Update last_push_at on success
- `app/Jobs/PrestaShop/BulkSyncProducts.php`: Verify timestamp update

**Acceptance Criteria**:
- [ ] Migration adds last_push_at column
- [ ] ProductShopData model casts/dates updated
- [ ] SyncProductToPrestaShop updates timestamp on success
- [ ] Rollback works (down() method)

---

#### Task 3: Update ProductShopData Helper Methods
**File**: `app/Models/ProductShopData.php`

**New Methods**:
```php
/**
 * Get time since last pull (PrestaShop → PPM)
 */
public function getTimeSinceLastPull(): string
{
    if (!$this->last_pulled_at) {
        return 'Nigdy';
    }
    return $this->last_pulled_at->diffForHumans();
}

/**
 * Get time since last push (PPM → PrestaShop)
 */
public function getTimeSinceLastPush(): string
{
    if (!$this->last_push_at) {
        return 'Nigdy';
    }
    return $this->last_push_at->diffForHumans();
}
```

**Acceptance Criteria**:
- [ ] Helper methods return human-readable timestamps
- [ ] Handle NULL timestamps gracefully
- [ ] Use Carbon diffForHumans()

---

#### Task 4: Anti-Duplicate JOB Logic
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Requirements**:
- saveAllPendingChanges() check for active sync jobs before dispatching
- Query: `jobs table WHERE queue='prestashop_sync' AND payload LIKE '%product_id":' . $productId . '%' AND status='pending'`
- If exists: Skip dispatch, show toast "Synchronizacja już w trakcie"
- Flag: $manualSyncRequested to prevent auto-sync on save

**Acceptance Criteria**:
- [ ] saveAllPendingChanges() checks for active jobs
- [ ] No duplicate jobs dispatched
- [ ] User notified if job already exists

---

### 2.2 LIVEWIRE-SPECIALIST TASKS

**Priority**: 🔥 HIGH (After laravel-expert)

#### Task 1: Add Public Properties for JOB Monitoring
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**New Properties**:
```php
// JOB Monitoring (ETAP_13)
public ?int $activeJobId = null;           // Active job ID for polling
public ?string $activeJobStatus = null;    // pending|processing|completed|failed
public ?string $activeJobType = null;      // sync|pull
public ?string $jobCreatedAt = null;       // Timestamp for countdown
public ?string $jobResult = null;          // success|error (after completion)
```

**Acceptance Criteria**:
- [ ] Properties added to ProductForm
- [ ] Default values set in mount()
- [ ] Properties public (Livewire reactive)

---

#### Task 2: Implement checkJobStatus() Method
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Method**:
```php
/**
 * Check status of active background job
 * Called by wire:poll.5s from Blade
 */
public function checkJobStatus(): void
{
    if (!$this->activeJobId) {
        return;
    }

    // Query jobs table
    $job = DB::table('jobs')->find($this->activeJobId);

    if (!$job) {
        // Job completed or failed - check failed_jobs
        $failed = DB::table('failed_jobs')
            ->where('id', $this->activeJobId)
            ->first();

        if ($failed) {
            $this->activeJobStatus = 'failed';
            $this->jobResult = 'error';
            $this->dispatch('job-failed', message: $failed->exception);
        } else {
            $this->activeJobStatus = 'completed';
            $this->jobResult = 'success';
            $this->dispatch('job-completed');

            // Refresh shop data
            $this->refreshShopData($this->activeShopId);
        }

        // Auto-clear after 5s
        $this->dispatch('auto-clear-job-status', delay: 5000);
        return;
    }

    // Still pending/processing
    $this->activeJobStatus = 'processing';
}
```

**Acceptance Criteria**:
- [ ] Queries jobs table for active job
- [ ] Checks failed_jobs if not found
- [ ] Updates $activeJobStatus, $jobResult
- [ ] Dispatches Livewire events for UI feedback
- [ ] Auto-clear after 5s

---

#### Task 3: Update bulkUpdateShops() Method
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Enhancement**:
```php
public function bulkUpdateShops(): void
{
    // Dispatch BulkSyncProducts JOB
    $batch = BulkSyncProducts::dispatch(
        $this->product->shopData->pluck('product'),
        $shop,
        "Bulk Update - Product {$this->product->sku}",
        auth()->id()
    );

    // Capture job ID for monitoring
    $this->activeJobId = $batch->id;
    $this->activeJobType = 'sync';
    $this->jobCreatedAt = now()->toIso8601String();
    $this->activeJobStatus = 'pending';

    $this->dispatch('success', message: 'Rozpoczęto aktualizację sklepów');
}
```

**Acceptance Criteria**:
- [ ] Dispatches BulkSyncProducts
- [ ] Captures job ID + timestamp
- [ ] Sets activeJobType = 'sync'
- [ ] User notification

---

#### Task 4: Create bulkPullFromShops() Method
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**New Method**:
```php
/**
 * Pull product data from ALL shops (PrestaShop → PPM)
 */
public function bulkPullFromShops(): void
{
    if (!$this->product) {
        $this->dispatch('error', message: 'Produkt nie istnieje');
        return;
    }

    $shops = $this->product->shopData->pluck('shop');

    if ($shops->isEmpty()) {
        $this->dispatch('warning', message: 'Produkt nie jest przypisany do żadnego sklepu');
        return;
    }

    // Dispatch BulkPullProducts JOB
    $batch = BulkPullProducts::dispatch(
        $this->product,
        $shops,
        auth()->id()
    );

    // Capture job ID for monitoring
    $this->activeJobId = $batch->id;
    $this->activeJobType = 'pull';
    $this->jobCreatedAt = now()->toIso8601String();
    $this->activeJobStatus = 'pending';

    $this->dispatch('success', message: 'Rozpoczęto wczytywanie danych ze sklepów');
}
```

**Acceptance Criteria**:
- [ ] Dispatches BulkPullProducts (from Task 2.1.1)
- [ ] Captures job ID + timestamp
- [ ] Sets activeJobType = 'pull'
- [ ] User notification

---

#### Task 5: Implement Pending Changes Detection
**File**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Computed Property**:
```php
/**
 * Detect pending changes for current shop (ETAP_13.3)
 *
 * Compare ProductShopData fields vs cached PrestaShop data
 * Return array of changed field names
 */
public function getPendingChangesForShop(int $shopId): array
{
    $shopData = ProductShopData::where('product_id', $this->product->id)
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData || !isset($this->loadedShopData[$shopId])) {
        return [];
    }

    $cached = $this->loadedShopData[$shopId];
    $changes = [];

    // Compare fields
    $fieldsToCheck = ['name', 'price', 'quantity', 'tax_rate', 'description'];

    foreach ($fieldsToCheck as $field) {
        if (isset($cached[$field]) && $shopData->$field != $cached[$field]) {
            $changes[] = $field;
        }
    }

    return $changes;
}
```

**Acceptance Criteria**:
- [ ] Compares ProductShopData vs cached PrestaShop data
- [ ] Returns array of changed field names
- [ ] Handles NULL/missing data gracefully

---

### 2.3 FRONTEND-SPECIALIST TASKS

**Priority**: 🟡 MEDIUM (After livewire-specialist)

#### Task 1: Refactor Shop Tab Footer Buttons
**File**: `resources/views/livewire/products/management/product-form.blade.php`

**Changes (Lines 1654-1759)**:
```blade
{{-- SHOP TAB FOOTER - ETAP_13.1 --}}
<div class="flex items-center space-x-3">
    {{-- Anuluj --}}
    <button wire:click="cancel" class="btn-enterprise-secondary">
        Anuluj
    </button>

    {{-- Przywróć domyślne --}}
    @if($hasUnsavedChanges)
        <button wire:click="resetToDefaults" class="btn-reset-defaults">
            Przywróć domyślne
        </button>
    @endif

    {{-- Aktualizuj aktualny sklep (NEW) --}}
    @if($activeShopId !== null)
        <button wire:click="syncShop({{ $activeShopId }})" class="btn-enterprise-primary">
            Aktualizuj aktualny sklep
        </button>
    @endif

    {{-- Wczytaj z aktualnego sklepu (NEW) --}}
    @if($activeShopId !== null)
        <button wire:click="pullShopData({{ $activeShopId }})" class="btn-enterprise-secondary">
            Wczytaj z aktualnego sklepu
        </button>
    @endif

    {{-- Zapisz i Zamknij --}}
    <button wire:click="saveAndClose" class="btn-enterprise-success">
        Zapisz i Zamknij
    </button>
</div>
```

**Acceptance Criteria**:
- [ ] Footer buttons reorganized per ETAP_13.1
- [ ] "Aktualizuj aktualny sklep" added (@if activeShopId)
- [ ] "Wczytaj z aktualnego sklepu" added
- [ ] Spacing: `gap-3` or `space-x-3`
- [ ] Enterprise button classes applied

---

#### Task 2: Add Sidepanel Bulk Actions
**File**: `resources/views/livewire/products/management/product-form.blade.php`

**Changes (After line 1770)**:
```blade
{{-- SIDEPANEL QUICK ACTIONS - ETAP_13.2 --}}
<div class="space-y-4">
    {{-- Save Button (existing) --}}
    <button wire:click="saveAndClose" class="btn-enterprise-primary w-full py-3">
        <i class="fas fa-save mr-3"></i>
        Zapisz zmiany
    </button>

    {{-- Aktualizuj sklepy (ALL shops export) - NEW --}}
    @if($isEditMode && !empty($exportedShops))
        <button wire:click="bulkUpdateShops" class="btn-enterprise-secondary w-full py-3">
            <i class="fas fa-cloud-upload-alt mr-2"></i>
            Aktualizuj sklepy
        </button>
    @endif

    {{-- Wczytaj ze sklepów (ALL shops import) - NEW --}}
    @if($isEditMode && !empty($exportedShops))
        <button wire:click="bulkPullFromShops" class="btn-enterprise-secondary w-full py-3">
            <i class="fas fa-cloud-download-alt mr-2"></i>
            Wczytaj ze sklepów
        </button>
    @endif
</div>
```

**Acceptance Criteria**:
- [ ] "Aktualizuj sklepy" button added (wire:click="bulkUpdateShops")
- [ ] "Wczytaj ze sklepów" button added (wire:click="bulkPullFromShops")
- [ ] Icons: fa-cloud-upload-alt, fa-cloud-download-alt
- [ ] Full width: `w-full py-3`

---

#### Task 3: Update Panel Synchronizacji Timestamps
**File**: `resources/views/livewire/products/management/product-form.blade.php`

**Changes (Line 440-459)**:
```blade
{{-- Szczegóły synchronizacji - ETAP_13.3 FIX --}}
<div class="sync-details">
    <p class="text-sm text-gray-400">
        <strong>Ostatnie wczytanie danych:</strong>
        {{ $shopData->last_pulled_at?->diffForHumans() ?? 'Nigdy' }}
    </p>
    <p class="text-sm text-gray-400">
        <strong>Ostatnia aktualizacja sklepu:</strong>
        {{ $shopData->last_push_at?->diffForHumans() ?? 'Nigdy' }}
    </p>
</div>

{{-- Oczekujące zmiany - DYNAMIC (ETAP_13.3) --}}
@php
    $pendingChanges = $this->getPendingChangesForShop($shopData->shop_id);
@endphp

@if(!empty($pendingChanges))
    <div class="pending-changes-compact">
        <h5 class="text-sm font-semibold text-gray-300 mb-2">
            <svg class="w-4 h-4 mr-1 text-yellow-400" ...>...</svg>
            Oczekujące zmiany ({{ count($pendingChanges) }})
        </h5>
        <ul class="compact-list">
            @foreach($pendingChanges as $field)
                <li class="text-sm text-gray-400">{{ $field }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

**Acceptance Criteria**:
- [ ] "Ostatnie wczytanie danych" shows last_pulled_at
- [ ] "Ostatnia aktualizacja sklepu" shows last_push_at
- [ ] Pending changes use getPendingChangesForShop() (NOT hardcoded)
- [ ] Field names translated to Polish labels

---

#### Task 4: Alpine.js Countdown Animation
**File**: `resources/views/livewire/products/management/product-form.blade.php`

**Alpine Component**:
```blade
<div x-data="jobCountdown({
    createdAt: @entangle('jobCreatedAt'),
    status: @entangle('activeJobStatus'),
    result: @entangle('jobResult')
})"
     x-init="init()"
     x-on:auto-clear-job-status.window="clearJob($event.detail.delay)">

    <button
        wire:click="bulkUpdateShops"
        :disabled="status === 'processing'"
        :class="{
            'btn-job-running': status === 'processing',
            'btn-job-success': result === 'success',
            'btn-job-error': result === 'error'
        }"
        :style="status === 'processing' ? `--progress-percent: ${progress}%` : ''">

        <template x-if="status === 'processing'">
            <span>Aktualizowanie... (<span x-text="remainingSeconds"></span>s)</span>
        </template>

        <template x-if="result === 'success'">
            <span>✓ SUKCES</span>
        </template>

        <template x-if="result === 'error'">
            <span>✗ BŁĄD</span>
        </template>

        <template x-if="!status && !result">
            <span>Aktualizuj sklepy</span>
        </template>
    </button>
</div>

<script>
function jobCountdown({ createdAt, status, result }) {
    return {
        createdAt: createdAt,
        status: status,
        result: result,
        currentTime: Date.now(),
        remainingSeconds: 60,
        progress: 0,
        interval: null,

        init() {
            if (this.status === 'processing') {
                this.startCountdown();
            }

            this.$watch('status', (value) => {
                if (value === 'processing') {
                    this.startCountdown();
                } else {
                    this.stopCountdown();
                }
            });
        },

        startCountdown() {
            this.interval = setInterval(() => {
                this.currentTime = Date.now();
                const elapsed = (this.currentTime - new Date(this.createdAt).getTime()) / 1000;
                this.remainingSeconds = Math.max(0, 60 - Math.floor(elapsed));
                this.progress = Math.min(100, (elapsed / 60) * 100);

                if (this.remainingSeconds <= 0) {
                    this.stopCountdown();
                }
            }, 1000);
        },

        stopCountdown() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },

        clearJob(delay) {
            setTimeout(() => {
                this.status = null;
                this.result = null;
                this.progress = 0;
                this.remainingSeconds = 60;
            }, delay);
        }
    }
}
</script>
```

**Acceptance Criteria**:
- [ ] Alpine countdown updates every 1s
- [ ] Progress bar fills from 0-100% over 60s
- [ ] Cleanup on destroy (clearInterval)
- [ ] Auto-reset after 5s (success/error)

---

#### Task 5: CSS Progress Animations
**File**: `resources/css/admin/components.css`

**New Styles**:
```css
/* ETAP_13 - JOB Countdown Animations */
.btn-job-countdown {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.btn-job-running {
    background: linear-gradient(
        to right,
        var(--color-primary-dark) var(--progress-percent, 0%),
        var(--color-primary) var(--progress-percent, 0%)
    );
    transition: background 0.3s ease;
    cursor: not-allowed;
}

.btn-job-success {
    background-color: var(--color-success);
    color: white;
    transition: background 0.3s ease;
}

.btn-job-error {
    background-color: var(--color-error);
    color: white;
    transition: background 0.3s ease;
}

/* Pending Sync Visual States */
.pending-sync-badge {
    background-color: var(--color-warning);
    color: var(--color-text-dark);
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.field-pending-sync {
    position: relative;
    opacity: 0.6;
}

.field-pending-sync::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(255, 193, 7, 0.1) 10px,
        rgba(255, 193, 7, 0.1) 20px
    );
    pointer-events: none;
}
```

**Acceptance Criteria**:
- [ ] .btn-job-running has linear gradient progress
- [ ] .btn-job-success/error have color transitions
- [ ] .pending-sync-badge style matches mockup
- [ ] .field-pending-sync has diagonal stripe overlay

---

#### Task 6: Wire:poll Integration
**File**: `resources/views/livewire/products/management/product-form.blade.php`

**Add to Component Wrapper**:
```blade
<div
    wire:poll.5s="checkJobStatus"
    @if($activeJobId === null) wire:poll.stop @endif>

    {{-- Rest of component --}}
</div>
```

**Acceptance Criteria**:
- [ ] wire:poll calls checkJobStatus() every 5s
- [ ] Polling stops when $activeJobId is NULL
- [ ] No unnecessary API calls when no job active

---

### 2.4 DEPLOYMENT-SPECIALIST TASKS

**Priority**: 🟢 LOW (After frontend-specialist)

#### Task 1: Build & Deploy Assets
**Files**: All frontend changes

**Steps**:
1. `npm run build` (verify "✓ built in X.XXs")
2. Upload ALL `public/build/assets/*` (Vite regenerates ALL hashes)
3. Upload `public/build/.vite/manifest.json` → `public/build/manifest.json` (ROOT)
4. Clear cache: `php artisan view:clear && cache:clear && config:clear`

**Acceptance Criteria**:
- [ ] Build successful (no errors)
- [ ] All assets uploaded (including new CSS)
- [ ] Manifest in ROOT location
- [ ] Cache cleared

---

#### Task 2: Deploy Backend Files
**Files**: ProductForm.php, BulkPullProducts.php, migration, ProductShopData.php

**Steps**:
1. Upload `app/Http/Livewire/Products/Management/ProductForm.php`
2. Upload `app/Jobs/PrestaShop/BulkPullProducts.php`
3. Upload `app/Models/ProductShopData.php`
4. Upload migration `2025_11_17_add_last_push_at_to_product_shop_data.php`
5. Run: `php artisan migrate --force`

**Acceptance Criteria**:
- [ ] All PHP files uploaded
- [ ] Migration executed successfully
- [ ] No migration errors

---

#### Task 3: HTTP 200 Verification
**Command**:
```powershell
@('components-X.css', 'app-Y.css', 'admin-Z.css') | % {
    curl -I "https://ppm.mpptrade.pl/public/build/assets/$_"
}
```

**Acceptance Criteria**:
- [ ] All CSS files return HTTP 200
- [ ] No 404 errors
- [ ] Hashes match manifest.json

---

#### Task 4: Screenshot Verification
**Tool**: `_TOOLS/full_console_test.cjs`

**Command**:
```bash
node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products/edit/123" --show --tab=Sklepy
```

**Acceptance Criteria**:
- [ ] No console errors
- [ ] Buttons visible and styled correctly
- [ ] Countdown animation working
- [ ] wire:poll updates status

---

#### Task 5: Queue Worker Verification
**Command**:
```powershell
plink ... -batch "crontab -l | grep queue"
plink ... -batch "ps aux | grep 'queue:work'"
```

**Acceptance Criteria**:
- [ ] Cron job exists for queue:work
- [ ] Frequency documented (1min? 5min?)
- [ ] Process running (or scheduled)

---

## 3. DEPENDENCIES & BLOCKERS

### 3.1 CRITICAL DEPENDENCIES

**BEFORE START:**
- ✅ ProductForm.php exists and working
- ✅ Shop Tab implemented
- ✅ product_shop_data table exists
- ✅ SyncProductToPrestaShop JOB exists
- ✅ BulkSyncProducts JOB exists

**REQUIRED:**
- ❌ BulkPullProducts JOB - MUST CREATE (Task 2.1.1)
- ❌ last_push_at column - MUST ADD (Task 2.1.2)
- ❓ Queue worker active on Hostido - VERIFY (Task 2.4.5)

---

### 3.2 POTENTIAL BLOCKERS

**1. Queue Worker Frequency**
- **Risk**: If cron runs every 5min, countdown must be 0-300s (not 0-60s)
- **Impact**: UI shows "Oczekiwanie 45s..." but job won't start for 4 more minutes
- **Mitigation**: Verify cron frequency FIRST (Task 2.4.5), adjust countdown accordingly

**2. Wire:poll in Conditional Rendering**
- **Risk**: Known Livewire issue - wire:poll inside @if may not work
- **Reference**: `_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md`
- **Mitigation**: Put wire:poll OUTSIDE conditionals (Task 2.3.6)

**3. Alpine Cleanup**
- **Risk**: Memory leak if clearInterval() not called on component destroy
- **Impact**: Multiple intervals running after tab switches
- **Mitigation**: x-init with proper cleanup (Task 2.3.4)

**4. CSS Cache Issues**
- **Risk**: Laravel Vite helper caching old manifest.json
- **Reference**: `_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md`
- **Mitigation**: Clear ALL caches after deploy (Task 2.4.1)

---

### 3.3 NICE-TO-HAVE (NOT BLOCKERS)

- Enhanced error messages in toast notifications
- Progress percentage display (in addition to countdown)
- Sound notification on job completion
- Desktop notification API integration

---

## 4. IMPLEMENTATION ORDER

### RECOMMENDED SEQUENCE:

**WEEK 1: Backend Foundation**
```
Day 1-2: laravel-expert
  ├─ Task 1: BulkPullProducts JOB
  ├─ Task 2: last_push_at migration
  ├─ Task 3: ProductShopData helpers
  └─ Task 4: Anti-duplicate logic

Day 3: Testing & Review
  └─ Unit tests for BulkPullProducts
```

**WEEK 2: Livewire Integration**
```
Day 4-5: livewire-specialist
  ├─ Task 1: Add public properties
  ├─ Task 2: checkJobStatus() method
  ├─ Task 3: bulkUpdateShops() enhancement
  ├─ Task 4: bulkPullFromShops() method
  └─ Task 5: Pending changes detection

Day 6: Testing
  └─ Livewire component tests
```

**WEEK 3: UI/UX Implementation**
```
Day 7-9: frontend-specialist
  ├─ Task 1: Shop Tab footer buttons
  ├─ Task 2: Sidepanel bulk actions
  ├─ Task 3: Panel Sync timestamps
  ├─ Task 4: Alpine countdown
  ├─ Task 5: CSS animations
  └─ Task 6: wire:poll integration

Day 10: Browser testing
  └─ Screenshot verification
```

**WEEK 4: Deployment**
```
Day 11-12: deployment-specialist
  ├─ Task 1: Build & deploy assets
  ├─ Task 2: Deploy backend
  ├─ Task 3: HTTP 200 verification
  ├─ Task 4: Screenshot verification
  └─ Task 5: Queue worker verification

Day 13: Final testing + User acceptance
```

---

## 5. RISKS & RECOMMENDATIONS

### 5.1 HIGH RISK

**1. Queue Worker Not Running**
- **Probability**: MEDIUM
- **Impact**: CRITICAL (all JOBs fail)
- **Mitigation**: Verify FIRST (Task 2.4.5), setup cron if missing
- **Fallback**: Use sync driver for testing (NOT production)

**2. Wire:poll Performance**
- **Probability**: LOW
- **Impact**: MEDIUM (high API calls, server load)
- **Mitigation**: Use .visible modifier, stop polling when job complete
- **Optimization**: Consider Livewire polling throttle

---

### 5.2 MEDIUM RISK

**1. Countdown Accuracy**
- **Probability**: MEDIUM
- **Impact**: LOW (cosmetic - user sees inaccurate time)
- **Cause**: Cron frequency != 1min
- **Mitigation**: Adjust countdown max time based on cron schedule

**2. Pending Changes Detection**
- **Probability**: LOW
- **Impact**: MEDIUM (incorrect data shown)
- **Cause**: Cache stale, field mapping incorrect
- **Mitigation**: Thorough testing, cache invalidation on pull

---

### 5.3 RECOMMENDATIONS

**1. Phase Implementation**
- Start with Backend (Tasks 2.1.*) - stable foundation
- Test thoroughly before moving to Livewire
- UI last - easiest to fix

**2. Testing Strategy**
- Unit tests for BulkPullProducts JOB
- Integration tests for checkJobStatus()
- Browser tests for Alpine countdown
- Production smoke tests after deploy

**3. User Communication**
- Document new buttons in user guide
- Add tooltips explaining "Aktualizuj" vs "Wczytaj"
- Toast notifications for job start/complete/error

**4. Monitoring**
- Log all JOB dispatches (INFO level)
- Track job completion time (metrics)
- Alert on failed jobs (>5 in 1h)

---

## 6. SUCCESS CRITERIA

### ✅ DEFINITION OF DONE:

**Backend**:
- [ ] BulkPullProducts JOB created and tested
- [ ] last_push_at migration deployed
- [ ] ProductShopData helpers working
- [ ] Anti-duplicate logic prevents duplicate jobs

**Livewire**:
- [ ] Public properties for job monitoring added
- [ ] checkJobStatus() polls and updates UI
- [ ] bulkUpdateShops() dispatches BulkSyncProducts
- [ ] bulkPullFromShops() dispatches BulkPullProducts
- [ ] Pending changes detected dynamically

**Frontend**:
- [ ] Shop Tab footer buttons renamed and styled
- [ ] Sidepanel has "Aktualizuj sklepy" + "Wczytaj ze sklepów"
- [ ] Panel Sync shows correct timestamps (pull/push)
- [ ] Alpine countdown animation works (0-60s)
- [ ] CSS animations smooth (progress bar, success/error states)
- [ ] wire:poll updates job status every 5s

**Deployment**:
- [ ] All assets deployed (HTTP 200 verified)
- [ ] Backend files deployed (migration run)
- [ ] Screenshot verification passed (no console errors)
- [ ] Queue worker verified active

**User Experience**:
- [ ] Zero console errors on production
- [ ] Countdown shows remaining time accurately
- [ ] Success/Error states clear and visible
- [ ] No duplicate jobs created
- [ ] Pending changes display correct fields

---

## 7. NEXT STEPS

### IMMEDIATE ACTIONS (TODAY):

1. **User Decision**: Zatwierdzić ten plan implementacji?
2. **Queue Worker**: Zweryfikować status na Hostido (CRITICAL)
3. **Agent Delegation**: Przypisać laravel-expert do Tasks 2.1.* (WEEK 1)

### SHORT TERM (WEEK 1):

1. laravel-expert rozpoczyna pracę
2. Daily standup - progress updates
3. Code review po każdym task

### LONG TERM (WEEKS 2-4):

1. Sequential agent handoffs (Laravel → Livewire → Frontend → Deploy)
2. Testing at each phase
3. Production deployment Week 4

---

## 8. FILES MODIFIED

**Estimated File Changes:**

**New Files (2)**:
- `app/Jobs/PrestaShop/BulkPullProducts.php`
- `database/migrations/2025_11_17_add_last_push_at_to_product_shop_data.php`

**Modified Files (4)**:
- `app/Http/Livewire/Products/Management/ProductForm.php`
- `app/Models/ProductShopData.php`
- `resources/views/livewire/products/management/product-form.blade.php`
- `resources/css/admin/components.css`

**Total**: 6 files

---

## 9. ESTIMATED EFFORT

**Time Estimates:**

| Agent | Tasks | Estimated Hours |
|-------|-------|----------------|
| laravel-expert | 4 tasks | 16h (2 days) |
| livewire-specialist | 5 tasks | 20h (2.5 days) |
| frontend-specialist | 6 tasks | 24h (3 days) |
| deployment-specialist | 5 tasks | 8h (1 day) |
| **TOTAL** | **20 tasks** | **68h (~8.5 days)** |

**With Testing & Reviews**: ~12-13 days (2.5 weeks)

---

## 10. CONCLUSION

ETAP_13 refactoring jest dobrze zdefiniowany i gotowy do implementacji. Obecna architektura jest solidna - potrzebne są głównie:
1. Nowy BulkPullProducts JOB (backend)
2. Monitoring properties + methods (Livewire)
3. UI reorganization + countdown (frontend)

**RECOMMENDATION**: ✅ PROCEED with delegation to laravel-expert (Tasks 2.1.*)

**CRITICAL**: Verify queue worker FIRST przed rozpoczęciem prac (Task 2.4.5)

---

**Report Generated**: 2025-11-17
**Agent**: Expert Planning Manager (architect)
**Status**: ✅ READY FOR DELEGATION
