# ETAP_07d: Import Modal Redesign - Category Comparison Tree & Variant Import

**Status**: ❌ **NIE ROZPOCZETY**
**Priority**: WYSOKI (UX improvement - wizualizacja kategorii + automatyczny import wariantow)
**Estimated Time**: 28-38h (5 FAZ)
**Dependencies**: ETAP_07b (Category System), ETAP_07c (Job Operations UX)
**Created**: 2025-12-09
**Last Updated**: 2025-12-09

---

## PROBLEM OVERVIEW

### Obecny stan (problemy):

1. **Modal "Podglad kategorii" bez wizualizacji drzewa**
   - Pokazuje tylko komunikat "Wszystkie kategorie juz istnieja!" bez szczegolowego widoku
   - Uzytkownik nie widzi hierarchii kategorii
   - Brak informacji o tym, ktore kategorie sa gdzie

2. **Brak porownania PrestaShop vs PPM**
   - Nie wiadomo ktore kategorie istnieja tylko w PrestaShop
   - Nie wiadomo ktore kategorie istnieja tylko w PPM
   - Brak statusow synchronizacji

3. **Brak akcji na kategoriach**
   - Nie mozna dodac brakujacych kategorii z PrestaShop
   - Nie mozna usunac nieuzywanych kategorii z PPM
   - Brak mozliwosci modyfikacji mappingow

4. **Brak importu wariantow**
   - Warianty (combinations) z PrestaShop nie sa automatycznie importowane
   - Uzytkownik musi recznic tworzyc warianty w PPM
   - Brak konfiguracji importu wariantow

### Docelowy stan (rozwiazanie):

1. **Rich Category Tree** - hierarchiczne drzewko z pelnym porownaniem PS vs PPM
2. **Category Actions** - przyciski do dodawania/usuwania kategorii
3. **Status Badges** - wizualne oznaczenie statusu kazdej kategorii
4. **Variant Auto-Import** - opcja automatycznego importu wariantow z PrestaShop

---

## ARCHITEKTURA ROZWIAZANIA

### Diagram Komponentow

```
┌─────────────────────────────────────────────────────────────────┐
│              CategoryComparisonModal (Livewire)                  │
│         Glowny komponent z drzewkiem i formularzami              │
├─────────────────────────────────────────────────────────────────┤
│  + Traits: TreeManagement, CategoryActions, VariantConfig        │
│  + Props: shopId, previewId, isOpen                              │
│  + State: comparisonTree, selectedToAdd, selectedToRemove        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              CategoryComparisonService (Service)                 │
│           Logika biznesowa porownania kategorii                  │
├─────────────────────────────────────────────────────────────────┤
│  + buildComparisonTree(shopId): array                           │
│  + getCategoriesOnlyInPrestaShop(shopId): array                 │
│  + getCategoriesOnlyInPPM(shopId): array                        │
│  + addCategoriesToPPM(categoryIds): void                        │
│  + removeUnusedCategories(categoryIds): void                    │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│     CategoryMapper      │     │   VariantImportService  │
│   (istniejacy serwis)   │     │     (nowy serwis)       │
└─────────────────────────┘     └─────────────────────────┘
```

### Struktura Danych - CategoryComparisonNode

```php
[
    'id' => int|null,                    // PPM category ID
    'prestashop_id' => int|null,         // PrestaShop category ID
    'name' => string,                    // Nazwa kategorii
    'full_path' => string,               // np. "Motorowery > Skutery > 50cc"
    'level' => int,                      // Poziom (0-4)
    'status' => 'both'|'ps_only'|'ppm_only',  // Status synchronizacji
    'is_mapped' => bool,                 // Czy zmapowane
    'product_count_ps' => int,           // Produkty w PrestaShop
    'product_count_ppm' => int,          // Produkty w PPM
    'children' => array,                 // Zagniezdzone kategorie
    'is_selected' => bool,               // Zaznaczone przez usera
    'can_delete' => bool,                // Mozna usunac (brak produktow)
]
```

### Flow Uzytkownika

