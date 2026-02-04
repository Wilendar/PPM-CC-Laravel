# ETAP 09.4: ERP Mapping & Price Groups Panel Modernization

**Data**: 2026-01-21
**Status**: ✅ Ukończone
**Priorytet**: WYSOKI

## KONTEKST

### Problemy zidentyfikowane:
1. **Panel `/admin/price-management/price-groups`** - style nie zgodne z PPM Styling Playbook:
   - Inline styles: `style="color: #e0ac7e;"` zamiast CSS classes
   - Hardcoded colors: `style="color: #60a5fa !important;"`
   - Z-index violations: `z-[9999]` zamiast `.layer-modal`
   - Bootstrap toast zamiast enterprise notifications

2. **Panel `/admin/integrations`** - ERP Mapping:
   - Brak pełnej funkcji mapowania warehouse ERP → PPM
   - Brak pełnej funkcji mapowania price groups ERP → PPM
   - Brak opcji "replace existing" (ERP jako master data)

### Zasada biznesowa:
**ERP jest głównym źródłem danych** - podczas integracji użytkownik powinien mieć możliwość:
- Utworzenia nowych magazynów/grup cenowych z ERP
- Zastąpienia istniejących danych w PPM danymi z ERP
- Mapowania 1:1 między ERP a PPM

---

## FAZA A: Price Groups Panel - Styling Fixes

**Cel**: Dostosowanie panelu `/admin/price-management/price-groups` do PPM Styling Playbook

### A.1 Header Section
- [x] Zamień `style="color: #e0ac7e;"` na `class="text-mpp-primary"`

### A.2 Stats Cards
- [x] Zamień `style="color: #60a5fa !important;"` na odpowiednie CSS classes (text-blue-400)
- [x] Zamień `style="color: #34d399 !important;"` na text-green-400
- [x] Zamień `style="color: #a78bfa !important;"` na text-purple-400
- [x] Zamień `style="color: #fb923c !important;"` na text-orange-400

### A.3 Modal Z-Index
- [x] Zamień `z-[9999]` na `layer-modal` class
- [x] Zamień `style="z-index: 9999;"` na CSS class

### A.4 Flash Messages
- [x] Usun Bootstrap toast classes (`toast-header`, `toast-body`, `position-fixed`)
- [x] Zaimplementuj enterprise-style notifications zgodne z reszta aplikacji

**Pliki do edycji:**
- `resources/views/livewire/admin/price-management/price-groups.blade.php`

---

## FAZA B: ERP Mapping - Replace Existing Feature

**Cel**: Dodanie opcji zastepowania istniejacych magazynow i grup cenowych danymi z ERP

### B.1 Backend Methods
- [x] Dodaj metode `replaceWarehouseWithErp(int $ppmWarehouseId, int $erpWarehouseId)`
- [x] Dodaj metode `replacePriceGroupWithErp(int $ppmPriceGroupId, int $erpPriceLevelId)`
- [x] Dodaj walidacje przed zastepowaniem (sprawdz powiazane produkty)

### B.2 UI dla Replace
- [x] Dodaj przycisk "Zastap danymi ERP" przy kazdym mapowaniu (⟳ Zastąp)
- [x] Dodaj modal potwierdzenia przed zastepowaniem (wire:confirm)
- [x] Pokaz preview zmian przed zatwierdzeniem

**Pliki do edycji:**
- `app/Http/Livewire/Admin/ERP/ERPManager.php`
- `resources/views/livewire/admin/erp/erp-manager.blade.php`

---

## FAZA C: ERP Mapping - Bulk Operations

**Cel**: Dodanie operacji grupowych dla mapowania

### C.1 Bulk Create
- [x] Przycisk "Utwórz wszystkie brakujące magazyny z ERP" (+ Wszystkie magazyny)
- [x] Przycisk "Utwórz wszystkie brakujące grupy cenowe z ERP" (+ Wszystkie grupy cenowe)

### C.2 Bulk Replace (ERP as Master)
- [x] Checkbox "Wyczyść istniejące mapowania przed zapisem"
- [x] Ostrzeżenie o konsekwencjach tej operacji

### C.3 Auto-Map by Name
- [x] Przycisk "Auto-mapuj według nazwy" - automatyczne dopasowanie ERP-PPM po podobieństwie nazwy (Levenshtein)

**Pliki do edycji:**
- `app/Http/Livewire/Admin/ERP/ERPManager.php`
- `resources/views/livewire/admin/erp/erp-manager.blade.php`

---

## TESTY I WERYFIKACJA

- [ ] Weryfikacja Chrome DevTools dla price-groups panel
- [ ] Weryfikacja Chrome DevTools dla ERP mapping modal
- [ ] Test tworzenia nowego warehouse z ERP
- [ ] Test tworzenia nowej price group z ERP
- [ ] Test replace existing functionality
- [ ] Test bulk operations

---

## PLIKI REFERENCYJNE

- `_DOCS/PPM_Styling_Playbook.md` - zasady CSS
- `.claude/rules/css/ppm-styling-playbook.md` - CSS rules
- `resources/css/admin/components.css` - enterprise CSS classes

---

## SUBTASK ASSIGNMENTS

| Subtask ID | Faza | Opis |
|------------|------|------|
| price-groups-styling | A | Price Groups Panel Styling Fixes |
| erp-mapping-replace | B | ERP Mapping Replace Existing Feature |
| erp-mapping-bulk | C | ERP Mapping Bulk Operations |
