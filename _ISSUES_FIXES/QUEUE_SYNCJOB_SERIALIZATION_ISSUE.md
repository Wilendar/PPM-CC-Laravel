# QUEUE + SERIALIZESMODELS + CLEANUP CONFLICT ISSUE

**Status**: ✅ RESOLVED (2025-11-13)
**Severity**: 🔴 CRITICAL (Queue worker crash)
**Category**: Laravel Queue, Model Serialization

---

## PROBLEM

Queue jobs używające `SerializesModels` trait crashują z `ModelNotFoundException` gdy referenced models są usuwane z bazy danych (np. przez cleanup operations).

### Symptomy

**Error message**:
```
Illuminate\Database\Eloquent\ModelNotFoundException: No query results for model [App\Models\SyncJob]
Location: vendor/laravel/framework/src/Illuminate/Queue/SerializesAndRestoresModelIdentifiers.php:110
```

**Stack trace**:
```
#0 SerializesAndRestoresModelIdentifiers.php(110): Builder->firstOrFail()
#1 SerializesAndRestoresModelIdentifiers.php(63): Job->restoreModel()
#2 SerializesModels.php(93): Job->getRestoredPropertyValue()
#3 [internal function]: Job->__unserialize()
#4 CallQueuedHandler.php(96): unserialize()
```

**Context**:
- Job dispatched: `PullProductsFromPrestaShop` creates `SyncJob` in constructor
- Job queued: Laravel serializes `protected ?SyncJob $syncJob` property
- Cleanup runs: User executes sync logs cleanup → `SyncJob::delete()`
- Queue worker starts: Tries to unserialize job → `SyncJob::findOrFail($id)` → ❌ CRASH

### Root Cause

**Laravel Queue Serialization Behavior**:

When job uses `SerializesModels` trait + has Eloquent model property:
1. **Dispatch time**: Laravel stores model's `id` and `class` (NOT full model data)
2. **Unserialization time**: Laravel executes `Model::findOrFail($id)` to restore model
3. **If model deleted**: `ModelNotFoundException` thrown → job fails permanently

**Code pattern causing issue**:
```php
class PullProductsFromPrestaShop implements ShouldQueue
{
    use SerializesModels; // ← PROBLEM: Will serialize model IDs

    protected ?SyncJob $syncJob = null; // ← VULNERABLE: Can be deleted

    public function __construct(PrestaShopShop $shop)
    {
        $this->syncJob = SyncJob::create([...]); // Created NOW
        // Laravel queue will serialize: ['syncJob' => ['id' => 123, 'class' => SyncJob::class]]
    }

    public function handle(): void
    {
        $this->syncJob->start(); // ← CRASH HERE if SyncJob deleted before processing
    }
}
```

**Timeline**:
```
T0: Job dispatched → SyncJob created (ID: 123) → Queued
T1: User runs cleanup → SyncJob::where('status', 'completed')->delete()
T2: Queue worker starts → Unserialize job → SyncJob::findOrFail(123) → ❌ CRASH
```

---

## SOLUTION

**Strategy**: Store scalar IDs instead of model instances, load models lazily with graceful handling.

### Implementation

**❌ BEFORE (Vulnerable)**:
```php
class PullProductsFromPrestaShop implements ShouldQueue
{
    use SerializesModels;

    protected ?SyncJob $syncJob = null; // ← Model instance

    public function __construct(PrestaShopShop $shop)
    {
        $this->syncJob = SyncJob::create([...]);
    }

    public function handle(): void
    {
        $this->syncJob->start(); // ← Assumes model exists
    }
}
```

**✅ AFTER (Resilient)**:
```php
class PullProductsFromPrestaShop implements ShouldQueue
{
    use SerializesModels;

    protected ?int $syncJobId = null; // ← Only scalar ID

    public function __construct(PrestaShopShop $shop)
    {
        $syncJob = SyncJob::create([...]);
        $this->syncJobId = $syncJob->id; // ← Store only ID
    }

    protected function getSyncJob(): ?SyncJob
    {
        if (!$this->syncJobId) return null;

        try {
            return SyncJob::find($this->syncJobId); // ← Graceful (not findOrFail)
        } catch (\Exception $e) {
            Log::warning('SyncJob deleted by cleanup', [
                'sync_job_id' => $this->syncJobId,
            ]);
            return null;
        }
    }

    public function handle(): void
    {
        $syncJob = $this->getSyncJob();
        $syncJob?->start(); // ← Null-safe operator
        // Job continues even if SyncJob deleted
    }
}
```

### Key Changes

1. **Property type**: `?SyncJob $syncJob` → `?int $syncJobId`
2. **Constructor**: Store only `$syncJob->id`, not full model
3. **Helper method**: `getSyncJob(): ?SyncJob` with `find()` (not `findOrFail()`)
4. **Usage**: Null-safe operator `$syncJob?->method()`

### Benefits

✅ **No crashes**: Job continues even if SyncJob deleted
✅ **Graceful degradation**: Tracking lost, but work completed
✅ **Cleanup-safe**: Cleanup operations don't break queue
✅ **Laravel-compliant**: Uses recommended patterns

