# ETAP_05f: Auto SKU Suffix/Prefix System dla Wariantów

**Status ETAPU:** ❌ Nie rozpoczęty
**Estymacja:** 14h (~2 dni robocze)
**Priorytet:** Medium
**Data utworzenia:** 2025-12-09
**Agent:** architect

---

## Cel Etapu

Implementacja systemu automatycznego generowania SKU dla wariantów produktów na podstawie prefix/suffix zdefiniowanych w AttributeValue.

**Wymagania funkcjonalne:**
1. Konfiguracja prefix/suffix per AttributeValue w `/admin/variants`
2. Checkbox "Auto-generuj SKU" w modalu edycji wariantu
3. Reaktywne generowanie SKU podczas wyboru atrybutów
4. Możliwość ręcznej edycji SKU (disables auto-mode)
5. Persystencja do bazy danych

**Format SKU:** `PREFIX1-PREFIX2-BASE-SKU-SUFFIX1-SUFFIX2`

**Przykład:**
- Base Product SKU: `MR-MRF-E`
- Atrybuty: Kolor=Czerwony (suffix="-CZE"), Rozmiar=XL (prefix="XL-")
- Generated Variant SKU: `XL-MR-MRF-E-CZE`

---

## ❌ 1. FAZA 1: Backend - VariantSkuGenerator Service

**Status:** ❌ Nie rozpoczęto
**Estymacja:** 2h
**Opis:** Stworzenie serwisu odpowiedzialnego za logikę generowania SKU.

### ❌ 1.1 Utworzenie pliku service
**Status:** ❌

**Zadania:**
- Utworzyć `app/Services/Product/VariantSkuGenerator.php`
- Namespace: `App\Services\Product`
- Class: `VariantSkuGenerator`
- Dependency injection: `AttributeValue` model

### ❌ 1.2 Implementacja metod generowania SKU
**Status:** ❌

#### ❌ 1.2.1 Metoda główna: generateSku()
**Status:** ❌

```php
/**
 * Generate SKU for variant based on product base SKU and attributes
 *
 * @param Product $product Base product
 * @param array $attributes Array of ['attribute_type_id' => 'value_id']
 * @return string Generated SKU
 */
public function generateSku(Product $product, array $attributes): string
```

**Logika:**
1. Pobierz base SKU z `$product->sku`
2. Iteruj przez `$attributes`
3. Dla każdego `value_id` pobierz `AttributeValue`
4. Zbierz prefixes gdzie `auto_prefix_enabled = true`
5. Zbierz suffixes gdzie `auto_suffix_enabled = true`
6. Zwróć skomponowany SKU

#### ❌ 1.2.2 Metoda pomocnicza: getPrefixesFromAttributes()
**Status:** ❌

```php
/**
 * Extract prefixes from attributes
 *
 * @param array $attributes
 * @return array Array of prefix strings
 */
protected function getPrefixesFromAttributes(array $attributes): array
```

#### ❌ 1.2.3 Metoda pomocnicza: getSuffixesFromAttributes()
**Status:** ❌

```php
/**
 * Extract suffixes from attributes
 *
 * @param array $attributes
 * @return array Array of suffix strings
 */
protected function getSuffixesFromAttributes(array $attributes): array
```

#### ❌ 1.2.4 Metoda pomocnicza: composeSku()
**Status:** ❌

```php
/**
 * Compose final SKU from parts
 *
 * @param string $baseSku Base product SKU
 * @param array $prefixes Array of prefix strings
 * @param array $suffixes Array of suffix strings
 * @return string Final composed SKU
 */
protected function composeSku(string $baseSku, array $prefixes, array $suffixes): string
```

**Format:** `implode('-', array_filter([...$prefixes, $baseSku, ...$suffixes]))`

### ❌ 1.3 Testy jednostkowe
**Status:** ❌

