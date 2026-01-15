<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Services\VisualEditor\PropertyPanel\PropertyPanelService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

/**
 * UVE Property Panel Trait - ETAP_07f_P5 FAZA PP.4
 *
 * Glowny trait do integracji Property Panel z UnifiedVisualEditor.
 * Zarzadza konfiguracja panelu, zakladkami, stanami hover i responsive.
 */
trait UVE_PropertyPanel
{
    // =====================
    // PANEL STATE
    // =====================

    /** @var string Active tab: 'style' | 'layout' | 'advanced' | 'classes' */
    public string $activeTab = 'style';

    /** @var string Hover state being edited: 'normal' | 'hover' */
    public string $hoverState = 'normal';

    /** @var array Current element styles (normal state) */
    public array $elementStyles = [];

    /** @var array Current element hover styles */
    public array $elementHoverStyles = [];

    /** @var array Current element CSS classes */
    public array $elementClasses = [];

    /** @var array Cache of element styles per elementId (persists across element switches) */
    public array $elementStylesCache = [];

    /** @var array<string, int> Last client update sequence per elementId|controlId */
    public array $lastControlClientSeq = [];

    /** @var string|null PP.0.4: Block type from iframe (intro, cover, slider, etc.) */
    public ?string $selectedBlockType = null;

    /** @var PropertyPanelService|null Lazy-loaded service */
    protected ?PropertyPanelService $propertyPanelService = null;

    // =====================
    // SERVICE ACCESS
    // =====================

    /**
     * Get PropertyPanelService instance (lazy loaded)
     */
    protected function getPropertyPanelService(): PropertyPanelService
    {
        if ($this->propertyPanelService === null) {
            $this->propertyPanelService = app(PropertyPanelService::class);
        }
        return $this->propertyPanelService;
    }

    // =====================
    // PANEL CONFIGURATION
    // =====================

    /**
     * Build panel configuration for selected element
     * NOTE: Not using #[Computed] to ensure fresh calculation on every call
     * This is critical for styles to persist when switching between elements
     *
     * @return array Panel configuration with tabs, controls, values
     */
    public function panelConfig(): array
    {
        \Log::info('UVE_PropertyPanel::panelConfig CALLED', [
            'selectedElementId' => $this->selectedElementId,
            'selectedBlockIndex' => $this->selectedBlockIndex ?? 'null',
        ]);

        if (!$this->selectedElementId) {
            \Log::info('UVE_PropertyPanel::panelConfig - No selectedElementId, returning empty');
            return [
                'tabs' => [],
                'controls' => [],
                'values' => [],
                'cssClasses' => [],
                'readonlyClasses' => [],
                'elementType' => 'div',
                'hoverSupported' => [],
                'responsive' => [],
                'classesTab' => ['current' => [], 'available' => [], 'prestashopClasses' => []],
                'hoverValues' => [],
                'responsiveValues' => [],
            ];
        }

        $element = $this->getSelectedElementData();
        if (!$element) {
            \Log::info('UVE_PropertyPanel::panelConfig - No element data found');
            return $this->getEmptyPanelConfig();
        }

        $cssClasses = $element['classes'] ?? [];
        // CRITICAL FIX: Always use $this->elementStyles which is set by onElementSelectedForPanel
        // This property is loaded from cache when switching elements, ensuring persistence
        // We only fallback to element HTML styles if elementStyles is empty AND we have no cache
        $currentStyles = !empty($this->elementStyles)
            ? $this->elementStyles
            : ($this->elementStylesCache[$this->selectedElementId] ?? ($element['styles'] ?? []));
        $elementType = $element['tag'] ?? 'div';

        // Get block type for block-specific controls (ETAP_07h Property Panel)
        // PP.0.4: Prioritize $selectedBlockType from iframe (intro, cover, slider, etc.)
        $blockType = $this->selectedBlockType;

        // ETAP_07h FIX: For raw-html blocks, detect section type from element CSS classes
        // This is critical - PropertyPanelService needs actual section type (cover, parallax)
        // not the parent block type (raw-html)
        if (!$blockType || $blockType === 'raw-html') {
            $detectedType = $this->detectSectionTypeFromClasses($cssClasses);
            if ($detectedType) {
                $blockType = $detectedType;
                \Log::info('[UVE_PropertyPanel] Detected section type from CSS classes', [
                    'cssClasses' => $cssClasses,
                    'detectedType' => $detectedType,
                ]);
            }
        }

        // Fallback: try to get from blocks array if not provided by iframe
        if (!$blockType) {
            $blockIndex = $this->editingBlockIndex ?? $this->selectedBlockIndex;
            if ($blockIndex !== null && isset($this->blocks[$blockIndex])) {
                $rawBlock = $this->blocks[$blockIndex];
                $block = $this->normalizeBlock($rawBlock);
                $blockType = $block['type'] ?? null;
            }
        }

        // Build config using PropertyPanelService
        $service = $this->getPropertyPanelService();
        $config = $service->buildPanelConfig($cssClasses, $currentStyles, $elementType, $blockType);

        \Log::info('UVE_PropertyPanel::panelConfig RESULT', [
            'elementType' => $elementType,
            'blockType' => $blockType,
            'blockTypeSource' => $this->selectedBlockType ? 'iframe' : 'blocks_array',
            'controlTypes' => array_keys($config['controls'] ?? []),
            'controlCount' => count($config['controls'] ?? []),
        ]);

        // Add hover and responsive values
        $config['hoverValues'] = $this->elementHoverStyles;
        $config['responsiveValues'] = $this->getResponsiveStylesForElement();

        return $config;
    }

