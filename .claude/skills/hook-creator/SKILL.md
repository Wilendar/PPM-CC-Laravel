---
name: "hook-creator"
description: "Create Claude Code hooks with best practices and standards."
---

# Hook Creator Skill

## 🎯 Overview

Hook Creator to skill odpowiedzialny za tworzenie Claude Code hooks zgodnie z best practices i standards. Hooks to automatyzacja uruchamiana przez Claude Code w odpowiedzi na events (SessionStart, PreCompact, PostToolUse, PreToolUse).

**Key Features:**
- **UTF-8 encoding support** dla Windows (emoji, polskie znaki)
- **Dual output system** (stdout dla Claude, stderr dla użytkownika)
- **Error handling** z proper exit codes
- **4 templates** dla różnych typów hooks
- **Best practices checklist**

---

## 🚀 Kiedy używać tego Skilla

Użyj `hook-creator` gdy:

- ✅ User prosi o stworzenie **nowego hooka**
- ✅ Potrzebujesz **automation przy starcie sesji** (SessionStart)
- ✅ Chcesz **save context przed compaction** (PreCompact)
- ✅ Musisz **enforce rules przed tool execution** (PreToolUse)
- ✅ Chcesz **run action po tool execution** (PostToolUse)
- ✅ **NEW 2.1.0:** Automation przy **starcie subagenta** (SubagentStart)
- ✅ **NEW 2.1.0:** Automation przy **zakonczeniu subagenta** (SubagentStop)
- ✅ Naprawiasz **istniejący hook** który nie działa
- ✅ Dodajesz **custom functionality** do Claude Code workflow

**Hook Events (Claude Code 2.1.1):**
| Event | Kiedy | Parametry |
|-------|-------|-----------|
| UserPromptSubmit | Przed przetworzeniem promptu | userPrompt |
| PreToolUse | Przed wywolaniem narzedzia | tool_name, tool_input |
| PostToolUse | Po wywolaniu narzedzia | tool_name, tool_output |
| Stop | Zakonczenie main conversation | - |
| PreCompact | Przed kompresja kontekstu | - |
| SubagentStart | **NEW!** Start subagenta | agent_name, agent_type, session_id |
| SubagentStop | Zakonczenie subagenta | agent_id, agent_transcript_path |

**Trigger Phrases:**
- "create a hook for..."
- "generate hook that..."
- "make a SessionStart/PreCompact/PostToolUse hook..."
- "create SubagentStart/SubagentStop hook..."
- "build custom hook..."
- "stwórz hook który..."

---

## 📋 MANDATORY Elements w Każdym Hooku

### 1. YAML Frontmatter (dla SKILL.md, nie dla .py hooks)

```yaml
---
name: hook-name
description: Short description
version: 1.0.0
created: 2025-11-05
---
```

### 2. UTF-8 Encoding Fix (Windows Compatibility)

**⚠️ KRYTYCZNE:** ZAWSZE dodaj na początku każdego hook.py:

```python
# -*- coding: utf-8 -*-
import sys

# Fix Windows UTF-8 encoding for emoji
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')
```

### 3. Dual Output System

**stdout** → Claude widzi
**stderr** → User widzi w CLI

```python
# Output for Claude (stdout via print)
print("✅ Hook executed successfully")
print("Information for Claude: [details]")

# Output for User (stderr - visible in terminal)
sys.stderr.write("\n" + "="*70 + "\n")
sys.stderr.write("✅ HOOK NAME EXECUTED\n")
sys.stderr.write("="*70 + "\n")
sys.stderr.write("📋 User-friendly summary\n")
sys.stderr.write("="*70 + "\n\n")
sys.stderr.flush()  # IMPORTANT!
```

### 4. Error Handling Pattern

```python
def main():
    try:
        # Hook logic here
        return 0  # Success
    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR in [hook-name]: {str(e)}\n")
        import traceback
        traceback.print_exc()
        return 1  # Failure

if __name__ == "__main__":
    sys.exit(main())
```

### 5. Optional: Color Support

```python
class Color:
    CYAN = '\033[96m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    RESET = '\033[0m'
    BOLD = '\033[1m'

def print_colored(text, color):
    print(f"{color}{text}{Color.RESET}")
```

---

## 📖 Hook Templates

### Template A: SessionStart Hook

