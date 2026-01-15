<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Section Block - Renders HTML EXACTLY as imported from PrestaShop.
 *
 * This block is a "passthrough" - it stores and renders original HTML
 * WITHOUT any modifications, wrappers, or inline styles.
 *
 * Supported section types (detected by CSS classes):
 * - intro: pd-base-grid pd-intro
 * - parallax: pd-pseudo-parallax
 * - slider: pd-slider splide
 * - merits: pd-merits
 * - specification: pd-specification
 * - more-links: pd-more-links
 * - block: pd-block (generic)
 * - footer: footer with pd-base-grid
 */
class PrestashopSectionBlock extends BaseBlock
{
    public string $type = 'prestashop-section';
    public string $name = 'Sekcja PrestaShop';
    public string $icon = 'heroicons-globe-alt';
    public string $category = 'prestashop';
    public string $description = 'Oryginalny HTML z PrestaShop (passthrough)';

    /**
     * ETAP_07h PP.3: Property Panel controls for PrestashopSectionBlock.
     *
     * DYNAMICZNE KONTROLKI - mapowanie na podstawie section_type!
     * Kontrolki MUSZA odpowiadac selektorom CSS w oryginalnym HTML.
     *
     * @see getPropertyPanelControlsForType() dla dynamicznych kontrolek
     */
    public array $propertyPanelControls = [
        // Domyslne kontrolki - nadpisywane przez getPropertyPanelControlsForType()
        'root' => ['box-model', 'size'],
    ];

    /**
     * ETAP_07h PP.3: Dynamiczne kontrolki Property Panel dla roznych typow sekcji.
     *
     * ZASADA: Kontrolki MUSZA odpowiadac selektorom CSS w HTML!
     * - Typography TYLKO na elementach tekstowych (h1-h6, p, span)
     * - Background z image picker na parallax/cover
     * - List-settings na kontenerach list
     *
     * @param string $sectionType Typ sekcji (cover, parallax, merits, etc.)
     * @return array Mapowanie selektor → kontrolki
     */
    public function getPropertyPanelControlsForType(string $sectionType): array
    {
        $controls = [
            'root' => ['box-model', 'size'],
        ];

        switch ($sectionType) {
            case 'cover':
            case 'parallax':
                // Parallax/Cover - glownie background-image
                $controls['.pd-pseudo-parallax'] = ['image-settings', 'background', 'parallax-settings', 'size'];
                $controls['.pd-pseudo-parallax__overlay'] = ['background', 'effects'];
                $controls['.pd-pseudo-parallax__title'] = ['typography', 'color-picker', 'box-model'];
                $controls['.pd-pseudo-parallax__subtitle'] = ['typography', 'color-picker'];
                $controls['.pd-pseudo-parallax__btn'] = ['button-settings', 'typography', 'color-picker', 'border'];
                break;

            case 'intro':
                // Intro - naglowek produktu
                $controls['.pd-intro'] = ['layout-flex', 'box-model', 'background', 'image-settings'];
                $controls['.pd-intro__title, .pd-product-name'] = ['typography', 'color-picker'];
                $controls['.pd-intro__subtitle'] = ['typography', 'color-picker'];
                $controls['.pd-intro__badge'] = ['typography', 'color-picker', 'background', 'border'];
                $controls['.pd-intro__image, .pd-intro img'] = ['image-settings', 'border', 'effects'];
                break;

            case 'merits':
                // Lista zalet - grid/flex + items
                $controls['.pd-merits'] = ['list-settings', 'layout-grid', 'layout-flex', 'box-model'];
                $controls['.pd-merit'] = ['box-model', 'background', 'border'];
                $controls['.pd-merit .pd-icon'] = ['color-picker', 'size'];
                $controls['.pd-merit h4'] = ['typography', 'color-picker'];
                $controls['.pd-merit p'] = ['typography', 'color-picker'];
                break;

            case 'specification':
                // Tabela specyfikacji
                $controls['.pd-specification'] = ['box-model', 'background'];
                $controls['.pd-specification__title'] = ['typography', 'color-picker', 'box-model'];
                $controls['.pd-specification__table'] = ['border', 'background'];
                $controls['.pd-specification__label'] = ['typography', 'color-picker', 'background'];
                $controls['.pd-specification__value'] = ['typography', 'color-picker'];
                $controls['.pd-specification tr'] = ['background'];
                break;

            case 'asset-list':
                // Lista parametrow (key values)
                $controls['.pd-asset-list'] = ['list-settings', 'layout-flex', 'layout-grid', 'box-model'];
                $controls['.pd-asset'] = ['box-model', 'background', 'border'];
                $controls['.pd-asset__icon'] = ['color-picker', 'size'];
                $controls['.pd-asset__value'] = ['typography', 'color-picker'];
                $controls['.pd-asset__unit'] = ['typography', 'color-picker'];
                $controls['.pd-asset__label'] = ['typography', 'color-picker'];
                break;

            case 'slider':
                // Slider Splide
                $controls['.pd-slider'] = ['slider-settings', 'box-model', 'background'];
                $controls['.splide__slide'] = ['box-model', 'background'];
                $controls['.pd-slider__image'] = ['image-settings', 'border', 'effects'];
                $controls['.pd-slider__title'] = ['typography', 'color-picker'];
                $controls['.pd-slider__text'] = ['typography', 'color-picker'];
                $controls['.splide__arrow'] = ['color-picker', 'background', 'border'];
                $controls['.splide__pagination__page'] = ['color-picker', 'size'];
                break;

            case 'more-links':
                // Linki
                $controls['.pd-more-links'] = ['layout-flex', 'box-model'];
                $controls['.pd-more-links a'] = ['typography', 'color-picker', 'border'];
                break;

            case 'where-2-ride':
                // Gdzie jezdzic
                $controls['.pd-where-2-ride'] = ['layout-grid', 'box-model', 'background'];
                $controls['.pd-where-2-ride__item'] = ['box-model', 'background', 'border'];
                $controls['.pd-where-2-ride__title'] = ['typography', 'color-picker'];
                $controls['.pd-where-2-ride__text'] = ['typography', 'color-picker'];
                break;

            case 'footer':
                // Stopka
                $controls['footer'] = ['box-model', 'background'];
                $controls['footer a'] = ['typography', 'color-picker'];
                break;

            case 'base-grid':
                // PP.3.1: pd-base-grid - layout container
                $controls['.pd-base-grid'] = ['layout-grid', 'background', 'box-model', 'size'];
                $controls['.pd-base-grid > *'] = ['box-model'];
                break;

            case 'block-row':
                // PP.3.8: pd-block-row - flex layout container
                $controls['.pd-block-row'] = ['layout-flex', 'background', 'border', 'box-model'];
                $controls['.pd-block-row > *'] = ['box-model', 'size'];
                break;

            default:
                // Blok ogolny - podstawowe kontrolki
                $controls['root'] = ['box-model', 'background', 'border'];
                break;
        }

        return $controls;
    }

