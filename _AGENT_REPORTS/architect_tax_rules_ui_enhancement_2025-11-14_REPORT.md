# RAPORT PRACY AGENTA: architect
**Data**: 2025-11-14 15:30
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: Tax Rules UI Enhancement System - Architectural Planning

---

## ✅ WYKONANE PRACE

### 1. ANALIZA ISTNIEJĄCEGO KODU

Przeanalizowano następujące komponenty:
- ✅ `app/Http/Livewire/Admin/Shops/AddShop.php` (837 linii) - Istniejący wizard dodawania/edycji sklepów (6 kroków)
- ✅ `app/Http/Livewire/Admin/Shops/ShopManager.php` (1052 linie) - Manager sklepów z connection health monitoring
- ✅ `app/Http/Livewire/Products/Management/ProductForm.php` (500+ linii analyzed) - Form produktu z tabulacjami
- ✅ `app/Models/ProductShopData.php` (766 linii) - Model z per-shop data i sync tracking
- ✅ `database/migrations/2025_11_14_120000_add_tax_rules_mapping_to_prestashop_shops.php` - Ukończona migracja backend

**Kluczowe odkrycia:**
1. AddShop używa 6-step wizard pattern (`currentStep`, `totalSteps`, validation per step)
2. Krok 4 już obsługuje Price Group Mapping (fetching + dropdown selection)
3. ProductForm używa trait-based architecture (ProductFormValidation, ProductFormUpdates, etc.)
4. ProductShopData ma extensive JSON casts i sync status tracking
5. Backend fix Tax Rules już ukończony (3-tier strategy: configured → auto-detect → fallback)

---

## 📋 ARCHITEKTURA SYSTEMU TAX RULES UI ENHANCEMENT

### PROBLEM STATEMENT

**Backend Status:** ✅ COMPLETE (2025-11-14)
- Migration: `prestashop_shops.tax_rules_group_id_23/8/5/0` + `tax_rules_last_fetched_at`
- `ProductTransformer::mapTaxRate()` + `autoDetectTaxRules()` implemented
- Products sync z prawidłowym `id_tax_rules_group = 6`

**User Requirements:**
1. ✅ Per-shop tax rate override (różne stawki VAT dla różnych sklepów)
2. ✅ Priority A → B: `/admin/shops` (Add/Edit) → `ProductForm`
3. ✅ Migration Strategy: Przeliczyć istniejące produkty i wykryć różnice
4. ✅ Parallel work z Specific Prices issue (delegation complete)

---

## 🏗️ FAZA 1: /admin/shops Enhancement (PRIORITY A)

### FAZA 1A: AddShop Form Enhancement

**Status:** ❌ NOT STARTED
**Complexity:** MEDIUM (2-3h)
**Agent Assignment:** livewire-specialist + prestashop-api-expert (parallel)

**Zadania:**

#### 1A.1 PrestaShop Tax Rules API Integration
**Agent:** prestashop-api-expert
**Files to modify:**
- `app/Services/PrestaShop/BasePrestaShopClient.php` (add `getTaxRulesGroups()` method)
- `app/Services/PrestaShop/PrestaShop8Client.php` (implement version-specific endpoint)
- `app/Services/PrestaShop/PrestaShop9Client.php` (implement version-specific endpoint)

**Implementation:**
```php
// BasePrestaShopClient.php
/**
 * Get all tax rules groups from PrestaShop
 *
 * ETAP_07 FAZA 5 - Tax Rules Dynamic Mapping
 * Fetches all tax_rules_groups which can be assigned to products
 *
 * @return array Tax rules groups data
 * @throws \App\Exceptions\PrestaShopAPIException
 */
abstract public function getTaxRulesGroups(): array;
```

**API Endpoint:**
- PrestaShop 8.x/9.x: `/api/tax_rule_groups?display=full`
- Response structure: `['tax_rule_groups' => [['id' => 1, 'name' => 'PL Standard 23%'], ...]]`

**Success Criteria:**
- ✅ Method returns array of tax rules groups
- ✅ Handles PrestaShop 8.x and 9.x differences
- ✅ Error handling (404, 500, timeout)
- ✅ Unit test coverage (mock PrestaShop responses)

#### 1A.2 AddShop Livewire Component Update
**Agent:** livewire-specialist
**Files to modify:**
- `app/Http/Livewire/Admin/Shops/AddShop.php` (rozszerz Step 3 Connection Test)

**Implementation:**

**New Properties:**
```php
// Step 3 Extension: Tax Rules Mapping (after connection test)
public array $prestashopTaxRulesGroups = []; // Cached tax rules from PrestaShop API
public array $taxRulesMappings = []; // [taxRate => tax_rules_group_id]
public bool $fetchingTaxRules = false;
public string $fetchTaxRulesError = '';
```

**New Methods:**
```php
/**
 * Fetch PrestaShop tax rules groups (auto-run after successful connection test)
 *
 * ETAP_07 FAZA 5 - Tax Rules Dynamic Mapping
 */
public function fetchPrestashopTaxRulesGroups(): void
{
    $this->fetchingTaxRules = true;
    $this->fetchTaxRulesError = '';
    $this->prestashopTaxRulesGroups = [];

    try {
        // Create temporary shop instance (same pattern as fetchPrestashopPriceGroups)
        $tempShop = new PrestaShopShop([...]);

        $client = $this->prestashopVersion === '9'
            ? new PrestaShop9Client($tempShop)
            : new PrestaShop8Client($tempShop);

        $response = $client->getTaxRulesGroups();

        // Parse response
        if (isset($response['tax_rule_groups'])) {
            foreach ($response['tax_rule_groups'] as $group) {
                $this->prestashopTaxRulesGroups[] = [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'rate' => $group['rate'] ?? null, // May not be in all responses
                ];
            }

            // Auto-detect smart defaults (23% → group with "23" in name)
            $this->autoMapTaxRules();
        }

    } catch (\Exception $e) {
        $this->fetchTaxRulesError = 'Błąd pobierania tax rules: ' . $e->getMessage();
    } finally {
        $this->fetchingTaxRules = false;
    }
}

/**
 * Auto-detect smart defaults for tax rules mapping
 */
private function autoMapTaxRules(): void
{
    $standardRates = [23, 8, 5, 0];

    foreach ($standardRates as $rate) {
        // Find group with rate in name (case-insensitive)
        foreach ($this->prestashopTaxRulesGroups as $group) {
            if (stripos($group['name'], (string)$rate) !== false) {
                $this->taxRulesMappings[$rate] = $group['id'];
                break;
            }
        }
    }
}
```

