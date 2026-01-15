# RAPORT PRACY AGENTA: frontend-specialist
**Data**: 2025-12-11
**Agent**: frontend-specialist
**Zadanie**: Redesign UI panelu "Przegladarka Wariantow" w VariantPanelContainer

---

## 1. ANALIZA OBECNEGO STANU

### Struktura obecna:
```
+----------------------------------------+
|  HEADER (z przyciskami modal)          |
|  [Zarzadzaj grupami] [Zarzadzaj wart.] |
+----------------------------------------+
|  LEFT    |   CENTER   |     RIGHT      |
|  PANEL   |   PANEL    |     PANEL      |
| (grupy)  | (wartosci) |   (produkty)   |
|          |            |                |
| - lista  | - grid dla | - lista prod.  |
|   grup   |   kolorow  |   z wariant.   |
|          | - lista    |                |
|          |   dla rest |                |
+----------------------------------------+
```

### Pliki zrodlowe:
- **Blade**: `resources/views/livewire/admin/variants/variant-panel-container.blade.php` (418 linii)
- **PHP**: `app/Http/Livewire/Admin/Variants/VariantPanelContainer.php` (315 linii)
- **CSS**: `resources/css/admin/components.css` (istniejace klasy)

### Problemy obecnego UI:
1. **Przyciski w headerze** - zajmuja przestrzen, wymagaja modali
2. **Brak inline CRUD** - operacje wymagaja otwierania oddzielnych modali
3. **Brak akcji hover** - nie widac od razu opcji edycji/usuwania

---

## 2. NOWY DESIGN - SPECYFIKACJA

### 2.1 ZMIANA HEADERA
**USUNAC:**
```blade
{{-- Te przyciski usuwamy z headera --}}
<button wire:click="openGroupManager">Zarzadzaj grupami</button>
<button wire:click="openValueManager">Zarzadzaj wartosciami</button>
```

**ZOSTAWIC:**
```blade
{{-- Header pozostaje z tytulem i opisem --}}
<div class="flex items-center gap-3">
    <span class="text-2xl">...</span>
    <div>
        <h2>Przegladarka Wariantow</h2>
        <p>Wybierz grupe i wartosci aby zobaczyc produkty</p>
    </div>
</div>
```

---

### 2.2 LEFT PANEL - GRUPY ATRYBUTOW (INLINE CRUD)

#### Struktura nowa:
```
+------------------------+
| GRUPY ATRYBUTOW        |
+------------------------+
| [Kolor]           [..] | <- hover pokazuje ikony
| [Rozmiar]         [..] |
| [Material]        [..] |
+------------------------+
|  [+ Dodaj grupe...]    | <- input na dole
+------------------------+
```

#### HTML/Blade dla elementu grupy z hover actions:
```blade
{{-- Kazda grupa z akcjami przy hover --}}
<div class="variant-group-item group" wire:key="type-{{ $type->id }}">
    <button wire:click="selectType({{ $type->id }})"
            class="variant-group-button {{ $selectedTypeId === $type->id ? 'active' : '' }}">
        <div class="flex items-center gap-2">
            <span class="variant-group-icon">
                @if($type->display_type === 'color') ... @else ... @endif
            </span>
            <span class="variant-group-name">{{ $type->name }}</span>
        </div>
        <span class="variant-group-count">{{ $type->values_count }}</span>
    </button>

    {{-- Hover Actions - widoczne tylko przy hover --}}
    <div class="variant-group-actions">
        <button wire:click="editGroup({{ $type->id }})"
                class="btn-icon-sm btn-icon-edit" title="Edytuj">
            <svg>...</svg>
        </button>
        <button wire:click="deleteGroup({{ $type->id }})"
                wire:confirm="Usunac grupe {{ $type->name }}?"
                class="btn-icon-sm btn-icon-delete" title="Usun">
            <svg>...</svg>
        </button>
    </div>
</div>
```

