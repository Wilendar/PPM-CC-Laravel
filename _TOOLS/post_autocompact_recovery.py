#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SessionStart Hook - Post-AutoCompact Recovery System
Displays recovery options when previous session was interrupted
Python version - more reliable stdin/stdout handling on Windows
"""

import sys
import json
import os
from datetime import datetime
from pathlib import Path

def main():
    try:
        # Read stdin (Claude sends session info as JSON)
        # Must consume stdin even if not parsing
        stdin_data = sys.stdin.read()

        # Paths
        project_root = Path(r"D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel")
        snapshot_path = project_root / "_TEMP" / "claude_session_state.json"

        # Check if snapshot exists
        if not snapshot_path.exists():
            print("\nProject: CLAUDE.md + 13 agents available")
            print("Resources: Plan_Projektu/ | _AGENT_REPORTS/ | Context7\n")
            sys.exit(0)

        # Read snapshot
        with open(snapshot_path, 'r', encoding='utf-8') as f:
            snapshot = json.load(f)

        # Check age
        snapshot_time = datetime.fromisoformat(snapshot['timestamp'].replace('Z', '+00:00'))
        age = datetime.now(snapshot_time.tzinfo) - snapshot_time
        age_minutes = int(age.total_seconds() / 60)

        # Only show if < 24h old
        if age.total_seconds() > 24 * 3600:
            print("\nProject: CLAUDE.md + 13 agents available")
            print("Resources: Plan_Projektu/ | _AGENT_REPORTS/ | Context7\n")
            sys.exit(0)

        # Display recovery info
        print()
        print("=" * 45)
        print("  CLAUDE POST-AUTOCOMPACT RECOVERY")
        print("=" * 45)
        print(f"\nWykryto przerwana sesje z poprzedniego kontekstu!")
        print(f"\nTimestamp: {snapshot['timestamp']} ({age_minutes}m ago)")

        if 'context_summary' in snapshot:
            print(f"Context: {snapshot['context_summary']}")

        # Count tasks
        todos = snapshot.get('todos', [])
        completed = len([t for t in todos if t.get('status') == 'completed'])
        in_progress = len([t for t in todos if t.get('status') == 'in_progress'])
        pending = len([t for t in todos if t.get('status') == 'pending'])
        total = len(todos)

        print(f"\nTODO Status ({total} total):")
        print(f"  Completed: {completed}")
        print(f"  In Progress: {in_progress}")
        print(f"  Pending: {pending}")

        # Show interrupted task
        if in_progress > 0:
            interrupted = [t for t in todos if t.get('status') == 'in_progress'][0]
            print(f"\nPrzerwane zadanie:")
            print(f"  {interrupted.get('content', 'Unknown')}")

        # Show agent
        if 'agent_in_progress' in snapshot:
            print(f"\nAgent: {snapshot['agent_in_progress']} (was in progress)")

        # Show last file
        if 'last_file_read' in snapshot:
            print(f"Last file: {snapshot['last_file_read']}")

        # Options
        print("\nPROPOZYCJE KONTYNUACJI:")
        print()
        print("A) KONTYNUUJ od przerwania")
        print("   Wczytam pelne TODO i wznowie prace")
        print()
        print("B) AKTUALIZUJ PLAN")
        print("   Przeanalizuje postepy i zaproponuje zmiany")
        print()
        print("C) NOWE ZADANIE")
        print("   Zacznij od nowa (poprzednie TODO archiwizowane)")
        print()
        print("D) PRZEGLAD KONTEKSTU")
        print("   Czytaj Plan_Projektu + Reports (WARNING: high tokens!)")
        print()
        print("=" * 45)
        print("TIP: Skopiuj snapshot JSON do Claude prompt dla opcji A/B")
        print()

    except Exception as e:
        # Silent failure - minimal output
        print("\nProject: CLAUDE.md + 13 agents available")
        print("Resources: Plan_Projektu/ | _AGENT_REPORTS/ | Context7\n")

    # CRITICAL: Always exit 0
    sys.exit(0)

if __name__ == "__main__":
    main()