#### ❌ 1.3.1 Test: Single suffix
**Input:** Base="MR-MRF-E", Attributes=[Czerwony(suffix="-CZE")]
**Expected:** "MR-MRF-E-CZE"

#### ❌ 1.3.2 Test: Prefix + Suffix
**Input:** Base="MR-MRF-E", Attributes=[XL(prefix="XL-"), Czerwony(suffix="-CZE")]
**Expected:** "XL-MR-MRF-E-CZE"

#### ❌ 1.3.3 Test: Multiple suffixes
**Input:** Base="PROD", Attributes=[Czerwony(-CZE), Bawelna(-BAW)]
**Expected:** "PROD-CZE-BAW"

#### ❌ 1.3.4 Test: Empty attributes
**Input:** Base="PROD", Attributes=[]
**Expected:** "PROD"

---

## ❌ 2. FAZA 2: Admin Panel - UI Konfiguracji Atrybutów

**Status:** ❌ Nie rozpoczęto
**Estymacja:** 3h
**Opis:** Dodanie UI do AttributeValueManager dla konfiguracji prefix/suffix.

### ❌ 2.1 Modyfikacja Blade template
**Status:** ❌

**Plik:** `resources/views/livewire/admin/variants/attribute-value-manager.blade.php`

#### ❌ 2.1.1 Dodanie sekcji "Automatyczne SKU"
**Status:** ❌

**Lokalizacja:** Po polu `color_hex` w formularzu edycji (~linia 200)

**Elementy UI:**
- Header sekcji: "Automatyczne SKU dla wariantów"
- Checkbox: "Dodaj prefix do SKU" → `wire:model.live="formData.auto_prefix_enabled"`
- Input text (conditional): `wire:model="formData.auto_prefix"` (visible gdy checkbox ON)
- Checkbox: "Dodaj suffix do SKU" → `wire:model.live="formData.auto_suffix_enabled"`
- Input text (conditional): `wire:model="formData.auto_suffix"` (visible gdy checkbox ON)
- Placeholder examples: "np. XL-", "np. -CZE"
- Help text: "Przykład: 'XL-' → Wariant SKU: 'XL-PROD-001'"

**CSS Classes:** PPM UI Standards
- Container: `bg-gray-900/50 border border-gray-700 rounded-lg p-4`
- Checkbox: `w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded`
- Input: `w-full px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-white`
- Help text: `text-xs text-gray-500`

### ❌ 2.2 Walidacja w Livewire component
**Status:** ❌

**Plik:** `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`

#### ❌ 2.2.1 Rozszerzenie validation rules
**Status:** ❌

**Metoda:** `save()` (~linia 201)

**Nowe rules:**
```php
'formData.auto_prefix' => 'nullable|string|max:20|regex:/^[A-Z0-9-_]+$/i',
'formData.auto_suffix' => 'nullable|string|max:20|regex:/^[A-Z0-9-_]+$/i',
```

**Validation messages:**
```php
'formData.auto_prefix.regex' => 'Prefix moze zawierac tylko litery, cyfry, myslniki i podkreslenia',
'formData.auto_suffix.regex' => 'Suffix moze zawierac tylko litery, cyfry, myslniki i podkreslenia',
'formData.auto_prefix.max' => 'Prefix nie moze przekraczac 20 znakow',
'formData.auto_suffix.max' => 'Suffix nie moze przekraczac 20 znakow',
```

### ❌ 2.3 Testy UI - Chrome DevTools
**Status:** ❌

#### ❌ 2.3.1 Test: Wyświetlanie sekcji Auto SKU
**Verify:** Sekcja widoczna po otwarciu edycji AttributeValue

#### ❌ 2.3.2 Test: Checkbox toggle (prefix)
**Action:** Zaznacz "Dodaj prefix do SKU"
**Verify:** Input field dla prefix staje się widoczny

