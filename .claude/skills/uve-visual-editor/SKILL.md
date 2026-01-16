# SKILL: UVE Visual Editor (CSS-First Architecture)

## Metadata
- **Version:** 2.11
- **Type:** domain
- **Enforcement:** require
- **Priority:** critical
- **Last Updated:** 2026-01-16

---

## Opis

Ten skill zawiera kompletna wiedze o UVE (Unified Visual Editor) - systemie edycji wizualnej opisow produktow dla PrestaShop. UVE uzywa architektury CSS-First, gdzie WSZYSTKIE style sa zapisywane jako klasy CSS, NIE jako inline styles.

---

## Kiedy Uzywac

- Praca nad edytorem wizualnym opisow produktow
- Modyfikacja stylow CSS dla PrestaShop
- Debugging problemow z renderowaniem opisow
- Integracja CSS z PrestaShop via FTP
- Migracja istniejacych opisow z inline styles

---

## KRYTYCZNE ZASADY (MANDATORY)

### 1. ZERO INLINE STYLES - BEZ KOMPROMISOW!

```html
<!-- ZABRONIONE -->
<h2 style="font-size: 56px;">...</h2>

<!-- WYMAGANE -->
<h2 class="uve-s7f3a2b1">...</h2>
```

### 2. CSS ISOLATION - SCOPED SELECTORS ONLY

```css
/* PRAWIDLOWO - scoped */
.uve-content .bg-brand { background-color: var(--uve-brand-color); }
.product-description .container { max-width: 100%; }
#product .tabs .nav-tabs { padding-inline: ...; }

/* ZABRONIONE - global leaks */
:root { --brand-color: ...; }       /* NIE! */
.container { max-width: 100%; }     /* NIE! */
.btn { ... }                        /* NIE! */
.breadcrumb { ... }                 /* NIE! */
```

### 3. SAFE SELECTOR PREFIXES

| Prefix | Status | Uzycie |
|--------|--------|--------|
| `.uve-content` | SAFE | Wrapper dla UVE content |
| `.uve-s*`, `.uve-e*` | SAFE | Klasy UVE |
| `.product-description` | SAFE | Kontener opisu |
| `#product-description` | SAFE | ID tab opisu |
| `#product .tabs` | SAFE | Scoped do product page |
| `:root` | FORBIDDEN | Global variables |
| `.container`, `.btn` | FORBIDDEN | Unscoped selectors |
| `.nav`, `.breadcrumb` | FORBIDDEN | Theme elements |

### 4. PER-STYLE HASH NAMING

```php
// Hash generowany z content stylow (nie element ID!)
$styles = ['font-size' => '56px', 'font-weight' => '800'];
ksort($styles);
$hash = substr(md5(json_encode($styles)), 0, 8);
$className = "uve-s{$hash}";  // uve-s7f3a2b1
```

**ZALETA:** Ten sam styl = ta sama klasa (reuse CSS, mniejszy plik)

---

## Glowne Komponenty

### CssSyncOrchestrator

**Plik:** `app/Services/VisualEditor/CssSyncOrchestrator.php`

**Kluczowe metody:**

| Metoda | Opis |
|--------|------|
| `syncProductDescription()` | Sync CSS do PrestaShop via FTP |
| `generateCssFromRulesV2()` | Generuje CSS z rules |
| `getLayoutFixCss()` | Base CSS z izolacja (v2.2) |
| `validateCssForLeaks()` | **KRYTYCZNE** - sprawdza przecieki |
| `fullReplaceWithMarkers()` | FULL REPLACE strategy |
| `rollbackCss()` | Rollback przy bledzie |

### UVE Traits

| Trait | Plik | Opis |
|-------|------|------|
| `UVE_CssClassGeneration` | `.../Traits/UVE_CssClassGeneration.php` | Generowanie klas CSS |
| `UVE_PropertyPanel` | `.../Traits/UVE_PropertyPanel.php` | UI edycji stylow |
| `UVE_BlockManagement` | `.../Traits/UVE_BlockManagement.php` | Zarzadzanie blokami |

---

## Tryby CSS

| Tryb | Warunek | Opis |
|------|---------|------|
| `external` | FTP OK | CSS w `/themes/{theme}/css/custom.css` |
| `pending` | Brak FTP | Blokada sync, ostrzezenie |

**USUNIETY:** `inline_style_block` - NIE JEST VALID OPTION!

---

## Sync Safety

### 1. Lock Mechanism

```php
$lock = Cache::lock('uve_css_sync_' . $shopId, 60);
if (!$lock->get()) {
    return ['error' => 'Sync in progress'];
}
```

### 2. Backup Before Sync

```php
$backup = $existingCss;
try {
    $this->uploadCss($shop, $mergedCss);
} catch (\Throwable $e) {
    $this->rollbackCss($shop, $backup);
}
```

### 3. FULL REPLACE (Not Append!)

CSS jest zastepowany w obrebie markerow:
```css
/* @uve-styles-start */
... nowe style (REPLACE) ...
/* @uve-styles-end */
```

### 4. FORCE FETCH FROM FTP (MANDATORY!) ⚠️

**KRYTYCZNE:** ZAWSZE pobieraj świeży CSS z FTP przed merge!

