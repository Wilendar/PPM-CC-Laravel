<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

use App\Services\VisualEditor\CssPropertyMapper;

/**
 * Formats control values to CSS properties.
 *
 * Handles conversion of Property Panel control values to valid CSS strings.
 * Extracted from PropertyPanelService to keep files under 300 lines.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
class CssValueFormatter
{
    private CssPropertyMapper $cssMapper;

    public function __construct(CssPropertyMapper $cssMapper)
    {
        $this->cssMapper = $cssMapper;
    }

    /**
     * Format control values to CSS.
     *
     * @param string $controlType Control type
     * @param mixed $value Control value
     * @param PropertyControlRegistry $registry Registry for control definitions
     * @return array<string, string> CSS property-value pairs
     */
    public function formatToCss(string $controlType, mixed $value, PropertyControlRegistry $registry): array
    {
        // Handle simple CSS properties passed as strings (from color-picker etc.)
        if (is_string($value) && $this->isSimpleCssProperty($controlType)) {
            return $this->formatSimpleProperty($controlType, $value);
        }

        $definition = $registry->get($controlType);
        if (!$definition && !$this->isSimpleCssProperty($controlType)) {
            return [];
        }

        return match ($controlType) {
            'box-model' => $this->formatBoxModel($value),
            'typography' => $this->formatTypography($value),
            'gradient-editor' => $this->formatGradient($value),
            'layout-flex' => $this->formatFlex($value),
            'layout-grid' => $this->formatGrid($value),
            'effects' => $this->formatEffects($value),
            'transform' => $this->formatTransform($value),
            'transition' => $this->formatTransition($value),
            'border' => $this->formatBorder($value),
            'background' => $this->formatBackground($value),
            'position' => $this->formatPosition($value),
            'size' => $this->formatSize($value),
            'image-settings' => $this->formatImageSettings($value),
            'list-settings' => $this->formatListSettings($value), // FIX #16
            default => $this->formatGeneric($value),
        };
    }

    /**
     * Check if controlType is a simple CSS property name.
     */
    private function isSimpleCssProperty(string $controlType): bool
    {
        $simpleProps = [
            'color', 'background-color', 'backgroundColor',
            'opacity', 'visibility', 'display', 'overflow',
            'cursor', 'pointer-events', 'user-select',
            'text-transform', 'text-decoration', 'text-align',
            'font-size', 'font-weight', 'font-family', 'line-height',
            'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height',
            'z-index', 'zIndex',
        ];
        return in_array($controlType, $simpleProps, true);
    }

    /**
     * Format a simple CSS property.
     */
    private function formatSimpleProperty(string $controlType, string $value): array
    {
        if ($value === '' || $value === null) {
            return [];
        }

        // Convert camelCase to kebab-case if needed
        $cssProp = $this->cssMapper->toCssPropertyName($controlType) ?: $controlType;
        return [$cssProp => $value];
    }

    /**
     * Format box-model value to CSS.
     *
     * @param array<string, mixed> $value Box model values
     * @return array<string, string> CSS properties
     */
    public function formatBoxModel(array $value): array
    {
        $css = [];

        foreach (['margin', 'padding'] as $property) {
            if (!isset($value[$property])) {
                continue;
            }

            $sides = $value[$property];
            if ($sides['linked'] ?? false) {
                $val = $sides['top'] ?? '';
                if ($val !== '') {
                    $css[$property] = $val;
                }
            } else {
                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    $val = $sides[$side] ?? '';
                    if ($val !== '') {
                        $css["{$property}-{$side}"] = $val;
                    }
                }
            }
        }

        // Border radius
        if (isset($value['borderRadius'])) {
            $radius = $value['borderRadius'];
            if ($radius['linked'] ?? false) {
                $val = $radius['top'] ?? '';
                if ($val !== '') {
                    $css['border-radius'] = $val;
                }
            } else {
                $corners = [
                    'top' => 'border-top-left-radius',
                    'right' => 'border-top-right-radius',
                    'bottom' => 'border-bottom-right-radius',
                    'left' => 'border-bottom-left-radius',
                ];
                foreach ($corners as $key => $cssProp) {
                    $val = $radius[$key] ?? '';
                    if ($val !== '') {
                        $css[$cssProp] = $val;
                    }
                }
            }
        }

        return $css;
    }

    /**
     * Format typography value to CSS.
     *
     * @param array<string, mixed> $value Typography values
     * @return array<string, string> CSS properties
     */
    public function formatTypography(array $value): array
    {
        $css = [];
        $mapping = [
            'fontSize' => 'font-size',
            'fontWeight' => 'font-weight',
            'fontFamily' => 'font-family',
            'fontStyle' => 'font-style',
            'lineHeight' => 'line-height',
            'letterSpacing' => 'letter-spacing',
            'textTransform' => 'text-transform',
            'textDecoration' => 'text-decoration',
            'textAlign' => 'text-align',
        ];

        // Properties that CAN be empty/inherit/normal (skip these values)
        $skipValues = ['inherit', 'normal', ''];

        // Properties where 'none' or default values MUST be included to reset previous styles
        // These are needed so that merge() will overwrite non-default values
        $alwaysIncludeProps = ['text-transform', 'text-decoration', 'text-align'];

        foreach ($mapping as $key => $cssProp) {
            $val = $value[$key] ?? '';

            // For text-transform, text-decoration, text-align - always include if not empty
            // This ensures 'none' or 'left' values overwrite previous non-default values
            if (in_array($cssProp, $alwaysIncludeProps, true)) {
                if ($val !== '') {
                    $css[$cssProp] = $val;
                }
            } else {
                // For other properties, skip default/empty values
                if (!in_array($val, $skipValues, true)) {
                    $css[$cssProp] = $val;
                }
            }
        }

        return $css;
    }

    /**
     * Format gradient value to CSS.
     *
     * @param array<string, mixed> $value Gradient configuration
     * @return array<string, string> CSS properties
     */
    public function formatGradient(array $value): array
    {
        $type = $value['type'] ?? 'linear';
        $stops = $value['stops'] ?? [];

        if (empty($stops)) {
            return [];
        }

        $stopStrings = array_map(
            fn($s) => "{$s['color']} {$s['position']}%",
            $stops
        );

        if ($type === 'linear') {
            $angle = $value['angle'] ?? 180;
            $gradient = "linear-gradient({$angle}deg, " . implode(', ', $stopStrings) . ')';
        } else {
            $gradient = "radial-gradient(circle, " . implode(', ', $stopStrings) . ')';
        }

        return ['background' => $gradient];
    }

    /**
     * Format flex value to CSS.
     *
     * @param array<string, mixed> $value Flex configuration
     * @return array<string, string> CSS properties
     */
    public function formatFlex(array $value): array
    {
        $css = [];
        $mapping = [
            'display' => 'display',
            'flexDirection' => 'flex-direction',
            'flexWrap' => 'flex-wrap',
            'justifyContent' => 'justify-content',
            'alignItems' => 'align-items',
            'alignContent' => 'align-content',
            'gap' => 'gap',
            'rowGap' => 'row-gap',
            'columnGap' => 'column-gap',
        ];

        foreach ($mapping as $key => $cssProp) {
            $val = $value[$key] ?? '';
            if ($val !== '') {
                $css[$cssProp] = $val;
            }
        }

        return $css;
    }

    /**
     * Format grid value to CSS.
     *
     * @param array<string, mixed> $value Grid configuration
     * @return array<string, string> CSS properties
     */
    public function formatGrid(array $value): array
    {
        $css = [];
        $mapping = [
            'display' => 'display',
            'gridTemplateColumns' => 'grid-template-columns',
            'gridTemplateRows' => 'grid-template-rows',
            'gap' => 'gap',
            'rowGap' => 'row-gap',
            'columnGap' => 'column-gap',
            'gridAutoFlow' => 'grid-auto-flow',
        ];

        foreach ($mapping as $key => $cssProp) {
            $val = $value[$key] ?? '';
            if ($val !== '') {
                $css[$cssProp] = $val;
            }
        }

        return $css;
    }

    /**
     * Format effects value to CSS.
     *
     * @param array<string, mixed> $value Effects configuration
     * @return array<string, string> CSS properties
     */
    public function formatEffects(array $value): array
    {
        $css = [];

        // Box shadow
        if (isset($value['boxShadow']) && ($value['boxShadow']['enabled'] ?? false)) {
            $s = $value['boxShadow'];
            $inset = ($s['inset'] ?? false) ? 'inset ' : '';
            $css['box-shadow'] = "{$inset}{$s['x']} {$s['y']} {$s['blur']} {$s['spread']} {$s['color']}";
        }

        // Text shadow
        if (isset($value['textShadow']) && ($value['textShadow']['enabled'] ?? false)) {
            $s = $value['textShadow'];
            $css['text-shadow'] = "{$s['x']} {$s['y']} {$s['blur']} {$s['color']}";
        }

        // Opacity
        if (isset($value['opacity']) && $value['opacity'] !== '1') {
            $css['opacity'] = $value['opacity'];
        }

        return $css;
    }

    /**
     * Format transform value to CSS.
     *
     * @param array<string, mixed> $value Transform configuration
     * @return array<string, string> CSS properties
     */
    public function formatTransform(array $value): array
    {
        $transforms = [];

        if (($value['rotate'] ?? '0') !== '0') {
            $transforms[] = "rotate({$value['rotate']}deg)";
        }
        if (($value['scaleX'] ?? '1') !== '1' || ($value['scaleY'] ?? '1') !== '1') {
            $transforms[] = "scale({$value['scaleX']}, {$value['scaleY']})";
        }
        if (($value['translateX'] ?? '0') !== '0' || ($value['translateY'] ?? '0') !== '0') {
            $transforms[] = "translate({$value['translateX']}, {$value['translateY']})";
        }
        if (($value['skewX'] ?? '0') !== '0' || ($value['skewY'] ?? '0') !== '0') {
            $transforms[] = "skew({$value['skewX']}deg, {$value['skewY']}deg)";
        }

        $css = [];
        if (!empty($transforms)) {
            $css['transform'] = implode(' ', $transforms);
        }

        if (isset($value['origin']) && $value['origin'] !== 'center') {
            $css['transform-origin'] = $value['origin'];
        }

        return $css;
    }

    /**
     * Format transition value to CSS.
     *
     * @param array<string, mixed> $value Transition configuration
     * @return array<string, string> CSS properties
     */
    public function formatTransition(array $value): array
    {
        if (!($value['enabled'] ?? false)) {
            return [];
        }

        $prop = $value['property'] ?? 'all';
        $duration = $value['duration'] ?? '0.3s';
        $timing = $value['timing'] ?? 'ease';
        $delay = $value['delay'] ?? '0s';

        return ['transition' => "{$prop} {$duration} {$timing} {$delay}"];
    }

    /**
     * Format border value to CSS.
     *
     * @param array<string, mixed> $value Border configuration
     * @return array<string, string> CSS properties
     */
    public function formatBorder(array $value): array
    {
        $css = [];

        $width = $value['width'] ?? '';
        $style = $value['style'] ?? 'solid';
        $color = $value['color'] ?? '';

        if ($width !== '' && $color !== '') {
            $css['border'] = "{$width} {$style} {$color}";
        } elseif ($width !== '') {
            $css['border-width'] = $width;
        }

        if ($style !== 'solid' && $style !== '') {
            $css['border-style'] = $style;
        }

        $radius = $value['radius'] ?? '';
        if ($radius !== '') {
            $css['border-radius'] = $radius;
        }

        return $css;
    }

    /**
     * Format background value to CSS.
     *
     * @param array<string, mixed> $value Background configuration
     * @return array<string, string> CSS properties
     */
    public function formatBackground(array $value): array
    {
        $css = [];

        // FIX #14e: Handle Alpine emitChange() format (camelCase CSS properties)
        // Alpine sends: { backgroundColor, backgroundImage, backgroundSize, ... }
        // Old format sends: { type: 'color|image|gradient', color: '...', image: '...' }

        // Check if this is Alpine format (has backgroundImage or backgroundColor keys)
        $isAlpineFormat = isset($value['backgroundImage']) || isset($value['backgroundColor']);

        if ($isAlpineFormat) {
            // Alpine format - direct CSS properties
            if (!empty($value['backgroundColor'])) {
                $css['background-color'] = $value['backgroundColor'];
            }

            if (!empty($value['backgroundImage'])) {
                // backgroundImage can be gradient string or url('...')
                $css['background-image'] = $value['backgroundImage'];
            }

            if (!empty($value['backgroundSize'])) {
                $css['background-size'] = $value['backgroundSize'];
            }

            if (!empty($value['backgroundPosition'])) {
                $css['background-position'] = $value['backgroundPosition'];
            }

            if (!empty($value['backgroundRepeat'])) {
                $css['background-repeat'] = $value['backgroundRepeat'];
            }

            if (!empty($value['backgroundAttachment']) && $value['backgroundAttachment'] !== 'scroll') {
                $css['background-attachment'] = $value['backgroundAttachment'];
            }

            return $css;
        }

        // Legacy format with 'type' key
        $type = $value['type'] ?? 'color';

        switch ($type) {
            case 'color':
                if (($value['color'] ?? '') !== '') {
                    $css['background-color'] = $value['color'];
                }
                break;

            case 'image':
                if (($value['image'] ?? '') !== '') {
                    $css['background-image'] = "url('{$value['image']}')";
                    $css['background-position'] = $value['position'] ?? 'center';
                    $css['background-size'] = $value['size'] ?? 'cover';
                    $css['background-repeat'] = $value['repeat'] ?? 'no-repeat';
                    if (($value['attachment'] ?? 'scroll') !== 'scroll') {
                        $css['background-attachment'] = $value['attachment'];
                    }
                }
                break;

            case 'gradient':
                // Delegate to gradient formatter
                return $this->formatGradient($value);
        }

        return $css;
    }

    /**
     * Format position value to CSS.
     *
     * @param array<string, mixed> $value Position configuration
     * @return array<string, string> CSS properties
     */
    public function formatPosition(array $value): array
    {
        $css = [];

        $position = $value['position'] ?? 'relative';
        if ($position !== 'static') {
            $css['position'] = $position;
        }

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $val = $value[$side] ?? '';
            if ($val !== '') {
                $css[$side] = $val;
            }
        }

        $zIndex = $value['zIndex'] ?? '';
        if ($zIndex !== '') {
            $css['z-index'] = $zIndex;
        }

        return $css;
    }

    /**
     * Format size value to CSS.
     *
     * @param array<string, mixed> $value Size configuration
     * @return array<string, string> CSS properties
     */
    public function formatSize(array $value): array
    {
        $css = [];
        $mapping = [
            'width' => 'width',
            'height' => 'height',
            'minWidth' => 'min-width',
            'maxWidth' => 'max-width',
            'minHeight' => 'min-height',
            'maxHeight' => 'max-height',
        ];

        foreach ($mapping as $key => $cssProp) {
            $val = $value[$key] ?? '';
            if ($val !== '') {
                $css[$cssProp] = $val;
            }
        }

        return $css;
    }

    /**
     * Format image-settings value to CSS.
     *
     * Handles image-specific properties: size presets, alignment, object-fit,
     * border-radius, and shadow. Note: lightbox/lazyLoad are HTML attributes
     * handled separately via data attributes.
     *
     * @param array<string, mixed> $value Image settings configuration
     * @return array<string, string> CSS properties
     */
    public function formatImageSettings(array $value): array
    {
        $css = [];

        // Size handling - preset or custom
        $size = $value['size'] ?? 'full';
        $sizePresets = [
            'full' => '100%',
            'large' => '75%',
            'medium' => '50%',
            'small' => '25%',
        ];

        if ($size === 'custom') {
            $customWidth = $value['customWidth'] ?? '';
            $customHeight = $value['customHeight'] ?? '';
            if ($customWidth !== '') {
                $css['width'] = $customWidth;
            }
            if ($customHeight !== '' && $customHeight !== 'auto') {
                $css['height'] = $customHeight;
            }
        } elseif (isset($sizePresets[$size])) {
            $css['width'] = $sizePresets[$size];
            $css['height'] = 'auto';
        }

        // Alignment - uses display block + margins for centering
        // CRITICAL: ALWAYS set margin values to reset previous alignment styles
        $alignment = $value['alignment'] ?? 'left';
        $css['display'] = 'block';

        if ($alignment === 'center') {
            $css['margin-left'] = 'auto';
            $css['margin-right'] = 'auto';
        } elseif ($alignment === 'right') {
            $css['margin-left'] = 'auto';
            $css['margin-right'] = '0';
        } else {
            // alignment === 'left' - MUST reset margins to prevent accumulation
            $css['margin-left'] = '0';
            $css['margin-right'] = 'auto';
        }

        // Object-fit for image scaling behavior
        $objectFit = $value['objectFit'] ?? '';
        if ($objectFit !== '' && $objectFit !== 'fill') {
            $css['object-fit'] = $objectFit;
        }

        // Border radius
        $borderRadius = $value['borderRadius'] ?? '';
        if ($borderRadius !== '' && $borderRadius !== '0' && $borderRadius !== '0px') {
            $css['border-radius'] = $borderRadius;
        }

        // Shadow effect
        $shadow = $value['shadow'] ?? false;
        if ($shadow === true || $shadow === 'true' || $shadow === '1') {
            $css['box-shadow'] = '0 4px 12px rgba(0, 0, 0, 0.15)';
        }

        return $css;
    }

    /**
     * Format list-settings value to CSS.
     *
     * FIX #16: Handles list-specific properties: list-style-type (numbering/bullets),
     * gap for item spacing, and padding for indentation.
     *
     * @param array<string, mixed> $value List settings configuration
     * @return array<string, string> CSS properties
     */
    public function formatListSettings(array $value): array
    {
        $css = [];

        // Determine list-style-type based on listStyle, numberingStyle, or bulletStyle
        $listStyle = $value['listStyle'] ?? 'checkmarks';

        if ($listStyle === 'numbers') {
            // FIX #16: Numbering style variants
            $numberingStyle = $value['numberingStyle'] ?? 'decimal';
            $css['list-style-type'] = $numberingStyle;
        } elseif ($listStyle === 'bullets') {
            // FIX #16: Bullet style variants
            $bulletStyle = $value['bulletStyle'] ?? 'disc';
            $css['list-style-type'] = $bulletStyle;
        } elseif ($listStyle === 'none') {
            $css['list-style-type'] = 'none';
        }
        // Note: checkmarks, icons, arrows use custom markers (handled via CSS pseudo-elements)

        // Item gap - applies to list container
        $itemGap = $value['itemGap'] ?? '';
        if ($itemGap !== '' && $itemGap !== '0') {
            $css['gap'] = $itemGap;
        }

        // Indentation - padding-left on the list
        $indentation = $value['indentation'] ?? '';
        if ($indentation !== '' && $indentation !== '0') {
            $css['padding-left'] = $indentation;
        }

        // Columns layout (grid)
        $layout = $value['layout'] ?? 'vertical';
        $columns = $value['columns'] ?? 1;

        if ($layout === 'grid' || ($layout === 'horizontal' && $columns > 1)) {
            $css['display'] = 'grid';
            $css['grid-template-columns'] = "repeat({$columns}, 1fr)";
        } elseif ($layout === 'horizontal') {
            $css['display'] = 'flex';
            $css['flex-wrap'] = 'wrap';
        }

        return $css;
    }

    /**
     * Generic formatter for unknown control types.
     *
     * @param mixed $value Control value
     * @return array<string, string> CSS properties
     */
    public function formatGeneric(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $css = [];
        foreach ($value as $prop => $val) {
            if ($val !== '' && $val !== null) {
                $cssProp = $this->cssMapper->toCssPropertyName($prop);
                if ($cssProp) {
                    $css[$cssProp] = (string) $val;
                }
            }
        }

        return $css;
    }

    /**
     * Parse CSS string to control value.
     *
     * @param string $controlType Control type
     * @param array<string, string> $cssProperties CSS property-value pairs
     * @return mixed Parsed control value
     */
    public function parseCssToValue(string $controlType, array $cssProperties): mixed
    {
        return match ($controlType) {
            'box-model' => $this->parseBoxModel($cssProperties),
            'typography' => $this->parseTypography($cssProperties),
            'layout-flex' => $this->parseFlex($cssProperties),
            'layout-grid' => $this->parseGrid($cssProperties),
            default => $this->parseGeneric($cssProperties),
        };
    }

    /**
     * Parse CSS to box-model value.
     */
    private function parseBoxModel(array $css): array
    {
        $value = [
            'margin' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => false],
            'padding' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => false],
            'borderRadius' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => false],
        ];

        foreach (['margin', 'padding'] as $prop) {
            if (isset($css[$prop])) {
                $value[$prop] = ['top' => $css[$prop], 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true];
            } else {
                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    $value[$prop][$side] = $css["{$prop}-{$side}"] ?? '';
                }
            }
        }

        if (isset($css['border-radius'])) {
            $value['borderRadius'] = ['top' => $css['border-radius'], 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true];
        }

        return $value;
    }

    /**
     * Parse CSS to typography value.
     */
    private function parseTypography(array $css): array
    {
        return [
            'fontSize' => $css['font-size'] ?? '',
            'fontWeight' => $css['font-weight'] ?? '400',
            'fontFamily' => $css['font-family'] ?? 'inherit',
            'fontStyle' => $css['font-style'] ?? 'normal',
            'lineHeight' => $css['line-height'] ?? '',
            'letterSpacing' => $css['letter-spacing'] ?? '',
            'textTransform' => $css['text-transform'] ?? 'none',
            'textDecoration' => $css['text-decoration'] ?? 'none',
            'textAlign' => $css['text-align'] ?? 'left',
        ];
    }

    /**
     * Parse CSS to flex value.
     */
    private function parseFlex(array $css): array
    {
        return [
            'display' => $css['display'] ?? 'flex',
            'flexDirection' => $css['flex-direction'] ?? 'row',
            'flexWrap' => $css['flex-wrap'] ?? 'nowrap',
            'justifyContent' => $css['justify-content'] ?? 'flex-start',
            'alignItems' => $css['align-items'] ?? 'stretch',
            'gap' => $css['gap'] ?? '',
        ];
    }

    /**
     * Parse CSS to grid value.
     */
    private function parseGrid(array $css): array
    {
        return [
            'display' => $css['display'] ?? 'grid',
            'gridTemplateColumns' => $css['grid-template-columns'] ?? '',
            'gridTemplateRows' => $css['grid-template-rows'] ?? '',
            'gap' => $css['gap'] ?? '1rem',
        ];
    }

    /**
     * Generic parser for CSS properties.
     */
    private function parseGeneric(array $css): array
    {
        $value = [];
        foreach ($css as $prop => $val) {
            $camelProp = $this->cssMapper->toCamelCase($prop);
            $value[$camelProp] = $val;
        }
        return $value;
    }
}
