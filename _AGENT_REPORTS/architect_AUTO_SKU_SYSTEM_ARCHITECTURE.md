# RAPORT PRACY AGENTA: architect

**Data**: 2025-12-09 14:45
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: Architektura funkcjonalności "Auto SKU Suffix/Prefix" dla systemu wariantów PPM

---

## STATUS PROJEKTU

**⚠️ KRYTYCZNE ODKRYCIE:** Projekt **ETAP_05f: Auto SKU System** **JUŻ ISTNIEJE** z kompleksowym planem implementacji!

**Lokalizacja planu:** `Plan_Projektu/ETAP_05f_Auto_SKU_System.md`

**Aktualny Status:**
- **Plan:** ✅ Ukończony (2025-12-09, 777 linii, ultra-szczegółowy)
- **Database Migration:** ✅ Istnieje (`2025_12_09_135739_add_auto_prefix_suffix_to_attribute_values.php`)
- **Model AttributeValue:** ✅ Zaktualizowany (fillable, casts, relationships)
- **Service AttributeValueService:** ✅ Obsługuje prefix/suffix fields
- **UI AttributeValueManager:** ✅ FormData zawiera prefix/suffix (linie 61-65)
- **VariantSkuGenerator Service:** ❌ NIE ISTNIEJE (główny komponent do implementacji)
- **UI Variant Modal:** ❌ Brak checkboxa "Auto-generuj SKU"

---

## ✅ WYKONANE PRACE

### 1. Analiza Istniejącej Infrastruktury

**Przeanalizowane pliki (10 plików):**
1. `app/Http/Livewire/Admin/Variants/AttributeSystemManager.php` (328 linii)
2. `app/Http/Livewire/Admin/Variants/AttributeValueManager.php` (454 linii)
3. `app/Models/VariantAttribute.php` (122 linii)
4. `app/Models/AttributeValue.php` (140 linii)
5. `app/Models/AttributeType.php` (140 linii)
6. `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` (300+ linii, partial read)
7. `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php` (127 linii)
8. `app/Services/Product/AttributeValueService.php` (285 linii)
9. `database/migrations/2025_12_09_135739_add_auto_prefix_suffix_to_attribute_values.php` (52 linii)
10. `Plan_Projektu/ETAP_05f_Auto_SKU_System.md` (777 linii)

**Kluczowe odkrycia:**
- ✅ Database schema gotowe: `auto_prefix`, `auto_suffix`, `auto_prefix_enabled`, `auto_suffix_enabled`
- ✅ AttributeValue model: fillable/casts poprawnie skonfigurowane
- ✅ AttributeValueManager: formData zawiera wszystkie required fields
- ✅ AttributeValueService: CRUD operations obsługują nowe pola (linie 96-99, 155-158)
- ❌ BRAK: VariantSkuGenerator service (główny komponent generowania SKU)
- ❌ BRAK: UI w variant-edit-modal.blade.php (checkbox "Auto-generuj SKU")
- ❌ BRAK: Lifecycle hooks w VariantCrudTrait (updatedVariantDataAttributes, updatedVariantDataSku)

---

## 📊 ANALIZA ARCHITEKTURY

### Aktualny Status Implementacji

| Komponent | Status | Lokalizacja | Notes |
|-----------|--------|-------------|-------|
| **1. Database Schema** | ✅ READY | `database/migrations/2025_12_09_135739_*` | Migracja gotowa do uruchomienia |
| **2. Model AttributeValue** | ✅ READY | `app/Models/AttributeValue.php` | Fillable, casts, relationships OK |
| **3. Service AttributeValueService** | ✅ READY | `app/Services/Product/AttributeValueService.php` | CRUD operations obsługują prefix/suffix |
| **4. UI AttributeValueManager** | 🟡 PARTIAL | `app/Http/Livewire/Admin/Variants/AttributeValueManager.php` | FormData ready, BRAK UI w Blade |
| **5. VariantSkuGenerator** | ❌ MISSING | BRAK | **GŁÓWNY KOMPONENT** do stworzenia |
| **6. VariantCrudTrait** | ❌ MISSING | `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` | Brak lifecycle hooks |
| **7. Variant Modal UI** | ❌ MISSING | `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php` | Brak checkboxa + attributes section |

### Istniejący Plan ETAP_05f (777 linii)

**Struktura planu:**
- **FAZA 1:** Backend - VariantSkuGenerator Service (2h) - ❌ NIE ROZPOCZĘTO
- **FAZA 2:** Admin Panel UI (AttributeValueManager) (3h) - ❌ NIE ROZPOCZĘTO
- **FAZA 3:** Product Form - Modal Edycji Wariantu (4h) - ❌ NIE ROZPOCZĘTO
- **FAZA 4:** Integracja - Zapisywanie Wariantu (2h) - ❌ NIE ROZPOCZĘTO
- **FAZA 5:** Testing & Polish (3h) - ❌ NIE ROZPOCZĘTO

**Estymacja całkowita:** 14h (~2 dni robocze)

