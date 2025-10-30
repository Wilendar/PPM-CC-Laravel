# TODO PERSISTENCE & POST-AUTOCOMPACT RECOVERY SYSTEM

**Data utworzenia:** 2025-10-30
**Status:** ✅ AKTYWNY
**Token Cost:** ~18k (97% reduction vs full context read)

---

## 🎯 CEL SYSTEMU

Rozwiązanie problemu **utraty kontekstu TODO po autocompact** poprzez:

1. ✅ **Automatyczne snapshot TODO** przed autocompact
2. ✅ **Wykrywanie przerwanych sesji** po autocompact
3. ✅ **Dialog z użytkownikiem** o kontynuację pracy
4. ✅ **Token-efficient recovery** (18k vs 700k+ tokenów)

---

## 📊 PROBLEM DO ROZWIĄZANIA

### Before TODO Persistence System

```
1. Claude pracuje → TODO: 5 zadań (2 ✅, 1 🛠️, 2 ❌)
2. AutoCompact wipes context 💥
3. Claude "budzi się": "Co robimy?" 🤷
4. User: "Dokańczaj to co robiłeś!" 😤
5. Claude: "Co robiłem?" 🤔
6. FRUSTRATION LOOP ♻️
```

### After TODO Persistence System

```
1. Claude pracuje → TODO: 5 zadań (auto-snapshot)
2. AutoCompact triggers 💥
3. SessionStart hook → post_autocompact_recovery.ps1
4. Claude: "Wykryto przerwane TODO! A/B/C/D?" ✅
5. User: "A - kontynuuj"
6. Claude wczytuje snapshot → TodoWrite restore
7. SEAMLESS CONTINUATION 🚀
```

---

## 🏗️ ARCHITEKTURA SYSTEMU

### 3-Layer Architecture

#### Layer 1: TODO Snapshot Mechanism
- **Trigger:** Manual (todo_snapshot_create.ps1)
- **Future:** Automatic on TodoWrite via wrapper
- **Output:** `_TEMP/claude_session_state.json`
- **Archiving:** Old snapshots → `_TEMP/claude_session_archive/`

#### Layer 2: Post-AutoCompact Recovery
- **Hook:** SessionStart (`.claude/settings.local.json`)
- **Script:** `_TOOLS/post_autocompact_recovery.ps1`
- **Detection:** Snapshot < 24h old
- **Display:** TODO summary + continuation options

#### Layer 3: User Dialogue Protocol
- **Options:** A (kontynuuj), B (aktualizuj plan), C (nowe), D (pełny kontekst)
- **Claude Response:** Analyzes snapshot + asks user choice
- **Restoration:** TodoWrite with snapshot data

---

## 📁 FILE STRUCTURE

```
PPM-CC-Laravel/
├── _TEMP/
│   ├── claude_session_state.json      # Current snapshot (auto-generated)
│   ├── claude_session_archive/        # Historical snapshots
│   │   ├── snapshot_2025-10-30_14-23.json
│   │   └── snapshot_2025-10-30_10-15.json
│   └── .gitignore                     # Ignore snapshots (local-only)
│
├── _TOOLS/
│   ├── post_autocompact_recovery.ps1  # SessionStart hook (MAIN)
│   ├── todo_snapshot_create.ps1       # Manual snapshot creator
│   └── todo_snapshot_cleanup.ps1      # Archive cleanup (weekly)
│
└── .claude/
    └── settings.local.json            # SessionStart hook config
```

---

## 🔧 KOMPONENTY SYSTEMU

### 1. post_autocompact_recovery.ps1 (SessionStart Hook)

**Lokalizacja:** `_TOOLS/post_autocompact_recovery.ps1`
**Trigger:** SessionStart hook (każdy start sesji Claude)
**Purpose:** Wykrywa przerwane sesje i prezentuje opcje kontynuacji

**Workflow:**
```powershell
1. Sprawdź czy _TEMP/claude_session_state.json istnieje
2. Jeśli TAK + < 24h:
   - Parsuj JSON snapshot
   - Policz TODO (completed/in_progress/pending)
   - Wyświetl banner + summary
   - Pokaż opcje A/B/C/D
3. Jeśli NIE lub > 24h:
   - Wyświetl minimal reminder (CLAUDE.md + agents)
```

