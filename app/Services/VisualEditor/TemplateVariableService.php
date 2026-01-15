<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\Product;
use App\Models\PrestaShopShop;
use Illuminate\Support\Str;

/**
 * Template Variable Service.
 *
 * Handles template variable parsing and replacement for Visual Description Editor.
 * Supports placeholders like {{product_name}}, {{product_sku}}, etc.
 */
class TemplateVariableService
{
    /**
     * Variable pattern regex: matches {{variable_name}}
     */
    private const VARIABLE_PATTERN = '/\{\{([a-z_]+)\}\}/i';

    /**
     * Get all available variables with descriptions.
     *
     * @return array<string, array{description: string, example: string, category: string}>
     */
    public function getAvailableVariables(): array
    {
        return [
            // Product variables
            'product_name' => [
                'description' => 'Nazwa produktu',
                'example' => 'Cross YCF Bigy Factory 190 MX',
                'category' => 'product',
            ],
            'product_sku' => [
                'description' => 'SKU produktu',
                'example' => 'YCF-BIGY-190-MX',
                'category' => 'product',
            ],
            'product_price' => [
                'description' => 'Cena produktu (sformatowana)',
                'example' => '25 990,00 zl',
                'category' => 'product',
            ],
            'product_price_raw' => [
                'description' => 'Cena produktu (wartosc)',
                'example' => '25990.00',
                'category' => 'product',
            ],
            'product_description_short' => [
                'description' => 'Krotki opis produktu',
                'example' => 'Profesjonalny cross dla zaawansowanych...',
                'category' => 'product',
            ],
            'product_ean' => [
                'description' => 'Kod EAN produktu',
                'example' => '5904123456789',
                'category' => 'product',
            ],
            'product_reference' => [
                'description' => 'Numer referencyjny produktu',
                'example' => 'REF-2024-001',
                'category' => 'product',
            ],
            'product_weight' => [
                'description' => 'Waga produktu',
                'example' => '85.5 kg',
                'category' => 'product',
            ],

            // Media variables
            'product_cover_image' => [
                'description' => 'URL glownego zdjecia produktu',
                'example' => 'https://ppm.mpptrade.pl/storage/products/YCF-123/cover.jpg',
                'category' => 'media',
            ],
            'product_images' => [
                'description' => 'Lista URL wszystkich zdjec (JSON)',
                'example' => '["url1.jpg", "url2.jpg", "url3.jpg"]',
                'category' => 'media',
            ],
            'product_gallery_count' => [
                'description' => 'Liczba zdjec w galerii',
                'example' => '8',
                'category' => 'media',
            ],

            // Features variables
            'product_features' => [
                'description' => 'Cechy produktu (tabela HTML)',
                'example' => '<table class="features-table">...</table>',
                'category' => 'features',
            ],
            'product_features_json' => [
                'description' => 'Cechy produktu (JSON)',
                'example' => '[{"name":"Moc","value":"125 KM"},...]',
                'category' => 'features',
            ],
            'product_attributes' => [
                'description' => 'Atrybuty produktu (JSON)',
                'example' => '{"color":"Red","size":"XL"}',
                'category' => 'features',
            ],

            // Manufacturer variables
            'manufacturer_name' => [
                'description' => 'Nazwa producenta',
                'example' => 'YCF',
                'category' => 'manufacturer',
            ],

            // Category variables
            'category_name' => [
                'description' => 'Nazwa kategorii',
                'example' => 'Crossy',
                'category' => 'category',
            ],
            'category_full_path' => [
                'description' => 'Pelna sciezka kategorii',
                'example' => 'Pojazdy > Motocykle > Crossy',
                'category' => 'category',
            ],

            // Shop variables
            'shop_name' => [
                'description' => 'Nazwa sklepu',
                'example' => 'B2B Hurtownia',
                'category' => 'shop',
            ],
            'shop_url' => [
                'description' => 'URL sklepu',
                'example' => 'https://b2b.mpptrade.pl',
                'category' => 'shop',
            ],

            // Date/time variables
            'current_date' => [
                'description' => 'Aktualna data',
                'example' => '11.12.2025',
                'category' => 'datetime',
            ],
            'current_year' => [
                'description' => 'Aktualny rok',
                'example' => '2025',
                'category' => 'datetime',
            ],
            'current_month' => [
                'description' => 'Aktualny miesiac',
                'example' => 'Grudzien',
                'category' => 'datetime',
            ],
        ];
    }

