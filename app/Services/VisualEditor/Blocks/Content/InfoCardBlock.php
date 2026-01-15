<?php

namespace App\Services\VisualEditor\Blocks\Content;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Info Card Block - Image + Text combo with CTA.
 *
 * Perfect for product highlights, callouts, and promotional sections.
 */
class InfoCardBlock extends BaseBlock
{
    public string $type = 'info-card';
    public string $name = 'Karta informacyjna';
    public string $icon = 'heroicons-information-circle';
    public string $category = 'content';

    public array $defaultSettings = [
        'image_position' => 'left', // left, right, top, background
        'image_ratio' => '1/1',
        'style' => 'default', // default, highlighted, minimal
        'background' => '#ffffff',
        'accent_color' => 'var(--pd-primary-color)',
        'padding' => '2rem',
        'border_radius' => 'var(--pd-border-radius)',
        'shadow' => true,
        'show_cta' => true,
        'cta_style' => 'button', // button, link, arrow
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for InfoCardBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['background', 'border', 'box-model', 'effects'],
        '.pd-info-card__image' => ['image-settings', 'border'],
        '.pd-info-card__image img' => ['image-settings', 'effects'],
        '.pd-info-card__bg' => ['background', 'effects'],
        '.pd-info-card__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-info-card__subtitle' => ['typography', 'color-picker'],
        '.pd-info-card__text' => ['typography', 'color-picker'],
        '.pd-info-card__cta' => ['button-settings', 'typography', 'color-picker', 'border'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $image = $this->escape($content['image'] ?? '');
        $title = $this->escape($content['title'] ?? '');
        $subtitle = $this->escape($content['subtitle'] ?? '');
        $text = $content['text'] ?? ''; // May contain HTML
        $ctaText = $this->escape($content['cta_text'] ?? 'Wiecej');
        $ctaLink = $this->escape($content['cta_link'] ?? '#');

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-info-card',
            "pd-info-card--{$settings['image_position']}",
            "pd-info-card--{$settings['style']}",
            $settings['shadow'] ? 'pd-info-card--shadow' : '',
        ]);

        // Container styles
        $containerStyle = $this->inlineStyles([
            'background' => $settings['background'],
            'padding' => $settings['padding'],
            'border-radius' => $settings['border_radius'],
            '--accent-color' => $settings['accent_color'],
        ]);

        // Image HTML
        $imageHtml = $this->buildImageHtml($image, $settings);

        // CTA HTML
        $ctaHtml = '';
        if ($settings['show_cta'] && $ctaLink) {
            $ctaHtml = $this->buildCtaHtml($ctaText, $ctaLink, $settings);
        }

        // Subtitle HTML
        $subtitleHtml = '';
        if ($subtitle) {
            $subtitleHtml = "<p class=\"pd-info-card__subtitle\">{$subtitle}</p>";
        }

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}">
            {$imageHtml}
            <div class="pd-info-card__content">
                <h3 class="pd-info-card__title">{$title}</h3>
                {$subtitleHtml}
                <div class="pd-info-card__text">{$text}</div>
                {$ctaHtml}
            </div>
        </div>
        HTML;
    }

    /**
     * Build image HTML based on position.
     */
    private function buildImageHtml(string $image, array $settings): string
    {
        if (!$image) {
            return '';
        }

        $position = $settings['image_position'];

        if ($position === 'background') {
            return <<<HTML
            <div class="pd-info-card__bg" style="background-image: url('{$image}');"></div>
            HTML;
        }

        return <<<HTML
        <div class="pd-info-card__image" style="aspect-ratio: {$settings['image_ratio']};">
            <img src="{$image}" alt="" loading="lazy" />
        </div>
        HTML;
    }

    /**
     * Build CTA HTML based on style.
     */
    private function buildCtaHtml(string $text, string $link, array $settings): string
    {
        $style = $settings['cta_style'];

        if ($style === 'arrow') {
            return <<<HTML
            <a href="{$link}" class="pd-info-card__cta pd-info-card__cta--arrow">
                {$text}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
            HTML;
        }

        if ($style === 'link') {
            return "<a href=\"{$link}\" class=\"pd-info-card__cta pd-info-card__cta--link\">{$text}</a>";
        }

        return "<a href=\"{$link}\" class=\"pd-info-card__cta pd-info-card__cta--button\">{$text}</a>";
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
                'subtitle' => [
                    'type' => 'text',
                    'label' => 'Podtytul',
                    'required' => false,
                ],
                'text' => [
                    'type' => 'richtext',
                    'label' => 'Tresc',
                    'required' => false,
                ],
                'cta_text' => [
                    'type' => 'text',
                    'label' => 'Tekst przycisku',
                    'default' => 'Wiecej',
                ],
                'cta_link' => [
                    'type' => 'url',
                    'label' => 'Link przycisku',
                    'required' => false,
                ],
            ],
            'settings' => [
                'image_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja obrazu',
                    'options' => [
                        'left' => 'Po lewej',
                        'right' => 'Po prawej',
                        'top' => 'Na gorze',
                        'background' => 'W tle',
                    ],
                    'default' => 'left',
                ],
                'image_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje obrazu',
                    'options' => [
                        '1/1' => 'Kwadrat (1:1)',
                        '4/3' => 'Standard (4:3)',
                        '16/9' => 'Szerokie (16:9)',
                    ],
                    'default' => '1/1',
                ],
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl',
                    'options' => [
                        'default' => 'Domyslny',
                        'highlighted' => 'Wyroznienie',
                        'minimal' => 'Minimalny',
                    ],
                    'default' => 'default',
                ],
                'background' => [
                    'type' => 'color',
                    'label' => 'Tlo',
                    'default' => '#ffffff',
                ],
                'accent_color' => [
                    'type' => 'color',
                    'label' => 'Kolor akcentu',
                    'default' => 'var(--pd-primary-color)',
                ],
                'padding' => [
                    'type' => 'text',
                    'label' => 'Padding',
                    'default' => '2rem',
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
                'shadow' => [
                    'type' => 'boolean',
                    'label' => 'Cien',
                    'default' => true,
                ],
                'show_cta' => [
                    'type' => 'boolean',
                    'label' => 'Pokaz przycisk',
                    'default' => true,
                ],
                'cta_style' => [
                    'type' => 'select',
                    'label' => 'Styl przycisku',
                    'options' => [
                        'button' => 'Przycisk',
                        'link' => 'Link',
                        'arrow' => 'Ze strzalka',
                    ],
                    'default' => 'button',
                    'condition' => ['show_cta' => true],
                ],
            ],
        ];
    }
}
