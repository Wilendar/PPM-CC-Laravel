<?php

namespace App\Services\Export\Generators;

use App\Models\ExportProfile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * JSON Feed Generator
 *
 * Generates JSON feed files with:
 * - Meta section (profile info, timestamps, field list)
 * - Products array
 * - Pretty-printed, unescaped Unicode output
 */
class JsonFeedGenerator implements FeedGeneratorInterface
{
    public function generate(array $products, ExportProfile $profile): string
    {
        $dir = storage_path('app/exports/feeds');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $profile->slug . '-' . now()->format('Ymd-His') . '.json';
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

        $data = [
            'meta' => $this->buildMeta($products, $profile),
            'products' => $products,
        ];

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        $written = file_put_contents($filePath, $json);
        if ($written === false) {
            throw new RuntimeException("Cannot write file: {$filePath}");
        }

        Log::info('JsonFeedGenerator: Feed generated', [
            'profile_id' => $profile->id,
            'profile_slug' => $profile->slug,
            'product_count' => count($products),
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    public function getContentType(): string
    {
        return 'application/json; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    /**
     * Build meta section for JSON feed.
     */
    private function buildMeta(array $products, ExportProfile $profile): array
    {
        $fields = $this->resolveFieldList($products, $profile);

        return [
            'profile_name' => $profile->name,
            'format' => 'json',
            'generated_at' => now()->toIso8601String(),
            'product_count' => count($products),
            'fields' => $fields,
        ];
    }

    /**
     * Resolve field list from profile config or product keys.
     *
     * @return string[]
     */
    private function resolveFieldList(array $products, ExportProfile $profile): array
    {
        $fieldConfig = $profile->field_config ?? [];

        if (!empty($fieldConfig)) {
            return array_is_list($fieldConfig)
                ? array_values($fieldConfig)
                : array_keys(array_filter($fieldConfig));
        }

        if (!empty($products)) {
            return array_keys($products[0]);
        }

        return [];
    }
}
