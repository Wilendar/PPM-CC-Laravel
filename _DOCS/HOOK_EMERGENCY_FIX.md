# 🚨 AWARYJNE WYŁĄCZENIE HOOKÓW - INSTRUKCJA

**Data:** 2025-10-30
**Problem:** Claude Code CLI zawiesza się, input nie działa w terminalu Windows
**Status:** WSZYSTKIE GŁÓWNE HOOKI TYMCZASOWO WYŁĄCZONE

---

## ✅ CO ZOSTAŁO ZROBIONE

Wyłączyłem **wszystkie główne hooki** które mogą powodować zawieszenie terminala poprzez zmianę nazw eventów (dodanie `_DISABLED_FOR_TESTING`):

### Wyłączone hooki:

1. **SessionStart** → `SessionStart_DISABLED_FOR_TESTING`
   - Skrypt: `_TOOLS\post_autocompact_recovery.ps1`
   - Problem: Wyświetla dużo kolorowego tekstu PRZED inicjalizacją CLI

2. **UserPromptSubmit** → `UserPromptSubmit_DISABLED_FOR_TESTING`
   - Skrypt: `.claude\scripts\prompt-context-reminder.ps1`
   - Problem: Wyświetla kontekst projektu przy KAŻDYM promptcie

3. **PreToolUse** → `PreToolUse_DISABLED_FOR_TESTING`
   - Skrypty: `enforce-context7.ps1`, `agent-report-reminder.ps1`
   - Problem: Wyświetla ostrzeżenia przed Write/Edit/Task

### Aktywne hooki (pozostawione):

- ✅ **PostToolUse** - działają tylko PO wykonaniu akcji (mniejsze ryzyko)
- ✅ **PreCompact** - rzadko wywoływany
- ✅ **mcp__context7__** - minimalny output

---

## 🔧 INSTRUKCJA DLA UŻYTKOWNIKA

### KROK 1: Zamknij obecną sesję Claude Code CLI
```powershell
# Wciśnij Ctrl+C lub zamknij terminal
```

### KROK 2: Uruchom nową sesję w projekcie
```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
claude
```

### KROK 3: Testuj czy input działa

Spróbuj wpisać prostą komendę:
```
ultrathink test input
```

---

## 📊 SCENARIUSZE

### ✅ SCENARIUSZ A: Input działa poprawnie

**Oznacza to:** Jeden z wyłączonych hooków powodował problem

**Następne kroki:**
1. Włączaj hooki jeden po drugim
2. Testuj po każdym włączeniu
3. Zidentyfikuj problematyczny hook
4. Przepisz go na cichszą wersję

**Kolejność testowania:**
```
1. Włącz PostToolUse (najmniej prawdopodobny winowajca)
2. Włącz PreCompact
3. Włącz PreToolUse (enforce-context7 + agent-report-reminder)
4. Włącz UserPromptSubmit (bardziej prawdopodobny)
5. Włącz SessionStart (najbardziej prawdopodobny - dużo outputu)
```

### ❌ SCENARIUSZ B: Input nadal nie działa

**Oznacza to:** Problem nie leży w hookach projektowych

**Możliwe przyczyny:**
1. Globalne hooki w `~/.claude/settings.json`
2. Problem z samym Claude Code CLI w Windows
3. Konflikt z Windows Terminal
4. Problem z kodowaniem UTF-8
5. Zablokowane procesy PowerShell

**Następne kroki:**
1. Sprawdź globalne hooki: `~/.claude/settings.json`
2. Sprawdź wersję Claude Code CLI: `claude --version`
3. Spróbuj w czystym terminalu PowerShell (nie Windows Terminal)
4. Sprawdź czy inne komendy działają: `ls`, `pwd`
5. Reinstaluj Claude Code CLI

---

## 🔄 JAK PRZYWRÓCIĆ HOOKI

### Metoda 1: Ręczne włączanie (zalecane)

