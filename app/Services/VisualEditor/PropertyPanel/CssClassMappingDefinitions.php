<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\PropertyPanel;

/**
 * Static CSS class to control mappings.
 *
 * Contains mapping definitions for 40+ PrestaShop CSS classes
 * to their corresponding Property Panel control types.
 *
 * ETAP_07f_P5 FAZA PP.1: Property Panel Infrastructure
 *
 * @package App\Services\VisualEditor\PropertyPanel
 */
final class CssClassMappingDefinitions
{
    /**
     * Get all class to controls mappings.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return array_merge(
            self::layoutMappings(),
            self::introMappings(),
            self::backgroundMappings(),
            self::meritsMappings(),
            self::specificationMappings(),
            self::sliderMappings(),
            self::parallaxMappings(),
            self::featuresMappings(),
            self::textMappings(),
            self::iconMappings()
        );
    }

    /**
     * Layout class mappings.
     */
    private static function layoutMappings(): array
    {
        return [
            'pd-base-grid' => [
                'controls' => ['layout-grid', 'box-model'],
                'defaults' => ['display' => 'grid', 'gridTemplateColumns' => 'repeat(6, 1fr)', 'gap' => '1rem'],
                'readonly' => false,
                'description' => 'Grid bazowy 6-kolumnowy',
            ],
            'grid-row' => [
                'controls' => ['layout-grid'],
                'defaults' => ['gridColumn' => '1 / -1'],
                'readonly' => true,
                'description' => 'Pelna szerokosc wiersza',
            ],
        ];
    }

    /**
     * Intro section mappings.
     */
    private static function introMappings(): array
    {
        return [
            'pd-intro' => [
                'controls' => ['layout-grid', 'box-model'],
                'defaults' => [],
                'readonly' => false,
                'description' => 'Sekcja wprowadzenia',
            ],
            'pd-intro__heading' => [
                'controls' => ['layout-grid', 'typography', 'color-picker', 'box-model'],
                'defaults' => [
                    'display' => 'grid', 'gridTemplateColumns' => '160px auto', 'gridTemplateRows' => 'auto auto',
                    'gap' => '0 16px', 'fontSize' => 'clamp(2rem, 5vw, 3.5rem)', 'lineHeight' => '1',
                    'fontWeight' => '800', 'margin' => '0 0 1.5rem 0', 'color' => '#000',
                ],
                'readonly' => false,
                'description' => 'Naglowek z pomaranczowym paskiem',
            ],
            'pd-intro__text' => [
                'controls' => ['typography', 'color-picker', 'layout-grid'],
                'defaults' => ['color' => '#333333', 'lineHeight' => '1.6', 'fontSize' => '1rem', 'gridColumn' => 'span 3', 'margin' => '0'],
                'readonly' => false,
                'description' => 'Tekst wprowadzenia',
            ],
            'pd-model' => [
                'controls' => ['layout-grid'],
                'defaults' => [],
                'readonly' => true,
                'description' => 'Kontener nazwy modelu',
            ],
            'pd-model__type' => [
                'controls' => ['typography', 'color-picker', 'layout-grid'],
                'defaults' => [
                    'display' => 'block', 'fontSize' => '0.7em', 'fontWeight' => '400', 'color' => '#000',
                    'gridColumn' => '2 / -1', 'gridRow' => '2 / 3', 'alignSelf' => 'center',
                ],
                'readonly' => false,
                'description' => 'Typ produktu',
            ],
            'pd-model__name' => [
                'controls' => ['typography', 'color-picker', 'layout-grid'],
                'defaults' => [
                    'display' => 'block', 'fontSize' => 'inherit', 'fontWeight' => '800', 'color' => '#000',
                    'gridColumn' => '1 / -1', 'gridRow' => '1 / 2',
                ],
                'readonly' => false,
                'description' => 'Nazwa produktu',
            ],
            'pd-cover' => [
                // FIX #13d: Added 'background' control - gradient is inherited from .pd-cover__picture child
                'controls' => ['background', 'layout-flex', 'layout-grid'],
                'defaults' => ['gridColumn' => '1 / -1', 'display' => 'flex', 'justifyContent' => 'center'],
                'readonly' => false,
                'description' => 'Sekcja glownego obrazu',
            ],
            'pd-cover__picture' => [
                'controls' => ['gradient-editor', 'layout-flex', 'size'],
                'defaults' => [
                    'background' => 'linear-gradient(#f6f6f6 70%, #ef8248 70%)', 'display' => 'flex',
                    'alignItems' => 'flex-end', 'justifyContent' => 'center', 'width' => '100%', 'maxWidth' => '960px',
                ],
                'readonly' => false,
                'description' => 'Obraz z gradientem',
            ],
            'pd-asset-list' => [
                'controls' => ['layout-flex', 'box-model', 'typography'],
                'defaults' => [
                    'display' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'center', 'gap' => '0 3rem',
                    'padding' => '1.5rem 1rem', 'margin' => '0', 'listStyle' => 'none',
                ],
                'readonly' => false,
                'description' => 'Lista kluczowych parametrow',
            ],
        ];
    }

