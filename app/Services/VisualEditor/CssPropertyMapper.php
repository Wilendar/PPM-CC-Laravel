<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * CSS Property Mapper for Unified Visual Editor.
 *
 * Maps VBB (Visual Block Builder) element inline styles to standard CSS properties.
 * Handles camelCase to kebab-case conversion, unit normalization, and value validation.
 *
 * ETAP_07f_P5 FAZA 5: CSS Synchronizacja
 *
 * @package App\Services\VisualEditor
 */
class CssPropertyMapper
{
    /**
     * Map of camelCase property names to CSS kebab-case names.
     */
    private const PROPERTY_MAP = [
        // Typography
        'fontSize' => 'font-size',
        'fontWeight' => 'font-weight',
        'fontFamily' => 'font-family',
        'fontStyle' => 'font-style',
        'lineHeight' => 'line-height',
        'letterSpacing' => 'letter-spacing',
        'textAlign' => 'text-align',
        'textDecoration' => 'text-decoration',
        'textTransform' => 'text-transform',
        'whiteSpace' => 'white-space',
        'wordBreak' => 'word-break',
        'wordSpacing' => 'word-spacing',

        // Colors
        'color' => 'color',
        'backgroundColor' => 'background-color',
        'borderColor' => 'border-color',

        // Background
        'background' => 'background',
        'backgroundImage' => 'background-image',
        'backgroundSize' => 'background-size',
        'backgroundPosition' => 'background-position',
        'backgroundRepeat' => 'background-repeat',
        'backgroundAttachment' => 'background-attachment',

        // Box Model - Spacing
        'margin' => 'margin',
        'marginTop' => 'margin-top',
        'marginRight' => 'margin-right',
        'marginBottom' => 'margin-bottom',
        'marginLeft' => 'margin-left',
        'padding' => 'padding',
        'paddingTop' => 'padding-top',
        'paddingRight' => 'padding-right',
        'paddingBottom' => 'padding-bottom',
        'paddingLeft' => 'padding-left',

        // Box Model - Sizing
        'width' => 'width',
        'minWidth' => 'min-width',
        'maxWidth' => 'max-width',
        'height' => 'height',
        'minHeight' => 'min-height',
        'maxHeight' => 'max-height',

        // Border
        'border' => 'border',
        'borderWidth' => 'border-width',
        'borderStyle' => 'border-style',
        'borderRadius' => 'border-radius',
        'borderTop' => 'border-top',
        'borderRight' => 'border-right',
        'borderBottom' => 'border-bottom',
        'borderLeft' => 'border-left',

        // Display & Layout
        'display' => 'display',
        'position' => 'position',
        'top' => 'top',
        'right' => 'right',
        'bottom' => 'bottom',
        'left' => 'left',
        'zIndex' => 'z-index',
        'overflow' => 'overflow',
        'overflowX' => 'overflow-x',
        'overflowY' => 'overflow-y',
        'visibility' => 'visibility',
        'opacity' => 'opacity',

        // Flexbox
        'flexDirection' => 'flex-direction',
        'flexWrap' => 'flex-wrap',
        'justifyContent' => 'justify-content',
        'alignItems' => 'align-items',
        'alignContent' => 'align-content',
        'alignSelf' => 'align-self',
        'flex' => 'flex',
        'flexGrow' => 'flex-grow',
        'flexShrink' => 'flex-shrink',
        'flexBasis' => 'flex-basis',
        'order' => 'order',
        'gap' => 'gap',
        'rowGap' => 'row-gap',
        'columnGap' => 'column-gap',

        // Grid
        'gridTemplateColumns' => 'grid-template-columns',
        'gridTemplateRows' => 'grid-template-rows',
        'gridColumn' => 'grid-column',
        'gridRow' => 'grid-row',
        'gridArea' => 'grid-area',
        'gridAutoFlow' => 'grid-auto-flow',
        'gridAutoColumns' => 'grid-auto-columns',
        'gridAutoRows' => 'grid-auto-rows',

        // Transform & Transition
        'transform' => 'transform',
        'transformOrigin' => 'transform-origin',
        'transition' => 'transition',
        'transitionDuration' => 'transition-duration',
        'transitionTimingFunction' => 'transition-timing-function',
        'transitionDelay' => 'transition-delay',
        'transitionProperty' => 'transition-property',

        // Shadows & Effects
        'boxShadow' => 'box-shadow',
        'textShadow' => 'text-shadow',
        'filter' => 'filter',
        'backdropFilter' => 'backdrop-filter',

        // Other
        'cursor' => 'cursor',
        'listStyle' => 'list-style',
        'listStyleType' => 'list-style-type',
        'listStylePosition' => 'list-style-position',
        'objectFit' => 'object-fit',
        'objectPosition' => 'object-position',
        'inset' => 'inset',
        'aspectRatio' => 'aspect-ratio',
        'borderCollapse' => 'border-collapse',
        'tableLayout' => 'table-layout',
    ];

