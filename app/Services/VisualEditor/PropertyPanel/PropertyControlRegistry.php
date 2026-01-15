<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

use App\Contracts\VisualEditor\PropertyPanel\PropertyControlInterface;
use App\Services\VisualEditor\PropertyPanel\Controls\AccordionSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\ButtonSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\GallerySettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\ImageSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\ListSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\TabsSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\TableSettingsDefinition;
use App\Services\VisualEditor\PropertyPanel\Controls\VideoSettingsDefinition;

/**
 * Registry of all Property Panel control types.
 *
 * Uses ControlDefinitions and AdvancedControlDefinitions for
 * static control configurations to keep this file manageable.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
class PropertyControlRegistry
{
    /**
     * Registered control definitions.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $controls = [];

    /**
     * Control groups for UI organization.
     * ETAP_07h PP.2: Added Content group for table/list controls
     */
    public const GROUPS = [
        'Style' => ['typography', 'color-picker', 'gradient-editor', 'background'],
        'Layout' => ['box-model', 'layout-flex', 'layout-grid', 'size', 'position'],
        'Advanced' => ['border', 'effects', 'transform', 'transition'],
        'Interactive' => ['slider-settings', 'parallax-settings', 'media-picker', 'gallery-settings', 'video-settings', 'accordion-settings', 'tabs-settings', 'button-settings'],
        'Content' => ['table-settings', 'list-settings', 'image-settings'],
        'States' => ['hover-states', 'device-switcher', 'responsive-wrapper'],
    ];

    public function __construct()
    {
        $this->registerBuiltInControls();
    }

    /**
     * Register all built-in control types.
     */
    private function registerBuiltInControls(): void
    {
        // Register basic controls from ControlDefinitions
        foreach (ControlDefinitions::all() as $type => $definition) {
            $this->register($type, $definition);
        }

        // Register advanced controls from AdvancedControlDefinitions
        foreach (AdvancedControlDefinitions::all() as $type => $definition) {
            $this->register($type, $definition);
        }

        // Register block-specific controls
        // ETAP_07h PP.2: Block-Specific Controls
        $this->register('image-settings', ImageSettingsDefinition::definition());
        $this->register('gallery-settings', GallerySettingsDefinition::definition());
        $this->register('video-settings', VideoSettingsDefinition::definition());
        $this->register('accordion-settings', AccordionSettingsDefinition::definition());
        $this->register('tabs-settings', TabsSettingsDefinition::definition());
        $this->register('button-settings', ButtonSettingsDefinition::definition());
        $this->register('table-settings', TableSettingsDefinition::definition());
        $this->register('list-settings', ListSettingsDefinition::definition());
    }

    /**
     * Register a control type.
     *
     * @param string $type Control type identifier
     * @param array<string, mixed> $definition Control definition
     */
    public function register(string $type, array $definition): void
    {
        $this->controls[$type] = array_merge([
            'type' => $type,
            'label' => $type,
            'cssProperties' => [],
            'defaultValue' => null,
            'viewPath' => "components.property-panel.controls.{$type}",
            'options' => [],
            'group' => 'Style',
            'priority' => 100,
            'icon' => null,
            'tooltip' => null,
            'readonly' => false,
            'responsive' => false,
            'hover' => false,
        ], $definition);
    }

    /**
     * Get control definition by type.
     *
     * @param string $type Control type
     * @return array<string, mixed>|null Control definition or null
     */
    public function get(string $type): ?array
    {
        return $this->controls[$type] ?? null;
    }

    /**
     * Get all registered controls.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->controls;
    }

    /**
     * Get controls by group.
     *
     * @param string $group Group name
     * @return array<string, array<string, mixed>>
     */
    public function getByGroup(string $group): array
    {
        return array_filter($this->controls, fn($c) => $c['group'] === $group);
    }

    /**
     * Get controls sorted by priority.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSorted(): array
    {
        $sorted = $this->controls;
        uasort($sorted, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return $sorted;
    }

    /**
     * Get controls for a CSS property.
     *
     * @param string $cssProperty CSS property name
     * @return array<string> Control types that handle this property
     */
    public function getControlsForProperty(string $cssProperty): array
    {
        $result = [];

        foreach ($this->controls as $type => $definition) {
            if (in_array($cssProperty, $definition['cssProperties'], true)) {
                $result[] = $type;
            }
        }

        return $result;
    }

    /**
     * Check if control type exists.
     *
     * @param string $type Control type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->controls[$type]);
    }

    /**
     * Get control types that support responsive values.
     *
     * @return array<string>
     */
    public function getResponsiveControls(): array
    {
        return array_keys(array_filter($this->controls, fn($c) => $c['responsive']));
    }

    /**
     * Get control types that support hover states.
     *
     * @return array<string>
     */
    public function getHoverControls(): array
    {
        return array_keys(array_filter($this->controls, fn($c) => $c['hover']));
    }

    /**
     * Get count of registered controls.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->controls);
    }

    /**
     * Get all control types.
     *
     * @return array<string>
     */
    public function getTypes(): array
    {
        return array_keys($this->controls);
    }

    /**
     * Remove a registered control.
     *
     * @param string $type Control type
     */
    public function unregister(string $type): void
    {
        unset($this->controls[$type]);
    }

    /**
     * Override a control definition.
     *
     * @param string $type Control type
     * @param array<string, mixed> $overrides Properties to override
     */
    public function override(string $type, array $overrides): void
    {
        if (isset($this->controls[$type])) {
            $this->controls[$type] = array_merge($this->controls[$type], $overrides);
        }
    }
}
