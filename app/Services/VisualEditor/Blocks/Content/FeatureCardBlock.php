<?php

namespace App\Services\VisualEditor\Blocks\Content;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Feature Card Block - Image/Icon + Title + Description card.
 *
 * Perfect for product features, services, or benefits presentation.
 */
class FeatureCardBlock extends BaseBlock
{
    public string $type = 'feature-card';
    public string $name = 'Karta cechy';
    public string $icon = 'heroicons-rectangle-group';
    public string $category = 'content';

    public array $defaultSettings = [
        'layout' => 'image-top', // image-top, image-left, image-right
        'media_type' => 'image', // image, icon
        'icon' => '',
        'icon_size' => '3rem',
        'icon_color' => 'var(--pd-primary-color)',
        'image_ratio' => '16/9',
        'card_style' => 'flat', // flat, bordered, shadow
        'background' => 'transparent',
        'padding' => '1.5rem',
        'border_radius' => 'var(--pd-border-radius)',
        'text_align' => 'left',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for FeatureCardBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['background', 'border', 'box-model', 'effects'],
        '.pd-feature-card__icon' => ['color-picker', 'size'],
        '.pd-feature-card__image' => ['image-settings', 'border'],
        '.pd-feature-card__image img' => ['image-settings', 'border', 'effects'],
        '.pd-feature-card__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-feature-card__description' => ['typography', 'color-picker', 'box-model'],
        '.pd-feature-card__link' => ['typography', 'color-picker'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $image = $this->escape($content['image'] ?? '');
        $title = $this->escape($content['title'] ?? '');
        $description = $content['description'] ?? ''; // May contain HTML
        $link = $this->escape($content['link'] ?? '');
        $linkText = $this->escape($content['link_text'] ?? 'Wiecej');

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-feature-card',
            "pd-feature-card--{$settings['layout']}",
            "pd-feature-card--{$settings['card_style']}",
        ]);

        // Container styles
        $containerStyle = $this->inlineStyles([
            'background' => $settings['background'],
            'padding' => $settings['padding'],
            'border-radius' => $settings['border_radius'],
            'text-align' => $settings['text_align'],
        ]);

        // Media HTML
        $mediaHtml = $this->buildMediaHtml($settings, $image);

        // Link HTML
        $linkHtml = '';
        if ($link) {
            $linkHtml = "<a href=\"{$link}\" class=\"pd-feature-card__link\">{$linkText}</a>";
        }

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}">
            {$mediaHtml}
            <div class="pd-feature-card__content">
                <h3 class="pd-feature-card__title">{$title}</h3>
                <div class="pd-feature-card__description">{$description}</div>
                {$linkHtml}
            </div>
        </div>
        HTML;
    }

    /**
     * Build media (image or icon) HTML.
     */
    private function buildMediaHtml(array $settings, string $image): string
    {
        if ($settings['media_type'] === 'icon' && $settings['icon']) {
            $iconStyle = $this->inlineStyles([
                'font-size' => $settings['icon_size'],
                'color' => $settings['icon_color'],
            ]);
            return "<div class=\"pd-feature-card__icon\" style=\"{$iconStyle}\">{$settings['icon']}</div>";
        }

        if ($image) {
            return <<<HTML
            <div class="pd-feature-card__image" style="aspect-ratio: {$settings['image_ratio']};">
                <img src="{$image}" alt="" loading="lazy" />
            </div>
            HTML;
        }

        return '';
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'image' => [
                    'type' => 'image',
                    'label' => 'Obraz',
                    'required' => false,
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul',
                    'required' => true,
                ],
                'description' => [
                    'type' => 'richtext',
                    'label' => 'Opis',
                    'required' => false,
                ],
                'link' => [
                    'type' => 'url',
                    'label' => 'Link',
                    'required' => false,
                ],
                'link_text' => [
                    'type' => 'text',
                    'label' => 'Tekst linku',
                    'default' => 'Wiecej',
                ],
            ],
            'settings' => [
                'layout' => [
                    'type' => 'select',
                    'label' => 'Uklad',
                    'options' => [
                        'image-top' => 'Obraz na gorze',
                        'image-left' => 'Obraz po lewej',
                        'image-right' => 'Obraz po prawej',
                    ],
                    'default' => 'image-top',
                ],
                'media_type' => [
                    'type' => 'select',
                    'label' => 'Typ mediow',
                    'options' => [
                        'image' => 'Obraz',
                        'icon' => 'Ikona',
                    ],
                    'default' => 'image',
                ],
                'icon' => [
                    'type' => 'icon',
                    'label' => 'Ikona',
                    'default' => '',
                    'condition' => ['media_type' => 'icon'],
                ],
                'icon_size' => [
                    'type' => 'text',
                    'label' => 'Rozmiar ikony',
                    'default' => '3rem',
                    'condition' => ['media_type' => 'icon'],
                ],
                'icon_color' => [
                    'type' => 'color',
                    'label' => 'Kolor ikony',
                    'default' => 'var(--pd-primary-color)',
                    'condition' => ['media_type' => 'icon'],
                ],
                'image_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje obrazu',
                    'options' => [
                        '1/1' => 'Kwadrat (1:1)',
                        '4/3' => 'Standard (4:3)',
                        '16/9' => 'Szerokie (16:9)',
                        '21/9' => 'Ultra szerokie (21:9)',
                    ],
                    'default' => '16/9',
                    'condition' => ['media_type' => 'image'],
                ],
                'card_style' => [
                    'type' => 'select',
                    'label' => 'Styl karty',
                    'options' => [
                        'flat' => 'Plaski',
                        'bordered' => 'Z obramowaniem',
                        'shadow' => 'Z cieniem',
                    ],
                    'default' => 'flat',
                ],
                'background' => [
                    'type' => 'color',
                    'label' => 'Tlo',
                    'default' => 'transparent',
                ],
                'padding' => [
                    'type' => 'text',
                    'label' => 'Padding',
                    'default' => '1.5rem',
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
                'text_align' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie tekstu',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowane',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'left',
                ],
            ],
        ];
    }
}
