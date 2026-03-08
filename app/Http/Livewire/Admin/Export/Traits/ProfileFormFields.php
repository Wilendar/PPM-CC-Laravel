<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Services\Export\ExportProfileService;
use App\Models\ExportProfile;

/**
 * ProfileFormFields Trait
 *
 * Field selection logic for ExportProfileForm wizard (Step 2).
 * Manages available field groups, toggle/select/deselect operations.
 *
 * @package App\Http\Livewire\Admin\Export\Traits
 */
trait ProfileFormFields
{
    // Properties
    public array $selectedFields = [];
    public array $availableFieldGroups = [];

    /**
     * Load available field groups from ExportProfileService.
     */
    public function initFields(): void
    {
        /** @var ExportProfileService $service */
        $service = app(ExportProfileService::class);
        $this->availableFieldGroups = $service->getAvailableFields();
    }

    /**
     * Toggle a single field in selectedFields.
     */
    public function toggleField(string $fieldKey): void
    {
        if ($this->isFieldSelected($fieldKey)) {
            $this->selectedFields = array_values(
                array_filter($this->selectedFields, fn($f) => $f !== $fieldKey)
            );
        } else {
            $this->selectedFields[] = $fieldKey;
        }
    }

    /**
     * Select all fields in a specific group.
     */
    public function selectAllInGroup(string $groupKey): void
    {
        if (!isset($this->availableFieldGroups[$groupKey])) {
            return;
        }

        $groupFieldKeys = array_keys($this->availableFieldGroups[$groupKey]['fields'] ?? []);

        foreach ($groupFieldKeys as $fieldKey) {
            if (!$this->isFieldSelected($fieldKey)) {
                $this->selectedFields[] = $fieldKey;
            }
        }
    }

    /**
     * Deselect all fields in a specific group.
     */
    public function deselectAllInGroup(string $groupKey): void
    {
        if (!isset($this->availableFieldGroups[$groupKey])) {
            return;
        }

        $groupFieldKeys = array_keys($this->availableFieldGroups[$groupKey]['fields'] ?? []);

        $this->selectedFields = array_values(
            array_filter($this->selectedFields, fn($f) => !in_array($f, $groupFieldKeys, true))
        );
    }

    /**
     * Select all available fields across all groups.
     */
    public function selectAllFields(): void
    {
        $this->selectedFields = [];

        foreach ($this->availableFieldGroups as $group) {
            foreach (array_keys($group['fields'] ?? []) as $fieldKey) {
                $this->selectedFields[] = $fieldKey;
            }
        }
    }

    /**
     * Deselect all fields.
     */
    public function deselectAllFields(): void
    {
        $this->selectedFields = [];
    }

    /**
     * Check if a field is currently selected.
     */
    public function isFieldSelected(string $fieldKey): bool
    {
        return in_array($fieldKey, $this->selectedFields, true);
    }

    /**
     * Get total count of selected fields.
     */
    public function getSelectedCount(): int
    {
        return count($this->selectedFields);
    }

    /**
     * Get count of selected fields within a specific group.
     */
    public function getGroupSelectedCount(string $groupKey): int
    {
        if (!isset($this->availableFieldGroups[$groupKey])) {
            return 0;
        }

        $groupFieldKeys = array_keys($this->availableFieldGroups[$groupKey]['fields'] ?? []);

        return count(array_intersect($this->selectedFields, $groupFieldKeys));
    }

    /**
     * Load field selection from existing profile.
     */
    public function loadFieldsFromProfile(ExportProfile $profile): void
    {
        $fieldConfig = $profile->field_config ?? [];

        if (empty($fieldConfig)) {
            $this->selectedFields = [];
            return;
        }

        // Support both keyed {'sku' => true, ...} and flat ['sku', ...]
        $this->selectedFields = array_is_list($fieldConfig)
            ? array_values($fieldConfig)
            : array_keys(array_filter($fieldConfig));
    }
}
