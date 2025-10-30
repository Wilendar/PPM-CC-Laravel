#!/usr/bin/env pwsh
# PPM-CC-Laravel Context Reminder (UserPromptSubmit Hook)
# Wyswietla kluczowe informacje kontekstu projektu
# Created: 2025-09-30

$ErrorActionPreference = 'SilentlyContinue'

try {
    Write-Host ''
    Write-Host '=== PPM-CC-LARAVEL PROJECT CONTEXT ===' -ForegroundColor Cyan
    Write-Host ''
    Write-Host '📚 MANDATORY DOCS:' -ForegroundColor Yellow
    Write-Host '  • CLAUDE.md - Project rules & Context7 requirements' -ForegroundColor White
    Write-Host '  • _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns' -ForegroundColor White
    Write-Host '  • Plan_Projektu/ - Current ETAP status & workflows' -ForegroundColor White
    Write-Host '  • _REPORTS/ - Latest project reports & status' -ForegroundColor White
    Write-Host ''
    Write-Host '🔑 CONTEXT7 MCP:' -ForegroundColor Green
    Write-Host '  • API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3' -ForegroundColor Gray
    Write-Host '  • Laravel 12.x: /websites/laravel_12_x' -ForegroundColor White
    Write-Host '  • Livewire 3.x: /livewire/livewire' -ForegroundColor White
    Write-Host ''
    Write-Host '⚠️ CRITICAL RULES:' -ForegroundColor Red
    Write-Host '  • NO HARDCODING - wszystko konfigurowane' -ForegroundColor White
    Write-Host '  • NO MOCK DATA - tylko prawdziwe struktury' -ForegroundColor White
    Write-Host '  • Context7 MANDATORY przed kodem' -ForegroundColor Yellow
    Write-Host '  • Agents MUST create reports in _AGENT_REPORTS/' -ForegroundColor White
    Write-Host ''
    Write-Host '🤖 AGENTS: 12 specialists available' -ForegroundColor Magenta
    Write-Host '🚀 DEPLOYMENT: ppm.mpptrade.pl (Hostido)' -ForegroundColor Blue
    Write-Host ''
    Write-Host '======================================' -ForegroundColor Cyan
    Write-Host ''

    exit 0
} catch {
    exit 0
}