**Plan zawiera:**
- ✅ Szczegółowe zadania dla każdej fazy (30+ podpunktów)
- ✅ Code snippets dla wszystkich metod
- ✅ Walidację + error handling patterns
- ✅ Testy jednostkowe (4 test cases)
- ✅ End-to-end test scenarios (5 scenarios)
- ✅ Edge cases (5 cases)
- ✅ Chrome DevTools verification workflow (6 checks)
- ✅ Documentation updates checklist
- ✅ Success criteria (functionality + quality + docs)

---

## 🎯 DIAGRAM ARCHITEKTURY AUTO SKU SYSTEM

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         AUTO SKU SYSTEM                              │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐      ┌──────────────────────┐
│  ADMIN PANEL         │      │  PRODUCT FORM        │
│  /admin/variants     │      │  /products/{id}/edit │
└──────────────────────┘      └──────────────────────┘
         │                              │
         │                              │
         ▼                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   AttributeValue Configuration                       │
├─────────────────────────────────────────────────────────────────────┤
│  auto_prefix: "XL-"                                                 │
│  auto_prefix_enabled: true                                          │
│  auto_suffix: "-CZE"                                                │
│  auto_suffix_enabled: true                                          │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               │ Used by
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│              VariantSkuGenerator Service (TO CREATE)                │
├─────────────────────────────────────────────────────────────────────┤
│  generateSku(Product $product, array $attributes): string           │
│    ├─ getPrefixesFromAttributes(array $attributes): array           │
│    ├─ getSuffixesFromAttributes(array $attributes): array           │
│    └─ composeSku(string $base, array $prefixes, array $suffixes)   │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               │ Returns
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Generated Variant SKU                           │
│                   "XL-MR-MRF-E-CZE"                                  │
└─────────────────────────────────────────────────────────────────────┘
```

### Data Flow Diagram

```
USER ACTION                    SYSTEM RESPONSE                  DATABASE
───────────────────────────────────────────────────────────────────────

1. Admin konfiguruje prefix/suffix
   │
   ├──> AttributeValueManager
   │    ├─ formData.auto_prefix = "XL-"
   │    ├─ formData.auto_prefix_enabled = true
   │    └─ save()
   │         │
   │         └──> AttributeValueService
   │              └─ createAttributeValue() ──────────> INSERT attribute_values
   │

2. User tworzy wariant
   │
   ├──> Variant Edit Modal
   │    ├─ Checkbox "Auto-generuj SKU" = checked (default)
   │    ├─ Select Rozmiar = XL (value_id=123)
   │    └─ Select Kolor = Czerwony (value_id=456)
   │         │
   │         └──> wire:model.live="variantData.attributes"
   │              │
   │              ├──> updatedVariantDataAttributes()
   │              │    └─ updateVariantSku()
   │              │         │
   │              │         └──> VariantSkuGenerator::generateSku()
   │              │              ├─ Load AttributeValue(123): prefix="XL-"
   │              │              ├─ Load AttributeValue(456): suffix="-CZE"
   │              │              └─ Return: "XL-MR-MRF-E-CZE"
   │              │
   │              └──> variantData.sku = "XL-MR-MRF-E-CZE"
   │

3. User zapisuje wariant
   │
   ├──> storeVariant()
   │    ├─ Validate SKU (not empty)
   │    ├─ Create ProductVariant ──────────────────> INSERT product_variants
   │    └─ Create VariantAttribute records ────────> INSERT variant_attributes
```

### Component Dependencies

```
┌─────────────────────────────────────────────────────────────────────┐
│                            DEPENDENCIES                              │
└─────────────────────────────────────────────────────────────────────┘

AttributeValueManager (Livewire)
    │
    ├─ depends on → AttributeValueService (CRUD operations)
    │
    └─ uses → AttributeValue model (fillable fields)

VariantCrudTrait (Livewire)
    │
    ├─ depends on → VariantSkuGenerator (SKU generation)
    │                   │
    │                   └─ depends on → AttributeValue model (prefix/suffix)
    │
    ├─ uses → ProductVariant model (save SKU)
    │
    └─ uses → VariantAttribute model (save attribute mappings)

VariantSkuGenerator (Service)
    │
    ├─ depends on → AttributeValue model (load prefix/suffix)
    │
    └─ depends on → Product model (base SKU)