#### Inline Add Group Input (na dole panelu):
```blade
{{-- Na dole LEFT PANEL --}}
<div class="variant-panel-footer">
    <div x-data="{ adding: false, name: '', displayType: 'dropdown' }" class="variant-add-form">
        {{-- Toggle form --}}
        <button @click="adding = !adding"
                x-show="!adding"
                class="variant-add-trigger">
            <span>+</span> Dodaj grupe atrybutow
        </button>

        {{-- Inline form --}}
        <div x-show="adding" x-cloak class="variant-add-inline">
            <input type="text"
                   x-model="name"
                   @keydown.enter="$wire.createGroup(name, displayType); name = ''; adding = false"
                   @keydown.escape="adding = false"
                   class="inline-edit-input"
                   placeholder="Nazwa grupy...">
            <select x-model="displayType" class="inline-edit-select">
                <option value="dropdown">Lista</option>
                <option value="color">Kolor</option>
                <option value="radio">Radio</option>
                <option value="button">Przyciski</option>
            </select>
            <div class="variant-add-actions">
                <button @click="$wire.createGroup(name, displayType); name = ''; adding = false"
                        class="btn-icon-sm btn-icon-confirm">...</button>
                <button @click="adding = false"
                        class="btn-icon-sm btn-icon-cancel">...</button>
            </div>
        </div>
    </div>
</div>
```

---

### 2.3 CENTER PANEL - WARTOSCI (INLINE CRUD)

#### Struktura nowa:
```
+----------------------------------+
| [Nazwa grupy] - X wartosci       |
| [Wyczysc] [Zaznacz wszystkie]    |
+----------------------------------+
| LISTA/GRID WARTOSCI              |
|                                  |
| [Wartość 1]    [edit][del]       |
| [Wartość 2]    [edit][del]       |
| ...                              |
+----------------------------------+
| [+ Dodaj nowa wartosc...]        |
+----------------------------------+
```

#### Inline Add Value - rozne typy display_type:

##### Dla display_type="color":
```blade
<div class="variant-add-value" x-data="{
    adding: false,
    label: '',
    code: '',
    colorHex: '#3B82F6'
}">
    <button @click="adding = !adding" x-show="!adding" class="variant-add-trigger">
        <span>+</span> Dodaj nowa wartosc
    </button>

    <div x-show="adding" x-cloak class="variant-add-inline variant-add-color">
        {{-- Color Preview + Picker --}}
        <div class="variant-color-preview"
             :style="'background-color: ' + colorHex"
             @click="$refs.colorInput.click()">
        </div>
        <input type="color" x-ref="colorInput" x-model="colorHex" class="hidden">

        {{-- Label --}}
        <input type="text" x-model="label"
               @keydown.enter="..."
               class="inline-edit-input flex-1"
               placeholder="Nazwa koloru...">

        {{-- Code (auto-generated) --}}
        <input type="text" x-model="code"
               class="inline-edit-input w-24"
               placeholder="kod">

        {{-- Actions --}}
        <div class="variant-add-actions">
            <button @click="$wire.createValue(label, code, colorHex); adding = false"
                    class="btn-icon-sm btn-icon-confirm">...</button>
            <button @click="adding = false"
                    class="btn-icon-sm btn-icon-cancel">...</button>
        </div>
    </div>
</div>
```

##### Dla display_type="dropdown/radio/button":
```blade
<div class="variant-add-value" x-data="{ adding: false, label: '', code: '' }">
    <button @click="adding = !adding" x-show="!adding" class="variant-add-trigger">
        <span>+</span> Dodaj nowa wartosc
    </button>

    <div x-show="adding" x-cloak class="variant-add-inline">
        <input type="text" x-model="label"
               @keydown.enter="$wire.createValue(label, code); adding = false"
               @keydown.escape="adding = false"
               class="inline-edit-input flex-1"
               placeholder="Nazwa wartosci...">

        <input type="text" x-model="code"
               class="inline-edit-input w-24"
               placeholder="kod">

        <div class="variant-add-actions">
            <button @click="$wire.createValue(label, code); adding = false"
                    class="btn-icon-sm btn-icon-confirm">...</button>
            <button @click="adding = false"
                    class="btn-icon-sm btn-icon-cancel">...</button>
        </div>
    </div>
</div>
```

