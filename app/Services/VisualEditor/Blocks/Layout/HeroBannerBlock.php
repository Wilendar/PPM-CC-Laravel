<?php

namespace App\Services\VisualEditor\Blocks\Layout;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Hero Banner Block - Full-width image/video banner with overlay text.
 *
 * Perfect for product headers, promotional banners, and landing sections.
 */
class HeroBannerBlock extends BaseBlock
{
    public string $type = 'hero-banner';
    public string $name = 'Hero Banner';
    public string $icon = 'heroicons-rectangle-stack';
    public string $category = 'layout';

    public array $defaultSettings = [
        'height' => '400px',
        'min_height' => '300px',
        'overlay' => true,
        'overlay_color' => '#000000',
        'overlay_opacity' => 0.4,
        'text_position' => 'center',
        'text_color' => '#ffffff',
        'content_width' => '100%',
        'parallax' => false,
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for HeroBannerBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['parallax-settings', 'background', 'box-model', 'size'],
        '.pd-baner__image img' => ['image-settings', 'border', 'effects'],
        '.pd-baner__overlay' => ['background', 'effects'],
        '.pd-baner__content' => ['layout-flex', 'box-model'],
        '.pd-baner__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-baner__subtitle' => ['typography', 'color-picker', 'box-model'],
        '.pd-baner__btn' => ['button-settings', 'typography', 'color-picker', 'border'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $image = $this->escape($content['image'] ?? '');
        $title = $this->escape($content['title'] ?? '');
        $subtitle = $this->escape($content['subtitle'] ?? '');
        $buttonText = $this->escape($content['button_text'] ?? '');
        $buttonLink = $this->escape($content['button_link'] ?? '#');

        // Build overlay style
        $overlayStyle = '';
        if ($settings['overlay']) {
            $opacity = $settings['overlay_opacity'];
            $color = $settings['overlay_color'];
            // Convert hex to rgba
            $rgb = sscanf($color, "#%02x%02x%02x");
            $overlayStyle = "background: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, {$opacity});";
        }

        // Container styles
        $containerStyle = $this->inlineStyles([
            'height' => $settings['height'],
            'min-height' => $settings['min_height'],
        ]);

        // Text position classes
        $textClasses = $this->classNames([
            'pd-baner__content',
            "pd-baner__content--{$settings['text_position']}",
        ]);

        // Parallax class
        $parallaxClass = $settings['parallax'] ? 'pd-baner--parallax' : '';

        // Button HTML
        $buttonHtml = '';
        if ($buttonText) {
            $buttonHtml = "<a href=\"{$buttonLink}\" class=\"pd-baner__btn\">{$buttonText}</a>";
        }

        return <<<HTML
        <div class="pd-block pd-baner {$parallaxClass}" style="{$containerStyle}">
            <picture class="pd-baner__image">
                <img src="{$image}" alt="{$title}" class="obj-cover" loading="lazy" />
            </picture>
            <div class="pd-baner__overlay" style="{$overlayStyle}">
                <div class="{$textClasses}" style="color: {$settings['text_color']}; max-width: {$settings['content_width']};">
                    <h2 class="pd-baner__title">{$title}</h2>
                    <p class="pd-baner__subtitle">{$subtitle}</p>
                    {$buttonHtml}
                </div>
            </div>
        </div>
        HTML;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'image' => [
                    'type' => 'image',
                    'label' => 'Obraz tla',
                    'required' => true,
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul',
                    'required' => false,
                ],
                'subtitle' => [
                    'type' => 'text',
                    'label' => 'Podtytul',
                    'required' => false,
                ],
                'button_text' => [
                    'type' => 'text',
                    'label' => 'Tekst przycisku',
                    'required' => false,
                ],
                'button_link' => [
                    'type' => 'url',
                    'label' => 'Link przycisku',
                    'required' => false,
                ],
            ],
            'settings' => [
                'height' => [
                    'type' => 'text',
                    'label' => 'Wysokosc',
                    'default' => '400px',
                ],
                'min_height' => [
                    'type' => 'text',
                    'label' => 'Minimalna wysokosc',
                    'default' => '300px',
                ],
                'overlay' => [
                    'type' => 'boolean',
                    'label' => 'Nakladka',
                    'default' => true,
                ],
                'overlay_color' => [
                    'type' => 'color',
                    'label' => 'Kolor nakladki',
                    'default' => '#000000',
                ],
                'overlay_opacity' => [
                    'type' => 'range',
                    'label' => 'Przezroczystosc nakladki',
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.1,
                    'default' => 0.4,
                ],
                'text_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja tekstu',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowany',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'center',
                ],
                'text_color' => [
                    'type' => 'color',
                    'label' => 'Kolor tekstu',
                    'default' => '#ffffff',
                ],
                'parallax' => [
                    'type' => 'boolean',
                    'label' => 'Efekt parallax',
                    'default' => false,
                ],
            ],
        ];
    }
}
