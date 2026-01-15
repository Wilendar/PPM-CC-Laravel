<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

/**
 * UVE CSS Class Generation Trait
 *
 * Generates CSS classes instead of inline styles for UVE elements.
 * Part of CSS-first architecture (ETAP_07h v2.0).
 *
 * Key concept: Per-Style Hash - identical styles share the same CSS class.
 * Example: Two headings with same font-size:56px -> same class="uve-s7f3a2b1"
 *
 * Benefits:
 * - CSS deduplication (same styles = same class)
 * - Smaller CSS output
 * - Better caching
 *
 * Naming convention:
 * - uve-s{hash} = per-style hash (v2.0)
 * - uve-e{hash} = per-element hash (deprecated v1.0)
 *
 * Properties used (defined in parent component):
 * - $productId: int|null
 * - $shopId: int|null
 * - $description: ProductDescription|null
 */
trait UVE_CssClassGeneration
{
    // =====================
    // CSS STATE
    // =====================

    /** @var array CSS rules by selector: {".uve-s7f3a2b1": {"font-size": "56px", ...}} (s = style-based hash) */
    public array $cssRules = [];

    /** @var array Element ID to class name mapping: {"block-0-heading-0": "uve-s7f3a2b1"} (many elements can share same class) */
    public array $cssClassMap = [];

    /** @var bool Whether CSS rules have been modified since last save */
    public bool $cssDirty = false;

    // =====================
    // CSS CLASS GENERATION (Per-Style Hash v2.0)
    // =====================

    /**
     * Generate deterministic CSS class name based on canonicalized styles.
     *
     * Per-style hash: identical styles produce identical class names.
     * This allows CSS deduplication - multiple elements can share the same class.
     *
     * Result: .uve-s{8-char-hash} (s = style-based)
     *
     * @param array $canonicalStyles Canonicalized styles from canonicalizeStyles()
     * @return string CSS class name without dot prefix
     */
    protected function generateStyleHash(array $canonicalStyles): string
    {
        if (empty($canonicalStyles)) {
            return '';
        }

        // Hash is based on JSON representation of sorted, normalized styles
        $hash = substr(md5(json_encode($canonicalStyles, JSON_UNESCAPED_SLASHES)), 0, 8);
        return "uve-s{$hash}";
    }

    /**
     * Canonicalize styles for consistent hashing.
     *
     * - Converts camelCase to kebab-case
     * - Removes empty/default values
     * - Sorts keys alphabetically
     * - Normalizes values (rounds pixels, etc.)
     *
     * @param array $styles Raw styles (camelCase or kebab-case)
     * @return array Canonicalized styles sorted alphabetically
     */
    protected function canonicalizeStyles(array $styles): array
    {
        $normalized = [];

        foreach ($styles as $key => $value) {
            // Skip empty/null/inherit/initial values
            if ($value === '' || $value === null || $value === 'inherit' || $value === 'initial') {
                continue;
            }

            // Convert camelCase to kebab-case
            $kebabKey = strtolower(preg_replace('/([A-Z])/', '-$1', $key));

            // Skip default CSS values that don't need to be stored
            if ($this->isDefaultCssValue($kebabKey, $value)) {
                continue;
            }

            // Normalize value (round pixels, trim whitespace, etc.)
            $normalized[$kebabKey] = $this->normalizeStyleValue($kebabKey, $value);
        }

        // Sort keys alphabetically for consistent hashing
        ksort($normalized);

        return $normalized;
    }

    /**
     * Check if value is a CSS default that doesn't need to be stored.
     *
     * NOTE: Only includes COMPUTED defaults (browser-assigned values).
     * User-choice properties like text-decoration, text-transform are NOT filtered
     * because when user selects "none" to RESET a property, we must save it.
     *
     * @param string $property CSS property name (kebab-case)
     * @param mixed $value Property value
     * @return bool True if value is a default
     */
    protected function isDefaultCssValue(string $property, mixed $value): bool
    {
        // Only filter COMPUTED defaults, NOT user-choice properties!
        // text-decoration, text-transform: User explicitly chooses, must save 'none' to override
        // font-style, font-weight: User choice, keep even default values
        $defaults = [
            'opacity' => ['1', '100%'],
            'background-color' => ['transparent', 'rgba(0, 0, 0, 0)', 'rgba(0,0,0,0)'],
            'border-style' => 'none',
            'display' => 'block', // Computed default for block elements
            'position' => 'static',
            'visibility' => 'visible',
        ];

        if (!isset($defaults[$property])) {
            return false;
        }

        $defaultValue = $defaults[$property];
        $stringValue = (string) $value;

        if (is_array($defaultValue)) {
            return in_array($stringValue, $defaultValue, true);
        }

        return $defaultValue === $stringValue;
    }

