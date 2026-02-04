# PLAN: IMPORT PANEL - FAZA 9.7b (Bugfixes + PrestaShop Categories)

**Status:** ✅ UKONCZONE (F+G+H) 2026-02-04
**Priorytet:** WYSOKI
**Kontekst:** 8 bugfixow + feature kategorii PrestaShop
**Poprzedni plan:** FAZA 9.7 (UKONCZONE 2026-02-03)

---

## PODSUMOWANIE ZMIAN (8 punktow + 1 feature)

| # | Typ | Opis | Grupa |
|---|-----|------|-------|
| 1 | Bug | Drag-fill handle nie podswietla komorek docelowych | F |
| 2 | Bug | CSV upload powoduje blad 500 | F |
| 3 | Bug | Dropdown publikacji przyciety do wysokosci okna | G |
| 4 | Bug | Modal edycji produktu nie otwiera sie z listy | G |
| 5 | Bug | Modal cen: brak auto-przeliczania netto/brutto + brak switch | G |
| 6 | Bug | Przycisk "+" do kategorii nie dziala | F |
| 7 | Bug | PrestaShop wybor zamyka dropdown (event propagation) | G |
| 8 | Feature | Kategorie PrestaShop per shop w kolumnie publikacji | H |

---

<!-- FAZA 9.7 (poprzednia) - UKONCZONA 2026-02-03 - szczegoly w git history -->

## GRUPA F: MODAL IMPORT - BUGFIXES

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalColumnModeTrait.php`
- `resources/views/livewire/products/import/modals/partials/column-mode.blade.php`

**Implementacja:**

1. **Zamiana const na metode** (ImportModalColumnModeTrait.php):
```php
// USUN: public const AVAILABLE_COLUMNS = [...];

// NOWE:
public static array $baseColumns = [
    'product_type_id' => ['label' => 'Typ produktu', 'type' => 'dropdown'],
    'supplier_code'   => ['label' => 'Kod Dostawcy', 'type' => 'input'],
    'supplier_id'     => ['label' => 'Dostawca', 'type' => 'dropdown'],
    'manufacturer_id' => ['label' => 'Producent', 'type' => 'dropdown'],
    'importer_id'     => ['label' => 'Importer', 'type' => 'dropdown'],
    'ean'             => ['label' => 'EAN', 'type' => 'input'],
    'cn_code'         => ['label' => 'Kod CN', 'type' => 'input'],
    'material'        => ['label' => 'Material', 'type' => 'input'],
    'defect_symbol'   => ['label' => 'Symbol z wada', 'type' => 'input'],
    'application'     => ['label' => 'Zastosowanie', 'type' => 'input'],
];

public function getAvailableColumns(): array
{
    $columns = self::$baseColumns;

    // Dynamiczne kolumny cenowe z PriceGroup
    $priceGroups = \App\Models\PriceGroup::where('is_active', true)
        ->orderBy('sort_order')->get();

    foreach ($priceGroups as $group) {
        $key = 'price_group_' . $group->id;
        $columns[$key] = [
            'label' => $group->name,
            'type' => 'price',  // nowy typ!
            'price_group_id' => $group->id,
            'price_group_code' => $group->code,
        ];
    }

    return $columns;
}
```

2. **Property dla trybu cen** (ImportModalColumnModeTrait.php):
```php
public string $priceDisplayMode = 'net'; // 'net' | 'gross'

public function togglePriceDisplayMode(): void
{
    $this->priceDisplayMode = $this->priceDisplayMode === 'net' ? 'gross' : 'net';
}
```

3. **Nowy typ kolumny 'price' w blade** (column-mode.blade.php):
```blade
@if($colType === 'price')
    <input type="number"
           wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
           class="form-input-dark-sm w-full font-mono text-right"
           step="0.01" min="0"
           placeholder="{{ $priceDisplayMode === 'net' ? 'Netto' : 'Brutto' }}"
           autocomplete="off">
@endif
```

4. **Switch netto/brutto w naglowku** (column-mode.blade.php):
```blade
@if($colType === 'price')
    <th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 min-w-[120px]">
        <div class="flex items-center gap-1.5">
            <span class="truncate">{{ $colLabel }}</span>
            <button type="button" wire:click="togglePriceDisplayMode"
                    class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors
                    {{ $priceDisplayMode === 'net'
                        ? 'bg-blue-900/50 text-blue-300 border border-blue-600'
                        : 'bg-gray-700 text-gray-400 border border-gray-600' }}">
                {{ $priceDisplayMode === 'net' ? 'NETTO' : 'BRUTTO' }}
            </button>
        </div>
    </th>
