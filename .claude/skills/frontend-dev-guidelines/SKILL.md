# Frontend Development Guidelines

**Type:** domain
**Enforcement:** require
**Priority:** critical
**Version:** 1.0.0
**Last Updated:** 2025-11-04

---

## Quick Reference

This skill enforces STRICT frontend development rules for the PPM project. The project has zero tolerance for inline styles and arbitrary Tailwind values to maintain a consistent, maintainable design system.

**CRITICAL RULES:**
- ❌ **ZAKAZ:** Inline styles (`style="..."`)
- ❌ **ZAKAZ:** Arbitrary Tailwind values (`class="z-[9999]"`)
- ✅ **WYMAGANE:** Dedicated CSS classes in `resources/css/`
- ✅ **WYMAGANE:** Screenshot verification after UI changes

---

## When to Use This Skill

**Auto-triggers on:**
- Keywords: `css`, `tailwind`, `style`, `modal`, `dropdown`, `ui`, `layout`
- File edits: `resources/css/**/*.css`, `resources/views/**/*.blade.php`
- Code patterns: `style="`, `class="z-[`, `@apply`

**Manually invoke when:**
- Creating new UI components
- Styling Blade templates
- Working with modals, dropdowns, overlays
- Adding animations or transitions
- Debugging z-index issues

---

## Core Principles

### 1. NO INLINE STYLES (Zero Tolerance)

**RULE:** `style="..."` is ABSOLUTELY FORBIDDEN in Blade templates.

**❌ WRONG:**
```blade
<div style="z-index: 9999; background: red;">
    Modal
</div>
```

**❌ ALSO WRONG:**
```blade
<div style="display: flex; justify-content: center;">
    Content
</div>
```

**❌ STILL WRONG:**
```blade
<button style="margin-top: 10px;">
    Click me
</button>
```

**✅ CORRECT:**
```blade
<div class="modal-overlay">
    Modal
</div>
```

**Why This Rule Exists:**
- Inline styles can't be reused
- Hard to maintain (scattered across templates)
- Can't be overridden with media queries
- Breaks design system consistency
- Makes refactoring impossible

**Exception:** NONE. There are NO exceptions to this rule.

---

### 2. NO ARBITRARY TAILWIND VALUES

**RULE:** Arbitrary values like `z-[9999]`, `bg-[#ff0000]`, `w-[847px]` are FORBIDDEN.

**❌ WRONG:**
```blade
<div class="z-[9999] bg-[#ff0000] w-[847px]">
    Content
</div>
```

**❌ ALSO WRONG:**
```blade
<div class="mt-[23px] text-[#333333]">
    Content
</div>
```

**✅ CORRECT:**
```blade
<div class="modal-overlay modal-content-wide">
    Content
</div>
```

**Why This Rule Exists:**
- Defeats the purpose of a design system
- Creates inconsistent spacing/colors
- Can't be tracked or refactored
- Breaks responsive design patterns
- Makes design changes impossible

**Exception:** NONE. Use design tokens from `tailwind.config.js` instead.

---

### 3. DEDICATED CSS FILES

**RULE:** All styles must be in dedicated CSS files in `resources/css/`.

**File Structure:**
```
resources/css/
├── app.css                          # Main entry (Tailwind directives)
├── admin/
│   ├── layout.css                   # Admin layout grid
│   └── components.css               # Reusable admin components
├── products/
│   ├── category-form.css            # Category form styles
│   └── variant-management.css       # Variant UI styles
└── components/
    ├── category-picker.css          # Picker component
    ├── category-preview-modal.css   # Modal styles
    ├── modal.css                    # Generic modal styles
    └── buttons.css                  # Button variants
```

**Example - Modal Component:**

```css
/* resources/css/components/modal.css */

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: var(--z-modal-overlay);  /* 1050 from design tokens */
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    position: relative;
    z-index: var(--z-modal-content);  /* 1051 */
    background-color: white;
    border-radius: 8px;
    padding: 24px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-content-wide {
    max-width: 900px;
}

.modal-content-narrow {
    max-width: 400px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-gray-900);
}

.modal-close {
    color: var(--color-gray-400);
    cursor: pointer;
    transition: color 0.2s;
}

.modal-close:hover {
    color: var(--color-gray-600);
}
```

