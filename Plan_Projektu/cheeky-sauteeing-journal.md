# PLAN: System MAW (Multi-Agent Workflow) - Globalny Orchestrator

## CEL
Stworzenie globalnego systemu workflow wykorzystującego wielu agentów PROJEKTOWYCH (z `.claude/agents/`) pracujących równolegle poprzez Task tool.

## WORKFLOW DO ZAIMPLEMENTOWANIA

```
ExitPlanMode → Commit Plan → Multi-Agent Tasks (Task tool) → Parallel Work →
→ Wait (same file) / Process (different files) → Verify → Deploy → Chrome Check → User Confirm → Final Commit
```

---

## KLUCZOWA ARCHITEKTURA

### Agenci Projektowi vs Subtask CLI

| Mechanizm | Użycie | Agenci |
|-----------|--------|--------|
| **Task tool + `subagent_type`** | Uruchamianie agentów | `.claude/agents/*.md` (PROJEKTOWE!) |
| **subtask CLI** (opcjonalne) | Git worktrees izolacja | Osobne workspace |

**MAW używa Task tool z `subagent_type` → agenci z `.claude/agents/` projektu!**

```python
# Parallel execution - WSZYSTKIE Task calls w JEDNEJ wiadomości!
Task(subagent_type="laravel-expert", prompt="...")      # .claude/agents/laravel-expert.md
Task(subagent_type="livewire-specialist", prompt="...")  # .claude/agents/livewire-specialist.md
Task(subagent_type="frontend-specialist", prompt="...")  # .claude/agents/frontend-specialist.md
```

---

## FAZA 1: Struktura Plików

### Globalne (C:\Users\kamil\.claude\)

```
skills/
├── maw/
│   └── SKILL.md                    # Orchestrator workflow
└── maw-init/
    └── SKILL.md                    # Komenda instalacji

hooks/
├── maw-post-exit-plan.py           # Trigger po ExitPlanMode
└── maw-agent-tracker.py            # Tracking plików agentów (SubagentStart/Stop)

rules/
└── maw/
    └── workflow.md                 # Dokumentacja workflow
```

### State Directory (C:\Users\kamil\.maw\)

```
config.json                         # Konfiguracja MAW
state.json                          # Runtime state (active tasks, file locks)
history/                            # Historia workflow
```

---

## FAZA 2: Implementacja Komponentów

### 2.1 Skill `/maw-init` - Instalacja (GLOBALNY)

**Ścieżka:** `C:\Users\kamil\.claude\skills\maw-init\SKILL.md`

**Funkcje:**
- Utworzenie folderów: `~/.maw/`
- Utworzenie `config.json` z domyślną konfiguracją
- Aktualizacja `~/.claude/settings.json` (permissions, hooks)
- Sprawdzenie czy projekt ma `.claude/agents/` (wymagane!)
- Wyświetlenie dostępnych agentów projektowych

### 2.2 Skill `/maw` - Główny Orchestrator (GLOBALNY)

**Ścieżka:** `C:\Users\kamil\.claude\skills\maw\SKILL.md`

**KLUCZOWE:** Używa **Task tool** z parametrem `subagent_type` aby uruchamiać **AGENTÓW PROJEKTOWYCH** z `.claude/agents/`!

**Workflow (12 faz):**

1. **Analiza planu** - parsuj Plan_Projektu/, wyciągnij taski
2. **Wykrycie agentów projektowych** - skanuj `.claude/agents/*.md`
3. **Mapowanie plików→agentów** - wg wzorców lub explicit assignment
4. **Wykrywanie konfliktów** - znajdź taski edytujące te same pliki
5. **Commit planu** - `git commit -m "MAW: Plan committed"`
6. **Tworzenie tasków równolegle** - Task tool z `subagent_type`:
   ```
   // W JEDNEJ wiadomości = PARALLEL EXECUTION!
   Task(subagent_type="laravel-expert", prompt="Task 1: ...")
   Task(subagent_type="livewire-specialist", prompt="Task 2: ...")
   Task(subagent_type="laravel-expert", prompt="Task 3: ...")  // Ten sam agent!
   ```
7. **Monitorowanie** - SubagentStart/SubagentStop hooks tracking
8. **Strategia odpowiedzi:**
   - **Różne pliki** → przetwarzaj odpowiedzi natychmiast
   - **TEN SAM PLIK** → CZEKAJ na WSZYSTKICH agentów!
9. **Weryfikacja** - `php artisan test`, `npm run build`
10. **Deploy** - `Skill(hostido-deployment)` (opcjonalnie)
11. **Chrome verification** - `mcp__chrome-bridge__*` (dla web)
12. **Final commit** - po potwierdzeniu użytkownika

### 2.3 Hook `maw-post-exit-plan.py`

**Ścieżka:** `C:\Users\kamil\.claude\hooks\maw-post-exit-plan.py`

**Trigger:** PreToolUse → ExitPlanMode

