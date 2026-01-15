<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Image Settings Control definition for Property Panel.
 *
 * Provides controls specific to ImageBlock:
 * - Size presets (full, large, medium, small, custom)
 * - Alignment (left, center, right)
 * - Object fit (contain, cover, fill)
 * - Lightbox toggle
 * - Lazy loading toggle
 *
 * ETAP_07h Property Panel: ImageBlock Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class ImageSettingsDefinition
{
    /**
     * Get the image-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia obrazu',
            'cssProperties' => [
                'width',
                'height',
                'margin-left',
                'margin-right',
                'object-fit',
                'border-radius',
                'box-shadow',
            ],
            'defaultValue' => [
                'size' => 'full',
                'customWidth' => '100%',
                'customHeight' => 'auto',
                'alignment' => 'center',
                'objectFit' => 'contain',
                'borderRadius' => '0',
                'shadow' => false,
                'lightbox' => false,
                'lazyLoad' => true,
            ],
            'viewPath' => 'livewire.products.visual-description.controls.image-settings',
            'options' => [
                'sizes' => [
                    'full' => 'Pelny (100%)',
                    'large' => 'Duzy (75%)',
                    'medium' => 'Sredni (50%)',
                    'small' => 'Maly (25%)',
                    'custom' => 'Wlasny',
                ],
                'alignments' => [
                    'left' => 'Do lewej',
                    'center' => 'Wysrodkowany',
                    'right' => 'Do prawej',
                ],
                'objectFits' => [
                    'contain' => 'Zawiera (zachowuje proporcje)',
                    'cover' => 'Pokrywa (moze przyciac)',
                    'fill' => 'Wypelnia (rozciaga)',
                    'none' => 'Brak (oryginalne wymiary)',
                    'scale-down' => 'Pomniejsz (jesli wiekszy)',
                ],
                'borderRadiusPresets' => [
                    '0' => 'Brak',
                    '0.25rem' => 'XS',
                    '0.5rem' => 'S',
                    '0.75rem' => 'M',
                    '1rem' => 'L',
                    '50%' => 'Okragly',
                ],
            ],
            'group' => 'Style',
            'priority' => 8,
            'icon' => 'image',
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

        // Size handling
        $size = $values['size'] ?? 'full';
        $sizeMap = [
            'full' => '100%',
            'large' => '75%',
            'medium' => '50%',
            'small' => '25%',
        ];

        if ($size === 'custom') {
            $css['width'] = $values['customWidth'] ?? '100%';
            $css['height'] = $values['customHeight'] ?? 'auto';
        } elseif (isset($sizeMap[$size])) {
            $css['width'] = $sizeMap[$size];
            $css['height'] = 'auto';
        }

        // Object fit
        if (!empty($values['objectFit'])) {
            $css['object-fit'] = $values['objectFit'];
        }

        // Border radius
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
            'size' => 'full',
            'customWidth' => '100%',
            'customHeight' => 'auto',
            'objectFit' => 'contain',
            'borderRadius' => '0',
        ];

        // Detect size from width
        $width = $cssProperties['width'] ?? null;
        if ($width) {
            $sizeMap = [
                '100%' => 'full',
                '75%' => 'large',
                '50%' => 'medium',
                '25%' => 'small',
            ];

            if (isset($sizeMap[$width])) {
                $values['size'] = $sizeMap[$width];
            } else {
                $values['size'] = 'custom';
                $values['customWidth'] = $width;
            }
        }

        // Height
        if (isset($cssProperties['height'])) {
            $values['customHeight'] = $cssProperties['height'];
        }

        // Object fit
        if (isset($cssProperties['object-fit'])) {
            $values['objectFit'] = $cssProperties['object-fit'];
        }

        // Border radius
        if (isset($cssProperties['border-radius'])) {
            $values['borderRadius'] = $cssProperties['border-radius'];
        }

        return $values;
    }
}
