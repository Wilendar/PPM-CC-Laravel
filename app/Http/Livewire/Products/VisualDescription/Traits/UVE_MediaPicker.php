<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * UVE Media Picker Trait - ETAP_07f_P5 FAZA PP.3
 *
 * Logika media pickera dla UVE:
 * - Wybor z galerii produktu
 * - Upload nowych plikow (drag&drop + progress)
 * - Ustawianie zewnetrznego URL
 * - Walidacja URL i typow plikow
 */
trait UVE_MediaPicker
{
    use WithFileUploads;

    /**
     * Currently selected media
     */
    public ?array $selectedMedia = null;

    /**
     * Upload progress (0-100)
     */
    public int $uploadProgress = 0;

    /**
     * External media URL
     */
    public string $mediaUrl = '';

    /**
     * Upload file reference
     */
    public $mediaUploadFile;

    /**
     * Media picker modal state
     */
    public bool $showUveMediaPicker = false;

    /**
     * Target element for media selection
     */
    public ?string $mediaPickerTargetElement = null;

    /**
     * Media picker active tab
     */
    public string $mediaPickerActiveTab = 'gallery';

    /**
     * Allowed file types
     */
    protected array $allowedMediaTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    /**
     * Max file size in bytes (10MB)
     */
    protected int $maxMediaSize = 10485760;

    /**
     * Open media picker for element
     */
    public function openMediaPicker(?string $elementId = null, string $tab = 'gallery'): void
    {
        $this->showUveMediaPicker = true;
        $this->mediaPickerTargetElement = $elementId;
        $this->mediaPickerActiveTab = $tab;
        $this->resetMediaPickerState();

        Log::info('openMediaPicker CALLED', [
            'element_id' => $elementId,
            'tab' => $tab,
            'mediaPickerTargetElement_AFTER_SET' => $this->mediaPickerTargetElement,
        ]);
    }

    /**
     * Close media picker
     */
    public function closeMediaPicker(): void
    {
        $this->showUveMediaPicker = false;
        $this->mediaPickerTargetElement = null;
        $this->resetMediaPickerState();
    }

    /**
     * Reset media picker state
     */
    protected function resetMediaPickerState(): void
    {
        $this->selectedMedia = null;
        $this->uploadProgress = 0;
        $this->mediaUrl = '';
        $this->mediaUploadFile = null;
    }

    /**
     * Livewire hook: Called when file upload completes
     * This automatically triggers handleUpload() after successful file upload
     */
    public function updatedMediaUploadFile(): void
    {
        Log::debug('updatedMediaUploadFile CALLED', [
            'hasFile' => $this->mediaUploadFile !== null,
            'fileType' => $this->mediaUploadFile ? get_class($this->mediaUploadFile) : null,
        ]);

        if ($this->mediaUploadFile) {
            $this->handleUpload();
        }
    }

    /**
     * Select media from product gallery and APPLY it to target element
     * Called from Alpine applyMedia() when user clicks "Wybierz" button
     */
    public function selectFromGallery(array $media): void
    {
        Log::info('=== selectFromGallery CALLED (from applyMedia/Wybierz button) ===', [
            'media_raw' => $media,
            'mediaPickerTargetElement' => $this->mediaPickerTargetElement,
        ]);

        // CRITICAL: Early return to prevent race condition on double-click
        // If picker was already closed by previous call, mediaPickerTargetElement will be null
        if (!$this->mediaPickerTargetElement) {
            Log::warning('selectFromGallery: NO mediaPickerTargetElement - returning early (race condition prevented)');
            return;
        }

        $this->selectedMedia = [
            'id' => $media['id'] ?? null,
            'url' => $media['url'] ?? $media['thumbnail_url'] ?? null,
            'alt' => $media['alt'] ?? '',
            'source' => 'gallery',
        ];

        Log::info('selectFromGallery: selectedMedia prepared', [
            'url' => $this->selectedMedia['url'],
            'id' => $this->selectedMedia['id'],
        ]);

        // APPLY the media to the target element
        Log::info('selectFromGallery: Calling applyMediaToElement', [
            'mediaPickerTargetElement' => $this->mediaPickerTargetElement,
        ]);
        $this->applyMediaToElement($this->mediaPickerTargetElement, $this->selectedMedia);

        // Dispatch event to update Alpine state
        $this->dispatch('uve-media-selected', mediaId: $media['id'] ?? null);

        // CRITICAL FIX: Dispatch event with URL for Property Panel image-settings control
        // This updates the Alpine component's imageUrl variable
        $this->dispatch('uve-image-url-updated', url: $this->selectedMedia['url'] ?? '');

        $this->dispatch('notify', type: 'success', message: 'Obraz wybrany z galerii');

        // Close media picker after applying
        $this->closeMediaPicker();

        Log::info('=== selectFromGallery END ===');
    }

