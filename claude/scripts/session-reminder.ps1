# PPM-CC-Laravel Session Reminder Hook
# Automatyczne przypominanie o zasadach projektu przy starcie sesji
# Created: 2025-09-29
# Encoding: UTF-8 with BOM

param()

# Ustawienia kodowania dla PowerShell
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

try {
    # Ustaw working directory na katalog projektu
    $ProjectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
    Set-Location $ProjectRoot

    # Header sesji
    Write-Host "🚀 PPM-CC-LARAVEL SESSION START" -ForegroundColor Cyan
    Write-Host ("=" * 70) -ForegroundColor Cyan

    # Dokumentacja obowiązkowa
    Write-Host "`n📚 MANDATORY DOCUMENTATION:" -ForegroundColor Yellow
    Write-Host "• CLAUDE.md - Project rules & Context7 requirements" -ForegroundColor White
    Write-Host "• _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns" -ForegroundColor White
    Write-Host "• Plan_Projektu/ - Current ETAP status & workflows" -ForegroundColor White

    # Status Context7 MCP
    Write-Host "`n🔧 CONTEXT7 MCP STATUS:" -ForegroundColor Yellow
    Write-Host "• API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3" -ForegroundColor Green
    Write-Host "• Required Libraries:" -ForegroundColor White
    Write-Host "  - Laravel 12.x: /websites/laravel_12_x" -ForegroundColor Gray
    Write-Host "  - Livewire 3.x: /livewire/livewire" -ForegroundColor Gray
    Write-Host "  - Alpine.js: /alpinejs/alpine" -ForegroundColor Gray
    Write-Host "  - PrestaShop: /prestashop/docs" -ForegroundColor Gray

    # Krytyczne zasady projektu
    Write-Host "`n⚠️ CRITICAL PROJECT RULES:" -ForegroundColor Red
    Write-Host "• NO HARDCODING - wszystko konfigurowane przez admin" -ForegroundColor White
    Write-Host "• NO MOCK DATA - tworz prawdziwe struktury danych" -ForegroundColor White
    Write-Host "• Context7 MANDATORY przed generowaniem kodu" -ForegroundColor White
    Write-Host "• Agents MUST create reports in _AGENT_REPORTS/" -ForegroundColor White
    Write-Host "• Enterprise patterns only - no shortcuts" -ForegroundColor White

    # Status projektu - bez czytania dużych plików (optymalizacja)
    Write-Host "`n📊 PROJECT STATUS:" -ForegroundColor Yellow
    Write-Host "• Check Plan_Projektu/ for current ETAP status" -ForegroundColor White
    Write-Host "• Completed: ETAP_01-07 ✅" -ForegroundColor Green
    Write-Host "• Current focus: ETAP_08 ERP Integration" -ForegroundColor Cyan

    # System agentów
    Write-Host "`n🤖 AGENT SYSTEM:" -ForegroundColor Yellow
    Write-Host "• Total Agents: 12 specialists" -ForegroundColor White
    Write-Host "• All agents require Context7 before implementation" -ForegroundColor White
    Write-Host "• Check _DOCS/AGENT_USAGE_GUIDE.md for delegation patterns" -ForegroundColor White

    # Deployment info
    Write-Host "`n🚀 DEPLOYMENT:" -ForegroundColor Yellow
    Write-Host "• Target: ppm.mpptrade.pl (Hostido)" -ForegroundColor White
    Write-Host "• SSH: host379076@host379076.hostido.net.pl:64321" -ForegroundColor Gray
    Write-Host "• Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -ForegroundColor Gray

    # Końcowa separacja
    Write-Host ("=" * 70) -ForegroundColor Cyan
    Write-Host "✅ Session initialized - follow enterprise patterns!" -ForegroundColor Green
    Write-Host ""

} catch {
    Write-Host "❌ Error in session-reminder.ps1: $($_.Exception.Message)" -ForegroundColor Red
}