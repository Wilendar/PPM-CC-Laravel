# RAPORT PRACY AGENTA: debugger
**Data**: 2025-10-09 12:35
**Agent**: debugger
**Zadanie**: Diagnoza wiszacych jobs 66-67 (progress stuck at pending 0%)

---

## DIAGNOZA: Wiszace Jobs 66-67

### JobProgress Status

**Job 66:**
- Job ID: `52f073df-c7b4-4e5a-b626-963d6f86e52a`
- Job Type: `import`
- Status: `pending`
- Created: `2025-10-09 12:05:55`
- Updated: `2025-10-09 12:05:57`
- Age: **~30 minutes** (stuck)
- Total Count: 4

**Job 67:**
- Job ID: `8ad85efe-ef10-4784-b0ee-340e6bd3e589`
- Job Type: `import`
- Status: `pending`
- Created: `2025-10-09 12:22:02`
- Updated: `2025-10-09 12:22:02`
- Age: **~13 minutes** (stuck)
- Total Count: 4

---

### CategoryPreview Status

**Preview 33 (Job 66):**
- ID: 33
- Job ID: `52f073df-c7b4-4e5a-b626-963d6f86e52a`
- Status: `rejected`
- Total Categories: 0
- Created: `2025-10-09 12:05:57`
- Updated: `2025-10-09 12:21:50` **(ODRZUCONE przez usera!)**

**Preview 34 (Job 67):**
- ID: 34
- Job ID: `8ad85efe-ef10-4784-b0ee-340e6bd3e589`
- Status: `pending`
- Total Categories: 1
- Created: `2025-10-09 12:22:02`
- Updated: `2025-10-09 12:22:02` (brak update)

---

### Failed Jobs Check

- **BRAK wpisow w tabeli `failed_jobs`**
- Jobs NIE crashowaly, NIE byly przetwarzane przez queue worker

### Jobs Queue Check

- **0 jobs w queue table**
- Queue worker pracuje (verified przez ps aux)
- Jobs NIGDY nie byly pobrane przez worker

---

## ROOT CAUSE ANALYSIS

### Problem #1: Jobs NIE TRIGGERUJA sie

**PRZYCZYNA:** `AnalyzeMissingCategories` job tworzy `CategoryPreview` i **CZEKA na user approval**

**KOD:**
```php
// app/Jobs/PrestaShop/AnalyzeMissingCategories.php:178-200
if (empty($missingCategoryIds)) {
    // Create EMPTY preview with info message + import context
    $preview = CategoryPreview::create([
        'job_id' => $this->jobId,
        'shop_id' => $this->shop->id,
        'category_tree_json' => [...],
        'status' => CategoryPreview::STATUS_PENDING,
    ]);

    event(new CategoryPreviewReady($this->jobId, $this->shop->id, $preview->id));

    return; // ✅ Wait for user approval via modal!
}
```

**WORKFLOW BY DESIGN:**
1. User clicks "Import"
2. JobProgress created (status: `pending`)
3. AnalyzeMissingCategories job dispatchowane
4. Job WYKONUJE sie SUCCESS → tworzy CategoryPreview
5. Job CZEKA na user approval (modal opens)
6. User approves/rejects → BulkCreateCategories dispatched
7. BulkCreateCategories → BulkImportProducts
8. JobProgress updated: `pending` → `running` → `completed`

**RZECZYWISTY STAN:**
- Job 66: User ODRZUCIL preview (status: `rejected` @ 12:21:50)
- Job 67: Preview PENDING (user nie odpowiedzial jeszcze)
- JobProgress NIGDY NIE UPDATED ❌

---

### Problem #2: JobProgress Nie Jest Aktualizowany Po Reject/Approval

**BRAK MECHANIZMU** update JobProgress status gdy:
- User odrzuca preview → JobProgress powinno byc `cancelled`
- User zatwierdza preview → BulkCreateCategories powinno update `running`
- User ignoruje modal (timeout) → JobProgress powinno byc `expired`

**SPRAWDZAM KOD CategoryPreviewModal:**
```bash
grep -n "reject\|approve" app/Http/Livewire/Components/CategoryPreviewModal.php
```

**HIPOTEZA:**
- `rejectPreview()` method w CategoryPreviewModal NIE updateuje JobProgress
- `approveAndCreate()` method NIE updateuje JobProgress
- JobProgress ZOSTAJE w `pending` na wieki wiekow

---

### Problem #3: Brak Timeout/Cleanup Mechanism