**Blade Usage:**
```blade
<div class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Modal Title</h2>
            <button type="button" class="modal-close">×</button>
        </div>
        <div class="modal-body">
            Content here
        </div>
    </div>
</div>
```

---

### 4. DESIGN TOKENS (CSS Variables)

**RULE:** Use design tokens from `tailwind.config.js` or CSS variables.

**Z-index Scale:**
```css
/* tailwind.config.js or resources/css/variables.css */
:root {
    --z-base: 0;
    --z-dropdown: 1000;
    --z-sticky: 1020;
    --z-fixed: 1030;
    --z-modal-backdrop: 1040;
    --z-modal-overlay: 1050;
    --z-modal-content: 1051;
    --z-popover: 1060;
    --z-tooltip: 1070;
}
```

**Color Palette:**
```css
:root {
    /* MPP Orange Brand Colors */
    --color-brand-500: #e0ac7e;  /* Main MPP Orange */
    --color-brand-600: #d1975a;  /* Hover state */
    --color-brand-700: #c28545;  /* Active state */

    /* Gray Scale */
    --color-gray-50: #f9fafb;
    --color-gray-100: #f3f4f6;
    --color-gray-200: #e5e7eb;
    --color-gray-300: #d1d5db;
    --color-gray-400: #9ca3af;
    --color-gray-500: #6b7280;
    --color-gray-600: #4b5563;
    --color-gray-700: #374151;
    --color-gray-800: #1f2937;
    --color-gray-900: #111827;

    /* Semantic Colors */
    --color-success: #10b981;
    --color-error: #ef4444;
    --color-warning: #f59e0b;
    --color-info: #3b82f6;
}
```

**Spacing Scale (Already defined in Tailwind):**
```javascript
// tailwind.config.js
module.exports = {
    theme: {
        spacing: {
            '0': '0',
            '1': '0.25rem',  // 4px
            '2': '0.5rem',   // 8px
            '3': '0.75rem',  // 12px
            '4': '1rem',     // 16px
            '6': '1.5rem',   // 24px
            '8': '2rem',     // 32px
            // ... use these, not arbitrary values!
        }
    }
}
```

---

### 5. MANDATORY SCREENSHOT VERIFICATION

**RULE:** ALL UI changes MUST be verified with screenshots before marking task complete.

**Workflow:**
1. Make UI changes
2. Run `_TOOLS/full_console_test.cjs` (PPM verification tool)
3. Review screenshots in `_TEST/screenshots/`
4. Check console for errors
5. Verify Livewire initialization
6. Only then mark task complete

**Script Usage:**
```bash
# Run verification
node _TOOLS/full_console_test.cjs

# Output:
# - Screenshots in _TEST/screenshots/
# - Console logs
# - HTTP request monitoring
# - Livewire init verification
```

**What to Check:**
- No console errors (red)
- Livewire initialized successfully
- No failed HTTP requests
- UI renders correctly (screenshots)
- Responsive design works (mobile/tablet/desktop)

**See:** `resources/verification.md` for detailed workflow

---

## Common Patterns

### Pattern 1: Enterprise Card Component

**CSS:**
```css
/* resources/css/components/cards.css */
.enterprise-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.enterprise-card-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--color-gray-200);
    background-color: var(--color-gray-50);
}

.enterprise-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-gray-900);
}

.enterprise-card-body {
    padding: 24px;
}

.enterprise-card-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--color-gray-200);
    background-color: var(--color-gray-50);
}
```

**Blade:**
```blade
<div class="enterprise-card">
    <div class="enterprise-card-header">
        <h3 class="enterprise-card-title">Product Information</h3>
    </div>
    <div class="enterprise-card-body">
        {{-- Content --}}
    </div>
    <div class="enterprise-card-footer">
        {{-- Actions --}}
    </div>
</div>
```

---

### Pattern 2: Tab System

**CSS:**
```css
/* resources/css/components/tabs.css */
.tabs-enterprise {
    display: flex;
    border-bottom: 2px solid var(--color-gray-200);
    gap: 4px;
}

.tab-enterprise {
    padding: 12px 24px;
    font-weight: 500;
    color: var(--color-gray-600);
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.tab-enterprise:hover {
    color: var(--color-gray-900);
}

.tab-enterprise.active {
    color: var(--color-brand-500);
}

.tab-enterprise.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--color-brand-500);
}
```

