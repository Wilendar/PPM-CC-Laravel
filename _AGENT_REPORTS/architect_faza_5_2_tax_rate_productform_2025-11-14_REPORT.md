# RAPORT PRACY AGENTA: architect
**Data**: 2025-11-14 15:30
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: FAZA 5.2 - ProductForm Tax Rate Enhancement (Architectural Plan)

---

## 📋 EXECUTIVE SUMMARY

**Status**: ✅ ARCHITECTURAL PLAN COMPLETED
**Complexity**: HIGH (Multi-store, PrestaShop API integration, Dynamic dropdowns, Indicator system)
**Estimated Implementation**: 12-16h (3 specialists: laravel-expert, livewire-specialist, frontend-specialist)

**KONTEKST:**
- FAZA 5.1 ✅ COMPLETED: Tax Rules mapping w AddShop/EditShop (4 stawki: 23%, 8%, 5%, 0%)
- Backend migration ✅: `product_shop_data.tax_rate_override` (DECIMAL 5,2)
- PrestaShop API method ✅: `getTaxRuleGroups()` dostępne w PS8/PS9 clients

**PROBLEM:**
- Tax Rate field NIEPRAWIDŁOWO umieszczone w zakładce "Właściwości fizyczne" (physical tab)
- Brak integracji ze zmapowanymi regułami podatkowymi z PrestaShop
- Prosty input numeryczny zamiast intelligent dropdown z PrestaShop tax rules

**CEL:**
1. **PRZENIESIENIE:** Physical tab → Basic tab (proper categorization)
2. **ROZBUDOWA:** Smart dropdown z zmapowanymi tax rules per shop
3. **INTEGRACJA:** Per-shop tax rate overrides (`product_shop_data.tax_rate_override`)
4. **VALIDATION:** Indicator system (green/yellow/red) jak przy innych polach

---

## 🎯 ZAKRES FAZA 5.2

### ✅ W ZAKRESIE:
- ✅ Przeniesienie Tax Rate field z Physical → Basic tab
- ✅ Default mode: Dropdown [23%, 8%, 5%, 0%, Custom] + input numeryczny dla Custom
- ✅ Shop-specific mode: Intelligent dropdown z PrestaShop tax rules
- ✅ Per-shop overrides: `product_shop_data.tax_rate_override` (NULL = use default)
- ✅ Indicator system integration (green/yellow/red)
- ✅ Validation: Unique tax rate selection per shop
- ✅ Fallback handling: Brak mappings / brak połączenia

### ❌ POZA ZAKRESEM (Future):
- ❌ Automatic tax rate selection based on product category
- ❌ Bulk tax rate update dla wielu produktów
- ❌ Tax rate history tracking (audit trail)
- ❌ Advanced tax rules (regional, seasonal, customer group)

---

## 📐 ARCHITEKTURA SYSTEMU

### 1. DATA FLOW ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────────┐
│                    FAZA 5.2: TAX RATE FLOW                       │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│  MOUNT COMPONENT │
└────────┬─────────┘
         │
         ▼
