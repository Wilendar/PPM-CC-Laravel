# Active Hooks System - Python Hooki z Aktywnym Czytaniem Danych

**Data:** 2025-10-31
**Status:** ✅ AKTYWNY

## Koncepcja

Zamiast statycznych przypomnień ("Przeczytaj X"), hooki **AKTYWNIE WYKONUJĄ** akcje:
- Czytają pliki projektu (CLAUDE.md, plany, raporty)
- Parsują YAML front matter (agent descriptions)
- Sprawdzają status projektu
- Zwracają **TREŚĆ** jako system-reminder (nie tylko przypomnienie)

## Architektura

### Hooki Python

Wszystkie hooki używają wspólnego wzorca:

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

def write_tty(text):
    """Pisz do /dev/tty z silent fallback do stderr"""
    try:
        with open('/dev/tty', 'w') as tty:
            tty.write(text)
            tty.flush()
    except (IOError, OSError):
        sys.stderr.write(text)
        sys.stderr.flush()

def main():
    try:
        # Konsumuj stdin żeby uniknąć deadlock
        sys.stdin.read()
    except:
        pass

    # [AKTYWNE CZYTANIE DANYCH]
    # - Czytaj pliki
    # - Parsuj YAML
    # - Sprawdź status
    # - Format output

    write_tty(output)
```

### Output do /dev/tty

**Dlaczego /dev/tty?**
- Claude Code przechwytuje stdout/stderr z hooków
- `/dev/tty` = bezpośrednie pisanie do terminala (omija capturing)
- Silent fallback do stderr jeśli /dev/tty niedostępny

## Zaimplementowane Hooki

### 1. SessionStart Hook
**Plik:** `.claude/hooks/session_start_active.py`

**Funkcjonalność:**
- Czyta `CLAUDE.md` (max 500 linii)
- Ekstraktuje kluczowe sekcje:
  - Stack Technologiczny
  - Build & Deployment
  - KRYTYCZNE ZASADY (CSS, inline styles)
  - Środowisko Deployment
  - System Agentów
  - Context7
- Sprawdza status projektu (liczba ETAP files)
- Pokazuje 3 ostatnie raporty agentów z `_AGENT_REPORTS/`

**Output:**
```
🚀 PPM-CC-Laravel SESSION START
================================================================================

📖 Reading CLAUDE.md...

▸ Stack Technologiczny:
  - Backend: PHP 8.3 + Laravel 12.x
  - UI: Blade + Livewire 3.x + Alpine.js
  ...

📋 Checking project status...
  Found 18 ETAP files

📊 Latest agent reports:
  • COORDINATION_2025-10-31_CHECKBOX_FIX_REPORT.md
  • livewire_specialist_variant_checkbox_fix_2025-10-31_REPORT.md
  ...

✅ Session initialized - Project context loaded
```

### 2. UserPromptSubmit Hook
**Plik:** `.claude/hooks/user_prompt_active.py`

**Funkcjonalność:**
- Sprawdza `recovery.flag` w `.claude/`
- Jeśli recovery:
  - Znajduje ostatni snapshot w `_TEMP/compact_snapshots/`
  - Parsuje JSON (branch, timestamp, working dir)
  - Pokazuje recovery info
  - Usuwa flag
- Jeśli brak recovery:
  - Krótkie przypomnienie o Context7

**Output (recovery):**
```
🔄 RECOVERY DETECTED
📸 Latest snapshot: session_2025-10-31T12-00-00.json
  Branch: main
  Timestamp: 2025-10-31T12:00:00
  Working dir: /mnt/d/...
✅ Recovery flag cleared
```

**Output (normal):**
```
💡 Tip: Use Context7 MCP for Laravel/Livewire documentation
```

### 3. PreToolUse PHP Hook
**Plik:** `.claude/hooks/pretooluse_php_active.py`

**Funkcjonalność:**
- Pokazuje dostępne Context7 libraries (z counts)
- Przypomina o zasadach:
  - NO HARDCODING
  - NO INLINE STYLES
  - Verify with Context7 BEFORE implementation

**Output:**
```
⚠️  PHP CODE MODIFICATION
Context7 libraries available:
  • Laravel 12.x: /websites/laravel_12_x (4927 snippets)
  • Livewire 3.x: /livewire/livewire (867 snippets)
  • Alpine.js: /alpinejs/alpine (364 snippets)

