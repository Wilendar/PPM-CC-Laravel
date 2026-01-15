---
name: "ppm-styling-guidelines"
description: "PPM-specific styling standards for enterprise-grade UI consistency. Color palettes, component naming, z-index layer system."
---

# PPM Styling Guidelines

**Type:** domain
**Enforcement:** require
**Priority:** critical
**Version:** 1.1.0
**Last Updated:** 2026-01-08

---

## Quick Reference

This skill enforces **PPM-specific styling standards** for enterprise-grade UI consistency. It complements `frontend-dev-guidelines` by adding project-specific color palettes, component naming conventions, and workflow automation.

**CRITICAL RULES:**
- ❌ **ZAKAZ:** Inline styles (`style="..."`) - ZERO tolerance
- ❌ **ZAKAZ:** Arbitrary Tailwind values (`class="z-[9999]"`)
- ❌ **ZAKAZ:** Hardcoded colors (use CSS Custom Properties)
- ✅ **WYMAGANE:** PPM color tokens (`--mpp-primary`, `--ppm-primary`, etc.)
- ✅ **WYMAGANE:** Enterprise component classes (`.btn-enterprise-*`, `.enterprise-card`)
- ✅ **WYMAGANE:** Layer system for z-index (`.layer-*`)

---

## When to Use This Skill

**Auto-triggers on:**
- Keywords: `style`, `styling`, `css`, `color`, `button`, `badge`, `card`, `modal`, `z-index`, `layout`
- File edits: `resources/css/**/*.css`, `resources/views/**/*.blade.php`
- Code patterns: `style="`, `background:`, `color:`, `z-index:`

**Manually invoke when:**
- Creating new UI components for PPM
- Styling product forms, category pickers, shop management UI
- Working with PPM-specific color schemes (MPP Orange brand)
- Implementing status badges, sync indicators, progress bars
- Debugging z-index conflicts (modals, dropdowns, overlays)

---

## Core Principles

### 1. PPM Color Palette (CSS Custom Properties)

**RULE:** ALL colors MUST use CSS Custom Properties defined in design system.

**Brand Colors:**
```css
/* resources/css/variables.css or tailwind.config.js */
:root {
    /* MPP Orange Brand (Primary CTA) */
    --mpp-primary: #e0ac7e;
    --mpp-primary-dark: #d1975a;
    --mpp-primary-rgb: 224, 172, 126; /* For rgba() usage */

    /* PPM Blue (System Actions - Sync) */
    --ppm-primary: #2563eb;
    --ppm-primary-dark: #1d4ed8;

    /* PPM Green (Success, Online Status) */
    --ppm-secondary: #059669;
    --ppm-secondary-dark: #047857;

    /* PPM Red (Errors, Destructive Actions) */
    --ppm-accent: #dc2626;
    --ppm-accent-dark: #b91c1c;

    /* Backgrounds */
    --bg-card: #1e293b;           /* Slate-800 */
    --bg-card-hover: #334155;     /* Slate-700 */
    --bg-nav: #0f172a;            /* Slate-900 */

    /* Text Hierarchy */
    --text-primary: #f8fafc;      /* Slate-50 */
    --text-secondary: #cbd5e1;    /* Slate-300 */
    --text-muted: #94a3b8;        /* Slate-400 */
    --text-disabled: #64748b;     /* Slate-500 */
}
```

**Usage:**
```css
/* ✅ CORRECT */
.btn-enterprise-primary {
    background: linear-gradient(135deg, var(--mpp-primary) 0%, var(--mpp-primary-dark) 50%, #c08449 100%);
    color: white;
}

.sync-badge {
    background-color: var(--ppm-primary);
    color: var(--text-primary);
}

/* ❌ WRONG - Hardcoded colors */
.button {
    background: #e0ac7e;  /* ❌ Use var(--mpp-primary) */
}
```

**Reference:** See `resources/color-palette.md` for full palette with semantic naming.

---

### 2. Enterprise Component System

**RULE:** Use standardized enterprise component classes for consistency.

#### Buttons

```css
/* resources/css/admin/components.css */

/* Primary CTA - MPP Orange Gradient */
.btn-enterprise-primary {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    background: linear-gradient(135deg, var(--mpp-primary) 0%, var(--mpp-primary-dark) 50%, #c08449 100%);
    color: white;
    border: none;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.btn-enterprise-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.btn-enterprise-primary:hover::before {
    opacity: 1;
}

/* Secondary - Neutral */
.btn-enterprise-secondary {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--text-muted);
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}

.btn-enterprise-secondary:hover {
    background: var(--bg-card-hover);
    border-color: var(--text-secondary);
}

/* Size Variants */
.btn-enterprise-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
}

.btn-enterprise-lg {
    padding: 12px 24px;
    font-size: 1.125rem;
}
```

