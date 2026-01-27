# Verification: Playwright MCP Token Optimization (MANDATORY)

## Critical Rule
**NEVER** use `browser_snapshot` for full page - it generates 10-20k tokens!
**ALWAYS** use screenshots + targeted searches instead!

## Token Usage Comparison

| Method | Tokens | Use Case |
|--------|--------|----------|
| `browser_snapshot` (full page) | 10,000-20,000 | **AVOID!** |
| `browser_take_screenshot` | ~500 (image) | Visual verification |
| `browser_run_code` (targeted) | 100-500 | Get specific values |
| `browser_evaluate` | 100-300 | Quick JS checks |

## Correct Workflow (Token-Efficient)

### 1. Navigate (minimal tokens)
```javascript
mcp__plugin_playwright_playwright__browser_navigate({ url: "https://..." })
```

### 2. Screenshot for visual verification
```javascript
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })
```

### 3. Get specific values with JS (NOT snapshot!)
```javascript
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => {
    return {
      producent: await page.$eval('input[name="manufacturer"]', el => el.value).catch(() => null),
      checkbox: await page.$eval('#shop-internet', el => el.checked).catch(() => false),
    };
  }`
})
```

### 4. Click elements by selector
```javascript
mcp__plugin_playwright_playwright__browser_click({ ref: "button-id" })
// OR use browser_run_code:
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => {
    await page.click('button:has-text("Pobierz dane")');
  }`
})
```

## Anti-Patterns (AVOID!)

### ❌ WRONG - Full snapshot (10-20k tokens!)
```javascript
mcp__plugin_playwright_playwright__browser_snapshot()  // GENERATES HUGE OUTPUT!
```

### ❌ WRONG - Snapshot with filename still reads full DOM
```javascript
mcp__plugin_playwright_playwright__browser_snapshot({ filename: "page.md" })
```

## Correct Patterns

### ✅ Screenshot + targeted JS
```javascript
// 1. Visual check
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })

// 2. Get specific values
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => ({
    title: await page.title(),
    fieldValue: await page.$eval('#my-field', el => el.value).catch(() => ''),
    isChecked: await page.$eval('#my-checkbox', el => el.checked).catch(() => false),
    buttonExists: await page.$('button.submit') !== null
  })`
})
```

### ✅ Find element and click
```javascript
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => {
    const btn = await page.$('button:has-text("Zapisz")');
    if (btn) await btn.click();
    return { clicked: !!btn };
  }`
})
```

### ✅ Wait and verify
```javascript
mcp__plugin_playwright_playwright__browser_wait_for({ text: "Dane zapisane" })
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })
```

## PPM Verification Workflow (Optimized)

```javascript
// 1. Navigate
mcp__plugin_playwright_playwright__browser_navigate({ url: "https://ppm.mpptrade.pl/admin/products/123/edit" })

// 2. Screenshot to see layout
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })

// 3. Get form values with JS
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => ({
    sku: await page.$eval('input[placeholder*="SKU"]', el => el.value).catch(() => ''),
    manufacturer: await page.$eval('input[placeholder*="Honda"]', el => el.value).catch(() => ''),
    shopInternet: await page.$eval('input[type="checkbox"]', el => el.checked).catch(() => false),
  })`
})

// 4. Click button
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => {
    await page.click('button:has-text("Pobierz dane")');
  }`
})

// 5. Wait for result
mcp__plugin_playwright_playwright__browser_wait_for({ time: 2 })

// 6. Final screenshot
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })
```

## Console Errors (Low Tokens)
```javascript
mcp__plugin_playwright_playwright__browser_console_messages({ level: "error" })
```

## Summary: Token-Efficient Tools

| Need | Tool | Tokens |
|------|------|--------|
| See page visually | `browser_take_screenshot` | ~500 |
| Get field values | `browser_run_code` | 100-500 |
| Click element | `browser_run_code` / `browser_click` | 100-200 |
| Check errors | `browser_console_messages` | 100-500 |
| Wait for text | `browser_wait_for` | ~50 |

**NEVER USE:** `browser_snapshot` without extreme necessity!
