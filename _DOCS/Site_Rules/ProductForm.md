# ProductForm – reguły strony

---

## REFACTORING NOTICE (2025-11-21)

**STATUS:** ✅ **REFACTORING COMPLETED** - Struktura monolityczna (2200 linii) → Modularny system TABS + PARTIALS

**ARCHITEKTURA:**
- **Main orchestrator:** `product-form.blade.php` (~100 linii)
- **6 TABS** (conditional rendering): `tabs/basic-tab.blade.php`, `description-tab.blade.php`, `physical-tab.blade.php`, `attributes-tab.blade.php`, `prices-tab.blade.php`, `stock-tab.blade.php`
- **9 PARTIALS** (always included): `partials/form-header.blade.php`, `form-messages.blade.php`, `tab-navigation.blade.php`, `shop-management.blade.php`, `quick-actions.blade.php`, `product-info.blade.php`, `category-tree-item.blade.php`, `category-browser.blade.php`, `product-shop-tab.blade.php`

**KATALOGI:**
```
resources/views/livewire/products/management/
├── product-form.blade.php          # Main orchestrator
├── tabs/                           # Conditional (only 1 in DOM)
└── partials/                       # Always included (reusable)
```

**FUNKCJONALNOŚĆ:** Reguły opisane poniżej NADAL obowiązują - refactoring zmienił TYLKO strukturę plików (separation of concerns), NIE logikę biznesową.

**GDZIE SĄ FUNKCJE:**
- **Kontekst/Pending changes:** `ProductForm.php` (backend Livewire component) - bez zmian
- **Statusy pól:** `ProductForm.php::getFieldClasses()`, `getFieldStatusIndicator()` - używane we wszystkich tabs
- **Kategorie:** `tabs/basic-tab.blade.php` (Categories Section lines 813-856) + `partials/category-tree-item.blade.php` (recursive tree)
- **Shop sync:** `partials/shop-management.blade.php` (dropdown wyboru sklepu + badge status)
- **Job monitoring:** `product-form.blade.php` główny kontener (wire:poll) + `partials/quick-actions.blade.php` (przyciski sync)
- **Sidebar:** `partials/quick-actions.blade.php` + `partials/product-info.blade.php`

**SZCZEGÓŁY REFACTORINGU:**
- 📖 [`ProductForm_REFACTORING_2025-11-22.md`](ProductForm_REFACTORING_2025-11-22.md) - Pełna dokumentacja: architektura, critical bug case study, 5 lessons learned, mandatory rules
- 📖 [`_DOCS/Struktura_Plikow_Projektu.md`](../Struktura_Plikow_Projektu.md) - Struktury katalogów + tabele odpowiedzialności TABS/PARTIALS

---

## Kontekst formularza i pending changes
- `switchToShop` zapisuje bieżący stan do `pendingChanges`, czyści cache walidacji kategorii i ładuje dane tylko dla aktywnego kontekstu (app/Http/Livewire/Products/Management/ProductForm.php:2187); w trybie sklepu przed danymi ładowane są tax rule groups.
- `savePendingChanges` trzyma pełny snapshot pól per kontekst (w tym `contextCategories`, `tax_rate_override`), aby uniknąć mieszania danych między zakładkami (ProductForm.php:2579).
- `markFormAsChanged` podnosi `hasUnsavedChanges` i automatycznie zapisuje `pendingChanges` na każde `updated`, z pominięciem pól technicznych (ProductForm.php:2732,3339+); badge „Niezapisane zmiany” w headerze i `beforeunload` w widoku opierają się na tym stanie (resources/views/livewire/products/management/product-form.blade.php:10-170).
- Reset kontekstu (`resetToDefaults`) usuwa pending tylko dla aktywnego kontekstu i ładuje dane z DB/`defaultData` (ProductForm.php:2749).

