# USUNIĘCIE SESSIONSTART HOOK - ROZWIĄZANIE ZAWIESZANIA

**Data:** 2025-10-30 11:45
**Problem:** Claude Code CLI zawiesza się przy starcie, input nie działa w Windows Terminal
**Rozwiązanie:** Usunięto SessionStart hook
**Status:** ✅ DO PRZETESTOWANIA

---

## 🔴 ROOT CAUSE

**SessionStart hook** został zidentyfikowany jako winowajca zawieszania terminala.

### Dowody:

1. **Git History Analysis:**
   - SessionStart hook **NIE istnieje w Git history**
   - Ostatni commit (eddb8d8) nie zawiera SessionStart
   - Hook został dodany RĘCZNIE lub przez agenta PO ostatnim commicie
   - To czyni go **najnowszym dodanym hookiem**

2. **Timing:**
   - Wszystkie skrypty hooków: ostatnia modyfikacja **30.09.2025**
   - Settings pliki: ostatnia modyfikacja **30.10.2025** (dzisiaj)
   - SessionStart był ostatnio dodanym hookiem przed problemem

3. **Mechanizm zawieszania:**
   ```
   SessionStart → post_autocompact_recovery.ps1 → Duży output z ANSI codes
   → Blokuje stdin/stdout podczas inicjalizacji Windows Terminal
   → Claude Code CLI czeka na zakończenie hooka
   → Hook czeka na stdin (deadlock?)
   → Terminal zawieszony, brak inputu
   ```

---

## 🔧 CO ZOSTAŁO ZROBIONE

### 1. Usunięto SessionStart hook całkowicie

**Z obu plików:**
- `.claude\settings.local.json`
- `.claude\settings.local-kwilinsk5.json`

**Przed:**
```json
"SessionStart": [
  {
    "type": "command",
    "command": "pwsh -NoProfile -ExecutionPolicy Bypass -File \"_TOOLS\\post_autocompact_recovery.ps1\"",
    "timeout": 3000
  }
]
```

**Po:**
```json
// SessionStart hook USUNIĘTY
```

### 2. Zachowano wszystkie inne hooki

✅ **UserPromptSubmit** - przypomnienie kontekstu projektu
✅ **PreToolUse** - enforce Context7, agent reports reminder
✅ **PostToolUse** - potwierdzenia po modyfikacji kodu
✅ **PreCompact** - przypomnienie przed kompaktacją

### 3. Zweryfikowano składnię JSON

```
✅ settings.local.json - VALID JSON
✅ settings.local-kwilinsk5.json - VALID JSON
```

---

## 📊 ANALIZA PROBLEMU

### Dlaczego SessionStart hook powodował zawieszenie?

**post_autocompact_recovery.ps1** wykonuje:

1. **Czyta plik JSON** (`_TEMP\claude_session_state.json`)
   ```powershell
   $snapshot = Get-Content $snapshotPath -Raw -Encoding UTF8 | ConvertFrom-Json
   ```

2. **Wyświetla DUŻO kolorowego tekstu** (30+ linii z ANSI escape codes)
   - Banner (3 linie)
   - Timestamp + context
   - TODO status (3-5 linii)
   - Przerwane zadanie
   - Agent info
   - Last file
   - Propozycje kontynuacji (12+ linii)

3. **Problem z Windows Terminal:**
   - ANSI codes mogą powodować problemy z bufforem
   - Duży output PRZED inicjalizacją inputu
   - Claude Code CLI czeka na exit code z hooka
   - Hook może czekać na flush stdout
   - **Deadlock → zawieszenie**

### Dlaczego testowanie ręczne działało?

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"
```

- Brak kontekstu Claude Code CLI
- Normalny stdout w PowerShell (nie przekierowany)
- Brak konkurencji o stdin/stdout
- Nie ma inicjalizacji inputu w tle

---

## ✅ ROZWIĄZANIE

### Natychmiastowe (WYKONANE):

**Całkowite usunięcie SessionStart hook**

### Długoterminowe (DO WDROŻENIA):

#### Opcja A: Cichy SessionStart Hook

Zastąp verbose script cichym przypomnieniem:

```json
"SessionStart": [
  {
    "type": "command",
    "command": "pwsh -Command \"Write-Host 'Session started. Type /recovery for context.' -ForegroundColor Green\"",
    "timeout": 500
  }
]
```

#### Opcja B: Przenieś funkcjonalność do Slash Command

Utwórz `.claude/commands/recovery.md`:
```markdown
# /recovery - Show Session Recovery Options

