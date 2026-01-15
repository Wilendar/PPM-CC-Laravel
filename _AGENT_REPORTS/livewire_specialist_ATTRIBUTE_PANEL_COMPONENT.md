# RAPORT PRACY AGENTA: livewire-specialist
**Data**: 2025-12-11
**Agent**: livewire-specialist
**Zadanie**: Analiza i plan przebudowy komponentu AttributeValueManager dla lepszego UX i informatywnosci

---

## 1. OBECNA STRUKTURA KOMPONENTU

### 1.1 Plik PHP: `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
**Linie kodu**: 453 linii (PRZEKRACZA limit 300 linii z CLAUDE.md!)
**Wersja**: 2.0 (Phase 5)

#### Properties (17 sztuk)
```php
// Modal control
public bool $showModal = false;
public ?int $attributeTypeId = null;
public ?int $editingValueId = null;
public bool $showEditForm = false;

// Form data
public array $formData = [
    'code', 'label', 'color_hex',
    'auto_prefix', 'auto_prefix_enabled',
    'auto_suffix', 'auto_suffix_enabled',
    'position', 'is_active'
];

// Products Modal
public bool $showProductsModal = false;
public ?int $selectedValueIdForProducts = null;

// Sync Modal
public bool $showSyncModal = false;
public ?int $selectedValueIdForSync = null;

// Lazy-loaded Services
private ?AttributeManager $attributeManager = null;
private ?PrestaShopAttributeSyncService $syncService = null;
```

#### Computed Properties (4 sztuk)
| Property | Opis | Problem |
|----------|------|---------|
| `attributeType()` | Pobiera AttributeType | Brak eager loading |
| `values()` | Lista AttributeValue | **Brak paginacji!** |
| `isColorType()` | Sprawdza display_type | OK |
| `productsUsingValue()` | Produkty uzywajace wartosci | **N+1 query potential** |

#### Methods (18 sztuk)
| Kategoria | Metody |
|-----------|--------|
| Event Listeners | `open()` |
| CRUD | `openCreateModal()`, `openEditModal()`, `save()`, `cancelEdit()`, `delete()`, `reorder()` |
| Modal Control | `closeModal()`, `openProductsModal()`, `closeProductsModal()`, `openSyncModal()`, `closeSyncModal()` |
| PrestaShop Sync | `getSyncStatusForValue()`, `getProductsCountForValue()`, `syncValueToShop()` |
| Helpers | `onColorUpdated()`, `resetForm()` |
| Render | `render()` |

### 1.2 Plik Blade: `resources/views/livewire/admin/variants/attribute-value-manager.blade.php`
**Linie kodu**: 497 linii
**Struktura**: 3 modaly w @teleport

```
attribute-value-manager.blade.php
|-- Main Modal (314 linii)
|   |-- Header (Dodaj Wartosc button)
|   |-- Values List (@foreach $this->values)
|   |   |-- Color Preview (inline style - WYJATEK dozwolony)
|   |   |-- Sync Status Badges
|   |   |-- Products Count Badge
|   |   |-- Action Buttons
|   |-- Edit Form (inline w modalu)
|   |   |-- Code/Label inputs
|   |   |-- Color Picker (nested Livewire)
|   |   |-- Auto SKU Prefix/Suffix
|   |-- Footer (Zamknij)
|-- Products Using Modal (59 linii)
|-- Sync Status Modal (108 linii)
|-- Flash Messages
```

---

## 2. DIAGRAM RELACJI MODELI

