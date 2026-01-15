# Plan: CSS Analyzer & Sync System dla Visual Description Editor

## Problem Statement
1. PPM może używać nieaktualnego/niekompletnego CSS z PrestaShop
2. Brak narzędzia do wykrywania brakujących klas CSS w opisach produktów
3. CSS/JS Editor nie pokazuje pełnej listy plików z PrestaShop
4. Obawa o rozsynchronizowanie stylów PPM <-> PrestaShop

## Current State Analysis

### Jak PPM obecnie pobiera CSS:
```
PrestaShopCssFetcher::getCssForPreview()
  → cache (60 min) LUB custom_css_url LUB FTP
  → TYLKO custom.css (NIE theme.css!)
```

### Co zawiera custom.css PrestaShop:
- Wszystkie klasy `pd-*` (pd-merit, pd-merits, pd-icon--wallet, etc.)
- Szary BG (.pd-merits--dividers .pd-merit { background: rgb(246,246,246) })
- Grid system, typography, icons

### Problem:
PPM może mieć **stary cache** custom.css lub **brakować niektórych klas** z theme.css

---

## Proposed Solution: 3-Phase Implementation

### FAZA 1: CSS Class Analyzer (Narzędzie diagnostyczne)
**Cel:** Wykrywanie brakujących definicji CSS dla klas używanych w opisach produktów

#### 1.1 Nowy Service: `CssClassAnalyzer`
**Lokalizacja:** `app/Services/VisualEditor/CssClassAnalyzer.php`

```php
class CssClassAnalyzer {
    /**
     * Extract all CSS class names from HTML
     */
    public function extractClassesFromHtml(string $html): array;

    /**
     * Extract all CSS selectors from CSS content
     */
    public function extractSelectorsFromCss(string $css): array;

    /**
     * Find classes used in HTML but not defined in CSS
     */
    public function findMissingClasses(string $html, string $css): array;

    /**
     * Analyze product description for missing CSS
     */
    public function analyzeProductDescription(int $productId, int $shopId): CssAnalysisResult;

    /**
     * Batch analyze all products for shop
     */
    public function analyzeShop(int $shopId): ShopCssAnalysisReport;
}
```

#### 1.2 UI w Visual Editor
- Przycisk "Analizuj CSS" obok przycisku CSS/JS
- Modal z wynikami:
  - Lista klas użytych w HTML
  - Lista klas ZDEFINIOWANYCH w CSS
  - Lista klas BRAKUJĄCYCH (czerwone)
  - Sugestie naprawy

### FAZA 2: Enhanced CSS/JS Editor
**Cel:** Pokazywać wszystkie pliki CSS/JS z PrestaShop

#### 2.1 Nowy Service: `PrestaShopAssetDiscovery`
**Lokalizacja:** `app/Services/VisualEditor/PrestaShopAssetDiscovery.php`

```php
class PrestaShopAssetDiscovery {
    /**
     * Fetch and parse HTML from PrestaShop product page
     * Extract all CSS/JS URLs from <link> and <script> tags
     */
    public function discoverAssets(PrestaShopShop $shop, ?int $productId = null): AssetList;

    /**
     * Categorize assets by type (theme, module, custom)
     */
    public function categorizeAssets(AssetList $assets): CategorizedAssets;

    /**
     * Get content of specific CSS file
     */
    public function fetchCssContent(string $url): ?string;
}
```

#### 2.2 Redesign CSS/JS Editor Modal
```
┌─────────────────────────────────────────────────────────────────┐
│ CSS/JS Editor - Test KAYO                              [X]      │
├─────────────────────────────────────────────────────────────────┤
│ [CSS] [JS] [Analiza] [Pliki PrestaShop]                         │
├─────────────────────────────────────────────────────────────────┤
│ 📁 Pliki CSS załadowane na PrestaShop:                          │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ ☑ theme.css (375KB) - Bootstrap, base                       │ │
│ │ ☑ custom.css (89KB) - pd-*, style produktów ← EDYTUJ        │ │
│ │ ☐ front.css (iqitreviews) (12KB)                            │ │
│ │ ☐ front.css (iqitmegamenu) (8KB)                            │ │
│ │ ... (24 plików)                                             │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ [Odśwież listę] [Pobierz wszystkie] [Sync do PPM]              │
├─────────────────────────────────────────────────────────────────┤
│ Edytor custom.css:                                              │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ .pd-merit {                                                  │ │
│ │   background: rgb(246, 246, 246);                           │ │
│ │   ...                                                        │ │
│ │ }                                                            │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ [Zapisz do PPM] [Deploy do PrestaShop] [Backup]                │
└─────────────────────────────────────────────────────────────────┘
```

