# UVE - Unified Visual Editor

## Przeglad

UVE (Unified Visual Editor) to system edycji wizualnej opisow produktow dla PrestaShop, zintegrowany z PPM (PrestaShop Product Manager). UVE pozwala na tworzenie bogatych opisow produktow z pelna kontrola nad stylami CSS.

**Wersja:** 2.3 (CSS-First Architecture + CSS Isolation + Sync Safety Fixes)
**Data aktualizacji:** 2026-01-13

---

## Architektura CSS-First

### Zasada Zero Inline Styles

**KRYTYCZNE:** UVE w wersji 2.0+ NIE UZYWA inline styles (`style="..."`). Wszystkie style sa zapisywane jako klasy CSS.

```html
<!-- STARA ARCHITEKTURA (zabroniona) -->
<h2 style="font-size: 56px; font-weight: 800;">KAYO S200</h2>

<!-- NOWA ARCHITEKTURA (CSS-First) -->
<h2 class="uve-s7f3a2b1">KAYO S200</h2>
```

### Naming Convention

| Prefix | Format | Opis |
|--------|--------|------|
| `.uve-s{hash}` | Per-style hash | Klasa generowana z hash stylow |
| `.uve-content` | Wrapper | Kontener dla CSS specificity |
| `.pd-*` | Product Description | Istniejace klasy z theme PrestaShop |

**Generowanie hash:**
```php
// Ten sam styl = ta sama klasa (reuse CSS)
$hash = substr(md5(json_encode(ksorted($styles))), 0, 8);
$className = "uve-s{$hash}";  // np. uve-s7f3a2b1
```

---

## CSS Isolation (v2.2)

### Problem "Przeciekow"

Bez izolacji, style UVE moga wplywac na inne elementy theme PrestaShop:
- Breadcrumbs
- Top menu
- Przyciski
- Stopka

### Rozwiazanie - Scoped Selectors

**WSZYSTKIE style musza byc scoped** do selektorow opisu produktu:

```css
/* PRAWIDLOWO - scoped do .uve-content */
.uve-content .bg-brand { background-color: var(--uve-brand-color); }
.product-description .pd-intro { display: grid; }
#product .tabs .nav-tabs { padding-inline: ...; }

/* ZABRONIONE - global selectors */
:root { --brand-color: ...; }       /* NIE! Leaks to theme */
.container { max-width: 100%; }     /* NIE! Affects all containers */
.btn { ... }                        /* NIE! Affects all buttons */
```

### Safe Selector Prefixes

| Prefix | Bezpieczny | Uzycie |
|--------|------------|--------|
| `.uve-content` | TAK | Wrapper dla UVE content |
| `.uve-e*`, `.uve-s*` | TAK | Klasy elementow/stylow UVE |
| `.product-description` | TAK | Kontener opisu produktu |
| `#product-description` | TAK | ID tab opisu |
| `#product .tabs` | TAK | Scoped do product page |
| `:root` | **NIE** | Global variables - ZABRONIONE |
| `.container`, `.btn` | **NIE** | Unscoped - ZABRONIONE |

### Walidacja CSS Leaks

```php
// CssSyncOrchestrator::validateCssForLeaks()
$validation = $this->validateCssForLeaks($css);
if (!$validation['valid']) {
    Log::warning('CSS leak detected', $validation['leaks']);
}
```

---

## Komponenty Systemu

### 1. CssSyncOrchestrator

**Plik:** `app/Services/VisualEditor/CssSyncOrchestrator.php`

**Odpowiedzialnosc:**
- Synchronizacja CSS do PrestaShop via FTP
- Lock mechanism (zapobiega race conditions)
- Backup i rollback przy bledach
- Walidacja CSS leaks

**Kluczowe metody:**

| Metoda | Opis |
|--------|------|
| `syncProductDescription()` | Glowna metoda sync |
| `generateCssFromRulesV2()` | Generuje CSS z rules |
| `getLayoutFixCss()` | Base CSS z izolacja |
| `validateCssForLeaks()` | Sprawdza przecieki |
| `fullReplaceWithMarkers()` | FULL REPLACE strategy |
| `rollbackCss()` | Rollback przy bledzie |

