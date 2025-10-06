<?php

namespace App\Http\Livewire\Products\Management\Traits;

use Illuminate\Support\Str;

/**
 * ProductFormUpdates Trait
 *
 * Handles form field updates, character counting, and auto-generation
 * Separated from main component per CLAUDE.md guidelines
 *
 * @package App\Http\Livewire\Products\Management\Traits
 */
trait ProductFormUpdates
{
    /*
    |--------------------------------------------------------------------------
    | FORM FIELD UPDATES - Real-time Updates
    |--------------------------------------------------------------------------
    */

    /**
     * Handle name field updates - auto-generate slug
     */
    public function updatedName(): void
    {
        // Auto-generate slug from name if slug is empty
        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        // Auto-generate meta title if empty
        if (empty($this->meta_title)) {
            $this->meta_title = $this->name;
        }

        $this->updateCharacterCounts();
    }

    /**
     * Handle SKU field updates - normalize format
     */
    public function updatedSku(): void
    {
        // Normalize SKU to uppercase and remove invalid characters
        $this->sku = strtoupper(preg_replace('/[^A-Z0-9\-_]/', '', $this->sku));
    }

    /**
     * Handle short description updates
     */
    public function updatedShortDescription(): void
    {
        $this->updateCharacterCounts();
    }

    /**
     * Handle long description updates
     */
    public function updatedLongDescription(): void
    {
        $this->updateCharacterCounts();
    }

    /**
     * Handle meta title updates
     */
    public function updatedMetaTitle(): void
    {
        $this->updateCharacterCounts();
    }

    /**
     * Handle meta description updates
     */
    public function updatedMetaDescription(): void
    {
        $this->updateCharacterCounts();
    }

    /**
     * Handle manufacturer updates - normalize
     */
    public function updatedManufacturer(): void
    {
        // Normalize manufacturer name (first letter uppercase)
        $this->manufacturer = ucfirst(strtolower(trim($this->manufacturer)));
    }

    /**
     * Handle supplier code updates - normalize
     */
    public function updatedSupplierCode(): void
    {
        // Normalize supplier code to uppercase
        $this->supplier_code = strtoupper(trim($this->supplier_code));
    }

    /**
     * Handle EAN updates - normalize
     */
    public function updatedEan(): void
    {
        // Remove any non-numeric characters
        $this->ean = preg_replace('/[^0-9]/', '', $this->ean);
    }

    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTING - Real-time Feedback
    |--------------------------------------------------------------------------
    */

    /**
     * Update all character counts for form fields
     */
    public function updateCharacterCounts(): void
    {
        $this->shortDescriptionCount = strlen($this->short_description);
        $this->longDescriptionCount = strlen($this->long_description);
    }

    /*
    |--------------------------------------------------------------------------
    | UTILITY METHODS - Form Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle slug field visibility
     */
    public function toggleSlugField(): void
    {
        $this->showSlugField = !$this->showSlugField;

        // If hiding slug field and it's empty, auto-generate from name
        if (!$this->showSlugField && empty($this->slug) && !empty($this->name)) {
            $this->slug = Str::slug($this->name);
        }
    }

    /**
     * Switch between form tabs
     *
     * @param string $tab
     */
    public function switchTab(string $tab): void
    {
        $validTabs = ['basic', 'description', 'physical', 'categories', 'attributes'];

        if (in_array($tab, $validTabs)) {
            $this->activeTab = $tab;
            $this->dispatch('tab-switched', tab: $tab);
        }
    }

    /**
     * Reset form to default values
     */
    public function resetForm(): void
    {
        $this->setDefaults();
        $this->updateCharacterCounts();
        $this->validationErrors = [];
        $this->successMessage = '';
    }

    /**
     * Clear success message
     */
    public function clearSuccessMessage(): void
    {
        $this->successMessage = '';
    }

    /**
     * Clear validation errors
     */
    public function clearValidationErrors(): void
    {
        $this->validationErrors = [];
    }
}