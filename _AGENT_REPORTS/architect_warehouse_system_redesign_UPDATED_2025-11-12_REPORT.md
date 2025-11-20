# ARCHITECTURE REPORT: Przeprojektowanie Systemu Magazynów PPM (UPDATED)

**Original Date**: 2025-11-07
**Updated Date**: 2025-11-12
**Agent**: Planning Manager & Project Plan Keeper
**Status**: ✅ **UPDATED WITH UI MODIFICATIONS**

---

## 📋 CHANGE LOG

### What's New in This Update?

**User Modifications Applied:**
1. ✅ **Shop Add Wizard Integration** - Warehouse creation during shop setup (not after first import)
2. ✅ **Custom Warehouse Management** - CRUD interface for standalone warehouses
3. ✅ **Extended UI Timeline** - Phase 4 increased from 5h to 8h
4. ✅ **New Files** - Additional Livewire components and routes
5. ✅ **Updated Success Criteria** - Added UI verification steps

**What Remains Unchanged:**
- ✅ Core architecture (MPPTRADE master warehouse)
- ✅ Inherit/Pull mode logic
- ✅ Database schema
- ✅ Service layer design
- ✅ Job workflow
- ✅ Rollback plan

---

## 📋 EXECUTIVE SUMMARY

### Cel Projektu
Całkowita przebudowa systemu magazynów PPM z obecnego modelu statycznego (6 predefiniowanych magazynów) na dynamiczny model zorientowany na sklepy PrestaShop z inteligentnym dziedziczeniem stanów magazynowych.

### Główne Zmiany
1. **MPPTRADE** staje się jedynym stałym magazynem (Master Warehouse)
2. **Wszystkie pozostałe statyczne magazyny USUWANE** (Pitbike, Cameraman, Otopit, INFMS, Reklamacje)
3. **Dynamiczne magazyny** tworzone:
   - ✅ **AUTO** przy dodawaniu nowego sklepu PrestaShop (Add Shop Wizard)
   - ✅ **MANUAL** dla custom warehouses nie powiązanych ze sklepem
4. **Dwa tryby synchronizacji**:
   - **Inherit FROM MASTER** → PPM (MPPTRADE) jest master, sklepy dziedziczą stany
   - **Pull FROM SHOP** → PrestaShop jest master, PPM pobiera stany co 30 min (cron)

### Korzyści
- ✅ **Automatyzacja**: Magazyny tworzone automatycznie w Shop Wizard
- ✅ **Elastyczność**: Toggle per sklep (inherit vs pull)
- ✅ **Czytelność**: Jawna relacja magazyn ↔ sklep PrestaShop
- ✅ **Skalowalność**: Nieograniczona liczba sklepów bez zmian w kodzie
- ✅ **Rozszerzalność**: Możliwość dodawania własnych magazynów (custom)
- ✅ **Data Integrity**: Jasny master/slave relationship

### Zakres Pracy
- 2 migracje bazy danych
- 2 nowe service classes
- 1 nowy job + modyfikacje 2 istniejących
- **NEW:** Add Shop Wizard - nowy step "Warehouse Configuration"
- **NEW:** Warehouse CRUD Livewire components
- Zmiany UI w 5 miejscach (było 3)
- Seeder updates
- Tests updates

**Szacowany czas implementacji**: ~21 godzin (było 18h)

---

## 🆕 USER MODIFICATIONS TO ORIGINAL PLAN

### Original Plan (2025-11-07) Zakładał:

**Warehouse Creation:**
- Dynamiczne magazyny tworzone automatycznie przy **PIERWSZYM IMPORCIE** z PrestaShop
- Brak explicit UI dla dodawania magazynów

**What User Requested:**

### 1. Magazyny PrestaShop Dodawane na Etapie Tworzenia Integracji

**Location:** `/admin/shops/add` (Add Shop Wizard)

**Automatic:** Przy dodawaniu nowego sklepu PrestaShop → automatycznie tworzony magazyn dla tego sklepu

**UI:** Nowa sekcja w wizard "Konfiguracja Magazynu" (step 3 lub 4)

**Benefits:**
- ✅ Użytkownik widzi magazyn od razu po dodaniu sklepu
- ✅ Brak niespodzianek "gdzie się wziął ten magazyn?" po pierwszym imporcie
- ✅ Jawna konfiguracja inherit mode podczas setup
- ✅ Możliwość edycji nazwy magazynu przed utworzeniem

### 2. Możliwość Dodawania "Własnego" Magazynu Nie Powiązanego ze Sklepem

**Location:** `/admin/warehouses/add` (nowy route)

**Use Cases:**
- Custom warehouses (np. "Magazyn Reklamacje", "Magazyn Tymczasowy")
- Warehouses bez powiązania z PrestaShop (manual stock management)
- Dedicated warehouses dla specific business needs

**UI:** Pełny CRUD interface dla custom warehouses

**Benefits:**
- ✅ Elastyczność dla advanced use cases
- ✅ Nie jesteśmy ograniczeni tylko do shop warehouses
- ✅ User może tworzyć dowolne magazyny dla własnych potrzeb

### 3. Aktualizacja UI z Nowymi Funkcjami

**Shop Add Wizard:**
- Nowy step "Warehouse Configuration" w wizardzie dodawania sklepu
- Auto-create checkbox (domyślnie zaznaczony)
- Edytowalna nazwa magazynu
- Inherit from master checkbox

**Warehouse Management:**
- CRUD interface dla custom warehouses
- Warehouse list z filtrami (type, status, shop linkage)
- Edit/Delete dla custom warehouses
- Cannot delete MPPTRADE lub shop-linked warehouses

**Product Form:**
- Dropdown z dynamiczną listą magazynów (MPPTRADE + shop warehouses + custom)
- Read-only logic dla inherited/pulled warehouses
- Status badges (Synced, Pulled, Manual)

---

## 🏗️ CURRENT vs NEW ARCHITECTURE

### CURRENT ARCHITECTURE (TO BE REMOVED)

```
┌─────────────────────────────────────────────────────┐
│               WAREHOUSES TABLE                       │
├─────────────────────────────────────────────────────┤
│ ✓ MPPTRADE (code: mpptrade, is_default: true)      │
│ ✓ Pitbike.pl (code: pitbike)                       │
│ ✓ Cameraman (code: cameraman)                      │
│ ✓ Otopit (code: otopit)                            │
│ ✓ INFMS (code: infms)                              │
│ ✓ Reklamacje (code: returns)                       │
└─────────────────────────────────────────────────────┘
                      ↓
         ┌──────────────────────────┐
         │   PRODUCT_STOCK TABLE    │
         ├──────────────────────────┤
         │ product_id + warehouse_id│
         │ quantity                 │
         │ reserved_quantity        │
         │ available_quantity       │
         └──────────────────────────┘
```

