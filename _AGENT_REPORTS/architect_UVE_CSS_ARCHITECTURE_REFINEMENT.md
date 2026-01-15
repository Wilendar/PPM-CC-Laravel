# RAPORT ARCHITEKTONICZNY: UVE CSS Architecture Refinement v2.0

**Data:** 2026-01-12
**Agent:** architect
**Zadanie:** Refinement architektury UVE CSS-First na podstawie feedbacku uzytkownika
**Status:** ARCHITEKTURA ZAKTUALIZOWANA

---

## 1. KONTEKST

Uzytkownik wskazal krytyczne luki w pierwotnym planie ETAP_07h_UVE_CSS_First.md (v1.0).
Ten raport dokumentuje zmiany wprowadzone w wersji 2.0.

---

## 2. ZIDENTYFIKOWANE PROBLEMY (v1.0)

| Problem | Wplyw | Priorytet |
|---------|-------|-----------|
| `inline_style_block` fallback problematyczny | Obchodzi zero-inline policy | KRYTYCZNY |
| Domyslny css_mode 'inline' otwiera backdoor | Brak twardej walidacji | KRYTYCZNY |
| Tryb 'external' niejasny | Brak specyfikacji pliku/hooka | WYSOKI |
| Brak zabezpieczen sync | Race conditions, brak backup | WYSOKI |
| Specificity/scoping | `.uve-*` przegrywa z theme | SREDNI |
| Per-element hash | Duplikacja CSS dla tych samych styli | SREDNI |
| Brak Style Library | Missed opportunity for reuse | NISKI |

---

## 3. WPROWADZONE ZMIANY (v2.0)

### 3.1 ELIMINACJA inline_style_block

**PRZED (v1.0):**
```sql
css_mode ENUM('inline', 'inline_style_block', 'external') DEFAULT 'inline'
```

**PO (v2.0):**
```sql
css_mode ENUM('external', 'pending') DEFAULT 'pending'
```

**UZASADNIENIE:**
- `<style>` block w opisie nadal zuzywa limity znakow (65535)
- Embedded content jest problematyczny (parsowanie, caching)
- CSS w opisie przegrywa z theme specificity
- Nie spelnia zasady "zero inline styles" (semantycznie nadal inline)

**KONSEKWENCJA:**
- Brak FTP = brak sync (tryb `pending`)
- UI musi wyswietlac ostrzezenie: "Skonfiguruj FTP aby synchronizowac style"

---

### 3.2 WALIDACJA ZERO-INLINE-STYLES

**NOWA FUNKCJA:**
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

**PUNKTY WALIDACJI:**
1. `save()` - PRZED zapisem do DB
2. `SyncProductCssJob` - PRZED sync do PrestaShop
3. `InlineStyleMigrator` - PO migracji (weryfikacja sukcesu)

**DZIALANIE:**
- Znalezione violations = BLOKUJ operacje
- Loguj do Laravel logs
- Wyswietl error w UI

---

### 3.3 PER-STYLE HASH (zamiast per-element)

**PRZED (v1.0):**
```php
// Per-element hash
$hash = md5("{$productId}-{$shopId}-{$elementId}");
// Result: .uve-e7f3a2b1

// PROBLEM: Te same style = rozne klasy
// Element A: font-size:56px -> .uve-eAAAAAAA
// Element B: font-size:56px -> .uve-eBBBBBBB
// CSS: .uve-eAAAAAAA { font-size: 56px; }
//      .uve-eBBBBBBB { font-size: 56px; } <- DUPLIKACJA!
```

**PO (v2.0):**
```php
// Per-style hash
protected function generateStyleHash(array $styles): string
{
    ksort($styles); // Canonicalize
    $canonical = json_encode($styles, JSON_UNESCAPED_SLASHES);
    return 'uve-s' . substr(md5($canonical), 0, 8);
}

// RESULT: Te same style = ta sama klasa
// Element A: font-size:56px -> .uve-s7f3a2b1
// Element B: font-size:56px -> .uve-s7f3a2b1 <- REUSE!
// CSS: .uve-s7f3a2b1 { font-size: 56px; } <- TYLKO RAZ!
```

**NAMING CONVENTION:**
- `uve-e*` -> per-element (v1.0, deprecated)
- `uve-s*` -> per-style (v2.0, current)
- `uve-sp-*` -> style preset (v2.0, FAZA 7)

**KORZYSCI:**
- Mniejszy plik CSS (brak duplikacji)
- Lepsze cachowanie
- Latwiejsza analiza (hash = unikalne style)

---

### 3.4 EXTERNAL MODE - PELNA SPECYFIKACJA

**PLIK DOCELOWY:**
```
/themes/{theme}/css/uve-custom.css
```

