# ANALIZA STRUKTURY BLOKOW PRESTASHOP - Visual Editor

**Agent:** architect
**Data:** 2025-12-17
**Zadanie:** Analiza wszystkich blokow "prestashop-specific" w projekcie PPM-CC-Laravel

---

## 1. PODSUMOWANIE WYKONAWCZE

### 1.1 Znalezione Bloki PrestaShop-Specific

| Nr | Typ Bloku | Klasa PHP | Kategoria | Opis |
|----|-----------|-----------|-----------|------|
| 1 | `pd-merits` | PdMeritsBlock | prestashop | Lista zalet z ikonami |
| 2 | `pd-slider` | PdSliderBlock | prestashop | Karuzela Splide.js |
| 3 | `pd-parallax` | PdParallaxBlock | prestashop | Fullwidth obraz z efektem parallax |
| 4 | `pd-specification` | PdSpecificationBlock | prestashop | Tabela danych technicznych |
| 5 | `pd-asset-list` | PdAssetListBlock | prestashop | Kluczowe parametry z jednostkami |
| 6 | `prestashop-section` | PrestashopSectionBlock | prestashop | Passthrough - oryginalny HTML |

### 1.2 Architektura Systemu

```
BlockRegistry (Singleton)
    |
    +-- Built-in Blocks (PHP classes)
    |     +-- Layout/ (HeroBanner, GridSection, TwoColumn, ThreeColumn, FullWidth)
    |     +-- Content/ (Heading, Text, FeatureCard, SpecTable, MeritList, InfoCard)
    |     +-- Media/ (Image, ImageGallery, VideoEmbed, ParallaxImage, PictureElement)
    |     +-- Interactive/ (Slider, Accordion, Tabs, CTAButton, RawHtml)
    |     +-- PrestaShop/ (6 blokow specyficznych)
    |
    +-- Dynamic Blocks (database per shop)
          +-- BlockDefinition model
          +-- DynamicBlock wrapper class
          +-- shop-custom category
```

---

## 2. SZCZEGOLOWA ANALIZA KAZDEGO BLOKU

### 2.1 PdMeritsBlock (Lista Zalet)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PdMeritsBlock.php`

#### HTML Output
```html
<div class="pd-merits pd-merits--dividers">
    <div class="pd-merit">
        <span class="pd-icon pd-icon--wallet"></span>
        <h4>Ekonomia</h4>
        <p>Oszczednosc paliwa...</p>
    </div>
    <!-- powtorzone dla kazdej zalety -->
</div>
```

#### Elementy Skladowe

| Element | Selektor CSS | Wlasciwosci | Kontrolka UI |
|---------|--------------|-------------|--------------|
| Kontener | `.pd-merits` | display: grid, grid-template-columns | - |
| Separator | `.pd-merits--dividers` | border modifiers | checkbox `show_dividers` |
| Kolumny | `.pd-merits--cols-{n}` | grid-column count | select `columns` (2,3,4) |
| Karta zalety | `.pd-merit` | flex column, padding | - (z repeatera) |
| Ikona | `.pd-icon.pd-icon--{type}` | width, height, SVG mask | select `icon` |
| Tytul | `h4` | font-weight: 700, font-size | text `title` |
| Opis | `p` | font-size, color | textarea `description` |

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `items` (repeater, min: 1, max: 6):
  - `icon` (select) - 12 dostepnych ikon pd-icon--*
  - `title` (text, required)
  - `description` (textarea)

**Settings:**
- `columns` (select: 2, 3, 4) - default: 3
- `show_dividers` (boolean) - default: true
- `background` (select: transparent, light, white)
- `text_align` (select: left, center, right)

#### Dostepne Ikony
```
pd-icon--wallet    : Portfel (ekonomia)
pd-icon--wrench    : Klucz (serwis)
pd-icon--shield    : Tarcza (bezpieczenstwo)
pd-icon--engine    : Silnik
pd-icon--terrain   : Teren
pd-icon--speed     : Predkosc
pd-icon--comfort   : Komfort
pd-icon--quality   : Jakosc
pd-icon--warranty  : Gwarancja
pd-icon--delivery  : Dostawa
pd-icon--support   : Wsparcie
pd-icon--check     : Ptaszek
```

---

### 2.2 PdSliderBlock (Karuzela Splide.js)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PdSliderBlock.php`