    /**
     * Get variable categories with labels.
     *
     * @return array<string, string>
     */
    public function getVariableCategories(): array
    {
        return [
            'product' => 'Dane produktu',
            'media' => 'Zdjecia i media',
            'features' => 'Cechy i atrybuty',
            'manufacturer' => 'Producent',
            'category' => 'Kategoria',
            'shop' => 'Sklep',
            'datetime' => 'Data i czas',
        ];
    }

    /**
     * Get variables grouped by category.
     *
     * @return array<string, array<string, array>>
     */
    public function getVariablesGroupedByCategory(): array
    {
        $variables = $this->getAvailableVariables();
        $grouped = [];

        foreach ($variables as $key => $data) {
            $category = $data['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$key] = $data;
        }

        return $grouped;
    }

    /**
     * Parse variables from blocks content.
     *
     * @param array $blocks Array of block data
     * @return array<string> List of unique variable names found
     */
    public function parseVariables(array $blocks): array
    {
        $variables = [];
        $json = json_encode($blocks);

        if ($json === false) {
            return [];
        }

        preg_match_all(self::VARIABLE_PATTERN, $json, $matches);

        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
            $variables = array_values($variables);
        }

        return $variables;
    }

    /**
     * Replace variables in blocks with product data.
     *
     * @param array $blocks Array of block data
     * @param Product $product Product to get data from
     * @param PrestaShopShop|null $shop Optional shop for shop-specific variables
     * @return array Modified blocks with replaced variables
     */
    public function replaceVariables(array $blocks, Product $product, ?PrestaShopShop $shop = null): array
    {
        $replacements = $this->buildReplacementMap($product, $shop);

        // Convert to JSON, replace, convert back
        $json = json_encode($blocks, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return $blocks;
        }

        foreach ($replacements as $variable => $value) {
            $pattern = '{{' . $variable . '}}';
            $json = str_replace($pattern, $value, $json);
        }

        $result = json_decode($json, true);
        return is_array($result) ? $result : $blocks;
    }

    /**
     * Replace variables in a single string.
     */
    public function replaceVariablesInString(string $text, Product $product, ?PrestaShopShop $shop = null): string
    {
        $replacements = $this->buildReplacementMap($product, $shop);

        foreach ($replacements as $variable => $value) {
            $pattern = '{{' . $variable . '}}';
            $text = str_replace($pattern, $value, $text);
        }

        return $text;
    }

    /**
     * Build replacement map for a product.
     *
     * @return array<string, string>
     */
    private function buildReplacementMap(Product $product, ?PrestaShopShop $shop = null): array
    {
        $replacements = [];

        // Product variables
        $replacements['product_name'] = $product->name ?? '';
        $replacements['product_sku'] = $product->sku ?? '';
        $replacements['product_price'] = $this->formatPrice($product->price ?? 0);
        $replacements['product_price_raw'] = (string) ($product->price ?? 0);
        $replacements['product_description_short'] = Str::limit($product->description_short ?? '', 200);
        $replacements['product_ean'] = $product->ean ?? '';
        $replacements['product_reference'] = $product->reference ?? '';
        $replacements['product_weight'] = $this->formatWeight($product->weight ?? 0);

        // Media variables
        $replacements['product_cover_image'] = $this->getProductCoverImage($product);
        $replacements['product_images'] = $this->getProductImagesJson($product);
        $replacements['product_gallery_count'] = (string) $this->getProductGalleryCount($product);

        // Features variables
        $replacements['product_features'] = $this->getProductFeaturesHtml($product);
        $replacements['product_features_json'] = $this->getProductFeaturesJson($product);
        $replacements['product_attributes'] = $this->getProductAttributesJson($product);

        // Manufacturer
        $replacements['manufacturer_name'] = $product->manufacturer->name ?? '';

        // Category
        $category = $product->category;
        $replacements['category_name'] = $category->name ?? '';
        $replacements['category_full_path'] = $this->getCategoryPath($category);

        // Shop
        $replacements['shop_name'] = $shop->name ?? '';
        $replacements['shop_url'] = $shop->shop_url ?? '';

        // Date/time
        $replacements['current_date'] = now()->format('d.m.Y');
        $replacements['current_year'] = now()->format('Y');
        $replacements['current_month'] = $this->getPolishMonth(now()->month);

        return $replacements;
    }

