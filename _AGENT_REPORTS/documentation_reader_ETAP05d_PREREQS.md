# RAPORT PRACY AGENTA: documentation-reader

**Data**: 2025-12-05
**Agent**: documentation-reader
**Zadanie**: Analiza dokumentacji dla ETAP_05d - System Dopasowań Części Zamiennych (Spare Parts Matching System)

---

## 📋 ODPOWIEDZI NA KLUCZOWE PYTANIA

### 1. Czy istnieje już dokumentacja dla systemu dopasowań (compatibility)?

✅ **TAK** - Istnieje obszerna dokumentacja:

**Agent Reports (3 dokumenty):**
- `architect_COMPATIBILITY_SYSTEM_REDESIGN.md` - Architektura systemu compatibility
- `import_export_EXCEL_COMPATIBILITY_ANALYSIS.md` - Szczegółowa analiza workflow importu Excel (780 linii)
- `frontend_COMPATIBILITY_TILES_UX_DESIGN.md` - Kompletna specyfikacja UX/UI (1562 linie)

**Plan Projektu:**
- `ETAP_05d_Produkty_Dopasowania.md` - Główny plan implementacji (6 faz)

**Dokumentacja Bazy:**
- `Struktura_Bazy_Danych.md` - Schema database z tabelami compatibility

**Istniejące Pliki Kodu:**
- `app/Models/VehicleCompatibility.php` - Model do zarządzania compatibility
- `app/Services/CompatibilityManager.php` - Service layer dla compatibility logic

---

### 2. Jakie są istniejące wzorce per-shop w projekcie?

✅ **WZORZEC: `product_shop_data` (JSON per-shop isolation)**

**Struktura z Struktura_Bazy_Danych.md:**
```php
// Tabela: products
'shop_data' => 'json' // Per-shop data isolation

// Struktura JSON:
{
  "shop_1": {
    "name": "Product Name for Shop 1",
    "description": "...",
    "categories": [1, 2, 3],
    "vehicle_matches": [...], // ← TUTAJ per-shop compatibility
    "sync_status": "synced",
    "prestashop_id": 123
  },
  "shop_2": { ... }
}
```

**Klucz główny:** `SKU` (uniwersalny identyfikator produktu)
**Per-shop data:** Przechowywane w `shop_data` JSON field
**Trait pattern:** `HasMultiStore` trait w modelach

**Wzorzec z ETAP_05b:**
- Trait-based architecture (np. `HasVariants`, `HasFeatures`, `HasMultiStore`)
- Per-shop isolation w `product_shop_data` pivot table
- Alpine.js state management z `@entangle` dla sync

---

### 3. Czy są konflikty z innymi ETAPami?

⚠️ **POTENCJALNE KONFLIKTY ZIDENTYFIKOWANE:**

#### A) ETAP_05b (Warianty) - STATUS: 63% ukończone
**Konflikt:** Warianty vs Compatibility relationship
- Warianty mają własne zdjęcia/atrybuty per-shop
- Compatibility może być na poziomie produktu RODZICA lub WARIANTU
- **Pytanie:** Czy dopasowania są per-produkt czy per-wariant?

**Z ETAP_05b_Produkty_Warianty.md:**
```
PHASE 4B: 🛠️ Per-Shop Variant Context
- variants_shop_data pivot table
- Per-shop prices/stock/images dla wariantów
```

**ROZWIĄZANIE WYMAGANE:** Określić poziom granulacji compatibility (parent vs variant)

#### B) ETAP_05a (Cechy) - BRAK PLIKU
**Zależność:** ETAP_05b wymienia ETAP_05a jako dependency
- Plik `ETAP_05a_Produkty_Cechy.md` NIE ISTNIEJE
- Cechy mogą być powiązane z compatibility (np. "Marka", "Model")
- **Blokada:** Brak dokumentacji dla systemu cech

#### C) ETAP_07 (PrestaShop API)
**Konflikt:** Synchronizacja compatibility z PrestaShop
- PrestaShop ma własny system "Accessories" i "Compatible Products"
- **Pytanie:** Jak mapować vehicle matches na PrestaShop features?

**Z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**
```
PrestaShop Sync Strategy:
- Option A: Custom feature groups per vehicle
- Option B: Product associations (accessories)
- Option C: Custom module (separate DB table)
```