**Blade Usage:**
```blade
{{-- ✅ CORRECT --}}
<button type="submit" class="btn-enterprise-primary">
    Zapisz produkt
</button>

<button type="button"
        wire:click="cancel"
        class="btn-enterprise-secondary btn-enterprise-sm">
    Anuluj
</button>

{{-- ❌ WRONG --}}
<button style="background: #e0ac7e; padding: 10px;">
    Save
</button>
```

#### Cards & Panels

```css
/* Enterprise Card */
.enterprise-card {
    background: var(--bg-card);
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.enterprise-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}

/* Variant - Warning */
.enterprise-card-warning {
    border-left: 4px solid var(--ppm-accent);
}

/* Variant - Success */
.enterprise-card-success {
    border-left: 4px solid var(--ppm-secondary);
}
```

#### Badge & Status Indicators

```css
/* Base Badge */
.badge-enterprise {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Status Variants */
.badge-enterprise--synced {
    background: rgba(var(--ppm-secondary-rgb, 5, 150, 105), 0.2);
    color: var(--ppm-secondary);
}

.badge-enterprise--pending {
    background: rgba(246, 173, 85, 0.2);
    color: #f6ad55;
}

.badge-enterprise--error {
    background: rgba(var(--ppm-accent-rgb, 220, 38, 38), 0.2);
    color: var(--ppm-accent);
}
```

**Reference:** See `resources/components.md` for complete catalog.

---

### 3. Layer System for Z-Index

**RULE:** NEVER use arbitrary z-index values. Use predefined layer classes.

```css
/* resources/css/admin/layout.css */

/* Z-Index Layer Classification */
.layer-base { z-index: 1; }
.layer-panel { z-index: 10; }
.layer-sticky { z-index: 20; }
.layer-modal { z-index: 100; }
.layer-overlay { z-index: 200; }
.layer-tooltip { z-index: 300; }

/* Development Only */
.layer-debug { z-index: 999; } /* Only in DEV, never production */
```

**Usage:**
```blade
{{-- ✅ CORRECT --}}
<div class="modal-overlay layer-overlay">
    <div class="modal-content layer-modal">
        Modal content
    </div>
</div>

<div class="dropdown-menu layer-panel">
    Dropdown items
</div>

{{-- ❌ WRONG --}}
<div class="z-[9999]">...</div>
<div style="z-index: 999;">...</div>
```

**Why This Matters:**
- Prevents z-index conflicts
- Easy to reason about layering hierarchy
- Searchable and refactorable
- Self-documenting code

---

### 4. Forms & Controls

**Input Fields:**
```css
/* Base Input */
.form-input-enterprise {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid var(--text-muted);
    background: var(--bg-card);
    color: var(--text-primary);
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input-enterprise:focus {
    outline: none;
    border-color: var(--mpp-primary);
    box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), 0.35);
}
```

**Checkbox/Radio:**
```css
.checkbox-enterprise {
    accent-color: var(--mpp-primary);
    cursor: pointer;
}

/* Variant - Danger */
.checkbox-danger {
    accent-color: var(--ppm-accent);
}
```

**Progress Bars:**
```css
.progress-enterprise {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: var(--bg-card-hover);
    overflow: hidden;
    position: relative;
}

.progress-enterprise__fill {
    height: 100%;
    background: linear-gradient(90deg, var(--ppm-primary), var(--ppm-primary-dark));
    border-radius: 4px;
    transition: transform 0.3s ease;
    transform: scaleX(var(--progress, 0));
    transform-origin: left;
}
```

**Blade + Alpine.js:**
```blade
{{-- Progress bar with dynamic value --}}
<div class="progress-enterprise"
     x-data="{ progress: @entangle('syncProgress') }"
     x-effect="$el.style.setProperty('--progress', progress / 100)">
    <div class="progress-enterprise__fill"></div>
</div>
```

---

### 5. Layout & Spacing

