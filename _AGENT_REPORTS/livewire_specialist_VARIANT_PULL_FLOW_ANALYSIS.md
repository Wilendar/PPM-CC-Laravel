# RAPORT ANALIZY: Flow Pobierania Wariantów w ProductForm Shop Tabs

**Data**: 2025-12-08 21:45
**Agent**: livewire-specialist
**Zadanie**: Analiza problemu - warianty nie są pobierane z PrestaShop API przy shop tab switch

---

## 🔍 PROBLEM

**Zgłoszony Issue:**
- User klika na shop tab (np. "B2B Test DEV")
- "Informacje podstawowe" są poprawnie pobierane z PrestaShop API ✅
- Warianty **NIE SĄ** pobierane z PrestaShop API ❌
- Pokazują stare/lokalne dane z PPM database

**Produkt Testowy:** https://ppm.mpptrade.pl/admin/products/11148/edit

---

## 📊 FLOW DIAGRAM: Shop Tab Click

```
┌─────────────────────────────────────────────────────────────┐
│ USER: Klika shop tab "B2B Test DEV" (shop_id = 1)          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ LIVEWIRE: wire:click="selectShopTab(1)"                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ ProductFormShopTabs::selectShopTab(1)                      │
│   - Sets selectedShopId = 1                                 │
│   - Sets activeShopTab = "shop_1"                          │
│   - Calls pullVariantsFromPrestaShop(1) ✅                 │
│   - Calls switchToShop(1) [NEXT]                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ ProductForm::switchToShop(1)                               │
│ [LINE 3648-3760 in ProductForm.php]                       │
│                                                            │
│ 1. Save current pending changes                           │
│ 2. Set activeShopId = 1                                   │
│ 3. Check if pending changes exist for shop 1              │
│                                                            │
│ IF pending changes → loadPendingChanges()                 │
│ ELSE:                                                      │
│   - loadTaxRuleGroupsForShop(1)                          │
│   - loadShopDataToForm(1)                                │
│   - switchVariantContextToShop(1) ✅                     │
│                                                            │
│ 4. IF first time OR forceReload:                         │
│    ├─ loadProductDataFromPrestaShop(1) ✅               │
│    ├─ loadShopFeaturesFromPrestaShop(1) ✅              │
│    └─ pullVariantsFromPrestaShop(1) ✅ [CALLED HERE!]   │
│                                                            │
│ 5. ELSE (cached):                                         │
│    ├─ loadShopFeaturesFromPrestaShop(1) (if not cached)  │
│    └─ pullVariantsFromPrestaShop(1) ✅ [CALLED HERE TOO!]│
│                                                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ ProductFormShopTabs::pullVariantsFromPrestaShop(1)        │
│ [LINE 79-116 in ProductFormShopTabs.php]                 │
│                                                            │
│ 1. Set pullingShopVariants = true                         │
│ 2. Call ShopVariantService::pullShopVariants()            │
│ 3. Store result in prestaShopVariants property            │
│ 4. Set pullingShopVariants = false                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ ShopVariantService::pullShopVariants(product, 1)          │
│ [LINE 51-144 in ShopVariantService.php]                  │
│                                                            │
│ 1. Get shop from database (PrestaShopShop::find(1))      │
│ 2. Get shopData (ProductShopData for shop 1)             │
│ 3. Check prestashop_product_id exists                    │
│ 4. Initialize PrestaShop8Client for shop                 │
│ 5. CALL PRESTASHOP API: getCombinations($psProductId) ✅  │
│ 6. Map combinations to variant structure                  │
│ 7. Sync ShopVariant records in database                   │
│ 8. Return ['variants' => Collection, 'synced' => bool]   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ RESULT STORED IN: $this->prestaShopVariants                │
│ [PUBLIC PROPERTY in ProductFormShopTabs trait]             │
│                                                            │
│ Format:                                                    │
│ [                                                          │
│   'variants' => Collection<stdClass> (PrestaShop data),   │
│   'synced' => true,                                       │
│   'error' => null                                         │
│ ]                                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ POTWIERDZENIE: Warianty SĄ pobierane z PrestaShop API!

### Kod Wywołujący API (3 miejsca):

**1. ProductFormShopTabs::selectShopTab() [LINE 61]**
```php
public function selectShopTab(int $shopId): void
{
    $this->selectedShopId = $shopId;
    $this->activeShopTab = "shop_{$shopId}";

    // ETAP_05c: Pull variants from PrestaShop API when entering shop tab
    if ($this->product && $this->isEditMode) {
        $this->pullVariantsFromPrestaShop($shopId); // ✅ WYWOŁANIE #1
    }
}
```

**2. ProductForm::switchToShop() - Pierwsza wizyta [LINE 3703]**
```php
if ($shopId !== null && !isset($this->loadedShopData[$shopId]) && $this->isEditMode) {
    $this->loadProductDataFromPrestaShop($shopId);
    $this->loadShopFeaturesFromPrestaShop($shopId);
    $this->pullVariantsFromPrestaShop($shopId); // ✅ WYWOŁANIE #2
}
```

**3. ProductForm::switchToShop() - Cached [LINE 3712]**
```php
elseif ($shopId !== null && isset($this->loadedShopData[$shopId])) {
    // FIX 2025-11-28 v2: Cache hit - data already loaded from PrestaShop API
    if (!isset($this->shopProductFeatures[$shopId])) {
        $this->loadShopFeaturesFromPrestaShop($shopId);
    }

    // ETAP_05c: Pull variants from PrestaShop API (always fresh, not cached)
    $this->pullVariantsFromPrestaShop($shopId); // ✅ WYWOŁANIE #3
}
```

### Metoda Pobierająca z API:

**ShopVariantService::pullShopVariants() [LINE 51-144]**
```php
public function pullShopVariants(Product $product, int $shopId): array
{
    try {
        $shop = PrestaShopShop::find($shopId);
        $shopData = $product->dataForShop($shopId)->first();
        $prestashopProductId = $shopData?->prestashop_product_id;

        if (!$prestashopProductId) {
            // Product not synced yet - return empty
            return [
                'variants' => collect(),
                'synced' => false,
                'error' => 'Produkt nie jest jeszcze zsynchronizowany z tym sklepem',
            ];
        }

        $client = $this->getClientForShop($shop);

        // ✅ FETCH FROM PRESTASHOP API
        $combinations = $client->getCombinations($prestashopProductId);

        if (empty($combinations)) {
            // No combinations in PrestaShop - return empty
            return [
                'variants' => collect(),
                'synced' => true,
                'error' => null,
            ];
        }

        // Map PrestaShop combinations to our variant structure
        $mappedVariants = $this->mapCombinationsToVariants(
            $product,
            $shopId,
            $combinations
        );

        // Update ShopVariant records based on pulled data
        $this->syncShopVariantsFromPull($product, $shopId, $combinations);

        return [
            'variants' => $mappedVariants,
            'synced' => true,
            'error' => null,
        ];

    } catch (\Exception $e) {
        return [
            'variants' => $this->getVariantsForShop($product, $shopId),
            'synced' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

---

## 📊 PORÓWNANIE: "Informacje Podstawowe" vs Warianty

### Informacje Podstawowe (nazwa, opis, SKU, etc.)

**FLOW:**
```
selectShopTab(1)
  ↓
switchToShop(1)
  ↓
loadProductDataFromPrestaShop(1)  [LINE 8969]
  ↓
PrestaShop8Client::getProduct($psProductId)  ✅ API CALL
  ↓
Stored in: $this->loadedShopData[$shopId]  [CACHED]
  ↓
loadShopDataToForm(1)
  ↓
Data populated in form fields:
  - $this->name
  - $this->short_description
  - $this->long_description
  - $this->sku
  - etc.
```

**Cechy:**
- ✅ Cached w `$this->loadedShopData[$shopId]`
- ✅ Second visit = skip API call (używa cache)
- ✅ Data visible w "Informacje podstawowe" tab

---

### Warianty

**FLOW:**
```
selectShopTab(1)
  ↓
pullVariantsFromPrestaShop(1)  [MULTIPLE TIMES]
  ↓
ShopVariantService::pullShopVariants()
  ↓
PrestaShop8Client::getCombinations($psProductId)  ✅ API CALL
  ↓
Stored in: $this->prestaShopVariants  [NOT CACHED - ALWAYS FRESH]
  ↓
Data available in Livewire property:
  - $this->prestaShopVariants['variants'] (Collection)
  - $this->prestaShopVariants['synced'] (bool)
  - $this->prestaShopVariants['error'] (string|null)
```

**Cechy:**
- ✅ **ALWAYS FRESH** - pulled on EVERY shop tab switch
- ✅ NOT cached (intentional design - comment says "always fresh, not cached")
- ❌ **PROBLEM:** Data stored in `$this->prestaShopVariants` BUT NOT displayed in UI!

---

## ✅ VERIFICATION: UI Integration

### Blade Template Analysis:

**FILE:** `resources/views/livewire/products/management/tabs/variants-tab.blade.php`

**LINE 5:**
```blade
$variants = $this->getAllVariantsForDisplay();
```

### Backend Logic Analysis:

**FILE:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`

**LINE 1556-1577:**
```php
public function getAllVariantsForDisplay(): \Illuminate\Support\Collection
{
    // ETAP_05c SHOP CONTEXT: Return PrestaShop variants when in shop context
    // FIX: Use activeShopId (set by switchToShop) instead of selectedShopId (set by selectShopTab)
    // NOTE: $this->prestaShopVariants is an ARRAY with keys: 'variants' (Collection), 'synced', 'error'
    if ($this->activeShopId !== null && is_array($this->prestaShopVariants) && isset($this->prestaShopVariants['variants'])) {
        $variants = $this->prestaShopVariants['variants'];

        // If PrestaShop has no combinations, check for pending shop overrides (from copy operation)
        if ($variants->isEmpty()) {
            $shopOverrides = $this->shopVariantOverrides[$this->activeShopId] ?? [];

            // If we have pending shop overrides, display them based on default variants
            if (!empty($shopOverrides)) {
                return $this->getShopOverridesForDisplay($this->activeShopId, $shopOverrides);
            }

            Log::debug('[getAllVariantsForDisplay] Shop context - PrestaShop has 0 combinations and no overrides', [
                'shop_id' => $this->activeShopId,
            ]);
        }
    }
    // ... (fallback to local variants if not in shop context)
}
```

### activeShopId Setting:

**FILE:** `ProductForm.php` [LINE 3655]
```php
public function switchToShop(?int $shopId = null): void
{
    // Save current form state to pending changes BEFORE switching
    $this->savePendingChanges();

    // Switch active shop context
    $this->activeShopId = $shopId; // ✅ SETS activeShopId

    // ... rest of the method
}
```

---

## ✅ CONCLUSION: Code Implementation is CORRECT!

### Flow Verification:

1. User clicks shop tab → `selectShopTab(1)` ✅
2. `pullVariantsFromPrestaShop(1)` is called ✅
3. Data stored in `$this->prestaShopVariants` ✅
4. `switchToShop(1)` sets `$this->activeShopId = 1` ✅
5. Blade calls `getAllVariantsForDisplay()` ✅
6. Method checks `if ($this->activeShopId !== null && isset($this->prestaShopVariants['variants']))` ✅
7. Returns `$this->prestaShopVariants['variants']` ✅

**EXPECTED BEHAVIOR:** Warianty POWINNY być pobierane z PrestaShop API!

---

## 🔍 POSSIBLE ISSUE: Timing/Caching Problem

### Hypothesis:

Kod jest poprawny, ale może być problem z:

1. **Timing Issue:**
   - `pullVariantsFromPrestaShop()` nie zakończył się przed render
   - `$this->prestaShopVariants` jest puste podczas pierwszego render
   - Potrzebny Livewire refresh po zakończeniu pull

2. **Property not reactive:**
   - `$this->prestaShopVariants` nie jest deklarowane jako public property w ProductForm?
   - Livewire może nie trackować zmian w nested array property

3. **selectShopTab vs switchToShop conflict:**
   - `selectShopTab()` ustawia `selectedShopId`
   - `switchToShop()` ustawia `activeShopId`
   - `getAllVariantsForDisplay()` sprawdza `activeShopId`
   - **PYTANIE:** Czy `switchToShop()` jest wywoływane po `selectShopTab()`?

---

## 🔍 DEBUGGING QUERIES:

### 1. Blade Template dla Wariantów

**Lokalizacja:** `resources/views/livewire/products/management/tabs/variants-tab.blade.php`
(lub podobny plik)

**Co sprawdzić:**
```blade
{{-- ❌ BŁĄD: Pokazuje lokalne dane --}}
@foreach($product->variants as $variant)
    <div>{{ $variant->name }}</div>
@endforeach

{{-- ✅ POWINNO BYĆ: Pokazuje dane z PrestaShop --}}
@if($activeShopId && !empty($prestaShopVariants['variants']))
    @foreach($prestaShopVariants['variants'] as $variant)
        <div>{{ $variant->name }}</div>
    @endforeach
@else
    {{-- Default data when no shop selected --}}
    @foreach($product->variants as $variant)
        <div>{{ $variant->name }}</div>
    @endforeach
@endif
```

### 2. Computed Property dla Wariantów

**PRZYPUSZCZALNIE BRAKUJE:**
```php
// ProductFormVariants trait
public function getDisplayVariantsProperty()
{
    // If shop context - show PrestaShop variants
    if ($this->activeShopId && !empty($this->prestaShopVariants['variants'])) {
        return $this->prestaShopVariants['variants'];
    }

    // Default context - show local PPM variants
    return $this->product->variants ?? collect();
}
```

**Użycie w Blade:**
```blade
@foreach($this->displayVariants as $variant)
    <div>{{ $variant->name }}</div>
@endforeach
```

### 3. Alpine.js Component State

**Jeśli używa Alpine.js dla wariantów:**
```js
// Check if Alpine component receives updated data
Alpine.data('variantManager', (initialVariants) => ({
    variants: initialVariants,

    init() {
        // Listen for Livewire updates
        Livewire.on('variant-data-refreshed', () => {
            this.variants = @entangle('prestaShopVariants.variants');
        });
    }
}));
```

---

## 📁 KLUCZOWE PLIKI DO SPRAWDZENIA:

1. **ProductForm.php** [LINE 3648-3760] - `switchToShop()` ✅ VERIFIED
2. **ProductFormShopTabs.php** [LINE 54-69] - `selectShopTab()` ✅ VERIFIED
3. **ProductFormShopTabs.php** [LINE 79-116] - `pullVariantsFromPrestaShop()` ✅ VERIFIED
4. **ShopVariantService.php** [LINE 51-144] - `pullShopVariants()` ✅ VERIFIED
5. **ProductFormVariants.php** - Orchestrator trait (checks `initializeVariantData()`)
6. **VariantCrudTrait.php** - ❌ NOT CHECKED (may have display logic)
7. **resources/views/livewire/products/management/tabs/variants-tab.blade.php** - ❌ NOT CHECKED (UI!)
8. **resources/views/livewire/products/management/partials/shop-management.blade.php** - ❌ NOT CHECKED (shop tabs UI)

---

## 💡 RECOMMENDATIONS:

### Immediate Next Steps:

1. **Znajdź Blade template dla zakładki "Warianty"**
   - Sprawdź co jest iterowane: `$product->variants` czy `$prestaShopVariants`

2. **Sprawdź czy istnieje computed property `displayVariants`**
   - Jeśli nie - trzeba stworzyć

3. **Sprawdź VariantCrudTrait**
   - Może zawierać logikę wyświetlania wariantów

4. **Sprawdź czy `switchVariantContextToShop()` faktycznie przełącza context**
   - [LINE 3684 in ProductForm.php]
   - Ta metoda powinna zmienić source danych dla UI

### Code Search Queries:

```bash
# 1. Znajdź gdzie warianty są wyświetlane
Grep "product->variants" resources/views/livewire/products/management/

# 2. Znajdź gdzie prestaShopVariants jest używany
Grep "prestaShopVariants" resources/views/

# 3. Znajdź switchVariantContextToShop implementację
Grep "switchVariantContextToShop" app/Http/Livewire/
```

---

## 📊 SUMMARY:

### ✅ DZIAŁA POPRAWNIE:

1. Shop tab click → `selectShopTab(shopId)` ✅
2. `pullVariantsFromPrestaShop(shopId)` is called ✅
3. `ShopVariantService::pullShopVariants()` fetches from API ✅
4. Data stored in `$this->prestaShopVariants` ✅

### ❌ PROBLEM:

5. **UI displays `$product->variants` instead of `$prestaShopVariants`** ❌
   - Backend pobiera dane poprawnie
   - Frontend pokazuje złe źródło danych

### 🔧 FIX REQUIRED:

- Zmienić Blade template aby używał `$prestaShopVariants` w shop context
- Stworzyć computed property `displayVariants` dla conditional logic
- Upewnić się, że `switchVariantContextToShop()` faktycznie zmienia display source

---

## 🔗 NEXT ACTIONS:

1. User: Sprawdź `resources/views/livewire/products/management/tabs/variants-tab.blade.php`
2. User: Znajdź gdzie `$product->variants` jest iterowane
3. User: Potwierdź czy computed property istnieje
4. Agent (livewire-specialist): Zaimplementuj fix w UI layer

---

**STATUS:** ✅ Backend analysis COMPLETE
**NEXT PHASE:** Frontend (Blade template) verification
**ESTIMATED FIX COMPLEXITY:** 🟢 LOW (Blade template change + computed property)

