# FINALNA NAPRAWA HOOKÓW - Windows Terminal Safe

**Data:** 2025-10-30 12:00
**Status:** ✅ NAPRAWIONE - READY TO TEST
**Problem 1:** 3 niepoprawne ustawienia w settings.json
**Problem 2:** SessionStart hook zawiesza Windows Terminal

---

## 🔧 NAPRAWIONE PROBLEMY

### Problem 1: Niepoprawna struktura hooków (3 błędy)

**Błąd:** Hooki BEZ matchera miały błędną strukturę (brak dodatkowego poziomu `"hooks": []`)

**Naprawione hooki:**
1. ✅ UserPromptSubmit
2. ✅ PreCompact
3. ✅ SessionStart

**PRZED (błędne):**
```json
"UserPromptSubmit": [
  {
    "type": "command",
    "command": "...",
    "timeout": 2000
  }
]
```

**PO (poprawne - zgodne z Git history):**
```json
"UserPromptSubmit": [
  {
    "hooks": [
      {
        "type": "command",
        "command": "...",
        "timeout": 2000
      }
    ]
  }
]
```

---

### Problem 2: SessionStart hook zawiesza terminal

**Root cause:** ANSI escape codes w dużym output podczas inicjalizacji Windows Terminal

**Rozwiązanie:**

1. **Usunięto ANSI colors:**
   - ❌ PRZED: `Write-Host '...' -ForegroundColor Cyan` (ANSI codes)
   - ✅ PO: `Write-Output '...'` (czysty tekst)

2. **Zbuforowany output:**
   - ❌ PRZED: 30+ osobnych Write-Host calls
   - ✅ PO: Jeden bufor `$output`, wysłany jednorazowo

3. **Timeout zwiększony:**
   - ❌ PRZED: 3000ms (mogło nie wystarczyć na Windows)
   - ✅ PO: 5000ms (bezpieczny margines)

4. **Simplified output:**
   - Usunięto nadmiarowe dekoracje
   - Zachowano wszystkie informacje
   - Lepsza czytelność w zwykłym tekście

---

## 📋 NOWY SKRYPT post_autocompact_recovery.ps1

### Kluczowe zmiany:

**1. NO ANSI codes:**
```powershell
# PRZED (problematyczne)
Write-Host "${cyan}=== RECOVERY ===${reset}"
Write-Host "${yellow}Context: $context${reset}"

# PO (bezpieczne)
$output += "=== CLAUDE SESSION RECOVERY ==="
$output += "Context: $context"
$output | ForEach-Object { Write-Output $_ }
```

**2. Buffered output:**
```powershell
$output = @()
# ... build all lines in array ...
$output | ForEach-Object { Write-Output $_ }  # One shot
```

**3. Exit 0 zawsze:**
```powershell
# CRITICAL: Hook MUST exit with code 0 (success)
exit 0
```

### Output teraz wygląda tak:

```
=== CLAUDE SESSION RECOVERY ===
Previous session detected from 95 minutes ago

Context: Testing TODO persistence system implementation

TODO Status (3 total): Completed: 1 | In Progress: 1 | Pending: 1

Interrupted task: Implement TODO snapshot system
Agent: general-purpose (was in progress)

OPTIONS:
  A) Continue from interruption
  B) Update plan based on progress
  C) Start new task (archive previous TODO)
  D) Full context review (Plan + Reports)

===================================
```

**Czytelny, informacyjny, BEZ ANSI codes = bezpieczny dla Windows Terminal**

---

## ✅ ZWERYFIKOWANE

### Test 1: Ręczne uruchomienie skryptu ✅
```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"
```
**Result:** ✅ Wyświetla pełne recovery info, exit 0, brak błędów

### Test 2: Walidacja JSON ✅
```powershell
Get-Content ".claude\settings.local.json" -Raw | ConvertFrom-Json
Get-Content ".claude\settings.local-kwilinsk5.json" -Raw | ConvertFrom-Json
```
**Result:** ✅ Oba pliki VALID JSON

