---
description: Deleguj zadanie do specjalistycznego agenta PPM-CC-Laravel
allowed-tools: Task, Read
argument-hint: [nazwa-agenta] [opis-zadania]
---

## DELEGACJA DO AGENTA

### Dostepni agenci:
- **laravel-expert** - Backend Laravel, modele, migrations, controllers
- **livewire-specialist** - Komponenty Livewire, interakcje frontend
- **frontend-specialist** - CSS, JavaScript, Alpine.js, UI/UX
- **prestashop-api-expert** - Integracja z PrestaShop API
- **erp-integration-expert** - Integracje ERP (BaseLinker, Subiekt, Dynamics)
- **import-export-specialist** - Import/Export Excel, CSV, mapowanie danych
- **deployment-specialist** - Deployment, SSH, server management
- **debugger** - Debug problemow (model: Opus)
- **architect** - Planowanie architektury, design patterns
- **coding-style-agent** - Code review, PSR-12, quality assurance
- **database-specialist** - Optymalizacja bazy, migrations, seeds
- **testing-specialist** - Testy jednostkowe, integracyjne

### Parametry:
- **$1** - Nazwa agenta (wymagane)
- **$2+** - Opis zadania (opcjonalne)

### PRZED DELEGACJA - SPRAWDZ:

1. **Przeczytaj przewodnik:**
@_DOCS/AGENT_USAGE_GUIDE.md

2. **Wybierz odpowiedniego agenta** bazujac na typie zadania

3. **Przygotuj kontekst:**
   - Aktualne pliki do modyfikacji
   - Wymagania zadania
   - Zaleznosci od innych zadan

### DELEGUJ ZADANIE:

**Jezeli podano $1 (nazwa agenta):**
```
Deleguj zadanie do agenta: $1

Zadanie: $ARGUMENTS

WYMAGANIA DLA AGENTA:
✓ MUST use Context7 before implementation
✓ MUST follow CLAUDE.md rules
✓ MUST create report in _AGENT_REPORTS/
✓ NO HARDCODING, NO MOCK DATA
✓ Update documentation after changes

Context dla agenta:
- CLAUDE.md: Project rules
- Plan_Projektu/: Current ETAP
- _DOCS/: Project documentation
```

**Jezeli NIE podano argumentow:**
Wyswietl liste dostepnych agentow i zapytaj uzytkownika:
- Jakiego agenta wybrac?
- Jakie zadanie do wykonania?

### PO WYKONANIU PRZEZ AGENTA:

1. Sprawdz raport w `_AGENT_REPORTS/`
2. Zweryfikuj wykonane zmiany
3. Przetestuj funkcjonalnosc
4. Rozważ code review przez `coding-style-agent`