    /**
     * Normalize style value for consistent comparison.
     *
     * @param string $property CSS property name
     * @param mixed $value Raw value
     * @return string Normalized value
     */
    protected function normalizeStyleValue(string $property, mixed $value): string
    {
        $value = trim((string) $value);

        // Round pixel values to avoid float precision issues (16.6666667px -> 17px)
        if (preg_match('/^(\d+\.?\d*)px$/', $value, $matches)) {
            return round((float) $matches[1]) . 'px';
        }

        // Normalize hex colors to lowercase
        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value)) {
            return strtolower($value);
        }

        // Normalize rgba/rgb whitespace: rgba( 0, 0, 0, 0.5 ) -> rgba(0,0,0,0.5)
        if (preg_match('/^(rgba?)\s*\(\s*(.+?)\s*\)$/i', $value, $matches)) {
            $parts = preg_split('/\s*,\s*/', $matches[2]);
            return strtolower($matches[1]) . '(' . implode(',', $parts) . ')';
        }

        return $value;
    }

    /**
     * @deprecated Use generateStyleHash() instead
     * Kept for backward compatibility during migration
     */
    protected function generateCssClassName(int $productId, int $shopId, string $elementId): string
    {
        $hash = substr(md5("{$productId}-{$shopId}-{$elementId}"), 0, 8);
        return "uve-e{$hash}";
    }

    /**
     * Add or update CSS rule for element using per-style hash.
     *
     * Per-style hash means:
     * - Same styles = same CSS class (deduplication)
     * - Multiple elements can share one class
     * - Smaller CSS output
     *
     * @param string $elementId Element ID
     * @param array $styles Styles in camelCase format: ['fontSize' => '56px', 'fontWeight' => '800']
     */
    protected function setCssRule(string $elementId, array $styles): void
    {
        if (empty($styles)) {
            Log::debug('[UVE] setCssRule: Empty styles, skipping', ['elementId' => $elementId]);
            return;
        }

        // Canonicalize styles for consistent hashing
        $canonical = $this->canonicalizeStyles($styles);

        if (empty($canonical)) {
            Log::debug('[UVE] setCssRule: No valid styles after canonicalization', [
                'elementId' => $elementId,
                'inputStylesCount' => count($styles),
            ]);
            return;
        }

        // Generate hash from canonicalized styles (per-style, not per-element)
        $className = $this->generateStyleHash($canonical);

        if (empty($className)) {
            return;
        }

        // Store style definition (unique per style combination)
        // If same styles exist, they will have same className - this is CSS deduplication
        $this->cssRules[".{$className}"] = $canonical;

        // Map element to style class (many elements can share same class)
        $this->cssClassMap[$elementId] = $className;

        $this->cssDirty = true;

        Log::debug('[UVE] Per-style hash generated', [
            'elementId' => $elementId,
            'className' => $className,
            'styleCount' => count($canonical),
            'totalUniqueRules' => count($this->cssRules),
        ]);
    }

    /**
     * Get CSS class name for element.
     *
     * @param string $elementId Element ID
     * @return string CSS class name (without dot) or empty string
     */
    protected function getCssClassForElement(string $elementId): string
    {
        return $this->cssClassMap[$elementId] ?? '';
    }

    /**
     * Remove CSS rule for element.
     *
     * With per-style hash, the CSS rule is only removed if no other elements use it.
     *
     * @param string $elementId Element ID
     */
    protected function removeCssRule(string $elementId): void
    {
        $className = $this->cssClassMap[$elementId] ?? null;

        if (!$className) {
            return;
        }

        // Remove element from mapping
        unset($this->cssClassMap[$elementId]);
        $this->cssDirty = true;

        // Check if any other element uses this class
        $isClassStillUsed = in_array($className, $this->cssClassMap, true);

        if (!$isClassStillUsed) {
            // No other element uses this class, safe to remove the CSS rule
            unset($this->cssRules[".{$className}"]);
            Log::debug('[UVE] CSS rule removed (no longer used)', [
                'className' => $className,
                'removedForElement' => $elementId,
            ]);
        } else {
            Log::debug('[UVE] CSS rule kept (still used by other elements)', [
                'className' => $className,
                'removedForElement' => $elementId,
            ]);
        }
    }

    /**
     * Clear all CSS rules.
     */
    protected function clearCssRules(): void
    {
        $this->cssRules = [];
        $this->cssClassMap = [];
        $this->cssDirty = true;
    }

    // =====================
    // CSS OUTPUT GENERATION
    // =====================

    /**
     * @deprecated REMOVED in v2.0 - inline_style_block mode eliminated
     *
     * Per CSS-First architecture v2.0, inline <style> blocks are FORBIDDEN.
     * CSS must be delivered via external file (FTP sync to uve-custom.css).
     * If FTP not configured, css_mode = 'pending' and sync is blocked.
     *
     * @throws \RuntimeException Always - this method should not be called
     */
    protected function generateStyleBlock(): string
    {
        throw new \RuntimeException(
            'generateStyleBlock() is deprecated. CSS-First v2.0 does not support inline_style_block mode. ' .
            'Configure FTP for external CSS delivery or use css_mode=pending.'
        );
    }

    /**
     * Generate minified CSS string (without <style> tags).
     *
     * ETAP_07h FIX (2026-01-12): Smart filter for layout-breaking properties.
     * Only filters FIXED PIXEL values for dimensions/positions.
     * KEEPS percentage (100%), viewport (vw/vh), auto, fit-content values.
     *
     * @return string Minified CSS
     */
    protected function generateCssString(): string
    {
        if (empty($this->cssRules)) {
            return '';
        }

        $css = '';
        foreach ($this->cssRules as $selector => $properties) {
            $css .= $selector . '{';
            $propsStr = [];
            foreach ($properties as $prop => $value) {
                // Skip layout-breaking properties with FIXED PIXEL values only
                if ($this->shouldSkipCssProperty($prop, $value)) {
                    continue;
                }
                $propsStr[] = "{$prop}:{$value}";
            }
            $css .= implode(';', $propsStr) . '}';
        }

        return $css;
    }

    /**
     * Check if a CSS property should be skipped from output.
     *
     * Smart filter: only skip FIXED PIXEL values for dimension/position properties.
     * KEEPS: percentage (100%), viewport (vw/vh), auto, fit-content, max-content values.
     *
     * @param string $prop CSS property name
     * @param string $value CSS value
     * @return bool True if property should be skipped
     */
    protected function shouldSkipCssProperty(string $prop, string $value): bool
    {
        // Properties that are layout-breaking only when using FIXED pixel values
        $dimensionProperties = [
            'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height',
        ];

        // Properties that should ALWAYS be skipped (computed values)
        $alwaysSkipProperties = [
            'position', 'top', 'right', 'bottom', 'left',
            'background-position', 'background-size',
        ];

        // Always skip certain properties
        if (in_array($prop, $alwaysSkipProperties)) {
            return true;
        }

        // For dimension properties, only skip FIXED PIXEL values
        if (in_array($prop, $dimensionProperties)) {
            // KEEP: percentage, viewport units, auto, fit-content, max-content, min-content
            $keepPatterns = ['%', 'vw', 'vh', 'vmin', 'vmax', 'auto', 'fit-content', 'max-content', 'min-content'];
            foreach ($keepPatterns as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    return false; // KEEP this value
                }
            }
            // SKIP: fixed pixel values like "131px", "39px"
            if (preg_match('/^\d+(\.\d+)?px$/', $value)) {
                return true;
            }
        }

        // For display/flex properties - skip computed defaults, keep intentional values
        if ($prop === 'display') {
            // Skip only 'block' (default) - keep flex, grid, inline-block, etc.
            return $value === 'block';
        }

        if (in_array($prop, ['flex-direction', 'gap'])) {
            // These are usually intentional - keep them
            return false;
        }

        return false;
    }

    // =====================
    // CSS MODE DETECTION
    // =====================

    /**
     * Determine CSS delivery mode based on shop config.
     *
     * CSS-First v2.0 Modes:
     * - 'external': FTP configured, upload to /themes/{theme}/css/uve-custom.css
     * - 'pending': No FTP, CSS sync blocked (user must configure FTP)
     *
     * IMPORTANT: 'inline_style_block' mode is ELIMINATED in v2.0.
     * No inline styles or embedded <style> blocks are allowed.
     *
     * @return string 'pending' or 'external'
     */
    protected function determineCssMode(): string
    {
        $shop = PrestaShopShop::find($this->shopId);

        if ($shop && !empty($shop->ftp_config) && !empty($shop->ftp_config['host'])) {
            return 'external';
        }

        // v2.0: Return 'pending' instead of 'inline_style_block'
        // CSS sync is blocked until FTP is configured
        return 'pending';
    }

    /**
     * Check if external CSS mode is available.
     *
     * @return bool True if FTP is configured
     */
    protected function isExternalCssModeAvailable(): bool
    {
        return $this->determineCssMode() === 'external';
    }

    // =====================
    // ZERO-INLINE VALIDATION (v2.0)
    // =====================

    /**
     * Validate that HTML contains ZERO inline styles.
     *
     * CSS-First v2.0 architecture FORBIDS:
     * - style="" attributes on any element
     * - <style> blocks embedded in content
     *
     * This validation MUST pass before save/sync.
     *
     * @param string $html HTML content to validate
     * @return array Validation result: ['valid' => bool, 'errors' => array]
     */
    protected function validateZeroInlineStyles(string $html): array
    {
        $errors = [];

        // Check for style="" attributes (any inline styles)
        if (preg_match_all('/\sstyle\s*=\s*["\'][^"\']+["\']/', $html, $matches)) {
            foreach ($matches[0] as $match) {
                // Allow empty style="" (will be cleaned)
                $styleContent = preg_replace('/\sstyle\s*=\s*["\']([^"\']*)["\']/', '$1', $match);
                if (trim($styleContent) !== '') {
                    $errors[] = [
                        'type' => 'inline_style_attribute',
                        'found' => substr(trim($match), 0, 100),
                        'message' => 'Inline style attribute found - use CSS classes instead',
                    ];
                }
            }
        }

        // Check for <style> blocks
        if (preg_match_all('/<style[^>]*>.*?<\/style>/is', $html, $styleBlocks)) {
            foreach ($styleBlocks[0] as $block) {
                $errors[] = [
                    'type' => 'embedded_style_block',
                    'found' => substr($block, 0, 100) . (strlen($block) > 100 ? '...' : ''),
                    'message' => 'Embedded <style> block found - CSS must be in external file',
                ];
            }
        }

        // Check for Tailwind arbitrary values like class="z-[9999]"
        if (preg_match_all('/class\s*=\s*["\'][^"\']*\[[^\]]+\][^"\']*["\']/', $html, $arbitraryClasses)) {
            foreach ($arbitraryClasses[0] as $match) {
                // Extract the arbitrary value
                if (preg_match('/\[([^\]]+)\]/', $match, $arbValue)) {
                    $errors[] = [
                        'type' => 'tailwind_arbitrary_value',
                        'found' => $arbValue[0],
                        'message' => 'Tailwind arbitrary value found - define proper CSS class',
                    ];
                }
            }
        }

        $valid = empty($errors);

        if (!$valid) {
            Log::warning('[UVE] Zero-inline validation FAILED', [
                'product_id' => $this->productId ?? null,
                'shop_id' => $this->shopId ?? null,
                'error_count' => count($errors),
                'errors' => $errors,
            ]);
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'error_count' => count($errors),
        ];
    }

    /**
     * Assert that HTML passes zero-inline validation.
     *
     * @param string $html HTML content to validate
     * @throws \InvalidArgumentException If validation fails
     */
    protected function assertZeroInlineStyles(string $html): void
    {
        $result = $this->validateZeroInlineStyles($html);

        if (!$result['valid']) {
            $errorMessages = array_map(
                fn($e) => "[{$e['type']}] {$e['message']}: {$e['found']}",
                $result['errors']
            );

            throw new \InvalidArgumentException(
                "CSS-First v2.0 validation failed. HTML contains forbidden inline styles:\n" .
                implode("\n", $errorMessages)
            );
        }
    }

    /**
     * Clean HTML by removing empty style attributes.
     *
     * Used during save to clean up any empty style="" left from editing.
     * Does NOT remove style attributes with content - use assertZeroInlineStyles() first.
     *
     * @param string $html HTML to clean
     * @return string Cleaned HTML
     */
    protected function cleanEmptyStyleAttributes(string $html): string
    {
        // Remove empty style="" or style='' attributes
        return preg_replace('/\s+style\s*=\s*["\'][\s]*["\']/', '', $html);
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Convert camelCase styles to kebab-case CSS properties.
     *
     * @param array $styles Styles in camelCase: ['fontSize' => '56px']
     * @return array CSS properties in kebab-case: ['font-size' => '56px']
     */
    protected function convertToCssProperties(array $styles): array
    {
        $cssProperties = [];

        foreach ($styles as $prop => $value) {
            // Skip empty values
            if ($value === '' || $value === null) {
                continue;
            }

            // Convert camelCase to kebab-case
            $cssProp = strtolower(preg_replace('/([A-Z])/', '-$1', $prop));

            // Normalize values
            $cssProperties[$cssProp] = $this->normalizeCssValue($cssProp, $value);
        }

        return $cssProperties;
    }

    /**
     * Normalize CSS value (add units, quotes, etc.).
     *
     * @param string $property CSS property name
     * @param mixed $value Raw value
     * @return string Normalized CSS value
     */
    protected function normalizeCssValue(string $property, mixed $value): string
    {
        $value = (string) $value;

        // Properties that need 'px' unit if numeric
        $needsPixelUnit = [
            'font-size', 'line-height', 'letter-spacing',
            'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'border-width', 'border-radius', 'gap', 'top', 'right', 'bottom', 'left',
        ];

        // Add px to numeric values for these properties
        if (in_array($property, $needsPixelUnit) && is_numeric($value) && $value !== '0') {
            return $value . 'px';
        }

        // Handle font-family (ensure quotes for multi-word fonts)
        if ($property === 'font-family' && !str_contains($value, ',') && str_contains($value, ' ')) {
            return "'{$value}'";
        }

        return $value;
    }

    /**
     * Load CSS rules from ProductDescription model.
     */
    protected function loadCssRulesFromDescription(): void
    {
        if (!$this->description) {
            return;
        }

        $this->cssRules = $this->description->css_rules ?? [];
        $this->cssClassMap = $this->description->css_class_map ?? [];
        $this->cssDirty = false;

        Log::debug('UVE_CssClassGeneration: Loaded CSS rules from description', [
            'product_id' => $this->productId,
            'rulesCount' => count($this->cssRules),
            'mappingsCount' => count($this->cssClassMap),
        ]);
    }

    /**
     * Save CSS rules to ProductDescription model.
     */
    protected function saveCssRulesToDescription(): void
    {
        if (!$this->description || !$this->cssDirty) {
            return;
        }

        $cssMode = $this->determineCssMode();

        $this->description->update([
            'css_rules' => $this->cssRules,
            'css_class_map' => $this->cssClassMap,
            'css_mode' => $cssMode,
        ]);

        $this->cssDirty = false;

        Log::info('UVE_CssClassGeneration: Saved CSS rules to description', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'rulesCount' => count($this->cssRules),
            'cssMode' => $cssMode,
        ]);
    }

    /**
     * Get CSS rules statistics including deduplication metrics.
     *
     * @return array Stats: rules_count, selectors, estimated_size, deduplication_ratio
     */
    public function getCssStats(): array
    {
        $cssString = $this->generateCssString();

        $totalElements = count($this->cssClassMap);
        $uniqueRules = count($this->cssRules);

        // Calculate deduplication ratio
        // 1.0 = no deduplication (each element has unique styles)
        // 0.5 = 50% deduplication (half as many rules as elements)
        $deduplicationRatio = $totalElements > 0
            ? round($uniqueRules / $totalElements, 2)
            : 1.0;

        // Count how many elements share each class
        $classUsageCounts = array_count_values($this->cssClassMap);
        $sharedClasses = array_filter($classUsageCounts, fn($count) => $count > 1);

        return [
            'rules_count' => $uniqueRules,
            'elements_count' => $totalElements,
            'selectors' => array_keys($this->cssRules),
            'estimated_size' => strlen($cssString),
            'css_mode' => $this->determineCssMode(),
            'is_dirty' => $this->cssDirty,
            // Per-style hash metrics
            'deduplication_ratio' => $deduplicationRatio,
            'shared_classes_count' => count($sharedClasses),
            'max_class_reuse' => $sharedClasses ? max($classUsageCounts) : 1,
        ];
    }

    /**
     * Get deduplication report for debugging.
     *
     * @return array Detailed report of which elements share which classes
     */
    public function getDeduplicationReport(): array
    {
        $classUsage = [];

        foreach ($this->cssClassMap as $elementId => $className) {
            if (!isset($classUsage[$className])) {
                $classUsage[$className] = [];
            }
            $classUsage[$className][] = $elementId;
        }

        $shared = array_filter($classUsage, fn($elements) => count($elements) > 1);

        return [
            'total_elements' => count($this->cssClassMap),
            'unique_classes' => count($this->cssRules),
            'elements_saved' => count($this->cssClassMap) - count($this->cssRules),
            'shared_classes' => $shared,
        ];
    }
}
