<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * CSS Rule Generator for Unified Visual Editor.
 *
 * Generates CSS rules from UVE blocks and elements.
 * Handles rule formatting, media queries, and CSS output generation.
 *
 * ETAP_07f_P5 FAZA 5: CSS Synchronizacja
 *
 * @package App\Services\VisualEditor
 */
class CssRuleGenerator
{
    /**
     * UVE namespace prefix for generated rules.
     */
    private const UVE_NAMESPACE = 'uve';

    /**
     * CSS section markers for injection.
     *
     * CRITICAL FIX: Must match CssSyncOrchestrator markers!
     * Previous mismatch caused CSS sync failures.
     */
    private const SECTION_START = '/* @uve-styles-start */';
    private const SECTION_END = '/* @uve-styles-end */';

    /**
     * CSS Property Mapper instance.
     */
    private CssPropertyMapper $propertyMapper;

    public function __construct(?CssPropertyMapper $propertyMapper = null)
    {
        $this->propertyMapper = $propertyMapper ?? new CssPropertyMapper();
    }

    /**
     * Generate CSS rules from UVE blocks array.
     *
     * @param array $blocks UVE blocks array
     * @param int|null $shopId Shop ID for scoping
     * @param int|null $productId Product ID for scoping
     * @return string Generated CSS
     */
    public function generateFromBlocks(array $blocks, ?int $shopId = null, ?int $productId = null): string
    {
        $rules = [];

        foreach ($blocks as $block) {
            $blockRules = $this->generateBlockRules($block, $shopId, $productId);
            if (!empty($blockRules)) {
                $rules = array_merge($rules, $blockRules);
            }
        }

        if (empty($rules)) {
            return '';
        }

        return $this->formatCssOutput($rules, $shopId, $productId);
    }

