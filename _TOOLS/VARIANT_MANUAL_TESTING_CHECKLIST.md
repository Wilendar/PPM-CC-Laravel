# VARIANT CRUD + CHECKBOX PERSISTENCE - MANUAL TESTING CHECKLIST

**Date:** 2025-11-12
**Tester:** [Imię użytkownika]
**Environment:** Production (ppm.mpptrade.pl)
**Test Duration:** ~10 min
**Purpose:** Comprehensive verification of Variant CRUD operations + Checkbox persistence

---

## PRE-TEST SETUP

### 1. Przygotuj produkt testowy
- [ ] Navigate: `https://ppm.mpptrade.pl/admin/products/11018/edit`
- [ ] TAB: Kliknij zakładkę "Warianty"
- [ ] Verify: Produkt ma checkbox "Produkt ma warianty" (visible)
- [ ] Verify: DevTools Console open (F12 → Console tab) - monitoruj błędy przez cały test

**Screenshot Tool:** Run before starting tests:
```bash
node _TOOLS/full_console_test.cjs 'https://ppm.mpptrade.pl/admin/products/11018/edit' --tab=Warianty --verify-variants
```

---

## TEST SCENARIO #1: Checkbox Check → Add Variant

**Goal:** Sprawdź czy zaznaczenie checkbox + dodanie wariantu działa poprawnie

### Steps:
1. [ ] **Initial State:** Jeśli checkbox "Produkt ma warianty" jest zaznaczony, odznacz go
2. [ ] **Save Product:** Kliknij "Zapisz produkt" (button u dołu formularza)
3. [ ] **Wait:** Poczekaj na przeładowanie strony
4. [ ] **Verify:** Checkbox "Produkt ma warianty" jest ODZNACZONY ✅
5. [ ] **Verify:** Brak wariantów w tabeli ✅
6. [ ] **Check Checkbox:** Zaznacz checkbox "Produkt ma warianty"
7. [ ] **Verify:** Pojawia się przycisk "Dodaj wariant" ✅
8. [ ] **Add Variant:** Kliknij "Dodaj wariant"
9. [ ] **Wait:** Modal "Dodaj wariant" się otwiera ✅
10. [ ] **Fill Form:**
    - Nazwa wariantu: "Test Variant A"
    - SKU: "TEST-VAR-A-001"
    - Cena bazowa: 100
    - Stan magazynowy (MPPTRADE): 10
11. [ ] **Save Variant:** Kliknij "Zapisz wariant" w modalu
12. [ ] **Wait:** Modal się zamyka
13. [ ] **Verify:** Wariant "Test Variant A" widoczny w tabeli ✅
14. [ ] **Verify:** Checkbox "Produkt ma warianty" NADAL zaznaczony ✅
15. [ ] **Save Product:** Kliknij "Zapisz produkt"
16. [ ] **Wait:** Przeładowanie strony
17. [ ] **Verify:** Checkbox "Produkt ma warianty" ZAZNACZONY po reload ✅
18. [ ] **Verify:** Wariant "Test Variant A" NADAL widoczny w tabeli ✅
19. [ ] **Console Check:** ZERO errors w konsoli DevTools ✅

**Expected Result:**
- ✅ Checkbox remains checked after adding variant + save
- ✅ Variant persists after page reload
- ✅ No console errors (JavaScript, Livewire, Network)
- ✅ "Dodaj wariant" button visible when checkbox checked

**Time:** ~2 min

---

## TEST SCENARIO #2: Checkbox Uncheck → Conversion Modal

**Goal:** Sprawdź czy odznaczenie checkbox pokazuje modal konwersji (jeśli są warianty)

### Steps:
1. [ ] **Initial State:** Produkt ma warianty (checkbox zaznaczony, wariant "Test Variant A" widoczny)
2. [ ] **Uncheck:** Odznacz checkbox "Produkt ma warianty"
3. [ ] **Wait:** Modal "Konwersja na produkt pojedynczy" pojawia się ✅
4. [ ] **Verify Modal Content:**
    - Tytuł: "Konwersja na produkt pojedynczy"
    - Opis: Ostrzeżenie o usunięciu wariantów
    - Opcje: "Zachowaj jako produkt pojedynczy" + "Anuluj"
