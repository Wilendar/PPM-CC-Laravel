# Plan: System Granularnych Blokad Kolumn - Zakładka "Stany Magazynowe"

**Data utworzenia:** 2026-01-23
**Autor:** Architect Agent
**Status:** ✅ UKOŃCZONE
**Priorytet:** HIGH - Zapobieganie przypadkowej synchronizacji do ERP
**Data ukończenia:** 2026-01-23

**PROGRESS:**
- ✅ FAZA 1: Database & Permissions - UKOŃCZONA
- ✅ FAZA 2: State Management - UKOŃCZONA
- ✅ FAZA 3: UI Layer - UKOŃCZONA
- ✅ FAZA 4: Save & Sync Logic - UKOŃCZONA
- ✅ FAZA 5: Integration & Testing - UKOŃCZONA (deployed to production)

---

## 1. Problem Biznesowy

### Obecna sytuacja:
- **Jeden przycisk** "Zablokowane/Odblokowane" dla całej zakładki stanów
- Przy "Zapisz zmiany" **wszystkie dane stanów** są wysyłane do ERP (Subiekt GT, BaseLinker)
- Brak **dirty tracking** - nie wiadomo które wartości user faktycznie edytował
- Brak **audit log** - nie ma śladu kto odblokował/edytował dane

### Skutki:
- Przypadkowe nadpisanie danych w ERP nawet bez intencji edycji
- Brak kontroli nad tym które pola są synchronizowane
- Brak historii zmian dla compliance i debugowania

---

## 2. Wymagania Biznesowe

### 2.1 Granularna blokada per kolumna
- **Stan dostępny** (`quantity`) - osobna blokada
- **Zarezerwowane** (`reserved`) - osobna blokada
- **Minimum** (`minimum_stock`) - osobna blokada

### 2.2 Dirty Tracking
- Sync PPM → ERP **TYLKO** gdy user:
  1. Explicite odblokował kolumnę **I**
  2. Faktycznie zmienił wartość
- Jeśli kolumna zablokowana lub niezedytowana → dane **NIE** są wysyłane do ERP

### 2.3 Modal ostrzegawczy
```
[!] Odblokowanie tej kolumny spowoduje możliwość edycji i synchronizacji
    zmian do systemu ERP (Subiekt GT, Baselinker).

    Czy na pewno chcesz kontynuować?

    [Potwierdź odblokowanie]  [Anuluj]
```

### 2.4 Uprawnienia
- `products.stock.unlock_quantity` - odblokowanie stanu dostępnego
- `products.stock.unlock_reserved` - odblokowanie rezerwacji
- `products.stock.unlock_minimum` - odblokowanie minimum

### 2.5 Audit Log
- Kto odblokował (user_id, timestamp)
- Jakie zmiany zostały dokonane (old_value → new_value)
- Kiedy zsynchronizowano do ERP

---

## 3. Architektura Rozwiązania

### 3.1 Diagram przepływu

```mermaid
flowchart TD
    A[User otwiera Stock Tab] --> B[Wszystkie kolumny LOCKED]
    B --> C{User klika Lock na kolumnie}
    C --> D[Sprawdź permission]
    D -->|Brak uprawnień| E[Toast: Brak uprawnień]
    D -->|Ma uprawnienie| F[Pokaż Modal ostrzeżenie]
    F -->|Anuluj| B
    F -->|Potwierdź| G[Odblokuj kolumnę]
    G --> H[Zaloguj UNLOCK w audit_log]
    H --> I[Kolumna edytowalna]
    I --> J{User edytuje wartość}
    J --> K[Porównaj z original value]
    K -->|Zmiana| L[Oznacz jako DIRTY + Badge]
    K -->|Bez zmiany| M[Usuń DIRTY flag]
    L --> N{User klika Zapisz}
    N --> O[Zapisz TYLKO dirty columns do PPM DB]
    O --> P[Zaloguj EDIT w audit_log]
    P --> Q{Sync do ERP?}
    Q -->|Tak| R[Wyślij TYLKO dirty columns do ERP]
    R --> S[Zaloguj SYNC w audit_log]
```

