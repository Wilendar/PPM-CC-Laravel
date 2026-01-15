# RAPORT PRACY AGENTA: architect
**Data**: 2025-12-09
**Agent**: architect (Planning Manager)
**Zadanie**: Zaprojektowanie architektury dla rozbudowanego modala importu kategorii z drzewkiem kategorii i importem wariantow

## EXECUTIVE SUMMARY

Zaprojektowano architekture dla nowego modala "Podglad kategorii" w PPM-CC-Laravel, ktory:
1. Pokazuje hierarchiczne drzewko kategorii z porownaniem PrestaShop vs PPM
2. Umozliwia dodawanie/usuwanie/modyfikacje kategorii
3. Zawiera opcje automatycznego importu wariantow z PrestaShop
4. Integruje sie z istniejacym kodem (CategoryPreviewModal, CategoryMapper, BulkImportProducts)

---

## 1. ANALIZA AKTUALNEGO STANU

### 1.1 Istniejace komponenty

| Komponent | Plik | Opis |
|-----------|------|------|
| CategoryPreviewModal | `app/Http/Livewire/Components/CategoryPreviewModal.php` | Modal wyswietlajacy podglad kategorii do importu |
| CategoryPreview Model | `app/Models/CategoryPreview.php` | Model tymczasowych podgladow (expires 1h) |
| CategoryMapper | `app/Services/PrestaShop/CategoryMapper.php` | Mapowanie kategorii PPM <-> PrestaShop |
| AnalyzeMissingCategories | `app/Jobs/PrestaShop/AnalyzeMissingCategories.php` | Job analizujacy brakujace kategorie |
| BulkImportProducts | `app/Jobs/PrestaShop/BulkImportProducts.php` | Job importujacy produkty z PrestaShop |
| PrestaShop8Client | `app/Services/PrestaShop/PrestaShop8Client.php` | Klient API PrestaShop (ma getCombinations) |

### 1.2 Obecne problemy

1. **Modal pokazuje tylko komunikat "Wszystkie kategorie juz istnieja!"** - brak szczegolowego drzewka
2. **Brak wizualizacji hierarchii** - uzytkownik nie widzi pelnego drzewa kategorii
3. **Brak akcji na kategoriach** - nie mozna dodac/usunac kategorii
4. **Brak opcji importu wariantow** - warianty nie sa automatycznie importowane

---

## 2. ARCHITEKTURA ROZWIAZANIA

### 2.1 Diagram Komponentow

```
                    ┌─────────────────────────────────────────────────────┐
                    │             CategoryComparisonModal                  │
                    │          (Nowy komponent Livewire)                   │
                    ├─────────────────────────────────────────────────────┤
                    │  Props:                                              │
                    │  - shopId: int                                       │
                    │  - previewId: ?int                                   │
                    │  - isOpen: bool                                      │
                    ├─────────────────────────────────────────────────────┤
                    │  State:                                              │
                    │  - comparisonTree: array                             │
                    │  - selectedCategoriesToAdd: array                    │
                    │  - selectedCategoriesToRemove: array                 │
                    │  - importVariantsEnabled: bool                       │
                    │  - summary: array                                    │
                    └─────────────────────────────────────────────────────┘
                                          │
                                          │ uses
                                          ▼
        ┌─────────────────────────────────────────────────────────────────┐
        │                  CategoryComparisonService                       │
        │               (Nowy serwis - logika biznesowa)                   │
        ├─────────────────────────────────────────────────────────────────┤
        │  Methods:                                                        │
        │  + buildComparisonTree(shopId): array                           │
        │  + getCategoriesOnlyInPrestaShop(shopId): array                 │
        │  + getCategoriesOnlyInPPM(shopId): array                        │
        │  + getCategoriesBothSystems(shopId): array                      │
        │  + addCategoriesToPPM(categoryIds, shopId): void                │
        │  + removeUnusedCategories(categoryIds, shopId): void            │
        │  + getSummary(shopId): CategoryComparisonSummary                │
        └─────────────────────────────────────────────────────────────────┘
                          │                    │
                          │                    │
              ┌───────────┘                    └───────────┐
              │                                            │
              ▼                                            ▼
┌─────────────────────────────┐            ┌─────────────────────────────┐
│       CategoryMapper        │            │     PrestaShop8Client       │
│    (istniejacy serwis)      │            │    (istniejacy klient)      │
├─────────────────────────────┤            ├─────────────────────────────┤
│ + mapOrCreateFromPrestaShop │            │ + getCategories()           │
│ + mapFromPrestaShop()       │            │ + getCategory(id)           │
│ + getAllMappingsForShop()   │            │ + getCombinations(prodId)   │
│ + deleteMapping()           │            │ + getProducts()             │
└─────────────────────────────┘            └─────────────────────────────┘
```

