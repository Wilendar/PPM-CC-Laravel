# SKILL: UVE Visual Editor (CSS-First Architecture)

## Metadata
- **Version:** 2.6
- **Type:** domain
- **Enforcement:** require
- **Priority:** critical
- **Last Updated:** 2026-01-15

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

### Media Picker / Property Panel (v2.6)
- [ ] **srcset aktualizowany** razem z src (FIX #8)
- [ ] **GLOBAL indexing** w markChildElements i findElementInContext (FIX #10)
- [ ] **XPath IDENTYCZNY** w injectEditableMarkers i findElementByStructuralMatching (FIX #11)
- [ ] **Dispatch imageUrl** dla kontrolek z wire:ignore.self (FIX #12)

---

## Powiazane Dokumenty

- `_DOCS/UVE_VISUAL_EDITOR.md` - Pelna dokumentacja
- `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` - Plan implementacji
- `_AGENT_REPORTS/architect_UVE_CSS_ARCHITECTURE_REFINEMENT.md` - Raport architektoniczny

---

## Changelog

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