```
                    +------------------+
                    |   AttributeType  |
                    +------------------+
                    | id               |
                    | code (unique)    |
                    | name             |
                    | display_type     |  <-- 'color', 'dropdown', 'radio', 'button'
                    | is_active        |
                    | position         |
                    +--------+---------+
                             |
                             | hasMany
                             v
                    +------------------+
                    |  AttributeValue  |
                    +------------------+
                    | id               |
                    | attribute_type_id|  FK
                    | code (unique/type|
                    | label            |
                    | color_hex        |  <-- tylko dla display_type='color'
                    | auto_prefix      |
                    | auto_prefix_enabled
                    | auto_suffix      |
                    | auto_suffix_enabled
                    | position         |
                    | is_active        |
                    +--------+---------+
                             |
              +--------------+--------------+
              |                             |
              | hasMany                     | hasMany
              v                             v
+-------------------------+    +--------------------------------+
|    VariantAttribute     |    | prestashop_attribute_value_    |
+-------------------------+    | mapping                        |
| id                      |    +--------------------------------+
| variant_id         FK   |    | id                             |
| attribute_type_id  FK   |    | attribute_value_id        FK   |
| value_id           FK   |    | prestashop_shop_id        FK   |
| color_hex               |    | prestashop_attribute_id        |
+----------+--------------+    | prestashop_label               |
           |                   | prestashop_color               |
           | belongsTo         | sync_status (enum)             |
           v                   | is_synced                      |
+-------------------------+    | last_synced_at                 |
|    ProductVariant       |    +--------------------------------+
+-------------------------+
| id                      |
| product_id         FK   |
| sku (unique)            |
| name                    |
| is_active               |
| is_default              |
| position                |
+----------+--------------+
           |
           | belongsTo
           v
+-------------------------+
|        Product          |
+-------------------------+
| id                      |
| sku (unique)            |
| name                    |
+-------------------------+
```

---

## 3. ANALIZA SERVICE LAYER

### 3.1 AttributeManager (Facade)
**Plik**: `app/Services/Product/AttributeManager.php` (185 linii)
**Rola**: Delegacja do wyspecjalizowanych serwisow

```php
AttributeManager::class
    -> AttributeTypeService::class   // CRUD AttributeType
    -> AttributeValueService::class  // CRUD AttributeValue
    -> AttributeUsageService::class  // Usage tracking
```

### 3.2 AttributeValueService
**Plik**: `app/Services/Product/AttributeValueService.php` (285 linii)
**Metody uzywane przez komponent**:
- `createAttributeValue(int $typeId, array $data): AttributeValue`
- `updateAttributeValue(AttributeValue $value, array $data): AttributeValue`
- `deleteAttributeValue(AttributeValue $value): bool`
- `reorderAttributeValues(int $typeId, array $valueIdsOrdered): bool`
- `getVariantsUsingAttributeValue(int $valueId): Collection`

### 3.3 AttributeUsageService
**Plik**: `app/Services/Product/AttributeUsageService.php` (165 linii)
**Metody uzywane przez komponent**:
- `getProductsUsingAttributeValue(int $valueId): Collection`
- `countVariantsUsingValue(int $valueId): int`
- `canDeleteValue(int $valueId): bool`

### 3.4 PrestaShopAttributeSyncService
**Plik**: `app/Services/PrestaShop/PrestaShopAttributeSyncService.php` (339 linii)
**Metody uzywane przez komponent**:
- `syncAttributeValue(int $attributeValueId, int $shopId): array`

---

## 4. PROBLEMY WYDAJNOSCIOWE (KRYTYCZNE!)

### 4.1 N+1 Query Problem w Blade
```php
// PROBLEM: Wywolywane w petli @foreach dla kazdej wartosci!
@foreach($this->values as $value)
    {{ $this->getSyncStatusForValue($value->id) }}     // QUERY per value!
    {{ $this->getProductsCountForValue($value->id) }}  // QUERY per value!
@endforeach
```

**Rozwiazanie**: Eager load sync status i products count PRZED renderem

### 4.2 Brak Paginacji
```php
#[Computed]
public function values(): Collection
{
    return AttributeValue::where('attribute_type_id', $this->attributeTypeId)
        ->orderBy('position')
        ->get();  // WSZYSTKIE WARTOSCI NA RAZ!
}
```

**Rozwiazanie**: Dodac `WithPagination` trait lub lazy loading

