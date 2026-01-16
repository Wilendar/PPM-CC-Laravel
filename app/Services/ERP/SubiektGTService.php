<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;

/**
 * SubiektGTService - Placeholder
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * PLACEHOLDER dla przyszlej integracji z Subiekt GT.
 * Subiekt GT wymaga DLL bridge na Windows Server.
 *
 * Status: NOT IMPLEMENTED (placeholder only)
 */
class SubiektGTService implements ERPSyncServiceInterface
{
    /**
     * Test connection to Subiekt GT.
     */
    public function testConnection(array $config): array
    {
        return [
            'success' => false,
            'message' => 'Subiekt GT integration nie jest jeszcze zaimplementowana. Wymaga DLL bridge na Windows Server.',
            'response_time' => 0,
            'details' => [
                'status' => 'not_implemented',
                'required' => ['Windows Server', 'Subiekt GT DLL', 'COM Bridge'],
            ],
        ];
    }

    /**
     * Test authentication with Subiekt GT.
     */
    public function testAuthentication(array $config): array
    {
        return $this->testConnection($config);
    }

    /**
     * Sync product TO Subiekt GT.
     */
    public function syncProductToERP(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Subiekt GT sync nie jest jeszcze zaimplementowany.',
            'external_id' => null,
        ];
    }

    /**
     * Sync product FROM Subiekt GT.
     */
    public function syncProductFromERP(ERPConnection $connection, string $erpProductId): array
    {
        return [
            'success' => false,
            'message' => 'Subiekt GT pull nie jest jeszcze zaimplementowany.',
            'product' => null,
        ];
    }

    /**
     * Sync all products to Subiekt GT.
     */
    public function syncAllProducts(ERPConnection $connection, array $filters = []): array
    {
        return [
            'success' => false,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => ['Subiekt GT nie jest jeszcze zaimplementowany.'],
        ];
    }

    /**
     * Pull all products from Subiekt GT.
     */
    public function pullAllProducts(ERPConnection $connection, array $filters = []): array
    {
        return [
            'success' => false,
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => ['Subiekt GT nie jest jeszcze zaimplementowany.'],
        ];
    }

    /**
     * Sync stock to Subiekt GT.
     */
    public function syncStock(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Subiekt GT stock sync nie jest jeszcze zaimplementowany.',
        ];
    }

    /**
     * Sync prices to Subiekt GT.
     */
    public function syncPrices(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Subiekt GT price sync nie jest jeszcze zaimplementowany.',
        ];
    }

    /**
     * Get ERP type.
     */
    public function getERPType(): string
    {
        return ERPConnection::ERP_SUBIEKT_GT;
    }

    /**
     * Get supported features.
     */
    public function getSupportedFeatures(): array
    {
        return [
            'status' => 'not_implemented',
            'planned_features' => [
                'products',
                'stock',
                'prices',
                'orders',
                'invoices',
                'contractors',
            ],
            'requirements' => [
                'Windows Server',
                'Subiekt GT installation',
                'COM/DLL bridge',
            ],
        ];
    }
}
