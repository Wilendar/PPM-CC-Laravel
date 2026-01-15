<?php

declare(strict_types=1);

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\DTOs\Media\MediaUploadDTO;
use App\Services\Media\MediaManager;
use Illuminate\Support\Facades\Log;

/**
 * MediaUploadWidget - Reusable Upload Component
 *
 * Features:
 * - Single/multiple file upload
 * - Drag & drop support
 * - Progress tracking
 * - Validation (size, type, count)
 * - Folder upload support
 *
 * Usage:
 * <livewire:components.media-upload-widget
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
class MediaUploadWidget extends Component
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Required props
    public string $mediableType;
    public int $mediableId;

    // Optional props with defaults
    public int $maxFiles = 10;
    public bool $multiple = true;
    public string $acceptTypes = 'image/jpeg,image/png,image/webp,image/gif';

    // Upload state
    #[Validate(['photos.*' => 'image|max:10240'])] // 10MB max per file
    public array $photos = [];
    public array $uploadProgress = [];
    public array $uploadErrors = [];
    public bool $isUploading = false;
    public int $currentUploads = 0;
    public int $existingCount = 0;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component with mediable info
     */
    public function mount(
        string $mediableType,
        int $mediableId,
        int $maxFiles = 10,
        bool $multiple = true,
        int $existingCount = 0
    ): void {
        $this->mediableType = $mediableType;
        $this->mediableId = $mediableId;
        $this->maxFiles = $maxFiles;
        $this->multiple = $multiple;
        $this->existingCount = $existingCount;
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Handle file upload via Livewire
     * Called automatically when $photos is updated
     */
    public function updatedPhotos(): void
    {
        $this->validate();
        $this->uploadErrors = [];

        // Check limit
        $remaining = $this->getRemainingSlots();
        if (count($this->photos) > $remaining) {
            $this->uploadErrors[] = "Mozna dodac maksymalnie {$remaining} zdjec (limit: {$this->maxFiles})";
            $this->photos = array_slice($this->photos, 0, $remaining);
        }

        if (empty($this->photos)) {
            return;
        }

        $this->isUploading = true;
        $this->processUploads();
    }

    /**
     * Process uploaded files
     */
    protected function processUploads(): void
    {
        try {
            $mediaManager = app(MediaManager::class);

            $dto = MediaUploadDTO::fromArray([
                'mediable_type' => $this->mediableType,
                'mediable_id' => $this->mediableId,
                'files' => $this->photos,
                'generate_thumbnails' => true,
                'convert_to_webp' => true,
                'auto_sync' => false,
            ]);

            $results = $mediaManager->uploadMultiple($dto);

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $failCount = count($results) - $successCount;

            // Collect errors
            foreach ($results as $result) {
                if (!$result['success'] && isset($result['error'])) {
                    $this->uploadErrors[] = $result['error'];
                }
            }

            // Dispatch success event to parent
            $this->dispatch('media-uploaded', [
                'count' => $successCount,
                'failed' => $failCount,
                'mediableId' => $this->mediableId,
            ]);

            Log::info('[MEDIA UPLOAD WIDGET] Upload completed', [
                'mediable_type' => $this->mediableType,
                'mediable_id' => $this->mediableId,
                'success' => $successCount,
                'failed' => $failCount,
            ]);

        } catch (\Exception $e) {
            Log::error('[MEDIA UPLOAD WIDGET] Upload failed', [
                'error' => $e->getMessage(),
                'mediable_id' => $this->mediableId,
            ]);
            $this->uploadErrors[] = 'Blad uploadu: ' . $e->getMessage();
        } finally {
            $this->isUploading = false;
            $this->photos = [];
            $this->currentUploads = 0;
        }
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void
    {
        $this->uploadErrors = [];
    }

    /**
     * Cancel current upload
     */
    public function cancelUpload(): void
    {
        $this->photos = [];
        $this->uploadProgress = [];
        $this->isUploading = false;
        $this->currentUploads = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get remaining upload slots
     */
    public function getRemainingSlots(): int
    {
        return max(0, $this->maxFiles - $this->existingCount);
    }

    /**
     * Check if can upload more
     */
    public function getCanUploadProperty(): bool
    {
        return $this->getRemainingSlots() > 0;
    }

    /**
     * Get upload progress percentage
     */
    public function getUploadPercentageProperty(): int
    {
        if (empty($this->photos) || !$this->isUploading) {
            return 0;
        }

        $total = count($this->photos);
        $completed = $total - count(array_filter($this->uploadProgress, fn($p) => $p < 100));
        return (int) (($completed / $total) * 100);
    }

    /**
     * Get accept string for input
     */
    public function getAcceptStringProperty(): string
    {
        return $this->acceptTypes;
    }

    /**
     * Check if has errors
     */
    public function getHasErrorsProperty(): bool
    {
        return !empty($this->uploadErrors);
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
        return view('livewire.components.media-upload-widget');
    }
}