---

### 4. Jakie są zależności z ETAP_05a i ETAP_05b?

✅ **DEPENDENCY CHAIN:** ETAP_05a → ETAP_05b → ETAP_05d

#### ETAP_05a (Cechy) - ⚠️ BRAK DOKUMENTACJI
**Wymagane z ETAP_05a:**
- System cech produktów (features/attributes)
- Możliwe pola: Marka, Model, Rok produkcji, VIN, Engine No.
- **BLOKADA:** Plik planu nie istnieje - wymaga uzupełnienia

**Z Excel analysis (import_export_EXCEL_COMPATIBILITY_ANALYSIS.md):**
```
Kolumny Excel potencjalnie związane z ETAP_05a:
- Parts Name
- U8 Code
- MRF CODE
- Model (vehicle model)
- VIN
- Engine No.
```

#### ETAP_05b (Warianty) - STATUS: 63% UKOŃCZONE
**Wymagane z ETAP_05b:**
- ✅ Trait-based architecture (`HasVariants`, `HasMultiStore`)
- ✅ Per-shop data isolation (`product_shop_data`)
- ✅ Alpine.js state management patterns
- 🛠️ **W TRAKCIE:** PHASE 4B - Per-Shop Variant Context

**Refactoring Pattern (do zastosowania w ETAP_05d):**
```
ProductFormVariants.php (1369 linii) → 6 traits:
- ProductFormVariants.php (158 linii core)
- ProductFormVariantsUI.php (variant display)
- ProductFormVariantsState.php (state management)
- ProductFormVariantsActions.php (create/edit/delete)
- ProductFormVariantsValidation.php (validation)
- ProductFormVariantsData.php (data transformation)
```

**Zalecenie:** Zastosować podobny pattern dla CompatibilityForm

---

## 📁 LISTA PRZECZYTANYCH DOKUMENTÓW

### Agent Reports (3 dokumenty)
1. ✅ `_AGENT_REPORTS/architect_COMPATIBILITY_SYSTEM_REDESIGN.md`
   - Architektura systemu compatibility
   - Database schema proposals

2. ✅ `_AGENT_REPORTS/import_export_EXCEL_COMPATIBILITY_ANALYSIS.md` (780 linii)
   - Analiza pliku Excel: 1591 produktów, 121 modeli pojazdów
   - System O/Z (Oryginał/Zamiennik): 15.5% produktów bez dopasowań
   - Top 3 marki: YCF (38.3%), KAYO (32.6%), MRF (14.3%)
   - Propozycja schema: `vehicle_models`, `product_vehicle_matches`, `product_vehicle_matches_history`
   - AI suggestion algorithm dla auto-matching

3. ✅ `_AGENT_REPORTS/frontend_COMPATIBILITY_TILES_UX_DESIGN.md` (1562 linie)
   - Kompletna specyfikacja CSS dla compatibility tiles
   - Alpine.js interaction patterns (selectionMode, toggleVehicle, bulk actions)
   - Responsive grid layouts (6/4/2 columns)
   - Badge system dla O/Z/M types
   - Blade template structure

### Plan Projektu (2 dokumenty)
4. ✅ `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md`
   - Główny plan implementacji (6 faz)
   - PHASE 1: Database & Models
   - PHASE 2: Import/Export
   - PHASE 3: UI (Livewire components)
   - PHASE 4: Per-Shop Context
   - PHASE 5: PrestaShop Sync
   - PHASE 6: Testing & Optimization

5. ✅ `Plan_Projektu/ETAP_05b_Produkty_Warianty.md` (dependency check)
   - Status: 63% ukończone (PHASE 1-4B done)
   - Trait-based refactoring pattern
   - Per-shop variant context implementation

### Dokumentacja Techniczna (1 dokument)
6. ✅ `_DOCS/Struktura_Bazy_Danych.md`
   - Schema database z 31+ tabelami
   - `product_shop_data` pivot table dla per-shop isolation
   - SKU jako primary identifier (nie auto-increment ID)

### Istniejące Pliki Kodu (2 pliki)
7. ✅ `app/Models/VehicleCompatibility.php` - Model exists
8. ✅ `app/Services/CompatibilityManager.php` - Service exists

