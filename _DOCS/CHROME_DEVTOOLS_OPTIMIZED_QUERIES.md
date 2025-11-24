# Chrome DevTools MCP - Optimized Queries Guide

## 🎯 Problem

**ISSUE:** `mcp__chrome-devtools__take_snapshot()` zwraca pełne źródło strony (>25k tokenów)
- Przekracza limit tokenów (25000)
- Zużywa niepotrzebnie context window
- Spowalnia analizę i response time

**SOLUTION:** Targeted queries zamiast full snapshot!

---

## ✅ RECOMMENDED PATTERNS

### Pattern 1: evaluate_script() - Targeted Element Selection (BEST!)

**Użyj gdy:** Wiesz CZEGO szukasz (checkboxy, przyciski, konkretne elementy)

**Zamiast:**
```javascript
// ❌ BAD: Full snapshot (>25k tokens)
const snapshot = mcp__chrome-devtools__take_snapshot()
// Potem szukasz w 25k+ tokenach...
```

**Użyj:**
```javascript
// ✅ GOOD: Targeted query (<<1k tokens)
const result = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    return {
      total: checkboxes.length,
      disabled: Array.from(checkboxes).filter(cb => cb.disabled).length,
      checked: Array.from(checkboxes).filter(cb => cb.checked).length,
      visible: Array.from(checkboxes).filter(cb => cb.offsetParent !== null).length
    };
  }`
})
// Result: {total: 1176, disabled: 0, checked: 5, visible: 1176} - tylko 50 tokenów!
```

**Zalety:**
- ✅ Minimal tokens (50-200 vs 25000+)
- ✅ Fast execution
- ✅ Structured JSON output
- ✅ No file I/O needed

---

### Pattern 2: snapshot → file + Grep (dla text search)

**Użyj gdy:** Szukasz text pattern (np. "wire:snapshot", error message, specific text)

**Step-by-step:**

```javascript
// Step 1: Save snapshot to file (NIE czytaj wyniku!)
mcp__chrome-devtools__take_snapshot({
  verbose: false,  // Mniejszy output
  filePath: "_TEMP/snapshot.txt"
})
// Output zapisany do pliku, nie do context!

// Step 2: Grep dla specific pattern
```

```powershell
# Grep dla wire:snapshot (tylko matched lines)
$matches = Select-String -Path "_TEMP/snapshot.txt" -Pattern "wire:snapshot" -Context 2