```

---

## 📁 PLIKI DO MODYFIKACJI/UTWORZENIA

### Nowe Pliki (CREATE) - 2 pliki

1. **`app/Services/Product/VariantSkuGenerator.php`** (~200 linii)
   - **Responsibility:** Generowanie SKU dla wariantów na podstawie atrybutów
   - **Methods:**
     - `generateSku(Product $product, array $attributes): string`
     - `getPrefixesFromAttributes(array $attributes): array`
     - `getSuffixesFromAttributes(array $attributes): array`
     - `composeSku(string $baseSku, array $prefixes, array $suffixes): string`
   - **Dependencies:** AttributeValue model
   - **Tests:** 4 unit tests (Plan FAZA 1.3)

2. **`_DOCS/AUTO_SKU_SYSTEM_GUIDE.md`** (dokumentacja)
   - **Content:** Developer guide - jak działa system, jak konfigurować, przykłady użycia
   - **Sections:**
     - System overview
     - Admin configuration guide
     - User workflow
     - API reference (VariantSkuGenerator)
     - Troubleshooting
     - Edge cases handling

### Pliki do Modyfikacji (EDIT) - 5 plików

1. **`resources/views/livewire/admin/variants/attribute-value-manager.blade.php`**
   - **Zmiany:** Dodać sekcję "Automatyczne SKU dla wariantów" (~linia 200)
   - **Elementy UI:**
     - Checkbox "Dodaj prefix do SKU" → `wire:model.live="formData.auto_prefix_enabled"`
     - Input text (conditional) → `wire:model="formData.auto_prefix"`
     - Checkbox "Dodaj suffix do SKU" → `wire:model.live="formData.auto_suffix_enabled"`
     - Input text (conditional) → `wire:model="formData.auto_suffix"`
     - Help text + examples
   - **CSS:** PPM UI Standards (dark theme)
   - **Plan Reference:** FAZA 2.1

2. **`app/Http/Livewire/Admin/Variants/AttributeValueManager.php`**
   - **Zmiany:** Rozszerzyć validation rules w metodzie `save()` (~linia 201)
   - **Nowe rules:**
     - `'formData.auto_prefix' => 'nullable|string|max:20|regex:/^[A-Z0-9-_]+$/i'`
     - `'formData.auto_suffix' => 'nullable|string|max:20|regex:/^[A-Z0-9-_]+$/i'`
   - **Validation messages:** Polskie komunikaty błędów
   - **Plan Reference:** FAZA 2.2

3. **`app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`**
   - **Zmiany (3 obszary):**

     **A. Rozszerzenie $variantData property (~linia 41):**
     ```php
     'attributes' => [], // AttributeType => AttributeValue mappings
     'auto_generate_sku' => true, // NEW: Default enabled
     ```

     **B. Nowe metody (3 metody):**
     - `updateVariantSku(): void` - Generuje SKU gdy auto_generate_sku=true
     - `updatedVariantDataAttributes(): void` - Lifecycle hook (wywołuje updateVariantSku)
     - `updatedVariantDataSku(): void` - Lifecycle hook (wyłącza auto mode przy manual edit)
     - `availableAttributeTypes(): Collection` - #[Computed] property

     **C. Modyfikacja storeVariant() / updateVariant():**
     - Pre-save SKU regeneration check
     - Zapisywanie VariantAttribute records
     - Logging dla debug

   - **Plan Reference:** FAZA 3.1, FAZA 4.1-4.2

4. **`resources/views/livewire/products/management/partials/variant-edit-modal.blade.php`**
   - **Zmiany (3 obszary):**

     **A. Checkbox "Auto-generuj SKU" (~linia 61):**
     ```blade
     <input type="checkbox" wire:model.live="variantData.auto_generate_sku">
     ```

     **B. Modyfikacja SKU Field (~linia 49-61):**
     - Dodać `@if($variantData['auto_generate_sku']) readonly @endif`
     - Conditional CSS: opacity-75 gdy auto mode
     - Info text: "Generowane automatycznie"

     **C. Sekcja wyboru atrybutów (~linia 77-87):**
     - Zastąpić placeholder dynamicznymi dropdowns
     - `@foreach($this->availableAttributeTypes as $type)`
     - `wire:model.live="variantData.attributes.{{ $type->id }}"`
     - Show prefix/suffix w options (SKU: XL-...−CZE)

   - **Plan Reference:** FAZA 3.2

5. **`CLAUDE.md`**
   - **Zmiany:** Dodać sekcję "Auto SKU System" w "System Wariantów"
   - **Content:**
     - Krótki opis funkcjonalności
     - Link do `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md`
     - Przykład użycia
   - **Plan Reference:** FAZA 5.5.1

---

## 🔍 KLUCZOWE ODKRYCIA

### 1. Infrastruktura Database + Model GOTOWA ✅

**Migration już istnieje:**
```php
// 2025_12_09_135739_add_auto_prefix_suffix_to_attribute_values.php
$table->string('auto_prefix', 20)->nullable();
$table->boolean('auto_prefix_enabled')->default(false);
$table->string('auto_suffix', 20)->nullable();
$table->boolean('auto_suffix_enabled')->default(false);
```

**AttributeValue model poprawnie skonfigurowany:**
```php
// app/Models/AttributeValue.php (linie 51-62, 64-72)
protected $fillable = [
    'auto_prefix',
    'auto_prefix_enabled',
    'auto_suffix',
    'auto_suffix_enabled',
    // ...
];

