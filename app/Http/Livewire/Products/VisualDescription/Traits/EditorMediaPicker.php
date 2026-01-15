<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

/**
 * Trait EditorMediaPicker.
 *
 * Handles media picker modal for selecting/uploading images in blocks.
 * ETAP_07f Faza 7: Media Integration
 */
trait EditorMediaPicker
{
    use WithFileUploads;

    // Media picker state
    public bool $showMediaPicker = false;
    public ?int $mediaPickerFieldIndex = null;
    public ?string $mediaPickerFieldName = null;
    public bool $mediaPickerMultiple = false;

    // Upload state
    public $mediaUpload;

    /**
     * Open the media picker modal.
     */
    public function openMediaPicker(int $index, string $fieldName, bool $multiple = false): void
    {
        $this->mediaPickerFieldIndex = $index;
        $this->mediaPickerFieldName = $fieldName;
        $this->mediaPickerMultiple = $multiple;
        $this->showMediaPicker = true;
    }

    /**
     * Close the media picker modal.
     */
    public function closeMediaPicker(): void
    {
        $this->showMediaPicker = false;
        $this->mediaPickerFieldIndex = null;
        $this->mediaPickerFieldName = null;
        $this->mediaPickerMultiple = false;
        $this->mediaUpload = null;
    }

    /**
     * Set the selected media from picker.
     *
     * @param int $index Block index
     * @param string $fieldName Field name in block data
     * @param string|array $value URL or array of URLs (for gallery)
     */
    public function setMediaPickerSelection(int $index, string $fieldName, mixed $value): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        // For gallery fields, append to existing
        if ($this->mediaPickerMultiple && is_array($value)) {
            $existing = $this->blocks[$index]['data'][$fieldName] ?? [];
            $value = array_merge($existing, $value);
        }

        $this->updateBlockProperty($index, $fieldName, $value);
        $this->closeMediaPicker();

        $this->dispatch('notify', type: 'success', message: 'Obraz wybrany');
    }

    /**
     * Upload media directly for a block field.
     *
     * @param mixed $file Uploaded file
     */
    public function uploadMediaForBlock(mixed $file): void
    {
        if (!$this->productId || !$this->mediaPickerFieldIndex === null) {
            $this->dispatch('notify', type: 'error', message: 'Brak kontekstu produktu');
            return;
        }

        try {
            // Validate file
            $this->validate([
                'mediaUpload' => 'image|max:10240', // 10MB max
            ]);

            // Store file
            $path = $this->mediaUpload->store('products/' . $this->productId . '/visual-editor', 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            $url = asset('storage/' . $path);

            // Set the URL in the block
            $this->setMediaPickerSelection(
                $this->mediaPickerFieldIndex,
                $this->mediaPickerFieldName,
                $this->mediaPickerMultiple ? [$url] : $url
            );

            Log::info('Visual editor media uploaded', [
                'product_id' => $this->productId,
                'path' => $path,
            ]);

        } catch (\Exception $e) {
            Log::error('Visual editor media upload failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Blad uploadu: ' . $e->getMessage());
        }
    }

    /**
     * Get video embed URL from various video platforms.
     *
     * @param string $url Original video URL
     * @return array Video info with platform, videoId, embedUrl
     */
    public function parseVideoUrl(string $url): array
    {
        $result = [
            'platform' => null,
            'videoId' => null,
            'embedUrl' => null,
            'thumbnailUrl' => null,
        ];

        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $result['platform'] = 'youtube';
            $result['videoId'] = $matches[1];
            $result['embedUrl'] = 'https://www.youtube.com/embed/' . $matches[1];
            $result['thumbnailUrl'] = 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
        }

        // Vimeo
        elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $result['platform'] = 'vimeo';
            $result['videoId'] = $matches[1];
            $result['embedUrl'] = 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $result;
    }

    /**
     * Generate lazy-loaded video facade HTML.
     *
     * @param string $url Video URL
     * @param bool $lazy Whether to use lazy loading facade
     * @return string HTML for video embed
     */
    public function generateVideoEmbed(string $url, bool $lazy = true): string
    {
        $video = $this->parseVideoUrl($url);

        if (!$video['embedUrl']) {
            return '';
        }

        if ($lazy && $video['thumbnailUrl']) {
            // Lazy facade - show thumbnail with play button
            return sprintf(
                '<div class="video-facade" data-src="%s">
                    <img src="%s" alt="Video thumbnail" class="video-facade__thumbnail" loading="lazy">
                    <button class="video-facade__play" aria-label="Odtwórz wideo">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                </div>',
                htmlspecialchars($video['embedUrl']),
                htmlspecialchars($video['thumbnailUrl'])
            );
        }

        // Direct iframe
        return sprintf(
            '<div class="video-embed">
                <iframe src="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>',
            htmlspecialchars($video['embedUrl'])
        );
    }
}