@endif
```

5. **Zapis cen do price_data** (w doImport):
```php
// Konwersja rows z price_group_X na price_data JSON
$priceData = ['groups' => []];
foreach ($row as $key => $value) {
    if (str_starts_with($key, 'price_group_') && $value !== '' && $value !== null) {
        $groupId = (int) str_replace('price_group_', '', $key);
        $netPrice = $this->priceDisplayMode === 'net'
            ? (float)$value
            : (float)$value / 1.23; // TODO: uzyc VAT produktu
        $grossPrice = $this->priceDisplayMode === 'gross'
            ? (float)$value
            : (float)$value * 1.23;
        $priceData['groups'][$groupId] = [
            'net' => round($netPrice, 2),
            'gross' => round($grossPrice, 2),
        ];
    }
}
```

6. **Update blade - zamiana AVAILABLE_COLUMNS na getAvailableColumns()**:
Wszystkie odwolania `defined('static::AVAILABLE_COLUMNS') ? static::AVAILABLE_COLUMNS : []`
zamienic na `$this->getAvailableColumns()` (przekazane z render() do blade).

**UWAGA:** Blade nie moze wywolywac metod komponentu bezposrednio.
Przekazac w render(): `'availableColumns' => $this->getAvailableColumns()`

---

## GRUPA B: COLUMN MODE - COPY-DOWN + DRAG-FILL + SAVED LAYOUT

### B1: Copy-down Button w Naglowku Kolumny

**Problem:** Dropdown kolumny (Typ, Dostawca, Producent, Importer) wymagaja recznego
wypelnienia kazdego wiersza. Potrzeba: przycisk kopiujacy wartosc z wiersza 1 na pozostale.

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalColumnModeTrait.php`
- `resources/views/livewire/products/import/modals/partials/column-mode.blade.php`

**Implementacja:**

1. **Metoda Livewire** (ImportModalColumnModeTrait.php):
```php
public function copyDownColumn(string $columnKey): void
{
    if (empty($this->rows)) return;

    $sourceValue = $this->rows[0][$columnKey] ?? '';
    if ($sourceValue === '' || $sourceValue === null) return;

    foreach ($this->rows as $i => &$row) {
        if ($i === 0) continue; // Pomin zrodlo
        $row[$columnKey] = $sourceValue;
    }
}
```

2. **Przycisk w naglowku kolumny** (column-mode.blade.php):
```blade
<th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 min-w-[150px]">
    <div class="flex items-center gap-1">
        <span>{{ $colLabel }}</span>
        @if(in_array($colType, ['dropdown', 'price']))
            <button wire:click="copyDownColumn('{{ $colKey }}')"
                    class="p-0.5 text-gray-600 hover:text-amber-400 transition-colors"
                    title="Kopiuj wartosc z wiersza 1 na wszystkie">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </button>
        @endif
    </div>
</th>
```

---

### B2: Drag-fill (Excel-like)

**Problem:** Brak mozliwosci przeciagania wartosci z komorki na inne (drag corner).

**Zlozonosc:** WYSOKA - wymaga custom Alpine.js komponentu z mouse events.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/modals/partials/column-mode.blade.php`
- `resources/css/products/import-panel.css` (nowe style)

**Implementacja (Alpine.js):**

1. **Drag handle w kazdej komorce** (column-mode.blade.php):
```blade
<td class="px-2 py-1.5 relative group/cell" data-col-key="{{ $colKey }}" data-row-index="{{ $rowIndex }}">
    <!-- input/select -->
    <div class="import-drag-handle"
         x-on:mousedown.prevent="startDragFill($event, {{ $rowIndex }}, '{{ $colKey }}')"
         title="Przeciagnij aby wypelnic">
    </div>
</td>
```

2. **Alpine drag-fill logic:**
```javascript
startDragFill(event, sourceRow, colKey) {
    this.dragSource = { row: sourceRow, col: colKey };
    this.isDragging = true;
    // ... mousemove + mouseup listeners
},
endDragFill(event) {
    if (!this.dragSource) return;
    const targetRow = event.target.closest('tr')?.dataset?.rowIndex;
    if (targetRow !== undefined) {
        const start = Math.min(this.dragSource.row, targetRow);
        const end = Math.max(this.dragSource.row, targetRow);
        $wire.fillColumnRange(this.dragSource.col, this.dragSource.row, start, end);
    }
    this.isDragging = false;
}
```

3. **Metoda Livewire:**
```php
public function fillColumnRange(string $colKey, int $sourceRow, int $startRow, int $endRow): void
{
    $value = $this->rows[$sourceRow][$colKey] ?? '';
    for ($i = $startRow; $i <= $endRow; $i++) {
        if (isset($this->rows[$i])) {
            $this->rows[$i][$colKey] = $value;
        }
    }
}
```

4. **CSS dla drag handle** (import-panel.css):
```css
.import-drag-handle {
    position: absolute; bottom: 0; right: 0;
    width: 8px; height: 8px;
    background: rgb(59, 130, 246);
    cursor: crosshair;
    opacity: 0;
    transition: opacity 100ms;
}
.group\/cell:hover .import-drag-handle { opacity: 1; }
```

---

### B3: Zapisywanie Ukladu Kolumn Per User

**Problem:** activeColumns resetuja sie po zamknieciu modalu.

**Rozwiazanie:** Nowa migracja - pole `import_column_preferences` (JSON) w tabeli `users`.

**Pliki do zmiany:**
- Nowa migracja: `add_import_column_preferences_to_users`
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalColumnModeTrait.php`
- `app/Models/User.php` (dodac do casts)

**Implementacja:**

1. **Migracja:**
```php
Schema::table('users', function (Blueprint $table) {
    $table->json('import_column_preferences')->nullable()
        ->after('dashboard_widget_preferences')
        ->comment('Saved column layout for import modal');
});
```

2. **Struktura JSON:**
```json
{
    "active_columns": ["product_type_id", "manufacturer_id", "price_group_1", "ean"],
    "price_display_mode": "net",
    "updated_at": "2026-02-02T10:30:00Z"
}
```

