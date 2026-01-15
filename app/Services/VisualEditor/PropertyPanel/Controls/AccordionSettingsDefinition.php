<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Accordion Settings Control definition for Property Panel.
 *
 * Provides controls specific to AccordionBlock:
 * - Icon style (plus/minus, chevron, arrow)
 * - Icon position (left, right)
 * - Allow multiple open
 * - Animation duration
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class AccordionSettingsDefinition
{
    /**
     * Get the accordion-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia akordeonu',
            'cssProperties' => [
                'transition-duration',
                'border-radius',
                'gap',
            ],
            'defaultValue' => [
                'iconStyle' => 'chevron',
                'iconPosition' => 'right',
                'allowMultiple' => false,
                'defaultOpen' => 'first',
                'animationDuration' => '0.3s',
                'gap' => '0.5rem',
                'borderRadius' => '0.5rem',
            ],
            'viewPath' => 'livewire.products.visual-description.controls.accordion-settings',
            'options' => [
                'iconStyles' => [
                    'chevron' => 'Strzalka (chevron)',
                    'plus' => 'Plus/Minus',
                    'arrow' => 'Strzalka w dol',
                    'none' => 'Brak ikony',
                ],
                'iconPositions' => [
                    'left' => 'Po lewej',
                    'right' => 'Po prawej',
                ],
                'defaultOpenOptions' => [
                    'none' => 'Wszystkie zamkniete',
                    'first' => 'Pierwszy otwarty',
                    'all' => 'Wszystkie otwarte',
                ],
                'animationDurations' => [
                    '0s' => 'Brak',
                    '0.2s' => 'Szybka',
                    '0.3s' => 'Normalna',
                    '0.5s' => 'Wolna',
                ],
                'gapPresets' => [
                    '0' => 'Brak',
                    '0.25rem' => 'XS',
                    '0.5rem' => 'S',
                    '1rem' => 'M',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 12,
            'icon' => 'bars-3-bottom-left',
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

        if (!empty($values['animationDuration'])) {
            $css['transition-duration'] = $values['animationDuration'];
        }

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
            'animationDuration' => '0.3s',
            'gap' => '0.5rem',
            'borderRadius' => '0.5rem',
        ];

        if (isset($cssProperties['transition-duration'])) {
            $values['animationDuration'] = $cssProperties['transition-duration'];
        }

        if (isset($cssProperties['gap'])) {
            $values['gap'] = $cssProperties['gap'];
        }

        if (isset($cssProperties['border-radius'])) {
            $values['borderRadius'] = $cssProperties['border-radius'];
        }

        return $values;
    }
}
