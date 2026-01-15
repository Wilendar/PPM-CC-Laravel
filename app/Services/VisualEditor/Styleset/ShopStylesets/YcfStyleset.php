<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

/**
 * YCF Shop Styleset Definition.
 *
 * Racing/Premium design:
 * - Namespace: pd-* + custom
 * - Colors: Racing red (#dc2626), white, premium accents
 * - Typography: Serif accents for headings, premium feel
 * - Features: Premium look, racing feel, elegant spacing
 */
class YcfStyleset extends AbstractShopStyleset
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'YCF Racing Premium';
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
        return 'ycf';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return [
            // Colors - YCF brand (racing red)
            'primary-color' => '#dc2626',
            'secondary-color' => '#1e293b',
            'accent-color' => '#fbbf24',
            'text-color' => '#18181b',
            'text-muted' => '#52525b',
            'text-light' => '#ffffff',
            'background-color' => '#ffffff',
            'background-alt' => '#fafafa',
            'background-dark' => '#18181b',

            // Typography - Premium with serif accents
            'font-family' => '"Inter", "Helvetica Neue", sans-serif',
            'heading-font' => '"Playfair Display", Georgia, serif',
            'font-size-base' => '1rem',
            'font-size-sm' => '0.875rem',
            'font-size-lg' => '1.125rem',
            'font-size-xl' => '1.5rem',
            'line-height' => '1.7',

            // Spacing - Generous
            'spacing-unit' => '1rem',
            'spacing-xs' => '0.25rem',
            'spacing-sm' => '0.5rem',
            'spacing-md' => '1rem',
            'spacing-lg' => '2rem',
            'spacing-xl' => '4rem',

            // Borders & Effects - Elegant
            'border-color' => '#e4e4e7',
            'border-radius' => '0.375rem',
            'border-radius-sm' => '0.25rem',
            'border-radius-lg' => '0.75rem',
            'shadow' => '0 4px 12px -2px rgb(0 0 0 / 0.08)',
            'shadow-sm' => '0 1px 3px 0 rgb(0 0 0 / 0.05)',
            'shadow-lg' => '0 12px 24px -6px rgb(0 0 0 / 0.12)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomCss(): string
    {
        $ns = $this->getNamespace();

        return <<<CSS
/* YCF Racing Premium Theme */

/* Hero Banner - Elegant, Full-impact */
.{$ns}-hero {
  position: relative;
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl) var(--{$ns}-spacing-lg);
  text-align: center;
}

.{$ns}-hero__eyebrow {
  font-size: var(--{$ns}-font-size-sm);
  text-transform: uppercase;
  letter-spacing: 0.2em;
  color: var(--{$ns}-primary-color);
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-hero__title {
  font-family: var(--{$ns}-heading-font);
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: var(--{$ns}-spacing-md);
  line-height: 1.1;
}

.{$ns}-hero__subtitle {
  font-size: var(--{$ns}-font-size-lg);
  opacity: 0.85;
  max-width: 600px;
  margin: 0 auto var(--{$ns}-spacing-lg);
}

/* Premium Divider */
.{$ns}-divider {
  display: flex;
  align-items: center;
  gap: var(--{$ns}-spacing-md);
  margin: var(--{$ns}-spacing-xl) 0;
}

.{$ns}-divider::before,
.{$ns}-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--{$ns}-border-color);
}

.{$ns}-divider__icon {
  color: var(--{$ns}-primary-color);
  font-size: 1.5rem;
}

/* Feature Cards - Premium */
.{$ns}-feature-card {
  background: var(--{$ns}-background-color);
  border-radius: var(--{$ns}-border-radius-lg);
  padding: var(--{$ns}-spacing-lg);
  box-shadow: var(--{$ns}-shadow);
  transition: all 0.3s ease;
}

.{$ns}-feature-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--{$ns}-shadow-lg);
}

.{$ns}-feature-card__number {
  font-family: var(--{$ns}-heading-font);
  font-size: 3rem;
  color: var(--{$ns}-primary-color);
  line-height: 1;
  margin-bottom: var(--{$ns}-spacing-sm);
}

.{$ns}-feature-card__title {
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-xl);
  color: var(--{$ns}-text-color);
  margin-bottom: var(--{$ns}-spacing-sm);
}

.{$ns}-feature-card__text {
  color: var(--{$ns}-text-muted);
  line-height: var(--{$ns}-line-height);
}

/* Specification Table - Elegant */
.{$ns}-spec-table {
  width: 100%;
  border-collapse: collapse;
}

.{$ns}-spec-table th {
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-lg);
  color: var(--{$ns}-text-color);
  text-align: left;
  padding: var(--{$ns}-spacing-md);
  border-bottom: 2px solid var(--{$ns}-primary-color);
}

.{$ns}-spec-table td {
  padding: var(--{$ns}-spacing-md);
  border-bottom: 1px solid var(--{$ns}-border-color);
  color: var(--{$ns}-text-muted);
}

.{$ns}-spec-table td:first-child {
  font-weight: 500;
  color: var(--{$ns}-text-color);
}

/* CTA Button - Premium */
.{$ns}-cta {
  display: inline-flex;
  align-items: center;
  gap: var(--{$ns}-spacing-sm);
  padding: var(--{$ns}-spacing-md) var(--{$ns}-spacing-xl);
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border: none;
  border-radius: var(--{$ns}-border-radius);
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.{$ns}-cta:hover {
  background: #b91c1c;
}

.{$ns}-cta--outline {
  background: transparent;
  border: 2px solid var(--{$ns}-text-color);
  color: var(--{$ns}-text-color);
}

.{$ns}-cta--outline:hover {
  background: var(--{$ns}-text-color);
  color: var(--{$ns}-text-light);
}

/* Quote Block */
.{$ns}-quote {
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-xl);
  font-style: italic;
  color: var(--{$ns}-text-color);
  border-left: 4px solid var(--{$ns}-primary-color);
  padding-left: var(--{$ns}-spacing-lg);
  margin: var(--{$ns}-spacing-lg) 0;
}

.{$ns}-quote__author {
  font-family: var(--{$ns}-font-family);
  font-size: var(--{$ns}-font-size-sm);
  font-style: normal;
  color: var(--{$ns}-text-muted);
  margin-top: var(--{$ns}-spacing-md);
}

/* Grid */
.{$ns}-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--{$ns}-spacing-xl); }
.{$ns}-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--{$ns}-spacing-lg); }

@media (max-width: 768px) {
  .{$ns}-grid-2, .{$ns}-grid-3 {
    grid-template-columns: 1fr;
  }
  .{$ns}-hero__title {
    font-size: 2.5rem;
  }
}

{$this->generateTextUtilities($ns)}
{$this->generateDisplayUtilities($ns)}
CSS;
    }
}
