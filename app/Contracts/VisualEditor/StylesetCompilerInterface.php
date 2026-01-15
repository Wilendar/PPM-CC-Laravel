<?php

declare(strict_types=1);

namespace App\Contracts\VisualEditor;

use App\Models\ShopStyleset;

/**
 * Interface for Styleset compilation and CSS processing.
 *
 * Handles:
 * - Compiling ShopStyleset to final CSS
 * - CSS variables compilation
 * - CSS minification
 * - CSS validation
 */
interface StylesetCompilerInterface
{
    /**
     * Compile styleset to final CSS.
     *
     * @param ShopStyleset $styleset The styleset to compile
     * @param array $options Compilation options:
     *   - minify: bool - Minify output CSS
     *   - include_base: bool - Include base block styles
     *   - include_reset: bool - Include CSS reset
     * @return string Compiled CSS
     */
    public function compile(ShopStyleset $styleset, array $options = []): string;

    /**
     * Compile CSS variables to :root declaration.
     *
     * @param array $variables Associative array of CSS variables
     *   ['primary-color' => '#2563eb', ...]
     * @param string $namespace Variable namespace (default: 'pd')
     * @return string CSS :root declaration
     */
    public function compileVariables(array $variables, string $namespace = 'pd'): string;

    /**
     * Minify CSS string.
     *
     * @param string $css CSS to minify
     * @return string Minified CSS
     */
    public function minify(string $css): string;

    /**
     * Validate CSS syntax.
     *
     * @param string $css CSS to validate
     * @return array Validation result:
     *   ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validate(string $css): array;

    /**
     * Get compiled CSS for a shop.
     *
     * @param int $shopId Shop ID
     * @param bool $cached Use cached version
     * @return string Compiled CSS
     */
    public function getCompiledCssForShop(int $shopId, bool $cached = true): string;

    /**
     * Clear compiled CSS cache for a shop.
     *
     * @param int $shopId Shop ID
     */
    public function clearCache(int $shopId): void;
}
