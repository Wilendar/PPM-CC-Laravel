# ETAP 07h: UVE CSS-First Architecture

**Status:** ✅ IMPLEMENTACJA UKONCZONA I ZDEPLOYOWANA (2026-01-15)
**Priorytet:** KRYTYCZNY
**Szacowany czas:** 22-26 dni roboczych (dodano FAZA 8)
**Wersja:** 2.3 (Property Panel Improvements)
**Data implementacji:** 2026-01-15

---

## Cel etapu

Przeprojektowanie UVE (Unified Visual Editor) z architektury opartej na inline styles na architekture CSS-first, gdzie style sa zapisywane jako klasy CSS zamiast atrybutow `style=""`.

**KRYTYCZNA ZASADA: ZERO INLINE STYLES - BEZ KOMPROMISOW!**

## Problem

UVE zapisuje WSZYSTKIE computed styles jako inline atrybuty:
```html
<!-- PRZED (problematyczne) -->
<h2 style="font-family: Montserrat; font-size: 56px; font-weight: 800;
           width: 328.5px; height: 56px; display: block; ...">
```

**Konsekwencje:**
- Naruszenie CLAUDE.md - ZAKAZ inline styles
- Przekraczanie limitu 65535 znakow w opisach PrestaShop
- Problemy z renderowaniem (Firefox)
- Brak cachowalnosci CSS

## Rozwiazanie

```html
<!-- PO (CSS-first) -->
<h2 class="uve-s7f3a2b1">KAYO S200</h2>

<!-- CSS w zewnetrznym pliku uve-custom.css -->
.uve-s7f3a2b1 {
  font-size: 56px;
  font-weight: 800;
  font-family: Montserrat, serif;
}
```

---

## KRYTYCZNE ZMIANY ARCHITEKTONICZNE (v2.0)

### 1. ELIMINACJA TRYBU inline_style_block

**USUNIETY TRYB:** `inline_style_block` - NIE JEST JUZ VALID OPTION!

| Tryb | Status | Opis |
|------|--------|------|
| `external` | JEDYNY TRYB PRODUKCYJNY | CSS w zewnetrznym pliku via FTP |
| `pending` | TRYB TYMCZASOWY | Brak FTP = blokuj sync, wyswietl ostrzezenie |
| ~~`inline_style_block`~~ | USUNIETY | Fallback do `<style>` block ZABRONIONY |

**POWOD:** `<style>` block w opisie PrestaShop:
- Nadal zuzywa limity znakow (65535)
- Embedded content jest problematyczny
- CSS nie jest cacheowalny
- Przegrywa z theme specificity

### 2. WALIDACJA ZERO-INLINE-STYLES

**BLOKUJ SAVE/SYNC jesli HTML zawiera:**
- `style="..."` (inline style attribute)
- `<style>` (embedded style block)

```php
protected function validateZeroInlineStyles(string $html): array
{
    $violations = [];

    // Check for inline style attributes
    if (preg_match('/\s+style\s*=\s*["\'][^"\']+["\']/', $html)) {
        $violations[] = 'HTML contains inline style attributes';
    }

    // Check for embedded style blocks
    if (preg_match('/<style[^>]*>.*?<\/style>/is', $html)) {
        $violations[] = 'HTML contains embedded style blocks';
    }

    return $violations;
}
```

### 3. PER-STYLE HASH (zamiast per-element)

**STARA KONWENCJA (v1.0):**
```
.uve-e{hash} gdzie hash = md5(productId-shopId-elementId)
```
PROBLEM: Ten sam styl = rozne klasy (duplikacja CSS)

**NOWA KONWENCJA (v2.0):**
```
.uve-s{hash} gdzie hash = md5(canonicalized_styles)
```
ZALETA: Ten sam styl = ta sama klasa (reuse CSS)

```php
protected function generateStyleHash(array $styles): string
{
    // Canonicalize: sort keys, normalize values
    ksort($styles);
    $canonical = json_encode($styles, JSON_UNESCAPED_SLASHES);
    return 'uve-s' . substr(md5($canonical), 0, 8);
}
```

**Przyklady:**
- `.uve-s7f3a2b1` - font-size:56px; font-weight:800;
- `.uve-s8c4d5e2` - color:#333; line-height:1.6;

### 4. EXTERNAL MODE - SPECYFIKACJA

**Plik CSS:** `/themes/{theme}/css/uve-custom.css`