## Statusy pól i blokady pending sync
- Status pola (`default`/`inherited`/`same`/`different`) liczony względem `defaultData` lub wartości dziedziczonych; puste wartości traktowane jako dziedziczone, `tax_rate` ma osobną ścieżkę z `shopTaxRateOverrides` (ProductForm.php:2798).
- Klasy pól (`getFieldClasses`) doklejają priorytetowo `field-pending-sync` przy `sync_status='pending'` oraz `field-status-*` dla kolorów statusu (ProductForm.php:3207); widok używa ich na wszystkich inputach (product-form.blade.php:538-740).
- Badge statusu (`getFieldStatusIndicator`) zwraca `pending-sync-badge` przy pending sync, inaczej `status-label-*` (ProductForm.php:3239). CSS: `field-status-*` i `status-label-*` w resources/css/admin/components.css:4893-5007; `field-pending-sync`/`pending-sync-badge` w resources/css/products/product-form.css:5-36 (dublet również w components.css sekcja „Pending Sync Visual States”).
- `isPendingSyncForShop` sprawdza tylko `ProductShopData::STATUS_PENDING` z DB; gdy pending, wszystkie pola kontekstu dostają blokadę `field-pending-sync` niezależnie od listy `pending_fields` (ProductForm.php:3298).

## Kategorie – dziedziczenie, mapowania, blokady
- Status kategorii (`getCategoryStatus`/`getCategoryStatusIndicator`) porównuje kontekst z danymi domyślnymi; statusy `inherited`/`same`/`different` mapują się na klasy `category-status-*`, a przy wykryciu oczekujących zmian zwracany jest badge `status-label-pending` (ProductForm.php:2954,3076-3088). `status-label-pending` nie ma definicji w CSS – do uzupełnienia.
- Blokada edycji kategorii (`isCategoryEditingDisabled`) działa przy `isSaving` lub `sync_status='pending'` dla aktywnego sklepu; UI ustawia `@disabled` na checkboxach i „Ustaw główną” (category-tree-item.blade.php:45,65). `getCategoryClasses` zwraca wtedy `category-status-pending` + żółte tony (ProductForm.php:3147-3182), ale klasa `category-status-pending` nie jest zdefiniowana w CSS – brakujący styl.
- Kategorie są kontekstowe i przechowują mapowania PrestaShop→PPM (Option A). `getPrestaShopCategoryIdsForContext`/`getPrimaryPrestaShopCategoryIdForContext` wykonują lazy-load mapowań z `product_shop_data.category_mappings` (ProductForm.php:1571-1676). `convertPrestaShopIdToPpmId` używane przy wyborze w drzewie (ProductForm.php:1687).
- `loadShopDataToForm` omija reload kategorii, gdy `sync_status='pending'`, aby nie nadpisać świeżo zapisanych zmian przed zakończeniem joba (ProductForm.php:2299-2342).
- `loadProductDataFromPrestaShop` przy pending sync ładuje tylko kategorie (`$loadCategoriesOnly=true`), mapuje je na PPM, zapisuje Option A do DB i odświeża UI/Alpine (ProductForm.php:6251-6440).

## CRITICAL FIX: Category Loading & Expansion (2025-11-24)

**PROBLEM:** Po poprzednim fix'ie `getShopCategories()` ZAWSZE zwracało PPM categories zamiast ładować WSZYSTKIE kategorie z PrestaShop API → użytkownik nie mógł wybrać nowych kategorii (tylko te już przypisane do produktu).

**SYMPTOMY:**
- ❌ B2B Test DEV tab pokazywał tylko 8 kategorii (przypisane do produktu)
- ❌ User nie mógł wybrać pozostałych 1168 kategorii z PrestaShop
- ❌ MRF, TEST PPM Category, RXF wyświetlały się na tym samym poziomie co "Baza" (ROOT) zamiast pod "Wszystko"

**ROOT CAUSE:**
1. **Błędna architektura (FIX 2025-11-24 - ROLLBACK):** `getShopCategories()` było zmienione na `return $this->getDefaultCategories();` dla wszystkich kontekstów → zawsze zwracało PPM categories, nie PrestaShop tree
2. **Database structure:** Kategorie MRF (id=12), TEST PPM Category (id=13), RXF (id=15) miały `parent_id=NULL` zamiast `parent_id=2` (Wszystko)

**FIX APPLIED:**