3. **Load/Save w trait:**
```php
public function loadSavedColumnLayout(): void
{
    $prefs = Auth::user()->import_column_preferences ?? [];
    if (!empty($prefs['active_columns'])) {
        $available = array_keys($this->getAvailableColumns());
        $this->activeColumns = array_intersect($prefs['active_columns'], $available);
    }
    $this->priceDisplayMode = $prefs['price_display_mode'] ?? 'net';
}

public function saveColumnLayout(): void
{
    Auth::user()->update([
        'import_column_preferences' => [
            'active_columns' => $this->activeColumns,
            'price_display_mode' => $this->priceDisplayMode,
            'updated_at' => now()->toISOString(),
        ],
    ]);
}
```

4. Wywolanie `loadSavedColumnLayout()` w `initColumnMode()`.
5. Wywolanie `saveColumnLayout()` w `addColumn()`, `removeColumn()`, `togglePriceDisplayMode()`.

---

## GRUPA C: CSV MODE FIXES

### C1: File Upload Fix (Bug #5)

**Problem:** Drag & drop i wybranie pliku CSV nie dziala. Plik nie jest odczytywany.

**Plik testowy:** `D:\OneDrive - MPP TRADE\Exporty CSV\B2B_Export_23-01-2026.csv`

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalCsvModeTrait.php`
- `resources/views/livewire/products/import/modals/partials/csv-mode.blade.php`

**Diagnoza do przeprowadzenia:**
1. Sprawdzic czy `wire:model="csvFile"` poprawnie binduje do Livewire
2. Sprawdzic czy `WithFileUploads` trait jest uzywany w komponencie
3. Sprawdzic czy `parseCsvData()` poprawnie rozpoznaje `$this->csvFile`
4. Sprawdzic czy `uploadCsvFile()` poprawnie parsuje plik (encoding, BOM, separator)
5. Przetestowac z plikiem testowym - odczytac jego format (separator, encoding, headers)

**Prawdopodobne przyczyny:**
- Brak `use Livewire\WithFileUploads` w ProductImportModal
- `csvFile` property nie jest poprawnie typowany
- `uploadCsvFile()` nie obsluguje encoding UTF-8 BOM
- Brak validation rules dla pliku

**Fix plan:**
1. Dodac `WithFileUploads` trait (jesli brak)
2. Poprawic `uploadCsvFile()` - BOM handling, encoding detection
3. Dodac `updatedCsvFile()` hook - auto-trigger parsowania po uplynadzie pliku
4. Przetestowac z plikiem CSV uzytkownika

---

### C2: Semicolon 500 Error Fix (Bug #6)

**Problem:** Wklejenie tekstu ze srednikami powoduje 500 error.

**Przyczyna (z eksploracji):** Brak try-catch w `applyCsvAutoMapping()`.
Jesli `ColumnMappingService::guessColumnMapping()` rzuci exception → 500.

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalCsvModeTrait.php`

**Fix:**
1. Dodac try-catch w `applyCsvAutoMapping()` z fallback do recznego mapowania
2. Dodac walidacje minimalnej liczby kolumn (>= 2)
3. Dodac walidacje pustych wierszy
4. Sprawdzic czy `ColumnMappingService` poprawnie obsluguje polskie naglowki ze srednikami

---

### C3: goBackToCsvInput MethodNotFoundException (Bug #7)

**Problem:** Klikniecie "Wstecz" w kroku mapowania powoduje MethodNotFoundException.

**Status z eksploracji:** Metoda `goBackToCsvInput()` ISTNIEJE (linia 561-565 trait).
Mozliwe przyczyny:
- Metoda nie zostala wdrozona na produkcje (brak deployment po FAZA 9 BUGFIX)
- Livewire cache: `php artisan view:clear && cache:clear`

**Fix:**
1. Zweryfikowac deployment - czy plik trait jest aktualny na produkcji
2. Jesli tak - sprawdzic czy metoda jest `public`
3. Clear cache na produkcji

---

## GRUPA D: PUBLIKACJA - ERP INTEGRATION

### D1: ERP w Dropdown Publikacji (Feature #8)

**Problem:** Dropdown publikacji pokazuje "PPM" jako ERP primary.
PPM NIE powinien byc na liscie (zawsze jest domyslnym storage).
Zamiast tego: Subiekt GT, Baselinker jako oddzielne checkboxy.

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Traits/ImportPanelPublicationTrait.php`
- `app/Services/Import/PublicationTargetService.php`
- `resources/views/livewire/products/import/partials/product-row.blade.php`
- `config/import.php`

**Implementacja:**

1. **Zmiana publication_targets JSON** (PendingProduct):
```json
// PRZED:
{ "erp_primary": true, "prestashop_shops": [1, 3] }

