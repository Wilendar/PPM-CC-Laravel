# ETAP_06 FAZA 2: Import Panel - UI Design Specifications

**Date:** 2025-12-08
**Status:** Design Phase
**Route:** `/admin/products/import`

---

## 🎨 DESIGN PHILOSOPHY

**Cel:** Enterprise-grade import panel z clear status indicators, inline editing, multi-action toolbar

**Zasady:**
- ✅ High contrast colors (PPM palette)
- ✅ Generous spacing (min 20px padding, 16px gaps)
- ✅ Clear button hierarchy (Orange primary, Blue secondary)
- ✅ NO hover transforms (only border/shadow changes)
- ✅ Sticky columns (checkbox + actions)
- ✅ Responsive (horizontal scroll mobile)

---

## 📐 LAYOUT STRUCTURE

### Page Container

```html
<div class="min-h-screen bg-main-gradient">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-40 glass-effect border-b border-primary shadow-lg">
        <!-- Header content -->
    </div>

    <!-- Main Content -->
    <div class="px-6 lg:px-8 py-8">
        <!-- Toolbar -->
        <!-- Filters -->
        <!-- Table -->
        <!-- Pagination -->
    </div>
</div>
```

**Spacing:**
- Page padding: `32px 24px` (desktop), `24px 16px` (mobile)
- Section gaps: `24px` between toolbar/filters/table

---

## 🔧 TOOLBAR DESIGN

### Toolbar Structure

```html
<div class="enterprise-card mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Left: Actions -->
        <div class="flex flex-wrap gap-3">
            <!-- Paste SKU (Primary) -->
            <button class="btn-enterprise-primary">
                <svg>...</svg> Wklej SKU
            </button>

            <!-- Import CSV (Secondary) -->
            <button class="btn-enterprise-secondary">
                <svg>...</svg> Import CSV
            </button>

            <!-- Bulk Publish (Success - conditional) -->
            <button class="btn-enterprise-success" x-show="selectedCount > 0">
                <svg>...</svg> Publikuj zaznaczone (<span x-text="selectedCount"></span>)
            </button>

            <!-- Bulk Delete (Danger - conditional) -->
            <button class="btn-enterprise-danger" x-show="selectedCount > 0">
                <svg>...</svg> Usuń zaznaczone
            </button>
        </div>

        <!-- Right: Stats -->
        <div class="flex items-center gap-6 text-sm">
            <div class="flex items-center gap-2">
                <span class="text-gray-400">Pending:</span>
                <span class="font-semibold text-white">{{ $pendingCount }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-400">Gotowe:</span>
                <span class="font-semibold text-emerald-400">{{ $readyCount }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-400">Błędy:</span>
                <span class="font-semibold text-red-400">{{ $errorCount }}</span>
            </div>
        </div>
    </div>
</div>
```

**Button Classes:**
- Primary (Orange): `.btn-enterprise-primary` - main actions
- Secondary (Blue): `.btn-enterprise-secondary` - secondary actions
- Success (Green): `.btn-enterprise-success` - publish/confirm
- Danger (Red): `.btn-enterprise-danger` - delete/destructive

---

## 🔍 FILTERS PANEL

### Filter Bar Structure

