<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

use App\Services\VisualEditor\PrestaShopCssDefinitions;

/**
 * Maps CSS classes (pd-*) to Property Panel controls.
 *
 * Uses CssClassMappingDefinitions for static mapping data.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
class CssClassControlMapper
{
    private PropertyControlRegistry $registry;

    /** @var array<string, array<string, mixed>> */
    private array $mappings;

    public function __construct(PropertyControlRegistry $registry)
    {
        $this->registry = $registry;
        $this->mappings = CssClassMappingDefinitions::all();
    }

    /**
     * Get mapping for a CSS class.
     *
     * @param string $className CSS class name
     * @return array<string, mixed>|null Mapping or null
     */
    public function getMapping(string $className): ?array
    {
        return $this->mappings[$className] ?? null;
    }

    /**
     * Get all mappings.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllMappings(): array
    {
        return $this->mappings;
    }

    /**
     * Get control types for a CSS class.
     *
     * @param string $className CSS class name
     * @return array<string> Control types
     */
    public function getControlsForClass(string $className): array
    {
        $mapping = $this->getMapping($className);
        return $mapping['controls'] ?? [];
    }

    /**
     * Get default values for a CSS class.
     *
     * @param string $className CSS class name
     * @return array<string, mixed> Default CSS values
     */
    public function getDefaultsForClass(string $className): array
    {
        $mapping = $this->getMapping($className);
        return $mapping['defaults'] ?? [];
    }

    /**
     * Check if a CSS class is readonly.
     *
     * @param string $className CSS class name
     * @return bool True if readonly
     */
    public function isReadonly(string $className): bool
    {
        $mapping = $this->getMapping($className);
        return $mapping['readonly'] ?? false;
    }

    /**
     * Get class description.
     *
     * @param string $className CSS class name
     * @return string|null Description or null
     */
    public function getDescription(string $className): ?string
    {
        $mapping = $this->getMapping($className);
        return $mapping['description'] ?? null;
    }

    /**
     * Build Property Panel configuration for given CSS classes.
     *
     * @param array<string> $cssClasses List of CSS classes
     * @return array<string, mixed> Panel configuration
     */
    public function buildPanelConfig(array $cssClasses): array
    {
        $controlTypes = [];
        $defaults = [];
        $readonly = [];

        foreach ($cssClasses as $className) {
            $mapping = $this->getMapping($className);
            if (!$mapping) {
                continue;
            }

            foreach ($mapping['controls'] as $controlType) {
                if (!in_array($controlType, $controlTypes, true)) {
                    $controlTypes[] = $controlType;
                }
            }

            $defaults = array_merge($defaults, $mapping['defaults']);

            if ($mapping['readonly']) {
                $readonly[] = $className;
            }
        }

        $controls = [];
        foreach ($controlTypes as $type) {
            $controlDef = $this->registry->get($type);
            if ($controlDef) {
                $controls[$type] = $controlDef;
            }
        }

        return [
            'controls' => $controls,
            'defaults' => $defaults,
            'readonlyClasses' => $readonly,
            'cssClasses' => $cssClasses,
        ];
    }

    /**
     * Get grouped classes for UI display.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getGroupedClasses(): array
    {
        $groups = [
            'Layout' => [], 'Intro' => [], 'Model' => [], 'Cover' => [], 'Assets' => [],
            'Background' => [], 'Merits' => [], 'Specification' => [], 'Features' => [],
            'Slider' => [], 'Parallax' => [], 'Text' => [], 'Icons' => [],
        ];

        foreach ($this->mappings as $className => $mapping) {
            $group = $this->classifyToGroup($className);
            if (isset($groups[$group])) {
                $groups[$group][$className] = [
                    'name' => $className,
                    'description' => $mapping['description'],
                    'readonly' => $mapping['readonly'],
                    'controlCount' => count($mapping['controls']),
                ];
            }
        }

        return array_filter($groups, fn($g) => !empty($g));
    }

    /**
     * Classify class name to group.
     */
    private function classifyToGroup(string $className): string
    {
        $prefixes = [
            'pd-intro' => 'Intro', 'pd-model' => 'Model', 'pd-cover' => 'Cover',
            'pd-asset' => 'Assets', 'bg-' => 'Background', 'pd-merit' => 'Merits',
            'pd-specification' => 'Specification', 'pd-feature' => 'Features',
            'pd-slider' => 'Slider', 'pd-parallax' => 'Parallax', 'pd-pseudo' => 'Parallax',
            'text-' => 'Text', 'pd-icon' => 'Icons', 'pd-base' => 'Layout', 'grid-' => 'Layout',
        ];

        foreach ($prefixes as $prefix => $group) {
            if (str_starts_with($className, $prefix)) {
                return $group;
            }
        }

        return 'Layout';
    }

    /**
     * Validate CSS class name.
     *
     * @param string $className CSS class name
     * @return bool True if valid
     */
    public function isValidClass(string $className): bool
    {
        return isset($this->mappings[$className])
            || PrestaShopCssDefinitions::hasDefinition($className);
    }

    /**
     * Get all mapped class names.
     *
     * @return array<string>
     */
    public function getAllClassNames(): array
    {
        return array_keys($this->mappings);
    }

    /**
     * Get classes that use a specific control type.
     *
     * @param string $controlType Control type
     * @return array<string> Class names
     */
    public function getClassesUsingControl(string $controlType): array
    {
        $result = [];

        foreach ($this->mappings as $className => $mapping) {
            if (in_array($controlType, $mapping['controls'], true)) {
                $result[] = $className;
            }
        }

        return $result;
    }

    /**
     * Merge defaults from PrestaShopCssDefinitions.
     *
     * @param string $className CSS class name
     * @return array<string, mixed> Merged defaults
     */
    public function getMergedDefaults(string $className): array
    {
        $mapping = $this->getMapping($className);
        $mappedDefaults = $mapping['defaults'] ?? [];
        $definitionDefaults = PrestaShopCssDefinitions::getClassStyles($className);

        return array_merge($definitionDefaults, $mappedDefaults);
    }

    /**
     * Register a custom mapping.
     *
     * @param string $className CSS class name
     * @param array<string, mixed> $mapping Mapping definition
     */
    public function registerMapping(string $className, array $mapping): void
    {
        $this->mappings[$className] = array_merge([
            'controls' => [],
            'defaults' => [],
            'readonly' => false,
            'description' => '',
        ], $mapping);
    }

    /**
     * Get mapping count.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->mappings);
    }
}
