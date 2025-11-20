# Quick Reference: Synchronizacja Kategorii

**FIX #11 Status**: ✅ Production (2025-11-18)

---

## 🚀 Fast Track Testing (5 minut)

### 1️⃣ PRZYGOTOWANIE
```
Ctrl + Shift + R (hard refresh)
```

### 2️⃣ WCZYTAJ KATEGORIE
```
Produkt → TAB "Sklepy" → Wybierz sklep → "Wczytaj z aktualnego sklepu"
```

### 3️⃣ ZMODYFIKUJ
```
TAB "Podstawowe" → Dodaj/usuń kategorię → Zapisz (Ctrl+S)
```

### 4️⃣ SPRAWDŹ BADGE
```
TAB "Sklepy" → Zobacz: "Oczekujące zmiany: Kategorie" ✅
```

### 5️⃣ SYNCHRONIZUJ
```
"Aktualizuj aktualny sklep" → Poczekaj → Status: "synchronized"
```

### 6️⃣ WERYFIKUJ
```
PrestaShop Admin → Znajdź produkt → Sprawdź kategorie ✅
```

---

## 📋 Operacje Bulk

**Update (PPM → PrestaShop)**:
```
Zaznacz produkty → "Aktualizuj sklepy" → Wybierz sklepy → Potwierdź
```

**Pull (PrestaShop → PPM)**:
```
Zaznacz produkty → "Wczytaj ze sklepów" → Wybierz sklepy → Potwierdź
```

---

## 🐛 Quick Troubleshooting

| Problem | Rozwiązanie |
|---------|------------|
| Brak badge "Oczekujące zmiany" | Kliknij "Wczytaj z aktualnego sklepu" |
| Kategorie nie synchronizują się | Sprawdź Laravel logs: `storage/logs/laravel.log` |
| Kategorie znikają po sync | Sprawdź DB: `category_mappings` w `product_shop_data` |
| Bulk sync błędy | Sprawdź queue: `failed_jobs` table |

---

## 📁 Pliki

**Pełny przewodnik**: `_DOCS/USER_GUIDE_CATEGORY_SYNC_TESTING.md`
**Raport techniczny**: `_AGENT_REPORTS/CRITICAL_FIX_categories_checksum_detection_bug_2025-11-18_REPORT.md`
**Test scripts**: `_TEMP/test_*.php`

---

## ✅ Akceptacja

- [ ] Badge "Oczekujące zmiany" działa
- [ ] Single sync działa (1 produkt)
- [ ] Bulk sync działa (5+ produktów)
- [ ] Pull z PrestaShop działa
- [ ] Weryfikacja w PrestaShop ✅

**Wszystkie ✅?** FIX działa! 🎉

---

**Version**: 1.0 | **Date**: 2025-11-18
