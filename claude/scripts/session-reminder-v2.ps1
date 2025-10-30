#!/usr/bin/env pwsh
# PPM-CC-Laravel Session Reminder Hook v2
# Zgodny z dokumentacja Claude Code hooks
# Created: 2025-09-30

param()

$ErrorActionPreference = 'SilentlyContinue'

try {
    # Budowanie kontekstu jako tekst
    $contextLines = @(
        "=== PPM-CC-LARAVEL PROJECT CONTEXT ===",
        "",
        "MANDATORY DOCUMENTATION:",
        "- CLAUDE.md: Project rules & Context7 requirements",
        "- _DOCS/AGENT_USAGE_GUIDE.md: Agent delegation patterns",
        "- Plan_Projektu/: Current ETAP status & workflows",
        "",
        "CONTEXT7 MCP:",
        "- API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3",
        "- Libraries: Laravel 12.x (/websites/laravel_12_x), Livewire 3.x (/livewire/livewire)",
        "",
        "CRITICAL RULES:",
        "- NO HARDCODING - wszystko konfigurowane",
        "- NO MOCK DATA - tylko prawdziwe struktury",
        "- Context7 MANDATORY przed kodem",
        "- Agents MUST create reports in _AGENT_REPORTS/",
        "",
        "PROJECT STATUS:",
        "- Completed: ETAP_01-07",
        "- Current: ETAP_08 ERP Integration",
        "",
        "AGENTS: 12 specialists available",
        "DEPLOYMENT: ppm.mpptrade.pl (Hostido)",
        "",
        "==================================="
    )

    $additionalContext = $contextLines -join "`n"

    # Zwroc JSON zgodnie z dokumentacja
    $result = @{
        hookSpecificOutput = @{
            hookEventName = "SessionStart"
            additionalContext = $additionalContext
        }
    } | ConvertTo-Json -Compress

    Write-Output $result
    exit 0

} catch {
    # Silent fail - nie blokuj sesji
    exit 0
}