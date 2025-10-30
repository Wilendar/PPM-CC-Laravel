#!/usr/bin/env pwsh
# PPM-CC-Laravel Agent Report Reminder (PostToolUse Hook)
# Przypomina o tworzeniu raportow agentow
# Created: 2025-09-30

$ErrorActionPreference = 'SilentlyContinue'

try {
    Write-Host ''
    Write-Host '🤖 AGENT TASK DELEGATED' -ForegroundColor Green
    Write-Host ''
    Write-Host 'Agent Requirements:' -ForegroundColor Yellow
    Write-Host '  ✓ MUST use Context7 before implementation' -ForegroundColor White
    Write-Host '  ✓ MUST create report in _AGENT_REPORTS/' -ForegroundColor White
    Write-Host '  ✓ Check _DOCS/AGENT_USAGE_GUIDE.md' -ForegroundColor White
    Write-Host ''
    Write-Host 'Available:' -ForegroundColor Cyan
    Write-Host '  • laravel-expert, livewire-specialist' -ForegroundColor Gray
    Write-Host '  • prestashop-api-expert, erp-integration-expert' -ForegroundColor Gray
    Write-Host '  • debugger (Opus), architect, coding-style-agent' -ForegroundColor Gray
    Write-Host ''

    exit 0
} catch {
    exit 0
}