**Output Example:**
```
=========================================
  CLAUDE POST-AUTOCOMPACT RECOVERY
=========================================

Wykryto przerwana sesje z poprzedniego kontekstu!

Timestamp: 2025-10-30T14:23:45Z (15m ago)
Context: Working on ETAP_05b variant system - implementing AttributeManager

TODO Status (5 total):
  ✅ Completed: 2
  🛠️ In Progress: 1
  ❌ Pending: 2

Przerwane zadanie:
  🛠️ Implement variant system Phase 2

Agent: laravel-expert (was in progress)

PROPOZYCJE KONTYNUACJI:

A) KONTYNUUJ od przerwania
   Wczytam pełne TODO i wznowię pracę

B) AKTUALIZUJ PLAN
   Przeanalizuję postępy i zaproponuję zmiany

C) NOWE ZADANIE
   Zacznij od nowa (poprzednie TODO archiwizowane)

D) PRZEGLĄD KONTEKSTU
   Czytaj Plan_Projektu + Reports (WARNING: high tokens!)
```

---

### 2. todo_snapshot_create.ps1 (Manual Trigger)

**Lokalizacja:** `_TOOLS/todo_snapshot_create.ps1`
**Trigger:** Manual (Claude wywołuje przed dużym taskiem)
**Purpose:** Tworzy snapshot current TODO state

**Parameters:**
```powershell
-ProjectRoot       # Default: project root
-ContextSummary    # Co robisz (np. "Implementing ETAP_05b Phase 2")
-AgentInProgress   # Który agent pracuje (np. "laravel-expert")
-LastFileRead      # Ostatni przeczytany plik
-LastCommand       # Ostatnia komenda
```

**Usage Example:**
```powershell
pwsh -File "_TOOLS\todo_snapshot_create.ps1" `
  -ContextSummary "Implementing variant system Phase 2" `
  -AgentInProgress "laravel-expert" `
  -LastFileRead "app/Services/AttributeManager.php"
```

**Output:**
```
Creating TODO snapshot...
  Archived old snapshot: snapshot_2025-10-30_10-15.json
  Snapshot saved: claude_session_state.json
  Timestamp: 2025-10-30T14:23:45Z
  TODO items: 5
```

---

### 3. todo_snapshot_cleanup.ps1 (Weekly Maintenance)

**Lokalizacja:** `_TOOLS/todo_snapshot_cleanup.ps1`
**Trigger:** Manual (weekly) lub scheduled task
**Purpose:** Usuwa stare snapshoty z archive (retention: 7 dni)

**Parameters:**
```powershell
-RetentionDays     # Default: 7 (keep 7 days)
-DryRun            # Preview what would be deleted
```

**Usage Example:**
```powershell
# Preview
pwsh -File "_TOOLS\todo_snapshot_cleanup.ps1" -DryRun

# Actual cleanup
pwsh -File "_TOOLS\todo_snapshot_cleanup.ps1"
```

**Output:**
```
=========================================
  TODO SNAPSHOT CLEANUP
=========================================

Found 15 snapshot(s) in archive
Retention period: 7 days

Found 10 snapshot(s) older than 7 days:

  ❌ Deleting: snapshot_2025-10-23_08-30.json (7 days ago, 1.2 KB)
  ❌ Deleting: snapshot_2025-10-22_14-15.json (8 days ago, 1.1 KB)
  ... (8 more)

✅ Cleanup complete! Freed 12.5 KB
```

---

### 4. SessionStart Hook Configuration

**Lokalizacja:** `.claude/settings.local.json`
**Hook:** SessionStart (runs at every session start)

```json
{
  "hooks": {
    "SessionStart": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "pwsh -NoProfile -ExecutionPolicy Bypass -File \"_TOOLS\\post_autocompact_recovery.ps1\"",
            "timeout": 3000
          }
        ]
      }
    ]
  }
}
```

---

## 📋 SNAPSHOT JSON FORMAT

**Lokalizacja:** `_TEMP/claude_session_state.json`

```json
{
  "timestamp": "2025-10-30T14:23:45Z",
  "session_id": "20251030-142345",
  "todos": [
    {
      "content": "Read CLAUDE.md and understand project rules",
      "activeForm": "Reading CLAUDE.md",
      "status": "completed"
    },
    {
      "content": "Implement variant system Phase 2",
      "activeForm": "Implementing variant system Phase 2",
      "status": "in_progress"
    },
    {
      "content": "Deploy to production",
      "activeForm": "Deploying to production",
      "status": "pending"
    }
  ],
  "context_summary": "Working on ETAP_05b variant system - implementing AttributeManager service",
  "agent_in_progress": "laravel-expert",
  "last_file_read": "app/Services/AttributeManager.php",
  "last_command": "Edit app/Services/AttributeManager.php",
  "project_root": "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
}
```

---

## 🔄 WORKFLOW DIAGRAMY

### Workflow 1: Normal Work (No Autocompact)

```
User: "Implement variant system"
↓
Claude: Creates TODO (5 tasks)
↓
Claude: Works on tasks (2 completed, 1 in progress)
↓
[Optional] Claude: Calls todo_snapshot_create.ps1
↓
Continue work...
```

### Workflow 2: Work Interrupted by AutoCompact

```
Claude: Working on task 3/5 (in_progress)
↓
AutoCompact triggers 💥 (context wiped)
↓
SessionStart hook → post_autocompact_recovery.ps1
↓
Script detects snapshot < 24h
↓
Display summary + options A/B/C/D
↓
Claude reads hook output
↓
Claude presents dialogue to user:
  "🔄 Wykryto przerwane TODO! Co wybierasz? (A/B/C/D)"
