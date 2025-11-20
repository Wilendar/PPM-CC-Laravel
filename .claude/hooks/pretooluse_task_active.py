#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PreToolUse Task Active Hook - Lista dostępnych agentów z descriptions
"""

import sys
from pathlib import Path

class Color:
    BOLD = '\033[1m'
    GREEN = '\033[32m'
    CYAN = '\033[36m'
    RESET = '\033[0m'

def write_tty(text):
    """Pisz do stderr (Windows-compatible)"""
    sys.stderr.write(text)
    sys.stderr.flush()

def read_agents_list():
    """Czyta listę agentów z .claude/agents/"""
    project_root = Path(__file__).resolve().parent.parent.parent
    agents_dir = project_root / '.claude' / 'agents'

    agents = []
    if agents_dir.exists():
        for agent_file in sorted(agents_dir.glob('*.md')):
            agent_name = agent_file.stem
            # Parse YAML front matter
            try:
                with open(agent_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                    description = "No description"

                    # Simple YAML front matter parsing (between --- lines)
                    if content.startswith('---'):
                        parts = content.split('---', 2)
                        if len(parts) >= 3:
                            yaml_content = parts[1]
                            # Extract description line
                            for line in yaml_content.split('\n'):
                                if line.startswith('description:'):
                                    description = line.split(':', 1)[1].strip()
                                    break

                    agents.append((agent_name, description))
            except:
                agents.append((agent_name, "No description"))

    return agents

def main():
    try:
        # Konsumuj stdin
        try:
            sys.stdin.read()
        except:
            pass

        output = f"\n{Color.BOLD}{Color.CYAN}🤖 AGENT DELEGATION{Color.RESET}\n"
        output += f"{Color.GREEN}Available agents:{Color.RESET}\n\n"

        agents = read_agents_list()
        if agents:
            for agent_name, description in agents[:13]:  # Max 13 agentów
                output += f"  • {Color.BOLD}{agent_name}{Color.RESET}: {description[:60]}...\n"
        else:
            output += "  No agents found in .claude/agents/\n"

        output += f"\n{Color.BOLD}REQUIREMENTS:{Color.RESET}\n"
        output += "  ✅ Create _AGENT_REPORTS/agent_name_REPORT.md after completion\n"
        output += "  ✅ Update Plan_Projektu/ with status emoji\n"
        output += "  ✅ Use coding-style-agent BEFORE completion\n\n"

        write_tty(output)

    except Exception as e:
        error_msg = f"❌ Hook Error: {e}\n"
        write_tty(error_msg)

if __name__ == '__main__':
    main()
