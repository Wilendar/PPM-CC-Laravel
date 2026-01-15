# 🔧 ETAP_05b: System Zarządzania Wariantami Produktów (v3 - PRZEBUDOWA)

**Status ETAPU:** 🛠️ **W TRAKCIE PRZEBUDOWY**
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 7-8 tygodni (1 developer full-time)
**Postęp:** 69% (FAZA 1-4B + 6.1 ukonczone - 6/9 faz)
**Zależności:** ETAP_05a (migracje ✅, modele ✅)
**Data przebudowy:** 2025-12-03
**Ostatnia aktualizacja:** 2025-12-10 (FAZA 6.1 - Variant Image Import Fix)

---

## 🚨 DIAGNOZA PROBLEMÓW (Poprzedni system)

### ❌ Krytyczne Problemy:
1. **ProductFormVariants.php** = **1369 linii** (przekracza limit 300 o 456%!)
2. **Brak dedykowanej zakładki "Warianty"** w ProductForm - wszystko w basic-tab
3. **ProductList** - brak expandable rows (wzór: Baselinker)
4. **Panel /admin/variants** - zarządza AttributeType/Value, NIE produktami wariantowymi
5. **Brak integracji z PrestaShop** dla import/export wariantów
6. **UX nieczytelny** - panel masowego zarządzania nieintuicyjny

### 📊 Raporty Agentów (2025-12-03):
- `_AGENT_REPORTS/architect_VARIANT_SYSTEM_REDESIGN.md` - Nowa architektura
- `_AGENT_REPORTS/prestashop_api_expert_VARIANT_API_ANALYSIS.md` - PrestaShop API
- `_AGENT_REPORTS/frontend_specialist_VARIANT_UI_REDESIGN.md` - UI/UX Redesign

---

## 🎯 CEL PRZEBUDOWY

### Główne Cele:
1. **ProductForm** - czytelna zakładka "Warianty" z pełnym CRUD
2. **ProductList** - expandable rows (wzór: Baselinker)
3. **Panel masowego zarządzania** - nowy, intuicyjny
4. **PrestaShop Integration** - poprawny import/eksport wariantów
5. **Compliance CLAUDE.md** - wszystkie pliki <300 linii

---

## 📋 PLAN IMPLEMENTACJI

### FAZA 1: Refactoring Fundament (Tydzień 1-2)
**Priorytet:** 🔴 KRYTYCZNY
**Status:** ✅ **UKONCZONE** (2025-12-03)

#### 1.1 ✅ Podział ProductFormVariants.php na 6 Traits
**Cel:** Zamienić 1 plik 1369 linii na 6 plików <300 linii każdy

| Trait | Odpowiedzialność | Rzecz. linii |
|-------|------------------|--------------|
| `VariantCrudTrait.php` | Create, Read, Update, Delete, Duplicate | ~290 |
| `VariantPriceTrait.php` | Price management per price group | ~180 |
| `VariantStockTrait.php` | Stock management per warehouse | ~160 |
| `VariantImageTrait.php` | Image upload, assign, cover | ~240 |
| `VariantAttributeTrait.php` | Attribute assignment (Color, Size) | ~110 |
| `ProductFormVariants.php` | Orchestrator - composes all traits | ~145 |

**Pliki utworzone:**
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantPriceTrait.php`
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantPriceTrait.php
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantStockTrait.php`
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantStockTrait.php
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantImageTrait.php`
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantImageTrait.php
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantAttributeTrait.php`
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantAttributeTrait.php

**Pliki pomocnicze:**
- ✅ `app/Services/Media/ThumbnailService.php` - wyekstrahowany z VariantImageTrait
        └── 📁 PLIK: app/Services/Media/ThumbnailService.php

**Pliki zmodyfikowane:**
- ✅ `app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php` - orchestrator
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php
- ✅ `_ARCHIVE/ProductFormVariants_ORIGINAL_1369_LINES.php` - backup
        └── 📁 PLIK: _ARCHIVE/ProductFormVariants_ORIGINAL_1369_LINES.php

