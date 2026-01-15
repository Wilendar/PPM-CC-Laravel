<?php

namespace App\Services\VisualEditor\Blocks\Layout;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Two Column Block - Flexible two-column layout with customizable ratios.
 *
 * Supports child blocks in each column. Responsive with mobile stacking.
 */
class TwoColumnBlock extends BaseBlock
{
    public string $type = 'two-column';
    public string $name = 'Dwie kolumny';
    public string $icon = 'heroicons-view-columns';
    public string $category = 'layout';
    public bool $supportsChildren = true;

    public array $defaultSettings = [
        'ratio' => '50-50',
        'gap' => '2rem',
        'reverse_mobile' => false,
        'vertical_align' => 'start',
        'padding' => '0',
        'background' => 'transparent',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for TwoColumnBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['layout-flex', 'background', 'box-model', 'size'],
        '.pd-cols__item' => ['box-model', 'background', 'border'],
        '.pd-cols__left' => ['box-model', 'background'],
        '.pd-cols__right' => ['box-model', 'background'],
    ];

    /**
     * Column ratio mappings to CSS grid fractions.
     */
    private array $ratioMap = [
        '50-50' => '1fr 1fr',
        '60-40' => '3fr 2fr',
        '40-60' => '2fr 3fr',
        '70-30' => '7fr 3fr',
        '30-70' => '3fr 7fr',
        '75-25' => '3fr 1fr',
        '25-75' => '1fr 3fr',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        // Get grid template
        $gridTemplate = $this->ratioMap[$settings['ratio']] ?? '1fr 1fr';

        // Container styles
        $containerStyle = $this->inlineStyles([
            'display' => 'grid',
            'grid-template-columns' => $gridTemplate,
            'gap' => $settings['gap'],
            'align-items' => $settings['vertical_align'],
            'padding' => $settings['padding'],
            'background' => $settings['background'],
        ]);

        // Mobile reverse class
        $reverseClass = $settings['reverse_mobile'] ? 'pd-cols--reverse-mobile' : '';

        // FALLBACK: If raw HTML exists (no column structure found), render directly
        if (!empty($content['html'])) {
            $originalClasses = $content['original_classes'] ?? '';
            $classes = !empty($originalClasses) ? $originalClasses : "pd-block pd-cols pd-cols--2 {$reverseClass}";
            return <<<HTML
            <div class="{$classes}" style="{$containerStyle}">
                {$content['html']}
            </div>
            HTML;
        }

        // Render column contents
        $leftContent = $children[0] ?? ($content['left'] ?? '');
        $rightContent = $children[1] ?? ($content['right'] ?? '');

        // If content is array, assume it's child blocks - they should be pre-rendered
        if (is_array($leftContent)) {
            $leftContent = '<!-- Child blocks should be pre-rendered -->';
        }
        if (is_array($rightContent)) {
            $rightContent = '<!-- Child blocks should be pre-rendered -->';
        }

        return <<<HTML
        <div class="pd-block pd-cols pd-cols--2 {$reverseClass}" style="{$containerStyle}">
            <div class="pd-cols__item pd-cols__left">
                {$leftContent}
            </div>
            <div class="pd-cols__item pd-cols__right">
                {$rightContent}
            </div>
        </div>
        HTML;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'left' => [
                    'type' => 'blocks',
                    'label' => 'Lewa kolumna',
                    'accepts' => ['content', 'media'],
                ],
                'right' => [
                    'type' => 'blocks',
                    'label' => 'Prawa kolumna',
                    'accepts' => ['content', 'media'],
                ],
            ],
            'settings' => [
                'ratio' => [
                    'type' => 'select',
                    'label' => 'Proporcje kolumn',
                    'options' => [
                        '50-50' => '50% / 50%',
                        '60-40' => '60% / 40%',
                        '40-60' => '40% / 60%',
                        '70-30' => '70% / 30%',
                        '30-70' => '30% / 70%',
                        '75-25' => '75% / 25%',
                        '25-75' => '25% / 75%',
                    ],
                    'default' => '50-50',
                ],
                'gap' => [
                    'type' => 'text',
                    'label' => 'Odstep miedzy kolumnami',
                    'default' => '2rem',
                ],
                'reverse_mobile' => [
                    'type' => 'boolean',
                    'label' => 'Odwroc na mobile',
                    'default' => false,
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
