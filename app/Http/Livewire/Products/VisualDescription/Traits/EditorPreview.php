<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use App\Services\VisualEditor\Styleset\StylesetCompiler;

/**
 * Trait EditorPreview.
 *
 * Handles preview generation and rendering.
 */
trait EditorPreview
{
    /**
     * Current preview mode: 'desktop', 'tablet', 'mobile'.
     */
    public string $previewMode = 'desktop';

    /**
     * Show live preview panel.
     */
    public bool $showPreview = true;

    /**
     * Set preview mode.
     */
    public function setPreviewMode(string $mode): void
    {
        if (in_array($mode, ['desktop', 'tablet', 'mobile'])) {
            $this->previewMode = $mode;
        }
    }

    /**
     * Toggle preview panel visibility.
     */
    public function togglePreview(): void
    {
        $this->showPreview = !$this->showPreview;
    }

    /**
     * Get preview HTML for all blocks.
     */
    public function getPreviewHtmlProperty(): string
    {
        return $this->generatePreviewHtml();
    }

    /**
     * Generate preview HTML from blocks.
     */
    protected function generatePreviewHtml(): string
    {
        if (empty($this->blocks)) {
            return '<div class="ve-preview-empty">Brak blokow</div>';
        }

        $registry = app(BlockRegistry::class);

        // Load dynamic blocks for current shop (each Livewire request is new PHP process)
        if (property_exists($this, 'shopId') && $this->shopId) {
            $registry->loadShopBlocks($this->shopId);
        }

        $html = '';

        // DEBUG: Log block types being rendered
        $blockTypes = array_map(fn($b) => $b['type'] ?? 'unknown', $this->blocks);
        \Log::info('[VE Preview] Generating HTML for blocks', [
            'block_count' => count($this->blocks),
            'block_types' => $blockTypes,
            'registry_has_prestashop_section' => $registry->has('prestashop-section'),
        ]);

        foreach ($this->blocks as $index => $block) {
            $blockType = $block['type'] ?? 'unknown';
            $blockInstance = $registry->get($blockType);

            if ($blockInstance) {
                $content = $block['data']['content'] ?? [];
                $settings = $block['data']['settings'] ?? [];
                $renderedHtml = $blockInstance->render($content, $settings);
                $html .= $renderedHtml;

                // DEBUG: Log each block render (only first 3 blocks for brevity)
                if ($index < 3) {
                    \Log::info("[VE Preview] Block {$index} rendered", [
                        'type' => $blockType,
                        'has_html_content' => !empty($content['html']),
                        'html_length' => strlen($renderedHtml),
                    ]);
                }
            } else {
                \Log::warning("[VE Preview] Block {$index} NOT REGISTERED", [
                    'type' => $blockType,
                    'available_types' => array_keys($registry->all()),
                ]);
            }
        }

        return $html;
    }

    /**
     * Get compiled CSS for preview.
     *
     * Returns PPM's internal styleset CSS only.
     * Shop-specific CSS is loaded via IFRAME isolation to prevent admin UI leaking.
     */
    public function getPreviewCssProperty(): string
    {
        if (!$this->shopId) {
            return '';
        }

        // Only return PPM's internal styleset CSS
        // Shop-specific PrestaShop CSS is loaded in isolated IFRAME
        $compiler = app(StylesetCompiler::class);
        return $compiler->getCompiledCssForShop($this->shopId, cached: false);
    }

    /**
     * Get preview width based on mode.
     */
    public function getPreviewWidthProperty(): string
    {
        return match ($this->previewMode) {
            'mobile' => '375px',
            'tablet' => '768px',
            default => '100%',
        };
    }

    /**
     * Render single block preview.
     */
    public function renderBlockPreview(int $index): string
    {
        if (!isset($this->blocks[$index])) {
            return '';
        }

        $block = $this->blocks[$index];
        $registry = app(BlockRegistry::class);
        $blockInstance = $registry->get($block['type']);

        if (!$blockInstance) {
            return '<div class="ve-block-error">Nieznany blok</div>';
        }

        $content = $block['data']['content'] ?? [];
        $settings = $block['data']['settings'] ?? [];
        return $blockInstance->render($content, $settings);
    }

