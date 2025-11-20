# RAPORT PRACY AGENTA: debugger

**Data**: 2025-11-12 09:30
**Agent**: debugger (Expert code debugger for PPM-CC-Laravel)
**Zadanie**: Diagnoza BUG #9 - System wyświetlania zadań synchronizacji w `/admin/shops/sync`

---

## 🐛 SYMPTOMY

### BUG #9.1: Ostatnie zadania synchronizacji nie pokazują się w UI
- **Lokalizacja**: https://ppm.mpptrade.pl/admin/shops (zakładka "Ostatnie zadania synchronizacji")
- **Problem**: Najnowsze wpisy są sprzed 4 dni (2025-11-07), brak wpisów z dzisiaj (2025-11-12)
- **Oczekiwanie**: Po kliknięciu "← Import" lub "Synchronizuj →" powinny pojawiać się nowe wpisy
- **Context**: BUG #7 został wdrożony (przycisk Import + SyncJob tracking)

### BUG #9.2: Brakuje UI do zarządzania historią synchronizacji
- Brak przycisku "Wyczyść Logi" (hard delete starych wpisów)
- Brak przycisku "Archiwizuj" (soft delete lub move do archive table)
- Oczekiwanie: Możliwość ręcznego czyszczenia/archiwizacji przed auto CRON job

---

## 🔍 ROOT CAUSE ANALYSIS

### 7 Potencjalnych Przyczyn (Initial Analysis)

1. ✅ **Query w SyncController filtruje zbyt wąsko** → **CONFIRMED ROOT CAUSE**
2. ⚠️ Brak refresh/polling w Livewire component → Może być dodatkowym problem
3. ❌ SyncJob nie jest tworzony podczas dispatch → NIE (diagnostic pokazał że SyncJob jest tworzony)
4. ❌ Cache issues (Livewire property cache) → NIE (diagnostic pokazał że dane są w bazie)
5. ❌ Pagination problem (nowe wpisy na stronie 2+) → NIE (query zwraca tylko 10)
6. ❌ Order by nieprawidłowy (ASC zamiast DESC) → NIE (query używa `latest()`)
7. ❌ Filter domyślnie ustawiony na specific shop/status → NIE (brak filtrów)

### 🎯 ROOT CAUSE: Query zbyt wąsko filtruje po `job_type`

**Przeczytane pliki:**

#### 1. `SyncController.php` (linie 297-304)

```php
protected function getRecentSyncJobs()
{
    return SyncJob::with(['prestashopShop', 'user'])
                 ->where('job_type', SyncJob::JOB_PRODUCT_SYNC)  // ← PROBLEM!
                 ->latest()
                 ->take(10)
                 ->get();
}
```

**PROBLEM:** Query filtruje TYLKO po `job_type = 'product_sync'`, ale:
- Przycisk "← Import" tworzy SyncJob z `job_type = 'import_products'` (linia 70 w PullProductsFromPrestaShop.php)
- Przycisk "Synchronizuj →" tworzy SyncJob z `job_type = 'product_sync'` (linia 831 w SyncController.php)

#### 2. `PullProductsFromPrestaShop.php` (linie 66-85)

```php
public function __construct(
    public PrestaShopShop $shop
) {
    // Create SyncJob for tracking (FIX #1 - BUG #7)
    $this->syncJob = SyncJob::create([
        'job_id' => \Str::uuid(),
        'job_type' => 'import_products',  // ← Inny typ!
        'job_name' => "Import Products from {$shop->name}",
        'source_type' => SyncJob::TYPE_PRESTASHOP,
        'source_id' => $shop->id,
        'target_type' => SyncJob::TYPE_PPM,
        // ...
    ]);
}
```

**DIAGNOSTIC OUTPUT:**
```
🔍 Simulating getRecentSyncJobs() query:
   Query returned: 10 jobs

   Results:
   • ID: 84 | Type: product_sync | Created: 2025-11-07 13:45:06 | Status: failed
   • ID: 83 | Type: product_sync | Created: 2025-11-07 13:39:13 | Status: failed
   ...
   (wszystkie są z 2025-11-07)

🔍 Checking non-product_sync jobs:
   Found: 1 jobs with other types
   • ID: 85 | Type: import_products | Created: 2025-11-12 08:14:55  ← DZISIAJ!
```

