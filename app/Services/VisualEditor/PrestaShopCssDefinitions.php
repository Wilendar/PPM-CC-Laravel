<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * PrestaShop CSS Class Definitions.
 *
 * Contains style definitions for all pd-* PrestaShop CSS classes.
 * These definitions mirror the original PrestaShop theme CSS.
 * Used by CssClassStyleResolver to auto-apply styles when classes are assigned.
 */
class PrestaShopCssDefinitions
{
    /**
     * CSS class to style mapping.
     * Keys are class names, values are arrays of camelCase style properties.
     */
    public const DEFINITIONS = [
        // ==============================================
        // BASE LAYOUT CLASSES
        // ==============================================
        'pd-base-grid' => [
            'display' => 'grid',
            'gridTemplateColumns' => 'repeat(6, 1fr)',
            'gap' => '1rem',
        ],
        'grid-row' => [
            'gridColumn' => '1 / -1',
        ],

        // ==============================================
        // PD-INTRO SECTION
        // ==============================================
        'pd-intro' => [
            // Container styles (inherits pd-base-grid)
        ],
        // pd-intro__heading: Grid layout with ::before orange bar
        // Row1=[Name full width], Row2=[orange bar | Type]
        'pd-intro__heading' => [
            'display' => 'grid',
            'gridTemplateColumns' => '160px auto',
            'gridTemplateRows' => 'auto auto',
            'gap' => '0 16px',
            'fontSize' => 'clamp(2rem, 5vw, 3.5rem)',
            'lineHeight' => '1',
            'fontWeight' => '800',
            'margin' => '0 0 1.5rem 0',
            'color' => '#000',
            // Note: ::before pseudo-element creates orange bar (handled in CSS)
        ],
        'pd-intro__text' => [
            'color' => '#333333',
            'lineHeight' => '1.6',
            'fontSize' => '1rem',
            'gridColumn' => 'span 3',
            'margin' => '0',
        ],

        // ==============================================
        // PD-MODEL (Product Title Components)
        // Used inside pd-intro__heading grid
        // ==============================================
        'pd-model' => [
            // Container for model type + name (alias for pd-intro__heading)
        ],
        'pd-model__type' => [
            'display' => 'block',
            'fontSize' => '0.7em',
            'fontWeight' => '400',
            'color' => '#000',
            'gridColumn' => '2 / -1',  // Row 2, next to orange bar
            'gridRow' => '2 / 3',
            'alignSelf' => 'center',
        ],
        'pd-model__name' => [
            'display' => 'block',
            'fontSize' => 'inherit',
            'fontWeight' => '800',
            'color' => '#000',
            'gridColumn' => '1 / -1',  // Row 1, full width
            'gridRow' => '1 / 2',
        ],

        // ==============================================
        // PD-COVER (Hero Image Section)
        // ==============================================
        'pd-cover' => [
            'gridColumn' => '1 / -1',
            'display' => 'flex',
            'justifyContent' => 'center',
        ],
        'pd-cover__picture' => [
            'background' => 'linear-gradient(#f6f6f6 70%, #ef8248 70%)',
            'display' => 'flex',
            'alignItems' => 'flex-end',
            'justifyContent' => 'center',
            'width' => '100%',
            'maxWidth' => '960px',
        ],

        // ==============================================
        // PD-ASSET-LIST (Key Parameters Bar)
        // Horizontal flex layout with centered items
        // On bg-brand: text must be BLACK (#000)
        // ==============================================
        'pd-asset-list' => [
            'display' => 'flex',
            'flexWrap' => 'wrap',
            'justifyContent' => 'center',
            'gap' => '0 3rem',
            'padding' => '1.5rem 1rem',
            'margin' => '0',
            'listStyle' => 'none',
        ],
        'pd-asset-list > li' => [
            'display' => 'flex',
            'flexDirection' => 'column',
            'alignItems' => 'center',
            'textAlign' => 'center',
            'fontSize' => '1.125rem',
            'minWidth' => '100px',
            'flex' => '0 0 auto',
        ],
        // Bold values (b, strong) appear FIRST due to order: -1
        'pd-asset-list > li b' => [
            'display' => 'block',
            'fontSize' => '2.25rem',
            'fontWeight' => '700',
            'order' => '-1',
        ],
        'pd-asset-list > li strong' => [
            'display' => 'block',
            'fontSize' => '2.25rem',
            'fontWeight' => '700',
            'order' => '-1',
        ],

        // ==============================================
        // BACKGROUND COLORS
        // ==============================================
        'bg-brand' => [
            'backgroundColor' => '#ef8248',
            'padding' => '1.5rem',
        ],
        'bg-neutral-accent' => [
            'backgroundColor' => '#f6f6f6',
        ],
        'bg-dark' => [
            'backgroundColor' => '#1a1a1a',
            'color' => '#ffffff',
        ],

        // ==============================================
        // PD-MERITS (Benefits Section)
        // FIX: Added horizontal padding (32px) and centered layout
        // ==============================================
        'pd-merits' => [
            'display' => 'grid',
            'gridTemplateColumns' => 'repeat(auto-fit, minmax(280px, 1fr))',
            'gap' => '1.5rem',
            'padding' => '2rem 32px',
            'maxWidth' => '1300px',
            'margin' => '0 auto',
        ],
        'pd-merit' => [
            'display' => 'flex',
            'flexDirection' => 'column',
            'alignItems' => 'center',
            'textAlign' => 'center',
            'padding' => '1.5rem',
        ],
        'pd-merit__icon' => [
            'fontSize' => '2.5rem',
            'marginBottom' => '1rem',
            'color' => '#ef8248',
        ],
        'pd-merit__title' => [
            'fontSize' => '1.125rem',
            'fontWeight' => '700',
            'marginBottom' => '0.5rem',
            'color' => '#1a1a1a',
        ],
        'pd-merit__text' => [
            'fontSize' => '0.875rem',
            'color' => '#666666',
            'lineHeight' => '1.5',
        ],

        // ==============================================
        // PD-SPECIFICATION (Specs Table)
        // ==============================================
        'pd-specification' => [
            'width' => '100%',
            'borderCollapse' => 'collapse',
        ],
        'pd-specification__row' => [
            'borderBottom' => '1px solid #e5e5e5',
        ],
        'pd-specification__label' => [
            'padding' => '0.75rem 1rem',
            'fontWeight' => '600',
            'color' => '#1a1a1a',
            'width' => '40%',
        ],
        'pd-specification__value' => [
            'padding' => '0.75rem 1rem',
            'color' => '#333333',
        ],

        // ==============================================
        // PD-FEATURES (Checkmark List)
        // ==============================================
        'pd-features' => [
            'listStyle' => 'none',
            'margin' => '0',
            'padding' => '0',
        ],
        'pd-feature' => [
            'display' => 'flex',
            'alignItems' => 'flex-start',
            'gap' => '0.75rem',
            'padding' => '0.5rem 0',
        ],
        'pd-feature__icon' => [
            'color' => '#10b981',
            'flexShrink' => '0',
            'marginTop' => '0.25rem',
        ],
        'pd-feature__text' => [
            'color' => '#333333',
            'lineHeight' => '1.5',
        ],

        // ==============================================
        // PD-SLIDER (Image Carousel)
        // ==============================================
        'pd-slider' => [
            'position' => 'relative',
            'overflow' => 'hidden',
        ],
        'pd-slider__track' => [
            'display' => 'flex',
            'transition' => 'transform 0.3s ease',
        ],
        'pd-slider__slide' => [
            'flexShrink' => '0',
            'width' => '100%',
        ],
        'pd-slider__nav' => [
            'position' => 'absolute',
            'top' => '50%',
            'transform' => 'translateY(-50%)',
            'backgroundColor' => 'rgba(0, 0, 0, 0.5)',
            'color' => '#ffffff',
            'padding' => '0.5rem',
            'borderRadius' => '50%',
        ],

        // ==============================================
        // PD-PARALLAX (Fullwidth with Effect)
        // ==============================================
        'pd-parallax' => [
            'position' => 'relative',
            'minHeight' => '400px',
            'backgroundAttachment' => 'fixed',
            'backgroundPosition' => 'center',
            'backgroundSize' => 'cover',
        ],
        'pd-parallax__overlay' => [
            'position' => 'absolute',
            'inset' => '0',
            'backgroundColor' => 'rgba(0, 0, 0, 0.5)',
        ],
        'pd-parallax__content' => [
            'position' => 'relative',
            'zIndex' => '10',
            'color' => '#ffffff',
            'textAlign' => 'center',
            'padding' => '4rem 2rem',
        ],

        // ==============================================
        // TEXT ALIGNMENT
        // ==============================================
        'text-center' => [
            'textAlign' => 'center',
        ],
        'text-left' => [
            'textAlign' => 'left',
        ],
        'text-right' => [
            'textAlign' => 'right',
        ],

        // ==============================================
        // ICONS (pd-icon--)
        // ==============================================
        'pd-icon' => [
            'display' => 'inline-flex',
            'alignItems' => 'center',
            'justifyContent' => 'center',
        ],
    ];