**PROBLEMS:**
- ❌ Brak powiązania magazyn ↔ sklep PrestaShop
- ❌ Wszystkie magazyny są statyczne (hardcoded w seederze)
- ❌ Brak logiki dziedziczenia stanów
- ❌ Brak automatycznej synchronizacji z PrestaShop
- ❌ Nieczytelne mapowanie (warehouse.prestashop_mapping JSON)

---

### NEW ARCHITECTURE (DYNAMIC & SCALABLE)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        WAREHOUSES TABLE (NEW)                            │
├─────────────────────────────────────────────────────────────────────────┤
│ ✓ MPPTRADE (code: mpptrade, is_master: TRUE, shop_id: NULL)            │
│   └─ Główny magazyn PPM (Single Source of Truth)                       │
│                                                                          │
│ ✓ Pitbike.pl Warehouse (code: shop_1_warehouse, shop_id: 1)           │
│   ├─ inherit_from_master: TRUE ☑                                       │
│   └─ Created DURING shop setup (Add Shop Wizard)                       │
│                                                                          │
│ ✓ Cameraman.pl Warehouse (code: shop_2_warehouse, shop_id: 2)         │
│   ├─ inherit_from_master: FALSE ☐                                      │
│   └─ Created DURING shop setup (Add Shop Wizard)                       │
│                                                                          │
│ ✓ Magazyn Reklamacje (code: magazyn-reklamacje, shop_id: NULL)        │
│   ├─ inherit_from_master: TRUE ☑                                       │
│   └─ Created MANUALLY (Custom Warehouse)                               │
└─────────────────────────────────────────────────────────────────────────┘
```

#### NEW FIELDS (warehouses table)
```sql
is_master BOOLEAN DEFAULT FALSE           -- MPPTRADE = TRUE
shop_id BIGINT NULLABLE (FK → prestashop_shops)  -- NULL dla MPPTRADE/custom, NOT NULL dla shop warehouses
inherit_from_master BOOLEAN DEFAULT FALSE  -- Toggle dziedziczenia stanów
```

---

## 🔄 UPDATED WORKFLOW DIAGRAMS

### WORKFLOW A: Creating Shop with Warehouse

```
USER VISITS /admin/shops/add
         ↓
┌────────────────────────────────────────┐
│ STEP 1: Basic Info                     │
│ - Shop name                            │
│ - Shop URL                             │
│ - API credentials                      │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ STEP 2: Connection Test                │
│ - Test API connection                  │
│ - Validate credentials                 │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ STEP 3: Warehouse Configuration (NEW)  │
│ ☑ Auto-create warehouse                │
│ ✏ Warehouse name: "Pitbike.pl Mag..."│
│ ☑ Inherit from MPPTRADE                │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ STEP 4: Category Mapping               │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ STEP 5: Summary & Create               │
│ - Review all settings                  │
│ - Create shop + warehouse              │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ SUCCESS:                               │
│ - Shop created (id: 1)                 │
│ - Warehouse created (shop_1_warehouse) │
│ - Inherit mode: TRUE                   │
└────────────────────────────────────────┘
```

---

### WORKFLOW B: Creating Custom Warehouse

```
USER VISITS /admin/warehouses/create
         ↓
┌────────────────────────────────────────┐
│ Custom Warehouse Form                  │
│                                        │
│ Name: "Magazyn Reklamacje"            │
│ Code: "magazyn-reklamacje"            │
│ Description: "Warehouse for returns"   │
│                                        │
│ ☐ Is Master (disabled - MPPTRADE)    │
│ ☑ Inherit from Master                 │
│ Linked Shop: [None] (optional)        │
│ ☑ Active                               │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ VALIDATION:                            │
│ - Code unique ✓                        │
│ - Name not empty ✓                     │
│ - Cannot set is_master (only 1) ✓     │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ CREATE WAREHOUSE:                      │
│ Warehouse::create([                    │
│   'name' => 'Magazyn Reklamacje',     │
│   'code' => 'magazyn-reklamacje',     │
│   'shop_id' => null,                  │
│   'is_master' => false,               │
│   'inherit_from_master' => true,      │
│ ])                                     │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ SUCCESS:                               │
│ - Custom warehouse created             │
│ - Available in Product Form dropdown   │
│ - Inherits stock from MPPTRADE         │
└────────────────────────────────────────┘
```

---

### WORKFLOW C: Inherit FROM MASTER = TRUE (☑)

```
USER EDITS PRODUCT IN PPM
         ↓
┌────────────────────────────────────────┐
│ User saves product in PPM              │
│ Updates product_stock for MPPTRADE     │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ AUTO DISPATCH JOB:                     │
│ SyncStockToPrestaShop                  │
│ (for EACH shop/custom warehouse with   │
│  inherit=TRUE)                         │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 1. Get stock from MPPTRADE warehouse   │
│    (quantity = 100)                    │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 2. Copy to Target warehouses:          │
│    - Shop 1 warehouse (quantity = 100) │
│    - Shop 2 warehouse (quantity = 100) │
│    - Custom warehouse (quantity = 100) │
│    (All warehouses READ-ONLY)          │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 3. Sync to PrestaShop API (if shop_id) │
│    PUT /api/stock_availables/{id}      │
│    <quantity>100</quantity>            │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ PrestaShop shops display: 100 items    │
│ Custom warehouses display: 100 items   │
└────────────────────────────────────────┘
```

---

### WORKFLOW D: Pull FROM SHOP = FALSE (☐)

```
CRON JOB (every 30 minutes)
         ↓
┌────────────────────────────────────────┐
│ PullStockFromPrestaShop Cron           │
│ (for shop warehouses with inherit=FALSE)│
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 1. Fetch stock from PrestaShop API     │
│    GET /api/stock_availables/{id}      │
│    <quantity>75</quantity>             │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ 2. Update Shop warehouse in PPM        │
│    product_stock.quantity = 75         │
│    (Shop warehouse HAS OWN stock)      │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│ PPM displays shop stock: 75 items      │
│ (READ-ONLY dla user - nie można        │
│  edytować, sklep jest master)          │
└────────────────────────────────────────┘
```

**REGUŁY:**
- Sklep jest master, PPM jest slave
- PPM **NIE MODYFIKUJE** stanów w tym sklepie (tylko READ)
- User może **CZYTAĆ** stany w PPM, ale nie może ich zmieniać
- Sync jest **UNIDIRECTIONAL**: Shop → PPM (PrestaShop is master)

---

## 🆕 SHOP ADD WIZARD - WAREHOUSE CONFIGURATION

### Location: `/admin/shops/add`

**Existing Steps:**
1. Basic Info (shop name, URL, API key)
2. Connection Test
3. **[NEW] Warehouse Configuration**
4. Category Mapping
5. Summary

---

### NEW STEP 3: Warehouse Configuration

**UI Components:**

#### 1. Auto-Create Warehouse (default: checked)

```blade
<div class="form-group">
    <label class="flex items-center">
        <input type="checkbox"
               wire:model.live="autoCreateWarehouse"
               checked
               class="form-checkbox">
        <span class="ml-2 font-semibold">Automatycznie utwórz magazyn dla tego sklepu</span>
    </label>
    <p class="text-sm text-gray-500 mt-1">
        Magazyn zostanie utworzony z nazwą: "{{ $shopName }} Warehouse"
    </p>