### Dokumenty NIEZNALEZIONE (blokery)
❌ `Plan_Projektu/ETAP_05a_Produkty_Cechy.md` - **BRAK PLIKU** (dependency dla ETAP_05b i ETAP_05d)

---

## 🔗 KLUCZOWE ZALEŻNOŚCI

### 1. Database Schema (CRITICAL)
**Z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**

```sql
-- Tabela główna modeli pojazdów
CREATE TABLE vehicle_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    brand VARCHAR(100),
    model_code VARCHAR(100),
    year VARCHAR(50),
    INDEX idx_brand (brand),
    INDEX idx_model_code (model_code)
);

-- Pivot table dla dopasowań produkt-pojazd
CREATE TABLE product_vehicle_matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    vehicle_model_id INT NOT NULL,
    match_type ENUM('original', 'replacement', 'model') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_vehicle (product_id, vehicle_model_id)
);

-- Historia zmian (audit trail)
CREATE TABLE product_vehicle_matches_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    vehicle_model_id INT NOT NULL,
    match_type ENUM('original', 'replacement', 'model') NOT NULL,
    action ENUM('added', 'removed') NOT NULL,
    user_id INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### 2. Per-Shop Isolation Pattern
**Z Struktura_Bazy_Danych.md:**

```php
// products.shop_data JSON structure
{
  "shop_1": {
    "vehicle_matches": [
      {
        "vehicle_model_id": 42,
        "match_type": "original",
        "shop_specific": true
      }
    ],
    "sync_status": "synced",
    "last_sync": "2025-01-15 10:30:00"
  }
}
```

### 3. Trait-Based Architecture (z ETAP_05b)
**Zalecany pattern dla CompatibilityForm:**

```
app/Http/Livewire/Products/Management/Traits/
├── ProductFormCompatibility.php (core - 150-200 linii)
├── ProductFormCompatibilityUI.php (display tiles)
├── ProductFormCompatibilityState.php (Alpine.js state)
├── ProductFormCompatibilityActions.php (add/remove/bulk)
├── ProductFormCompatibilityValidation.php (validation rules)
└── ProductFormCompatibilityData.php (transformation)
```

### 4. Alpine.js State Management (z frontend_COMPATIBILITY_TILES_UX_DESIGN.md)
**Pattern dla interactive tiles:**

```javascript
Alpine.data('compatibilityManager', () => ({
    selectionMode: false,
    selectedVehicles: [],

    init() {
        this.$watch('selectionMode', value => {
            if (!value) this.selectedVehicles = [];
        });
    },

    toggleVehicle(vehicleId) {
        const index = this.selectedVehicles.indexOf(vehicleId);
        if (index === -1) {
            this.selectedVehicles.push(vehicleId);
        } else {
            this.selectedVehicles.splice(index, 1);
        }
    },

    bulkDelete() {
        this.$wire.call('bulkDeleteVehicles', this.selectedVehicles);
    }
}))
```

### 5. CSS Design System (z frontend_COMPATIBILITY_TILES_UX_DESIGN.md)
**Klasy do użycia:**

```css
/* Compatibility Tiles Grid */
.compatibility-tiles { /* responsive grid 6/4/2 cols */ }
.vehicle-tile { /* individual tile */ }
.vehicle-badge--original { /* O badge */ }
.vehicle-badge--zamiennik { /* Z badge */ }
.vehicle-badge--model { /* M badge */ }
.vehicle-tile--selected { /* selection state */ }
```

---

## ⚙️ WYMAGANE PREREQS PRZED FAZĄ 1

### A) DEPENDENCY RESOLUTION (KRYTYCZNE)

#### 1. ETAP_05a - System Cech (BLOKADA)
**Status:** ❌ Plik planu nie istnieje
**Wymagane:**
- [ ] Utworzyć `Plan_Projektu/ETAP_05a_Produkty_Cechy.md`
- [ ] Zdefiniować schema dla product features
- [ ] Określić relację features → compatibility

**Pytania do rozwiązania:**
- Czy cechy pojazdu (Marka, Model, Rok) są częścią ETAP_05a czy ETAP_05d?
- Jak mapować kolumny Excel (Model, VIN, Engine No.) na system cech?

#### 2. ETAP_05b - Warianty (63% ukończone)
**Status:** 🛠️ PHASE 4B w trakcie
**Wymagane przed ETAP_05d:**
- [ ] Ukończyć PHASE 4B: Per-Shop Variant Context
- [ ] Zdefiniować: Czy compatibility jest per-PARENT czy per-VARIANT?

**Scenariusze do rozważenia:**
```
SCENARIUSZ A: Compatibility per PARENT product
✅ Prostsze zarządzanie
✅ Mniej duplikacji danych
❌ Brak flexibility dla wariantów z różnymi dopasowaniami