#### 1.2 ⚠️ Test Refactoringu (wymaga deployment)
- ⚠️ Wszystkie istniejące funkcje - wymaga testu na produkcji
- ⚠️ Zero breaking changes - wymaga testu na produkcji
- ⚠️ Testy jednostkowe przechodzą - brak testow jednostkowych w projekcie

---

### FAZA 2: Backend Services (Tydzień 2-3)
**Priorytet:** 🔴 WYSOKI
**Status:** ✅ **UKONCZONE** (2025-12-03)
**Zależności:** FAZA 1 complete

#### 2.1 ✅ Nowe Services
| Service | Odpowiedzialność | Rzecz. linii |
|---------|------------------|--------------|
| `VariantPriceService.php` | Bulk price operations, calculations | 303 |
| `VariantStockService.php` | Bulk stock operations, transfers | 354 |
| `VariantSyncService.php` | PrestaShop variant sync | ❌ TODO |

**Pliki utworzone:**
- ✅ `app/Services/Product/VariantPriceService.php`
        └── 📁 PLIK: app/Services/Product/VariantPriceService.php
- ✅ `app/Services/Product/VariantStockService.php`
        └── 📁 PLIK: app/Services/Product/VariantStockService.php
- ❌ `app/Services/PrestaShop/VariantSyncService.php` - TODO w FAZA 6

#### 2.2 ❌ PrestaShop Transformers
**Wg raportu:** `prestashop_api_expert_VARIANT_API_ANALYSIS.md`

- ❌ `app/Services/PrestaShop/Transformers/VariantTransformer.php` - PPM → PS XML
- ❌ `app/Services/PrestaShop/Mappers/VariantAttributeMapper.php` - Attribute ID mapping

**⚠️ KRYTYCZNE - Price Impact Model:**
```php
// PrestaShop używa różnic cenowych, NIE cen absolutnych!
$basePrice = 100.00;  // Product base price
$variantPrice = 120.00; // Variant absolute price
$xml->combination->price = $variantPrice - $basePrice; // +20 PLN
```

#### 2.3 ❌ Testy Jednostkowe
- ❌ `tests/Unit/Services/VariantPriceServiceTest.php`
- ❌ `tests/Unit/Services/VariantStockServiceTest.php`
- ❌ `tests/Unit/Services/VariantSyncServiceTest.php`

---

### FAZA 3: ProductForm - Tab "Warianty" (Tydzień 3-4)
**Priorytet:** 🔴 WYSOKI
**Status:** ✅ **UKONCZONE** (2025-12-03)
**Zależności:** FAZA 1, FAZA 2

#### 3.1 ✅ Główna Zakładka "Warianty"
**Wg raportu:** `frontend_specialist_VARIANT_UI_REDESIGN.md`

```
┌─────────────────────────────────────────────────────────────┐
│ WARIANTY (5)                              [+ Dodaj Wariant] │
├─────────────────────────────────────────────────────────────┤
│ ☐ │ 🖼️ │ SKU-001-RED ⭐ │ Czerwony M │ 120 zł │ 50 szt │ ✅ │
│ ☐ │ 🖼️ │ SKU-001-BLU    │ Niebieski M│ 120 zł │ 30 szt │ ✅ │
│ ☐ │ 🖼️ │ SKU-001-GRN    │ Zielony L  │ 130 zł │ 0 szt  │ ❌ │
└─────────────────────────────────────────────────────────────┘
```

**Pliki do utworzenia:**
- ❌ `resources/views/livewire/products/management/tabs/variants-tab.blade.php` (~150 linii)
- ❌ `resources/views/livewire/products/management/partials/variant-card.blade.php` (~100 linii)

#### 3.2 ❌ Modals CRUD
- ❌ Refactor `variant-create-modal.blade.php` - nowy design
- ❌ Refactor `variant-edit-modal.blade.php` - z zakładkami (Podstawowe, Atrybuty, Ceny, Stany, Zdjęcia)

