#!/usr/bin/env pwsh
# PPM-CC-Laravel Startup Prompt Generator
# Generuje gotowy prompt do uruchomienia projektu
# Created: 2025-09-30

param()

$ErrorActionPreference = 'Stop'

try {
    # Znajdz najnowszy raport Podsumowanie_dnia
    $reportsPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_REPORTS"
    $latestReport = Get-ChildItem -Path $reportsPath -Filter "Podsumowanie_dnia_*.md" -File |
                    Sort-Object LastWriteTime -Descending |
                    Select-Object -First 1

    if ($latestReport) {
        $reportName = $latestReport.Name
        $reportDate = $latestReport.LastWriteTime.ToString("yyyy-MM-dd HH:mm")
    } else {
        $reportName = "BRAK RAPORTU"
        $reportDate = "N/A"
    }

    # Generuj prompt
    $prompt = @"

╔════════════════════════════════════════════════════════════════╗
║           PPM-CC-LARAVEL STARTUP PROMPT                        ║
╚════════════════════════════════════════════════════════════════╝

Skopiuj ponizszy prompt do Claude Code:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ultrathink Kontynuuj prace nad projektem zgodnie z planem.

KROK 1 - ZAPOZNAJ SIE Z AKTUALNYM STATUSEM:
• Przeczytaj najnowszy raport: @_REPORTS\$reportName
  (ostatnia aktualizacja: $reportDate)
• Przeczytaj zasady projektu: @CLAUDE.md
• Sprawdz aktualny ETAP: @Plan_Projektu/

KROK 2 - ZAPOZNAJ SIE ZE STRUKTURA:
• Przeczytaj: @_DOCS\Struktura_Bazy_Danych.md
• Przeczytaj: @_DOCS\Struktura_Plikow_Projektu.md
• Przeczytaj: @_DOCS\dane_hostingu.md

KROK 3 - ZASADY PRACY:
⚠️ KONIECZNE: Stosowanie MCP Context7 w tym projekcie
⚠️ ZAKAZ: Przeskakiwania/omijania punktow w planie
⚠️ WYMAGANE: Aktualizowanie dokumentacji przy zmianach
⚠️ WYMAGANE: Sam uruchamiaj Toolsy i weryfikuj dzialanie
⚠️ WYMAGANE: Testuj kod na stronie: https://ppm.mpptrade.pl

KROK 4 - ROZPOCZNIJ PRACE:
Wykonuj zadania jedno po drugim zgodnie z Planem_Projektu/.
Wyjatkiem jest zaleznosc od innego zadania/ETAPu.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

"@

    Write-Host $prompt -ForegroundColor Cyan

    # Zapisz do pliku
    $promptFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\STARTUP_PROMPT.txt"
    $prompt | Out-File -FilePath $promptFile -Encoding utf8 -Force

    Write-Host ""
    Write-Host "✅ Prompt zapisany do: .claude\STARTUP_PROMPT.txt" -ForegroundColor Green
    Write-Host ""

    exit 0

} catch {
    Write-Host "❌ Blad generowania prompta: $($_.Exception.Message)" -ForegroundColor Red
    exit 0
}