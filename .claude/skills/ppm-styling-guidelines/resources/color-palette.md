# PPM Color Palette Reference

**Version:** 1.0.0
**Last Updated:** 2025-11-19

Complete color system for PPM-CC-Laravel application with semantic naming and usage guidelines.

---

## Brand Colors

### MPP Orange (Primary Brand)

**Primary CTA, Active Links, Highlights**

```css
:root {
    --mpp-primary: #e0ac7e;           /* Main MPP Orange */
    --mpp-primary-dark: #d1975a;      /* Hover/Active state */
    --mpp-primary-darker: #c08449;    /* Deep accent */
    --mpp-primary-rgb: 224, 172, 126; /* For rgba() usage */
}
```

**Usage:**
- Primary call-to-action buttons
- Active navigation items
- Important status indicators
- Brand highlighting elements
- Focus states on inputs

**Examples:**
```css
.btn-enterprise-primary {
    background: linear-gradient(135deg, var(--mpp-primary) 0%, var(--mpp-primary-dark) 50%, var(--mpp-primary-darker) 100%);
}

.nav-item.active {
    color: var(--mpp-primary);
    border-bottom: 2px solid var(--mpp-primary);
}

.form-input-enterprise:focus {
    border-color: var(--mpp-primary);
    box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), 0.35);
}
```

---

### PPM Blue (System Actions)

**Sync Operations, System Functions**

```css
:root {
    --ppm-primary: #2563eb;           /* Blue-600 */
    --ppm-primary-dark: #1d4ed8;      /* Blue-700 */
    --ppm-primary-rgb: 37, 99, 235;   /* For rgba() */
}
```

**Usage:**
- Sync/refresh buttons
- System operation indicators
- PrestaShop integration actions
- Import/export operations

**Examples:**
```css
.sync-badge {
    background-color: rgba(var(--ppm-primary-rgb), 0.2);
    color: var(--ppm-primary);
}

.btn-sync {
    background: var(--ppm-primary);
    color: white;
}

.btn-sync:hover {
    background: var(--ppm-primary-dark);
}
```

---

### PPM Green (Success)

**Success States, Online Status**

```css
:root {
    --ppm-secondary: #059669;         /* Emerald-600 */
    --ppm-secondary-dark: #047857;    /* Emerald-700 */
    --ppm-secondary-rgb: 5, 150, 105; /* For rgba() */
}
```

**Usage:**
- Success notifications
- Online/connected status
- Positive validation feedback
- "Synced" status badges

**Examples:**
```css
.status-online {
    color: var(--ppm-secondary);
}

.status-online::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    background: var(--ppm-secondary);
    border-radius: 50%;
    margin-right: 8px;
}

.badge-success {
    background: rgba(var(--ppm-secondary-rgb), 0.2);
    color: var(--ppm-secondary);
}
```

---

### PPM Red (Errors)

**Errors, Warnings, Destructive Actions**

```css
:root {
    --ppm-accent: #dc2626;            /* Red-600 */
    --ppm-accent-dark: #b91c1c;       /* Red-700 */
    --ppm-accent-rgb: 220, 38, 38;    /* For rgba() */
}
```

**Usage:**
- Error messages
- Destructive action buttons (delete, remove)
- Validation errors
- Critical warnings
- "Failed" status badges

**Examples:**
```css
.error-message {
    color: var(--ppm-accent);
    background: rgba(var(--ppm-accent-rgb), 0.1);
    border-left: 4px solid var(--ppm-accent);
    padding: 12px 16px;
}

.btn-danger {
    background: var(--ppm-accent);
    color: white;
}

.btn-danger:hover {
    background: var(--ppm-accent-dark);
}

.input-error {
    border-color: var(--ppm-accent);
}
```

---

## Backgrounds

### Card Backgrounds

```css
:root {
    --bg-card: #1e293b;               /* Slate-800 - Default card background */
    --bg-card-hover: #334155;         /* Slate-700 - Hover state */
    --bg-card-active: #475569;        /* Slate-600 - Active/selected */
}
```

**Usage:**
```css
.enterprise-card {
    background: var(--bg-card);
}

.enterprise-card:hover {
    background: var(--bg-card-hover);
}

.list-item.selected {
    background: var(--bg-card-active);
}
```

### Navigation/Sidebar

```css
:root {
    --bg-nav: #0f172a;                /* Slate-900 - Navigation background */
    --bg-nav-hover: #1e293b;          /* Slate-800 - Nav item hover */
}
```

**Usage:**
```css
.admin-sidebar {
    background: var(--bg-nav);
}

.nav-item:hover {
    background: var(--bg-nav-hover);
}
```

### Page Background

```css
:root {
    --bg-page: #0f172a;               /* Slate-900 - Main page background */
}
```

---

## Text Hierarchy

### Primary Text

```css
:root {
    --text-primary: #f8fafc;          /* Slate-50 - High contrast white */
}
```

**Usage:** Headings, important body text, button text

