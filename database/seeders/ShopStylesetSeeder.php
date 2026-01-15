<?php

namespace Database\Seeders;

use App\Models\PrestaShopShop;
use App\Models\ShopStyleset;
use Illuminate\Database\Seeder;

/**
 * Seeder for Visual Description Editor shop stylesets
 *
 * Creates default CSS stylesets for each shop based on their branding.
 * Stylesets define CSS namespaces and variables for consistent styling.
 */
class ShopStylesetSeeder extends Seeder
{
    public function run(): void
    {
        $shops = PrestaShopShop::all();

        if ($shops->isEmpty()) {
            $this->command->warn('No PrestaShop shops found. Skipping styleset seeding.');
            return;
        }

        $count = 0;
        foreach ($shops as $shop) {
            $styleset = $this->getStylesetForShop($shop);

            if ($styleset) {
                ShopStyleset::updateOrCreate(
                    ['shop_id' => $shop->id, 'name' => $styleset['name']],
                    $styleset
                );
                $count++;
            }
        }

        $this->command->info("Created/updated {$count} shop stylesets");
    }

    private function getStylesetForShop(PrestaShopShop $shop): ?array
    {
        // Determine shop type from name or URL
        $shopName = strtolower($shop->name);
        $shopUrl = strtolower($shop->url ?? '');

        // Match shop to styleset configuration
        if (str_contains($shopName, 'b2b') || str_contains($shopUrl, 'b2b')) {
            return $this->getB2BStyleset($shop);
        }

        if (str_contains($shopName, 'kayo') || str_contains($shopUrl, 'kayo')) {
            return $this->getKayoStyleset($shop);
        }

        if (str_contains($shopName, 'ycf') || str_contains($shopUrl, 'ycf')) {
            return $this->getYcfStyleset($shop);
        }

        if (str_contains($shopName, 'pitgang') || str_contains($shopUrl, 'pitgang')) {
            return $this->getPitgangStyleset($shop);
        }

        if (str_contains($shopName, 'mrf') || str_contains($shopUrl, 'pitbikemrf') || str_contains($shopUrl, 'mrf')) {
            return $this->getMrfStyleset($shop);
        }

        // Default styleset for unmatched shops
        return $this->getDefaultStyleset($shop);
    }