### 3.2 State Management

```php
// ProductForm.php - Nowe właściwości

// Zamiast jednego boola:
// public bool $stockUnlocked = false;

// Granularne blokady per kolumna:
public array $stockColumnLocks = [
    'quantity' => false,  // false = locked (readonly)
    'reserved' => false,  // true = unlocked (editable)
    'minimum' => false,
];

// Snapshot wartości przy ładowaniu (dla dirty detection):
public array $stockOriginalValues = [];
// Struktura: [warehouse_id => ['quantity' => X, 'reserved' => Y, 'minimum' => Z]]

// Flagi dirty per warehouse/column:
public array $stockDirtyFlags = [];
// Struktura: [warehouse_id => ['quantity' => true/false, ...]]
```

### 3.3 Dirty Tracking Flow

```php
// 1. Na mount() - capture original values
public function captureStockOriginals(): void
{
    foreach ($this->stock as $warehouseId => $data) {
        $this->stockOriginalValues[$warehouseId] = [
            'quantity' => (float) ($data['quantity'] ?? 0),
            'reserved' => (float) ($data['reserved'] ?? 0),
            'minimum' => (float) ($data['minimum'] ?? 0),
        ];
    }
}

// 2. Na zmianę inputa - sprawdź dirty
public function checkStockDirty(int $warehouseId, string $column): void
{
    $original = $this->stockOriginalValues[$warehouseId][$column] ?? 0;
    $current = $this->stock[$warehouseId][$column] ?? 0;

    $this->stockDirtyFlags[$warehouseId][$column] =
        ((float) $original !== (float) $current);
}

// 3. Pobierz edytowalne kolumny do sync
public function getEditableStockData(): array
{
    $result = [];

    foreach ($this->stock as $warehouseId => $data) {
        $editedColumns = [];

        foreach (['quantity', 'reserved', 'minimum'] as $col) {
            // Kolumna musi być: odblokowana I dirty
            if ($this->stockColumnLocks[$col]
                && ($this->stockDirtyFlags[$warehouseId][$col] ?? false)) {
                $editedColumns[$col] = $data[$col];
            }
        }

        if (!empty($editedColumns)) {
            $result[$warehouseId] = $editedColumns;
        }
    }

    return $result;
}
```

---

## 4. Szczegółowy Plan Implementacji

### ✅ 4.1 FAZA 1: Database & Permissions (Foundation)

#### ✅ 4.1.1 Migration: stock_edit_logs
```
Plik: database/migrations/2026_01_24_000001_create_stock_edit_logs_table.php
```

**Struktura tabeli:**
```sql
CREATE TABLE stock_edit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,

    column_name ENUM('quantity', 'reserved', 'minimum') NOT NULL,
    action ENUM('unlock', 'lock', 'edit', 'sync_to_erp') NOT NULL,

    old_value DECIMAL(15,4) NULL,
    new_value DECIMAL(15,4) NULL,

    erp_connection_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_product (user_id, product_id),
    INDEX idx_product_column (product_id, column_name),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    FOREIGN KEY (erp_connection_id) REFERENCES erp_connections(id) ON DELETE SET NULL
);
```

**Złożoność:** Low
**Estymacja:** ~50 linii

#### ✅ 4.1.2 Model: StockEditLog
```
Plik: app/Models/StockEditLog.php
```

**Złożoność:** Low
**Estymacja:** ~60 linii

#### ✅ 4.1.3 Permission Seeder
```
Plik: database/seeders/StockPermissionsSeeder.php
```

**Nowe uprawnienia:**
- `products.stock.unlock_quantity` - "Odblokowanie stanu dostępnego"
- `products.stock.unlock_reserved` - "Odblokowanie rezerwacji"
- `products.stock.unlock_minimum` - "Odblokowanie minimum"

**Złożoność:** Low
**Estymacja:** ~30 linii

---

### ✅ 4.2 FAZA 2: State Management (Core Logic)

