#!/usr/bin/env pwsh
# UserPromptSubmit Hook - Context Reminder + Recovery Check
# Shows recovery info on FIRST prompt if flag exists
# Created: 2025-10-30

$ErrorActionPreference = 'SilentlyContinue'

try {
    $flagPath = "_TEMP\.recovery_pending"
    $snapshotPath = "_TEMP\claude_session_state.json"

    # Check if recovery flag exists
    if (Test-Path $flagPath) {
        # Remove flag immediately (show only once)
        Remove-Item $flagPath -Force

        # Show full recovery info
        if (Test-Path $snapshotPath) {
            $snapshot = Get-Content $snapshotPath -Raw -Encoding UTF8 | ConvertFrom-Json
            $age = (Get-Date) - $snapshot.timestamp
            $ageMinutes = [math]::Round($age.TotalMinutes)

            Write-Host ''
            Write-Host '=== CLAUDE SESSION RECOVERY ===' -ForegroundColor Cyan
            Write-Host "Previous session detected from $ageMinutes minutes ago" -ForegroundColor Yellow
            Write-Host ''

            if ($snapshot.context_summary) {
                Write-Host "Context: $($snapshot.context_summary)" -ForegroundColor White
            }

            # Count tasks
            $completed = ($snapshot.todos | Where-Object {$_.status -eq 'completed'}).Count
            $inProgress = ($snapshot.todos | Where-Object {$_.status -eq 'in_progress'}).Count
            $pending = ($snapshot.todos | Where-Object {$_.status -eq 'pending'}).Count
            $total = $snapshot.todos.Count

            Write-Host ''
            Write-Host "TODO Status ($total total):" -ForegroundColor White
            Write-Host "  Completed: $completed | In Progress: $inProgress | Pending: $pending" -ForegroundColor Gray

            # Show interrupted task
            if ($inProgress -gt 0) {
                $interruptedTask = $snapshot.todos | Where-Object {$_.status -eq 'in_progress'} | Select-Object -First 1
                Write-Host ''
                Write-Host "Interrupted task:" -ForegroundColor Yellow
                Write-Host "  $($interruptedTask.content)" -ForegroundColor Yellow
            }

            # Show agent info
            if ($snapshot.agent_in_progress) {
                Write-Host "Agent: $($snapshot.agent_in_progress) (was in progress)" -ForegroundColor Magenta
            }

            Write-Host ''
            Write-Host 'OPTIONS:' -ForegroundColor Cyan
            Write-Host '  A) Continue from interruption' -ForegroundColor White
            Write-Host '  B) Update plan based on progress' -ForegroundColor White
            Write-Host '  C) Start new task (archive previous TODO)' -ForegroundColor White
            Write-Host '  D) Full context review (Plan + Reports)' -ForegroundColor White
            Write-Host ''
            Write-Host '===================================' -ForegroundColor Cyan
            Write-Host ''
        }
    }

    # Always show context reminder (original functionality)
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