```php
// ❌ BŁĘDNIE - używa cache (może być stale!)
$fetchResult = $this->fetchExistingCssWithValidation($shop, false);

// ✅ PRAWIDŁOWO - zawsze świeży z FTP
$fetchResult = $this->fetchExistingCssWithValidation($shop, true);
```

**Dlaczego to ważne:**
- Cache może zawierać stary CSS (po ręcznym upload przez użytkownika)
- Stale cache powoduje utratę oryginalnych styli theme'u (`.btn-primary`, `:root`, etc.)
- **ROOT CAUSE** wielu problemów z "nadpisywaniem styli"

### 5. UNIFIED CSS MARKERS (MANDATORY!)

**WSZYSTKIE komponenty MUSZĄ używać TYCH SAMYCH markerów:**

```php
// CssRuleGenerator.php
private const SECTION_START = '/* @uve-styles-start */';
private const SECTION_END = '/* @uve-styles-end */';

// CssSyncOrchestrator.php
private const CSS_MARKER_START = '/* @uve-styles-start */';
private const CSS_MARKER_END = '/* @uve-styles-end */';
```

**⚠️ NIGDY różne markery między komponentami!**

| Komponent | Marker START | Marker END |
|-----------|--------------|------------|
| CssRuleGenerator | `/* @uve-styles-start */` | `/* @uve-styles-end */` |
| CssSyncOrchestrator | `/* @uve-styles-start */` | `/* @uve-styles-end */` |

### 6. CACHE UPDATE AFTER UPLOAD

**Po successful upload ZAWSZE aktualizuj cache:**

```php
// Po uploadCss()
$shop->update([
    'cached_custom_css' => $mergedCss,
    'css_last_fetched_at' => now(),
]);
```

**Zapobiega:** Kolejnym operacjom używającym stale cache.

---

## Diagnostyczne Logowanie (v2.3)

### Analiza Pobranego CSS

```php
Log::info('CssSyncOrchestrator: Fetched existing CSS analysis', [
    'shop_id' => $shopId,
    'size' => strlen($existingCss),
    'has_root_vars' => str_contains($existingCss, ':root'),
    'has_btn_primary' => str_contains($existingCss, '.btn-primary'),
    'has_uve_markers' => str_contains($existingCss, self::CSS_MARKER_START),
    'first_100_chars' => substr(trim($existingCss), 0, 100),
]);
```

### Analiza Merged CSS

```php
Log::info('CssSyncOrchestrator: Merged CSS analysis', [
    'shop_id' => $shopId,
    'existing_size' => strlen($existingCss),
    'generated_size' => strlen($generatedCss),
    'merged_size' => strlen($mergedCss),
    'merged_has_root_vars' => str_contains($mergedCss, ':root'),
    'merged_has_btn_primary' => str_contains($mergedCss, '.btn-primary'),
]);
```

### Weryfikacja w Logach

Po CSS Sync sprawdź logi:
```bash
tail -n 30 storage/logs/laravel.log | grep "CssSyncOrchestrator"
```

**Oczekiwany wynik dla prawidłowego sync:**
```
has_root_vars: true ✅
has_btn_primary: true ✅
has_uve_markers: false (przed sync) → true (po sync)
```

**Jeśli `has_root_vars: false` lub `has_btn_primary: false`** = PROBLEM! Cache stale lub błędny merge.

---

## CSS Validation

### validateCssForLeaks()

**ZAWSZE** waliduj CSS przed sync:

```php
$validation = $this->validateCssForLeaks($css);
if (!$validation['valid']) {
    Log::warning('CSS leak detected', $validation['leaks']);
    // BLOKUJ SYNC lub loguj warning
}
```

**Wykrywa:**
- `:root { ... }` - global variables
- `.container { ... }` - unscoped container
- `.btn*`, `.nav*`, `.breadcrumb*` - theme elements
- `body { }`, `html { }`, `* { }` - universal selectors

---

## Layout Fix CSS (v2.2)

Automatycznie dodawany przy kazdym sync:

```css
/* === UVE CSS ISOLATION LAYER v2.2 === */

/* Scoped variables (NOT :root!) */
.uve-content,
.product-description .rte-content {
  --uve-brand-color: #ef8248;
  --uve-max-content-width: 1300px;
}

/* Container fix - ONLY inside product description */
.product-description .container,
.uve-content .container {
  max-width: 100% !important;
}

/* Grid layout for product description */
#product-description.tab-pane.active {
  display: grid !important;
  grid-template-columns: ...;
}

/* Scoped brand background */
.uve-content .bg-brand,
.product-description .bg-brand {
  background-color: var(--uve-brand-color);
  grid-column: 1 / -1;
}

/* Nav tabs fix (warehouse theme) */
#product .tabs .nav-tabs {
  padding-inline: max(...) !important;
}
```

---

## Element Indexing Rules (v2.6) ⚠️

### KRYTYCZNE: GLOBAL indexing (FIX #10)

**UWAGA:** System używa GLOBAL indexing (jeden licznik dla wszystkich typów)!

Format `data-uve-id`: `block-{blockNum}-{type}-{globalIndex}`

```
block-6-heading-0 → pierwszy element w bloku (heading)
block-6-text-1    → drugi element w bloku (paragraph)
block-6-image-2   → trzeci element w bloku (img)
block-6-button-3  → czwarty element w bloku (button)
```