#### HTML Output
```html
<div class="pd-slider splide" data-splide='{"type":"loop","perPage":1,...}'>
    <div class="splide__track">
        <ul class="splide__list">
            <li class="splide__slide">
                <div class="pd-slider__item">
                    <img src="..." alt="..." class="pd-slider__image" loading="lazy">
                    <div class="pd-slider__content">
                        <h4 class="pd-slider__title">Tytul</h4>
                        <p class="pd-slider__description">Opis</p>
                    </div>
                </div>
            </li>
            <!-- kolejne slajdy -->
        </ul>
    </div>
</div>
```

#### Elementy Skladowe

| Element | Selektor CSS | Wlasciwosci | Kontrolka UI |
|---------|--------------|-------------|--------------|
| Kontener | `.pd-slider.splide` | grid layout, 100% width | - |
| Track | `.splide__track` | overflow: hidden | - |
| Lista | `.splide__list` | display: flex | - |
| Slajd | `.splide__slide` | flex-shrink: 0 | - (z repeatera) |
| Item wrapper | `.pd-slider__item` | position: relative | - |
| Obraz | `.pd-slider__image` | object-fit: cover, lazy | image picker |
| Tytul slajdu | `.pd-slider__title` | font-weight: 700 | text `title` |
| Opis slajdu | `.pd-slider__description` | - | textarea |
| Link wrapper | `.pd-slider__link` | display: block | url `link` |
| Strzalki | `.splide__arrows` | position: absolute | boolean `arrows` |
| Paginacja | `.splide__pagination` | dots | boolean `pagination` |

#### Typy Slajdow
- `image-text` - obraz + tekst obok/pod
- `image-only` - tylko obraz
- `text-only` - tylko tekst

#### Splide.js Config (data-splide JSON)
```json
{
    "type": "loop|slide",
    "perPage": 1-4,
    "perMove": 1,
    "gap": "0",
    "pagination": true,
    "arrows": true,
    "speed": 400,
    "autoplay": false,
    "interval": 5000,
    "pauseOnHover": true,
    "easing": "ease"
}
```

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `slides` (repeater, min: 1, max: 20):
  - `image` (image) - URL obrazu
  - `title` (text)
  - `description` (textarea)
  - `link` (url, opcjonalny)

**Settings:**
- `slide_type` (select: image-text, image-only, text-only)
- `perPage` (select: 1, 2, 3, 4) - slajdow na strone
- `autoplay` (boolean)
- `autoplay_interval` (number: 1000-15000ms)
- `loop` (boolean) - nieskonczone przewijanie
- `arrows` (boolean)
- `pagination` (boolean)
- `speed` (number: 100-2000ms) - szybkosc animacji

---

### 2.3 PdParallaxBlock (Efekt Parallax)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PdParallaxBlock.php`

#### HTML Output
```html
<div class="pd-pseudo-parallax" style="background-image: url(...); min-height: 500px;">
    <div class="pd-pseudo-parallax__overlay pd-pseudo-parallax__overlay--dark text-center pd-pseudo-parallax__overlay--center">
        <h3 class="pd-pseudo-parallax__title">Tytul</h3>
        <p class="pd-pseudo-parallax__subtitle">Podtytul</p>
        <a href="..." class="pd-pseudo-parallax__btn btn-primary">Przycisk</a>
    </div>
</div>
```

#### Elementy Skladowe

| Element | Selektor CSS | Wlasciwosci | Kontrolka UI |
|---------|--------------|-------------|--------------|
| Kontener | `.pd-pseudo-parallax` | grid, 100dvh, clip-path | - |
| Tlo | style `background-image` | url(), fixed | image picker |
| Wysokosc | style `min-height` | 300-600px, 100vh | select `height` |
| Nakladka | `.pd-pseudo-parallax__overlay` | rgba overlay | - |
| Kolor nakladki | `--dark/--light/--brand/--none` | background-color | select `overlay_color` |
| Przezroczystosc | - | opacity: 0-1 | range `overlay_opacity` |
| Pozycja tekstu | `--top/--center/--bottom` | align-items | select `text_position` |
| Wyrownanie | `.text-left/center/right` | text-align | select `text_align` |
| Tytul | `.pd-pseudo-parallax__title` | font-size: clamp() | text `title` |
| Podtytul | `.pd-pseudo-parallax__subtitle` | smaller font | text `subtitle` |
| Przycisk | `.pd-pseudo-parallax__btn` | brand button | text + url |

