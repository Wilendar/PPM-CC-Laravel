# RAPORT PRACY AGENTA: livewire-specialist
**Data**: 2025-10-15
**Agent**: livewire-specialist
**Zadanie**: Implementacja Category Merge w CategoryTree component (sekcja 2.2.2.2.4)

## WYKONANE PRACE

### 1. Dodane Properties (COMPONENT STATE PROPERTIES, lines 194-220)

**Lokalizacja:** `app/Http/Livewire/Products/Categories/CategoryTree.php`

```php
/**
 * Show category merge modal
 *
 * @var bool
 */
public $showMergeCategoriesModal = false;

/**
 * Source category ID for merge (kategoria do usunięcia)
 *
 * @var int|null
 */
public $sourceCategoryId = null;

/**
 * Target category ID for merge (kategoria docelowa)
 *
 * @var int|null
 */
public $targetCategoryId = null;

/**
 * Merge warnings (produkty, podkategorie)
 *
 * @var array
 */
public $mergeWarnings = [];
```

**Szczegóły:**
- `showMergeCategoriesModal` - kontroluje widoczność modala merge
- `sourceCategoryId` - kategoria źródłowa (będzie usunięta po merge)
- `targetCategoryId` - kategoria docelowa (otrzyma wszystkie produkty/children ze source)
- `mergeWarnings` - array warnings o produktach i podkategoriach do przeniesienia

### 2. Dodane Metody (CATEGORY MERGE OPERATIONS, lines 1235-1500)

#### a) `openCategoryMergeModal(int $sourceCategoryId): void` (lines 1248-1300)

**Funkcjonalność:**
- Ładuje source category z `with(['products', 'children', 'descendants'])` i `withCount(['products', 'children'])`
- Waliduje czy kategoria istnieje
- Resetuje `targetCategoryId` (użytkownik wybierze z dropdown)
- Zbiera warnings:
  - Liczba produktów do przeniesienia
  - Liczba bezpośrednich children i total descendants (jeśli różne)
  - Info o pustej kategorii (brak products/children)
- Otwiera modal (`showMergeCategoriesModal = true`)
- Loguje otwarcie modala z kontekstem

**Wzorzec:** `showForceDeleteConfirmation()` (lines 679-708) - podobny pattern zbierania warnings

#### b) `closeCategoryMergeModal(): void` (lines 1307-1313)

**Funkcjonalność:**
- Zamyka modal
- Resetuje state: `sourceCategoryId`, `targetCategoryId`, `mergeWarnings`

**Wzorzec:** `cancelForceDelete()` (lines 777-782) - czyszczenie modal state

#### c) `mergeCategories(): void` (lines 1336-1500)

**GŁÓWNA LOGIKA MERGE - SZCZEGÓŁOWY OPIS:**

**VALIDATION (lines 1339-1378):**

1. **Both categories selected** (lines 1340-1343):
   ```php
   if (!$this->sourceCategoryId || !$this->targetCategoryId) {
       session()->flash('error', 'Wybierz kategorię źródłową i docelową.');
       return;
   }
   ```

2. **Source != Target** (lines 1345-1349):
   ```php
   if ($this->sourceCategoryId === $this->targetCategoryId) {
       session()->flash('error', 'Kategoria źródłowa i docelowa muszą być różne.');
       return;
   }
   ```

3. **Categories exist** (lines 1352-1360):
   - Load source with `['products', 'children', 'descendants']`
   - Load target (base)
   - Check obie kategorie istnieją

4. **Circular reference check** (lines 1362-1366):
   ```php
   if ($sourceCategory->isAncestorOf($this->targetCategoryId)) {
       session()->flash('error', 'Nie można połączyć kategorii z własnym potomkiem (zapętlenie).');
       return;
   }
   ```
   - Używa `Category::isAncestorOf()` method
   - Zapobiega merge parent → child (circular reference)

5. **Max level check** (lines 1368-1378):
   ```php
   if ($sourceCategory->children()->count() > 0) {
       $maxDescendantLevel = $sourceCategory->getMaxDescendantLevel();
       $wouldBeLevel = $targetCategory->level + 1; // Children will be at target's level + 1
       $finalLevel = $wouldBeLevel + $maxDescendantLevel;

       if ($finalLevel > Category::MAX_LEVEL) {
           session()->flash('error', "Nie można połączyć...");
           return;
       }
   }
   ```
   - Używa `Category::getMaxDescendantLevel()` method
   - Sprawdza czy po merge children nie przekroczą `Category::MAX_LEVEL`
   - Children będą na poziomie `target->level + 1`
   - Descendants będą na poziomie `target->level + 1 + maxDescendantLevel`

