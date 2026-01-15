<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * CSS Class Style Resolver.
 *
 * Automatically resolves inline styles from CSS class names.
 * When an element has CSS classes assigned, this service looks up
 * their style definitions and returns the merged styles.
 *
 * Usage:
 *   $resolver = new CssClassStyleResolver();
 *   $styles = $resolver->resolveStyles(['pd-intro__heading', 'pd-model']);
 *   // Returns: ['fontSize' => 'clamp(...)', 'fontWeight' => '800', ...]
 */
class CssClassStyleResolver
{
    /**
     * Resolve styles for given CSS classes.
     * Returns merged styles from PrestaShopCssDefinitions.
     *
     * @param array $classNames Array of CSS class names
     * @return array Merged styles in camelCase format
     */
    public function resolveStyles(array $classNames): array
    {
        return PrestaShopCssDefinitions::getMergedStyles($classNames);
    }

    /**
     * Apply resolved styles to an element.
     * Merges class-based styles with any existing inline styles.
     * Inline styles take precedence over class-based styles.
     *
     * @param array $element Element with 'classes' and optional 'styles'
     * @return array Element with resolved styles applied
     */
    public function applyStylesToElement(array $element): array
    {
        $classes = $element['classes'] ?? [];
        $existingStyles = $element['styles'] ?? [];

        // Get styles from CSS class definitions
        $classBasedStyles = $this->resolveStyles($classes);

        // Merge: class-based styles first, then existing inline styles override
        $element['styles'] = array_merge($classBasedStyles, $existingStyles);

        // Recursively process children
        if (!empty($element['children'])) {
            $element['children'] = array_map(
                fn($child) => $this->applyStylesToElement($child),
                $element['children']
            );
        }

        return $element;
    }

    /**
     * Apply styles to entire document tree.
     *
     * @param array $document BlockDocument structure
     * @return array Document with resolved styles
     */
    public function applyStylesToDocument(array $document): array
    {
        if (isset($document['root'])) {
            $document['root'] = $this->applyStylesToElement($document['root']);
        }

        return $document;
    }

    /**
     * Get style preview for a class (for UI display).
     *
     * @param string $className CSS class name
     * @return array Key style properties for preview
     */
    public function getClassPreview(string $className): array
    {
        $styles = PrestaShopCssDefinitions::getClassStyles($className);

        // Return only key visual properties for preview
        $previewProps = ['fontSize', 'fontWeight', 'color', 'backgroundColor', 'display', 'gridColumn'];

        return array_intersect_key($styles, array_flip($previewProps));
    }

    /**
     * Check if adding a class would change the element's styles.
     *
     * @param array $currentClasses Current element classes
     * @param string $newClass Class to potentially add
     * @return array ['wouldChange' => bool, 'newStyles' => array]
     */
    public function previewClassAddition(array $currentClasses, string $newClass): array
    {
        $currentStyles = $this->resolveStyles($currentClasses);
        $newStyles = $this->resolveStyles(array_merge($currentClasses, [$newClass]));

        return [
            'wouldChange' => $currentStyles !== $newStyles,
            'newStyles' => $newStyles,
            'addedStyles' => array_diff_assoc($newStyles, $currentStyles),
        ];
    }

    /**
     * Get all available PrestaShop classes grouped by category.
     *
     * @return array Grouped class names for UI
     */
    public function getAvailableClasses(): array
    {
        return PrestaShopCssDefinitions::getGroupedClasses();
    }

    /**
     * Validate that all classes in an element have definitions.
     *
     * @param array $classNames Classes to validate
     * @return array ['valid' => array, 'unknown' => array]
     */
    public function validateClasses(array $classNames): array
    {
        $valid = [];
        $unknown = [];

        foreach ($classNames as $className) {
            if (PrestaShopCssDefinitions::hasDefinition($className)) {
                $valid[] = $className;
            } else {
                $unknown[] = $className;
            }
        }

        return [
            'valid' => $valid,
            'unknown' => $unknown,
        ];
    }
}