</div>
```

#### 2. Warehouse Name (editable)

```blade
<div class="form-group" x-show="autoCreateWarehouse">
    <label for="warehouseName" class="form-label">Nazwa magazynu</label>
    <input type="text"
           id="warehouseName"
           wire:model.live="warehouseName"
           placeholder="Pitbike.pl Warehouse"
           class="form-control"
           maxlength="255">
    <small class="form-text text-muted">
        Jeśli pozostawisz puste, zostanie użyta domyślna nazwa
    </small>
</div>
```

#### 3. Inherit From Master (checkbox)

```blade
<div class="form-group" x-show="autoCreateWarehouse">
    <label class="flex items-center">
        <input type="checkbox"
               wire:model="inheritFromMaster"
               checked
               class="form-checkbox">
        <span class="ml-2 font-semibold">Dziedzicz stany magazynowe z MPPTRADE</span>
    </label>
    <div class="alert alert-info mt-2">
        <strong>ℹ️ Tryby synchronizacji:</strong>
        <ul class="list-disc ml-5 mt-1">
            <li>
                <strong>Zaznaczone (Inherit):</strong>
                PPM (MPPTRADE) jest master, sklep synchronizuje stany z PPM<br>
                <small>→ Edytujesz stany w PPM → automatycznie synchronizowane do PrestaShop</small>
            </li>
            <li>
                <strong>Odznaczone (Pull):</strong>
                PrestaShop jest master, PPM pobiera stany co 30 min (cron)<br>
                <small>→ Edytujesz stany w PrestaShop → PPM automatycznie pobiera co 30 min</small>
            </li>
        </ul>
    </div>
</div>
```

---

### Backend Logic (AddShop.php Livewire Component)

**File:** `app/Http/Livewire/Admin/Shops/AddShop.php`

```php
<?php

namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use App\Models\PrestaShopShop;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AddShop extends Component
{
    // Shop properties
    public string $name = '';
    public string $url = '';
    public string $apiKey = '';

    // Warehouse properties (NEW)
    public bool $autoCreateWarehouse = true;
    public string $warehouseName = '';
    public bool $inheritFromMaster = true;

    // Wizard state
    public int $currentStep = 1;

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'apiKey' => 'required|string|max:255',
            'autoCreateWarehouse' => 'boolean',
            'warehouseName' => 'nullable|string|max:255',
            'inheritFromMaster' => 'boolean',
        ];
    }

    /**
     * Create shop and warehouse
     */
    public function createShop(): void
    {
        $this->validate();

        // 1. Create PrestaShop shop
        $shop = PrestaShopShop::create([
            'name' => $this->name,
            'url' => $this->url,
            'api_key' => $this->apiKey,
            'is_active' => true,
        ]);

        Log::info('PrestaShop shop created', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        // 2. Create warehouse if auto-create enabled
        if ($this->autoCreateWarehouse) {
            $this->createWarehouseForShop($shop);
        }

        // 3. Redirect to shop list
        session()->flash('success', "Sklep '{$shop->name}' został dodany pomyślnie!");

        return redirect()->route('admin.shops.index');
    }

    /**
     * Create warehouse for shop
     */
    protected function createWarehouseForShop(PrestaShopShop $shop): void
    {
        // Determine warehouse name
        $name = $this->warehouseName ?: "{$shop->name} Warehouse";

        // Generate warehouse code
        $code = Str::slug($name);

        // Ensure code is unique
        $originalCode = $code;
        $counter = 1;
        while (Warehouse::where('code', $code)->exists()) {
            $code = "{$originalCode}-{$counter}";
            $counter++;
        }

        // Create warehouse
        $warehouse = Warehouse::create([
            'name' => $name,
            'code' => $code,
            'shop_id' => $shop->id,
            'is_master' => false,  // Tylko MPPTRADE jest master
            'is_default' => false,
            'inherit_from_master' => $this->inheritFromMaster,
            'is_active' => true,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 0,
            'sort_order' => 1000 + $shop->id, // Shop warehouses na końcu listy
            'notes' => "Automatically created during shop setup for: {$shop->name}",
        ]);

        Log::info('Warehouse auto-created for shop', [
            'shop_id' => $shop->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_code' => $warehouse->code,
            'inherit_from_master' => $warehouse->inherit_from_master,
        ]);

        session()->flash('success', "Magazyn '{$warehouse->name}' został utworzony dla sklepu '{$shop->name}'!");
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.shops.add-shop-wizard');
    }
}
```

---

### Blade View (Shop Wizard Step 3)

**File:** `resources/views/livewire/admin/shops/add-shop-wizard.blade.php`

```blade
<div class="shop-add-wizard">
    <!-- Progress indicator -->
    <div class="wizard-progress">
        <div class="step {{ $currentStep >= 1 ? 'active' : '' }}">1. Basic Info</div>
        <div class="step {{ $currentStep >= 2 ? 'active' : '' }}">2. Connection Test</div>
        <div class="step {{ $currentStep >= 3 ? 'active' : '' }}">3. Warehouse Configuration</div>
        <div class="step {{ $currentStep >= 4 ? 'active' : '' }}">4. Category Mapping</div>
        <div class="step {{ $currentStep >= 5 ? 'active' : '' }}">5. Summary</div>
    </div>

    <!-- Step content -->
    @if($currentStep === 3)
        <div class="wizard-step">
            <h2>Warehouse Configuration</h2>
            <p class="text-muted mb-4">
                Skonfiguruj magazyn dla tego sklepu PrestaShop. Magazyn będzie używany do zarządzania stanami magazynowymi.
            </p>

            <!-- Auto-create checkbox -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox"
                           wire:model.live="autoCreateWarehouse"
                           checked
                           class="form-checkbox">
                    <span class="font-semibold">Automatycznie utwórz magazyn dla tego sklepu</span>
                </label>
                <small class="form-text text-muted">
                    Magazyn zostanie utworzony z nazwą: "{{ $name }} Warehouse"
                </small>
            </div>

            @if($autoCreateWarehouse)
                <!-- Warehouse name -->
                <div class="form-group">
                    <label for="warehouseName" class="form-label">Nazwa magazynu</label>
                    <input type="text"
                           id="warehouseName"
                           wire:model.live="warehouseName"
                           placeholder="{{ $name }} Warehouse"
                           class="form-control"
                           maxlength="255">
                    <small class="form-text text-muted">
                        Jeśli pozostawisz puste, zostanie użyta domyślna nazwa
                    </small>
                </div>

                <!-- Inherit from master -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox"
                               wire:model="inheritFromMaster"
                               checked
                               class="form-checkbox">
                        <span class="font-semibold">Dziedzicz stany magazynowe z MPPTRADE</span>
                    </label>

                    <div class="alert alert-info mt-3">
                        <h4 class="alert-heading">ℹ️ Tryby synchronizacji:</h4>
                        <ul class="list-disc ml-5 mt-2">
                            <li class="mb-2">
                                <strong>Zaznaczone (Inherit Mode):</strong><br>
                                PPM (MPPTRADE) jest master, sklep synchronizuje stany z PPM<br>
                                <small class="text-muted">
                                    → Edytujesz stany w PPM → automatycznie synchronizowane do PrestaShop
                                </small>
                            </li>
                            <li>
                                <strong>Odznaczone (Pull Mode):</strong><br>
                                PrestaShop jest master, PPM pobiera stany co 30 min (cron)<br>
                                <small class="text-muted">
                                    → Edytujesz stany w PrestaShop → PPM automatycznie pobiera co 30 min
                                </small>
                            </li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    ⚠️ Magazyn NIE zostanie utworzony automatycznie. Będziesz musiał dodać magazyn ręcznie w ustawieniach sklepu.
                </div>
            @endif

            <!-- Navigation buttons -->
            <div class="wizard-navigation">
                <button type="button"
                        wire:click="previousStep"
                        class="btn btn-secondary">
                    ← Poprzedni krok
                </button>
                <button type="button"
                        wire:click="nextStep"
                        class="btn btn-primary">
                    Następny krok →
                </button>
            </div>
        </div>
    @endif
