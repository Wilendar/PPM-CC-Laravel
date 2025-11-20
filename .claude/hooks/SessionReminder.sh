#!/bin/bash
# PPM-CC-Laravel: Session Start Rules Reminder Hook
# ==================================================
# Przypomina Claude o kluczowych zasadach projektu przy każdym starcie sesji.
# Wymaga potwierdzenia zapoznania się z zasadami.

# Kolory ANSI dla terminala
CYAN='\033[96m'
GREEN='\033[92m'
YELLOW='\033[93m'
RED='\033[91m'
MAGENTA='\033[95m'
WHITE='\033[97m'
BOLD='\033[1m'
RESET='\033[0m'

# Konsumuj stdin żeby uniknąć deadlock (Claude wysyła JSON session info)
# Ignoruj błędy permission denied w WSL
cat > /dev/null 2>&1 || true

# KRYTYCZNE: Pisz bezpośrednio do TTY aby ominąć przechwytywanie Claude Code
# Funkcja wyświetlająca kolorowy tekst - próbuje /dev/tty, silent fallback do stderr
print_colored() {
    local text="$1"
    local color="$2"

    # Próbuj pisać do /dev/tty (ominąć przechwytywanie Claude Code), silent fallback do stderr
    {
        echo -e "${color}${text}${RESET}" > /dev/tty
    } 2>/dev/null || {
        echo -e "${color}${text}${RESET}" >&2
    }
}

# Funkcja wyświetlająca separator
print_separator() {
    print_colored "================================================================================" "$CYAN"
}

# Data i czas sesji
SESSION_TIME=$(date '+%Y-%m-%d %H:%M:%S')

# Funkcja echo do TTY - próbuje /dev/tty, silent fallback do stderr
echo_tty() {
    {
        echo "$@" > /dev/tty
    } 2>/dev/null || {
        echo "$@" >&2
    }
}

# Header
echo_tty ""
print_separator
print_colored "🚀 PPM-CC-LARAVEL SESSION START - MANDATORY RULES ACKNOWLEDGMENT" "${BOLD}${CYAN}"
print_separator
echo_tty ""
print_colored "📅 Session start: $SESSION_TIME" "$WHITE"
echo_tty ""

# 🔴 KATEGORYCZNE ZAKAZY
print_separator
print_colored "🔴 KATEGORYCZNE ZAKAZY" "${BOLD}${RED}"
print_separator
print_colored "  ❌ NO HARDCODING - wszystko konfigurowane przez admin" "$RED"
print_colored "  ❌ NO MOCK DATA - tylko prawdziwe struktury danych" "$RED"
print_colored "  ❌ NO INLINE STYLES - zawsze CSS classes (kategoryczny zakaz!)" "$RED"
print_colored "  ❌ NO NEW CSS FILES bez konsultacji - dodawaj do istniejących" "$RED"
print_colored "  ❌ NO SKIPPING Context7 - MANDATORY przed każdą implementacją" "$RED"
echo_tty ""

# ⚠️ OBOWIĄZKOWE WORKFLOW
print_separator
print_colored "⚠️ OBOWIĄZKOWE WORKFLOW" "${BOLD}${YELLOW}"
print_separator
print_colored "  ✅ Context7 docs lookup PRZED implementacją (mcp__context7__get-library-docs)" "$GREEN"
print_colored "  ✅ Agent reports MANDATORY w _AGENT_REPORTS/ po ukończeniu pracy" "$GREEN"
print_colored "  ✅ Frontend verification MANDATORY przed informowaniem użytkownika" "$GREEN"
print_colored "  ✅ PPM Verification Tool (_TOOLS/full_console_test.cjs) po UI changes" "$GREEN"
print_colored "  ✅ Deployment checklist: npm run build → upload ALL assets → manifest ROOT → cache clear" "$GREEN"
echo_tty ""

# 🏗️ VITE & BUILD ARCHITECTURE
print_separator
print_colored "🏗️ VITE & BUILD ARCHITECTURE" "${BOLD}${YELLOW}"
print_separator
print_colored "  ⚠️ Vite działa TYLKO lokalnie (brak Node.js na produkcji!)" "$YELLOW"
print_colored "  ⚠️ Deploy WSZYSTKIE pliki z public/build/assets/ (nie tylko zmienione!)" "$YELLOW"
print_colored "  ⚠️ Upload manifest do ROOT: public/build/manifest.json (nie .vite/!)" "$YELLOW"
print_colored "  ⚠️ HTTP 200 verification MANDATORY dla wszystkich CSS po deployment" "$YELLOW"
echo_tty ""

# 🤖 SYSTEM AGENTÓW
print_separator
print_colored "🤖 SYSTEM AGENTÓW" "${BOLD}${CYAN}"
print_separator
print_colored "  ✅ 13 specjalistycznych agentów dostępnych (.claude/agents/)" "$GREEN"
print_colored "  ✅ TYLKO JEDEN agent in_progress jednocześnie" "$GREEN"
print_colored "  ✅ Agents MUST create reports w _AGENT_REPORTS/" "$GREEN"
print_colored "  ✅ coding-style-agent PRZED completion (ZAWSZE)" "$GREEN"
echo_tty ""

