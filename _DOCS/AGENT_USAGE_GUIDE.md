# 🤖 PRZEWODNIK UŻYCIA AGENTÓW - PPM-CC-Laravel

**Data utworzenia:** 2025-09-27
**Projekt:** PPM-CC-Laravel (Prestashop Product Manager)
**Wersja:** 1.0

---

## 📖 WPROWADZENIE

Ten przewodnik określa **kiedy i jak** używać każdego agenta w projekcie PPM-CC-Laravel. Jest to **obowiązkowe** narzędzie dla głównego koordynatora (Claude Code - główna instancja) do efektywnego zarządzania zespołem specjalistycznych agentów.

### ⚡ ZASADY PODSTAWOWE

1. **ZAWSZE** czytaj raporty agentów przed delegowaniem nowych zadań
2. **TYLKO JEDEN** agent może być w stanie `in_progress` w danym momencie
3. **WYMAGANE** raportowanie w `_AGENT_REPORTS/` po każdym zadaniu
4. **OBOWIĄZKOWE** aktualizowanie TodoWrite podczas pracy agentów

---

## 🏗️ AGENCI BAZOWI (Core Team)

### 🎯 **architect** - Expert Planning Manager & Project Plan Keeper
**Model:** `sonnet`
**Specjalizacja:** Planowanie, architektura, zarządzanie Plan_Projektu/

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ZAWSZE** na początku nowej sesji do sprawdzenia statusu planu
- ✅ **ZAWSZE** przed rozpoczęciem nowego ETAPU
- ✅ Po ukończeniu milestone'ów do aktualizacji planu
- ✅ Planowanie implementacji nowych funkcjonalności
- ✅ Analiza dependencies między ETAP-ami
- ✅ Rozwiązywanie blokad w planie projektu

#### 📋 WZORZEC UŻYCIA:
```
SYTUACJA: User pyta "pomóż mi zaplanować implementację systemu płatności"

AKCJA KOORDYNATORA:
1. Użyj Task → architect
2. Prompt: "Przeanalizuj wymagania systemu płatności dla PPM-CC-Laravel.
   Sprawdź Plan_Projektu/ i zaproponuj szczegółowy plan implementacji
   z uwzględnieniem istniejących ETAP-ów i dependencies."
3. Czekaj na raport agenta
4. Przedstaw plan użytkownikowi
```

#### ⚠️ NIE UŻYWAJ GDY:
- Zadanie nie wymaga planowania (pojedyncze pliki, drobne poprawki)
- Już masz szczegółowy plan i potrzebujesz implementacji

---

### ❓ **ask** - Knowledge Expert
**Model:** `sonnet`
**Specjalizacja:** Odpowiedzi na pytania, analiza kodu, wyjaśnianie konceptów

#### 🔑 KIEDY UŻYWAĆ:
- ✅ User pyta o techniczne koncepty lub istniejący kod
- ✅ Potrzebujesz wyjaśnień bez implementacji
- ✅ Analiza istniejących rozwiązań przed modyfikacją
- ✅ Edukacyjne pytania o Laravel, Livewire, PrestaShop
- ✅ Pytania o architekturę systemu bez wprowadzania zmian

#### 📋 WZORZEC UŻYCIA:
```
SYTUACJA: User pyta "jak działa system synchronizacji produktów?"

AKCJA KOORDYNATORA:
1. Użyj Task → ask
2. Prompt: "Wyjaśnij jak działa system synchronizacji produktów w PPM-CC-Laravel.
   Przeanalizuj klasy w app/Services/PrestaShop/ i opisz przepływ danych."
3. Przekaż odpowiedź użytkownikowi
```

#### ⚠️ NIE UŻYWAJ GDY:
- User chce implementować nową funkcjonalność
- Potrzebujesz modyfikacji kodu (użyj specjalisty)

---

### 🐛 **debugger** - Expert Code Debugger
**Model:** `opus` *(wyższa moc obliczeniowa dla złożonego debugowania)*
**Specjalizacja:** Systematyczna diagnostyka problemów, analiza błędów

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ZAWSZE** przy błędach aplikacji i wyjątkach
- ✅ Problemy z Livewire komponentami (wire:snapshot, state issues)
- ✅ Błędy integracji API (PrestaShop, ERP)
- ✅ Problemy z bazą danych i relacjami
- ✅ Konflikty uprawnień i autoryzacji
- ✅ Błędy queue job i background processing

