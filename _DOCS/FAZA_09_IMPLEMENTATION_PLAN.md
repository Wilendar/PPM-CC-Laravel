# FAZA 9: QUEUE JOBS MONITORING DASHBOARD - IMPLEMENTATION PLAN

**Status:** 🚨 **URGENT** - Required due to stuck "Oczekujące" statuses
**Est. Time:** 6-8h (streamlined implementation)
**Priority:** CRITICAL
**Date:** 2025-11-06

---

## 🎯 USER REQUIREMENT (PILNY TRYB)

**Problem:**
- Statusy "Oczekujące" stuck bez możliwości śledzenia
- Brak widoczności aktywnie działających JOBów
- Brak narzędzi do zarządzania (retry, cancel)
- Utknięte JOBy nie są wykrywane

**User Request:**
> "Wciaż mamy wiszące statusy 'Oczekujące' w zwiazku z brakiem możliwosci śledzenia aktualnie JOBów musimy przystąpić wyjątkowo musimy w trybie pilnym przystąpić do FAZA 9 Przebudowę panelu /admin/shops/sync aby uwzględniała wszystkie aktywne JOBy, błędy JOBów, utknięte JOBy, narzędzia do zarządzania JOBami, takie jak ponowienie, anulowanie i inne"

---

## 📋 ARCHITECTURE DECISION

**Option 1: Import/Export Batches (ORIGINAL PLAN)**
- Track import_batches + export_batches tables only
- Limited to specific operations (import XLSX, import API, export)
- Doesn't cover general queue jobs (SyncProductToPrestaShop, etc.)

**Option 2: Laravel Queue System (RECOMMENDED FOR URGENT)**
- Track all jobs from `jobs` + `failed_jobs` tables
- Covers ALL queue operations (sync, import, export, pull, etc.)
- Native Laravel integration (no custom tables needed)
- **SELECTED** - faster implementation, more comprehensive

---

## 🏗️ STREAMLINED IMPLEMENTATION (6-8h)

### PHASE 1: Backend - Queue Jobs Service (2h)
**Agent:** laravel-expert
**Priority:** CRITICAL

**Task 1.1: QueueJobsService (1.5h)**

Create `app/Services/QueueJobsService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueJobsService
{
    /**
     * Get all active jobs (pending + processing)
     */
    public function getActiveJobs()
    {
        return DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'reserved_at',
                'available_at',
                'created_at',
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($job) => $this->parseJob($job));
    }

    /**
     * Get failed jobs
     */
    public function getFailedJobs()
    {
        return DB::table('failed_jobs')
            ->select([
                'id',
                'uuid',
                'connection',
                'queue',
                'payload',
                'exception',
                'failed_at',
            ])
            ->orderBy('failed_at', 'desc')
            ->get()
            ->map(fn($job) => $this->parseFailedJob($job));
    }

    /**
     * Get stuck jobs (processing > 5 minutes)
     */
    public function getStuckJobs()
    {
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;

        return DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', $fiveMinutesAgo)
            ->get()
            ->map(fn($job) => $this->parseJob($job));
    }

    /**
     * Parse job payload
     */
    private function parseJob($job)
    {
        $payload = json_decode($job->payload, true);
        $commandName = $payload['displayName'] ?? 'Unknown';
        $data = unserialize($payload['data']['command']);

        return [
            'id' => $job->id,
            'queue' => $job->queue,
            'job_name' => $commandName,
            'status' => $job->reserved_at ? 'processing' : 'pending',
            'attempts' => $job->attempts,
            'data' => $this->extractJobData($data),
            'created_at' => Carbon::createFromTimestamp($job->created_at),
            'reserved_at' => $job->reserved_at ? Carbon::createFromTimestamp($job->reserved_at) : null,
        ];
    }

    /**
     * Parse failed job
     */
    private function parseFailedJob($job)
    {
        $payload = json_decode($job->payload, true);
        $commandName = $payload['displayName'] ?? 'Unknown';

        return [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'job_name' => $commandName,
            'queue' => $job->queue,
            'exception' => $job->exception,
            'failed_at' => $job->failed_at,
        ];
    }

    /**
     * Extract useful data from job
     */
    private function extractJobData($data)
    {
        if (method_exists($data, 'product')) {
            return ['product_id' => $data->product->id ?? null, 'sku' => $data->product->sku ?? null];
        }

        if (method_exists($data, 'shop')) {
            return ['shop_id' => $data->shop->id ?? null, 'shop_name' => $data->shop->name ?? null];
        }

        return [];
    }

    /**
     * Retry failed job
     */
    public function retryFailedJob($uuid)
    {
        return \Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    /**
     * Delete failed job
     */
    public function deleteFailedJob($uuid)
    {
        return DB::table('failed_jobs')->where('uuid', $uuid)->delete();
    }

    /**
     * Cancel pending job
     */
    public function cancelPendingJob($id)
    {
        return DB::table('jobs')->where('id', $id)->delete();
    }
}
```