**MERGE LOGIC (DB::transaction, lines 1380-1458):**

**1. Move Products (continue-on-error, lines 1385-1428):**
```php
$products = $sourceCategory->products;

foreach ($products as $product) {
    try {
        // Check if product already has target category (global, not per-shop)
        $hasTargetCategory = $product->categories()
                                    ->wherePivotNull('shop_id')
                                    ->where('categories.id', $targetCategory->id)
                                    ->exists();

        if (!$hasTargetCategory) {
            // Attach target category (global)
            $product->categories()->attach($targetCategory->id, ['shop_id' => null]);
        }

        // Detach source category (global)
        $product->categories()
               ->wherePivotNull('shop_id')
               ->detach($sourceCategory->id);

        // Update primary category if source was primary
        if ($product->primary_category_id === $sourceCategory->id) {
            $product->primary_category_id = $targetCategory->id;
            $product->save();
        }

        $processed++;

    } catch (\Exception $e) {
        $errors[] = "Product ID {$product->id}: {$e->getMessage()}";
        Log::error('CategoryMerge: Error processing product', [...]);
        continue; // Continue-on-error
    }
}
```

**CRITICAL DESIGN DECISIONS:**
- **ONLY global categories** (`shop_id = null`):
  - Używa `wherePivotNull('shop_id')` dla attach/detach
  - Per-shop categories (`product_categories.shop_id NOT NULL`) są IGNOROWANE
  - Merge dotyczy TYLKO globalnej kategorii produktu
- **Duplicate detection:**
  - Sprawdza czy produkt JUŻ MA target category (global)
  - Jeśli TAK → skip attach (avoid duplicate pivot)
  - Jeśli NIE → attach target category
- **Primary category update:**
  - Jeśli source była primary → zmień na target
  - Zapobiega pozostawieniu produktu z nieistniejącą primary category
- **Continue-on-error:**
  - Jeśli błąd na produkcie → log error + continue z następnym
  - Nie przerywa całej operacji
  - Count errors dla user feedback

**2. Move Children (stop-on-error, lines 1430-1454):**
```php
$children = Category::where('parent_id', $sourceCategory->id)->get();

foreach ($children as $child) {
    try {
        $child->parent_id = $targetCategory->id;
        $child->save();

        // Refresh path/level (Category model handles this in boot)
        $child->refresh();

    } catch (\Exception $e) {
        $errors[] = "Child category ID {$child->id}: {$e->getMessage()}";
        Log::error('CategoryMerge: Error moving child category', [...]);
        throw $e; // Stop transaction for critical children errors
    }
}
```

**CRITICAL DESIGN DECISIONS:**
- **Stop-on-error (NOT continue-on-error):**
  - Jeśli błąd na child → `throw $e` (abort transaction)
  - Children move MUSI być atomowe (all or nothing)
  - Inconsistent hierarchy tree jest UNACCEPTABLE
- **Auto path/level refresh:**
  - Category model `boot()` event automatycznie aktualizuje `path`, `level`
  - `$child->refresh()` ładuje zaktualizowane wartości
- **All children przeniesione:**
  - Source category po tej operacji ma `children()->count() === 0`
  - Bezpieczne do usunięcia

**3. Delete Source (line 1457):**
```php
$sourceCategory->delete();
```
- Bezpieczne bo:
  - Wszystkie products przeniesione/detached
  - Wszystkie children przeniesione
  - Category jest pusta

**POST-TRANSACTION OPERATIONS (lines 1460-1467):**
- Remove source z `selectedCategories` (nie wyświetlać już jako selected)
- Remove source z `expandedNodes` (nie istnieje już)
- Auto-expand target category (show merged children)

**USER FEEDBACK (lines 1472-1480):**
```php
if (empty($errors)) {
    session()->flash('message', "Połączono kategorie: {$source->name} → {$target->name}. Przeniesiono {$processed} produktów.");
} else {
    // Show first 3 errors + count if more
    $errorSummary = implode('; ', array_slice($errors, 0, 3));
    $moreErrors = count($errors) > 3 ? ' (i ' . (count($errors) - 3) . ' więcej)' : '';
    session()->flash('warning', "Połączono kategorie, ale wystąpiły błędy: {$errorSummary}{$moreErrors}...");
}
```