**Use Case:** Run gdy Claude Code starts (każda sesja)

**Full Template:**

```python
# -*- coding: utf-8 -*-
"""
[Project Name]: Session Start Hook
Description: [What this hook does]
"""

import sys
from datetime import datetime
from pathlib import Path

# UTF-8 fix
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def main():
    try:
        # Your hook logic here
        # Example: Load project rules, check environment, etc.

        # Output for Claude (stdout)
        print("\n" + "="*70)
        print("SESSION START - [Project Name]")
        print("="*70)
        print("[Information for Claude...]")
        print("="*70 + "\n")

        # CLI Output for User (stderr)
        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ SESSION START HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        sys.stderr.write(f"📋 [Summary for user]\n")
        sys.stderr.write(f"⏰ Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR: {str(e)}\n")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    sys.exit(main())
```

**Configuration (.claude/settings.local.json):**

```json
{
  "hooks": {
    "SessionStart": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/your_session_start_hook.py",
            "timeout": 5000
          }
        ]
      }
    ]
  },
  "permissions": {
    "allow": [
      "Bash(python .claude/hooks/your_session_start_hook.py:*)"
    ]
  }
}
```

---

### Template B: PreCompact Hook

**Use Case:** Save context PRZED compaction (memory optimization)

**Full Template:**

```python
# -*- coding: utf-8 -*-
"""
[Project Name]: Pre-Compact Snapshot Hook
Description: Save critical context before compaction
"""

import json
import sys
from datetime import datetime
from pathlib import Path

# UTF-8 fix
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def create_snapshot():
    """Create context snapshot"""
    snapshot_dir = Path("_TEMP/compact_snapshots")
    snapshot_dir.mkdir(parents=True, exist_ok=True)

    timestamp = datetime.now().isoformat().replace(':', '-')
    snapshot_data = {
        "timestamp": datetime.now().isoformat(),
        "project": "[Your Project Name]",
        "context": {
            # Add your critical context here
            "rules": ["rule1", "rule2"],
            "workflow": ["step1", "step2"]
        }
    }

    snapshot_file = snapshot_dir / f"snapshot_{timestamp}.json"
    with open(snapshot_file, 'w', encoding='utf-8') as f:
        json.dump(snapshot_data, f, indent=2, ensure_ascii=False)

    # Also save as latest
    latest_file = snapshot_dir / "latest_snapshot.json"
    with open(latest_file, 'w', encoding='utf-8') as f:
        json.dump(snapshot_data, f, indent=2, ensure_ascii=False)

    return snapshot_file, snapshot_data

def main():
    try:
        snapshot_file, data = create_snapshot()

        # Output for Claude
        print("\n" + "="*60)
        print("📦 PRE-COMPACT SNAPSHOT")
        print("="*60)
        print(f"✅ Snapshot saved: {snapshot_file.name}")
        print("="*60 + "\n")

        # CLI Output for User
        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ PRE-COMPACT HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        sys.stderr.write(f"📦 Snapshot created: {snapshot_file.name}\n")
        sys.stderr.write(f"💾 Location: _TEMP/compact_snapshots/\n")
        sys.stderr.write("✅ Ready for compaction\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR: {str(e)}\n")
        return 1

if __name__ == "__main__":
    sys.exit(main())
```

**Configuration:**

```json
{
  "hooks": {
    "PreCompact": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/pre_compact_hook.py",
            "timeout": 5000
          }
        ]
      }
    ]
  }
}
```

---

### Template C: PostToolUse Hook

**Use Case:** Run AFTER specific tool execution (logging, validation)

**Full Template:**

```python
# -*- coding: utf-8 -*-
"""
[Project Name]: Post Tool Use Hook
Description: Run after [specific tool] execution
"""

import sys
import json

# UTF-8 fix
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def main():
    try:
        # Read tool execution data from stdin (if provided by Claude)
        stdin_data = sys.stdin.read()
        if stdin_data:
            tool_data = json.loads(stdin_data)
            # Process tool data...

        # Output for Claude
        print("\n✅ Post-tool hook executed successfully\n")

        # CLI Output for User
        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ POST-TOOL HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        sys.stderr.write("📋 [Tool action completed]\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR: {str(e)}\n")
        return 1

if __name__ == "__main__":
    sys.exit(main())
```

