<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\ProductDescription;
use App\Models\ShopStyleset;
use Illuminate\Support\Facades\Log;

/**
 * Renders Visual Editor descriptions to HTML.
 *
 * Provides multiple rendering modes:
 * - Full HTML with wrapper and CSS (for preview)
 * - Clean HTML without wrapper (for PrestaShop sync)
 * - Inline styles (for email compatibility)
 * - Minified output (for production)
 *
 * @package App\Services\VisualEditor
 * @since ETAP_07f Faza 8 - Rendering i Export
 */
class DescriptionRenderer
{
    public function __construct(
        private BlockRegistry $blockRegistry,
        private StylesetManager $stylesetManager
    ) {}

    /**
     * Render blocks to full HTML with wrapper and CSS.
     *
     * Includes:
     * - <style> tag with CSS variables and custom CSS
     * - Wrapper div with namespace class
     * - Rendered block content
     *
     * @param array $blocks Block data array
     * @param int $shopId Shop ID for styleset lookup
     * @return string Complete HTML with embedded styles
     */
    public function render(array $blocks, int $shopId): string
    {
        $styleset = $this->stylesetManager->getForShop($shopId);
        $css = $styleset ? $styleset->compileCss() : $this->getDefaultCss();
        $namespace = $styleset?->css_namespace ?? 'pd';

        $html = $this->renderBlocks($blocks, $styleset);

        return $this->wrapWithContainer($html, $css, $namespace);
    }

    /**
     * Render blocks to clean HTML (content only, no wrapper styles).
     *
     * Suitable for PrestaShop sync where CSS is deployed separately.
     * Includes only the wrapper div without embedded <style> tag.
     *
     * @param array $blocks Block data array
     * @param int|null $shopId Optional shop ID for styleset namespace
     * @return string Clean HTML with namespace wrapper
     */
    public function renderClean(array $blocks, ?int $shopId = null): string
    {
        $styleset = $shopId ? $this->stylesetManager->getForShop($shopId) : null;
        $namespace = $styleset?->css_namespace ?? 'pd';

        $html = $this->renderBlocks($blocks, $styleset);

        return "<div class=\"{$namespace}-wrapper\">{$html}</div>";
    }

    /**
     * Render blocks with inline styles (for email compatibility).
     *
     * Converts CSS classes to inline style attributes.
     * Suitable for email clients that don't support <style> tags.
     *
     * @param array $blocks Block data array
     * @param int $shopId Shop ID for styleset lookup
     * @return string HTML with inline styles
     */
    public function renderInline(array $blocks, int $shopId): string
    {
        $styleset = $this->stylesetManager->getForShop($shopId);
        $html = $this->renderBlocks($blocks, $styleset);

        // Convert CSS variables to concrete values
        $html = $this->inlineVariables($html, $styleset);

        // Add inline styles for common elements
        $html = $this->addInlineStyles($html, $styleset);

        return $html;
    }

    /**
     * Minify HTML output.
     *
     * Removes unnecessary whitespace, comments, and formatting.
     * Reduces output size for production use.
     *
     * @param string $html Raw HTML
     * @return string Minified HTML
     */
    public function minify(string $html): string
    {
        // Remove HTML comments (except conditional comments)
        $html = preg_replace('/<!--(?!\[).*?-->/s', '', $html);

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse multiple whitespace to single space
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove whitespace around tag brackets
        $html = preg_replace('/\s*(\/?>)\s*/', '$1', $html);

        return trim($html);
    }

    /**
     * Render ProductDescription model to HTML.
     *
     * @param ProductDescription $description Description model
     * @param bool $includeStyles Include CSS in output
     * @return string Rendered HTML
     */
    public function renderDescription(ProductDescription $description, bool $includeStyles = true): string
    {
        $blocks = $description->parseBlocks();

        if ($includeStyles) {
            return $this->render($blocks, $description->shop_id);
        }

        return $this->renderClean($blocks, $description->shop_id);
    }

    /**
     * Render and cache HTML for ProductDescription.
     *
     * Updates model with rendered HTML and timestamp.
     *
     * @param ProductDescription $description Description to render
     * @param bool $minify Whether to minify output
     * @return string Rendered HTML
     */
    public function renderAndCache(ProductDescription $description, bool $minify = true): string
    {
        $blocks = $description->parseBlocks();
        $html = $this->renderClean($blocks, $description->shop_id);

        if ($minify) {
            $html = $this->minify($html);
        }

        $description->setRenderedHtml($html);

        Log::debug('DescriptionRenderer: HTML rendered and cached', [
            'description_id' => $description->id,
            'product_id' => $description->product_id,
            'shop_id' => $description->shop_id,
            'block_count' => count($blocks),
            'html_length' => strlen($html),
        ]);

        return $html;
    }

