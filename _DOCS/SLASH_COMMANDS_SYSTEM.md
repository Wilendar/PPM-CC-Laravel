# SYSTEM SLASH COMMANDS - PPM-CC-LARAVEL
**Data:** 2025-09-30
**Wersja:** 1.0
**Status:** ✅ PRODUKCYJNY

## GENEZA SYSTEMU

### Problem do rozwiązania:
Użytkownik chciał automatycznego uruchamiania prompta startowego w Claude Code CLI, który:
1. Czyta najnowszy raport z _REPORTS/
2. Stosuje zasady z CLAUDE.md
3. Wymusza Context7
4. Zabrania przeskakiwania zadań w planie
5. Weryfikuje kod na produkcji

### Ewolucja rozwiązań:

#### ❌ Próba 1: SessionStart Hook
**Problem:** Powodował freeze Claude Code CLI
**Przyczyna:** SessionStart z/bez matchera blokowało input

#### ⚠️ Próba 2: Helper Script + ręczne wklejanie
**Problem:** Wymaga ręcznej akcji użytkownika
**Wady:** Nie jest w pełni automatyczne, trzeba kopiować prompt

#### ✅ Rozwiązanie finalne: Custom Slash Commands
**Zalety:**
- Jedno polecenie: `/kontynuuj_ppm`
- Pełna automatyzacja
- Brak freeze
- Natywna integracja z Claude Code
- Możliwość parametryzacji

---

## ARCHITEKTURA SYSTEMU

### Lokalizacja:
```
.claude/
└── commands/
    ├── README.md                    # Dokumentacja komend
    ├── kontynuuj_ppm.md            # 🚀 Główna komenda startu
    ├── status_ppm.md               # 📊 Status projektu
    ├── raport_ppm.md               # 📝 Najnowszy raport
    ├── plan_ppm.md                 # 📋 Plan projektu
    ├── deploy_ppm.md               # 🚢 Deployment
    ├── agent_ppm.md                # 🤖 Delegacja do agentów
    └── docs_ppm.md                 # 📚 Dokumentacja
```

### Struktura komendy:
```markdown
---
description: Krótki opis komendy
allowed-tools: Read, Glob, Bash, Task
argument-hint: [opcjonalne argumenty]
---

# Treść prompta do wykonania przez Claude

Instrukcje krok po kroku...

Użycie parametrów:
- $1, $2, $3 - poszczególne argumenty
- $ARGUMENTS - wszystkie argumenty razem
- @plik.md - referencja do pliku
```

---

## DOSTĘPNE KOMENDY

### 1️⃣ `/kontynuuj_ppm` - GŁÓWNA KOMENDA STARTU

**Plik:** `kontynuuj_ppm.md`
**Priorytet:** NAJWYŻSZY - to komenda do codziennego użytku!

**Co robi:**
```
KROK 1: AKTUALNY STATUS
- Czyta najnowszy raport Podsumowanie_dnia_*.md
- Czyta CLAUDE.md dla zasad
- Sprawdza aktualny ETAP w Plan_Projektu/

KROK 2: STRUKTURA PROJEKTU
- Czyta Struktura_Bazy_Danych.md
- Czyta Struktura_Plikow_Projektu.md
- Czyta dane_hostingu.md

KROK 3: ZASADY PRACY
⚠️ KONIECZNE: Context7 przed kodem
⚠️ ZAKAZ: Przeskakiwanie zadań
⚠️ WYMAGANE: Aktualizacja dokumentacji
⚠️ WYMAGANE: Testowanie na produkcji

KROK 4: ROZPOCZNIJ PRACĘ
- Identyfikuje następne zadanie
- Tworzy TODO list
- Używa Context7
- Wykonuje krok po kroku
```

**Użycie:**
```
/kontynuuj_ppm
```

**Allowed tools:**
- Read, Glob, Bash, Task, mcp__context7__get-library-docs

---

### 2️⃣ `/status_ppm` - STATUS PROJEKTU

**Plik:** `status_ppm.md`

**Co pokazuje:**
- Najnowszy raport (skrót)
- Aktualny ETAP i progress (X/Y zadań)
- Ostatnie 10 commitów git
- Pending issues/blokery
- Gotowość do pracy (TAK/NIE)