```html
<div class="enterprise-card mb-6" x-show="showFilters">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="form-group-import">
            <label class="form-label-import">Wyszukaj</label>
            <input type="text"
                   placeholder="SKU, nazwa..."
                   class="form-input-import">
        </div>

        <!-- Status Filter -->
        <div class="form-group-import">
            <label class="form-label-import">Status</label>
            <select class="form-select-import">
                <option value="">Wszystkie</option>
                <option value="incomplete">Niekompletne</option>
                <option value="ready">Gotowe</option>
                <option value="published">Opublikowane</option>
                <option value="error">Błędy</option>
            </select>
        </div>

        <!-- Product Type -->
        <div class="form-group-import">
            <label class="form-label-import">Typ produktu</label>
            <select class="form-select-import">
                <option value="">Wszystkie typy</option>
                <option value="vehicle">Pojazd</option>
                <option value="spare_part">Część zamienna</option>
                <option value="accessory">Akcesoria</option>
            </select>
        </div>

        <!-- Import Session -->
        <div class="form-group-import">
            <label class="form-label-import">Sesja importu</label>
            <select class="form-select-import">
                <option value="">Wszystkie sesje</option>
                @foreach($importSessions as $session)
                    <option value="{{ $session->id }}">
                        {{ $session->created_at->format('Y-m-d H:i') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Filter Actions -->
    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-primary">
        <button class="btn-enterprise-secondary-sm" wire:click="resetFilters">
            Wyczyść filtry
        </button>
        <button class="btn-enterprise-primary-sm" wire:click="applyFilters">
            Zastosuj
        </button>
    </div>
</div>
```

**Form Spacing:**
- Form group margin: `20px` bottom
- Input padding: `12px 16px`
- Label margin: `8px` bottom
- Filter button gap: `12px`

---

## 📊 TABLE DESIGN

### Table Structure

