<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Parser converting HTML to BlockDocument structure for Visual Block Builder.
 *
 * Converts HTML elements into BlockDocument JSON format:
 * - Detects element types (heading, text, image, icon, button, etc.)
 * - Extracts inline styles and CSS classes
 * - Preserves hierarchy (nested containers)
 * - Identifies PrestaShop pd-* patterns
 */
class HtmlToBlockDocumentParser
{
    /**
     * PrestaShop icon class prefix.
     */
    private const ICON_PREFIX = 'pd-icon--';

    /**
     * Counter for generating unique element IDs.
     */
    private int $elementCounter = 0;

    /**
     * Extracted variables from content.
     */
    private array $variables = [];

    /**
     * CSS classes found during parsing.
     */
    private array $cssClasses = [];

    /**
     * Parse HTML string into BlockDocument structure.
     */
    public function parse(string $html, string $name = 'Imported Block'): array
    {
        $this->elementCounter = 0;
        $this->variables = [];
        $this->cssClasses = [];

        if (empty(trim($html))) {
            return $this->createEmptyDocument($name);
        }

        $dom = $this->createDomDocument($html);
        if (!$dom) {
            return $this->createEmptyDocument($name);
        }

        // Find root element
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $this->createEmptyDocument($name);
        }

        // Parse children into elements
        $children = [];
        foreach ($body->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $element = $this->parseElement($node, $dom);
                if ($element) {
                    $children[] = $element;
                }
            }
        }

        // Create root container
        $root = $this->createContainerElement($children);

        return [
            'id' => 'doc-' . Str::random(8),
            'name' => $name,
            'type' => 'custom-block',
            'root' => $root,
            'variables' => $this->variables,
            'cssClasses' => array_unique($this->cssClasses),
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Parse single DOM element into BlockDocument element.
     */
    private function parseElement(DOMElement $element, DOMDocument $dom): ?array
    {
        $tagName = strtolower($element->tagName);
        $classes = $element->getAttribute('class');
        $style = $element->getAttribute('style');

        // Track CSS classes
        if ($classes) {
            foreach (explode(' ', $classes) as $class) {
                $class = trim($class);
                if ($class) {
                    $this->cssClasses[] = $class;
                }
            }
        }

        // Detect element type based on tag and classes
        $elementType = $this->detectElementType($element, $tagName, $classes);

        return match ($elementType) {
            'heading' => $this->parseHeading($element, $tagName, $classes, $style),
            'text' => $this->parseText($element, $dom, $classes, $style),
            'image' => $this->parseImage($element, $classes, $style),
            'picture' => $this->parsePicture($element, $dom, $classes, $style),
            'icon' => $this->parseIcon($element, $classes, $style),
            'button' => $this->parseButton($element, $classes, $style),
            'separator' => $this->parseSeparator($element, $classes, $style),
            'container' => $this->parseContainer($element, $dom, $classes, $style),
            'row' => $this->parseRow($element, $dom, $classes, $style),
            default => $this->parseGenericContainer($element, $dom, $tagName, $classes, $style),
        };
    }

    /**
     * Detect element type from DOM element.
     */
    private function detectElementType(DOMElement $element, string $tagName, string $classes): string
    {
        // Heading tags
        if (in_array($tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            return 'heading';
        }

        // Paragraph/text
        if ($tagName === 'p' || str_contains($classes, 'pd-text')) {
            return 'text';
        }

        // Picture element (wrapper for responsive images)
        if ($tagName === 'picture') {
            return 'picture';
        }

        // Image
        if ($tagName === 'img') {
            return 'image';
        }

        // Icon (span/i with pd-icon class)
        if (($tagName === 'span' || $tagName === 'i') && str_contains($classes, 'pd-icon')) {
            return 'icon';
        }

        // Button/link with btn class
        if (($tagName === 'a' || $tagName === 'button') && str_contains($classes, 'btn')) {
            return 'button';
        }

        // Separator
        if ($tagName === 'hr' || str_contains($classes, 'pd-separator') || str_contains($classes, 'separator')) {
            return 'separator';
        }

        // Row/flex container
        if (str_contains($classes, 'pd-merit') || str_contains($classes, 'pd-asset')) {
            return 'row';
        }

        // Grid/flex containers
        if (str_contains($classes, 'pd-merits') || str_contains($classes, 'pd-asset-list') ||
            str_contains($classes, 'grid') || str_contains($classes, 'flex')) {
            return 'container';
        }

        // Generic div/section = container
        if (in_array($tagName, ['div', 'section', 'article', 'main', 'header', 'footer', 'aside'])) {
            return 'container';
        }

        // Default to container for unknown elements with children
        if ($element->hasChildNodes()) {
            return 'container';
        }

        return 'text';
    }

    /**
     * Parse heading element (h1-h6).
     */
    private function parseHeading(DOMElement $element, string $tagName, string $classes, string $style): array
    {
        $content = trim($element->textContent);

        // Detect if content might be a variable
        if ($this->looksLikeProductField($content)) {
            $varName = $this->extractVariableName($content);
            $this->addVariable($varName, 'text', $content);
            $content = '{{' . $varName . '}}';
        }

        return [
            'id' => $this->generateElementId(),
            'type' => 'heading',
            'tag' => $tagName,
            'content' => $content,
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse text/paragraph element.
     */
    private function parseText(DOMElement $element, DOMDocument $dom, string $classes, string $style): array
    {
        $content = $this->getInnerHtml($element, $dom);

        return [
            'id' => $this->generateElementId(),
            'type' => 'text',
            'tag' => 'p',
            'content' => trim($content),
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse image element with full responsive image support.
     * Extracts srcset, sizes, width, height as top-level properties.
     * Replaces original image URL with placeholder preserving dimensions.
     */
    private function parseImage(DOMElement $element, string $classes, string $style): array
    {
        $originalSrc = $element->getAttribute('src') ?: $element->getAttribute('data-src');
        $alt = $element->getAttribute('alt');
        $srcset = $element->getAttribute('srcset');
        $sizes = $element->getAttribute('sizes');

        // Extract dimensions from element attributes or inline style
        $width = $element->getAttribute('width') ? (int) $element->getAttribute('width') : 960;
        $height = $element->getAttribute('height') ? (int) $element->getAttribute('height') : 660;

        // Try to extract from style if not in attributes
        $styles = $this->parseInlineStyles($style);
        if (isset($styles['width']) && preg_match('/(\d+)/', $styles['width'], $m)) {
            $width = (int) $m[1];
        }
        if (isset($styles['height']) && preg_match('/(\d+)/', $styles['height'], $m)) {
            $height = (int) $m[1];
        }

        // Generate placeholder URL with original dimensions
        $placeholderSrc = $this->generatePlaceholderUrl($width, $height, $alt);

        // Extract object-fit from classes
        if (str_contains($classes, 'object-cover')) {
            $styles['objectFit'] = 'cover';
        } elseif (str_contains($classes, 'object-contain')) {
            $styles['objectFit'] = 'contain';
        }

        // Build element with TOP-LEVEL responsive properties (not nested in props)
        $imageElement = [
            'id' => $this->generateElementId(),
            'type' => 'image',
            'tag' => 'img',
            'content' => null,
            // TOP-LEVEL properties for direct access by exporter/renderer
            'src' => $placeholderSrc,
            'alt' => $alt ?: 'Obraz produktu',
            'width' => $width,
            'height' => $height,
            // Preserve original for reference
            'originalSrc' => $originalSrc,
            'props' => [
                'src' => $placeholderSrc,
                'alt' => $alt ?: 'Obraz produktu',
                'originalSrc' => $originalSrc,
                'width' => $width,
                'height' => $height,
            ],
            'styles' => $styles,
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];

        // Add srcset/sizes only if present in original
        if ($srcset) {
            $imageElement['srcset'] = $this->convertSrcsetToPlaceholders($srcset, $width, $height, $alt);
            $imageElement['originalSrcset'] = $srcset;
            $imageElement['props']['srcset'] = $imageElement['srcset'];
            $imageElement['props']['originalSrcset'] = $srcset;
        }

        if ($sizes) {
            $imageElement['sizes'] = $sizes;
            $imageElement['props']['sizes'] = $sizes;
        }

        return $imageElement;
    }

    /**
     * Convert srcset URLs to placeholders preserving dimensions.
     */
    private function convertSrcsetToPlaceholders(string $srcset, int $defaultWidth, int $defaultHeight, string $alt = ''): string
    {
        $entries = array_map('trim', explode(',', $srcset));
        $converted = [];

        foreach ($entries as $entry) {
            // Parse "url WIDTHw" or "url Xx" format
            if (preg_match('/^(.+?)\s+(\d+)w$/i', $entry, $matches)) {
                $entryWidth = (int) $matches[2];
                // Calculate proportional height
                $ratio = $defaultWidth > 0 ? $defaultHeight / $defaultWidth : 0.6875;
                $entryHeight = (int) round($entryWidth * $ratio);
                $placeholder = $this->generatePlaceholderUrl($entryWidth, $entryHeight, $alt);
                $converted[] = "{$placeholder} {$entryWidth}w";
            } elseif (preg_match('/^(.+?)\s+(\d+(?:\.\d+)?)x$/i', $entry, $matches)) {
                // Handle Xx format (e.g., 2x)
                $multiplier = (float) $matches[2];
                $entryWidth = (int) round($defaultWidth * $multiplier);
                $entryHeight = (int) round($defaultHeight * $multiplier);
                $placeholder = $this->generatePlaceholderUrl($entryWidth, $entryHeight, $alt);
                $converted[] = "{$placeholder} {$multiplier}x";
            } else {
                // Fallback - use default dimensions
                $placeholder = $this->generatePlaceholderUrl($defaultWidth, $defaultHeight, $alt);
                $converted[] = $placeholder;
            }
        }

        return implode(', ', $converted);
    }

    /**
     * Parse picture element (responsive image wrapper).
     * Preserves structure: <picture class="..."><img ...></picture>
     */
    private function parsePicture(DOMElement $element, DOMDocument $dom, string $classes, string $style): array
    {
        $children = [];

        // Parse child elements (should contain <source> and <img>)
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $childTag = strtolower($child->tagName);

                if ($childTag === 'source') {
                    // Parse source element for art direction/format switching
                    $sourceElement = $this->parseSourceElement($child);
                    if ($sourceElement) {
                        $children[] = $sourceElement;
                    }
                } elseif ($childTag === 'img') {
                    // Parse the img element with full responsive attributes
                    $childClasses = $child->getAttribute('class');
                    $childStyle = $child->getAttribute('style');
                    $children[] = $this->parseImage($child, $childClasses, $childStyle);
                }
            }
        }

        return [
            'id' => $this->generateElementId(),
            'type' => 'picture',
            'tag' => 'picture',
            'content' => null,
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => $children,
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse source element inside picture.
     */
    private function parseSourceElement(DOMElement $element): ?array
    {
        $srcset = $element->getAttribute('srcset');
        $sizes = $element->getAttribute('sizes');
        $media = $element->getAttribute('media');
        $type = $element->getAttribute('type');

        if (!$srcset) {
            return null;
        }

        return [
            'id' => $this->generateElementId(),
            'type' => 'source',
            'tag' => 'source',
            'content' => null,
            'srcset' => $srcset,
            'sizes' => $sizes,
            'media' => $media,
            'mimeType' => $type,
            'props' => [
                'srcset' => $srcset,
                'sizes' => $sizes,
                'media' => $media,
                'type' => $type,
            ],
            'styles' => [],
            'classes' => [],
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Generate placeholder image URL with specific dimensions.
     */
    private function generatePlaceholderUrl(int $width, int $height, string $alt = ''): string
    {
        // Use placehold.co service for placeholder images
        $text = $alt ? urlencode(substr($alt, 0, 30)) : "{$width}x{$height}";
        return "https://placehold.co/{$width}x{$height}/374151/9ca3af?text=" . urlencode($text);
    }

    /**
     * Parse icon element (pd-icon--*).
     */
    private function parseIcon(DOMElement $element, string $classes, string $style): array
    {
        // Extract icon name from pd-icon--* class
        $iconName = 'check';
        if (preg_match('/pd-icon--([a-z0-9-]+)/i', $classes, $matches)) {
            $iconName = $matches[1];
        }

        return [
            'id' => $this->generateElementId(),
            'type' => 'icon',
            'tag' => 'span',
            'content' => null,
            'props' => [
                'icon' => $iconName,
                'size' => $this->detectIconSize($classes),
            ],
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse button/CTA element.
     */
    private function parseButton(DOMElement $element, string $classes, string $style): array
    {
        $text = trim($element->textContent);
        $url = $element->getAttribute('href') ?: '#';

        // Detect variant from classes
        $variant = 'primary';
        if (str_contains($classes, 'btn-secondary') || str_contains($classes, 'btn-outline')) {
            $variant = 'secondary';
        } elseif (str_contains($classes, 'btn-danger')) {
            $variant = 'danger';
        } elseif (str_contains($classes, 'btn-success')) {
            $variant = 'success';
        }

        return [
            'id' => $this->generateElementId(),
            'type' => 'button',
            'tag' => 'a',
            'content' => $text,
            'props' => [
                'url' => $url,
                'variant' => $variant,
                'size' => $this->detectButtonSize($classes),
            ],
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse separator/divider element.
     */
    private function parseSeparator(DOMElement $element, string $classes, string $style): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'separator',
            'tag' => 'hr',
            'content' => null,
            'styles' => $this->parseInlineStyles($style),
            'classes' => $this->parseClasses($classes),
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse container element with children.
     */
    private function parseContainer(DOMElement $element, DOMDocument $dom, string $classes, string $style): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $parsed = $this->parseElement($child, $dom);
                if ($parsed) {
                    $children[] = $parsed;
                }
            }
        }

        $styles = $this->parseInlineStyles($style);

        // Detect layout from classes
        $layout = $this->detectLayout($classes);
        $styles = array_merge($styles, $layout);

        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'content' => null,
            'styles' => $styles,
            'classes' => $this->parseClasses($classes),
            'children' => $children,
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse row element (horizontal flex).
     */
    private function parseRow(DOMElement $element, DOMDocument $dom, string $classes, string $style): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $parsed = $this->parseElement($child, $dom);
                if ($parsed) {
                    $children[] = $parsed;
                }
            }
        }

        $styles = $this->parseInlineStyles($style);
        $styles['display'] = 'flex';
        $styles['flexDirection'] = 'column';
        $styles['alignItems'] = 'center';
        $styles['gap'] = '0.5rem';

        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'content' => null,
            'styles' => $styles,
            'classes' => $this->parseClasses($classes),
            'children' => $children,
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Parse generic container (fallback).
     */
    private function parseGenericContainer(DOMElement $element, DOMDocument $dom, string $tagName, string $classes, string $style): array
    {
        // Check if has meaningful children
        $hasElementChildren = false;
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElementChildren = true;
                break;
            }
        }

        if ($hasElementChildren) {
            return $this->parseContainer($element, $dom, $classes, $style);
        }

        // Treat as text if only text content
        $content = trim($element->textContent);
        if ($content) {
            return [
                'id' => $this->generateElementId(),
                'type' => 'text',
                'tag' => $tagName,
                'content' => $content,
                'styles' => $this->parseInlineStyles($style),
                'classes' => $this->parseClasses($classes),
                'children' => [],
                'visible' => true,
                'locked' => false,
            ];
        }

        return null;
    }

    /**
     * Parse inline style string to style properties array.
     */
    private function parseInlineStyles(string $style): array
    {
        $styles = [];
        if (empty($style)) {
            return $styles;
        }

        // Map CSS properties to camelCase
        $propertyMap = [
            'background-color' => 'backgroundColor',
            'background-image' => 'backgroundImage',
            'background-size' => 'backgroundSize',
            'background-position' => 'backgroundPosition',
            'font-family' => 'fontFamily',
            'font-size' => 'fontSize',
            'font-weight' => 'fontWeight',
            'line-height' => 'lineHeight',
            'text-align' => 'textAlign',
            'border-width' => 'borderWidth',
            'border-style' => 'borderStyle',
            'border-color' => 'borderColor',
            'border-radius' => 'borderRadius',
            'min-width' => 'minWidth',
            'max-width' => 'maxWidth',
            'min-height' => 'minHeight',
            'object-fit' => 'objectFit',
            'object-position' => 'objectPosition',
            'flex-direction' => 'flexDirection',
            'justify-content' => 'justifyContent',
            'align-items' => 'alignItems',
            'flex-wrap' => 'flexWrap',
            'grid-template-columns' => 'gridTemplateColumns',
            'grid-template-rows' => 'gridTemplateRows',
            'grid-gap' => 'gridGap',
            'z-index' => 'zIndex',
            'box-shadow' => 'boxShadow',
        ];

        // Parse style string
        $declarations = explode(';', $style);
        foreach ($declarations as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);

                // Convert to camelCase
                $camelProperty = $propertyMap[$property] ?? lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $property))));
                $styles[$camelProperty] = $value;
            }
        }

        return $styles;
    }

    /**
     * Parse class string to array.
     */
    private function parseClasses(string $classes): array
    {
        if (empty($classes)) {
            return [];
        }

        return array_filter(array_map('trim', explode(' ', $classes)));
    }

    /**
     * Detect layout properties from CSS classes.
     */
    private function detectLayout(string $classes): array
    {
        $layout = [];

        // Grid detection
        if (str_contains($classes, 'grid')) {
            $layout['display'] = 'grid';

            // Detect columns
            if (preg_match('/grid-cols-(\d+)|cols-(\d+)/', $classes, $matches)) {
                $cols = $matches[1] ?: $matches[2];
                $layout['gridTemplateColumns'] = "repeat({$cols}, 1fr)";
            }
        }

        // Flex detection
        if (str_contains($classes, 'flex')) {
            $layout['display'] = 'flex';

            if (str_contains($classes, 'flex-col') || str_contains($classes, 'flex-column')) {
                $layout['flexDirection'] = 'column';
            } else {
                $layout['flexDirection'] = 'row';
            }

            if (str_contains($classes, 'items-center')) {
                $layout['alignItems'] = 'center';
            }

            if (str_contains($classes, 'justify-center')) {
                $layout['justifyContent'] = 'center';
            }
        }

        // PrestaShop specific layouts
        if (str_contains($classes, 'pd-merits')) {
            $layout['display'] = 'grid';
            $layout['gridTemplateColumns'] = 'repeat(3, 1fr)';
            $layout['gap'] = '2rem';
        }

        if (str_contains($classes, 'pd-asset-list')) {
            $layout['display'] = 'flex';
            $layout['flexDirection'] = 'row';
            $layout['justifyContent'] = 'center';
            $layout['gap'] = '2rem';
        }

        return $layout;
    }

    /**
     * Detect icon size from classes.
     */
    private function detectIconSize(string $classes): string
    {
        if (str_contains($classes, 'icon-sm') || str_contains($classes, 'text-sm')) {
            return 'S';
        }
        if (str_contains($classes, 'icon-lg') || str_contains($classes, 'text-lg')) {
            return 'XL';
        }
        if (str_contains($classes, 'icon-xl') || str_contains($classes, 'text-xl')) {
            return '2XL';
        }
        return 'L'; // Default
    }

    /**
     * Detect button size from classes.
     */
    private function detectButtonSize(string $classes): string
    {
        if (str_contains($classes, 'btn-sm')) {
            return 'sm';
        }
        if (str_contains($classes, 'btn-lg')) {
            return 'lg';
        }
        if (str_contains($classes, 'btn-xl')) {
            return 'xl';
        }
        return 'md'; // Default
    }

    /**
     * Check if content looks like a product field value.
     */
    private function looksLikeProductField(string $content): bool
    {
        // Check for variable syntax
        if (preg_match('/\{\{.*\}\}/', $content)) {
            return true;
        }

        // Common product field patterns
        $patterns = [
            '/^KAYO\s+\w+/i',  // Product model names
            '/^\d+\s*(cm3|KM|mm|kg)$/i',  // Specs with units
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract variable name from content.
     */
    private function extractVariableName(string $content): string
    {
        // If already has {{var}} syntax, extract it
        if (preg_match('/\{\{(\w+)\}\}/', $content, $matches)) {
            return $matches[1];
        }

        // Generate variable name from content
        return Str::camel(Str::limit(Str::slug($content, '_'), 20, ''));
    }

    /**
     * Add variable to the document.
     */
    private function addVariable(string $name, string $type, $defaultValue): void
    {
        $this->variables[] = [
            'name' => $name,
            'type' => $type,
            'label' => ucfirst(str_replace('_', ' ', $name)),
            'defaultValue' => $defaultValue,
        ];
    }

    /**
     * Generate unique element ID.
     */
    private function generateElementId(): string
    {
        $this->elementCounter++;
        return 'el-' . Str::random(8);
    }

    /**
     * Create root container element.
     */
    private function createContainerElement(array $children): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'content' => null,
            'styles' => [
                'display' => 'flex',
                'flexDirection' => 'column',
                'gap' => '1rem',
            ],
            'classes' => ['pd-block'],
            'children' => $children,
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Create empty document structure.
     */
    private function createEmptyDocument(string $name): array
    {
        return [
            'id' => 'doc-' . Str::random(8),
            'name' => $name,
            'type' => 'custom-block',
            'root' => $this->createContainerElement([]),
            'variables' => [],
            'cssClasses' => [],
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Create DOMDocument from HTML string.
     */
    private function createDomDocument(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        libxml_use_internal_errors(true);

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
}