**HOOK DO PRESTASHOP:**

Opcja A - Module:
```php
// modules/ppm_uve/ppm_uve.php
public function hookDisplayHeader($params)
{
    return '<link rel="stylesheet" href="' .
           $this->context->shop->getBaseURL() .
           'themes/' . $this->context->shop->theme->getName() .
           '/css/uve-custom.css?v=' . time() . '">';
}
```

Opcja B - Theme template:
```smarty
{* themes/{theme}/templates/_partials/head.tpl *}
<link rel="stylesheet" href="{$urls.theme_assets}css/uve-custom.css" type="text/css">
```

**STRUKTURA PLIKU:**
```css
/* PPM UVE Custom Styles - AUTO-GENERATED - DO NOT EDIT */
/* Generated: 2026-01-12T10:30:00Z */
/* Version: v2.0.0 */

/* ========== STYLE DEFINITIONS ========== */
/* @uve-styles-start */
.uve-content .uve-s7f3a2b1 { font-size: 56px; font-weight: 800; }
.uve-content .uve-s8c4d5e2 { line-height: 1.6; color: #333; }
/* @uve-styles-end */

/* ========== PRODUCT MAPPINGS ========== */
/* @uve-mappings-start */
/* Product 1234: uve-s7f3a2b1, uve-s8c4d5e2 */
/* Product 5678: uve-s7f3a2b1 */
/* @uve-mappings-end */
```

---

### 3.5 SYNC SAFETY - LOCK, BACKUP, ROLLBACK

**PROBLEM:** Race conditions gdy wiele produktow syncuje rownoczesnie

**ROZWIAZANIE:**

```php
class CssSyncOrchestrator
{
    public function syncProductCss(ProductDescription $description, array $cssRules): array
    {
        $shop = PrestaShopShop::find($description->shop_id);

        // 1. LOCK - atomic per-shop
        if (!$this->acquireSyncLock($shop->id)) {
            return ['success' => false, 'error' => 'Sync in progress'];
        }

        try {
            // 2. BACKUP - przed kazdym sync
            $backupPath = $this->backupCurrentCss($shop);

            // 3. FETCH - aktualne CSS
            $existingCss = $this->fetchUveCustomCss($shop);

            // 4. REPLACE - FULL REPLACE sekcji (nie append!)
            $mergedCss = $this->replaceStylesSection($existingCss, $cssRules);

            // 5. UPLOAD
            $result = $this->uploadCss($shop, $mergedCss);

            if (!$result['success']) {
                // 6. ROLLBACK on failure
                $this->rollback($backupPath, $shop);
            }

            // 7. CLEANUP old backups (keep 5)
            $this->cleanupBackups($shop->id);

            return $result;

        } finally {
            // 8. RELEASE lock
            $this->releaseSyncLock($shop->id);
        }
    }

    protected function acquireSyncLock(int $shopId): bool
    {
        return Cache::lock("uve_css_sync_shop_{$shopId}", 30)->get();
    }

    protected function backupCurrentCss(PrestaShopShop $shop): string
    {
        $css = $this->fetchExistingCss($shop, true);
        $path = "uve_backup/{$shop->id}/" . date('Y-m-d_His') . '.css';
        Storage::put($path, $css);
        return $path;
    }

    protected function rollback(string $backupPath, PrestaShopShop $shop): void
    {
        $css = Storage::get($backupPath);
        $this->uploadCss($shop, $css);
        Log::warning('CssSyncOrchestrator: Rollback performed', [
            'shop_id' => $shop->id,
            'backup' => $backupPath,
        ]);
    }
}
```

**KRYTYCZNE:** FULL REPLACE, nie append!
- Stara logika: append nowych regul -> duplikacja
- Nowa logika: replace calej sekcji @uve-styles-start do @uve-styles-end

---

### 3.6 SPECIFICITY STRATEGY

**PROBLEM:**
```css
/* Theme CSS */
.product-description h2 { font-size: 24px !important; }

/* UVE CSS */
.uve-s7f3a2b1 { font-size: 56px; } /* PRZEGRYWA! */
```

**ROZWIAZANIE - Wrapper Scope:**

HTML output:
```html
<div class="uve-content" data-uve-version="2">
    <h2 class="uve-s7f3a2b1">KAYO S200</h2>
</div>
```

CSS output:
```css
/* Higher specificity */
.uve-content .uve-s7f3a2b1 { font-size: 56px; }

/* Lub jeszcze mocniej: */
.uve-content[data-uve-version="2"] .uve-s7f3a2b1 { font-size: 56px; }
```

**DODATKOWA STRATEGIA:**
- Preferuj istniejace klasy PrestaShop (`pd-*`)
- Mapuj common styles: `.pd-title`, `.pd-text`, `.pd-price`

