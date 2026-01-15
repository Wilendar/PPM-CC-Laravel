<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\BlockGenerator;

use App\Models\BlockDefinition;
use App\Models\PrestaShopShop;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * BlockAutoGenerator - generates block definitions from HTML.
 *
 * Analyzes prestashop-section HTML structure to create reusable dedicated blocks.
 * Detects repeaters, content fields, and CSS dependencies.
 *
 * ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System
 *
 * CRITICAL: For PrestaShop templates (pd-* classes), HTML is rendered 1:1 (passthrough)
 * without any wrappers. CSS comes from PrestaShop custom.css loaded via IFRAME isolation.
 */
class BlockAutoGenerator
{
    /**
     * PrestaShop pd-* CSS class prefixes that indicate passthrough mode.
     * When detected, HTML is rendered without modifications.
     */
    private const PRESTASHOP_CLASS_PREFIXES = [
        'pd-intro',
        'pd-cover',
        'pd-model',
        'pd-asset-list',
        'pd-merits',
        'pd-specification',
        'pd-features',
        'pd-slider',
        'pd-parallax',
        'pd-pseudo-parallax',
        'pd-more-links',
        'pd-where-2-ride',
        'pd-base-grid',
        'pd-block',
        'pd-text-block',
    ];

    /**
     * PrestaShop section types mapped to CSS class patterns.
     */
    private const PRESTASHOP_SECTION_TYPES = [
        'intro' => ['pd-intro'],
        'cover' => ['pd-cover'],
        'merits' => ['pd-merits'],
        'specification' => ['pd-specification'],
        'features' => ['pd-features'],
        'slider' => ['pd-slider', 'splide'],
        'parallax' => ['pd-pseudo-parallax', 'pd-parallax'],
        'asset-list' => ['pd-asset-list'],
        'where-2-ride' => ['pd-where-2-ride'],
        'more-links' => ['pd-more-links'],
    ];

    /**
     * Common CSS classes that indicate editable content.
     */
    private const CONTENT_CLASSES = [
        'title', 'heading', 'header', 'h1', 'h2', 'h3',
        'text', 'description', 'content', 'paragraph',
        'image', 'img', 'photo', 'icon',
        'link', 'button', 'btn', 'cta',
        'price', 'value', 'number',
    ];

    /**
     * CSS class patterns that suggest layout structure.
     */
    private const LAYOUT_PATTERNS = [
        'col', 'column', 'row', 'grid', 'flex',
        'container', 'wrapper', 'section',
        'list', 'item', 'card',
    ];

    /**
     * Generate a BlockDefinition from HTML.
     */
    public function generateFromHtml(
        string $html,
        PrestaShopShop $shop,
        string $suggestedName = 'Nowy blok'
    ): BlockAnalysisResult {
        $result = new BlockAnalysisResult();
        $result->originalHtml = $html;
        $result->shopId = $shop->id;
        $result->suggestedName = $suggestedName;

        // Parse HTML
        $doc = $this->parseHtml($html);
        if (!$doc) {
            $result->errors[] = 'Nie udalo sie sparsowac HTML';
            return $result;
        }

        // Analyze structure
        $result->structure = $this->analyzeStructure($doc);
        $result->cssClasses = $this->extractCssClasses($doc);
        $result->repeaters = $this->detectRepeaters($doc);
        $result->contentFields = $this->detectContentFields($doc);

        // CRITICAL: Detect if this is a PrestaShop template (pd-* classes)
        $result->isPrestaShopTemplate = $this->detectPrestaShopTemplate($result->cssClasses);
        $result->prestaShopSectionType = $this->detectPrestaShopSectionType($result->cssClasses);

        // Generate schema
        $result->generatedSchema = $this->generateSchema($result);

        // Generate render template
        // CRITICAL: For PrestaShop templates, use passthrough mode (no wrapper)
        $result->renderTemplate = $this->generateRenderTemplate($html, $result);

        // Generate type slug
        $result->suggestedType = BlockDefinition::generateTypeSlug($suggestedName, $shop->id);

        return $result;
    }

