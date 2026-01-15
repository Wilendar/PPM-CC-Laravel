<?php

namespace Database\Seeders;

use App\Models\DescriptionBlock;
use Illuminate\Database\Seeder;

/**
 * Seeder for Visual Description Editor blocks
 *
 * Creates default block definitions available in the editor.
 * Blocks are organized by category: layout, content, media, interactive.
 */
class DescriptionBlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = $this->getBlockDefinitions();

        foreach ($blocks as $block) {
            DescriptionBlock::updateOrCreate(
                ['type' => $block['type']],
                $block
            );
        }

        $this->command->info('Created ' . count($blocks) . ' description blocks');
    }

    private function getBlockDefinitions(): array
    {
        return [
            // ==================
            // LAYOUT BLOCKS
            // ==================
            [
                'name' => 'Hero Banner',
                'type' => 'hero-banner',
                'category' => DescriptionBlock::CATEGORY_LAYOUT,
                'icon' => 'heroicons-rectangle-stack',
                'sort_order' => 10,
                'default_settings' => [
                    'height' => '400px',
                    'overlay' => true,
                    'overlay_opacity' => 0.4,
                    'text_position' => 'center',
                    'text_color' => '#ffffff',
                ],
                'schema' => [
                    'content' => [
                        'image' => ['type' => 'image', 'label' => 'Obraz tla'],
                        'title' => ['type' => 'text', 'label' => 'Tytul'],
                        'subtitle' => ['type' => 'text', 'label' => 'Podtytul'],
                    ],
                    'settings' => [
                        'height' => ['type' => 'text', 'label' => 'Wysokosc', 'default' => '400px'],
                        'overlay' => ['type' => 'boolean', 'label' => 'Nakladka'],
                        'overlay_opacity' => ['type' => 'range', 'label' => 'Przezroczystosc', 'min' => 0, 'max' => 1, 'step' => 0.1],
                        'text_position' => ['type' => 'select', 'label' => 'Pozycja tekstu', 'options' => ['left', 'center', 'right']],
                    ],
                ],
            ],
            [
                'name' => 'Dwie kolumny',
                'type' => 'two-column',
                'category' => DescriptionBlock::CATEGORY_LAYOUT,
                'icon' => 'heroicons-view-columns',
                'sort_order' => 20,
                'default_settings' => [
                    'ratio' => '50-50',
                    'gap' => '2rem',
                    'reverse_mobile' => false,
                ],
                'schema' => [
                    'content' => [
                        'left' => ['type' => 'blocks', 'label' => 'Lewa kolumna'],
                        'right' => ['type' => 'blocks', 'label' => 'Prawa kolumna'],
                    ],
                    'settings' => [
                        'ratio' => ['type' => 'select', 'label' => 'Proporcje', 'options' => ['50-50', '60-40', '40-60', '70-30', '30-70']],
                        'gap' => ['type' => 'text', 'label' => 'Odstep'],
                        'reverse_mobile' => ['type' => 'boolean', 'label' => 'Odwroc na mobile'],
                    ],
                ],
            ],
            [
                'name' => 'Trzy kolumny',
                'type' => 'three-column',
                'category' => DescriptionBlock::CATEGORY_LAYOUT,
                'icon' => 'heroicons-squares-2x2',
                'sort_order' => 30,
                'default_settings' => [
                    'ratio' => '33-33-33',
                    'gap' => '2rem',
                    'stack_on_mobile' => true,
                ],
                'schema' => [
                    'content' => [
                        'col1' => ['type' => 'blocks', 'label' => 'Kolumna 1'],
                        'col2' => ['type' => 'blocks', 'label' => 'Kolumna 2'],
                        'col3' => ['type' => 'blocks', 'label' => 'Kolumna 3'],
                    ],
                    'settings' => [
                        'ratio' => ['type' => 'select', 'label' => 'Proporcje', 'options' => ['33-33-33', '25-50-25', '50-25-25']],
                        'gap' => ['type' => 'text', 'label' => 'Odstep'],
                        'stack_on_mobile' => ['type' => 'boolean', 'label' => 'Stosuj na mobile'],
                    ],
                ],
            ],
            [
                'name' => 'Sekcja siatki',
                'type' => 'grid-section',
                'category' => DescriptionBlock::CATEGORY_LAYOUT,
                'icon' => 'heroicons-squares-plus',
                'sort_order' => 40,
                'default_settings' => [
                    'columns' => 3,
                    'rows' => 'auto',
                    'gap' => '1.5rem',
                ],
                'schema' => [
                    'content' => [
                        'items' => ['type' => 'blocks_array', 'label' => 'Elementy siatki'],
                    ],
                    'settings' => [
                        'columns' => ['type' => 'number', 'label' => 'Kolumny', 'min' => 1, 'max' => 6],
                        'gap' => ['type' => 'text', 'label' => 'Odstep'],
                    ],
                ],
            ],
            [
                'name' => 'Pelna szerokosc',
                'type' => 'full-width',
                'category' => DescriptionBlock::CATEGORY_LAYOUT,
                'icon' => 'arrows-pointing-out',
                'sort_order' => 50,
                'default_settings' => [
                    'background_color' => 'transparent',
                    'padding' => '2rem 0',
                ],
                'schema' => [
                    'content' => [
                        'inner' => ['type' => 'blocks', 'label' => 'Zawartosc'],
                    ],
                    'settings' => [
                        'background_color' => ['type' => 'color', 'label' => 'Kolor tla'],
                        'background_image' => ['type' => 'image', 'label' => 'Obraz tla'],
                        'padding' => ['type' => 'text', 'label' => 'Padding'],
                    ],
                ],
            ],

            // ==================
            // CONTENT BLOCKS
            // ==================
            [
                'name' => 'Naglowek',
                'type' => 'heading',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-h1',
                'sort_order' => 100,
                'default_settings' => [
                    'level' => 'h2',
                    'alignment' => 'left',
                    'style' => 'default',
                ],
                'schema' => [
                    'content' => [
                        'text' => ['type' => 'text', 'label' => 'Tekst naglowka'],
                        'subtitle' => ['type' => 'text', 'label' => 'Podtytul'],
                    ],
                    'settings' => [
                        'level' => ['type' => 'select', 'label' => 'Poziom', 'options' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']],
                        'alignment' => ['type' => 'select', 'label' => 'Wyrownanie', 'options' => ['left', 'center', 'right']],
                        'style' => ['type' => 'select', 'label' => 'Styl', 'options' => ['default', 'underline', 'accent']],
                    ],
                ],
            ],
            [
                'name' => 'Tekst',
                'type' => 'text',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-document-text',
                'sort_order' => 110,
                'default_settings' => [
                    'alignment' => 'left',
                    'columns' => 1,
                ],
                'schema' => [
                    'content' => [
                        'html' => ['type' => 'richtext', 'label' => 'Tresc'],
                    ],
                    'settings' => [
                        'alignment' => ['type' => 'select', 'label' => 'Wyrownanie', 'options' => ['left', 'center', 'right', 'justify']],
                        'columns' => ['type' => 'select', 'label' => 'Kolumny tekstu', 'options' => [1, 2, 3]],
                    ],
                ],
            ],
            [
                'name' => 'Karta funkcji',
                'type' => 'feature-card',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-identification',
                'sort_order' => 120,
                'default_settings' => [
                    'layout' => 'image-top',
                    'image_ratio' => '16:9',
                ],
                'schema' => [
                    'content' => [
                        'image' => ['type' => 'image', 'label' => 'Obraz'],
                        'icon' => ['type' => 'icon', 'label' => 'Ikona (zamiast obrazu)'],
                        'title' => ['type' => 'text', 'label' => 'Tytul'],
                        'description' => ['type' => 'textarea', 'label' => 'Opis'],
                    ],
                    'settings' => [
                        'layout' => ['type' => 'select', 'label' => 'Uklad', 'options' => ['image-top', 'image-left', 'image-right']],
                        'image_ratio' => ['type' => 'select', 'label' => 'Proporcje obrazu', 'options' => ['1:1', '4:3', '16:9', '21:9']],
                    ],
                ],
            ],
            [
                'name' => 'Tabela specyfikacji',
                'type' => 'spec-table',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-table-cells',
                'sort_order' => 130,
                'default_settings' => [
                    'columns' => 2,
                    'header_style' => 'bold',
                    'striped' => true,
                ],
                'schema' => [
                    'content' => [
                        'rows' => ['type' => 'key_value_array', 'label' => 'Wiersze (klucz-wartosc)'],
                    ],
                    'settings' => [
                        'columns' => ['type' => 'select', 'label' => 'Kolumny', 'options' => [1, 2]],
                        'header_style' => ['type' => 'select', 'label' => 'Styl naglowka', 'options' => ['bold', 'normal', 'uppercase']],
                        'striped' => ['type' => 'boolean', 'label' => 'Zebra'],
                    ],
                ],
            ],
            [
                'name' => 'Lista zalet',
                'type' => 'merit-list',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-check-badge',
                'sort_order' => 140,
                'default_settings' => [
                    'layout' => 'vertical',
                    'icon' => 'check',
                    'icon_color' => '#22c55e',
                ],
                'schema' => [
                    'content' => [
                        'items' => ['type' => 'array', 'label' => 'Elementy', 'item_schema' => [
                            'icon' => ['type' => 'icon', 'label' => 'Ikona'],
                            'title' => ['type' => 'text', 'label' => 'Tytul'],
                            'description' => ['type' => 'text', 'label' => 'Opis'],
                        ]],
                    ],
                    'settings' => [
                        'layout' => ['type' => 'select', 'label' => 'Uklad', 'options' => ['vertical', 'horizontal', 'grid']],
                        'icon' => ['type' => 'select', 'label' => 'Ikona', 'options' => ['check', 'star', 'arrow', 'plus']],
                        'icon_color' => ['type' => 'color', 'label' => 'Kolor ikony'],
                    ],
                ],
            ],
            [
                'name' => 'Karta informacyjna',
                'type' => 'info-card',
                'category' => DescriptionBlock::CATEGORY_CONTENT,
                'icon' => 'heroicons-information-circle',
                'sort_order' => 150,
                'default_settings' => [
                    'image_position' => 'left',
                    'image_ratio' => '1:1',
                    'show_cta' => false,
                ],
                'schema' => [
                    'content' => [
                        'image' => ['type' => 'image', 'label' => 'Obraz'],
                        'title' => ['type' => 'text', 'label' => 'Tytul'],
                        'description' => ['type' => 'richtext', 'label' => 'Opis'],
                        'cta_text' => ['type' => 'text', 'label' => 'Tekst przycisku'],
                        'cta_url' => ['type' => 'url', 'label' => 'URL przycisku'],
                    ],
                    'settings' => [
                        'image_position' => ['type' => 'select', 'label' => 'Pozycja obrazu', 'options' => ['left', 'right', 'top', 'bottom']],
                        'image_ratio' => ['type' => 'select', 'label' => 'Proporcje obrazu', 'options' => ['1:1', '4:3', '16:9']],
                        'show_cta' => ['type' => 'boolean', 'label' => 'Pokaz przycisk CTA'],
                    ],
                ],
            ],

            // ==================
            // MEDIA BLOCKS
            // ==================
            [
                'name' => 'Obraz',
                'type' => 'image',
                'category' => DescriptionBlock::CATEGORY_MEDIA,
                'icon' => 'heroicons-photo',
                'sort_order' => 200,
                'default_settings' => [
                    'size' => 'full',
                    'alignment' => 'center',
                    'lightbox' => true,
                ],
                'schema' => [
                    'content' => [
                        'src' => ['type' => 'image', 'label' => 'Obraz'],
                        'alt' => ['type' => 'text', 'label' => 'Tekst alternatywny'],
                        'caption' => ['type' => 'text', 'label' => 'Podpis'],
                        'link' => ['type' => 'url', 'label' => 'Link'],
                    ],
                    'settings' => [
                        'size' => ['type' => 'select', 'label' => 'Rozmiar', 'options' => ['small', 'medium', 'large', 'full']],
                        'alignment' => ['type' => 'select', 'label' => 'Wyrownanie', 'options' => ['left', 'center', 'right']],
                        'lightbox' => ['type' => 'boolean', 'label' => 'Lightbox'],
                    ],
                ],
            ],
            [
                'name' => 'Galeria',
                'type' => 'image-gallery',
                'category' => DescriptionBlock::CATEGORY_MEDIA,
                'icon' => 'heroicons-squares-2x2',
                'sort_order' => 210,
                'default_settings' => [
                    'columns' => 3,
                    'gap' => '1rem',
                    'lightbox' => true,
                ],
                'schema' => [
                    'content' => [
                        'images' => ['type' => 'images_array', 'label' => 'Obrazy'],
                    ],
                    'settings' => [
                        'columns' => ['type' => 'select', 'label' => 'Kolumny', 'options' => [2, 3, 4, 5, 6]],
                        'gap' => ['type' => 'text', 'label' => 'Odstep'],
                        'lightbox' => ['type' => 'boolean', 'label' => 'Lightbox'],
                        'lazy_load' => ['type' => 'boolean', 'label' => 'Lazy loading'],
                    ],
                ],
            ],
            [
                'name' => 'Wideo',
                'type' => 'video-embed',
                'category' => DescriptionBlock::CATEGORY_MEDIA,
                'icon' => 'heroicons-play-circle',
                'sort_order' => 220,
                'default_settings' => [
                    'autoplay' => false,
                    'controls' => true,
                    'lazy_facade' => true,
                    'ratio' => '16:9',
                ],
                'schema' => [
                    'content' => [
                        'url' => ['type' => 'url', 'label' => 'URL YouTube/Vimeo'],
                        'title' => ['type' => 'text', 'label' => 'Tytul (opcjonalny)'],
                    ],
                    'settings' => [
                        'autoplay' => ['type' => 'boolean', 'label' => 'Autoodtwarzanie'],
                        'controls' => ['type' => 'boolean', 'label' => 'Kontrolki'],
                        'lazy_facade' => ['type' => 'boolean', 'label' => 'Fasada (lazy load)'],
                        'ratio' => ['type' => 'select', 'label' => 'Proporcje', 'options' => ['16:9', '4:3', '1:1', '21:9']],
                    ],
                ],
            ],
            [
                'name' => 'Parallax',
                'type' => 'parallax-image',
                'category' => DescriptionBlock::CATEGORY_MEDIA,
                'icon' => 'heroicons-sparkles',
                'sort_order' => 230,
                'default_settings' => [
                    'speed' => 0.5,
                    'height' => '400px',
                    'overlay' => true,
                ],
                'schema' => [
                    'content' => [
                        'image' => ['type' => 'image', 'label' => 'Obraz'],
                        'text' => ['type' => 'richtext', 'label' => 'Tekst na obrazie'],
                    ],
                    'settings' => [
                        'speed' => ['type' => 'range', 'label' => 'Szybkosc efektu', 'min' => 0.1, 'max' => 1, 'step' => 0.1],
                        'height' => ['type' => 'text', 'label' => 'Wysokosc'],
                        'overlay' => ['type' => 'boolean', 'label' => 'Nakladka'],
                    ],
                ],
            ],

            // ==================
            // INTERACTIVE BLOCKS
            // ==================
            [
                'name' => 'Slider',
                'type' => 'slider',
                'category' => DescriptionBlock::CATEGORY_INTERACTIVE,
                'icon' => 'heroicons-rectangle-group',
                'sort_order' => 300,
                'default_settings' => [
                    'autoplay' => true,
                    'autoplay_interval' => 5000,
                    'arrows' => true,
                    'dots' => true,
                    'slides_per_view' => 1,
                ],
                'schema' => [
                    'content' => [
                        'slides' => ['type' => 'blocks_array', 'label' => 'Slajdy'],
                    ],
                    'settings' => [
                        'autoplay' => ['type' => 'boolean', 'label' => 'Autoodtwarzanie'],
                        'autoplay_interval' => ['type' => 'number', 'label' => 'Interwat (ms)', 'min' => 1000, 'max' => 10000],
                        'arrows' => ['type' => 'boolean', 'label' => 'Strzalki'],
                        'dots' => ['type' => 'boolean', 'label' => 'Kropki'],
                        'slides_per_view' => ['type' => 'select', 'label' => 'Slajdow na widok', 'options' => [1, 2, 3, 4]],
                    ],
                ],
            ],
            [
                'name' => 'Akordeon',
                'type' => 'accordion',
                'category' => DescriptionBlock::CATEGORY_INTERACTIVE,
                'icon' => 'heroicons-bars-3-bottom-left',
                'sort_order' => 310,
                'default_settings' => [
                    'allow_multiple' => false,
                    'default_open' => 0,
                    'icon_position' => 'right',
                ],
                'schema' => [
                    'content' => [
                        'items' => ['type' => 'array', 'label' => 'Sekcje', 'item_schema' => [
                            'title' => ['type' => 'text', 'label' => 'Tytul'],
                            'content' => ['type' => 'richtext', 'label' => 'Zawartosc'],
                        ]],
                    ],
                    'settings' => [
                        'allow_multiple' => ['type' => 'boolean', 'label' => 'Wiele otwartych'],
                        'default_open' => ['type' => 'number', 'label' => 'Domyslnie otwarty (indeks)', 'min' => -1],
                        'icon_position' => ['type' => 'select', 'label' => 'Pozycja ikony', 'options' => ['left', 'right']],
                    ],
                ],
            ],
            [
                'name' => 'Zakladki',
                'type' => 'tabs',
                'category' => DescriptionBlock::CATEGORY_INTERACTIVE,
                'icon' => 'heroicons-folder-open',
                'sort_order' => 320,
                'default_settings' => [
                    'style' => 'default',
                    'position' => 'top',
                    'default_tab' => 0,
                ],
                'schema' => [
                    'content' => [
                        'tabs' => ['type' => 'array', 'label' => 'Zakladki', 'item_schema' => [
                            'title' => ['type' => 'text', 'label' => 'Tytul zakladki'],
                            'icon' => ['type' => 'icon', 'label' => 'Ikona'],
                            'content' => ['type' => 'blocks', 'label' => 'Zawartosc'],
                        ]],
                    ],
                    'settings' => [
                        'style' => ['type' => 'select', 'label' => 'Styl', 'options' => ['default', 'pills', 'underline']],
                        'position' => ['type' => 'select', 'label' => 'Pozycja', 'options' => ['top', 'left', 'bottom']],
                        'default_tab' => ['type' => 'number', 'label' => 'Domyslna zakladka (indeks)', 'min' => 0],
                    ],
                ],
            ],
            [
                'name' => 'Przycisk CTA',
                'type' => 'cta-button',
                'category' => DescriptionBlock::CATEGORY_INTERACTIVE,
                'icon' => 'heroicons-cursor-arrow-rays',
                'sort_order' => 330,
                'default_settings' => [
                    'style' => 'primary',
                    'size' => 'medium',
                    'alignment' => 'center',
                    'full_width' => false,
                ],
                'schema' => [
                    'content' => [
                        'text' => ['type' => 'text', 'label' => 'Tekst przycisku'],
                        'url' => ['type' => 'url', 'label' => 'URL'],
                        'icon' => ['type' => 'icon', 'label' => 'Ikona'],
                    ],
                    'settings' => [
                        'style' => ['type' => 'select', 'label' => 'Styl', 'options' => ['primary', 'secondary', 'outline', 'ghost']],
                        'size' => ['type' => 'select', 'label' => 'Rozmiar', 'options' => ['small', 'medium', 'large']],
                        'alignment' => ['type' => 'select', 'label' => 'Wyrownanie', 'options' => ['left', 'center', 'right']],
                        'full_width' => ['type' => 'boolean', 'label' => 'Pelna szerokosc'],
                        'target' => ['type' => 'select', 'label' => 'Cel', 'options' => ['_self', '_blank']],
                    ],
                ],
            ],
        ];
    }
}
