<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Button Settings Control definition for Property Panel.
 *
 * Provides controls specific to CTAButtonBlock:
 * - Button style (solid, outline, ghost)
 * - Button size (small, medium, large)
 * - Border radius
 * - Icon position
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class ButtonSettingsDefinition
{
    /**
     * Get the button-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia przycisku',
            'cssProperties' => [
                'padding',
                'border-radius',
                'font-size',
                'font-weight',
            ],
            'defaultValue' => [
                'buttonStyle' => 'solid',
                'buttonSize' => 'medium',
                'borderRadius' => '0.5rem',
                'fullWidth' => false,
                'iconPosition' => 'left',
                'hasIcon' => false,
                'fontWeight' => '600',
            ],
            'viewPath' => 'livewire.products.visual-description.controls.button-settings',
            'options' => [
                'buttonStyles' => [
                    'solid' => 'Wypelniony',
                    'outline' => 'Konturowy',
                    'ghost' => 'Przezroczysty',
                    'link' => 'Link',
                ],
                'buttonSizes' => [
                    'small' => 'Maly',
                    'medium' => 'Sredni',
                    'large' => 'Duzy',
                    'xl' => 'Bardzo duzy',
                ],
                'borderRadiusPresets' => [
                    '0' => 'Brak',
                    '0.25rem' => 'Maly',
                    '0.5rem' => 'Sredni',
                    '1rem' => 'Duzy',
                    '9999px' => 'Pelny (pill)',
                ],
                'iconPositions' => [
                    'left' => 'Po lewej',
                    'right' => 'Po prawej',
                ],
                'fontWeights' => [
                    '400' => 'Normalny',
                    '500' => 'Medium',
                    '600' => 'Semi-bold',
                    '700' => 'Bold',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 14,
            'icon' => 'cursor-arrow-rays',
            'responsive' => true,
            'hover' => true,
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

        // Padding based on button size
        $paddingMap = [
            'small' => '0.5rem 1rem',
            'medium' => '0.75rem 1.5rem',
            'large' => '1rem 2rem',
            'xl' => '1.25rem 2.5rem',
        ];
        $size = $values['buttonSize'] ?? 'medium';
        $css['padding'] = $paddingMap[$size] ?? $paddingMap['medium'];

        // Font size based on button size
        $fontSizeMap = [
            'small' => '0.875rem',
            'medium' => '1rem',
            'large' => '1.125rem',
            'xl' => '1.25rem',
        ];
        $css['font-size'] = $fontSizeMap[$size] ?? $fontSizeMap['medium'];

        if (!empty($values['borderRadius'])) {
            $css['border-radius'] = $values['borderRadius'];
        }

        if (!empty($values['fontWeight'])) {
            $css['font-weight'] = $values['fontWeight'];
        }

        // Full width
        if (!empty($values['fullWidth'])) {
            $css['width'] = '100%';
            $css['display'] = 'block';
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
            'buttonSize' => 'medium',
            'borderRadius' => '0.5rem',
            'fontWeight' => '600',
            'fullWidth' => false,
        ];

        // Detect size from padding
        if (isset($cssProperties['padding'])) {
            $padding = $cssProperties['padding'];
            if (str_contains($padding, '0.5rem 1rem')) {
                $values['buttonSize'] = 'small';
            } elseif (str_contains($padding, '1rem 2rem')) {
                $values['buttonSize'] = 'large';
            } elseif (str_contains($padding, '1.25rem')) {
                $values['buttonSize'] = 'xl';
            }
        }

        if (isset($cssProperties['border-radius'])) {
            $values['borderRadius'] = $cssProperties['border-radius'];
        }

        if (isset($cssProperties['font-weight'])) {
            $values['fontWeight'] = $cssProperties['font-weight'];
        }

        if (isset($cssProperties['width']) && $cssProperties['width'] === '100%') {
            $values['fullWidth'] = true;
        }

        return $values;
    }
}
