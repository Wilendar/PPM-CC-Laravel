#!/usr/bin/env pwsh
# PPM-CC-Laravel Context7 Enforcer (PreToolUse Hook)
# Wymusza uzywanie Context7 przed pisaniem kodu
# Created: 2025-09-30

$ErrorActionPreference = 'SilentlyContinue'

try {
    Write-Host ''
    Write-Host '⚠️ CODE MODIFICATION DETECTED' -ForegroundColor Yellow
    Write-Host ''
    Write-Host '📚 MANDATORY: Use Context7 MCP before writing code!' -ForegroundColor Red
    Write-Host ''
    Write-Host 'Quick Reference:' -ForegroundColor Cyan
    Write-Host '  • Laravel 12.x: mcp__context7__get-library-docs /websites/laravel_12_x' -ForegroundColor White
    Write-Host '  • Livewire 3.x: mcp__context7__get-library-docs /livewire/livewire' -ForegroundColor White
    Write-Host ''
    Write-Host 'Rules:' -ForegroundColor Yellow
    Write-Host '  • NO HARDCODING - use configurable patterns' -ForegroundColor White
    Write-Host '  • NO MOCK DATA - create real structures' -ForegroundColor White
    Write-Host '  • PSR-12, Enterprise patterns' -ForegroundColor White
    Write-Host ''

    exit 0
} catch {
    exit 0
}