**Configuration:**

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit.*\\.php$",
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/post_tool_hook.py",
            "timeout": 3000
          }
        ]
      }
    ]
  }
}
```

---

### Template D: PreToolUse Hook

**Use Case:** ENFORCE rules PRZED tool execution (validation, reminders)

**Full Template:**

```python
# -*- coding: utf-8 -*-
"""
[Project Name]: Pre Tool Use Hook
Description: Enforce [rule] before [tool] execution
"""

import sys

# UTF-8 fix
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def main():
    try:
        # Reminder/warning for Claude
        print("\n⚠️  REMINDER: [Your rule/requirement]")
        print("Make sure to: [specific requirement]\n")

        # CLI Output for User (brief)
        sys.stderr.write("⚠️  Pre-tool hook: [brief reminder]\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR: {str(e)}\n")
        return 1

if __name__ == "__main__":
    sys.exit(main())
```

**Configuration:**

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Write.*\\.(php|js)$",
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/pre_tool_hook.py",
            "timeout": 2000
          }
        ]
      }
    ]
  }
}
```

---

## 🔧 Hook Creation Workflow

### Step 1: Understand Requirements

Ask user:
- **What trigger?** (SessionStart, PreCompact, PostToolUse, PreToolUse)
- **What should hook do?** (Load rules, save context, enforce validation, etc.)
- **Project-specific or global?** (`.claude/hooks/` vs global location)

### Step 2: Select Template

Choose from 4 templates above based on trigger type.

### Step 3: Customize Logic

Add project-specific implementation:
- Load project rules/config
- Check environment
- Validate state
- Enforce requirements
- Save snapshots
- Log actions

### Step 4: Implement Dual Output

```python
# Claude sees this (stdout)
print("Detailed information for AI...")

# User sees this (stderr)
sys.stderr.write("Brief summary for human\n")
sys.stderr.flush()
```

### Step 5: Test Hook

```bash
# Test manually
python .claude/hooks/your_hook.py

# Check output encoding
python .claude/hooks/your_hook.py 2>&1 | tee test_output.txt
```

### Step 6: Configure Settings

Add to `.claude/settings.local.json`:
1. Hook configuration (trigger, command, timeout)
2. Permission (if needed for Bash execution)

### Step 7: Test in Claude Code

- Restart Claude Code
- Trigger hook event (start session, use tool, etc.)
- Verify stdout (Claude) and stderr (CLI) output
- Check for errors

### Step 8: Document Hook

Create documentation:
- Hook purpose
- Configuration
- Expected behavior
- Troubleshooting

---

## ✅ Best Practices Checklist

When creating ANY hook, ensure:

- [ ] **UTF-8 encoding fix** for Windows (top of file)
- [ ] **Dual output system** (stdout for Claude, stderr for user)
- [ ] **Try-except error handling** with exit codes
- [ ] **Exit code 0** on success, **1** on failure
- [ ] **Timeout configured** (minimum 3000ms, complex hooks 5000ms+)
- [ ] **Permission added** to settings.local.json (if needed)
- [ ] **User-friendly CLI messages** (emoji, clear text, 70-char width)
- [ ] **Tested manually** before committing
- [ ] **Documented** in project docs or README

---

## 📚 Common Patterns

### Load Project Configuration

```python
import json
from pathlib import Path

def load_project_config():
    config_path = Path("project_config.json")
    if config_path.exists():
        with open(config_path, 'r', encoding='utf-8') as f:
            return json.load(f)
    return {}
```

### Read CLAUDE.md Rules

```python
def load_claude_rules():
    claude_md = Path("CLAUDE.md")
    if claude_md.exists():
        with open(claude_md, 'r', encoding='utf-8') as f:
            content = f.read()
            # Parse critical sections...
            return parsed_rules
    return []
```

### Check Git Status

```python
import subprocess

def check_git_status():
    try:
        result = subprocess.run(
            ['git', 'status', '--porcelain'],
            capture_output=True,
            text=True,
            timeout=5
        )
        return result.stdout
    except Exception:
        return None
```

---

## 🎯 Complete Example: Context7 Check Hook

**User Request:** "Create hook that checks if Context7 MCP is available before Write tool"

**Implementation:**

