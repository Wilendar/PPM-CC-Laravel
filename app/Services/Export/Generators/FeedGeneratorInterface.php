<?php

namespace App\Services\Export\Generators;

use App\Models\ExportProfile;

interface FeedGeneratorInterface
{
    /**
     * Generate feed file and return absolute file path.
     */
    public function generate(array $products, ExportProfile $profile): string;

    /**
     * Get content type for HTTP response.
     */
    public function getContentType(): string;

    /**
     * Get file extension.
     */
    public function getFileExtension(): string;
}