REMEMBER:
  ❌ NO HARDCODING - use realistic random/dynamic values
  ❌ NO INLINE STYLES - use CSS classes
  ✅ Verify with Context7 BEFORE implementation
```

### 4. PreToolUse Task Hook
**Plik:** `.claude/hooks/pretooluse_task_active.py`

**Funkcjonalność:**
- Czyta wszystkie pliki z `.claude/agents/`
- Parsuje YAML front matter (description field)
- Pokazuje listę 13 agentów z opisami (max 60 chars)
- Przypomina o requirements:
  - Create agent report
  - Update plan status
  - Use coding-style-agent before completion

**Output:**
```
🤖 AGENT DELEGATION
Available agents:

  • architect: Expert Planning Manager & Project Plan Keeper dla PPM-CC-Lar...
  • ask: Knowledge Expert dla PPM-CC-Laravel - Udzielanie odpowiedzi ...
  • coding-style-agent: Code Quality Guardian dla PPM-CC-Laravel - Pilnowanie standa...
  • debugger: Expert Debugger specjalizujący się w systematycznej diagnost...
  • deployment-specialist: Deployment & Infrastructure Expert dla PPM-CC-Laravel - Spec...
  ...

REQUIREMENTS:
  ✅ Create _AGENT_REPORTS/agent_name_REPORT.md after completion
  ✅ Update Plan_Projektu/ with status emoji
  ✅ Use coding-style-agent BEFORE completion
```

## Konfiguracja

### settings.local.json

```json
{
  "hooks": {
    "SessionStart": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "python3 .claude/hooks/session_start_active.py",
            "timeout": 5000
          }
        ]
      }
    ],
    "UserPromptSubmit": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "python3 .claude/hooks/user_prompt_active.py",
            "timeout": 3000
          }
        ]
      }
    ],
    "PreToolUse": [
      {
        "matcher": "(Write|Edit).*\\.(php|blade\\.php)$",
        "hooks": [
          {
            "type": "command",
            "command": "python3 .claude/hooks/pretooluse_php_active.py",
            "timeout": 2000
          }
        ]
      },
      {
        "matcher": "Task",
        "hooks": [
          {
            "type": "command",
            "command": "python3 .claude/hooks/pretooluse_task_active.py",
            "timeout": 2000
          }
        ]
      }
    ]
  }
}
```

### Permissions

Wszystkie hooki muszą mieć execute permissions:

```bash
chmod +x .claude/hooks/session_start_active.py
chmod +x .claude/hooks/user_prompt_active.py
chmod +x .claude/hooks/pretooluse_php_active.py
chmod +x .claude/hooks/pretooluse_task_active.py
```

## Testy

### Test 1: SessionStart
```bash
python3 .claude/hooks/session_start_active.py < /dev/null
```
**Expected:** Full project context z CLAUDE.md sections, status, reports

### Test 2: UserPromptSubmit
```bash
python3 .claude/hooks/user_prompt_active.py < /dev/null
```
**Expected:** Context7 tip (lub recovery info jeśli flag istnieje)

### Test 3: PreToolUse PHP
```bash
python3 .claude/hooks/pretooluse_php_active.py < /dev/null
```
**Expected:** Context7 libraries + zasady

### Test 4: PreToolUse Task
```bash
python3 .claude/hooks/pretooluse_task_active.py < /dev/null
```
**Expected:** Lista 13 agentów z descriptions

## Wyniki Testów

**Data:** 2025-10-31

| Hook | Status | Output Visibility | Data Reading |
|------|--------|-------------------|--------------|
| SessionStart | ✅ DZIAŁA | 👀 WIDOCZNY | ✅ CLAUDE.md, status, reports |
| UserPromptSubmit | ✅ DZIAŁA | 👀 WIDOCZNY | ✅ Recovery flag, snapshots |
| PreToolUse PHP | ✅ DZIAŁA | 👀 WIDOCZNY | ✅ Context7 info |
| PreToolUse Task | ✅ DZIAŁA | 👀 WIDOCZNY | ✅ Agent YAML parsing |

## Zalety Nowego Systemu

### Przed (Statyczne Przypomnienia)
```bash
⚠️ PRZYPOMNIENIE: Przeczytaj CLAUDE.md
⚠️ PRZYPOMNIENIE: Sprawdź Context7
```
**Problem:** Claude musi **MANUALNIE** wykonać akcje

### Po (Aktywne Czytanie)
```bash
🚀 PPM-CC-Laravel SESSION START
📖 Reading CLAUDE.md...

