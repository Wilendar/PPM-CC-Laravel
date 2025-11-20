# -*- coding: utf-8 -*-
"""
PPM-CC-Laravel: Post-AutoCompact Recovery Hook
===============================================
Przywraca kontekst i zasady PO automatycznym kompaktowaniu.
"""

import json
import os
import sys
from pathlib import Path
from datetime import datetime

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
    MAGENTA = '\033[95m'
    RESET = '\033[0m'
    BOLD = '\033[1m'

def print_colored(text, color):
    """Wyświetl kolorowy tekst w CLI"""
    print(f"{color}{text}{Color.RESET}")

def load_latest_snapshot():
    """Wczytaj ostatni snapshot"""
    snapshot_file = Path("_TEMP/compact_snapshots/latest_snapshot.json")

    if not snapshot_file.exists():
        return None

    try:
        with open(snapshot_file, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print_colored(f"⚠️  Nie można wczytać snapshot: {e}", Color.YELLOW)
        return None

def load_critical_rules():
    """Wczytaj krytyczne zasady z CLAUDE.md"""
    critical_sections = {
        "Vite & Build": [
            "⚠️ KRYTYCZNA ZASADA: Vite działa TYLKO na lokalnej maszynie!",
            "Deploy WSZYSTKIE pliki z public/build/assets/",
            "Upload manifest do ROOT: public/build/manifest.json",
            "HTTP 200 Verification MANDATORY dla wszystkich CSS"
        ],
        "Frontend Verification": [
            "⚠️ OBOWIĄZKOWA WERYFIKACJA przed informowaniem użytkownika",
            "PPM Verification Tool: _TOOLS/full_console_test.cjs",
            "Screenshot verification MANDATORY"
        ],
        "CSS & Styles": [
            "⛔ KATEGORYCZNY ZAKAZ inline styles",
            "Dodawaj style do ISTNIEJĄCYCH plików CSS",
            "NIGDY nie twórz nowych plików CSS bez konsultacji"
        ],
        "Context7 & Agents": [
            "Context7 MANDATORY przed implementacją",
            "Agent reports MANDATORY w _AGENT_REPORTS/",
            "NO HARDCODING - wszystko konfigurowane",
            "NO MOCK DATA - tylko prawdziwe struktury"
        ]
    }

    return critical_sections

def load_deployment_info():
    """Wczytaj informacje o deployment z dane_hostingu.md"""
    deployment = {
        "Domena": "ppm.mpptrade.pl",
        "SSH Host": "host379076@host379076.hostido.net.pl:64321",
        "SSH Key": "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk",
        "Laravel Path": "domains/ppm.mpptrade.pl/public_html/",
        "Admin Login": "admin@mpptrade.pl / Admin123!MPP"
    }

    return deployment

def format_recovery_message():
    """Sformatuj pełną wiadomość recovery"""
    output = []

    output.append("="*70)
    output.append("🔄 POST-AUTOCOMPACT RECOVERY")
    output.append("="*70)

    # Snapshot info
    snapshot = load_latest_snapshot()
    if snapshot:
        output.append(f"\n📦 SNAPSHOT RECOVERY")
        output.append(f"   Timestamp: {snapshot['timestamp']}")
        output.append(f"   Project: {snapshot['session_context']['project']}")

        if "active_tasks" in snapshot:
            output.append(f"\n⚠️  {snapshot['active_tasks']}")
    else:
        output.append("\n⚠️  Brak snapshot - pierwszy compact w sesji")

    # Critical rules
    output.append(f"\n{'='*70}")
    output.append("⚠️  KRYTYCZNE ZASADY PPM-CC-Laravel")
    output.append("="*70)

    rules = load_critical_rules()
    for section, items in rules.items():
        output.append(f"\n🔹 {section}:")
        for item in items:
            output.append(f"   • {item}")

    # Deployment info
    output.append(f"\n{'='*70}")
    output.append("🚀 DEPLOYMENT INFO")
    output.append("="*70)

    deployment = load_deployment_info()
    for key, value in deployment.items():
        output.append(f"   {key}: {value}")

    # Workflow reminder
    output.append(f"\n{'='*70}")
    output.append("📋 DEPLOYMENT WORKFLOW")
    output.append("="*70)

    if snapshot and "workflow" in snapshot["session_context"]:
        for step in snapshot["session_context"]["workflow"]:
            output.append(f"   {step}")
    else:
        output.append("   1. npm run build (lokalnie)")
        output.append("   2. pscp upload ALL assets + manifest (ROOT!)")
        output.append("   3. php artisan cache:clear (produkcja)")
        output.append("   4. PPM Verification Tool")
        output.append("   5. Screenshot verification")

    # Documentation references
    output.append(f"\n{'='*70}")
    output.append("📚 KLUCZOWA DOKUMENTACJA")
    output.append("="*70)
    output.append("   • CLAUDE.md - Project rules & architecture")
    output.append("   • _DOCS/dane_hostingu.md - SSH & credentials")
    output.append("   • _DOCS/DEPLOYMENT_GUIDE.md - Complete deployment")
    output.append("   • _DOCS/FRONTEND_VERIFICATION_GUIDE.md - UI testing")
    output.append("   • Plan_Projektu/ - Current ETAP status")
    output.append("   • _AGENT_REPORTS/ - Latest agent reports")

    output.append(f"\n{'='*70}")
    output.append("✅ RECOVERY COMPLETE - Kontekst przywrócony")
    output.append("="*70 + "\n")

    return "\n".join(output)

def main():
    """Główna funkcja hooka"""
    try:
        # CRITICAL: Consume stdin to prevent deadlock (Claude sends JSON session info)
        stdin_data = sys.stdin.read()

        # Wyświetl recovery message
        recovery_msg = format_recovery_message()

        # Kolorowanie sekcji
        for line in recovery_msg.split('\n'):
            if '=' in line and len(line) > 60:
                print_colored(line, Color.CYAN)
            elif line.startswith('🔄') or line.startswith('📦') or line.startswith('🚀') or line.startswith('📋') or line.startswith('📚'):
                print_colored(line, Color.BOLD + Color.CYAN)
            elif line.startswith('⚠️'):
                print_colored(line, Color.YELLOW)
            elif line.startswith('✅'):
                print_colored(line, Color.GREEN)
            elif line.startswith('🔹'):
                print_colored(line, Color.MAGENTA)
            elif '   •' in line:
                print_colored(line, Color.YELLOW if '⚠️' in line or 'MANDATORY' in line or 'ZAKAZ' in line else Color.CYAN)
            elif '   ' in line and ':' in line:
                print_colored(line, Color.GREEN)
            else:
                print(line)

        # Return success message dla Claude
        print("\n" + "="*70)
        print_colored("🤖 CLAUDE CODE - KONTEKST PRZYWRÓCONY", Color.BOLD + Color.GREEN)
        print_colored("    Wszystkie krytyczne zasady i workflow zachowane", Color.GREEN)
        print("="*70 + "\n")

        # CLI OUTPUT dla użytkownika (stderr = widoczne w terminalu)
        snapshot = load_latest_snapshot()
        rules = load_critical_rules()
        total_rules = sum(len(items) for items in rules.values())

        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ POST-AUTOCOMPACT RECOVERY HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        if snapshot:
            sys.stderr.write(f"📦 Context restored from snapshot: {snapshot['timestamp']}\n")
        else:
            sys.stderr.write("⚠️  No snapshot found (first compact in session)\n")
        sys.stderr.write(f"📋 Loaded {total_rules} critical rules across {len(rules)} sections\n")
        sys.stderr.write("🚀 Deployment info displayed to Claude\n")
        sys.stderr.write("🔄 Deployment workflow restored\n")
        sys.stderr.write("📚 Context7 configuration loaded\n")
        sys.stderr.write("✅ Claude ready to continue with full context\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        print_colored(f"\n❌ BŁĄD post-autocompact hook: {str(e)}", Color.RED)
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    sys.exit(main())