┌────────────────────────────────────────────────────────┐
│ LOAD PHASE                                              │
├────────────────────────────────────────────────────────┤
│ 1. Load products.tax_rate (default)                    │
│ 2. Load product_shop_data.tax_rate_override per shop   │
│ 3. Load prestashop_shops.tax_rules_group_id_XX         │
│ 4. Fetch PrestaShop tax rule groups names (API)        │
│    - Cache: $availableTaxRuleGroups[shopId]            │
│    - Cache TTL: 1 hour (refresh on edit)               │
└────────┬───────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────────────────────┐
│ DISPLAY PHASE                                           │
├────────────────────────────────────────────────────────┤
│ IF activeShopId === null (DEFAULT MODE):               │
│   → Show: "Stawka VAT domyślna (%)"                    │
│   → Dropdown: [23%, 8%, 5%, 0%, Własna stawka]         │
│   → If "Własna stawka": Show input[type=number]        │
│   → Current: products.tax_rate                          │
│                                                         │
│ ELSE (SHOP-SPECIFIC MODE):                             │
│   → Show: "Stawka VAT dla {shop_name}"                 │
│   → Dropdown:                                           │
│     1. "Użyj domyślnej PPM ({default_rate}%)" [DEFAULT]│
│     2. PrestaShop tax rules (if mapped):               │
│        - "VAT 23% (PrestaShop: {group_name})"          │
│        - "VAT 8% (PrestaShop: {group_name})"           │
│        - "VAT 5% (PrestaShop: {group_name})"           │
│        - "VAT 0% (PrestaShop: {group_name})"           │
│     3. "Własna stawka" → Show input[type=number]       │
│   → Current: tax_rate_override ?? products.tax_rate    │
│                                                         │
│ → Indicator: Green/Yellow/Red badge                    │
└────────┬───────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────────────────────┐
│ EDIT PHASE                                              │
├────────────────────────────────────────────────────────┤
│ USER SELECTS OPTION:                                    │
│                                                         │
│ IF "23%", "8%", "5%", "0%":                             │
│   → wire:model="tax_rate" (default)                    │
│   → wire:model="shopTaxRateOverrides[{shopId}]" (shop) │
│   → Hide custom input                                   │
│                                                         │
│ IF "Własna stawka":                                     │
│   → Show: input[type=number, step=0.01, min=0, max=100]│
│   → wire:model.live="customTaxRate"                    │
│   → Validation: 0.00 - 100.00 range                    │
│                                                         │
│ IF "Użyj domyślnej PPM" (shop mode only):              │
│   → Set: shopTaxRateOverrides[shopId] = null           │
│   → Display: Inherited indicator (blue)                │
└────────┬───────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────────────────────┐
│ SAVE PHASE                                              │
├────────────────────────────────────────────────────────┤
│ DEFAULT MODE (activeShopId === null):                  │
│   → UPDATE products SET tax_rate = ?                   │
│                                                         │
│ SHOP MODE (activeShopId !== null):                     │
│   IF "Użyj domyślnej PPM":                             │
│     → UPDATE product_shop_data                         │
│       SET tax_rate_override = NULL                     │
│   ELSE:                                                 │
│     → UPDATE product_shop_data                         │
│       SET tax_rate_override = ?                        │
│                                                         │
│ → Mark field as "pending sync" (pending_fields array)  │
│ → Recalculate indicator status                         │
│ → Trigger save success message                         │
└────────────────────────────────────────────────────────┘
```

---

## 🎨 UI/UX DESIGN SPECIFICATION

### 2.1 BLADE STRUCTURE (Basic Tab)

**TARGET LOCATION:** `product-form.blade.php` - Basic tab (lines 280-700)
**PLACEMENT:** Po "SKU / Product Type / Name", przed "Description / Status"
**GRID POSITION:** Full width (md:col-span-2) lub single column (depends on density)

#### PSEUDO-CODE BLADE:

```blade
{{-- Tax Rate Field - RELOCATED FROM PHYSICAL TAB (2025-11-14 FAZA 5.2) --}}
<div class="{{ $activeShopId === null ? 'md:col-span-1' : 'md:col-span-2' }}">
    <label for="tax_rate" class="block text-sm font-medium text-gray-300 mb-2">
        @if($activeShopId === null)
            Stawka VAT domyślna (%)
        @else
            @php
                $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
            @endphp
            Stawka VAT dla {{ $currentShop['name'] ?? 'sklepu' }}
        @endif
        <span class="text-red-500">*</span>

        {{-- STATUS INDICATOR --}}
        @php
            $taxRateIndicator = $this->getFieldStatusIndicator('tax_rate');
        @endphp
        @if($taxRateIndicator['show'])
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $taxRateIndicator['class'] }}">
                {{ $taxRateIndicator['text'] }}
            </span>
        @endif
    </label>

    {{-- DROPDOWN: Tax Rate Selection --}}
    <select wire:model.live="selectedTaxRateOption"
            id="tax_rate"
            class="{{ $this->getFieldClasses('tax_rate') }} @error('tax_rate') !border-red-500 @enderror">

        @if($activeShopId === null)
            {{-- DEFAULT MODE: Standard rates + Custom --}}
            <option value="23">23% (standardowa)</option>
            <option value="8">8% (obniżona)</option>
            <option value="5">5% (preferencyjna)</option>
            <option value="0">0% (zwolniona)</option>
            <option value="custom">Własna stawka...</option>
        @else
            {{-- SHOP-SPECIFIC MODE: Inherit + PrestaShop rules + Custom --}}
            @php
                $defaultRate = $this->product?->tax_rate ?? 23.00;
            @endphp
            <option value="inherit">Użyj domyślnej PPM ({{ number_format($defaultRate, 2) }}%)</option>

            {{-- PrestaShop Tax Rules (if mapped) --}}
            @foreach($this->getAvailableTaxRulesForShop($activeShopId) as $taxRule)
                <option value="prestashop-{{ $taxRule['rate'] }}">
                    VAT {{ number_format($taxRule['rate'], 2) }}%
                    (PrestaShop: {{ $taxRule['name'] }})
                </option>
            @endforeach

            <option value="custom">Własna stawka...</option>
        @endif
    </select>

    {{-- CONDITIONAL: Custom Tax Rate Input --}}
    @if($selectedTaxRateOption === 'custom')
        <input wire:model.live="customTaxRate"
               type="number"
               step="0.01"
               min="0"
               max="100"
               placeholder="Wpisz stawkę VAT (0.00 - 100.00)"
               class="mt-2 {{ $this->getFieldClasses('tax_rate') }} @error('customTaxRate') !border-red-500 @enderror">
        @error('customTaxRate')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif

    {{-- HELP TEXT --}}
    @if($activeShopId !== null)
        <p class="mt-2 text-xs text-gray-400">
            <svg class="w-4 h-4 inline mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Wybierz zmapowaną regułę podatkową PrestaShop lub własną stawkę dla tego sklepu.
        </p>
    @endif

    @error('tax_rate')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