---

### 3.7 STYLE LIBRARY / PRESETS (FAZA 7)

**NOWA FAZA (opcjonalna):**

```json
{
    "presets": {
        "heading-primary": {
            "class": "uve-sp-heading-primary",
            "styles": {
                "font-size": "56px",
                "font-weight": "800"
            }
        }
    }
}
```

**UI:**
- Dropdown "Apply Preset" w Property Panel
- Button "Save as Preset"
- Library browser

**KORZYSCI:**
- Konsystentne style
- Szybsza edycja
- Mniejszy CSS

---

## 4. DIAGRAM ARCHITEKTURY v2.0

```
+-----------------------------------------------------------------------+
|                         UVE Property Panel                              |
|  (uzytkownik edytuje style)                                            |
+--------------------------------+--------------------------------------+
                                 |
                                 v
+-----------------------------------------------------------------------+
|                    CSS Class Generator (v2.0)                          |
|  - generateStyleHash(styles) -> .uve-s{hash}                           |
|  - cssRules: {".uve-s7f3a2b1": {...}}                                  |
|  - cssClassMap: {"element-id": "uve-s7f3a2b1"}                         |
+--------------------------------+--------------------------------------+
                                 |
                                 v
+-----------------------------------------------------------------------+
|                    VALIDATION LAYER                                    |
|  - validateZeroInlineStyles(html)                                      |
|  - BLOKUJ jesli style="" lub <style> znalezione                        |
+--------------------------------+--------------------------------------+
                                 |
         +-----------------------+------------------------+
         |                                                |
         v                                                v
+------------------+                          +----------------------+
|   HTML Output    |                          |   CSS Sync Service   |
|                  |                          |                      |
| <div class=      |                          | 1. acquireLock()     |
|   "uve-content"> |                          | 2. backup()          |
|   <h2 class=     |                          | 3. fetch()           |
|     "uve-s...">  |                          | 4. REPLACE section   |
| </div>           |                          | 5. upload()          |
|                  |                          | 6. rollback on fail  |
| rendered_html DB |                          | 7. releaseLock()     |
+------------------+                          +----------+-----------+
                                                         |
                                                         v
                                              +----------------------+
                                              | /themes/{theme}/css/ |
                                              | uve-custom.css       |
                                              |                      |
                                              | .uve-content .uve-s* |
                                              +----------------------+
```

---

## 5. ZMIANY W PLIKACH

### Zaktualizowane pliki:
| Plik | Zmiana |
|------|--------|
| `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` | Kompletna aktualizacja v2.0 |

### Pliki wymagajace aktualizacji (ETAP implementacji):
| Plik | Zmiana |
|------|--------|
| `database/migrations/xxx_add_css_columns.php` | Zmiana ENUM css_mode |
| `UVE_CssClassGeneration.php` | Per-style hash, validateZeroInlineStyles() |
| `UVE_BlockManagement.php` | Wrapper .uve-content, usunac inline_style_block |
| `CssSyncOrchestrator.php` | Lock, backup, rollback, FULL REPLACE |
| `SyncProductCssJob.php` | Walidacja pre-sync |

---

## 6. ESTYMACJA CZASU

| Faza | v1.0 | v2.0 | Roznica |
|------|------|------|---------|
| FAZA 1-4 | 8-12 dni | 9-14 dni | +1-2 dni (aktualizacje) |
| FAZA 5 | 3-4 dni | 4-5 dni | +1 dzien (safety features) |
| FAZA 6 | 2-3 dni | 2-3 dni | bez zmian |
| FAZA 7 | N/A | 3-4 dni | NOWE (opcjonalne) |
| **TOTAL** | 15-19 dni | 18-22 dni | +3 dni |

---

## 7. PODSUMOWANIE

### Kluczowe zmiany v2.0:
1. **ZERO KOMPROMISOW** - usuniety inline_style_block
2. **TWARDA WALIDACJA** - blokuj save/sync z inline styles
3. **MADRZEJSZY HASH** - per-style zamiast per-element
4. **PELNA SPECYFIKACJA** - external mode z plikiem i hookiem
5. **SYNC SAFETY** - lock, backup, rollback
6. **SPECIFICITY** - wrapper .uve-content

### Nastepne kroki:
1. Zatwierdzenie planu v2.0 przez uzytkownika
2. Aktualizacja FAZ 2, 4, 5 zgodnie z nowymi wymaganiami
3. Implementacja walidacji zero-inline
4. Implementacja sync safety
5. Testy E2E

---

**Autor:** architect agent
**Wersja raportu:** 1.0
**Plik:** `_AGENT_REPORTS/architect_UVE_CSS_ARCHITECTURE_REFINEMENT.md`