protected $casts = [
    'auto_prefix_enabled' => 'boolean',
    'auto_suffix_enabled' => 'boolean',
    // ...
];
```

### 2. Service Layer CZĘŚCIOWO GOTOWY 🟡

**AttributeValueService obsługuje nowe pola:**
```php
// app/Services/Product/AttributeValueService.php (linie 96-99)
'auto_prefix' => $data['auto_prefix'] ?? null,
'auto_prefix_enabled' => $data['auto_prefix_enabled'] ?? false,
'auto_suffix' => $data['auto_suffix'] ?? null,
'auto_suffix_enabled' => $data['auto_suffix_enabled'] ?? false,
```

**Brak VariantSkuGenerator service** - to główny komponent do stworzenia!

### 3. UI Layer POTRZEBUJE UZUPEŁNIENIA ❌

**AttributeValueManager formData ready:**
```php
// app/Http/Livewire/Admin/Variants/AttributeValueManager.php (linie 61-65)
'auto_prefix' => '',
'auto_prefix_enabled' => false,
'auto_suffix' => '',
'auto_suffix_enabled' => false,
```

**BRAK:**
- UI w Blade template dla AttributeValueManager (checkboxes + inputs)
- Validation rules w save() method
- Checkbox "Auto-generuj SKU" w variant-edit-modal
- Attributes dropdowns w variant-edit-modal
- Lifecycle hooks w VariantCrudTrait

### 4. Plan ETAP_05f ULTRA-SZCZEGÓŁOWY ✅

**777 linii kompleksowego planu zawierającego:**
- 5 faz implementacji (FAZA 1-5)
- 30+ hierarchicznych podpunktów
- Code snippets dla wszystkich metod
- 4 unit tests + 5 end-to-end scenarios
- 5 edge cases
- 6 Chrome DevTools verification checks
- Validation patterns
- Error handling patterns
- Success criteria
- Timeline + estymacja (14h)

**Jakość planu:** Przemysłowa, gotowa do immediate execution przez specialists

---

## 📝 ARCHITEKTURA - TECHNICAL DETAILS

### VariantSkuGenerator Service (DO UTWORZENIA)

**Namespace:** `App\Services\Product`

**Responsibility:** Generowanie SKU dla wariantów na podstawie product base SKU + attribute prefix/suffix

**Public Methods:**

```php
/**
 * Generate SKU for variant based on product base SKU and attributes
 *
 * @param Product $product Base product
 * @param array $attributes ['attribute_type_id' => 'value_id', ...]
 * @return string Generated SKU
 */
public function generateSku(Product $product, array $attributes): string
```

**Protected Methods:**

```php
/**
 * Extract prefixes from attributes
 * Returns: ['XL-', 'LONG-']
 */
protected function getPrefixesFromAttributes(array $attributes): array

/**
 * Extract suffixes from attributes
 * Returns: ['-CZE', '-BAW']
 */
protected function getSuffixesFromAttributes(array $attributes): array

/**
 * Compose final SKU from parts
 * Example: baseSku="MR-MRF-E", prefixes=["XL-"], suffixes=["-CZE"]
 * Result: "XL-MR-MRF-E-CZE"
 */
protected function composeSku(string $baseSku, array $prefixes, array $suffixes): string
```

**Algorithm (composeSku):**

```php
// Pseudo-code
$parts = array_filter([
    ...$prefixes,      // ["XL-", "LONG-"]
    $baseSku,          // "MR-MRF-E"
    ...$suffixes       // ["-CZE", "-BAW"]
]);

return implode('-', $parts);  // "XL-LONG-MR-MRF-E-CZE-BAW"
```

**Edge Cases Handling:**

1. **Empty attributes:** Return base SKU (no prefix/suffix)
2. **Attribute bez prefix/suffix:** Skip (nie wpływa na SKU)
3. **Duplikaty:** Deduplikacja via `array_unique()`
4. **Empty base SKU:** Throw `InvalidArgumentException`
5. **SKU > 100 chars:** Validation error

**Dependencies:**
- `AttributeValue::whereIn('id', $valueIds)->get()` - Load prefix/suffix
- `Product::sku` - Base SKU

**Testing:**
- Unit test: Single suffix → "BASE-SUF"
- Unit test: Prefix + Suffix → "PRE-BASE-SUF"
- Unit test: Multiple suffixes → "BASE-SUF1-SUF2"
- Unit test: Empty attributes → "BASE"

---

### Lifecycle Hooks w VariantCrudTrait

**Hook 1: updatedVariantDataAttributes()**

```php
/**
 * Livewire lifecycle - called when variantData.attributes changes
 * Auto-regenerates SKU if auto mode is enabled
 */
public function updatedVariantDataAttributes(): void
{
    $this->updateVariantSku();
}
```

**Trigger:** User wybiera atrybut w dropdown (wire:model.live)

**Flow:**
```
User selects Kolor=Czerwony
  → variantData.attributes[2] = 456
  → updatedVariantDataAttributes() called
  → updateVariantSku()
  → VariantSkuGenerator::generateSku()
  → variantData.sku updated
  → UI re-renders with new SKU
