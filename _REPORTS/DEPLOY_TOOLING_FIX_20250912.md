# RAPORT PRACY AGENTA: DEPLOY_TOOLING_FIX
**Data**: 2025-09-12 13:54
**Zadanie**: Poprawa skryptu deploy (vendor exclude, prawdziwy DryRun, fix sciezek), aktualizacja AGENTS.md i wykonanie natychmiastowego deployu

## ✅ WYKONANE PRACE
- `_TOOLS/hostido_deploy.ps1`: naprawa `Setup-HostidoDirectories` (sciezki bez wiodacego `/`)
- `_TOOLS/hostido_deploy.ps1`: dodano `vendor/*` do wykluczen synchronizacji WinSCP
- `_TOOLS/hostido_deploy.ps1`: dodano obsluge `-DryRun` w `Deploy-ToHostido` i `Invoke-PostDeployCommands`
- `AGENTS.md`: zaktualizowano procedurę minimalną (dodano `composer install --no-dev`), dodano instrukcje DryRun i notatkę o wykluczeniu `vendor/*`
- Wykonano pełny deploy z backupem i naprawą vendor na produkcji

## ⚠️ PROBLEMY/BLOKERY
- `composer install` po uploadzie moze nie powiesc sie, jesli nie istnieja katalogi `storage/**` i `bootstrap/cache` (artisan scripts). Rozwiązane przez utworzenie katalogów przed `composer install`.

## 📋 NASTĘPNE KROKI
- Opcjonalnie: Przenieść wykonanie custom `-Command` przed post-deploy w skrypcie (porzadek operacji). Aktualnie obejściem jest tworzenie katalogów w samej komendzie.
- Dodać krótką checklistę smoke-testów do `_TOOLS/` (na życzenie).

## 📁 PLIKI
- _TOOLS/hostido_deploy.ps1 - vendor exclude, DryRun guard, fix ścieżek
- AGENTS.md - procedura minimalna + DryRun + vendor exclude
- _REPORTS/DEPLOY_TOOLING_FIX_20250912.md - niniejszy raport
