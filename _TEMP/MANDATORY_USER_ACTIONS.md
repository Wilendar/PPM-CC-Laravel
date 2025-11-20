# ⚠️ OBOWIĄZKOWE KROKI TESTOWE

## KROK 1: HARD REFRESH (NAJWAŻNIEJSZE!)

**MUSISZ** załadować nowy kod Livewire!

**Windows/Linux:**
- **Ctrl + F5** (hard refresh)
- Lub: Ctrl + Shift + R
- Lub: F12 (DevTools) → klik prawym na Reload → "Empty Cache and Hard Reload"

**Mac:**
- **Cmd + Shift + R**

**LUB:**
- Zamknij kartę całkowicie
- Otwórz produkt w **nowej karcie/oknie**

---

## KROK 2: Sprawdź czy nowy kod załadowany

### 2a. Otwórz DevTools Console (F12)

Powinny być logi:
```
loadShopCategories: Loaded from product_shop_data.category_mappings
Shop categories loaded (Option A Architecture)
```

### 2b. Sprawdź Network

- Odśwież stronę (F5)
- W Network tab zobacz czy jest request do `/admin/products/11034/edit`
- Sprawdź timestamp - musi być AFTER deployment (po 12:xx dzisiaj)

---

## KROK 3: Test UI Kategorii

Po hard refresh otwórz produkt 11034:

1. **Shop Tab** → powinno pokazać **WSZYSTKIE 5 kategorii** (nie 2!)
2. Zmień kategorie
3. **"Zapisz zmiany"**
4. Sprawdź czy redirect działa (powinien wrócić do /admin/products)

---

## ⚠️ JEŚLI WCIĄŻ POKAZUJE 2 KATEGORIE:

Oznacza to że **cache przeglądarki** blokuje nowy kod!

**Drastic solution:**
1. Zamknij WSZYSTKIE karty z ppm.mpptrade.pl
2. Wyczyść cache przeglądarki:
   - Chrome: Ctrl+Shift+Delete → "Cached images and files"
   - Firefox: Ctrl+Shift+Delete → "Cache"
3. Otwórz nową kartę **Incognito/Private** (Ctrl+Shift+N)
4. Zaloguj się ponownie
5. Otwórz produkt 11034

---

## DEBUG: Sprawdź wersję kodu

W DevTools Console wpisz:
```javascript
document.querySelector('[wire\\:id]').getAttribute('wire:id')
```

Jeśli zwraca długi hash - to Livewire component jest załadowany.

---

**REMEMBER:** Bez hard refresh = stary kod = problem persists!
