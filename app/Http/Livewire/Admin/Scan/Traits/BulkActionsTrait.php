<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Models\Product;
use App\Models\ProductScanResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait for bulk actions on scan results
 *
 * ETAP_10 FAZA 3: Product Scan System - Bulk Actions
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait BulkActionsTrait
{
    /**
     * Link selected results to existing PPM products by SKU
     */
    public function bulkLink(): void
    {
        if (empty($this->selectedResults)) {
            session()->flash('error', 'Wybierz wyniki do polaczenia');
            return;
        }

        $linked = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($this->selectedResults as $resultId) {
                $result = ProductScanResult::find($resultId);

                if (!$result || !$result->isPending()) {
                    continue;
                }

                // Find PPM product by SKU
                $product = Product::where('sku', $result->sku)->first();

                if ($product) {
                    if ($result->linkToProduct($product->id, auth()->id())) {
                        $linked++;
                    } else {
                        $failed++;
                    }
                } else {
                    $failed++;
                    Log::warning('BulkLink: No PPM product found for SKU', [
                        'result_id' => $resultId,
                        'sku' => $result->sku,
                    ]);
                }
            }

            DB::commit();

            $this->selectedResults = [];
            $this->selectAll = false;

            session()->flash('success', "Polaczono {$linked} produktow" . ($failed > 0 ? ", {$failed} bledow" : ''));

            Log::info('BulkLink completed', [
                'linked' => $linked,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BulkLink failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas laczenia produktow: ' . $e->getMessage());
        }
    }

    /**
     * Create selected results as PendingProducts
     */
    public function bulkCreate(): void
    {
        if (empty($this->selectedResults)) {
            session()->flash('error', 'Wybierz wyniki do importu');
            return;
        }

        $created = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($this->selectedResults as $resultId) {
                $result = ProductScanResult::find($resultId);

                if (!$result || !$result->isPending()) {
                    continue;
                }

                $pendingProduct = $result->createAsPendingProduct(auth()->id());

                if ($pendingProduct) {
                    $created++;
                } else {
                    $failed++;
                }
            }

            DB::commit();

            $this->selectedResults = [];
            $this->selectAll = false;

            session()->flash('success', "Utworzono {$created} draftow produktow" . ($failed > 0 ? ", {$failed} bledow" : ''));

            Log::info('BulkCreate completed', [
                'created' => $created,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BulkCreate failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas tworzenia produktow: ' . $e->getMessage());
        }
    }

    /**
     * Ignore selected results
     */
    public function bulkIgnore(): void
    {
        if (empty($this->selectedResults)) {
            session()->flash('error', 'Wybierz wyniki do zignorowania');
            return;
        }

        $ignored = 0;

        DB::beginTransaction();
        try {
            foreach ($this->selectedResults as $resultId) {
                $result = ProductScanResult::find($resultId);

                if (!$result || !$result->isPending()) {
                    continue;
                }

                $result->markAsIgnored('Bulk ignore', auth()->id());
                $ignored++;
            }

            DB::commit();

            $this->selectedResults = [];
            $this->selectAll = false;

            session()->flash('success', "Zignorowano {$ignored} wynikow");

            Log::info('BulkIgnore completed', [
                'ignored' => $ignored,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BulkIgnore failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas ignorowania wynikow');
        }
    }

    /**
     * Link single result to product
     *
     * @param int $resultId
     * @param int $productId
     */
    public function linkResult(int $resultId, int $productId): void
    {
        $result = ProductScanResult::find($resultId);

        if (!$result) {
            session()->flash('error', 'Nie znaleziono wyniku');
            return;
        }

        if ($result->linkToProduct($productId, auth()->id())) {
            session()->flash('success', 'Produkt zostal polaczony');
        } else {
            session()->flash('error', 'Blad podczas laczenia produktu');
        }
    }

    /**
     * Create single result as PendingProduct
     *
     * @param int $resultId
     */
    public function createResult(int $resultId): void
    {
        $result = ProductScanResult::find($resultId);

        if (!$result) {
            session()->flash('error', 'Nie znaleziono wyniku');
            return;
        }

        $pendingProduct = $result->createAsPendingProduct(auth()->id());

        if ($pendingProduct) {
            session()->flash('success', 'Utworzono draft produktu');
        } else {
            session()->flash('error', 'Blad podczas tworzenia draftu');
        }
    }

    /**
     * Ignore single result
     *
     * @param int $resultId
     */
    public function ignoreResult(int $resultId): void
    {
        $result = ProductScanResult::find($resultId);

        if (!$result) {
            session()->flash('error', 'Nie znaleziono wyniku');
            return;
        }

        $result->markAsIgnored(null, auth()->id());
        session()->flash('success', 'Wynik zostal zignorowany');
    }

    /**
     * Toggle select all results
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            // Get all pending result IDs from current filter
            $this->selectedResults = $this->getFilteredResults()
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedResults = [];
        }
    }

    /**
     * Update selectAll state based on selection
     */
    public function updatedSelectedResults(): void
    {
        $totalPending = $this->getFilteredResults()->count();
        $this->selectAll = count($this->selectedResults) === $totalPending && $totalPending > 0;
    }

    /**
     * Publish single result to source (ERP/PrestaShop)
     *
     * @param int $resultId
     */
    public function publishToSource(int $resultId): void
    {
        $result = ProductScanResult::find($resultId);

        if (!$result) {
            session()->flash('error', 'Nie znaleziono wyniku');
            return;
        }

        if (!$result->ppm_product_id) {
            session()->flash('error', 'Brak powiazanego produktu PPM');
            return;
        }

        // TODO: Implement actual publication to source
        // This will require integration with existing sync jobs:
        // - For PrestaShop: SyncShopVariantsToPrestaShopJob
        // - For ERP: SyncProductsToErpJob (to be implemented)

        session()->flash('info', 'Funkcja publikacji do zrodla bedzie dostepna wkrotce. Uzyj formularza produktu do synchronizacji.');

        Log::info('PublishToSource requested', [
            'result_id' => $resultId,
            'product_id' => $result->ppm_product_id,
            'source_type' => $result->external_source_type,
            'source_id' => $result->external_source_id,
        ]);
    }

    /**
     * Bulk publish selected results to source
     */
    public function bulkPublishToSource(): void
    {
        if (empty($this->selectedResults)) {
            session()->flash('error', 'Wybierz wyniki do publikacji');
            return;
        }

        // TODO: Implement bulk publication
        session()->flash('info', 'Masowa publikacja do zrodla bedzie dostepna wkrotce.');

        Log::info('BulkPublishToSource requested', [
            'count' => count($this->selectedResults),
        ]);
    }
}