**Indeks jest GLOBALNY** - wszystkie typy elementów dzielą jeden licznik!

### Implementacja w markChildElements() i findElementInContext():

```php
// FIX #10: JEDEN globalny licznik dla wszystkich typów!
$elementIndex = 0;

foreach ($headings as $heading) {
    $heading->setAttribute('data-uve-id', "{$blockId}-heading-{$elementIndex}");
    $elementIndex++;  // 0, 1, 2...
}
foreach ($paragraphs as $p) {
    $p->setAttribute('data-uve-id', "{$blockId}-text-{$elementIndex}");
    $elementIndex++;  // kontynuuje: 3, 4, 5...
}
foreach ($images as $img) {
    $img->setAttribute('data-uve-id', "{$blockId}-image-{$elementIndex}");
    $elementIndex++;  // kontynuuje: 6, 7, 8...
}
```

### WYMAGANE:
- ✅ Ten sam globalny licznik w `markChildElements()` (UVE_Preview) i `findElementInContext()` (UVE_MediaPicker)

---

## XPath Unification Rules (v2.6) ⚠️

### KRYTYCZNE: XPath MUSI być IDENTYCZNY! (FIX #11)

**Problem:** Różne XPath queries w `injectEditableMarkers()` i `findElementByStructuralMatching()` powodują że bloki są różnie liczone!

### OBOWIĄZKOWY XPath dla visual blocks:

```php
// MUSI być IDENTYCZNY w obu miejscach!
$blockXPath = '//*[contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-cover")]';
```

### Pliki do synchronizacji:

| Plik | Metoda | Użycie |
|------|--------|--------|
| `UVE_Preview.php` | `injectEditableMarkers()` | Przypisuje data-uve-id |
| `UVE_MediaPicker.php` | `findElementByStructuralMatching()` | Szuka elementów |

### ZABRONIONE:
- ❌ `'//*[contains(@class, "pd-") and not(contains(@class, "__"))]'` - za szeroki!
- ❌ Różne XPath w różnych plikach

### WYMAGANE:
- ✅ Identyczny XPath w obu metodach

---

## Alpine wire:ignore Sync Rules (v2.6) ⚠️

### KRYTYCZNE: Dispatch event dla kontrolek z wire:ignore.self! (FIX #12)

**Problem:** Kontrolki Alpine z `wire:ignore.self` NIE są reinicjalizowane przy Livewire update!

### Symptom:
Kliknięcie na inny obrazek → Property Panel pokazuje stary URL (nie aktualizuje się)

### Przyczyna:
`image-settings.blade.php` ma `wire:ignore.self` → Alpine zachowuje stary state

### Rozwiązanie:
`onElementSelectedForPanel()` MUSI dispatch'ować event z nowym imageUrl:

```php
// UVE_PropertyPanel.php - onElementSelectedForPanel()
$imageUrl = $this->elementStyles['imageUrl'] ?? $this->elementStyles['src'] ?? null;
if ($imageUrl) {
    $this->dispatch('uve-image-url-updated', url: $imageUrl);
}
```

### Alpine listener (w kontrolce):

```javascript
init() {
    Livewire.on('uve-image-url-updated', (data) => {
        const newUrl = data?.url || data[0]?.url;
        if (newUrl !== undefined) {
            this.imageUrl = newUrl;
        }
    });
}
```

### WYMAGANE dla każdej kontrolki z wire:ignore.self:
- ✅ Dispatch Livewire event przy zmianie elementu
- ✅ Alpine listener w `init()` aktualizujący state

---

## Child Element Background Inheritance (v2.7) ⚠️

### KRYTYCZNE: Gradient z child elementu! (FIX #13d)

**Problem:** Niektóre bloki (np. `.pd-cover`) mają `background-image: none` na rodzicu, ale gradient jest zdefiniowany na child elemencie (np. `.pd-cover__picture`). Property Panel pokazywał pusty background zamiast rzeczywistego gradientu.

### Symptom:
Wybranie bloku `.pd-cover` → Background control w Property Panel jest pusty, mimo że wizualnie widać gradient.

### Przyczyna:
`getElementStyles()` odczytywał tylko style wybranego elementu, nie sprawdzając child elementów.

### Rozwiązanie:
W `getElementStyles()` (UVE_Preview.php) dodano logikę dziedziczenia gradientu z child elementów:

```javascript
// UVE_Preview.php - getElementStyles() (~linia 943)

// FIX #13d: For pd-cover blocks, check child elements for gradient background
if (el.classList && el.classList.contains('pd-cover')) {
    const pictureChild = el.querySelector('.pd-cover__picture');
    if (pictureChild) {
        const childComputed = window.getComputedStyle(pictureChild);
        const childBgImage = childComputed.backgroundImage;
        if (childBgImage && childBgImage !== 'none' && childBgImage.includes('gradient')) {
            styles.backgroundImage = childBgImage;
            styles.childBackgroundSource = '.pd-cover__picture';
        }
    }
}

// FIX #13d: Generic handler for any block with __picture child containing gradient
if (!styles.backgroundImage || styles.backgroundImage === 'none') {
    const pictureChild = el.querySelector('[class*="__picture"]');
    if (pictureChild) {
        const childComputed = window.getComputedStyle(pictureChild);
        const childBgImage = childComputed.backgroundImage;
        if (childBgImage && childBgImage !== 'none' && childBgImage.includes('gradient')) {
            styles.backgroundImage = childBgImage;
            styles.childBackgroundSource = pictureChild.className;
        }
    }
}
```

