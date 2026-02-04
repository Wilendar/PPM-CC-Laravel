#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Hook Name: Playwright Token Optimization Reminder
Hook Type: PreToolUse
Purpose: Remind about token optimization rules before using Playwright MCP tools

Matcher: mcp__plugin_playwright_playwright__.*
When it fires: Before any Playwright MCP tool call
Reference: .claude/rules/verification/playwright-token-optimization.md
"""

import sys
import json

# Token-expensive tools that need special warning
HIGH_TOKEN_TOOLS = [
    'browser_snapshot',
]

# Token-efficient tools (just confirmation)
EFFICIENT_TOOLS = [
    'browser_take_screenshot',
    'browser_run_code',
    'browser_evaluate',
    'browser_wait_for',
    'browser_console_messages',
]

def main():
    try:
        # CRITICAL: Always consume stdin
        stdin_data = sys.stdin.read()

        try:
            hook_input = json.loads(stdin_data) if stdin_data else {}
        except:
            hook_input = {}

        tool_name = hook_input.get('tool_name', '')
        tool_input = hook_input.get('tool_input', {})

        # Extract just the tool suffix
        tool_suffix = tool_name.replace('mcp__plugin_playwright_playwright__', '')

        # Check for high-token tools
        if tool_suffix in HIGH_TOKEN_TOOLS:
            output = {
                "hookSpecificOutput": {
                    "hookEventName": "PreToolUse",
                    "additionalContext": """
## ⚠️ PLAYWRIGHT TOKEN OPTIMIZATION WARNING

**UNIKAJ browser_snapshot!** Generuje 10,000-20,000 tokenow!

### ZAMIAST browser_snapshot UZYWAJ:
1. `browser_take_screenshot` (~500 tokenow) - wizualna weryfikacja
2. `browser_run_code` (100-500 tokenow) - pobranie konkretnych wartosci
3. `browser_evaluate` (100-300 tokenow) - szybkie sprawdzenia JS

### POPRAWNY WORKFLOW:
```javascript
// 1. Screenshot dla wizualnej weryfikacji
mcp__plugin_playwright_playwright__browser_take_screenshot({ type: "png" })

// 2. Pobranie konkretnych wartosci przez JS
mcp__plugin_playwright_playwright__browser_run_code({
  code: `async (page) => ({
    title: await page.title(),
    fieldValue: await page.$eval('#my-field', el => el.value).catch(() => ''),
    buttonExists: await page.$('button.submit') !== null
  })`
})
```

### Dokumentacja: .claude/rules/verification/playwright-token-optimization.md
"""
                }
            }
            print(json.dumps(output))

        elif tool_suffix in EFFICIENT_TOOLS:
            # Token-efficient tool - just brief confirmation
            output = {
                "hookSpecificOutput": {
                    "hookEventName": "PreToolUse",
                    "additionalContext": f"✅ Playwright: {tool_suffix} - token-efficient choice"
                }
            }
            print(json.dumps(output))

        else:
            # Other Playwright tools - general reminder
            output = {
                "hookSpecificOutput": {
                    "hookEventName": "PreToolUse",
                    "additionalContext": """
## PLAYWRIGHT OPTIMIZATION REMINDER

Preferuj token-efficient tools:
- `browser_take_screenshot` zamiast `browser_snapshot`
- `browser_run_code` do pobierania konkretnych wartosci
- `browser_evaluate` do szybkich sprawdzen JS

Ref: .claude/rules/verification/playwright-token-optimization.md
"""
                }
            }
            print(json.dumps(output))

    except Exception as e:
        # Graceful failure - don't block
        print(f"Hook error: {e}", file=sys.stderr)

    sys.exit(0)

if __name__ == "__main__":
    main()
