<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Gallery Settings Control definition for Property Panel.
 *
 * Provides controls specific to ImageGalleryBlock:
 * - Column count (2-6)
 * - Gap size
 * - Thumbnail aspect ratio
 * - Lightbox settings
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class GallerySettingsDefinition
{
    /**
     * Get the gallery-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia galerii',
            'cssProperties' => [
                'grid-template-columns',
                'gap',
                'aspect-ratio',
            ],
            'defaultValue' => [
                'columns' => 3,
                'gap' => '1rem',
                'aspectRatio' => '1/1',
                'thumbnailSize' => 'medium',
                'lightbox' => true,
                'showCaptions' => false,
                'hoverEffect' => 'zoom',
            ],
            'viewPath' => 'livewire.products.visual-description.controls.gallery-settings',
            'options' => [
                'columnPresets' => [
                    2 => '2 kolumny',
                    3 => '3 kolumny',
                    4 => '4 kolumny',
                    5 => '5 kolumn',
                    6 => '6 kolumn',
                ],
                'gapPresets' => [
                    '0' => 'Brak',
                    '0.5rem' => 'XS',
                    '1rem' => 'S',
                    '1.5rem' => 'M',
                    '2rem' => 'L',
                    '3rem' => 'XL',
                ],
                'aspectRatios' => [
                    '1/1' => 'Kwadrat (1:1)',
                    '4/3' => 'Standard (4:3)',
                    '16/9' => 'Szerokoekranowy (16:9)',
                    '3/2' => 'Foto (3:2)',
                    'auto' => 'Oryginalny',
                ],
                'thumbnailSizes' => [
                    'small' => 'Maly (150px)',
                    'medium' => 'Sredni (300px)',
                    'large' => 'Duzy (450px)',
                ],
                'hoverEffects' => [
                    'none' => 'Brak',
                    'zoom' => 'Powiekszenie',
                    'fade' => 'Przyciemnienie',
                    'slide' => 'Przesuniecie',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 10,
            'icon' => 'photos',
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

        // Grid columns
        $columns = $values['columns'] ?? 3;
        $css['grid-template-columns'] = "repeat({$columns}, 1fr)";

        // Gap
        if (!empty($values['gap'])) {
            $css['gap'] = $values['gap'];
        }

        // Aspect ratio (applied to items)
        if (!empty($values['aspectRatio']) && $values['aspectRatio'] !== 'auto') {
            $css['aspect-ratio'] = $values['aspectRatio'];
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
            'columns' => 3,
            'gap' => '1rem',
            'aspectRatio' => '1/1',
        ];

        // Parse grid-template-columns
        if (isset($cssProperties['grid-template-columns'])) {
            if (preg_match('/repeat\((\d+),/', $cssProperties['grid-template-columns'], $matches)) {
                $values['columns'] = (int) $matches[1];
            }
        }

        // Gap
        if (isset($cssProperties['gap'])) {
            $values['gap'] = $cssProperties['gap'];
        }

        // Aspect ratio
        if (isset($cssProperties['aspect-ratio'])) {
            $values['aspectRatio'] = $cssProperties['aspect-ratio'];
        }

        return $values;
    }
}
