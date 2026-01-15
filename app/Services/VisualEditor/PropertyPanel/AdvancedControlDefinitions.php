<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

/**
 * Advanced control definitions for Property Panel.
 *
 * Contains configurations for advanced and interactive controls:
 * background, effects, transform, position, size, slider, parallax,
 * media, hover, transition, device-switcher, responsive-wrapper.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
final class AdvancedControlDefinitions
{
    /**
     * Background Control definition.
     */
    public static function background(): array
    {
        return [
            'label' => 'Tlo',
            'cssProperties' => [
                'background', 'background-color', 'background-image',
                'background-position', 'background-size', 'background-repeat', 'background-attachment',
            ],
            'defaultValue' => [
                'type' => 'color', 'color' => '', 'image' => '',
                'position' => 'center', 'size' => 'cover', 'repeat' => 'no-repeat', 'attachment' => 'scroll',
            ],
            'viewPath' => 'components.property-panel.controls.background',
            'options' => [
                'types' => ['color', 'image', 'gradient'],
                'positions' => [
                    'center' => 'Srodek', 'top' => 'Gora', 'bottom' => 'Dol',
                    'left' => 'Lewo', 'right' => 'Prawo',
                    'top left' => 'Gora lewo', 'top right' => 'Gora prawo',
                    'bottom left' => 'Dol lewo', 'bottom right' => 'Dol prawo',
                ],
                'sizes' => [
                    'cover' => 'Cover', 'contain' => 'Contain', 'auto' => 'Auto', '100% 100%' => 'Rozciagnij',
                ],
                'repeats' => [
                    'no-repeat' => 'Bez powtarzania', 'repeat' => 'Powtarzaj',
                    'repeat-x' => 'Powtarzaj X', 'repeat-y' => 'Powtarzaj Y',
                ],
                'attachments' => [
                    'scroll' => 'Scroll', 'fixed' => 'Fixed (Parallax)', 'local' => 'Local',
                ],
            ],
            'group' => 'Style',
            'priority' => 25,
            'icon' => 'image',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Effects Control definition.
     */
    public static function effects(): array
    {
        return [
            'label' => 'Efekty',
            'cssProperties' => ['box-shadow', 'text-shadow', 'opacity', 'filter'],
            'defaultValue' => [
                'boxShadow' => [
                    'enabled' => false, 'x' => '0', 'y' => '4px', 'blur' => '6px',
                    'spread' => '0', 'color' => 'rgba(0,0,0,0.1)', 'inset' => false,
                ],
                'textShadow' => [
                    'enabled' => false, 'x' => '0', 'y' => '2px', 'blur' => '4px', 'color' => 'rgba(0,0,0,0.3)',
                ],
                'opacity' => '1',
            ],
            'viewPath' => 'components.property-panel.controls.effects',
            'options' => [
                'shadowPresets' => [
                    'none' => ['label' => 'Brak', 'value' => 'none'],
                    'sm' => ['label' => 'Maly', 'value' => '0 1px 2px rgba(0,0,0,0.05)'],
                    'md' => ['label' => 'Sredni', 'value' => '0 4px 6px -1px rgba(0,0,0,0.1)'],
                    'lg' => ['label' => 'Duzy', 'value' => '0 10px 15px -3px rgba(0,0,0,0.1)'],
                    'xl' => ['label' => 'XL', 'value' => '0 20px 25px -5px rgba(0,0,0,0.1)'],
                ],
                'opacityStep' => 0.05,
            ],
            'group' => 'Advanced',
            'priority' => 40,
            'icon' => 'sparkles',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Transform Control definition.
     */
    public static function transform(): array
    {
        return [
            'label' => 'Transformacja',
            'cssProperties' => ['transform', 'transform-origin'],
            'defaultValue' => [
                'rotate' => '0', 'scaleX' => '1', 'scaleY' => '1',
                'translateX' => '0', 'translateY' => '0', 'skewX' => '0', 'skewY' => '0', 'origin' => 'center',
            ],
            'viewPath' => 'components.property-panel.controls.transform',
            'options' => [
                'origins' => [
                    'center' => 'Srodek', 'top left' => 'Gora lewo', 'top' => 'Gora', 'top right' => 'Gora prawo',
                    'left' => 'Lewo', 'right' => 'Prawo',
                    'bottom left' => 'Dol lewo', 'bottom' => 'Dol', 'bottom right' => 'Dol prawo',
                ],
                'units' => ['rotate' => 'deg', 'scale' => '', 'translate' => 'px', 'skew' => 'deg'],
            ],
            'group' => 'Advanced',
            'priority' => 45,
            'icon' => 'transform',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Position Control definition.
     */
    public static function position(): array
    {
        return [
            'label' => 'Pozycja',
            'cssProperties' => ['position', 'top', 'right', 'bottom', 'left', 'z-index'],
            'defaultValue' => [
                'position' => 'relative', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'zIndex' => '',
            ],
            'viewPath' => 'components.property-panel.controls.position',
            'options' => [
                'positions' => [
                    'static' => 'Static', 'relative' => 'Relative', 'absolute' => 'Absolute',
                    'fixed' => 'Fixed', 'sticky' => 'Sticky',
                ],
                'units' => ['px', 'rem', '%', 'vw', 'vh'],
            ],
            'group' => 'Layout',
            'priority' => 50,
            'icon' => 'move',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Size Control definition.
     */
    public static function size(): array
    {
        return [
            'label' => 'Rozmiar',
            'cssProperties' => ['width', 'height', 'min-width', 'max-width', 'min-height', 'max-height'],
            'defaultValue' => [
                'width' => '', 'height' => '', 'minWidth' => '', 'maxWidth' => '', 'minHeight' => '', 'maxHeight' => '',
            ],
            'viewPath' => 'components.property-panel.controls.size',
            'options' => [
                'units' => ['px', 'rem', '%', 'vw', 'vh', 'auto'],
                'presets' => [
                    'auto' => 'Auto', '100%' => 'Pelna', '50%' => 'Polowa',
                    '960px' => 'Max Content', '100vw' => 'Full Viewport Width', '100vh' => 'Full Viewport Height',
                ],
            ],
            'group' => 'Layout',
            'priority' => 55,
            'icon' => 'maximize',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Slider Settings Control definition.
     */
    public static function sliderSettings(): array
    {
        return [
            'label' => 'Slider',
            'cssProperties' => [],
            'defaultValue' => [
                'type' => 'loop', 'perPage' => 1, 'perMove' => 1, 'gap' => '1rem',
                'autoplay' => false, 'interval' => 5000, 'pauseOnHover' => true,
                'arrows' => true, 'pagination' => true, 'speed' => 400, 'easing' => 'ease',
                'drag' => true, 'breakpoints' => [],
            ],
            'viewPath' => 'components.property-panel.controls.slider-settings',
            'options' => [
                'types' => ['slide' => 'Slide', 'loop' => 'Loop', 'fade' => 'Fade'],
                'easings' => [
                    'ease' => 'Ease', 'ease-in' => 'Ease In', 'ease-out' => 'Ease Out',
                    'ease-in-out' => 'Ease In Out', 'linear' => 'Linear',
                ],
            ],
            'group' => 'Interactive',
            'priority' => 60,
            'icon' => 'layers',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Parallax Settings Control definition.
     */
    public static function parallaxSettings(): array
    {
        return [
            'label' => 'Parallax',
            'cssProperties' => ['min-height', 'background-attachment', 'background-position', 'background-size'],
            'defaultValue' => [
                'enabled' => false, 'height' => '400px', 'speed' => 0.5,
                'overlayEnabled' => true, 'overlayColor' => 'rgba(0,0,0,0.5)', 'backgroundImage' => '',
            ],
            'viewPath' => 'components.property-panel.controls.parallax-settings',
            'options' => [
                'heightPresets' => ['300px' => 'Maly', '400px' => 'Sredni', '500px' => 'Duzy', '100vh' => 'Pelny ekran'],
                'speedRange' => ['min' => 0, 'max' => 1, 'step' => 0.1],
            ],
            'group' => 'Interactive',
            'priority' => 65,
            'icon' => 'layers',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Media Picker Control definition.
     */
    public static function mediaPicker(): array
    {
        return [
            'label' => 'Media',
            'cssProperties' => ['background-image'],
            'defaultValue' => ['type' => 'none', 'url' => '', 'alt' => '', 'galleryId' => null],
            'viewPath' => 'components.property-panel.controls.media-picker',
            'options' => [
                'types' => ['none' => 'Brak', 'gallery' => 'Z galerii', 'upload' => 'Wgraj', 'url' => 'URL'],
                'acceptedFormats' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
                'maxFileSize' => 5 * 1024 * 1024,
            ],
            'group' => 'Interactive',
            'priority' => 70,
            'icon' => 'image',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Hover States Control definition.
     */
    public static function hoverStates(): array
    {
        return [
            'label' => 'Stany',
            'cssProperties' => [],
            'defaultValue' => ['currentState' => 'normal', 'hoverStyles' => []],
            'viewPath' => 'components.property-panel.controls.hover-states',
            'options' => ['states' => ['normal' => 'Normalny', 'hover' => 'Hover']],
            'group' => 'States',
            'priority' => 75,
            'icon' => 'mouse-pointer',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Transition Control definition.
     */
    public static function transition(): array
    {
        return [
            'label' => 'Przejscie',
            'cssProperties' => [
                'transition', 'transition-property', 'transition-duration',
                'transition-timing-function', 'transition-delay',
            ],
            'defaultValue' => [
                'enabled' => false, 'property' => 'all', 'duration' => '0.3s', 'timing' => 'ease', 'delay' => '0s',
            ],
            'viewPath' => 'components.property-panel.controls.transition',
            'options' => [
                'properties' => [
                    'all' => 'Wszystko', 'transform' => 'Transform', 'opacity' => 'Opacity',
                    'background' => 'Background', 'color' => 'Color', 'box-shadow' => 'Shadow',
                ],
                'timings' => [
                    'ease' => 'Ease', 'ease-in' => 'Ease In', 'ease-out' => 'Ease Out',
                    'ease-in-out' => 'Ease In Out', 'linear' => 'Linear', 'cubic-bezier(0.4, 0, 0.2, 1)' => 'Smooth',
                ],
                'durationPresets' => ['0.15s', '0.3s', '0.5s', '0.7s', '1s'],
            ],
            'group' => 'Advanced',
            'priority' => 80,
            'icon' => 'zap',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Device Switcher Control definition.
     */
    public static function deviceSwitcher(): array
    {
        return [
            'label' => 'Urzadzenie',
            'cssProperties' => [],
            'defaultValue' => 'desktop',
            'viewPath' => 'components.property-panel.controls.device-switcher',
            'options' => [
                'devices' => [
                    'desktop' => ['label' => 'Desktop', 'icon' => 'monitor', 'width' => null],
                    'tablet' => ['label' => 'Tablet', 'icon' => 'tablet', 'width' => '768px'],
                    'mobile' => ['label' => 'Mobile', 'icon' => 'smartphone', 'width' => '375px'],
                ],
                'breakpoints' => ['desktop' => 1024, 'tablet' => 768, 'mobile' => 0],
            ],
            'group' => 'States',
            'priority' => 1,
            'icon' => 'monitor',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Responsive Wrapper Control definition.
     */
    public static function responsiveWrapper(): array
    {
        return [
            'label' => 'Responsywnosc',
            'cssProperties' => [],
            'defaultValue' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
            'viewPath' => 'components.property-panel.controls.responsive-wrapper',
            'options' => [
                'breakpoints' => [
                    'desktop' => ['min' => 1024, 'label' => 'Desktop'],
                    'tablet' => ['min' => 768, 'max' => 1023, 'label' => 'Tablet'],
                    'mobile' => ['max' => 767, 'label' => 'Mobile'],
                ],
            ],
            'group' => 'States',
            'priority' => 2,
            'icon' => 'smartphone',
            'responsive' => false,
            'hover' => false,
        ];
    }

    /**
     * Get all advanced control definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'background' => self::background(),
            'effects' => self::effects(),
            'transform' => self::transform(),
            'position' => self::position(),
            'size' => self::size(),
            'slider-settings' => self::sliderSettings(),
            'parallax-settings' => self::parallaxSettings(),
            'media-picker' => self::mediaPicker(),
            'hover-states' => self::hoverStates(),
            'transition' => self::transition(),
            'device-switcher' => self::deviceSwitcher(),
            'responsive-wrapper' => self::responsiveWrapper(),
        ];
    }
}