```html
<div class="enterprise-card overflow-hidden">
    <!-- Table Container (horizontal scroll mobile) -->
    <div class="overflow-x-auto">
        <table class="import-table">
            <thead class="import-table-header">
                <tr>
                    <!-- Sticky Checkbox Column -->
                    <th class="import-th-sticky-left">
                        <input type="checkbox"
                               x-model="selectAll"
                               class="import-checkbox">
                    </th>

                    <!-- Columns -->
                    <th class="import-th">Miniaturka</th>
                    <th class="import-th">SKU</th>
                    <th class="import-th">Nazwa</th>
                    <th class="import-th">Typ</th>
                    <th class="import-th">Kategorie</th>
                    <th class="import-th">Warianty</th>
                    <th class="import-th">Cechy</th>
                    <th class="import-th">Dopasowania</th>
                    <th class="import-th">Sklepy</th>
                    <th class="import-th">Gotowość</th>

                    <!-- Sticky Actions Column -->
                    <th class="import-th-sticky-right">Akcje</th>
                </tr>
            </thead>

            <tbody class="import-table-body">
                <!-- Row -->
                <tr class="import-row" x-data="{ editing: false }">
                    <!-- Checkbox (sticky) -->
                    <td class="import-td-sticky-left">
                        <input type="checkbox"
                               value="{{ $product->id }}"
                               x-model="selected"
                               class="import-checkbox">
                    </td>

                    <!-- Thumbnail -->
                    <td class="import-td">
                        <div class="import-thumbnail">
                            @if($product->thumbnail)
                                <img src="{{ $product->thumbnail }}"
                                     alt="{{ $product->sku }}"
                                     class="import-thumbnail-img">
                            @else
                                <div class="import-thumbnail-placeholder">
                                    <svg>...</svg>
                                </div>
                            @endif
                        </div>
                    </td>

                    <!-- SKU (editable inline) -->
                    <td class="import-td">
                        <div x-show="!editing" class="import-cell-view">
                            <span class="import-sku">{{ $product->sku }}</span>
                            <button @click="editing = true" class="import-edit-icon">
                                <svg>...</svg>
                            </button>
                        </div>
                        <div x-show="editing" class="import-cell-edit">
                            <input type="text"
                                   value="{{ $product->sku }}"
                                   class="import-input-inline">
                            <button @click="editing = false" class="import-save-icon">
                                <svg>...</svg>
                            </button>
                        </div>
                    </td>

                    <!-- Name (editable inline, truncate) -->
                    <td class="import-td">
                        <div class="import-name-cell">
                            <span class="import-name-truncate">{{ $product->name }}</span>
                            <button class="import-edit-icon">
                                <svg>...</svg>
                            </button>
                        </div>
                    </td>

                    <!-- Product Type (dropdown select) -->
                    <td class="import-td">
                        <select class="import-select-inline" wire:model="product.type">
                            <option value="vehicle">Pojazd</option>
                            <option value="spare_part">Część</option>
                            <option value="accessory">Akcesorium</option>
                        </select>
                    </td>

                    <!-- Categories (path + click to edit) -->
                    <td class="import-td">
                        <button class="import-category-cell" wire:click="editCategories({{ $product->id }})">
                            <div class="import-category-path">
                                {{ $product->categoryPath ?? 'Nie przypisano' }}
                            </div>
                            <svg class="import-category-icon">...</svg>
                        </button>
                    </td>

                    <!-- Variants (badge) -->
                    <td class="import-td">
                        @if($product->is_variant_master)
                            <span class="import-badge-master">Master</span>
                        @elseif($product->variants_count > 0)
                            <span class="import-badge-variants">
                                {{ $product->variants_count }} wariantów
                            </span>
                        @else
                            <span class="import-badge-none">-</span>
                        @endif
                    </td>

                    <!-- Features (icon + count) -->
                    <td class="import-td">
                        <button class="import-icon-badge" wire:click="editFeatures({{ $product->id }})">
                            <svg class="w-4 h-4">...</svg>
                            <span>{{ $product->features_count }}</span>
                        </button>
                    </td>

                    <!-- Compatibility (icon + count) -->
                    <td class="import-td">
                        <button class="import-icon-badge" wire:click="editCompatibility({{ $product->id }})">
                            <svg class="w-4 h-4">...</svg>
                            <span>{{ $product->compatibility_count }}</span>
                        </button>
                    </td>

                    <!-- Shops (mini tiles) -->
                    <td class="import-td">
                        <div class="import-shop-tiles">
                            @foreach($product->shops as $shop)
                                <div class="import-shop-tile"
                                     style="background: {{ $shop->color }};"
                                     title="{{ $shop->name }}">
                                    {{ substr($shop->name, 0, 2) }}
                                </div>
                            @endforeach
                        </div>
                    </td>

                    <!-- Readiness (progress bar) -->
                    <td class="import-td">
                        <div class="import-progress-container">
                            <div class="import-progress-bar"
                                 style="width: {{ $product->readiness }}%;"
                                 :class="{
                                     'bg-emerald-500': {{ $product->readiness }} === 100,
                                     'bg-orange-500': {{ $product->readiness }} >= 50 && {{ $product->readiness }} < 100,
                                     'bg-red-500': {{ $product->readiness }} < 50
                                 }">
                            </div>
                        </div>
                        <span class="import-progress-text">{{ $product->readiness }}%</span>
                    </td>

                    <!-- Actions (sticky) -->
                    <td class="import-td-sticky-right">
                        <div class="import-actions">
                            <button class="import-action-btn import-action-edit"
                                    wire:click="edit({{ $product->id }})">
                                <svg>...</svg>
                            </button>
                            <button class="import-action-btn import-action-delete"
                                    wire:click="delete({{ $product->id }})">
                                <svg>...</svg>
                            </button>
                            @if($product->readiness === 100)
                                <button class="import-action-btn import-action-publish"
                                        wire:click="publish({{ $product->id }})">
                                    <svg>...</svg>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="import-pagination">
        {{ $products->links() }}
    </div>
</div>
```

---

## 🎨 CSS CLASSES (dodaj do resources/css/admin/components.css)