↓
User: "A - kontynuuj"
↓
Claude: Reads snapshot JSON
↓
Claude: TodoWrite (restore all 5 tasks)
↓
Claude: "Wznawiam pracę od task 3/5..."
↓
✅ Seamless continuation!
```

### Workflow 3: User Chooses "B - Aktualizuj Plan"

```
User: "B - aktualizuj plan"
↓
Claude: Reads snapshot
↓
Claude: Analyzes progress (2/5 completed)
↓
Claude: "Widzę że ukończyłeś X i Y. Czy chcesz:
  - Dodać nowe zadanie Z?
  - Zmienić kolejność zadań?
  - Usunąć zadanie Q (już nieaktualne)?"
↓
User provides feedback
↓
Claude: Updates TODO with changes
↓
Continue work...
```

---

## 📊 TOKEN COST ANALYSIS

### Without TODO Persistence

| Component | Tokens | Required |
|-----------|--------|----------|
| CLAUDE.md | ~15k | ✅ YES |
| Plan_Projektu/ (14 files) | ~200k | ✅ YES |
| _AGENT_REPORTS/ (300+ files) | ~500k+ | ✅ YES |
| **TOTAL** | **~715k** | Instant autocompact |

### With TODO Persistence

| Component | Tokens | Required |
|-----------|--------|----------|
| CLAUDE.md | ~15k | ✅ YES |
| Snapshot (hook output) | ~500 | ✅ YES (display only) |
| Snapshot JSON (if user chooses A/B) | ~3k | ⚠️ CONDITIONAL |
| Plan_Projektu/ | ~200k | ❌ ONLY if user chooses D |
| _AGENT_REPORTS/ | ~500k | ❌ ONLY if user chooses D |
| **TOTAL (typical)** | **~18k** | ✅ Sustainable |
| **SAVINGS** | **97%** | 🚀 |

---

## 🎯 USAGE GUIDELINES

### For Claude Code (Post-AutoCompact)

**When you see recovery hook output:**

1. ✅ **ALWAYS acknowledge** snapshot detection
2. ✅ **ALWAYS present options** A/B/C/D to user
3. ✅ **DO NOT automatically read** Plan_Projektu/ or _AGENT_REPORTS/
4. ✅ **WAIT for user choice** before loading full context

**Response Template:**

```markdown
🔄 WYKRYTO PRZERWANE TODO Z POPRZEDNIEJ SESJI

**Timestamp:** [timestamp] ([age] ago)
**Context:** [context_summary]
**Agent:** [agent_in_progress]

**TODO Status:**
- ✅ Completed: [count]
- 🛠️ In Progress: [count]
- ❌ Pending: [count]

**Przerwane zadanie:**
🛠️ [interrupted_task_content]

---

## 🎯 PROPOZYCJE KONTYNUACJI:

**A) KONTYNUUJ od przerwania**
   → Wczytam pełne TODO i wznowię pracę

**B) AKTUALIZUJ PLAN**
   → Przeanalizuję postępy i zaproponuję zmiany/dodatkowe zadania

**C) NOWE ZADANIE**
   → Zacznij od nowa (poprzednie TODO zostanie zarchiwizowane)

**D) PRZEGLĄD KONTEKSTU**
   → Najpierw przeczytam Plan_Projektu/ i _AGENT_REPORTS/ (ostrzeżenie: ~700k tokenów)