**Task 1.2: Unit Tests (0.5h)**

Create `tests/Unit/Services/QueueJobsServiceTest.php`:
- Test getActiveJobs()
- Test getFailedJobs()
- Test getStuckJobs()
- Test retryFailedJob()
- Test cancelPendingJob()

**Deliverables:**
- `app/Services/QueueJobsService.php` (~150 lines)
- `tests/Unit/Services/QueueJobsServiceTest.php` (~100 lines)

---

### PHASE 2: Livewire Component (2-3h)
**Agent:** livewire-specialist
**Priority:** HIGH

**Task 2.1: QueueJobsDashboard Component (2h)**

Create `app/Http/Livewire/Admin/QueueJobsDashboard.php`:

```php
<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Services\QueueJobsService;

class QueueJobsDashboard extends Component
{
    public $filter = 'all'; // all, pending, processing, failed, stuck
    public $selectedQueue = 'all';

    protected $queueService;

    public function boot(QueueJobsService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function render()
    {
        $jobs = match($this->filter) {
            'all' => $this->queueService->getActiveJobs(),
            'failed' => $this->queueService->getFailedJobs(),
            'stuck' => $this->queueService->getStuckJobs(),
            default => $this->queueService->getActiveJobs()
                ->where('status', $this->filter),
        };

        return view('livewire.admin.queue-jobs-dashboard', [
            'jobs' => $jobs,
            'stats' => $this->getStats(),
        ]);
    }

    private function getStats()
    {
        $active = $this->queueService->getActiveJobs();
        $failed = $this->queueService->getFailedJobs();
        $stuck = $this->queueService->getStuckJobs();

        return [
            'pending' => $active->where('status', 'pending')->count(),
            'processing' => $active->where('status', 'processing')->count(),
            'failed' => $failed->count(),
            'stuck' => $stuck->count(),
        ];
    }

    public function retryJob($uuid)
    {
        $this->queueService->retryFailedJob($uuid);
        session()->flash('message', 'Job został dodany ponownie do kolejki');
    }

    public function cancelJob($id)
    {
        $this->queueService->cancelPendingJob($id);
        session()->flash('message', 'Job został anulowany');
    }

    public function deleteFailedJob($uuid)
    {
        $this->queueService->deleteFailedJob($uuid);
        session()->flash('message', 'Failed job został usunięty');
    }

    public function retryAllFailed()
    {
        \Artisan::call('queue:retry all');
        session()->flash('message', 'Wszystkie failed jobs zostały dodane ponownie');
    }

    public function clearAllFailed()
    {
        DB::table('failed_jobs')->truncate();
        session()->flash('message', 'Wszystkie failed jobs zostały usunięte');
    }
}
```

**Task 2.2: Routes (0.5h)**

Add to `routes/web.php`:

```php
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/queue-jobs', \App\Http\Livewire\Admin\QueueJobsDashboard::class)
        ->name('admin.queue-jobs');
});
```

**Task 2.3: Tests (0.5h)**

Create `tests/Feature/QueueJobsDashboardTest.php`:
- Test rendering
- Test filter changes
- Test retry action
- Test cancel action
- Test stats display

**Deliverables:**
- `app/Http/Livewire/Admin/QueueJobsDashboard.php` (~120 lines)
- `routes/web.php` (1 new route)
- `tests/Feature/QueueJobsDashboardTest.php` (~80 lines)

---

### PHASE 3: Frontend UI (2-3h)
**Agent:** frontend-specialist
**Priority:** HIGH

**Task 3.1: Dashboard View (2h)**

Create `resources/views/livewire/admin/queue-jobs-dashboard.blade.php`:

```blade
<div class="queue-jobs-dashboard">
    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card stat-pending">
            <div class="stat-label">Oczekujące</div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
        </div>
        <div class="stat-card stat-processing">
            <div class="stat-label">W trakcie</div>
            <div class="stat-value">{{ $stats['processing'] }}</div>
        </div>
        <div class="stat-card stat-failed">
            <div class="stat-label">Błędy</div>
            <div class="stat-value">{{ $stats['failed'] }}</div>
        </div>
        <div class="stat-card stat-stuck">
            <div class="stat-label">Utknięte</div>
            <div class="stat-value">{{ $stats['stuck'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters">
        <button wire:click="$set('filter', 'all')"
                class="filter-btn {{ $filter === 'all' ? 'active' : '' }}">
            Wszystkie
        </button>
        <button wire:click="$set('filter', 'pending')"
                class="filter-btn {{ $filter === 'pending' ? 'active' : '' }}">
            Oczekujące
        </button>
        <button wire:click="$set('filter', 'processing')"
                class="filter-btn {{ $filter === 'processing' ? 'active' : '' }}">
            W trakcie
        </button>
        <button wire:click="$set('filter', 'failed')"
                class="filter-btn {{ $filter === 'failed' ? 'active' : '' }}">
            Błędy
        </button>
        <button wire:click="$set('filter', 'stuck')"
                class="filter-btn {{ $filter === 'stuck' ? 'active' : '' }}">
            Utknięte
        </button>
    </div>

    {{-- Actions --}}
    @if($filter === 'failed')
    <div class="bulk-actions">
        <button wire:click="retryAllFailed"
                wire:confirm="Ponowić wszystkie failed jobs?"
                class="btn-primary">
            Ponów wszystkie
        </button>
        <button wire:click="clearAllFailed"
                wire:confirm="Usunąć wszystkie failed jobs?"
                class="btn-danger">
            Usuń wszystkie
        </button>
    </div>
    @endif

    {{-- Jobs Table --}}
    <div class="jobs-table" wire:poll.5s>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Name</th>
                    <th>Queue</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Attempts</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                <tr class="job-row job-status-{{ $job['status'] ?? 'unknown' }}">
                    <td>{{ $job['id'] }}</td>
                    <td>{{ $job['job_name'] }}</td>
                    <td>{{ $job['queue'] }}</td>
                    <td>
                        <span class="status-badge status-{{ $job['status'] ?? 'unknown' }}">
                            {{ ucfirst($job['status'] ?? 'Unknown') }}
                        </span>
                    </td>
                    <td>
                        @if(isset($job['data']['sku']))
                            SKU: {{ $job['data']['sku'] }}
                        @elseif(isset($job['data']['shop_name']))
                            Shop: {{ $job['data']['shop_name'] }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $job['attempts'] ?? 0 }}</td>
                    <td>{{ $job['created_at']->diffForHumans() }}</td>
                    <td class="job-actions">
                        @if($filter === 'failed')
                            <button wire:click="retryJob('{{ $job['uuid'] }}')"
                                    class="btn-action btn-retry">
                                Ponów
                            </button>
                            <button wire:click="deleteFailedJob('{{ $job['uuid'] }}')"
                                    wire:confirm="Usunąć ten job?"
                                    class="btn-action btn-delete">
                                Usuń
                            </button>
                        @elseif(($job['status'] ?? '') === 'pending')
                            <button wire:click="cancelJob({{ $job['id'] }})"
                                    wire:confirm="Anulować ten job?"
                                    class="btn-action btn-cancel">
                                Anuluj
                            </button>
                        @elseif(($job['status'] ?? '') === 'stuck')
                            <button wire:click="cancelJob({{ $job['id'] }})"
                                    wire:confirm="Anulować utknięty job?"
                                    class="btn-action btn-cancel">
                                Anuluj
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        Brak jobów do wyświetlenia
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

**Task 3.2: CSS Styles (1h)**

Create `resources/css/admin/queue-jobs.css`:

```css
/* Queue Jobs Dashboard Styles */

.queue-jobs-dashboard {
    padding: 24px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card.stat-pending {
    border-left: 4px solid var(--color-info);
}

.stat-card.stat-processing {
    border-left: 4px solid var(--color-warning);
}

.stat-card.stat-failed {
    border-left: 4px solid var(--color-error);
}

.stat-card.stat-stuck {
    border-left: 4px solid #ff6b35;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-gray-600);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-gray-900);
}

/* Filters */
.filters {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid var(--color-gray-300);
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: var(--color-gray-50);
}

.filter-btn.active {
    background: var(--color-brand-500);
    color: white;
    border-color: var(--color-brand-500);
}

/* Bulk Actions */
.bulk-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

/* Jobs Table */
.jobs-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.jobs-table table {
    width: 100%;
    border-collapse: collapse;
}

.jobs-table thead {
    background: var(--color-gray-50);
}

.jobs-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: var(--color-gray-700);
    border-bottom: 1px solid var(--color-gray-200);
}