// PO:
{
    "erp_connections": [5, 8],  // ERPConnection IDs (default ERP zawsze wlaczony)
    "prestashop_shops": [1, 3]
}
```

2. **Nowe metody w ImportPanelPublicationTrait:**
```php
// Zamiana toggleErpPrimary() na:
public function toggleErpConnection(int $productId, int $connectionId): void
{
    $product = PendingProduct::findOrFail($productId);
    $targets = $product->publication_targets ?? [];
    $connections = $targets['erp_connections'] ?? [];

    // Default ERP - nie mozna wylaczyc
    $defaultErp = ERPConnection::where('is_default', true)->first();
    if ($defaultErp && $defaultErp->id === $connectionId) {
        return; // Zablokuj wylaczenie domyslnego
    }

    if (in_array($connectionId, $connections)) {
        $connections = array_values(array_diff($connections, [$connectionId]));
    } else {
        $connections[] = $connectionId;
    }

    $targets['erp_connections'] = $connections;
    $product->publication_targets = $targets;
    $product->save();
}
```

3. **Dropdown blade** (product-row.blade.php):
```blade
@php
    $erpConnections = \App\Models\ERPConnection::where('is_active', true)
        ->orderBy('priority')->orderBy('instance_name')->get();
    $defaultErp = $erpConnections->firstWhere('is_default', true);
    $activeErpIds = $pubTargets['erp_connections'] ??
        ($defaultErp ? [$defaultErp->id] : []);
@endphp

{{-- ERP Connections --}}
@foreach($erpConnections as $erp)
    <label class="import-targets-dropdown-item {{ $erp->is_default ? 'opacity-70' : '' }}">
        <input type="checkbox"
               @checked(in_array($erp->id, $activeErpIds))
               wire:click="toggleErpConnection({{ $product->id }}, {{ $erp->id }})"
               class="form-checkbox-dark w-3.5 h-3.5"
               @if($erp->is_default) disabled @endif>
        <span>
            {{ $erp->instance_name }}
            @if($erp->is_default)
                <span class="text-amber-400 text-[10px] font-bold ml-1">DOMYSLNY</span>
            @endif
        </span>
    </label>
@endforeach

<div class="border-t border-gray-700 my-1"></div>

{{-- PrestaShop Shops --}}
@foreach($allShops as $shop)
    ...
@endforeach
```

4. **Badge'e w tabeli** - zamiana "PPM" badge na nazwe domyslnego ERP:
```blade
{{-- ERP badges --}}
@foreach($erpConnections->whereIn('id', $activeErpIds) as $erp)
    <span class="import-publication-badge import-publication-badge-erp">
        {{ Str::limit($erp->instance_name, 12) }}
        @if($erp->is_default) * @endif
    </span>
@endforeach
```

---

### D2: Domyslny ERP w /admin/integrations (Feature #9)

**Problem:** Brak "Set as default ERP" toggle w konfiguracji ERP.

**Pliki do zmiany:**
- Nowa migracja: `add_is_default_to_erp_connections`
- `app/Models/ERPConnection.php`
- `app/Http/Livewire/Admin/ERP/ERPManager.php`
- `resources/views/livewire/admin/erp/erp-manager.blade.php`

**Implementacja:**

1. **Migracja:**
```php
Schema::table('erp_connections', function (Blueprint $table) {
    $table->boolean('is_default')->default(false)
        ->after('is_active')
        ->comment('Default ERP - always enabled for new products');
});
```

2. **Model ERPConnection:**
```php
// Dodac do fillable: 'is_default'
// Scope:
public function scopeDefault($query) { return $query->where('is_default', true); }
```

3. **ERPManager - toggle metoda:**
```php
public function setAsDefaultErp(int $connectionId): void
{
    // Usun default z wszystkich
    ERPConnection::where('is_default', true)->update(['is_default' => false]);

    // Ustaw nowy default + priority 1
    $connection = ERPConnection::findOrFail($connectionId);
    $connection->update([
        'is_default' => true,
        'priority' => 1,
    ]);

    $this->dispatch('flash-message', [
        'type' => 'success',
        'message' => $connection->instance_name . ' ustawiony jako domyslny ERP',
    ]);
}
```

4. **Blade - toggle w Step 4** (erp-manager.blade.php, po linii ~676):
```blade
<div class="p-3 bg-gray-700/50 rounded-md flex items-center justify-between">
    <div>
        <span class="text-sm text-gray-300">Domyslny ERP</span>
        <p class="text-xs text-gray-500">
            Domyslny ERP jest ZAWSZE wlaczony dla nowych importow
        </p>
    </div>
    <button wire:click="setAsDefaultErp({{ $editingConnectionId }})"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
            {{ ($connectionForm['is_default'] ?? false)
                ? 'bg-amber-600 text-white cursor-default'
                : 'bg-gray-600 hover:bg-amber-600 text-gray-300 hover:text-white' }}">
        {{ ($connectionForm['is_default'] ?? false) ? 'Domyslny' : 'Ustaw jako domyslny' }}
    </button>
</div>
```

5. **Label "Domyslny" na liscie ERP** (erp-manager.blade.php):
```blade
{{-- W karcie/wierszu ERPConnection --}}
@if($connection->is_default)
    <span class="px-2 py-0.5 bg-amber-600/20 text-amber-400 text-xs rounded-full font-medium">
        Domyslny
    </span>
@endif
```

---

## GRUPA E: UI FIXES

### E1: Cancel Scheduled Publication (Enhancement #10)

**Problem:** Countdown timer nie ma opcji anulowania publikacji.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/partials/product-row.blade.php`

**Implementacja (Alpine hover state):**

