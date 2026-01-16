<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;

/**
 * DynamicsService - Placeholder
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * PLACEHOLDER dla przyszlej integracji z Microsoft Dynamics 365.
 * Dynamics wymaga OAuth2 authentication i OData API.
 *
 * Status: NOT IMPLEMENTED (placeholder only)
 */
class DynamicsService implements ERPSyncServiceInterface
{
    /**
     * Test connection to Microsoft Dynamics.
     */
    public function testConnection(array $config): array
    {
        return [
            'success' => false,
            'message' => 'Microsoft Dynamics integration nie jest jeszcze zaimplementowana. Wymaga OAuth2 i OData API.',
            'response_time' => 0,
            'details' => [
                'status' => 'not_implemented',
                'required' => ['Azure AD App Registration', 'OAuth2 credentials', 'OData endpoint'],
            ],
        ];
    }

    /**
     * Test authentication with Microsoft Dynamics.
     */
    public function testAuthentication(array $config): array
    {
        return $this->testConnection($config);
    }

    /**
     * Sync product TO Microsoft Dynamics.
     */
    public function syncProductToERP(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Microsoft Dynamics sync nie jest jeszcze zaimplementowany.',
            'external_id' => null,
        ];
    }

    /**
     * Sync product FROM Microsoft Dynamics.
     */
    public function syncProductFromERP(ERPConnection $connection, string $erpProductId): array
    {
        return [
            'success' => false,
            'message' => 'Microsoft Dynamics pull nie jest jeszcze zaimplementowany.',
            'product' => null,
        ];
    }

    /**
     * Sync all products to Microsoft Dynamics.
     */
    public function syncAllProducts(ERPConnection $connection, array $filters = []): array
    {
        return [
            'success' => false,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => ['Microsoft Dynamics nie jest jeszcze zaimplementowany.'],
        ];
    }

    /**
     * Pull all products from Microsoft Dynamics.
     */
    public function pullAllProducts(ERPConnection $connection, array $filters = []): array
    {
        return [
            'success' => false,
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => ['Microsoft Dynamics nie jest jeszcze zaimplementowany.'],
        ];
    }

    /**
     * Sync stock to Microsoft Dynamics.
     */
    public function syncStock(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Microsoft Dynamics stock sync nie jest jeszcze zaimplementowany.',
        ];
    }

    /**
     * Sync prices to Microsoft Dynamics.
     */
    public function syncPrices(ERPConnection $connection, Product $product): array
    {
        return [
            'success' => false,
            'message' => 'Microsoft Dynamics price sync nie jest jeszcze zaimplementowany.',
        ];
    }

    /**
     * Get ERP type.
     */
    public function getERPType(): string
    {
        return ERPConnection::ERP_DYNAMICS;
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
                'customers',
                'sales_orders',
                'purchase_orders',
            ],
            'requirements' => [
                'Azure AD App Registration',
                'OAuth2 Client ID/Secret',
                'OData API endpoint',
                'Dynamics 365 license',
            ],
        ];
    }
}