```

---

### 2.2 VISUAL STATES

#### STATE 1: Default Mode (activeShopId === null)
```
┌────────────────────────────────────────────────────────┐
│ Stawka VAT domyślna (%) *                              │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 23% (standardowa)                             [▼] │ │
│ └────────────────────────────────────────────────────┘ │
│                                                        │
│ Options:                                               │
│ • 23% (standardowa)                                    │
│ • 8% (obniżona)                                        │
│ • 5% (preferencyjna)                                   │
│ • 0% (zwolniona)                                       │
│ • Własna stawka...                                     │
└────────────────────────────────────────────────────────┘
```

#### STATE 2: Shop-Specific Mode (activeShopId !== null)
```
┌────────────────────────────────────────────────────────┐
│ Stawka VAT dla Pitbike.pl * [🟢 Zsynchronizowany]     │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Użyj domyślnej PPM (23.00%)                   [▼] │ │
│ └────────────────────────────────────────────────────┘ │
│                                                        │
│ Options:                                               │
│ • Użyj domyślnej PPM (23.00%)                          │
│ • VAT 23.00% (PrestaShop: PL Standard Rate)           │
│ • VAT 8.00% (PrestaShop: Reduced Rate)                │
│ • VAT 5.00% (PrestaShop: Super Reduced)               │
│ • VAT 0.00% (PrestaShop: Exempt)                      │
│ • Własna stawka...                                     │
│                                                        │
│ ℹ️ Wybierz zmapowaną regułę podatkową PrestaShop      │
│   lub własną stawkę dla tego sklepu.                  │
└────────────────────────────────────────────────────────┘
```

#### STATE 3: Custom Tax Rate (selectedTaxRateOption === 'custom')
```
┌────────────────────────────────────────────────────────┐
│ Stawka VAT dla Pitbike.pl * [🟡 Oczekuje sync]        │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Własna stawka...                              [▼] │ │
│ └────────────────────────────────────────────────────┘ │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 12.50                                              │ │
│ └────────────────────────────────────────────────────┘ │
│ Wpisz stawkę VAT (0.00 - 100.00)                      │
└────────────────────────────────────────────────────────┘
```

---

## 🔧 LIVEWIRE BACKEND INTEGRATION

### 3.1 NEW PROPERTIES

```php
/**
 * ProductForm.php - New Properties (FAZA 5.2)
 */

// === TAX RATE MANAGEMENT ===
public ?float $tax_rate = 23.00; // Default tax rate (already exists)

// NEW: Dropdown selection state
public string $selectedTaxRateOption = '23'; // '23', '8', '5', '0', 'inherit', 'prestashop-XX', 'custom'

// NEW: Custom tax rate input (when "Własna stawka" selected)
public ?float $customTaxRate = null;

// NEW: Per-shop tax rate overrides [shopId => override_rate]
public array $shopTaxRateOverrides = []; // [1 => 8.00, 2 => null (inherit), 3 => 12.50 (custom)]

// NEW: Cached PrestaShop tax rule groups per shop [shopId => [['id' => X, 'name' => 'Y', 'rate' => Z], ...]]
public array $availableTaxRuleGroups = [];

// NEW: Cache timestamp for tax rule groups [shopId => Carbon]
public array $taxRuleGroupsCacheTimestamp = [];
```

---

### 3.2 NEW METHODS

#### 3.2.1 Loading Tax Rule Groups

```php
/**
 * Load tax rule groups from PrestaShop for specific shop
 *
 * CACHING STRATEGY:
 * - Cache per shop in component property
 * - TTL: 1 hour (refresh on edit, manual refresh button)
 * - Fallback: Empty array if API fails
 *
 * @param int $shopId
 * @return array [['id' => 6, 'name' => 'PL Standard Rate', 'rate' => 23.0], ...]
 */
public function loadTaxRuleGroupsForShop(int $shopId): array
{
    // Check cache (1 hour TTL)
    if (
        isset($this->availableTaxRuleGroups[$shopId]) &&
        isset($this->taxRuleGroupsCacheTimestamp[$shopId]) &&
        $this->taxRuleGroupsCacheTimestamp[$shopId]->diffInMinutes(now()) < 60
    ) {
        Log::debug('Using cached tax rule groups', [
            'shop_id' => $shopId,
            'cached_at' => $this->taxRuleGroupsCacheTimestamp[$shopId]->toDateTimeString(),
        ]);
        return $this->availableTaxRuleGroups[$shopId];
    }

    try {
        $shop = PrestaShopShop::findOrFail($shopId);
        $client = PrestaShopClientFactory::make($shop);

        // Fetch tax rule groups from PrestaShop
        $taxRuleGroups = $client->getTaxRuleGroups();

        // Cache results
        $this->availableTaxRuleGroups[$shopId] = $taxRuleGroups;
        $this->taxRuleGroupsCacheTimestamp[$shopId] = now();

        Log::info('Tax rule groups loaded from PrestaShop', [
            'shop_id' => $shopId,
            'count' => count($taxRuleGroups),
        ]);

        return $taxRuleGroups;

    } catch (\Exception $e) {
        Log::error('Failed to load tax rule groups', [
            'shop_id' => $shopId,
            'error' => $e->getMessage(),
        ]);

        // Fallback: Empty array (will show only "Inherit" + "Custom")
        $this->availableTaxRuleGroups[$shopId] = [];
        $this->taxRuleGroupsCacheTimestamp[$shopId] = now();

        return [];
    }
}
```

#### 3.2.2 Getting Available Tax Rules with Mappings

```php
/**
 * Get available tax rules for shop with mapping data
 *
 * LOGIC:
 * 1. Load tax rule groups from PrestaShop (cached)
 * 2. Filter by mapped tax_rules_group_id_XX from prestashop_shops
 * 3. Return enriched data with rate + name
 *
 * @param int $shopId
 * @return array [['rate' => 23.0, 'name' => 'PL Standard Rate', 'group_id' => 6], ...]
 */
