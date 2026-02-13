<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;

/**
 * DescriptionModal - ETAP_06 FAZA 6.5.4
 *
 * Modal do edycji opisow (short_description, long_description) dla pending products.
 * Obsluguje zakladki per-sklep PrestaShop z dziedziczeniem z domyslnych opisow.
 *
 * Structure: description_data = [
 *   'short_description' => 'Krotki opis...',
 *   'long_description' => 'Pelny opis HTML...',
 *   'skip_descriptions' => false,
 * ]
 *
 * shop_descriptions JSON: [
 *   shopId => ['short' => '...', 'long' => '...'],
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
     * Short description (text, max ~500 chars) - default tab
     */
    public string $shortDescription = '';

    /**
     * Long description (HTML content) - default tab
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
     * Active tab: 'default' or 'shop_{id}'
     */
    public string $activeTab = 'default';

    /**
     * Per-shop descriptions: [shopId => ['short' => '', 'long' => '']]
     */
    public array $shopDescriptions = [];

    /**
     * Available PS shops from publication_targets: [['id' => int, 'name' => string, 'url' => string]]
     */
    public array $availableShops = [];

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
        $this->reset([
            'shortDescription', 'longDescription', 'skipDescriptions',
            'activeTab', 'shopDescriptions', 'availableShops',
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

        // Load existing default descriptions
        $this->shortDescription = $this->pendingProduct->short_description ?? '';
        $this->longDescription = $this->pendingProduct->long_description ?? '';
        $this->skipDescriptions = $this->pendingProduct->skip_descriptions ?? false;

        // Load available PS shops from publication_targets
        $targets = $this->pendingProduct->publication_targets ?? [];
        $psShopIds = $targets['prestashop_shops'] ?? [];

        if (!empty($psShopIds)) {
            $this->availableShops = PrestaShopShop::whereIn('id', $psShopIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'url', 'label_color', 'label_icon'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'url' => $s->url,
                    'label_color' => $s->label_color,
                    'label_icon' => $s->label_icon,
                ])
                ->toArray();
        } else {
            $this->availableShops = [];
        }

        // Load per-shop descriptions from JSON column
        // Initialize keys for ALL available shops so wire:model binding works
        $rawShopDescs = $this->pendingProduct->shop_descriptions ?? [];
        $this->shopDescriptions = [];
        foreach ($this->availableShops as $shop) {
            $key = (string) $shop['id'];
            $this->shopDescriptions[$key] = [
                'short' => $rawShopDescs[$shop['id']]['short'] ?? $rawShopDescs[$key]['short'] ?? '',
                'long' => $rawShopDescs[$shop['id']]['long'] ?? $rawShopDescs[$key]['long'] ?? '',
            ];
        }

        $this->activeTab = 'default';
        $this->showModal = true;
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset([
            'pendingProductId', 'pendingProduct',
            'shortDescription', 'longDescription', 'skipDescriptions',
            'activeTab', 'shopDescriptions', 'availableShops',
        ]);
    }

    /**
     * Set active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Toggle skip descriptions flag
     */
    public function toggleSkipDescriptions(): void
    {
        $this->skipDescriptions = !$this->skipDescriptions;
    }

    /**
     * Copy short description to long description (context-aware)
     */
    public function copyShortToLong(): void
    {
        if ($this->activeTab === 'default') {
            $this->longDescription = $this->shortDescription;
        } else {
            $shopId = $this->getActiveShopId();
            if ($shopId !== null) {
                $descs = $this->shopDescriptions;
                $descs[$shopId]['long'] = $descs[$shopId]['short'] ?? '';
                $this->shopDescriptions = $descs;
            }
        }
    }

    /**
     * Copy default descriptions to a specific shop
     */
    public function copyDefaultToShop(int $shopId): void
    {
        $key = (string) $shopId;
        $descs = $this->shopDescriptions;
        $descs[$key] = [
            'short' => $this->shortDescription,
            'long' => $this->longDescription,
        ];
        $this->shopDescriptions = $descs;
    }

    /**
     * Clear shop-specific descriptions (revert to inheriting)
     */
    public function clearShopDescriptions(int $shopId): void
    {
        $key = (string) $shopId;
        $descs = $this->shopDescriptions;
        $descs[$key] = ['short' => '', 'long' => ''];
        $this->shopDescriptions = $descs;
    }

    /**
     * Clear both default descriptions
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
            // Clean shop descriptions: remove entries with both empty
            $cleanShopDescs = [];
            foreach ($this->shopDescriptions as $shopId => $descs) {
                $short = trim($descs['short'] ?? '');
                $long = trim($descs['long'] ?? '');
                if ($short !== '' || $long !== '') {
                    $cleanShopDescs[(int) $shopId] = [
                        'short' => $short,
                        'long' => $long,
                    ];
                }
            }

            $this->pendingProduct->update([
                'short_description' => trim($this->shortDescription) ?: null,
                'long_description' => trim($this->longDescription) ?: null,
                'skip_descriptions' => $this->skipDescriptions,
                'shop_descriptions' => !empty($cleanShopDescs) ? $cleanShopDescs : null,
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
                'shop_descriptions_count' => count($cleanShopDescs),
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
     * Get character counts for UI - default short
     */
    public function getShortCountProperty(): int
    {
        return strlen($this->shortDescription);
    }

    /**
     * Get character counts for UI - default long
     */
    public function getLongCountProperty(): int
    {
        return strlen($this->longDescription);
    }

    /**
     * Get active shop ID from tab name, or null if default tab
     */
    protected function getActiveShopId(): ?string
    {
        if (str_starts_with($this->activeTab, 'shop_')) {
            return substr($this->activeTab, 5);
        }
        return null;
    }

    /**
     * Get description status for a shop tab badge
     */
    public function getShopDescriptionStatus(int $shopId): string
    {
        $key = (string) $shopId;
        $descs = $this->shopDescriptions[$key] ?? [];
        $hasShort = !empty(trim($descs['short'] ?? ''));
        $hasLong = !empty(trim($descs['long'] ?? ''));

        if ($hasShort || $hasLong) {
            return 'custom';
        }
        return 'inherited';
    }

    /**
     * Get filled count for a shop (0, 1, or 2)
     */
    public function getShopFilledCount(int $shopId): int
    {
        $key = (string) $shopId;
        $descs = $this->shopDescriptions[$key] ?? [];
        $count = 0;
        if (!empty(trim($descs['short'] ?? ''))) {
            $count++;
        }
        if (!empty(trim($descs['long'] ?? ''))) {
            $count++;
        }
        return $count;
    }

    /**
     * Build footer status string
     */
    public function getFooterStatusProperty(): string
    {
        $parts = [];

        // Default tab status
        $defaultCount = 0;
        if (!empty(trim($this->shortDescription))) {
            $defaultCount++;
        }
        if (!empty(trim($this->longDescription))) {
            $defaultCount++;
        }
        $parts[] = "Domyslny: {$defaultCount}/2";

        // Shop statuses
        foreach ($this->availableShops as $shop) {
            $status = $this->getShopDescriptionStatus($shop['id']);
            $shopName = $shop['name'];
            if ($status === 'custom') {
                $filled = $this->getShopFilledCount($shop['id']);
                $parts[] = "{$shopName}: {$filled}/2";
            } else {
                $parts[] = "{$shopName}: dziedziczy";
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.description-modal');
    }
}
