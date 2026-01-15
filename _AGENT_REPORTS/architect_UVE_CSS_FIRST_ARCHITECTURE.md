# RAPORT ARCHITEKTONICZNY: UVE CSS-First Architecture

**Data**: 2026-01-09
**Agent**: architect
**Zadanie**: Przeprojektowanie UVE z inline styles na CSS classes
**Status**: ARCHITEKTURA ZAPROJEKTOWANA

---

## 1. ANALIZA PROBLEMU

### 1.1 Aktualny (problematyczny) przeplyw

```
Uzytkownik edytuje styl w Property Panel
        |
        v
UVE_PropertyPanel.php zapisuje do:
  - $this->elementStyles (camelCase)
  - $this->blocks[$index]['elementStyles'][$elementId]
        |
        v
UVE_BlockManagement.php kompiluje:
  - compileBlockHtml() -> applyElementStylesToHtml()
  - renderStyles() -> generuje inline string
        |
        v
WYNIK W PRESTASHOP:
<h2 style="font-family: Montserrat; font-size: 56px; font-weight: 800; width: 328.5px; height: 56px; display: block;">...</h2>
```

### 1.2 Zidentyfikowane problemy

| Problem | Wplyw | Priorytet |
|---------|-------|-----------|
| ZAKAZ inline styles w CLAUDE.md | Naruszone standardy projektu | KRYTYCZNY |
| Limit 65535 znakow w opisach PrestaShop | Obciecie opisow | WYSOKI |
| Problemy z renderowaniem (Firefox) | UX degradacja | SREDNI |
| Ogromne atrybuty style="" | Wydajnosc, czytelnosc | SREDNI |
| Duplikacja stylow (copy-paste blokow) | Rozmiar danych | NISKI |

### 1.3 Istniejaca infrastruktura CSS sync

Projekt posiada juz czesciowa infrastrukture do synchronizacji CSS:

- `CssSyncOrchestrator.php` - koordynator workflow
- `CssRuleGenerator.php` - generator regul CSS
- `CssPropertyMapper.php` - mapper camelCase -> kebab-case
- `CssDeploymentService.php` - deployment via FTP
- `PrestaShopShop.ftp_config` - konfiguracja FTP per sklep

**Problem:** Ta infrastruktura jest niewykorzystywana - inline styles sa nadal generowane.

---

## 2. ARCHITEKTURA CSS-FIRST

### 2.1 Diagram wysokopoziomowy

```
+-----------------------------------------------------------------+
|                    UVE Property Panel                            |
|  (uzytkownik edytuje styl elementu)                             |
+---------------------------+-------------------------------------+
                            |
                            v
+-----------------------------------------------------------------+
|              CSS Class Generator (NOWY)                          |
|  - Generuje unikalne klasy CSS: .uve-e{hash}                    |
|  - Zapisuje mapowanie elementId -> className                     |
|  - Zapisuje definicje CSS do ProductDescription->css_rules       |
+---------------------------+-------------------------------------+
                            |
            +---------------+---------------+
            v                               v
+-------------------------+     +---------------------------+
|   HTML Output           |     |   CSS Output              |
|   (opis produktu)       |     |   (zewnetrzny plik)       |
|                         |     |                           |
|   <h2 class="uve-       |     |   .uve-e7f3a2b1 {         |
|     e7f3a2b1">          |     |     font-size: 56px;      |
|                         |     |     font-weight: 800;     |
|                         |     |   }                       |
+-------------------------+     +-------------+-------------+
                                              |
                                              v
                                +---------------------------+
                                |  CSS Sync Service         |
                                |  - Upload via FTP         |
                                |  - Inject do <head>       |
                                |  - Per-shop CSS file      |
                                +---------------------------+
```

### 2.2 Tryby dostarczania CSS

