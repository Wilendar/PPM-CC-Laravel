<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * Exporter converting BlockDocument structure back to PrestaShop-compatible HTML.
 *
 * Reverse operation of HtmlToBlockDocumentParser:
 * - Converts ElementNode tree to HTML elements
 * - Preserves pd-* CSS classes for PrestaShop styling
 * - Converts camelCase styles to CSS kebab-case
 * - Maintains hierarchical structure (nested containers)
 */
class BlockDocumentToHtmlExporter
{
    /**
     * Style properties map (camelCase → kebab-case).
     */
    private const STYLE_MAP = [
        'backgroundColor' => 'background-color',
        'backgroundImage' => 'background-image',
        'backgroundSize' => 'background-size',
        'backgroundPosition' => 'background-position',
        'fontFamily' => 'font-family',
        'fontSize' => 'font-size',
        'fontWeight' => 'font-weight',
        'lineHeight' => 'line-height',
        'textAlign' => 'text-align',
        'borderWidth' => 'border-width',
        'borderStyle' => 'border-style',
        'borderColor' => 'border-color',
        'borderRadius' => 'border-radius',
        'minWidth' => 'min-width',
        'maxWidth' => 'max-width',
        'minHeight' => 'min-height',
        'objectFit' => 'object-fit',
        'objectPosition' => 'object-position',
        'flexDirection' => 'flex-direction',
        'justifyContent' => 'justify-content',
        'alignItems' => 'align-items',
        'flexWrap' => 'flex-wrap',
        'gridTemplateColumns' => 'grid-template-columns',
        'gridTemplateRows' => 'grid-template-rows',
        'gridGap' => 'grid-gap',
        'gridColumn' => 'grid-column',
        'gridRow' => 'grid-row',
        'listStyle' => 'list-style',
        'zIndex' => 'z-index',
        'boxShadow' => 'box-shadow',
    ];

    /**
     * Whether to include inline styles in output.
     */
    private bool $includeInlineStyles = true;

    /**
     * Whether to minify output HTML.
     */
    private bool $minify = false;

    /**
     * Variables for template replacement.
     */
    private array $variables = [];

    /**
     * Export BlockDocument to HTML string.
     */
    public function export(array $document, array $variableValues = []): string
    {
        $this->variables = $variableValues;

        $root = $document['root'] ?? null;
        if (!$root || empty($root['children'])) {
            return '';
        }

        // Export children of root (skip root wrapper)
        $html = '';
        foreach ($root['children'] as $element) {
            $html .= $this->exportElement($element);
        }

        // Apply variable substitution
        $html = $this->substituteVariables($html, $document['variables'] ?? []);

        return $this->minify ? $this->minifyHtml($html) : $html;
    }

    /**
     * Export single BlockDocument to HTML (wrapper method).
     */
    public function exportDocument(array $document, array $variableValues = []): string
    {
        return $this->export($document, $variableValues);
    }

    /**
     * Set whether to include inline styles.
     */
    public function setIncludeInlineStyles(bool $include): self
    {
        $this->includeInlineStyles = $include;
        return $this;
    }

    /**
     * Set whether to minify output.
     */
    public function setMinify(bool $minify): self
    {
        $this->minify = $minify;
        return $this;
    }

    /**
     * Export single element to HTML.
     */
    private function exportElement(array $element): string
    {
        // Skip hidden elements
        if (isset($element['visible']) && $element['visible'] === false) {
            return '';
        }

        $type = $element['type'] ?? 'container';

        return match ($type) {
            'heading' => $this->exportHeading($element),
            'text' => $this->exportText($element),
            'image' => $this->exportImage($element),
            'picture' => $this->exportPicture($element),
            'source' => $this->exportSource($element),
            'icon' => $this->exportIcon($element),
            'button' => $this->exportButton($element),
            'separator' => $this->exportSeparator($element),
            'container' => $this->exportContainer($element),
            'raw-html' => $this->exportRawHtml($element),
            default => $this->exportContainer($element),
        };
    }

