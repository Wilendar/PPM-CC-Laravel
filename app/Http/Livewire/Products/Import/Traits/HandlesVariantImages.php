<?php

namespace App\Http\Livewire\Products\Import\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait HandlesVariantImages
 *
 * Handles variant image assignment, per-variant cover selection,
 * and variant image grouping for the ImageUploadModal.
 *
 * IMPORTANT: All modifications to $variantCovers MUST reassign the full array
 * to avoid Livewire 3 hydration issues with associative arrays.
 */
trait HandlesVariantImages
{
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
     * Per-variant cover image mapping
     * Format: ['sku_suffix' => image_index], e.g. ['-RED' => 1, '-BLUE' => 3]
     */
    public array $variantCovers = [];

    /**
     * Assign image to a variant
     */
    public function assignToVariant(int $imageIndex, ?string $variantSku): void
    {
        if (!isset($this->images[$imageIndex])) {
            return;
        }

        $oldVariantSku = $this->images[$imageIndex]['variant_sku'] ?? null;
        $newVariantSku = $variantSku ?: null;

        // Build new covers array (full reassignment for Livewire hydration)
        $covers = $this->variantCovers;

        // Clean up variant cover if image is being unassigned from old variant
        if ($oldVariantSku && $oldVariantSku !== $newVariantSku) {
            if (isset($covers[$oldVariantSku]) && $covers[$oldVariantSku] === $imageIndex) {
                unset($covers[$oldVariantSku]);
            }
        }

        $this->images[$imageIndex]['variant_sku'] = $newVariantSku;

        // Auto-assign as variant cover if this variant has no cover yet
        if ($newVariantSku && !isset($covers[$newVariantSku])) {
            $covers[$newVariantSku] = $imageIndex;
        }

        // Full reassignment (critical for Livewire 3 hydration)
        $this->variantCovers = $covers;
    }

    /**
     * Set image as cover for a specific variant
     */
    public function setVariantCover(int $imageIndex, string $variantSku): void
    {
        if (!isset($this->images[$imageIndex])) {
            return;
        }

        // Image must be assigned to this variant
        if (($this->images[$imageIndex]['variant_sku'] ?? null) !== $variantSku) {
            return;
        }

        // Full reassignment (critical for Livewire 3 hydration)
        $covers = $this->variantCovers;
        $covers[$variantSku] = $imageIndex;
        $this->variantCovers = $covers;
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

        $parts = [];
        foreach ($variant['attributes'] ?? [] as $attr) {
            $parts[] = $attr['value'] ?? '';
        }

        return implode(' / ', $parts) ?: ($variant['sku_suffix'] ?? 'Wariant');
    }

    /**
     * Get images grouped by variant for preview section
     * Returns: ['_main' => [...], '-RED' => [...], '-BLUE' => [...]]
     */
    public function getVariantImageGroupsProperty(): array
    {
        $groups = ['_main' => []];

        foreach ($this->variants as $variant) {
            $sku = $variant['sku_suffix'] ?? '';
            if ($sku !== '') {
                $groups[$sku] = [];
            }
        }

        foreach ($this->images as $index => $image) {
            $variantSku = $image['variant_sku'] ?? null;
            $key = ($variantSku !== null && $variantSku !== '') ? $variantSku : '_main';
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = ['index' => $index, 'image' => $image];
        }

        return $groups;
    }

    /**
     * Find first image index assigned to a variant
     */
    protected function findFirstImageForVariant(string $variantSku): ?int
    {
        foreach ($this->images as $index => $image) {
            if (($image['variant_sku'] ?? null) === $variantSku) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Synchronize variantCovers after image removal
     * Must be called AFTER $this->images has been reindexed
     */
    protected function syncVariantCoversAfterRemoval(int $removedIndex): void
    {
        $newCovers = [];
        foreach ($this->variantCovers as $sku => $coverIndex) {
            if ($coverIndex === $removedIndex) {
                $firstIndex = $this->findFirstImageForVariant($sku);
                if ($firstIndex !== null) {
                    $newCovers[$sku] = $firstIndex;
                }
            } elseif ($coverIndex > $removedIndex) {
                $newCovers[$sku] = $coverIndex - 1;
            } else {
                $newCovers[$sku] = $coverIndex;
            }
        }
        $this->variantCovers = $newCovers;
    }

    /**
     * Synchronize variantCovers after image swap (moveUp/moveDown)
     */
    protected function syncVariantCoversAfterSwap(int $indexA, int $indexB): void
    {
        // Full reassignment (critical for Livewire 3 hydration)
        $covers = $this->variantCovers;
        foreach ($covers as $sku => $coverIndex) {
            if ($coverIndex === $indexA) {
                $covers[$sku] = $indexB;
            } elseif ($coverIndex === $indexB) {
                $covers[$sku] = $indexA;
            }
        }
        $this->variantCovers = $covers;
    }
}
