# 🤖 PRZEWODNIK UŻYCIA AGENTÓW - PPM-CC-Laravel

**Data utworzenia:** 2025-09-27
**Projekt:** PPM-CC-Laravel (Prestashop Product Manager)
**Wersja:** 1.0

---

## ⚡ ZASADY PODSTAWOWE

1. **ZAWSZE** czytaj raporty agentów przed delegowaniem nowych zadań
2. **TYLKO JEDEN** agent może być w stanie `in_progress` w danym momencie
3. **WYMAGANE** raportowanie w `_AGENT_REPORTS/` po każdym zadaniu
4. **OBOWIĄZKOWE** aktualizowanie TodoWrite podczas pracy agentów

---

## ⛔ KATEGORYCZNY ZAKAZ SYMULACJI I PLACEHOLDERÓW (WSZYSTKIE AGENTY)

**CRITICAL RULE FOR ALL AGENTS:** ZERO TOLERANCE for simulations, placeholders, mock data, or fake operations!

### ❌ ABSOLUTELY FORBIDDEN:

**1. Symulowane operacje bez wykonania rzeczywistych komend:**
```markdown
❌ FORBIDDEN:
- "✅ Uploaded file.php (58 KB)" ← WITHOUT actual pscp command!
- "✅ Cache cleared successfully" ← WITHOUT actual plink command!
- "✅ Migration completed" ← WITHOUT actual artisan migrate!
- "✅ Tests passed (100%)" ← WITHOUT actual php artisan test!
```

**2. Placeholder dane i wartości:**
```php
❌ FORBIDDEN:
$product->price = 150.0;  // ← Hardcoded fake price!
'value' => 'Lorem ipsum'; // ← Placeholder text!
'users' => 250;           // ← Mock count!
'status' => 'active';     // ← Fake status!
```

**3. Fake raportowanie bez weryfikacji:**
```markdown
❌ FORBIDDEN:
## VERIFICATION RESULTS
- ✅ All files deployed successfully ← WITHOUT grep/ls verification!
- ✅ Application running correctly  ← WITHOUT curl/health check!
- ✅ Database migrated             ← WITHOUT migrate:status check!
```

### ✅ MANDATORY - Real Operations Only:

**1. Rzeczywiste komendy z actual output:**
```bash
✅ CORRECT - deployment-specialist:
# Execute REAL command
pscp -i "..." -P 64321 "file.php" "host@...:domains/.../file.php"

# Wait for actual output
Output: "file.php | 57 kB | 57.8 kB/s | ETA: 00:00:00 | 100%"

# Report with REAL output
"✅ Uploaded file.php (57 KB) - confirmed via pscp output"
```

**2. Prawdziwe dane z bazy/API:**
```php
✅ CORRECT - Any agent writing code:
// Get REAL data from database
$product = Product::find($productId);
$price = $product->price;  // ← Real value from DB

// Get REAL data from API
$response = $apiClient->getProduct($sku);
$stock = $response['stock']; // ← Real value from API
```

**3. Weryfikacja po każdej operacji:**
```bash
✅ CORRECT - deployment-specialist:
# 1. Upload file
pscp -i "..." file.php host@...:domains/.../file.php

# 2. VERIFY file exists on server (MANDATORY)
plink ... "ls -lh domains/.../file.php"
Output: "-rw-rw-r-- 1 user user 57K Oct 15 14:14 file.php"

# 3. VERIFY file content (for critical files)
plink ... "grep -n 'public function' domains/.../file.php | head -3"
Output: "10: public function index()"

# 4. ONLY THEN report success
"✅ file.php deployed and VERIFIED on server"
```

### 📋 VERIFICATION CHECKLIST (Mandatory for ALL Agents):

**deployment-specialist:**
- [ ] Execute REAL pscp/plink commands
- [ ] Capture actual command output
- [ ] Verify file exists on server (ls/stat)
- [ ] Verify file content on server (grep/cat)
- [ ] Report ONLY after confirmation

**livewire-specialist / laravel-expert:**
- [ ] NO hardcoded values (prices, names, counts)
- [ ] Get data from DB/models ONLY
- [ ] Use realistic ranges for random data (NOT fixed values)
- [ ] Validate data types match database schema

**frontend-specialist:**
- [ ] ZERO inline styles (use CSS classes ONLY)
- [ ] NO placeholder text ("Lorem ipsum", "Example")
- [ ] NO fake counts/numbers in UI
- [ ] Dynamic content from backend ONLY

**coding-style-agent:**
- [ ] Check for hardcoded values in reviewed code
- [ ] Flag ANY placeholder/mock data as CRITICAL violation
- [ ] Verify Context7 patterns match REAL library docs

### 🚫 CONSEQUENCES OF VIOLATION:

**IF ANY AGENT SIMULATES INSTEAD OF EXECUTING:**
1. ❌ User will see NO changes on production
2. ❌ Deployment will FAIL silently
3. ❌ Agent report will be INVALID
4. ❌ User trust in system DESTROYED
5. ❌ Agent delegation pattern will be REVISED

**ZASADA:** Jeśli nie możesz wykonać rzeczywistej operacji - **NIE RAPORTUJ SUKCESU**. Zgłoś blokadę i poproś o pomoc.

