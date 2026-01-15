<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Parallax Block - Fullwidth image z efektem parallax.
 *
 * Renderuje HTML zgodny z PrestaShop custom.css (klasa pd-pseudo-parallax).
 * Uzywany do tworzenia efektownych sekcji z obrazem tla i tekstem overlay.
 *
 * HTML Output:
 * <div class="pd-pseudo-parallax" style="background-image: url(...)">
 *     <div class="pd-pseudo-parallax__overlay">
 *         <h3>Tytul</h3>
 *         <p>Podtytul</p>
 *     </div>
 * </div>
 */
class PdParallaxBlock extends BaseBlock
{
    public string $type = 'pd-parallax';
    public string $name = 'Parallax';
    public string $icon = 'heroicons-photo';
    public string $category = 'prestashop';
    public string $description = 'Fullwidth obraz z efektem parallax i nakladka';

    /**
     * ETAP_07h PP.3: Property Panel controls for PdParallaxBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['parallax-settings', 'background', 'box-model', 'size'],
        '.pd-pseudo-parallax__overlay' => ['background', 'effects', 'layout-flex'],
        '.pd-pseudo-parallax__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-pseudo-parallax__subtitle' => ['typography', 'color-picker'],
        '.pd-pseudo-parallax__btn' => ['button-settings', 'typography', 'color-picker', 'border'],
    ];

    public array $defaultSettings = [
        'height' => '500px',
        'overlay_opacity' => 0.4,
        'overlay_color' => 'dark',
        'text_align' => 'center',
        'text_position' => 'center',
        'parallax_speed' => 0.5,
    ];

    /**
     * Render block - generates HTML compatible with PrestaShop custom.css.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $backgroundImage = $content['background_image'] ?? '';
        $title = $this->escape($content['title'] ?? '');
        $subtitle = $this->escape($content['subtitle'] ?? '');
        $buttonText = $this->escape($content['button_text'] ?? '');
        $buttonUrl = $this->escape($content['button_url'] ?? '');

        // Build inline styles for background
        $bgStyle = '';
        if ($backgroundImage) {
            $bgStyle = "background-image: url('{$backgroundImage}');";
        }

        // Height style
        $heightStyle = "min-height: {$settings['height']};";

        // Overlay styles
        $overlayClass = $this->getOverlayClass($settings);
        $textAlignClass = "text-{$settings['text_align']}";
        $positionClass = $this->getPositionClass($settings['text_position']);

        // Build content HTML
        $contentHtml = $this->buildContentHtml($title, $subtitle, $buttonText, $buttonUrl);

        return <<<HTML
<div class="pd-pseudo-parallax" style="{$bgStyle} {$heightStyle}">
    <div class="pd-pseudo-parallax__overlay {$overlayClass} {$textAlignClass} {$positionClass}">
        {$contentHtml}
    </div>
</div>
HTML;
    }

    /**
     * Build content HTML (title, subtitle, button).
     */
    private function buildContentHtml(string $title, string $subtitle, string $buttonText, string $buttonUrl): string
    {
        $html = '';

        if ($title) {
            $html .= "<h3 class=\"pd-pseudo-parallax__title\">{$title}</h3>\n";
        }

        if ($subtitle) {
            $html .= "<p class=\"pd-pseudo-parallax__subtitle\">{$subtitle}</p>\n";
        }

        if ($buttonText && $buttonUrl) {
            $html .= "<a href=\"{$buttonUrl}\" class=\"pd-pseudo-parallax__btn btn-primary\">{$buttonText}</a>\n";
        }

        return $html;
    }

    /**
     * Get overlay CSS class based on settings.
     */
    private function getOverlayClass(array $settings): string
    {
        $overlayColor = $settings['overlay_color'] ?? 'dark';
        $opacity = $settings['overlay_opacity'] ?? 0.4;

        // Map to CSS classes (these should exist in custom.css)
        $colorClass = match ($overlayColor) {
            'light' => 'pd-pseudo-parallax__overlay--light',
            'brand' => 'pd-pseudo-parallax__overlay--brand',
            'none' => 'pd-pseudo-parallax__overlay--none',
            default => 'pd-pseudo-parallax__overlay--dark',
        };

        return $colorClass;
    }

    /**
     * Get text position CSS class.
     */
    private function getPositionClass(string $position): string
    {
        return match ($position) {
            'top' => 'pd-pseudo-parallax__overlay--top',
            'bottom' => 'pd-pseudo-parallax__overlay--bottom',
            default => 'pd-pseudo-parallax__overlay--center',
        };
    }

    /**
     * Get schema for block editor UI.
     */
    public function getSchema(): array
    {
        return [
            'content' => [
                'background_image' => [
                    'type' => 'image',
                    'label' => 'Obraz tla',
                    'required' => true,
                    'help' => 'URL obrazu. Zalecana szerokosc: min. 1920px',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul',
                    'placeholder' => 'np. Gdzie mozna poczuc pelnie mozliwosci',
                ],
                'subtitle' => [
                    'type' => 'text',
                    'label' => 'Podtytul',
                    'placeholder' => 'np. KAYO S200',
                ],
                'button_text' => [
                    'type' => 'text',
                    'label' => 'Tekst przycisku',
                    'placeholder' => 'np. Dowiedz sie wiecej',
                ],
                'button_url' => [
                    'type' => 'url',
                    'label' => 'URL przycisku',
                    'placeholder' => 'https://...',
                ],
            ],
            'settings' => [
                'height' => [
                    'type' => 'select',
                    'label' => 'Wysokosc',
                    'options' => [
                        '300px' => 'Mala (300px)',
                        '400px' => 'Srednia (400px)',
                        '500px' => 'Duza (500px)',
                        '600px' => 'Bardzo duza (600px)',
                        '100vh' => 'Pelny ekran (100vh)',
                    ],
                    'default' => '500px',
                ],
                'overlay_color' => [
                    'type' => 'select',
                    'label' => 'Kolor nakladki',
                    'options' => [
                        'dark' => 'Ciemna',
                        'light' => 'Jasna',
                        'brand' => 'Pomaranczowa',
                        'none' => 'Brak',
                    ],
                    'default' => 'dark',
                ],
                'overlay_opacity' => [
                    'type' => 'range',
                    'label' => 'Przezroczystosc nakladki',
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.1,
                    'default' => 0.4,
                ],
                'text_align' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie tekstu',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Srodek',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'center',
                ],
                'text_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja tekstu',
                    'options' => [
                        'top' => 'Gora',
                        'center' => 'Srodek',
                        'bottom' => 'Dol',
                    ],
                    'default' => 'center',
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
<div class="block-preview block-preview--pd-parallax">
    <div class="preview-visual">
        <div class="preview-parallax">
            <div class="preview-bg"></div>
            <div class="preview-overlay">
                <span class="preview-text">Parallax</span>
            </div>
        </div>
    </div>
    <div class="preview-info">
        <span class="preview-name">Parallax</span>
        <span class="preview-desc">Obraz z efektem</span>
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
                'background_image' => '',
                'title' => 'Tytul sekcji',
                'subtitle' => 'Podtytul',
                'button_text' => '',
                'button_url' => '',
            ],
            'settings' => $this->defaultSettings,
        ];
    }
}