**LOGGING (lines 1482-1489):**
```php
Log::info('CategoryTree: Categories merged successfully', [
    'source_category_id' => $sourceCategory->id,
    'source_category_name' => $sourceCategory->name,
    'target_category_id' => $targetCategory->id,
    'target_category_name' => $targetCategory->name,
    'products_processed' => $processed,
    'errors_count' => count($errors),
]);
```

**ERROR HANDLING (lines 1491-1499):**
- Top-level try-catch dla całej metody
- Jeśli transaction fail → rollback automatycznie
- Session flash error + log error

**Wzorce użyte:**
- `bulkMove()` (lines 987-1065) - validation logic, circular reference check, max level check
- `confirmForceDelete()` (lines 715-770) - modal workflow, transaction pattern
- `bulkDelete()` (lines 918-980) - continue-on-error pattern for products

## ENTERPRISE PATTERNS APPLIED

1. **DB::transaction() dla atomicity:**
   - Wszystkie operacje w jednej transakcji
   - Rollback jeśli child move fail
   - Products move: continue-on-error
   - Children move: stop-on-error (critical)

2. **Validation BEFORE execution:**
   - 5 walidacji przed rozpoczęciem transaction
   - Sprawdzenie circular reference (`isAncestorOf()`)
   - Sprawdzenie max level (`getMaxDescendantLevel()`)
   - Early return z clear error messages

3. **Continue-on-error strategy (products only):**
   - Nie przerywaj merge jeśli 1 produkt fail
   - Count errors + log każdy error
   - Report errors do użytkownika
   - Children: stop-on-error (hierarchy integrity critical)

4. **Detailed logging:**
   - `Log::info` dla successful merge (products_processed, errors_count)
   - `Log::error` dla:
     - Error opening modal
     - Error processing product (per product)
     - Error moving child category (per child)
     - Error merging categories (top-level)
   - Full context (IDs, names, counts, error messages)

5. **User feedback:**
   - Success: "Połączono kategorie: X → Y. Przeniesiono N produktów."
   - Partial success: "...ale wystąpiły błędy: ..." (max 3 errors shown)
   - Error: "Błąd podczas łączenia kategorii: ..."
   - Session flash (message/warning/error)

6. **Global categories ONLY:**
   - `wherePivotNull('shop_id')` dla ALL operations
   - Per-shop categories NIE są ruszane
   - Merge dotyczy TYLKO global product-category assignments

## VALIDATION LOGIC DETAILS

### 1. Circular Reference Prevention
```php
if ($sourceCategory->isAncestorOf($this->targetCategoryId)) {
    // PREVENTED: Parent → Child merge
    // Example: Category "Vehicles" → Child "Cars" (Cars is descendant of Vehicles)
    // Result: ERROR - circular reference
}
```

**Edge Cases Covered:**
- Direct parent → child (level difference = 1)
- Grandparent → grandchild (level difference = 2+)
- Root → any descendant

### 2. Max Level Check
```php
$maxDescendantLevel = $sourceCategory->getMaxDescendantLevel();
$wouldBeLevel = $targetCategory->level + 1;
$finalLevel = $wouldBeLevel + $maxDescendantLevel;

if ($finalLevel > Category::MAX_LEVEL) {
    // PREVENTED: Exceeding max hierarchy depth
}
```

**Example Scenario:**
- Source category level: 2 (has children at level 3, 4)
- Source max descendant level: 2 (deepest child is 2 levels below)
- Target category level: 3
- Children would be at: 3 + 1 = 4
- Deepest descendant would be at: 4 + 2 = 6
- If MAX_LEVEL = 5 → ERROR (6 > 5)

### 3. Duplicate Product-Category Prevention
```php
$hasTargetCategory = $product->categories()
                            ->wherePivotNull('shop_id')
                            ->where('categories.id', $targetCategory->id)
                            ->exists();

if (!$hasTargetCategory) {
    $product->categories()->attach($targetCategory->id, ['shop_id' => null]);
}
```

**Why Needed:**
- Produkt może ALREADY mieć target category (multi-category product)
- Attach bez check → duplicate pivot row → CONSTRAINT ERROR
- Check prevents constraint violation

### 4. Primary Category Update
```php
if ($product->primary_category_id === $sourceCategory->id) {
    $product->primary_category_id = $targetCategory->id;
    $product->save();
}
```

