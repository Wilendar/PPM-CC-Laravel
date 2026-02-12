<?php

namespace App\Http\Livewire\Products\Import\Traits;

use App\Models\PendingProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait HandlesImageUpload
 *
 * Handles image upload from disk, URL import, and copy from product.
 * Extracted from ImageUploadModal for file size compliance (~300 lines).
 */
trait HandlesImageUpload
{
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
     * Upload in progress
     */
    public bool $isUploading = false;

    /**
     * SKU suggestions for autocomplete
     */
    public array $skuSuggestions = [];

    /**
     * Show SKU suggestions dropdown
     */
    public bool $showSkuSuggestions = false;

    /**
     * Handle file upload
     */
    public function updatedUploadedFiles(): void
    {
        $this->validate();

        $this->isUploading = true;

        try {
            foreach ($this->uploadedFiles as $file) {
                $extension = $file->getClientOriginalExtension();
                $filename = $file->getClientOriginalName();
                $uniqueName = Str::uuid() . '.' . $extension;

                $path = $file->storeAs('pending_imports', $uniqueName, 'public');

                $this->images[] = [
                    'path' => $path,
                    'filename' => $filename,
                    'position' => count($this->images),
                    'is_cover' => count($this->images) === 0,
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
            $imageContent = @file_get_contents($this->imageUrl);

            if ($imageContent === false) {
                throw new \Exception('Nie mozna pobrac obrazu z URL');
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (!str_starts_with($mimeType, 'image/')) {
                throw new \Exception('URL nie wskazuje na obraz');
            }

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $uniqueName = Str::uuid() . '.' . $extension;
            $path = 'pending_imports/' . $uniqueName;

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
                $this->copyFromPendingProduct($source);
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

            $this->copyFromExistingProduct($product);
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

    protected function copyFromPendingProduct(PendingProduct $source): void
    {
        $mediaData = $source->temp_media_paths ?? [];
        if (empty($mediaData['images'])) {
            $this->dispatch('flash-message', ['type' => 'info', 'message' => 'Produkt nie ma zdjec']);
            return;
        }

        $copiedCount = 0;
        foreach ($mediaData['images'] as $img) {
            if (!Storage::disk('public')->exists($img['path'])) {
                continue;
            }
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

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Skopiowano ' . $copiedCount . ' zdjec z pending produktu',
        ]);
    }

    protected function copyFromExistingProduct(Product $product): void
    {
        $copiedCount = 0;
        foreach ($product->media as $media) {
            if (empty($media->file_path)) {
                continue;
            }

            if (!Storage::disk('public')->exists($media->file_path)) {
                continue;
            }

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
        }

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Skopiowano ' . $copiedCount . ' zdjec z produktu ' . $this->copyFromSku,
        ]);
    }

    /**
     * Handle SKU input change - search for suggestions
     */
    public function updatedCopyFromSku(): void
    {
        if (strlen($this->copyFromSku) < 2) {
            $this->skuSuggestions = [];
            $this->showSkuSuggestions = false;
            return;
        }

        $searchTerm = $this->copyFromSku;
        $suggestions = [];

        $pendingProducts = PendingProduct::where('sku', 'LIKE', "%{$searchTerm}%")
            ->where('id', '!=', $this->pendingProductId)
            ->limit(5)
            ->get(['id', 'sku', 'name', 'temp_media_paths']);

        foreach ($pendingProducts as $pp) {
            $mediaData = $pp->temp_media_paths ?? [];
            $hasImages = !empty($mediaData['images']);
            $suggestions[] = [
                'sku' => $pp->sku,
                'name' => $pp->name ?? '(brak nazwy)',
                'source' => 'pending',
                'has_images' => $hasImages,
                'image_count' => $hasImages ? count($mediaData['images']) : 0,
            ];
        }

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

        usort($suggestions, function ($a, $b) use ($searchTerm) {
            $aExact = strtolower($a['sku']) === strtolower($searchTerm) ? 0 : 1;
            $bExact = strtolower($b['sku']) === strtolower($searchTerm) ? 0 : 1;
            if ($aExact !== $bExact) return $aExact - $bExact;
            if ($a['has_images'] !== $b['has_images']) return $b['has_images'] ? 1 : -1;
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
}