    /**
     * Normalize block structure - handles nested array format [[{block}]] -> {block}
     * Unwraps nested indexed arrays until we reach an associative array with block data
     */
    protected function normalizeBlock($block): ?array
    {
        // Handle nested indexed array structure: [[{block}]] -> {block}
        // Keep unwrapping while we have indexed arrays (numeric keys starting from 0)
        while (is_array($block) && $this->isIndexedArray($block)) {
            $block = $block[0] ?? null;
            if ($block === null) {
                return null;
            }
        }

        // Ensure we have an array with expected keys (associative block data)
        if (!is_array($block) || (!isset($block['type']) && !isset($block['id']) && !isset($block['content']))) {
            return null;
        }

        return $block;
    }

    /**
     * Check if array is indexed (sequential numeric keys) vs associative (string keys)
     */
    protected function isIndexedArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        // Check if first key is 0 and all keys are sequential integers
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Get selected element data from document tree
     */
    protected function getSelectedElementData(): ?array
    {
        if (!$this->selectedElementId) {
            Log::debug('[UVE_PropertyPanel] getSelectedElementData: no element selected');
            return null;
        }

        // Use editingBlockIndex if set, otherwise selectedBlockIndex
        $blockIndex = $this->editingBlockIndex ?? $this->selectedBlockIndex;
        $originalBlockIndex = $blockIndex;

        // Extract block index from elementId using parseBlockIndexFromUveId()
        // CRITICAL: Visual block number in DOM != array index in $this->blocks!
        // parseBlockIndexFromUveId() handles this mapping correctly with fallback for raw-html blocks
        $extractedBlockIndex = $this->parseBlockIndexFromUveId($this->selectedElementId);

        if ($blockIndex === null && $extractedBlockIndex === null) {
            Log::debug('[UVE_PropertyPanel] getSelectedElementData: no block index available', [
                'editingBlockIndex' => $this->editingBlockIndex,
                'selectedBlockIndex' => $this->selectedBlockIndex ?? 'N/A',
                'selectedElementId' => $this->selectedElementId,
            ]);
            return null;
        }

        // Try extracted index first (from elementId), then original, then fallback to 0
        // This handles raw-html imports where entire HTML is ONE block but DOM has multiple sections
        $indicesToTry = array_unique(array_filter([
            $extractedBlockIndex,
            $originalBlockIndex,
            0, // Fallback - most imported content is in first block
        ], fn($v) => $v !== null));

        Log::info('[UVE_PropertyPanel] getSelectedElementData: searching blocks', [
            'indicesToTry' => $indicesToTry,
            'totalBlocks' => count($this->blocks),
            'elementId' => $this->selectedElementId,
        ]);

        $block = null;
        $usedBlockIndex = null;

        foreach ($indicesToTry as $tryIndex) {
            $rawBlock = $this->blocks[$tryIndex] ?? null;
            if ($rawBlock !== null) {
                $normalizedBlock = $this->normalizeBlock($rawBlock);
                if ($normalizedBlock) {
                    $block = $normalizedBlock;
                    $usedBlockIndex = $tryIndex;
                    Log::info('[UVE_PropertyPanel] getSelectedElementData: found block', [
                        'triedIndex' => $tryIndex,
                        'blockType' => $block['type'] ?? 'unknown',
                    ]);
                    break;
                }
            }
        }

        if (!$block) {
            Log::info('[UVE_PropertyPanel] getSelectedElementData: no valid block found', [
                'indicesToTry' => $indicesToTry,
                'totalBlocks' => count($this->blocks),
                'blockKeys' => array_keys($this->blocks),
            ]);
            return null;
        }

        // Update blockIndex to the one we actually used
        $blockIndex = $usedBlockIndex;

        Log::debug('[UVE_PropertyPanel] getSelectedElementData: analyzing block', [
            'blockType' => $block['type'] ?? 'unknown',
            'hasDocumentRoot' => isset($block['document']['root']),
            'hasCompiledHtml' => !empty($block['compiled_html'] ?? ''),
            'hasContentHtml' => !empty($block['content'][0]['html'] ?? $block['content']['html'] ?? ''),
            'selectedElementId' => $this->selectedElementId,
        ]);

        // For structured blocks with document tree, search there first
        if (isset($block['document']['root'])) {
            $element = $this->findElementById($block['document']['root'], $this->selectedElementId);
            if ($element) {
                Log::debug('[UVE_PropertyPanel] Found element in document tree', [
                    'elementId' => $element['id'] ?? 'unknown',
                    'tag' => $element['tag'] ?? 'unknown',
                    'classes' => $element['classes'] ?? [],
                ]);
                return $element;
            }
        }

        // Fallback: extract element info from compiled_html or content (works for ALL block types)
        // Handle various content structures: content[0]['html'], content['html'], compiled_html
        $html = $block['compiled_html'] ?? '';
        if (empty($html) && isset($block['content'])) {
            if (is_array($block['content']) && isset($block['content'][0]['html'])) {
                $html = $block['content'][0]['html'];
            } elseif (is_array($block['content']) && isset($block['content']['html'])) {
                $html = $block['content']['html'];
            }
        }

        if (!empty($html)) {
            $element = $this->extractElementFromHtml($html, $this->selectedElementId);
            if ($element) {
                Log::debug('[UVE_PropertyPanel] Found element in HTML', [
                    'elementId' => $element['id'] ?? 'unknown',
                    'tag' => $element['tag'] ?? 'unknown',
                    'classes' => $element['classes'] ?? [],
                ]);
                return $element;
            }
        }

        Log::debug('[UVE_PropertyPanel] Element not found in any source');
        return null;
    }

