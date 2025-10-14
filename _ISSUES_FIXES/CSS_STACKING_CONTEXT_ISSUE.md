# CSS STACKING CONTEXT - Admin Panel Dropdown Issues

**Status**: ⚠️ ONGOING - Zasady do przestrzegania przy każdym dropdown/modal
**Priorytet**: WYSOKIE - wpływa na UX admin panelu
**Typ**: CSS/UI Issue

## 🚨 OPIS PROBLEMU

Dropdown z header chowa się pod inne komponenty strony z powodu nieprawidłowej hierarchii CSS z-index i stacking context.

### Objawy problemu
- ❌ Dropdown menu chowa się pod content
- ❌ Modal overlays nie pokrywają wszystkich elementów
- ❌ Tooltip appears behind other elements
- ❌ Notification popups są niewidoczne

### Przyczyna - CSS Stacking Context
**Root cause**: Dzieci elementu nie mogą mieć wyższego z-index niż najbliższy positioned ancestor.

```css
/* ❌ PROBLEM - niski z-index w parent */
.admin-header {
    z-index: 10; /* za niski! */
    position: relative;
}

.dropdown-menu {
    z-index: 9999; /* nie pomoże! */
    position: absolute;
}
```

## ✅ ROZWIĄZANIE - HIERARCHIA Z-INDEX

### Poprawna hierarchia z-index w admin panelu
```css
/* ✅ DOBRZE - header ma najwyższy z-index */
.admin-header {
    z-index: 100 !important; /* Wyższy niż modals (z-50), sidebars (z-30) */
    position: relative;
    overflow: visible; /* KRYTYCZNE! */
}

/* Dopiero wtedy dropdown może być nad wszystkim */
.dropdown-menu {
    z-index: 9999 !important;
    position: absolute;
}
```

### Standardowa hierarchia z-index
```css
/* Background elements */
.page-background { z-index: 0; }
.content-sections { z-index: 1-10; }

/* Page content headers (KOMPONENTY LIVEWIRE) */
.livewire-component-header { z-index: 1-5; }

/* Navigation */
.sidebar { z-index: 30; }

/* Modals/Overlays */
.modal-overlay { z-index: 50; }
.toast-notifications { z-index: 60; }

/* ADMIN LAYOUT HEADER */
.admin-header { z-index: 100; }

/* Dropdowns (NAJWYŻSZY) */
.dropdown-menu { z-index: 9999; }
```

## 🛡️ KRYTYCZNE ZASADY

1. **NIGDY** nie używaj z-index > 100 w komponentach Livewire
2. **SPRAWDZAJ** inline style="z-index:" - ma priorytet nad CSS class
3. **TESTUJ** dropdown i modale po każdej zmianie z-index
4. **DOKUMENTUJ** każdą zmianę z-index w komentarzu CSS

### Częste błędy
```css
/* ❌ ŹLE - hardcoded wysoki z-index w komponencie */
<div style="z-index: 10000;">

/* ❌ ŹLE - inline style override */
<div class="dropdown" style="z-index: 5;">

/* ✅ DOBRZE - niski z-index dla content */
<div style="z-index: 1;">

/* ✅ DOBRZE - CSS class bez inline override */
<div class="dropdown-menu">
```

## 🔍 DEBUGOWANIE PROBLEMÓW Z Z-INDEX

### Krok po kroku diagnostyka
1. **Developer Tools** → **Elements** → znajdź dropdown element
2. **Sprawdź Computed styles** → szukaj `z-index`
3. **Znajdź parent z positioned** (relative/absolute/fixed)
4. **Porównaj z-index parent vs dropdown**
5. **Wyszukaj w kodzie** inne elementy z wysokim z-index

### Komendy diagnostyczne
```bash
# Znajdź wszystkie z-index w plikach blade
grep -r "z-index" resources/views/
grep -r "z-\[" resources/views/

# Sprawdź konkretny plik na serwerze
head -n 50 domains/ppm.mpptrade.pl/public_html/resources/views/livewire/admin/shops/shop-manager.blade.php | grep -i "z-index"

# PowerShell search
Get-ChildItem -Path "resources\views" -Recurse -Include "*.blade.php" | Select-String -Pattern "z-index|z-\["
```

### Browser DevTools Debug
```javascript
// Console command - znajdź wszystkie elementy z z-index
Array.from(document.querySelectorAll('*'))
  .filter(el => getComputedStyle(el).zIndex !== 'auto')
  .map(el => ({
    element: el,
    zIndex: getComputedStyle(el).zIndex,
    position: getComputedStyle(el).position
  }))
  .sort((a, b) => parseInt(b.zIndex) - parseInt(a.zIndex));
```

## 🛠️ IMPLEMENTACJA W PROJEKCIE

