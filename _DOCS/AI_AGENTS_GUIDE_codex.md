# 🤖 PRZEWODNIK AGENTÓW AI - PPM-CC-Laravel

**Wersja:** 1.0  
**Data:** 2024-09-05  
**Projekt:** PPM-CC-Laravel (Prestashop Product Manager)  
**Przeznaczenie:** Kompletny system Sub-Agentów Claude Code dla rozwoju aplikacji enterprise

---

## 📋 SPIS TREŚCI

1. [Wprowadzenie](#wprowadzenie)
2. [Architektura Zespołu Agentów](#architektura-zespołu-agentów)
3. [Agenci Bazowi](#agenci-bazowi)  
4. [Agenci Specjalistyczni](#agenci-specjalistyczni)
5. [Przepływ Pracy z Agentami](#przepływ-pracy-z-agentami)
6. [System Raportowania](#system-raportowania)
7. [Przykłady Użycia](#przykłady-użycia)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 WPROWADZENIE

System Sub-Agentów Claude Code dla projektu PPM-CC-Laravel składa się z **12 specjalistycznych agentów** zaprojektowanych do obsługi wszystkich aspektów rozwoju aplikacji enterprise. Każdy agent ma swoją specjalizację i jest zoptymalizowany do konkretnych zadań w ramach projektu.

## 🚀 INTEGRACJA MCP CODEX - REWOLUCJA W DEVELOPMENT WORKFLOW

### KRYTYCZNA ZMIANA: MCP Codex jako Główne Narzędzie

Od tej pory **MCP Codex** (`mcp__codex__codex`) jest głównym narzędziem do implementacji kodu w projekcie PPM-CC-Laravel. Wszystkie agenci muszą przestrzegać nowego workflow z MCP Codex.

### NOWE ZASADY PRACY AGENTÓW:

🔥 **DELEGACJA KODOWA** - Agenci NIE piszą kodu bezpośrednio, tylko delegują do MCP Codex  
🔥 **WERYFIKACJA OBOWIĄZKOWA** - Każdy kod (włącznie z frontend design) MUSI być zweryfikowany przez MCP Codex  
🔥 **KONSULTACJA PLANU** - Wszystkie aktualizacje planu MUSZĄ być skonsultowane z MCP Codex  
🔥 **ZGODNOŚĆ Z _init.md** - MCP Codex weryfikuje zgodność z requirements i usuwa zbędne punkty

### MCP Codex Workflow dla Agentów:

```mermaid
flowchart TD
    A[Agent otrzymuje zadanie] --> B[Analiza requirements]
    B --> C{Typ zadania}
    
    C -->|Implementacja kodu| D[Delegacja do MCP Codex]
    C -->|Design frontend| E[Agent pisze design]
    C -->|Aktualizacja planu| F[Konsultacja z MCP Codex]
    
    D --> G[MCP Codex implementuje]
    E --> H[MCP Codex weryfikuje design]
    F --> I[MCP Codex weryfikuje plan]
    
    G --> J[Agent sprawdza wynik]
    H --> J
    I --> K[Agent aktualizuje plan]
    
    J --> L[Deploy + Testy]
    K --> L
    L --> M[Raport agenta]
```

### Kluczowe Zasady (ZAKTUALIZOWANE):

✅ **Jedna funkcja - jeden agent** - każdy agent ma wyraźnie zdefiniowaną specjalizację  
✅ **MCP Codex Integration** - wszyscy agenci MUSZĄ korzystać z MCP Codex do implementacji  
✅ **Komunikacja przez raporty** - agenci komunikują się poprzez pliki `.md` w folderze `_AGENT_REPORTS/`  
✅ **Kontekst projektowy** - wszyscy agenci znają specyfikę PPM-CC-Laravel  
✅ **Ultrathink approach** - każdy agent ma guidelines do głębokiej analizy  
✅ **Enterprise quality** - wszystkie agenci przestrzegają najwyższych standardów  

---

## 🏗️ ARCHITEKTURA ZESPOŁU AGENTÓW

```mermaid
graph TB
    A[GŁÓWNY KOORDYNATOR<br/>Claude Code - główna instancja] --> B[AGENCI BAZOWI]
    A --> C[AGENCI SPECJALISTYCZNI]
    
    B --> B1[Architect<br/>Planowanie & Architektura]
    B --> B2[Ask<br/>Odpowiedzi na pytania]  
    B --> B3[Debugger<br/>Diagnostyka błędów]
    B --> B4[Coding-Style-Agent<br/>Jakość kodu]
    B --> B5[Documentation-Reader<br/>Zgodność z dokumentacją]
    
    C --> C1[Laravel-Expert<br/>PHP & Laravel backend]
    C --> C2[Database-Expert<br/>MySQL & optymalizacja]
    C --> C3[Prestashop-API-Expert<br/>Integracja Prestashop]
    C --> C4[ERP-Integration-Expert<br/>Baselinker, Subiekt GT, etc.]
    C --> C5[Frontend-Specialist<br/>Livewire & Alpine.js]
    C --> C6[Import-Export-Specialist<br/>XLSX processing]
    C --> C7[Deployment-Specialist<br/>MyDevil deployment]
    
    A -.-> D[System Raportowania<br/>_AGENT_REPORTS/]
    B -.-> D
    C -.-> D
```

---

## 🔧 PROCEDURY MCP CODEX DLA AGENTÓW

### Standardowe Procedury Delegacji

Każdy agent MUSI używać następujących procedur przy pracy z MCP Codex:

#### 1. IMPLEMENTACJA KODU

**Dla Laravel-Expert, Database-Expert, ERP-Integration-Expert, etc.:**

```javascript
// Procedura delegacji implementacji
const implementCode = async (taskDescription, requirements) => {
    // 1. Analiza zadania przez agenta
    const analysis = await analyzeTask(taskDescription, requirements);
    
    // 2. Delegacja do MCP Codex
    const result = await mcp__codex__codex({
        prompt: `Zaimplementuj ${taskDescription} dla PPM-CC-Laravel zgodnie z requirements:\n${requirements}\n\nAnaliza agenta:\n${analysis}`,
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
        model: "opus", // używaj opus dla complex implementations
        sandbox: "workspace-write"
    });
    
    // 3. Agent weryfikuje wynik
    return verifyImplementation(result);
};
```

#### 2. WERYFIKACJA KODU

**Obowiązkowa dla wszystkich agentów po każdej implementacji:**

```javascript
// Procedura weryfikacji
const verifyCode = async (filePaths, verificationCriteria) => {
    const result = await mcp__codex__codex({
        prompt: `Zweryfikuj kod w plikach: ${filePaths.join(', ')}\n\nKryteria weryfikacji:\n${verificationCriteria}\n\nSprawdź zgodność z Laravel 12.x, PSR-12, security best practices i requirements z _init.md`,
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
        model: "sonnet", // sonnet wystarczy dla weryfikacji
        sandbox: "read-only" // tylko czytanie dla weryfikacji
    });
    
    return result;
};
```

#### 3. KONSULTACJA PLANU

**Obowiązkowa dla Architect i wszystkich agentów aktualizujących plan:**

```javascript
// Procedura konsultacji planu
const consultPlan = async (planFile, proposedChanges) => {
    const result = await mcp__codex__codex({
        prompt: `Przeanalizuj plan w pliku ${planFile} i zaproponowane zmiany:\n${proposedChanges}\n\nWeryfikuj:\n1. Zgodność z _init.md requirements\n2. Czy wszystkie punkty przynoszą korzyść projektowi\n3. Czy nie ma zbędnych punktów\n4. Czy plan jest realistyczny i wykonalny\n\nUsuń zbędne punkty i zaoptymalizuj plan.`,
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
        model: "sonnet",
        sandbox: "workspace-write"
    });
    
    return result;
};
```

### Procedury Specjalistyczne

#### ARCHITECT - Zarządzanie Planem z MCP Codex

```javascript
// Architect musi ZAWSZE konsultować plan z MCP Codex
const architectPlanManagement = async (planningTask) => {
    // 1. Wstępna analiza przez Architect
    const initialPlan = await createInitialPlan(planningTask);
    
    // 2. Konsultacja z MCP Codex
    const consultedPlan = await mcp__codex__codex({
        prompt: `Przeanalizuj i zoptymalizuj plan architektury dla zadania: ${planningTask}\n\nWstępny plan:\n${initialPlan}\n\nWeryfikuj zgodność z _init.md, usuń zbędne punkty, dodaj brakujące elementy krytyczne dla sukcesu projektu PPM-CC-Laravel.`,
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
        model: "opus", // opus dla complex planning
        sandbox: "workspace-write"
    });
    
    // 3. Finalizacja przez Architect
    return finalizePlan(consultedPlan);
};
```

#### FRONTEND-SPECIALIST - Design z Weryfikacją MCP Codex

```javascript
// Frontend-Specialist może pisać design, ale MUSI weryfikować przez MCP Codex
const frontendDesignWorkflow = async (designTask) => {
    // 1. Agent pisze design (Livewire + Blade + Alpine.js)
    const design = await createFrontendDesign(designTask);
    
    // 2. OBOWIĄZKOWA weryfikacja przez MCP Codex
    const verification = await mcp__codex__codex({
        prompt: `Zweryfikuj frontend design dla ${designTask}\n\nKod designu:\n${design}\n\nSprawdź:\n1. Zgodność z Livewire 3.x patterns\n2. Correct Alpine.js usage\n3. Blade templating best practices\n4. Accessibility standards\n5. Responsive design\n6. Security (XSS, CSRF)`,
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
        model: "sonnet",
        sandbox: "read-only"
    });
    
    // 3. Apply corrections if needed
    return applyVerificationFeedback(design, verification);
};
```

### Model Selection Guidelines

**Kiedy używać którego modelu MCP Codex:**

- **opus** - Complex implementations, architecture planning, critical business logic
- **sonnet** - Code verification, plan consultation, simple implementations
- **haiku** - Quick checks, simple tasks, documentation updates

### Sandbox Guidelines

- **workspace-write** - Gdy MCP Codex ma pisać/edytować pliki
- **read-only** - Wyłącznie dla weryfikacji i konsultacji
- **danger-full-access** - NIGDY nie używać (zbyt niebezpieczne)

---

## 👥 AGENCI BAZOWI

### 🏛️ **ARCHITECT** 
```yaml
Agent: architect
Model: sonnet
Specjalizacja: Zarządzanie planem projektu i architektura
```

**Kiedy używać:**
- ✅ Planowanie nowych funkcjonalności przed implementacją
- ✅ Aktualizacja planu projektu po ukończonych milestone'ach  
- ✅ Zarządzanie hierarchią zadań w `Plan_Projektu/`
- ✅ Architektoniczne decyzje dla aplikacji enterprise
- ✅ Breaking down complex features na wykonalne zadania

**Przykład użycia:**
```
/task architect "Zaplanuj implementację systemu synchronizacji produktów 
z Baselinker API. Uwzględnij rate limiting, error handling, progress tracking 
i integrację z istniejącym systemem kolejek."
```

---

### ❓ **ASK**
```yaml
Agent: ask  
Model: sonnet
Specjalizacja: Odpowiedzi na pytania techniczne
```

**Kiedy używać:**
- ✅ Pytania o Laravel 12.x best practices
- ✅ Wyjaśnienia dotyczące Prestashop API compatibility
- ✅ Analiza istniejącego kodu bez wprowadzania zmian
- ✅ Rekomendacje technologiczne i architektoniczne
- ✅ Nauka o nowych technologiach/patterns

**Przykład użycia:**
```
/task ask "Jaka jest najlepsza strategia cache'owania dla aplikacji Laravel 
na shared hostingu MyDevil z dużą liczbą produktów? Porównaj opcje Redis 
vs database cache."
```

---

### 🐛 **DEBUGGER**
```yaml
Agent: debugger
Model: opus  
Specjalizacja: Diagnostyka i rozwiązywanie problemów
```

**Kiedy używać:**
- ✅ Troubleshooting błędów aplikacji
- ✅ Performance issues investigation  
- ✅ API integration problems (Prestashop/ERP)
- ✅ Database query optimization
- ✅ Systematic problem diagnosis

**Przykład użycia:**
```
/task debugger "Aplikacja spowalnia podczas importu dużych plików XLSX. 
Zdiagnozuj problem i zaproponuj rozwiązanie. Log pokazuje memory limit errors."
```

---

### 📝 **CODING-STYLE-AGENT**
```yaml
Agent: coding-style-agent
Model: sonnet
Specjalizacja: Jakość kodu i standardy
```

**Kiedy używać:**
- ✅ Code review nowych funkcjonalności
- ✅ Refactoring istniejącego kodu  
- ✅ Implementacja Context7 patterns
- ✅ PSR-12 compliance verification
- ✅ Enterprise code quality assurance

**Przykład użycia:**
```
/task coding-style-agent "Przejrzyj nowo utworzony ProductController 
i zweryfikuj zgodność z PSR-12, Laravel best practices i Context7 patterns."
```

---

### 📚 **DOCUMENTATION-READER**
```yaml
Agent: documentation-reader
Model: sonnet
Specjalizacja: Zgodność z dokumentacją projektu
```

**Kiedy używać:**
- ✅ Przed implementacją nowych funkcjonalności
- ✅ Weryfikacja compliance z `_init.md` requirements
- ✅ Sprawdzenie zgodności z Prestashop DB structure
- ✅ Validation przeciwko oficjalnej dokumentacji API
- ✅ Review requirements consistency

**Przykład użycia:**
```
/task documentation-reader "Zweryfikuj czy planowana implementacja 
systemu 8 grup cenowych jest zgodna z wymaganiami z _init.md 
i strukturą bazy Prestashop."
```

---

## 🛠️ AGENCI SPECJALISTYCZNI

### 🔶 **LARAVEL-EXPERT**
```yaml
Agent: laravel-expert
Model: sonnet
Specjalizacja: PHP 8.3 + Laravel 12.x backend
```

**Kiedy używać:**
- ✅ Implementacja core business logic
- ✅ Database migrations i model relationships
- ✅ API endpoints i controllers development
- ✅ Authentication & authorization (7 poziomów)
- ✅ Service layer architecture
- ✅ Queue jobs i background processing

**Najczęściej używany z:**
- `database-expert` (schema design)
- `coding-style-agent` (code review)

**Przykład użycia:**
```
/task laravel-expert "Zaimplementuj ProductService z metodami dla CRUD 
operacji, sync z external APIs i bulk operations. Uwzględnij error handling 
i audit trail dla wszystkich operacji."
```

---

### 🗄️ **DATABASE-EXPERT**
```yaml
Agent: database-expert
Model: sonnet  
Specjalizacja: MySQL i optymalizacja bazy danych
```

**Kiedy używać:**
- ✅ Projektowanie database schemas
- ✅ Complex migrations z proper rollback
- ✅ Query optimization i performance tuning
- ✅ Database constraints i data integrity  
- ✅ Indexing strategies dla large datasets

**Najczęściej używany z:**
- `laravel-expert` (model relationships)
- `prestashop-api-expert` (DB structure compatibility)

**Przykład użycia:**
```
/task database-expert "Zaprojektuj optymalną strukturę tabel dla systemu 
dopasowań pojazdów (Model/Oryginał/Zamiennik) z uwzględnieniem performance 
i możliwości filtrowania per sklep Prestashop."
```

---

### 🛒 **PRESTASHOP-API-EXPERT**  
```yaml
Agent: prestashop-api-expert
Model: sonnet
Specjalizacja: Integracja z Prestashop 8.x/9.x API
```

**Kiedy używać:**
- ✅ Implementacja Prestashop API connections
- ✅ Product synchronization workflows
- ✅ Multi-store data management
- ✅ Image upload i directory structure
- ✅ Category mapping między systemami
- ✅ Price groups jako specific_prices

**Najczęściej używany z:**
- `database-expert` (DB structure compatibility)
- `laravel-expert` (service integration)

**Przykład użycia:**
```
/task prestashop-api-expert "Zaimplementuj synchronizację produktu 
z dopasowaniami pojazdów na wieloma sklepami Prestashop. 
Uwzględnij filtrowanie 'zbanowanych' modeli per sklep."
```

---

### 🔗 **ERP-INTEGRATION-EXPERT**
```yaml
Agent: erp-integration-expert
Model: sonnet
Specjalizacja: Integracje ERP (Baselinker #1, Subiekt GT, Microsoft Dynamics)
```

**Kiedy używać:**
- ✅ Integration z systemami ERP
- ✅ Data synchronization strategies
- ✅ Order management i delivery system
- ✅ Multi-warehouse mapping
- ✅ API rate limiting i error recovery

**Najczęściej używany z:**
- `laravel-expert` (service architecture) 
- `database-expert` (mapping tables)

**Przykład użycia:**
```
/task erp-integration-expert "Zaimplementuj dwukierunkową synchronizację 
stanów magazynowych z Baselinker API. Priorytet dla mapowania 6 magazynów 
PPM na magazyny Baselinker."
```

---

### 🎨 **FRONTEND-SPECIALIST**
```yaml  
Agent: frontend-specialist
Model: sonnet
Specjalizacja: Livewire 3.x + Blade + Alpine.js
```

**Kiedy używać:**
- ✅ Livewire components development  
- ✅ Interactive UI z Alpine.js
- ✅ Complex forms z real-time validation
- ✅ Dashboard interfaces z role-based access
- ✅ Search interfaces z intelligent suggestions
- ✅ Responsive design i dark/light theme

**Najczęściej używany z:**
- `laravel-expert` (backend integration)
- `coding-style-agent` (frontend code quality)

**Przykład użycia:**
```
/task frontend-specialist "Stwórz Livewire component dla listy produktów 
z zaawansowanymi filtrami, bulk operations, search suggestions 
i real-time updates."
```

---

### 📊 **IMPORT-EXPORT-SPECIALIST**
```yaml
Agent: import-export-specialist  
Model: sonnet
Specjalizacja: XLSX processing i data workflows
```

**Kiedy używać:**
- ✅ XLSX import/export functionality
- ✅ Column mapping systems
- ✅ Background processing dla large files
- ✅ Data validation i error handling
- ✅ Progress tracking i notifications

**Najczęściej używany z:**
- `laravel-expert` (queue jobs)
- `database-expert` (batch operations)

**Przykład użycia:**
```
/task import-export-specialist "Zaimplementuj system importu XLSX 
z szablonem POJAZDY. Uwzględnij mapowanie kolumn, walidację biznesową 
i processing w tle dla plików do 10MB."
```

---

### 🚀 **DEPLOYMENT-SPECIALIST**
```yaml
Agent: deployment-specialist
Model: sonnet  
Specjalizacja: Deployment na MyDevil shared hosting
```

**Kiedy używać:**
- ✅ Production deployment workflows
- ✅ Shared hosting optimization
- ✅ Performance tuning dla limited resources  
- ✅ Backup i disaster recovery strategies
- ✅ Environment configuration
- ✅ CI/CD pipeline setup

**Najczęściej używany z:**
- `laravel-expert` (production optimization)
- `database-expert` (database deployment)

**Przykład użycia:**
```
/task deployment-specialist "Zoptymalizuj konfigurację Laravel 
dla MyDevil shared hosting. Skoncentruj się na memory usage, 
database connections i file permissions."
```

---

## 🔄 PRZEPŁYW PRACY Z AGENTAMI (Z MCP CODEX)

### 1. **ROZPOCZĘCIE NOWEJ FUNKCJONALNOŚCI z MCP Codex**

```mermaid
sequenceDiagram
    participant U as User
    participant M as Main Coordinator  
    participant A as Architect
    participant DR as Documentation-Reader
    participant MCP as MCP Codex
    participant S as Specialist Agent
    
    U->>M: Prośba o nową funkcjonalność
    M->>DR: Sprawdź zgodność z dokumentacją
    DR->>M: Raport zgodności
    M->>A: Zaplanuj implementację
    A->>MCP: Konsultacja planu z _init.md
    MCP->>A: Zoptymalizowany plan
    A->>M: Plan + hierarchia zadań
    M->>S: Deleguj konkretne zadanie
    S->>MCP: Implementacja kodu
    MCP->>S: Gotowy kod
    S->>MCP: Weryfikacja kodu
    MCP->>S: Raport weryfikacji
    S->>M: Raport wykonania + weryfikacja
    M->>U: Podsumowanie + aktualizacja planu
```

### 2. **DEBUGGING WORKFLOW**

```mermaid
sequenceDiagram
    participant U as User
    participant M as Main Coordinator
    participant D as Debugger  
    participant LE as Laravel-Expert
    participant DE as Database-Expert
    
    U->>M: Problem z aplikacją
    M->>D: Diagnoza problemu
    D->>M: Analiza + hipotezy
    alt Database Issue
        M->>DE: Optymalizacja zapytań
        DE->>M: Rozwiązanie
    else Laravel Issue  
        M->>LE: Fix implementation
        LE->>M: Rozwiązanie
    end
    M->>U: Problem rozwiązany
```

### 3. **FEATURE DEVELOPMENT CYCLE**

```
📋 PLANNING PHASE
├── Architect: Decompose feature into tasks
├── Documentation-Reader: Validate requirements
└── Update Plan_Projektu/ with detailed tasks

🛠️ IMPLEMENTATION PHASE  
├── Specialist Agents: Implement specific parts
├── Coding-Style-Agent: Review code quality
└── Create implementation files

🧪 TESTING PHASE
├── Debugger: Diagnose any issues  
├── Laravel-Expert: Fix backend problems
└── Deployment-Specialist: Prepare for production

📦 DEPLOYMENT PHASE
├── Deployment-Specialist: Deploy to MyDevil
├── Health checks and monitoring
└── Update project plan with completion status
```

---

## 📊 SYSTEM RAPORTOWANIA

### Format Raportu Agenta

Każdy agent tworzy raport w formacie:

```markdown
# RAPORT PRACY AGENTA: [nazwa_agenta]
**Data**: [YYYY-MM-DD HH:MM]
**Agent**: [nazwa_agenta]  
**Zadanie**: [krótki opis zadania]

## ✅ WYKONANE PRACE
- Lista wykonanych zadań
- Ścieżki do utworzonych/zmodyfikowanych plików  
- Krótkie opisy zmian

## ⚠️ PROBLEMY/BLOKERY
- Lista napotkanych problemów
- Nierozwiązane kwestie wymagające uwagi

## 📋 NASTĘPNE KROKI
- Co należy zrobić dalej
- Zalecenia dla kolejnych agentów

## 📁 PLIKI
- [nazwa_pliku.ext] - [opis zmian]
- [folder/nazwa_pliku.ext] - [opis zmian]
```

### Lokalizacja Raportów

```
_AGENT_REPORTS/
├── architect_REPORT_2024-09-05_14-30.md
├── laravel-expert_REPORT_2024-09-05_15-45.md  
├── database-expert_REPORT_2024-09-05_16-20.md
└── prestashop-api-expert_REPORT_2024-09-05_17-10.md
```

---

## 💡 PRZYKŁADY UŻYCIA

### **Przykład 1: Implementacja Nowej Funkcjonalności z MCP Codex**

**Zadanie:** "Dodaj system komentarzy do produktów z moderacją."

**NOWY Przepływ z MCP Codex:**
1. **Architect** → planuje architekturę → **KONSULTACJA z MCP Codex** → optymalizacja planu
2. **Database-Expert** → projektuje schema → **DELEGACJA do MCP Codex** → implementacja migrations
3. **MCP Codex** → **WERYFIKACJA** database schema pod kątem performance i security
4. **Laravel-Expert** → analizuje requirements → **DELEGACJA do MCP Codex** → implementacja models, controllers, services
5. **MCP Codex** → **WERYFIKACJA** backend kodu pod kątem Laravel 12.x best practices
6. **Frontend-Specialist** → pisze Livewire components → **WERYFIKACJA przez MCP Codex** → poprawki
7. **Coding-Style-Agent** → **DELEGACJA do MCP Codex** → final code review
8. **Deployment-Specialist** → deploy na production

### **Przykład 2: Integracja z Nowym ERP**

**Zadanie:** "Dodaj integrację z Microsoft Dynamics Business Central."

**Przepływ:**  
1. **Documentation-Reader** → sprawdza requirements i API docs
2. **Architect** → planuje integration architecture
3. **ERP-Integration-Expert** → implementuje API connector  
4. **Database-Expert** → dodaje mapping tables
5. **Laravel-Expert** → integruje z core application
6. **Debugger** → testuje i debuguje integration

### **Przykład 3: Performance Optimization**

**Zadanie:** "Aplikacja jest wolna przy dużej liczbie produktów."

**Przepływ:**
1. **Debugger** → diagnozuje bottlenecks
2. **Database-Expert** → optymalizuje queries i indexes  
3. **Laravel-Expert** → implementuje caching strategies
4. **Frontend-Specialist** → optymalizuje frontend performance
5. **Deployment-Specialist** → optymalizuje production environment

---

## 🔧 TROUBLESHOOTING

### Częste Problemy

**Problem:** Agent nie rozumie kontekstu projektu PPM-CC-Laravel
```
Rozwiązanie: Sprawdź czy agent ma dostęp do CLAUDE.md i _init.md. 
Przekaż kontekst explicite w zadaniu.
```

**Problem:** Agent tworzy kod niezgodny z enterprise standards
```
Rozwiązanie: Zawsze używaj coding-style-agent do review. 
Upewnij się że agent ma dostęp do Context7 guidelines.
```

**Problem:** Konflikty między różnymi agentami
```
Rozwiązanie: Main Coordinator musi czytać raporty wszystkich agentów 
przed delegowaniem kolejnych zadań.
```

**Problem:** Agent nie aktualizuje planu projektu
```
Rozwiązanie: Użyj Architect agenta do aktualizacji Plan_Projektu/ 
po każdym ukończonym milestone.
```

### Najlepsze Praktyki

✅ **Zawsze czytaj raporty** poprzednich agentów przed delegowaniem  
✅ **Używaj konkretnych zadań** - unikaj zbyt ogólnych poleceń  
✅ **Kombinuj agentów** - np. Laravel-Expert + Database-Expert  
✅ **Aktualizuj plan** po każdym znaczącym postępie  
✅ **Dokumentuj problemy** w raportach agentów  

---

## 📈 METRYKI I KPI

### Tracking Skuteczności Agentów

- **Code Quality Score** → przez Coding-Style-Agent
- **Bug Resolution Time** → przez Debugger  
- **Feature Completion Rate** → przez Architect
- **Documentation Compliance** → przez Documentation-Reader
- **Deployment Success Rate** → przez Deployment-Specialist

### Dashboard Agentów (Future Enhancement)

```
📊 AGENT DASHBOARD
├── 🏛️ Architect: 12 planów, 89% ukończonych zadań
├── 🔶 Laravel-Expert: 47 implementacji, 0 bugs
├── 🗄️ Database-Expert: 23 migracje, 100% rollback success  
├── 🛒 Prestashop-API-Expert: 156 produktów zsynchronizowanych
├── 🔗 ERP-Integration-Expert: 3 aktywne integracje
├── 🎨 Frontend-Specialist: 28 komponentów, 95% user satisfaction
├── 📊 Import-Export-Specialist: 45 importów, avg 2.3min processing
└── 🚀 Deployment-Specialist: 34 wdrożenia, 99.2% uptime
```

---

## 🎯 PODSUMOWANIE

System 12 Sub-Agentów Claude Code dla PPM-CC-Laravel zapewnia:

✅ **Kompletną ekspertyzę** w każdej dziedzinie projektu  
✅ **Consistency** w jakości kodu i standardach  
✅ **Scalability** dla złożonych enterprise workflows  
✅ **Traceability** przez system raportowania  
✅ **Efficiency** przez specialized task delegation  

### Kolejne Kroki

1. **Test agentów** na małych zadaniach
2. **Optymalizacja promptów** na podstawie wyników  
3. **Rozszerzenie systemu** o dodatkowych specjalistów jeśli potrzeba
4. **Implementacja automatyzacji** workflow między agentami
5. **Dashboard monitoring** dla track'owania performance

---

**🚀 System Sub-Agentów gotowy do użycia!**  
*Porażka nie wchodzi w grę. Sukces projektu PPM-CC-Laravel jest gwarantowany przez profesjonalny zespół AI Specialists.*