**1. ROLLBACK ProductForm.php (lines 6163-6197):**
```php
// ❌ BEFORE (broken):
public function getShopCategories(): array
{
    return $this->getDefaultCategories(); // Always PPM categories!
}

// ✅ AFTER (fixed):
public function getShopCategories(): array
{
    if (!$this->activeShopId) {
        return $this->getDefaultCategories(); // Default TAB = PPM categories
    }

    try {
        $shop = PrestaShopShop::find($this->activeShopId);
        $categoryService = app(\App\Services\PrestaShop\PrestaShopCategoryService::class);
        $tree = $categoryService->getCachedCategoryTree($shop); // Load ALL PrestaShop categories from API!
        return array_map([$this, 'convertCategoryArrayToObject'], $tree);
    } catch (\Exception $e) {
        Log::error('Failed to get shop categories', ['shop_id' => $this->activeShopId]);
        return $this->getDefaultCategories();
    }
}
```

**2. RESTORED ID Conversion (lines 1663-1792):**
- `getPrestaShopCategoryIdsForContext()`: Przywrócono konwersję PPM IDs → PrestaShop IDs dla shop context
- `getPrimaryPrestaShopCategoryIdForContext()`: Przywrócono konwersję PPM primary ID → PrestaShop ID
- `calculateExpandedCategoryIds()` (lines 1304-1408): Dodano konwersję PPM IDs → PrestaShop IDs dla shop context (aby expansion działało z PrestaShop tree)

**3. DATABASE FIX:**
```sql
-- Script: _TEMP/fix_root_level_categories.php (executed 2025-11-24)
UPDATE categories SET parent_id = 2 WHERE id IN (12, 13, 15);
-- MRF (id=12): parent_id=NULL → parent_id=2 ✅
-- TEST PPM Category (id=13): parent_id=NULL → parent_id=2 ✅
-- RXF (id=15): parent_id=NULL → parent_id=2 ✅
```

**VERIFICATION (Chrome DevTools MCP - 2025-11-24):**
- ✅ **Default TAB:** Hierarchia PPM poprawna (Baza → Wszystko → PITGANG/KAYO/Pojazdy/MRF/TEST PPM/RXF)
- ✅ **B2B Test DEV TAB:** Total checkboxes = 1179 (WSZYSTKIE kategorie PrestaShop z API)
- ✅ **Expansion works:** 5 kategorii rozwinięte (Baza, Wszystko, PITGANG, Pojazdy, KAYO) - parent categories zaznaczonych kategorii
- ✅ **User może wybrać DOWOLNĄ kategorię** z 1170 dostępnych (niezaznaczonych)

**LESSONS LEARNED:**
1. **NIGDY nie zmieniaj `getShopCategories()` na PPM-only** - shop tabs MUSZĄ pokazywać WSZYSTKIE kategorie PrestaShop z API
2. **PPM categories = DEFAULT TAB** (fallback, własna hierarchia organizacji)
3. **PrestaShop categories = SHOP TABS** (complete tree z API, umożliwia wybór nowych kategorii)
4. **Expansion wymaga konwersji IDs** - calculateExpandedCategoryIds() musi konwertować PPM → PrestaShop dla shop context
5. **Database integrity** - zawsze sprawdzaj parent_id struktur hierarchicznych po zmianach

**FILES MODIFIED:**
- `app/Http/Livewire/Products/Management/ProductForm.php` (lines 6163-6197, 1663-1792, 1304-1408)
- Database: `categories` table (`parent_id` fix for MRF, TEST PPM Category, RXF)

**SCRIPTS:**
- `_TEMP/fix_root_level_categories.php` - Database fix
- `_TEMP/deploy_productform_rollback_fix.ps1` - Deployment

**SCREENSHOTS:**
- `_TOOLS/screenshots/b2b_test_dev_categories_fixed.jpg` - Weryfikacja 1179 kategorii

## CRITICAL FIX: Category UI Reactivity (2025-11-24) - FINAL v2

**PROBLEM EVOLUTION:**

### Iteracja 1 (Failed):
1. ❌ **LAG przy przełączaniu przycisku "Główna"** - Po kliknięciu "Ustaw główną" na nowej kategorii, poprzednia kategoria nie zmieniała natychmiast przycisku
2. ❌ **CSS class (border color) nie reagował na zmianę primary** - Badge zmieniał się poprawnie ale ramka pozostawała w tym samym kolorze

