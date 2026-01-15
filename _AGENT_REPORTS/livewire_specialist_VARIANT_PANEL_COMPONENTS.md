# RAPORT PRACY AGENTA: livewire-specialist
**Data**: 2025-12-11 (sesja aktualna)
**Agent**: livewire-specialist
**Zadanie**: Projekt architektury komponentow Livewire dla nowego panelu wariantow

---

## 1. ANALIZA OBECNEGO STANU

### 1.1 Istniejace Komponenty

| Komponent | Lokalizacja | Linie | Funkcjonalnosc |
|-----------|-------------|-------|----------------|
| `AttributeSystemManager` | `Admin/Variants/` | 327 | CRUD dla AttributeType + PrestaShop sync badges |
| `AttributeValueManager` | `Admin/Variants/` | 276 | CRUD dla AttributeValue + modal z tabelka |
| `AttributeValueBulkOperations` | `Traits/` | 227 | Bulk select/delete, search/filter/sort |

### 1.2 Obecna Architektura Komunikacji

```
AttributeSystemManager (parent)
    |
    |-- dispatch('open-attribute-value-manager', typeId: $id)
    |
    v
AttributeValueManager (embedded component)
    |
    |-- #[On('open-attribute-value-manager')]
    |-- dispatch('attribute-values-updated')
```

**Obecne Zalety:**
- Computed properties z `#[Computed]` dla memoizacji
- Trait extraction dla bulk operations (SRP compliance)
- Lazy services via `app()` w getterach (nie __construct!)
- `@teleport('body')` dla modali z `$wire` pattern
- `wire:key` na wszystkich loop items

**Obecne Problemy:**
- Brak drill-down navigation (Type -> Values -> Products)
- Brak inline editing (pelny modal dla kazdej zmiany)
- N+1 potencjal w `getSyncStatusForType()` (query per render)
- Brak lazy loading produktow (cala kolekcja od razu)

---

## 2. PROPONOWANA ARCHITEKTURA KOMPONENTOW

### 2.1 Hierarchia Komponentow

```
VariantPanelContainer (NOWY - orchestrator)
├── AttributeTypeList (NOWY - grid/list of types)
│   └── AttributeTypeCard (NOWY - single type card)
│       ├── TypeHeader (inline display)
│       ├── SyncStatusBadges (per-shop status)
│       └── TypeActions (buttons: edit, values, sync)
│
├── AttributeValuePanel (REFACTORED - drawer/modal)
│   ├── ValueListTable (scrollable with virtual scrolling)
│   │   └── ValueRow (single row with inline edit mode)
│   ├── ValueEditForm (create/edit form)
│   └── ValueBulkActions (toolbar)
│
└── ProductPreviewPanel (NOWY - lazy loaded)
    └── ProductListItem (minimal product info)
```

### 2.2 Szczegolowy Projekt Komponentow

---

#### A. `VariantPanelContainer` (Orchestrator)

**Lokalizacja:** `app/Http/Livewire/Admin/Variants/VariantPanelContainer.php`

**Odpowiedzialnosc:**
- Glowny kontener z layoutem
- Zarzadzanie aktywnym panelem (types/values/products)
- Breadcrumb navigation state
- Global flash messages

```php
<?php
namespace App\Http\Livewire\Admin\Variants;

use Livewire\Component;
use Livewire\Attributes\On;

class VariantPanelContainer extends Component
{
    // Navigation State
    public string $activePanel = 'types'; // types | values | products
    public ?int $selectedTypeId = null;
    public ?int $selectedValueId = null;

    // Breadcrumb data (computed via events)
    public ?string $selectedTypeName = null;
    public ?string $selectedValueLabel = null;

    #[On('navigate-to-values')]
    public function showValuesPanel(int $typeId, string $typeName): void
    {
        $this->selectedTypeId = $typeId;
        $this->selectedTypeName = $typeName;
        $this->activePanel = 'values';
        $this->dispatch('load-values', typeId: $typeId);
    }

    #[On('navigate-to-products')]
    public function showProductsPanel(int $valueId, string $valueLabel): void
    {
        $this->selectedValueId = $valueId;
        $this->selectedValueLabel = $valueLabel;
        $this->activePanel = 'products';
        $this->dispatch('load-products', valueId: $valueId);
    }

    #[On('navigate-back')]
    public function navigateBack(): void
    {
        match($this->activePanel) {
            'products' => $this->activePanel = 'values',
            'values' => $this->activePanel = 'types',
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.admin.variants.variant-panel-container')
            ->layout('layouts.admin', ['title' => 'System Wariantow - PPM']);
    }
}
```

