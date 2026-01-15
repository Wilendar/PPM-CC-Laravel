<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks\PrestaShop;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * PrestaShop Merits Block - Lista Zalet z ikonami.
 *
 * Renderuje HTML zgodny z PrestaShop custom.css (klasy pd-merits, pd-merit).
 * Uzywany do prezentacji zalet produktu w formie kart z ikonami.
 *
 * HTML Output:
 * <div class="pd-merits pd-merits--dividers">
 *     <div class="pd-merit">
 *         <span class="pd-icon pd-icon--wallet"></span>
 *         <h4>Ekonomia</h4>
 *         <p>Oszczednosc paliwa...</p>
 *     </div>
 *     ...
 * </div>
 */
class PdMeritsBlock extends BaseBlock
{
    public string $type = 'pd-merits';
    public string $name = 'Lista Zalet';
    public string $icon = 'heroicons-check-badge';
    public string $category = 'prestashop';
    public string $description = 'Karty zalet z ikonami i opisami (pd-merits)';

    /**
     * ETAP_07h PP.3: Property Panel controls for PdMeritsBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['list-settings', 'layout-grid', 'box-model', 'background'],
        '.pd-merit' => ['box-model', 'background', 'border'],
        '.pd-icon' => ['color-picker', 'size'],
        '.pd-merit h4' => ['typography', 'color-picker'],
        '.pd-merit p' => ['typography', 'color-picker'],
    ];

    public array $defaultSettings = [
        'columns' => 3,
        'show_dividers' => true,
        'background' => 'transparent',
        'text_align' => 'center',
    ];

    /**
     * Available PrestaShop pd-icon classes.
     */
    private array $availableIcons = [
        'pd-icon--wallet' => 'Portfel (ekonomia)',
        'pd-icon--wrench' => 'Klucz (serwis)',
        'pd-icon--shield' => 'Tarcza (bezpieczenstwo)',
        'pd-icon--engine' => 'Silnik',
        'pd-icon--terrain' => 'Teren',
        'pd-icon--speed' => 'Predkosc',
        'pd-icon--comfort' => 'Komfort',
        'pd-icon--quality' => 'Jakosc',
        'pd-icon--warranty' => 'Gwarancja',
        'pd-icon--delivery' => 'Dostawa',
        'pd-icon--support' => 'Wsparcie',
        'pd-icon--check' => 'Ptaszek',
    ];

    /**
     * Render block - generates HTML compatible with PrestaShop custom.css.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);
        $items = $content['items'] ?? [];

        if (empty($items)) {
            return '';
        }

        // Build CSS classes
        $classes = ['pd-merits'];
        if ($settings['show_dividers']) {
            $classes[] = 'pd-merits--dividers';
        }
        if ($settings['columns'] && $settings['columns'] !== 3) {
            $classes[] = "pd-merits--cols-{$settings['columns']}";
        }

        $classString = implode(' ', $classes);

        // Build items HTML
        $itemsHtml = $this->renderItems($items, $settings);

        return <<<HTML
<div class="{$classString}">
{$itemsHtml}
</div>
HTML;
    }

    /**
     * Render merit items.
     */
    private function renderItems(array $items, array $settings): string
    {
        $html = '';

        foreach ($items as $item) {
            $iconClass = $item['icon'] ?? 'pd-icon--check';
            $title = $this->escape($item['title'] ?? '');
            $description = $item['description'] ?? '';

            // Sanitize description but allow basic HTML
            $description = strip_tags($description, '<strong><em><b><i><br><a>');

            $html .= <<<HTML
    <div class="pd-merit">
        <span class="pd-icon {$iconClass}"></span>
        <h4>{$title}</h4>
        <p>{$description}</p>
    </div>
HTML;
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
                'items' => [
                    'type' => 'repeater',
                    'label' => 'Zalety',
                    'min' => 1,
                    'max' => 6,
                    'fields' => [
                        'icon' => [
                            'type' => 'select',
                            'label' => 'Ikona',
                            'options' => $this->availableIcons,
                            'default' => 'pd-icon--check',
                        ],
                        'title' => [
                            'type' => 'text',
                            'label' => 'Tytul',
                            'required' => true,
                            'placeholder' => 'np. Ekonomia',
                        ],
                        'description' => [
                            'type' => 'textarea',
                            'label' => 'Opis',
                            'rows' => 3,
                            'placeholder' => 'Krotki opis zalety...',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'columns' => [
                    'type' => 'select',
                    'label' => 'Liczba kolumn',
                    'options' => [
                        2 => '2 kolumny',
                        3 => '3 kolumny',
                        4 => '4 kolumny',
                    ],
                    'default' => 3,
                ],
                'show_dividers' => [
                    'type' => 'boolean',
                    'label' => 'Pokazuj separatory',
                    'default' => true,
                    'help' => 'Dodaje pionowe linie miedzy kartami',
                ],
                'background' => [
                    'type' => 'select',
                    'label' => 'Tlo',
                    'options' => [
                        'transparent' => 'Przezroczyste',
                        'light' => 'Jasne (#f6f6f6)',
                        'white' => 'Biale',
                    ],
                    'default' => 'transparent',
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
            ],
        ];
    }

    /**
     * Get preview for block palette - visual representation.
     */
    public function getPreview(): string
    {
        return <<<'HTML'
<div class="block-preview block-preview--pd-merits">
    <div class="preview-visual">
        <div class="preview-cards">
            <div class="preview-card">
                <span class="preview-icon">&#10003;</span>
                <span class="preview-label">Zaleta 1</span>
            </div>
            <div class="preview-card">
                <span class="preview-icon">&#10003;</span>
                <span class="preview-label">Zaleta 2</span>
            </div>
            <div class="preview-card">
                <span class="preview-icon">&#10003;</span>
                <span class="preview-label">Zaleta 3</span>
            </div>
        </div>
    </div>
    <div class="preview-info">
        <span class="preview-name">Lista Zalet</span>
        <span class="preview-desc">Karty z ikonami i opisem</span>
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
                'items' => [
                    [
                        'icon' => 'pd-icon--check',
                        'title' => 'Zaleta 1',
                        'description' => 'Opis pierwszej zalety produktu.',
                    ],
                    [
                        'icon' => 'pd-icon--check',
                        'title' => 'Zaleta 2',
                        'description' => 'Opis drugiej zalety produktu.',
                    ],
                    [
                        'icon' => 'pd-icon--check',
                        'title' => 'Zaleta 3',
                        'description' => 'Opis trzeciej zalety produktu.',
                    ],
                ],
            ],
            'settings' => $this->defaultSettings,
        ];
    }

    /**
     * Get available icons for UI.
     */
    public function getAvailableIcons(): array
    {
        return $this->availableIcons;
    }
}