    /**
     * Export blocks as HTML.
     */
    public function exportAsHtml(): string
    {
        $css = $this->getPreviewCssProperty();
        $html = $this->generatePreviewHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>{$css}</style>
</head>
<body>
    <div class="pd-wrapper">
        {$html}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Export blocks as JSON.
     */
    public function exportAsJson(): string
    {
        return json_encode([
            'version' => '1.0',
            'shop_id' => $this->shopId,
            'blocks' => $this->blocks,
            'exported_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import blocks from JSON.
     */
    public function importFromJson(string $json): bool
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['blocks']) || !is_array($data['blocks'])) {
                $this->dispatch('notify', type: 'error', message: 'Nieprawidlowy format JSON');
                return false;
            }

            // Regenerate block IDs
            $this->blocks = array_map(function ($block) {
                $block['id'] = $this->generateBlockId();
                return $block;
            }, $data['blocks']);

            $this->selectedBlockIndex = null;
            $this->pushUndoState();
            $this->isDirty = true;

            $this->dispatch('notify', type: 'success', message: 'Zaimportowano bloki');
            return true;
        } catch (\JsonException $e) {
            $this->dispatch('notify', type: 'error', message: 'Blad parsowania JSON');
            return false;
        }
    }

    /**
     * Get all PrestaShop CSS for isolated preview (theme.css + custom.css).
     *
     * Returns complete CSS bundle for IFRAME-based preview isolation.
     * This prevents CSS leaking into PPM admin UI.
     */
    public function getShopPreviewCssProperty(): string
    {
        if (!$this->shopId) {
            return '';
        }

        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            return '';
        }

        $cssParts = [];

        // 1. Google Fonts: Montserrat (required by KAYO/Warehouse theme)
        $cssParts[] = "/* Google Fonts */\n@import url('https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap');";

        // 2. Theme CSS variables (from analysis report)
        $cssParts[] = $this->getThemeCssVariables();

        // 3. PrestaShop custom.css (fetched via URL or FTP)
        $fetcher = app(PrestaShopCssFetcher::class);
        $prestaShopCss = $fetcher->getCssForPreview($shop);

        if ($prestaShopCss) {
            $cssParts[] = "/* PrestaShop Custom CSS: {$shop->name} */\n";
            $cssParts[] = $prestaShopCss;
        }

