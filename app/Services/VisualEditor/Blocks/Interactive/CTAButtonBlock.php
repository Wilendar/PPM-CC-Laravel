<?php

namespace App\Services\VisualEditor\Blocks\Interactive;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * CTA Button Block - Call to action button with multiple styles.
 *
 * Supports links, modals, and scroll targets.
 */
class CTAButtonBlock extends BaseBlock
{
    public string $type = 'cta-button';
    public string $name = 'Przycisk CTA';
    public string $icon = 'heroicons-cursor-arrow-rays';
    public string $category = 'interactive';

    /**
     * ETAP_07h PP.3: Property Panel controls for CTAButtonBlock.
     */
    public array $propertyPanelControls = [
        'root' => ['layout-flex', 'box-model'],
        '.pd-cta' => ['button-settings', 'typography', 'color-picker', 'background', 'border', 'effects', 'box-model'],
        '.pd-cta__icon' => ['color-picker', 'size'],
        '.pd-cta__text' => ['typography'],
        '.pd-cta__subtitle' => ['typography', 'color-picker'],
    ];

    public array $defaultSettings = [
        'style' => 'primary', // primary, secondary, outline, ghost
        'size' => 'medium', // small, medium, large
        'full_width' => false,
        'icon' => '',
        'icon_position' => 'left', // left, right
        'alignment' => 'center',
        'target' => '_self', // _self, _blank
        'action_type' => 'link', // link, scroll, modal
        'scroll_target' => '',
        'modal_target' => '',
        'border_radius' => 'var(--pd-border-radius)',
        'custom_bg' => '',
        'custom_text' => '',
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $text = $this->escape($content['text'] ?? 'Kliknij tutaj');
        $link = $this->escape($content['link'] ?? '#');
        $subtitle = $this->escape($content['subtitle'] ?? '');

        // Container for alignment
        $alignStyle = $this->inlineStyles([
            'display' => 'flex',
            'justify-content' => $this->mapAlignment($settings['alignment']),
        ]);

        // Button classes
        $buttonClasses = $this->classNames([
            'pd-cta',
            "pd-cta--{$settings['style']}",
            "pd-cta--{$settings['size']}",
            $settings['full_width'] ? 'pd-cta--full' : '',
            $settings['icon'] ? "pd-cta--icon-{$settings['icon_position']}" : '',
        ]);

        // Custom colors
        $buttonStyle = $this->inlineStyles([
            'border-radius' => $settings['border_radius'],
            'background-color' => $settings['custom_bg'] ?: null,
            'color' => $settings['custom_text'] ?: null,
        ]);

        // Icon HTML
        $iconHtml = '';
        if ($settings['icon']) {
            $iconHtml = "<span class=\"pd-cta__icon\">{$settings['icon']}</span>";
        }

        // Subtitle HTML
        $subtitleHtml = '';
        if ($subtitle) {
            $subtitleHtml = "<span class=\"pd-cta__subtitle\">{$subtitle}</span>";
        }

        // Build button content
        $buttonContent = $settings['icon_position'] === 'left'
            ? "{$iconHtml}<span class=\"pd-cta__text\">{$text}{$subtitleHtml}</span>"
            : "<span class=\"pd-cta__text\">{$text}{$subtitleHtml}</span>{$iconHtml}";

        // Build attributes based on action type
        $attrs = $this->buildActionAttributes($settings, $link);

        return <<<HTML
        <div class="pd-block pd-button-wrapper" style="{$alignStyle}">
            <a href="{$link}" class="{$buttonClasses}" style="{$buttonStyle}" {$attrs}>
                {$buttonContent}
            </a>
        </div>
        HTML;
    }

    /**
     * Map alignment to flex justify-content.
     */
    private function mapAlignment(string $alignment): string
    {
        return match ($alignment) {
            'left' => 'flex-start',
            'center' => 'center',
            'right' => 'flex-end',
            default => 'center',
        };
    }

    /**
     * Build HTML attributes based on action type.
     */
    private function buildActionAttributes(array $settings, string $link): string
    {
        $attrs = [];

        switch ($settings['action_type']) {
            case 'scroll':
                $target = $this->escape($settings['scroll_target']);
                $attrs[] = "data-scroll-to=\"{$target}\"";
                $attrs[] = 'role="button"';
                break;

            case 'modal':
                $target = $this->escape($settings['modal_target']);
                $attrs[] = "data-modal-target=\"{$target}\"";
                $attrs[] = 'role="button"';
                break;

            default: // link
                if ($settings['target'] === '_blank') {
                    $attrs[] = 'target="_blank"';
                    $attrs[] = 'rel="noopener noreferrer"';
                }
                break;
        }

        return implode(' ', $attrs);
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'text' => [
                    'type' => 'text',
                    'label' => 'Tekst przycisku',
                    'required' => true,
                    'default' => 'Kliknij tutaj',
                ],
                'subtitle' => [
                    'type' => 'text',
                    'label' => 'Podtytul (opcjonalny)',
                    'required' => false,
                ],
                'link' => [
                    'type' => 'url',
                    'label' => 'Link',
                    'required' => false,
                    'default' => '#',
                ],
            ],
            'settings' => [
                'style' => [
                    'type' => 'select',
                    'label' => 'Styl',
                    'options' => [
                        'primary' => 'Glowny',
                        'secondary' => 'Drugoplanowy',
                        'outline' => 'Obrys',
                        'ghost' => 'Przezroczysty',
                    ],
                    'default' => 'primary',
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
                'full_width' => [
                    'type' => 'boolean',
                    'label' => 'Pelna szerokosc',
                    'default' => false,
                ],
                'icon' => [
                    'type' => 'icon',
                    'label' => 'Ikona',
                    'default' => '',
                ],
                'icon_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja ikony',
                    'options' => [
                        'left' => 'Lewa',
                        'right' => 'Prawa',
                    ],
                    'default' => 'left',
                ],
                'alignment' => [
                    'type' => 'select',
                    'label' => 'Wyrownanie',
                    'options' => [
                        'left' => 'Do lewej',
                        'center' => 'Wysrodkowane',
                        'right' => 'Do prawej',
                    ],
                    'default' => 'center',
                ],
                'action_type' => [
                    'type' => 'select',
                    'label' => 'Typ akcji',
                    'options' => [
                        'link' => 'Link',
                        'scroll' => 'Przewin do elementu',
                        'modal' => 'Otworz modal',
                    ],
                    'default' => 'link',
                ],
                'target' => [
                    'type' => 'select',
                    'label' => 'Otworz w',
                    'options' => [
                        '_self' => 'Ta sama karta',
                        '_blank' => 'Nowa karta',
                    ],
                    'default' => '_self',
                    'condition' => ['action_type' => 'link'],
                ],
                'scroll_target' => [
                    'type' => 'text',
                    'label' => 'ID elementu docelowego',
                    'default' => '',
                    'placeholder' => '#section-features',
                    'condition' => ['action_type' => 'scroll'],
                ],
                'modal_target' => [
                    'type' => 'text',
                    'label' => 'ID modalu',
                    'default' => '',
                    'placeholder' => '#contact-modal',
                    'condition' => ['action_type' => 'modal'],
                ],
                'border_radius' => [
                    'type' => 'text',
                    'label' => 'Zaokraglenie',
                    'default' => 'var(--pd-border-radius)',
                ],
                'custom_bg' => [
                    'type' => 'color',
                    'label' => 'Wlasny kolor tla',
                    'default' => '',
                ],
                'custom_text' => [
                    'type' => 'color',
                    'label' => 'Wlasny kolor tekstu',
                    'default' => '',
                ],
            ],
        ];
    }
}