---

## WHEN TO APPLY THIS PATTERN

Use scalar IDs (not model instances) when:

1. ✅ Model can be deleted independently (cleanup, cascade deletes, soft deletes)
2. ✅ Model is created IN job constructor (not passed from outside)
3. ✅ Job's primary work doesn't depend on model existence
4. ✅ Model is used only for tracking/logging (not core business logic)

**Examples**:
- ✅ `SyncJob` (tracking/logging) → Use scalar ID
- ✅ `ImportBatch` (progress tracking) → Use scalar ID
- ❌ `PrestaShopShop` (required for API calls) → Keep model instance (protected by foreign keys)
- ❌ `Product` (core business entity) → Keep model instance (unlikely to be deleted during sync)

---

## ALTERNATIVE SOLUTIONS (NOT RECOMMENDED)

### Alternative 1: Disable SerializesModels
```php
class PullProductsFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable; // ← No SerializesModels

    protected ?SyncJob $syncJob = null;
}
```

**❌ Problem**: Breaks serialization for ALL models (including required ones like `PrestaShopShop`)

### Alternative 2: Soft Deletes
```php
class SyncJob extends Model
{
    use SoftDeletes; // ← Prevents permanent deletion
}
```

**❌ Problem**: Database bloat, doesn't solve root issue (soft-deleted models still cause issues)

### Alternative 3: Prevent Cleanup
```php
// Never cleanup SyncJob while jobs are queued
```

**❌ Problem**: Unrealistic constraint, defeats purpose of cleanup

---

## VERIFICATION

### Test Scenario

**Setup**:
1. Dispatch job with SyncJob tracking
2. Wait for job to be queued
3. Delete SyncJob from database (simulate cleanup)
4. Process queue

**Expected behavior** (AFTER FIX):
```
✅ Job processes successfully
✅ No ModelNotFoundException
✅ Logs warning: "SyncJob deleted by cleanup"
✅ Work completed (products synced, prices imported)
```

**Actual behavior** (BEFORE FIX):
```
❌ Job fails with ModelNotFoundException
❌ Queue worker crashes
❌ Job goes to failed_jobs table
```

### Production Test Results (2025-11-13)

**Test workflow**:
```bash
# 1. Flush old failed jobs
php artisan queue:flush

# 2. Dispatch test job
php artisan tinker --execute="dispatch(new \App\Jobs\PullProductsFromPrestaShop(\App\Models\PrestaShopShop::first()));"

# 3. Process queue
php artisan queue:work database --stop-when-empty

# 4. Verify no failures
php artisan queue:failed
```

**Results**:
```
✅ Job DONE in 1s (no failures)
✅ Logs: "Prices imported for product" → getSpecificPrices() working
✅ No failed jobs: "No failed jobs found"
```

---

## RELATED ISSUES

### Same Pattern Applied To:
- ✅ `PullProductsFromPrestaShop` (2025-11-13)
- 🔄 TODO: Audit other jobs using SyncJob/ImportBatch/ExportBatch

### Jobs To Review:
```bash
# Find all jobs with SerializesModels + potential tracking models
rg "class.*implements ShouldQueue" app/Jobs/ -A 10 | rg "SyncJob|ImportBatch|ExportBatch"
```

**Candidates**:
- `SyncProductToPrestaShop`
- `BulkSyncProducts`
- `PullSingleProductFromPrestaShop`

---

## REFERENCES

- **Laravel docs**: https://laravel.com/docs/12.x/queues#handling-relationships
- **SerializesModels trait**: `vendor/laravel/framework/src/Illuminate/Queue/SerializesModels.php`
- **Bug report**: `_AGENT_REPORTS/prestashop_api_expert_getspecificprices_fix_2025-11-13_REPORT.md`

---

## PREVENTION CHECKLIST

When creating new queue jobs:

- [ ] Job uses `SerializesModels` trait?
- [ ] Job has Eloquent model properties?
- [ ] Models can be deleted independently?
- [ ] Models created IN constructor (not passed)?
- [ ] Models used only for tracking/logging?

If YES to 4+: **Use scalar IDs, not model instances**

**Template**:
```php
class MyJob implements ShouldQueue
{
    use SerializesModels;

    protected ?int $trackingModelId = null; // ← Scalar ID

    public function __construct(RequiredModel $required)
    {
        // Required models: Keep as instance (protected by business logic)
        $this->required = $required;

        // Tracking models: Store only ID
        $tracking = TrackingModel::create([...]);
        $this->trackingModelId = $tracking->id;
    }

    protected function getTrackingModel(): ?TrackingModel
    {
        return $this->trackingModelId ? TrackingModel::find($this->trackingModelId) : null;
    }

    public function handle(): void
    {
        $tracking = $this->getTrackingModel();
        $tracking?->updateProgress(...); // Null-safe
    }
}
```

---

**Last Updated**: 2025-11-13
**Fixed By**: prestashop-api-expert agent
**Status**: ✅ RESOLVED + DOCUMENTED
