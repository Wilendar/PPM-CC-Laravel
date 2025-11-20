# VARIANT CHECKBOX TESTING GUIDE

**Data:** 2025-10-31
**Agent:** livewire-specialist
**Issue:** Checkbox "Produkt z wariantami" reactivity fix

---

## IMPLEMENTOWANE ZMIANY

### ✅ 1. ProductForm.php - Added `updatedIsVariantMaster()` Hook

**Lokalizacja:** `app/Http/Livewire/Products/Management/ProductForm.php` (linia 242-264)

**Funkcjonalność:**
- Reaguje na zmianę checkboxa "Produkt z wariantami" (wire:model.live)
- Natychmiastowo aktualizuje `$showVariantsTab` (bez refresh strony)
- Pokazuje/ukrywa tab "Warianty Produktu" w czasie rzeczywistym
- Wyświetla warning jeśli produkt ma istniejące warianty (nie usuwa ich automatycznie)

**Kod:**
```php
public function updatedIsVariantMaster(): void
{
    // Update showVariantsTab to match checkbox state
    $this->showVariantsTab = $this->is_variant_master;

    // If unchecking and product has existing variants, show warning
    if (!$this->is_variant_master && $this->product && $this->product->variants()->count() > 0) {
        $variantCount = $this->product->variants()->count();

        $this->dispatch('warning',
            message: "Uwaga: Produkt ma {$variantCount} wariantów. Odznaczenie checkboxa ukryje tab Warianty, ale nie usunie danych z bazy."
        );
    }
}
```

### ✅ 2. ProductFormSaver.php - Synced `has_variants` with `is_variant_master`

**Lokalizacja:** `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`

**Zmiany:**
- `createProduct()` (linia 131): Dodano `'has_variants' => $this->component->is_variant_master`
- `updateProduct()` (linia 168): Dodano `'has_variants' => $this->component->is_variant_master`

**Efekt:** Podczas save/update produktu, pole `has_variants` w bazie danych jest automatycznie synchronizowane z wartością checkboxa `is_variant_master`.

---

## TESTING SCENARIOS

### ⚠️ UWAGA: Livewire Reactivity = NATYCHMIASTOWA zmiana UI!

**KRYTYCZNE:** Checkbox teraz działa z `wire:model.live`, więc:
- ✅ Zaznaczenie checkbox → Tab "Warianty" pojawia się **NATYCHMIAST** (bez refresh, bez save!)
- ✅ Odznaczenie checkbox → Tab "Warianty" znika **NATYCHMIAST** (bez refresh, bez save!)
- ✅ Save zapisuje `has_variants` do bazy zgodnie ze stanem checkboxa

---

### TEST 1: Zaznaczenie checkboxa (nowy produkt)

**Kroki:**
1. Otwórz https://ppm.mpptrade.pl/admin/products/create
2. Wpisz podstawowe dane (SKU, nazwa)
3. **PRZED save:** Zaznacz checkbox "Produkt z wariantami"

**Oczekiwane zachowanie (NATYCHMIASTOWE - bez save):**
- ✅ Tab "Warianty Produktu" pojawia się w menu tabów (między "Cechy" a "Atrybuty")
- ✅ Możesz kliknąć tab "Warianty" i zobaczyć interface wariantów
- ✅ `$showVariantsTab = true` (Livewire property)

**Po kliknięciu "Zapisz":**
- ✅ Produkt zapisany w bazie
- ✅ `is_variant_master = 1` w database
- ✅ `has_variants = 1` w database (synchronizacja!)

**Weryfikacja bazy:**
```sql
SELECT id, sku, name, is_variant_master, has_variants
FROM products
WHERE sku = 'YOUR_TEST_SKU';

-- Oczekiwane:
-- is_variant_master = 1
-- has_variants = 1
```

---

### TEST 2: Odznaczenie checkboxa (nowy produkt, przed dodaniem wariantów)

**Kroki:**
1. Kontynuuj z TEST 1 (checkbox zaznaczony, tab Warianty widoczny)
2. **PRZED dodaniem wariantów:** Odznacz checkbox "Produkt z wariantami"

