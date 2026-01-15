<?php

declare(strict_types=1);

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Media;
use App\Models\Product;
use App\Services\Media\MediaManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * MediaGalleryGrid - Reusable Gallery Display Component
 *
 * Features:
 * - Display media in grid layout
 * - Set primary image
 * - Delete image (PPM/PrestaShop/Both)
 * - Reorder images (drag & drop)
 * - Multi-select for bulk actions
 * - Live sync status labels
 *
 * Usage:
 * <livewire:components.media-gallery-grid
 *     :mediableType="Product::class"
 *     :mediableId="$productId"
 * />
 *
 * ETAP_07d Phase 5: Livewire Components
 * Max 250 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Http\Livewire\Components
 * @version 1.0
 */
class MediaGalleryGrid extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Required props
    public string $mediableType;
    public int $mediableId;

    // Gallery state
    public array $selectedIds = [];
    public bool $selectMode = false;
    public ?int $confirmDeleteId = null;
    public string $deleteScope = 'ppm'; // ppm, prestashop, both

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component
     */
    public function mount(string $mediableType, int $mediableId): void
    {
        $this->mediableType = $mediableType;
        $this->mediableId = $mediableId;
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Set image as primary
     */
    public function setPrimary(int $mediaId): void
    {
        try {
            $mediaManager = app(MediaManager::class);
            $media = Media::findOrFail($mediaId);

            $mediaManager->setPrimary($media);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Zdjecie ustawione jako glowne',
            ]);

            Log::info('[MEDIA GALLERY] Primary set', ['media_id' => $mediaId]);

        } catch (\Exception $e) {
            Log::error('[MEDIA GALLERY] Set primary failed', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udalo sie ustawic zdjecia glownego',
            ]);
        }
    }

    /**
     * Show delete confirmation
     */
    public function confirmDelete(int $mediaId, string $scope = 'ppm'): void
    {
        $this->confirmDeleteId = $mediaId;
        $this->deleteScope = $scope;
    }

    /**
     * Cancel delete
     */
    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->deleteScope = 'ppm';
    }

    /**
     * Execute delete
     */
    public function executeDelete(): void
    {
        if (!$this->confirmDeleteId) {
            return;
        }

        try {
            $mediaManager = app(MediaManager::class);
            $media = Media::findOrFail($this->confirmDeleteId);

            $deleteFromPrestaShop = in_array($this->deleteScope, ['prestashop', 'both']);
            $deleteFromPpm = in_array($this->deleteScope, ['ppm', 'both']);

            $mediaManager->delete($media, $deleteFromPrestaShop, $deleteFromPpm);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Zdjecie usuniete',
            ]);

            $this->dispatch('media-deleted', ['mediaId' => $this->confirmDeleteId]);

            Log::info('[MEDIA GALLERY] Delete executed', [
                'media_id' => $this->confirmDeleteId,
                'scope' => $this->deleteScope,
            ]);

        } catch (\Exception $e) {
            Log::error('[MEDIA GALLERY] Delete failed', [
                'media_id' => $this->confirmDeleteId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udalo sie usunac zdjecia',
            ]);
        } finally {
            $this->cancelDelete();
        }
    }

    /**
     * Toggle selection mode
     */
    public function toggleSelectMode(): void
    {
        $this->selectMode = !$this->selectMode;
        if (!$this->selectMode) {
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
    }

    /**
     * Select all
     */
    public function selectAll(): void
    {
        $this->selectedIds = $this->getMedia()->pluck('id')->toArray();
    }

    /**
     * Clear selection
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    /**
     * Bulk delete selected
     */
    public function bulkDelete(string $scope = 'ppm'): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $mediaManager = app(MediaManager::class);
            $media = Media::whereIn('id', $this->selectedIds)->get();

            $deleteFromPrestaShop = in_array($scope, ['prestashop', 'both']);
            $deleteFromPpm = in_array($scope, ['ppm', 'both']);

            $mediaManager->deleteMultiple($media, $deleteFromPrestaShop, $deleteFromPpm);

            $count = count($this->selectedIds);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Usunieto {$count} zdjec",
            ]);

            $this->dispatch('media-bulk-deleted', ['count' => $count]);

            Log::info('[MEDIA GALLERY] Bulk delete executed', [
                'count' => $count,
                'scope' => $scope,
            ]);

        } catch (\Exception $e) {
            Log::error('[MEDIA GALLERY] Bulk delete failed', ['error' => $e->getMessage()]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udalo sie usunac zdjec',
            ]);
        } finally {
            $this->selectedIds = [];
            $this->selectMode = false;
        }
    }

    /**
     * Update sort order (drag & drop)
     */
    public function updateOrder(array $orderedIds): void
    {
        try {
            $mediaManager = app(MediaManager::class);
            $mediaManager->reorder($this->mediableType, $this->mediableId, $orderedIds);

            Log::info('[MEDIA GALLERY] Order updated', [
                'mediable_id' => $this->mediableId,
                'order' => $orderedIds,
            ]);

        } catch (\Exception $e) {
            Log::error('[MEDIA GALLERY] Reorder failed', ['error' => $e->getMessage()]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udalo sie zmienic kolejnosci',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh gallery on media upload
     */
    #[On('media-uploaded')]
    public function refreshGallery(): void
    {
        // Livewire will re-render automatically
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES / DATA ACCESS
    |--------------------------------------------------------------------------
    */

    /**
     * Get media collection
     */
    public function getMedia(): Collection
    {
        return Media::where('mediable_type', $this->mediableType)
            ->where('mediable_id', $this->mediableId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get media count
     */
    public function getMediaCountProperty(): int
    {
        return $this->getMedia()->count();
    }

    /**
     * Check if has selection
     */
    public function getHasSelectionProperty(): bool
    {
        return !empty($this->selectedIds);
    }

    /**
     * Get selection count
     */
    public function getSelectionCountProperty(): int
    {
        return count($this->selectedIds);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.media-gallery-grid', [
            'media' => $this->getMedia(),
        ]);
    }
}
