---
description: Wyswietl aktualny plan projektu i status zadan
allowed-tools: Read, Glob
---

## PLAN PROJEKTU PPM-CC-LARAVEL

### Krok 1: Lista ETAPow
Wyswietl wszystkie pliki ETAPow z katalogu `Plan_Projektu/`:
```bash
ls -la Plan_Projektu/ETAP_*.md
```

### Krok 2: Znajdz aktualny ETAP
Przejrzyj pliki ETAPow i znajdz:
- Ktory ETAP ma status 🛠️ (w trakcie)?
- Ktore ETAPy sa ✅ (ukonczone)?
- Ktore ETAPy sa ❌ (nie rozpoczete)?

### Krok 3: Szczegoly aktualnego ETAPu
Przeczytaj i wyswietl szczegoly aktualnego ETAPu w trakcie realizacji:
- Nazwa ETAPu
- Lista glownych zadan
- Status kazdego zadania (❌/🛠️/✅/⚠️)
- Utworzone pliki (└──📁 PLIK: ...)

### Krok 4: Nastepne zadanie
Wskaż konkretnie:
- Jakie zadanie jest nastepne do wykonania?
- Czy sa jakies zaleznosci?
- Czy sa blokery?

### Krok 5: Podsumowanie
```
═══════════════════════════════════════════════════════
  PLAN PROJEKTU - STATUS
═══════════════════════════════════════════════════════

UKONCZONYCH ETAPow: [liczba]
AKTUALNY ETAP: [nazwa]
Progress: [X/Y zadan]

NASTEPNE ZADANIE:
[szczegoly zadania]

BLOKERY: [lista lub "Brak"]
═══════════════════════════════════════════════════════
```