### ✅ CORRECT Example - deployment-specialist:

```markdown
## DEPLOYMENT REPORT

**File Upload:**
- Command: `pscp -i "..." -P 64321 "CategoryTree.php" "host@...:domains/.../CategoryTree.php"`
- Output: `CategoryTree.php | 57 kB | 57.8 kB/s | 100%`
- Status: ✅ REAL upload completed

**Cache Clear:**
- Command: `plink ... "php artisan view:clear && cache:clear"`
- Output: `INFO Compiled views cleared successfully.`
- Status: ✅ REAL cache cleared

**Verification:**
- Command: `plink ... "grep 'showMergeCategoriesModal' .../CategoryTree.php | head -3"`
- Output: `199:    public $showMergeCategoriesModal = false;`
- Status: ✅ Code VERIFIED on server (file contains expected code)

**Conclusion:** Deployment VERIFIED - all steps confirmed with real command outputs.
```

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

### 🔄 **refactoring-specialist** - Code Refactoring Expert
**Model:** `sonnet`
**Specjalizacja:** Enterprise refactoring, separation of concerns, file size compliance
**Data utworzenia:** 2025-10-16

#### 🔑 KIEDY UŻYWAĆ:
- ✅ **ZAWSZE** gdy plik przekracza 300 linii (CLAUDE.md violation)
- ✅ Przed rozpoczęciem ETAP_05a (Product.php 2181 linii → refactor)
- ✅ Service/Model/Component przekracza max size
- ✅ Kod wymaga separation of concerns
- ✅ Planowany refactoring architektury
- ✅ Przygotowanie do nowych funkcjonalności

#### 📋 PRZYKŁADY ZASTOSOWAŃ:
```
✅ "Product.php ma 2181 linii - zrefaktoruj do max 300 linii per file"
✅ "CompatibilityManager 600 linii - rozbij na mniejsze services"
✅ "VariantsTab component 500 linii - wydziel business logic"
✅ "Przygotuj architekturę przed implementacją ETAP_05a"
```

#### ⚠️ CRITICAL RULES:
- **MAX 300 LINII** per file (CLAUDE.md mandatory)
- **Context7 FIRST** - check Laravel/Livewire patterns
- **SKU-first preserved** - maintain SKU as primary key
- **No breaking changes** - preserve public API
- **Tests GREEN** - after EACH extraction

#### 🎯 REFACTORING PATTERNS:

**Large Model → Traits:**
```
Product.php (2181 linii)
→ Product.php (250 linii) + 8 Traits (150 linii each)
```

**Large Service → Concerns:**
```
CompatibilityManager.php (600 linii)
→ Manager (180 linii) + 4 Concerns (120-140 linii each)
```

**Large Component → Service + Traits:**
```
VariantsTab.php (500 linii)
→ Component (220 linii) + Service (180 linii) + 3 Traits (110-140 linii each)
```

#### 📊 WORKFLOW:
```
1. Analysis (2-3h) → measure violations, plan split
2. Context7 check → verify Laravel/Livewire patterns
3. Execution (4-8h per file) → extract to Traits/Services
4. Verification → tests GREEN, file sizes OK
5. Report → _AGENT_REPORTS/refactoring_*.md
```

#### ⚠️ DEPENDENCIES:
- **PRZED:** Git backup (commit + push)
- **AFTER:** coding-style-agent review
- **VERIFY:** All tests pass, no regressions

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

### 🔄 PATTERN 3: Refactoring (File Size Violations)
```
1. documentation-reader → verify violations (file sizes >300 lines)
2. refactoring-specialist → analysis + plan split strategy
3. refactoring-specialist → execute refactor (Traits/Services extraction)
4. coding-style-agent → compliance check (Context7, PSR-12)
5. [Domain specialist if needed] → adjust business logic
6. Test verification → all tests GREEN
```

**PRZYKŁAD:**
```
USER: "Product.php ma 2181 linii - naruszona zasada CLAUDE.md"

WORKFLOW:
1. documentation-reader → verify compliance violations
   Output: "CRITICAL: Product.php 2181 lines (7x limit!)"

2. refactoring-specialist → analysis phase (2-3h)
   Output: "Plan: Extract 8 Traits (pricing, stock, categories, variants, features, compatibility, multi-store, sync)"

3. refactoring-specialist → execute refactor (12-16h)
   Output: "Product.php (250 lines) + 8 Traits (<150 lines each)"

4. coding-style-agent → final review
   Output: "✅ All files ≤300 lines, Context7 compliant, PSR-12 OK"

5. laravel-expert (if needed) → adjust relationships

6. Run tests → verify GREEN
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
| **Refactoring (>300 lines)** | **refactoring-specialist** | **coding-style-agent** |

---

**🏁 PAMIĘTAJ:** Ten przewodnik to żywy dokument. Aktualizuj go gdy pojawiają się nowe wzorce użycia agentów w projekcie PPM-CC-Laravel.

---

**Autor:** Claude Code AI
**Data:** 2025-09-27
**Projekt:** PPM-CC-Laravel Enterprise PIM System
**Status:** ✅ ACTIVE GUIDE