| Tryb | Warunek | Opis | Zalety | Wady |
|------|---------|------|--------|------|
| `inline_style_block` | Brak FTP | `<style>` na poczatku opisu | Dziala bez konfiguracji | Duplikacja CSS per produkt |
| `external` | FTP skonfigurowane | Zewnetrzny uve-custom.css | Cacheowalny, jeden plik | Wymaga FTP |

### 2.3 Strategia generowania klas CSS

**Naming Convention:**
```
.uve-e{hash}

gdzie hash = substr(md5("{productId}-{shopId}-{elementId}"), 0, 8)
```

**Przyklady:**
- `.uve-e7f3a2b1` - naglowek w produkcie 1234
- `.uve-e8c4d5e2` - tekst w produkcie 1234

**Zalety:**
- Deterministyczny (ten sam element = ta sama klasa)
- Krotki (12 znakow)
- Unikalny per produkt/sklep
- Cache-friendly (nie zmienia sie przy edycji stylow)

---

## 3. STRUKTURA DANYCH

### 3.1 Nowe pola w ProductDescription

```sql
ALTER TABLE product_descriptions
ADD COLUMN css_rules JSON NULL AFTER blocks_v2 COMMENT 'Definicje CSS dla elementow',
ADD COLUMN css_class_map JSON NULL COMMENT 'Mapowanie elementId -> className',
ADD COLUMN css_mode ENUM('inline', 'inline_style_block', 'external') DEFAULT 'inline' COMMENT 'Tryb dostarczania CSS',
ADD COLUMN css_migrated_at TIMESTAMP NULL COMMENT 'Data migracji z inline styles';
```

### 3.2 Struktura css_rules (JSON)

```json
{
  "version": "1.0",
  "generated_at": "2026-01-09T10:30:00Z",
  "rules": {
    ".uve-e7f3a2b1": {
      "font-size": "56px",
      "font-weight": "800",
      "font-family": "Montserrat, serif"
    },
    ".uve-e7f3a2b1:hover": {
      "color": "#e0ac7e"
    },
    ".uve-e8c4d5e2": {
      "line-height": "1.6",
      "color": "#333"
    }
  },
  "responsive": {
    "@media (max-width: 768px)": {
      ".uve-e7f3a2b1": {
        "font-size": "32px"
      }
    }
  }
}
```

### 3.3 Struktura css_class_map (JSON)

```json
{
  "block-0-heading-0": "uve-e7f3a2b1",
  "block-0-text-0": "uve-e8c4d5e2",
  "block-1-img-0": "uve-e9d6f7g3"
}
```

---

## 4. ZMIANY W PLIKACH

### 4.1 Nowy trait: UVE_CssClassGeneration.php

```php
<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

trait UVE_CssClassGeneration
{
    public array $cssRules = [];
    public array $cssClassMap = [];
    public bool $cssDirty = false;

    /**
     * Generate deterministic CSS class name for element
     */
    protected function generateCssClassName(int $productId, int $shopId, string $elementId): string
    {
        $hash = substr(md5("{$productId}-{$shopId}-{$elementId}"), 0, 8);
        return "uve-e{$hash}";
    }

    /**
     * Add or update CSS rule for element
     */
    protected function setCssRule(string $elementId, array $styles): void
    {
        $className = $this->generateCssClassName($this->productId, $this->shopId, $elementId);

        // Convert camelCase to kebab-case CSS properties
        $cssProperties = [];
        foreach ($styles as $prop => $value) {
            if ($value === '' || $value === null) continue;
            $cssProp = strtolower(preg_replace('/([A-Z])/', '-$1', $prop));
            $cssProperties[$cssProp] = $value;
        }

        if (!empty($cssProperties)) {
            $this->cssRules[".$className"] = $cssProperties;
            $this->cssClassMap[$elementId] = $className;
            $this->cssDirty = true;
        }
    }

    /**
     * Get CSS class for element
     */
    protected function getCssClassForElement(string $elementId): string
    {
        return $this->cssClassMap[$elementId] ?? '';
    }

    /**
     * Generate inline <style> block for fallback mode
     */
    protected function generateStyleBlock(): string
    {
        if (empty($this->cssRules)) {
            return '';
        }

        $css = "/* UVE Generated CSS - Product {$this->productId} */\n";
        foreach ($this->cssRules as $selector => $properties) {
            $css .= "$selector {\n";
            foreach ($properties as $prop => $value) {
                $css .= "  $prop: $value;\n";
            }
            $css .= "}\n";
        }

        return "<style>\n$css</style>\n";
    }

    /**
     * Determine CSS delivery mode based on shop config
     */
    protected function determineCssMode(): string
    {
        $shop = \App\Models\PrestaShopShop::find($this->shopId);

        if ($shop && $shop->ftp_config && !empty($shop->ftp_config['host'])) {
            return 'external';
        }

        return 'inline_style_block';
    }
}
```

