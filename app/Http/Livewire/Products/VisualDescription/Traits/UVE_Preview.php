<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Livewire\Attributes\Computed;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Cache;

/**
 * UVE Preview Trait - ETAP_07f_P5
 *
 * Generowanie preview opisu produktu z CSS z PrestaShop.
 * Obsluga IFRAME isolation i responsive breakpoints.
 */
trait UVE_Preview
{
    // =====================
    // PREVIEW DEVICE
    // =====================

    /**
     * Set preview device size
     */
    public function setPreviewDevice(string $device): void
    {
        if (in_array($device, ['desktop', 'tablet', 'mobile'])) {
            $this->previewDevice = $device;

            // Dispatch event to notify iframe about device change
            $this->dispatch('uve-device-changed', [
                'device' => $device,
                'width' => $this->previewWidth(),
            ]);
        }
    }

    // =====================
    // ELEMENT STYLE SYNC (FAZA PP.4)
    // =====================

    /**
     * Update element styles in iframe via postMessage
     * Called from Property Panel when styles change
     *
     * @param string $elementId Element ID (data-uve-id)
     * @param array $styles CSS styles to apply
     * @param array $hoverStyles Optional hover styles
     */
    public function updateElementStyle(string $elementId, array $styles, array $hoverStyles = []): void
    {
        // Format styles for JavaScript (camelCase to kebab-case)
        $formattedStyles = [];
        foreach ($styles as $property => $value) {
            $kebabProperty = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
            $formattedStyles[$kebabProperty] = $value;
        }

        $formattedHoverStyles = [];
        foreach ($hoverStyles as $property => $value) {
            $kebabProperty = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
            $formattedHoverStyles[$kebabProperty] = $value;
        }

        // Dispatch event for Alpine to send postMessage to iframe
        $this->dispatch('uve-update-element', [
            'elementId' => $elementId,
            'styles' => $formattedStyles,
            'hoverStyles' => $formattedHoverStyles,
        ]);
    }

    /**
     * Update element content in iframe
     *
     * @param string $elementId Element ID
     * @param string $content New HTML content
     */
    public function updateElementContent(string $elementId, string $content): void
    {
        $this->dispatch('uve-update-element-content', [
            'elementId' => $elementId,
            'content' => $content,
        ]);
    }

    /**
     * Scroll iframe to element
     *
     * @param string $elementId Element ID to scroll to
     */
    public function scrollToElement(string $elementId): void
    {
        $this->dispatch('uve-scroll-to-element', [
            'elementId' => $elementId,
        ]);
    }

    /**
     * Re-select element after iframe refresh
     *
     * @param string $elementId Element ID to re-select
     */
    public function reselectElement(string $elementId): void
    {
        $this->dispatch('uve-reselect-element', [
            'elementId' => $elementId,
        ]);
    }

    /**
     * Get preview width based on device
     */
    #[Computed]
    public function previewWidth(): string
    {
        return match ($this->previewDevice) {
            'mobile' => '375px',
            'tablet' => '768px',
            default => '100%',
        };
    }

    // =====================
    // PREVIEW HTML
    // =====================

    /**
     * Get compiled preview HTML from all blocks
     */
    #[Computed]
    public function previewHtml(): string
    {
        $html = '';

        foreach ($this->blocks as $block) {
            $html .= $block['compiled_html'] ?? '';
        }

        return $html;
    }