    /**
     * Delete media from product gallery
     */
    public function deleteFromGallery(int $mediaId): void
    {
        try {
            $media = Media::find($mediaId);

            if (!$media) {
                $this->dispatch('notify', type: 'error', message: 'Nie znaleziono obrazu');
                return;
            }

            // Check if it's this product's media
            $productId = property_exists($this, 'productId') ? $this->productId : null;
            if ($media->mediable_id != $productId || $media->mediable_type !== Product::class) {
                $this->dispatch('notify', type: 'error', message: 'Brak uprawnien do usuniecia tego obrazu');
                return;
            }

            // Delete file from storage
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            // Delete database record
            $media->delete();

            $this->dispatch('notify', type: 'success', message: 'Obraz usuniety z galerii');

            Log::info('UVE Media deleted from gallery', [
                'media_id' => $mediaId,
                'product_id' => $productId,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());

            Log::error('UVE Media delete failed', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle file upload
     */
    public function handleUpload(): void
    {
        if (!$this->mediaUploadFile) {
            $this->dispatch('notify', type: 'error', message: 'Brak pliku do uploadu');
            return;
        }

        // Validate file
        $validationResult = $this->validateMediaFile($this->mediaUploadFile);
        if ($validationResult !== true) {
            $this->dispatch('notify', type: 'error', message: $validationResult);
            return;
        }

        try {
            $this->uploadProgress = 10;

            // Generate unique filename
            $filename = $this->generateMediaFilename($this->mediaUploadFile);
            $path = $this->getMediaStoragePath();

            $this->uploadProgress = 30;

            // Store the file
            $storedPath = $this->mediaUploadFile->storeAs($path, $filename, 'public');

            $this->uploadProgress = 70;

            if (!$storedPath) {
                throw new \Exception('Nie udalo sie zapisac pliku');
            }

            $url = Storage::disk('public')->url($storedPath);

            // Get file size
            $fileSize = Storage::disk('public')->size($storedPath);

            $this->uploadProgress = 80;

            // Create Media record with context='visual_description'
            // This ensures UVE uploads are tracked and isolated from product gallery
            $productId = property_exists($this, 'productId') ? $this->productId : null;
            $media = null;

            if ($productId) {
                $media = Media::create([
                    'mediable_type' => Product::class,
                    'mediable_id' => $productId,
                    'file_name' => $filename,
                    'original_name' => $this->mediaUploadFile->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'file_size' => $fileSize,
                    'mime_type' => $this->mediaUploadFile->getMimeType(),
                    'context' => Media::CONTEXT_VISUAL_DESCRIPTION, // CRITICAL: Mark as UVE media
                    'sort_order' => 0,
                    'is_primary' => false,
                    'sync_status' => 'pending',
                    'is_active' => true,
                ]);

                // Extract dimensions if image
                $dimensions = $this->getImageDimensionsFromFile($this->mediaUploadFile);
                if ($dimensions) {
                    $media->update([
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height'],
                    ]);
                }
            }

            $this->uploadProgress = 100;

            $this->selectedMedia = [
                'id' => $media?->id,
                'url' => $url,
                'alt' => pathinfo($this->mediaUploadFile->getClientOriginalName(), PATHINFO_FILENAME),
                'source' => 'upload',
                'path' => $storedPath,
            ];

            if ($this->mediaPickerTargetElement) {
                $this->applyMediaToElement($this->mediaPickerTargetElement, $this->selectedMedia);
            }

            // AUTO-SWITCH: Switch to gallery tab and select uploaded image
            $this->mediaPickerActiveTab = 'gallery';
            $this->dispatch('uve-upload-complete', mediaId: $media?->id, mediaUrl: $url);

            // CRITICAL FIX: Dispatch event with URL for Property Panel image-settings control
            $this->dispatch('uve-image-url-updated', url: $url);

            $this->dispatch('notify', type: 'success', message: 'Dodano do galerii i wybrano');

            Log::info('UVE Media uploaded', [
                'media_id' => $media?->id,
                'path' => $storedPath,
                'url' => $url,
                'context' => Media::CONTEXT_VISUAL_DESCRIPTION,
                'product_id' => $productId,
                'element_id' => $this->mediaPickerTargetElement,
            ]);

        } catch (\Exception $e) {
            $this->uploadProgress = 0;
            $this->dispatch('notify', type: 'error', message: 'Blad uploadu: ' . $e->getMessage());

            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Upload media file (called from JavaScript)
     */
    public function uploadMediaFile($file): ?string
    {
        $this->mediaUploadFile = $file;
        $this->handleUpload();

        return $this->selectedMedia['url'] ?? null;
    }

    /**
     * Set external URL
     */
    public function setExternalUrl(string $url): void
    {
        // Validate URL
        $validationResult = $this->validateMediaUrl($url);
        if ($validationResult !== true) {
            $this->dispatch('notify', type: 'error', message: $validationResult);
            return;
        }

        $this->selectedMedia = [
            'id' => null,
            'url' => $url,
            'alt' => '',
            'source' => 'external',
        ];

        if ($this->mediaPickerTargetElement) {
            $this->applyMediaToElement($this->mediaPickerTargetElement, $this->selectedMedia);
        }

        $this->mediaUrl = '';

        // CRITICAL FIX: Dispatch event with URL for Property Panel image-settings control
        $this->dispatch('uve-image-url-updated', url: $url);

        $this->dispatch('notify', type: 'success', message: 'Zewnetrzny URL ustawiony');

        Log::info('External media URL set', [
            'url' => $url,
            'element_id' => $this->mediaPickerTargetElement,
        ]);
    }

    /**
     * Clear selected media
     */
    public function clearMedia(): void
    {
        $previousMedia = $this->selectedMedia;
        $this->selectedMedia = null;

        if ($this->mediaPickerTargetElement) {
            $this->applyMediaToElement($this->mediaPickerTargetElement, null);
        }

        $this->dispatch('notify', type: 'info', message: 'Obraz usuniety');

        Log::debug('Media cleared', [
            'previous_url' => $previousMedia['url'] ?? null,
            'element_id' => $this->mediaPickerTargetElement,
        ]);
    }

    /**
     * Apply selected media to element
     * DEEP LOGGING VERSION for debugging
     */
    protected function applyMediaToElement(string $elementId, ?array $media): void
    {
        Log::info('=== applyMediaToElement START ===', [
            'elementId' => $elementId,
            'media' => $media,
            'blocks_count' => count($this->blocks ?? []),
        ]);

        $url = $media['url'] ?? '';
        $alt = $media['alt'] ?? '';

        Log::info('STEP 1: Media data', [
            'url' => $url,
            'alt' => $alt,
        ]);

        // FIX #4: DOM block IDs (block-1, block-3, etc.) DO NOT match $this->blocks indices!
        // We must iterate through ALL blocks and search for element by data-uve-id
        $foundBlockIndex = null;
        $htmlSource = null;
        $updatedHtml = null;

        Log::info('STEP 2: Iterating through all blocks to find element', [
            'blocks_count' => count($this->blocks),
            'elementId' => $elementId,
        ]);

        foreach ($this->blocks as $blockIndex => $block) {
            // Get HTML content from this block
            $html = null;
            $source = 'NONE';

            if (isset($block['content'])) {
                if (is_string($block['content'])) {
                    $html = $block['content'];
                    $source = 'content_string';
                } elseif (is_array($block['content']) && isset($block['content']['html']) && is_string($block['content']['html'])) {
                    $html = $block['content']['html'];
                    $source = 'content.html';
                }
            }
            if ($html === null && isset($block['compiled_html']) && is_string($block['compiled_html'])) {
                $html = $block['compiled_html'];
                $source = 'compiled_html';
            }
            if ($html === null && isset($block['compiledHtml']) && is_string($block['compiledHtml'])) {
                $html = $block['compiledHtml'];
                $source = 'compiledHtml';
            }

            if (empty($html)) {
                Log::debug('STEP 2: Block has no HTML, skipping', ['blockIndex' => $blockIndex]);
                continue;
            }

            Log::info('STEP 3: Trying block', [
                'blockIndex' => $blockIndex,
                'blockType' => $block['type'] ?? 'unknown',
                'html_length' => strlen($html),
                'source' => $source,
            ]);

            // Try to update image in this block's HTML
            $updated = $this->updateImageInHtml($html, $elementId, $url, $alt);

            if ($updated !== $html) {
                // Element was found and updated in this block!
                $foundBlockIndex = $blockIndex;
                $htmlSource = $source;
                $updatedHtml = $updated;

                Log::info('STEP 4: Element found in block!', [
                    'blockIndex' => $blockIndex,
                    'htmlSource' => $source,
                    'first_img_src_before' => $this->extractFirstImgSrc($html),
                    'first_img_src_after' => $this->extractFirstImgSrc($updated),
                ]);

                break; // Found it, stop searching
            }
        }

        if ($foundBlockIndex === null) {
            Log::error('=== applyMediaToElement FAILED: Element not found in any block ===', [
                'elementId' => $elementId,
                'blocks_searched' => count($this->blocks),
            ]);
            return;
        }

        // Update the blocks array
        $blocks = $this->blocks;

        if ($htmlSource === 'content_string') {
            $blocks[$foundBlockIndex]['content'] = $updatedHtml;
            Log::info('STEP 5: Updated content_string');
        } elseif ($htmlSource === 'content.html') {
            $blocks[$foundBlockIndex]['content']['html'] = $updatedHtml;
            Log::info('STEP 5: Updated content.html');
        } elseif ($htmlSource === 'compiled_html') {
            $blocks[$foundBlockIndex]['compiled_html'] = $updatedHtml;
            Log::info('STEP 5: Updated compiled_html');
        } elseif ($htmlSource === 'compiledHtml') {
            $blocks[$foundBlockIndex]['compiledHtml'] = $updatedHtml;
            Log::info('STEP 5: Updated compiledHtml');
        }

        // Reassign entire blocks array to trigger Livewire reactivity
        $this->blocks = $blocks;

        Log::info('STEP 6: Blocks array reassigned', [
            'blockIndex' => $foundBlockIndex,
            'new_html_length' => strlen($updatedHtml),
        ]);

        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        // Refresh preview - compile the block we actually modified
        Log::info('STEP 7: Calling compileBlockHtml', ['blockIndex' => $foundBlockIndex]);
        if (method_exists($this, 'compileBlockHtml')) {
            $this->compileBlockHtml($foundBlockIndex);

            // Log what compileBlockHtml produced
            $compiledHtml = $this->blocks[$foundBlockIndex]['compiled_html'] ?? null;
            Log::info('STEP 8: After compileBlockHtml', [
                'compiled_html_length' => strlen($compiledHtml ?? ''),
                'compiled_first_img_src' => $this->extractFirstImgSrc($compiledHtml ?? ''),
            ]);
        }

        // CRITICAL FIX: Invalidate computed properties cache after blocks update
        // Without this, getEditModeHtml() returns stale cached HTML
        // Livewire 3.x computed properties are cached and must be manually invalidated
        Log::info('STEP 9: Invalidating computed properties cache');
        unset($this->previewHtml);
        unset($this->editableIframeContent);

        Log::info('STEP 10: Triggering iframe refresh via browser event');
        // Dispatch browser event that Alpine can listen to with @uve-refresh.window
        $this->dispatch('uve-preview-refresh');
        // Also trigger via JS as backup
        $this->js('window.dispatchEvent(new CustomEvent("uve-media-applied"))');

        Log::info('=== applyMediaToElement END ===', [
            'success' => true,
            'blockIndex' => $foundBlockIndex,
            'elementId' => $elementId,
        ]);
    }

    /**
     * Helper: Extract first img src from HTML for logging
     */
    protected function extractFirstImgSrc(string $html): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Find element by structural matching - mirrors markChildElements() logic from UVE_Preview
     * This is CRITICAL because source HTML doesn't have data-uve-id markers (added only in preview)
     *
     * FIX: Must search WITHIN the correct visual block (pd-intro, pd-block, etc.)
     * because markChildElements() assigns indices PER BLOCK, not globally!
     */
    protected function findElementByStructuralMatching(\DOMXPath $xpath, string $elementId): ?\DOMElement
    {
        // Parse elementId format: block-{blockNum}-{type}-{elementIndex}
        if (!preg_match('/^block-(\d+)-([a-z]+)-(\d+)$/i', $elementId, $matches)) {
            Log::debug('findElementByStructuralMatching: Invalid elementId format', ['elementId' => $elementId]);
            return null;
        }

        $targetBlockNum = (int)$matches[1];
        $targetType = strtolower($matches[2]);
        $targetIndex = (int)$matches[3];

        Log::info('findElementByStructuralMatching: Searching', [
            'elementId' => $elementId,
            'targetBlockNum' => $targetBlockNum,
            'targetType' => $targetType,
            'targetIndex' => $targetIndex,
        ]);

        // Find visual blocks in HTML - MUST match injectEditableMarkers() XPath EXACTLY!
        // FIX #11: Use SAME XPath as UVE_Preview::injectEditableMarkers()
        $blockXPath = '//*[contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-cover")]';
        $visualBlocks = $xpath->query($blockXPath);

        Log::info('findElementByStructuralMatching: Found visual blocks', [
            'count' => $visualBlocks->length,
        ]);

        if ($visualBlocks->length === 0) {
            Log::warning('findElementByStructuralMatching: No visual blocks found, searching entire document');
            // Fallback: search in entire document
            return $this->findElementInContext($xpath, null, $targetType, $targetIndex);
        }

        // Find the target block (blockNum is 0-based, but visual blocks may start from 1)
        // In injectEditableMarkers(), blocks are numbered starting from 0
        $targetBlock = null;
        $blockCounter = 0;

        foreach ($visualBlocks as $block) {
            if ($blockCounter === $targetBlockNum) {
                $targetBlock = $block;
                break;
            }
            $blockCounter++;
        }

        if (!$targetBlock) {
            Log::warning('findElementByStructuralMatching: Target block not found', [
                'targetBlockNum' => $targetBlockNum,
                'totalBlocks' => $visualBlocks->length,
            ]);
            // Fallback: search in entire document
            return $this->findElementInContext($xpath, null, $targetType, $targetIndex);
        }

        Log::info('findElementByStructuralMatching: Searching within block', [
            'blockClass' => $targetBlock->getAttribute('class'),
        ]);

        return $this->findElementInContext($xpath, $targetBlock, $targetType, $targetIndex);
    }

    /**
     * Find element within a context (block or entire document)
     * Uses GLOBAL indexing (same as markChildElements in UVE_Preview.php)
     * All element types share ONE index counter!
     */
    protected function findElementInContext(\DOMXPath $xpath, ?\DOMElement $context, string $targetType, int $targetIndex): ?\DOMElement
    {
        // GLOBAL index counter - same as markChildElements()!
        $elementIndex = 0;
        $elementQueue = [];

        // XPath prefix: if context is given, search within it; otherwise search globally
        $prefix = $context ? '.' : '';

        // 1. Headings (h1-h6) - SAME ORDER as markChildElements()
        $headings = $xpath->query("{$prefix}//h1|{$prefix}//h2|{$prefix}//h3|{$prefix}//h4|{$prefix}//h5|{$prefix}//h6", $context);
        foreach ($headings as $heading) {
            $elementQueue[] = ['node' => $heading, 'type' => 'heading', 'globalIndex' => $elementIndex++];
        }

        // 2. Paragraphs (p not in li)
        $paragraphs = $xpath->query("{$prefix}//p[not(ancestor::li)]", $context);
        foreach ($paragraphs as $p) {
            $elementQueue[] = ['node' => $p, 'type' => 'text', 'globalIndex' => $elementIndex++];
        }

        // 3. Images
        $images = $xpath->query("{$prefix}//img", $context);
        foreach ($images as $img) {
            $elementQueue[] = ['node' => $img, 'type' => 'image', 'globalIndex' => $elementIndex++];
        }

        // 4. Button links
        $links = $xpath->query("{$prefix}//a[contains(@class, 'btn') or contains(@class, 'button')]", $context);
        foreach ($links as $link) {
            $elementQueue[] = ['node' => $link, 'type' => 'button', 'globalIndex' => $elementIndex++];
        }

        // 5. List items
        $listItems = $xpath->query("{$prefix}//li", $context);
        foreach ($listItems as $li) {
            $elementQueue[] = ['node' => $li, 'type' => 'listitem', 'globalIndex' => $elementIndex++];
        }

        // 6. Table cells
        $cells = $xpath->query("{$prefix}//td|{$prefix}//th", $context);
        foreach ($cells as $cell) {
            $elementQueue[] = ['node' => $cell, 'type' => 'cell', 'globalIndex' => $elementIndex++];
        }

        Log::info('findElementInContext: Built queue with GLOBAL indexing', [
            'total_elements' => count($elementQueue),
            'contextClass' => $context ? $context->getAttribute('class') : 'GLOBAL',
            'targetType' => $targetType,
            'targetIndex' => $targetIndex,
        ]);

        // Find element with matching type AND GLOBAL index
        foreach ($elementQueue as $item) {
            if ($item['type'] === $targetType && $item['globalIndex'] === $targetIndex) {
                Log::info('findElementInContext: Found element!', [
                    'matchedGlobalIndex' => $item['globalIndex'],
                    'matchedType' => $item['type'],
                    'nodeName' => $item['node']->nodeName,
                    'src' => $item['node']->getAttribute('src') ?? 'N/A',
                ]);
                return $item['node'];
            }
        }

        Log::warning('findElementInContext: Element not found', [
            'targetType' => $targetType,
            'targetIndex' => $targetIndex,
            'queueSize' => count($elementQueue),
        ]);

        return null;
    }

    /**
     * Update img src in raw HTML string
     */
    protected function updateImageInHtml(string $html, string $elementId, string $newSrc, string $newAlt = ''): string
    {
        // Use DOMDocument to parse and update HTML
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $element = null;

        // Try data-uve-id first (matches PropertyPanel logic)
        $elements = $xpath->query("//*[@data-uve-id='{$elementId}']");
        if ($elements->length > 0) {
            $element = $elements->item(0);
            Log::info('updateImageInHtml: Found via data-uve-id');
        }

        // Fallback: try id attribute
        if (!$element) {
            $elements = $xpath->query("//*[@id='{$elementId}']");
            if ($elements->length > 0) {
                $element = $elements->item(0);
                Log::info('updateImageInHtml: Found via id attribute');
            }
        }

        // Fallback: try data-element-id attribute
        if (!$element) {
            $elements = $xpath->query("//*[@data-element-id='{$elementId}']");
            if ($elements->length > 0) {
                $element = $elements->item(0);
                Log::info('updateImageInHtml: Found via data-element-id');
            }
        }

        // CRITICAL FALLBACK: Use structural matching (same logic as markChildElements)
        // Source HTML doesn't have data-uve-id markers - they're added only in preview!
        if (!$element) {
            Log::info('updateImageInHtml: Using structural matching fallback', ['elementId' => $elementId]);
            $element = $this->findElementByStructuralMatching($xpath, $elementId);
        }

        if ($element) {
            $tagName = strtolower($element->nodeName);

            if ($tagName === 'img') {
                $element->setAttribute('src', $newSrc);
                if ($newAlt) {
                    $element->setAttribute('alt', $newAlt);
                }
                // FIX #8: Update srcset to new URL (browser prefers srcset over src)
                // Replace all URLs in srcset with new URL
                if ($element->hasAttribute('srcset')) {
                    $element->setAttribute('srcset', $newSrc);
                    Log::info('updateImageInHtml: Updated srcset to new URL');
                }

                // FIX #8b: If img is inside <picture>, also update <source> srcset
                $parent = $element->parentNode;
                if ($parent && strtolower($parent->nodeName) === 'picture') {
                    foreach ($parent->childNodes as $child) {
                        if ($child->nodeName === 'source') {
                            $child->setAttribute('srcset', $newSrc);
                        }
                    }
                    Log::info('updateImageInHtml: Updated <source> srcset in <picture>');
                }

                Log::info('updateImageInHtml: Updated img src and srcset', [
                    'elementId' => $elementId,
                    'newSrc' => $newSrc,
                ]);
            } else {
                // For non-img elements, set background-image style
                $style = $element->getAttribute('style') ?: '';
                $style = preg_replace('/background-image:\s*url\([^)]*\);?/', '', $style);
                if ($newSrc) {
                    $style .= "background-image: url('{$newSrc}');";
                }
                $element->setAttribute('style', trim($style));
            }

            // Extract updated HTML from wrapper div
            $wrapper = $dom->getElementsByTagName('div')->item(0);
            $updatedHtml = '';
            foreach ($wrapper->childNodes as $child) {
                $updatedHtml .= $dom->saveHTML($child);
            }
            return $updatedHtml;
        }

        return $html;
    }

    /**
     * Validate uploaded file
     */
    protected function validateMediaFile($file): bool|string
    {
        if (!$file instanceof UploadedFile) {
            return 'Nieprawidlowy plik';
        }

        // Check file type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMediaTypes)) {
            return 'Nieprawidlowy typ pliku. Dozwolone: JPG, PNG, GIF, WebP, SVG';
        }

        // Check file size
        if ($file->getSize() > $this->maxMediaSize) {
            $maxMb = $this->maxMediaSize / 1048576;
            return "Plik jest za duzy. Maksymalny rozmiar: {$maxMb}MB";
        }

        return true;
    }

    /**
     * Validate external URL
     */
    protected function validateMediaUrl(string $url): bool|string
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Nieprawidlowy format URL';
        }

        // Check protocol
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return 'URL musi uzywac protokolu HTTP lub HTTPS';
        }

        // Check file extension (optional but helpful)
        $path = $parsed['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if ($extension && !in_array($extension, $allowedExtensions)) {
            return 'URL nie wskazuje na plik obrazu';
        }

        return true;
    }

    /**
     * Generate unique filename for upload
     */
    protected function generateMediaFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = substr(md5(uniqid()), 0, 8);

        // Sanitize original name
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $safeName = substr($safeName, 0, 50);

        return "{$safeName}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get storage path for media files
     */
    protected function getMediaStoragePath(): string
    {
        $productId = property_exists($this, 'productId') ? $this->productId : 'unknown';

        return "products/{$productId}/visual-editor";
    }

    /**
     * Get image dimensions from uploaded file
     */
    protected function getImageDimensionsFromFile(UploadedFile $file): ?array
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get product media for picker (ALL contexts - gallery + visual_description)
     * UVE picker should show all product media including previously uploaded UVE images
     */
    public function getProductMediaForPickerProperty(): array
    {
        if (!property_exists($this, 'productId') || !$this->productId) {
            return [];
        }

        // Try to load from product relationship - ALL media (no context filter)
        try {
            $product = Product::find($this->productId);

            if (!$product) {
                return [];
            }

            // Get all active media for this product (both gallery and visual_description)
            $media = Media::where('mediable_type', Product::class)
                ->where('mediable_id', $this->productId)
                ->where('is_active', true)
                ->orderBy('context') // Group by context
                ->orderBy('sort_order')
                ->get();

            return $media->map(function ($item) {
                return [
                    'id' => $item->id,
                    'url' => $item->url,
                    'thumbnail_url' => $item->thumbnail_url ?? $item->url,
                    'alt' => $item->alt_text ?? '',
                    'context' => $item->context ?? Media::CONTEXT_PRODUCT_GALLERY,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::warning('Failed to load product media for UVE picker', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Confirm media selection and close picker
     */
    public function confirmMediaSelection(): void
    {
        if ($this->selectedMedia && $this->mediaPickerTargetElement) {
            $this->applyMediaToElement($this->mediaPickerTargetElement, $this->selectedMedia);
        }

        $this->closeMediaPicker();
    }

    /**
     * Update media alt text
     */
    public function updateMediaAlt(string $alt): void
    {
        if ($this->selectedMedia) {
            $this->selectedMedia['alt'] = $alt;

            if ($this->mediaPickerTargetElement) {
                $this->updateElementInTree($this->mediaPickerTargetElement, function ($element) use ($alt) {
                    if (($element['tag'] ?? '') === 'img') {
                        $element['alt'] = $alt;
                    }
                    return $element;
                });
            }

            if (property_exists($this, 'isDirty')) {
                $this->isDirty = true;
            }
        }
    }
}
