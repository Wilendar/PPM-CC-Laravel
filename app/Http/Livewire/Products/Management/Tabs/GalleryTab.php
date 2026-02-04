<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Management\Tabs;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ERPConnection;
use App\DTOs\Media\MediaUploadDTO;
use App\Services\Media\MediaManager;
use App\Services\Media\MediaSyncService;
use App\Jobs\Media\SyncMediaFromPrestaShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * GalleryTab - Product Gallery Management Component
 *
 * Features:
 * - Display product images in gallery grid
 * - Upload new images (single/multiple/drag&drop)
 * - Set primary image
 * - Delete images (PPM/PrestaShop/Both)
 * - Sync with PrestaShop (pull/push)
 * - Live sync status labels
 *
 * Usage in ProductForm:
 * @include('livewire.products.management.tabs.gallery-tab')
 *
 * ETAP_07d Phase 5: Livewire Components
 * Max 250 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Http\Livewire\Products\Management\Tabs
 * @version 1.0
 */
class GalleryTab extends Component
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Product reference
    public ?int $productId = null;
    public ?Product $product = null;

    // Upload state
    public array $newPhotos = [];
    public array $folderUpload = [];
    public bool $isUploading = false;
    public array $uploadErrors = [];

    // Sync state
    public bool $isSyncing = false;
    public ?int $syncShopId = null;
    public array $syncStatus = [];

    // Pending shop changes - ETAP_07d: Deferred sync architecture
    public array $pendingShopChanges = []; // ['mediaId:shopId' => 'sync'|'unsync']

    // Delete confirmation
    public ?int $deleteMediaId = null;
    public string $deleteScope = 'ppm';

    // Selection for bulk operations
    public array $selectedIds = [];
    public bool $selectAll = false;

    // Lightbox state
    public ?string $lightboxUrl = null;
    public ?string $lightboxName = null;

    // Import Modal state (ETAP_07d: Advanced Import Modal)
    public bool $showImportModal = false;
    public array $importShopImages = [];      // ['shop_id' => ['images' => [...], 'error' => null]]
    public array $selectedImportImages = [];  // ['shop_id:image_id' => true]
    public array $selectedDeleteImages = [];  // ['shop_id:image_id' => true]
    public bool $isLoadingShopImages = false;
    public array $importModalShops = [];      // Shops to fetch images from

    // ERP Integration state (ETAP_08.6: Baselinker/Subiekt GT Integration)
    public array $erpConnections = [];        // Active ERP connections (Baselinker, etc.)
    public array $pendingErpChanges = [];     // ['mediaId:erpId' => 'sync'|'unsync']
    public array $erpSyncStatus = [];         // Status sync per media per ERP connection

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    private const MAX_IMAGES = 99;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component
     */
    public function mount(?int $productId = null): void
    {
        $this->productId = $productId;
        if ($productId) {
            $this->product = Product::find($productId);
            $this->loadSyncStatus();
            $this->loadErpConnections();
            $this->loadErpSyncStatus();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILE UPLOAD METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Handle file upload
     */
    public function updatedNewPhotos(): void
    {
        $this->uploadErrors = [];

        if (empty($this->newPhotos) || !$this->product) {
            return;
        }

        $remaining = self::MAX_IMAGES - $this->getMediaCount();
        if (count($this->newPhotos) > $remaining) {
            $this->uploadErrors[] = "Mozna dodac maksymalnie {$remaining} zdjec";
            return;
        }

        $this->processUpload($this->newPhotos);
    }

    /**
     * Handle folder upload
     */
    public function updatedFolderUpload(): void
    {
        $this->uploadErrors = [];

        if (empty($this->folderUpload) || !$this->product) {
            return;
        }

        $remaining = self::MAX_IMAGES - $this->getMediaCount();
        if (count($this->folderUpload) > $remaining) {
            $this->uploadErrors[] = "Mozna dodac maksymalnie {$remaining} zdjec";
            return;
        }

        $this->processUpload($this->folderUpload);
    }

    /**
     * Process file upload
     */
    protected function processUpload(array $files): void
    {
        $this->isUploading = true;

        try {
            $mediaManager = app(MediaManager::class);

            // Use uploadMultiple with correct parameters
            $uploadedMedia = $mediaManager->uploadMultiple(
                $files,
                'App\\Models\\Product',
                $this->productId,
                [
                    'convert_to_webp' => true,
                    'generate_thumbnails' => true,
                    'set_first_as_primary' => false,
                ]
            );

            $successCount = $uploadedMedia->count();

            if ($successCount > 0) {
                $this->dispatch('notify', ['type' => 'success', 'message' => "Dodano {$successCount} zdjec"]);
            }

            Log::info('[GALLERY TAB] Upload completed', [
                'product_id' => $this->productId,
                'success' => $successCount,
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Upload failed', ['error' => $e->getMessage()]);
            $this->uploadErrors[] = 'Blad uploadu: ' . $e->getMessage();
        } finally {
            $this->isUploading = false;
            $this->newPhotos = [];
            $this->folderUpload = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK SELECTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle select all
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedIds = $this->getMedia()->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    /**
     * Toggle single selection
     */
    public function toggleSelection(int $mediaId): void
    {
        if (in_array($mediaId, $this->selectedIds)) {
            $this->selectedIds = array_diff($this->selectedIds, [$mediaId]);
        } else {
            $this->selectedIds[] = $mediaId;
        }

        $this->selectAll = count($this->selectedIds) === $this->getMediaCount();
    }

    /**
     * Clear selection
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Bulk delete media
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) return;

        try {
            $deleted = 0;
            foreach ($this->selectedIds as $mediaId) {
                $media = Media::find($mediaId);
                if ($media) {
                    app(MediaManager::class)->delete($media, false, true);
                    $deleted++;
                }
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => "Usunieto {$deleted} zdjec"]);
            $this->clearSelection();

            Log::info('[GALLERY TAB] Bulk delete completed', ['deleted' => $deleted]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Bulk delete failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad usuwania']);
        }
    }

    /**
     * Bulk sync to PrestaShop
     */
    public function bulkSyncToPrestaShop(int $shopId): void
    {
        if (empty($this->selectedIds)) return;

        try {
            $shop = PrestaShopShop::findOrFail($shopId);
            $synced = 0;

            foreach ($this->selectedIds as $mediaId) {
                $media = Media::find($mediaId);
                if ($media && app(MediaSyncService::class)->pushToPrestaShop($media, $shop)) {
                    $synced++;
                }
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => "Wyslano {$synced} zdjec do {$shop->name}"]);
            $this->clearSelection();
            $this->loadSyncStatus();

            Log::info('[GALLERY TAB] Bulk sync completed', ['synced' => $synced, 'shop_id' => $shopId]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Bulk sync failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad synchronizacji']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Set image as primary
     */
    public function setPrimary(int $mediaId): void
    {
        try {
            $media = Media::findOrFail($mediaId);
            app(MediaManager::class)->setPrimary($media);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Zdjecie glowne ustawione']);
        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Set primary failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm delete
     */
    public function confirmDelete(int $mediaId, string $scope = 'ppm'): void
    {
        $this->deleteMediaId = $mediaId;
        $this->deleteScope = $scope;
    }

    /**
     * Execute delete
     */
    public function executeDelete(): void
    {
        if (!$this->deleteMediaId) return;

        try {
            $media = Media::findOrFail($this->deleteMediaId);
            $deletePs = in_array($this->deleteScope, ['prestashop', 'both']);
            $deletePpm = in_array($this->deleteScope, ['ppm', 'both']);

            app(MediaManager::class)->delete($media, $deletePs, $deletePpm);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Zdjecie usuniete']);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Delete failed', ['error' => $e->getMessage()]);
        } finally {
            $this->deleteMediaId = null;
        }
    }

    /**
     * Cancel delete
     */
    public function cancelDelete(): void
    {
        $this->deleteMediaId = null;
    }

    /**
     * Move image up in order
     */
    public function moveUp(int $mediaId): void
    {
        $this->reorderMedia($mediaId, -1);
    }

    /**
     * Move image down in order
     */
    public function moveDown(int $mediaId): void
    {
        $this->reorderMedia($mediaId, 1);
    }

    /**
     * Reorder media item
     */
    protected function reorderMedia(int $mediaId, int $direction): void
    {
        try {
            $allMedia = $this->getMedia();
            $currentIndex = $allMedia->search(fn($m) => $m->id === $mediaId);

            if ($currentIndex === false) return;

            $newIndex = $currentIndex + $direction;
            if ($newIndex < 0 || $newIndex >= $allMedia->count()) return;

            // Swap sort_order values
            $current = $allMedia[$currentIndex];
            $swap = $allMedia[$newIndex];

            $tempOrder = $current->sort_order;
            $current->sort_order = $swap->sort_order;
            $swap->sort_order = $tempOrder;

            $current->save();
            $swap->save();

            Log::info('[GALLERY TAB] Reordered media', ['media_id' => $mediaId, 'direction' => $direction]);
        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Reorder failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Open lightbox with full-size image
     */
    public function openLightbox(int $mediaId): void
    {
        try {
            $media = Media::findOrFail($mediaId);
            $this->lightboxUrl = $media->url;
            $this->lightboxName = $media->original_name ?? 'Zdjecie';
        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Open lightbox failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Close lightbox
     */
    public function closeLightbox(): void
    {
        $this->lightboxUrl = null;
        $this->lightboxName = null;
    }

    /**
     * Toggle shop assignment - LOCAL ONLY (deferred architecture)
     * NO API CALL - just mark intent in $pendingShopChanges
     * Also stores in session for cross-component access during save
     */
    public function toggleShopAssignment(int $mediaId, int $shopId): void
    {
        try {
            $media = Media::findOrFail($mediaId);
            $shop = PrestaShopShop::findOrFail($shopId);

            $key = "{$mediaId}:{$shopId}";
            $storeKey = 'store_' . $shopId;
            $mapping = $media->prestashop_mapping ?? [];
            $isSynced = isset($mapping[$storeKey]['ps_image_id']) && $mapping[$storeKey]['ps_image_id'];

            // Toggle intent
            if ($isSynced) {
                // Currently synced → mark for UNSYNC
                $this->pendingShopChanges[$key] = 'unsync';
                // Optimistically update local status
                if (isset($this->syncStatus[$mediaId][$storeKey])) {
                    $this->syncStatus[$mediaId][$storeKey]['pending_unsync'] = true;
                }
            } else {
                // Currently NOT synced → mark for SYNC
                $this->pendingShopChanges[$key] = 'sync';
                // Optimistically update local status
                $this->syncStatus[$mediaId][$storeKey] = [
                    'ps_image_id' => null,
                    'pending_sync' => true,
                ];
            }

            // Store in session for ProductForm to access during save
            $sessionKey = "pending_media_sync_{$this->productId}";
            session([$sessionKey => $this->pendingShopChanges]);

            Log::info('[GALLERY TAB] Shop assignment toggled (LOCAL)', [
                'media_id' => $mediaId,
                'shop_id' => $shopId,
                'action' => $this->pendingShopChanges[$key],
                'session_stored' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Toggle shop assignment failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad zmiany stanu']);
        }
    }

    /**
     * Apply pending shop changes - EXECUTE ALL API CALLS
     * Called manually via button OR on product save
     */
    public function applyPendingShopChanges(): void
    {
        if (empty($this->pendingShopChanges)) {
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Brak zmian do zastosowania']);
            return;
        }

        $this->isSyncing = true;
        $syncedCount = 0;
        $unsyncedCount = 0;
        $errors = [];

        try {
            $syncService = app(MediaSyncService::class);

            foreach ($this->pendingShopChanges as $key => $action) {
                [$mediaId, $shopId] = explode(':', $key);
                $media = Media::find($mediaId);
                $shop = PrestaShopShop::find($shopId);

                if (!$media || !$shop) {
                    $errors[] = "Nie znaleziono media {$mediaId} lub sklep {$shopId}";
                    continue;
                }

                try {
                    if ($action === 'sync') {
                        // Push to PrestaShop
                        $success = $syncService->pushToPrestaShop($media, $shop);
                        if ($success) {
                            $syncedCount++;
                        } else {
                            $errors[] = "Blad wysylania zdjecia {$mediaId} do {$shop->name}";
                        }
                    } elseif ($action === 'unsync') {
                        // Remove from PrestaShop
                        $success = $syncService->deleteFromPrestaShop($media, $shop);
                        if ($success) {
                            $unsyncedCount++;
                        } else {
                            $errors[] = "Blad usuwania zdjecia {$mediaId} z {$shop->name}";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Blad: {$e->getMessage()}";
                    Log::error('[GALLERY TAB] Apply pending change failed', [
                        'media_id' => $mediaId,
                        'shop_id' => $shopId,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear pending changes
            $this->pendingShopChanges = [];

            // Reload sync status from DB
            $this->loadSyncStatus();

            // Notify user
            $message = "Zastosowano zmiany: {$syncedCount} wyslano, {$unsyncedCount} usunieto";
            if (!empty($errors)) {
                $message .= ". Bledy: " . count($errors);
            }

            $this->dispatch('notify', [
                'type' => empty($errors) ? 'success' : 'warning',
                'message' => $message,
            ]);

            Log::info('[GALLERY TAB] Pending changes applied', [
                'synced' => $syncedCount,
                'unsynced' => $unsyncedCount,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Apply pending changes failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad zastosowania zmian']);
        } finally {
            $this->isSyncing = false;
        }
    }

    /**
     * Discard pending shop changes (reset to DB state)
     */
    public function discardPendingShopChanges(): void
    {
        $this->pendingShopChanges = [];
        $this->loadSyncStatus();
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Anulowano zmiany synchronizacji']);

        Log::info('[GALLERY TAB] Pending changes discarded');
    }

    /**
     * Check if there are any pending shop changes
     */
    public function hasPendingShopChanges(): bool
    {
        return !empty($this->pendingShopChanges);
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP SYNC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Pull images from PrestaShop (dispatch job)
     */
    public function pullFromShop(int $shopId): void
    {
        if (!$this->product) return;

        try {
            $shop = PrestaShopShop::findOrFail($shopId);

            // Dispatch job for background processing
            SyncMediaFromPrestaShop::dispatch(
                $this->productId,
                $shopId,
                auth()->id()
            );

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Pobieranie zdjec z {$shop->name} rozpoczete w tle",
            ]);

            // Refresh operations bar to show new job
            $this->dispatch('refresh-active-operations');

            Log::info('[GALLERY TAB] Media sync job dispatched', [
                'product_id' => $this->productId,
                'shop_id' => $shopId,
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Job dispatch failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad rozpoczecia synchronizacji']);
        }
    }

    /**
     * Push image to PrestaShop
     */
    public function pushToShop(int $mediaId, int $shopId): void
    {
        try {
            $media = Media::findOrFail($mediaId);
            $shop = PrestaShopShop::findOrFail($shopId);

            $success = app(MediaSyncService::class)->pushToPrestaShop($media, $shop);

            $this->dispatch('notify', [
                'type' => $success ? 'success' : 'error',
                'message' => $success ? 'Zdjecie wyslane' : 'Blad wysylania',
            ]);

            $this->loadSyncStatus();

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Push failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Load sync status for all media
     */
    protected function loadSyncStatus(): void
    {
        if (!$this->product) return;

        $this->syncStatus = [];
        foreach ($this->getMedia() as $media) {
            $this->syncStatus[$media->id] = $media->prestashop_mapping ?? [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT MODAL METHODS (ETAP_07d: Advanced Import Modal)
    |--------------------------------------------------------------------------
    */

    /**
     * Open import modal
     */
    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->importShopImages = [];
        $this->selectedImportImages = [];
        $this->selectedDeleteImages = [];
        $this->importModalShops = $this->getShops()->pluck('id')->toArray();
        $this->isLoadingShopImages = false;

        Log::info('[GALLERY TAB] Import modal opened', [
            'product_id' => $this->productId,
            'shops_count' => count($this->importModalShops),
        ]);
    }

    /**
     * Close import modal
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importShopImages = [];
        $this->selectedImportImages = [];
        $this->selectedDeleteImages = [];
        $this->isLoadingShopImages = false;
    }

    /**
     * Fetch images from selected shops
     */
    public function fetchShopImages(): void
    {
        if (!$this->product || empty($this->importModalShops)) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Wybierz sklepy do pobrania']);
            return;
        }

        $this->isLoadingShopImages = true;
        $this->importShopImages = [];

        try {
            foreach ($this->importModalShops as $shopId) {
                $shop = PrestaShopShop::find($shopId);
                if (!$shop) continue;

                try {
                    $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);

                    // Get PS product ID from shopData
                    $shopData = $this->product->shopData()->where('shop_id', $shopId)->first();
                    $psProductId = $shopData?->prestashop_product_id;

                    if (!$psProductId) {
                        $this->importShopImages[$shopId] = [
                            'shop_name' => $shop->name,
                            'images' => [],
                            'error' => 'Produkt nie jest powiazany z tym sklepem',
                        ];
                        continue;
                    }

                    // Fetch images from PrestaShop
                    $psImages = $client->getProductImages($psProductId);

                    if (empty($psImages)) {
                        $this->importShopImages[$shopId] = [
                            'shop_name' => $shop->name,
                            'images' => [],
                            'error' => null,
                        ];
                        continue;
                    }

                    // Get cover image info
                    $coverImageId = $client->getProductCoverImageId($psProductId);

                    // Build image data
                    $images = [];
                    foreach ($psImages as $psImage) {
                        $imageId = is_array($psImage) ? ($psImage['id'] ?? null) : (int) $psImage;
                        if (!$imageId) continue;

                        // Check if already exists in PPM
                        $existsInPpm = $this->imageExistsInPpm($shopId, $imageId);

                        $images[] = [
                            'id' => $imageId,
                            'url' => $client->getProductImageUrl($psProductId, $imageId),
                            'is_cover' => $imageId === $coverImageId,
                            'exists_in_ppm' => $existsInPpm,
                        ];
                    }

                    $this->importShopImages[$shopId] = [
                        'shop_name' => $shop->name,
                        'images' => $images,
                        'error' => null,
                        'ps_product_id' => $psProductId,
                    ];

                } catch (\Exception $e) {
                    Log::error('[GALLERY TAB] Failed to fetch images from shop', [
                        'shop_id' => $shopId,
                        'error' => $e->getMessage(),
                    ]);

                    $this->importShopImages[$shopId] = [
                        'shop_name' => $shop->name,
                        'images' => [],
                        'error' => 'Blad polaczenia: ' . $e->getMessage(),
                    ];
                }
            }

            Log::info('[GALLERY TAB] Shop images fetched', [
                'product_id' => $this->productId,
                'shops_fetched' => count($this->importShopImages),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Fetch shop images failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad pobierania zdjec']);
        } finally {
            $this->isLoadingShopImages = false;
        }
    }

    /**
     * Check if image from shop already exists in PPM
     */
    protected function imageExistsInPpm(int $shopId, int $psImageId): bool
    {
        return $this->product->media()
            ->whereJsonContains("prestashop_mapping->store_{$shopId}->ps_image_id", $psImageId)
            ->exists();
    }

    /**
     * Toggle image selection for import
     */
    public function toggleImportSelection(int $shopId, int $imageId): void
    {
        $key = "{$shopId}:{$imageId}";

        if (isset($this->selectedImportImages[$key])) {
            unset($this->selectedImportImages[$key]);
        } else {
            $this->selectedImportImages[$key] = true;
            // Remove from delete selection if selected for import
            unset($this->selectedDeleteImages[$key]);
        }
    }

    /**
     * Toggle image selection for deletion
     */
    public function toggleDeleteSelection(int $shopId, int $imageId): void
    {
        $key = "{$shopId}:{$imageId}";

        if (isset($this->selectedDeleteImages[$key])) {
            unset($this->selectedDeleteImages[$key]);
        } else {
            $this->selectedDeleteImages[$key] = true;
            // Remove from import selection if selected for delete
            unset($this->selectedImportImages[$key]);
        }
    }

    /**
     * Select all images for import
     */
    public function selectAllForImport(): void
    {
        $this->selectedImportImages = [];
        $this->selectedDeleteImages = [];

        foreach ($this->importShopImages as $shopId => $data) {
            foreach ($data['images'] ?? [] as $image) {
                if (!$image['exists_in_ppm']) {
                    $key = "{$shopId}:{$image['id']}";
                    $this->selectedImportImages[$key] = true;
                }
            }
        }
    }

    /**
     * Deselect all images
     */
    public function deselectAllImport(): void
    {
        $this->selectedImportImages = [];
        $this->selectedDeleteImages = [];
    }

    /**
     * Import selected images from PrestaShop
     */
    public function importSelectedImages(): void
    {
        if (empty($this->selectedImportImages)) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Nie zaznaczono zdjec do importu']);
            return;
        }

        $imported = 0;
        $errors = [];

        try {
            $syncService = app(MediaSyncService::class);

            foreach ($this->selectedImportImages as $key => $selected) {
                if (!$selected) continue;

                [$shopId, $imageId] = explode(':', $key);
                $shop = PrestaShopShop::find($shopId);

                if (!$shop) continue;

                $shopData = $this->importShopImages[$shopId] ?? null;
                if (!$shopData || !isset($shopData['ps_product_id'])) continue;

                try {
                    // Download and store the image
                    $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
                    $imageData = $client->downloadProductImage($shopData['ps_product_id'], (int) $imageId);

                    if (!$imageData) {
                        $errors[] = "Nie udalo sie pobrac zdjecia {$imageId} z {$shop->name}";
                        continue;
                    }

                    // Store in PPM
                    $storageService = app(\App\Services\Media\MediaStorageService::class);
                    $index = $storageService->getNextAvailableIndex($this->product);

                    if (!$index) {
                        $errors[] = 'Osiagnieto limit zdjec';
                        break;
                    }

                    $stored = $storageService->storeContents($imageData, $this->product, $index, 'jpg');

                    // Create Media record
                    $isCover = false;
                    foreach ($shopData['images'] as $img) {
                        if ($img['id'] == $imageId && $img['is_cover']) {
                            $isCover = true;
                            break;
                        }
                    }

                    $media = Media::create([
                        'mediable_type' => Product::class,
                        'mediable_id' => $this->product->id,
                        'file_name' => $stored['filename'],
                        'original_name' => "prestashop_{$imageId}.jpg",
                        'file_path' => $stored['path'],
                        'file_size' => $stored['size'],
                        'mime_type' => 'image/jpeg',
                        'sort_order' => $index,
                        'is_primary' => $isCover && $imported === 0,
                        'sync_status' => 'synced',
                        'is_active' => true,
                    ]);

                    // Save mapping (cast $shopId to int - array keys can be strings)
                    $media->setPrestaShopMapping((int) $shopId, [
                        'ps_product_id' => $shopData['ps_product_id'],
                        'ps_image_id' => (int) $imageId,
                        'is_cover' => $isCover,
                        'synced_at' => now()->toIso8601String(),
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Blad importu {$imageId}: " . $e->getMessage();
                    Log::error('[GALLERY TAB] Import image failed', [
                        'shop_id' => $shopId,
                        'image_id' => $imageId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Zaimportowano {$imported} zdjec";
            if (!empty($errors)) {
                $message .= ". Bledy: " . count($errors);
            }

            $this->dispatch('notify', [
                'type' => $imported > 0 ? 'success' : 'error',
                'message' => $message,
            ]);

            $this->loadSyncStatus();
            $this->closeImportModal();

            Log::info('[GALLERY TAB] Import completed', [
                'imported' => $imported,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Import failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad importu: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete selected images from PrestaShop
     */
    public function deleteSelectedFromPrestaShop(): void
    {
        if (empty($this->selectedDeleteImages)) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Nie zaznaczono zdjec do usuniecia']);
            return;
        }

        $deleted = 0;
        $errors = [];

        try {
            foreach ($this->selectedDeleteImages as $key => $selected) {
                if (!$selected) continue;

                [$shopId, $imageId] = explode(':', $key);
                $shop = PrestaShopShop::find($shopId);

                if (!$shop) continue;

                $shopData = $this->importShopImages[$shopId] ?? null;
                if (!$shopData || !isset($shopData['ps_product_id'])) continue;

                try {
                    $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
                    $client->deleteProductImage($shopData['ps_product_id'], (int) $imageId);
                    $deleted++;

                } catch (\Exception $e) {
                    $errors[] = "Blad usuwania {$imageId}: " . $e->getMessage();
                    Log::error('[GALLERY TAB] Delete from PS failed', [
                        'shop_id' => $shopId,
                        'image_id' => $imageId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Usunieto {$deleted} zdjec z PrestaShop";
            if (!empty($errors)) {
                $message .= ". Bledy: " . count($errors);
            }

            $this->dispatch('notify', [
                'type' => $deleted > 0 ? 'success' : 'error',
                'message' => $message,
            ]);

            // Refresh images
            $this->fetchShopImages();

            Log::info('[GALLERY TAB] Delete from PS completed', [
                'deleted' => $deleted,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Delete from PS failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad usuwania: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle shop selection for image fetch
     */
    public function toggleShopForFetch(int $shopId): void
    {
        if (in_array($shopId, $this->importModalShops)) {
            $this->importModalShops = array_diff($this->importModalShops, [$shopId]);
        } else {
            $this->importModalShops[] = $shopId;
        }
    }

    /**
     * Check if any selection has changed from initial state
     */
    public function hasSelectionChanged(): bool
    {
        return !empty($this->selectedImportImages) || !empty($this->selectedDeleteImages);
    }

    /**
     * Count total images to import
     */
    public function getImportCount(): int
    {
        return count(array_filter($this->selectedImportImages));
    }

    /**
     * Count total images to delete
     */
    public function getDeleteCount(): int
    {
        return count(array_filter($this->selectedDeleteImages));
    }

    /**
     * Get total images found across all shops
     */
    public function getTotalImagesFound(): int
    {
        $total = 0;
        foreach ($this->importShopImages as $data) {
            $total += count($data['images'] ?? []);
        }
        return $total;
    }

    /*
    |--------------------------------------------------------------------------
    | ERP INTEGRATION METHODS (ETAP_08.6: Baselinker/Subiekt GT)
    |--------------------------------------------------------------------------
    */

    /**
     * Load active ERP connections
     */
    protected function loadErpConnections(): void
    {
        $this->erpConnections = ERPConnection::where('is_active', true)
            ->orderBy('instance_name')
            ->get()
            ->toArray();
    }

    /**
     * Load ERP sync status for all media
     */
    protected function loadErpSyncStatus(): void
    {
        if (!$this->product) return;

        $this->erpSyncStatus = [];
        foreach ($this->getMedia() as $media) {
            $this->erpSyncStatus[$media->id] = $media->erp_mapping ?? [];
        }
    }

    /**
     * Toggle ERP assignment - LOCAL ONLY (deferred architecture)
     * NO API CALL - just mark intent in $pendingErpChanges
     */
    public function toggleErpAssignment(int $mediaId, int $erpConnectionId): void
    {
        try {
            $media = Media::findOrFail($mediaId);
            $connection = ERPConnection::findOrFail($erpConnectionId);

            $key = "{$mediaId}:{$erpConnectionId}";
            $connectionKey = "connection_{$erpConnectionId}";
            $mapping = $media->erp_mapping ?? [];
            $isSynced = isset($mapping[$connectionKey]['status']) &&
                        $mapping[$connectionKey]['status'] === 'synced';

            // Toggle intent
            if ($isSynced) {
                // Currently synced → mark for UNSYNC
                $this->pendingErpChanges[$key] = 'unsync';
                // Optimistically update local status
                if (isset($this->erpSyncStatus[$mediaId][$connectionKey])) {
                    $this->erpSyncStatus[$mediaId][$connectionKey]['pending_unsync'] = true;
                }
            } else {
                // Currently NOT synced → mark for SYNC
                $this->pendingErpChanges[$key] = 'sync';
                // Optimistically update local status
                $this->erpSyncStatus[$mediaId][$connectionKey] = [
                    'status' => null,
                    'pending_sync' => true,
                ];
            }

            Log::info('[GALLERY TAB] ERP assignment toggled (LOCAL)', [
                'media_id' => $mediaId,
                'erp_connection_id' => $erpConnectionId,
                'action' => $this->pendingErpChanges[$key],
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Toggle ERP assignment failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad zmiany stanu ERP']);
        }
    }

    /**
     * Apply pending ERP changes - EXECUTE ALL API CALLS
     */
    public function applyPendingErpChanges(): void
    {
        if (empty($this->pendingErpChanges)) {
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Brak zmian ERP do zastosowania']);
            return;
        }

        $syncedCount = 0;
        $unsyncedCount = 0;
        $errors = [];

        try {
            $baselinkerService = app(\App\Services\ERP\BaselinkerService::class);

            foreach ($this->pendingErpChanges as $key => $action) {
                [$mediaId, $erpConnectionId] = explode(':', $key);
                $media = Media::find($mediaId);
                $connection = ERPConnection::find($erpConnectionId);

                if (!$media || !$connection) {
                    $errors[] = "Nie znaleziono media {$mediaId} lub ERP {$erpConnectionId}";
                    continue;
                }

                try {
                    if ($action === 'sync') {
                        // Push image to ERP
                        $success = $this->syncMediaToErp($media, $connection);
                        if ($success) {
                            $syncedCount++;
                        } else {
                            $errors[] = "Blad wysylania zdjecia {$mediaId} do {$connection->name}";
                        }
                    } elseif ($action === 'unsync') {
                        // Remove from ERP
                        $media->clearErpMapping($connection->id);
                        $unsyncedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Blad: {$e->getMessage()}";
                    Log::error('[GALLERY TAB] Apply pending ERP change failed', [
                        'media_id' => $mediaId,
                        'erp_connection_id' => $erpConnectionId,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear pending changes
            $this->pendingErpChanges = [];

            // Reload ERP sync status from DB
            $this->loadErpSyncStatus();

            // Notify user
            $message = "Zastosowano zmiany ERP: {$syncedCount} wyslano, {$unsyncedCount} usunieto";
            if (!empty($errors)) {
                $message .= ". Bledy: " . count($errors);
            }

            $this->dispatch('notify', [
                'type' => empty($errors) ? 'success' : 'warning',
                'message' => $message,
            ]);

            Log::info('[GALLERY TAB] Pending ERP changes applied', [
                'synced' => $syncedCount,
                'unsynced' => $unsyncedCount,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Apply pending ERP changes failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Blad zastosowania zmian ERP']);
        }
    }

    /**
     * Sync single media to ERP connection
     */
    protected function syncMediaToErp(Media $media, ERPConnection $connection): bool
    {
        try {
            // Get product's Baselinker product ID
            $product = $media->mediable;
            if (!$product || !($product instanceof Product)) {
                return false;
            }

            $productErpData = $product->erpData()
                ->where('erp_connection_id', $connection->id)
                ->first();

            if (!$productErpData || !$productErpData->external_product_id) {
                Log::warning('[GALLERY TAB] Product not synced to ERP, cannot sync image', [
                    'product_id' => $product->id,
                    'erp_connection_id' => $connection->id,
                ]);
                return false;
            }

            // Get image URL
            $imageUrl = $media->url;
            if (!$imageUrl || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::warning('[GALLERY TAB] Invalid image URL', ['media_id' => $media->id]);
                return false;
            }

            // TODO: Implement actual Baselinker image sync via API
            // For now, just mark as synced (placeholder for full implementation)
            $media->markAsErpSynced($connection->id, [
                'product_id' => $productErpData->external_product_id,
                'image_url' => $imageUrl,
                'image_position' => $media->sort_order,
            ]);

            Log::info('[GALLERY TAB] Media synced to ERP', [
                'media_id' => $media->id,
                'erp_connection_id' => $connection->id,
                'product_id' => $productErpData->external_product_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Sync media to ERP failed', [
                'media_id' => $media->id,
                'erp_connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            $media->markErpSyncError($connection->id, $e->getMessage());
            return false;
        }
    }

    /**
     * Discard pending ERP changes (reset to DB state)
     */
    public function discardPendingErpChanges(): void
    {
        $this->pendingErpChanges = [];
        $this->loadErpSyncStatus();
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Anulowano zmiany ERP']);

        Log::info('[GALLERY TAB] Pending ERP changes discarded');
    }

    /**
     * Check if there are any pending ERP changes
     */
    public function hasPendingErpChanges(): bool
    {
        return !empty($this->pendingErpChanges);
    }

    /**
     * Get active ERP connections for display
     */
    public function getErpConnections(): array
    {
        return $this->erpConnections;
    }

    /*
    |--------------------------------------------------------------------------
    | DATA ACCESS
    |--------------------------------------------------------------------------
    */

    /**
     * Get media collection
     * Filters to show ONLY product_gallery context (excludes UVE/visual_description media)
     */
    public function getMedia(): Collection
    {
        if (!$this->productId) {
            return collect();
        }

        return Media::where('mediable_type', Product::class)
            ->where('mediable_id', $this->productId)
            ->where('is_active', true)
            ->forGallery() // CRITICAL: Exclude UVE media from product gallery
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get media count
     */
    public function getMediaCount(): int
    {
        return $this->getMedia()->count();
    }

    /**
     * Get available shops - ONLY shops linked to this product
     */
    public function getShops(): Collection
    {
        if (!$this->product) {
            return collect();
        }

        // Only return shops that are linked to this product via shopData relation
        $linkedShopIds = $this->product->shopData()->pluck('shop_id');

        if ($linkedShopIds->isEmpty()) {
            return collect();
        }

        return PrestaShopShop::active()->whereIn('id', $linkedShopIds)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    #[On('product-saved')]
    public function handleProductSaved(int $productId): void
    {
        $this->productId = $productId;
        $this->product = Product::find($productId);
        $this->loadSyncStatus();
    }

    /**
     * Handle before-product-save event - apply pending shop changes synchronously
     * This ensures all media sync happens BEFORE the product save completes
     */
    #[On('before-product-save')]
    public function handleBeforeProductSave(): void
    {
        if ($this->hasPendingShopChanges()) {
            Log::info('[GALLERY TAB] Applying pending shop changes before product save', [
                'product_id' => $this->productId,
                'pending_count' => count($this->pendingShopChanges),
            ]);

            // Apply pending changes synchronously (no notification dispatch - save will handle feedback)
            $this->applyPendingShopChangesQuietly();
        }
    }

    /**
     * Apply pending shop changes without UI notifications
     * Used when called from product save flow
     */
    protected function applyPendingShopChangesQuietly(): void
    {
        if (empty($this->pendingShopChanges)) {
            return;
        }

        $syncedCount = 0;
        $unsyncedCount = 0;
        $errors = [];

        try {
            $syncService = app(MediaSyncService::class);

            foreach ($this->pendingShopChanges as $key => $action) {
                [$mediaId, $shopId] = explode(':', $key);
                $media = Media::find($mediaId);
                $shop = PrestaShopShop::find($shopId);

                if (!$media || !$shop) {
                    $errors[] = "Media {$mediaId} or shop {$shopId} not found";
                    continue;
                }

                try {
                    if ($action === 'sync') {
                        $success = $syncService->pushToPrestaShop($media, $shop);
                        if ($success) {
                            $syncedCount++;
                        } else {
                            $errors[] = "Failed to push media {$mediaId} to {$shop->name}";
                        }
                    } elseif ($action === 'unsync') {
                        $success = $syncService->deleteFromPrestaShop($media, $shop);
                        if ($success) {
                            $unsyncedCount++;
                        } else {
                            $errors[] = "Failed to delete media {$mediaId} from {$shop->name}";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error: {$e->getMessage()}";
                    Log::error('[GALLERY TAB] Apply pending change failed (quiet)', [
                        'media_id' => $mediaId,
                        'shop_id' => $shopId,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear pending changes
            $this->pendingShopChanges = [];
            $this->loadSyncStatus();

            Log::info('[GALLERY TAB] Pending shop changes applied (quiet mode)', [
                'synced' => $syncedCount,
                'unsynced' => $unsyncedCount,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('[GALLERY TAB] Apply pending changes failed (quiet)', ['error' => $e->getMessage()]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.products.management.tabs.gallery-tab', [
            'media' => $this->getMedia(),
            'shops' => $this->getShops(),
            'mediaCount' => $this->getMediaCount(),
            'maxImages' => self::MAX_IMAGES,
            // ETAP_08.6: ERP Integration
            'erpConnections' => $this->erpConnections,
            'erpSyncStatus' => $this->erpSyncStatus,
            'hasPendingErpChanges' => $this->hasPendingErpChanges(),
        ]);
    }
}
