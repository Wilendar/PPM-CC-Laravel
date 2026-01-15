<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin\VisualEditor;

use App\Models\DescriptionBlock;
use App\Models\ProductDescription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Block Manager Component.
 *
 * Admin panel for managing visual description blocks.
 * Supports filtering, sorting, activation toggle and drag-to-reorder.
 */
class BlockManager extends Component
{
    // =====================
    // PUBLIC PROPERTIES
    // =====================

    /** @var string Search query for filtering blocks */
    public string $search = '';

    /** @var string|null Filter by category */
    public ?string $categoryFilter = null;

    /** @var bool Show only active blocks */
    public bool $showOnlyActive = false;

    /** @var int|null Selected block for preview */
    public ?int $selectedBlockId = null;

    /** @var bool Show preview modal */
    public bool $showPreviewModal = false;

    // =====================
    // LISTENERS
    // =====================

    protected $listeners = [
        'block-updated' => '$refresh',
        'refresh' => '$refresh',
    ];

    // =====================
    // COMPUTED PROPERTIES
    // =====================

    /**
     * Get filtered blocks ordered by sort_order.
     */
    #[Computed]
    public function filteredBlocks(): Collection
    {
        $query = DescriptionBlock::query()->ordered();

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('type', 'like', "%{$this->search}%");
            });
        }

        // Category filter
        if ($this->categoryFilter) {
            $query->byCategory($this->categoryFilter);
        }

        // Active only filter
        if ($this->showOnlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get block categories for filtering.
     */
    #[Computed]
    public function blockCategories(): array
    {
        return DescriptionBlock::getCategories();
    }

    /**
     * Get usage statistics for blocks.
     */
    #[Computed]
    public function usageStats(): array
    {
        $totalBlocks = DescriptionBlock::count();
        $activeBlocks = DescriptionBlock::active()->count();
        $inactiveBlocks = $totalBlocks - $activeBlocks;

        // Count total block usages in descriptions
        $totalUsages = $this->getTotalBlockUsages();

        return [
            'total' => $totalBlocks,
            'active' => $activeBlocks,
            'inactive' => $inactiveBlocks,
            'total_usages' => $totalUsages,
        ];
    }

    /**
     * Get selected block for preview.
     */
    #[Computed]
    public function selectedBlock(): ?DescriptionBlock
    {
        if (!$this->selectedBlockId) {
            return null;
        }

        return DescriptionBlock::find($this->selectedBlockId);
    }

    // =====================
    // ACTIONS
    // =====================

    /**
     * Toggle block active status.
     */
    public function toggleBlockActive(int $blockId): void
    {
        $block = DescriptionBlock::find($blockId);

        if (!$block) {
            $this->dispatch('notify', type: 'error', message: 'Blok nie istnieje');
            return;
        }

        $block->update(['is_active' => !$block->is_active]);

        $status = $block->is_active ? 'aktywowany' : 'dezaktywowany';
        $this->dispatch('notify', type: 'success', message: "Blok {$block->name} zostal {$status}");
    }

    /**
     * Update sort order via drag-and-drop.
     * Uses Livewire 3.x wire:sort directive.
     */
    public function updateSortOrder(array $items): void
    {
        foreach ($items as $item) {
            DescriptionBlock::where('id', $item['value'])
                ->update(['sort_order' => $item['order']]);
        }

        $this->dispatch('notify', type: 'success', message: 'Kolejnosc blokow zaktualizowana');
    }

    /**
     * Get usage count for a specific block.
     */
    public function getBlockUsageCount(int $blockId): int
    {
        $block = DescriptionBlock::find($blockId);

        if (!$block) {
            return 0;
        }

        return $this->countBlockUsages($block->type);
    }

    /**
     * Open preview modal for block.
     */
    public function previewBlock(int $blockId): void
    {
        $this->selectedBlockId = $blockId;
        $this->showPreviewModal = true;
    }

    /**
     * Close preview modal.
     */
    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->selectedBlockId = null;
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = null;
        $this->showOnlyActive = false;
    }

    // =====================
    // HELPERS
    // =====================

    /**
     * Get total block usages across all product descriptions.
     */
    private function getTotalBlockUsages(): int
    {
        $count = 0;

        ProductDescription::chunk(100, function ($descriptions) use (&$count) {
            foreach ($descriptions as $description) {
                $blocks = $description->blocks_json ?? [];
                $count += count($blocks);
            }
        });

        return $count;
    }

    /**
     * Count usages for a specific block type.
     */
    private function countBlockUsages(string $blockType): int
    {
        $count = 0;

        ProductDescription::chunk(100, function ($descriptions) use (&$count, $blockType) {
            foreach ($descriptions as $description) {
                $blocks = $description->blocks_json ?? [];
                foreach ($blocks as $block) {
                    if (($block['type'] ?? '') === $blockType) {
                        $count++;
                    }
                }
            }
        });

        return $count;
    }

    /**
     * Get category icon for display.
     */
    public function getCategoryIcon(string $category): string
    {
        return match ($category) {
            DescriptionBlock::CATEGORY_LAYOUT => 'fa-th-large',
            DescriptionBlock::CATEGORY_CONTENT => 'fa-align-left',
            DescriptionBlock::CATEGORY_MEDIA => 'fa-image',
            DescriptionBlock::CATEGORY_INTERACTIVE => 'fa-hand-pointer',
            default => 'fa-cube',
        };
    }

    /**
     * Get category color class for badge.
     */
    public function getCategoryColor(string $category): string
    {
        return match ($category) {
            DescriptionBlock::CATEGORY_LAYOUT => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            DescriptionBlock::CATEGORY_CONTENT => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            DescriptionBlock::CATEGORY_MEDIA => 'bg-green-500/20 text-green-400 border-green-500/30',
            DescriptionBlock::CATEGORY_INTERACTIVE => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            default => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
        };
    }

    // =====================
    // RENDER
    // =====================

    public function render(): View
    {
        return view('livewire.admin.visual-editor.block-manager')
            ->layout('layouts.admin');
    }
}