#### 📋 WZORZEC UŻYCIA:
```
SYTUACJA: User zgłasza "Livewire component pokazuje surowy kod zamiast UI"

AKCJA KOORDYNATORA:
1. Użyj Task → debugger
2. Prompt: "Diagnozuj problem wire:snapshot w komponencie [nazwa].
   Przeanalizuj 5-7 możliwych przyczyn, zawęź do 1-2 najbardziej prawdopodobnych
   i zaproponuj strategię debugowania."
3. **WAŻNE:** Poproś użytkownika o potwierdzenie diagnozy PRZED implementacją poprawek
4. Dopiero po potwierdzeniu deleguj implementację do odpowiedniego specjalisty
```

#### ⚠️ NIE UŻYWAJ GDY:
- Problem jest jasny i znasz rozwiązanie
- Potrzebujesz tylko implementacji bez diagnostyki

---

### 🎨 **coding-style-agent** - Code Quality Guardian
**Model:** `sonnet`
**Specjalizacja:** Standardy kodowania, Context7 integration, best practices

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ZAWSZE** przed akceptacją nowego kodu
- ✅ **OBOWIĄZKOWO** przy refaktoringu
- ✅ Weryfikacja compliance z Laravel/Livewire standards
- ✅ Integracja z Context7 dla aktualnych dokumentacji
- ✅ Code review enterprise patterns

#### 📋 WZORZEC UŻYCIA:
```
SYTUACJA: Agent specjalista ukończył implementację nowej funkcji

AKCJA KOORDYNATORA:
1. Użyj Task → coding-style-agent
2. Prompt: "Przejrzyj kod z [ścieżka do plików]. Sprawdź zgodność z PSR-12,
   Laravel conventions i enterprise patterns. Użyj Context7 dla weryfikacji
   najnowszych standardów."
3. Jeśli są problemy - deleguj poprawki do odpowiedniego specjalisty
4. Dopiero po aprobacie oznacz zadanie jako completed
```

#### ⚠️ ZAWSZE WYMAGANE:
- Po każdej implementacji specjalisty (przed completion)
- **KRYTYCZNE:** Musi użyć Context7 MCP przed rekomendacjami

---

### 📚 **documentation-reader** - Documentation Compliance Expert
**Model:** `sonnet`
**Specjalizacja:** Zgodność z dokumentacją projektu, weryfikacja requirements

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **PRZED** rozpoczęciem implementacji nowych funkcji
- ✅ Weryfikacja zgodności z CLAUDE.md i Plan_Projektu/
- ✅ Sprawdzanie cross-ETAP dependencies (🔗 POWIAZANIE)
- ✅ Analiza zgodności z enterprise requirements
- ✅ Rozwiązywanie konfliktów między dokumentacjami

#### 📋 WZORZEC UŻYCIA:
```
SYTUACJA: Rozpoczynasz implementację nowej funkcji

AKCJA KOORDYNATORA:
1. Użyj Task → documentation-reader
2. Prompt: "Zweryfikuj wymagania dla [funkcja] w kontekście CLAUDE.md
   i Plan_Projektu/ETAP_XX. Sprawdź dependencies z innymi ETAP-ami."
3. Na podstawie raportu przygotuj szczegółowe wymagania dla specjalisty
```

#### ⚠️ NIE UŻYWAJ GDY:
- Implementujesz drobne poprawki bez wpływu na architekturę
- Masz pewność co do requirements

---

## 🔧 AGENCI SPECJALIŚCI (Domain Experts)

### ⚙️ **laravel-expert** - Laravel Framework Expert
**Model:** `sonnet`
**Specjalizacja:** Laravel 12.x, Eloquent ORM, Service Layer, Queue system

#### 🔑 KIEDY UŻYWAĆ:
- ✅ Implementacja nowych modeli Eloquent i relacji
- ✅ Tworzenie serwisów i repository patterns
- ✅ System kolejek i background jobs
- ✅ Form requests i validation rules
- ✅ API resources i transformery
- ✅ Middleware i service providers
- ✅ Database migrations i seedy

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Stworz model ProductCategory z hierarchią 5-poziomową"
✅ "Zaimplementuj ProductSyncService dla integracji ERP"
✅ "Zaprojektuj queue job dla masowej synchronizacji produktów"
✅ "Stwórz FormRequest dla walidacji danych produktu"
```

#### ⚠️ NIE UŻYWAJ GDY:
- Problem dotyczy Livewire komponentów (użyj livewire-specialist)
- Potrzebujesz frontend UI (użyj frontend-specialist)

---

### ⚡ **livewire-specialist** - Livewire 3.x Expert
**Model:** `sonnet`
**Specjalizacja:** Livewire components, Alpine.js, reactive interfaces

#### 🔑 KIEDY UŻYWAĆ:
- ✅ Tworzenie i modyfikacja komponentów Livewire
- ✅ Problemy z wire:model i state synchronization
- ✅ Event handling (dispatch vs emit migration)
- ✅ Alpine.js integration i reactive UI
- ✅ Real-time updates i WebSocket integration
- ✅ Complex form interfaces z validation

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Stworz ProductForm component z multi-store support"
✅ "Napraw wire:snapshot issue w AdminDashboard"
✅ "Zaimplementuj real-time sync status monitoring"
✅ "Stwórz wizard component dla dodawania sklepów"
```

