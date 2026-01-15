---
name: "agent-creator"
description: "Create and update Claude Code agents based on Anthropic best practices for long-running agents."
---

# Agent Creator Skill

## Overview

Skill do tworzenia i aktualizacji agentow Claude Code zgodnie z najnowszymi best practices z dokumentacji Anthropic "Effective Harnesses for Long-Running Agents" (2025).

**Key Principles z Anthropic:**
- Agents work in **discrete sessions** without memory between sessions
- Use **progress tracking files** (JSON preferred over Markdown)
- **Verification before new work** - fix existing state before new features
- **Incremental progression** - one feature at a time
- **Clean state** - code ready to merge after each session
- **Model: default** - let framework decide optimal model

---

## Kiedy uzywac tego Skilla

Uzyj `agent-creator` gdy:

- Tworzysz **nowego agenta** dla projektu
- Aktualizujesz **istniejacego agenta** do nowych standardow
- Implementujesz **session state management** dla dlugo-dzialajacych agentow
- Potrzebujesz **progress tracking** miedzy sesjami
- Chcesz **zoptymalizowac** agenta wg Anthropic best practices

**Trigger Phrases:**
- "create new agent for..."
- "stworz agenta do..."
- "update agent..."
- "optimize agent..."
- "add progress tracking..."

---

## MANDATORY Agent Frontmatter (Claude Code 2.1.1+)

Kazdy agent MUSI miec YAML frontmatter:

```yaml
---
name: agent-name
description: Short description (1 sentence) - what this agent does
model: default
color: [red|orange|yellow|green|cyan|blue|purple|pink]
disallowedTools:        # NEW in 2.1.0 - Optional: block specific tools
  - Bash                 # Read-only agents should block Bash
  - Edit                 # Block editing for analysis-only agents
  - Write
hooks:                  # NEW in 2.1.0 - Agent-scoped hooks
  - on: PreToolUse      # Event: PreToolUse, PostToolUse, Stop
    tool: Edit          # Optional: filter by tool name
    type: prompt        # type: prompt or command
    prompt: "VALIDATION: Check X before proceeding"
  - on: PostToolUse
    tool: Bash
    type: prompt
    prompt: "VERIFICATION: Check command output for errors"
  - on: Stop
    type: prompt
    prompt: "COMPLETION: Generate report and update Plan_Projektu/"
---
```

**KRYTYCZNE (Claude Code 2.1.1):**
- `model: default` - ZAWSZE! Framework decyduje o modelu (lub opus/sonnet dla specyficznych przypadkow)
- `description` - krotki, jasny opis (1-2 zdania)
- `color` - opcjonalny, dla wizualnej identyfikacji
- `disallowedTools` - **NOWE!** Blokowanie narzedzi (read-only agents: block Edit, Write, Bash)
- `hooks` - **NOWE!** Scoped hooks dla agenta (lifecycle: PreToolUse, PostToolUse, Stop)

---

## Agent Template (Anthropic-Compliant + Claude Code 2.1.1)

```markdown
---
name: [agent-name]
description: [One sentence description]
model: default
color: [color]
disallowedTools:        # For read-only agents (ask, documentation-reader)
  - Edit
  - Write
  - Bash
hooks:                  # Agent-specific automation
  - on: PreToolUse
    tool: Edit
    type: prompt
    prompt: "[AGENT-NAME] CHECK: Verify [domain-specific validation] before editing"
  - on: PostToolUse
    tool: Bash
    type: prompt
    prompt: "[AGENT-NAME] POST: Check output for [domain-specific patterns]"
  - on: Stop
    type: prompt
    prompt: "[AGENT-NAME] COMPLETION: Did you [completion checklist]? Generate report if significant work."
---

# [Agent Name] - [Project Name]

You are [role description], specializing in [area of expertise] for the [project-name] application.

For complex decisions, **ultrathink** about [key considerations for this domain] before implementing solutions.

## SESSION STATE MANAGEMENT (MANDATORY)

**CRITICAL:** Agents work in discrete sessions without memory. You MUST:

1. **START OF SESSION - Read State:**
   - Read `_AGENT_STATE/[agent-name]_progress.json` (if exists)
   - Read recent git commits: `git log --oneline -20`
   - Check current ETAP in `Plan_Projektu/`
   - **FIX any broken state BEFORE new work!**

2. **DURING SESSION - Track Progress:**
   - Use TodoWrite for task tracking
   - One feature/task at a time
   - Commit with descriptive messages after each completion

3. **END OF SESSION - Save State:**
   - Update `_AGENT_STATE/[agent-name]_progress.json`
   - Create report in `_AGENT_REPORTS/`
   - Ensure code is mergeable (no incomplete implementations)

**Progress File Format (JSON - less prone to model overwriting):**
```json
{
  "last_updated": "2025-11-27T10:30:00",
  "current_task": "Implementing feature X",
  "completed_tasks": ["task1", "task2"],
  "pending_tasks": ["task3", "task4"],
  "blockers": [],
  "next_session_notes": "Start with verification of feature X"
}
```

## VERIFICATION FIRST WORKFLOW

**MANDATORY:** Every session starts with verification!

1. Run verification command (if defined in init.sh or similar)
2. Check that previous work is functional
3. **FIX any broken state BEFORE starting new features**
4. Only then proceed to new implementation

## [DOMAIN-SPECIFIC SECTION]

[Add domain knowledge, patterns, code examples relevant to this agent's specialty]

## Kiedy uzywac:

Use this agent when working on:
- [list of specific use cases]
- [areas of expertise]
- [problem types this agent solves]

## Narzedzia agenta:

Read, Edit, Glob, Grep, Bash, MCP

**Context7 MCP (if applicable):**
- mcp__context7__get-library-docs: For up-to-date documentation

## Skills Integration

**MANDATORY Skills:**
- **agent-report-writer** - AFTER completing work (generates _AGENT_REPORTS/)
- **context7-docs-lookup** - BEFORE implementing patterns (if applicable)

**Optional Skills:**
- [project-specific skills]
```