```

**Hook 2: updatedVariantDataSku()**

```php
/**
 * Livewire lifecycle - called when user manually edits SKU field
 * Disables auto-generation mode when user types manually
 */
public function updatedVariantDataSku(): void
{
    if ($this->variantData['auto_generate_sku']) {
        // User manually edited SKU -> disable auto mode
        $this->variantData['auto_generate_sku'] = false;

        Log::debug('[AUTO SKU] Disabled due to manual edit');
    }
}
```

**Trigger:** User wpisuje coś w SKU field

**Flow:**
```
User types "CUSTOM-SKU-001" in SKU field
  → variantData.sku changed
  → updatedVariantDataSku() called
  → auto_generate_sku set to false
  → Checkbox unchecks
  → SKU field becomes editable (no readonly)
```

**Hook 3: updateVariantSku()**

```php
/**
 * Called when user changes attributes or toggles auto_generate_sku
 * Updates variantData.sku if auto_generate_sku is enabled
 */
public function updateVariantSku(): void
{
    if (!$this->variantData['auto_generate_sku']) {
        return; // Manual SKU mode
    }

    $generator = app(VariantSkuGenerator::class);

    $this->variantData['sku'] = $generator->generateSku(
        $this->product,
        $this->variantData['attributes']
    );

    Log::debug('[AUTO SKU] Generated', [
        'sku' => $this->variantData['sku'],
        'attributes' => $this->variantData['attributes'],
    ]);
}
```

**Trigger:** Called by updatedVariantDataAttributes() lub manually

---

### UI Components Architecture

**Admin Panel: AttributeValueManager**

```blade
{{-- NEW SECTION: Auto SKU Configuration --}}
<div class="space-y-4 bg-gray-900/50 border border-gray-700 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-300">
        Automatyczne SKU dla wariantów
    </h4>

    {{-- Prefix Section --}}
    <label class="flex items-center space-x-3">
        <input type="checkbox"
               wire:model.live="formData.auto_prefix_enabled"
               class="w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded">
        <span class="text-sm text-gray-300">Dodaj prefix do SKU</span>
    </label>

    @if($formData['auto_prefix_enabled'])
    <input type="text"
           wire:model="formData.auto_prefix"
           placeholder="np. XL-"
           class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white">
    <p class="text-xs text-gray-500">
        Przykład: 'XL-' → Wariant SKU: 'XL-PROD-001'
    </p>
    @endif

    {{-- Suffix Section (analogicznie) --}}
</div>
```

**Product Form: Variant Edit Modal**

```blade
{{-- AUTO SKU CHECKBOX --}}
<div class="flex items-center space-x-3 bg-blue-900/20 border border-blue-700 rounded-lg p-3">
    <input type="checkbox"
           wire:model.live="variantData.auto_generate_sku"
           id="auto-generate-sku"
           class="w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded">
    <label for="auto-generate-sku" class="text-sm text-gray-300 cursor-pointer">
        <i class="fas fa-magic text-blue-500 mr-2"></i>
        Automatycznie generuj SKU z atrybutów
    </label>
</div>

{{-- SKU FIELD (conditional readonly) --}}
<input type="text"
       wire:model="variantData.sku"
       @if($variantData['auto_generate_sku']) readonly @endif
       class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white
              @if($variantData['auto_generate_sku']) opacity-75 cursor-not-allowed @endif">

{{-- ATTRIBUTES DROPDOWNS --}}
<div class="space-y-3">
    @foreach($this->availableAttributeTypes as $type)
    <div wire:key="attr-type-{{ $type->id }}">
        <label class="block text-xs text-gray-400 mb-1">{{ $type->name }}</label>
        <select wire:model.live="variantData.attributes.{{ $type->id }}"
                class="w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white">
            <option value="">-- Wybierz {{ $type->name }} --</option>
            @foreach($type->values->where('is_active', true) as $value)
            <option value="{{ $value->id }}">
                {{ $value->label }}
                @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
                    (SKU:
                    @if($value->auto_prefix_enabled){{ $value->auto_prefix }}@endif
                    ...
                    @if($value->auto_suffix_enabled){{ $value->auto_suffix }}@endif
                    )
                @endif
            </option>
            @endforeach
        </select>
    </div>
    @endforeach