5. [ ] **Test Cancel:** Kliknij "Anuluj"
6. [ ] **Verify:** Modal się zamyka ✅
7. [ ] **Verify:** Checkbox AUTOMATYCZNIE zaznaczony ponownie ✅
8. [ ] **Verify:** Warianty NADAL widoczne ✅
9. [ ] **Uncheck Again:** Odznacz checkbox ponownie
10. [ ] **Confirm Delete:** Kliknij "Zachowaj jako produkt pojedynczy" w modalu
11. [ ] **Wait:** Modal się zamyka
12. [ ] **Verify:** Warianty USUNIĘTE z tabeli ✅
13. [ ] **Verify:** Checkbox ODZNACZONY ✅
14. [ ] **Verify:** Przycisk "Dodaj wariant" NIEWIDOCZNY ✅
15. [ ] **Save Product:** Kliknij "Zapisz produkt"
16. [ ] **Wait:** Przeładowanie strony
17. [ ] **Verify:** Checkbox ODZNACZONY po reload ✅
18. [ ] **Verify:** Brak wariantów w tabeli ✅
19. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Modal appears when unchecking checkbox (if variants exist)
- ✅ "Anuluj" re-checks checkbox and keeps variants
- ✅ "Potwierdź" deletes variants and unchecks checkbox
- ✅ Checkbox state persists after page reload
- ✅ No orphan variants in database

**Time:** ~2 min

---

## TEST SCENARIO #3: Edit Variant

**Goal:** Sprawdź czy edycja wariantu działa poprawnie + persists after reload

