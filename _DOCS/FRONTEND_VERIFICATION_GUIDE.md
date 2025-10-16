# Frontend Verification Guide - PPM-CC-Laravel

**Dokument:** Obowiązkowy przewodnik weryfikacji zmian frontend PRZED informowaniem użytkownika
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Zasady Development → Weryfikacja Frontend

---

## ⚠️ KRYTYCZNA ZASADA

**ZAWSZE weryfikuj poprawność layout, styles i frontend PRZED informowaniem użytkownika o ukończeniu!**

**NIGDY nie informuj użytkownika "Gotowe ✅" bez uprzedniego sprawdzenia przez screenshot/DOM analysis!**

---

## 🤖 AUTOMATED VERIFICATION HOOK

### PowerShell Script (Recommended)

```powershell
# Basic verification
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "https://ppm.mpptrade.pl/admin/products"

# Z automatycznym otwarciem screenshot
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "https://ppm.mpptrade.pl/admin/products" -OpenReport

# Skip specific checks (jeśli potrzebne)
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "..." -SkipScreenshot -SkipDOM
```

### Hook Funkcjonalność

**Automatycznie sprawdza:**
- ✅ Screenshot viewport (1920x1080)
- ✅ DOM structure (Grid, parent hierarchy, positioning)
- ✅ Header/spacing issues (overlay, gaps)
- ❌ Exit code 1 jeśli wykryto problemy (nie informuj użytkownika!)
- ✅ Exit code 0 jeśli wszystko OK

---

## 📋 KIEDY UŻYWAĆ WERYFIKACJI?

### ✅ OBOWIĄZKOWO po każdej zmianie dotyczącej:

- Layout (flexbox, grid, positioning)
- CSS styles (inline styles, classes, media queries)
- Blade templates (struktura DOM, divs balance)
- Responsive design (mobile/desktop breakpoints)
- Z-index / stacking context issues
- Sidebar, header, footer positioning
- Modals, dropdowns, overlays
- Any component that affects page layout

### Slash Command

```bash
/analizuj_strone
```

Użyj tego polecenia dla pełnej diagnostyki strony (screenshot + DOM + CSS).

---

## 🔄 WORKFLOW OBOWIĄZKOWY

### ✅ PRAWIDŁOWY WORKFLOW (WYMAGANY)

```bash
# 1. Wprowadź zmiany w kodzie (CSS/Blade/HTML)
# Edit files locally...

# 2. Build assets (jeśli CSS)
npm run build

# 3. Deploy na produkcję
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
pscp -i $HostidoKey -P 64321 "local/file.php" host379076@host379076.hostido.net.pl:remote/path/
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

# 4. ⚠️ KRYTYCZNE: Zweryfikuj przez screenshot
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
# lub
/analizuj_strone

# 5. Jeśli screenshot pokazuje problem → FIX → powtórz 1-4

# 6. Dopiero gdy screenshot OK → informuj użytkownika
# ✅ "Zmiany wdrożone i zweryfikowane - sidebar działa poprawnie"
```

### ❌ BŁĘDNY WORKFLOW (ZABRONIONY)

```
❌ BAD:
1. Zmiana admin.blade.php (sidebar lg:relative)
2. Upload na produkcję
3. Clear cache
4. "✅ Sidebar naprawiony!" ← BEZ WERYFIKACJI!

User: "Nie widzę żadnych zmian"
Claude: "Przepraszam, sprawdzam..." ← ZA PÓŹNO!
```

---

## 🛠️ NARZĘDZIA WERYFIKACJI

### 1. Screenshot Verification (PODSTAWOWE)

```bash
# Full page screenshot + viewport screenshot
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products

# Custom viewport
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products --width 1920 --height 1080
```

**Output:**
```
_TOOLS/screenshots/
├── page_full_2025-10-14T15-30-00.png      # Full page scroll
├── page_viewport_2025-10-14T15-30-00.png  # Viewport only (1920x1080)
```

### 2. DOM Structure Check

```bash
# Check DOM hierarchy and structure
node _TOOLS/check_dom_structure_new.cjs https://ppm.mpptrade.pl/admin/products
```

**Sprawdza:**
- Grid container structure
- Parent-child hierarchy
- Div balance (opening/closing tags)
- Element positioning (fixed/relative/absolute)

