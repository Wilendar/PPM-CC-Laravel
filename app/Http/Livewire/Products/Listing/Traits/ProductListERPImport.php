<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\ERPConnection;
use App\Models\SyncJob;
use App\Models\JobProgress;
use App\Jobs\ERP\BaselinkerSyncJob;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ProductListERPImport Trait
 *
 * FAZA 10: Import z ERP w ProductList
 *
 * Dodaje funkcjonalnosc importu produktow z podlaczonych systemow ERP
 * (BaseLinker, Subiekt GT, Microsoft Dynamics) do ProductList.
 *
 * UWAGA: Uzywa JobProgress dla progress bar (jak PrestaShop import)
 *
 * FIXED: wire:model.live na textarea
 * ADDED: Tryby wyszukiwania (ID, SKU, Nazwa)
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 * @since ETAP_08 - ERP Integration
 */
trait ProductListERPImport
{
    /*
    |--------------------------------------------------------------------------
    | ERP IMPORT PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Show/hide ERP import modal.
     */
    public bool $showERPImportModal = false;

    /**
     * Selected ERP connection ID.
     */
    public ?int $selectedERPConnectionId = null;

    /**
     * Import mode: 'all' or 'individual'.
     */
    public string $erpImportMode = 'all';

    /**
     * Search type for individual import: 'id', 'sku', 'name'.
     */
    public string $erpSearchType = 'id';

    /**
     * Comma-separated product IDs or SKUs for individual import (id/sku mode).
     */
    public string $erpProductIds = '';

    /**
     * Search query for name search mode.
     */
    public string $erpSearchQuery = '';

    /**
     * Search results from ERP (for name search).
     */
    public array $erpSearchResults = [];

    /**
     * Selected products from search results.
     */
    public array $selectedERPProducts = [];

    /**
     * Loading state for import operation.
     */
    public bool $erpImportLoading = false;

    /*
    |--------------------------------------------------------------------------
    | MODAL CONTROL
    |--------------------------------------------------------------------------
    */

    /**
     * Open ERP import modal.
     */
    public function openERPImportModal(): void
    {
        $this->showERPImportModal = true;
        $this->selectedERPConnectionId = null;
        $this->erpImportMode = 'all';
        $this->erpSearchType = 'id';
        $this->erpProductIds = '';
        $this->erpSearchQuery = '';
        $this->erpSearchResults = [];
        $this->selectedERPProducts = [];
        $this->erpImportLoading = false;
    }

