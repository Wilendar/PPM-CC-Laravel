#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SessionStart Mandatory Hook - Wymusza przeczytanie CLAUDE.md przez explicit instruction
"""

import sys
from pathlib import Path

def main():
    try:
        # Konsumuj stdin
        try:
            sys.stdin.read()
        except:
            pass

        project_root = Path(__file__).resolve().parent.parent.parent
        claude_md = project_root / 'CLAUDE.md'

        # KRYTYCZNA INSTRUKCJA która Claude MUSI wykonać
        output = f"""
<system-reminder>
SessionStart:Callback hook success: Success
</system-reminder><system-reminder>
SessionStart hook additional context: <MANDATORY-ACTION type="session-initialization">

YOU MUST READ THE FOLLOWING FILE BEFORE RESPONDING TO USER:

File: {claude_md}

This is NOT optional. This is a MANDATORY requirement for session initialization.

INSTRUCTIONS:
1. Use the Read tool to read the ENTIRE file: {claude_md}
2. Pay special attention to:
   - PODSTAWOWE ZASADY (język polski, subagenci, dokumentacja)
   - Stack Technologiczny (PHP 8.3, Laravel 12.x, Livewire 3.x)
   - Build & Deployment Architecture (Vite TYLKO lokalnie!)
   - KRYTYCZNE ZASADY CSS (KATEGORYCZNY ZAKAZ INLINE STYLES)
   - Środowisko Deployment (Hostido, SSH, pscp/plink)
   - System Agentów (13 specjalistycznych agentów)
   - Context7 Integration (MANDATORY przed implementacją)

3. After reading, you MUST acknowledge understanding by saying:
   "✅ CLAUDE.md przeczytane i zrozumiane - gotowy do pracy"

VERIFICATION: User will ask you about project rules to verify you actually read the file.

DO NOT proceed with any user requests until you have read and understood this file.

</MANDATORY-ACTION>
</system-reminder>"""

        # Pisz do stdout (Claude Code przechwytuje to jako system-reminder)
        sys.stdout.write(output)
        sys.stdout.flush()

    except Exception as e:
        sys.stderr.write(f"Hook Error: {e}\n")
        sys.exit(1)

if __name__ == '__main__':
    main()