if ($matches) {
  Write-Host "❌ FOUND wire:snapshot issues:"
  $matches | ForEach-Object { $_.Line }
} else {
  Write-Host "✅ No wire:snapshot found"
}
```

**Alternatywnie - Read z Grep:**
```javascript
// Grep tool (built-in)
Grep({
  pattern: "wire:snapshot",
  path: "_TEMP/snapshot.txt",
  output_mode: "content",
  "-n": true,  // Line numbers
  head_limit: 10  // Max 10 matches
})
```

**Zalety:**
- ✅ Snapshot nie wchodzi do context
- ✅ Tylko matched lines (10-100 tokenów)
- ✅ Fast search w dużych plikach

---

### Pattern 3: Incremental Element Inspection

**Użyj gdy:** Potrzebujesz UIDs dla click/fill, ale nie chcesz full snapshot

**Step-by-step:**

```javascript
// Step 1: Get ONLY interactive elements (nie cały DOM!)
const interactiveElements = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const buttons = Array.from(document.querySelectorAll('button'));
    const inputs = Array.from(document.querySelectorAll('input:not([type="hidden"])'));
    const selects = Array.from(document.querySelectorAll('select'));

    return {
      buttons: buttons.slice(0, 20).map((btn, i) => ({
        text: btn.textContent.trim().substring(0, 50),
        id: btn.id,
        classes: btn.className.substring(0, 50),
        disabled: btn.disabled
      })),
      inputs: inputs.slice(0, 20).map((inp, i) => ({
        type: inp.type,
        name: inp.name,
        id: inp.id,
        value: inp.value.substring(0, 30),
        disabled: inp.disabled
      })),
      selects: selects.slice(0, 10).map((sel, i) => ({
        name: sel.name,
        id: sel.id,
        options: sel.options.length
      }))
    };
  }`
})
// Result: ~500-1000 tokenów (zamiast 25k+)

// Step 2: Jeśli potrzebujesz UID dla konkretnego elementu:
// TERAZ weź minimal snapshot dla TEGO specific area
const minimalSnapshot = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    // Find button by text
    const btn = Array.from(document.querySelectorAll('button'))
      .find(b => b.textContent.includes('Ustaw główną'));

    // Return UID (jeśli MCP używa data-uid lub similar)
    return {
      found: !!btn,
      // Return info to locate w snapshot
      selector: btn ? \`button:contains("Ustaw główną")\` : null,
      index: btn ? Array.from(document.querySelectorAll('button')).indexOf(btn) : null
    };
  }`
})
```

**Alternatywnie - Snapshot z verbose: false:**
```javascript
// TYLKO jeśli musisz mieć UIDs
const minimalSnapshot = mcp__chrome-devtools__take_snapshot({
  verbose: false,  // Mniejszy output (~50% reduction)
  filePath: "_TEMP/minimal_snapshot.txt"
})
```

Następnie czytaj plik po kawałku:
```javascript
// Read tylko pierwszych 500 linii (zamiast całości)
Read({
  file_path: "_TEMP/minimal_snapshot.txt",
  offset: 0,
  limit: 500
})
// Znajdź UID w tym fragmencie
```

---

## 📋 COMMON SCENARIOS - Optimized Queries

### Scenario 1: Verify NO wire:snapshot Issues

**❌ OLD WAY (25k+ tokens):**
```javascript
const snapshot = mcp__chrome-devtools__take_snapshot()
// Szukasz "wire:snapshot" w 25k+ tokenach
```

**✅ NEW WAY (<100 tokens):**
```javascript
// Method A: JS search (fastest!)
const check = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const html = document.documentElement.outerHTML;
    return {
      hasWireSnapshot: html.includes('wire:snapshot'),
      count: (html.match(/wire:snapshot/g) || []).length
    };
  }`
})
// Result: {hasWireSnapshot: false, count: 0} ✅

// Method B: Snapshot → file + grep (if need line numbers)
mcp__chrome-devtools__take_snapshot({
  verbose: false,
  filePath: "_TEMP/check_wire_snapshot.txt"
})

Grep({
  pattern: "wire:snapshot",
  path: "_TEMP/check_wire_snapshot.txt",
  output_mode: "content",
  "-n": true,
  head_limit: 5
})
// If no results: ✅ Clean
```

---

### Scenario 2: Check Disabled States (FIX #7/#8)

**❌ OLD WAY:**
```javascript
const snapshot = mcp__chrome-devtools__take_snapshot()
// Manually count disabled w 25k+ tokenach
```

**✅ NEW WAY (<100 tokens):**
```javascript
const disabledCheck = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
    const allButtons = document.querySelectorAll('button');

    return {
      checkboxes: {
        total: allCheckboxes.length,
        disabled: Array.from(allCheckboxes).filter(cb => cb.disabled).length,
        disabledList: Array.from(allCheckboxes)
          .filter(cb => cb.disabled)
          .slice(0, 10)
          .map(cb => ({
            name: cb.name,
            id: cb.id,
            classes: cb.className.substring(0, 50)
          }))
      },
      buttons: {
        total: allButtons.length,
        disabled: Array.from(allButtons).filter(btn => btn.disabled).length
      }
    };
  }`
})
// Result: ~200 tokenów
// {checkboxes: {total: 1176, disabled: 0, disabledList: []}, buttons: {total: 45, disabled: 0}}
```

---

### Scenario 3: Verify Specific Element State

**❌ OLD WAY:**
```javascript
const snapshot = mcp__chrome-devtools__take_snapshot()
// Szukasz "Ustaw główną" button w 25k+ tokenach
```

**✅ NEW WAY (<200 tokens):**
```javascript
const buttonCheck = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const button = Array.from(document.querySelectorAll('button'))
      .find(btn => btn.textContent.includes('Ustaw główną'));

    if (!button) return {found: false};

    return {
      found: true,
      text: button.textContent.trim(),
      disabled: button.disabled,
      visible: button.offsetParent !== null,
      classes: button.className,
      wireClick: button.getAttribute('wire:click')
    };
  }`
})
// Result: {found: true, text: "Ustaw główną", disabled: false, visible: true, ...}
```

---

### Scenario 4: Check Console Errors (already optimized!)

**✅ GOOD (już optimized):**
```javascript
const consoleCheck = mcp__chrome-devtools__list_console_messages({
  types: ["error", "warn"]
})
// Returns ONLY errors/warnings, not full console
```

