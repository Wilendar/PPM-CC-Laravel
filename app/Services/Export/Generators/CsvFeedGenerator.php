<?php

namespace App\Services\Export\Generators;

use App\Models\ExportProfile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * CSV Feed Generator
 *
 * Generates CSV files with:
 * - UTF-8 BOM for Excel compatibility
 * - Semicolon separator (Polish Excel standard)
 * - Streaming write for memory efficiency
 */
class CsvFeedGenerator implements FeedGeneratorInterface
{
    public function generate(array $products, ExportProfile $profile): string
    {
        $dir = storage_path('app/exports/feeds');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $profile->slug . '-' . now()->format('Ymd-His') . '.csv';
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file for writing: {$filePath}");
        }

        try {
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = $this->resolveHeaders($products, $profile);

            if (!empty($headers)) {
                fputcsv($handle, $headers, ';');
            }

            foreach ($products as $product) {
                $row = $this->buildRow($product, $headers);
                fputcsv($handle, $row, ';');
            }
        } finally {
            fclose($handle);
        }

        Log::info('CsvFeedGenerator: Feed generated', [
            'profile_id' => $profile->id,
            'profile_slug' => $profile->slug,
            'product_count' => count($products),
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    public function getContentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }

    /**
     * Resolve column headers from profile field_config or product keys.
     *
     * @return string[]
     */
    private function resolveHeaders(array $products, ExportProfile $profile): array
    {
        $fieldConfig = $profile->field_config ?? [];

        // If profile has explicit field config, use its keys
        if (!empty($fieldConfig)) {
            $keys = array_is_list($fieldConfig)
                ? array_values($fieldConfig)
                : array_keys(array_filter($fieldConfig));

            return $keys;
        }

        // Fallback: use keys from the first product row
        if (!empty($products)) {
            return array_keys($products[0]);
        }

        return [];
    }

    /**
     * Build a CSV row ordered by headers. Missing keys become empty strings.
     */
    private function buildRow(array $product, array $headers): array
    {
        if (empty($headers)) {
            return array_values($product);
        }

        $row = [];
        foreach ($headers as $key) {
            $value = $product[$key] ?? '';
            $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $row;
    }
}