### 2.2 Struktura Danych

#### 2.2.1 CategoryComparisonNode (pojedynczy wezel drzewa)

```php
[
    'id' => int,                          // PPM category ID lub null
    'prestashop_id' => int,               // PrestaShop category ID lub null
    'name' => string,                     // Nazwa kategorii
    'full_path' => string,                // np. "Motorowery > Skutery > 50cc"
    'level' => int,                       // Poziom zaglebienia (0-4)
    'status' => string,                   // 'both' | 'prestashop_only' | 'ppm_only'
    'is_mapped' => bool,                  // Czy istnieje mapowanie
    'product_count_ps' => int,            // Liczba produktow w PS
    'product_count_ppm' => int,           // Liczba produktow w PPM
    'children' => array,                  // Zagniezdzone kategorie
    'is_selected' => bool,                // Czy zaznaczona przez uzytkownika
    'can_delete' => bool,                 // Czy mozna usunac (brak produktow)
]
```

#### 2.2.2 CategoryComparisonSummary (podsumowanie)

```php
[
    'categories_to_add' => int,           // Liczba kategorii do dodania
    'categories_to_remove' => int,        // Liczba kategorii do usuniecia
    'categories_synced' => int,           // Liczba zsynchronizowanych
    'total_prestashop' => int,            // Lacznie w PrestaShop
    'total_ppm' => int,                   // Lacznie w PPM
    'variants_to_import' => int,          // Liczba wariantow do importu
    'products_with_variants' => int,      // Produkty z wariantami
]
```

#### 2.2.3 VariantImportConfig (konfiguracja importu wariantow)

```php
[
    'enabled' => bool,                    // Czy importowac warianty
    'mode' => string,                     // 'auto' | 'manual' | 'skip'
    'match_by' => string,                 // 'sku' | 'reference' | 'ean'
    'create_missing_attributes' => bool,  // Tworz brakujace atrybuty
    'update_existing' => bool,            // Aktualizuj istniejace warianty
]
```

---

## 3. FLOW UZYTKOWNIKA