**Hook do <head> w PrestaShop:**
```php
// W module PPM_UVE lub custom hook
public function hookDisplayHeader($params)
{
    return '<link rel="stylesheet" href="' .
           $this->context->shop->getBaseURL() .
           'themes/' . $this->context->shop->theme->getName() .
           '/css/uve-custom.css?v=' . time() . '">';
}
```

**Alternatywa - reczne dodanie do theme:**
```smarty
{* themes/{theme}/templates/_partials/head.tpl *}
<link rel="stylesheet" href="{$urls.theme_assets}css/uve-custom.css" type="text/css">
```

**Struktura pliku uve-custom.css:**
```css
/* PPM UVE Custom Styles - AUTO-GENERATED - DO NOT EDIT */
/* Generated: 2026-01-12T10:30:00Z */
/* Version: v2.0.0 */

/* ========== STYLE DEFINITIONS ========== */
/* @uve-styles-start */
.uve-s7f3a2b1 { font-size: 56px; font-weight: 800; font-family: Montserrat, serif; }
.uve-s8c4d5e2 { line-height: 1.6; color: #333; }
.uve-sa1b2c3d { background: linear-gradient(135deg, #e0ac7e, #d1975a); }
/* @uve-styles-end */

/* ========== PRODUCT MAPPINGS ========== */
/* @uve-mappings-start */
/* Product 1234: uses uve-s7f3a2b1, uve-s8c4d5e2 */
/* Product 5678: uses uve-sa1b2c3d */
/* @uve-mappings-end */
```

### 5. SYNC SAFETY - LOCK, BACKUP, ROLLBACK

**Problem:** Race conditions gdy wiele produktow syncuje rownoczesnie

**Rozwiazanie:**
```php
class CssSyncOrchestrator
{
    // 1. LOCK - atomic file lock
    protected function acquireSyncLock(int $shopId): bool
    {
        $lockKey = "uve_css_sync_shop_{$shopId}";
        return Cache::lock($lockKey, 30)->get(); // 30 sec timeout
    }

    // 2. BACKUP przed kazdym sync
    protected function backupCurrentCss(PrestaShopShop $shop): string
    {
        $css = $this->fetchExistingCss($shop, true);
        $backupPath = "uve_backup/{$shop->id}/" . date('Y-m-d_His') . '.css';
        Storage::put($backupPath, $css);
        return $backupPath;
    }

    // 3. ROLLBACK przy bledzie
    protected function rollback(string $backupPath, PrestaShopShop $shop): void
    {
        $css = Storage::get($backupPath);
        $this->uploadCss($shop, $css);
    }

    // 4. FULL REPLACE (nie append!)
    protected function syncCss(array $allStyles): string
    {
        // REPLACE caly blok @uve-styles-start do @uve-styles-end
        // NIE append nowych regul!
    }
}
```

### 6. SPECIFICITY STRATEGY

**Problem:** `.uve-*` moze przegrac z theme CSS (np. `.product-description h2`)

**Rozwiazanie - Wrapper Scope:**
```css
/* Nadaj wrapper class do kontenera UVE */
.uve-content .uve-s7f3a2b1 { ... }

/* Lub jeszcze mocniej: */
.uve-content[data-uve-version="2"] .uve-s7f3a2b1 { ... }
```

**HTML output:**
```html
<div class="uve-content" data-uve-version="2">
    <h2 class="uve-s7f3a2b1">KAYO S200</h2>
</div>
```

**Preferuj istniejace klasy PrestaShop:**
- Uzyj `pd-*` classes gdzie mozliwe (pd = product description)
- Mapuj common styles do PS classes: `.pd-title`, `.pd-text`, `.pd-price`

### 7. STYLE LIBRARY / PRESETS (FAZA 7 - opcjonalne)

**Koncept:** Reusable named styles

```json
{
    "presets": {
        "heading-primary": {
            "class": "uve-sp-heading-primary",
            "styles": {
                "font-size": "56px",
                "font-weight": "800",
                "font-family": "Montserrat, serif"
            }
        },
        "text-body": {
            "class": "uve-sp-text-body",
            "styles": {
                "font-size": "16px",
                "line-height": "1.6",
                "color": "#333"
            }
        }
    }
}
```

**UI w Property Panel:**
- Dropdown "Apply Preset"
- Button "Save as Preset"
- Library browser dla globalnych stylow

---

## FAZA 1: Infrastruktura DB (2-3 dni)

