---
description: Pelna diagnostyka layoutu strony - screenshot, DOM analysis, CSS debugging, div balance checking
tags: [diagnostics, layout, debugging, dom, css, flexbox]
---

# Komenda: Analizuj Strone

Przeprowadz pelna analize layoutu strony internetowej z projektu PPM-CC-Laravel.

## Cel

Automatyczna diagnostyka problemow z layoutem strony, w tym:
- Problemy z flexbox/grid layout
- Nieprawidlowa hierarchia DOM (parent-child relationships)
- Niezbalansowane div tagi w Blade templates
- CSS stacking context issues
- Problemy z position: sticky/fixed

## Parametry

**URL strony do analizy** (domyslnie: https://ppm.mpptrade.pl/admin/products/4/edit)

## Workflow Diagnostyczny

### FAZA 1: Visual Inspection & Screenshot
1. Uzyj Playwright do zaladowania strony i zrobienia screenshota
2. Zapisz screenshot w `_TOOLS/screenshots/page_TIMESTAMP.png`
3. Przeanalizuj wizualnie layout (gdzie elementy sie renderuja)

### FAZA 2: DOM Structure Analysis
Utworz i uruchom skrypt Playwright `check_dom_structure.cjs`:
```javascript
// Sprawdz:
// 1. Czy kluczowe elementy istnieja (main-container, left-column, right-column)
// 2. Czy parent-child relationships sa poprawne
// 3. Jakie sa bezposrednie dzieci main-container
// 4. Pelna sciezka parent hierarchy dla problematycznych elementow
```

### FAZA 3: CSS Computed Styles Analysis
Utworz i uruchom skrypt `debug_flexbox_styles.cjs`:
```javascript
// Sprawdz:
// 1. display, flex-direction, gap dla containers
// 2. position, top, flex properties dla columns
// 3. Rzeczywiste pozycje x,y i rozmiary elementow
// 4. Czy layout jest zgodny z oczekiwaniami (left po lewej, right po prawej)
```

### FAZA 4: Blade Template Balance Check
Dla stron renderowanych z Blade templates, utworz skrypty PowerShell:

**A. Znajdz plik Blade:**
```powershell
# Wyszukaj blade file na podstawie URL route
# np. /admin/products/4/edit -> resources/views/livewire/products/management/product-form.blade.php
```

**B. Policz balans divow:**
```powershell
# count_divs_balance.ps1
# Dla kazdej sekcji blade template policz:
# - Opening divs: <div...>
# - Closing divs: </div>
# - Balance (powinien byc 0 na koncu)
# Wypisz balance line-by-line dla sekcji problematycznych
```

**C. Trace parent containers:**
```powershell
# trace_container_balance.ps1
# Dla glownego kontenera (np. main-container):
# 1. Znajdz linie gdzie otwiera sie (balance +1)
# 2. Track balance przez caly plik
# 3. Znajdz gdzie powinien sie zamknac (balance wraca do 0)
# 4. Sprawdz czy komentarze {{-- Close X --}} sa poprawne
```

### FAZA 5: Detailed Section Analysis
Jesli wykryto problem w konkretnej sekcji:

```powershell
# detailed_balance_LINES.ps1
# Szczegolowa analiza balance dla konkretnych linii
# Wypisz kazda zmiane balance z kontekstem linii kodu
# Oznacz krytyczne linie kolorami (gdzie balance spada ponizej oczekiwanej wartosci)
```

### FAZA 6: Root Cause Identification
Na podstawie wszystkich analiz zidentyfikuj:
1. **Gdzie jest problem?** (konkretna linia w blade template)
2. **Jaka jest przyczyna?** (extra closing div, missing opening div, zly parent)
3. **Jak naprawic?** (usunac/dodac/przeniesc div tag)

### FAZA 7: Fix Implementation
1. Edytuj blade template z poprawka
2. Upload na serwer przez `quick_upload_blade.ps1`
3. Clear cache: `php artisan view:clear && php artisan cache:clear`
4. Weryfikuj fix przez ponowne uruchomienie FAZY 2 i 3

### FAZA 8: Report Generation
Utworz raport w `_AGENT_REPORTS/LAYOUT_FIX_REPORT_TIMESTAMP.md`:
```markdown
# LAYOUT FIX REPORT - [URL]

## PROBLEM
- Opis problemu (np. "Right sidepanel renders at bottom instead of right side")
- Screenshot przed naprawa

## ROOT CAUSE
- Dokladna przyczyna (np. "Line 992 in blade template closes left-column instead of enterprise-card")
- Analiza balance (expected vs actual)

## FIX APPLIED
- Konkretne zmiany w kodzie
- Linie przed/po poprawce

## VERIFICATION
- DOM structure check: ✅/❌
- CSS computed styles: ✅/❌
- Visual confirmation: ✅/❌
- Screenshot po naprawie

## FILES MODIFIED
- resources/views/livewire/products/management/product-form.blade.php:992

## TOOLS CREATED
- _TOOLS/check_dom_structure.cjs
- _TOOLS/debug_flexbox_styles.cjs
- _TOOLS/trace_container_balance.ps1
```

## Narzedzia do Utworzenia

### Playwright Scripts (Node.js + Playwright)

**check_dom_structure.cjs:**
- Load page with networkidle
- Query selectors for key elements
- Check parent-child relationships
- Build full parent path hierarchy
- Return structured JSON results

**debug_flexbox_styles.cjs:**
- Get computed styles for containers
- Get bounding box positions (x, y, width, height)
- Verify flexbox properties apply correctly
- Take screenshot z zaznaczonymi elementami
- Diagnose layout issues

**screenshot_page.cjs:**
- Full page screenshot
- Viewport screenshot (1920x1080, 2560x1440)
- Element-specific screenshots
- Save to _TOOLS/screenshots/

### PowerShell Scripts

**find_blade_template.ps1:**
- Input: URL route (np. /admin/products/4/edit)
- Output: Sciezka do blade template
- Search through routes/web.php i Livewire components

**count_divs_balance.ps1:**
- Input: Blade template path
- Count opening/closing divs dla calego pliku
- Count per section (tabs, modals, etc)
- Report imbalances

**trace_container_balance.ps1:**
- Input: Blade template path, container class name
- Find opening line
- Track balance line-by-line
- Find closing line
- Verify comments match actual structure

**detailed_balance_LINES.ps1:**
- Input: Blade template path, line range (start-end)
- Detailed balance tracking z kontekstem
- Color-coded output (Red=problem, Yellow=warning, Green=ok)
- Show truncated line content

**quick_upload_blade.ps1:**
- Upload blade template to server
- Run view:clear and cache:clear
- Confirm upload success

## Quick Start Examples

### Przyklad 1: Problem z sidepanel na dole zamiast po prawej
```bash
# 1. Screenshot i DOM check
node _TOOLS/check_dom_structure.cjs

# 2. CSS analysis
node _TOOLS/debug_flexbox_styles.cjs

# 3. Jesli parent-child zle, sprawdz balance
pwsh _TOOLS/trace_container_balance.ps1

# 4. Znajdz root cause
pwsh _TOOLS/detailed_balance_990_1105.ps1

# 5. Fix i upload
# ... edit blade template ...
pwsh _TOOLS/quick_upload_blade.ps1

# 6. Weryfikuj
node _TOOLS/check_dom_structure.cjs
```

### Przyklad 2: Dropdown chowa sie pod innymi elementami
```bash
# 1. Screenshot i computed styles
node _TOOLS/debug_flexbox_styles.cjs

# 2. Sprawdz z-index stacking context
node _TOOLS/check_z_index_context.cjs

# 3. Fix CSS (z-index na parent, nie child)
# ... edit CSS ...
npm run build
pwsh _TOOLS/hostido_deploy.ps1
```

## Best Practices

1. **Zawsze zacznij od screenshota** - zobacz problem wizualnie
2. **DOM analysis przed CSS** - sprawdz strukture przed stylami
3. **Balance check dla Blade** - wiele problemow to niezbalansowane divs
4. **Detailed analysis tylko dla problematycznej sekcji** - nie analizuj calego pliku line-by-line
5. **Weryfikuj po kazdej zmianie** - upload → clear cache → recheck DOM
6. **Dokumentuj w raporcie** - kazda diagnoza = raport w _AGENT_REPORTS/

## Czeste Problemy i Rozwiazania

### Problem: Right column renders outside main container
**Root Cause:** Extra closing div zamyka parent container przedwczesnie
**Fix:** Znajdz i usun extra closing div lub dodaj brakujacy opening div
**Tool:** `trace_container_balance.ps1`

### Problem: Dropdown chowa sie pod innymi komponentami
**Root Cause:** z-index ustawiony na child zamiast parent
**Fix:** Ustaw z-index na parent container (np. admin header)
**Tool:** `check_z_index_context.cjs`

### Problem: Flexbox nie dziala mimo poprawnego CSS
**Root Cause:** Element nie jest bezposrednim dzieckiem flex container
**Fix:** Popraw strukture DOM (przesun element do wlasciwego parent)
**Tool:** `check_dom_structure.cjs`

### Problem: Sticky position nie dziala
**Root Cause:** Parent ma overflow: hidden lub height constraint
**Fix:** Usun overflow/height constraints z parent chain
**Tool:** `debug_flexbox_styles.cjs` (sprawdz overflow dla parentow)

## Integration z Workflow PPM-CC-Laravel

- **Uzywaj przed deployment:** Weryfikuj layout lokalnie przed wgraniem na serwer
- **Dokumentuj w _AGENT_REPORTS/:** Kazda diagnoza layoutu = raport
- **Aktualizuj _ISSUES_FIXES/:** Jesli nowy typ problemu, utworz issue fix doc
- **Dodaj do CLAUDE.md:** Jesli czesty problem, dodaj do Quick Reference

## Output Format

Komenda powinna wypisac:
1. URL analizowanej strony
2. Status kazdej fazy (✅/❌)
3. Wykryte problemy z konkretnych linii kodu
4. Rekomendowane actions (co naprawic)
5. Sciezka do wygenerowanego raportu

## Przykladowy Output

```
=== ANALIZA STRONY: https://ppm.mpptrade.pl/admin/products/4/edit ===

FAZA 1: Visual Inspection ✅
  Screenshot: _TOOLS/screenshots/page_2025-09-30_14-23-15.png

FAZA 2: DOM Structure ❌
  ❌ Right column parent IS NOT main container
  ❌ Right column parent: <form> (expected: main-container)

FAZA 3: CSS Analysis ✅
  ✅ CSS flexbox properties apply correctly
  ✅ Computed styles match expected values

FAZA 4: Blade Balance Check ❌
  ❌ Imbalance detected in left-column section
  ❌ Line 1101: Balance = 0 (expected: 1)
  ❌ Extra closing div closes left-column too early

ROOT CAUSE IDENTIFIED:
  Line 992: resources/views/livewire/products/management/product-form.blade.php
  Comment says "Close enterprise-card" but actually closes left-column!

RECOMMENDED FIX:
  Remove line 992 (erroneous closing div)

Would you like me to apply this fix? (y/n)
```

## Notes

- Wszystkie skrypty zapisuj w `_TOOLS/`
- Screenshoty w `_TOOLS/screenshots/`
- Raporty w `_AGENT_REPORTS/`
- Nazwy plikow bez polskich znakow (uzyj transliteracji: ą→a, ę→e, etc)
- Kazdy skrypt powinien miec clear output z kolorami (PowerShell: Write-Host -ForegroundColor)