#### Klasy Nakladki
```css
.pd-pseudo-parallax__overlay--dark   /* rgba(0,0,0, opacity) */
.pd-pseudo-parallax__overlay--light  /* rgba(255,255,255, opacity) */
.pd-pseudo-parallax__overlay--brand  /* rgba(239,130,72, opacity) - KAYO orange */
.pd-pseudo-parallax__overlay--none   /* transparent */
```

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `background_image` (image, required)
- `title` (text)
- `subtitle` (text)
- `button_text` (text)
- `button_url` (url)

**Settings:**
- `height` (select: 300px, 400px, 500px, 600px, 100vh)
- `overlay_color` (select: dark, light, brand, none)
- `overlay_opacity` (range: 0-1, step: 0.1)
- `text_align` (select: left, center, right)
- `text_position` (select: top, center, bottom)

---

### 2.4 PdSpecificationBlock (Tabela Specyfikacji)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PdSpecificationBlock.php`

#### HTML Output (Single Column)
```html
<div class="pd-specification pd-specification--striped">
    <h3 class="pd-specification__title">Dane techniczne</h3>
    <table class="pd-specification__table">
        <tbody>
            <tr>
                <td class="pd-specification__label">Silnik</td>
                <td class="pd-specification__value">200cm3</td>
            </tr>
            <!-- kolejne wiersze -->
        </tbody>
    </table>
</div>
```

#### HTML Output (Two Column)
```html
<div class="pd-specification pd-specification--two-column pd-specification--striped">
    <h3 class="pd-specification__title">Dane techniczne</h3>
    <table class="pd-specification__table pd-specification__table--wide">
        <tbody>
            <tr>
                <td class="pd-specification__label">Silnik</td>
                <td class="pd-specification__value">200cm3</td>
                <td class="pd-specification__label">Moc</td>
                <td class="pd-specification__value">15 KM</td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Elementy Skladowe

| Element | Selektor CSS | Wlasciwosci | Kontrolka UI |
|---------|--------------|-------------|--------------|
| Kontener | `.pd-specification` | padding, background | - |
| Layout | `--single/--two-column` | table width | select `layout` |
| Zebra | `--striped` | tr:nth-child(odd) bg | boolean `striped` |
| Ramki | `--bordered` | border on cells | boolean `bordered` |
| Kompakt | `--compact` | reduced padding | boolean `compact` |
| Tytul | `.pd-specification__title` | h3, color: brand | text `title` |
| Tabela | `.pd-specification__table` | width: 100% | - |
| Etykieta | `.pd-specification__label` | font-weight: 400, uppercase | text (w repeaterze) |
| Wartosc | `.pd-specification__value` | font-weight: 600 | text (w repeaterze) |

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `title` (text, default: "Dane techniczne")
- `rows` (repeater, min: 1, max: 50):
  - `label` (text, required) - nazwa parametru
  - `value` (text, required) - wartosc

**Settings:**
- `layout` (select: single, two-column)
- `show_title` (boolean, default: true)
- `striped` (boolean, default: true) - zebra striping
- `bordered` (boolean, default: false)
- `compact` (boolean, default: false)

---

### 2.5 PdAssetListBlock (Lista Parametrow)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PdAssetListBlock.php`

#### HTML Output
```html
<div class="pd-asset-list pd-asset-list--horizontal pd-asset-list--medium pd-asset-list--centered">
    <div class="pd-asset">
        <span class="pd-asset__icon pd-icon pd-icon--engine"></span>
        <span class="pd-asset__value">200</span>
        <span class="pd-asset__unit">cm3</span>
        <span class="pd-asset__label">Pojemnosc</span>
    </div>
    <!-- kolejne parametry -->
</div>
```

#### Elementy Skladowe