#### Hover Actions dla wartosci (list view):
```blade
<button wire:click="toggleValue({{ $value->id }})"
        class="variant-value-item group {{ $isSelected ? 'selected' : '' }}">
    <div class="flex items-center gap-2">
        {{-- Checkbox --}}
        <div class="variant-value-checkbox {{ $isSelected ? 'checked' : '' }}">
            @if($isSelected) <svg>...</svg> @endif
        </div>

        <div>
            <div class="variant-value-label">{{ $value->label }}</div>
            <div class="variant-value-code">kod: {{ $value->code }}</div>
        </div>
    </div>

    {{-- Product count + Hover actions --}}
    <div class="flex items-center gap-2">
        <span class="variant-value-count">{{ $value->product_count }} prod.</span>

        {{-- Hover actions --}}
        <div class="variant-value-actions">
            <button wire:click.stop="editValue({{ $value->id }})"
                    class="btn-icon-sm btn-icon-edit">
                <svg>...</svg>
            </button>
            @if($value->product_count === 0)
            <button wire:click.stop="deleteValue({{ $value->id }})"
                    wire:confirm="Usunac {{ $value->label }}?"
                    class="btn-icon-sm btn-icon-delete">
                <svg>...</svg>
            </button>
            @endif
        </div>
    </div>
</button>
```

---

## 3. NOWE KLASY CSS

### Do dodania w `resources/css/admin/components.css`:

```css
/* ==========================================================================
   VARIANT PANEL - Inline CRUD Components (2025-12)
   ========================================================================== */

/* Group Item with Hover Actions */
.variant-group-item {
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
}

.variant-group-button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-secondary, #cbd5e1);
    transition: all 0.15s ease;
    cursor: pointer;
}

.variant-group-button:hover {
    background: rgba(71, 85, 105, 0.3);
}

.variant-group-button.active {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.4);
    color: #60a5fa;
}

.variant-group-icon {
    font-size: 1rem;
}

.variant-group-name {
    font-size: 0.875rem;
    font-weight: 500;
}

.variant-group-count {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
    background: rgba(71, 85, 105, 0.5);
    color: #94a3b8;
}

.variant-group-button.active .variant-group-count {
    background: rgba(59, 130, 246, 0.25);
    color: #93c5fd;
}

/* Hover Actions - Hidden by default */
.variant-group-actions,
.variant-value-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease, visibility 0.15s ease;
}

.variant-group-item:hover .variant-group-actions,
.variant-value-item:hover .variant-value-actions {
    opacity: 1;
    visibility: visible;
}

/* Small Icon Buttons */
.btn-icon-sm {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-icon-sm svg {
    width: 14px;
    height: 14px;
}

.btn-icon-edit {
    color: #94a3b8;
}
.btn-icon-edit:hover {
    color: #60a5fa;
    background: rgba(59, 130, 246, 0.15);
}

.btn-icon-delete {
    color: #94a3b8;
}
.btn-icon-delete:hover {
    color: #f87171;
    background: rgba(239, 68, 68, 0.15);
}

.btn-icon-confirm {
    color: #94a3b8;
}
.btn-icon-confirm:hover {
    color: #4ade80;
    background: rgba(74, 222, 128, 0.15);
}

.btn-icon-cancel {
    color: #94a3b8;
}
.btn-icon-cancel:hover {
    color: #f87171;
    background: rgba(239, 68, 68, 0.15);
}

/* Panel Footer - Add Button Area */
.variant-panel-footer {
    padding: 0.75rem;
    border-top: 1px solid rgba(71, 85, 105, 0.3);
    background: rgba(15, 23, 42, 0.3);
}

/* Add Trigger Button */
.variant-add-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px dashed rgba(71, 85, 105, 0.5);
    border-radius: 0.5rem;
    background: transparent;
    color: #64748b;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.variant-add-trigger:hover {
    border-color: rgba(59, 130, 246, 0.5);
    color: #60a5fa;
    background: rgba(59, 130, 246, 0.05);
}

.variant-add-trigger span {
    font-size: 1.125rem;
    font-weight: 600;
}

/* Inline Add Form */
.variant-add-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.variant-add-inline .inline-edit-input {
    flex: 1;
    min-width: 120px;
}

.variant-add-actions {
    display: flex;
    gap: 0.25rem;
}

/* Inline Select */
.inline-edit-select {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(71, 85, 105, 0.3);
    border-radius: 0.375rem;
    padding: 0.375rem 0.5rem;
    color: #e2e8f0;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.inline-edit-select:hover {
    border-color: rgba(71, 85, 105, 0.5);
}

.inline-edit-select:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Color Picker Integration */
.variant-color-preview {
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
    border: 2px solid rgba(71, 85, 105, 0.5);
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.variant-color-preview:hover {
    border-color: rgba(71, 85, 105, 0.8);
}

.variant-add-color {
    flex-wrap: nowrap;
}

/* Value Item Styling */
.variant-value-item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(71, 85, 105, 0.3);
    background: rgba(30, 41, 59, 0.3);
    cursor: pointer;
    transition: all 0.15s ease;
}

.variant-value-item:hover {
    border-color: rgba(71, 85, 105, 0.5);
    background: rgba(30, 41, 59, 0.5);
}

.variant-value-item.selected {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.4);
}

.variant-value-checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 2px solid rgba(71, 85, 105, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.variant-value-checkbox.checked {
    background: #3b82f6;
    border-color: #3b82f6;
}

.variant-value-checkbox svg {
    width: 10px;
    height: 10px;
    color: white;
}

.variant-value-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #e2e8f0;
}

.variant-value-item.selected .variant-value-label {
    color: #60a5fa;
}

.variant-value-code {
    font-size: 0.75rem;
    color: #64748b;
}

.variant-value-count {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
    border: 1px solid rgba(139, 92, 246, 0.25);
}

/* Color Swatch in Grid View */
.variant-color-swatch {
    position: relative;
    padding: 0.5rem;
    border-radius: 0.5rem;
    border: 2px solid rgba(71, 85, 105, 0.3);
    background: rgba(30, 41, 59, 0.3);
    cursor: pointer;
    transition: all 0.15s ease;
}

.variant-color-swatch:hover {
    border-color: rgba(71, 85, 105, 0.5);
}

.variant-color-swatch.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.variant-color-swatch-preview {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 0.25rem;
    border: 1px solid rgba(71, 85, 105, 0.3);
    margin-bottom: 0.25rem;
}

.variant-color-swatch-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #e2e8f0;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.variant-color-swatch-count {
    font-size: 0.625rem;
    color: #64748b;
    text-align: center;
}

/* Selected checkmark for color swatches */
.variant-color-swatch-check {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 9999px;
    background: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.variant-color-swatch-check svg {
    width: 10px;
    height: 10px;
    color: white;
}

/* Hover actions for color swatches */
.variant-color-swatch .variant-value-actions {
    position: absolute;
    bottom: 0.25rem;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.9);
    border-radius: 0.25rem;
    padding: 0.125rem;
}
```

