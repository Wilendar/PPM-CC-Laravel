<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

/**
 * KAYO Shop Styleset Definition.
 *
 * Motocross/Racing design:
 * - Namespace: pd-* (extended)
 * - Colors: Brand orange (#f97316), black, dynamic accents
 * - Typography: Bold, dynamic
 * - Features: Parallax support, sliders, aggressive styling
 */
class KayoStyleset extends AbstractShopStyleset
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'KAYO Racing';
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
        return 'kayo';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return [
            // Colors - KAYO brand
            'primary-color' => '#f97316',
            'secondary-color' => '#0f0f0f',
            'accent-color' => '#facc15',
            'text-color' => '#1a1a1a',
            'text-muted' => '#6b7280',
            'text-light' => '#ffffff',
            'background-color' => '#ffffff',
            'background-alt' => '#f5f5f5',
            'background-dark' => '#0f0f0f',

            // Typography - Bold, dynamic
            'font-family' => '"Roboto Condensed", "Arial Narrow", sans-serif',
            'heading-font' => '"Russo One", "Impact", sans-serif',
            'font-size-base' => '1rem',
            'font-size-sm' => '0.875rem',
            'font-size-lg' => '1.25rem',
            'font-size-xl' => '1.5rem',
            'line-height' => '1.5',

            // Spacing
            'spacing-unit' => '1rem',
            'spacing-xs' => '0.25rem',
            'spacing-sm' => '0.5rem',
            'spacing-md' => '1rem',
            'spacing-lg' => '2rem',
            'spacing-xl' => '3rem',

            // Borders & Effects - Sharp, aggressive
            'border-color' => '#e5e5e5',
            'border-radius' => '0.25rem',
            'border-radius-sm' => '0.125rem',
            'border-radius-lg' => '0.5rem',
            'shadow' => '0 8px 16px -4px rgb(0 0 0 / 0.2)',
            'shadow-sm' => '0 2px 4px 0 rgb(0 0 0 / 0.1)',
            'shadow-lg' => '0 20px 25px -5px rgb(0 0 0 / 0.25)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomCss(): string
    {
        $ns = $this->getNamespace();

        return <<<CSS
/* KAYO Racing Theme */

/* Hero Banner - Dynamic, Full-width */
.{$ns}-hero {
  position: relative;
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl) var(--{$ns}-spacing-md);
  overflow: hidden;
}

.{$ns}-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, var(--{$ns}-primary-color) 0%, transparent 70%);
  opacity: 0.3;
}

.{$ns}-hero__content {
  position: relative;
  z-index: 1;
}

.{$ns}-hero__title {
  font-family: var(--{$ns}-heading-font);
  font-size: 3rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-hero__title span {
  color: var(--{$ns}-primary-color);
}

/* Racing Stripe Accent */
.{$ns}-racing-stripe {
  position: relative;
  padding-left: var(--{$ns}-spacing-md);
}

.{$ns}-racing-stripe::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: var(--{$ns}-primary-color);
}

/* Feature Cards - Bold */
.{$ns}-feature-card {
  background: var(--{$ns}-background-color);
  border: 2px solid var(--{$ns}-border-color);
  border-radius: var(--{$ns}-border-radius);
  padding: var(--{$ns}-spacing-lg);
  transition: all 0.3s ease;
}

.{$ns}-feature-card:hover {
  border-color: var(--{$ns}-primary-color);
  transform: translateY(-4px);
  box-shadow: var(--{$ns}-shadow);
}

.{$ns}-feature-card__icon {
  width: 56px;
  height: 56px;
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border-radius: var(--{$ns}-border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--{$ns}-spacing-md);
  font-size: 1.5rem;
}

.{$ns}-feature-card__title {
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-xl);
  text-transform: uppercase;
  color: var(--{$ns}-text-color);
  margin-bottom: var(--{$ns}-spacing-sm);
}

/* Specification Table - Racing style */
.{$ns}-spec-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--{$ns}-background-color);
}

.{$ns}-spec-table th {
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  font-family: var(--{$ns}-heading-font);
  text-transform: uppercase;
  padding: var(--{$ns}-spacing-md);
}

.{$ns}-spec-table td {
  padding: var(--{$ns}-spacing-sm) var(--{$ns}-spacing-md);
  border-bottom: 1px solid var(--{$ns}-border-color);
}

.{$ns}-spec-table tr:nth-child(even) {
  background: var(--{$ns}-background-alt);
}

/* CTA Button - Bold, Action */
.{$ns}-cta {
  display: inline-flex;
  align-items: center;
  gap: var(--{$ns}-spacing-sm);
  padding: var(--{$ns}-spacing-md) var(--{$ns}-spacing-xl);
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border: none;
  border-radius: var(--{$ns}-border-radius);
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-lg);
  text-transform: uppercase;
  text-decoration: none;
  transition: all 0.3s ease;
}

.{$ns}-cta:hover {
  background: var(--{$ns}-accent-color);
  transform: scale(1.05);
}

/* Parallax Section */
.{$ns}-parallax {
  position: relative;
  background-attachment: fixed;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.{$ns}-parallax__overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.{$ns}-parallax__content {
  position: relative;
  z-index: 1;
  text-align: center;
  color: var(--{$ns}-text-light);
}

/* Grid Layout */
.{$ns}-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--{$ns}-spacing-lg); }
.{$ns}-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--{$ns}-spacing-lg); }
.{$ns}-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--{$ns}-spacing-lg); }

@media (max-width: 768px) {
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
}
