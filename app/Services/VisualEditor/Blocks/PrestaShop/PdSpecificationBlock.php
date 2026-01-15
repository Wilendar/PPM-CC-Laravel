<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Specification Block - Tabela danych technicznych.
 *
 * Renderuje HTML zgodny z PrestaShop custom.css (klasy pd-specification).
 * Uzywany do prezentacji parametrow technicznych produktu.
 *
 * HTML Output:
 * <div class="pd-specification">
 *     <h3>Dane techniczne</h3>
 *     <table class="pd-specification__table">
 *         <tr><td>Silnik</td><td>200cm³</td></tr>
 *     </table>
 * </div>
 */
class PdSpecificationBlock extends BaseBlock
{
    public string $type = 'pd-specification';
    public string $name = 'Specyfikacja';
    public string $icon = 'heroicons-table-cells';
    public string $category = 'prestashop';
    public string $description = 'Tabela danych technicznych produktu';

    /**
     * ETAP_07h PP.3: Property Panel controls for PdSpecificationBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['table-settings', 'box-model', 'background'],
        '.pd-specification__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-specification__table' => ['border', 'background'],
        '.pd-specification__label' => ['typography', 'color-picker', 'background'],
        '.pd-specification__value' => ['typography', 'color-picker'],
        'tr' => ['background'],
    ];

    public array $defaultSettings = [
        'layout' => 'single', // single, two-column
        'show_title' => true,
        'striped' => true,
        'bordered' => false,
        'compact' => false,
    ];

    /**
     * Render block - generates HTML compatible with PrestaShop custom.css.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);
        $title = $this->escape($content['title'] ?? 'Dane techniczne');
        $rows = $content['rows'] ?? [];

        if (empty($rows)) {
            return '';
        }

        // Build CSS classes
        $classes = ['pd-specification'];
        if ($settings['layout'] === 'two-column') {
            $classes[] = 'pd-specification--two-column';
        }
        if ($settings['striped']) {
            $classes[] = 'pd-specification--striped';
        }
        if ($settings['bordered']) {
            $classes[] = 'pd-specification--bordered';
        }
        if ($settings['compact']) {
            $classes[] = 'pd-specification--compact';
        }

        $classString = implode(' ', $classes);

        // Title HTML
        $titleHtml = $settings['show_title'] && $title
            ? "<h3 class=\"pd-specification__title\">{$title}</h3>"
            : '';

        // Table HTML
        $tableHtml = $this->renderTable($rows, $settings);

        return <<<HTML
<div class="{$classString}">
    {$titleHtml}
    {$tableHtml}
</div>
HTML;
    }

    /**
     * Render specification table.
     */
    private function renderTable(array $rows, array $settings): string
    {
        if ($settings['layout'] === 'two-column') {
            return $this->renderTwoColumnTable($rows);
        }

        return $this->renderSingleColumnTable($rows);
    }

    /**
     * Render single-column table (label | value).
     */
    private function renderSingleColumnTable(array $rows): string
    {
        $html = '<table class="pd-specification__table">';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $label = $this->escape($row['label'] ?? '');
            $value = $this->escape($row['value'] ?? '');

            if ($label || $value) {
                $html .= <<<ROW
<tr>
    <td class="pd-specification__label">{$label}</td>
    <td class="pd-specification__value">{$value}</td>
</tr>
ROW;
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Render two-column table (label | value | label | value).
     */
    private function renderTwoColumnTable(array $rows): string
    {
        $html = '<table class="pd-specification__table pd-specification__table--wide">';
        $html .= '<tbody>';

        // Split rows into pairs
        $chunks = array_chunk($rows, 2);

        foreach ($chunks as $pair) {
            $html .= '<tr>';

            // First cell pair
            $label1 = $this->escape($pair[0]['label'] ?? '');
            $value1 = $this->escape($pair[0]['value'] ?? '');
            $html .= "<td class=\"pd-specification__label\">{$label1}</td>";
            $html .= "<td class=\"pd-specification__value\">{$value1}</td>";

            // Second cell pair (if exists)
            if (isset($pair[1])) {
                $label2 = $this->escape($pair[1]['label'] ?? '');
                $value2 = $this->escape($pair[1]['value'] ?? '');
                $html .= "<td class=\"pd-specification__label\">{$label2}</td>";
                $html .= "<td class=\"pd-specification__value\">{$value2}</td>";
            } else {
                $html .= '<td></td><td></td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Get schema for block editor UI.
     */
    public function getSchema(): array
    {
        return [
            'content' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul tabeli',
                    'default' => 'Dane techniczne',
                    'placeholder' => 'np. Dane techniczne, Specyfikacja',
                ],
                'rows' => [
                    'type' => 'repeater',
                    'label' => 'Wiersze',
                    'min' => 1,
                    'max' => 50,
                    'fields' => [
                        'label' => [
                            'type' => 'text',
                            'label' => 'Nazwa parametru',
                            'required' => true,
                            'placeholder' => 'np. Silnik, Moc, Masa',
                        ],
                        'value' => [
                            'type' => 'text',
                            'label' => 'Wartosc',
                            'required' => true,
                            'placeholder' => 'np. 200cm³, 15 KM, 210 kg',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'layout' => [
                    'type' => 'select',
                    'label' => 'Uklad',
                    'options' => [
                        'single' => 'Jedna kolumna',
                        'two-column' => 'Dwie kolumny',
                    ],
                    'default' => 'single',
                    'help' => 'Dwie kolumny dla dlugich list',
                ],
                'show_title' => [
                    'type' => 'boolean',
                    'label' => 'Pokaz tytul',
                    'default' => true,
                ],
                'striped' => [
                    'type' => 'boolean',
                    'label' => 'Naprzemienne tlo',
                    'default' => true,
                    'help' => 'Zebra striping dla lepszej czytelnosci',
                ],
                'bordered' => [
                    'type' => 'boolean',
                    'label' => 'Ramki komorek',
                    'default' => false,
                ],
                'compact' => [
                    'type' => 'boolean',
                    'label' => 'Kompaktowy',
                    'default' => false,
                    'help' => 'Mniejsze odstepy dla dlugich tabel',
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
<div class="block-preview block-preview--pd-specification">
    <div class="preview-visual">
        <div class="preview-table">
            <div class="preview-row">
                <span class="preview-label">Silnik</span>
                <span class="preview-value">200cm³</span>
            </div>
            <div class="preview-row preview-row--alt">
                <span class="preview-label">Moc</span>
                <span class="preview-value">15 KM</span>
            </div>
            <div class="preview-row">
                <span class="preview-label">Masa</span>
                <span class="preview-value">210 kg</span>
            </div>
        </div>
    </div>
    <div class="preview-info">
        <span class="preview-name">Specyfikacja</span>
        <span class="preview-desc">Tabela danych technicznych</span>
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
                'title' => 'Dane techniczne',
                'rows' => [
                    [
                        'label' => 'Silnik',
                        'value' => '200cm³, 4T, 1 cylinder',
                    ],
                    [
                        'label' => 'Moc',
                        'value' => '15 KM / 7500 obr./min',
                    ],
                    [
                        'label' => 'Skrzynia biegow',
                        'value' => 'CVT (automat)',
                    ],
                    [
                        'label' => 'Masa wlasna',
                        'value' => '210 kg',
                    ],
                ],
            ],
            'settings' => $this->defaultSettings,
        ];
    }
}