### Iteracja 2 (Failed - Race Condition):
Po zastosowaniu FIX #1 (Alpine.js manual toggle usunięty) i FIX #2 (wire:key z `$selectedCategoriesCount`):
- ✅ Przycisk "Główna" działał poprawnie
- ✅ CSS class reagował na primary changes
- ❌ **KRYTYCZNY BŁĄD:** Checkboxy automatycznie odznaczały się po każdej zmianie!

**ROOT CAUSE (Iteracja 2 - Race Condition):**
- `wire:key` z `$selectedCategoriesCount` powodował force re-render przy KAŻDYM checkbox toggle
- Force re-render niszczył Alpine.js state (`isSelected`) → checkbox resetował się
- User klikał checkbox → zmiana `$selectedCategoriesCount` → wire:key change → DOM re-render → Alpine.js state lost → checkbox unchecked

### Iteracja 3 (FINAL - SUCCESS):
**DISCOVERY:** Badge działa poprawnie bo używa `getPrimaryCategoryStatus()`, ale CSS class używał `getCategoryStatus()` (porównuje SELECTED, nie PRIMARY!)

**FINAL SOLUTION:**

**FIX #1 - Usunięcie Alpine.js Manual Toggle (category-tree-item.blade.php lines 13-76):**

```blade
{{-- PRZED: --}}
<div x-data="{ isPrimary: {{ $isPrimary ? 'true' : 'false' }} }">
<button @click="isPrimary = !isPrimary; $wire.setPrimaryCategory(...)">

{{-- PO: --}}
<div x-data="{ /* isPrimary removed */ }">
<button @click="$wire.setPrimaryCategory(...)" class="{{ $isPrimary ? ... : ... }}">
```

**Rezultat:** Livewire zarządza stanem → WSZYSTKIE przyciski aktualizują się jednocześnie ✅

**FIX #2 (v2 FINAL) - getPrimaryCategoryStatus() + wire:key ONLY primary (basic-tab.blade.php + ProductForm.php):**

```blade
{{-- basic-tab.blade.php (lines 818-826): --}}
@php
    $categoryContainerClasses = $this->getCategoryClasses();
    $primaryCatId = $this->getPrimaryCategoryForContext($activeShopId) ?? 'none';
    // REMOVED: $selectedCategoriesCount (was causing Alpine.js state reset!)
@endphp
<div class="{{ $categoryContainerClasses }}" wire:key="categories-ctx-{{ $activeShopId }}-pri-{{ $primaryCatId }}">
```

```php
// ProductForm.php line 3396-3424 (getCategoryClasses):
public function getCategoryClasses(): string
{
    // FIX 2025-11-24: Use getPrimaryCategoryStatus() instead of getCategoryStatus()
    // This makes CSS class react to PRIMARY changes (like badge does)
    // NOT to checkbox toggles (which was causing Alpine.js state reset!)
    $status = $this->getPrimaryCategoryStatus(); // ← CHANGED from getCategoryStatus()

    // ... switch statement unchanged ...
}

// ProductForm.php lines 3268-3292 (NEW METHOD):
public function getPrimaryCategoryStatus(): string
{
    if ($this->activeShopId === null) return 'default';

    $currentPrimary = $this->getPrimaryCategoryForContext($this->activeShopId);
    $defaultPrimary = $this->getPrimaryCategoryForContext(null);

    if ($currentPrimary === $defaultPrimary) return 'same';
    if ($currentPrimary === null) return 'inherited';
    return 'different';
}
```

**Jak działa (FINAL):**
1. `wire:key` zmienia się TYLKO gdy primary się zmienia (NIE przy checkbox toggle!)
2. Checkbox toggle → Alpine.js `isSelected` toggle → checkbox state preserved (no DOM re-render!)
3. Primary button click → `wire:key` change → `@php` re-evaluation → `getCategoryClasses()` calls `getPrimaryCategoryStatus()` → CSS updated ✅
4. Badge i CSS class używają tej samej metody → synchronizacja perfekcyjna!

