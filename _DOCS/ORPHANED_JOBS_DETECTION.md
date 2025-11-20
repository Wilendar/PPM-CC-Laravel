# ORPHANED JOBS DETECTION SYSTEM

**Created:** 2025-11-06
**Phase:** FAZA 9 Phase 3 - Queue Jobs Monitoring Dashboard
**Status:** Design Complete + Implementation Ready

---

## 📋 OVERVIEW

System wykrywania orphaned jobs zapewnia cross-reference między:
- **`sync_jobs`** - Business logic model (SyncJob)
- **`jobs`** - Queue infrastructure (Laravel Queue)
- **`failed_jobs`** - Failed queue jobs

**Problem:** Czasami występuje rozsynchronizowanie między tymi tabelami, co prowadzi do:
- Orphaned queue jobs (pracują bez monitoringu)
- Orphaned SyncJob records (status "running" bez odpowiadającego queue job)

---

## 🎯 TWO TYPES OF ORPHANS

### Type 1: Queue Jobs without SyncJob Record

**What:** Queue job istnieje w `jobs` table, ale odpowiadający SyncJob record nie istnieje lub został usunięty.

**Why does this happen:**
1. **Failed SyncJob creation** - Job dispatched przed utworzeniem SyncJob
2. **Deleted SyncJob** - Administrator/system usunął SyncJob, ale queue job nadal działa
3. **Database transaction rollback** - SyncJob creation rollback, ale job już w queue
4. **Missing dependencies** - Job unserialize fails (deleted models)

**Impact:**
- Queue job działa bez business logic monitoring
- Brak progress tracking
- Brak error logging do SyncJob
- Brak user notification

**Example Scenario:**
```php
// SyncProductsJob dispatched
dispatch(new SyncProductsJob($syncJob));

// Transaction rollback (database error)
DB::rollBack(); // SyncJob not saved!

// Result: Queue job exists, but SyncJob record = NULL
```

**Detection Algorithm:**
```php
// For each job in jobs table:
1. Extract SyncProductsJob from payload
2. Access protected $syncJob property via Reflection
3. Check if SyncJob->id exists in sync_jobs table
4. If NOT exists → Type 1 orphan
```

**Resolution:**
- **Safe:** Review job details, cancel if no longer needed
- **Risky:** Delete queue job (may lose in-progress work)

---

### Type 2: SyncJob without Queue Job

**What:** SyncJob record istnieje z status `pending`/`running`, ale odpowiadający queue job NIE istnieje (ani w `jobs`, ani w `failed_jobs`).

**Why does this happen:**
1. **Queue job never dispatched** - SyncJob created, ale dispatch failed
2. **Manual queue job deletion** - Administrator usunął job z `jobs` table
3. **Queue worker crashed** - Job processing started, queue worker died, job lost
4. **Completed/Failed jobs** - Expected scenario (NOT orphaned!)

**Impact:**
- SyncJob stuck in "pending"/"running" status
- User sees job as "in progress" but nothing happens
- Dashboard shows false progress
- No way to resume or fix

**Example Scenario:**
```php
// SyncJob created
$syncJob = SyncJob::create([...]);

// Dispatch FAILS (queue connection error)
try {
    dispatch(new SyncProductsJob($syncJob));
} catch (Exception $e) {
    // Error not caught, job not dispatched
}

// Result: SyncJob status = "pending", but NO queue job
```

**Detection Algorithm:**
```php
// For each SyncJob with status 'pending' or 'running':
1. Check if queue_job_id exists in jobs table
2. If NOT → Check if exists in failed_jobs table
3. If NOT in both → Type 2 orphan
```

**Resolution:**
- **Safe:** Mark SyncJob as failed with reason "Queue job not found"
- **Risky:** Retry job (may cause duplicates if job actually running)

---

## 🔍 DETECTION METHOD

**Implementation:** `QueueJobsService::detectOrphanedJobs()`

**Location:** `app/Services/QueueJobsService.php`

**Usage:**
```php
$queueService = app(QueueJobsService::class);
$result = $queueService->detectOrphanedJobs();

// Returns:
[
    'queue_without_sync' => Collection, // Type 1 orphans
    'sync_without_queue' => Collection, // Type 2 orphans
    'recommendations' => [
        [
            'type' => 'queue_without_sync',
            'severity' => 'high',
            'count' => 3,
            'message' => 'Found queue jobs without SyncJob records...',
            'actions' => [
                'safe' => 'Review job details and cancel if no longer needed',
                'risky' => 'Delete queue jobs (may lose in-progress work)',
            ],
        ],
        // ...
    ]
]
```

**Performance:**
- Uses indexes: `idx_sync_jobs_status`, `idx_sync_jobs_queue`
- Filters BEFORE reflection (only SyncProductsJob)
- Efficient cross-reference queries
- No N+1 queries

---

## 🛠️ RESOLUTION STRATEGIES

### Auto-Cleanup (Safe Scenarios)

**Type 1 - Queue without SyncJob:**
```php
// SAFE when:
- Job unserialization fails (missing dependencies)
- Job is pending (not yet processing)
- Job attempts > 3 (retry exhausted)

// ACTION:
$queueService->cleanupOrphanedQueueJobs([1, 2, 3]);
```