public function getAvailableTaxRulesForShop(int $shopId): array
{
    $shop = PrestaShopShop::find($shopId);
    if (!$shop) {
        return [];
    }

    // Load PrestaShop tax rule groups
    $allTaxRuleGroups = $this->loadTaxRuleGroupsForShop($shopId);

    // Map: tax_rules_group_id_XX → PrestaShop group data
    $mappedRates = [23, 8, 5, 0];
    $availableRules = [];

    foreach ($mappedRates as $rate) {
        $mappedGroupId = $shop->{"tax_rules_group_id_{$rate}"};

        if (!$mappedGroupId) {
            continue; // Skip unmapped rates
        }

        // Find group in PrestaShop data
        $group = collect($allTaxRuleGroups)->firstWhere('id', $mappedGroupId);

        if ($group) {
            $availableRules[] = [
                'rate' => (float) $rate,
                'name' => $group['name'],
                'group_id' => $mappedGroupId,
            ];
        }
    }

    return $availableRules;
}
```

#### 3.2.3 Saving Tax Rate

```php
/**
 * Save tax rate based on selected option
 *
 * SCENARIOS:
 * 1. Default mode (activeShopId === null):
 *    → Save to products.tax_rate
 *
 * 2. Shop mode - "inherit":
 *    → Save NULL to product_shop_data.tax_rate_override
 *
 * 3. Shop mode - standard rate (23/8/5/0):
 *    → Save rate to product_shop_data.tax_rate_override
 *
 * 4. Shop mode - "custom":
 *    → Validate customTaxRate (0.00-100.00)
 *    → Save to product_shop_data.tax_rate_override
 *
 * @return void
 */
public function saveTaxRate(): void
{
    // Validate selected option
    $this->validate([
        'selectedTaxRateOption' => 'required|string',
        'customTaxRate' => [
            'nullable',
            'numeric',
            'min:0',
            'max:100',
            Rule::requiredIf($this->selectedTaxRateOption === 'custom'),
        ],
    ]);

    if ($this->activeShopId === null) {
        // DEFAULT MODE: Save to products.tax_rate
        $this->saveTaxRateDefault();
    } else {
        // SHOP MODE: Save to product_shop_data.tax_rate_override
        $this->saveTaxRateOverride($this->activeShopId);
    }

    // Mark field as pending sync
    $this->markFieldAsPendingSync('tax_rate', $this->activeShopId);

    $this->dispatch('tax-rate-saved');
    session()->flash('success', 'Stawka VAT została zapisana.');
}

/**
 * Save tax rate to products table (default mode)
 */
protected function saveTaxRateDefault(): void
{
    $rate = $this->resolveTaxRateFromOption();

    $this->tax_rate = $rate;
    $this->product->tax_rate = $rate;
    $this->product->save();

    Log::info('Default tax rate saved', [
        'product_id' => $this->product->id,
        'tax_rate' => $rate,
    ]);
}

/**
 * Save tax rate override to product_shop_data table (shop mode)
 */
protected function saveTaxRateOverride(int $shopId): void
{
    $rate = $this->resolveTaxRateFromOption();

    // NULL = inherit from products.tax_rate
    $overrideRate = ($this->selectedTaxRateOption === 'inherit') ? null : $rate;

    ProductShopData::updateOrCreate(
        ['product_id' => $this->product->id, 'shop_id' => $shopId],
        ['tax_rate_override' => $overrideRate]
    );

    $this->shopTaxRateOverrides[$shopId] = $overrideRate;

    Log::info('Shop tax rate override saved', [
        'product_id' => $this->product->id,
        'shop_id' => $shopId,
        'tax_rate_override' => $overrideRate,
    ]);
}

/**
 * Resolve tax rate from selected option
 *
 * @return float
 */