#### ❌ 2.3.3 Test: Checkbox toggle (suffix)
**Action:** Zaznacz "Dodaj suffix do SKU"
**Verify:** Input field dla suffix staje się widoczny

#### ❌ 2.3.4 Test: Walidacja - nieprawidłowe znaki
**Action:** Wpisz prefix z spacjami: "XL 123"
**Verify:** Error message: "Prefix moze zawierac tylko litery..."

#### ❌ 2.3.5 Test: Zapis do bazy danych
**Action:** Zapisz AttributeValue z prefix="-CZE", auto_prefix_enabled=true
**Verify:** Database `attribute_values` ma poprawne wartości

---

## ❌ 3. FAZA 3: Product Form - Modal Edycji Wariantu

**Status:** ❌ Nie rozpoczęto
**Estymacja:** 4h
**Opis:** Dodanie checkbox "Auto-generuj SKU" i reaktywnego pola SKU.

### ❌ 3.1 Modyfikacja VariantCrudTrait
**Status:** ❌

**Plik:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`

#### ❌ 3.1.1 Rozszerzenie $variantData property
**Status:** ❌

**Lokalizacja:** ~linia 41

**Dodać:**
```php
'attributes' => [], // AttributeType => AttributeValue mappings
'auto_generate_sku' => true, // NEW: Default enabled
```

#### ❌ 3.1.2 Nowa metoda: updateVariantSku()
**Status:** ❌

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

#### ❌ 3.1.3 Livewire lifecycle hook: updatedVariantDataAttributes()
**Status:** ❌

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

#### ❌ 3.1.4 Livewire lifecycle hook: updatedVariantDataSku()
**Status:** ❌

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

        Log::debug('[AUTO SKU] Disabled due to manual edit', [
            'sku' => $this->variantData['sku'],
        ]);
    }
}
```

#### ❌ 3.1.5 Computed property: availableAttributeTypes
**Status:** ❌

```php
#[Computed]
public function availableAttributeTypes(): Collection
{
    return AttributeType::with('values')
        ->where('is_active', true)
        ->ordered()
        ->get();
}
```

### ❌ 3.2 Modyfikacja Blade template
**Status:** ❌

**Plik:** `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php`

#### ❌ 3.2.1 Dodanie checkbox "Auto-generuj SKU"
**Status:** ❌

**Lokalizacja:** Po linii 61 (przed SKU Field)

**Kod:**
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
```

#### ❌ 3.2.2 Modyfikacja SKU Field (readonly when auto mode)
**Status:** ❌

**Lokalizacja:** ~linia 49-61

**Zmiany:**
- Dodać `@if($variantData['auto_generate_sku']) readonly @endif`
- Dodać conditional CSS: `opacity-75 cursor-not-allowed` gdy auto mode
- Dodać info text: "Generowane automatycznie" obok label
- Dodać help text: Instrukcja jak włączyć/wyłączyć auto mode

#### ❌ 3.2.3 Dodanie sekcji wyboru atrybutów
**Status:** ❌

**Lokalizacja:** Zastąpić placeholder (~linia 77-87)

**Kod:**
```blade
{{-- ATTRIBUTES SELECTION --}}
<div>
    <label class="block text-sm font-medium text-gray-300 mb-2">
        Atrybuty Wariantu
    </label>
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
</div>
```

### ❌ 3.3 Testy UI - Chrome DevTools
**Status:** ❌

#### ❌ 3.3.1 Test: Checkbox default state
**Verify:** "Auto-generuj SKU" checkbox zaznaczony domyślnie przy tworzeniu nowego wariantu

#### ❌ 3.3.2 Test: SKU auto-generation (single attribute)
**Action:** Wybierz Kolor=Czerwony (suffix="-CZE")
**Verify:** SKU field automatycznie aktualizuje się: "BASE-PRODUCT-SKU-CZE"

#### ❌ 3.3.3 Test: SKU auto-generation (multiple attributes)
**Action:** Wybierz Kolor=Czerwony (-CZE), Rozmiar=XL (prefix="XL-")
**Verify:** SKU = "XL-BASE-PRODUCT-SKU-CZE"

#### ❌ 3.3.4 Test: Manual edit disables auto mode
**Action:** Zacznij pisać w SKU field
**Verify:** Checkbox "Auto-generuj SKU" automatycznie się odznacza

#### ❌ 3.3.5 Test: Re-enable auto mode
**Action:** Odznacz i ponownie zaznacz checkbox
**Verify:** SKU regeneruje się na podstawie aktualnych atrybutów

#### ❌ 3.3.6 Test: SKU field readonly w auto mode
**Verify:** W auto mode SKU field ma `readonly` attribute i opacity-75

---

## ❌ 4. FAZA 4: Integracja - Zapisywanie Wariantu

**Status:** ❌ Nie rozpoczęto
**Estymacja:** 2h
**Opis:** Upewnić się, że generated SKU jest poprawnie zapisywane do bazy danych.

### ❌ 4.1 Modyfikacja metody storeVariant()
**Status:** ❌

**Plik:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`

