<?php

namespace App\Http\Livewire\Admin\Features;

use App\Models\FeatureTemplate;
use App\Models\FeatureType;
use App\Models\Product;
use App\Services\Product\FeatureManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * VehicleFeatureManagement Livewire Component
 *
 * Standalone management page for vehicle feature templates (NOT product-specific)
 *
 * FEATURES:
 * - Template management (predefined + custom)
 * - Template editor modal (CRUD operations)
 * - Feature library sidebar (50+ predefined features)
 * - Bulk assign wizard (apply template to multiple products)
 *
 * LIVEWIRE 3.x COMPLIANCE:
 * - wire:key for @foreach loops
 * - dispatch() events (NOT emit())
 * - NO constructor DI (lazy loading pattern)
 * - Computed properties for grouped data
 * - Loading states (wire:loading)
 *
 * ARCHITECTURE:
 * - FeatureManager service for ALL business logic
 * - NO direct model queries in component
 * - Transaction-based bulk operations
 * - Validation with error messages
 *
 * USAGE:
 * ```blade
 * <livewire:admin.features.vehicle-feature-management />
 * ```
 *
 * @package App\Http\Livewire\Admin\Features
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-23)
 */
class VehicleFeatureManagement extends Component
{
    // ========================================
    // TEMPLATE MANAGEMENT
    // ========================================

    /**
     * TEST COUNTER - verify Livewire reactivity
     */
    public int $testCounter = 0;

    /**
     * Show/hide template editor modal
     */
    public bool $showTemplateEditor = false;

    /**
     * Currently editing template ID (null = new template)
     */
    public ?int $editingTemplateId = null;

    /**
     * Template name input
     */
    public string $templateName = '';

    /**
     * Template features array (for editor)
     * Format: [['name' => 'VIN', 'type' => 'text', 'required' => true, 'default' => ''], ...]
     */
    public array $templateFeatures = [];

    /**
     * Custom templates collection (loaded from DB)
     */
    public Collection $customTemplates;

    /**
     * Predefined templates collection (loaded from DB)
     */
    public Collection $predefinedTemplates;

    // ========================================
    // FEATURE LIBRARY
    // ========================================

    /**
     * Search query for feature library
     */
    public string $searchFeature = '';

    /**
     * Predefined feature library (50+ features grouped)
     */
    public array $featureLibrary = [];

    // ========================================
    // BULK ASSIGN WIZARD
    // ========================================

    /**
     * Show/hide bulk assign modal
     */
    public bool $showBulkAssignModal = false;

    /**
     * Selected template ID for bulk assign
     */
    public ?int $selectedTemplateId = null;

    /**
     * Bulk assign scope (all_vehicles / by_category)
     */
    public string $bulkAssignScope = 'all_vehicles';

    /**
     * Category ID for bulk assign (if scope = by_category)
     */
    public ?int $bulkAssignCategoryId = null;

    /**
     * Bulk assign action (add_features / replace_features)
     */
    public string $bulkAssignAction = 'add_features';

    /**
     * Products count matching bulk assign scope
     */
    public int $bulkAssignProductsCount = 0;

    // ========================================
    // SERVICE LAYER (LAZY LOADING)
    // ========================================

    /**
     * FeatureManager service instance (lazy loaded)
     */
    private ?FeatureManager $featureManager = null;

    /**
     * Get FeatureManager service instance
     *
     * @return FeatureManager
     */
    protected function getFeatureManager(): FeatureManager
    {
        if (!$this->featureManager) {
            $this->featureManager = app(FeatureManager::class);
        }
        return $this->featureManager;
    }

    // ========================================
    // LIFECYCLE HOOKS
    // ========================================