protected function resolveTaxRateFromOption(): float
{
    if ($this->selectedTaxRateOption === 'custom') {
        return (float) $this->customTaxRate;
    }

    if (str_starts_with($this->selectedTaxRateOption, 'prestashop-')) {
        // Extract rate from "prestashop-23.00"
        return (float) str_replace('prestashop-', '', $this->selectedTaxRateOption);
    }

    // Standard rates: '23', '8', '5', '0'
    return (float) $this->selectedTaxRateOption;
}
```

#### 3.2.4 Display Helper

```php
/**
 * Get effective tax rate for display
 *
 * Shows current effective tax rate considering:
 * - Shop override (if exists)
 * - Default product tax rate (fallback)
 *
 * @param int|null $shopId
 * @return float
 */
public function getEffectiveTaxRate(?int $shopId = null): float
{
    if ($shopId === null) {
        return $this->tax_rate ?? 23.00;
    }

    // Check shop override
    $override = $this->shopTaxRateOverrides[$shopId] ?? null;

    if ($override !== null) {
        return $override;
    }

    // Check database
    $shopData = ProductShopData::where('product_id', $this->product?->id)
        ->where('shop_id', $shopId)
        ->first();

    if ($shopData && $shopData->tax_rate_override !== null) {
        return $shopData->tax_rate_override;
    }

    // Fallback to default
    return $this->tax_rate ?? 23.00;
}
```

---

### 3.3 LIVEWIRE UPDATES

```php
/**
 * Listen for selectedTaxRateOption changes
 *
 * When user changes dropdown:
 * 1. If "custom" → Show custom input
 * 2. Otherwise → Hide custom input, set customTaxRate = null
 */
#[On('tax-rate-option-changed')]
public function updatedSelectedTaxRateOption($value): void
{
    if ($value !== 'custom') {
        $this->customTaxRate = null;
    }

    // Auto-save if not in custom mode
    if ($value !== 'custom') {
        $this->saveTaxRate();
    }
}
```

---

## 🎨 INDICATOR SYSTEM INTEGRATION

### 4.1 Existing Indicator Logic Extension

**CURRENT SYSTEM** (ProductForm.php:2147-2190):
- `getFieldStatusIndicator(string $field): array`
- Returns: `['show' => bool, 'text' => string, 'class' => string]`
- States: pending-sync, inherited, same, different, default

**NEW LOGIC for 'tax_rate':**

```php
/**
 * Get field status indicator for tax_rate field
 *
 * PRIORITY LOGIC:
 * 1. Pending sync (yellow): Field changed, awaiting sync
 * 2. Different (red): PPM ≠ PrestaShop (validation warning)
 * 3. Same (green): PPM === PrestaShop (synced)
 * 4. Inherited (blue): Shop using default PPM rate
 * 5. Default (no indicator): Normal state
 *
 * @param string $field 'tax_rate'
 * @return array
 */
public function getFieldStatusIndicator(string $field): array
{
    // ... existing logic for other fields ...

    if ($field === 'tax_rate') {
        return $this->getTaxRateIndicator();
    }

    // ... fallback ...
}

/**
 * Get tax rate specific indicator
 */
protected function getTaxRateIndicator(): array
{
    // PRIORITY 1: Pending sync
    if ($this->activeShopId !== null && $this->isPendingSyncForShop($this->activeShopId, 'tax_rate')) {
        return [
            'show' => true,
            'text' => 'Oczekuje na synchronizację',
            'class' => 'pending-sync-badge', // Yellow
        ];
    }

    // PRIORITY 2: Validation warnings (PPM ≠ PrestaShop)
    if ($this->activeShopId !== null && $this->hasTaxRateValidationWarning($this->activeShopId)) {
        return [
            'show' => true,
            'text' => 'Różni się od PrestaShop',
            'class' => 'bg-red-900/30 text-red-200 border border-red-700/50', // Red
        ];
    }

    // PRIORITY 3: Inherited (shop using default)
    if ($this->activeShopId !== null) {
        $override = $this->shopTaxRateOverrides[$this->activeShopId] ?? null;

        if ($override === null) {
            return [
                'show' => true,
                'text' => 'Odziedziczone z PPM',
                'class' => 'field-status-inherited', // Blue
            ];
        }
    }

    // PRIORITY 4: Synced (green)
    if ($this->activeShopId !== null && $this->isTaxRateSynced($this->activeShopId)) {
        return [
            'show' => true,
            'text' => 'Zsynchronizowany',
            'class' => 'bg-green-900/30 text-green-200 border border-green-700/50', // Green
        ];
    }

    // DEFAULT: No indicator
    return [
        'show' => false,
        'text' => '',
        'class' => '',
    ];
}
```

### 4.2 Validation Warning Detection

```php
/**
 * Check if tax rate has validation warning
 *
 * LOGIC: Compare PPM tax_rate vs PrestaShop tax_rate
 * Source: product_shop_data.validation_warnings (JSON)
 *
 * @param int $shopId
 * @return bool
 */