**Blade Template Pattern:**

```blade
<div class="enterprise-card">
    {{-- Breadcrumb Navigation --}}
    <nav class="flex items-center gap-2 mb-6 text-sm">
        <button wire:click="$set('activePanel', 'types')"
                class="{{ $activePanel === 'types' ? 'text-mpp-orange' : 'text-gray-400 hover:text-white' }}">
            Grupy Atrybutow
        </button>
        @if($activePanel !== 'types')
            <span class="text-gray-600">/</span>
            <button wire:click="$set('activePanel', 'values')"
                    class="{{ $activePanel === 'values' ? 'text-mpp-orange' : 'text-gray-400 hover:text-white' }}">
                {{ $selectedTypeName }}
            </button>
        @endif
        @if($activePanel === 'products')
            <span class="text-gray-600">/</span>
            <span class="text-mpp-orange">{{ $selectedValueLabel }}</span>
        @endif
    </nav>

    {{-- Panel Content (conditional rendering) --}}
    <div x-show="$wire.activePanel === 'types'" x-cloak>
        <livewire:admin.variants.attribute-type-list />
    </div>

    <div x-show="$wire.activePanel === 'values'" x-cloak>
        <livewire:admin.variants.attribute-value-panel :type-id="$selectedTypeId" />
    </div>

    <div x-show="$wire.activePanel === 'products'" x-cloak>
        <livewire:admin.variants.product-preview-panel :value-id="$selectedValueId" lazy />
    </div>
</div>
```

---

#### B. `AttributeTypeCard` (Subcomponent)

**Lokalizacja:** `app/Http/Livewire/Admin/Variants/AttributeTypeCard.php`

**Odpowiedzialnosc:**
- Single type card rendering
- Inline edit mode (bez modalu dla prostych zmian)
- Local state dla editing

```php
<?php
namespace App\Http\Livewire\Admin\Variants;

use App\Models\AttributeType;
use Livewire\Component;
use Livewire\Attributes\Modelable;

class AttributeTypeCard extends Component
{
    public AttributeType $type;
    public bool $isEditing = false;

    // Inline edit form data
    #[Modelable]
    public string $editName = '';
    public string $editDisplayType = '';

    public function mount(AttributeType $type): void
    {
        $this->type = $type;
        $this->editName = $type->name;
        $this->editDisplayType = $type->display_type;
    }

    public function startEdit(): void
    {
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->editName = $this->type->name;
        $this->editDisplayType = $this->type->display_type;
    }

    public function saveInline(): void
    {
        $this->validate([
            'editName' => 'required|string|max:100',
            'editDisplayType' => 'required|in:dropdown,radio,color,button',
        ]);

        $this->type->update([
            'name' => $this->editName,
            'display_type' => $this->editDisplayType,
        ]);

        $this->isEditing = false;
        $this->dispatch('type-updated', typeId: $this->type->id);
    }

    public function navigateToValues(): void
    {
        $this->dispatch('navigate-to-values',
            typeId: $this->type->id,
            typeName: $this->type->name
        );
    }

    public function render()
    {
        return view('livewire.admin.variants.attribute-type-card');
    }
}
```

---

#### C. `ValueRow` (Inline Editing)

**Lokalizacja:** `app/Http/Livewire/Admin/Variants/ValueRow.php`

**Optymalne podejscie do inline editing:**

```php
<?php
namespace App\Http\Livewire\Admin\Variants;

use App\Models\AttributeValue;
use Livewire\Component;

class ValueRow extends Component
{
    public AttributeValue $value;
    public array $usageStats;
    public bool $isColorType;

    // Inline edit state
    public bool $isEditing = false;
    public string $editLabel = '';
    public string $editCode = '';
    public string $editColorHex = '';

    public function mount(AttributeValue $value, array $usageStats, bool $isColorType): void
    {
        $this->value = $value;
        $this->usageStats = $usageStats;
        $this->isColorType = $isColorType;
        $this->resetEditForm();
    }

    public function startEdit(): void
    {
        $this->resetEditForm();
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
    }

    public function saveInline(): void
    {
        $rules = [
            'editLabel' => 'required|string|max:100',
            'editCode' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
        ];

        if ($this->isColorType) {
            $rules['editColorHex'] = 'nullable|regex:/^#[0-9A-Fa-f]{6}$/';
        }

        $this->validate($rules);

        $this->value->update([
            'label' => $this->editLabel,
            'code' => $this->editCode,
            'color_hex' => $this->isColorType ? $this->editColorHex : null,
        ]);

        $this->isEditing = false;
        $this->dispatch('value-updated');
    }

    public function showProducts(): void
    {
        $this->dispatch('navigate-to-products',
            valueId: $this->value->id,
            valueLabel: $this->value->label
        );
    }

    protected function resetEditForm(): void
    {
        $this->editLabel = $this->value->label;
        $this->editCode = $this->value->code;
        $this->editColorHex = $this->value->color_hex ?? '';
    }

    public function render()
    {
        return view('livewire.admin.variants.value-row');
    }
}
```

