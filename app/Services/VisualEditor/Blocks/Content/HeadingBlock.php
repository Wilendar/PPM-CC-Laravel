<?php

namespace App\Services\VisualEditor\Blocks\Content;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Heading Block - H1-H6 headings with optional subtitle.
 *
 * Supports alignment, decoration, and icon prefix.
 */
class HeadingBlock extends BaseBlock
{
    public string $type = 'heading';
    public string $name = 'Naglowek';
    public string $icon = 'heroicons-h1';
    public string $category = 'content';

    public array $defaultSettings = [
        'level' => 'h2',
        'alignment' => 'left',
        'style' => 'default', // default, underline, with-line
        'color' => 'inherit',
        'margin_bottom' => '1.5rem',
        'show_subtitle' => false,
        'subtitle_color' => '#6b7280',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for HeadingBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'background'],
        '.pd-heading__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-heading__subtitle' => ['typography', 'color-picker', 'box-model'],
        'h1' => ['typography', 'color-picker'],
        'h2' => ['typography', 'color-picker'],
        'h3' => ['typography', 'color-picker'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $text = $this->escape($content['text'] ?? '');
        $subtitle = $this->escape($content['subtitle'] ?? '');
        $level = $settings['level'];

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-heading',
            "pd-heading--{$settings['style']}",
            $this->alignmentClass($settings['alignment']),
        ]);

        // Heading styles
        $headingStyle = $this->inlineStyles([
            'color' => $settings['color'],
            'margin-bottom' => $settings['show_subtitle'] ? '0.5rem' : $settings['margin_bottom'],
        ]);

        // Container styles
        $containerStyle = $this->inlineStyles([
            'margin-bottom' => $settings['margin_bottom'],
        ]);

        // Subtitle HTML
        $subtitleHtml = '';
        if ($settings['show_subtitle'] && $subtitle) {
            $subtitleHtml = "<p class=\"pd-heading__subtitle\" style=\"color: {$settings['subtitle_color']}\">{$subtitle}</p>";
        }

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}">
            <{$level} class="pd-heading__title" style="{$headingStyle}">{$text}</{$level}>
            {$subtitleHtml}
        </div>
        HTML;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'text' => [
                    'type' => 'text',
                    'label' => 'Tekst naglowka',
                    'required' => true,
                ],
                'subtitle' => [
                    'type' => 'text',
                    'label' => 'Podtytul',
                    'required' => false,
                ],
            ],
            'settings' => [
                'level' => [
                    'type' => 'select',
                    'label' => 'Poziom naglowka',
                    'options' => [
                        'h1' => 'H1 - Glowny',
                        'h2' => 'H2 - Sekcja',
                        'h3' => 'H3 - Podsekcja',
                        'h4' => 'H4',
                        'h5' => 'H5',
                        'h6' => 'H6',
                    ],
                    'default' => 'h2',
                ],
                'alignment' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowane',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'left',
                ],
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl',
                    'options' => [
                        'default' => 'Domyslny',
                        'underline' => 'Z podkresleniem',
                        'with-line' => 'Z linia boczna',
                    ],
                    'default' => 'default',
                ],
                'color' => [
                    'type' => 'color',
                    'label' => 'Kolor',
                    'default' => 'inherit',
                ],
                'margin_bottom' => [
                    'type' => 'text',
                    'label' => 'Margines dolny',
                    'default' => '1.5rem',
                ],
                'show_subtitle' => [
                    'type' => 'boolean',
                    'label' => 'Pokaz podtytul',
                    'default' => false,
                ],
                'subtitle_color' => [
                    'type' => 'color',
                    'label' => 'Kolor podtytulu',
                    'default' => '#6b7280',
                    'condition' => ['show_subtitle' => true],
                ],
            ],
        ];
    }
}
