# Verification: Claude in Chrome (MANDATORY)

> **Note:** Claude in Chrome to oficjalna wtyczka do Chrome od Anthropic (NOT an MCP!).
> Funkcje używają prefixu `mcp__claude-in-chrome__*` ale to notacja interfejsu.

## Critical Rule
**NEVER** inform user "Gotowe" without Claude in Chrome verification!

## Token Optimization
`read_page()` can return large content. Use targeted approaches.

## Complete Tool Reference

### 1. Tab Management (ALWAYS FIRST!)

| Tool | Description |
|------|-------------|
| `tabs_context_mcp` | Get tab context - **MUST call first!** |
| `tabs_create_mcp` | Create new tab in MCP group |

```javascript
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })
mcp__claude-in-chrome__tabs_create_mcp()
```

### 2. Navigation

| Tool | Description |
|------|-------------|
| `navigate` | Navigate to URL or back/forward |

```javascript
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://example.com" })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "back" })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "forward" })
```

### 3. Page Reading

| Tool | Description |
|------|-------------|
| `read_page` | Get accessibility tree (use depth limit!) |
| `find` | Find elements by natural language |
| `get_page_text` | Extract raw text content |

```javascript
// Depth-limited (recommended)
mcp__claude-in-chrome__read_page({ tabId: TAB_ID, depth: 5 })
mcp__claude-in-chrome__read_page({ tabId: TAB_ID, filter: "interactive" })
mcp__claude-in-chrome__read_page({ tabId: TAB_ID, ref_id: "ref_123", depth: 3 })

// Natural language search
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "save button" })
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "error messages" })

// Text extraction
mcp__claude-in-chrome__get_page_text({ tabId: TAB_ID })
```

### 4. Computer Tool (Mouse/Keyboard/Screenshot)

| Action | Description |
|--------|-------------|
| `screenshot` | Take screenshot |
| `left_click` | Click (by ref or coordinate) |
| `right_click` | Right click |
| `double_click` | Double click |
| `triple_click` | Triple click |
| `type` | Type text |
| `key` | Press keyboard key |
| `scroll` | Scroll in direction |
| `scroll_to` | Scroll element into view |
| `hover` | Hover over element |
| `wait` | Wait seconds |
| `zoom` | Screenshot specific region |
| `left_click_drag` | Drag and drop |

```javascript
// Screenshot
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })

// Click by ref
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "left_click", ref: "ref_123" })

// Click by coordinates
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "left_click", coordinate: [100, 200] })

// With modifiers
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "left_click", coordinate: [100, 200], modifiers: "ctrl" })

// Type text
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "type", text: "Hello World" })

// Keyboard
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "key", text: "Enter" })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "key", text: "ctrl+a" })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "key", text: "Backspace", repeat: 5 })

// Scroll
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "scroll", coordinate: [500, 300], scroll_direction: "down", scroll_amount: 3 })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "scroll_to", ref: "ref_123" })

// Hover
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "hover", ref: "ref_123" })

// Wait
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "wait", duration: 2 })

// Zoom region
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "zoom", region: [100, 100, 400, 300] })

// Drag
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "left_click_drag", start_coordinate: [100, 100], coordinate: [200, 200] })
```

### 5. Form Input

| Tool | Description |
|------|-------------|
| `form_input` | Set form element values |

```javascript
mcp__claude-in-chrome__form_input({ tabId: TAB_ID, ref: "ref_123", value: "text" })
mcp__claude-in-chrome__form_input({ tabId: TAB_ID, ref: "ref_123", value: true })  // checkbox
mcp__claude-in-chrome__form_input({ tabId: TAB_ID, ref: "ref_123", value: "option1" })  // select
```

### 6. JavaScript Execution

| Tool | Description |
|------|-------------|
| `javascript_tool` | Execute JS in page context |

```javascript
mcp__claude-in-chrome__javascript_tool({
  tabId: TAB_ID,
  action: "javascript_exec",
  text: "document.querySelectorAll('[disabled]').length"
})

mcp__claude-in-chrome__javascript_tool({
  tabId: TAB_ID,
  action: "javascript_exec",
  text: "({ title: document.title, url: location.href })"
})
```

### 7. Console & Network

| Tool | Description |
|------|-------------|
| `read_console_messages` | Read browser console |
| `read_network_requests` | Read HTTP requests |

```javascript
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, onlyErrors: true })
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, pattern: "error|warning" })
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, clear: true })

mcp__claude-in-chrome__read_network_requests({ tabId: TAB_ID })
mcp__claude-in-chrome__read_network_requests({ tabId: TAB_ID, urlPattern: "/api/" })
```

### 8. Window Management

| Tool | Description |
|------|-------------|
| `resize_window` | Resize browser window |

```javascript
mcp__claude-in-chrome__resize_window({ tabId: TAB_ID, width: 1920, height: 1080 })
mcp__claude-in-chrome__resize_window({ tabId: TAB_ID, width: 768, height: 1024 })  // Tablet
mcp__claude-in-chrome__resize_window({ tabId: TAB_ID, width: 375, height: 812 })  // Mobile
```

### 9. GIF Recording