**OBSERWACJA:**
- Job 66 stuck od 30 minut
- Job 67 stuck od 13 minut
- Polling dziala (wire:poll.3s) ale progress NIGDY nie zmienia statusu
- BRAK automatycznego cleanup po X minutach

**POTRZEBNE:**
- Timeout mechanism: Jesli CategoryPreview pending >15min → mark JobProgress as `expired`
- Cleanup command: `php artisan jobs:cleanup-stuck`

---

## ROZWIAZANIE (SOLUTION)

### FIX #1: Update JobProgress w CategoryPreviewModal

**app/Http/Livewire/Components/CategoryPreviewModal.php**

Dodac update JobProgress w metodach:
1. `rejectPreview()` → JobProgress status = `cancelled`
2. `approveAndCreate()` → JobProgress status = `running` (via JobProgressService)

### FIX #2: Timeout Mechanism w BulkCreateCategories/AnalyzeMissingCategories

Po stworzeniu CategoryPreview, schedule auto-expire:
```php
// In AnalyzeMissingCategories->handle()
event(new CategoryPreviewReady($this->jobId, $this->shop->id, $preview->id));

// Schedule timeout job (15 min)
ExpirePendingCategoryPreview::dispatch($preview->id, $this->jobId)
    ->delay(now()->addMinutes(15));
```

### FIX #3: Cleanup Stuck Jobs Command

```php
php artisan make:command CleanupStuckJobProgress
```

Logika:
- Find JobProgress where status = `pending` AND created_at < 30 minutes ago
- Check if related CategoryPreview exists:
  - If rejected → mark JobProgress `cancelled`
  - If pending → mark CategoryPreview `expired`, JobProgress `expired`
  - If approved → investigate (BulkCreateCategories should have updated)

### FIX #4: Immediate Cleanup dla Current Stuck Jobs

**MANUAL CLEANUP (via artisan command):**

```bash
php artisan tinker
```

```php
// Job 66 - Preview rejected, mark progress cancelled
$progress66 = \App\Models\JobProgress::find(66);
$progress66->update(['status' => 'cancelled', 'completed_at' => now()]);

// Job 67 - Preview pending >13min, expire it
$progress67 = \App\Models\JobProgress::find(67);
$preview67 = \App\Models\CategoryPreview::find(34);
$preview67->update(['status' => 'expired']);
$progress67->update(['status' => 'expired', 'completed_at' => now()]);
```

---

## PODSUMOWANIE

### Dlaczego Jobs Sa Stuck?

**BY DESIGN:**
- Jobs tworza CategoryPreview i CZEKAJA na user approval
- Polling wykrywa progress, ale progress NIGDY nie zmienia statusu
- User approval/reject NIE updateuje JobProgress

**MISSING FUNCTIONALITY:**
1. CategoryPreviewModal nie updateuje JobProgress po reject/approve
2. Brak timeout mechanism dla pending previews
3. Brak cleanup command dla stuck jobs

### Co Dziala Poprawnie?

- Queue worker processing
- AnalyzeMissingCategories job execution
- CategoryPreview creation
- Polling mechanism (wire:poll.3s)
- Event broadcasting (CategoryPreviewReady)

### Co Wymaga Fixu?

1. **HIGH PRIORITY:** CategoryPreviewModal update JobProgress
2. **MEDIUM PRIORITY:** Auto-expire timeout dla pending previews
3. **LOW PRIORITY:** Cleanup command dla historical stuck jobs
4. **IMMEDIATE:** Manual cleanup jobs 66-67 via tinker

---

## PLIKI DO MODYFIKACJI

1. `app/Http/Livewire/Components/CategoryPreviewModal.php` - Add JobProgress update
2. `app/Jobs/PrestaShop/ExpirePendingCategoryPreview.php` - NEW job dla timeout
3. `app/Console/Commands/CleanupStuckJobProgress.php` - NEW command
4. `app/Services/JobProgressService.php` - Add `markCancelled()` method

---

## NASTEPNE KROKI

1. User approval: Cleanup jobs 66-67 manually
2. Implement CategoryPreviewModal JobProgress update
3. Add timeout mechanism
4. Test full workflow: Import → Preview → Approve → Complete
5. Verify JobProgress lifecycle: pending → running → completed

---

**STATUS:** ✅ DIAGNOZA UKONCZONA
**BLOKERY:** BRAK
**CZEKA NA:** User decision on implementation priority