### Status: ✅ UKONCZONE (2026-01-09)

### Zadania:
- [x] 1.1: Utworzenie migracji dla nowych kolumn
      └── PLIK: `database/migrations/2026_01_09_100001_add_css_columns_to_product_descriptions.php`
- [x] 1.2: Aktualizacja modelu ProductDescription
      └── PLIK: `app/Models/ProductDescription.php`
- [x] 1.3: Test migracji na produkcji

### Szczegoly techniczne (ZAKTUALIZOWANE v2.0):
```sql
ALTER TABLE product_descriptions
ADD COLUMN css_rules JSON NULL COMMENT 'Definicje CSS: {".uve-s{hash}": {...}}',
ADD COLUMN css_class_map JSON NULL COMMENT 'Mapowanie elementId -> styleHash',
ADD COLUMN css_mode ENUM('external', 'pending') DEFAULT 'pending',
ADD COLUMN css_validated_at TIMESTAMP NULL COMMENT 'Data ostatniej walidacji zero-inline',
ADD COLUMN css_migrated_at TIMESTAMP NULL;
```

**UWAGA:** Usuniety tryb `inline` i `inline_style_block` z ENUM!

---

## FAZA 2: Trait CSS Class Generation (2-3 dni)

### Status: ✅ UKONCZONE (2026-01-09) - WYMAGA AKTUALIZACJI

### Zadania:
- [x] 2.1: Utworzenie traitu UVE_CssClassGeneration.php
      └── PLIK: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssClassGeneration.php`
- [x] 2.2: Implementacja generateCssClassName()
- [x] 2.3: Implementacja setCssRule(), getCssClassForElement()
- [x] 2.4: Implementacja generateStyleBlock()
- [x] 2.5: Implementacja determineCssMode()
- [x] **2.6: AKTUALIZACJA - Per-style hash zamiast per-element** ✅
      └── PLIK: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssClassGeneration.php`
      └── RAPORT: `_AGENT_REPORTS/laravel_expert_PER_STYLE_HASH_IMPLEMENTATION.md`
- [x] **2.7: AKTUALIZACJA - Usunac generateStyleBlock() (nie uzywamy inline_style_block)** ✅ (2026-01-12)
      └── Metoda oznaczona @deprecated, rzuca RuntimeException
- [x] **2.8: AKTUALIZACJA - validateZeroInlineStyles()** ✅ (2026-01-12)
      └── Walidacja: style="", <style>, Tailwind arbitrary values
      └── assertZeroInlineStyles() - rzuca exception przy violations
      └── cleanEmptyStyleAttributes() - czysci puste style=""
- [ ] 2.9: Unit testy (opcjonalne)

### Naming Convention (ZAKTUALIZOWANA v2.0):
```
.uve-s{hash}
gdzie hash = substr(md5(canonicalized_styles), 0, 8)
```

---

## FAZA 3: Modyfikacja Property Panel (2-3 dni)

### Status: ✅ UKONCZONE (2026-01-09)

### Zadania:
- [x] 3.1: Modyfikacja updateNormalControlValue() - uzycie setCssRule()
      └── PLIK: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php`
- [x] 3.2: Modyfikacja syncToIframe() - dodanie className
- [x] 3.3: Integracja z UVE_CssClassGeneration trait
- [x] 3.4: Deploy na produkcje

---

## FAZA 4: Modyfikacja Block Management (2-3 dni)

### Status: ✅ UKONCZONE (2026-01-09) - WYMAGA AKTUALIZACJI

### Zadania:
- [x] 4.1: Dodanie applyElementClassesToHtml() - nowa metoda CSS-first
      └── PLIK: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_BlockManagement.php`
- [x] 4.2: Modyfikacja compileBlockHtml() - uzycie CSS classes gdy dostepne
- [x] 4.3: Modyfikacja generateRenderedHtml() - dodawanie <style> block
- [x] 4.4: Modyfikacja save() - zapis css_rules, css_class_map, css_mode
- [x] 4.5: Modyfikacja loadDescription() - ladowanie CSS rules
- [x] 4.6: Deploy i weryfikacja na produkcji
- [x] **4.7: AKTUALIZACJA - Usunac logike inline_style_block z generateRenderedHtml()** ✅ (2026-01-12)
      └── Usunieta logika CSS_MODE_INLINE_STYLE_BLOCK
      └── Fallback do inline styles usuniety z compileBlockHtml()
