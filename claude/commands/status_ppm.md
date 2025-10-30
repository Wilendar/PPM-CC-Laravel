---
description: Sprawdz aktualny status projektu PPM-CC-Laravel
allowed-tools: Read, Glob, Bash
---

## SZYBKI STATUS PROJEKTU PPM-CC-LARAVEL

Wykonaj nastepujace kroki:

### 1. NAJNOWSZY RAPORT
Znajdz i wyswietl SKROT najnowszego raportu z `_REPORTS/`:
- Uzyj Glob: `Podsumowanie_dnia_*.md` w `_REPORTS/`
- Przeczytaj najnowszy raport
- Wyswietl: date, glowne wykonane zadania, aktualny status

### 2. AKTUALNY ETAP
Sprawdz Plan_Projektu/:
- Ktory ETAP jest w trakcie realizacji? (🛠️)
- Ile zadan ukonczone vs pozostale w tym ETAPie?
- Jakie zadanie jest aktualnie in_progress?

### 3. OSTATNIE ZMIANY
Sprawdz ostatnie commity w git:
```bash
git log --oneline -10
```

### 4. PENDING ISSUES
Sprawdz czy sa jakies blokery/issues:
- Przejrzyj `_ISSUES_FIXES/`
- Sprawdz czy sa zadania z oznaczeniem ⚠️ w Planie

### 5. PODSUMOWANIE
Wyswietl zwiezle podsumowanie:
- Aktualny ETAP: [nazwa]
- Progress ETAPu: [X/Y zadan]
- Ostatnia aktywnosc: [data z raportu]
- Blokery: [liczba]
- Gotowe do pracy: [TAK/NIE]