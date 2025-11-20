#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Finalne skanowanie CLAUDE.md - znajdz WSZYSTKIE @ (potential Blade directives)
"""
import re

claude_md = r"D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\CLAUDE.md"

with open(claude_md, 'r', encoding='utf-8') as f:
    content = f.read()
    lines = content.split('\n')

# Znajdz wszystkie @ ktore nie sa w:
# 1. Email adresach (xxx@yyy.com)
# 2. Inline code (`...@...`)

problems = []
in_code_block = False

for line_num, line in enumerate(lines, 1):
    # Toggle code block
    if line.strip().startswith('```'):
        in_code_block = not in_code_block
        continue

    # Skip empty lines
    if not line.strip():
        continue

    # Find all @ symbols
    at_positions = [m.start() for m in re.finditer(r'@', line)]

    for pos in at_positions:
        # Check if @ is in email (has . and alphanumeric after @)
        if re.match(r'@[\w.-]+\.\w+', line[pos:]):
            continue  # Email - OK

        # Check if @ is in inline code (between backticks)
        before_at = line[:pos]
        after_at = line[pos:]
        backticks_before = before_at.count('`')

        # If odd number of backticks before @ = we're inside inline code
        if backticks_before % 2 == 1:
            continue  # In inline code - probably OK

        # This @ might be a problem
        context = line[max(0, pos-10):min(len(line), pos+20)]
        status = "IN CODE BLOCK" if in_code_block else "OUTSIDE CODE"
        problems.append((line_num, status, context, line.strip()[:80]))

print("SKANOWANIE CLAUDE.md - WSZYSTKIE @ (potencjalne dyrektywy Blade)")
print("=" * 80)
print()

if not problems:
    print("OK! Brak problematycznych @ w CLAUDE.md")
else:
    print(f"Znaleziono {len(problems)} potencjalnych problemow:")
    print()
    for line_num, status, context, full_line in problems:
        print(f"Linia {line_num} [{status}]:")
        print(f"  Kontekst: ...{context}...")
        print(f"  Pelna linia: {full_line}")
        print()

print("=" * 80)