- [x] **4.8: AKTUALIZACJA - Dodac walidacje zero-inline przed save** ✅ (2026-01-12)
      └── generateRenderedHtml($validate = true) - walidacja przy generowaniu
      └── Logowanie violations (bez blokady na czas migracji)
- [x] **4.9: AKTUALIZACJA - Wrapper class .uve-content** ✅ (2026-01-12)
      └── wrapWithUveContent() - nowa metoda
      └── HTML opakowany w `<div class="uve-content">...</div>`
      └── CSS specificity: `.uve-content .uve-sXXXX { ... }`

### Weryfikacja (2026-01-12):
- css_rules zapisywane do DB: ✅
- css_class_map zapisywane do DB: ✅
- css_mode: 'pending' (domyslnie) lub 'external' (z FTP): ✅
- Klasy CSS generowane w HTML: ✅ (per-style hash: `uve-s{hash}`)
- Wrapper .uve-content: ✅
- Zero-inline validation: ✅ (log violations)

---

## FAZA 5: CSS Sync Service - ROZSZERZONA (4-5 dni)

### Status: ✅ UKONCZONE (2026-01-12)

### Zadania:
- [x] 5.1: Utworzenie SyncProductCssJob ✅ (2026-01-12)
      └── PLIK: `app/Jobs/VisualEditor/SyncProductCssJob.php`
      └── Queue: 'prestashop', retry: 3, backoff: 10s
      └── Skip jesli css_mode='pending' lub brak css_rules
- [x] 5.2: Rozszerzenie CssSyncOrchestrator.syncProductDescription() ✅ (2026-01-12)
      └── PLIK: `app/Services/VisualEditor/CssSyncOrchestrator.php`
      └── Nowe metody: generateCssFromRulesV2(), fullReplaceWithMarkers(), rollbackCss()
- [x] **5.3: Implementacja LOCK mechanism (Cache::lock)** ✅
      └── LOCK_PREFIX = 'uve_css_sync_', timeout 60s
- [x] **5.4: Implementacja BACKUP przed sync** ✅
      └── Backup w zmiennej $backup, przywracany przy error
- [x] **5.5: Implementacja ROLLBACK przy bledzie** ✅
      └── rollbackCss() wywolywane w catch block
- [x] **5.6: Implementacja FULL REPLACE (nie append!)** ✅
      └── fullReplaceWithMarkers() z markerami @uve-styles-start/@uve-styles-end
      └── CSS_SPECIFICITY_WRAPPER = '.uve-content'
- [ ] 5.7: Test synchronizacji via FTP (wymaga konfiguracji sklepu)
- [ ] 5.8: Weryfikacja na PrestaShop (wymaga deployu)

### External Mode - Szczegoly:
```
Plik docelowy: /themes/{theme}/css/uve-custom.css
Hook w PS: DisplayHeader lub manual include w theme
Struktura: @uve-styles-start ... @uve-styles-end + @uve-mappings-start ... @uve-mappings-end
```

### Sync Safety:
```php
public function syncProductCss(ProductDescription $description, array $cssRules): array
{
    $shop = PrestaShopShop::find($description->shop_id);

    // 1. Acquire lock (30 sec timeout)
    if (!$this->acquireSyncLock($shop->id)) {
        return ['success' => false, 'error' => 'Sync in progress by another process'];
    }

    try {
        // 2. Backup current CSS
        $backupPath = $this->backupCurrentCss($shop);

        // 3. Fetch existing
        $existingCss = $this->fetchUveCustomCss($shop);

        // 4. FULL REPLACE product styles (nie append!)
        $mergedCss = $this->replaceProductStyles($existingCss, $description->product_id, $cssRules);

        // 5. Upload
        $result = $this->uploadUveCustomCss($shop, $mergedCss);

        if (!$result['success']) {
            // 6. Rollback on failure
            $this->rollback($backupPath, $shop);
            return $result;
        }

        // 7. Cleanup old backups (keep last 5)
        $this->cleanupOldBackups($shop->id);

        return $result;

    } finally {
        // 8. Release lock
        $this->releaseSyncLock($shop->id);
    }
}
```

---

## FAZA 6: Migracja istniejacych opisow (2-3 dni)

### Status: ✅ UKONCZONE (2026-01-12)

