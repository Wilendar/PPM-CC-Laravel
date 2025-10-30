<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
use App\Models\FeatureType;
use App\Models\ProductFeature;
use App\Services\Product\FeatureManager;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;

/**
 * FeatureEditor Livewire Component
 *
 * Edit product features (technical specifications) - inline editing, grouped display, bulk save
 *
 * FEATURES:
 * - Toggle edit mode (view/edit)
 * - Add new features (select type from dropdown)
 * - Edit feature values (text, number, bool, select)
 * - Remove features (with confirmation)
 * - Bulk save all changes
 * - Grouped display by feature type
 * - Validation with error messages
 *
 * LIVEWIRE 3.x COMPLIANCE:
 * - wire:key for @foreach (prevent DOM issues)
 * - wire:model.blur for text inputs (better performance)
 * - wire:model.live for checkboxes (instant feedback)
 * - dispatch() for events (Livewire 3.x API)
 * - Computed properties for grouped data
 *
 * USAGE:
 * ```blade
 * <livewire:product.feature-editor :product="$product" />
 * ```
 *
 * @package App\Http\Livewire\Product
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-17)
 */
class FeatureEditor extends Component
{
    /**
     * Product instance
     */
    public Product $product;

    /**
     * Features collection
     */
    public Collection $features;

    /**
     * Available feature types (for adding new features)
     */
    public Collection $availableFeatureTypes;

    /**
     * Edit mode flag
     */
    public bool $editMode = false;

    /**
     * New feature type ID (for adding new feature)
     */
    public ?int $newFeatureTypeId = null;

    /**
     * Feature Manager service
     */
    private FeatureManager $featureManager;

    /**
     * Constructor - inject FeatureManager service
     */
    public function __construct()
    {
        parent::__construct();
        $this->featureManager = app(FeatureManager::class);
    }

    /**
     * Mount component
     */
    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadFeatures();
        $this->loadAvailableFeatureTypes();
    }

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        $rules = [
            'newFeatureTypeId' => 'nullable|exists:feature_types,id',
        ];

        // Dynamic validation rules for each feature
        foreach ($this->features as $index => $feature) {
            $rules["features.{$index}.feature_value_id"] = 'nullable|exists:feature_values,id';
            $rules["features.{$index}.custom_value"] = 'nullable|string|max:500';
        }

        return $rules;
    }

    /**
     * Load product features
     */
    public function loadFeatures(): void
    {
        $this->features = $this->product->features()
            ->with(['featureType', 'featureValue'])
            ->get();
    }

    /**
     * Load available feature types (for adding new features)
     */
    public function loadAvailableFeatureTypes(): void
    {
        $this->availableFeatureTypes = FeatureType::active()
            ->ordered()
            ->get();
    }

    /**
     * Toggle edit mode
     */
    public function toggleEditMode(): void
    {
        $this->editMode = !$this->editMode;

        Log::info('FeatureEditor::toggleEditMode', [
            'product_id' => $this->product->id,
            'edit_mode' => $this->editMode,
        ]);

        // Reload features when entering view mode (to discard unsaved changes)
        if (!$this->editMode) {
            $this->loadFeatures();
            $this->newFeatureTypeId = null;
        }
    }

    /**
     * Add new feature
     */
    public function addFeature(): void
    {
        $this->validate([
            'newFeatureTypeId' => 'required|exists:feature_types,id',
        ]);

        try {
            Log::info('FeatureEditor::addFeature CALLED', [
                'product_id' => $this->product->id,
                'feature_type_id' => $this->newFeatureTypeId,
            ]);

            $this->featureManager->addFeature($this->product, [
                'feature_type_id' => $this->newFeatureTypeId,
                'feature_value_id' => null,
                'custom_value' => null,
            ]);

            // Reload features
            $this->loadFeatures();
            $this->newFeatureTypeId = null;

            $this->dispatch('feature-added', productId: $this->product->id);
            session()->flash('message', 'Feature added successfully. Click "Save All Features" to persist changes.');

            Log::info('FeatureEditor::addFeature COMPLETED', [
                'product_id' => $this->product->id,
            ]);

        } catch (\Exception $e) {
            Log::error('FeatureEditor::addFeature FAILED', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error adding feature: ' . $e->getMessage());
        }
    }

    /**
     * Update feature value (called on blur/change)
     */
    public function updateFeatureValue(int $featureId, string $field, $value): void
    {
        try {
            Log::info('FeatureEditor::updateFeatureValue CALLED', [
                'feature_id' => $featureId,
                'field' => $field,
                'value' => $value,
            ]);

            $feature = ProductFeature::findOrFail($featureId);

            // Update the feature in collection (for immediate UI update)
            $featureInCollection = $this->features->firstWhere('id', $featureId);
            if ($featureInCollection) {
                $featureInCollection->{$field} = $value;
            }

            Log::info('FeatureEditor::updateFeatureValue COMPLETED', [
                'feature_id' => $featureId,
            ]);

        } catch (\Exception $e) {
            Log::error('FeatureEditor::updateFeatureValue FAILED', [
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);

            $this->addError("feature_{$featureId}", 'Error updating feature: ' . $e->getMessage());
        }
    }

    /**
     * Remove feature
     */
    public function removeFeature(int $featureId): void
    {
        try {
            Log::info('FeatureEditor::removeFeature CALLED', [
                'feature_id' => $featureId,
                'product_id' => $this->product->id,
            ]);

            $feature = ProductFeature::findOrFail($featureId);
            $this->featureManager->removeFeature($feature);

            // Reload features
            $this->loadFeatures();

            $this->dispatch('feature-removed', featureId: $featureId);
            session()->flash('message', 'Feature removed successfully.');

            Log::info('FeatureEditor::removeFeature COMPLETED', [
                'feature_id' => $featureId,
            ]);

        } catch (\Exception $e) {
            Log::error('FeatureEditor::removeFeature FAILED', [
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error removing feature: ' . $e->getMessage());
        }
    }

    /**
     * Save all features (bulk update)
     */
    public function saveAll(): void
    {
        $this->validate();

        try {
            Log::info('FeatureEditor::saveAll CALLED', [
                'product_id' => $this->product->id,
                'features_count' => $this->features->count(),
            ]);

            foreach ($this->features as $feature) {
                $this->featureManager->updateFeature($feature, [
                    'feature_type_id' => $feature->feature_type_id,
                    'feature_value_id' => $feature->feature_value_id,
                    'custom_value' => $feature->custom_value,
                ]);
            }

            // Reload features
            $this->loadFeatures();

            $this->dispatch('features-saved', productId: $this->product->id);
            session()->flash('message', 'All features saved successfully.');

            Log::info('FeatureEditor::saveAll COMPLETED', [
                'product_id' => $this->product->id,
            ]);

        } catch (\Exception $e) {
            Log::error('FeatureEditor::saveAll FAILED', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error saving features: ' . $e->getMessage());
        }
    }

    /**
     * Get grouped features (computed property)
     *
     * Groups features by feature type group
     *
     * LIVEWIRE 3.x: #[Computed] attribute for cached computed properties
     */
    #[Computed]
    public function groupedFeatures(): Collection
    {
        return $this->features->groupBy(function ($feature) {
            return $feature->featureType->group ?? 'General';
        });
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.product.feature-editor');
    }
}