**Workflow Integration:**
1. User completes Step 2 (API Credentials)
2. User clicks "Następny krok" → Step 3 (Connection Test)
3. `testConnection()` runs automatically (existing behavior)
4. **NEW:** If connection success → Auto-run `fetchPrestashopTaxRulesGroups()`
5. **NEW:** Display fetched tax rules with smart defaults pre-selected
6. **NEW:** User can override smart defaults via dropdowns
7. User clicks "Następny krok" → Step 4 (Price Group Mapping - existing)

**Success Criteria:**
- ✅ Tax rules fetched after successful connection test
- ✅ Smart defaults auto-detected (23% → group ID X)
- ✅ Dropdown selection UI dla 4 stawek (23%, 8%, 5%, 0%)
- ✅ Validation: przynajmniej 23% (Standard Rate) musi być zmapowane
- ✅ Loading states (wire:loading) podczas fetch
- ✅ Error handling (fetch failed → fallback to manual input)

#### 1A.3 AddShop Blade Template Update
**Agent:** frontend-specialist
**Files to modify:**
- `resources/views/livewire/admin/shops/add-shop.blade.php`

**Implementation:**

**Step 3 Extension (after connection diagnostics):**
```blade
{{-- STEP 3: Connection Test + Tax Rules Mapping --}}
@if ($currentStep === 3)
    {{-- Existing connection test diagnostics --}}

    {{-- NEW: Tax Rules Mapping Section (shows after successful connection) --}}
    @if ($connectionStatus === 'success')
        <div class="tax-rules-mapping-section">
            <h4>Mapowanie Stawek VAT</h4>
            <p class="text-sm text-gray-600">
                Wybierz grupy reguł podatkowych PrestaShop dla każdej stawki VAT.
                System automatycznie wykrył domyślne mapowanie.
            </p>

            @if ($fetchingTaxRules)
                <div wire:loading.delay wire:target="fetchPrestashopTaxRulesGroups">
                    <span class="loading-spinner"></span> Pobieranie grup podatkowych...
                </div>
            @endif

            @if ($fetchTaxRulesError)
                <div class="alert alert-warning">
                    {{ $fetchTaxRulesError }}
                    <button wire:click="fetchPrestashopTaxRulesGroups" class="btn-retry">
                        Spróbuj ponownie
                    </button>
                </div>
            @endif

            @if (count($prestashopTaxRulesGroups) > 0)
                <div class="tax-rates-grid">
                    {{-- 23% VAT (Standard Rate) - REQUIRED --}}
                    <div class="tax-rate-field">
                        <label for="tax_23">
                            <strong>23% VAT (Stawka Standardowa)</strong>
                            <span class="required">*</span>
                        </label>
                        <select wire:model="taxRulesMappings.23" id="tax_23" class="form-select" required>
                            <option value="">-- Wybierz grupę --</option>
                            @foreach ($prestashopTaxRulesGroups as $group)
                                <option value="{{ $group['id'] }}">
                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 8% VAT (Reduced Rate) - OPTIONAL --}}
                    <div class="tax-rate-field">
                        <label for="tax_8">8% VAT (Stawka Obniżona)</label>
                        <select wire:model="taxRulesMappings.8" id="tax_8" class="form-select">
                            <option value="">-- Opcjonalne --</option>
                            @foreach ($prestashopTaxRulesGroups as $group)
                                <option value="{{ $group['id'] }}">
                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5% VAT (Super Reduced Rate) - OPTIONAL --}}
                    <div class="tax-rate-field">
                        <label for="tax_5">5% VAT (Stawka Super Obniżona)</label>
                        <select wire:model="taxRulesMappings.5" id="tax_5" class="form-select">
                            <option value="">-- Opcjonalne --</option>
                            @foreach ($prestashopTaxRulesGroups as $group)
                                <option value="{{ $group['id'] }}">
                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 0% VAT (Exempt) - OPTIONAL --}}
                    <div class="tax-rate-field">
                        <label for="tax_0">0% VAT (Zwolniona)</label>
                        <select wire:model="taxRulesMappings.0" id="tax_0" class="form-select">
                            <option value="">-- Opcjonalne --</option>
                            @foreach ($prestashopTaxRulesGroups as $group)
                                <option value="{{ $group['id'] }}">
                                    {{ $group['name'] }} (ID: {{ $group['id'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endif
```

**CSS Styling:**
```css
/* resources/css/admin/components.css - Tax Rules Mapping Section */
.tax-rules-mapping-section {
    margin-top: var(--spacing-4);
    padding: var(--spacing-4);
    background: var(--color-bg-secondary);
    border-radius: var(--border-radius-md);
}

.tax-rates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-3);
    margin-top: var(--spacing-3);
}

.tax-rate-field label {
    display: block;
    font-weight: var(--font-weight-medium);
    margin-bottom: var(--spacing-2);
}

.tax-rate-field .required {
    color: var(--color-error);
}
```

**Success Criteria:**
- ✅ UI pokazuje się TYLKO po successful connection test
- ✅ Dropdowns pre-populated z smart defaults
- ✅ 23% VAT (required) wyróżnione wizualnie
- ✅ Loading spinner podczas fetch
- ✅ Error state z przyciskiem retry
- ✅ Responsive grid (2 kolumny desktop, 1 kolumna mobile)

#### 1A.4 AddShop Save Logic Update
**Agent:** laravel-expert
**Files to modify:**
- `app/Http/Livewire/Admin/Shops/AddShop.php` (update `saveShop()` method)

**Implementation:**

**Extend `validateCurrentStep()` for Step 3:**
```php
case 3:
    // Existing connection test validation...

    // NEW: Tax rules validation
    if (!isset($this->taxRulesMappings[23]) || empty($this->taxRulesMappings[23])) {
        throw new \Exception('Mapowanie dla stawki 23% VAT jest wymagane');
    }
    break;
```