    /**
     * Detect if HTML contains PrestaShop pd-* CSS classes.
     */
    protected function detectPrestaShopTemplate(array $cssClasses): bool
    {
        foreach ($cssClasses as $class) {
            foreach (self::PRESTASHOP_CLASS_PREFIXES as $prefix) {
                if (str_starts_with($class, $prefix) || $class === $prefix) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Detect PrestaShop section type from CSS classes.
     */
    protected function detectPrestaShopSectionType(array $cssClasses): string
    {
        $classString = implode(' ', $cssClasses);

        foreach (self::PRESTASHOP_SECTION_TYPES as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($classString, $pattern)) {
                    return $type;
                }
            }
        }

        return 'block';
    }

    /**
     * Create BlockDefinition from analysis result.
     */
    public function createFromResult(
        BlockAnalysisResult $result,
        ?int $userId = null
    ): BlockDefinition {
        return BlockDefinition::create([
            'shop_id' => $result->shopId,
            'type' => $result->suggestedType,
            'name' => $result->suggestedName,
            'category' => 'shop-custom',
            'icon' => $this->suggestIcon($result),
            'description' => "Wygenerowany z HTML ({$result->structure['rootTag']})",
            'schema' => $result->generatedSchema,
            'render_template' => $result->renderTemplate,
            'css_classes' => $result->cssClasses,
            'sample_html' => $result->originalHtml,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    /**
     * Parse HTML into DOMDocument.
     */
    protected function parseHtml(string $html): ?DOMDocument
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        // Suppress warnings for invalid HTML
        libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="UTF-8"><div id="block-root">' . $html . '</div>';
        $loaded = $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        return $loaded ? $doc : null;
    }

    /**
     * Analyze HTML structure.
     */
    protected function analyzeStructure(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $root = $xpath->query('//div[@id="block-root"]/*[1]')->item(0);

        if (!$root instanceof DOMElement) {
            return ['rootTag' => 'div', 'depth' => 1, 'elementCount' => 0];
        }

        return [
            'rootTag' => $root->tagName,
            'rootClasses' => $this->getElementClasses($root),
            'depth' => $this->calculateDepth($root),
            'elementCount' => $this->countElements($root),
            'hasImages' => $xpath->query('.//img', $root)->length > 0,
            'hasLinks' => $xpath->query('.//a', $root)->length > 0,
            'hasList' => $xpath->query('.//ul|.//ol', $root)->length > 0,
        ];
    }

    /**
     * Extract all CSS classes from HTML.
     */
    protected function extractCssClasses(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $elements = $xpath->query('//*[@class]');
        $classes = [];

        foreach ($elements as $element) {
            if ($element instanceof DOMElement) {
                $classAttr = $element->getAttribute('class');
                foreach (explode(' ', $classAttr) as $class) {
                    $class = trim($class);
                    if ($class && !in_array($class, $classes)) {
                        $classes[] = $class;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Detect repeating elements (potential loops).
     */
    protected function detectRepeaters(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $repeaters = [];

        // Look for common list patterns
        $patterns = [
            '//ul/li' => 'items',
            '//ol/li' => 'items',
            '//*[contains(@class, "row")]/*[contains(@class, "col")]' => 'columns',
            '//*[contains(@class, "grid")]/*' => 'gridItems',
            '//*[contains(@class, "list")]/*[contains(@class, "item")]' => 'listItems',
            '//*[contains(@class, "carousel")]/*[contains(@class, "slide")]' => 'slides',
        ];

        foreach ($patterns as $xpathQuery => $suggestedName) {
            $nodes = $xpath->query($xpathQuery);
            if ($nodes->length >= 2) {
                $firstNode = $nodes->item(0);
                if ($firstNode instanceof DOMElement) {
                    $repeaters[] = [
                        'name' => $suggestedName,
                        'count' => $nodes->length,
                        'sampleHtml' => $doc->saveHTML($firstNode),
                        'tag' => $firstNode->tagName,
                        'classes' => $this->getElementClasses($firstNode),
                    ];
                }
            }
        }

        return $repeaters;
    }

    /**
     * Detect content fields (editable elements).
     */
    protected function detectContentFields(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $fields = [];

        // Headings
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $nodes = $xpath->query("//{$tag}");
            foreach ($nodes as $i => $node) {
                if ($node instanceof DOMElement) {
                    $name = $tag . ($i > 0 ? '_' . ($i + 1) : '');
                    $fields[] = [
                        'name' => $name,
                        'type' => 'text',
                        'label' => 'Naglowek ' . strtoupper($tag),
                        'tag' => $tag,
                        'currentValue' => trim($node->textContent),
                    ];
                }
            }
        }

        // Paragraphs with content
        $paragraphs = $xpath->query('//p[string-length(normalize-space()) > 10]');
        foreach ($paragraphs as $i => $p) {
            if ($p instanceof DOMElement) {
                $fields[] = [
                    'name' => 'paragraph' . ($i > 0 ? '_' . ($i + 1) : ''),
                    'type' => 'textarea',
                    'label' => 'Paragraf ' . ($i + 1),
                    'tag' => 'p',
                    'currentValue' => trim($p->textContent),
                ];
            }
        }

        // Images
        $images = $xpath->query('//img[@src]');
        foreach ($images as $i => $img) {
            if ($img instanceof DOMElement) {
                $fields[] = [
                    'name' => 'image' . ($i > 0 ? '_' . ($i + 1) : ''),
                    'type' => 'image',
                    'label' => 'Obraz ' . ($i + 1),
                    'tag' => 'img',
                    'currentValue' => $img->getAttribute('src'),
                    'alt' => $img->getAttribute('alt'),
                ];
            }
        }

        // Links/buttons
        $links = $xpath->query('//a[contains(@class, "btn") or contains(@class, "button")]');
        foreach ($links as $i => $link) {
            if ($link instanceof DOMElement) {
                $fields[] = [
                    'name' => 'button' . ($i > 0 ? '_' . ($i + 1) : ''),
                    'type' => 'link',
                    'label' => 'Przycisk ' . ($i + 1),
                    'tag' => 'a',
                    'currentValue' => trim($link->textContent),
                    'href' => $link->getAttribute('href'),
                ];
            }
        }

        return $fields;
    }

    /**
     * Generate schema from analysis.
     *
     * CRITICAL for PrestaShop templates:
     * - Uses single 'html' field for passthrough rendering
     * - Minimal settings (no wrappers needed)
     * - Section type stored in settings for reference
     */
    protected function generateSchema(BlockAnalysisResult $result): array
    {
        // CRITICAL: PrestaShop templates use passthrough schema
        // Single 'html' content field, minimal settings
        if ($result->isPrestaShopTemplate ?? false) {
            return [
                'content' => [
                    'html' => [
                        'type' => 'code',
                        'label' => 'Kod HTML (PrestaShop)',
                        'language' => 'html',
                        'required' => true,
                        'rows' => 20,
                        'default' => $result->originalHtml,
                        'help' => 'Oryginalny HTML z PrestaShop - edytuj ostroznie. Style pochodza z custom.css sklepu.',
                    ],
                ],
                'settings' => [
                    [
                        'name' => 'section_type',
                        'type' => 'select',
                        'label' => 'Typ sekcji PrestaShop',
                        'options' => [
                            'intro' => 'Intro (naglowek produktu)',
                            'cover' => 'Cover (zdjecie glowne)',
                            'merits' => 'Zalety (lista)',
                            'specification' => 'Specyfikacja',
                            'features' => 'Cechy (checkmark lista)',
                            'slider' => 'Slider (karuzela)',
                            'parallax' => 'Parallax (obraz)',
                            'asset-list' => 'Lista cech',
                            'where-2-ride' => 'Gdzie jezdzic',
                            'more-links' => 'Linki',
                            'block' => 'Blok ogolny',
                        ],
                        'default' => $result->prestaShopSectionType ?? 'block',
                        'readonly' => true,
                    ],
                    [
                        'name' => 'original_classes',
                        'type' => 'text',
                        'label' => 'Oryginalne klasy CSS',
                        'default' => implode(' ', $result->cssClasses ?? []),
                        'readonly' => true,
                        'help' => 'Klasy CSS wykryte w HTML - style sa ladowane z PrestaShop.',
                    ],
                ],
            ];
        }

        // Non-PrestaShop templates: traditional approach
        $content = [];
        $settings = [];

        // Content fields from detection
        foreach ($result->contentFields as $field) {
            $content[$field['name']] = [
                'type' => $field['type'],
                'label' => $field['label'],
                'default' => $field['currentValue'] ?? '',
            ];

            // Add href for links
            if ($field['type'] === 'link' && isset($field['href'])) {
                $content[$field['name'] . '_href'] = [
                    'type' => 'text',
                    'label' => $field['label'] . ' - URL',
                    'default' => $field['href'],
                ];
            }
        }

        // Repeaters as array fields
        foreach ($result->repeaters as $repeater) {
            $content[$repeater['name']] = [
                'type' => 'repeater',
                'label' => ucfirst($repeater['name']),
                'default' => [],
                'itemSchema' => $this->generateRepeaterItemSchema($repeater),
            ];
        }

        // Default settings
        $settings = [
            [
                'name' => 'cssClass',
                'type' => 'text',
                'label' => 'Dodatkowe klasy CSS',
                'default' => '',
            ],
            [
                'name' => 'containerWidth',
                'type' => 'select',
                'label' => 'Szerokosc kontenera',
                'options' => [
                    'full' => 'Pelna szerokosc',
                    'container' => 'Kontener',
                    'narrow' => 'Waski',
                ],
                'default' => 'full',
            ],
        ];

        return [
            'content' => $content,
            'settings' => $settings,
        ];
    }

    /**
     * Generate schema for repeater item.
     */
    protected function generateRepeaterItemSchema(array $repeater): array
    {
        // Basic item schema based on detected content
        return [
            'text' => [
                'type' => 'text',
                'label' => 'Tekst',
            ],
            'icon' => [
                'type' => 'text',
                'label' => 'Ikona (klasa)',
            ],
        ];
    }

    /**
     * Generate render template from HTML.
     *
     * CRITICAL for PrestaShop templates:
     * - Returns HTML 1:1 (passthrough mode) WITHOUT any wrappers
     * - CSS comes from PrestaShop custom.css, NOT inline styles
     * - This ensures visual 1:1 match with PrestaShop frontend
     *
     * For non-PrestaShop templates:
     * - Replaces detected content with placeholders
     * - Adds standard pd-block wrapper
     */
    protected function generateRenderTemplate(string $html, BlockAnalysisResult $result): string
    {
        // CRITICAL: PrestaShop templates use PASSTHROUGH mode
        // HTML is rendered exactly as stored, no wrappers, no modifications
        // CSS styling comes from PrestaShop custom.css loaded via IFRAME isolation
        if ($result->isPrestaShopTemplate ?? false) {
            // For PrestaShop templates: store raw HTML in content.html field
            // The DynamicBlock will render it via passthrough (like PrestashopSectionBlock)
            return '{{ $content.html }}';
        }

        // Non-PrestaShop templates: use traditional approach with placeholders
        $template = $html;

        // Replace content fields with placeholders
        foreach ($result->contentFields as $field) {
            $currentValue = $field['currentValue'] ?? '';
            if (empty($currentValue)) {
                continue;
            }

            $placeholder = '{{ $content.' . $field['name'] . ' }}';

            // For text/textarea - replace inner text
            if (in_array($field['type'], ['text', 'textarea'])) {
                $escapedValue = preg_quote($currentValue, '/');
                $template = preg_replace(
                    '/>' . $escapedValue . '</',
                    '>' . $placeholder . '<',
                    $template,
                    1
                );
            }

            // For images - replace src
            if ($field['type'] === 'image') {
                $escapedSrc = preg_quote($currentValue, '/');
                $template = preg_replace(
                    '/src=["\']' . $escapedSrc . '["\']/',
                    'src="' . $placeholder . '"',
                    $template,
                    1
                );
            }
        }

        // Add wrapper with settings class (only for non-PrestaShop blocks)
        $template = '<div class="pd-block pd-block--dynamic {{ $settings.cssClass }}">' .
            "\n" . $template . "\n" . '</div>';

        return $template;
    }

    /**
     * Suggest icon based on detected content.
     */
    protected function suggestIcon(BlockAnalysisResult $result): string
    {
        if ($result->structure['hasImages']) {
            return 'heroicons-photo';
        }

        if ($result->structure['hasList']) {
            return 'heroicons-list-bullet';
        }

        if (!empty($result->repeaters)) {
            return 'heroicons-squares-2x2';
        }

        $rootClasses = implode(' ', $result->structure['rootClasses'] ?? []);

        if (Str::contains($rootClasses, ['banner', 'hero'])) {
            return 'heroicons-rectangle-group';
        }

        if (Str::contains($rootClasses, ['card', 'box'])) {
            return 'heroicons-square-3-stack-3d';
        }

        if (Str::contains($rootClasses, ['feature', 'merit', 'benefit'])) {
            return 'heroicons-check-badge';
        }

        return 'heroicons-cube';
    }

    /**
     * Get element classes as array.
     */
    protected function getElementClasses(DOMElement $element): array
    {
        $classAttr = $element->getAttribute('class');
        return array_filter(array_map('trim', explode(' ', $classAttr)));
    }

    /**
     * Calculate DOM depth.
     */
    protected function calculateDepth(DOMElement $element, int $current = 0): int
    {
        $maxDepth = $current;

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $childDepth = $this->calculateDepth($child, $current + 1);
                $maxDepth = max($maxDepth, $childDepth);
            }
        }

        return $maxDepth;
    }

    /**
     * Count total elements.
     */
    protected function countElements(DOMElement $element): int
    {
        $count = 1;

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $count += $this->countElements($child);
            }
        }

        return $count;
    }
}