▸ Stack Technologiczny:
  - Backend: PHP 8.3 + Laravel 12.x
  ...
```
**Zaleta:** Claude **OTRZYMUJE TREŚĆ** automatycznie!

### Porównanie

| Aspekt | Statyczne | Aktywne |
|--------|-----------|---------|
| Akcja | Claude musi czytać | Hook czyta automatycznie |
| Treść | Tylko przypomnienie | Pełna treść w output |
| Kontekst | Wymaga Read tool | Natychmiastowy kontekst |
| Efektywność | Wolne (extra tool calls) | Szybkie (zero tool calls) |
| Compliance | Łatwo zignorować | Trudno zignorować (widzi treść) |

## Rozszerzenia w Przyszłości

### 1. SessionStart - Plan Status
```python
# Czytaj wszystkie ETAP_*.md files
# Parse emoji statusy (❌ 🛠️ ✅ ⚠️)
# Pokaż progress: "ETAP_05: 12/45 tasks completed (27%)"
```

### 2. UserPromptSubmit - Smart Context7 Tips
```python
# Count prompts since last Context7 use
# Show tip co 10 prompts (nie co prompt)
# Detect PHP/Blade editing → specific Laravel/Livewire tips
```

### 3. PreToolUse PHP - File-Specific Patterns
```python
# Detect editing Livewire component → show Livewire patterns
# Detect editing Blade view → show Blade/Alpine patterns
# Detect editing Model → show Eloquent patterns
```

### 4. PreCompact - Better Snapshots
```python
# Include git diff summary
# Include active TODO items
# Include recent errors from logs
```

## Troubleshooting

### Hook nie wyświetla output

**Symptom:** Hook się wykonuje ale nic nie widać w terminalu

**Diagnoza:**
```bash
# Test bezpośrednio
python3 .claude/hooks/session_start_active.py < /dev/null
```

**Możliwe przyczyny:**
1. Brak `/dev/tty` access → sprawdź fallback do stderr
2. Exception w hooku → sprawdź error handling
3. Timeout zbyt krótki → zwiększ timeout w settings.local.json

### "No such device" errors

**Symptom:** `/dev/tty: No such device or address`

**Rozwiązanie:** Hook używa silent fallback do stderr - to normalne w pipe contexts

### YAML parsing nie działa

**Symptom:** Agent descriptions pokazują "No description"

**Diagnoza:**
```bash
# Sprawdź format YAML front matter
head -n 10 .claude/agents/architect.md
```

**Wymagany format:**
```markdown
---
name: architect
description: Expert Planning Manager & Project Plan Keeper...
---
```

## Historia Zmian

### 2025-10-31: Implementacja Aktywnego Systemu
- ✅ Stworzono 4 Python hooki z aktywnym czytaniem
- ✅ SessionStart: CLAUDE.md + status + reports
- ✅ UserPromptSubmit: Recovery detection + Context7 tips
- ✅ PreToolUse PHP: Context7 libraries info
- ✅ PreToolUse Task: Agent YAML parsing
- ✅ Wszystkie hooki przetestowane - output widoczny w WSL
- ✅ Zaktualizowano settings.local.json

### Poprzednia wersja (Statyczne Bash Hooki)
- ❌ Tylko statyczne przypomnienia
- ❌ Wymagały manualnych akcji od Claude
- ❌ Brak automatycznego ładowania kontekstu

## Referencje

- **Poprzednia implementacja:** `_REPORTS/HOOKS_FINAL_FIX_2025-10-30.md`
- **Session start guide:** `_DOCS/SESSION_START_HOOK_GUIDE.md`
- **Hooks system overview:** `_DOCS/HOOKS_SYSTEM_OVERVIEW.md`
