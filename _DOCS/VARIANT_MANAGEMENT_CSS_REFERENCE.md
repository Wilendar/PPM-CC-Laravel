# Variant Management CSS Reference

**File**: `resources/css/products/variant-management.css`
**Version**: 1.0.0
**Date**: 2025-10-30
**Lines**: 847
**Size**: 13.46 KB (2.53 KB gzipped)
**Compliance**: `_DOCS/UI_UX_STANDARDS_PPM.md` ✅

---

## Quick Start

### Include in Blade Layout
```blade
@vite([
    'resources/css/app.css',
    'resources/css/products/variant-management.css'
])
```

### Build Assets
```bash
npm run build
```

---

## CSS Classes by Component

### 1. Section Headers

```blade
<div class="variant-section-header">
    <h3>Variants <span class="badge">12</span></h3>
    <button class="variant-btn-primary">
        <i class="fas fa-plus"></i> Add Variant
    </button>
</div>
```

**Classes**:
- `.variant-section-header` - Main container
- `.variant-section-header h3` - Title styling
- `.variant-section-header .badge` - Count badge

---

### 2. Variant List Table

```blade
<table class="variant-list-table">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Attributes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="variant-sku">PRD-001-RED-L</td>
            <td>
                <div class="variant-attributes">
                    <span class="variant-attribute-badge">
                        <span class="badge-label">Color</span>
                        <span class="badge-value">Red</span>
                    </span>
                </div>
            </td>
            <td>
                <div class="variant-actions">
                    <button class="variant-action-btn">Edit</button>
                </div>
            </td>
        </tr>
    </tbody>
</table>
```

**Classes**:
- `.variant-list-table` - Table container
- `.variant-list-table th` - Table headers
- `.variant-list-table td` - Table cells
- `.variant-list-table tbody tr:hover` - Row hover (no transform!)

---

### 3. Variant Attributes & Status

```blade
{{-- Attributes --}}
<div class="variant-attributes">
    <span class="variant-attribute-badge">
        <span class="badge-label">Size</span>
        <span class="badge-value">Large</span>
    </span>
</div>

{{-- Default Badge --}}
<span class="variant-default-badge">
    <i class="fas fa-star"></i> Default
</span>

{{-- Active Status --}}
<span class="variant-status-active">Active</span>

{{-- Inactive Status --}}
<span class="variant-status-inactive">Inactive</span>
```

**Classes**:
- `.variant-attributes` - Container for badges
- `.variant-attribute-badge` - Individual badge
- `.variant-attribute-badge .badge-label` - Attribute name
- `.variant-attribute-badge .badge-value` - Attribute value
- `.variant-default-badge` - Default variant indicator
- `.variant-status-active` - Active status (green dot)
- `.variant-status-inactive` - Inactive status (red dot)

---

### 4. Price & Stock Display

```blade
{{-- Price --}}
<div class="variant-price">149.99 PLN</div>
<div class="variant-price-range">120 - 180 PLN</div>

{{-- Stock --}}
<div class="variant-stock">
    <span class="variant-stock-value">45</span>
    <span class="variant-stock-ok">OK</span>
</div>

{{-- Low Stock --}}
<div class="variant-stock">
    <span class="variant-stock-value">3</span>
    <span class="variant-stock-low">Low</span>
</div>
```

**Classes**:
- `.variant-price` - Price display (monospace)
- `.variant-price-range` - Price range (muted)
- `.variant-stock` - Stock container
- `.variant-stock-value` - Stock number (monospace)
- `.variant-stock-ok` - OK stock indicator
- `.variant-stock-low` - Low stock warning badge

---

### 5. Action Buttons

```blade
<div class="variant-actions">
    <button class="variant-action-btn">
        <i class="fas fa-edit"></i> Edit
    </button>
    <button class="variant-action-btn danger">
        <i class="fas fa-trash"></i> Delete
    </button>
</div>
```

**Classes**:
- `.variant-actions` - Container
- `.variant-action-btn` - Button styling
- `.variant-action-btn:hover` - Hover state (no transform!)
- `.variant-action-btn.danger:hover` - Danger hover

---

### 6. Modals