#### ⚠️ TYPOWE PROBLEMY DO ROZWIĄZANIA:
- wire:snapshot renderuje surowy kod
- wire:key missing w pętlach
- emit() vs dispatch() migration
- Component state corruption

---

### 🛒 **prestashop-api-expert** - PrestaShop Integration Expert
**Model:** `sonnet`
**Specjalizacja:** PrestaShop API v8/v9, multi-store sync, product mapping

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ETAP_07:** Integracja PrestaShop API
- ✅ Problemy z synchronizacją produktów do sklepów
- ✅ Category mapping i hierarchy sync
- ✅ Webhook system implementation
- ✅ Rate limiting i API optimization
- ✅ Conflict resolution w sync

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Zaimplementuj ProductSyncStrategy dla PrestaShop v9"
✅ "Napraw webhook processing dla product.updated events"
✅ "Stworz CategoryMapper dla multi-store scenarios"
✅ "Optymalizuj bulk sync performance"
```

#### ⚠️ DEPENDENCIES:
- Wymaga ukończenia ETAP_02 (modele)
- Integruje się z ETAP_04 (panel admin)

---

### 🔌 **erp-integration-expert** - ERP Systems Expert
**Model:** `sonnet`
**Specjalizacja:** BaseLinker, Subiekt GT, Microsoft Dynamics integration

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ETAP_08:** Integracje z systemami ERP *(currently IN PROGRESS)*
- ✅ BaseLinker API integration (priority #1)
- ✅ Subiekt GT .NET Bridge development
- ✅ Microsoft Dynamics OData integration
- ✅ ERPServiceManager i unified interfaces
- ✅ Data transformation between ERP formats

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Zaimplementuj BaseLinkerSyncService z rate limiting"
✅ "Stwórz SubiektGT .NET Bridge Windows Service"
✅ "Integruj Microsoft Dynamics OData authentication"
✅ "Stworz unified ERPServiceManager interface"
```

#### ⚠️ COMPLEX DEPENDENCIES:
- BaseLinker → Subiekt GT → Dynamics (kolejność implementacji)
- Wymaga panelu admin (ETAP_04) dla konfiguracji

---

### 📊 **import-export-specialist** - Data Processing Expert
**Model:** `sonnet`
**Specjalizacja:** XLSX processing, column mapping, data transformation

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ETAP_06:** System Import/Export XLSX
- ✅ Dynamic column mapping z templates
- ✅ Large file processing i memory optimization
- ✅ Container-based import workflows
- ✅ Data validation i error handling
- ✅ Background job processing dla import/export

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Stworz PojazdyTemplate dla vehicle parts import"
✅ "Zaimplementuj XLSXProcessor z memory optimization"
✅ "Stwórz dynamic column mapping interface"
✅ "Napraw batch processing dla large datasets"
```

#### ⚠️ KLUCZOWE KOLUMNY:
- ORDER, Parts Name, U8 Code, MRF CODE, Qty, Ctn no., Size, Weight, Model, VIN, Engine No.

---

### 🚀 **deployment-specialist** - Infrastructure & Deployment Expert
**Model:** `sonnet`
**Specjalizacja:** SSH automation, PowerShell, Hostido environment, CI/CD

#### 🔑 KIEDY UŻYWAĆ:
- ✅ Deployment scripts i automation
- ✅ PowerShell 7 workflows na Windows
- ✅ SSH automation z PuTTY/plink
- ✅ Hostido environment optimization
- ✅ CI/CD pipeline development
- ✅ Health checks i monitoring

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Zaktualizuj hostido_deploy.ps1 o nowe migracje"
✅ "Stworz health check script dla production"
✅ "Zaimplementuj rollback mechanism"
✅ "Optymalizuj cache clearing workflow"
```