---

## 4. NOWE METODY PHP W KOMPONENCIE

### Do dodania w `VariantPanelContainer.php`:

```php
/*
|--------------------------------------------------------------------------
| INLINE CRUD - Groups
|--------------------------------------------------------------------------
*/

/**
 * Create new attribute group (inline)
 */
public function createGroup(string $name, string $displayType = 'dropdown'): void
{
    $this->validate([
        'name' => 'required|string|max:100',
    ], ['name' => $name]);

    try {
        $code = Str::slug($name, '_');

        // Check if code exists
        if (AttributeType::where('code', $code)->exists()) {
            $code = $code . '_' . time();
        }

        AttributeType::create([
            'name' => $name,
            'code' => $code,
            'display_type' => in_array($displayType, ['dropdown', 'color', 'radio', 'button'])
                ? $displayType
                : 'dropdown',
            'position' => AttributeType::max('position') + 1,
            'is_active' => true,
        ]);

        unset($this->attributeTypes);
        $this->dispatch('notify', type: 'success', message: 'Grupa utworzona');

    } catch (\Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Blad: ' . $e->getMessage());
    }
}

/**
 * Edit group - opens inline edit or simple modal
 */
public function editGroup(int $typeId): void
{
    // Mozna uzyc prostego modala lub inline edit
    // Na razie otwieramy istniejacy GroupManager
    $this->openGroupManager();
}

/**
 * Delete attribute group
 */
public function deleteGroup(int $typeId): void
{
    try {
        $type = AttributeType::findOrFail($typeId);

        // Check if has values
        if ($type->values()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Nie mozna usunac grupy z wartosciami');
            return;
        }

        $type->delete();

        // Reset selection if deleted current
        if ($this->selectedTypeId === $typeId) {
            $this->selectedTypeId = null;
            $this->showValuePanel = false;
        }

        unset($this->attributeTypes);
        $this->dispatch('notify', type: 'success', message: 'Grupa usunieta');

    } catch (\Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Blad: ' . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| INLINE CRUD - Values
|--------------------------------------------------------------------------
*/

/**
 * Create new attribute value (inline)
 */
public function createValue(string $label, string $code = '', ?string $colorHex = null): void
{
    if (!$this->selectedTypeId) {
        $this->dispatch('notify', type: 'error', message: 'Wybierz najpierw grupe');
        return;
    }

    try {
        // Auto-generate code if empty
        if (empty($code)) {
            $code = Str::slug($label, '_');
        }

        // Check if code exists in this type
        if (AttributeValue::where('attribute_type_id', $this->selectedTypeId)
            ->where('code', $code)->exists()) {
            $code = $code . '_' . time();
        }

        $data = [
            'attribute_type_id' => $this->selectedTypeId,
            'label' => $label,
            'code' => $code,
            'position' => AttributeValue::where('attribute_type_id', $this->selectedTypeId)
                ->max('position') + 1,
            'is_active' => true,
        ];

        // Add color if type is color
        if ($colorHex && $this->selectedType?->display_type === 'color') {
            $data['color_hex'] = $colorHex;
        }

        AttributeValue::create($data);

        unset($this->valuesWithCounts, $this->attributeTypes);
        $this->dispatch('notify', type: 'success', message: 'Wartosc utworzona');
        $this->dispatch('attribute-values-updated');

    } catch (\Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Blad: ' . $e->getMessage());
    }
}

/**
 * Edit value - inline or modal
 */
public function editValue(int $valueId): void
{
    // Otwiera istniejacy modal AttributeValueManager do edycji
    $this->dispatch('open-attribute-value-manager', typeId: $this->selectedTypeId);
}

/**
 * Delete attribute value (only if unused)
 */
public function deleteValue(int $valueId): void
{
    try {
        $value = AttributeValue::findOrFail($valueId);

        // Check if used
        if ($value->variantAttributes()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Nie mozna usunac uzwanej wartosci');
            return;
        }

        $value->delete();

        // Remove from selection if selected
        if (in_array($valueId, $this->selectedValueIds)) {
            $this->selectedValueIds = array_values(array_diff($this->selectedValueIds, [$valueId]));
        }

        unset($this->valuesWithCounts, $this->attributeTypes);
        $this->dispatch('notify', type: 'success', message: 'Wartosc usunieta');
        $this->dispatch('attribute-values-updated');

    } catch (\Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Blad: ' . $e->getMessage());
    }
}
```