protected function hasTaxRateValidationWarning(int $shopId): bool
{
    $shopData = ProductShopData::where('product_id', $this->product?->id)
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData || !$shopData->validation_warnings) {
        return false;
    }

    // Check if validation_warnings contains tax_rate warning
    $warnings = is_string($shopData->validation_warnings)
        ? json_decode($shopData->validation_warnings, true)
        : $shopData->validation_warnings;

    if (!is_array($warnings)) {
        return false;
    }

    foreach ($warnings as $warning) {
        if (($warning['field'] ?? '') === 'tax_rate' && ($warning['severity'] ?? '') !== 'info') {
            return true;
        }
    }

    return false;
}

/**
 * Check if tax rate is synced with PrestaShop
 *
 * @param int $shopId
 * @return bool
 */
protected function isTaxRateSynced(int $shopId): bool
{
    $shopData = ProductShopData::where('product_id', $this->product?->id)
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData) {
        return false;
    }

    // Check if tax_rate is NOT in pending_fields
    $pendingFields = is_string($shopData->pending_fields)
        ? json_decode($shopData->pending_fields, true)
        : ($shopData->pending_fields ?? []);

    return !in_array('tax_rate', $pendingFields, true) && $shopData->sync_status === 'synced';
}
```

---

## 🚨 EDGE CASES & ERROR HANDLING

### 5.1 Edge Cases Matrix

| Scenario | Behavior | Error Handling |
|----------|----------|----------------|
| **No tax rules mapped** | Show: "Użyj domyślnej PPM" + "Własna stawka" only | Warning message: "Sklep nie ma zmapowanych reguł podatkowych" |
| **PrestaShop API down** | Use cached tax rules (if available) | Fallback: Empty dropdown options, log error |
| **Invalid tax rate (>100%)** | Validation error | Show: "Stawka VAT musi być w zakresie 0.00 - 100.00" |
| **Shop deleted** | Display warning in shop selector | Hide shop-specific fields, prevent save |
| **Product not synced yet** | Show: "Produkt nie zsynchronizowany" indicator | Allow setting tax rate, sync on first push |
| **Conflicting tax rates** | Show red indicator + validation warning | Manual resolution: User chooses PPM or PrestaShop |
| **Custom rate = standard rate** | Auto-convert to standard option | Simplify data (8.00 custom → "8" standard) |
| **NULL tax_rate_override** | Display "Odziedziczone" indicator | Inherit from products.tax_rate |
| **Multiple shops, different rates** | Show per-shop indicators | Each shop independent, no conflict |

---

### 5.2 Validation Rules

```php
/**
 * Tax Rate Validation Rules
 */
protected function taxRateValidationRules(): array
{
    return [
        'selectedTaxRateOption' => [
            'required',
            'string',
            Rule::in(['23', '8', '5', '0', 'inherit', 'custom', ...array_map(fn($r) => "prestashop-{$r}", [23, 8, 5, 0])]),
        ],

        'customTaxRate' => [
            'nullable',
            'numeric',
            'min:0',
            'max:100',
            'regex:/^\d+(\.\d{1,2})?$/', // Max 2 decimal places
            Rule::requiredIf($this->selectedTaxRateOption === 'custom'),
        ],

        'tax_rate' => [
            'required',
            'numeric',
            'min:0',
            'max:100',
        ],
    ];
}

/**
 * Custom validation messages
 */
protected function taxRateValidationMessages(): array
{
    return [
        'customTaxRate.required' => 'Pole "Własna stawka VAT" jest wymagane.',
        'customTaxRate.numeric' => 'Stawka VAT musi być liczbą.',
        'customTaxRate.min' => 'Stawka VAT nie może być ujemna.',
        'customTaxRate.max' => 'Stawka VAT nie może przekraczać 100%.',
        'customTaxRate.regex' => 'Stawka VAT może mieć maksymalnie 2 miejsca po przecinku.',
        'tax_rate.required' => 'Pole "Stawka VAT" jest wymagane.',
    ];
}
```

---

### 5.3 Error Recovery Strategies

#### Strategy 1: PrestaShop API Failure
```php
/**
 * PROBLEM: API timeout/connection error during getTaxRuleGroups()
 *
 * RECOVERY:
 * 1. Try cache (if < 24h old)
 * 2. Fallback: Show only "Inherit" + "Custom" options
 * 3. Log error for monitoring
 * 4. Display warning to user: "Nie można pobrać reguł PrestaShop. Używane dane cache."
 */
```

#### Strategy 2: Missing Mappings
```php
/**
 * PROBLEM: Shop has no tax_rules_group_id_XX mappings
 *
 * RECOVERY:
 * 1. Show info message: "Ten sklep nie ma zmapowanych reguł podatkowych."
 * 2. Link to: /admin/shops/{shopId}/edit (Edit Shop → Tax Rules tab)
 * 3. Allow "Inherit" + "Custom" only
 * 4. Suggest: "Skonfiguruj mapowania w ustawieniach sklepu"
 */
