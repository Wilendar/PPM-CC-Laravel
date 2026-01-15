<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset\ShopStylesets;

/**
 * Pitgang Shop Styleset Definition.
 *
 * Urban/Bold design:
 * - Namespace: blok-* (DIFFERENT from others!)
 * - Colors: Black, white, bold accents
 * - Typography: Bold, uppercase, caps
 * - Features: Skull stamps, aggressive styling, high contrast
 */
class PitgangStyleset extends AbstractShopStyleset
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Pitgang Urban Bold';
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return 'blok';
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'pitgang';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return [
            // Colors - High contrast, bold
            'primary-color' => '#000000',
            'secondary-color' => '#ffffff',
            'accent-color' => '#eab308',
            'text-color' => '#000000',
            'text-muted' => '#4b5563',
            'text-light' => '#ffffff',
            'background-color' => '#ffffff',
            'background-alt' => '#f3f4f6',
            'background-dark' => '#000000',

            // Typography - Bold, aggressive
            'font-family' => '"Oswald", "Impact", sans-serif',
            'heading-font' => '"Anton", "Impact", sans-serif',
            'font-size-base' => '1rem',
            'font-size-sm' => '0.875rem',
            'font-size-lg' => '1.25rem',
            'font-size-xl' => '1.75rem',
            'line-height' => '1.4',

            // Spacing - Compact, impactful
            'spacing-unit' => '1rem',
            'spacing-xs' => '0.25rem',
            'spacing-sm' => '0.5rem',
            'spacing-md' => '1rem',
            'spacing-lg' => '1.5rem',
            'spacing-xl' => '2.5rem',

            // Borders & Effects - Sharp
            'border-color' => '#000000',
            'border-radius' => '0',
            'border-radius-sm' => '0',
            'border-radius-lg' => '0',
            'shadow' => '8px 8px 0 0 rgba(0, 0, 0, 1)',
            'shadow-sm' => '4px 4px 0 0 rgba(0, 0, 0, 1)',
            'shadow-lg' => '12px 12px 0 0 rgba(0, 0, 0, 1)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomCss(): string
    {
        $ns = $this->getNamespace();

        return <<<CSS
/* Pitgang Urban Bold Theme */

/* Hero Banner - Full Impact */
.{$ns}-hero {
  position: relative;
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl) var(--{$ns}-spacing-md);
  border: 4px solid var(--{$ns}-text-light);
}

.{$ns}-hero__title {
  font-family: var(--{$ns}-heading-font);
  font-size: 4rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: var(--{$ns}-spacing-md);
  line-height: 1;
}

.{$ns}-hero__subtitle {
  font-family: var(--{$ns}-font-family);
  font-size: var(--{$ns}-font-size-lg);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Skull Stamp Effect */
.{$ns}-stamp {
  display: inline-block;
  padding: var(--{$ns}-spacing-sm) var(--{$ns}-spacing-md);
  background: var(--{$ns}-accent-color);
  color: var(--{$ns}-primary-color);
  font-family: var(--{$ns}-heading-font);
  text-transform: uppercase;
  transform: rotate(-3deg);
  box-shadow: var(--{$ns}-shadow-sm);
}

/* Feature Cards - Brutalist */
.{$ns}-feature-card {
  background: var(--{$ns}-background-color);
  border: 3px solid var(--{$ns}-primary-color);
  padding: var(--{$ns}-spacing-lg);
  transition: all 0.2s ease;
}

.{$ns}-feature-card:hover {
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  box-shadow: var(--{$ns}-shadow);
  transform: translate(-4px, -4px);
}

.{$ns}-feature-card__icon {
  font-size: 3rem;
  margin-bottom: var(--{$ns}-spacing-md);
}

.{$ns}-feature-card__title {
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-xl);
  text-transform: uppercase;
  margin-bottom: var(--{$ns}-spacing-sm);
}

.{$ns}-feature-card__text {
  font-family: var(--{$ns}-font-family);
  line-height: var(--{$ns}-line-height);
}

/* Inverted Section */
.{$ns}-inverted {
  background: var(--{$ns}-background-dark);
  color: var(--{$ns}-text-light);
  padding: var(--{$ns}-spacing-xl);
}

.{$ns}-inverted a {
  color: var(--{$ns}-accent-color);
}

/* Specification Table - Bold */
.{$ns}-spec-table {
  width: 100%;
  border-collapse: collapse;
  border: 3px solid var(--{$ns}-primary-color);
}

.{$ns}-spec-table th {
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  font-family: var(--{$ns}-heading-font);
  text-transform: uppercase;
  padding: var(--{$ns}-spacing-md);
  text-align: left;
}

.{$ns}-spec-table td {
  padding: var(--{$ns}-spacing-md);
  border-bottom: 2px solid var(--{$ns}-primary-color);
}

.{$ns}-spec-table tr:last-child td {
  border-bottom: none;
}

/* CTA Button - Bold */
.{$ns}-cta {
  display: inline-block;
  padding: var(--{$ns}-spacing-md) var(--{$ns}-spacing-xl);
  background: var(--{$ns}-primary-color);
  color: var(--{$ns}-text-light);
  border: 3px solid var(--{$ns}-primary-color);
  font-family: var(--{$ns}-heading-font);
  font-size: var(--{$ns}-font-size-lg);
  text-transform: uppercase;
  text-decoration: none;
  letter-spacing: 0.05em;
  transition: all 0.2s ease;
}

.{$ns}-cta:hover {
  background: var(--{$ns}-text-light);
  color: var(--{$ns}-primary-color);
  box-shadow: var(--{$ns}-shadow);
  transform: translate(-4px, -4px);
}

.{$ns}-cta--accent {
  background: var(--{$ns}-accent-color);
  border-color: var(--{$ns}-accent-color);
  color: var(--{$ns}-primary-color);
}

/* Diagonal Stripe Background */
.{$ns}-stripes {
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 10px,
    var(--{$ns}-accent-color) 10px,
    var(--{$ns}-accent-color) 20px
  );
  padding: var(--{$ns}-spacing-sm);
}

/* Grid - Compact */
.{$ns}-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--{$ns}-spacing-md); }
.{$ns}-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--{$ns}-spacing-md); }
.{$ns}-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--{$ns}-spacing-md); }

@media (max-width: 768px) {
  .{$ns}-grid-2, .{$ns}-grid-3, .{$ns}-grid-4 {
    grid-template-columns: 1fr;
  }
  .{$ns}-hero__title {
    font-size: 2.5rem;
  }
}

/* Text Utilities */
.{$ns}-text-left { text-align: left; }
.{$ns}-text-center { text-align: center; }
.{$ns}-text-right { text-align: right; }
.{$ns}-uppercase { text-transform: uppercase; }
.{$ns}-font-bold { font-weight: 700; }

/* Display Utilities */
.{$ns}-hidden { display: none; }
.{$ns}-block { display: block; }
.{$ns}-flex { display: flex; }
.{$ns}-items-center { align-items: center; }
.{$ns}-justify-center { justify-content: center; }
.{$ns}-justify-between { justify-content: space-between; }
CSS;
    }
}
