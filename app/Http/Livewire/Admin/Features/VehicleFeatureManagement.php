<?php

namespace App\Http\Livewire\Admin\Features;

use App\Jobs\Features\BulkAssignFeaturesJob;
use App\Models\FeatureGroup;
use App\Models\FeatureTemplate;
use App\Models\FeatureType;
use App\Models\JobProgress;
use App\Models\Product;
use App\Services\JobProgressService;
use App\Services\Product\FeatureManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    // TAB NAVIGATION (ETAP_07e Phase 2)
    // ========================================

    /**
     * Active tab: 'browser' | 'library' | 'templates'
     * Default: 'browser' (Przeglądarka Cech - najczęściej używana)
     */
    public string $activeTab = 'browser';

    /**
     * Expanded group IDs for feature library tree view
     */
    public array $expandedGroups = [];

    /**
     * Selected group ID for 2-column library view
     */
    public ?int $selectedGroupId = null;

    /**
     * Search query for feature types in library
     */
    public string $searchQuery = '';

    /**
     * Filter for templates tab
     */
    public string $filter = 'all';

    /**
     * Set active tab
     */
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['library', 'templates', 'browser'])) {
            $this->activeTab = $tab;
        }
    }

    // ========================================
    // FEATURE LIBRARY TREE VIEW METHODS
    // ========================================

    /**
     * Toggle group expansion state
     */
    public function toggleGroup(int $groupId): void
    {
        if (in_array($groupId, $this->expandedGroups)) {
            $this->expandedGroups = array_values(array_filter(
                $this->expandedGroups,
                fn($id) => $id !== $groupId
            ));
        } else {
            $this->expandedGroups[] = $groupId;
        }
    }

    /**
     * Check if group is expanded
     */
    public function isGroupExpanded(int $groupId): bool
    {
        return in_array($groupId, $this->expandedGroups);
    }

    /**
     * Expand all groups
     */
    public function expandAll(): void
    {
        $this->expandedGroups = collect($this->featureLibrary)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Collapse all groups
     */
    public function collapseAll(): void
    {
        $this->expandedGroups = [];
    }

    /**
     * Select group for 2-column library view
     */
    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
    }

    /**
     * Get selected group data (computed property)
     */
    public function getSelectedGroupProperty(): ?array
    {
        if (!$this->selectedGroupId) {
            return null;
        }

        $groups = $this->getGroupsProperty();
        return $groups->firstWhere('id', $this->selectedGroupId);
    }

    /**
     * Get feature types for selected group (computed property)
     */
    public function getFeatureTypesProperty(): Collection
    {
        if (!$this->selectedGroupId) {
            return collect([]);
        }

        $query = FeatureType::where('feature_group_id', $this->selectedGroupId)
            ->where('is_active', true)
            ->orderBy('position');

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('code', 'like', '%' . $this->searchQuery . '%');
            });
        }

        return $query->get()->map(fn($f) => [
            'id' => $f->id,
            'name' => $f->name,
            'code' => $f->code,
            'value_type' => $f->value_type,
            'unit' => $f->unit,
            'products_count' => $f->productFeatures()->count(),
            'conditional' => $f->conditional_group,
        ]);
    }

    // ========================================
    // TEMPLATE MANAGEMENT
    // ========================================

    /**
     * Show/hide template editor modal
     * Alias: showTemplateModal (used in blade templates)
     */
    public bool $showTemplateEditor = false;
    public bool $showTemplateModal = false; // Alias for blade

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
    // FEATURE TYPE CRUD
    // ========================================

    /**
     * Show/hide feature type editor modal
     * Alias: showFeatureTypeModal (used in blade templates)
     */
    public bool $showFeatureTypeEditor = false;
    public bool $showFeatureTypeModal = false; // Alias for blade

    /**
     * Currently editing feature type ID (null = new)
     */
    public ?int $editingFeatureTypeId = null;

    /**
     * Feature type form fields
     */
    public string $featureTypeName = '';
    public string $featureTypeCode = '';
    public string $featureTypeValueType = 'text';
    public ?string $featureTypeUnit = null;
    public ?int $featureTypeGroupId = null;
    public ?string $featureTypePlaceholder = null;
    public ?string $featureTypeConditional = null;

    // ========================================
    // FEATURE GROUP CRUD
    // ========================================

    /**
     * Show/hide feature group editor modal
     * Alias: showFeatureGroupModal (used in blade templates)
     */
    public bool $showFeatureGroupEditor = false;
    public bool $showFeatureGroupModal = false; // Alias for blade

    /**
     * Currently editing feature group ID (null = new)
     */
    public ?int $editingFeatureGroupId = null;

    /**
     * Feature group form fields
     */
    public string $featureGroupName = '';
    public string $featureGroupCode = '';
    public ?string $featureGroupNamePl = null;
    public ?string $featureGroupIcon = null;
    public ?string $featureGroupColor = null;
    public ?string $featureGroupVehicleFilter = null;
    public int $featureGroupSortOrder = 0;

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

    /**
     * Active job progress ID for bulk assign tracking
     */
    public ?int $activeJobProgressId = null;

    /**
     * Active job progress data for UI
     */
    public array $activeJobProgress = [];

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
     * Open template editor (new template)
     */
    public function openTemplateEditor(): void
    {
        $this->reset(['editingTemplateId', 'templateName', 'templateFeatures']);
        $this->showTemplateEditor = true;
        $this->showTemplateModal = true; // Alias sync

        Log::info('VehicleFeatureManagement::openTemplateEditor CALLED', [
            'mode' => 'new',
            'showTemplateEditor' => $this->showTemplateEditor,
        ]);
    }

    /**
     * Alias for openTemplateEditor (used by templates tab)
     */
    public function openTemplateModal(): void
    {
        $this->openTemplateEditor();
    }

    /**
     * Close template modal (alias for closeTemplateEditor)
     */
    public function closeTemplateModal(): void
    {
        $this->closeTemplateEditor();
    }

    /**
     * Save template (alias for saveTemplate consistency)
     * Already exists as saveTemplate()
     */

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
        $this->showTemplateModal = true; // Alias sync
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
        $this->showTemplateModal = false; // Alias sync
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
     * Load feature library from database (grouped by FeatureGroup)
     *
     * ETAP_07e FAZA 2 - Uses FeatureGroup model with icons and colors
     */
    public function loadFeatureLibrary(): void
    {
        // Get all active groups with their feature types
        $groups = FeatureGroup::getGroupsWithFeatures();

        // Transform to component format with icon and color support
        $this->featureLibrary = $groups->map(function(FeatureGroup $group) {
            return [
                'id' => $group->id,
                'code' => $group->code,
                'group' => $group->getDisplayName(),
                'icon' => $group->icon,
                'color' => $group->color,
                'colorClasses' => $group->getColorClasses(),
                'isConditional' => $group->isConditional(),
                'vehicleTypeFilter' => $group->vehicle_type_filter,
                'features' => $group->activeFeatureTypes->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'code' => $f->code,
                    'type' => $f->value_type,
                    'unit' => $f->unit,
                    'placeholder' => $f->input_placeholder,
                    'conditionalGroup' => $f->conditional_group,
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
    // FEATURE TYPE CRUD METHODS
    // ========================================

    /**
     * Open feature type editor (new)
     */
    public function openFeatureTypeEditor(?int $groupId = null): void
    {
        $this->resetFeatureTypeForm();
        $this->featureTypeGroupId = $groupId;
        $this->showFeatureTypeEditor = true;
        $this->showFeatureTypeModal = true; // Alias sync
    }

    /**
     * Alias for openFeatureTypeEditor (used by library tab)
     */
    public function openFeatureTypeModal(?int $groupId = null): void
    {
        $this->openFeatureTypeEditor($groupId);
    }

    /**
     * Close feature type modal (alias)
     */
    public function closeFeatureTypeModal(): void
    {
        $this->closeFeatureTypeEditor();
    }

    /**
     * Edit existing feature type
     */
    public function editFeatureType(int $featureTypeId): void
    {
        $featureType = FeatureType::find($featureTypeId);

        if (!$featureType) {
            $this->addError('general', 'Cecha nie zostala znaleziona.');
            return;
        }

        $this->editingFeatureTypeId = $featureType->id;
        $this->featureTypeName = $featureType->name;
        $this->featureTypeCode = $featureType->code;
        $this->featureTypeValueType = $featureType->value_type;
        $this->featureTypeUnit = $featureType->unit;
        $this->featureTypeGroupId = $featureType->feature_group_id;
        $this->featureTypePlaceholder = $featureType->input_placeholder;
        $this->featureTypeConditional = $featureType->conditional_group;

        $this->showFeatureTypeEditor = true;
        $this->showFeatureTypeModal = true; // Alias sync
    }

    /**
     * Save feature type (create or update)
     */
    public function saveFeatureType(): void
    {
        $this->validate([
            'featureTypeName' => 'required|string|max:255',
            'featureTypeCode' => 'required|string|max:100',
            'featureTypeValueType' => 'required|in:text,number,bool,select',
            'featureTypeGroupId' => 'nullable|exists:feature_groups,id',
        ]);

        try {
            DB::transaction(function () {
                if ($this->editingFeatureTypeId) {
                    // UPDATE
                    $featureType = FeatureType::findOrFail($this->editingFeatureTypeId);
                    $featureType->update([
                        'name' => $this->featureTypeName,
                        'code' => $this->featureTypeCode,
                        'value_type' => $this->featureTypeValueType,
                        'unit' => $this->featureTypeUnit,
                        'feature_group_id' => $this->featureTypeGroupId,
                        'input_placeholder' => $this->featureTypePlaceholder,
                        'conditional_group' => $this->featureTypeConditional,
                    ]);
                    Log::info('FeatureType updated', ['id' => $featureType->id]);
                } else {
                    // CREATE
                    $maxPosition = FeatureType::where('feature_group_id', $this->featureTypeGroupId)->max('position') ?? 0;
                    FeatureType::create([
                        'name' => $this->featureTypeName,
                        'code' => $this->featureTypeCode,
                        'value_type' => $this->featureTypeValueType,
                        'unit' => $this->featureTypeUnit,
                        'feature_group_id' => $this->featureTypeGroupId,
                        'input_placeholder' => $this->featureTypePlaceholder,
                        'conditional_group' => $this->featureTypeConditional,
                        'is_active' => true,
                        'position' => $maxPosition + 1,
                    ]);
                    Log::info('FeatureType created', ['name' => $this->featureTypeName]);
                }
            });

            $this->loadFeatureLibrary();
            $this->closeFeatureTypeEditor();
            session()->flash('message', 'Cecha zapisana pomyslnie.');

        } catch (\Exception $e) {
            Log::error('FeatureType save failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Delete feature type
     */
    public function deleteFeatureType(int $featureTypeId): void
    {
        try {
            $featureType = FeatureType::findOrFail($featureTypeId);

            // Check if feature is used by products
            $usageCount = $featureType->productFeatures()->count();
            if ($usageCount > 0) {
                $this->addError('general', "Nie mozna usunac - cecha uzywana przez {$usageCount} produktow.");
                return;
            }

            $featureType->delete();
            $this->loadFeatureLibrary();
            session()->flash('message', 'Cecha usunieta.');

        } catch (\Exception $e) {
            Log::error('FeatureType delete failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Close feature type editor
     */
    public function closeFeatureTypeEditor(): void
    {
        $this->showFeatureTypeEditor = false;
        $this->showFeatureTypeModal = false; // Alias sync
        $this->resetFeatureTypeForm();
    }

    /**
     * Reset feature type form
     */
    private function resetFeatureTypeForm(): void
    {
        $this->editingFeatureTypeId = null;
        $this->featureTypeName = '';
        $this->featureTypeCode = '';
        $this->featureTypeValueType = 'text';
        $this->featureTypeUnit = null;
        $this->featureTypeGroupId = null;
        $this->featureTypePlaceholder = null;
        $this->featureTypeConditional = null;
    }

    // ========================================
    // FEATURE GROUP CRUD METHODS
    // ========================================

    /**
     * Open feature group editor (new)
     */
    public function openFeatureGroupEditor(): void
    {
        $this->resetFeatureGroupForm();
        $this->showFeatureGroupEditor = true;
        $this->showFeatureGroupModal = true; // Alias sync
    }

    /**
     * Alias for openFeatureGroupEditor (used by library tab)
     */
    public function openFeatureGroupModal(): void
    {
        $this->openFeatureGroupEditor();
    }

    /**
     * Close feature group modal (alias)
     */
    public function closeFeatureGroupModal(): void
    {
        $this->closeFeatureGroupEditor();
    }

    /**
     * Edit existing feature group
     */
    public function editFeatureGroup(int $groupId): void
    {
        $group = FeatureGroup::find($groupId);

        if (!$group) {
            $this->addError('general', 'Grupa nie zostala znaleziona.');
            return;
        }

        $this->editingFeatureGroupId = $group->id;
        $this->featureGroupName = $group->name;
        $this->featureGroupCode = $group->code;
        $this->featureGroupNamePl = $group->name_pl;
        $this->featureGroupIcon = $group->icon;
        $this->featureGroupColor = $group->color;
        $this->featureGroupVehicleFilter = $group->vehicle_type_filter;
        $this->featureGroupSortOrder = $group->sort_order;

        $this->showFeatureGroupEditor = true;
        $this->showFeatureGroupModal = true; // Alias sync
    }

    /**
     * Save feature group (create or update)
     */
    public function saveFeatureGroup(): void
    {
        $this->validate([
            'featureGroupName' => 'required|string|max:255',
            'featureGroupCode' => 'required|string|max:100',
            'featureGroupSortOrder' => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () {
                if ($this->editingFeatureGroupId) {
                    // UPDATE
                    $group = FeatureGroup::findOrFail($this->editingFeatureGroupId);
                    $group->update([
                        'name' => $this->featureGroupName,
                        'code' => $this->featureGroupCode,
                        'name_pl' => $this->featureGroupNamePl,
                        'icon' => $this->featureGroupIcon,
                        'color' => $this->featureGroupColor,
                        'vehicle_type_filter' => $this->featureGroupVehicleFilter,
                        'sort_order' => $this->featureGroupSortOrder,
                    ]);
                    Log::info('FeatureGroup updated', ['id' => $group->id]);
                } else {
                    // CREATE
                    FeatureGroup::create([
                        'name' => $this->featureGroupName,
                        'code' => $this->featureGroupCode,
                        'name_pl' => $this->featureGroupNamePl,
                        'icon' => $this->featureGroupIcon,
                        'color' => $this->featureGroupColor,
                        'vehicle_type_filter' => $this->featureGroupVehicleFilter,
                        'sort_order' => $this->featureGroupSortOrder,
                        'is_active' => true,
                        'is_collapsible' => true,
                    ]);
                    Log::info('FeatureGroup created', ['name' => $this->featureGroupName]);
                }
            });

            $this->loadFeatureLibrary();
            $this->closeFeatureGroupEditor();
            session()->flash('message', 'Grupa zapisana pomyslnie.');

        } catch (\Exception $e) {
            Log::error('FeatureGroup save failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Delete feature group
     */
    public function deleteFeatureGroup(int $groupId): void
    {
        try {
            $group = FeatureGroup::findOrFail($groupId);

            // Check if group has features
            $featureCount = $group->featureTypes()->count();
            if ($featureCount > 0) {
                $this->addError('general', "Nie mozna usunac - grupa zawiera {$featureCount} cech.");
                return;
            }

            $group->delete();
            $this->loadFeatureLibrary();
            session()->flash('message', 'Grupa usunieta.');

        } catch (\Exception $e) {
            Log::error('FeatureGroup delete failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Close feature group editor
     */
    public function closeFeatureGroupEditor(): void
    {
        $this->showFeatureGroupEditor = false;
        $this->showFeatureGroupModal = false; // Alias sync
        $this->resetFeatureGroupForm();
    }

    /**
     * Reset feature group form
     */
    private function resetFeatureGroupForm(): void
    {
        $this->editingFeatureGroupId = null;
        $this->featureGroupName = '';
        $this->featureGroupCode = '';
        $this->featureGroupNamePl = null;
        $this->featureGroupIcon = null;
        $this->featureGroupColor = null;
        $this->featureGroupVehicleFilter = null;
        $this->featureGroupSortOrder = 0;
    }

    /**
     * Get available feature groups for dropdown
     */
    public function getFeatureGroupsProperty(): Collection
    {
        return FeatureGroup::active()->ordered()->get();
    }

    /**
     * Get all groups with features for library tree view
     * Used by feature-library-tab.blade.php
     */
    public function getGroupsProperty(): Collection
    {
        return FeatureGroup::with('activeFeatureTypes')
            ->active()
            ->ordered()
            ->get()
            ->map(function (FeatureGroup $group) {
                return [
                    'id' => $group->id,
                    'name' => $group->getDisplayName(),
                    'code' => $group->code,
                    'icon' => $group->icon,
                    'color' => $group->color,
                    'colorClasses' => $group->getColorClasses(),
                    'vehicle_filter' => $group->vehicle_type_filter,
                    'features_count' => $group->activeFeatureTypes->count(),
                    'used_features_count' => $group->activeFeatureTypes->filter(fn($f) => $f->productFeatures()->count() > 0)->count(),
                    'features' => $group->activeFeatureTypes->map(fn($f) => [
                        'id' => $f->id,
                        'name' => $f->name,
                        'code' => $f->code,
                        'value_type' => $f->value_type,
                        'unit' => $f->unit,
                        'products_count' => $f->productFeatures()->count(),
                        'conditional' => $f->conditional_group,
                    ])->toArray(),
                ];
            });
    }

    /**
     * Alias for allGroups (used in modals)
     */
    public function getAllGroupsProperty(): Collection
    {
        return $this->getFeatureGroupsProperty();
    }

    /**
     * Get all templates for templates tab
     * Combines predefined and custom templates
     */
    public function getTemplatesProperty(): Collection
    {
        return FeatureTemplate::active()
            ->orderBy('is_predefined', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function (FeatureTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'icon' => $template->icon ?? '📋',
                    'category' => $template->category ?? 'Ogolne',
                    'is_predefined' => $template->is_predefined,
                    'features' => $template->features ?? [],
                    'features_count' => count($template->features ?? []),
                    'usage_count' => $template->usage_count ?? 0,
                ];
            });
    }

    /**
     * Get selected template data for preview
     */
    public function getSelectedTemplateProperty(): ?array
    {
        if (!$this->selectedTemplateId) {
            return null;
        }

        $template = FeatureTemplate::find($this->selectedTemplateId);
        if (!$template) {
            return null;
        }

        return [
            'id' => $template->id,
            'name' => $template->name,
            'icon' => $template->icon ?? '📋',
            'category' => $template->category ?? 'Ogolne',
            'is_predefined' => $template->is_predefined,
            'features' => $template->features ?? [],
            'features_count' => count($template->features ?? []),
            'usage_count' => $template->usage_count ?? 0,
        ];
    }

    /**
     * Select template for preview
     */
    public function selectTemplate(int $templateId): void
    {
        $this->selectedTemplateId = $templateId;
    }

    /**
     * Duplicate template (create copy as custom)
     */
    public function duplicateTemplate(int $templateId): void
    {
        try {
            $original = FeatureTemplate::findOrFail($templateId);

            $copy = FeatureTemplate::create([
                'name' => $original->name . ' (kopia)',
                'icon' => $original->icon,
                'category' => $original->category,
                'features' => $original->features,
                'is_predefined' => false,
                'is_active' => true,
            ]);

            session()->flash('message', 'Szablon zduplikowany: ' . $copy->name);

        } catch (\Exception $e) {
            Log::error('Template duplication failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad duplikowania szablonu: ' . $e->getMessage());
        }
    }

    // ========================================
    // BULK ASSIGN METHODS
    // ========================================

    /**
     * Open bulk assign modal
     */
    public function openBulkAssignModal(?int $templateId = null): void
    {
        $this->reset(['bulkAssignScope', 'bulkAssignCategoryId', 'bulkAssignAction']);
        if ($templateId) {
            $this->selectedTemplateId = $templateId;
        }
        $this->showBulkAssignModal = true;
        $this->calculateBulkAssignProductsCount();

        Log::info('VehicleFeatureManagement::openBulkAssignModal CALLED', [
            'template_id' => $templateId,
        ]);
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
     *
     * NOTE: Removed is_vehicle filter as column doesn't exist.
     * Features can be assigned to any product.
     */
    public function calculateBulkAssignProductsCount(): void
    {
        $query = Product::query();

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
     * Apply template to products (bulk assign) via background job
     *
     * ETAP_07e FAZA 2: Dispatches BulkAssignFeaturesJob with JobProgress tracking
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
                'products_count' => $this->bulkAssignProductsCount,
            ]);

            // Generate job ID for progress tracking
            $jobId = Str::uuid()->toString();

            // Create pending progress BEFORE dispatching job
            $progressService = app(JobProgressService::class);
            $progressId = $progressService->createJobProgress(
                $jobId,
                null, // No shop context for features
                'bulk_assign_features',
                $this->bulkAssignProductsCount
            );

            // Store progress ID for UI tracking
            $this->activeJobProgressId = $progressId;

            // Dispatch job to queue
            BulkAssignFeaturesJob::dispatch(
                $this->selectedTemplateId,
                $this->bulkAssignScope,
                $this->bulkAssignCategoryId,
                $this->bulkAssignAction,
                $jobId,
                auth()->id()
            );

            // Close modal and show progress
            $this->closeBulkAssignModal();
            session()->flash('message', "Rozpoczeto przypisywanie szablonu do {$this->bulkAssignProductsCount} produktow...");

            Log::info('VehicleFeatureManagement::bulkAssign DISPATCHED', [
                'job_id' => $jobId,
                'progress_id' => $progressId,
                'products_count' => $this->bulkAssignProductsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('VehicleFeatureManagement::bulkAssign FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', 'Error dispatching bulk assign: ' . $e->getMessage());
        }
    }

    /**
     * Refresh job progress (called by wire:poll)
     */
    public function refreshJobProgress(): void
    {
        if (!$this->activeJobProgressId) {
            return;
        }

        $progress = JobProgress::find($this->activeJobProgressId);

        if (!$progress) {
            $this->activeJobProgressId = null;
            $this->activeJobProgress = [];
            return;
        }

        $this->activeJobProgress = [
            'status' => $progress->status,
            'current' => $progress->current_count,
            'total' => $progress->total_count,
            'percentage' => $progress->progress_percentage,
            'errors' => $progress->error_count,
            'message' => $this->formatProgressMessage($progress),
        ];

        // Clear progress ID when completed or failed
        if (in_array($progress->status, ['completed', 'failed'])) {
            if ($progress->status === 'completed') {
                session()->flash('message', "Ukonczone! Szablon zastosowany do {$progress->current_count} produktow.");
            } elseif ($progress->status === 'failed') {
                $this->addError('general', 'Blad podczas przypisywania szablonu.');
            }

            // Keep progress visible for 5 seconds after completion
            // User can dismiss manually
        }
    }

    /**
     * Dismiss progress bar (manual close)
     */
    public function dismissProgress(): void
    {
        $this->activeJobProgressId = null;
        $this->activeJobProgress = [];
    }

    /**
     * Format progress message for UI
     */
    private function formatProgressMessage(JobProgress $progress): string
    {
        switch ($progress->status) {
            case 'running':
                return "Przetwarzanie... {$progress->current_count}/{$progress->total_count}";
            case 'completed':
                return "Ukonczone! {$progress->current_count}/{$progress->total_count}";
            case 'failed':
                return 'Wystapil blad podczas przypisywania szablonu';
            case 'pending':
                return 'Oczekiwanie na uruchomienie...';
            default:
                return 'Status nieznany';
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