**VERIFICATION (Production - 2025-11-24 - Chrome DevTools MCP):**

**TEST 1: Checkbox Persistence** ✅ **PASSED**
- Clicked checkbox "TEST PPM Category" (category_1_2352)
- Result: `checkboxStillChecked: true` (after 2 seconds)
- Frame class: `category-status-same` (unchanged - correct!)
- Clicked checkbox "MRF" (category_1_11)
- Result: `stable: true` (remained checked for 2+ seconds)
- **Conclusion:** Alpine.js state is NOT reset by wire:key changes!

**TEST 2: CSS Class Reactivity** ✅ **PASSED**
- Clicked "Ustaw główną" on PITGANG category
- Before: `category-status-same` (green border)
- After: `category-status-different` (orange border)
- Change: `changed: true` ✅
- Button state: Changed to "Główna" ✅
- Checkboxes: All remained in their state (no reset) ✅

**Screenshot:** `_TOOLS/screenshots/category_reactivity_fix_v2_FINAL_2025-11-24.jpg`

**LESSONS LEARNED:**
1. **wire:key race conditions** - Adding dependencies to wire:key can destroy Alpine.js state
2. **Separate concerns** - Badge uses primary status, CSS should too (not selected status)
3. **getPrimaryCategoryStatus() pattern** - Dedicated method for primary-only comparisons
4. **Testing critical** - Manual verification missed race condition, Chrome DevTools MCP caught it
5. **Iterative fixes** - First solution can introduce new bugs, need full testing cycle

**FILES MODIFIED:**
- `resources/views/livewire/products/management/partials/category-tree-item.blade.php` (lines 13-76) - Alpine.js fix
- `resources/views/livewire/products/management/tabs/basic-tab.blade.php` (lines 818-826) - wire:key rollback
- `app/Http/Livewire/Products/Management/ProductForm.php` (lines 3268-3292, 3396-3424) - getPrimaryCategoryStatus() + getCategoryClasses() fix

**SCRIPTS:**
- `_TEMP/deploy_category_reactivity_fix_v2.ps1` - Final deployment

## CRITICAL FIX: Root Categories Auto-Repair (2025-11-25)

**PROBLEM:** Po imporcie produktu z PrestaShop, root categories (Baza=1, Wszystko=2) były tracone:
- Import budował `category_mappings` tylko z kategorii PrestaShop (np. `[25, 26]`)
- PULL z PrestaShop nadpisywał `category_mappings` danymi bez root categories
- UI pokazywało tylko 2 kategorie zamiast 4 (brak Baza, Wszystko)

**SYMPTOMY:**
- ❌ ProductForm pokazywał `selected_categories: [25, 26]` (tylko 2 kategorie)
- ❌ Checkboxy "Baza" i "Wszystko" były odznaczone mimo że powinny być zawsze zaznaczone
- ❌ Dane w DB: `ui.selected: [25, 26]` bez root categories

**ROOT CAUSE:**
1. `PrestaShopImportService::importProductFromPrestaShop()` NIE budowało `category_mappings` z root categories
2. `pullShopDataInstant()` nadpisywało dane z PrestaShop (które nie mają PPM root categories)
3. Brak mechanizmu auto-repair przy ładowaniu danych

**FIX APPLIED (3 warstwy ochrony):**

### 1. Import Flow - `buildCategoryMappingsFromProductCategories()` (PrestaShopImportService.php)

```php
// Line 263-265 - Called after syncProductCategories()
// 11. FIX 2025-11-25: Build category_mappings from product_categories
$this->buildCategoryMappingsFromProductCategories($product, $shop);

// New method (lines 1179-1273):
protected function buildCategoryMappingsFromProductCategories(Product $product, PrestaShopShop $shop): void
{
    // Get categories from product_categories table
    $categories = DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->get();

    // Build ui.selected with root categories
    $selectedIds = $categories->pluck('category_id')->toArray();
    $rootCategoryIds = [1, 2]; // Baza, Wszystko - PPM-only
    foreach ($rootCategoryIds as $rootId) {
        if (!in_array($rootId, $selectedIds)) {
            $selectedIds[] = $rootId;
        }
    }

    // Build mappings and save to ProductShopData
    $categoryMappings = [
        'ui' => ['selected' => $selectedIds, 'primary' => $primaryId],
        'mappings' => $mappings,
        'metadata' => ['last_updated' => now()->toIso8601String(), 'source' => 'import_build'],
    ];
    $productShopData->category_mappings = $categoryMappings;
    $productShopData->save();
}
```

