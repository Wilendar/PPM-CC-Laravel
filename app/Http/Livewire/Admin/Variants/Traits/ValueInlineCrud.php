<?php

namespace App\Http\Livewire\Admin\Variants\Traits;

use App\Models\AttributeValue;
use Illuminate\Support\Str;

/**
 * ValueInlineCrud - Inline CRUD for Attribute Values
 *
 * Used by VariantPanelContainer for inline value management.
 * Supports color picker for 'color' display type.
 *
 * @package App\Http\Livewire\Admin\Variants\Traits
 * @since Variant Panel Inline CRUD 2025-12
 */
trait ValueInlineCrud
{
    // Inline form state for values
    public bool $showValueCreateForm = false;
    public ?int $editingValueId = null;
    public array $valueForm = [
        'label' => '',
        'code' => '',
        'color_hex' => '#000000',
        'auto_prefix' => '',
        'auto_prefix_enabled' => false,
        'auto_suffix' => '',
        'auto_suffix_enabled' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | CREATE VALUE
    |--------------------------------------------------------------------------
    */

    public function showCreateValueForm(): void
    {
        $this->resetValueForm();
        $this->showValueCreateForm = true;
        $this->editingValueId = null;
    }

    public function cancelValueCreate(): void
    {
        $this->showValueCreateForm = false;
        $this->resetValueForm();
        $this->resetErrorBag('valueForm.*');
    }

    public function createValue(): void
    {
        if (!$this->selectedTypeId) return;

        $rules = [
            'valueForm.label' => 'required|string|max:100|min:1',
            'valueForm.code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
            ],
        ];

        // Add color validation for color type
        if ($this->selectedType?->display_type === 'color') {
            $rules['valueForm.color_hex'] = 'required|regex:/^#[0-9A-Fa-f]{6}$/';
        }

        $this->validate($rules, [
            'valueForm.label.required' => 'Etykieta jest wymagana',
            'valueForm.code.required' => 'Kod jest wymagany',
            'valueForm.code.regex' => 'Kod: male litery, cyfry, -, _',
            'valueForm.color_hex.required' => 'Kolor jest wymagany',
            'valueForm.color_hex.regex' => 'Format: #RRGGBB',
        ]);

        // Check uniqueness within type
        $exists = AttributeValue::where('attribute_type_id', $this->selectedTypeId)
            ->where('code', $this->valueForm['code'])
            ->exists();

        if ($exists) {
            $this->addError('valueForm.code', 'Ten kod juz istnieje w tej grupie');
            return;
        }

        try {
            $maxPosition = AttributeValue::where('attribute_type_id', $this->selectedTypeId)
                ->max('position') ?? 0;

            AttributeValue::create([
                'attribute_type_id' => $this->selectedTypeId,
                'label' => $this->valueForm['label'],
                'code' => $this->valueForm['code'],
                'color_hex' => $this->selectedType?->display_type === 'color'
                    ? $this->valueForm['color_hex']
                    : null,
                'auto_prefix' => $this->valueForm['auto_prefix'] ?: null,
                'auto_prefix_enabled' => (bool) $this->valueForm['auto_prefix_enabled'],
                'auto_suffix' => $this->valueForm['auto_suffix'] ?: null,
                'auto_suffix_enabled' => (bool) $this->valueForm['auto_suffix_enabled'],
                'position' => $maxPosition + 1,
                'is_active' => true,
            ]);

            $this->cancelValueCreate();
            unset($this->valuesWithCounts, $this->attributeTypes);
            $this->dispatch('attribute-values-updated');
            session()->flash('message', 'Wartosc utworzona');

        } catch (\Exception $e) {
            $this->addError('valueForm.general', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT VALUE
    |--------------------------------------------------------------------------
    */

    public function startValueEdit(int $valueId): void
    {
        $value = AttributeValue::find($valueId);
        if (!$value) return;

        $this->editingValueId = $valueId;
        $this->valueForm = [
            'label' => $value->label,
            'code' => $value->code,
            'color_hex' => $value->color_hex ?? '#000000',
            'auto_prefix' => $value->auto_prefix ?? '',
            'auto_prefix_enabled' => (bool) $value->auto_prefix_enabled,
            'auto_suffix' => $value->auto_suffix ?? '',
            'auto_suffix_enabled' => (bool) $value->auto_suffix_enabled,
        ];
        $this->showValueCreateForm = false;
    }

    public function cancelValueEdit(): void
    {
        $this->editingValueId = null;
        $this->resetValueForm();
        $this->resetErrorBag('valueForm.*');
    }

    public function saveValueEdit(): void
    {
        $value = AttributeValue::find($this->editingValueId);
        if (!$value) return;

        $rules = [
            'valueForm.label' => 'required|string|max:100|min:1',
            'valueForm.code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
            ],
        ];

        if ($this->selectedType?->display_type === 'color') {
            $rules['valueForm.color_hex'] = 'required|regex:/^#[0-9A-Fa-f]{6}$/';
        }

        $this->validate($rules, [
            'valueForm.label.required' => 'Etykieta jest wymagana',
            'valueForm.code.required' => 'Kod jest wymagany',
            'valueForm.code.regex' => 'Kod: male litery, cyfry, -, _',
            'valueForm.color_hex.required' => 'Kolor jest wymagany',
            'valueForm.color_hex.regex' => 'Format: #RRGGBB',
        ]);

        // Check uniqueness (excluding current)
        $exists = AttributeValue::where('attribute_type_id', $value->attribute_type_id)
            ->where('code', $this->valueForm['code'])
            ->where('id', '!=', $this->editingValueId)
            ->exists();

        if ($exists) {
            $this->addError('valueForm.code', 'Ten kod juz istnieje w tej grupie');
            return;
        }

        try {
            $value->update([
                'label' => $this->valueForm['label'],
                'code' => $this->valueForm['code'],
                'color_hex' => $this->selectedType?->display_type === 'color'
                    ? $this->valueForm['color_hex']
                    : null,
                'auto_prefix' => $this->valueForm['auto_prefix'] ?: null,
                'auto_prefix_enabled' => (bool) $this->valueForm['auto_prefix_enabled'],
                'auto_suffix' => $this->valueForm['auto_suffix'] ?: null,
                'auto_suffix_enabled' => (bool) $this->valueForm['auto_suffix_enabled'],
            ]);

            $this->cancelValueEdit();
            unset($this->valuesWithCounts);
            $this->dispatch('attribute-values-updated');
            session()->flash('message', 'Wartosc zaktualizowana');

        } catch (\Exception $e) {
            $this->addError('valueForm.general', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE VALUE
    |--------------------------------------------------------------------------
    */

    public function deleteValue(int $valueId): void
    {
        try {
            $value = AttributeValue::withCount('variantAttributes')->find($valueId);
            if (!$value) return;

            // Block if used in variants
            if ($value->variant_attributes_count > 0) {
                $this->addError('valueDelete', "Nie mozna usunac - {$value->variant_attributes_count} wariantow uzywa tej wartosci");
                return;
            }

            // Remove from selection if selected
            $this->selectedValueIds = array_values(array_diff($this->selectedValueIds, [$valueId]));
            if (empty($this->selectedValueIds)) {
                $this->showProductPanel = false;
            }

            $value->delete();

            unset($this->valuesWithCounts, $this->attributeTypes, $this->products, $this->productCount);
            $this->dispatch('attribute-values-updated');
            session()->flash('message', 'Wartosc usunieta');

        } catch (\Exception $e) {
            $this->addError('valueDelete', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function resetValueForm(): void
    {
        $this->valueForm = [
            'label' => '',
            'code' => '',
            'color_hex' => '#000000',
            'auto_prefix' => '',
            'auto_prefix_enabled' => false,
            'auto_suffix' => '',
            'auto_suffix_enabled' => false,
        ];
    }

    public function generateValueCode(): void
    {
        if (!empty($this->valueForm['label']) && empty($this->valueForm['code'])) {
            $this->valueForm['code'] = Str::slug($this->valueForm['label'], '_');
        }
    }
}
