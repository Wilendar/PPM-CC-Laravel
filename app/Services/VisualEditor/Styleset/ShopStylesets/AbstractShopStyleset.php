<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

use App\Contracts\VisualEditor\ShopStylesetDefinitionInterface;

/**
 * Abstract base class for shop-specific styleset definitions.
 *
 * Provides common functionality and default values that can be
 * overridden by specific shop implementations.
 */
abstract class AbstractShopStyleset implements ShopStylesetDefinitionInterface
{
    /**
     * Default breakpoints for responsive design.
     */
    protected array $breakpoints = [
        'sm' => '640px',
        'md' => '768px',
        'lg' => '1024px',
        'xl' => '1280px',
    ];

    /**
     * Common editor groups structure.
     */
    protected function getBaseEditorGroups(): array
    {
        return [
            'Kolory' => [
                ['name' => 'primary-color', 'label' => 'Kolor podstawowy', 'type' => 'color'],
                ['name' => 'secondary-color', 'label' => 'Kolor dodatkowy', 'type' => 'color'],
                ['name' => 'accent-color', 'label' => 'Kolor akcentu', 'type' => 'color'],
                ['name' => 'text-color', 'label' => 'Kolor tekstu', 'type' => 'color'],
                ['name' => 'background-color', 'label' => 'Kolor tla', 'type' => 'color'],
            ],
            'Typografia' => [
                ['name' => 'font-family', 'label' => 'Czcionka glowna', 'type' => 'font'],
                ['name' => 'heading-font', 'label' => 'Czcionka naglowkow', 'type' => 'font'],
                ['name' => 'font-size-base', 'label' => 'Rozmiar bazowy', 'type' => 'size'],
                ['name' => 'line-height', 'label' => 'Wysokosc linii', 'type' => 'number'],
            ],
            'Odstepy' => [
                ['name' => 'spacing-unit', 'label' => 'Jednostka odstepow', 'type' => 'size'],
                ['name' => 'spacing-sm', 'label' => 'Odstep maly', 'type' => 'size'],
                ['name' => 'spacing-md', 'label' => 'Odstep sredni', 'type' => 'size'],
                ['name' => 'spacing-lg', 'label' => 'Odstep duzy', 'type' => 'size'],
            ],
            'Obramowania' => [
                ['name' => 'border-color', 'label' => 'Kolor obramowania', 'type' => 'color'],
                ['name' => 'border-radius', 'label' => 'Zaokraglenie', 'type' => 'size'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedBlocks(): ?array
    {
        // All blocks supported by default
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBreakpoints(): array
    {
        return $this->breakpoints;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorGroups(): array
    {
        return $this->getBaseEditorGroups();
    }

    /**
     * Generate utility classes for spacing.
     */
    protected function generateSpacingUtilities(string $namespace): string
    {
        $css = "/* Spacing Utilities */\n";
        $sides = ['t' => 'top', 'r' => 'right', 'b' => 'bottom', 'l' => 'left'];
        $sizes = ['0' => '0', '1' => '0.25rem', '2' => '0.5rem', '3' => '0.75rem', '4' => '1rem', '5' => '1.5rem'];

        foreach (['m' => 'margin', 'p' => 'padding'] as $prefix => $property) {
            foreach ($sizes as $size => $value) {
                $css .= ".{$namespace}-{$prefix}-{$size} { {$property}: {$value}; }\n";

                foreach ($sides as $sidePrefix => $side) {
                    $css .= ".{$namespace}-{$prefix}{$sidePrefix}-{$size} { {$property}-{$side}: {$value}; }\n";
                }

                // X and Y axis
                $css .= ".{$namespace}-{$prefix}x-{$size} { {$property}-left: {$value}; {$property}-right: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}y-{$size} { {$property}-top: {$value}; {$property}-bottom: {$value}; }\n";
            }
        }

        return $css;
    }

    /**
     * Generate text utility classes.
     */
    protected function generateTextUtilities(string $namespace): string
    {
        return <<<CSS
/* Text Utilities */
.{$namespace}-text-left { text-align: left; }
.{$namespace}-text-center { text-align: center; }
.{$namespace}-text-right { text-align: right; }
.{$namespace}-text-justify { text-align: justify; }

.{$namespace}-font-normal { font-weight: 400; }
.{$namespace}-font-medium { font-weight: 500; }
.{$namespace}-font-semibold { font-weight: 600; }
.{$namespace}-font-bold { font-weight: 700; }

.{$namespace}-uppercase { text-transform: uppercase; }
.{$namespace}-lowercase { text-transform: lowercase; }
.{$namespace}-capitalize { text-transform: capitalize; }
CSS;
    }

    /**
     * Generate display utility classes.
     */
    protected function generateDisplayUtilities(string $namespace): string
    {
        return <<<CSS
/* Display Utilities */
.{$namespace}-hidden { display: none; }
.{$namespace}-block { display: block; }
.{$namespace}-inline-block { display: inline-block; }
.{$namespace}-flex { display: flex; }
.{$namespace}-inline-flex { display: inline-flex; }
.{$namespace}-grid { display: grid; }

.{$namespace}-flex-row { flex-direction: row; }
.{$namespace}-flex-col { flex-direction: column; }
.{$namespace}-flex-wrap { flex-wrap: wrap; }
.{$namespace}-items-center { align-items: center; }
.{$namespace}-items-start { align-items: flex-start; }
.{$namespace}-items-end { align-items: flex-end; }
.{$namespace}-justify-center { justify-content: center; }
.{$namespace}-justify-between { justify-content: space-between; }
.{$namespace}-justify-around { justify-content: space-around; }
CSS;
    }
}
