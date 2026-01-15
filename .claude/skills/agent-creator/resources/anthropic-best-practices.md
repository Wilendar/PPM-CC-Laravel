# Anthropic Best Practices for Long-Running Agents

Source: https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents

## Core Principle

> "Agents work in discrete sessions without memory between sessions"

This is the fundamental constraint that shapes all agent design decisions.

---

## 1. Session State Management

### The Problem

Claude has no memory between sessions. Each session starts fresh. Without explicit state management, agents:
- Lose context from previous work
- May redo completed tasks
- Can't track progress across sessions
- May leave code in broken state

### The Solution: Progress Tracking Files

**Initialization Session:**
```
1. Create init.sh for environment setup
2. Generate feature_list.json with task tracking
3. Make first git commit
4. Agent ready for iterative sessions
```

**Each Subsequent Session:**
```
1. Read progress files (feature_list.json, git log)
2. Run init.sh (start dev server, etc.)
3. Verify previous work still functional
4. Pick ONE incomplete feature
5. Implement + verify
6. Commit with descriptive message
7. Update progress files
8. Exit session in clean state
```

### Progress File Format

**Prefer JSON over Markdown:**

```json
{
  "project": "Project Name",
  "current_session": 5,
  "last_updated": "2025-11-27T10:30:00",
  "features": [
    {
      "id": 1,
      "description": "User authentication",
      "passes": true,
      "completed_session": 2
    },
    {
      "id": 2,
      "description": "Product listing",
      "passes": false,
      "current_focus": true
    }
  ],
  "notes": "Remember to check X before Y"
}
```

**Why JSON?**
- Less prone to model overwriting content inappropriately
- Structured data easier to parse
- Clear boolean status (passes: true/false)
- Session tracking built-in

---

## 2. Verification Before New Work

### The Pattern

Every session MUST start with verification:

```
Session Start:
1. pwd → Verify correct directory
2. Read progress files
3. Run init.sh → Start environment
4. Run basic end-to-end test
5. If test fails → FIX FIRST
6. Only if test passes → Start new feature
```

### Why This Matters

Without verification:
- Previous session may have left broken state
- New features built on broken foundation
- Debugging becomes exponentially harder
- Time wasted on cascading failures

With verification:
- Catch issues early
- Fix before they compound
- Maintain clean codebase
- Each session starts from working state

---

## 3. One Feature at a Time

### Anti-Pattern: Multiple Features

```
Session:
- Implement feature A (partial)
- Switch to feature B (partial)
- Start feature C
- Session ends with 3 incomplete features
```

**Problems:**
- Code not mergeable
- Half-implementations everywhere
- No clear progress tracking
- Next session doesn't know what's done

### Best Practice: Single Feature Focus

```
Session:
1. Pick ONE incomplete feature from list
2. Implement completely
3. Verify it works
4. Commit with descriptive message
5. Update feature_list.json (passes: true)
6. If time remains, pick next feature
7. If no time, exit cleanly
```

**Benefits:**
- Always mergeable code
- Clear progress tracking
- Easy to resume
- No half-implementations

---

## 4. Clean State After Each Session

### What "Clean State" Means

- All code compiles/runs
- No syntax errors
- No half-implemented features
- Tests that existed before still pass
- New feature tested and working
- Progress files updated
- Descriptive commit made

### Session Exit Checklist

```markdown
Before ending session:
- [ ] All files saved
- [ ] Code compiles without errors
- [ ] New feature works (tested)
- [ ] Existing functionality intact
- [ ] Git commit made with clear message
- [ ] Progress file updated
- [ ] No TODO comments for "later"
- [ ] Report generated (if required)
```

---

## 5. Init Script Pattern

### Purpose

Automated environment setup that runs at session start:

```bash
#!/bin/bash
# init.sh

# Environment setup
export APP_ENV=development

# Start services
npm run dev &
php artisan serve &

# Wait for services
sleep 3

# Verify environment
curl -s http://localhost:8000/health || exit 1

echo "Environment ready"
```

### Usage in Sessions

```
Session Start:
1. cd /project/directory
2. ./init.sh
3. [wait for services]
4. Proceed with development
```

---

## 6. Git as Checkpoint System

### Use Git for State Recovery

```bash
# Before starting risky operation
git stash push -m "checkpoint before feature X"

# After successful implementation
git commit -m "Feature X: description"

# If something goes wrong
git stash pop  # or git reset --hard HEAD
```

### Descriptive Commit Messages

```
Good:
"Add user authentication with JWT tokens
- Implement login/logout endpoints
- Add token refresh mechanism
- Include rate limiting"

Bad:
"updates"
"wip"
"fix"
```

---

## 7. Error Recovery Patterns

### When Verification Fails

```
1. Identify what broke
2. Check git log for recent changes
3. Consider rollback: git reset --hard [commit]
4. If no rollback possible, fix issue first
5. Document the issue in progress file
6. Only then proceed to new work
```

### When Feature Implementation Fails

```
1. Assess current state
2. Can feature be completed? → Continue
3. Cannot complete? → Rollback to last good commit
4. Update progress file: passes: false, notes: "reason"
5. Document learnings
6. Exit session cleanly
```

---

## 8. Model Selection: Use Default

### Why `model: default`

- Framework/harness decides optimal model
- Allows flexibility without agent changes
- Future model improvements automatic
- Consistent across all agents
- Cost optimization at harness level

### Frontmatter

```yaml
---
name: agent-name
description: Agent description
model: default  # ALWAYS default
color: blue
---
```

---

## 9. Communication with Users

### What Agents Should Communicate

- Current progress (% complete, tasks done)
- What's being worked on now
- Any blockers or issues found
- Request for clarification if needed
- Summary at session end

### What to Avoid

- Technical jargon without explanation
- Assuming user knows internal state
- Silent failures
- Proceeding when uncertain

---

## 10. Long-Running Task Patterns

### For Tasks Spanning Multiple Sessions

```json
{
  "multi_session_task": {
    "name": "Major Refactoring",
    "started": "2025-11-20",
    "estimated_sessions": 5,
    "current_session": 3,
    "phases": [
      {"name": "Analysis", "status": "completed"},
      {"name": "Core changes", "status": "completed"},
      {"name": "Migration", "status": "in_progress"},
      {"name": "Testing", "status": "pending"},
      {"name": "Cleanup", "status": "pending"}
    ]
  }
}
```

### Session Boundaries

Each session should:
- Start from a known good state
- Complete one logical unit of work
- End in a known good state
- Enable next session to continue seamlessly

---

## Summary: The Effective Agent Loop

```
┌─────────────────────────────────────────┐
│           SESSION START                 │
│  1. Read progress files                 │
│  2. Run init.sh                         │
│  3. Verify previous work                │
│  4. Fix any issues found                │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         FEATURE IMPLEMENTATION          │
│  1. Pick ONE incomplete feature         │
│  2. Implement completely                │
│  3. Verify it works                     │
│  4. Commit with descriptive message     │
│  5. Update progress files               │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│           SESSION END                   │
│  1. Ensure clean state                  │
│  2. All code mergeable                  │
│  3. Progress files updated              │
│  4. Report generated (if required)      │
└─────────────────────────────────────────┘
```

This loop repeats for each session until all features are complete.
