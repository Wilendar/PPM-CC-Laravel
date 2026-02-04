<?php

namespace App\Services\Scan\Sources;

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Exceptions\PrestaShopAPIException;
use App\Exceptions\ScanSourceException;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShopScanSource
 *
 * Adapter for scanning products from PrestaShop shops.
 * Uses existing PrestaShopClientFactory to create appropriate client version.
 *
 * Usage:
 * ```php
 * $shop = PrestaShopShop::find(1);
 * $source = new PrestaShopScanSource($shop);
 * $skus = $source->getAllSkus();
 * ```
 *
 * @package App\Services\Scan\Sources
 * @version 1.0.0
 */
class PrestaShopScanSource implements ScanSourceInterface
{
    protected PrestaShopShop $shop;
    protected BasePrestaShopClient $client;
    protected ?int $cachedProductCount = null;

    /**
     * Constructor
     *
     * @param PrestaShopShop $shop Active PrestaShop shop
     * @throws ScanSourceException When shop is invalid or inactive
     */
    public function __construct(PrestaShopShop $shop)
    {
        $this->validateShop($shop);
        $this->shop = $shop;
        $this->client = PrestaShopClientFactory::create($shop);
    }

    /**
     * Validate the PrestaShop shop.
     *
     * @param PrestaShopShop $shop
     * @throws ScanSourceException
     */
    protected function validateShop(PrestaShopShop $shop): void
    {
        if (!$shop->is_active) {
            throw new ScanSourceException(
                "PrestaShop shop '{$shop->name}' is not active"
            );
        }

        if (empty($shop->api_key)) {
            throw new ScanSourceException(
                "PrestaShop shop '{$shop->name}' has no API key configured"
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSkus(): array
    {
        Log::info('PrestaShopScanSource::getAllSkus - Starting', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        $allSkus = [];
        $startTime = microtime(true);

        try {
            // Fetch all products with only id and reference fields
            $response = $this->client->getProducts([
                'display' => '[id,reference]',
            ]);

            $products = $response['products'] ?? [];

            // Handle single product returned as object
            if (!isset($products[0]) && isset($products['id'])) {
                $products = [$products];
            }

            foreach ($products as $product) {
                $sku = $product['reference'] ?? null;
                if ($sku !== null && $sku !== '') {
                    $allSkus[] = (string) $sku;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('PrestaShopScanSource::getAllSkus - Completed', [
                'shop_id' => $this->shop->id,
                'total_skus' => count($allSkus),
                'duration_seconds' => $duration,
            ]);

            return array_unique($allSkus);

        } catch (PrestaShopAPIException $e) {
            Log::error('PrestaShopScanSource::getAllSkus - API Error', [
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch SKUs from PrestaShop '{$this->shop->name}': {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductBySku(string $sku): ?array
    {
        try {
            // PrestaShop uses 'reference' field for SKU
            $response = $this->client->getProducts([
                'filter[reference]' => $sku,
                'display' => 'full',
            ]);

            $products = $response['products'] ?? [];

            // Handle single product returned as object
            if (!isset($products[0]) && isset($products['id'])) {
                $products = [$products];
            }

            if (empty($products)) {
                return null;
            }

            return $this->normalizeProduct($products[0]);

        } catch (PrestaShopAPIException $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            Log::error('PrestaShopScanSource::getProductBySku - API Error', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch product '{$sku}' from PrestaShop: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsBatch(int $page, int $perPage = 100): array
    {
        $perPage = min($perPage, 500);
        $offset = ($page - 1) * $perPage;

        try {
            $response = $this->client->getProducts([
                'display' => 'full',
                'limit' => "{$offset},{$perPage}",
            ]);

            $products = $response['products'] ?? [];

            // Handle single product returned as object
            if (!isset($products[0]) && isset($products['id'])) {
                $products = [$products];
            }

            $normalizedProducts = array_map(
                fn($product) => $this->normalizeProduct($product),
                $products
            );

            $total = $this->getProductCount();

            return [
                'data' => $normalizedProducts,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => ($offset + count($products)) < $total,
            ];

        } catch (PrestaShopAPIException $e) {
            Log::error('PrestaShopScanSource::getProductsBatch - API Error', [
                'page' => $page,
                'per_page' => $perPage,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch products batch from PrestaShop: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductCount(): int
    {
        if ($this->cachedProductCount !== null) {
            return $this->cachedProductCount;
        }

        try {
            // Fetch only IDs to count products
            $response = $this->client->getProducts([
                'display' => '[id]',
            ]);

            $products = $response['products'] ?? [];

            // Handle single product returned as object
            if (!isset($products[0]) && isset($products['id'])) {
                $products = [$products];
            }

            $this->cachedProductCount = count($products);

            return $this->cachedProductCount;

        } catch (PrestaShopAPIException $e) {
            Log::error('PrestaShopScanSource::getProductCount - API Error', [
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to get product count from PrestaShop: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType(): string
    {
        return 'prestashop';
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceId(): ?int
    {
        return $this->shop->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceName(): string
    {
        return "PrestaShop - {$this->shop->name}";
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            // Try to fetch a single product to verify connection
            $response = $this->client->getProducts([
                'display' => '[id]',
                'limit' => '0,1',
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'message' => 'Connection to PrestaShop successful',
                'response_time_ms' => $responseTime,
            ];

        } catch (PrestaShopAPIException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'message' => "Connection failed: {$e->getMessage()}",
                'response_time_ms' => $responseTime,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeProduct(array $rawProduct): array
    {
        // Extract multilang name (take first language)
        $name = $this->extractMultilangValue($rawProduct['name'] ?? []);

        // Extract multilang description
        $description = $this->extractMultilangValue($rawProduct['description'] ?? []);

        return [
            'external_id' => (string) ($rawProduct['id'] ?? ''),
            'sku' => (string) ($rawProduct['reference'] ?? ''),
            'name' => $name,
            'description' => $description,
            'ean' => $rawProduct['ean13'] ?? null,
            'price_net' => isset($rawProduct['price'])
                ? (float) $rawProduct['price']
                : null,
            'price_gross' => isset($rawProduct['price'])
                ? (float) $rawProduct['price'] * (1 + (($rawProduct['id_tax_rules_group'] ?? 23) / 100))
                : null,
            'stock' => isset($rawProduct['quantity'])
                ? (float) $rawProduct['quantity']
                : null,
            'unit' => $rawProduct['unity'] ?? null,
            'weight' => isset($rawProduct['weight'])
                ? (float) $rawProduct['weight']
                : null,
            'vat_rate' => null, // Would require tax rules lookup
            'is_active' => (bool) ($rawProduct['active'] ?? true),
            'manufacturer' => $rawProduct['id_manufacturer'] ?? null,
            'group' => $rawProduct['id_category_default'] ?? null,
            'source_type' => $this->getSourceType(),
            'source_id' => $this->getSourceId(),
            'raw_data' => $rawProduct,
        ];
    }

    /**
     * Extract value from multilang PrestaShop field.
     *
     * @param mixed $field Multilang field (array or string)
     * @return string|null Extracted value
     */
    protected function extractMultilangValue(mixed $field): ?string
    {
        if (is_string($field)) {
            return $field;
        }

        if (is_array($field)) {
            // PrestaShop multilang: [['id' => 1, 'value' => 'text'], ...]
            if (isset($field[0]['value'])) {
                return (string) $field[0]['value'];
            }

            // Alternative format: ['language' => [['id' => 1, 'value' => '...']]]
            if (isset($field['language'][0]['value'])) {
                return (string) $field['language'][0]['value'];
            }

            // Simple array with first value
            $firstValue = reset($field);
            if (is_string($firstValue)) {
                return $firstValue;
            }
        }

        return null;
    }

    /**
     * Get the underlying API client.
     *
     * @return BasePrestaShopClient
     */
    public function getClient(): BasePrestaShopClient
    {
        return $this->client;
    }

    /**
     * Get the PrestaShop shop model.
     *
     * @return PrestaShopShop
     */
    public function getShop(): PrestaShopShop
    {
        return $this->shop;
    }
}
