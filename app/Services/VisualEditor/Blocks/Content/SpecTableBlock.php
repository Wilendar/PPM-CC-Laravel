<?php

namespace App\Services\VisualEditor\Blocks\Content;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Specification Table Block - Key-value pairs table.
 *
 * Perfect for product specifications, technical details.
 */
class SpecTableBlock extends BaseBlock
{
    public string $type = 'spec-table';
    public string $name = 'Tabela specyfikacji';
    public string $icon = 'heroicons-table-cells';
    public string $category = 'content';

    public array $defaultSettings = [
        'columns' => 1, // 1 or 2 column layout
        'style' => 'striped', // plain, striped, bordered
        'header_style' => 'bold', // bold, light, accent
        'label_width' => '40%',
        'show_group_headers' => true,
        'compact' => false,
    ];

    /**
     * ETAP_07h PP.3: Property Panel controls for SpecTableBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['table-settings', 'box-model', 'background'],
        '.pd-spec-table__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-spec-table__table' => ['border', 'background'],
        '.pd-spec-table__label' => ['typography', 'color-picker', 'background'],
        '.pd-spec-table__value' => ['typography', 'color-picker'],
        '.pd-spec-table__group th' => ['typography', 'color-picker', 'background'],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $rows = $content['rows'] ?? [];
        $title = $this->escape($content['title'] ?? '');

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-spec-table',
            "pd-spec-table--{$settings['style']}",
            "pd-spec-table--header-{$settings['header_style']}",
            $settings['compact'] ? 'pd-spec-table--compact' : '',
        ]);

        // Title HTML
        $titleHtml = '';
        if ($title) {
            $titleHtml = "<h3 class=\"pd-spec-table__title\">{$title}</h3>";
        }

        // Build table rows
        $rowsHtml = $this->buildRowsHtml($rows, $settings);

        // Multi-column wrapper if needed
        if ($settings['columns'] > 1) {
            return <<<HTML
            <div class="{$containerClasses}">
                {$titleHtml}
                <div class="pd-spec-table__grid" style="display: grid; grid-template-columns: repeat({$settings['columns']}, 1fr); gap: 2rem;">
                    {$rowsHtml}
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <div class="{$containerClasses}">
            {$titleHtml}
            <table class="pd-spec-table__table">
                <colgroup>
                    <col style="width: {$settings['label_width']};" />
                    <col />
                </colgroup>
                <tbody>
                    {$rowsHtml}
                </tbody>
            </table>
        </div>
        HTML;
    }

    /**
     * Build table rows HTML from data.
     */
    private function buildRowsHtml(array $rows, array $settings): string
    {
        $html = '';
        $currentGroup = null;

        foreach ($rows as $row) {
            // Group header
            if (isset($row['group']) && $row['group'] !== $currentGroup) {
                $currentGroup = $row['group'];
                if ($settings['show_group_headers']) {
                    $groupName = $this->escape($currentGroup);
                    $html .= "<tr class=\"pd-spec-table__group\"><th colspan=\"2\">{$groupName}</th></tr>";
                }
            }

            // Regular row
            $label = $this->escape($row['label'] ?? '');
            $value = $this->escape($row['value'] ?? '');
            $highlight = !empty($row['highlight']) ? 'pd-spec-table__row--highlight' : '';

            $html .= "<tr class=\"pd-spec-table__row {$highlight}\">";
            $html .= "<th class=\"pd-spec-table__label\">{$label}</th>";
            $html .= "<td class=\"pd-spec-table__value\">{$value}</td>";
            $html .= "</tr>";
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul tabeli',
                    'required' => false,
                ],
                'rows' => [
                    'type' => 'repeater',
                    'label' => 'Wiersze',
                    'fields' => [
                        'label' => [
                            'type' => 'text',
                            'label' => 'Etykieta',
                        ],
                        'value' => [
                            'type' => 'text',
                            'label' => 'Wartosc',
                        ],
                        'group' => [
                            'type' => 'text',
                            'label' => 'Grupa (opcjonalna)',
                        ],
                        'highlight' => [
                            'type' => 'boolean',
                            'label' => 'Wyroznienie',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'columns' => [
                    'type' => 'select',
                    'label' => 'Liczba kolumn tabeli',
                    'options' => [
                        '1' => '1 kolumna',
                        '2' => '2 kolumny',
                    ],
                    'default' => '1',
                ],
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl tabeli',
                    'options' => [
                        'plain' => 'Prosty',
                        'striped' => 'Paski',
                        'bordered' => 'Z obramowaniem',
                    ],
                    'default' => 'striped',
                ],
                'header_style' => [
                    'type' => 'select',
                    'label' => 'Styl etykiet',
                    'options' => [
                        'bold' => 'Pogrubione',
                        'light' => 'Lekkie',
                        'accent' => 'Akcent kolorowy',
                    ],
                    'default' => 'bold',
                ],
                'label_width' => [
                    'type' => 'text',
                    'label' => 'Szerokosc etykiet',
                    'default' => '40%',
                ],
                'show_group_headers' => [
                    'type' => 'boolean',
                    'label' => 'Pokaz naglowki grup',
                    'default' => true,
                ],
                'compact' => [
                    'type' => 'boolean',
                    'label' => 'Kompaktowy wyglad',
                    'default' => false,
                ],
            ],
        ];
    }
}