```
User klika "Importuj z PrestaShop"
        ↓
Modal otwiera sie z loading spinner
        ↓
CategoryComparisonService.buildComparisonTree() laduje dane
        ↓
Drzewko kategorii wyswietla sie z kolorami statusu:
  ● Zielone = w obu systemach (zsynchronizowane)
  ○ Zolte = tylko w PrestaShop (do dodania)
  ✗ Czerwone = tylko w PPM (do usuniecia)
        ↓
User zaznacza kategorie do dodania/usuniecia
        ↓
User wlacza/wylacza opcje importu wariantow
        ↓
User klika "Zatwierdz i importuj"
        ↓
System wykonuje akcje na kategoriach + dispatch BulkImportProducts
```

---

## FAZA 1: CategoryComparisonService (6-8h)

### ❌ 1.1 Utworzenie serwisu CategoryComparisonService
#### ❌ 1.1.1 Utworzenie klasy bazowej
        ❌ 1.1.1.1 Utworz plik app/Services/CategoryComparisonService.php
        ❌ 1.1.1.2 Dodaj dependency injection: CategoryMapper, PrestaShopClientFactory
        ❌ 1.1.1.3 Dodaj konstruktor z serwisami

#### ❌ 1.1.2 Metoda buildComparisonTree()
        ❌ 1.1.2.1 Pobierz wszystkie kategorie z PrestaShop API
        ❌ 1.1.2.2 Pobierz wszystkie kategorie z PPM (tabela categories)
        ❌ 1.1.2.3 Pobierz wszystkie mappingi (shop_mappings)
        ❌ 1.1.2.4 Zbuduj hierarchiczne drzewko z porownaniem
        ❌ 1.1.2.5 Dodaj status do kazdego wezla (both/ps_only/ppm_only)
        ❌ 1.1.2.6 Dodaj liczniki produktow (product_count_ps, product_count_ppm)

#### ❌ 1.1.3 Metody pomocnicze
        ❌ 1.1.3.1 getCategoriesOnlyInPrestaShop(shopId): array
        ❌ 1.1.3.2 getCategoriesOnlyInPPM(shopId): array
        ❌ 1.1.3.3 getCategoriesBothSystems(shopId): array
        ❌ 1.1.3.4 getSummary(shopId): CategoryComparisonSummary

### ❌ 1.2 Metody akcji na kategoriach
#### ❌ 1.2.1 Dodawanie kategorii do PPM
        ❌ 1.2.1.1 addCategoriesToPPM(array $prestashopIds, int $shopId): void
        ❌ 1.2.1.2 Wykorzystaj CategoryMapper::mapOrCreateFromPrestaShop()
        ❌ 1.2.1.3 Zachowaj hierarchie (parent_id)
        ❌ 1.2.1.4 Utworz mappingi w shop_mappings

#### ❌ 1.2.2 Usuwanie kategorii z PPM
        ❌ 1.2.2.1 removeUnusedCategories(array $ppmIds): void
        ❌ 1.2.2.2 Walidacja: kategoria musi miec 0 produktow
        ❌ 1.2.2.3 Soft-delete lub hard-delete (configurable)
        ❌ 1.2.2.4 Usun mappingi z shop_mappings

### ❌ 1.3 DTOs
#### ❌ 1.3.1 CategoryComparisonNode DTO
        ❌ 1.3.1.1 Utworz app/DTOs/CategoryComparisonNode.php
        ❌ 1.3.1.2 Wszystkie pola z dokumentacji architektury
        ❌ 1.3.1.3 Metoda toArray() dla Livewire

#### ❌ 1.3.2 CategoryComparisonSummary DTO
        ❌ 1.3.2.1 Utworz app/DTOs/CategoryComparisonSummary.php
        ❌ 1.3.2.2 Pola: categories_to_add, categories_to_remove, categories_synced
        ❌ 1.3.2.3 Pola: variants_to_import, products_with_variants

---

## FAZA 2: CategoryComparisonModal Livewire (8-10h)

### ❌ 2.1 Utworzenie komponentu Livewire
#### ❌ 2.1.1 Glowny komponent
        ❌ 2.1.1.1 Utworz app/Http/Livewire/Components/CategoryComparisonModal.php
        ❌ 2.1.1.2 Dodaj props: shopId, previewId, isOpen
        ❌ 2.1.1.3 Dodaj state: comparisonTree, selectedToAdd, selectedToRemove
        ❌ 2.1.1.4 Dodaj state: importVariants, variantConfig, summary

