# SYSTEM AUTOMATYCZNEGO PROMPTA STARTOWEGO
**Data:** 2025-09-30
**Wersja:** 1.0

## WYMAGANIA UŻYTKOWNIKA
Hook startowy, który automatycznie:
1. Generuje prompt z instrukcjami
2. Uruchamia go w Claude Code
3. Zawiera instrukcje kontynuacji pracy nad projektem
4. Czyta najnowszy raport z _REPORTS/
5. Wymusza stosowanie zasad z CLAUDE.md
6. Wymusza używanie MCP Context7
7. Zabrania przeskakiwania punktów w planie

## ⚠️ WAŻNE OGRANICZENIA TECHNICZNE

### NIEMOŻLIWE: Automatyczne uruchamianie promptów
Zgodnie z dokumentacją Claude Code hooks:
- **Hooki NIE MOGĄ automatycznie uruchamiać promptów w Claude Code**
- `additionalContext` w SessionStart to TYLKO kontekst, nie instrukcja do wykonania
- Nie ma mechanizmu "wstrzyknięcia" prompta do CLI

### Co jest możliwe:
1. SessionStart z `additionalContext` - Claude zobaczy kontekst, ale NIE wykona automatycznie akcji
2. Helper script - generuje prompt, który użytkownik kopiuje i wkleja
3. Wyświetlanie przypomnienia przy każdym prompt (UserPromptSubmit)

## DOSTARCZONE ROZWIĄZANIA

### ROZWIĄZANIE A: Helper Script (ZALECANE)
**Plik:** `.claude/scripts/generate-startup-prompt.ps1`

**Jak działa:**
1. Znajduje najnowszy raport z _REPORTS/Podsumowanie_dnia_*.md
2. Generuje gotowy prompt z instrukcjami
3. Wyświetla prompt do skopiowania
4. Zapisuje do `.claude/STARTUP_PROMPT.txt`

**Użycie:**
```bash
pwsh -NoProfile -ExecutionPolicy Bypass -File ".claude\scripts\generate-startup-prompt.ps1"
```

**Wyjście:**
```
╔════════════════════════════════════════════════════════════════╗
║           PPM-CC-LARAVEL STARTUP PROMPT                        ║
╚════════════════════════════════════════════════════════════════╝

ultrathink Kontynuuj prace nad projektem zgodnie z planem.

KROK 1 - ZAPOZNAJ SIE Z AKTUALNYM STATUSEM:
• Przeczytaj najnowszy raport: @_REPORTS\Podsumowanie_dnia_2025-09-24_16-26.md
• Przeczytaj zasady projektu: @CLAUDE.md
• Sprawdz aktualny ETAP: @Plan_Projektu/

[... pełny prompt ...]
```

**Zalety:**
- ✅ Niezawodne - zawsze działa
- ✅ Brak ryzyka freeze CLI
- ✅ Pełna kontrola użytkownika
- ✅ Zawiera najnowszy raport automatycznie
- ✅ Zapisuje do pliku dla wygody

**Wady:**
- ❌ Wymaga ręcznego skopiowania i wklejenia prompta
- ❌ Nie jest w pełni automatyczne

---

### ROZWIĄZANIE B: SessionStart Hook (EKSPERYMENTALNE)
**Plik:** `.claude/scripts/session-auto-context.ps1`

**Jak działa:**
1. Uruchamia się przy starcie sesji Claude Code
2. Znajduje najnowszy raport
3. Zwraca JSON z `additionalContext`
4. Claude otrzymuje kontekst na starcie sesji

**⚠️ OSTRZEŻENIE:**
- SessionStart może powodować freeze Claude Code CLI (problem z poprzednich wersji)
- `additionalContext` to TYLKO kontekst, nie gwarantuje wykonania akcji przez Claude
- Claude może zobaczyć instrukcje, ale NIE musi ich wykonać automatycznie

**Konfiguracja w settings.local.json:**
```json
{
  "hooks": {
    "SessionStart": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "pwsh -NoProfile -ExecutionPolicy Bypass -File \".claude\\scripts\\session-auto-context.ps1\"",
            "timeout": 3000
          }
        ]
      }
    ]
  }
}
```

**Zalety:**
- ✅ Automatyczne - nie wymaga akcji użytkownika
- ✅ Claude otrzymuje kontekst na starcie
- ✅ Zawiera najnowszy raport automatycznie

**Wady:**
- ❌ RYZYKO freeze Claude Code CLI
- ❌ Nie gwarantuje wykonania akcji przez Claude
- ❌ additionalContext to tylko kontekst, nie instrukcja
- ❌ Claude może zignorować kontekst

---

## REKOMENDACJA

### ✅ ZALECANE: Rozwiązanie A (Helper Script)

**Dlaczego:**
1. **Niezawodne** - zawsze działa
2. **Bezpieczne** - brak ryzyka freeze
3. **Kontrolowane** - użytkownik decyduje kiedy uruchomić
4. **Skuteczne** - prompt jest faktycznie wykonany przez Claude

**Workflow:**
1. Uruchom skrypt helper:
   ```bash
   pwsh .claude/scripts/generate-startup-prompt.ps1
   ```
2. Skopiuj wygenerowany prompt
3. Wklej do Claude Code CLI
4. Claude wykona instrukcje

### ⚠️ NIE ZALECANE: Rozwiązanie B (SessionStart)

**Dlaczego:**
1. **Niestabilne** - może powodować freeze CLI
2. **Nieskuteczne** - additionalContext to tylko kontekst, nie instrukcja
3. **Nierzetelne** - Claude może zignorować kontekst