### 4.3 Redundantne Query w getSyncStatusForValue()
```php
public function getSyncStatusForValue(int $valueId): array
{
    $shops = PrestaShopShop::where('is_active', true)->get(); // Query per call!
    foreach ($shops as $shop) {
        $mapping = DB::table('prestashop_attribute_value_mapping')
            ->where('attribute_value_id', $valueId)
            ->where('prestashop_shop_id', $shop->id)
            ->first();  // Query per shop per value!
    }
}
```

---

## 5. BRAKUJACE METODY/PROPERTIES

### 5.1 Do dodania w PHP
```php
// Search/Filter
public string $search = '';
public string $filterStatus = 'all'; // all, active, inactive
public string $sortField = 'position';
public string $sortDirection = 'asc';

// Bulk Operations
public array $selectedValues = [];
public bool $selectAll = false;

// Eager-loaded data
#[Computed]
public function valuesWithStats(): Collection
{
    return AttributeValue::where('attribute_type_id', $this->attributeTypeId)
        ->withCount(['variantAttributes as products_count' => function($q) {
            $q->distinct('variant_id');
        }])
        ->with(['prestashopMappings']) // eager load
        ->when($this->search, fn($q) => $q->where('label', 'like', "%{$this->search}%"))
        ->when($this->filterStatus !== 'all', fn($q) => $q->where('is_active', $this->filterStatus === 'active'))
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(25);
}

// Bulk Methods
public function deleteSelected(): void;
public function mergeValues(int $targetId, array $sourceIds): void;
public function toggleSelectAll(): void;
public function selectValue(int $valueId): void;
```

### 5.2 Do dodania w AttributeValue Model
```php
// Relacja do mappings (brakuje!)
public function prestashopMappings(): HasMany
{
    return $this->hasMany(AttributeValuePsMapping::class, 'attribute_value_id');
}
```

### 5.3 Nowy Model: AttributeValuePsMapping
**Plik do utworzenia**: `app/Models/AttributeValuePsMapping.php`
```php
class AttributeValuePsMapping extends Model
{
    protected $table = 'prestashop_attribute_value_mapping';

    protected $fillable = [
        'attribute_value_id', 'prestashop_shop_id',
        'prestashop_attribute_id', 'prestashop_label',
        'prestashop_color', 'is_synced', 'sync_status',
        'last_synced_at', 'sync_notes',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'prestashop_shop_id');
    }
}
```

---

## 6. NOWE QUERIES DO DODANIA

### 6.1 Optimized Values Query (zamienia N+1)
```php
public function getValuesWithAllStats(): LengthAwarePaginator
{
    $activeShops = PrestaShopShop::where('is_active', true)->pluck('id');

    return AttributeValue::query()
        ->where('attribute_type_id', $this->attributeTypeId)
        // Products count (distinct products via variants)
        ->withCount([
            'variantAttributes as variants_count',
            'variantAttributes as products_count' => function($q) {
                $q->select(DB::raw('COUNT(DISTINCT variant_id)'));
            }
        ])
        // Eager load PS mappings
        ->with(['prestashopMappings' => function($q) use ($activeShops) {
            $q->whereIn('prestashop_shop_id', $activeShops);
        }])
        // Search
        ->when($this->search, fn($q) => $q->where(function($q) {
            $q->where('label', 'like', "%{$this->search}%")
              ->orWhere('code', 'like', "%{$this->search}%");
        }))
        // Filter
        ->when($this->filterStatus === 'active', fn($q) => $q->where('is_active', true))
        ->when($this->filterStatus === 'inactive', fn($q) => $q->where('is_active', false))
        ->when($this->filterStatus === 'unused', fn($q) => $q->has('variantAttributes', '=', 0))
        // Sort
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(25);
}
```

### 6.2 Bulk Delete Unused Query
```php
public function getUnusedValues(): Collection
{
    return AttributeValue::where('attribute_type_id', $this->attributeTypeId)
        ->whereDoesntHave('variantAttributes')
        ->get();
}
```