```blade
{{-- Countdown timer z cancel on hover --}}
<div x-data="{ hovering: false }"
     x-on:mouseenter="hovering = true"
     x-on:mouseleave="hovering = false">

    {{-- Countdown (normalny stan) --}}
    <div x-show="!hovering" class="import-countdown-timer">
        <svg>...</svg>
        <span x-text="countdown">--:--</span>
    </div>

    {{-- Cancel button (hover) --}}
    <button x-show="hovering" x-cloak
            wire:click="cancelScheduledPublication({{ $product->id }})"
            class="import-publish-btn import-publish-btn-failed text-xs">
        Anuluj
    </button>
</div>
```

**Nowa metoda w ImportPanelPublicationTrait:**
```php
public function cancelScheduledPublication(int $productId): void
{
    $product = PendingProduct::findOrFail($productId);
    $product->update([
        'scheduled_publish_at' => null,
        'publish_status' => 'draft',
    ]);
}
```

---

### E2: Modal Reopen Fix (Bug #11)

**Problem:** Po zaimportowaniu produktow modal nie otwiera sie ponownie.

**Prawdopodobna przyczyna:** Stan `showModal` nie jest resetowany poprawnie
lub event `openImportModal` nie dociera do komponentu po pierwszym uzyciu.

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/ProductImportModal.php`
- `app/Http/Livewire/Products/Import/ProductImportPanel.php`

**Diagnoza:**
1. Sprawdzic czy `closeModal()` resetuje WSZYSTKIE stany (showModal, activeMode, rows, csv*)
2. Sprawdzic czy `#[On('openImportModal')]` listener dziala po pierwszym zamknieciu
3. Sprawdzic czy komponent nie jest renderowany warunkowo (`@if` vs `x-show`)

**Fix plan:**
1. Upewnic sie ze `resetAllState()` jest wywolywany w `closeModal()`
2. Dodac `$this->showModal = false` w `handleImportCompleted()`
3. Sprawdzic czy `@livewire('product-import-modal')` nie jest w `@if`
4. Test: otworz modal → importuj → zamknij → otworz ponownie

---

## MIGRACJE (nowe)

| # | Nazwa | Opis |
|---|-------|------|
| 1 | `add_import_column_preferences_to_users` | JSON pole na users table |
| 2 | `add_is_default_to_erp_connections` | Boolean is_default na erp_connections |

---

## KOLEJNOSC IMPLEMENTACJI

```
GRUPA C (CSV fixes) ─────────────────────────> DEPLOY + TEST
    ↕ rownolegle
GRUPA D (ERP publication) ──> migracje ──────> DEPLOY + TEST
    ↕ rownolegle
GRUPA E (UI fixes) ──────────────────────────> DEPLOY + TEST
    ↓ po grupach C,D,E
GRUPA A (Column paste + ceny) ───────────────> DEPLOY + TEST
    ↓
GRUPA B (Copy-down, drag-fill, saved layout) > DEPLOY + TEST
```

**Uzasadnienie kolejnosci:**
- GRUPA C (CSV bugs) - blokujace, szybkie fixy
- GRUPA D (ERP) - wymaga migracji, core feature
- GRUPA E (UI) - szybkie fixy
- GRUPA A (Column ceny) - wymaga refaktoru const→method, wiecej pracy
- GRUPA B (Drag-fill) - najwieksza zlozonosc, custom Alpine component

---

## PLIKI DO MODYFIKACJI (podsumowanie)

| # | Plik | Grupy |
|---|------|-------|
| 1 | `ImportModalColumnModeTrait.php` | A, B |
| 2 | `column-mode.blade.php` | A, B |
| 3 | `ImportModalCsvModeTrait.php` | C |
| 4 | `csv-mode.blade.php` | C |
| 5 | `ProductImportModal.php` | C, E |
| 6 | `ImportPanelPublicationTrait.php` | D, E |
| 7 | `product-row.blade.php` | D, E |
| 8 | `ERPManager.php` | D |
| 9 | `erp-manager.blade.php` | D |
| 10 | `ERPConnection.php` | D |
| 11 | `ProductImportPanel.php` | E |
| 12 | `config/import.php` | D |
| 13 | `import-panel.css` | B |
| 14 | `User.php` | B |

## NOWE PLIKI

| # | Plik | Opis |
|---|------|------|
| 1 | `database/migrations/xxx_add_import_column_preferences_to_users.php` | Migracja |
| 2 | `database/migrations/xxx_add_is_default_to_erp_connections.php` | Migracja |

---

---

## GRUPA F: MODAL IMPORT - BUGFIXES

### F1: Drag-fill Highlight (Bug #1)

**Problem:** Podczas przeciagania drag-fill handle user nie widzi ktore komorki zostana wypelnione.

**Przyczyna:** Brak CSS klas dla podswietlenia + brak `:class` Alpine binding.

**Pliki do zmiany:**
- `resources/css/products/import-panel.css`
- `resources/views/livewire/products/import/modals/partials/column-mode.blade.php`

**Implementacja:**

1. **CSS klasy** (import-panel.css):
```css
/* Drag-fill highlight */
.import-drag-fill-highlight {
    background-color: rgba(59, 130, 246, 0.15) !important;
}
.import-drag-fill-source {
    background-color: rgba(59, 130, 246, 0.25) !important;
    border: 2px solid rgb(59, 130, 246) !important;
}
tr.import-drag-fill-row-highlight {
    background-color: rgba(59, 130, 246, 0.08) !important;
}
```