#### ❌ 2.1.2 Lifecycle methods
        ❌ 2.1.2.1 mount() - inicjalizacja
        ❌ 2.1.2.2 loadComparisonTree() - ladowanie danych
        ❌ 2.1.2.3 render() - zwracanie widoku

### ❌ 2.2 Traity
#### ❌ 2.2.1 CategoryTreeManagement trait
        ❌ 2.2.1.1 Utworz app/Http/Livewire/Components/Traits/CategoryTreeManagement.php
        ❌ 2.2.1.2 expandNode(nodeId) - rozwin wezel drzewa
        ❌ 2.2.1.3 collapseNode(nodeId) - zwin wezel drzewa
        ❌ 2.2.1.4 toggleNode(nodeId) - przelacz
        ❌ 2.2.1.5 expandAll() / collapseAll()

#### ❌ 2.2.2 CategoryActions trait
        ❌ 2.2.2.1 Utworz app/Http/Livewire/Components/Traits/CategoryActions.php
        ❌ 2.2.2.2 selectCategoryToAdd(prestashopId)
        ❌ 2.2.2.3 selectCategoryToRemove(ppmId)
        ❌ 2.2.2.4 selectAllToAdd() / deselectAllToAdd()
        ❌ 2.2.2.5 executeActions() - wykonaj zaznaczone akcje

#### ❌ 2.2.3 VariantImportConfig trait
        ❌ 2.2.3.1 Utworz app/Http/Livewire/Components/Traits/VariantImportConfig.php
        ❌ 2.2.3.2 toggleVariantImport()
        ❌ 2.2.3.3 setVariantConfig(array $config)
        ❌ 2.2.3.4 getVariantCount() - policz warianty do importu

#### ❌ 2.2.4 ComparisonSummary trait
        ❌ 2.2.4.1 Utworz app/Http/Livewire/Components/Traits/ComparisonSummary.php
        ❌ 2.2.4.2 calculateSummary()
        ❌ 2.2.4.3 getSummaryStats(): array

### ❌ 2.3 Integracja z istniejacym kodem
#### ❌ 2.3.1 Aktualizacja CategoryPreviewModal
        ❌ 2.3.1.1 Dodaj flage useEnhancedModal
        ❌ 2.3.1.2 Deleguj do CategoryComparisonModal gdy flaga = true
        ❌ 2.3.1.3 Zachowaj backward compatibility dla starego flow

#### ❌ 2.3.2 Eventy Livewire
        ❌ 2.3.2.1 open-category-comparison-modal
        ❌ 2.3.2.2 category-comparison-approved
        ❌ 2.3.2.3 categories-modified

---

## FAZA 3: Widoki Blade (4-6h)

### ❌ 3.1 Glowny widok modala
#### ❌ 3.1.1 Utworzenie pliku
        ❌ 3.1.1.1 Utworz resources/views/livewire/components/category-comparison-modal.blade.php
        ❌ 3.1.1.2 Struktura: header, tree, variant-section, summary, footer
        ❌ 3.1.1.3 Dark theme zgodny z reszta PPM
        ❌ 3.1.1.4 Responsywnosc (mobile-friendly)

### ❌ 3.2 Partial: category-tree-node
#### ❌ 3.2.1 Rekursywny wezel drzewa
        ❌ 3.2.1.1 Utworz resources/views/livewire/components/partials/category-tree-node.blade.php
        ❌ 3.2.1.2 Checkbox do zaznaczania
        ❌ 3.2.1.3 Ikona expand/collapse dla wezlow z dziecmi
        ❌ 3.2.1.4 Status badge (kolor + tekst)
        ❌ 3.2.1.5 Licznik produktow
        ❌ 3.2.1.6 Rekursywne renderowanie children

### ❌ 3.3 Partial: category-legend
#### ❌ 3.3.1 Legenda statusow
        ❌ 3.3.1.1 Utworz resources/views/livewire/components/partials/category-legend.blade.php
        ❌ 3.3.1.2 Zielony = zsynchronizowane
        ❌ 3.3.1.3 Zolty = tylko PrestaShop (do dodania)
        ❌ 3.3.1.4 Czerwony = tylko PPM (do usuniecia)

