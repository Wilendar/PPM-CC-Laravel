<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Asset List Block - Lista kluczowych parametrow.
 *
 * Renderuje HTML zgodny z PrestaShop custom.css (klasy pd-asset-list).
 * Uzywany do prezentacji kluczowych parametrow produktu w formie wizualnej.
 *
 * HTML Output:
 * <div class="pd-asset-list">
 *     <div class="pd-asset">
 *         <span class="pd-asset__value">200</span>
 *         <span class="pd-asset__unit">cm³</span>
 *         <span class="pd-asset__label">Pojemność</span>
 *     </div>
 * </div>
 */
class PdAssetListBlock extends BaseBlock
{
    public string $type = 'pd-asset-list';
    public string $name = 'Parametry';
    public string $icon = 'heroicons-squares-2x2';
    public string $category = 'prestashop';
    public string $description = 'Kluczowe parametry z wartosciami i jednostkami';

    /**
     * ETAP_07h PP.3: Property Panel controls for PdAssetListBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['list-settings', 'layout-flex', 'layout-grid', 'box-model', 'background'],
        '.pd-asset' => ['box-model', 'background', 'border'],
        '.pd-asset__icon' => ['color-picker', 'size'],
        '.pd-asset__value' => ['typography', 'color-picker'],
        '.pd-asset__unit' => ['typography', 'color-picker'],
        '.pd-asset__label' => ['typography', 'color-picker'],
    ];

    public array $defaultSettings = [
        'layout' => 'horizontal', // horizontal, vertical, grid
        'columns' => 4,
        'size' => 'medium', // small, medium, large
        'show_dividers' => false,
        'center_text' => true,
    ];

    /**
     * Render block - generates HTML compatible with PrestaShop custom.css.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);
        $assets = $content['assets'] ?? [];

        if (empty($assets)) {
            return '';
        }

        // Build CSS classes
        $classes = ['pd-asset-list'];
        $classes[] = "pd-asset-list--{$settings['layout']}";
        $classes[] = "pd-asset-list--{$settings['size']}";

        if ($settings['layout'] === 'grid') {
            $classes[] = "pd-asset-list--cols-{$settings['columns']}";
        }
        if ($settings['show_dividers']) {
            $classes[] = 'pd-asset-list--dividers';
        }
        if ($settings['center_text']) {
            $classes[] = 'pd-asset-list--centered';
        }

        $classString = implode(' ', $classes);

        // Build assets HTML
        $assetsHtml = $this->renderAssets($assets);

        return <<<HTML
<div class="{$classString}">
{$assetsHtml}
</div>
HTML;
    }

    /**
     * Render individual assets.
     */
    private function renderAssets(array $assets): string
    {
        $html = '';

        foreach ($assets as $asset) {
            $value = $this->escape($asset['value'] ?? '');
            $unit = $this->escape($asset['unit'] ?? '');
            $label = $this->escape($asset['label'] ?? '');
            $icon = $asset['icon'] ?? '';

            if (!$value && !$label) {
                continue;
            }

            // Icon HTML (optional)
            $iconHtml = '';
            if ($icon) {
                $iconHtml = "<span class=\"pd-asset__icon pd-icon {$icon}\"></span>\n";
            }

            // Unit HTML (optional)
            $unitHtml = $unit ? "<span class=\"pd-asset__unit\">{$unit}</span>" : '';

            $html .= <<<ASSET
    <div class="pd-asset">
        {$iconHtml}<span class="pd-asset__value">{$value}</span>
        {$unitHtml}
        <span class="pd-asset__label">{$label}</span>
    </div>
ASSET;
        }

        return $html;
    }