2. **Alpine `:class` na `<tr>`** (column-mode.blade.php, linia ~238):
```blade
<tr :class="{
    'import-drag-fill-row-highlight': isDragging && dragSource && (
        {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
        {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
    )
}">
```

3. **Alpine `:class` na `<td>`** (dla kazdej komorki z drag-handle):
```blade
<td :class="{
    'import-drag-fill-highlight': isDragging && dragSource?.col === '{{ $colKey }}' && (
        {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
        {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
    ),
    'import-drag-fill-source': isDragging && dragSource?.row === {{ $rowIndex }} && dragSource?.col === '{{ $colKey }}'
}">
```

---

### F2: CSV Upload 500 Error (Bug #2)

**Problem:** Upload pliku CSV powoduje 500 error.

**Plik testowy:** `D:\OneDrive - MPP TRADE\Exporty CSV\B2B_Export_23-01-2026.csv`

**Pliki do zmiany:**
- `app/Http/Livewire/Products/Import/Modals/Traits/ImportModalCsvModeTrait.php`

**Implementacja:**

1. **Ulepszona walidacja** (linia ~263):
```php
$this->validate([
    'csvFile' => [
        'required',
        'file',
        'max:51200',
        'mimes:csv,txt,xlsx,xls',
        'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
], [...]);
```

2. **Try-catch z debug logging** (linia ~287):
```php
try {
    Log::debug('CSV Upload', [
        'filename' => $this->csvFile->getClientOriginalName(),
        'mime' => $this->csvFile->getMimeType(),
        'size' => $this->csvFile->getSize(),
    ]);

    $path = $this->csvFile->getRealPath();
    if (!$path || !file_exists($path)) {
        $this->addError('csvFile', 'Nie mozna odczytac pliku.');
        return;
    }

    $parsed = $this->csvParser->parseCSV($this->csvFile);
    // ...
} catch (\Exception $e) {
    Log::error('CSV Upload Error', ['error' => $e->getMessage()]);
    $this->addError('csvFile', 'Blad parsowania: ' . $e->getMessage());
}
```

---

### F3: Przycisk "+" do kategorii (Bug #6)

**Problem:** Przyciski w dropdown "Dodaj kolumne" nie dzialaja.

**Przyczyna:** `wire:click` wykonuje sie, ale dropdown zamyka sie przez `x-on:click.away` PRZED wywolaniem.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/modals/partials/column-mode.blade.php`

**Implementacja:**

Zmiana (linie ~149, ~165):
```blade
{{-- PRZED: --}}
<button wire:click="addColumn('{{ $key }}')"
        x-on:click="showColumnPicker = false"

{{-- PO: --}}
<button wire:click.stop="addColumn('{{ $key }}')"
        x-on:click="setTimeout(() => showColumnPicker = false, 100)"
```

---

## GRUPA G: LISTA IMPORT - BUGFIXES

### G1: Dropdown Publikacji Overflow (Bug #3)

**Problem:** Dropdown publikacji jest przyciety gdy tabela ma overflow.

**Przyczyna:** Mimo `x-teleport="body"`, pozycjonowanie moze byc bledne.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/partials/product-row.blade.php`
- `resources/css/products/import-panel.css`

**Implementacja:**

1. **Dodac `x-ref="trigger"` do przycisku** (linia ~262):
```blade
<button type="button" x-on:click="open = !open"
        x-ref="trigger" ...>
```

2. **Dynamic positioning w dropdown** (linia ~292):
```blade
<template x-teleport="body">
    <div x-show="open" x-cloak
         class="import-targets-dropdown-menu"
         :style="`position: fixed; z-index: 9999; top: ${$refs.trigger?.getBoundingClientRect().bottom + 4}px; left: ${$refs.trigger?.getBoundingClientRect().left}px;`">
```

3. **CSS z-index** (import-panel.css):
```css
.import-targets-dropdown-menu {
    z-index: 9999;  /* zwiekszone z 50 */
}
```

---

### G2: Modal Edycji z Listy (Bug #4)

**Problem:** Produkty na liscie nie maja przycisku edycji przez modal.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/partials/product-row.blade.php`

**Implementacja:**

Dodac przycisk edycji w sekcji AKCJE (po linia ~566):
```blade
{{-- Edytuj przez modal --}}
<button wire:click="openImportModal({{ $product->id }})"
        class="p-1 rounded transition-colors text-gray-400 hover:text-white hover:bg-gray-700/50"
        title="Edytuj podstawowe dane">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
</button>
```

---

### G3: Modal Cen - Przeliczanie (Bug #5)

**Problem:** Brak auto-przeliczania netto/brutto i switcha w naglowku.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/modals/import-prices-modal.blade.php`

**Implementacja:**

1. **Zmienic `wire:model.defer` na `wire:model.live`** (linie ~105, ~121)

2. **Poprawic Alpine x-data** (linie ~60-75):
```javascript
x-data="{
    taxRate: {{ $taxRate }},
    isLocked: {{ !$isEditable ? 'true' : 'false' }},
    prices: @js($modalPrices),
    calculateGross(groupId) {
        if (this.isLocked) return;
        const net = parseFloat(this.prices[groupId]?.net || 0);
        if (isNaN(net)) return;
        const gross = (net * (1 + this.taxRate / 100)).toFixed(2);
        this.prices[groupId].gross = gross;
        $wire.set('modalPrices.' + groupId + '.gross', gross, false);
    },
    calculateNet(groupId) {
        if (this.isLocked) return;
        const gross = parseFloat(this.prices[groupId]?.gross || 0);
        if (isNaN(gross)) return;
        const net = (gross / (1 + this.taxRate / 100)).toFixed(2);
        this.prices[groupId].net = net;
        $wire.set('modalPrices.' + groupId + '.net', net, false);
    }
}"
```