### 3. Computed Styles Analysis

```bash
# Check specific element styles
node _TOOLS/check_sidebar_styles.cjs https://ppm.mpptrade.pl/admin/products
```

**Example script:**
```javascript
// _TOOLS/check_sidebar_styles.cjs
const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    await page.goto('https://ppm.mpptrade.pl/admin/products');

    const styles = await page.evaluate(() => {
        const sidebar = document.querySelector('#sidebar');
        const computed = window.getComputedStyle(sidebar);
        return {
            position: computed.position,
            display: computed.display,
            width: computed.width,
            zIndex: computed.zIndex,
            top: computed.top,
            left: computed.left
        };
    });

    console.log('Sidebar computed styles:', styles);
    await browser.close();
})();
```

---

## 📸 CO SPRAWDZAĆ NA SCREENSHOT

### Layout & Positioning

- [ ] **Sidebar NIE zasłania content**
- [ ] **Wszystkie kolumny widoczne i klikalne**
- [ ] **Header nie overlays content**
- [ ] **Footer w odpowiednim miejscu**
- [ ] **No unexpected gaps/whitespace**

### Responsive Design

- [ ] **Desktop breakpoints działają (>1024px)**
- [ ] **Tablet view OK (768px - 1024px)**
- [ ] **Mobile view OK (<768px)**
- [ ] **No horizontal scroll (chyba że zamierzone)**

### Components

- [ ] **Modals renderują się na wierzchu (z-index)**
- [ ] **Dropdowns nie chowają się pod content**
- [ ] **Tooltips widoczne**
- [ ] **Forms layout spójny**

### Typography & Content

- [ ] **Teksty nie są ucięte (word-wrap)**
- [ ] **Font sizes consistent**
- [ ] **Colors zgodne z paletą MPP TRADE**
- [ ] **Icons/images load correctly**

---

## 📁 SCREENSHOT STORAGE

```
_TOOLS/screenshots/
├── page_full_2025-10-14T15-30-00.png      # Full page scroll
├── page_viewport_2025-10-14T15-30-00.png  # Viewport (1920x1080)
├── page_full_2025-10-14T16-45-00.png      # After fix
└── page_viewport_2025-10-14T16-45-00.png  # After fix viewport
```

**ZASADA:** Zachowuj screenshoty PRZED i PO zmianach dla porównania!

---

## 🎯 PRZYPADKI UŻYCIA

### Case 1: Sidebar Layout Fix

```bash
# Problem: Sidebar zasłania content
/analizuj_strone
# → Screenshot pokazuje sidebar fixed zamiast relative
# → Diagnoza: CSS position: fixed na desktop

# Fix: Zmień na position: relative dla lg breakpoint
# Edit: resources/css/layout.css
# .sidebar { @media (min-width: 1024px) { position: relative; } }

# Rebuild & Deploy
npm run build
# Upload CSS assets
# Clear cache

# Verify AGAIN
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
# → Screenshot shows sidebar position: relative ✅

# NOW inform user
# ✅ "Sidebar naprawiony - zweryfikowane przez screenshot"
```

### Case 2: Modal Z-Index Issue

```bash
# Problem: Modal chowa się pod header
/analizuj_strone
# → Screenshot pokazuje modal pod headerem

# Check z-index hierarchy
node _TOOLS/check_modal_zindex.cjs
# → Header: z-50, Modal: z-40 (PROBLEM!)

# Fix: Modal z-999999
# Edit: resources/css/components/modal.css
# .modal { z-index: 999999; }

# Rebuild & Deploy & Verify
npm run build
# Upload & cache clear
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
# → Modal on top ✅
```

### Case 3: Responsive Breakpoints

```bash
# Test multiple viewports:
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products --width 375   # Mobile
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products --width 768   # Tablet
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products --width 1920  # Desktop

# Compare screenshots
# Verify:
# - Mobile: Sidebar collapsed/hidden
# - Tablet: Sidebar toggle button
# - Desktop: Sidebar visible relative
```

### Case 4: Blade Template Changes

