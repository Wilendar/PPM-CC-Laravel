#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PreToolUse PHP Active Hook - Sprawdza Context7 connection i przypomina o patterns
"""

import sys
from pathlib import Path

class Color:
    BOLD = '\033[1m'
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

        output = f"\n{Color.BOLD}{Color.YELLOW}⚠️  PHP CODE MODIFICATION{Color.RESET}\n"
        output += f"{Color.CYAN}Context7 libraries available:{Color.RESET}\n"
        output += "  • Laravel 12.x: /websites/laravel_12_x (4927 snippets)\n"
        output += "  • Livewire 3.x: /livewire/livewire (867 snippets)\n"
        output += "  • Alpine.js: /alpinejs/alpine (364 snippets)\n"
        output += f"\n{Color.BOLD}REMEMBER:{Color.RESET}\n"
        output += "  ❌ NO HARDCODING - use realistic random/dynamic values\n"
        output += "  ❌ NO INLINE STYLES - use CSS classes\n"
        output += "  ✅ Verify with Context7 BEFORE implementation\n\n"

        write_tty(output)

    except Exception as e:
        error_msg = f"❌ Hook Error: {e}\n"
        write_tty(error_msg)

if __name__ == '__main__':
    main()