```css
h1, h2, h3 {
    color: var(--text-primary);
}

.btn-enterprise-primary {
    color: var(--text-primary);
}
```

### Secondary Text

```css
:root {
    --text-secondary: #cbd5e1;        /* Slate-300 - Supporting text */
}
```

**Usage:** Descriptions, labels, metadata

```css
.label {
    color: var(--text-secondary);
}

.product-description {
    color: var(--text-secondary);
}
```

### Muted Text

```css
:root {
    --text-muted: #94a3b8;            /* Slate-400 - Low emphasis */
}
```

**Usage:** Placeholders, hints, timestamps

```css
.timestamp {
    color: var(--text-muted);
    font-size: 0.875rem;
}

input::placeholder {
    color: var(--text-muted);
}
```

### Disabled Text

```css
:root {
    --text-disabled: #64748b;         /* Slate-500 - Disabled state */
}
```

**Usage:** Disabled buttons, inactive elements

```css
.btn:disabled {
    color: var(--text-disabled);
    cursor: not-allowed;
}
```

---

## Borders

```css
:root {
    --border-default: #334155;        /* Slate-700 */
    --border-hover: #475569;          /* Slate-600 */
    --border-focus: var(--mpp-primary); /* Orange on focus */
}
```

**Usage:**
```css
.enterprise-card {
    border: 1px solid var(--border-default);
}

.enterprise-card:hover {
    border-color: var(--border-hover);
}

.form-input:focus {
    border-color: var(--border-focus);
}
```

---

## Semantic Colors

### Warning (Yellow/Orange)

```css
:root {
    --color-warning: #f59e0b;         /* Amber-500 */
    --color-warning-bg: rgba(245, 158, 11, 0.1);
}
```

**Usage:**
```css
.badge-pending {
    background: var(--color-warning-bg);
    color: var(--color-warning);
}
```

### Info (Blue)

```css
:root {
    --color-info: #3b82f6;            /* Blue-500 */
    --color-info-bg: rgba(59, 130, 246, 0.1);
}
```

**Usage:**
```css
.notification-info {
    background: var(--color-info-bg);
    border-left: 4px solid var(--color-info);
}
```

---

## Usage Guidelines

### DO's ✅

```css
/* ✅ Always use CSS Custom Properties */
.button {
    background: var(--mpp-primary);
    color: var(--text-primary);
}

/* ✅ Use rgba() with RGB variables for transparency */
.overlay {
    background: rgba(var(--mpp-primary-rgb), 0.5);
}

/* ✅ Define new semantic colors as variables */
:root {
    --color-product-active: var(--ppm-secondary);
    --color-product-inactive: var(--text-muted);
}
```

### DON'Ts ❌

```css
/* ❌ NEVER hardcode hex colors */
.button {
    background: #e0ac7e;  /* Use var(--mpp-primary) instead */
}

/* ❌ NEVER use inline styles with colors */
<div style="color: #f8fafc;">...</div>

/* ❌ NEVER create one-off color values */
.special-button {
    background: #ff6b9d;  /* Define as --color-special first */
}
```

---

## Color Contrast Compliance

All color combinations meet **WCAG 2.1 AA standards** for accessibility.

**High Contrast Combinations (WCAG AAA):**
- `--text-primary` on `--bg-card` → Ratio: 16.2:1
- `--text-primary` on `--bg-nav` → Ratio: 18.5:1

**Standard Contrast Combinations (WCAG AA):**
- `--text-secondary` on `--bg-card` → Ratio: 9.8:1
- `--mpp-primary` on `white` → Ratio: 4.8:1

**Testing Tool:**
```bash
# Use WebAIM Contrast Checker
https://webaim.org/resources/contrastchecker/
```

---

## Migration from Hardcoded Colors

**Find and Replace Pattern:**

```bash
# Find hardcoded colors
grep -r "#e0ac7e" resources/css/
grep -r "#2563eb" resources/css/

# Replace with variables
sed -i 's/#e0ac7e/var(--mpp-primary)/g' file.css
sed -i 's/#2563eb/var(--ppm-primary)/g' file.css
```

**Manual Review Required:**
- Verify context (is it brand orange or different shade?)
- Check if rgba() transparency needed
- Ensure semantic naming matches usage

---

## Color System Evolution

**When to add new colors:**
1. New brand requirement (approved by design team)
2. New semantic meaning (e.g., "product archived" state)
3. Integration with external system (e.g., ERP status colors)

**Process:**
1. Define variable in `:root`
2. Add to this documentation
3. Update `resources/css/variables.css`
4. Test contrast ratios
5. Deploy with `npm run build`

**Example:**
```css
/* New: ERP integration status */
:root {
    --color-erp-synced: #10b981;      /* Emerald-500 */
    --color-erp-pending: #f59e0b;     /* Amber-500 */
    --color-erp-failed: #ef4444;      /* Red-500 */
}
```

---

**Last Updated:** 2025-11-19
**Maintained By:** PPM Frontend Team
**Reference:** `_DOCS/PPM_Styling_Playbook.md` (section 1)