### FAZA 3: CSS Sync System (Bidirectional)
**Cel:** Automatyczna synchronizacja CSS między PPM a PrestaShop

#### 3.1 Tabela `css_sync_log`
```sql
CREATE TABLE css_sync_log (
    id BIGINT PRIMARY KEY,
    shop_id BIGINT NOT NULL,
    direction ENUM('ppm_to_ps', 'ps_to_ppm'),
    css_type VARCHAR(50), -- 'custom.css', 'theme.css', etc.
    checksum_before VARCHAR(64),
    checksum_after VARCHAR(64),
    status ENUM('success', 'failed', 'conflict'),
    conflict_resolution TEXT NULL,
    synced_at TIMESTAMP,
    synced_by BIGINT NULL (FK users)
);
```

#### 3.2 Nowy Service: `CssSyncService`
```php
class CssSyncService {
    /**
     * Compare CSS versions (PPM cache vs PrestaShop live)
     */
    public function compareVersions(PrestaShopShop $shop): CssComparison;

    /**
     * Pull CSS from PrestaShop to PPM (force refresh)
     */
    public function pullFromPrestaShop(PrestaShopShop $shop): SyncResult;

    /**
     * Push CSS from PPM to PrestaShop (with backup)
     */
    public function pushToPrestaShop(PrestaShopShop $shop): SyncResult;

    /**
     * Detect conflicts (both sides changed)
     */
    public function detectConflicts(PrestaShopShop $shop): ?CssConflict;

    /**
     * Resolve conflict with merge strategy
     */
    public function resolveConflict(CssConflict $conflict, string $strategy): SyncResult;
}
```

#### 3.3 Auto-sync hooks
- Hook na zapis opisu produktu → sprawdź czy CSS wymaga sync
- Cron job co 6h → sprawdź czy PrestaShop CSS się zmienił
- Webhook (opcjonalnie) → PrestaShop module notyfikuje PPM o zmianach

---

## Implementation Priority

| Priorytet | Faza | Czas | Wartość |
|-----------|------|------|---------|
| 🔴 HIGH | 1.1 CssClassAnalyzer | 4h | Diagnostyka problemów |
| 🔴 HIGH | 1.2 UI "Analizuj CSS" | 2h | UX dla użytkownika |
| 🟡 MEDIUM | 2.1 PrestaShopAssetDiscovery | 3h | Lista plików CSS |
| 🟡 MEDIUM | 2.2 Redesign CSS/JS Editor | 4h | Pełna kontrola |
| 🟢 LOW | 3.1-3.3 CSS Sync System | 8h | Automatyzacja |

---

## Files to Create/Modify

### New Files:
- `app/Services/VisualEditor/CssClassAnalyzer.php`
- `app/Services/VisualEditor/PrestaShopAssetDiscovery.php`
- `app/Services/VisualEditor/CssSyncService.php`
- `app/Http/Livewire/Products/VisualDescription/CssAnalyzerModal.php`
- `resources/views/livewire/products/visual-description/partials/css-analyzer-modal.blade.php`
- `database/migrations/XXXX_create_css_sync_log_table.php`

### Modified Files:
- `app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php` - dodanie przycisków
- `resources/views/livewire/products/visual-description/visual-description-editor.blade.php` - UI
- `app/Http/Livewire/Products/VisualDescription/Traits/EditorCssJs.php` - rozszerzenie funkcji

---

## ⚡ IMMEDIATE FIX: IFRAME Background Color

**PROBLEM ZIDENTYFIKOWANY:**
- W PrestaShop: `#product .tabs` ma `background: #f6f6f6 !important`
- W PPM IFRAME: `body` ma domyślne białe tło
- Sekcje z `background: rgb(246,246,246)` wyglądają inaczej (szare na białym vs szare na szarym)

**ROZWIĄZANIE (1-liniowa zmiana):**

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/EditorPreview.php`
**Metoda:** `getIframeContent()`
**Linia:** ~377-383

```php
// PRZED:
body {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
}