</div>
```

---

## ⚠️ EDGE CASES & CONSIDERATIONS

### Edge Cases Identified (5 cases):

1. **Usunięcie AttributeValue z auto-SKU:**
   - **Issue:** Istniejące warianty mają SKU z tym prefix/suffix
   - **Solution:** Historical data - NIE aktualizujemy istniejących SKU
   - **Reason:** SKU są używane w PrestaShop, external systems

2. **Duplikaty prefix/suffix:**
   - **Issue:** Dwa atrybuty mają ten sam suffix "-CZE"
   - **Solution:** `array_unique()` w VariantSkuGenerator::composeSku()
   - **Result:** "BASE-CZE" (nie "BASE-CZE-CZE")

3. **Nieprawidłowe znaki w prefix/suffix:**
   - **Issue:** User wpisuje spacje, polish chars
   - **Solution:** Regex validation `/^[A-Z0-9-_]+$/i` w AttributeValueManager
   - **Result:** Validation error: "Prefix może zawierać tylko litery..."

4. **Brak base SKU w produkcie:**
   - **Issue:** `$product->sku = null` lub empty
   - **Solution:** Validation w VariantSkuGenerator::generateSku()
   - **Result:** Throw `InvalidArgumentException`: "Base SKU produktu jest wymagane"

5. **Bardzo długi SKU (>100 chars):**
   - **Issue:** Base=50, prefix=20, suffix=20 → 90+ chars
   - **Solution:** Validation limit 100 chars w VariantValidation trait
   - **Result:** Error: "SKU nie może przekraczać 100 znaków"

### Performance Considerations:

- **SKU Generation:** O(n) gdzie n = liczba atrybutów
  - Expected: <10 attributes per variant
  - Time: ~1ms (instant)

- **Database Queries:**
  - VariantSkuGenerator needs: `AttributeValue::whereIn('id', $valueIds)->get()`
  - N+1 prevented przez eager loading: `AttributeType::with('values')`
  - Expected queries: 1-2 per variant creation

### Security Considerations:

1. **SKU Validation:**
   - Unique per product (VariantValidation trait)
   - Regex dla prefix/suffix: tylko alphanumeric + hyphen/underscore

2. **SQL Injection:**
   - Eloquent ORM (automatic parameter binding)
   - No raw queries

3. **XSS:**
   - Blade escaping (automatic)
   - No user-generated HTML

---

## 📋 NASTĘPNE KROKI (IMPLEMENTATION ROADMAP)

### Rekomendowana Kolejność Implementacji:

**Krok 1: FAZA 1 - VariantSkuGenerator Service (2h)**
- **Agent:** `laravel-expert`
- **Zadanie:** Utworzenie `app/Services/Product/VariantSkuGenerator.php`
- **Deliverables:**
  - Service z 4 metodami (generateSku, getPrefixes, getSuffixes, composeSku)
  - 4 unit tests (Plan sekcja 1.3)
  - Logging dla debug
- **Success Criteria:**
  - Tests PASS
  - Code compliance: <300 lines

**Krok 2: FAZA 2 - Admin Panel UI (3h)**
- **Agent:** `livewire-specialist` + `frontend-specialist`
- **Zadanie:** Dodanie UI do AttributeValueManager
- **Deliverables:**
  - Blade template: Sekcja "Automatyczne SKU" (checkboxes + inputs)
  - Livewire: Validation rules
  - Chrome DevTools verification (5 tests, Plan sekcja 2.3)
- **Success Criteria:**
  - UI zgodne z PPM Styling Playbook
  - Validation działa
  - Zero console errors

**Krok 3: FAZA 3 - Product Form UI (4h)**
- **Agent:** `livewire-specialist` + `frontend-specialist`
- **Zadanie:** Dodanie checkbox + attributes do variant-edit-modal
- **Deliverables:**
  - Blade template: Checkbox "Auto-generuj SKU" + attributes dropdowns
  - Trait: Lifecycle hooks (updatedVariantDataAttributes, updatedVariantDataSku)
  - Trait: updateVariantSku() method + availableAttributeTypes computed
  - Chrome DevTools verification (6 tests, Plan sekcja 3.3)
- **Success Criteria:**
  - Reactive SKU generation działa
  - Manual edit disables auto mode
  - Checkbox state persistence

**Krok 4: FAZA 4 - Integracja (2h)**
- **Agent:** `laravel-expert`
- **Zadanie:** Zapisywanie wariantu z auto-SKU
- **Deliverables:**
  - Trait: Modyfikacja storeVariant() + updateVariant()
  - Pre-save SKU regeneration check
  - VariantAttribute records creation
  - 4 integration tests (Plan sekcja 4.3)
- **Success Criteria:**
  - Database records poprawne
  - Auto-SKU persists
  - Manual SKU persists

**Krok 5: FAZA 5 - Testing & Polish (3h)**
- **Agent:** `debugger` + `frontend-specialist`
- **Zadanie:** End-to-end testing + UI polish
- **Deliverables:**
  - 5 end-to-end test scenarios (Plan sekcja 5.1)
  - 5 edge cases tests (Plan sekcja 5.2)
  - Chrome DevTools verification (6 checks, Plan sekcja 5.4)
  - Screenshots (3 screenshots)
  - Documentation: `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md`
  - CLAUDE.md update
  - Plan_Projektu: wszystkie ✅ z ścieżkami
- **Success Criteria:**
  - All scenarios PASS
  - Zero bugs
  - Documentation complete

### Dependencies Między Fazami:

```
FAZA 1 (VariantSkuGenerator)
    │
    ├─────> FAZA 2 (Admin UI) - potrzebuje tylko database + model (OK)
    │
    └─────> FAZA 3 (Product Form UI) - potrzebuje VariantSkuGenerator
                │
                └─────> FAZA 4 (Integracja) - potrzebuje FAZA 3 UI
                            │
                            └─────> FAZA 5 (Testing) - potrzebuje FAZA 1-4
