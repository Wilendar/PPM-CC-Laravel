---
name: "product-form-category-test"
description: "Automatyczne testowanie workflow kategorii w ProductForm (sklep B2B Test DEV)"
---

# Product Form Category Test Skill

## 🎯 Overview

Skill do **automatycznego testowania workflow kategorii** w PPM ProductForm (sklep "B2B Test DEV").

**Co testuje (8-step workflow):**
1. ✅ Bezpośrednie otwarcie produktu (https://ppm.mpptrade.pl/admin/products/11034/edit)
2. ✅ Kliknięcie shop tab "B2B Test DEV"
3. ✅ Przewinięcie do sekcji kategorii (z timeout dla danych PrestaShop)
4. ✅ Zaznaczenie kategorii (wywołanie zmian)
5. ✅ **KRYTYCZNY**: Kliknięcie "Zapisz zmiany" → redirect na `/admin/products`
6. ✅ Z listy produktów: wejście na produkt (bezpośredni link lub kliknięcie)
7. ✅ Powtórzenie kroków 2-3 i weryfikacja checkboxów kategorii
8. ✅ Sprawdzenie logów Laravel i bazy danych PPM

**Dlaczego ten skill:**
- Automatyzuje powtarzalny proces testowania
- Wykrywa problemy z redirect po zapisie
- Weryfikuje persistencję danych kategorii
- Zbiera logi i dane DB do diagnozy

---

## 🚀 Kiedy używać tego Skilla

Użyj `product-form-category-test` gdy:
- ✅ Zmieniasz logikę zapisu kategorii w ProductForm
- ✅ Modyfikujesz workflow sklepów (shop tab switching)
- ✅ Debugujesz problemy z redirect po save
- ✅ Testujesz integrację Livewire + PrestaShop categories
- ✅ Weryfikujesz fix dla category persistence bugs
- ✅ Przed deployment zmian w ProductForm
- ✅ Po refactoringu ProductFormSaver lub ProductCategoryManager

---

## 📋 INSTRUKCJE GŁÓWNE

### FAZA 1: PRZYGOTOWANIE ŚRODOWISKA

#### 1.1 Walidacja Warunków Wstępnych
```markdown
SPRAWDŹ:
✅ Node.js zainstalowany (node --version)
✅ Playwright zainstalowany (npm list playwright)
✅ SSH dostęp do produkcji (plink test)
✅ Produkcja działa (curl https://ppm.mpptrade.pl)
✅ Produkt 11034 istnieje w DB
✅ Sklep "B2B Test DEV" aktywny
```

**Jeśli brak Playwright:**
```bash
npm install --save-dev playwright
npx playwright install chromium
```

#### 1.2 Przygotowanie Narzędzia Testowego
```markdown
Skill automatycznie użyje:
- Skrypt: `.claude/skills/product-form-category-test/test_workflow.cjs`
- Lokalizacja screenshots: `_TOOLS/screenshots/category_test_*.png`
- Logfile: `_TOOLS/screenshots/category_test_results.txt`
```

---

### FAZA 2: WYKONANIE TESTU E2E

#### 2.1 Uruchomienie Testu
```powershell
# AUTOMATYCZNE URUCHOMIENIE (przez skill)
node .claude/skills/product-form-category-test/test_workflow.cjs

# OPCJONALNIE: Manual run z debug
node .claude/skills/product-form-category-test/test_workflow.cjs --show --slow
```

**Parametry:**
- `--show` - Pokaż okno przeglądarki (default: headless)
- `--slow` - Wolniejsze wykonanie (slowMo: 1000ms vs 500ms)
- `--no-save` - Nie klikaj "Zapisz zmiany" (tylko test UI)

#### 2.2 Workflow Testu (Automatyczny - 8 kroków)

**KROK 1: Bezpośrednie Otwarcie Produktu 11034**
```javascript
// Bezpośredni link (zakłada że użytkownik już zalogowany)
await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
await page.waitForSelector('[wire\\:id]', { timeout: 10000 });
await page.waitForTimeout(2000); // Livewire init

VERIFY: ✅ ProductForm załadowany
SCREENSHOT: category_test_01_product_loaded.png
```

**KROK 2: Kliknięcie Shop Tab "B2B Test DEV"**
```javascript
const shopTab = page.locator('button:has-text("B2B Test DEV")').first();
await shopTab.click();

// KRYTYCZNE: Wait for PrestaShop data loading
await page.waitForTimeout(3000); // PrestaShop API może mieć delay

VERIFY: ✅ Shop tab aktywny (sprawdź klasę .active lub aria-selected)
SCREENSHOT: category_test_02_shop_tab_clicked.png
```

**KROK 3: Przewiń do Sekcji Kategorii**
```javascript
// Po załadowaniu danych PrestaShop (opóźnienie możliwe)
const categoriesSection = page.locator('section:has-text("Kategorie")').first();
await categoriesSection.scrollIntoViewIfNeeded();

VERIFY: ✅ Sekcja kategorii widoczna
SCREENSHOT: category_test_03_categories_section.png
```

**KROK 4: Zaznacz Kategorie (Wywołaj Zmiany)**
```javascript
// Zaznacz jakieś kategorie aby wywołać zmiany
const firstCheckbox = page.locator('input[type="checkbox"][wire\\:model*="shopCategories"]').first();

const wasChecked = await firstCheckbox.isChecked();
await firstCheckbox.click();

const nowChecked = await firstCheckbox.isChecked();

VERIFY: ✅ Kategoria zaznaczona/odznaczona (wasChecked !== nowChecked)
LOG: "Category toggled: ${wasChecked} → ${nowChecked}"
SCREENSHOT: category_test_04_category_changed.png
```

**KROK 5: Kliknięcie "Zapisz zmiany" (KRYTYCZNY TEST)**
```javascript
const saveButton = page.locator('button:has-text("Zapisz zmiany")').first();
await saveButton.click();

// KRYTYCZNY MOMENT: Oczekuj redirect na /admin/products
try {
    await page.waitForURL('**/admin/products', { timeout: 10000 });

    VERIFY: ✅✅✅ REDIRECT SUKCES! - Został przeniesiony na /admin/products
    LOG: "✅ CRITICAL: Redirect to /admin/products SUCCESS"

} catch (error) {
    VERIFY: ❌❌❌ REDIRECT FAILED! - Nie został przeniesiony
    LOG: "❌ CRITICAL: Redirect to /admin/products FAILED"
    LOG: `Current URL: ${page.url()}`
    SCREENSHOT: category_test_ERROR_no_redirect.png

    // To jest BLOCKER - test nie może kontynuować
    throw new Error('CRITICAL: Redirect failed - test cannot continue');
}

SCREENSHOT: category_test_05_redirect_success.png
```

**KROK 6: Z Listy Produktów - Wejście na Produkt**
```javascript
// OPCJA A: Bezpośredni link (najprostsze)
await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');

// LUB OPCJA B: Kliknięcie w link produktu z listy
const productLink = page.locator('a:has-text("Q-KAYO-EA70")')
    .or(page.locator('a[href*="/11034/edit"]'))
    .first();
await productLink.click();

await page.waitForSelector('[wire\\:id]', { timeout: 10000 });
await page.waitForTimeout(2000);

VERIFY: ✅ Produkt ponownie otwarty
SCREENSHOT: category_test_06_product_reopened.png
```

**KROK 7: Powtórzenie Kroków 2-3 i Weryfikacja Checkboxów**
```javascript
// Kliknij ponownie tab "B2B Test DEV"
const shopTab2 = page.locator('button:has-text("B2B Test DEV")').first();
await shopTab2.click();
await page.waitForTimeout(3000); // PrestaShop data loading

// Przewiń do sekcji kategorii
const categoriesSection2 = page.locator('section:has-text("Kategorie")').first();
await categoriesSection2.scrollIntoViewIfNeeded();

// Sprawdź czy checkbox jest w oczekiwanym stanie (czy zmiany się zapisały)
const firstCheckbox2 = page.locator('input[type="checkbox"][wire\\:model*="shopCategories"]').first();
const currentState = await firstCheckbox2.isChecked();

VERIFY:
if (currentState === nowChecked) {
    ✅✅✅ PERSISTENCJA SUKCES! - Zmiany zostały zapisane
    LOG: "✅ PERSISTENCE: Category state persisted correctly (${currentState})"
} else {
    ❌❌❌ PERSISTENCJA FAILED! - Zmiany NIE zostały zapisane
    LOG: "❌ PERSISTENCE FAILED: Expected ${nowChecked}, got ${currentState}"
}

SCREENSHOT: category_test_07_verification.png
```

**KROK 8: Sprawdzenie Logów Laravel i Bazy Danych**
```powershell
# Zobacz FAZA 3 poniżej dla szczegółów weryfikacji logów i DB
```

---

### FAZA 3: WERYFIKACJA LOGÓW I BAZY DANYCH (KROK 8)

#### 3.1 Sprawdzenie Logów Laravel
```powershell
# SSH do produkcji - ostatnie 50 linii logów
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && tail -n 50 storage/logs/laravel.log"

SZUKAJ:
✅ "ProductFormSaver: Saving product 11034"
✅ "ProductCategoryManager: Syncing categories for shop"
✅ "Categories saved successfully"

❌ ERROR patterns:
- "Undefined method"
- "SQLSTATE"
- "Call to undefined"
- "wire:snapshot"
```

**Output do pliku:**
```powershell
plink ... > _TOOLS/screenshots/category_test_laravel_logs.txt
```

#### 3.2 Weryfikacja Bazy Danych
```powershell
# Utwórz skrypt weryfikacyjny
cat > _TEMP/verify_category_save.php << 'EOF'
<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;

$product = Product::find(11034);
$shopData = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1) // B2B Test DEV
    ->first();

echo "=== PRODUCT 11034 CATEGORY VERIFICATION ===\n\n";

if ($shopData) {
    echo "✅ ProductShopData found (ID: {$shopData->id})\n";
    echo "Shop ID: {$shopData->shop_id}\n";

    // Categories (JSON field)
    $categories = $shopData->categories ?? [];
    echo "\nCategories (" . count($categories) . " total):\n";
    foreach ($categories as $catId) {
        echo "  - Category ID: $catId\n";
    }

    // Primary category
    echo "\nPrimary Category ID: " . ($shopData->primary_category_id ?? 'NULL') . "\n";

    // Updated timestamp
    echo "\nLast Updated: {$shopData->updated_at}\n";

} else {
    echo "❌ ProductShopData NOT FOUND for product 11034, shop 1\n";
}

echo "\n=== END ===\n";
EOF

# Upload i wykonaj
pscp -i $HostidoKey -P 64321 "_TEMP/verify_category_save.php" host379076@...:domains/.../verify_category_save.php

plink ... -batch "cd domains/... && php verify_category_save.php"
```

**Output do pliku:**
```powershell
plink ... > _TOOLS/screenshots/category_test_db_verification.txt
```

---

### FAZA 4: ANALIZA WYNIKÓW I RAPORTOWANIE

#### 4.1 Generowanie Raportu
```markdown
# CATEGORY TEST REPORT - [Data]

## 🎯 Test Execution Summary

**Product:** 11034 (SKU: Q-KAYO-EA70)
**Shop:** B2B Test DEV (ID: 1)
**Timestamp:** [YYYY-MM-DD HH:MM:SS]

---

## ✅ TEST RESULTS

### Phase 1: UI Navigation
- [✅/❌] Product loaded correctly
- [✅/❌] Shop tab "B2B Test DEV" clicked
- [✅/❌] Categories section visible
- [✅/❌] Category checkbox toggled

### Phase 2: Save & Redirect (**CRITICAL**)
- [✅/❌] "Zapisz zmiany" button clicked
- [✅/❌] **Redirect to /admin/products** (BLOCKER if failed)
- [✅/❌] Product search by SKU
- [✅/❌] Product reopened

### Phase 3: Persistence Verification
- [✅/❌] Category state matches expected
- [✅/❌] **Changes persisted in database**

### Phase 4: Logs & Database
- [✅/❌] Laravel logs clean (no errors)
- [✅/❌] ProductShopData updated in DB
- [✅/❌] Categories JSON field correct
- [✅/❌] Updated timestamp fresh

---

## 📸 Screenshots

1. `category_test_01_product_loaded.png` - Initial state
2. `category_test_02_shop_tab_clicked.png` - After shop tab click
3. `category_test_03_categories_section.png` - Categories visible
4. `category_test_04_category_toggled.png` - After toggle
5. `category_test_05_redirect_success.png` - After save (redirect)
6. `category_test_06_product_found.png` - Search results
7. `category_test_07_product_reopened.png` - Reopened product
8. `category_test_08_verification.png` - Final verification

---

## 🐛 Issues Found

[Lista znalezionych problemów]

---

## 💡 Recommendations

[Sugestie na podstawie wyników testu]

---

## 📁 Artifacts

- Laravel Logs: `_TOOLS/screenshots/category_test_laravel_logs.txt`
- DB Verification: `_TOOLS/screenshots/category_test_db_verification.txt`
- Test Results: `_TOOLS/screenshots/category_test_results.txt`
```

**Zapisz raport:**
```powershell
# Automatycznie przez skrypt test_workflow.cjs
# Lokalizacja: _TOOLS/screenshots/category_test_report_[timestamp].md
```

#### 4.2 Notyfikacja Użytkownika
```markdown
Po zakończeniu testu:

✅ SUKCES:
═══════════════════════════════════════════════════════
🎉 CATEGORY TEST - PASSED ✅

All checks passed:
✅ Redirect to /admin/products works
✅ Category changes persisted
✅ Database updated correctly
✅ No errors in Laravel logs

Report: _TOOLS/screenshots/category_test_report_[timestamp].md
═══════════════════════════════════════════════════════

❌ FAILURE:
═══════════════════════════════════════════════════════
⚠️ CATEGORY TEST - FAILED ❌

Critical issues found:
❌ [Lista problemów]

Screenshots: _TOOLS/screenshots/category_test_*.png
Logs: _TOOLS/screenshots/category_test_*.txt
Report: _TOOLS/screenshots/category_test_report_[timestamp].md

ACTION REQUIRED: Review logs and fix issues before deployment
═══════════════════════════════════════════════════════
```

---

## 📚 PRZYKŁADY UŻYCIA

### Przykład 1: Standardowy Test (Automatyczny)

**Scenariusz:** Przed deployment zmian w ProductForm

**Input:**
```
User: Zweryfikuj czy category workflow działa poprawnie

Claude: Używam product-form-category-test skill...
```

**Proces:**
1. Skill uruchamia `test_workflow.cjs` automatycznie
2. Wykonuje wszystkie 9 kroków workflow
3. Zbiera screenshots i logi
4. Generuje raport
5. Notyfikuje użytkownika o wynikach

**Output:**
```markdown
✅ CATEGORY TEST - PASSED

All 9 steps completed successfully:
✅ Product 11034 loaded
✅ Shop tab clicked
✅ Categories toggled
✅ CRITICAL: Redirect to /admin/products SUCCESS
✅ Product found by SKU
✅ Changes persisted correctly
✅ Database updated
✅ Logs clean

Report: _TOOLS/screenshots/category_test_report_2025-01-20_14-30.md
```

---

### Przykład 2: Debug Failed Redirect

**Scenariusz:** Redirect po save nie działa

**Input:**
```
User: Test pokazuje że redirect failuje, potrzebuję więcej info

Claude: Uruchamiam test z --show --slow aby zobaczyć co się dzieje...
```

**Proces:**
```powershell
node .claude/skills/product-form-category-test/test_workflow.cjs --show --slow
```

1. Test uruchamia się z widocznym oknem przeglądarki (--show)
2. Wolniejsze wykonanie (slowMo: 1000ms)
3. Po kliknięciu "Zapisz zmiany" obserwujesz:
   - Czy przycisk jest clickable
   - Czy jest wire:click event
   - Czy Livewire pokazuje loading state
   - Czy redirect się wykonuje
4. Screenshot error state: `category_test_ERROR_no_redirect.png`

**Output:**
```markdown
❌ REDIRECT FAILED

Current URL after save: https://ppm.mpptrade.pl/admin/products/11034/edit
Expected URL: https://ppm.mpptrade.pl/admin/products

DIAGNOSIS:
- Save button clicked: ✅
- Livewire event dispatched: ❓ (check wire:click)
- Redirect executed: ❌

NEXT STEPS:
1. Check ProductFormSaver::save() method
2. Verify redirect() is called after save
3. Check for JavaScript errors (DevTools console)
4. Review Livewire events (wire:snapshot issue?)

Screenshot: category_test_ERROR_no_redirect.png
Logs: category_test_laravel_logs.txt
```

---

### Przykład 3: Manual Verification (No Save)

**Scenariusz:** Chcesz tylko sprawdzić UI bez faktycznego save

**Input:**
```powershell
node .claude/skills/product-form-category-test/test_workflow.cjs --no-save --show
```

**Proces:**
1. Wykonuje kroki 1-5 (do toggle kategorii)
2. **Pomija** krok 6 (save)
3. Pomija kroki 7-9 (verification)
4. Pozwala manualnie zweryfikować UI

**Output:**
```markdown
✅ UI TEST - COMPLETED (No save executed)

Checks:
✅ Product loaded
✅ Shop tab works
✅ Categories section visible
✅ Toggle works

Browser left open for manual verification.
Close browser when done.
```

---

## ⚙️ KONFIGURACJA

### Parametry Skryptu (test_workflow.cjs)

```javascript
const CONFIG = {
    // Product to test
    PRODUCT_ID: 11034,
    PRODUCT_SKU: 'Q-KAYO-EA70',

    // Shop to test
    SHOP_NAME: 'B2B Test DEV',
    SHOP_ID: 1,

    // Credentials
    EMAIL: 'admin@mpptrade.pl',
    PASSWORD: 'Admin123!MPP',

    // Timeouts
    LOGIN_TIMEOUT: 10000,
    PAGE_LOAD_TIMEOUT: 10000,
    LIVEWIRE_INIT_TIMEOUT: 2000,
    PRESTASHOP_DATA_TIMEOUT: 3000,
    REDIRECT_TIMEOUT: 10000,

    // Browser
    HEADLESS: true, // Override with --show
    SLOW_MO: 500,   // Override with --slow (1000ms)
    VIEWPORT: { width: 1920, height: 1080 },

    // Output
    SCREENSHOTS_DIR: '_TOOLS/screenshots',
    LOGS_DIR: '_TOOLS/screenshots',
};
```

---

## 🔍 TROUBLESHOOTING

### Problem: Redirect nie działa (stuck on /edit)

**Diagnoza:**
```markdown
1. Sprawdź czy przycisk "Zapisz zmiany" ma wire:click
2. Sprawdź Livewire events w DevTools
3. Sprawdź czy ProductFormSaver::save() wywołuje redirect()
4. Sprawdź logi Laravel dla błędów save
```

**Rozwiązanie:**
```markdown
1. Otwórz ProductFormSaver.php
2. Znajdź metodę save()
3. Sprawdź czy ma: `return redirect()->route('admin.products.index')`
4. Jeśli brak - dodaj redirect
5. Jeśli jest - sprawdź czy nie jest blokowany (error przed redirect)
6. Uruchom test ponownie z --show aby obserwować
```

---

### Problem: Categories nie persistują (reset po reload)

**Diagnoza:**
```markdown
1. Sprawdź ProductShopData.categories (JSON field)
2. Sprawdź czy ProductCategoryManager zapisuje do DB
3. Sprawdź czy loadShopDataToForm() ładuje poprawnie
4. Sprawdź updated_at timestamp
```

**Rozwiązanie:**
```powershell
# Weryfikuj DB bezpośrednio
php _TEMP/verify_category_save.php

# Sprawdź logi save operation
tail -f storage/logs/laravel.log | grep "ProductCategoryManager"

# Jeśli brak wpisów - save() nie wywołuje manager
# Fix: Dodaj wywołanie w ProductFormSaver::save()
```

---

### Problem: Shop tab nie ładuje danych PrestaShop

**Diagnoza:**
```markdown
1. Sprawdź czy PrestaShop API odpowiada (curl test)
2. Sprawdź timeout dla API (może być za krótki)
3. Sprawdź credentials sklepu w DB
4. Sprawdź network tab w DevTools
```

**Rozwiązanie:**
```javascript
// W test_workflow.cjs zwiększ timeout
await page.waitForTimeout(5000); // było 3000

// Lub dodaj explicit wait for data
await page.waitForSelector('input[wire\\:model*="shopCategories"]', { timeout: 10000 });
```

---

## 📖 BEST PRACTICES

### ✅ DO:
- **Uruchamiaj test przed każdym deployment** zmian w ProductForm
- **Używaj --show przy debugowaniu** aby zobaczyć co się dzieje
- **Sprawdzaj logi Laravel** po każdym failed test
- **Archiwizuj screenshoty** dla późniejszej analizy
- **Dokumentuj found issues** w _ISSUES_FIXES/
- **Update test jeśli workflow się zmieni** (np. nowy shop, inne kategorie)

### ❌ DON'T:
- Nie ignoruj failed redirect - to KRYTYCZNY blocker
- Nie zakładaj że DB jest aktualne bez weryfikacji
- Nie używaj testu na lokalnym dev (tylko produkcja)
- Nie modyfikuj CONFIG bez update dokumentacji
- Nie usuń screenshotów po failed test (potrzebne do diagnozy)

---

## 📊 SYSTEM UCZENIA SIĘ (Automatyczny - nie edytować ręcznie)

### Tracking Informacji
Ten skill automatycznie zbiera następujące dane:
- Czas wykonania każdego kroku workflow
- Status sukces/porażka dla każdej fazy
- Napotkane błędy (redirect, persistence, DB)
- Feedback użytkownika (czy test wykrył prawdziwy problem)

### Metryki Sukcesu
- Success rate target: **95%** (5% tolerance dla flaky tests)
- Max execution time: **120 sekund** (full E2E workflow)
- User satisfaction target: **4.5/5**
- Critical failures: **Redirect failure = BLOCKER**

### Historia Ulepszeń
<!-- Automatycznie generowane przy każdej aktualizacji -->

#### v1.0.0 (2025-01-20)
- [INIT] Początkowa wersja Product Form Category Test Skill
- [FEATURE] 9-step E2E workflow (login → save → verify)
- [FEATURE] Redirect verification (critical check)
- [FEATURE] Persistence verification (DB + UI)
- [FEATURE] Laravel logs checking
- [FEATURE] Database verification script
- [FEATURE] Screenshot automation
- [FEATURE] Report generation
- [DOCS] Pełna dokumentacja workflow i troubleshooting

---

## 🏁 PODSUMOWANIE

Product-form-category-test to **E2E testing skill** który:

✅ **Automatyzuje** powtarzalny proces testowania kategorii
✅ **Weryfikuje** krytyczny redirect po save
✅ **Sprawdza** persistencję zmian w DB i UI
✅ **Zbiera** logi i screenshots do diagnozy
✅ **Generuje** raporty z wynikami testów
✅ **Wykrywa** problemy przed deployment

Używając tego skilla, zapewniasz **jakość i stabilność** ProductForm category workflow.

---

**Happy Testing! 🚀**