---

#### D. `ProductPreviewPanel` (Lazy Loading)

**Lokalizacja:** `app/Http/Livewire/Admin/Variants/ProductPreviewPanel.php`

**Kluczowe cechy:**
- `#[Lazy]` attribute dla deferred loading
- Paginacja dla duzych list
- Minimalne dane produktu

```php
<?php
namespace App\Http\Livewire\Admin\Variants;

use App\Services\Product\AttributeUsageService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\WithPagination;

#[Lazy]
class ProductPreviewPanel extends Component
{
    use WithPagination;

    public ?int $valueId = null;
    public int $perPage = 20;

    private ?AttributeUsageService $usageService = null;

    protected function getUsageService(): AttributeUsageService
    {
        return $this->usageService ??= app(AttributeUsageService::class);
    }

    #[On('load-products')]
    public function loadForValue(int $valueId): void
    {
        $this->valueId = $valueId;
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        if (!$this->valueId) {
            return collect([]);
        }

        return $this->getUsageService()
            ->getProductsUsingAttributeValue($this->valueId)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function valueInfo()
    {
        if (!$this->valueId) {
            return null;
        }

        return \App\Models\AttributeValue::with('attributeType')
            ->find($this->valueId);
    }

    public function placeholder()
    {
        return view('livewire.admin.variants.product-preview-placeholder');
    }

    public function render()
    {
        return view('livewire.admin.variants.product-preview-panel');
    }
}
```

---

## 3. KOMUNIKACJA MIEDZY KOMPONENTAMI

### 3.1 Event Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    VariantPanelContainer                         │
│  Listens: navigate-to-values, navigate-to-products, navigate-back│
└─────────────────────────────────────────────────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         │                    │                    │
         ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ AttributeTypeList│  │AttributeValuePanel│ │ProductPreviewPanel│
│                 │  │                 │  │ (lazy loaded)   │
│ Dispatches:     │  │ Dispatches:     │  │                 │
│ - navigate-to-  │  │ - navigate-to-  │  │ Listens:        │
│   values        │  │   products      │  │ - load-products │
│ - type-updated  │  │ - value-updated │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
         │                    │
         ▼                    ▼
┌─────────────────┐  ┌─────────────────┐
│ AttributeTypeCard│ │    ValueRow     │
│ (per type)      │  │ (per value)     │
│                 │  │                 │
│ Inline edit     │  │ Inline edit     │
│ Local state     │  │ Local state     │
└─────────────────┘  └─────────────────┘
```

### 3.2 Rekomendowane Patterns

**A. Parent-Child Communication:**
```php
// Child dispatches UP to parent
$this->dispatch('navigate-to-values', typeId: $id, typeName: $name);

// Parent listens with #[On]
#[On('navigate-to-values')]
public function showValuesPanel(int $typeId, string $typeName): void { }
```

**B. Sibling Communication (via parent):**
```php
// Type card dispatches
$this->dispatch('type-updated', typeId: $this->type->id);

// Container catches and re-dispatches
#[On('type-updated')]
public function handleTypeUpdated(int $typeId): void
{
    $this->dispatch('refresh-type-list'); // to AttributeTypeList
}
```

**C. Inline Data Binding (Modelable):**
```php
// Child component
#[Modelable]
public string $editName = '';

// Parent can bind
<livewire:attribute-type-card :type="$type" wire:model="typeNames.{{ $type->id }}" />
```

---

## 4. OPTYMALIZACJE WYDAJNOSCI

### 4.1 Zapobieganie N+1

**Problem w obecnym kodzie:**
```php
// attribute-system-manager.blade.php line 78
@php
    $syncStatuses = $this->getSyncStatusForType($type->id); // Query per type!