    private function getB2BStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'B2B Default',
            'css_namespace' => 'pd-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#1e40af',
                'secondary-color' => '#64748b',
                'accent-color' => '#f59e0b',
                'text-color' => '#1f2937',
                'background-color' => '#ffffff',
                'font-family' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'heading-font' => '"Inter", system-ui, sans-serif',
                'spacing-unit' => '1rem',
                'border-radius' => '0.375rem',
            ],
            'css_content' => $this->getB2BCss(),
        ];
    }

    private function getKayoStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'KAYO Motorsport',
            'css_namespace' => 'pd-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#f97316',
                'secondary-color' => '#1f2937',
                'accent-color' => '#22c55e',
                'text-color' => '#1f2937',
                'background-color' => '#ffffff',
                'font-family' => '"Roboto", system-ui, sans-serif',
                'heading-font' => '"Oswald", "Roboto", sans-serif',
                'spacing-unit' => '1rem',
                'border-radius' => '0.25rem',
            ],
            'css_content' => $this->getKayoCss(),
        ];
    }

    private function getYcfStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'YCF Racing',
            'css_namespace' => 'pd-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#dc2626',
                'secondary-color' => '#1f2937',
                'accent-color' => '#fbbf24',
                'text-color' => '#1f2937',
                'background-color' => '#ffffff',
                'font-family' => '"Montserrat", system-ui, sans-serif',
                'heading-font' => '"Bebas Neue", "Montserrat", sans-serif',
                'spacing-unit' => '1rem',
                'border-radius' => '0',
            ],
            'css_content' => $this->getYcfCss(),
        ];
    }

    private function getPitgangStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'Pitgang Style',
            'css_namespace' => 'blok-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#000000',
                'secondary-color' => '#4b5563',
                'accent-color' => '#ef4444',
                'text-color' => '#111827',
                'background-color' => '#ffffff',
                'font-family' => '"Barlow", system-ui, sans-serif',
                'heading-font' => '"Barlow Condensed", "Barlow", sans-serif',
                'spacing-unit' => '1rem',
                'border-radius' => '0',
            ],
            'css_content' => $this->getPitgangCss(),
        ];
    }

    private function getMrfStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'MRF Pitbike',
            'css_namespace' => 'pd-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#e11d48',
                'secondary-color' => '#374151',
                'accent-color' => '#f59e0b',
                'text-color' => '#111827',
                'background-color' => '#ffffff',
                'font-family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
                'heading-font' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
                'spacing-unit' => '1rem',
                'border-radius' => '0',
            ],
            'css_content' => $this->getMrfCss(),
        ];
    }

    private function getDefaultStyleset(PrestaShopShop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'name' => 'Default Style',
            'css_namespace' => 'pd-',
            'is_active' => true,
            'variables_json' => [
                'primary-color' => '#2563eb',
                'secondary-color' => '#64748b',
                'accent-color' => '#f59e0b',
                'text-color' => '#1f2937',
                'background-color' => '#ffffff',
                'font-family' => 'system-ui, -apple-system, sans-serif',
                'heading-font' => 'inherit',
                'spacing-unit' => '1rem',
                'border-radius' => '0.375rem',
            ],
            'css_content' => $this->getDefaultCss(),
        ];
    }

    // ===================
    // CSS TEMPLATES
    // ===================

    private function getDefaultCss(): string
    {
        return <<<'CSS'
/* Product Description Base Styles */
.pd-wrapper {
    font-family: var(--pd-font-family);
    color: var(--pd-text-color);
    line-height: 1.6;
}

.pd-heading {
    font-family: var(--pd-heading-font);
    color: var(--pd-text-color);
    margin-bottom: var(--pd-spacing-unit);
}

.pd-heading h1 { font-size: 2.5rem; font-weight: 700; }
.pd-heading h2 { font-size: 2rem; font-weight: 600; }
.pd-heading h3 { font-size: 1.5rem; font-weight: 600; }
.pd-heading h4 { font-size: 1.25rem; font-weight: 500; }

.pd-text {
    margin-bottom: var(--pd-spacing-unit);
}

.pd-text p {
    margin-bottom: 1em;
}

.pd-image {
    border-radius: var(--pd-border-radius);
    overflow: hidden;
}

.pd-image img {
    max-width: 100%;
    height: auto;
    display: block;
}

.pd-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--pd-border-radius);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pd-button--primary {
    background: var(--pd-primary-color);
    color: #fff;
}

.pd-button--primary:hover {
    filter: brightness(1.1);
}

.pd-button--secondary {
    background: var(--pd-secondary-color);
    color: #fff;
}

.pd-button--outline {
    background: transparent;
    border: 2px solid var(--pd-primary-color);
    color: var(--pd-primary-color);
}

.pd-two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: calc(var(--pd-spacing-unit) * 2);
}

.pd-three-column {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: calc(var(--pd-spacing-unit) * 2);
}

.pd-spec-table {
    width: 100%;
    border-collapse: collapse;
}

.pd-spec-table th,
.pd-spec-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.pd-spec-table th {
    font-weight: 600;
    background: #f9fafb;
}

.pd-spec-table tr:nth-child(even) {
    background: #f9fafb;
}

.pd-merit-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pd-merit-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.pd-merit-icon {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    color: var(--pd-accent-color);
}

