---
description: Wyswietl lub zaktualizuj dokumentacje projektu PPM-CC-Laravel
allowed-tools: Read, Write, Edit
argument-hint: [opcjonalnie: nazwa dokumentu]
---

## DOKUMENTACJA PROJEKTU PPM-CC-LARAVEL

### DOKUMENTY DOSTEPNE:

**Zasady projektu:**
- `CLAUDE.md` - Glowne zasady projektu i workflow

**Dokumentacja techniczna (_DOCS/):**
- `Struktura_Bazy_Danych.md` - Schema bazy, tabele, relacje
- `Struktura_Plikow_Projektu.md` - Organizacja katalogow i plikow
- `dane_hostingu.md` - Dane SSH, bazy, deployment
- `AGENT_USAGE_GUIDE.md` - Jak delegowac do agentow
- `AUTO_STARTUP_PROMPT_SYSTEM.md` - System auto-startup
- `HOOKS_SYSTEM_V3_2025-09-30.md` - System hookow

**Raporty (_REPORTS/):**
- `Podsumowanie_dnia_*.md` - Codzienne raporty postepow

**Raporty agentow (_AGENT_REPORTS/):**
- Raporty z wykonanych zadan przez agentow

**Plan projektu (Plan_Projektu/):**
- `ETAP_XX.md` - Szczegolowy plan kazdego etapu

### AKCJE:

**Jezeli podano $1 (nazwa dokumentu):**
Wyswietl zawartosc dokumentu: `$1`

**Jezeli NIE podano argumentu:**
Wyswietl menu:

```
═══════════════════════════════════════════════════════
  DOKUMENTACJA PPM-CC-LARAVEL
═══════════════════════════════════════════════════════

📚 GLOWNE DOKUMENTY:
  1. CLAUDE.md - Zasady projektu
  2. _DOCS/Struktura_Bazy_Danych.md
  3. _DOCS/Struktura_Plikow_Projektu.md
  4. _DOCS/dane_hostingu.md
  5. _DOCS/AGENT_USAGE_GUIDE.md

📋 PLAN PROJEKTU:
  • Plan_Projektu/ - Wszystkie ETAPy

📊 RAPORTY:
  • _REPORTS/ - Raporty postepow
  • _AGENT_REPORTS/ - Raporty agentow

💡 SYSTEMOWE:
  • _DOCS/HOOKS_SYSTEM_V3_2025-09-30.md
  • _DOCS/AUTO_STARTUP_PROMPT_SYSTEM.md

═══════════════════════════════════════════════════════

Wpisz numer lub nazwe dokumentu do wyswietlenia.
```

### AKTUALIZACJA DOKUMENTACJI:

Pamietaj: Po zmianach w projekcie MUSISZ zaktualizowac odpowiednie dokumenty:
- Zmiany w bazie → `Struktura_Bazy_Danych.md`
- Nowe pliki/katalogi → `Struktura_Plikow_Projektu.md`
- Nowe/zmienione reguły → `CLAUDE.md`