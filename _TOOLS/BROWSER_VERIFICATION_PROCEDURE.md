# BROWSER VERIFICATION PROCEDURE

**Status:** ✅ MANDATORY dla wszystkich zmian UI
**Version:** 1.0.0
**Last Updated:** 2025-11-06

---

## 🎯 KRYTYCZNA ZASADA

**⚠️ ZAWSZE wchodź na /admin/products/ NAJPIERW, a następnie wybieraj produkt z listy!**

**❌ BŁĄD:**
```
https://ppm.mpptrade.pl/admin/products/10969/edit  ← Direct link
```

**✅ POPRAWNIE:**
```
1. https://ppm.mpptrade.pl/admin/products/           ← Lista produktów
2. Klik na produkt z listy (np. SKU: TEST-AUTOFIX-1762422647)
3. ProductForm otwiera się automatycznie
4. Wykonaj weryfikację (screenshot, console, interakcje)
```

---

## 📋 PROCEDURA MANUALNA

### Krok 1: Przejdź do listy produktów
```
URL: https://ppm.mpptrade.pl/admin/products/
```

### Krok 2: Znajdź produkt do weryfikacji
- Użyj filtrów (SKU, nazwa, kategoria)
- Lub skorzystaj z wyszukiwarki
- Lub wybierz z listy

### Krok 3: Kliknij w produkt
- Kliknij w wiersz produktu (cała linia jest klikalna)
- ProductForm otwiera się w nowym widoku

### Krok 4: Wykonaj weryfikację
- Sprawdź console (F12) - 0 błędów
- Sprawdź Livewire init (console: "Livewire initialized")
- Sprawdź UI (layout, kolory, responsive)
- Przetestuj interakcje (TABy, formularze, modalne)

---

## 🤖 PROCEDURA AUTOMATYCZNA (full_console_test.cjs)

### Użycie z listą produktów (ZALECANE):

```bash
# Weryfikacja produktu przez listę (POPRAWNIE)
node _TOOLS/full_console_test.cjs \
  --list-first \
  --sku="TEST-AUTOFIX-1762422647" \
  --show

# Lub z domyślnym pierwszym produktem z listy
node _TOOLS/full_console_test.cjs --list-first --show
```

**Flow:**
1. Otwórz `/admin/products/` (lista)
2. Znajdź produkt po SKU (lub pierwszy z listy)
3. Kliknij w wiersz produktu
4. Czekaj na załadowanie ProductForm
5. Wykonaj weryfikację (screenshoty, console)

### Użycie z bezpośrednim linkiem (TYLKO DLA TESTÓW):

```bash
# Direct link (TYLKO gdy masz pewność że produkt istnieje)
node _TOOLS/full_console_test.cjs \
  "https://ppm.mpptrade.pl/admin/products/10969/edit" \
  --show

# ⚠️ WARNING: To omija workflow użytkownika!
```

---

## ❓ DLACZEGO TA ZASADA JEST KRYTYCZNA?

### 1. Realistyczny Workflow
- Użytkownicy NIGDY nie wchodzą bezpośrednio przez URL
- Zawsze przechodzą przez listę produktów
- Weryfikacja musi odzwierciedlać rzeczywisty workflow

### 2. Wykrycie Błędów Routingu
- Bezpośredni link omija middleware
- Lista produktów może mieć różne błędy (filtry, paginacja)
- Link w liście może być niepoprawny

### 3. Context Loading
- Lista produktów ładuje dane użytkownika (permissions, preferences)
- ProductForm może się inaczej zachować gdy otwarty z listy vs bezpośredni link
- Auto-load danych (np. PrestaShop data) może być różny

### 4. Lazy Loading & Eager Loading
- Lista produktów robi eager loading relacji (categories, shop_data)
- Direct link może robić lazy loading (N+1 queries)
- Performance różni się

---

## 🔧 KONFIGURACJA full_console_test.cjs

### Aktualizacja DEFAULT URL (FUTURE TASK):

```javascript
// ❌ STARE (CURRENT):
const url = args.find(...) || 'https://ppm.mpptrade.pl/admin/products/10969/edit';

// ✅ NOWE (PLANNED):
const url = args.find(...) || 'https://ppm.mpptrade.pl/admin/products/'; // Lista
const sku = args.find(arg => arg.startsWith('--sku='))?.split('=')[1] || null;
const listFirst = args.includes('--list-first');

if (listFirst) {
  // 1. Otwórz listę
  await page.goto(url);

  // 2. Znajdź produkt po SKU (jeśli podany)
  if (sku) {
    const productRow = await page.locator(`tr:has-text("${sku}")`).first();
    await productRow.click();
  } else {
    // 3. Klik w pierwszy produkt
    const firstRow = await page.locator('table tbody tr').first();
    await firstRow.click();
  }

  // 4. Czekaj na ProductForm
  await page.waitForSelector('[wire\\:id]');
}
```

---

## 📊 VERIFICATION CHECKLIST

Przed zakończeniem weryfikacji sprawdź:

- [ ] ✅ URL zaczyna się od `/admin/products/` (lista)
- [ ] ✅ Produkt wybrany z listy (nie direct link)
- [ ] ✅ ProductForm załadowany (wire:id visible)
- [ ] ✅ Console: 0 błędów (czerwonych)
- [ ] ✅ Livewire initialized (console log)
- [ ] ✅ No failed HTTP requests (network tab)
- [ ] ✅ UI renders correctly (screenshot review)
- [ ] ✅ TABy działają (click test)
- [ ] ✅ Formularze działają (input test)
- [ ] ✅ Modalne działają (open/close test)

---

## 🚨 COMMON MISTAKES

### Mistake 1: Direct Link w automatyzacji
```bash
# ❌ WRONG
node _TOOLS/full_console_test.cjs \
  "https://ppm.mpptrade.pl/admin/products/10969/edit"
```
**Problem:** Omija workflow użytkownika

### Mistake 2: Hardcoded Product ID
```bash
# ❌ WRONG
const url = 'https://ppm.mpptrade.pl/admin/products/10969/edit';
```
**Problem:** Produkt ID może nie istnieć w innym środowisku

### Mistake 3: Brak weryfikacji listy
```bash
# ❌ WRONG: Skip list, go straight to ProductForm
```
**Problem:** Nie wykrywa błędów na liście produktów

---

## 📚 RELATED DOCS

- [frontend-dev-guidelines](.claude/skills/guidelines/frontend-dev-guidelines/SKILL.md)
- [TROUBLESHOOTING.md](_DOCS/TROUBLESHOOTING.md)
- [PROJECT_KNOWLEDGE.md](_DOCS/PROJECT_KNOWLEDGE.md)

---

**Skill Version:** 1.0.0
**Last Updated:** 2025-11-06
**Maintainer:** PPM Development Team
**Zero Tolerance:** Direct links w weryfikacji WILL be rejected