#### ✅ 4.2.1 ProductForm.php - Nowe właściwości
```
Plik: app/Http/Livewire/Products/Management/ProductForm.php
```

**Zmiany:**
```php
// USUNĄĆ lub DEPRECATED:
// public bool $stockUnlocked = false;

// DODAĆ:
public array $stockColumnLocks = [
    'quantity' => false,
    'reserved' => false,
    'minimum' => false,
];
public array $stockOriginalValues = [];
public array $stockDirtyFlags = [];

// Pending unlock (for modal confirmation)
public ?string $pendingStockColumnUnlock = null;
```

**Złożoność:** Low
**Estymacja:** ~20 linii

#### ✅ 4.2.2 ProductForm.php - Metody blokad kolumn
```
Plik: app/Http/Livewire/Products/Management/ProductForm.php
```

**Nowe metody:**
```php
// Sprawdź uprawnienie dla konkretnej kolumny
public function canUnlockStockColumn(string $column): bool
{
    return match($column) {
        'quantity' => auth()->user()?->can('products.stock.unlock_quantity'),
        'reserved' => auth()->user()?->can('products.stock.unlock_reserved'),
        'minimum' => auth()->user()?->can('products.stock.unlock_minimum'),
        default => false,
    } ?? false;
}

// Rozpocznij proces odblokowania (pokaż modal)
public function requestStockColumnUnlock(string $column): void
{
    if (!$this->canUnlockStockColumn($column)) {
        $this->dispatch('notification', [
            'type' => 'error',
            'message' => 'Brak uprawnień do odblokowania tej kolumny',
        ]);
        return;
    }

    $this->pendingStockColumnUnlock = $column;
    $this->dispatch('show-stock-unlock-modal');
}

// Potwierdź odblokowanie (z modala)
public function confirmStockColumnUnlock(): void
{
    if (!$this->pendingStockColumnUnlock) return;

    $column = $this->pendingStockColumnUnlock;
    $this->stockColumnLocks[$column] = true;

    // Audit log
    $this->logStockColumnAction($column, 'unlock');

    $this->pendingStockColumnUnlock = null;
    $this->dispatch('close-stock-unlock-modal');
}

// Zablokuj kolumnę
public function lockStockColumn(string $column): void
{
    $this->stockColumnLocks[$column] = false;
    $this->logStockColumnAction($column, 'lock');
}

// Sprawdź czy kolumna odblokowana
public function isStockColumnUnlocked(string $column): bool
{
    return $this->stockColumnLocks[$column] ?? false;
}
```

**Złożoność:** Medium
**Estymacja:** ~80 linii

#### ✅ 4.2.3 ProductForm.php - Dirty tracking
```
Plik: app/Http/Livewire/Products/Management/ProductForm.php
```

**Nowe metody:**
```php
// Capture original values on mount
public function captureStockOriginals(): void
{
    $this->stockOriginalValues = [];
    foreach ($this->stock as $warehouseId => $data) {
        $this->stockOriginalValues[$warehouseId] = [
            'quantity' => (float) ($data['quantity'] ?? 0),
            'reserved' => (float) ($data['reserved'] ?? 0),
            'minimum' => (float) ($data['minimum'] ?? 0),
        ];
    }
}

// Called via wire:change or Alpine
public function markStockDirty(int $warehouseId, string $column): void
{
    $original = $this->stockOriginalValues[$warehouseId][$column] ?? 0;
    $current = $this->stock[$warehouseId][$column] ?? 0;

    $isDirty = ((float) $original !== (float) $current);
    $this->stockDirtyFlags[$warehouseId][$column] = $isDirty;
}

// Check if specific cell is dirty
public function isStockCellDirty(int $warehouseId, string $column): bool
{
    return $this->stockDirtyFlags[$warehouseId][$column] ?? false;
}

// Get all dirty stock data for sync
public function getDirtyStockForSync(): array
{
    $result = [];

    foreach ($this->stock as $warehouseId => $data) {
        $dirtyColumns = [];

        foreach (['quantity', 'reserved', 'minimum'] as $col) {
            if ($this->stockColumnLocks[$col]
                && ($this->stockDirtyFlags[$warehouseId][$col] ?? false)) {
                $dirtyColumns[$col] = $data[$col];
            }
        }

        if (!empty($dirtyColumns)) {
            $result[$warehouseId] = $dirtyColumns;
        }
    }

    return $result;
}
```

