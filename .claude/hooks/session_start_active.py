#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SessionStart Active Hook - Aktywnie czyta CLAUDE.md i zwraca treść
"""

import sys
import os
from pathlib import Path

class Color:
    BOLD = '\033[1m'
    RED = '\033[31m'
    GREEN = '\033[32m'
    YELLOW = '\033[33m'
    BLUE = '\033[34m'
    CYAN = '\033[36m'
    RESET = '\033[0m'

def write_tty(text):
    """Pisz do stderr (Windows-compatible)"""
    sys.stderr.write(text)
    sys.stderr.flush()

def read_file_safe(filepath, max_lines=None):
    """Bezpiecznie czyta plik z limitem linii"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            if max_lines:
                lines = []
                for i, line in enumerate(f):
                    if i >= max_lines:
                        break
                    lines.append(line)
                return ''.join(lines)
            return f.read()
    except Exception as e:
        return f"[ERROR reading {filepath}: {e}]"

def extract_key_sections(claude_md_content):
    """Ekstraktuje kluczowe sekcje z CLAUDE.md"""
    sections = {
        'PODSTAWOWE ZASADY': [],
        'Stack Technologiczny': [],
        'Build & Deployment': [],
        'KRYTYCZNE ZASADY': [],
        'Środowisko Deployment': [],
        'AGENT SYSTEM': [],
        'Context7': []
    }

    current_section = None
    lines = claude_md_content.split('\n')

    for line in lines:
        # Detect section headers
        if 'PODSTAWOWE ZASADY' in line:
            current_section = 'PODSTAWOWE ZASADY'
        elif 'Stack Technologiczny' in line:
            current_section = 'Stack Technologiczny'
        elif 'Build & Deployment Architecture' in line:
            current_section = 'Build & Deployment'
        elif 'KRYTYCZNE ZASADY' in line:
            current_section = 'KRYTYCZNE ZASADY'
        elif 'Środowisko Deployment' in line:
            current_section = 'Środowisko Deployment'
        elif 'SYSTEM AGENTÓW' in line:
            current_section = 'AGENT SYSTEM'
        elif 'CONTEXT7' in line:
            current_section = 'Context7'

        # Collect lines for current section (max 15 lines per section)
        if current_section and len(sections[current_section]) < 15:
            sections[current_section].append(line)

    return sections

def main():
    try:
        # Konsumuj stdin żeby uniknąć deadlock
        try:
            sys.stdin.read()
        except:
            pass

        # Ścieżka do projektu
        project_root = Path(__file__).resolve().parent.parent.parent
        claude_md_path = project_root / 'CLAUDE.md'

        # Header
        output = f"\n{Color.BOLD}{Color.CYAN}{'='*80}{Color.RESET}\n"
        output += f"{Color.BOLD}{Color.GREEN}🚀 PPM-CC-Laravel SESSION START{Color.RESET}\n"
        output += f"{Color.CYAN}{'='*80}{Color.RESET}\n\n"

        # Czytaj CLAUDE.md
        if claude_md_path.exists():
            output += f"{Color.YELLOW}📖 Reading CLAUDE.md...{Color.RESET}\n\n"

            content = read_file_safe(str(claude_md_path), max_lines=500)
            sections = extract_key_sections(content)

            # Wyświetl kluczowe sekcje
            for section_name, section_lines in sections.items():
                if section_lines:
                    output += f"{Color.BOLD}{Color.BLUE}▸ {section_name}:{Color.RESET}\n"
                    for line in section_lines[:10]:  # Max 10 linii per sekcja
                        if line.strip():
                            output += f"  {line}\n"
                    output += "\n"
        else:
            output += f"{Color.RED}⚠️  CLAUDE.md NOT FOUND at {claude_md_path}{Color.RESET}\n\n"

        # Sprawdź status projektu
        plan_dir = project_root / 'Plan_Projektu'
        if plan_dir.exists():
            output += f"{Color.YELLOW}📋 Checking project status...{Color.RESET}\n"
            plan_files = list(plan_dir.glob('ETAP_*.md'))
            output += f"  Found {len(plan_files)} ETAP files\n\n"

        # Sprawdź ostatnie raporty agentów
        reports_dir = project_root / '_AGENT_REPORTS'
        if reports_dir.exists():
            report_files = sorted(reports_dir.glob('*.md'), key=os.path.getmtime, reverse=True)
            if report_files:
                output += f"{Color.YELLOW}📊 Latest agent reports:{Color.RESET}\n"
                for report in report_files[:3]:  # 3 ostatnie
                    output += f"  • {report.name}\n"
                output += "\n"

        # Footer
        output += f"{Color.CYAN}{'='*80}{Color.RESET}\n"
        output += f"{Color.BOLD}{Color.GREEN}✅ Session initialized - Project context loaded{Color.RESET}\n"
        output += f"{Color.CYAN}{'='*80}{Color.RESET}\n\n"

        # Pisz do TTY
        write_tty(output)

    except Exception as e:
        error_msg = f"{Color.RED}❌ Hook Error: {e}{Color.RESET}\n"
        write_tty(error_msg)
        sys.exit(1)

if __name__ == '__main__':
    main()