**Akcja:** Wyświetla `<system-reminder>` z instrukcją uruchomienia `Skill(maw)`

### 2.4 Hook `maw-agent-tracker.py`

**Ścieżka:** `C:\Users\kamil\.claude\hooks\maw-agent-tracker.py`

**Triggery:**
- **SubagentStart** → rejestruj agenta w `state.json`, inicjuj file tracking
- **PreToolUse (Edit/Write)** → sprawdź/ustaw lock na pliku
- **SubagentStop** → zwolnij locki, oznacz jako ready to process

**State tracking w `~/.maw/state.json`:**
```json
{
  "workflow_id": "maw-2026-02-04-001",
  "active_tasks": {
    "agent-abc123": {
      "agent_type": "laravel-expert",
      "locked_files": ["app/Services/ProductService.php"],
      "status": "working"
    },
    "agent-def456": {
      "agent_type": "livewire-specialist",
      "locked_files": ["app/Http/Livewire/ProductForm.php"],
      "status": "working"
    }
  },
  "file_locks": {
    "app/Services/ProductService.php": "agent-abc123",
    "app/Http/Livewire/ProductForm.php": "agent-def456"
  },
  "completed_tasks": [],
  "pending_same_file": {}
}
```

**Logika conflict detection:**
```python
def on_edit_attempt(agent_id, file_path):
    if file_path in state["file_locks"]:
        other_agent = state["file_locks"][file_path]
        if other_agent != agent_id:
            # CONFLICT! Mark as pending same file
            state["pending_same_file"][file_path].append(agent_id)
            return "WAIT - file locked by another agent"
    # Lock file for this agent
    state["file_locks"][file_path] = agent_id
    return "OK"
```

### 2.5 Rule `workflow.md`

**Ścieżka:** `C:\Users\kamil\.claude\rules\maw\workflow.md`

**Zawartość:**
- Opis workflow krok po kroku
- Mapowanie plików do agentów (domyślne)
- Zasady rozwiązywania konfliktów plików
- Przykłady parallel Task calls
- Checklista przed/po workflow

---

## FAZA 3: Konfiguracja

### 3.1 MAW Config (`~/.maw/config.json`)

```json
{
  "version": "1.0.0",
  "enabled": true,
  "max_parallel_agents": 10,
  "allow_same_agent_multiple": true,

  "default_agent_mapping": {
    "app/Services/": "laravel-expert",
    "app/Http/Livewire/": "livewire-specialist",
    "app/Http/Controllers/": "laravel-expert",
    "app/Jobs/": "laravel-expert",
    "app/Models/": "laravel-expert",
    "database/migrations/": "laravel-expert",
    "resources/views/.*\\.blade\\.php": "frontend-specialist",
    "resources/css/": "frontend-specialist",
    "resources/js/": "frontend-specialist",
    "tests/": "laravel-expert",
    "_TOOLS/": "laravel-expert"
  },

  "conflict_resolution": {
    "strategy": "wait_for_all",
    "max_wait_seconds": 300,
    "notify_on_conflict": true
  },

  "verification": {
    "run_tests": true,
    "test_command": "php artisan test",
    "npm_build": true,
    "chrome_check": true
  },

  "deployment": {
    "enabled": true,
    "skill": "hostido-deployment",
    "auto_clear_cache": true
  }
}
```

### 3.2 Aktualizacja `settings.json`

**Dodać do permissions:**
```json
"Skill(maw)",
"Skill(maw-init)"
```

**Zaktualizować/Dodać hooks:**
```json
{
  "PreToolUse": [
    {
      "matcher": "ExitPlanMode",
      "hooks": [{
        "type": "command",
        "command": "python \"C:\\Users\\kamil\\.claude\\hooks\\maw-post-exit-plan.py\"",
        "timeout": 5000
      }]
    }
  ],
  "SubagentStart": [{
    "hooks": [{
      "type": "command",
      "command": "python \"C:\\Users\\kamil\\.claude\\hooks\\maw-agent-tracker.py\" start",
      "timeout": 3000
    }]
  }],
  "SubagentStop": [{
    "hooks": [{
      "type": "command",
      "command": "python \"C:\\Users\\kamil\\.claude\\hooks\\maw-agent-tracker.py\" stop",
      "timeout": 3000
    }]
  }]
}
```

---

## FAZA 4: Aktualizacja CLAUDE.md

**Dodać na początku CLAUDE.md (globalny i projektowy):**

