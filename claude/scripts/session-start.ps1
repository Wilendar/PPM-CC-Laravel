#!/usr/bin/env pwsh
# PPM-CC-Laravel Session Start Hook
# Zgodny z dokumentacja Claude Code hooks
# Created: 2025-09-30
# Encoding: UTF-8

$ErrorActionPreference = 'Stop'

try {
    # Bezposredni zapis JSON - unikamy ConvertTo-Json ktory moze powodowac problemy
    $json = '{"hookSpecificOutput":{"hookEventName":"SessionStart","additionalContext":"=== PPM-CC-LARAVEL PROJECT CONTEXT ===\n\nMANDATORY DOCUMENTATION:\n- CLAUDE.md: Project rules & Context7 requirements\n- _DOCS/AGENT_USAGE_GUIDE.md: Agent delegation patterns\n- Plan_Projektu/: Current ETAP status & workflows\n\nCONTEXT7 MCP:\n- API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3\n- Libraries: Laravel 12.x (/websites/laravel_12_x), Livewire 3.x (/livewire/livewire)\n\nCRITICAL RULES:\n- NO HARDCODING - wszystko konfigurowane\n- NO MOCK DATA - tylko prawdziwe struktury\n- Context7 MANDATORY przed kodem\n- Agents MUST create reports in _AGENT_REPORTS/\n\nPROJECT STATUS:\n- Completed: ETAP_01-07\n- Current: ETAP_08 ERP Integration\n\nAGENTS: 12 specialists available\nDEPLOYMENT: ppm.mpptrade.pl (Hostido)\n\n==================================="}}'

    Write-Output $json
    exit 0

} catch {
    # Jesli wystapil blad, zwroc prosty JSON
    Write-Output '{"hookSpecificOutput":{"hookEventName":"SessionStart","additionalContext":"PPM-CC-Laravel Project"}}'
    exit 0
}