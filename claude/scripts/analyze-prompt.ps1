# PPM-CC-Laravel Prompt Analyzer Hook
# Analizuje prompty użytkownika i dostarcza kontekstowe przypomnienia
# Created: 2025-09-29
# Encoding: UTF-8 with BOM

param()

# Ustawienia kodowania dla PowerShell
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

try {
    # Pobierz prompt użytkownika z zmiennej środowiskowej
    $prompt = $env:USER_PROMPT

    if ([string]::IsNullOrEmpty($prompt)) {
        return # Brak promptu do analizy
    }

    # Flaga czy znaleziono jakiekolwiek dopasowanie
    $foundMatch = $false

    # DETEKCJA 1: Słowa kluczowe agentów
    if ($prompt -match "(?i)(agent|subagent|deleguj|Task|ultrathink)") {
        Write-Host "🤖 AGENT DELEGATION DETECTED" -ForegroundColor Yellow
        Write-Host "• Review: _DOCS/AGENT_USAGE_GUIDE.md" -ForegroundColor White
        Write-Host "• Choose appropriate specialist agent" -ForegroundColor White
        Write-Host "• Agent MUST use Context7 before implementation" -ForegroundColor Cyan
        $foundMatch = $true
    }

    # DETEKCJA 2: Laravel/Backend context
    if ($prompt -match "(?i)(laravel|eloquent|migration|model|controller|artisan|php|backend)") {
        Write-Host "🔷 LARAVEL CONTEXT DETECTED" -ForegroundColor Blue
        Write-Host "• Use: laravel-expert agent" -ForegroundColor White
        Write-Host "• Context7: /websites/laravel_12_x" -ForegroundColor White
        Write-Host "• Remember: PSR-12, enterprise patterns" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 3: Livewire/Frontend context
    if ($prompt -match "(?i)(livewire|wire:|alpine|component|blade|frontend|ui)") {
        Write-Host "⚡ LIVEWIRE/FRONTEND CONTEXT DETECTED" -ForegroundColor Cyan
        Write-Host "• Use: livewire-specialist or frontend-specialist" -ForegroundColor White
        Write-Host "• Context7 Libraries:" -ForegroundColor White
        Write-Host "  - Livewire: /livewire/livewire" -ForegroundColor Gray
        Write-Host "  - Alpine.js: /alpinejs/alpine" -ForegroundColor Gray
        Write-Host "• Remember: dispatch() not emit(), wire:key in loops" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 4: PrestaShop context
    if ($prompt -match "(?i)(prestashop|api|sync|webhook|multi-store|sklep)") {
        Write-Host "🛒 PRESTASHOP CONTEXT DETECTED" -ForegroundColor Magenta
        Write-Host "• Use: prestashop-api-expert agent" -ForegroundColor White
        Write-Host "• Context7: /prestashop/docs" -ForegroundColor White
        Write-Host "• Remember: v8/v9 compatibility, multi-store support" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 5: ERP Integration context
    if ($prompt -match "(?i)(erp|baselinker|subiekt|dynamics|integracja|synchronizacja)") {
        Write-Host "🔌 ERP INTEGRATION CONTEXT DETECTED" -ForegroundColor Green
        Write-Host "• Use: erp-integration-expert agent" -ForegroundColor White
        Write-Host "• Context7: /websites/laravel_12_x" -ForegroundColor White
        Write-Host "• Current: ETAP_08 ERP Integration in progress" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 6: Import/Export context
    if ($prompt -match "(?i)(import|export|xlsx|excel|csv|data|column|mapping)") {
        Write-Host "📊 IMPORT/EXPORT CONTEXT DETECTED" -ForegroundColor DarkYellow
        Write-Host "• Use: import-export-specialist agent" -ForegroundColor White
        Write-Host "• Context7: /websites/laravel_12_x" -ForegroundColor White
        Write-Host "• Key columns: ORDER, Parts Name, U8 Code, MRF CODE" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 7: Deployment context
    if ($prompt -match "(?i)(deploy|ssh|hostido|upload|production|server)") {
        Write-Host "🚀 DEPLOYMENT CONTEXT DETECTED" -ForegroundColor Green
        Write-Host "• Use: deployment-specialist agent" -ForegroundColor White
        Write-Host "• Target: ppm.mpptrade.pl" -ForegroundColor White
        Write-Host "• SSH Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 8: Database/Migration context
    if ($prompt -match "(?i)(database|baza|migration|seed|sql|mysql|mariadb)") {
        Write-Host "🗄️ DATABASE CONTEXT DETECTED" -ForegroundColor DarkCyan
        Write-Host "• Use: laravel-expert agent for migrations" -ForegroundColor White
        Write-Host "• Remember: Enterprise patterns, proper relations" -ForegroundColor Gray
        Write-Host "• No hardcoding - configurable structures" -ForegroundColor Red
        $foundMatch = $true
    }

    # DETEKCJA 9: Context7 direct usage
    if ($prompt -match "(?i)(context7|mcp|documentation|docs|library)") {
        Write-Host "📚 CONTEXT7/DOCUMENTATION CONTEXT DETECTED" -ForegroundColor Yellow
        Write-Host "• Great! Context7 awareness detected" -ForegroundColor Green
        Write-Host "• Available libraries: Laravel, Livewire, Alpine, PrestaShop" -ForegroundColor White
        $foundMatch = $true
    }

    # DETEKCJA 10: ETAP/Planning context
    if ($prompt -match "(?i)(etap|plan|projekt|architektura|planning)") {
        Write-Host "📋 PROJECT PLANNING CONTEXT DETECTED" -ForegroundColor Cyan
        Write-Host "• Use: architect agent" -ForegroundColor White
        Write-Host "• Check: Plan_Projektu/ directory" -ForegroundColor White
        Write-Host "• Current: ETAP_08 ERP Integration" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 11: Debug/Problem context
    if ($prompt -match "(?i)(bug|error|problem|debug|fix|błąd|problem|naprawa)") {
        Write-Host "🐛 DEBUGGING CONTEXT DETECTED" -ForegroundColor Red
        Write-Host "• Use: debugger agent (Opus model)" -ForegroundColor White
        Write-Host "• Systematic diagnostic approach" -ForegroundColor White
        Write-Host "• Check: _ISSUES_FIXES/ for known problems" -ForegroundColor Gray
        $foundMatch = $true
    }

    # DETEKCJA 12: Code quality/Review context
    if ($prompt -match "(?i)(review|quality|standards|compliance|code|style)") {
        Write-Host "🎨 CODE QUALITY CONTEXT DETECTED" -ForegroundColor Magenta
        Write-Host "• Use: coding-style-agent (MANDATORY before completion)" -ForegroundColor White
        Write-Host "• Context7 integration required" -ForegroundColor White
        Write-Host "• PSR-12, Laravel conventions, enterprise patterns" -ForegroundColor Gray
        $foundMatch = $true
    }

    # OSTRZEŻENIE: Hardcoding detection
    if ($prompt -match "(?i)(hardcode|fake|mock|test.*data|przykład|próbny)") {
        Write-Host "⚠️ POTENTIAL HARDCODING DETECTED" -ForegroundColor Red
        Write-Host "• WARNING: No hardcoding allowed in PPM-CC-Laravel!" -ForegroundColor Red
        Write-Host "• Create configurable structures instead" -ForegroundColor Yellow
        Write-Host "• No mock/fake data - use real data structures" -ForegroundColor Yellow
        $foundMatch = $true
    }

    # Jeśli nic nie zostało wykryte, daj ogólne przypomnienie
    if (-not $foundMatch -and $prompt.Length -gt 10) {
        Write-Host "💡 GENERAL REMINDER:" -ForegroundColor Yellow
        Write-Host "• Consider using specialist agents for complex tasks" -ForegroundColor White
        Write-Host "• Context7 required for code generation" -ForegroundColor White
        Write-Host "• Check CLAUDE.md and _DOCS/AGENT_USAGE_GUIDE.md" -ForegroundColor Gray
    }

} catch {
    Write-Host "❌ Error in analyze-prompt.ps1: $($_.Exception.Message)" -ForegroundColor Red
}