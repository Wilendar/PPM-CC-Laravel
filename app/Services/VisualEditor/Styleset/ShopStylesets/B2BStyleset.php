<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

/**
 * B2B Shop Styleset Definition.
 *
 * Professional, corporate design:
 * - Namespace: pd-*
 * - Colors: Corporate blue (#2563eb), gray tones
 * - Typography: Clean, system fonts
 * - Style: Minimalist, readable, professional
 */
class B2BStyleset extends AbstractShopStyleset
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'B2B Professional';
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
        return 'b2b';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return [
            // Colors
            'primary-color' => '#2563eb',
            'secondary-color' => '#64748b',
            'accent-color' => '#f59e0b',
            'text-color' => '#1e293b',
            'text-muted' => '#64748b',
            'text-light' => '#ffffff',
            'background-color' => '#ffffff',
            'background-alt' => '#f8fafc',
            'background-dark' => '#1e293b',

            // Typography
            'font-family' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'heading-font' => 'inherit',
            'font-size-base' => '1rem',
            'font-size-sm' => '0.875rem',
            'font-size-lg' => '1.125rem',
            'font-size-xl' => '1.25rem',
            'line-height' => '1.6',

            // Spacing
            'spacing-unit' => '1rem',
            'spacing-xs' => '0.25rem',
            'spacing-sm' => '0.5rem',
            'spacing-md' => '1rem',
            'spacing-lg' => '1.5rem',
            'spacing-xl' => '2rem',

            // Borders & Effects
            'border-color' => '#e2e8f0',
            'border-radius' => '0.5rem',
            'border-radius-sm' => '0.25rem',
            'border-radius-lg' => '0.75rem',
            'shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1)',
            'shadow-sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            'shadow-lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomCss(): string
    {
        $ns = $this->getNamespace();

        return <<<CSS
/* B2B Professional Theme */

/* Hero Banner */
.{$ns}-hero {
  position: relative;
  background: linear-gradient(135deg, var(--{$ns}-primary-color) 0%, var(--{$ns}-secondary-color) 100%);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl) var(--{$ns}-spacing-md);
  border-radius: var(--{$ns}-border-radius-lg);
  overflow: hidden;
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

/* Feature Cards */
.{$ns}-feature-card {
  background: var(--{$ns}-background-color);
  border: 1px solid var(--{$ns}-border-color);
  border-radius: var(--{$ns}-border-radius);
  padding: var(--{$ns}-spacing-lg);
  transition: box-shadow 0.2s ease;
}

.{$ns}-feature-card:hover {
  box-shadow: var(--{$ns}-shadow);
}

.{$ns}-feature-card__icon {
  width: 48px;
  height: 48px;
  background: var(--{$ns}-background-alt);
  border-radius: var(--{$ns}-border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--{$ns}-spacing-md);
  color: var(--{$ns}-primary-color);
}

.{$ns}-feature-card__title {
  font-size: var(--{$ns}-font-size-lg);
  font-weight: 600;
  color: var(--{$ns}-text-color);
  margin-bottom: var(--{$ns}-spacing-sm);
}

.{$ns}-feature-card__text {
  color: var(--{$ns}-text-muted);
  line-height: var(--{$ns}-line-height);
}

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
  color: var(--{$ns}-text-color);
}

.{$ns}-spec-table tr:last-child td {
  border-bottom: none;
}

/* Two Column Layout */
.{$ns}-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--{$ns}-spacing-lg);
}

@media (max-width: 768px) {
  .{$ns}-two-col {
    grid-template-columns: 1fr;
  }
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
  transition: opacity 0.2s;
}

.{$ns}-cta:hover {
  opacity: 0.9;
}

.{$ns}-cta--secondary {
  background: transparent;
  border: 2px solid var(--{$ns}-primary-color);
  color: var(--{$ns}-primary-color);
}

{$this->generateTextUtilities($ns)}
{$this->generateDisplayUtilities($ns)}
CSS;
    }
}