    /**
     * Background color mappings.
     */
    private static function backgroundMappings(): array
    {
        return [
            'bg-brand' => [
                'controls' => ['color-picker', 'box-model'],
                'defaults' => ['backgroundColor' => '#ef8248', 'padding' => '1.5rem'],
                'readonly' => false,
                'description' => 'Pomaranczowe tlo brand',
            ],
            'bg-neutral-accent' => [
                'controls' => ['color-picker'],
                'defaults' => ['backgroundColor' => '#f6f6f6'],
                'readonly' => false,
                'description' => 'Jasnoszare tlo',
            ],
            'bg-dark' => [
                'controls' => ['color-picker', 'typography'],
                'defaults' => ['backgroundColor' => '#1a1a1a', 'color' => '#ffffff'],
                'readonly' => false,
                'description' => 'Ciemne tlo z bialym tekstem',
            ],
        ];
    }

    /**
     * Merits section mappings.
     */
    private static function meritsMappings(): array
    {
        return [
            'pd-merits' => [
                'controls' => ['layout-grid', 'box-model'],
                'defaults' => [
                    'display' => 'grid', 'gridTemplateColumns' => 'repeat(auto-fit, minmax(280px, 1fr))',
                    'gap' => '1.5rem', 'padding' => '2rem 0',
                ],
                'readonly' => false,
                'description' => 'Siatka korzysci',
            ],
            'pd-merit' => [
                'controls' => ['layout-flex', 'box-model', 'typography'],
                'defaults' => [
                    'display' => 'flex', 'flexDirection' => 'column', 'alignItems' => 'center',
                    'textAlign' => 'center', 'padding' => '1.5rem',
                ],
                'readonly' => false,
                'description' => 'Pojedyncza korzysc',
            ],
            'pd-merit__icon' => [
                'controls' => ['typography', 'color-picker', 'box-model'],
                'defaults' => ['fontSize' => '2.5rem', 'marginBottom' => '1rem', 'color' => '#ef8248'],
                'readonly' => false,
                'description' => 'Ikona korzysci',
            ],
            'pd-merit__title' => [
                'controls' => ['typography', 'color-picker', 'box-model'],
                'defaults' => ['fontSize' => '1.125rem', 'fontWeight' => '700', 'marginBottom' => '0.5rem', 'color' => '#1a1a1a'],
                'readonly' => false,
                'description' => 'Tytul korzysci',
            ],
            'pd-merit__text' => [
                'controls' => ['typography', 'color-picker'],
                'defaults' => ['fontSize' => '0.875rem', 'color' => '#666666', 'lineHeight' => '1.5'],
                'readonly' => false,
                'description' => 'Opis korzysci',
            ],
        ];
    }

    /**
     * Specification table mappings.
     */
    private static function specificationMappings(): array
    {
        return [
            'pd-specification' => [
                'controls' => ['size', 'border'],
                'defaults' => ['width' => '100%', 'borderCollapse' => 'collapse'],
                'readonly' => false,
                'description' => 'Tabela specyfikacji',
            ],
            'pd-specification__row' => [
                'controls' => ['border'],
                'defaults' => ['borderBottom' => '1px solid #e5e5e5'],
                'readonly' => false,
                'description' => 'Wiersz specyfikacji',
            ],
            'pd-specification__label' => [
                'controls' => ['typography', 'color-picker', 'box-model', 'size'],
                'defaults' => ['padding' => '0.75rem 1rem', 'fontWeight' => '600', 'color' => '#1a1a1a', 'width' => '40%'],
                'readonly' => false,
                'description' => 'Etykieta specyfikacji',
            ],
            'pd-specification__value' => [
                'controls' => ['typography', 'color-picker', 'box-model'],
                'defaults' => ['padding' => '0.75rem 1rem', 'color' => '#333333'],
                'readonly' => false,
                'description' => 'Wartosc specyfikacji',
            ],
        ];
    }

