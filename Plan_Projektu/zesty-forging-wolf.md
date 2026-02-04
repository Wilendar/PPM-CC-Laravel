# ETAP_09.2: ERP → PPM Warehouse & Price Group Mapping ✅ COMPLETED

**Status:** ✅ Ukończone (2026-01-21)
**Zweryfikowano:** Chrome DevTools MCP - UI działa prawidłowo

---

# ETAP_09.3: Price Groups Panel Modernization & ERP Create Feature

**Status:** ❌ Nie rozpoczęte
**Poprzedni etap:** ETAP_09.2 (ERP Mapping UI)

## Cel

Modernizacja panelu Price Groups (`/admin/price-management/price-groups`) oraz rozszerzenie ERP Integration o możliwość tworzenia nowych magazynów i grup cenowych z danych ERP.

---

## ODKRYCIA Z EKSPLORACJI

### Panel Price Groups - STAN FAKTYCZNY:
- **CRUD ISTNIEJE** - 518 linii PHP, 706 linii Blade
- **Pełna funkcjonalność:** Lista, Dodawanie, Edycja, Usuwanie, Status toggling
- **PROBLEM:** ~15-20 naruszeń `PPM_Styling_Playbook.md`

### Naruszenia stylistyczne (do naprawy):
| Linia | Problem | Rozwiązanie |
|-------|---------|-------------|
| L6 | `style="color: #e0ac7e;"` | `.text-mpp-primary` |
| L42, L70, L98, L126 | `style="color: #..."` | CSS classes |
| L385 | `z-[9999]` | `.layer-modal` |
| L586 | `z-[9999]` | `.layer-modal` |
| L680-705 | Bootstrap `.toast` | Enterprise component |

### Panel ERP Integration - BRAKUJE:
1. **Tworzenie magazynu z ERP** - podczas mapowania user nie może utworzyć nowego magazynu PPM
2. **Tworzenie grupy cenowej z ERP** - j.w.
3. **"Replace existing"** - brak opcji wyczyszczenia poprzednich mapowań

---

## PLAN IMPLEMENTACJI

### FAZA A: Price Groups Panel - Styling Fixes (1h)

#### A.1: Usunięcie inline styles
```php
// PRZED:
<span style="color: #e0ac7e;">Grupy cenowe</span>

// PO:
<span class="text-mpp-primary">Grupy cenowe</span>
```

#### A.2: Zamiana z-[9999] na layer-modal
```blade
{{-- PRZED: --}}
<div class="z-[9999]">

{{-- PO: --}}
<div class="layer-modal">
```

#### A.3: Zamiana Bootstrap toast na enterprise component
```blade
{{-- PRZED: --}}
<div class="toast toast-success">...</div>

{{-- PO: --}}
<div x-data="{ show: false }"
     @notify.window="show = true; setTimeout(() => show = false, 3000)">
    <div x-show="show" class="enterprise-notification enterprise-notification--success">
        ...
    </div>
</div>
```

**Pliki:**
- `resources/views/livewire/admin/price-management/price-groups.blade.php`
- `resources/css/admin/components.css` (dodać `.enterprise-notification` jeśli brak)

---

### FAZA B: Model Layer - Create From ERP Methods (45 min)

#### B.1: Warehouse.php - createFromErpData()
```php
/**
 * Tworzy nowy magazyn PPM na podstawie danych z ERP
 */
public static function createFromErpData(
    string $erpType,
    array $erpWarehouseData,
    int $connectionId
): self {
    $warehouse = self::create([
        'name' => $erpWarehouseData['name'] ?? 'Magazyn z ERP',
        'code' => $erpWarehouseData['symbol'] ?? null,
        'is_active' => true,
        'erp_mapping' => [
            $erpType => [
                'connection_id' => $connectionId,
                'erp_warehouse_ids' => [$erpWarehouseData['id']],
                'aggregation_mode' => 'sum',
                'created_from_erp' => true,
                'created_at' => now()->toISOString(),
            ]
        ]
    ]);

    return $warehouse;
}
```

#### B.2: PriceGroup.php - createFromErpData()
```php
/**
 * Tworzy nową grupę cenową PPM na podstawie danych z ERP
 */
public static function createFromErpData(
    string $erpType,
    array $erpPriceLevelData,
    int $connectionId
): self {
    $priceGroup = self::create([
        'name' => $erpPriceLevelData['name'] ?? 'Poziom cenowy z ERP',
        'code' => 'ERP_' . $erpPriceLevelData['id'],
        'is_active' => true,
        'erp_mapping' => [
            $erpType => [
                'connection_id' => $connectionId,
                'erp_price_level_id' => $erpPriceLevelData['id'],
                'created_from_erp' => true,
                'created_at' => now()->toISOString(),
            ]
        ]
    ]);

    return $priceGroup;
}
```