**Grid System:**
```css
/* Standard Container */
.ppm-container {
    max-width: 1280px; /* 80rem */
    margin: 0 auto;
    padding: 0 1rem;
}

@media (min-width: 1280px) {
    .ppm-container {
        padding: 0 2rem;
    }
}

/* Product Form Layout */
.product-form-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

@media (min-width: 1024px) {
    .product-form-layout {
        grid-template-columns: 2fr 1fr;
    }
}
```

**Spacing Rules:**
- Cards: `padding: 24px` (desktop), `padding: 16px` (mobile)
- Grid gaps: Minimum `16px`, standard `24px`
- Section spacing: `32px` vertical
- Form groups: `20px` margin-bottom

**❌ ZAKAZ:**
```css
/* NEVER use inline styles for layout */
<div style="margin: 23px;">...</div>
<div style="display: flex; justify-content: center;">...</div>
```

**✅ WYMAGANE:**
```blade
<div class="space-y-6">
    <div class="enterprise-card">...</div>
    <div class="enterprise-card">...</div>
</div>
```

---

### 6. Typography

**Text Hierarchy:**
```css
/* Headings */
.text-h1 {
    font-size: 2.25rem;  /* 36px */
    font-weight: 700;
    line-height: 1.2;
    color: var(--text-primary);
    margin-bottom: 16px;
}

.text-h2 {
    font-size: 1.875rem; /* 30px */
    font-weight: 600;
    line-height: 1.3;
    color: var(--text-primary);
    margin-bottom: 12px;
}

.text-h3 {
    font-size: 1.5rem;   /* 24px */
    font-weight: 600;
    line-height: 1.4;
    color: var(--text-primary);
    margin-bottom: 12px;
}

/* Body Text */
.text-body {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--text-primary);
}

.text-small {
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--text-secondary);
}

/* Brand Mantra */
.brand-mantra {
    font-size: 1.125rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--mpp-primary);
    text-transform: uppercase;
}
```

**Usage:**
```blade
<h1 class="text-h1">Zarządzanie produktami</h1>
<p class="text-body">Opis funkcjonalności...</p>
<span class="text-small">Ostatnia aktualizacja: 2025-11-19</span>
<blockquote class="brand-mantra">/// TWORZYMY PASJE ///</blockquote>
```

---

### 7. Icons & Visual Elements

**Icon Chips:**
```css
.icon-chip {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    background: rgba(var(--mpp-primary-rgb), 0.18);
    color: var(--mpp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.icon-chip--secondary {
    background: rgba(var(--ppm-primary-rgb, 37, 99, 235), 0.18);
    color: var(--ppm-primary);
}
```

**❌ ZAKAZ:**
```blade
{{-- NEVER inline styles for icon backgrounds --}}
<div style="background-color: rgba(224, 172, 126, 0.2); width: 48px; height: 48px;">
    <i class="icon"></i>
</div>
```

**✅ WYMAGANE:**
```blade
<div class="icon-chip">
    <i class="fas fa-box"></i>
</div>
```

---

## Workflow: Adding New Styles

**Step-by-Step Process:**

1. **Check Existing Classes**
   - Search `resources/css/admin/components.css`
   - Search `resources/css/products/category-form.css`
   - Use `Grep` tool to find similar patterns

2. **Decide on File Location**
   - Reusable components → `admin/components.css`
   - Product-specific → `products/[feature].css`
   - Category forms → `products/category-form.css`
   - Create NEW file ONLY if >200 lines needed

3. **Define Styles Using Tokens**
   ```css
   /* ✅ CORRECT */
   .new-component {
       background: var(--bg-card);
       color: var(--text-primary);
       border: 1px solid var(--text-muted);
   }

   /* ❌ WRONG */
   .new-component {
       background: #1e293b;
       color: #f8fafc;
   }
   ```

4. **Import in app.css (if new file)**
   ```css
   /* resources/css/app.css */
   @import './admin/components.css';
   @import './products/new-feature.css'; /* New import */
   ```

5. **Build & Verify**
   ```bash
   npm run build
   ```
   - Check `public/build/manifest.json` exists
   - Verify hashed filenames generated

6. **Deploy to Production**
   ```powershell
   # Upload ALL assets (Vite regenerates hashes for ALL files)
   pscp -r -i $HostidoKey -P 64321 "public/build/assets/*" host379076@...:public/build/assets/

   # Upload manifest to ROOT (CRITICAL!)
   pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json

   # Clear caches
   plink ... -batch "cd domains/.../public_html && php artisan view:clear && php artisan cache:clear"
   ```