SCENARIUSZ B: Compatibility per VARIANT
✅ Maksymalna granulacja
✅ Różne dopasowania dla różnych wariantów (np. kolor, rozmiar)
❌ Więcej complexity
❌ Większa objętość danych
```

**Zalecenie:** Konsultacja z użytkownikiem - jaki scenariusz odpowiada business logic?

---

### B) TECHNICAL SETUP

#### 1. Database Migrations (PHASE 1 - ETAP_05d)
**Wymagane migracje:**
- [ ] `create_vehicle_models_table.php`
- [ ] `create_product_vehicle_matches_table.php`
- [ ] `create_product_vehicle_matches_history_table.php`
- [ ] Update `products.shop_data` JSON structure (add vehicle_matches)

**Schema gotowe do implementacji** - z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md

#### 2. Model & Service Layer (PHASE 1 - ETAP_05d)
**Istniejące pliki (do weryfikacji/refactor):**
- [ ] Sprawdzić `app/Models/VehicleCompatibility.php` - czy aktualny?
- [ ] Sprawdzić `app/Services/CompatibilityManager.php` - czy zgodny z planem?

**Nowe pliki wymagane:**
- [ ] `app/Models/VehicleModel.php` - Model dla vehicle_models table
- [ ] `app/Models/ProductVehicleMatch.php` - Pivot model
- [ ] `app/Models/ProductVehicleMatchHistory.php` - Audit trail model
- [ ] Traits: `HasCompatibility` (dla Product model)

#### 3. Livewire Components (PHASE 3 - ETAP_05d)
**Wymagane komponenty:**
- [ ] `CompatibilityManager.php` (main component)
- [ ] `VehicleSearch.php` (search/autocomplete)
- [ ] `VehicleMatchModal.php` (add/edit modal)
- [ ] Traits (6 plików) - zgodnie z pattern z ETAP_05b

#### 4. Frontend Assets (PHASE 3 - ETAP_05d)
**CSS (GOTOWE w frontend_COMPATIBILITY_TILES_UX_DESIGN.md):**
- [ ] `resources/css/products/compatibility-tiles.css` (480 linii gotowego kodu)
- [ ] Import w `vite.config.js`

**Alpine.js (GOTOWE w frontend_COMPATIBILITY_TILES_UX_DESIGN.md):**
- [ ] `compatibilityManager` data component (75 linii gotowego kodu)
- [ ] Integration z Livewire via `@entangle`

#### 5. Excel Import/Export (PHASE 2 - ETAP_05d)
**Z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**
- [ ] `app/Imports/VehicleMatchImport.php` - Laravel-Excel import
- [ ] `app/Exports/VehicleMatchExport.php` - Laravel-Excel export
- [ ] AI Suggestion Algorithm (optional - dla auto-matching)

---

### C) BUSINESS LOGIC DECISIONS (WYMAGANE OD UŻYTKOWNIKA)

#### 1. Granulacja Compatibility
**Pytanie:** Czy dopasowania są per-PARENT product czy per-VARIANT?
- [ ] User decision: PARENT vs VARIANT vs BOTH

#### 2. Per-Shop Isolation
**Pytanie:** Czy każdy sklep może mieć RÓŻNE dopasowania dla tego samego produktu?
- [ ] User decision: Global matches vs Per-shop matches

**Z frontend_COMPATIBILITY_TILES_UX_DESIGN.md:**
```
"KONTEKST PER-SHOP:
- Dane Domyślne: Global vehicle matches (shared across all shops)
- Shop Context: Shop-specific overrides (add/remove vehicles per shop)"
```

**Implikacje:**
- Global: Prościej, ale mniej flexibility
- Per-shop: Bardziej złożone, ale zgodne z multi-store philosophy

#### 3. PrestaShop Sync Strategy (PHASE 5 - ETAP_05d)
**Pytanie:** Jak mapować vehicle matches na PrestaShop?

**Opcje z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**
```
A) Custom Feature Groups (ps_feature_value per vehicle)
   ✅ Native PrestaShop features
   ❌ Clutter w feature lists