// PO:
body {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: #f6f6f6; /* Symuluje #product .tabs z PrestaShop */
}
```

**EFEKT:** Preview w PPM będzie 1:1 z PrestaShop - sekcje `.pd-merit` "zleją się" z tłem tak jak w sklepie.

---

---

## FAZA 4: Analiza i Katalogowanie Bloków PrestaShop

**Cel:** Przeanalizować wszystkie istniejące sekcje HTML z PrestaShop i stworzyć katalog wzorców

### 4.1 Analiza istniejących sekcji

Przeanalizować opisy produktów z PrestaShop i zidentyfikować powtarzające się wzorce:

| Wzorzec | Klasy CSS | Struktura | Parametry |
|---------|-----------|-----------|-----------|
| **pd-intro** | `.pd-intro`, `.pd-base-grid` | H2 + tekst + ikony | heading, text, icons[] |
| **pd-merits** | `.pd-merits`, `.pd-merit` | Grid 3-4 kart z ikonami | items[], columns, dividers |
| **pd-pseudo-parallax** | `.pd-pseudo-parallax` | Fullwidth image + overlay | image, overlay_text, height |
| **pd-slider** / **splide** | `.pd-slider`, `.splide` | Karuzela obrazów/treści | slides[], perPage, autoplay |
| **pd-specification** | `.pd-specification` | Tabela parametrów | rows[], columns |
| **pd-cols--2/3** | `.pd-cols`, `.pd-cols--2` | Multi-column layout | columns[], ratio |
| **pd-footer** | `footer`, `.pd-footer` | Sekcja zamykająca | content, background |

### 4.2 Narzędzie do ekstrakcji wzorców

```php
class BlockPatternExtractor {
    /**
     * Analizuje HTML i identyfikuje wzorce sekcji
     */
    public function extractPatterns(string $html): array;

    /**
     * Grupuje podobne sekcje z wielu produktów
     */
    public function findCommonPatterns(array $products): PatternCatalog;

    /**
     * Generuje schemat bloku z wzorca HTML
     */
    public function generateBlockSchema(string $patternHtml): BlockSchema;
}
```

---

## FAZA 5: Konwersja na Indywidualne Bloki

**Cel:** Stworzyć pełnoprawne bloki z wizualną reprezentacją w edytorze

### 5.1 Nowe bloki do stworzenia (na podstawie analizy)

```
app/Services/VisualEditor/Blocks/PrestaShop/
├── PdIntroBlock.php           # Sekcja wprowadzająca (H2 + tekst + ikony)
├── PdMeritsBlock.php          # Lista zalet (karty z ikonami)
├── PdParallaxBlock.php        # Parallax image section
├── PdSliderBlock.php          # Karuzela (Splide)
├── PdSpecificationBlock.php   # Tabela specyfikacji
├── PdColumnsBlock.php         # Multi-column layout
├── PdFooterBlock.php          # Sekcja zamykająca
├── PdIconGridBlock.php        # Siatka ikon
└── PdHeroBlock.php            # Hero banner z tłem
```

### 5.2 Struktura nowego bloku (przykład: PdMeritsBlock)

```php
class PdMeritsBlock extends BaseBlock {
    public string $type = 'pd-merits';
    public string $name = 'Lista Zalet';
    public string $icon = 'heroicons-check-badge';
    public string $category = 'prestashop';

    // ⭐ KLUCZOWE: Wizualna reprezentacja w edytorze
    public function getPreviewHtml(): string {
        return <<<HTML
        <div class="block-preview pd-merits-preview">
            <div class="preview-icon">✓ ✓ ✓</div>
            <div class="preview-label">Lista Zalet</div>
            <div class="preview-hint">3-4 karty z ikonami</div>
        </div>
        HTML;
    }

    public function getSchema(): array {
        return [
            'content' => [
                'items' => [
                    'type' => 'repeater',
                    'label' => 'Zalety',
                    'fields' => [
                        'icon' => ['type' => 'icon-picker', 'label' => 'Ikona'],
                        'title' => ['type' => 'text', 'label' => 'Tytuł'],
                        'description' => ['type' => 'textarea', 'label' => 'Opis'],
                    ]
                ]
            ],
            'settings' => [
                'columns' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                'show_dividers' => ['type' => 'boolean', 'default' => true],
                'background' => ['type' => 'color', 'default' => '#f6f6f6'],
            ]
        ];
    }

