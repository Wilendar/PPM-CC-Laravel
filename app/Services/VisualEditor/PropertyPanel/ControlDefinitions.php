<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

/**
 * Static control definitions for Property Panel.
 *
 * Contains default configurations for all 20 control types.
 * Used by PropertyControlRegistry to initialize controls.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
final class ControlDefinitions
{
    /**
     * Box Model Control definition.
     */
    public static function boxModel(): array
    {
        return [
            'label' => 'Box Model',
            'cssProperties' => [
                'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
                'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
                'border-radius',
            ],
            'defaultValue' => [
                'margin' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true],
                'padding' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true],
                'borderRadius' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'linked' => true],
            ],
            'viewPath' => 'components.property-panel.controls.box-model',
            'options' => [
                'units' => ['px', 'rem', 'em', '%'],
                'linkedToggle' => true,
                'presets' => [
                    'none' => ['value' => '0', 'label' => 'Brak'],
                    'sm' => ['value' => '0.5rem', 'label' => 'S'],
                    'md' => ['value' => '1rem', 'label' => 'M'],
                    'lg' => ['value' => '1.5rem', 'label' => 'L'],
                    'xl' => ['value' => '2rem', 'label' => 'XL'],
                ],
            ],
            'group' => 'Layout',
            'priority' => 10,
            'icon' => 'box',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Typography Control definition.
     */
    public static function typography(): array
    {
        return [
            'label' => 'Typografia',
            'cssProperties' => [
                'font-size', 'font-weight', 'font-family', 'font-style',
                'line-height', 'letter-spacing', 'text-transform', 'text-decoration',
                'text-align',
            ],
            'defaultValue' => [
                'fontSize' => '',
                'fontWeight' => '400',
                'fontFamily' => 'inherit',
                'fontStyle' => 'normal',
                'lineHeight' => '',
                'letterSpacing' => '',
                'textTransform' => 'none',
                'textDecoration' => 'none',
                'textAlign' => 'left',
            ],
            'viewPath' => 'components.property-panel.controls.typography',
            'options' => [
                'fontWeights' => [
                    '100' => 'Thin', '200' => 'Extra Light', '300' => 'Light',
                    '400' => 'Normal', '500' => 'Medium', '600' => 'Semi Bold',
                    '700' => 'Bold', '800' => 'Extra Bold', '900' => 'Black',
                ],
                'fontFamilies' => [
                    'inherit' => 'Dziedzicz',
                    'system-ui, sans-serif' => 'System',
                    'Arial, sans-serif' => 'Arial',
                    'Georgia, serif' => 'Georgia',
                    'monospace' => 'Monospace',
                ],
                'textTransforms' => [
                    'none' => 'Brak', 'uppercase' => 'WIELKIE',
                    'lowercase' => 'male', 'capitalize' => 'Kapitalizuj',
                ],
                'textDecorations' => [
                    'none' => 'Brak', 'underline' => 'Podkreslenie', 'line-through' => 'Przekreslenie',
                ],
                'textAligns' => [
                    'left' => 'Do lewej', 'center' => 'Wycentruj',
                    'right' => 'Do prawej', 'justify' => 'Wyjustuj',
                ],
                'units' => ['px', 'rem', 'em', '%', 'vw'],
            ],
            'group' => 'Style',
            'priority' => 5,
            'icon' => 'type',
            'responsive' => true,
            'hover' => true,
        ];
    }

    /**
     * Color Picker Control definition.
     */
    public static function colorPicker(): array
    {
        return [
            'label' => 'Kolor',
            'cssProperties' => ['color', 'background-color', 'border-color'],
            'defaultValue' => '',
            'viewPath' => 'components.property-panel.controls.color-picker',
            'options' => [
                'presets' => [
                    '#ef8248' => 'Brand Orange', '#1a1a1a' => 'Dark',
                    '#f6f6f6' => 'Light Gray', '#ffffff' => 'White',
                    '#000000' => 'Black', '#333333' => 'Text',
                    '#666666' => 'Text Light', '#10b981' => 'Success',
                    '#ef4444' => 'Error', '#3b82f6' => 'Info',
                ],
                'formats' => ['hex', 'rgba', 'hsla'],
                'showAlpha' => true,
                'showInput' => true,
            ],
            'group' => 'Style',
            'priority' => 15,
            'icon' => 'palette',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Gradient Editor Control definition.
     */
    public static function gradientEditor(): array
    {
        return [
            'label' => 'Gradient',
            'cssProperties' => ['background', 'background-image'],
            'defaultValue' => [
                'type' => 'linear',
                'angle' => 180,
                'stops' => [
                    ['color' => '#f6f6f6', 'position' => 0],
                    ['color' => '#ef8248', 'position' => 100],
                ],
            ],
            'viewPath' => 'components.property-panel.controls.gradient-editor',
            'options' => [
                'types' => ['linear', 'radial'],
                'presets' => [
                    'cover-gradient' => [
                        'type' => 'linear', 'angle' => 180,
                        'stops' => [
                            ['color' => '#f6f6f6', 'position' => 70],
                            ['color' => '#ef8248', 'position' => 70],
                        ],
                    ],
                    'dark-overlay' => [
                        'type' => 'linear', 'angle' => 180,
                        'stops' => [
                            ['color' => 'rgba(0,0,0,0)', 'position' => 0],
                            ['color' => 'rgba(0,0,0,0.5)', 'position' => 100],
                        ],
                    ],
                ],
                'maxStops' => 5,
            ],
            'group' => 'Style',
            'priority' => 20,
            'icon' => 'gradient',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Layout Flex Control definition.
     */
    public static function layoutFlex(): array
    {
        return [
            'label' => 'Flexbox',
            'cssProperties' => [
                'display', 'flex-direction', 'flex-wrap', 'justify-content',
                'align-items', 'align-content', 'gap', 'row-gap', 'column-gap',
            ],
            'defaultValue' => [
                'display' => 'flex',
                'flexDirection' => 'row',
                'flexWrap' => 'nowrap',
                'justifyContent' => 'flex-start',
                'alignItems' => 'stretch',
                'gap' => '',
            ],
            'viewPath' => 'components.property-panel.controls.layout-flex',
            'options' => [
                'directions' => [
                    'row' => 'Wiersz', 'row-reverse' => 'Wiersz (odwrocony)',
                    'column' => 'Kolumna', 'column-reverse' => 'Kolumna (odwrocona)',
                ],
                'wraps' => [
                    'nowrap' => 'Bez zawijania', 'wrap' => 'Zawijaj', 'wrap-reverse' => 'Zawijaj (odwrocone)',
                ],
                'justifies' => [
                    'flex-start' => 'Start', 'flex-end' => 'End', 'center' => 'Srodek',
                    'space-between' => 'Space Between', 'space-around' => 'Space Around', 'space-evenly' => 'Space Evenly',
                ],
                'aligns' => [
                    'flex-start' => 'Start', 'flex-end' => 'End', 'center' => 'Srodek',
                    'stretch' => 'Rozciagnij', 'baseline' => 'Baseline',
                ],
            ],
            'group' => 'Layout',
            'priority' => 25,
            'icon' => 'layout',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Layout Grid Control definition.
     */
    public static function layoutGrid(): array
    {
        return [
            'label' => 'Grid',
            'cssProperties' => [
                'display', 'grid-template-columns', 'grid-template-rows',
                'gap', 'row-gap', 'column-gap', 'grid-auto-flow',
            ],
            'defaultValue' => [
                'display' => 'grid',
                'gridTemplateColumns' => '',
                'gridTemplateRows' => '',
                'gap' => '1rem',
            ],
            'viewPath' => 'components.property-panel.controls.layout-grid',
            'options' => [
                'columnPresets' => [
                    '1fr' => '1 kolumna', 'repeat(2, 1fr)' => '2 kolumny',
                    'repeat(3, 1fr)' => '3 kolumny', 'repeat(4, 1fr)' => '4 kolumny',
                    'repeat(6, 1fr)' => '6 kolumn', 'repeat(auto-fit, minmax(280px, 1fr))' => 'Auto-fit 280px',
                ],
                'rowPresets' => [
                    'auto' => 'Auto', '1fr' => '1fr', 'repeat(2, 1fr)' => '2 wiersze',
                ],
            ],
            'group' => 'Layout',
            'priority' => 30,
            'icon' => 'grid',
            'responsive' => true,
            'hover' => false,
        ];
    }

    /**
     * Border Control definition.
     */
    public static function border(): array
    {
        return [
            'label' => 'Obramowanie',
            'cssProperties' => [
                'border', 'border-width', 'border-style', 'border-color',
                'border-top', 'border-right', 'border-bottom', 'border-left', 'border-radius',
            ],
            'defaultValue' => [
                'width' => '', 'style' => 'solid', 'color' => '', 'radius' => '',
            ],
            'viewPath' => 'components.property-panel.controls.border',
            'options' => [
                'styles' => [
                    'none' => 'Brak', 'solid' => 'Ciagla', 'dashed' => 'Kreskowana',
                    'dotted' => 'Kropkowana', 'double' => 'Podwojna',
                ],
                'units' => ['px', 'rem', 'em'],
                'radiusPresets' => [
                    '0' => 'Brak', '0.25rem' => 'XS', '0.5rem' => 'S',
                    '0.75rem' => 'M', '1rem' => 'L', '50%' => 'Okragly',
                ],
            ],
            'group' => 'Advanced',
            'priority' => 35,
            'icon' => 'border',
            'responsive' => false,
            'hover' => true,
        ];
    }

    /**
     * Get all control definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'box-model' => self::boxModel(),
            'typography' => self::typography(),
            'color-picker' => self::colorPicker(),
            'gradient-editor' => self::gradientEditor(),
            'layout-flex' => self::layoutFlex(),
            'layout-grid' => self::layoutGrid(),
            'border' => self::border(),
        ];
    }
}
