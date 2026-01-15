<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

/**
 * MRF Shop Styleset Definition.
 *
 * Modern/Grid design (based on analysis of sklep.pitbikemrf.pl):
 * - Namespace: pd-* + grid utilities
 * - Colors: Brand rose (#e11d48), gray tones
 * - Typography: Arial-based, clean
 * - Features: Bootstrap-like spacing, CSS Grid, responsive md-* modifiers
 */
class MrfStyleset extends AbstractShopStyleset
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'MRF Modern Grid';
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return 'pd';
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'mrf';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return [
            // Colors - MRF brand
            'primary-color' => '#e11d48',
            'secondary-color' => '#374151',
            'accent-color' => '#f59e0b',
            'text-color' => '#1f2937',
            'text-muted' => '#6b7280',
            'text-light' => '#ffffff',
            'background-color' => '#ffffff',
            'background-alt' => '#f9fafb',
            'background-dark' => '#111827',

            // Typography - Arial-based
            'font-family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
            'heading-font' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
            'font-size-base' => '1rem',
            'font-size-sm' => '0.875rem',
            'font-size-lg' => '1.125rem',
            'font-size-xl' => '1.5rem',
            'line-height' => '1.6',

            // Spacing - Bootstrap-like
            'spacing-unit' => '1rem',
            'spacing-xs' => '0.25rem',
            'spacing-sm' => '0.5rem',
            'spacing-md' => '1rem',
            'spacing-lg' => '1.5rem',
            'spacing-xl' => '3rem',

            // Borders & Effects
            'border-color' => '#e5e7eb',
            'border-radius' => '0.375rem',
            'border-radius-sm' => '0.25rem',
            'border-radius-lg' => '0.5rem',
            'shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
            'shadow-sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            'shadow-lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomCss(): string
    {
        $ns = $this->getNamespace();

        return <<<CSS
/* MRF Modern Grid Theme */

/* Base Block Styles */
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

/* Hero Banner */
.{$ns}-hero {
  position: relative;
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl) var(--{$ns}-spacing-md);
}

.{$ns}-hero__title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-hero__subtitle {
  font-size: var(--{$ns}-font-size-lg);
  opacity: 0.9;
  max-width: 600px;
}

/* Joy Block (from MRF analysis) */
.{$ns}-joy-block {
  background: var(--{$ns}-background-alt);
  padding: var(--{$ns}-spacing-lg);
  border-radius: var(--{$ns}-border-radius);
}

/* Feature Cards */
.{$ns}-feature-card {
  background: var(--{$ns}-background-color);
  border: 1px solid var(--{$ns}-border-color);
  border-radius: var(--{$ns}-border-radius);
  padding: var(--{$ns}-spacing-lg);
  transition: all 0.2s ease;
}

.{$ns}-feature-card:hover {
  box-shadow: var(--{$ns}-shadow);
  transform: translateY(-2px);
}

.{$ns}-feature-card__icon {
  width: 48px;
  height: 48px;
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border-radius: var(--{$ns}-border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-feature-card__title {
  font-size: var(--{$ns}-font-size-lg);
  font-weight: 600;
  color: var(--{$ns}-text-color);
  margin-bottom: var(--{$ns}-spacing-sm);
}

/* Inverted Colors Section */
.{$ns}-inverted-colors {
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
}

.{$ns}-inverted-colors a {
  color: var(--{$ns}-primary-color);
}

/* Grid System (Bootstrap-like) */
.{$ns}-row {
  display: flex;
  flex-wrap: wrap;
  margin-left: calc(var(--{$ns}-spacing-md) * -0.5);
  margin-right: calc(var(--{$ns}-spacing-md) * -0.5);
}

.{$ns}-col {
  flex: 1 0 0%;
  padding-left: calc(var(--{$ns}-spacing-md) * 0.5);
  padding-right: calc(var(--{$ns}-spacing-md) * 0.5);
}

.{$ns}-col-6 { flex: 0 0 50%; max-width: 50%; }
.{$ns}-col-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
.{$ns}-col-3 { flex: 0 0 25%; max-width: 25%; }
.{$ns}-col-12 { flex: 0 0 100%; max-width: 100%; }

/* CSS Grid Alternative */
.grid { display: grid; }
.{$ns}-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--{$ns}-spacing-md); }
.{$ns}-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--{$ns}-spacing-md); }
.{$ns}-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--{$ns}-spacing-md); }

/* Specification Table */
.{$ns}-spec-table {
  width: 100%;
  border-collapse: collapse;
}

.{$ns}-spec-table th,
.{$ns}-spec-table td {
  padding: var(--{$ns}-spacing-sm) var(--{$ns}-spacing-md);
  text-align: left;
  border-bottom: 1px solid var(--{$ns}-border-color);
}

.{$ns}-spec-table th {
  background: var(--{$ns}-background-alt);
  font-weight: 600;
}

/* CTA Button */
.{$ns}-cta {
  display: inline-flex;
  align-items: center;
  gap: var(--{$ns}-spacing-sm);
  padding: var(--{$ns}-spacing-sm) var(--{$ns}-spacing-lg);
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border: none;
  border-radius: var(--{$ns}-border-radius);
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}

.{$ns}-cta:hover {
  opacity: 0.9;
}

/* Spacing Utilities (Bootstrap-like) */
{$this->generateBootstrapSpacingUtilities($ns)}

/* Responsive - md prefix */
@media (min-width: 768px) {
  .md-{$ns}-col-6 { flex: 0 0 50%; max-width: 50%; }
  .md-{$ns}-col-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
  .md-{$ns}-col-3 { flex: 0 0 25%; max-width: 25%; }
  .md-{$ns}-hidden { display: none; }
  .md-{$ns}-block { display: block; }
  .md-{$ns}-flex { display: flex; }
}

@media (max-width: 767px) {
  .{$ns}-col-6, .{$ns}-col-4, .{$ns}-col-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  .{$ns}-grid-2, .{$ns}-grid-3, .{$ns}-grid-4 {
    grid-template-columns: 1fr;
  }
  .{$ns}-hero__title {
    font-size: 2rem;
  }
}

{$this->generateTextUtilities($ns)}
{$this->generateDisplayUtilities($ns)}
CSS;
    }

    /**
     * Generate Bootstrap-like spacing utilities.
     */
    private function generateBootstrapSpacingUtilities(string $namespace): string
    {
        $css = "/* Spacing Utilities */\n";
        $sizes = [
            '0' => '0',
            '1' => '0.25rem',
            '2' => '0.5rem',
            '3' => '1rem',
            '4' => '1.5rem',
            '5' => '3rem',
        ];

        foreach (['m' => 'margin', 'p' => 'padding'] as $prefix => $property) {
            foreach ($sizes as $size => $value) {
                $css .= ".{$namespace}-{$prefix}-{$size} { {$property}: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}t-{$size} { {$property}-top: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}r-{$size} { {$property}-right: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}b-{$size} { {$property}-bottom: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}l-{$size} { {$property}-left: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}x-{$size} { {$property}-left: {$value}; {$property}-right: {$value}; }\n";
                $css .= ".{$namespace}-{$prefix}y-{$size} { {$property}-top: {$value}; {$property}-bottom: {$value}; }\n";
            }
        }

        return $css;
    }
}
