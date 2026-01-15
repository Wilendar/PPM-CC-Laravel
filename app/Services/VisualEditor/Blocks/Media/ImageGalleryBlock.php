<?php

namespace App\Services\VisualEditor\Blocks\Media;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Image Gallery Block - Multiple images in grid layout.
 *
 * Supports lightbox and lazy loading.
 */
class ImageGalleryBlock extends BaseBlock
{
    public string $type = 'image-gallery';
    public string $name = 'Galeria obrazow';
    public string $icon = 'heroicons-squares-plus';
    public string $category = 'media';

    /**
     * ETAP_07h PP.3: Property Panel controls for ImageGalleryBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'background'],
        '.pd-gallery__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-gallery__grid' => ['layout-grid', 'box-model'],
        '.pd-gallery__item' => ['border', 'effects', 'box-model'],
        '.pd-gallery__image' => ['image-settings', 'effects'],
        '.pd-gallery__overlay' => ['background', 'effects'],
    ];

    public array $defaultSettings = [
        'columns' => 3,
        'columns_tablet' => 2,
        'columns_mobile' => 1,
        'gap' => '1rem',
        'aspect_ratio' => '1/1', // 1/1, 4/3, 16/9, auto
        'lightbox' => true,
        'lazy_load' => true,
        'border_radius' => 'var(--pd-border-radius)',
        'hover_effect' => 'zoom', // none, zoom, fade, overlay
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $images = $content['images'] ?? [];
        $title = $this->escape($content['title'] ?? '');

        if (empty($images)) {
            return '<!-- Image gallery: no images provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-gallery',
            $settings['lightbox'] ? 'pd-gallery--lightbox' : '',
            "pd-gallery--hover-{$settings['hover_effect']}",
        ]);

        // Grid styles
        $gridStyle = $this->inlineStyles([
            'display' => 'grid',
            'grid-template-columns' => "repeat({$settings['columns']}, 1fr)",
            'gap' => $settings['gap'],
        ]);

        // Title HTML
        $titleHtml = '';
        if ($title) {
            $titleHtml = "<h3 class=\"pd-gallery__title\">{$title}</h3>";
        }

        // Build gallery items
        $itemsHtml = $this->buildGalleryItems($images, $settings);

        return <<<HTML
        <div class="{$containerClasses}">
            {$titleHtml}
            <div class="pd-gallery__grid pd-gallery--cols-{$settings['columns']} pd-gallery--tablet-{$settings['columns_tablet']} pd-gallery--mobile-{$settings['columns_mobile']}" style="{$gridStyle}">
                {$itemsHtml}
            </div>
        </div>
        HTML;
    }

    /**
     * Build gallery items HTML.
     */
    private function buildGalleryItems(array $images, array $settings): string
    {
        $html = '';
        $loadingAttr = $settings['lazy_load'] ? 'loading="lazy"' : '';
        $aspectRatio = $settings['aspect_ratio'];
        $borderRadius = $settings['border_radius'];

        foreach ($images as $index => $image) {
            $src = $this->escape($image['src'] ?? '');
            $alt = $this->escape($image['alt'] ?? "Obraz " . ($index + 1));
            $caption = $this->escape($image['caption'] ?? '');

            if (!$src) {
                continue;
            }

            // Lightbox link
            $lightboxAttr = $settings['lightbox']
                ? "data-lightbox=\"gallery\" data-caption=\"{$caption}\""
                : '';

            // Aspect ratio style
            $itemStyle = $aspectRatio !== 'auto'
                ? "aspect-ratio: {$aspectRatio}; border-radius: {$borderRadius};"
                : "border-radius: {$borderRadius};";

            $html .= <<<HTML
            <a href="{$src}" class="pd-gallery__item" style="{$itemStyle}" {$lightboxAttr}>
                <img src="{$src}" alt="{$alt}" class="pd-gallery__image" {$loadingAttr} />
                <div class="pd-gallery__overlay">
                    <span class="pd-gallery__zoom-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="24" height="24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                        </svg>
                    </span>
                </div>
            </a>
            HTML;
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul galerii',
                    'required' => false,
                ],
                'images' => [
                    'type' => 'repeater',
                    'label' => 'Obrazy',
                    'fields' => [
                        'src' => [
                            'type' => 'image',
                            'label' => 'Obraz',
                        ],
                        'alt' => [
                            'type' => 'text',
                            'label' => 'Tekst alternatywny',
                        ],
                        'caption' => [
                            'type' => 'text',
                            'label' => 'Podpis',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'columns' => [
                    'type' => 'number',
                    'label' => 'Kolumny (desktop)',
                    'min' => 1,
                    'max' => 6,
                    'default' => 3,
                ],
                'columns_tablet' => [
                    'type' => 'number',
                    'label' => 'Kolumny (tablet)',
                    'min' => 1,
                    'max' => 4,
                    'default' => 2,
                ],
                'columns_mobile' => [
                    'type' => 'number',
                    'label' => 'Kolumny (mobile)',
                    'min' => 1,
                    'max' => 2,
                    'default' => 1,
                ],
                'gap' => [
                    'type' => 'text',
                    'label' => 'Odstep',
                    'default' => '1rem',
                ],
                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje',
                    'options' => [
                        '1/1' => 'Kwadrat (1:1)',
                        '4/3' => 'Standard (4:3)',
                        '16/9' => 'Szerokie (16:9)',
                        'auto' => 'Automatyczne',
                    ],
                    'default' => '1/1',
                ],
                'lightbox' => [
                    'type' => 'boolean',
                    'label' => 'Lightbox',
                    'default' => true,
                ],
                'lazy_load' => [
                    'type' => 'boolean',
                    'label' => 'Leniwe ladowanie',
                    'default' => true,
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
                'hover_effect' => [
                    'type' => 'select',
                    'label' => 'Efekt hover',
                    'options' => [
                        'none' => 'Brak',
                        'zoom' => 'Przyblizenie',
                        'fade' => 'Zanikanie',
                        'overlay' => 'Nakladka',
                    ],
                    'default' => 'zoom',
                ],
            ],
        ];
    }
}
