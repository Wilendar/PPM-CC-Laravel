# HookCreator Skill - Globalny Skill Utworzony

**Data:** 2025-10-30
**Lokalizacja:** `~/.claude/skills/HookCreator/`
**Status:** ✅ GOTOWY DO UŻYCIA

---

## 🎯 CO ZOSTAŁO STWORZONE

### Globalny Skill: HookCreator

**Przeznaczenie:** Expert skill do tworzenia Claude Code hooków z best practices

**Lokalizacja plików:**
```
C:\Users\kamil\.claude\skills\HookCreator\
├── skill.md         # Główny skill prompt (11KB dokumentacji)
└── README.md        # User guide
```

---

## 📚 CO ZAWIERA SKILL

### 1. Pełna dokumentacja Claude Code Hooks

Ze źródła: https://docs.claude.com/en/docs/claude-code/hooks

Zawiera:
- ✅ Wszystkie typy hooków (PreToolUse, PostToolUse, SessionStart, etc.)
- ✅ Strukturę i matchery
- ✅ Input/Output schema
- ✅ Exit codes i blocking behavior
- ✅ Environment variables
- ✅ Advanced JSON output
- ✅ MCP tool integration
- ✅ Security considerations

### 2. Best Practices (z praktycznego doświadczenia)

**Critical Rules:**
- ✅ Zawsze Python (nie PowerShell) - lepsze stdin/stdout na Windows
- ✅ Zawsze konsumuj stdin - `stdin_data = sys.stdin.read()`
- ✅ Zawsze exit 0 lub 2 - hook protocol requirement
- ✅ Rozumiej matcher requirements - różne dla różnych typów

**Performance:**
- Fast hooks (<100ms) dla SessionStart "startup", UserPromptSubmit
- Slower OK (<5s) dla SessionStart "compact", PreToolUse
- Avoid network calls, heavy I/O

**Security:**
- Walidacja ścieżek (`.env`, `..` traversal)
- Sanityzacja inputów
- Error handling

### 3. Python Hook Template

Gotowy szablon z:
- stdin consumption
- JSON parsing
- Error handling
- Multiple output methods (stdout, stderr, structured JSON)
- Comments i dokumentacja

### 4. Common Hook Patterns

**4 gotowe wzorce:**
1. Validation Hook (block if invalid)
2. Context Injection Hook (add info to Claude context)
3. Information Display Hook (show messages)
4. Recovery Hook (SessionStart with compact)

### 5. Testing & Debugging

**Testing checklist:**
- Manual test with `echo | python`
- Test in Claude Code with `--debug`
- Verify with `/hooks` menu
- Performance measurement

**Debugging checklist:**
- 10-point verification list
- Common issues & solutions
- Troubleshooting guide

---

## 🚀 JAK UŻYWAĆ

### Invoke Skill w Projekcie

```bash
# Ogólny format
ultrathink use HookCreator to create a hook that [opisz co chcesz]

# Przykłady:
ultrathink use HookCreator to create a hook that reminds me to use Context7 before writing PHP files

ultrathink use HookCreator to create a SessionStart hook that shows recovery info after autocompact

ultrathink use HookCreator to create a hook that validates file paths before writing

ultrathink use HookCreator to create a hook that blocks modifications to .env files
```

### Co Skill Zrobi

1. **Przeanalizuje** twój prompt i zrozumie intent
2. **Wybierze** odpowiedni hook type i matcher
3. **Stworzy** Python implementation (zawsze)
4. **Zaktualizuje** `.claude/settings.local.json`
5. **Przetestuje** basic functionality
6. **Dokumentuje** kiedy i jak hook działa

---

## 📋 HOOK TYPES REFERENCE

| Hook Type | Matcher | Kiedy używać |
|-----------|---------|--------------|
| **PreToolUse** | Tool names: `"Write"`, `"Bash"`, regex | Przed użyciem narzędzia, walidacja, blocking |
| **PostToolUse** | Tool names (jak PreToolUse) | Po użyciu narzędzia, display info, logging |
| **UserPromptSubmit** | BRAK matchera | Każdy prompt, context injection, reminders |
| **SessionStart** | Source: `"startup"`, `"compact"`, `"resume"` | Inicjalizacja, recovery, environment setup |
| **SessionEnd** | BRAK matchera | Cleanup, save state, logging |
| **PreCompact** | Type: `"manual"`, `"auto"` | Przed kompaktacją, save important context |