---

## Best Practices z Anthropic Documentation

### 1. Discrete Sessions Pattern

**Problem:** Agents have no memory between sessions
**Solution:** Progress tracking files

```
Session 1 (Initialize):
- Create init.sh for environment setup
- Generate progress.json with feature list
- First git commit

Session 2+:
1. Read progress.json and git log
2. Run init.sh (start dev server, etc.)
3. Verify previous work
4. Pick ONE incomplete feature
5. Implement + commit
6. Update progress.json
```

### 2. JSON vs Markdown for Progress

**Prefer JSON:**
```json
{
  "features": [
    {"id": 1, "description": "Feature X", "passes": false},
    {"id": 2, "description": "Feature Y", "passes": true}
  ]
}
```

**Why?** Model less likely to inappropriately overwrite JSON than Markdown.

### 3. Verification Before New Work

```
Before starting any new feature:
1. pwd → verify correct directory
2. Read progress files
3. Run init.sh → start environment
4. Run basic end-to-end test
5. FIX any issues found
6. THEN start new feature
```

### 4. One Feature at a Time

**Anti-pattern:**
- Implement multiple features
- Leave incomplete implementations
- Create merge conflicts

**Best Practice:**
- Pick ONE feature
- Implement completely
- Verify it works
- Commit with descriptive message
- Update progress
- Move to next feature

### 5. Clean State After Each Session

Code should be:
- Mergeable without conflicts
- Functional (no half-implemented features)
- Tested (at least basic verification)
- Documented (progress file updated)

---

## Agent Creation Workflow

### Step 1: Define Agent Purpose

- What domain does agent specialize in?
- What problems does it solve?
- What tools does it need?
- What skills should it integrate?

### Step 2: Create Agent File

Location: `.claude/agents/[agent-name].md`

Structure:
1. YAML frontmatter (model: default!)
2. Role description
3. Session state management (MANDATORY)
4. Domain-specific knowledge
5. When to use
6. Tools list
7. Skills integration

### Step 3: Add to Skill-Rules (if needed)

If agent should auto-activate, add to `.claude/skill-rules.json`:

```json
{
  "skills": {
    "[agent-name]": {
      "type": "workflow",
      "enforcement": "suggest",
      "priority": "high",
      "promptTriggers": {
        "keywords": ["keyword1", "keyword2"],
        "intentPatterns": ["pattern.*?match"]
      }
    }
  }
}
```

### Step 4: Test Agent

1. Launch agent manually: `Task` tool with `subagent_type`
2. Verify session state management works
3. Check progress file creation
4. Verify report generation

### Step 5: Document Agent

Add to project docs:
- Agent purpose and use cases
- Integration with other agents
- Common workflows

---

## Checklist for Every Agent

- [ ] YAML frontmatter with `model: default`
- [ ] Short, clear description (1-2 sentences)
- [ ] Session state management section (read/track/save)
- [ ] Verification-first workflow
- [ ] Domain-specific knowledge/patterns
- [ ] "When to use" section
- [ ] Tools list
- [ ] Skills integration (agent-report-writer mandatory!)
- [ ] Color for visual identification
- [ ] Progress file format defined
- [ ] Report template defined

---

## Common Anti-Patterns (AVOID!)

### 1. Hardcoded Model

```yaml
# WRONG
model: sonnet
model: opus

# CORRECT
model: default
```

### 2. No Session State

```markdown
# WRONG - No progress tracking
You are an expert...
[just domain knowledge]

# CORRECT - With state management
## SESSION STATE MANAGEMENT (MANDATORY)
1. START: Read progress.json...
```

### 3. Multiple Features per Session

```markdown
# WRONG
Implement features A, B, C in one go

# CORRECT
Pick ONE feature, complete it, commit, move to next
```

### 4. No Verification Step

```markdown
# WRONG
Start implementing new feature immediately

# CORRECT
1. Verify previous work
2. Fix any issues
3. THEN start new feature
```

### 5. Markdown Progress (risky)

```markdown
# RISKY - Model may overwrite content
## Progress
- [x] Feature 1
- [ ] Feature 2

# SAFER - JSON format
{"features": [{"id": 1, "passes": true}]}
```

---

## Resources

- [templates.md](resources/templates.md) - Full agent templates
- [anthropic-best-practices.md](resources/anthropic-best-practices.md) - Detailed Anthropic patterns

---

## Changelog

### v2.0.0 (2025-11-27)
- [NEW] Based on Anthropic "Effective Harnesses for Long-Running Agents"
- [NEW] Session state management pattern (MANDATORY)
- [NEW] Verification-first workflow
- [NEW] JSON progress tracking (preferred over Markdown)
- [CHANGED] model: default (was: sonnet)
- [ADDED] Anti-patterns section
- [ADDED] Complete workflow from Anthropic docs