| Element | Selektor CSS | Wlasciwosci | Kontrolka UI |
|---------|--------------|-------------|--------------|
| Kontener | `.pd-asset-list` | display: flex/grid | - |
| Layout | `--horizontal/--vertical/--grid` | flex-direction, grid | select `layout` |
| Kolumny | `--cols-{n}` | grid-template-columns | select `columns` |
| Rozmiar | `--small/--medium/--large` | font-size, padding | select `size` |
| Separatory | `--dividers` | border-left | boolean `show_dividers` |
| Centrowanie | `--centered` | text-align: center | boolean `center_text` |
| Parametr | `.pd-asset` | flex column | - (z repeatera) |
| Ikona | `.pd-asset__icon.pd-icon` | SVG mask | select `icon` |
| Wartosc | `.pd-asset__value` | font-size: 2em, bold | text `value` |
| Jednostka | `.pd-asset__unit` | font-size: smaller | text `unit` |
| Etykieta | `.pd-asset__label` | uppercase, muted | text `label` |

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `assets` (repeater, min: 1, max: 8):
  - `value` (text, required) - np. "200", "R-N-F"
  - `unit` (text, opcjonalne) - np. "cm3", "KM"
  - `label` (text, required) - np. "Pojemnosc"
  - `icon` (select, opcjonalne) - pd-icon--* classes

**Settings:**
- `layout` (select: horizontal, vertical, grid)
- `columns` (select: 2, 3, 4, 5) - tylko dla grid
- `size` (select: small, medium, large)
- `show_dividers` (boolean, default: false)
- `center_text` (boolean, default: true)

---

### 2.6 PrestashopSectionBlock (Passthrough)

**Plik:** `app/Services/VisualEditor/Blocks/PrestaShop/PrestashopSectionBlock.php`

#### HTML Output
```html
<!-- Zwraca DOKLADNIE to co jest w $content['html'] - bez modyfikacji -->
```

#### Cel
Blok "passthrough" - przechowuje i renderuje oryginalny HTML z PrestaShop BEZ ZADNYCH modyfikacji.
Uzywany podczas importu opisow produktow z PrestaShop, gdy nie ma dedykowanego bloku.

#### Wykrywane Typy Sekcji
```php
$sectionLabels = [
    'intro'         => 'Intro (naglowek produktu)',
    'parallax'      => 'Parallax (obraz)',
    'slider'        => 'Slider (karuzela)',
    'merits'        => 'Zalety (lista)',
    'specification' => 'Specyfikacja',
    'more-links'    => 'Linki',
    'footer'        => 'Stopka',
    'block'         => 'Blok ogolny',
    'cover'         => 'Cover (zdjecie glowne)',
    'asset-list'    => 'Lista cech',
    'where-2-ride'  => 'Gdzie jezdzic',
];
```

#### Detekcja Typu (z klas CSS)
```php
str_contains($classes, 'pd-intro')           -> 'intro'
str_contains($classes, 'pd-pseudo-parallax') -> 'parallax'
str_contains($classes, 'pd-slider|splide')   -> 'slider'
str_contains($classes, 'pd-merits')          -> 'merits'
str_contains($classes, 'pd-specification')   -> 'specification'
str_contains($classes, 'pd-more-links')      -> 'more-links'
str_contains($classes, 'pd-cover')           -> 'cover'
str_contains($classes, 'pd-asset-list')      -> 'asset-list'
str_contains($classes, 'pd-where-2-ride')    -> 'where-2-ride'
$tagName === 'footer'                        -> 'footer'
$tagName === 'aside'                         -> 'more-links'
default                                      -> 'block'
```

#### Schema (Kontrolki Edytora)

**Content Fields:**
- `html` (code, language: html, rows: 20)

**Settings (readonly):**
- `section_type` (select, readonly)
- `original_tag` (text, readonly)
- `original_classes` (text, readonly)

---

## 3. WSPOLNE ELEMENTY I WZORCE

### 3.1 Paleta Kolorow (KAYO)

| Nazwa | Hex | Uzycie |
|-------|-----|--------|
| Primary Orange | `#ef8248` | Glowny kolor marki, tla |
| Accent Orange | `#eb5e20` | Linki, hover, underlines |
| Dark Orange | `#dd4819` | Naglowki, podkreslenia |
| Light Orange | `#f5ad7c` | Separatory, soft accents |
| Black | `#000000` | Ciemne sekcje |
| White | `#ffffff` | Tekst na ciemnym |
| Gray Light | `#f6f6f6` | Tla |
| Gray Medium | `#d1d1d1` | Ramki, separatory |

### 3.2 Typografia

**Font:** Montserrat (Google Fonts)