### Mapowanie kontrolek:
W `CssClassMappingDefinitions.php` dodano kontrolkę `background` dla `pd-cover`:

```php
'pd-cover' => [
    // FIX #13d: Added 'background' control - gradient inherited from .pd-cover__picture child
    'controls' => ['background', 'layout-flex', 'layout-grid'],
    'defaults' => [...],
],
```

### Dodatkowe informacje:
- `childBackgroundSource` - wskazuje z którego child elementu pochodzi gradient
- Generic handler dla `[class*="__picture"]` obsługuje inne bloki z podobnym wzorcem

### ⚠️ CACHE IFRAME:
Po zmianie `getElementStyles()` wymagany hard reload iframe (`window.location.reload(true)`) - browser cache może serwować starą wersję skryptu!

### WYMAGANE:
- ✅ Sprawdzaj child elementy `__picture` dla gradientów
- ✅ Dodaj kontrolkę `background` do bloków z child gradientami
- ✅ Hard reload po deploy zmian w iframe scripts

---

## Child Element Style Application (v2.10) ⚠️

### KRYTYCZNE: uveApplyStyles dla child elementów! (FIX #14f)

**Problem:** FIX #13d odczytuje gradient z child elementu i ustawia `childBackgroundSource`, ale `uveApplyStyles` aplikowało WSZYSTKIE style do parent elementu. Efekt: Property Panel pokazywał gradient, ale zmiany NIE aktualizowały canvas.

### Symptom:
1. Wybierz blok `.pd-cover` (który ma gradient na `.pd-cover__picture`)
2. Property Panel pokazuje gradient poprawnie
3. Zmień kąt gradientu w Property Panel
4. Canvas NIE aktualizuje się (nadal pokazuje stary gradient)

### Przyczyna:
`uveApplyStyles` ignorowało `childBackgroundSource` i aplikowało `background-image` do parent elementu, który ma `background-image: none`.

### Rozwiązanie:
W `uveApplyStyles` (unified-visual-editor.blade.php) dodano logikę aplikowania `background-*` stylów do child elementu:

```javascript
// unified-visual-editor.blade.php - window.uveApplyStyles()

// FIX #14f: Check if background styles should be applied to child element
const childBgSource = styles['childBackgroundSource'] || styles['child-background-source'];
let bgTargetElement = element;

if (childBgSource) {
    const childEl = element.querySelector(childBgSource);
    if (childEl) {
        bgTargetElement = childEl;
        console.log('[UVE Global] FIX #14f: Background will be applied to child:', childBgSource);
    }
}

// Background-related properties (to apply to bgTargetElement)
const bgProps = ['background-image', 'background-color', 'background-size',
                 'background-position', 'background-repeat', 'background-attachment'];

Object.entries(styles).forEach(([prop, value]) => {
    // Skip metadata properties
    if (prop === 'childBackgroundSource' || prop === 'child-background-source') return;

    const cssProp = camelToKebab(prop);

    // FIX #14f: Apply background props to child, others to parent
    const targetEl = bgProps.includes(cssProp) ? bgTargetElement : element;
    targetEl.style.setProperty(cssProp, value);
});
```

### Powiązanie z FIX #13d:
- **FIX #13d**: Odczytuje gradient z child elementu → ustawia `childBackgroundSource`
- **FIX #14f**: Aplikuje gradient DO child elementu → respektuje `childBackgroundSource`

### WYMAGANE:
- ✅ `uveApplyStyles` sprawdza `childBackgroundSource`
- ✅ Background-* style aplikowane do child elementu gdy `childBackgroundSource` jest ustawione
- ✅ Inne style (typography, box-model) nadal aplikowane do parent elementu

---

## Full-Width Block CSS Rules (v2.11) ⚠️

### KRYTYCZNE: pd-pseudo-parallax musi być full-width! (FIX #15)

**Problem:** Bloki `.pd-pseudo-parallax` były "boxed" (1300px) zamiast full-width (2553px) jak na oryginalnym sklep.kayomoto.pl.

### Symptom:
Na test.kayomoto.pl sekcje parallax miały szerokość 1300px (grid-column: block) zamiast pełnej szerokości strony.

### Przyczyna:
W `getLayoutFixCss()` CSS selector `.uve-content > div:not(...)` aplikował `grid-column: block` do wszystkich divów, włącznie z `.pd-pseudo-parallax` który powinien być full-width.

### Rozwiązanie:
W `CssSyncOrchestrator.php` (~linia 617):

```css
/* FIX #15: Exclude pd-pseudo-parallax from block constraint - it must be full-width */
.uve-content > .pd-block:not(.pd-intro):not([class*="grid-row"]):not(.pd-pseudo-parallax),
.uve-content > .pd-specification,
.uve-content > div:not(.pd-intro):not(.bg-brand):not(.pd-cover):not([class*="grid-row"]):not(.pd-pseudo-parallax) {
  grid-column: block;
}

/* FIX #15: pd-pseudo-parallax MUST be full-width (same as original sklep.kayomoto.pl) */
.uve-content > .pd-pseudo-parallax,
.uve-content > .pd-block.pd-pseudo-parallax,
.product-description .pd-pseudo-parallax {
  grid-column: 1 / -1;
}
```