---

### Scenario 5: Check Network (already optimized!)

**✅ GOOD (już optimized):**
```javascript
const networkCheck = mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["stylesheet", "script"],
  pageSize: 50  // Limit results
})
// Returns ONLY CSS/JS requests
```

---

### Scenario 6: Anti-Pattern Detection (inline styles, z-index)

**❌ OLD WAY:**
```javascript
const snapshot = mcp__chrome-devtools__take_snapshot()
// Manually search for style="..." in 25k+ tokenach
```

**✅ NEW WAY (<100 tokens):**
```javascript
const antiPatternCheck = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const inlineStyles = document.querySelectorAll('[style]');
    const inlineZIndex = Array.from(inlineStyles)
      .filter(el => el.style.zIndex);

    return {
      inlineStyles: {
        count: inlineStyles.length,
        examples: Array.from(inlineStyles)
          .slice(0, 5)
          .map(el => ({
            tag: el.tagName,
            style: el.getAttribute('style').substring(0, 100),
            classes: el.className.substring(0, 50)
          }))
      },
      inlineZIndex: {
        count: inlineZIndex.length,
        examples: inlineZIndex.slice(0, 5).map(el => ({
          tag: el.tagName,
          zIndex: el.style.zIndex,
          classes: el.className.substring(0, 50)
        }))
      }
    };
  }`
})
// Result: ~300 tokenów
// {inlineStyles: {count: 0, examples: []}, inlineZIndex: {count: 0, examples: []}} ✅
```

---

### Scenario 7: Find Element UID for Click/Fill

**Problem:** Potrzebujesz UID z snapshot, ale nie chcesz 25k tokenów

**Solution A: evaluate_script + Manual Click (jeśli możliwe):**
```javascript
// Get button index
const buttonInfo = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const buttons = Array.from(document.querySelectorAll('button'));
    const targetButton = buttons.find(btn => btn.textContent.includes('B2B Test DEV'));
    return {
      found: !!targetButton,
      index: targetButton ? buttons.indexOf(targetButton) : null,
      selector: targetButton ? 'button containing "B2B Test DEV"' : null
    };
  }`
})

// Jeśli MCP nie wymaga UID, użyj evaluate_script do click:
mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const button = Array.from(document.querySelectorAll('button'))
      .find(btn => btn.textContent.includes('B2B Test DEV'));
    if (button) {
      button.click();
      return {clicked: true};
    }
    return {clicked: false, error: 'Button not found'};
  }`
})
```

**Solution B: Minimal Snapshot (tylko interactive area):**
```javascript
// Step 1: Get bounding info
const area = mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const tabs = document.querySelector('.tabs-container'); // Adjust selector
    return {
      found: !!tabs,
      childCount: tabs ? tabs.children.length : 0
    };
  }`
})

// Step 2: Snapshot z verbose: false
mcp__chrome-devtools__take_snapshot({
  verbose: false,
  filePath: "_TEMP/tabs_snapshot.txt"
})

// Step 3: Read tylko relevant section
Read({
  file_path: "_TEMP/tabs_snapshot.txt",
  offset: 0,
  limit: 200  // Enough for tabs area
})
// Find UID in this small section
```

---

## 🎯 DECISION TREE

**Question: Co chcesz sprawdzić?**

### A) Text Pattern (wire:snapshot, error message, specific word)
→ **Pattern 2**: snapshot → file + Grep
```javascript
mcp__chrome-devtools__take_snapshot({filePath: "_TEMP/snapshot.txt"})
Grep({pattern: "wire:snapshot", path: "_TEMP/snapshot.txt", output_mode: "content", head_limit: 5})
```

### B) Element Properties (disabled, checked, visible, count)
→ **Pattern 1**: evaluate_script (targeted query)
```javascript
mcp__chrome-devtools__evaluate_script({
  function: "() => ({disabled: document.querySelectorAll('input[disabled]').length})"
})
```

### C) Console Errors/Warnings
→ **Already Optimized**: list_console_messages
```javascript
mcp__chrome-devtools__list_console_messages({types: ["error", "warn"]})
```

### D) Network Requests (HTTP 200 check)
→ **Already Optimized**: list_network_requests
```javascript
mcp__chrome-devtools__list_network_requests({resourceTypes: ["stylesheet"]})
```

### E) Visual Verification (layout, styling)
→ **Screenshot**: take_screenshot (reasonable size)
```javascript
mcp__chrome-devtools__take_screenshot({filePath: "_TEMP/screenshot.png"})
```

