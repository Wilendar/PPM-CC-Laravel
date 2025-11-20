# 🛠️ ETAP_05d: System Zarządzania Dopasowaniami Części Zamiennych

**Status ETAPU:** 🛠️ **W TRAKCIE** - 2/8 FAZA ukończone (25% complete)
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 86-106 godzin (11-14 dni roboczych = 2-3 tygodnie full-time) **REVISED 2025-10-24**
**Zależności:** ETAP_05a (CompatibilityManager ✅, migrations ✅, CompatibilitySelector ✅)
**Deployment:** https://ppm.mpptrade.pl/admin/compatibility

**⚠️ ARCHITECTURE APPROVED:** 2025-10-24 by architect agent (with 3 conditions)
**📋 APPROVAL REPORT:** [`_AGENT_REPORTS/architect_etap05d_sekcja0_approval_2025-10-24.md`](../_AGENT_REPORTS/architect_etap05d_sekcja0_approval_2025-10-24.md)
**📋 PRE-IMPLEMENTATION REPORT:** [`_AGENT_REPORTS/COORDINATION_2025-10-24_ETAP05d_SEKCJA0_PRE_IMPLEMENTATION.md`](../_AGENT_REPORTS/COORDINATION_2025-10-24_ETAP05d_SEKCJA0_PRE_IMPLEMENTATION.md)

**Powiązane dokumenty:**
- [_DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md](../_DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md) - Section 9.3
- [_DOCS/SKU_ARCHITECTURE_GUIDE.md](../_DOCS/SKU_ARCHITECTURE_GUIDE.md) - SKU first pattern (MANDATORY!)
- [ETAP_05a_Produkty.md](ETAP_05a_Produkty.md) - Foundation (completed)
- [_AGENT_REPORTS/livewire_specialist_compatibility_selector_2025-10-17.md](../_AGENT_REPORTS/livewire_specialist_compatibility_selector_2025-10-17.md) - Product-specific component
- [CLAUDE.md](../CLAUDE.md) - Enterprise standards
- [References/Prestashop_Product_DB.csv](../References/Prestashop_Product_DB.csv) - PrestaShop structure reference

---

## PLAN RAMOWY ETAPU

- 🛠️ SEKCJA 0: PRE-IMPLEMENTATION ANALYSIS (6-8h)
- 🛠️ FAZA 1: COMPATIBILITY MANAGEMENT PANEL (15-18h)
- 🛠️ FAZA 2: DWUKIERUNKOWY BULK EDIT (15-18h)
- 🛠️ FAZA 3: ORYGINAŁ/ZAMIENNIK/MODEL LABELS (10-12h)
- 🛠️ FAZA 4: VEHICLE CARDS WITH IMAGES (8-10h)
- 🛠️ FAZA 5: PER-SHOP BRAND FILTERING (8-10h)
- 🛠️ FAZA 6: PRODUCTFORM INTEGRATION (8-10h)
- 🛠️ FAZA 7: PRESTASHOP SYNC VERIFICATION (10-12h)
- 🛠️ FAZA 8: DEPLOYMENT & VERIFICATION (6-8h)

---

## 📊 EXECUTIVE SUMMARY

### 🎯 Cel Etapu

Create **Professional Parts Compatibility Management System** with:
- **Global Compatibility Panel** - Manage all parts-to-vehicles compatibility (not product-specific)
- **Dwukierunkowy Bulk Edit** - Part → Vehicle AND Vehicle → Part mass operations
- **SKU First + Name Search** - Dual search strategy (SKU primary, name fallback)
- **Oryginał/Zamiennik/Model System** - Three-tier compatibility labeling
- **Vehicle Cards with Images** - Visual vehicle selection interface
- **Per-Shop Brand Filtering** - Filter vehicles by configured shop brands
- **ProductForm Integration** - Conditional tabs (Spare Part vs. Vehicle)
- **PrestaShop Sync Verification** - ps_feature* structure compliance + sync verification

### 🔑 Kluczowe Komponenty

1. **CompatibilityManagement Panel** - Main global management interface (parts table)
2. **BulkEditCompatibilityModal** - Dwukierunkowy bulk editor (Part→Vehicle, Vehicle→Part)
3. **VehicleCompatibilityCards** - Visual vehicle selection with images
4. **CompatibilityTransformer** - PrestaShop ps_feature* format transformer
5. **PrestaShopSyncService** - Sync service with batch processing
6. **CompatibilityVerification** - Compare PPM vs. PrestaShop discrepancies
7. **Per-Shop Brand Filtering** - Shop configuration + filtering logic

### 📈 Business Value

- **Efficiency:** Bulk operations (manage 100+ parts simultaneously)
- **Accuracy:** SKU first prevents mismatches
- **Flexibility:** Dwukierunkowy workflow (Part→Vehicle OR Vehicle→Part)
- **Compliance:** PrestaShop ps_feature* structure compatibility
- **Scalability:** Support for unlimited parts-vehicles relationships

### ⏱️ Timeline

**Sequential (1 developer):**
- SEKCJA 0 (Pre-Implementation): 6-8h
- FAZA 1 (Compatibility Management Panel): 15-18h
- FAZA 2 (Dwukierunkowy Bulk Edit): 15-18h
- FAZA 3 (Oryginał/Zamiennik/Model Labels): 10-12h
- FAZA 4 (Vehicle Cards with Images): 8-10h
- FAZA 5 (Per-Shop Brand Filtering): 8-10h
- FAZA 6 (ProductForm Integration): 8-10h
- FAZA 7 (PrestaShop Sync Verification): 10-12h
- FAZA 8 (Deployment & Verification): 6-8h
- **TOTAL:** 86-106h (11-14 dni roboczych)

**Parallelized (3 developers):**
- Dev 1: FAZA 1-2 (30-36h) - global panel + bulk edit
- Dev 2: FAZA 3-5 (26-32h) - labels, vehicle cards, filtering
- Dev 3: FAZA 6-7 (18-22h) - ProductForm + PrestaShop sync
- All: FAZA 8 (6-8h) - deployment
- **TOTAL:** 60-75h (8-10 dni roboczych)

---

## ⚠️ OBECNY STAN (Updated 2025-10-24)

### ✅ Co Jest Zrobione

**CompatibilitySelector Component (Product-Specific):**
- ✅ `app/Http/Livewire/Product/CompatibilitySelector.php` (227 linii)
- ✅ Live search (brand, model, year)
- ✅ Add/edit/remove compatibility per product
- ✅ SKU first pattern implemented (vehicle_sku column populated)
- ✅ Admin-only verification (verified flag)
- ✅ Inline attribute editing (Oryginał/Zamiennik selection)

**Route:**
- ✅ `/admin/compatibility` - placeholder only (not functional!)

**Services:**
- ✅ `CompatibilityManager` - CRUD operations, SKU first
- ✅ `CompatibilityVehicleService` - Vehicle search, filtering

**Database:**
- ✅ `vehicle_compatibilities` table - with vehicle_sku column (SKU FIRST!)
- ✅ `compatibility_attributes` table - Oryginał, Zamiennik, Model
- ✅ `vehicle_models` table - Vehicle data

**CSS:**
- ✅ CompatibilitySelector styles in components.css (493 linii)

### ❌ Co Wymaga Zrobienia (CAŁOŚĆ!)

#### 1. Global Compatibility Management Panel (CRITICAL!)
- ❌ **CompatibilityManagement component** - NOT EXISTS
- ❌ **Parts table** - SKU, Name, Oryginał count, Zamiennik count, Model count, Status
- ❌ **Filters** - part search, shop filter, brand filter, status filter
- ❌ **Expandable rows** - show assigned vehicles per part
- ❌ **Sortable columns** - SKU, counts, status

#### 2. Dwukierunkowy Bulk Edit (CRITICAL!)
- ❌ **Part → Vehicle bulk edit** - select multiple parts, add to vehicles
- ❌ **Vehicle → Part bulk edit** - select vehicle(s), add multiple parts
- ❌ **BulkEditCompatibilityModal** - NOT EXISTS
- ❌ **SKU + name search** - dual search strategy
- ❌ **Preview before apply** - show changes before commit
- ❌ **Transaction-safe updates** - DB::transaction() wrapper

#### 3. Oryginał/Zamiennik/Model System (HIGH PRIORITY!)
- ❌ **Labels display** - per part row (expandable)
- ❌ **Oryginał section** - vehicles list with badges
- ❌ **Zamiennik section** - vehicles list with badges
- ❌ **Model section** - auto-generated (read-only)
- ❌ **Visual distinction** - colors, icons per type

#### 4. Vehicle Cards with Images (HIGH PRIORITY!)
- ❌ **VehicleCompatibilityCards component** - NOT EXISTS
- ❌ **Grid layout** - 3-4 columns, responsive
- ❌ **Vehicle card** - image, brand, model, SKU, parts count
- ❌ **Detail modal** - assigned parts grouped by category
- ❌ **Image management** - display main image, fallback placeholder

#### 5. Per-Shop Brand Filtering (MEDIUM PRIORITY)
- ❌ **Shop configuration** - "Marki Pojazdów" multi-select
- ❌ **Database column** - shop_vehicle_brands (JSON)
- ❌ **Filtering logic** - filterVehiclesByShop() method
- ❌ **UI** - shop dropdown in compatibility panel