    /**
     * Get schema for block editor UI.
     */
    public function getSchema(): array
    {
        return [
            'content' => [
                'assets' => [
                    'type' => 'repeater',
                    'label' => 'Parametry',
                    'min' => 1,
                    'max' => 8,
                    'fields' => [
                        'value' => [
                            'type' => 'text',
                            'label' => 'Wartosc',
                            'required' => true,
                            'placeholder' => 'np. 200, R-N-F, 15',
                        ],
                        'unit' => [
                            'type' => 'text',
                            'label' => 'Jednostka',
                            'placeholder' => 'np. cm³, KM, kg',
                            'help' => 'Opcjonalne - pokazywane obok wartosci',
                        ],
                        'label' => [
                            'type' => 'text',
                            'label' => 'Etykieta',
                            'required' => true,
                            'placeholder' => 'np. Pojemnosc, Skrzynia, Moc',
                        ],
                        'icon' => [
                            'type' => 'select',
                            'label' => 'Ikona (opcjonalna)',
                            'options' => [
                                '' => 'Brak ikony',
                                'pd-icon--engine' => 'Silnik',
                                'pd-icon--speed' => 'Predkosc',
                                'pd-icon--terrain' => 'Teren',
                                'pd-icon--wallet' => 'Ekonomia',
                                'pd-icon--wrench' => 'Serwis',
                                'pd-icon--shield' => 'Bezpieczenstwo',
                                'pd-icon--comfort' => 'Komfort',
                                'pd-icon--quality' => 'Jakosc',
                            ],
                            'default' => '',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'layout' => [
                    'type' => 'select',
                    'label' => 'Uklad',
                    'options' => [
                        'horizontal' => 'Poziomy (wiersz)',
                        'vertical' => 'Pionowy (kolumna)',
                        'grid' => 'Siatka',
                    ],
                    'default' => 'horizontal',
                ],
                'columns' => [
                    'type' => 'select',
                    'label' => 'Kolumny (dla siatki)',
                    'options' => [
                        2 => '2 kolumny',
                        3 => '3 kolumny',
                        4 => '4 kolumny',
                        5 => '5 kolumn',
                    ],
                    'default' => 4,
                    'condition' => ['layout' => 'grid'],
                ],
                'size' => [
                    'type' => 'select',
                    'label' => 'Rozmiar',
                    'options' => [
                        'small' => 'Maly',
                        'medium' => 'Sredni',
                        'large' => 'Duzy',
                    ],
                    'default' => 'medium',
                ],
                'show_dividers' => [
                    'type' => 'boolean',
                    'label' => 'Separatory',
                    'default' => false,
                    'help' => 'Pionowe linie miedzy parametrami',
                ],
                'center_text' => [
                    'type' => 'boolean',
                    'label' => 'Wycentruj tekst',
                    'default' => true,
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
<div class="block-preview block-preview--pd-asset-list">
    <div class="preview-visual">
        <div class="preview-assets">
            <div class="preview-asset">
                <span class="preview-value">200</span>
                <span class="preview-unit">cm³</span>
            </div>
            <div class="preview-asset">
                <span class="preview-value">R-N-F</span>
            </div>
            <div class="preview-asset">
                <span class="preview-value">15</span>
                <span class="preview-unit">KM</span>
            </div>
        </div>
    </div>
    <div class="preview-info">
        <span class="preview-name">Parametry</span>
        <span class="preview-desc">Kluczowe wartosci</span>
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
                'assets' => [
                    [
                        'value' => '200',
                        'unit' => 'cm³',
                        'label' => 'Pojemnosc',
                        'icon' => 'pd-icon--engine',
                    ],
                    [
                        'value' => 'R-N-F',
                        'unit' => '',
                        'label' => 'Skrzynia',
                        'icon' => '',
                    ],
                    [
                        'value' => '15',
                        'unit' => 'KM',
                        'label' => 'Moc',
                        'icon' => 'pd-icon--speed',
                    ],
                    [
                        'value' => '210',
                        'unit' => 'kg',
                        'label' => 'Masa',
                        'icon' => '',
                    ],
                ],
            ],
            'settings' => $this->defaultSettings,
        ];
    }
}
