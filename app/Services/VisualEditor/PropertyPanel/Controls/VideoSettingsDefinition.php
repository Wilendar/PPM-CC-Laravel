<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel\Controls;

/**
 * Video Settings Control definition for Property Panel.
 *
 * Provides controls specific to VideoEmbedBlock:
 * - Aspect ratio (16:9, 4:3, 1:1)
 * - Autoplay, loop, muted, controls
 * - Thumbnail/poster image
 *
 * ETAP_07h Property Panel PP.2: Block-Specific Controls
 *
 * @package App\Services\VisualEditor\PropertyPanel\Controls
 */
final class VideoSettingsDefinition
{
    /**
     * Get the video-settings control definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'label' => 'Ustawienia wideo',
            'cssProperties' => [
                'aspect-ratio',
                'width',
                'max-width',
            ],
            'defaultValue' => [
                'aspectRatio' => '16/9',
                'width' => '100%',
                'maxWidth' => '100%',
                'autoplay' => false,
                'loop' => false,
                'muted' => false,
                'controls' => true,
                'lazyLoad' => true,
            ],
            'viewPath' => 'livewire.products.visual-description.controls.video-settings',
            'options' => [
                'aspectRatios' => [
                    '16/9' => 'Szerokoekranowy (16:9)',
                    '4/3' => 'Standard (4:3)',
                    '1/1' => 'Kwadrat (1:1)',
                    '21/9' => 'Ultraszerokoekranowy (21:9)',
                ],
                'widthPresets' => [
                    '100%' => 'Pelna szerokosc',
                    '75%' => 'Duzy (75%)',
                    '50%' => 'Sredni (50%)',
                ],
                'maxWidthPresets' => [
                    '100%' => 'Brak limitu',
                    '1200px' => '1200px',
                    '800px' => '800px',
                    '640px' => '640px',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 11,
            'icon' => 'video',
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

        if (!empty($values['aspectRatio'])) {
            $css['aspect-ratio'] = $values['aspectRatio'];
        }

        if (!empty($values['width'])) {
            $css['width'] = $values['width'];
        }

        if (!empty($values['maxWidth']) && $values['maxWidth'] !== '100%') {
            $css['max-width'] = $values['maxWidth'];
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
            'aspectRatio' => '16/9',
            'width' => '100%',
            'maxWidth' => '100%',
        ];

        if (isset($cssProperties['aspect-ratio'])) {
            $values['aspectRatio'] = $cssProperties['aspect-ratio'];
        }

        if (isset($cssProperties['width'])) {
            $values['width'] = $cssProperties['width'];
        }

        if (isset($cssProperties['max-width'])) {
            $values['maxWidth'] = $cssProperties['max-width'];
        }

        return $values;
    }
}
