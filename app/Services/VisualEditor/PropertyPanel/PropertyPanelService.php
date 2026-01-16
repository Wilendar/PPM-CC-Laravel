<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\CssPropertyMapper;
use App\Services\VisualEditor\PrestaShopCssDefinitions;

/**
 * Main service for Property Panel operations.
 *
 * Provides:
 * - Panel configuration building based on CSS classes
 * - Control grouping into tabs (Style, Layout, Advanced, Classes)
 * - Default value application
 * - Value validation
 *
 * CSS formatting is delegated to CssValueFormatter.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
class PropertyPanelService
{
    /**
     * Panel tabs configuration.
     */
    public const TABS = [
        'style' => [
            'label' => 'Style',
            'icon' => 'palette',
            'groups' => ['Style'],
            'priority' => 1,
        ],
        'layout' => [
            'label' => 'Layout',
            'icon' => 'layout',
            'groups' => ['Layout'],
            'priority' => 2,
        ],
        'advanced' => [
            'label' => 'Zaawansowane',
            'icon' => 'sliders',
            'groups' => ['Advanced', 'Interactive', 'States'],
            'priority' => 3,
        ],
        'classes' => [
            'label' => 'Klasy CSS',
            'icon' => 'code',
            'groups' => [],
            'priority' => 4,
        ],
    ];

    private PropertyControlRegistry $registry;
    private CssClassControlMapper $classMapper;
    private CssPropertyMapper $cssMapper;
    private CssValueFormatter $formatter;
    private BlockRegistry $blockRegistry;

    public function __construct(
        PropertyControlRegistry $registry,
        CssClassControlMapper $classMapper,
        CssPropertyMapper $cssMapper,
        CssValueFormatter $formatter,
        BlockRegistry $blockRegistry
    ) {
        $this->registry = $registry;
        $this->classMapper = $classMapper;
        $this->cssMapper = $cssMapper;
        $this->formatter = $formatter;
        $this->blockRegistry = $blockRegistry;
    }

    /**
     * Build complete panel configuration for an element.
     *
     * @param array<string> $cssClasses Element CSS classes
     * @param array<string, mixed> $currentStyles Current inline styles
     * @param string $elementType Element type (div, span, etc.)
     * @param string|null $blockType Block type (image, slider, etc.) for block-specific controls
     * @return array<string, mixed> Panel configuration
     */
    public function buildPanelConfig(
        array $cssClasses,
        array $currentStyles = [],
        string $elementType = 'div',
        ?string $blockType = null
    ): array {
        // DEBUG: Log input parameters
        \Log::info('PropertyPanelService::buildPanelConfig CALLED', [
            'elementType' => $elementType,
            'blockType' => $blockType,
            'cssClassesCount' => count($cssClasses),
            'cssClasses' => array_slice($cssClasses, 0, 5),
        ]);

        // Get class-based configuration
        $classConfig = $this->classMapper->buildPanelConfig($cssClasses);

        // Get all relevant controls (including block-specific)
        $controls = $this->getControlsForElement($cssClasses, $elementType, $blockType);

        // DEBUG: Log resolved controls
        \Log::info('PropertyPanelService::buildPanelConfig CONTROLS', [
            'controlTypes' => array_keys($controls),
            'controlCount' => count($controls),
        ]);

        // Apply defaults from CSS classes
        $defaults = $this->getMergedDefaults($cssClasses);

        // Merge current styles with defaults (flat array)
        $flatValues = $this->mergeStylesWithDefaults($currentStyles, $defaults);

        // CRITICAL FIX: Group values by control type for panel controls
        // Each control (typography, color-picker, etc.) expects its values grouped
        $values = $this->groupValuesByControl($flatValues, $controls);

        // Group controls into tabs
        $tabs = $this->resolveControlGroups($controls);

        // Build classes tab content
        $classesTab = $this->buildClassesTabContent($cssClasses);

        return [
            'tabs' => $tabs,
            'classesTab' => $classesTab,
            'controls' => $controls,
            'values' => $values,
            'defaults' => $defaults,
            'cssClasses' => $cssClasses,
            'readonlyClasses' => $classConfig['readonlyClasses'] ?? [],
            'elementType' => $elementType,
            'blockType' => $blockType,
            'responsive' => $this->getResponsiveControls($controls),
            'hoverSupported' => $this->getHoverControls($controls),
        ];
    }

    /**
     * Get controls applicable to an element.
     *
     * @param array<string> $cssClasses CSS classes
     * @param string $elementType Element type
     * @param string|null $blockType Block type for block-specific controls
     * @return array<string, array<string, mixed>> Controls by type
     */
    public function getControlsForElement(array $cssClasses, string $elementType, ?string $blockType = null): array
    {
        $controlTypes = [];

        // Get controls from CSS class mappings
        foreach ($cssClasses as $className) {
            $classControls = $this->classMapper->getControlsForClass($className);
            $controlTypes = array_merge($controlTypes, $classControls);
        }

        // Add base controls available for all elements
        $baseControls = $this->getBaseControlsForElement($elementType);
        $controlTypes = array_merge($controlTypes, $baseControls);

        // Add block-specific controls if block type provided
        // ETAP_07h PP.3: Pass CSS classes for selector matching
        if ($blockType !== null) {
            $blockControls = $this->getControlsForBlockType($blockType, 'root', $cssClasses);
            $controlTypes = array_merge($controlTypes, $blockControls);
        }

        // Remove duplicates
        $controlTypes = array_unique($controlTypes);

        // Filter out inappropriate controls for element type
        $controlTypes = $this->filterControlsForElementType($controlTypes, $elementType);

        // Build control definitions
        $controls = [];
        foreach ($controlTypes as $type) {
            $definition = $this->registry->get($type);
            if ($definition) {
                $controls[$type] = $definition;
            }
        }

        return $controls;
    }

    /**
     * Get controls specific to a block type.
     *
     * Maps block types to their specific controls.
     *
     * @param string $blockType Block type identifier
     * @param string $elementSelector CSS selector or 'root' for main element
     * @param array<string> $cssClasses Element's CSS classes for selector matching
     * @return array<string> Control types for this block
     */
    public function getControlsForBlockType(string $blockType, string $elementSelector = 'root', array $cssClasses = []): array
    {
        // ETAP_07h PP.3: Check if blockType is a PrestaShop section type
        // Section types (cover, parallax, merits) are NOT registered as blocks
        // They belong to PrestashopSectionBlock which provides dynamic controls
        $sectionType = $this->detectPrestashopSectionType($blockType);
        if ($sectionType !== null) {
            $prestashopBlock = $this->blockRegistry->get('prestashop-section');
            if ($prestashopBlock && method_exists($prestashopBlock, 'getPropertyPanelControlsForType')) {
                $dynamicControls = $prestashopBlock->getPropertyPanelControlsForType($sectionType);

                // ETAP_07h PP.3: Match element's CSS classes against dynamic control selectors
                $matchedControls = $this->matchCssClassesToControls($cssClasses, $dynamicControls);
                if (!empty($matchedControls)) {
                    \Log::debug('PropertyPanelService: Matched CSS classes to dynamic controls', [
                        'sectionType' => $sectionType,
                        'cssClasses' => $cssClasses,
                        'matchedControls' => $matchedControls,
                    ]);
                    return $matchedControls;
                }

                // Fallback: return root controls if no CSS class match
                $controls = $dynamicControls[$elementSelector] ?? $dynamicControls['root'] ?? [];
                if (!empty($controls)) {
                    \Log::debug('PropertyPanelService: Using PrestashopSectionBlock root controls', [
                        'sectionType' => $sectionType,
                        'selector' => $elementSelector,
                        'controls' => $controls,
                    ]);
                    return $controls;
                }
            }
        }

        // ETAP_07h PP.1: First check if block has custom Property Panel configuration
        $block = $this->blockRegistry->get($blockType);
        if ($block && $block->hasPropertyPanelConfig()) {
            $blockControls = $block->getPropertyPanelConfig($elementSelector);
            if (!empty($blockControls)) {
                \Log::debug('PropertyPanelService: Using block-defined controls', [
                    'blockType' => $blockType,
                    'selector' => $elementSelector,
                    'controls' => $blockControls,
                ]);
                return $blockControls;
            }
        }

        // Normalize block type - add pd- prefix if missing for PrestaShop blocks
        $normalizedType = $this->normalizeBlockType($blockType);

        // Fallback: Block-specific control mappings (hardcoded)
        $blockControls = [
            // Media blocks
            'image' => ['image-settings', 'border', 'effects'],
            'image-gallery' => ['image-settings', 'layout-grid'],
            'video-embed' => ['size', 'border'],
            'parallax-image' => ['parallax-settings', 'background', 'effects'],
            'picture-element' => ['image-settings', 'size'],
            'cover' => ['image-settings', 'background', 'effects', 'size'],

            // Content blocks
            'heading' => ['typography', 'color-picker'],
            'text' => ['typography', 'color-picker'],
            'feature-card' => ['background', 'border', 'effects', 'typography'],
            'spec-table' => ['border', 'typography'],
            'merit-list' => ['layout-flex', 'layout-grid', 'color-picker', 'typography'],
            'info-card' => ['background', 'border', 'effects'],

            // Layout blocks
            'hero-banner' => ['parallax-settings', 'effects', 'position', 'background'],
            'grid-section' => ['layout-grid', 'position'],
            'two-column' => ['layout-flex', 'size'],
            'three-column' => ['layout-flex', 'size'],
            'full-width' => ['background', 'size'],

            // Interactive blocks
            'slider' => ['slider-settings', 'size'],
            'accordion' => ['border', 'typography', 'color-picker', 'transition'],
            'tabs' => ['typography', 'color-picker', 'border'],
            'cta-button' => ['typography', 'background', 'border', 'effects', 'hover-states', 'transition'],

            // PrestaShop blocks (with pd- prefix)
            'pd-merits' => ['list-settings', 'layout-flex', 'layout-grid', 'color-picker', 'typography', 'box-model'],
            'pd-slider' => ['slider-settings', 'size'],
            'pd-parallax' => ['parallax-settings', 'background', 'image-settings', 'effects'],
            'pd-specification' => ['border', 'typography', 'background', 'table-settings'],
            'pd-asset-list' => ['list-settings', 'layout-grid', 'layout-flex', 'typography', 'color-picker', 'box-model'],
            'prestashop-section' => ['background', 'size', 'layout-flex'],

            // PP.0.5: New PrestaShop section types (detected from iframe)
            'pd-intro' => ['typography', 'background', 'layout-flex', 'image-settings', 'size', 'box-model'],
            'pd-cover' => ['image-settings', 'background', 'effects', 'size', 'parallax-settings'],
            'pd-features' => ['layout-grid', 'color-picker', 'typography'],
            'pd-more-links' => ['layout-flex', 'typography', 'background'],
            'pd-footer' => ['typography', 'background', 'layout-flex'],
            'pd-header' => ['typography', 'background', 'layout-flex'],
            'pd-gallery' => ['layout-grid', 'image-settings', 'effects'],
            'pd-video' => ['size', 'border', 'effects'],
            'pd-accordion' => ['border', 'typography', 'color-picker', 'transition'],
            'pd-tabs' => ['typography', 'color-picker', 'border'],
            'pd-cta' => ['typography', 'background', 'border', 'effects'],
            'pd-hero' => ['parallax-settings', 'effects', 'background', 'size'],
            'pd-grid' => ['layout-grid', 'background'],
            'pd-section' => ['background', 'size', 'layout-flex'],
            'pd-block' => ['background', 'layout-flex', 'size'],  // Generic block fallback
            'pd-base' => ['background', 'layout-flex', 'size'],   // Base grid fallback

            // PP.3: Missing block types from ETAP_07h_Property_Panel_Complete.md
            'pd-base-grid' => ['layout-grid', 'background', 'box-model', 'size'],
            'pd-pseudo-parallax' => ['parallax-settings', 'background', 'image-settings', 'effects', 'size'],
            'pd-where-2-ride' => ['layout-grid', 'typography', 'link-settings', 'box-model'],
            'pd-block-row' => ['layout-flex', 'background', 'border', 'box-model'],

            // Imported/raw blocks - provide base editing controls
            'raw-html' => ['typography', 'color-picker', 'background', 'border', 'effects'],
        ];

        // Try normalized type first, then original
        return $blockControls[$normalizedType] ?? $blockControls[$blockType] ?? [];
    }

    /**
     * ETAP_07h PP.3: Match element CSS classes to dynamic control selectors.
     *
     * Finds the most specific matching selector from dynamic controls
     * based on element's CSS classes.
     *
     * @param array<string> $cssClasses Element's CSS classes
     * @param array<string, array<string>> $dynamicControls Selector → controls mapping
     * @return array<string> Matched controls (empty if no match)
     */
    private function matchCssClassesToControls(array $cssClasses, array $dynamicControls): array
    {
        if (empty($cssClasses)) {
            return [];
        }

        $bestMatch = [];
        $bestMatchScore = 0;

        foreach ($dynamicControls as $selector => $controls) {
            // Skip 'root' - it's the fallback
            if ($selector === 'root') {
                continue;
            }

            // Parse selector (supports .class, .class1.class2, .class h4, etc.)
            $score = $this->calculateSelectorMatchScore($selector, $cssClasses);

            if ($score > $bestMatchScore) {
                $bestMatchScore = $score;
                $bestMatch = $controls;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate how well a CSS selector matches element's classes.
     *
     * @param string $selector CSS selector (e.g., '.pd-merit h4', '.pd-pseudo-parallax__title')
     * @param array<string> $cssClasses Element's CSS classes
     * @return int Match score (0 = no match, higher = better match)
     */
    private function calculateSelectorMatchScore(string $selector, array $cssClasses): int
    {
        $score = 0;

        // Handle multiple selectors separated by comma
        $selectors = array_map('trim', explode(',', $selector));

        foreach ($selectors as $singleSelector) {
            // Extract class names from selector
            // Matches .class-name patterns
            if (preg_match_all('/\.([a-zA-Z0-9_-]+)/', $singleSelector, $matches)) {
                $selectorClasses = $matches[1];

                // Check if ALL classes in selector exist in element
                $allMatch = true;
                $matchCount = 0;

                foreach ($selectorClasses as $selectorClass) {
                    // Check for exact match or prefix match (BEM)
                    $found = false;
                    foreach ($cssClasses as $elementClass) {
                        if ($elementClass === $selectorClass) {
                            $found = true;
                            $matchCount += 2; // Exact match bonus
                            break;
                        }
                        // BEM prefix match (pd-merit matches pd-merit__icon)
                        if (str_starts_with($elementClass, $selectorClass . '__') ||
                            str_starts_with($elementClass, $selectorClass . '--')) {
                            $found = true;
                            $matchCount += 1; // Partial match
                            break;
                        }
                    }
                    if (!$found) {
                        $allMatch = false;
                        break;
                    }
                }

                if ($allMatch && $matchCount > 0) {
                    // Longer selectors (more specific) get higher scores
                    $selectorScore = $matchCount + count($selectorClasses);
                    $score = max($score, $selectorScore);
                }
            }
        }

        return $score;
    }

    /**
     * ETAP_07h PP.3: Detect if blockType is a PrestaShop section type.
     *
     * Maps iframe block types (cover, parallax, merits, intro, etc.)
     * to PrestashopSectionBlock section_type values.
     *
     * @param string $blockType Block type from iframe
     * @return string|null Section type if recognized, null otherwise
     */
    private function detectPrestashopSectionType(string $blockType): ?string
    {
        // Remove pd- prefix if present for uniform detection
        $normalizedType = str_starts_with($blockType, 'pd-')
            ? substr($blockType, 3)
            : $blockType;

        // List of recognized PrestaShop section types
        // These correspond to PrestashopSectionBlock::getPropertyPanelControlsForType() cases
        $sectionTypes = [
            'cover' => 'cover',
            'parallax' => 'parallax',
            'pseudo-parallax' => 'parallax',
            'intro' => 'intro',
            'merits' => 'merits',
            'specification' => 'specification',
            'asset-list' => 'asset-list',
            'slider' => 'slider',
            'more-links' => 'more-links',
            'where-2-ride' => 'where-2-ride',
            'footer' => 'footer',
            'block' => 'block',
            'section' => 'block',
            'base' => 'block',
            // PP.3: Additional section type mappings
            'base-grid' => 'intro',  // pd-base-grid often contains intro sections
            'block-row' => 'block',  // pd-block-row is a layout container
        ];

        return $sectionTypes[$normalizedType] ?? null;
    }

    /**
     * Normalize block type by adding pd- prefix for PrestaShop blocks.
     *
     * PP.0.5: Handles cases where iframe sends "merits" but map has "pd-merits".
     *
     * @param string $blockType Block type from iframe
     * @return string Normalized block type
     */
    private function normalizeBlockType(string $blockType): string
    {
        // Already has pd- prefix
        if (str_starts_with($blockType, 'pd-')) {
            return $blockType;
        }

        // List of types that should be prefixed with pd-
        $prestaShopTypes = [
            'intro', 'cover', 'slider', 'parallax', 'specification', 'merits',
            'features', 'more-links', 'footer', 'header', 'gallery', 'video',
            'accordion', 'tabs', 'cta', 'hero', 'grid', 'section', 'block', 'base',
            'asset-list',
            // PP.3: Additional PrestaShop block types
            'base-grid', 'pseudo-parallax', 'where-2-ride', 'block-row',
        ];

        if (in_array($blockType, $prestaShopTypes, true)) {
            return 'pd-' . $blockType;
        }

        return $blockType;
    }

    /**
     * Get base controls for element type.
     *
     * @param string $elementType Element type
     * @return array<string> Control types
     */
    private function getBaseControlsForElement(string $elementType): array
    {
        // Base controls available for all elements
        $base = ['box-model', 'size'];

        // Text elements get typography
        $textElements = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'li', 'label', 'figcaption', 'caption', 'td', 'th'];
        if (in_array($elementType, $textElements, true)) {
            $base[] = 'typography';
            $base[] = 'color-picker';
        }

        // Container elements get layout controls
        $containerElements = ['div', 'section', 'article', 'aside', 'nav', 'header', 'footer', 'main', 'ul', 'ol', 'figure', 'table', 'tbody', 'thead', 'tr'];
        if (in_array($elementType, $containerElements, true)) {
            $base[] = 'layout-flex';
            $base[] = 'background';
        }

        // Images get specific controls (img, picture)
        if (in_array($elementType, ['img', 'picture', 'source'], true)) {
            $base[] = 'image-settings';
            $base[] = 'border';
            $base[] = 'effects';
        }

        // Figure elements (image containers) get image-settings
        if ($elementType === 'figure') {
            $base[] = 'image-settings';
            $base[] = 'border';
            $base[] = 'effects';
        }

        // Video/media elements
        if (in_array($elementType, ['video', 'iframe', 'embed'], true)) {
            $base[] = 'border';
            $base[] = 'effects';
        }

        // Table elements get border
        if (in_array($elementType, ['table', 'td', 'th', 'tr'], true)) {
            $base[] = 'border';
        }

        return $base;
    }

    /**
     * Filter out controls that are inappropriate for the element type.
     *
     * For example, images should not have typography controls even if
     * they are inside a raw-html block that normally provides them.
     *
     * @param array<string> $controlTypes Control type identifiers
     * @param string $elementType HTML element type
     * @return array<string> Filtered control types
     */
    private function filterControlsForElementType(array $controlTypes, string $elementType): array
    {
        // Non-text elements should NOT have typography controls
        $nonTextElements = ['img', 'picture', 'source', 'video', 'iframe', 'embed', 'audio', 'canvas', 'svg', 'hr', 'br'];

        if (in_array($elementType, $nonTextElements, true)) {
            $controlTypes = array_filter($controlTypes, function ($control) {
                // Remove text-specific controls for non-text elements
                return !in_array($control, ['typography', 'color-picker'], true);
            });
        }

        return array_values($controlTypes);
    }

    /**
     * Resolve controls into tab groups.
     *
     * @param array<string, array<string, mixed>> $controls Controls
     * @return array<string, array<string, mixed>> Tabs with controls
     */
    public function resolveControlGroups(array $controls): array
    {
        $tabs = [];

        foreach (self::TABS as $tabKey => $tabConfig) {
            if ($tabKey === 'classes') {
                // Classes tab is handled separately
                $tabs[$tabKey] = [
                    'key' => $tabKey,
                    'label' => $tabConfig['label'],
                    'icon' => $tabConfig['icon'],
                    'priority' => $tabConfig['priority'],
                    'controls' => [],
                ];
                continue;
            }

            $tabControls = [];
            foreach ($controls as $type => $definition) {
                $controlGroup = $definition['group'] ?? 'Style';
                if (in_array($controlGroup, $tabConfig['groups'], true)) {
                    $tabControls[$type] = $definition;
                }
            }

            // Sort by priority
            uasort($tabControls, fn($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

            $tabs[$tabKey] = [
                'key' => $tabKey,
                'label' => $tabConfig['label'],
                'icon' => $tabConfig['icon'],
                'priority' => $tabConfig['priority'],
                'controls' => $tabControls,
            ];
        }

        // Sort tabs by priority
        uasort($tabs, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $tabs;
    }

    /**
     * Get merged defaults from all CSS classes.
     *
     * @param array<string> $cssClasses CSS classes
     * @return array<string, mixed> Merged defaults
     */
    public function getMergedDefaults(array $cssClasses): array
    {
        $defaults = [];

        foreach ($cssClasses as $className) {
            $classDefaults = $this->classMapper->getMergedDefaults($className);
            $defaults = array_merge($defaults, $classDefaults);
        }

        return $defaults;
    }

    /**
     * Apply defaults to controls.
     *
     * @param array<string, array<string, mixed>> $controls Controls
     * @param array<string, mixed> $defaults CSS defaults
     * @return array<string, array<string, mixed>> Controls with applied defaults
     */
    public function applyDefaults(array $controls, array $defaults): array
    {
        foreach ($controls as $type => &$definition) {
            $controlProps = $definition['cssProperties'] ?? [];
            $controlDefaults = [];

            foreach ($controlProps as $prop) {
                // Convert CSS property to camelCase for lookup
                $camelProp = $this->cssMapper->toCamelCase($prop);
                if (isset($defaults[$camelProp])) {
                    $controlDefaults[$camelProp] = $defaults[$camelProp];
                } elseif (isset($defaults[$prop])) {
                    $controlDefaults[$prop] = $defaults[$prop];
                }
            }

            if (!empty($controlDefaults)) {
                $definition['appliedDefaults'] = $controlDefaults;
            }
        }

        return $controls;
    }

    /**
     * Merge current styles with defaults.
     *
     * @param array<string, mixed> $currentStyles Current inline styles
     * @param array<string, mixed> $defaults Default values
     * @return array<string, mixed> Merged values
     */
    private function mergeStylesWithDefaults(array $currentStyles, array $defaults): array
    {
        // Defaults first, then current styles override
        return array_merge($defaults, $currentStyles);
    }

    /**
     * Group flat CSS values by control type.
     * This is critical for panel controls like typography to receive their values
     * grouped under a single key (e.g., $values['typography']).
     *
     * @param array<string, mixed> $flatValues Flat CSS properties
     * @param array<string, array<string, mixed>> $controls Control definitions
     * @return array<string, array<string, mixed>> Values grouped by control type
     */
    private function groupValuesByControl(array $flatValues, array $controls): array
    {
        $grouped = [];

        foreach ($controls as $type => $definition) {
            $cssProperties = $definition['cssProperties'] ?? [];
            $controlValues = [];

            foreach ($cssProperties as $prop) {
                // Try camelCase version first (how values are stored)
                $camelProp = $this->cssMapper->toCamelCase($prop);
                if (isset($flatValues[$camelProp])) {
                    $controlValues[$camelProp] = $flatValues[$camelProp];
                } elseif (isset($flatValues[$prop])) {
                    // Fallback to kebab-case
                    $controlValues[$camelProp] = $flatValues[$prop];
                }
            }

            // ETAP_07h: For image-settings, also include imageUrl and src attributes (non-CSS)
            // These are HTML attributes extracted from IMG elements
            if ($type === 'image-settings') {
                if (isset($flatValues['imageUrl'])) {
                    $controlValues['imageUrl'] = $flatValues['imageUrl'];
                }
                if (isset($flatValues['src'])) {
                    $controlValues['src'] = $flatValues['src'];
                    // Also set imageUrl if not already set
                    if (!isset($controlValues['imageUrl'])) {
                        $controlValues['imageUrl'] = $flatValues['src'];
                    }
                }
            }

            // NOTE: Background control receives ONLY CSS background-image/background-color
            // Nested <img> elements are handled by image-settings control, NOT background control
            // IMG ≠ Background Image - these are conceptually different!

            // Only add if there are any values for this control
            // Include empty array so control can initialize with defaults
            $grouped[$type] = $controlValues;
        }

        // Also include flat values for backward compatibility
        // This allows direct access like $values['textDecoration']
        return array_merge($flatValues, $grouped);
    }

    /**
     * Validate control values.
     *
     * @param array<string, mixed> $values Values to validate
     * @param array<string, array<string, mixed>> $controls Control definitions
     * @return array<string, array<string>> Validation errors by control type
     */
    public function validateValues(array $values, array $controls): array
    {
        $errors = [];

        foreach ($controls as $type => $definition) {
            $controlErrors = $this->validateControlValue($type, $values, $definition);
            if (!empty($controlErrors)) {
                $errors[$type] = $controlErrors;
            }
        }

        return $errors;
    }

    /**
     * Validate single control value.
     *
     * @param string $type Control type
     * @param array<string, mixed> $values All values
     * @param array<string, mixed> $definition Control definition
     * @return array<string> Errors
     */
    private function validateControlValue(string $type, array $values, array $definition): array
    {
        $errors = [];
        $cssProps = $definition['cssProperties'] ?? [];

        foreach ($cssProps as $prop) {
            $camelProp = $this->cssMapper->toCamelCase($prop);
            $value = $values[$camelProp] ?? $values[$prop] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // Type-specific validation
            switch ($type) {
                case 'color-picker':
                    if (!$this->isValidColor($value)) {
                        $errors[] = "Nieprawidlowy kolor: {$value}";
                    }
                    break;

                case 'size':
                case 'box-model':
                    if (!$this->isValidSizeValue($value)) {
                        $errors[] = "Nieprawidlowa wartosc: {$value}";
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Check if value is a valid CSS color.
     */
    private function isValidColor(string $value): bool
    {
        // Hex colors
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
            return true;
        }

        // RGB/RGBA
        if (preg_match('/^rgba?\(/', $value)) {
            return true;
        }

        // HSL/HSLA
        if (preg_match('/^hsla?\(/', $value)) {
            return true;
        }

        // CSS variables
        if (str_starts_with($value, 'var(')) {
            return true;
        }

        // Named colors (basic check)
        $namedColors = ['transparent', 'inherit', 'currentColor', 'white', 'black', 'red', 'green', 'blue'];
        return in_array(strtolower($value), $namedColors, true);
    }

    /**
     * Check if value is a valid CSS size value.
     */
    private function isValidSizeValue(string $value): bool
    {
        // Keywords
        $keywords = ['auto', 'inherit', 'initial', 'unset', 'none', '0'];
        if (in_array($value, $keywords, true)) {
            return true;
        }

        // CSS functions
        if (preg_match('/^(calc|clamp|min|max|var)\(/', $value)) {
            return true;
        }

        // Value with unit
        if (preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vw|vh|vmin|vmax|ch|ex)?$/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Build classes tab content.
     *
     * @param array<string> $currentClasses Current CSS classes
     * @return array<string, mixed> Classes tab configuration
     */
    private function buildClassesTabContent(array $currentClasses): array
    {
        $availableClasses = $this->classMapper->getGroupedClasses();

        return [
            'current' => $currentClasses,
            'available' => $availableClasses,
            'prestashopClasses' => PrestaShopCssDefinitions::getGroupedClasses(),
        ];
    }

    /**
     * Get controls that support responsive values.
     *
     * @param array<string, array<string, mixed>> $controls Controls
     * @return array<string> Control types
     */
    private function getResponsiveControls(array $controls): array
    {
        return array_keys(array_filter($controls, fn($c) => $c['responsive'] ?? false));
    }

    /**
     * Get controls that support hover states.
     *
     * @param array<string, array<string, mixed>> $controls Controls
     * @return array<string> Control types
     */
    private function getHoverControls(array $controls): array
    {
        return array_keys(array_filter($controls, fn($c) => $c['hover'] ?? false));
    }

    /**
     * Format control values to CSS.
     *
     * Delegates to CssValueFormatter.
     *
     * @param string $controlType Control type
     * @param mixed $value Control value
     * @return array<string, string> CSS property-value pairs
     */
    public function formatToCss(string $controlType, mixed $value): array
    {
        return $this->formatter->formatToCss($controlType, $value, $this->registry);
    }

    /**
     * Parse CSS values to control value format.
     *
     * @param string $controlType Control type
     * @param array<string, string> $cssProperties CSS property-value pairs
     * @return mixed Parsed control value
     */
    public function parseCssToValue(string $controlType, array $cssProperties): mixed
    {
        return $this->formatter->parseCssToValue($controlType, $cssProperties);
    }

    /**
     * Get the registry instance.
     */
    public function getRegistry(): PropertyControlRegistry
    {
        return $this->registry;
    }

    /**
     * Get the class mapper instance.
     */
    public function getClassMapper(): CssClassControlMapper
    {
        return $this->classMapper;
    }

    /**
     * Get the CSS value formatter instance.
     */
    public function getFormatter(): CssValueFormatter
    {
        return $this->formatter;
    }
}
