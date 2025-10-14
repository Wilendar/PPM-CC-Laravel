# PRZEBUDOWA HOOKÓW CLAUDE CODE - 2025-09-30

## PROBLEM
Hook startowy w projekcie PPM-CC-Laravel powodowal freeze Claude Code CLI przy starcie sesji.

## GLOWNA PRZYCZYNA (ZNALEZIONA)
**SessionStart mial niepoprawny `"matcher": "startup"` w konfiguracji!**

Zgodnie z dokumentacja Claude Code hooks:
- SessionStart, SessionEnd, UserPromptSubmit, PreCompact, Stop, SubagentStop, Notification **NIE UŻYWAJĄ** matcher
- **Matcher jest tylko dla PreToolUse i PostToolUse!**

To bylo glowna przyczyna freeze CLI - niepoprawna struktura hooka blokowala input.

## INNE PRZYCZYNY (potencjalne)
1. Skrypt `session-reminder-v2.ps1` używal `ConvertTo-Json -Compress`, ktory mogl powodowac problemy z parsowaniem
2. Brak timeoutu w konfiguracji hookow
3. Skrypt `analyze-prompt.ps1` byl zbyt dlogi i mogl spowalniać działanie

## ROZWIAZANIE

### 1. Nowy skrypt SessionStart
**Plik:** `.claude/scripts/session-start.ps1`

**Zmiany:**
- Bezposredni zapis JSON zamiast `ConvertTo-Json`
- Uproszczony format zgodny z dokumentacja Claude Code hooks
- Dodany timeout 5000ms (5 sekund)
- Lepsza obsluga bledow z fallback JSON

**Format JSON:**
```json
{
  "hookSpecificOutput": {
    "hookEventName": "SessionStart",
    "additionalContext": "kontekst projektu..."
  }
}
```

### 2. Zoptymalizowany skrypt UserPromptSubmit
**Plik:** `.claude/scripts/analyze-prompt-optimized.ps1`

**Zmiany:**
- Użycie `elseif` zamiast wielu `if` dla lepszej wydajności
- Uproszczone detekcje - jedna na prompt
- Dodany timeout 3000ms (3 sekundy)
- Exit code 0 na końcu (wczesniej brak)
- Lepsza obsluga bledow z silent fail

### 3. Aktualizacja settings.local.json

**Zmiany:**
```json
// ❌ NIEPOPRAWNE (blokowalo CLI):
"SessionStart": [
  {
    "matcher": "startup",  // ❌ BŁĄD! SessionStart nie używa matcher!
    "hooks": [...]
  }
]

// ✅ POPRAWNE:
"SessionStart": [
  {
    "hooks": [  // ✅ Bez matchera!
      {
        "type": "command",
        "command": "pwsh -NoProfile -ExecutionPolicy Bypass -File \".claude\\scripts\\session-start.ps1\"",
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
        "command": "pwsh -NoProfile -ExecutionPolicy Bypass -File \".claude\\scripts\\analyze-prompt-optimized.ps1\"",
        "timeout": 3000
      }
    ]
  }
]
```

## PLIKI STWORZONE
- `.claude/scripts/session-start.ps1` - nowy skrypt SessionStart
- `.claude/scripts/analyze-prompt-optimized.ps1` - zoptymalizowany skrypt UserPromptSubmit
- `_DOCS/HOOKS_REBUILD_2025-09-30.md` - dokumentacja zmian

## PLIKI ZMODYFIKOWANE
- `.claude/settings.local.json` - aktualizacja konfiguracji hookow z timeoutami

## PLIKI BACKUP (zachowane)
- `.claude/scripts/session-reminder-v2.ps1` - stary skrypt SessionStart
- `.claude/scripts/analyze-prompt.ps1` - stary skrypt UserPromptSubmit
- `.claude/hooks.json.backup` - stary plik konfiguracji hookow

## TESTOWANIE
Wszystkie nowe skrypty zostaly przetestowane:
- ✅ session-start.ps1 - zwraca poprawny JSON
- ✅ analyze-prompt-optimized.ps1 - wykrywa kontekst Laravel

## REKOMENDACJE
1. **KRYTYCZNE - Matcher**: SessionStart/End, UserPromptSubmit, PreCompact, Stop, SubagentStop, Notification **NIE UŻYWAJĄ** matcher! Tylko PreToolUse i PostToolUse!
2. **Timeouty**: Zawsze ustawiaj timeout dla hookow (domyslnie 60s moze byc za duzo)
3. **JSON Format**: Unikaj ConvertTo-Json w skryptach hookow - lepiej hardcode JSON
4. **Exit Codes**: Zawsze konczc skrypty hooków z exit 0
5. **Error Handling**: Zawsze lapac bledy i robic silent fail w hookach
6. **Wydajnosc**: Hooki powinny byc szybkie (<1s idealnie, <5s max)

## DOKUMENTACJA
Zgodnie z oficjalna dokumentacja Claude Code hooks:
https://docs.claude.com/en/docs/claude-code/hooks

## STATUS
✅ UKONCZONE - Glowna przyczyna znaleziona i naprawiona

**Fix:** Usuniety niepoprawny `"matcher": "startup"` z SessionStart hooka w settings.local.json

Restart Claude Code CLI powinien teraz dzialac poprawnie bez freeze.