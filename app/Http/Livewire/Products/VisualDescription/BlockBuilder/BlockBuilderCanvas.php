<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\BlockBuilder;

use App\Models\BlockDefinition;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\BlockDocumentToHtmlExporter;
use App\Services\VisualEditor\HtmlToBlockDocumentParser;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * BlockBuilderCanvas - Visual Block Builder main component.
 *
 * Elementor-style visual editor for creating and editing block definitions.
 * Part of ETAP_07f_P4: Visual Block Builder.
 */
class BlockBuilderCanvas extends Component
{
    // Modal state
    public bool $show = false;
    public int $shopId = 0;
    public ?int $definitionId = null;

    // Block metadata
    public string $blockName = '';
    public string $blockType = '';
    public string $blockIcon = 'heroicons-cube';
    public string $blockDescription = '';

    // Document state (JSON structure for visual editing)
    public array $document = [];

    // Selection state
    public ?string $selectedElementId = null;

    // History state for undo/redo
    public array $history = [];
    public int $historyIndex = -1;
    public int $maxHistorySize = 50;

    // Clipboard
    public ?array $clipboard = null;

    // UI state
    public string $activePanel = 'elements'; // elements, properties, layers

    protected $listeners = [
        'openBlockBuilder' => 'open',
        'openBlockBuilderEdit' => 'openEdit',
    ];

    /**
     * Open the builder for creating a new block.
     */
    public function open(int $shopId, ?string $sourceHtml = null): void
    {
        $this->reset();
        $this->shopId = $shopId;
        $this->show = true;

        // Initialize empty document
        $this->initializeDocument();

        // If source HTML provided, convert it to document structure
        if ($sourceHtml) {
            $this->importFromHtml($sourceHtml);
        }

        $this->pushHistory();
    }

    /**
     * Open the builder for editing an existing block.
     */
    public function openEdit(int $definitionId): void
    {
        $this->reset();

        $definition = BlockDefinition::find($definitionId);
        if (!$definition) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono definicji bloku');
            return;
        }

        $this->definitionId = $definitionId;
        $this->shopId = $definition->shop_id;
        $this->blockName = $definition->name;
        $this->blockType = $definition->type;
        $this->blockIcon = $definition->icon ?? 'heroicons-cube';
        $this->blockDescription = $definition->description ?? '';

        // Load document from builder_document or initialize from render_template
        if (!empty($definition->builder_document)) {
            $this->document = $definition->builder_document;
        } else {
            $this->initializeDocument();
            // Try to convert render_template to document structure
            if (!empty($definition->render_template)) {
                $this->importFromHtml($definition->render_template);
            }
        }