### 3.1 Happy Path - Pelny Import z Wariantami

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ KROK 1: Uzytkownik otwiera modal "Podglad kategorii"                        │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ User klika "Importuj z PrestaShop" → Modal otwiera sie                  │ │
│ │ System laduje dane w tle (CategoryComparisonService.buildComparisonTree)│ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                    ↓                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│ KROK 2: Wyswietlenie drzewa kategorii z porownaniem                         │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │                     PODGLAD KATEGORII                                   │ │
│ │ ──────────────────────────────────────────────────────────────────────  │ │
│ │ ▼ [x] Motorowery              ● ZSYNCHRONIZOWANE  (12 prod)             │ │
│ │   ├── [x] Skutery 50cc        ● ZSYNCHRONIZOWANE  (5 prod)              │ │
│ │   ├── [ ] Skutery 125cc       ○ DO DODANIA        (8 prod)              │ │
│ │   └── [x] Czesci zamienne     ● ZSYNCHRONIZOWANE  (15 prod)             │ │
│ │ ▼ [ ] Czesci samochodowe      ○ DO DODANIA        (25 prod)             │ │
│ │   ├── [ ] Filtry              ○ DO DODANIA        (10 prod)             │ │
│ │   └── [ ] Klocki hamulcowe    ○ DO DODANIA        (15 prod)             │ │
│ │ ▼ [x] Stara kategoria PPM     ✗ DO USUNIECIA      (0 prod)              │ │
│ │ ──────────────────────────────────────────────────────────────────────  │ │
│ │                                                                         │ │
│ │ LEGENDA:  ● Zielone = w obu systemach                                   │ │
│ │           ○ Zolte = tylko PrestaShop (do dodania)                       │ │
│ │           ✗ Czerwone = tylko PPM (do usuniecia)                         │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                    ↓                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│ KROK 3: Akcje na kategoriach                                                │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ [Zaznacz wszystkie do dodania]  [Odznacz wszystkie]                     │ │
│ │                                                                         │ │
│ │ PODSUMOWANIE:                                                           │ │
│ │ • Kategorii do dodania: 5                                               │ │
│ │ • Kategorii do usuniecia: 1                                             │ │
│ │ • Juz zsynchronizowanych: 8                                             │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                    ↓                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│ KROK 4: Opcje importu wariantow                                             │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ ☑ Automatycznie importuj brakujace warianty z PrestaShop                │ │
│ │                                                                         │ │
│ │   Po imporcie produktu, system sprawdzi warianty (combinations) w PS    │ │
│ │   i utworzy odpowiadajace warianty w PPM.                               │ │
│ │                                                                         │ │
│ │   Znaleziono: 45 wariantow do zaimportowania (12 produktow)             │ │
│ │                                                                         │ │
│ │   Opcje:                                                                │ │
│ │   ○ Tworz brakujace atrybuty (np. nowe kolory, rozmiary)                │ │
│ │   ○ Aktualizuj istniejace warianty (ceny, stany)                        │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                    ↓                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│ KROK 5: Potwierdzenie i rozpoczecie importu                                 │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ [Anuluj]                            [Dodaj kategorie i rozpocznij import]│ │
│ │                                                                         │ │
│ │ Co sie stanie:                                                          │ │
│ │ 1. Utworzone zostana 5 nowych kategorii w PPM                           │ │
│ │ 2. Usunieta zostanie 1 nieuzywana kategoria                             │ │
│ │ 3. Zaimportowanych zostanie ~50 produktow                               │ │
│ │ 4. Utworzonych zostanie ~45 wariantow                                   │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Flow - Wszystkie Kategorie Zsynchronizowane

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ SCENARIUSZ: Brak nowych kategorii do dodania                                │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │                     PODGLAD KATEGORII                                   │ │
│ │ ──────────────────────────────────────────────────────────────────────  │ │
│ │                                                                         │ │
│ │   ✅ Wszystkie kategorie sa juz zsynchronizowane!                       │ │
│ │                                                                         │ │
│ │   ▼ [x] Motorowery              ● ZSYNCHRONIZOWANE  (12 prod)           │ │
│ │     ├── [x] Skutery 50cc        ● ZSYNCHRONIZOWANE  (5 prod)            │ │
│ │     └── [x] Czesci zamienne     ● ZSYNCHRONIZOWANE  (15 prod)           │ │
│ │                                                                         │ │
│ │ ──────────────────────────────────────────────────────────────────────  │ │
│ │                                                                         │ │
│ │ ☑ Importuj warianty z PrestaShop (45 wariantow znalezionych)            │ │
│ │                                                                         │ │
│ │ [Anuluj]                                      [Importuj produkty]        │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. ARCHITEKTURA KOMPONENTOW LIVEWIRE

### 4.1 Decyzja: Jeden Modal vs Osobne Komponenty

**REKOMENDACJA: Jeden Rozbudowany Modal z Traitami**

```
CategoryComparisonModal (glowny komponent)
├── Traits/
│   ├── CategoryTreeManagement.php     # Logika drzewa kategorii
│   ├── CategoryActions.php            # Akcje: dodaj/usun/modyfikuj
│   ├── VariantImportConfig.php        # Konfiguracja importu wariantow
│   └── ComparisonSummary.php          # Obliczanie podsumowania
├── Views/
│   ├── category-comparison-modal.blade.php          # Glowny widok
│   ├── partials/
│   │   ├── category-tree-node.blade.php             # Pojedynczy wezel
│   │   ├── category-legend.blade.php                # Legenda kolorow
│   │   ├── variant-import-section.blade.php         # Sekcja wariantow
│   │   └── summary-panel.blade.php                  # Panel podsumowania
└── Services/
    └── CategoryComparisonService.php  # Logika biznesowa
```