**Użyj tylko jeśli:**
- Akceptujesz ryzyko freeze
- Rozumiesz, że Claude może nie wykonać akcji automatycznie
- Chcesz eksperymentować

---

## ALTERNATYWNE ROZWIĄZANIA

### OPCJA 1: Alias PowerShell
Stwórz alias dla szybkiego generowania prompta:

```powershell
# W PowerShell Profile (~/.config/powershell/profile.ps1)
function Start-PPM {
    pwsh -NoProfile -ExecutionPolicy Bypass -File "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\scripts\generate-startup-prompt.ps1"
}
Set-Alias -Name ppm-start -Value Start-PPM
```

Użycie:
```bash
ppm-start
```

### OPCJA 2: Skrót klawiszowy (Windows)
Stwórz skrót `.lnk` do skryptu z przypisanym klawiszem (np. Ctrl+Alt+P)

### OPCJA 3: UserPromptSubmit reminder
Dodaj hook UserPromptSubmit, który przy pierwszym prompt w sesji wyświetla przypomnienie:

```json
{
  "UserPromptSubmit": [
    {
      "hooks": [
        {
          "type": "command",
          "command": "pwsh -Command \"Write-Host 'REMINDER: Run generate-startup-prompt.ps1 for full context!' -ForegroundColor Yellow\""
        }
      ]
    }
  ]
}
```

---

## PRZYKŁAD UŻYCIA

### Scenariusz: Rozpoczęcie pracy nad projektem

**Krok 1:** Uruchom generator prompta
```bash
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
pwsh .claude/scripts/generate-startup-prompt.ps1
```

**Krok 2:** Skopiuj wygenerowany prompt (jest także w `.claude/STARTUP_PROMPT.txt`)

**Krok 3:** Uruchom Claude Code CLI
```bash
claude code
```

**Krok 4:** Wklej prompt do Claude Code

**Krok 5:** Claude wykona instrukcje:
- Przeczyta najnowszy raport
- Przeczyta CLAUDE.md
- Sprawdzi Plan_Projektu/
- Przeczyta dokumentację
- Rozpocznie pracę zgodnie z planem

---

## ZAWARTOŚĆ GENEROWANEGO PROMPTA

```
ultrathink Kontynuuj prace nad projektem zgodnie z planem.

KROK 1 - ZAPOZNAJ SIE Z AKTUALNYM STATUSEM:
• Przeczytaj najnowszy raport: @_REPORTS\Podsumowanie_dnia_[data].md
• Przeczytaj zasady projektu: @CLAUDE.md
• Sprawdz aktualny ETAP: @Plan_Projektu/

KROK 2 - ZAPOZNAJ SIE ZE STRUKTURA:
• Przeczytaj: @_DOCS\Struktura_Bazy_Danych.md
• Przeczytaj: @_DOCS\Struktura_Plikow_Projektu.md
• Przeczytaj: @_DOCS\dane_hostingu.md

KROK 3 - ZASADY PRACY:
⚠️ KONIECZNE: Stosowanie MCP Context7 w tym projekcie
⚠️ ZAKAZ: Przeskakiwania/omijania punktow w planie
⚠️ WYMAGANE: Aktualizowanie dokumentacji przy zmianach
⚠️ WYMAGANE: Sam uruchamiaj Toolsy i weryfikuj dzialanie
⚠️ WYMAGANE: Testuj kod na stronie: https://ppm.mpptrade.pl

KROK 4 - ROZPOCZNIJ PRACE:
Wykonuj zadania jedno po drugim zgodnie z Planem_Projektu/.
Wyjatkiem jest zaleznosc od innego zadania/ETAPu.
```

---

## PLIKI SYSTEMU

### Utworzone:
- `.claude/scripts/generate-startup-prompt.ps1` - generator prompta (ZALECANE)
- `.claude/scripts/session-auto-context.ps1` - SessionStart hook (EKSPERYMENTALNE)
- `.claude/STARTUP_PROMPT.txt` - ostatnio wygenerowany prompt
- `_DOCS/AUTO_STARTUP_PROMPT_SYSTEM.md` - dokumentacja

### Do edycji:
- `.claude/settings.local.json` - konfiguracja hooków (opcjonalnie dla Rozwiązania B)

---

## FAQ

### Q: Czy mogę w pełni zautomatyzować uruchamianie prompta?
**A:** Nie. Claude Code hooks nie obsługują automatycznego uruchamiania promptów. Musisz ręcznie wkleić wygenerowany prompt.

### Q: Dlaczego SessionStart nie działa jak chcę?
**A:** SessionStart z `additionalContext` to tylko kontekst dla Claude, nie instrukcja do wykonania. Claude może go zobaczyć, ale nie jest zmuszony do wykonania akcji.

### Q: Co jeśli SessionStart spowoduje freeze?
**A:** Usuń SessionStart hook z `settings.local.json` i użyj Rozwiązania A (Helper Script).

### Q: Czy mogę edytować treść prompta?
**A:** Tak, edytuj plik `generate-startup-prompt.ps1`, sekcję `$prompt = @"..."@`

### Q: Jak często jest aktualizowany najnowszy raport?
**A:** Skrypt automatycznie znajduje najnowszy plik `Podsumowanie_dnia_*.md` na podstawie daty modyfikacji.

---

## STATUS
✅ **GOTOWE DO UŻYCIA**

**Zalecane użycie:** Rozwiązanie A (Helper Script)