**Type 2 - SyncJob without Queue:**
```php
// SAFE when:
- SyncJob status = 'pending' (never started)
- Created > 1 hour ago (stale)
- No queue job in jobs OR failed_jobs

// ACTION:
$queueService->markOrphanedSyncJobsAsFailed([123, 456]);
```

### Manual Review (Risky Scenarios)

**Type 1 - Queue without SyncJob:**
```php
// RISKY when:
- Job is processing (reserved_at IS NOT NULL)
- Job attempts = 1 (first try)
- Unknown unserialization error

// ACTION:
1. Review job payload manually
2. Check if SyncJob was deleted recently (audit_logs)
3. Decide: cancel vs investigate
```

**Type 2 - SyncJob without Queue:**
```php
// RISKY when:
- SyncJob status = 'running' (may be processing)
- Created < 5 minutes ago (may be delayed)
- queue_job_id exists (reference exists)

// ACTION:
1. Check queue worker status (is it running?)
2. Check if job in processing (queue:work --once)
3. Decide: mark failed vs wait
```

### Prevention Measures

**1. Transactional Job Dispatching:**
```php
// CORRECT pattern:
DB::transaction(function () use ($syncJob) {
    // Create SyncJob
    $syncJob->save();

    // Dispatch AFTER successful save
    dispatch(new SyncProductsJob($syncJob));
});
```

**2. Error Handling:**
```php
try {
    dispatch(new SyncProductsJob($syncJob));
} catch (Exception $e) {
    // Mark SyncJob as failed immediately
    $syncJob->fail('Job dispatch failed: ' . $e->getMessage());

    throw $e;
}
```

**3. Queue Job ID Tracking:**
```php
// Store queue job ID immediately after dispatch
$jobId = dispatch(new SyncProductsJob($syncJob));
$syncJob->update(['queue_job_id' => $jobId]);
```

**4. Monitoring & Alerts:**
```php
// Daily cron job:
php artisan queue:monitor orphaned-jobs

// Alert if orphaned count > threshold
if ($orphanedCount > 10) {
    Notification::send($admins, new OrphanedJobsAlert($orphanedCount));
}
```

---

## 📊 ACTIONABLE INSIGHTS

### Severity Levels

**HIGH (Type 1):**
- Queue jobs consuming resources without monitoring
- May process wrong data (deleted SyncJob = no filters/config)
- No error logging/notifications

**MEDIUM (Type 2):**
- False progress indicators
- User confusion (job "running" but nothing happens)
- Manual investigation required

**LOW (All Clear):**
- No orphans detected
- All cross-references valid

### Notification Triggers

**Immediate Alert:**
- Type 1 orphans > 5
- Type 2 orphans > 10
- Any orphan with status "running" > 1 hour

**Daily Digest:**
- Total orphans detected
- Trend analysis (increasing/decreasing)
- Top causes (why orphaned)

---

## 🎨 UI INTEGRATION (Phase 3)

### Dashboard Widget: Orphaned Jobs Counter

**Location:** Admin Dashboard (top row)

**Display:**
```
┌─────────────────────────────────┐
│  ⚠️ ORPHANED JOBS              │
│                                 │
│  Type 1: 3 queue jobs           │
│  Type 2: 7 sync jobs            │
│                                 │
│  [View Details] [Cleanup]       │
└─────────────────────────────────┘
```

**States:**
- **Green:** 0 orphans (all clear)
- **Yellow:** 1-10 orphans (monitor)
- **Red:** >10 orphans (action required)

### Orphaned Jobs Page

**Route:** `/admin/queue-jobs/orphaned`

**Sections:**

**1. Summary:**
```
Total Orphaned: 10
├─ Type 1 (Queue without Sync): 3 [High Priority]
└─ Type 2 (Sync without Queue): 7 [Medium Priority]

Last Check: 2025-11-06 14:30:25
[Run Detection Now]
```

**2. Type 1 Table:**
```
| Queue ID | Job Name           | Attempts | Created At          | Actions           |
|----------|--------------------|----------|---------------------|-------------------|
| 456      | SyncProductsJob    | 2        | 2025-11-06 10:00:00 | [Review] [Cancel] |
| 789      | SyncProductsJob    | 0        | 2025-11-06 12:30:00 | [Review] [Cancel] |
```

**3. Type 2 Table:**
```
| Sync ID | Job Name        | Status  | Created At          | Actions                |
|---------|-----------------|---------|---------------------|------------------------|
| 123     | Product Sync    | pending | 2025-11-06 08:00:00 | [Mark Failed] [Retry]  |
| 234     | Category Sync   | running | 2025-11-06 09:15:00 | [Mark Failed] [Retry]  |
```

**4. Bulk Actions:**
```
[x] Select All Type 1
[ ] Cleanup Selected (Delete queue jobs)

[x] Select All Type 2
[ ] Mark Failed (Safe cleanup)
[ ] Retry Selected (Risky)
```