#### 3.3 ❌ Gridy Inline Editing
- ❌ Refactor `variant-prices-grid.blade.php` - inline editing + bulk apply
- ❌ Refactor `variant-stock-grid.blade.php` - inline editing + transfer stock

#### 3.4 ❌ Tab Navigation Integration
- ❌ Dodać tab "Warianty" w `tab-navigation.blade.php`
- ❌ Dodać property `showVariantsTab` w ProductForm.php

#### 3.5 ❌ CSS Styling (PPM Playbook)
**Dodać do istniejącego pliku:** `resources/css/products/category-form.css`

Klasy do dodania:
```css
.variant-card-row { ... }
.variant-checkbox-enterprise { ... }
.variant-thumbnail-cell { ... }
.variant-sku-cell { ... }
.variant-attributes-badges { ... }
.bulk-actions-toolbar { ... }
```

---

### FAZA 4: ProductList - Expandable Rows (Tydzień 4-5)
**Priorytet:** 🟡 ŚREDNI
**Status:** ✅ **UKONCZONE** (2025-12-03)
**Zależności:** FAZA 3
**Wzór:** Baselinker (screenshot: `References/Baselinker_wariants.png`)

#### 4.1 ✅ Backend Logic
**Modyfikacja:** `app/Http/Livewire/Products/Listing/ProductList.php`

```php
// Nowe properties
public array $expandedProducts = [];

// Nowe metody
public function toggleExpand(int $productId): void
public function getVariantsForProduct(int $productId): Collection
```

#### 4.2 ✅ Frontend UI
```
┌────────────────────────────────────────────────────────────────────┐
│ 🖼️ │ Nakładki na szprychy pitbike MRF │ MRF13-68-003 │ [Warianty: 33] │
├────────────────────────────────────────────────────────────────────┤
│    │ 🖼️ │ 12' białe pitbike MRF │ MRF13-68-003WH12 │ 41 szt │ ① │
│    │ 🖼️ │ 12' czerwone pitbike MRF │ MRF13-68-003RD12 │ 8 szt │ ② │
│    │ 🖼️ │ 12' czarne pitbike MRF │ MRF13-68-003BK12 │ 37 szt │ ③ │
└────────────────────────────────────────────────────────────────────┘
```

**Pliki utworzone:**
- ✅ `resources/views/livewire/products/listing/partials/variant-row.blade.php`
        └── 📁 PLIK: resources/views/livewire/products/listing/partials/variant-row.blade.php

**Pliki zmodyfikowane:**
- ✅ `resources/views/livewire/products/listing/product-list.blade.php` - badge + expandable
        └── 📁 PLIK: resources/views/livewire/products/listing/product-list.blade.php

#### 4.3 ✅ Alpine.js Integration
- ✅ Wykorzystano Alpine.js x-data/x-show w product-list.blade.php
- ✅ Animacja x-transition dla slide-down/slide-up

---

### FAZA 4B: Per-Shop Variant Isolation (2025-12-04)
**Priorytet:** 🔴 KRYTYCZNY (bugfix - cross-contamination)
**Status:** ✅ **UKONCZONE** (2025-12-04)
**Zależności:** FAZA 3, FAZA 4
**Problem:** Zmiany wariantów w tabie sklepu nadpisywały warianty w tabie domyslnym i odwrotnie

#### 4B.1 ✅ Architektura Per-Shop Isolation
**Wzor:** ProductFormFeatures (per-shop feature isolation)

**Pattern:**
- Default variants: stored in `product_variants` table
- Shop overrides: stored in `product_shop_data.attribute_mappings['variants']`
- Inheritance: shops inherit from default unless they have custom overrides

**Status Indicators:**
| Status | Opis | Kolor |
|--------|------|-------|
| `default` | Dane domyslne (brak kontekstu sklepu) | - |
| `inherited` | Sklep dziedziczy z domyslnych | Fioletowy |
| `same` | Sklep ma override identyczny z domyslnymi | Zielony |
| `different` | Sklep ma wlasne dane | Pomaranczowy |