    /**
     * Mount component
     */
    public function mount(): void
    {
        $this->loadPredefinedTemplates();
        $this->loadCustomTemplates();
        $this->loadFeatureLibrary();
    }

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        return [
            'templateName' => 'required|string|max:255',
            'templateFeatures' => 'required|array|min:1',
            'templateFeatures.*.name' => 'required|string|max:255',
            'templateFeatures.*.type' => 'required|in:text,number,bool,select',
            'templateFeatures.*.required' => 'boolean',
            'templateFeatures.*.default' => 'nullable|string|max:255',
            'selectedTemplateId' => 'required_if:showBulkAssignModal,true|exists:feature_types,id',
            'bulkAssignScope' => 'required|in:all_vehicles,by_category',
            'bulkAssignCategoryId' => 'required_if:bulkAssignScope,by_category|exists:categories,id',
            'bulkAssignAction' => 'required|in:add_features,replace_features',
        ];
    }

    // ========================================
    // TEMPLATE MANAGEMENT METHODS
    // ========================================

    /**
     * Load predefined templates from database
     */
    public function loadPredefinedTemplates(): void
    {
        $this->predefinedTemplates = FeatureTemplate::predefined()->active()->get();

        Log::debug('VehicleFeatureManagement::loadPredefinedTemplates', [
            'count' => $this->predefinedTemplates->count(),
        ]);
    }

    /**
     * Load custom templates from DB
     */
    public function loadCustomTemplates(): void
    {
        $this->customTemplates = FeatureTemplate::custom()->active()->get();

        Log::debug('VehicleFeatureManagement::loadCustomTemplates', [
            'count' => $this->customTemplates->count(),
        ]);
    }

    /**
     * TEST METHOD - increment counter to verify Livewire reactivity
     */
    public function incrementTest(): void
    {
        $this->testCounter++;
        Log::info('TEST INCREMENT CALLED', ['new_value' => $this->testCounter]);
    }

    /**
     * Open template editor (new template)
     */
    public function openTemplateEditor(): void
    {
        $this->reset(['editingTemplateId', 'templateName', 'templateFeatures']);
        $this->showTemplateEditor = true;

        Log::info('VehicleFeatureManagement::openTemplateEditor CALLED', [
            'mode' => 'new',
            'showTemplateEditor' => $this->showTemplateEditor,
        ]);
    }

    /**
     * Edit existing template
     */
    public function editTemplate(int $templateId): void
    {
        Log::debug('VehicleFeatureManagement::editTemplate CALLED', [
            'template_id' => $templateId,
        ]);

        $this->editingTemplateId = $templateId;

        // Load template from DATABASE (not hardcoded!)
        $template = FeatureTemplate::find($templateId);

        if ($template) {
            $this->templateName = $template->name;
            $this->templateFeatures = $template->features; // JSON auto-decoded by model cast
        }

        $this->showTemplateEditor = true;
    }

    /**
     * Delete template
     */
    public function deleteTemplate(int $templateId): void
    {
        try {
            Log::info('VehicleFeatureManagement::deleteTemplate CALLED', [
                'template_id' => $templateId,
            ]);

            $template = FeatureTemplate::find($templateId);

            if (!$template) {
                $this->addError('general', 'Template not found.');
                return;
            }

            // Prevent deletion of predefined templates (check via attribute, not hardcoded ID!)
            if ($template->is_predefined) {
                $this->addError('general', 'Cannot delete predefined templates.');
                return;
            }

            DB::transaction(function () use ($template) {
                // TODO FUTURE: Check if template is used by products (usage_count)
                // For now, allow deletion without checking

                $template->delete();

                Log::info('Template deleted', ['id' => $template->id, 'name' => $template->name]);
            });

            $this->loadCustomTemplates();
            session()->flash('message', 'Template deleted successfully.');

            Log::info('VehicleFeatureManagement::deleteTemplate COMPLETED');

        } catch (\Exception $e) {
            Log::error('VehicleFeatureManagement::deleteTemplate FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error deleting template: ' . $e->getMessage());
        }
    }

    /**
     * Save template (create or update)
     */
    public function saveTemplate(): void
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateFeatures' => 'required|array|min:1',
        ]);

        try {
            Log::debug('VehicleFeatureManagement::saveTemplate CALLED', [
                'template_id' => $this->editingTemplateId,
                'template_name' => $this->templateName,
                'features_count' => count($this->templateFeatures),
            ]);

            DB::transaction(function () {
                if ($this->editingTemplateId) {
                    // UPDATE existing template
                    $template = FeatureTemplate::find($this->editingTemplateId);

                    if (!$template) {
                        throw new \Exception("Template not found: {$this->editingTemplateId}");
                    }

                    // Prevent editing predefined templates (unless explicitly allowed)
                    if ($template->is_predefined) {
                        throw new \Exception("Cannot edit predefined templates");
                    }

                    $template->update([
                        'name' => $this->templateName,
                        'features' => $this->templateFeatures,
                    ]);

                    Log::info('Template updated', ['id' => $template->id, 'name' => $template->name]);
                } else {
                    // CREATE new template
                    $template = FeatureTemplate::create([
                        'name' => $this->templateName,
                        'features' => $this->templateFeatures,
                        'is_predefined' => false, // User-created templates
                        'is_active' => true,
                    ]);

                    Log::info('Template created', ['id' => $template->id, 'name' => $template->name]);
                }
            });

            $this->loadCustomTemplates();
            $this->loadPredefinedTemplates(); // Refresh if needed
            $this->closeTemplateEditor();

            session()->flash('message', 'Template saved successfully.');

            Log::info('VehicleFeatureManagement::saveTemplate COMPLETED');

        } catch (\Exception $e) {
            Log::error('VehicleFeatureManagement::saveTemplate FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error saving template: ' . $e->getMessage());
        }
    }

    /**
     * Close template editor modal
     */
    public function closeTemplateEditor(): void
    {
        $this->showTemplateEditor = false;
        $this->reset(['editingTemplateId', 'templateName', 'templateFeatures']);
    }

    /**
     * Add feature row to template
     */
    public function addFeatureRow(): void
    {
        $this->templateFeatures[] = [
            'name' => '',
            'type' => 'text',
            'required' => false,
            'default' => '',
        ];
    }

    /**
     * Add feature from library to template
     */
    public function addFeatureToTemplate(string $featureName): void
    {
        // Find feature in library
        $feature = null;
        foreach ($this->featureLibrary as $group) {
            foreach ($group['features'] as $f) {
                if ($f['name'] === $featureName) {
                    $feature = $f;
                    break 2;
                }
            }
        }

        if ($feature) {
            $this->templateFeatures[] = [
                'name' => $feature['name'],
                'type' => $feature['type'],
                'required' => false,
                'default' => $feature['default'] ?? '',
            ];

            session()->flash('message', "Feature '{$featureName}' added to template.");
        }
    }

    /**
     * Remove feature from template
     */
    public function removeFeature(int $index): void
    {
        if (isset($this->templateFeatures[$index])) {
            unset($this->templateFeatures[$index]);
            $this->templateFeatures = array_values($this->templateFeatures); // Re-index
        }
    }

    // ========================================
    // FEATURE LIBRARY METHODS
    // ========================================

    /**
     * Load feature library from database (grouped by group)
     */
    public function loadFeatureLibrary(): void
    {
        // Use new scope from FeatureType model (FAZA 2.1)
        $grouped = FeatureType::active()
            ->orderBy('position')
            ->get()
            ->groupBy('group');

        // Transform to component format
        $this->featureLibrary = $grouped->map(function($features, $groupName) {
            return [
                'group' => $groupName,
                'features' => $features->map(fn($f) => [
                    'name' => $f->name,
                    'type' => $f->value_type,
                    'code' => $f->code,
                    'unit' => $f->unit,
                    'default' => '',
                ])->toArray(),
            ];
        })->values()->toArray();

        Log::debug('VehicleFeatureManagement::loadFeatureLibrary', [
            'groups_count' => count($this->featureLibrary),
            'total_features' => collect($this->featureLibrary)->sum(fn($g) => count($g['features'])),
        ]);
    }

    /**
     * Get filtered feature library (based on search)
     */
    public function getFilteredFeatureLibraryProperty(): array
    {
        if (empty($this->searchFeature)) {
            return $this->featureLibrary;
        }

        $search = strtolower($this->searchFeature);
        $filtered = [];

        foreach ($this->featureLibrary as $group) {
            $filteredFeatures = array_filter($group['features'], function ($feature) use ($search) {
                return str_contains(strtolower($feature['name']), $search);
            });

            if (!empty($filteredFeatures)) {
                $filtered[] = [
                    'group' => $group['group'],
                    'features' => array_values($filteredFeatures),
                ];
            }
        }

        return $filtered;
    }

    // ========================================
    // BULK ASSIGN METHODS
    // ========================================

    /**
     * Open bulk assign modal
     */
    public function openBulkAssignModal(): void
    {
        $this->reset(['selectedTemplateId', 'bulkAssignScope', 'bulkAssignCategoryId', 'bulkAssignAction']);
        $this->showBulkAssignModal = true;
        $this->calculateBulkAssignProductsCount();

        Log::info('VehicleFeatureManagement::openBulkAssignModal CALLED');
    }

    /**
     * Close bulk assign modal
     */
    public function closeBulkAssignModal(): void
    {
        $this->showBulkAssignModal = false;
    }

    /**
     * Calculate products count for bulk assign scope
     */
    public function calculateBulkAssignProductsCount(): void
    {
        $query = Product::query()->where('is_vehicle', true);

        if ($this->bulkAssignScope === 'by_category' && $this->bulkAssignCategoryId) {
            $query->where('category_id', $this->bulkAssignCategoryId);
        }

        $this->bulkAssignProductsCount = $query->count();
    }

    /**
     * Updated bulk assign scope (recalculate products count)
     */
    public function updatedBulkAssignScope(): void
    {
        $this->calculateBulkAssignProductsCount();
    }

    /**
     * Updated bulk assign category (recalculate products count)
     */
    public function updatedBulkAssignCategoryId(): void
    {
        $this->calculateBulkAssignProductsCount();
    }

    /**
     * Apply template to products (bulk assign)
     */
    public function bulkAssign(): void
    {
        $this->validate([
            'selectedTemplateId' => 'required',
            'bulkAssignScope' => 'required|in:all_vehicles,by_category',
            'bulkAssignAction' => 'required|in:add_features,replace_features',
        ]);

        try {
            Log::info('VehicleFeatureManagement::bulkAssign CALLED', [
                'template_id' => $this->selectedTemplateId,
                'scope' => $this->bulkAssignScope,
                'action' => $this->bulkAssignAction,
            ]);

            DB::transaction(function () {
                // Get products matching scope
                $query = Product::query()->where('is_vehicle', true);

                if ($this->bulkAssignScope === 'by_category' && $this->bulkAssignCategoryId) {
                    $query->where('category_id', $this->bulkAssignCategoryId);
                }

                $products = $query->get();

                // Get template features
                $templateFeatures = $this->getTemplateFeatures($this->selectedTemplateId);

                // Apply to each product
                $manager = $this->getFeatureManager();

                foreach ($products as $product) {
                    if ($this->bulkAssignAction === 'replace_features') {
                        // Replace all features
                        $manager->setFeatures($product, $templateFeatures);
                    } else {
                        // Add features (keep existing)
                        foreach ($templateFeatures as $featureData) {
                            $manager->addFeature($product, $featureData);
                        }
                    }
                }
            });

            $this->closeBulkAssignModal();
            session()->flash('message', "Template applied to {$this->bulkAssignProductsCount} products successfully.");

            Log::info('VehicleFeatureManagement::bulkAssign COMPLETED', [
                'products_count' => $this->bulkAssignProductsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('VehicleFeatureManagement::bulkAssign FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error applying template: ' . $e->getMessage());
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get template features for bulk assign
     */
    private function getTemplateFeatures(int $templateId): array
    {
        // Load from database
        $template = FeatureTemplate::find($templateId);

        if (!$template) {
            Log::warning('Template not found for bulk assign', ['template_id' => $templateId]);
            return [];
        }

        return $this->convertToFeatureManagerFormat($template->features);
    }

    /**
     * Convert template format to FeatureManager format
     */
    private function convertToFeatureManagerFormat(array $templateFeatures): array
    {
        $converted = [];

        foreach ($templateFeatures as $feature) {
            // Find or create FeatureType
            $featureType = FeatureType::firstOrCreate(
                ['code' => strtolower(str_replace(' ', '_', $feature['name']))],
                [
                    'name' => $feature['name'],
                    'value_type' => $feature['type'],
                    'is_active' => true,
                ]
            );

            $converted[] = [
                'feature_type_id' => $featureType->id,
                'feature_value_id' => null,
                'custom_value' => $feature['default'] ?? null,
            ];
        }

        return $converted;
    }

    // ========================================
    // RENDER
    // ========================================

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.features.vehicle-feature-management');
    }
}
