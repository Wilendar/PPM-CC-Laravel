<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Slider Block - Karuzela Splide.js.
 *
 * Renderuje HTML zgodny z PrestaShop custom.css i Splide.js.
 * Uzywany do prezentacji slajdow z obrazami i tekstem.
 *
 * HTML Output:
 * <div class="pd-slider splide">
 *     <div class="splide__track">
 *         <ul class="splide__list">
 *             <li class="splide__slide">...</li>
 *         </ul>
 *     </div>
 * </div>
 */
class PdSliderBlock extends BaseBlock
{
    public string $type = 'pd-slider';
    public string $name = 'Slider';
    public string $icon = 'heroicons-rectangle-stack';
    public string $category = 'prestashop';
    public string $description = 'Karuzela Splide.js z obrazami i tekstem';

    /**
     * ETAP_07h PP.3: Property Panel controls for PdSliderBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['slider-settings', 'box-model', 'background'],
        '.splide__slide' => ['box-model', 'background'],
        '.pd-slider__item' => ['box-model', 'background'],
        '.pd-slider__image' => ['image-settings', 'border', 'effects'],
        '.pd-slider__content' => ['box-model', 'background'],
        '.pd-slider__title' => ['typography', 'color-picker'],
        '.pd-slider__description' => ['typography', 'color-picker'],
        '.splide__arrow' => ['color-picker', 'background', 'border'],
        '.splide__pagination__page' => ['color-picker', 'size'],
    ];

    public array $defaultSettings = [
        'perPage' => 1,
        'autoplay' => false,
        'autoplay_interval' => 5000,
        'loop' => true,
        'arrows' => true,
        'pagination' => true,
        'gap' => '0',
        'speed' => 400,
        'slide_type' => 'image-text', // image-only, image-text, text-only
    ];

    /**
     * Render block - generates HTML compatible with Splide.js.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);
        $slides = $content['slides'] ?? [];

        if (empty($slides)) {
            return '';
        }

        // Build slides HTML
        $slidesHtml = $this->renderSlides($slides, $settings);

        // Build Splide config as data attribute
        $splideConfig = $this->buildSplideConfig($settings);
        $configJson = htmlspecialchars(json_encode($splideConfig), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="pd-slider splide" data-splide='{$configJson}'>
    <div class="splide__track">
        <ul class="splide__list">
{$slidesHtml}
        </ul>
    </div>
</div>
HTML;
    }

    /**
     * Render slide items.
     */
    private function renderSlides(array $slides, array $settings): string
    {
        $html = '';
        $slideType = $settings['slide_type'];

        foreach ($slides as $slide) {
            $image = $slide['image'] ?? '';
            $title = $this->escape($slide['title'] ?? '');
            $description = $slide['description'] ?? '';
            $link = $slide['link'] ?? '';

            // Sanitize description
            $description = strip_tags($description, '<strong><em><b><i><br><a>');

            $slideContent = match ($slideType) {
                'image-only' => $this->renderImageOnlySlide($image, $title),
                'text-only' => $this->renderTextOnlySlide($title, $description),
                default => $this->renderImageTextSlide($image, $title, $description),
            };

            // Wrap in link if provided
            if ($link) {
                $linkEscaped = $this->escape($link);
                $slideContent = "<a href=\"{$linkEscaped}\" class=\"pd-slider__link\">{$slideContent}</a>";
            }

            $html .= <<<HTML
            <li class="splide__slide">
                <div class="pd-slider__item">
                    {$slideContent}
                </div>
            </li>
HTML;
        }

        return $html;
    }

    /**
     * Render image-only slide.
     */
    private function renderImageOnlySlide(string $image, string $alt): string
    {
        if (!$image) {
            return '<div class="pd-slider__placeholder">Brak obrazu</div>';
        }

        return <<<HTML
<img src="{$image}" alt="{$alt}" class="pd-slider__image" loading="lazy">
HTML;
    }

    /**
     * Render text-only slide.
     */
    private function renderTextOnlySlide(string $title, string $description): string
    {
        $titleHtml = $title ? "<h4 class=\"pd-slider__title\">{$title}</h4>" : '';
        $descHtml = $description ? "<p class=\"pd-slider__description\">{$description}</p>" : '';

        return <<<HTML
<div class="pd-slider__content">
    {$titleHtml}
    {$descHtml}
</div>
HTML;
    }

    /**
     * Render image + text slide.
     */
    private function renderImageTextSlide(string $image, string $title, string $description): string
    {
        $imageHtml = '';
        if ($image) {
            $imageHtml = "<img src=\"{$image}\" alt=\"{$title}\" class=\"pd-slider__image\" loading=\"lazy\">";
        }

        $titleHtml = $title ? "<h4 class=\"pd-slider__title\">{$title}</h4>" : '';
        $descHtml = $description ? "<p class=\"pd-slider__description\">{$description}</p>" : '';

        return <<<HTML
{$imageHtml}
<div class="pd-slider__content">
    {$titleHtml}
    {$descHtml}
</div>
HTML;
    }