```markdown
## KRYTYCZNA ZASADA WORKFLOW: MAW (Multi-Agent Workflow)

**OBOWIĄZKOWY WORKFLOW PO ZATWIERDZENIU PLANU:**

1. **ExitPlanMode** → Plan zatwierdzony
2. **Commit and Push** → `git commit -m "MAW: Plan committed"`
3. **Skill(maw)** → Uruchom orchestrator
4. **Multi-Agent Tasks** → Task tool z subagent_type (agenci z .claude/agents/)
5. **Parallel Execution** → WSZYSTKIE Task calls w JEDNEJ wiadomości!
6. **Odpowiedzi agentów:**
   - **Różne pliki** → przetwarzaj natychmiast
   - **TEN SAM PLIK** → CZEKAJ na WSZYSTKICH agentów!
7. **Weryfikacja** → testy, build
8. **Deploy** → produkcja (web projects)
9. **Chrome Check** → weryfikacja wizualna
10. **User Confirm** → "Czy wszystko OK?"
11. **Final Commit** → `git push` i next task

**ZASADY:**
- Do 10 agentów równolegle (w jednej wiadomości!)
- Ten sam agent może być użyty wielokrotnie (np. 3x laravel-expert)
- Agenci to PROJEKTOWE pliki z `.claude/agents/*.md`
- NIGDY nie przetwarzaj odpowiedzi gdy inny agent edytuje ten sam plik
- ZAWSZE czekaj na Chrome verification przed pytaniem usera

**PRZYKŁAD PARALLEL EXECUTION:**
```
// W JEDNEJ wiadomości Claude wysyła:
Task(subagent_type="laravel-expert", prompt="Fix ProductService...")
Task(subagent_type="livewire-specialist", prompt="Update ProductForm...")
Task(subagent_type="frontend-specialist", prompt="Fix CSS...")
// = 3 agenci startują RÓWNOLEGLE!
```
```

---

## FAZA 5: Pliki do Utworzenia/Modyfikacji

### Nowe pliki:
| Plik | Ścieżka | Opis |
|------|---------|------|
| SKILL.md | `~/.claude/skills/maw/SKILL.md` | Główny orchestrator |
| SKILL.md | `~/.claude/skills/maw-init/SKILL.md` | Instalacja |
| maw-post-exit-plan.py | `~/.claude/hooks/maw-post-exit-plan.py` | Hook po ExitPlanMode |
| maw-agent-tracker.py | `~/.claude/hooks/maw-agent-tracker.py` | File locking |
| workflow.md | `~/.claude/rules/maw/workflow.md` | Dokumentacja |
| config.json | `~/.maw/config.json` | Konfiguracja |

### Modyfikacje:
| Plik | Ścieżka | Zmiany |
|------|---------|--------|
| settings.json | `~/.claude/settings.json` | Permissions, hooks |
| CLAUDE.md | `~/.claude/CLAUDE.md` | Zasada workflow |

---

## WERYFIKACJA

### Po implementacji:
1. Uruchom `/maw-init` w projekcie z `.claude/agents/`
2. Sprawdź czy `~/.maw/config.json` utworzony
3. Sprawdź czy hooks zarejestrowane w settings.json
4. Wejdź w plan mode, zatwierdź plan
5. Zweryfikuj czy MAW automatycznie się uruchamia
6. Sprawdź czy Task tool uruchamia agentów projektowych
7. Zweryfikuj file locking (edycja tego samego pliku)
8. Sprawdź czy odpowiedzi są przetwarzane poprawnie
9. Zweryfikuj Chrome verification
10. Test user confirmation flow

### Komendy testowe:
```bash
# Sprawdź MAW state
cat ~/.maw/state.json

# Sprawdź dostępnych agentów projektowych
ls .claude/agents/

# Sprawdź logi MAW
cat ~/.maw/history/$(date +%Y-%m-%d)*.json
```

---

## UWAGI

1. **Task tool + subagent_type** = agenci projektowi z `.claude/agents/`
2. **Parallel execution** = wszystkie Task calls w JEDNEJ wiadomości
3. **File locking** = zapobiega konfliktom gdy wielu agentów edytuje ten sam plik
4. **Python dla hooks** - kompatybilność z Windows + proste debugowanie
5. **JSON dla state** - łatwy dostęp z hooks i skills
6. **Globalny** - raz zainstalowany działa we wszystkich projektach
7. **Wymaga `.claude/agents/`** - projekt musi mieć zdefiniowanych agentów!

---

## KOLEJNOŚĆ IMPLEMENTACJI

1. ✅ `~/.maw/config.json` - struktura konfiguracji
2. ✅ `~/.claude/hooks/maw-post-exit-plan.py` - trigger po ExitPlanMode
3. ✅ `~/.claude/hooks/maw-agent-tracker.py` - file locking + SubagentStart/Stop
4. ✅ `~/.claude/skills/maw/SKILL.md` - orchestrator (Task tool integration)
5. ✅ `~/.claude/skills/maw-init/SKILL.md` - instalacja + agent detection
6. ✅ `~/.claude/rules/maw/workflow.md` - dokumentacja
7. ✅ Aktualizacja `~/.claude/settings.json` - permissions/hooks
8. ✅ Aktualizacja `~/.claude/CLAUDE.md` - zasada workflow
9. ✅ Test end-to-end z projektowymi agentami

## STATUS: IMPLEMENTACJA UKOŃCZONA (2026-02-04)