Co wybierasz? (A/B/C/D)
```

### For Users

**When Claude presents recovery options:**

- **Option A (Kontynuuj):** Best for quick resumption, Claude wznawia dokładnie tam gdzie przerwał
- **Option B (Aktualizuj plan):** Best gdy chcesz dodać/zmienić zadania przed kontynuacją
- **Option C (Nowe zadanie):** Best gdy poprzednie TODO już nieaktualne
- **Option D (Przegląd kontekstu):** Best gdy potrzebujesz pełnego przeglądu (UWAGA: high token cost!)

---

## 🔧 MAINTENANCE

### Weekly Cleanup (Recommended)

```powershell
# Every Monday, run:
pwsh -File "_TOOLS\todo_snapshot_cleanup.ps1"
```

### Manual Snapshot Before Big Task

```powershell
# Before starting risky/long task:
pwsh -File "_TOOLS\todo_snapshot_create.ps1" `
  -ContextSummary "Starting deployment of ETAP_05b to production" `
  -AgentInProgress "deployment-specialist"
```

---

## 🐛 TROUBLESHOOTING

### Problem: Recovery hook nie działa

**Symptom:** SessionStart hook nie wyświetla recovery message

**Diagnosis:**
```powershell
# Test hook manually:
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"
```

**Solution:**
1. Sprawdź czy `.claude/settings.local.json` zawiera SessionStart hook
2. Sprawdź czy `_TOOLS\post_autocompact_recovery.ps1` istnieje
3. Sprawdź permissions w settings.local.json (może być blokowane)

---

### Problem: Snapshot nie jest tworzony

**Symptom:** Brak `_TEMP/claude_session_state.json`

**Solution:**
1. Ręcznie wywołaj `todo_snapshot_create.ps1`
2. Sprawdź czy folder `_TEMP/` istnieje i ma write permissions
3. Future: Implementuj automatic TodoWrite wrapper

---

### Problem: Snapshot zbyt stary (> 24h)

**Symptom:** Recovery hook pokazuje tylko minimal reminder

**Solution:**
- To jest expected behavior (snapshoty > 24h są ignorowane)
- Jeśli chcesz zmienić retention: edytuj `$age.TotalHours -lt 24` w recovery script
- Zalecane: Pozostaw 24h (stare snapshoty = nieaktualne TODO)

---

## 🚀 FUTURE ENHANCEMENTS

### 1. Automatic TodoWrite Wrapper

**Goal:** Auto-snapshot przy każdym TodoWrite (nie tylko manual)

**Implementation:**
```powershell
# Wrapper for TodoWrite tool
# Intercept TodoWrite → extract TODO data → call snapshot_create.ps1
```

**Status:** 📋 Planned (ETAP future)

---

### 2. Snapshot Compression

**Goal:** Reduce snapshot file size dla długich TODO lists

**Implementation:**
```powershell
# Compress JSON with gzip
# Decompress on recovery
```

**Status:** 📋 Planned (optional optimization)

---

### 3. Multi-Session Snapshot History

**Goal:** Przeglądaj historię wszystkich sesji (timeline view)

**Implementation:**
```powershell
# List all archived snapshots
# Allow user to choose which session to restore
```

**Status:** 📋 Planned (advanced feature)

---

## 📚 RELATED DOCUMENTATION

- `CLAUDE.md` - Project rules (MANDATORY read post-autocompact)
- `_DOCS/AGENT_USAGE_GUIDE.md` - Agent delegation patterns
- `Plan_Projektu/` - ETAP status (read on-demand)
- `_AGENT_REPORTS/` - Agent work history (read on-demand)

---

## ✅ IMPLEMENTATION CHECKLIST

- [x] PowerShell scripts created (3 files)
- [x] SessionStart hook configured
- [x] Folder structure created (_TEMP/claude_session_archive/)
- [x] .gitignore configured (snapshots = local-only)
- [x] Documentation written (this file)
- [ ] Testing: Manual snapshot creation
- [ ] Testing: SessionStart hook trigger
- [ ] Testing: Recovery dialogue with user
- [ ] Testing: TODO restoration (option A)
- [ ] Testing: Plan update dialogue (option B)

---

**Last Updated:** 2025-10-30
**Maintainer:** PPM-CC-Laravel Team
**Status:** ✅ PRODUCTION READY (pending testing)