**Extend `saveShop()` method:**
```php
public function saveShop()
{
    // ... existing validation ...

    // Prepare shop data
    $shopData = [
        // ... existing fields ...

        // NEW: Tax Rules Mapping
        'tax_rules_group_id_23' => $this->taxRulesMappings[23] ?? null,
        'tax_rules_group_id_8' => $this->taxRulesMappings[8] ?? null,
        'tax_rules_group_id_5' => $this->taxRulesMappings[5] ?? null,
        'tax_rules_group_id_0' => $this->taxRulesMappings[0] ?? null,
        'tax_rules_last_fetched_at' => now(),
    ];

    // ... existing save logic ...
}
```

**Success Criteria:**
- ✅ Validation wymusza 23% mapping (required)
- ✅ Tax rules zapisane w `prestashop_shops` table
- ✅ `tax_rules_last_fetched_at` timestamp recorded
- ✅ Works w create i edit mode

---

### FAZA 1B: EditShop Form Enhancement

**Status:** ❌ NOT STARTED
**Complexity:** LOW (1-2h) - Reuse FAZA 1A components
**Agent Assignment:** livewire-specialist

**Zadania:**

#### 1B.1 Edit Mode Support
**Agent:** livewire-specialist
**Files to modify:**
- `app/Http/Livewire/Admin/Shops/AddShop.php` (extend `loadShopData()`)

**Implementation:**

**Load existing tax rules mappings in edit mode:**
```php
public function loadShopData()
{
    // ... existing loading logic ...

    // NEW: Load Tax Rules Mappings
    if ($shop->tax_rules_group_id_23) {
        $this->taxRulesMappings[23] = $shop->tax_rules_group_id_23;
    }
    if ($shop->tax_rules_group_id_8) {
        $this->taxRulesMappings[8] = $shop->tax_rules_group_id_8;
    }
    if ($shop->tax_rules_group_id_5) {
        $this->taxRulesMappings[5] = $shop->tax_rules_group_id_5;
    }
    if ($shop->tax_rules_group_id_0) {
        $this->taxRulesMappings[0] = $shop->tax_rules_group_id_0;
    }

    // Re-fetch tax rules groups to populate dropdown options
    if ($this->connectionStatus === 'success') {
        $this->fetchPrestashopTaxRulesGroups();
    }
}
```

#### 1B.2 "Refresh from PrestaShop" Button
**Agent:** livewire-specialist
**Files to modify:**
- `app/Http/Livewire/Admin/Shops/AddShop.php` (new method)
- `resources/views/livewire/admin/shops/add-shop.blade.php` (UI button)

**Implementation:**

**New Method:**
```php
/**
 * Refresh tax rules mapping from PrestaShop (re-fetch and auto-detect)
 *
 * ETAP_07 FAZA 5 - Tax Rules Dynamic Mapping
 */
public function refreshTaxRulesFromPrestaShop(): void
{
    // Clear existing mappings
    $this->taxRulesMappings = [];

    // Re-fetch from PrestaShop
    $this->fetchPrestashopTaxRulesGroups();

    session()->flash('success', 'Tax rules zostały odświeżone z PrestaShop');
}
```

**Blade Button:**
```blade
<button wire:click="refreshTaxRulesFromPrestaShop"
        class="btn-secondary"
        wire:loading.attr="disabled"
        type="button">
    <span wire:loading.remove wire:target="refreshTaxRulesFromPrestaShop">
        🔄 Odśwież z PrestaShop
    </span>
    <span wire:loading wire:target="refreshTaxRulesFromPrestaShop">
        Odświeżanie...
    </span>
</button>
```

**Success Criteria:**
- ✅ Edit mode ładuje existing mappings
- ✅ Przycisk "Refresh from PrestaShop" re-fetches i auto-detects
- ✅ Loading state podczas refresh
- ✅ Success message po refresh

#### 1B.3 Audit Trail (Optional Enhancement)
**Status:** ⏳ DEFERRED (future enhancement)
**Complexity:** LOW (1h)

**Potential Implementation:**
- Dodaj `tax_rules_mappings_history` JSON column w `prestashop_shops`
- Log zmian: `[{'changed_at' => timestamp, 'changed_by' => user_id, 'old' => [...], 'new' => [...]}]`
- Display history w modal (user clicks "Historia zmian")

**Decision:** Defer to future iteration (not critical for MVP)

---

## 🏗️ FAZA 2: ProductForm - Physical Properties Tab (PRIORITY B)

### FAZA 2A: Tax Rate Field Enhancement

**Status:** ❌ NOT STARTED
**Complexity:** MEDIUM (3-4h)
**Agent Assignment:** laravel-expert (migration) + livewire-specialist (UI) + prestashop-api-expert (validation)

**Zadania:**

#### 2A.1 Database Migration - tax_rate_override Column
**Agent:** laravel-expert
**Files to create:**
- `database/migrations/2025_11_14_140000_add_tax_rate_override_to_product_shop_data.php`

**Implementation:**
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
     * ETAP_07 FAZA 5 - Tax Rules UI Enhancement - Per-Shop Tax Rate Override
     *
     * Problem: Products need per-shop tax rate override capability
     * - Default: Use products.tax_rate (global default)
     * - Override: Store in product_shop_data.tax_rate_override
     *
     * Examples:
     * - Product X: 23% VAT w Polsce (default), but 20% VAT w UK (override)
     * - Product Y: 8% VAT dla książek (default), but 5% VAT dla e-booków (override)
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // Per-shop tax rate override
            $table->decimal('tax_rate_override', 5, 2)->nullable()
                ->after('tax_rate')
                ->comment('Per-shop tax rate override (NULL = use products.tax_rate default)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('tax_rate_override');
        });
    }
};
```

**Success Criteria:**
- ✅ Migration runs bez errors
- ✅ Column `tax_rate_override` exists w `product_shop_data`
- ✅ NULL allowed (default behavior = use products.tax_rate)
- ✅ DECIMAL(5,2) precision (supports 0.00 - 999.99%)

#### 2A.2 ProductShopData Model Update
**Agent:** laravel-expert
**Files to modify:**
- `app/Models/ProductShopData.php`

**Implementation:**

**Add to $fillable:**
```php
protected $fillable = [
    // ... existing fields ...
    'tax_rate_override', // NEW: Per-shop tax rate override
];
```

**Add to $casts:**
```php
protected $casts = [
    // ... existing casts ...
    'tax_rate_override' => 'decimal:2', // NEW
];
```

**Add helper method:**
```php
/**
 * Get effective tax rate (override or default)
 *
 * ETAP_07 FAZA 5 - Tax Rules UI Enhancement
 *
 * @return float Effective tax rate for this shop
 */
