# RAPORT: Analiza Wzorców Bloków PrestaShop

**Data**: 2025-12-16
**FAZA**: 4 - Analiza wzorców sekcji z PrestaShop
**Status**: UKOŃCZONA

---

## 1. OBECNA ARCHITEKTURA

### 1.1 BlockRegistry - Zarejestrowane bloki (22)

| Kategoria | Bloki |
|-----------|-------|
| **layout** | HeroBannerBlock, GridSectionBlock, FullWidthBlock, TwoColumnBlock, ThreeColumnBlock |
| **content** | HeadingBlock, TextBlock, FeatureCardBlock, SpecTableBlock, **MeritListBlock**, InfoCardBlock |
| **media** | ImageBlock, ImageGalleryBlock, VideoEmbedBlock, ParallaxImageBlock, PictureElementBlock |
| **interactive** | SliderBlock, AccordionBlock, TabsBlock, CTAButtonBlock, RawHtmlBlock |
| **prestashop** | **PrestashopSectionBlock** (passthrough) |

### 1.2 PrestashopSectionBlock - Problem

```php
// OBECNE: Passthrough - renderuje HTML bez modyfikacji
public function render(array $content, array $settings, array $children = []): string {
    return $content['html'] ?? '';  // ← NIE reprezentuje co robi!
}
```

**Problem**: Użytkownik widzi "Sekcja PrestaShop" bez informacji co blok zawiera.

---

## 2. ZIDENTYFIKOWANE TYPY SEKCJI

### 2.1 Wykryte przez PrestashopSectionBlock::detectSectionType()

| Typ | Klasa CSS | Opis |
|-----|-----------|------|
| `intro` | `pd-intro` | Nagłówek produktu z tekstem wprowadzającym |
| `parallax` | `pd-pseudo-parallax` | Fullwidth image z efektem parallax |
| `slider` | `pd-slider`, `splide` | Karuzela (Splide.js) |
| `merits` | `pd-merits` | Lista zalet z ikonami |
| `specification` | `pd-specification` | Tabela specyfikacji technicznych |
| `more-links` | `pd-more-links` | Linki do dodatkowych informacji |
| `cover` | `pd-cover` | Główne zdjęcie produktu |
| `asset-list` | `pd-asset-list` | Lista cech (np. 200cm³, R-N-F) |
| `where-2-ride` | `pd-where-2-ride` | Sekcja "Gdzie jeździć" |
| `footer` | tag `footer` | Stopka opisu |
| `block` | default | Blok ogólny |

### 2.2 Dodatkowe wzorce z HtmlToBlocksParser

| Wzorzec | Klasy CSS | Przeznaczenie |
|---------|-----------|---------------|
| pd-* | `pd-*` | Wszystkie klasy PrestaShop Description |
| blok-* | `blok-*` | Namespace Pitgang |
| splide | `splide` | Slider library |
| grid-row | `grid-row` | Layout rows |
| bg-brand | `bg-brand` | Brand background (orange) |
| bg-neutral | `bg-neutral` | Neutral background |

---

## 3. ANALIZA STRUKTURY HTML

### 3.1 pd-intro (Sekcja Intro)

```html
<div class="pd-intro pd-base-grid">
    <h2>KAYO S200</h2>
    <p class="pd-intro__subtitle">Sport UTV dla wymagających</p>
    <div class="pd-intro__icons">
        <span class="pd-icon pd-icon--engine"></span>
        <span class="pd-icon pd-icon--terrain"></span>
    </div>
</div>
```

**Parametry bloku**:
- `heading`: string (H2)
- `subtitle`: string
- `icons`: array of icon identifiers

---

### 3.2 pd-merits (Lista Zalet)