# 📏 QUALITY STANDARDS
print_separator
print_colored "📏 QUALITY STANDARDS" "${BOLD}${CYAN}"
print_separator
print_colored "  ✅ Max 300 linii per file (idealnie 150-200, wyjątkowo 500)" "$GREEN"
print_colored "  ✅ Separation of concerns - models, logic, UI, config w oddzielnych plikach" "$GREEN"
print_colored "  ✅ Enterprise class - bez skrótów, pełna walidacja, error handling" "$GREEN"
print_colored "  ✅ ZAWSZE aktualizuj TODO list podczas pracy" "$GREEN"
echo_tty ""

# 🚀 DEPLOYMENT INFORMATION
print_separator
print_colored "🚀 DEPLOYMENT INFORMATION" "${BOLD}${MAGENTA}"
print_separator
print_colored "  🚀 Domena: ppm.mpptrade.pl" "$WHITE"
print_colored "  🔑 SSH: host379076@host379076.hostido.net.pl:64321" "$WHITE"
print_colored "  🔐 Key: D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk" "$WHITE"
print_colored "  📁 Path: domains/ppm.mpptrade.pl/public_html/" "$WHITE"
print_colored "  👤 Admin: admin@mpptrade.pl / Admin123!MPP" "$WHITE"
echo_tty ""

# 📚 CONTEXT7 MCP INTEGRATION
print_separator
print_colored "📚 CONTEXT7 MCP INTEGRATION" "${BOLD}${CYAN}"
print_separator
print_colored "  API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3" "$WHITE"
print_colored "  Libraries:" "$WHITE"
print_colored "    • Laravel 12.x: /websites/laravel_12_x (4927 snippets)" "$CYAN"
print_colored "    • Livewire 3.x: /livewire/livewire (867 snippets)" "$CYAN"
print_colored "    • Alpine.js: /alpinejs/alpine (364 snippets)" "$CYAN"
print_colored "    • PrestaShop: /prestashop/docs (3289 snippets)" "$CYAN"
echo_tty ""

# 📖 ESSENTIAL DOCUMENTATION
print_separator
print_colored "📖 ESSENTIAL DOCUMENTATION" "${BOLD}${CYAN}"
print_separator
print_colored "  📖 CLAUDE.md - COMPLETE project rules (MUST READ!)" "$CYAN"
print_colored "  📖 _DOCS/dane_hostingu.md - SSH & credentials" "$CYAN"
print_colored "  📖 _DOCS/DEPLOYMENT_GUIDE.md - Complete deployment workflow" "$CYAN"
print_colored "  📖 _DOCS/FRONTEND_VERIFICATION_GUIDE.md - UI testing mandatory" "$CYAN"
print_colored "  📖 _DOCS/CSS_STYLING_GUIDE.md - Style rules & inline styles ban" "$CYAN"
print_colored "  📖 _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns" "$CYAN"
print_colored "  📖 Plan_Projektu/ - Current ETAP status" "$CYAN"
print_colored "  📖 _AGENT_REPORTS/ - Latest agent work reports" "$CYAN"
echo_tty ""

# ⚠️ MANDATORY ACKNOWLEDGMENT REQUIRED
print_separator
print_colored "⚠️ MANDATORY ACKNOWLEDGMENT REQUIRED" "${BOLD}${RED}"
print_separator
echo_tty ""
print_colored "🔴 CLAUDE MUST CONFIRM:" "${BOLD}${RED}"
echo_tty ""
print_colored "  1. ✅ Przeczytałem i zrozumiałem wszystkie powyższe zasady" "$GREEN"
print_colored "  2. ✅ Będę stosować się do WSZYSTKICH zasad podczas tej sesji" "$GREEN"
print_colored "  3. ✅ Szczególnie będę pamiętać o:" "$GREEN"
print_colored "      • Context7 MANDATORY przed każdą implementacją" "$YELLOW"
print_colored "      • NO HARDCODING, NO MOCK DATA" "$YELLOW"
print_colored "      • NO INLINE STYLES - kategoryczny zakaz" "$YELLOW"
print_colored "      • Frontend verification MANDATORY" "$YELLOW"
print_colored "      • Agent reports MANDATORY" "$YELLOW"
print_colored "      • TODO list updates during work" "$YELLOW"
echo_tty ""

print_separator
print_colored "🤖 RESPOND IN YOUR FIRST MESSAGE:" "${BOLD}${GREEN}"
print_separator
echo_tty ""
print_colored '  "✅ POTWIERDZAM ZAPOZNANIE Z ZASADAMI PPM-CC-LARAVEL"' "${BOLD}${GREEN}"
print_colored '  "Będę stosować wszystkie reguły z CLAUDE.md podczas tej sesji."' "$GREEN"
echo_tty ""
print_separator
echo_tty ""

# CLI OUTPUT dla użytkownika - BEZPOŚREDNIO DO TTY
echo_tty ""
echo_tty "======================================================================"
echo_tty "✅ SESSION START HOOK EXECUTED"
echo_tty "======================================================================"
echo_tty "📋 Claude received full PPM-CC-Laravel rules reminder"
echo_tty "🔴 5 critical rules sections loaded"
echo_tty "🚀 Deployment info provided"
echo_tty "📚 Context7 configuration loaded"
echo_tty "📖 8 essential docs referenced"
echo_tty "⚠️  Waiting for Claude's acknowledgment in first response..."
echo_tty "======================================================================"
echo_tty ""

exit 0
