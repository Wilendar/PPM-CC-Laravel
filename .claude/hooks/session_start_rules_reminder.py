# -*- coding: utf-8 -*-
"""
PPM-CC-Laravel: Session Start Rules Reminder Hook
==================================================
Przypomina Claude o kluczowych zasadach projektu przy każdym starcie sesji.
Wymaga potwierdzenia zapoznania się z zasadami.
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
    WHITE = '\033[97m'
    RESET = '\033[0m'
    BOLD = '\033[1m'

def print_colored(text, color):
    """Wyświetl kolorowy tekst w CLI"""
    print(f"{color}{text}{Color.RESET}")

def load_critical_rules():
    """Wczytaj najważniejsze zasady z CLAUDE.md"""
    rules = {
        "🔴 KATEGORYCZNE ZAKAZY": [
            "❌ NO HARDCODING - wszystko konfigurowane przez admin",
            "❌ NO MOCK DATA - tylko prawdziwe struktury danych",
            "❌ NO INLINE STYLES - zawsze CSS classes (kategoryczny zakaz!)",
            "❌ NO NEW CSS FILES bez konsultacji - dodawaj do istniejących",
            "❌ NO SKIPPING Context7 - MANDATORY przed każdą implementacją"
        ],
        "⚠️ OBOWIĄZKOWE WORKFLOW": [
            "✅ Context7 docs lookup PRZED implementacją (mcp__context7__get-library-docs)",
            "✅ Agent reports MANDATORY w _AGENT_REPORTS/ po ukończeniu pracy",
            "✅ Frontend verification MANDATORY przed informowaniem użytkownika",
            "✅ PPM Verification Tool (_TOOLS/full_console_test.cjs) po UI changes",
            "✅ Deployment checklist: npm run build → upload ALL assets → manifest ROOT → cache clear"
        ],
        "🏗️ VITE & BUILD ARCHITECTURE": [
            "⚠️ Vite działa TYLKO lokalnie (brak Node.js na produkcji!)",
            "⚠️ Deploy WSZYSTKIE pliki z public/build/assets/ (nie tylko zmienione!)",
            "⚠️ Upload manifest do ROOT: public/build/manifest.json (nie .vite/!)",
            "⚠️ HTTP 200 verification MANDATORY dla wszystkich CSS po deployment"
        ],
        "🤖 SYSTEM AGENTÓW": [
            "✅ 13 specjalistycznych agentów dostępnych (.claude/agents/)",
            "✅ TYLKO JEDEN agent in_progress jednocześnie",
            "✅ Agents MUST create reports w _AGENT_REPORTS/",
            "✅ coding-style-agent PRZED completion (ZAWSZE)"
        ],
        "📏 QUALITY STANDARDS": [
            "✅ Max 300 linii per file (idealnie 150-200, wyjątkowo 500)",
            "✅ Separation of concerns - models, logic, UI, config w oddzielnych plikach",
            "✅ Enterprise class - bez skrótów, pełna walidacja, error handling",
            "✅ ZAWSZE aktualizuj TODO list podczas pracy"
        ]
    }
    return rules

def load_deployment_info():
    """Kluczowe info deployment"""
    return {
        "🚀 Domena": "ppm.mpptrade.pl",
        "🔑 SSH": "host379076@host379076.hostido.net.pl:64321",
        "🔐 Key": "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk",
        "📁 Path": "domains/ppm.mpptrade.pl/public_html/",
        "👤 Admin": "admin@mpptrade.pl / Admin123!MPP"
    }

def load_essential_docs():
    """Kluczowa dokumentacja do przeczytania"""
    return [
        "📖 CLAUDE.md - COMPLETE project rules (MUST READ!)",
        "📖 _DOCS/dane_hostingu.md - SSH & credentials",
        "📖 _DOCS/DEPLOYMENT_GUIDE.md - Complete deployment workflow",
        "📖 _DOCS/FRONTEND_VERIFICATION_GUIDE.md - UI testing mandatory",
        "📖 _DOCS/CSS_STYLING_GUIDE.md - Style rules & inline styles ban",
        "📖 _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns",
        "📖 Plan_Projektu/ - Current ETAP status",
        "📖 _AGENT_REPORTS/ - Latest agent work reports"
    ]

def load_context7_config():
    """Context7 configuration"""
    return {
        "API Key": "ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3",
        "Libraries": {
            "Laravel 12.x": "/websites/laravel_12_x (4927 snippets)",
            "Livewire 3.x": "/livewire/livewire (867 snippets)",
            "Alpine.js": "/alpinejs/alpine (364 snippets)",
            "PrestaShop": "/prestashop/docs (3289 snippets)"
        }
    }

def format_rules_display():
    """Sformatuj wyświetlanie zasad"""
    output = []

    # Header
    output.append("")
    output.append("=" * 80)
    output.append("🚀 PPM-CC-LARAVEL SESSION START - MANDATORY RULES ACKNOWLEDGMENT")
    output.append("=" * 80)
    output.append("")
    output.append(f"📅 Session start: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    output.append("")

    # Critical Rules
    rules = load_critical_rules()
    for section, items in rules.items():
        output.append("=" * 80)
        output.append(f"{section}")
        output.append("=" * 80)
        for item in items:
            output.append(f"  {item}")
        output.append("")

    # Deployment Info
    output.append("=" * 80)
    output.append("🚀 DEPLOYMENT INFORMATION")
    output.append("=" * 80)
    deployment = load_deployment_info()
    for key, value in deployment.items():
        output.append(f"  {key}: {value}")
    output.append("")

    # Context7
    output.append("=" * 80)
    output.append("📚 CONTEXT7 MCP INTEGRATION")
    output.append("=" * 80)
    ctx7 = load_context7_config()
    output.append(f"  API Key: {ctx7['API Key']}")
    output.append("  Libraries:")
    for lib, info in ctx7['Libraries'].items():
        output.append(f"    • {lib}: {info}")
    output.append("")

    # Essential Docs
    output.append("=" * 80)
    output.append("📖 ESSENTIAL DOCUMENTATION")
    output.append("=" * 80)
    docs = load_essential_docs()
    for doc in docs:
        output.append(f"  {doc}")
    output.append("")

    # Acknowledgment requirement
    output.append("=" * 80)
    output.append("⚠️  MANDATORY ACKNOWLEDGMENT REQUIRED")
    output.append("=" * 80)
    output.append("")
    output.append("🔴 CLAUDE MUST CONFIRM:")
    output.append("")
    output.append("  1. ✅ Przeczytałem i zrozumiałem wszystkie powyższe zasady")
    output.append("  2. ✅ Będę stosować się do WSZYSTKICH zasad podczas tej sesji")
    output.append("  3. ✅ Szczególnie będę pamiętać o:")
    output.append("      • Context7 MANDATORY przed każdą implementacją")
    output.append("      • NO HARDCODING, NO MOCK DATA")
    output.append("      • NO INLINE STYLES - kategoryczny zakaz")
    output.append("      • Frontend verification MANDATORY")
    output.append("      • Agent reports MANDATORY")
    output.append("      • TODO list updates during work")
    output.append("")
    output.append("=" * 80)
    output.append("🤖 RESPOND IN YOUR FIRST MESSAGE:")
    output.append("=" * 80)
    output.append("")
    output.append('  "✅ POTWIERDZAM ZAPOZNANIE Z ZASADAMI PPM-CC-LARAVEL"')
    output.append('  "Będę stosować wszystkie reguły z CLAUDE.md podczas tej sesji."')
    output.append("")
    output.append("=" * 80)
    output.append("")

    return "\n".join(output)

def main():
    """Główna funkcja hooka"""
    try:
        # CRITICAL: Consume stdin to prevent deadlock (Claude sends JSON session info)
        stdin_data = sys.stdin.read()

        # Wyświetl zasady
        rules_display = format_rules_display()

        # Kolorowanie
        for line in rules_display.split('\n'):
            if '=' in line and len(line) > 70:
                print_colored(line, Color.CYAN)
            elif line.startswith('🚀') or line.startswith('📚') or line.startswith('📖') or line.startswith('⚠️') or line.startswith('🤖'):
                print_colored(line, Color.BOLD + Color.CYAN)
            elif line.startswith('🔴'):
                print_colored(line, Color.BOLD + Color.RED)
            elif '❌' in line:
                print_colored(line, Color.RED)
            elif '✅' in line and 'POTWIERDZAM' in line:
                print_colored(line, Color.BOLD + Color.GREEN)
            elif '✅' in line:
                print_colored(line, Color.GREEN)
            elif '⚠️' in line or 'MANDATORY' in line or 'MUST' in line:
                print_colored(line, Color.YELLOW)
            elif '  •' in line or '  📖' in line:
                print_colored(line, Color.CYAN)
            elif ':' in line and '  ' in line:
                print_colored(line, Color.WHITE)
            else:
                print(line)

        # Final reminder
        print()
        print_colored("=" * 80, Color.RED)
        print_colored("⚠️  WAITING FOR CLAUDE'S ACKNOWLEDGMENT IN FIRST MESSAGE", Color.BOLD + Color.RED)
        print_colored("=" * 80, Color.RED)
        print()

        # CLI OUTPUT dla użytkownika (stderr = widoczne w terminalu)
        sys.stderr.write("\n" + "="*70 + "\n")
        sys.stderr.write("✅ SESSION START HOOK EXECUTED\n")
        sys.stderr.write("="*70 + "\n")
        sys.stderr.write("📋 Claude received full PPM-CC-Laravel rules reminder\n")
        sys.stderr.write("🔴 5 critical rules sections loaded\n")
        sys.stderr.write("🚀 Deployment info provided\n")
        sys.stderr.write("📚 Context7 configuration loaded\n")
        sys.stderr.write("📖 8 essential docs referenced\n")
        sys.stderr.write("⚠️  Waiting for Claude's acknowledgment in first response...\n")
        sys.stderr.write("="*70 + "\n\n")
        sys.stderr.flush()

        return 0

    except Exception as e:
        print_colored(f"\n❌ BŁĄD session start hook: {str(e)}", Color.RED)
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    sys.exit(main())