    /**
     * Format price with Polish locale.
     */
    private function formatPrice(float $price): string
    {
        return number_format($price, 2, ',', ' ') . ' zl';
    }

    /**
     * Format weight with unit.
     */
    private function formatWeight(float $weight): string
    {
        if ($weight < 1) {
            return number_format($weight * 1000, 0) . ' g';
        }
        return number_format($weight, 1, ',', ' ') . ' kg';
    }

    /**
     * Get full category path as breadcrumb string.
     */
    private function getCategoryPath($category): string
    {
        if (!$category) {
            return '';
        }

        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent ?? null;
        }

        return implode(' > ', $path);
    }

    /**
     * Get Polish month name.
     */
    private function getPolishMonth(int $month): string
    {
        $months = [
            1 => 'Styczen',
            2 => 'Luty',
            3 => 'Marzec',
            4 => 'Kwiecien',
            5 => 'Maj',
            6 => 'Czerwiec',
            7 => 'Lipiec',
            8 => 'Sierpien',
            9 => 'Wrzesien',
            10 => 'Pazdziernik',
            11 => 'Listopad',
            12 => 'Grudzien',
        ];

        return $months[$month] ?? '';
    }

    /**
     * Get product cover image URL.
     */
    private function getProductCoverImage(Product $product): string
    {
        // Use primaryImage accessor from HasFeatures trait
        return $product->primary_image ?? '';
    }

    /**
     * Get all product images as JSON array of URLs.
     */
    private function getProductImagesJson(Product $product): string
    {
        try {
            $images = $product->media()
                ->active()
                ->get()
                ->pluck('url')
                ->filter()
                ->values()
                ->toArray();

            return json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
        } catch (\Throwable $e) {
            return '[]';
        }
    }

    /**
     * Get product gallery count.
     */
    private function getProductGalleryCount(Product $product): int
    {
        try {
            return $product->media()->active()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get product features as HTML table.
     */
    private function getProductFeaturesHtml(Product $product): string
    {
        try {
            $features = $product->features()
                ->with(['featureType', 'featureValue'])
                ->get();

            if ($features->isEmpty()) {
                return '';
            }

            $html = '<table class="product-features-table">';
            $html .= '<tbody>';

            foreach ($features as $feature) {
                $name = $feature->featureType->name ?? 'N/A';
                $value = $feature->featureValue->value ?? $feature->custom_value ?? 'N/A';
                $html .= '<tr>';
                $html .= '<td class="feature-name">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td class="feature-value">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            return $html;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get product features as JSON.
     */
    private function getProductFeaturesJson(Product $product): string
    {
        try {
            $features = $product->features()
                ->with(['featureType', 'featureValue'])
                ->get()
                ->map(function ($feature) {
                    return [
                        'name' => $feature->featureType->name ?? 'N/A',
                        'value' => $feature->featureValue->value ?? $feature->custom_value ?? 'N/A',
                        'code' => $feature->featureType->code ?? null,
                    ];
                })
                ->toArray();

            return json_encode($features, JSON_UNESCAPED_UNICODE) ?: '[]';
        } catch (\Throwable $e) {
            return '[]';
        }
    }

    /**
     * Get product attributes as JSON.
     */
    private function getProductAttributesJson(Product $product): string
    {
        try {
            $attributes = $product->attributeValues()
                ->with('attribute')
                ->get()
                ->mapWithKeys(function ($value) {
                    $code = $value->attribute->code ?? 'unknown';
                    return [$code => $value->formatted_value ?? $value->value];
                })
                ->toArray();

            return json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: '{}';
        } catch (\Throwable $e) {
            return '{}';
        }
    }

    /**
     * Validate if all variables in blocks can be resolved.
     *
     * @return array{valid: bool, missing: array<string>}
     */
    public function validateVariables(array $blocks): array
    {
        $found = $this->parseVariables($blocks);
        $available = array_keys($this->getAvailableVariables());
        $missing = array_diff($found, $available);

        return [
            'valid' => empty($missing),
            'missing' => array_values($missing),
        ];
    }

    /**
     * Get preview of variables with example values.
     *
     * @return array<string, string>
     */
    public function getPreviewValues(): array
    {
        $values = [];
        foreach ($this->getAvailableVariables() as $key => $data) {
            $values[$key] = $data['example'];
        }
        return $values;
    }
}
