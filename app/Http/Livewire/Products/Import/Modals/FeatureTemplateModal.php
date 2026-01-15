<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\FeatureType;
use App\Models\FeatureGroup;
use App\Models\FeatureValue;
use App\Models\FeatureTemplate;
use Illuminate\Support\Facades\Log;

/**
 * FeatureTemplateModal - ETAP_06 FAZA 5.5
 *
 * Modal do przypisywania cech produktu dla pending products.
 * Cechy przechowywane jako JSON w kolumnie feature_data.
 *
 * Structure: feature_data = [
 *   'features' => [
 *     ['feature_type_id' => 1, 'feature_type_code' => 'power', 'value' => '2000W', 'value_id' => null],
 *     ['feature_type_id' => 2, 'feature_type_code' => 'color', 'value' => 'Czerwony', 'value_id' => 5],
 *   ],
 *   'template_used' => 'pojazd_elektryczny',
 *   'updated_at' => '2025-12-09T...'
 * ]
 *
 * @package App\Http\Livewire\Products\Import\Modals
 * @since 2025-12-09
 */
class FeatureTemplateModal extends Component
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
     * Feature values input
     * Format: [feature_type_id => ['value' => '...', 'value_id' => null|int]]
     */
    public array $featureValues = [];

    /**
     * Selected feature group filter (optional)
     */
    public ?int $selectedGroupId = null;

    /**
     * Search query for features
     */
    public string $featureSearch = '';

    /**
     * Copy from product SKU
     */
    public string $copyFromSku = '';

    /**
     * Processing flag
     */
    public bool $isProcessing = false;

    /**
     * Selected template ID
     */
    public ?int $selectedTemplateId = null;

    /**
     * Show template dropdown
     */
    public bool $showTemplateSelector = true;

    /**
     * Show save as template modal
     */
    public bool $showSaveTemplateModal = false;

    /**
     * New template name (for saving)
     */
    public string $newTemplateName = '';

    /**
     * Listeners
     */
    protected $listeners = [
        'openFeatureModal' => 'openModal',
    ];

    /**
     * Open modal for a pending product
     */
    #[On('openFeatureModal')]
    public function openModal(int $productId): void
    {
        $this->reset(['featureValues', 'selectedGroupId', 'featureSearch', 'copyFromSku']);

        $this->pendingProductId = $productId;
        $this->pendingProduct = PendingProduct::find($productId);

        if (!$this->pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        // Load existing feature data
        $existingData = $this->pendingProduct->feature_data ?? [];

        if (!empty($existingData['features'])) {
            foreach ($existingData['features'] as $feature) {
                $typeId = $feature['feature_type_id'] ?? null;
                if ($typeId) {
                    $this->featureValues[$typeId] = [
                        'value' => $feature['value'] ?? '',
                        'value_id' => $feature['value_id'] ?? null,
                    ];
                }
            }
        }

        $this->showModal = true;
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['pendingProductId', 'pendingProduct', 'featureValues']);
    }

    /**
     * Update feature value (text input)
     */
    public function updateFeatureValue(int $typeId, string $value): void
    {
        $this->featureValues[$typeId] = [
            'value' => $value,
            'value_id' => $this->featureValues[$typeId]['value_id'] ?? null,
        ];
    }

    /**
     * Update feature value (select dropdown)
     */
    public function updateFeatureValueId(int $typeId, ?int $valueId): void
    {
        $value = '';
        if ($valueId) {
            $featureValue = FeatureValue::find($valueId);
            $value = $featureValue?->value ?? '';
        }

        $this->featureValues[$typeId] = [
            'value' => $value,
            'value_id' => $valueId,
        ];
    }

    /**
     * Copy features from another product
     */
    public function copyFromProduct(): void
    {
        if (empty($this->copyFromSku)) {
            return;
        }

        // Find product by SKU in PendingProduct or Product
        $source = PendingProduct::where('sku', $this->copyFromSku)->first();

        if (!$source) {
            $source = \App\Models\Product::where('sku', $this->copyFromSku)
                ->with('features.featureType')
                ->first();

            if ($source) {
                // Copy from Product model (has ProductFeature relation)
                foreach ($source->features as $feature) {
                    $typeId = $feature->feature_type_id;
                    $this->featureValues[$typeId] = [
                        'value' => $feature->value ?? '',
                        'value_id' => $feature->value_id ?? null,
                    ];
                }

                $this->dispatch('flash-message', [
                    'type' => 'success',
                    'message' => 'Skopiowano ' . count($source->features) . ' cech z produktu ' . $this->copyFromSku,
                ]);
                $this->copyFromSku = '';
                return;
            }

            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie znaleziono produktu o SKU: ' . $this->copyFromSku,
            ]);
            return;
        }

        // Copy from PendingProduct
        $featureData = $source->feature_data ?? [];
        if (!empty($featureData['features'])) {
            foreach ($featureData['features'] as $feature) {
                $typeId = $feature['feature_type_id'] ?? null;
                if ($typeId) {
                    $this->featureValues[$typeId] = [
                        'value' => $feature['value'] ?? '',
                        'value_id' => $feature['value_id'] ?? null,
                    ];
                }
            }

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Skopiowano cechy z pending produktu ' . $this->copyFromSku,
            ]);
        } else {
            $this->dispatch('flash-message', [
                'type' => 'info',
                'message' => 'Produkt ' . $this->copyFromSku . ' nie ma zdefiniowanych cech',
            ]);
        }

        $this->copyFromSku = '';
    }

    /**
     * Clear all feature values
     */
    public function clearFeatures(): void
    {
        $this->featureValues = [];
    }

    /**
     * Load template features into form
     */
    public function loadTemplate(): void
    {
        if (!$this->selectedTemplateId) {
            return;
        }

        $template = FeatureTemplate::find($this->selectedTemplateId);

        if (!$template) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Szablon nie zostal znaleziony',
            ]);
            return;
        }

        // Clear existing values
        $this->featureValues = [];

        // Load template features
        $templateFeatures = $template->features ?? [];
        $loadedCount = 0;

        foreach ($templateFeatures as $feature) {
            $featureName = $feature['name'] ?? null;
            $featureType = $feature['type'] ?? 'text';
            $defaultValue = $feature['default'] ?? '';

            if (!$featureName) {
                continue;
            }

            // Find matching FeatureType by name or code
            $type = FeatureType::where('name', $featureName)
                ->orWhere('code', strtolower(str_replace(' ', '_', $featureName)))
                ->first();

            if ($type) {
                $this->featureValues[$type->id] = [
                    'value' => $defaultValue,
                    'value_id' => null,
                ];
                $loadedCount++;
            }
        }

        Log::info('[FeatureTemplateModal] Template loaded', [
            'template_id' => $this->selectedTemplateId,
            'template_name' => $template->name,
            'features_loaded' => $loadedCount,
        ]);

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Wczytano szablon '{$template->name}' ({$loadedCount} cech)",
        ]);
    }

    /**
     * Open save as template modal
     */
    public function openSaveTemplateModal(): void
    {
        $this->newTemplateName = '';
        $this->showSaveTemplateModal = true;
    }

    /**
     * Close save as template modal
     */
    public function closeSaveTemplateModal(): void
    {
        $this->showSaveTemplateModal = false;
        $this->newTemplateName = '';
    }

    /**
     * Save current features as new template
     */
    public function saveAsTemplate(): void
    {
        if (empty(trim($this->newTemplateName))) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Podaj nazwe szablonu',
            ]);
            return;
        }

        // Check if template with this name already exists
        $existing = FeatureTemplate::where('name', trim($this->newTemplateName))->first();
        if ($existing) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Szablon o tej nazwie juz istnieje',
            ]);
            return;
        }

        try {
            // Build template features array
            $templateFeatures = [];

            foreach ($this->featureValues as $typeId => $data) {
                $value = trim($data['value'] ?? '');
                if ($value === '') {
                    continue;
                }

                $type = FeatureType::find($typeId);
                if (!$type) continue;

                $templateFeatures[] = [
                    'name' => $type->name,
                    'type' => $type->value_type,
                    'required' => false,
                    'default' => $value,
                ];
            }

            if (empty($templateFeatures)) {
                $this->dispatch('flash-message', [
                    'type' => 'warning',
                    'message' => 'Brak wypelnionych cech do zapisania',
                ]);
                return;
            }

            // Create new template
            $template = FeatureTemplate::create([
                'name' => trim($this->newTemplateName),
                'description' => 'Utworzono z panelu importu',
                'features' => $templateFeatures,
                'is_predefined' => false,
                'is_active' => true,
            ]);

            Log::info('[FeatureTemplateModal] New template saved', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'features_count' => count($templateFeatures),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zapisano nowy szablon '{$template->name}' (" . count($templateFeatures) . " cech)",
            ]);

            $this->closeSaveTemplateModal();

        } catch (\Exception $e) {
            Log::error('[FeatureTemplateModal] Save template failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update existing template with current values
     */
    public function updateTemplate(): void
    {
        if (!$this->selectedTemplateId) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie wybrano szablonu do aktualizacji',
            ]);
            return;
        }

        $template = FeatureTemplate::find($this->selectedTemplateId);

        if (!$template) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Szablon nie zostal znaleziony',
            ]);
            return;
        }

        // Check if predefined
        if ($template->is_predefined) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie mozna edytowac predefiniowanego szablonu',
            ]);
            return;
        }

        try {
            // Build template features array
            $templateFeatures = [];

            foreach ($this->featureValues as $typeId => $data) {
                $value = trim($data['value'] ?? '');
                if ($value === '') {
                    continue;
                }

                $type = FeatureType::find($typeId);
                if (!$type) continue;

                $templateFeatures[] = [
                    'name' => $type->name,
                    'type' => $type->value_type,
                    'required' => false,
                    'default' => $value,
                ];
            }

            // Update template
            $template->update([
                'features' => $templateFeatures,
            ]);

            Log::info('[FeatureTemplateModal] Template updated', [
                'template_id' => $template->id,
                'features_count' => count($templateFeatures),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zaktualizowano szablon '{$template->name}'",
            ]);

        } catch (\Exception $e) {
            Log::error('[FeatureTemplateModal] Update template failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad aktualizacji: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Save features to pending product
     */
    public function saveFeatures(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Build features array
            $features = [];

            foreach ($this->featureValues as $typeId => $data) {
                $value = trim($data['value'] ?? '');
                $valueId = $data['value_id'] ?? null;

                // Skip empty values
                if ($value === '' && !$valueId) {
                    continue;
                }

                $type = FeatureType::find($typeId);
                if (!$type) continue;

                $features[] = [
                    'feature_type_id' => $typeId,
                    'feature_type_code' => $type->code,
                    'feature_type_name' => $type->name,
                    'value' => $value,
                    'value_id' => $valueId,
                    'unit' => $type->unit,
                ];
            }

            // Build feature_data structure
            $featureData = [
                'features' => $features,
                'updated_at' => now()->toIso8601String(),
            ];

            $this->pendingProduct->update([
                'feature_data' => $featureData,
            ]);

            // Recalculate completion percentage
            $this->pendingProduct->recalculateCompletion();

            Log::info('[FeatureTemplateModal] Saved features', [
                'pending_product_id' => $this->pendingProductId,
                'feature_count' => count($features),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zapisano ' . count($features) . ' cech',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[FeatureTemplateModal] Save failed', [
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
     * Get available feature groups
     */
    public function getFeatureGroupsProperty()
    {
        return FeatureGroup::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available feature templates
     */
    public function getFeatureTemplatesProperty()
    {
        return FeatureTemplate::where('is_active', true)
            ->orderBy('is_predefined', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get selected template (for UI)
     */
    public function getSelectedTemplateProperty()
    {
        if (!$this->selectedTemplateId) {
            return null;
        }
        return FeatureTemplate::find($this->selectedTemplateId);
    }

    /**
     * Get feature types (filtered)
     * Note: feature_group_id column does not exist in current schema,
     * so group filtering is disabled for now
     */
    public function getFeatureTypesProperty()
    {
        return FeatureType::query()
            ->where('is_active', true)
            ->when($this->featureSearch, fn($q) => $q->where(function($q2) {
                $q2->where('name', 'like', '%' . $this->featureSearch . '%')
                   ->orWhere('code', 'like', '%' . $this->featureSearch . '%');
            }))
            ->orderBy('position')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Get predefined values for a feature type (if type=select)
     */
    public function getValuesForType(int $typeId)
    {
        return FeatureValue::where('feature_type_id', $typeId)
            ->orderBy('position')
            ->orderBy('value')
            ->get();
    }

    /**
     * Count filled features
     */
    public function getFilledCountProperty(): int
    {
        return collect($this->featureValues)->filter(fn($v) =>
            !empty($v['value']) || !empty($v['value_id'])
        )->count();
    }

    /**
     * Check if skip_features flag is set
     */
    public function getIsSkippedProperty(): bool
    {
        return $this->pendingProduct?->skip_features ?? false;
    }

    /**
     * Set "Brak cech" flag and close modal
     *
     * ETAP_06: Quick Actions - skip flag with history tracking
     */
    public function setSkipFeatures(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            $this->pendingProduct->setSkipFlag('skip_features', true);

            Log::info('[FeatureTemplateModal] Set skip_features flag', [
                'pending_product_id' => $this->pendingProductId,
                'user_id' => auth()->id(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'info',
                'message' => 'Oznaczono jako "Brak cech"',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[FeatureTemplateModal] Set skip flag failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Clear skip_features flag
     */
    public function clearSkipFeatures(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->pendingProduct->setSkipFlag('skip_features', false);

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Odznaczono "Brak cech"',
        ]);

        // Refresh the row to update status %
        $this->dispatch('refreshPendingProducts');
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.feature-template-modal');
    }
}
