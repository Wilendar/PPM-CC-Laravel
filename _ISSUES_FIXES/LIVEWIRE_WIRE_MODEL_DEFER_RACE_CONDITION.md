# Livewire wire:model.defer + wire:click Race Condition Issue

**Discovered:** 2025-11-13
**Component:** SyncController (Sync Configuration Form)
**Severity:** HIGH (Data loss - user changes not saved)
**Status:** FIXED

---

## Problem

User changes to form fields with `wire:model.defer` are NOT saved when using `wire:click` button (instead of `wire:submit`).

**Symptoms:**
- User changes select value: "hourly" → "daily"
- Clicks "Save" button
- Value is saved as "hourly" (old value, NOT the changed value)
- After page refresh, form shows "hourly" again

---

## Root Cause

**RACE CONDITION** between Livewire modifiers:

### Livewire 3.x Behavior:

1. **`wire:model.defer`** - Deferred synchronization
   - Value is synchronized ONLY on:
     - Form submit event
     - Input blur
     - Next Livewire request
   - NOT synchronized on input change

2. **`wire:click`** - Immediate method invocation
   - Calls PHP method **IMMEDIATELY**
   - Does NOT trigger defer synchronization first

### Event Sequence (BUG):

```
1. User changes <select wire:model.defer="autoSyncFrequency">
   Browser: "hourly" → "daily" (local state only)
   Livewire: $autoSyncFrequency = "hourly" (NOT synchronized yet!)

2. User clicks <button wire:click="saveSyncConfiguration">

3. Livewire calls saveSyncConfiguration() IMMEDIATELY
   PHP reads: $this->autoSyncFrequency = "hourly" (old value!)

4. Method saves "hourly" to database (OVERWRITES user change)

5. AFTER saveSyncConfiguration() returns, Livewire synchronizes defer
   Livewire: $autoSyncFrequency = "daily" (TOO LATE!)

6. Result: Database has "hourly", user expected "daily"
```

---

## Example Code (BUG)

```blade
<!-- PROBLEMATIC CODE -->
<select wire:model.defer="autoSyncFrequency">
    <option value="hourly">Co godzinę</option>
    <option value="daily">Codziennie</option>
    <option value="weekly">Tygodniowo</option>
</select>

<button wire:click="saveSyncConfiguration">
    Zapisz konfigurację
</button>
```

```php
// SyncController.php
public $autoSyncFrequency = 'hourly'; // Default

public function saveSyncConfiguration()
{
    // BUG: Reads OLD value if user changed select with defer!
    $frequency = $this->autoSyncFrequency; // "hourly" (NOT "daily"!)

    SystemSetting::updateOrCreate(
        ['key' => 'sync.schedule.frequency'],
        ['value' => $frequency] // Saves "hourly" instead of "daily"
    );
}
```

---

## Fix Option 1: Use wire:model.live (RECOMMENDED)

**CHANGE:** `wire:model.defer` → `wire:model.live`

```blade
<!-- FIXED CODE -->
<select wire:model.live="autoSyncFrequency">
    <option value="hourly">Co godzinę</option>
    <option value="daily">Codziennie</option>
    <option value="weekly">Tygodniowo</option>
</select>

<button wire:click="saveSyncConfiguration">
    Zapisz konfigurację
</button>
```

**Behavior:**
- `wire:model.live` synchronizes IMMEDIATELY on change
- No race condition (value is always current)
- More requests to server (acceptable for config forms)

**When to use:**
- Forms with `wire:click` buttons
- Forms where immediate validation is needed
- Low-traffic forms (admin panels, config)

---

## Fix Option 2: Use wire:submit (ALTERNATIVE)

**CHANGE:** Wrap in `<form>` with `wire:submit.prevent`

```blade
<!-- ALTERNATIVE FIX -->
<form wire:submit.prevent="saveSyncConfiguration">
    <select wire:model.defer="autoSyncFrequency">
        <option value="hourly">Co godzinę</option>
        <option value="daily">Codziennie</option>
        <option value="weekly">Tygodniowo</option>
    </select>

    <button type="submit">
        Zapisz konfigurację
    </button>
</form>
```