    /**
     * Properties that require unit conversion.
     * If value is numeric without unit, append 'px'.
     */
    private const UNITLESS_PROPERTIES = [
        'line-height',
        'font-weight',
        'opacity',
        'z-index',
        'order',
        'flex-grow',
        'flex-shrink',
    ];

    /**
     * Properties that can use 'clamp' or other CSS functions.
     */
    private const FUNCTION_ALLOWED_PROPERTIES = [
        'font-size',
        'line-height',
        'width',
        'height',
        'min-width',
        'max-width',
        'min-height',
        'max-height',
        'margin',
        'margin-top',
        'margin-right',
        'margin-bottom',
        'margin-left',
        'padding',
        'padding-top',
        'padding-right',
        'padding-bottom',
        'padding-left',
        'gap',
        'row-gap',
        'column-gap',
    ];

    /**
     * Map VBB element inline styles to CSS properties.
     *
     * @param array $styles Inline styles from element (camelCase keys)
     * @return array CSS properties (kebab-case keys, normalized values)
     */
    public function mapStyles(array $styles): array
    {
        $cssProperties = [];

        foreach ($styles as $property => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Convert property name to CSS format
            $cssProperty = $this->toCssPropertyName($property);
            if (!$cssProperty) {
                continue;
            }

            // Normalize value
            $cssValue = $this->normalizeValue($cssProperty, $value);
            if ($cssValue !== null) {
                $cssProperties[$cssProperty] = $cssValue;
            }
        }

        return $cssProperties;
    }

    /**
     * Convert camelCase property name to kebab-case CSS property.
     *
     * @param string $property camelCase property name
     * @return string|null CSS property name or null if not recognized
     */
    public function toCssPropertyName(string $property): ?string
    {
        // Check if property is in our map
        if (isset(self::PROPERTY_MAP[$property])) {
            return self::PROPERTY_MAP[$property];
        }

        // Fallback: convert camelCase to kebab-case
        return strtolower(preg_replace('/([A-Z])/', '-$1', $property));
    }

