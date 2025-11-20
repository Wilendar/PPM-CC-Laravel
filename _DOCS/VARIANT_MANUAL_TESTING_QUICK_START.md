# QUICK START: Manual Testing Wariantów Produktów

**Data:** 2025-11-06
**Czas:** ~20-25 minut
**Feature:** Phase 6 - Variant CRUD Operations

---

## ⚠️ KRYTYCZNA ZMIANA

**Product 10969 z oryginalnego guide NIE ISTNIEJE!**

**Użyj zamiast:**
- `TEST-CREATE-1762351961` (SKU)
- `KAYO150` (SKU)
- Lub dowolny inny produkt z listy produktów

---

## SZYBKI START (2 minuty)

### 1. Login
- URL: https://ppm.mpptrade.pl/login
- Email: `admin@mpptrade.pl`
- Password: `Admin123!MPP`

### 2. Wybierz Produkt
1. Menu: **Produkty** → **Lista produktów**
2. Kliknij **"Edytuj"** na dowolnym produkcie (np. KAYO150)
3. Przejdź do zakładki **"Warianty Produktu"**

### 3. Otwórz DevTools (F12)
- Zakładka **Console** (sprawdzanie błędów)
- Zostaw otwarte podczas testów

---

## 8 TESTÓW DO WYKONANIA

### ✅ TEST 1: Dodaj Wariant (2 min)

**Co robisz:**
1. Kliknij **"Dodaj Wariant"** (pomarańczowy przycisk)
2. Wypełnij:
   - SKU: `TEST-MANUAL-001`
   - Nazwa: `Test Wariant Manual`
   - ✅ Wariant aktywny
3. Kliknij **"Dodaj Wariant"**

**Co sprawdzasz:**
- [ ] Modal otwiera się płynnie
- [ ] Przycisk zmienia się na "Tworzenie..."
- [ ] Zielone powiadomienie pojawia się
- [ ] Modal zamyka się automatycznie
- [ ] Nowy wariant w tabeli
- [ ] **Brak czerwonych errorów w Console**

---

### ✅ TEST 2: Edytuj Wariant (2 min)

**Co robisz:**
1. Znajdź `TEST-MANUAL-001` w tabeli
2. Kliknij **"Edytuj"** (ołówek)
3. Zmień nazwę na: `Test Wariant Manual EDITED`
4. Kliknij **"Zapisz Zmiany"**

**Co sprawdzasz:**
- [ ] Modal otwiera się z wypełnionymi danymi
- [ ] Zmiana widoczna w tabeli po zapisie
- [ ] **Brak errorów**

---

### 🔴 TEST 3: Usuń Wariant (1 min)

**Co robisz:**
1. Znajdź `TEST-MANUAL-001`
2. Kliknij **"Usuń"** (kosz)
3. Kliknij **"Anuluj"** (wariant pozostaje)
4. Kliknij **"Usuń"** ponownie
5. Kliknij **"Tak, usuń"**

**Co sprawdzasz:**
- [ ] **Pojawia się dialog potwierdzenia** (CRITICAL!)
- [ ] Wariant znika z tabeli
- [ ] Smooth animation (nie instant zniknięcie)

---

### 🔴 TEST 4: Checkbox Persistence (1 min) **CRITICAL!**

**Co robisz:**
1. Otwórz produkt **BEZ wariantów**
2. Zakładka Warianty
3. **Zaznacz** "Konwertuj na produkt wariantowy"
4. Kliknij **"Zapisz produkt"** (główny save)
5. **Odśwież stronę (F5)**
6. Sprawdź czy checkbox **wciąż zaznaczony**

**Co sprawdzasz:**
- [ ] **Checkbox ✅ po reload** (MUST PASS!)
- [ ] Brak console errors

**Jeśli FAIL → BLOCKER! Screenshot + Console errors**

---

### ⚠️ TEST 5: Konwersja (2 min)

**Co robisz:**
1. Produkt bez wariantów
2. Zaznacz "Konwertuj..."
3. Kliknij "Konwertuj" button
4. Potwierdź w modalu
5. Sprawdź czy pojawił się wariant automatycznie

**Co sprawdzasz:**
- [ ] Modal konwersji zrozumiały
- [ ] 1 wariant utworzony automatycznie
- [ ] SKU wariantu = SKU produktu

---

### 💰 TEST 6: Ceny (3 min)

**Co robisz:**
1. Scroll do **"Ceny Wariantów"**
2. Znajdź swój wariant
3. Wypełnij ceny:
   - DETALICZNA: 100.00
   - DEALER STANDARD: 90.00
   - DEALER PREMIUM: 85.00
4. Kliknij **"Zapisz Ceny"**
5. **Odśwież stronę**
6. Sprawdź czy ceny pozostały

**Co sprawdzasz:**
- [ ] Grid czytelny
- [ ] Ceny persist po reload
- [ ] Success notification

---

### 📦 TEST 7: Stany (3 min)

**Co robisz:**
1. Scroll do **"Stany Magazynowe"**
2. Wypełnij:
   - MPPTRADE: 50
   - Pitbike.pl: 20
   - Cameraman: 10
3. Kliknij **"Zapisz Stany"**
4. Reload → verify

**Co sprawdzasz:**
- [ ] Stany persist
- [ ] Validation (nie akceptuje "-5")

---

### 📷 TEST 8: Zdjęcia (2 min)

**Co robisz:**
1. Scroll do **"Zdjęcia Wariantów"**
2. Kliknij **"Wybierz Pliki"**
3. Upload obraz (<5MB)
4. Sprawdź thumbnail
5. Kliknij **"Usuń"** (X)
6. Potwierdź

**Co sprawdzasz:**
- [ ] Upload progress widoczny
- [ ] Thumbnail pojawia się
- [ ] Delete działa

---

## WYNIKI - WYŚLIJ DO MNIE

```markdown
**Tester:** [Twoje imię]
**Data:** 2025-11-06
**Product:** [SKU użyty do testów]

### WYNIKI
- [ ] TEST 1: CREATE - PASS / FAIL
- [ ] TEST 2: EDIT - PASS / FAIL
- [ ] TEST 3: DELETE - PASS / FAIL
- [ ] TEST 4: CHECKBOX ⚠️ - PASS / FAIL
- [ ] TEST 5: CONVERSION - PASS / FAIL
- [ ] TEST 6: PRICES - PASS / FAIL
- [ ] TEST 7: STOCK - PASS / FAIL
- [ ] TEST 8: IMAGES - PASS / FAIL

**Status:** [X/8 PASSED]
**Console Errors:** [Paste lub "Brak"]
**Screenshots:** [Attach jeśli były błędy]

**Verdict:**
- [ ] ✅ Wszystko działa idealnie
- [ ] ⚠️ Minor issues (opisz)
- [ ] 🔴 Critical bugs (opisz)
```

---

## JEŚLI COKOLWIEK NIE DZIAŁA

1. **Screenshot** ekranu z błędem
2. **F12 → Console** → Copy czerwone errory
3. **F12 → Network** → Sprawdź failed requests (czerwone)
4. **Wyślij mi:** Screenshot + Console output + opis co robiłeś

---

## PO ZAKOŃCZENIU

Jeśli **wszystko PASS**:
- Odpowiedz: **"działa idealnie"**
- Przejdę do cleanup debug logging
- Phase 6 COMPLETED ✅

Jeśli **są błędy**:
- Wyślij wyniki
- Naprawię bugi
- Re-test failed scenarios

---

**Pytania?** Pisz na bieżąco podczas testów!

**Powodzenia! 🚀**