**Pliki:**
- `app/Models/Warehouse.php`
- `app/Models/PriceGroup.php`

---

### FAZA C: ERPManager Backend - Create Methods (1h)

#### C.1: Nowe properties
```php
public bool $clearExistingMappingsOnSave = false;
```

#### C.2: createWarehouseFromErp()
```php
public function createWarehouseFromErp(int $erpWarehouseId): void
{
    $erpWarehouse = collect($this->availableErpWarehouses)
        ->firstWhere('id', $erpWarehouseId);

    if (!$erpWarehouse) {
        $this->addError('warehouse', 'Nie znaleziono magazynu ERP');
        return;
    }

    $warehouse = Warehouse::createFromErpData(
        $this->erpType,
        $erpWarehouse,
        $this->erpConnectionId
    );

    // Odśwież listę magazynów PPM
    $this->loadPpmWarehouses();

    // Auto-przypisz mapowanie
    $this->warehouseMappings[$warehouse->id] = [$erpWarehouseId];

    $this->dispatch('notify', type: 'success', message: "Utworzono magazyn: {$warehouse->name}");
}
```

#### C.3: createPriceGroupFromErp()
```php
public function createPriceGroupFromErp(int $erpPriceLevelId): void
{
    $erpPriceLevel = collect($this->availableErpPriceLevels)
        ->firstWhere('id', $erpPriceLevelId);

    if (!$erpPriceLevel) {
        $this->addError('priceGroup', 'Nie znaleziono poziomu cenowego ERP');
        return;
    }

    $priceGroup = PriceGroup::createFromErpData(
        $this->erpType,
        $erpPriceLevel,
        $this->erpConnectionId
    );

    // Odśwież listę grup cenowych PPM
    $this->loadPpmPriceGroups();

    // Auto-przypisz mapowanie
    $this->priceGroupMappings[$priceGroup->id] = $erpPriceLevelId;

    $this->dispatch('notify', type: 'success', message: "Utworzono grupę cenową: {$priceGroup->name}");
}
```

#### C.4: Rozszerzenie saveMappings() o clearExisting
```php
public function saveMappings(): void
{
    if ($this->clearExistingMappingsOnSave) {
        // Wyczyść poprzednie mapowania dla tego połączenia ERP
        Warehouse::where('erp_mapping->' . $this->erpType . '->connection_id', $this->erpConnectionId)
            ->each(fn($w) => $w->clearErpMapping($this->erpType));

        PriceGroup::where('erp_mapping->' . $this->erpType . '->connection_id', $this->erpConnectionId)
            ->each(fn($pg) => $pg->clearErpMapping($this->erpType));
    }

    // ... reszta logiki zapisu (istniejąca)
}
```

**Pliki:**
- `app/Http/Livewire/Admin/ERP/ERPManager.php`

---

### FAZA D: UI - Create From ERP Buttons (1.5h)

#### D.1: Sekcja mapowania magazynów - przycisk "Utwórz nowy"
```blade
{{-- Dla każdego magazynu ERP bez mapowania --}}
@foreach($availableErpWarehouses as $erpWarehouse)
    <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg">
        <div class="flex-1">
            <span class="font-medium text-white">{{ $erpWarehouse['name'] }}</span>
            <span class="text-xs text-gray-500">(ID: {{ $erpWarehouse['id'] }})</span>
        </div>

        {{-- Dropdown: wybierz istniejący PPM --}}
        <select wire:model.live="warehouseMappings.{{ $erpWarehouse['id'] }}"
                class="form-select-enterprise w-48">
            <option value="">-- Nie mapuj --</option>
            @foreach($ppmWarehouses as $ppmWarehouse)
                <option value="{{ $ppmWarehouse->id }}">{{ $ppmWarehouse->name }}</option>
            @endforeach
        </select>

        {{-- LUB: Utwórz nowy --}}
        <button type="button"
                wire:click="createWarehouseFromErp({{ $erpWarehouse['id'] }})"
                class="btn-enterprise-secondary btn-enterprise-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Utwórz nowy
        </button>
    </div>
@endforeach
```

