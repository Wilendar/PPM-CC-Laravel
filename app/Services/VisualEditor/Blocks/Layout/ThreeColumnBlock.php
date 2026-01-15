<?php

namespace App\Services\VisualEditor\Blocks\Layout;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Three Column Block - Three-column layout for feature sections.
 *
 * Supports child blocks in each column. Responsive with mobile stacking.
 */
class ThreeColumnBlock extends BaseBlock
{
    public string $type = 'three-column';
    public string $name = 'Trzy kolumny';
    public string $icon = 'heroicons-view-columns';
    public string $category = 'layout';
    public bool $supportsChildren = true;

    public array $defaultSettings = [
        'ratio' => '33-33-33',
        'gap' => '2rem',
        'stack_on_tablet' => true,
        'stack_on_mobile' => true,
        'vertical_align' => 'start',
        'padding' => '0',
        'background' => 'transparent',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for ThreeColumnBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['layout-flex', 'background', 'box-model', 'size'],
        '.pd-cols__item' => ['box-model', 'background', 'border'],
    ];

    /**
     * Column ratio mappings to CSS grid fractions.
     */
    private array $ratioMap = [
        '33-33-33' => '1fr 1fr 1fr',
        '50-25-25' => '2fr 1fr 1fr',
        '25-50-25' => '1fr 2fr 1fr',
        '25-25-50' => '1fr 1fr 2fr',
        '40-30-30' => '4fr 3fr 3fr',
        '30-40-30' => '3fr 4fr 3fr',
        '30-30-40' => '3fr 3fr 4fr',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        // Get grid template
        $gridTemplate = $this->ratioMap[$settings['ratio']] ?? '1fr 1fr 1fr';

        // Container styles
        $containerStyle = $this->inlineStyles([
            'display' => 'grid',
            'grid-template-columns' => $gridTemplate,
            'gap' => $settings['gap'],
            'align-items' => $settings['vertical_align'],
            'padding' => $settings['padding'],
            'background' => $settings['background'],
        ]);

        // Responsive classes
        $classes = $this->classNames([
            'pd-block',
            'pd-cols',
            'pd-cols--3',
            $settings['stack_on_tablet'] ? 'pd-cols--stack-tablet' : '',
            $settings['stack_on_mobile'] ? 'pd-cols--stack-mobile' : '',
        ]);

        // FALLBACK: If raw HTML exists (no column structure found), render directly
        if (!empty($content['html'])) {
            $originalClasses = $content['original_classes'] ?? '';
            $useClasses = !empty($originalClasses) ? $originalClasses : $classes;
            return <<<HTML
            <div class="{$useClasses}" style="{$containerStyle}">
                {$content['html']}
            </div>
            HTML;
        }

        // Render column contents
        $col1 = $children[0] ?? ($content['col1'] ?? '');
        $col2 = $children[1] ?? ($content['col2'] ?? '');
        $col3 = $children[2] ?? ($content['col3'] ?? '');

        return <<<HTML
        <div class="{$classes}" style="{$containerStyle}">
            <div class="pd-cols__item pd-cols__col1">
                {$col1}
            </div>
            <div class="pd-cols__item pd-cols__col2">
                {$col2}
            </div>
            <div class="pd-cols__item pd-cols__col3">
                {$col3}
            </div>
        </div>
        HTML;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'col1' => [
                    'type' => 'blocks',
                    'label' => 'Kolumna 1',
                    'accepts' => ['content', 'media'],
                ],
                'col2' => [
                    'type' => 'blocks',
                    'label' => 'Kolumna 2',
                    'accepts' => ['content', 'media'],
                ],
                'col3' => [
                    'type' => 'blocks',
                    'label' => 'Kolumna 3',
                    'accepts' => ['content', 'media'],
                ],
            ],
            'settings' => [
                'ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje kolumn',
                    'options' => [
                        '33-33-33' => '33% / 33% / 33%',
                        '50-25-25' => '50% / 25% / 25%',
                        '25-50-25' => '25% / 50% / 25%',
                        '25-25-50' => '25% / 25% / 50%',
                        '40-30-30' => '40% / 30% / 30%',
                        '30-40-30' => '30% / 40% / 30%',
                        '30-30-40' => '30% / 30% / 40%',
                    ],
                    'default' => '33-33-33',
                ],
                'gap' => [
                    'type' => 'text',
                    'label' => 'Odstep miedzy kolumnami',
                    'default' => '2rem',
                ],
                'stack_on_tablet' => [
                    'type' => 'boolean',
                    'label' => 'Stosuj na tablecie',
                    'default' => true,
                ],
                'stack_on_mobile' => [
                    'type' => 'boolean',
                    'label' => 'Stosuj na mobile',
                    'default' => true,
                ],
                'vertical_align' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie pionowe',
                    'options' => [
                        'start' => 'Do gory',
                        'center' => 'Srodek',
                        'end' => 'Do dolu',
                        'stretch' => 'Rozciagnij',
                    ],
                    'default' => 'start',
                ],
                'padding' => [
                    'type' => 'text',
                    'label' => 'Padding',
                    'default' => '0',
                ],
                'background' => [
                    'type' => 'color',
                    'label' => 'Tlo',
                    'default' => 'transparent',
                ],
            ],
        ];
    }
}