</div>
```

---

### Validation Rules (Step 3)

```php
/**
 * Validate warehouse configuration step
 */
protected function validateWarehouseStep(): void
{
    $rules = [
        'autoCreateWarehouse' => 'required|boolean',
    ];

    // If auto-create enabled, validate warehouse name
    if ($this->autoCreateWarehouse) {
        $rules['warehouseName'] = 'nullable|string|max:255';
        $rules['inheritFromMaster'] = 'required|boolean';
    }

    $this->validate($rules);
}
```

---

## 🆕 CUSTOM WAREHOUSE MANAGEMENT

### Location: `/admin/warehouses`

---

### New Routes

**File:** `routes/web.php`

```php
<?php

use App\Http\Livewire\Admin\Warehouses\WarehouseList;
use App\Http\Livewire\Admin\Warehouses\WarehouseForm;

Route::group(['prefix' => 'admin/warehouses', 'middleware' => ['auth', 'can:manage_warehouses']], function () {
    Route::get('/', WarehouseList::class)->name('admin.warehouses.index');
    Route::get('/create', WarehouseForm::class)->name('admin.warehouses.create');
    Route::get('/{id}/edit', WarehouseForm::class)->name('admin.warehouses.edit');
});
```

---

### UI: Warehouse List

**File:** `app/Http/Livewire/Admin/Warehouses/WarehouseList.php`

```php
<?php

namespace App\Http\Livewire\Admin\Warehouses;

use Livewire\Component;
use App\Models\Warehouse;

class WarehouseList extends Component
{
    // Filters
    public string $typeFilter = 'all'; // all, master, shop, custom
    public string $statusFilter = 'all'; // all, active, inactive
    public string $searchTerm = '';

    /**
     * Delete warehouse
     */
    public function deleteWarehouse(int $warehouseId): void
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        // Validate can delete
        if ($warehouse->is_master) {
            session()->flash('error', 'Nie możesz usunąć magazynu głównego (MPPTRADE)!');
            return;
        }

        if ($warehouse->shop_id !== null) {
            session()->flash('error', 'Nie możesz usunąć magazynu powiązanego ze sklepem! Usuń najpierw sklep.');
            return;
        }

        if ($warehouse->stock()->exists()) {
            session()->flash('error', 'Nie możesz usunąć magazynu ze stanami magazynowymi! Opróżnij magazyn najpierw.');
            return;
        }

        // Delete warehouse
        $warehouse->delete();

        session()->flash('success', "Magazyn '{$warehouse->name}' został usunięty pomyślnie!");
    }

    /**
     * Render component
     */
    public function render()
    {
        $query = Warehouse::query();

        // Apply type filter
        if ($this->typeFilter === 'master') {
            $query->where('is_master', true);
        } elseif ($this->typeFilter === 'shop') {
            $query->whereNotNull('shop_id');
        } elseif ($this->typeFilter === 'custom') {
            $query->whereNull('shop_id')->where('is_master', false);
        }

        // Apply status filter
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        // Apply search
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->searchTerm}%")
                  ->orWhere('code', 'like', "%{$this->searchTerm}%");
            });
        }

        $warehouses = $query->with('shop')->orderBy('sort_order')->get();

        return view('livewire.admin.warehouses.warehouse-list', [
            'warehouses' => $warehouses,
        ]);
    }
}
```

---

### Blade View: Warehouse List

**File:** `resources/views/livewire/admin/warehouses/warehouse-list.blade.php`

```blade
<div class="warehouse-list-container">
    <div class="page-header">
        <h1>Warehouse Management</h1>
        <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
            + Dodaj Własny Magazyn
        </a>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <div class="filter-group">
            <label>Type:</label>
            <select wire:model.live="typeFilter" class="form-select">
                <option value="all">All</option>
                <option value="master">Master (MPPTRADE)</option>
                <option value="shop">Shop Warehouses</option>
                <option value="custom">Custom Warehouses</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Status:</label>
            <select wire:model.live="statusFilter" class="form-select">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search:</label>
            <input type="text"
                   wire:model.live.debounce.300ms="searchTerm"
                   placeholder="Search by name or code..."
                   class="form-control">
        </div>
    </div>

    <!-- Warehouse Table -->
    <table class="table enterprise-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Linked Shop</th>
                <th>Inherit From Master</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($warehouses as $warehouse)
                <tr class="{{ $warehouse->is_master ? 'warehouse-master' : ($warehouse->shop_id ? 'warehouse-shop' : 'warehouse-custom') }}">
                    <td>{{ $warehouse->id }}</td>
                    <td>
                        <strong>{{ $warehouse->name }}</strong>
                        @if($warehouse->is_master)
                            <span class="badge badge-primary ml-2">MASTER</span>
                        @endif
                    </td>
                    <td><code>{{ $warehouse->code }}</code></td>
                    <td>
                        @if($warehouse->is_master)
                            <span class="badge badge-primary">Master</span>
                        @elseif($warehouse->shop_id)
                            <span class="badge badge-info">Shop Warehouse</span>
                        @else
                            <span class="badge badge-secondary">Custom</span>
                        @endif
                    </td>
                    <td>
                        @if($warehouse->shop)
                            <a href="{{ route('admin.shops.edit', $warehouse->shop_id) }}">
                                {{ $warehouse->shop->name }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">
                        @if($warehouse->inherit_from_master)
                            <span class="badge badge-success">✓ Yes</span>
                        @else
                            <span class="badge badge-secondary">✗ No</span>
                        @endif
                    </td>
                    <td>
                        @if($warehouse->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-buttons">
                            @if(!$warehouse->is_master && !$warehouse->shop_id)
                                <!-- Custom warehouse: can edit/delete -->
                                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}"
                                   class="btn btn-sm btn-primary">
                                    Edit
                                </a>
                                <button type="button"
                                        wire:click="deleteWarehouse({{ $warehouse->id }})"
                                        wire:confirm="Are you sure you want to delete this warehouse?"
                                        class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            @elseif($warehouse->shop_id)
                                <!-- Shop warehouse: edit via shop settings -->
                                <a href="{{ route('admin.shops.edit', $warehouse->shop_id) }}"
                                   class="btn btn-sm btn-secondary">
                                    View Shop
                                </a>
                            @else
                                <!-- Master warehouse: view only -->
                                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}"
                                   class="btn btn-sm btn-secondary">
                                    View
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        No warehouses found matching your filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

