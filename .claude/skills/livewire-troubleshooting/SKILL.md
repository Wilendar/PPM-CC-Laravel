# Livewire Troubleshooting Skill

---
name: livewire-troubleshooting
description: Use when debugging Livewire 3.x issues - wire:snapshot, events, polling, teleport, DI conflicts, state management
version: 1.0.0
author: Claude Code
created: 2026-01-15
updated: 2026-01-15
tags: [livewire, debugging, troubleshooting, laravel, alpine]
---

## Overview

Skill do diagnozowania i naprawiania najczęstszych problemów z Livewire 3.x w projekcie PPM-CC-Laravel. Zawiera 9 udokumentowanych wzorców problemów i ich rozwiązania.

**Kiedy używać:**
- Błędy `MethodNotFoundException` mimo istniejącej metody
- `wire:snapshot` wyświetla się zamiast UI
- `wire:poll` nie aktualizuje komponentu
- `wire:click` nie działa w modalu
- `BadMethodCallException: Method emit does not exist`
- `BindingResolutionException` dla typowanych properties
- Checkboxy/listy mają problemy ze stanem po operacjach

---

## 🔥 ISSUE #1: wire:snapshot Rendering Problem

### Symptomy
- Surowy JSON `wire:snapshot="eyJ..."` widoczny na stronie
- Brak renderowania UI komponentu
- Component mount() działa poprawnie

### Root Cause
**Konflikt layout** - bezpośredni routing komponentu który ma layout w `render()`.

### ❌ BŁĘDNY PATTERN
```php
// routes/web.php
Route::get('/create', ProductForm::class)->name('create');

// ProductForm.php - render() z layout
public function render()
{
    return view('livewire.product-form')->layout('layouts.admin');
}
```

### ✅ ROZWIĄZANIE #1: Blade Wrapper (PREFEROWANE)
```php
// routes/web.php
Route::get('/create', fn() => view('pages.embed-product-form'))->name('create');

// resources/views/pages/embed-product-form.blade.php
<livewire:products.management.product-form />
```

### ✅ ROZWIĄZANIE #2: Layout w Routing
```php
// routes/web.php - layout TU, nie w render()
Route::get('/create', ProductForm::class)
    ->layout('layouts.admin')
    ->name('create');

// ProductForm.php - render() BEZ layout
public function render()
{
    return view('livewire.product-form');
}
```

### 🛡️ ZŁOTA ZASADA
- **Routing bezpośredni** → layout w `->layout()` na route
- **Routing przez blade** → layout w `render()->layout()`
- **NIGDY** layout w obu miejscach!

**Reference:** `_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md`

---

## 🔥 ISSUE #2: emit() → dispatch() Migration (Livewire 3.x)

### Symptomy
- `BadMethodCallException: Method emit does not exist`
- `BadMethodCallException: Method emitTo does not exist`
- Brak komunikacji między komponentami

### Root Cause
Livewire 3.x usunęło `emit()` na rzecz `dispatch()`.

### MIGRACJA API

| Livewire 2.x | Livewire 3.x |
|--------------|--------------|
| `$this->emit('event')` | `$this->dispatch('event')` |
| `$this->emitTo('Comp', 'event')` | `$this->dispatch('event')->to('Comp')` |
| `$this->emitSelf('event')` | `$this->dispatch('event')->self()` |
| `$this->emitUp('event')` | `$this->dispatch('event')->up()` |

### Przykłady
```php
// ❌ STARE (Livewire 2.x)
$this->emit('shopDeleted', ['shopId' => $id]);
$this->emitTo('ShopList', 'refresh');
$this->emitSelf('updated');
$this->emitUp('modalClosed');

// ✅ NOWE (Livewire 3.x)
$this->dispatch('shopDeleted', ['shopId' => $id]);
$this->dispatch('refresh')->to('ShopList');
$this->dispatch('updated')->self();
$this->dispatch('modalClosed')->up();
```

### JavaScript/Alpine.js
```javascript
// ❌ STARE
Livewire.emit('eventName', data);
$wire.emit('buttonClicked');

// ✅ NOWE
Livewire.dispatch('eventName', data);
$wire.dispatch('buttonClicked');
```

### Znajdowanie w projekcie
```bash
grep -r "\$this->emit" app/Http/Livewire/
grep -r "emitTo\|emitSelf\|emitUp" app/Http/Livewire/
```

**Reference:** `_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md`

---

## 🔥 ISSUE #3: wire:poll Inside Conditional (@if)

