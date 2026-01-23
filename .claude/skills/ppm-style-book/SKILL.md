---
name: ppm-style-book
description: Complete PPM styling reference - CSS tokens, enterprise components, dark mode requirements, and anti-patterns to avoid. Use when creating/modifying UI components in admin pages.
version: 1.2.0
author: Kamil Wilinski
created: 2026-01-23
updated: 2026-01-23
tags: [styling, css, ui, frontend, blade, components, dark-mode, admin]
---

# PPM Style Book - Complete Styling Reference

## 🎯 Overview

Ten skill zawiera kompletne zasady stylowania dla projektu PPM-CC-Laravel.
Automatyzuje wykrywanie i naprawianie problemów z CSS/UI w admin pages.

---

## 🚀 Kiedy używać tego Skilla

Użyj tego skilla gdy:

- **Tworzysz** nowy komponent UI w admin panel
- **Modyfikujesz** istniejące blade templates (`.blade.php`)
- **Naprawiasz** problemy ze stylowaniem (CSS, kolory, przyciski)
- **Dodajesz** nowe style CSS
- **Konwertujesz** light mode na dark mode
- **Usuwasz** inline styles lub `dark:` prefix problemy

**Trigger files:**
- `resources/views/livewire/admin/**/*.blade.php`
- `resources/css/**/*.css`
- Dowolny plik blade z problemami stylowania

---

## 📋 Instrukcje Główne

### FUNDAMENTALNA ZASADA

```
ZERO TOLERANCE dla:
- style="..." w Blade templates
- Hardcoded hex values (#e0ac7e) w Blade
- Tailwind arbitrary values (z-[9999], bg-[#color])
- onmouseover/onmouseout JavaScript w HTML
- dark: prefix w admin pages (NIE DZIAŁA!)
```

---

## CSS CUSTOM PROPERTIES (TOKENS)

### Brand Colors
| Token | Value | Usage |
|-------|-------|-------|
| `--mpp-primary` | `#e0ac7e` | CTA, active links, highlights, brand accent |
| `--mpp-primary-dark` | `#d1975a` | Hover states for brand elements |
| `--mpp-primary-rgb` | `224, 172, 126` | For rgba() transparency |
| `--ppm-primary` | `#2563eb` | System actions (sync, save) |
| `--ppm-primary-dark` | `#1d4ed8` | Hover for system actions |
| `--ppm-secondary` | `#059669` | Success states, "online" |
| `--ppm-secondary-dark` | `#047857` | Hover for success |
| `--ppm-accent` | `#dc2626` | Errors, destructive actions |
| `--ppm-accent-dark` | `#b91c1c` | Hover for destructive |

### Background Colors
| Token | Usage |
|-------|-------|
| `--bg-card` | Card/panel backgrounds |
| `--bg-card-hover` | Card hover state |
| `--bg-nav` | Sidebar/navigation |

### Text Colors
| Token | Usage |
|-------|-------|
| `--text-primary` | Main text (white on dark) |
| `--text-secondary` | Secondary text (gray-300) |
| `--text-muted` | Muted/disabled text (gray-500) |

### Usage
```css
/* CORRECT */
.my-element {
    color: var(--mpp-primary);
    background: rgba(var(--mpp-primary-rgb), 0.18);
}

/* WRONG - hardcoded */
.my-element {
    color: #e0ac7e;
}
```

---

## ENTERPRISE COMPONENT CLASSES

### Buttons - MANDATORY
```html
<!-- Primary CTA (orange gradient) -->
<button class="btn-enterprise-primary">Save Changes</button>

<!-- Secondary (neutral dark + border) -->
<button class="btn-enterprise-secondary">Cancel</button>

<!-- Danger/Destructive -->
<button class="btn-enterprise-danger">Delete</button>

<!-- Success -->
<button class="btn-enterprise-success">Confirm</button>

<!-- Ghost (transparent) -->
<button class="btn-enterprise-ghost">Cancel</button>

<!-- Small variant -->
<button class="btn-enterprise-sm">Small Action</button>
```

### FORBIDDEN Button Patterns
```html
<!-- WRONG: undefined classes -->
<button class="btn btn-primary">NEVER</button>
<button class="btn-success">NEVER</button>

<!-- WRONG: inline styles -->
<button style="background: linear-gradient(...);">NEVER</button>
```

### Cards and Panels
```html
<div class="enterprise-card">Content</div>
<div class="enterprise-card-warning">Warning content</div>
<div class="enterprise-card-success">Success content</div>
```

### Badges and Status
```html
<span class="badge-enterprise">Default</span>
<span class="badge-enterprise--warning">Warning</span>
<span class="badge-enterprise--success">Success</span>
```

---

## FORM INPUTS

```html
<input type="text" class="form-input-enterprise" placeholder="Enter value...">
<input type="checkbox" class="checkbox-enterprise">
<select class="form-input-enterprise">
    <option>Option 1</option>
</select>
```

