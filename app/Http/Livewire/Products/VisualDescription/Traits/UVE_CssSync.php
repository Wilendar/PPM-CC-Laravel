<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\CssSyncOrchestrator;
use Illuminate\Support\Facades\Log;

/**
 * UVE CSS Sync Trait
 *
 * Provides CSS synchronization functionality for Unified Visual Editor.
 * Integrates with CssSyncOrchestrator to sync styles to PrestaShop.
 *
 * ETAP_07f_P5 FAZA 5: CSS Synchronizacja
 *
 * Properties used (defined in parent component):
 * - $productId: int|null
 * - $shopId: int|null
 * - $description: ProductDescription|null
 * - $blocks: array
 */
trait UVE_CssSync
{
    // =====================
    // CSS SYNC STATE
    // =====================

    /** @var bool Whether CSS sync is in progress */
    public bool $cssSyncInProgress = false;

    /** @var string|null CSS sync status message */
    public ?string $cssSyncStatus = null;

    /** @var int CSS sync progress (0-100) */
    public int $cssSyncProgress = 0;

    /** @var string|null CSS sync error message */
    public ?string $cssSyncError = null;

    /** @var bool Whether to auto-sync CSS on save */
    public bool $autoSyncCss = true;

    /** @var array|null Last sync result */
    public ?array $lastSyncResult = null;

    // =====================
    // CSS SYNC METHODS
    // =====================

    /**
     * Trigger CSS synchronization.
     *
     * @param bool $force Force re-fetch from server
     */
    public function syncCss(bool $force = false): void
    {
        if (!$this->description) {
            $this->dispatch('notify', type: 'error', message: 'Brak opisu do synchronizacji CSS');
            return;
        }

        $this->cssSyncInProgress = true;
        $this->cssSyncStatus = 'Rozpoczynam synchronizacje...';
        $this->cssSyncProgress = 0;
        $this->cssSyncError = null;

        try {
            /** @var CssSyncOrchestrator $orchestrator */
            $orchestrator = app(CssSyncOrchestrator::class);

            // Validate shop config first
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                throw new \RuntimeException('Nie znaleziono sklepu');
            }

            $validation = $orchestrator->validateShopConfig($shop);
            if (!$validation['valid']) {
                throw new \RuntimeException(implode(', ', $validation['issues']));
            }

            // Run sync
            $result = $orchestrator->syncProductDescription($this->description, $force);

            $this->lastSyncResult = $result;
            $this->cssSyncProgress = 100;

            if ($result['status'] === CssSyncOrchestrator::STATUS_SUCCESS) {
                $this->cssSyncStatus = 'CSS zsynchronizowane pomyslnie';
                $this->dispatch('notify', type: 'success', message: 'CSS zsynchronizowane');
                $this->dispatch('css-sync-complete', result: $result);

            } elseif ($result['status'] === CssSyncOrchestrator::STATUS_SKIPPED) {
                $this->cssSyncStatus = $result['message'] ?? 'Synchronizacja pominieta';
                $this->dispatch('notify', type: 'info', message: $this->cssSyncStatus);

            } else {
                throw new \RuntimeException($result['error'] ?? 'Nieznany blad synchronizacji');
            }

        } catch (\Throwable $e) {
            Log::error('UVE_CssSync: Sync failed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            $this->cssSyncError = $e->getMessage();
            $this->cssSyncStatus = 'Blad synchronizacji';
            $this->dispatch('notify', type: 'error', message: 'Blad CSS: ' . $e->getMessage());
        }

        $this->cssSyncInProgress = false;
    }

    /**
     * Preview CSS that would be generated.
     */
    public function previewCss(): void
    {
        if (!$this->description) {
            $this->dispatch('notify', type: 'warning', message: 'Brak opisu do podgladu');
            return;
        }

        try {
            /** @var CssSyncOrchestrator $orchestrator */
            $orchestrator = app(CssSyncOrchestrator::class);

            $preview = $orchestrator->previewCss($this->description);

            $this->dispatch('show-css-preview', [
                'css' => $preview['css'],
                'rulesCount' => $preview['rules_count'],
                'size' => $preview['size'],
            ]);

        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Blad podgladu: ' . $e->getMessage());
        }
    }

