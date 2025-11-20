# -*- coding: utf-8 -*-
# PPM-CC-Laravel: Session Start Rules Reminder Hook (PowerShell)
# ================================================================
# Przypomina Claude o kluczowych zasadach projektu przy każdym starcie sesji.
# Wymaga potwierdzenia zapoznania się z zasadami.

# Ustaw encoding UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# Konsumuj stdin żeby uniknąć deadlock
$null = $input

# Funkcja wyświetlająca kolorowy separator
function Write-Separator {
    Write-Host ("=" * 80) -ForegroundColor Cyan
}

# Data i czas sesji
$sessionTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

# Header
Write-Host ""
Write-Separator
Write-Host "🚀 PPM-CC-LARAVEL SESSION START - MANDATORY RULES ACKNOWLEDGMENT" -ForegroundColor Cyan
Write-Separator
Write-Host ""
Write-Host "📅 Session start: $sessionTime" -ForegroundColor White
Write-Host ""

# 🔴 KATEGORYCZNE ZAKAZY
Write-Separator
Write-Host "🔴 KATEGORYCZNE ZAKAZY" -ForegroundColor Red
Write-Separator
Write-Host "  ❌ NO HARDCODING - wszystko konfigurowane przez admin" -ForegroundColor Red
Write-Host "  ❌ NO MOCK DATA - tylko prawdziwe struktury danych" -ForegroundColor Red
Write-Host "  ❌ NO INLINE STYLES - zawsze CSS classes (kategoryczny zakaz!)" -ForegroundColor Red
Write-Host "  ❌ NO NEW CSS FILES bez konsultacji - dodawaj do istniejacych" -ForegroundColor Red
Write-Host "  ❌ NO SKIPPING Context7 - MANDATORY przed każda implementacja" -ForegroundColor Red
Write-Host ""

# ⚠️ OBOWIĄZKOWE WORKFLOW
Write-Separator
Write-Host "⚠️ OBOWIĄZKOWE WORKFLOW" -ForegroundColor Yellow
Write-Separator
Write-Host "  ✅ Context7 docs lookup PRZED implementacja (mcp__context7__get-library-docs)" -ForegroundColor Green
Write-Host "  ✅ Agent reports MANDATORY w _AGENT_REPORTS/ po ukończeniu pracy" -ForegroundColor Green
Write-Host "  ✅ Frontend verification MANDATORY przed informowaniem użytkownika" -ForegroundColor Green
Write-Host "  ✅ PPM Verification Tool (_TOOLS/full_console_test.cjs) po UI changes" -ForegroundColor Green
Write-Host "  ✅ Deployment checklist: npm run build → upload ALL assets → manifest ROOT → cache clear" -ForegroundColor Green
Write-Host ""

# 🏗️ VITE & BUILD ARCHITECTURE
Write-Separator
Write-Host "🏗️ VITE & BUILD ARCHITECTURE" -ForegroundColor Yellow
Write-Separator
Write-Host "  ⚠️ Vite działa TYLKO lokalnie (brak Node.js na produkcji!)" -ForegroundColor Yellow
Write-Host "  ⚠️ Deploy WSZYSTKIE pliki z public/build/assets/ (nie tylko zmienione!)" -ForegroundColor Yellow
Write-Host "  ⚠️ Upload manifest do ROOT: public/build/manifest.json (nie .vite/!)" -ForegroundColor Yellow
Write-Host "  ⚠️ HTTP 200 verification MANDATORY dla wszystkich CSS po deployment" -ForegroundColor Yellow
Write-Host ""

# 🤖 SYSTEM AGENTÓW
Write-Separator
Write-Host "🤖 SYSTEM AGENTÓW" -ForegroundColor Cyan
Write-Separator
Write-Host "  ✅ 13 specjalistycznych agentów dostępnych (.claude/agents/)" -ForegroundColor Green
Write-Host "  ✅ TYLKO JEDEN agent in_progress jednocześnie" -ForegroundColor Green
Write-Host "  ✅ Agents MUST create reports w _AGENT_REPORTS/" -ForegroundColor Green
Write-Host "  ✅ coding-style-agent PRZED completion (ZAWSZE)" -ForegroundColor Green
Write-Host ""

# 📏 QUALITY STANDARDS
Write-Separator
Write-Host "📏 QUALITY STANDARDS" -ForegroundColor Cyan
Write-Separator
Write-Host "  ✅ Max 300 linii per file (idealnie 150-200, wyjątkowo 500)" -ForegroundColor Green
Write-Host "  ✅ Separation of concerns - models, logic, UI, config w oddzielnych plikach" -ForegroundColor Green
Write-Host "  ✅ Enterprise class - bez skrótów, pełna walidacja, error handling" -ForegroundColor Green
Write-Host "  ✅ ZAWSZE aktualizuj TODO list podczas pracy" -ForegroundColor Green
Write-Host ""