```css
/* ========================================
   IMPORT PANEL - TABLE & FORMS (ETAP_06 FAZA 2)
   ======================================== */

/* === FORM COMPONENTS === */

.form-group-import {
    margin-bottom: 20px; /* Proper spacing */
}

.form-label-import {
    display: block;
    margin-bottom: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #cbd5e1; /* Slate-300 - readable */
}

.form-input-import,
.form-select-import {
    width: 100%;
    padding: 12px 16px; /* Generous padding */
    background: #1e293b; /* Slate-800 */
    border: 2px solid #334155; /* Slate-700 - clear border */
    border-radius: 8px;
    color: #f8fafc; /* Slate-50 */
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-input-import:focus,
.form-select-import:focus {
    border-color: #f97316; /* Orange accent */
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.form-input-import::placeholder {
    color: #64748b; /* Slate-500 */
}

/* === TABLE COMPONENTS === */

.import-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.import-table-header {
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95));
    border-bottom: 2px solid rgba(224, 172, 126, 0.2);
}

.import-th {
    padding: 16px 20px; /* Generous padding */
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #cbd5e1; /* Slate-300 */
    white-space: nowrap;
}

.import-th-sticky-left {
    position: sticky;
    left: 0;
    z-index: 20;
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.98));
    padding: 16px 20px;
    border-right: 1px solid rgba(224, 172, 126, 0.15);
}

.import-th-sticky-right {
    position: sticky;
    right: 0;
    z-index: 20;
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.98));
    padding: 16px 20px;
    border-left: 1px solid rgba(224, 172, 126, 0.15);
    text-align: center;
}

.import-table-body {
    background: #0f172a; /* Slate-900 */
}

.import-row {
    border-bottom: 1px solid rgba(224, 172, 126, 0.1);
    transition: background 0.2s ease;
}

.import-row:hover {
    background: rgba(255, 255, 255, 0.02); /* Subtle highlight */
    /* NO transform! */
}

.import-td {
    padding: 16px 20px; /* Matching header padding */
    color: #f8fafc; /* Slate-50 */
    vertical-align: middle;
}

.import-td-sticky-left {
    position: sticky;
    left: 0;
    z-index: 10;
    background: #0f172a;
    padding: 16px 20px;
    border-right: 1px solid rgba(224, 172, 126, 0.1);
}

.import-row:hover .import-td-sticky-left {
    background: rgba(255, 255, 255, 0.02);
}

.import-td-sticky-right {
    position: sticky;
    right: 0;
    z-index: 10;
    background: #0f172a;
    padding: 16px 20px;
    border-left: 1px solid rgba(224, 172, 126, 0.1);
}

.import-row:hover .import-td-sticky-right {
    background: rgba(255, 255, 255, 0.02);
}

/* === CHECKBOX === */

.import-checkbox {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 2px solid #475569; /* Slate-600 */
    background: #1e293b; /* Slate-800 */
    cursor: pointer;
    transition: all 0.2s ease;
}

.import-checkbox:checked {
    background: #f97316; /* Orange-500 */
    border-color: #f97316;
}

.import-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

/* === THUMBNAIL === */

.import-thumbnail {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    background: #1e293b;
    border: 1px solid rgba(224, 172, 126, 0.2);
}

.import-thumbnail-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.import-thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b; /* Slate-500 */
}

/* === INLINE EDITING === */

.import-cell-view {
    display: flex;
    align-items: center;
    gap: 8px;
}

.import-sku {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #f8fafc;
}

.import-edit-icon,
.import-save-icon {
    padding: 4px;
    border-radius: 4px;
    color: #94a3b8; /* Slate-400 */
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.import-edit-icon:hover,
.import-save-icon:hover {
    color: #f97316; /* Orange-500 */
    background: rgba(249, 115, 22, 0.1);
    transform: scale(1.1); /* Small icons OK */
}

.import-input-inline {
    width: 100%;
    padding: 6px 10px;
    background: #1e293b;
    border: 2px solid #f97316; /* Orange - editing state */
    border-radius: 6px;
    color: #f8fafc;
    font-size: 14px;
    font-family: 'Courier New', monospace;
}

.import-input-inline:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

/* === NAME CELL === */

.import-name-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: 300px; /* Limit width */
}

.import-name-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}

/* === SELECT INLINE === */

.import-select-inline {
    padding: 8px 12px;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 6px;
    color: #f8fafc;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.import-select-inline:hover {
    border-color: #475569;
}

.import-select-inline:focus {
    border-color: #f97316;
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

/* === CATEGORY CELL === */

.import-category-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 6px;
    color: #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
    max-width: 250px;
}

.import-category-cell:hover {
    background: rgba(249, 115, 22, 0.05);
    border-color: #f97316;
}

.import-category-path {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    text-align: left;
    font-size: 13px;
}

.import-category-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    color: #94a3b8;
}

/* === BADGES === */

.import-badge-master {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.15));
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 9999px;
    color: #60a5fa; /* Blue-400 */
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.import-badge-variants {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(147, 51, 234, 0.15));
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 9999px;
    color: #c084fc; /* Purple-400 */
    font-size: 0.75rem;
    font-weight: 600;
}

.import-badge-none {
    color: #64748b; /* Slate-500 */
    font-size: 0.875rem;
}

/* === ICON BADGE === */

.import-icon-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 6px;
    color: #cbd5e1;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.import-icon-badge:hover {
    background: rgba(249, 115, 22, 0.05);
    border-color: #f97316;
    color: #f97316;
}

/* === SHOP TILES === */

.import-shop-tiles {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.import-shop-tile {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 0.625rem;
    font-weight: 700;
    color: #ffffff;
    text-transform: uppercase;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* === PROGRESS BAR === */

.import-progress-container {
    position: relative;
    width: 100%;
    height: 8px;
    background: #1e293b;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.import-progress-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.import-progress-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: #cbd5e1;
}

/* === ACTION BUTTONS === */

.import-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.import-action-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.import-action-edit {
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa; /* Blue-400 */
}

.import-action-edit:hover {
    background: rgba(59, 130, 246, 0.2);
    transform: scale(1.1); /* Small buttons OK */
}

.import-action-delete {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171; /* Red-400 */
}

.import-action-delete:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: scale(1.1);
}

.import-action-publish {
    background: rgba(16, 185, 129, 0.1);
    color: #34d399; /* Emerald-400 */
}

.import-action-publish:hover {
    background: rgba(16, 185, 129, 0.2);
    transform: scale(1.1);
}

/* === PAGINATION === */

.import-pagination {
    padding: 20px 24px;
    border-top: 1px solid rgba(224, 172, 126, 0.1);
    display: flex;
    justify-content: center;
}

/* === RESPONSIVE === */

@media (max-width: 768px) {
    .import-th,
    .import-td {
        padding: 12px 16px; /* Slightly reduced on mobile */
    }

    .import-name-cell {
        max-width: 200px;
    }

    .import-category-cell {
        max-width: 180px;
    }
}
```