### 4.2 Zmiany w UVE_PropertyPanel.php

**BYŁO:**
```php
protected function updateNormalControlValue(string $controlId, mixed $value): void
{
    // ... existing code ...

    // CRITICAL FIX: For raw-html blocks, save styles directly to block
    if (!$found && $this->editingBlockIndex !== null) {
        $this->blocks[$this->editingBlockIndex]['elementStyles'][$this->selectedElementId] = $this->elementStyles;
    }
}
```

**BEDZIE:**
```php
protected function updateNormalControlValue(string $controlId, mixed $value): void
{
    $service = $this->getPropertyPanelService();
    $cssProperties = $service->formatToCss($controlId, $value);

    // Update local styles for UI sync
    $this->elementStyles = array_merge($this->elementStyles, $cssProperties);
    $this->elementStyles = array_filter($this->elementStyles, fn($v) => $v !== '' && $v !== null);

    // NOWE: Save to CSS rules instead of elementStyles
    $this->setCssRule($this->selectedElementId, $this->elementStyles);

    // Sync to iframe with class name
    $this->syncToIframeWithClass();
}

protected function syncToIframeWithClass(): void
{
    $className = $this->getCssClassForElement($this->selectedElementId);

    $jsData = json_encode([
        'elementId' => $this->selectedElementId,
        'className' => $className,
        'styles' => $this->elementStyles, // for instant preview
    ]);

    $this->js("
        if (window.uveApplyStyles) {
            window.uveApplyStyles({$jsData});
        }
    ");
}
```

### 4.3 Zmiany w UVE_BlockManagement.php

**BYŁO:**
```php
protected function applyElementStylesToHtml(string $html, array $elementStyles): string
{
    // ... wstrzykuje style="" attribute ...
    if (!empty($cssString)) {
        $attrs = preg_replace('/style=["\'][^"\']*["\']/', 'style="' . $cssString . '"', $attrs);
    }
}
```

**BEDZIE:**
```php
protected function applyElementClassesToHtml(string $html, array $classMap): string
{
    if (empty($classMap) || empty($html)) {
        return $html;
    }

    foreach ($classMap as $elementId => $className) {
        // Pattern: find element with data-uve-id
        $pattern = '/(<[^>]*data-uve-id=["\']' . preg_quote($elementId, '/') . '["\'])([^>]*)(>)/';

        $html = preg_replace_callback($pattern, function ($matches) use ($className) {
            $prefix = $matches[1];
            $attrs = $matches[2];
            $close = $matches[3];

            // REMOVE any existing style attribute
            $attrs = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $attrs);

            // ADD or MERGE class attribute
            if (preg_match('/class=["\']([^"\']*)["\']/', $attrs, $classMatch)) {
                $existingClasses = $classMatch[1];
                if (strpos($existingClasses, $className) === false) {
                    $attrs = preg_replace(
                        '/class=["\']([^"\']*)["\']/',
                        'class="$1 ' . $className . '"',
                        $attrs
                    );
                }
            } else {
                $attrs .= ' class="' . $className . '"';
            }

            return $prefix . $attrs . $close;
        }, $html);
    }

    return $html;
}
```