W plikach:
- `.claude\settings.local.json`
- `.claude\settings.local-kwilinsk5.json`

Zmień nazwę eventu z powrotem:
```json
// Z tego:
"SessionStart_DISABLED_FOR_TESTING": [ ... ]

// Na to:
"SessionStart": [ ... ]
```

### Metoda 2: Przywróć backup

Backup został utworzony automatycznie:
```
.claude\settings.local.json.backup_[timestamp]
.claude\settings.local-kwilinsk5.json.backup_[timestamp]
```

Przywróć:
```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude"

# Znajdź najnowszy backup
Get-ChildItem *.backup_* | Sort-Object LastWriteTime -Descending | Select-Object -First 2

# Przywróć (zamień [timestamp] na właściwy)
Copy-Item "settings.local.json.backup_[timestamp]" "settings.local.json" -Force
Copy-Item "settings.local-kwilinsk5.json.backup_[timestamp]" "settings.local-kwilinsk5.json" -Force
```

---

## 🛠️ NAPRAWIONE PROBLEMY (z poprzedniej iteracji)

✅ Dodano `exit 0` do `post_autocompact_recovery.ps1`
✅ Poprawiono strukturę hooków (usunięto podwójne zagnieżdżenie)
✅ Zweryfikowano składnię JSON (wszystkie pliki VALID)

---

## 📝 ZALECENIA NA PRZYSZŁOŚĆ

### 1. Minimalistyczne hooki dla SessionStart

Zamiast wyświetlać duży blok tekstu, użyj:
```powershell
Write-Host "Claude session started" -ForegroundColor Green
exit 0
```

### 2. Prznieś verbose output do skilla/komendy

Zamiast automatycznego wyświetlania przy SessionStart, utwórz:
```
/context  # Wyświetla kontekst projektu na żądanie
/recovery # Wyświetla opcje recovery na żądanie
```

### 3. Użyj flag dla verbose mode

```json
{
  "hooks": {
    "SessionStart": [
      {
        "type": "command",
        "command": "pwsh -File script.ps1 -Quiet",
        "timeout": 1000
      }
    ]
  }
}
```

### 4. Testuj hooki w izolacji ZAWSZE

```powershell
# Test przed wdrożeniem
pwsh -NoProfile -ExecutionPolicy Bypass -File ".claude\scripts\test-hook.ps1"
Measure-Command { & ".claude\scripts\test-hook.ps1" }
```

---

## 🔍 DIAGNOSTYKA

### Sprawdź które hooki są aktywne:

```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
Get-Content ".claude\settings.local.json" | Select-String -Pattern '"(UserPromptSubmit|SessionStart|PreToolUse|PostToolUse|PreCompact)"' -Context 0,3
```

### Sprawdź czy skrypty istnieją:

```powershell
Test-Path ".claude\scripts\prompt-context-reminder.ps1"
Test-Path ".claude\scripts\enforce-context7.ps1"
Test-Path ".claude\scripts\agent-report-reminder.ps1"
Test-Path "_TOOLS\post_autocompact_recovery.ps1"
```

### Uruchom skrypty ręcznie:

```powershell
# Test każdego skryptu osobno
pwsh -NoProfile -ExecutionPolicy Bypass -File ".claude\scripts\prompt-context-reminder.ps1"
pwsh -NoProfile -ExecutionPolicy Bypass -File ".claude\scripts\enforce-context7.ps1"
pwsh -NoProfile -ExecutionPolicy Bypass -File ".claude\scripts\agent-report-reminder.ps1"
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"
```

---

## ⚠️ UWAGA

**NIE EDYTUJ** plików hooków podczas gdy Claude Code CLI jest uruchomiony!

**ZAWSZE** testuj zmiany w hookach w nowej sesji terminala.

**BACKUP** zawsze przed zmianami w settings.

---

**Utworzono:** 2025-10-30
**Autor:** Claude (Sonnet 4.5)
**Następna akcja:** Testuj Claude Code CLI i raportuj wynik
