# TODO - Następna Sesja

## 🔴 PRIORYTET KRYTYCZNY: Problem z wyświetlaniem stawki VAT

**Status:** NIEROZWIĄZANY po deploymencie fix z value formatting

**Problem:**
Stawka VAT nie jest poprawnie odczytywana/wyświetlana z kolumny `product_shop_data.tax_rate_override`

**Szczegóły:**
- Użytkownik zmienia tax rate w Shop Tab (np. na 8%)
- Kliknięcie ZAPISZ tworzy poprawny JOB
- PrestaShop otrzymuje poprawną wartość (`tax_rules_group: 2` dla 8%)
- Baza danych `product_shop_data.tax_rate_override` ma poprawną wartość (`8.00`)
- **ALE:** UI dropdown nadal pokazuje poprzednią wartość (np. "23%" lub "Użyj domyślnej PPM")
- **ALE:** Indicator pokazuje "NIE ZMAPOWANE W PRESTASHOP" zamiast "ZGODNE"

**Wykonane fixy (NIE ROZWIĄZAŁY problemu):**
1. ✅ Fix PropertyNotFoundException (`$currentMode` → `$activeShopId`)
2. ✅ Fix numeric value matching (Integer vs String w switch-case)
3. ✅ Fix `tax_rate_override` brak w `pendingChanges` flow
4. ✅ Fix hardcoded CSS rules (usunięte `!important`)
5. ✅ Fix `getTaxRateIndicator()` read from form state instead of DB
6. ✅ Fix CRITICAL global default overwrite (`tax_rate` NULL w Shop Mode)
7. ✅ Fix indicator messages (4-tier: OCZEKUJE/DZIEDZICZONE/ZGODNE/NIE ZMAPOWANE)
8. ✅ Fix `loadShopDataToForm()` - dodany reload `tax_rate_override` z DB
9. ✅ **OSTATNI FIX:** Blade template value formatting (`value="8"` → `value="8.00"`)

**Logi produkcyjne potwierdzają:**
```
[FAZA 5.2 UI RELOAD] loadShopDataToForm called
  shop_id: 1
  tax_rate_override_from_db: "8.00"
  selectedTaxRateOption: "8.00"  ← Property USTAWIONA poprawnie!

[ProductForm FAZA 5.2] Loaded tax rule groups from PrestaShop
  shop_id: 1
  groups_count: 4

Tax Rule Groups zawierają:
  [1] rate: 8, label: "VAT 8% (Obniżona)", prestashop_group_id: 2  ← Istnieje!
```

**Diagnostic z produkcji:**
- `availableTaxRuleGroups[1]` zawiera `rate: 8` (Integer)
- Blade template PO FIX generuje: `<option value="8.00">` (String z .00)
- Livewire property: `$this->selectedTaxRateOption = "8.00"`
- **TEORETYCZNIE** powinno działać, ale nie działa!

**Możliwe przyczyny do zbadania:**
1. **Livewire reactivity issue** - Property zmieniona ale UI nie re-renderuje
2. **Alpine.js conflict** - Jakieś x-model lub x-bind na dropdownie?
3. **Livewire lifecycle timing** - `loadShopDataToForm()` wywoływane przed `loadTaxRuleGroupsForShop()`?
4. **Cache issue** - Vite manifest? Blade cache? Livewire snapshot?
5. **Wire:model.live binding** - Może wymaga ręcznego `$this->dispatch('refresh')`?
6. **Multiple instances** - Czy przypadkiem nie ma wielu instancji komponentu?

**Pliki do analizy:**
- `app/Http/Livewire/Products/Management/ProductForm.php`
  - Linia 1810: `switchToShop()` method
  - Linia 1914: `loadShopDataToForm()` method
  - Linia 398: `loadTaxRuleGroupsForShop()` method
  - Linia 1938-1960: Tax rate override reload logic
- `resources/views/livewire/products/management/product-form.blade.php`
  - Linia 763: `wire:model.live="selectedTaxRateOption"`
  - Linia 784: `<option value="{{ number_format(...) }}">`

**Diagnostic scripts gotowe:**
- `_TEMP/diagnose_tax_rule_groups.php` - Sprawdza zawartość tax rule groups
- `_TEMP/deploy_dropdown_value_fix.ps1` - Deployment ostatniego fix

**Następne kroki do wykonania:**
1. ✅ Sprawdź console browser (może JS errors?)
2. ✅ Sprawdź DevTools Network tab (czy są AJAX requesty Livewire?)
3. ✅ Dodaj więcej debug logging do `updatedSelectedTaxRateOption()`
4. ✅ Sprawdź czy `$this->selectedTaxRateOption` jest public property (musi być!)
5. ✅ Test manual property refresh: `$this->dispatch('$refresh')`
6. ✅ Zbadaj Livewire snapshot (wire:snapshot w HTML)
7. ✅ Weryfikuj timing: Czy `loadTaxRuleGroupsForShop()` wykonuje się PRZED `loadShopDataToForm()`?

**User feedback:**
> "przeanalizuj dokłądnie co się dzieje z wyświetlaniem stawki VAT w Shop TAB, ponieważ po zmianie stawki na SHOP TAB jest tworzony poprawny JOB i jest przesyłana poprawna stawka na Prestashop, ale PPM wciąż pokazuje 23% i napisa 'Nie zmapowane w prestashop'"

**Data ostatniej sesji:** 2025-11-14
**Status TODO:** PENDING - wymaga dogłębnej analizy Livewire reactivity + browser debugging
