<?php

namespace App\Http\Livewire\Admin\Import\Traits;

/**
 * ImportPanelModals Trait
 *
 * ETAP_06 FAZA 2: Modal state management dla ProductImportPanel
 *
 * PLACEHOLDER methods dla FAZY 3-4 implementation.
 * Modals:
 * - SKU Paste Modal (FAZA 3)
 * - CSV Import Modal (FAZA 4)
 *
 * @package App\Http\Livewire\Admin\Import\Traits
 */
trait ImportPanelModals
{
    /*
    |--------------------------------------------------------------------------
    | SKU PASTE MODAL (FAZA 3)
    |--------------------------------------------------------------------------
    */

    /**
     * Open SKU paste modal
     *
     * PLACEHOLDER: Implementation in FAZA 3
     */
    public function openSKUPasteModal(): void
    {
        $this->showSKUPasteModal = true;
    }

    /**
     * Close SKU paste modal
     */
    public function closeSKUPasteModal(): void
    {
        $this->showSKUPasteModal = false;
    }

    /**
     * Handle SKU paste submission
     *
     * PLACEHOLDER: Implementation in FAZA 3
     *
     * @param string $skuText Pasted SKU list (line-separated)
     */
    public function handleSKUPaste(string $skuText): void
    {
        // FAZA 3: Parse SKU list
        // FAZA 3: Create ImportSession
        // FAZA 3: Create PendingProduct records
        // FAZA 3: Flash success message
        // FAZA 3: Close modal

        session()->flash('warning', 'Funkcja dostepna w FAZY 3');
        $this->closeSKUPasteModal();
    }

    /*
    |--------------------------------------------------------------------------
    | CSV IMPORT MODAL (FAZA 4)
    |--------------------------------------------------------------------------
    */

    /**
     * Open CSV import modal
     *
     * PLACEHOLDER: Implementation in FAZA 4
     */
    public function openCSVImportModal(): void
    {
        $this->showCSVImportModal = true;
    }

    /**
     * Close CSV import modal
     */
    public function closeCSVImportModal(): void
    {
        $this->showCSVImportModal = false;
    }

    /**
     * Handle CSV file upload
     *
     * PLACEHOLDER: Implementation in FAZA 4
     *
     * @param mixed $file Uploaded CSV file
     */
    public function handleCSVUpload($file): void
    {
        // FAZA 4: Validate CSV
        // FAZA 4: Parse CSV
        // FAZA 4: Create ImportSession
        // FAZA 4: Create PendingProduct records
        // FAZA 4: Flash success message
        // FAZA 4: Close modal

        session()->flash('warning', 'Funkcja dostepna w FAZY 4');
        $this->closeCSVImportModal();
    }
}