    /**
     * Render multiple blocks to HTML string.
     *
     * @param array $blocks Array of block data
     * @param ShopStyleset|null $styleset Optional styleset for rendering
     * @return string Combined HTML
     */
    private function renderBlocks(array $blocks, ?ShopStyleset $styleset = null): string
    {
        $html = '';

        foreach ($blocks as $blockData) {
            $html .= $this->renderSingleBlock($blockData, $styleset);
        }

        return $html;
    }

    /**
     * Render a single block to HTML.
     *
     * @param array $blockData Block data structure
     * @param ShopStyleset|null $styleset Optional styleset
     * @return string Rendered HTML
     */
    private function renderSingleBlock(array $blockData, ?ShopStyleset $styleset = null): string
    {
        $type = $blockData['type'] ?? null;

        if (!$type) {
            Log::warning('DescriptionRenderer: blok bez typu', ['data' => $blockData]);
            return '<!-- Blok bez typu -->';
        }

        $block = $this->blockRegistry->get($type);

        if (!$block) {
            Log::warning("DescriptionRenderer: nieznany typ bloku '{$type}'");
            return "<!-- Nieznany typ bloku: {$type} -->";
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
            Log::error("DescriptionRenderer: blad renderowania bloku {$type}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return "<!-- Blad renderowania: {$type} -->";
        }
    }

    /**
     * Render child blocks recursively.
     *
     * @param array $children Child blocks array
     * @param ShopStyleset|null $styleset
     * @return array Rendered children HTML strings
     */
    private function renderChildBlocks(array $children, ?ShopStyleset $styleset = null): array
    {
        $rendered = [];

        foreach ($children as $index => $childData) {
            if (is_string($childData)) {
                // Already rendered - pass through
                $rendered[$index] = $childData;
            } elseif (is_array($childData)) {
                // Block data - render recursively
                $rendered[$index] = $this->renderSingleBlock($childData, $styleset);
            } else {
                $rendered[$index] = '';
            }
        }

        return $rendered;
    }

    /**
     * Wrap HTML content with container and embedded styles.
     *
     * @param string $html Block content HTML
     * @param string $css Compiled CSS
     * @param string $namespace CSS namespace
     * @return string Complete HTML with styles
     */
    private function wrapWithContainer(string $html, string $css, string $namespace): string
    {
        return <<<HTML
<style>
{$css}
</style>
<div class="{$namespace}-wrapper">
{$html}
</div>
HTML;
    }

    /**
     * Get default CSS when no styleset is configured.
     *
     * @return string Default CSS content
     */
    private function getDefaultCss(): string
    {
        return <<<CSS
:root {
  --pd-primary-color: #2563eb;
  --pd-secondary-color: #64748b;
  --pd-text-color: #1e293b;
  --pd-background-color: #ffffff;
  --pd-font-family: system-ui, -apple-system, sans-serif;
  --pd-spacing-unit: 1rem;
  --pd-border-radius: 0.5rem;
}
.pd-wrapper {
  font-family: var(--pd-font-family);
  color: var(--pd-text-color);
  background: var(--pd-background-color);
}
CSS;
    }

    /**
     * Replace CSS variables with concrete values for inline styles.
     *
     * @param string $html HTML content
     * @param ShopStyleset|null $styleset
     * @return string HTML with replaced variables
     */
    private function inlineVariables(string $html, ?ShopStyleset $styleset): string
    {
        $variables = $styleset ? $styleset->variables : [
            'primary-color' => '#2563eb',
            'secondary-color' => '#64748b',
            'text-color' => '#1e293b',
            'background-color' => '#ffffff',
            'font-family' => 'system-ui, -apple-system, sans-serif',
        ];

        $namespace = $styleset?->namespace_prefix ?? 'pd-';

        // Replace var(--namespace-name) with actual values
        foreach ($variables as $name => $value) {
            $varPattern = "/var\(--{$namespace}{$name}\)/";
            $html = preg_replace($varPattern, $value, $html);
        }

        return $html;
    }

    /**
     * Add inline styles for common HTML elements.
     *
     * @param string $html HTML content
     * @param ShopStyleset|null $styleset
     * @return string HTML with inline styles
     */
    private function addInlineStyles(string $html, ?ShopStyleset $styleset): string
    {
        $fontFamily = $styleset?->getVariable('font-family') ?? 'system-ui, -apple-system, sans-serif';
        $textColor = $styleset?->getVariable('text-color') ?? '#1e293b';
        $primaryColor = $styleset?->getVariable('primary-color') ?? '#2563eb';

        // Add basic inline styles to common elements
        $replacements = [
            '/<h1/' => "<h1 style=\"font-family: {$fontFamily}; color: {$textColor};\"",
            '/<h2/' => "<h2 style=\"font-family: {$fontFamily}; color: {$textColor};\"",
            '/<h3/' => "<h3 style=\"font-family: {$fontFamily}; color: {$textColor};\"",
            '/<p/' => "<p style=\"font-family: {$fontFamily}; color: {$textColor};\"",
            '/<a /' => "<a style=\"color: {$primaryColor};\" ",
        ];

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        return $html;
    }
}