### 4.2 Hierarchia Komponentow

```php
// CategoryComparisonModal.php - Glowny komponent
class CategoryComparisonModal extends Component
{
    use CategoryTreeManagement;    // loadTree, expandNode, collapseNode
    use CategoryActions;           // selectCategory, addCategories, removeCategories
    use VariantImportConfig;       // toggleVariantImport, getVariantCount
    use ComparisonSummary;         // calculateSummary, getSummaryStats

    // Props
    public int $shopId;
    public ?int $previewId = null;
    public bool $isOpen = false;

    // State
    public array $comparisonTree = [];
    public array $selectedToAdd = [];
    public array $selectedToRemove = [];
    public bool $importVariants = true;
    public array $variantConfig = [];
    public array $summary = [];
    public bool $isLoading = false;
    public string $loadingMessage = '';
}
```

### 4.3 Integracja z Istniejacym Kodem

```php
// Modyfikacja CategoryPreviewModal - delegowanie do nowego komponentu
class CategoryPreviewModal extends Component
{
    // Dodaj flage do przekierowania do nowego modala
    public bool $useEnhancedModal = true;

    public function showEnhancedModal(): void
    {
        if ($this->useEnhancedModal) {
            $this->dispatch('open-category-comparison-modal', [
                'shopId' => $this->shopId,
                'previewId' => $this->previewId,
            ]);
        }
    }
}
```

---

## 5. INTEGRACJA Z ISTNIEJACYM KODEM IMPORTU

### 5.1 Modyfikacja BulkImportProducts Job

```php
// Dodanie obslugi importu wariantow
class BulkImportProducts implements ShouldQueue
{
    protected array $variantConfig;

    public function __construct(
        PrestaShopShop $shop,
        string $mode = 'all',
        array $options = [],
        ?string $jobId = null,
        array $variantConfig = []  // NOWY parametr
    ) {
        $this->variantConfig = $variantConfig;
    }

    protected function importProduct(array $productData): void
    {
        // Istniejaca logika importu produktu
        $ppmProduct = $this->importService->importProduct($productData);

        // NOWE: Import wariantow jesli wlaczony
        if ($this->variantConfig['enabled'] ?? false) {
            $this->importVariants($ppmProduct, $productData);
        }
    }

    protected function importVariants(Product $ppmProduct, array $psProduct): void
    {
        $client = PrestaShopClientFactory::create($this->shop);
        $combinations = $client->getCombinations($psProduct['id']);

        foreach ($combinations as $combination) {
            $this->variantService->importCombination(
                $ppmProduct,
                $combination,
                $this->variantConfig
            );
        }
    }
}
```

### 5.2 Nowy Serwis VariantImportService

```php
class VariantImportService
{
    public function importCombination(
        Product $product,
        array $combination,
        array $config
    ): ?ProductVariant {
        // 1. Znajdz lub utworz atrybuty (kolor, rozmiar, etc.)
        $attributes = $this->resolveAttributes($combination, $config);

        // 2. Sprawdz czy wariant juz istnieje (po SKU/reference)
        $existing = $this->findExistingVariant($product, $combination, $config);

        if ($existing && !$config['update_existing']) {
            return $existing;
        }

        // 3. Utworz lub zaktualizuj wariant
        return $this->createOrUpdateVariant($product, $combination, $attributes, $config);
    }
}
```

---

## 6. STRUKTURA PLIKOW (PLAN IMPLEMENTACJI)

### 6.1 Nowe Pliki do Utworzenia