    /**
     * Slider mappings.
     */
    private static function sliderMappings(): array
    {
        return [
            'pd-slider' => [
                'controls' => ['slider-settings', 'position', 'size'],
                'defaults' => ['position' => 'relative', 'overflow' => 'hidden'],
                'readonly' => false,
                'description' => 'Slider obrazow',
            ],
            'pd-slider__track' => [
                'controls' => ['layout-flex', 'transition'],
                'defaults' => ['display' => 'flex', 'transition' => 'transform 0.3s ease'],
                'readonly' => true,
                'description' => 'Sciezka slidera',
            ],
            'pd-slider__slide' => [
                'controls' => ['layout-flex', 'size'],
                'defaults' => ['flexShrink' => '0', 'width' => '100%'],
                'readonly' => true,
                'description' => 'Pojedynczy slajd',
            ],
            'pd-slider__nav' => [
                'controls' => ['position', 'color-picker', 'box-model', 'border'],
                'defaults' => [
                    'position' => 'absolute', 'top' => '50%', 'transform' => 'translateY(-50%)',
                    'backgroundColor' => 'rgba(0, 0, 0, 0.5)', 'color' => '#ffffff', 'padding' => '0.5rem', 'borderRadius' => '50%',
                ],
                'readonly' => false,
                'description' => 'Nawigacja slidera',
            ],
        ];
    }

    /**
     * Parallax mappings.
     */
    private static function parallaxMappings(): array
    {
        return [
            'pd-parallax' => [
                'controls' => ['parallax-settings', 'background', 'size', 'position'],
                'defaults' => [
                    'position' => 'relative', 'minHeight' => '400px', 'backgroundAttachment' => 'fixed',
                    'backgroundPosition' => 'center', 'backgroundSize' => 'cover',
                ],
                'readonly' => false,
                'description' => 'Sekcja parallax',
            ],
            'pd-parallax__overlay' => [
                'controls' => ['color-picker', 'position'],
                'defaults' => ['position' => 'absolute', 'inset' => '0', 'backgroundColor' => 'rgba(0, 0, 0, 0.5)'],
                'readonly' => false,
                'description' => 'Nakladka parallax',
            ],
            'pd-parallax__content' => [
                'controls' => ['position', 'color-picker', 'typography', 'box-model'],
                'defaults' => [
                    'position' => 'relative', 'zIndex' => '10', 'color' => '#ffffff', 'textAlign' => 'center', 'padding' => '4rem 2rem',
                ],
                'readonly' => false,
                'description' => 'Tresc parallax',
            ],
            'pd-pseudo-parallax' => [
                'controls' => ['parallax-settings', 'background', 'size'],
                'defaults' => ['minHeight' => '400px', 'backgroundAttachment' => 'fixed', 'backgroundPosition' => 'center', 'backgroundSize' => 'cover'],
                'readonly' => false,
                'description' => 'Pseudo-parallax CSS',
            ],
        ];
    }

    /**
     * Features list mappings.
     */
    private static function featuresMappings(): array
    {
        return [
            'pd-features' => [
                'controls' => ['box-model'],
                'defaults' => ['listStyle' => 'none', 'margin' => '0', 'padding' => '0'],
                'readonly' => false,
                'description' => 'Lista cech',
            ],
            'pd-feature' => [
                'controls' => ['layout-flex', 'box-model'],
                'defaults' => ['display' => 'flex', 'alignItems' => 'flex-start', 'gap' => '0.75rem', 'padding' => '0.5rem 0'],
                'readonly' => false,
                'description' => 'Pojedyncza cecha',
            ],
            'pd-feature__icon' => [
                'controls' => ['color-picker', 'layout-flex', 'box-model'],
                'defaults' => ['color' => '#10b981', 'flexShrink' => '0', 'marginTop' => '0.25rem'],
                'readonly' => false,
                'description' => 'Ikona cechy',
            ],
            'pd-feature__text' => [
                'controls' => ['typography', 'color-picker'],
                'defaults' => ['color' => '#333333', 'lineHeight' => '1.5'],
                'readonly' => false,
                'description' => 'Tekst cechy',
            ],
        ];
    }

    /**
     * Text alignment mappings.
     */
    private static function textMappings(): array
    {
        return [
            'text-center' => [
                'controls' => ['typography'],
                'defaults' => ['textAlign' => 'center'],
                'readonly' => true,
                'description' => 'Tekst wysrodkowany',
            ],
            'text-left' => [
                'controls' => ['typography'],
                'defaults' => ['textAlign' => 'left'],
                'readonly' => true,
                'description' => 'Tekst do lewej',
            ],
            'text-right' => [
                'controls' => ['typography'],
                'defaults' => ['textAlign' => 'right'],
                'readonly' => true,
                'description' => 'Tekst do prawej',
            ],
        ];
    }

    /**
     * Icon mappings.
     */
    private static function iconMappings(): array
    {
        return [
            'pd-icon' => [
                'controls' => ['layout-flex', 'typography'],
                'defaults' => ['display' => 'inline-flex', 'alignItems' => 'center', 'justifyContent' => 'center'],
                'readonly' => true,
                'description' => 'Kontener ikony',
            ],
        ];
    }
}
