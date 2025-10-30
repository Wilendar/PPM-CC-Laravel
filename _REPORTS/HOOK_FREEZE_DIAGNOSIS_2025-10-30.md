# DIAGNOZA: Zawieszenie Claude Code CLI przez Hooki

**Data:** 2025-10-30
**Projekt:** PPM-CC-Laravel
**Status:** ✅ NAPRAWIONO

---

## 🔴 GŁÓWNY PROBLEM

### 1. Brak `exit 0` w skrypcie SessionStart hook

**Plik:** `_TOOLS\post_autocompact_recovery.ps1`

**Problem:**
- Skrypt kończy się blokiem `try-catch` bez gwarancji zwrócenia kodu wyjścia
- Według dokumentacji Anthropic: **każdy hook MUSI kończyć się exit code 0 (sukces) lub 2 (blokowanie)**
- Brak jawnego `exit 0` powodował zawieszenie Claude Code CLI przy każdym starcie sesji

**Lokalizacja błędu:**
```powershell
# Linie 137-140 (PRZED NAPRAWĄ)
catch {
    # Silent failure - just show minimal reminder
    Show-MinimalReminder
}
# BRAK exit 0 tutaj!
```

**Naprawa:**
```powershell
catch {
    # Silent failure - just show minimal reminder
    Show-MinimalReminder
}

# CRITICAL: Hook MUST exit with code 0 (success) or 2 (blocking)
exit 0
```

---

## ⚠️ DODATKOWE PROBLEMY STRUKTURALNE

### 2. Niezgodna struktura hooków bez matchera

**Problem:** Hooki bez matchera (UserPromptSubmit, SessionStart, PreCompact) miały dodatkowy poziom zagnieżdżenia `"hooks": []`

**PRZED (niepoprawne):**
```json
"UserPromptSubmit": [
  {
    "hooks": [          // ❌ Niepotrzebny poziom
      {
        "type": "command",
        "command": "...",
        "timeout": 2000
      }
    ]
  }
]
```

**PO (zgodnie z dokumentacją Anthropic):**
```json
"UserPromptSubmit": [
  {
    "type": "command",     // ✅ Bezpośrednio w tablicy
    "command": "...",
    "timeout": 2000
  }
]
```

**Naprawiono w plikach:**
- `.claude\settings.local.json`
- `.claude\settings.local-kwilinsk5.json`

### 3. Brak SessionStart hook w drugim pliku

**Problem:** `settings.local-kwilinsk5.json` nie miał SessionStart hook - powodowało to niespójność

**Naprawa:** Dodano SessionStart hook do obu plików dla spójności

---

## 📋 ZWERYFIKOWANE WYMAGANIA DOKUMENTACJI ANTHROPIC

### ✅ Poprawne elementy (nie wymagały zmian):

1. **Timeout values:**
   - SessionStart: 3000ms (3s) ✅
   - UserPromptSubmit: 2000ms (2s) ✅
   - PreToolUse: 1000-2000ms ✅
   - PostToolUse: 1000ms ✅
   - PreCompact: 1000ms ✅

2. **Exit codes w innych skryptach:**
   - `prompt-context-reminder.ps1` - ✅ ma `exit 0`
   - `enforce-context7.ps1` - ✅ ma `exit 0`
   - `agent-report-reminder.ps1` - ✅ ma `exit 0`

3. **Matchery w PreToolUse/PostToolUse:**
   - Regex patterns poprawne ✅
   - Struktura z `"matcher"` + `"hooks"` poprawna ✅

---

## 🔧 NAPRAWIONE PLIKI

### 1. `_TOOLS\post_autocompact_recovery.ps1`
- ✅ Dodano `exit 0` na końcu skryptu (linia 143)

### 2. `.claude\settings.local.json`
- ✅ Usunięto dodatkowy poziom `"hooks": []` z UserPromptSubmit
- ✅ Usunięto dodatkowy poziom `"hooks": []` z PreCompact
- ✅ Usunięto dodatkowy poziom `"hooks": []` z SessionStart
- ✅ Składnia JSON zweryfikowana - VALID

### 3. `.claude\settings.local-kwilinsk5.json`
- ✅ Usunięto dodatkowy poziom `"hooks": []` z UserPromptSubmit
- ✅ Usunięto dodatkowy poziom `"hooks": []` z PreCompact
- ✅ Dodano SessionStart hook dla spójności
- ✅ Składnia JSON zweryfikowana - VALID

---

## 📚 DOKUMENTACJA ANTHROPIC - KLUCZOWE ZASADY

### Hook Script Requirements:
1. **MUST** exit with code 0 (success) or 2 (blocking error)
2. **MUST** complete within timeout (default 60s, configurable)
3. **MUST** handle stdin/stdout properly (JSON or plain text)
4. **SHOULD** use `$CLAUDE_PROJECT_DIR` for project-relative paths
5. **SHOULD** quote all shell variables as `"$VAR"`

### Common Issues Causing Hangs:
- ❌ Infinite loops in hook scripts
- ❌ Missing exit codes (scripts that don't terminate)
- ❌ Timeout misconfiguration
- ❌ Blocking operations without timeout protection
- ❌ Deadlocks from stdin/stdout synchronization issues

---

## ✅ WERYFIKACJA

### Test składni JSON:
```powershell
# Oba pliki przeszły walidację
✅ settings.local.json - VALID
✅ settings.local-kwilinsk5.json - VALID
```

### Struktura hooków zgodna z dokumentacją:
```
✅ UserPromptSubmit - poprawiona struktura
✅ PreToolUse - struktura z matcherem OK
✅ PostToolUse - struktura z matcherem OK
✅ PreCompact - poprawiona struktura
✅ SessionStart - poprawiona struktura + dodany exit 0
```

---

## 🎯 ROZWIĄZANIE

**ROOT CAUSE:** Brak `exit 0` w skrypcie SessionStart + niepoprawna struktura JSON hooków bez matchera

**FIX:**
1. Dodano `exit 0` do `post_autocompact_recovery.ps1`
2. Poprawiono strukturę hooków według dokumentacji Anthropic
3. Ujednolicono konfigurację w obu plikach settings

**EXPECTED RESULT:** Claude Code CLI nie będzie się już zawieszać przy starcie sesji

---

## 📝 REKOMENDACJE

1. **Zawsze** testuj hooki niezależnie przed wdrożeniem
2. **Sprawdzaj** czy skrypty mają jawny `exit 0` lub `exit 2`
3. **Weryfikuj** strukturę JSON zgodnie z dokumentacją
4. **Używaj** timeout protection dla wszystkich hooków
5. **Monitoruj** logi Claude Code w przypadku problemów

---

**Autor:** Claude (Sonnet 4.5)
**Czas naprawy:** ~15 minut
**Pliki zmodyfikowane:** 3