**Why Critical:**
- Produkt MUSI mieć valid primary category
- Source category będzie usunięta
- Leaving primary_category_id pointing to deleted category → DATA INTEGRITY ISSUE
- Update ensures consistency

## TESTING CHECKLIST (Manual Tests)

### 1. Basic Merge Test
- [ ] Otwórz CategoryTree
- [ ] Wybierz kategorię z produktami (np. 5 produktów)
- [ ] Kliknij "Merge Category" (button do dodania przez frontend-specialist)
- [ ] Modal się otwiera, pokazuje warnings
- [ ] Wybierz target category (different from source)
- [ ] Kliknij "Połącz kategorie"
- [ ] Verify:
  - [ ] Source category usunięta
  - [ ] Wszystkie 5 produktów przeniesione do target
  - [ ] Flash message: "Połączono kategorie: ... → ... Przeniesiono 5 produktów."

### 2. Merge with Children Test
- [ ] Wybierz parent category z 3 children (każde child ma produkty)
- [ ] Merge do innej kategorii
- [ ] Verify:
  - [ ] Source parent usunięta
  - [ ] 3 children przeniesione do target (parent_id updated)
  - [ ] Wszystkie produkty z children nadal przypisane do swoich children
  - [ ] Tree structure spójna (no orphans)

### 3. Circular Reference Prevention Test
- [ ] Wybierz parent category (np. "Vehicles")
- [ ] Try merge do child category (np. "Cars")
- [ ] Verify:
  - [ ] Error: "Nie można połączyć kategorii z własnym potomkiem (zapętlenie)."
  - [ ] Merge NIE wykonany

### 4. Same Category Prevention Test
- [ ] Wybierz category A
- [ ] Try merge do category A (same category)
- [ ] Verify:
  - [ ] Error: "Kategoria źródłowa i docelowa muszą być różne."
  - [ ] Merge NIE wykonany

### 5. Max Level Prevention Test
- [ ] Znajdź kategorię z children na poziomie 4-5 (deep hierarchy)
- [ ] Try merge do kategorii na poziomie 3-4 (result would exceed MAX_LEVEL=5)
- [ ] Verify:
  - [ ] Error: "Nie można połączyć kategorii - przekroczono maksymalną głębokość drzewa..."
  - [ ] Merge NIE wykonany

### 6. Empty Category Merge Test
- [ ] Wybierz kategorię BEZ produktów i BEZ children
- [ ] Merge do innej kategorii
- [ ] Verify:
  - [ ] Warning: "Kategoria jest pusta (brak produktów i podkategorii)."
  - [ ] Merge wykonany pomyślnie
  - [ ] Source category usunięta

### 7. Primary Category Update Test
- [ ] Znajdź produkt gdzie source category JEST primary category
- [ ] Merge source → target
- [ ] Verify:
  - [ ] Produkt primary_category_id zmieniony na target ID
  - [ ] Produkt NIE ma nieistniejącej primary category

### 8. Duplicate Category Prevention Test
- [ ] Znajdź produkt który JUŻ MA target category (multi-category product)
- [ ] Merge source → target
- [ ] Verify:
  - [ ] NIE powstał duplicate pivot row
  - [ ] Produkt ma target category XXXXXXXXX (bez duplicate)
  - [ ] Source category detached

### 9. Partial Error Handling Test
- [ ] Create scenario gdzie 1-2 produkty mogą fail (np. constraint violation mock)
- [ ] Merge category
- [ ] Verify:
  - [ ] Merge KONTYNUOWANY (continue-on-error)
  - [ ] Successful products przeniesione
  - [ ] Failed products zalogowane
  - [ ] Flash warning: "...ale wystąpiły błędy: Product ID X: ..."

### 10. Transaction Rollback Test (Child Error)
- [ ] Mock error during child category move
- [ ] Try merge
- [ ] Verify:
  - [ ] Transaction ROLLBACK (atomicity)
  - [ ] Source category NIE usunięta
  - [ ] Products NIE przeniesione (rollback)
  - [ ] Error message shown

## KNOWN EDGE CASES

### 1. Multi-Category Products
**Scenario:** Produkt ma SOURCE i TARGET categories jednocześnie (przed merge).

**Behavior:**
- Duplicate check PREVENTS drugi pivot dla target
- Source category DETACHED
- Target category REMAINS (no duplicate)
- Primary category updated if needed