### F) UID dla Click/Fill
→ **Pattern 3**: evaluate_script click OR minimal snapshot
```javascript
// Option 1: Direct click via JS
mcp__chrome-devtools__evaluate_script({function: "() => {document.querySelector('button').click()}"})

// Option 2: Minimal snapshot
mcp__chrome-devtools__take_snapshot({verbose: false, filePath: "_TEMP/snap.txt"})
Read({file_path: "_TEMP/snap.txt", offset: 0, limit: 300})
```

---

## 📊 TOKEN COMPARISON

| Method | Tokens | Speed | Use Case |
|--------|--------|-------|----------|
| **Full snapshot** | 25000+ | Slow | ❌ Avoid! |
| **evaluate_script (targeted)** | 50-300 | Fast | ✅ Element checks |
| **snapshot → grep** | 100-500 | Medium | ✅ Text search |
| **Minimal snapshot (verbose: false)** | 10000-15000 | Medium | ⚠️ If UIDs needed |
| **Read (offset/limit)** | 500-2000 | Fast | ✅ Known section |
| **list_console_messages** | 100-1000 | Fast | ✅ Errors only |
| **list_network_requests** | 200-2000 | Fast | ✅ HTTP checks |
| **Screenshot** | 500-2000 | Medium | ✅ Visual only |

---

## ✅ BEST PRACTICES

### DO ✅

1. **Use evaluate_script() dla structured data**
   - Element counts, properties, state checks
   - Anti-pattern detection
   - Specific element search

2. **Use snapshot → file + Grep dla text search**
   - wire:snapshot detection
   - Error message search
   - Specific text patterns

3. **Use verbose: false gdy musisz mieć snapshot**
   - Reduces output by ~50%
   - Still provides UIDs

4. **Use Read z offset/limit dla dużych snapshots**
   - Read only relevant sections
   - Iterative reading jeśli potrzeba

5. **Use filePath parameter ALWAYS**
   - Nie wczytuj wyniku do context
   - Read tylko gdy potrzeba

### DON'T ❌

1. **NIE używaj full snapshot bez filePath**
   - 25k+ tokenów w context!

2. **NIE czytaj całego snapshot file naraz**
   - Use offset/limit w Read

3. **NIE używaj snapshot gdy evaluate_script wystarczy**
   - evaluate_script: 50 tokenów vs snapshot: 25000

4. **NIE używaj verbose: true bez powodu**
   - Więcej tokenów, rzadko potrzebne

---

## 🚀 TEMPLATE: Optimized Verification Workflow

```javascript
// === OPTIMIZED CHROME DEVTOOLS VERIFICATION ===

// 1. Navigate (standard)
mcp__chrome-devtools__navigate_page({
  type: "url",
  url: "https://ppm.mpptrade.pl/admin/products",
  ignoreCache: true
})

// 2. Console check (already optimized)
const consoleCheck = mcp__chrome-devtools__list_console_messages({
  types: ["error", "warn"]
})
// ✅ Pass if: length === 0

// 3. Network check (already optimized)
const networkCheck = mcp__chrome-devtools__list_network_requests({
  resourceTypes: ["stylesheet", "script"],
  pageSize: 50
})
// ✅ Pass if: all HTTP 200

// 4. wire:snapshot check (OPTIMIZED - snapshot → file + grep)
mcp__chrome-devtools__take_snapshot({
  verbose: false,
  filePath: "_TEMP/snapshot_check.txt"
})

Grep({
  pattern: "wire:snapshot",
  path: "_TEMP/snapshot_check.txt",
  output_mode: "content",
  head_limit: 5
})
// ✅ Pass if: no matches

// 5. Disabled state check (OPTIMIZED - evaluate_script)
const disabledCheck = mcp__chrome-devtools__evaluate_script({
  function: `() => ({
    checkboxes: {
      total: document.querySelectorAll('input[type="checkbox"]').length,
      disabled: document.querySelectorAll('input[type="checkbox"][disabled]').length
    },
    buttons: {
      total: document.querySelectorAll('button').length,
      disabled: document.querySelectorAll('button[disabled]').length
    }
  })`
})
// ✅ Pass if: disabled counts === 0 (lub expected)

// 6. Anti-pattern check (OPTIMIZED - evaluate_script)
const antiPatterns = mcp__chrome-devtools__evaluate_script({
  function: `() => ({
    inlineStyles: document.querySelectorAll('[style]').length,
    inlineZIndex: Array.from(document.querySelectorAll('[style]'))
      .filter(el => el.style.zIndex).length
  })`
})
// ✅ Pass if: both === 0

// 7. Screenshot (visual - reasonable size)
mcp__chrome-devtools__take_screenshot({
  fullPage: true,
  filePath: "_TOOLS/screenshots/verification_2025-11-21.png"
})
// ✅ Visual check

// === TOTAL TOKENS: ~2000-3000 (zamiast 30000+) ===
// === TIME SAVED: ~80% ===
```