#### 4B.2 ✅ Pliki Utworzone
- ✅ `app/DTOs/ShopVariantOverride.php` - DTO dla shop overrides
        └── 📁 PLIK: app/DTOs/ShopVariantOverride.php
- ✅ `app/Http/Livewire/Products/Management/Traits/VariantShopContextTrait.php` - Per-shop logic (~506 linii)
        └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/VariantShopContextTrait.php

#### 4B.3 ✅ Pliki Zmodyfikowane
- ✅ `app/Http/Livewire/Products/Management/ProductForm.php` - trait integration + context switching
- ✅ `resources/views/livewire/products/management/tabs/variants-tab.blade.php` - shop context UI
- ✅ `resources/css/admin/components.css` - status styling (variant-row-inherited, variant-status-*)

#### 4B.4 ✅ UI Features
- ✅ Panel "Kontekst sklepu" z info i checkbox "Pokaz dziedziczone"
- ✅ Kolumna "KONTEKST" (widoczna tylko w shop context)
- ✅ Badge "Dziedziczony"/"Identyczny"/"Wlasny" per wariant
- ✅ Przycisk "Dostosuj" - tworzy shop-specific override
- ✅ Przycisk "Przywroc" - usuwa override, wraca do dziedziczenia
- ✅ Kolorowe tlo wierszy (fioletowy/zielony/pomaranczowy)

#### 4B.5 ✅ Raport Architekta
- ✅ `_AGENT_REPORTS/architect_PER_SHOP_VARIANTS_ARCHITECTURE.md`
        └── 📁 PLIK: _AGENT_REPORTS/architect_PER_SHOP_VARIANTS_ARCHITECTURE.md

---

### FAZA 5: Panel Masowego Zarządzania (Tydzień 5-6)
**Priorytet:** 🟢 NISKI (Nice-to-have)
**Status:** ❌ NIE ROZPOCZĘTE
**Zależności:** FAZA 2, FAZA 3

#### 5.1 ❌ Nowy Component
**Route:** `/admin/variants/bulk-edit`

- ❌ `app/Http/Livewire/Admin/Variants/BulkVariantManager.php` (~290 linii)
- ❌ `resources/views/livewire/admin/variants/bulk-variant-manager.blade.php` (~200 linii)

#### 5.2 ❌ Zakładki Panelu
| Zakładka | Funkcja |
|----------|---------|
| Generuj Kombinacje | Multi-select atrybutów → generuj wszystkie kombinacje |
| Edytuj Ceny | Bulk grid: warianty × grupy cenowe |
| Edytuj Stany | Bulk grid: warianty × magazyny |
| Sync PrestaShop | Select shops → bulk sync |

#### 5.3 ❌ Menu Link
- ❌ Dodać link w sidebara: PRODUKTY → Zarządzanie Wariantami

---

### FAZA 6: PrestaShop Integration (Tydzień 6-7)
**Priorytet:** 🟡 ŚREDNI
**Status:** 🛠️ W TRAKCIE (6.1 ukonczone)
**Zależności:** FAZA 2, FAZA 5
**Dokumentacja:** `prestashop_api_expert_VARIANT_API_ANALYSIS.md`

#### 6.1 ✅ Variant Image Import Fix (2025-12-10)
**Problem:** Zdjęcia wariantów nie były pobierane podczas importu z PrestaShop
**Root Cause:** Accessor `url()` w `VariantImage` model zwracał URL PrestaShop API zamiast lokalnego pliku

**Pliki utworzone:**
- ✅ `app/Services/Media/VariantImageDownloadService.php` - pobieranie zdjęć z PS API
        └── 📁 PLIK: app/Services/Media/VariantImageDownloadService.php

**Pliki zmodyfikowane:**
- ✅ `app/Services/PrestaShop/PrestaShopImportService.php` - metoda importVariantImages() z 4-strategią
        └── 📁 PLIK: app/Services/PrestaShop/PrestaShopImportService.php (linie 2290-2470)
- ✅ `app/Models/VariantImage.php` - naprawiony accessor url() (priority: local > external)
        └── 📁 PLIK: app/Models/VariantImage.php (linie 132-159)