### Steps (SETUP: Add variant first if deleted in Scenario #2):
1. [ ] **Setup:** Zaznacz checkbox, dodaj wariant "Test Variant A" (jeśli usunięty w Scenario #2)
2. [ ] **Verify:** Wariant "Test Variant A" widoczny w tabeli
3. [ ] **Edit:** Kliknij przycisk "Edytuj" (ikona ołówka) przy wariancie
4. [ ] **Wait:** Modal "Edytuj wariant" się otwiera ✅
5. [ ] **Verify Modal Prefill:**
    - Nazwa: "Test Variant A"
    - SKU: "TEST-VAR-A-001"
    - Cena: 100
6. [ ] **Change Data:**
    - Nazwa: "Test Variant A - Edited"
    - Cena bazowa: 150
7. [ ] **Save Changes:** Kliknij "Zapisz zmiany"
8. [ ] **Wait:** Modal się zamyka
9. [ ] **Verify Table Update:**
    - Nazwa: "Test Variant A - Edited" ✅
    - Cena: 150 ✅
10. [ ] **Save Product:** Kliknij "Zapisz produkt"
11. [ ] **Wait:** Przeładowanie strony
12. [ ] **Verify Persistence:**
    - Nazwa: "Test Variant A - Edited" ✅
    - Cena: 150 ✅
13. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Edit modal prefills with existing data
- ✅ Changes visible immediately in table
- ✅ Changes persist after page reload
- ✅ No console errors

**Time:** ~1.5 min

---

## TEST SCENARIO #4: Delete Variant (Last Variant)

**Goal:** Sprawdź czy usunięcie ostatniego wariantu auto-unchecks checkbox

### Steps:
1. [ ] **Verify:** Produkt ma DOKŁADNIE 1 wariant ("Test Variant A - Edited")
2. [ ] **Verify:** Checkbox "Produkt ma warianty" ZAZNACZONY
3. [ ] **Delete:** Kliknij przycisk "Usuń" (ikona kosza) przy wariancie
4. [ ] **Wait:** Confirm dialog pojawia się
5. [ ] **Confirm:** Potwierdź usunięcie
6. [ ] **Wait:** Wariant znika z tabeli
7. [ ] **Verify:** Checkbox "Produkt ma warianty" AUTOMATYCZNIE odznaczony ✅
8. [ ] **Verify:** Tabela wariantów pusta ✅
9. [ ] **Verify:** Przycisk "Dodaj wariant" NIEWIDOCZNY ✅
10. [ ] **Save Product:** Kliknij "Zapisz produkt"
11. [ ] **Wait:** Przeładowanie strony
12. [ ] **Verify:** Checkbox ODZNACZONY po reload ✅
13. [ ] **Verify:** Brak wariantów w tabeli ✅
14. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Deleting last variant auto-unchecks checkbox
- ✅ "Dodaj wariant" button hidden
- ✅ Checkbox state persists after reload
- ✅ No orphan data in database

**Time:** ~1 min

---

## TEST SCENARIO #5: Multiple Variants

**Goal:** Sprawdź czy dodanie wielu wariantów działa + checkbox remains checked

### Steps:
1. [ ] **Setup:** Zaznacz checkbox "Produkt ma warianty"
2. [ ] **Add Variant B:**
    - Kliknij "Dodaj wariant"
    - Nazwa: "Test Variant B"
    - SKU: "TEST-VAR-B-001"
    - Cena: 200
    - Stan: 20
    - Zapisz
3. [ ] **Verify:** Wariant B widoczny w tabeli ✅
4. [ ] **Add Variant C:**
    - Kliknij "Dodaj wariant"
    - Nazwa: "Test Variant C"
    - SKU: "TEST-VAR-C-001"
    - Cena: 300
    - Stan: 30
    - Zapisz
5. [ ] **Verify:** Wariant C widoczny w tabeli ✅
6. [ ] **Verify:** Tabela pokazuje 2 warianty (B + C) ✅
7. [ ] **Verify:** Checkbox ZAZNACZONY ✅
8. [ ] **Save Product:** Kliknij "Zapisz produkt"
9. [ ] **Wait:** Przeładowanie strony
10. [ ] **Verify:** 2 warianty (B + C) PERSIST po reload ✅
11. [ ] **Verify:** Checkbox ZAZNACZONY po reload ✅
12. [ ] **Delete Variant B:** Usuń wariant B (potwierdź)
13. [ ] **Verify:** Tylko wariant C widoczny ✅
14. [ ] **Verify:** Checkbox NADAL ZAZNACZONY (bo pozostał 1 wariant) ✅
15. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Multiple variants can be added
- ✅ All variants persist after page reload
- ✅ Checkbox remains checked if at least 1 variant exists
- ✅ Deleting one variant doesn't affect others or checkbox (unless last)

**Time:** ~2 min

---

## TEST SCENARIO #6: SKU Uniqueness Validation

**Goal:** Sprawdź czy walidacja SKU działa (duplicate SKU rejected)

### Steps (assuming Variant C exists with SKU "TEST-VAR-C-001"):
1. [ ] **Add Variant:** Kliknij "Dodaj wariant"
2. [ ] **Fill Form (duplicate SKU):**
    - Nazwa: "Test Variant D"
    - SKU: "TEST-VAR-C-001" (DUPLICATE!)
    - Cena: 400
3. [ ] **Save:** Kliknij "Zapisz wariant"
4. [ ] **Verify Error:**
    - Modal NIE ZAMYKA SIĘ ✅
    - Error message: "SKU already exists" lub podobny ✅
    - Pole SKU podświetlone na czerwono ✅
5. [ ] **Fix SKU:**
    - Zmień SKU na: "TEST-VAR-D-001" (unique)
6. [ ] **Save:** Kliknij "Zapisz wariant"
7. [ ] **Verify Success:**
    - Modal się ZAMYKA ✅
    - Wariant D widoczny w tabeli ✅
8. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Duplicate SKU rejected with clear error message
- ✅ Unique SKU accepted
- ✅ Validation works immediately (no page reload needed)

**Time:** ~1 min

---

## TEST SCENARIO #7: Prices & Stock Per Warehouse

**Goal:** Sprawdź czy ceny i stany magazynowe per warehouse działają + persist

### Steps (assuming Variant D exists):
1. [ ] **Edit Variant D:** Kliknij "Edytuj" przy wariancie D
2. [ ] **Check Sections:**
    - Sekcja "Ceny" widoczna ✅
    - Sekcja "Stany magazynowe" widoczna ✅
3. [ ] **Set Prices:**
    - Detaliczna: 100
    - Dealer Standard: 90
    - Dealer Premium: 80
4. [ ] **Set Stock:**
    - MPPTRADE: 50
    - Pitbike.pl: 20
    - Cameraman: 10
5. [ ] **Save Variant:** Kliknij "Zapisz zmiany"
6. [ ] **Verify:** Modal się zamyka ✅
7. [ ] **Verify Table:** Ceny i stany widoczne w tabeli (jeśli wyświetlane) ✅
8. [ ] **Save Product:** Kliknij "Zapisz produkt"
9. [ ] **Wait:** Przeładowanie strony
10. [ ] **Edit Variant D Again:** Kliknij "Edytuj"
11. [ ] **Verify Persistence:**
    - Detaliczna: 100 ✅
    - Dealer Standard: 90 ✅
    - MPPTRADE: 50 ✅
    - Pitbike.pl: 20 ✅
12. [ ] **Console Check:** ZERO errors ✅

**Expected Result:**
- ✅ Prices per group saved correctly
- ✅ Stock per warehouse saved correctly
- ✅ Data persists after page reload
- ✅ No data loss or corruption

**Time:** ~1.5 min

---

## TEST SCENARIO #8: Console Error Check (Comprehensive)

**Goal:** Sprawdź czy ZERO błędów w konsoli przeglądarki przez WSZYSTKIE scenariusze

### Steps:
1. [ ] **DevTools:** F12 → Console tab OPEN przez CAŁY test
2. [ ] **Monitor:** Śledź konsole podczas wykonywania Scenarios #1-#7
3. [ ] **Check for:**
    - ❌ JavaScript errors (red)
    - ⚠️ Livewire warnings (yellow) - especially "wire:snapshot"
    - ❌ Network errors (404, 500) w zakładce Network
    - ❌ Failed AJAX requests
4. [ ] **Verify:** ZERO critical errors ✅
5. [ ] **Verify:** ZERO "wire:snapshot" raw text visible on page ✅

**Expected Result:**
- ✅ No JavaScript errors
- ✅ No Livewire errors (wire:snapshot, wire:id missing, etc.)
- ✅ No network errors (all requests return 200/302)
- ✅ No failed Livewire component updates

**Time:** Continuous monitoring (0 min - parallel with other scenarios)

---

## POST-TEST CLEANUP

### Usuń produkty testowe:
1. [ ] **Delete All Test Variants:** Usuń wszystkie warianty utworzone podczas testów (C, D)
2. [ ] **Uncheck Checkbox:** Odznacz "Produkt ma warianty"
3. [ ] **Save Product:** Kliknij "Zapisz produkt"
4. [ ] **Verify:** Produkt wrócił do stanu początkowego (no variants, checkbox unchecked)

**Time:** ~1 min

---

## AUTOMATED VERIFICATION (OPTIONAL)

**Run console verification tool:**
```bash
node _TOOLS/full_console_test.cjs 'https://ppm.mpptrade.pl/admin/products/11018/edit' --tab=Warianty --verify-variants
```

**Capture screenshots:**
```bash
node _TOOLS/screenshot_variant_test.cjs 11018
```

**Check screenshots in:** `_TOOLS/screenshots/variant_test_*.png`

---

## TEST SUMMARY

**Test Completed:** [Data i czas, np. 2025-11-12 10:30]
**Duration:** [X min]
**Scenarios Passed:** [X / 8]
**Scenarios Failed:** [X / 8]

### Detailed Results:

| Scenario | Status | Notes |
|----------|--------|-------|
| #1: Checkbox Check → Add Variant | ✅/❌ | [Opis problemu jeśli fail] |
| #2: Checkbox Uncheck → Conversion Modal | ✅/❌ | [Opis problemu jeśli fail] |
| #3: Edit Variant | ✅/❌ | [Opis problemu jeśli fail] |
| #4: Delete Variant (Last) | ✅/❌ | [Opis problemu jeśli fail] |
| #5: Multiple Variants | ✅/❌ | [Opis problemu jeśli fail] |
| #6: SKU Uniqueness Validation | ✅/❌ | [Opis problemu jeśli fail] |
| #7: Prices & Stock Per Warehouse | ✅/❌ | [Opis problemu jeśli fail] |
| #8: Console Error Check | ✅/❌ | [Opis problemu jeśli fail] |

### Failed Scenarios (if any):

**Scenario #X - [Opis problemu]:**
- **Steps to reproduce:** [Kroki 1, 2, 3...]
- **Expected:** [Co powinno się stać]
- **Actual:** [Co się stało]
- **Screenshot:** `_TOOLS/screenshots/failure_X_[timestamp].png`

### Console Errors (if any):

```
[Paste console errors here]
```

### Screenshots:

- `_TOOLS/screenshots/variant_test_01_initial_[timestamp].png`
- `_TOOLS/screenshots/variant_test_02_tab_[timestamp].png`
- `_TOOLS/screenshots/variant_test_03_checkbox_[timestamp].png`

---

## NEXT STEPS

### If ALL scenarios PASS:
- [ ] ✅ Report success to developer
- [ ] ✅ Approve debug log cleanup (remove `Log::debug()` statements)
- [ ] ✅ Mark Variant CRUD feature as COMPLETE

### If ANY scenario FAILS:
- [ ] ❌ Create bug report with detailed steps + screenshots
- [ ] ❌ Provide console errors and network logs
- [ ] ❌ Developer fixes issue
- [ ] ❌ Re-run this checklist

---

## NOTES & OBSERVATIONS

[Space for tester to add any observations, suggestions, or issues not covered by scenarios]

**Examples:**
- "Modal animation too slow (1s delay)"
- "Delete button too close to Edit button - risk of accidental clicks"
- "SKU validation error message hard to read (small font)"

---

**Checklist Version:** 1.0
**Last Updated:** 2025-11-12
**Maintained By:** frontend-specialist agent