---

## 🎓 LEARNING FROM PRODUCTION

Skill zawiera wnioski z 4h debugowania hooków w tej sesji:

### Problem 1: PowerShell stdin deadlock
**Solution:** Python zamiast PowerShell - niezawodne stdin handling

### Problem 2: Brak stdin consumption
**Solution:** Zawsze `stdin_data = sys.stdin.read()` na początku

### Problem 3: Brak matchera w SessionStart
**Solution:** SessionStart WYMAGA matchera dla źródła (startup, compact, etc.)

### Problem 4: ANSI codes w SessionStart
**Solution:** Plain text lub przeniesienie do PostToolUse/UserPromptSubmit

### Problem 5: Niepoprawna struktura JSON
**Solution:** Różne struktury dla hooków z/bez matchera

---

## 📝 PRZYKŁAD SESJI

**User request:**
```
use HookCreator to create a hook that reminds me to use Context7 before writing PHP
```

**Skill analysis:**
- Event: Before file write → PreToolUse
- Trigger: PHP files → matcher: `"(Write|Edit).*\\.php$"`
- Action: Display reminder → stdout
- Block?: No (just inform)

**Skill creates:**

``.claude/hooks/context7_php_reminder.py`:
```python
#!/usr/bin/env python3
"""Remind to use Context7 before PHP modifications"""
import sys
import json

def main():
    stdin_data = sys.stdin.read()
    hook_input = json.loads(stdin_data) if stdin_data else {}

    tool_input = hook_input.get('tool_input', {})
    file_path = tool_input.get('file_path', '')

    if file_path.endswith('.php'):
        print("\n⚠️  PHP FILE MODIFICATION")
        print("Remember: Use Context7 MCP before writing code!")
        print("  → mcp__context7__get-library-docs /websites/laravel_12_x\n")

    sys.exit(0)

if __name__ == "__main__":
    main()
```

`.claude/settings.local.json`:
```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "(Write|Edit).*\\.php$",
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/context7_php_reminder.py",
            "timeout": 1000
          }
        ]
      }
    ]
  }
}
```

**Result:** Every PHP file modification shows Context7 reminder

---

## 🔧 MAINTENANCE

### Aktualizacja Skilla

Skill jest globalny w `~/.claude/skills/HookCreator/`. Możesz edytować:
- `skill.md` - główny prompt dla Claude
- `README.md` - dokumentacja dla użytkownika

### Dodanie Custom Patterns

Możesz dodać własne wzorce do sekcji "COMMON HOOK PATTERNS" w `skill.md`.

### Sharing

Skill może być skopiowany do innych projektów:
```bash
cp -r ~/.claude/skills/HookCreator /other/project/.claude/skills/
```

---

## 📚 DOKUMENTACJA

**Skill documentation:** `~/.claude/skills/HookCreator/skill.md` (11KB)
**User guide:** `~/.claude/skills/HookCreator/README.md` (3KB)
**Official Anthropic docs:** https://docs.claude.com/en/docs/claude-code/hooks

---

## ✅ VERIFICATION

Sprawdź czy skill działa:

```bash
# 1. Verify files exist
ls ~/.claude/skills/HookCreator/

# 2. Check skill is available
# In Claude CLI, skills should auto-detect from this location

# 3. Test invocation
ultrathink use HookCreator to create a simple test hook
```

---

## 🎯 NASTĘPNE KROKI

1. **Przetestuj skill:**
   ```
   ultrathink use HookCreator to create a hook that displays "Hello" on every prompt
   ```

2. **Stwórz przydatny hook:**
   - Context reminder dla Context7
   - File validation przed zapisem
   - Recovery info po autocompact
   - Git info display po commitach

3. **Dostosuj do projektu:**
   - Dodaj project-specific patterns
   - Zintegruj z workflow
   - Dokumentuj custom hooki

---

**Autor:** Claude (Sonnet 4.5)
**Czas tworzenia:** 30 minut
**Wielkość dokumentacji:** 14KB (skill.md + README.md)
**Status:** ✅ PRODUCTION READY

**Skill gotowy do użycia! Invoke z `ultrathink use HookCreator to...`** 🎉