```blade
<div class="variant-modal-overlay">
    <div class="variant-modal">
        <div class="variant-modal-header">
            <h3>Create Variant</h3>
            <button class="variant-modal-close">&times;</button>
        </div>

        <div class="variant-modal-body">
            <h4 class="variant-modal-section-title">Basic Information</h4>
            <!-- Content -->
        </div>

        <div class="variant-modal-footer">
            <button class="variant-btn-secondary">Cancel</button>
            <button class="variant-btn-primary">Save</button>
        </div>
    </div>
</div>
```

**Classes**:
- `.variant-modal-overlay` - Backdrop (z-index: 50)
- `.variant-modal` - Modal container (max-width: 800px)
- `.variant-modal-header` - Header with title
- `.variant-modal-close` - Close button
- `.variant-modal-body` - Content area
- `.variant-modal-section-title` - Section titles
- `.variant-modal-footer` - Footer with buttons

---

### 7. Price Grid

```blade
<table class="variant-price-grid">
    <thead>
        <tr>
            <th>Warehouse</th>
            <th>Retail</th>
            <th>Dealer</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="row-header">MPPTRADE</td>
            <td>
                <input type="number" class="variant-price-input"
                       value="149.99" placeholder="0.00">
            </td>
            <td>
                <input type="number" class="variant-price-input"
                       value="120.00" placeholder="0.00">
            </td>
        </tr>
    </tbody>
</table>
```

**Classes**:
- `.variant-price-grid` - Table container
- `.variant-price-grid th` - Column headers
- `.variant-price-grid .row-header` - Row labels
- `.variant-price-input` - Editable price field (monospace, right-aligned)

---

### 8. Stock Grid

```blade
<table class="variant-stock-grid">
    <thead>
        <tr>
            <th>Warehouse</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="row-header">MPPTRADE</td>
            <td>
                <input type="number" class="variant-stock-input"
                       value="45" placeholder="0">
            </td>
        </tr>
    </tbody>
</table>
```

**Classes**:
- `.variant-stock-grid` - Table container
- `.variant-stock-grid th` - Column headers
- `.variant-stock-grid .row-header` - Row labels
- `.variant-stock-input` - Editable stock field (monospace, center-aligned)

---

### 9. Images Manager

```blade
{{-- Dropzone --}}
<div class="variant-images-dropzone">
    <i class="fas fa-cloud-upload-alt"></i>
    <p>Drag & drop images here</p>
    <p class="text-sm">or click to browse</p>
</div>

{{-- Images Grid --}}
<div class="variant-images-grid">
    <div class="variant-image-item variant-image-cover">
        <img src="image.jpg" alt="Variant">
        <div class="variant-image-actions">
            <button class="variant-image-btn">
                <i class="fas fa-star"></i>
            </button>
            <button class="variant-image-btn danger">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
```

**Classes**:
- `.variant-images-dropzone` - Upload area
- `.variant-images-dropzone:hover` - Hover state
- `.variant-images-dropzone.dragging` - Drag state
- `.variant-images-grid` - Thumbnails container (auto-fill, 140px)
- `.variant-image-item` - Individual image (aspect-ratio: 1)
- `.variant-image-cover` - Cover image indicator
- `.variant-image-actions` - Action buttons container
- `.variant-image-btn` - Action button (ALLOWED transform on hover - small element)
- `.variant-image-btn.danger:hover` - Delete hover

---

### 10. Form Elements

```blade
<div class="variant-form-group">
    <label class="variant-form-label">SKU</label>
    <input type="text" class="variant-form-input"
           placeholder="Enter SKU">
</div>

<div class="variant-form-group">
    <label class="variant-form-label">Attribute Type</label>
    <select class="variant-form-select">
        <option>Select...</option>
        <option>Color</option>
        <option>Size</option>
    </select>
</div>

<div class="variant-form-group">
    <label>
        <input type="checkbox" class="variant-form-checkbox">
        Set as default variant
    </label>
</div>
```

**Classes**:
- `.variant-form-group` - Form field container (margin-bottom: 20px)
- `.variant-form-label` - Label styling
- `.variant-form-input` - Text input
- `.variant-form-select` - Select dropdown
- `.variant-form-checkbox` - Checkbox styling

