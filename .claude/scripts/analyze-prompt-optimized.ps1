#!/usr/bin/env pwsh
# PPM-CC-Laravel Prompt Analyzer Hook - Optimized
# Analizuje prompty uzytkownika i dostarcza kontekstowe przypomnienia
# Created: 2025-09-30
# Encoding: UTF-8

$ErrorActionPreference = 'SilentlyContinue'

try {
    # Pobierz prompt uzytkownika z zmiennej srodowiskowej
    $prompt = $env:USER_PROMPT

    if ([string]::IsNullOrEmpty($prompt) -or $prompt.Length -lt 10) {
        exit 0 # Brak promptu do analizy
    }

    # DETEKCJA 1: Agent delegation
    if ($prompt -match '(?i)(agent|subagent|deleguj|Task|ultrathink)') {
        Write-Host '🤖 AGENT DELEGATION DETECTED' -ForegroundColor Yellow
        Write-Host '• Review: _DOCS/AGENT_USAGE_GUIDE.md' -ForegroundColor White
        Write-Host '• Agent MUST use Context7 before implementation' -ForegroundColor Cyan
    }

    # DETEKCJA 2: Laravel/Backend
    elseif ($prompt -match '(?i)(laravel|eloquent|migration|model|controller|artisan|php|backend)') {
        Write-Host '🔷 LARAVEL CONTEXT DETECTED' -ForegroundColor Blue
        Write-Host '• Use: laravel-expert agent' -ForegroundColor White
        Write-Host '• Context7: /websites/laravel_12_x' -ForegroundColor White
    }

    # DETEKCJA 3: Livewire/Frontend
    elseif ($prompt -match '(?i)(livewire|wire:|alpine|component|blade|frontend|ui)') {
        Write-Host '⚡ LIVEWIRE/FRONTEND DETECTED' -ForegroundColor Cyan
        Write-Host '• Use: livewire-specialist or frontend-specialist' -ForegroundColor White
        Write-Host '• Context7: /livewire/livewire' -ForegroundColor White
    }

    # DETEKCJA 4: PrestaShop
    elseif ($prompt -match '(?i)(prestashop|api|sync|webhook|multi-store)') {
        Write-Host '🛒 PRESTASHOP DETECTED' -ForegroundColor Magenta
        Write-Host '• Use: prestashop-api-expert agent' -ForegroundColor White
        Write-Host '• Context7: /prestashop/docs' -ForegroundColor White
    }

    # DETEKCJA 5: ERP Integration
    elseif ($prompt -match '(?i)(erp|baselinker|subiekt|dynamics|integracja)') {
        Write-Host '🔌 ERP INTEGRATION DETECTED' -ForegroundColor Green
        Write-Host '• Use: erp-integration-expert agent' -ForegroundColor White
        Write-Host '• Current: ETAP_08 ERP Integration in progress' -ForegroundColor Gray
    }

    # DETEKCJA 6: Import/Export
    elseif ($prompt -match '(?i)(import|export|xlsx|excel|csv|data|mapping)') {
        Write-Host '📊 IMPORT/EXPORT DETECTED' -ForegroundColor DarkYellow
        Write-Host '• Use: import-export-specialist agent' -ForegroundColor White
        Write-Host '• Context7: /websites/laravel_12_x' -ForegroundColor White
    }

    # DETEKCJA 7: Deployment
    elseif ($prompt -match '(?i)(deploy|ssh|hostido|upload|production|server)') {
        Write-Host '🚀 DEPLOYMENT DETECTED' -ForegroundColor Green
        Write-Host '• Use: deployment-specialist agent' -ForegroundColor White
        Write-Host '• Target: ppm.mpptrade.pl' -ForegroundColor White
    }

    # DETEKCJA 8: Database
    elseif ($prompt -match '(?i)(database|baza|migration|seed|sql|mysql)') {
        Write-Host '🗄️ DATABASE DETECTED' -ForegroundColor DarkCyan
        Write-Host '• Use: laravel-expert agent for migrations' -ForegroundColor White
        Write-Host '• No hardcoding - configurable structures' -ForegroundColor Red
    }

    # DETEKCJA 9: Debug/Problem
    elseif ($prompt -match '(?i)(bug|error|problem|debug|fix|blad|naprawa)') {
        Write-Host '🐛 DEBUGGING DETECTED' -ForegroundColor Red
        Write-Host '• Use: debugger agent (Opus model)' -ForegroundColor White
        Write-Host '• Check: _ISSUES_FIXES/ for known problems' -ForegroundColor Gray
    }

    # DETEKCJA 10: Code quality
    elseif ($prompt -match '(?i)(review|quality|standards|compliance|style)') {
        Write-Host '🎨 CODE QUALITY DETECTED' -ForegroundColor Magenta
        Write-Host '• Use: coding-style-agent (MANDATORY before completion)' -ForegroundColor White
        Write-Host '• Context7 integration required' -ForegroundColor White
    }

    # OSTRZEZENIE: Hardcoding detection
    if ($prompt -match '(?i)(hardcode|fake|mock|test.*data|przyklad|probny)') {
        Write-Host '⚠️ POTENTIAL HARDCODING DETECTED' -ForegroundColor Red
        Write-Host '• WARNING: No hardcoding allowed!' -ForegroundColor Red
        Write-Host '• Create configurable structures instead' -ForegroundColor Yellow
    }

    exit 0

} catch {
    # Silent fail - nie blokuj sesji
    exit 0
}