### Test 3: Struktura hooków ✅
- UserPromptSubmit: ✅ Poprawna struktura `"hooks": []`
- PreToolUse: ✅ Poprawna struktura z matcherem
- PostToolUse: ✅ Poprawna struktura z matcherem
- PreCompact: ✅ Poprawna struktura `"hooks": []`
- SessionStart: ✅ Poprawna struktura `"hooks": []`

---

## 📊 PORÓWNANIE

| Element | Przed | Po | Status |
|---------|-------|-----|---------|
| **Struktura hooków** | ❌ 3 błędy | ✅ Poprawne | NAPRAWIONE |
| **ANSI codes** | ❌ 30+ linii | ✅ Brak | NAPRAWIONE |
| **Output method** | ❌ 30+ Write-Host | ✅ Zbuforowany | NAPRAWIONE |
| **Timeout** | ⚠️ 3000ms | ✅ 5000ms | ZWIĘKSZONY |
| **Exit code** | ✅ exit 0 | ✅ exit 0 | OK |
| **Funkcjonalność** | ✅ Full info | ✅ Full info | ZACHOWANA |

---

## 🚀 JAK TESTOWAĆ

### KROK 1: Zamknij obecną sesję Claude Code CLI
```powershell
# Ctrl+C lub zamknij terminal
```

### KROK 2: Uruchom NOWĄ sesję
```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
claude
```

### KROK 3: Sprawdź co się dzieje

**EXPECTED:**
- ✅ Terminal uruchamia się natychmiast (< 3s)
- ✅ Widzisz recovery info (jeśli snapshot < 24h)
- ✅ Input działa od razu
- ✅ Brak zawieszenia

**Recovery output powinien wyglądać tak:**
```
=== CLAUDE SESSION RECOVERY ===
Previous session detected from XX minutes ago
Context: [poprzedni context]
TODO Status (X total): Completed: X | In Progress: X | Pending: X
Interrupted task: [nazwa zadania]
OPTIONS:
  A) Continue from interruption
  B) Update plan based on progress
  C) Start new task (archive previous TODO)
  D) Full context review (Plan + Reports)
===================================
```

### KROK 4: Sprawdź inne hooki

```
# Test UserPromptSubmit (przy każdym promptcie)
test message

# Test PreToolUse (przy edycji PHP)
# (automatycznie gdy użyjesz Write/Edit)

# Test PostToolUse (po edycji PHP)
# (automatycznie po Write/Edit)
```

---

## 📁 ZMODYFIKOWANE PLIKI

```
✅ _TOOLS\post_autocompact_recovery.ps1       # Przepisany (NO ANSI, buffered)
✅ .claude\settings.local.json                 # Naprawiona struktura hooków
✅ .claude\settings.local-kwilinsk5.json       # Naprawiona struktura hooków
```

### Backup:
```
.claude\settings.local.json.backup_20251030_1124
.claude\settings.local-kwilinsk5.json.backup_20251030_1124
```

---

## 🔍 CO SIĘ ZMIENIŁO W SKRYPCIE

### Funkcje usunięte:
- ❌ `Show-Banner` - używało ANSI colors
- ❌ `Show-SnapshotSummary` - używało ANSI colors
- ❌ `Show-ContinuationOptions` - używało ANSI colors
- ❌ Wszystkie ANSI color variables (`$cyan`, `$yellow`, etc.)

### Funkcje zachowane:
- ✅ Czytanie snapshot JSON
- ✅ Sprawdzanie wieku snapshotu (< 24h)
- ✅ Liczenie TODO status (completed/in_progress/pending)
- ✅ Wyświetlanie przerwanych zadań
- ✅ Wyświetlanie agenta in progress
- ✅ Opcje kontynuacji (A/B/C/D)
- ✅ Minimal reminder (gdy brak snapshotu)

### Nowe podejście:
```powershell
# Build output in array
$output = @()
$output += "Line 1"
$output += "Line 2"
# ...

# Output all at once (buffered, safe for hooks)
$output | ForEach-Object { Write-Output $_ }
```

**Dlaczego bezpieczne:**
- `Write-Output` nie używa ANSI codes
- Zbuforowany output = jedna operacja I/O
- No color formatting = no terminal deadlock
- No host interaction = safe for hooks

---

## ⚙️ SETTINGS.JSON - FINALNA STRUKTURA