    public function render(array $content, array $settings, array $children = []): string {
        // Generuje HTML zgodny z PrestaShop custom.css
        $items = $content['items'] ?? [];
        $cols = $settings['columns'] ?? 3;
        $dividers = $settings['show_dividers'] ? 'pd-merits--dividers' : '';

        $html = "<div class=\"pd-merits {$dividers}\">";
        foreach ($items as $item) {
            $html .= $this->renderMeritCard($item);
        }
        $html .= "</div>";

        return $html;
    }
}
```

### 5.3 Wizualna reprezentacja w Block Palette

**OBECNY STAN (problem):**
```
┌─────────────────────────┐
│ 🌐 Sekcja PrestaShop    │  ← Użytkownik NIE WIE co to jest!
└─────────────────────────┘
```

**DOCELOWY STAN (rozwiązanie):**
```
┌─────────────────────────┐
│ ✓✓✓                     │
│ Lista Zalet             │  ← Miniaturka + nazwa mówią co blok robi
│ 3-4 karty z ikonami     │
└─────────────────────────┘

┌─────────────────────────┐
│ ═══════════════════     │
│ Parallax                │
│ Fullwidth + overlay     │
└─────────────────────────┘

┌─────────────────────────┐
│ ◀ ● ● ● ▶               │
│ Slider                  │
│ Karuzela obrazów        │
└─────────────────────────┘
```

### 5.4 Migracja istniejących bloków

```php
class BlockMigrationService {
    /**
     * Konwertuje prestashop-section na odpowiedni blok
     */
    public function migrateBlock(array $prestashopSection): array {
        $sectionType = $prestashopSection['settings']['section_type'] ?? 'unknown';
        $html = $prestashopSection['content']['html'] ?? '';

        return match($sectionType) {
            'intro' => $this->convertToIntroBlock($html),
            'merits' => $this->convertToMeritsBlock($html),
            'parallax' => $this->convertToParallaxBlock($html),
            'slider' => $this->convertToSliderBlock($html),
            default => $prestashopSection, // Zachowaj oryginał
        };
    }

    /**
     * Parsuje HTML i ekstrahuje dane do struktury bloku
     */
    private function convertToMeritsBlock(string $html): array {
        // DOM parsing: znajdź .pd-merit elementy
        // Ekstrahuj: icon class, title, description
        // Zwróć strukturę bloku pd-merits
    }
}
```

---

## FAZA 6: UI Enhancements dla Block Editor

### 6.1 Block Palette z wizualnymi preview

```blade
{{-- Zamiast tylko ikona + nazwa --}}
<div class="block-palette-item" draggable="true">
    <div class="block-preview-thumbnail">
        {!! $block->getPreviewHtml() !!}
    </div>
    <div class="block-name">{{ $block->name }}</div>
    <div class="block-description">{{ $block->description }}</div>
</div>
```

### 6.2 Block Canvas z kontekstowym preview

```blade
{{-- Dla każdego bloku na canvas --}}
<div class="block-canvas-item" wire:key="block-{{ $index }}">
    {{-- Nagłówek z typem bloku --}}
    <div class="block-header">
        <span class="block-type-badge">{{ $block['type'] }}</span>
        <span class="block-name">{{ $this->getBlockName($block['type']) }}</span>
    </div>

    {{-- Live preview HTML --}}
    <div class="block-content-preview">
        {!! $this->renderBlockPreview($index) !!}
    </div>
</div>
```

### 6.3 Tooltips i opisy

Każdy blok powinien mieć:
- `name` - Krótka nazwa (np. "Lista Zalet")
- `description` - Opis funkcji (np. "Siatka 3-4 kart z ikonami i opisami")
- `previewHtml` - Wizualna miniaturka
- `helpUrl` - Link do dokumentacji (opcjonalnie)

---

---

## FAZA 7: Auto-Generator Bloków z HTML/CSS

**Cel:** Narzędzie w PPM do automatycznego tworzenia bloków z kodu prestashop-section

### 7.1 BlockAutoGenerator Service

```php
class BlockAutoGenerator {
    /**
     * Analizuje HTML i generuje pełną definicję bloku
     */
    public function generateFromHtml(string $html, string $css = ''): GeneratedBlock {
        // 1. Parsuj HTML (DOMDocument)
        // 2. Wykryj strukturę (root element, children, atrybuty)
        // 3. Ekstrahuj klasy CSS
        // 4. Mapuj klasy na właściwości (colors, spacing, layout)
        // 5. Generuj getSchema() z wykrytych pól
        // 6. Generuj render() który odtwarza HTML 1:1
    }