### Bloki wymagające full-width:

| Blok | grid-column | Powód |
|------|-------------|-------|
| `.pd-intro` | `1 / -1` | Intro sekcja z nagłówkiem |
| `.bg-brand` | `1 / -1` | Tło na całą szerokość |
| `.pd-cover` | `1 / -1` | Hero image section |
| `.pd-pseudo-parallax` | `1 / -1` | **FIX #15** - Parallax sections |
| `[class*="grid-row"]` | `1 / -1` | Grid rows |

### Bloki z constraint (1300px):

| Blok | grid-column | Powód |
|------|-------------|-------|
| `.pd-block` (inne) | `block` | Standardowa szerokość content |
| `.pd-specification` | `block` | Tabela specyfikacji |
| Inne `div` | `block` | Domyślna szerokość |

### Weryfikacja:

```javascript
// Chrome DevTools - sprawdź szerokość pd-pseudo-parallax
document.querySelectorAll('.pd-pseudo-parallax').forEach((el, i) => {
    console.log(`pd-pseudo-parallax #${i}: ${el.offsetWidth}px`);
});
// Oczekiwane: 2553px (full-width), NIE 1300px (boxed)
```

### WYMAGANE:
- ✅ `:not(.pd-pseudo-parallax)` w selektorach `grid-column: block`
- ✅ Explicit rule `grid-column: 1 / -1` dla pd-pseudo-parallax
- ✅ Wszystkie 3 selektory (`.uve-content >`, `.pd-block.`, `.product-description`) dla pewności
- ✅ Browser hard refresh (Ctrl+Shift+R) po CSS sync

---

## Inline Gradient Editor (v2.8) ⚠️

### FIX #14: Pełny Inline Gradient Editor w Property Panel

**Problem:** Kontrolka Background w Property Panel miała tylko prosty color picker. Użytkownicy nie mogli tworzyć/edytować gradientów inline - musieli ręcznie wpisywać CSS.

### Rozwiązanie:
Zaimplementowano pełny inline gradient editor jako Alpine component `uveGradientEditorInline()`:

**Plik:** `resources/js/app.js` (~linia 1050)

```javascript
Alpine.data('uveGradientEditorInline', () => ({
    gradientType: 'linear',      // linear | radial
    angle: 180,                   // 0-360 dla linear
    colorStops: [                 // Array color stops
        { color: '#f6f6f6', position: 0 },
        { color: '#ef8248', position: 100 }
    ],
    selectedStop: 0,

    init() {
        // Parse initial gradient from parent
        Livewire.on('uve-background-updated', (data) => {
            const bgImage = data?.backgroundImage || data[0]?.backgroundImage;
            if (bgImage && bgImage.includes('gradient')) {
                this.parseGradient(bgImage);
            }
        });
    },

    parseGradient(gradientCss) { /* ... */ },
    generateGradientCss() { /* ... */ },
    updateParent() { /* ... */ },
    addColorStop() { /* ... */ },
    removeColorStop(index) { /* ... */ },
}));
```

### Funkcjonalności:
- **Zakładki**: Kolor | Obraz | Gradient
- **Typ gradientu**: Linear / Radial
- **Kąt**: Slider + presety (0°, 45°, 90°, 135°, 180°, 225°, 270°, 315°)
- **Color stops**: Lista z color picker + pozycja (0-100%)
- **Pasek podglądu**: Wizualizacja gradientu z markerami
- **Gotowe gradienty**: Presety do szybkiego wyboru
- **CSS output**: Podgląd wygenerowanego CSS

### FIX #14b: Drag Functionality dla Markerów

**Problem:** Markery kolorów na pasku gradientu nie mogły być przeciągane myszką.

**Rozwiązanie:** Dodano pełny drag & drop:

```javascript
// State
draggingIndex: null,
barElement: null,

init() {
    // ... existing code ...

    // FIX #14b: Drag event listeners
    document.addEventListener('mousemove', (e) => this.onDrag(e));
    document.addEventListener('mouseup', () => this.stopDrag());
    document.addEventListener('touchmove', (e) => this.onDrag(e));
    document.addEventListener('touchend', () => this.stopDrag());
},

startDrag(index, event) {
    event.preventDefault();
    this.draggingIndex = index;
    this.selectedStop = index;
    this.barElement = this.$el.querySelector('.uve-gradient-stops-bar');
},

onDrag(event) {
    if (this.draggingIndex === null || !this.barElement) return;

    const rect = this.barElement.getBoundingClientRect();
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const relativeX = clientX - rect.left;

    let newPosition = Math.round((relativeX / rect.width) * 100);
    newPosition = Math.max(0, Math.min(100, newPosition));
    this.colorStops[this.draggingIndex].position = newPosition;
},