**Behavior:**
- `wire:submit` triggers defer synchronization BEFORE calling method
- Fewer requests (defer batches changes)
- Requires form restructuring (button type="submit")

**When to use:**
- Large forms with many fields
- High-traffic forms (user-facing)
- When you want to batch changes

---

## Fix Option 3: Manual Synchronization (NOT RECOMMENDED)

```php
public function saveSyncConfiguration()
{
    // Force synchronization of deferred properties
    $this->skipRender(); // Prevent re-render

    // Then save
    SystemSetting::updateOrCreate(
        ['key' => 'sync.schedule.frequency'],
        ['value' => $this->autoSyncFrequency]
    );
}
```

**Issues:**
- Livewire 3.x doesn't expose easy way to force defer sync
- Requires internal API knowledge
- Not future-proof

---

## Verification Test

**BEFORE FIX (BUG):**
```
1. Open /admin/shops/sync
2. Click "Pokaż konfigurację"
3. Change frequency: "Co godzinę" → "Codziennie"
4. Click "Zapisz konfigurację"
5. Refresh page (F5)
6. ACTUAL: Shows "Co godzinę" (change LOST)
```

**AFTER FIX:**
```
1. Open /admin/shops/sync
2. Click "Pokaż konfigurację"
3. Change frequency: "Co godzinę" → "Codziennie"
4. Click "Zapisz konfigurację"
5. Refresh page (F5)
6. EXPECTED: Shows "Codziennie" (change SAVED)
```

---

## Database Verification

```sql
-- Check saved value
SELECT `key`, `value`, `type`
FROM `system_settings`
WHERE `key` = 'sync.schedule.frequency';

-- BEFORE FIX: Always "hourly" (default)
-- AFTER FIX: User-selected value ("daily", "weekly")
```

---

## Impact Analysis

**AFFECTED FILES:**
- `resources/views/livewire/admin/shops/sync-controller.blade.php`

**AFFECTED FIELDS (~30 fields):**
- `autoSyncFrequency` (PRIMARY issue)
- `batchSize`, `syncTimeout`, `conflictResolution`
- `autoSyncEnabled`, `autoSyncScheduleHour`, `autoSyncDaysOfWeek`
- `retryEnabled`, `maxRetryAttempts`, `retryDelayMinutes`
- `notificationsEnabled`, `notifyOnSuccess`, `notifyOnFailure`
- `performanceMode`, `maxConcurrentJobs`, `jobProcessingDelay`
- `backupBeforeSync`, `backupRetentionDays`, etc.

**USER IMPACT:**
- HIGH - Data loss (user changes not saved)
- Frustration (user thinks settings saved, but reverted)

---

## Prevention

**RULE:** In Livewire 3.x, when using `wire:click` buttons:

✅ **DO:** Use `wire:model.live` or `wire:model` (no modifier)
❌ **DON'T:** Use `wire:model.defer` (race condition!)

**ALTERNATIVE:** If you want defer (performance):
✅ **DO:** Use `<form wire:submit.prevent="method">` + `<button type="submit">`

---

## Related Issues

- [LIVEWIRE_EMIT_DISPATCH_ISSUE.md](LIVEWIRE_EMIT_DISPATCH_ISSUE.md) - Livewire 3.x API changes
- [LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md](LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md) - Constructor DI conflicts
- [LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md](LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md) - Rendering issues

---

## References

- **Livewire 3.x Docs:** https://livewire.laravel.com/docs/wire-model
- **wire:model modifiers:** https://livewire.laravel.com/docs/wire-model#available-modifiers
- **PPM Issue:** Frequency save bug (2025-11-13)

---

**FIXED BY:** debugger agent
**DATE:** 2025-11-13
**DEPLOYMENT:** Production (ppm.mpptrade.pl)
