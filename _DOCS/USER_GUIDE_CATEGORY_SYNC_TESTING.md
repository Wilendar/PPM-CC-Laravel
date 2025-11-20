# Przewodnik Testowania Synchronizacji Kategorii

**Wersja**: 1.0
**Data**: 2025-11-18
**Status FIX #11**: ✅ Deployed to Production

---

## 📋 Co zostało naprawione?

**Problem**: Kategorie produktów NIE były synchronizowane z PPM do PrestaShop

**Rozwiązanie**: Naprawiono logikę detekcji zmian - system teraz prawidłowo wykrywa gdy kategorie się zmieniają i automatycznie je synchronizuje

**Status**: ✅ Fix został wdrożony i zweryfikowany testami technicznymi

---

## 🧪 Jak przetestować FIX w produkcji?

### KROK 1: Przygotowanie (jednorazowe)

**Hard Refresh przeglądarki:**
```
Windows: Ctrl + Shift + R
Mac: Cmd + Shift + R
```

**Dlaczego**: Wyczyść cache Livewire w przeglądarce

---

### KROK 2: Wczytaj kategorie z PrestaShop

**Gdzie**: Formularz produktu → TAB "Sklepy"

**Akcja**:
1. Otwórz dowolny produkt (np. ID 11033)
2. Kliknij TAB **"Sklepy"**
3. Wybierz sklep z listy (np. pitbike.pl)
4. Kliknij przycisk **"Wczytaj z aktualnego sklepu"**
5. Poczekaj na komunikat: "Dane zaktualizowane"

**Efekt**: System pobierze aktualne kategorie z PrestaShop i zapisze je w bazie PPM

**Kiedy to zrobić**:
- Dla nowych produktów (jednorazowo)
- Gdy chcesz zsynchronizować kategorie z PrestaShop → PPM

---

### KROK 3: Zmodyfikuj kategorie w PPM

**Gdzie**: Formularz produktu → TAB "Podstawowe"

**Akcja**:
1. Znajdź sekcję "Kategorie produktu"
2. **Dodaj** nową kategorię LUB **usuń** istniejącą
3. Zapisz produkt (Ctrl+S LUB kliknij "Zapisz")

**Efekt**: Produkt ma teraz INNE kategorie niż na PrestaShop

---

### KROK 4: Sprawdź badge oczekujących zmian

**Gdzie**: TAB "Sklepy" → Sklep dla którego zmieniono kategorie

**Co zobaczyć**:
```
Badge: "Oczekujące zmiany: Kategorie" (żółte tło)
```

**Jeśli NIE widzisz badge**:
- Problem: System nie wykrył zmian
- Akcja: Zgłoś błąd (dołącz ID produktu i ID sklepu)

---

### KROK 5: Synchronizuj do PrestaShop

**Gdzie**: TAB "Sklepy" → Sklep z oczekującymi zmianami

**Akcja**:
1. Kliknij przycisk **"Aktualizuj aktualny sklep"**
2. Poczekaj na komunikat potwierdzenia
3. Status sklepu powinien się zmienić na **"synchronized"**

**Efekt**: Kategorie zostały wysłane do PrestaShop

---

### KROK 6: Weryfikacja w PrestaShop Admin

**Gdzie**: PrestaShop Admin Panel

**Akcja**:
1. Zaloguj się do admin PrestaShop
2. Przejdź do: **Katalog → Produkty**
3. Znajdź produkt (użyj SKU do wyszukania)
4. Otwórz edycję produktu
5. Sprawdź sekcję **"Powiązane kategorie"**

**Co zobaczyć**:
- Kategorie w PrestaShop powinny być **IDENTYCZNE** jak w PPM
- Jeśli dodałeś kategorię X → X powinna być w PrestaShop
- Jeśli usunąłeś kategorię Y → Y NIE powinna być w PrestaShop

---

## ✅ Scenariusze Testowe

### Scenario A: Dodawanie Kategorii

**Stan początkowy**:
- Produkt ma kategorie: Części → Silnik → Tłoki

**Akcja w PPM**:
- Dodaj kategorię: Części → Układ chłodzenia → Chłodnice

**Synchronizacja**:
1. Badge: "Oczekujące zmiany: Kategorie" ✅
2. Kliknij "Aktualizuj aktualny sklep" ✅
3. Status: "synchronized" ✅

**Weryfikacja PrestaShop**:
- Kategorie: Części → Silnik → Tłoki, Części → Układ chłodzenia → Chłodnice ✅

---

### Scenario B: Usuwanie Kategorii

