---
paths: "**/*.blade.php"
---

# PPM Styling Playbook (CSS Master Rule)

## Critical Rule
**ZERO TOLERANCE** for `style=""` attributes and hardcoded colors in Blade templates.

## Color Palette (CSS Custom Properties)
| Token | Value | Usage |
|-------|-------|-------|
| `--mpp-primary` / `--mpp-primary-dark` | `#e0ac7e` / `#d1975a` | CTA, active links, highlights |
| `--ppm-primary` / `--ppm-primary-dark` | `#2563eb` / `#1d4ed8` | System actions (sync) |
| `--ppm-secondary` / `--ppm-secondary-dark` | `#059669` / `#047857` | Success, "online" status |
| `--ppm-accent` / `--ppm-accent-dark` | `#dc2626` / `#b91c1c` | Errors, destructive actions |
| `--bg-card`, `--bg-card-hover`, `--bg-nav` | - | Cards, panels, sidebar |
| `--text-primary`, `--text-secondary`, `--text-muted` | - | Text hierarchy |

**Rules:**
- New colors as CSS variables: `color: var(--mpp-primary);`
- Gradients/shadows use brand values
- **FORBIDDEN:** hex values in Blade (even if matching brand)

## Enterprise Component Classes

### Buttons
```html
<button class="btn-enterprise-primary">Save</button>      <!-- Main CTA (orange gradient) -->
<button class="btn-enterprise-secondary">Cancel</button>  <!-- Neutral (dark bg + border) -->
<button class="btn-enterprise-danger">Delete</button>     <!-- Destructive -->
<button class="btn-enterprise-sm">Small</button>          <!-- Compact -->
```
Use `@class` for states (`disabled`, `loading`). Animation via `.btn-enterprise-primary::before`.

### Cards and Panels
```html
<div class="enterprise-card">Content</div>
<div class="enterprise-card-warning">Warning</div>
<div class="enterprise-card-success">Success</div>
```

### Badge and Status
```html
<span class="badge-enterprise">Default</span>
<span class="badge-enterprise--warning">Warning</span>
<span class="sync-status-*">Sync status</span>
```

### Table Rows (Variant Subrows)
```css
.variant-subrow {
    background: #151a238c;
    border-left: 3px solid var(--ppm-primary);
}
.variant-subrow:hover {
    background: rgba(31, 41, 55, 0.7);
}
```
**Location:** `resources/css/admin/components.css` (~line 6828)
**Rule:** Table rows styled ONLY via CSS classes, NEVER inline Tailwind (`bg-gray-800/30`)

### Form Inputs
```html
<input class="form-input-enterprise" type="text">
<input type="checkbox" class="checkbox-enterprise">
<input type="radio" class="checkbox-enterprise">
```
Focus ring: `box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), .35)`
**FORBIDDEN:** `style="accent-color: ..."`

### Progress Bars
```html
<div class="progress-enterprise" data-progress="{{ $percent }}">
    <div class="progress-enterprise__fill"></div>
</div>

<!-- With Alpine for dynamic values -->
<div x-data="{ value: @entangle('progress') }"
     x-effect="$refs.bar.style.setProperty('--progress', value / 100)">
    <span class="progress-enterprise__fill" x-ref="bar"></span>
</div>
```

## Z-Index Layer System
| Name | Z-index |
|------|---------|
| `.layer-base` | `1` |
| `.layer-panel` | `10` |
| `.layer-modal` | `100` |
| `.layer-overlay` | `200` |
| `.layer-debug` (DEV only) | `999` |

**FORBIDDEN:** Tailwind `z-[9999]` or `style="z-index: ..."`

## Layout & Spacing
- Grids: `max-w-4xl`, `max-w-6xl`, `px-4 xl:px-8` (like ProductForm)
- Sidebar + content: `lg:grid lg:grid-cols-[2fr_1fr]`
- Card paddings: `p-4` (mobile), `p-6` (desktop)
- **NO** `style="margin: ..."` - use `mx-auto`, `space-y-*`

## Typography
- Font: `Inter`
- Headings: `.text-h1`, `.text-h2`, `.text-h3`
- Colors: `.text-dark-primary` (white), `.text-dark-secondary` (gray 200), `.text-dark-muted`
- Brand mantra: `.brand-mantra` class (NOT `style="color:#e0ac7e"`)

## CTA Gradient (ONLY allowed)
```css
linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%)
```
Defined in `.btn-enterprise-primary` - do NOT duplicate.

## Icon Chips
```css
.icon-chip {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    background: rgba(var(--mpp-primary-rgb), 0.18);
    color: var(--mpp-primary);
}
```
**FORBIDDEN:** `style="background-color: rgba(224, 172, 126, 0.2)"` per element

## Existing CSS Files (Safe)
- `resources/css/admin/components.css` - Admin UI
- `resources/css/admin/layout.css` - Layout/grid
- `resources/css/products/category-form.css` - Product forms
- `resources/css/components/category-picker.css` - Pickers

## Adding New Styles Workflow
1. Check existing classes in `components.css`, `category-form.css`
2. New CSS module ONLY when >200 lines needed
3. Colors as `var(--token)`
4. **NEVER** store styles in Blade
5. Build & deploy: `npm run build` -> upload manifest

## Pre-Deploy Checklist
- [ ] Zero `style="..."` in new/changed Blade files
- [ ] All colors reference tokens
- [ ] Buttons use `.btn-enterprise-*`
- [ ] Checkboxes, radios, progress use CSS classes
- [ ] Scalable layout (no hardcoded pixel widths)