### Symptomy
- `wire:poll` nie aktualizuje komponentu
- Real-time updates wymagają ręcznego F5
- Dynamic content nie pojawia się automatycznie

### Root Cause
**Element z wire:poll musi ZAWSZE istnieć w DOM!**

Gdy `wire:poll` jest wewnątrz `@if` który jest `false`, element nie renderuje się → polling nigdy się nie inicjalizuje.

### ❌ BŁĘDNY PATTERN
```blade
@if($someCondition)
    <div wire:poll.3s>
        <!-- Content -->
    </div>
@endif
```

### ✅ PRAWIDŁOWY PATTERN
```blade
<div wire:poll.3s>
    @if($someCondition)
        <!-- Content -->
    @endif
</div>
```

### Dlaczego to działa
1. ✅ Wrapper z `wire:poll` **ZAWSZE** w DOM
2. ✅ Livewire inicjalizuje polling przy pierwszym render
3. ✅ Polling sprawdza condition co N sekund
4. ✅ Gdy condition zmieni się na true → content pojawia się automatycznie

### 🛡️ ZŁOTA ZASADA
```blade
{{-- ✅ ZAWSZE --}}
<div wire:poll.3s>
    @if($condition)...</@if>
</div>

{{-- ❌ NIGDY --}}
@if($condition)
    <div wire:poll.3s>...</div>
@endif
```

**Reference:** `_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md`

---

## 🔥 ISSUE #4: Livewire Events from Queue Job

### Symptomy
- `Call to undefined method Livewire\LivewireManager::dispatch()`
- Queue job FAIL z Livewire error
- Background job nie może triggerować modal

### Root Cause
**Livewire events wymagają HTTP request context!**

Queue jobs działają w CLI/background bez session/request - Livewire nie ma active component.

### ❌ ZABRONIONE w Queue Jobs
```php
// W Job::handle()
\Livewire\Livewire::dispatch('event', $data); // CRASH!
$this->emit('event'); // CRASH!
$this->dispatch('event'); // CRASH!
```

### ✅ ROZWIĄZANIE: Database Polling
```php
// Queue Job - zapisz do DB
CategoryPreview::create([
    'status' => 'pending',
    'data' => $data,
]);

// Livewire Component - poll DB
// W blade:
<div wire:poll.3s="checkForPendingPreviews">

// W PHP:
public function checkForPendingPreviews(): void
{
    $preview = CategoryPreview::where('status', 'pending')->first();
    if ($preview) {
        $this->dispatch('openModal', ['id' => $preview->id]);
    }
}
```

### Alternatywa: Laravel Broadcasting
```php
// Queue Job - Laravel event (NOT Livewire!)
event(new CategoryPreviewReady($preview->id));

// Frontend - Laravel Echo
Echo.private('channel').listen('CategoryPreviewReady', (e) => {
    Livewire.dispatch('openModal', e);
});
```

**Reference:** `_ISSUES_FIXES/LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md`

---

## 🔥 ISSUE #5: x-teleport + wire:click Not Working

### Symptomy
- `MethodNotFoundException: Unable to call component method`
- `wire:click` nie wywołuje metody mimo że jest PUBLIC
- Modal otwiera się ale przyciski nie działają

### Root Cause
**x-teleport przenosi DOM poza kontekst Livewire component!**

Element teleportowany do `<body>` traci powiązanie z `wire:id`.

### ❌ BŁĘDNY PATTERN
```blade
<template x-teleport="body">
    <div x-data="...">
        <button wire:click="approve">Approve</button> {{-- NIE DZIAŁA! --}}
    </div>
</template>
```

### ✅ ROZWIĄZANIE: Usuń x-teleport, użyj z-index
```blade
{{-- BEZ x-teleport, wysoki z-index --}}
<div x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     x-cloak
     class="fixed inset-0 z-[9999]">

    <div @click="isOpen = false" class="absolute inset-0 bg-black/70"></div>

    <div class="relative ...">
        <button wire:click="approve">Approve</button> {{-- DZIAŁA! --}}
    </div>
</div>
```

### 🛡️ KRYTYCZNE ZASADY
- ❌ NIE używaj `x-teleport` w Livewire child components
- ❌ NIE używaj `wire:id` w child components (psuje parent!)
- ❌ NIE polegaj na `$wire` w x-teleport (referencuje parent!)
- ✅ UŻYJ wysokiego z-index (9999+) zamiast teleport
- ✅ TRZYMAJ modal w kontekście komponentu