#### ⚠️ ENVIRONMENT SPECIFIC:
- Hostido: host379076@host379076.hostido.net.pl:64321
- SSH Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk
- Path: domains/ppm.mpptrade.pl/public_html/

---

### 🎨 **frontend-specialist** - UI/UX Expert
**Model:** `sonnet`
**Specjalizacja:** Blade templates, Alpine.js, responsive design, accessibility

#### 🔑 KIEDY UŻYWAĆ:
- ✅ Blade template development i optimization
- ✅ Alpine.js component architecture
- ✅ Responsive design implementation
- ✅ Enterprise UI patterns
- ✅ Accessibility compliance (WCAG)
- ✅ Integration z Livewire components

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Stworz responsive admin layout z sidebar"
✅ "Zaimplementuj data-table component z filtering"
✅ "Napraw mobile navigation w admin panel"
✅ "Dodaj WCAG accessibility do form components"
```

#### ⚠️ INTEGRATION:
- Współpracuje z livewire-specialist
- Używa Alpine.js dla interactivity

---

## 🔄 WORKFLOW PATTERNS (Wzorce Przepływu Pracy)

### 📋 PATTERN 1: Nowa Funkcjonalność
```
1. documentation-reader → sprawdź requirements
2. architect → zaplanuj implementację
3. [Specjalista dziedziny] → implementuj
4. coding-style-agent → code review
5. debugger → jeśli problemy
6. deployment-specialist → deploy (jeśli gotowe)
```

### 🐛 PATTERN 2: Debugging Problem
```
1. debugger → diagnoza problemu
2. [Specjalista dziedziny] → implementacja fix
3. coding-style-agent → weryfikacja
4. Test deployment
```

### 🔄 PATTERN 3: Refactoring
```
1. ask → analiza istniejącego kodu
2. architect → plan refactoringu
3. [Specjalista dziedziny] → implementacja
4. coding-style-agent → compliance check
```

### 📦 PATTERN 4: ETAP Implementation
```
1. architect → aktualizacja planu ETAP
2. documentation-reader → wymagania compliance
3. [Multiple specialists] → implementacja sekcji
4. coding-style-agent → final review
5. deployment-specialist → production deploy
6. architect → update plan status ✅
```

---

## ⚠️ KRYTYCZNE ZASADY

### 🚫 ZAKAZY
- **NIE UŻYWAJ** wielu agentów jednocześnie w tym samym zadaniu
- **NIE OZNACZAJ** zadań jako completed bez code review
- **NIE IMPLEMENTUJ** bez sprawdzenia dependencies w planie
- **NIE DEPLOY** bez health checks

### ✅ OBOWIĄZKI
- **ZAWSZE** czytaj raporty agentów w `_AGENT_REPORTS/`
- **ZAWSZE** aktualizuj TodoWrite z postępem agentów
- **ZAWSZE** użyj Context7 MCP w coding-style-agent
- **ZAWSZE** weryfikuj enterprise compliance

### 📊 MONITORING
- Każdy agent MUSI stworzyć raport `.md` w `_AGENT_REPORTS/`
- Format: `NAZWA_AGENTA_TASK_DESCRIPTION_REPORT.md`
- Raport musi zawierać: wykonane prace, problemy, następne kroki, pliki

---

## 🎯 QUICK REFERENCE

### 🔥 EMERGENCY SITUATIONS
| Problem | Agent | Priorytet |
|---------|-------|-----------|
| Production down | debugger → deployment-specialist | 🔴 CRITICAL |
| Data corruption | debugger → database-expert | 🔴 CRITICAL |
| Security breach | debugger → security policies | 🔴 CRITICAL |
| API integration failure | debugger → [erp/prestashop]-expert | 🟡 HIGH |
| UI broken | debugger → livewire-specialist | 🟡 HIGH |

### 📋 DAILY WORKFLOWS
| Task Type | Primary Agent | Secondary Agents |
|-----------|---------------|------------------|
| New feature planning | architect | documentation-reader |
| Bug fixes | debugger | [domain specialist] |
| Code review | coding-style-agent | - |
| Deployment | deployment-specialist | - |
| Architecture questions | ask | architect |

---

**🏁 PAMIĘTAJ:** Ten przewodnik to żywy dokument. Aktualizuj go gdy pojawiają się nowe wzorce użycia agentów w projekcie PPM-CC-Laravel.

---

**Autor:** Claude Code AI
**Data:** 2025-09-27
**Projekt:** PPM-CC-Laravel Enterprise PIM System
**Status:** ✅ ACTIVE GUIDE