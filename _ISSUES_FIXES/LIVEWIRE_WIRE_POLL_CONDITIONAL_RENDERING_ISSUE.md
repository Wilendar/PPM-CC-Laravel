# ISSUE: Livewire wire:poll Inside Conditional Rendering (@if)

**Severity:** 🔥 CRITICAL
**Category:** Livewire 3.x Reactivity
**First Discovered:** 2025-10-08
**Component:** Real-Time Progress Tracking System

---

## 🚨 PROBLEM DESCRIPTION

**Symptom:** `wire:poll` directive nie działa - component nie aktualizuje się automatycznie mimo poprawnej konfiguracji.

**User Experience:**
- Real-time updates NIE pojawiają się automatycznie
- Użytkownik musi ręcznie odświeżać stronę (F5)
- Dynamic content (np. progress bars, notifications) nie wyświetla się w odpowiednim czasie

---

## 🔍 ROOT CAUSE

**KRYTYCZNY BŁĄD:** Umieszczenie elementu z `wire:poll` wewnątrz warunkowego renderowania (`@if`, `@unless`, `x-show` z v-if logic).

### ❌ BŁĘDNY CODE PATTERN:

```blade
@if($someCondition)
    <div wire:poll.3s>
        <!-- Dynamic content -->
    </div>
@endif
```

### DLACZEGO TO NIE DZIAŁA:

1. **Inicjalizacja Livewire Component:**
   - Livewire inicjalizuje `wire:poll` podczas pierwszego renderowania component
   - Element musi **istnieć w DOM** w momencie inicjalizacji

2. **Gdy `$someCondition` jest `false`:**
   - Element `<div wire:poll.3s>` **NIE renderuje się** w DOM
   - Livewire nie może założyć polling listener (element nie istnieje)
   - **Polling nigdy się nie inicjalizuje**

3. **CATCH-22 Paradox:**
   - Aby wykryć zmianę `$someCondition` z false → true, potrzebujesz `wire:poll`
   - Ale `wire:poll` nie działa bo element nie istnieje gdy condition jest false
   - **Niemożliwe do rozwiązania bez user F5**

### REAL-WORLD EXAMPLE (PPM-CC-Laravel):

```blade
{{-- ❌ BŁĘDNY PATTERN - Progress tracking --}}
@if(!empty($this->activeJobProgress))
    <div class="progress-section" wire:poll.3s>
        <livewire:job-progress-bar ... />
    </div>
@endif
```

**Problem:**
- Gdy brak aktywnych jobów → `$this->activeJobProgress` pusty → condition FALSE
- Element nie renderuje się → `wire:poll` nie działa
- Gdy job się pojawia → polling nie działa więc nie wykrywa nowego joba
- Użytkownik NIGDY nie zobaczy progress bar bez F5

---

## ✅ SOLUTION

### POPRAWNY PATTERN: wire:poll POZA @if

```blade
{{-- ✅ CORRECT - Wrapper zawsze w DOM, polling zawsze aktywny --}}
<div wire:poll.3s>
    @if($someCondition)
        <div class="content">
            <!-- Dynamic content -->
        </div>
    @endif
</div>
```

### DLACZEGO TO DZIAŁA:

1. ✅ `<div wire:poll.3s>` **ZAWSZE istnieje** w DOM (niezależnie od condition)
2. ✅ Livewire może zainicjalizować polling podczas pierwszego render
3. ✅ Polling działa **non-stop** co N sekund
4. ✅ Sprawdza computed property/reactive data regularnie
5. ✅ Gdy `$someCondition` zmienia się z false → true, content się pojawia **automatycznie**
6. ✅ User widzi zmiany w real-time **bez F5**

### REAL-WORLD FIX (PPM-CC-Laravel):

```blade
{{-- ✅ FIXED - Progress tracking --}}
<div wire:poll.3s>
    @if(!empty($this->activeJobProgress))
        <div class="px-6 sm:px-8 lg:px-12 pt-6">
            <!-- Aktywne Operacje section -->
            <livewire:job-progress-bar ... />
        </div>
    @endif
</div>
```

**Result:**
- Wrapper zawsze w DOM → polling zawsze aktywny
- Gdy job się pojawia → `$this->activeJobProgress` staje się niepusty → sekcja renderuje się
- User widzi progress bar **automatycznie po 3 sekundach** od startu joba

---

## 🎯 BEST PRACTICES

### 1. **ZAWSZE** umieszczaj `wire:poll` na elemencie który ISTNIEJE:

```blade
{{-- ✅ GOOD --}}
<div wire:poll.3s>
    @if($condition) ... @endif
</div>

{{-- ❌ BAD --}}
@if($condition)
    <div wire:poll.3s> ... </div>
@endif
```

### 2. **Performance Optimization** - Conditional Polling:

Jeśli MUSISZ warunkowe polling (np. tylko gdy user jest na stronie):

```blade
{{-- Użyj wire:poll.visible lub wire:poll.keep-alive --}}
<div wire:poll.3s.visible>
    <!-- Polling tylko gdy element jest visible in viewport -->
</div>
```

**LUB** kontroluj polling programmatically w component:

```php
// W Livewire component
public bool $enablePolling = false;

public function mount(): void
{
    $this->enablePolling = $this->hasActiveJobs();
}
```

```blade
<div @if($enablePolling) wire:poll.3s @endif>
    <!-- Content -->
</div>
```

**⚠️ UWAGA:** Drugi pattern nadal ma issue - jeśli `$enablePolling` jest false podczas mount, polling nigdy się nie włączy!

**LEPSZE ROZWIĄZANIE:**

```php
// Component method
public function getPollingIntervalProperty(): ?int
{
    return $this->hasActiveJobs() ? 3000 : null; // 3s lub disable
}
```