### 4.4 Zmiany w save() method

```php
public function save(): void
{
    // ... existing validation ...

    // Compile CSS rules from all edited elements
    $this->compileCssRulesFromCache();

    // Determine CSS delivery mode
    $cssMode = $this->determineCssMode();

    // Compile HTML with CSS classes (not inline styles!)
    foreach ($this->blocks as $index => $block) {
        $this->compileBlockHtmlWithClasses($index);
    }

    // Generate final HTML
    $html = $this->generateRenderedHtml();

    // For inline_style_block mode: prepend <style> block
    if ($cssMode === 'inline_style_block' && !empty($this->cssRules)) {
        $html = $this->generateStyleBlock() . $html;
    }

    // Save to database
    $this->description->update([
        'blocks_v2' => $this->blocks,
        'rendered_html' => $html,
        'css_rules' => $this->cssRules,
        'css_class_map' => $this->cssClassMap,
        'css_mode' => $cssMode,
    ]);

    // Trigger external CSS sync if configured
    if ($cssMode === 'external' && $this->cssDirty) {
        \App\Jobs\VisualEditor\SyncProductCssJob::dispatch(
            $this->description,
            $this->cssRules
        );
    }

    $this->isDirty = false;
    $this->cssDirty = false;
    $this->dispatch('notify', type: 'success', message: 'Zapisano');
}
```

---

## 5. MECHANIZM AKTUALIZACJI CSS PRESTASHOP

### 5.1 Struktura pliku uve-custom.css

```css
/* /themes/[theme]/css/uve-custom.css */
/* Managed by PPM Visual Editor - DO NOT EDIT MANUALLY */

/* ========== UVE Global Styles ========== */
.uve-content {
  font-family: inherit;
  line-height: inherit;
}

/* ========== Product 1234 ========== */
/* @uve-product-start: 1234 */
.uve-e7f3a2b1 {
  font-size: 56px;
  font-weight: 800;
  font-family: Montserrat, serif;
}
.uve-e8c4d5e2 {
  line-height: 1.6;
  color: #333;
}
/* @uve-product-end: 1234 */

/* ========== Product 5678 ========== */
/* @uve-product-start: 5678 */
.uve-ea1b2c3d {
  background: linear-gradient(135deg, #e0ac7e, #d1975a);
}
/* @uve-product-end: 5678 */
```

### 5.2 Job: SyncProductCssJob

```php
<?php

namespace App\Jobs\VisualEditor;

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductCssJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ProductDescription $description,
        public array $cssRules
    ) {}

    public function handle(CssSyncOrchestrator $orchestrator): void
    {
        $orchestrator->syncProductCss(
            $this->description,
            $this->cssRules
        );
    }
}
```

### 5.3 Rozszerzenie CssSyncOrchestrator

```php
/**
 * Sync CSS for single product to PrestaShop
 */
public function syncProductCss(ProductDescription $description, array $cssRules): array
{
    $shop = PrestaShopShop::find($description->shop_id);
    if (!$shop || !$this->isCssSyncEnabled($shop)) {
        return ['success' => false, 'error' => 'FTP not configured'];
    }

    // 1. Fetch existing uve-custom.css
    $existingCss = $this->fetchUveCustomCss($shop);

    // 2. Generate CSS for this product
    $productCss = $this->formatProductCss($description->product_id, $cssRules);

    // 3. Replace product section in existing CSS
    $mergedCss = $this->replaceProductSection(
        $existingCss,
        $description->product_id,
        $productCss
    );

    // 4. Upload merged CSS
    return $this->uploadUveCustomCss($shop, $mergedCss);
}

/**
 * Replace product section in CSS file
 */
protected function replaceProductSection(string $css, int $productId, string $productCss): string
{
    $startMarker = "/* @uve-product-start: {$productId} */";
    $endMarker = "/* @uve-product-end: {$productId} */";

    $startPos = strpos($css, $startMarker);
    $endPos = strpos($css, $endMarker);

    if ($startPos !== false && $endPos !== false) {
        // Replace existing section
        $before = substr($css, 0, $startPos);
        $after = substr($css, $endPos + strlen($endMarker));

        return rtrim($before) . "\n\n" . $productCss . "\n" . ltrim($after);
    }

    // Append new section
    return rtrim($css) . "\n\n" . $productCss;
}
```