### 6.3 Merge Values Query
```php
public function mergeValues(int $targetValueId, array $sourceValueIds): void
{
    DB::transaction(function () use ($targetValueId, $sourceValueIds) {
        // Update all variant_attributes to use target
        VariantAttribute::whereIn('value_id', $sourceValueIds)
            ->update(['value_id' => $targetValueId]);

        // Delete source values
        AttributeValue::whereIn('id', $sourceValueIds)->delete();

        // Delete orphaned PS mappings
        DB::table('prestashop_attribute_value_mapping')
            ->whereIn('attribute_value_id', $sourceValueIds)
            ->delete();
    });
}
```

---

## 7. LIVEWIRE EVENTS/LISTENERS

### 7.1 Obecne
```php
// Listener (wejscie)
#[On('open-attribute-value-manager')]
public function open(int $typeId): void;

#[On('color-updated')]
public function onColorUpdated(string $color): void;

// Dispatch (wyjscie)
$this->dispatch('attribute-values-updated');
```

### 7.2 Do dodania
```php
// Nowe listenery
#[On('refresh-attribute-values')]
public function refreshValues(): void
{
    unset($this->valuesWithStats);
}

#[On('bulk-sync-completed')]
public function onBulkSyncCompleted(array $results): void
{
    $this->refreshValues();
    session()->flash('message', "Zsynchronizowano {$results['success']} / {$results['total']} wartosci");
}

// Nowe dispatche
$this->dispatch('value-created', valueId: $value->id);
$this->dispatch('value-updated', valueId: $value->id);
$this->dispatch('value-deleted', valueId: $valueId);
$this->dispatch('bulk-delete-requested', valueIds: $this->selectedValues);
$this->dispatch('merge-values-requested', targetId: $targetId, sourceIds: $sourceIds);
```

---

## 8. PLAN REFACTORINGU

### 8.1 FAZA 1: Model AttributeValuePsMapping (1h)
- [ ] Utworz `app/Models/AttributeValuePsMapping.php`
- [ ] Dodaj relacje `prestashopMappings()` do AttributeValue
- [ ] Testy jednostkowe relacji

### 8.2 FAZA 2: Wydzielenie Traits z AttributeValueManager (2h)
**Cel**: Redukcja z 453 do ~200 linii w glownym pliku

```
app/Http/Livewire/Admin/Variants/
|-- AttributeValueManager.php (~200 linii)
|-- Traits/
|   |-- AttributeValueCrud.php (~80 linii)
|   |   - openCreateModal(), openEditModal(), save(), delete()
|   |-- AttributeValueSync.php (~60 linii)
|   |   - getSyncStatusForValue(), syncValueToShop()
|   |-- AttributeValueBulkOperations.php (~50 linii)
|   |   - deleteSelected(), mergeValues(), selectAll handling
|   |-- AttributeValueFilters.php (~40 linii)
|   |   - search, filterStatus, sorting
```

### 8.3 FAZA 3: Optymalizacja Queries (1h)
- [ ] Zamien `values()` na `valuesWithStats()` z eager loading
- [ ] Usun `getSyncStatusForValue()` i `getProductsCountForValue()` z petli
- [ ] Dodaj WithPagination trait
- [ ] Przetestuj na duzych danych (>100 wartosci)

### 8.4 FAZA 4: Blade Partials (1.5h)
**Cel**: Redukcja z 497 do ~200 linii w glownym blade

```
resources/views/livewire/admin/variants/
|-- attribute-value-manager.blade.php (~200 linii)
|-- partials/
|   |-- value-list-item.blade.php (~60 linii)
|   |-- value-edit-form.blade.php (~80 linii)
|   |-- products-using-modal.blade.php (~60 linii)
|   |-- sync-status-modal.blade.php (~110 linii)
|   |-- bulk-actions-bar.blade.php (~30 linii)
```

