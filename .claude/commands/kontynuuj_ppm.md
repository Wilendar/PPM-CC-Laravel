---
description: Kontynuuj prace nad projektem PPM-CC-Laravel zgodnie z planem
allowed-tools: Read, Glob, Bash, Task, mcp__context7__get-library-docs
---

ultrathink Kontynuuj prace nad projektem PPM-CC-Laravel zgodnie z planem.

## KROK 1: ZAPOZNAJ SIE Z AKTUALNYM STATUSEM

**PRIORYTET NAJWYZSZY:** Znajdz i przeczytaj NAJNOWSZY raport z katalogu `_REPORTS/`:
- Uzyj Glob tool: pattern `Podsumowanie_dnia_*.md` w `_REPORTS/`
- Posortuj po dacie modyfikacji (najnowszy pierwszy)
- Przeczytaj najnowszy raport w calosci
- To jest KLUCZOWE - raport zawiera aktualny status projektu!

**Przeczytaj zasady projektu:**
@CLAUDE.md

**Sprawdz aktualny ETAP pracy:**
- Przejrzyj katalog `Plan_Projektu/`
- Znajdz aktualny ETAP (ETAP_XX) w trakcie realizacji
- Przeczytaj szczegoly aktualnego ETAPu

## KROK 2: ZAPOZNAJ SIE ZE STRUKTURA PROJEKTU

**Dokumentacja struktury (WYMAGANE):**
@_DOCS/Struktura_Bazy_Danych.md
@_DOCS/Struktura_Plikow_Projektu.md
@_DOCS/dane_hostingu.md
@_DOCS/PPM_Color_Style_Guide.md

## KROK 3: ZASADY PRACY (CRITICAL!)

⚠️ **KONIECZNE:** Stosowanie MCP Context7 w tym projekcie
- Przed pisaniem kodu Laravel: `mcp__context7__get-library-docs /websites/laravel_12_x`
- Przed pisaniem kodu Livewire: `mcp__context7__get-library-docs /livewire/livewire`
- API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3

⚠️ **ZAKAZ:** Przeskakiwania/omijania punktow w planie
- Wykonuj zadania jedno po drugim
- Tylko zaleznosci od innych zadan pozwalaja na przeskok

⚠️ **WYMAGANE:** Aktualizowanie dokumentacji przy zmianach
- Po zmianach w bazie: aktualizuj `_DOCS/Struktura_Bazy_Danych.md`
- Po zmianach w strukturze: aktualizuj `_DOCS/Struktura_Plikow_Projektu.md`

⚠️ **WYMAGANE:** Sam uruchamiaj Toolsy i weryfikuj dzialanie
- Po deploy: testuj na https://ppm.mpptrade.pl
- Uzywaj curl/browser do weryfikacji
- Sprawdzaj logi i errory

⚠️ **OBOWIAZKI AGENTOW:**
- Agent MUSI uzyc Context7 przed implementacja
- Agent MUSI stworzyc raport w `_AGENT_REPORTS/`
- Agent MUSI przestrzegac zasad z CLAUDE.md

## KROK 4: ROZPOCZNIJ PRACE

**Workflow:**
1. Zidentyfikuj nastepne zadanie do wykonania w Planie_Projektu/
2. Sprawdz zaleznosci - czy mozna wykonac to zadanie teraz?
3. Jesli tak - stworz TODO list z TaskWrite
4. Uzyj Context7 przed pisaniem kodu
5. Wykonaj zadanie krok po kroku
6. Przetestuj rozwiazanie
7. Zaktualizuj Plan_Projektu/ (oznacz zadanie jako ukonczone)
8. Zaktualizuj dokumentacje jesli potrzeba
9. Przejdz do nastepnego zadania

**Deployment:**
- Host: ppm.mpptrade.pl (Hostido)
- Dane w: @_DOCS/dane_hostingu.md
- Po deploy: ZAWSZE testuj na produkcji

**Problemy/Blokery:**
- Jesli napotkasz bloker: oznacz zadanie w planie jako ⚠️
- Opisz bloker w zadaniu
- Przejdz do nastepnego niezablokowanego zadania

---

**ROZPOCZNIJ PRACE TERAZ!**