### ❌ 3.4 Partial: variant-import-section
#### ❌ 3.4.1 Sekcja konfiguracji wariantow
        ❌ 3.4.1.1 Utworz resources/views/livewire/components/partials/variant-import-section.blade.php
        ❌ 3.4.1.2 Checkbox glowny: "Importuj warianty z PrestaShop"
        ❌ 3.4.1.3 Licznik: "X wariantow znalezionych"
        ❌ 3.4.1.4 Opcje: tworz brakujace atrybuty, aktualizuj istniejace

### ❌ 3.5 Partial: summary-panel
#### ❌ 3.5.1 Panel podsumowania
        ❌ 3.5.1.1 Utworz resources/views/livewire/components/partials/summary-panel.blade.php
        ❌ 3.5.1.2 Kategorii do dodania: X
        ❌ 3.5.1.3 Kategorii do usuniecia: X
        ❌ 3.5.1.4 Juz zsynchronizowanych: X
        ❌ 3.5.1.5 Wariantow do importu: X

### ❌ 3.6 CSS
#### ❌ 3.6.1 Style dla drzewa kategorii
        ❌ 3.6.1.1 Dodaj style do resources/css/products/category-comparison.css
        ❌ 3.6.1.2 Klasy dla statusow: .category-synced, .category-ps-only, .category-ppm-only
        ❌ 3.6.1.3 Animacje expand/collapse
        ❌ 3.6.1.4 Import w app.css

---

## FAZA 4: Variant Import Service (6-8h)

### ❌ 4.1 Utworzenie VariantImportService
#### ❌ 4.1.1 Klasa serwisu
        ❌ 4.1.1.1 Utworz app/Services/VariantImportService.php
        ❌ 4.1.1.2 Dependency injection: PrestaShopClientFactory, AttributeValueService
        ❌ 4.1.1.3 Konstruktor z serwisami

#### ❌ 4.1.2 Metoda importCombination()
        ❌ 4.1.2.1 Sygn: importCombination(Product $product, array $combination, array $config): ?ProductVariant
        ❌ 4.1.2.2 Resolve atrybutow (kolor, rozmiar, etc.)
        ❌ 4.1.2.3 Znajdz lub utworz ProductVariant
        ❌ 4.1.2.4 Mapuj pola: reference, ean, price, quantity

#### ❌ 4.1.3 Metoda resolveAttributes()
        ❌ 4.1.3.1 Pobierz option_values z combination
        ❌ 4.1.3.2 Znajdz odpowiadajace AttributeValue w PPM
        ❌ 4.1.3.3 Opcjonalnie: utworz brakujace (jesli config['create_missing_attributes'])

#### ❌ 4.1.4 Metoda countVariantsToImport()
        ❌ 4.1.4.1 Policz warianty dla listy produktow
        ❌ 4.1.4.2 Zwroc liczbe i sample SKU

### ❌ 4.2 Integracja z BulkImportProducts
#### ❌ 4.2.1 Modyfikacja konstruktora
        ❌ 4.2.1.1 Dodaj parametr array $variantConfig = []
        ❌ 4.2.1.2 Zapisz w $this->variantConfig

#### ❌ 4.2.2 Modyfikacja importProduct()
        ❌ 4.2.2.1 Po zaimportowaniu produktu, sprawdz variantConfig['enabled']
        ❌ 4.2.2.2 Jesli enabled, wywolaj importVariants()
        ❌ 4.2.2.3 Loguj importowane warianty

#### ❌ 4.2.3 Nowa metoda importVariants()
        ❌ 4.2.3.1 Pobierz combinations z PrestaShop API (getCombinations)
        ❌ 4.2.3.2 Dla kazdego combination wywolaj VariantImportService::importCombination()
        ❌ 4.2.3.3 Aktualizuj JobProgress z liczba wariantow

### ❌ 4.3 Integracja z CategoryComparisonModal
#### ❌ 4.3.1 Przekazanie variantConfig
        ❌ 4.3.1.1 Po approve(), przekaz variantConfig do BulkImportProducts
        ❌ 4.3.1.2 Dispatch job z parametrem variantConfig

---

## FAZA 5: Testy i Polish (4-6h)