---

## 5. PODSUMOWANIE ZMIAN

### Pliki do modyfikacji:

| Plik | Zmiany |
|------|--------|
| `variant-panel-container.blade.php` | Usunac przyciski z headera, dodac inline forms i hover actions |
| `VariantPanelContainer.php` | Dodac metody inline CRUD |
| `resources/css/admin/components.css` | Dodac nowe klasy CSS (sekcja Variant Panel) |

### Nowe komponenty UI:

1. **Group Item** - `.variant-group-item` z hover actions
2. **Add Group Form** - inline form na dole LEFT PANEL
3. **Value Item** - `.variant-value-item` z hover actions
4. **Add Value Form** - inline form na dole CENTER PANEL (z obsługa color picker dla typu color)

### Zachowana funkcjonalnosc:

- 3-panel layout (LEFT | CENTER | RIGHT)
- Wybor grup i wartosci
- Filtrowanie produktow
- Modals (AttributeValueManager, AttributeSystemManager) jako backup dla zaawansowanej edycji

---

## 6. CHECKLIST PRZED IMPLEMENTACJA

- [ ] Zero `style="..."` w nowych elementach
- [ ] Wszystkie kolory jako CSS variables
- [ ] Hover actions ukryte domyslnie, widoczne przy :hover
- [ ] Inline forms z Alpine.js (x-show, x-model)
- [ ] Obsluga Escape i Enter w inputach
- [ ] wire:confirm dla akcji destrukcyjnych
- [ ] Zgodnosc z PPM Styling Playbook

---

**Autor:** frontend-specialist
**Data:** 2025-12-11
