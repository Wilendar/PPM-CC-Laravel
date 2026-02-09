<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Traits;

use App\Models\ERPConnection;
use App\Models\PendingProduct;
use App\Models\PrestaShopShop;
use App\Services\Import\PublicationTargetService;
use Illuminate\Support\Facades\Log;

/**
 * ImportPanelPublicationTrait - FAZA 9.3 / GRUPA D
 *
 * Manages publication targets and scheduling for import panel.
 * Provides methods for:
 * - Listing available publication targets (ERPConnection instances + PrestaShop shops)
 * - Setting/updating publication targets per product
 * - Scheduling publication date/time
 * - Triggering immediate or scheduled publish
 *
 * GRUPA D: publication_targets JSON format:
 * {"erp_connections": [5, 8], "prestashop_shops": [1, 3]}
 * Default ERP (is_default=true) is ALWAYS included and cannot be unchecked.
 *
 * @package App\Http\Livewire\Products\Import\Traits
 */
trait ImportPanelPublicationTrait
{
    /**
     * Cached available targets for UI dropdowns
     */
    protected ?array $cachedAvailableTargets = null;

    /**
     * Cached active ERP connections for UI
     */
    protected ?array $cachedErpConnections = null;

    /**
     * Product ID awaiting unpublish confirmation (PPM modal).
     * When set, the confirmation modal is displayed.
     */
    public ?int $confirmUnpublishId = null;

    /**
     * Show PPM confirmation modal for unpublish action.
     */
    public function requestUnpublish(int $productId): void
    {
        $this->confirmUnpublishId = $productId;
    }

    /**
     * Cancel the unpublish confirmation modal.
     */
    public function cancelUnpublish(): void
    {
        $this->confirmUnpublishId = null;
    }

    /**
     * Confirm and execute unpublish (called from PPM modal).
     */
    public function confirmUnpublish(): void
    {
        if (!$this->confirmUnpublishId) {
            return;
        }

        $productId = $this->confirmUnpublishId;
        $this->confirmUnpublishId = null;

        $this->unpublishProduct($productId);
    }

    /**
     * Get all available publication targets for dropdowns
     *
     * @return array ['erp_connections' => [...], 'prestashop_shops' => [...]]
     */
    public function getAvailablePublicationTargets(): array
    {
        if ($this->cachedAvailableTargets !== null) {
            return $this->cachedAvailableTargets;
        }

        $service = app(PublicationTargetService::class);
        $this->cachedAvailableTargets = $service->getAvailableTargets();

        return $this->cachedAvailableTargets;
    }

    /**
     * Get active ERP connections for publication dropdown.
     * Cached per request to avoid repeated DB queries in product rows.
     *
     * @return array [['id' => int, 'instance_name' => string, 'erp_type' => string, 'is_default' => bool], ...]
     */
    public function getActiveErpConnections(): array
    {
        if ($this->cachedErpConnections !== null) {
            return $this->cachedErpConnections;
        }

        $this->cachedErpConnections = ERPConnection::active()
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->get(['id', 'instance_name', 'erp_type', 'is_default'])
            ->toArray();

        return $this->cachedErpConnections;
    }

    /**
     * Get the default ERP connection ID.
     * Returns null if no default is set.
     */
    public function getDefaultErpConnectionId(): ?int
    {
        $connections = $this->getActiveErpConnections();

        foreach ($connections as $connection) {
            if ($connection['is_default']) {
                return $connection['id'];
            }
        }

        return null;
    }

    /**
     * Get active PrestaShop shops for multi-select
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPrestaShopShops()
    {
        return PrestaShopShop::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'url']);
    }

    /**
     * Set publication targets for a single product
     *
     * @param int $productId PendingProduct ID
     * @param array $targets ['erp_connections' => int[], 'prestashop_shops' => int[]]
     */
    public function setPublicationTargets(int $productId, array $targets): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        // Ensure default ERP is always included
        $targets = $this->ensureDefaultErpIncluded($targets);

        $product->publication_targets = $targets;
        $product->save();

