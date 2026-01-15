<?php

namespace App\Services\VisualEditor\Blocks\Media;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Picture Element Block - Responsive picture with multiple sources.
 *
 * Supports art direction and format switching (WebP).
 */
class PictureElementBlock extends BaseBlock
{
    public string $type = 'picture-element';
    public string $name = 'Obraz responsywny (picture)';
    public string $icon = 'heroicons-device-phone-mobile';
    public string $category = 'media';

    /**
     * ETAP_07h PP.3: Property Panel controls for PictureElementBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'size'],
        '.pd-picture__picture' => ['border', 'effects'],
        '.pd-picture__img' => ['image-settings', 'border', 'effects'],
        '.pd-picture__caption' => ['typography', 'color-picker', 'box-model'],
    ];

    public array $defaultSettings = [
        'lazy_load' => true,
        'object_fit' => 'cover',
        'aspect_ratio' => 'auto',
        'border_radius' => '0',
        'alignment' => 'center',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $fallbackSrc = $this->escape($content['fallback'] ?? '');
        $alt = $this->escape($content['alt'] ?? '');
        $sources = $content['sources'] ?? [];
        $caption = $this->escape($content['caption'] ?? '');

        if (!$fallbackSrc) {
            return '<!-- Picture element: no fallback image provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-picture',
            "pd-picture--{$settings['alignment']}",
        ]);

        // Image styles
        $imgStyle = $this->inlineStyles([
            'object-fit' => $settings['object_fit'],
            'aspect-ratio' => $settings['aspect_ratio'] !== 'auto' ? $settings['aspect_ratio'] : null,
            'border-radius' => $settings['border_radius'],
        ]);

        // Loading attribute
        $loadingAttr = $settings['lazy_load'] ? 'loading="lazy"' : '';

        // Build source elements
        $sourcesHtml = $this->buildSourcesHtml($sources);

        // Caption HTML
        $captionHtml = '';
        if ($caption) {
            $captionHtml = "<figcaption class=\"pd-picture__caption\">{$caption}</figcaption>";
        }

        return <<<HTML
        <figure class="{$containerClasses}">
            <picture class="pd-picture__picture">
                {$sourcesHtml}
                <img src="{$fallbackSrc}" alt="{$alt}" class="pd-picture__img" style="{$imgStyle}" {$loadingAttr} />
            </picture>
            {$captionHtml}
        </figure>
        HTML;
    }

    /**
     * Build source elements for picture.
     */
    private function buildSourcesHtml(array $sources): string
    {
        $html = '';

        foreach ($sources as $source) {
            $srcset = $this->escape($source['srcset'] ?? '');
            $media = $this->escape($source['media'] ?? '');
            $type = $this->escape($source['type'] ?? '');

            if (!$srcset) {
                continue;
            }

            $attrs = ["srcset=\"{$srcset}\""];

            if ($media) {
                $attrs[] = "media=\"{$media}\"";
            }

            if ($type) {
                $attrs[] = "type=\"{$type}\"";
            }

            $attrString = implode(' ', $attrs);
            $html .= "<source {$attrString} />";
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'fallback' => [
                    'type' => 'image',
                    'label' => 'Obraz domyslny (fallback)',
                    'required' => true,
                ],
                'alt' => [
                    'type' => 'text',
                    'label' => 'Tekst alternatywny',
                    'required' => false,
                ],
                'caption' => [
                    'type' => 'text',
                    'label' => 'Podpis',
                    'required' => false,
                ],
                'sources' => [
                    'type' => 'repeater',
                    'label' => 'Zrodla obrazu',
                    'fields' => [
                        'srcset' => [
                            'type' => 'text',
                            'label' => 'Srcset',
                            'placeholder' => 'image-800.jpg 800w, image-1200.jpg 1200w',
                        ],
                        'media' => [
                            'type' => 'text',
                            'label' => 'Media query',
                            'placeholder' => '(min-width: 768px)',
                        ],
                        'type' => [
                            'type' => 'select',
                            'label' => 'Typ',
                            'options' => [
                                '' => 'Auto',
                                'image/webp' => 'WebP',
                                'image/avif' => 'AVIF',
                                'image/jpeg' => 'JPEG',
                                'image/png' => 'PNG',
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [
                'lazy_load' => [
                    'type' => 'boolean',
                    'label' => 'Leniwe ladowanie',
                    'default' => true,
                ],
                'object_fit' => [
                    'type' => 'select',
                    'label' => 'Dopasowanie',
                    'options' => [
                        'cover' => 'Pokrywa',
                        'contain' => 'Zawiera',
                        'fill' => 'Wypelnia',
                        'none' => 'Brak',
                    ],
                    'default' => 'cover',
                ],
                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje',
                    'options' => [
                        'auto' => 'Automatyczne',
                        '1/1' => 'Kwadrat (1:1)',
                        '4/3' => 'Standard (4:3)',
                        '16/9' => 'Szerokie (16:9)',
                        '21/9' => 'Ultra szerokie (21:9)',
                    ],
                    'default' => 'auto',
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => '0',
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
            ],
        ];
    }
}