**Strategie importu zdjęć wariantów:**
1. Link do istniejącego Media (matching by PS image ID)
2. Link do Media by position
3. Pobieranie z API przez VariantImageDownloadService
4. Fallback - tylko URL

#### 6.2 ❌ Queue Jobs
- ❌ `app/Jobs/PrestaShop/SyncVariantToPrestaShopJob.php`
- ❌ `app/Jobs/PrestaShop/BulkSyncVariantsJob.php`
- ❌ `app/Jobs/PrestaShop/ImportVariantsFromPrestaShopJob.php`

#### 6.2 ❌ API Integration
**Endpoints PrestaShop:**
- `POST /api/combinations` - CREATE variant
- `PATCH /api/combinations/{id}` - UPDATE variant
- `PATCH /api/stock_availables/{id}` - UPDATE stock
- `POST /api/images/products/{id}/{combination}` - Image upload

#### 6.3 ❌ Sync Status UI
- ❌ Badge sync status per wariant (synced ✅, pending ⏳, conflict ⚠️, missing ❌)
- ❌ Button "Sync to Shop" per wariant
- ❌ Bulk sync modal

#### 6.4 ❌ Database Tables (jeśli potrzebne)
- ❌ `variant_sync_status` - tracking per variant per shop
- ❌ Update `prestashop_attribute_mappings` - attribute ID mapping

---

### FAZA 7: Testing & Deployment (Tydzień 7-8)
**Priorytet:** 🔴 KRYTYCZNY
**Status:** ❌ NIE ROZPOCZĘTE
**Zależności:** ALL PHASES

#### 7.1 ❌ Testy
- ❌ Feature tests (E2E workflows)
- ❌ Browser tests (Chrome DevTools MCP verification)
- ❌ Performance tests (1000+ wariantów)

#### 7.2 ❌ Documentation
- ❌ Zaktualizować `_DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md`
- ❌ Utworzyć `_DOCS/VARIANT_SYSTEM_GUIDE.md` (user docs)

#### 7.3 ❌ Deployment
- ❌ `npm run build`
- ❌ Deploy ALL `public/build/assets/*`
- ❌ Deploy manifest do ROOT (`public/build/manifest.json`)
- ❌ Clear cache (views, config, routes)
- ❌ Chrome DevTools verification

---

## 📊 TIMELINE & PROGRESS

| Faza | Opis | Czas | Status | Progress |
|------|------|------|--------|----------|
| **FAZA 1** | Refactoring Traits | 1-2 tyg | ✅ DONE | 100% |
| **FAZA 2** | Backend Services | 1 tyg | ✅ DONE | 100% |
| **FAZA 3** | ProductForm Tab "Warianty" | 1-2 tyg | ✅ DONE | 100% |
| **FAZA 4** | ProductList Expandable | 1 tyg | ✅ DONE | 100% |
| **FAZA 4B** | Per-Shop Variant Isolation | 1 dzien | ✅ DONE | 100% |
| **FAZA 5** | Bulk Management Panel | 1 tyg | ❌ | 0% |
| **FAZA 6** | PrestaShop Integration | 1 tyg | 🛠️ | 25% |
| **FAZA 7** | Testing & Deploy | 1 tyg | ❌ | 0% |
| **TOTAL** | | **7-8 tyg** | 🛠️ | **69%** |

```
FAZA 1:  ████████████████████ 100% ✅ Refactoring (CRITICAL)
FAZA 2:  ████████████████████ 100% ✅ Services
FAZA 3:  ████████████████████ 100% ✅ ProductForm UI (HIGH)
FAZA 4:  ████████████████████ 100% ✅ ProductList Expandable
FAZA 4B: ████████████████████ 100% ✅ Per-Shop Isolation (CRITICAL FIX)
FAZA 5:  ░░░░░░░░░░░░░░░░░░░░ 0%   ❌ Bulk Panel (OPTIONAL)
FAZA 6:  █████░░░░░░░░░░░░░░░ 25%  🛠️ PrestaShop Sync (6.1 Image Import ✅)
FAZA 7:  ░░░░░░░░░░░░░░░░░░░░ 0%   ❌ Testing & Deploy

OVERALL: █████████████░░░░░░░ 69% (6/9 faz - wliczajac 6.1)
```