        Log::debug('ImportPanelPublicationTrait: targets updated', [
            'product_id' => $productId,
            'targets' => $targets,
        ]);

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Cele publikacji zaktualizowane',
        ]);
    }

    /**
     * Toggle an ERP connection in product's publication targets.
     * Default ERP (is_default=true) cannot be toggled off.
     *
     * @param int $productId PendingProduct ID
     * @param int $connectionId ERPConnection ID
     */
    public function toggleErpConnection(int $productId, int $connectionId): void
    {
        // Check if this is the default ERP - cannot toggle off
        $connection = ERPConnection::find($connectionId);
        if (!$connection) {
            return;
        }

        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $targets = $product->publication_targets ?? [];
        $erpConnections = $targets['erp_connections'] ?? [];

        // Default ERP cannot be removed
        if ($connection->is_default && in_array($connectionId, $erpConnections)) {
            return;
        }

        if (in_array($connectionId, $erpConnections)) {
            $erpConnections = array_values(array_diff($erpConnections, [$connectionId]));
        } else {
            $erpConnections[] = $connectionId;
        }

        $targets['erp_connections'] = $erpConnections;
        $product->publication_targets = $targets;
        $product->save();
    }

    /**
     * Toggle a PrestaShop shop in product's targets
     */
    public function togglePrestaShopShop(int $productId, int $shopId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $targets = $product->publication_targets ?? [];
        $shops = $targets['prestashop_shops'] ?? [];

        if (in_array($shopId, $shops)) {
            $shops = array_values(array_diff($shops, [$shopId]));
        } else {
            $shops[] = $shopId;
        }

        $targets['prestashop_shops'] = $shops;
        $product->publication_targets = $targets;
        $product->save();
    }

    /**
     * Cancel a scheduled publication for a product.
     * Resets scheduled_publish_at and sets status back to draft.
     *
     * @param int $productId PendingProduct ID
     */
    public function cancelScheduledPublication(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $product->scheduled_publish_at = null;
        if ($product->publish_status === 'scheduled') {
            $product->publish_status = 'draft';
        }
        $product->save();

        Log::info('ImportPanelPublicationTrait: scheduled publication cancelled', [
            'product_id' => $productId,
        ]);

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Zaplanowana publikacja anulowana',
        ]);
    }

    /**
     * Schedule publication for a product
     *
     * @param int $productId
     * @param string|null $datetime ISO date string or null for immediate
     */
    public function schedulePublication(int $productId, ?string $datetime): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        if ($datetime) {
            $product->scheduled_publish_at = $datetime;
            $product->publish_status = 'scheduled';

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zaplanowano publikacje na: ' . $product->scheduled_publish_at->format('d.m.Y H:i'),
            ]);
        } else {
            $product->scheduled_publish_at = null;
            if ($product->publish_status === 'scheduled') {
                $product->publish_status = 'draft';
            }
        }

        $product->save();

        Log::debug('ImportPanelPublicationTrait: schedule updated', [
            'product_id' => $productId,
            'scheduled_at' => $datetime,
            'status' => $product->publish_status,
        ]);
    }

    /**
     * Ensure default ERP connection is always included in targets.
     *
     * @param array $targets
     * @return array
     */
    protected function ensureDefaultErpIncluded(array $targets): array
    {
        $defaultId = $this->getDefaultErpConnectionId();
        if ($defaultId === null) {
            return $targets;
        }

        $erpConnections = $targets['erp_connections'] ?? [];
        if (!in_array($defaultId, $erpConnections)) {
            $erpConnections[] = $defaultId;
        }

        $targets['erp_connections'] = $erpConnections;

        return $targets;
    }

    /**
     * Get default publication targets for new products.
     * Includes default ERP connection automatically.
     *
     * @return array
     */
    public function getDefaultPublicationTargets(): array
    {
        $defaultId = $this->getDefaultErpConnectionId();
        $erpConnections = $defaultId ? [$defaultId] : [];

        return [
            'erp_connections' => $erpConnections,
            'prestashop_shops' => [],
        ];
    }

    /**
     * Publish product with configured targets
     *
     * For immediate publish or when scheduled time is reached.
     */
    public function publishWithTargets(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Produkt nie istnieje',
            ]);
            return;
        }

        // Validate readiness
        if (!$product->canPublish()) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Produkt nie jest gotowy do publikacji (completion < 100%)',
            ]);
            return;
        }

        // Update status
        $product->publish_status = 'publishing';
        $product->save();

        try {
            $service = app(\App\Services\Import\ProductPublicationService::class);
            $result = $service->publishSingle($product, dispatchSyncJobs: false);

            if ($result['success']) {
                $product->publish_status = 'published';
                $product->save();

                // Dispatch target-specific jobs
                $targetService = app(PublicationTargetService::class);
                $resolvedTargets = $targetService->resolveTargets($product->publication_targets);
                $targetService->dispatchSyncJobs($result['product'], $resolvedTargets);

                $this->dispatch('flash-message', [
                    'type' => 'success',
                    'message' => "Opublikowano: {$product->sku}",
                ]);
            } else {
                $product->publish_status = 'failed';
                $product->save();

                $this->dispatch('flash-message', [
                    'type' => 'error',
                    'message' => 'Blad publikacji: ' . implode(', ', $result['errors']),
                ]);
            }
        } catch (\Throwable $e) {
            $product->publish_status = 'failed';
            $product->save();

            Log::error('ImportPanelPublicationTrait: publish failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad publikacji: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get publish button state config for a product
     *
     * Used in blade to determine button appearance and behavior.
     *
     * @return array ['state', 'label', 'cssClass', 'disabled', 'countdown']
     */
    public function getPublishButtonState(PendingProduct $product): array
    {
        $completion = $product->completion_percentage ?? 0;
        $status = $product->publish_status ?? 'draft';

        // Published
        if ($status === 'published' || $product->isPublished()) {
            return [
                'state' => 'published',
                'label' => 'Opublikowano',
                'cssClass' => 'import-publish-btn-published',
                'disabled' => true,
                'countdown' => null,
                'productId' => $product->published_as_product_id,
            ];
        }

        // Publishing in progress
        if ($status === 'publishing') {
            return [
                'state' => 'publishing',
                'label' => 'Publikowanie...',
                'cssClass' => 'import-publish-btn-publishing',
                'disabled' => true,
                'countdown' => null,
            ];
        }

        // Failed
        if ($status === 'failed') {
            return [
                'state' => 'failed',
                'label' => 'Blad',
                'cssClass' => 'import-publish-btn-failed',
                'disabled' => false,
                'countdown' => null,
            ];
        }

        // Not ready (completion < 100%)
        if ($completion < 100) {
            return [
                'state' => 'incomplete',
                'label' => 'Publikuj',
                'cssClass' => 'import-publish-btn-disabled',
                'disabled' => true,
                'countdown' => null,
            ];
        }

        // Scheduled (100% + future date)
        if ($status === 'scheduled' && $product->scheduled_publish_at && $product->scheduled_publish_at->isFuture()) {
            return [
                'state' => 'scheduled',
                'label' => null, // countdown display
                'cssClass' => 'import-publish-btn-scheduled',
                'disabled' => true,
                'countdown' => $product->scheduled_publish_at->toIso8601String(),
            ];
        }

        // Ready (100%, no date or past date)
        return [
            'state' => 'ready',
            'label' => 'Publikuj',
            'cssClass' => 'import-publish-btn-ready',
            'disabled' => false,
            'countdown' => null,
        ];
    }

    /**
     * Get formatted publication targets display for a product
     *
     * @return array of badge configs: ['name' => string, 'type' => string, 'is_default' => bool]
     */
    public function getPublicationTargetBadges(PendingProduct $product): array
    {
        $badges = [];
        $targets = $product->publication_targets ?? [];

        // ERP Connections
        $erpConnectionIds = $targets['erp_connections'] ?? [];
        if (!empty($erpConnectionIds)) {
            $connections = ERPConnection::whereIn('id', $erpConnectionIds)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('priority')
                ->get(['id', 'instance_name', 'erp_type', 'is_default']);

            foreach ($connections as $conn) {
                $badges[] = [
                    'name' => $conn->instance_name,
                    'type' => 'erp',
                    'erp_type' => $conn->erp_type,
                    'is_default' => $conn->is_default,
                ];
            }
        }

        // Backward compatibility: support old erp_primary format
        if (empty($erpConnectionIds) && ($targets['erp_primary'] ?? false)) {
            $defaultConn = ERPConnection::default()->first();
            if ($defaultConn) {
                $badges[] = [
                    'name' => $defaultConn->instance_name,
                    'type' => 'erp',
                    'erp_type' => $defaultConn->erp_type,
                    'is_default' => true,
                ];
            }
        }

        // PrestaShop shops
        $shopIds = $targets['prestashop_shops'] ?? [];
        if (!empty($shopIds)) {
            $shops = PrestaShopShop::whereIn('id', $shopIds)
                ->where('is_active', true)
                ->get(['id', 'name']);

            foreach ($shops as $shop) {
                $badges[] = [
                    'name' => $shop->name,
                    'type' => 'prestashop',
                    'is_default' => false,
                ];
            }
        }

        return $badges;
    }

    /**
     * Unpublish a product - full rollback from PPM.
     * Requires import.unpublish permission.
     *
     * @param int $productId PendingProduct ID
     */
    public function unpublishProduct(int $productId): void
    {
        $user = auth()->user();
        if (!$user || !$user->can('import.unpublish')) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Brak uprawnien do cofania publikacji',
            ]);
            return;
        }

        try {
            $service = app(\App\Services\Import\ProductUnpublishService::class);
            $result = $service->unpublish($productId);

            if ($result['success']) {
                $this->dispatch('flash-message', [
                    'type' => 'success',
                    'message' => 'Publikacja cofnieta - produkt przywrocony do draft',
                ]);
            } else {
                $this->dispatch('flash-message', [
                    'type' => 'error',
                    'message' => 'Blad cofania: ' . implode(', ', $result['errors']),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('ImportPanelPublicationTrait: unpublish failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad cofania publikacji: ' . $e->getMessage(),
            ]);
        }
    }
}