```html
<div class="pd-merits pd-merits--dividers">
    <div class="pd-merit">
        <span class="pd-icon pd-icon--wallet"></span>
        <h4>Ekonomia</h4>
        <p>Oszczędność paliwa dzięki...</p>
    </div>
    <div class="pd-merit">
        <span class="pd-icon pd-icon--wrench"></span>
        <h4>Serwis</h4>
        <p>Części zamienne dostępne...</p>
    </div>
    <!-- ... więcej pd-merit -->
</div>
```

**Parametry bloku**:
- `items`: array of {icon, title, description}
- `show_dividers`: boolean (klasa `--dividers`)
- `columns`: 2|3|4

---

### 3.3 pd-pseudo-parallax (Parallax Image)

```html
<div class="pd-pseudo-parallax" style="background-image: url(...)">
    <div class="pd-pseudo-parallax__overlay">
        <h3>Gdzie można poczuć pełnię możliwości</h3>
        <p>KAYO S200</p>
    </div>
</div>
```

**Parametry bloku**:
- `background_image`: URL
- `overlay_text`: string
- `overlay_subtitle`: string
- `height`: CSS value (np. `500px`, `100vh`)

---

### 3.4 pd-slider / splide (Karuzela)

```html
<div class="pd-slider splide">
    <div class="splide__track">
        <ul class="splide__list">
            <li class="splide__slide">
                <div class="pd-slider__item">
                    <img src="..." alt="...">
                    <h4>Tytuł slajdu</h4>
                    <p>Opis slajdu</p>
                </div>
            </li>
            <!-- więcej slajdów -->
        </ul>
    </div>
</div>
```

**Parametry bloku**:
- `slides`: array of {image, title, description}
- `perPage`: 1|2|3
- `autoplay`: boolean
- `loop`: boolean
- `arrows`: boolean
- `pagination`: boolean

---

### 3.5 pd-specification (Tabela Specyfikacji)

```html
<div class="pd-specification">
    <h3>Dane techniczne</h3>
    <table class="pd-specification__table">
        <tr>
            <td>Silnik</td>
            <td>200cm³, 4T, 1 cylinder</td>
        </tr>
        <tr>
            <td>Moc</td>
            <td>15 KM</td>
        </tr>
    </table>
</div>
```

**Parametry bloku**:
- `title`: string
- `rows`: array of {label, value}
- `columns`: 1|2 (dla multi-column)

---

### 3.6 pd-asset-list (Lista Cech)

```html
<div class="pd-asset-list">
    <div class="pd-asset">
        <span class="pd-asset__value">200</span>
        <span class="pd-asset__unit">cm³</span>
        <span class="pd-asset__label">Pojemność</span>
    </div>
    <div class="pd-asset">
        <span class="pd-asset__value">R-N-F</span>
        <span class="pd-asset__label">Skrzynia</span>
    </div>
</div>
```

**Parametry bloku**:
- `assets`: array of {value, unit, label}
- `layout`: horizontal|vertical|grid

---

### 3.7 pd-cover (Cover Image)

```html
<div class="pd-cover">
    <picture>
        <source srcset="image-desktop.webp" media="(min-width: 768px)">
        <img src="image-mobile.jpg" alt="...">
    </picture>
</div>
```

**Parametry bloku**:
- `desktop_image`: URL
- `mobile_image`: URL
- `alt`: string

---

### 3.8 footer (Stopka Opisu)

```html
<footer class="pd-base-grid">
    <div class="pd-footer__cta">
        <a href="..." class="btn-primary">Kup teraz</a>
        <a href="..." class="btn-secondary">Dowiedz się więcej</a>
    </div>
    <p class="pd-footer__disclaimer">* Zdjęcia poglądowe</p>
</footer>
```

**Parametry bloku**:
- `cta_buttons`: array of {label, url, style}
- `disclaimer`: string

---

## 4. MAPOWANIE NA NOWE BLOKI

### 4.1 Bloki do stworzenia (FAZA 5)

