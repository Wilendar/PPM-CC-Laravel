# Dark Mode Color Mapping Reference

## Problem: `dark:` Prefix w Admin Pages

Strony admin w PPM NIE uzywaja systemu Tailwind `dark:` mode.
Brak klasy `dark` na body/html oznacza, ze WSZYSTKIE klasy `dark:*` sa ignorowane.

---

## Kompletne Mapowanie Kolorow

### Text Colors

| Light Mode Pattern | Dark: Prefix Pattern | POPRAWNE (Direct Dark) |
|-------------------|---------------------|------------------------|
| `text-gray-500` | `text-gray-500 dark:text-gray-400` | `text-gray-400` |
| `text-gray-600` | `text-gray-600 dark:text-gray-400` | `text-gray-400` |
| `text-gray-700` | `text-gray-700 dark:text-gray-300` | `text-gray-300` |
| `text-gray-900` | `text-gray-900 dark:text-gray-100` | `text-gray-100` |
| `text-green-600` | `text-green-600 dark:text-green-400` | `text-green-400` |
| `text-red-600` | `text-red-600 dark:text-red-400` | `text-red-400` |
| `text-yellow-600` | `text-yellow-600 dark:text-yellow-400` | `text-yellow-400` |
| `text-blue-600` | `text-blue-600 dark:text-blue-400` | `text-blue-400` |

### Background Colors

| Light Mode Pattern | Dark: Prefix Pattern | POPRAWNE (Direct Dark) |
|-------------------|---------------------|------------------------|
| `bg-white` | `bg-white dark:bg-gray-800` | `bg-gray-800` |
| `bg-gray-50` | `bg-gray-50 dark:bg-gray-700` | `bg-gray-700` |
| `bg-gray-100` | `bg-gray-100 dark:bg-gray-700` | `bg-gray-700` |
| `bg-gray-200` | `bg-gray-200 dark:bg-gray-600` | `bg-gray-600` |

### Border Colors

| Light Mode Pattern | Dark: Prefix Pattern | POPRAWNE (Direct Dark) |
|-------------------|---------------------|------------------------|
| `border-gray-200` | `border-gray-200 dark:border-gray-600` | `border-gray-600` |
| `border-gray-300` | `border-gray-300 dark:border-gray-600` | `border-gray-600` |

### Divide Colors

| Light Mode Pattern | Dark: Prefix Pattern | POPRAWNE (Direct Dark) |
|-------------------|---------------------|------------------------|
| `divide-gray-200` | `divide-gray-200 dark:divide-gray-600` | `divide-gray-600` |
| `divide-gray-300` | `divide-gray-300 dark:divide-gray-700` | `divide-gray-700` |

### Hover States

| Light Mode Pattern | Dark: Prefix Pattern | POPRAWNE (Direct Dark) |
|-------------------|---------------------|------------------------|
| `hover:bg-gray-50` | `hover:bg-gray-50 dark:hover:bg-gray-700` | `hover:bg-gray-700` |
| `hover:bg-gray-100` | `hover:bg-gray-100 dark:hover:bg-gray-700` | `hover:bg-gray-700` |
| `hover:text-gray-700` | `hover:text-gray-700 dark:hover:text-gray-100` | `hover:text-gray-100` |
| `hover:text-gray-900` | `hover:text-gray-900 dark:hover:text-white` | `hover:text-white` |

---

## Status Badges - Kompletne Mapowanie

### Success/Active Status

```html
<!-- BLEDNE -->
<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
    Aktywny
</span>

<!-- POPRAWNE - Opcja 1 (prostsza) -->
<span class="bg-green-900/50 text-green-300">
    Aktywny
</span>

<!-- POPRAWNE - Opcja 2 (z border, bardziej widoczna) -->
<span class="bg-emerald-900/50 text-emerald-300 border border-emerald-700">
    Aktywny
</span>
```

### Error/Inactive Status

```html
<!-- BLEDNE -->
<span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
    Nieaktywny
</span>

<!-- POPRAWNE -->
<span class="bg-red-900/50 text-red-300">
    Nieaktywny
</span>
```

### Warning Status

```html
<!-- BLEDNE -->
<span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
    Ostrzezenie
</span>

<!-- POPRAWNE -->
<span class="bg-yellow-900/30 text-yellow-400">
    Ostrzezenie
</span>
```

### Info Status

```html
<!-- BLEDNE -->
<span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
    Informacja
</span>

<!-- POPRAWNE -->
<span class="bg-blue-900/50 text-blue-300">
    Informacja
</span>
```

---

## Dynamiczne Klasy PHP/Blade

### Problem z PHP Variables

```php
// BLEDNE - dark: prefix nie zadziala
@php
$statusClass = $isActive
    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
@endphp
<span class="{{ $statusClass }}">Status</span>

// POPRAWNE
@php
$statusClass = $isActive
    ? 'bg-green-900/50 text-green-300'
    : 'bg-red-900/50 text-red-300';
@endphp
<span class="{{ $statusClass }}">Status</span>
```

### Dynamiczne Kolory z Zmiennej

```php
// BLEDNE
<div class="bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 text-{{ $color }}-800 dark:text-{{ $color }}-200">

// POPRAWNE
<div class="bg-{{ $color }}-900/30 text-{{ $color }}-300">
```

---

## Naprawione Pliki (Reference)

Pliki naprawione podczas sesji (2026-01-23):

| Plik | Liczba Poprawek | Glowne Problemy |
|------|-----------------|-----------------|
| `user-list.blade.php` | 46 | text-gray-*, bg-gray-*, hover states, badges |
| `role-list.blade.php` | 15 | text-gray-*, bg-gray-*, borders |
| `permission-matrix.blade.php` | 9 | text-gray-*, status badges |
| `audit-logs.blade.php` | 50 | text-gray-*, bg-gray-*, badges, dividers |

---

## Quick Fix Script (Grep + Sed)

```bash
# Znajdz wszystkie uzycia dark: prefix w admin views
grep -r "dark:" resources/views/livewire/admin/ --include="*.blade.php"

# Typowe zamiany (wykonaj recznie lub sed):
# text-gray-500 dark:text-gray-400 -> text-gray-400
# text-gray-600 dark:text-gray-400 -> text-gray-400
# text-gray-900 dark:text-gray-100 -> text-gray-100
# bg-gray-50 dark:bg-gray-700 -> bg-gray-700
# bg-gray-100 dark:bg-gray-700 -> bg-gray-700
# border-gray-200 dark:border-gray-600 -> border-gray-600
```

---

## Checklist Przed Deploy

- [ ] Brak `dark:` prefix w kodzie (grep sprawdzenie)
- [ ] Wszystkie text-gray-* to 100-400 (jasne)
- [ ] Wszystkie bg-gray-* to 700-900 (ciemne)
- [ ] Status badges uzywaja dark mode patterns
- [ ] Dynamiczne klasy PHP nie zawieraja dark:
- [ ] Hover states sa ciemne (hover:bg-gray-700)