### 8.5 FAZA 5: Bulk Operations UI (1h)
- [ ] Checkboxy dla multi-select
- [ ] Bulk action dropdown (Delete, Merge, Sync All)
- [ ] Confirmation modals

### 8.6 FAZA 6: Search/Filter/Sort (1h)
- [ ] Search input z debounce
- [ ] Filter dropdown (All, Active, Inactive, Unused)
- [ ] Sortable column headers
- [ ] QueryString dla URL persistence

---

## 9. BLADE PARTIALS DO UTWORZENIA

### 9.1 `partials/value-list-item.blade.php`
```blade
{{-- Value Row Component --}}
@props(['value', 'syncStatuses', 'isColorType'])

<div wire:key="attr-value-{{ $value->id }}"
     class="value-row-enhanced">

    {{-- Bulk Selection Checkbox --}}
    <div class="flex-shrink-0">
        <input type="checkbox"
               wire:model.live="selectedValues"
               value="{{ $value->id }}"
               class="checkbox-enterprise">
    </div>

    {{-- Value Info --}}
    <div class="flex items-center gap-4 flex-1">
        @if($isColorType && $value->color_hex)
            <div class="w-10 h-10 rounded-lg border-2 border-gray-600"
                 style="background-color: {{ $value->color_hex }}"></div>
        @endif

        <div class="flex-1">
            <h4 class="font-semibold text-gray-200">{{ $value->label }}</h4>
            <p class="text-xs text-gray-400 font-mono">
                Code: {{ $value->code }}
                @if($value->color_hex)
                    <span class="ml-2">| {{ $value->color_hex }}</span>
                @endif
            </p>

            {{-- Sync Status Badges (from eager loaded data) --}}
            <div class="value-sync-status-row mt-1">
                @foreach($value->prestashopMappings as $mapping)
                    @include('livewire.admin.variants.partials.sync-badge', ['mapping' => $mapping])
                @endforeach
            </div>
        </div>

        {{-- Stats Badges --}}
        <span class="badge-enterprise">
            {{ $value->is_active ? 'Active' : 'Inactive' }}
        </span>

        <span class="products-count-badge">
            {{ $value->variants_count ?? 0 }} wariantow
        </span>
    </div>

    {{-- Actions --}}
    <div class="sync-actions">
        <button wire:click="openProductsModal({{ $value->id }})"
                class="btn-enterprise-sm">
            Produkty
        </button>
        <button wire:click="openSyncModal({{ $value->id }})"
                class="btn-enterprise-sm">
            Sync
        </button>
        <button wire:click="openEditModal({{ $value->id }})"
                class="btn-enterprise-sm">
            Edit
        </button>
        <button wire:click="delete({{ $value->id }})"
                wire:confirm="Czy na pewno usunac?"
                class="btn-enterprise-sm btn-enterprise-danger">
            Delete
        </button>
    </div>
</div>
```

### 9.2 `partials/bulk-actions-bar.blade.php`
```blade
{{-- Bulk Actions Bar --}}
@if(count($selectedValues) > 0)
<div class="bulk-actions-bar">
    <span class="text-sm text-gray-300">
        Zaznaczono: {{ count($selectedValues) }}
    </span>

    <div class="flex gap-2">
        <button wire:click="bulkDelete"
                wire:confirm="Usunac {{ count($selectedValues) }} wartosci?"
                class="btn-enterprise-sm btn-enterprise-danger">
            Usun zaznaczone
        </button>

        <button wire:click="openMergeModal"
                class="btn-enterprise-sm"
                @if(count($selectedValues) < 2) disabled @endif>
            Scalm wartosci
        </button>

        <button wire:click="bulkSync"
                class="btn-enterprise-sm">
            Sync zaznaczone
        </button>

        <button wire:click="clearSelection"
                class="btn-enterprise-sm btn-enterprise-secondary">
            Odznacz
        </button>
    </div>
</div>
@endif
```