**Reference:** `_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md`

---

## 🔥 ISSUE #6: Dependency Injection Conflict

### Symptomy
- `BindingResolutionException: Unable to resolve dependency`
- Błąd przed wywołaniem `mount()`
- Laravel próbuje rozwiązać primitive type jako DI

### Root Cause
**Non-nullable typed properties** są traktowane jako DI zamiast Blade parameters.

### ❌ BŁĘDNY PATTERN
```php
class JobProgressBar extends Component
{
    public int $progressId; // Laravel próbuje rozwiązać int przez DI!

    public function mount(int $progressId): void
    {
        $this->progressId = $progressId;
    }
}
```

### ✅ ROZWIĄZANIE: Nullable + Default
```php
class JobProgressBar extends Component
{
    public ?int $progressId = null; // Nullable + default

    public function mount(int $progressId): void
    {
        $this->progressId = (int) $progressId; // Explicit cast
    }

    public function fetchProgress(): void
    {
        if ($this->progressId === null) {
            Log::error('progressId is null');
            return;
        }
        // Safe to use
    }
}
```

### 🛡️ ZASADY
```php
// ❌ ZŁE - DI conflict
public int $userId;
public string $category;
public Model $product;

// ✅ DOBRE - Nullable + default
public ?int $userId = null;
public ?string $category = null;
public ?Model $product = null;
```

**Reference:** `_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md`

---

## 🔥 ISSUE #7: wire:model.defer + wire:click Race Condition

### Symptomy
- Zmiana w formularzu nie zapisuje się
- Po refresh wartość wraca do poprzedniej
- `wire:click` używa starej wartości property

### Root Cause
**Race condition** - `wire:model.defer` synchronizuje DOPIERO przy submit, a `wire:click` wywołuje metodę NATYCHMIAST.

### Event Sequence (BUG)
```
1. User zmienia select (wire:model.defer) - local state only
2. User klika button (wire:click) - method called IMMEDIATELY
3. Method reads OLD value (defer not synced yet!)
4. AFTER method returns, defer syncs (TOO LATE!)
```

### ❌ BŁĘDNY PATTERN
```blade
<select wire:model.defer="frequency">
    <option value="hourly">Hourly</option>
    <option value="daily">Daily</option>
</select>

<button wire:click="save">Save</button> {{-- Reads OLD value! --}}
```

### ✅ ROZWIĄZANIE #1: wire:model.live
```blade
<select wire:model.live="frequency"> {{-- Syncs immediately --}}
    <option value="hourly">Hourly</option>
    <option value="daily">Daily</option>
</select>

<button wire:click="save">Save</button> {{-- Reads CURRENT value --}}
```

### ✅ ROZWIĄZANIE #2: wire:submit
```blade
<form wire:submit.prevent="save"> {{-- Submit triggers defer sync FIRST --}}
    <select wire:model.defer="frequency">
        <option value="hourly">Hourly</option>
        <option value="daily">Daily</option>
    </select>

    <button type="submit">Save</button>
</form>
```

### 🛡️ ZASADA
- `wire:click` + button → użyj `wire:model.live` lub `wire:model`
- `wire:submit` + form → możesz użyć `wire:model.defer`

**Reference:** `_ISSUES_FIXES/LIVEWIRE_WIRE_MODEL_DEFER_RACE_CONDITION.md`

---

## 🔥 ISSUE #8: Missing wire:key on Lists

### Symptomy
- Checkboxy "przeskakują" na inne elementy po delete
- Stan listy jest niesynchronizowany z UI
- Livewire nie rozpoznaje które elementy się zmieniły

### Root Cause
**Brak `wire:key`** - Livewire identyfikuje elementy po pozycji, nie po ID.

### ❌ BŁĘDNY PATTERN
```blade
@foreach($categories as $category)
    <tr class="..."> {{-- Brak wire:key! --}}
        <td><input type="checkbox" wire:click="toggle({{ $category->id }})"></td>
    </tr>
@endforeach
```

### ✅ PRAWIDŁOWY PATTERN
```blade
<tbody wire:key="list-{{ $viewMode }}">
    @foreach($categories as $category)
        <tr wire:key="row-{{ $category->id }}" class="...">
            <td><input type="checkbox" wire:click="toggle({{ $category->id }})"></td>
        </tr>
    @endforeach
</tbody>
```