public function getEffectiveTaxRate(): float
{
    // Priority: Override → Product Default
    return $this->tax_rate_override ?? $this->product->tax_rate ?? 23.00;
}
```

**Success Criteria:**
- ✅ Model allows mass assignment of `tax_rate_override`
- ✅ Decimal casting preserves 2 decimal places
- ✅ Helper method `getEffectiveTaxRate()` returns correct value

#### 2A.3 ProductForm Livewire Component Update
**Agent:** livewire-specialist
**Files to modify:**
- `app/Http/Livewire/Products/Management/ProductForm.php`
- `resources/views/livewire/products/management/product-form.blade.php` (Physical Properties tab)

**Implementation:**

**ProductForm.php - New Properties:**
```php
// === TAX RATE OVERRIDES (ETAP_07 FAZA 5) ===
public array $taxRateOverrides = []; // [shopId => float|null]
```

**ProductForm.php - Load Method:**
```php
/**
 * Load tax rate overrides from product_shop_data
 *
 * ETAP_07 FAZA 5 - Tax Rules UI Enhancement
 */
private function loadTaxRateOverrides(): void
{
    if (!$this->product || !$this->product->exists) {
        return;
    }

    $shopDataRecords = $this->product->shopData()->get();

    foreach ($shopDataRecords as $shopData) {
        $this->taxRateOverrides[$shopData->shop_id] = $shopData->tax_rate_override;
    }
}
```

**Call in `mount()`:**
```php
public function mount(?Product $product = null): void
{
    // ... existing initialization ...

    // NEW: Load tax rate overrides
    if ($this->isEditMode) {
        $this->loadTaxRateOverrides();
    }
}
```

**Blade Template - Physical Properties Tab:**
```blade
{{-- TAX RATE FIELD (Default + Per-Shop Overrides) --}}
<div class="form-group">
    <label for="tax_rate">
        Stawka VAT (Default)
        <span class="text-sm text-gray-500">- Domyślna dla wszystkich sklepów</span>
    </label>

    <div class="tax-rate-field-wrapper">
        <select wire:model="tax_rate" id="tax_rate" class="form-select">
            <option value="23.00">23% (Standard)</option>
            <option value="8.00">8% (Obniżona)</option>
            <option value="5.00">5% (Super Obniżona)</option>
            <option value="0.00">0% (Zwolniona)</option>
        </select>

        <div class="default-indicator">
            <span class="badge badge-info">PPM Default</span>
        </div>
    </div>

    {{-- Per-Shop Overrides --}}
    @if (count($exportedShops) > 0)
        <div class="tax-rate-overrides-section">
            <h5>Nadpisania per sklep</h5>
            <p class="text-sm text-gray-600">
                Ustaw inną stawkę VAT dla wybranych sklepów. Pozostaw puste aby użyć domyślnej.
            </p>

            <div class="shop-overrides-grid">
                @foreach ($exportedShops as $shopId)
                    @php
                        $shop = \App\Models\PrestaShopShop::find($shopId);
                    @endphp

                    <div class="shop-override-field">
                        <label for="tax_override_{{ $shopId }}">
                            {{ $shop->name }}
                        </label>

                        <select wire:model="taxRateOverrides.{{ $shopId }}"
                                id="tax_override_{{ $shopId }}"
                                class="form-select">
                            <option value="">Użyj domyślnej ({{ $tax_rate }}%)</option>
                            <option value="23.00">23% (Standard)</option>
                            <option value="8.00">8% (Obniżona)</option>
                            <option value="5.00">5% (Super Obniżona)</option>
                            <option value="0.00">0% (Zwolniona)</option>
                        </select>

                        {{-- Validation Indicator --}}
                        @php
                            $effectiveTaxRate = $taxRateOverrides[$shopId] ?? $tax_rate;
                            $mappedGroupId = $this->getMappedTaxRulesGroupId($shop, $effectiveTaxRate);
                        @endphp

                        @if ($mappedGroupId)
                            <div class="validation-indicator success">
                                <span class="badge badge-success">✓ Zmapowane (Group ID: {{ $mappedGroupId }})</span>
                            </div>
                        @else
                            <div class="validation-indicator warning">
                                <span class="badge badge-warning">⚠ Brak mapowania w sklepie</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
```

**Helper Method for Validation:**
```php
/**
 * Get mapped tax_rules_group ID for shop and tax rate
 *
 * ETAP_07 FAZA 5 - Tax Rules UI Enhancement
 *
 * @param PrestaShopShop $shop
 * @param float $taxRate
 * @return int|null Mapped group ID or null if not configured
 */
private function getMappedTaxRulesGroupId($shop, float $taxRate): ?int
{
    $taxRateInt = (int)$taxRate;

    return match($taxRateInt) {
        23 => $shop->tax_rules_group_id_23,
        8 => $shop->tax_rules_group_id_8,
        5 => $shop->tax_rules_group_id_5,
        0 => $shop->tax_rules_group_id_0,
        default => null,
    };
}
```

**Success Criteria:**
- ✅ Default tax rate field shows "PPM Default" badge
- ✅ Per-shop overrides section shows ONLY dla exported shops
- ✅ Dropdown z opcją "Użyj domyślnej" (value = empty string)
- ✅ Validation indicators (green ✓ / yellow ⚠) per shop
- ✅ Sync preview: "Po synchronizacji: id_tax_rules_group = X"

#### 2A.4 ProductFormSaver Update
**Agent:** laravel-expert
**Files to modify:**
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`

**Implementation:**