```
app/
├── Http/Livewire/Components/
│   └── CategoryComparisonModal.php                    # Glowny komponent
├── Http/Livewire/Components/Traits/
│   ├── CategoryTreeManagement.php                     # Trait: drzewko
│   ├── CategoryActions.php                            # Trait: akcje
│   ├── VariantImportConfig.php                        # Trait: warianty
│   └── ComparisonSummary.php                          # Trait: podsumowanie
├── Services/
│   ├── CategoryComparisonService.php                  # Logika biznesowa
│   └── VariantImportService.php                       # Import wariantow
├── DTOs/
│   ├── CategoryComparisonNode.php                     # DTO wezla
│   └── CategoryComparisonSummary.php                  # DTO podsumowania

resources/views/livewire/components/
├── category-comparison-modal.blade.php                # Glowny widok
└── partials/
    ├── category-tree-node.blade.php                   # Wezel drzewa
    ├── category-legend.blade.php                      # Legenda
    ├── variant-import-section.blade.php               # Sekcja wariantow
    └── summary-panel.blade.php                        # Podsumowanie
```

### 6.2 Pliki do Modyfikacji

```
app/
├── Jobs/PrestaShop/BulkImportProducts.php            # Dodanie variant import
├── Http/Livewire/Components/CategoryPreviewModal.php  # Delegacja do nowego modala
└── Models/CategoryPreview.php                         # Dodanie pola variant_config
```

---

## 7. PODSUMOWANIE ARCHITEKTONICZNE

### 7.1 Kluczowe Decyzje

| Aspekt | Decyzja | Uzasadnienie |
|--------|---------|--------------|
| Struktura komponentu | Jeden modal z traitami | Unika duplikacji stanu, latwiejsze zarzadzanie |
| Import wariantow | Opcjonalny, konfiguроwalny | Nie wszyscy uzytkownicy potrzebuja wariantow |
| Integracja | Rozszerzenie istniejacego kodu | Minimalizuje ryzyko regresji |
| Drzewko kategorii | Lazy-loaded + cached | Wydajnosc przy duzej liczbie kategorii |

### 7.2 Ryzyka i Mitygacje

| Ryzyko | Prawdopodobienstwo | Mitygacja |
|--------|-------------------|-----------|
| Duza liczba kategorii (>1000) | Srednie | Lazy loading + pagination drzewa |
| Konflikt przy usuwaniu kategorii | Niskie | Soft-delete + walidacja przed usunieciem |
| Import wariantow bez atrybutow | Wysokie | Automatyczne tworzenie brakujacych atrybutow |

### 7.3 Szacowany Czas Implementacji

| Faza | Czas | Opis |
|------|------|------|
| FAZA 1: CategoryComparisonService | 6-8h | Logika biznesowa porownania |
| FAZA 2: CategoryComparisonModal | 8-10h | Komponent Livewire + traity |
| FAZA 3: Widoki Blade | 4-6h | UI drzewka i formularzy |
| FAZA 4: Import Wariantow | 6-8h | VariantImportService + integracja |
| FAZA 5: Testy + polish | 4-6h | Testy jednostkowe, UI polish |
| **TOTAL** | **28-38h** | |

---

## WYKONANE PRACE

- Analiza istniejacego kodu (CategoryPreviewModal, CategoryMapper, BulkImportProducts)
- Zaprojektowanie struktury danych (CategoryComparisonNode, Summary, VariantConfig)
- Stworzenie diagramu komponentow i flow uzytkownika
- Zaplanowanie architektury Livewire (traity, serwisy)
- Okreslenie integracji z istniejacym kodem importu
- Przygotowanie listy plikow do utworzenia/modyfikacji

## PROBLEMY/BLOKERY

- Brak - architektura gotowa do implementacji

## NASTEPNE KROKI

1. Utworzenie planu projektu ETAP_07d_Import_Modal_Redesign.md
2. Implementacja CategoryComparisonService (FAZA 1)
3. Implementacja CategoryComparisonModal (FAZA 2)
4. Implementacja widokow Blade (FAZA 3)
5. Implementacja VariantImportService (FAZA 4)

## PLIKI

- [_AGENT_REPORTS/architect_IMPORT_MODAL_REDESIGN_ARCHITECTURE.md] - Ten raport
