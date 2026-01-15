# Vehicle Compatibility Tiles - CSS Design Guide

**Date:** 2025-12-05
**ETAP:** ETAP_05d FAZA 3
**Component:** Tile-Based Vehicle Selection System
**CSS Location:** `resources/css/admin/components.css` (lines 7013-7671)

---

## 📋 Przegląd

System kafelkowy (tile-based) do wybierania dopasowań pojazdów dla produktów. Design oparty na PPM styling standards z dark theme, glass morphism i enterprise patterns.

---

## 🎨 Kluczowe Komponenty CSS

### 1. **Main Panel Container**
```html
<div class="vehicle-compatibility-panel">
    <!-- Main content -->
</div>
```

**Styl:**
- Dark glass morphism background
- Orange border accent (#e0ac7e)
- Responsive padding
- Smooth scrolling z custom scrollbar

---

### 2. **Layout Grid (z Suggestions Sidebar)**
```html
<div class="vehicle-compatibility-layout with-suggestions">
    <!-- Main content area -->
    <div>
        <!-- Vehicle tiles grid here -->
    </div>

    <!-- Suggestions sidebar (optional) -->
    <aside class="suggestions-panel">
        <!-- AI suggestions -->
    </aside>
</div>
```

**Breakpoints:**
- Mobile/Tablet: Single column (1fr)
- Desktop (1280px+): Two columns (1fr 320px)

---

### 3. **Vehicle Tiles Grid**
```html
<div class="vehicle-brand-section">
    <!-- Brand header (sticky) -->
    <div class="vehicle-brand-header">
        <img src="logo.png" alt="Brand" class="vehicle-brand-logo">
        <h3 class="vehicle-brand-name">TOYOTA</h3>
        <span class="vehicle-brand-count">24</span>
    </div>

    <!-- Tiles grid -->
    <div class="vehicle-tile-grid">
        <!-- Individual tiles -->
    </div>
</div>
```

**Grid Properties:**
- Auto-fill columns: min 120px, max 1fr
- Gap: 0.75rem (desktop), 0.5rem (mobile)
- Responsive: 100px min on mobile

---

### 4. **Individual Vehicle Tile**
```html
<!-- Default state -->
<div class="vehicle-tile" data-vehicle-id="123">
    <span class="vehicle-tile-model">Corolla</span>
    <span class="vehicle-tile-year">2020-2023</span>
</div>

<!-- Selected as Oryginał (Orange) -->
<div class="vehicle-tile selected-original" data-vehicle-id="456">
    <span class="vehicle-tile-model">Camry</span>
    <span class="vehicle-tile-year">2018-2022</span>
</div>

<!-- Selected as Zamiennik (Blue) -->
<div class="vehicle-tile selected-replacement" data-vehicle-id="789">
    <span class="vehicle-tile-model">RAV4</span>
    <span class="vehicle-tile-year">2019-2024</span>
</div>

<!-- With AI confidence badge -->
<div class="vehicle-tile" data-vehicle-id="101">
    <span class="vehicle-tile-confidence high">95</span>
    <span class="vehicle-tile-model">Yaris</span>
    <span class="vehicle-tile-year">2021-2024</span>
</div>

<!-- Loading state -->
<div class="vehicle-tile loading" data-vehicle-id="102">
    <span class="vehicle-tile-model">Prius</span>
    <span class="vehicle-tile-year">2020-2023</span>
</div>
```

**Visual States:**
- **Default**: Dark glass, gray border
- **Hover**: Orange border glow, box shadow
- **Selected Original**: Orange left border (4px), orange background tint
- **Selected Replacement**: Blue left border (4px), blue background tint
- **Loading**: Opacity 0.6 + spinning loader

**AI Confidence Badge:**
- `high`: Green (>=75%) - #34d399
- `medium`: Yellow (>=50%) - #fbbf24
- `low`: Red (<50%) - #f87171

---

### 5. **Bulk Action Bar (Fixed Bottom)**
```html
<div class="bulk-action-bar visible">
    <div class="bulk-action-info">
        <!-- Selection count -->
        <div class="bulk-selection-count">
            <span class="bulk-selection-count-number">12</span>
            <span class="bulk-selection-count-label">zaznaczonych</span>
        </div>

        <!-- Shop selector -->
        <select class="bulk-shop-selector">
            <option value="">Wszystkie sklepy</option>
            <option value="1">B2B Test DEV</option>
            <option value="2">B2C Retail</option>
        </select>
    </div>

    <div class="bulk-action-buttons">
        <button class="btn-bulk-action btn-bulk-original">
            <svg>...</svg>
            Dodaj Oryginał
        </button>
        <button class="btn-bulk-action btn-bulk-replacement">
            <svg>...</svg>
            Dodaj Zamiennik
        </button>
        <button class="btn-bulk-action btn-bulk-remove">
            <svg>...</svg>
            Usuń
        </button>
        <button class="btn-bulk-action btn-bulk-verify">
            <svg>...</svg>
            Weryfikuj
        </button>
    </div>
</div>
```

**Behavior:**
- Fixed bottom (z-index: 50)
- Hidden by default (`transform: translateY(100%)`)
- Visible when class `.visible` added (slides up)
- Glass morphism background z orange border top
- Responsive: stacks vertically on mobile

**Button Variants:**
- `btn-bulk-original`: Orange gradient (#e0ac7e → #d1975a)
- `btn-bulk-replacement`: Blue gradient (#2563eb → #1d4ed8)
- `btn-bulk-remove`: Red gradient (#ef4444 → #dc2626)
- `btn-bulk-verify`: Transparent + gray border

---

### 6. **Smart Suggestions Panel (Sidebar)**
```html
<aside class="suggestions-panel">
    <!-- Header -->
    <div class="suggestions-panel-header">
        <h3 class="suggestions-panel-title">
            <svg class="suggestions-panel-icon">...</svg>
            Sugestie AI
        </h3>
        <button class="suggestions-toggle">
            <svg>...</svg>
        </button>
    </div>

    <!-- Suggestion items -->
    <div class="suggestion-item">
        <div class="suggestion-item-header">
            <span class="suggestion-item-model">Honda Civic</span>
            <span class="suggestion-confidence-badge high">
                <svg>...</svg>
                89%
            </span>
        </div>
        <p class="suggestion-reason">
            Pasuje do kategorii "Compact Cars" i podobnego zakresu lat produkcji
        </p>
        <div class="suggestion-actions">
            <button class="btn-suggestion-apply">Zastosuj</button>
            <button class="btn-suggestion-dismiss">Odrzuć</button>
        </div>
    </div>
</aside>
```

**Features:**
- Collapsible sidebar (320px width on desktop)
- Max height 600px z scroll
- Confidence badges: high/medium/low (color-coded)
- Quick apply/dismiss actions per suggestion

---

### 7. **Empty State**
```html
<div class="vehicle-compatibility-empty">
    <svg class="vehicle-compatibility-empty-icon">
        <!-- Empty state icon -->
    </svg>
    <h3 class="vehicle-compatibility-empty-title">
        Brak dopasowań pojazdów
    </h3>
    <p class="vehicle-compatibility-empty-text">
        Rozpocznij od wybrania pojazdów z listy poniżej lub skorzystaj z sugestii AI
    </p>
</div>
```

---

## 🎯 Integracja z Alpine.js (przykład)

```html
<div x-data="vehicleCompatibility()">
    <!-- Main panel -->
    <div class="vehicle-compatibility-panel">
        <!-- Layout grid -->
        <div class="vehicle-compatibility-layout"
             :class="{ 'with-suggestions': showSuggestions }">

            <!-- Main content -->
            <div>
                <!-- Brand section -->
                <template x-for="brand in brands" :key="brand.id">
                    <div class="vehicle-brand-section">
                        <!-- Brand header -->
                        <div class="vehicle-brand-header">
                            <img :src="brand.logo" :alt="brand.name" class="vehicle-brand-logo">
                            <h3 class="vehicle-brand-name" x-text="brand.name"></h3>
                            <span class="vehicle-brand-count" x-text="brand.vehicleCount"></span>
                        </div>

                        <!-- Tiles grid -->
                        <div class="vehicle-tile-grid">
                            <template x-for="vehicle in brand.vehicles" :key="vehicle.id">
                                <div class="vehicle-tile"
                                     :class="{
                                         'selected-original': isSelectedOriginal(vehicle.id),
                                         'selected-replacement': isSelectedReplacement(vehicle.id),
                                         'loading': vehicle.loading
                                     }"
                                     @click="toggleVehicle(vehicle.id)">

                                    <!-- AI confidence badge (if present) -->
                                    <template x-if="vehicle.aiConfidence">
                                        <span class="vehicle-tile-confidence"
                                              :class="getConfidenceClass(vehicle.aiConfidence)"
                                              x-text="Math.round(vehicle.aiConfidence * 100)">
                                        </span>
                                    </template>

                                    <span class="vehicle-tile-model" x-text="vehicle.model"></span>
                                    <span class="vehicle-tile-year" x-text="vehicle.yearRange"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Suggestions sidebar -->
            <aside class="suggestions-panel" x-show="showSuggestions">
                <!-- Suggestions content -->
            </aside>
        </div>
    </div>

    <!-- Bulk action bar -->
    <div class="bulk-action-bar" :class="{ 'visible': selectedCount > 0 }">
        <div class="bulk-action-info">
            <div class="bulk-selection-count">
                <span class="bulk-selection-count-number" x-text="selectedCount"></span>
                <span class="bulk-selection-count-label">zaznaczonych</span>
            </div>

            <select class="bulk-shop-selector" x-model="selectedShop">
                <option value="">Wszystkie sklepy</option>
                <template x-for="shop in shops" :key="shop.id">
                    <option :value="shop.id" x-text="shop.name"></option>
                </template>
            </select>
        </div>

        <div class="bulk-action-buttons">
            <button class="btn-bulk-action btn-bulk-original"
                    @click="addAsOriginal()">
                Dodaj Oryginał
            </button>
            <button class="btn-bulk-action btn-bulk-replacement"
                    @click="addAsReplacement()">
                Dodaj Zamiennik
            </button>
            <button class="btn-bulk-action btn-bulk-remove"
                    @click="removeSelected()">
                Usuń
            </button>
            <button class="btn-bulk-action btn-bulk-verify"
                    @click="verifySelected()">
                Weryfikuj
            </button>
        </div>
    </div>
</div>

<script>
function vehicleCompatibility() {
    return {
        brands: [],
        selectedOriginal: [],
        selectedReplacement: [],
        selectedShop: '',
        showSuggestions: true,

        get selectedCount() {
            return this.selectedOriginal.length + this.selectedReplacement.length;
        },

        isSelectedOriginal(vehicleId) {
            return this.selectedOriginal.includes(vehicleId);
        },

        isSelectedReplacement(vehicleId) {
            return this.selectedReplacement.includes(vehicleId);
        },

        toggleVehicle(vehicleId) {
            // Toggle logic (shift for replacement, click for original)
            if (event.shiftKey) {
                // Toggle replacement
                const index = this.selectedReplacement.indexOf(vehicleId);
                if (index > -1) {
                    this.selectedReplacement.splice(index, 1);
                } else {
                    this.selectedReplacement.push(vehicleId);
                    // Remove from original if present
                    const origIndex = this.selectedOriginal.indexOf(vehicleId);
                    if (origIndex > -1) {
                        this.selectedOriginal.splice(origIndex, 1);
                    }
                }
            } else {
                // Toggle original
                const index = this.selectedOriginal.indexOf(vehicleId);
                if (index > -1) {
                    this.selectedOriginal.splice(index, 1);
                } else {
                    this.selectedOriginal.push(vehicleId);
                    // Remove from replacement if present
                    const replIndex = this.selectedReplacement.indexOf(vehicleId);
                    if (replIndex > -1) {
                        this.selectedReplacement.splice(replIndex, 1);
                    }
                }
            }
        },

        getConfidenceClass(confidence) {
            if (confidence >= 0.75) return 'high';
            if (confidence >= 0.50) return 'medium';
            return 'low';
        },

        addAsOriginal() {
            // Bulk add logic
        },

        addAsReplacement() {
            // Bulk add logic
        },

        removeSelected() {
            // Bulk remove logic
        },

        verifySelected() {
            // Verification logic
        }
    }
}
</script>
```

---

## 📐 Responsive Behavior

**Desktop (>1280px):**
- Two-column layout (main + suggestions sidebar)
- 120px min tile width
- ~8-10 tiles per row
- Full bulk action bar

**Tablet (768px - 1280px):**
- Single column layout
- 120px min tile width
- ~6-8 tiles per row
- Full bulk action bar

**Mobile (<768px):**
- Single column layout
- 100px min tile width
- ~3-4 tiles per row
- Stacked bulk action bar (vertical buttons)
- Smaller tiles (70px height)

---

## 🎨 Color Scheme

**PPM Color Tokens Used:**
- `--mpp-primary`: #e0ac7e (Orange - Oryginał)
- `--ppm-primary`: #2563eb (Blue - Zamiennik)
- Success: #059669 (Green - High confidence)
- Warning: #f59e0b (Yellow/Orange - Medium confidence)
- Error: #dc2626 (Red - Low confidence/remove)

**Gradients:**
- Original button: `linear-gradient(135deg, #e0ac7e, #d1975a)`
- Replacement button: `linear-gradient(135deg, #2563eb, #1d4ed8)`
- Remove button: `linear-gradient(135deg, #ef4444, #dc2626)`

---

## ⚡ Performance Optimizations

**Applied:**
- `will-change: transform` on interactive elements
- CSS animations with GPU acceleration
- Smooth scrolling with custom scrollbar
- Staggered tile animations (optional)
- Backdrop-filter blur for glass morphism

**Best Practices:**
- Avoid inline styles (all styles in CSS)
- Use CSS classes for state changes
- Leverage CSS Grid for responsive layout
- Minimize repaints with transform/opacity

---

## 🧪 Testing Checklist

**Visual States:**
- [ ] Default tile appearance (dark glass)
- [ ] Hover effect (orange border glow)
- [ ] Selected original (orange left border + tint)
- [ ] Selected replacement (blue left border + tint)
- [ ] Loading state (opacity + spinner)
- [ ] AI confidence badges (high/medium/low colors)

**Bulk Action Bar:**
- [ ] Hidden by default
- [ ] Slides up when selections made
- [ ] All button hover effects work
- [ ] Shop selector dropdown styled correctly
- [ ] Responsive layout on mobile

**Suggestions Panel:**
- [ ] Sidebar visible on desktop
- [ ] Collapsible/expandable
- [ ] Confidence badges color-coded correctly
- [ ] Apply/Dismiss buttons functional
- [ ] Scrollable content area

**Responsive:**
- [ ] Desktop: 2-column layout with sidebar
- [ ] Tablet: single column, full width tiles
- [ ] Mobile: compact tiles (100px min), stacked bulk bar

**Accessibility:**
- [ ] Sufficient color contrast (4.5:1 minimum)
- [ ] Keyboard navigable (focus states)
- [ ] Screen reader friendly labels
- [ ] Touch-friendly targets (44px minimum)

---

## 📝 Implementation Notes

**Build Process:**
1. CSS already added to `resources/css/admin/components.css`
2. No new files = no Vite manifest issues
3. Run `npm run build` to include in production
4. Deploy `public/build/assets/*` to production

**Livewire Integration:**
- Use Alpine.js for client-side interactivity
- Livewire wire:model for selections persistence
- wire:click for bulk actions (server-side processing)
- Real-time updates via wire:poll (optional)

**Future Enhancements:**
- Drag-and-drop vehicle reordering
- Multi-select with Ctrl/Cmd
- Keyboard shortcuts (Space = select, Arrow keys = navigate)
- Advanced filters (year range, engine type)
- Batch import from CSV

---

## 🔗 References

**Documentation:**
- `_DOCS/PPM_Styling_Playbook.md` - PPM color tokens and components
- `_DOCS/ARCHITEKTURA_PPM/18_DESIGN_SYSTEM.md` - Enterprise design system
- `_DOCS/ARCHITEKTURA_PPM/17_UI_UX_GUIDELINES.md` - UI/UX patterns

**Related CSS:**
- Sync status badges (lines 4-149)
- Enterprise cards (lines 150-203)
- Button system (lines 269-378)
- Form components (lines 382-497)

**ETAP Files:**
- `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md` - Vehicle compatibility system
- `_AGENT_REPORTS/frontend_COMPATIBILITY_TILES_UX_DESIGN.md` - This design report

---

**Last Updated:** 2025-12-05
**Version:** 1.0
**Author:** Frontend Specialist Agent
**Status:** ✅ CSS Implementation Complete
