# HookCreator Skill - Enable & Troubleshooting

**Data:** 2025-10-31
**Status:** ✅ RESOLVED

## Problem

HookCreator skill **nie uruchamiał się automatycznie** podczas tworzenia hooków, mimo że:
- ✅ Skill istnieje w globalnym folderze: `C:\Users\kamil\.claude\skills\HookCreator\`
- ✅ Skill.md jest poprawnie sformatowany
- ✅ Skill ma trigger phrases: "create a hook", "generate hook", etc.

## Root Cause

**Skill nie był w permissions allow list** w `.claude/settings.local.json`!

Claude Code wymaga **explicit permission** dla każdego skilla, nawet jeśli skill jest w globalnym folderze.

### Sprawdzenie

```bash
# Check if skill enabled
cat .claude/settings.local.json | grep "HookCreator"

# Jeśli brak outputu = skill NOT enabled
```

## Rozwiązanie

**⭐ PREFEROWANE: Global Settings (dla wszystkich projektów)**

Dodaj `Skill(HookCreator)` do **globalnego** settings:

```json
// C:\Users\kamil\.claude\settings.local.json (GLOBAL)
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",         // ← DODAJ TEN WIERSZ
      "Bash(pwsh:*)",
      "Bash(python:*)",
      "..."
    ]
  }
}
```

**Alternatywnie: Project-Specific Settings (tylko dla PPM-CC-Laravel)**

Dodaj `Skill(HookCreator)` do projektu `.claude/settings.local.json`:

```json
{
  "permissions": {
    "allow": [
      "Skill(livewire-troubleshooting)",
      "Skill(context7-docs-lookup)",
      "Skill(hostido-deployment)",
      "Skill(frontend-verification)",
      "Skill(skill-creator)",
      "Skill(HookCreator)",         // ← DODAJ TEN WIERSZ
      "..."
    ]
  }
}
```

### Krok po kroku (Global Settings - RECOMMENDED):

1. Open `C:\Users\kamil\.claude\settings.local.json` (GLOBAL)
2. Find `"permissions": { "allow": [`
3. Add line: `"Skill(HookCreator)",`
4. Save file
5. **Restart Claude Code** (skills require restart to activate)
6. ✅ Skill działa we **wszystkich projektach**!

### Krok po kroku (Project Settings - jeśli potrzebujesz tylko dla PPM):

1. Open `.claude/settings.local.json` (w projekcie)
2. Find `"permissions": { "allow": [`
3. Add line: `"Skill(HookCreator)",`
4. Save file
5. **Restart Claude Code** (skills require restart to activate)
6. ✅ Skill działa tylko w **tym projekcie**

## Weryfikacja

Po restarcie Claude Code, skill powinien działać automatycznie gdy użytkownik użyje trigger phrases:

### Trigger Phrases (z skill.md):

```
"create a hook for..."
"generate hook that..."
"make a SessionStart hook..."
"build custom hook..."
"create PreCompact hook..."
"fix hook that..."
"debug this hook..."
```

### Test:

Po restarcie, napisz:
```
"Create a hook that validates file paths before Write tool"
```

**Expected:** Claude uruchomi HookCreator skill i:
1. Zaanalizuje requirement
2. Wybierze PreToolUse hook type
3. Stworzy Python implementation z template
4. Doda configuration snippet dla settings.json
5. Przetestuje hook

## Skills Location Architecture

### Globalny Katalog Skills
```
C:\Users\kamil\.claude\skills\
├── HookCreator\
│   ├── skill.md          ← Skill definition
│   └── README.md         ← Documentation
├── agent-report-writer\
├── context7-docs-lookup\
├── debug-log-cleanup\
├── frontend-verification\
├── hostido-deployment\
├── issue-documenter\
├── livewire-troubleshooting\
├── ppm-architecture-compliance\
├── project-plan-manager\
└── skill-creator\
```

### Project-Specific Skills

Można też tworzyć project-specific skills w `.claude/skills/` (w projekcie), ale:
- ⚠️ Wymaga dodania do permissions w project settings.local.json
- ⚠️ Nie będzie dostępny w innych projektach

**Recommendation:** Global skills dla uniwersalnych funkcji (jak HookCreator), project-specific tylko dla bardzo specific cases.

## HookCreator Skill Capabilities

Po enable, skill automatycznie:

### 1. Analizuje User Intent
- Co user chce zautomatyzować?
- Kiedy hook powinien się uruchomić?
- Czy hook powinien blokować action?

### 2. Wybiera Hook Type & Matcher
- PreToolUse (before tool execution)
- PostToolUse (after tool execution)
- SessionStart (at session start)
- UserPromptSubmit (on every prompt)
- PreCompact (before compaction)
- etc.

### 3. Generuje Python Implementation
- Complete working hook z template
- UTF-8 encoding fix (Windows)
- Dual output system (Claude + User CLI)
- Error handling
- stdin consumption (CRITICAL!)

### 4. Tworzy Configuration
- settings.json snippet
- Permissions entry
- Proper matcher pattern
- Timeout recommendation

### 5. Testuje Hook
- Manual test command
- Expected behavior
- Troubleshooting tips

## Example: Using HookCreator After Enable

**User:** "Create a hook that checks if Context7 is available before Write tool"

**Claude (with HookCreator skill):**

```
I'll use the HookCreator skill to create this enforcement hook.

[Skill activates]

Analysis:
- Event: Before Write tool → PreToolUse
- Action: Check Context7 availability → validation
- Should block?: Optional (warn but allow)

Creating Python hook...
[generates complete hook.py]

Configuration for settings.json...
[generates config snippet]

Testing:
python .claude/hooks/context7_check.py

Would you like me to:
1. Test this hook now?
2. Add it to your settings.json?
3. Adjust the behavior?
```

## Common Issues

### Issue: Skill still doesn't activate after adding to permissions

**Solution:** Restart Claude Code! Skills require full restart to load.

### Issue: Multiple skills with similar names

**Check exact name:**
```bash
ls "C:\Users\kamil\.claude\skills\"
```

Use exact folder name in permissions:
- `Skill(HookCreator)` ✅ (matches folder)
- `Skill(hook-creator)` ❌ (wrong case)

### Issue: Skill conflicts with another

If two skills have overlapping trigger phrases, Claude might choose wrong one.

**Solution:** Be more specific in prompt:
- "Use HookCreator skill to..." (explicit)
- "Create a hook..." (may trigger wrong skill)

## Related Files

- **Skill Location:** `C:\Users\kamil\.claude\skills\HookCreator\skill.md`
- **Global Settings:** `C:\Users\kamil\.claude\settings.local.json` (⭐ PREFEROWANE)
- **Project Settings:** `.claude/settings.local.json` (optional)
- **Existing Hooks:** `_TOOLS/` (project-specific)
- **Hook Documentation:** `_DOCS/HOOKS_SYSTEM_OVERVIEW.md`
- **Global vs Project Guide:** `_DOCS/GLOBAL_VS_PROJECT_PERMISSIONS.md` (⭐ NOWY!)

## Why session_start_rules_reminder.py is NOT Global

**Question:** "Should session_start_rules_reminder.py be global?"

**Answer:** **NO** - Hook is **project-specific** because zawiera:
- PPM-CC-Laravel specific rules (hardcoded)
- Deployment info (ppm.mpptrade.pl, SSH credentials)
- Context7 config (specific API key, libraries)
- Docs list (project-specific paths)

**Better approach:**
- ✅ HookCreator skill (global) - creates hooks for any project
- ✅ session_start_rules_reminder.py (project-specific) - customized for PPM
- ✅ Each project gets own customized session start hook

**Workflow for new project:**
1. Use HookCreator skill: "Create SessionStart hook for [project]"
2. HookCreator generates template
3. Customize with project-specific rules
4. Place in project's _TOOLS/ or .claude/hooks/

## Summary

### Problem
- HookCreator skill existed but didn't activate

### Root Cause
- Missing from permissions.allow in settings files

### Solution (UPDATED 2025-10-31)
- **⭐ PREFEROWANE:** Added `Skill(HookCreator)` to **GLOBAL** settings (`C:\Users\kamil\.claude\settings.local.json`)
- Alternative: Add to project settings (`.claude/settings.local.json`)
- Restart Claude Code

### Result
- ✅ Skill now activates on trigger phrases
- ✅ **Działa we wszystkich projektach** (global settings)
- ✅ Automatic hook creation with best practices
- ✅ Complete template-based implementation
- ✅ Testing and configuration included

---

**Last Updated:** 2025-10-31
**Status:** ✅ RESOLVED - Skill enabled globally!
**Location:**
- **Global:** `C:\Users\kamil\.claude\settings.local.json` line 4 (⭐ ACTIVE)
- Project: `.claude/settings.local.json` line 79 (optional, can be removed)

**See Also:** `_DOCS/GLOBAL_VS_PROJECT_PERMISSIONS.md` for complete architecture guide
