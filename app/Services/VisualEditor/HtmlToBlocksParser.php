<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Services\VisualEditor\Blocks\PrestaShop\PrestashopSectionBlock;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Parser converting HTML from PrestaShop to Visual Editor block structure.
 *
 * Detects pd-* and blok-* classes, maps them to block types,
 * extracts content/settings from DOM.
 * Unknown HTML falls back to RawHtmlBlock.
 */
class HtmlToBlocksParser
{
    /**
     * CSS class patterns that indicate PrestaShop content.
     * When detected, the element will be parsed as prestashop-section
     * to preserve HTML 1:1 without any modifications.
     */
    private array $prestashopClassPatterns = [
        'pd-',           // All pd-* classes (PrestaShop Description)
        'blok-',         // Pitgang namespace
        'splide',        // Slider
        'grid-row',      // Layout rows
        'bg-brand',      // Brand backgrounds
        'bg-neutral',    // Neutral backgrounds
    ];

    /**
     * Tags that should be treated as PrestaShop sections.
     */
    private array $prestashopTags = ['footer', 'aside', 'section', 'article'];

    /**
     * Parse HTML string into array of block structures.
     */
    public function parse(string $html): array
    {
        if (empty(trim($html))) {
            return [];
        }

        $dom = $this->createDomDocument($html);
        if (!$dom) {
            return $this->createRawHtmlBlock($html);
        }

        $blocks = [];
        $xpath = new DOMXPath($dom);

        // Find wrapper element or use body
        $wrapper = $xpath->query('//div[contains(@class, "pd-wrapper")]')->item(0);
        if (!$wrapper) {
            $wrapper = $dom->getElementsByTagName('body')->item(0);
        }

        if (!$wrapper) {
            return $this->createRawHtmlBlock($html);
        }

        // Parse direct children as blocks
        foreach ($wrapper->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $block = $this->parseElement($node, $dom);
                if ($block) {
                    $blocks[] = $block;
                }
            }
        }

        // If no blocks detected, wrap entire HTML in RawHtmlBlock
        if (empty($blocks)) {
            return $this->createRawHtmlBlock($html);
        }