#### ❌ 4.1.1 Pre-save SKU regeneration check
**Status:** ❌

**Lokalizacja:** W metodzie `storeVariant()` przed `ProductVariant::create()`

**Kod:**
```php
// ENSURE: If auto_generate_sku is ON and SKU is empty, regenerate
if ($this->variantData['auto_generate_sku'] && empty($this->variantData['sku'])) {
    $this->updateVariantSku();
}

// Additional validation: SKU must not be empty
if (empty($this->variantData['sku'])) {
    $this->addError('variantData.sku', 'SKU nie moze byc puste');
    return;
}
```

#### ❌ 4.1.2 Zapisywanie atrybutów wariantu
**Status:** ❌

**Lokalizacja:** Po `ProductVariant::create()`

**Kod:**
```php
// Create variant attributes (value_id FK)
foreach ($this->variantData['attributes'] as $typeId => $valueId) {
    if (!$valueId) continue;

    VariantAttribute::create([
        'variant_id' => $variant->id,
        'attribute_type_id' => $typeId,
        'value_id' => $valueId,
    ]);
}
```

#### ❌ 4.1.3 Logging dla debug
**Status:** ❌

```php
Log::info('[VARIANT CREATED] With auto SKU', [
    'variant_id' => $variant->id,
    'sku' => $variant->sku,
    'auto_generated' => $this->variantData['auto_generate_sku'],
    'attributes' => $this->variantData['attributes'],
]);
```

### ❌ 4.2 Modyfikacja metody updateVariant()
**Status:** ❌

#### ❌ 4.2.1 Obsługa edycji atrybutów
**Status:** ❌

**Logika:**
1. Jeśli `auto_generate_sku` jest włączone → regeneruj SKU
2. Update `ProductVariant` record
3. Sync `VariantAttribute` records (delete old, create new)

### ❌ 4.3 Testy integracyjne
**Status:** ❌

#### ❌ 4.3.1 Test: Create variant with auto-SKU
**Action:** Utwórz wariant z auto-SKU enabled
**Verify DB:** `product_variants.sku` = generated value
**Verify DB:** `variant_attributes` ma poprawne `value_id` FKs

#### ❌ 4.3.2 Test: Create variant with manual SKU
**Action:** Utwórz wariant z ręcznym SKU
**Verify DB:** `product_variants.sku` = manually entered value

#### ❌ 4.3.3 Test: Update variant attributes (auto-SKU ON)
**Action:** Edytuj wariant, zmień atrybuty
**Verify:** SKU automatycznie aktualizuje się w UI
**Verify DB:** `product_variants.sku` updated po zapisie

#### ❌ 4.3.4 Test: Update variant SKU manually
**Action:** Edytuj wariant, zmień SKU ręcznie
**Verify:** Auto mode się wyłącza
**Verify DB:** SKU zapisuje się jako ręcznie edytowany