**Blade:**
```blade
<div class="tabs-enterprise">
    <button type="button"
            wire:click="$set('activeTab', 0)"
            class="tab-enterprise {{ $activeTab === 0 ? 'active' : '' }}">
        Basic Info
    </button>
    <button type="button"
            wire:click="$set('activeTab', 1)"
            class="tab-enterprise {{ $activeTab === 1 ? 'active' : '' }}">
        Categories
    </button>
    <button type="button"
            wire:click="$set('activeTab', 2)"
            class="tab-enterprise {{ $activeTab === 2 ? 'active' : '' }}">
        Variants
    </button>
</div>
```

---

### Pattern 3: Button System

**CSS:**
```css
/* resources/css/components/buttons.css */
.btn-enterprise {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-enterprise-primary {
    background-color: var(--color-brand-500);
    color: white;
}

.btn-enterprise-primary:hover {
    background-color: var(--color-brand-600);
}

.btn-enterprise-secondary {
    background-color: var(--color-gray-200);
    color: var(--color-gray-700);
}

.btn-enterprise-secondary:hover {
    background-color: var(--color-gray-300);
}

.btn-enterprise-danger {
    background-color: var(--color-error);
    color: white;
}

.btn-enterprise-danger:hover {
    background-color: #dc2626;
}

.btn-enterprise-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
}

.btn-enterprise-lg {
    padding: 12px 24px;
    font-size: 1.125rem;
}
```

**Blade:**
```blade
<button type="submit" class="btn-enterprise btn-enterprise-primary">
    Save Product
</button>

<button type="button"
        wire:click="cancel"
        class="btn-enterprise btn-enterprise-secondary">
    Cancel
</button>

<button type="button"
        wire:click="delete"
        class="btn-enterprise btn-enterprise-danger btn-enterprise-sm">
    Delete
</button>
```

---

### Pattern 4: Dropdown Menu

**CSS:**
```css
/* resources/css/components/dropdown.css */
.dropdown {
    position: relative;
}

.dropdown-trigger {
    cursor: pointer;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    min-width: 200px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    z-index: var(--z-dropdown);
    display: none;
}

.dropdown-menu.open {
    display: block;
}

.dropdown-item {
    padding: 12px 16px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: var(--color-gray-50);
}

.dropdown-item:first-child {
    border-radius: 8px 8px 0 0;
}

.dropdown-item:last-child {
    border-radius: 0 0 8px 8px;
}
```

**Blade with Alpine.js:**
```blade
<div class="dropdown" x-data="{ open: false }">
    <button type="button"
            @click="open = !open"
            class="dropdown-trigger">
        Options ▼
    </button>

    <div class="dropdown-menu"
         :class="{ 'open': open }"
         @click.outside="open = false">
        <div class="dropdown-item" @click="editProduct()">
            Edit
        </div>
        <div class="dropdown-item" @click="duplicateProduct()">
            Duplicate
        </div>
        <div class="dropdown-item" @click="deleteProduct()">
            Delete
        </div>
    </div>
</div>
```

---

## Anti-Patterns (ZAKAZ!)

### ❌ Anti-Pattern 1: Inline Styles

**Examples of FORBIDDEN code:**
```blade
{{-- ❌ ABSOLUTELY FORBIDDEN --}}
<div style="display: flex;">...</div>
<p style="color: red;">...</p>
<button style="margin-top: 10px;">...</button>
<span style="font-weight: bold;">...</span>
```

**Why it's forbidden:**
- Can't be reused
- Can't be responsive
- Can't be themed
- Hard to maintain

**Correct approach:**
```blade
{{-- ✅ CORRECT --}}
<div class="flex">...</div>
<p class="text-error">...</p>
<button class="mt-4">...</button>
<span class="font-semibold">...</span>
```

---

### ❌ Anti-Pattern 2: Arbitrary Tailwind Values

**Examples of FORBIDDEN code:**
```blade
{{-- ❌ ABSOLUTELY FORBIDDEN --}}
<div class="z-[9999]">...</div>
<div class="bg-[#ff0000]">...</div>
<div class="w-[847px]">...</div>
<div class="mt-[23px]">...</div>
```

**Why it's forbidden:**
- Breaks design system
- Can't be tracked
- Inconsistent spacing/colors
- Hard to refactor

