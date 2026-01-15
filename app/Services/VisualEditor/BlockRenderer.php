<?php

namespace App\Services\VisualEditor;

use App\Models\ProductDescription;
use App\Models\ShopStyleset;
use App\Services\VisualEditor\Blocks\BaseBlock;
use Illuminate\Support\Facades\Log;

/**
 * Renders Visual Editor blocks to HTML.
 *
 * Handles:
 * - Single block rendering
 * - Multiple blocks rendering
 * - Nested/recursive block rendering
 * - Styleset application
 * - HTML minification
 */
class BlockRenderer
{
    public function __construct(
        private BlockRegistry $registry,
        private StylesetManager $stylesetManager
    ) {}

    /**
     * Render a single block to HTML.
     *
     * @param array $blockData Block data structure
     * @param ShopStyleset|null $styleset Optional styleset
     * @return string Rendered HTML
     */
    public function renderBlock(array $blockData, ?ShopStyleset $styleset = null): string
    {
        $type = $blockData['type'] ?? null;

        if (!$type) {
            Log::warning('Block render: missing type', ['data' => $blockData]);
            return '<!-- Block missing type -->';
        }

        $block = $this->registry->get($type);

        if (!$block) {
            Log::warning("Block render: unknown type '{$type}'");
            return "<!-- Unknown block type: {$type} -->";
        }

        $content = $blockData['content'] ?? [];
        $settings = $block->mergeSettings($blockData['settings'] ?? []);

        // Render children recursively if block supports them
        $children = [];
        if ($block->supportsChildren && !empty($blockData['children'])) {
            $children = $this->renderChildBlocks($blockData['children'], $styleset);
        }

        try {
            return $block->render($content, $settings, $children);
        } catch (\Throwable $e) {
            Log::error("Block render failed: {$type}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return "<!-- Block render error: {$type} -->";
        }
    }

    /**
     * Render child blocks and return as array.
     *
     * Handles both:
     * - Array block data (recursive rendering)
     * - String HTML (already rendered, passed through)
     *
     * @param array $children Array of block data or HTML strings
     * @param ShopStyleset|null $styleset
     * @return array Array of rendered HTML strings
     */
    private function renderChildBlocks(array $children, ?ShopStyleset $styleset = null): array
    {
        $rendered = [];

        foreach ($children as $index => $childData) {
            if (is_string($childData)) {
                // Already rendered HTML - pass through
                $rendered[$index] = $childData;
            } elseif (is_array($childData)) {
                // Block data array - render recursively
                $rendered[$index] = $this->renderBlock($childData, $styleset);
            } else {
                // Unknown type - skip with warning
                Log::warning('Block child: unexpected type', [
                    'index' => $index,
                    'type' => gettype($childData),
                ]);
                $rendered[$index] = '';
            }
        }

        return $rendered;
    }

    /**
     * Render multiple blocks to HTML.
     *
     * @param array $blocks Array of block data structures
     * @param ShopStyleset|null $styleset Optional styleset
     * @return string Combined rendered HTML
     */
    public function renderBlocks(array $blocks, ?ShopStyleset $styleset = null): string
    {
        $html = '';

        foreach ($blocks as $blockData) {
            $html .= $this->renderBlock($blockData, $styleset);
        }

        return $html;
    }

    /**
     * Generate complete HTML from ProductDescription.
     *
     * @param ProductDescription $description
     * @param bool $includeStyles Whether to include CSS in output
     * @return string Complete HTML
     */
    public function generateHtml(ProductDescription $description, bool $includeStyles = true): string
    {
        $blocks = $description->parseBlocks();
        $styleset = $this->stylesetManager->getForShop($description->shop_id);

        $html = $this->renderBlocks($blocks, $styleset);

        if ($includeStyles) {
            $html = $this->stylesetManager->applyToHtml($html, $styleset);
        }

        return $html;
    }

    /**
     * Generate clean HTML without wrapper styles (for PrestaShop sync).
     *
     * @param ProductDescription $description
     * @return string Clean HTML
     */
    public function generateCleanHtml(ProductDescription $description): string
    {
        $blocks = $description->parseBlocks();
        $styleset = $this->stylesetManager->getForShop($description->shop_id);

        $html = $this->renderBlocks($blocks, $styleset);

        // Add namespace wrapper without styles
        $namespace = $styleset?->css_namespace ?? 'pd';

        return "<div class=\"{$namespace}-wrapper\">{$html}</div>";
    }

    /**
     * Minify HTML output.
     *
     * @param string $html Raw HTML
     * @return string Minified HTML
     */
    public function minifyHtml(string $html): string
    {
        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\[).*?-->/s', '', $html);

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse multiple whitespace
        $html = preg_replace('/\s+/', ' ', $html);

        // Trim whitespace inside tags
        $html = preg_replace('/\s*(\/?>)\s*/', '$1', $html);

        return trim($html);
    }

    /**
     * Render blocks with full output options.
     *
     * @param array $blocks Block data array
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function render(array $blocks, array $options = []): string
    {
        $styleset = null;

        // Get styleset if shop_id provided
        if (!empty($options['shop_id'])) {
            $styleset = $this->stylesetManager->getForShop($options['shop_id']);
        } elseif (!empty($options['styleset_id'])) {
            $styleset = $this->stylesetManager->getById($options['styleset_id']);
        } elseif (!empty($options['styleset']) && $options['styleset'] instanceof ShopStyleset) {
            $styleset = $options['styleset'];
        }

        // Render blocks
        $html = $this->renderBlocks($blocks, $styleset);

        // Apply styles if requested
        if ($options['include_styles'] ?? false) {
            $html = $this->stylesetManager->applyToHtml($html, $styleset);
        }

        // Minify if requested
        if ($options['minify'] ?? false) {
            $html = $this->minifyHtml($html);
        }

        return $html;
    }

    /**
     * Get preview HTML for a single block (for editor).
     *
     * @param array $blockData Block data
     * @param int|null $shopId Shop ID for styleset
     * @return string Preview HTML
     */
    public function getBlockPreview(array $blockData, ?int $shopId = null): string
    {
        $styleset = $shopId ? $this->stylesetManager->getForShop($shopId) : null;

        return $this->renderBlock($blockData, $styleset);
    }

    /**
     * Validate and render blocks (returns errors if invalid).
     *
     * @param array $blocks Block data array
     * @return array ['html' => string, 'errors' => array]
     */
    public function validateAndRender(array $blocks): array
    {
        $errors = [];
        $validBlocks = [];

        foreach ($blocks as $index => $blockData) {
            $type = $blockData['type'] ?? null;

            if (!$type) {
                $errors[$index] = ['type' => 'Brak typu bloku'];
                continue;
            }

            $blockErrors = $this->registry->validateBlockData($type, $blockData);

            if (!empty($blockErrors)) {
                $errors[$index] = $blockErrors;
            } else {
                $validBlocks[] = $blockData;
            }
        }

        return [
            'html' => $this->renderBlocks($validBlocks),
            'errors' => $errors,
        ];
    }
}
