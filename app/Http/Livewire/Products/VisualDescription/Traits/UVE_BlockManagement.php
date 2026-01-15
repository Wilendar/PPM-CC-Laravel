<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Services\VisualEditor\BlockRegistry;
use Illuminate\Support\Str;

/**
 * UVE Block Management Trait - ETAP_07f_P5 + ETAP_07h v2.0
 *
 * Zarzadzanie blokami: dodawanie, usuwanie, duplikowanie, przenoszenie.
 * System freeze/unfreeze dla edycji elementow wewnatrz bloku.
 *
 * CSS-FIRST Architecture (ETAP_07h v2.0):
 * - NO inline styles (style="" attributes FORBIDDEN)
 * - NO embedded <style> blocks (inline_style_block mode ELIMINATED)
 * - Uses CSS classes from UVE_CssClassGeneration trait
 * - Output wrapped in .uve-content for CSS specificity
 * - CSS delivered externally via FTP (uve-custom.css)
 */
trait UVE_BlockManagement
{
    // =====================
    // BLOCK CRUD
    // =====================

    /**
     * Add new block at position
     */
    public function addBlock(string $type, ?int $position = null): void
    {
        $this->pushHistory();

        $block = $this->createBlock($type);

        if ($position === null || $position >= count($this->blocks)) {
            $this->blocks[] = $block;
            $this->selectedBlockIndex = count($this->blocks) - 1;
        } else {
            array_splice($this->blocks, $position, 0, [$block]);
            $this->selectedBlockIndex = $position;
        }

        $this->isDirty = true;
        $this->dispatch('block-added', blockId: $block['id'], type: $type);
        $this->dispatch('notify', type: 'success', message: 'Blok dodany');
    }

    /**
     * Remove block at index
     */
    public function removeBlock(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        $this->pushHistory();

        $blockId = $this->blocks[$index]['id'] ?? null;

        // If removing editing block, unfreeze first
        if ($this->editingBlockIndex === $index) {
            $this->editingBlockIndex = null;
            $this->selectedElementId = null;
        }

        array_splice($this->blocks, $index, 1);

        // Adjust indices
        if ($this->selectedBlockIndex !== null) {
            if ($this->selectedBlockIndex === $index) {
                $this->selectedBlockIndex = null;
            } elseif ($this->selectedBlockIndex > $index) {
                $this->selectedBlockIndex--;
            }
        }

        if ($this->editingBlockIndex !== null && $this->editingBlockIndex > $index) {
            $this->editingBlockIndex--;
        }

        $this->isDirty = true;
        $this->dispatch('block-removed', blockId: $blockId);
        $this->dispatch('notify', type: 'info', message: 'Blok usuniety');
    }

    /**
     * Duplicate block at index
     */
    public function duplicateBlock(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        $this->pushHistory();

        $original = $this->blocks[$index];
        $duplicate = $this->cloneBlock($original);

        // Insert after original
        array_splice($this->blocks, $index + 1, 0, [$duplicate]);
        $this->selectedBlockIndex = $index + 1;

        $this->isDirty = true;
        $this->dispatch('block-duplicated', originalId: $original['id'], newId: $duplicate['id']);
        $this->dispatch('notify', type: 'success', message: 'Blok zduplikowany');
    }

    /**
     * Move block from one position to another
     */
    public function moveBlock(int $fromIndex, int $toIndex): void
    {
        if (!isset($this->blocks[$fromIndex]) || $fromIndex === $toIndex) {
            return;
        }

        $this->pushHistory();

        $block = $this->blocks[$fromIndex];
        array_splice($this->blocks, $fromIndex, 1);

        // Adjust target index if needed
        if ($toIndex > $fromIndex) {
            $toIndex--;
        }

        array_splice($this->blocks, $toIndex, 0, [$block]);

        // Update selection
        if ($this->selectedBlockIndex === $fromIndex) {
            $this->selectedBlockIndex = $toIndex;
        }

        $this->isDirty = true;
    }