    /**
     * Build Splide.js configuration object.
     */
    private function buildSplideConfig(array $settings): array
    {
        $config = [
            'type' => $settings['loop'] ? 'loop' : 'slide',
            'perPage' => (int) $settings['perPage'],
            'perMove' => 1,
            'gap' => $settings['gap'],
            'pagination' => $settings['pagination'],
            'arrows' => $settings['arrows'],
            'speed' => (int) $settings['speed'],
            'easing' => 'ease',
        ];

        if ($settings['autoplay']) {
            $config['autoplay'] = true;
            $config['interval'] = (int) $settings['autoplay_interval'];
            $config['pauseOnHover'] = true;
        }

        return $config;
    }

    /**
     * Get schema for block editor UI.
     */
    public function getSchema(): array
    {
        return [
            'content' => [
                'slides' => [
                    'type' => 'repeater',
                    'label' => 'Slajdy',
                    'min' => 1,
                    'max' => 20,
                    'fields' => [
                        'image' => [
                            'type' => 'image',
                            'label' => 'Obraz',
                            'help' => 'URL obrazu slajdu',
                        ],
                        'title' => [
                            'type' => 'text',
                            'label' => 'Tytul',
                            'placeholder' => 'Tytul slajdu',
                        ],
                        'description' => [
                            'type' => 'textarea',
                            'label' => 'Opis',
                            'rows' => 2,
                        ],
                        'link' => [
                            'type' => 'url',
                            'label' => 'Link (opcjonalny)',
                            'placeholder' => 'https://...',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'slide_type' => [
                    'type' => 'select',
                    'label' => 'Typ slajdu',
                    'options' => [
                        'image-text' => 'Obraz + tekst',
                        'image-only' => 'Tylko obraz',
                        'text-only' => 'Tylko tekst',
                    ],
                    'default' => 'image-text',
                ],
                'perPage' => [
                    'type' => 'select',
                    'label' => 'Slajdow na strone',
                    'options' => [
                        1 => '1 slajd',
                        2 => '2 slajdy',
                        3 => '3 slajdy',
                        4 => '4 slajdy',
                    ],
                    'default' => 1,
                ],
                'autoplay' => [
                    'type' => 'boolean',
                    'label' => 'Automatyczne przewijanie',
                    'default' => false,
                ],
                'autoplay_interval' => [
                    'type' => 'number',
                    'label' => 'Interwal (ms)',
                    'min' => 1000,
                    'max' => 15000,
                    'default' => 5000,
                    'condition' => ['autoplay' => true],
                ],
                'loop' => [
                    'type' => 'boolean',
                    'label' => 'Petla (nieskonczone)',
                    'default' => true,
                ],
                'arrows' => [
                    'type' => 'boolean',
                    'label' => 'Pokazuj strzalki',
                    'default' => true,
                ],
                'pagination' => [
                    'type' => 'boolean',
                    'label' => 'Pokazuj paginacje',
                    'default' => true,
                ],
                'speed' => [
                    'type' => 'number',
                    'label' => 'Szybkosc animacji (ms)',
                    'min' => 100,
                    'max' => 2000,
                    'default' => 400,
                ],
            ],
        ];
    }

    /**
     * Get preview for block palette.
     */
    public function getPreview(): string
    {
        return <<<'HTML'
<div class="block-preview block-preview--pd-slider">
    <div class="preview-visual">
        <div class="preview-slider">
            <span class="preview-arrow preview-arrow--left">&#9664;</span>
            <div class="preview-slide-box">
                <div class="preview-slide-indicator"></div>
                <div class="preview-slide-indicator preview-slide-indicator--active"></div>
                <div class="preview-slide-indicator"></div>
            </div>
            <span class="preview-arrow preview-arrow--right">&#9654;</span>
        </div>
    </div>
    <div class="preview-info">
        <span class="preview-name">Slider</span>
        <span class="preview-desc">Karuzela Splide.js</span>
    </div>
</div>
HTML;
    }

    /**
     * Get default data for new block.
     */
    public function getDefaultData(): array
    {
        return [
            'content' => [
                'slides' => [
                    [
                        'image' => '',
                        'title' => 'Slajd 1',
                        'description' => 'Opis pierwszego slajdu.',
                        'link' => '',
                    ],
                    [
                        'image' => '',
                        'title' => 'Slajd 2',
                        'description' => 'Opis drugiego slajdu.',
                        'link' => '',
                    ],
                ],
            ],
            'settings' => $this->defaultSettings,
        ];
    }
}
