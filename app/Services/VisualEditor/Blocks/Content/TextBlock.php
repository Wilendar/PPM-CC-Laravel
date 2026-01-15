<?php

namespace App\Services\VisualEditor\Blocks\Content;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Text Block - Rich text content with formatting.
 *
 * Supports multi-column layout and dropcap.
 */
class TextBlock extends BaseBlock
{
    public string $type = 'text';
    public string $name = 'Tekst';
    public string $icon = 'heroicons-document-text';
    public string $category = 'content';

    public array $defaultSettings = [
        'alignment' => 'left',
        'columns' => 1,
        'column_gap' => '2rem',
        'font_size' => 'inherit',
        'line_height' => '1.6',
        'color' => 'inherit',
        'dropcap' => false,
        'max_width' => 'none',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for TextBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['typography', 'color-picker', 'box-model', 'size'],
        'p' => ['typography', 'color-picker', 'box-model'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        // Content can be HTML from TinyMCE
        $text = $content['text'] ?? '';

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-text',
            $this->alignmentClass($settings['alignment']),
            $settings['dropcap'] ? 'pd-text--dropcap' : '',
            $settings['columns'] > 1 ? 'pd-text--multicolumn' : '',
        ]);

        // Build column styles
        $columnStyles = [];
        if ($settings['columns'] > 1) {
            $columnStyles = [
                'column-count' => $settings['columns'],
                'column-gap' => $settings['column_gap'],
            ];
        }

        // Container styles
        $containerStyle = $this->inlineStyles(array_merge($columnStyles, [
            'font-size' => $settings['font_size'],
            'line-height' => $settings['line_height'],
            'color' => $settings['color'],
            'max-width' => $settings['max_width'],
        ]));

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}">
            {$text}
        </div>
        HTML;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'text' => [
                    'type' => 'richtext',
                    'label' => 'Tresc',
                    'required' => true,
                ],
            ],
            'settings' => [
                'alignment' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowane',
                        'right' => 'Do prawej',
                        'justify' => 'Wyjustowane',
                    ],
                    'default' => 'left',
                ],
                'columns' => [
                    'type' => 'number',
                    'label' => 'Liczba kolumn',
                    'min' => 1,
                    'max' => 4,
                    'default' => 1,
                ],
                'column_gap' => [
                    'type' => 'text',
                    'label' => 'Odstep kolumn',
                    'default' => '2rem',
                    'condition' => ['columns' => ['>', 1]],
                ],
                'font_size' => [
                    'type' => 'text',
                    'label' => 'Rozmiar czcionki',
                    'default' => 'inherit',
                ],
                'line_height' => [
                    'type' => 'text',
                    'label' => 'Interlinia',
                    'default' => '1.6',
                ],
                'color' => [
                    'type' => 'color',
                    'label' => 'Kolor tekstu',
                    'default' => 'inherit',
                ],
                'dropcap' => [
                    'type' => 'boolean',
                    'label' => 'Inicjal (dropcap)',
                    'default' => false,
                ],
                'max_width' => [
                    'type' => 'text',
                    'label' => 'Maksymalna szerokosc',
                    'default' => 'none',
                ],
            ],
        ];
    }
}