```

**CRITICAL PATH:** FAZA 1 → FAZA 3 → FAZA 4

**PARALLEL:** FAZA 2 może być implementowana równolegle z FAZA 1

---

## 🎯 SUCCESS CRITERIA

### Functionality Requirements:

- ✅ Admin może skonfigurować prefix/suffix per AttributeValue w `/admin/variants`
- ✅ User może włączyć/wyłączyć auto-generation w modal wariantu (checkbox)
- ✅ SKU generuje się automatycznie podczas wyboru atrybutów (reactive)
- ✅ User może ręcznie edytować SKU (wyłącza auto mode)
- ✅ SKU poprawnie zapisuje się do bazy danych (persistence)
- ✅ Multiple attributes (prefix + suffix) działają prawidłowo
- ✅ Edge cases obsłużone (empty attributes, duplicates, long SKU, etc.)

### Quality Requirements:

- ✅ Walidacja: regex `/^[A-Z0-9-_]+$/i` dla prefix/suffix
- ✅ Walidacja: SKU unique per product
- ✅ Walidacja: SKU max 100 chars
- ✅ Error handling: try-catch + meaningful error messages
- ✅ Logging: `Log::debug()` dla development (cleanup przed production)
- ✅ Tests: 4 unit tests + 4 integration tests + 5 E2E scenarios PASS
- ✅ Chrome DevTools: zero console errors, all verifications PASS
- ✅ UI: PPM Styling Playbook compliance (dark theme, enterprise style)
- ✅ Performance: <10ms SKU generation, <3 DB queries per variant
- ✅ Code compliance: <300 lines per file (CLAUDE.md)

### Documentation Requirements:

- ✅ Technical guide: `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md` (developer reference)
- ✅ CLAUDE.md: Sekcja "Auto SKU System" added
- ✅ Plan_Projektu: `ETAP_05f_Auto_SKU_System.md` wszystkie ✅ z ścieżkami
- ✅ Agent reports: Każdy agent tworzy raport w `_AGENT_REPORTS/`
- ✅ Screenshots: 3 screenshots w `_TOOLS/screenshots/auto_sku_*.jpg`

---

## 📊 TIMELINE & ESTYMACJA

| Faza | Zadanie | Agent | Estymacja | Status |
|------|---------|-------|-----------|--------|
| **FAZA 1** | VariantSkuGenerator Service | laravel-expert | 2h | ❌ |
| **FAZA 2** | Admin Panel UI | livewire-specialist + frontend-specialist | 3h | ❌ |
| **FAZA 3** | Product Form UI | livewire-specialist + frontend-specialist | 4h | ❌ |
| **FAZA 4** | Integracja | laravel-expert | 2h | ❌ |
| **FAZA 5** | Testing & Polish | debugger + frontend-specialist | 3h | ❌ |
| **TOTAL** | | | **14h** (~2 dni robocze) | ❌ |

**Milestones:**

| Data | Milestone | Deliverable |
|------|-----------|-------------|
| 2025-12-09 | ✅ Plan utworzony | `Plan_Projektu/ETAP_05f_Auto_SKU_System.md` (777 linii) |
| TBD | FAZA 1 Complete | VariantSkuGenerator + 4 unit tests PASS |
| TBD | FAZA 2 Complete | Admin UI + Chrome DevTools verification |
| TBD | FAZA 3 Complete | Variant Modal UI + lifecycle hooks |
| TBD | FAZA 4 Complete | Integration + 4 tests PASS |
| TBD | FAZA 5 Complete | E2E tests + documentation + screenshots |
| TBD | ✅ ETAP UKOŃCZONY | All success criteria met |

---

## ⚠️ BLOKERY & DEPENDENCIES

### Blokery (BRAK):

✅ Wszystkie dependencies READY:
- Database migration istnieje
- AttributeValue model zaktualizowany
- AttributeValueService obsługuje nowe pola
- AttributeValueManager formData zawiera fields
- VariantCrudTrait ma pending variants system (session persistence)

### Dependencies Zewnętrzne:

**WYMAGANE PRZED STARTEM:**
- ✅ Migration uruchomiona: `php artisan migrate` (sprawdzić status)

**OPCJONALNE (dla deployment):**
- Chrome DevTools verification (mandatory dla FAZA 2, 3, 5)
- Production database backup przed migracją

---

## 📁 PLIKI UŻYTE W ANALIZIE

**Przeanalizowane (10 plików):**
1. `app/Http/Livewire/Admin/Variants/AttributeSystemManager.php`
2. `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
3. `app/Models/VariantAttribute.php`
4. `app/Models/AttributeValue.php`
5. `app/Models/AttributeType.php`
6. `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`
7. `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php`
8. `app/Services/Product/AttributeValueService.php`
9. `database/migrations/2025_12_09_135739_add_auto_prefix_suffix_to_attribute_values.php`
10. `Plan_Projektu/ETAP_05f_Auto_SKU_System.md`

