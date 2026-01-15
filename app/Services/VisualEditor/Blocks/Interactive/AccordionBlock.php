<?php

namespace App\Services\VisualEditor\Blocks\Interactive;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Accordion Block - Collapsible sections.
 *
 * Perfect for FAQ, specifications, or content organization.
 */
class AccordionBlock extends BaseBlock
{
    public string $type = 'accordion';
    public string $name = 'Akordeon';
    public string $icon = 'heroicons-bars-3-bottom-left';
    public string $category = 'interactive';

    /**
     * ETAP_07h PP.3: Property Panel controls for AccordionBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'background'],
        '.pd-accordion__title' => ['typography', 'color-picker', 'box-model'],
        '.pd-accordion__item' => ['box-model', 'background', 'border'],
        '.pd-accordion__header' => ['typography', 'color-picker', 'background', 'box-model'],
        '.pd-accordion__icon' => ['color-picker', 'size'],
        '.pd-accordion__heading' => ['typography', 'color-picker'],
        '.pd-accordion__panel' => ['background', 'box-model'],
        '.pd-accordion__content' => ['typography', 'color-picker', 'box-model'],
    ];

    public array $defaultSettings = [
        'allow_multiple' => false,
        'default_open' => 0, // Index of default open item, -1 for all closed
        'style' => 'default', // default, bordered, minimal
        'icon_position' => 'right', // left, right
        'icon_type' => 'chevron', // chevron, plus, arrow
        'spacing' => '0.5rem',
        'border_radius' => 'var(--pd-border-radius)',
    ];

    /**
     * Icon SVGs for different states.
     */
    private array $icons = [
        'chevron' => [
            'closed' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>',
            'open' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>',
        ],
        'plus' => [
            'closed' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>',
            'open' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" /></svg>',
        ],
        'arrow' => [
            'closed' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>',
            'open' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>',
        ],
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $items = $content['items'] ?? [];
        $title = $this->escape($content['title'] ?? '');

        if (empty($items)) {
            return '<!-- Accordion: no items provided -->';
        }

        // Container classes
        $containerClasses = $this->classNames([
            'pd-block',
            'pd-accordion',
            "pd-accordion--{$settings['style']}",
            "pd-accordion--icon-{$settings['icon_position']}",
        ]);

        // Data attributes
        $dataAttrs = $settings['allow_multiple']
            ? 'data-allow-multiple="true"'
            : 'data-allow-multiple="false"';

        // Title HTML
        $titleHtml = '';
        if ($title) {
            $titleHtml = "<h3 class=\"pd-accordion__title\">{$title}</h3>";
        }

        // Build items
        $itemsHtml = $this->buildItemsHtml($items, $settings);

        // Container style
        $containerStyle = $this->inlineStyles([
            '--accordion-spacing' => $settings['spacing'],
            '--accordion-radius' => $settings['border_radius'],
        ]);

        return <<<HTML
        <div class="{$containerClasses}" style="{$containerStyle}" {$dataAttrs}>
            {$titleHtml}
            <div class="pd-accordion__items">
                {$itemsHtml}
            </div>
        </div>
        HTML;
    }

    /**
     * Build accordion items HTML.
     */
    private function buildItemsHtml(array $items, array $settings): string
    {
        $html = '';
        $iconType = $settings['icon_type'];

        foreach ($items as $index => $item) {
            $heading = $this->escape($item['heading'] ?? "Element " . ($index + 1));
            $content = $item['content'] ?? '';

            // Check if this item should be open by default
            $isOpen = $settings['default_open'] === $index;
            $openClass = $isOpen ? 'pd-accordion__item--open' : '';
            $hiddenAttr = $isOpen ? '' : 'hidden';
            $ariaExpanded = $isOpen ? 'true' : 'false';

            // Icons
            $iconClosed = $this->icons[$iconType]['closed'] ?? $this->icons['chevron']['closed'];
            $iconOpen = $this->icons[$iconType]['open'] ?? $this->icons['chevron']['open'];

            $html .= <<<HTML
            <div class="pd-accordion__item {$openClass}">
                <button type="button" class="pd-accordion__header" aria-expanded="{$ariaExpanded}">
                    <span class="pd-accordion__heading">{$heading}</span>
                    <span class="pd-accordion__icon pd-accordion__icon--closed">{$iconClosed}</span>
                    <span class="pd-accordion__icon pd-accordion__icon--open">{$iconOpen}</span>
                </button>
                <div class="pd-accordion__panel" {$hiddenAttr}>
                    <div class="pd-accordion__content">
                        {$content}
                    </div>
                </div>
            </div>
            HTML;
        }

        return $html;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Tytul akordeonu',
                    'required' => false,
                ],
                'items' => [
                    'type' => 'repeater',
                    'label' => 'Elementy',
                    'fields' => [
                        'heading' => [
                            'type' => 'text',
                            'label' => 'Naglowek',
                        ],
                        'content' => [
                            'type' => 'richtext',
                            'label' => 'Tresc',
                        ],
                    ],
                ],
            ],
            'settings' => [
                'allow_multiple' => [
                    'type' => 'boolean',
                    'label' => 'Pozwol na wiele otwartych',
                    'default' => false,
                ],
                'default_open' => [
                    'type' => 'number',
                    'label' => 'Domyslnie otwarty (indeks)',
                    'min' => -1,
                    'default' => 0,
                ],
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl',
                    'options' => [
                        'default' => 'Domyslny',
                        'bordered' => 'Z obramowaniem',
                        'minimal' => 'Minimalny',
                    ],
                    'default' => 'default',
                ],
                'icon_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja ikony',
                    'options' => [
                        'left' => 'Lewa',
                        'right' => 'Prawa',
                    ],
                    'default' => 'right',
                ],
                'icon_type' => [
                    'type' => 'select',
                    'label' => 'Typ ikony',
                    'options' => [
                        'chevron' => 'Strzalka',
                        'plus' => 'Plus/Minus',
                        'arrow' => 'Strzalka boczna',
                    ],
                    'default' => 'chevron',
                ],
                'spacing' => [
                    'type' => 'text',
                    'label' => 'Odstepy',
                    'default' => '0.5rem',
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