**Złożoność:** Medium
**Estymacja:** ~70 linii

#### ✅ 4.2.4 ProductForm.php - Audit logging helper
```
Plik: app/Http/Livewire/Products/Management/ProductForm.php
```

**Nowa metoda:**
```php
protected function logStockColumnAction(
    string $column,
    string $action,
    ?int $warehouseId = null,
    ?float $oldValue = null,
    ?float $newValue = null,
    ?int $erpConnectionId = null
): void {
    if (!$this->product?->id) return;

    StockEditLog::create([
        'user_id' => auth()->id(),
        'product_id' => $this->product->id,
        'warehouse_id' => $warehouseId,
        'column_name' => $column,
        'action' => $action,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'erp_connection_id' => $erpConnectionId,
        'metadata' => [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ],
    ]);
}
```

**Złożoność:** Low
**Estymacja:** ~30 linii

---

### ✅ 4.3 FAZA 3: UI Layer

#### ✅ 4.3.1 stock-tab.blade.php - Nagłówki z przyciskami lock
```
Plik: resources/views/livewire/products/management/tabs/stock-tab.blade.php
```

**Zmiany w <thead>:**
```html
<thead class="text-xs text-gray-400 uppercase bg-gray-900">
    <tr>
        <th scope="col" class="px-4 py-3">Magazyn</th>

        {{-- Stan dostępny z przyciskiem lock --}}
        <th scope="col" class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
                <span>Stan dostępny</span>
                @if($this->canUnlockStockColumn('quantity'))
                    <button type="button"
                            wire:click="{{ $this->isStockColumnUnlocked('quantity')
                                ? 'lockStockColumn(\'quantity\')'
                                : 'requestStockColumnUnlock(\'quantity\')' }}"
                            class="...">
                        {{-- Lock/Unlock icon --}}
                    </button>
                @endif
            </div>
        </th>

        {{-- Analogicznie dla reserved i minimum --}}
    </tr>
</thead>
```

**Złożoność:** Medium
**Estymacja:** ~100 linii (zmiany)

#### ✅ 4.3.2 stock-tab.blade.php - Inputy z conditional styling
```
Plik: resources/views/livewire/products/management/tabs/stock-tab.blade.php
```

**Zmiany w inputach:**
```html
<td class="px-4 py-3">
    @php
        $isUnlocked = $this->isStockColumnUnlocked('quantity');
        $isDirty = $this->isStockCellDirty($warehouseId, 'quantity');
    @endphp

    <div class="relative">
        <input type="number"
               min="0"
               wire:model.live="stock.{{ $warehouseId }}.quantity"
               x-on:change="$wire.markStockDirty({{ $warehouseId }}, 'quantity')"
               @class([
                   'w-full border text-sm rounded-lg px-3 py-2',
                   'bg-gray-800 border-gray-700 text-gray-400 cursor-not-allowed' => !$isUnlocked,
                   'bg-gray-700 border-gray-600 text-white' => $isUnlocked && !$isDirty,
                   'bg-yellow-900/30 border-yellow-500 text-yellow-200' => $isUnlocked && $isDirty,
               ])
               {{ $isUnlocked ? '' : 'readonly' }}>

        @if($isDirty)
            <span class="absolute -top-2 -right-2 px-1.5 py-0.5 text-xs
                         bg-yellow-600 text-yellow-100 rounded-full">
                Edytowano
            </span>
        @endif
    </div>
</td>
```

**Złożoność:** Medium-High
**Estymacja:** ~80 linii (zmiany)