    /**
     * Extract element data from HTML by elementId
     * Supports: data-uve-id, id attribute, or positional parsing (block-X-tagname-Y)
     */
    protected function extractElementFromHtml(string $html, string $elementId): ?array
    {
        if (empty($html) || empty($elementId)) {
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $node = null;

        // Try data-uve-id first
        $nodes = $xpath->query("//*[@data-uve-id='{$elementId}']");
        if ($nodes->length > 0) {
            $node = $nodes->item(0);
        }

        // Fallback: try id attribute
        if (!$node) {
            $nodes = $xpath->query("//*[@id='{$elementId}']");
            if ($nodes->length > 0) {
                $node = $nodes->item(0);
            }
        }

        // Fallback: parse positional ID like "block-0-heading-0" or "block-0-p-1"
        if (!$node && preg_match('/^block-\d+-([a-z0-9]+)-(\d+)$/i', $elementId, $matches)) {
            $tagName = $this->normalizeTagName($matches[1]);
            $index = (int) $matches[2];

            $nodes = $xpath->query("//{$tagName}");
            if ($nodes->length > $index) {
                $node = $nodes->item($index);
            }
        }

        // Fallback: root block element "block-N" (without child element suffix)
        // PP.0.2: Support selecting the block root itself
        // ETAP_07h FIX: Use same XPath as parseRawHtmlLayers() to find pd-* blocks
        // Raw HTML doesn't have data-uve-type markers - they're only in iframe
        if (!$node && preg_match('/^block-(\d+)$/i', $elementId, $matches)) {
            $blockIndex = (int) $matches[1];

            // First try: elements with data-uve-type="block" (iframe HTML)
            $blocks = $xpath->query("//*[@data-uve-type='block']");
            if ($blocks->length > $blockIndex) {
                $node = $blocks->item($blockIndex);
            }

            // ETAP_07h FIX: Second try - raw HTML without markers (uses pd-* classes)
            // This matches parseRawHtmlLayers() XPath exactly
            if (!$node) {
                $blocks = $xpath->query('//*[(contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-merits") or contains(@class, "pd-specification") or contains(@class, "pd-features") or contains(@class, "pd-cover") or contains(@class, "pd-slider") or contains(@class, "pd-pseudo-parallax") or contains(@class, "pd-parallax") or contains(@class, "pd-more-links") or contains(@class, "pd-footer") or contains(@class, "pd-header") or contains(@class, "pd-asset-list") or contains(@class, "pd-where-2-ride")) and not(contains(@class, "__"))]');
                if ($blocks->length > $blockIndex) {
                    $node = $blocks->item($blockIndex);
                }
            }
        }

        if (!$node) {
            Log::info('[UVE_PropertyPanel] extractElementFromHtml: element not found', [
                'elementId' => $elementId,
                'htmlLen' => strlen($html),
                'triedPatterns' => [
                    'data-uve-id' => true,
                    'id-attribute' => true,
                    'positional-block-X-tag-Y' => preg_match('/^block-\d+-([a-z0-9]+)-(\d+)$/i', $elementId),
                    'block-N-root' => preg_match('/^block-(\d+)$/i', $elementId),
                ],
            ]);
            return null;
        }

        Log::info('[UVE_PropertyPanel] extractElementFromHtml: element FOUND', [
            'elementId' => $elementId,
            'nodeName' => $node->nodeName,
            'classes' => $node->getAttribute('class'),
        ]);

        // Extract classes
        $classString = $node->getAttribute('class') ?? '';
        $classes = array_filter(explode(' ', $classString));

        // Extract inline styles
        $styleString = $node->getAttribute('style') ?? '';
        $styles = $this->parseInlineStyles($styleString);

        // Extract attributes specific to element type
        $tagName = strtolower($node->nodeName);
        $attributes = [];

        // For IMG elements, extract src and alt
        if ($tagName === 'img') {
            $src = $node->getAttribute('src');
            if ($src) {
                $attributes['src'] = $src;
                $styles['src'] = $src; // Also add to styles so it's available in Property Panel
                $styles['imageUrl'] = $src;
            }
            $alt = $node->getAttribute('alt');
            if ($alt) {
                $attributes['alt'] = $alt;
            }
        }

        // For A elements, extract href
        if ($tagName === 'a') {
            $href = $node->getAttribute('href');
            if ($href) {
                $attributes['href'] = $href;
            }
        }

        return [
            'id' => $elementId,
            'tag' => $tagName,
            'classes' => $classes,
            'styles' => $styles,
            'attributes' => $attributes,
            'content' => $node->textContent,
        ];
    }

    /**
     * Normalize tag name from elementId to actual HTML tag
     */
    protected function normalizeTagName(string $name): string
    {
        $map = [
            'heading' => 'h2',    // Most common heading
            'text' => 'p',        // Text elements map to paragraph
            'image' => 'img',     // CRITICAL: 'image' in elementId maps to 'img' HTML tag
            'listitem' => 'li',   // PP.0.3: List items from UVE markers
            'cell' => 'td',       // PP.0.3: Table cells (td/th) - td as default
            'h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6',
            'p' => 'p',
            'div' => 'div',
            'span' => 'span',
            'img' => 'img',
            'a' => 'a',
            'ul' => 'ul',
            'ol' => 'ol',
            'li' => 'li',
            'section' => 'section',
            'article' => 'article',
            'header' => 'header',
            'footer' => 'footer',
            'nav' => 'nav',
            'aside' => 'aside',
            'main' => 'main',
            'figure' => 'figure',
            'figcaption' => 'figcaption',
            'table' => 'table',
            'tr' => 'tr',
            'td' => 'td',
            'th' => 'th',
            'form' => 'form',
            'input' => 'input',
            'button' => 'button',
            'label' => 'label',
            'strong' => 'strong',
            'em' => 'em',
            'blockquote' => 'blockquote',
        ];

        return $map[strtolower($name)] ?? strtolower($name);
    }

    /**
     * Parse inline style string to array
     */
    protected function parseInlineStyles(string $styleString): array
    {
        $styles = [];
        if (empty($styleString)) {
            return $styles;
        }

        $pairs = explode(';', $styleString);
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) continue;

            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);
                // Convert to camelCase
                $camelProperty = $this->toCamelCase($property);
                $styles[$camelProperty] = $value;
            }
        }

        return $styles;
    }

    /**
     * Convert kebab-case to camelCase
     */
    protected function toCamelCase(string $property): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $property))));
    }

    /**
     * Get empty panel config
     */
    protected function getEmptyPanelConfig(): array
    {
        return [
            'tabs' => [],
            'controls' => [],
            'values' => [],
            'cssClasses' => [],
            'readonlyClasses' => [],
            'elementType' => 'div',
            'hoverSupported' => [],
            'responsive' => [],
            'classesTab' => ['current' => [], 'available' => [], 'prestashopClasses' => []],
            'hoverValues' => [],
            'responsiveValues' => [],
        ];
    }

    // =====================
    // TAB MANAGEMENT
    // =====================

    /**
     * Switch active tab
     */
    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['style', 'layout', 'advanced', 'classes'])) {
            $this->activeTab = $tab;
        }
    }

    // =====================
    // HOVER STATE MANAGEMENT
    // =====================

    /**
     * Switch hover state being edited
     */
    public function switchHoverState(string $state): void
    {
        if (in_array($state, ['normal', 'hover'])) {
            $this->hoverState = $state;

            // Sync panel to show appropriate values
            if ($state === 'hover') {
                $this->loadHoverStyles();
            }
        }
    }

    /**
     * Load hover styles for current element
     */
    protected function loadHoverStyles(): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $block = $this->blocks[$this->editingBlockIndex] ?? null;
        if (!$block) {
            return;
        }

        // Get hover styles from element metadata
        $element = $this->getSelectedElementData();
        if ($element) {
            $this->elementHoverStyles = $element['hoverStyles'] ?? [];
        }
    }

    /**
     * Apply hover preset to element
     */
    public function applyHoverPreset(array $presetConfig): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $this->captureState();

        // Apply transition settings
        if (isset($presetConfig['transition'])) {
            $this->updateControlValue('transition', $presetConfig['transition']);
        }

        // Apply hover styles
        if (isset($presetConfig['hover'])) {
            $this->elementHoverStyles = array_merge($this->elementHoverStyles, $presetConfig['hover']);
            $this->applyHoverStylesToElement();
        }

        $this->isDirty = true;
        $this->dispatch('uve-preview-refresh');
    }

    /**
     * Apply hover styles to element in document
     */
    protected function applyHoverStylesToElement(): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $this->updateElementInTree($this->selectedElementId, function ($element) {
            $element['hoverStyles'] = $this->elementHoverStyles;
            return $element;
        });
    }

    // =====================
    // CONTROL VALUE UPDATES
    // =====================

    /**
     * Update control value from panel
     *
     * @param string $controlId Control identifier (e.g., 'typography', 'color')
     * @param mixed $value New value
     */
    public function updateControlValue(string $controlId, mixed $value): void
    {
        // If editingBlockIndex is null but we have selectedElementId, resolve block index properly
        // selectedElementId format: "block-{visualNum}-{type}-{subindex}" e.g. "block-1-image-0"
        // CRITICAL: Visual block number in DOM != array index in $this->blocks!
        $blockIndex = $this->editingBlockIndex;
        if ($blockIndex === null && $this->selectedElementId) {
            // Use parseBlockIndexFromUveId() which handles mapping correctly
            $blockIndex = $this->parseBlockIndexFromUveId($this->selectedElementId);
        }

        Log::info('[UVE_PropertyPanel] updateControlValue CALLED', [
            'controlId' => $controlId,
            'value' => $value,
            'selectedElementId' => $this->selectedElementId,
            'editingBlockIndex' => $this->editingBlockIndex,
            'resolvedBlockIndex' => $blockIndex,
        ]);

        if (!$this->selectedElementId || $blockIndex === null) {
            Log::info('[UVE_PropertyPanel] updateControlValue EARLY RETURN - no selection');
            return;
        }

        $clientSeq = null;

        // Guard against out-of-order requests (e.g. rapid clicking):
        // ignore updates with older client sequence numbers for the same element + control.
        if (is_array($value) && isset($value['_clientSeq']) && is_numeric($value['_clientSeq'])) {
            $clientSeq = (int) $value['_clientSeq'];
            unset($value['_clientSeq']);

            $seqKey = "{$this->selectedElementId}|{$controlId}";
            $lastSeq = (int) ($this->lastControlClientSeq[$seqKey] ?? 0);

            if ($clientSeq < $lastSeq) {
                Log::info('[UVE_PropertyPanel] updateControlValue IGNORED stale client seq', [
                    'controlId' => $controlId,
                    'selectedElementId' => $this->selectedElementId,
                    'clientSeq' => $clientSeq,
                    'lastSeq' => $lastSeq,
                ]);
                return;
            }

            $this->lastControlClientSeq[$seqKey] = $clientSeq;
        }

        // Use resolved block index
        $this->editingBlockIndex = $blockIndex;

        // Determine if we're editing hover state
        $isHover = $this->hoverState === 'hover';

        if ($isHover) {
            $this->updateHoverControlValue($controlId, $value);
        } else {
            $this->updateNormalControlValue($controlId, $value);
        }

        $this->isDirty = true;
    }

    /**
     * Update normal state control value
     */
    protected function updateNormalControlValue(string $controlId, mixed $value): void
    {
        $service = $this->getPropertyPanelService();

        // Convert control value to CSS properties
        $cssProperties = $service->formatToCss($controlId, $value);

        // Update element styles in tree AND sync to $this->elementStyles
        $found = $this->updateElementInTree($this->selectedElementId, function ($element) use ($cssProperties) {
            $element['styles'] = array_merge($element['styles'] ?? [], $cssProperties);
            // Remove empty values
            $element['styles'] = array_filter($element['styles'], fn($v) => $v !== '' && $v !== null);
            return $element;
        });

        Log::info('[UVE_PropertyPanel] updateElementInTree result', [
            'elementId' => $this->selectedElementId,
            'found' => $found,
        ]);

        // CRITICAL: Update $this->elementStyles to match the updated element tree
        $camelCaseProperties = [];
        foreach ($cssProperties as $property => $cssValue) {
            $camelCaseProperty = $this->kebabToCamelCase($property);
            $camelCaseProperties[$camelCaseProperty] = $cssValue;

            if ($camelCaseProperty !== $property) {
                unset($this->elementStyles[$property]);
            }
        }

        // PP.0.8 FIX: Preserve control-level values for controls that need them
        // image-settings needs 'size' and 'alignment' presets to re-initialize correctly
        // Without this, the control derives presets from computed CSS which causes desync
        // Also preserve imageUrl/src for image source management
        if ($controlId === 'image-settings' && is_array($value)) {
            $controlLevelProps = ['imageUrl', 'src', 'size', 'alignment', 'objectFit', 'borderRadius', 'shadow', 'lightbox', 'lazyLoad'];
            foreach ($controlLevelProps as $prop) {
                if (isset($value[$prop])) {
                    $camelCaseProperties[$prop] = $value[$prop];
                }
            }
            Log::info('[UVE_PropertyPanel] image-settings: preserved control-level values', [
                'imageUrl' => $value['imageUrl'] ?? null,
                'src' => $value['src'] ?? null,
                'size' => $value['size'] ?? null,
                'alignment' => $value['alignment'] ?? null,
            ]);
        }

        // slider-settings also needs its control values preserved
        if ($controlId === 'slider-settings' && is_array($value)) {
            $controlLevelProps = ['type', 'perPage', 'autoplay', 'interval', 'arrows', 'pagination', 'speed', 'gap', 'breakpoints'];
            foreach ($controlLevelProps as $prop) {
                if (isset($value[$prop])) {
                    $camelCaseProperties[$prop] = $value[$prop];
                }
            }
        }

        $this->elementStyles = array_merge($this->elementStyles, $camelCaseProperties);
        $this->elementStyles = array_filter($this->elementStyles, fn($v) => $v !== '' && $v !== null);

        // CRITICAL FIX: Save to cache so styles persist across element switches
        // This is essential for blocks without document['root'] where updateElementInTree fails
        $this->elementStylesCache[$this->selectedElementId] = $this->elementStyles;

        // CSS-FIRST ARCHITECTURE (ETAP_07h): Save to CSS rules instead of inline styles
        // This generates CSS class instead of inline style="" attribute
        if (method_exists($this, 'setCssRule')) {
            $this->setCssRule($this->selectedElementId, $this->elementStyles);
        }

        // CRITICAL FIX: For raw-html blocks (without document['root']), save styles directly to block
        // This ensures styles are persisted when save() is called
        if (!$found && $this->editingBlockIndex !== null) {
            if (!isset($this->blocks[$this->editingBlockIndex]['elementStyles'])) {
                $this->blocks[$this->editingBlockIndex]['elementStyles'] = [];
            }
            $this->blocks[$this->editingBlockIndex]['elementStyles'][$this->selectedElementId] = $this->elementStyles;
            Log::info('[UVE_PropertyPanel] Saved styles to block.elementStyles (raw-html fallback)', [
                'blockIndex' => $this->editingBlockIndex,
                'elementId' => $this->selectedElementId,
            ]);
        }

        Log::info('[UVE_PropertyPanel] Updated styles', [
            'controlId' => $controlId,
            'cssProperties' => $cssProperties,
            'elementStyles' => $this->elementStyles,
            'savedToCache' => true,
            'cssRuleSet' => method_exists($this, 'setCssRule'),
        ]);

        // Recompile block HTML
        Log::info('[UVE_PropertyPanel] Calling compileBlockHtml', ['blockIndex' => $this->editingBlockIndex]);
        $this->compileBlockHtml($this->editingBlockIndex);

        // Sync to iframe
        $this->syncToIframe($controlId, null);
    }

    /**
     * Update hover state control value
     */
    protected function updateHoverControlValue(string $controlId, mixed $value): void
    {
        $service = $this->getPropertyPanelService();

        // Convert control value to CSS properties
        $cssProperties = $service->formatToCss($controlId, $value);

        // Update hover styles
        $this->elementHoverStyles = array_merge($this->elementHoverStyles, $cssProperties);

        // Save to element
        $this->applyHoverStylesToElement();

        // Sync hover styles to iframe
        $this->syncToIframe($controlId, null);
    }

    // =====================
    // CSS CLASS MANAGEMENT
    // =====================

    /**
     * Add CSS class to element
     */
    public function addClass(string $className): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $className = trim($className);
        if (empty($className)) {
            return;
        }

        $this->captureState();

        $this->updateElementInTree($this->selectedElementId, function ($element) use ($className) {
            $classes = $element['classes'] ?? [];
            if (!in_array($className, $classes)) {
                $classes[] = $className;
            }
            $element['classes'] = $classes;
            return $element;
        });

        $this->compileBlockHtml($this->editingBlockIndex);
        $this->isDirty = true;
        $this->syncToIframe();
    }

    /**
     * Remove CSS class from element
     */
    public function removeClass(string $className): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $this->captureState();

        $this->updateElementInTree($this->selectedElementId, function ($element) use ($className) {
            $classes = $element['classes'] ?? [];
            $element['classes'] = array_values(array_filter($classes, fn($c) => $c !== $className));
            return $element;
        });

        $this->compileBlockHtml($this->editingBlockIndex);
        $this->isDirty = true;
        $this->syncToIframe();
    }

    // =====================
    // STYLE OPERATIONS
    // =====================

    /**
     * Reset all styles on element
     */
    public function resetElementStyles(): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $this->captureState();

        $this->updateElementInTree($this->selectedElementId, function ($element) {
            $element['styles'] = [];
            $element['hoverStyles'] = [];
            return $element;
        });

        $this->elementStyles = [];
        $this->elementHoverStyles = [];

        // Clear cache for this element
        if ($this->selectedElementId) {
            unset($this->elementStylesCache[$this->selectedElementId]);
        }

        $this->compileBlockHtml($this->editingBlockIndex);
        $this->isDirty = true;
        $this->syncToIframe();

        $this->dispatch('notify', type: 'info', message: 'Style zresetowane');
    }

    /**
     * Apply styles from clipboard
     */
    public function applyStyles(array $styles): void
    {
        if (!$this->selectedElementId || $this->editingBlockIndex === null) {
            return;
        }

        $this->captureState();

        $this->updateElementInTree($this->selectedElementId, function ($element) use ($styles) {
            $element['styles'] = array_merge($element['styles'] ?? [], $styles);
            return $element;
        });

        $this->compileBlockHtml($this->editingBlockIndex);
        $this->isDirty = true;
        $this->syncToIframe();
    }

    // =====================
    // IFRAME SYNCHRONIZATION
    // =====================

    /**
     * Sync current element styles to iframe
     */
    public function syncToIframe(?string $controlId = null, ?int $clientSeq = null): void
    {
        Log::info('[UVE_PropertyPanel] syncToIframe CALLED', [
            'selectedElementId' => $this->selectedElementId,
            'elementStyles' => $this->elementStyles,
        ]);

        if (!$this->selectedElementId) {
            Log::info('[UVE_PropertyPanel] syncToIframe EARLY RETURN - no selection');
            return;
        }

        // CSS-FIRST ARCHITECTURE (ETAP_07h): Include class name for final output
        $className = '';
        if (method_exists($this, 'getCssClassForElement')) {
            $className = $this->getCssClassForElement($this->selectedElementId);
        }

        // Build JavaScript data for direct call
        $jsData = json_encode([
            'elementId' => $this->selectedElementId,
            'controlId' => $controlId,
            'clientSeq' => $clientSeq,
            'styles' => $this->elementStyles,           // For instant preview
            'hoverStyles' => $this->elementHoverStyles,
            'className' => $className,                  // CSS class for final output
        ]);

        // Use $this->js() for direct JavaScript execution - more reliable than dispatch
        $this->js("
            console.log('[UVE PHP->JS] syncToIframe called with data:', {$jsData});
            if (window.uveApplyStyles) {
                window.uveApplyStyles({$jsData});
            } else {
                console.warn('[UVE PHP->JS] window.uveApplyStyles not defined');
            }
        ");

        Log::info('[UVE_PropertyPanel] syncToIframe JS CALLED', [
            'elementId' => $this->selectedElementId,
            'styles' => $this->elementStyles,
            'className' => $className,
        ]);
    }

    // =====================
    // RESPONSIVE STYLES
    // =====================

    /**
     * Get responsive styles for current element
     * Delegated to UVE_ResponsiveStyles trait
     */
    protected function getResponsiveStylesForElement(): array
    {
        // Check if UVE_ResponsiveStyles trait is used
        if (method_exists($this, 'getStylesForDevice')) {
            return [
                'desktop' => $this->getStylesForDevice('desktop'),
                'tablet' => $this->getStylesForDevice('tablet'),
                'mobile' => $this->getStylesForDevice('mobile'),
            ];
        }

        return [];
    }

    // =====================
    // EVENT HANDLERS
    // =====================

    /**
     * Handle element selection - build panel for element
     *
     * PRIORITY ORDER for styles:
     * 1. canvasStyles - ACTUAL styles from Canvas iframe (HIGHEST - always shows current Canvas state)
     * 2. elementStylesCache - Session-level cache (for switching between elements)
     * 3. block['elementStyles'] - Database-saved styles
     * 4. HTML inline styles - Fallback from original HTML
     *
     * @param string $elementId Selected element ID
     * @param array $canvasStyles Styles from Canvas iframe (computed + inline)
     * @param string|null $blockType Block type from iframe (PP.0.4)
     */
    #[On('element-selected')]
    public function onElementSelectedForPanel(string $elementId, array $canvasStyles = [], ?string $blockType = null): void
    {
        $this->selectedElementId = $elementId;
        // PP.0.4: Store block type from iframe for block-specific controls
        $this->selectedBlockType = $blockType;

        // Reset to normal state when selecting new element
        $this->hoverState = 'normal';

        // PRIORITY 0 (HIGHEST): Use Canvas styles if provided
        // This ensures Panel ALWAYS shows what Canvas actually displays
        if (!empty($canvasStyles)) {
            // ETAP_07h FIX: Parse CSS url() values to clean URLs (backup for JS parsing)
            // Converts: url("https://...") -> https://...
            if (isset($canvasStyles['backgroundImage']) && str_starts_with($canvasStyles['backgroundImage'], 'url(')) {
                $canvasStyles['backgroundImage'] = preg_replace('/^url\([\'"]?|[\'"]?\)$/', '', $canvasStyles['backgroundImage']);
            }

            $this->elementStyles = $canvasStyles;
            // Update cache with Canvas state
            $this->elementStylesCache[$elementId] = $canvasStyles;
            Log::debug('[UVE_PropertyPanel] CANVAS SYNC - loaded styles from Canvas iframe', [
                'elementId' => $elementId,
                'canvasStylesCount' => count($canvasStyles),
                'hasTextDecoration' => isset($canvasStyles['textDecoration']),
                'backgroundImage' => $canvasStyles['backgroundImage'] ?? 'none',
            ]);
        }
        // PRIORITY 0.5: CSS-FIRST - Load styles from cssRules based on cssClassMap
        // ETAP_07h: After reopen, cssRules/cssClassMap are loaded from DB but elementStylesCache is empty
        elseif ($this->loadStylesFromCssRules($elementId)) {
            Log::debug('[UVE_PropertyPanel] CSS-FIRST - loaded styles from cssRules', [
                'elementId' => $elementId,
                'stylesCount' => count($this->elementStyles),
            ]);
        }
        // PRIORITY 1: Check cache for styles (persists across element switches)
        elseif (isset($this->elementStylesCache[$elementId])) {
            $this->elementStyles = $this->elementStylesCache[$elementId];
            Log::debug('[UVE_PropertyPanel] Loaded styles from cache', [
                'elementId' => $elementId,
                'cachedStyles' => $this->elementStyles,
            ]);
        } else {
            // PRIORITY 2: Check block['elementStyles'] (styles saved to database)
            $blockIndex = $this->editingBlockIndex ?? $this->selectedBlockIndex;
            $blockSavedStyles = [];
            if ($blockIndex !== null && isset($this->blocks[$blockIndex]['elementStyles'][$elementId])) {
                $blockSavedStyles = $this->blocks[$blockIndex]['elementStyles'][$elementId];
                Log::debug('[UVE_PropertyPanel] Loaded styles from block.elementStyles', [
                    'elementId' => $elementId,
                    'blockIndex' => $blockIndex,
                    'savedStyles' => $blockSavedStyles,
                ]);
            }

            // PRIORITY 3: Load from element data (HTML inline styles) as fallback
            $element = $this->getSelectedElementData();
            $elementHtmlStyles = $element['styles'] ?? [];

            // Merge: block saved styles override HTML inline styles
            $this->elementStyles = array_merge($elementHtmlStyles, $blockSavedStyles);
            $this->elementHoverStyles = $element['hoverStyles'] ?? [];
            $this->elementClasses = $element['classes'] ?? [];

            // Initialize cache with merged styles
            $this->elementStylesCache[$elementId] = $this->elementStyles;
        }

        // Always refresh classes from element (not cached)
        $element = $this->getSelectedElementData();
        if ($element) {
            $this->elementClasses = $element['classes'] ?? [];
        }

        Log::debug('[UVE_PropertyPanel] Element selected for panel', [
            'elementId' => $elementId,
            'stylesCount' => count($this->elementStyles),
            'classesCount' => count($this->elementClasses),
            'hasCachedStyles' => isset($this->elementStylesCache[$elementId]),
            'canvasStylesProvided' => !empty($canvasStyles),
        ]);

        // FIX #12: Dispatch uve-image-url-updated when selecting image element
        // This updates Alpine image-settings control which has wire:ignore.self
        // Without this, clicking another image doesn't update Property Panel preview
        $imageUrl = $this->elementStyles['imageUrl'] ?? $this->elementStyles['src'] ?? null;
        if ($imageUrl) {
            $this->dispatch('uve-image-url-updated', url: $imageUrl);
            Log::debug('[UVE_PropertyPanel] Dispatched uve-image-url-updated for image element', [
                'elementId' => $elementId,
                'imageUrl' => $imageUrl,
            ]);
        }

        // FIX #12 (background): Dispatch uve-background-updated when selecting element with background
        // This updates Alpine background control which has wire:ignore.self
        $backgroundImage = $this->elementStyles['backgroundImage'] ?? null;
        $backgroundColor = $this->elementStyles['backgroundColor'] ?? null;
        if ($backgroundImage || $backgroundColor) {
            $this->dispatch('uve-background-updated',
                backgroundImage: $backgroundImage,
                backgroundColor: $backgroundColor,
                backgroundSize: $this->elementStyles['backgroundSize'] ?? 'cover',
                backgroundPosition: $this->elementStyles['backgroundPosition'] ?? 'center center',
                backgroundRepeat: $this->elementStyles['backgroundRepeat'] ?? 'no-repeat',
                backgroundAttachment: $this->elementStyles['backgroundAttachment'] ?? 'scroll',
            );
            Log::debug('[UVE_PropertyPanel] Dispatched uve-background-updated for element', [
                'elementId' => $elementId,
                'backgroundImage' => $backgroundImage,
                'backgroundColor' => $backgroundColor,
            ]);
        }
    }

    /**
     * Merge all cached element styles to their respective blocks.
     * Called before save() to ensure all edits are persisted.
     */
    public function mergeElementStylesCacheToBlocks(): void
    {
        if (empty($this->elementStylesCache) || $this->editingBlockIndex === null) {
            Log::debug('[UVE_PropertyPanel] mergeElementStylesCacheToBlocks - nothing to merge', [
                'cacheCount' => count($this->elementStylesCache),
                'editingBlockIndex' => $this->editingBlockIndex,
            ]);
            return;
        }

        Log::info('[UVE_PropertyPanel] Merging elementStylesCache to blocks', [
            'blockIndex' => $this->editingBlockIndex,
            'cacheKeys' => array_keys($this->elementStylesCache),
        ]);

        // Ensure elementStyles array exists in block
        if (!isset($this->blocks[$this->editingBlockIndex]['elementStyles'])) {
            $this->blocks[$this->editingBlockIndex]['elementStyles'] = [];
        }

        // Merge all cached styles to block
        foreach ($this->elementStylesCache as $elementId => $styles) {
            if (!empty($styles)) {
                $this->blocks[$this->editingBlockIndex]['elementStyles'][$elementId] = $styles;
            }
        }

        Log::info('[UVE_PropertyPanel] Merged elementStylesCache', [
            'mergedCount' => count($this->elementStylesCache),
            'blockElementStyles' => array_keys($this->blocks[$this->editingBlockIndex]['elementStyles']),
        ]);
    }

    /**
     * Load styles from cssRules based on cssClassMap.
     *
     * ETAP_07h CSS-FIRST: When UVE is reopened, cssRules and cssClassMap are loaded
     * from database but elementStylesCache is empty. This method loads styles from
     * the CSS-first data structure so Property Panel shows correct values.
     *
     * @param string $elementId Element ID to load styles for
     * @return bool True if styles were loaded, false otherwise
     */
    protected function loadStylesFromCssRules(string $elementId): bool
    {
        // Check if CSS-first properties exist (from UVE_CssClassGeneration trait)
        if (!property_exists($this, 'cssClassMap') || !property_exists($this, 'cssRules')) {
            return false;
        }

        // Check if element has a CSS class mapping
        $className = $this->cssClassMap[$elementId] ?? null;
        if (!$className) {
            return false;
        }

        // Get CSS rules for this class (selector includes dot prefix)
        $selector = ".{$className}";
        $cssRules = $this->cssRules[$selector] ?? null;
        if (!$cssRules || !is_array($cssRules)) {
            return false;
        }

        // Convert kebab-case CSS properties to camelCase for Property Panel
        $camelCaseStyles = [];
        foreach ($cssRules as $property => $value) {
            $camelCaseProperty = $this->kebabToCamelCase($property);
            $camelCaseStyles[$camelCaseProperty] = $value;
        }

        // Set element styles and update cache
        $this->elementStyles = $camelCaseStyles;
        $this->elementStylesCache[$elementId] = $camelCaseStyles;

        return true;
    }

    /**
     * Convert kebab-case CSS property to camelCase.
     *
     * Examples:
     * - "font-size" -> "fontSize"
     * - "background-color" -> "backgroundColor"
     * - "margin-top" -> "marginTop"
     *
     * @param string $property Kebab-case CSS property
     * @return string CamelCase property name
     */
    protected function kebabToCamelCase(string $property): string
    {
        // Already camelCase (no hyphens)
        if (!str_contains($property, '-')) {
            return $property;
        }

        return lcfirst(str_replace('-', '', ucwords($property, '-')));
    }

    /**
     * ETAP_07h: Detect section type from CSS classes array.
     *
     * Maps pd-* CSS classes to section types for PropertyPanelService.
     * Specific types have priority over generic ones.
     *
     * @param array $cssClasses Array of CSS class names
     * @return string|null Detected section type or null
     */
    protected function detectSectionTypeFromClasses(array $cssClasses): ?string
    {
        if (empty($cssClasses)) {
            return null;
        }

        $classString = implode(' ', $cssClasses);

        // Prioritized list: specific types FIRST, generic types LAST
        // Order matters - first match wins
        // CRITICAL: Return values MUST match PropertyPanelService::getControlsForBlockType() keys!
        $prioritizedTypes = [
            // Content-specific (highest priority)
            'pd-intro' => 'pd-intro',
            'pd-cover' => 'pd-cover',
            'pd-pseudo-parallax' => 'pd-pseudo-parallax',
            'pd-slider' => 'pd-slider',
            'pd-specification' => 'pd-specification',
            'pd-merits' => 'pd-merits',
            'pd-features' => 'pd-features',
            'pd-more-links' => 'pd-more-links',
            'pd-asset-list' => 'pd-asset-list',
            'pd-where-2-ride' => 'pd-where-2-ride',
            'pd-footer' => 'pd-footer',
            'pd-header' => 'pd-header',
            'pd-gallery' => 'pd-gallery',
            // Layout containers
            'pd-base-grid' => 'pd-base-grid',
            'pd-block-row' => 'pd-block-row',
            // Generic containers (lowest priority)
            'pd-block' => 'pd-block',
        ];

        foreach ($prioritizedTypes as $type => $classPattern) {
            if (str_contains($classString, $classPattern)) {
                // Skip if it's a BEM child (has __)
                if (preg_match('/\b' . preg_quote($classPattern, '/') . '(?:__|$|\s)/', $classString) &&
                    preg_match('/\b' . preg_quote($classPattern, '/') . '__/', $classString)) {
                    continue;
                }
                return $type;
            }
        }

        return null;
    }
}