### FORBIDDEN
```html
<!-- WRONG: inline accent-color -->
<input type="checkbox" style="accent-color: #e0ac7e;">
```

---

## DARK MODE REQUIREMENTS (CRITICAL!)

### PROBLEM: `dark:` PREFIX NIE DZIALA W ADMIN PAGES!

**KRYTYCZNE:** Strony admin w PPM dzialaja w stalym dark mode. NIE uzywaja systemu Tailwind `dark:` prefix!

```
PRZYCZYNA: Brak klasy "dark" na body/html element
EFEKT: Wszystkie klasy dark:* sa IGNOROWANE przez przegladarke
```

**ZASADA:** Wszystkie kolory musza byc bezposrednio dla dark mode - NIE uzywaj `dark:` prefix!

### Konwersja dark: Prefix (MANDATORY)

| BLEDNE (z dark:) | POPRAWNE (bezposrednie) |
|------------------|-------------------------|
| `text-gray-500 dark:text-gray-400` | `text-gray-400` |
| `text-gray-600 dark:text-gray-400` | `text-gray-400` |
| `text-gray-900 dark:text-gray-100` | `text-gray-100` |
| `text-green-600 dark:text-green-400` | `text-green-400` |
| `bg-gray-50 dark:bg-gray-700` | `bg-gray-700` |
| `bg-gray-100 dark:bg-gray-700` | `bg-gray-700` |
| `bg-white dark:bg-gray-800` | `bg-gray-800` |
| `border-gray-200 dark:border-gray-600` | `border-gray-600` |
| `divide-gray-200 dark:divide-gray-600` | `divide-gray-600` |
| `hover:text-gray-700 dark:hover:text-gray-100` | `hover:text-gray-100` |

### Status Badges Konwersja

| BLEDNE | POPRAWNE |
|--------|----------|
| `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200` | `bg-green-900/50 text-green-300` lub `bg-emerald-900/50 text-emerald-300 border border-emerald-700` |
| `bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200` | `bg-red-900/50 text-red-300` |
| `bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200` | `bg-yellow-900/30 text-yellow-400` |
| `bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200` | `bg-blue-900/50 text-blue-300` |

### Dynamiczne Klasy Blade (PHP Variables)

```php
// BLEDNE - dark: prefix z PHP variable
bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30

// POPRAWNE - tylko dark mode
bg-{{ $color }}-900/30
```

### Background Colors
```html
<!-- CORRECT: Dark mode backgrounds -->
<div class="bg-gray-900">Page background</div>
<div class="bg-gray-800">Card/section background</div>
<div class="bg-gray-700">Input/nested element background</div>

<!-- WRONG: Light mode backgrounds - NEVER on admin pages! -->
<div class="bg-white">FORBIDDEN</div>
<div class="bg-gray-100">FORBIDDEN</div>
<div class="bg-gray-50">FORBIDDEN</div>
```

### Border Colors
```html
<!-- CORRECT -->
<div class="border-gray-700">Standard border</div>
<div class="border-gray-600">Input/form border</div>

<!-- WRONG -->
<div class="border-gray-200">FORBIDDEN</div>
<div class="border-gray-300">FORBIDDEN</div>
```

### Text Colors
```html
<!-- CORRECT -->
<p class="text-white">Primary text</p>
<p class="text-gray-300">Secondary text</p>
<p class="text-gray-400">Muted text</p>

<!-- WRONG -->
<p class="text-gray-900">FORBIDDEN</p>
```

### Hover States
```html
<!-- CORRECT -->
<tr class="hover:bg-gray-700">...</tr>

<!-- WRONG -->
<tr class="hover:bg-gray-50">FORBIDDEN</tr>
```

---

## Z-INDEX LAYER SYSTEM

| Class | Z-index | Usage |
|-------|---------|-------|
| `.layer-base` | `1` | Normal content |
| `.layer-panel` | `10` | Floating panels |
| `.layer-modal` | `100` | Modals |
| `.layer-overlay` | `200` | Overlay backgrounds |

```html
<!-- CORRECT -->
<div class="layer-modal">Modal content</div>

<!-- WRONG -->
<div class="z-[9999]">FORBIDDEN</div>
<div style="z-index: 999999;">FORBIDDEN</div>
```

---

## TABLE STYLING

```html
<thead class="bg-gray-700">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">
            Column Name
        </th>
    </tr>
</thead>
<tbody class="bg-gray-800 divide-y divide-gray-700">
    <tr class="hover:bg-gray-700 transition-colors">
        <td class="px-6 py-4 text-white">...</td>
    </tr>
</tbody>
```

---

## CSS FILES REFERENCE

| File | Purpose |
|------|---------|
| `resources/css/admin/components.css` | Admin UI components |
| `resources/css/admin/layout.css` | Layout/grid styles |
| `resources/css/products/category-form.css` | Product form styles |
| `resources/css/products/product-form.css` | ProductForm specific |

### Adding New Styles Workflow
1. Check existing classes in files above
2. Add to existing file if <200 new lines
3. Create new CSS module ONLY if >200 lines
4. Define colors using `var(--token)`
5. Build: `npm run build`
6. Deploy: upload manifest + assets
7. Test: verify with Chrome DevTools