---

### 11. Standard Buttons

```blade
{{-- Primary (Orange) --}}
<button class="variant-btn-primary">
    <i class="fas fa-save"></i> Save
</button>

{{-- Secondary (Border) --}}
<button class="variant-btn-secondary">
    <i class="fas fa-times"></i> Cancel
</button>

{{-- Danger (Red) --}}
<button class="variant-btn-danger">
    <i class="fas fa-trash"></i> Delete
</button>
```

**Classes**:
- `.variant-btn-primary` - Orange button (main actions)
- `.variant-btn-secondary` - Border button (secondary actions)
- `.variant-btn-danger` - Red button (destructive actions)

**⚠️ NO TRANSFORM on hover** - Professional standard!

---

### 12. Utility Classes

```blade
{{-- Text Colors --}}
<span class="variant-text-muted">Muted text</span>
<span class="variant-text-success">Success text</span>
<span class="variant-text-error">Error text</span>

{{-- Divider --}}
<div class="variant-divider"></div>

{{-- Loading State --}}
<div class="variant-loading">
    <i class="fas fa-spinner fa-spin"></i>
</div>

{{-- Empty State --}}
<div class="variant-empty-state">
    <i class="fas fa-cube"></i>
    <p>No variants found</p>
    <p class="text-sm">Create your first variant</p>
</div>
```

**Classes**:
- `.variant-text-muted` - Muted text color
- `.variant-text-success` - Success color
- `.variant-text-error` - Error color
- `.variant-divider` - Horizontal line (margin: 24px 0)
- `.variant-loading` - Loading spinner
- `.variant-empty-state` - Empty state container

---

## Color Palette

### CSS Variables Available

```css
/* Primary Actions - Orange */
--color-primary: #f97316;
--color-primary-hover: #ea580c;

/* Secondary Actions - Blue */
--color-secondary: #3b82f6;
--color-secondary-hover: #2563eb;

/* Status Colors */
--color-success: #10b981;
--color-danger: #ef4444;

/* Backgrounds */
--color-bg-primary: #0f172a;     /* Main background */
--color-bg-secondary: #1e293b;   /* Cards/Panels */
--color-bg-tertiary: #334155;    /* Hover states */
--color-bg-hover: rgba(255, 255, 255, 0.05);

/* Text */
--color-text-primary: #f8fafc;   /* Main text */
--color-text-secondary: #cbd5e1; /* Secondary text */
--color-text-muted: #94a3b8;     /* Muted text */

/* Borders */
--color-border: #334155;
--color-border-light: #475569;
```

---

## Responsive Breakpoints

### Desktop (Default)
- Container max-width: 800px (modals)
- Image grid: 140px columns
- Table font: 14px

### Tablet (≤1024px)
- Modal max-width: 95%
- Image grid: 120px columns
- Actions: column layout

### Mobile (≤768px)
- Modal: full-screen
- Table font: 12px
- Image grid: 100px columns
- Section header: column layout

---

## PPM Standards Compliance

### ✅ MANDATORY Requirements Met

1. **Spacing**:
   - ✅ Cards: 24px padding
   - ✅ Form groups: 20px margin-bottom
   - ✅ Grid gaps: 16px minimum
   - ✅ Section spacing: 24px