---

## ❌ 5. FAZA 5: Testing & Polish

**Status:** ❌ Nie rozpoczęto
**Estymacja:** 3h
**Opis:** Comprehensive testing, edge cases, UI polish, Chrome DevTools verification.

### ❌ 5.1 End-to-end test scenarios
**Status:** ❌

#### ❌ 5.1.1 Test Scenario 1: Pełny flow - admin + user
**Status:** ❌

**Kroki:**
1. Admin → `/admin/variants`
2. Create AttributeType "Kolor"
3. Create AttributeValue "Czerwony" z `auto_suffix = "-CZE"`, enabled
4. User → ProductForm → Warianty
5. Dodaj wariant, wybierz Kolor=Czerwony
6. Verify: SKU = "BASE-SKU-CZE"
7. Zapisz produkt
8. Verify DB: `product_variants.sku` correct

#### ❌ 5.1.2 Test Scenario 2: Multiple attributes (prefix + suffix)
**Status:** ❌

**Kroki:**
1. Create AttributeValue "XL" (Rozmiar) z `auto_prefix = "XL-"`, enabled
2. Create AttributeValue "Czerwony" (Kolor) z `auto_suffix = "-CZE"`, enabled
3. Dodaj wariant: Rozmiar=XL, Kolor=Czerwony
4. Verify: SKU = "XL-BASE-SKU-CZE"

#### ❌ 5.1.3 Test Scenario 3: Manual override
**Status:** ❌

**Kroki:**
1. Start: Auto-SKU enabled, SKU = "XL-BASE-CZE"
2. User edits SKU manually: "CUSTOM-SKU-001"
3. Verify: Checkbox auto-unchecks
4. Zapisz wariant
5. Verify DB: SKU = "CUSTOM-SKU-001"

#### ❌ 5.1.4 Test Scenario 4: Re-enable auto after manual edit
**Status:** ❌

**Kroki:**
1. Manual SKU = "CUSTOM-SKU-001"
2. User re-checks "Auto-generuj SKU"
3. Verify: SKU regeneruje się: "XL-BASE-CZE"
4. Zapisz
5. Verify DB: SKU updated

### ❌ 5.2 Edge cases testing
**Status:** ❌

#### ❌ 5.2.1 Edge Case: Brak atrybutów
**Input:** Zaznacz "Auto-generuj SKU", ale nie wybierz żadnych atrybutów
**Expected:** SKU = base product SKU (bez prefix/suffix)

#### ❌ 5.2.2 Edge Case: Atrybut bez prefix/suffix
**Input:** Wybierz atrybut który ma `auto_prefix_enabled = false`
**Expected:** SKU = base (atrybut nie wpływa na SKU)

#### ❌ 5.2.3 Edge Case: Duplikaty prefix/suffix
**Input:** Dwa atrybuty mają ten sam suffix "-CZE"
**Expected:** Deduplikacja: "BASE-CZE" (nie "BASE-CZE-CZE")

#### ❌ 5.2.4 Edge Case: Empty base SKU
**Input:** Product ma `sku = null` lub empty string
**Expected:** Walidacja błędu: "Base SKU produktu jest wymagane"

#### ❌ 5.2.5 Edge Case: Bardzo długi SKU
**Input:** Base=50 chars, prefix=20, suffix=20
**Expected:** SKU max 100 chars (validation limit)

### ❌ 5.3 UI Polish & UX improvements
**Status:** ❌

#### ❌ 5.3.1 Loading states
**Dodać:** `wire:loading` indicators podczas generowania SKU

#### ❌ 5.3.2 Tooltips
**Dodać:** Tooltips na checkbox "Auto-generuj SKU" z wyjaśnieniem

#### ❌ 5.3.3 Visual feedback
**Dodać:** Animacja/highlight gdy SKU się regeneruje