    /**
     * Wykrywa powtarzające się elementy (repeater fields)
     */
    public function detectRepeaterPatterns(DOMDocument $doc): array;

    /**
     * Mapuje klasy CSS na właściwości bloku
     */
    public function mapCssToProperties(array $classes, string $css): array;

    /**
     * Generuje kod PHP klasy bloku
     */
    public function generateBlockClassCode(GeneratedBlock $block): string;

    /**
     * Waliduje że wygenerowany blok renderuje 1:1 z oryginałem
     */
    public function validateRender(string $originalHtml, GeneratedBlock $block): ValidationResult;
}
```

### 7.2 UI: Block Generator Modal

```
┌─────────────────────────────────────────────────────────────────┐
│ 🔧 Auto-Generator Bloków                                   [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ 1. ŹRÓDŁO HTML                                                 │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ <div class="pd-merits pd-merits--dividers">                 │ │
│ │   <div class="pd-merit">                                    │ │
│ │     <span class="pd-icon pd-icon--wallet"></span>           │ │
│ │     <h4>Ekonomia</h4>                                       │ │
│ │     <p>Oszczędność paliwa...</p>                            │ │
│ │   </div>                                                    │ │
│ │   ...                                                       │ │
│ │ </div>                                                      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ [📥 Wczytaj z prestashop-section] [📋 Wklej HTML]              │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│ 2. ANALIZA (automatyczna)                                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ ✅ Wykryto strukturę: pd-merits (grid 3 kolumny)            │ │
│ │ ✅ Wykryto repeater: 3x .pd-merit                           │ │
│ │ ✅ Pola per item: icon, title (h4), description (p)         │ │
│ │ ✅ Klasy CSS: pd-merits, pd-merits--dividers, pd-merit      │ │
│ │ ✅ Warianty: --dividers, --no-bg                            │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│ 3. KONFIGURACJA BLOKU                                          │
│                                                                 │
│ Nazwa bloku:     [Lista Zalet (Merits)        ]                │
│ Typ (slug):      [pd-merits                   ]                │
│ Kategoria:       [▼ PrestaShop               ]                │
│ Ikona:           [🏆 check-badge             ]                │
│                                                                 │
│ Pola Content:                                                  │
│ ┌────────────────┬──────────────┬─────────────┐               │
│ │ Nazwa          │ Typ          │ Wymagane    │               │
│ ├────────────────┼──────────────┼─────────────┤               │
│ │ items          │ repeater     │ ✓           │               │
│ │ ├─ icon        │ icon-picker  │ ✓           │               │
│ │ ├─ title       │ text         │ ✓           │               │
│ │ └─ description │ textarea     │             │               │
│ └────────────────┴──────────────┴─────────────┘               │
│                                                                 │
│ Pola Settings:                                                 │
│ ┌────────────────┬──────────────┬─────────────┐               │
│ │ show_dividers  │ boolean      │ default: ✓  │               │
│ │ columns        │ select 2/3/4 │ default: 3  │               │
│ │ background     │ color        │ #f6f6f6     │               │
│ └────────────────┴──────────────┴─────────────┘               │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│ 4. PODGLĄD PORÓWNAWCZY                                         │
│ ┌──────────────────────┐  ┌──────────────────────┐            │
│ │   ORYGINAŁ (HTML)    │  │  WYGENEROWANY BLOK   │            │
│ │  ┌──┐ ┌──┐ ┌──┐     │  │  ┌──┐ ┌──┐ ┌──┐     │            │
│ │  │💰│ │🔧│ │📦│     │  │  │💰│ │🔧│ │📦│     │            │
│ │  └──┘ └──┘ └──┘     │  │  └──┘ └──┘ └──┘     │            │
│ │  Ekonomia  Serwis... │  │  Ekonomia  Serwis... │            │
│ └──────────────────────┘  └──────────────────────┘            │
│                                                                 │
│ Zgodność renderowania: ✅ 100%                                 │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│ [Generuj kod PHP] [💾 Zapisz blok] [Testuj]           [Anuluj] │
└─────────────────────────────────────────────────────────────────┘
```

### 7.3 Algorytm generowania

```php
class BlockAutoGenerator {
    public function generateFromHtml(string $html, string $css = ''): GeneratedBlock {
        $doc = new DOMDocument();
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // 1. ANALIZA STRUKTURY
        $root = $doc->documentElement;
        $rootClasses = $this->extractClasses($root);
        $blockType = $this->detectBlockType($rootClasses);

        // 2. WYKRYCIE REPEATERÓW
        $repeaters = $this->detectRepeaterPatterns($doc);
        // np. 3x .pd-merit → repeater "items"

        // 3. EKSTRAKCJA PÓL
        $fields = [];
        foreach ($repeaters as $repeater) {
            $fields[$repeater->name] = [
                'type' => 'repeater',
                'fields' => $this->extractFieldsFromElement($repeater->sample)
            ];
        }

        // 4. MAPOWANIE CSS → SETTINGS
        $settings = $this->mapCssToSettings($rootClasses, $css);
        // np. pd-merits--dividers → show_dividers: true

        // 5. GENEROWANIE RENDER()
        $renderTemplate = $this->generateRenderTemplate($doc, $repeaters);

        return new GeneratedBlock(
            type: $blockType,
            name: $this->humanizeName($blockType),
            schema: ['content' => $fields, 'settings' => $settings],
            renderTemplate: $renderTemplate,
            originalHtml: $html
        );
    }

    /**
     * Generuje metodę render() która odtwarza HTML 1:1
     */
    private function generateRenderTemplate(DOMDocument $doc, array $repeaters): string {
        // Zamienia statyczne elementy na placeholdery
        // Zamienia powtarzające się elementy na foreach
        // Zachowuje wszystkie klasy CSS i atrybuty

        return <<<'PHP'
        public function render(array $content, array $settings, array $children = []): string {
            $items = $content['items'] ?? [];
            $dividers = $settings['show_dividers'] ? 'pd-merits--dividers' : '';

            $html = "<div class=\"pd-merits {$dividers}\">";
            foreach ($items as $item) {
                $icon = htmlspecialchars($item['icon'] ?? '');
                $title = htmlspecialchars($item['title'] ?? '');
                $desc = htmlspecialchars($item['description'] ?? '');

                $html .= <<<ITEM
                <div class="pd-merit">
                    <span class="pd-icon {$icon}"></span>
                    <h4>{$title}</h4>
                    <p>{$desc}</p>
                </div>
                ITEM;
            }
            $html .= "</div>";

            return $html;
        }
        PHP;
    }
}
```

### 7.4 Walidacja 1:1 renderowania

```php
class RenderValidator {
    /**
     * Porównuje oryginalny HTML z wygenerowanym
     */
    public function compare(string $originalHtml, string $generatedHtml): ValidationResult {
        // 1. Normalizuj whitespace
        // 2. Parsuj oba jako DOM
        // 3. Porównaj strukturę (tagi, atrybuty, klasy)
        // 4. Porównaj teksty
        // 5. Zwróć % zgodności + różnice

        return new ValidationResult(
            match: 98.5,  // procent zgodności
            differences: [
                'line 5: class order differs (cosmetic)',
            ],
            isAcceptable: true  // >95% = OK
        );
    }

    /**
     * Wizualne porównanie (screenshot diff)
     */
    public function visualCompare(string $html1, string $html2): VisualDiff {
        // Renderuje oba w headless browser
        // Porównuje piksele
        // Zwraca diff image
    }
}
```

### 7.5 Zapis wygenerowanego bloku

```php
class BlockSaver {
    /**
     * Zapisuje wygenerowany blok jako plik PHP
     */
    public function saveAsPhpClass(GeneratedBlock $block): string {
        $className = Str::studly($block->type) . 'Block';
        $filePath = app_path("Services/VisualEditor/Blocks/Generated/{$className}.php");

        $code = $this->generatePhpCode($block);
        File::put($filePath, $code);

        // Auto-discovery BlockRegistry znajdzie nowy blok
        return $filePath;
    }

    /**
     * Zapisuje jako JSON (dla dynamicznych bloków)
     */
    public function saveAsJson(GeneratedBlock $block): void {
        $path = storage_path("app/visual-editor/blocks/{$block->type}.json");
        File::put($path, json_encode($block->toArray(), JSON_PRETTY_PRINT));
    }
}
```

---

## Implementation Priority (zaktualizowana)

| Priorytet | Faza | Czas | Wartość |
|-----------|------|------|---------|
| 🔴 **IMMEDIATE** | Fix tło IFRAME | 10min | Preview 1:1 |
| 🔴 HIGH | 4.1 Analiza wzorców | 4h | Katalog sekcji |
| 🔴 HIGH | 5.1-5.2 Nowe bloki | 8h | Reprezentatywne bloki |
| 🟡 MEDIUM | 5.3-5.4 Wizualne preview | 4h | UX edytora |
| 🟡 MEDIUM | 6.1-6.3 UI enhancements | 3h | Polish |
| 🔴 **HIGH** | 7.1-7.5 Auto-Generator | 12h | Automatyzacja tworzenia bloków |
| 🟢 LATER | 1-3 CSS Analyzer & Sync | 12h | Sync CSS |

---

## Files to Create (rozszerzone)

### IMMEDIATE FIX:
```
app/Http/Livewire/Products/VisualDescription/Traits/EditorPreview.php
  → Dodać: background-color: #f6f6f6; do body w getIframeContent()
```

### Nowe bloki (Faza 5):
```
app/Services/VisualEditor/Blocks/PrestaShop/
├── PdIntroBlock.php           # Sekcja intro
├── PdMeritsBlock.php          # Lista zalet
├── PdParallaxBlock.php        # Parallax image
├── PdSliderBlock.php          # Karuzela Splide
├── PdSpecificationBlock.php   # Tabela specyfikacji
├── PdColumnsBlock.php         # Multi-column
├── PdFooterBlock.php          # Footer section
├── PdIconGridBlock.php        # Siatka ikon
└── PdHeroBlock.php            # Hero banner
```

### Auto-Generator (Faza 7):
```
app/Services/VisualEditor/BlockGenerator/
├── BlockAutoGenerator.php     # Główny generator
├── HtmlAnalyzer.php           # Analiza struktury HTML
├── RepeaterDetector.php       # Wykrywanie powtórzeń
├── CssPropertyMapper.php      # Mapowanie CSS → settings
├── RenderTemplateBuilder.php  # Generowanie render()
├── RenderValidator.php        # Walidacja 1:1
├── BlockSaver.php             # Zapis jako PHP/JSON
└── GeneratedBlock.php         # DTO
```

### Serwisy pomocnicze:
```
app/Services/VisualEditor/
├── BlockPatternExtractor.php
├── BlockMigrationService.php
├── BlockPreviewGenerator.php
└── CssClassAnalyzer.php       # Faza 1
```

### UI Livewire components:
```
app/Http/Livewire/Products/VisualDescription/
├── BlockGeneratorModal.php    # Modal auto-generatora
└── CssAnalyzerModal.php       # Modal analizy CSS
```

### UI Blade templates:
```
resources/views/livewire/products/visual-description/partials/
├── block-palette.blade.php (update) - wizualne preview
├── block-canvas.blade.php (update) - nazwy bloków
├── block-preview-thumbnail.blade.php (new)
├── block-generator-modal.blade.php (new)
└── css-analyzer-modal.blade.php (new)
```

---

## Podsumowanie Planu

**7 FAZ implementacji:**

| Faza | Nazwa | Status |
|------|-------|--------|
| FIX | Tło IFRAME #f6f6f6 | 🔴 IMMEDIATE |
| 1-3 | CSS Analyzer & Sync | 🟢 LATER |
| 4 | Analiza wzorców | 🔴 HIGH |
| 5 | Indywidualne bloki | 🔴 HIGH |
| 6 | UI Enhancements | 🟡 MEDIUM |
| 7 | Auto-Generator bloków | 🔴 HIGH |

**Cel końcowy:**
- Preview 1:1 z PrestaShop ✓
- Reprezentatywne bloki zamiast "Sekcja PrestaShop" ✓
- Auto-generowanie nowych bloków z HTML/CSS ✓
- CSS sync PPM ↔ PrestaShop ✓