### ❌ 5.1 Testy jednostkowe
#### ❌ 5.1.1 CategoryComparisonService tests
        ❌ 5.1.1.1 Test buildComparisonTree() z mockowanymi danymi
        ❌ 5.1.1.2 Test addCategoriesToPPM()
        ❌ 5.1.1.3 Test removeUnusedCategories()

#### ❌ 5.1.2 VariantImportService tests
        ❌ 5.1.2.1 Test importCombination() z mockowanym combination
        ❌ 5.1.2.2 Test resolveAttributes()
        ❌ 5.1.2.3 Test countVariantsToImport()

### ❌ 5.2 Testy manualne
#### ❌ 5.2.1 Flow testy
        ❌ 5.2.1.1 Test otwarcia modala z pustym drzewem
        ❌ 5.2.1.2 Test otwarcia modala z kategoriami do dodania
        ❌ 5.2.1.3 Test zaznaczania/odznaczania kategorii
        ❌ 5.2.1.4 Test opcji importu wariantow
        ❌ 5.2.1.5 Test pelnego flow importu

#### ❌ 5.2.2 Edge cases
        ❌ 5.2.2.1 Test z duza liczba kategorii (>100)
        ❌ 5.2.2.2 Test z gleboka hierarchia (5 poziomow)
        ❌ 5.2.2.3 Test z produktami bez wariantow

### ❌ 5.3 UI Polish
#### ❌ 5.3.1 Animacje
        ❌ 5.3.1.1 Smooth expand/collapse drzewa
        ❌ 5.3.1.2 Loading spinner podczas ladowania
        ❌ 5.3.1.3 Success/error toast po akcjach

#### ❌ 5.3.2 Accessibility
        ❌ 5.3.2.1 ARIA labels dla drzewa
        ❌ 5.3.2.2 Keyboard navigation
        ❌ 5.3.2.3 Screen reader announcements

### ❌ 5.4 Deployment
#### ❌ 5.4.1 Przygotowanie
        ❌ 5.4.1.1 PHP syntax validation wszystkich plikow
        ❌ 5.4.1.2 Utworzenie skryptu deploy_etap07d.ps1
        ❌ 5.4.1.3 npm run build dla CSS

#### ❌ 5.4.2 Deploy
        ❌ 5.4.2.1 Upload wszystkich plikow
        ❌ 5.4.2.2 Cache clear
        ❌ 5.4.2.3 Weryfikacja na produkcji

---

## PROGRESS SUMMARY

**ETAP Status:** ❌ **NIE ROZPOCZETY** (0/5 FAZ)

**Completion:**
- FAZA 1: ❌ 0% - CategoryComparisonService
- FAZA 2: ❌ 0% - CategoryComparisonModal Livewire
- FAZA 3: ❌ 0% - Widoki Blade
- FAZA 4: ❌ 0% - Variant Import Service
- FAZA 5: ❌ 0% - Testy i Polish

**Total:** 0/~60 tasks (0%)

---

## DELIVERABLES

Po ukonczeniu ETAP_07d:

1. **Rich Category Tree** - hierarchiczne drzewko z porownaniem PS vs PPM
2. **Status Badges** - wizualne statusy: zsynchronizowane, do dodania, do usuniecia
3. **Category Actions** - mozliwosc dodawania/usuwania kategorii
4. **Variant Auto-Import** - automatyczny import wariantow z PrestaShop
5. **Summary Panel** - podsumowanie akcji przed wykonaniem

---

## REFERENCES

**Related ETAPs:**
- ETAP_07b - Category System Redesign (category mapping infrastructure)
- ETAP_07c - Job Operations UX (progress bar, background jobs)
- ETAP_07 - PrestaShop API (base client, import service)
- ETAP_05b - Produkty Warianty (variant model, attributes)

**Existing Components:**
- `app/Http/Livewire/Components/CategoryPreviewModal.php`
- `app/Services/PrestaShop/CategoryMapper.php`
- `app/Jobs/PrestaShop/BulkImportProducts.php`
- `app/Jobs/PrestaShop/AnalyzeMissingCategories.php`
- `app/Models/CategoryPreview.php`

**Architecture Documentation:**
- `_AGENT_REPORTS/architect_IMPORT_MODAL_REDESIGN_ARCHITECTURE.md`

---

**CRITICAL:** Ten ETAP wymaga zatwierdzenia uzytkownika przed implementacja.
