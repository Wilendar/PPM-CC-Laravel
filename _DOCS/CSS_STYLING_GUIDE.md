# CSS & Styling Guide - PPM-CC-Laravel

**Dokument:** Kompleksowy przewodnik stylowania CSS - zakaz inline styles, spójność enterprise
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Zasady Development → CSS & Styling

---

## 🚫 ABSOLUTNY ZAKAZ STYLÓW INLINE

### ⚠️ BEZWZGLĘDNY ZAKAZ używania atrybutu `style=""` w HTML/Blade templates!

---

## ❌ ZABRONIONE PATTERNS

```html
<!-- ❌ WRONG: Inline styles -->
<div style="z-index: 9999; background: #1f2937;">Content</div>
<button style="color: red; margin-top: 10px;">Button</button>
<span style="font-size: 14px; font-weight: bold;">Text</span>

<!-- ❌ WRONG: Inline positioning -->
<div style="position: fixed; top: 0; left: 0;">Header</div>

<!-- ❌ WRONG: Inline colors -->
<div style="background-color: #3b82f6; color: white;">Card</div>

<!-- ❌ WRONG: Inline responsive -->
<div style="width: 100%; max-width: 1200px; margin: 0 auto;">Container</div>
```

---

## ✅ POPRAWNE PATTERNS

### 1. Dedykowane klasy CSS

```css
/* resources/css/components/my-component.css */
.my-component-header {
    z-index: 1;
    background: #1f2937;
}

.btn-danger {
    color: red;
    margin-top: 10px;
}

.text-bold {
    font-size: 14px;
    font-weight: bold;
}
```

```html
<!-- ✅ CORRECT: CSS classes -->
<div class="my-component-header">Content</div>
<button class="btn-danger">Button</button>
<span class="text-bold">Text</span>
```

### 2. Enterprise component classes

```html
<!-- ✅ CORRECT: Enterprise patterns -->
<div class="enterprise-card">
    <div class="card-header">Header</div>
    <div class="card-body">Content</div>
</div>

<button class="btn-enterprise-primary">Save</button>
<button class="btn-enterprise-secondary">Cancel</button>

<div class="tabs-enterprise">
    <button class="tab-item active">Tab 1</button>
    <button class="tab-item">Tab 2</button>
</div>
```

---

## 📋 PROCES TWORZENIA STYLÓW

### Krok 1: Sprawdź istniejące klasy

```bash
# Check PPM Color & Style Guide
cat _DOCS/PPM_Color_Style_Guide.md

# Search for existing classes
grep -r "my-class" resources/css/
```

### Krok 2: Stwórz dedykowany plik CSS (jeśli potrzebny)

```bash
# Create component CSS file
touch resources/css/components/product-form.css
```

```css
/* resources/css/components/product-form.css */

.product-form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.product-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.product-form-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.product-form-actions {
    display: flex;
    gap: 1rem;
}
```

### Krok 3: Dodaj build entry do `vite.config.js`

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/components/product-form.css', // ← Add this
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

### Krok 4: Zbuduj assets

```bash
npm run build
```

### Krok 5: Include w Blade template

```blade
{{-- resources/views/livewire/products/product-form.blade.php --}}

@push('styles')
    @vite('resources/css/components/product-form.css')
@endpush

<div class="product-form-container">
    <div class="product-form-header">
        <h1 class="product-form-title">{{ $title }}</h1>
        <div class="product-form-actions">
            <button class="btn-enterprise-primary">Save</button>
            <button class="btn-enterprise-secondary">Cancel</button>
        </div>
    </div>
    {{-- ... --}}
</div>
```

### Krok 6: Deploy & Verify

```powershell
# Upload CSS
pscp -i $HostidoKey -P 64321 "public/build/assets/*.css" ...

# Clear cache
plink ... "php artisan view:clear && php artisan cache:clear"

# Screenshot verification
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
```

---

## 🎨 ZASADA SPÓJNOŚCI STYLÓW

### ⚠️ WSZYSTKIE panele MUSZĄ używać identycznych:

#### 1. Kolorów (paleta MPP TRADE)

```css
/* _DOCS/PPM_Color_Style_Guide.md */
:root {
    /* Primary colors */
    --color-primary: #3b82f6;
    --color-secondary: #6b7280;
    --color-success: #10b981;
    --color-danger: #ef4444;
    --color-warning: #f59e0b;

    /* Text colors */
    --color-text-primary: #111827;
    --color-text-secondary: #6b7280;
    --color-text-muted: #9ca3af;

    /* Background colors */
    --color-bg-primary: #ffffff;
    --color-bg-secondary: #f9fafb;
    --color-bg-tertiary: #f3f4f6;

    /* Dark mode */
    --color-dark-bg: #1f2937;
    --color-dark-text: #f9fafb;
}
```

#### 2. Komponentów (`.enterprise-*`)

```css
/* Enterprise components */
.enterprise-card { /* ... */ }
.tabs-enterprise { /* ... */ }
.btn-enterprise-primary { /* ... */ }
.btn-enterprise-secondary { /* ... */ }
.btn-enterprise-danger { /* ... */ }
.input-enterprise { /* ... */ }
.select-enterprise { /* ... */ }
```

#### 3. Layoutów (consistent spacing)

```css
/* Spacing scale */
.spacing-xs { padding: 0.5rem; }
.spacing-sm { padding: 1rem; }
.spacing-md { padding: 1.5rem; }
.spacing-lg { padding: 2rem; }
.spacing-xl { padding: 3rem; }

/* Consistent margins */
.mb-standard { margin-bottom: 2rem; }
.mt-standard { margin-top: 2rem; }
```

#### 4. Typografii (Inter font)