.jobs-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-gray-200);
}

.job-row.job-status-stuck {
    background: #fff3e0;
}

/* Status Badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.status-pending {
    background: #e3f2fd;
    color: #1976d2;
}

.status-badge.status-processing {
    background: #fff3e0;
    color: #f57c00;
}

.status-badge.status-failed {
    background: #ffebee;
    color: #c62828;
}

/* Actions */
.job-actions {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.875rem;
    cursor: pointer;
    border: 1px solid;
    transition: all 0.2s;
}

.btn-action.btn-retry {
    background: var(--color-success);
    color: white;
    border-color: var(--color-success);
}

.btn-action.btn-cancel {
    background: var(--color-error);
    color: white;
    border-color: var(--color-error);
}

.btn-action.btn-delete {
    background: var(--color-gray-500);
    color: white;
    border-color: var(--color-gray-500);
}

.btn-action:hover {
    opacity: 0.8;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: var(--color-gray-500);
}
```

**Task 3.3: Import CSS in app.css (0.5h)**

Update `resources/css/app.css`:
```css
@import './admin/queue-jobs.css';
```

**Deliverables:**
- `resources/views/livewire/admin/queue-jobs-dashboard.blade.php` (~150 lines)
- `resources/css/admin/queue-jobs.css` (~200 lines)

---

## 🚀 DEPLOYMENT WORKFLOW

### Step 1: Deploy Backend
```bash
# Upload Service
pscp app/Services/QueueJobsService.php host:/path/

# Run migrations (if needed for jobs table - probably exists)
plink host "php artisan migrate"
```

### Step 2: Deploy Livewire Component
```bash
# Upload component
pscp app/Http/Livewire/Admin/QueueJobsDashboard.php host:/path/

# Upload view
pscp resources/views/livewire/admin/queue-jobs-dashboard.blade.php host:/path/

# Update routes
pscp routes/web.php host:/path/
```

### Step 3: Deploy Frontend
```bash
# Build assets
npm run build

# Deploy CSS
pscp -r public/build/assets/* host:/path/

# Deploy manifest
pscp public/build/.vite/manifest.json host:/public/build/manifest.json
```

### Step 4: Clear Caches
```bash
plink host "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

### Step 5: Verify
```bash
# Screenshot verification
node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/queue-jobs" --show
```

---

## ✅ SUCCESS CRITERIA

- [ ] Dashboard displays all active jobs (pending + processing)
- [ ] Failed jobs displayed separately with exception details
- [ ] Stuck jobs detected (processing > 5 minutes)
- [ ] Stats cards show correct counts (pending, processing, failed, stuck)
- [ ] Real-time updates (wire:poll.5s) working
- [ ] Retry button works for failed jobs
- [ ] Cancel button works for pending jobs
- [ ] Delete button removes failed jobs from database
- [ ] Bulk actions work (retry all, clear all)
- [ ] No console errors
- [ ] HTTP 200 for all assets
- [ ] Screenshot verification passed

---

## 📊 ESTIMATION BREAKDOWN

| Task | Agent | Time Est. | Priority |
|------|-------|-----------|----------|
| 1.1 QueueJobsService | laravel-expert | 1.5h | CRITICAL |
| 1.2 Unit Tests | laravel-expert | 0.5h | HIGH |
| 2.1 Dashboard Component | livewire-specialist | 2h | CRITICAL |
| 2.2 Routes | livewire-specialist | 0.5h | HIGH |
| 2.3 Feature Tests | livewire-specialist | 0.5h | MEDIUM |
| 3.1 Dashboard View | frontend-specialist | 2h | CRITICAL |
| 3.2 CSS Styles | frontend-specialist | 1h | HIGH |
| **TOTAL** | | **6-8h** | |

---

## 🔗 RELATED DOCS

- [ETAP_07_Prestashop_API.md](Plan_Projektu/ETAP_07_Prestashop_API.md) - Original FAZA 9 plan
- [TROUBLESHOOTING.md](_DOCS/TROUBLESHOOTING.md) - Known issues
- [livewire-dev-guidelines](.claude/skills/guidelines/livewire-dev-guidelines/SKILL.md) - Livewire patterns
- [frontend-dev-guidelines](.claude/skills/guidelines/frontend-dev-guidelines/SKILL.md) - Frontend rules

---

**Plan Created:** 2025-11-06
**Last Updated:** 2025-11-06
**Status:** READY FOR IMPLEMENTATION
**Approver:** Kamil Wiliński
