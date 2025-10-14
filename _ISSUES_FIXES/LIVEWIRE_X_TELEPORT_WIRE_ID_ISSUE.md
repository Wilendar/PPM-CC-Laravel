# ISSUE: Livewire wire:click Nie Działa w x-teleport Element

**Data Odkrycia**: 2025-10-09
**Kategoria**: Livewire + Alpine.js Integration
**Severity**: 🔥 CRITICAL
**Status**: ✅ ROZWIĄZANY

---

## 🚨 PROBLEM

### Objawy
```
Livewire\Exceptions\MethodNotFoundException
Unable to call component method. Public method [approve] not found on component
```

### Kontekst
- Komponent: `CategoryPreviewModal` (Livewire 3.x)
- Template używa: `<template x-teleport="body">`
- Przycisk z: `wire:click="approve"`
- Metoda `approve()` istnieje i jest PUBLIC
- Metoda NIE jest wywoływana (brak 🔥 emoji w debug logach)

### Root Cause Analysis

**Alpine.js `x-teleport` przenosi DOM element poza kontekst Livewire component!**

```blade
<template x-teleport="body">
    <div x-data="{ isOpen: @entangle('isOpen') }">
        <!-- Modal content -->
        <button wire:click="approve">Approve</button>  ❌ NIE DZIAŁA!
    </div>
</template>
```

**Dlaczego to powoduje problem:**

1. `x-teleport="body"` fizycznie przenosi DOM do `<body>` w HTML
2. Element jest POZA oryginalnym `wire:id` scope Livewire component
3. Livewire nie może routować `wire:click` bo nie wie do którego komponentu należy ten element
4. Result: `MethodNotFoundException` mimo że metoda istnieje

---

## ✅ ROZWIĄZANIE

### ⚠️ UWAGA: wire:id NIE DZIAŁA dla Child Components!

**Pierwotne rozwiązanie z wire:id było BŁĘDNE** i powodowało że cały parent component przestał działać!

### PRAWIDŁOWE ROZWIĄZANIE: **USUŃ x-teleport całkowicie!** ✅

**x-teleport w child Livewire component ZAWSZE powoduje problemy z routing!**

```blade
{{-- ❌ WRONG - z x-teleport --}}
<template x-teleport="body">
    <div x-data="{ isOpen: @entangle('isOpen') }">
        <button wire:click="approve">Approve</button>  {{-- NIE DZIAŁA! --}}
    </div>
</template>

{{-- ✅ CORRECT - BEZ x-teleport, użyj wysokiego z-index --}}
<div x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     x-cloak
     class="fixed inset-0 z-[9999]"  {{-- Wysoki z-index zamiast teleport! --}}
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    <!-- Background Overlay -->
    <div @click="isOpen = false" class="absolute inset-0 bg-black/70"></div>

    <!-- Modal Content -->
    <div class="relative ...">
        <button wire:click="approve">Approve</button>  ✅ DZIAŁA!
    </div>
</div>
```

**DLACZEGO to działa:**
- Modal pozostaje w kontekście Livewire component (nie jest teleportowany)
- `wire:click` działa normalnie (standard Livewire routing)
- Wysoki `z-index` (9999) zapewnia że modal jest na wierzchu
- Nie ma konfliktów z parent component

### Deployment Steps

```bash
# 1. Edit template - dodaj wire:id
# resources/views/livewire/components/category-preview-modal.blade.php

# 2. Upload template
pscp -i "path/to/key.ppk" -P 64321 \
  "local/category-preview-modal.blade.php" \
  host@server:path/category-preview-modal.blade.php

# 3. Clear view cache (CRITICAL!)
php artisan view:clear
php artisan cache:clear

# 4. Test w przeglądarce (hard refresh: Ctrl+Shift+R)
```

---

## 🛡️ PREVENTION RULES

### ⚠️ ZAWSZE gdy używasz x-teleport z Livewire:

```blade
<!-- ❌ WRONG - Livewire routing nie działa -->
<template x-teleport="body">
    <div x-data="...">
        <button wire:click="method">Click</button>
    </div>
</template>

<!-- ✅ CORRECT - z wire:id -->
<template x-teleport="body">
    <div wire:id="{{ $this->getId() }}" x-data="...">
        <button wire:click="method">Click</button>
    </div>
</template>
```

### CHECKLIST dla Livewire + Alpine Modals:

- [x] **NIE UŻYWAJ `x-teleport` w Livewire child components!** (użyj wysokiego z-index)
- [x] Użyj `class="fixed inset-0 z-[9999]"` zamiast x-teleport
- [x] Pozostaw standardowe `wire:click` (działa normalnie bez teleport)
- [x] Użyj `@entangle()` dla two-way binding (isOpen, etc.)
- [x] Po zmianach → clear view cache + test
- [x] Jeśli metoda nie działa → sprawdź logi (czy metoda była wywołana?)
- [x] **CRITICAL**: NIGDY nie dodawaj `wire:id` do child component!

---

## 📋 DEBUG WORKFLOW

### Jak Zdiagnozować Ten Problem

**Symptomy wskazujące na x-teleport issue:**