@endphp
```

**Rozwiazanie - Batch Query:**
```php
#[Computed(persist: true)]
public function allSyncStatuses(): array
{
    $shops = PrestaShopShop::where('is_active', true)->get();
    $typeIds = $this->attributeTypes->pluck('id');

    $mappings = DB::table('prestashop_attribute_group_mapping')
        ->whereIn('attribute_type_id', $typeIds)
        ->get()
        ->groupBy('attribute_type_id');

    $result = [];
    foreach ($typeIds as $typeId) {
        $result[$typeId] = [];
        foreach ($shops as $shop) {
            $mapping = $mappings->get($typeId)?->firstWhere('prestashop_shop_id', $shop->id);
            $result[$typeId][$shop->id] = [
                'shop_name' => $shop->name,
                'status' => $mapping ? ($mapping->sync_status ?? 'pending') : 'missing',
                'ps_id' => $mapping->prestashop_attribute_group_id ?? null,
            ];
        }
    }
    return $result;
}

// Usage in blade:
$syncStatuses = $this->allSyncStatuses[$type->id] ?? [];
```

### 4.2 wire:model Optimization

**Obecny problem:**
```blade
<input wire:model.live.debounce.300ms="search">
<select wire:model.live="filterStatus">
```

**Rekomendacja:**
- `wire:model.live` = request per keystroke (nawet z debounce)
- Dla search/filter: OK z debounce 300-500ms
- Dla checkboxes w bulk select: **NIE** uzywac `.live`

```blade
{{-- DOBRZE: wire:model (bez .live) + wire:click --}}
<input type="checkbox" wire:model="selectedValues" wire:click="toggleValueSelection({{ $value->id }})">

{{-- ZLE: wire:model.live na checkbox (request per click) --}}
<input type="checkbox" wire:model.live="selectedValues.{{ $value->id }}">
```

### 4.3 Lazy Loading Pattern

```php
// Component z #[Lazy]
#[Lazy]
class ProductPreviewPanel extends Component
{
    // Placeholder podczas ladowania
    public function placeholder()
    {
        return <<<'HTML'
        <div class="animate-pulse bg-gray-800 rounded-lg h-64">
            <div class="p-6 space-y-4">
                <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                <div class="h-4 bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>
        HTML;
    }
}
```

```blade
{{-- W parent - lazy zaladuje gdy panel widoczny --}}
<div x-show="$wire.activePanel === 'products'" x-cloak>
    <livewire:admin.variants.product-preview-panel lazy />
</div>
```

### 4.4 Computed Property Caching

```php
// Bez cache - query per render
#[Computed]
public function values(): Collection { return AttributeValue::where(...)->get(); }

// Z persist - cache w session komponentu
#[Computed(persist: true)]
public function activeShops(): Collection { return PrestaShopShop::active()->get(); }

// Z global cache - cache w Laravel Cache
#[Computed(cache: true, key: 'attribute-types-list')]
public function allTypes(): Collection { return AttributeType::all(); }
```

---

## 5. POTENCJALNE PROBLEMY LIVEWIRE

### 5.1 Znane Issues do Unikniecia

| Problem | Rozwiazanie |
|---------|-------------|
| wire:poll w @if | wire:poll NA ZEWNATRZ conditional |
| wire:click w x-teleport | Uzyj `$wire.method()` lub `wire:id` |
| Missing wire:key | ZAWSZE `wire:key="unique-{{ $item->id }}"` w loops |
| DI w __construct | Uzyj `boot()` lub lazy getters |
| emit() deprecated | Uzyj `dispatch()` (Livewire 3.x) |

### 5.2 Inline Edit vs Modal Trade-offs

| Aspekt | Inline Edit | Modal |
|--------|-------------|-------|
| UX | Szybsze, mniej klikniec | Bardziej formalne |
| Walidacja | Trudniejsza (limited space) | Pelna kontrola |
| Re-rendering | Local scope (lepiej) | Full component refresh |
| Mobile | Moze byc ciasno | Pelnoekranowy modal |
| Complex forms | Nie nadaje sie | Idealne |

**Rekomendacja:**
- **Inline edit**: Proste pola (name, code, color)
- **Modal**: Kompleksowe formy (prefix/suffix, bulk operations)
- **Drawer**: Lista produktow (sizable, scrollable)

### 5.3 Struktura Stanu (Properties)

```php
// GLOWNY KOMPONENT (Container)
public string $activePanel = 'types';      // Navigation
public ?int $selectedTypeId = null;        // Context
public ?int $selectedValueId = null;       // Context

// SUBKOMPONENT (Card/Row)
public AttributeType $type;                // Passed via mount
public bool $isEditing = false;            // Local UI state
public string $editName = '';              // Form state (reset on cancel)