### 2. Pull Flow - `ensureRootCategoriesInCategoryMappings()` (ProductForm.php)

```php
// Called in pullShopDataInstant() after update (line 2488-2490):
// FIX 2025-11-25: Ensure root categories are ALWAYS in category_mappings after pull
$this->ensureRootCategoriesInCategoryMappings($shopData);

// Method (lines 2651-2692):
private function ensureRootCategoriesInCategoryMappings(\App\Models\ProductShopData $shopData): void
{
    $rootCategoryIds = [1, 2]; // Baza, Wszystko
    $categoryMappings = $shopData->category_mappings;
    $selected = $categoryMappings['ui']['selected'];
    $updated = false;

    foreach ($rootCategoryIds as $rootId) {
        if (!in_array($rootId, $selected)) {
            $selected[] = $rootId;
            $categoryMappings['mappings'][(string)$rootId] = $rootId;
            $updated = true;
        }
    }

    if ($updated) {
        $categoryMappings['ui']['selected'] = $selected;
        $categoryMappings['metadata']['source'] = 'pull';
        $shopData->category_mappings = $categoryMappings;
        $shopData->save();
        Log::info('[ROOT CATEGORIES] Added root categories after pull');
    }
}
```

### 3. Load Flow - Auto-Repair in `loadShopCategories()` (ProductForm.php)

```php
// Lines 2718-2751 - Inside loadShopCategories():
// FIX 2025-11-25: Auto-repair missing root categories (Baza=1, Wszystko=2)
$selectedCategories = $categoryMappings['ui']['selected'] ?? [];
$rootCategoryIds = [1, 2];
$needsRepair = false;

foreach ($rootCategoryIds as $rootId) {
    if (!in_array($rootId, $selectedCategories)) {
        $needsRepair = true;
        break;
    }
}

if ($needsRepair) {
    Log::info('[loadShopCategories] ROOT CATEGORIES MISSING - auto-repairing', [
        'product_id' => $this->product->id,
        'shop_id' => $shopId,
        'before_selected' => $selectedCategories,
    ]);

    // Use ensureRootCategoriesInCategoryMappings to repair DB
    $this->ensureRootCategoriesInCategoryMappings($productShopData);

    // Refresh category_mappings after repair
    $productShopData->refresh();
    $categoryMappings = $productShopData->category_mappings;

    Log::info('[loadShopCategories] ROOT CATEGORIES REPAIRED', [
        'after_selected' => $categoryMappings['ui']['selected'] ?? [],
    ]);
}
```

### 4. Validator Update (CategoryMappingsValidator.php)

```php
// Line 41 - Added new allowed sources:
'metadata.source' => 'nullable|in:manual,pull,sync,migration,import,import_build,import_root_sync',
```

**VERIFICATION (Production - 2025-11-25 - Chrome DevTools MCP):**

**BEFORE:**
```json
{"ui": {"selected": [25, 26]}, "mappings": {"25": 4, "26": 119}}
```

**AFTER (auto-repaired on shop tab click):**
```json
{"ui": {"selected": [25, 26, 1, 2]}, "mappings": {"25": 4, "26": 119, "1": 1, "2": 2}}
```

**Logs confirm auto-repair:**
```
[loadShopCategories] ROOT CATEGORIES MISSING - auto-repairing {"before_selected":[25,26]}
[ROOT CATEGORIES] Added root categories after pull {"selected_count":4}
[loadShopCategories] ROOT CATEGORIES REPAIRED {"after_selected":[25,26,1,2]}
```

**UI verification:**
- ✅ Checkbox "Baza" - checked
- ✅ Checkbox "Wszystko" - checked
- ✅ Checkbox "Dirt Bike" - checked
- ✅ Checkbox "125cc+" - checked
- ✅ Text: "Wybrano 4 kategori." (was 2)