```css
/* Typography hierarchy */
.text-h1 {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1.2;
}

.text-h2 {
    font-size: 1.875rem;
    font-weight: 600;
    line-height: 1.3;
}

.text-h3 {
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1.4;
}

.text-body {
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
}
```

#### 5. Animacji (transitions)

```css
/* Standard transitions */
.transition-standard {
    transition: all 0.2s ease-in-out;
}

.transition-fast {
    transition: all 0.1s ease-in-out;
}

.transition-slow {
    transition: all 0.3s ease-in-out;
}

/* Hover effects */
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
```

---

## 🎯 CEL: Zero Visual Differences

**Użytkownik NIE powinien dostrzec różnic wizualnych między różnymi sekcjami aplikacji.**

### Visual Consistency Checklist

- [ ] Header i breadcrumbs identyczne we wszystkich panelach
- [ ] Tabs używają `.tabs-enterprise` (nie custom styles)
- [ ] Przyciski używają `.btn-enterprise-*` (nie inline colors)
- [ ] Karty używają `.enterprise-card` (nie custom shadows)
- [ ] Sidepanel "Szybkie akcje" w identycznym miejscu
- [ ] Dark mode colors zgodne z paletą (nie hardcoded)
- [ ] NO inline styles (`style=""` attributes)
- [ ] Consistent spacing/padding/margins
- [ ] Same typography hierarchy
- [ ] Identical animations/transitions

---

## 📚 REFERENCJA: CategoryForm

**CategoryForm jest WZORCEM dla wszystkich formularzy w aplikacji:**

```
resources/views/livewire/products/categories/category-form.blade.php
```

**Używa:**
- ✅ `.enterprise-card` dla kontenerów
- ✅ `.tabs-enterprise` dla zakładek
- ✅ `.btn-enterprise-primary/secondary` dla przycisków
- ✅ Consistent header structure
- ✅ Sidepanel "Szybkie akcje"
- ✅ NO inline styles
- ✅ Dark mode support

**Wszystkie nowe formularze MUSZĄ naśladować ten pattern!**

---

## 🔧 COMMON USE CASES

### Use Case 1: Modal Z-Index

```css
/* ❌ WRONG: Inline style */
<div style="z-index: 9999;">Modal</div>

/* ✅ CORRECT: CSS class */
/* resources/css/components/modal.css */
.modal-overlay {
    z-index: 999999;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.modal-container {
    z-index: 1000000;
    position: relative;
}
```

### Use Case 2: Responsive Layout

```css
/* ❌ WRONG: Inline responsive */
<div style="width: 100%; padding: 1rem;">
    @media (min-width: 1024px) { width: 1200px; }
</div>

/* ✅ CORRECT: CSS media queries */
/* resources/css/layout.css */
.container-responsive {
    width: 100%;
    padding: 1rem;
}

@media (min-width: 1024px) {
    .container-responsive {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

### Use Case 3: Dynamic Colors

```html
<!-- ❌ WRONG: Inline dynamic color -->
<div style="background: {{ $isActive ? '#10b981' : '#6b7280' }}">
    Status
</div>

<!-- ✅ CORRECT: Conditional CSS classes -->
<div class="{{ $isActive ? 'bg-success' : 'bg-secondary' }}">
    Status
</div>
```

```css
/* resources/css/components/status.css */
.bg-success {
    background-color: var(--color-success);
}

.bg-secondary {
    background-color: var(--color-secondary);
}
```

### Use Case 4: Component-Specific Styles

```html
<!-- ❌ WRONG: Inline component styles -->
<livewire:category-picker
    style="max-height: 400px; overflow-y: auto;"
/>

<!-- ✅ CORRECT: CSS class -->
<livewire:category-picker class="category-picker-scrollable" />
```

```css
/* resources/css/components/category-picker.css */
.category-picker-scrollable {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
}
```

---

## 🚨 CODE REVIEW RED FLAGS

### Podczas code review sprawdź:

- [ ] ❌ Obecność `style=""` attribute w HTML/Blade
- [ ] ❌ Hardcoded colors (#3b82f6 zamiast var(--color-primary))
- [ ] ❌ Hardcoded spacing (margin: 10px zamiast .spacing-sm)
- [ ] ❌ Custom shadows (zamiast .shadow-enterprise)
- [ ] ❌ Custom transitions (zamiast .transition-standard)
- [ ] ❌ Inconsistent typography (font-size: 18px zamiast .text-h3)

### Jeśli wykryto violations:

1. **Reject PR** z komentarzem: "Inline styles detected - must use CSS classes"
2. **Zaproponuj poprawkę:** "Use `.btn-enterprise-primary` instead of inline styles"
3. **Link do dokumentacji:** "See _DOCS/CSS_STYLING_GUIDE.md"

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Główne zasady CSS & Styling
- **_DOCS/PPM_Color_Style_Guide.md** - Paleta kolorów MPP TRADE
- **_DOCS/FRONTEND_VERIFICATION_GUIDE.md** - Weryfikacja UI po zmianach
- **_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md** - Z-index problems

---

## 🧪 TESTING CHECKLIST

Po wprowadzeniu nowych stylów:

- [ ] Build assets lokalnie (`npm run build`)
- [ ] Sprawdź brak `style=""` w HTML
- [ ] Verify colors z palety MPP TRADE
- [ ] Test dark mode (jeśli applicable)
- [ ] Screenshot verification różne viewports
- [ ] Cross-browser testing (Chrome/Firefox/Edge)
- [ ] Deploy & production verification

---

**PAMIĘTAJ:** `style=""` attribute jest ABSOLUTNIE ZABRONIONY! Zawsze używaj dedykowanych klas CSS!
