<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Table Settings Control definition for Property Panel.
 *
 * Provides controls specific to SpecTableBlock:
 * - Table style (striped, bordered, hover)
 * - Header style
 * - Cell padding
 * - Border style
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class TableSettingsDefinition
{
    /**
     * Get the table-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia tabeli',
            'cssProperties' => [
                'border-collapse',
                'border-spacing',
            ],
            'defaultValue' => [
                'tableStyle' => 'striped',
                'headerStyle' => 'dark',
                'cellPadding' => 'medium',
                'borderStyle' => 'subtle',
                'hoverEffect' => true,
                'roundedCorners' => true,
                'fullWidth' => true,
                'compactMode' => false,
            ],
            'viewPath' => 'livewire.products.visual-description.controls.table-settings',
            'options' => [
                'tableStyles' => [
                    'simple' => 'Prosta',
                    'striped' => 'Paski',
                    'bordered' => 'Z ramkami',
                    'minimal' => 'Minimalistyczna',
                ],
                'headerStyles' => [
                    'none' => 'Brak',
                    'light' => 'Jasny',
                    'dark' => 'Ciemny',
                    'brand' => 'Kolor marki',
                ],
                'cellPaddingPresets' => [
                    'compact' => 'Kompaktowy',
                    'small' => 'Maly',
                    'medium' => 'Sredni',
                    'large' => 'Duzy',
                ],
                'borderStyles' => [
                    'none' => 'Brak',
                    'subtle' => 'Subtelne',
                    'normal' => 'Normalne',
                    'bold' => 'Grube',
                ],
            ],
            'group' => 'Content',
            'priority' => 15,
            'icon' => 'table-cells',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Convert control values to CSS properties.
     *
     * @param array<string, mixed> $values Control values
     * @return array<string, string> CSS property-value pairs
     */
    public static function toCss(array $values): array
    {
        $css = [];

        // Border collapse based on style
        $borderStyle = $values['borderStyle'] ?? 'subtle';
        if ($borderStyle === 'none') {
            $css['border-collapse'] = 'separate';
            $css['border-spacing'] = '0';
        } else {
            $css['border-collapse'] = 'collapse';
        }

        // Full width
        if (!empty($values['fullWidth'])) {
            $css['width'] = '100%';
        }

        return $css;
    }

    /**
     * Parse CSS properties to control values.
     *
     * @param array<string, string> $cssProperties CSS property-value pairs
     * @return array<string, mixed> Control values
     */
    public static function fromCss(array $cssProperties): array
    {
        $values = [
            'tableStyle' => 'striped',
            'cellPadding' => 'medium',
            'borderStyle' => 'subtle',
            'fullWidth' => true,
        ];

        if (isset($cssProperties['border-collapse'])) {
            if ($cssProperties['border-collapse'] === 'separate') {
                $values['borderStyle'] = 'none';
            }
        }

        if (isset($cssProperties['width']) && $cssProperties['width'] === '100%') {
            $values['fullWidth'] = true;
        }

        return $values;
    }

    /**
     * Get CSS classes for table based on settings.
     *
     * @param array<string, mixed> $values Control values
     * @return array<string> CSS class names
     */
    public static function getCssClasses(array $values): array
    {
        $classes = ['uve-spec-table'];

        // Table style class
        $tableStyle = $values['tableStyle'] ?? 'striped';
        $classes[] = "uve-table-{$tableStyle}";

        // Header style class
        $headerStyle = $values['headerStyle'] ?? 'dark';
        if ($headerStyle !== 'none') {
            $classes[] = "uve-table-header-{$headerStyle}";
        }

        // Cell padding class
        $cellPadding = $values['cellPadding'] ?? 'medium';
        $classes[] = "uve-table-padding-{$cellPadding}";

        // Optional classes
        if (!empty($values['hoverEffect'])) {
            $classes[] = 'uve-table-hover';
        }

        if (!empty($values['roundedCorners'])) {
            $classes[] = 'uve-table-rounded';
        }

        if (!empty($values['compactMode'])) {
            $classes[] = 'uve-table-compact';
        }

        return $classes;
    }
}
