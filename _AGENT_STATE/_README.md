# Agent State Directory

Ten folder przechowuje stan sesji agentow zgodnie z Anthropic best practices.

## Struktura

```
_AGENT_STATE/
├── _README.md                    # Ten plik
├── [agent-name]_progress.json    # Stan postępu agenta
└── session_log.json              # Opcjonalny log wszystkich sesji
```

## Format Progress File

```json
{
  "agent": "agent-name",
  "project": "PPM-CC-Laravel",
  "last_session": "2025-11-27T10:30:00",
  "session_count": 5,
  "current_task": {
    "id": "ETAP_07.2.1",
    "description": "Task description",
    "started": "2025-11-27T10:30:00",
    "status": "in_progress"
  },
  "completed_tasks": [
    {"id": "ETAP_07.1.1", "description": "...", "completed": "timestamp"},
    {"id": "ETAP_07.1.2", "description": "...", "completed": "timestamp"}
  ],
  "pending_tasks": [
    {"id": "ETAP_07.2.2", "description": "...", "priority": "high"},
    {"id": "ETAP_07.2.3", "description": "...", "priority": "medium"}
  ],
  "blockers": [],
  "next_session": {
    "priority": "Continue current task",
    "notes": "Remember to check X before Y"
  }
}
```

## Dlaczego JSON a nie Markdown?

Zgodnie z dokumentacja Anthropic:
> "Model less likely to inappropriately overwrite JSON than Markdown"

JSON jest:
- Strukturalny (latwiejszy do parsowania)
- Mniej podatny na przypadkowe nadpisanie
- Czytelny dla maszyn i ludzi
- Latwiejszy do automatycznego przetwarzania

## Workflow

### Start sesji:
```bash
cat _AGENT_STATE/[agent]_progress.json
git log --oneline -10
```

### Koniec sesji:
```bash
# Agent aktualizuje progress file
# Agent tworzy raport w _AGENT_REPORTS/
# Git commit z opisowym message
```

## Source

https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents
