<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * List Settings Control definition for Property Panel.
 *
 * Provides controls specific to MeritListBlock:
 * - List style (bullets, numbers, icons, checkmarks)
 * - Icon style and color
 * - Spacing between items
 * - Layout (vertical, horizontal grid)
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class ListSettingsDefinition
{
    /**
     * Get the list-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia listy',
            'cssProperties' => [
                'list-style-type',
                'gap',
                'padding-left',
            ],
            'defaultValue' => [
                'listStyle' => 'checkmarks',
                'iconStyle' => 'check-circle',
                'iconColor' => 'brand',
                'layout' => 'vertical',
                'columns' => 1,
                'itemGap' => '0.75rem',
                'iconSize' => 'medium',
                'indentation' => '0',
            ],
            'viewPath' => 'livewire.products.visual-description.controls.list-settings',
            'options' => [
                'listStyles' => [
                    'none' => 'Brak znacznikow',
                    'bullets' => 'Punktory',
                    'numbers' => 'Numerowana',
                    'checkmarks' => 'Ptaszki',
                    'icons' => 'Ikony',
                    'arrows' => 'Strzalki',
                ],
                'iconStyles' => [
                    'check' => 'Ptaszek prosty',
                    'check-circle' => 'Ptaszek w kolku',
                    'check-badge' => 'Ptaszek w znaczku',
                    'star' => 'Gwiazdka',
                    'arrow-right' => 'Strzalka',
                    'chevron-right' => 'Chevron',
                    'plus' => 'Plus',
                    'dot' => 'Kropka',
                ],
                'iconColors' => [
                    'brand' => 'Kolor marki',
                    'success' => 'Zielony',
                    'info' => 'Niebieski',
                    'warning' => 'Zolty',
                    'dark' => 'Ciemny',
                    'inherit' => 'Dziedziczony',
                ],
                'layouts' => [
                    'vertical' => 'Pionowy',
                    'horizontal' => 'Poziomy',
                    'grid' => 'Siatka',
                ],
                'columnPresets' => [
                    1 => '1 kolumna',
                    2 => '2 kolumny',
                    3 => '3 kolumny',
                    4 => '4 kolumny',
                ],
                'gapPresets' => [
                    '0.25rem' => 'XS',
                    '0.5rem' => 'S',
                    '0.75rem' => 'M',
                    '1rem' => 'L',
                    '1.5rem' => 'XL',
                ],
                'iconSizes' => [
                    'small' => 'Maly (16px)',
                    'medium' => 'Sredni (20px)',
                    'large' => 'Duzy (24px)',
                ],
            ],
            'group' => 'Content',
            'priority' => 16,
            'icon' => 'list-bullet',
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

        // List style type based on listStyle
        $listStyle = $values['listStyle'] ?? 'checkmarks';
        if ($listStyle === 'bullets') {
            $css['list-style-type'] = 'disc';
        } elseif ($listStyle === 'numbers') {
            $css['list-style-type'] = 'decimal';
        } else {
            $css['list-style-type'] = 'none';
        }

        // Gap between items
        if (!empty($values['itemGap'])) {
            $css['gap'] = $values['itemGap'];
        }

        // Layout - grid columns
        $layout = $values['layout'] ?? 'vertical';
        if ($layout === 'grid' || $layout === 'horizontal') {
            $columns = $values['columns'] ?? 1;
            if ($columns > 1) {
                $css['display'] = 'grid';
                $css['grid-template-columns'] = "repeat({$columns}, 1fr)";
            }
        }

        // Indentation
        if (!empty($values['indentation']) && $values['indentation'] !== '0') {
            $css['padding-left'] = $values['indentation'];
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
            'listStyle' => 'checkmarks',
            'layout' => 'vertical',
            'columns' => 1,
            'itemGap' => '0.75rem',
        ];

        // Detect list style
        if (isset($cssProperties['list-style-type'])) {
            $type = $cssProperties['list-style-type'];
            if ($type === 'disc') {
                $values['listStyle'] = 'bullets';
            } elseif ($type === 'decimal') {
                $values['listStyle'] = 'numbers';
            }
        }

        // Gap
        if (isset($cssProperties['gap'])) {
            $values['itemGap'] = $cssProperties['gap'];
        }

        // Grid columns
        if (isset($cssProperties['grid-template-columns'])) {
            if (preg_match('/repeat\((\d+),/', $cssProperties['grid-template-columns'], $matches)) {
                $values['columns'] = (int) $matches[1];
                $values['layout'] = 'grid';
            }
        }

        // Indentation
        if (isset($cssProperties['padding-left'])) {
            $values['indentation'] = $cssProperties['padding-left'];
        }

        return $values;
    }

    /**
     * Get icon HTML based on icon style.
     *
     * @param string $iconStyle The icon style name
     * @param string $iconColor The icon color class
     * @return string HTML for the icon
     */
    public static function getIconHtml(string $iconStyle, string $iconColor = 'brand'): string
    {
        $colorClass = "uve-icon-{$iconColor}";

        return sprintf(
            '<span class="uve-list-icon %s" data-icon="%s"></span>',
            $colorClass,
            htmlspecialchars($iconStyle)
        );
    }
}