#### ❌ 5.3.4 Empty state
**Dodać:** Message gdy brak dostępnych AttributeTypes: "Najpierw utwórz typy atrybutów w /admin/variants"

### ❌ 5.4 Chrome DevTools verification (MANDATORY)
**Status:** ❌

**Reference:** `_DOCS/CHROME_DEVTOOLS_OPTIMIZED_QUERIES.md`

#### ❌ 5.4.1 Verify: Reactive SKU field
**Status:** ❌

**Script:**
```javascript
// Check: SKU field updates when attributes change
const skuField = document.querySelector('input[wire\\:model\\.live="variantData.sku"]');
console.log('SKU value:', skuField.value);
console.log('Is readonly:', skuField.hasAttribute('readonly'));
```

#### ❌ 5.4.2 Verify: Checkbox state persistence
**Status:** ❌

**Script:**
```javascript
const checkbox = document.querySelector('input[wire\\:model\\.live="variantData.auto_generate_sku"]');
console.log('Auto-generate enabled:', checkbox.checked);
```

#### ❌ 5.4.3 Verify: Attributes dropdown
**Status:** ❌

**Script:**
```javascript
const dropdowns = document.querySelectorAll('select[wire\\:model\\.live^="variantData.attributes"]');
console.log('Attribute dropdowns count:', dropdowns.length);
dropdowns.forEach(dd => console.log('Selected:', dd.value));
```

#### ❌ 5.4.4 Verify: Console errors
**Status:** ❌

```javascript
list_console_messages({ types: ["error", "warn"], includePreservedMessages: false })
```

#### ❌ 5.4.5 Verify: Network requests (save variant)
**Status:** ❌

```javascript
list_network_requests({
    resourceTypes: ["xhr", "fetch"],
    pageSize: 10
})
```

#### ❌ 5.4.6 Screenshot verification
**Status:** ❌

**Screenshots:**
- `_TOOLS/screenshots/auto_sku_modal_initial.jpg` - Modal z checkboxem zaznaczonym
- `_TOOLS/screenshots/auto_sku_generated.jpg` - SKU po wyborze atrybutów
- `_TOOLS/screenshots/auto_sku_manual_edit.jpg` - Checkbox odznaczony po ręcznej edycji

### ❌ 5.5 Documentation updates
**Status:** ❌

#### ❌ 5.5.1 Update: CLAUDE.md
**Dodać:** Sekcję o Auto SKU System w "System Wariantów"

#### ❌ 5.5.2 Create: Technical docs
**Plik:** `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md`
**Treść:** Developer guide - jak działa system, jak konfigurować, przykłady

#### ❌ 5.5.3 Update: Plan_Projektu status
**Oznaczyć:** Wszystkie podpunkty ✅ z ścieżkami do plików

---

## 📊 DEPENDENCIES

**Required BEFORE starting:**
- ✅ Database migration `2025_12_09_135739_add_auto_prefix_suffix_to_attribute_values.php` (ISTNIEJE)
- ✅ AttributeValue model updated z fillable/casts (ISTNIEJE)
- ✅ AttributeValueManager formData zawiera prefix/suffix fields (ISTNIEJE)

**Dependencies między fazami:**
- FAZA 2 zależy od FAZA 1 (potrzebny VariantSkuGenerator)
- FAZA 3 zależy od FAZA 1 (potrzebny VariantSkuGenerator)
- FAZA 4 zależy od FAZA 3 (potrzebne UI fields)
- FAZA 5 zależy od FAZA 1-4 (testing całego flow)

---

## ⚠️ EDGE CASES & CONSIDERATIONS