---

## 🎨 STATUS COLORS

### Readiness Progress Colors

```css
/* Extracted from progress bar classes */

/* Ready (100%) - Green */
.bg-emerald-500 {
    background: #10b981;
}

/* In Progress (50-99%) - Orange */
.bg-orange-500 {
    background: #f97316;
}

/* Incomplete (<50%) - Red */
.bg-red-500 {
    background: #ef4444;
}
```

### Status Filter Colors

```html
<!-- Incomplete (Yellow/Orange) -->
<option value="incomplete" class="text-orange-400">Niekompletne</option>

<!-- Ready (Green) -->
<option value="ready" class="text-emerald-400">Gotowe</option>

<!-- Published (Blue) -->
<option value="published" class="text-blue-400">Opublikowane</option>

<!-- Error (Red) -->
<option value="error" class="text-red-400">Błędy</option>
```

---

## 🔘 BUTTON CLASSES (existing in components.css)

### Primary Actions

```css
.btn-enterprise-primary {
    background: linear-gradient(135deg, #f97316, #ea580c); /* Orange gradient */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease;
}

.btn-enterprise-primary:hover {
    background: linear-gradient(135deg, #ea580c, #dc2626);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    /* NO transform! */
}
```

### Secondary Actions

```css
.btn-enterprise-secondary {
    background: transparent;
    color: #3b82f6; /* Blue-500 */
    padding: 10px 20px;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-enterprise-secondary:hover {
    background: rgba(59, 130, 246, 0.1);
    border-color: #2563eb;
}
```