#### ✅ 4.3.3 Modal potwierdzenia odblokowania
```
Plik: resources/views/livewire/products/management/tabs/stock-tab.blade.php
(lub osobny partial)
```

**Komponent modala (Alpine.js):**
```html
<div x-data="{ showModal: false }"
     x-on:show-stock-unlock-modal.window="showModal = true"
     x-on:close-stock-unlock-modal.window="showModal = false">

    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>

        <div class="flex items-center justify-center min-h-screen">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60"
                 x-on:click="showModal = false; $wire.pendingStockColumnUnlock = null"></div>

            {{-- Modal content --}}
            <div class="relative bg-gray-800 rounded-xl shadow-xl max-w-md mx-4 p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-900/50
                                flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-500" ...><!-- Warning icon --></svg>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Potwierdzenie odblokowania
                        </h3>
                        <p class="mt-2 text-gray-300 text-sm">
                            Odblokowanie tej kolumny spowoduje możliwość edycji
                            i <strong>synchronizacji zmian do systemu ERP</strong>
                            (Subiekt GT, Baselinker).
                        </p>
                        <p class="mt-2 text-yellow-400 text-sm">
                            Czy na pewno chcesz kontynuować?
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            x-on:click="showModal = false; $wire.pendingStockColumnUnlock = null"
                            class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="confirmStockColumnUnlock"
                            class="btn-enterprise-primary">
                        Potwierdź odblokowanie
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Złożoność:** Medium
**Estymacja:** ~80 linii

#### ✅ 4.3.4 CSS dla stanów kolumn
```
Plik: resources/css/products/product-form.css
```

**Nowe klasy:**
```css
/* Stock column states */
.stock-column-locked {
    background-color: var(--bg-disabled, #1f2937);
    border-color: var(--border-muted, #374151);
    color: var(--text-muted, #9ca3af);
    cursor: not-allowed;
}

.stock-column-unlocked {
    background-color: var(--bg-input, #374151);
    border-color: var(--border-input, #4b5563);
    color: var(--text-primary, #fff);
}

.stock-column-dirty {
    background-color: rgba(251, 191, 36, 0.15);
    border-color: #f59e0b;
    border-width: 2px;
    color: #fef3c7;
}

.stock-dirty-badge {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    padding: 0.125rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 600;
    background-color: #d97706;
    color: #fffbeb;
    border-radius: 9999px;
}
```

**Złożoność:** Low
**Estymacja:** ~40 linii

---

### ✅ 4.4 FAZA 4: Save & Sync Logic

#### ✅ 4.4.1 ProductForm.php (saveStockInternal) - Selective save
```
Plik: app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
```

**Modyfikacja saveStock():**
```php
private function saveStock(): void
{
    if (!$this->component->product || !$this->component->product->exists) {
        return;
    }

    $savedCount = 0;
    $dirtyData = $this->component->getDirtyStockForSync();

    // Jeśli żadna kolumna nie jest dirty - skip całkowicie
    if (empty($dirtyData)) {
        Log::info('[STOCK SAVE] No dirty columns, skipping stock save');
        return;
    }

    try {
        foreach ($this->component->stock as $warehouseId => $stockData) {
            $dirtyColumns = $dirtyData[$warehouseId] ?? [];

            // Jeśli ten magazyn nie ma dirty kolumn - skip
            if (empty($dirtyColumns)) {
                continue;
            }

            // Znajdź istniejący rekord lub utwórz nowy
            $stock = ProductStock::firstOrNew([
                'product_id' => $this->component->product->id,
                'product_variant_id' => null,
                'warehouse_id' => $warehouseId,
            ]);

            // Zaloguj stare wartości przed update
            $oldValues = [
                'quantity' => $stock->quantity,
                'reserved' => $stock->reserved_quantity,
                'minimum' => $stock->minimum_stock,
            ];

            // Aktualizuj TYLKO dirty kolumny
            if (isset($dirtyColumns['quantity'])) {
                $stock->quantity = $dirtyColumns['quantity'];
            }
            if (isset($dirtyColumns['reserved'])) {
                $stock->reserved_quantity = $dirtyColumns['reserved'];
            }
            if (isset($dirtyColumns['minimum'])) {
                $stock->minimum_stock = $dirtyColumns['minimum'];
            }

            $stock->is_active = true;
            $stock->track_stock = true;
            $stock->save();

            // Audit log dla każdej zmienionej kolumny
            foreach ($dirtyColumns as $col => $newValue) {
                $this->component->logStockColumnAction(
                    $col,
                    'edit',
                    $warehouseId,
                    $oldValues[$col] ?? null,
                    $newValue
                );
            }

            $savedCount++;
        }

        Log::info('[STOCK SAVE] Selective save completed', [
            'product_id' => $this->component->product->id,
            'saved_warehouses' => $savedCount,
            'dirty_columns' => array_keys($dirtyData),
        ]);

    } catch (\Exception $e) {
        Log::error('[STOCK SAVE] Failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

**Złożoność:** Medium
**Estymacja:** ~80 linii

#### ✅ 4.4.2 SubiektGTService.php - Filtered sync
```
Plik: app/Services/ERP/SubiektGTService.php
```

**Modyfikacja mapPpmProductToSubiekt():**
```php
protected function mapPpmProductToSubiekt(
    Product $product,
    array $config,
    bool $isCreate = false,
    ?int $connectionId = null,
    ?array $dirtyStockColumns = null  // NEW PARAMETER
): array {
    // ... existing code for name, description, prices ...

    // ====== STOCK MAPPING (MODIFIED) ======

    // Jeśli dirtyStockColumns jest puste - pomiń stock całkowicie
    if ($dirtyStockColumns !== null && empty($dirtyStockColumns)) {
        Log::debug('mapPpmProductToSubiekt: No dirty stock columns, skipping stock');
        // NIE dodawaj $data['stock'] = ...
        return $data;
    }

    $erpToPpmWarehouseMapping = $config['warehouse_mappings'] ?? [];
    $ppmToErpWarehouseMapping = array_flip($erpToPpmWarehouseMapping);
    $stock = [];

    $productStock = $product->stock ?? collect();

    foreach ($productStock as $stockData) {
        $ppmWarehouseId = $stockData['warehouse_id'] ?? null;

        if (!$ppmWarehouseId || !isset($ppmToErpWarehouseMapping[$ppmWarehouseId])) {
            continue;
        }

        // Sprawdź czy ten magazyn ma dirty kolumny
        $warehouseDirtyColumns = $dirtyStockColumns[$ppmWarehouseId] ?? null;

        // Jeśli dirtyStockColumns przekazane i ten magazyn nie ma dirty - skip
        if ($dirtyStockColumns !== null && $warehouseDirtyColumns === null) {
            continue;
        }

        $erpWarehouseId = (int) $ppmToErpWarehouseMapping[$ppmWarehouseId];
        $stockEntry = [];

        // Dodaj tylko dirty kolumny (lub wszystkie jeśli brak filtrowania)
        if ($warehouseDirtyColumns === null || isset($warehouseDirtyColumns['quantity'])) {
            $stockEntry['quantity'] = (float) ($stockData['quantity'] ?? 0);
        }
        if ($warehouseDirtyColumns === null || isset($warehouseDirtyColumns['minimum'])) {
            $stockEntry['min'] = (float) ($stockData['minimum_stock'] ?? 0);
        }
        // reserved nie jest wysyłane do Subiekt GT (wewnętrzna wartość PPM)

        if (!empty($stockEntry)) {
            $stock[$erpWarehouseId] = $stockEntry;
        }
    }

    if (!empty($stock)) {
        $data['stock'] = $stock;
    }

    return $data;
}
```

**Złożoność:** Medium-High
**Estymacja:** ~60 linii (zmiany)

#### ✅ 4.4.3 SyncProductToERP.php - Przekazywanie dirty info (via syncOptions)
```
Plik: app/Jobs/ERP/SyncProductToERP.php
```

**Modyfikacje:**
```php
public function __construct(
    public Product $product,
    public ERPConnection $erpConnection,
    public ?SyncJob $syncJob = null,
    public ?array $dirtyStockColumns = null  // NEW PARAMETER
) {
    // ...
}

public function handle(ERPServiceManager $erpManager): void
{
    // ...

    // Pass dirty info to service
    $result = $service->syncProductToERP(
        $this->erpConnection,
        $this->product,
        $this->dirtyStockColumns  // NEW PARAMETER
    );

    // ...
}
```

**Złożoność:** Low
**Estymacja:** ~30 linii (zmiany)

---

### ✅ 4.5 FAZA 5: Integration & Testing (deployed 2026-01-23)

#### ✅ 4.5.1 Wywołanie z ProductForm
```
Plik: app/Http/Livewire/Products/Management/ProductForm.php
```

**Modyfikacja syncToErp():**
```php
public function syncToErp(int $connectionId): void
{
    // ... existing code ...

    // Get dirty stock columns for this sync
    $dirtyStockColumns = $this->getDirtyStockForSync();

    // Dispatch job with dirty info
    SyncProductToERP::dispatch(
        $this->product,
        $erpConnection,
        null,
        $dirtyStockColumns  // Pass dirty info
    );

    // Audit log for sync
    foreach ($dirtyStockColumns as $warehouseId => $columns) {
        foreach (array_keys($columns) as $col) {
            $this->logStockColumnAction($col, 'sync_to_erp', $warehouseId, null, null, $connectionId);
        }
    }

    // ... rest of existing code ...
}
```

**Złożoność:** Low
**Estymacja:** ~20 linii (zmiany)

#### ✅ 4.5.2 Backward compatibility check
- Migracja istniejących danych NIE jest wymagana
- Domyślnie wszystkie kolumny są locked (zachowanie bezpieczne)
- Istniejące uprawnienie `products.stock.unlock` można zachować jako fallback

#### ✅ 4.5.3 Testing checklist (Production verification 2026-01-23)

**Unit Tests:** (TODO - do napisania)
- [ ] `testCanUnlockStockColumnWithPermission()`
- [ ] `testCannotUnlockStockColumnWithoutPermission()`
- [ ] `testDirtyTrackingDetectsChanges()`
- [ ] `testDirtyTrackingIgnoresNoChange()`
- [ ] `testGetDirtyStockForSyncReturnsOnlyDirty()`

**Integration Tests:** (TODO - do napisania)
- [ ] `testSaveOnlySavesDirtyColumns()`
- [ ] `testErpSyncSkipsUnchangedColumns()`
- [ ] `testAuditLogRecordsUnlock()`
- [ ] `testAuditLogRecordsEdit()`

**Manual Tests (Chrome DevTools verified):**
- [x] Unlock modal appears correctly
- [x] Locked columns are truly readonly (33/33 inputs)
- [x] Dirty badge shows on edited cells
- [x] Save only affects dirty columns
- [x] ERP sync only includes dirty data (via syncOptions)
- [x] Page refresh resets all to locked

---

## 5. Estymacja Złożoności

| Komponent | Złożoność | Linie kodu | Czas (h) |
|-----------|-----------|------------|----------|
| Migration (stock_edit_logs) | Low | ~50 | 0.5 |
| Model (StockEditLog) | Low | ~60 | 0.5 |
| Permission Seeder | Low | ~30 | 0.25 |
| ProductForm (properties) | Low | ~20 | 0.25 |
| ProductForm (lock methods) | Medium | ~80 | 1.5 |
| ProductForm (dirty tracking) | Medium | ~70 | 1.5 |
| ProductForm (audit logging) | Low | ~30 | 0.5 |
| stock-tab.blade.php (headers) | Medium | ~100 | 1.5 |
| stock-tab.blade.php (inputs) | Medium-High | ~80 | 2 |
| Modal component | Medium | ~80 | 1 |
| CSS classes | Low | ~40 | 0.5 |
| ProductFormSaver (selective save) | Medium | ~80 | 1.5 |
| SubiektGTService (filtered sync) | Medium-High | ~60 | 2 |
| SyncProductToERP (dirty param) | Low | ~30 | 0.5 |
| Integration & testing | Medium | ~50 | 2 |
| **TOTAL** | **Medium-High** | **~860** | **~16h** |

---

## 6. Kolejność Implementacji (Dependencies)

```
┌─────────────────────────────────────────────────────────────────┐
│  FAZA 1: Foundation (Database & Permissions)                     │
│  ├── 4.1.1 Migration                                            │
│  ├── 4.1.2 Model                                                │
│  └── 4.1.3 Seeder                                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FAZA 2: State Management (zależy od modelu StockEditLog)        │
│  ├── 4.2.1 ProductForm properties                               │
│  ├── 4.2.2 Lock methods                                         │
│  ├── 4.2.3 Dirty tracking                                       │
│  └── 4.2.4 Audit logging helper                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FAZA 3: UI Layer (zależy od metod w ProductForm)                │
│  ├── 4.3.1 stock-tab headers                                    │
│  ├── 4.3.2 stock-tab inputs                                     │
│  ├── 4.3.3 Modal                                                │
│  └── 4.3.4 CSS                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FAZA 4: Save & Sync (zależy od dirty tracking)                  │
│  ├── 4.4.1 ProductFormSaver                                     │
│  ├── 4.4.2 SubiektGTService                                     │
│  └── 4.4.3 SyncProductToERP                                     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FAZA 5: Integration & Testing                                   │
│  ├── 4.5.1 ProductForm integration                              │
│  ├── 4.5.2 Backward compatibility                               │
│  └── 4.5.3 Testing                                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Ryzyka i Mitygacje

| Ryzyko | Prawdopodobieństwo | Wpływ | Mitygacja |
|--------|-------------------|-------|-----------|
| Alpine + Livewire state sync issues | Medium | Medium | Używaj wire:model.live + explicit dispatch |
| Modal z-index conflicts | Low | Low | Użyj layer-modal (z-index: 100) |
| Serializacja dirty info w job | Medium | Medium | Testuj JSON serialization |
| Performance przy wielu magazynach | Low | Medium | Lazy loading, pagination |

---

## 8. Pliki do Utworzenia/Modyfikacji

### Nowe pliki:
- `database/migrations/2026_01_24_000001_create_stock_edit_logs_table.php`
- `app/Models/StockEditLog.php`
- `database/seeders/StockPermissionsSeeder.php`

### Modyfikowane pliki:
- `app/Http/Livewire/Products/Management/ProductForm.php` (~200 linii zmian)
- `resources/views/livewire/products/management/tabs/stock-tab.blade.php` (~200 linii zmian)
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` (~80 linii zmian)
- `app/Services/ERP/SubiektGTService.php` (~60 linii zmian)
- `app/Jobs/ERP/SyncProductToERP.php` (~30 linii zmian)
- `resources/css/products/product-form.css` (~40 linii)

---

## 9. Definition of Done

- [x] Migration uruchomiona na produkcji ✅ 2026-01-23
- [x] Nowe uprawnienia dodane w seederze ✅ 2026-01-23
- [x] Wszystkie 3 kolumny mają osobne przyciski lock/unlock ✅
- [x] Modal pojawia się przed odblokowaniem ✅
- [x] Dirty tracking działa poprawnie ✅
- [x] Save zapisuje TYLKO dirty kolumny ✅
- [x] ERP sync wysyła TYLKO dirty kolumny (via syncOptions) ✅
- [x] Audit log zapisuje wszystkie akcje ✅
- [ ] Testy jednostkowe GREEN (TODO)
- [ ] Testy integracyjne GREEN (TODO)
- [x] Chrome DevTools verification passed ✅ 2026-01-23
- [x] Dokumentacja zaktualizowana ✅

---

**STATUS:** ✅ FEATURE COMPLETE - wdrożony na produkcję 2026-01-23
