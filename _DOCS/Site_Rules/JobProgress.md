# JobProgress System - ETAP_07c Documentation

## Overview

Real-time job progress tracking system for asynchronous operations (import, export, sync).

**Implemented in:** ETAP_07c (FAZA 1-3)

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (Livewire + Alpine.js)              │
├─────────────────────────────────────────────────────────────────┤
│  ActiveOperationsBar (wire:poll.5s)                             │
│    └── JobProgressBar (wire:poll.3s) × N                        │
│          └── job-progress-icon partial                          │
├─────────────────────────────────────────────────────────────────┤
│                    BACKEND (Laravel)                            │
├─────────────────────────────────────────────────────────────────┤
│  JobProgressService ──→ JobProgress Model (database)            │
│  Queue Jobs (BulkImportProducts, BulkSyncProducts, etc.)        │
└─────────────────────────────────────────────────────────────────┘
```

## Components

### 1. JobProgressBar Component

**Location:** `app/Http/Livewire/Components/JobProgressBar.php`
**View:** `resources/views/livewire/components/job-progress-bar.blade.php`

**Features:**
- Real-time polling (wire:poll.3s)
- Animated progress bar with percentage
- Status indicators (running, completed, failed, pending, awaiting_user)
- Error count badge
- Conflict resolution buttons
- Accordion with rich details (FAZA 2)
- ARIA accessibility (FAZA 3)

**Usage:**
```blade
<livewire:components.job-progress-bar :jobId="$progressId" />
<livewire:components.job-progress-bar :jobId="$progressId" :shopId="$shopId" />
```

**Computed Properties:**
| Property | Type | Description |
|----------|------|-------------|
| `percentage` | int | Progress 0-100 |
| `status` | string | pending/running/completed/failed/awaiting_user |
| `message` | string | Human-readable status message |
| `errorCount` | int | Number of errors |
| `conflictCount` | int | Number of pending conflicts |
| `jobType` | string | import/export/sync/category_analysis |
| `jobTypeLabel` | string | Human-readable job type |
| `duration` | string | Formatted duration (e.g., "2m 15s") |
| `shopName` | string | Target shop name |
| `productsSample` | array | First 5 SKUs being processed |
| `metadataDetails` | array | Filtered metadata for display |

### 2. ActiveOperationsBar Component

**Location:** `app/Http/Livewire/Components/ActiveOperationsBar.php`
**View:** `resources/views/livewire/components/active-operations-bar.blade.php`

**Features:**
- Aggregates multiple JobProgressBar instances
- Optional shop filtering
- Collapse/expand all
- Active job count badges
- Auto-refresh (wire:poll.5s)

**Usage:**
```blade
<livewire:components.active-operations-bar />
<livewire:components.active-operations-bar :shopId="$shopId" />
```

### 3. JobProgressService

**Location:** `app/Services/JobProgressService.php`

**Key Methods:**
```php
// Create pending job (BEFORE dispatch)
$progressId = $service->createPendingJobProgress($jobId, $shop, 'import', $count);

// Start pending job (when actually running)
$service->startPendingJob($jobId, $actualCount);

// Update progress
$service->updateProgress($progressId, $current, $errors);

// Mark completed/failed
$service->markCompleted($progressId, ['imported' => 95]);
$service->markFailed($progressId, 'Error message');

// Query active jobs
$jobs = $service->getActiveJobs($shopId);
```

## Status Flow

```
pending → running → completed
                 └→ failed
                 └→ awaiting_user → (user action) → running/completed
