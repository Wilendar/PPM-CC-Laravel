<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\ProductDescription;
use Illuminate\Support\Facades\Log;

/**
 * Inline Style Migrator for CSS-First Architecture.
 *
 * ETAP_07h v2.0: Migrates existing UVE descriptions with inline styles
 * to CSS-First format (css_rules + CSS classes).
 *
 * Migration process:
 * 1. Find elements with style="" attributes in compiled_html
 * 2. Extract inline styles
 * 3. Generate per-style hash (uve-s{hash})
 * 4. Store in css_rules/css_class_map
 * 5. Remove inline styles from HTML
 * 6. Add CSS classes to elements
 *
 * @package App\Services\VisualEditor
 */
class InlineStyleMigrator
{
    /**
     * Migration stats.
     */
    protected array $stats = [
        'processed' => 0,
        'migrated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'styles_extracted' => 0,
        'unique_rules' => 0,
    ];

    /**
     * CSS defaults to skip during migration.
     */
    protected array $cssDefaults = [
        'text-decoration' => 'none',
        'text-transform' => 'none',
        'font-style' => 'normal',
        'font-weight' => ['400', 'normal'],
        'opacity' => ['1', '100%'],
        'background-color' => ['transparent', 'rgba(0, 0, 0, 0)', 'rgba(0,0,0,0)'],
        'border-style' => 'none',
        'display' => 'block',
        'position' => 'static',
        'visibility' => 'visible',
    ];