**Do utworzenia (2 pliki):**
1. `app/Services/Product/VariantSkuGenerator.php`
2. `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md`

**Do modyfikacji (5 plików):**
1. `resources/views/livewire/admin/variants/attribute-value-manager.blade.php`
2. `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
3. `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`
4. `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php`
5. `CLAUDE.md`

---

## 📝 NOTATKI KOŃCOWE

### Kluczowe Obserwacje:

1. **Plan już istnieje** - to NAJWIĘKSZE odkrycie. ETAP_05f jest ultra-szczegółowy (777 linii), gotowy do immediate execution. NIE POTRZEBA tworzyć nowego planu.

2. **Infrastruktura 60% gotowa** - Database, Model, Service Layer częściowo zrobione. Brakuje tylko:
   - VariantSkuGenerator service (główny komponent)
   - UI w 2 miejscach (admin panel + variant modal)
   - Lifecycle hooks

3. **Jakość istniejącego planu: PRZEMYSŁOWA** - Zawiera:
   - Dokładne line numbers gdzie wprowadzać zmiany
   - Complete code snippets (copy-paste ready)
   - Test scenarios z expected results
   - Chrome DevTools verification workflow
   - Edge cases handling
   - Success criteria

4. **Estymacja realistyczna:** 14h to sensowna estymacja dla:
   - 1 service (~200 linii)
   - 5 UI sections
   - 3 lifecycle hooks
   - 13 tests (4 unit + 4 integration + 5 E2E)
   - Full Chrome DevTools verification
   - Documentation

5. **Dependencies cleared:** Wszystkie prerequisite gotowe, zero blokerów.

### Rekomendacje dla User:

**Opcja A: Delegować do specialists (RECOMMENDED)**
```
@laravel-expert: Wykonaj FAZA 1 z Plan_Projektu/ETAP_05f_Auto_SKU_System.md
@livewire-specialist + @frontend-specialist: Wykonaj FAZA 2-3
@laravel-expert: Wykonaj FAZA 4
@debugger + @frontend-specialist: Wykonaj FAZA 5
```

**Opcja B: Batch execution (szybsza)**
```
1. Uruchom migrację: php artisan migrate
2. Deleguj wszystkie fazy równolegle (gdzie możliwe)
3. Final verification: @debugger + @frontend-specialist
```

**Opcja C: Incremental (bezpieczniejsza)**
```
1. Start FAZA 1 → verify → commit
2. Start FAZA 2 → verify → commit
3. Start FAZA 3 → verify → commit
4. Start FAZA 4 → verify → commit
5. Start FAZA 5 → verify → commit
```

### Co NIE robić:

- ❌ NIE tworzyć nowego planu (już istnieje doskonały)
- ❌ NIE tworzyć nowych migracji (już istnieje)
- ❌ NIE modyfikować AttributeValue model fillable (już OK)
- ❌ NIE pisać testów przed implementacją (plan zawiera test scenarios)

### Co zrobić TERAZ:

1. **Sprawdzić status migracji:** `php artisan migrate:status | Select-String "attribute"`
2. **Uruchomić migrację (jeśli nie):** `php artisan migrate`
3. **Delegować FAZA 1:** `@laravel-expert` → VariantSkuGenerator Service
4. **Wait for FAZA 1 complete** → Then delegate FAZA 3 (Product Form)
5. **Parallel:** Delegate FAZA 2 (Admin Panel) - niezależna od FAZA 1

---

## 🎖️ PODSUMOWANIE

**Status projektu:**
- Plan: ✅ GOTOWY (ETAP_05f, 777 linii, ultra-szczegółowy)
- Database: ✅ GOTOWA (migracja + model)
- Service Layer: 🟡 PARTIAL (AttributeValueService OK, brak VariantSkuGenerator)
- UI Layer: ❌ MISSING (2 blade files + 3 lifecycle hooks)
- Tests: ❌ NOT STARTED (13 tests do napisania)
- Documentation: ❌ NOT STARTED (2 docs do stworzenia)

**Co zostało zrobione dziś:**
- ✅ Analiza 10 plików kodu (Models, Services, Livewire, Blade)
- ✅ Weryfikacja istniejącego planu ETAP_05f
- ✅ Identyfikacja statusu implementacji (60% infrastructure ready)
- ✅ Diagram architektury (3 diagramy: High-Level, Data Flow, Dependencies)
- ✅ Technical details (methods, lifecycle hooks, UI components)
- ✅ Edge cases analysis (5 cases identified)
- ✅ Implementation roadmap (5 faz, dependencies, timeline)
- ✅ Success criteria (functionality + quality + docs)

**Next Agent:** `laravel-expert` (FAZA 1: VariantSkuGenerator Service)

**Estymacja do completion:** 14h (~2 dni robocze)

---

**RAPORT KOŃCZY:** 2025-12-09 14:45
**Agent:** architect
