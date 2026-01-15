<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\PendingProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ImageUploadModal - ETAP_06 FAZA 5.7
 *
 * Modal do uploadowania i zarzadzania zdjeciami dla pending products.
 * Zdjecia przechowywane jako JSON w kolumnie temp_media_paths.
 *
 * Structure: temp_media_paths = [
 *   'images' => [
 *     [
 *       'path' => 'pending_imports/xxx.jpg',
 *       'filename' => 'original.jpg',
 *       'position' => 0,
 *       'is_cover' => true,
 *       'size' => 123456,
 *       'mime' => 'image/jpeg',
 *       'variant_sku' => null|'-RED-XL',  // opcjonalnie przypisanie do wariantu
 *     ],
 *   ],
 *   'source' => 'upload|copy|url',
 *   'updated_at' => '2025-12-09T...'
 * ]
 *
 * @package App\Http\Livewire\Products\Import\Modals
 * @since 2025-12-09
 */
class ImageUploadModal extends Component
{
    use WithFileUploads;

    /**
     * Whether modal is visible
     */
    public bool $showModal = false;

    /**
     * Currently editing pending product ID
     */
    public ?int $pendingProductId = null;

    /**
     * Pending product model
     */
    public ?PendingProduct $pendingProduct = null;

    /**
     * Uploaded images info
     * Format: [['path' => ..., 'filename' => ..., 'position' => ..., 'is_cover' => ...]]
     */
    public array $images = [];

    /**
     * Temporary uploaded files
     */
    public $uploadedFiles = [];

    /**
     * Copy from product SKU
     */
    public string $copyFromSku = '';

    /**
     * Image URL for import
     */
    public string $imageUrl = '';

    /**
     * Processing flag
     */
    public bool $isProcessing = false;

    /**
     * Upload in progress
     */
    public bool $isUploading = false;

    /**
     * Product variants (loaded from variant_data)
     * Format: [['sku_suffix' => '-RED', 'name' => 'Czerwony', 'attributes' => [...]]]
     */
    public array $variants = [];

    /**
     * Show variant assignment panel
     */
    public bool $showVariantAssignment = false;

    /**
     * SKU suggestions for autocomplete
     * Format: [['sku' => 'ABC-123', 'name' => 'Product Name', 'source' => 'pending|product', 'has_images' => true]]
     */
    public array $skuSuggestions = [];

    /**
     * Show SKU suggestions dropdown
     */
    public bool $showSkuSuggestions = false;

    /**
     * Listeners
     */
    protected $listeners = [
        'openImageModal' => 'openModal',
    ];

    /**
     * Validation rules for file uploads
     */
    protected function rules(): array
    {
        return [
            'uploadedFiles.*' => 'image|max:10240', // 10MB max per image
        ];
    }