        $this->show = true;
        $this->pushHistory();
    }

    /**
     * Close the builder.
     */
    public function close(): void
    {
        $this->show = false;
    }

    /**
     * Initialize empty document structure.
     */
    protected function initializeDocument(): void
    {
        $this->document = [
            'version' => '1.0',
            'root' => [
                'id' => $this->generateElementId(),
                'type' => 'container',
                'tag' => 'div',
                'classes' => ['pd-block', 'grid-row'],
                'styles' => [
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'alignItems' => 'center',
                    'gap' => '1rem',
                    'padding' => '2rem',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'variables' => [],
            'cssClasses' => ['pd-block', 'grid-row'],
        ];
    }

    /**
     * Import HTML and convert to document structure using HtmlToBlockDocumentParser.
     * Converts PrestaShop HTML blocks into editable BlockDocument elements.
     */
    protected function importFromHtml(string $html): void
    {
        try {
            $parser = new HtmlToBlockDocumentParser();
            $parsedDocument = $parser->parse($html, $this->blockName ?: 'Imported Block');

            // Check if parsing produced valid children
            if (!empty($parsedDocument['root']['children'])) {
                // Merge parsed elements into document
                $this->document['root']['children'] = $parsedDocument['root']['children'];
                $this->document['variables'] = array_merge(
                    $this->document['variables'] ?? [],
                    $parsedDocument['variables'] ?? []
                );
                $this->document['cssClasses'] = array_unique(array_merge(
                    $this->document['cssClasses'] ?? [],
                    $parsedDocument['cssClasses'] ?? []
                ));

                Log::info('BlockBuilder: HTML imported successfully', [
                    'elements_count' => count($parsedDocument['root']['children']),
                    'variables_count' => count($parsedDocument['variables'] ?? []),
                ]);
            } else {
                // Fallback: create raw-html element if parsing fails
                $this->createRawHtmlElement($html);
                Log::warning('BlockBuilder: HTML parsing produced no elements, using raw-html fallback');
            }
        } catch (\Exception $e) {
            // Fallback on any error
            $this->createRawHtmlElement($html);
            Log::error('BlockBuilder: HTML import failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create raw-html element as fallback when parsing fails.
     */
    protected function createRawHtmlElement(string $html): void
    {
        $this->document['root']['children'][] = [
            'id' => $this->generateElementId(),
            'type' => 'raw-html',
            'tag' => 'div',
            'content' => $html,
            'classes' => [],
            'styles' => [],
            'children' => [],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Generate unique element ID.
     */
    protected function generateElementId(): string
    {
        return 'el-' . Str::random(8);
    }

    /**
     * Import HTML content from user input (called from UI).
     * Public method for wire:click from Import HTML modal.
     */
    public function importHtmlContent(string $html): void
    {
        if (empty(trim($html))) {
            $this->dispatch('notify', type: 'warning', message: 'Brak kodu HTML do zaimportowania');
            return;
        }

        $this->importFromHtml($html);
        $this->pushHistory();

        $elementsCount = count($this->document['root']['children'] ?? []);
        $this->dispatch('notify', type: 'success', message: "Zaimportowano {$elementsCount} elementow z HTML");
    }

    // ========== TEMPLATE OPERATIONS ==========

    /**
     * Add PrestaShop template to canvas.
     * Templates are predefined element structures with pd-* CSS classes.
     * Styles are automatically resolved from CSS class definitions.
     */
    public function addTemplate(string $templateName): void
    {
        $template = $this->getTemplateDefinition($templateName);
        if (!$template) {
            $this->dispatch('notify', type: 'error', message: "Nieznany szablon: {$templateName}");
            return;
        }

        // Add template elements to root WITHOUT inline styles
        // PrestaShop templates use external CSS classes only - styles are defined in PrestaShop theme CSS
        // NO CssClassStyleResolver - elements keep only their CSS classes
        foreach ($template['elements'] as $element) {
            // Ensure element has no inline styles - only CSS classes
            $cleanElement = $this->ensureNoInlineStyles($element);
            $this->document['root']['children'][] = $cleanElement;
        }

        // Merge CSS classes for documentation/export purposes
        if (!empty($template['cssClasses'])) {
            $this->document['cssClasses'] = array_unique(array_merge(
                $this->document['cssClasses'] ?? [],
                $template['cssClasses']
            ));
        }

        $this->pushHistory();
        $this->dispatch('notify', type: 'success', message: "Dodano szablon: {$template['name']}");

        Log::info('BlockBuilder: Template added', [
            'template' => $templateName,
            'elements_added' => count($template['elements']),
        ]);
    }

    /**
     * Recursively ensure element has no inline styles - only CSS classes.
     * PrestaShop templates must use external CSS only.
     */
    protected function ensureNoInlineStyles(array $element): array
    {
        // Remove styles array - PrestaShop uses external CSS only
        unset($element['styles']);

        // Recursively process children
        if (!empty($element['children'])) {
            $element['children'] = array_map(
                fn($child) => $this->ensureNoInlineStyles($child),
                $element['children']
            );
        }

        return $element;
    }

    /**
     * Get template definition by name.
     */
    protected function getTemplateDefinition(string $templateName): ?array
    {
        $templates = [
            'pd-intro' => $this->getIntroTemplate(),
            'pd-merits' => $this->getMeritsTemplate(),
            'pd-specification' => $this->getSpecificationTemplate(),
            'pd-features' => $this->getFeaturesTemplate(),
            'pd-slider' => $this->getSliderTemplate(),
            'pd-parallax' => $this->getParallaxTemplate(),
            'pd-asset-list' => $this->getAssetListTemplate(),
            'pd-cover' => $this->getCoverTemplate(),
        ];

        return $templates[$templateName] ?? null;
    }

    /**
     * Template: pd-intro - Product intro section matching PrestaShop structure.
     * Original structure: pd-base-grid pd-intro > heading + text + pd-cover + pd-asset-list
     */
    protected function getIntroTemplate(): array
    {
        // Template defines ONLY CSS classes - styles are auto-resolved
        // from PrestaShopCssDefinitions by CssClassStyleResolver
        return [
            'name' => 'Intro (pd-intro)',
            'cssClasses' => ['pd-base-grid', 'pd-intro'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-base-grid', 'pd-intro'],
                    'styles' => [], // Auto-resolved from classes
                    'children' => [
                        // Heading: pd-intro__heading pd-model
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'heading',
                            'tag' => 'h2',
                            'content' => '<span class="pd-model__type">Typ modelu</span> <span class="pd-model__name">Nazwa modelu</span>',
                            'classes' => ['pd-intro__heading', 'pd-model'],
                            'styles' => [], // Auto-resolved from classes
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        // Text: pd-intro__text
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'text',
                            'tag' => 'p',
                            'content' => 'Opis produktu. Krotki tekst wprowadzajacy przedstawiajacy glowne cechy i zalety produktu. Idealna propozycja dla osob poszukujacych nowoczesnego rozwiazania.',
                            'classes' => ['pd-intro__text'],
                            'styles' => [], // Auto-resolved from classes
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        // Cover image: pd-cover grid-row > picture.pd-cover__picture
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['pd-cover', 'grid-row'],
                            'styles' => [], // Auto-resolved from classes
                            'children' => [
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'picture',
                                    'tag' => 'picture',
                                    'classes' => ['pd-cover__picture'],
                                    'styles' => [], // Auto-resolved from classes
                                    'children' => [
                                        [
                                            'id' => $this->generateElementId(),
                                            'type' => 'image',
                                            'tag' => 'img',
                                            'src' => 'https://placehold.co/960x660/374151/9ca3af?text=960x660',
                                            'alt' => 'Zdjecie produktu - widok glowny',
                                            'srcset' => 'https://placehold.co/480x330/374151/9ca3af?text=480w 480w, https://placehold.co/960x660/374151/9ca3af?text=960w 960w, https://placehold.co/1920x1320/374151/9ca3af?text=1920w 1920w',
                                            'sizes' => '(max-width: 960px) 100vw, 960px',
                                            'width' => 960,
                                            'height' => 660,
                                            'classes' => [],
                                            'styles' => ['maxWidth' => '100%', 'height' => 'auto'],
                                            'children' => [],
                                            'visible' => true,
                                            'locked' => false,
                                        ],
                                    ],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                        // Asset list: grid-row bg-brand > pd-asset-list
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['grid-row', 'bg-brand'],
                            'styles' => [], // Auto-resolved from classes
                            'children' => [
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'container',
                                    'tag' => 'ul',
                                    'classes' => ['pd-asset-list'],
                                    'styles' => [], // Auto-resolved from classes
                                    'children' => [
                                        [
                                            'id' => $this->generateElementId(),
                                            'type' => 'text',
                                            'tag' => 'li',
                                            'content' => 'Pojemnosc silnika <b>250cc</b>',
                                            'classes' => [],
                                            'styles' => [],
                                            'children' => [],
                                            'visible' => true,
                                            'locked' => false,
                                        ],
                                        [
                                            'id' => $this->generateElementId(),
                                            'type' => 'text',
                                            'tag' => 'li',
                                            'content' => 'Moc <b>21 KM</b>',
                                            'classes' => [],
                                            'styles' => [],
                                            'children' => [],
                                            'visible' => true,
                                            'locked' => false,
                                        ],
                                        [
                                            'id' => $this->generateElementId(),
                                            'type' => 'text',
                                            'tag' => 'li',
                                            'content' => 'Rozstaw osi <b>1320mm</b>',
                                            'classes' => [],
                                            'styles' => [],
                                            'children' => [],
                                            'visible' => true,
                                            'locked' => false,
                                        ],
                                    ],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Template: pd-merits - Benefits/features cards with icons.
     */
    protected function getMeritsTemplate(): array
    {
        return [
            'name' => 'Lista zalet (pd-merits)',
            'cssClasses' => ['pd-merits', 'pd-merit', 'pd-icon'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-merits'],
                    'styles' => [
                        'display' => 'flex',
                        'flexWrap' => 'wrap',
                        'justifyContent' => 'center',
                        'gap' => '2rem',
                        'padding' => '2rem',
                    ],
                    'children' => [
                        $this->createMeritItem('pd-icon--check', 'Wysoka jakosc', 'Produkty premium z certyfikatem'),
                        $this->createMeritItem('pd-icon--shield', 'Gwarancja', '24 miesiace gwarancji'),
                        $this->createMeritItem('pd-icon--delivery', 'Szybka dostawa', 'Wysylka w 24h'),
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create single merit item for pd-merits template.
     */
    protected function createMeritItem(string $iconClass, string $title, string $description): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'classes' => ['pd-merit'],
            'styles' => [
                'display' => 'flex',
                'flexDirection' => 'column',
                'alignItems' => 'center',
                'gap' => '0.5rem',
                'textAlign' => 'center',
                'maxWidth' => '200px',
            ],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'icon',
                    'tag' => 'span',
                    'iconClass' => $iconClass,
                    'classes' => ['pd-icon', $iconClass],
                    'styles' => [
                        'fontSize' => '2.5rem',
                        'color' => '#e0ac7e',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'heading',
                    'tag' => 'h4',
                    'content' => $title,
                    'classes' => [],
                    'styles' => [
                        'fontSize' => '1.125rem',
                        'fontWeight' => '600',
                        'color' => '#1f2937',
                        'margin' => '0',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'p',
                    'content' => $description,
                    'classes' => [],
                    'styles' => [
                        'fontSize' => '0.875rem',
                        'color' => '#6b7280',
                        'margin' => '0',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Template: pd-specification - Technical specification table.
     */
    protected function getSpecificationTemplate(): array
    {
        return [
            'name' => 'Specyfikacja (pd-specification)',
            'cssClasses' => ['pd-specification', 'pd-specification__table'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-specification'],
                    'styles' => [
                        'padding' => '2rem',
                    ],
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'heading',
                            'tag' => 'h3',
                            'content' => 'Dane techniczne',
                            'classes' => ['pd-specification__title'],
                            'styles' => [
                                'fontSize' => '1.5rem',
                                'fontWeight' => '700',
                                'color' => '#1f2937',
                                'marginBottom' => '1rem',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'table',
                            'classes' => ['pd-specification__table'],
                            'styles' => [
                                'width' => '100%',
                                'borderCollapse' => 'collapse',
                            ],
                            'children' => [
                                $this->createSpecRow('Silnik', '200cm³, 4T, 1 cylinder'),
                                $this->createSpecRow('Moc', '15 KM / 7500 obr./min'),
                                $this->createSpecRow('Skrzynia biegow', 'CVT (automat)'),
                                $this->createSpecRow('Masa wlasna', '210 kg'),
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create specification table row.
     */
    protected function createSpecRow(string $label, string $value): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'tr',
            'classes' => [],
            'styles' => [],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'td',
                    'content' => $label,
                    'classes' => ['pd-specification__label'],
                    'styles' => [
                        'padding' => '0.75rem 1rem',
                        'fontWeight' => '500',
                        'color' => '#374151',
                        'backgroundColor' => '#f9fafb',
                        'borderBottom' => '1px solid #e5e7eb',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'td',
                    'content' => $value,
                    'classes' => ['pd-specification__value'],
                    'styles' => [
                        'padding' => '0.75rem 1rem',
                        'color' => '#1f2937',
                        'borderBottom' => '1px solid #e5e7eb',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Template: pd-features - Feature list with checkmarks.
     */
    protected function getFeaturesTemplate(): array
    {
        return [
            'name' => 'Lista cech (pd-features)',
            'cssClasses' => ['pd-features', 'pd-feature'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-features'],
                    'styles' => [
                        'display' => 'grid',
                        'gridTemplateColumns' => 'repeat(2, 1fr)',
                        'gap' => '1rem',
                        'padding' => '2rem',
                    ],
                    'children' => [
                        $this->createFeatureItem('Wysoka wydajnosc silnika'),
                        $this->createFeatureItem('Ekonomiczny uzytek paliwa'),
                        $this->createFeatureItem('Wygodne siedzenie'),
                        $this->createFeatureItem('Niezawodne hamulce'),
                        $this->createFeatureItem('Solidna konstrukcja ramy'),
                        $this->createFeatureItem('Pelne oswietlenie LED'),
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create single feature item.
     */
    protected function createFeatureItem(string $text): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'classes' => ['pd-feature'],
            'styles' => [
                'display' => 'flex',
                'alignItems' => 'center',
                'gap' => '0.5rem',
            ],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'icon',
                    'tag' => 'span',
                    'iconClass' => 'pd-icon--check',
                    'classes' => ['pd-icon', 'pd-icon--check'],
                    'styles' => [
                        'fontSize' => '1.25rem',
                        'color' => '#059669',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'span',
                    'content' => $text,
                    'classes' => [],
                    'styles' => [
                        'fontSize' => '1rem',
                        'color' => '#374151',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Template: pd-slider - Splide.js carousel for product images/content.
     * Based on PrestaShop pd-slider structure with Splide.js integration.
     */
    protected function getSliderTemplate(): array
    {
        return [
            'name' => 'Slider/Karuzela (pd-slider)',
            'cssClasses' => ['pd-slider', 'splide', 'splide__track', 'splide__list', 'splide__slide'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-slider', 'splide'],
                    'styles' => [
                        'width' => '100%',
                        'overflow' => 'hidden',
                    ],
                    'dataAttributes' => [
                        'splide' => '{"type":"loop","perPage":1,"autoplay":true,"interval":5000}',
                    ],
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['splide__track'],
                            'styles' => [
                                'overflow' => 'hidden',
                            ],
                            'children' => [
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'container',
                                    'tag' => 'ul',
                                    'classes' => ['splide__list'],
                                    'styles' => [
                                        'display' => 'flex',
                                        'margin' => '0',
                                        'padding' => '0',
                                        'listStyle' => 'none',
                                    ],
                                    'children' => [
                                        $this->createSliderSlide('Slajd 1', 'Opis pierwszego slajdu prezentujacy produkt', 'https://placehold.co/960x400/374151/9ca3af?text=Slajd+1'),
                                        $this->createSliderSlide('Slajd 2', 'Opis drugiego slajdu z dodatkowymi informacjami', 'https://placehold.co/960x400/4b5563/9ca3af?text=Slajd+2'),
                                        $this->createSliderSlide('Slajd 3', 'Opis trzeciego slajdu z cechami produktu', 'https://placehold.co/960x400/6b7280/d1d5db?text=Slajd+3'),
                                    ],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create single slider slide.
     */
    protected function createSliderSlide(string $title, string $description, string $imageUrl): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'li',
            'classes' => ['splide__slide'],
            'styles' => [
                'minWidth' => '100%',
            ],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-slider__item'],
                    'styles' => [
                        'display' => 'flex',
                        'flexDirection' => 'column',
                        'alignItems' => 'center',
                        'padding' => '2rem',
                        'backgroundColor' => '#f3f4f6',
                        'minHeight' => '300px',
                        'backgroundSize' => 'cover',
                        'backgroundPosition' => 'center',
                    ],
                    'backgroundImage' => $imageUrl,
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'heading',
                            'tag' => 'h4',
                            'content' => $title,
                            'classes' => ['pd-slider__title'],
                            'styles' => [
                                'fontSize' => '1.75rem',
                                'fontWeight' => '700',
                                'color' => '#1f2937',
                                'marginBottom' => '0.5rem',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'text',
                            'tag' => 'p',
                            'content' => $description,
                            'classes' => ['pd-slider__description'],
                            'styles' => [
                                'fontSize' => '1rem',
                                'color' => '#4b5563',
                                'textAlign' => 'center',
                                'maxWidth' => '600px',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Template: pd-parallax - Fullwidth parallax section with overlay.
     * Based on PrestaShop pd-pseudo-parallax structure.
     */
    protected function getParallaxTemplate(): array
    {
        return [
            'name' => 'Parallax (pd-parallax)',
            'cssClasses' => ['pd-pseudo-parallax', 'pd-pseudo-parallax__overlay', 'pd-pseudo-parallax__title'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'background',
                    'tag' => 'div',
                    'classes' => ['pd-pseudo-parallax'],
                    'styles' => [
                        'position' => 'relative',
                        'minHeight' => '500px',
                        'backgroundSize' => 'cover',
                        'backgroundPosition' => 'center',
                        'backgroundAttachment' => 'fixed',
                        'display' => 'flex',
                        'alignItems' => 'center',
                        'justifyContent' => 'center',
                    ],
                    'backgroundImage' => 'https://placehold.co/1280x720/374151/9ca3af?text=Parallax+1280x720',
                    'overlayColor' => '#000000',
                    'overlayOpacity' => 0.5,
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['pd-pseudo-parallax__overlay', 'pd-pseudo-parallax__overlay--dark'],
                            'styles' => [
                                'position' => 'absolute',
                                'inset' => '0',
                                'backgroundColor' => 'rgba(0, 0, 0, 0.5)',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['pd-pseudo-parallax__content'],
                            'styles' => [
                                'position' => 'relative',
                                'zIndex' => '1',
                                'textAlign' => 'center',
                                'padding' => '2rem',
                                'maxWidth' => '800px',
                            ],
                            'children' => [
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'heading',
                                    'tag' => 'h3',
                                    'content' => 'Tytul sekcji parallax',
                                    'classes' => ['pd-pseudo-parallax__title'],
                                    'styles' => [
                                        'fontSize' => 'clamp(1.75rem, 1.125rem + 2vw, 3rem)',
                                        'fontWeight' => '700',
                                        'color' => '#ffffff',
                                        'marginBottom' => '1rem',
                                    ],
                                    'children' => [],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'text',
                                    'tag' => 'p',
                                    'content' => 'Podtytul lub krotki opis sekcji',
                                    'classes' => ['pd-pseudo-parallax__subtitle'],
                                    'styles' => [
                                        'fontSize' => '1.125rem',
                                        'color' => '#ffffff',
                                        'marginBottom' => '1.5rem',
                                        'opacity' => '0.9',
                                    ],
                                    'children' => [],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'button',
                                    'tag' => 'a',
                                    'content' => 'Zobacz wiecej',
                                    'href' => '#',
                                    'classes' => ['pd-pseudo-parallax__btn', 'btn-primary'],
                                    'styles' => [
                                        'display' => 'inline-block',
                                        'padding' => '1rem 2rem',
                                        'backgroundColor' => '#ef8248',
                                        'color' => '#ffffff',
                                        'borderRadius' => '0.5rem',
                                        'textDecoration' => 'none',
                                        'fontWeight' => '600',
                                    ],
                                    'children' => [],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Template: pd-asset-list - Key product parameters with icons and units.
     * Based on PrestaShop pd-asset-list structure.
     */
    protected function getAssetListTemplate(): array
    {
        return [
            'name' => 'Lista parametrow (pd-asset-list)',
            'cssClasses' => ['pd-asset-list', 'pd-asset', 'pd-asset__value', 'pd-asset__unit', 'pd-asset__label'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-asset-list', 'pd-asset-list--horizontal', 'pd-asset-list--centered'],
                    'styles' => [
                        'display' => 'flex',
                        'flexWrap' => 'wrap',
                        'justifyContent' => 'center',
                        'gap' => '2rem',
                        'padding' => '2rem',
                        'backgroundColor' => '#f9fafb',
                    ],
                    'children' => [
                        $this->createAssetItem('pd-icon--engine', '200', 'cm³', 'Pojemnosc'),
                        $this->createAssetItem('pd-icon--speed', '15', 'KM', 'Moc'),
                        $this->createAssetItem('pd-icon--terrain', 'R-N-F', '', 'Skrzynia'),
                        $this->createAssetItem('pd-icon--quality', '210', 'kg', 'Masa'),
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create single asset item for pd-asset-list template.
     */
    protected function createAssetItem(string $iconClass, string $value, string $unit, string $label): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'classes' => ['pd-asset'],
            'styles' => [
                'display' => 'flex',
                'flexDirection' => 'column',
                'alignItems' => 'center',
                'gap' => '0.25rem',
                'textAlign' => 'center',
                'minWidth' => '100px',
            ],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'icon',
                    'tag' => 'span',
                    'iconClass' => $iconClass,
                    'classes' => ['pd-asset__icon', 'pd-icon', $iconClass],
                    'styles' => [
                        'fontSize' => '2rem',
                        'color' => '#ef8248',
                        'marginBottom' => '0.5rem',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => ['pd-asset__value-wrapper'],
                    'styles' => [
                        'display' => 'flex',
                        'alignItems' => 'baseline',
                        'gap' => '0.25rem',
                    ],
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'text',
                            'tag' => 'span',
                            'content' => $value,
                            'classes' => ['pd-asset__value'],
                            'styles' => [
                                'fontSize' => '2rem',
                                'fontWeight' => '700',
                                'color' => '#1f2937',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'text',
                            'tag' => 'span',
                            'content' => $unit,
                            'classes' => ['pd-asset__unit'],
                            'styles' => [
                                'fontSize' => '0.875rem',
                                'color' => '#6b7280',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'span',
                    'content' => $label,
                    'classes' => ['pd-asset__label'],
                    'styles' => [
                        'fontSize' => '0.75rem',
                        'textTransform' => 'uppercase',
                        'letterSpacing' => '0.05em',
                        'color' => '#9ca3af',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    /**
     * Template: pd-cover - Cover image section with overlay and text.
     * Used for hero sections and featured product displays.
     */
    protected function getCoverTemplate(): array
    {
        return [
            'name' => 'Cover/Hero (pd-cover)',
            'cssClasses' => ['pd-cover', 'pd-cover__overlay', 'pd-cover__content'],
            'elements' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'background',
                    'tag' => 'div',
                    'classes' => ['pd-cover'],
                    'styles' => [
                        'position' => 'relative',
                        'minHeight' => '400px',
                        'backgroundSize' => 'cover',
                        'backgroundPosition' => 'center',
                        'display' => 'flex',
                        'alignItems' => 'center',
                        'justifyContent' => 'center',
                    ],
                    'backgroundImage' => 'https://placehold.co/960x660/374151/9ca3af?text=Cover+960x660',
                    'overlayColor' => '#000000',
                    'overlayOpacity' => 0.3,
                    'children' => [
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['pd-cover__overlay'],
                            'styles' => [
                                'position' => 'absolute',
                                'inset' => '0',
                                'backgroundColor' => 'rgba(0, 0, 0, 0.3)',
                            ],
                            'children' => [],
                            'visible' => true,
                            'locked' => false,
                        ],
                        [
                            'id' => $this->generateElementId(),
                            'type' => 'container',
                            'tag' => 'div',
                            'classes' => ['pd-cover__content'],
                            'styles' => [
                                'position' => 'relative',
                                'zIndex' => '1',
                                'textAlign' => 'center',
                                'padding' => '2rem',
                            ],
                            'children' => [
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'heading',
                                    'tag' => 'h2',
                                    'content' => 'Nazwa produktu',
                                    'classes' => ['pd-cover__title'],
                                    'styles' => [
                                        'fontSize' => 'clamp(2rem, 2rem + 2vw, 3.5rem)',
                                        'fontWeight' => '800',
                                        'color' => '#ffffff',
                                        'marginBottom' => '0.5rem',
                                        'textShadow' => '0 2px 4px rgba(0,0,0,0.3)',
                                    ],
                                    'children' => [],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                                [
                                    'id' => $this->generateElementId(),
                                    'type' => 'text',
                                    'tag' => 'p',
                                    'content' => 'Motto lub krotki opis produktu',
                                    'classes' => ['pd-cover__subtitle'],
                                    'styles' => [
                                        'fontSize' => '1.25rem',
                                        'color' => '#ffffff',
                                        'opacity' => '0.9',
                                    ],
                                    'children' => [],
                                    'visible' => true,
                                    'locked' => false,
                                ],
                            ],
                            'visible' => true,
                            'locked' => false,
                        ],
                    ],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
        ];
    }

    /**
     * Create single repeater item with default structure.
     */
    protected function createRepeaterItem(): array
    {
        return [
            'id' => $this->generateElementId(),
            'type' => 'container',
            'tag' => 'div',
            'classes' => ['pd-repeater__item'],
            'styles' => [
                'display' => 'flex',
                'alignItems' => 'center',
                'gap' => '1rem',
                'padding' => '1rem',
                'backgroundColor' => '#f9fafb',
                'borderRadius' => '0.5rem',
            ],
            'children' => [
                [
                    'id' => $this->generateElementId(),
                    'type' => 'icon',
                    'tag' => 'span',
                    'iconClass' => 'pd-icon--check',
                    'classes' => ['pd-icon', 'pd-icon--check'],
                    'styles' => [
                        'fontSize' => '1.5rem',
                        'color' => '#e0ac7e',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                [
                    'id' => $this->generateElementId(),
                    'type' => 'text',
                    'tag' => 'span',
                    'content' => 'Element listy',
                    'classes' => [],
                    'styles' => [
                        'fontSize' => '1rem',
                        'color' => '#374151',
                    ],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
            ],
            'visible' => true,
            'locked' => false,
        ];
    }

    // ========== ELEMENT OPERATIONS ==========

    /**
     * Add element to canvas.
     */
    public function addElement(string $type, ?string $parentId = null): void
    {
        $element = $this->createElementByType($type);

        if ($parentId) {
            $this->addElementToParent($element, $parentId);
        } else {
            // Add to root
            $this->document['root']['children'][] = $element;
        }

        $this->selectedElementId = $element['id'];
        $this->pushHistory();

        Log::debug('BlockBuilder: Element added', [
            'type' => $type,
            'element_id' => $element['id'],
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Create element by type with default properties.
     */
    protected function createElementByType(string $type): array
    {
        $id = $this->generateElementId();

        return match ($type) {
            'heading' => [
                'id' => $id,
                'type' => 'heading',
                'tag' => 'h2',
                'content' => 'Nowy naglowek',
                'classes' => [],
                'styles' => [
                    'fontWeight' => '700',
                    'fontSize' => '2rem',
                    'color' => '#000000',
                    'textAlign' => 'center',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'text' => [
                'id' => $id,
                'type' => 'text',
                'tag' => 'p',
                'content' => 'Nowy tekst. Kliknij, aby edytowac.',
                'classes' => [],
                'styles' => [
                    'fontSize' => '1rem',
                    'color' => '#333333',
                    'textAlign' => 'left',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'image' => [
                'id' => $id,
                'type' => 'image',
                'tag' => 'img',
                'src' => '',
                'alt' => 'Obraz',
                'classes' => [],
                'styles' => [
                    'maxWidth' => '100%',
                    'height' => 'auto',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'button' => [
                'id' => $id,
                'type' => 'button',
                'tag' => 'a',
                'content' => 'Przycisk',
                'href' => '#',
                'classes' => ['btn', 'btn-primary'],
                'styles' => [
                    'display' => 'inline-block',
                    'padding' => '0.75rem 1.5rem',
                    'backgroundColor' => '#e0ac7e',
                    'color' => '#ffffff',
                    'borderRadius' => '0.5rem',
                    'textDecoration' => 'none',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'separator' => [
                'id' => $id,
                'type' => 'separator',
                'tag' => 'hr',
                'classes' => [],
                'styles' => [
                    'width' => '100%',
                    'borderTop' => '1px solid #e5e5e5',
                    'margin' => '1rem 0',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'container' => [
                'id' => $id,
                'type' => 'container',
                'tag' => 'div',
                'classes' => [],
                'styles' => [
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'gap' => '1rem',
                    'padding' => '1rem',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'row' => [
                'id' => $id,
                'type' => 'row',
                'tag' => 'div',
                'classes' => [],
                'styles' => [
                    'display' => 'flex',
                    'flexDirection' => 'row',
                    'gap' => '1rem',
                    'alignItems' => 'center',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'column' => [
                'id' => $id,
                'type' => 'column',
                'tag' => 'div',
                'classes' => [],
                'styles' => [
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'gap' => '1rem',
                    'alignItems' => 'stretch',
                    'flex' => '1',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'grid' => [
                'id' => $id,
                'type' => 'grid',
                'tag' => 'div',
                'classes' => [],
                'styles' => [
                    'display' => 'grid',
                    'gridTemplateColumns' => 'repeat(2, 1fr)',
                    'gap' => '1rem',
                    'padding' => '1rem',
                ],
                'gridColumns' => 2,
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'background' => [
                'id' => $id,
                'type' => 'background',
                'tag' => 'div',
                'classes' => ['relative'],
                'styles' => [
                    'position' => 'relative',
                    'minHeight' => '200px',
                    'backgroundColor' => '#f3f4f6',
                    'backgroundSize' => 'cover',
                    'backgroundPosition' => 'center',
                ],
                'backgroundImage' => '',
                'overlayColor' => '',
                'overlayOpacity' => 0.5,
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'repeater' => [
                'id' => $id,
                'type' => 'repeater',
                'tag' => 'div',
                'classes' => ['pd-repeater'],
                'styles' => [
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'gap' => '1rem',
                ],
                'itemLayout' => 'list', // list, grid, carousel
                'itemsPerRow' => 1,
                'items' => [
                    $this->createRepeaterItem(),
                    $this->createRepeaterItem(),
                    $this->createRepeaterItem(),
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'slide' => [
                'id' => $id,
                'type' => 'slide',
                'tag' => 'div',
                'classes' => ['pd-slide'],
                'styles' => [
                    'position' => 'relative',
                    'minHeight' => '300px',
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'alignItems' => 'center',
                    'justifyContent' => 'center',
                    'padding' => '2rem',
                    'backgroundSize' => 'cover',
                    'backgroundPosition' => 'center',
                ],
                'backgroundImage' => '',
                'overlayColor' => '#000000',
                'overlayOpacity' => 0.3,
                'slideIndex' => 0,
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'icon' => [
                'id' => $id,
                'type' => 'icon',
                'tag' => 'span',
                'iconClass' => 'pd-icon--check',
                'classes' => ['pd-icon'],
                'styles' => [
                    'fontSize' => '2rem',
                    'color' => '#e0ac7e',
                ],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            default => [
                'id' => $id,
                'type' => $type,
                'tag' => 'div',
                'content' => '',
                'classes' => [],
                'styles' => [],
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
        };
    }

    /**
     * Add element to parent recursively.
     */
    protected function addElementToParent(array $element, string $parentId): bool
    {
        return $this->traverseAndModify($this->document['root'], function (&$node) use ($element, $parentId) {
            if ($node['id'] === $parentId) {
                $node['children'][] = $element;
                return true;
            }
            return false;
        });
    }

    /**
     * Select an element.
     */
    public function selectElement(?string $elementId): void
    {
        $this->selectedElementId = $elementId;
        $this->activePanel = 'properties';
    }

    /**
     * Delete selected element.
     */
    public function deleteElement(?string $elementId = null): void
    {
        $targetId = $elementId ?? $this->selectedElementId;
        if (!$targetId || $targetId === $this->document['root']['id']) {
            return;
        }

        $this->traverseAndRemove($this->document['root'], $targetId);

        if ($this->selectedElementId === $targetId) {
            $this->selectedElementId = null;
        }

        $this->pushHistory();
    }

    /**
     * Copy element to clipboard.
     */
    public function copyElement(?string $elementId = null): void
    {
        $targetId = $elementId ?? $this->selectedElementId;
        if (!$targetId) {
            return;
        }

        $element = $this->findElementById($this->document['root'], $targetId);
        if ($element) {
            $this->clipboard = $this->cloneElement($element);
            $this->dispatch('notify', type: 'info', message: 'Element skopiowany');
        }
    }

    /**
     * Paste element from clipboard.
     */
    public function pasteElement(?string $parentId = null): void
    {
        if (!$this->clipboard) {
            return;
        }

        $element = $this->cloneElement($this->clipboard);
        $targetParentId = $parentId ?? $this->selectedElementId ?? $this->document['root']['id'];

        if ($targetParentId === $this->document['root']['id']) {
            $this->document['root']['children'][] = $element;
        } else {
            $this->addElementToParent($element, $targetParentId);
        }

        $this->selectedElementId = $element['id'];
        $this->pushHistory();
        $this->dispatch('notify', type: 'success', message: 'Element wklejony');
    }

    /**
     * Duplicate element.
     */
    public function duplicateElement(?string $elementId = null): void
    {
        $targetId = $elementId ?? $this->selectedElementId;
        if (!$targetId) {
            return;
        }

        $element = $this->findElementById($this->document['root'], $targetId);
        if ($element) {
            $clone = $this->cloneElement($element);
            $parentId = $this->findParentId($this->document['root'], $targetId);

            if ($parentId) {
                $this->addElementToParent($clone, $parentId);
            } else {
                $this->document['root']['children'][] = $clone;
            }

            $this->selectedElementId = $clone['id'];
            $this->pushHistory();
        }
    }

    /**
     * Clone element with new IDs.
     */
    protected function cloneElement(array $element): array
    {
        $clone = $element;
        $clone['id'] = $this->generateElementId();

        if (!empty($clone['children'])) {
            $clone['children'] = array_map(fn($child) => $this->cloneElement($child), $clone['children']);
        }

        return $clone;
    }

    // ========== ELEMENT PROPERTIES ==========

    /**
     * Update element property.
     */
    public function updateElementProperty(string $elementId, string $property, mixed $value): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $property, $value) {
            if ($node['id'] === $elementId) {
                // Handle nested properties (e.g., styles.fontSize)
                $parts = explode('.', $property);
                $ref = &$node;
                foreach ($parts as $i => $part) {
                    if ($i === count($parts) - 1) {
                        $ref[$part] = $value;
                    } else {
                        if (!isset($ref[$part])) {
                            $ref[$part] = [];
                        }
                        $ref = &$ref[$part];
                    }
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Update element size (width/height) from resize handles.
     * Handle types: 'e' (width only), 's' (height only), 'se' (both)
     */
    public function updateElementSize(string $elementId, int $width, int $height, string $handle): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $width, $height, $handle) {
            if ($node['id'] === $elementId) {
                if (!isset($node['styles'])) {
                    $node['styles'] = [];
                }

                // Update width for 'e' or 'se' handles
                if ($handle === 'e' || $handle === 'se') {
                    $node['styles']['width'] = $width . 'px';
                }

                // Update height for 's' or 'se' handles
                if ($handle === 's' || $handle === 'se') {
                    $node['styles']['height'] = $height . 'px';
                }

                return true;
            }
            return false;
        });

        // Don't push history on every mouse move - only on release
        // History is pushed from frontend on mouseup via separate call
    }

    /**
     * Commit resize to history (called on mouseup).
     */
    public function commitResize(): void
    {
        $this->pushHistory();
    }

    /**
     * Move element up in order.
     */
    public function moveElementUp(string $elementId): void
    {
        $this->reorderElement($elementId, -1);
    }

    /**
     * Move element down in order.
     */
    public function moveElementDown(string $elementId): void
    {
        $this->reorderElement($elementId, 1);
    }

    /**
     * Reorder element within its parent.
     */
    protected function reorderElement(string $elementId, int $direction): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $direction) {
            foreach ($node['children'] as $index => $child) {
                if ($child['id'] === $elementId) {
                    $newIndex = $index + $direction;
                    if ($newIndex >= 0 && $newIndex < count($node['children'])) {
                        $temp = $node['children'][$index];
                        $node['children'][$index] = $node['children'][$newIndex];
                        $node['children'][$newIndex] = $temp;
                        return true;
                    }
                }
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Move element to the front (last position in parent = rendered on top).
     */
    public function bringToFront(string $elementId): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId) {
            foreach ($node['children'] as $index => $child) {
                if ($child['id'] === $elementId) {
                    $element = array_splice($node['children'], $index, 1)[0];
                    $node['children'][] = $element;
                    return true;
                }
            }
            return false;
        });
        $this->pushHistory();
    }

    /**
     * Move element to the back (first position in parent = rendered behind).
     */
    public function sendToBack(string $elementId): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId) {
            foreach ($node['children'] as $index => $child) {
                if ($child['id'] === $elementId) {
                    $element = array_splice($node['children'], $index, 1)[0];
                    array_unshift($node['children'], $element);
                    return true;
                }
            }
            return false;
        });
        $this->pushHistory();
    }

    /**
     * Add NEW element to a container at specific position.
     * Used for drag & drop from palette to container.
     */
    public function addElementToContainer(string $type, string $containerId, int $position = -1): void
    {
        // Create new element
        $element = $this->createElementByType($type);

        // Insert at position within container
        $inserted = $this->insertElementAtPosition($element, $containerId, $position);

        if ($inserted) {
            $this->selectedElementId = $element['id'];
            $this->pushHistory();

            Log::debug('BlockBuilder: Element added to container', [
                'type' => $type,
                'element_id' => $element['id'],
                'container_id' => $containerId,
                'position' => $position,
            ]);
        } else {
            Log::warning('BlockBuilder: Failed to add element to container', [
                'type' => $type,
                'container_id' => $containerId,
            ]);
        }
    }

    /**
     * Move element to a new parent container at specific position.
     * Used for drag & drop between containers.
     */
    public function moveElementToContainer(string $elementId, string $targetContainerId, int $position = -1): void
    {
        // Cannot move root element
        if ($elementId === $this->document['root']['id']) {
            return;
        }

        // Find the element to move
        $element = $this->findElementById($this->document['root'], $elementId);
        if (!$element) {
            Log::warning('BlockBuilder: Element not found for move', ['element_id' => $elementId]);
            return;
        }

        // Cannot move into itself or its children
        if ($this->isDescendantOf($element, $targetContainerId)) {
            $this->dispatch('notify', type: 'warning', message: 'Nie mozna przeniesc elementu do samego siebie');
            return;
        }

        // Remove from current parent
        $this->traverseAndRemove($this->document['root'], $elementId);

        // Add to new parent at position
        $inserted = $this->insertElementAtPosition($element, $targetContainerId, $position);

        if ($inserted) {
            $this->pushHistory();
            Log::debug('BlockBuilder: Element moved', [
                'element_id' => $elementId,
                'target_container' => $targetContainerId,
                'position' => $position,
            ]);
        }
    }

    /**
     * Insert element at specific position within a container.
     */
    protected function insertElementAtPosition(array $element, string $containerId, int $position): bool
    {
        return $this->traverseAndModify($this->document['root'], function (&$node) use ($element, $containerId, $position) {
            if ($node['id'] === $containerId) {
                if (!isset($node['children'])) {
                    $node['children'] = [];
                }

                if ($position < 0 || $position >= count($node['children'])) {
                    // Add at end
                    $node['children'][] = $element;
                } else {
                    // Insert at position
                    array_splice($node['children'], $position, 0, [$element]);
                }
                return true;
            }
            return false;
        });
    }

    /**
     * Check if targetId is a descendant of the given node.
     */
    protected function isDescendantOf(array $node, string $targetId): bool
    {
        if ($node['id'] === $targetId) {
            return true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if ($this->isDescendantOf($child, $targetId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reorder element at specific position within the same parent.
     * Used for drag reordering.
     */
    public function reorderElementAtPosition(string $elementId, int $newPosition): void
    {
        $parentId = $this->findParentId($this->document['root'], $elementId);
        if (!$parentId) {
            // Element is direct child of root
            $parentId = $this->document['root']['id'];
        }

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $newPosition) {
            if (!isset($node['children'])) {
                return false;
            }

            $currentIndex = -1;
            foreach ($node['children'] as $index => $child) {
                if ($child['id'] === $elementId) {
                    $currentIndex = $index;
                    break;
                }
            }

            if ($currentIndex === -1) {
                return false;
            }

            // Remove from current position
            $element = $node['children'][$currentIndex];
            array_splice($node['children'], $currentIndex, 1);

            // Adjust target position if needed
            $targetPosition = min($newPosition, count($node['children']));

            // Insert at new position
            array_splice($node['children'], $targetPosition, 0, [$element]);

            return true;
        });

        $this->pushHistory();
    }

    /**
     * Check if element type is a container that can accept children.
     */
    public function isContainerType(string $type): bool
    {
        return in_array($type, ['container', 'row', 'column', 'grid', 'background', 'repeater', 'slide'], true);
    }

    /**
     * Toggle element visibility.
     */
    public function toggleVisibility(string $elementId): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId) {
            if ($node['id'] === $elementId) {
                $node['visible'] = !($node['visible'] ?? true);
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Toggle element lock.
     */
    public function toggleLock(string $elementId): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId) {
            if ($node['id'] === $elementId) {
                $node['locked'] = !($node['locked'] ?? false);
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Apply button style variant.
     */
    public function applyButtonVariant(string $elementId, string $variant): void
    {
        $variants = [
            'primary' => [
                'backgroundColor' => '#e0ac7e',
                'color' => '#ffffff',
                'border' => 'none',
                'fontWeight' => '600',
            ],
            'secondary' => [
                'backgroundColor' => '#374151',
                'color' => '#ffffff',
                'border' => 'none',
                'fontWeight' => '500',
            ],
            'outline' => [
                'backgroundColor' => 'transparent',
                'color' => '#e0ac7e',
                'border' => '2px solid #e0ac7e',
                'fontWeight' => '500',
            ],
            'ghost' => [
                'backgroundColor' => 'transparent',
                'color' => '#9ca3af',
                'border' => 'none',
                'fontWeight' => '500',
            ],
            'danger' => [
                'backgroundColor' => '#dc2626',
                'color' => '#ffffff',
                'border' => 'none',
                'fontWeight' => '600',
            ],
            'success' => [
                'backgroundColor' => '#059669',
                'color' => '#ffffff',
                'border' => 'none',
                'fontWeight' => '600',
            ],
        ];

        if (!isset($variants[$variant])) {
            return;
        }

        $styles = $variants[$variant];

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $styles) {
            if ($node['id'] === $elementId) {
                foreach ($styles as $prop => $value) {
                    $node['styles'][$prop] = $value;
                }
                $node['buttonVariant'] = array_search($styles, [
                    'primary' => $styles,
                ]) ?: 'custom';
                return true;
            }
            return false;
        });

        $this->pushHistory();

        Log::debug('BlockBuilder: Button variant applied', [
            'element_id' => $elementId,
            'variant' => $variant,
        ]);
    }

    /**
     * Apply button size preset.
     */
    public function applyButtonSize(string $elementId, string $size): void
    {
        $sizes = [
            'sm' => [
                'padding' => '0.5rem 1rem',
                'fontSize' => '0.875rem',
            ],
            'md' => [
                'padding' => '0.75rem 1.5rem',
                'fontSize' => '1rem',
            ],
            'lg' => [
                'padding' => '1rem 2rem',
                'fontSize' => '1.125rem',
            ],
            'xl' => [
                'padding' => '1.25rem 2.5rem',
                'fontSize' => '1.25rem',
            ],
        ];

        if (!isset($sizes[$size])) {
            return;
        }

        $styles = $sizes[$size];

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $styles, $size) {
            if ($node['id'] === $elementId) {
                foreach ($styles as $prop => $value) {
                    $node['styles'][$prop] = $value;
                }
                $node['buttonSize'] = $size;
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Apply icon size preset.
     */
    public function applyIconSize(string $elementId, string $size): void
    {
        $sizes = [
            'sm' => '1rem',
            'md' => '1.5rem',
            'lg' => '2rem',
            'xl' => '2.5rem',
            '2xl' => '3rem',
            '3xl' => '4rem',
        ];

        if (!isset($sizes[$size])) {
            return;
        }

        $this->updateElementProperty($elementId, 'styles.fontSize', $sizes[$size]);
    }

    /**
     * Apply grid columns preset.
     */
    public function applyGridColumns(string $elementId, int $columns): void
    {
        if ($columns < 1 || $columns > 6) {
            return;
        }

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $columns) {
            if ($node['id'] === $elementId) {
                $node['gridColumns'] = $columns;
                $node['styles']['gridTemplateColumns'] = "repeat({$columns}, 1fr)";
                return true;
            }
            return false;
        });

        $this->pushHistory();

        Log::debug('BlockBuilder: Grid columns applied', [
            'element_id' => $elementId,
            'columns' => $columns,
        ]);
    }

    /**
     * Add CSS class to element.
     */
    public function addElementClass(string $elementId, string $class): void
    {
        $class = trim($class);
        if (empty($class)) {
            return;
        }

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $class) {
            if ($node['id'] === $elementId) {
                if (!isset($node['classes'])) {
                    $node['classes'] = [];
                }
                if (!in_array($class, $node['classes'])) {
                    $node['classes'][] = $class;
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Remove CSS class from element.
     */
    public function removeElementClass(string $elementId, string $class): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $class) {
            if ($node['id'] === $elementId && isset($node['classes'])) {
                $node['classes'] = array_values(array_filter(
                    $node['classes'],
                    fn($c) => $c !== $class
                ));
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Toggle CSS class on element.
     */
    public function toggleElementClass(string $elementId, string $class): void
    {
        $class = trim($class);
        if (empty($class)) {
            return;
        }

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $class) {
            if ($node['id'] === $elementId) {
                if (!isset($node['classes'])) {
                    $node['classes'] = [];
                }
                $index = array_search($class, $node['classes']);
                if ($index !== false) {
                    unset($node['classes'][$index]);
                    $node['classes'] = array_values($node['classes']);
                } else {
                    $node['classes'][] = $class;
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Set all CSS classes for element (space-separated string).
     */
    public function setElementClasses(string $elementId, string $classesString): void
    {
        $classes = array_filter(array_map('trim', explode(' ', $classesString)));

        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $classes) {
            if ($node['id'] === $elementId) {
                $node['classes'] = array_values(array_unique($classes));
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Update background image.
     */
    public function updateBackgroundImage(string $elementId, string $imageUrl): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $imageUrl) {
            if ($node['id'] === $elementId) {
                $node['backgroundImage'] = $imageUrl;
                if (!empty($imageUrl)) {
                    $node['styles']['backgroundImage'] = "url('{$imageUrl}')";
                } else {
                    unset($node['styles']['backgroundImage']);
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Update background overlay.
     */
    public function updateBackgroundOverlay(string $elementId, string $color, float $opacity): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $color, $opacity) {
            if ($node['id'] === $elementId) {
                $node['overlayColor'] = $color;
                $node['overlayOpacity'] = max(0, min(1, $opacity));
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Add item to repeater.
     */
    public function addRepeaterItem(string $elementId): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId) {
            if ($node['id'] === $elementId && $node['type'] === 'repeater') {
                $node['items'][] = $this->createRepeaterItem();
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Remove item from repeater.
     */
    public function removeRepeaterItem(string $elementId, int $index): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $index) {
            if ($node['id'] === $elementId && $node['type'] === 'repeater') {
                if (isset($node['items'][$index]) && count($node['items']) > 1) {
                    array_splice($node['items'], $index, 1);
                    return true;
                }
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Set repeater layout mode.
     */
    public function setRepeaterLayout(string $elementId, string $layout, int $itemsPerRow = 1): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $layout, $itemsPerRow) {
            if ($node['id'] === $elementId && $node['type'] === 'repeater') {
                $node['itemLayout'] = $layout;
                $node['itemsPerRow'] = $itemsPerRow;

                // Update styles based on layout
                if ($layout === 'grid') {
                    $node['styles']['display'] = 'grid';
                    $node['styles']['gridTemplateColumns'] = "repeat({$itemsPerRow}, 1fr)";
                    unset($node['styles']['flexDirection']);
                } elseif ($layout === 'list') {
                    $node['styles']['display'] = 'flex';
                    $node['styles']['flexDirection'] = 'column';
                    unset($node['styles']['gridTemplateColumns']);
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Update slide background image.
     */
    public function updateSlideBackground(string $elementId, string $imageUrl): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $imageUrl) {
            if ($node['id'] === $elementId && $node['type'] === 'slide') {
                $node['backgroundImage'] = $imageUrl;
                if (!empty($imageUrl)) {
                    $node['styles']['backgroundImage'] = "url('{$imageUrl}')";
                } else {
                    unset($node['styles']['backgroundImage']);
                }
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    /**
     * Update slide overlay settings.
     */
    public function updateSlideOverlay(string $elementId, string $color, float $opacity): void
    {
        $this->traverseAndModify($this->document['root'], function (&$node) use ($elementId, $color, $opacity) {
            if ($node['id'] === $elementId && $node['type'] === 'slide') {
                $node['overlayColor'] = $color;
                $node['overlayOpacity'] = max(0, min(1, $opacity));
                return true;
            }
            return false;
        });

        $this->pushHistory();
    }

    // ========== HISTORY (UNDO/REDO) ==========

    /**
     * Push current state to history.
     */
    protected function pushHistory(): void
    {
        // Remove future states if we're not at the end
        if ($this->historyIndex < count($this->history) - 1) {
            $this->history = array_slice($this->history, 0, $this->historyIndex + 1);
        }

        // Add current state
        $this->history[] = json_encode($this->document);
        $this->historyIndex = count($this->history) - 1;

        // Limit history size
        if (count($this->history) > $this->maxHistorySize) {
            array_shift($this->history);
            $this->historyIndex--;
        }
    }

    /**
     * Undo last action.
     */
    public function undo(): void
    {
        if ($this->historyIndex > 0) {
            $this->historyIndex--;
            $this->document = json_decode($this->history[$this->historyIndex], true);
        }
    }

    /**
     * Redo last undone action.
     */
    public function redo(): void
    {
        if ($this->historyIndex < count($this->history) - 1) {
            $this->historyIndex++;
            $this->document = json_decode($this->history[$this->historyIndex], true);
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Traverse tree and modify nodes.
     */
    protected function traverseAndModify(array &$node, callable $callback): bool
    {
        if ($callback($node)) {
            return true;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as &$child) {
                if ($this->traverseAndModify($child, $callback)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Traverse tree and remove element by ID.
     */
    protected function traverseAndRemove(array &$node, string $targetId): bool
    {
        if (!empty($node['children'])) {
            foreach ($node['children'] as $index => $child) {
                if ($child['id'] === $targetId) {
                    array_splice($node['children'], $index, 1);
                    return true;
                }
                if ($this->traverseAndRemove($node['children'][$index], $targetId)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Find element by ID.
     */
    protected function findElementById(array $node, string $targetId): ?array
    {
        if ($node['id'] === $targetId) {
            return $node;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $found = $this->findElementById($child, $targetId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Find parent ID of an element.
     */
    protected function findParentId(array $node, string $targetId, ?string $parentId = null): ?string
    {
        if ($node['id'] === $targetId) {
            return $parentId;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $found = $this->findParentId($child, $targetId, $node['id']);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    // ========== SAVE & EXPORT ==========

    /**
     * Save block definition.
     */
    public function save(): void
    {
        if (empty($this->blockName)) {
            $this->dispatch('notify', type: 'error', message: 'Nazwa bloku jest wymagana');
            return;
        }

        try {
            // Generate render template from document
            $renderTemplate = $this->exportToHtml();

            // Generate type slug if new
            if (empty($this->blockType)) {
                $this->blockType = BlockDefinition::generateTypeSlug($this->blockName, $this->shopId);
            }

            // Build schema from document variables
            $schema = [
                'content' => [],
                'settings' => [],
            ];

            foreach ($this->document['variables'] ?? [] as $variable) {
                $schema['content'][$variable['name']] = [
                    'type' => $variable['type'],
                    'label' => $variable['label'],
                    'default' => $variable['defaultValue'] ?? '',
                ];
            }

            $data = [
                'shop_id' => $this->shopId,
                'type' => $this->blockType,
                'name' => $this->blockName,
                'category' => 'shop-custom',
                'icon' => $this->blockIcon,
                'description' => $this->blockDescription ?: 'Utworzony w Visual Block Builder',
                'schema' => $schema,
                'render_template' => $renderTemplate,
                'builder_document' => $this->document,
                'builder_version' => '1.0',
                'css_classes' => $this->document['cssClasses'] ?? [],
                'is_active' => true,
                'updated_by' => auth()->id(),
            ];

            if ($this->definitionId) {
                $definition = BlockDefinition::find($this->definitionId);
                $definition->update($data);
            } else {
                $data['created_by'] = auth()->id();
                $definition = BlockDefinition::create($data);
                $this->definitionId = $definition->id;
            }

            Log::info('BlockBuilder: Block saved', [
                'definition_id' => $definition->id,
                'type' => $definition->type,
                'shop_id' => $this->shopId,
            ]);

            $this->dispatch('notify', type: 'success', message: "Blok '{$this->blockName}' zostal zapisany");
            $this->dispatch('block-definition-saved', definitionId: $definition->id);

        } catch (\Exception $e) {
            Log::error('BlockBuilder: Save failed', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Export document to HTML using BlockDocumentToHtmlExporter.
     */
    public function exportToHtml(): string
    {
        try {
            $exporter = new BlockDocumentToHtmlExporter();
            // PrestaShop templates use external CSS only - no inline styles
            $exporter->setIncludeInlineStyles(false);
            return $exporter->export($this->document);
        } catch (\Exception $e) {
            Log::error('BlockBuilder: Export failed', ['error' => $e->getMessage()]);
            // Fallback to legacy method
            return $this->renderElementToHtml($this->document['root']);
        }
    }

    /**
     * Get exported HTML for display in modal (minified option).
     */
    public function getExportedHtml(bool $minify = false): string
    {
        try {
            $exporter = new BlockDocumentToHtmlExporter();
            $exporter->setMinify($minify);
            // PrestaShop templates use external CSS only - no inline styles
            $exporter->setIncludeInlineStyles(false);
            return $exporter->export($this->document);
        } catch (\Exception $e) {
            Log::error('BlockBuilder: Export failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Copy exported HTML to clipboard (dispatches JS event).
     */
    public function copyExportedHtml(): void
    {
        $html = $this->exportToHtml();
        if (empty(trim($html))) {
            $this->dispatch('notify', type: 'warning', message: 'Brak elementow do eksportu');
            return;
        }
        $this->dispatch('copy-to-clipboard', html: $html);
        $this->dispatch('notify', type: 'success', message: 'HTML skopiowany do schowka');
    }

    /**
     * Apply edited HTML back to Visual Editor.
     *
     * Used when VBB is opened from VE for inline block editing.
     * Exports current document to HTML and dispatches event back to VE.
     */
    public function applyToEditor(): void
    {
        $html = $this->exportToHtml();

        if (empty(trim($html))) {
            $this->dispatch('notify', type: 'warning', message: 'Brak elementow do eksportu');
            return;
        }

        // Dispatch event with HTML to update block in VE
        $this->dispatch('vbb-html-exported', html: $html);
        $this->dispatch('notify', type: 'success', message: 'Zmiany zastosowane do edytora');

        // Close VBB modal
        $this->close();
    }

    /**
     * Render single element to HTML.
     */
    protected function renderElementToHtml(array $element): string
    {
        if (!($element['visible'] ?? true)) {
            return '';
        }

        $tag = $element['tag'] ?? 'div';
        $classes = implode(' ', $element['classes'] ?? []);
        $styles = $this->buildInlineStyles($element['styles'] ?? []);

        // Build attributes
        $attrs = [];
        if ($classes) {
            $attrs[] = "class=\"{$classes}\"";
        }
        if ($styles) {
            $attrs[] = "style=\"{$styles}\"";
        }

        // Handle specific element types
        if ($element['type'] === 'image') {
            $src = $element['src'] ?? '';
            $alt = $element['alt'] ?? '';
            $attrsStr = implode(' ', $attrs);
            return "<img src=\"{$src}\" alt=\"{$alt}\" {$attrsStr} />";
        }

        if ($element['type'] === 'separator') {
            $attrsStr = implode(' ', $attrs);
            return "<hr {$attrsStr} />";
        }

        if ($element['type'] === 'icon') {
            $iconClass = $element['iconClass'] ?? '';
            $classes .= " {$iconClass}";
            $attrsStr = "class=\"{$classes}\"";
            if ($styles) {
                $attrsStr .= " style=\"{$styles}\"";
            }
            return "<span {$attrsStr}></span>";
        }

        if ($element['type'] === 'raw-html') {
            return $element['content'] ?? '';
        }

        $attrsStr = implode(' ', $attrs);
        $content = $element['content'] ?? '';

        // Render children
        $childrenHtml = '';
        foreach ($element['children'] ?? [] as $child) {
            $childrenHtml .= $this->renderElementToHtml($child);
        }

        return "<{$tag} {$attrsStr}>{$content}{$childrenHtml}</{$tag}>";
    }

    /**
     * Build inline styles string.
     */
    protected function buildInlineStyles(array $styles): string
    {
        $cssProps = [];

        $mapping = [
            // Display & Flex
            'display' => 'display',
            'flexDirection' => 'flex-direction',
            'flexWrap' => 'flex-wrap',
            'flexGrow' => 'flex-grow',
            'flexShrink' => 'flex-shrink',
            'alignItems' => 'align-items',
            'justifyContent' => 'justify-content',
            'gap' => 'gap',
            'rowGap' => 'row-gap',
            'columnGap' => 'column-gap',
            // Grid
            'gridTemplateColumns' => 'grid-template-columns',
            'gridTemplateRows' => 'grid-template-rows',
            'gridColumn' => 'grid-column',
            'gridRow' => 'grid-row',
            // Spacing
            'padding' => 'padding',
            'paddingTop' => 'padding-top',
            'paddingBottom' => 'padding-bottom',
            'paddingLeft' => 'padding-left',
            'paddingRight' => 'padding-right',
            'margin' => 'margin',
            'marginTop' => 'margin-top',
            'marginBottom' => 'margin-bottom',
            'marginLeft' => 'margin-left',
            'marginRight' => 'margin-right',
            // Sizing
            'width' => 'width',
            'height' => 'height',
            'maxWidth' => 'max-width',
            'maxHeight' => 'max-height',
            'minWidth' => 'min-width',
            'minHeight' => 'min-height',
            // Typography
            'fontSize' => 'font-size',
            'fontWeight' => 'font-weight',
            'fontStyle' => 'font-style',
            'lineHeight' => 'line-height',
            'letterSpacing' => 'letter-spacing',
            'color' => 'color',
            'textAlign' => 'text-align',
            'textDecoration' => 'text-decoration',
            'textTransform' => 'text-transform',
            // Background
            'backgroundColor' => 'background-color',
            'backgroundImage' => 'background-image',
            'backgroundSize' => 'background-size',
            'backgroundPosition' => 'background-position',
            'backgroundRepeat' => 'background-repeat',
            // Border
            'border' => 'border',
            'borderTop' => 'border-top',
            'borderBottom' => 'border-bottom',
            'borderLeft' => 'border-left',
            'borderRight' => 'border-right',
            'borderRadius' => 'border-radius',
            'borderColor' => 'border-color',
            'borderWidth' => 'border-width',
            'borderStyle' => 'border-style',
            // Image specific
            'objectFit' => 'object-fit',
            'objectPosition' => 'object-position',
            // Effects
            'opacity' => 'opacity',
            'boxShadow' => 'box-shadow',
            'overflow' => 'overflow',
            'transform' => 'transform',
            'filter' => 'filter',
            // Position
            'position' => 'position',
            'top' => 'top',
            'bottom' => 'bottom',
            'left' => 'left',
            'right' => 'right',
            'zIndex' => 'z-index',
        ];

        foreach ($styles as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $cssProp = $mapping[$key] ?? $key;
            $cssProps[] = "{$cssProp}: {$value}";
        }

        return implode('; ', $cssProps);
    }

    // ========== COMPUTED PROPERTIES ==========

    #[Computed]
    public function shop(): ?PrestaShopShop
    {
        return $this->shopId ? PrestaShopShop::find($this->shopId) : null;
    }

    /**
     * Get PrestaShop CSS for canvas preview with proper scoping.
     * CSS is scoped to .vbb-canvas-preview to prevent leaking into admin UI.
     */
    #[Computed]
    public function previewCss(): string
    {
        if (!$this->shopId) {
            return '';
        }

        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            return '';
        }

        $cssParts = [];

        // 1. Google Fonts (safe - only @import)
        $cssParts[] = "@import url('https://fonts.googleapis.com/css?family=Montserrat:400,600,700,800&display=swap');";

        // 2. Scoped base CSS variables and styles
        $cssParts[] = $this->getScopedBaseCss();

        // 3. Scoped PrestaShop CSS
        $fetcher = app(PrestaShopCssFetcher::class);
        $prestaShopCss = $fetcher->getCssForPreview($shop);

        if ($prestaShopCss) {
            $scopedCss = $this->scopeCssToContainer($prestaShopCss);
            $cssParts[] = "/* PrestaShop CSS (scoped): {$shop->name} */\n{$scopedCss}";
        }

        return implode("\n\n", array_filter($cssParts));
    }

    /**
     * Scope CSS selectors to .vbb-canvas-preview container.
     * Prevents PrestaShop CSS from affecting admin UI.
     */
    protected function scopeCssToContainer(string $css, string $container = '.vbb-canvas-preview'): string
    {
        // Replace :root with container
        $css = preg_replace('/:root\s*\{/', "{$container} {", $css);

        // Replace body/html selectors with container
        $css = preg_replace('/\bbody\s*\{/', "{$container} {", $css);
        $css = preg_replace('/\bhtml\s*\{/', "{$container} {", $css);

        // Replace universal selector at start of rule
        $css = preg_replace('/^\s*\*\s*\{/m', "{$container} * {", $css);

        // Prefix standalone selectors (not @rules, not already prefixed)
        // This handles cases like .some-class { } or div { }
        $css = preg_replace_callback(
            '/^([^@{}\n][^{]*)\{/m',
            function ($matches) use ($container) {
                $selector = trim($matches[1]);

                // Skip if already prefixed or is @rule
                if (str_starts_with($selector, $container) || str_starts_with($selector, '@')) {
                    return $matches[0];
                }

                // Split by comma, prefix each, rejoin
                $parts = array_map(function ($s) use ($container) {
                    $s = trim($s);
                    if (empty($s)) {
                        return $s;
                    }
                    // Skip selectors that look like they're already scoped
                    if (str_starts_with($s, $container)) {
                        return $s;
                    }
                    return "{$container} {$s}";
                }, explode(',', $selector));

                return implode(', ', $parts) . ' {';
            },
            $css
        );

        return $css;
    }

    /**
     * Get scoped base CSS (variables + styles confined to .vbb-canvas-preview).
     */
    protected function getScopedBaseCss(): string
    {
        return <<<'CSS'
/* === VBB CANVAS SCOPED STYLES === */
/* All styles are scoped to .vbb-canvas-preview to prevent CSS leak */

.vbb-canvas-preview {
  /* CSS Variables (scoped to container instead of :root) */
  /* VBB canvas = full width, block CSS defines constraints */
  --max-content-width: 100%;
  --max-text-width: 100%;
  --inline-padding: 0.5rem;
  --brand-color: #ef8248;
  --accent-color: #eb5e20;
  --dark-accent: #dd4819;
  --light-accent: #f5ad7c;
  --bg-neutral: #f6f6f6;
  --text-color: #212529;
  --border-color: #d1d1d1;
  --font-family: 'Montserrat', sans-serif;

  /* Base styling */
  font-family: var(--font-family);
  font-size: 16px;
  line-height: 1.4;
  color: var(--text-color);
  background-color: #f6f6f6;
}

/* VBB Canvas: Full width layout - blocks define their own constraints */
/* Block width is controlled by: 1) Block CSS, 2) Properties panel */
.vbb-canvas-preview .pd-base-grid,
.vbb-canvas-preview .product-description .rte-content {
  display: block;
  width: 100%;
}

/* All children get full width by default */
.vbb-canvas-preview .pd-base-grid > *,
.vbb-canvas-preview .product-description .rte-content > * {
  width: 100%;
}

/* === PD-INTRO HEADING STYLES (matches VE preview 1:1) === */
/* Grid: Row1=[Name full width], Row2=[orange line | Type] */
.vbb-canvas-preview .pd-intro__heading {
  display: grid;
  grid-template-columns: 160px auto;
  grid-template-rows: auto auto;
  gap: 0 16px;
  color: #000;
  font-size: clamp(2rem, 5vw, 3.5rem);
  line-height: 1;
  font-weight: 800;
  margin: 0 0 1.5rem 0;
}

/* Orange bar via ::before pseudo-element */
.vbb-canvas-preview .pd-intro__heading::before {
  content: "";
  display: block;
  width: 160px;
  height: 12px;
  background-color: #eb5e20;
  grid-column: 1 / 2;
  grid-row: 2 / 3;
  align-self: center;
}

/* Model name - Row 1, spans full width */
.vbb-canvas-preview .pd-model__name {
  display: block;
  font-size: inherit;
  font-weight: 800;
  color: #000;
  grid-column: 1 / -1;
  grid-row: 1 / 2;
}

/* Model type - Row 2, next to orange bar */
.vbb-canvas-preview .pd-model__type {
  display: block;
  font-size: 0.7em;
  font-weight: 400;
  color: #000;
  grid-column: 2 / -1;
  grid-row: 2 / 3;
  align-self: center;
}

/* === PD-COVER STYLES === */
.vbb-canvas-preview .pd-cover {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  padding: 3rem 2rem;
  background: #3d4654;
  color: #6b7280;
}

.vbb-canvas-preview .pd-cover__value {
  font-size: 8rem;
  font-weight: 700;
  line-height: 1;
  color: rgba(255,255,255,0.15);
}

/* === PD-ASSET-LIST STYLES (matches VE preview 1:1) === */
.vbb-canvas-preview .pd-asset-list {
  display: flex !important;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0 3rem;
  padding: 1.5rem 1rem;
  margin: 0;
  list-style: none;
}

.vbb-canvas-preview .pd-asset-list > li {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  font-size: 1.125rem;
  min-width: 100px;
  flex: 0 0 auto;
}

/* CRITICAL: order: -1 makes bold value appear FIRST even if in HTML it's second */
.vbb-canvas-preview .pd-asset-list > li b,
.vbb-canvas-preview .pd-asset-list > li strong {
  display: block;
  font-size: 2.25rem;
  font-weight: 700;
  order: -1;
}

.vbb-canvas-preview .pd-asset__value {
  font-size: 2rem;
  font-weight: 700;
  color: inherit;
}

.vbb-canvas-preview .pd-asset__label {
  font-size: 0.875rem;
  opacity: 0.8;
}

/* === BACKGROUND UTILITIES (matches VE preview 1:1) === */
.vbb-canvas-preview .bg-brand {
  background-color: #ef8248 !important;
}

/* CRITICAL: pd-asset-list text must be BLACK on orange bg-brand - matches PrestaShop 1:1 */
.vbb-canvas-preview .bg-brand .pd-asset-list,
.vbb-canvas-preview .bg-brand .pd-asset-list > li,
.vbb-canvas-preview .bg-brand .pd-asset-list > li b,
.vbb-canvas-preview .bg-brand .pd-asset-list > li strong {
  color: #000 !important;
}

.vbb-canvas-preview .bg-neutral-accent {
  background-color: #f6f6f6 !important;
}
CSS;
    }

    #[Computed]
    public function selectedElement(): ?array
    {
        if (!$this->selectedElementId) {
            return null;
        }
        return $this->findElementById($this->document['root'], $this->selectedElementId);
    }

    #[Computed]
    public function canUndo(): bool
    {
        return $this->historyIndex > 0;
    }

    #[Computed]
    public function canRedo(): bool
    {
        return $this->historyIndex < count($this->history) - 1;
    }

    #[Computed]
    public function flatElementList(): array
    {
        return $this->flattenElements($this->document['root'] ?? [], 0);
    }

    /**
     * Flatten element tree for layer panel.
     */
    protected function flattenElements(array $node, int $depth): array
    {
        $list = [[
            'id' => $node['id'],
            'type' => $node['type'],
            'content' => $node['content'] ?? '',
            'depth' => $depth,
            'visible' => $node['visible'] ?? true,
            'locked' => $node['locked'] ?? false,
            'hasChildren' => !empty($node['children']),
        ]];

        foreach ($node['children'] ?? [] as $child) {
            $list = array_merge($list, $this->flattenElements($child, $depth + 1));
        }

        return $list;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.products.visual-description.block-builder.canvas');
    }
}