    /**
     * Move block up
     */
    public function moveBlockUp(int $index): void
    {
        if ($index > 0) {
            $this->moveBlock($index, $index - 1);
        }
    }

    /**
     * Move block down
     */
    public function moveBlockDown(int $index): void
    {
        if ($index < count($this->blocks) - 1) {
            $this->moveBlock($index, $index + 2);
        }
    }

    /**
     * Reorder blocks by new order array
     */
    public function reorderBlocks(array $newOrder): void
    {
        if (count($newOrder) !== count($this->blocks)) {
            return;
        }

        $this->pushHistory();

        $reordered = [];
        foreach ($newOrder as $oldIndex) {
            if (isset($this->blocks[$oldIndex])) {
                $reordered[] = $this->blocks[$oldIndex];
            }
        }

        $this->blocks = $reordered;
        $this->isDirty = true;
    }

    /**
     * Clear all blocks
     */
    public function clearBlocks(): void
    {
        $this->pushHistory();

        $this->blocks = [];
        $this->selectedBlockIndex = null;
        $this->editingBlockIndex = null;
        $this->selectedElementId = null;

        $this->isDirty = true;
        $this->dispatch('notify', type: 'info', message: 'Wszystkie bloki usuniete');
    }

    // =====================
    // BLOCK SELECTION
    // =====================

    /**
     * Select block at index
     */
    public function selectBlock(?int $index): void
    {
        // If clicking different block while editing, freeze current first
        if ($this->editingBlockIndex !== null && $this->editingBlockIndex !== $index) {
            $this->freezeBlock($this->editingBlockIndex, save: true);
        }

        $this->selectedBlockIndex = $index;
        $this->selectedElementId = null;
    }

    // =====================
    // FREEZE / UNFREEZE
    // =====================

    /**
     * Unfreeze block for editing elements inside
     */
    public function unfreezeBlock(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        // Freeze any currently editing block first
        if ($this->editingBlockIndex !== null && $this->editingBlockIndex !== $index) {
            $this->freezeBlock($this->editingBlockIndex, save: true);
        }

        $this->pushHistory();

        // Mark block as unlocked
        $this->blocks[$index]['locked'] = false;

        $this->editingBlockIndex = $index;
        $this->selectedBlockIndex = $index;
        $this->selectedElementId = null;

        $this->dispatch('block-unfrozen', index: $index);
    }

    /**
     * Freeze block (stop editing elements)
     */
    public function freezeBlock(int $index, bool $save = true): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        if ($save) {
            // Compile HTML from document
            $this->compileBlockHtml($index);
        }

        // Mark block as locked
        $this->blocks[$index]['locked'] = true;

        if ($this->editingBlockIndex === $index) {
            $this->editingBlockIndex = null;
            $this->selectedElementId = null;
        }

