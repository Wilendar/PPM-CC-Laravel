# CUSTOM SLASH COMMANDS - PPM-CC-LARAVEL

## DOSTEPNE KOMENDY

### 🚀 `/kontynuuj_ppm`
**Opis:** Automatycznie kontynuuj prace nad projektem zgodnie z planem

**Użycie:**
```
/kontynuuj_ppm
```

**Co robi:**
1. Czyta najnowszy raport z `_REPORTS/Podsumowanie_dnia_*.md`
2. Czyta zasady z `CLAUDE.md`
3. Sprawdza aktualny ETAP w `Plan_Projektu/`
4. Czyta dokumentacje projektu
5. Rozpoczyna prace nad nastepnym zadaniem w planie

**Najwazniejsze do automatycznego startu projektu!**

---

### 📊 `/status_ppm`
**Opis:** Szybki status projektu - postepy, aktualny ETAP, blokery

**Użycie:**
```
/status_ppm
```

**Co pokazuje:**
- Najnowszy raport (skrot)
- Aktualny ETAP i progress
- Ostatnie commity git
- Pending issues/blokery
- Podsumowanie gotowosci do pracy

---

### 📝 `/raport_ppm`
**Opis:** Przeczytaj i wyswietl najnowszy raport projektu

**Użycie:**
```
/raport_ppm
```

**Co robi:**
- Znajduje najnowszy `Podsumowanie_dnia_*.md`
- Wyswietla cala zawartosc raportu

---

### 📋 `/plan_ppm`
**Opis:** Wyswietl aktualny plan projektu i status zadan

**Użycie:**
```
/plan_ppm
```

**Co pokazuje:**
- Liste wszystkich ETAPow
- Aktualny ETAP w trakcie
- Status kazdego zadania
- Nastepne zadanie do wykonania
- Blokery i zaleznosci

---

### 🚢 `/deploy_ppm [sciezka]`
**Opis:** Deploy projektu na serwer produkcyjny (ppm.mpptrade.pl)

**Użycie:**
```
/deploy_ppm
/deploy_ppm app/Http/Controllers/ProductController.php
/deploy_ppm resources/views/
```

**Co robi:**
1. Czyta dane hostingu z `_DOCS/dane_hostingu.md`
2. Uploaduje pliki przez SSH (pscp)
3. Wykonuje post-deployment (cache:clear, view:clear)
4. Weryfikuje dzialanie na produkcji
5. Tworzy raport deployment

**UWAGA:** Deploy na PRODUKCJE - upewnij sie, ze kod przetestowany!

---

### 🤖 `/agent_ppm [nazwa-agenta] [zadanie]`
**Opis:** Deleguj zadanie do specjalistycznego agenta

**Użycie:**
```
/agent_ppm laravel-expert "Stworz model Product z relacjami"
/agent_ppm debugger "Napraw blad w CategoryController"
/agent_ppm
```

**Dostepni agenci:**
- `laravel-expert` - Backend Laravel
- `livewire-specialist` - Komponenty Livewire
- `frontend-specialist` - CSS/JS/Alpine
- `prestashop-api-expert` - Integracja PrestaShop
- `erp-integration-expert` - ERP integrations
- `import-export-specialist` - Excel/CSV
- `deployment-specialist` - Deploy/SSH
- `debugger` - Debug (Opus model)
- `architect` - Architecture planning
- `coding-style-agent` - Code review/QA
- `database-specialist` - Database optimization
- `testing-specialist` - Unit/Integration tests

**Co robi:**
- Czyta przewodnik z `_DOCS/AGENT_USAGE_GUIDE.md`
- Deleguje zadanie do wybranego agenta
- Wymaga od agenta: Context7, raport, przestrzeganie zasad

---

### 📚 `/docs_ppm [nazwa-dokumentu]`
**Opis:** Wyswietl lub zaktualizuj dokumentacje projektu

**Użycie:**
```
/docs_ppm
/docs_ppm CLAUDE.md
/docs_ppm Struktura_Bazy_Danych.md
```

**Co pokazuje:**
- Menu wszystkich dokumentow
- Zawartosc wybranego dokumentu
- Przypomnienie o aktualizacji dokumentacji