B) Product Associations (ps_accessory)
   ✅ Native associations
   ❌ Semantic mismatch (accessories ≠ compatibility)

C) Custom Module (separate DB table)
   ✅ Clean separation
   ✅ Full control
   ❌ Requires custom PrestaShop module development
```

- [ ] User decision: Strategy A vs B vs C

---

## ⚠️ POTENCJALNE KONFLIKTY

### 1. ETAP_05b vs ETAP_05d - Granularity Conflict
**Problem:** Niejednoznaczna relacja warianty ↔ compatibility

**Z ETAP_05b:**
- Warianty mają per-shop prices/stock/images
- Trait-based architecture

**Z ETAP_05d:**
- Compatibility może być per-product LUB per-variant
- Brak definicji w planie

**ROZWIĄZANIE:**
- [ ] Zdefiniować relationship w database schema:
  - Jeśli PARENT: `product_vehicle_matches.product_id` → `products.id`
  - Jeśli VARIANT: `product_vehicle_matches.variant_id` → `product_variants.id`
  - Jeśli BOTH: Dodać `variant_id` jako nullable foreign key

**Zalecenie:** PARENT level (prostsze), z możliwością override per-variant w przyszłości

---

### 2. ETAP_05a (Cechy) - Missing Plan
**Problem:** ETAP_05b i ETAP_05d wymagają ETAP_05a, ale plik planu nie istnieje

**Impact:**
- Brak definicji systemu cech (features/attributes)
- Brak mapowania kolumn Excel → cechy produktu
- Potencjalna duplikacja (cechy vs compatibility)

**ROZWIĄZANIE:**
- [ ] **PRIORYTET 1:** Utworzyć Plan_Projektu/ETAP_05a_Produkty_Cechy.md
- [ ] Zdefiniować relationship: Features → Compatibility
- [ ] Rozważyć: Czy vehicle model info (Marka, Model, Rok) jest częścią Features czy Compatibility?

**Zalecenie:** Vehicle info jako część ETAP_05d (Compatibility), nie ETAP_05a (Features)

---

### 3. ETAP_07 (PrestaShop API) - Sync Strategy Uncertainty
**Problem:** Brak decyzji jak synchronizować compatibility z PrestaShop

**Z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**
- 3 możliwe strategie (A/B/C)
- Każda ma trade-offs

**Impact:**
- Database schema może wymagać modyfikacji zależnie od strategii
- Service layer (`CompatibilityManager`) musi obsługiwać sync logic

**ROZWIĄZANIE:**
- [ ] User decision: Strategy BEFORE PHASE 5 implementation
- [ ] Jeśli Strategy C (Custom Module): Wymaga development po stronie PrestaShop

**Zalecenie:** Strategy A (Custom Feature Groups) - native PrestaShop, acceptable trade-offs

---

### 4. Excel Import - Column Mapping Ambiguity
**Problem:** 136 kolumn w Excel, ale nie wszystkie są jasno zdefiniowane

**Z import_export_EXCEL_COMPATIBILITY_ANALYSIS.md:**
```
Kolumny z wartościami O/Z (vehicle matches):
- YCF C10 50 (O/Z values)
- KAYO ES50AC (O/Z values)
- MRF RT-100 (O/Z values)
... (121 kolumn vehicle models)