### Admin Layout Header
```blade
{{-- resources/views/layouts/admin.blade.php --}}
<header class="admin-header" style="z-index: 100; position: relative; overflow: visible;">
    {{-- Header content --}}

    {{-- Dropdown example --}}
    <div class="relative">
        <button class="dropdown-toggle">Menu</button>
        <div class="dropdown-menu absolute" style="z-index: 9999;">
            {{-- Dropdown items --}}
        </div>
    </div>
</header>
```

### Livewire Component Headers
```blade
{{-- resources/views/livewire/admin/shops/shop-manager.blade.php --}}
<div class="component-header" style="z-index: 5; position: relative;">
    {{-- Component header - niski z-index --}}
</div>

<div class="component-content">
    {{-- Main content bez z-index conflicts --}}
</div>
```

### Modal Components
```blade
{{-- Modal overlay --}}
<div class="fixed inset-0 bg-gray-900 bg-opacity-75" style="z-index: 50;">
    {{-- Modal content --}}
    <div class="modal-content bg-white rounded-lg" style="z-index: 51;">
        {{-- Content --}}
    </div>
</div>
```

## 📋 CHECKLIST NOWEGO DROPDOWN/MODAL

- [ ] Czy parent element ma wystarczająco wysoki z-index?
- [ ] Czy nie ma inline style="z-index:" w HTML?
- [ ] Czy overflow: visible jest ustawione w parent?
- [ ] Czy dropdown/modal ma najwyższy z-index (9999)?
- [ ] Czy przetestowano na różnych rozmiarach ekranu?
- [ ] Czy nie konfliktuje z innymi dropdown/modal?
- [ ] Czy działa we wszystkich przeglądarkach?

## 💡 EXAMPLE FIXES W PROJEKCIE

### ShopManager Dropdown Fix
```blade
{{-- PRZED - dropdown chował się --}}
<div class="shop-manager" style="z-index: 50;">
    <div class="dropdown-menu" style="z-index: 999;">
        {{-- nie działało --}}
    </div>
</div>

{{-- PO - dropdown widoczny --}}
<div class="shop-manager" style="z-index: 5;">
    <div class="dropdown-menu" style="z-index: 9999;">
        {{-- działa! --}}
    </div>
</div>
```

### Admin Header Fix
```css
/* PRZED */
.admin-header {
    z-index: 20; /* za niski */
    overflow: hidden; /* ukrywał dropdown */
}

/* PO */
.admin-header {
    z-index: 100 !important; /* wystarczająco wysoki */
    overflow: visible; /* pozwala na dropdown */
    position: relative;
}
```

## 🔧 TOOLS & UTILITIES

### CSS Utility Classes
```css
/* Utility classes dla z-index */
.z-dropdown { z-index: 9999 !important; }
.z-modal { z-index: 50 !important; }
.z-header { z-index: 100 !important; }
.z-content { z-index: 5 !important; }
.z-background { z-index: 1 !important; }

/* Position utilities */
.overflow-visible { overflow: visible !important; }
.relative { position: relative !important; }
.absolute { position: absolute !important; }
```

### Tailwind CSS Classes
```html
<!-- Używanie Tailwind z-index classes -->
<div class="z-50">Modal overlay</div>
<div class="z-[9999]">Dropdown menu</div>
<div class="z-10">Content header</div>
<div class="z-0">Background</div>
```

## 🎯 PREVENTION STRATEGIES

### Code Review Checklist
- [ ] Nowe dropdown/modal components mają prawidłowy z-index
- [ ] Brak wysokich z-index w componentach Livewire
- [ ] Parent elements mają overflow: visible
- [ ] Inline styles z z-index są udokumentowane

### Development Guidelines
1. **Nie używaj z-index > 100** w componentach
2. **Testuj dropdown** na każdej nowej stronie
3. **Dokumentuj zmiany** z-index w commit message
4. **Używaj CSS variables** dla standardowych z-index values

### CSS Variables Approach
```css
:root {
    --z-dropdown: 9999;
    --z-modal: 50;
    --z-header: 100;
    --z-content: 5;
    --z-background: 1;
}

.dropdown-menu { z-index: var(--z-dropdown); }
.modal-overlay { z-index: var(--z-modal); }
.admin-header { z-index: var(--z-header); }
```

## 🔗 POWIĄZANE KOMPONENTY

**Wymagające sprawdzenia:**
- `resources/views/layouts/admin.blade.php` - main header
- `resources/views/livewire/admin/shops/shop-manager.blade.php` - dropdown actions
- `resources/views/livewire/admin/products/product-form.blade.php` - modal forms
- `resources/views/components/` - reusable dropdown components

**Priorytetowe fixes:**
1. Admin header dropdown visibility
2. Product form modal overlays
3. Shop management action menus
4. Notification toast positioning