    public array $defaultSettings = [
        'section_type' => 'block',
        'original_tag' => 'div',
        'original_classes' => '',
        'editable' => true,
    ];

    /**
     * Section type labels for UI.
     */
    private array $sectionLabels = [
        'intro' => 'Intro (nagłówek produktu)',
        'parallax' => 'Parallax (obraz)',
        'slider' => 'Slider (karuzela)',
        'merits' => 'Zalety (lista)',
        'specification' => 'Specyfikacja',
        'more-links' => 'Linki',
        'footer' => 'Stopka',
        'block' => 'Blok ogólny',
        'cover' => 'Cover (zdjęcie główne)',
        'asset-list' => 'Lista cech',
        'where-2-ride' => 'Gdzie jeździć',
        // PP.3: New section types
        'base-grid' => 'Grid bazowy (layout)',
        'block-row' => 'Wiersz bloku (flex)',
    ];

    /**
     * Render block - outputs HTML EXACTLY as stored, no modifications.
     *
     * CRITICAL: This method must NOT add any wrappers, classes, or inline styles!
     * The HTML must be 1:1 identical to what was imported from PrestaShop.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        // PASSTHROUGH MODE: Return HTML exactly as stored
        return $content['html'] ?? '';
    }

    /**
     * Get schema for block editor UI.
     */
    public function getSchema(): array
    {
        return [
            'content' => [
                'html' => [
                    'type' => 'code',
                    'label' => 'Kod HTML',
                    'language' => 'html',
                    'required' => true,
                    'rows' => 20,
                    'help' => 'Oryginalny HTML z PrestaShop - edytuj ostrożnie',
                ],
            ],
            'settings' => [
                'section_type' => [
                    'type' => 'select',
                    'label' => 'Typ sekcji',
                    'options' => $this->sectionLabels,
                    'default' => 'block',
                    'readonly' => true,
                ],
                'original_tag' => [
                    'type' => 'text',
                    'label' => 'Oryginalny tag HTML',
                    'default' => 'div',
                    'readonly' => true,
                ],
                'original_classes' => [
                    'type' => 'text',
                    'label' => 'Oryginalne klasy CSS',
                    'default' => '',
                    'readonly' => true,
                ],
            ],
        ];
    }

    /**
     * Get preview for block palette.
     */
    public function getPreview(): string
    {
        return <<<HTML
        <div class="block-preview block-preview--prestashop">
            <span class="block-preview__icon">🛒</span>
            <span class="block-preview__label">Sekcja PrestaShop</span>
        </div>
        HTML;
    }

    /**
     * Detect section type from CSS classes.
     */
    public static function detectSectionType(string $classes, string $tagName = 'div'): string
    {
        $classes = strtolower($classes);

        // Check specific patterns first
        if (str_contains($classes, 'pd-intro')) {
            return 'intro';
        }
        if (str_contains($classes, 'pd-pseudo-parallax')) {
            return 'parallax';
        }
        if (str_contains($classes, 'pd-slider') || str_contains($classes, 'splide')) {
            return 'slider';
        }
        if (str_contains($classes, 'pd-merits')) {
            return 'merits';
        }
        if (str_contains($classes, 'pd-specification')) {
            return 'specification';
        }
        if (str_contains($classes, 'pd-more-links')) {
            return 'more-links';
        }
        if (str_contains($classes, 'pd-cover')) {
            return 'cover';
        }
        if (str_contains($classes, 'pd-asset-list')) {
            return 'asset-list';
        }
        if (str_contains($classes, 'pd-where-2-ride')) {
            return 'where-2-ride';
        }
        // PP.3.1: pd-base-grid detection
        if (str_contains($classes, 'pd-base-grid') && !str_contains($classes, 'pd-intro')) {
            return 'base-grid';
        }
        // PP.3.8: pd-block-row detection
        if (str_contains($classes, 'pd-block-row')) {
            return 'block-row';
        }
        if ($tagName === 'footer') {
            return 'footer';
        }
        if ($tagName === 'aside') {
            return 'more-links';
        }

        return 'block';
    }
}