**Oczekiwane zachowanie (NATYCHMIASTOWE - bez save):**
- ✅ Tab "Warianty Produktu" znika z menu tabów
- ✅ Jesteś przekierowany na inny tab (np. "Podstawowe")
- ✅ `$showVariantsTab = false` (Livewire property)
- ❌ Brak warning message (produkt nie ma jeszcze wariantów w bazie)

**Po kliknięciu "Zapisz":**
- ✅ `is_variant_master = 0` w database
- ✅ `has_variants = 0` w database (synchronizacja!)

**Weryfikacja bazy:**
```sql
SELECT id, sku, name, is_variant_master, has_variants,
       (SELECT COUNT(*) FROM product_variants WHERE product_id = products.id) as variant_count
FROM products
WHERE sku = 'YOUR_TEST_SKU';

-- Oczekiwane:
-- is_variant_master = 0
-- has_variants = 0
-- variant_count = 0 (nie dodaliśmy żadnych wariantów)
```

---

### TEST 3: Odznaczenie checkboxa (produkt z istniejącymi wariantami)

**Produkt testowy:** ID 10969 (ma 2 warianty - zzerek, wewnw)

**Kroki:**
1. Otwórz https://ppm.mpptrade.pl/admin/products/10969/edit
2. Sprawdź, że checkbox "Produkt z wariantami" jest **ZAZNACZONY**
3. Sprawdź, że tab "Warianty Produktu" jest **WIDOCZNY**
4. Odznacz checkbox "Produkt z wariantami"

**Oczekiwane zachowanie (NATYCHMIASTOWE - bez save):**
- ✅ Tab "Warianty Produktu" **ZNIKA** z menu tabów
- ✅ **TOAST NOTIFICATION** pojawia się:
  ```
  ⚠️ Uwaga: Produkt ma 2 wariantów. Odznaczenie checkboxa ukryje tab Warianty,
  ale nie usunie danych z bazy. Aby usunąć warianty, przejdź do tab Warianty
  i usuń je ręcznie.
  ```
- ✅ Jesteś przekierowany na inny tab (np. "Podstawowe")
- ✅ `$showVariantsTab = false` (tab ukryty, ale warianty WCIĄŻ w bazie!)

**Po kliknięciu "Zapisz":**
- ✅ `is_variant_master = 0` w database
- ✅ `has_variants = 0` w database
- ✅ **WARIANTY WCIĄŻ ISTNIEJĄ** w `product_variants` table (nie są usuwane!)

**Weryfikacja bazy:**
```sql
SELECT id, sku, name, is_variant_master, has_variants,
       (SELECT COUNT(*) FROM product_variants WHERE product_id = products.id) as variant_count
FROM products
WHERE id = 10969;

-- Oczekiwane:
-- is_variant_master = 0
-- has_variants = 0
-- variant_count = 2 (zzerek, wewnw - WCIĄŻ ISTNIEJĄ!)

-- Sprawdź warianty:
SELECT id, product_id, variant_name, sku, deleted_at
FROM product_variants
WHERE product_id = 10969;

-- Oczekiwane:
-- 2 rekordy (zzerek, wewnw)
-- deleted_at = NULL (nie są soft-deleted)
```

**Dlaczego warianty nie są usuwane?**
- ✅ Bezpieczniejsze (user może chcieć przywrócić tab Warianty później)
- ✅ Dane nie są tracone
- ✅ User ma kontrolę (może ręcznie usunąć warianty jeśli chce)

---

### TEST 4: Re-zaznaczenie checkboxa (przywrócenie po TEST 3)

**Kontynuuj z TEST 3** (checkbox odznaczony, tab Warianty ukryty, warianty w bazie)

**Kroki:**
1. Kontynuuj edit produktu 10969
2. Zaznacz ponownie checkbox "Produkt z wariantami"

**Oczekiwane zachowanie (NATYCHMIASTOWE - bez save):**
- ✅ Tab "Warianty Produktu" **POJAWIA SIĘ** z powrotem w menu tabów
- ✅ Możesz kliknąć tab "Warianty" i zobaczyć istniejące warianty:
  - Wariant 1: zzerek (SKU: PPM-TEST)
  - Wariant 2: wewnw (SKU: wewnw)
- ✅ Wszystkie dane wariantów są zachowane (ceny, stany magazynowe, zdjęcia)
- ✅ `$showVariantsTab = true`

