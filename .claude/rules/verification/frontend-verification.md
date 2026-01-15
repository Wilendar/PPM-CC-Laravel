# Verification: Frontend Changes (MANDATORY)

## Critical Rule
**ALWAYS** verify UI changes BEFORE informing user about completion!
**NEVER** say "Gotowe" without screenshot/Chrome DevTools verification!

## Mandatory Workflow
```
1. Implement changes (CSS/Blade/HTML)
2. Build assets: npm run build
3. Deploy to production
4. VERIFY with Chrome DevTools MCP
5. IF OK -> Inform user
6. IF PROBLEM -> Fix -> Repeat 3-5
```

## Forbidden Workflow
```
1. Change admin.blade.php
2. Upload to production
3. Clear cache
4. "Gotowe! Sidebar naprawiony!" <- WITHOUT VERIFICATION!
```

## What to Check on Screenshots

### Layout & Positioning
- [ ] Sidebar NOT overlaying content
- [ ] All columns visible and clickable
- [ ] Header not overlaying content
- [ ] No unexpected gaps/whitespace

### Responsive Design
- [ ] Desktop breakpoints work (>1024px)
- [ ] Tablet view OK (768px-1024px)
- [ ] Mobile view OK (<768px)

### Components
- [ ] Modals render on top (z-index)
- [ ] Dropdowns visible
- [ ] Forms layout consistent

## Red Flags (DO NOT INFORM USER!)
- Sidebar overlays content
- Columns not clickable
- Horizontal scroll appears
- Modal hidden under header
- Console errors in DevTools

## Verification Commands
```javascript
// Navigate
mcp__chrome-devtools__navigate_page({ type: "url", url: "https://ppm.mpptrade.pl/admin/products" })

// Check console
mcp__chrome-devtools__list_console_messages({ types: ["error", "warn"] })

// Check network
mcp__chrome-devtools__list_network_requests({ resourceTypes: ["stylesheet", "script"] })

// Screenshot
mcp__chrome-devtools__take_screenshot({ format: "jpeg", quality: 85, filePath: "_TEMP/verify.jpg" })
```