1. ✅ Metoda istnieje i jest PUBLIC (weryfikacja: `method_exists($this, 'approve')`)
2. ✅ Modal się otwiera poprawnie (`@entangle` działa)
3. ❌ `wire:click` nie wywołuje metody (brak loga w approve())
4. ❌ `MethodNotFoundException` mimo że metoda istnieje

**Debug Steps:**

```php
// 1. Dodaj log na początku metody
public function approve(): void
{
    Log::info('🔥 approve() CALLED', ['component_id' => $this->getId()]);
    // ... rest of method
}

// 2. Dodaj log przy otwarciu modal
public function show(): void
{
    Log::info('🎯 show() CALLED', [
        'component_id' => $this->getId(),
        'has_approve_method' => method_exists($this, 'approve'),
    ]);
}

// 3. Przetestuj → jeśli widzisz 🎯 ale NIE widzisz 🔥 → routing problem!
```

**Sprawdź template:**

```bash
# Szukaj x-teleport bez wire:id
grep -n "x-teleport" template.blade.php
grep -n "wire:id" template.blade.php

# Jeśli x-teleport jest, a wire:id NIE → ZNALAZŁEŚ PROBLEM!
```

---

## 💡 ALTERNATIVE SOLUTIONS

### Opcja 1: Usuń x-teleport (✅ FINAL WORKING SOLUTION)

```blade
<!-- NIE teleport - modal w miejscu z wysokim z-index -->
<div x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     class="fixed inset-0 z-[9999]">  {{-- Bardzo wysoki z-index! --}}
    <!-- Modal content -->
</div>
```

**Pros**:
- ✅ Livewire routing działa out-of-the-box
- ✅ Prosty, przewidywalny kod
- ✅ Nie ma konfliktów z parent component
- ✅ Standard Livewire patterns

**Cons**:
- ⚠️ Wymaga bardzo wysokiego z-index (9999+) aby być na wierzchu wszystkiego
- ⚠️ Może być problem jeśli parent ma `isolation` CSS property

**Status**: ✅ **WDROŻONE w CategoryPreviewModal - działa idealnie!**

### Opcja 2: Użyj $wire.method() (❌ NIE DZIAŁA w child component!)

```blade
<template x-teleport="body">
    <div x-data="{ isOpen: @entangle('isOpen') }">
        <!-- Alpine magic property $wire -->
        <button @click="$wire.approve()">Approve</button>  {{-- BŁĄD! --}}
    </div>
</template>
```

**Cons**:
- ❌ W x-teleport, `$wire` referencuje **PARENT** component, nie child!
- ❌ Wywołuje method w ProductList zamiast CategoryPreviewModal
- ❌ Result: `MethodNotFoundException` mimo że metoda istnieje w child

**Status**: ❌ **TESTED i REJECTED** - nie działa w naszym use case

### Opcja 3: wire:id (❌ NIE UŻYWAĆ w Child Components!)

```blade
<template x-teleport="body">
    <div wire:id="{{ $this->getId() }}" x-data="...">
        <button wire:click="approve">Approve</button>
    </div>
</template>
```

**Cons**:
- ❌ **CAŁKOWICIE PSUJE parent component!**
- ❌ Wire ID conflict między parent i child
- ❌ Wszystkie wire:click w parent przestają działać
- ❌ User nie może nawet otworzyć modal

**Status**: ❌ **TESTED i REJECTED** - krytyczny bug

---

## 🔗 RELATED ISSUES

- `LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md` - Wire snapshot rendering problems
- `LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md` - wire:poll w @if
- `CSS_STACKING_CONTEXT_ISSUE.md` - Z-index problems (dlaczego używamy teleport)

---

## 📚 REFERENCES

**Livewire Docs:**
- https://livewire.laravel.com/docs/3.x/teleport
- https://livewire.laravel.com/docs/3.x/alpine#accessing-livewire-from-alpine

**Alpine.js Docs:**
- https://alpinejs.dev/directives/teleport

**Stack Overflow:**
- "Livewire wire:click not working in teleported element" (common issue)

---

## 🎯 SUMMARY

**Problem**: `x-teleport` w child Livewire component powoduje problemy z routing
**Root Cause**: Teleported element traci kontekst komponentu (wire:click nie działa)
**Solution**: **USUŃ x-teleport** i użyj wysokiego `z-index` (9999+)
**Prevention**: **NIE UŻYWAJ x-teleport w Livewire child components!**
**Testing**: Debug logs + hard refresh przeglądarki

**KRYTYCZNE ZASADY**:
- ❌ NIE używaj `x-teleport` w Livewire child components
- ❌ NIE używaj `wire:id` w child components (psuje parent!)
- ❌ NIE polegaj na `$wire` w x-teleport (referencuje parent!)
- ✅ UŻYJ wysokiego z-index zamiast teleport
- ✅ TRZYMAJ modal w kontekście komponenta

---

**Status**: ✅ RESOLVED (2025-10-09)
**Deployed**: Production (ppm.mpptrade.pl)
**Verified**: CategoryPreviewModal approve button działa poprawnie