| Element | Font Size | Font Weight |
|---------|-----------|-------------|
| Model Name | clamp(2rem, 2rem + 2vw, 3.5rem) | 800 |
| Block Heading | clamp(1.75rem, 1.125rem + 2vw, 3rem) | 700 |
| Slide Title | 28px | 700 |
| Body | 16px | 400 |
| Secondary | 0.5em of parent | 400 |

### 3.3 System Ikon (pd-icon--)

Wszystkie ikony sa definiowane jako CSS background z SVG mask:

```css
.pd-icon {
    display: inline-block;
    width: 2.5rem;
    height: 2.5rem;
    background-color: currentColor;
    mask-size: contain;
    mask-repeat: no-repeat;
    mask-position: center;
}

.pd-icon--wallet {
    mask-image: url('/icons/wallet.svg');
}
```

### 3.4 CSS Grid System

```css
.product-description .rte-content, .pd-base-grid {
    display: grid;
    grid-template-columns:
        [row-start] minmax(var(--inline-padding, 1rem), 1fr)
        [block-start] minmax(0, var(--block-breakout))
        [text-start] min(var(--max-text-width), 100% - 2 * var(--inline-padding))
        [text-end] minmax(0, var(--block-breakout))
        [block-end] minmax(var(--inline-padding, 1rem), 1fr)
        [row-end];
}

/* Named grid areas */
grid-column: row;    /* Full width */
grid-column: block;  /* Content + margins */
grid-column: text;   /* Main text column */
```

### 3.5 Responsywne Breakpointy

| Nazwa | Width | Zastosowanie |
|-------|-------|--------------|
| sm | 500px | Asset list flex wrap |
| md | 680px | More links, merits grid |
| lg | 760px | Spec grid 2 columns |
| xl | 960px | Slide side-by-side |
| xxl | 1024px | Merits 4 columns |

---

## 4. SYSTEM DYNAMICZNYCH BLOKOW

### 4.1 Architektura

```
Admin tworzy blok z prestashop-section
    |
    v
BlockAutoGenerator analizuje HTML
    |
    +-- Wykrywa CSS classes
    +-- Identyfikuje pola tresci
    +-- Generuje schema
    +-- Tworzy render_template
    |
    v
BlockDefinition (w bazie danych)
    |
    v
DynamicBlock (wrapper runtime)
    |
    v
BlockRegistry laduje per shop
```

### 4.2 Tabela block_definitions

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | bigint | PK |
| shop_id | FK | prestashop_shops |
| type | string(100) | Unikalny slug per shop |
| name | string(255) | Display name |
| category | string(100) | shop-custom |
| icon | string(100) | heroicons-* |
| description | text | Opis |
| schema | json | Content + settings fields |
| render_template | longText | Blade-like template |
| css_classes | json | Required CSS |
| sample_html | longText | Original HTML |
| is_active | boolean | Status |
| usage_count | int | Ile razy uzyty |
| created_by | FK | users |
| updated_by | FK | users |
| timestamps | - | created_at, updated_at |

### 4.3 DynamicBlock Rendering

```php
// W DynamicBlock::render()
public function render(array $content, array $settings, array $children = []): string
{
    $mergedSettings = $this->mergeSettings($settings);

    // Increment usage (async)
    defer(fn () => $this->definition->incrementUsage());

    // Delegate to BlockDefinition
    return $this->definition->render($content, $mergedSettings);
}
```

### 4.4 Template Syntax

```blade
<!-- Zmienne tresci -->
{{ $content.title }}
{{ $content.items.0.icon }}

<!-- Zmienne ustawien -->
{{ $settings.columns }}
{{ $settings.show_dividers ? 'pd-merits--dividers' : '' }}

<!-- Petle -->
@foreach($content.items as $item)
    <div class="pd-merit">
        <span class="pd-icon {{ $item.icon }}"></span>
        <h4>{{ $item.title }}</h4>
    </div>
@endforeach
```

---

## 5. KONTROLKI EDYTORA - TYPY POL

### 5.1 Typy Pol Content

| Typ | Renderowanie | Opis |
|-----|--------------|------|
| `text` | `<input type="text">` | Pojedyncza linia tekstu |
| `textarea` | `<textarea>` | Wieloliniowy tekst |
| `richtext` | WYSIWYG | HTML z formatowaniem |
| `code` | CodeMirror | Kod HTML/CSS/JS |
| `image` | Image picker | URL + upload |
| `url` | `<input type="url">` | Link |
| `repeater` | Dynamiczna lista | Zagniezdzone pola |