    /**
     * Generate CSS rules from a single block.
     *
     * @param array $block UVE block
     * @param int|null $shopId Shop ID
     * @param int|null $productId Product ID
     * @return array Array of CSS rule arrays [selector => properties]
     */
    public function generateBlockRules(array $block, ?int $shopId = null, ?int $productId = null): array
    {
        $rules = [];

        // Block-level document
        $document = $block['document'] ?? [];
        if (empty($document['root'])) {
            return $rules;
        }

        // Scope prefix for this product/shop
        $scopePrefix = $this->getScopeSelector($shopId, $productId);

        // Collect element rules recursively
        $this->collectElementRules($document['root'], $rules, $scopePrefix);

        // Add CSS classes defined in document
        if (!empty($document['cssClasses'])) {
            foreach ($document['cssClasses'] as $className => $properties) {
                if (is_array($properties) && !empty($properties)) {
                    $selector = $scopePrefix . '.' . $className;
                    $cssProps = $this->propertyMapper->mapStyles($properties);
                    if (!empty($cssProps)) {
                        $rules[$selector] = $cssProps;
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Recursively collect CSS rules from element tree.
     *
     * Processes both normal styles and hover styles for each element.
     */
    private function collectElementRules(array $element, array &$rules, string $scopePrefix): void
    {
        $elementId = $element['id'] ?? '';
        $styles = $element['styles'] ?? [];
        $hoverStyles = $element['hoverStyles'] ?? [];
        $cssClasses = $element['classes'] ?? [];

        // Generate rules for inline styles if present
        if (!empty($elementId) && !empty($styles)) {
            // Create selector using data attribute
            $selector = $scopePrefix . '[data-uve-id="' . $elementId . '"]';

            // Map styles to CSS properties
            $cssProps = $this->propertyMapper->mapStyles($styles);
            if (!empty($cssProps)) {
                $rules[$selector] = $cssProps;
            }
        }

        // Generate hover rules if hover styles are defined
        if (!empty($elementId) && !empty($hoverStyles)) {
            $hoverSelector = $scopePrefix . '[data-uve-id="' . $elementId . '"]:hover';
            $hoverCssProps = $this->propertyMapper->mapStyles($hoverStyles);
            if (!empty($hoverCssProps)) {
                $rules[$hoverSelector] = $hoverCssProps;
            }
        }

        // Process children
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                $this->collectElementRules($child, $rules, $scopePrefix);
            }
        }
    }

    /**
     * Get scope selector for shop/product.
     *
     * @param int|null $shopId
     * @param int|null $productId
     * @return string Scope selector prefix
     */
    private function getScopeSelector(?int $shopId, ?int $productId): string
    {
        $parts = [];

        if ($productId) {
            $parts[] = "[data-product-id=\"{$productId}\"]";
        }

        // Always add UVE scope
        $parts[] = '.uve-content';

        return implode(' ', $parts) . ' ';
    }

    /**
     * Format CSS rules array into final CSS output.
     *
     * @param array $rules [selector => [property => value, ...], ...]
     * @param int|null $shopId
     * @param int|null $productId
     * @param bool $minify Whether to minify output
     * @return string Formatted CSS
     */
    public function formatCssOutput(array $rules, ?int $shopId = null, ?int $productId = null, bool $minify = false): string
    {
        if (empty($rules)) {
            return '';
        }

        $output = [];
        $output[] = self::SECTION_START;

        // Add metadata comment
        $meta = [];
        if ($shopId) {
            $meta[] = "shop_id: {$shopId}";
        }
        if ($productId) {
            $meta[] = "product_id: {$productId}";
        }
        $meta[] = 'generated: ' . now()->toIso8601String();

        $output[] = '/* ' . implode(' | ', $meta) . ' */';
        $output[] = '';

        // Generate rules
        foreach ($rules as $selector => $properties) {
            if (empty($properties)) {
                continue;
            }

            if ($minify) {
                $output[] = $this->formatRuleMinified($selector, $properties);
            } else {
                $output[] = $this->formatRulePretty($selector, $properties);
            }
        }

        $output[] = '';
        $output[] = self::SECTION_END;

        return implode($minify ? '' : "\n", $output);
    }

    /**
     * Format single CSS rule (pretty format).
     */
    private function formatRulePretty(string $selector, array $properties): string
    {
        $lines = [];
        $lines[] = $selector . ' {';

        foreach ($properties as $property => $value) {
            $lines[] = '    ' . $property . ': ' . $value . ';';
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Format single CSS rule (minified).
     */
    private function formatRuleMinified(string $selector, array $properties): string
    {
        $declarations = [];

        foreach ($properties as $property => $value) {
            $declarations[] = $property . ':' . $value;
        }

        return $selector . '{' . implode(';', $declarations) . '}';
    }

    /**
     * Inject generated CSS into existing CSS content.
     * Replaces previous UVE section if exists, or appends.
     *
     * @param string $existingCss Existing CSS content
     * @param string $generatedCss Generated UVE CSS
     * @return string Merged CSS content
     */
    public function injectIntoExisting(string $existingCss, string $generatedCss): string
    {
        // Check if UVE section exists
        $startPos = strpos($existingCss, self::SECTION_START);
        $endPos = strpos($existingCss, self::SECTION_END);

        if ($startPos !== false && $endPos !== false) {
            // Replace existing section
            $before = substr($existingCss, 0, $startPos);
            $after = substr($existingCss, $endPos + strlen(self::SECTION_END));

            return rtrim($before) . "\n\n" . $generatedCss . "\n" . ltrim($after);
        }

        // Append to end
        return rtrim($existingCss) . "\n\n" . $generatedCss;
    }

    /**
     * Remove UVE generated section from CSS.
     *
     * @param string $css CSS content
     * @return string CSS without UVE section
     */
    public function removeUveSection(string $css): string
    {
        $startPos = strpos($css, self::SECTION_START);
        $endPos = strpos($css, self::SECTION_END);

        if ($startPos !== false && $endPos !== false) {
            $before = substr($css, 0, $startPos);
            $after = substr($css, $endPos + strlen(self::SECTION_END));

            return rtrim($before) . "\n" . ltrim($after);
        }

        return $css;
    }

    /**
     * Check if CSS contains UVE section.
     *
     * @param string $css CSS content
     * @return bool
     */
    public function hasUveSection(string $css): bool
    {
        return str_contains($css, self::SECTION_START) && str_contains($css, self::SECTION_END);
    }

    /**
     * Extract UVE section from CSS.
     *
     * @param string $css CSS content
     * @return string|null UVE section or null if not found
     */
    public function extractUveSection(string $css): ?string
    {
        $startPos = strpos($css, self::SECTION_START);
        $endPos = strpos($css, self::SECTION_END);

        if ($startPos !== false && $endPos !== false) {
            return substr($css, $startPos, $endPos + strlen(self::SECTION_END) - $startPos);
        }

        return null;
    }

    /**
     * Generate responsive CSS rules with media queries.
     *
     * @param array $rules Base rules
     * @param array $breakpoints Breakpoint-specific rules [breakpoint => [selector => props]]
     * @return string CSS with media queries
     */
    public function generateResponsiveRules(array $rules, array $breakpoints): string
    {
        $output = [];

        // Base rules (desktop-first)
        if (!empty($rules)) {
            foreach ($rules as $selector => $properties) {
                $output[] = $this->formatRulePretty($selector, $properties);
            }
        }

        // Breakpoint-specific rules
        $mediaQueries = [
            'tablet' => '@media (max-width: 1024px)',
            'mobile' => '@media (max-width: 768px)',
        ];

        foreach ($breakpoints as $breakpoint => $breakpointRules) {
            if (empty($breakpointRules)) {
                continue;
            }

            $mediaQuery = $mediaQueries[$breakpoint] ?? null;
            if (!$mediaQuery) {
                continue;
            }

            $output[] = '';
            $output[] = $mediaQuery . ' {';

            foreach ($breakpointRules as $selector => $properties) {
                // Indent rules inside media query
                $rule = $this->formatRulePretty($selector, $properties);
                $indentedRule = preg_replace('/^/m', '    ', $rule);
                $output[] = $indentedRule;
            }

            $output[] = '}';
        }

        return implode("\n", $output);
    }

    /**
     * Minify CSS string.
     *
     * @param string $css CSS content
     * @return string Minified CSS
     */
    public function minify(string $css): string
    {
        // Remove comments (except UVE markers)
        $css = preg_replace('/\/\*(?!.*UVE).*?\*\//s', '', $css);

        // Remove newlines and multiple spaces
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove spaces around special characters
        $css = preg_replace('/\s*([{};:,>+~])\s*/', '$1', $css);

        // Remove trailing semicolons before closing braces
        $css = preg_replace('/;+}/', '}', $css);

        return trim($css);
    }

    /**
     * Get section markers for reference.
     */
    public function getSectionMarkers(): array
    {
        return [
            'start' => self::SECTION_START,
            'end' => self::SECTION_END,
        ];
    }

    /**
     * Generate CSS variable definitions.
     *
     * @param array $variables [varName => value, ...]
     * @param string $selector Selector for :root or scope
     * @return string CSS variable definitions
     */
    public function generateVariables(array $variables, string $selector = ':root'): string
    {
        if (empty($variables)) {
            return '';
        }

        $lines = [];
        $lines[] = $selector . ' {';

        foreach ($variables as $name => $value) {
            // Ensure variable name starts with --
            $varName = str_starts_with($name, '--') ? $name : '--' . $name;
            $lines[] = '    ' . $varName . ': ' . $value . ';';
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Parse existing CSS rules into array format.
     *
     * @param string $css CSS content
     * @return array [selector => [property => value, ...], ...]
     */
    public function parseRules(string $css): array
    {
        $rules = [];

        // Simple regex to extract rules (handles most cases)
        preg_match_all('/([^{]+)\s*{\s*([^}]+)\s*}/s', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $selector = trim($match[1]);
            $declarations = trim($match[2]);

            if (empty($selector) || empty($declarations)) {
                continue;
            }

            // Parse declarations
            $properties = $this->propertyMapper->parseCssString($declarations);
            if (!empty($properties)) {
                $rules[$selector] = $properties;
            }
        }

        return $rules;
    }
}