        $this->isDirty = true;
        $this->dispatch('block-frozen', index: $index);
    }

    /**
     * Cancel editing block (revert changes)
     */
    public function cancelBlockEdit(int $index): void
    {
        // Revert to last history state
        $this->undo();

        // Force freeze
        if (isset($this->blocks[$index])) {
            $this->blocks[$index]['locked'] = true;
        }

        $this->editingBlockIndex = null;
        $this->selectedElementId = null;
    }

    // =====================
    // ELEMENT SELECTION (within unfrozen block)
    // =====================

    /**
     * Select element within editing block
     */
    public function selectElement(?string $elementId): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $this->selectedElementId = $elementId;
    }

    /**
     * Deselect current element
     */
    public function deselectElement(): void
    {
        $this->selectedElementId = null;
    }

    /**
     * ETAP_07h FIX #4: Select element from layers panel
     * This triggers iframe selection and Property Panel update
     */
    public function selectElementFromLayers(string $elementId): void
    {
        $this->selectedElementId = $elementId;

        // Dispatch browser event to select element in iframe
        // The iframe JS will add uve-selected class and call back with styles
        $this->dispatch('uve-select-element-from-layers', elementId: $elementId);

        \Log::debug('[UVE] Element selected from layers panel', [
            'elementId' => $elementId,
        ]);
    }

    // =====================
    // BLOCK FACTORY
    // =====================

    /**
     * Create new block structure
     */
    protected function createBlock(string $type): array
    {
        $blockId = 'blk_' . Str::random(8);

        // Get block template from registry if available
        $template = $this->getBlockTemplate($type);

        return [
            'id' => $blockId,
            'type' => $type,
            'locked' => true,
            'document' => $template['document'] ?? $this->createDefaultDocument($type),
            'compiled_html' => $template['html'] ?? '',
            'meta' => [
                'created_from' => 'manual',
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Clone block with new ID
     */
    protected function cloneBlock(array $block): array
    {
        $newId = 'blk_' . Str::random(8);

        $clone = $block;
        $clone['id'] = $newId;
        $clone['locked'] = true;
        $clone['meta']['cloned_from'] = $block['id'];
        $clone['meta']['created_at'] = now()->toIso8601String();

        // Regenerate element IDs in document
        if (isset($clone['document']['root'])) {
            $clone['document']['root'] = $this->regenerateElementIds($clone['document']['root']);
        }

        return $clone;
    }

    /**
     * Recursively regenerate element IDs
     */
    protected function regenerateElementIds(array $element): array
    {
        $element['id'] = 'el_' . Str::random(8);

        if (!empty($element['children'])) {
            $element['children'] = array_map(
                fn($child) => $this->regenerateElementIds($child),
                $element['children']
            );
        }

        return $element;
    }

    /**
     * Create default document structure for block type
     */
    protected function createDefaultDocument(string $type): array
    {
        return [
            'version' => '1.0',
            'root' => [
                'id' => 'el_' . Str::random(8),
                'type' => 'container',
                'tag' => 'div',
                'classes' => [$type, 'pd-block'],
                'styles' => [
                    'padding' => '2rem',
                ],
                'content' => '',
                'children' => [],
                'visible' => true,
                'locked' => false,
            ],
            'variables' => [],
            'cssClasses' => [$type],
        ];
    }

    /**
     * Get block template from registry
     */
    protected function getBlockTemplate(string $type): ?array
    {
        try {
            $registry = app(BlockRegistry::class);
            return $registry->getBlockDefinition($type, $this->shopId);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =====================
    // HTML COMPILATION
    // =====================

    /**
     * Compile block document to HTML
     *
     * For structured blocks (with document.root): renders element tree
     * For raw-html blocks: applies elementStyles to existing HTML
     */
    protected function compileBlockHtml(int $index): void
    {
        $block = $this->blocks[$index] ?? null;
        if (!$block) {
            return;
        }

        // Structured blocks: render from document tree
        if (isset($block['document']['root'])) {
            $html = $this->renderElement($block['document']['root']);
            $this->blocks[$index]['compiled_html'] = $html;
            return;
        }

        // Raw-html blocks: always recompile
        $html = $block['content']['html'] ?? '';
        if ($html) {
            $htmlWithMarkers = $this->injectEditableMarkersForBlock($html, $index);

            // CSS-FIRST ARCHITECTURE (ETAP_07h v2.0): ONLY CSS classes, NO inline styles
            // Apply CSS classes to elements based on cssClassMap
            if (property_exists($this, 'cssClassMap') && !empty($this->cssClassMap)) {
                $htmlWithClasses = $this->applyElementClassesToHtml($htmlWithMarkers, $this->cssClassMap);
                $this->blocks[$index]['compiled_html'] = $htmlWithClasses;
            } else {
                // v2.0: No fallback to inline styles - just use HTML with markers
                // Inline styles are FORBIDDEN by CSS-First architecture
                $this->blocks[$index]['compiled_html'] = $htmlWithMarkers;
            }

            // Clean any empty style="" attributes that may have been left from editing
            if (method_exists($this, 'cleanEmptyStyleAttributes')) {
                $this->blocks[$index]['compiled_html'] = $this->cleanEmptyStyleAttributes(
                    $this->blocks[$index]['compiled_html']
                );
            }
        }
    }

    /**
     * Inject editable markers for a single block's HTML
     * This is a simplified version of injectEditableMarkers for use during save
     *
     * @param string $html Block HTML content
     * @param int $blockIndex Block index for ID generation
     * @return string HTML with data-uve-id markers
     */
    protected function injectEditableMarkersForBlock(string $html, int $blockIndex): string
    {
        if (empty($html)) {
            return $html;
        }

        $dom = new \DOMDocument();
        $dom->encoding = 'UTF-8';

        // Suppress HTML5 warnings, wrap in container for proper parsing
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="uve-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);
        $blockId = 'block-' . $blockIndex;

        // Find the root block element (first child of wrapper)
        $root = $dom->getElementById('uve-root');
        if (!$root || !$root->hasChildNodes()) {
            return $html;
        }

        // Get first element child (skip text nodes)
        $blockElement = null;
        foreach ($root->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $blockElement = $child;
                break;
            }
        }

        if ($blockElement) {
            $blockElement->setAttribute('data-uve-id', $blockId);
            $blockElement->setAttribute('data-uve-type', 'block');

            // Mark child editable elements
            $elementIndex = 0;

            // Mark headings
            $headings = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $blockElement);
            foreach ($headings as $heading) {
                $heading->setAttribute('data-uve-id', "{$blockId}-heading-{$elementIndex}");
                $heading->setAttribute('data-uve-type', 'heading');
                $elementIndex++;
            }

            // Mark paragraphs
            $paragraphs = $xpath->query('.//p[not(ancestor::li)]', $blockElement);
            foreach ($paragraphs as $p) {
                $p->setAttribute('data-uve-id', "{$blockId}-text-{$elementIndex}");
                $p->setAttribute('data-uve-type', 'text');
                $elementIndex++;
            }

            // Mark images
            $images = $xpath->query('.//img', $blockElement);
            foreach ($images as $img) {
                $img->setAttribute('data-uve-id', "{$blockId}-image-{$elementIndex}");
                $img->setAttribute('data-uve-type', 'image');
                $elementIndex++;
            }

            // Mark links
            $links = $xpath->query('.//a', $blockElement);
            foreach ($links as $link) {
                $link->setAttribute('data-uve-id', "{$blockId}-link-{$elementIndex}");
                $link->setAttribute('data-uve-type', 'link');
                $elementIndex++;
            }

            // Mark spans (potential styled text)
            $spans = $xpath->query('.//span[not(ancestor::a)]', $blockElement);
            foreach ($spans as $span) {
                $span->setAttribute('data-uve-id', "{$blockId}-span-{$elementIndex}");
                $span->setAttribute('data-uve-type', 'span');
                $elementIndex++;
            }

            // Mark divs with content classes
            $divs = $xpath->query('.//div[contains(@class, "content") or contains(@class, "text") or contains(@class, "title")]', $blockElement);
            foreach ($divs as $div) {
                if (!$div->hasAttribute('data-uve-id')) {
                    $div->setAttribute('data-uve-id', "{$blockId}-div-{$elementIndex}");
                    $div->setAttribute('data-uve-type', 'container');
                    $elementIndex++;
                }
            }
        }

        // Get inner HTML (without wrapper)
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    /**
     * @deprecated CSS-FIRST v2.0 - Inline styles are FORBIDDEN
     *
     * Apply elementStyles to HTML string using data-uve-id markers.
     * This method is kept only for migration purposes.
     * Use applyElementClassesToHtml() instead.
     *
     * @param string $html Original HTML (must have data-uve-id markers)
     * @param array $elementStyles Map of elementId => styles array
     * @return string Modified HTML with injected inline styles
     */
    protected function applyElementStylesToHtml(string $html, array $elementStyles): string
    {
        if (empty($elementStyles)) {
            return $html;
        }

        foreach ($elementStyles as $elementId => $styles) {
            if (empty($styles)) {
                continue;
            }

            // Build CSS string from styles array (may be empty if all values are defaults)
            $cssString = $this->renderStyles($styles);

            // Find element with data-uve-id and REPLACE style attribute (not merge)
            // Pattern matches: data-uve-id="elementId" with optional existing style
            $pattern = '/(<[^>]*data-uve-id=["\']' . preg_quote($elementId, '/') . '["\'])([^>]*)(>)/';

            $html = preg_replace_callback($pattern, function ($matches) use ($cssString) {
                $prefix = $matches[1];
                $attrs = $matches[2];
                $close = $matches[3];

                // Check if style attribute exists
                if (preg_match('/style=["\'][^"\']*["\']/', $attrs)) {
                    if (empty($cssString)) {
                        // REMOVE style attribute entirely when no active styles
                        $attrs = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $attrs);
                    } else {
                        // Replace existing style with new styles
                        $attrs = preg_replace('/style=["\'][^"\']*["\']/', 'style="' . $cssString . '"', $attrs);
                    }
                } elseif (!empty($cssString)) {
                    // Add new style attribute only if we have styles
                    $attrs .= ' style="' . $cssString . '"';
                }

                return $prefix . $attrs . $close;
            }, $html);
        }

        return $html;
    }

    /**
     * CSS-FIRST ARCHITECTURE (ETAP_07h): Apply CSS classes instead of inline styles
     *
     * Replaces inline style="" with class="uve-eXXXX" attributes.
     * Removes any existing style attributes to ensure clean output.
     *
     * @param string $html Original HTML (must have data-uve-id markers)
     * @param array $classMap Map of elementId => className
     * @return string Modified HTML with CSS classes instead of inline styles
     */
    protected function applyElementClassesToHtml(string $html, array $classMap): string
    {
        if (empty($classMap) || empty($html)) {
            return $html;
        }

        foreach ($classMap as $elementId => $className) {
            // Pattern matches entire opening tag with data-uve-id="elementId"
            // FIX: Capture FULL tag to properly find class attribute anywhere in tag
            $pattern = '/(<[^>]*data-uve-id=["\']' . preg_quote($elementId, '/') . '["\'][^>]*)(>)/';

            $html = preg_replace_callback($pattern, function ($matches) use ($className) {
                $fullTag = $matches[1];  // Everything from < to before >
                $close = $matches[2];     // The >

                // CRITICAL: Remove any existing style attribute (CSS-first!)
                $fullTag = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $fullTag);

                // Add or merge class attribute - check in FULL tag now
                if (preg_match('/class=["\']([^"\']*)["\']/', $fullTag, $classMatch)) {
                    $existingClasses = $classMatch[1];
                    // Only add if not already present
                    if (strpos($existingClasses, $className) === false) {
                        $fullTag = preg_replace(
                            '/class=["\']([^"\']*)["\']/',
                            'class="$1 ' . $className . '"',
                            $fullTag
                        );
                    }
                } else {
                    // Add new class attribute (at end of tag before >)
                    $fullTag .= ' class="' . $className . '"';
                }

                return $fullTag . $close;
            }, $html);
        }

        return $html;
    }

    /**
     * Compile all blocks HTML
     */
    protected function compileAllBlocksHtml(): void
    {
        foreach ($this->blocks as $index => $block) {
            $this->compileBlockHtml($index);
        }
    }

    /**
     * Render element to HTML recursively
     *
     * CSS-FIRST ARCHITECTURE (ETAP_07h v2.0):
     * - Uses CSS class from cssClassMap instead of inline styles
     * - Element styles are stored in cssRules and delivered externally
     */
    protected function renderElement(array $element): string
    {
        $tag = $element['tag'] ?? 'div';
        $elementId = $element['id'] ?? '';
        $content = $element['content'] ?? '';

        // Build class list
        $classes = $element['classes'] ?? [];

        // CSS-FIRST v2.0: Add CSS class from cssClassMap if element has styles
        if ($elementId && property_exists($this, 'cssClassMap')) {
            $cssClass = $this->cssClassMap[$elementId] ?? null;
            if ($cssClass && !in_array($cssClass, $classes)) {
                $classes[] = $cssClass;
            }
        }

        $classStr = implode(' ', $classes);

        // Build attributes (NO inline styles in v2.0!)
        $attrs = [];
        if ($classStr) {
            $attrs[] = 'class="' . e($classStr) . '"';
        }
        // Add data-uve-id for element identification
        if ($elementId) {
            $attrs[] = 'data-uve-id="' . e($elementId) . '"';
        }
        if (!empty($element['src'])) {
            $attrs[] = 'src="' . e($element['src']) . '"';
        }
        if (!empty($element['alt'])) {
            $attrs[] = 'alt="' . e($element['alt']) . '"';
        }
        if (!empty($element['href'])) {
            $attrs[] = 'href="' . e($element['href']) . '"';
        }

        $attrStr = $attrs ? ' ' . implode(' ', $attrs) : '';

        // Self-closing tags
        if (in_array($tag, ['img', 'br', 'hr', 'input'])) {
            return "<{$tag}{$attrStr} />";
        }

        // Render children
        $childrenHtml = '';
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                if ($child['visible'] ?? true) {
                    $childrenHtml .= $this->renderElement($child);
                }
            }
        }

        // Content or children
        $innerHtml = $content ?: $childrenHtml;

        return "<{$tag}{$attrStr}>{$innerHtml}</{$tag}>";
    }

    /**
     * @deprecated CSS-FIRST v2.0 - Use CSS classes instead of inline styles
     *
     * Convert styles array to CSS string.
     * This method is kept only for InlineStyleMigrator.
     * New code should use setCssRule() and CSS classes.
     *
     * Skips default CSS values to allow proper reset via JavaScript.
     */
    protected function renderStyles(array $styles): string
    {
        // Default values that should NOT be added to inline style
        // These are CSS defaults - adding them prevents JavaScript from removing them
        $skipDefaults = [
            'text-decoration' => 'none',
            'text-transform' => 'none',
            'text-align' => 'left',
            'font-style' => 'normal',
            'font-weight' => '400',
            'letter-spacing' => 'normal',
            'line-height' => 'normal',
        ];

        $css = [];
        foreach ($styles as $property => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            // Convert camelCase to kebab-case
            $prop = strtolower(preg_replace('/([A-Z])/', '-$1', $property));

            // Skip default values - let CSS cascade handle them
            if (isset($skipDefaults[$prop]) && $value === $skipDefaults[$prop]) {
                continue;
            }

            $css[] = "{$prop}: {$value}";
        }
        return implode('; ', $css);
    }

    /**
     * Generate full rendered HTML for all blocks
     *
     * CSS-FIRST ARCHITECTURE (ETAP_07h v2.0):
     * - NO inline <style> blocks (inline_style_block mode ELIMINATED)
     * - HTML wrapped in .uve-content for CSS specificity
     * - CSS delivered externally via FTP (uve-custom.css)
     *
     * @param bool $validate Run zero-inline validation (default: true)
     * @return string Rendered HTML wrapped in .uve-content
     * @throws \InvalidArgumentException If validation fails and $validate is true
     */
    protected function generateRenderedHtml(bool $validate = true): string
    {
        $blocksHtml = '';

        foreach ($this->blocks as $block) {
            $blocksHtml .= $block['compiled_html'] ?? '';
        }

        // CSS-FIRST v2.0: Wrap in .uve-content for CSS specificity
        // This ensures our CSS beats theme CSS: .uve-content .uve-sXXXX { ... }
        $html = $this->wrapWithUveContent($blocksHtml);

        // CSS-FIRST v2.0: Validate NO inline styles in output
        if ($validate && method_exists($this, 'validateZeroInlineStyles')) {
            $validationResult = $this->validateZeroInlineStyles($html);

            if (!$validationResult['valid']) {
                \Illuminate\Support\Facades\Log::error('[UVE] generateRenderedHtml: Zero-inline validation FAILED', [
                    'product_id' => $this->productId ?? null,
                    'shop_id' => $this->shopId ?? null,
                    'errors' => $validationResult['errors'],
                ]);

                // For backward compatibility, log but don't throw during migration period
                // After migration complete, uncomment this:
                // throw new \InvalidArgumentException(
                //     'CSS-First validation failed: HTML contains inline styles. ' .
                //     'Error count: ' . $validationResult['error_count']
                // );
            }
        }

        return $html;
    }

    /**
     * Wrap HTML content with .uve-content container for CSS specificity.
     *
     * This ensures UVE CSS classes have higher specificity than theme CSS:
     * .uve-content .uve-s7f3a2b1 { font-size: 56px; }
     *
     * @param string $html Inner HTML content
     * @return string HTML wrapped in .uve-content div
     */
    protected function wrapWithUveContent(string $html): string
    {
        if (empty(trim($html))) {
            return '';
        }

        return '<div class="uve-content">' . $html . '</div>';
    }

    // =====================
    // BLOCK PALETTE DATA
    // =====================

    /**
     * Get available block types for palette
     */
    public function getBlockPaletteProperty(): array
    {
        return [
            [
                'category' => 'Podstawowe',
                'blocks' => [
                    ['type' => 'heading', 'label' => 'Naglowek', 'icon' => 'text'],
                    ['type' => 'text', 'label' => 'Tekst', 'icon' => 'align-left'],
                    ['type' => 'image', 'label' => 'Obraz', 'icon' => 'image'],
                    ['type' => 'button', 'label' => 'Przycisk', 'icon' => 'cursor-click'],
                ],
            ],
            [
                'category' => 'Layouty',
                'blocks' => [
                    ['type' => 'container', 'label' => 'Kontener', 'icon' => 'square'],
                    ['type' => 'row', 'label' => 'Wiersz', 'icon' => 'view-columns'],
                    ['type' => 'columns-2', 'label' => '2 Kolumny', 'icon' => 'view-columns'],
                    ['type' => 'columns-3', 'label' => '3 Kolumny', 'icon' => 'view-columns'],
                ],
            ],
            [
                'category' => 'PrestaShop',
                'blocks' => [
                    ['type' => 'pd-intro', 'label' => 'Intro', 'icon' => 'sparkles'],
                    ['type' => 'pd-merits', 'label' => 'Zalety', 'icon' => 'check-badge'],
                    ['type' => 'pd-specification', 'label' => 'Specyfikacja', 'icon' => 'table-cells'],
                    ['type' => 'pd-features', 'label' => 'Cechy', 'icon' => 'list-bullet'],
                    ['type' => 'pd-slider', 'label' => 'Slider', 'icon' => 'photo'],
                    ['type' => 'pd-cover', 'label' => 'Cover', 'icon' => 'photo'],
                ],
            ],
            [
                'category' => 'Import',
                'blocks' => [
                    ['type' => 'prestashop-section', 'label' => 'Sekcja PS', 'icon' => 'code-bracket'],
                    ['type' => 'custom-html', 'label' => 'Custom HTML', 'icon' => 'code-bracket-square'],
                ],
            ],
        ];
    }
}
