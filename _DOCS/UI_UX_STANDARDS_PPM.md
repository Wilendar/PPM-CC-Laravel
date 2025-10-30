# PPM-CC-Laravel UI/UX Standards

**Version:** 1.0.0
**Date:** 2025-10-28
**Status:** 🔴 MANDATORY dla wszystkich nowych komponentów

---

## 🚨 CRITICAL ISSUES - FOUND 2025-10-28

Screenshot: System Atrybutów page (`/admin/variants`)

### ❌ PROBLEMY (NIEDOPUSZCZALNE):

1. **BRAK SPACING** - Elementy przyklejone do krawędzi
   - Header "System Atrybutów" bez padding od góry
   - Filtry (Szukaj, Status, Sync) bez marginesów między sobą
   - Karty bez wewnętrznego padding
   - Tekst "Kolor", "Rozmiar" przyklejony do brzegu karty

2. **SŁABE KOLORY** - Mdłe, niewyraziste
   - Przyciski "Edit" (fioletowy) zlewają się z tłem
   - "Values" button (fioletowy) - brak kontrastu
   - Karty koloru tła (#1e293b) zbyt podobne do background (#0f172a)

3. **HOVER TRANSFORMS** - Niszczą profesjonalizm
   - `transform: translate(...)` na hover dużych paneli/kart
   - Powodują "podskakiwanie" całej karty - nieprofesjonalne

4. **BUTTON HIERARCHY** - Brak jasnej hierarchii
   - Wszystkie przyciski tej samej wagi wizualnej
   - Primary action nie wyróżniony

---

## 📏 SPACING SYSTEM (8px Grid)

**MANDATORY:** Wszystkie spacing based on 8px increments (8, 16, 24, 32, 40, 48, 64)

### Container Padding

```css
/* ✅ CORRECT - Generous padding */
.page-header {
    padding: 32px 24px; /* 32px top/bottom, 24px sides */
}

.content-section {
    padding: 24px; /* Wszystkie strony */
}

.card {
    padding: 20px; /* Minimum dla cards */
}

/* ❌ WRONG - Brak padding */
.page-header {
    padding: 8px; /* ZA MAŁO! */
}

.card {
    padding: 0; /* NIEDOPUSZCZALNE! */
}
```

### Element Spacing

```css
/* ✅ CORRECT - Proper margins */
.filter-group {
    display: grid;
    gap: 16px; /* Spacing między filtrami */
}

.card-title {
    margin-bottom: 16px; /* Space after title */
}

.button-group {
    display: flex;
    gap: 12px; /* Space między buttonami */
}

/* ❌ WRONG - Brak spacing */
.filter-group {
    gap: 4px; /* ZA MAŁO! */
}

.card-title {
    margin-bottom: 0; /* BRAK SPACING! */
}
```

### Typography Spacing

```css
/* ✅ CORRECT */
h1, h2, h3 {
    line-height: 1.4; /* Comfortable reading */
    margin-bottom: 16px;
}

p {
    margin-bottom: 12px;
    line-height: 1.6;
}

/* ❌ WRONG */
h1 {
    line-height: 1.0; /* Zbyt ciasno! */
    margin-bottom: 4px;
}
```

---

## 🎨 COLOR PALETTE

**PPM Brand Colors** - High contrast, professional

### Primary Colors

```css
/* ✅ PRIMARY ACTIONS - Orange (Brand) */
--color-primary: #f97316;        /* Orange-500 */
--color-primary-hover: #ea580c;  /* Orange-600 */
--color-primary-light: #fb923c;  /* Orange-400 */

/* ✅ SECONDARY ACTIONS - Blue */
--color-secondary: #3b82f6;      /* Blue-500 */
--color-secondary-hover: #2563eb; /* Blue-600 */

/* ✅ SUCCESS - Green */
--color-success: #10b981;        /* Emerald-500 */
--color-success-hover: #059669;  /* Emerald-600 */

/* ✅ DANGER - Red */
--color-danger: #ef4444;         /* Red-500 */
--color-danger-hover: #dc2626;   /* Red-600 */
```

### Background Colors

```css
/* ✅ CORRECT - Dark theme z kontrastem */
--color-bg-primary: #0f172a;     /* Slate-900 - Main background */
--color-bg-secondary: #1e293b;   /* Slate-800 - Cards/Panels */
--color-bg-tertiary: #334155;    /* Slate-700 - Hover states */

/* ❌ WRONG - Zbyt podobne kolory */
--color-bg-primary: #1a1a1a;
--color-bg-secondary: #1e1e1e;   /* Zbyt mała różnica! */
```

### Text Colors

```css
/* ✅ CORRECT - High contrast */
--color-text-primary: #f8fafc;   /* Slate-50 - Main text */
--color-text-secondary: #cbd5e1; /* Slate-300 - Secondary text */
--color-text-muted: #94a3b8;     /* Slate-400 - Muted text */

/* ❌ WRONG - Low contrast */
--color-text-primary: #888888;   /* Zbyt ciemny na ciemnym tle! */
```

---

## 🔘 BUTTON HIERARCHY

**MANDATORY:** Clear visual hierarchy dla actions

### Primary Buttons (Main Actions)

```css
/* ✅ CORRECT - Orange, wysokiej kontrast */
.btn-primary {
    background: #f97316; /* Orange-500 */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.btn-primary:hover {
    background: #ea580c; /* Orange-600 */
    /* NO TRANSFORM! */
}

/* ❌ WRONG - Fioletowy, niska kontrast */
.btn-primary {
    background: #7c3aed; /* Zlewa się z tłem! */
    color: #aaaaaa;      /* Słabo widoczny tekst! */
}

.btn-primary:hover {
    transform: translateY(-2px); /* ❌ ZABRONIONE! */
}
```

### Secondary Buttons

```css
/* ✅ CORRECT - Border + transparent background */
.btn-secondary {
    background: transparent;
    color: #3b82f6; /* Blue-500 */
    padding: 10px 20px;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    font-weight: 600;
}

.btn-secondary:hover {
    background: rgba(59, 130, 246, 0.1); /* Subtle fill */
    /* NO TRANSFORM! */
}
```

### Danger Buttons

```css
/* ✅ CORRECT - Red, clear intent */
.btn-danger {
    background: #ef4444; /* Red-500 */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-danger:hover {
    background: #dc2626; /* Red-600 */
    /* NO TRANSFORM! */
}
```

---

## 📦 CARD DESIGN

**MANDATORY:** Proper padding, spacing, borders

### Card Structure

```css
/* ✅ CORRECT - Generous padding, proper spacing */
.card {
    background: #1e293b; /* Slate-800 */
    border: 1px solid #334155; /* Slate-700 */
    border-radius: 12px;
    padding: 24px; /* MINIMUM 20px! */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.card-header {
    margin-bottom: 20px; /* Space after header */
    padding-bottom: 16px;
    border-bottom: 1px solid #334155;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #f8fafc;
    margin: 0; /* Header już ma margin-bottom */
}

.card-body {
    /* Content spacing handled by child elements */
}

.card-footer {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #334155;
    display: flex;
    gap: 12px; /* Space między buttonami */
    justify-content: flex-end;
}

/* ❌ WRONG - Brak padding, wszystko ściśnięte */
.card {
    padding: 8px; /* ZA MAŁO! */
}

.card-header {
    margin-bottom: 0; /* BRAK SPACING! */
}

.card-footer {
    gap: 0; /* Buttony przyklejone! */
}
```

### Card Hover (ONLY subtle effects!)

```css
/* ✅ CORRECT - Subtle border/shadow change */
.card:hover {
    border-color: #475569; /* Slate-600 - subtle */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    /* NO TRANSFORM! */
}

/* ❌ WRONG - Transform translate */
.card:hover {
    transform: translateY(-4px); /* ❌ ZABRONIONE! Niszczy profesjonalizm! */
}
```

---

## 🚫 FORBIDDEN: HOVER TRANSFORMS

**⚠️ KATEGORYCZNY ZAKAZ:** `transform: translate(...)` na hover dla dużych elementów (cards, panels, sections)

### WHY FORBIDDEN:

1. **Nieprofesjonalne** - Wygląda jak "zabawka", nie enterprise app
2. **Rozpraszające** - User focus zostaje zakłócony przez ruch
3. **Accessibility** - Problemy dla użytkowników z motion sensitivity
4. **Performance** - Trigger layout reflow

### ❌ EXAMPLES - ZABRONIONE:

```css
/* ❌ FORBIDDEN - Card hover */
.card:hover {
    transform: translateY(-4px);
}

/* ❌ FORBIDDEN - Panel hover */
.panel:hover {
    transform: scale(1.02);
}

/* ❌ FORBIDDEN - Section hover */
.section:hover {
    transform: translateX(5px);
}

/* ❌ FORBIDDEN - List item hover */
.list-item:hover {
    transform: translateY(-2px);
}
```

### ✅ ALLOWED ALTERNATIVES:

```css
/* ✅ ALLOWED - Subtle border/shadow change */
.card:hover {
    border-color: #475569;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}

/* ✅ ALLOWED - Background opacity change */
.list-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* ✅ ALLOWED - Border accent */
.panel:hover {
    border-left: 4px solid #f97316; /* Orange accent */
}
```

### ✅ ONLY EXCEPTION: Small interactive elements

```css
/* ✅ ALLOWED - Small buttons/icons ONLY */
.btn-icon:hover {
    transform: scale(1.1); /* Icons mogą rosnąć */
}

.dropdown-trigger:hover {
    transform: rotate(180deg); /* Ikony dropdown */
}

/* Size limit: <48px width/height */
```

---

## 📐 LAYOUT SPACING

### Page Structure

```css
/* ✅ CORRECT */
.page-container {
    padding: 32px 24px; /* Generous page padding */
}

.page-header {
    margin-bottom: 32px; /* Space after header */
}

.page-content {
    display: grid;
    gap: 24px; /* Space między sekcjami */
}

/* ❌ WRONG */
.page-container {
    padding: 8px; /* ZA MAŁO! */
}

.page-header {
    margin-bottom: 8px; /* ZA MAŁO! */
}
```

### Grid Layouts

```css
/* ✅ CORRECT - Proper gaps */
.grid-2-cols {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px; /* MINIMUM 16px! */
}

.grid-3-cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* ❌ WRONG - Zbyt małe gaps */
.grid-2-cols {
    gap: 8px; /* ZA MAŁO! */
}
```

---

## 🎯 FORM DESIGN

### Input Fields

```css
/* ✅ CORRECT - Proper padding, spacing */
.form-group {
    margin-bottom: 20px; /* Space między polami */
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #cbd5e1; /* Slate-300 */
}

.form-input {
    width: 100%;
    padding: 12px 16px; /* Generous padding */
    background: #1e293b; /* Slate-800 */
    border: 2px solid #334155; /* Slate-700 */
    border-radius: 8px;
    color: #f8fafc;
    font-size: 14px;
}

.form-input:focus {
    border-color: #f97316; /* Orange accent */
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

/* ❌ WRONG */
.form-group {
    margin-bottom: 4px; /* ZA MAŁO! */
}

.form-input {
    padding: 4px 8px; /* ZA CIASNO! */
    border: 1px solid #444; /* Słaby kontrast! */
}
```

### Filter Bars

```css
/* ✅ CORRECT - Proper spacing */
.filter-bar {
    display: flex;
    gap: 16px; /* Space między filtrami */
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.filter-item {
    min-width: 200px; /* Adequate width */
}

/* ❌ WRONG */
.filter-bar {
    gap: 4px; /* ZA MAŁO! */
}

.filter-item {
    min-width: 100px; /* ZA WĄSKO! */
}
```

---

## 📋 IMPLEMENTATION CHECKLIST

### Before Creating New Component:

- [ ] **Spacing:** Min 20px padding dla cards, 16px gap między elementami
- [ ] **Colors:** High contrast (check color palette section)
- [ ] **Buttons:** Clear hierarchy (primary orange, secondary border, danger red)
- [ ] **NO hover transforms** dla cards/panels (ONLY border/shadow changes)
- [ ] **Typography:** Proper line-height (1.4-1.6), margin-bottom (12-16px)
- [ ] **Layout:** Grid gaps min 16px, page padding 24-32px

### Code Review Red Flags:

```css
/* 🚨 RED FLAGS - STOP and FIX! */
transform: translateY(-4px);  /* ❌ FORBIDDEN on cards! */
padding: 8px;                 /* ❌ TOO SMALL! */
gap: 4px;                     /* ❌ TOO SMALL! */
margin-bottom: 0;             /* ❌ NO SPACING! */
background: #7c3aed;          /* ❌ LOW CONTRAST! */
color: #888888;               /* ❌ POOR READABILITY! */
```

---

## 🔍 VERIFICATION

### Visual Check:

1. ✅ **"Air" test:** Czy elementy mają "breathing space"?
2. ✅ **Kontrast test:** Czy wszystkie teksty są czytelne?
3. ✅ **Hover test:** Czy hover NIE powoduje "podskakiwania"?
4. ✅ **Button test:** Czy primary action jest wyraźnie wyróżniony?

### Code Check:

```bash
# Search for forbidden patterns
grep -r "transform.*translate" resources/css/
grep -r "padding: [0-9]px" resources/css/ | grep -v "padding: [12][0-9]px"
grep -r "gap: [0-8]px" resources/css/
```

---

## 📚 REFERENCE - Good Examples in Project

✅ **CategoryForm** (`resources/views/livewire/products/categories/category-form.blade.php`)
- Proper spacing throughout
- Clear button hierarchy
- High contrast colors
- NO hover transforms

✅ **ProductList** (`resources/views/livewire/products/listing/product-list.blade.php`)
- Generous card padding
- Proper grid gaps
- Professional hover effects (border only)

❌ **BAD EXAMPLE: System Atrybutów** (2025-10-28)
- Brak padding
- Słabe kolory
- Transform hover (needs fix!)

---

**LAST UPDATED:** 2025-10-28
**ENFORCED BY:** frontend-specialist, livewire-specialist agents + frontend-verification skill
**COMPLIANCE:** 🔴 MANDATORY dla wszystkich nowych komponentów