2. **Colors**:
   - ✅ High contrast palette
   - ✅ Primary: Orange (#f97316)
   - ✅ Secondary: Blue (#3b82f6)
   - ✅ Success: Green (#10b981)
   - ✅ Danger: Red (#ef4444)

3. **Button Hierarchy**:
   - ✅ Primary: Orange, solid
   - ✅ Secondary: Blue, border
   - ✅ Danger: Red, solid

4. **Hover Effects**:
   - ✅ NO TRANSFORM on cards/panels
   - ✅ ONLY border/shadow changes
   - ✅ Exception: Small buttons (<48px) CAN have scale

5. **Typography**:
   - ✅ Line-height: 1.4-1.6
   - ✅ Proper margins (12-16px)
   - ✅ Monospace for SKU/prices

6. **Accessibility**:
   - ✅ Focus states defined
   - ✅ Sufficient contrast
   - ✅ Hover feedback

---

## Common Patterns

### Complete Variant Row Example
```blade
<tr>
    <td class="variant-sku">PRD-001-RED-L</td>
    <td>
        <div class="variant-attributes">
            <span class="variant-attribute-badge">
                <span class="badge-label">Color</span>
                <span class="badge-value">Red</span>
            </span>
            <span class="variant-attribute-badge">
                <span class="badge-label">Size</span>
                <span class="badge-value">Large</span>
            </span>
        </div>
        @if($variant->is_default)
            <span class="variant-default-badge">Default</span>
        @endif
    </td>
    <td class="variant-price">149.99 PLN</td>
    <td>
        <div class="variant-stock">
            <span class="variant-stock-value">{{ $stock }}</span>
            @if($stock < 10)
                <span class="variant-stock-low">Low</span>
            @else
                <span class="variant-stock-ok">OK</span>
            @endif
        </div>
    </td>
    <td>
        <span class="variant-status-{{ $variant->is_active ? 'active' : 'inactive' }}">
            {{ $variant->is_active ? 'Active' : 'Inactive' }}
        </span>
    </td>
    <td>
        <div class="variant-actions">
            <button wire:click="editVariant({{ $variant->id }})"
                    class="variant-action-btn">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button wire:click="deleteVariant({{ $variant->id }})"
                    class="variant-action-btn danger">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </td>
</tr>
```

### Complete Modal Example
```blade
<div class="variant-modal-overlay"
     x-show="showModal"
     @click.self="showModal = false">
    <div class="variant-modal">
        <div class="variant-modal-header">
            <h3>Create Variant</h3>
            <button @click="showModal = false"
                    class="variant-modal-close">&times;</button>
        </div>

        <div class="variant-modal-body">
            <h4 class="variant-modal-section-title">Basic Information</h4>

            <div class="variant-form-group">
                <label class="variant-form-label">SKU</label>
                <input type="text" class="variant-form-input"
                       wire:model="sku" placeholder="PRD-001-VAR">
            </div>

            <div class="variant-divider"></div>

            <h4 class="variant-modal-section-title">Attributes</h4>
            <!-- Attribute selection -->
        </div>

        <div class="variant-modal-footer">
            <button @click="showModal = false"
                    class="variant-btn-secondary">Cancel</button>
            <button wire:click="saveVariant"
                    class="variant-btn-primary">
                <i class="fas fa-save"></i> Save Variant
            </button>
        </div>
    </div>
</div>
```

---

## Troubleshooting

### Issue: Styles not loading
**Solution**:
1. Check `@vite()` includes `variant-management.css`
2. Run `npm run build`
3. Upload ALL assets (not just variant-management)
4. Upload manifest to ROOT: `public/build/manifest.json`
5. Clear caches: `php artisan view:clear && cache:clear`

### Issue: Modal backdrop not working
**Solution**:
- Ensure `.variant-modal-overlay` has `z-index: 50`
- Check Alpine.js `x-show` directive
- Verify `@click.self` on overlay for close behavior

### Issue: Buttons too small on mobile
**Solution**:
- Responsive styles already included
- Check viewport meta tag in layout
- Verify media query `@media (max-width: 768px)` loaded

---

## Performance Notes

- **File Size**: 13.46 KB (2.53 KB gzipped) - Excellent compression ratio
- **Build Time**: ~2.4s with all assets
- **CSS Specificity**: Low (single class names, no deep nesting)
- **Browser Support**: Modern browsers (CSS Grid, flexbox, CSS variables)

---

## Related Documentation

- `_DOCS/UI_UX_STANDARDS_PPM.md` - PPM UI/UX compliance rules
- `_DOCS/ARCHITEKTURA_STYLOW_PPM.md` - CSS architecture guide
- `_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md` - Deployment considerations
- `_AGENT_REPORTS/frontend_specialist_phase6_variant_css_2025-10-30.md` - Detailed agent report

---

**Last Updated**: 2025-10-30
**Maintained By**: frontend-specialist agent
**Status**: ✅ Production Ready
