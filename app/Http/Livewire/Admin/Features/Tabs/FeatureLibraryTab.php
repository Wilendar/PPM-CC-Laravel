<?php

namespace App\Http\Livewire\Admin\Features\Tabs;

use App\Models\FeatureGroup;
use App\Models\FeatureType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * FeatureLibraryTab - 2-Column Feature Library Management
 *
 * Left column: Feature Groups
 * Right column: Feature Types of selected group
 *
 * ETAP_07e Features Panel Redesign
 */
class FeatureLibraryTab extends Component
{
    // ========================================
    // STATE PROPERTIES
    // ========================================

    /**
     * Currently selected group ID
     */
    public ?int $selectedGroupId = null;

    /**
     * Search query for features
     */
    public string $searchQuery = '';

    // ========================================
    // FEATURE TYPE CRUD
    // ========================================

    public bool $showFeatureTypeModal = false;
    public ?int $editingFeatureTypeId = null;
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

    public bool $showFeatureGroupModal = false;
    public ?int $editingFeatureGroupId = null;
    public string $featureGroupName = '';
    public string $featureGroupCode = '';
    public ?string $featureGroupNamePl = null;
    public ?string $featureGroupIcon = null;
    public ?string $featureGroupColor = null;
    public ?string $featureGroupVehicleFilter = null;
    public int $featureGroupSortOrder = 0;

    // ========================================
    // COMPUTED PROPERTIES
    // ========================================

    /**
     * Get all feature groups with counts
     */
    #[Computed]
    public function groups(): Collection
    {
        return FeatureGroup::query()
            ->withCount('featureTypes')
            ->withCount(['featureTypes as used_features_count' => function ($query) {
                $query->whereHas('productFeatures');
            }])
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
                    'features_count' => $group->feature_types_count,
                    'used_features_count' => $group->used_features_count,
                    'vehicle_filter' => $group->vehicle_type_filter,
                ];
            });
    }

    /**
     * Get feature types for selected group
     */
    #[Computed]
    public function featureTypes(): Collection
    {
        if (!$this->selectedGroupId) {
            return collect();
        }

        $query = FeatureType::query()
            ->where('feature_group_id', $this->selectedGroupId)
            ->withCount('productFeatures')
            ->orderBy('position')
            ->orderBy('name');

        // Apply search filter
        if (!empty($this->searchQuery)) {
            $search = '%' . $this->searchQuery . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        return $query->get()->map(function (FeatureType $type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'value_type' => $type->value_type,
                'unit' => $type->unit,
                'products_count' => $type->product_features_count,
                'placeholder' => $type->input_placeholder,
                'conditional' => $type->conditional_group,
                'is_active' => $type->is_active,
            ];
        });
    }

    /**
     * Get currently selected group data
     */
    #[Computed]
    public function selectedGroup(): ?array
    {
        if (!$this->selectedGroupId) {
            return null;
        }

        return $this->groups->firstWhere('id', $this->selectedGroupId);
    }

    // ========================================
    // GROUP SELECTION
    // ========================================

    /**
     * Select a feature group
     */
    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->searchQuery = '';
    }

    // ========================================
    // FEATURE TYPE CRUD METHODS
    // ========================================

    /**
     * Open modal to create new feature type
     */
    public function openFeatureTypeModal(?int $groupId = null): void
    {
        $this->resetFeatureTypeForm();
        $this->featureTypeGroupId = $groupId ?? $this->selectedGroupId;
        $this->showFeatureTypeModal = true;
    }

    /**
     * Open modal to edit existing feature type
     */
    public function editFeatureType(int $featureTypeId): void
    {
        $featureType = FeatureType::find($featureTypeId);

        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Cecha nie zostala znaleziona.');
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

        $this->showFeatureTypeModal = true;
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
                    $maxPosition = FeatureType::where('feature_group_id', $this->featureTypeGroupId)
                        ->max('position') ?? 0;

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

            $this->closeFeatureTypeModal();
            $this->dispatch('notify', type: 'success', message: 'Cecha zapisana pomyslnie.');

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

            $usageCount = $featureType->productFeatures()->count();
            if ($usageCount > 0) {
                $this->dispatch('notify', type: 'error', message: "Nie mozna usunac - cecha uzywana przez {$usageCount} produktow.");
                return;
            }

            $featureType->delete();
            $this->dispatch('notify', type: 'success', message: 'Cecha usunieta.');

        } catch (\Exception $e) {
            Log::error('FeatureType delete failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Close feature type modal
     */
    public function closeFeatureTypeModal(): void
    {
        $this->showFeatureTypeModal = false;
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
     * Open modal to create new feature group
     */
    public function openFeatureGroupModal(): void
    {
        $this->resetFeatureGroupForm();
        $this->showFeatureGroupModal = true;
    }

    /**
     * Open modal to edit existing feature group
     */
    public function editFeatureGroup(int $groupId): void
    {
        $group = FeatureGroup::find($groupId);

        if (!$group) {
            $this->dispatch('notify', type: 'error', message: 'Grupa nie zostala znaleziona.');
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

        $this->showFeatureGroupModal = true;
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

            $this->closeFeatureGroupModal();
            $this->dispatch('notify', type: 'success', message: 'Grupa zapisana pomyslnie.');

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

            $featureCount = $group->featureTypes()->count();
            if ($featureCount > 0) {
                $this->dispatch('notify', type: 'error', message: "Nie mozna usunac - grupa zawiera {$featureCount} cech.");
                return;
            }

            $group->delete();

            // Clear selection if deleted group was selected
            if ($this->selectedGroupId === $groupId) {
                $this->selectedGroupId = null;
            }

            $this->dispatch('notify', type: 'success', message: 'Grupa usunieta.');

        } catch (\Exception $e) {
            Log::error('FeatureGroup delete failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Close feature group modal
     */
    public function closeFeatureGroupModal(): void
    {
        $this->showFeatureGroupModal = false;
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
     * Get all feature groups for dropdown
     */
    #[Computed]
    public function allGroups(): Collection
    {
        return FeatureGroup::active()->ordered()->get();
    }

    // ========================================
    // RENDER
    // ========================================

    public function render()
    {
        return view('livewire.admin.features.tabs.feature-library-tab');
    }
}