**POTWIERDZENIE:**
- Query `getRecentSyncJobs()` zwraca TYLKO `product_sync` (ostatni: 2025-11-07)
- Job `import_products` z DZISIAJ (ID: 85) jest **IGNOROWANY** przez query
- User kliknął "← Import" dzisiaj, ale nie widzi go w UI!

---

## 📊 DIAGNOSTIC SCRIPT OUTPUT

**File**: `_TEMP/diagnose_bug9_sync_jobs_display.php`

### Kluczowe Findings:

1. **Total SyncJobs**: 46 (database NIE jest pusty)
2. **Recent 7 days**: 46 jobs (aktywność jest!)
3. **Today**: 1 job (ID: 85, type: `import_products`, created: 08:14:55)
4. **Status Distribution**: 19 completed, 23 failed, 4 cancelled
5. **Job Type Distribution**: 1 `import_products`, 45 `product_sync`

**ROOT CAUSE CONFIRMED:**
- Query `getRecentSyncJobs()` zwraca 10 jobs z `job_type = 'product_sync'`
- Najnowszy `product_sync` job: 2025-11-07 13:45:06
- Najnowszy `import_products` job: 2025-11-12 08:14:55 ← **IGNOROWANY!**

---

## 🛠️ PROPOSED SOLUTIONS

### FIX #1: Remove `job_type` filter from `getRecentSyncJobs()` (CRITICAL, HIGH PRIORITY)

**Pliki do modyfikacji**: `app/Http/Livewire/Admin/Shops/SyncController.php`

**BEFORE** (linie 297-304):
```php
protected function getRecentSyncJobs()
{
    return SyncJob::with(['prestashopShop', 'user'])
                 ->where('job_type', SyncJob::JOB_PRODUCT_SYNC)  // ← REMOVE THIS!
                 ->latest()
                 ->take(10)
                 ->get();
}
```

**AFTER**:
```php
protected function getRecentSyncJobs()
{
    return SyncJob::with(['prestashopShop', 'user'])
                 // NO job_type filter - show ALL sync jobs (import + export)
                 ->latest()
                 ->take(10)
                 ->get();
}
```

**Uzasadnienie:**
- User chce widzieć WSZYSTKIE operacje synchronizacji (import + export)
- Query nie powinien filtrować po job_type
- UI może pokazać type jako badge (np. "Import" vs "Sync")

**Estymacja**: 15 minut (zmiana 1 linii + test)

**Priority**: CRITICAL (user nie widzi dzisiejszych operacji!)

---

### FIX #2: Add `wire:poll` to Recent Sync Jobs section (HIGH PRIORITY)

**Pliki do modyfikacji**: `resources/views/livewire/admin/shops/sync-controller.blade.php`

**BEFORE** (linia 1063):
```blade
<div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border"
     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
```

**AFTER**:
```blade
<div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border"
     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);"
     wire:poll.5s>
```

**Uzasadnienie:**
- Sekcja "Queue Infrastructure" (linia 1385) ma `wire:poll.5s` (auto-refresh)
- Sekcja "Recent Sync Jobs" (linia 1063) NIE MA polling
- User musi ręcznie refresh page aby zobaczyć nowe jobs

**Estymacja**: 5 minut (dodanie 1 atrybutu)

**Priority**: HIGH (auto-refresh poprawia UX)

---

### FIX #3: Add `job_type` badge in Recent Sync Jobs UI (MEDIUM PRIORITY)

**Pliki do modyfikacji**: `resources/views/livewire/admin/shops/sync-controller.blade.php`

**BEFORE** (linia 1100):
```blade
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
    @if($job->status === 'completed') bg-green-900 bg-opacity-40 text-green-300
    @elseif($job->status === 'failed') bg-red-900 bg-opacity-40 text-red-300
    @elseif($job->status === 'running') bg-yellow-900 bg-opacity-40 text-yellow-300
    @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
    {{ ucfirst($job->status) }}
</span>
```

**AFTER**:
```blade
{{-- Job Type Badge --}}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
    @if($job->job_type === 'import_products') bg-blue-900 bg-opacity-40 text-blue-300
    @elseif($job->job_type === 'product_sync') bg-purple-900 bg-opacity-40 text-purple-300
    @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
    @if($job->job_type === 'import_products')
        ← Import
    @elseif($job->job_type === 'product_sync')
        Sync →
    @else
        {{ ucfirst(str_replace('_', ' ', $job->job_type)) }}
    @endif
</span>

{{-- Status Badge --}}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
    @if($job->status === 'completed') bg-green-900 bg-opacity-40 text-green-300
    @elseif($job->status === 'failed') bg-red-900 bg-opacity-40 text-red-300
    @elseif($job->status === 'running') bg-yellow-900 bg-opacity-40 text-yellow-300
    @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
    {{ ucfirst($job->status) }}
</span>
```