---

## ANTI-PATTERNS CHECKLIST

Before submitting ANY UI changes, verify:

- [ ] Zero `style="..."` in Blade files
- [ ] No hardcoded hex colors (#e0ac7e, etc.)
- [ ] No `z-[9999]` or arbitrary Tailwind values
- [ ] No undefined btn classes (btn-primary, btn-success)
- [ ] No light mode colors (bg-white, border-gray-200)
- [ ] **No `dark:` prefix classes** (they don't work in admin!)
- [ ] Buttons use `.btn-enterprise-*` classes
- [ ] Forms use `.form-input-enterprise`
- [ ] Loading overlays have `wire:target`
- [ ] Chrome DevTools verification completed

### GREP Pattern for Finding dark: Issues
```bash
# Find all dark: prefix usages in admin blade files
grep -r "dark:" resources/views/livewire/admin/
```

---

## 💡 Examples

### Example 1: Converting Inline Styles
```html
<!-- BEFORE (wrong) -->
<button style="background: linear-gradient(135deg, #e0ac7e, #d1975a);">
    Save
</button>

<!-- AFTER (correct) -->
<button class="btn-enterprise-primary">Save</button>
```

### Example 2: Fixing Light Mode
```html
<!-- BEFORE (wrong) -->
<div class="bg-white border-gray-200 text-gray-900">
    Content
</div>

<!-- AFTER (correct) -->
<div class="bg-gray-800 border-gray-700 text-white">
    Content
</div>
```

### Example 3: Fixing Button Classes
```html
<!-- BEFORE (wrong) -->
<button class="btn btn-success">Confirm</button>
<button class="btn btn-secondary">Cancel</button>

<!-- AFTER (correct) -->
<button class="btn-enterprise-success">Confirm</button>
<button class="btn-enterprise-secondary">Cancel</button>
```

### Example 4: Fixing dark: Prefix (CRITICAL!)
```html
<!-- BEFORE (wrong - dark: prefix ignored!) -->
<span class="text-green-600 dark:text-green-400">Online</span>
<div class="bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
    Content
</div>

<!-- AFTER (correct - direct dark mode) -->
<span class="text-green-400">Online</span>
<div class="bg-gray-700 text-gray-100">
    Content
</div>
```

### Example 5: Status Badge Fix
```html
<!-- BEFORE (wrong) -->
<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded">
    Aktywny
</span>

<!-- AFTER (correct) -->
<span class="bg-emerald-900/50 text-emerald-300 border border-emerald-700 px-2 py-1 rounded">
    Aktywny
</span>
```

### Example 6: Table Row Styling Fix
```html
<!-- BEFORE (wrong) -->
<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
    <td class="text-gray-900 dark:text-gray-100">Data</td>
</tr>

<!-- AFTER (correct) -->
<tr class="hover:bg-gray-700">
    <td class="text-gray-100">Data</td>
</tr>
```

---

## 📚 RESOURCES

Szczegolowe mapowanie kolorow: [resources/dark-mode-color-mapping.md](resources/dark-mode-color-mapping.md)

---

## 📊 SYSTEM UCZENIA SIĘ

### Tracking Informacji
Ten skill automatycznie zbiera nastepujace dane:
- Ilosc naprawionych bledow stylowania
- Typy naprawianych problemow (inline styles, dark: prefix, light mode)
- Pliki najczesciej wymagajace poprawek

### Metryki Sukcesu
- Success rate target: 100% (zero regressions)
- Max review time: 30 sekund per plik
- Zero tolerance: inline styles, hardcoded colors

### Historia Ulepszen
<!-- Automatycznie generowane przy kazdej aktualizacji -->

---

## CHANGELOG

### v1.2.0 (2026-01-23)
- [STRUCTURE] Dodano sekcje "Kiedy uzywac tego Skilla" zgodnie z skill-creator guidelines
- [STRUCTURE] Dodano sekcje "System Uczenia Sie" na koncu
- [FIX] Poprawiono sciezke do resources/
- [IMPROVE] Ulepszono czytelnosc sekcji Overview

### v1.1.0 (2026-01-23)
- [CRITICAL] Dodano sekcje o problemie `dark:` prefix w admin pages
- [ADDED] Tabela konwersji dark: prefix do bezposrednich klas
- [ADDED] Status badges konwersja patterns
- [ADDED] Dynamiczne klasy Blade z PHP variables
- [ADDED] 3 nowe przyklady (4-6) z naprawami dark: prefix
- [ADDED] GREP pattern do znajdowania dark: issues
- Bazowane na sesji naprawiania stron admin (46+ poprawek)

### v1.0.0 (2026-01-23)
- Poczatkowa wersja z podstawowymi zasadami stylowania

---

**Last Updated:** 2026-01-23
**Source:** PPM_Styling_Playbook.md + Production Analysis + Admin Pages Fixing Session
