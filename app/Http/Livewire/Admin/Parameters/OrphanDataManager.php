<?php

namespace App\Http\Livewire\Admin\Parameters;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\OrphanDataCleanupService;

/**
 * Orphan Data Manager Component
 *
 * Panel do zarządzania osieroconymi danymi w bazie PPM.
 * Umożliwia wykrywanie i czyszczenie sierocych rekordów.
 *
 * @package App\Http\Livewire\Admin\Parameters
 * @since 2025-12-15
 */
class OrphanDataManager extends Component
{
    /**
     * Statistics per orphan type
     */
    public array $stats = [];

    /**
     * Orphan type definitions
     */
    public array $orphanTypes = [];

    /**
     * Currently selected type for details view
     */
    public ?string $selectedType = null;

    /**
     * Orphan records for selected type
     */
    public array $selectedOrphans = [];

    /**
     * Show confirmation modal
     */
    public bool $showConfirmModal = false;

    /**
     * Type pending cleanup confirmation
     */
    public ?string $pendingCleanupType = null;

    /**
     * Processing state
     */
    public bool $isProcessing = false;

    /**
     * Last cleanup result
     */
    public ?array $lastCleanupResult = null;

    /**
     * Show cleanup all confirmation
     */
    public bool $showCleanupAllModal = false;

    protected OrphanDataCleanupService $cleanupService;

    public function boot(OrphanDataCleanupService $cleanupService): void
    {
        $this->cleanupService = $cleanupService;
    }

    public function mount(): void
    {
        $this->orphanTypes = OrphanDataCleanupService::ORPHAN_TYPES;
        $this->refreshStats();
    }

    /**
     * Refresh orphan statistics
     */
    public function refreshStats(): void
    {
        $this->stats = $this->cleanupService->getOrphanStats();
        $this->lastCleanupResult = null;

        // Refresh selected orphans if type is selected
        if ($this->selectedType) {
            $this->loadOrphansForType($this->selectedType);
        }
    }

    /**
     * Select type and load details
     */
    public function selectType(string $type): void
    {
        if ($this->selectedType === $type) {
            // Toggle off if clicking same type
            $this->selectedType = null;
            $this->selectedOrphans = [];
            return;
        }

        $this->selectedType = $type;
        $this->loadOrphansForType($type);
    }

    /**
     * Load orphan records for type
     */
    protected function loadOrphansForType(string $type): void
    {
        $this->selectedOrphans = match ($type) {
            'shop_mappings_categories' => $this->cleanupService->getOrphanCategoryMappings(50)->toArray(),
            'shop_mappings_products' => $this->cleanupService->getOrphanProductMappings(50)->toArray(),
            'media_products' => $this->cleanupService->getOrphanProductMedia(50)->toArray(),
            'media_variants' => $this->cleanupService->getOrphanVariantMedia(50)->toArray(),
            'product_categories' => $this->cleanupService->getOrphanProductCategories(50)->toArray(),
            'conflict_logs' => $this->cleanupService->getOrphanConflictLogs(50)->toArray(),
            default => [],
        };
    }

    /**
     * Show cleanup confirmation modal
     */
    public function confirmCleanup(string $type): void
    {
        $this->pendingCleanupType = $type;
        $this->showConfirmModal = true;
    }

    /**
     * Cancel cleanup
     */
    public function cancelCleanup(): void
    {
        $this->pendingCleanupType = null;
        $this->showConfirmModal = false;
    }

    /**
     * Execute cleanup for pending type
     */
    public function executeCleanup(): void
    {
        if (!$this->pendingCleanupType) {
            return;
        }

        $this->isProcessing = true;

        try {
            $this->lastCleanupResult = $this->cleanupService->cleanup(
                $this->pendingCleanupType,
                dryRun: false
            );

            $this->refreshStats();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Wyczyszczono {$this->lastCleanupResult['deleted']} rekordow typu: {$this->orphanTypes[$this->pendingCleanupType]['label']}",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Blad podczas czyszczenia: {$e->getMessage()}",
            ]);
        } finally {
            $this->isProcessing = false;
            $this->showConfirmModal = false;
            $this->pendingCleanupType = null;
        }
    }

    /**
     * Show cleanup all confirmation
     */
    public function confirmCleanupAll(): void
    {
        $this->showCleanupAllModal = true;
    }

    /**
     * Cancel cleanup all
     */
    public function cancelCleanupAll(): void
    {
        $this->showCleanupAllModal = false;
    }

    /**
     * Execute cleanup for all types
     */
    public function executeCleanupAll(): void
    {
        $this->isProcessing = true;

        try {
            $results = $this->cleanupService->cleanupAll(dryRun: false);
            $totalDeleted = array_sum(array_column($results, 'deleted'));

            $this->refreshStats();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Wyczyszczono lacznie {$totalDeleted} sierocych rekordow",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Blad podczas czyszczenia: {$e->getMessage()}",
            ]);
        } finally {
            $this->isProcessing = false;
            $this->showCleanupAllModal = false;
        }
    }

    /**
     * Get total orphan count
     */
    public function getTotalOrphanCountProperty(): int
    {
        return array_sum($this->stats);
    }

    /**
     * Check if any orphans exist
     */
    public function getHasOrphansProperty(): bool
    {
        return $this->totalOrphanCount > 0;
    }

    public function render()
    {
        return view('livewire.admin.parameters.orphan-data-manager');
    }
}
