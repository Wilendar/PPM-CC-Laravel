#!/usr/bin/env pwsh
# PPM-CC-Laravel Auto Context (SessionStart Hook)
# Automatycznie dodaje kontekst startowy do sesji
# Created: 2025-09-30
# UWAGA: SessionStart moze powodowac problemy z CLI - uzyj ostroznie

param()

$ErrorActionPreference = 'SilentlyContinue'

try {
    # Znajdz najnowszy raport
    $reportsPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_REPORTS"
    $latestReport = Get-ChildItem -Path $reportsPath -Filter "Podsumowanie_dnia_*.md" -File -ErrorAction SilentlyContinue |
                    Sort-Object LastWriteTime -Descending |
                    Select-Object -First 1

    if ($latestReport) {
        $reportPath = "_REPORTS\$($latestReport.Name)"
    } else {
        $reportPath = "_REPORTS\[BRAK NAJNOWSZEGO RAPORTU]"
    }

    # Przygotuj kontekst
    $context = @"
=== AUTO-START CONTEXT ===

ZADANIE: Kontynuuj prace nad projektem PPM-CC-Laravel zgodnie z planem.

WYMAGANE AKCJE NA START:
1. Przeczytaj najnowszy raport: $reportPath
2. Przeczytaj CLAUDE.md dla zasad projektu
3. Sprawdz Plan_Projektu/ dla aktualnego ETAPu
4. Przeczytaj _DOCS/Struktura_Bazy_Danych.md
5. Przeczytaj _DOCS/Struktura_Plikow_Projektu.md
6. Przeczytaj _DOCS/dane_hostingu.md

CRITICAL RULES:
- MCP Context7 MANDATORY
- NO HARDCODING, NO MOCK DATA
- ZAKAZ przeskakiwania punktow w planie
- Wykonuj zadania jedno po drugim
- Sam uruchamiaj Toolsy i weryfikuj na https://ppm.mpptrade.pl
- Aktualizuj dokumentacje przy zmianach

===========================
"@

    # Zwroc JSON z kontekstem
    $json = @{
        hookSpecificOutput = @{
            hookEventName = "SessionStart"
            additionalContext = $context
        }
    } | ConvertTo-Json -Compress -Depth 10

    Write-Output $json
    exit 0

} catch {
    # Silent fail
    $fallback = '{"hookSpecificOutput":{"hookEventName":"SessionStart","additionalContext":"PPM-CC-Laravel Project"}}'
    Write-Output $fallback
    exit 0
}