### 5.2 Typy Pol Settings

| Typ | Renderowanie | Opis |
|-----|--------------|------|
| `select` | `<select>` | Lista opcji |
| `boolean` | `<input type="checkbox">` | Tak/Nie |
| `number` | `<input type="number">` | Liczba z min/max |
| `range` | `<input type="range">` | Slider |
| `color` | Color picker | Kolor hex/rgba |
| `text` | `<input type="text">` | Dowolny tekst |
| `icon` | Icon picker | Wybor ikony |

### 5.3 Pole Warunkowe (condition)

```php
'autoplay_interval' => [
    'type' => 'number',
    'label' => 'Interwal (ms)',
    'condition' => ['autoplay' => true], // Widoczne tylko gdy autoplay = true
]
```

---

## 6. WYMAGANE PLIKI CSS

### 6.1 PrestaShop custom.css

Lokalizacja na serwerze KAYO:
```
/themes/warehouse/assets/css/custom.css
```

Zawiera wszystkie klasy `pd-*` (~150 regul CSS)

### 6.2 Zewnetrzne Zaleznosci

| Biblioteka | CDN URL | Uzycie |
|------------|---------|--------|
| Google Fonts Montserrat | `fonts.googleapis.com/css?family=Montserrat:400,700` | Typografia |
| Splide.js CSS | `cdn.jsdelivr.net/npm/@splidejs/splide/dist/css/splide.min.css` | Slider |
| Splide.js JS | `cdn.jsdelivr.net/npm/@splidejs/splide/dist/js/splide.min.js` | Slider |

### 6.3 Ikony SVG

```
https://mm.mpptrade.pl/ps-themes/kayo/icons/wallet.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/tick.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/gift.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/package.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/pin.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/globe.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/engine.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/terrain.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/speed.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/comfort.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/quality.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/warranty.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/delivery.svg
https://mm.mpptrade.pl/ps-themes/kayo/icons/support.svg
```

---

## 7. MAPA Z-INDEX

| Warstwa | Z-index | Elementy |
|---------|---------|----------|
| Base | 0 | Normalna tresc |
| Parallax BG | -1 | `.pd-pseudo-parallax__img` (fixed) |
| Overlay | 1 | `.pd-pseudo-parallax__overlay` |
| Slider arrows | 10 | `.splide__arrows` |
| Modal | 100 | Block generator modal |

---

## 8. REKOMENDACJE

### 8.1 Dla Nowych Blokow

1. **Wzoruj sie na istniejacych** - PdMeritsBlock to dobry przyklad struktury
2. **Uzywaj repeatera** dla list elementow (max 6-8 itemow)
3. **Definiuj defaultSettings** z sensownymi wartosciami
4. **Dodaj getPreview()** dla palety blokow
5. **Schema musi zawierac** typy, labels, defaults

### 8.2 Dla CSS

1. **Scoping CSS** - prefixuj klasy per sklep (`.ppm-preview-kayo .pd-*`)
2. **CSS Variables** - uzywaj dla kolorow i rozmiarow
3. **Mobile-first** - responsywne breakpointy
4. **Font loading** - preload Montserrat

### 8.3 Dla Importu z PrestaShop

1. **Passthrough dla nieznanego** - uzywaj prestashop-section
2. **Nie modyfikuj HTML** - import 1:1
3. **Block Generator** - konwertuj prestashop-section na dedykowane bloki

---

## 9. PLIKI ZRODLOWE

```
app/Services/VisualEditor/Blocks/PrestaShop/
    PdMeritsBlock.php
    PdSliderBlock.php
    PdParallaxBlock.php
    PdSpecificationBlock.php
    PdAssetListBlock.php
    PrestashopSectionBlock.php

app/Services/VisualEditor/
    BlockRegistry.php
    HtmlToBlocksParser.php
    Blocks/BaseBlock.php
    Blocks/DynamicBlock.php

database/migrations/
    2025_12_17_100002_create_block_definitions_table.php

_AGENT_REPORTS/
    architect_VISUAL_EDITOR_CSS_DEEP_ANALYSIS.md
```

---

**Raport wygenerowany:** 2025-12-17
**Agent:** architect
**Status:** ANALIZA ZAKONCZONA