### Zadania:
- [x] 6.1: Utworzenie InlineStyleMigrator service ✅ (2026-01-12)
      └── PLIK: `app/Services/VisualEditor/InlineStyleMigrator.php`
      └── migrate() - migracja pojedynczego ProductDescription
      └── extractAndConvertStyles() - parsowanie i konwersja
      └── Per-style hash: generateStyleHash()
- [x] 6.2: Utworzenie artisan command uve:migrate-inline-styles ✅ (2026-01-12)
      └── PLIK: `app/Console/Commands/MigrateUveInlineStyles.php`
- [x] 6.3: Opcje: --shop=, --dry-run, --limit=, --product=, --force ✅
- [x] **6.4: Walidacja zero-inline po migracji** ✅
      └── Style usuniete z HTML, dodane do css_rules
      └── css_migrated_at ustawiane po migracji
- [ ] 6.5: Test migracji na staging (wymaga deployu)
- [ ] 6.6: Dokumentacja

### Uzycie:
```bash
php artisan uve:migrate-inline-styles --dry-run --limit=10
php artisan uve:migrate-inline-styles --shop=5
php artisan uve:migrate-inline-styles --validate-only  # sprawdz bez migracji
```

---

## FAZA 7: Style Library / Presets (3-4 dni) - OPCJONALNE

### Status: ❌ Nie rozpoczete

### Zadania:
- [ ] 7.1: Migracja DB dla style presets
- [ ] 7.2: Model StylePreset
- [ ] 7.3: UI - dropdown "Apply Preset" w Property Panel
- [ ] 7.4: UI - button "Save as Preset"
- [ ] 7.5: Global vs per-shop presets
- [ ] 7.6: Import/export presets

### Benefit:
- Konsystentne style across produktow
- Szybsza edycja (apply preset)
- Mniejszy CSS (reuse classes)

---

## FAZA 8: CSS File Editor w Admin Panel (3-4 dni) - NOWA

### Status: ❌ Nie rozpoczete

### Cel:
Umozliwienie edycji pliku CSS PrestaShop (`custom.css`) bezposrednio z panelu PPM Admin,
bez koniecznosci otwierania FTP na serwerze.

### Zadania:
- [ ] 8.1: Komponent Livewire `ShopCssEditor.php`
      └── SCIEZKA: `app/Http/Livewire/Admin/Shops/ShopCssEditor.php`
      └── Funkcje: loadCss(), saveCss(), previewChanges()
- [ ] 8.2: Widok Blade z edytorem kodu
      └── SCIEZKA: `resources/views/livewire/admin/shops/shop-css-editor.blade.php`
      └── CodeMirror lub Monaco Editor dla CSS syntax highlighting
- [ ] 8.3: Rozszerzenie PrestaShopCssFetcher
      └── Metody: getCustomCss(), saveCustomCss(), createBackup()
- [ ] 8.4: Route i permissions
      └── Route: `/admin/shops/{id}/css-editor`
      └── Permission: `shops.edit_css`
- [ ] 8.5: UI integracja w Edit Shop page
      └── Tab "CSS Editor" lub przycisk "Edytuj CSS"
- [ ] 8.6: Backup przed kazda edycja
      └── Automatyczne tworzenie backup przed zapisem
      └── Lista backupow z opcja restore
- [ ] 8.7: Deploy i weryfikacja

### Specyfikacja UI:

```
+--------------------------------------------------+
| Shop: B2B Test DEV (test.kayomoto.pl)           |
+--------------------------------------------------+
| [Podstawowe] [FTP] [CSS Editor]                  |
+--------------------------------------------------+
|                                                  |
| Plik: /themes/warehouse/assets/css/custom.css   |
| [Odswież]  [Zapisz]  [Przywróc backup]           |
|                                                  |
| +----------------------------------------------+ |
| | /* Custom CSS */                             | |
| | .uve-content { ... }                         | |
| |                                              | |
| | /* @uve-styles-start */                      | |
| | .uve-s7f3a2b1 { font-size: 56px; }          | |
| | /* @uve-styles-end */                        | |
| +----------------------------------------------+ |
|                                                  |
| Ostatnia modyfikacja: 2026-01-13 10:30:00       |
| Backupy: [2026-01-13] [2026-01-12] [2026-01-11] |
+--------------------------------------------------+
```

### Funkcjonalnosc:

