<?php

namespace App\Services\VisualEditor\Blocks\Layout;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Grid Section Block - CSS Grid based layout for complex arrangements.
 *
 * Supports custom columns, rows, and gap settings.
 */
class GridSectionBlock extends BaseBlock
{
    public string $type = 'grid-section';
    public string $name = 'Siatka (Grid)';
    public string $icon = 'heroicons-squares-2x2';
    public string $category = 'layout';
    public bool $supportsChildren = true;

    public array $defaultSettings = [
        'columns' => 3,
        'columns_tablet' => 2,
        'columns_mobile' => 1,
        'gap' => '1.5rem',
        'row_gap' => null, // null = same as gap
        'min_item_width' => '250px',
        'auto_fit' => true,
        'align_items' => 'stretch',
        'justify_items' => 'stretch',
        'padding' => '0',
        'background' => 'transparent',
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for GridSectionBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['layout-grid', 'background', 'box-model', 'size'],
        '.pd-grid__item' => ['box-model', 'background', 'border'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        // Build grid template based on settings
        $gridTemplate = $this->buildGridTemplate($settings);
        $rowGap = $settings['row_gap'] ?? $settings['gap'];

        // Container styles
        $containerStyle = $this->inlineStyles([
            'display' => 'grid',
            'grid-template-columns' => $gridTemplate,
            'gap' => "{$rowGap} {$settings['gap']}",
            'align-items' => $settings['align_items'],
            'justify-items' => $settings['justify_items'],
            'padding' => $settings['padding'],
            'background' => $settings['background'],
        ]);

        // Classes for responsive behavior - include original classes if present
        $originalClasses = $content['original_classes'] ?? '';
        $classes = $this->classNames([
            'pd-block',
            'pd-grid',
            "pd-grid--cols-{$settings['columns']}",
            "pd-grid--tablet-{$settings['columns_tablet']}",
            "pd-grid--mobile-{$settings['columns_mobile']}",
        ]);

        // Render grid items
        $items = '';
        foreach ($children as $index => $child) {
            $items .= "<div class=\"pd-grid__item\">{$child}</div>";
        }

        // If no children, use content items
        if (empty($children) && !empty($content['items'])) {
            foreach ($content['items'] as $item) {
                $items .= "<div class=\"pd-grid__item\">{$item}</div>";
            }
        }

        // FALLBACK: If no items but raw HTML exists, use it directly
        // This preserves PrestaShop content imported without pd-grid__item structure
        if (empty($items) && !empty($content['html'])) {
            $items = $content['html'];
            // Use original classes if available for better styling
            if (!empty($originalClasses)) {
                $classes = $originalClasses;
            }
        }

        return <<<HTML
        <div class="{$classes}" style="{$containerStyle}">
            {$items}
        </div>
        HTML;
    }

    /**
     * Build CSS grid-template-columns value.
     */
    private function buildGridTemplate(array $settings): string
    {
        if ($settings['auto_fit']) {
            // Responsive auto-fit with minimum width
            return "repeat(auto-fit, minmax({$settings['min_item_width']}, 1fr))";
        }

        // Fixed column count
        return "repeat({$settings['columns']}, 1fr)";
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'items' => [
                    'type' => 'blocks',
                    'label' => 'Elementy siatki',
                    'accepts' => ['content', 'media'],
                    'multiple' => true,
                ],
            ],
            'settings' => [
                'columns' => [
                    'type' => 'number',
                    'label' => 'Liczba kolumn (desktop)',
                    'min' => 1,
                    'max' => 12,
                    'default' => 3,
                ],
                'columns_tablet' => [
                    'type' => 'number',
                    'label' => 'Liczba kolumn (tablet)',
                    'min' => 1,
                    'max' => 6,
                    'default' => 2,
                ],
                'columns_mobile' => [
                    'type' => 'number',
                    'label' => 'Liczba kolumn (mobile)',
                    'min' => 1,
                    'max' => 4,
                    'default' => 1,
                ],
                'gap' => [
                    'type' => 'text',
                    'label' => 'Odstep (gap)',
                    'default' => '1.5rem',
                ],
                'row_gap' => [
                    'type' => 'text',
                    'label' => 'Odstep pionowy (opcjonalny)',
                    'default' => null,
                ],
                'min_item_width' => [
                    'type' => 'text',
                    'label' => 'Min. szerokosc elementu',
                    'default' => '250px',
                ],
                'auto_fit' => [
                    'type' => 'boolean',
                    'label' => 'Auto-fit (responsywny)',
                    'default' => true,
                ],
                'align_items' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie pionowe',
                    'options' => [
                        'start' => 'Do gory',
                        'center' => 'Srodek',
                        'end' => 'Do dolu',
                        'stretch' => 'Rozciagnij',
                    ],
                    'default' => 'stretch',
                ],
                'justify_items' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie poziome',
                    'options' => [
                        'start' => 'Do lewej',
                        'center' => 'Srodek',
                        'end' => 'Do prawej',
                        'stretch' => 'Rozciagnij',
                    ],
                    'default' => 'stretch',
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