| Tool | Description |
|------|-------------|
| `gif_creator` | Record/export GIF |

```javascript
mcp__claude-in-chrome__gif_creator({ tabId: TAB_ID, action: "start_recording" })
mcp__claude-in-chrome__gif_creator({ tabId: TAB_ID, action: "stop_recording" })
mcp__claude-in-chrome__gif_creator({ tabId: TAB_ID, action: "export", download: true, filename: "recording.gif" })
mcp__claude-in-chrome__gif_creator({ tabId: TAB_ID, action: "clear" })
```

### 10. Image Upload

| Tool | Description |
|------|-------------|
| `upload_image` | Upload to file input or drag target |

```javascript
mcp__claude-in-chrome__upload_image({ tabId: TAB_ID, imageId: "SCREENSHOT_ID", ref: "ref_123" })
mcp__claude-in-chrome__upload_image({ tabId: TAB_ID, imageId: "SCREENSHOT_ID", coordinate: [500, 300] })
```

### 11. Plan Presentation

| Tool | Description |
|------|-------------|
| `update_plan` | Present plan for user approval |

```javascript
mcp__claude-in-chrome__update_plan({
  domains: ["github.com", "ppm.mpptrade.pl"],
  approach: ["Navigate to admin panel", "Check product list"]
})
```

### 12. Shortcuts

| Tool | Description |
|------|-------------|
| `shortcuts_list` | List available shortcuts |
| `shortcuts_execute` | Execute shortcut |

```javascript
mcp__claude-in-chrome__shortcuts_list({ tabId: TAB_ID })
mcp__claude-in-chrome__shortcuts_execute({ tabId: TAB_ID, command: "debug" })
```

## PPM Verification Workflow

```javascript
// 1. Get tab context (MANDATORY!)
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })

// 2. Navigate
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/products" })

// 3. Check errors
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, onlyErrors: true })

// 4. Find elements
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "error messages" })

// 5. Screenshot
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

## Token Optimization

| Need | Use | Tokens |
|------|-----|--------|
| Find elements | `find()` | 50-300 |
| Element state | `javascript_tool()` | 50-200 |
| Interactive only | `read_page({ filter: "interactive" })` | 500-2000 |
| Console errors | `read_console_messages({ onlyErrors: true })` | 100-500 |
| Visual layout | `computer({ action: "screenshot" })` | Image |

## Anti-Patterns (AVOID!)

- Using tools WITHOUT `tabs_context_mcp()` first
- `read_page()` without depth limit
- Assuming "works because build passed"
- Informing user WITHOUT verification
- Reusing tab IDs from previous sessions

## Decision Tree

| Need to Check | Use | Tokens |
|---------------|-----|--------|
| Element exists/visible | `find({ query: "..." })` | 50-300 |
| Element properties (disabled, checked) | `javascript_tool()` | 50-200 |
| Interactive elements only | `read_page({ filter: "interactive" })` | 500-2000 |
| Full DOM structure | `read_page({ depth: 5 })` | 1000-5000 |
| Text content | `get_page_text()` | 500-3000 |
| Console errors | `read_console_messages({ onlyErrors: true })` | 100-500 |
| Network requests | `read_network_requests({ urlPattern: "/api/" })` | 200-1000 |
| Visual layout/styling | `computer({ action: "screenshot" })` | Image |

## Key Differences from DevTools MCP

| DevTools MCP (OLD) | Claude in Chrome (NEW) |
|--------------------|------------------------|
| `mcp__chrome-devtools__navigate_page({ type: "url", url: X })` | `mcp__claude-in-chrome__navigate({ tabId: ID, url: X })` |
| `mcp__chrome-devtools__take_snapshot()` | `mcp__claude-in-chrome__read_page({ tabId: ID })` |
| `mcp__chrome-devtools__take_screenshot()` | `mcp__claude-in-chrome__computer({ tabId: ID, action: "screenshot" })` |
| `mcp__chrome-devtools__click({ uid: X })` | `mcp__claude-in-chrome__computer({ tabId: ID, action: "left_click", ref: X })` |
| `mcp__chrome-devtools__fill({ uid: X, value: Y })` | `mcp__claude-in-chrome__form_input({ tabId: ID, ref: X, value: Y })` |
| `mcp__chrome-devtools__evaluate_script({ function: F })` | `mcp__claude-in-chrome__javascript_tool({ tabId: ID, action: "javascript_exec", text: F })` |
| `mcp__chrome-devtools__list_console_messages()` | `mcp__claude-in-chrome__read_console_messages({ tabId: ID })` |
| `mcp__chrome-devtools__list_network_requests()` | `mcp__claude-in-chrome__read_network_requests({ tabId: ID })` |
| `mcp__chrome-devtools__select_page({ pageIdx: N })` | `tabs_context_mcp` + use tabId from context |
| N/A | `mcp__claude-in-chrome__find({ tabId: ID, query: "..." })` |

**Key Architectural Difference:** DevTools MCP uses `pageIdx`, Claude in Chrome uses `tabId` from `tabs_context_mcp()`

## Success Pattern
```
tabs_context_mcp -> navigate -> find/javascript checks -> console -> screenshot -> Report
```
