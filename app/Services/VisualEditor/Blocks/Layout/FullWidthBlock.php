<?php

namespace App\Services\VisualEditor\Blocks\Layout;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Full Width Block - Edge-to-edge container section.
 *
 * Perfect for background sections, dividers, and full-bleed content.
 */
class FullWidthBlock extends BaseBlock
{
    public string $type = 'full-width';
    public string $name = 'Pelna szerokosc';
    public string $icon = 'arrows-pointing-out';
    public string $category = 'layout';
    public bool $supportsChildren = true;

    public array $defaultSettings = [
        'background_type' => 'color', // color, gradient, image
        'background_color' => '#f8fafc',
        'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'background_image' => '',
        'background_overlay' => false,
        'overlay_color' => '#000000',
        'overlay_opacity' => 0.3,
        'padding_top' => '3rem',
        'padding_bottom' => '3rem',
        'padding_sides' => '1rem',
        'content_width' => '1200px',
        'text_color' => 'inherit',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for FullWidthBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['background', 'box-model', 'size', 'effects'],
        '.pd-fullwidth__overlay' => ['background', 'effects'],
        '.pd-fullwidth__inner' => ['box-model', 'size'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        // Build background styles
        $bgStyles = $this->buildBackgroundStyles($settings);

        // Outer container styles (full width)
        $outerStyle = $this->inlineStyles(array_merge($bgStyles, [
            'padding-top' => $settings['padding_top'],
            'padding-bottom' => $settings['padding_bottom'],
            'padding-left' => $settings['padding_sides'],
            'padding-right' => $settings['padding_sides'],
            'color' => $settings['text_color'],
        ]));

        // Inner container styles (content width)
        $innerStyle = $this->inlineStyles([
            'max-width' => $settings['content_width'],
            'margin' => '0 auto',
        ]);

        // Render children or content
        $innerContent = implode('', $children);
        if (empty($innerContent) && !empty($content['content'])) {
            $innerContent = $content['content'];
        }
        // FALLBACK: Also check 'html' field (used by HtmlToBlocksParser)
        if (empty($innerContent) && !empty($content['html'])) {
            $innerContent = $content['html'];
        }

        // Overlay HTML
        $overlayHtml = '';
        if ($settings['background_overlay'] && $settings['background_type'] === 'image') {
            $rgb = sscanf($settings['overlay_color'], "#%02x%02x%02x");
            $overlayStyle = "position: absolute; inset: 0; background: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, {$settings['overlay_opacity']});";
            $overlayHtml = "<div class=\"pd-fullwidth__overlay\" style=\"{$overlayStyle}\"></div>";
        }

        $positionClass = $settings['background_overlay'] ? 'position: relative;' : '';

        return <<<HTML
        <div class="pd-block pd-fullwidth" style="{$outerStyle} {$positionClass}">
            {$overlayHtml}
            <div class="pd-fullwidth__inner" style="{$innerStyle}">
                {$innerContent}
            </div>
        </div>
        HTML;
    }

    /**
     * Build background CSS styles based on type.
     */
    private function buildBackgroundStyles(array $settings): array
    {
        return match ($settings['background_type']) {
            'gradient' => [
                'background' => $settings['background_gradient'],
            ],
            'image' => [
                'background-image' => "url('{$settings['background_image']}')",
                'background-size' => 'cover',
                'background-position' => 'center',
                'background-repeat' => 'no-repeat',
            ],
            default => [
                'background-color' => $settings['background_color'],
            ],
        };
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'content' => [
                    'type' => 'blocks',
                    'label' => 'Zawartosc',
                    'accepts' => ['content', 'media', 'interactive'],
                ],
            ],
            'settings' => [
                'background_type' => [
                    'type' => 'select',
                    'label' => 'Typ tla',
                    'options' => [
                        'color' => 'Kolor',
                        'gradient' => 'Gradient',
                        'image' => 'Obraz',
                    ],
                    'default' => 'color',
                ],
                'background_color' => [
                    'type' => 'color',
                    'label' => 'Kolor tla',
                    'default' => '#f8fafc',
                    'condition' => ['background_type' => 'color'],
                ],
                'background_gradient' => [
                    'type' => 'text',
                    'label' => 'Gradient CSS',
                    'default' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'condition' => ['background_type' => 'gradient'],
                ],
                'background_image' => [
                    'type' => 'image',
                    'label' => 'Obraz tla',
                    'default' => '',
                    'condition' => ['background_type' => 'image'],
                ],
                'background_overlay' => [
                    'type' => 'boolean',
                    'label' => 'Nakladka na obrazie',
                    'default' => false,
                    'condition' => ['background_type' => 'image'],
                ],
                'overlay_color' => [
                    'type' => 'color',
                    'label' => 'Kolor nakladki',
                    'default' => '#000000',
                    'condition' => ['background_overlay' => true],
                ],
                'overlay_opacity' => [
                    'type' => 'range',
                    'label' => 'Przezroczystosc nakladki',
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.1,
                    'default' => 0.3,
                    'condition' => ['background_overlay' => true],
                ],
                'padding_top' => [
                    'type' => 'text',
                    'label' => 'Padding gora',
                    'default' => '3rem',
                ],
                'padding_bottom' => [
                    'type' => 'text',
                    'label' => 'Padding dol',
                    'default' => '3rem',
                ],
                'padding_sides' => [
                    'type' => 'text',
                    'label' => 'Padding boki',
                    'default' => '1rem',
                ],
                'content_width' => [
                    'type' => 'text',
                    'label' => 'Szerokosc zawartosci',
                    'default' => '1200px',
                ],
                'text_color' => [
                    'type' => 'color',
                    'label' => 'Kolor tekstu',
                    'default' => 'inherit',
                ],
            ],
        ];
    }
}