### Success Actions

```css
.btn-enterprise-success {
    background: linear-gradient(135deg, #10b981, #059669); /* Emerald gradient */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.btn-enterprise-success:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}
```

### Danger Actions

```css
.btn-enterprise-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626); /* Red gradient */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.btn-enterprise-danger:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}
```

### Small Button Variants

```css
.btn-enterprise-primary-sm,
.btn-enterprise-secondary-sm {
    padding: 8px 16px; /* Smaller padding */
    font-size: 0.875rem;
}
```

---

## 🖼️ ICON PATTERNS

### SVG Icons (reuse existing)

```html
<!-- Edit Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
</svg>

<!-- Delete Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
</svg>

<!-- Publish Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>

<!-- Category Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
</svg>

<!-- Features Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
</svg>

<!-- Compatibility Icon (Car) -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
</svg>

<!-- Thumbnail Placeholder Icon -->
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
</svg>

<!-- Chevron Right (Category path) -->
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5l7 7-7 7" />
</svg>
```

---

## ✅ IMPLEMENTATION CHECKLIST

### Before Implementation:

- [ ] CSS added to `resources/css/admin/components.css` (istniejący plik!)
- [ ] All classes use PPM color palette (Orange/Blue/Green/Red)
- [ ] No inline styles in Blade templates
- [ ] No arbitrary Tailwind z-index (`z-[9999]`)
- [ ] All spacing follows 8px grid (20px+ padding, 16px+ gaps)
- [ ] NO hover transforms on table rows/cards
- [ ] Button hierarchy clear (Orange primary, Blue secondary)
- [ ] Sticky columns tested on mobile (horizontal scroll)
- [ ] Progress bar colors conditional (100%=green, 50-99%=orange, <50%=red)

### Development:

- [ ] Livewire component: `app/Http/Livewire/Admin/Products/ImportPanel.php`
- [ ] Blade view: `resources/views/livewire/admin/products/import-panel.blade.php`
- [ ] Route: `Route::get('/admin/products/import', ImportPanel::class)`
- [ ] Alpine.js for: inline editing, checkboxes, filter toggle
- [ ] Vite imports CSS: `@vite(['resources/css/admin/components.css'])`

### Testing:

- [ ] Build: `npm run build`
- [ ] Deploy CSS: `pscp public/build/assets/components-*.css`
- [ ] Clear cache: `php artisan view:clear && cache:clear`
- [ ] HTTP 200 verification dla CSS
- [ ] Chrome DevTools MCP verification
- [ ] Screenshot: `_TOOLS/screenshots/import_panel_verification.jpg`

---

## 📖 REFERENCES

**Existing Components:**
- `resources/views/livewire/products/listing/product-list.blade.php` - Table structure
- `resources/css/admin/components.css` - Enterprise cards, buttons
- `_DOCS/UI_UX_STANDARDS_PPM.md` - Full styling standards (580 lines)

**Anti-Patterns (FORBIDDEN):**
- ❌ `style="z-index: 9999;"` → Use CSS classes
- ❌ `class="z-[9999]"` → Use CSS classes
- ❌ `.import-row:hover { transform: translateY(-2px); }` → Border/shadow only
- ❌ `padding: 8px;` → Min 20px for cards
- ❌ `gap: 4px;` → Min 16px for grids

**Success Patterns:**
- ✅ CSS classes in dedicated files
- ✅ PPM color tokens (`#f97316`, `#3b82f6`, etc.)
- ✅ Generous spacing (20px+ padding)
- ✅ Clear button hierarchy
- ✅ High contrast colors

---

**NEXT STEPS:**

1. Review this spec with user
2. Create Livewire component structure
3. Implement Blade template
4. Add CSS to `components.css`
5. Test + verify with Chrome DevTools
6. Deploy to production

**STATUS:** 🎨 Design Complete - Ready for Implementation
