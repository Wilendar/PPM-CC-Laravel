# -*- coding: utf-8 -*-
"""
PPM-CC-Laravel: Pre-Compact Snapshot Hook
==========================================
Zapisuje snapshot TODO i kontekstu sesji PRZED kompaktowaniem.
"""

import json
import os
import sys
from datetime import datetime
from pathlib import Path

# Fix Windows UTF-8 encoding for emoji
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

# Kolory dla Windows PowerShell
class Color:
    CYAN = '\033[96m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    RESET = '\033[0m'
    BOLD = '\033[1m'

def print_colored(text, color):
    """Wyświetl kolorowy tekst w CLI - próbuje /dev/tty, fallback do stderr"""
    output = f"{color}{text}{Color.RESET}\n"
    try:
        # Próbuj pisać do /dev/tty (ominąć przechwytywanie Claude Code)
        with open('/dev/tty', 'w') as tty:
            tty.write(output)
    except (IOError, OSError):
        # Fallback do stderr
        sys.stderr.write(output)
        sys.stderr.flush()

def ensure_snapshot_dir():
    """Upewnij się że katalog snapshots istnieje"""
    snapshot_dir = Path("_TEMP/compact_snapshots")
    snapshot_dir.mkdir(parents=True, exist_ok=True)
    return snapshot_dir

def create_snapshot():
    """Utwórz snapshot kontekstu sesji"""
    timestamp = datetime.now().isoformat().replace(':', '-')

    snapshot_data = {
        "timestamp": datetime.now().isoformat(),
        "session_context": {
            "project": "PPM-CC-Laravel",
            "critical_rules": [
                "Context7 MANDATORY przed implementacją",
                "NO HARDCODING - wszystko konfigurowane",
                "NO MOCK DATA - tylko prawdziwe struktury",
                "Agents MUST create reports in _AGENT_REPORTS/",
                "Frontend verification MANDATORY przed informowaniem użytkownika"
            ],
            "deployment": {
                "domain": "ppm.mpptrade.pl",
                "ssh_host": "host379076@host379076.hostido.net.pl:64321",
                "ssh_key": "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk",
                "path": "domains/ppm.mpptrade.pl/public_html/"
            },
            "workflow": [
                "1. npm run build (lokalnie)",
                "2. pscp upload ALL assets + manifest (ROOT!)",
                "3. php artisan cache:clear (produkcja)",
                "4. PPM Verification Tool (_TOOLS/full_console_test.cjs)",
                "5. Screenshot verification"
            ]
        },
        "active_tasks": "Check current TODO list - may contain in-progress tasks",
        "last_actions": "Review recent operations before compact"
    }

    # Zapisz snapshot
    snapshot_dir = ensure_snapshot_dir()
    snapshot_file = snapshot_dir / f"snapshot_{timestamp}.json"

    with open(snapshot_file, 'w', encoding='utf-8') as f:
        json.dump(snapshot_data, f, indent=2, ensure_ascii=False)

    # Zapisz także "latest" dla łatwego dostępu
    latest_file = snapshot_dir / "latest_snapshot.json"
    with open(latest_file, 'w', encoding='utf-8') as f:
        json.dump(snapshot_data, f, indent=2, ensure_ascii=False)

    return snapshot_file, snapshot_data

def main():
    """Główna funkcja hooka"""
    try:
        # CRITICAL: Consume stdin to prevent deadlock (Claude sends JSON session info)
        stdin_data = sys.stdin.read()

        print_colored("\n" + "="*60, Color.CYAN)
        print_colored("📦 PRE-COMPACT SNAPSHOT", Color.BOLD + Color.CYAN)
        print_colored("="*60, Color.CYAN)

        snapshot_file, data = create_snapshot()

        print_colored(f"\n✅ Snapshot zapisany: {snapshot_file.name}", Color.GREEN)

        print_colored("\n⚠️  KRYTYCZNE ZASADY (zachowane po compact):", Color.YELLOW)
        for rule in data["session_context"]["critical_rules"]:
            print_colored(f"   • {rule}", Color.YELLOW)

        print_colored("\n🔄 Deployment workflow (zachowany):", Color.CYAN)
        for step in data["session_context"]["workflow"]:
            print_colored(f"   {step}", Color.CYAN)

        print_colored("\n💾 Recovery będzie dostępny w post-compact hook", Color.GREEN)
        print_colored("="*60 + "\n", Color.CYAN)

        # CLI OUTPUT dla użytkownika (stderr = widoczne w terminalu)
        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ PRE-COMPACT HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        sys.stderr.write(f"📦 Context snapshot created: {snapshot_file.name}\n")
        sys.stderr.write(f"💾 Location: _TEMP/compact_snapshots/\n")
        sys.stderr.write(f"📋 Saved: {len(data['session_context']['critical_rules'])} critical rules\n")
        sys.stderr.write(f"🔄 Saved: {len(data['session_context']['workflow'])} workflow steps\n")
        sys.stderr.write("🚀 Deployment info preserved\n")
        sys.stderr.write("✅ Ready for compaction - context will be restored after compact\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        print_colored(f"\n❌ BŁĄD pre-compact hook: {str(e)}", Color.RED)
        return 1

if __name__ == "__main__":
    sys.exit(main())