| Nowy Blok | Typ | Kategoria | Bazuje na |
|-----------|-----|-----------|-----------|
| `PdIntroBlock` | `pd-intro` | prestashop | pd-intro |
| `PdMeritsBlock` | `pd-merits` | prestashop | pd-merits |
| `PdParallaxBlock` | `pd-parallax` | prestashop | pd-pseudo-parallax |
| `PdSliderBlock` | `pd-slider` | prestashop | pd-slider, splide |
| `PdSpecificationBlock` | `pd-specification` | prestashop | pd-specification |
| `PdAssetListBlock` | `pd-asset-list` | prestashop | pd-asset-list |
| `PdCoverBlock` | `pd-cover` | prestashop | pd-cover |
| `PdFooterBlock` | `pd-footer` | prestashop | footer |
| `PdIconGridBlock` | `pd-icon-grid` | prestashop | ikony w gridzie |

### 4.2 Struktura nowego bloku (wzorzec)

```php
class PdMeritsBlock extends BaseBlock
{
    public string $type = 'pd-merits';
    public string $name = 'Lista Zalet';
    public string $icon = 'heroicons-check-badge';
    public string $category = 'prestashop';

    public array $defaultSettings = [
        'columns' => 3,
        'show_dividers' => true,
        'background' => '#f6f6f6',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        // Renderuje HTML zgodny z PrestaShop custom.css
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'items' => [
                    'type' => 'repeater',
                    'label' => 'Zalety',
                    'fields' => [
                        'icon' => ['type' => 'icon-picker', 'label' => 'Ikona'],
                        'title' => ['type' => 'text', 'label' => 'Tytuł'],
                        'description' => ['type' => 'textarea', 'label' => 'Opis'],
                    ],
                ],
            ],
            'settings' => [
                'columns' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                'show_dividers' => ['type' => 'boolean', 'default' => true],
            ],
        ];
    }

    // ⭐ KLUCZOWE: Wizualna reprezentacja w palecie bloków
    public function getPreview(): string
    {
        return <<<HTML
        <div class="block-preview pd-merits-preview">
            <div class="preview-icon">✓ ✓ ✓</div>
            <div class="preview-label">Lista Zalet</div>
            <div class="preview-hint">Karty z ikonami</div>
        </div>
        HTML;
    }
}
```

---

## 5. PLIKI DO UTWORZENIA (FAZA 5)

```
app/Services/VisualEditor/Blocks/PrestaShop/
├── PdIntroBlock.php
├── PdMeritsBlock.php
├── PdParallaxBlock.php
├── PdSliderBlock.php
├── PdSpecificationBlock.php
├── PdAssetListBlock.php
├── PdCoverBlock.php
├── PdFooterBlock.php
└── PdIconGridBlock.php
```

---

## 6. PRIORYTET IMPLEMENTACJI

| Priorytet | Blok | Powód |
|-----------|------|-------|
| 🔴 HIGH | PdMeritsBlock | Najczęściej używany |
| 🔴 HIGH | PdSliderBlock | Wymaga Splide.js |
| 🟡 MEDIUM | PdParallaxBlock | Efekt wizualny |
| 🟡 MEDIUM | PdSpecificationBlock | Tabela techniczna |
| 🟡 MEDIUM | PdAssetListBlock | Kluczowe parametry |
| 🟢 LOW | PdIntroBlock | Prosty tekst |
| 🟢 LOW | PdCoverBlock | Zdjęcie główne |
| 🟢 LOW | PdFooterBlock | Stopka |

---

## 7. NASTĘPNE KROKI

1. **FAZA 5**: Implementacja bloków PdMeritsBlock, PdSliderBlock, PdParallaxBlock
2. **FAZA 6**: UI enhancements - wizualne preview w palecie
3. **FAZA 7**: Auto-Generator bloków z HTML/CSS

---

**Raport utworzony przez**: architect agent
**Lokalizacja**: `_AGENT_REPORTS/architect_PRESTASHOP_BLOCK_PATTERNS_ANALYSIS.md`
