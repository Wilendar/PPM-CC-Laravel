# Claude Code: Global vs Project-Specific Permissions

**Data:** 2025-10-31
**Status:** ✅ RESOLVED

## Pytanie

Czy globalny skill musi być dodany do lokalnego settings projektu aby działał? Czy można go dodać globalnie dla wszystkich projektów?

## Odpowiedź

**✅ TAK! Skills mogą być włączone globalnie dla wszystkich projektów!**

Claude Code ma **DWIE lokalizacje** dla permissions:

### 1. GLOBALNY Settings (dla wszystkich projektów)

```
C:\Users\kamil\.claude\settings.local.json
```

**Permissions dodane tutaj działają we WSZYSTKICH projektach.**

### 2. Project-Specific Settings (tylko dla danego projektu)

```
[projekt]/.claude/settings.local.json
```

**Permissions dodane tutaj działają TYLKO w tym projekcie.**

---

## Architektura Permissions

### Merge Strategy

Claude Code **merguje** permissions z obu lokalizacji:

```
Effective Permissions = Global Permissions + Project-Specific Permissions
```

**Przykład:**

**Global (`~/.claude/settings.local.json`):**
```json
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",
      "Bash(pwsh:*)",
      "Bash(python:*)"
    ]
  }
}
```

**Project-Specific (`.claude/settings.local.json`):**
```json
{
  "permissions": {
    "allow": [
      "Skill(livewire-troubleshooting)",
      "Skill(hostido-deployment)",
      "Bash(php artisan:*)"
    ]
  }
}
```

**Effective Permissions (merged):**
- ✅ `Skill(HookCreator)` (global)
- ✅ `Skill(livewire-troubleshooting)` (project)
- ✅ `Skill(hostido-deployment)` (project)
- ✅ `Bash(pwsh:*)` (global)
- ✅ `Bash(python:*)` (global)
- ✅ `Bash(php artisan:*)` (project)

---

## Kiedy Używać Global vs Project-Specific?

### ✅ GLOBAL - Użyj dla:

1. **Universal Skills** - przydatne we wszystkich projektach
   - `Skill(HookCreator)` - tworzenie hooków dla dowolnego projektu
   - `Skill(skill-creator)` - universal skill creation

2. **Common Tools** - używane wszędzie
   - `Bash(pwsh:*)` - PowerShell commands
   - `Bash(python:*)` - Python scripts
   - `Bash(node:*)` - Node.js tools
   - `WebSearch` - web searching capability

3. **Development Utilities** - standardowe narzędzia dev
   - `Bash(git:*)` - git commands
   - `Bash(npm:*)` - package management
   - `Read(//c/Users/[user]/.claude/skills/**)` - access to global skills

### ⚠️ PROJECT-SPECIFIC - Użyj dla:

1. **Project-Specific Skills** - tylko dla tego projektu
   - `Skill(livewire-troubleshooting)` - Livewire specific issues
   - `Skill(hostido-deployment)` - specific deployment target
   - `Skill(prestashop-api-expert)` - project domain expert

2. **Project-Specific Tools** - dedykowane skrypty
   - `Bash(php artisan:*)` - Laravel specific
   - `Bash(composer:*)` - PHP dependency management
   - `Bash(pwsh -ExecutionPolicy Bypass -File "_TOOLS/*.ps1")` - project scripts

3. **Sensitive Permissions** - bezpieczeństwo
   - Deployment credentials
   - SSH keys
   - Database access
   - Production server access

---

## Migracja: Project → Global

### Krok 1: Zidentyfikuj Universal Skills

```bash
# Sprawdź aktualne project permissions
cat .claude/settings.local.json | grep "Skill("
```

**Pytanie:** Czy ten skill jest przydatny w INNYCH projektach?
- ✅ TAK → przenieś do global
- ❌ NIE → zostaw w project-specific

### Krok 2: Dodaj do Global Settings

```json
// C:\Users\kamil\.claude\settings.local.json
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",     // ← UNIVERSAL
      "Skill(skill-creator)",   // ← UNIVERSAL
      // ... other global permissions
    ]
  }
}
```