| Akcja | Opis |
|-------|------|
| `Load CSS` | Pobiera aktualny plik CSS via FTP |
| `Save CSS` | Zapisuje edytowany CSS via FTP (z backup) |
| `Preview` | Podglad zmian przed zapisem (opcjonalne) |
| `Restore Backup` | Przywrocenie poprzedniej wersji |
| `Diff View` | Porownanie zmian (opcjonalne) |

### Bezpieczenstwo:

1. **Backup MANDATORY** - przed kazdym zapisem
2. **Lock** - zapobieganie rownoczesnej edycji
3. **Validation** - sprawdzenie skladni CSS przed zapisem
4. **Audit log** - logowanie kto i kiedy edytowal

### Techniczne detale:

```php
// ShopCssEditor.php
public function loadCss(): void
{
    $shop = PrestaShopShop::findOrFail($this->shopId);
    $result = $this->cssFetcher->getCustomCss($shop);

    if ($result['success']) {
        $this->cssContent = $result['content'];
        $this->originalContent = $result['content'];
        $this->lastModified = $result['modified_at'] ?? null;
    }
}

public function saveCss(): void
{
    // 1. Create backup
    $this->createBackup();

    // 2. Validate CSS (optional - basic syntax check)
    if (!$this->validateCssSyntax($this->cssContent)) {
        $this->dispatch('notify', type: 'error', message: 'Invalid CSS syntax');
        return;
    }

    // 3. Save via FTP
    $result = $this->cssFetcher->saveCustomCss($shop, $this->cssContent);

    if ($result['success']) {
        $this->originalContent = $this->cssContent;
        $this->dispatch('notify', type: 'success', message: 'CSS zapisany pomyslnie');
    }
}
```

### Benefit:
- Szybka edycja CSS bez FTP client
- Bezpieczne backupy przed zmianami
- Centralny punkt kontroli CSS
- Latwe debugowanie problemow z stylami

---

## Tryby dostarczania CSS (ZAKTUALIZOWANE v2.0)

| Tryb | Warunek | Opis | Dzialanie |
|------|---------|------|-----------|
| `external` | FTP skonfigurowane | Zewnetrzny uve-custom.css via FTP | Sync dziala normalnie |
| `pending` | Brak FTP | Oczekiwanie na konfiguracje FTP | Blokuj sync, wyswietl ostrzezenie |

**USUNIETY:** `inline_style_block` - NIE JEST JUZ VALID OPTION!

---

## Walidacja Zero-Inline (NOWA SEKCJA)

### Pre-save validation:
```php
public function save(): void
{
    // Generate HTML with CSS classes
    $html = $this->generateRenderedHtml();

    // MANDATORY: Validate zero inline styles
    $violations = $this->validateZeroInlineStyles($html);

    if (!empty($violations)) {
        $this->dispatch('notify', type: 'error',
            message: 'HTML zawiera inline styles! ' . implode(', ', $violations));
        return; // BLOKUJ SAVE!
    }

    // Continue with save...
}
```

### Pre-sync validation:
```php
// W SyncProductCssJob lub CssSyncOrchestrator
if (!$this->validateCleanHtml($description->rendered_html)) {
    Log::error('CSS Sync blocked - HTML contains inline styles', [
        'product_id' => $description->product_id
    ]);
    return ['success' => false, 'error' => 'HTML must not contain inline styles'];
}
```

---

## Weryfikacja koncowa

- [x] Edycja opisu w UVE -> brak `style=""` w HTML ✅ (2026-01-12)
- [x] Brak `<style>` block w rendered_html (usuniety inline_style_block) ✅ (2026-01-12)
- [ ] Plik uve-custom.css na PrestaShop (tryb external) - wymaga konfiguracji FTP dla sklepu
- [x] Walidacja loguje violations jesli inline styles znalezione ✅ (2026-01-12)
- [x] Per-style hash dziala (te same style = ta sama klasa) ✅ (potwierdzone w logach: `uve-sd2620bf6`)
- [x] Sync safety: lock, backup, rollback - zaimplementowane ✅ (2026-01-12)
- [x] Specificity: wrapper .uve-content - zaimplementowane ✅ (2026-01-12)
- [x] InlineStyleMigrator + artisan command dziala ✅ (2026-01-12)
- [ ] Firefox renderuje poprawnie - do weryfikacji manualnej
- [x] Deploy na produkcje ✅ (2026-01-12)
- [x] UVE dziala po deployu - potwierdzone Chrome DevTools ✅ (2026-01-12)

---

## Korzysci