**Użycie:**
```
/status_ppm
```

**Output format:**
```
═══════════════════════════════════════════════════════
  STATUS PROJEKTU PPM-CC-LARAVEL
═══════════════════════════════════════════════════════

Aktualny ETAP: ETAP_08 ERP Integration
Progress: 3/7 zadań (43%)
Ostatnia aktywność: 2025-09-24 16:27
Blokery: 1
Gotowe do pracy: TAK

═══════════════════════════════════════════════════════
```

---

### 3️⃣ `/raport_ppm` - NAJNOWSZY RAPORT

**Plik:** `raport_ppm.md`

**Co robi:**
- Znajduje najnowszy Podsumowanie_dnia_*.md
- Wyświetla pełną zawartość raportu

**Użycie:**
```
/raport_ppm
```

---

### 4️⃣ `/plan_ppm` - PLAN PROJEKTU

**Plik:** `plan_ppm.md`

**Co pokazuje:**
- Lista wszystkich ETAPów z statusami
- Szczegóły aktualnego ETAPu
- Status każdego zadania (❌/🛠️/✅/⚠️)
- Utworzone pliki (└──📁)
- Następne zadanie do wykonania
- Blokery i zależności

**Użycie:**
```
/plan_ppm
```

---

### 5️⃣ `/deploy_ppm [ścieżka]` - DEPLOYMENT

**Plik:** `deploy_ppm.md`

**Parametry:**
- `$1` (opcjonalny) - ścieżka do pliku/katalogu do deploy

**Co robi:**
1. Czyta dane hostingu z dane_hostingu.md
2. Uploaduje pliki przez SSH (pscp)
3. Wykonuje post-deployment:
   - `php artisan cache:clear`
   - `php artisan view:clear`
   - `php artisan config:clear`
4. Weryfikuje na https://ppm.mpptrade.pl
5. Tworzy raport deployment w _REPORTS/

**Użycie:**
```
/deploy_ppm
/deploy_ppm app/Http/Controllers/ProductController.php
/deploy_ppm resources/views/products/
```

**⚠️ OSTRZEŻENIE:** Deploy na PRODUKCJĘ - najpierw testuj lokalnie!

---

### 6️⃣ `/agent_ppm [agent] [zadanie]` - DELEGACJA

**Plik:** `agent_ppm.md`

**Parametry:**
- `$1` (opcjonalny) - nazwa agenta
- `$2+` (opcjonalny) - opis zadania

**Dostępni agenci:**
```
laravel-expert           Backend Laravel
livewire-specialist      Komponenty Livewire
frontend-specialist      CSS/JS/Alpine
prestashop-api-expert    PrestaShop API
erp-integration-expert   ERP integrations
import-export-specialist Excel/CSV
deployment-specialist    Deploy/SSH
debugger                 Debug (Opus)
architect                Architecture
coding-style-agent       Code review/QA
database-specialist      Database optimization
testing-specialist       Tests
```

**Co robi:**
1. Czyta AGENT_USAGE_GUIDE.md
2. Waliduje wybór agenta
3. Deleguje zadanie z wymaganiami:
   - MUST use Context7
   - MUST follow CLAUDE.md
   - MUST create report in _AGENT_REPORTS/
   - NO HARDCODING, NO MOCK DATA
4. Po wykonaniu - sprawdza raport

**Użycie:**
```
/agent_ppm
/agent_ppm laravel-expert "Stwórz model Product z relacjami"
/agent_ppm debugger "Napraw błąd w CategoryController"
```

---

### 7️⃣ `/docs_ppm [dokument]` - DOKUMENTACJA

**Plik:** `docs_ppm.md`

**Parametry:**
- `$1` (opcjonalny) - nazwa dokumentu

**Dostępne dokumenty:**
```
GŁÓWNE:
- CLAUDE.md
- _DOCS/Struktura_Bazy_Danych.md
- _DOCS/Struktura_Plikow_Projektu.md
- _DOCS/dane_hostingu.md
- _DOCS/AGENT_USAGE_GUIDE.md

SYSTEMOWE:
- _DOCS/HOOKS_SYSTEM_V3_2025-09-30.md
- _DOCS/AUTO_STARTUP_PROMPT_SYSTEM.md
- _DOCS/SLASH_COMMANDS_SYSTEM.md

PLAN:
- Plan_Projektu/ETAP_*.md

RAPORTY:
- _REPORTS/Podsumowanie_dnia_*.md
- _AGENT_REPORTS/*.md
```

