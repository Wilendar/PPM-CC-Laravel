<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use Illuminate\Support\Facades\Log;

/**
 * DescriptionModal - ETAP_06 FAZA 6.5.4
 *
 * Modal do edycji opisow (short_description, long_description) dla pending products.
 * Zawiera opcje "Publikuj bez opisow" (skip_descriptions flag).
 *
 * Structure: description_data = [
 *   'short_description' => 'Krotki opis...',
 *   'long_description' => 'Pelny opis HTML...',
 *   'skip_descriptions' => false,
 * ]
 *
 * @package App\Http\Livewire\Products\Import\Modals
 * @since 2025-12-10
 */
class DescriptionModal extends Component
{
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
     * Short description (text, max ~500 chars)
     */
    public string $shortDescription = '';

    /**
     * Long description (HTML content)
     */
    public string $longDescription = '';

    /**
     * Skip descriptions flag - publish without descriptions
     */
    public bool $skipDescriptions = false;

    /**
     * Processing flag
     */
    public bool $isProcessing = false;

    /**
     * Listeners
     */
    protected $listeners = [
        'openDescriptionModal' => 'openModal',
    ];

    /**
     * Open modal for a pending product
     */
    #[On('openDescriptionModal')]
    public function openModal(int $productId): void
    {
        $this->reset(['shortDescription', 'longDescription', 'skipDescriptions']);

        $this->pendingProductId = $productId;
        $this->pendingProduct = PendingProduct::find($productId);

        if (!$this->pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        // Load existing data
        $this->shortDescription = $this->pendingProduct->short_description ?? '';
        $this->longDescription = $this->pendingProduct->long_description ?? '';
        $this->skipDescriptions = $this->pendingProduct->skip_descriptions ?? false;

        $this->showModal = true;
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['pendingProductId', 'pendingProduct', 'shortDescription', 'longDescription', 'skipDescriptions']);
    }

    /**
     * Toggle skip descriptions flag
     */
    public function toggleSkipDescriptions(): void
    {
        $this->skipDescriptions = !$this->skipDescriptions;
    }

    /**
     * Copy short description to long description
     */
    public function copyShortToLong(): void
    {
        $this->longDescription = $this->shortDescription;
    }

    /**
     * Clear both descriptions
     */
    public function clearDescriptions(): void
    {
        $this->shortDescription = '';
        $this->longDescription = '';
    }

    /**
     * Save descriptions to pending product
     */
    public function saveDescriptions(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            $this->pendingProduct->update([
                'short_description' => trim($this->shortDescription) ?: null,
                'long_description' => trim($this->longDescription) ?: null,
                'skip_descriptions' => $this->skipDescriptions,
            ]);

            // Track skip history if flag is set
            if ($this->skipDescriptions) {
                $history = $this->pendingProduct->skip_history ?? [];
                $history['skip_descriptions'] = [
                    'set_at' => now()->toIso8601String(),
                    'set_by' => auth()->id(),
                    'set_by_name' => auth()->user()?->name ?? 'System',
                ];
                $this->pendingProduct->update(['skip_history' => $history]);
            }

            Log::info('[DescriptionModal] Saved descriptions', [
                'pending_product_id' => $this->pendingProductId,
                'short_length' => strlen($this->shortDescription),
                'long_length' => strlen($this->longDescription),
                'skip_descriptions' => $this->skipDescriptions,
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => $this->skipDescriptions
                    ? 'Ustawiono: publikuj bez opisow'
                    : 'Zapisano opisy produktu',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[DescriptionModal] Save failed', [
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
     * Get character counts for UI
     */
    public function getShortCountProperty(): int
    {
        return strlen($this->shortDescription);
    }

    public function getLongCountProperty(): int
    {
        return strlen($this->longDescription);
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.description-modal');
    }
}