    /**
     * Close ERP import modal.
     */
    public function closeERPImportModal(): void
    {
        $this->showERPImportModal = false;
        $this->reset([
            'selectedERPConnectionId',
            'erpImportMode',
            'erpSearchType',
            'erpProductIds',
            'erpSearchQuery',
            'erpSearchResults',
            'selectedERPProducts',
            'erpImportLoading',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE HOOKS
    |--------------------------------------------------------------------------
    */

    /**
     * Called when erpSearchQuery changes (name search).
     */
    public function updatedErpSearchQuery(): void
    {
        if ($this->erpSearchType !== 'name') {
            return;
        }

        if (strlen($this->erpSearchQuery) < 3) {
            $this->erpSearchResults = [];
            return;
        }

        $this->searchERPProducts();
    }

    /**
     * Called when erpSearchType changes - reset relevant fields.
     */
    public function updatedErpSearchType(): void
    {
        $this->erpProductIds = '';
        $this->erpSearchQuery = '';
        $this->erpSearchResults = [];
        $this->selectedERPProducts = [];
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Search products in ERP by name.
     */
    protected function searchERPProducts(): void
    {
        if (!$this->selectedERPConnectionId || strlen($this->erpSearchQuery) < 3) {
            return;
        }

        $connection = ERPConnection::find($this->selectedERPConnectionId);
        if (!$connection) {
            return;
        }

        try {
            $service = app(\App\Services\ERP\BaselinkerService::class);

            // Use searchProducts method if available, otherwise return empty
            // Note: BaselinkerService needs searchProducts method
            $results = $service->searchProducts($connection, $this->erpSearchQuery);

            $this->erpSearchResults = $results['products'] ?? [];

        } catch (\Exception $e) {
            Log::error('ERP product search failed', [
                'connection_id' => $connection->id,
                'query' => $this->erpSearchQuery,
                'error' => $e->getMessage(),
            ]);
            $this->erpSearchResults = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Import all products from selected ERP connection.
     */
    public function importAllFromERP(): void
    {
        if (!$this->selectedERPConnectionId) {
            session()->flash('error', 'Wybierz polaczenie ERP.');
            return;
        }

        $connection = ERPConnection::find($this->selectedERPConnectionId);

        if (!$connection) {
            session()->flash('error', 'Nie znaleziono polaczenia ERP.');
            return;
        }

        if (!$connection->is_active) {
            session()->flash('error', 'Polaczenie ERP jest nieaktywne.');
            return;
        }

        $this->erpImportLoading = true;

        try {
            // Generate unique job ID
            $jobId = Str::uuid()->toString();

            // Create PENDING JobProgress record BEFORE dispatch (like PrestaShop import)
            // This ensures progress bar appears IMMEDIATELY
            $jobProgress = JobProgress::create([
                'job_id' => $jobId,
                'job_type' => 'erp_import',
                'shop_id' => null, // ERP import has no PrestaShop shop
                'user_id' => Auth::id(),
                'status' => 'pending',
                'current_count' => 0,
                'total_count' => 0, // Will be updated when job starts
                'error_count' => 0,
                'error_details' => [],
                'metadata' => [
                    'mode' => 'all',
                    'erp_type' => $connection->erp_type,
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->instance_name,
                    'initiated_by' => auth()->user()?->name ?? 'System',
                    'phase' => 'queued',
                    'phase_label' => 'W kolejce - oczekuje na uruchomienie',
                ],
                'started_at' => now(),
            ]);

            // Create SyncJob for ERP tracking
            $syncJob = SyncJob::create([
                'job_id' => $jobId,
                'job_type' => 'import_products',
                'job_name' => "Import z {$connection->instance_name}",
                'source_type' => $connection->erp_type,
                'source_id' => (string) $connection->id,
                'target_type' => 'ppm',
                'target_id' => 'products',
                'status' => SyncJob::STATUS_PENDING,
                'job_config' => [
                    'connection_id' => $connection->id,
                    'sync_type' => 'pull',
                    'import_mode' => 'all',
                    'job_progress_id' => $jobProgress->id, // Link to JobProgress
                ],
                'user_id' => Auth::id(),
                'trigger_type' => 'manual',
            ]);

            // Dispatch job
            BaselinkerSyncJob::dispatch($syncJob);

            Log::info('ERP Import Job dispatched with JobProgress', [
                'job_id' => $jobId,
                'job_progress_id' => $jobProgress->id,
                'sync_job_id' => $syncJob->id,
                'connection_id' => $connection->id,
                'connection_name' => $connection->instance_name,
                'import_mode' => 'all',
            ]);

            $this->closeERPImportModal();

            $this->dispatch('success', message: "Import z {$connection->instance_name} uruchomiony. Postep widoczny w belce 'Aktywne operacje'.");

        } catch (\Exception $e) {
            Log::error('ERP Import failed to start', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Nie udalo sie uruchomic importu: ' . $e->getMessage());
        } finally {
            $this->erpImportLoading = false;
        }
    }

    /**
     * Import selected products from ERP.
     */
    public function importSelectedFromERP(): void
    {
        if (!$this->selectedERPConnectionId) {
            session()->flash('error', 'Wybierz polaczenie ERP.');
            return;
        }

        $connection = ERPConnection::find($this->selectedERPConnectionId);

        if (!$connection) {
            session()->flash('error', 'Nie znaleziono polaczenia ERP.');
            return;
        }

        if (!$connection->is_active) {
            session()->flash('error', 'Polaczenie ERP jest nieaktywne.');
            return;
        }

        // Determine what to import based on search type
        $productIdentifiers = [];
        $searchType = $this->erpSearchType;

        if ($searchType === 'name') {
            // Name search - use selected products from search results
            $productIdentifiers = $this->selectedERPProducts;
        } else {
            // ID or SKU - parse from textarea
            $productIdentifiers = array_filter(
                array_map('trim', explode(',', $this->erpProductIds)),
                fn($id) => !empty($id)
            );
        }

        if (empty($productIdentifiers)) {
            session()->flash('error', 'Podaj produkty do importu.');
            return;
        }

        $this->erpImportLoading = true;

        try {
            // Generate unique job ID
            $jobId = Str::uuid()->toString();

            // Create PENDING JobProgress record BEFORE dispatch
            $jobProgress = JobProgress::create([
                'job_id' => $jobId,
                'job_type' => 'erp_import',
                'shop_id' => null,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'current_count' => 0,
                'total_count' => count($productIdentifiers),
                'error_count' => 0,
                'error_details' => [],
                'metadata' => [
                    'mode' => 'individual',
                    'search_type' => $searchType,
                    'erp_type' => $connection->erp_type,
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->instance_name,
                    'product_count' => count($productIdentifiers),
                    'initiated_by' => auth()->user()?->name ?? 'System',
                    'phase' => 'queued',
                    'phase_label' => 'W kolejce - oczekuje na uruchomienie',
                ],
                'started_at' => now(),
            ]);

            // Create SyncJob with filters
            $syncJob = SyncJob::create([
                'job_id' => $jobId,
                'job_type' => 'import_products',
                'job_name' => "Import wybranych z {$connection->instance_name} (" . count($productIdentifiers) . " szt.)",
                'source_type' => $connection->erp_type,
                'source_id' => (string) $connection->id,
                'target_type' => 'ppm',
                'target_id' => 'products',
                'status' => SyncJob::STATUS_PENDING,
                'total_items' => count($productIdentifiers),
                'job_config' => [
                    'connection_id' => $connection->id,
                    'sync_type' => 'pull',
                    'import_mode' => 'individual',
                    'search_type' => $searchType,
                    'job_progress_id' => $jobProgress->id,
                ],
                'filters' => [
                    'search_type' => $searchType,
                    'product_ids' => $searchType === 'id' ? $productIdentifiers : null,
                    'product_skus' => $searchType === 'sku' ? $productIdentifiers : null,
                    'selected_products' => $searchType === 'name' ? $productIdentifiers : null,
                ],
                'user_id' => Auth::id(),
                'trigger_type' => 'manual',
            ]);

            // Dispatch job
            BaselinkerSyncJob::dispatch($syncJob);

            Log::info('ERP Import Job dispatched (individual) with JobProgress', [
                'job_id' => $jobId,
                'job_progress_id' => $jobProgress->id,
                'sync_job_id' => $syncJob->id,
                'connection_id' => $connection->id,
                'search_type' => $searchType,
                'product_count' => count($productIdentifiers),
            ]);

            $this->closeERPImportModal();

            $this->dispatch('success', message: "Import " . count($productIdentifiers) . " produktow z {$connection->instance_name} uruchomiony. Postep widoczny w belce 'Aktywne operacje'.");

        } catch (\Exception $e) {
            Log::error('ERP Import (individual) failed to start', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Nie udalo sie uruchomic importu: ' . $e->getMessage());
        } finally {
            $this->erpImportLoading = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get available active ERP connections.
     */
    #[Computed]
    public function availableERPConnections()
    {
        return ERPConnection::where('is_active', true)
            ->orderBy('priority')
            ->orderBy('instance_name')
            ->get();
    }

    /**
     * Get selected ERP connection details.
     */
    #[Computed]
    public function selectedERPConnection()
    {
        if (!$this->selectedERPConnectionId) {
            return null;
        }

        return ERPConnection::find($this->selectedERPConnectionId);
    }
}