stopDrag() {
    if (this.draggingIndex !== null) {
        this.updateParent();
        this.draggingIndex = null;
    }
}
```

### Blade Template:

**Plik:** `resources/views/livewire/products/visual-description/controls/background.blade.php`

```blade
{{-- Color stops bar with draggable markers --}}
<div class="uve-gradient-stops-bar" :style="'background: ' + gradientCss">
    <template x-for="(stop, index) in colorStops" :key="index">
        <div
            class="uve-gradient-stop-marker"
            :class="{
                'uve-gradient-stop-marker--selected': selectedStop === index,
                'uve-gradient-stop-marker--dragging': draggingIndex === index
            }"
            :style="'left: ' + stop.position + '%'"
            @mousedown="startDrag(index, $event)"
            @touchstart="startDrag(index, $event)"
        >
            <div class="uve-gradient-stop-handle" :style="'background: ' + stop.color"></div>
        </div>
    </template>
</div>
```

### CSS dla markerów:

```css
.uve-gradient-stop-marker {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    cursor: grab;
    z-index: 1;
    user-select: none;
    touch-action: none;
}

.uve-gradient-stop-marker--dragging {
    cursor: grabbing;
    z-index: 3;
}

.uve-gradient-stop-marker--dragging .uve-gradient-stop-handle {
    transform: scale(1.3);
    border-color: #e0ac7e;
    box-shadow: 0 2px 6px rgba(224, 172, 126, 0.5);
}
```

### WYMAGANE:
- ✅ Alpine component `uveGradientEditorInline` w app.js
- ✅ Zakładki Kolor/Obraz/Gradient w background.blade.php
- ✅ Drag handlers: `startDrag()`, `onDrag()`, `stopDrag()`
- ✅ Event listeners w `init()` dla mouse/touch events
- ✅ CSS classes dla marker states (selected, dragging)

### FIX #14e: CssValueFormatter Format Compatibility (v2.9)

**Problem:** Property Panel → Canvas sync nie działał dla gradientów. Po przesunięciu markera koloru, canvas nie aktualizował się mimo że Livewire request był wysyłany (HTTP 200).

### Symptom:
Przesunięcie markera koloru gradientu w Property Panel → Canvas pokazuje stary gradient (bez zmian).

### Root Cause:
`formatBackground()` w `CssValueFormatter.php` oczekiwało formatu z kluczem `type`:
```php
// Oczekiwany format (legacy):
['type' => 'color|image|gradient', 'color' => '...', 'image' => '...']

// Ale Alpine emitChange() wysyła:
['backgroundColor' => '...', 'backgroundImage' => 'linear-gradient(...)', ...]
```

Bez klucza `type`, metoda defaultowała do `'color'` i szukała `$value['color']` który nie istniał → zwracała pusty array `[]`.

### Rozwiązanie:
W `formatBackground()` dodano detekcję formatu Alpine i bezpośrednie mapowanie CSS:

```php
// app/Services/VisualEditor/PropertyPanel/CssValueFormatter.php

public function formatBackground(array $value): array
{
    $css = [];

    // FIX #14e: Handle Alpine emitChange() format (camelCase CSS properties)
    // Alpine sends: { backgroundColor, backgroundImage, backgroundSize, ... }
    // Legacy format sends: { type: 'color|image|gradient', color: '...', image: '...' }

    // Check if this is Alpine format (has backgroundImage or backgroundColor keys)
    $isAlpineFormat = isset($value['backgroundImage']) || isset($value['backgroundColor']);

    if ($isAlpineFormat) {
        // Alpine format - direct CSS properties
        if (!empty($value['backgroundColor'])) {
            $css['background-color'] = $value['backgroundColor'];
        }

        if (!empty($value['backgroundImage'])) {
            // backgroundImage can be gradient string or url('...')
            $css['background-image'] = $value['backgroundImage'];
        }

        if (!empty($value['backgroundSize'])) {
            $css['background-size'] = $value['backgroundSize'];
        }

        if (!empty($value['backgroundPosition'])) {
            $css['background-position'] = $value['backgroundPosition'];
        }

        if (!empty($value['backgroundRepeat'])) {
            $css['background-repeat'] = $value['backgroundRepeat'];
        }

        if (!empty($value['backgroundAttachment']) && $value['backgroundAttachment'] !== 'scroll') {
            $css['background-attachment'] = $value['backgroundAttachment'];
        }

        return $css;
    }

    // Legacy format with 'type' key
    $type = $value['type'] ?? 'color';
    // ... existing switch statement ...
}
```

### Data Flow (po naprawie):
```
Alpine uveGradientEditorInline.stopDrag()
    → updateParent()
    → uveBackgroundControl.emitChange()
    → $wire.updateControlValue('background', { backgroundColor, backgroundImage, ... })
    → UVE_PropertyPanel.updateNormalControlValue()
    → CssValueFormatter.formatToCss('background', $value)
    → formatBackground($value) // FIX #14e: teraz rozpoznaje Alpine format!
    → return ['background-image' => 'linear-gradient(...)']
    → syncToIframe() → window.uveApplyStyles()
    → Canvas aktualizuje gradient ✅
```

### WYMAGANE:
- ✅ `formatBackground()` MUSI rozpoznawać oba formaty (Alpine i legacy)
- ✅ Backward compatibility z formatem `{ type, color, image }`
- ✅ Direct CSS property mapping dla formatu Alpine

---

## Image URL Update Rules (v2.4) ⚠️

### KRYTYCZNE: srcset ma priorytet nad src!

Przeglądarka używa `srcset` zamiast `src` gdy oba są obecne. To powoduje że zmiana tylko `src` NIE aktualizuje wyświetlanego obrazka.

### Przy zmianie obrazka w Media Picker:

```php
// UVE_MediaPicker.php - updateImageInHtml()

