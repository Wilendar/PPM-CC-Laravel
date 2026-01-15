<?php

namespace App\Http\Livewire\Admin\Variants\Traits;

use App\Models\AttributeType;
use Illuminate\Support\Str;

/**
 * TypeInlineCrud - Inline CRUD for Attribute Types (Groups)
 *
 * Used by VariantPanelContainer for inline type management.
 *
 * @package App\Http\Livewire\Admin\Variants\Traits
 * @since Variant Panel Inline CRUD 2025-12
 */
trait TypeInlineCrud
{
    // Inline form state for types
    public bool $showTypeCreateForm = false;
    public ?int $editingTypeId = null;
    public array $typeForm = [
        'name' => '',
        'code' => '',
        'display_type' => 'dropdown',
    ];

    /*
    |--------------------------------------------------------------------------
    | CREATE TYPE
    |--------------------------------------------------------------------------
    */

    public function showCreateTypeForm(): void
    {
        $this->resetTypeForm();
        $this->showTypeCreateForm = true;
        $this->editingTypeId = null;
    }

    public function cancelTypeCreate(): void
    {
        $this->showTypeCreateForm = false;
        $this->resetTypeForm();
        $this->resetErrorBag('typeForm.*');
    }

    public function createType(): void
    {
        $this->validate([
            'typeForm.name' => 'required|string|max:100|min:2',
            'typeForm.code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                'unique:attribute_types,code',
            ],
            'typeForm.display_type' => 'required|in:dropdown,radio,color,button',
        ], [
            'typeForm.name.required' => 'Nazwa grupy jest wymagana',
            'typeForm.name.min' => 'Min. 2 znaki',
            'typeForm.code.required' => 'Kod jest wymagany',
            'typeForm.code.regex' => 'Kod: male litery, cyfry, _',
            'typeForm.code.unique' => 'Ten kod juz istnieje',
        ]);

        try {
            $maxPosition = AttributeType::max('position') ?? 0;

            $type = AttributeType::create([
                'name' => $this->typeForm['name'],
                'code' => $this->typeForm['code'],
                'display_type' => $this->typeForm['display_type'],
                'position' => $maxPosition + 1,
                'is_active' => true,
            ]);

            $this->cancelTypeCreate();
            unset($this->attributeTypes);
            $this->dispatch('attribute-types-updated');

            // Auto-select new type
            $this->selectType($type->id);

            session()->flash('message', 'Grupa utworzona');

        } catch (\Exception $e) {
            $this->addError('typeForm.general', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT TYPE
    |--------------------------------------------------------------------------
    */

    public function startTypeEdit(int $typeId): void
    {
        $type = AttributeType::find($typeId);
        if (!$type) return;

        $this->editingTypeId = $typeId;
        $this->typeForm = [
            'name' => $type->name,
            'code' => $type->code,
            'display_type' => $type->display_type,
        ];
        $this->showTypeCreateForm = false;
    }

    public function cancelTypeEdit(): void
    {
        $this->editingTypeId = null;
        $this->resetTypeForm();
        $this->resetErrorBag('typeForm.*');
    }

    public function saveTypeEdit(): void
    {
        $this->validate([
            'typeForm.name' => 'required|string|max:100|min:2',
            'typeForm.code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                'unique:attribute_types,code,' . $this->editingTypeId,
            ],
            'typeForm.display_type' => 'required|in:dropdown,radio,color,button',
        ], [
            'typeForm.name.required' => 'Nazwa grupy jest wymagana',
            'typeForm.code.required' => 'Kod jest wymagany',
            'typeForm.code.regex' => 'Kod: male litery, cyfry, _',
            'typeForm.code.unique' => 'Ten kod juz istnieje',
        ]);

        try {
            $type = AttributeType::find($this->editingTypeId);
            if (!$type) return;

            $type->update([
                'name' => $this->typeForm['name'],
                'code' => $this->typeForm['code'],
                'display_type' => $this->typeForm['display_type'],
            ]);

            $this->cancelTypeEdit();
            unset($this->attributeTypes, $this->selectedType);
            $this->dispatch('attribute-types-updated');
            session()->flash('message', 'Grupa zaktualizowana');

        } catch (\Exception $e) {
            $this->addError('typeForm.general', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE TYPE
    |--------------------------------------------------------------------------
    */

    public function deleteType(int $typeId): void
    {
        try {
            $type = AttributeType::withCount('variantAttributes')->find($typeId);
            if (!$type) return;

            // Block if used in variants
            if ($type->variant_attributes_count > 0) {
                $this->addError('typeDelete', "Nie mozna usunac - {$type->variant_attributes_count} wariantow uzywa tej grupy");
                return;
            }

            // Delete values (cascade)
            $type->values()->delete();
            $type->delete();

            // Reset selection if deleted type was selected
            if ($this->selectedTypeId === $typeId) {
                $this->selectedTypeId = null;
                $this->showValuePanel = false;
                $this->selectedValueIds = [];
                $this->showProductPanel = false;
            }

            unset($this->attributeTypes);
            $this->dispatch('attribute-types-updated');
            session()->flash('message', 'Grupa usunieta');

        } catch (\Exception $e) {
            $this->addError('typeDelete', 'Blad: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function resetTypeForm(): void
    {
        $this->typeForm = [
            'name' => '',
            'code' => '',
            'display_type' => 'dropdown',
        ];
    }

    public function generateTypeCode(): void
    {
        if (!empty($this->typeForm['name']) && empty($this->typeForm['code'])) {
            $this->typeForm['code'] = Str::slug($this->typeForm['name'], '_');
        }
    }
}
