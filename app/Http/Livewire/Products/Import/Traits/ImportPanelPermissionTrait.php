<?php

namespace App\Http\Livewire\Products\Import\Traits;

/**
 * FAZA 9.1.5: Import Panel Permission Trait
 *
 * Provides granular permission checks (P1-P11) for import panel columns and actions.
 * Caches user permissions once per request for performance.
 *
 * Permission mapping:
 * P1  = import.images            -> Obraz + Zdjecia
 * P2  = import.basic_data        -> SKU + Nazwa + Typ + modal Importu
 * P3  = import.prices            -> Cena + modal cen
 * P4  = import.categories        -> Kategorie L3-L6
 * P5  = import.publication_targets -> Kolumna Publikacja
 * P6  = import.variants          -> Warianty (akcja)
 * P7  = import.compatibility     -> Dopasowania (akcja)
 * P8  = import.descriptions      -> Opisy (akcja)
 * P9  = import.schedule          -> Data i czas publikacji
 * P10 = import.publish           -> Przycisk Publikuj
 * P11 = import.manage            -> Duplikuj + Usun
 *
 * @see config/permissions/import.php
 */
trait ImportPanelPermissionTrait
{
    /**
     * Cached permission results for current request
     */
    protected ?array $importPermissionCache = null;

    /**
     * Load and cache all import permissions for current user
     */
    protected function loadImportPermissions(): array
    {
        if ($this->importPermissionCache !== null) {
            return $this->importPermissionCache;
        }

        $user = auth()->user();
        if (!$user) {
            $this->importPermissionCache = [];
            return $this->importPermissionCache;
        }

        $permissions = [
            'images' => $user->can('import.images'),
            'basic_data' => $user->can('import.basic_data'),
            'prices' => $user->can('import.prices'),
            'categories' => $user->can('import.categories'),
            'publication_targets' => $user->can('import.publication_targets'),
            'variants' => $user->can('import.variants'),
            'compatibility' => $user->can('import.compatibility'),
            'descriptions' => $user->can('import.descriptions'),
            'schedule' => $user->can('import.schedule'),
            'publish' => $user->can('import.publish'),
            'manage' => $user->can('import.manage'),
        ];

        $this->importPermissionCache = $permissions;
        return $this->importPermissionCache;
    }

    /**
     * Reset permission cache (call on user change)
     */
    public function resetImportPermissionCache(): void
    {
        $this->importPermissionCache = null;
    }

    // =========================================================================
    // P1-P11 Permission Check Methods
    // =========================================================================

    /** P1: Obraz + Zdjecia */
    public function canSeeImages(): bool
    {
        return $this->loadImportPermissions()['images'] ?? false;
    }

    /** P2: SKU + Nazwa + Typ (modal import) */
    public function canSeeBasicData(): bool
    {
        return $this->loadImportPermissions()['basic_data'] ?? false;
    }

    /** P3: Cena */
    public function canSeePrices(): bool
    {
        return $this->loadImportPermissions()['prices'] ?? false;
    }

    /** P4: Kategorie L3-L6 */
    public function canSeeCategories(): bool
    {
        return $this->loadImportPermissions()['categories'] ?? false;
    }

    /** P5: Publikacja (targety) */
    public function canSeePublication(): bool
    {
        return $this->loadImportPermissions()['publication_targets'] ?? false;
    }

    /** P6: Warianty */
    public function canManageVariants(): bool
    {
        return $this->loadImportPermissions()['variants'] ?? false;
    }

    /** P7: Dopasowania */
    public function canManageCompatibility(): bool
    {
        return $this->loadImportPermissions()['compatibility'] ?? false;
    }

    /** P8: Opisy */
    public function canManageDescriptions(): bool
    {
        return $this->loadImportPermissions()['descriptions'] ?? false;
    }

    /** P9: Data i czas publikacji */
    public function canSeeScheduleDate(): bool
    {
        return $this->loadImportPermissions()['schedule'] ?? false;
    }

    /** P10: Przycisk Publikuj */
    public function canPublish(): bool
    {
        return $this->loadImportPermissions()['publish'] ?? false;
    }

    /** P11: Duplikuj + Usun */
    public function canDuplicateDelete(): bool
    {
        return $this->loadImportPermissions()['manage'] ?? false;
    }

    // =========================================================================
    // Visible Columns Helper
    // =========================================================================

    /**
     * Get list of visible column keys based on user permissions
     *
     * Used in blade views for @if checks and header rendering.
     */
    public function getVisibleColumns(): array
    {
        $columns = [];

        if ($this->canSeeImages()) {
            $columns[] = 'image';
        }
        if ($this->canSeeBasicData()) {
            $columns[] = 'sku';
            $columns[] = 'name';
            $columns[] = 'type';
        }
        if ($this->canSeePrices()) {
            $columns[] = 'price';
        }
        if ($this->canSeeCategories()) {
            $columns[] = 'categories';
        }
        if ($this->canSeePublication()) {
            $columns[] = 'publication';
        }
        if ($this->canSeeScheduleDate()) {
            $columns[] = 'schedule_date';
        }

        // Status is always visible
        $columns[] = 'status';

        if ($this->canPublish()) {
            $columns[] = 'publish_button';
        }

        // Actions column visible if any action permission granted
        if ($this->canSeeImages() || $this->canManageVariants() ||
            $this->canManageCompatibility() || $this->canManageDescriptions() ||
            $this->canDuplicateDelete()) {
            $columns[] = 'actions';
        }

        return $columns;
    }

    /**
     * Get visible action buttons based on permissions
     */
    public function getVisibleActions(): array
    {
        $actions = [];

        if ($this->canManageVariants()) {
            $actions[] = 'variants';
        }
        if ($this->canManageCompatibility()) {
            $actions[] = 'compatibility';
        }
        if ($this->canSeeImages()) {
            $actions[] = 'images';
        }
        if ($this->canManageDescriptions()) {
            $actions[] = 'descriptions';
        }
        if ($this->canDuplicateDelete()) {
            $actions[] = 'duplicate';
            $actions[] = 'delete';
        }

        return $actions;
    }
}
