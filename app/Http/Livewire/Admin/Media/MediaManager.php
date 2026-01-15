<?php

namespace App\Http\Livewire\Admin\Media;

use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\Media\MediaManager as MediaManagerService;
use App\Services\Media\MediaSyncService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

/**
 * Admin Media Manager Panel
 *
 * ETAP_07d Phase 8: Admin Media Management
 * - Orphaned media detection
 * - Product search (SKU/nazwa)
 * - Product galleries listing
 * - Bulk actions (delete, assign, sync)
 */
class MediaManager extends Component
{
    use WithPagination, AuthorizesRequests;

    /**
     * Active tab: 'products' | 'orphaned' | 'sync'
     */
    public string $activeTab = 'products';

    /**
     * Search and filters
     */
    public string $search = '';
    public string $filterShop = '';
    public string $filterSyncStatus = '';
    public string $statFilter = 'all';
    public string $viewMode = 'grid';
    public string $sortBy = 'updated_at';
    public int $perPage = 12;

    /**
     * Selected items for bulk actions
     */
    public array $selectedMediaIds = [];
    public array $selectedProductIds = [];
    public bool $selectMode = false;

    /**
     * Stats
     */
    public array $stats = [
        'totalMedia' => 0,
        'orphanedMedia' => 0,
        'pendingSync' => 0,
        'syncErrors' => 0,
    ];

    /**
     * Loading state
     */
    public bool $isLoading = false;
    public ?string $message = null;
    public string $messageType = 'info';

    /**
     * Orphaned media filters
     */
    public string $orphanedSearch = '';
    public string $orphanedViewMode = 'grid';
    public int $orphanedPerPage = 24;

    /**
     * Assign Modal state
     */
    public bool $showAssignModal = false;
    public ?int $assignMediaId = null;
    public string $assignProductSearch = '';
    public ?int $assignSelectedProductId = null;
    public array $assignSearchResults = [];
    public bool $assignSetAsPrimary = false;
    public bool $bulkAssignMode = false;

    protected $queryString = ['search', 'activeTab', 'filterShop', 'filterSyncStatus', 'statFilter', 'viewMode', 'sortBy', 'perPage', 'orphanedPerPage'];

    public function mount(): void
    {
        // TODO: Re-enable after permissions seeder update
        // $this->authorize('admin.media.manage');
        $this->loadStats();
    }

    public function render()
    {
        $shops = PrestaShopShop::active()->get();

        return view('livewire.admin.media.media-manager', [
            'products' => $this->getProducts(),
            'orphanedMedia' => $this->getOrphanedMedia(),
            'shops' => $shops,
        ])->layout('layouts.admin');
    }

    /**
     * Load stats for dashboard
     */
    public function loadStats(): void
    {
        $this->stats = [
            'totalMedia' => Media::count(),
            'orphanedMedia' => Media::whereDoesntHave('mediable')->count(),
            'pendingSync' => Media::where('sync_status', 'pending')->count(),
            'syncErrors' => Media::where('sync_status', 'error')->count(),
        ];
    }

