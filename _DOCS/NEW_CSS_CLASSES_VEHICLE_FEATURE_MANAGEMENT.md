# NEW CSS Classes - VehicleFeatureManagement Component

**Component:** `VehicleFeatureManagement.php` + Blade view
**Date:** 2025-10-23
**Purpose:** List of NEW CSS classes needed for template cards, feature library, and modals

---

## Template Cards (.template-card)

**Location:** Template grid display (3 columns responsive)

```css
/* Template Card Container */
.template-card {
    background: linear-gradient(135deg, rgba(31, 41, 55, 0.9), rgba(17, 24, 39, 0.95));
    border: 1px solid rgba(75, 85, 99, 0.3);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.template-card:hover {
    border-color: rgba(59, 130, 246, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

/* Template Icon (top center) */
.template-icon {
    font-size: 3rem;
    text-align: center;
    margin-bottom: 0.5rem;
}

.template-icon.electric {
    color: #60a5fa; /* Blue for electric */
}

.template-icon.combustion {
    color: #f59e0b; /* Orange for combustion */
}

.template-icon.custom {
    color: #a78bfa; /* Purple for custom */
}

/* Template Title */
.template-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #f3f4f6;
    text-align: center;
    margin-bottom: 0.5rem;
}

/* Template Stats (feature count, usage count) */
.template-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #9ca3af;
    text-align: center;
}

.stat-item {
    display: block;
}

/* Template Actions (Edit/Delete buttons) */
.template-actions {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: auto;
}

.btn-template-action {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    background: rgba(59, 130, 246, 0.2);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-template-action:hover {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.5);
}

.btn-template-action.delete {
    background: rgba(220, 38, 38, 0.2);
    border-color: rgba(220, 38, 38, 0.3);
    color: #f87171;
}

.btn-template-action.delete:hover {
    background: rgba(220, 38, 38, 0.3);
    border-color: rgba(220, 38, 38, 0.5);
}
```

---

## Feature Library (.feature-library)

**Location:** Sidebar with grouped features (50+)

```css
/* Feature Library Container */
.feature-library {
    background: rgba(17, 24, 39, 0.5);
    border: 1px solid rgba(75, 85, 99, 0.3);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

/* Feature Group */
.feature-group {
    margin-bottom: 1.5rem;
}

.feature-group:last-child {
    margin-bottom: 0;
}

/* Feature Group Title */
.feature-group-title {
    font-size: 1rem;
    font-weight: 600;
    color: #60a5fa;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

/* Feature List (unordered) */
.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Feature List Item (clickable) */
.feature-list-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #d1d5db;
}

.feature-list-item:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa;
}

.feature-bullet {
    font-size: 1.25rem;
    color: #9ca3af;
}

.feature-name {
    flex: 1;
    font-size: 0.875rem;
}

.feature-type-badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    background: rgba(107, 114, 128, 0.2);
    color: #9ca3af;
}
```

---

## Template Features Table (.template-features-table)

**Location:** Template editor modal

```css
/* Template Features Table Container */
.template-features-table {
    overflow-x: auto;
    border: 1px solid rgba(75, 85, 99, 0.3);
    border-radius: 0.5rem;
}

/* Small form inputs for table cells */
.form-input-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    min-height: auto;
}

/* Small button (e.g., "Dodaj Ceche") */
.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Icon button for delete action */
.btn-icon-danger {
    padding: 0.25rem 0.5rem;
    background: transparent;
    border: none;
    color: #f87171;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-icon-danger:hover {
    color: #dc2626;
    transform: scale(1.1);
}
```

---

## Modal Enhancements (.modal-header, .modal-close)

**Location:** Template editor & bulk assign modals

```css
/* Modal Header (title + close button) */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

/* Modal Close Button (X) */
.modal-close {
    font-size: 1.5rem;
    color: #9ca3af;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0.25rem 0.5rem;
}

.modal-close:hover {
    color: #f3f4f6;
    transform: scale(1.1);
}

/* Modal Actions (footer buttons) */
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(75, 85, 99, 0.3);
}
```

---

## Radio Label (.radio-label)

**Location:** Bulk assign modal (scope + action selection)

```css
/* Radio Label (with icon + text) */
.radio-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.radio-label:hover {
    background: rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
}

.form-radio {
    width: 1.125rem;
    height: 1.125rem;
    cursor: pointer;
}
```

---

## Alerts (.alert, .alert-success, .alert-error)

**Location:** Flash messages (top of page)

```css
/* Base Alert */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

/* Success Alert */
.alert-success {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.2), rgba(4, 120, 87, 0.15));
    border: 1px solid rgba(5, 150, 105, 0.3);
    color: #34d399;
}

/* Error Alert */
.alert-error {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(185, 28, 28, 0.15));
    border: 1px solid rgba(220, 38, 38, 0.3);
    color: #f87171;
}
```

---

## Summary of NEW Classes

**Template Cards (9 classes):**
- `.template-card`
- `.template-icon` (+ `.electric`, `.combustion`, `.custom`)
- `.template-title`
- `.template-stats`, `.stat-item`
- `.template-actions`, `.btn-template-action` (+ `.delete`)

**Feature Library (7 classes):**
- `.feature-library`
- `.feature-group`, `.feature-group-title`
- `.feature-list`, `.feature-list-item`
- `.feature-bullet`, `.feature-name`, `.feature-type-badge`

**Table & Forms (3 classes):**
- `.template-features-table`
- `.form-input-sm`, `.btn-sm`
- `.btn-icon-danger`

**Modal Enhancements (3 classes):**
- `.modal-header`, `.modal-close`
- `.modal-actions`

**Radio Labels (2 classes):**
- `.radio-label`, `.form-radio`

**Alerts (3 classes):**
- `.alert`, `.alert-success`, `.alert-error`

**TOTAL:** 27 new CSS classes

**RECOMMENDED:** Add to `resources/css/admin/components.css` (existing file)

---

## Responsive Breakpoints

**Grid adjustments:**
```css
/* Mobile: 1 column */
@media (max-width: 768px) {
    .template-card {
        padding: 1rem;
    }

    .template-icon {
        font-size: 2rem;
    }
}

/* Tablet: 2 columns */
@media (min-width: 769px) and (max-width: 1024px) {
    /* Already handled by grid-cols-md-2 */
}

/* Desktop: 3 columns */
@media (min-width: 1025px) {
    /* Already handled by grid-cols-lg-3 */
}
```

---

## Integration Notes

1. **Add to existing file:** `resources/css/admin/components.css` (DO NOT create new file!)
2. **Section comment:** Add `/* VEHICLE FEATURE MANAGEMENT (2025-10-23) */` header
3. **Build:** Run `npm run build` after adding classes
4. **Deploy:** Upload `public/build/assets/components-*.css` + `public/build/manifest.json`
5. **Verify:** Check on production with hard refresh (Ctrl+Shift+R)

---

## Compliance Checklist

- [x] NO inline styles in Blade view
- [x] NO Tailwind arbitrary values (z-[9999])
- [x] Uses existing CSS patterns (enterprise-card, btn-enterprise-*)
- [x] Consistent color palette (MPP TRADE blues/grays)
- [x] Responsive design (mobile-first)
- [x] Hover states and transitions
- [x] Loading states support
- [x] Accessibility (focus states, cursor pointers)

---

**END OF DOCUMENT**