#### D.2: Sekcja mapowania cen - przycisk "Utwórz nowy"
```blade
{{-- Analogicznie dla poziomów cenowych --}}
@foreach($availableErpPriceLevels as $erpLevel)
    <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg">
        <div class="flex-1">
            <span class="font-medium text-white">{{ $erpLevel['name'] }}</span>
            <span class="text-xs text-gray-500">(Level {{ $erpLevel['id'] }})</span>
        </div>

        <select wire:model.live="priceGroupMappings.{{ $erpLevel['id'] }}"
                class="form-select-enterprise w-48">
            <option value="">-- Nie mapuj --</option>
            @foreach($ppmPriceGroups as $ppmGroup)
                <option value="{{ $ppmGroup->id }}">{{ $ppmGroup->name }}</option>
            @endforeach
        </select>

        <button type="button"
                wire:click="createPriceGroupFromErp({{ $erpLevel['id'] }})"
                class="btn-enterprise-secondary btn-enterprise-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Utwórz nowy
        </button>
    </div>
@endforeach
```

#### D.3: Toggle "Wyczyść poprzednie mapowania"
```blade
{{-- Na górze sekcji mapowań --}}
<div class="flex items-center gap-3 p-4 bg-yellow-900/20 border border-yellow-700/50 rounded-lg mb-6">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox"
               wire:model.live="clearExistingMappingsOnSave"
               class="checkbox-enterprise">
        <span class="text-sm text-yellow-200">
            Wyczyść poprzednie mapowania przed zapisem
        </span>
    </label>
    <div class="text-xs text-yellow-400">
        (Uwaga: Usunie wszystkie istniejące powiązania ERP dla tego połączenia)
    </div>
</div>
```

**Pliki:**
- `resources/views/livewire/admin/erp/erp-manager.blade.php`

---

## KLUCZOWE PLIKI DO MODYFIKACJI

| Plik | Opis zmian |
|------|------------|
| `resources/views/livewire/admin/price-management/price-groups.blade.php` | ~15 inline style fixes, z-index, toast |
| `app/Models/Warehouse.php` | `createFromErpData()`, `clearErpMapping()` |
| `app/Models/PriceGroup.php` | `createFromErpData()`, `clearErpMapping()` |
| `app/Http/Livewire/Admin/ERP/ERPManager.php` | Create methods, clearExisting toggle |
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | UI buttons "Utwórz nowy", toggle |
| `resources/css/admin/components.css` | `.enterprise-notification` (jeśli brak) |

---

## WERYFIKACJA

### Test 1: Price Groups Styling
1. Przejdź do `/admin/price-management/price-groups`
2. Otwórz Chrome DevTools → Elements
3. Sprawdź brak `style="..."` w elementach
4. Sprawdź brak `z-[9999]` w klasach
5. Przetestuj notyfikacje (dodaj/edytuj grupę)

### Test 2: Create Warehouse From ERP
1. Przejdź do `/admin/integrations`
2. Edytuj połączenie Subiekt GT → Step 4
3. Znajdź magazyn ERP bez mapowania
4. Kliknij "Utwórz nowy"
5. Sprawdź czy magazyn pojawił się w dropdown
6. Sprawdź bazę danych: `warehouses.erp_mapping`

### Test 3: Create Price Group From ERP
1. Ten sam flow dla poziomów cenowych
2. Sprawdź `price_groups.erp_mapping`

### Test 4: Clear Existing Mappings
1. Utwórz kilka mapowań
2. Zaznacz "Wyczyść poprzednie mapowania"
3. Zapisz
4. Sprawdź że poprzednie mapowania zostały usunięte

### Chrome DevTools Verification
```javascript
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/price-management/price-groups" })
mcp__claude-in-chrome__javascript_tool({
  tabId: TAB_ID, action: "javascript_exec",
  text: "document.querySelectorAll('[style]').length"  // Should be 0 or minimal
})
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

---

## SZACOWANY CZAS

| Faza | Czas |
|------|------|
| A: Price Groups Styling | 1h |
| B: Model Layer | 45 min |
| C: ERPManager Backend | 1h |
| D: UI Buttons | 1.5h |
| Testowanie | 30 min |
| **RAZEM** | **4-5h** |

---

## PODSUMOWANIE ZMIAN

### Panel Price Groups:
- ✅ CRUD już istnieje (brak implementacji potrzebnej)
- ❌ Wymaga naprawy stylistycznej (~15 zmian)

### Panel ERP Integration:
- ✅ Mapowanie ERP→PPM działa (ETAP_09.2)
- ❌ Brak "Utwórz nowy magazyn/grupę z ERP"
- ❌ Brak "Wyczyść poprzednie mapowania"