    /**
     * Get style definition for a single class.
     */
    public static function getClassStyles(string $className): array
    {
        return self::DEFINITIONS[$className] ?? [];
    }

    /**
     * Get merged styles for multiple classes.
     * Later classes override earlier ones (CSS cascade behavior).
     */
    public static function getMergedStyles(array $classNames): array
    {
        $mergedStyles = [];

        foreach ($classNames as $className) {
            $classStyles = self::getClassStyles($className);
            $mergedStyles = array_merge($mergedStyles, $classStyles);
        }

        return $mergedStyles;
    }

    /**
     * Check if a class has style definitions.
     */
    public static function hasDefinition(string $className): bool
    {
        return isset(self::DEFINITIONS[$className]);
    }

    /**
     * Get all available class names.
     */
    public static function getAllClassNames(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    /**
     * Get classes grouped by category for UI display.
     */
    public static function getGroupedClasses(): array
    {
        return [
            'Layout' => ['pd-base-grid', 'grid-row'],
            'Intro' => ['pd-intro', 'pd-intro__heading', 'pd-intro__text'],
            'Model' => ['pd-model', 'pd-model__type', 'pd-model__name'],
            'Cover' => ['pd-cover', 'pd-cover__picture'],
            'Assets' => ['pd-asset-list'],
            'Background' => ['bg-brand', 'bg-neutral-accent', 'bg-dark'],
            'Merits' => ['pd-merits', 'pd-merit', 'pd-merit__icon', 'pd-merit__title', 'pd-merit__text'],
            'Specification' => ['pd-specification', 'pd-specification__row', 'pd-specification__label', 'pd-specification__value'],
            'Features' => ['pd-features', 'pd-feature', 'pd-feature__icon', 'pd-feature__text'],
            'Slider' => ['pd-slider', 'pd-slider__track', 'pd-slider__slide', 'pd-slider__nav'],
            'Parallax' => ['pd-parallax', 'pd-parallax__overlay', 'pd-parallax__content'],
            'Text' => ['text-center', 'text-left', 'text-right'],
        ];
    }
}