    /**
     * Get full IFRAME content with PrestaShop structure
     * NOTE: Uses same template as EditorPreview::getIframeContent() for 1:1 preview
     */
    #[Computed]
    public function iframeContent(): string
    {
        $html = $this->previewHtml;
        $css = $this->shopPreviewCss;
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
        .uve-preview-empty {
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

    // =====================
    // CSS FETCHING
    // =====================

    /**
     * Get CSS for shop preview (from PrestaShop)
     */
    #[Computed]
    public function shopPreviewCss(): string
    {
        if (!$this->shopId) {
            return $this->defaultPreviewCss;
        }

        $cacheKey = "uve_preview_css_{$this->shopId}";

        return Cache::remember($cacheKey, 3600, function () {
            try {
                $shop = PrestaShopShop::find($this->shopId);
                if (!$shop) {
                    return $this->defaultPreviewCss;
                }

                $fetcher = app(PrestaShopCssFetcher::class);
                $css = $fetcher->getFullCssForPreview($shop);

                return !empty($css) ? $css : $this->defaultPreviewCss;
            } catch (\Exception $e) {
                return $this->defaultPreviewCss;
            }
        });
    }

    /**
     * Default preview CSS when shop CSS unavailable
     */
    #[Computed]
    public function defaultPreviewCss(): string
    {
        return <<<CSS
/* UVE Default Preview CSS */
.product-description {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.rte-content {
    line-height: 1.7;
}

/* PrestaShop Block Classes */
.pd-block {
    margin-bottom: 2rem;
}

.pd-intro {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: center;
}

.pd-merits {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.pd-specification table {
    width: 100%;
    border-collapse: collapse;
}

.pd-specification th,
.pd-specification td {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
    text-align: left;
}

.pd-features ul {
    list-style: none;
    padding: 0;
}

.pd-features li {
    padding: 0.5rem 0;
    padding-left: 1.5rem;
    position: relative;
}

.pd-features li::before {
    content: '\\2713';
    position: absolute;
    left: 0;
    color: #28a745;
}

.pd-cover {
    position: relative;
    width: 100%;
    min-height: 300px;
    background-size: cover;
    background-position: center;
}

.pd-slider .splide__slide img {
    width: 100%;
    height: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .pd-intro {
        grid-template-columns: 1fr;
    }

    .pd-merits {
        grid-template-columns: 1fr;
    }
}
CSS;
    }

    /**
     * Refresh preview CSS cache
     */
    public function refreshPreviewCss(): void
    {
        if ($this->shopId) {
            Cache::forget("uve_preview_css_{$this->shopId}");
        }

        $this->dispatch('notify', type: 'info', message: 'Cache CSS odswiezony');
    }

    // =====================
    // CANVAS PREVIEW (scoped CSS)
    // =====================

    /**
     * Get CSS scoped to canvas (for edit view, prevents leak to admin)
     */
    #[Computed]
    public function canvasPreviewCss(): string
    {
        $baseCss = $this->shopPreviewCss;

        // Scope all selectors to .uve-canvas
        $scopedCss = preg_replace_callback(
            '/([^{]+)\{/',
            function ($matches) {
                $selectors = explode(',', $matches[1]);
                $scopedSelectors = array_map(function ($selector) {
                    $selector = trim($selector);
                    if (empty($selector)) {
                        return '';
                    }
                    // Skip @rules
                    if (str_starts_with($selector, '@')) {
                        return $selector;
                    }
                    return '.uve-canvas ' . $selector;
                }, $selectors);
                return implode(', ', array_filter($scopedSelectors)) . ' {';
            },
            $baseCss
        );

        return $scopedCss;
    }

    // =====================
    // EXPORT
    // =====================

    /**
     * Export all blocks as HTML
     */
    public function exportAsHtml(): string
    {
        return $this->previewHtml;
    }

    /**
     * Export blocks as JSON
     */
    public function exportAsJson(): string
    {
        return json_encode([
            'version' => '2.0',
            'exported_at' => now()->toIso8601String(),
            'blocks' => $this->blocks,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import blocks from JSON
     */
    public function importFromJson(string $json): bool
    {
        try {
            $data = json_decode($json, true);

            if (!isset($data['blocks']) || !is_array($data['blocks'])) {
                $this->dispatch('notify', type: 'error', message: 'Nieprawidlowy format JSON');
                return false;
            }

            $this->pushHistory();
            $this->blocks = $data['blocks'];
            $this->isDirty = true;

            $this->dispatch('notify', type: 'success', message: 'Bloki zaimportowane');
            return true;

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Blad importu: ' . $e->getMessage());
            return false;
        }
    }

    // =====================
    // EDIT MODE IFRAME (FAZA 4.5.1)
    // =====================

    /**
     * Get editable IFRAME content with postMessage communication
     * Used for Edit Mode = Preview 1:1 with element editing capability
     */
    #[Computed]
    public function editableIframeContent(): string
    {
        $html = $this->previewHtml;
        $css = $this->shopPreviewCss;
        $shopName = $this->shop?->name ?? 'Edit Mode';

        // ETAP_07h FIX (2026-01-12): Generate UVE CSS from cssRules for page refresh persistence
        // Without this, CSS rules loaded from DB aren't applied to iframe on page load
        $uveCss = $this->generateCssString();

        // Inject data-uve-id markers to all editable elements
        $html = $this->injectEditableMarkers($html);

        // Get edit mode script
        $editScript = $this->getEditModeScript();

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: {$shopName}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* === MINIMAL RESET === */
        *, *::before, *::after { box-sizing: border-box; }

        /* === IFRAME VIEWPORT SIMULATION === */
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #f6f6f6;
        }

        /* === CSS VARIABLES (from Warehouse theme) === */
        :root {
            --inline-padding: 1rem;
            --max-content-width: 1300px;
            --max-text-width: 760px;
        }

        /* === BOOTSTRAP RESPONSIVE TEXT UTILITIES === */
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

        /* === EDIT MODE INDICATORS === */
        [data-uve-id] {
            cursor: pointer;
            transition: outline 0.15s ease;
        }
        [data-uve-id]:hover {
            outline: 2px dashed rgba(224, 172, 126, 0.6) !important;
            outline-offset: 2px;
        }
        [data-uve-id].uve-selected {
            outline: 3px solid #e0ac7e !important;
            outline-offset: 2px;
        }
        [data-uve-id].uve-editing {
            outline: 3px solid #3b82f6 !important;
            outline-offset: 2px;
            cursor: text;
        }

        /* Block-level indicators */
        [data-uve-type="block"]:hover::before {
            content: attr(data-uve-block-type);
            position: absolute;
            top: -24px;
            left: 0;
            background: #e0ac7e;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 3px;
            font-family: system-ui, sans-serif;
            z-index: 1000;
        }
        [data-uve-type="block"] {
            position: relative;
        }

        /* Splide visibility fallback */
        .splide, .pd-slider {
            visibility: visible;
            opacity: 1;
        }

        /* Image fallback */
        img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* === SHOP CSS (from PrestaShop) === */
        {$css}

        /* === UVE CSS RULES (from DB - loaded on page refresh) === */
        {$uveCss}
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
            // Initialize all Splide sliders
            var sliders = document.querySelectorAll('.splide, .pd-slider');
            sliders.forEach(function(slider) {
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

    <!-- Edit Mode Script -->
    <script>
    {$editScript}
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get edit mode HTML for JS (used by refreshIframe in Alpine)
     * This is called via $wire.getEditModeHtml() from frontend
     */
    public function getEditModeHtml(): string
    {
        return $this->editableIframeContent;
    }

    /**
     * Inject data-uve-id markers to editable elements in HTML
     * Adds unique IDs to blocks and child elements for postMessage targeting
     */
    protected function injectEditableMarkers(string $html): string
    {
        if (empty($html)) {
            return '<div class="uve-preview-empty" data-uve-id="empty">Brak blokow - dodaj pierwszy blok</div>';
        }

        $dom = new \DOMDocument();
        $dom->encoding = 'UTF-8';

        // Suppress HTML5 warnings, wrap in container for proper parsing
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="uve-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);
        $blockIndex = 0;

        // Mark pd-block elements (top-level blocks ONLY, exclude BEM children with __)
        // BEM child elements like pd-intro__heading, pd-cover__picture are NOT top-level blocks
        // ETAP_07h FIX: Dodano pd-pseudo-parallax, pd-parallax, pd-more-links, pd-footer, pd-header
        $blocks = $xpath->query('//*[(contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-merits") or contains(@class, "pd-specification") or contains(@class, "pd-features") or contains(@class, "pd-cover") or contains(@class, "pd-slider") or contains(@class, "pd-pseudo-parallax") or contains(@class, "pd-parallax") or contains(@class, "pd-more-links") or contains(@class, "pd-footer") or contains(@class, "pd-header") or contains(@class, "pd-asset-list") or contains(@class, "pd-where-2-ride")) and not(contains(@class, "__"))]');

        foreach ($blocks as $block) {
            $blockId = 'block-' . $blockIndex;
            $block->setAttribute('data-uve-id', $blockId);
            $block->setAttribute('data-uve-type', 'block');

            // Extract block type from class using prioritized detection
            $classes = $block->getAttribute('class');
            $blockType = $this->detectBlockTypeFromClasses($classes);
            if ($blockType) {
                $block->setAttribute('data-uve-block-type', $blockType);
            }

            // Mark child editable elements
            $this->markChildElements($xpath, $block, $blockId);

            $blockIndex++;
        }

        // Get inner HTML (without wrapper)
        $root = $dom->getElementById('uve-root');
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    /**
     * Detect block type from CSS classes using prioritized matching.
     *
     * Specific types (intro, slider, specification) take precedence over generic (block, base).
     * This prevents "pd-base-grid pd-intro" from being detected as "base" instead of "intro".
     *
     * @param string $classes Space-separated CSS classes
     * @return string|null Detected block type or null
     */
    protected function detectBlockTypeFromClasses(string $classes): ?string
    {
        // Prioritized list: specific types FIRST, generic types LAST
        // Order matters - first match wins
        $prioritizedTypes = [
            // Content-specific (highest priority)
            'intro',
            'cover',
            'slider',
            'parallax',
            'specification',
            'merits',
            'features',
            'more-links',
            'footer',
            'header',
            'gallery',
            'video',
            'accordion',
            'tabs',
            'cta',
            'hero',
            'grid',
            'two-column',
            'three-column',
            // Generic containers (lowest priority)
            'section',
            'block',
            'base',
        ];

        // Check each prioritized type in order
        foreach ($prioritizedTypes as $type) {
            // Match "pd-{type}" but NOT "pd-{type}__" (BEM children) and NOT "pd-{type}-grid" etc.
            // Use word boundary or end of string
            if (preg_match('/\bpd-' . preg_quote($type, '/') . '(?:\s|$)/i', $classes)) {
                return $type;
            }
        }

        // Fallback: try to extract any pd-* that isn't a BEM child
        if (preg_match('/\bpd-([a-z0-9-]+)(?:\s|$)/i', $classes, $matches)) {
            $extracted = $matches[1];
            // Skip if it looks like a BEM modifier (contains --)
            if (!str_contains($extracted, '--')) {
                return $extracted;
            }
        }

        return null;
    }

    /**
     * Mark child elements within a block for inline editing
     */
    protected function markChildElements(\DOMXPath $xpath, \DOMElement $block, string $blockId): void
    {
        $elementIndex = 0;

        // Mark headings
        $headings = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $block);
        foreach ($headings as $heading) {
            $heading->setAttribute('data-uve-id', "{$blockId}-heading-{$elementIndex}");
            $heading->setAttribute('data-uve-type', 'heading');
            $heading->setAttribute('data-uve-editable', 'text');
            $elementIndex++;
        }

        // Mark paragraphs
        $paragraphs = $xpath->query('.//p[not(ancestor::li)]', $block);
        foreach ($paragraphs as $p) {
            $p->setAttribute('data-uve-id', "{$blockId}-text-{$elementIndex}");
            $p->setAttribute('data-uve-type', 'text');
            $p->setAttribute('data-uve-editable', 'text');
            $elementIndex++;
        }

        // Mark images
        $images = $xpath->query('.//img', $block);
        foreach ($images as $img) {
            $img->setAttribute('data-uve-id', "{$blockId}-image-{$elementIndex}");
            $img->setAttribute('data-uve-type', 'image');
            $img->setAttribute('data-uve-editable', 'src');
            $elementIndex++;
        }

        // Mark links/buttons
        $links = $xpath->query('.//a[contains(@class, "btn") or contains(@class, "button")]', $block);
        foreach ($links as $link) {
            $link->setAttribute('data-uve-id', "{$blockId}-button-{$elementIndex}");
            $link->setAttribute('data-uve-type', 'button');
            $link->setAttribute('data-uve-editable', 'text,href');
            $elementIndex++;
        }

        // Mark list items
        $listItems = $xpath->query('.//li', $block);
        foreach ($listItems as $li) {
            $li->setAttribute('data-uve-id', "{$blockId}-listitem-{$elementIndex}");
            $li->setAttribute('data-uve-type', 'listitem');
            $li->setAttribute('data-uve-editable', 'text');
            $elementIndex++;
        }

        // Mark table cells
        $cells = $xpath->query('.//td|.//th', $block);
        foreach ($cells as $cell) {
            $cell->setAttribute('data-uve-id', "{$blockId}-cell-{$elementIndex}");
            $cell->setAttribute('data-uve-type', 'cell');
            $cell->setAttribute('data-uve-editable', 'text');
            $elementIndex++;
        }
    }

    /**
     * Get edit mode JavaScript for postMessage communication
     * FAZA 4.5.4: Enhanced with inline toolbar and Escape restoration
     */
    protected function getEditModeScript(): string
    {
        return <<<'JS'
(function() {
    'use strict';

    let selectedElement = null;
    let editingElement = null;
    let originalContent = null;
    let toolbar = null;

    // CRITICAL: Extract element styles for Panel synchronization
    function getElementStyles(el) {
        const computed = window.getComputedStyle(el);
        const inline = el.style;

        // Get both computed and inline styles for relevant CSS properties
        // Priority: inline styles > computed styles (inline shows what user actually set)
        const styles = {};

        // Typography properties
        const typographyProps = [
            'fontFamily', 'fontSize', 'fontWeight', 'fontStyle',
            'textDecoration', 'textTransform', 'lineHeight', 'letterSpacing',
            'textAlign', 'color'
        ];

        // Layout properties
        const layoutProps = [
            'display', 'flexDirection', 'justifyContent', 'alignItems',
            'gap', 'padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
            'width', 'height', 'minWidth', 'maxWidth', 'minHeight', 'maxHeight'
        ];

        // Background and border properties
        const visualProps = [
            'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
            'borderWidth', 'borderStyle', 'borderColor', 'borderRadius',
            'boxShadow', 'opacity'
        ];

        const allProps = [...typographyProps, ...layoutProps, ...visualProps];

        allProps.forEach(prop => {
            // Check inline style first (what user explicitly set)
            if (inline[prop] && inline[prop] !== '') {
                styles[prop] = inline[prop];
            } else {
                // Fallback to computed style
                const computedValue = computed[prop];
                if (computedValue && computedValue !== '' && computedValue !== 'none' && computedValue !== 'normal') {
                    styles[prop] = computedValue;
                }
            }
        });

        // Special handling for textDecoration (needs to detect underline, line-through etc.)
        const textDec = inline.textDecoration || computed.textDecoration || computed.textDecorationLine;
        if (textDec && textDec !== 'none') {
            styles.textDecoration = textDec;
        }

        // ETAP_07h FIX: Parse CSS url() to clean URL for background-image
        // Converts: url("https://...") or url('https://...') or url(https://...) -> https://...
        if (styles.backgroundImage && styles.backgroundImage.startsWith('url(')) {
            styles.backgroundImage = styles.backgroundImage
                .replace(/^url\(['"]?/, '')
                .replace(/['"]?\)$/, '');
        }

        // FIX #13d: For pd-cover blocks, check child elements for gradient background
        // The visual gradient is on .pd-cover__picture (child), not .pd-cover (parent)
        // When selecting the block, we should show the gradient in Property Panel
        if (el.classList && el.classList.contains('pd-cover')) {
            const pictureChild = el.querySelector('.pd-cover__picture');
            if (pictureChild) {
                const childComputed = window.getComputedStyle(pictureChild);
                const childBgImage = childComputed.backgroundImage;
                // Check if child has gradient (not 'none')
                if (childBgImage && childBgImage !== 'none' && childBgImage.includes('gradient')) {
                    styles.backgroundImage = childBgImage;
                    styles.childBackgroundSource = '.pd-cover__picture'; // Mark source for debugging
                }
            }
        }

        // FIX #13d: Generic handler for any block with __picture child containing gradient
        // Handles pd-intro, pd-section, etc. with similar structure
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

        // ETAP_07h PP.3: Extract src attribute for IMG elements
        // FIX #13 CORRECTED: Set ONLY src/imageUrl for image-settings control
        // DO NOT set backgroundImage - that's for CSS background-image only!
        // IMG ≠ Background Image - these are conceptually different!
        if (el.tagName === 'IMG' && el.src) {
            styles.src = el.src;
            styles.imageUrl = el.src;
        }

        // Also check for PICTURE element - extract src from nested IMG
        // FIX #13 CORRECTED: Set ONLY src/imageUrl, NOT backgroundImage
        if (el.tagName === 'PICTURE') {
            const img = el.querySelector('img');
            if (img && img.src) {
                styles.src = img.src;
                styles.imageUrl = img.src;
            }
        }

        // ETAP_07h PP.3.1: Check for nested images in parallax/cover containers
        // PrestaShop uses <picture><img> inside containers like pd-pseudo-parallax
        // FIX #13 CORRECTED: Set ONLY src/imageUrl for nested images
        // backgroundImage should come from CSS only, not from nested <img> elements
        if (!styles.src && !styles.imageUrl) {
            // Check for pd-pseudo-parallax__img class (PrestaShop pattern)
            const parallaxImg = el.querySelector('.pd-pseudo-parallax__img') ||
                               el.querySelector('picture img') ||
                               el.querySelector('img');
            if (parallaxImg && parallaxImg.src) {
                styles.src = parallaxImg.src;
                styles.imageUrl = parallaxImg.src;
            }
        }

        return styles;
    }

    // Create inline toolbar
    function createToolbar() {
        if (toolbar) return toolbar;

        toolbar = document.createElement('div');
        toolbar.id = 'uve-inline-toolbar';
        toolbar.innerHTML = `
            <button type="button" data-cmd="bold" title="Pogrubienie (Ctrl+B)"><b>B</b></button>
            <button type="button" data-cmd="italic" title="Kursywa (Ctrl+I)"><i>I</i></button>
            <button type="button" data-cmd="underline" title="Podkreslenie (Ctrl+U)"><u>U</u></button>
            <span class="uve-toolbar-separator"></span>
            <button type="button" data-cmd="createLink" title="Wstaw link"><span>🔗</span></button>
            <button type="button" data-cmd="unlink" title="Usun link"><span>⛓️‍💥</span></button>
        `;

        // Toolbar styles
        const style = document.createElement('style');
        style.textContent = `
            #uve-inline-toolbar {
                position: fixed !important;
                z-index: 2147483647 !important; /* Max int - always on top */
                display: none;
                background: #1e293b;
                border: 1px solid #475569;
                border-radius: 6px;
                padding: 4px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                gap: 2px;
                pointer-events: auto;
            }
            #uve-inline-toolbar.visible {
                display: flex !important;
            }
            #uve-inline-toolbar button {
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: none;
                border-radius: 4px;
                color: #e2e8f0;
                cursor: pointer;
                font-size: 14px;
                transition: background 0.15s;
            }
            #uve-inline-toolbar button:hover {
                background: #334155;
            }
            #uve-inline-toolbar button.active {
                background: #e0ac7e;
                color: #0f172a;
            }
            .uve-toolbar-separator {
                width: 1px;
                background: #475569;
                margin: 0 4px;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(toolbar);

        // Handle toolbar button clicks
        toolbar.addEventListener('mousedown', function(e) {
            e.preventDefault(); // Prevent blur
            const btn = e.target.closest('button');
            if (!btn) return;

            const cmd = btn.dataset.cmd;
            if (cmd === 'createLink') {
                const url = prompt('Wprowadz URL:', 'https://');
                if (url) {
                    document.execCommand('createLink', false, url);
                }
            } else {
                document.execCommand(cmd, false, null);
            }
            updateToolbarState();
        });

        return toolbar;
    }

    // Show toolbar near selection - ALWAYS BELOW element to avoid stacking issues
    function showToolbar(el) {
        if (!toolbar) createToolbar();

        const rect = el.getBoundingClientRect();
        const toolbarWidth = 180;
        const toolbarHeight = 40;
        const padding = 8;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // Calculate position - prefer BELOW element (avoids stacking context issues)
        let left = rect.left;
        let top = rect.bottom + padding;

        // If toolbar would go beyond LEFT edge
        if (left < padding) {
            left = padding;
        }

        // If toolbar would go beyond RIGHT edge
        if (left + toolbarWidth > viewportWidth - padding) {
            left = viewportWidth - toolbarWidth - padding;
        }

        // If toolbar would go beyond BOTTOM edge, show ABOVE element
        if (top + toolbarHeight > viewportHeight - padding) {
            top = rect.top - toolbarHeight - padding;
        }

        // If still goes beyond TOP edge, center vertically
        if (top < padding) {
            top = Math.max(padding, (viewportHeight - toolbarHeight) / 2);
        }

        toolbar.style.left = left + 'px';
        toolbar.style.top = top + 'px';
        toolbar.classList.add('visible');
        updateToolbarState();
    }

    // Hide toolbar
    function hideToolbar() {
        if (toolbar) {
            toolbar.classList.remove('visible');
        }
    }

    // Update toolbar button states
    function updateToolbarState() {
        if (!toolbar) return;
        toolbar.querySelectorAll('button').forEach(btn => {
            const cmd = btn.dataset.cmd;
            if (['bold', 'italic', 'underline'].includes(cmd)) {
                btn.classList.toggle('active', document.queryCommandState(cmd));
            }
        });
    }

    // Click to select element
    document.addEventListener('click', function(e) {
        // Allow clicks inside toolbar
        if (e.target.closest('#uve-inline-toolbar')) return;

        e.preventDefault();
        e.stopPropagation();

        const el = e.target.closest('[data-uve-id]');
        if (!el) return;

        // Clear previous selection
        if (selectedElement) {
            selectedElement.classList.remove('uve-selected');
        }

        // Select new element
        el.classList.add('uve-selected');
        selectedElement = el;

        // Send selection to parent with STYLES for Panel synchronization
        const rect = el.getBoundingClientRect();
        const styles = getElementStyles(el);
        parent.postMessage({
            type: 'uve:select',
            elementId: el.dataset.uveId,
            elementType: el.dataset.uveType,
            blockType: el.dataset.uveBlockType || null,
            editable: el.dataset.uveEditable || null,
            rect: {
                top: rect.top,
                left: rect.left,
                width: rect.width,
                height: rect.height
            },
            content: el.innerHTML,
            tagName: el.tagName.toLowerCase(),
            styles: styles  // CRITICAL: Send styles to Panel for synchronization
        }, '*');
    }, true);

    // Double-click for inline edit
    document.addEventListener('dblclick', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const el = e.target.closest('[data-uve-editable]');
        if (!el || el.dataset.uveEditable !== 'text') return;

        startInlineEdit(el);
    }, true);

    // Listen for commands from parent
    window.addEventListener('message', function(e) {
        const { type, elementId, content, styles, action } = e.data;

        if (type === 'uve:update') {
            const el = document.querySelector('[data-uve-id="' + elementId + '"]');
            if (el) {
                if (content !== undefined) el.innerHTML = content;
                if (styles) Object.assign(el.style, styles);
                parent.postMessage({ type: 'uve:updated', elementId: elementId }, '*');
            }
        }

        if (type === 'uve:deselect') {
            if (selectedElement) {
                selectedElement.classList.remove('uve-selected');
                selectedElement = null;
            }
            hideToolbar();
        }

        if (type === 'uve:start-edit') {
            const el = document.querySelector('[data-uve-id="' + elementId + '"]');
            if (el && el.dataset.uveEditable === 'text') {
                startInlineEdit(el);
            }
        }

        if (type === 'uve:scroll-to') {
            const el = document.querySelector('[data-uve-id="' + elementId + '"]');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        if (type === 'uve:select-element') {
            // Select element after iframe refresh (from parent)
            const el = document.querySelector('[data-uve-id="' + elementId + '"]');
            if (el) {
                // Clear previous selection
                if (selectedElement) {
                    selectedElement.classList.remove('uve-selected');
                }
                // Select new element
                el.classList.add('uve-selected');
                selectedElement = el;
                // Scroll into view
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Notify parent about re-selection with STYLES for Panel sync
                const rect = el.getBoundingClientRect();
                const styles = getElementStyles(el);
                parent.postMessage({
                    type: 'uve:select',
                    elementId: el.dataset.uveId,
                    elementType: el.dataset.uveType,
                    blockType: el.dataset.uveBlockType || null,
                    editable: el.dataset.uveEditable || null,
                    rect: { top: rect.top, left: rect.left, width: rect.width, height: rect.height },
                    content: el.innerHTML,
                    tagName: el.tagName.toLowerCase(),
                    styles: styles  // CRITICAL: Send styles for Panel synchronization
                }, '*');
            }
        }
    });

    // Inline editing functions
    function startInlineEdit(el) {
        // Save original content for Escape restoration
        originalContent = el.innerHTML;
        editingElement = el;

        el.classList.add('uve-editing');
        el.contentEditable = 'true';
        el.focus();

        // Select all text
        const range = document.createRange();
        range.selectNodeContents(el);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);

        // Show inline toolbar
        showToolbar(el);

        // Notify parent
        parent.postMessage({
            type: 'uve:editing-started',
            elementId: el.dataset.uveId
        }, '*');

        // Handle blur (end edit)
        el.addEventListener('blur', function onBlur(e) {
            // Don't end if clicking toolbar
            if (e.relatedTarget && e.relatedTarget.closest('#uve-inline-toolbar')) {
                return;
            }
            el.removeEventListener('blur', onBlur);
            endInlineEdit(el, false);
        });

        // Handle keyboard
        el.addEventListener('keydown', function onKey(e) {
            // Keyboard shortcuts for formatting
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 'b') {
                    e.preventDefault();
                    document.execCommand('bold', false, null);
                    updateToolbarState();
                }
                if (e.key === 'i') {
                    e.preventDefault();
                    document.execCommand('italic', false, null);
                    updateToolbarState();
                }
                if (e.key === 'u') {
                    e.preventDefault();
                    document.execCommand('underline', false, null);
                    updateToolbarState();
                }
            }

            // Enter to finish
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                el.removeEventListener('keydown', onKey);
                endInlineEdit(el, false);
            }

            // Escape to cancel and restore
            if (e.key === 'Escape') {
                e.preventDefault();
                el.removeEventListener('keydown', onKey);
                endInlineEdit(el, true);
            }
        });

        // Update toolbar on selection change
        el.addEventListener('input', updateToolbarState);
        document.addEventListener('selectionchange', updateToolbarState);
    }

    function endInlineEdit(el, cancelled) {
        el.classList.remove('uve-editing');
        el.contentEditable = 'false';

        // Hide toolbar
        hideToolbar();

        // Restore content if cancelled
        if (cancelled && originalContent !== null) {
            el.innerHTML = originalContent;
            parent.postMessage({
                type: 'uve:editing-cancelled',
                elementId: el.dataset.uveId
            }, '*');
        } else {
            parent.postMessage({
                type: 'uve:content-changed',
                elementId: el.dataset.uveId,
                content: el.innerHTML,
                tagName: el.tagName.toLowerCase()
            }, '*');
        }

        // Clear state
        editingElement = null;
        originalContent = null;
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Skip if editing
        if (editingElement) return;

        if (!selectedElement) return;

        if (e.key === 'Delete' || e.key === 'Backspace') {
            // Only for block-level elements
            if (selectedElement.dataset.uveType === 'block') {
                e.preventDefault();
                parent.postMessage({
                    type: 'uve:delete-request',
                    elementId: selectedElement.dataset.uveId
                }, '*');
            }
        }

        if (e.key === 'Escape') {
            selectedElement.classList.remove('uve-selected');
            selectedElement = null;
            parent.postMessage({ type: 'uve:deselected' }, '*');
        }
    });

    // Scroll handling - update selection rect on scroll
    // CRITICAL: body is the scrolling element (overflow-y: auto), listen directly on it
    let scrollTimeout = null;
    document.body.addEventListener('scroll', function() {
        if (!selectedElement) return;

        // Throttle scroll updates (~60fps)
        if (scrollTimeout) return;

        scrollTimeout = setTimeout(function() {
            scrollTimeout = null;

            if (selectedElement) {
                const rect = selectedElement.getBoundingClientRect();
                parent.postMessage({
                    type: 'uve:rect-update',
                    elementId: selectedElement.dataset.uveId,
                    rect: {
                        top: rect.top,
                        left: rect.left,
                        width: rect.width,
                        height: rect.height
                    }
                }, '*');
            }

            // Update toolbar position if editing
            if (editingElement) {
                showToolbar(editingElement);
            }
        }, 16);
    });

    console.log('[UVE] Edit mode initialized - FAZA 4.5.4');
})();
JS;
    }

    // =====================
    // RAW-HTML LAYER PARSING (FAZA 4.5.3.2)
    // =====================

    /**
     * Parse raw-html block content into virtual layers for Layers Panel
     * Extracts top-level div elements with pd-* classes and z-index information
     *
     * @param string $html The raw HTML content
     * @return array Array of virtual layers with id, type, classes, zIndex
     */
    public function parseRawHtmlLayers(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $layers = [];

        // Use DOMDocument to parse HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="wrapper">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // ETAP_07h FIX #4: Use same XPath filtering as injectEditableMarkers()
        // This ensures block-X indices match between layers panel and iframe
        $xpath = new \DOMXPath($dom);
        $blocks = $xpath->query('//*[(contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-merits") or contains(@class, "pd-specification") or contains(@class, "pd-features") or contains(@class, "pd-cover") or contains(@class, "pd-slider") or contains(@class, "pd-pseudo-parallax") or contains(@class, "pd-parallax") or contains(@class, "pd-more-links") or contains(@class, "pd-footer") or contains(@class, "pd-header") or contains(@class, "pd-asset-list") or contains(@class, "pd-where-2-ride")) and not(contains(@class, "__"))]');

        $index = 0;
        foreach ($blocks as $node) {
            $tagName = strtolower($node->nodeName);
            $classes = $node->getAttribute('class') ?? '';
            $style = $node->getAttribute('style') ?? '';

            // Extract z-index from inline style if present
            $zIndex = null;
            if (preg_match('/z-index\s*:\s*(\d+)/i', $style, $matches)) {
                $zIndex = (int) $matches[1];
            }

            // Determine layer name from pd-* class or tag name
            $layerName = $tagName;
            if (preg_match('/pd-([a-z0-9-]+)/i', $classes, $matches)) {
                $layerName = 'pd-' . $matches[1];
            }

            // Get first line of text content for preview
            $textContent = trim($node->textContent);
            $preview = mb_substr($textContent, 0, 30);
            if (mb_strlen($textContent) > 30) {
                $preview .= '...';
            }

            // ETAP_07h FIX #4: Generate block-X IDs matching injectEditableMarkers()
            $uveId = $node->getAttribute('data-uve-id') ?: 'block-' . $index;

            $layers[] = [
                'id' => $uveId,
                'index' => $index,
                'tag' => $tagName,
                'name' => $layerName,
                'classes' => $classes,
                'zIndex' => $zIndex,
                'preview' => $preview,
                'hasChildren' => $node->hasChildNodes() && $node->childNodes->length > 1,
            ];

            $index++;
        }

        // Sort by z-index (higher first) if z-indices are present
        usort($layers, function ($a, $b) {
            // Elements with z-index come first
            if ($a['zIndex'] !== null && $b['zIndex'] === null) return -1;
            if ($a['zIndex'] === null && $b['zIndex'] !== null) return 1;
            // If both have z-index, higher comes first
            if ($a['zIndex'] !== null && $b['zIndex'] !== null) {
                return $b['zIndex'] - $a['zIndex'];
            }
            // Otherwise maintain DOM order
            return $a['index'] - $b['index'];
        });

        return $layers;
    }

    /**
     * Get layers for a specific block (works for both structured and raw-html)
     *
     * @param int $blockIndex Index of the block
     * @return array Array of layers
     */
    public function getBlockLayers(int $blockIndex): array
    {
        if (!isset($this->blocks[$blockIndex])) {
            return [];
        }

        $block = $this->blocks[$blockIndex];

        // For structured blocks, use document tree
        if (isset($block['document']['root']['children'])) {
            return $block['document']['root']['children'];
        }

        // For raw-html blocks, parse the HTML
        if (($block['type'] ?? '') === 'raw-html' && isset($block['content']['html'])) {
            return $this->parseRawHtmlLayers($block['content']['html']);
        }

        return [];
    }
}