        return implode("\n\n", array_filter($cssParts));
    }

    /**
     * Get base theme CSS variables (common for Warehouse template).
     */
    protected function getThemeCssVariables(): string
    {
        return <<<'CSS'
/* === WAREHOUSE THEME BASE VARIABLES === */
:root {
  /* Layout */
  --max-content-width: 1300px;
  --max-text-width: 760px;
  --inline-padding: 1rem;

  /* Brand Colors (KAYO Orange) */
  --brand-color: #ef8248;
  --accent-color: #eb5e20;
  --dark-accent: #dd4819;
  --light-accent: #f5ad7c;

  /* Neutrals */
  --bg-neutral: #f6f6f6;
  --text-color: #212529;
  --border-color: #d1d1d1;

  /* Typography */
  --font-family: 'Montserrat', sans-serif;
}

/* Base body styling for preview */
body {
  font-family: var(--font-family);
  font-size: 16px;
  line-height: 1.4;
  color: var(--text-color);
  margin: 0;
  padding: 0;
}

/* Grid system for product description */
.product-description .rte-content, .pd-base-grid {
  --block-breakout: calc((var(--max-content-width) - var(--max-text-width)) / 2);
  display: grid;
  grid-column: 1 / -1;
  justify-content: center;
  grid-template-columns:
    [row-start] minmax(var(--inline-padding, 1rem), 1fr)
    [block-start] minmax(0, var(--block-breakout))
    [text-start] min(var(--max-text-width), 100% - 2 * var(--inline-padding))
    [text-end] minmax(0, var(--block-breakout))
    [block-end] minmax(var(--inline-padding, 1rem), 1fr)
    [row-end];
}

:where(.rte-content) > * { grid-column: text; }
:where(.rte-content) > :is(div, section) { grid-column: block; }
.grid-row { grid-column: 1 / -1; }
.pd-text-block { grid-column: text; }

/* Background utilities */
.bg-brand {
  --bg-color: #ef8248;
  background-color: rgb(239, 130, 72);
  /* NO color set here - text color inherited from parent/defaults - matches PrestaShop! */
}

.bg-neutral-accent {
  background-color: rgb(0, 0, 0);
  color: #fff;
}

/* === PD-ASSET-LIST STYLES (horizontal layout) === */
/* CRITICAL: Text must be BLACK on orange bg-brand - matches PrestaShop */
.pd-asset-list {
  display: flex !important;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 2rem 4rem;
  padding: 1.5rem 2rem;
  margin: 0;
  list-style: none;
  color: #000 !important; /* Override bg-brand white text - PrestaShop has black */
}

.pd-asset-list > li {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-width: 100px;
  flex: 0 0 auto;
  color: #000; /* Ensure black text */
}

.pd-asset-list > li b,
.pd-asset-list > li strong {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  margin-top: 0.25rem;
  color: #000; /* Ensure black text for bold values */
}

/* === PD-MODEL HEADING STYLES === */
/* CRITICAL: These styles must match PrestaShop exactly! */
/* Orange line is created via ::before on heading, NOT border on name */
.pd-intro__heading,
.pd-model {
  display: grid;
  grid-template-columns: 160px auto;
  grid-template-rows: auto auto;
  gap: 0 16px;
  margin-bottom: 1.5rem;
  font-size: clamp(2rem, 5vw, 3.5rem);
  line-height: 1;
  font-weight: 800;
}

/* Orange line via ::before pseudo-element */
.pd-intro__heading::before,
.pd-model::before {
  content: "";
  display: block;
  width: 160px;
  height: 12px;
  background-color: #eb5e20;
  grid-column: 1 / 2;
  grid-row: 2 / 3;
  align-self: center;
}

.pd-model__name {
  display: block;
  font-size: inherit;
  font-weight: 800;
  color: var(--text-color);
  grid-column: 1 / -1;
  grid-row: 1 / 2;
  /* NO border-bottom - matches PrestaShop original */
}

.pd-model__type {
  display: block;
  font-size: 0.7em;
  font-weight: 400;
  color: var(--text-color);
  grid-column: 2 / -1;
  grid-row: 2 / 3;
  align-self: center;
}
CSS;
    }

    /**
     * Get complete HTML document for IFRAME-based preview.
     *
     * This creates a complete, isolated HTML document that contains:
     * 1. PrestaShop-compatible HTML structure (#description > .product-description > .rte-content)
     * 2. All shop-specific CSS (theme variables + custom.css)
     * 3. Google Fonts (Montserrat)
     *
     * The IFRAME isolation ensures PrestaShop CSS cannot leak into PPM admin UI.
     */
    public function getIframeContent(): string
    {
        $css = $this->shopPreviewCss;
        $html = $this->generatePreviewHtml();
        $shopName = $this->shop?->name ?? 'Preview';

        // Build complete HTML document
        // NOTE: Do NOT use htmlspecialchars() here - Blade's {{ }} will handle escaping for srcdoc attribute
        // CRITICAL: Shop CSS ({$css}) comes LAST to override any fallback styles
        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {$shopName}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- NO Splide CSS from CDN - using shop CSS only (1:1 with PrestaShop) -->
    <style>
        /* === MINIMAL RESET === */
        *, *::before, *::after { box-sizing: border-box; }

        /* === IFRAME VIEWPORT SIMULATION === */
        /* html = viewport container (fixed height), body = scrollable content */
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden; /* html is the viewport container */
        }
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-y: auto; /* body scrolls vertically */
            overflow-x: hidden;
            background-color: #f6f6f6; /* Symuluje #product .tabs z PrestaShop */
        }

        /* === CSS VARIABLES (from Warehouse theme) === */
        :root {
            --inline-padding: 1rem;
            --max-content-width: 1300px;
            --max-text-width: 760px;
        }

        /* === BOOTSTRAP RESPONSIVE TEXT UTILITIES (from theme.css) === */
        /* These are NOT in custom.css, so we provide them as fallback */
        @media (min-width: 768px) {
            .text-md-center { text-align: center !important; }
            .text-md-start { text-align: left !important; }
            .text-md-end { text-align: right !important; }
        }
        @media (min-width: 992px) {
            .text-lg-center { text-align: center !important; }
            .text-lg-start { text-align: left !important; }
            .text-lg-end { text-align: right !important; }
        }

        /* === FALLBACK STYLES (will be overridden by shop CSS) === */
        .ve-preview-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            color: #6b7280;
            font-family: 'Montserrat', sans-serif;
        }

        /* Splide visibility fallback */
        .splide, .pd-slider {
            visibility: visible;
            opacity: 1;
        }

        /* Image fallback - prevent stretching */
        img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* === SHOP CSS (from PrestaShop custom.css) - OVERRIDES FALLBACKS === */
        {$css}
    </style>