Ale też:
- Model (vehicle model name?)
- VIN (Vehicle Identification Number?)
- Engine No. (engine number?)
```

**Pytania:**
- Czy kolumna "Model" to nazwa modelu pojazdu, czy kod produktu?
- Czy VIN/Engine No. są częścią compatibility, czy product features?
- Czy O/Z wartości w kolumnach vehicle models są binarne (O=1, Z=1, puste=0), czy tekstowe?

**ROZWIĄZANIE:**
- [ ] Analiza rzeczywistego pliku Excel (user dostarczy przykład)
- [ ] Mapowanie kolumn → database fields (validation rules)

**Zalecenie:** User preview Excel file + confirm mapping BEFORE import implementation

---

## 📌 NASTĘPNE KROKI

### IMMEDIATE (przed rozpoczęciem PHASE 1)

1. **KRYTYCZNE - Dependency Resolution:**
   - [ ] **User Decision:** Utworzyć Plan_Projektu/ETAP_05a_Produkty_Cechy.md?
   - [ ] **User Decision:** Compatibility per PARENT vs VARIANT vs BOTH?
   - [ ] **User Decision:** Global matches vs Per-shop matches?

2. **KRYTYCZNE - Ukończyć ETAP_05b:**
   - [ ] Verify PHASE 4B status (per-shop variant context)
   - [ ] Confirm variants system ready for ETAP_05d dependency

3. **Excel Analysis:**
   - [ ] User dostarczy przykładowy plik Excel (JK25154D*.xlsx)
   - [ ] Validate column mapping (136 kolumn → database fields)
   - [ ] Confirm O/Z value format (binary vs text vs enum)

---

### PHASE 1 - Database & Models (ETAP_05d)

**Zależności spełnione:**
- [x] Database schema defined (import_export_EXCEL_COMPATIBILITY_ANALYSIS.md)
- [x] Per-shop pattern known (Struktura_Bazy_Danych.md)
- [ ] User decisions (granularity, per-shop, PrestaShop strategy)

**Tasks:**
1. [ ] Create migrations (vehicle_models, product_vehicle_matches, matches_history)
2. [ ] Create models (VehicleModel, ProductVehicleMatch, ProductVehicleMatchHistory)
3. [ ] Add trait `HasCompatibility` to Product model
4. [ ] Update `products.shop_data` JSON structure
5. [ ] Write model tests (PHPUnit)

**Estimated:** 1-2 dni (zależnie od user decisions)

---

### PHASE 2 - Import/Export (ETAP_05d)

**Zależności:**
- [ ] PHASE 1 complete (database + models)
- [ ] Excel column mapping confirmed
- [ ] AI suggestion algorithm decision (optional feature?)

**Tasks:**
1. [ ] Create VehicleMatchImport (Laravel-Excel)
2. [ ] Create VehicleMatchExport (Laravel-Excel)
3. [ ] Implement validation rules (O/Z values, vehicle model existence)
4. [ ] (Optional) Implement AI suggestion algorithm
5. [ ] Write import/export tests

**Estimated:** 2-3 dni

---

### PHASE 3 - UI (Livewire Components) (ETAP_05d)

**Zależności:**
- [ ] PHASE 1-2 complete
- [x] Frontend spec ready (frontend_COMPATIBILITY_TILES_UX_DESIGN.md)
- [x] CSS ready (compatibility-tiles.css - 480 linii)
- [x] Alpine.js patterns ready (compatibilityManager - 75 linii)

**Tasks:**
1. [ ] Create CompatibilityManager Livewire component (6 traits pattern)
2. [ ] Create VehicleSearch component (autocomplete)
3. [ ] Create VehicleMatchModal component (add/edit)
4. [ ] Implement Blade templates (tiles, badges, modals)
5. [ ] Add CSS to vite.config.js
6. [ ] Write component tests (Livewire testing)

**Estimated:** 3-4 dni

---

### PHASE 4 - Per-Shop Context (ETAP_05d)

**Zależności:**
- [ ] PHASE 1-3 complete
- [ ] User decision: Global vs Per-shop matches
- [x] Per-shop pattern known (product_shop_data)

**Tasks:**
1. [ ] Implement shop context switching (Dane Domyślne vs Shop Tab)
2. [ ] Update shop_data JSON structure (vehicle_matches array)
3. [ ] Implement per-shop override logic (CompatibilityManager)
4. [ ] Add sync status indicators (synced/pending/conflict)
5. [ ] Write per-shop tests

**Estimated:** 2-3 dni

---

### PHASE 5 - PrestaShop Sync (ETAP_05d)

**Zależności:**
- [ ] PHASE 1-4 complete
- [ ] User decision: PrestaShop sync strategy (A/B/C)
- [ ] ETAP_07 (PrestaShop API) - sync infrastructure ready

**Tasks:**
1. [ ] Implement sync strategy (A: Feature Groups, B: Associations, C: Custom Module)
2. [ ] Create PrestaShopCompatibilitySync service
3. [ ] Implement conflict resolution (PPM vs PrestaShop differences)
4. [ ] Add sync queue jobs (background processing)
5. [ ] Write sync tests

**Estimated:** 3-5 dni (zależnie od strategy)

---

### PHASE 6 - Testing & Optimization (ETAP_05d)

**Zależności:**
- [ ] PHASE 1-5 complete

**Tasks:**
1. [ ] End-to-end testing (import → UI → sync → PrestaShop)
2. [ ] Performance optimization (query optimization, caching)
3. [ ] User acceptance testing (UAT)
4. [ ] Documentation update (user guide, technical docs)
5. [ ] Deployment to production

**Estimated:** 2-3 dni

---

## 🎯 TOTAL ESTIMATED TIME: 13-20 dni roboczych

**Breakdown:**
- PHASE 1: 1-2 dni
- PHASE 2: 2-3 dni
- PHASE 3: 3-4 dni
- PHASE 4: 2-3 dni
- PHASE 5: 3-5 dni
- PHASE 6: 2-3 dni

**Krytyczna ścieżka:**
```
User Decisions → ETAP_05a Plan → ETAP_05b Complete → ETAP_05d PHASE 1 → ... → PHASE 6
```

---

## 📊 PODSUMOWANIE

### ✅ GOTOWE DO UŻYCIA
- [x] Database schema (3 tabele + JSON structure)
- [x] Frontend spec (CSS + Alpine.js + Blade)
- [x] Import/Export workflow (Excel analysis)
- [x] Per-shop pattern (product_shop_data)
- [x] Trait-based architecture (z ETAP_05b)

### ⚠️ WYMAGAJĄ DECYZJI UŻYTKOWNIKA
- [ ] Compatibility granularity (PARENT vs VARIANT)
- [ ] Per-shop isolation (Global vs Per-shop)
- [ ] PrestaShop sync strategy (A vs B vs C)
- [ ] Excel column mapping (validation)
- [ ] ETAP_05a creation (cechy vs compatibility)

### 🚫 BLOKERY
- [ ] **ETAP_05a** - Plan nie istnieje (dependency dla ETAP_05b i ETAP_05d)
- [ ] **ETAP_05b** - PHASE 4B w trakcie (per-shop variant context)
- [ ] **User Decisions** - 5 kluczowych decyzji wymaganych przed PHASE 1

---

## 🔍 REKOMENDACJE

### PRIORITY 1 (KRYTYCZNE)
1. **Utworzyć Plan_Projektu/ETAP_05a_Produkty_Cechy.md**
   - Zdefiniować system cech produktów
   - Określić relationship: Features → Compatibility
   - Rozwiązać dependency chain (05a → 05b → 05d)

2. **User Decisions (5 pytań)**
   - Compatibility: PARENT level (zalecane)
   - Per-shop: Per-shop isolation (zgodne z multi-store philosophy)
   - PrestaShop: Strategy A - Feature Groups (native, acceptable trade-offs)
   - Excel: Dostarczyć przykładowy plik dla validation
   - ETAP_05a: Czy tworzymy, czy pomijamy?

### PRIORITY 2 (WYSOKIE)
3. **Ukończyć ETAP_05b PHASE 4B**
   - Per-shop variant context
   - Verify variants system ready

4. **Verify existing code**
   - Sprawdzić VehicleCompatibility.php - czy aktualny?
   - Sprawdzić CompatibilityManager.php - czy zgodny z planem?

### PRIORITY 3 (ŚREDNIE)
5. **Przygotować development environment**
   - Setup testowej bazy danych
   - Przygotować przykładowe dane (vehicle models, products)
   - Verify Laravel-Excel installed

---

**KONIEC RAPORTU**

**Status:** ✅ Dokumentacja przeczytana i przeanalizowana
**Następny krok:** Oczekiwanie na User Decisions (5 pytań w sekcji BLOKERY)
**Agent:** documentation-reader ready for next task after blockers resolved
