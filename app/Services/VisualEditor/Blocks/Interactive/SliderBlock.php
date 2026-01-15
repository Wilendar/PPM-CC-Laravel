<?php

namespace App\Services\VisualEditor\Blocks\Interactive;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Slider Block - Splide.js based carousel.
 *
 * Supports images, nested blocks, and various navigation options.
 */
class SliderBlock extends BaseBlock
{
    public string $type = 'slider';
    public string $name = 'Slider/Karuzela';
    public string $icon = 'heroicons-rectangle-group';
    public string $category = 'interactive';
    public bool $supportsChildren = true;

    /**
     * ETAP_07h PP.3: Property Panel controls for SliderBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['slider-settings', 'box-model', 'background'],
        '.splide__slide' => ['box-model', 'background'],
        '.pd-slider__image' => ['image-settings', 'border', 'effects'],
        '.pd-slider__content' => ['box-model', 'background'],
        '.pd-slider__title' => ['typography', 'color-picker'],
        '.pd-slider__text' => ['typography', 'color-picker'],
        '.splide__arrow' => ['color-picker', 'background', 'border', 'size'],
        '.splide__pagination__page' => ['color-picker', 'size'],
    ];

    public array $defaultSettings = [
        'type' => 'slide', // slide, loop, fade
        'autoplay' => false,
        'autoplay_interval' => 5000,
        'pause_on_hover' => true,
        'arrows' => true,
        'pagination' => true,
        'per_page' => 1,
        'per_move' => 1,
        'gap' => '1rem',
        'padding' => '0',
        'speed' => 400,
        'rewind' => true,
        'drag' => true,
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $slides = $content['slides'] ?? [];

        // If we have children (nested blocks), use those
        if (!empty($children)) {
            $slides = $children;
        }

        if (empty($slides)) {
            return '<!-- Slider: no slides provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-slider',
            'splide',
            "pd-slider--{$settings['type']}",
        ]);

        // Splide options as JSON
        $splideOptions = json_encode([
            'type' => $settings['type'],
            'autoplay' => $settings['autoplay'],
            'interval' => $settings['autoplay_interval'],
            'pauseOnHover' => $settings['pause_on_hover'],
            'arrows' => $settings['arrows'],
            'pagination' => $settings['pagination'],
            'perPage' => $settings['per_page'],
            'perMove' => $settings['per_move'],
            'gap' => $settings['gap'],
            'padding' => $settings['padding'],
            'speed' => $settings['speed'],
            'rewind' => $settings['rewind'],
            'drag' => $settings['drag'],
        ]);

        // Build slides HTML
        $slidesHtml = $this->buildSlidesHtml($slides);

        return <<<HTML
        <div class="{$containerClasses}" data-splide='{$splideOptions}'>
            <div class="splide__track">
                <ul class="splide__list">
                    {$slidesHtml}
                </ul>
            </div>
        </div>
        HTML;
    }

    /**
     * Build slides HTML.
     */
    private function buildSlidesHtml(array $slides): string
    {
        $html = '';

        foreach ($slides as $index => $slide) {
            // If slide is string (pre-rendered HTML from children)
            if (is_string($slide)) {
                $html .= "<li class=\"splide__slide\">{$slide}</li>";
                continue;
            }

            // If slide is array with image/content
            $image = $this->escape($slide['image'] ?? '');
            $title = $this->escape($slide['title'] ?? '');
            $text = $slide['text'] ?? '';
            $link = $this->escape($slide['link'] ?? '');

            $slideContent = '';

            if ($image) {
                $slideContent .= "<img src=\"{$image}\" alt=\"{$title}\" class=\"pd-slider__image\" loading=\"lazy\" />";
            }

            if ($title || $text) {
                $slideContent .= <<<HTML
                <div class="pd-slider__content">
                    <h3 class="pd-slider__title">{$title}</h3>
                    <div class="pd-slider__text">{$text}</div>
                </div>
                HTML;
            }

            if ($link) {
                $slideContent = "<a href=\"{$link}\" class=\"pd-slider__link\">{$slideContent}</a>";
            }

            $html .= "<li class=\"splide__slide\">{$slideContent}</li>";
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'slides' => [
                    'type' => 'repeater',
                    'label' => 'Slajdy',
                    'fields' => [
                        'image' => [
                            'type' => 'image',
                            'label' => 'Obraz',
                        ],
                        'title' => [
                            'type' => 'text',
                            'label' => 'Tytul',
                        ],
                        'text' => [
                            'type' => 'richtext',
                            'label' => 'Tekst',
                        ],
                        'link' => [
                            'type' => 'url',
                            'label' => 'Link',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'type' => [
                    'type' => 'select',
                    'label' => 'Typ animacji',
                    'options' => [
                        'slide' => 'Przesuwanie',
                        'loop' => 'Petla (nieskonczona)',
                        'fade' => 'Zanikanie',
                    ],
                    'default' => 'slide',
                ],
                'autoplay' => [
                    'type' => 'boolean',
                    'label' => 'Autoodtwarzanie',
                    'default' => false,
                ],
                'autoplay_interval' => [
                    'type' => 'number',
                    'label' => 'Interwal (ms)',
                    'min' => 1000,
                    'max' => 20000,
                    'default' => 5000,
                    'condition' => ['autoplay' => true],
                ],
                'pause_on_hover' => [
                    'type' => 'boolean',
                    'label' => 'Pauza przy hover',
                    'default' => true,
                    'condition' => ['autoplay' => true],
                ],
                'arrows' => [
                    'type' => 'boolean',
                    'label' => 'Strzalki nawigacji',
                    'default' => true,
                ],
                'pagination' => [
                    'type' => 'boolean',
                    'label' => 'Kropki (paginacja)',
                    'default' => true,
                ],
                'per_page' => [
                    'type' => 'number',
                    'label' => 'Slajdow na strone',
                    'min' => 1,
                    'max' => 6,
                    'default' => 1,
                ],
                'per_move' => [
                    'type' => 'number',
                    'label' => 'Slajdow przy przewijaniu',
                    'min' => 1,
                    'max' => 6,
                    'default' => 1,
                ],
                'gap' => [
                    'type' => 'text',
                    'label' => 'Odstep miedzy slajdami',
                    'default' => '1rem',
                ],
                'speed' => [
                    'type' => 'number',
                    'label' => 'Szybkosc animacji (ms)',
                    'min' => 100,
                    'max' => 2000,
                    'default' => 400,
                ],
                'rewind' => [
                    'type' => 'boolean',
                    'label' => 'Przewin do poczatku',
                    'default' => true,
                ],
                'drag' => [
                    'type' => 'boolean',
                    'label' => 'Przeciaganie',
                    'default' => true,
                ],
            ],
        ];
    }
}