```

## Event System (FAZA 3)

### Dispatched Events

| Event | Source | Payload | Description |
|-------|--------|---------|-------------|
| `job-started` | ProductList | `progressId: int` | New job created |
| `progress-completed` | JobProgressBar | `progressId: int` | Job finished |
| `job-hidden` | JobProgressBar | `progressId: int` | User closed bar |
| `refresh-active-operations` | Any | - | Force refresh |

### Listened Events

| Event | Listener | Action |
|-------|----------|--------|
| `job-started` | ActiveOperationsBar | Add job to list |
| `progress-completed` | ActiveOperationsBar | Refresh list |
| `job-hidden` | ActiveOperationsBar | Remove from list |
| `job-progress-updated.{jobId}` | JobProgressBar | Refresh progress |

**Example - Dispatch job-started:**
```php
// In component that creates jobs:
$progressId = $progressService->createPendingJobProgress(...);
$this->dispatch('job-started', progressId: $progressId);
```

## Database Schema

**Table:** `job_progresses`

| Column | Type | Description |
|--------|------|-------------|
| id | int | Primary key |
| job_id | string | Laravel queue job UUID |
| job_type | string | import/export/sync/category_analysis |
| shop_id | int | FK to prestashop_shops (nullable) |
| status | string | pending/running/completed/failed/awaiting_user |
| current_count | int | Current progress |
| total_count | int | Total items |
| error_count | int | Error count |
| error_details | json | Array of {sku, error} |
| metadata | json | Extra context (sample_skus, mode, etc.) |
| started_at | timestamp | When job started |
| completed_at | timestamp | When job finished |

## Accessibility (FAZA 3)

ARIA attributes implemented:
- `role="region"` + `aria-label` on main container
- `aria-live="polite"` for screen reader announcements
- `role="progressbar"` with `aria-valuenow/min/max`
- `aria-expanded` + `aria-controls` on accordion
- `aria-label` on interactive buttons
- `aria-hidden="true"` on decorative icons

## Job Type Labels

| job_type | Label (Polish) |
|----------|----------------|
| import | Import produktow |
| export | Eksport produktow |
| category_analysis | Analiza kategorii |
| bulk_export | Eksport zbiorczy |
| bulk_update | Aktualizacja zbiorcza |
| stock_sync | Synchronizacja stanow |
| price_sync | Synchronizacja cen |

## Best Practices

### Creating Jobs with Progress

```php
// 1. Generate job UUID BEFORE creating JobProgress
$jobId = (string) \Illuminate\Support\Str::uuid();

// 2. Create PENDING progress (UI shows immediately)
$progressId = $progressService->createPendingJobProgress(
    $jobId,
    $shop,
    'import',
    $estimatedCount
);

// 3. Dispatch event for immediate UI update
$this->dispatch('job-started', progressId: $progressId);

// 4. Dispatch the actual job WITH jobId
BulkImportProducts::dispatch($shop, 'all', [], $jobId);
```

### In Queue Job

```php
public function handle(): void
{
    $service = app(JobProgressService::class);

    // Start pending → running
    $service->startPendingJob($this->jobId, $actualCount);

    foreach ($products as $i => $product) {
        // Process...

        // Update every 5-10 items
        if ($i % 5 === 0) {
            $service->updateProgress($progressId, $i + 1);
        }
    }

    $service->markCompleted($progressId);
}
```

### Sample SKUs in Metadata

```php
// Collect first 5 SKUs for display in accordion
$sampleSkus = $products->take(5)->pluck('sku')->filter()->toArray();

$service->updateMetadata($progressId, [
    'sample_skus' => $sampleSkus,
    'mode' => $this->mode,
]);
```

## Troubleshooting

### Progress bar not appearing
1. Check `JobProgress` record exists in database
2. Verify `wire:poll` is working (inspect network tab)
3. Check `isVisible` property is true

### Progress stuck at pending
1. Queue worker running? `php artisan queue:work`
2. Job dispatched? Check `failed_jobs` table
3. Check Laravel logs for exceptions

### Events not received
1. Verify event name matches (case-sensitive)
2. Check `#[On('event-name')]` attribute syntax
3. Ensure components are on same page

## Files Reference

```
app/
├── Http/Livewire/Components/
│   ├── JobProgressBar.php
│   └── ActiveOperationsBar.php
├── Models/
│   └── JobProgress.php
├── Services/
│   └── JobProgressService.php
└── Jobs/PrestaShop/
    ├── BulkImportProducts.php
    └── BulkSyncProducts.php

resources/views/livewire/components/
├── job-progress-bar.blade.php
├── active-operations-bar.blade.php
└── partials/
    └── job-progress-icon.blade.php
```