### 9.3 `partials/filter-bar.blade.php`
```blade
{{-- Search and Filter Bar --}}
<div class="filter-bar">
    {{-- Search --}}
    <div class="flex-1 max-w-xs">
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Szukaj po nazwie lub kodzie..."
               class="form-input-enterprise w-full">
    </div>

    {{-- Status Filter --}}
    <select wire:model.live="filterStatus"
            class="form-input-enterprise">
        <option value="all">Wszystkie</option>
        <option value="active">Aktywne</option>
        <option value="inactive">Nieaktywne</option>
        <option value="unused">Nieuzywane</option>
    </select>

    {{-- Sort --}}
    <select wire:model.live="sortField"
            class="form-input-enterprise">
        <option value="position">Kolejnosc</option>
        <option value="label">Nazwa</option>
        <option value="code">Kod</option>
        <option value="created_at">Data utworzenia</option>
    </select>

    <button wire:click="toggleSortDirection"
            class="btn-enterprise-sm">
        {{ $sortDirection === 'asc' ? 'ASC' : 'DESC' }}
    </button>
</div>
```

---

## 10. SZACOWANY CZAS IMPLEMENTACJI

| Faza | Opis | Czas |
|------|------|------|
| 1 | Model AttributeValuePsMapping | 1h |
| 2 | Traits extraction | 2h |
| 3 | Query optimization | 1h |
| 4 | Blade partials | 1.5h |
| 5 | Bulk operations UI | 1h |
| 6 | Search/Filter/Sort | 1h |
| **TOTAL** | | **7.5h** |

---

## 11. PLIKI DO UTWORZENIA/MODYFIKACJI

### Nowe pliki
- `app/Models/AttributeValuePsMapping.php`
- `app/Http/Livewire/Admin/Variants/Traits/AttributeValueCrud.php`
- `app/Http/Livewire/Admin/Variants/Traits/AttributeValueSync.php`
- `app/Http/Livewire/Admin/Variants/Traits/AttributeValueBulkOperations.php`
- `app/Http/Livewire/Admin/Variants/Traits/AttributeValueFilters.php`
- `resources/views/livewire/admin/variants/partials/value-list-item.blade.php`
- `resources/views/livewire/admin/variants/partials/value-edit-form.blade.php`
- `resources/views/livewire/admin/variants/partials/products-using-modal.blade.php`
- `resources/views/livewire/admin/variants/partials/sync-status-modal.blade.php`
- `resources/views/livewire/admin/variants/partials/bulk-actions-bar.blade.php`
- `resources/views/livewire/admin/variants/partials/filter-bar.blade.php`

### Modyfikacje
- `app/Models/AttributeValue.php` - dodanie relacji `prestashopMappings()`
- `app/Http/Livewire/Admin/Variants/AttributeValueManager.php` - refaktoring do traits
- `resources/views/livewire/admin/variants/attribute-value-manager.blade.php` - podpial partials

---

## 12. PODSUMOWANIE

### Obecny stan
- Komponent dziala, ale ma problemy wydajnosciowe (N+1 queries)
- Przekracza limit linii (453 PHP, 497 Blade)
- Brak paginacji, search, filter, bulk operations
- Brak modelu dla prestashop_attribute_value_mapping (raw DB queries)

### Po refaktoringu
- Optymalne queries z eager loading
- Zgodnosc z CLAUDE.md (<300 linii per file)
- Pelna funkcjonalnosc UX: search, filter, sort, pagination
- Bulk operations: delete, merge, sync
- Modularna architektura (traits + partials)

---

## NASTEPNE KROKI
1. Zatwierdzenie planu przez uzytkownika
2. Rozpoczecie od FAZA 1 (Model)
3. Testy na kazdym etapie
4. Deploy po kazdej fazie

---
**Autor raportu**: livewire-specialist agent
**Weryfikacja Context7**: Livewire 3.x patterns verified via `/livewire/livewire`