    /**
     * Convert CSS kebab-case property to camelCase.
     *
     * @param string $cssProperty CSS property name (kebab-case)
     * @return string camelCase property name
     */
    public function toCamelCase(string $cssProperty): string
    {
        // Look up in reverse map
        $reverseMap = array_flip(self::PROPERTY_MAP);
        if (isset($reverseMap[$cssProperty])) {
            return $reverseMap[$cssProperty];
        }

        // Fallback: convert kebab-case to camelCase
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $cssProperty))));
    }

    /**
     * Normalize CSS value (add units, validate, etc.).
     *
     * @param string $property CSS property name
     * @param mixed $value Raw value
     * @return string|null Normalized CSS value
     */
    public function normalizeValue(string $property, mixed $value): ?string
    {
        // Convert to string
        $value = (string) $value;

        // Empty values
        if (trim($value) === '') {
            return null;
        }

        // Check if value contains CSS function (clamp, calc, var, etc.)
        if ($this->containsCssFunction($value)) {
            return $value;
        }

        // Check if property requires unit
        if ($this->requiresUnit($property, $value)) {
            $value = $this->addDefaultUnit($value);
        }

        // Validate color values
        if ($this->isColorProperty($property)) {
            $value = $this->normalizeColor($value);
        }

        return $value;
    }

    /**
     * Check if value contains CSS function.
     */
    private function containsCssFunction(string $value): bool
    {
        $functions = ['clamp(', 'calc(', 'var(', 'url(', 'linear-gradient(', 'radial-gradient(', 'rgb(', 'rgba(', 'hsl(', 'hsla(', 'minmax(', 'repeat('];

        foreach ($functions as $func) {
            if (str_contains($value, $func)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if property requires unit for numeric values.
     */
    private function requiresUnit(string $property, string $value): bool
    {
        // If already has unit, no need to add
        if (preg_match('/[a-zA-Z%]$/', $value)) {
            return false;
        }

        // If not numeric, no unit needed
        if (!is_numeric($value)) {
            return false;
        }

        // Zero doesn't need unit
        if ((float) $value === 0.0) {
            return false;
        }

        // Check if property is unitless
        return !in_array($property, self::UNITLESS_PROPERTIES, true);
    }

    /**
     * Add default px unit to numeric value.
     */
    private function addDefaultUnit(string $value): string
    {
        if (is_numeric($value) && (float) $value !== 0.0) {
            return $value . 'px';
        }
        return $value;
    }

    /**
     * Check if property is a color property.
     */
    private function isColorProperty(string $property): bool
    {
        return in_array($property, ['color', 'background-color', 'border-color'], true)
            || str_contains($property, 'color');
    }

    /**
     * Normalize color value.
     */
    private function normalizeColor(string $value): string
    {
        $value = trim($value);

        // Valid color formats: #hex, rgb(), rgba(), hsl(), hsla(), named colors
        // Basic validation - return as-is if seems valid
        if (
            str_starts_with($value, '#') ||
            str_starts_with($value, 'rgb') ||
            str_starts_with($value, 'hsl') ||
            str_starts_with($value, 'var(')
        ) {
            return $value;
        }

        // Named colors are passed through
        return $value;
    }

    /**
     * Map entire document tree styles to CSS.
     *
     * @param array $document Document with root element and children
     * @return array Map of element selectors to CSS properties
     */
    public function mapDocumentStyles(array $document): array
    {
        $result = [];

        if (isset($document['root'])) {
            $this->collectElementStyles($document['root'], $result);
        }

        return $result;
    }

    /**
     * Recursively collect styles from element tree.
     */
    private function collectElementStyles(array $element, array &$result, string $parentSelector = ''): void
    {
        $elementId = $element['id'] ?? '';
        $classes = $element['classes'] ?? [];
        $styles = $element['styles'] ?? [];

        // Skip if no ID or no inline styles
        if (empty($elementId) || empty($styles)) {
            // But still process children
            if (!empty($element['children'])) {
                foreach ($element['children'] as $child) {
                    $this->collectElementStyles($child, $result, $parentSelector);
                }
            }
            return;
        }

        // Generate selector based on ID or classes
        $selector = $this->generateSelector($elementId, $classes);

        // Map styles
        $cssProperties = $this->mapStyles($styles);
        if (!empty($cssProperties)) {
            $result[$selector] = $cssProperties;
        }

        // Process children
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                $this->collectElementStyles($child, $result, $selector);
            }
        }
    }

    /**
     * Generate CSS selector for element.
     *
     * @param string $id Element ID
     * @param array $classes Element classes
     * @return string CSS selector
     */
    public function generateSelector(string $id, array $classes = []): string
    {
        // Prefer ID-based selector for uniqueness
        if (!empty($id)) {
            // Use data attribute selector for element IDs
            return "[data-uve-id=\"{$id}\"]";
        }

        // Fallback to class-based selector
        if (!empty($classes)) {
            return '.' . implode('.', array_map('trim', $classes));
        }

        return '';
    }

    /**
     * Get all supported CSS properties.
     *
     * @return array List of supported CSS property names
     */
    public function getSupportedProperties(): array
    {
        return array_values(self::PROPERTY_MAP);
    }

    /**
     * Get all supported camelCase property names.
     *
     * @return array List of supported camelCase property names
     */
    public function getSupportedCamelCaseProperties(): array
    {
        return array_keys(self::PROPERTY_MAP);
    }

    /**
     * Parse CSS string into property-value pairs.
     *
     * @param string $cssString CSS declarations string
     * @return array Key-value pairs of CSS properties
     */
    public function parseCssString(string $cssString): array
    {
        $properties = [];

        // Split by semicolon
        $declarations = array_filter(array_map('trim', explode(';', $cssString)));

        foreach ($declarations as $declaration) {
            // Split property: value
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);
                $properties[$property] = $value;
            }
        }

        return $properties;
    }

    /**
     * Convert CSS properties array to inline style string.
     *
     * @param array $properties CSS properties (kebab-case)
     * @return string Inline style string
     */
    public function toInlineStyle(array $properties): string
    {
        $parts = [];

        foreach ($properties as $property => $value) {
            $parts[] = "{$property}: {$value}";
        }

        return implode('; ', $parts);
    }
}