// COMPUTED (nie properties!)
#[Computed]
public function attributeTypes(): Collection { }  // Query result
#[Computed(persist: true)]
public function activeShops(): Collection { }     // Cached query
```

---

## 6. PROPONOWANA STRUKTURA PLIKOW

```
app/Http/Livewire/Admin/Variants/
├── VariantPanelContainer.php          # NOWY - orchestrator
├── AttributeTypeList.php              # NOWY - grid of types
├── AttributeTypeCard.php              # NOWY - single type (inline edit)
├── AttributeValuePanel.php            # REFACTORED - drawer/panel
├── ValueRow.php                       # NOWY - single value (inline edit)
├── ProductPreviewPanel.php            # NOWY - lazy products list
├── AttributeSystemManager.php         # DEPRECATED - zastapiony Container
├── AttributeValueManager.php          # DEPRECATED - zastapiony Panel
└── Traits/
    ├── AttributeValueBulkOperations.php  # Zachowany (dobre SRP)
    └── TypeSyncStatusLoader.php          # NOWY - batch sync status

resources/views/livewire/admin/variants/
├── variant-panel-container.blade.php     # NOWY
├── attribute-type-list.blade.php         # NOWY
├── attribute-type-card.blade.php         # NOWY
├── attribute-value-panel.blade.php       # REFACTORED
├── value-row.blade.php                   # NOWY
├── product-preview-panel.blade.php       # NOWY
├── product-preview-placeholder.blade.php # NOWY (lazy loading)
└── partials/
    ├── value-edit-form.blade.php         # Zachowany
    ├── sync-modal.blade.php              # Zachowany
    └── products-modal.blade.php          # Do usuniecia (zastapiony Panel)
```

---

## 7. KOLEJNOSC IMPLEMENTACJI

### Faza 1: Container + Navigation (1-2h)
1. Utworz `VariantPanelContainer.php`
2. Utworz breadcrumb navigation w blade
3. Przenieś istniejace komponenty jako embedded
4. Test drill-down flow

### Faza 2: Type Cards z Inline Edit (2-3h)
1. Utworz `AttributeTypeCard.php` z inline editing
2. Utworz `AttributeTypeList.php` z grid layout
3. Batch sync status query (N+1 fix)
4. Test inline edit flow

### Faza 3: Value Panel Refactor (2-3h)
1. Utworz `ValueRow.php` z inline editing
2. Refaktoruj `AttributeValuePanel.php`
3. Zachowaj bulk operations z traitu
4. Test table interactions

### Faza 4: Product Preview Lazy (1-2h)
1. Utworz `ProductPreviewPanel.php` z `#[Lazy]`
2. Pagination dla duzych list
3. Placeholder component
4. Test lazy loading

### Faza 5: Cleanup + Testing (1h)
1. Usun deprecated komponenty
2. Update routes
3. Full flow testing
4. Chrome DevTools verification

---

## 8. CHECKLIST PRZED IMPLEMENTACJA

- [ ] Kazdy komponent < 300 linii (SRP)
- [ ] wire:key na wszystkich @foreach
- [ ] dispatch() zamiast emit() (Livewire 3.x)
- [ ] boot() zamiast __construct() dla DI
- [ ] #[Computed] dla query results
- [ ] wire:model (bez .live) na checkboxes
- [ ] wire:poll POZA @if conditions
- [ ] x-teleport + `$wire.method()` pattern
- [ ] Lazy loading dla ciezkich paneli
- [ ] Batch queries dla sync status (N+1)

---

## PODSUMOWANIE

Proponowana architektura wprowadza:

1. **Modularnosc**: Rozdzielenie na male, wyspecjalizowane komponenty
2. **Drill-down UX**: Naturalna nawigacja Type -> Values -> Products
3. **Inline editing**: Szybkie zmiany bez pelnych modali
4. **Performance**: Lazy loading, batch queries, computed caching
5. **Livewire 3.x compliance**: Wszystkie patterns zgodne z Context7 docs

**Szacowany czas implementacji**: 8-12h

**Ryzyko**: Niskie - bazujemy na sprawdzonych wzorcach z istniejacego kodu

---

## PLIKI REFERENCYJNE

- Obecny kod: `app/Http/Livewire/Admin/Variants/AttributeSystemManager.php`
- Obecny kod: `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
- Context7 Livewire docs: `/livewire/livewire` (867 snippets)
- PPM Styling: `_DOCS/PPM_Styling_Playbook.md`
- Livewire Issues: `_ISSUES_FIXES/LIVEWIRE_*.md`