        return $blocks;
    }

    /**
     * Parse single DOM element into block structure.
     *
     * For PrestaShop content (pd-* classes, footer, aside, etc.),
     * creates prestashop-section blocks that preserve HTML 1:1.
     */
    private function parseElement(DOMElement $element, DOMDocument $dom): ?array
    {
        $classes = $element->getAttribute('class');
        $tagName = strtolower($element->tagName);

        // Check if this is PrestaShop content - if yes, preserve HTML exactly
        if ($this->isPrestashopContent($classes, $tagName)) {
            return $this->createPrestashopSectionBlock($element, $dom);
        }

        // For non-PrestaShop content, fall back to raw HTML
        $html = $this->getOuterHtml($element, $dom);
        if (!empty(trim($html))) {
            return $this->createSingleRawHtmlBlock($html);
        }

        return null;
    }

    /**
     * Check if element contains PrestaShop content.
     */
    private function isPrestashopContent(string $classes, string $tagName): bool
    {
        // Check tag name
        if (in_array($tagName, $this->prestashopTags)) {
            return true;
        }

        // Check class patterns
        foreach ($this->prestashopClassPatterns as $pattern) {
            if (str_contains($classes, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a block from PrestaShop content.
     *
     * IMPORTANT: Always preserves original HTML as prestashop-section passthrough.
     * This ensures 1:1 visual fidelity with PrestaShop - the HTML is rendered exactly
     * as imported without any modifications.
     *
     * The specialized blocks (PdMeritsBlock, PdSliderBlock, etc.) are available
     * for CREATING new content, but NOT for importing from PrestaShop because
     * PrestaShop uses different HTML structures than our blocks generate.
     */
    private function createPrestashopSectionBlock(DOMElement $element, DOMDocument $dom): array
    {
        $classes = $element->getAttribute('class');
        $tagName = strtolower($element->tagName);

        // Detect section type for metadata (helps with UI labeling)
        $sectionType = PrestashopSectionBlock::detectSectionType($classes, $tagName);

        // ALWAYS preserve original HTML in prestashop-section (passthrough mode)
        // This ensures visual 1:1 fidelity with PrestaShop
        $outerHtml = $this->getOuterHtml($element, $dom);

        return [
            'type' => 'prestashop-section',
            'data' => [
                'content' => [
                    'html' => $outerHtml,
                ],
                'settings' => [
                    'section_type' => $sectionType,
                    'original_tag' => $tagName,
                    'original_classes' => $classes,
                ],
            ],
        ];
    }

    /**
     * Extract pd-merits block data from DOM.
     *
     * Looks for .pd-merit elements with icon, h4 (title), p (description).
     */
    private function extractMeritsBlock(DOMElement $element, DOMDocument $dom): ?array
    {
        $xpath = new DOMXPath($dom);
        $meritElements = $xpath->query('.//*[contains(@class, "pd-merit")]', $element);

        if ($meritElements->length === 0) {
            return null;
        }

        $items = [];
        foreach ($meritElements as $merit) {
            // Extract icon class
            $iconElement = $xpath->query('.//*[contains(@class, "pd-icon")]', $merit)->item(0);
            $iconClass = '';
            if ($iconElement) {
                $iconClasses = $iconElement->getAttribute('class');
                if (preg_match('/pd-icon--[\w-]+/', $iconClasses, $matches)) {
                    $iconClass = $matches[0];
                }
            }

            // Extract title (h4)
            $titleElement = $xpath->query('.//h4', $merit)->item(0);
            $title = $titleElement ? trim($titleElement->textContent) : '';

            // Extract description (p)
            $descElement = $xpath->query('.//p', $merit)->item(0);
            $description = $descElement ? trim($descElement->textContent) : '';

            if ($title || $description) {
                $items[] = [
                    'icon' => $iconClass ?: 'pd-icon--check',
                    'title' => $title,
                    'description' => $description,
                ];
            }
        }

        if (empty($items)) {
            return null;
        }

        // Detect settings from classes
        $classes = $element->getAttribute('class');
        $showDividers = str_contains($classes, 'pd-merits--dividers');
        $columns = 3;
        if (preg_match('/pd-merits--cols-(\d+)/', $classes, $matches)) {
            $columns = (int) $matches[1];
        }

        return [
            'type' => 'pd-merits',
            'data' => [
                'content' => ['items' => $items],
                'settings' => [
                    'columns' => $columns,
                    'show_dividers' => $showDividers,
                    'background' => 'transparent',
                    'text_align' => 'center',
                ],
            ],
        ];
    }

    /**
     * Extract pd-slider block data from DOM.
     *
     * Looks for Splide.js structure with slides.
     */
    private function extractSliderBlock(DOMElement $element, DOMDocument $dom): ?array
    {
        $xpath = new DOMXPath($dom);
        $slideElements = $xpath->query('.//*[contains(@class, "splide__slide")]', $element);

        if ($slideElements->length === 0) {
            return null;
        }

        $slides = [];
        foreach ($slideElements as $slide) {
            // Extract image
            $imgElement = $xpath->query('.//img', $slide)->item(0);
            $image = $imgElement ? $imgElement->getAttribute('src') : '';
            $alt = $imgElement ? $imgElement->getAttribute('alt') : '';

            // Extract title (h4 or first heading)
            $titleElement = $xpath->query('.//h4|.//h3|.//h2', $slide)->item(0);
            $title = $titleElement ? trim($titleElement->textContent) : ($alt ?: '');

            // Extract description (p)
            $descElement = $xpath->query('.//p', $slide)->item(0);
            $description = $descElement ? trim($descElement->textContent) : '';

            // Extract link (a)
            $linkElement = $xpath->query('.//a', $slide)->item(0);
            $link = $linkElement ? $linkElement->getAttribute('href') : '';

            $slides[] = [
                'image' => $image,
                'title' => $title,
                'description' => $description,
                'link' => $link,
            ];
        }

        if (empty($slides)) {
            return null;
        }

        // Try to extract Splide config from data attribute
        $splideConfig = $element->getAttribute('data-splide');
        $config = $splideConfig ? json_decode($splideConfig, true) : [];

        return [
            'type' => 'pd-slider',
            'data' => [
                'content' => ['slides' => $slides],
                'settings' => [
                    'perPage' => $config['perPage'] ?? 1,
                    'autoplay' => $config['autoplay'] ?? false,
                    'loop' => isset($config['type']) && $config['type'] === 'loop',
                    'arrows' => $config['arrows'] ?? true,
                    'pagination' => $config['pagination'] ?? true,
                    'speed' => $config['speed'] ?? 400,
                    'slide_type' => 'image-text',
                ],
            ],
        ];
    }

    /**
     * Extract pd-specification block data from DOM.
     *
     * Looks for table structure with label/value pairs.
     */
    private function extractSpecificationBlock(DOMElement $element, DOMDocument $dom): ?array
    {
        $xpath = new DOMXPath($dom);

        // Try to find table rows
        $tableRows = $xpath->query('.//table//tr|.//tr', $element);

        if ($tableRows->length === 0) {
            return null;
        }

        $rows = [];
        foreach ($tableRows as $tr) {
            $cells = $xpath->query('.//td|.//th', $tr);
            if ($cells->length >= 2) {
                $label = trim($cells->item(0)->textContent);
                $value = trim($cells->item(1)->textContent);
                if ($label && $value) {
                    $rows[] = [
                        'label' => $label,
                        'value' => $value,
                    ];
                }
            }
        }

        if (empty($rows)) {
            return null;
        }

        // Extract title (h3 or heading before table)
        $titleElement = $xpath->query('.//h3|.//h2|.//*[contains(@class, "title")]', $element)->item(0);
        $title = $titleElement ? trim($titleElement->textContent) : 'Dane techniczne';

        // Detect settings from classes
        $classes = $element->getAttribute('class');

        return [
            'type' => 'pd-specification',
            'data' => [
                'content' => [
                    'title' => $title,
                    'rows' => $rows,
                ],
                'settings' => [
                    'layout' => str_contains($classes, 'two-column') ? 'two-column' : 'single',
                    'show_title' => !empty($title),
                    'striped' => true,
                    'bordered' => false,
                    'compact' => false,
                ],
            ],
        ];
    }

    /**
     * Extract pd-parallax block data from DOM.
     *
     * Looks for background image and overlay text.
     */
    private function extractParallaxBlock(DOMElement $element, DOMDocument $dom): ?array
    {
        // Extract background image from inline style
        $style = $element->getAttribute('style');
        $backgroundImage = '';
        if (preg_match('/background-image:\s*url\([\'"]?([^\'")\s]+)[\'"]?\)/i', $style, $matches)) {
            $backgroundImage = $matches[1];
        }

        // Also check for data-bg attribute (lazy loading pattern)
        if (!$backgroundImage) {
            $backgroundImage = $element->getAttribute('data-bg') ?: '';
        }

        $xpath = new DOMXPath($dom);

        // Extract title (h3, h2, or similar)
        $titleElement = $xpath->query('.//h3|.//h2|.//*[contains(@class, "title")]', $element)->item(0);
        $title = $titleElement ? trim($titleElement->textContent) : '';

        // Extract subtitle (p or subtitle class)
        $subtitleElement = $xpath->query('.//p|.//*[contains(@class, "subtitle")]', $element)->item(0);
        $subtitle = $subtitleElement ? trim($subtitleElement->textContent) : '';

        // Extract button
        $buttonElement = $xpath->query('.//a[contains(@class, "btn")]', $element)->item(0);
        $buttonText = $buttonElement ? trim($buttonElement->textContent) : '';
        $buttonUrl = $buttonElement ? $buttonElement->getAttribute('href') : '';

        // If no meaningful content extracted, return null
        if (!$backgroundImage && !$title && !$subtitle) {
            return null;
        }

        // Detect settings from classes
        $classes = $element->getAttribute('class');

        return [
            'type' => 'pd-parallax',
            'data' => [
                'content' => [
                    'background_image' => $backgroundImage,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'button_text' => $buttonText,
                    'button_url' => $buttonUrl,
                ],
                'settings' => [
                    'height' => '500px',
                    'overlay_opacity' => 0.4,
                    'overlay_color' => 'dark',
                    'text_align' => 'center',
                    'text_position' => 'center',
                ],
            ],
        ];
    }

    /**
     * Extract pd-asset-list block data from DOM.
     *
     * Looks for .pd-asset elements with value, unit, label.
     */
    private function extractAssetListBlock(DOMElement $element, DOMDocument $dom): ?array
    {
        $xpath = new DOMXPath($dom);
        $assetElements = $xpath->query('.//*[contains(@class, "pd-asset")]', $element);

        if ($assetElements->length === 0) {
            return null;
        }

        $assets = [];
        foreach ($assetElements as $asset) {
            // Extract value
            $valueElement = $xpath->query('.//*[contains(@class, "pd-asset__value")]|.//strong|.//b', $asset)->item(0);
            $value = $valueElement ? trim($valueElement->textContent) : '';

            // Extract unit
            $unitElement = $xpath->query('.//*[contains(@class, "pd-asset__unit")]', $asset)->item(0);
            $unit = $unitElement ? trim($unitElement->textContent) : '';

            // Extract label
            $labelElement = $xpath->query('.//*[contains(@class, "pd-asset__label")]|.//span[last()]', $asset)->item(0);
            $label = $labelElement ? trim($labelElement->textContent) : '';

            // Extract icon
            $iconElement = $xpath->query('.//*[contains(@class, "pd-icon")]', $asset)->item(0);
            $iconClass = '';
            if ($iconElement) {
                $iconClasses = $iconElement->getAttribute('class');
                if (preg_match('/pd-icon--[\w-]+/', $iconClasses, $matches)) {
                    $iconClass = $matches[0];
                }
            }

            if ($value || $label) {
                $assets[] = [
                    'value' => $value,
                    'unit' => $unit,
                    'label' => $label,
                    'icon' => $iconClass,
                ];
            }
        }

        if (empty($assets)) {
            return null;
        }

        // Detect settings from classes
        $classes = $element->getAttribute('class');

        return [
            'type' => 'pd-asset-list',
            'data' => [
                'content' => ['assets' => $assets],
                'settings' => [
                    'layout' => str_contains($classes, 'vertical') ? 'vertical' : 'horizontal',
                    'columns' => 4,
                    'size' => 'medium',
                    'show_dividers' => str_contains($classes, 'dividers'),
                    'center_text' => true,
                ],
            ],
        ];
    }

    /**
     * Detect block type from CSS classes.
     */
    private function detectBlockType(string $classes): ?string
    {
        $classList = preg_split('/\s+/', $classes);

        foreach ($classList as $class) {
            // Check exact match first
            if (isset($this->classToBlockMap[$class])) {
                return $this->classToBlockMap[$class];
            }

            // Check prefix matches (e.g., pd-cols--2 matches pd-cols)
            foreach ($this->classToBlockMap as $pattern => $type) {
                if (Str::startsWith($class, $pattern)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Extract block data based on type.
     *
     * Returns data in BlockRegistry-compatible format:
     * ['type' => '...', 'data' => ['content' => [...], 'settings' => [...]]]
     */
    private function extractBlockData(DOMElement $element, string $type, DOMDocument $dom): array
    {
        $extractedData = match ($type) {
            'hero-banner' => $this->extractHeroBannerData($element, $dom),
            'two-column' => $this->extractColumnData($element, $dom, 2),
            'three-column' => $this->extractColumnData($element, $dom, 3),
            'grid-section' => $this->extractGridData($element, $dom),
            'full-width' => $this->extractFullWidthData($element, $dom),
            'heading' => $this->extractHeadingData($element),
            'text' => $this->extractTextData($element, $dom),
            'image' => $this->extractImageData($element),
            'image-gallery' => $this->extractGalleryData($element, $dom),
            'video-embed' => $this->extractVideoData($element),
            'raw-html' => $this->extractRawHtmlData($element, $dom),
            default => ['html' => $this->getInnerHtml($element, $dom)],
        };

        // Normalize to content/settings structure expected by BlockRegistry
        return [
            'type' => $type,
            'data' => [
                'content' => $extractedData,
                'settings' => [],
            ],
        ];
    }

    /**
     * Extract hero banner data.
     */
    private function extractHeroBannerData(DOMElement $element, DOMDocument $dom): array
    {
        $xpath = new DOMXPath($dom);

        // Find background image
        $style = $element->getAttribute('style');
        $backgroundImage = '';
        if (preg_match('/background-image:\s*url\([\'"]?([^\'"]+)[\'"]?\)/i', $style, $matches)) {
            $backgroundImage = $matches[1];
        }

        // Find title
        $titleNode = $xpath->query('.//h1|.//h2|.//h3', $element)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        // Find subtitle
        $subtitleNode = $xpath->query('.//p[contains(@class, "subtitle")]|.//p', $element)->item(0);
        $subtitle = $subtitleNode ? trim($subtitleNode->textContent) : '';

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'background_image' => $backgroundImage,
            'height' => $this->extractStyleValue($style, 'height', '400px'),
            'overlay_opacity' => 0.5,
        ];
    }

    /**
     * Extract column layout data.
     *
     * Handles both structured pd-cols__item/blok-col children and raw HTML.
     * When no column elements found, preserves innerHTML as fallback.
     */
    private function extractColumnData(DOMElement $element, DOMDocument $dom, int $columns): array
    {
        $xpath = new DOMXPath($dom);
        $items = $xpath->query('.//*[contains(@class, "pd-cols__item")]|.//*[contains(@class, "blok-col")]', $element);

        $children = [];
        foreach ($items as $item) {
            $childBlocks = [];
            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $block = $this->parseElement($child, $dom);
                    if ($block) {
                        $childBlocks[] = $block;
                    }
                }
            }
            $children[] = $childBlocks;
        }

        // FALLBACK: If no column items found, preserve entire innerHTML
        if (empty($children)) {
            return [
                'children' => [],
                'ratio' => $columns === 2 ? '50-50' : '33-33-33',
                'html' => $this->getInnerHtml($element, $dom),
                'original_classes' => $element->getAttribute('class'),
            ];
        }

        return [
            'children' => $children,
            'ratio' => $columns === 2 ? '50-50' : '33-33-33',
        ];
    }

    /**
     * Extract grid section data.
     *
     * Handles both structured pd-grid__item children and raw HTML content.
     * When no pd-grid__item elements are found, preserves entire innerHTML
     * as fallback to ensure no content is lost during parsing.
     */
    private function extractGridData(DOMElement $element, DOMDocument $dom): array
    {
        $classes = $element->getAttribute('class');

        // Extract column count from class
        $columns = 3;
        if (preg_match('/pd-grid--cols-(\d+)/', $classes, $matches)) {
            $columns = (int) $matches[1];
        }

        $xpath = new DOMXPath($dom);
        $items = $xpath->query('.//*[contains(@class, "pd-grid__item")]', $element);

        $children = [];
        foreach ($items as $item) {
            $children[] = $this->getInnerHtml($item, $dom);
        }

        // FALLBACK: If no grid items found, preserve entire innerHTML
        // This handles PrestaShop HTML that uses pd-base-grid without pd-grid__item
        if (empty($children)) {
            return [
                'columns' => $columns,
                'children' => [],
                'html' => $this->getInnerHtml($element, $dom),
                'original_classes' => $classes,
            ];
        }

        return [
            'columns' => $columns,
            'children' => $children,
        ];
    }

    /**
     * Extract heading block from semantic h1-h6.
     */
    private function extractHeadingBlock(DOMElement $element, string $tagName): array
    {
        return [
            'type' => 'heading',
            'data' => [
                'content' => [
                    'text' => trim($element->textContent),
                    'level' => $tagName,
                ],
                'settings' => [],
            ],
        ];
    }

    /**
     * Extract heading data from pd-heading element.
     */
    private function extractHeadingData(DOMElement $element): array
    {
        $xpath = new DOMXPath($element->ownerDocument);
        $headingNode = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $element)->item(0);

        $level = 'h2';
        $text = '';
        if ($headingNode) {
            $level = strtolower($headingNode->tagName);
            $text = trim($headingNode->textContent);
        } else {
            $text = trim($element->textContent);
        }

        return [
            'text' => $text,
            'level' => $level,
        ];
    }

    /**
     * Extract text block from paragraph.
     */
    private function extractTextBlock(DOMElement $element, DOMDocument $dom): array
    {
        return [
            'type' => 'text',
            'data' => [
                'content' => [
                    'text' => $this->getInnerHtml($element, $dom),
                ],
                'settings' => [],
            ],
        ];
    }

    /**
     * Extract text data from pd-text element.
     */
    private function extractTextData(DOMElement $element, DOMDocument $dom): array
    {
        return [
            'text' => $this->getInnerHtml($element, $dom),
        ];
    }

    /**
     * Extract image block from img element.
     */
    private function extractImageBlock(DOMElement $element): array
    {
        return [
            'type' => 'image',
            'data' => [
                'content' => $this->extractImageData($element),
                'settings' => [],
            ],
        ];
    }

    /**
     * Extract image data.
     */
    private function extractImageData(DOMElement $element): array
    {
        $xpath = new DOMXPath($element->ownerDocument);
        $img = $element->tagName === 'img' ? $element : $xpath->query('.//img', $element)->item(0);

        if (!$img) {
            return ['src' => '', 'alt' => ''];
        }

        return [
            'src' => $img->getAttribute('src'),
            'alt' => $img->getAttribute('alt'),
            'title' => $img->getAttribute('title'),
        ];
    }

    /**
     * Extract gallery data.
     */
    private function extractGalleryData(DOMElement $element, DOMDocument $dom): array
    {
        $xpath = new DOMXPath($dom);
        $images = $xpath->query('.//img', $element);

        $items = [];
        foreach ($images as $img) {
            $items[] = [
                'src' => $img->getAttribute('src'),
                'alt' => $img->getAttribute('alt'),
            ];
        }

        // Extract columns from class
        $classes = $element->getAttribute('class');
        $columns = 3;
        if (preg_match('/pd-gallery--cols-(\d+)/', $classes, $matches)) {
            $columns = (int) $matches[1];
        }

        return [
            'images' => $items,
            'columns' => $columns,
            'lightbox' => str_contains($classes, 'lightbox'),
        ];
    }

    /**
     * Extract video block from iframe.
     */
    private function extractVideoBlock(DOMElement $element): array
    {
        return [
            'type' => 'video-embed',
            'data' => [
                'content' => $this->extractVideoData($element),
                'settings' => [],
            ],
        ];
    }

    /**
     * Extract video data.
     */
    private function extractVideoData(DOMElement $element): array
    {
        $xpath = new DOMXPath($element->ownerDocument);
        $iframe = $element->tagName === 'iframe' ? $element : $xpath->query('.//iframe', $element)->item(0);

        $url = '';
        if ($iframe) {
            $url = $iframe->getAttribute('src');
        }

        return [
            'url' => $url,
            'autoplay' => false,
            'controls' => true,
        ];
    }

    /**
     * Extract full-width section data.
     */
    private function extractFullWidthData(DOMElement $element, DOMDocument $dom): array
    {
        $classes = $element->getAttribute('class');
        $innerHtml = $this->getInnerHtml($element, $dom);

        // Determine background from classes
        $background = 'transparent';
        if (str_contains($classes, 'bg-brand')) {
            $background = 'brand';
        } elseif (str_contains($classes, 'bg-dark')) {
            $background = 'dark';
        }

        return [
            'html' => $innerHtml,
            'background' => $background,
            'padding' => 'normal',
            'original_classes' => $classes,
        ];
    }

    /**
     * Extract raw HTML data - preserves entire HTML structure.
     */
    private function extractRawHtmlData(DOMElement $element, DOMDocument $dom): array
    {
        return [
            'html' => $this->getOuterHtml($element, $dom),
            'wrapper_class' => '',
            'wrapper_id' => '',
            'sanitize' => true,
        ];
    }

    /**
     * Create array with single RawHtmlBlock.
     */
    private function createRawHtmlBlock(string $html): array
    {
        return [$this->createSingleRawHtmlBlock($html)];
    }

    /**
     * Create single RawHtmlBlock structure.
     */
    private function createSingleRawHtmlBlock(string $html): array
    {
        return [
            'type' => 'raw-html',
            'data' => [
                'content' => [
                    'html' => $html,
                    'wrapper_class' => '',
                    'wrapper_id' => '',
                    'sanitize' => true,
                ],
                'settings' => [],
            ],
        ];
    }

    /**
     * Create DOMDocument from HTML string.
     */
    private function createDomDocument(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        // Wrap in proper HTML structure with UTF-8
        $wrappedHtml = '<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>';

        if (!$dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return null;
        }

        libxml_clear_errors();
        return $dom;
    }

    /**
     * Get inner HTML of element.
     */
    private function getInnerHtml(DOMElement $element, DOMDocument $dom): string
    {
        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }
        return trim($innerHTML);
    }

    /**
     * Get outer HTML of element.
     */
    private function getOuterHtml(DOMElement $element, DOMDocument $dom): string
    {
        return trim($dom->saveHTML($element));
    }

    /**
     * Extract style property value.
     */
    private function extractStyleValue(string $style, string $property, string $default = ''): string
    {
        if (preg_match("/{$property}:\s*([^;]+)/i", $style, $matches)) {
            return trim($matches[1]);
        }
        return $default;
    }

    /**
     * Check if element has block-level children.
     */
    private function hasBlockChildren(DOMElement $element): bool
    {
        $blockTags = ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'section', 'article'];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), $blockTags)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get available class mappings (for documentation/debugging).
     */
    public function getClassMappings(): array
    {
        return $this->classToBlockMap;
    }
}