**Stan początkowy**:
- Produkt ma kategorie: A, B, C, D

**Akcja w PPM**:
- Usuń kategorię B

**Synchronizacja**:
1. Badge: "Oczekujące zmiany: Kategorie" ✅
2. Kliknij "Aktualizuj aktualny sklep" ✅

**Weryfikacja PrestaShop**:
- Kategorie: A, C, D (brak B) ✅

---

### Scenario C: Różne Kategorie Per Sklep

**Stan początkowy**:
- Sklep pitbike.pl: Kategorie [A, B]
- Sklep motovehicles.pl: Kategorie [C, D]

**Akcja w PPM**:
1. TAB Sklepy → pitbike.pl → Dodaj kategorię E
2. TAB Sklepy → motovehicles.pl → Dodaj kategorię F

**Synchronizacja**:
1. pitbike.pl → "Aktualizuj aktualny sklep"
2. motovehicles.pl → "Aktualizuj aktualny sklep"

**Weryfikacja**:
- pitbike.pl PrestaShop: [A, B, E] ✅
- motovehicles.pl PrestaShop: [C, D, F] ✅

**Potwierdzenie**: Różne sklepy mają różne kategorie ✅

---

### Scenario D: Pull z PrestaShop

**Stan początkowy**:
- Produkt w PPM: Kategorie [A, B]
- Produkt w PrestaShop: Kategorie [A, B, X] (ktoś dodał X bezpośrednio w PS)

**Akcja w PPM**:
1. TAB Sklepy → Wybierz sklep
2. Kliknij **"Wczytaj z aktualnego sklepu"**

**Efekt**:
- Badge: "Oczekujące zmiany: Kategorie" (wykrył różnicę) ✅
- System pokazuje że PrestaShop ma kategorię X której brak w PPM

**Opcje**:
- Zapisz produkt → zsynchronizuje PPM kategorie [A, B] do PrestaShop (nadpisze, usuwa X)
- LUB dodaj kategorię X w PPM → zachowasz X

---

## 🔄 Operacje Bulk (Wiele Produktów)

### Bulk Update: PPM → PrestaShop

**Gdzie**: Lista produktów

**Akcja**:
1. Zaznacz wiele produktów (checkbox)
2. Kliknij przycisk **"Aktualizuj sklepy"** (góra strony)
3. Wybierz sklepy do synchronizacji (modal)
4. Kliknij "Potwierdź"

**Efekt**:
- System utworzy job w kolejce dla każdego produktu
- Monitoring: Pasek postępu + licznik "X/Y zakończonych"
- Badge queue stats: "Jobs: X pending, Y processing"

**Weryfikacja**:
- Po zakończeniu: Status wszystkich produktów = "synchronized"
- Sprawdź losowy produkt w PrestaShop → kategorie poprawne ✅

---

### Bulk Pull: PrestaShop → PPM

**Gdzie**: Lista produktów

**Akcja**:
1. Zaznacz wiele produktów
2. Kliknij **"Wczytaj ze sklepów"**
3. Wybierz sklepy
4. Potwierdź

**Efekt**:
- System pobierze dane z PrestaShop dla wszystkich produktów
- Kategorie zostaną zaktualizowane w ProductShopData.category_mappings
- Badge "Oczekujące zmiany" pojawi się dla produktów z różnicami

---

## 🐛 Troubleshooting

### Problem 1: Badge "Oczekujące zmiany" nie pokazuje się

**Możliwe przyczyny**:
1. **category_mappings jest NULL** → Nie wczytano kategorii z PrestaShop
   - **Rozwiązanie**: Kliknij "Wczytaj z aktualnego sklepu"

2. **Kategorie są identyczne** → Brak zmian
   - **Weryfikacja**: Sprawdź TAB Sklepy → Kategorie (powinny być takie same jak w Podstawowe)

3. **Cache nie wyczyszczony** → Stare dane
   - **Rozwiązanie**: Hard refresh (Ctrl+Shift+R)

---

### Problem 2: Synchronizacja wykonuje się ale kategorie nie zmieniają się w PrestaShop

**Możliwe przyczyny**:
1. **Błąd PrestaShop API** → Sprawdź Laravel logs
   ```
   storage/logs/laravel.log
   Szukaj: "PrestaShopAPIException", "categories"
   ```

2. **Niepoprawne mapowanie kategorii** → Kategoria PPM nie ma odpowiednika w PrestaShop
   - **Rozwiązanie**: Sprawdź TAB Sklepy → Mapowania kategorii
   - Upewnij się że PPM kategorie mają przypisane PrestaShop kategorie