**5. Recommendations Panel:**
```
📋 RECOMMENDATIONS:

⚠️ HIGH PRIORITY (Type 1):
Found 3 queue jobs without SyncJob records. These may indicate
failed job creation or deleted SyncJob records.

Actions:
✅ SAFE: Review job details and cancel if no longer needed
⚠️ RISKY: Delete queue jobs (may lose in-progress work)

⚠️ MEDIUM PRIORITY (Type 2):
Found 7 SyncJob records without queue jobs. These may be stuck
jobs that were never processed or manually deleted.

Actions:
✅ SAFE: Mark SyncJobs as failed with reason "Queue job not found"
⚠️ RISKY: Retry these jobs (may cause duplicates)
```

### Livewire Component

**Component:** `OrphanedJobsManager`

**Location:** `app/Http/Livewire/Admin/QueueJobs/OrphanedJobsManager.php`

**Key Methods:**
```php
public function detectOrphans()
public function cleanupQueueJob($id)
public function markSyncJobFailed($id)
public function retrySyncJob($id)
public function bulkCleanup(array $ids, string $type)
```

**Real-time Updates:**
```php
// After cleanup action:
$this->dispatch('orphans-updated');
$this->dispatch('notify', [
    'message' => "Cleaned up {$count} orphaned jobs",
    'type' => 'success'
]);
```

---

## 🧪 TESTING SCENARIOS

### Create Type 1 Orphan (for testing):
```php
// Create SyncJob
$syncJob = SyncJob::create([...]);

// Dispatch job
dispatch(new SyncProductsJob($syncJob));

// Delete SyncJob (simulate deletion)
$syncJob->forceDelete();

// Result: Queue job exists, SyncJob = NULL
```

### Create Type 2 Orphan (for testing):
```php
// Create SyncJob
$syncJob = SyncJob::create([
    'status' => 'pending',
    'queue_job_id' => 999999, // Non-existent
]);

// Don't dispatch job

// Result: SyncJob exists, queue job = NULL
```

### Verify Detection:
```php
$result = app(QueueJobsService::class)->detectOrphanedJobs();

assertEquals(1, $result['queue_without_sync']->count());
assertEquals(1, $result['sync_without_queue']->count());
```

---

## 📈 METRICS & MONITORING

### Key Metrics:
- **Orphaned Jobs Count** (Type 1 + Type 2)
- **Orphaned Jobs Age** (how long stuck)
- **Orphaned Jobs Trend** (increasing/decreasing)
- **Cleanup Success Rate** (auto-cleanup vs manual)

### Monitoring Commands:
```bash
# Detect orphaned jobs
php artisan queue:monitor orphaned

# Auto-cleanup (safe scenarios only)
php artisan queue:cleanup-orphaned --safe

# Generate report
php artisan queue:orphaned-report --email=admin@mpptrade.pl
```

### Grafana Dashboard:
```sql
-- Orphaned jobs count (last 24h)
SELECT
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
    COUNT(*) as orphaned_count
FROM (
    -- Type 1 query
    UNION ALL
    -- Type 2 query
) as orphaned
GROUP BY hour
ORDER BY hour DESC
```

---

## 🚀 IMPLEMENTATION CHECKLIST

**Backend:**
- [x] `QueueJobsService::detectOrphanedJobs()` - Detection logic
- [x] `QueueJobsService::cleanupOrphanedQueueJobs()` - Type 1 cleanup
- [x] `QueueJobsService::markOrphanedSyncJobsAsFailed()` - Type 2 cleanup
- [x] `QueueJobsService::generateOrphanedJobRecommendations()` - Recommendations

**Frontend (Phase 3):**
- [ ] `OrphanedJobsManager` Livewire component
- [ ] Orphaned jobs dashboard widget
- [ ] Orphaned jobs detail page
- [ ] Bulk cleanup actions
- [ ] Real-time notifications

**CLI Commands:**
- [ ] `php artisan queue:monitor orphaned`
- [ ] `php artisan queue:cleanup-orphaned --safe`
- [ ] `php artisan queue:orphaned-report`

**Tests:**
- [ ] Unit tests for detection logic
- [ ] Integration tests for cleanup methods
- [ ] E2E tests for UI interactions

**Documentation:**
- [x] This file (ORPHANED_JOBS_DETECTION.md)
- [ ] User guide (how to use orphaned jobs page)
- [ ] Admin training (when to cleanup vs investigate)

---

## 🔗 RELATED DOCUMENTATION

- [QUEUE_JOBS_MONITORING.md](_DOCS/QUEUE_JOBS_MONITORING.md) - Queue monitoring overview
- [TROUBLESHOOTING.md](_DOCS/TROUBLESHOOTING.md) - Known issues
- [PROJECT_KNOWLEDGE.md](_DOCS/PROJECT_KNOWLEDGE.md) - System architecture

---

## 📝 CHANGELOG

**2025-11-06:**
- Initial design and implementation
- Added detection logic for Type 1 and Type 2 orphans
- Implemented cleanup methods (safe + risky)
- Documented resolution strategies and prevention measures
- Designed UI integration for Phase 3

---

**End of Document**