if ($tagName === 'img') {
    // 1. Aktualizuj src
    $element->setAttribute('src', $newSrc);

    // 2. KRYTYCZNE: Aktualizuj srcset!
    if ($element->hasAttribute('srcset')) {
        $element->setAttribute('srcset', $newSrc);
    }

    // 3. Jeśli img jest w <picture>, zaktualizuj <source>
    $parent = $element->parentNode;
    if ($parent && strtolower($parent->nodeName) === 'picture') {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeName === 'source') {
                $child->setAttribute('srcset', $newSrc);
            }
        }
    }
}
```

### Struktura HTML do obsługi:

```html
<!-- Prosty img z srcset -->
<img src="old.jpg" srcset="old.jpg 1x, old@2x.jpg 2x" alt="...">

<!-- Picture z source -->
<picture class="pd-cover_picture">
    <source srcset="old-sm.webp 650w, old.webp 960w" sizes="...">
    <img src="old.jpg" srcset="old.jpg" alt="...">
</picture>
```

### Checklist przy zmianie obrazka:
- [ ] `src` zaktualizowany
- [ ] `srcset` zaktualizowany (jeśli istnieje)
- [ ] `<source srcset>` zaktualizowane (jeśli w `<picture>`)

---

## Migracja Inline Styles

### Artisan Command

```bash
# Dry run
php artisan uve:migrate-inline-styles --dry-run --limit=10

# Migracja dla sklepu
php artisan uve:migrate-inline-styles --shop=5