    /**
     * Migrate a single product description.
     *
     * @param ProductDescription $description
     * @param bool $dryRun If true, don't save changes
     * @return array Migration result
     */
    public function migrate(ProductDescription $description, bool $dryRun = false): array
    {
        $this->stats['processed']++;

        // Skip if already migrated
        if (!empty($description->css_rules) && $description->css_mode === 'external') {
            $this->stats['skipped']++;
            return [
                'status' => 'skipped',
                'reason' => 'Already migrated (css_mode=external)',
                'description_id' => $description->id,
            ];
        }

        // Get blocks with compiled_html
        $blocks = $description->blocks_v2 ?? $description->blocks ?? [];

        if (empty($blocks)) {
            $this->stats['skipped']++;
            return [
                'status' => 'skipped',
                'reason' => 'No blocks found',
                'description_id' => $description->id,
            ];
        }

        try {
            $cssRules = [];
            $cssClassMap = [];
            $migratedBlocks = [];
            $stylesExtracted = 0;

            foreach ($blocks as $index => $block) {
                $html = $block['compiled_html'] ?? '';

                if (empty($html)) {
                    $migratedBlocks[] = $block;
                    continue;
                }

                // Extract inline styles and convert to CSS classes
                $migrationResult = $this->extractAndConvertStyles($html, "block-{$index}");

                $migratedBlocks[] = array_merge($block, [
                    'compiled_html' => $migrationResult['html'],
                ]);

                // Merge CSS rules (per-style deduplication happens automatically)
                foreach ($migrationResult['rules'] as $selector => $properties) {
                    $cssRules[$selector] = $properties;
                }

                // Merge class map
                foreach ($migrationResult['class_map'] as $elementId => $className) {
                    $cssClassMap[$elementId] = $className;
                }

                $stylesExtracted += $migrationResult['styles_count'];
            }

            if ($stylesExtracted === 0) {
                $this->stats['skipped']++;
                return [
                    'status' => 'skipped',
                    'reason' => 'No inline styles found',
                    'description_id' => $description->id,
                ];
            }

            $this->stats['styles_extracted'] += $stylesExtracted;
            $this->stats['unique_rules'] += count($cssRules);

            if (!$dryRun) {
                $description->update([
                    'blocks_v2' => $migratedBlocks,
                    'css_rules' => $cssRules,
                    'css_class_map' => $cssClassMap,
                    'css_mode' => 'pending', // Will be 'external' after sync
                    'css_migrated_at' => now(),
                ]);
            }

            $this->stats['migrated']++;

            return [
                'status' => 'migrated',
                'description_id' => $description->id,
                'product_id' => $description->product_id,
                'shop_id' => $description->shop_id,
                'styles_extracted' => $stylesExtracted,
                'unique_rules' => count($cssRules),
                'dry_run' => $dryRun,
            ];

        } catch (\Throwable $e) {
            $this->stats['errors']++;

            Log::error('InlineStyleMigrator: Migration failed', [
                'description_id' => $description->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'description_id' => $description->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract inline styles from HTML and convert to CSS classes.
     *
     * @param string $html HTML with inline styles
     * @param string $blockPrefix Prefix for element IDs
     * @return array ['html' => cleaned HTML, 'rules' => CSS rules, 'class_map' => mapping, 'styles_count' => int]
     */
    protected function extractAndConvertStyles(string $html, string $blockPrefix): array
    {
        $rules = [];
        $classMap = [];
        $stylesCount = 0;
        $elementIndex = 0;

        // Pattern to find elements with style attributes
        $pattern = '/<([a-zA-Z][a-zA-Z0-9]*)\s+([^>]*?)style=["\']([^"\']+)["\']([^>]*)>/i';

        $html = preg_replace_callback($pattern, function ($matches) use (&$rules, &$classMap, &$stylesCount, &$elementIndex, $blockPrefix) {
            $tag = $matches[1];
            $beforeStyle = $matches[2];
            $styleContent = $matches[3];
            $afterStyle = $matches[4];

            // Parse inline styles
            $styles = $this->parseInlineStyles($styleContent);

            if (empty($styles)) {
                // No valid styles, just remove style attribute
                return "<{$tag} {$beforeStyle}{$afterStyle}>";
            }

            $stylesCount++;
            $elementIndex++;

            // Generate element ID
            $elementId = "{$blockPrefix}-el-{$elementIndex}";

            // Generate per-style hash
            $className = $this->generateStyleHash($styles);

            if (empty($className)) {
                return "<{$tag} {$beforeStyle}{$afterStyle}>";
            }

            // Store rule (per-style deduplication)
            $rules[".{$className}"] = $styles;
            $classMap[$elementId] = $className;

            // Build new attributes (no style, add class and data-uve-id)
            $newAttrs = trim($beforeStyle . $afterStyle);

            // Add or merge class
            if (preg_match('/class=["\']([^"\']*)["\']/', $newAttrs, $classMatch)) {
                $existingClasses = $classMatch[1];
                if (strpos($existingClasses, $className) === false) {
                    $newAttrs = preg_replace(
                        '/class=["\']([^"\']*)["\']/',
                        'class="$1 ' . $className . '"',
                        $newAttrs
                    );
                }
            } else {
                $newAttrs .= ' class="' . $className . '"';
            }

            // Add data-uve-id if not present
            if (strpos($newAttrs, 'data-uve-id') === false) {
                $newAttrs .= ' data-uve-id="' . $elementId . '"';
            }

            return "<{$tag} " . trim($newAttrs) . ">";

        }, $html);

        return [
            'html' => $html,
            'rules' => $rules,
            'class_map' => $classMap,
            'styles_count' => $stylesCount,
        ];
    }

    /**
     * Parse inline style string to array.
     *
     * @param string $styleString e.g. "font-size: 56px; color: red;"
     * @return array Canonicalized styles
     */
    protected function parseInlineStyles(string $styleString): array
    {
        $styles = [];

        $declarations = explode(';', $styleString);

        foreach ($declarations as $declaration) {
            $declaration = trim($declaration);
            if (empty($declaration)) {
                continue;
            }

            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $property = trim(strtolower($parts[0]));
            $value = trim($parts[1]);

            // Skip empty values
            if ($value === '' || $value === 'inherit' || $value === 'initial') {
                continue;
            }

            // Skip defaults
            if ($this->isDefaultValue($property, $value)) {
                continue;
            }

            // Normalize value
            $styles[$property] = $this->normalizeValue($property, $value);
        }

        // Sort for consistent hashing
        ksort($styles);

        return $styles;
    }

    /**
     * Check if value is a CSS default.
     */
    protected function isDefaultValue(string $property, string $value): bool
    {
        if (!isset($this->cssDefaults[$property])) {
            return false;
        }

        $default = $this->cssDefaults[$property];

        if (is_array($default)) {
            return in_array($value, $default, true);
        }

        return $default === $value;
    }

    /**
     * Normalize CSS value.
     */
    protected function normalizeValue(string $property, string $value): string
    {
        $value = trim($value);

        // Round pixel values
        if (preg_match('/^(\d+\.?\d*)px$/', $value, $matches)) {
            return round((float) $matches[1]) . 'px';
        }

        // Lowercase colors
        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value)) {
            return strtolower($value);
        }

        // Normalize rgba
        if (preg_match('/^(rgba?)\s*\(\s*(.+?)\s*\)$/i', $value, $matches)) {
            $parts = preg_split('/\s*,\s*/', $matches[2]);
            return strtolower($matches[1]) . '(' . implode(',', $parts) . ')';
        }

        return $value;
    }

    /**
     * Generate per-style hash.
     *
     * @param array $styles Canonicalized styles
     * @return string CSS class name (uve-s{hash})
     */
    protected function generateStyleHash(array $styles): string
    {
        if (empty($styles)) {
            return '';
        }

        $hash = substr(md5(json_encode($styles, JSON_UNESCAPED_SLASHES)), 0, 8);
        return "uve-s{$hash}";
    }

    /**
     * Get migration statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Reset statistics.
     */
    public function resetStats(): void
    {
        $this->stats = [
            'processed' => 0,
            'migrated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'styles_extracted' => 0,
            'unique_rules' => 0,
        ];
    }
}