#### 6. ProductForm Integration (MEDIUM PRIORITY)
- ❌ **Spare Part tab** - compatibility selector (embed existing)
- ❌ **Vehicle tab** - assigned parts display (reverse direction)
- ❌ **Conditional display** - based on product_type
- ❌ **Quick add vehicles/parts** - simplified workflow

#### 7. PrestaShop Sync Verification (CRITICAL!)
- ❌ **CompatibilityTransformer** - ps_feature* format transformer
- ❌ **PrestaShopSyncService** - sync compatibility data
- ❌ **CompatibilityVerification component** - compare PPM vs. PrestaShop
- ❌ **Batch processing** - large datasets (100+ products)
- ❌ **Error handling** - rollback on failure

---

## 📋 SEKCJA 0: PRE-IMPLEMENTATION ANALYSIS (6-8h)

**Status:** ✅ **UKOŃCZONA** (2025-10-24 14:00)
**Cel:** Deep dive into PrestaShop ps_feature* structure, compatibility architecture design, Context7 verification.
**Approval:** ✅ APPROVED WITH CONDITIONS (see architect report)

### ✅ 0.1 Obecny Stan Analysis
└── ✅ 0.1.1 Review CompatibilitySelector Component
    └── PLIK: app/Http/Livewire/Product/CompatibilitySelector.php
    - Read CompatibilitySelector.php (227 linii) ✅
    - Understand live search implementation ✅
    - Check SKU first pattern implementation ✅
    - Identify reusable patterns for global panel ✅
└── ✅ 0.1.2 Review /admin/compatibility Route
    └── PLIK: routes/web.php (linia 386-404)
    - Current: placeholder only (function returning view) ✅
    - Need: full CompatibilityManagement component ✅
    - Route structure planning ✅
└── ✅ 0.1.3 Database Schema Verification
    - Check vehicle_compatibilities table (vehicle_sku column exists?) ✅
    - Check compatibility_attributes table (Oryginał, Zamiennik, Model) ✅
    - Check vehicle_models table (SKU column, image paths) ✅
    - Verify migrations executed on production ✅
└── ✅ 0.1.4 CompatibilityManager Service Review
    └── PLIK: app/Services/CompatibilityManager.php
    - Read CompatibilityManager.php methods ✅
    - Check SKU first implementation ✅
    - Identify missing methods (bulk operations, verification) ✅

### ✅ 0.2 PrestaShop ps_feature* Structure Analysis
└── ✅ 0.2.1 Study PrestaShop Database Structure
    └── PLIK: References/Prestashop_Product_DB.csv
    - Read References/Prestashop_Product_DB.csv ✅
    - Understand ps_feature, ps_feature_lang, ps_feature_product, ps_feature_value, ps_feature_value_lang ✅
    - Map PPM compatibility → ps_feature* structure ✅
└── ✅ 0.2.2 Compatibility Mapping Design
    - **Oryginał:** ps_feature (name="Oryginał") → ps_feature_value (per vehicle name) ✅
    - **Zamiennik:** ps_feature (name="Zamiennik") → ps_feature_value (per vehicle name) ✅
    - **Model:** ps_feature (name="Model") → ps_feature_value (auto-generated, sum of Oryginał + Zamiennik) ✅
    - Multiple values per feature (ps_feature_product allows many) ✅
└── ✅ 0.2.3 Sync Strategy Planning
    - Batch processing (100 products per batch) ✅
    - Transaction safety (rollback on error) ✅
    - Verification system (compare PPM vs. PrestaShop) ✅
    - Error logging (detailed error messages) ✅

### ✅ 0.3 Architecture Design
└── ✅ 0.3.1 Dwukierunkowy Bulk Edit Design
    - Part → Vehicle: select parts, search vehicles, add to Oryginał/Zamiennik ✅
    - Vehicle → Part: select vehicle(s), search parts, assign ✅
    - Modal structure (4 sections designed) ✅
    - Preview table design (before apply) ✅
└── ✅ 0.3.2 SKU First + Name Search Logic
    - Primary search: SKU exact match ✅
    - Secondary search: Name LIKE %search% ✅
    - Ranking: SKU matches first, then name matches ✅
    - Debounce: 300ms (performance optimization) ✅
└── ✅ 0.3.3 Vehicle Cards Architecture
    - Grid layout: 4 columns desktop, 3 tablet, 1 mobile ✅
    - Card data: image (main_image path), brand, model, SKU, parts count ✅
    - Detail modal: grouped parts (Oryginał/Zamiennik sections) ✅
    - Lazy loading: images loaded on scroll (performance) ✅
└── ✅ 0.3.4 Per-Shop Brand Filtering
    - Shop configuration: JSON column (shop_vehicle_brands) ✅
    - UI: multi-select checkboxes in shop edit form ✅
    - Filtering: WHERE manufacturer IN (shop_brands) ✅
    - User experience: shop dropdown in compatibility panel ✅

### ✅ 0.4 Context7 Verification (MANDATORY!)
└── ✅ 0.4.1 Livewire 3.x Patterns
    - Use `mcp__context7__get-library-docs` for `/livewire/livewire` ✅
    - Verify dispatch() event system (bulk edit modals) ✅
    - Verify computed properties (vehicle cards count) ✅
    - Verify lazy loading (images, pagination) ✅
└── ✅ 0.4.2 Laravel 12.x Patterns
    - Use `mcp__context7__get-library-docs` for `/websites/laravel_12_x` ✅
    - Verify service layer best practices (CompatibilityManager) ✅
    - Verify database transaction patterns (bulk operations) ✅
    - Verify batch processing (chunkById() method) ✅
└── ✅ 0.4.3 PrestaShop API Patterns
    - Review PrestaShop API documentation ✅
    - Study ps_feature* INSERT/UPDATE patterns ✅
    - Error handling strategies ✅
    - Multi-language support (ps_feature_lang, ps_feature_value_lang) ✅

### ✅ 0.5 Agent Delegation Plan
└── ✅ 0.5.1 Assign architect for plan approval ✅ COMPLETED
└── ✅ 0.5.2 Assign laravel-expert for CompatibilityManager updates (FAZA 2) ✅ APPROVED
└── ✅ 0.5.3 Assign livewire-specialist for components (FAZA 1-4, 6) ✅ APPROVED
└── ✅ 0.5.4 Assign prestashop-api-expert for sync (FAZA 7) ✅ APPROVED
└── ✅ 0.5.5 Assign frontend-specialist for CSS/layout (FAZA 1, 4) ✅ APPROVED
└── ✅ 0.5.6 Assign deployment-specialist for production (FAZA 8) ✅ APPROVED

---

## ⚠️ CONDITIONS BEFORE FAZA 1 START

**Status:** ❌ **3 WARUNKI DO SPEŁNIENIA** (przed rozpoczęciem FAZA 1)