# Tylko walidacja
php artisan uve:migrate-inline-styles --validate-only
```

### InlineStyleMigrator

**Plik:** `app/Services/VisualEditor/InlineStyleMigrator.php`

```php
$migrator = new InlineStyleMigrator();
$result = $migrator->migrate($description);
```

---

## Debugging

### Sprawdzenie CSS na Produkcji

1. DevTools -> Network -> `custom.css`
2. Szukaj markerow `@uve-styles-start`
3. Sprawdz czy brak globalnych `:root` z UVE

### Logi Laravel

```bash
# Na Hostido
tail -n 100 storage/logs/laravel.log | grep -i "CssSyncOrchestrator"
```

### Walidacja CSS Leaks

```php
$orchestrator = app(CssSyncOrchestrator::class);
$result = $orchestrator->validateCssForLeaks($css);
// ['valid' => false, 'leaks' => [['selector' => ':root', 'reason' => '...']]]
```

---

## Checklist Przed Deployem UVE

### HTML/CSS Rules
- [ ] Brak inline styles w HTML (`style="..."`)
- [ ] Brak embedded `<style>` blocks
- [ ] Wszystkie selektory scoped do `.uve-content` lub `.product-description`
- [ ] Brak globalnych `:root` variables
- [ ] Brak unscoped `.container`, `.btn`, `.nav`
- [ ] `validateCssForLeaks()` zwraca `valid: true`

### Sync Safety (v2.3)
- [ ] Lock mechanism dziala (`Cache::lock`)
- [ ] Backup tworzony przed sync
- [ ] FULL REPLACE (nie append)
- [ ] **forceFetch=true** w fetchExistingCssWithValidation()
- [ ] **Markery ujednolicone** między CssRuleGenerator i CssSyncOrchestrator
- [ ] **Cache update** po successful upload

### Weryfikacja Post-Sync
- [ ] Logi pokazują `has_root_vars: true`
- [ ] Logi pokazują `has_btn_primary: true`
- [ ] Oryginalne style theme'u zachowane na stronie produktu

### Media Picker / Property Panel (v2.7)
- [ ] **srcset aktualizowany** razem z src (FIX #8)
- [ ] **GLOBAL indexing** w markChildElements i findElementInContext (FIX #10)
- [ ] **XPath IDENTYCZNY** w injectEditableMarkers i findElementByStructuralMatching (FIX #11)
- [ ] **Dispatch imageUrl** dla kontrolek z wire:ignore.self (FIX #12)
- [ ] **Child gradient inheritance** dla bloków z `__picture` child (FIX #13d)

### Layout CSS Rules (v2.11)
- [ ] **pd-pseudo-parallax** ma `grid-column: 1 / -1` (FIX #15)
- [ ] **Full-width blocks** (.pd-intro, .bg-brand, .pd-cover, .pd-pseudo-parallax) excluded from block constraint
- [ ] **Browser hard refresh** (Ctrl+Shift+R) po CSS sync dla weryfikacji

---

## Powiazane Dokumenty

- `_DOCS/UVE_VISUAL_EDITOR.md` - Pelna dokumentacja
- `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` - Plan implementacji
- `_AGENT_REPORTS/architect_UVE_CSS_ARCHITECTURE_REFINEMENT.md` - Raport architektoniczny

---

## Changelog

### v2.11 (2026-01-16)
- **[CRITICAL FIX]** FIX #15: Full-Width Block CSS Rules for pd-pseudo-parallax
- **[ROOT CAUSE]** CSS selector `.uve-content > div:not(...)` aplikował `grid-column: block` do pd-pseudo-parallax
- **[SYMPTOM]** Bloki parallax miały 1300px (boxed) zamiast full-width (2553px) jak na sklep.kayomoto.pl
- **[FIX]** Dodano `:not(.pd-pseudo-parallax)` + explicit rule `grid-column: 1 / -1`
- **[FILE]** `app/Services/VisualEditor/CssSyncOrchestrator.php`
- **[VERIFIED]** Chrome DevTools - wszystkie pd-pseudo-parallax = 2553px full-width
- **[DOC]** Dodano sekcję: Full-Width Block CSS Rules z tabelą bloków full-width vs boxed

### v2.10 (2026-01-16)
- **[CRITICAL FIX]** FIX #14f: uveApplyStyles Child Element Gradient Application
- **[ROOT CAUSE]** `uveApplyStyles` aplikowało WSZYSTKIE style do parent elementu, ignorując `childBackgroundSource`
- **[SYMPTOM]** Property Panel pokazywał gradient z child elementu, ale zmiany NIE aktualizowały canvas
- **[FIX]** `uveApplyStyles` teraz sprawdza `childBackgroundSource` i aplikuje `background-*` style do child elementu
- **[FILE]** `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php`
- **[VERIFIED]** Chrome DevTools - canvas aktualizuje się w real-time przy zmianie gradientu

### v2.9 (2026-01-16)
- **[CRITICAL FIX]** FIX #14e: CssValueFormatter Format Compatibility
- **[ROOT CAUSE]** `formatBackground()` oczekiwało `{ type, color, image }` ale Alpine wysyła `{ backgroundColor, backgroundImage, ... }`
- **[FIX]** Dodano detekcję formatu Alpine i bezpośrednie mapowanie CSS properties
- **[FEATURE]** Backward compatibility - obsługiwane oba formaty (Alpine i legacy)
- **[DOC]** Dodano sekcję: FIX #14e z pełnym data flow diagramem

### v2.8 (2026-01-16)
- **[FEATURE]** FIX #14: Inline Gradient Editor w Property Panel
- **[FEATURE]** FIX #14b: Drag & Drop dla markerów kolorów gradientu
- **[FEATURE]** FIX #14d: Canvas → Property Panel sync dla gradientów
- **[DOC]** Dodano sekcję: Inline Gradient Editor (kompletna dokumentacja)

### v2.7 (2026-01-16)
- **[CRITICAL FIX]** FIX #13d: Child Element Background Inheritance
- **[ROOT CAUSE]** Bloki jak `.pd-cover` mają `background-image: none`, gradient jest na child `.pd-cover__picture`
- **[FIX]** `getElementStyles()` teraz sprawdza child elementy `__picture` dla gradientów
- **[MAPPING]** Dodano kontrolkę `background` do `pd-cover` w CssClassMappingDefinitions
- **[DOC]** Dodano sekcję: Child Element Background Inheritance
- **[CACHE]** Po zmianach w iframe scripts wymagany hard reload (`window.location.reload(true)`)

### v2.6 (2026-01-15)
- **[CRITICAL FIX]** FIX #10: GLOBAL indexing (rollback from FIX #9)
- **[CRITICAL FIX]** FIX #11: XPath unification - `injectEditableMarkers()` i `findElementByStructuralMatching()` MUSZĄ używać identycznego XPath
- **[CRITICAL FIX]** FIX #12: Alpine wire:ignore.self sync - `onElementSelectedForPanel()` MUSI dispatch'ować `uve-image-url-updated`
- **[ROOT CAUSE #11]** Różne XPath queries powodowały że bloki były różnie liczone (pd-block vs pd-*)
- **[ROOT CAUSE #12]** Kontrolki Alpine z `wire:ignore.self` nie reinicjalizują się przy Livewire update
- **[DOC]** Dodano sekcje: XPath Unification Rules, Alpine wire:ignore Sync Rules, zaktualizowany checklist

### v2.5 (2026-01-15) - DEPRECATED
- FIX #9: Per-type indexing - **WYCOFANY** w v2.6 na rzecz GLOBAL indexing (FIX #10)

### v2.4 (2026-01-15)
- **[CRITICAL FIX]** FIX #8: srcset MUSI być aktualizowany razem z src!
- **[ROOT CAUSE]** Przeglądarka preferuje srcset nad src - stary obrazek wyświetlany mimo nowego src
- **[FIX]** `updateImageInHtml()` teraz aktualizuje: src, srcset, oraz `<source>` w `<picture>`
- **[DOC]** Dodano sekcję: Image URL Update Rules

### v2.3 (2026-01-13)
- **[CRITICAL FIX]** `forceFetch=true` jako MANDATORY - zawsze pobieraj świeży CSS z FTP
- **[CRITICAL FIX]** Ujednolicone markery CSS między CssRuleGenerator i CssSyncOrchestrator
- **[FEATURE]** Diagnostyczne logowanie: `has_root_vars`, `has_btn_primary`, `first_100_chars`
- **[FIX]** Cache update po successful upload - zapobiega stale cache
- **[DOC]** Dodano sekcje: Force Fetch, Unified Markers, Cache Update, Diagnostyczne Logowanie

### v2.2 (2026-01-13)
- CSS Isolation - wszystkie style scoped
- `validateCssForLeaks()` - walidacja przeciekow
- Usuniete globalne `:root`

### v2.0 (2026-01-12)
- CSS-First Architecture
- Per-style hash naming
- Sync safety (lock, backup, rollback)