---

### UI: Warehouse Form (Create/Edit)

**File:** `app/Http/Livewire/Admin/Warehouses/WarehouseForm.php`

```php
<?php

namespace App\Http\Livewire\Admin\Warehouses;

use Livewire\Component;
use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use Illuminate\Support\Str;

class WarehouseForm extends Component
{
    public ?int $warehouseId = null;
    public Warehouse $warehouse;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public bool $isMaster = false;
    public bool $inheritFromMaster = false;
    public ?int $shopId = null;
    public bool $isActive = true;

    // State
    public bool $mpptradExists = false;
    public bool $isEditMode = false;

    /**
     * Mount component
     */
    public function mount(?int $id = null): void
    {
        $this->warehouseId = $id;
        $this->isEditMode = $id !== null;

        // Check if MPPTRADE exists
        $this->mpptradExists = Warehouse::where('is_master', true)->exists();

        // Load warehouse if editing
        if ($this->isEditMode) {
            $this->warehouse = Warehouse::findOrFail($id);
            $this->name = $this->warehouse->name;
            $this->code = $this->warehouse->code;
            $this->description = $this->warehouse->notes ?? '';
            $this->isMaster = $this->warehouse->is_master;
            $this->inheritFromMaster = $this->warehouse->inherit_from_master;
            $this->shopId = $this->warehouse->shop_id;
            $this->isActive = $this->warehouse->is_active;
        }
    }

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|regex:/^[a-z0-9-]+$/|unique:warehouses,code,' . $this->warehouseId,
            'description' => 'nullable|string|max:1000',
            'isMaster' => 'boolean',
            'inheritFromMaster' => 'boolean',
            'shopId' => 'nullable|exists:prestashop_shops,id',
            'isActive' => 'boolean',
        ];
    }

    /**
     * Validation messages
     */
    protected function messages(): array
    {
        return [
            'code.regex' => 'Code must contain only lowercase letters, numbers, and hyphens.',
            'code.unique' => 'This code is already in use by another warehouse.',
        ];
    }

    /**
     * Save warehouse
     */
    public function save(): void
    {
        $this->validate();

        // Business logic validation
        if ($this->isMaster && $this->inheritFromMaster) {
            session()->flash('error', 'Master warehouse cannot inherit from itself!');
            return;
        }

        if ($this->isMaster && $this->mpptradExists && !$this->isEditMode) {
            session()->flash('error', 'Master warehouse (MPPTRADE) already exists! Only one master warehouse is allowed.');
            return;
        }

        // Create or update
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'notes' => $this->description,
            'is_master' => $this->isMaster,
            'inherit_from_master' => $this->inheritFromMaster,
            'shop_id' => $this->shopId,
            'is_active' => $this->isActive,
            'is_default' => $this->isMaster, // Master is also default
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 0,
            'sort_order' => $this->isMaster ? 0 : ($this->shopId ? 1000 + $this->shopId : 2000),
        ];

        if ($this->isEditMode) {
            $this->warehouse->update($data);
            session()->flash('success', "Warehouse '{$this->name}' updated successfully!");
        } else {
            Warehouse::create($data);
            session()->flash('success', "Warehouse '{$this->name}' created successfully!");
        }

        return redirect()->route('admin.warehouses.index');
    }

    /**
     * Generate code from name
     */
    public function updatedName(string $value): void
    {
        if (!$this->isEditMode && empty($this->code)) {
            $this->code = Str::slug($value);
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        $availableShops = PrestaShopShop::where('is_active', true)
            ->whereDoesntHave('warehouse') // Only shops without warehouse
            ->get();

        return view('livewire.admin.warehouses.warehouse-form', [
            'availableShops' => $availableShops,
        ]);
    }
}
```

---

### Blade View: Warehouse Form

**File:** `resources/views/livewire/admin/warehouses/warehouse-form.blade.php`

```blade
<div class="warehouse-form-container">
    <div class="page-header">
        <h1>{{ $isEditMode ? 'Edit Warehouse' : 'Create Custom Warehouse' }}</h1>
    </div>

    <form wire:submit.prevent="save">
        <div class="card">
            <div class="card-body">
                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label required">Warehouse Name</label>
                    <input type="text"
                           id="name"
                           wire:model.live="name"
                           placeholder="Magazyn Reklamacje"
                           class="form-control @error('name') is-invalid @enderror"
                           maxlength="255"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Code -->
                <div class="form-group">
                    <label for="code" class="form-label required">Warehouse Code</label>
                    <input type="text"
                           id="code"
                           wire:model.live="code"
                           placeholder="magazyn-reklamacje"
                           class="form-control @error('code') is-invalid @enderror"
                           maxlength="50"
                           required>
                    <small class="form-text text-muted">
                        Używaj tylko małych liter, cyfr i myślników (a-z, 0-9, -)
                    </small>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description"
                              wire:model="description"
                              rows="3"
                              placeholder="Optional description for this warehouse..."
                              class="form-control"
                              maxlength="1000"></textarea>
                </div>

                <!-- Is Master -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox"
                               wire:model="isMaster"
                               @if($mpptradExists && !$isEditMode) disabled @endif
                               class="form-checkbox">
                        <span class="font-semibold">Magazyn główny (Master)</span>
                    </label>
                    @if($mpptradExists && !$isEditMode)
                        <div class="alert alert-warning mt-2">
                            ⚠️ Magazyn MPPTRADE już istnieje jako master. Tylko jeden magazyn może być master.
                        </div>
                    @endif
                </div>

                <!-- Inherit From Master -->
                <div class="form-group" x-data="{ isMaster: @entangle('isMaster') }">
                    <label class="checkbox-label" x-show="!isMaster">
                        <input type="checkbox"
                               wire:model="inheritFromMaster"
                               class="form-checkbox"
                               x-bind:disabled="isMaster">
                        <span class="font-semibold">Dziedzicz stany z magazynu głównego (MPPTRADE)</span>
                    </label>
                    <small class="form-text text-muted" x-show="!isMaster">
                        Jeśli zaznaczone, stany w tym magazynie będą automatycznie kopiowane z MPPTRADE
                    </small>
                </div>

                <!-- Linked Shop -->
                <div class="form-group">
                    <label for="shopId" class="form-label">Linked Shop (Optional)</label>
                    <select id="shopId"
                            wire:model="shopId"
                            class="form-select">
                        <option value="">-- Brak powiązania --</option>
                        @foreach($availableShops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        Opcjonalne: powiąż magazyn ze sklepem PrestaShop (nie zalecane dla custom warehouses)
                    </small>
                </div>

                <!-- Is Active -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox"
                               wire:model="isActive"
                               checked
                               class="form-checkbox">
                        <span class="font-semibold">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="form-actions">
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                {{ $isEditMode ? 'Update Warehouse' : 'Create Warehouse' }}
            </button>
        </div>
    </form>
</div>
```