3. **PrestaShop permissions** → API key nie ma uprawnień do modyfikacji kategorii
   - **Rozwiązanie**: Sprawdź PrestaShop → Webservice → API key permissions

---

### Problem 3: Kategorie znikają po synchronizacji

**Przyczyna**: Prawdopodobnie category_mappings jest pusty lub NULL

**Diagnoza**:
1. Otwórz bazę danych PPM
2. Sprawdź tabelę `product_shop_data`
3. Znajdź rekord dla product_id + shop_id
4. Sprawdź kolumnę `category_mappings`

**Rozwiązanie**:
- Jeśli NULL → Kliknij "Wczytaj z aktualnego sklepu"
- Jeśli pusty array `[]` → Dodaj kategorie w PPM → Zapisz

---

### Problem 4: Bulk sync kończy się błędami

**Sprawdź**:
1. **Queue worker status** → Czy działa?
   ```bash
   php artisan queue:work --queue=default --tries=3
   ```

2. **Failed jobs** → Sprawdź tabelę `failed_jobs`
   ```sql
   SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
   ```

3. **Laravel logs** → Szczegóły błędów
   ```
   storage/logs/laravel.log
   ```

**Rozwiązanie**:
- Retry failed jobs: `php artisan queue:retry all`
- Jeśli błąd PrestaShop API → Sprawdź credentials sklepu
- Jeśli timeout → Zwiększ queue timeout w config

---

## 📊 Monitoring

### Queue Statistics (widoczne w UI)

**Gdzie**: Panel Admin → Badge "Queue Stats" (prawa górna)

**Metryki**:
- **Pending**: Jobs czekające na wykonanie
- **Processing**: Jobs aktualnie wykonywane
- **Failed**: Jobs zakończone błędem

**Normalne wartości**:
- Pending: 0-50 (zależy od bulk operations)
- Processing: 1-5 (zależy od worker count)
- Failed: 0 (ideally)

---

### Laravel Logs

**Lokalizacja**: `storage/logs/laravel.log`

**Frazy do szukania**:
```
"Checksum comparison" → Detekcja zmian
"needsSync" → Decyzja czy synchronizować
"category_mappings" → Shop-specific kategorie
"buildCategoryAssociations" → Transformation
"Product updated successfully" → Sukces synchronizacji
"PrestaShopAPIException" → Błędy API
```

**Przykład SUCCESS log**:
```
[2025-11-18] Checksum comparison: old=XXX, new=YYY, needs_sync=true
[2025-11-18] buildCategoryAssociations: 7 categories [9,15,800,981,983,985,2350]
[2025-11-18] Product updated successfully: prestashop_id=9752
```

---

## 📞 Zgłaszanie Błędów

**W przypadku problemów zgłoś**:

1. **ID produktu** i **ID sklepu**
2. **Kroki reprodukcji** (co dokładnie zrobiłeś?)
3. **Oczekiwany efekt** vs **Rzeczywisty efekt**
4. **Screenshot** formularza produktu (TAB Sklepy)
5. **Laravel logs** (ostatnie 50 linii z storage/logs/laravel.log)

**Opcjonalnie** (dla zaawansowanych):
6. Wynik query SQL:
   ```sql
   SELECT id, shop_id, category_mappings, sync_status, checksum
   FROM product_shop_data
   WHERE product_id = [PRODUCT_ID] AND shop_id = [SHOP_ID];
   ```

---

## ✅ Checklist Akceptacji FIX #11

- [ ] Hard refresh wykonany (Ctrl+Shift+R)
- [ ] Wczytano kategorie z PrestaShop ("Wczytaj z aktualnego sklepu")
- [ ] Dodano/usunięto kategorię w PPM
- [ ] Badge "Oczekujące zmiany: Kategorie" pojawił się ✅
- [ ] Kliknięto "Aktualizuj aktualny sklep"
- [ ] Status zmienił się na "synchronized"
- [ ] Weryfikacja w PrestaShop Admin: kategorie poprawne ✅
- [ ] Test bulk sync: 5+ produktów zsynchronizowanych ✅
- [ ] Test pull: kategorie z PrestaShop poprawnie wczytane ✅

**Jeśli WSZYSTKIE checkboxy ✅**: FIX #11 działa poprawnie w Twoim środowisku! 🎉

---

**Dokument stworzony**: 2025-11-18
**Wersja**: 1.0
**Autor**: Claude Code
**Status**: Production Ready