**Uzasadnienie:**
- User będzie widzieć różnicę między "Import" (PrestaShop → PPM) i "Sync" (PPM → PrestaShop)
- Kolor badge: niebieski dla import, fioletowy dla sync
- Ikony: ← dla import, → dla sync

**Estymacja**: 30 minut (HTML + styling)

**Priority**: MEDIUM (poprawia czytelność)

---

### FIX #4: Add "Wyczyść Logi" button (MEDIUM PRIORITY)

**Pliki do modyfikacji**:
- `app/Http/Livewire/Admin/Shops/SyncController.php` (new method)
- `resources/views/livewire/admin/shops/sync-controller.blade.php` (new button)

**NEW METHOD**:
```php
/**
 * Clear old sync jobs (hard delete)
 *
 * Deletes sync jobs older than X days (configurable)
 * WARNING: This is permanent deletion!
 */
public function clearOldSyncJobs(int $olderThanDays = 30): void
{
    try {
        $deleted = SyncJob::where('created_at', '<', now()->subDays($olderThanDays))
                         ->delete();

        session()->flash('success', "Usunięto {$deleted} starych zadań synchronizacji (starszych niż {$olderThanDays} dni).");

        Log::info('Old sync jobs cleared', [
            'deleted_count' => $deleted,
            'older_than_days' => $olderThanDays,
            'user_id' => auth()->id(),
        ]);

    } catch (\Exception $e) {
        session()->flash('error', 'Błąd podczas czyszczenia logów: ' . $e->getMessage());

        Log::error('Failed to clear old sync jobs', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id(),
        ]);
    }
}
```

**NEW BUTTON** (po linii 1072):
```blade
<div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold text-white flex items-center">
        <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Ostatnie zadania synchronizacji
    </h3>

    <button wire:click="clearOldSyncJobs(30)"
            wire:confirm="Czy na pewno chcesz usunąć zadania starsze niż 30 dni?"
            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        Wyczyść Logi (>30 dni)
    </button>
</div>
```

**Estymacja**: 1 godzina (backend method + UI button + validation + tests)

