<?php

declare(strict_types=1);

namespace App\Contracts\VisualEditor;

/**
 * Interface for shop-specific styleset definitions.
 *
 * Each shop (B2B, KAYO, YCF, Pitgang, MRF) implements this interface
 * to define its default CSS variables, custom CSS, and editor configuration.
 */
interface ShopStylesetDefinitionInterface
{
    /**
     * Get styleset name (human-readable).
     *
     * @return string e.g., "B2B Professional", "KAYO Racing"
     */
    public function getName(): string;

    /**
     * Get CSS namespace prefix.
     *
     * @return string e.g., 'pd-', 'blok-'
     */
    public function getNamespace(): string;

    /**
     * Get default CSS variables.
     *
     * @return array Associative array of CSS variables
     *   ['primary-color' => '#2563eb', 'font-family' => 'system-ui, ...']
     */
    public function getDefaultVariables(): array;

    /**
     * Get custom CSS (beyond variables).
     *
     * @return string CSS content
     */
    public function getCustomCss(): string;

    /**
     * Get editor configuration for variable grouping.
     *
     * @return array Groups with their variables
     *   [
     *     'Kolory' => [
     *       ['name' => 'primary-color', 'label' => 'Kolor podstawowy', 'type' => 'color'],
     *       ['name' => 'secondary-color', 'label' => 'Kolor dodatkowy', 'type' => 'color'],
     *     ],
     *     'Typografia' => [...],
     *   ]
     */
    public function getEditorGroups(): array;

    /**
     * Get shop type identifier.
     *
     * @return string e.g., 'b2b', 'kayo', 'ycf', 'pitgang', 'mrf'
     */
    public function getType(): string;

    /**
     * Get supported block types for this shop.
     *
     * @return array|null List of block types, or null for all blocks
     */
    public function getSupportedBlocks(): ?array;

    /**
     * Get responsive breakpoints configuration.
     *
     * @return array Breakpoint definitions
     *   ['sm' => '640px', 'md' => '768px', 'lg' => '1024px', 'xl' => '1280px']
     */
    public function getBreakpoints(): array;
}