### 2. Per-Shop Categories (IGNORED)
**Scenario:** Produkt ma per-shop category assignments (`product_categories.shop_id NOT NULL`).

**Behavior:**
- Merge operations używają `wherePivotNull('shop_id')`
- Per-shop categories NIE są ruszane
- ONLY global categories (`shop_id = null`) są merged

**Why:**
- Merge dotyczy GLOBAL category structure
- Per-shop overrides są niezależne
- User może mieć różne per-shop mappings (nie chcemy ich zepsuć)

### 3. Deep Hierarchy with Multiple Branches
**Scenario:** Source ma children na różnych poziomach (branch A: level 3, branch B: level 5).

**Behavior:**
- `getMaxDescendantLevel()` zwraca MAX (5 - source level)
- Validation sprawdza NAJGŁĘBSZY branch
- Jeśli exceed → ERROR (prevents ANY branch from exceeding)

### 4. Empty Category (No Products/Children)
**Scenario:** Source category jest pusta (zero products, zero children).

**Behavior:**
- Warning: "Kategoria jest pusta..."
- Merge allowed (just delete source, nothing to move)
- User może używać jako "cleanup" operation

## PLIKI ZMODYFIKOWANE

### app/Http/Livewire/Products/Categories/CategoryTree.php
**Linie dodane:** ~270 lines (properties + methods + documentation)

**Sekcja 1: Properties (lines 194-220):**
- `showMergeCategoriesModal`
- `sourceCategoryId`
- `targetCategoryId`
- `mergeWarnings`

**Sekcja 2: Methods (lines 1235-1500):**
- `openCategoryMergeModal()` (~50 lines)
- `closeCategoryMergeModal()` (~7 lines)
- `mergeCategories()` (~165 lines)

## NASTĘPNE KROKI

### 1. Frontend Implementation (frontend-specialist)
**Zadanie:** Dodanie UI dla Category Merge w CategoryTree Blade view

**Requirements:**
- [ ] Dropdown button "Actions" dla każdej kategorii w tree view
- [ ] Option "Merge Category" w dropdown
- [ ] Wire:click call `openCategoryMergeModal(categoryId)`
- [ ] Modal Blade partial z:
  - [ ] Source category display (read-only)
  - [ ] Target category selector (dropdown z available categories)
  - [ ] Warnings display (list z `mergeWarnings`)
  - [ ] Buttons: "Połącz kategorie" (wire:click="mergeCategories") + "Anuluj" (wire:click="closeCategoryMergeModal")
- [ ] Loading states (wire:loading)
- [ ] Validation feedback (prevent submit bez target selection)

**Plik do modyfikacji:**
- `resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php`

**Wzorzec do użycia:**
- Force Delete Modal (lines ~710-770 w view) - podobna struktura

### 2. Integration Tests (po frontend)
- [ ] User acceptance testing według checklisty powyżej
- [ ] Edge cases verification
- [ ] Performance test (merge category z 1000+ products)

### 3. Documentation Update
- [ ] Update plan: Mark sekcja 2.2.2.2.4 jako ✅ COMPLETED
- [ ] Add to `_ISSUES_FIXES/` jeśli znajdziesz edge case issues

## PODSUMOWANIE

**Zaimplementowano PEŁNĄ backend logic dla Category Merge:**
- ✅ Properties dla modal state
- ✅ Validation (5 checks: both selected, different, exists, circular ref, max level)
- ✅ openCategoryMergeModal() - load source, collect warnings, open modal
- ✅ closeCategoryMergeModal() - reset state
- ✅ mergeCategories() - core logic:
  - Move products (continue-on-error)
  - Update primary categories
  - Move children (stop-on-error)
  - Delete source
  - User feedback + logging

**Enterprise Patterns:**
- ✅ DB::transaction() atomicity
- ✅ Validation before execution
- ✅ Continue-on-error (products) / Stop-on-error (children)
- ✅ Detailed logging (info/error levels)
- ✅ Clear user feedback (success/warning/error)
- ✅ Global categories only (`wherePivotNull('shop_id')`)

**Context7 Livewire 3.x Compliance:**
- ✅ Session flash messages (NOT `$this->addError()`)
- ✅ Component properties (public, typed)
- ✅ Method naming conventions
- ✅ Exception handling patterns
- ✅ Log facade usage

**READY FOR:**
- Frontend implementation (modal UI + wire:click bindings)
- User acceptance testing
- Production deployment (po frontend + tests)