**Correct approach:**
```blade
{{-- ✅ CORRECT: Use design tokens --}}
<div class="modal-overlay">...</div>
<div class="bg-error">...</div>
<div class="max-w-4xl">...</div>
<div class="mt-6">...</div>
```

---

### ❌ Anti-Pattern 3: Magic Numbers in z-index

**❌ WRONG:**
```css
.my-modal {
    z-index: 9999;  /* Magic number! */
}

.my-dropdown {
    z-index: 999;  /* Another magic number! */
}
```

**✅ CORRECT:**
```css
.my-modal {
    z-index: var(--z-modal-overlay);  /* 1050 from design system */
}

.my-dropdown {
    z-index: var(--z-dropdown);  /* 1000 from design system */
}
```

---

### ❌ Anti-Pattern 4: Component Styles in app.css

**❌ WRONG:**
```css
/* resources/css/app.css */

/* Mixing component styles with global styles */
.product-form { /* ... */ }
.category-tree { /* ... */ }
.modal { /* ... */ }
```

**✅ CORRECT:**
```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Import component styles */
@import './components/modal.css';
@import './components/buttons.css';
@import './products/category-form.css';
```

---

## Vite Build Process

### Development

```bash
# Start Vite dev server (hot reload)
npm run dev
```

**IMPORTANT:** Dev server ONLY for local development!

### Production Build

```bash
# Build for production
npm run build
```

**Output:**
```
public/build/
├── assets/
│   ├── app-[hash].css      # Bundled CSS
│   ├── app-[hash].js       # Bundled JS
│   └── ...
└── manifest.json           # ✅ REQUIRED by Laravel (ROOT!)
```

**CRITICAL:** `manifest.json` MUST be in `public/build/` (root), NOT in `public/build/.vite/`!

**Deployment Checklist:**
- [ ] Run `npm run build` locally
- [ ] Verify `public/build/manifest.json` exists
- [ ] Upload `public/build/` to production
- [ ] Clear Laravel caches (`php artisan view:clear`)
- [ ] Verify assets load (check browser console)

**See:** `resources/vite-build.md` for complete guide

---

## Alpine.js Integration

**RULE:** Use Alpine.js for client-side UI state, Livewire for server-side state.

**Example - Modal with Alpine:**
```blade
<div x-data="{ open: false }">
    {{-- Trigger --}}
    <button type="button" @click="open = true">
        Open Modal
    </button>

    {{-- Modal --}}
    <div x-show="open"
         x-cloak
         class="modal-overlay"
         @click.self="open = false">
        <div class="modal-content">
            <h2 class="modal-title">Modal Title</h2>

            <button type="button"
                    @click="open = false"
                    class="modal-close">
                ×
            </button>

            <div class="modal-body">
                {{-- Livewire content --}}
                <livewire:product.form />
            </div>
        </div>
    </div>
</div>
```

**Alpine Directives Commonly Used:**
- `x-data` - Component state
- `x-show` / `x-if` - Conditional rendering
- `x-on / @` - Event handling
- `@click.outside` - Click outside detection
- `x-cloak` - Hide until Alpine loads

**See:** `resources/alpine-patterns.md`

---

## Resource Files

- **css-architecture.md** - File organization, naming conventions
- **styling-rules.md** - Complete styling rules (ZAKAZ + WYMAGANE)
- **alpine-patterns.md** - Alpine.js best practices
- **vite-build.md** - Vite configuration, production builds
- **verification.md** - MANDATORY screenshot testing workflow

---

## Related Skills

- **livewire-dev-guidelines** - When working with Livewire components
- **laravel-dev-guidelines** - When implementing backend for frontend features

---

## Success Checklist

Before finishing frontend work, verify:

- [ ] NO inline styles (`style="..."`) anywhere
- [ ] NO arbitrary Tailwind values (`z-[9999]`, `bg-[#...]`)
- [ ] All styles in dedicated CSS files
- [ ] Design tokens used (z-index, colors, spacing)
- [ ] Alpine.js used correctly (client-side state only)
- [ ] Screenshot verification completed
- [ ] No console errors
- [ ] Responsive design tested
- [ ] Vite build successful (`npm run build`)

---

**Skill Version:** 1.0.0
**Last Updated:** 2025-11-04
**Maintainer:** PPM Development Team
**Zero Tolerance:** Inline styles and arbitrary Tailwind WILL be rejected in code review