### Hooki BEZ matchera:
```json
"UserPromptSubmit": [
  {
    "hooks": [
      {
        "type": "command",
        "command": "...",
        "timeout": 2000
      }
    ]
  }
]
```

### Hooki Z matcherem:
```json
"PreToolUse": [
  {
    "matcher": "(Write|Edit).*\\.php",
    "hooks": [
      {
        "type": "command",
        "command": "...",
        "timeout": 2000
      }
    ]
  }
]
```

**Zasada:**
- Bez matchera: `[{ "hooks": [ ... ] }]`
- Z matcherem: `[{ "matcher": "...", "hooks": [ ... ] }]`

---

## 🎯 EXPECTED BEHAVIOR

### Przy starcie Claude Code CLI:

1. **SessionStart hook wykonuje się:**
   - Sprawdza snapshot
   - Jeśli < 24h → wyświetla recovery info
   - Jeśli > 24h lub brak → minimal reminder
   - Exit 0

2. **Terminal gotowy do użycia:**
   - Input aktywny
   - Brak zawieszenia
   - Pełna funkcjonalność

3. **Przy każdym promptcie:**
   - UserPromptSubmit hook wyświetla context reminder
   - Przypomina o CLAUDE.md, Context7, Plan_Projektu

4. **Przy edycji kodu:**
   - PreToolUse hook przypomina o Context7
   - PostToolUse hook potwierdza zmiany

---

## 🐛 TROUBLESHOOTING

### Problem: Terminal nadal się zawiesza

**Check:**
```powershell
# Test skryptu ręcznie
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"

# Sprawdź ile czasu zajmuje
Measure-Command {
    & "_TOOLS\post_autocompact_recovery.ps1"
}
```

**Expected:** < 1000ms execution time

**Jeśli nadal problem:**
1. Tymczasowo usuń SessionStart:
   ```json
   "SessionStart": []
   ```
2. Restart Claude
3. Dodaj SessionStart z dłuższym timeout (10000ms)

### Problem: Recovery info się nie wyświetla

**Check:**
```powershell
# Sprawdź czy snapshot istnieje
Test-Path "_TEMP\claude_session_state.json"

# Sprawdź wiek snapshotu
(Get-Item "_TEMP\claude_session_state.json").LastWriteTime
```

**Expected:**
- Plik istnieje
- LastWriteTime < 24h od teraz

### Problem: Błędy w JSON

**Check:**
```powershell
Get-Content ".claude\settings.local.json" -Raw | ConvertFrom-Json
```

**Expected:** No errors

**Jeśli błąd:** Przywróć backup
```powershell
Copy-Item ".claude\settings.local.json.backup_20251030_1124" ".claude\settings.local.json"
```

---

## 📝 PODSUMOWANIE

### Co naprawiono:
1. ✅ 3 niepoprawne struktury hooków w settings.json
2. ✅ SessionStart hook przepisany (NO ANSI, buffered output)
3. ✅ Timeout zwiększony do 5000ms
4. ✅ Zachowana pełna funkcjonalność recovery info

### Co się NIE zmieniło:
- ✅ Wszystkie inne hooki działają jak wcześniej
- ✅ Recovery info wyświetla się automatycznie przy starcie
- ✅ Pełna informacja o poprzedniej sesji
- ✅ Opcje kontynuacji (A/B/C/D)

### Dlaczego teraz powinno działać:
1. **Poprawna struktura JSON** - zgodna z dokumentacją
2. **Brak ANSI codes** - nie blokuje Windows Terminal
3. **Buffered output** - jedna operacja I/O zamiast 30+
4. **Dłuższy timeout** - margines bezpieczeństwa dla Windows
5. **Tested** - ręczne testy przeszły OK

---

**TERAZ:** Restart Claude Code CLI i przetestuj!

**Jeśli działa:** Wszystko naprawione ✅
**Jeśli nie działa:** Zobacz Troubleshooting powyżej lub zgłoś dodatkowe info

---

**Autor:** Claude (Sonnet 4.5)
**Czas naprawy:** ~2h (iteracyjne podejście)
**Status:** READY FOR PRODUCTION TEST