[Pełna treść z post_autocompact_recovery.ps1]
```

Użytkownik wywołuje **TYLKO gdy potrzebuje:**
```
/recovery
```

#### Opcja C: Przenieś do UserPromptSubmit (mniej inwazyjne)

Zamiast SessionStart (wywołany RAZ przy starcie), użyj UserPromptSubmit (przy pierwszym promptcie):

```json
"UserPromptSubmit": [
  {
    "type": "command",
    "command": "pwsh -NoProfile -File \".claude\\scripts\\check-recovery-once.ps1\"",
    "timeout": 1000
  }
]
```

`check-recovery-once.ps1`:
```powershell
# Sprawdź flag file
if (Test-Path "_TEMP\.recovery_shown") { exit 0 }

# Pokaż MINIMAL reminder
Write-Host "📋 Previous session detected. Type /recovery for details." -ForegroundColor Cyan

# Ustaw flag
New-Item "_TEMP\.recovery_shown" -Force | Out-Null
exit 0
```

---

## 🔄 TESTOWANIE

### KROK 1: Zamknij obecną sesję Claude Code CLI

```powershell
# Ctrl+C lub zamknij terminal
```

### KROK 2: Usuń cache (opcjonalne)

```powershell
Remove-Item "_TEMP\.recovery_shown" -ErrorAction SilentlyContinue
```

### KROK 3: Uruchom nową sesję

```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
claude
```

### KROK 4: Testuj input

```
ultrathink test - czy terminal dziala poprawnie?
```

### EXPECTED RESULT:

✅ Terminal uruchamia się natychmiast
✅ Input działa od razu
✅ Brak zawieszenia
✅ Brak verbose outputu przy starcie

---

## 📝 WNIOSKI

### Problem:

**SessionStart hook z verbose outputem blokuje inicjalizację Claude Code CLI w Windows Terminal**

### Lekcje:

1. **Hooki SessionStart powinny być MINIMALISTYCZNE**
   - Maksymalnie 1-2 linie outputu
   - Timeout < 1000ms
   - Brak złożonych operacji I/O

2. **Verbose functionality → Slash Commands**
   - Użytkownik wywołuje na żądanie
   - Brak interference z inicjalizacją
   - Lepsze UX

3. **Testuj hooki W KONTEKŚCIE Claude Code CLI**
   - Ręczne uruchomienie skryptu ≠ hook w Claude
   - Windows Terminal ma inne zachowanie
   - ANSI codes mogą powodować problemy

4. **Git history is your friend**
   - Identyfikacja ostatnio dodanych zmian
   - Porównanie working vs broken state

---

## 📁 BACKUP

**Utworzono automatyczne backupy:**
```
.claude\settings.local.json.backup_20251030_1124
.claude\settings.local-kwilinsk5.json.backup_20251030_1124
```

**SessionStart hook dostępny w:**
```
_TOOLS\post_autocompact_recovery.ps1  # Skrypt zachowany
```

**Można przywrócić jako slash command lub cichszą wersję**

---

## 🎯 REKOMENDACJA

**ZALECAM Opcję B: Slash Command /recovery**

**Dlaczego:**
- ✅ Zero interference z inicjalizacją
- ✅ Funkcjonalność dostępna tylko gdy potrzebna
- ✅ Verbose output OK (użytkownik się spodziewa)
- ✅ Łatwe w utrzymaniu
- ✅ Brak ryzyka deadlock

**Implementacja:**

1. Utwórz `.claude/commands/recovery.md`
2. Przenieś logikę z `post_autocompact_recovery.ps1`
3. Dodaj minimalny SessionStart (opcjonalnie):
   ```json
   "SessionStart": [{
     "type": "command",
     "command": "pwsh -Command \"if (Test-Path '_TEMP\\claude_session_state.json') { Write-Host '📋 Previous session detected (/recovery)' -ForegroundColor Cyan }\"",
     "timeout": 500
   }]
   ```

---

**Autor:** Claude (Sonnet 4.5)
**Czas diagnozy:** ~45 minut
**Status:** Czeka na test użytkownika
