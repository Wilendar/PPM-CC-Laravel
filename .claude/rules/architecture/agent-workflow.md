# Architecture: Agent Workflow

## Core Rules
1. **ONLY ONE** agent can be `in_progress` at a time
2. **MANDATORY** reports in `_AGENT_REPORTS/` after each task
3. **ALWAYS** update TodoWrite during agent work
4. **MANDATORY** Chrome DevTools MCP verification before completion

## Agent Types and Usage

### Core Agents
| Agent | Use For |
|-------|---------|
| **architect** | Planning, architecture, Plan_Projektu/ management |
| **ask** | Technical questions, code explanations |
| **debugger** | Error fixing, bug investigation |
| **coding-style-agent** | Code review, standards compliance |
| **documentation-reader** | Documentation compliance |

### Domain Specialists
| Agent | Use For |
|-------|---------|
| **laravel-expert** | Models, services, migrations, queues |
| **livewire-specialist** | Livewire components, Alpine.js |
| **prestashop-api-expert** | PrestaShop API integration |
| **frontend-specialist** | Blade templates, CSS, UI/UX |
| **deployment-specialist** | SSH, Hostido, CI/CD |
| **refactoring-specialist** | Files >300 lines |

## Workflow Patterns

### New Feature
```
1. documentation-reader -> check requirements
2. architect -> plan implementation
3. [Domain specialist] -> implement
4. coding-style-agent -> code review
5. deployment-specialist -> deploy
```

### Bug Fix
```
1. debugger -> diagnose problem
2. [Domain specialist] -> implement fix
3. coding-style-agent -> verify
4. Test deployment
```

### Refactoring (>300 lines)
```
1. refactoring-specialist -> analysis + plan
2. refactoring-specialist -> execute refactor
3. coding-style-agent -> compliance check
4. Run tests -> verify GREEN
```

## Report Format
```markdown
# AGENT REPORT: [agent_name]
**Date**: YYYY-MM-DD HH:MM
**Task**: [description]

## COMPLETED WORK
- List with file paths

## ISSUES/BLOCKERS
- List of problems

## NEXT STEPS
- What needs to be done

## FILES
- [file.ext] - [description]
```