```bash
# Problem: Zmiana struktury DOM w product-list.blade.php
# 1. Deploy template
# 2. Screenshot verification
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products

# 3. DOM structure check
node _TOOLS/check_dom_structure_new.cjs https://ppm.mpptrade.pl/admin/products
# → Check div balance, grid structure

# 4. Visual inspection (open screenshot)
# → Verify layout matches expectations
```

---

## 🔗 INTEGRATION Z AGENTS

### frontend-specialist

**ZAWSZE** używa `/analizuj_strone` po każdej zmianie layout/CSS/Blade.

```markdown
# frontend-specialist workflow
1. Implementuj zmiany CSS/Blade
2. Build assets (npm run build)
3. Deploy
4. Run: /analizuj_strone
5. Jeśli OK → Raport + inform user
6. Jeśli PROBLEM → Fix → powtórz 3-5
```

### livewire-specialist

Weryfikuje rendering komponentów Livewire (wire:key, reactivity, DOM updates).

```markdown
# livewire-specialist workflow
1. Implementuj komponent Livewire
2. Deploy
3. Screenshot + interaction test
4. Verify: wire:key unique, no DOM duplication
```

### coding-style-agent

Sprawdza violations:
- ❌ Inline styles (`style=""` attributes)
- ❌ Hardcoded colors (nie z palety MPP)
- ❌ Inconsistent spacing/margins

---

## 📊 VERIFICATION CHECKLIST

### Pre-Deployment

- [ ] Zmiany CSS/Blade/HTML zaimplementowane
- [ ] Assets zbudowane lokalnie (`npm run build`)
- [ ] Brak inline styles (`style=""`)
- [ ] Zgodność z paletą kolorów MPP TRADE
- [ ] Responsive breakpoints uwzględnione

### Post-Deployment

- [ ] Files uploaded via pscp
- [ ] Cache cleared (view:clear, cache:clear)
- [ ] **Screenshot captured (`_TOOLS/screenshot_page.cjs`)**
- [ ] **DOM structure verified (`_TOOLS/check_dom_structure_new.cjs`)**
- [ ] Visual inspection passed
- [ ] Multiple viewports tested (desktop/tablet/mobile)
- [ ] No console errors in browser DevTools

### ✅ DOPIERO TERAZ

- [ ] Inform user: "✅ Zmiany wdrożone i zweryfikowane"
- [ ] Include screenshot path in report
- [ ] Update todo list: mark task completed

---

## 🚨 RED FLAGS (DO NOT INFORM USER)

### ❌ PROBLEMS DETECTED:

- Sidebar overlays content (z-index/position issue)
- Columns not clickable (overlay blocking)
- Horizontal scroll appears
- Modal/dropdown hidden under header
- Text cut off/overflowing
- Responsive breakpoints not working
- Layout broken on mobile
- Console errors in DevTools

### ✅ FIX FIRST, THEN INFORM

1. Identify root cause
2. Implement fix
3. Rebuild & deploy
4. Screenshot verification AGAIN
5. Only when screenshot OK → inform user

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Główne zasady weryfikacji frontend
- **_DOCS/CSS_STYLING_GUIDE.md** - Zasady stylów CSS
- **_DOCS/DEPLOYMENT_GUIDE.md** - Deploy workflow
- **_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md** - Z-index problems
- **_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md** - Grid layout solutions

---

## 🔧 TROUBLESHOOTING

### Problem: Screenshot pokazuje stare zmiany

```bash
# 1. Sprawdź czy assets zbudowane
npm run build

# 2. Upload manifest.json + hashed files
pscp -i $HostidoKey -P 64321 "public/build/manifest.json" ...

# 3. Clear view cache
plink ... "php artisan view:clear"

# 4. Hard refresh browser (Ctrl+Shift+R)

# 5. Screenshot AGAIN
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
```

### Problem: Screenshot pokazuje 500 error

```bash
# Check Laravel logs
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Fix error → Deploy → Screenshot again
```

### Problem: Layout działa local, broken production

```bash
# 1. Verify CSS compiled for production
npm run build

# 2. Check manifest.json hash matches uploaded files
cat public/build/manifest.json

# 3. Verify all CSS files uploaded
# 4. Clear all caches
plink ... "php artisan optimize:clear"
```

---

**PAMIĘTAJ:** Screenshot verification to OBOWIĄZKOWY krok przed informowaniem użytkownika o ukończeniu zmian frontend!