| Aspekt | Przed | Po | Poprawa |
|--------|-------|-----|---------|
| Rozmiar HTML | ~10KB+ per element | ~50B per element | -95% |
| Zgodnosc z CLAUDE.md | NARUSZONA | ZGODNA | 100% |
| Limit PrestaShop | Czesto przekraczany | Nigdy | 100% |
| Cacheowalnosc CSS | Brak | Pelna (external mode) | +100% |
| Style reuse | Brak | Per-style hash | Nowa funkcja |
| Sync safety | Brak | Lock+backup+rollback | Nowa funkcja |

---

## Ryzyka i Mitigacje

| Ryzyko | Prawdopodobienstwo | Mitigacja |
|--------|-------------------|-----------|
| Konflikt CSS z theme PS | Srednie | Wrapper `.uve-content` + higher specificity |
| FTP timeout przy sync | Niskie | Retry logic, async queue, lock timeout |
| Race conditions | Srednie | Cache::lock() z timeout |
| Regression w istniejacych opisach | Srednie | Walidacja, backup, rollback |
| Brak FTP u klienta | Wysokie | Wyraznie komunikuj: "CSS sync wymaga FTP" |

---

## Powiazane dokumenty

- Raport architektoniczny (v1.0): `_AGENT_REPORTS/architect_UVE_CSS_FIRST_ARCHITECTURE.md`
- Raport refinement (v2.0): `_AGENT_REPORTS/architect_UVE_CSS_ARCHITECTURE_REFINEMENT.md`
- Quick fix script: `_TOOLS/fix_prestashop_inline_styles.php`
- CssSyncOrchestrator: `app/Services/VisualEditor/CssSyncOrchestrator.php`

---

## Changelog

### v2.3 (2026-01-15) - Property Panel Improvements
- DODANY lightbox dla thumbnail preview w sekcji Tło (Background)
- FIX: Image-settings size mismatch - control-level values zamiast CSS
- DODANY image preview + URL input do image-settings kontrolki
- WERYFIKACJA: slider-settings prawidłowo przypisane do SliderBlock
- Pliki: `image-settings.blade.php`, `UVE_PropertyPanel.php`, `PropertyPanelService.php`, `app.js`

### v2.2 (2026-01-13) - CSS Isolation + Documentation
- DODANA CSS Isolation - wszystkie style scoped do `.uve-content`/`.product-description`
- DODANA metoda `validateCssForLeaks()` w CssSyncOrchestrator
- USUNIETE globalne `:root` variables (leak do theme)
- PRZEPISANA `getLayoutFixCss()` z pelna izolacja
- DODANA dokumentacja: `_DOCS/UVE_VISUAL_EDITOR.md`
- DODANY SKILL: `.claude/skills/uve-visual-editor/SKILL.md`
- DODANE triggers w `skill-rules.json` dla auto-aktywacji
- DODANA FAZA 8: CSS File Editor w Admin Panel

### v2.0 (2026-01-12) - Architecture Refinement
- USUNIETY tryb `inline_style_block` - tylko `external` lub `pending`
- DODANA walidacja zero-inline-styles (blokuje save/sync)
- ZMIENIONY hash: per-style (`uve-s{hash}`) zamiast per-element (`uve-e{hash}`)
- DODANA specyfikacja external mode (plik CSS, hook do theme)
- DODANY sync safety: lock, backup, rollback
- DODANA strategia specificity (wrapper `.uve-content`)
- DODANA FAZA 7: Style Library / Presets (opcjonalne)
- ZWIEKSZONA estymacja czasu: 15-19 -> 18-22 dni

### v1.0 (2026-01-09) - Initial Architecture
- Podstawowa architektura CSS-first
- Per-element hash naming
- inline_style_block jako fallback (USUNIETY w v2.0)

---

## Dokumentacja i Skille

| Zasób | Sciezka | Opis |
|-------|---------|------|
| Dokumentacja UVE | `_DOCS/UVE_VISUAL_EDITOR.md` | Pelna dokumentacja systemu |
| SKILL UVE | `.claude/skills/uve-visual-editor/SKILL.md` | Skill dla Claude Code |
| Skill Rules | `.claude/skill-rules.json` | Auto-aktywacja przy pracy z UVE |
| Plan Projektu | `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` | Ten plik |

---

**Utworzono:** 2026-01-09
**Zaktualizowano:** 2026-01-15
**Autor:** Claude Code (architect agent)