    /**
     * Export heading element (h1-h6).
     */
    private function exportHeading(array $element): string
    {
        $tag = $element['tag'] ?? 'h2';
        $content = $element['content'] ?? '';

        // Content may contain HTML (e.g., span elements for model type/name)
        if ($this->isHtmlContent($content)) {
            // Allow safe HTML tags - same as text element
            $content = strip_tags($content, '<strong><em><b><i><u><a><br><span>');
        } else {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        $attributes = $this->buildAttributes($element);

        return "<{$tag}{$attributes}>{$content}</{$tag}>\n";
    }

    /**
     * Export text/paragraph element.
     */
    private function exportText(array $element): string
    {
        $tag = $element['tag'] ?? 'p';
        $content = $element['content'] ?? '';

        // Content may contain HTML (from WYSIWYG)
        if ($this->isHtmlContent($content)) {
            // Allow safe HTML tags
            $content = strip_tags($content, '<strong><em><b><i><u><a><br><span>');
        } else {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        $attributes = $this->buildAttributes($element);

        return "<{$tag}{$attributes}>{$content}</{$tag}>\n";
    }

    /**
     * Export image element.
     * Supports direct properties (src, alt, srcset, sizes, width, height) and nested props.
     */
    private function exportImage(array $element): string
    {
        // Check direct properties first, then fallback to props (backwards compatibility)
        $src = $element['src'] ?? ($element['props']['src'] ?? '');
        $alt = $element['alt'] ?? ($element['props']['alt'] ?? '');
        $srcset = $element['srcset'] ?? ($element['props']['srcset'] ?? '');
        $sizes = $element['sizes'] ?? ($element['props']['sizes'] ?? '');
        $width = $element['width'] ?? ($element['props']['width'] ?? '');
        $height = $element['height'] ?? ($element['props']['height'] ?? '');

        $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

        $attributes = $this->buildAttributes($element);

        // Build img tag with optional srcset/sizes/width/height
        $imgAttrs = "src=\"{$src}\" alt=\"{$alt}\"";

        if ($srcset) {
            $imgAttrs .= ' srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($sizes) {
            $imgAttrs .= ' sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($width) {
            $imgAttrs .= ' width="' . htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($height) {
            $imgAttrs .= ' height="' . htmlspecialchars((string) $height, ENT_QUOTES, 'UTF-8') . '"';
        }

        // Add loading lazy for performance
        return "<img {$imgAttrs} loading=\"lazy\"{$attributes}>\n";
    }

    /**
     * Export picture element with source and img children.
     * Structure: <picture class="..."><img ...></picture>
     */
    private function exportPicture(array $element): string
    {
        $attributes = $this->buildAttributes($element);

        $html = "<picture{$attributes}>";

        // Export children (should contain img element)
        foreach ($element['children'] ?? [] as $child) {
            $html .= ' ' . trim($this->exportElement($child));
        }

        $html .= " </picture>\n";

        return $html;
    }

    /**
     * Export source element inside picture.
     */
    private function exportSource(array $element): string
    {
        $srcset = $element['srcset'] ?? ($element['props']['srcset'] ?? '');
        $sizes = $element['sizes'] ?? ($element['props']['sizes'] ?? '');
        $media = $element['media'] ?? ($element['props']['media'] ?? '');
        $type = $element['mimeType'] ?? ($element['props']['type'] ?? '');

        if (!$srcset) {
            return '';
        }

        $attrs = ['srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"'];

        if ($sizes) {
            $attrs[] = 'sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($media) {
            $attrs[] = 'media="' . htmlspecialchars($media, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($type) {
            $attrs[] = 'type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<source ' . implode(' ', $attrs) . ">\n";
    }

    /**
     * Export icon element (pd-icon--*).
     */
    private function exportIcon(array $element): string
    {
        $props = $element['props'] ?? [];
        $iconName = $props['icon'] ?? 'check';

        // Ensure pd-icon class is present
        $classes = $element['classes'] ?? [];
        if (!$this->hasIconClass($classes)) {
            $classes[] = 'pd-icon--' . $iconName;
        }

        // Build element with icon classes
        $elementWithClasses = array_merge($element, ['classes' => $classes]);
        $attributes = $this->buildAttributes($elementWithClasses);

        return "<span{$attributes}></span>\n";
    }

    /**
     * Export button/CTA element.
     */
    private function exportButton(array $element): string
    {
        $props = $element['props'] ?? [];
        $text = htmlspecialchars($element['content'] ?? 'Click', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($props['url'] ?? '#', ENT_QUOTES, 'UTF-8');
        $variant = $props['variant'] ?? 'primary';

        // Ensure btn class is present
        $classes = $element['classes'] ?? [];
        if (!in_array('btn', $classes)) {
            $classes[] = 'btn';
            $classes[] = 'btn-' . $variant;
        }

        $elementWithClasses = array_merge($element, ['classes' => $classes]);
        $attributes = $this->buildAttributes($elementWithClasses);

        return "<a href=\"{$url}\"{$attributes}>{$text}</a>\n";
    }

    /**
     * Export separator/divider element.
     */
    private function exportSeparator(array $element): string
    {
        $attributes = $this->buildAttributes($element);
        return "<hr{$attributes}>\n";
    }

    /**
     * Export container element with children.
     */
    private function exportContainer(array $element): string
    {
        $tag = $element['tag'] ?? 'div';
        $attributes = $this->buildAttributes($element);

        $childrenHtml = '';
        $children = $element['children'] ?? [];

        foreach ($children as $child) {
            $childrenHtml .= $this->exportElement($child);
        }

        // Self-closing if no children
        if (empty($childrenHtml)) {
            return "<{$tag}{$attributes}></{$tag}>\n";
        }

        return "<{$tag}{$attributes}>\n{$childrenHtml}</{$tag}>\n";
    }

    /**
     * Export raw HTML element.
     */
    private function exportRawHtml(array $element): string
    {
        return $element['content'] ?? '';
    }

    /**
     * Build HTML attributes string from element.
     */
    private function buildAttributes(array $element): string
    {
        $attrs = [];

        // Classes
        $classes = $element['classes'] ?? [];
        if (!empty($classes)) {
            $classString = implode(' ', array_filter($classes));
            if ($classString) {
                $attrs[] = 'class="' . htmlspecialchars($classString, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        // Inline styles
        if ($this->includeInlineStyles) {
            $styles = $element['styles'] ?? [];
            $styleString = $this->buildStyleString($styles);
            if ($styleString) {
                $attrs[] = 'style="' . htmlspecialchars($styleString, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        // Data attributes
        $data = $element['data'] ?? [];
        foreach ($data as $key => $value) {
            $attrs[] = 'data-' . $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        // ID attribute (for anchors)
        if (!empty($element['htmlId'])) {
            $attrs[] = 'id="' . htmlspecialchars($element['htmlId'], ENT_QUOTES, 'UTF-8') . '"';
        }

        return empty($attrs) ? '' : ' ' . implode(' ', $attrs);
    }

    /**
     * Build CSS style string from styles array.
     */
    private function buildStyleString(array $styles): string
    {
        if (empty($styles)) {
            return '';
        }

        $cssDeclarations = [];

        foreach ($styles as $property => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Convert camelCase to kebab-case
            $cssProperty = self::STYLE_MAP[$property] ?? $this->camelToKebab($property);
            $cssDeclarations[] = $cssProperty . ': ' . $value;
        }

        return implode('; ', $cssDeclarations);
    }

    /**
     * Convert camelCase to kebab-case.
     */
    private function camelToKebab(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Check if content contains HTML.
     */
    private function isHtmlContent(string $content): bool
    {
        return preg_match('/<[^>]+>/', $content) === 1;
    }

    /**
     * Check if classes array contains icon class.
     */
    private function hasIconClass(array $classes): bool
    {
        foreach ($classes as $class) {
            if (str_contains($class, 'pd-icon')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Substitute variables in HTML.
     */
    private function substituteVariables(string $html, array $documentVariables): string
    {
        // Build variable map
        $variableMap = [];
        foreach ($documentVariables as $var) {
            $name = $var['name'] ?? '';
            $defaultValue = $var['defaultValue'] ?? '';
            $variableMap[$name] = $this->variables[$name] ?? $defaultValue;
        }

        // Replace {{varName}} patterns
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($variableMap) {
                $varName = $matches[1];
                return htmlspecialchars($variableMap[$varName] ?? $matches[0], ENT_QUOTES, 'UTF-8');
            },
            $html
        );
    }

    /**
     * Minify HTML output.
     */
    private function minifyHtml(string $html): string
    {
        // Remove newlines and extra spaces
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        return trim($html);
    }
}