    /**
     * Test CSS sync connection.
     */
    public function testCssConnection(): void
    {
        try {
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                throw new \RuntimeException('Nie znaleziono sklepu');
            }

            /** @var CssSyncOrchestrator $orchestrator */
            $orchestrator = app(CssSyncOrchestrator::class);

            $result = $orchestrator->testConnection($shop);

            if ($result['success']) {
                $this->dispatch('notify', type: 'success', message: 'Polaczenie CSS dziala poprawnie');
            } else {
                throw new \RuntimeException($result['error'] ?? 'Blad polaczenia');
            }

        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Test polaczenia: ' . $e->getMessage());
        }
    }

    /**
     * Toggle auto-sync CSS setting.
     */
    public function toggleAutoSyncCss(): void
    {
        $this->autoSyncCss = !$this->autoSyncCss;

        $status = $this->autoSyncCss ? 'wlaczona' : 'wylaczona';
        $this->dispatch('notify', type: 'info', message: "Auto-synchronizacja CSS {$status}");
    }

    /**
     * Get CSS sync configuration status for current shop.
     */
    public function getCssSyncConfig(): array
    {
        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            return [
                'enabled' => false,
                'reason' => 'Brak sklepu',
                'hasFtp' => false,
                'hasCssFiles' => false,
            ];
        }

        /** @var CssSyncOrchestrator $orchestrator */
        $orchestrator = app(CssSyncOrchestrator::class);
        $validation = $orchestrator->validateShopConfig($shop);

        return [
            'enabled' => $validation['valid'],
            'reason' => $validation['valid'] ? null : implode(', ', $validation['issues']),
            'hasFtp' => $validation['has_ftp'],
            'hasCssFiles' => $validation['has_css_files'],
            'issues' => $validation['issues'],
        ];
    }

    /**
     * Hook for save() method - sync CSS after save if enabled.
     *
     * Call this from save() method in main component.
     */
    protected function afterSaveCssSync(): void
    {
        if (!$this->autoSyncCss) {
            return;
        }

        $config = $this->getCssSyncConfig();
        if (!$config['enabled']) {
            Log::debug('UVE_CssSync: Auto-sync skipped - not configured', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'reason' => $config['reason'],
            ]);
            return;
        }

        // Trigger sync (will run in same request)
        $this->syncCss(force: false);
    }

    /**
     * Reset CSS sync state.
     */
    public function resetCssSyncState(): void
    {
        $this->cssSyncInProgress = false;
        $this->cssSyncStatus = null;
        $this->cssSyncProgress = 0;
        $this->cssSyncError = null;
        $this->lastSyncResult = null;
    }

    /**
     * Get last sync result summary.
     */
    public function getLastSyncSummary(): ?string
    {
        if (!$this->lastSyncResult) {
            return null;
        }

        $status = $this->lastSyncResult['status'] ?? 'unknown';

        return match ($status) {
            CssSyncOrchestrator::STATUS_SUCCESS => sprintf(
                'Zsynchronizowano %d bajtow CSS',
                $this->lastSyncResult['details']['merged_size'] ?? 0
            ),
            CssSyncOrchestrator::STATUS_SKIPPED => $this->lastSyncResult['message'] ?? 'Pominieto',
            CssSyncOrchestrator::STATUS_FAILED => 'Blad: ' . ($this->lastSyncResult['error'] ?? 'nieznany'),
            default => 'Status: ' . $status,
        };
    }

    /**
     * Check if CSS sync is available for current context.
     */
    public function isCssSyncAvailable(): bool
    {
        if (!$this->shopId) {
            return false;
        }

        $config = $this->getCssSyncConfig();
        return $config['enabled'];
    }
}
