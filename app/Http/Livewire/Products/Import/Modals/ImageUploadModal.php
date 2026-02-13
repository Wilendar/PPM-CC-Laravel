<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\PendingProduct;
use App\Http\Livewire\Products\Import\Traits\HandlesImageUpload;
use App\Http\Livewire\Products\Import\Traits\HandlesVariantImages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ImageUploadModal - ETAP_06 FAZA 5.7
 *
 * Modal do uploadowania i zarzadzania zdjeciami dla pending products.
 * Zdjecia przechowywane jako JSON w kolumnie temp_media_paths.
 *
 * Structure: temp_media_paths = [
 *   'images' => [...],
 *   'variant_covers' => ['sku_suffix' => image_index],
 *   'source' => 'upload|copy|url',
 *   'updated_at' => '2025-12-09T...'
 * ]
 */
class ImageUploadModal extends Component
{
    use WithFileUploads;
    use HandlesImageUpload;
    use HandlesVariantImages;

    public bool $showModal = false;
    public ?int $pendingProductId = null;
    public ?PendingProduct $pendingProduct = null;

    /**
     * Uploaded images info
     * Format: [['path' => ..., 'filename' => ..., 'position' => ..., 'is_cover' => ..., 'variant_sku' => ...]]
     */
    public array $images = [];

    public bool $isProcessing = false;

    /**
     * View mode: 'grid' (flat) or 'grouped' (by variant)
     */
    public string $viewMode = 'grid';

    /**
     * Indices of selected images for batch operations
     */
    public array $selectedImages = [];

    /**
     * Variant filter: null=all, '_main'=main product, 'sku_suffix'=specific variant
     */
    public ?string $variantFilter = null;

    protected $listeners = [
        'openImageModal' => 'openModal',
    ];

    protected function rules(): array
    {
        return [
            'uploadedFiles.*' => 'image|max:10240',
        ];
    }