**Screenshot:** `_TOOLS/screenshots/ROOT_CATEGORIES_AUTO_REPAIR_SUCCESS_2025-11-25.jpg`

**LESSONS LEARNED:**
1. **Root categories are PPM-only** - PrestaShop doesn't have Baza/Wszystko, so they're never in API response
2. **3-layer protection needed** - Import, Pull, and Load all need to ensure root categories
3. **Auto-repair on load** - Best UX: fix transparently when user opens product, not require manual action
4. **Validator must allow new sources** - `import_build`, `import_root_sync` for tracking origin

**FILES MODIFIED:**
- `app/Services/PrestaShop/PrestaShopImportService.php` (lines 263-265, 1179-1273)
- `app/Http/Livewire/Products/Management/ProductForm.php` (lines 2488-2490, 2651-2692, 2718-2751)
- `app/Services/CategoryMappingsValidator.php` (line 41)

**SCRIPTS:**
- `_TEMP/deploy_import_service.ps1` - Deployment script

---

## Integracja z PrestaShop i monitoring jobów
- Główny kontener ma `wire:poll.5s="checkJobStatus"` z auto-stopem przy braku joba lub statusach completed/failed (product-form.blade.php:10-11). `jobCountdown` JS animuje `btn-job-*` (resources/views/livewire/products/management/product-form.blade.php:1800+; style w resources/css/admin/components.css sekcja „ETAP_13: JOB COUNTDOWN ANIMATIONS”).
- `checkJobStatus` obsługuje single/bulk sync/pull, ustawia `activeJobStatus`/`jobResult` i po sukcesie triggeruje `pullShopData` dla aktywnego sklepu w celu auto-refreshu kategorii (ProductForm.php:3942-4096).
- `pullShopData` (przycisk „Wczytaj z aktualnego sklepu”) najpierw zapisuje pending dla bieżącego kontekstu, blokuje pobieranie gdy `sync_status='pending'`, a po pobraniu zapisuje `category_mappings` Option A i przeładowuje UI + `reloadCleanShopCategories` (ProductForm.php:4317-4707).
- `loadProductDataFromPrestaShop` korzysta z `PrestaShopClientFactory` i `CategoryMappingsValidator/Converter`; przy pending sync nie pobiera pól tekstowych/cen, żeby nie nadpisać zmian (ProductForm.php:6251-6380).

## Style i komponenty powiązane
- `category-status-*`, `pending-sync-badge`, `field-pending-sync`, `shop-tab-*`, `status-badge` itp. są zdefiniowane w resources/css/products/product-form.css (sekcje Pending Sync, Category Status Indicators, Shop Tabs) oraz resources/css/admin/components.css (sekcje Product Form Field Status Styles, Status Label Badges).
- Kategorie renderuje makro `category-tree-item.blade.php` (checkbox + „Ustaw główną”) i bazuje na `isCategoryEditingDisabled` oraz `getPrestaShopCategoryIdsForContext`; drzewo korzysta z `category-manager` i Resource tree przekazywanego przez `getShopCategories()` w widoku (product-form.blade.php:1068-1125).
- Shop tab sidepanel (`product-shop-tab.blade.php`) pokazuje badge pending (`shopData->sync_status === 'pending'`) i listy `pending_fields` (resources/views/livewire/products/management/partials/product-shop-tab.blade.php:1-180); style w product-form.css sekcja Shop Tabs.

## Luki do poprawy (wykryte podczas audytu kodu)
- Brak styli dla `status-label-pending` użytej w `getCategoryStatusIndicator` (ProductForm.php:3088) – badge pending kategorii renderuje się bez kolorystyki; dodać w existing CSS (np. resources/css/admin/components.css obok `status-label-*`).
- Brak styli dla `category-status-pending` zwracanej w `getCategoryClasses` przy blokadzie edycji (ProductForm.php:3179); UI używa klasy, ale nie istnieje w żadnym CSS (resources/css/products/product-form.css / admin/components.css) – należy uzupełnić, aby wizualnie odróżniać stan „pending”.
