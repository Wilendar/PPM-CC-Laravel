#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Znajdz dyrektywy Blade (@ prefix) poza code blockami w CLAUDE.md
"""
import re

claude_md = r"D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\CLAUDE.md"

with open(claude_md, 'r', encoding='utf-8') as f:
    lines = f.readlines()

in_code_block = False
problems = []

for line_num, line in enumerate(lines, 1):
    # Toggle code block
    if line.strip().startswith('```'):
        in_code_block = not in_code_block
        continue

    # Skip if in code block
    if in_code_block:
        continue

    # Skip if @ is in inline code (backticks)
    if '`' in line:
        # Remove inline code parts
        line_clean = re.sub(r'`[^`]+`', '', line)
    else:
        line_clean = line

    # Find @ directives
    if '@' in line_clean and re.search(r'@\w+', line_clean):
        problems.append((line_num, line.strip()))

print("Problematyczne linie w CLAUDE.md (dyrektywy @ poza code blocks):")
print("=" * 70)
for line_num, line_text in problems:
    print(f"Linia {line_num}: {line_text[:80]}")
print("=" * 70)
print(f"TOTAL: {len(problems)} potencjalnych problemow")
