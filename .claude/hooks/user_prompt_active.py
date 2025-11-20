#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
UserPromptSubmit Active Hook - Sprawdza recovery i Context7 status
"""

import sys
import os
from pathlib import Path
import json

class Color:
    BOLD = '\033[1m'
    GREEN = '\033[32m'
    YELLOW = '\033[33m'
    CYAN = '\033[36m'
    RESET = '\033[0m'

def write_tty(text):
    """Pisz do stderr (Windows-compatible)"""
    sys.stderr.write(text)
    sys.stderr.flush()

def main():
    try:
        # Konsumuj stdin
        try:
            sys.stdin.read()
        except:
            pass

        project_root = Path(__file__).resolve().parent.parent.parent
        compact_dir = project_root / '_TEMP' / 'compact_snapshots'

        output = ""

        # Check for recovery flag
        recovery_flag = project_root / '.claude' / 'recovery.flag'
        if recovery_flag.exists():
            output += f"\n{Color.BOLD}{Color.CYAN}🔄 RECOVERY DETECTED{Color.RESET}\n"

            # Znajdź ostatni snapshot
            if compact_dir.exists():
                snapshots = sorted(compact_dir.glob('session_*.json'), key=os.path.getmtime, reverse=True)
                if snapshots:
                    latest = snapshots[0]
                    output += f"{Color.YELLOW}📸 Latest snapshot: {latest.name}{Color.RESET}\n"

                    try:
                        with open(latest, 'r', encoding='utf-8') as f:
                            snapshot = json.load(f)
                            output += f"  Branch: {snapshot.get('git_branch', 'N/A')}\n"
                            output += f"  Timestamp: {snapshot.get('timestamp', 'N/A')}\n"

                            if 'working_directory' in snapshot:
                                output += f"  Working dir: {snapshot['working_directory']}\n"
                    except:
                        pass

            # Usuń flag
            recovery_flag.unlink()
            output += f"{Color.GREEN}✅ Recovery flag cleared{Color.RESET}\n\n"

        # Context7 reminder (co 5 prompt - prostszy check)
        # W przyszłości można dodać counting mechanism
        else:
            # Krótkie przypomnienie
            output += f"{Color.CYAN}💡 Tip: Use Context7 MCP for Laravel/Livewire documentation{Color.RESET}\n"

        if output:
            write_tty(output)

    except Exception as e:
        error_msg = f"❌ Hook Error: {e}\n"
        write_tty(error_msg)

if __name__ == '__main__':
    main()
