<?php

namespace App\Services\Export;

use App\Services\Export\Generators\FeedGeneratorInterface;
use App\Services\Export\Generators\CsvFeedGenerator;
use App\Services\Export\Generators\JsonFeedGenerator;
use App\Services\Export\Generators\XmlCeneoGenerator;
use App\Services\Export\Generators\XmlGoogleShoppingGenerator;
use App\Services\Export\Generators\PrestaShopXmlGenerator;
use InvalidArgumentException;

class FeedGeneratorFactory
{
    /**
     * Create a feed generator for the given format.
     *
     * @throws InvalidArgumentException
     */
    public function make(string $format): FeedGeneratorInterface
    {
        return match ($format) {
            'csv' => new CsvFeedGenerator(),
            'json' => new JsonFeedGenerator(),
            'xml_google' => new XmlGoogleShoppingGenerator(),
            'xml_ceneo' => new XmlCeneoGenerator(),
            'xml_prestashop' => new PrestaShopXmlGenerator(),
            default => throw new InvalidArgumentException("Unsupported feed format: {$format}"),
        };
    }

    /**
     * Get list of currently supported formats.
     *
     * @return string[]
     */
    public function supportedFormats(): array
    {
        return ['csv', 'json', 'xml_google', 'xml_ceneo', 'xml_prestashop'];
    }
}