7. **Verify HTTP 200**
   ```powershell
   # Check ALL CSS files return 200
   curl -I "https://ppm.mpptrade.pl/public/build/assets/components-[hash].css"
   ```

8. **Screenshot Verification**
   ```bash
   node _TOOLS/full_console_test.cjs
   ```

**Reference:** See `resources/workflow.md` for detailed deployment checklist.

---

## Anti-Patterns (ZAKAZ!)

### ❌ Anti-Pattern 1: Inline Styles
```blade
{{-- ❌ ABSOLUTELY FORBIDDEN --}}
<div style="background: #e0ac7e;">...</div>
<button style="padding: 10px; color: white;">...</button>
```

### ❌ Anti-Pattern 2: Hardcoded Colors
```css
/* ❌ WRONG */
.button {
    background: #e0ac7e;
    color: #ffffff;
}

/* ✅ CORRECT */
.button {
    background: var(--mpp-primary);
    color: var(--text-primary);
}
```

### ❌ Anti-Pattern 3: Arbitrary Z-Index
```css
/* ❌ WRONG */
.modal { z-index: 9999; }
.dropdown { z-index: 999; }

/* ✅ CORRECT */
.modal { z-index: var(--z-modal); }
.dropdown { z-index: var(--z-panel); }
```

### ❌ Anti-Pattern 4: Duplicating Component Styles
```css
/* ❌ WRONG - Copying gradient to multiple classes */
.btn-save {
    background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%);
}

.btn-submit {
    background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%);
}

/* ✅ CORRECT - Use existing class */
<button class="btn-enterprise-primary">Save</button>
<button class="btn-enterprise-primary">Submit</button>
```

---

## Vite Build Integration

**Laravel Blade:**
```blade
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPM Admin</title>

    {{-- ✅ CORRECT: Load Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
```

**CRITICAL:**
- `npm run build` regenerates hashes for **ALL files**
- Must upload **ALL** `public/build/assets/*`
- Manifest MUST be at `public/build/manifest.json` (ROOT, not `.vite/`)

**Reference:** See `_DOCS/DEPLOYMENT_GUIDE.md` for complete deployment workflow.

---

## Checklist Before Merge/Deploy

- [ ] Zero `style="..."` in new/modified Blade files
- [ ] All colors use CSS tokens (`var(--token)`)
- [ ] Buttons use `.btn-enterprise-*` classes
- [ ] Checkboxes/radio use `.checkbox-enterprise`
- [ ] Progress bars use `.progress-enterprise`
- [ ] Z-index uses `.layer-*` classes
- [ ] Layout uses grid/flex classes (no inline styles)
- [ ] `npm run build` successful
- [ ] All assets uploaded to production
- [ ] Manifest uploaded to ROOT (`public/build/manifest.json`)
- [ ] HTTP 200 verification for all CSS files
- [ ] Screenshot verification passed
- [ ] No console errors in browser

---

## Resource Files

- **color-palette.md** - Complete PPM color system with semantic naming
- **components.md** - Enterprise component catalog with examples
- **workflow.md** - Detailed deployment checklist and troubleshooting

---

## Related Skills

- **frontend-dev-guidelines** - Generic frontend rules (complements this skill)
- **frontend-verification** - MANDATORY UI screenshot testing
- **livewire-dev-guidelines** - Livewire component patterns
- **hostido-deployment** - Production deployment automation

---

## Integration with frontend-dev-guidelines

**Priority Order:**
1. **ppm-styling-guidelines** (this skill) - PPM-specific standards
2. **frontend-dev-guidelines** - Generic frontend rules

**Overlap Resolution:**
- Both skills enforce ZAKAZ inline styles → Reinforced
- Both skills enforce ZAKAZ arbitrary Tailwind → Reinforced
- This skill ADDS: PPM color tokens, enterprise components, layer system

**Usage Pattern:**
```
User task: "Style new product form button"
  ↓
1. ppm-styling-guidelines activates (PPM-specific)
   - Use .btn-enterprise-primary
   - Use var(--mpp-primary)
   - Follow PPM workflow
  ↓
2. frontend-dev-guidelines activates (generic enforcement)
   - NO inline styles
   - NO arbitrary Tailwind
   - Screenshot verification
  ↓
3. Implementation
```

---

**Skill Version:** 1.0.0
**Last Updated:** 2025-11-19
**Maintainer:** PPM Development Team
**Compliance:** MANDATORY for all PPM UI development
