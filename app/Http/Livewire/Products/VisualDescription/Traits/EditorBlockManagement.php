<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Services\VisualEditor\BlockRegistry;

/**
 * Trait EditorBlockManagement.
 *
 * Handles block creation, deletion, reordering, and selection.
 */
trait EditorBlockManagement
{
    /**
     * Add a new block at the specified position.
     */
    public function addBlock(string $type, ?int $position = null): void
    {
        $registry = app(BlockRegistry::class);

        // Load dynamic blocks for current shop (each Livewire request is new PHP process)
        if (property_exists($this, 'shopId') && $this->shopId) {
            $registry->loadShopBlocks($this->shopId);
        }

        if (!$registry->has($type)) {
            $this->dispatch('notify', type: 'error', message: "Nieznany typ bloku: {$type}");
            return;
        }

        $block = $registry->get($type);
        $defaultData = $block->getDefaultData();

        $newBlock = [
            'id' => $this->generateBlockId(),
            'type' => $type,
            'data' => $defaultData,
        ];

        if ($position === null) {
            $this->blocks[] = $newBlock;
            $this->selectedBlockIndex = count($this->blocks) - 1;
        } else {
            array_splice($this->blocks, $position, 0, [$newBlock]);
            $this->selectedBlockIndex = $position;
        }

        $this->pushUndoState();
        $this->isDirty = true;

        $this->dispatch('block-added', blockId: $newBlock['id'], type: $type);
    }

    /**
     * Remove block at specified index.
     */
    public function removeBlock(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        $removedBlock = $this->blocks[$index];
        array_splice($this->blocks, $index, 1);

        if ($this->selectedBlockIndex === $index) {
            $this->selectedBlockIndex = null;
        } elseif ($this->selectedBlockIndex !== null && $this->selectedBlockIndex > $index) {
            $this->selectedBlockIndex--;
        }

        $this->pushUndoState();
        $this->isDirty = true;

        $this->dispatch('block-removed', blockId: $removedBlock['id']);
    }

    /**
     * Duplicate block at specified index.
     */
    public function duplicateBlock(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        $originalBlock = $this->blocks[$index];
        $newBlock = [
            'id' => $this->generateBlockId(),
            'type' => $originalBlock['type'],
            'data' => $originalBlock['data'],
        ];

        array_splice($this->blocks, $index + 1, 0, [$newBlock]);
        $this->selectedBlockIndex = $index + 1;

        $this->pushUndoState();
        $this->isDirty = true;

        $this->dispatch('block-duplicated', originalId: $originalBlock['id'], newId: $newBlock['id']);
    }

    /**
     * Move block from one position to another.
     */
    public function moveBlock(int $fromIndex, int $toIndex): void
    {
        if (!isset($this->blocks[$fromIndex]) || $fromIndex === $toIndex) {
            return;
        }

        $block = $this->blocks[$fromIndex];
        array_splice($this->blocks, $fromIndex, 1);
        array_splice($this->blocks, $toIndex, 0, [$block]);

        if ($this->selectedBlockIndex === $fromIndex) {
            $this->selectedBlockIndex = $toIndex;
        }

        $this->pushUndoState();
        $this->isDirty = true;
    }

    /**
     * Move block up (decrease index).
     */
    public function moveBlockUp(int $index): void
    {
        if ($index <= 0 || !isset($this->blocks[$index])) {
            return;
        }

        $this->moveBlock($index, $index - 1);
    }

    /**
     * Move block down (increase index).
     */
    public function moveBlockDown(int $index): void
    {
        if ($index >= count($this->blocks) - 1 || !isset($this->blocks[$index])) {
            return;
        }

        $this->moveBlock($index, $index + 1);
    }

    /**
     * Select a block by index.
     */
    public function selectBlock(?int $index): void
    {
        if ($index !== null && !isset($this->blocks[$index])) {
            return;
        }

        $this->selectedBlockIndex = $index;
        // NOTE: dispatch('block-selected') removed - caused infinite loop with listener
    }

    /**
     * Update block data property.
     */
    public function updateBlockProperty(int $index, string $key, mixed $value): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        data_set($this->blocks[$index]['data'], $key, $value);
        $this->isDirty = true;

        $this->dispatch('block-property-updated', index: $index, key: $key);
    }

    /**
     * Update entire block data.
     */
    public function updateBlockData(int $index, array $data): void
    {
        if (!isset($this->blocks[$index])) {
            return;
        }

        $this->blocks[$index]['data'] = array_merge($this->blocks[$index]['data'], $data);
        $this->pushUndoState();
        $this->isDirty = true;
    }

    /**
     * Get the currently selected block.
     */
    public function getSelectedBlockProperty(): ?array
    {
        if ($this->selectedBlockIndex === null || !isset($this->blocks[$this->selectedBlockIndex])) {
            return null;
        }

        return $this->blocks[$this->selectedBlockIndex];
    }

    /**
     * Generate unique block ID.
     */
    protected function generateBlockId(): string
    {
        return 'block_' . uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Reorder blocks based on new order array.
     */
    public function reorderBlocks(array $newOrder): void
    {
        $reorderedBlocks = [];

        foreach ($newOrder as $blockId) {
            foreach ($this->blocks as $block) {
                if ($block['id'] === $blockId) {
                    $reorderedBlocks[] = $block;
                    break;
                }
            }
        }

        if (count($reorderedBlocks) === count($this->blocks)) {
            $this->blocks = $reorderedBlocks;
            $this->pushUndoState();
            $this->isDirty = true;
        }
    }

    /**
     * Open Visual Block Builder for editing a specific block.
     *
     * ETAP_07f FAZA 4.6: VE -> VBB integration
     */
    public function editBlockInBuilder(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            $this->dispatch('notify', type: 'error', message: 'Blok nie istnieje');
            return;
        }

        $block = $this->blocks[$index];

        // Get HTML preview of the block to pass to VBB
        $html = $this->renderBlockPreview($index);

        // Store the editing index for when VBB returns
        session(['ve_editing_block_index' => $index]);

        // Dispatch event to open VBB with this block's HTML
        $this->dispatch('openBlockBuilder', shopId: $this->shopId, sourceHtml: $html);
    }

    /**
     * Handle block update from Visual Block Builder.
     *
     * ETAP_07f FAZA 4.6: VBB -> VE integration
     */
    public function handleBlockBuilderSave(string $html): void
    {
        $index = session('ve_editing_block_index');

        if ($index === null || !isset($this->blocks[$index])) {
            // No block was being edited, add as new prestashop-section
            $this->blocks[] = [
                'id' => $this->generateBlockId(),
                'type' => 'prestashop-section',
                'data' => ['html' => $html],
            ];
        } else {
            // Update existing block
            $this->blocks[$index]['data']['html'] = $html;
            session()->forget('ve_editing_block_index');
        }

        $this->pushUndoState();
        $this->isDirty = true;

        $this->dispatch('notify', type: 'success', message: 'Blok zostal zaktualizowany');
    }
}