**Extend `saveShopData()` method:**
```php
/**
 * Save shop-specific data
 *
 * ENHANCEMENT (2025-11-14): Added tax_rate_override support
 */
private function saveShopData(): void
{
    foreach ($this->component->exportedShops as $shopId) {
        $shopData = ProductShopData::firstOrNew([
            'product_id' => $this->product->id,
            'shop_id' => $shopId,
        ]);

        // ... existing fields ...

        // NEW: Tax Rate Override
        $shopData->tax_rate_override = $this->component->taxRateOverrides[$shopId] ?? null;

        // ... existing save logic ...

        $shopData->save();
    }
}
```

**Extend checksum calculation:**
```php
/**
 * Generate checksum for shop data (detect changes)
 *
 * ENHANCEMENT (2025-11-14): Include tax_rate_override in checksum
 */
private function generateShopDataChecksum($shopId): string
{
    $data = [
        // ... existing fields ...
        'tax_rate_override' => $this->component->taxRateOverrides[$shopId] ?? null,
    ];

    return md5(json_encode($data));
}
```

**Success Criteria:**
- ✅ `tax_rate_override` saved w `product_shop_data`
- ✅ NULL saved when "Użyj domyślnej" selected
- ✅ Checksum updated gdy tax override changes
- ✅ Sync status → 'pending' when tax override modified

---

### FAZA 2B: Shop Tab Integration (Optional Enhancement)

**Status:** ⏳ DEFERRED (optional, future enhancement)
**Complexity:** LOW (1-2h)
**Agent Assignment:** frontend-specialist + livewire-specialist

**Potential Implementation:**
- Display current tax rate w zakładce "Sklepy" produktu
- Comparison: "PPM: 23% | PrestaShop: 23% (Group ID: 6)" → Badge: ✅ Zgodne
- Warning badge jeśli mismatch: "PPM: 23% | PrestaShop: 8% (Group ID: 2)" → Badge: ⚠ Różnice

**Decision:** Defer to future iteration (FAZA 2A sufficient for MVP)

---

## 🏗️ FAZA 3: Backend Integration

### FAZA 3A: ProductTransformer Update

**Status:** ❌ NOT STARTED (depends on FAZA 1A + 2A completion)
**Complexity:** LOW (30min) - Simple code change
**Agent Assignment:** prestashop-api-expert

**Zadania:**

#### 3A.1 Update mapTaxRate() to Use tax_rate_override
**Agent:** prestashop-api-expert
**Files to modify:**
- `app/Services/PrestaShop/ProductTransformer.php`

**Current Implementation (2025-11-14):**
```php
private function mapTaxRate(float $taxRate, PrestaShopShop $shop): int
{
    // Strategy 1: Use configured mapping (if available)
    $taxRateInt = (int)$taxRate;
    $mappedGroupId = match($taxRateInt) {
        23 => $shop->tax_rules_group_id_23,
        8 => $shop->tax_rules_group_id_8,
        5 => $shop->tax_rules_group_id_5,
        0 => $shop->tax_rules_group_id_0,
        default => null,
    };

    if ($mappedGroupId !== null) {
        return $mappedGroupId;
    }

    // Strategy 2: Auto-detect from PrestaShop API
    // ... existing auto-detect logic ...

    // Strategy 3: Fallback to hardcoded defaults
    return 1; // Fallback
}
```

**NEW Implementation (with tax_rate_override support):**
```php
/**
 * Map PPM tax rate to PrestaShop tax_rules_group ID
 *
 * ENHANCEMENT (2025-11-14): Support for tax_rate_override from product_shop_data
 *
 * Strategy (3-tier with per-shop override):
 * 1. Get effective tax rate (override → default)
 * 2. Use configured mapping (tax_rules_group_id_XX from shop)
 * 3. Auto-detect from PrestaShop API (cache result)
 * 4. Fallback to default group ID 1
 *
 * @param Product $product PPM Product instance
 * @param ProductShopData|null $productShopData Per-shop data (may have override)
 * @param PrestaShopShop $shop PrestaShop shop instance
 * @return int PrestaShop tax_rules_group ID
 */
private function mapTaxRateWithOverride(
    Product $product,
    ?ProductShopData $productShopData,
    PrestaShopShop $shop
): int
{
    // Step 1: Get effective tax rate (override → default)
    $effectiveTaxRate = $productShopData?->tax_rate_override ?? $product->tax_rate ?? 23.00;

    // Step 2: Use configured mapping
    $taxRateInt = (int)$effectiveTaxRate;
    $mappedGroupId = match($taxRateInt) {
        23 => $shop->tax_rules_group_id_23,
        8 => $shop->tax_rules_group_id_8,
        5 => $shop->tax_rules_group_id_5,
        0 => $shop->tax_rules_group_id_0,
        default => null,
    };

    if ($mappedGroupId !== null) {
        return $mappedGroupId;
    }

    // Step 3: Auto-detect from PrestaShop API (existing logic)
    $autoDetected = $this->autoDetectTaxRules($effectiveTaxRate, $shop);
    if ($autoDetected !== null) {
        return $autoDetected;
    }

    // Step 4: Fallback
    return 1;
}
```

**Update `toPrestaShop()` method:**
```php
public function toPrestaShop(Product $product, PrestaShopShop $shop): array
{
    // Get product_shop_data for this shop (if exists)
    $productShopData = ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->first();

    $transformed = [
        // ... existing fields ...

        // TAX RATE (with per-shop override support)
        'id_tax_rules_group' => $this->mapTaxRateWithOverride($product, $productShopData, $shop),
    ];

    return ['product' => $transformed];
}
```

**Success Criteria:**
- ✅ Produkty synchronizują się z tax_rate_override (jeśli set)
- ✅ Fallback do products.tax_rate jeśli brak override
- ✅ Existing 3-tier strategy preserved (configured → auto-detect → fallback)
- ✅ No performance degradation (avoid N+1 queries)

---

### FAZA 3B: Checksum Recalculation

**Status:** ❌ NOT STARTED (depends on FAZA 3A)
**Complexity:** LOW (15min)
**Agent Assignment:** laravel-expert

**Zadania:**

#### 3B.1 Include tax_rate_override in ProductShopData Checksum
**Agent:** laravel-expert
**Files to modify:**
- `app/Models/ProductShopData.php`

