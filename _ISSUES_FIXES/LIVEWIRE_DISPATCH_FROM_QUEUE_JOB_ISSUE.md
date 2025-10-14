# ISSUE: Livewire::dispatch() Call from Queue Job Context

**Data znalezienia**: 2025-10-09
**Severity**: 🔥 **CRITICAL** - Job crashes na produkcji
**Status**: ✅ **RESOLVED**
**Podobne issues**: `LIVEWIRE_EMIT_DISPATCH_ISSUE.md` (Livewire 3.x migration)

---

## 🚨 PROBLEM

### Objawy

Queue job `AnalyzeMissingCategories` kończy się **FAIL** z błędem:

```
[2025-10-08 15:41:11] production.ERROR:
Call to undefined method Livewire\LivewireManager::dispatch()
at /app/Jobs/PrestaShop/AnalyzeMissingCategories.php:214
```

### Stack Trace

```php
#0 AnalyzeMissingCategories.php(214): Facade::__callStatic()
#1 Container/BoundMethod.php(36): AnalyzeMissingCategories->handle()
#2 Queue/Worker.php(442): Job->fire()
```

### Kod powodujący błąd

```php
// ❌ INCORRECT - Line 214 w AnalyzeMissingCategories.php
\Livewire\Livewire::dispatch('show-category-preview', [
    'previewId' => $preview->id,
]);
```

---

## ✅ ROOT CAUSE

### 1. Livewire Events NIE działają z Queue Job Context

**DLACZEGO:**
- Livewire events (`dispatch()`, `emit()`) wymagają **HTTP request context**
- Queue jobs działają w **CLI/background context** bez session/request
- `Livewire::dispatch()` próbuje znaleźć active Livewire component (którego NIE MA w queue job)

**DOKUMENTACJA:**
- Livewire Documentation: "Events work within HTTP request lifecycle"
- Laravel Queue Documentation: "Jobs run in isolated process without HTTP context"

### 2. Legacy Code z poprzedniej implementacji

Kod został napisany przed odkryciem że Livewire events nie działają z queue jobs.
Polling mechanism (`wire:poll.3s` w ProductList) już **zastąpił** ten kod, ale stary `dispatch()` call pozostał.

**CHRONOLOGIA:**
1. **ETAP_07 FAZA 3D** - Initial implementation z `Livewire::dispatch()`
2. **2025-10-08** - Debugging: Odkryto że events nie działają z queue jobs
3. **2025-10-08** - Implementacja polling mechanism jako workaround
4. **2025-10-09** - Cleanup: Usunięcie legacy `dispatch()` call

---

## 🛡️ ROZWIĄZANIE

### FIX #1: Usuń Livewire::dispatch() z Queue Job

**PRZED:**
```php
// ❌ NIE DZIAŁA - Livewire events z queue job
\Livewire\Livewire::dispatch('show-category-preview', [
    'previewId' => $preview->id,
]);
```

**PO:**
```php
// ✅ CORRECT - Polling mechanism zastępuje events
// NOTE: Livewire events DO NOT WORK from queue jobs!
// CategoryPreview is detected via polling mechanism in ProductList component (wire:poll.3s)
// See: ProductList::checkForPendingCategoryPreviews()
```

**LOKALIZACJA FIX:**
- `app/Jobs/PrestaShop/AnalyzeMissingCategories.php:212-216` - Usunięto problematyczny kod

### FIX #2: Polling Mechanism (już zaimplementowany)

**GDZIE:**
- `app/Http/Livewire/Products/Listing/ProductList.php:2195-2226`
- Method: `checkForPendingCategoryPreviews()`

**JAK DZIAŁA:**
```php
// In Blade template - poll every 3 seconds
<div wire:poll.3s="checkForPendingCategoryPreviews">

// In ProductList component
public function checkForPendingCategoryPreviews(): void
{
    $preview = \App\Models\CategoryPreview::where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->first();

    if ($preview) {
        // Show modal
        $this->dispatch('openCategoryPreviewModal', [
            'previewId' => $preview->id,
        ]);
    }
}
```

**FLOW:**
1. Job creates `CategoryPreview` record with `status='pending'`
2. ProductList polls every 3 seconds: `wire:poll.3s`
3. `checkForPendingCategoryPreviews()` finds pending preview
4. Dispatches event **W LIVEWIRE CONTEXT** (not queue job!)
5. Modal opens

---

## 📋 ZASADY ZAPOBIEGANIA

### ❌ NIGDY NIE UŻYWAJ w Queue Jobs:

```php
// ❌ ZABRONIONE w kontekście Queue Job
\Livewire\Livewire::dispatch('event-name', $data);
$this->emit('event-name'); // Livewire 2.x
$this->dispatch('event-name'); // Livewire 3.x
```

### ✅ ZAMIAST TEGO:

**OPCJA 1: Database Polling** (preferowane dla batch jobs)
```php
// Queue Job - zapisz status do DB
CategoryPreview::create([
    'status' => 'pending',
    'data' => $categoryData,
]);

// Livewire Component - poll database
wire:poll.3s="checkForPendingPreviews"
```

**OPCJA 2: Laravel Events + Broadcasting** (dla real-time)
```php
// Queue Job - Laravel event (NOT Livewire event!)
event(new CategoryPreviewReady($preview->id));

// Frontend - Laravel Echo listener
Echo.private('channel').listen('CategoryPreviewReady', (e) => {
    Livewire.dispatch('openModal', e);
});
```

**OPCJA 3: Dispatch to Specific Component** (jeśli znasz component ID)
```php
// NIE DZIAŁA z queue jobs!
// Component ID nie istnieje poza HTTP request
```

---

## 🔧 DEPLOYMENT CHECKLIST

Po wprowadzeniu fix:

- [x] Edit `AnalyzeMissingCategories.php` - usunięto `Livewire::dispatch()` call
- [x] Upload file na produkcję przez `pscp`
- [x] Restart queue worker: `php artisan queue:restart`
- [x] Start new worker: `nohup php artisan queue:work ... &`
- [x] Verify worker running: `ps aux | grep queue:work`
- [x] Monitor logs: `tail -f storage/logs/queue-worker.log`
- [x] Test workflow: Import → CategoryPreview modal pojawia się po 3-6s

---

## 🎯 VERIFICATION

### Test Case

1. **Uruchom import produktów** z PrestaShop
2. **Sprawdź logi** - nie powinno być błędu `Call to undefined method`
3. **Sprawdź queue worker log** - job powinien kończyć się `DONE` zamiast `FAIL`
4. **Sprawdź UI** - modal powinien się pojawić po ~3-6 sekund (polling delay)

### Expected Logs (PO FIX)

```log
# ✅ CORRECT - Job succeeds
[2025-10-09 09:29:15] App\Jobs\PrestaShop\AnalyzeMissingCategories ... RUNNING
[2025-10-09 09:29:15] App\Jobs\PrestaShop\AnalyzeMissingCategories  245.12ms DONE

# ✅ CategoryPreview detected via polling
[2025-10-09 09:29:18] ProductList: Pending CategoryPreview detected via polling
[2025-10-09 09:29:18] CategoryPreviewModal: Opened successfully
```

### Before Fix Logs (PRZED FIX)

```log
# ❌ INCORRECT - Job fails
[2025-10-08 15:41:11] App\Jobs\PrestaShop\AnalyzeMissingCategories ... RUNNING
[2025-10-08 15:41:11] App\Jobs\PrestaShop\AnalyzeMissingCategories  238.94ms FAIL
[2025-10-08 15:41:11] production.ERROR: Call to undefined method Livewire\LivewireManager::dispatch()
```

---

## 📚 POWIĄZANE PLIKI

### Modified Files
- `app/Jobs/PrestaShop/AnalyzeMissingCategories.php:212-216` - Usunięto Livewire::dispatch()

### Polling Mechanism Files (już istniejące)
- `app/Http/Livewire/Products/Listing/ProductList.php:2195-2226` - checkForPendingCategoryPreviews()
- `resources/views/livewire/products/listing/product-list.blade.php:1702` - wire:poll.3s directive
- `app/Models/CategoryPreview.php` - Database model for polling

### Related Documentation
- `_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md` - Livewire 3.x event migration
- `_REPORTS/Podsumowanie_dnia_2025-10-08_1744.md` - Original discovery of polling workaround

---

## 💡 KEY TAKEAWAYS

1. **Livewire events ≠ Laravel events**
   - Livewire events: HTTP request context ONLY
   - Laravel events: Work everywhere (including queue jobs)

2. **Queue jobs są izolowane**
   - No session, no request, no Livewire components
   - Use database polling lub Laravel broadcasting

3. **Polling mechanism works!**
   - 3-second delay acceptable for UX (dodano loading animation)
   - Reliable, no WebSocket dependency
   - Fallback when Laravel Echo not configured

4. **Cleanup legacy code**
   - Po znalezieniu workaround, usuń stary kod
   - Dodaj komentarze wyjaśniające DLACZEGO

---

**Author**: Claude Code
**Fixed**: 2025-10-09
**Verified**: ✅ Production deployment successful
**Queue Worker**: ✅ Running without errors since 09:29
