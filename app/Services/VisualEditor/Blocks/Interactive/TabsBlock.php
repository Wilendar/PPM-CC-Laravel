<?php

namespace App\Services\VisualEditor\Blocks\Interactive;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Tabs Block - Tab navigation with content panels.
 *
 * Supports horizontal and vertical layouts with icons.
 */
class TabsBlock extends BaseBlock
{
    public string $type = 'tabs';
    public string $name = 'Zakladki (Tabs)';
    public string $icon = 'heroicons-bookmark-square';
    public string $category = 'interactive';
    public bool $supportsChildren = true;

    /**
     * ETAP_07h PP.3: Property Panel controls for TabsBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['tabs-settings', 'box-model', 'background'],
        '.pd-tabs__list' => ['layout-flex', 'background', 'border', 'box-model'],
        '.pd-tabs__tab' => ['typography', 'color-picker', 'background', 'border', 'box-model'],
        '.pd-tabs__tab--active' => ['typography', 'color-picker', 'background', 'border'],
        '.pd-tabs__icon' => ['color-picker', 'size'],
        '.pd-tabs__label' => ['typography'],
        '.pd-tabs__panels' => ['background', 'box-model'],
        '.pd-tabs__panel' => ['box-model', 'background'],
    ];

    public array $defaultSettings = [
        'style' => 'default', // default, pills, underline
        'position' => 'top', // top, left, right, bottom
        'alignment' => 'start', // start, center, end, stretch
        'default_tab' => 0,
        'tab_width' => 'auto', // auto, equal
        'icon_position' => 'left', // left, top
        'border_radius' => 'var(--pd-border-radius)',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $tabs = $content['tabs'] ?? [];

        if (empty($tabs)) {
            return '<!-- Tabs: no tabs provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-tabs',
            "pd-tabs--{$settings['style']}",
            "pd-tabs--{$settings['position']}",
            "pd-tabs--align-{$settings['alignment']}",
            "pd-tabs--width-{$settings['tab_width']}",
        ]);

        // Build tabs and panels
        $tabListHtml = $this->buildTabListHtml($tabs, $settings);
        $panelsHtml = $this->buildPanelsHtml($tabs, $children, $settings);

        // Container style
        $containerStyle = $this->inlineStyles([
            '--tabs-radius' => $settings['border_radius'],
        ]);

        // Determine layout order based on position
        if (in_array($settings['position'], ['bottom', 'right'])) {
            return <<<HTML
            <div class="{$containerClasses}" style="{$containerStyle}">
                <div class="pd-tabs__panels">
                    {$panelsHtml}
                </div>
                <div class="pd-tabs__list" role="tablist">
                    {$tabListHtml}
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}">
            <div class="pd-tabs__list" role="tablist">
                {$tabListHtml}
            </div>
            <div class="pd-tabs__panels">
                {$panelsHtml}
            </div>
        </div>
        HTML;
    }

    /**
     * Build tab list HTML.
     */
    private function buildTabListHtml(array $tabs, array $settings): string
    {
        $html = '';
        $defaultTab = $settings['default_tab'];

        foreach ($tabs as $index => $tab) {
            $label = $this->escape($tab['label'] ?? "Zakladka " . ($index + 1));
            $icon = $tab['icon'] ?? '';

            $isActive = $index === $defaultTab;
            $activeClass = $isActive ? 'pd-tabs__tab--active' : '';
            $ariaSelected = $isActive ? 'true' : 'false';
            $tabIndex = $isActive ? '0' : '-1';

            // Icon HTML
            $iconHtml = '';
            if ($icon) {
                $iconHtml = "<span class=\"pd-tabs__icon\">{$icon}</span>";
            }

            // Icon position class
            $iconPosClass = $icon && $settings['icon_position'] === 'top'
                ? 'pd-tabs__tab--icon-top'
                : '';

            $html .= <<<HTML
            <button
                type="button"
                class="pd-tabs__tab {$activeClass} {$iconPosClass}"
                role="tab"
                aria-selected="{$ariaSelected}"
                aria-controls="pd-tab-panel-{$index}"
                tabindex="{$tabIndex}"
                data-tab-index="{$index}"
            >
                {$iconHtml}
                <span class="pd-tabs__label">{$label}</span>
            </button>
            HTML;
        }

        return $html;
    }

    /**
     * Build tab panels HTML.
     */
    private function buildPanelsHtml(array $tabs, array $children, array $settings): string
    {
        $html = '';
        $defaultTab = $settings['default_tab'];

        foreach ($tabs as $index => $tab) {
            $isActive = $index === $defaultTab;
            $activeClass = $isActive ? 'pd-tabs__panel--active' : '';
            $hiddenAttr = $isActive ? '' : 'hidden';

            // Content: either from children (nested blocks) or tab content
            $content = '';
            if (isset($children[$index])) {
                $content = $children[$index];
            } elseif (isset($tab['content'])) {
                $content = $tab['content'];
            }

            $html .= <<<HTML
            <div
                class="pd-tabs__panel {$activeClass}"
                role="tabpanel"
                id="pd-tab-panel-{$index}"
                data-tab-index="{$index}"
                {$hiddenAttr}
            >
                {$content}
            </div>
            HTML;
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'tabs' => [
                    'type' => 'repeater',
                    'label' => 'Zakladki',
                    'fields' => [
                        'label' => [
                            'type' => 'text',
                            'label' => 'Etykieta',
                        ],
                        'icon' => [
                            'type' => 'icon',
                            'label' => 'Ikona (opcjonalna)',
                        ],
                        'content' => [
                            'type' => 'richtext',
                            'label' => 'Tresc',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl',
                    'options' => [
                        'default' => 'Domyslny',
                        'pills' => 'Pilulki',
                        'underline' => 'Podkreslenie',
                    ],
                    'default' => 'default',
                ],
                'position' => [
                    'type' => 'select',
                    'label' => 'Pozycja zakladek',
                    'options' => [
                        'top' => 'Gora',
                        'bottom' => 'Dol',
                        'left' => 'Lewa',
                        'right' => 'Prawa',
                    ],
                    'default' => 'top',
                ],
                'alignment' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie',
                    'options' => [
                        'start' => 'Poczatek',
                        'center' => 'Srodek',
                        'end' => 'Koniec',
                        'stretch' => 'Rozciagnij',
                    ],
                    'default' => 'start',
                ],
                'default_tab' => [
                    'type' => 'number',
                    'label' => 'Domyslna zakladka (indeks)',
                    'min' => 0,
                    'default' => 0,
                ],
                'tab_width' => [
                    'type' => 'select',
                    'label' => 'Szerokosc zakladek',
                    'options' => [
                        'auto' => 'Automatyczna',
                        'equal' => 'Rowna',
                    ],
                    'default' => 'auto',
                ],
                'icon_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja ikony',
                    'options' => [
                        'left' => 'Obok tekstu',
                        'top' => 'Nad tekstem',
                    ],
                    'default' => 'left',
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
            ],
        ];
    }
}
