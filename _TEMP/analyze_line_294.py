#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Analiza linii 294 CLAUDE.md
"""

line_294 = "- `[wire:poll + @if](_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md)` - Nie działa w conditional"

print("ANALIZA LINII 294:")
print("=" * 70)
print(f"Treść: {line_294}")
print()

# Sprawdź elementy
has_backticks = '`' in line_294
has_markdown_link = '[' in line_294 and '](' in line_294
has_blade_directive = '@if' in line_294
has_inline_code = line_294.count('`') >= 2

print("WYKRYTE ELEMENTY:")
print(f"  Backticki (inline code): {has_backticks} - count: {line_294.count('`')}")
print(f"  Markdown link [text](url): {has_markdown_link}")
print(f"  Dyrektywa Blade (@if): {has_blade_directive}")
print(f"  Inline code (para backticks): {has_inline_code}")
print()

print("PROBLEM:")
print("  ❌ ZAKAZANA KOMBINACJA: backticks + markdown link + @if")
print("  ❌ Parser nie wie czy to:")
print("     1. Inline code (ignoruj markdown i @)")
print("     2. Markdown link (parsuj URL)")
print("     3. Dyrektywa Blade (interpretuj jako instrukcję)")
print()

print("ROZWIĄZANIA:")
print("  A) Usuń backticki, zostaw tylko link:")
print(f"     - [wire:poll + @if issue](_ISSUES_FIXES/...) - ...")
print()
print("  B) Usuń @if, zmień na tekst:")
print(f"     - `[wire:poll + conditional](_ISSUES_FIXES/...)` - ...")
print()
print("  C) Escape @ jako \\@:")
print(f"     - `[wire:poll + \\@if](_ISSUES_FIXES/...)` - ...")
print()
print("  D) Podziel na dwie linie:")
print("     - wire:poll + @if issue")
print("       Link: _ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md")