---

## 6. PLAN MIGRACJI ISTNIEJACYCH OPISOW

### 6.1 Fazy migracji

| Faza | Opis | Czas |
|------|------|------|
| 1. Dual Mode | Nowe = CSS classes, istniejace = inline | Natychmiast |
| 2. Auto-migration | Migracja przy edycji istniejacego opisu | On-demand |
| 3. Bulk migration | Komenda do masowej migracji | Opcjonalnie |

### 6.2 Service: InlineStyleMigrator

```php
<?php

namespace App\Services\VisualEditor;

class InlineStyleMigrator
{
    /**
     * Migrate inline styles to CSS classes
     */
    public function migrate(string $html, int $productId, int $shopId): array
    {
        if (empty($html)) {
            return ['html' => $html, 'css_rules' => [], 'class_map' => []];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $cssRules = [];
        $classMap = [];

        $xpath = new \DOMXPath($dom);
        $elementsWithStyle = $xpath->query('//*[@style]');

        foreach ($elementsWithStyle as $element) {
            $styleAttr = $element->getAttribute('style');
            $elementId = $element->getAttribute('data-uve-id');

            if (empty($elementId)) {
                $elementId = $this->generateElementId($element);
                $element->setAttribute('data-uve-id', $elementId);
            }

            // Parse inline styles
            $styles = $this->parseInlineStyles($styleAttr);

            // Generate CSS class
            $className = $this->generateClassName($productId, $shopId, $elementId);

            // Store rule
            $cssRules[".$className"] = $styles;
            $classMap[$elementId] = $className;

            // Replace style with class
            $element->removeAttribute('style');
            $existingClasses = $element->getAttribute('class') ?: '';
            $element->setAttribute('class', trim("$existingClasses $className"));
        }

        // Extract body content
        $body = $dom->getElementsByTagName('body')->item(0);
        $migratedHtml = '';
        foreach ($body->childNodes as $child) {
            $migratedHtml .= $dom->saveHTML($child);
        }

        return [
            'html' => $migratedHtml,
            'css_rules' => $cssRules,
            'class_map' => $classMap,
        ];
    }

    protected function parseInlineStyles(string $styleStr): array
    {
        $styles = [];
        $pairs = array_filter(array_map('trim', explode(';', $styleStr)));

        foreach ($pairs as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $prop = trim($parts[0]);
                $value = trim($parts[1]);
                $styles[$prop] = $value;
            }
        }

        return $styles;
    }

    protected function generateClassName(int $productId, int $shopId, string $elementId): string
    {
        $hash = substr(md5("{$productId}-{$shopId}-{$elementId}"), 0, 8);
        return "uve-e{$hash}";
    }

    protected function generateElementId(\DOMElement $element): string
    {
        return 'el_' . substr(md5($element->getNodePath()), 0, 8);
    }
}
```

### 6.3 Bulk Migration Command