3. **Inputy z x-model + @input** (linie ~102-130):
```blade
<input type="number"
       x-model="prices[{{ $group->id }}].net"
       @input="calculateGross({{ $group->id }})"
       wire:model.live="modalPrices.{{ $group->id }}.net"
       ...>
```

4. **Switch netto/brutto w naglowku** (po linia ~82):
```blade
<th class="px-4 py-3 text-center">
    <button type="button"
            @click="/* swap logic */"
            class="text-xs px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded"
            title="Zamien wartosci netto/brutto">
        ⇄
    </button>
</th>
```

---

### G4: PrestaShop Dropdown Zamykanie (Bug #7)

**Problem:** Wybor PrestaShop shop zamyka dropdown - nie mozna wybrac wiecej sklepow.

**Przyczyna:** `wire:click` powoduje Livewire re-render ktory resetuje Alpine `open` state.

**Pliki do zmiany:**
- `resources/views/livewire/products/import/partials/product-row.blade.php`

**Implementacja:**

1. **Alpine-first approach** (zmiana x-data, linia ~242):
```blade
<div x-data="{
    open: false,
    selectedShops: @js($psShops)
}" class="import-targets-dropdown" x-on:click.outside="open = false">
```

2. **Checkbox z Alpine state** (linie ~329-337):
```blade
<input type="checkbox"
       :checked="selectedShops.includes({{ $shop->id }})"
       @change.stop="
           if ($event.target.checked) {
               selectedShops.push({{ $shop->id }});
           } else {
               selectedShops = selectedShops.filter(id => id !== {{ $shop->id }});
           }
           $wire.togglePrestaShopShop({{ $product->id }}, {{ $shop->id }});
       ">
```

---

## GRUPA H: KATEGORIE PRESTASHOP (Feature #8)

### H1: Architektura

**Wymaganie:** Kazda integracja PrestaShop ma wlasne drzewko kategorii.
Uzytkownik przy imporcie powinien moc:
1. Wybrac kategorie z drzewka danego sklepu
2. LUB utworzyc kategorie w PrestaShop na podstawie struktury PPM

**Istniejace komponenty do wykorzystania:**
- `PrestaShopCategoryService` - cache drzewek (15min TTL)
- `CategoryMapper` - mapowanie PPM <-> PS
- `PendingProduct.shop_categories` - JSON field per shop
- `ProductPublicationService::createShopData()` - obsluguje shop_categories

### H2: Nowe komponenty

| Plik | Typ | Opis |
|------|-----|------|
| `PrestaShopCategoryPickerModal.php` | Livewire | Modal wyboru kategorii |
| `prestashop-category-picker-modal.blade.php` | Blade | Widok modalu |
| `PrestaShopCategoryCreationService.php` | Service | Tworzenie kategorii w PS z PPM |

### H3: Format danych

**PendingProduct.shop_categories (JSON):**
```json
{
  "1": [123, 456],   // shop_id: 1 -> PrestaShop category IDs
  "3": [789]         // shop_id: 3 -> PrestaShop category IDs
}
```

### H4: UI Flow

```
[User wybiera PS shop w dropdown publikacji]
        |
        v
[Pojawia sie przycisk "Kategorie" przy tym sklepie]
        |
        v
[Klik -> Modal PrestaShopCategoryPickerModal]
        |
        +---> [Opcja A: Wybierz z drzewka]
        |            |
        |            v
        |     [Drzewko z cache -> multi-select checkboxy]
        |            |
        |            v
        |     [Zapisz -> shop_categories[$shopId] = [ids]]
        |
        +---> [Opcja B: Utworz z PPM]
                     |
                     v
              [PrestaShopCategoryCreationService]
                     |
                     v
              [Mapuj kategorie PPM -> utworz w PS API]
                     |
                     v
              [Zapisz mapowanie + shop_categories]
```

### H5: Plan implementacji

**Krok 1: PrestaShopCategoryPickerModal.php**
```php
class PrestaShopCategoryPickerModal extends Component
{
    public bool $isOpen = false;
    public ?int $productId = null;
    public ?int $shopId = null;
    public array $selectedCategoryIds = [];
    public string $mode = 'select'; // 'select' | 'auto_create'

    #[On('openPrestaShopCategoryPicker')]
    public function open(int $productId, int $shopId): void;

    public function getCategoryTree(): array;  // PrestaShopCategoryService
    public function toggleCategory(int $categoryId): void;
    public function save(): void;
    public function useAutoCategoryCreate(): void;
}
```

**Krok 2: Blade view (modal)**
```blade
<div x-show="$wire.isOpen" x-teleport="body" class="modal-overlay">
    <div class="modal-content max-w-2xl">
        <!-- Header: Nazwa sklepu + Odswiez -->
        <!-- Search input -->
        <!-- Category tree (recursive) -->
        <!-- Opcja: "Utwórz z PPM" -->
        <!-- Footer: Anuluj / Zapisz -->
    </div>
</div>
```