---

## 📊 DATABASE SCHEMA CHANGES

### MIGRATION 1: Modify warehouses table

**File**: `database/migrations/2025_11_07_100000_add_master_warehouse_fields.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOWA ARCHITEKTURA: Dynamic shop warehouses z inherit logic
     *
     * Changes:
     * 1. Add is_master field (MPPTRADE = TRUE)
     * 2. Add shop_id FK → prestashop_shops (NULL dla MPPTRADE/custom, NOT NULL dla shop warehouses)
     * 3. Add inherit_from_master toggle (kontroluje sync direction)
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Master warehouse flag (tylko MPPTRADE)
            $table->boolean('is_master')
                ->default(false)
                ->after('is_default')
                ->comment('Główny magazyn PPM (MPPTRADE)');

            // PrestaShop shop association (dynamiczne magazyny)
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->after('code')
                ->comment('PrestaShop shop ID (NULL dla MPPTRADE/custom warehouses)');

            // Inherit logic toggle
            $table->boolean('inherit_from_master')
                ->default(false)
                ->after('shop_id')
                ->comment('TRUE = dziedziczenie z MPPTRADE, FALSE = pull z PrestaShop');

            // Foreign key constraint
            $table->foreign('shop_id')
                ->references('id')
                ->on('prestashop_shops')
                ->onDelete('cascade')
                ->comment('Cascade delete: sklep usunięty → warehouse usunięty');

            // Performance indexes
            $table->index('shop_id', 'idx_warehouses_shop_id');
            $table->index(['is_master', 'is_active'], 'idx_warehouses_master_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['shop_id']);

            // Drop indexes
            $table->dropIndex('idx_warehouses_shop_id');
            $table->dropIndex('idx_warehouses_master_active');

            // Drop columns
            $table->dropColumn(['is_master', 'shop_id', 'inherit_from_master']);
        });
    }
};
```

---

### MIGRATION 2: Data migration (drop old warehouses)

**File**: `database/migrations/2025_11_07_100001_migrate_warehouse_data.php`

*(Same as original plan - no changes needed)*

---

## 🔧 SERVICE LAYER DESIGN

*(Same as original plan - WarehouseFactory and StockInheritanceService remain unchanged)*

---

## 🚀 JOB LAYER DESIGN

*(Same as original plan - SyncStockToPrestaShop, PullStockFromPrestaShop, modifications to existing jobs)*

---

## 📅 UPDATED IMPLEMENTATION PLAN

### PHASE 1: Database (Estimated: 2h)

**NO CHANGES** - Same as original plan

**Tasks:**
1. ✅ Create migration: `2025_11_07_100000_add_master_warehouse_fields.php`
2. ✅ Create migration: `2025_11_07_100001_migrate_warehouse_data.php`
3. ⚠️ **CRITICAL DECISION**: Choose data migration strategy (Strategy A vs B)
4. ✅ Test migrations on local database
5. ✅ Update Warehouse model with new fields
6. ✅ Update WarehouseSeeder (remove old warehouses, keep only MPPTRADE)
7. ✅ Test seeder on fresh database

---

### PHASE 2: Services (Estimated: 4h)

**NO CHANGES** - Same as original plan

**Tasks:**
1. ✅ Create `WarehouseFactory` service
2. ✅ Create `StockInheritanceService` service
3. ✅ Add Warehouse→Shop relationship to models
4. ✅ Modify `PrestaShopStockImporter::mapShopToWarehouse()`
5. ✅ Modify `PrestaShopStockImporter::importStockForProduct()` (add inherit check)
6. ✅ Write unit tests for services

---

### PHASE 3: Jobs (Estimated: 3h)

**NO CHANGES** - Same as original plan

**Tasks:**
1. ✅ Create `SyncStockToPrestaShop` job
2. ✅ Modify `PullProductsFromPrestaShop` job (add inherit check)
3. ✅ Create `PullStockFromPrestaShop` job
4. ✅ Add cron schedule to `routes/console.php`
5. ✅ Write job tests
6. ✅ Test job dispatching and execution

---

### PHASE 4: UI (Estimated: 8h) ⬅️ **UPDATED** (was 5h)

**EXPANDED TASKS:**

#### Original Tasks (5h):
1. ✅ Create warehouse management UI (`admin/warehouses/index.blade.php`)
2. ✅ Add inherit toggle to warehouse list
3. ✅ Update Product Form stock tab (read-only logic)
4. ✅ Add warehouse settings to shop edit page
5. ✅ Create CSS for warehouse UI
6. ✅ Add routes for warehouse management
7. ✅ Create WarehouseController with toggle action
8. ✅ Test UI interactions (toggle, edit, view)

#### New Tasks (3h):
9. **NEW (2h):** Add Shop Wizard - Warehouse Configuration Step
   - Create/modify `AddShop.php` Livewire component
   - Add Step 3 to `add-shop-wizard.blade.php`
   - Implement warehouse creation logic
   - Add validation rules
   - Test wizard flow

10. **NEW (2h):** Custom Warehouse CRUD
    - Create `WarehouseForm.php` Livewire component
    - Create `warehouse-form.blade.php` view
    - Implement create/edit/delete logic
    - Add validation rules
    - Test CRUD operations

11. **NEW (1h):** Dynamic Warehouse Dropdown
    - Update Product Form warehouse dropdown
    - Show MPPTRADE + shop warehouses + custom warehouses
    - Add type badges (Master, Shop, Custom)
    - Test dropdown population

12. **NEW (1h):** Warehouse List Enhancements
    - Add type filter (Master, Shop, Custom)
    - Add shop linkage column
    - Add delete protection logic
    - Test filtering