**Markers CSS:**
```css
/* @uve-styles-start */
... UVE styles here ...
/* @uve-styles-end */
```

### 2. UVE_CssClassGeneration Trait

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssClassGeneration.php`

**Odpowiedzialnosc:**
- Generowanie klas CSS per-style
- Mapping elementId -> className
- Walidacja zero-inline-styles

### 3. UVE_PropertyPanel Trait

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php`

**Odpowiedzialnosc:**
- UI do edycji stylow elementow
- Zapis do css_rules (nie inline!)
- Sync do iframe

### 4. UVE_BlockManagement Trait

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/UVE_BlockManagement.php`

**Odpowiedzialnosc:**
- Zarzadzanie blokami HTML
- Kompilacja HTML z klasami CSS
- Wrapper .uve-content

---

## Tryby Dostarczania CSS

| Tryb | Warunek | Opis |
|------|---------|------|
| `external` | FTP skonfigurowane | CSS w `/themes/{theme}/css/custom.css` |
| `pending` | Brak FTP | Blokada sync, ostrzezenie dla uzytkownika |

**USUNIETY:** `inline_style_block` - NIE JEST JUZ VALID OPTION!

### External Mode Workflow

```
1. Uzytkownik edytuje opis w UVE (PPM admin)
2. Style zapisywane do css_rules (JSON w DB)
3. HTML generowany z klasami CSS (bez inline!)
4. SyncProductCssJob wysyla CSS via FTP
5. CSS trafia do /themes/{theme}/css/custom.css
6. PrestaShop serwuje CSS z cache
```

### Sciezka Pliku CSS

```
/public_html/themes/{theme_name}/assets/css/custom.css
```

**UWAGA:** Shared hosting ma FTP root w `/home/user/`, wiec sciezka to:
```
/public_html/themes/warehouse/assets/css/custom.css
```

---

## Struktura Bazy Danych

### Tabela: product_descriptions

| Kolumna | Typ | Opis |
|---------|-----|------|
| `css_rules` | JSON | Definicje CSS: `{".uve-s{hash}": {...}}` |
| `css_class_map` | JSON | Mapping elementId -> styleHash |
| `css_mode` | ENUM | 'external' lub 'pending' |
| `css_validated_at` | TIMESTAMP | Data walidacji zero-inline |
| `css_migrated_at` | TIMESTAMP | Data migracji z inline |
| `css_synced_at` | TIMESTAMP | Data ostatniego sync |

---

## Layout Fix CSS

UVE dodaje "Layout Fix CSS" do kazdego sync, aby zapewnic kompatybilnosc z roznymi theme PrestaShop (warehouse, kayo, etc.).

### Zawarte Fixy

1. **Scoped UVE Variables** - zmienne CSS scoped do `.uve-content`
2. **Container Fix** - `max-width: 100%` tylko dla `.product-description .container`
3. **Grid Layout** - pelna szerokosc dla opisu produktu
4. **Nav Tabs Fix** - padding formula dla warehouse theme
5. **Product Description Styles** - `.pd-*` klasy scoped do `.uve-content`

### Przyklad

```css
/* === UVE CSS ISOLATION LAYER v2.2 === */

/* Scoped variables (NOT global :root!) */
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

/* Scoped brand background */
.uve-content .bg-brand,
.product-description .bg-brand {
  background-color: var(--uve-brand-color);
  grid-column: 1 / -1;
}
```

---

## Sync Safety

### Lock Mechanism

```php
// Zapobiega rownoczesnym sync dla tego samego sklepu
$lock = Cache::lock('uve_css_sync_' . $shopId, 60);
if (!$lock->get()) {
    return ['error' => 'Sync in progress'];
}
```

### Backup i Rollback

```php
// Backup przed modyfikacja
$backup = $existingCss;

try {
    $this->uploadCss($shop, $mergedCss);
} catch (\Throwable $e) {
    // Rollback przy bledzie
    $this->rollbackCss($shop, $backup);
    throw $e;
}
```

### FULL REPLACE Strategy

CSS jest **zastepowany** (nie appendowany) w obrebie markerow:

```css
/* Przed sync */
/* @uve-styles-start */
.uve-s123 { ... }  /* stary */
/* @uve-styles-end */