# 🚀 DEPLOYMENT INFORMATION
Write-Separator
Write-Host "🚀 DEPLOYMENT INFORMATION" -ForegroundColor Magenta
Write-Separator
Write-Host "  🚀 Domena: ppm.mpptrade.pl" -ForegroundColor White
Write-Host "  🔑 SSH: host379076@host379076.hostido.net.pl:64321" -ForegroundColor White
Write-Host "  🔐 Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -ForegroundColor White
Write-Host "  📁 Path: domains/ppm.mpptrade.pl/public_html/" -ForegroundColor White
Write-Host "  👤 Admin: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor White
Write-Host ""

# 📚 CONTEXT7 MCP INTEGRATION
Write-Separator
Write-Host "📚 CONTEXT7 MCP INTEGRATION" -ForegroundColor Cyan
Write-Separator
Write-Host "  API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3" -ForegroundColor White
Write-Host "  Libraries:" -ForegroundColor White
Write-Host "    • Laravel 12.x: /websites/laravel_12_x (4927 snippets)" -ForegroundColor Cyan
Write-Host "    • Livewire 3.x: /livewire/livewire (867 snippets)" -ForegroundColor Cyan
Write-Host "    • Alpine.js: /alpinejs/alpine (364 snippets)" -ForegroundColor Cyan
Write-Host "    • PrestaShop: /prestashop/docs (3289 snippets)" -ForegroundColor Cyan
Write-Host ""

# 📖 ESSENTIAL DOCUMENTATION
Write-Separator
Write-Host "📖 ESSENTIAL DOCUMENTATION" -ForegroundColor Cyan
Write-Separator
Write-Host "  📖 CLAUDE.md - COMPLETE project rules (MUST READ!)" -ForegroundColor Cyan
Write-Host "  📖 _DOCS/dane_hostingu.md - SSH & credentials" -ForegroundColor Cyan
Write-Host "  📖 _DOCS/DEPLOYMENT_GUIDE.md - Complete deployment workflow" -ForegroundColor Cyan
Write-Host "  📖 _DOCS/FRONTEND_VERIFICATION_GUIDE.md - UI testing mandatory" -ForegroundColor Cyan
Write-Host "  📖 _DOCS/CSS_STYLING_GUIDE.md - Style rules & inline styles ban" -ForegroundColor Cyan
Write-Host "  📖 _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns" -ForegroundColor Cyan
Write-Host "  📖 Plan_Projektu/ - Current ETAP status" -ForegroundColor Cyan
Write-Host "  📖 _AGENT_REPORTS/ - Latest agent work reports" -ForegroundColor Cyan
Write-Host ""

# ⚠️ MANDATORY ACKNOWLEDGMENT REQUIRED
Write-Separator
Write-Host "⚠️ MANDATORY ACKNOWLEDGMENT REQUIRED" -ForegroundColor Red
Write-Separator
Write-Host ""
Write-Host "🔴 CLAUDE MUST CONFIRM:" -ForegroundColor Red
Write-Host ""
Write-Host "  1. ✅ Przeczytałem i zrozumiałem wszystkie powyższe zasady" -ForegroundColor Green
Write-Host "  2. ✅ Będę stosować się do WSZYSTKICH zasad podczas tej sesji" -ForegroundColor Green
Write-Host "  3. ✅ Szczególnie będę pamiętać o:" -ForegroundColor Green
Write-Host "      • Context7 MANDATORY przed każda implementacja" -ForegroundColor Yellow
Write-Host "      • NO HARDCODING, NO MOCK DATA" -ForegroundColor Yellow
Write-Host "      • NO INLINE STYLES - kategoryczny zakaz" -ForegroundColor Yellow
Write-Host "      • Frontend verification MANDATORY" -ForegroundColor Yellow
Write-Host "      • Agent reports MANDATORY" -ForegroundColor Yellow
Write-Host "      • TODO list updates during work" -ForegroundColor Yellow
Write-Host ""

Write-Separator
Write-Host "🤖 RESPOND IN YOUR FIRST MESSAGE:" -ForegroundColor Green
Write-Separator
Write-Host ""
Write-Host '  "✅ POTWIERDZAM ZAPOZNANIE Z ZASADAMI PPM-CC-LARAVEL"' -ForegroundColor Green
Write-Host '  "Będę stosować wszystkie reguły z CLAUDE.md podczas tej sesji."' -ForegroundColor Green
Write-Host ""
Write-Separator
Write-Host ""

# Final status do stderr (widoczne w CLI użytkownika)
[Console]::Error.WriteLine("")
[Console]::Error.WriteLine("=" * 70)
[Console]::Error.WriteLine("✅ SESSION START HOOK EXECUTED (PowerShell)")
[Console]::Error.WriteLine("=" * 70)
[Console]::Error.WriteLine("📋 Claude received full PPM-CC-Laravel rules reminder")
[Console]::Error.WriteLine("🔴 5 critical rules sections loaded")
[Console]::Error.WriteLine("🚀 Deployment info provided")
[Console]::Error.WriteLine("📚 Context7 configuration loaded")
[Console]::Error.WriteLine("📖 8 essential docs referenced")
[Console]::Error.WriteLine("⚠️  Waiting for Claude's acknowledgment in first response...")
[Console]::Error.WriteLine("=" * 70)
[Console]::Error.WriteLine("")

exit 0