```python
# -*- coding: utf-8 -*-
"""
Context7 MCP Availability Check Hook
Warns before Write tool if Context7 not configured
"""

import sys
import subprocess

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def check_context7_mcp():
    """Check if Context7 MCP is available"""
    try:
        # Try to access Context7 tools
        result = subprocess.run(
            ['mcp', 'list-tools'],
            capture_output=True,
            text=True,
            timeout=2
        )
        return 'context7' in result.stdout.lower()
    except:
        return False

def main():
    try:
        has_context7 = check_context7_mcp()

        if not has_context7:
            # Warn Claude
            print("\n⚠️  WARNING: Context7 MCP not detected")
            print("Consider using Context7 docs before implementation")
            print("Use mcp__context7__resolve-library-id and mcp__context7__get-library-docs\n")

            # Warn User
            sys.stderr.write("⚠️  Context7 MCP not available - consider docs lookup\n")
            sys.stderr.flush()

        return 0

    except Exception as e:
        sys.stderr.write(f"\n❌ ERROR: {str(e)}\n")
        return 1

if __name__ == "__main__":
    sys.exit(main())
```

**Configuration:**

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Write.*\\.(php|js|ts)$",
        "hooks": [
          {
            "type": "command",
            "command": "python .claude/hooks/context7_check.py",
            "timeout": 3000
          }
        ]
      }
    ]
  },
  "permissions": {
    "allow": [
      "Bash(python .claude/hooks/context7_check.py:*)"
    ]
  }
}
```

---

## 🔍 Troubleshooting

### Problem: Hook doesn't execute

**Diagnoza:**
1. Check `.claude/settings.local.json` syntax (valid JSON?)
2. Verify permission is added (if using Bash)
3. Check Python path: `python --version`
4. Test hook manually: `python .claude/hooks/your_hook.py`

**Rozwiązanie:**
```bash
# Validate JSON
python -m json.tool .claude/settings.local.json

# Test hook
python .claude/hooks/your_hook.py
```

### Problem: No output in CLI

**Diagnoza:**
- Not using `sys.stderr.write()` for user output
- Missing `sys.stderr.flush()`
- Terminal encoding issues

**Rozwiązanie:**
```python
# ALWAYS use stderr for user output
sys.stderr.write("Message for user\n")
sys.stderr.flush()  # DON'T FORGET!
```

### Problem: Unicode/Emoji errors

**Diagnoza:**
- Missing UTF-8 encoding fix
- Python < 3.7
- Windows terminal encoding

**Rozwiązanie:**
```python
# Add at TOP of every hook
# -*- coding: utf-8 -*-
import sys

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')
```

### Problem: Hook times out

**Diagnoza:**
- Timeout too short
- Hook takes too long
- Infinite loop

**Rozwiązanie:**
```json
// Increase timeout
{
  "hooks": {
    "SessionStart": [{
      "hooks": [{
        "timeout": 10000  // 10 seconds
      }]
    }]
  }
}
```

---

## 📖 Files Generated by Hook Creator

When creating a hook, skill generates:

1. **`[hook_name].py`** - Main hook script
2. **`README_[hook_name].md`** - Hook documentation (optional)
3. **Configuration snippet** for `.claude/settings.local.json`

---

## 🎓 When to Use This Skill

Use `hook-creator` whenever user asks to:
- Create ANY hook
- Add custom automation
- Enforce rules/standards
- Run actions at SessionStart/PreCompact/PostToolUse/PreToolUse
- Fix broken hooks
- Add functionality to Claude Code workflow

**Always follow templates and best practices!**

---

## 📊 System Uczenia Się (Automatyczny)

### Tracking Informacji
- Hook creation success rate
- Common issues encountered
- Most used templates
- Configuration patterns

### Metryki Sukcesu
- Hook executes without errors
- Proper dual output (stdout + stderr)
- UTF-8 encoding works
- User satisfaction with automation

### Historia Ulepszeń

#### v1.0.0 (2025-11-05)
- [REFACTOR] Converted from plain .md to proper SKILL.md format
- [ADDED] YAML frontmatter with metadata
- [ADDED] Structured sections (Overview, Kiedy używać, Workflow)
- [IMPROVED] Clearer template separation
- [ADDED] Complete example (Context7 check)
- [ADDED] Troubleshooting section
- Compliant with skill-creator documentation

---

**Sukcesu z Hooks! 🪝**