/* Po sync */
/* @uve-styles-start */
.uve-s456 { ... }  /* nowy (FULL REPLACE) */
/* @uve-styles-end */
```

### Force Fetch from FTP (v2.3) ⚠️

**KRYTYCZNE:** ZAWSZE pobieraj swiezy CSS z FTP przed merge!

```php
// ❌ BLEDNIE - uzywa cache (moze byc stale!)
$fetchResult = $this->fetchExistingCssWithValidation($shop, false);

// ✅ PRAWIDLOWO - zawsze swiezy z FTP
$fetchResult = $this->fetchExistingCssWithValidation($shop, true);  // forceFetch = true
```

**Dlaczego to wazne:**
- Cache moze zawierac stary CSS (po recznym upload przez uzytkownika)
- Stale cache powoduje utrate oryginalnych styli theme'u (`.btn-primary`, `:root`, etc.)
- **ROOT CAUSE** wielu problemow z "nadpisywaniem styli"

### Unified CSS Markers (v2.3)

**WSZYSTKIE komponenty MUSZA uzywac TYCH SAMYCH markerow:**

| Komponent | Marker START | Marker END |
|-----------|--------------|------------|
| CssRuleGenerator | `/* @uve-styles-start */` | `/* @uve-styles-end */` |
| CssSyncOrchestrator | `/* @uve-styles-start */` | `/* @uve-styles-end */` |

**⚠️ NIGDY rozne markery miedzy komponentami!**

### Cache Update After Upload (v2.3)

**Po successful upload ZAWSZE aktualizuj cache:**

```php
// Po uploadCss()
$shop->update([
    'cached_custom_css' => $mergedCss,
    'css_last_fetched_at' => now(),
]);
```

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

### Weryfikacja w Logach

Po CSS Sync sprawdz logi:
```bash
tail -n 30 storage/logs/laravel.log | grep "CssSyncOrchestrator"
```

**Oczekiwany wynik dla prawidlowego sync:**
```
has_root_vars: true ✅
has_btn_primary: true ✅
merged_has_root_vars: true ✅
merged_has_btn_primary: true ✅
```

**Jesli `has_root_vars: false` lub `has_btn_primary: false`** = PROBLEM! Cache stale lub bledny merge.

---

## Migracja Istniejacych Opisow

### Artisan Command

```bash
# Dry run - podglad co zostanie zmigrowane
php artisan uve:migrate-inline-styles --dry-run --limit=10

# Migracja dla konkretnego sklepu
php artisan uve:migrate-inline-styles --shop=5

