# Claude Code Skills - Przewodnik Użycia
**Projekt:** PPM-CC-Laravel
**Data utworzenia:** 2025-10-17
**Ostatnia aktualizacja:** 2025-10-22 (dodano ppm-architecture-compliance)
**Wersja:** 1.1.0
**Total Skills:** 9

---

## 📚 SPIS TREŚCI

1. [Wprowadzenie](#wprowadzenie)
2. [Co to są Skills?](#co-to-są-skills)
3. [Dostępne Skills](#dostępne-skills)
4. [Skills vs Slash Commands](#skills-vs-slash-commands)
5. [Kiedy używać Skills](#kiedy-używać-skills)
6. [Skills Integration w Agentach](#skills-integration-w-agentach)
7. [Przykłady użycia](#przykłady-użycia)
8. [Best Practices](#best-practices)

---

## WPROWADZENIE

System Skills w Claude Code automatyzuje powtarzalne operacje poprzez model-invoked capabilities. Claude autonomicznie wybiera Skills gdy description pasuje do aktualnego zadania, eliminując potrzebę manualnego wywoływania.

**Kluczowe korzyści:**
- ✅ Automatyzacja powtarzalnych workflow
- ✅ Redukcja błędów przy standardowych operacjach
- ✅ Spójność w wykonywaniu zadań
- ✅ Szybsze wykonanie typowych operacji
- ✅ Wbudowana dokumentacja best practices

---

## CO TO SĄ SKILLS?

### Definicja

**Skills** to model-invoked capabilities - funkcje które Claude automatycznie wybiera i wykonuje gdy:
1. Description Skill pasuje do aktualnego zadania
2. Kontekst wskazuje na potrzebę użycia Skill
3. Agent ma w swoich instrukcjach informacje o dostępnych Skills

### Różnica od narzędzi (tools)

| Aspekt | Tools (Read, Edit, Bash) | Skills |
|--------|--------------------------|--------|
| Wywołanie | Zawsze dostępne | Wybierane przez Claude gdy pasują |
| Zakres | Pojedyncza operacja | Kompletny workflow |
| Dokumentacja | Wbudowana w tool | W SKILL.md + reference files |
| Przykład | `Read file.php` | `hostido-deployment` (deploy + cache + verify) |

---

## DOSTĘPNE SKILLS

### 1. **hostido-deployment**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\hostido-deployment\`

**Przeznaczenie**: Automatyczny deployment na serwer produkcyjny Hostido

**Główne funkcje**:
- Upload plików przez pscp (SSH)
- Czyszczenie cache (artisan optimize:clear, php artisan config:clear)
- Fix Vite manifest path issues
- Weryfikacja po deployment

**Kiedy używać**:
- Deployment pojedynczych plików PHP/Blade
- Deployment assets (JS/CSS) z Vite manifest fix
- Deployment z migracjami DB
- Deployment Livewire components

**Reference**: `REFERENCE.md` - Complete command syntax

---

### 2. **livewire-troubleshooting**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\livewire-troubleshooting\`

**Przeznaczenie**: Diagnoza i fix znanych Livewire 3.x issues

**Znane issues (9 documented)**:
1. **wire:snapshot Problem** - rendering raw code instead of UI
2. **Dependency Injection Conflict** - constructor vs mount conflicts
3. **wire:poll Issues** - polling conflicts with other directives
4. **x-teleport Issues** - Livewire + Alpine.js teleport conflicts
5. **wire:key Missing** - state corruption in loops without wire:key
6. (+ 4 more documented issues)

**Kiedy używać**:
- Livewire component rendering issues
- wire:model not updating
- Component state synchronization problems
- Events not firing properly

**Reference**: `LIVEWIRE_ISSUES_REFERENCE.md` - All 9 issues with solutions

---

### 3. **frontend-verification**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\frontend-verification\`

**Przeznaczenie**: ⚠️ **MANDATORY** screenshot verification przed informowaniem użytkownika "Gotowe ✅"

**Workflow**:
```
1. Deploy UI changes to production
2. Run: node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/path
3. Analyze screenshot for:
   - Layout correctness
   - Responsive behavior
   - Component rendering
   - CSS styling accuracy
   - Alpine.js/Livewire interactivity
4. ONLY THEN inform user "Gotowe ✅"
```

**Kiedy używać**:
- **ZAWSZE** po deployment UI changes
- **ZAWSZE** przed informowaniem użytkownika o ukończeniu frontend work
- Po modyfikacji Blade templates
- Po zmianie CSS/Tailwind

**KRYTYCZNA ZASADA**: ❌ **NIGDY** nie mów użytkownikowi "Gotowe ✅" bez screenshot verification!

**Reference**: `VERIFICATION_CHECKLIST.md` - Complete verification checklist

---

### 4. **agent-report-writer**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\agent-report-writer\`

**Przeznaczenie**: ⚠️ **MANDATORY** generowanie raportów agentów w _AGENT_REPORTS/

**Format raportu**:
```markdown
# RAPORT PRACY AGENTA: [nazwa_agenta]
**Data**: [YYYY-MM-DD HH:MM]
**Agent**: [nazwa_agenta]
**Zadanie**: [krótki opis]

## ✅ WYKONANE PRACE
[Lista z plikami]

## ⚠️ PROBLEMY/BLOKERY
[Lista problemów]

## 📋 NASTĘPNE KROKI
[Co dalej]

## 📁 PLIKI
[Lista plików z opisami]
```

**Kiedy używać**:
- **ZAWSZE** po ukończeniu pracy agenta
- Po deployment
- Po implementacji feature
- Po debugging session

**Reference**: `REPORT_TEMPLATE.md` - Complete template

---

### 5. **project-plan-manager**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\project-plan-manager\`

**Przeznaczenie**: Zarządzanie Plan_Projektu/ files z accurate status tracking

**Kluczowe funkcje**:
- Aktualizacja emoji statusów (❌🛠️✅⚠️)
- Dodawanie ścieżek do plików przy ✅
- Accurate progress reporting
- ⚠️ **ZAKAZ** marking ETAP as ✅ if ANY sub-tasks are ❌

**Format**:
```markdown
## ❌ 1. ETAP 1
### 🛠️ 1.1 Zadanie w trakcie
    ✅ 1.1.1 Ukończone zadanie
        └── PLIK: app/Http/Controllers/ProductController.php
### ❌ 1.2 Nierozpoczęte
```

**Kiedy używać**:
- Aktualizacja planu po milestone
- Oznaczanie zadań jako completed
- Raportowanie postępu projektu

**KRYTYCZNA ZASADA**: Dokładny progress tracking - NIE raportuj ETAP jako ✅ if ANY sekcje są ❌

**Reference**: `PLAN_FORMAT_GUIDE.md` - Hierarchical format guide

---

### 6. **context7-docs-lookup**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\context7-docs-lookup\`

**Przeznaczenie**: ⚠️ **MANDATORY** weryfikacja patterns z oficjalnej dokumentacji PRZED implementacją

**Workflow**:
```
1. BEFORE implementing: Use mcp__context7__get-library-docs
2. Verify current patterns from official sources
3. Implement according to documentation
4. Reference docs in comments/PR
```

**Library IDs**:
- Laravel 12.x: `/websites/laravel_12_x` (4927 snippets)
- Livewire 3.x: `/livewire/livewire` (867 snippets)
- Alpine.js: `/alpinejs/alpine` (364 snippets)
- PrestaShop: `/prestashop/docs` (3289 snippets)

**Kiedy używać**:
- **ZAWSZE** przed implementing new Laravel features
- **ZAWSZE** przed creating Livewire components
- Przed API integration patterns
- Przed architecture decisions

**Reference**: `CONTEXT7_USAGE.md` - MCP usage patterns

---

### 7. **issue-documenter**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\issue-documenter\`

**Przeznaczenie**: Dokumentowanie complex issues (>2h debugging) dla przyszłości

**Format dokumentu**:
```markdown
# [COMPONENT]_[ISSUE_TYPE]_ISSUE

**Status**: ✅ ROZWIĄZANY
**Data**: YYYY-MM-DD
**Czas naprawy**: ~X godzin
**Wpływ**: KRYTYCZNY / WYSOKIE / ŚREDNIE / NISKA

## 🚨 OPIS PROBLEMU
[Symptoms]

## 🔍 PRZYCZYNA
[Root cause]

## ✅ ROZWIĄZANIE
[Solution with code examples]

## 🛡️ ZAPOBIEGANIE
[Prevention rules]

## 📋 CHECKLIST NAPRAWY
[Step-by-step fix guide]
```

**Kiedy używać**:
- Issue wymagający >2h debugowania
- Complex root cause analysis
- Issues które mogą się powtórzyć
- Lessons learned from bugs

**Reference**: `ISSUE_TEMPLATE.md` - Complete documentation template

---

### 8. **debug-log-cleanup**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\debug-log-cleanup\`

**Przeznaczenie**: Cleanup extensive debug logging AFTER user confirmation

**⚠️ CRITICAL: WAIT FOR USER CONFIRMATION!**

**Workflow**:
```
Development Phase:
├─ Add extensive logging (Log::debug with types, BEFORE/AFTER)
├─ Deploy + Test
└─ User confirms: "działa idealnie" ✅

THEN Cleanup:
├─ Remove all Log::debug() calls
├─ Remove gettype(), array_map('gettype')
├─ Remove BEFORE/AFTER markers
├─ Remove CALLED/COMPLETED markers
└─ Keep Log::info(), Log::warning(), Log::error()
```

**Co usuwać**:
```php
// ❌ REMOVE
Log::debug('methodName CALLED', [...]);
gettype($var);
'array_BEFORE' => ...
'CALLED', 'COMPLETED'
```

**Co zachować**:
```php
// ✅ KEEP
Log::info('Business operation', [...]);
Log::warning('Unusual situation', [...]);
Log::error('Error occurred', [...]);
```

**Kiedy używać**:
- **TYLKO** po user confirmation "działa idealnie"
- Po successful deployment + testing
- NIGDY before user confirms

**Reference**: `CLEANUP_PATTERNS.md` - Detailed cleanup patterns

---

### 9. **ppm-architecture-compliance**
📁 Lokalizacja: `C:\Users\kamil\.claude\skills\ppm-architecture-compliance\`

**Przeznaczenie**: ⚠️ MANDATORY compliance check with PPM-CC-Laravel documentation

**⚠️ CRITICAL: USE BEFORE IMPLEMENTATION!**

**Documentation Coverage**:
- **Architecture & Menu** - `_DOCS/ARCHITEKTURA_PPM/` (21 modules, 2000+ lines)
- **Database Schema** - `_DOCS/Struktura_Bazy_Danych.md` (1060 lines)
- **File Structure** - `_DOCS/Struktura_Plikow_Projektu.md` (373 lines)

**Compliance Checks (5 categories)**:

1. **Architecture & Menu**
   - Menu placement (12 documented sections)
   - Routing patterns (49 RESTful routes)
   - Role-based access (7-level hierarchy)
   - Multi-store support (SKU-first architecture)

2. **Database Schema**
   - Table existence in documentation
   - Column alignment with schema
   - Foreign keys and indexes
   - Naming conventions (snake_case)
   - ETAP mapping

3. **File Structure**
   - Folder placement (app/, resources/, database/)
   - Naming conventions (PascalCase/kebab-case/snake_case)
   - ETAP alignment
   - Component organization

4. **Design System**
   - MPP TRADE color palette (Primary: #3b82f6)
   - Typography (Inter font, 16px base)
   - Spacing (8px base scale)
   - Enterprise components (.enterprise-card, .tabs-enterprise)
   - ❌ NO inline styles (CATEGORICALLY FORBIDDEN)

5. **Integrations**
   - PrestaShop multi-version support (v8.x + v9.x)
   - ERP plugin-based architecture
   - Proper authentication patterns

**Red Flags (Auto-flagged violations)**:

CRITICAL (Block Implementation):
- ❌ New top-level menu items (must fit 12 existing sections)
- ❌ Non-RESTful routes
- ❌ Database tables not in documentation
- ❌ Violating SKU-first architecture
- ❌ Hardcoded role checks (must use middleware)
- ❌ Inline styles (CATEGORICALLY FORBIDDEN)

WARNING (Require Documentation Update):
- ⚠️ Routes not in ROUTING_TABLE.md
- ⚠️ Missing permission matrix entries
- ⚠️ New files outside documented structure
- ⚠️ Custom colors not in design system

**Workflow**:
```
1. User assigns PPM task
2. Agent invokes ppm-architecture-compliance (AUTO)
3. Skill reads relevant documentation modules
4. Skill analyzes task vs architecture
5. Skill generates compliance report
   ├─ ✅ COMPLIANT → Proceed with implementation
   └─ ❌ VIOLATION → Report blocker, STOP
```

**Output Format**:
```markdown
# PPM Architecture Compliance Report
**Task:** [description]

## ✅ COMPLIANCE CHECKS
[5 categories: Architecture, Database, Files, Design, Integrations]

## ⚠️ VIOLATIONS FOUND
[List with doc references]

## 💡 RECOMMENDATIONS
[Actionable steps]

## 📋 IMPLEMENTATION CHECKLIST
[Step-by-step]
```

**Kiedy używać**:
- **MANDATORY** przed rozpoczęciem prac nad PPM features
- Podczas planowania nowych features (architect)
- Przed tworzeniem migrations/models (laravel-expert)
- Przed implementacją Livewire components (livewire-specialist)
- Przed implementacją UI (frontend-specialist)
- Przed integracjami PrestaShop/ERP

**Agent Integration (MANDATORY)**:
- **architect**: Before planning features
- **laravel-expert**: Before migrations/models
- **livewire-specialist**: Before components
- **frontend-specialist**: Before UI implementation
- **prestashop-api-expert**: Before PS integrations
- **erp-integration-expert**: Before ERP integrations

**Reference**: `skill.md` + `README.md` - Complete compliance workflow

**Success Metrics**:
- ✅ 0% architectural violations in deployed code
- ✅ 100% documentation-code alignment
- ✅ All features fit documented structure
- ✅ No hardcoded patterns or inline styles

---

## SKILLS VS SLASH COMMANDS

### Skills (Model-Invoked)
- ✅ Autonomicznie wybierane przez Claude
- ✅ Description-based triggering
- ✅ Kompleksowe workflows
- ✅ Przykład: `hostido-deployment`, `livewire-troubleshooting`

### Slash Commands (User-Invoked)
- ✅ Użytkownik wywołuje przez `/command`
- ✅ Explicit triggering
- ✅ Custom project workflows
- ✅ Przykład: `/ccc`, `/cc`

**Kiedy Skills vs Commands**:
- **Use Skills** - Powtarzalne operacje które Claude może automatycznie rozpoznać
- **Use Commands** - Project-specific workflows wymagające user input

---

## KIEDY UŻYWAĆ SKILLS

### Decision Tree

```
Czy wykonuję powtarzalną operację?
├─ TAK → Czy istnieje Skill dla tego workflow?
│   ├─ TAK → Claude automatycznie wybierze Skill
│   └─ NIE → Stwórz nowy Skill (jeśli operacja często się powtarza)
└─ NIE → Use standard tools (Read, Edit, Bash)
```

### Operacje Idealne dla Skills

1. **Deployment workflows** - Zawsze te same kroki
2. **Known issue diagnosis** - Documented solutions
3. **Documentation verification** - Standard checks
4. **Report generation** - Standard format
5. **Plan management** - Consistent structure
6. **Debug cleanup** - Repeatable pattern

### Operacje NIE dla Skills

1. **Ad-hoc code changes** - Use Edit tool
2. **Exploratory debugging** - Use Read + Grep
3. **Custom project logic** - Implement directly
4. **User-specific workflows** - Use slash commands

---

## SKILLS INTEGRATION W AGENTACH

### Szablon Sekcji (dodaj do każdego agenta)

```markdown
## 🎯 SKILLS INTEGRATION

This agent should use the following Claude Code Skills when applicable:

**MANDATORY Skills:**
- **[skill-name]** - [Description] (PRIMARY SKILL!)
- **agent-report-writer** - For generating reports (ALWAYS)

**Optional Skills:**
- **[skill-name]** - [Description when useful]

**Skills Usage Pattern:**
```
1. [Step 1] → Use [skill] skill
2. [Step 2] → Use [skill] skill
3. After work → Use agent-report-writer (MANDATORY!)
```

**Integration with [Agent] Workflow:**
- Phase 1: [Description]
- Phase 2: [Description]
- Phase 3: Use agent-report-writer
```

### Skills Assignment Matrix

| Agent Type | MANDATORY Skills | OPTIONAL Skills |
|------------|------------------|-----------------|
| **Deployment** | hostido-deployment, frontend-verification, agent-report-writer | debug-log-cleanup, issue-documenter |
| **Livewire** | livewire-troubleshooting, context7-docs-lookup, agent-report-writer | debug-log-cleanup, issue-documenter |
| **Planning** | project-plan-manager, context7-docs-lookup, agent-report-writer | - |
| **Debugging** | livewire-troubleshooting, issue-documenter, debug-log-cleanup, agent-report-writer | - |
| **Documentation** | context7-docs-lookup, agent-report-writer | - |
| **General Coding** | context7-docs-lookup, agent-report-writer | debug-log-cleanup, issue-documenter |

---

## PRZYKŁADY UŻYCIA

### Example 1: Deployment Workflow

**Scenario**: Deploy new Livewire component to production

```
Agent: deployment-specialist
Skills Used:
1. context7-docs-lookup → Verify Livewire 3.x patterns
2. hostido-deployment → Upload + cache clear + verify
3. frontend-verification → Screenshot verification
4. agent-report-writer → Generate deployment report

Workflow:
├─ Read component code
├─ context7-docs-lookup: Verify patterns ✅
├─ hostido-deployment: Deploy to production ✅
├─ frontend-verification: Screenshot check ✅
└─ agent-report-writer: Document deployment ✅

Result: Fully automated deployment with verification
```

### Example 2: Livewire Debugging

**Scenario**: wire:snapshot rendering raw code

```
Agent: debugger
Skills Used:
1. livewire-troubleshooting → Check ISSUE #1
2. issue-documenter → Document if new issue
3. debug-log-cleanup → Clean after fix
4. agent-report-writer → Report debugging session

Workflow:
├─ User reports: "Livewire shows raw code"
├─ livewire-troubleshooting: ISSUE #1 wire:snapshot ✅
│   └─ Solution: Use Blade wrapper instead of direct route
├─ Apply fix
├─ Test + deploy
├─ User confirms: "działa idealnie"
├─ debug-log-cleanup: Remove debug logs ✅
└─ agent-report-writer: Document session ✅

Result: Quick fix using documented solution
```

### Example 3: Project Planning

**Scenario**: Update Plan_Projektu after milestone

```
Agent: architect
Skills Used:
1. project-plan-manager → Update plan with accurate status
2. agent-report-writer → Document plan changes

Workflow:
├─ ETAP_02 completed
├─ project-plan-manager: Update ETAP_02 ✅
│   ├─ Mark completed tasks ✅
│   ├─ Add file paths
│   ├─ Calculate accurate progress (29% not 100%)
│   └─ Update status: 🛠️ IN PROGRESS (not ✅)
└─ agent-report-writer: Document plan update ✅

Result: Accurate plan reflecting real progress
```

---

## BEST PRACTICES

### 1. Skills Selection

✅ **DO**:
- Let Claude autonomously select Skills based on description
- Add SKILLS INTEGRATION section to agent instructions
- Specify MANDATORY vs OPTIONAL Skills clearly
- Document Skills usage pattern in agent

❌ **DON'T**:
- Manually force Skill invocation in prompts
- Assume Claude will use Skill without proper description
- Skip agent-report-writer at end (MANDATORY!)
- Use Skill before verifying it matches workflow

### 2. Skills Development

✅ **DO**:
- Create Skills for frequently repeated workflows (>5x per project)
- Include comprehensive reference files
- Document all patterns and edge cases
- Test Skill with multiple agents before deployment

❌ **DON'T**:
- Create Skills for one-off operations
- Put all logic in SKILL.md (use reference files)
- Skip documentation of Skill capabilities
- Mix multiple unrelated workflows in one Skill

### 3. Skills Maintenance

✅ **DO**:
- Update Skills when workflow changes
- Add new issues to livewire-troubleshooting as discovered
- Keep reference files up-to-date
- Version Skills when making breaking changes

❌ **DON'T**:
- Let Skills become outdated with project evolution
- Remove old solutions from troubleshooting Skills
- Change Skill behavior without updating agents
- Skip testing after Skill updates

### 4. Skills Documentation

✅ **DO**:
- Document every Skill in SKILLS_USAGE_GUIDE.md
- Include real examples of Skill usage
- Explain WHEN to use each Skill
- Reference related _ISSUES_FIXES and _DOCS

❌ **DON'T**:
- Create Skills without documentation
- Skip usage examples
- Leave description vague
- Forget to update guide when adding new Skills

---

## PODSUMOWANIE

System Skills w PPM-CC-Laravel automatyzuje 8 kluczowych workflow:

1. **hostido-deployment** - Automatic deployment
2. **livewire-troubleshooting** - Known issues diagnosis
3. **frontend-verification** - UI verification (MANDATORY!)
4. **agent-report-writer** - Report generation (MANDATORY!)
5. **project-plan-manager** - Accurate plan tracking
6. **context7-docs-lookup** - Documentation verification (MANDATORY!)
7. **issue-documenter** - Complex issue documentation
8. **debug-log-cleanup** - Production log cleanup

**Kluczowe zasady**:
- ✅ Skills są autonomicznie wybierane przez Claude
- ✅ agent-report-writer jest MANDATORY dla wszystkich agentów
- ✅ context7-docs-lookup jest MANDATORY przed implementacją
- ✅ frontend-verification jest MANDATORY przed informowaniem użytkownika o UI completion
- ✅ Każdy agent ma sekcję SKILLS INTEGRATION

---

**Autor**: Claude Code AI
**Projekt**: PPM-CC-Laravel Enterprise PIM System
**Lokalizacja Skills (GLOBALNY KATALOG CLAUDE)**: `C:\Users\kamil\.claude\skills\`
