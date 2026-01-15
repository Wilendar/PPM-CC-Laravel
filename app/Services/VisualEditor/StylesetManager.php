<?php

namespace App\Services\VisualEditor;

use App\Models\ShopStyleset;
use Illuminate\Support\Facades\Cache;

/**
 * Manages shop-specific stylesets for Visual Editor.
 *
 * Handles:
 * - Retrieving stylesets for shops
 * - Compiling CSS variables
 * - Applying styles to rendered HTML
 */
class StylesetManager
{
    /**
     * Default CSS variables when no styleset is defined.
     */
    private array $defaultVariables = [
        '--pd-primary-color' => '#2563eb',
        '--pd-secondary-color' => '#64748b',
        '--pd-accent-color' => '#f59e0b',
        '--pd-text-color' => '#1e293b',
        '--pd-background-color' => '#ffffff',
        '--pd-font-family' => 'system-ui, -apple-system, sans-serif',
        '--pd-heading-font' => 'inherit',
        '--pd-spacing-unit' => '1rem',
        '--pd-border-radius' => '0.5rem',
        '--pd-shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    ];

    /**
     * Get active styleset for a shop.
     *
     * @param int $shopId PrestaShop shop ID
     * @return ShopStyleset|null
     */
    public function getForShop(int $shopId): ?ShopStyleset
    {
        return Cache::remember(
            "styleset.shop.{$shopId}",
            now()->addHours(1),
            fn() => ShopStyleset::forShop($shopId)->active()->first()
        );
    }

    /**
     * Get styleset by ID.
     */
    public function getById(int $stylesetId): ?ShopStyleset
    {
        return ShopStyleset::find($stylesetId);
    }

    /**
     * Compile CSS variables from styleset into CSS string.
     *
     * @param ShopStyleset|null $styleset
     * @return string CSS :root declaration
     */
    public function compileVariables(?ShopStyleset $styleset = null): string
    {
        $variables = $this->defaultVariables;

        if ($styleset) {
            $customVars = $styleset->variables_json ?? [];
            $variables = array_merge($variables, $customVars);
        }

        $css = ":root {\n";
        foreach ($variables as $name => $value) {
            $css .= "  {$name}: {$value};\n";
        }
        $css .= "}\n";

        return $css;
    }

    /**
     * Get full CSS for a styleset (variables + custom CSS).
     *
     * @param ShopStyleset|null $styleset
     * @return string Complete CSS
     */
    public function getFullCss(?ShopStyleset $styleset = null): string
    {
        $css = $this->compileVariables($styleset);

        if ($styleset && $styleset->css_content) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $styleset->css_content;
        }

        return $css;
    }

    /**
     * Apply styleset to rendered HTML by wrapping with style tag.
     *
     * @param string $html Rendered block HTML
     * @param ShopStyleset|null $styleset
     * @return string HTML with embedded styles
     */
    public function applyToHtml(string $html, ?ShopStyleset $styleset = null): string
    {
        $css = $this->getFullCss($styleset);
        $namespace = $styleset?->css_namespace ?? 'pd';

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
     * Get a specific variable value from styleset.
     *
     * @param ShopStyleset|null $styleset
     * @param string $variable Variable name (without --)
     * @return string|null
     */
    public function getVariable(?ShopStyleset $styleset, string $variable): ?string
    {
        $fullName = str_starts_with($variable, '--') ? $variable : "--{$variable}";

        if ($styleset) {
            $customVars = $styleset->variables_json ?? [];
            if (isset($customVars[$fullName])) {
                return $customVars[$fullName];
            }
        }

        return $this->defaultVariables[$fullName] ?? null;
    }

    /**
     * Generate CSS variable reference.
     *
     * @param string $variable Variable name
     * @param string|null $fallback Fallback value
     * @return string CSS var() function
     */
    public function varRef(string $variable, ?string $fallback = null): string
    {
        $name = str_starts_with($variable, '--') ? $variable : "--{$variable}";

        if ($fallback) {
            return "var({$name}, {$fallback})";
        }

        return "var({$name})";
    }

    /**
     * Clear cached styleset for a shop.
     */
    public function clearCache(int $shopId): void
    {
        Cache::forget("styleset.shop.{$shopId}");
    }

    /**
     * Get default variables.
     */
    public function getDefaultVariables(): array
    {
        return $this->defaultVariables;
    }

    /**
     * Minify CSS string.
     */
    public function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove unnecessary spaces
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);

        // Remove trailing semicolons
        $css = preg_replace('/;}/', '}', $css);

        return trim($css);
    }
}
