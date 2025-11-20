# PRODUKT TESTOWY - PPM-CC-Laravel

**AKTUALNE (2025-11-07):**
- **URL:** https://ppm.mpptrade.pl/admin/products/11018/edit
- **ID:** 11018
- **Status:** Aktywny produkt testowy

## ZASTOSOWANIE

Używaj tego produktu do:
- Manual testing (Variant CRUD, Checkbox Persistence)
- Shop TAB testing (pending sync indicators, auto-dispatch)
- Screenshot verification
- Queue workflow testing

## HISTORIA

**2025-11-07:** ID 11018 - Nowy produkt testowy (poprzedni nieistniejący)

## SKRYPTY WYMAGAJĄCE AKTUALIZACJI

Jeśli jakieś skrypty używają starego ID produktu, zaktualizuj je do 11018:
- `_TOOLS/full_console_test.cjs`
- `_TOOLS/test_variant_crud_suite.cjs` (jeśli zostanie utworzony)
- `_DOCS/VARIANT_MANUAL_TESTING_GUIDE.md`

## WERYFIKACJA

```bash
# Screenshot verification z nowym produktem
node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products/11018/edit" --tab=Warianty
```