---

## 📚 EXAMPLES

### Example 1: Full Verification (Optimized - 2k tokens)

**Before (OLD):**
```javascript
// ❌ 30000+ tokens
const snapshot = mcp__chrome-devtools__take_snapshot() // 25000
const console = mcp__chrome-devtools__list_console_messages() // 1000
const network = mcp__chrome-devtools__list_network_requests() // 2000
const screenshot = mcp__chrome-devtools__take_screenshot() // 2000
// TOTAL: 30000 tokens
```

**After (NEW):**
```javascript
// ✅ 2000-3000 tokens
const console = mcp__chrome-devtools__list_console_messages({types: ["error"]}) // 200
const network = mcp__chrome-devtools__list_network_requests({resourceTypes: ["stylesheet"]}) // 500

mcp__chrome-devtools__take_snapshot({verbose: false, filePath: "_TEMP/snap.txt"}) // 0 (file)
Grep({pattern: "wire:snapshot", path: "_TEMP/snap.txt", output_mode: "content", head_limit: 3}) // 100

const disabled = mcp__chrome-devtools__evaluate_script({
  function: "() => ({disabled: document.querySelectorAll('[disabled]').length})"
}) // 50

const antiPatterns = mcp__chrome-devtools__evaluate_script({
  function: "() => ({inline: document.querySelectorAll('[style]').length})"
}) // 50

mcp__chrome-devtools__take_screenshot({filePath: "_TEMP/screen.png"}) // 1000

// TOTAL: ~2000 tokens (85% reduction!)
```

---

### Example 2: Livewire Component Check (Optimized - 500 tokens)

```javascript
// Navigate + click tab
mcp__chrome-devtools__navigate_page({type: "url", url: "https://ppm.mpptrade.pl/admin/products"})

// Click via evaluate_script (no UID needed!)
mcp__chrome-devtools__evaluate_script({
  function: `() => {
    const tab = Array.from(document.querySelectorAll('button'))
      .find(btn => btn.textContent.includes('B2B Test DEV'));
    if (tab) tab.click();
    return {clicked: !!tab};
  }`
})

// Wait for wire:poll
await new Promise(resolve => setTimeout(resolve, 6000))

// Check state (targeted!)
const state = mcp__chrome-devtools__evaluate_script({
  function: `() => ({
    checkboxes: {
      total: document.querySelectorAll('input[type="checkbox"]').length,
      disabled: document.querySelectorAll('input[disabled]').length,
      checked: document.querySelectorAll('input:checked').length
    },
    livewire: {
      components: window.Livewire?.components?.componentsByName('product-form')?.length || 0
    }
  })`
})
// Result: ~200 tokens (zamiast 25k snapshot!)

// Quick wire:snapshot check
mcp__chrome-devtools__take_snapshot({verbose: false, filePath: "_TEMP/quick_check.txt"})
Grep({pattern: "wire:snapshot", path: "_TEMP/quick_check.txt", output_mode: "files_with_matches"})
// Result: 0 matches ✅

// TOTAL: ~500 tokens (95% reduction!)
```

---

## 🎓 Summary

**Key Takeaways:**

1. **evaluate_script() = Primary Tool** dla structured checks (50-300 tokens)
2. **snapshot → file + Grep** dla text search (100-500 tokens)
3. **verbose: false** jeśli snapshot needed (50% reduction)
4. **Read z offset/limit** dla targeted file reading
5. **filePath ALWAYS** - nie wczytuj do context

**Token Savings:**
- Full snapshot: 25000 tokens
- Optimized approach: 500-3000 tokens
- **Reduction: 85-95%** ✅

**Speed Improvement:**
- Full snapshot: ~5-10s
- Optimized queries: ~1-3s
- **Faster by 3-5x** ✅

---

**Use These Patterns! 🚀**