```blade
<div wire:poll="{{ $this->pollingInterval }}ms">
    <!-- Content -->
</div>
```

### 3. **Lightweight Wrapper:**

Upewnij się że wrapper z `wire:poll` jest **lekki** (minimal overhead):

```blade
{{-- ✅ GOOD - Lekki wrapper --}}
<div wire:poll.3s>
    @if($condition)
        <heavy-component />
    @endif
</div>

{{-- ❌ BAD - Heavy wrapper --}}
<div wire:poll.3s class="complex-layout with lots of styles">
    <many nested divs>
        @if($condition) ... @endif
    </many nested divs>
</div>
```

---

## 🛡️ PREVENTION CHECKLIST

Podczas implementacji `wire:poll`, sprawdź:

- [ ] Element z `wire:poll` **NIE jest** wewnątrz `@if` / `@unless`
- [ ] Element z `wire:poll` **NIE jest** wewnątrz `x-show` z dynamic condition
- [ ] Element z `wire:poll` **ZAWSZE** renderuje się w DOM (niezależnie od stanu aplikacji)
- [ ] Conditional rendering jest **WEWNĄTRZ** elementu z `wire:poll`
- [ ] Wrapper z `wire:poll` jest **lightweight** (minimal HTML/CSS)
- [ ] Tested: Sprawdź Dev Tools → Elements tab → element z wire:poll istnieje nawet gdy content ukryty

---

## 🧪 TESTING PROCEDURE

### Test #1: Element Exists in DOM

**Kroki:**
1. Otwórz stronę z `wire:poll` component
2. F12 → Elements tab
3. Znajdź element z `wire:poll` directive
4. Sprawdź czy element **istnieje** gdy condition jest FALSE

**Expected:**
- ✅ Element z `wire:poll` jest w DOM (może być pusty)
- ✅ Console nie pokazuje Livewire errors

**Failed jeśli:**
- ❌ Element nie istnieje w DOM gdy condition FALSE
- ❌ Console pokazuje: "Livewire directive 'poll' missing"

### Test #2: Auto-Update Works

**Kroki:**
1. Upewnij się że condition jest FALSE (content ukryty)
2. Uruchom akcję która zmienia condition na TRUE (np. start background job)
3. **NIE ODŚWIEŻAJ strony (no F5!)**
4. Obserwuj stronę przez czas polling interval (np. 3s)

**Expected:**
- ✅ Po ~3s content pojawia się automatycznie
- ✅ User NIE musi naciskać F5

**Failed jeśli:**
- ❌ Content nie pojawia się automatycznie
- ❌ Wymaga ręcznego F5

---

## 📚 RELATED DOCUMENTATION

### Livewire 3.x Documentation:
- **wire:poll**: https://livewire.laravel.com/docs/wire-poll
- **Reactivity**: https://livewire.laravel.com/docs/reactivity

### Related Issues in PPM-CC-Laravel:
- `_AGENT_REPORTS/CRITICAL_FIX_WIRE_POLL_MODAL_2025-10-08.md` - Full incident report
- `_AGENT_REPORTS/PROGRESS_TRACKING_DEBUG_FIX_2025-10-08.md` - First iteration (incomplete fix)

### Related Livewire Directives:
- `wire:poll.visible` - Poll tylko gdy element visible in viewport
- `wire:poll.keep-alive` - Keep connection alive during polling
- `wire:stream` - Alternative for real-time updates (Livewire 3.x)

---

## 💡 ALTERNATIVES TO wire:poll

Jeśli `wire:poll` nie jest odpowiednie dla use case:

### 1. **Livewire Events + Laravel Echo (WebSockets)**

```php
// Backend - dispatch event
broadcast(new JobCompleted($job));

// Frontend - listen
Echo.channel('jobs')
    ->listen('JobCompleted', (e) => {
        $wire.$refresh();
    });
```

**Pros:** Real-time (no polling delay), skalowalne
**Cons:** Wymaga Redis + Laravel Echo Server setup

### 2. **Server-Sent Events (SSE)**

```php
// Controller
public function stream()
{
    return response()->stream(function () {
        while (true) {
            echo "data: " . json_encode(['progress' => $this->getProgress()]) . "\n\n";
            ob_flush();
            flush();
            sleep(3);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
}
```

**Pros:** Native browser support, no external dependencies
**Cons:** Connection management complexity

### 3. **Alpine.js Interval (Client-side)**

```blade
<div x-data="{ progress: null }"
     x-init="setInterval(() => {
         fetch('/api/progress').then(r => r.json()).then(data => progress = data)
     }, 3000)">
    <!-- Display progress -->
</div>
```

**Pros:** Simple, nie wymaga Livewire polling
**Cons:** Łatwo stracić sync między frontend/backend state

---

## ✨ SUMMARY

**GOLDEN RULE:** `wire:poll` element MUSI istnieć w DOM **zawsze**, niezależnie od stanu aplikacji.

**❌ NIGDY:**
```blade
@if($condition)
    <div wire:poll.3s>...</div>
@endif
```

**✅ ZAWSZE:**
```blade
<div wire:poll.3s>
    @if($condition)...</@if>
</div>
```

**IMPACT:** Naruszenie tej zasady prowadzi do **całkowitego braku real-time updates** - użytkownicy NIGDY nie zobaczą dynamic content bez ręcznego F5.

**PRIORITY:** 🔥 CRITICAL - Aplikacje enterprise wymagające real-time updates są bezużyteczne bez działającego `wire:poll`.

---

**Last Updated:** 2025-10-08
**Verified on:** PPM-CC-Laravel - Real-Time Progress Tracking System
**Status:** ✅ RESOLVED - Pattern documented and implemented