**Implementation:**

**Update `generateDataHash()` method:**
```php
public function generateDataHash(): string
{
    $data = [
        // ... existing fields ...

        // NEW: Include tax_rate_override in checksum
        'tax_rate_override' => $this->tax_rate_override,
    ];

    return md5(json_encode($data));
}
```

**Success Criteria:**
- ✅ Checksum changes when tax_rate_override modified
- ✅ Sync status → 'pending' when override updated
- ✅ Re-sync triggered automatically (if auto-sync enabled)

---

### FAZA 3C: ValidationService Enhancement (Optional)

**Status:** ⏳ DEFERRED (optional, future enhancement)
**Complexity:** MEDIUM (2-3h)
**Agent Assignment:** laravel-expert + prestashop-api-expert

**Potential Implementation:**
- `ValidationService::validateTaxRateMapping()` - Compare PPM vs PrestaShop
- Reverse mapping: Fetch `id_tax_rules_group` from PrestaShop → determine tax rate
- Generate warnings: "PPM: 23% | PrestaShop: 8% → Niezgodność!"
- UI indicator: Badge w ProductForm Shop Tab (green ✓ / red ✗)

**Decision:** Defer to future iteration (not critical for MVP)

---

## 🏗️ FAZA 4: Data Analysis & Migration (Optional)

### FAZA 4A: Existing Products Analysis

**Status:** ⏳ DEFERRED (optional, future enhancement)
**Complexity:** LOW (1h)
**Agent Assignment:** laravel-expert

**Potential Implementation:**

**Analysis Script:**
```php
<?php
// _TOOLS/analyze_tax_rate_discrepancies.php

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

/**
 * Analyze tax rate discrepancies between PPM and PrestaShop
 *
 * ETAP_07 FAZA 5 - Tax Rules UI Enhancement - Data Analysis
 *
 * Identify products where PrestaShop tax rate differs from PPM default
 * Suggest which products need tax_rate_override
 */

$products = Product::with('shopData')->get();
$discrepancies = [];

foreach ($products as $product) {
    foreach ($product->shopData as $shopData) {
        $shop = $shopData->shop;

        // Fetch current PrestaShop product
        $client = PrestaShopClientFactory::make($shop);
        $psProduct = $client->getProduct($shopData->prestashop_product_id);

        $psTaxGroupId = $psProduct['product']['id_tax_rules_group'];
        $ppmTaxRate = $product->tax_rate;

        // Reverse map: PrestaShop group ID → tax rate
        $psTaxRate = $this->reverseMaطTaxRulesGroup($psTaxGroupId, $shop);

        if ($psTaxRate !== null && $psTaxRate != $ppmTaxRate) {
            $discrepancies[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'ppm_tax_rate' => $ppmTaxRate,
                'prestashop_tax_rate' => $psTaxRate,
                'prestashop_group_id' => $psTaxGroupId,
                'action' => 'SET tax_rate_override = ' . $psTaxRate,
            ];
        }
    }
}

// Output report
echo "ANALIZA: " . count($discrepancies) . " produktów z różnicami\n";
foreach ($discrepancies as $d) {
    echo "{$d['sku']} ({$d['shop_name']}): PPM {$d['ppm_tax_rate']}% → PS {$d['prestashop_tax_rate']}%\n";
}
```

**Decision:** Defer to future iteration (manual analysis sufficient for MVP)

---

## 📊 AGENT ASSIGNMENTS & DEPENDENCIES

### PARALLEL WORK (No Dependencies)

**GROUP A: FAZA 1A (AddShop Tax Rules) - CAN START IMMEDIATELY**
- prestashop-api-expert: Task 1A.1 - PrestaShop Tax Rules API Integration (2-3h)
- livewire-specialist: Task 1A.2 - AddShop Livewire Component Update (2-3h)

**GROUP B: FAZA 2A (ProductForm Tax Override) - CAN START AFTER GROUP A**
- laravel-expert: Task 2A.1 - Database Migration (30min)
- laravel-expert: Task 2A.2 - ProductShopData Model Update (15min)

### SEQUENTIAL WORK (Dependencies)

**SEQUENCE 1: AddShop → Frontend → Save Logic**
1. prestashop-api-expert: 1A.1 (API Integration) → **COMPLETE**
2. livewire-specialist: 1A.2 (Livewire Component) → **DEPENDS ON 1A.1**
3. frontend-specialist: 1A.3 (Blade Template) → **DEPENDS ON 1A.2**
4. laravel-expert: 1A.4 (Save Logic) → **DEPENDS ON 1A.3**

**SEQUENCE 2: EditShop → Edit Mode Support**
5. livewire-specialist: 1B.1 (Edit Mode) → **DEPENDS ON 1A.4**
6. livewire-specialist: 1B.2 (Refresh Button) → **PARALLEL WITH 1B.1**

**SEQUENCE 3: ProductForm → UI → Save Logic**
7. laravel-expert: 2A.1 + 2A.2 (Migration + Model) → **PARALLEL WITH GROUP A**
8. livewire-specialist: 2A.3 (ProductForm Livewire) → **DEPENDS ON 2A.2**
9. laravel-expert: 2A.4 (ProductFormSaver) → **DEPENDS ON 2A.3**

**SEQUENCE 4: Backend Integration**
10. prestashop-api-expert: 3A.1 (ProductTransformer) → **DEPENDS ON 2A.2**
11. laravel-expert: 3B.1 (Checksum) → **DEPENDS ON 3A.1**

---

## ⏱️ ESTIMATED COMPLEXITY & TIME

### FAZA 1: /admin/shops Enhancement
- **1A.1** (prestashop-api-expert): 2-3h - API Integration
- **1A.2** (livewire-specialist): 2-3h - Livewire Component
- **1A.3** (frontend-specialist): 1-2h - Blade Template + CSS
- **1A.4** (laravel-expert): 1h - Save Logic
- **1B.1** (livewire-specialist): 1h - Edit Mode Support
- **1B.2** (livewire-specialist): 1h - Refresh Button
- **TOTAL FAZA 1:** 8-12h (1-1.5 days)