13. **NEW (1h):** CSS & Styling
    - Create `warehouse-form.css`
    - Update `warehouse-list.css`
    - Add wizard step styles
    - Ensure responsive design

**Files to create (NEW):**
- `app/Http/Livewire/Admin/Warehouses/WarehouseList.php` ⬅️ NEW
- `app/Http/Livewire/Admin/Warehouses/WarehouseForm.php` ⬅️ NEW
- `app/Http/Livewire/Admin/Shops/AddShop.php` (modify existing or create)
- `resources/views/livewire/admin/warehouses/warehouse-list.blade.php` ⬅️ NEW
- `resources/views/livewire/admin/warehouses/warehouse-form.blade.php` ⬅️ NEW
- `resources/views/livewire/admin/shops/add-shop-wizard.blade.php` (modify existing)
- `resources/css/admin/warehouse-form.css` ⬅️ NEW

**Files to modify (EXISTING):**
- `resources/views/admin/warehouses/index.blade.php` (if exists)
- `resources/views/admin/warehouses/edit.blade.php` (if exists)
- `resources/views/livewire/products/management/product-form.blade.php`
- `resources/views/admin/shops/edit.blade.php`
- `routes/web.php` (add warehouse routes)
- `vite.config.js` (add warehouse-form.css)

**Validation:**
- Visit `/admin/warehouses` → see MPPTRADE + shop warehouses + custom warehouses
- Click "Dodaj Własny Magazyn" → form renders correctly
- Create custom warehouse → saved successfully, appears in list
- Edit custom warehouse → changes saved
- Delete custom warehouse (without stock) → deleted successfully
- Try delete MPPTRADE → error message shown
- Try delete shop warehouse → error message shown
- Add new shop via wizard → warehouse created automatically
- Toggle inherit mode → DB updated correctly

---

### PHASE 5: Testing (Estimated: 4h)

**NO CHANGES** - Same as original plan

**Tasks:**
1. ✅ Unit tests dla WarehouseFactory
2. ✅ Unit tests dla StockInheritanceService
3. ✅ Integration tests dla SyncStockToPrestaShop job
4. ✅ Integration tests dla inherit workflow
5. ✅ Integration tests dla pull workflow
6. ✅ Manual testing on local environment
7. ✅ Performance testing (bulk sync)
8. ✅ Edge cases testing (missing data, API errors)

**Additional Test Scenarios (NEW):**
```php
// Test 5: Shop wizard creates warehouse
$this->visitShopWizard()
    ->fillBasicInfo()
    ->testConnection()
    ->configureWarehouse(['auto_create' => true, 'inherit' => true])
    ->submit();
$this->assertDatabaseHas('warehouses', ['shop_id' => $shop->id]);

// Test 6: Custom warehouse creation
$this->visitWarehouseCreate()
    ->fill(['name' => 'Test Warehouse', 'code' => 'test-warehouse'])
    ->submit();
$this->assertDatabaseHas('warehouses', ['code' => 'test-warehouse', 'shop_id' => null]);

// Test 7: Cannot delete MPPTRADE
$mpptrade = Warehouse::where('is_master', true)->first();
$this->deleteWarehouse($mpptrade->id);
$this->assertDatabaseHas('warehouses', ['id' => $mpptrade->id]);

// Test 8: Cannot delete shop-linked warehouse
$shopWarehouse = Warehouse::whereNotNull('shop_id')->first();
$this->deleteWarehouse($shopWarehouse->id);
$this->assertDatabaseHas('warehouses', ['id' => $shopWarehouse->id]);
```

---

### TIMELINE SUMMARY (UPDATED)

| Phase | Tasks | Estimated Time | Dependencies |
|-------|-------|----------------|--------------|
| **Phase 1: Database** | Migrations, seeders, models | 2h | None |
| **Phase 2: Services** | WarehouseFactory, StockInheritanceService | 4h | Phase 1 |
| **Phase 3: Jobs** | SyncStockToPrestaShop, modifications | 3h | Phase 1, 2 |
| **Phase 4: UI** | Warehouse management, wizard, CRUD, product form | **8h** ⬅️ **UPDATED** | Phase 1, 2 |
| **Phase 5: Testing** | Unit, integration, manual tests | 4h | Phase 1-4 |
| **TOTAL** | | **21h** ⬅️ **UPDATED** | |

**Breakdown by Role:**
- **Backend (Laravel)**: 9h (Phases 1-3)
- **Frontend (Blade/Livewire)**: **8h** ⬅️ **UPDATED** (Phase 4)
- **Testing & QA**: 4h (Phase 5)

**Time Increase Breakdown:**
- Original UI: 5h
- Shop Wizard Integration: +2h
- Custom Warehouse CRUD: +2h
- Dynamic Dropdown & Filters: +1h
- **Total UI**: 8h (+3h increase)

---

## ✅ UPDATED SUCCESS CRITERIA

### FUNCTIONAL CRITERIA

#### Backend Success (Original):
1. ✅ **MPPTRADE is master warehouse**
   - `Warehouse::where('is_master', true)->count() === 1`
   - `Warehouse::where('code', 'mpptrade')->first()->is_master === true`

2. ✅ **Old warehouses removed**
   - `Warehouse::whereIn('code', ['pitbike', 'cameraman', 'otopit', 'infms', 'returns'])->count() === 0`

3. ✅ **Dynamic warehouses created**
   - Add shop via wizard → warehouse auto-created
   - `Warehouse::where('shop_id', $shop->id)->exists() === true`

4. ✅ **Inherit mode works**
   - Update stock in MPPTRADE → shop warehouse updated
   - PrestaShop API shows updated stock
   - Sync job completes without errors

5. ✅ **Pull mode works**
   - Cron runs → stock pulled from PrestaShop
   - PPM warehouse updated with PrestaShop values
   - No errors in logs

#### UI Success (NEW):

6. ✅ **Shop Add Wizard: Warehouse step visible and functional**
   - Visit `/admin/shops/add` → Step 3 "Warehouse Configuration" shows
   - Auto-create checkbox works (checked by default)
   - Warehouse name editable
   - Inherit toggle works
   - Warehouse created on wizard completion

7. ✅ **Custom Warehouse CRUD: Create/Edit/Delete works**
   - Visit `/admin/warehouses/create` → form renders correctly
   - Create custom warehouse → saved successfully
   - Edit custom warehouse → changes saved
   - Delete custom warehouse (without stock) → deleted successfully
   - Cannot delete MPPTRADE → error message shown
   - Cannot delete shop-linked warehouse → error message shown

8. ✅ **Product Form: Warehouse dropdown shows MPPTRADE + shop warehouses + custom**
   - Open product form → warehouse dropdown populated correctly
   - Dropdown shows: MPPTRADE (badge: Master), Shop warehouses (badge: Shop), Custom warehouses (badge: Custom)
   - Inherited/pulled warehouses show read-only inputs
   - Status badges visible (Synced, Pulled, Manual)

