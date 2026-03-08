<?php

namespace App\Services\Export\Generators;

use App\Models\ExportProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use XMLWriter;

/**
 * PrestaShop XML Feed Generator
 *
 * Generates XML files compatible with PrestaShop Import/Export:
 * - UTF-8 encoding with XML declaration
 * - CDATA sections for text fields
 * - Field mapping from PPM flat product arrays to PrestaShop XML structure
 * - Uses PHP XMLWriter for streaming, memory-efficient output
 *
 * Reference: PrestaShop Webservice API /api/products schema
 */
class PrestaShopXmlGenerator implements FeedGeneratorInterface
{
    /**
     * Fields that require CDATA wrapping (text content).
     */
    private const CDATA_FIELDS = [
        'name',
        'description',
        'description_short',
        'manufacturer',
        'category_default',
        'categories',
        'meta_title',
        'meta_description',
        'link_rewrite',
    ];

    /**
     * Mapping: PrestaShop XML element => PPM product array key.
     */
    private const FIELD_MAP = [
        'reference'           => 'sku',
        'name'                => 'name',
        'description'         => 'long_description',
        'description_short'   => 'short_description',
        'ean13'               => 'ean',
        'weight'              => 'weight',
        'height'              => 'height',
        'width'               => 'width',
        'depth'               => 'length',
        'manufacturer'        => 'manufacturer',
        'category_default'    => 'category_primary',
        'categories'          => 'category_path',
        'image_url'           => 'image_url_main',
        'meta_title'          => 'meta_title',
        'meta_description'    => 'meta_description',
        'link_rewrite'        => 'slug',
    ];

    /**
     * Prefixes used to find net price columns in PPM product data.
     */
    private const PRICE_NET_PREFIX = 'price_net_';

    /**
     * Prefix used to find stock columns in PPM product data.
     */
    private const STOCK_PREFIX = 'stock_';

    public function generate(array $products, ExportProfile $profile): string
    {
        $dir = storage_path('app/exports/feeds');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $profile->slug . '-' . now()->format('Ymd-His') . '.xml';
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

        $xml = new XMLWriter();
        if (!$xml->openUri($filePath)) {
            throw new RuntimeException("Cannot open file for writing: {$filePath}");
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startElement('products');

        foreach ($products as $product) {
            $this->writeProduct($xml, $product);
        }

        $xml->endElement(); // </products>
        $xml->endDocument();
        $xml->flush();

        Log::info('PrestaShopXmlGenerator: Feed generated', [
            'profile_id' => $profile->id,
            'profile_slug' => $profile->slug,
            'product_count' => count($products),
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    public function getContentType(): string
    {
        return 'application/xml; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'xml';
    }

    /**
     * Write a single <product> element to the XML stream.
     */
    private function writeProduct(XMLWriter $xml, array $product): void
    {
        $xml->startElement('product');

        // id_product - PrestaShop external ID if available
        $this->writeElement($xml, 'id_product', $this->getField($product, 'id', ''));

        // Mapped fields from PPM to PrestaShop
        foreach (self::FIELD_MAP as $xmlElement => $ppmKey) {
            $value = $this->getField($product, $ppmKey, '');
            $this->writeElement($xml, $xmlElement, $value);
        }

        // Price - first available net price
        $price = $this->resolvePrice($product);
        $this->writeElement($xml, 'price', $this->formatDecimal($price));

        // Wholesale price (purchase price)
        $wholesalePrice = $this->getField($product, 'purchase_price', '');
        $this->writeElement(
            $xml,
            'wholesale_price',
            $this->formatDecimal($wholesalePrice)
        );

        // Tax rules group
        $this->writeElement(
            $xml,
            'id_tax_rules_group',
            $this->getField($product, 'id_tax_rules_group', '1')
        );

        // Quantity - sum of all stock_ fields
        $quantity = $this->resolveQuantity($product);
        $this->writeElement($xml, 'quantity', (string) $quantity);

        // Active status
        $active = $this->resolveActive($product);
        $this->writeElement($xml, 'active', $active);

        $xml->endElement(); // </product>
    }

    /**
     * Write a single XML element, using CDATA for text fields.
     */
    private function writeElement(XMLWriter $xml, string $name, string $value): void
    {
        if (in_array($name, self::CDATA_FIELDS, true)) {
            $xml->startElement($name);
            $xml->writeCdata($value);
            $xml->endElement();
        } else {
            $xml->writeElement($name, $value);
        }
    }

    /**
     * Resolve product price from PPM data.
     *
     * Searches for the first available price_net_* field.
     * Falls back to 'price' field if no price_net_* found.
     */
    private function resolvePrice(array $product): string
    {
        // Search for price_net_* columns
        foreach ($product as $key => $value) {
            if (str_starts_with($key, self::PRICE_NET_PREFIX) && $this->isNumericValue($value)) {
                return (string) $value;
            }
        }

        // Fallback: generic 'price' field
        $fallback = $product['price'] ?? $product['price_net'] ?? '';

        return $this->isNumericValue($fallback) ? (string) $fallback : '0';
    }

    /**
     * Resolve total stock quantity from PPM data.
     *
     * Sums all stock_* fields. Falls back to 'quantity' or 'stock' field.
     */
    private function resolveQuantity(array $product): int
    {
        $total = 0;
        $foundStock = false;

        foreach ($product as $key => $value) {
            if (str_starts_with($key, self::STOCK_PREFIX) && is_numeric($value)) {
                $total += (int) $value;
                $foundStock = true;
            }
        }

        if ($foundStock) {
            return max(0, $total);
        }

        // Fallback: generic fields
        $fallback = $product['quantity'] ?? $product['stock'] ?? 0;

        return max(0, (int) $fallback);
    }

    /**
     * Resolve active status from PPM data.
     *
     * Maps is_active (bool/int/string) to "1" or "0".
     */
    private function resolveActive(array $product): string
    {
        $value = $product['is_active'] ?? $product['active'] ?? 1;

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'yes', 'tak'], true)) {
                return '1';
            }
            if (in_array($lower, ['false', '0', 'no', 'nie'], true)) {
                return '0';
            }
        }

        return $value ? '1' : '0';
    }

    /**
     * Get a field value from product array defensively.
     */
    private function getField(array $product, string $key, string $default = ''): string
    {
        $value = $product[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_array($value)) {
            return implode(',', $value);
        }

        return (string) $value;
    }

    /**
     * Format a value as a decimal string (e.g., "99.99").
     * Returns "0" for empty/non-numeric values.
     */
    private function formatDecimal(string $value): string
    {
        if ($value === '' || !is_numeric($value)) {
            return '0';
        }

        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Check if a value is numeric (int, float, or numeric string).
     */
    private function isNumericValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return is_numeric($value);
    }
}