### FAZA 2: ProductForm Enhancement
- **2A.1** (laravel-expert): 30min - Migration
- **2A.2** (laravel-expert): 15min - Model Update
- **2A.3** (livewire-specialist): 2-3h - ProductForm UI
- **2A.4** (laravel-expert): 1h - ProductFormSaver
- **TOTAL FAZA 2:** 4-5h (0.5 days)

### FAZA 3: Backend Integration
- **3A.1** (prestashop-api-expert): 30min - ProductTransformer
- **3B.1** (laravel-expert): 15min - Checksum
- **TOTAL FAZA 3:** 45min (0.1 days)

### FAZA 4: Data Analysis (DEFERRED)
- **4A** (laravel-expert): 1h - Analysis Script

**GRAND TOTAL:** 12-18h (1.5-2.5 days)

---

## 🚨 RISK ASSESSMENT

### FAZA 1 RISKS

**RISK #1: PrestaShop API Compatibility**
- **Problem:** Different PrestaShop installations may use different API endpoints dla tax_rule_groups
- **Likelihood:** MEDIUM
- **Impact:** HIGH (blocking FAZA 1A)
- **Mitigation:**
  - Test z PrestaShop 8.x i 9.x sandbox
  - Fallback to manual input jeśli API unavailable
  - Document required API permissions

**RISK #2: Complex Tax Rules Structures**
- **Problem:** PrestaShop tax_rule_groups may have complex hierarchies (country-specific, state-specific)
- **Likelihood:** LOW
- **Impact:** MEDIUM (confusion dla users)
- **Mitigation:**
  - Display full group name (e.g., "PL Standard 23% (ID: 6)")
  - Tooltip z group details
  - Smart defaults auto-detection

**RISK #3: Wizard Step Validation Logic**
- **Problem:** Adding tax rules to Step 3 may conflict z existing connection test flow
- **Likelihood:** LOW
- **Impact:** MEDIUM (UX confusion)
- **Mitigation:**
  - Tax rules fetch ONLY after successful connection test
  - Clear separation: Step 3a (Connection) → Step 3b (Tax Rules)
  - Allow skip if tax rules fetch fails

### FAZA 2 RISKS

**RISK #4: UI Complexity in ProductForm**
- **Problem:** Per-shop tax overrides add complexity to already complex Physical Properties tab
- **Likelihood:** HIGH
- **Impact:** MEDIUM (user confusion)
- **Mitigation:**
  - Collapsible section "Nadpisania per sklep"
  - Show ONLY dla exported shops
  - Clear labels: "Użyj domyślnej (23%)"

**RISK #5: Validation Indicator Accuracy**
- **Problem:** Validation badge (✓ / ⚠) may be inaccurate jeśli shop config outdated
- **Likelihood:** MEDIUM
- **Impact:** LOW (cosmetic, misleading)
- **Mitigation:**
  - Cache shop tax_rules_mappings in ProductForm
  - Refresh button: "Odśwież mapowania"
  - Tooltip: "Last updated: YYYY-MM-DD"

**RISK #6: Migration Conflicts**
- **Problem:** `tax_rate_override` column may conflict z existing migrations
- **Likelihood:** LOW
- **Impact:** HIGH (blocking FAZA 2A)
- **Mitigation:**
  - Run migration on test DB first
  - Rollback plan (down() method)
  - Verify column doesn't exist before migration

### FAZA 3 RISKS

**RISK #7: ProductTransformer Performance**
- **Problem:** Additional ProductShopData query per sync may slow down bulk operations
- **Likelihood:** MEDIUM
- **Impact:** LOW (performance degradation)
- **Mitigation:**
  - Eager load ProductShopData w bulk sync jobs
  - Cache tax_rules_mappings per shop
  - Monitor query count (avoid N+1)

**RISK #8: Checksum Calculation Overhead**
- **Problem:** Including tax_rate_override in checksum adds calculation overhead
- **Likelihood:** LOW
- **Impact:** LOW (negligible performance cost)
- **Mitigation:**
  - Already using md5(json_encode()) (fast)
  - Single additional field (minimal impact)

---

## 🧪 TESTING STRATEGY

### UNIT TESTS

**FAZA 1: AddShop**
- Test `fetchPrestashopTaxRulesGroups()` z mock PrestaShop responses
- Test `autoMapTaxRules()` z różnymi group names
- Test validation: 23% VAT required
- Test save logic: tax_rules_group_id_XX saved correctly

**FAZA 2: ProductForm**
- Test `loadTaxRateOverrides()` loading existing overrides
- Test `getMappedTaxRulesGroupId()` reverse mapping logic
- Test `saveShopData()` saving overrides
- Test checksum generation z tax_rate_override

**FAZA 3: Backend Integration**
- Test `mapTaxRateWithOverride()` priority (override → default)
- Test ProductTransformer z różnymi override scenarios
- Test checksum detection (override change → sync pending)

### INTEGRATION TESTS

**Scenario 1: AddShop Tax Rules Mapping**
1. Create new shop
2. Enter API credentials
3. Test connection (success)
4. Verify tax rules auto-fetched
5. Verify smart defaults selected (23% → group ID X)
6. Save shop
7. Verify `prestashop_shops.tax_rules_group_id_23` = X

**Scenario 2: ProductForm Tax Override**
1. Open existing product
2. Navigate to Physical Properties tab
3. Set default tax_rate = 23%
4. Navigate to Sklepy tab → add shop X
5. Navigate back to Physical Properties
6. Set tax_rate_override dla shop X = 8%
7. Save product
8. Verify `product_shop_data.tax_rate_override` = 8.00
9. Verify sync status → 'pending'

**Scenario 3: Synchronizacja z Override**
1. Product: tax_rate = 23% (default)
2. Shop X: tax_rate_override = 8%
3. Trigger sync to shop X
4. Verify ProductTransformer użył 8% (not 23%)
5. Verify PrestaShop product ma id_tax_rules_group = 2 (8% group)
6. Verify sync status → 'synced'

### MANUAL TESTS (User Acceptance)

**UAT 1: Admin Dodaje Sklep**
- User: Admin dodaje nowy sklep PrestaShop
- Expectation: Tax rules auto-fetched, smart defaults selected
- Success: 23% zmapowane do poprawnego group ID