### Krok 3: Usuń z Project Settings (opcjonalnie)

Możesz zostawić duplikaty - nie powodują problemów (merge strategy).

Ale dla czystości kodu:
```json
// .claude/settings.local.json
{
  "permissions": {
    "allow": [
      // "Skill(HookCreator)",  // ← REMOVED (now global)
      "Skill(livewire-troubleshooting)",  // ← PROJECT-SPECIFIC
      "Skill(hostido-deployment)"         // ← PROJECT-SPECIFIC
    ]
  }
}
```

### Krok 4: Restart Claude Code

**KRYTYCZNE:** Skills require restart to activate!

```bash
# Close Claude Code
# Reopen Claude Code
```

---

## PPM-CC-Laravel: Recommended Split

### Global Permissions

**Location:** `C:\Users\kamil\.claude\settings.local.json`

```json
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",
      "Skill(skill-creator)",
      "Skill(agent-report-writer)",
      "Skill(project-plan-manager)",
      "Skill(issue-documenter)",
      "Skill(debug-log-cleanup)",
      "Skill(context7-docs-lookup)",
      "Bash(pwsh:*)",
      "Bash(powershell:*)",
      "Bash(python:*)",
      "Bash(node:*)",
      "Bash(npm:*)",
      "Bash(git:*)",
      "Bash(ls:*)",
      "Bash(claude /doctor)",
      "WebSearch",
      "Read(//c/Users/kamil/.claude/skills/**)"
    ]
  }
}
```

**Skills Breakdown:**
- `HookCreator` - ✅ Universal (creates hooks for any project)
- `skill-creator` - ✅ Universal (creates skills for any project)
- `agent-report-writer` - ✅ Universal (any project can have agents)
- `project-plan-manager` - ✅ Universal (any project has plans)
- `issue-documenter` - ✅ Universal (any project has issues)
- `debug-log-cleanup` - ✅ Universal (any project has debug logs)
- `context7-docs-lookup` - ✅ Universal (Context7 MCP is project-agnostic)

### Project-Specific Permissions (PPM-CC-Laravel)

**Location:** `D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\settings.local.json`

```json
{
  "permissions": {
    "allow": [
      "Skill(livewire-troubleshooting)",
      "Skill(hostido-deployment)",
      "Skill(frontend-verification)",
      "Read(//c/Users/kamil/.claude/skills/frontend-verification/**)",
      "Read(//c/Users/kamil/.claude/skills/hostido-deployment/**)",
      "Bash($HostidoKey = \"D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk\")",
      "Bash(pscp:*)",
      "Bash(plink:*)",
      "Bash($RemoteBase = \"host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html\")",
      "Bash(php artisan tinker:*)",
      "Bash(php -l:*)",
      "Bash(composer:*)",
      "Bash(python _TOOLS/pre_compact_snapshot.py:*)",
      "Bash(python _TOOLS/post_autocompact_recovery.py:*)",
      "Bash(python _TOOLS/session_start_rules_reminder.py:*)",
      "WebFetch(domain:dev.mpptrade.pl)",
      "WebFetch(domain:demo.mpptrade.pl)"
    ]
  },
  "hooks": {
    "PreCompact": [...],
    "SessionStart": [...]
  }
}
```

**Skills Breakdown:**
- `livewire-troubleshooting` - ❌ Project-specific (only Laravel + Livewire 3.x projects)
- `hostido-deployment` - ❌ Project-specific (PPM deployment to Hostido server)
- `frontend-verification` - ❌ Project-specific (PPM verification tool)

**Project Hooks:**
- `pre_compact_snapshot.py` - ❌ PPM-specific (saves PPM context)
- `post_autocompact_recovery.py` - ❌ PPM-specific (restores PPM rules)
- `session_start_rules_reminder.py` - ❌ PPM-specific (hardcoded PPM rules/deployment/Context7)

---

## Przykład: Nowy Projekt

Gdy tworzysz **nowy projekt**, globalny `Skill(HookCreator)` jest **automatycznie dostępny**:

```
Projekt: my-new-project/
└── .claude/
    └── settings.local.json   (może być pusty!)

Skill(HookCreator) działa od razu! (z global settings)
```

**User prompt:**
```
"Create SessionStart hook for my-new-project that displays project info"
```

**Claude response:**
```
I'll use the HookCreator skill to create this hook.
[Skill activates automatically - no project-specific permission needed!]
```

---

## Weryfikacja Global Settings

### Test 1: Check Global Permissions

```bash
cat "C:\Users\kamil\.claude\settings.local.json"
```

**Expected:**
```json
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",  // ← Should be present
      ...
    ]
  }
}
```

### Test 2: Check Effective Permissions

```bash
# W dowolnym projekcie
cat .claude/settings.local.json | grep "Skill(HookCreator)"
```

**If present:** Duplikat (can remove from project)
**If absent:** Global permission działa!

### Test 3: Restart & Test Skill

1. Close Claude Code
2. Reopen Claude Code
3. Prompt: "Create a hook that..."
4. **Expected:** Skill activates without project-specific permission

---

## Troubleshooting

### Problem: Skill nie działa mimo global permission

**Rozwiązanie:**
1. ✅ Verify skill exists: `C:\Users\kamil\.claude\skills\HookCreator\skill.md`
2. ✅ Verify global settings: `cat ~/.claude/settings.local.json | grep HookCreator`
3. ✅ **Restart Claude Code** (MANDATORY!)
4. ✅ Test with new prompt

### Problem: Global vs Project conflict

**Behavior:** Global permissions są mergowane, nie override.

**Jeśli masz:**
- Global: `Skill(HookCreator)`
- Project: `Skill(HookCreator)`

**Result:** Duplikat (działa normalnie, bez błędów)

**Recommendation:** Usuń z project dla czystości.

### Problem: Nie wiem czy permission jest global czy project

**Check:**
```bash
# Global
cat "C:\Users\kamil\.claude\settings.local.json" | grep "Skill(name)"

# Project
cat ".claude/settings.local.json" | grep "Skill(name)"

# Jeśli w obu = duplikat (can remove from project)
```

---

## Best Practices

### 1. Preferuj Global dla Universal Tools

✅ **DO:** Global permissions dla skills używanych w wielu projektach
❌ **DON'T:** Copy-paste tego samego skilla do każdego projektu

### 2. Project-Specific tylko gdy konieczne

✅ **DO:** Project permissions dla project-specific tools/credentials
❌ **DON'T:** Global permissions dla sensitive data (SSH keys, credentials)

### 3. Clean Up Duplicates

Gdy przenosisz skill do global, usuń z project (opcjonalnie):
```bash
# 1. Dodaj do global
# 2. Restart Claude Code
# 3. Test że działa
# 4. Usuń z project settings
```

### 4. Document Global Decisions

Dodaj komentarz w global settings:
```json
{
  "permissions": {
    "allow": [
      "Skill(HookCreator)",  // Universal hook creation for all projects
      "Bash(pwsh:*)"        // Standard PowerShell access
    ]
  }
}
```

---

## Summary

### Question
Czy globalny skill musi być dodany do lokalnego settings projektu?

### Answer
**NIE!** Skills mogą być dodane do globalnego `~/.claude/settings.local.json` i będą działać we **wszystkich projektach**.

### Key Points
1. ✅ Global settings: `C:\Users\kamil\.claude\settings.local.json`
2. ✅ Project settings: `[project]/.claude/settings.local.json`
3. ✅ Permissions są **mergowane** (global + project)
4. ✅ `Skill(HookCreator)` dodany do global → działa wszędzie
5. ✅ Restart Claude Code po zmianach

### Recommendation
- **Global:** Universal skills (HookCreator, skill-creator)
- **Project:** Project-specific skills (livewire-troubleshooting, hostido-deployment)

---

**Last Updated:** 2025-10-31
**Status:** ✅ RESOLVED - Global permissions work for all projects!
**Global Skill Added:** `Skill(HookCreator)` in `C:\Users\kamil\.claude\settings.local.json`