# Walidacja bez migracji
php artisan uve:migrate-inline-styles --validate-only
```

### InlineStyleMigrator Service

**Plik:** `app/Services/VisualEditor/InlineStyleMigrator.php`

```php
$migrator = new InlineStyleMigrator();
$result = $migrator->migrate($description);
// $result: ['migrated' => true, 'styles_count' => 15]
```

---

## Weryfikacja i Debugging

### Sprawdzenie CSS na Produkcji

1. Otworz strone produktu na PrestaShop
2. DevTools -> Network -> szukaj `custom.css`
3. Sprawdz czy zawiera markery `@uve-styles-start`

### Sprawdzenie CSS Isolation

```javascript
// W konsoli przegladarki
// Sprawdz czy nie ma globalnych :root z UVE
document.styleSheets[0].cssRules
```

### Cache-Busting

```bash
# Dodaj timestamp do URL CSS
?v=1705142400
```

---

## Best Practices

### DO (Prawidlowo)

- Uzywaj `.uve-content` jako wrapper
- Scopuj wszystkie selektory do `.product-description` lub `.uve-content`
- Uzywaj per-style hash (`uve-s{hash}`)
- Waliduj CSS przed sync (`validateCssForLeaks()`)
- Uzywaj FULL REPLACE (nie append)

### DON'T (Zabronione)

- NIE uzywaj globalnego `:root` dla UVE variables
- NIE uzywaj unscoped selektorow (`.container`, `.btn`)
- NIE uzywaj inline styles (`style="..."`)
- NIE appenduj CSS (uzywaj FULL REPLACE)
- NIE pomijaj walidacji CSS leaks

---

## Troubleshooting

### Problem: Style UVE nie dzialaja

1. Sprawdz czy `css_mode = 'external'`
2. Sprawdz czy FTP jest skonfigurowane
3. Sprawdz czy plik `custom.css` istnieje na serwerze
4. Sprawdz czy CSS jest ladowany w `<head>` strony

### Problem: Style wplywaja na breadcrumbs/menu

1. Sprawdz czy wszystkie selektory sa scoped
2. Uruchom `validateCssForLeaks()` na CSS
3. Uzyj tylko safe prefixes (`.uve-content`, `.product-description`)

### Problem: CSS nie syncuje sie

1. Sprawdz konfiguracje FTP sklepu
2. Sprawdz logi Laravel (`storage/logs/laravel.log`)
3. Sprawdz czy nie ma locka (`Cache::lock`)

### Problem: Oryginalne style theme nadpisywane (v2.3)

**Objawy:** Po sync CSS, przyciski, menu, breadcrumbs tracą oryginalne style (`.btn-primary`, `:root`, etc.)

**Diagnoza:**
```bash
tail -n 30 storage/logs/laravel.log | grep "CssSyncOrchestrator"
# Szukaj: has_root_vars: false lub has_btn_primary: false
```

**Przyczyna i rozwiązanie:**

1. **Stale cache** - Sprawdź czy `forceFetch=true`:
   ```php
   // W CssSyncOrchestrator::syncProductDescription()
   $fetchResult = $this->fetchExistingCssWithValidation($shop, true); // MANDATORY!
   ```

2. **Różne markery** - Sprawdź czy markery są ujednolicone:
   ```php
   // CssRuleGenerator.php i CssSyncOrchestrator.php MUSZĄ używać:
   '/* @uve-styles-start */'
   '/* @uve-styles-end */'
   ```

3. **Brak cache update** - Sprawdź czy cache jest aktualizowany po upload:
   ```php
   $shop->update([
       'cached_custom_css' => $mergedCss,
       'css_last_fetched_at' => now(),
   ]);
   ```

### Problem: Logi pokazują has_root_vars: false

**Przyczyna:** Cache zawiera stary CSS bez oryginalnych styli theme'u.

**Rozwiązanie:**
1. Przywróć oryginalny CSS do sklepu (via FTP)
2. Wyczyść cache sklepu: `$shop->update(['cached_custom_css' => null])`
3. Wykonaj CSS Sync ponownie

---

## Powiazane Pliki

| Plik | Opis |
|------|------|
| `app/Services/VisualEditor/CssSyncOrchestrator.php` | Glowna logika sync |
| `app/Services/VisualEditor/InlineStyleMigrator.php` | Migracja inline styles |
| `app/Jobs/VisualEditor/SyncProductCssJob.php` | Queue job dla sync |
| `app/Console/Commands/MigrateUveInlineStyles.php` | Artisan command |
| `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` | Plan implementacji |

---

## Changelog

### v2.3 (2026-01-13) - Sync Safety Fixes
- **[CRITICAL FIX]** `forceFetch=true` jako MANDATORY - zawsze pobieraj swiezy CSS z FTP
- **[CRITICAL FIX]** Ujednolicone markery CSS miedzy CssRuleGenerator i CssSyncOrchestrator
- **[FEATURE]** Diagnostyczne logowanie: `has_root_vars`, `has_btn_primary`, `first_100_chars`
- **[FIX]** Cache update po successful upload - zapobiega stale cache
- **[DOC]** Dodano sekcje: Force Fetch, Unified Markers, Cache Update, Diagnostyczne Logowanie

### v2.2 (2026-01-13) - CSS Isolation
- Dodana walidacja CSS leaks (`validateCssForLeaks()`)
- Wszystkie style scoped do `.uve-content`/`.product-description`
- Usuniete globalne `:root` variables
- Poprawione `getLayoutFixCss()` z pelna izolacja

### v2.0 (2026-01-12) - CSS-First Architecture
- Usunieto tryb `inline_style_block`
- Per-style hash zamiast per-element
- Sync safety: lock, backup, rollback
- Wrapper `.uve-content` dla specificity

### v1.0 (2026-01-09) - Initial Implementation
- Podstawowa architektura CSS-first
- Integracja z FTP

---

**Autor:** Claude Code
**Projekt:** PPM-CC-Laravel