### Edge Cases:
1. **Usunięcie AttributeValue z auto-SKU:** Istniejące warianty NIE są aktualizowane (historical data)
2. **Duplikaty prefix/suffix:** VariantSkuGenerator deduplikuje
3. **Nieprawidłowe znaki w prefix/suffix:** Walidacja regex w AttributeValueManager
4. **Brak base SKU w produkcie:** Walidacja: "Base SKU produktu jest wymagane"
5. **Konflikt SKU (duplicate):** VariantValidation trait sprawdza unique per product

### Performance:
- Generowanie SKU: O(n) gdzie n = liczba atrybutów (~instant dla <10 atrybutów)
- Database queries: 1 query na AttributeValue (N+1 prevented przez eager loading)

### Security:
- SKU validation: unique per product
- Regex dla prefix/suffix: `/^[A-Z0-9-_]+$/i` (tylko bezpieczne znaki)
- SQL injection: Eloquent ORM (automatic escaping)

---

## 📁 PLIKI DO UTWORZENIA/MODYFIKACJI

### Nowe pliki (CREATE):
1. `app/Services/Product/VariantSkuGenerator.php` (~200 linii)
2. `_DOCS/AUTO_SKU_SYSTEM_GUIDE.md` (dokumentacja)

### Modyfikacje (EDIT):
1. `resources/views/livewire/admin/variants/attribute-value-manager.blade.php` (dodać sekcję Auto SKU)
2. `app/Http/Livewire/Admin/Variants/AttributeValueManager.php` (dodać validation rules)
3. `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` (dodać metody + lifecycle hooks)
4. `resources/views/livewire/products/management/partials/variant-edit-modal.blade.php` (dodać checkbox + attributes section)
5. `CLAUDE.md` (update - sekcja Auto SKU)

---

## 🎯 SUCCESS CRITERIA

### Functionality:
- ✅ Admin może skonfigurować prefix/suffix per AttributeValue
- ✅ User może włączyć/wyłączyć auto-generation w modal wariantu
- ✅ SKU generuje się automatycznie podczas wyboru atrybutów
- ✅ User może ręcznie edytować SKU (wyłącza auto mode)
- ✅ SKU poprawnie zapisuje się do bazy danych
- ✅ Multiple attributes (prefix + suffix) działają prawidłowo

### Quality:
- ✅ Walidacja: regex dla prefix/suffix
- ✅ Edge cases obsłużone (empty attributes, duplicates, etc.)
- ✅ Logging: debug logs dla development
- ✅ Tests: 5 end-to-end scenarios PASS
- ✅ Chrome DevTools: zero console errors, all verifications PASS
- ✅ UI: PPM styling standards, responsive, accessible

### Documentation:
- ✅ Technical guide created
- ✅ CLAUDE.md updated
- ✅ Plan_Projektu: wszystkie ✅ z ścieżkami do plików

---

## 📅 TIMELINE

| Data | Milestone |
|------|-----------|
| 2025-12-09 | ✅ Plan utworzony przez architect |
| TBD | FAZA 1: VariantSkuGenerator Service (2h) |
| TBD | FAZA 2: Admin Panel UI (3h) |
| TBD | FAZA 3: Product Form UI (4h) |
| TBD | FAZA 4: Integracja (2h) |
| TBD | FAZA 5: Testing & Polish (3h) |
| TBD | ✅ ETAP UKOŃCZONY |

**Estymacja całkowita:** 14h (~2 dni robocze)

---

## 📝 NOTES

- Migracja database już istnieje - **NIE TWORZYĆ PONOWNIE**
- AttributeValue model już ma pola w fillable - **TYLKO UŻYĆ**
- AttributeValueManager formData już zawiera fields - **TYLKO DODAĆ UI**
- System budowany zgodnie z PPM UI Standards (dark theme, enterprise style)
- Chrome DevTools verification MANDATORY przed completion
- Debug logging WYŁĄCZNIE w development (użyć debug-log-cleanup skill po zakończeniu)

---

**KOLEJNY KROK:** Delegować do `laravel-expert` lub `livewire-specialist` dla implementacji FAZA 1-5.