**Dokumenty:**
- `CLAUDE.md` - Zasady projektu
- `_DOCS/Struktura_Bazy_Danych.md`
- `_DOCS/Struktura_Plikow_Projektu.md`
- `_DOCS/dane_hostingu.md`
- `_DOCS/AGENT_USAGE_GUIDE.md`
- I wiele wiecej...

---

### 🔍 `/analizuj_strone [URL]`
**Opis:** Kompleksowa diagnostyka problemow z layoutem strony

**Użycie:**
```
/analizuj_strone
/analizuj_strone https://ppm.mpptrade.pl/admin/products/create
```

**Co robi:**
1. Screenshot strony (viewport + full page)
2. Analiza DOM structure (parent-child relationships)
3. CSS computed styles (flexbox, grid, position, z-index)
4. Blade template balance check (div tags)
5. Root cause identification (konkretna linia kodu)
6. Fix implementation i verification
7. Raport w `_AGENT_REPORTS/LAYOUT_FIX_REPORT_*.md`

**Narzedzia tworzone automatycznie:**
- `check_dom_structure.cjs` - Playwright DOM analysis
- `debug_flexbox_styles.cjs` - CSS computed styles
- `trace_container_balance.ps1` - Div balance tracking
- `detailed_balance_LINES.ps1` - Line-by-line analysis
- `quick_upload_blade.ps1` - Deploy fix

**Typowe problemy wykrywane:**
- Right column na dole zamiast po prawej
- Dropdown menu chowajace sie pod innymi elementami
- Sticky positioning nie dziala
- Flexbox layout nie stosuje sie poprawnie
- Niezbalansowane div tagi w Blade templates

---

## JAK UZYWAC KOMEND?

### W Claude Code CLI:
```
/kontynuuj_ppm
```

### W Claude Code Desktop/Web:
Wpisz `/` aby zobaczyc liste dostepnych komend, nastepnie wybierz komende.

---

## NAJCZESCIEJ UZYWANE WORKFLOW

### 🌅 START DNIA:
```
/kontynuuj_ppm
```
To automatycznie:
- Przeczyta najnowszy raport
- Sprawdzi plan
- Rozpocznie prace

### 📊 SPRAWDZENIE STATUSU:
```
/status_ppm
```

### 🤖 DELEGACJA DO AGENTA:
```
/agent_ppm laravel-expert "Zadanie do wykonania"
```

### 🚢 DEPLOYMENT:
```
/deploy_ppm resources/views/products/
```

### 📚 DOKUMENTACJA:
```
/docs_ppm CLAUDE.md
```

### 🔍 DIAGNOSTYKA LAYOUTU:
```
/analizuj_strone
```

---

## STRUKTURA PLIKOW

```
.claude/
└── commands/
    ├── README.md                    (ten plik)
    ├── kontynuuj_ppm.md            (glowna komenda startu)
    ├── status_ppm.md               (status projektu)
    ├── raport_ppm.md               (najnowszy raport)
    ├── plan_ppm.md                 (plan projektu)
    ├── deploy_ppm.md               (deployment)
    ├── agent_ppm.md                (delegacja do agentow)
    ├── docs_ppm.md                 (dokumentacja)
    └── analizuj_strone.md          (diagnostyka layoutu)
```

---

## TWORZENIE WLASNYCH KOMEND

Jesli chcesz stworzyc wlasna komende:

1. Stworz plik `.claude/commands/nazwa_komendy.md`
2. Dodaj frontmatter:
```markdown
---
description: Opis komendy
allowed-tools: Read, Write, Bash
argument-hint: [opcjonalne argumenty]
---
```
3. Napisz prompt - to co komenda ma wykonac
4. Uzywaj `$1`, `$2` dla argumentow, `$ARGUMENTS` dla wszystkich
5. Uzywaj `@plik.md` do referencji plikow

Wiecej info: https://docs.claude.com/en/docs/claude-code/slash-commands

---

## STATUS
✅ WSZYSTKIE KOMENDY GOTOWE DO UZYCIA

**Restart Claude Code aby zaladowac nowe komendy!**