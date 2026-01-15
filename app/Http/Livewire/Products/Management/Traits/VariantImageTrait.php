<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\VariantImage;
use App\Services\Media\ThumbnailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * VariantImageTrait - Image Management for Product Variants
 *
 * Handles: Upload, assign, delete, set cover for variant images
 *
 * EXTRACTED FROM: ProductFormVariants.php (1369 lines -> 6 traits)
 * LINE COUNT TARGET: < 280 lines (CLAUDE.md compliance)
 *
 * NOTE: Thumbnail generation moved to ThumbnailService for separation of concerns
 *
 * DEPENDENCIES:
 * - VariantValidation trait (validateVariantImage)
 * - Product model ($this->product)
 * - ThumbnailService (for image processing)
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait VariantImageTrait
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** @var mixed Livewire file upload property for variant images */
    public $variantImageUpload;

    /** @var mixed Variant images upload (Livewire WithFileUploads) */
    public $variantImages;

    /** @var array Image to variant assignments [image_id] => variant_id */
    public array $imageVariantAssignments = [];

    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Handle variant images upload (Livewire property update hook)
     */
    public function updatedVariantImages(): void
    {
        if (empty($this->variantImages)) {
            return;
        }

        try {
            $this->validate([
                'variantImages.*' => 'image|max:5120|mimes:jpg,jpeg,png,gif,webp',
            ], [
                'variantImages.*.image' => 'Plik musi byc zdjeciem.',
                'variantImages.*.max' => 'Maksymalny rozmiar pliku to 5MB.',
                'variantImages.*.mimes' => 'Dozwolone formaty: JPG, PNG, GIF, WEBP.',
            ]);

            DB::beginTransaction();

            $thumbnailService = app(ThumbnailService::class);

            foreach ($this->variantImages as $image) {
                $filename = uniqid() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs("products/{$this->product->id}/variants", $filename, 'public');

                $thumbnailPath = $thumbnailService->generate($path, 200, 200);

                VariantImage::create([
                    'variant_id' => null, // Will be assigned manually
                    'filename' => $filename,
                    'path' => $path,
                    'is_cover' => false,
                    'position' => VariantImage::max('position') + 1,
                ]);
            }

            DB::commit();

            Log::info('Variant images uploaded (unassigned)', [
                'product_id' => $this->product->id,
                'images_count' => count($this->variantImages),
            ]);

            $this->variantImages = null;
            $this->dispatch('variant-images-uploaded');
            session()->flash('message', 'Zdjecia zostaly przeslane. Przypisz je do wariantow.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant image upload failed', ['error' => $e->getMessage()]);
            $this->addError('variantImages', 'Blad podczas przesylania zdjec: ' . $e->getMessage());
        }
    }

    /**
     * Upload images for variant (direct method)
     */
    public function uploadVariantImages(int $variantId, array $images): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);
            $thumbnailService = app(ThumbnailService::class);

            foreach ($images as $image) {
                $this->validateVariantImage($image);

                DB::transaction(function () use ($variant, $image, $thumbnailService) {
                    $path = $image->store("variants/{$variant->id}", 'public');
                    $thumbPath = $thumbnailService->generate($path, 200, 200);

                    $position = $variant->images()->max('position') + 1;

                    $variant->images()->create([
                        'image_path' => $path,
                        'image_thumb_path' => $thumbPath,
                        'is_cover' => $variant->images()->count() === 0,
                        'position' => $position,
                    ]);
                });
            }

            Log::info('Variant images uploaded', [
                'variant_id' => $variantId,
                'images_count' => count($images),
            ]);

            $this->dispatch('variant-images-uploaded');
            session()->flash('message', 'Zdjecia zostaly dodane pomyslnie.');
        } catch (\Exception $e) {
            Log::error('Variant image upload failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas dodawania zdjec: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Assign existing image to variant
     */
    public function assignImageToVariant(int $imageId, int $variantId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);
            ProductVariant::findOrFail($variantId);

            $image->update(['variant_id' => $variantId]);

            Log::info('Image assigned to variant', [
                'image_id' => $imageId,
                'variant_id' => $variantId,
            ]);

            $this->dispatch('image-assigned');
            session()->flash('message', 'Zdjecie zostalo przypisane do wariantu.');
        } catch (\Exception $e) {
            Log::error('Image assignment failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas przypisywania zdjecia: ' . $e->getMessage());
        }
    }

    /**
     * Delete variant image
     */
    public function deleteVariantImage(int $imageId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);

            DB::transaction(function () use ($image) {
                // Delete files from storage
                Storage::disk('public')->delete($image->path);

                // Delete thumbnail if exists
                $thumbPath = str_replace($image->filename, 'thumb_' . $image->filename, $image->path);
                if (Storage::disk('public')->exists($thumbPath)) {
                    Storage::disk('public')->delete($thumbPath);
                }

                // If this was cover, set first remaining as cover
                if ($image->is_cover && $image->variant) {
                    $newCover = $image->variant->images()
                        ->where('id', '!=', $image->id)
                        ->orderBy('position')
                        ->first();

                    if ($newCover) {
                        $newCover->update(['is_cover' => true]);
                    }
                }

                $image->delete();

                Log::info('Variant image deleted', ['image_id' => $image->id]);
            });

            $this->dispatch('image-deleted');
            session()->flash('message', 'Zdjecie zostalo usuniete.');
        } catch (\Exception $e) {
            Log::error('Image deletion failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas usuwania zdjecia: ' . $e->getMessage());
        }
    }

    /**
     * Set image as cover
     */
    public function setCoverImage(int $imageId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);

            if (!$image->variant) {
                throw new \Exception('Zdjecie nie jest przypisane do zadnego wariantu.');
            }

            DB::transaction(function () use ($image) {
                // Unset other covers for this variant
                $image->variant->images()
                    ->where('id', '!=', $image->id)
                    ->update(['is_cover' => false]);

                $image->update(['is_cover' => true]);

                Log::info('Cover image changed', [
                    'image_id' => $image->id,
                    'variant_id' => $image->variant_id,
                ]);
            });

            $this->dispatch('cover-image-changed');
            session()->flash('message', 'Zdjecie glowne zostalo ustawione.');
        } catch (\Exception $e) {
            Log::error('Set cover image failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas ustawiania zdjecia glownego: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALIAS METHODS (Blade Compatibility)
    |--------------------------------------------------------------------------
    */

    /**
     * Alias for setCoverImage
     */
    public function setImageAsCover(int $imageId): void
    {
        $this->setCoverImage($imageId);
    }

    /**
     * Alias for deleteVariantImage
     */
    public function deleteImage(int $imageId): void
    {
        $this->deleteVariantImage($imageId);
    }
}