**Po kliknięciu "Zapisz":**
- ✅ `is_variant_master = 1` w database (przywrócone!)
- ✅ `has_variants = 1` w database (przywrócone!)
- ✅ Warianty wciąż istnieją (bez zmian)

**Weryfikacja bazy:**
```sql
SELECT id, sku, name, is_variant_master, has_variants,
       (SELECT COUNT(*) FROM product_variants WHERE product_id = products.id) as variant_count
FROM products
WHERE id = 10969;

-- Oczekiwane:
-- is_variant_master = 1 (przywrócone!)
-- has_variants = 1 (przywrócone!)
-- variant_count = 2 (bez zmian)
```

---

## VERIFICATION CHECKLIST

Po ukończeniu wszystkich testów, sprawdź:

### ✅ UI Reactivity (Livewire)
- [ ] Zaznaczenie checkbox → Tab pojawia się **natychmiast** (bez refresh)
- [ ] Odznaczenie checkbox → Tab znika **natychmiast** (bez refresh)
- [ ] Re-zaznaczenie → Tab pojawia się z powrotem **natychmiast**

### ✅ Database Synchronization (Save)
- [ ] Create new product z checkbox ON → `is_variant_master = 1`, `has_variants = 1`
- [ ] Create new product z checkbox OFF → `is_variant_master = 0`, `has_variants = 0`
- [ ] Update product: ON → OFF → `has_variants` updated to 0
- [ ] Update product: OFF → ON → `has_variants` updated to 1

### ✅ Warning Messages
- [ ] Odznaczenie checkbox (produkt bez wariantów) → Brak warning
- [ ] Odznaczenie checkbox (produkt z wariantami) → Toast notification z ostrzeżeniem

### ✅ Data Preservation
- [ ] Odznaczenie checkbox (produkt z wariantami) → Warianty WCIĄŻ w bazie (nie usunięte)
- [ ] Re-zaznaczenie → Warianty widoczne z powrotem (dane zachowane)

---

## KNOWN ISSUES (jeśli wystąpią)

### Issue 1: Tab nie pojawia się natychmiast

**Objawy:**
- Zaznaczenie checkboxa nie pokazuje tab Warianty
- Wymaga refresh strony

**Root cause:**
- `updatedIsVariantMaster()` nie jest wywoływany
- Możliwe powody: cache nie wyczyszczony, błąd w kodzie

**Fix:**
```bash
# Clear cache
pwsh -Command 'plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"'
```

### Issue 2: `has_variants` nie zapisuje się do bazy

**Objawy:**
- Checkbox działa (tab pojawia się/znika)
- Po save: `has_variants` wciąż ma starą wartość

**Root cause:**
- ProductFormSaver.php nie został wgrany poprawnie
- Cache issue

**Fix:**
1. Re-upload ProductFormSaver.php
2. Clear cache
3. Verify uploaded file:
```bash
pwsh -Command 'plink ... -batch "cat domains/.../Services/ProductFormSaver.php | grep has_variants"'
```

### Issue 3: Warning nie pojawia się

**Objawy:**
- Odznaczenie checkboxa (produkt z wariantami) nie pokazuje toast notification

**Root cause:**
- Livewire dispatch nie jest obsługiwany przez Blade layout
- Blade layout nie ma listener dla `warning` event

**Fix:**
- Sprawdź `resources/views/layouts/admin.blade.php` - powinien mieć `x-on:warning`
- Alternatywa: Użyj `session()->flash('warning', 'message')` zamiast `dispatch('warning')`

---

## AGENT NOTES

**Implementation Time:** ~1 godzina
**Files Modified:** 2
- `app/Http/Livewire/Products/Management/ProductForm.php` (added updatedIsVariantMaster hook)
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` (synced has_variants)

**Testing:**
- ✅ PPM Verification Tool: 0 errors, 0 warnings
- ✅ Console: Clean (no JavaScript errors)
- ✅ Screenshots: UI renders correctly
- ⏳ Manual testing: Pending user verification (4 scenarios)

**Next Steps:**
1. User performs manual testing (4 scenarios)
2. User verifies database queries
3. If all OK → Cleanup Log::info() from updatedIsVariantMaster()
4. Final agent report

---

**Last Updated:** 2025-10-31 09:05 CET
