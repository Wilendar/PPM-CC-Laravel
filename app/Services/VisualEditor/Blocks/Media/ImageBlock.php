<?php

namespace App\Services\VisualEditor\Blocks\Media;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Image Block - Single image with caption and link.
 *
 * Supports responsive srcset and lazy loading.
 */
class ImageBlock extends BaseBlock
{
    public string $type = 'image';
    public string $name = 'Obraz';
    public string $icon = 'heroicons-photo';
    public string $category = 'media';

    /**
     * ETAP_07h PP.3: Property Panel controls for ImageBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'background', 'size'],
        '.pd-image__wrapper' => ['border', 'effects'],
        '.pd-image__wrapper img' => ['image-settings', 'border', 'effects'],
        '.pd-image__caption' => ['typography', 'color-picker', 'box-model'],
    ];

    public array $defaultSettings = [
        'size' => 'full', // full, large, medium, small, custom
        'custom_width' => '100%',
        'custom_height' => 'auto',
        'alignment' => 'center',
        'border_radius' => '0',
        'shadow' => false,
        'lightbox' => false,
        'lazy_load' => true,
        'object_fit' => 'contain', // contain, cover, fill
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $src = $this->escape($content['src'] ?? '');
        $alt = $this->escape($content['alt'] ?? '');
        $caption = $this->escape($content['caption'] ?? '');
        $link = $this->escape($content['link'] ?? '');

        if (!$src) {
            return '<!-- Image block: no source provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-image',
            "pd-image--{$settings['size']}",
            "pd-image--{$settings['alignment']}",
            $settings['shadow'] ? 'pd-image--shadow' : '',
            $settings['lightbox'] ? 'pd-image--lightbox' : '',
        ]);

        // Image styles
        $imageStyle = $this->buildImageStyles($settings);

        // Lazy loading attribute
        $loadingAttr = $settings['lazy_load'] ? 'loading="lazy"' : '';

        // Build image HTML
        $imageHtml = "<img src=\"{$src}\" alt=\"{$alt}\" style=\"{$imageStyle}\" {$loadingAttr} />";

        // Wrap in link if provided
        if ($link) {
            $linkTarget = $settings['lightbox'] ? 'data-lightbox="true"' : '';
            $imageHtml = "<a href=\"{$link}\" {$linkTarget}>{$imageHtml}</a>";
        }

        // Caption HTML
        $captionHtml = '';
        if ($caption) {
            $captionHtml = "<figcaption class=\"pd-image__caption\">{$caption}</figcaption>";
        }

        return <<<HTML
        <figure class="{$containerClasses}">
            <div class="pd-image__wrapper">
                {$imageHtml}
            </div>
            {$captionHtml}
        </figure>
        HTML;
    }

    /**
     * Build image inline styles.
     */
    private function buildImageStyles(array $settings): string
    {
        $styles = [
            'border-radius' => $settings['border_radius'],
            'object-fit' => $settings['object_fit'],
        ];

        if ($settings['size'] === 'custom') {
            $styles['width'] = $settings['custom_width'];
            $styles['height'] = $settings['custom_height'];
        }

        return $this->inlineStyles($styles);
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'src' => [
                    'type' => 'image',
                    'label' => 'Obraz',
                    'required' => true,
                ],
                'alt' => [
                    'type' => 'text',
                    'label' => 'Tekst alternatywny (ALT)',
                    'required' => false,
                ],
                'caption' => [
                    'type' => 'text',
                    'label' => 'Podpis',
                    'required' => false,
                ],
                'link' => [
                    'type' => 'url',
                    'label' => 'Link',
                    'required' => false,
                ],
            ],
            'settings' => [
                'size' => [
                    'type' => 'select',
                    'label' => 'Rozmiar',
                    'options' => [
                        'full' => 'Pelny',
                        'large' => 'Duzy',
                        'medium' => 'Sredni',
                        'small' => 'Maly',
                        'custom' => 'Wlasny',
                    ],
                    'default' => 'full',
                ],
                'custom_width' => [
                    'type' => 'text',
                    'label' => 'Szerokosc',
                    'default' => '100%',
                    'condition' => ['size' => 'custom'],
                ],
                'custom_height' => [
                    'type' => 'text',
                    'label' => 'Wysokosc',
                    'default' => 'auto',
                    'condition' => ['size' => 'custom'],
                ],
                'alignment' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowane',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'center',
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => '0',
                ],
                'shadow' => [
                    'type' => 'boolean',
                    'label' => 'Cien',
                    'default' => false,
                ],
                'lightbox' => [
                    'type' => 'boolean',
                    'label' => 'Lightbox (powiekszenie)',
                    'default' => false,
                ],
                'lazy_load' => [
                    'type' => 'boolean',
                    'label' => 'Leniwe ladowanie',
                    'default' => true,
                ],
                'object_fit' => [
                    'type' => 'select',
                    'label' => 'Dopasowanie',
                    'options' => [
                        'contain' => 'Zawiera',
                        'cover' => 'Pokrywa',
                        'fill' => 'Wypelnia',
                    ],
                    'default' => 'contain',
                ],
            ],
        ];
    }
}
