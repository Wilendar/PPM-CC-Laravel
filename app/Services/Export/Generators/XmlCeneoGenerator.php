<?php

namespace App\Services\Export\Generators;

use App\Models\ExportProfile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use XMLWriter;

/**
 * Ceneo.pl XML Feed Generator
 *
 * Generates XML feed compatible with Ceneo.pl marketplace:
 * - <offers> root element with <offer> children
 * - CDATA wrapping for text fields with potential HTML
 * - Multiple <img> elements for product images
 * - <attrs> section for additional attributes
 * - XMLWriter streaming for memory efficiency
 */
class XmlCeneoGenerator implements FeedGeneratorInterface
{
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

        try {
            $this->writeDocument($xml, $products);
        } finally {
            $xml->flush();
        }

        Log::info('XmlCeneoGenerator: Feed generated', [
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
     * Write the complete XML document.
     */
    private function writeDocument(XMLWriter $xml, array $products): void
    {
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startElement('offers');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('version', '1');

        foreach ($products as $product) {
            $this->writeOffer($xml, $product);
        }

        $xml->endElement(); // </offers>
        $xml->endDocument();
    }

    /**
     * Write a single <offer> element.
     */
    private function writeOffer(XMLWriter $xml, array $product): void
    {
        $sku = $product['sku'] ?? null;
        if ($sku === null || $sku === '') {
            return;
        }

        $xml->startElement('offer');
        $xml->writeAttribute('id', $sku);

        // <name> - required
        $name = $product['name'] ?? '';
        if ($name !== '') {
            $xml->writeElement('name', $name);
        }

        // <desc> - CDATA (short_description preferred, fallback to long_description)
        $description = $product['short_description'] ?? $product['long_description'] ?? null;
        if ($description !== null && $description !== '') {
            $this->writeCdataElement($xml, 'desc', $description);
        }

        // <cat> - CDATA, category path
        $categoryPath = $product['category_path'] ?? null;
        if ($categoryPath !== null && $categoryPath !== '') {
            // Replace pipe separator with Ceneo ">" format if needed
            $categoryPath = $this->normalizeCategoryPath($categoryPath);
            $this->writeCdataElement($xml, 'cat', $categoryPath);
        }

        // <price> - first available gross price
        $price = $this->resolvePrice($product);
        if ($price !== null) {
            $xml->writeElement('price', $price);
        }

        // <url> - product URL if available
        $url = $product['url'] ?? $product['product_url'] ?? null;
        if ($url !== null && $url !== '') {
            $xml->writeElement('url', $url);
        }

        // <img> elements - main image + additional images
        $this->writeImageElements($xml, $product);

        // <availability> - 1=available, 0=unavailable
        $totalStock = $this->resolveTotalStock($product);
        $xml->writeElement('availability', $totalStock > 0 ? '1' : '0');

        // <stock>
        if ($totalStock > 0) {
            $xml->writeElement('stock', (string) $totalStock);
        }

        // <weight>
        $weight = $product['weight'] ?? null;
        if ($weight !== null && $weight !== '' && (float) $weight > 0) {
            $xml->writeElement('weight', number_format((float) $weight, 2, '.', ''));
        }

        // <brand> - manufacturer
        $brand = $product['manufacturer'] ?? null;
        if ($brand !== null && $brand !== '') {
            $xml->writeElement('brand', $brand);
        }

        // <ean>
        $ean = $product['ean'] ?? null;
        if ($ean !== null && $ean !== '') {
            $xml->writeElement('ean', $ean);
        }

        // <attrs> - additional attributes
        $this->writeAttrs($xml, $product);

        $xml->endElement(); // </offer>
    }

    /**
     * Write a CDATA-wrapped element.
     */
    private function writeCdataElement(XMLWriter $xml, string $elementName, string $value): void
    {
        $xml->startElement($elementName);
        $xml->writeCdata($value);
        $xml->endElement();
    }

    /**
     * Normalize category path to Ceneo ">" separator format.
     *
     * Input may use " | " or " > " separators from the export system.
     */
    private function normalizeCategoryPath(string $path): string
    {
        // Replace pipe separator with " > " (Ceneo standard)
        return str_replace(' | ', ' > ', $path);
    }

    /**
     * Resolve the first available gross price from product data.
     *
     * Searches for keys matching "price_gross_*" pattern.
     */
    private function resolvePrice(array $product): ?string
    {
        foreach ($product as $key => $value) {
            if (str_starts_with($key, 'price_gross_') && $value !== null && $value !== '') {
                $numericValue = (float) $value;
                if ($numericValue > 0) {
                    return number_format($numericValue, 2, '.', '');
                }
            }
        }

        // Fallback: try net price keys
        foreach ($product as $key => $value) {
            if (str_starts_with($key, 'price_net_') && $value !== null && $value !== '') {
                $numericValue = (float) $value;
                if ($numericValue > 0) {
                    return number_format($numericValue, 2, '.', '');
                }
            }
        }

        return null;
    }

    /**
     * Write <img> elements for main image and additional images.
     */
    private function writeImageElements(XMLWriter $xml, array $product): void
    {
        $mainImage = $product['image_url_main'] ?? null;

        if ($mainImage !== null && $mainImage !== '') {
            $xml->writeElement('img', $mainImage);
        }

        // Additional images from "image_urls_all" (pipe-separated)
        $allImages = $product['image_urls_all'] ?? null;
        if ($allImages !== null && $allImages !== '') {
            $imageUrls = array_map('trim', explode(' | ', $allImages));

            foreach ($imageUrls as $imageUrl) {
                if ($imageUrl === '' || $imageUrl === $mainImage) {
                    continue;
                }
                $xml->writeElement('img', $imageUrl);
            }
        }
    }

    /**
     * Calculate total stock from all stock_* fields.
     */
    private function resolveTotalStock(array $product): int
    {
        $total = 0;

        foreach ($product as $key => $value) {
            if (str_starts_with($key, 'stock_') && $value !== null && $value !== '') {
                $total += (int) $value;
            }
        }

        return max(0, $total);
    }

    /**
     * Write <attrs> section with additional product attributes.
     */
    private function writeAttrs(XMLWriter $xml, array $product): void
    {
        $attrs = [];

        // Manufacturer as attribute
        $manufacturer = $product['manufacturer'] ?? null;
        if ($manufacturer !== null && $manufacturer !== '') {
            $attrs['Producent'] = $manufacturer;
        }

        // EAN as attribute
        $ean = $product['ean'] ?? null;
        if ($ean !== null && $ean !== '') {
            $attrs['EAN'] = $ean;
        }

        // Supplier code
        $supplierCode = $product['supplier_code'] ?? null;
        if ($supplierCode !== null && $supplierCode !== '') {
            $attrs['Kod dostawcy'] = $supplierCode;
        }

        // Weight as attribute
        $weight = $product['weight'] ?? null;
        if ($weight !== null && $weight !== '' && (float) $weight > 0) {
            $attrs['Waga'] = number_format((float) $weight, 2, '.', '') . ' kg';
        }

        // Vehicle compatibility
        $vehicles = $product['compatible_vehicles'] ?? null;
        if ($vehicles !== null && $vehicles !== '') {
            $attrs['Kompatybilne pojazdy'] = $vehicles;
        }

        if (empty($attrs)) {
            return;
        }

        $xml->startElement('attrs');

        foreach ($attrs as $name => $value) {
            $xml->startElement('a');
            $xml->writeAttribute('name', $name);
            $xml->writeCdata($value);
            $xml->endElement(); // </a>
        }

        $xml->endElement(); // </attrs>
    }
}