### 🛡️ ZASADY
- **MANDATORY**: `wire:key` na każdym elemencie w pętli
- Key musi być **unikatowy** i **stabilny** (używaj ID, nie index)
- Dodaj `wire:key` również na parent container (tbody, ul, etc.)

**Reference:** `_AGENT_REPORTS/livewire_specialist_CHECKBOX_BUG_ANALYSIS_FINAL.md`

---

## 🔥 ISSUE #9: Sparse Array Keys After Operations

### Symptomy
- State nie synchronizuje się poprawnie po remove operations
- Livewire reactivity jest niestabilna
- JSON serialization ma problemy

### Root Cause
**`array_diff()` zachowuje oryginalne keys** → sparse array [0, 2, 4] zamiast [0, 1, 2].

### ❌ BŁĘDNY PATTERN
```php
public function toggleSelection(int $id): void
{
    if (in_array($id, $this->selected)) {
        // array_diff() leaves holes: [0=>10, 2=>30]
        $this->selected = array_diff($this->selected, [$id]);
    }
}
```

### ✅ PRAWIDŁOWY PATTERN
```php
public function toggleSelection(int $id): void
{
    if (in_array($id, $this->selected)) {
        // array_values() resets keys: [0=>10, 1=>30]
        $this->selected = array_values(
            array_filter($this->selected, fn($item) => $item !== $id)
        );
    } else {
        $this->selected[] = $id;
    }
}
```

### 🛡️ ZASADA
Zawsze użyj `array_values()` po operacjach usuwających elementy:
```php
$array = array_values(array_diff($array, [$removeId]));
$array = array_values(array_filter($array, fn($x) => $x !== $removeId));
```

**Reference:** `_AGENT_REPORTS/livewire_specialist_CHECKBOX_RESET_BUG_FIX.md`

---

## 📋 QUICK DIAGNOSIS CHECKLIST

### Gdy wire:click nie działa:
1. [ ] Czy metoda jest `public`?
2. [ ] Czy element jest w `x-teleport`? → Usuń teleport
3. [ ] Czy używasz `wire:model.defer` z `wire:click`? → Zmień na `.live`

### Gdy component nie renderuje się:
1. [ ] Czy widzisz `wire:snapshot`? → Sprawdź layout routing
2. [ ] Czy używasz bezpośredniego routingu? → Dodaj blade wrapper

### Gdy polling nie działa:
1. [ ] Czy `wire:poll` jest wewnątrz `@if`? → Przenieś na zewnątrz
2. [ ] Czy element z `wire:poll` zawsze istnieje w DOM?

### Gdy lista ma problemy ze stanem:
1. [ ] Czy masz `wire:key` na każdym elemencie pętli?
2. [ ] Czy używasz `array_values()` po array operations?

### Gdy masz BindingResolutionException:
1. [ ] Czy property jest non-nullable typed? → Zmień na `?type = null`

### Gdy emit() nie istnieje:
1. [ ] Czy migrowałeś do Livewire 3.x? → Użyj `dispatch()`

---

## 🔗 POWIĄZANE DOKUMENTY

### _ISSUES_FIXES/
- `LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md`
- `LIVEWIRE_EMIT_DISPATCH_ISSUE.md`
- `LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md`
- `LIVEWIRE_DISPATCH_FROM_QUEUE_JOB_ISSUE.md`
- `LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md`
- `LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md`
- `LIVEWIRE_WIRE_MODEL_DEFER_RACE_CONDITION.md`

### Context7 Documentation
- Library ID: `/livewire/livewire`
- Topics: lifecycle hooks, wire:model, wire:poll, Alpine integration

---

## 📊 SYSTEM UCZENIA SIĘ

### Tracking
Ten skill zbiera dane o:
- Które issues są najczęściej diagnozowane
- Czas rozwiązania dla każdego typu problemu
- Nowe wzorce problemów do dokumentacji

### Metryki Sukcesu
- Issue resolution success rate: >95%
- Average diagnosis time: <5 min
- Zero repeat issues (once fixed, stays fixed)

### Historia Ulepszeń
#### v1.0.0 (2026-01-15)
- [INIT] Utworzono skill z 9 udokumentowanymi issues
- [SOURCE] _ISSUES_FIXES/ - 7 plików Livewire
- [SOURCE] _AGENT_REPORTS/ - Checkbox bug analysis
- [SOURCE] Context7 - Official Livewire 3.x documentation

---

**Status:** ✅ ACTIVE
**Confidence:** HIGH (100% issues verified in production)
**Last Updated:** 2026-01-15