    /**
     * Open modal for a pending product
     */
    #[On('openImageModal')]
    public function openModal(int $productId): void
    {
        $this->reset(['images', 'uploadedFiles', 'copyFromSku', 'imageUrl', 'variants', 'showVariantAssignment', 'skuSuggestions', 'showSkuSuggestions']);

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

        // Load variants from variant_data
        $variantData = $this->pendingProduct->variant_data ?? [];
        if (!empty($variantData['variants'])) {
            $this->variants = $variantData['variants'];
        }

        $this->showModal = true;
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['pendingProductId', 'pendingProduct', 'images', 'uploadedFiles']);
    }

    /**
     * Handle SKU input change - search for suggestions
     */
    public function updatedCopyFromSku(): void
    {
        // Clear suggestions if input too short
        if (strlen($this->copyFromSku) < 2) {
            $this->skuSuggestions = [];
            $this->showSkuSuggestions = false;
            return;
        }

        $searchTerm = $this->copyFromSku;
        $suggestions = [];

        // Search in PendingProducts (same import session)
        $pendingProducts = PendingProduct::where('sku', 'LIKE', "%{$searchTerm}%")
            ->where('id', '!=', $this->pendingProductId) // Exclude current product
            ->limit(5)
            ->get(['id', 'sku', 'name', 'temp_media_paths']);

        foreach ($pendingProducts as $pp) {
            $mediaData = $pp->temp_media_paths ?? [];
            $hasImages = !empty($mediaData['images']);
            $imageCount = $hasImages ? count($mediaData['images']) : 0;

            $suggestions[] = [
                'sku' => $pp->sku,
                'name' => $pp->name ?? '(brak nazwy)',
                'source' => 'pending',
                'has_images' => $hasImages,
                'image_count' => $imageCount,
            ];
        }

        // Search in Products (existing products in database)
        $products = Product::where('sku', 'LIKE', "%{$searchTerm}%")
            ->withCount('media')
            ->limit(5)
            ->get(['id', 'sku', 'name']);

        foreach ($products as $product) {
            $suggestions[] = [
                'sku' => $product->sku,
                'name' => $product->name ?? '(brak nazwy)',
                'source' => 'product',
                'has_images' => $product->media_count > 0,
                'image_count' => $product->media_count,
            ];
        }

        // Sort by relevance (exact match first, then by image count)
        usort($suggestions, function($a, $b) use ($searchTerm) {
            // Exact match first
            $aExact = strtolower($a['sku']) === strtolower($searchTerm) ? 0 : 1;
            $bExact = strtolower($b['sku']) === strtolower($searchTerm) ? 0 : 1;
            if ($aExact !== $bExact) return $aExact - $bExact;

            // Then by has_images
            if ($a['has_images'] !== $b['has_images']) {
                return $b['has_images'] ? 1 : -1;
            }

            // Then by image count
            return ($b['image_count'] ?? 0) - ($a['image_count'] ?? 0);
        });

        $this->skuSuggestions = array_slice($suggestions, 0, 8);
        $this->showSkuSuggestions = count($this->skuSuggestions) > 0;
    }

    /**
     * Select SKU from suggestions
     */
    public function selectSkuSuggestion(string $sku): void
    {
        $this->copyFromSku = $sku;
        $this->skuSuggestions = [];
        $this->showSkuSuggestions = false;
    }

    /**
     * Hide SKU suggestions dropdown
     */
    public function hideSkuSuggestions(): void
    {
        $this->showSkuSuggestions = false;
    }

    /**
     * Handle file upload
     */
    public function updatedUploadedFiles(): void
    {
        $this->validate();

        $this->isUploading = true;

        try {
            foreach ($this->uploadedFiles as $file) {
                // Generate unique filename
                $extension = $file->getClientOriginalExtension();
                $filename = $file->getClientOriginalName();
                $uniqueName = Str::uuid() . '.' . $extension;

                // Store in pending_imports folder
                $path = $file->storeAs('pending_imports', $uniqueName, 'public');

                $this->images[] = [
                    'path' => $path,
                    'filename' => $filename,
                    'position' => count($this->images),
                    'is_cover' => count($this->images) === 0, // First image is cover
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }

            $this->uploadedFiles = [];

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Dodano zdjecia',
            ]);

        } catch (\Exception $e) {
            Log::error('[ImageUploadModal] Upload failed', [
                'pending_product_id' => $this->pendingProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad uploadu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isUploading = false;
        }
    }

    /**
     * Import image from URL
     */
    public function importFromUrl(): void
    {
        if (empty($this->imageUrl)) {
            return;
        }

        if (!filter_var($this->imageUrl, FILTER_VALIDATE_URL)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nieprawidlowy URL',
            ]);
            return;
        }

        $this->isUploading = true;

        try {
            // Download image
            $imageContent = @file_get_contents($this->imageUrl);

            if ($imageContent === false) {
                throw new \Exception('Nie mozna pobrac obrazu z URL');
            }

            // Detect mime type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (!str_starts_with($mimeType, 'image/')) {
                throw new \Exception('URL nie wskazuje na obraz');
            }

            // Get extension from mime
            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            // Generate filename
            $uniqueName = Str::uuid() . '.' . $extension;
            $path = 'pending_imports/' . $uniqueName;

            // Store file
            Storage::disk('public')->put($path, $imageContent);

            $this->images[] = [
                'path' => $path,
                'filename' => basename(parse_url($this->imageUrl, PHP_URL_PATH)) ?: 'image.' . $extension,
                'position' => count($this->images),
                'is_cover' => count($this->images) === 0,
                'size' => strlen($imageContent),
                'mime' => $mimeType,
                'source_url' => $this->imageUrl,
            ];

            $this->imageUrl = '';

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zdjecie pobrane z URL',
            ]);

        } catch (\Exception $e) {
            Log::error('[ImageUploadModal] URL import failed', [
                'url' => $this->imageUrl,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad importu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isUploading = false;
        }
    }

    /**
     * Copy images from another product
     */
    public function copyFromProduct(): void
    {
        if (empty($this->copyFromSku)) {
            return;
        }

        $this->isUploading = true;

        try {
            // Try PendingProduct first
            $source = PendingProduct::where('sku', $this->copyFromSku)->first();

            if ($source) {
                $mediaData = $source->temp_media_paths ?? [];
                if (!empty($mediaData['images'])) {
                    $copiedCount = 0;
                    foreach ($mediaData['images'] as $img) {
                        // Copy file to new location
                        if (Storage::disk('public')->exists($img['path'])) {
                            $extension = pathinfo($img['path'], PATHINFO_EXTENSION);
                            $newPath = 'pending_imports/' . Str::uuid() . '.' . $extension;
                            Storage::disk('public')->copy($img['path'], $newPath);

                            $this->images[] = [
                                'path' => $newPath,
                                'filename' => $img['filename'] ?? basename($newPath),
                                'position' => count($this->images),
                                'is_cover' => count($this->images) === 0 && ($img['is_cover'] ?? false),
                                'size' => $img['size'] ?? 0,
                                'mime' => $img['mime'] ?? 'image/jpeg',
                            ];
                            $copiedCount++;
                        }
                    }

                    $this->dispatch('flash-message', [
                        'type' => 'success',
                        'message' => 'Skopiowano ' . $copiedCount . ' zdjec z pending produktu',
                    ]);
                } else {
                    $this->dispatch('flash-message', [
                        'type' => 'info',
                        'message' => 'Produkt nie ma zdjec',
                    ]);
                }
                $this->copyFromSku = '';
                return;
            }

            // Try Product with media
            $product = Product::where('sku', $this->copyFromSku)
                ->with('media')
                ->first();

            if (!$product) {
                $this->dispatch('flash-message', [
                    'type' => 'warning',
                    'message' => 'Nie znaleziono produktu o SKU: ' . $this->copyFromSku,
                ]);
                return;
            }

            $copiedCount = 0;
            foreach ($product->media as $media) {
                // Skip media without valid file_path
                if (empty($media->file_path)) {
                    Log::debug('[ImageUploadModal] Skipping media without file_path', [
                        'media_id' => $media->id,
                        'product_sku' => $this->copyFromSku,
                    ]);
                    continue;
                }

                if (Storage::disk('public')->exists($media->file_path)) {
                    $extension = pathinfo($media->file_path, PATHINFO_EXTENSION);
                    $newPath = 'pending_imports/' . Str::uuid() . '.' . $extension;
                    Storage::disk('public')->copy($media->file_path, $newPath);

                    $this->images[] = [
                        'path' => $newPath,
                        'filename' => $media->original_name ?? basename($newPath),
                        'position' => count($this->images),
                        'is_cover' => count($this->images) === 0 && $media->is_primary,
                        'size' => $media->file_size ?? 0,
                        'mime' => $media->mime_type ?? 'image/jpeg',
                    ];
                    $copiedCount++;
                } else {
                    Log::debug('[ImageUploadModal] File not found on disk', [
                        'media_id' => $media->id,
                        'file_path' => $media->file_path,
                    ]);
                }
            }

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Skopiowano ' . $copiedCount . ' zdjec z produktu ' . $this->copyFromSku,
            ]);
            $this->copyFromSku = '';

        } catch (\Exception $e) {
            Log::error('[ImageUploadModal] Copy failed', [
                'sku' => $this->copyFromSku,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad kopiowania: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isUploading = false;
        }
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
    }

    /**
     * Set image as cover
     */
    public function setCover(int $index): void
    {
        foreach ($this->images as $i => &$img) {
            $img['is_cover'] = ($i === $index);
        }
    }

    /**
     * Assign image to a variant
     */
    public function assignToVariant(int $imageIndex, ?string $variantSku): void
    {
        if (!isset($this->images[$imageIndex])) {
            return;
        }

        $this->images[$imageIndex]['variant_sku'] = $variantSku ?: null;

        Log::debug('[ImageUploadModal] Assigned image to variant', [
            'image_index' => $imageIndex,
            'variant_sku' => $variantSku,
        ]);
    }

    /**
     * Toggle variant assignment panel visibility
     */
    public function toggleVariantAssignment(): void
    {
        $this->showVariantAssignment = !$this->showVariantAssignment;
    }

    /**
     * Get variant display name (from attributes)
     */
    public function getVariantDisplayName(array $variant): string
    {
        $name = $variant['name'] ?? '';
        if (!empty($name)) {
            return $name;
        }

        // Build from attributes
        $parts = [];
        foreach ($variant['attributes'] ?? [] as $attr) {
            $parts[] = $attr['value'] ?? '';
        }

        return implode(' / ', $parts) ?: ($variant['sku_suffix'] ?? 'Wariant');
    }

    /**
     * Move image up in order
     */
    public function moveUp(int $index): void
    {
        if ($index <= 0 || !isset($this->images[$index])) {
            return;
        }

        // Swap with previous
        $temp = $this->images[$index - 1];
        $this->images[$index - 1] = $this->images[$index];
        $this->images[$index] = $temp;

        // Update positions
        $this->images[$index - 1]['position'] = $index - 1;
        $this->images[$index]['position'] = $index;
    }

    /**
     * Move image down in order
     */
    public function moveDown(int $index): void
    {
        if ($index >= count($this->images) - 1 || !isset($this->images[$index])) {
            return;
        }

        // Swap with next
        $temp = $this->images[$index + 1];
        $this->images[$index + 1] = $this->images[$index];
        $this->images[$index] = $temp;

        // Update positions
        $this->images[$index]['position'] = $index;
        $this->images[$index + 1]['position'] = $index + 1;
    }

    /**
     * Clear all images
     */
    public function clearImages(): void
    {
        // Delete all files
        foreach ($this->images as $image) {
            if (!empty($image['path']) && Storage::disk('public')->exists($image['path'])) {
                Storage::disk('public')->delete($image['path']);
            }
        }

        $this->images = [];
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
            // Build temp_media_paths structure
            $mediaData = [
                'images' => $this->images,
                'source' => 'upload',
                'updated_at' => now()->toIso8601String(),
            ];

            $this->pendingProduct->update([
                'temp_media_paths' => $mediaData,
            ]);

            // Recalculate completion percentage
            $this->pendingProduct->recalculateCompletion();

            Log::info('[ImageUploadModal] Saved images', [
                'pending_product_id' => $this->pendingProductId,
                'image_count' => count($this->images),
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
     *
     * ETAP_06: Quick Actions - skip flag with history tracking
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
                'user_id' => auth()->id(),
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

        // Refresh the row to update status %
        $this->dispatch('refreshPendingProducts');
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.image-upload-modal');
    }
}