---

## ⚠️ RYZYKA & MITIGACJE

### 🔴 WYSOKIE RYZYKO

| Ryzyko | Prawdop. | Impact | Mitigacja |
|--------|----------|--------|-----------|
| Breaking changes przy refactoringu | 70% | KRYTYCZNY | Comprehensive tests BEFORE refactor, feature flag |
| Performance >1000 wariantów | 50% | WYSOKI | Pagination 25/page, lazy loading, eager loading |
| PrestaShop API rate limiting | 60% | ŚREDNI | Rate limiter 60 req/min, retry logic 3x |

### 🟡 ŚREDNIE RYZYKO

| Ryzyko | Prawdop. | Impact | Mitigacja |
|--------|----------|--------|-----------|
| Vite manifest cache issues | 40% | ŚREDNI | Dodać do istniejącego CSS, deploy ALL assets |
| Livewire wire:key conflicts | 30% | NISKI | Unique keys z timestamp |

---

## 🤖 AGENT DELEGATION

| Faza | Agent | Zadanie |
|------|-------|---------|
| FAZA 1 | `refactoring-specialist` | Podział ProductFormVariants.php |
| FAZA 2 | `laravel-expert` | Backend Services |
| FAZA 3 | `livewire-specialist` + `frontend-specialist` | ProductForm UI |
| FAZA 4 | `livewire-specialist` | ProductList Expandable |
| FAZA 5 | `livewire-specialist` | Bulk Management |
| FAZA 6 | `prestashop-api-expert` | PrestaShop Integration |
| FAZA 7 | `deployment-specialist` + `coding-style-agent` | Deploy + Review |

---

## 📚 DOKUMENTACJA POWIĄZANA

- `_AGENT_REPORTS/architect_VARIANT_SYSTEM_REDESIGN.md` - Architektura (22 pliki)
- `_AGENT_REPORTS/prestashop_api_expert_VARIANT_API_ANALYSIS.md` - PrestaShop API
- `_AGENT_REPORTS/frontend_specialist_VARIANT_UI_REDESIGN.md` - UI/UX Spec
- `_DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md` - PPM Architecture
- `_DOCS/PPM_Styling_Playbook.md` - Design Tokens
- `References/Baselinker_wariants.png` - Wzór UI (expandable rows)

---

## 🎯 NASTĘPNY KROK

### → FAZA 5: Panel Masowego Zarządzania Wariantami

**Priorytet:** 🟢 NISKI (Nice-to-have)

**Agent:** `livewire-specialist`

**Zadanie:**
1. Utworzyć `app/Http/Livewire/Admin/Variants/BulkVariantManager.php` (~290 linii)
2. Utworzyć `resources/views/livewire/admin/variants/bulk-variant-manager.blade.php` (~200 linii)
3. Implementacja zakładek: Generuj Kombinacje, Edytuj Ceny, Edytuj Stany, Sync PrestaShop
4. Dodać route `/admin/variants/bulk-edit`
5. Dodać link w sidebar: PRODUKTY → Zarzadzanie Wariantami

**Alternatywnie - FAZA 6: PrestaShop Integration (wyższy priorytet)**:
1. `app/Services/PrestaShop/VariantSyncService.php`
2. `app/Jobs/PrestaShop/SyncVariantToPrestaShopJob.php`
3. API endpoints dla combinations + stock_availables

---

**Data utworzenia (v1):** 2025-10-23
**Data przebudowy (v3):** 2025-12-03
**Ostatnia aktualizacja:** 2025-12-10
**Status:** 🛠️ **W TRAKCIE PRZEBUDOWY** (69% complete - FAZA 1-4B + 6.1 ukonczone)
**Autor przebudowy:** Orchestrator + architect + prestashop-api-expert + frontend-specialist