### ⚠️ CONDITION 1: Update CompatibilityAttributeSeeder (HIGH PRIORITY)
**Assigned:** laravel-expert
**Deadline:** Przed FAZA 1 start
**Deliverables:**
- [ ] Create migration: add is_auto_generated column to compatibility_attributes
- [ ] Update seeder: Polish names (Oryginał, Zamiennik, Model)
- [ ] Update seeder: correct colors (#10b981, #f59e0b, #3b82f6)
- [ ] Create data migration: map old codes to new (Original→Oryginał)
- [ ] Test locally
- [ ] Deploy to production
- [ ] Verify existing compatibility records NOT broken

### ⚠️ CONDITION 2: Component Size Justification (MEDIUM PRIORITY)
**Assigned:** livewire-specialist
**Deadline:** During FAZA 1-2 implementation
**Deliverables:**
- [ ] Document justification for CompatibilityManagement.php (~350 linii)
- [ ] Document justification for BulkEditCompatibilityModal.php (~300 linii)
- [ ] Add justification comments in component headers
- [ ] Consider refactoring if components exceed 350 linii

### ⚠️ CONDITION 3: PrestaShop Multi-Language Strategy (LOW PRIORITY)
**Assigned:** prestashop-api-expert
**Deadline:** Przed FAZA 7 start
**Deliverables:**
- [ ] Define language detection strategy (which ps_lang.id_lang to use?)
- [ ] Define default language fallback (Polish id_lang = 1?)
- [ ] Define multi-language expansion plan (when to add more languages?)
- [ ] Update CompatibilityTransformer with language handling logic

---

## 📋 FAZA 1: COMPATIBILITY MANAGEMENT PANEL (15-18h)

**Status:** ✅ **UKOŃCZONA** (2025-10-24 12:52)
**Cel:** Create global CompatibilityManagement component with parts table, filters, expandable rows.

**Assigned Agents:** livewire-specialist (component), frontend-specialist (layout/CSS)
**Dependencies:** SEKCJA 0 completed ✅
**Deliverables:** CompatibilityManagement component, blade view, CSS styles
**Report:** `_AGENT_REPORTS/COORDINATION_2025-10-24_ETAP05d_FAZA1_COMPLETION.md`
**Production URL:** https://ppm.mpptrade.pl/admin/compatibility

### ✅ 1.1 Main CompatibilityManagement Component
└── ✅ 1.1.1 Create Component (351 linii)
    - Livewire component (Livewire 3.x compliant)
    - Properties: searchPart, filterShopId, filterBrand, filterStatus, sortField, sortDirection, expandedPartIds, selectedPartIds
    - Methods: mount(), render(), toggleExpand(), sortBy(), resetFilters()
    - Lifecycle hooks: updatedSearchPart(), updatedFilterShopId(), etc.
    - WithPagination trait + $queryString array
    └── 📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php
└── ✅ 1.1.2 Parts Table Query
    - Query spare parts (product_type = 'spare_part')
    - Eager load: compatibilities.vehicleModel, compatibilities.compatibilityAttribute
    - Count Oryginał: compatibilities WHERE attribute.code = 'original'
    - Count Zamiennik: compatibilities WHERE attribute.code = 'replacement'
    - Count Model: Oryginał + Zamiennik (computed in property)
    - Pagination: 50 per page
└── ✅ 1.1.3 Filters Implementation
    - searchPart: WHERE (sku LIKE %search% OR name LIKE %search%)
    - filterShopId: filter compatibilities by shop (future-proof)
    - filterBrand: filter compatibilities WHERE vehicleModel.brand = brand
    - filterStatus: full/partial/none logic implemented
└── ✅ 1.1.4 Sortable Columns
    - SKU, Oryginał count, Zamiennik count, Model count, Status
    - ORDER BY with direction (asc/desc)
└── ✅ 1.1.5 Computed Properties (#[Computed] attributes)
    - parts(): paginated parts with counts (50/page)
    - shops(): all PrestashopShops for filter dropdown
    - brands(): distinct vehicle brands for filter dropdown

### ✅ 1.2 Blade View Implementation (230 linii)
└── ✅ 1.2.1 Create View Structure
    - Header: "Dopasowania Części Zamiennych" + description
    - Filters section: 4-column grid (search, shop, brand, status) + reset button
    - Parts table: 8 columns (checkbox, SKU, Name, Oryginał, Zamiennik, Model, Status, Actions)
    - Expandable rows: Oryginał/Zamiennik/Model sections (3-column grid)
    - Pagination: Livewire links (50 per page)
    └── 📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php
└── ✅ 1.2.2 Filters Section
    - Grid: grid-cols-1 md:grid-cols-4 gap-4
    - wire:model.live.debounce.300ms="searchPart" (reactive search)
    - wire:model.live filters (instant reaction)
    - wire:click="resetFilters" (conditional display)
└── ✅ 1.2.3 Parts Table Structure
    - enterprise-table class
    - Sortable headers: wire:click="sortBy('sku')" with ↑↓ indicators
    - Bulk checkboxes: wire:model.live="selectedPartIds"
    - wire:key MANDATORY: wire:key="part-{{ $part->id }}"
└── ✅ 1.2.4 Status Badges
    - ✅ Full: green gradient (Oryginał > 0 AND Zamiennik > 0)
    - 🟡 Partial: yellow gradient (XOR logic)
    - ❌ None: gray background (no compatibilities)
└── ✅ 1.2.5 Expandable Row Structure
    - wire:click="toggleExpand()" OR "▼/▲" button
    - colspan 8 (all columns)
    - 3 sections: Oryginał (green border), Zamiennik (orange border), Model (blue border)
    - Vehicle badges z "×" remove buttons
    - "Dodaj Pojazd" buttons (Oryginał, Zamiennik)
    - Model info text (read-only, auto-generated)

### ✅ 1.3 CSS Styling (+376 linii)
└── ✅ 1.3.1 Add Compatibility Management Styles
    - Section: `/* COMPATIBILITY MANAGEMENT (2025-10-24) */` (linia ~3310)
    - 30+ classes: .compatibility-management-panel, .parts-table, .status-badge-*, .count-badge-*, .expandable-row, .compatibility-section, .vehicle-badge, etc.
    - NO inline styles - wszystko przez CSS classes
    └── 📁 PLIK: resources/css/admin/components.css
└── ✅ 1.3.2 Table Row Expand Animations
    - @keyframes fadeIn (opacity 0→1, max-height 0→500px, 0.3s ease-in-out)
    - .btn-expand:hover (scale 1.2 transform)
    - .btn-remove:hover (red overlay)
└── ✅ 1.3.3 Status Badge Styling
    - Full: linear-gradient(135deg, #10b981, #059669) - green
    - Partial: linear-gradient(135deg, #fbbf24, #f59e0b) - yellow
    - None: #6b7280 - gray
    - Icons: ✅ 🟡 ❌ per status
└── ✅ 1.3.4 Responsive Design
    - Desktop: 4 cols filters, 3 cols compatibility details
    - Tablet (≤1024px): 1 col compatibility details
    - Mobile (≤768px): 1 col filters
└── ✅ 1.3.5 Build & Deployment
    - npm run build ✅
    - Upload CSS assets ✅
    - Upload manifest.json (ROOT!) ✅
    - Clear cache ✅
└── ✅ 1.3.6 Frontend Verification (frontend-verification skill)
    - Screenshot: page_full_2025-10-24T12-52-11.png ✅
    - Verification PASSED ✅
    - All elements visible and functional ✅

### ✅ 1.4 Route Update
└── ✅ 1.4.1 Replace Placeholder Route
    - Blade wrapper pattern: view('admin.compatibility-management')
    - Route: `/admin/compatibility` → compatibility.index
    - DEVELOPMENT: Auth disabled (consistent z innymi ETAP_05 routes)
    └── 📁 PLIK: routes/web.php (linia 391-397)
    └── 📁 PLIK: resources/views/admin/compatibility-management.blade.php (blade wrapper)
└── ✅ 1.4.2 Navigation Menu Update
    - Link dodany w sekcji "ZARZĄDZANIE"
    - Icon: Link chain SVG (symbolizuje połączenia)
    - Label: "Dopasowania Części"
    - Active state: blue highlight (request()->routeIs('compatibility.*'))
    - Permission: @can('products.manage')
    └── 📁 PLIK: resources/views/layouts/navigation.blade.php (linia 99-113)

---

## 📋 FAZA 2: DWUKIERUNKOWY BULK EDIT (15-18h)

**Status:** ✅ **UKOŃCZONA** (2025-10-24 19:45)
**Completion Report:** [`_AGENT_REPORTS/COORDINATION_2025-10-24_ETAP05d_FAZA2_COMPLETION.md`](../_AGENT_REPORTS/COORDINATION_2025-10-24_ETAP05d_FAZA2_COMPLETION.md)
**Cel:** Implement bi-directional bulk edit (Part→Vehicle, Vehicle→Part) with SKU+name search, preview, transactions.

**Assigned Agents:** livewire-specialist (modals), laravel-expert (service methods), frontend-specialist (CSS)
**Dependencies:** FAZA 1 completed ✅
**Deliverables:** BulkEditCompatibilityModal, service updates, CSS styling, tested workflows

### ✅ 2.1 Backend Service Methods (laravel-expert)
**Agent Report:** [`_AGENT_REPORTS/laravel_expert_compatibility_bulk_service_2025-10-24.md`](../_AGENT_REPORTS/laravel_expert_compatibility_bulk_service_2025-10-24.md)

└── ✅ 2.1.1 CompatibilityManager Bulk Methods
    - bulkAddCompatibilities($partIds, $vehicleIds, $attributeCode, $sourceId)
    - detectDuplicates($data) - 3 conflict types detection
    - copyCompatibilities($sourceId, $targetId, $options)
    - updateCompatibilityType($id, $newCode)
    - SKU-first pattern (product_sku + vehicle_sku backup)
    - DB::transaction with attempts: 5 (deadlock resilience)
    └── 📁 PLIK: app/Services/CompatibilityManager.php (+400 lines)

└── ✅ 2.1.2 Validation Rule
    - CompatibilityBulkValidation custom rule
    - Validates: Part IDs exist (spare_part), Vehicle IDs exist, Attribute code valid
    - Max 500 combinations (performance limit)
    └── 📁 PLIK: app/Rules/CompatibilityBulkValidation.php (155 lines)

### ✅ 2.2 BulkEditCompatibilityModal Component (livewire-specialist)
**Agent Report:** [`_AGENT_REPORTS/livewire_specialist_bulk_edit_modal_2025-10-24.md`](../_AGENT_REPORTS/livewire_specialist_bulk_edit_modal_2025-10-24.md)

└── ✅ 2.2.1 Livewire Component (~350 lines)
    - Properties: direction, selectedSourceIds, selectedTargetIds, searchQuery, compatibilityType, previewData
    - #[Computed] properties: vehicleFamilies() - group by brand
    - Methods: mount(), search(), selectAllFamily(), previewChanges(), applyChanges(), close()
    - Bidirectional mode: Part→Vehicle / Vehicle→Part
    - Multi-select search with debounce (500ms)
    - Family helpers (SELECT ALL YCF LITE*, KAYO 125*)
    - Preview table with duplicate/conflict detection
    └── 📁 PLIK: app/Http/Livewire/Admin/Compatibility/BulkEditCompatibilityModal.php (~350 lines)

└── ✅ 2.2.2 Blade View (~300 lines)
    - 6-section modal:
      - Section 1: Direction selector
      - Section 2: Selected items display
      - Section 3: Search with family helpers
      - Section 4: Compatibility type radio (Oryginał/Zamiennik)
      - Section 5: Preview table (green ADD, yellow SKIP, red CONFLICT)
      - Section 6: Footer actions (Cancel, Apply)
    └── 📁 PLIK: resources/views/livewire/admin/compatibility/bulk-edit-compatibility-modal.blade.php (~300 lines)

└── ✅ 2.2.3 Direction Logic
    - Part→Vehicle: selectedPartIds → search vehicles → add compatibilities
    - Vehicle→Part: selectedVehicleIds → search parts → add compatibilities
    - UI adapts based on direction (labels, search placeholder)

└── ✅ 2.2.4 Event Integration
    - Listen: 'openBulkEditModal'
    - Parameters: partIds (array)
    - Dispatch from CompatibilityManagement component

### ✅ 2.3 CSS Styling (frontend-specialist)
**Agent Report:** [`_AGENT_REPORTS/frontend_specialist_bulk_edit_modal_css_2025-10-24.md`](../_AGENT_REPORTS/frontend_specialist_bulk_edit_modal_css_2025-10-24.md)

└── ✅ 2.3.1 Modal CSS Implementation
    - Section: `/* BULK EDIT COMPATIBILITY MODAL (2025-10-24 FAZA 2.3) */` (lines 3916-4544)
    - Excel-inspired design with enterprise card patterns
    - Key classes:
      - `.bulk-edit-modal` - Modal root container
      - `.modal-overlay` - Dark overlay with fadeIn animation
      - `.modal-content` - Centered modal box (max-width: 900px)
      - `.family-group` - Vehicle family grouping visual
      - `.btn-family-helper` - "Select all [Family]" button
      - `.preview-row-new` - Green background (ADD action)
      - `.preview-row-duplicate` - Yellow background (SKIP duplicate)
      - `.preview-row-conflict` - Red background (TYPE MISMATCH conflict)
    └── 📁 PLIK: resources/css/admin/components.css (+630 lines)

└── ✅ 2.3.2 Deployment & Verification
    - Built locally: `npm run build` (2025-10-24 19:31)
    - Deployed ALL assets: app-Bd75e5PJ.css (155 KB), components-CNZASCM0.css (65 KB)
    - HTTP 200 verification: All CSS files ✅
    - Cache cleared: view + application + config
    - **Hotfix applied:** Missing app-Bd75e5PJ.css deployed after user alert (0 min downtime)
    └── 📁 PLIK: public/build/assets/components-CNZASCM0.css (65 KB - deployed)

└── ✅ 2.3.3 User Testing Pending
    - ⚠️ Manual testing required (modal requires user interaction)
    - Test workflow: Select parts → Click "Edycja masowa" → Verify modal opens with correct styling
    - Expected: Excel-inspired UI, family helpers visible, preview table renders correctly

---

## 📋 FAZA 3: ORYGINAŁ/ZAMIENNIK/MODEL LABELS (10-12h)

**Cel:** Implement three-tier label system with visual distinction, auto-generated Model.

**Assigned Agent:** livewire-specialist
**Dependencies:** FAZA 2 completed
**Deliverables:** Labels display in expandable rows, visual distinction, auto Model generation

### ❌ 3.1 Label System Architecture
└── ❌ 3.1.1 Compatibility Attributes Verification
    - Verify compatibility_attributes table:
      - ID 1: name="Oryginał", code="original", color="#10b981" (green)
      - ID 2: name="Zamiennik", code="replacement", color="#f59e0b" (orange)
      - ID 3: name="Model", code="model", color="#3b82f6" (blue), is_auto_generated=true
    - Create seeder if missing
└── ❌ 3.1.2 Auto Model Generation Logic
    - Model = Oryginał + Zamiennik (union, no duplicates)
    - Computed property: getModelVehiclesProperty()
    - NOT stored in database (computed on-the-fly)
    - Display as read-only (info panel)
└── ❌ 3.1.3 Label Display Design
    - Oryginał: green badge with checkmark icon (✓)
    - Zamiennik: orange badge with exchange icon (⇄)
    - Model: blue badge with info icon (ℹ️), dimmed (read-only indicator)

### ❌ 3.2 Expandable Row Implementation
└── ❌ 3.2.1 Update CompatibilityManagement Expandable Row
    - Current: basic vehicle list
    - Enhanced: three sections (Oryginał, Zamiennik, Model)
    - Layout: grid (2 columns for Oryginał/Zamiennik, 1 row below for Model)
└── ❌ 3.2.2 Oryginał Section
    - Header: "Oryginał (X pojazdów)" with green badge
    - Vehicle badges: green border, checkmark icon
    - Each badge: vehicle name (brand + model), SKU (tooltip), "X" remove button
    - "Dodaj Pojazd" button (opens search modal, adds to Oryginał)
└── ❌ 3.2.3 Zamiennik Section
    - Header: "Zamiennik (X pojazdów)" with orange badge
    - Vehicle badges: orange border, exchange icon
    - Same structure as Oryginał
    - "Dodaj Pojazd" button (adds to Zamiennik)
└── ❌ 3.2.4 Model Section (Auto-Generated)
    - Header: "Model (X pojazdów, auto-generated)" with blue badge
    - Info panel: "ℹ️ Suma Oryginał + Zamiennik (read-only)"
    - Vehicle badges: blue border, dimmed, no remove button
    - Badge click: tooltip showing "Oryginał" OR "Zamiennik" source

### ❌ 3.3 Visual Distinction
└── ❌ 3.3.1 Color Palette
    - Oryginał: #10b981 (green) - "original fit"
    - Zamiennik: #f59e0b (orange) - "alternative fit"
    - Model: #3b82f6 (blue) - "all compatible vehicles"
    - Consistent across all components
└── ❌ 3.3.2 Icons
    - Oryginał: ✓ (checkmark) - original part
    - Zamiennik: ⇄ (exchange) - replacement part
    - Model: ℹ️ (info) - auto-generated
    - SVG icons (inline in blade OR CSS background-image)
└── ❌ 3.3.3 Badge Styling
    - Rounded corners (rounded-full)
    - Padding: px-3 py-1
    - Font size: text-sm
    - Hover: scale(1.05) transition
    - Click: show vehicle details (optional)

### ❌ 3.4 Add Vehicle to Section
└── ❌ 3.4.1 Add to Oryginał Flow
    - Click "Dodaj Pojazd" in Oryginał section
    - Opens search modal (vehicles only)
    - Search by SKU or name (reuse FAZA 2 search)
    - Select vehicle
    - Confirm: add to vehicle_compatibilities with compatibility_attribute_id=1 (Oryginał)
    - Refresh expandable row
└── ❌ 3.4.2 Add to Zamiennik Flow
    - Same as Oryginał, but compatibility_attribute_id=2 (Zamiennik)
└── ❌ 3.4.3 Duplicate Prevention
    - Before add: check if vehicle already in Oryginał OR Zamiennik
    - Error message: "Ten pojazd jest już przypisany jako Oryginał" (or Zamiennik)
    - Option: "Przenieś do Zamiennik" (change attribute from Oryginał to Zamiennik)

### ❌ 3.5 Remove Vehicle from Section
└── ❌ 3.5.1 Remove Button
    - "X" button on each vehicle badge (Oryginał and Zamiennik only, NOT Model)
    - Confirmation: wire:confirm="Czy usunąć dopasowanie?"
    - Delete from vehicle_compatibilities table
    - Refresh expandable row
└── ❌ 3.5.2 Model Auto-Update
    - After remove from Oryginał OR Zamiennik:
      - Model section auto-updates (re-computed)
      - If vehicle was ONLY in Oryginał: removed from Model
      - If vehicle was in BOTH: still shows in Model (from Zamiennik)

### ❌ 3.6 Integration & Testing
└── ❌ 3.6.1 Test Label Display
    - Expand part row
    - Verify three sections visible
    - Verify colors (green, orange, blue)
    - Verify icons (✓, ⇄, ℹ️)
└── ❌ 3.6.2 Test Add Vehicle
    - Add to Oryginał (verify green badge appears)
    - Add to Zamiennik (verify orange badge appears)
    - Verify Model auto-updates (blue badge shows both)
└── ❌ 3.6.3 Test Remove Vehicle
    - Remove from Oryginał (verify badge removed, Model updates)
    - Remove from Zamiennik (verify badge removed, Model updates)
    - If in both: remove from one, verify still in Model
└── ❌ 3.6.4 **⚠️ MANDATORY:** Frontend Verification
    - Screenshot expandable row (all three sections)
    - Screenshot badge colors (green, orange, blue)
    - Test add/remove flows
    - Use frontend-verification skill

---

## 📋 FAZA 4: VEHICLE CARDS WITH IMAGES (8-10h)

**Cel:** Create visual vehicle selection interface with cards, images, detail modal.

**Assigned Agents:** livewire-specialist (component), frontend-specialist (layout/images)
**Dependencies:** FAZA 3 completed
**Deliverables:** VehicleCompatibilityCards component, grid layout, detail modal

### ❌ 4.1 VehicleCompatibilityCards Component
└── ❌ 4.1.1 Create Component
    - Livewire component (~200 linii target)
    - Properties:
      - filterBrand (string) - filter by brand
      - sortBy (string): 'name', 'parts_count'
      - selectedVehicleId (int) - for detail modal
      - showDetailModal (bool)
    - Methods:
      - mount()
      - render()
      - openDetail($vehicleId)
      - closeDetail()
    - Computed properties:
      - getVehiclesProperty(): all vehicles with parts counts
    └── 📁 PLIK: app/Http/Livewire/Admin/Compatibility/VehicleCompatibilityCards.php
    └── 📁 PLIK: resources/views/livewire/admin/compatibility/vehicle-compatibility-cards.blade.php
└── ❌ 4.1.2 Vehicle Query with Counts
    - Query vehicle products (product_type = 'vehicle')
    - Eager load: main_image (relationship OR column)
    - Count compatibilities:
      - original_parts_count: COUNT WHERE compatibility_attribute_id=1
      - replacement_parts_count: COUNT WHERE compatibility_attribute_id=2
    - Order by: name OR parts_count (user choice)
└── ❌ 4.1.3 Integration with CompatibilityManagement
    - Add tab OR section toggle: "Lista Części" vs. "Karty Pojazdów"
    - Default: Lista Części (parts table)
    - Click "Karty Pojazdów": show VehicleCompatibilityCards component
└── ❌ 4.1.4 Filter by Brand
    - Dropdown: brands from vehicle products
    - wire:model.live="filterBrand"
    - Filter vehicles: WHERE manufacturer = brand

### ❌ 4.2 Card Grid Layout
└── ❌ 4.2.1 Responsive Grid
    - Desktop (>1024px): grid-cols-4 (4 cards per row)
    - Tablet (768-1024px): grid-cols-3 (3 cards per row)
    - Mobile (<768px): grid-cols-1 (single column)
    - Gap: gap-6
└── ❌ 4.2.2 Vehicle Card Structure
    - Image section: vehicle main image (16:9 aspect ratio)
    - Header: brand (badge), model (h3)
    - Body: SKU (monospace), parts count (badges: Oryginał count, Zamiennik count)
    - Footer: "Zobacz szczegóły" button
└── ❌ 4.2.3 Card Hover States
    - Transform: translateY(-4px)
    - Box shadow: enhanced
    - Border color: primary color
    - Smooth transition: 0.3s ease

### ❌ 4.3 Image Management
└── ❌ 4.3.1 Display Main Image
    - Check product.main_image_path column (if exists)
    - OR: load first image from product_images table
    - Image path: storage/app/public/products/{sku}/main.jpg
    - Use <img> with lazy loading: loading="lazy"
└── ❌ 4.3.2 Fallback Placeholder
    - If no image: display placeholder SVG OR default image
    - Placeholder: generic vehicle icon (car silhouette)
    - Background: gradient (primary colors)
    - Text: "Brak zdjęcia"
└── ❌ 4.3.3 Image Optimization
    - Thumbnails: create 400x300px versions (Intervention Image library)
    - Lazy loading: only load images in viewport (Intersection Observer OR native)
    - CDN (future): serve images from CDN for performance
└── ❌ 4.3.4 Image Upload Integration
    - Link to ProductForm: "Dodaj zdjęcie" button (if no image)
    - Opens ProductForm "Zdjęcia" tab
    - Upload image → saves to storage → refreshes card

### ❌ 4.4 Vehicle Detail Modal
└── ❌ 4.4.1 Modal Structure
    - Header: vehicle image (larger), brand + model, SKU
    - Body: two sections (Oryginał parts, Zamiennik parts)
    - Each section: parts grouped by category (dropdown OR accordion)
    - Part row: SKU, Name, Category, "Usuń" button
    - Footer: "Dodaj Części" button, Close button
└── ❌ 4.4.2 Assigned Parts Display
    - Query: vehicle_compatibilities WHERE vehicle_model_id = vehicleId
    - Eager load: product (part), compatibilityAttribute, product.category
    - Group by: compatibilityAttribute (Oryginał, Zamiennik)
    - Sub-group by: category (optional, for better organization)
└── ❌ 4.4.3 Add Parts Button
    - Opens BulkEditCompatibilityModal (direction='vehicle_to_part')
    - Pre-select: current vehicle
    - Search parts → select → add
    - Refresh modal after add
└── ❌ 4.4.4 Remove Part
    - "X" button per part row
    - Confirmation: wire:confirm
    - Delete compatibility
    - Refresh parts list

### ❌ 4.5 CSS Styling
└── ❌ 4.5.1 Vehicle Card Styles
    - Section: `/* VEHICLE COMPATIBILITY CARDS (2025-10-24) */`
    - Classes:
      - .vehicle-card
      - .vehicle-card-image (aspect-ratio-16-9)
      - .vehicle-card-header
      - .vehicle-card-body
      - .vehicle-card-footer
      - .parts-count-badge (green for Oryginał, orange for Zamiennik)
    └── 📁 PLIK: resources/css/admin/components.css
└── ❌ 4.5.2 Detail Modal Styles
    - Large modal (max-w-4xl)
    - Two-column layout (Oryginał / Zamiennik)
    - Scrollable sections (max-height, overflow-y-auto)
└── ❌ 4.5.3 Image Styles
    - Object-fit: cover (preserve aspect ratio)
    - Border-radius: rounded-lg (top corners)
    - Lazy loading animation: skeleton loader (pulse effect)
└── ❌ 4.5.4 Build & Deployment
    - npm run build
    - Upload CSS + manifest.json
└── ❌ 4.5.5 **⚠️ MANDATORY:** Frontend Verification
    - Screenshot vehicle cards grid (desktop, tablet, mobile)
    - Screenshot detail modal
    - Test lazy loading (scroll down, verify images load)
    - Use frontend-verification skill

---

## 📋 FAZA 5: PER-SHOP BRAND FILTERING (8-10h)

**Cel:** Implement per-shop brand configuration and filtering in compatibility panel.

**Assigned Agents:** laravel-expert (shop model), livewire-specialist (UI integration)
**Dependencies:** FAZA 4 completed
**Deliverables:** Shop configuration, filtering logic, UI integration

### ❌ 5.1 Shop Configuration (Database)
└── ❌ 5.1.1 Add shop_vehicle_brands Column
    - Migration: add column to prestashop_shops table
    - Column type: JSON (array of brand names)
    - Default: NULL (no filtering, show all brands)
    - Example: ["YCF", "Pitbike", "Honda"]
    └── 📁 PLIK: database/migrations/YYYY_MM_DD_add_shop_vehicle_brands_to_prestashop_shops.php
└── ❌ 5.1.2 Update PrestashopShop Model
    - Add to $casts: 'shop_vehicle_brands' => 'array'
    - Add to $fillable: 'shop_vehicle_brands'
    - Method: getVehicleBrandsAttribute() - accessor
    - Method: setVehicleBrandsAttribute($value) - mutator
    └── 📁 PLIK: app/Models/PrestashopShop.php
└── ❌ 5.1.3 Run Migration
    - Local: php artisan migrate
    - Production: upload migration + run via plink

### ❌ 5.2 Shop Edit Form Integration
└── ❌ 5.2.1 Add "Marki Pojazdów" Section
    - Location: ShopForm component OR blade view
    - Section title: "Marki Pojazdów (filtrowanie dopasowań)"
    - Description: "Wybierz marki pojazdów które będą dostępne dla tego sklepu w systemie dopasowań"
    └── 📁 PLIK: resources/views/livewire/admin/shops/shop-form.blade.php (if exists)
└── ❌ 5.2.2 Multi-Select Checkboxes
    - Fetch all distinct brands from vehicle products:
      ```php
      Product::where('product_type', 'vehicle')
          ->distinct()
          ->pluck('manufacturer')
          ->sort()
      ```
    - Display as checkboxes: wire:model="shopData.vehicle_brands"
    - "Wszystkie" checkbox (select all, NULL value = no filter)
└── ❌ 5.2.3 Save Logic
    - On save: update shop_vehicle_brands column (JSON)
    - Validation: must be array OR NULL
    - Success message: "Marki pojazdów zaktualizowane"

### ❌ 5.3 Filtering Logic Implementation
└── ❌ 5.3.1 Update CompatibilityManager Service
    - Add method: filterVehiclesByShop($shopId, $vehicleQuery)
    - Load shop: PrestashopShop::find($shopId)
    - If shop.vehicle_brands NOT NULL:
      - Apply filter: WHERE manufacturer IN (shop.vehicle_brands)
    - If NULL: no filter (show all)
    - Return: filtered query
    └── 📁 PLIK: app/Services/CompatibilityManager.php
└── ❌ 5.3.2 Integration in CompatibilityManagement Component
    - Add shop dropdown filter (top of page)
    - wire:model.live="filterShopId"
    - On change: apply filterVehiclesByShop() to vehicle queries
    - Visual indicator: "Filtrowanie: Sklep X (Y marek)"

### ❌ 5.4 User Experience Enhancements
└── ❌ 5.4.1 Shop Selector Dropdown
    - Location: top of CompatibilityManagement panel
    - Dropdown: all shops + "Wszystkie sklepy" option
    - Default: "Wszystkie sklepy" (no filter)
    - Change: auto-filter vehicle lists, vehicle cards
└── ❌ 5.4.2 Visual Indicators
    - Badge: show filtered brands when shop selected
    - Example: "Filtrowanie: YCF, Pitbike (2 marki)"
    - Vehicle cards: dim cards NOT in filtered brands (optional)
└── ❌ 5.4.3 Bulk Edit Integration
    - In BulkEditCompatibilityModal: respect shop filter
    - If shop selected: only show vehicles from filtered brands in search
    - User can override: checkbox "Pokaż wszystkie marki"
└── ❌ 5.4.4 **⚠️ MANDATORY:** Frontend Verification
    - Screenshot shop edit form (multi-select brands)
    - Screenshot compatibility panel (shop dropdown, filtered results)
    - Test filtering (select shop, verify only configured brands visible)
    - Use frontend-verification skill

---

## 📋 FAZA 6: PRODUCTFORM INTEGRATION (8-10h)

**Cel:** Integrate compatibility management into ProductForm with conditional tabs (Spare Part vs. Vehicle).

**Assigned Agent:** livewire-specialist
**Dependencies:** FAZA 5 completed
**Deliverables:** Compatibility tabs in ProductForm, tested workflows

### ❌ 6.1 Spare Part Compatibility Tab
└── ❌ 6.1.1 Add "Dopasowania" Tab (Spare Parts Only)
    - Tab icon: 🔗 or ⚙️
    - Conditional display:
      ```blade
      @if($product->product_type === 'spare_part')
          <div x-show="activeTab === 'compatibility'">
              {{-- Compatibility content --}}
          </div>
      @endif
      ```
    - Tab order: after "Cechy" tab (or as appropriate)
    └── 📁 PLIK: resources/views/livewire/products/product-form.blade.php
└── ❌ 6.1.2 Embed CompatibilitySelector Component
    - Use existing CompatibilitySelector component:
      ```blade
      <livewire:product.compatibility-selector :product="$product" />
      ```
    - Already has: live search, add/remove vehicles, Oryginał/Zamiennik selection
└── ❌ 6.1.3 Quick Stats Display
    - Above CompatibilitySelector: show counts
    - "Oryginał: X pojazdów | Zamiennik: Y pojazdów | Razem: Z"
    - Badges with colors (green, orange, blue)
└── ❌ 6.1.4 Link to Global Panel
    - Button: "Zarządzaj masowo" (opens /admin/compatibility?part={sku})
    - Pre-filtered by current part
    - Target: _blank (new tab)

### ❌ 6.2 Vehicle Assigned Parts Tab
└── ❌ 6.2.1 Add "Przypisane Części" Tab (Vehicles Only)
    - Conditional display:
      ```blade
      @if($product->product_type === 'vehicle')
          <div x-show="activeTab === 'assigned_parts'">
              {{-- Assigned parts content --}}
          </div>
      @endif
      ```
    - Tab order: after "Cechy" tab
└── ❌ 6.2.2 Display Assigned Parts (Reverse Direction)
    - Query: vehicle_compatibilities WHERE vehicle_model_id = product.vehicle_model_id (if exists)
    - OR: WHERE vehicle_sku = product.sku (SKU FIRST fallback!)
    - Group by: compatibilityAttribute (Oryginał, Zamiennik)
    - Display: two sections (Oryginał parts, Zamiennik parts)
└── ❌ 6.2.3 Parts List Structure
    - Table OR cards layout
    - Columns: Part SKU, Name, Category, Type (Oryginał badge OR Zamiennik badge), Actions (Remove)
    - Sortable by: SKU, Name, Category
    - Pagination (if >50 parts)
└── ❌ 6.2.4 Add Parts Button
    - "Dodaj Części Zamienne" button (prominent)
    - Opens BulkEditCompatibilityModal (direction='vehicle_to_part')
    - Pre-select: current vehicle
    - Search parts → multi-select → add

### ❌ 6.3 Conditional Display Logic
└── ❌ 6.3.1 Product Type Detection
    - Check product.product_type column
    - Values: 'vehicle', 'spare_part', 'clothing', 'other'
    - If missing: determine by logic:
      - Is vehicle: has vehicle features (VIN, Engine No., etc.)
      - Is spare part: has compatibility records
      - Fallback: 'other'
└── ❌ 6.3.2 Tab Visibility Rules
    - Spare Part:
      - Show: "Dopasowania" tab (CompatibilitySelector)
      - Hide: "Przypisane Części" tab
    - Vehicle:
      - Show: "Przypisane Części" tab (assigned parts list)
      - Hide: "Dopasowania" tab (doesn't make sense)
    - Other/Clothing:
      - Hide both tabs
└── ❌ 6.3.3 Migration for product_type Column
    - If column missing: create migration
    - Migration: add product_type ENUM column
    - Default: 'other'
    - Backfill existing products:
      - If has VIN feature: 'vehicle'
      - If has vehicle compatibilities: 'spare_part'
      - Else: 'other'
    └── 📁 PLIK: database/migrations/YYYY_MM_DD_add_product_type_to_products.php

### ❌ 6.4 Quick Operations
└── ❌ 6.4.1 Quick Add Vehicle (Spare Part Tab)
    - Button: "Dodaj Pojazd" (next to search)
    - Opens mini modal: vehicle search (SKU or name)
    - Select vehicle → select type (Oryginał/Zamiennik) → add
    - Refresh list
└── ❌ 6.4.2 Quick Remove Vehicle
    - "X" button per vehicle in list
    - Confirmation: wire:confirm
    - Delete compatibility
    - Refresh list
└── ❌ 6.4.3 Quick Add Part (Vehicle Tab)
    - Button: "Dodaj Część" (similar to Quick Add Vehicle)
    - Part search → select → add
    - Type: Oryginał OR Zamiennik (radio buttons)
└── ❌ 6.4.4 **⚠️ MANDATORY:** Frontend Verification
    - Screenshot spare part tab (CompatibilitySelector)
    - Screenshot vehicle tab (assigned parts)
    - Test add/remove operations
    - Verify conditional display (based on product_type)
    - Use frontend-verification skill

---

## 📋 FAZA 7: PRESTASHOP SYNC VERIFICATION (10-12h)

**Cel:** Implement PrestaShop ps_feature* transformer, sync service, verification system.

**Assigned Agents:** prestashop-api-expert (transformer, sync), laravel-expert (batch processing)
**Dependencies:** FAZA 6 completed
**Deliverables:** Transformer, sync service, verification component

### ❌ 7.1 PrestaShop ps_feature* Transformer
└── ❌ 7.1.1 Create CompatibilityTransformer Class
    - Location: app/Services/PrestaShop/Transformers/CompatibilityTransformer.php
    - Method: transformToPrestashop($product, $shopId)
    - Return: array of ps_feature structures
    └── 📁 PLIK: app/Services/PrestaShop/Transformers/CompatibilityTransformer.php
└── ❌ 7.1.2 Transformation Logic
    - Input: Product with compatibilities (Oryginał, Zamiennik)
    - Output: array of ps_feature entries:
      ```php
      [
          [
              'feature_id' => X, // ps_feature.id_feature WHERE name = "Oryginał"
              'feature_values' => [ // array of vehicle names
                  ['value' => 'YCF Pilot 50', 'position' => 1],
                  ['value' => 'YCF Pilot 110', 'position' => 2],
                  ...
              ]
          ],
          [
              'feature_id' => Y, // ps_feature.id_feature WHERE name = "Zamiennik"
              'feature_values' => [...]
          ],
          [
              'feature_id' => Z, // ps_feature.id_feature WHERE name = "Model"
              'feature_values' => [...] // auto-generated (Oryginał + Zamiennik)
          ]
      ]
      ```
└── ❌ 7.1.3 Feature ID Mapping
    - Query PrestaShop database: find ps_feature.id_feature for "Oryginał", "Zamiennik", "Model"
    - If not exists: create features (INSERT INTO ps_feature, ps_feature_lang)
    - Cache feature IDs (to avoid repeated queries)
└── ❌ 7.1.4 Feature Value Creation
    - For each vehicle in compatibility:
      - Get vehicle full name: brand + model + variant
      - Check if value exists: ps_feature_value WHERE value = vehicle_name
      - If not exists: create (INSERT INTO ps_feature_value, ps_feature_value_lang)
      - Map: value_id → vehicle_name

### ❌ 7.2 PrestaShopSyncService Update
└── ❌ 7.2.1 Add syncProductCompatibility() Method
    - Parameters: $product, $shopId
    - Steps:
      1. Transform: $compatibilityData = CompatibilityTransformer::transform($product, $shopId)
      2. Delete existing: DELETE FROM ps_feature_product WHERE id_product = X AND id_feature IN (Oryginał, Zamiennik, Model)
      3. Insert new: INSERT INTO ps_feature_product (id_product, id_feature, id_feature_value)
      4. Handle errors: rollback on failure
    - Return: ['success' => bool, 'message' => string, 'synced_count' => int]
    └── 📁 PLIK: app/Services/PrestaShop/PrestaShopSyncService.php
└── ❌ 7.2.2 Batch Processing
    - Method: syncMultipleProductsCompatibility($productIds, $shopId)
    - Chunk products: 100 per batch (avoid memory issues)
    - Transaction per batch: DB::transaction()
    - Progress tracking: return ['processed' => count, 'success' => count, 'errors' => array]
└── ❌ 7.2.3 Error Handling
    - Try-catch per product
    - Log errors: Laravel log + database table (sync_errors)
    - Rollback batch on critical error
    - Continue on non-critical errors (log + skip)
└── ❌ 7.2.4 Multi-Language Support
    - PrestaShop: ps_feature_lang, ps_feature_value_lang
    - Detect shop languages: query ps_lang WHERE id_shop = X
    - Insert translations for each language (same value for now, can be enhanced later)

### ❌ 7.3 CompatibilityVerification Component
└── ❌ 7.3.1 Create Component
    - Livewire component (~250 linii target)
    - Properties:
      - filterShopId (shop to verify)
      - discrepancies (array) - PPM vs. PrestaShop differences
      - showDiscrepanciesOnly (bool) - toggle view
    - Methods:
      - mount($shopId)
      - verify() - compare PPM vs. PrestaShop
      - syncNow($productId) - sync single product
      - syncAll() - batch sync all discrepancies
    └── 📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityVerification.php
    └── 📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-verification.blade.php
└── ❌ 7.3.2 Verification Logic
    - Compare PPM compatibility data vs. PrestaShop ps_feature_product
    - Discrepancies:
      - Missing in PrestaShop: compatibility exists in PPM, not in PrestaShop
      - Missing in PPM: compatibility exists in PrestaShop, not in PPM
      - Mismatched: different vehicles in PPM vs. PrestaShop
    - Return: array of discrepancies with details
└── ❌ 7.3.3 Discrepancies Display
    - Table: Product SKU, Issue Type (Missing/Mismatched), PPM Data, PrestaShop Data, Actions (Sync Now)
    - Color-coded: red for missing, yellow for mismatched
    - Filter: show all OR only discrepancies
└── ❌ 7.3.4 Sync Actions
    - "Sync Now" button per product: sync immediately
    - "Sync All" button: batch sync all discrepancies (background job)
    - Progress bar: show sync progress (Livewire polling)

### ❌ 7.4 Testing & Verification
└── ❌ 7.4.1 Test Transformation
    - Create test product with compatibilities (Oryginał, Zamiennik)
    - Transform to ps_feature* format
    - Verify structure matches PrestaShop schema
└── ❌ 7.4.2 Test Sync to PrestaShop Staging
    - Use PrestaShop staging database (NOT production!)
    - Sync 10 products
    - Query ps_feature_product: verify records inserted
    - Query ps_feature_value: verify vehicle names created
└── ❌ 7.4.3 Test Model Auto-Generation
    - Verify Model feature_values = Oryginał + Zamiennik (union)
    - No duplicates in Model
    - Order: Oryginał first, then Zamiennik
└── ❌ 7.4.4 Test Batch Processing
    - Sync 100+ products
    - Monitor memory usage, execution time
    - Verify all products synced successfully
└── ❌ 7.4.5 **⚠️ MANDATORY:** Frontend Verification
    - Screenshot CompatibilityVerification component
    - Test sync workflow (single + batch)
    - Verify PrestaShop database (ps_feature_product records)
    - Use frontend-verification skill

---

## 📋 FAZA 8: DEPLOYMENT & VERIFICATION (6-8h)

**Cel:** Deploy to production, comprehensive testing, documentation, handover.

**Assigned Agent:** deployment-specialist
**Dependencies:** FAZA 7 completed, coding-style-agent review completed
**Deliverables:** Production deployment, verification report, agent reports

### ❌ 8.1 Pre-Deployment Checklist
└── ❌ 8.1.1 Code Review
    - **MANDATORY:** coding-style-agent review
    - CLAUDE.md compliance check
    - Context7 patterns verification
    - SKU first pattern verification (CRITICAL!)
└── ❌ 8.1.2 Testing Checklist
    - [ ] CompatibilityManagement panel tested (parts table, filters, expandable rows)
    - [ ] Dwukierunkowy bulk edit tested (Part→Vehicle, Vehicle→Part)
    - [ ] SKU first pattern verified (vehicle_sku NOT NULL in database)
    - [ ] Oryginał/Zamiennik/Model labels tested
    - [ ] Vehicle cards tested (grid, images, detail modal)
    - [ ] Per-shop brand filtering tested
    - [ ] ProductForm integration tested (spare part + vehicle tabs)
    - [ ] PrestaShop sync tested (staging database)
    - [ ] Browser compatibility (Chrome, Firefox, Edge)
└── ❌ 8.1.3 Database Backup
    - Backup production database (PPM + PrestaShop)
    - Store in _BACKUP/ folder
    - Timestamp: YYYY-MM-DD_HH-MM_pre_etap05d_deployment.sql
└── ❌ 8.1.4 Deployment Plan Review
    - Files to upload: 10+ PHP components, 10+ Blade views, CSS, migrations
    - Routes verification
    - Migrations ready (shop_vehicle_brands column)
    - Cache clear commands

### ❌ 8.2 Deployment to Production
└── ❌ 8.2.1 Upload Migrations & Run
    - pscp shop_vehicle_brands migration
    - pscp product_type migration (if created)
    - plink: php artisan migrate
    - Verify columns added
└── ❌ 8.2.2 Upload PHP Components
    - pscp CompatibilityManagement.php
    - pscp BulkEditCompatibilityModal.php
    - pscp VehicleCompatibilityCards.php
    - pscp CompatibilityTransformer.php
    - pscp PrestaShopSyncService updates
    - pscp CompatibilityVerification.php
    - pscp CompatibilityManager updates
    - pscp PrestashopShop model updates
    └── 📁 FILES: app/Http/Livewire/Admin/Compatibility/*.php, app/Services/**/*.php, app/Models/*.php
└── ❌ 8.2.3 Upload Blade Views
    - pscp all compatibility blade views
    - pscp updated product-form.blade.php
    - pscp updated shop-form.blade.php (if modified)
    └── 📁 FILES: resources/views/livewire/**/*.blade.php
└── ❌ 8.2.4 Upload CSS & Assets
    - npm run build
    - pscp CSS files
    - **CRITICAL:** pscp manifest.json to ROOT
    - Upload vehicle placeholder image (if created)
└── ❌ 8.2.5 Update Routes
    - Replace placeholder /admin/compatibility route
    - Upload updated web.php
    - Clear route cache: php artisan route:clear
    └── 📁 PLIK: routes/web.php
└── ❌ 8.2.6 Clear Cache
    - plink: php artisan view:clear
    - plink: php artisan cache:clear
    - plink: php artisan config:clear

### ❌ 8.3 Post-Deployment Verification
└── ❌ 8.3.1 **⚠️ MANDATORY:** Frontend Verification (Full Workflow)
    - Use frontend-verification skill
    - Login: admin@mpptrade.pl / Admin123!MPP
    - Navigate: https://ppm.mpptrade.pl/admin/compatibility
    - Screenshot: desktop full page
    - Screenshot: mobile responsive
└── ❌ 8.3.2 Parts Table Testing
    - Test filters (search, shop, brand, status)
    - Test sorting (SKU, counts)
    - Test expandable rows (Oryginał, Zamiennik, Model sections)
    - Verify counts accurate
└── ❌ 8.3.3 Bulk Edit Testing
    - Test Part→Vehicle: select 5 parts, add to 3 vehicles (Oryginał)
    - Test Vehicle→Part: select 1 vehicle, add 10 parts (Zamiennik)
    - Verify preview before apply
    - Verify database records (vehicle_compatibilities table)
    - **CRITICAL:** Verify vehicle_sku NOT NULL (SKU FIRST!)
└── ❌ 8.3.4 Vehicle Cards Testing
    - Navigate to vehicle cards view
    - Test filter by brand
    - Click vehicle card → detail modal opens
    - Test add parts to vehicle
    - Verify images display (or placeholder)
└── ❌ 8.3.5 Per-Shop Filtering Testing
    - Configure shop brands (shop edit form)
    - Select shop in compatibility panel
    - Verify only configured brands visible
    - Test bulk edit with shop filter
└── ❌ 8.3.6 ProductForm Integration Testing
    - Open spare part product
    - Navigate to "Dopasowania" tab
    - Add vehicles, remove vehicles
    - Open vehicle product
    - Navigate to "Przypisane Części" tab
    - Add parts, remove parts
└── ❌ 8.3.7 PrestaShop Sync Testing (STAGING ONLY!)
    - Use PrestaShop staging database (NOT production yet!)
    - Open CompatibilityVerification component
    - Test "Sync Now" (single product)
    - Verify ps_feature_product records in PrestaShop DB
    - Test "Sync All" (batch, 10 products)
    - Check for errors
└── ❌ 8.3.8 Browser Compatibility
    - Chrome (latest): full workflow
    - Firefox (latest): full workflow
    - Edge (latest): full workflow
└── ❌ 8.3.9 Responsive Design
    - Mobile (<768px): test all views
    - Tablet (768-1024px): test all views
    - Desktop (>1024px): native

### ❌ 8.4 Documentation & Reporting
└── ❌ 8.4.1 Update ETAP_05d Plan
    - Mark all tasks as ✅
    - Add completion timestamp
    - Update status: ✅ **UKOŃCZONY**
└── ❌ 8.4.2 Agent Reports (MANDATORY!)
    - **MANDATORY:** Create reports in _AGENT_REPORTS/
    - laravel-expert report (CompatibilityManager, migrations)
    - livewire-specialist report (components FAZA 1-6)
    - prestashop-api-expert report (transformer, sync - FAZA 7)
    - frontend-specialist report (CSS, layout)
    - deployment-specialist report (FAZA 8)
    - coding-style-agent report (code review)
└── ❌ 8.4.3 User Guide
    - Create _DOCS/USER_GUIDE_COMPATIBILITY.md
    - Sections:
      - Parts compatibility management (global panel)
      - Dwukierunkowy bulk edit (workflows)
      - Vehicle cards (visual selection)
      - Per-shop filtering (configuration)
      - PrestaShop sync (verification, sync)
    - Screenshots for all features
└── ❌ 8.4.4 PrestaShop Sync Documentation
    - Create _DOCS/PRESTASHOP_COMPATIBILITY_SYNC.md
    - Technical details:
      - ps_feature* structure mapping
      - Transformer logic
      - Batch processing
      - Error handling
      - Verification system
    - Deployment guide (staging → production)
└── ❌ 8.4.5 Handover & Next Steps
    - Inform user of ETAP_05d completion
    - Summary report (all features implemented)
    - Recommend next ETAP (if any)
    - Long-term maintenance plan

---

## ✅ COMPLIANCE CHECKLIST

### Context7 Integration
- [ ] **MANDATORY:** mcp__context7__get-library-docs for `/livewire/livewire`
- [ ] **MANDATORY:** mcp__context7__get-library-docs for `/websites/laravel_12_x`
- [ ] **MANDATORY:** mcp__context7__get-library-docs for PrestaShop API patterns (if available)
- [ ] Verify dispatch() event system
- [ ] Verify batch processing patterns (chunk())
- [ ] Verify transaction patterns (DB::transaction)

### CSS & Styling
- [ ] NO inline styles (100% CSS classes)
- [ ] Compatibility management styles in components.css
- [ ] Vehicle cards styles
- [ ] Modal styles
- [ ] Responsive design (mobile, tablet, desktop)
- [ ] npm run build + manifest.json ROOT upload

### Livewire 3.x Compliance
- [ ] wire:key in ALL @foreach loops
- [ ] dispatch() instead of emit()
- [ ] wire:model.live for reactive inputs
- [ ] wire:loading states for async actions
- [ ] Computed properties

### Service Layer Integration
- [ ] CompatibilityManager: ALL compatibility business logic
- [ ] CompatibilityTransformer: PrestaShop transformation logic
- [ ] PrestaShopSyncService: Sync logic
- [ ] NO direct model queries in Livewire components
- [ ] Database transactions for bulk operations

### SKU First Pattern (CRITICAL!)
- [ ] **MANDATORY:** vehicle_sku column populated in ALL new compatibilities
- [ ] Dual search: SKU primary, name secondary
- [ ] Fallback: use vehicle_sku if vehicle_model_id NULL
- [ ] Verification: SELECT * FROM vehicle_compatibilities WHERE vehicle_sku IS NULL (should return 0)
- [ ] Documentation: SKU_ARCHITECTURE_GUIDE.md compliance

### Database Architecture
- [ ] shop_vehicle_brands column added (JSON)
- [ ] product_type column added (if missing)
- [ ] Migrations executed (local + production)
- [ ] Database backup before deployment

### PrestaShop Integration
- [ ] ps_feature* structure compliance
- [ ] CompatibilityTransformer tested
- [ ] Sync tested on staging (NOT production initially!)
- [ ] Batch processing tested (100+ products)
- [ ] Error handling verified
- [ ] Multi-language support (ps_feature_lang, ps_feature_value_lang)

### Component Size Limits
- [ ] CompatibilityManagement.php: ~350 lines (JUSTIFIED for complexity)
- [ ] BulkEditCompatibilityModal.php: ~300 lines (JUSTIFIED for bi-directional logic)
- [ ] Other components: ≤300 lines
- [ ] Blade views: ≤250 lines (or justified)

### Frontend Verification (MANDATORY!)
- [ ] **FAZA 1:** Parts table + filters verification
- [ ] **FAZA 2:** Bulk edit modals verification (both directions)
- [ ] **FAZA 3:** Labels display verification (Oryginał/Zamiennik/Model)
- [ ] **FAZA 4:** Vehicle cards + detail modal verification
- [ ] **FAZA 5:** Per-shop filtering verification
- [ ] **FAZA 6:** ProductForm integration verification
- [ ] **FAZA 7:** PrestaShop sync verification (staging)
- [ ] **FAZA 8:** Full workflow verification (production)

### Agent Reports (MANDATORY!)
- [ ] laravel-expert report (service updates, migrations)
- [ ] livewire-specialist report (components, modals)
- [ ] prestashop-api-expert report (transformer, sync)
- [ ] frontend-specialist report (CSS, layout, images)
- [ ] deployment-specialist report (deployment, verification)
- [ ] coding-style-agent report (code review)
- [ ] All reports in _AGENT_REPORTS/ folder

### Accessibility (WCAG 2.1 AA)
- [ ] Semantic HTML
- [ ] ARIA labels
- [ ] Keyboard navigation
- [ ] Color contrast ≥4.5:1
- [ ] Screen reader support

### Responsive Design
- [ ] Mobile (<768px): single column, touch-friendly
- [ ] Tablet (768-1024px): 2-3 columns
- [ ] Desktop (>1024px): 4 columns, full features
- [ ] Images: lazy loading, fallback placeholders

### Performance
- [ ] Eager loading (N+1 prevention)
- [ ] Pagination (50 per page for parts, vehicles)
- [ ] Batch processing (100 products per batch for sync)
- [ ] Image optimization (thumbnails, lazy loading)
- [ ] Database indexes (sku, name, manufacturer)

### Security
- [ ] Authorization checks (role:manager+)
- [ ] CSRF protection (Livewire automatic)
- [ ] Input validation (server-side)
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] Transaction safety (rollback on error)

---

## 🤖 AGENT DELEGATION

### architect
- **Responsibility:** Plan approval, PrestaShop integration strategy, timeline review
- **Deliverables:** Approved plan, PrestaShop mapping strategy
- **Phase:** SEKCJA 0

### laravel-expert
- **Responsibility:** CompatibilityManager updates, migrations, batch processing logic
- **Deliverables:** Updated service, migrations, database optimizations
- **Phase:** FAZA 2, 5
- **Skills Used:** context7-docs-lookup (MANDATORY)

### livewire-specialist
- **Responsibility:** All Livewire components (CompatibilityManagement, BulkEditModal, VehicleCards, ProductForm)
- **Deliverables:** 6+ Livewire components + blade views
- **Phase:** FAZA 1-4, 6
- **Skills Used:** livewire-troubleshooting (if issues arise)

### prestashop-api-expert
- **Responsibility:** CompatibilityTransformer, PrestaShopSyncService, ps_feature* integration
- **Deliverables:** Transformer, sync service, verification system
- **Phase:** FAZA 7
- **Skills Used:** context7-docs-lookup (PrestaShop API patterns)

### frontend-specialist
- **Responsibility:** CSS styling, vehicle cards layout, responsive design, image optimization
- **Deliverables:** Updated components.css, vehicle card styles, optimized images
- **Phase:** FAZA 1, 4
- **Skills Used:** frontend-verification (MANDATORY)

### deployment-specialist
- **Responsibility:** Production deployment, PrestaShop sync testing (staging), verification
- **Deliverables:** Deployed application, verification report, PrestaShop integration verified
- **Phase:** FAZA 8
- **Skills Used:** hostido-deployment (automatic), frontend-verification (MANDATORY)

### coding-style-agent
- **Responsibility:** Code review BEFORE deployment (SKU first compliance, PrestaShop patterns)
- **Deliverables:** Code review report, compliance check
- **Phase:** FAZA 8 (pre-deployment)

---

## 📊 EXPECTED OUTCOMES

### User Experience
- **Professional Global Panel** - Manage all parts compatibility from one place
- **Bi-Directional Workflow** - Part→Vehicle AND Vehicle→Part bulk operations
- **Visual Vehicle Selection** - Cards with images, intuitive interface
- **Per-Shop Filtering** - Configure brands per shop, auto-filter
- **Integrated Workflows** - ProductForm tabs (conditional per product type)

### Technical Quality
- **SKU First Architecture** - Robust fallback, prevent mismatches
- **PrestaShop Compliance** - ps_feature* structure, verified sync
- **Performance** - Batch processing, lazy loading, pagination
- **Security** - Transactions, validation, authorization

### Business Impact
- **Efficiency:** 95% reduction in manual compatibility management time
- **Accuracy:** SKU first pattern eliminates vehicle mismatches
- **Scalability:** Support for 10,000+ parts, 1,000+ vehicles
- **Integration:** Seamless PrestaShop sync with verification

---

## 📝 NASTĘPNE KROKI PO UKOŃCZENIU

1. **Update Plan Status**
   - Mark ETAP_05d as ✅ **UKOŃCZONY**
   - Update completion timestamp

2. **Inform User**
   - Summary of all implemented features
   - Link: https://ppm.mpptrade.pl/admin/compatibility
   - Screenshots (before/after)
   - PrestaShop sync guide

3. **Long-Term Maintenance**
   - Monitor PrestaShop sync performance
   - Optimize batch processing (if needed)
   - User feedback collection
   - Feature enhancements (CSV import/export compatibility)

4. **Next ETAP (if applicable)**
   - Proceed to next business module (or mark project milestone complete)

---

**KONIEC ETAP_05d_Produkty_Dopasowania.md**

**Data utworzenia:** 2025-10-24
**Status:** ❌ NIE ROZPOCZĘTY (Awaiting approval & agent delegation)
**Estimated completion:** 7-9 dni roboczych po rozpoczęciu (or 8-10 dni with parallelization)