**Priority**: MEDIUM (nice-to-have, nie blokuje BUG #9.1)

**Dependencies**: NIE (można implementować niezależnie)

---

### FIX #5: Add "Archiwizuj" button (LOW PRIORITY)

**Pliki do modyfikacji**:
- `database/migrations/2025_11_12_create_archived_sync_jobs_table.php` (new migration)
- `app/Models/ArchivedSyncJob.php` (new model)
- `app/Http/Livewire/Admin/Shops/SyncController.php` (new method)
- `resources/views/livewire/admin/shops/sync-controller.blade.php` (new button)

**DESCRIPTION:**
- Create `archived_sync_jobs` table (identical structure to `sync_jobs`)
- Move old sync jobs (>90 days) to archive table
- Keep sync_jobs table small for performance
- Allow viewing archive in separate UI

**Estymacja**: 3 godziny (migration + model + service + UI + tests)

**Priority**: LOW (future enhancement, nie urgent)

**Dependencies**: NIE

---

### FIX #6: Add config for retention policy (LOW PRIORITY)

**Pliki do modyfikacji**:
- `config/sync.php` (new config file)

**NEW CONFIG**:
```php
<?php

return [
    'sync_jobs_retention' => [
        'auto_delete_enabled' => false,
        'delete_after_days' => 90,
        'archive_before_delete' => true,
        'archive_after_days' => 30,
    ],
];
```

**Estymacja**: 30 minut (config file + documentation)

**Priority**: LOW (foundation dla future CRON automation)

**Dependencies**: Potrzebuje FIX #4 lub FIX #5

---

## 📋 IMPLEMENTATION ROADMAP

### Phase 1: Critical Fixes (IMMEDIATE)
1. **FIX #1**: Remove job_type filter (15 min) ← **MUST DO FIRST!**
2. **FIX #2**: Add wire:poll (5 min)

**Total**: 20 minut

**Result**: User będzie widział WSZYSTKIE sync jobs (import + export) z auto-refresh

---

### Phase 2: UI Improvements (OPTIONAL, later)
3. **FIX #3**: Add job_type badge (30 min)

**Total**: 30 minut

**Result**: User będzie rozróżniał import vs sync wizualnie

---

### Phase 3: Maintenance Features (FUTURE)
4. **FIX #4**: Add "Wyczyść Logi" button (1 godz)
5. **FIX #5**: Add "Archiwizuj" button (3 godz)
6. **FIX #6**: Add config for retention (30 min)

**Total**: 4.5 godziny

**Result**: Enterprise-grade log management z auto-cleanup

---

## 🧪 TESTING STRATEGY

### Test Case #1: Verify FIX #1 (Remove job_type filter)

**Preconditions:**
- Database ma mix of `product_sync` i `import_products` jobs
- Ostatni `product_sync`: 2025-11-07
- Ostatni `import_products`: 2025-11-12

**Steps:**
1. Deploy FIX #1 (remove `->where('job_type', SyncJob::JOB_PRODUCT_SYNC)`)
2. Clear cache: `php artisan view:clear && php artisan cache:clear`
3. Open https://ppm.mpptrade.pl/admin/shops/sync
4. Scroll to "Ostatnie zadania synchronizacji"

**Expected Result:**
- Lista pokazuje 10 najnowszych jobs (ANY type)
- Pierwszy job: ID 85, type: `import_products`, created: 2025-11-12 08:14:55
- Mieszanka `import_products` i `product_sync` jobs

**Actual Result (before FIX):**
- Lista pokazuje tylko `product_sync` jobs
- Pierwszy job: 2025-11-07 (4 dni temu!)

---

### Test Case #2: Verify FIX #2 (wire:poll auto-refresh)

**Preconditions:**
- FIX #1 deployed
- User ma otwartą stronę https://ppm.mpptrade.pl/admin/shops/sync

**Steps:**
1. Open page (Chrome DevTools Network tab)
2. Kliknij "← Import" dla sklepu (triggeruje PullProductsFromPrestaShop)
3. Wait 5 seconds (wire:poll interval)
4. Check if sekcja "Recent Sync Jobs" refreshed automatically

**Expected Result:**
- Po 5 sekundach: automatic Livewire request (XHR)
- Nowy job pojawia się bez ręcznego refresh page

---

### Test Case #3: Verify FIX #4 (Wyczyść Logi button)

**Preconditions:**
- Database ma jobs starsze niż 30 dni

**Steps:**
1. Count jobs before: `SELECT COUNT(*) FROM sync_jobs WHERE created_at < NOW() - INTERVAL 30 DAY`
2. Kliknij "Wyczyść Logi (>30 dni)" button
3. Potwierdź confirm dialog
4. Count jobs after: `SELECT COUNT(*) FROM sync_jobs WHERE created_at < NOW() - INTERVAL 30 DAY`

**Expected Result:**
- Before: N jobs (N > 0)
- After: 0 jobs
- Flash message: "Usunięto N starych zadań synchronizacji"

---

## 📝 DEPENDENCIES

### FIX #1 (Remove job_type filter)
- **Dependencies**: BRAK
- **Blocked by**: BRAK
- **Can implement**: NATYCHMIAST ✅

### FIX #2 (Add wire:poll)
- **Dependencies**: BRAK (niezależny od FIX #1, ale synergiczne)
- **Blocked by**: BRAK
- **Can implement**: NATYCHMIAST ✅

### FIX #3 (Add job_type badge)
- **Dependencies**: FIX #1 (bez tego badge nie ma sensu - user nie widzi import jobs)
- **Blocked by**: NIE (może implementować równolegle)
- **Can implement**: PO FIX #1 deployment

### FIX #4 (Wyczyść Logi button)
- **Dependencies**: BRAK
- **Blocked by**: BRAK
- **Can implement**: PARALLEL z FIX #1-3

### FIX #5 (Archiwizuj button)
- **Dependencies**: Migration (archived_sync_jobs table)
- **Blocked by**: BRAK
- **Can implement**: FUTURE (low priority)

### FIX #6 (Config retention policy)
- **Dependencies**: FIX #4 lub FIX #5 (używa config values)
- **Blocked by**: FIX #4 implementation
- **Can implement**: PO FIX #4

---

## 🎯 RECOMMENDED IMPLEMENTATION ORDER

### IMMEDIATE (Deploy today):
1. **FIX #1** (15 min) - CRITICAL
2. **FIX #2** (5 min) - HIGH

**Total**: 20 minut → **DEPLOY NATYCHMIAST!**

### OPTIONAL (Next sprint):
3. **FIX #3** (30 min) - MEDIUM

### FUTURE (Backlog):
4. **FIX #4** (1h) - MEDIUM
5. **FIX #6** (30 min) - LOW (after FIX #4)
6. **FIX #5** (3h) - LOW

---

## 📁 PLIKI

**Przeczytane:**
- `app/Http/Livewire/Admin/Shops/SyncController.php` (lines 1-1250)
- `app/Models/SyncJob.php` (lines 1-662)
- `app/Jobs/PullProductsFromPrestaShop.php` (lines 1-372)
- `resources/views/livewire/admin/shops/sync-controller.blade.php` (lines 1-1479)

**Do modyfikacji (FIX #1-2):**
- `app/Http/Livewire/Admin/Shops/SyncController.php` (linia 300 - remove filter)
- `resources/views/livewire/admin/shops/sync-controller.blade.php` (linia 1063 - add wire:poll)

**Do stworzenia (FIX #4-6):**
- `config/sync.php` (new config)
- `database/migrations/2025_11_12_create_archived_sync_jobs_table.php` (new migration)
- `app/Models/ArchivedSyncJob.php` (new model)

---

## ⏱️ ESTYMACJA CZASU

### CRITICAL Fixes (Phase 1):
- FIX #1: 15 minut (remove 1 line + test)
- FIX #2: 5 minut (add 1 attribute)
- **TOTAL**: 20 minut ✅

### OPTIONAL Improvements (Phase 2):
- FIX #3: 30 minut (HTML + styling)
- **TOTAL**: 30 minut

### FUTURE Features (Phase 3):
- FIX #4: 1 godzina (backend + UI + tests)
- FIX #5: 3 godziny (migration + model + service + UI)
- FIX #6: 30 minut (config file)
- **TOTAL**: 4.5 godziny

**GRAND TOTAL**: 5 godzin 20 minut (wszystkie fixes)

**RECOMMENDED NOW**: 20 minut (FIX #1 + FIX #2) → Rozwiązuje BUG #9.1

---

## 🚨 PRIORITY MATRIX

| Fix | Priority | Effort | Impact | Implement |
|-----|----------|--------|--------|-----------|
| FIX #1 | CRITICAL | 15 min | HIGH | NOW ✅ |
| FIX #2 | HIGH | 5 min | MEDIUM | NOW ✅ |
| FIX #3 | MEDIUM | 30 min | LOW | LATER |
| FIX #4 | MEDIUM | 1h | MEDIUM | BACKLOG |
| FIX #5 | LOW | 3h | LOW | FUTURE |
| FIX #6 | LOW | 30 min | LOW | FUTURE |

---

## 📋 NASTĘPNE KROKI

### IMMEDIATE ACTION REQUIRED:

**User powinien:**
1. ✅ Zaakceptować diagnozę ROOT CAUSE
2. ✅ Zdecydować czy implementować FIX #1 + FIX #2 NATYCHMIAST (20 min)
3. ⏰ Zdecydować czy chce FIX #3-6 (backlog)

**Po zatwierdzeniu:**
1. Implementacja FIX #1 (remove job_type filter)
2. Implementacja FIX #2 (add wire:poll)
3. Deploy na produkcję
4. Test Case #1 + #2 verification
5. User confirmation

---

## ✅ PODSUMOWANIE

**ROOT CAUSE FOUND:**
Query `getRecentSyncJobs()` filtruje TYLKO `job_type = 'product_sync'`, ignorując `import_products` jobs utworzone przez przycisk "← Import".

**SOLUTION:**
Remove `->where('job_type', SyncJob::JOB_PRODUCT_SYNC)` z query (1 linia kodu).

**IMPACT:**
User będzie widział WSZYSTKIE sync jobs (import + export) w sekcji "Recent Sync Jobs".

**EFFORT:**
20 minut (FIX #1 + FIX #2).

**STATUS:**
✅ DIAGNOSIS COMPLETE - WAITING FOR USER APPROVAL TO IMPLEMENT.

---

**Diagnostic Script**: `_TEMP/diagnose_bug9_sync_jobs_display.php`
**Deployed to Production**: ✅ (2025-11-12 09:15)
**Diagnostic Output**: Saved above ⬆️
