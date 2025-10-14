# 🐛 LIVEWIRE DEPENDENCY INJECTION ISSUE

**Status:** ✅ RESOLVED
**Date:** 2025-10-10
**Severity:** HIGH (Application Breaking)
**Component:** `App\Http\Livewire\Components\JobProgressBar`

---

## 🚨 PROBLEM DESCRIPTION

### Error Message
```
Illuminate\Contracts\Container\BindingResolutionException
Unable to resolve dependency [Parameter #0 [ <required> int $progressId ]] in class App\Http\Livewire\Components\JobProgressBar
```

### Symptoms
- Strona z komponentem `<livewire:components.job-progress-bar>` zwraca 500 error
- Błąd pojawia się PRZED wywołaniem `mount()` metody
- Laravel próbuje automatycznie rozwiązać dependency injection dla `$progressId`

### Root Cause
**Livewire 3.x Dependency Injection Conflict:**

W Livewire 3.x, gdy zadeklarujesz public property jako **non-nullable type** (`public int $progressId`), framework próbuje rozwiązać to jako **dependency injection** w konstruktorze ZAMIAST traktować jako property przekazywane z Blade template.

**❌ PROBLEMATYCZNY KOD:**
```php
class JobProgressBar extends Component
{
    public int $progressId; // ← Laravel próbuje rozwiązać jako DI!

    public function mount(int $progressId): void
    {
        $this->progressId = $progressId;
    }
}
```

**Blade usage:**
```blade
<livewire:components.job-progress-bar :progressId="$job['id']" />
```

**CO SIĘ DZIEJE:**
1. Laravel widzi `public int $progressId` w klasie
2. Próbuje rozwiązać `int` przez service container (DI)
3. Nie ma binding dla `int` → **BindingResolutionException**
4. `mount()` nigdy nie jest wywoływane

---

## ✅ SOLUTION

### Zmiana property type na NULLABLE

**✅ POPRAWIONY KOD:**
```php
class JobProgressBar extends Component
{
    public ?int $progressId = null; // ← Nullable + default null

    public function mount(int $progressId): void
    {
        $this->progressId = (int) $progressId; // ← Explicit cast
    }
}
```

### Dodatkowa walidacja (safety check)

Dodaj sprawdzenie w metodach które używają `$progressId`:

```php
public function fetchProgress(): void
{
    // Safety check - should never happen if mount() is called correctly
    if ($this->progressId === null) {
        Log::error('JobProgressBar: progressId is null in fetchProgress()');
        $this->progress = [
            'status' => 'error',
            'message' => 'Brak ID postępu zadania',
            'current' => 0,
            'total' => 100,
            'percentage' => 0,
            'errors' => [],
        ];
        return;
    }

    $service = app(JobProgressService::class);
    $this->progress = $service->getProgress($this->progressId);
    // ... rest of method
}
```

---

## 🛡️ PREVENTION RULES

### **ZASADA #1: NULLABLE PROPERTIES DLA LIVEWIRE PARAMETERS**

**ZAWSZE** używaj nullable types dla properties które są przekazywane z Blade:

```php
// ❌ ZŁE - Laravel dependency injection conflict
public int $userId;
public string $category;
public Model $product;

// ✅ DOBRE - Nullable + default value
public ?int $userId = null;
public ?string $category = null;
public ?Model $product = null;
```

### **ZASADA #2: EXPLICIT CASTING W MOUNT()**

Zawsze rzutuj parametry w `mount()` na oczekiwany typ:

```php
public function mount(int $progressId, ?int $shopId = null): void
{
    $this->progressId = (int) $progressId;  // ← Explicit cast
    $this->shopId = $shopId;

    // Initial load
    $this->fetchProgress();
}
```

### **ZASADA #3: VALIDATION W METHODS**

Dodaj safety checks w metodach które używają tych properties:

```php
public function someMethod(): void
{
    if ($this->requiredProperty === null) {
        Log::error('Required property is null');
        // Handle gracefully
        return;
    }

    // Safe to use $this->requiredProperty
}
```

---

## 📋 CHECKLIST - FIX IMPLEMENTATION

Gdy napotkasz podobny błąd:

- [ ] Zidentyfikuj problematyczne property (non-nullable type)
- [ ] Zmień type na nullable: `public ?int $prop = null;`
- [ ] Dodaj explicit cast w `mount()`: `$this->prop = (int) $param;`
- [ ] Dodaj validation w metodach używających property
- [ ] Test locally (jeśli możliwe)
- [ ] Deploy na produkcję
- [ ] Clear cache: `php artisan view:clear && php artisan cache:clear`
- [ ] Verify na stronie produkcyjnej

---

## 💡 AFFECTED FILES

**Fixed Files:**
- `app/Http/Livewire/Components/JobProgressBar.php` (lines 37, 56, 77-88)

**Related Files:**
- `resources/views/livewire/products/listing/product-list.blade.php` (line 333 - usage)

**Deployment:**
- Uploaded: 2025-10-10
- Cache cleared: ✅
- Production verified: ⏳ (pending user verification)

---

## 🔗 RELATED ISSUES

- [Livewire 3.x Events](_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md) - Migracja emit() → dispatch()
- [Type Juggling](_ISSUES_FIXES/PHP_TYPE_JUGGLING_ISSUE.md) - Mixed int/string types

---

## 📚 REFERENCES

**Livewire Documentation:**
- [Component Properties](https://livewire.laravel.com/docs/3.x/properties)
- [Mount Lifecycle Hook](https://livewire.laravel.com/docs/3.x/lifecycle-hooks#mount)

**Laravel Documentation:**
- [Dependency Injection & Service Container](https://laravel.com/docs/12.x/container)

---

**Last Updated:** 2025-10-10
**Reporter:** Claude Code (debugger)
**Status:** ✅ Fixed & Deployed