**UAT 2: Admin Edytuje Sklep**
- User: Admin edytuje istniejący sklep
- Clicks "Odśwież z PrestaShop"
- Expectation: Tax rules re-fetched, mappings updated
- Success: Nowe grupy pojawiają się w dropdowns

**UAT 3: Redaktor Ustawia Tax Override**
- User: Redaktor edytuje produkt
- Sets default tax_rate = 23%
- For shop "UK", sets override = 20%
- Saves product
- Expectation: Override saved, validation badge shows ✓
- Success: PrestaShop sync używa 20% dla UK shop

---

## 📁 PLIKI DO UTWORZENIA / MODYFIKACJI

### NOWE PLIKI (Create)
- `database/migrations/2025_11_14_140000_add_tax_rate_override_to_product_shop_data.php` (FAZA 2A.1)

### MODYFIKOWANE PLIKI (Edit)

**Backend:**
- `app/Services/PrestaShop/BasePrestaShopClient.php` (1A.1)
- `app/Services/PrestaShop/PrestaShop8Client.php` (1A.1)
- `app/Services/PrestaShop/PrestaShop9Client.php` (1A.1)
- `app/Models/ProductShopData.php` (2A.2, 3B.1)
- `app/Services/PrestaShop/ProductTransformer.php` (3A.1)
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` (2A.4)

**Livewire Components:**
- `app/Http/Livewire/Admin/Shops/AddShop.php` (1A.2, 1A.4, 1B.1, 1B.2)
- `app/Http/Livewire/Products/Management/ProductForm.php` (2A.3)

**Views:**
- `resources/views/livewire/admin/shops/add-shop.blade.php` (1A.3)
- `resources/views/livewire/products/management/product-form.blade.php` (2A.3)

**CSS:**
- `resources/css/admin/components.css` (1A.3 - tax rules mapping section)

---

## 📝 DODATKOWE DOKUMENTY DO UTWORZENIA

### Documentation Files
- `_DOCS/TAX_RULES_MAPPING_GUIDE.md` - User guide dla tax rules configuration
- `_ISSUES_FIXES/TAX_RATE_OVERRIDE_IMPLEMENTATION.md` - Technical reference dla developers

### Test Scripts (Optional)
- `_TOOLS/test_tax_rules_mapping.php` - Test script dla tax rules API
- `_TOOLS/analyze_tax_rate_discrepancies.php` - Analysis script (FAZA 4A)

---

## 🎯 SUCCESS CRITERIA - FINAL CHECKLIST

### FAZA 1: /admin/shops Enhancement
- ✅ Tax rules groups fetched from PrestaShop API
- ✅ Smart defaults auto-detected (23% → correct group ID)
- ✅ Dropdown selection UI dla 4 stawek (23%, 8%, 5%, 0%)
- ✅ Validation: 23% VAT mapping required
- ✅ Save logic: tax_rules_group_id_XX persisted
- ✅ Edit mode: existing mappings loaded correctly
- ✅ Refresh button: re-fetches tax rules from PrestaShop
- ✅ Error handling: graceful fallback jeśli API unavailable

### FAZA 2: ProductForm Enhancement
- ✅ Migration: tax_rate_override column created
- ✅ Model: ProductShopData allows mass assignment
- ✅ UI: Default tax rate shows "PPM Default" badge
- ✅ UI: Per-shop overrides section (exported shops only)
- ✅ UI: Dropdown z "Użyj domyślnej" option
- ✅ UI: Validation indicators (✓ / ⚠) per shop
- ✅ Save logic: tax_rate_override persisted correctly
- ✅ Checksum: updated when override changes

### FAZA 3: Backend Integration
- ✅ ProductTransformer: uses tax_rate_override (priority over default)
- ✅ Checksum: includes tax_rate_override
- ✅ Sync: products synchronize z correct tax rate
- ✅ PrestaShop: id_tax_rules_group matches configured mapping
- ✅ Performance: no N+1 queries, eager loading optimized

---

## ⚠️ PROBLEMY/BLOKERY

**NONE** - All dependencies clear, parallel work possible

---

## 📋 NASTĘPNE KROKI

### IMMEDIATE ACTIONS (Next 1h)

1. **laravel-expert**: Create FAZA 2A.1 migration (`tax_rate_override` column)
   - Priority: HIGH (required dla wszystkich innych tasks)
   - Estimated time: 30min
   - Blocking: FAZA 2A.2, 2A.3, 2A.4

2. **prestashop-api-expert**: Implement FAZA 1A.1 (PrestaShop Tax Rules API)
   - Priority: HIGH (required dla FAZA 1A.2)
   - Estimated time: 2-3h
   - Parallel with: laravel-expert migration

3. **livewire-specialist**: Implement FAZA 1A.2 (AddShop Livewire Component)
   - Priority: HIGH (depends on 1A.1)
   - Estimated time: 2-3h
   - Sequential after: prestashop-api-expert

### COORDINATION

**Suggested Workflow:**
1. **DAY 1 Morning:** laravel-expert (migration) + prestashop-api-expert (API) - **PARALLEL**
2. **DAY 1 Afternoon:** livewire-specialist (AddShop component) - **SEQUENTIAL**
3. **DAY 1 Evening:** frontend-specialist (Blade template + CSS) - **SEQUENTIAL**
4. **DAY 2 Morning:** laravel-expert (AddShop save logic) + livewire-specialist (Edit mode) - **PARALLEL**
5. **DAY 2 Afternoon:** livewire-specialist (ProductForm UI) + laravel-expert (ProductFormSaver) - **SEQUENTIAL**
6. **DAY 2 Evening:** prestashop-api-expert (ProductTransformer) + laravel-expert (Checksum) - **SEQUENTIAL**

**Total Time:** 1.5-2 days (12-16h)

---

## 📁 PLIKI

**Architectural Reports:**
- `_AGENT_REPORTS/architect_tax_rules_ui_enhancement_2025-11-14_REPORT.md` - Ten dokument

**Plan Projektu Updates:**
- `Plan_Projektu/ETAP_07_Prestashop_API.md` - Dodać nową sekcję FAZA 5

---

**Koniec Raportu**