    /**
     * Open modal for a pending product
     */
    #[On('openImageModal')]
    public function openModal(int $productId): void
    {
        $this->reset([
            'images', 'uploadedFiles', 'copyFromSku', 'imageUrl',
            'variants', 'showVariantAssignment', 'variantCovers',
            'skuSuggestions', 'showSkuSuggestions',
            'viewMode', 'selectedImages', 'variantFilter',
        ]);

        $this->pendingProductId = $productId;
        $this->pendingProduct = PendingProduct::find($productId);

        if (!$this->pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        // Load existing image data
        $existingData = $this->pendingProduct->temp_media_paths ?? [];

        if (!empty($existingData['images'])) {
            $this->images = $existingData['images'];
        }

        // Load per-variant covers
        $this->variantCovers = $existingData['variant_covers'] ?? [];

        // Load variants from variant_data
        $variantData = $this->pendingProduct->variant_data ?? [];
        if (!empty($variantData['variants'])) {
            $this->variants = $variantData['variants'];
            // Auto-show variant assignment when variants exist
            $this->showVariantAssignment = true;
        }

        // Clean up orphaned variant_sku values (variant removed but images still assigned)
        $validSuffixes = collect($this->variants)->pluck('sku_suffix')->filter()->toArray();
        foreach ($this->images as $i => &$img) {
            if (!empty($img['variant_sku']) && !in_array($img['variant_sku'], $validSuffixes)) {
                $img['variant_sku'] = null;
            }
        }
        unset($img);

        // Clean up orphaned variant covers
        $cleanCovers = [];
        foreach ($this->variantCovers as $sku => $coverIdx) {
            if (in_array($sku, $validSuffixes)) {
                $cleanCovers[$sku] = $coverIdx;
            }
        }

        // Auto-populate variant covers for assigned images that have no cover yet
        foreach ($this->images as $idx => $img) {
            $sku = $img['variant_sku'] ?? null;
            if ($sku && !isset($cleanCovers[$sku])) {
                $cleanCovers[$sku] = $idx;
            }
        }

        $this->variantCovers = $cleanCovers;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset([
            'pendingProductId', 'pendingProduct', 'images', 'uploadedFiles',
            'variantCovers', 'selectedImages', 'viewMode', 'variantFilter',
        ]);
    }

    /**
     * Remove image
     */
    public function removeImage(int $index): void
    {
        if (!isset($this->images[$index])) {
            return;
        }

        $image = $this->images[$index];
        $wasCover = $image['is_cover'] ?? false;

        // Delete file
        if (!empty($image['path']) && Storage::disk('public')->exists($image['path'])) {
            Storage::disk('public')->delete($image['path']);
        }

        unset($this->images[$index]);
        $this->images = array_values($this->images);

        // Update positions
        foreach ($this->images as $i => &$img) {
            $img['position'] = $i;
        }

        // If removed cover, set first image as cover
        if ($wasCover && count($this->images) > 0) {
            $this->images[0]['is_cover'] = true;
        }

        // Sync variant covers after reindexing
        $this->syncVariantCoversAfterRemoval($index);
    }

    /**
     * Set image as global cover (product level)
     */
    public function setCover(int $index): void
    {
        foreach ($this->images as $i => &$img) {
            $img['is_cover'] = ($i === $index);
        }
    }

    /**
     * Move image up in order
     */
    public function moveUp(int $index): void
    {
        if ($index <= 0 || !isset($this->images[$index])) {
            return;
        }

        $temp = $this->images[$index - 1];
        $this->images[$index - 1] = $this->images[$index];
        $this->images[$index] = $temp;

        $this->images[$index - 1]['position'] = $index - 1;
        $this->images[$index]['position'] = $index;

        $this->syncVariantCoversAfterSwap($index - 1, $index);
    }

    /**
     * Move image down in order
     */
    public function moveDown(int $index): void
    {
        if ($index >= count($this->images) - 1 || !isset($this->images[$index])) {
            return;
        }

        $temp = $this->images[$index + 1];
        $this->images[$index + 1] = $this->images[$index];
        $this->images[$index] = $temp;

        $this->images[$index]['position'] = $index;
        $this->images[$index + 1]['position'] = $index + 1;

        $this->syncVariantCoversAfterSwap($index, $index + 1);
    }

    /**
     * Clear all images
     */
    public function clearImages(): void
    {
        foreach ($this->images as $image) {
            if (!empty($image['path']) && Storage::disk('public')->exists($image['path'])) {
                Storage::disk('public')->delete($image['path']);
            }
        }

        $this->images = [];
        $this->variantCovers = [];
    }

    /**
     * Save images to pending product
     */
    public function saveImages(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            $mediaData = [
                'images' => $this->images,
                'variant_covers' => $this->variantCovers,
                'source' => 'upload',
                'updated_at' => now()->toIso8601String(),
            ];

            $this->pendingProduct->update([
                'temp_media_paths' => $mediaData,
            ]);

            $this->pendingProduct->recalculateCompletion();

            Log::info('[ImageUploadModal] Saved images', [
                'pending_product_id' => $this->pendingProductId,
                'image_count' => count($this->images),
                'variant_covers' => $this->variantCovers,
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zapisano ' . count($this->images) . ' zdjec',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();
        } catch (\Exception $e) {
            Log::error('[ImageUploadModal] Save failed', [
                'pending_product_id' => $this->pendingProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Get image URL for preview
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Check if skip_images flag is set
     */
    public function getIsSkippedProperty(): bool
    {
        return $this->pendingProduct?->skip_images ?? false;
    }

    /**
     * Set "Publikuj bez zdjec" flag and close modal
     */
    public function setSkipImages(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            $this->pendingProduct->setSkipFlag('skip_images', true);

            Log::info('[ImageUploadModal] Set skip_images flag', [
                'pending_product_id' => $this->pendingProductId,
            ]);

            $this->dispatch('flash-message', [
                'type' => 'info',
                'message' => 'Oznaczono jako "Publikuj bez zdjec"',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();
        } catch (\Exception $e) {
            Log::error('[ImageUploadModal] Set skip flag failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Clear skip_images flag
     */
    public function clearSkipImages(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->pendingProduct->setSkipFlag('skip_images', false);

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Odznaczono "Publikuj bez zdjec"',
        ]);

        $this->dispatch('refreshPendingProducts');
    }

    // =========================================================================
    // Batch Selection & View Mode
    // =========================================================================

    /**
     * Set gallery view mode
     */
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'grouped'])) {
            $this->viewMode = $mode;
        }
    }

    /**
     * Set variant filter
     */
    public function setVariantFilter(?string $filter): void
    {
        $this->variantFilter = ($filter === '') ? null : $filter;
        $this->selectedImages = [];
    }

    /**
     * Toggle image selection for batch operations
     */
    public function toggleImageSelection(int $index): void
    {
        if (in_array($index, $this->selectedImages)) {
            $this->selectedImages = array_values(array_diff($this->selectedImages, [$index]));
        } else {
            $this->selectedImages[] = $index;
        }
    }

    /**
     * Deselect all images
     */
    public function deselectAllImages(): void
    {
        $this->selectedImages = [];
    }

    /**
     * Batch assign selected images to a variant
     * '_main' means assign to main product (null variant_sku)
     */
    public function batchAssignToVariant(?string $variantSku): void
    {
        $assignSku = ($variantSku === '_main' || $variantSku === '') ? null : $variantSku;

        foreach ($this->selectedImages as $index) {
            $this->assignToVariant($index, $assignSku);
        }
        $this->selectedImages = [];
    }

    /**
     * Batch remove selected images
     */
    public function batchRemoveImages(): void
    {
        $sorted = $this->selectedImages;
        rsort($sorted);
        foreach ($sorted as $index) {
            $this->removeImage($index);
        }
        $this->selectedImages = [];
    }

    /**
     * Get filtered images based on variantFilter (computed property)
     */
    public function getFilteredImagesProperty(): array
    {
        if ($this->variantFilter === null) {
            return $this->images;
        }

        $filtered = [];
        foreach ($this->images as $index => $image) {
            $variantSku = $image['variant_sku'] ?? null;

            if ($this->variantFilter === '_main') {
                if (empty($variantSku)) {
                    $filtered[$index] = $image;
                }
            } else {
                if ($variantSku === $this->variantFilter) {
                    $filtered[$index] = $image;
                }
            }
        }

        return $filtered;
    }

    /**
     * Get image counts per variant for filter buttons
     */
    public function getVariantImageCountsProperty(): array
    {
        $counts = ['_all' => count($this->images), '_main' => 0];

        foreach ($this->variants as $variant) {
            $sku = $variant['sku_suffix'] ?? '';
            if ($sku !== '') {
                $counts[$sku] = 0;
            }
        }

        foreach ($this->images as $image) {
            $variantSku = $image['variant_sku'] ?? null;
            if (empty($variantSku)) {
                $counts['_main']++;
            } elseif (isset($counts[$variantSku])) {
                $counts[$variantSku]++;
            }
        }

        return $counts;
    }

    public function render()
    {
        return view('livewire.products.import.modals.image-upload-modal');
    }
}