9. ✅ **Warehouse List: Filters work (type, status, shop linkage)**
   - Visit `/admin/warehouses` → table shows all warehouses
   - Type filter: Master → shows MPPTRADE only
   - Type filter: Shop → shows only shop warehouses
   - Type filter: Custom → shows only custom warehouses
   - Status filter: Active/Inactive works
   - Search by name/code works

10. ✅ **Warehouse permissions: Cannot delete MPPTRADE or shop-linked warehouses**
    - Try delete MPPTRADE → error: "Cannot delete master warehouse"
    - Try delete shop warehouse → error: "Cannot delete shop-linked warehouse"
    - Try delete custom warehouse with stock → error: "Warehouse has stock, empty first"
    - Delete custom warehouse without stock → success

---

### PERFORMANCE CRITERIA

*(Same as original plan - no changes)*

---

### DATA INTEGRITY CRITERIA

*(Same as original plan - no changes)*

---

## 🔄 ROLLBACK PLAN

*(Same as original plan - no changes to rollback strategy)*

---

## 📁 UPDATED FILES TO CREATE

### Migrations (UNCHANGED)
- `database/migrations/2025_11_07_100000_add_master_warehouse_fields.php`
- `database/migrations/2025_11_07_100001_migrate_warehouse_data.php`

### Services (UNCHANGED)
- `app/Services/Warehouse/WarehouseFactory.php`
- `app/Services/Warehouse/StockInheritanceService.php`

### Jobs (UNCHANGED)
- `app/Jobs/PrestaShop/SyncStockToPrestaShop.php`
- `app/Jobs/PrestaShop/PullStockFromPrestaShop.php`

### Livewire Components (NEW)
- `app/Http/Livewire/Admin/Warehouses/WarehouseList.php` ⬅️ **NEW**
- `app/Http/Livewire/Admin/Warehouses/WarehouseForm.php` ⬅️ **NEW**
- `app/Http/Livewire/Admin/Shops/AddShop.php` ⬅️ **NEW** (or modify existing)

### Views (NEW)
- `resources/views/livewire/admin/warehouses/warehouse-list.blade.php` ⬅️ **NEW**
- `resources/views/livewire/admin/warehouses/warehouse-form.blade.php` ⬅️ **NEW**
- `resources/views/livewire/admin/shops/add-shop-wizard.blade.php` (modify existing)

### CSS (NEW)
- `resources/css/admin/warehouse-form.css` ⬅️ **NEW**
- `resources/css/admin/warehouse-list.css` (modify existing)
- `resources/css/admin/shop-wizard.css` ⬅️ **NEW**

### Tests
- `tests/Unit/Services/WarehouseFactoryTest.php`
- `tests/Unit/Services/StockInheritanceServiceTest.php`
- `tests/Feature/Jobs/SyncStockToPrestaShopTest.php`
- `tests/Feature/Jobs/PullStockFromPrestaShopTest.php`
- `tests/Feature/WarehouseInheritWorkflowTest.php`
- `tests/Feature/WarehousePullWorkflowTest.php`
- `tests/Feature/Livewire/WarehouseFormTest.php` ⬅️ **NEW**
- `tests/Feature/Livewire/ShopWizardTest.php` ⬅️ **NEW**

---

## 📁 UPDATED FILES TO MODIFY

### Models (UNCHANGED)
- `app/Models/Warehouse.php` (add fillable, relationships, scopes)
- `app/Models/PrestaShopShop.php` (add warehouse() relationship)

### Services (UNCHANGED)
- `app/Services/PrestaShop/PrestaShopStockImporter.php`

### Jobs (UNCHANGED)
- `app/Jobs/PullProductsFromPrestaShop.php`

### Seeders (UNCHANGED)
- `database/seeders/WarehouseSeeder.php`

### Routes (MODIFIED)
- `routes/console.php` (add cron)
- `routes/web.php` (add warehouse routes) ⬅️ **EXPANDED** (add CRUD routes)

### Views (MODIFIED)
- `resources/views/livewire/products/management/product-form.blade.php`
- `resources/views/admin/shops/edit.blade.php`

### Config (MODIFIED)
- `vite.config.js` (add warehouse-form.css, shop-wizard.css)

---

## 🎯 CONCLUSION

### Summary

Updated architecture plan with **UI-first approach**:

**Key Changes from Original:**
- ✅ Warehouse creation moved to **Shop Add Wizard** (not first import)
- ✅ **Custom Warehouse CRUD** for standalone warehouses
- ✅ **Extended UI timeline** from 5h to 8h (+3h)
- ✅ **13 new files** (Livewire components, views, CSS)
- ✅ **Updated success criteria** with UI verification

**What Stays the Same:**
- ✅ Core architecture (MPPTRADE master warehouse)
- ✅ Database schema
- ✅ Service layer (WarehouseFactory, StockInheritanceService)
- ✅ Job workflow (SyncStockToPrestaShop, PullStockFromPrestaShop)
- ✅ Inherit/Pull mode logic

### Benefits of UI-First Approach

**User Experience:**
- ✅ Warehouse creation explicit (no surprises)
- ✅ Full control over warehouse management
- ✅ Clear visibility of warehouse types
- ✅ Intuitive wizard flow

**Developer Experience:**
- ✅ Livewire components reusable
- ✅ CRUD operations standardized
- ✅ Clear separation of concerns

### Approval Required

**CRITICAL**: User MUSI zaaprobować tę zaktualizowaną wersję przed implementacją!

**Questions for User:**
1. ✅ Zgoda na 3h dodatkowego czasu (21h total zamiast 18h)?
2. ✅ Akceptacja Shop Wizard jako miejsca tworzenia warehouse?
3. ✅ Akceptacja Custom Warehouse CRUD functionality?
4. ✅ Zgoda na wszystkie pozostałe zmiany z oryginalnego planu?

### Next Steps

**IF APPROVED:**
1. Create detailed subtasks in project management tool
2. Schedule implementation (recommend dedicated **3-day sprint**)
3. Prepare production database backup
4. Notify stakeholders about upcoming changes
5. Begin Phase 1 (Database)

**Delegation:**
- **laravel-expert**: Phase 1-3 (database, services, jobs)
- **frontend-specialist**: Phase 4 (UI implementation - 8h)
- **livewire-specialist**: Phase 4 assistance (Livewire components)
- **deployment-specialist**: Phase 5 (production deployment)

---

**END OF UPDATED ARCHITECTURE REPORT**

---

**Original Date**: 2025-11-07
**Updated Date**: 2025-11-12
**Agent**: Planning Manager & Project Plan Keeper
**Status**: ✅ **READY FOR USER APPROVAL** (UPDATED VERSION)
**Time Estimate**: 21 hours (3-day sprint + 3h extra)
**Priority**: 🟡 HIGH