@media (max-width: 768px) {
    .pd-two-column,
    .pd-three-column {
        grid-template-columns: 1fr;
    }
}
CSS;
    }

    private function getB2BCss(): string
    {
        return $this->getDefaultCss() . <<<'CSS'

/* B2B Specific Styles */
.pd-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.pd-heading h1 {
    border-bottom: 3px solid var(--pd-primary-color);
    padding-bottom: 0.5rem;
}

.pd-spec-table {
    border: 1px solid #e5e7eb;
}

.pd-spec-table th {
    background: var(--pd-primary-color);
    color: #fff;
}
CSS;
    }

    private function getKayoCss(): string
    {
        return $this->getDefaultCss() . <<<'CSS'

/* KAYO Motorsport Styles */
.pd-wrapper {
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
}

.pd-heading h1,
.pd-heading h2 {
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.pd-heading h1::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: var(--pd-primary-color);
    margin-top: 0.5rem;
}

.pd-button--primary {
    background: linear-gradient(135deg, var(--pd-primary-color) 0%, #ea580c 100%);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.pd-hero {
    position: relative;
    overflow: hidden;
}

.pd-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0,0,0,0.7) 0%, transparent 100%);
}

.pd-parallax {
    background-attachment: fixed;
    background-size: cover;
    background-position: center;
}
CSS;
    }

    private function getYcfCss(): string
    {
        return $this->getDefaultCss() . <<<'CSS'

/* YCF Racing Styles */
.pd-wrapper {
    position: relative;
}

.pd-heading h1,
.pd-heading h2 {
    text-transform: uppercase;
    font-weight: 400;
    letter-spacing: 0.15em;
}

.pd-heading h1 {
    font-size: 3rem;
    color: var(--pd-primary-color);
}

.pd-button--primary {
    background: var(--pd-primary-color);
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-weight: 400;
}

.pd-dna-section {
    background: #111;
    color: #fff;
    padding: 4rem 2rem;
}

.pd-dna-title {
    color: var(--pd-accent-color);
}

.pd-feature-card {
    border-left: 4px solid var(--pd-primary-color);
    padding-left: 1.5rem;
}
CSS;
    }

    private function getPitgangCss(): string
    {
        return <<<'CSS'
/* Pitgang Block Styles */
.blok-wrapper {
    font-family: var(--blok-font-family);
    color: var(--blok-text-color);
    line-height: 1.6;
}

.blok-naglowek {
    font-family: var(--blok-heading-font);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.blok-naglowek h1 { font-size: 2.5rem; font-weight: 700; }
.blok-naglowek h2 { font-size: 2rem; font-weight: 600; }
.blok-naglowek h3 { font-size: 1.5rem; font-weight: 600; }

.blok-tekst {
    margin-bottom: var(--blok-spacing-unit);
}

.blok-obraz {
    position: relative;
}

.blok-obraz img {
    max-width: 100%;
    height: auto;
    display: block;
}

.blok-przycisk {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid currentColor;
}

.blok-przycisk--glowny {
    background: var(--blok-primary-color);
    color: #fff;
    border-color: var(--blok-primary-color);
}

.blok-przycisk--akcent {
    background: var(--blok-accent-color);
    color: #fff;
    border-color: var(--blok-accent-color);
}

.blok-dwa-kolumny {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: calc(var(--blok-spacing-unit) * 2);
}

.blok-trzy-kolumny {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: calc(var(--blok-spacing-unit) * 2);
}

.blok-tabela-spec {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid var(--blok-primary-color);
}

.blok-tabela-spec th,
.blok-tabela-spec td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.blok-tabela-spec th {
    font-weight: 700;
    background: var(--blok-primary-color);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.blok-lista-zalet {
    list-style: none;
    padding: 0;
    margin: 0;
}

.blok-zaleta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-left: 1rem;
    border-left: 3px solid var(--blok-accent-color);
}

/* Skull stamp decoration */
.blok-stempel {
    position: relative;
}

.blok-stempel::after {
    content: '';
    position: absolute;
    width: 80px;
    height: 80px;
    background-image: url('data:image/svg+xml,...'); /* Skull SVG */
    opacity: 0.1;
    transform: rotate(-15deg);
}

@media (max-width: 768px) {
    .blok-dwa-kolumny,
    .blok-trzy-kolumny {
        grid-template-columns: 1fr;
    }
}
CSS;
    }

    private function getMrfCss(): string
    {
        return $this->getDefaultCss() . <<<'CSS'

/* MRF Pitbike Styles - Grid Utilities */
.pd-wrapper {
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
}

.pd-block {
    position: relative;
}

.pd-block-contained {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.pd-joy-block {
    padding: 2rem;
    background: var(--pd-background-color);
}

.pd-baner {
    position: relative;
    overflow: hidden;
}

.pd-container {
    max-width: 1200px;
    margin: 0 auto;
}

.pd-base-grid {
    display: grid;
    gap: 1rem;
}

/* Grid System */
.grid {
    display: grid;
}

.grid-row {
    display: grid;
    grid-auto-flow: row;
}

.grid-flow-column {
    grid-auto-flow: column;
}

.grid-flow-row {
    grid-auto-flow: row;
}

.row-start { grid-row-start: 1; }
.row-end { grid-row-end: -1; }
.column-start { grid-column-start: 1; }
.column-end { grid-column-end: -1; }

.colspan-2 { grid-column: span 2; }
.rowspan-2 { grid-row: span 2; }

/* Layout Utilities */
.inverted-colors {
    background: var(--pd-text-color);
    color: var(--pd-background-color);
}

.bg-light-gray {
    background: #f3f4f6;
}

.aspect-ratio-1 {
    aspect-ratio: 1;
}

.zindex {
    z-index: 1;
}

.img-cover {
    object-fit: cover;
    width: 100%;
    height: 100%;
}

/* Spacing System (Bootstrap-like) */
.py-3 { padding-top: 1rem; padding-bottom: 1rem; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.py-5 { padding-top: 3rem; padding-bottom: 3rem; }
.py-0 { padding-top: 0; padding-bottom: 0; }

.px-3 { padding-left: 1rem; padding-right: 1rem; }
.px-4 { padding-left: 1.5rem; padding-right: 1.5rem; }

.mx-3 { margin-left: 1rem; margin-right: 1rem; }
.mx-auto { margin-left: auto; margin-right: auto; }

.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.mt-5 { margin-top: 3rem; }

.mb-4 { margin-bottom: 1.5rem; }
.mb-5 { margin-bottom: 3rem; }

.pt-2 { padding-top: 0.5rem; }
.pt-4 { padding-top: 1.5rem; }

.pb-0 { padding-bottom: 0; }
.pb-3 { padding-bottom: 1rem; }
.pb-4 { padding-bottom: 1.5rem; }

.p-0 { padding: 0; }
.m-0 { margin: 0; }

.my-5 { margin-top: 3rem; margin-bottom: 3rem; }

/* Flexbox Utilities */
.flex-col-reverse {
    display: flex;
    flex-direction: column-reverse;
}

.align-self-end {
    align-self: flex-end;
}

.align-self-center {
    align-self: center;
}

/* Typography */
.text-center { text-align: center; }
.text-end { text-align: right; }
.text-transform-none { text-transform: none; }
.font-weight-bold { font-weight: 700; }
.arial { font-family: Arial, sans-serif; }

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.125rem;
}

.btn-outline-dark {
    background: transparent;
    border-color: var(--pd-text-color);
    color: var(--pd-text-color);
}

.btn-outline-dark:hover {
    background: var(--pd-text-color);
    color: var(--pd-background-color);
}

/* Visual Elements */
.icon-start {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.border-bottom {
    border-bottom: 1px solid #e5e7eb;
}

.list-none {
    list-style: none;
    padding: 0;
    margin: 0;
}

.block { display: block; }
.inline { display: inline; }
.w-max-content { width: max-content; }

.lc-first::first-letter {
    font-size: 1.5em;
    font-weight: bold;
}

/* Responsive Modifiers */
@media (min-width: 768px) {
    .md-column-start { grid-column-start: 1; }
    .md-rowspan-2 { grid-row: span 2; }
    .py-md-5 { padding-top: 3rem; padding-bottom: 3rem; }
    .pl-md-5 { padding-left: 3rem; }
    .pt-md-0 { padding-top: 0; }
    .text-md-left { text-align: left; }
    .md-bg { background: #f3f4f6; }
}
CSS;
    }
}
