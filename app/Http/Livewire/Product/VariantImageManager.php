<?php

namespace App\Http\Livewire\Product;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Services\Product\VariantManager;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Support\Facades\Log;

/**
 * VariantImageManager Livewire Component
 *
 * Manages variant-specific images with upload, reorder, delete, and primary selection
 *
 * FEATURES:
 * - Multi-file upload for variant images
 * - Drag & drop reordering (position management)
 * - Set primary/cover image
 * - Delete images with file cleanup
 * - Responsive image gallery UI
 *
 * COMPLIANCE:
 * - Livewire 3.x patterns (#[Computed], dispatch(), wire:key)
 * - Context7 verified (WithFileUploads, Storage, validation)
 * - CLAUDE.md: max 300 linii, NO inline styles
 * - Enterprise UI patterns (reference: VariantManagement.php)
 *
 * USAGE:
 * ```blade
 * <livewire:product.variant-image-manager :variantId="$variant->id" />
 * ```
 *
 * RELATED:
 * - app/Services/Product/VariantManager.php (business logic)
 * - app/Models/VariantImage.php (model)
 * - resources/views/livewire/product/variant-image-manager.blade.php (UI)
 *
 * @package App\Http\Livewire\Product
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-24)
 */
class VariantImageManager extends Component
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Variant ID (required, passed from parent component)
     */
    public int $variantId;

    /**
     * Temporary storage for uploaded images (before save)
     */
    public array $uploadedImages = [];

    /**
     * Loading state for upload operation
     */
    public bool $isUploading = false;

    /**
     * Selected image ID for lightbox modal
     */
    public ?int $selectedImageId = null;

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    /**
     * Validation rules for uploaded images
     */
    protected function rules(): array
    {
        return [
            'uploadedImages.*' => 'image|max:10240', // Max 10MB per image
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES (Livewire 3.x)
    |--------------------------------------------------------------------------
    */

    /**
     * Get variant with relationships
     */
    #[Computed]
    public function variant(): ProductVariant
    {
        return ProductVariant::with('images', 'product')->findOrFail($this->variantId);
    }

    /**
     * Get variant images ordered by position
     */
    #[Computed]
    public function images()
    {
        return VariantImage::where('variant_id', $this->variantId)
            ->orderBy('position')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component with variant ID
     */
    public function mount(int $variantId): void
    {
        $this->variantId = $variantId;

        Log::info('VariantImageManager MOUNTED', [
            'variant_id' => $variantId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS (UI Actions)
    |--------------------------------------------------------------------------
    */

    /**
     * Upload images to variant
     */
    public function uploadImages(): void
    {
        $this->validate();

        if (empty($this->uploadedImages)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Nie wybrano plików do uploadu'
            ]);
            return;
        }

        try {
            $this->isUploading = true;
            $variantManager = app(VariantManager::class);

            Log::info('VariantImageManager::uploadImages CALLED', [
                'variant_id' => $this->variantId,
                'files_count' => count($this->uploadedImages),
            ]);

            foreach ($this->uploadedImages as $file) {
                $variantManager->uploadImage($this->variantId, $file);
            }

            // Reset uploaded files
            $this->uploadedImages = [];
            $this->isUploading = false;

            // Dispatch events
            $this->dispatch('images-uploaded');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Zdjęcia zostały wgrane pomyślnie'
            ]);

            Log::info('VariantImageManager::uploadImages COMPLETED');

        } catch (\Exception $e) {
            $this->isUploading = false;

            Log::error('VariantImageManager::uploadImages FAILED', [
                'variant_id' => $this->variantId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas uploadu: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reorder image (move up or down)
     */
    public function reorderImage(int $imageId, string $direction): void
    {
        try {
            Log::info('VariantImageManager::reorderImage CALLED', [
                'image_id' => $imageId,
                'direction' => $direction,
            ]);

            $images = $this->images;
            $currentIndex = $images->search(fn($img) => $img->id === $imageId);

            if ($currentIndex === false) {
                return;
            }

            // Calculate new position
            $newIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            // Boundary check
            if ($newIndex < 0 || $newIndex >= $images->count()) {
                return;
            }

            // Swap positions
            $imageIds = $images->pluck('id')->toArray();
            $temp = $imageIds[$currentIndex];
            $imageIds[$currentIndex] = $imageIds[$newIndex];
            $imageIds[$newIndex] = $temp;

            // Save new order
            $variantManager = app(VariantManager::class);
            $variantManager->reorderImages($this->variantId, $imageIds);

            $this->dispatch('images-reordered');

            Log::info('VariantImageManager::reorderImage COMPLETED');

        } catch (\Exception $e) {
            Log::error('VariantImageManager::reorderImage FAILED', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas zmiany kolejności'
            ]);
        }
    }

    /**
     * Delete image
     */
    public function deleteImage(int $imageId): void
    {
        try {
            Log::info('VariantImageManager::deleteImage CALLED', [
                'image_id' => $imageId,
            ]);

            $variantManager = app(VariantManager::class);
            $variantManager->deleteImage($imageId);

            $this->dispatch('image-deleted');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Zdjęcie zostało usunięte'
            ]);

            Log::info('VariantImageManager::deleteImage COMPLETED');

        } catch (\Exception $e) {
            Log::error('VariantImageManager::deleteImage FAILED', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas usuwania: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Set image as primary (cover)
     */
    public function setPrimary(int $imageId): void
    {
        try {
            Log::info('VariantImageManager::setPrimary CALLED', [
                'image_id' => $imageId,
            ]);

            $variantManager = app(VariantManager::class);
            $variantManager->setPrimaryImage($this->variantId, $imageId);

            $this->dispatch('primary-image-set');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Zdjęcie główne zostało ustawione'
            ]);

            Log::info('VariantImageManager::setPrimary COMPLETED');

        } catch (\Exception $e) {
            Log::error('VariantImageManager::setPrimary FAILED', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas ustawiania zdjęcia głównego'
            ]);
        }
    }

    /**
     * Open lightbox modal with image
     */
    public function openLightbox(int $imageId): void
    {
        $this->selectedImageId = $imageId;
    }

    /**
     * Close lightbox modal
     */
    public function closeLightbox(): void
    {
        $this->selectedImageId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.product.variant-image-manager');
    }
}
