# 🚀 CHROME DEVTOOLS MCP - PRZEWODNIK KOMPLEKSOWY

**Data utworzenia:** 2025-11-21
**Projekt:** PPM-CC-Laravel (Prestashop Product Manager)
**Status:** ✅ MCP zainstalowane i aktywne
**Wersja:** 1.0

---

## 📖 SPIS TREŚCI

1. [Wprowadzenie](#wprowadzenie)
2. [Dlaczego Chrome DevTools MCP](#dlaczego-chrome-devtools-mcp)
3. [Dostępne Narzędzia MCP](#dostępne-narzędzia-mcp)
4. [Mandatory Verification Scenarios](#mandatory-verification-scenarios)
5. [Przykłady Użycia](#przykłady-użycia)
6. [Anti-Patterns i Błędy](#anti-patterns-i-błędy)
7. [Integration z Workflow](#integration-z-workflow)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 WPROWADZENIE

**Chrome DevTools MCP** to **PRIMARY verification tool** dla projektu PPM-CC-Laravel, zastępujący legacy Node.js scripts jako główne narzędzie weryfikacji deployment, UI i interaktywności.

### Status w Projekcie

- ✅ **Zainstalowane:** 2025-11-21
- ✅ **Aktywne:** Wszystkie agenty muszą używać
- ✅ **Mandatory:** Przed każdym completion frontend/deployment task
- ✅ **Verified:** FIX #7/#8 - pierwsze production use case

### Dokumenty Powiązane

- [`CLAUDE.md`](../CLAUDE.md) - Section: "🎨 OBOWIĄZKOWA WERYFIKACJA FRONTEND"
- [`_DOCS/AGENT_USAGE_GUIDE.md`](_DOCS/AGENT_USAGE_GUIDE.md) - Section: "🚀 OBOWIĄZKOWE: Chrome DevTools MCP"
- [`_DOCS/FRONTEND_VERIFICATION_GUIDE.md`](_DOCS/FRONTEND_VERIFICATION_GUIDE.md) - Szczegółowe procedury

---

## 💡 DLACZEGO CHROME DEVTOOLS MCP

### 🏆 Porównanie: MCP vs Node.js vs curl

| Funkcjonalność | Chrome DevTools MCP | Node.js Scripts | curl/plink |
|----------------|---------------------|-----------------|------------|
| **Live DOM Inspection** | ✅ Rzeczywisty stan przeglądarki | ⚠️ Teoretyczny render (puppeteer) | ❌ Brak |
| **Network Monitoring** | ✅ HTTP codes, headers, timing | ⚠️ Ograniczone | ⚠️ Basic HTTP check |
| **Console Errors** | ✅ JS/Livewire runtime errors | ⚠️ Tylko uncaught errors | ❌ Brak |
| **Interactive Testing** | ✅ Clicks, forms, state changes | ⚠️ Emulated interactions | ❌ Brak |
| **Livewire State** | ✅ Component properties access | ❌ Brak | ❌ Brak |
| **Screenshot** | ✅ Viewport + full-page | ✅ Via puppeteer | ❌ Brak |
| **Element Inspection** | ✅ Text snapshot (preferred) | ⚠️ HTML dump | ❌ Brak |
| **Real Browser** | ✅ Chrome native | ⚠️ Headless emulation | ❌ Brak |
| **wire:loading conflicts** | ✅ **Wykrywa (FIX #8)** | ❌ **Nie wykryje** | ❌ Brak |
| **Disabled state flashing** | ✅ **Wykrywa (FIX #7)** | ❌ **Nie wykryje** | ❌ Brak |

### 🎯 Kluczowe Przewagi

**1. LIVE BROWSER STATE**
- Chrome DevTools MCP komunikuje się z **rzeczywistym Chrome browser**
- Widzi **faktyczny stan DOM**, nie teoretyczny render
- Wykrywa problemy które **tylko real browser** może zobaczyć

**2. LIVEWIRE 3.x COMPATIBILITY**
- Dostęp do `window.Livewire.components`
- Inspection component properties (`isSaving`, `activeJobStatus`)
- Wykrywanie `wire:snapshot`, `wire:loading` conflicts

**3. INTERACTIVE TESTING**
- Kliknięcia przycisków i checkbox-ów
- Wypełnianie formularzy
- Weryfikacja state changes PO interakcji

**4. PRODUCTION-LIKE VERIFICATION**
- Test w prawdziwym Chrome (nie emulacja)
- CSS rendering jak user widzi
- JavaScript execution w real environment

---

## 🛠️ DOSTĘPNE NARZĘDZIA MCP

### 1️⃣ Navigation & Pages

#### `mcp__chrome-devtools__list_pages()`
Lista otwartych stron/tabów w przeglądarce.

```javascript
// Przykład użycia
mcp__chrome-devtools__list_pages()

// Output
[
  {pageIdx: 0, url: "https://ppm.mpptrade.pl/admin", title: "Admin Panel"},
  {pageIdx: 1, url: "https://ppm.mpptrade.pl/admin/products", title: "Products"}
]
```

#### `mcp__chrome-devtools__new_page({url, timeout?})`
Otwiera nową kartę z podanym URL.

```javascript
mcp__chrome-devtools__new_page({
  url: "https://ppm.mpptrade.pl/admin/products",
  timeout: 30000  // 30s (optional)
})
```

#### `mcp__chrome-devtools__select_page({pageIdx})`
Przełącza focus na wybraną kartę.

```javascript
mcp__chrome-devtools__select_page({pageIdx: 1})
```

#### `mcp__chrome-devtools__navigate_page({type, url?, ignoreCache?, timeout?})`
Nawiguje aktywną kartę.

```javascript
// Navigate to URL
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products"
})

// Reload (ignore cache)
mcp__chrome-devtools__navigate_page({
  type: "reload",
  ignoreCache: true
})

// Back/Forward
mcp__chrome-devtools__navigate_page({type: "back"})
mcp__chrome-devtools__navigate_page({type: "forward"})
```

#### `mcp__chrome-devtools__close_page({pageIdx})`
Zamyka wybraną kartę (ostatnia nie może być zamknięta).

```javascript
mcp__chrome-devtools__close_page({pageIdx: 1})
```

---

### 2️⃣ DOM Inspection (PRIMARY VERIFICATION)

#### `mcp__chrome-devtools__take_snapshot({verbose?, filePath?})`
**PREFERRED** - Text snapshot oparty na accessibility tree.

**Dlaczego preferred:**
- ✅ **Szybsze** niż screenshot (text vs image)
- ✅ **Searchable** - można grep-ować w output
- ✅ **Smaller** - text file vs PNG
- ✅ **UID references** - każdy element ma unique ID dla interakcji

```javascript
// Basic snapshot
mcp__chrome-devtools__take_snapshot()

// Verbose (full a11y tree)
mcp__chrome-devtools__take_snapshot({verbose: true})

// Save to file
mcp__chrome-devtools__take_snapshot({
  filePath: "_TOOLS/screenshots/verification_snapshot_2025-11-21.txt"
})
```

**Output example:**
```
heading "Admin Panel" uid: 1_1
  button "Products" uid: 1_2
  button "Shops" uid: 1_3
form "Product Form" uid: 2_1
  heading "Kategorie" uid: 2_2
  checkbox "Baza" uid: 8_239 checked disabled: false
  button "Ustaw główną" uid: 8_240 disabled: false
```

**Use Cases:**
- ✅ **Quick verification** - czy elementy są widoczne
- ✅ **wire:snapshot detection** - search for literal "wire:snapshot" text
- ✅ **Disabled state check** - `disabled: true/false` w snapshot
- ✅ **Element presence** - czy button/checkbox istnieje

#### `mcp__chrome-devtools__take_screenshot({uid?, fullPage?, format?, quality?, filePath?})`
**SECONDARY** - Screenshot dla wizualnej weryfikacji.

```javascript
// Viewport screenshot
mcp__chrome-devtools__take_screenshot()

// Full-page screenshot
mcp__chrome-devtools__take_screenshot({fullPage: true})

// Specific element
mcp__chrome-devtools__take_screenshot({uid: "8_239"})

// Save to file (PNG, JPEG, WebP)
mcp__chrome-devtools__take_screenshot({
  fullPage: true,
  format: "png",
  quality: 90,
  filePath: "_TOOLS/screenshots/verification_full_2025-11-21.png"
})
```

**Use Cases:**
- ✅ **Visual verification** - layout, styling, spacing
- ✅ **User review** - pokazanie użytkownikowi jak wygląda
- ✅ **Evidence** - dokumentacja w raportach agentów
- ⚠️ **NOT for primary check** - używaj snapshot jako primary

---

### 3️⃣ Element Interaction

#### `mcp__chrome-devtools__click({uid, dblClick?})`
Kliknięcie elementu (wymaga snapshot najpierw!).

```javascript
// Single click
mcp__chrome-devtools__click({uid: "8_239"})

// Double click
mcp__chrome-devtools__click({uid: "8_240", dblClick: true})
```

#### `mcp__chrome-devtools__hover({uid})`
Hover nad elementem.

```javascript
mcp__chrome-devtools__hover({uid: "1_2"})
```

#### `mcp__chrome-devtools__fill({uid, value})`
Wypełnienie input/textarea/select.

```javascript
mcp__chrome-devtools__fill({
  uid: "3_10",
  value: "Test Product Name"
})
```

#### `mcp__chrome-devtools__fill_form({elements})`
Wypełnienie wielu pól jednocześnie.

```javascript
mcp__chrome-devtools__fill_form({
  elements: [
    {uid: "3_10", value: "Product Name"},
    {uid: "3_11", value: "12.99"},
    {uid: "3_12", value: "150"}
  ]
})
```

#### `mcp__chrome-devtools__press_key({key})`
Naciśnięcie klawisza/kombinacji.

```javascript
// Single key
mcp__chrome-devtools__press_key({key: "Enter"})

// Combination
mcp__chrome-devtools__press_key({key: "Control+S"})
mcp__chrome-devtools__press_key({key: "Control+Shift+R"})
```

#### `mcp__chrome-devtools__drag({from_uid, to_uid})`
Drag & drop elementu.

```javascript
mcp__chrome-devtools__drag({
  from_uid: "5_10",
  to_uid: "5_20"
})
```

#### `mcp__chrome-devtools__wait_for({text, timeout?})`
Czekaj aż tekst pojawi się na stronie.

```javascript
mcp__chrome-devtools__wait_for({
  text: "Zapisano pomyślnie",
  timeout: 5000  // 5s (optional)
})
```

---

### 4️⃣ JavaScript Evaluation (ADVANCED)

#### `mcp__chrome-devtools__evaluate_script({function, args?})`
Wykonanie custom JavaScript w kontekście strony.

**CRITICAL:** Returns must be JSON-serializable!

```javascript
// Simple query
mcp__chrome-devtools__evaluate_script({
  function: "() => document.title"
})

// Livewire component state
mcp__chrome-devtools__evaluate_script({
  function: "() => window.Livewire.components.componentsByName('product-form')[0]?.data"
})

// Disabled inputs count (FIX #7/#8 pattern)
mcp__chrome-devtools__evaluate_script({
  function: "() => ({ total: document.querySelectorAll('input').length, disabled: document.querySelectorAll('input[disabled]').length })"
})

// With arguments (element from snapshot)
mcp__chrome-devtools__evaluate_script({
  function: "(el) => el.innerText",
  args: [{uid: "8_239"}]
})

// Inline styles detection (anti-pattern)
mcp__chrome-devtools__evaluate_script({
  function: "() => document.querySelectorAll('[style]').length"
})

// Z-index conflicts detection
mcp__chrome-devtools__evaluate_script({
  function: "() => Array.from(document.querySelectorAll('[style*=\"z-index\"]')).map(el => ({tag: el.tagName, z: el.style.zIndex}))"
})
```

**Use Cases:**
- ✅ **Livewire state inspection** - component properties
- ✅ **Anti-pattern detection** - inline styles, z-index
- ✅ **Disabled state counting** - prevent FIX #7/#8 repeats
- ✅ **Custom verification logic** - any JS you need

---

### 5️⃣ Console Monitoring

#### `mcp__chrome-devtools__list_console_messages({types?, includePreservedMessages?, pageIdx?, pageSize?})`
Lista console messages (errors, warnings, logs).

```javascript
// Errors tylko
mcp__chrome-devtools__list_console_messages({
  types: ["error"]
})

// Errors + warnings
mcp__chrome-devtools__list_console_messages({
  types: ["error", "warn"]
})

// All messages
mcp__chrome-devtools__list_console_messages({
  types: ["log", "debug", "info", "error", "warn"]
})

// Pagination
mcp__chrome-devtools__list_console_messages({
  pageIdx: 0,
  pageSize: 50
})
```

**Output:**
```
[
  {msgid: 1, type: "error", text: "Uncaught TypeError: Cannot read property 'data' of undefined", source: "https://ppm.mpptrade.pl/build/assets/app-abc123.js:150"},
  {msgid: 2, type: "warn", text: "Livewire: wire:poll interval too short (< 1s)", source: "..."}
]
```

#### `mcp__chrome-devtools__get_console_message({msgid})`
Pobiera szczegóły pojedynczej wiadomości.

```javascript
mcp__chrome-devtools__get_console_message({msgid: 1})
```

---

### 6️⃣ Network Monitoring

#### `mcp__chrome-devtools__list_network_requests({resourceTypes?, includePreservedRequests?, pageIdx?, pageSize?})`
Lista network requests (HTTP calls).

```javascript
// CSS + JS assets
mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["stylesheet", "script"]
})

// AJAX/Fetch calls (API monitoring)
mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["xhr", "fetch"]
})

// All requests
mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["document", "stylesheet", "image", "script", "xhr", "fetch"]
})
```

**Output:**
```
[
  {reqid: 1, method: "GET", url: "https://ppm.mpptrade.pl/build/assets/components-abc123.css", status: 200, resourceType: "stylesheet"},
  {reqid: 2, method: "POST", url: "https://ppm.mpptrade.pl/livewire/update", status: 200, resourceType: "xhr"},
  {reqid: 3, method: "GET", url: "https://ppm.mpptrade.pl/build/assets/app-def456.js", status: 404, resourceType: "script"}
]
```

#### `mcp__chrome-devtools__get_network_request({reqid?})`
Pobiera szczegóły request (headers, body, response).

```javascript
// Get specific request
mcp__chrome-devtools__get_network_request({reqid: 2})

// Get currently selected in DevTools Network panel
mcp__chrome-devtools__get_network_request()
```

---

### 7️⃣ Other Tools

#### `mcp__chrome-devtools__handle_dialog({action, promptText?})`
Obsługa alert/confirm/prompt dialogs.

```javascript
// Accept alert/confirm
mcp__chrome-devtools__handle_dialog({action: "accept"})

// Dismiss
mcp__chrome-devtools__handle_dialog({action: "dismiss"})

// Prompt with text
mcp__chrome-devtools__handle_dialog({
  action: "accept",
  promptText: "Test input"
})
```

#### `mcp__chrome-devtools__resize_page({width, height})`
Zmiana rozmiaru viewport (responsive testing).

```javascript
// Desktop
mcp__chrome-devtools__resize_page({width: 1920, height: 1080})

// Tablet
mcp__chrome-devtools__resize_page({width: 768, height: 1024})

// Mobile
mcp__chrome-devtools__resize_page({width: 375, height: 667})
```

#### `mcp__chrome-devtools__emulate({cpuThrottlingRate?, networkConditions?})`
Emulacja slow CPU/network.

```javascript
// Slow CPU (4x slowdown)
mcp__chrome-devtools__emulate({cpuThrottlingRate: 4})

// Slow 3G network
mcp__chrome-devtools__emulate({networkConditions: "Slow 3G"})

// Disable throttling
mcp__chrome-devtools__emulate({
  cpuThrottlingRate: 1,
  networkConditions: "No emulation"
})
```

#### `mcp__chrome-devtools__upload_file({uid, filePath})`
Upload pliku przez input[type=file].

```javascript
mcp__chrome-devtools__upload_file({
  uid: "4_10",
  filePath: "D:/test_file.xlsx"
})
```

#### `mcp__chrome-devtools__performance_start_trace({reload, autoStop})`
Start performance tracing (Core Web Vitals).

```javascript
mcp__chrome-devtools__performance_start_trace({
  reload: true,
  autoStop: true
})
```

#### `mcp__chrome-devtools__performance_stop_trace()`
Stop performance tracing.

```javascript
mcp__chrome-devtools__performance_stop_trace()
```

---

## 🎯 MANDATORY VERIFICATION SCENARIOS

### 📦 SCENARIO 1: Post-Deployment Verification

**MANDATORY AFTER:** Każdy deployment CSS/JS/Blade/Livewire

```javascript
// 1. Navigate to deployed page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products"
})

// 2. Take snapshot (PRIMARY check - fast, searchable)
mcp__chrome-devtools__take_snapshot({
  filePath: "_TOOLS/screenshots/deploy_snapshot_2025-11-21.txt"
})

// 3. Check console for errors
const consoleCheck = mcp__chrome-devtools__list_console_messages({
  types: ["error", "warn"]
})
// ✅ PASS if: 0 errors
// ❌ FAIL if: any errors present

// 4. Verify network (HTTP 200 for CSS/JS)
const networkCheck = mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["stylesheet", "script"]
})
// ✅ PASS if: all status = 200
// ❌ FAIL if: any 404 (manifest cache issue!)

// 5. Screenshot dla wizualizacji (SECONDARY - dla użytkownika)
mcp__chrome-devtools__take_screenshot({
  fullPage: true,
  filePath: "_TOOLS/screenshots/deploy_full_2025-11-21.png"
})

// 6. ONLY THEN report success in _AGENT_REPORTS/
```

**Success Criteria:**
- ✅ Snapshot shows expected elements (no wire:snapshot literals)
- ✅ Console: 0 errors, 0 warnings
- ✅ Network: All CSS/JS assets HTTP 200
- ✅ Screenshot: UI renders correctly

**Report Template:**
```markdown
## DEPLOYMENT VERIFICATION (Chrome DevTools MCP)

**Page:** https://ppm.mpptrade.pl/admin/products

**Snapshot:** ✅ PASS
- File: _TOOLS/screenshots/deploy_snapshot_2025-11-21.txt
- Expected elements present
- No wire:snapshot literals

**Console:** ✅ PASS (0 errors, 0 warnings)

**Network:** ✅ PASS
- components-abc123.css: HTTP 200
- app-def456.js: HTTP 200

**Screenshot:** ✅ PASS
- File: _TOOLS/screenshots/deploy_full_2025-11-21.png
- UI renders correctly

**Conclusion:** Deployment VERIFIED
```

---

### ⚡ SCENARIO 2: Livewire Component Verification

**MANDATORY AFTER:** Update Livewire component (PHP/Blade)

```javascript
// 1. Navigate to component page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products"
})

// 2. Interact with component (trigger Livewire update)
mcp__chrome-devtools__click({uid: "2_5"})  // Click tab/button

// 3. Wait for Livewire update
mcp__chrome-devtools__wait_for({
  text: "Expected text after update",
  timeout: 5000
})

// 4. Check for wire:snapshot issues (PRIMARY)
const snapshot = mcp__chrome-devtools__take_snapshot()
// ✅ PASS if: no literal "wire:snapshot" text
// ❌ FAIL if: "wire:snapshot" found in output

// 5. Evaluate Livewire component state
const livewireState = mcp__chrome-devtools__evaluate_script({
  function: "() => window.Livewire.components.componentsByName('product-form')[0]?.data"
})
// ✅ PASS if: state shows expected values
// ❌ FAIL if: undefined or wrong state

// 6. Check console for Livewire errors
const consoleCheck = mcp__chrome-devtools__list_console_messages({
  types: ["error"]
})
// ✅ PASS if: 0 Livewire-related errors
// ❌ FAIL if: Livewire errors present

// 7. Verify disabled states (prevent FIX #7/#8 repeats!)
const disabledCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => ({ total: document.querySelectorAll('input').length, disabled: document.querySelectorAll('input[disabled]').length })"
})
// ✅ PASS if: disabled count matches expected (e.g., 0 if none should be disabled)
// ❌ FAIL if: unexpected disabled count (race condition!)

// 8. Screenshot final state
mcp__chrome-devtools__take_screenshot({
  filePath: "_TOOLS/screenshots/livewire_state_2025-11-21.png"
})
```

**Success Criteria:**
- ✅ No wire:snapshot literals
- ✅ Component state correct
- ✅ Console: 0 Livewire errors
- ✅ Disabled states match expected
- ✅ UI stable after wire:poll cycles

---

### 🎨 SCENARIO 3: Frontend/CSS Verification

**MANDATORY AFTER:** Update Blade/CSS/Alpine.js

```javascript
// 1. Navigate to updated page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin"
})

// 2. Snapshot (PRIMARY check)
mcp__chrome-devtools__take_snapshot()

// 3. Check for inline styles (ANTI-PATTERN!)
const inlineStylesCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => document.querySelectorAll('[style]').length"
})
// ✅ PASS if: 0 (no inline styles)
// ❌ FAIL if: > 0 (violation of CLAUDE.md CSS rules!)

// 4. Check for z-index conflicts
const zIndexCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => Array.from(document.querySelectorAll('[style*=\"z-index\"]')).map(el => ({tag: el.tagName, z: el.style.zIndex}))"
})
// ✅ PASS if: [] (no inline z-index)
// ❌ FAIL if: any elements with inline z-index

// 5. Full-page screenshot (VISUAL verification)
mcp__chrome-devtools__take_screenshot({
  fullPage: true,
  filePath: "_TOOLS/screenshots/frontend_full_2025-11-21.png"
})

// 6. Responsive check (optional - dla mobile/tablet)
mcp__chrome-devtools__resize_page({width: 768, height: 1024})
mcp__chrome-devtools__take_screenshot({
  fullPage: true,
  filePath: "_TOOLS/screenshots/frontend_tablet_2025-11-21.png"
})
```

**Success Criteria:**
- ✅ Snapshot shows correct layout
- ✅ 0 inline styles (use CSS classes!)
- ✅ 0 inline z-index (CSS file only!)
- ✅ Screenshot: visual layout correct
- ✅ Responsive: mobile/tablet OK

---

### 🔌 SCENARIO 4: API Integration Verification

**MANDATORY AFTER:** ERP/PrestaShop API integration

```javascript
// 1. Navigate to integration page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/integrations"
})

// 2. Trigger API call (e.g., click "Test Connection" button)
mcp__chrome-devtools__click({uid: "5_10"})

// 3. Monitor network for API requests
const networkRequests = mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["xhr", "fetch"]
})
// ✅ PASS if: API request sent with correct method/URL
// ❌ FAIL if: no request or wrong endpoint

// 4. Get API response details
const apiResponse = mcp__chrome-devtools__get_network_request({reqid: 10})
// ✅ PASS if: status 200, response contains expected data
// ❌ FAIL if: 4xx/5xx error or malformed response

// 5. Check console for API errors
const consoleCheck = mcp__chrome-devtools__list_console_messages({
  types: ["error", "warn"]
})
// ✅ PASS if: 0 errors
// ❌ FAIL if: API-related errors present

// 6. Verify UI update after API response
mcp__chrome-devtools__wait_for({
  text: "Connection successful",
  timeout: 10000
})
```

**Success Criteria:**
- ✅ API request sent to correct endpoint
- ✅ Response status 200
- ✅ Console: 0 API errors
- ✅ UI shows success message

---

## 📚 PRZYKŁADY UŻYCIA

### Example 1: FIX #7/#8 Verification Pattern

**Problem:** Checkboxes disabled/flashing due to wire:poll + wire:loading.attr conflict

**Chrome DevTools MCP Solution:**

```javascript
// Step 1: Navigate to ProductForm
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products"
})

// Step 2: Click shop tab to load categories
mcp__chrome-devtools__click({uid: "1_10"})

// Step 3: WAIT 5 SECONDS for wire:poll.5s to settle
// (crucial - wire:poll triggers every 5s)
await new Promise(resolve => setTimeout(resolve, 5000))

// Step 4: Check disabled states
const disabledCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => ({ total: document.querySelectorAll('input[type=\"checkbox\"]').length, disabled: document.querySelectorAll('input[type=\"checkbox\"][disabled]').length })"
})

// Expected: {total: 1176, disabled: 0}
// Actual (before FIX #8): {total: 1176, disabled: 1176} ❌
// Actual (after FIX #8): {total: 1176, disabled: 0} ✅

// Step 5: Test button interactivity
mcp__chrome-devtools__click({uid: "8_239"})  // Click "Ustaw główną"

// Step 6: Verify state change
const snapshot = mcp__chrome-devtools__take_snapshot()
// Search for "Główna" text (button should change)

// Step 7: Wait another 5s and verify stability
await new Promise(resolve => setTimeout(resolve, 5000))
const finalCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => ({ total: document.querySelectorAll('button').length, disabled: document.querySelectorAll('button[disabled]').length })"
})

// Expected: disabled = 0 (no flashing!)
```

**Key Learnings:**
- ✅ Wait for wire:poll cycles to settle
- ✅ Check disabled states AFTER interactions
- ✅ Verify stability over multiple poll cycles
- ✅ Node.js scripts would NOT detect this issue!

---

### Example 2: Manifest Cache Detection

**Problem:** Deployed new CSS but browser loads old file (manifest not updated)

**Chrome DevTools MCP Solution:**

```javascript
// Step 1: Navigate to page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin",
  ignoreCache: true  // Force fresh load
})

// Step 2: List network requests for CSS
const networkRequests = mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["stylesheet"]
})

// Step 3: Check HTTP status codes
networkRequests.forEach(req => {
  if (req.status === 404) {
    console.error(`❌ 404 NOT FOUND: ${req.url}`)
    // This means manifest points to non-existent file!
  } else if (req.status === 200) {
    console.log(`✅ 200 OK: ${req.url}`)
  }
})

// Step 4: Verify hashes match build output
// Expected: components-abc123.css (NEW hash)
// Actual: components-xyz789.css (OLD hash) ❌

// ROOT CAUSE: Manifest not uploaded to ROOT location!
// FIX: pscp public/build/.vite/manifest.json → remote/build/manifest.json
```

**Key Learnings:**
- ✅ Network monitor reveals 404s curl misses
- ✅ Can verify exact file hashes loaded
- ✅ Detects partial deployment issues

---

### Example 3: wire:snapshot Detection

**Problem:** Livewire component renders literal "wire:snapshot" text instead of UI

**Chrome DevTools MCP Solution:**

```javascript
// Step 1: Navigate to broken page
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products"
})

// Step 2: Take snapshot (PRIMARY check)
const snapshot = mcp__chrome-devtools__take_snapshot()

// Step 3: Search for "wire:snapshot" literal in output
if (snapshot.includes('wire:snapshot')) {
  console.error('❌ LIVEWIRE RENDER ISSUE: wire:snapshot literal detected!')

  // Step 4: Get console errors for details
  const consoleErrors = mcp__chrome-devtools__list_console_messages({
    types: ["error"]
  })

  // Common causes:
  // - Missing @livewireScripts in layout
  // - JavaScript error preventing Livewire init
  // - wire:key missing in loops
}
```

**Key Learnings:**
- ✅ Text snapshot perfect for this (searchable!)
- ✅ Screenshot would NOT be searchable
- ✅ Console errors give clues to root cause

---

## 🚫 ANTI-PATTERNS I BŁĘDY

### ❌ ANTI-PATTERN 1: Raportowanie Bez Weryfikacji

**WRONG:**
```markdown
## DEPLOYMENT REPORT

✅ Uploaded ProductForm.php (57 KB)
✅ Cleared cache
✅ Deployment successful!
```

**Problem:** No Chrome DevTools verification = user sees broken UI!

**CORRECT:**
```markdown
## DEPLOYMENT REPORT

**Upload:** ✅ ProductForm.php (57 KB via pscp)
**Cache:** ✅ Cleared (artisan view:clear)

**CHROME DEVTOOLS VERIFICATION:**
- Navigate: ✅ PASS (HTTP 200)
- Console: ✅ PASS (0 errors)
- Network: ✅ PASS (all assets HTTP 200)
- Snapshot: ✅ PASS (no wire:snapshot)
- Screenshot: ✅ PASS (saved to _TOOLS/screenshots/)

**Conclusion:** Deployment VERIFIED with Chrome DevTools MCP
```

---

### ❌ ANTI-PATTERN 2: Używanie Screenshot ZAMIAST Snapshot

**WRONG:**
```javascript
// Primary check
mcp__chrome-devtools__take_screenshot()
// Then trying to find text in PNG file ❌
```

**Problem:**
- Screenshots are binary images (not searchable!)
- Slower to generate
- Larger files
- Cannot grep for "wire:snapshot" or element UIDs

**CORRECT:**
```javascript
// PRIMARY check (fast, searchable, UID references)
const snapshot = mcp__chrome-devtools__take_snapshot()

// SECONDARY check (visual confirmation)
mcp__chrome-devtools__take_screenshot()
```

---

### ❌ ANTI-PATTERN 3: Ignorowanie Disabled States

**WRONG:**
```javascript
// Deploy change
// Check snapshot shows checkboxes ✅
// Report success ❌
```

**Problem:** Checkboxes VISIBLE but DISABLED (FIX #7/#8 repeat!)

**CORRECT:**
```javascript
// Check snapshot
const snapshot = mcp__chrome-devtools__take_snapshot()

// CHECK DISABLED STATES (mandatory!)
const disabledCheck = mcp__chrome-devtools__evaluate_script({
  function: "() => ({ total: document.querySelectorAll('input').length, disabled: document.querySelectorAll('input[disabled]').length })"
})

if (disabledCheck.disabled > 0) {
  console.error(`❌ FAIL: ${disabledCheck.disabled}/${disabledCheck.total} inputs disabled!`)
}
```

---

### ❌ ANTI-PATTERN 4: Nie Czekanie Na wire:poll

**WRONG:**
```javascript
mcp__chrome-devtools__navigate_page({type: "url", url: "..."})
const snapshot = mcp__chrome-devtools__take_snapshot()
// Report success ❌
```

**Problem:** wire:poll.5s triggers AFTER navigation → state changes!

**CORRECT:**
```javascript
mcp__chrome-devtools__navigate_page({type: "url", url: "..."})

// WAIT for wire:poll to settle
await new Promise(resolve => setTimeout(resolve, 6000))

const snapshot = mcp__chrome-devtools__take_snapshot()
```

---

### ❌ ANTI-PATTERN 5: Poleganie Na curl Tylko

**WRONG:**
```bash
curl -I https://ppm.mpptrade.pl/admin
# HTTP 200 OK ✅
# Report deployment success ❌
```

**Problem:**
- curl only checks HTTP code
- Does NOT check: Console errors, Livewire state, disabled elements, wire:snapshot
- User sees 200 OK but BROKEN UI!

**CORRECT:**
```javascript
// 1. curl for quick check (optional)
curl -I https://ppm.mpptrade.pl/admin

// 2. MANDATORY Chrome DevTools verification
mcp__chrome-devtools__navigate_page(...)
mcp__chrome-devtools__take_snapshot()
mcp__chrome-devtools__list_console_messages()
mcp__chrome-devtools__list_network_requests()
```

---

## 🔄 INTEGRATION Z WORKFLOW

### Deployment-Specialist Workflow

```
1. Upload files (pscp)
2. Clear cache (plink + artisan)
3. 🚀 MANDATORY Chrome DevTools MCP Verification:
   - Navigate
   - Snapshot
   - Console check
   - Network check
   - Screenshot
4. Save evidence to _TOOLS/screenshots/
5. Include verification in _AGENT_REPORTS/
6. ONLY THEN report "deployed successfully"
```

### Frontend-Specialist Workflow

```
1. Update Blade/CSS
2. npm run build
3. Deploy (pscp)
4. 🚀 MANDATORY Chrome DevTools MCP Verification:
   - Snapshot
   - Inline styles check
   - Z-index conflicts check
   - Screenshot
5. Save evidence
6. Report with Chrome DevTools proof
```

### Livewire-Specialist Workflow

```
1. Update component (PHP/Blade)
2. Deploy
3. 🚀 MANDATORY Chrome DevTools MCP Verification:
   - Navigate + interact
   - Check wire:snapshot
   - Livewire state evaluation
   - Console errors
   - Disabled states
   - Screenshot
4. Save evidence
5. Report with verification
```

---

## 🔧 TROUBLESHOOTING

### Problem: "Page not found" error

**Solution:**
```javascript
// Check if page is open
mcp__chrome-devtools__list_pages()

// If no pages, create one
mcp__chrome-devtools__new_page({url: "https://ppm.mpptrade.pl/admin"})

// Then select it
mcp__chrome-devtools__select_page({pageIdx: 0})
```

---

### Problem: "Element not found" (UID doesn't exist)

**Solution:**
```javascript
// ALWAYS take snapshot FIRST
mcp__chrome-devtools__take_snapshot()

// Find correct UID in snapshot output
// THEN use it for interaction
mcp__chrome-devtools__click({uid: "correct_uid_from_snapshot"})
```

---

### Problem: evaluate_script returns "undefined"

**Cause:** Non-serializable return value (e.g., DOM element)

**Solution:**
```javascript
// WRONG
function: "() => document.querySelector('#my-element')"  // Returns DOM element ❌

// CORRECT
function: "() => document.querySelector('#my-element').textContent"  // Returns string ✅
function: "() => ({ id: el.id, text: el.textContent })"  // Returns object ✅
```

---

### Problem: Network requests list is empty

**Cause:** Requests happened before monitoring started

**Solution:**
```javascript
// Navigate THEN check
mcp__chrome-devtools__navigate_page({type: "url", url: "..."})
// Requests now captured
mcp__chrome-devtools__list_network_requests()
```

---

### Problem: Console messages not showing

**Cause:** Messages cleared or from previous navigation

**Solution:**
```javascript
// Enable preserved logs
mcp__chrome-devtools__list_console_messages({
  includePreservedMessages: true
})
```

---

## 📖 SUMMARY

### 🎯 Key Takeaways

1. **Chrome DevTools MCP = PRIMARY verification tool** (not Node.js scripts!)
2. **Snapshot > Screenshot** for primary checks (faster, searchable)
3. **MANDATORY dla wszystkich agentów** (deployment/frontend/livewire)
4. **Weryfikacja PRZED completion** (evidence w raportach)
5. **Livewire-specific** - wykrywa wire:snapshot, disabled states, directive conflicts

### ✅ Checklist dla Każdego Agent

- [ ] Navigate to page
- [ ] Take snapshot (PRIMARY)
- [ ] Check console (0 errors)
- [ ] Check network (HTTP 200)
- [ ] Evaluate states (disabled, Livewire)
- [ ] Screenshot (SECONDARY)
- [ ] Save evidence to _TOOLS/screenshots/
- [ ] Include in _AGENT_REPORTS/
- [ ] Report ONLY after verification

---

**Autor:** Claude Code AI
**Data:** 2025-11-21
**Projekt:** PPM-CC-Laravel Enterprise PIM System
**Status:** ✅ ACTIVE GUIDE - MANDATORY FOR ALL AGENTS