```

#### Strategy 3: Data Corruption
```php
/**
 * PROBLEM: product_shop_data.tax_rate_override contains invalid value (e.g., 999.99)
 *
 * RECOVERY:
 * 1. Detect: Validation during load
 * 2. Auto-fix: Set to NULL (inherit)
 * 3. Log warning
 * 4. Display: "Nieprawidłowa stawka VAT została zresetowana do domyślnej"
 */
```

---

## 📊 MIGRATION PATH

### 6.1 Existing Data Analysis

**CURRENT STATE:**
- ✅ `products.tax_rate` (DECIMAL 5,2) - Exists, populated
- ✅ `product_shop_data.tax_rate_override` (DECIMAL 5,2 NULL) - Exists, migration completed (2025-11-14)
- ✅ `prestashop_shops.tax_rules_group_id_XX` (INT NULL) - Exists, may be NULL if not mapped

**QUESTIONS:**
1. Are existing `products.tax_rate` values valid? (0.00 - 100.00 range)
2. Are there any `product_shop_data.tax_rate_override` values set? (Probably NO - new column)
3. Are tax rules mapped in `prestashop_shops`? (Depends on FAZA 5.1 completion)

---

### 6.2 Data Migration Required?

**ANSWER: ❌ NO MIGRATION REQUIRED**

**REASONS:**
1. **products.tax_rate**: Already exists, no schema change
2. **product_shop_data.tax_rate_override**: Already exists (migration 2025-11-14), defaults to NULL
3. **prestashop_shops.tax_rules_group_id_XX**: Already exists (migration 2025-11-14)

**DATA INTEGRITY CHECKS** (Optional):
```sql
-- Check 1: Verify tax_rate values are within range
SELECT id, sku, tax_rate
FROM products
WHERE tax_rate < 0 OR tax_rate > 100;

-- Check 2: Count products with shop overrides
SELECT COUNT(*)
FROM product_shop_data
WHERE tax_rate_override IS NOT NULL;

-- Check 3: Count shops with tax rules mappings
SELECT id, name,
       tax_rules_group_id_23,
       tax_rules_group_id_8,
       tax_rules_group_id_5,
       tax_rules_group_id_0
