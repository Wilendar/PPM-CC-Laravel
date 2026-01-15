<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Tabs Settings Control definition for Property Panel.
 *
 * Provides controls specific to TabsBlock:
 * - Tab style (underline, pills, boxed)
 * - Tab alignment
 * - Active tab indicator
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class TabsSettingsDefinition
{
    /**
     * Get the tabs-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia zakladek',
            'cssProperties' => [
                'border-radius',
                'gap',
            ],
            'defaultValue' => [
                'tabStyle' => 'underline',
                'alignment' => 'left',
                'activeIndicator' => 'underline',
                'fullWidth' => false,
                'gap' => '0.5rem',
                'borderRadius' => '0.5rem',
            ],
            'viewPath' => 'livewire.products.visual-description.controls.tabs-settings',
            'options' => [
                'tabStyles' => [
                    'underline' => 'Podkreslenie',
                    'pills' => 'Pigulki',
                    'boxed' => 'Ramki',
                    'minimal' => 'Minimalistyczne',
                ],
                'alignments' => [
                    'left' => 'Do lewej',
                    'center' => 'Wysrodkowane',
                    'right' => 'Do prawej',
                    'justify' => 'Rowno rozlozone',
                ],
                'activeIndicators' => [
                    'underline' => 'Podkreslenie',
                    'background' => 'Tlo',
                    'border' => 'Ramka',
                ],
                'gapPresets' => [
                    '0' => 'Brak',
                    '0.25rem' => 'XS',
                    '0.5rem' => 'S',
                    '1rem' => 'M',
                    '1.5rem' => 'L',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 13,
            'icon' => 'window',
            'responsive' => false,
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

        if (!empty($values['gap'])) {
            $css['gap'] = $values['gap'];
        }

        if (!empty($values['borderRadius'])) {
            $css['border-radius'] = $values['borderRadius'];
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
            'gap' => '0.5rem',
            'borderRadius' => '0.5rem',
        ];

        if (isset($cssProperties['gap'])) {
            $values['gap'] = $cssProperties['gap'];
        }

        if (isset($cssProperties['border-radius'])) {
            $values['borderRadius'] = $cssProperties['border-radius'];
        }

        return $values;
    }
}