**Krok 3: Przycisk w dropdown publikacji** (product-row.blade.php)
```blade
@if(in_array($shop->id, $psShops))
    <button wire:click="$dispatch('openPrestaShopCategoryPicker', {
        productId: {{ $product->id }},
        shopId: {{ $shop->id }}
    })" class="ps-category-btn">
        @php $catCount = count($product->shop_categories[$shop->id] ?? []); @endphp
        @if($catCount > 0)
            <span class="badge">{{ $catCount }}</span>
        @else
            <span class="badge-warning">!</span>
        @endif
        Kategorie
    </button>
@endif
```

**Krok 4: PrestaShopCategoryCreationService.php**
```php
class PrestaShopCategoryCreationService
{
    public function createFromPpmCategories(PrestaShopShop $shop, array $ppmCategoryIds): array
    {
        // 1. Pobierz kategorie PPM
        // 2. Dla kazdej: sprawdz ShopMapping, jesli brak -> utworz w PS
        // 3. Zapisz mapowanie
        // 4. Zwroc PS category IDs
    }
}
```

**Krok 5: Walidacja przed publikacja**
Rozszerzyc `PendingProduct::getPublishValidationErrors()`:
```php
foreach ($psShops as $shopId) {
    if (empty($shopCats[$shopId])) {
        $errors[] = "Brak kategorii dla sklepu: " . $shopName;
    }
}
```

### H6: Decyzje architektoniczne (zatwierdzone)

| Pytanie | Decyzja |
|---------|---------|
| Modal UI | ✅ Osobny modal per sklep (trigger z przycisku przy sklepie) |
| Kategorie | ✅ Multi-select (PrestaShop wspiera wiele kategorii) |
| Auto-create | ✅ Wymaga swiadomego wyboru (klik "Utworz z PPM") |
| Walidacja | ✅ Warning badge + blokada publikacji (zolty wykrzyknik, publikacja zablokowana) |

### H7: Szczegoly UI

**Badge przy sklepie w dropdown:**
- Brak kategorii: `<span class="badge-warning">!</span>` (zolty wykrzyknik)
- Wybrane kategorie: `<span class="badge-count">3</span>` (liczba kategorii)

**Modal kategorii:**
- Header: Nazwa sklepu + przycisk "Odswiez drzewko"
- Content: Drzewko kategorii z checkboxami (multi-select)
- Wyszukiwarka kategorii na gorze
- Sekcja "Opcje": przycisk "Utworz kategorie z PPM"
- Footer: "Anuluj" / "Zapisz (X wybranych)"

**Walidacja publikacji:**
- Jesli `shop_categories[$shopId]` jest puste → blokada przycisku "Publikuj"
- Tooltip: "Wybierz kategorie dla sklepu X"

---

## PLIKI DO MODYFIKACJI (FAZA 9.7b)

| # | Plik | Bugi |
|---|------|------|
| 1 | `column-mode.blade.php` | 1, 6 |
| 2 | `import-panel.css` | 1, 3 |
| 3 | `ImportModalCsvModeTrait.php` | 2 |
| 4 | `product-row.blade.php` | 3, 4, 7, 8 |
| 5 | `import-prices-modal.blade.php` | 5 |

## NOWE PLIKI (FAZA 9.7b)

| # | Plik | Opis |
|---|------|------|
| 1 | `PrestaShopCategoryPickerModal.php` | Modal wyboru kategorii PS |
| 2 | `prestashop-category-picker-modal.blade.php` | Widok modalu |
| 3 | `PrestaShopCategoryCreationService.php` | Serwis tworzenia kategorii |

---

## KOLEJNOSC IMPLEMENTACJI

```
GRUPA F (Modal bugs: 1, 2, 6) ────────> DEPLOY + TEST
    ↓
GRUPA G (Lista bugs: 3, 4, 5, 7) ─────> DEPLOY + TEST
    ↓
GRUPA H (Kategorie PrestaShop) ───────> DEPLOY + TEST
```

---

## WERYFIKACJA PO WDROZENIU

### GRUPA F ✅ UKONCZONE 2026-02-04
- [x] Drag-fill: komorki podswietlaja sie podczas przeciagania
- [x] CSV upload: plik `B2B_Export_23-01-2026.csv` laduje bez bledu
- [x] Przycisk "+": kolumny dodaja sie po kliknieciu

### GRUPA G ✅ UKONCZONE 2026-02-04
- [x] Dropdown publikacji: widoczny w calosci (nie przyciety)
- [x] Przycisk edycji: otwiera modal z danymi produktu
- [x] Modal cen: auto-przeliczanie netto/brutto + switch dziala
- [x] PrestaShop wybor: dropdown nie zamyka sie po wyborze sklepu

### GRUPA H ✅ UKONCZONE 2026-02-04
- [x] Przycisk "Kategorie" pojawia sie przy wybranym PS shop
- [x] Modal kategorii: drzewko laduje sie z cache
- [x] Modal kategorii: multi-select dziala
- [ ] "Utworz z PPM": tworzy kategorie w PrestaShop (opcja na pozniej)
- [x] Walidacja: blad jesli brak kategorii dla wybranego sklepu
- [x] Chrome DevTools: brak nowych bledow w konsoli