FROM prestashop_shops;
```

---

## 🎯 IMPLEMENTATION ROADMAP

### Phase 1: Backend Foundation (laravel-expert) - 4h
- ✅ Add new properties to ProductForm.php
- ✅ Implement loadTaxRuleGroupsForShop() method
- ✅ Implement getAvailableTaxRulesForShop() method
- ✅ Implement saveTaxRate() + helper methods
- ✅ Add validation rules
- ✅ Unit tests for tax rate resolution logic

### Phase 2: Livewire Integration (livewire-specialist) - 4h
- ✅ Add wire:model.live bindings for dropdown
- ✅ Implement updatedSelectedTaxRateOption() listener
- ✅ Add conditional rendering for custom input
- ✅ Integrate with existing save flow
- ✅ Test mount/load/save cycle

### Phase 3: Frontend/UI (frontend-specialist) - 4h
- ✅ Relocate tax_rate field from physical → basic tab
- ✅ Design dropdown with proper styling (match existing fields)
- ✅ Add conditional custom input field
- ✅ Integrate indicator system (reuse existing classes)
- ✅ Add help text and icons
- ✅ Responsive design (mobile/tablet/desktop)

### Phase 4: Indicator System (livewire-specialist) - 2h
- ✅ Extend getFieldStatusIndicator() for tax_rate
- ✅ Implement getTaxRateIndicator() method
- ✅ Add validation warning detection
- ✅ Test all indicator states (pending/inherited/synced/different)

### Phase 5: Testing & Deployment (all specialists) - 2h
- ✅ Manual testing: Default mode + Shop mode
- ✅ Test all dropdown options (standard/inherit/custom)
- ✅ Test validation (invalid rates, missing data)
- ✅ Test edge cases (API down, no mappings, conflicts)
- ✅ Deploy to production
- ✅ Verify with real PrestaShop data

**TOTAL ESTIMATE:** 16h (2 working days for 3 specialists)

---

## 📁 FILES TO MODIFY

### Modified Files:
1. **resources/views/livewire/products/management/product-form.blade.php**
   - Lines 1210-1234: REMOVE tax_rate field from physical tab
   - Lines 280-700: ADD new tax_rate field to basic tab

2. **app/Http/Livewire/Products/Management/ProductForm.php**
   - Add properties: selectedTaxRateOption, customTaxRate, shopTaxRateOverrides, availableTaxRuleGroups, taxRuleGroupsCacheTimestamp
   - Add methods: loadTaxRuleGroupsForShop(), getAvailableTaxRulesForShop(), saveTaxRate(), saveTaxRateDefault(), saveTaxRateOverride(), resolveTaxRateFromOption(), getEffectiveTaxRate()
   - Extend: getFieldStatusIndicator(), add getTaxRateIndicator(), hasTaxRateValidationWarning(), isTaxRateSynced()
   - Add validation rules

3. **resources/css/products/product-form.css** (if needed)
   - Add custom styling for tax rate dropdown (optional, reuse existing)

### No New Files Required

### Database Schema:
- ✅ No changes required (all migrations completed in FAZA 5.1)

---

## ✅ DELIVERABLES CHECKLIST

- [x] Architectural diagram (Data Flow)
- [x] UI/UX design specification (Blade pseudo-code)
- [x] Livewire backend integration (properties, methods, validation)
- [x] Indicator system integration (green/yellow/red logic)
- [x] Edge cases documentation (9 scenarios)
- [x] Error handling strategies (3 recovery patterns)
- [x] Migration path analysis (NO migration required)
- [x] Implementation roadmap (5 phases, 16h estimate)
- [x] Files to modify list (3 files)
- [x] Validation rules specification

---

## 🎓 COMPLIANCE & BEST PRACTICES

### Context7 Integration:
- ✅ Laravel 12.x patterns: Component properties, Livewire lifecycle, validation
- ✅ Livewire 3.x patterns: wire:model.live, #[On] attribute, dispatch()
- ✅ Blade best practices: Conditional rendering, component slots, loops

### PPM-CC-Laravel Compliance:
- ✅ Multi-store architecture: Per-shop overrides, activeShopId context
- ✅ Indicator system: Reuse getFieldStatusIndicator() pattern
- ✅ Pending changes system: markFieldAsPendingSync() integration
- ✅ Field classes: getFieldClasses() for consistent styling
- ✅ Validation system: Enterprise-grade validation rules
- ✅ Error handling: Graceful degradation, user-friendly messages

### CLAUDE.md Compliance:
- ✅ Enterprise-class code (no hardcode, configuration-driven)
- ✅ Separacja odpowiedzialności (separate methods, clear naming)
- ✅ Debug logging (Log::info/warning/error for all operations)
- ✅ No inline styles (CSS classes only)
- ✅ Polish language (UI labels, help text, error messages)

---

## 🚀 NEXT STEPS

### Immediate Actions:
1. **Review architectural plan** with user (obtain approval)
2. **Delegate to specialists**:
   - laravel-expert: Backend foundation (Phase 1)
   - livewire-specialist: Livewire integration (Phase 2) + Indicator system (Phase 4)
   - frontend-specialist: UI implementation (Phase 3)
3. **Create implementation issues** in Plan_Projektu/ETAP_07_Prestashop_API.md

### Post-Implementation:
1. **Manual testing** on production (test all scenarios)
2. **User feedback** gathering (UI/UX improvements)
3. **Documentation update** (CLAUDE.md, _DOCS/)
4. **Plan FAZA 5.3** (if additional enhancements needed)

---

## 📝 NOTES

### Design Decisions:
1. **Why dropdown instead of input?**
   - Better UX: Prevent typos, enforce valid rates
   - PrestaShop integration: Show mapped tax rules
   - Consistency: Match category picker, price groups

2. **Why "inherit" option in shop mode?**
   - Flexibility: Allow shops to use default PPM rate
   - Data efficiency: NULL override = less storage
   - Maintainability: Change default, all shops update

3. **Why cache tax rule groups?**
   - Performance: Avoid API call on every render
   - Reliability: Fallback if API down
   - UX: Instant dropdown population

4. **Why relocate from physical to basic tab?**
   - Categorization: Tax rate is NOT physical property
   - Visibility: Basic tab is first tab (higher discoverability)
   - Grouping: With SKU, name, status (core product info)

### Known Limitations:
1. **Tax rate validation** against PrestaShop is basic (compare numbers only)
   - Future: Validate against tax_rules_group actual rate from PrestaShop
2. **Cache invalidation** is time-based (1 hour TTL)
   - Future: Add manual "Refresh tax rules" button
3. **No bulk update** for multiple products
   - Future: Add bulk tax rate update in product list view

---

## 📚 REFERENCES

- **FAZA 5.1 Reports:**
  - `_AGENT_REPORTS/architect_tax_rules_ui_enhancement_2025-11-14_REPORT.md`
  - `_AGENT_REPORTS/laravel_expert_tax_rate_override_migration_2025-11-14_REPORT.md`
  - `_AGENT_REPORTS/prestashop_api_expert_tax_rules_integration_2025-11-14_REPORT.md`
  - `_AGENT_REPORTS/livewire_specialist_addshop_editshop_tax_rules_2025-11-14_REPORT.md`

- **Database Schema:**
  - `database/migrations/2025_11_14_120000_add_tax_rules_mapping_to_prestashop_shops.php`
  - `database/migrations/2025_11_14_140000_add_tax_rate_override_to_product_shop_data.php`

- **PrestaShop API:**
  - `app/Services/PrestaShop/BasePrestaShopClient.php:500` (abstract getTaxRuleGroups)
  - `app/Services/PrestaShop/PrestaShop8Client.php:564-644` (implementation)
  - `app/Services/PrestaShop/PrestaShop9Client.php` (implementation - similar)

- **ProductForm:**
  - `resources/views/livewire/products/management/product-form.blade.php`
  - `app/Http/Livewire/Products/Management/ProductForm.php`

---

**END OF REPORT**