**Użycie:**
```
/docs_ppm
/docs_ppm CLAUDE.md
/docs_ppm Struktura_Bazy_Danych.md
```

**Przypomnienie:** Po zmianach w projekcie aktualizuj odpowiednie dokumenty!

---

### 8️⃣ `/analizuj_strone [URL]` - DIAGNOSTYKA LAYOUTU

**Plik:** `analizuj_strone.md`

**Parametry:**
- `$1` (opcjonalny) - URL strony do analizy (domyślnie: https://ppm.mpptrade.pl/admin/products/4/edit)

**Co robi:**
Kompleksowa diagnostyka problemów z layoutem strony:

**FAZA 1: Visual Inspection**
- Screenshot strony (viewport + full page)
- Zapisanie do `_TOOLS/screenshots/page_TIMESTAMP.png`

**FAZA 2: DOM Structure Analysis**
- Sprawdzenie hierarchii parent-child dla kluczowych elementów
- Weryfikacja czy elementy są we właściwych kontenerach
- Full parent path tracing

**FAZA 3: CSS Computed Styles**
- Analiza flexbox/grid properties
- Position, top, z-index dla sticky/fixed elements
- Rzeczywiste pozycje x,y i rozmiary elementów

**FAZA 4: Blade Template Balance Check**
- Zliczanie opening/closing div tags
- Identyfikacja niezbalansowanych sekcji
- Trace balance line-by-line dla problematycznych obszarów

**FAZA 5: Root Cause Identification**
- Konkretna linia kodu z problemem
- Analiza przyczyny (extra closing div, wrong parent, etc)
- Rekomendacje naprawy

**FAZA 6: Fix & Verification**
- Implementacja poprawki
- Upload na serwer i cache clear
- Ponowna weryfikacja DOM i CSS

**FAZA 7: Report Generation**
- Utworzenie raportu w `_AGENT_REPORTS/LAYOUT_FIX_REPORT_TIMESTAMP.md`

**Narzędzia tworzone automatycznie:**
```
_TOOLS/
├── check_dom_structure.cjs          # Playwright DOM analysis
├── debug_flexbox_styles.cjs         # CSS computed styles
├── screenshot_page.cjs              # Full page screenshots
├── trace_container_balance.ps1      # Div balance tracking
├── detailed_balance_LINES.ps1       # Line-by-line analysis
└── quick_upload_blade.ps1           # Deploy fix to server
```

**Użycie:**
```
/analizuj_strone
/analizuj_strone https://ppm.mpptrade.pl/admin/products/create
```

**Typical Problems Detected:**
- Right column renders at bottom instead of right side
- Dropdown menus hidden under other components
- Sticky positioning not working
- Flexbox layout not applying correctly
- Unbalanced div tags causing DOM structure issues

**Output Example:**
```
=== ANALIZA STRONY ===
✅ FAZA 1: Screenshot captured
❌ FAZA 2: Right column parent IS NOT main container
✅ FAZA 3: CSS properties correct
❌ FAZA 4: Extra closing div at line 992

ROOT CAUSE: Line 992 closes left-column instead of enterprise-card
FIX: Remove erroneous closing div

Apply fix? (y/n)
```

---

## WORKFLOW UŻYCIA

### 🌅 Rozpoczęcie dnia pracy:
```
/kontynuuj_ppm
```
→ Claude automatycznie czyta wszystko i zaczyna pracę

### 📊 Sprawdzenie postępów:
```
/status_ppm
```
→ Szybki overview gdzie jesteśmy

### 🔍 Szczegółowe info o planie:
```
/plan_ppm
```
→ Dokładny status zadań

### 🤖 Delegacja złożonego zadania:
```
/agent_ppm laravel-expert "Stwórz kompleksowy model Product z relacjami"
```
→ Agent przejmuje zadanie

### 🚢 Deployment zmian:
```
/deploy_ppm resources/views/products/
```
→ Automatyczny upload i weryfikacja

### 📚 Sprawdzenie dokumentacji:
```
/docs_ppm dane_hostingu.md
```
→ Szybki dostęp do dokumentów

### 🔍 Diagnostyka problemów z layoutem:
```
/analizuj_strone https://ppm.mpptrade.pl/admin/products/4/edit
```
→ Kompleksowa analiza DOM, CSS i Blade templates

---

## TECHNIKALIA

### Frontmatter options:
```markdown
---
description: Opis widoczny w /help
allowed-tools: Read, Write, Edit, Bash, Task, mcp__*
argument-hint: [nazwa-arg] [opcjonalny-arg]
---
```

### Użycie parametrów:
- `$1`, `$2`, `$3` - poszczególne argumenty
- `$ARGUMENTS` - wszystkie argumenty jako string
- `@file.md` - include zawartości pliku
- `!command` - wykonaj shell command (ostrożnie!)

### Referencje do plików:
```markdown
@CLAUDE.md
@_DOCS/Struktura_Bazy_Danych.md
```
→ Claude automatycznie czyta te pliki

### Glob patterns:
```markdown
Użyj Glob tool: pattern `Podsumowanie_dnia_*.md` w `_REPORTS/`
```
→ Znajduje pliki pasujące do wzorca

---

## INTEGRACJA Z INNYMI SYSTEMAMI

### 🔗 Hooks System (v3.0):
Slash commands **ZASTĘPUJĄ** potrzebę SessionStart hook:
- SessionStart powodował freeze ❌
- `/kontynuuj_ppm` działa idealnie ✅

### 🔗 Auto-Startup System:
Helper script (`generate-startup-prompt.ps1`) wciąż dostępny jako alternatywa:
- Dla użytkowników preferujących ręczną kontrolę
- Jako backup jeśli slash commands nie działają

### 🔗 Agent System:
`/agent_ppm` integruje się z:
- `_DOCS/AGENT_USAGE_GUIDE.md`
- `_AGENT_REPORTS/` directory
- Hooks system (PreToolUse dla Task)

### 🔗 Deployment System:
`/deploy_ppm` integruje się z:
- `_DOCS/dane_hostingu.md`
- SSH keys (HostidoSSHNoPass.ppk)
- Post-deployment hooks

---

## ROZSZERZANIE SYSTEMU

### Jak dodać nową komendę:

1. **Utwórz plik:**
   ```bash
   touch .claude/commands/nowa_komenda.md
   ```

2. **Dodaj frontmatter:**
   ```markdown
   ---
   description: Opis komendy
   allowed-tools: Read, Write, Bash
   argument-hint: [parametr1] [parametr2]
   ---
   ```

3. **Napisz prompt:**
   ```markdown
   ## INSTRUKCJE DLA CLAUDE

   Krok 1: Zrób to
   Krok 2: Zrób tamto

   Użyj parametru: $1
   Przeczytaj plik: @file.md
   ```

4. **Restart Claude Code**

5. **Użyj komendy:**
   ```
   /nowa_komenda arg1 arg2
   ```

### Przykład - komenda do testów:
```markdown
---
description: Uruchom testy projektu
allowed-tools: Bash
---

Uruchom testy Laravel:

1. Wszystkie testy:
   ```bash
   php artisan test
   ```

2. Konkretny test:
   ```bash
   php artisan test --filter=$1
   ```

3. Wyświetl wyniki
```

Użycie: `/test_ppm ProductTest`

---

## BEST PRACTICES

### ✅ DO:
1. **Używaj `/kontynuuj_ppm` codziennie** - to główna komenda workflow
2. **Sprawdzaj status często** - `/status_ppm` przed każdą większą pracą
3. **Deleguj do agentów** - `/agent_ppm` dla złożonych zadań
4. **Deploy często** - `/deploy_ppm` małe porcje zmian
5. **Aktualizuj dokumentację** - `/docs_ppm` po zmianach

### ❌ DON'T:
1. **Nie omijaj `/kontynuuj_ppm`** - to kluczowy krok inicjalizacji
2. **Nie deployuj bez testów** - zawsze weryfikuj lokalnie
3. **Nie ignoruj błędów** - używaj `/agent_ppm debugger`
4. **Nie przeskakuj zadań** - przestrzegaj planu
5. **Nie zapomnij o raportach** - agenci MUSZĄ tworzyć raporty

### 💡 TIPS:
- Możesz łączyć komendy w workflow:
  ```
  /status_ppm
  /kontynuuj_ppm
  /deploy_ppm
  ```
- Tab completion działa dla komend!
- Używaj `/help` aby zobaczyć wszystkie komendy
- Komendy są case-sensitive: `/kontynuuj_ppm` ✅ `/KONTYNUUJ_PPM` ❌

---

## TROUBLESHOOTING

### Problem: Komenda nie działa
**Rozwiązanie:** Restart Claude Code CLI

### Problem: Komenda nie widzi plików projektu
**Rozwiązanie:** Sprawdź working directory:
```bash
pwd
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
```

### Problem: Brak dostępu do MCP Context7
**Rozwiązanie:** Sprawdź czy MCP server działa i czy API key jest poprawny

### Problem: Deploy fails
**Rozwiązanie:**
1. Sprawdź połączenie SSH
2. Sprawdź uprawnienia do plików
3. Zobacz logi w `_REPORTS/`

---

## PORÓWNANIE Z POPRZEDNIMI ROZWIĄZANIAMI

| Feature | SessionStart Hook | Helper Script | Slash Commands |
|---------|-------------------|---------------|----------------|
| Automatyzacja | ⚠️ Częściowa | ❌ Ręczna | ✅ Pełna |
| Freeze risk | ❌ Tak | ✅ Nie | ✅ Nie |
| Łatwość użycia | ⚠️ Transparent | ❌ Multi-step | ✅ One command |
| Parametryzacja | ❌ Nie | ❌ Nie | ✅ Tak |
| Niezawodność | ❌ Niska | ✅ Wysoka | ✅ Wysoka |
| Integracja | ⚠️ Średnia | ⚠️ Średnia | ✅ Natywna |

**WERDYKT:** Slash Commands = najlepsze rozwiązanie! 🏆

---

## METRYKI SYSTEMU

**Liczba komend:** 8
**Główne komendy:** 3 (`/kontynuuj_ppm`, `/status_ppm`, `/plan_ppm`)
**Pomocnicze komendy:** 4 (`/raport_ppm`, `/deploy_ppm`, `/agent_ppm`, `/docs_ppm`)
**Diagnostyczne komendy:** 1 (`/analizuj_strone`)

**Oszczędność czasu:**
- Bez komend: ~5 minut setup każdego dnia
- Z komendami: ~10 sekund (`/kontynuuj_ppm`)
- **Oszczędność: ~95%**

**Developer experience:**
- Przed: 😐 Manual, tedious
- Po: 😊 One command, automated

---

## ROADMAP

### v1.0 (Current) ✅
- 8 podstawowych komend
- Integracja z Context7
- Deployment automation
- Agent delegation
- Layout diagnostics (Playwright + DOM analysis)

### v1.1 (Planned)
- `/test_ppm` - uruchamianie testów
- `/backup_ppm` - backup projektu
- `/migrate_ppm` - migrations management
- `/seed_ppm` - database seeding

### v2.0 (Future)
- Komendy z AI suggestions
- Auto-completion kontekstowe
- Integracja z CI/CD
- Multi-project support

---

## DOKUMENTACJA REFERENCYJNA

**Oficjalna dokumentacja Claude Code:**
https://docs.claude.com/en/docs/claude-code/slash-commands

**Powiązane dokumenty w projekcie:**
- `.claude/commands/README.md` - Quick reference
- `_DOCS/AUTO_STARTUP_PROMPT_SYSTEM.md` - Helper script (alternatywa)
- `_DOCS/HOOKS_SYSTEM_V3_2025-09-30.md` - Hooks system
- `.claude/HOW_TO_START_SESSION.md` - Quick start guide

---

## STATUS
✅ **SYSTEM GOTOWY DO UŻYCIA**

**Aby rozpocząć:**
1. Restart Claude Code CLI
2. Wpisz: `/kontynuuj_ppm`
3. Gotowe! 🚀

**Data wdrożenia:** 2025-09-30
**Autor:** System zbudowany przez Claude Code
**Wersja:** 1.0 - Production Ready