    /**
     * Get products with their media for gallery display
     */
    protected function getProducts()
    {
        $query = Product::query()
            ->with([
                'media' => fn($q) => $q->orderBy('is_primary', 'desc')->orderBy('sort_order'),
                'shopData.shop' => fn($q) => $q->select('id', 'name')
            ])
            ->withCount('media');

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filterSyncStatus)) {
            $status = $this->filterSyncStatus;
            $query->whereHas('media', fn($q) => $q->where('sync_status', $status));
        }

        // Sorting
        $orderColumn = match($this->sortBy) {
            'created_at' => 'created_at',
            'media_count' => 'media_count',
            'sku' => 'sku',
            'name' => 'name',
            default => 'updated_at',
        };

        $orderDirection = in_array($this->sortBy, ['sku', 'name']) ? 'asc' : 'desc';

        return $query->having('media_count', '>', 0)
                     ->orderBy($orderColumn, $orderDirection)
                     ->paginate($this->perPage);
    }

    /**
     * Get orphaned media (no product/variant association)
     * Uses whereDoesntHave to find media where the related model doesn't exist
     * (either mediable_type/id is NULL or points to deleted product/variant)
     */
    protected function getOrphanedMedia()
    {
        // Use same logic as stats - whereDoesntHave checks if the actual relationship exists
        $query = Media::whereDoesntHave('mediable');

        // Apply search filter
        if (!empty($this->orphanedSearch)) {
            $search = $this->orphanedSearch;
            $query->where('original_name', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')
                     ->paginate($this->orphanedPerPage);
    }

    /**
     * Switch tab
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * Reset pagination when perPage changes
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when orphanedPerPage changes
     */
    public function updatedOrphanedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when orphanedSearch changes
     */
    public function updatedOrphanedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle select mode
     */
    public function toggleSelectMode(): void
    {
        $this->selectMode = !$this->selectMode;
        if (!$this->selectMode) {
            $this->selectedMediaIds = [];
            $this->selectedProductIds = [];
        }
    }

    /**
     * Toggle media selection
     */
    public function toggleMediaSelection(int $mediaId): void
    {
        if (in_array($mediaId, $this->selectedMediaIds)) {
            $this->selectedMediaIds = array_diff($this->selectedMediaIds, [$mediaId]);
        } else {
            $this->selectedMediaIds[] = $mediaId;
        }
    }

    /**
     * Select all orphaned media on current page
     */
    public function selectAllOrphaned(): void
    {
        $this->selectMode = true;
        $orphanedMedia = $this->getOrphanedMedia();
        $this->selectedMediaIds = $orphanedMedia->pluck('id')->toArray();
    }

    /**
     * Deselect all orphaned media
     */
    public function deselectAllOrphaned(): void
    {
        $this->selectedMediaIds = [];
    }

    /**
     * Toggle product selection
     */
    public function toggleProductSelection(int $productId): void
    {
        if (in_array($productId, $this->selectedProductIds)) {
            $this->selectedProductIds = array_diff($this->selectedProductIds, [$productId]);
        } else {
            $this->selectedProductIds[] = $productId;
        }
    }

    /**
     * Delete orphaned media
     */
    public function deleteOrphanedMedia(int $mediaId): void
    {
        try {
            $media = Media::findOrFail($mediaId);

            // Verify it's actually orphaned
            if ($media->mediable) {
                $this->showMessage('To zdjecie jest przypisane do produktu', 'error');
                return;
            }

            /** @var MediaManagerService $service */
            $service = app(MediaManagerService::class);
            $service->delete($media, 'ppm_only');

            $this->loadStats();
            $this->showMessage('Zdjecie zostalo usuniete', 'success');
        } catch (\Exception $e) {
            Log::error('Failed to delete orphaned media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            $this->showMessage('Blad podczas usuwania: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Bulk delete selected orphaned media
     */
    public function bulkDeleteOrphaned(): void
    {
        if (empty($this->selectedMediaIds)) {
            $this->showMessage('Nie wybrano zadnych zdjec', 'warning');
            return;
        }

        $this->isLoading = true;

        try {
            /** @var MediaManagerService $service */
            $service = app(MediaManagerService::class);
            $deleted = 0;
            $errors = 0;

            foreach ($this->selectedMediaIds as $mediaId) {
                try {
                    $media = Media::find($mediaId);
                    if ($media) {
                        $service->delete($media, 'ppm_only');
                        $deleted++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::warning('Failed to delete media in bulk', [
                        'media_id' => $mediaId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->selectedMediaIds = [];
            $this->selectMode = false;
            $this->loadStats();

            $message = "Usunieto {$deleted} zdjec";
            if ($errors > 0) {
                $message .= " (bledy: {$errors})";
            }
            $this->showMessage($message, $errors > 0 ? 'warning' : 'success');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Sync all pending media to PrestaShop
     */
    public function syncPendingToShop(int $shopId): void
    {
        $this->isLoading = true;

        try {
            /** @var MediaSyncService $syncService */
            $syncService = app(MediaSyncService::class);

            $pendingMedia = Media::where('sync_status', 'pending')
                                 ->whereHas('mediable')
                                 ->limit(50)
                                 ->get();

            $synced = 0;
            $errors = 0;

            foreach ($pendingMedia as $media) {
                try {
                    $syncService->pushToShop($media, $shopId);
                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::warning('Failed to sync media to shop', [
                        'media_id' => $media->id,
                        'shop_id' => $shopId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->loadStats();

            $message = "Zsynchronizowano {$synced} zdjec";
            if ($errors > 0) {
                $message .= " (bledy: {$errors})";
            }
            $this->showMessage($message, $errors > 0 ? 'warning' : 'success');
        } catch (\Exception $e) {
            Log::error('Bulk sync failed', ['error' => $e->getMessage()]);
            $this->showMessage('Blad synchronizacji: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * View product gallery
     */
    public function viewProductGallery(int $productId): void
    {
        $this->redirect(route('admin.products.edit', $productId) . '?tab=gallery');
    }

    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterShop = '';
        $this->filterSyncStatus = '';
        $this->resetPage();
    }

    /**
     * Show message
     */
    protected function showMessage(string $message, string $type = 'info'): void
    {
        $this->message = $message;
        $this->messageType = $type;
    }

    /**
     * Clear message
     */
    public function clearMessage(): void
    {
        $this->message = null;
    }

    /**
     * Filter by stat card
     */
    public function filterByStat(string $filter): void
    {
        $this->statFilter = $filter;

        // Apply filter logic
        match($filter) {
            'orphaned' => $this->switchTab('orphaned'),
            'pending' => $this->applyPendingFilter(),
            'errors' => $this->applyErrorsFilter(),
            default => $this->clearStatFilters(),
        };
    }

    /**
     * Apply pending sync filter
     */
    protected function applyPendingFilter(): void
    {
        $this->switchTab('products');
        $this->filterSyncStatus = 'pending';
    }

    /**
     * Apply errors filter
     */
    protected function applyErrorsFilter(): void
    {
        $this->switchTab('products');
        $this->filterSyncStatus = 'error';
    }

    /**
     * Clear stat filters
     */
    protected function clearStatFilters(): void
    {
        $this->filterSyncStatus = '';
        $this->switchTab('products');
    }

    /**
     * Sync single product media
     */
    public function syncProductMedia(int $productId): void
    {
        try {
            $product = Product::with('media')->findOrFail($productId);

            if ($product->media->isEmpty()) {
                $this->showMessage('Brak zdjęć do synchronizacji', 'warning');
                return;
            }

            /** @var MediaSyncService $syncService */
            $syncService = app(MediaSyncService::class);
            $shops = PrestaShopShop::active()->get();

            $synced = 0;
            $errors = 0;

            foreach ($product->media as $media) {
                foreach ($shops as $shop) {
                    try {
                        $syncService->pushToShop($media, $shop->id);
                        $synced++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::warning('Failed to sync media', [
                            'media_id' => $media->id,
                            'shop_id' => $shop->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $this->loadStats();
            $message = "Zsynchronizowano {$synced} zdjęć";
            if ($errors > 0) {
                $message .= " (błędy: {$errors})";
            }
            $this->showMessage($message, $errors > 0 ? 'warning' : 'success');
        } catch (\Exception $e) {
            Log::error('Product media sync failed', ['error' => $e->getMessage()]);
            $this->showMessage('Błąd synchronizacji: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Bulk sync selected products
     */
    public function bulkSyncProducts(): void
    {
        if (empty($this->selectedProductIds)) {
            $this->showMessage('Nie wybrano żadnych produktów', 'warning');
            return;
        }

        $this->isLoading = true;

        try {
            /** @var MediaSyncService $syncService */
            $syncService = app(MediaSyncService::class);
            $shops = PrestaShopShop::active()->get();

            $synced = 0;
            $errors = 0;

            foreach ($this->selectedProductIds as $productId) {
                $product = Product::with('media')->find($productId);

                if (!$product || $product->media->isEmpty()) {
                    continue;
                }

                foreach ($product->media as $media) {
                    foreach ($shops as $shop) {
                        try {
                            $syncService->pushToShop($media, $shop->id);
                            $synced++;
                        } catch (\Exception $e) {
                            $errors++;
                            Log::warning('Bulk sync media failed', [
                                'media_id' => $media->id,
                                'shop_id' => $shop->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            $this->selectedProductIds = [];
            $this->selectMode = false;
            $this->loadStats();

            $message = "Zsynchronizowano {$synced} zdjęć";
            if ($errors > 0) {
                $message .= " (błędy: {$errors})";
            }
            $this->showMessage($message, $errors > 0 ? 'warning' : 'success');
        } catch (\Exception $e) {
            Log::error('Bulk products sync failed', ['error' => $e->getMessage()]);
            $this->showMessage('Błąd synchronizacji: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // ORPHANED MEDIA ASSIGNMENT METHODS
    // =========================================================================

    /**
     * Open assign modal for single media
     */
    public function openAssignModal(int $mediaId): void
    {
        $this->assignMediaId = $mediaId;
        $this->bulkAssignMode = false;
        $this->resetAssignModal();
        $this->showAssignModal = true;
    }

    /**
     * Open assign modal for bulk assignment
     */
    public function openBulkAssignModal(): void
    {
        if (empty($this->selectedMediaIds)) {
            $this->showMessage('Nie wybrano zadnych zdjec', 'warning');
            return;
        }
        $this->bulkAssignMode = true;
        $this->assignMediaId = null;
        $this->resetAssignModal();
        $this->showAssignModal = true;
    }

    /**
     * Reset assign modal state
     */
    protected function resetAssignModal(): void
    {
        $this->assignProductSearch = '';
        $this->assignSelectedProductId = null;
        $this->assignSearchResults = [];
        $this->assignSetAsPrimary = false;
    }

    /**
     * Close assign modal
     */
    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->resetAssignModal();
    }

    /**
     * Search products for assignment (real-time)
     */
    public function updatedAssignProductSearch(): void
    {
        if (strlen($this->assignProductSearch) < 2) {
            $this->assignSearchResults = [];
            return;
        }

        $search = $this->assignProductSearch;
        $this->assignSearchResults = Product::query()
            ->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            })
            ->withCount('media')
            ->orderBy('sku')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'media_count' => $p->media_count,
            ])
            ->toArray();
    }

    /**
     * Select product for assignment
     */
    public function selectAssignProduct(int $productId): void
    {
        $this->assignSelectedProductId = $productId;
    }

    /**
     * Confirm assignment (single or bulk)
     */
    public function confirmAssign(): void
    {
        if (!$this->assignSelectedProductId) {
            $this->showMessage('Wybierz produkt', 'warning');
            return;
        }

        $this->isLoading = true;

        try {
            $product = Product::findOrFail($this->assignSelectedProductId);
            $mediaIds = $this->bulkAssignMode ? $this->selectedMediaIds : [$this->assignMediaId];
            $assigned = 0;
            $errors = 0;

            // Determine sort order
            $maxSortOrder = $product->media()->max('sort_order') ?? 0;

            foreach ($mediaIds as $index => $mediaId) {
                try {
                    $media = Media::find($mediaId);
                    if (!$media) continue;

                    // Update media to assign to product
                    $media->mediable_type = Product::class;
                    $media->mediable_id = $product->id;
                    $media->sort_order = $maxSortOrder + $index + 1;

                    // Set as primary if requested and it's the first one
                    if ($this->assignSetAsPrimary && $index === 0) {
                        // Remove primary from other media
                        $product->media()->update(['is_primary' => false]);
                        $media->is_primary = true;
                    }

                    $media->save();
                    $assigned++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::warning('Failed to assign media to product', [
                        'media_id' => $mediaId,
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Clear selection
            if ($this->bulkAssignMode) {
                $this->selectedMediaIds = [];
                $this->selectMode = false;
            }

            $this->closeAssignModal();
            $this->loadStats();

            $message = "Przypisano {$assigned} zdjec do produktu {$product->sku}";
            if ($errors > 0) {
                $message .= " (bledy: {$errors})";
            }
            $this->showMessage($message, $errors > 0 ? 'warning' : 'success');
        } catch (\Exception $e) {
            Log::error('Media assignment failed', ['error' => $e->getMessage()]);
            $this->showMessage('Blad przypisywania: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get media info for modal display
     */
    public function getAssignMediaProperty(): ?Media
    {
        if (!$this->assignMediaId) return null;
        return Media::find($this->assignMediaId);
    }

    /**
     * Get selected product info
     */
    public function getAssignSelectedProductProperty(): ?array
    {
        if (!$this->assignSelectedProductId) return null;
        $product = Product::withCount('media')->find($this->assignSelectedProductId);
        if (!$product) return null;
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'media_count' => $product->media_count,
        ];
    }
}