</head>
<body>
    <!-- PrestaShop-compatible HTML structure -->
    <div id="description">
        <div class="product-description">
            <div class="rte-content">
                {$html}
            </div>
        </div>
    </div>

    <!-- Splide JS -->
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all Splide sliders with slider-v1 config (1 slide per page like PrestaShop)
            var sliders = document.querySelectorAll('.splide, .pd-slider');
            sliders.forEach(function(slider) {
                // Skip if already initialized
                if (slider.classList.contains('is-initialized')) return;

                try {
                    new Splide(slider, {
                        type: 'loop',
                        perPage: 1,
                        perMove: 1,
                        gap: '0',
                        pagination: true,
                        arrows: true,
                        autoplay: false,
                        speed: 400,
                        easing: 'ease'
                    }).mount();
                } catch(e) {
                    console.warn('Splide init error:', e);
                }
            });
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get shop instance for preview.
     */
    public function getShopProperty(): ?PrestaShopShop
    {
        if (!$this->shopId) {
            return null;
        }

        return PrestaShopShop::find($this->shopId);
    }

    /**
     * Get CSS namespace for shop.
     */
    public function getCssNamespaceProperty(): string
    {
        return 'pd';
    }

    /**
     * Get block count.
     */
    public function getBlockCountProperty(): int
    {
        return count($this->blocks ?? []);
    }

    /**
     * Get scoped CSS for Edit view canvas.
     *
     * Returns PrestaShop CSS scoped to .ve-canvas container to prevent
     * leaking into PPM admin UI while maintaining 1:1 visual parity with Preview.
     */
    public function getCanvasPreviewCssProperty(): string
    {
        if (!$this->shopId) {
            return '';
        }

        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            return '';
        }

        $cssParts = [];
        $container = '.ve-canvas .product-description .rte-content';

        // 1. Google Fonts - loaded via <link> tag in blade, not @import (browser compatibility)
        // See block-canvas.blade.php for the link tags

        // 2. Scoped base CSS variables and styles
        $cssParts[] = $this->getCanvasScopedBaseCss($container);

        // 3. PrestaShop CSS (scoped to container)
        $fetcher = app(PrestaShopCssFetcher::class);
        $prestaShopCss = $fetcher->getCssForPreview($shop);

        if ($prestaShopCss) {
            $scopedCss = $this->scopeCssToContainer($prestaShopCss, $container);
            $cssParts[] = "/* PrestaShop CSS (scoped): {$shop->name} */\n{$scopedCss}";
        }

        return implode("\n\n", array_filter($cssParts));
    }

    /**
     * Scope CSS selectors to a container.
     * Prevents PrestaShop CSS from affecting admin UI.
     */
    protected function scopeCssToContainer(string $css, string $container): string
    {
        // Replace :root with container
        $css = preg_replace('/:root\s*\{/', "{$container} {", $css);

        // Replace body/html selectors with container
        $css = preg_replace('/\bbody\s*\{/', "{$container} {", $css);
        $css = preg_replace('/\bhtml\s*\{/', "{$container} {", $css);

        // Replace universal selector at start of rule
        $css = preg_replace('/^\s*\*\s*\{/m', "{$container} * {", $css);

        // Prefix standalone selectors (not @rules, not already prefixed)
        $css = preg_replace_callback(
            '/^([^@{}\n][^{]*)\{/m',
            function ($matches) use ($container) {
                $selector = trim($matches[1]);

                // Skip if already prefixed or is @rule
                if (str_starts_with($selector, $container) || str_starts_with($selector, '@')) {
                    return $matches[0];
                }

                // Split by comma, prefix each, rejoin
                $parts = array_map(function ($s) use ($container) {
                    $s = trim($s);
                    if (empty($s) || str_starts_with($s, $container)) {
                        return $s;
                    }
                    return "{$container} {$s}";
                }, explode(',', $selector));

                return implode(', ', $parts) . ' {';
            },
            $css
        );

        return $css;
    }

    /**
     * Get scoped base CSS for Edit view canvas.
     */
    protected function getCanvasScopedBaseCss(string $container): string
    {
        return <<<CSS
/* === VE CANVAS SCOPED STYLES === */
/* Matches Preview 1:1 while preventing CSS leak to admin UI */

{$container} {
  /* CSS Variables */
  --max-content-width: 1300px;
  --max-text-width: 760px;
  --inline-padding: 1rem;
  --brand-color: #ef8248;
  --accent-color: #eb5e20;
  --dark-accent: #dd4819;
  --light-accent: #f5ad7c;
  --bg-neutral: #f6f6f6;
  --text-color: #212529;
  --border-color: #d1d1d1;
  --font-family: 'Montserrat', sans-serif;

  /* Base styling - !important to override admin UI fonts */
  font-family: 'Montserrat', sans-serif !important;
  font-size: 16px;
  line-height: 1.4;
  color: var(--text-color);
  background-color: #f6f6f6;
}

/* Ensure all text inside uses Montserrat */
{$container} * {
  font-family: inherit;
}

/* Grid system for product description */
{$container} {
  --block-breakout: calc((var(--max-content-width) - var(--max-text-width)) / 2);
  display: grid;
  grid-column: 1 / -1;
  justify-content: center;
  grid-template-columns:
    [row-start] minmax(var(--inline-padding, 1rem), 1fr)
    [block-start] minmax(0, var(--block-breakout))
    [text-start] min(var(--max-text-width), 100% - 2 * var(--inline-padding))
    [text-end] minmax(0, var(--block-breakout))
    [block-end] minmax(var(--inline-padding, 1rem), 1fr)
    [row-end];
}

:where({$container}) > * { grid-column: text; }
:where({$container}) > :is(div, section) { grid-column: block; }
{$container} .grid-row { grid-column: 1 / -1; }
{$container} .pd-text-block { grid-column: text; }

/* Background utilities */
{$container} .bg-brand {
  --bg-color: #ef8248;
  background-color: rgb(239, 130, 72);
}

{$container} .bg-neutral-accent {
  background-color: rgb(0, 0, 0);
  color: #fff;
}

/* === PD-ASSET-LIST STYLES === */
{$container} .pd-asset-list {
  display: flex !important;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 2rem 4rem;
  padding: 1.5rem 2rem;
  margin: 0;
  list-style: none;
  color: #000 !important;
}

{$container} .pd-asset-list > li {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-width: 100px;
  flex: 0 0 auto;
  color: #000;
}

{$container} .pd-asset-list > li b,
{$container} .pd-asset-list > li strong {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  margin-top: 0.25rem;
  color: #000;
}

/* === PD-INTRO CONTAINER (simulates PrestaShop layout) === */
/* In PrestaShop, pd-intro spans full width but content is in white box */
{$container} .pd-intro,
{$container} .pd-base-grid {
  grid-column: 1 / -1; /* Full width for backgrounds */
  display: grid;
  grid-template-columns: inherit;
  position: relative;
}

/* White background for heading and text within pd-intro */
/* Simulates pd-intro__copy wrapper from PrestaShop */
{$container} .pd-intro > .pd-intro__heading,
{$container} .pd-intro > .pd-model,
{$container} .pd-intro > .pd-intro__text {
  background: white;
  padding: 2rem;
  margin-left: auto;
  margin-right: auto;
  max-width: var(--max-text-width, 760px);
  width: 100%;
  box-sizing: border-box;
}

/* First element gets top padding, last text element gets bottom */
{$container} .pd-intro > .pd-intro__heading:first-child,
{$container} .pd-intro > .pd-model:first-child {
  padding-top: 3rem;
  border-radius: 8px 8px 0 0;
}

{$container} .pd-intro > .pd-intro__text:last-of-type {
  padding-bottom: 3rem;
  border-radius: 0 0 8px 8px;
  margin-bottom: 0;
}

/* pd-cover (buggy image) with orange background behind it */
{$container} .pd-cover {
  position: relative;
  z-index: 1;
  margin-top: -2rem; /* Overlap with orange section below */
}

{$container} .pd-cover img {
  display: block;
  margin: 0 auto;
  max-width: 100%;
  height: auto;
}

/* Orange background section - full width */
{$container} .grid-row.bg-brand {
  grid-column: 1 / -1;
  padding-top: 180px; /* Space for buggy overlap */
  margin-top: -180px;
  position: relative;
  z-index: 0;
}

/* Text blocks should be left-aligned */
{$container} p,
{$container} .pd-text-block {
  text-align: left;
}

/* === PD-MODEL HEADING STYLES === */
{$container} .pd-intro__heading,
{$container} .pd-model {
  display: grid;
  grid-template-columns: 160px auto;
  grid-template-rows: auto auto;
  gap: 0 16px;
  margin-bottom: 1.5rem;
  font-size: clamp(2rem, 5vw, 3.5rem);
  line-height: 1;
  font-weight: 800;
  text-align: left; /* Heading text left-aligned within centered container */
}

{$container} .pd-intro__heading::before,
{$container} .pd-model::before {
  content: "";
  display: block;
  width: 160px;
  height: 12px;
  background-color: #eb5e20;
  grid-column: 1 / 2;
  grid-row: 2 / 3;
  align-self: center;
}

{$container} .pd-model__name {
  display: block;
  font-size: inherit;
  font-weight: 800;
  color: var(--text-color);
  grid-column: 1 / -1;
  grid-row: 1 / 2;
}

{$container} .pd-model__type {
  display: block;
  font-size: 0.7em;
  font-weight: 400;
  color: var(--text-color);
  grid-column: 2 / -1;
  grid-row: 2 / 3;
  align-self: center;
}

/* Image fallback */
{$container} img {
  max-width: 100%;
  height: auto;
  object-fit: contain;
}
CSS;
    }
}
