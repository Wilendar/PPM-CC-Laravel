<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset;

use App\Contracts\VisualEditor\StylesetCompilerInterface;
use App\Models\ShopStyleset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Compiles ShopStylesets to final CSS.
 *
 * Handles:
 * - CSS variable compilation with namespace
 * - Custom CSS merging
 * - Base block styles inclusion
 * - CSS minification
 * - Caching compiled output
 */
class StylesetCompiler implements StylesetCompilerInterface
{
    private const CACHE_PREFIX = 'styleset.compiled.';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Default CSS variables (fallback).
     */
    private array $defaultVariables = [
        'primary-color' => '#2563eb',
        'secondary-color' => '#64748b',
        'accent-color' => '#f59e0b',
        'text-color' => '#1e293b',
        'text-muted' => '#64748b',
        'text-light' => '#ffffff',
        'background-color' => '#ffffff',
        'background-alt' => '#f8fafc',
        'background-dark' => '#1e293b',
        'font-family' => 'system-ui, -apple-system, sans-serif',
        'heading-font' => 'inherit',
        'font-size-base' => '1rem',
        'font-size-sm' => '0.875rem',
        'font-size-lg' => '1.125rem',
        'line-height' => '1.6',
        'spacing-unit' => '1rem',
        'spacing-xs' => '0.25rem',
        'spacing-sm' => '0.5rem',
        'spacing-md' => '1rem',
        'spacing-lg' => '1.5rem',
        'spacing-xl' => '2rem',
        'border-color' => '#e2e8f0',
        'border-radius' => '0.5rem',
        'border-radius-sm' => '0.25rem',
        'border-radius-lg' => '0.75rem',
        'shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        'shadow-sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'shadow-lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1)',
    ];

    public function __construct(
        private StylesetValidator $validator
    ) {}

    /**
     * {@inheritdoc}
     */
    public function compile(ShopStyleset $styleset, array $options = []): string
    {
        $minify = $options['minify'] ?? false;
        $includeBase = $options['include_base'] ?? true;
        $includeReset = $options['include_reset'] ?? false;

        $parts = [];

        // CSS Reset (optional)
        if ($includeReset) {
            $parts[] = $this->getCssReset();
        }

        // Variables
        $namespace = $styleset->css_namespace ?? 'pd';
        $variables = $this->mergeVariables($styleset->variables_json ?? []);
        $parts[] = $this->compileVariables($variables, $namespace);

        // Base block styles
        if ($includeBase) {
            $parts[] = $this->getBaseBlockStyles($namespace);
        }

        // Custom CSS from styleset
        if ($styleset->css_content) {
            $parts[] = "/* Custom CSS: {$styleset->name} */\n" . $styleset->css_content;
        }

        $css = implode("\n\n", array_filter($parts));

        if ($minify) {
            $css = $this->minify($css);
        }

        return $css;
    }

    /**
     * {@inheritdoc}
     */
    public function compileVariables(array $variables, string $namespace = 'pd'): string
    {
        $lines = [":root {"];

        foreach ($variables as $name => $value) {
            // Remove leading -- if present for consistency
            $cleanName = ltrim($name, '-');
            $cssVar = "--{$namespace}-{$cleanName}";
            $lines[] = "  {$cssVar}: {$value};";
        }

        $lines[] = "}";

        return implode("\n", $lines);
    }

    /**
     * {@inheritdoc}
     */
    public function minify(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove spaces around selectors/properties
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);

        // Remove trailing semicolons before }
        $css = preg_replace('/;}/', '}', $css);

        // Remove newlines
        $css = str_replace(["\r\n", "\r", "\n"], '', $css);

        return trim($css);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $css): array
    {
        return $this->validator->validate($css);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompiledCssForShop(int $shopId, bool $cached = true): string
    {
        $cacheKey = self::CACHE_PREFIX . $shopId;

        if ($cached && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $styleset = ShopStyleset::forShop($shopId)->active()->first();

        if (!$styleset) {
            // Return default compiled CSS
            return $this->compileVariables($this->defaultVariables) . "\n\n" . $this->getBaseBlockStyles('pd');
        }

        $css = $this->compile($styleset, ['minify' => true, 'include_base' => true]);

        if ($cached) {
            Cache::put($cacheKey, $css, self::CACHE_TTL);
        }

        return $css;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(int $shopId): void
    {
        Cache::forget(self::CACHE_PREFIX . $shopId);
    }

    /**
     * Get default variables.
     */
    public function getDefaultVariables(): array
    {
        return $this->defaultVariables;
    }

    /**
     * Merge custom variables with defaults.
     */
    private function mergeVariables(array $custom): array
    {
        $merged = $this->defaultVariables;

        foreach ($custom as $name => $value) {
            // Normalize name (remove -- prefix if present)
            $cleanName = ltrim($name, '-');
            $merged[$cleanName] = $value;
        }

        return $merged;
    }

    /**
     * Get base block styles.
     */
    private function getBaseBlockStyles(string $namespace): string
    {
        $path = resource_path('css/visual-editor/base-blocks.css');

        if (File::exists($path)) {
            $css = File::get($path);
            // Replace namespace placeholder
            return str_replace('{{namespace}}', $namespace, $css);
        }

        // Inline fallback base styles
        return $this->getInlineBaseStyles($namespace);
    }

    /**
     * Inline fallback base styles.
     */
    private function getInlineBaseStyles(string $namespace): string
    {
        $ns = $namespace;
        return <<<CSS
/* Base Block Styles - {$ns} namespace */
.{$ns}-block {
  margin-bottom: var(--{$ns}-spacing-lg);
}

.{$ns}-block-contained {
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--{$ns}-spacing-md);
  padding-right: var(--{$ns}-spacing-md);
}

.{$ns}-heading {
  color: var(--{$ns}-text-color);
  font-family: var(--{$ns}-heading-font);
  line-height: 1.2;
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-text {
  color: var(--{$ns}-text-color);
  font-family: var(--{$ns}-font-family);
  line-height: var(--{$ns}-line-height);
}

.{$ns}-image {
  max-width: 100%;
  height: auto;
  border-radius: var(--{$ns}-border-radius);
}

.{$ns}-button {
  display: inline-flex;
  align-items: center;
  gap: var(--{$ns}-spacing-sm);
  padding: var(--{$ns}-spacing-sm) var(--{$ns}-spacing-md);
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border: none;
  border-radius: var(--{$ns}-border-radius);
  font-family: var(--{$ns}-font-family);
  cursor: pointer;
  transition: opacity 0.2s;
}

.{$ns}-button:hover {
  opacity: 0.9;
}

.{$ns}-grid {
  display: grid;
  gap: var(--{$ns}-spacing-md);
}

.{$ns}-flex {
  display: flex;
  gap: var(--{$ns}-spacing-md);
}
CSS;
    }

    /**
     * Get minimal CSS reset.
     */
    private function getCssReset(): string
    {
        return <<<CSS
/* CSS Reset for Product Descriptions */
.pd-wrapper *, .pd-wrapper *::before, .pd-wrapper *::after {
  box-sizing: border-box;
}

.pd-wrapper img, .pd-wrapper picture, .pd-wrapper video, .pd-wrapper svg {
  display: block;
  max-width: 100%;
}

.pd-wrapper p, .pd-wrapper h1, .pd-wrapper h2, .pd-wrapper h3,
.pd-wrapper h4, .pd-wrapper h5, .pd-wrapper h6 {
  overflow-wrap: break-word;
}
CSS;
    }
}