```php
<?php

namespace App\Console\Commands;

use App\Models\ProductDescription;
use App\Services\VisualEditor\InlineStyleMigrator;
use Illuminate\Console\Command;

class MigrateUveInlineStyles extends Command
{
    protected $signature = 'uve:migrate-inline-styles
                           {--shop= : Migrate only specific shop}
                           {--dry-run : Preview without saving}
                           {--limit=100 : Limit number of descriptions}';

    protected $description = 'Migrate UVE inline styles to CSS classes';

    public function handle(InlineStyleMigrator $migrator): int
    {
        $query = ProductDescription::whereNotNull('rendered_html')
            ->where('css_mode', 'inline')
            ->where('rendered_html', 'LIKE', '%style=%');

        if ($shopId = $this->option('shop')) {
            $query->where('shop_id', $shopId);
        }

        $descriptions = $query->limit($this->option('limit'))->get();

        $this->info("Found {$descriptions->count()} descriptions to migrate");

        $bar = $this->output->createProgressBar($descriptions->count());
        $migrated = 0;

        foreach ($descriptions as $description) {
            $result = $migrator->migrate(
                $description->rendered_html,
                $description->product_id,
                $description->shop_id
            );

            if (!$this->option('dry-run')) {
                $description->update([
                    'rendered_html' => $result['html'],
                    'css_rules' => $result['css_rules'],
                    'css_class_map' => $result['class_map'],
                    'css_mode' => 'inline_style_block',
                    'css_migrated_at' => now(),
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated $migrated descriptions");

        return Command::SUCCESS;
    }
}
```

---

## 7. HARMONOGRAM IMPLEMENTACJI

| Etap | Zadania | Czas | Pliki |
|------|---------|------|-------|
| **1. Infrastruktura** | Migracja DB, nowy trait | 2-3 dni | migration, UVE_CssClassGeneration.php |
| **2. Generowanie klas** | generateCssClassName(), updateNormalControlValue() | 2-3 dni | UVE_PropertyPanel.php |
| **3. Kompilacja HTML** | applyElementClassesToHtml(), generateStyleBlock() | 2-3 dni | UVE_BlockManagement.php |
| **4. CSS Sync** | SyncProductCssJob, rozszerzenie orchestratora | 3-4 dni | Jobs/, CssSyncOrchestrator.php |
| **5. Migracja** | InlineStyleMigrator, command | 2-3 dni | Services/, Commands/ |
| **6. Testy** | E2E, performance | 2-3 dni | tests/ |

**RAZEM: 15-19 dni roboczych**

---

## 8. KORZYSCI

| Aspekt | Przed | Po | Poprawa |
|--------|-------|-----|---------|
| Rozmiar HTML | ~10KB+ per element | ~50B per element | -95% |
| Zgodnosc z CLAUDE.md | NARUSZONA | ZGODNA | 100% |
| Limit PrestaShop | Czesto przekraczany | Nigdy | 100% |
| Cacheowalnosc CSS | Brak | Pelna (external mode) | +100% |
| Edycja globalna | Niemozliwa | Mozliwa (przez CSS) | Nowa funkcja |

---

## 9. RYZYKA I MITIGACJE

| Ryzyko | Prawdopodobienstwo | Mitigacja |
|--------|-------------------|-----------|
| Konflikt CSS z theme PS | Srednie | Prefix `.uve-content` dla wszystkich regul |
| FTP timeout przy sync | Niskie | Retry logic w job, async queue |
| Regression w istniejacych opisach | Srednie | Dual mode, feature flag, auto-migracja tylko przy edycji |
| Wydajnosc przy wielu produktach | Niskie | Batch sync, incremental update |

---

## 10. NASTEPNE KROKI

1. **Zatwierdzenie architektury** przez uzytkownika
2. **Utworzenie ETAP_07h_UVE_CSS_First.md** w Plan_Projektu/
3. **Implementacja w kolejnosci etapow** (1 -> 6)
4. **Testy na staging** przed wdrozeniem
5. **Dokumentacja** dla uzytkownikow

---

**Autor:** architect agent
**Wersja:** 1.0
**Plik:** `_AGENT_REPORTS/architect_UVE_CSS_FIRST_ARCHITECTURE.md`
