# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt: PPM-CC-Laravel (Prestashop Product Manager)

Aplikacja klasy enterprise do zarządzania produktami na wielu sklepach Prestashop jednocześnie, będąca centralnym hubem produktów dla organizacji MPP TRADE.

## Środowisko Techniczne

### Stack Technologiczny
- **Backend**: PHP 8.3 + Laravel 12.x
- **UI**: Blade + Livewire 3.x + Alpine.js
- **Build**: Vite 5.4.20 (**TYLKO lokalnie** - nie istnieje na produkcji!)
- **DB**: MySQL SQL
- **Cache/Kolejki**: Redis (lub driver database jako fallback)
- **Import XLSX**: Laravel-Excel (PhpSpreadsheet)
- **Autoryzacja**: Laravel Socialite (Google Workspace + Microsoft Entra ID) - implementacja na końcu
- Wszystkie potrzebne dane logowania, bazy danych prestashop, klucze API, SSH i FTP znajdują się w @_DOCS\dane_hostingu.md

### 🏗️ Build & Deployment Architecture

**⚠️ KRYTYCZNA ZASADA:** Vite działa TYLKO lokalnie! Produkcja otrzymuje gotowe zbudowane pliki.

**WORKFLOW:**
```
[Local Windows]                     [Production Hostido]
npm run build                       Laravel vite() directive
  ↓                                   ↓
public/build/ (hashed assets)       Reads manifest.json
  ↓                                   ↓
pscp upload →                       Serves static files
```

**LOKALNE:** Node.js + Vite 5.4.20 → `npm run build` → Output: `public/build/` (hashed + manifest)
**PRODUKCJA:** Brak Node.js/Vite/npm → TYLKO zbudowane pliki z lokalnej maszyny

### 🚨 KRYTYCZNE: Vite Manifest - Dwie Lokalizacje!

**PROBLEM:** Laravel wymaga manifestu w `public/build/manifest.json` (ROOT), ale Vite tworzy w `.vite/manifest.json` (subdirectory)

**Lokalizacje:**
```
public/build/
├── .vite/manifest.json          ❌ IGNOROWANE przez Laravel
└── manifest.json                ✅ WYMAGANE przez Laravel
```

**OBJAWY nieprawidłowego deployment:**
- Build lokalnie działa, upload zakończony, cache wyczyszczony
- Przeglądarka ładuje STARE pliki CSS/JS
- Manifest wskazuje na nieistniejące pliki

**ROZWIĄZANIE:**

```powershell
# ✅ Upload ROOT manifest (MANDATORY)
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json
```

**WERYFIKACJA:**
```powershell
plink ... -batch "cat domains/.../public/build/manifest.json | grep components.css"
# Musi pokazać AKTUALNY hash
```

**DEPLOYMENT CHECKLIST:**

**⚠️ KRYTYCZNA ZASADA:** Deploy **WSZYSTKIE** `public/build/assets/*` (Vite regeneruje hashe dla WSZYSTKICH plików przy każdym build)

1. ✅ `npm run build` (sprawdź "✓ built in X.XXs")
2. ✅ Upload ALL assets: `pscp -r public/build/assets/* → remote/assets/`
3. ✅ Upload manifest do ROOT: `pscp public/build/.vite/manifest.json → remote/build/manifest.json`
4. ✅ Clear cache: `php artisan view:clear && cache:clear && config:clear`
5. ✅ HTTP 200 verification (MANDATORY):
   ```powershell
   @('app-X.css', 'components-Y.css') | % { curl -I "https://ppm.mpptrade.pl/public/build/assets/$_" }
   # All must return HTTP 200 - jeśli 404 = incomplete deployment
   ```
6. ✅ Screenshot: `node _TOOLS/screenshot_page.cjs 'https://ppm.mpptrade.pl/admin'`
7. ✅ DevTools Network → verify fresh hashes

**Reference:** `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md`

### Środowisko Deployment
- **Domena**: ppm.mpptrade.pl
- **Hosting**: Hostido.net.pl (shared hosting - **brak Node.js/npm/Vite**)
- **SSH**: `host379076@host379076.hostido.net.pl:64321` (klucz SSH wymagany)
- **SSH Key Path**: `D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk`
- **Laravel Root Path**: `domains/ppm.mpptrade.pl/public_html/` (bezpośrednio w public_html, bez podfolderu)
- **Baza**: `host379076_ppm@localhost` (MariaDB 10.11.13)
- **PHP**: 8.3.23 (natywnie dostępny)
- **Composer**: 2.8.5 (preinstalowany)
- **Node.js/npm**: ❌ NIE DOSTĘPNE (build tylko lokalnie!)

## Architektura Aplikacji

### 🔑 KRYTYCZNA ZASADA: SKU jako Główny Klucz Produktu

**SKU (Stock Keeping Unit) = UNIWERSALNY IDENTYFIKATOR** (zawsze ten sam dla produktu fizycznego, w przeciwieństwie do zmiennych ID w różnych sklepach/ERP)

**ZASADA SKU FIRST:**
- PRIMARY: Wyszukiwanie, conflict detection, import/export, multi-store sync → SKU
- SECONDARY/FALLBACK: External IDs

**📖 PRZEWODNIK:** [`_DOCS/SKU_ARCHITECTURE_GUIDE.md`](_DOCS/SKU_ARCHITECTURE_GUIDE.md) - patterns, schema, scenariusze, checklist

---

### System Użytkowników (Hierarchia uprawnień)
1. **Admin** - pełny dostęp + zarządzanie użytkownikami/sklepami/ERP
2. **Menadżer** - zarządzanie produktami + eksport + import CSV/ERP
3. **Redaktor** - edycja opisów/zdjęć + eksport (bez usuwania produktów)
4. **Magazynier** - panel dostaw (bez rezerwacji z kontenera)
5. **Handlowiec** - rezerwacje z kontenera (bez widoczności cen zakupu)
6. **Reklamacje** - panel reklamacji
7. **Użytkownik** - odczyt + wyszukiwarka

### Kluczowe Encje
- **Produkty**: SKU (klucz główny), nazwa, kategorie wielopoziomowe, opisy HTML, ceny grupowe, stany magazynowe, warianty
- **Kategorie**: 5 poziomów zagnieżdżenia (Kategoria→Kategoria4)
- **Grupy Cenowe**: Detaliczna, Dealer Standard/Premium, Warsztat/Premium, Szkółka-Komis-Drop, Pracownik
- **Magazyny**: MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje + custom
- **Sklepy Prestashop**: Multi-store support z dedykowanymi opisami/kategoriami per sklep
- **Integracje ERP**: Baselinker, Subiekt GT, Microsoft Dynamics

### System Importu/Eksportu
- **Import XLSX**: Mapowanie kolumn z predefiniowanymi szablonami (POJAZDY/CZĘŚCI)
- **Kluczowe kolumny**: ORDER, Parts Name, U8 Code, MRF CODE, Qty, Ctn no., Size, Weight, Model, VIN, Engine No.
- **System kontenerów**: id_kontener + dokumenty odprawy (.zip, .xlsx, .pdf, .xml)
- **Weryfikacja**: Sprawdzanie poprawności przed eksportem na Prestashop

## Komendy i Workflow

### Quick Reference

**Development:**
```bash
php artisan serve           # Local dev server
php artisan migrate         # Run migrations
npm run build              # Build assets
php artisan test           # Run tests
```

**Deployment (Hostido):**
```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload file
pscp -i $HostidoKey -P 64321 "local/file" host379076@host379076.hostido.net.pl:remote/path

# Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"
```

**📖 PEŁNY PRZEWODNIK DEPLOYMENT:** [`_DOCS/DEPLOYMENT_GUIDE.md`](_DOCS/DEPLOYMENT_GUIDE.md)
- Wszystkie komendy SSH/pscp/plink
- Deployment patterns (single file, multiple files, migrations, assets)
- Maintenance commands (cache, queue, database)
- Troubleshooting deployment issues
- Deployment checklist

## Kluczowe Funkcjonalności

### System Dopasowań Pojazdów
- **Cechy**: Model, Oryginał, Zamiennik
- **Format eksportu**: Osobne wpisy dla każdego modelu (Model: X, Model: Y, etc.)
- **Filtrowanie**: Per sklep Prestashop (globalne modele z możliwością "banowania" na wybranych sklepach)

### System Wyszukiwania
- **Inteligentna wyszukiwarka**: Podpowiedzi, obsługa błędów, literówek
- **Filtry**: "Wyszukaj dokładnie" vs. przybliżone wyszukiwanie
- **Domyślny widok**: Statystyki zamiast listy produktów (dopóki nie wyszuka)

### Synchronizacja Multi-Store
- **Status synchronizacji**: Monitoring rozbieżności między aplikacją a Prestashop/ERP
- **Dedykowane dane per sklep**: Różne opisy, kategorie, cechy
- **Mapowanie**: Grupy cenowe, magazyny, kategorie między systemami

## Struktura Folderów Projektu

```
PPM-CC-Laravel/
├── _init.md                    # Dokumentacja projektu
├── AGENTS.md                   # Instrukcje dla agentów
├── dane_hostingu.md           # Dane hostingu i SSH
├── References/                # Mockupy UI i pliki źródłowe
│   ├── Dashboard_admin.png
│   ├── Lista_produktów.png
│   ├── Produkt_part1.png
│   ├── ERP_Dashboard.png
│   └── JK25154D*.xlsx         # Przykładowe pliki importu
└── [Laravel structure when created]
```

## Integracje

### Prestashop API
- Multi-store support
- Zachowanie struktur katalogów dla zdjęć
- Weryfikacja zgodności z bazą danych Prestashop 8.x/9.x
- **KRYTYCZNE**: Sprawdzanie struktury DB: https://github.com/PrestaShop/PrestaShop/blob/8.3.x/install-dev/data/db_structure.sql

### ERP Systems
- **Baselinker**: Priorytet #1 dla integracji
- **Subiekt GT**: Import/eksport + mapowanie magazynów
- **Microsoft Dynamics**: Zaawansowana integracja business

## Zasady Development

### Jakość Kodu
- **Klasa Enterprise**: Bez skrótów i uproszczeń
- **Bez hardcode'u**: Wszystko konfigurowane przez admin
- **Best Practices**: Laravel + Prestashop oficjalna dokumentacja
- **Bezpieczeństwo**: Walidacja, sanitization, error handling

### 🎨 OBOWIĄZKOWA WERYFIKACJA FRONTEND

**⚠️ KRYTYCZNA ZASADA:** Weryfikuj layout/styles PRZED informowaniem użytkownika!

**WORKFLOW:** Zmiany → Build → Deploy → **PPM Verification Tool** → (jeśli OK) informuj użytkownika

**NARZĘDZIE:** `_TOOLS/full_console_test.cjs` - Console monitoring + screenshots + Livewire check + tab interactions

```bash
# Basic (default: headless, Warianty tab)
node _TOOLS/full_console_test.cjs

# Custom
node _TOOLS/full_console_test.cjs "URL" --show --tab=Cechy --no-click
```

**MANDATORY dla agentów:**
- Po deployment CSS/JS/Blade
- Po Livewire updates
- PRZED informowaniem o completion

**📖 PRZEWODNIK:** [`_DOCS/FRONTEND_VERIFICATION_GUIDE.md`](_DOCS/FRONTEND_VERIFICATION_GUIDE.md)

### 🔍 DEBUG LOGGING

**ZASADA:** Development = Extensive logging (`Log::debug()`) → Production = Minimal logging (`Log::info/warning/error`)

**WORKFLOW:** Development + `Log::debug()` → User potwierdza "działa idealnie" → Usuń `Log::debug()` → Final deploy

**📖 PRZEWODNIK:** [`_DOCS/DEBUG_LOGGING_GUIDE.md`](_DOCS/DEBUG_LOGGING_GUIDE.md)

### 🚫 KRYTYCZNE ZASADY CSS

#### ⛔ KATEGORYCZNY ZAKAZ INLINE STYLES

**❌ ZABRONIONE:** `style="..."`, `class="z-[9999]"` (Tailwind arbitrary dla z-index)
**✅ WYMAGANE:** CSS classes w dedykowanych plikach

```css
/* resources/css/components/my-component.css */
.my-component-modal { z-index: 11; background: var(--color-bg-primary); }
```

```html
<div class="my-component-modal">...</div>
```

#### 🚨 VITE MANIFEST - NOWE PLIKI CSS

**PROBLEM:** Laravel Vite helper ma problemy z caching manifestu przy NOWYCH plikach CSS → `ViteException: Unable to locate file`

**ROZWIĄZANIE:** Dodawaj style do ISTNIEJĄCYCH plików CSS zamiast tworzyć nowe

**ISTNIEJĄCE PLIKI (bezpieczne):**
- `resources/css/admin/components.css` - Admin UI
- `resources/css/admin/layout.css` - Layout/grid
- `resources/css/products/category-form.css` - Product forms
- `resources/css/components/category-picker.css` - Pickers

**PROCES:**
1. Znajdź odpowiedni istniejący plik
2. Dodaj sekcję z komentarzem
3. Zdefiniuj CSS classes (NIGDY inline!)
4. Build + deploy + clear cache

**NOWY PLIK:** Tylko dla dużych modułów (>200 linii) + po konsultacji + test na produkcji

**SPÓJNOŚĆ:** Używaj `var(--color-primary)`, `.enterprise-card`, `.tabs-enterprise`, `.btn-enterprise-*`

**📖 PRZEWODNIK:** [`_DOCS/CSS_STYLING_GUIDE.md`](_DOCS/CSS_STYLING_GUIDE.md)

### Issues & Fixes

**📁 LOKALIZACJA**: `_ISSUES_FIXES/` - Raporty znanych problemów i rozwiązań

**🔥 Krytyczne:**
- [wire:snapshot](_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md) - Surowy kod zamiast UI
- [wire:poll + conditional rendering](_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md) - Nie działa w conditional
- [x-teleport + wire:id](_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md) - wire:click wymaga wire:id
- [DI Conflict](_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md) - Non-nullable properties w Livewire 3.x
- [Livewire Events](_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md) - emit() → dispatch()
- [CSS Incomplete Deploy](_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md) - Partial upload = brak stylów

**🎨 UI/UX:**
- [CSS Stacking](_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md) - z-index conflicts
- [Category Picker](_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md) - Cross-contamination
- [Sidebar Layout](_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md) - Grid solution

**🔧 Development:**
- [Debug Logging](_ISSUES_FIXES/DEBUG_LOGGING_BEST_PRACTICES.md) - Dev vs production
- [Vite Manifest](_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md) - Nowe pliki CSS
- [CSS Import](_ISSUES_FIXES/CSS_IMPORT_MISSING_FROM_LAYOUT.md) - Brak w `@vite()`

**💡 Quick Reference:**

```php
// ❌ BŁĘDY
Route::get('/path', Component::class); // → Use: fn() => view('wrapper')
$this->emit('event'); // → Use: $this->dispatch('event')
style="z-index: 9999;" // → Use: CSS classes
class="z-[9999]" // → Use: CSS classes
// @if conditional inside wire:poll // → Put wire:poll OUTSIDE conditionals
<template x-teleport><button wire:click>... // → Use Alpine click with $wire
public int $progressId; // → Use: public ?int $progressId = null;
pscp "components-X.css" // → Use: pscp -r "public/build/assets/*"
```

### System Planowania
- Plan w folderze `Plan_Projektu/` - każdy etap osobny plik
- Statusy: ❌ (nie rozpoczęte), 🛠️ (w trakcie), ✅ (ukończone), ⚠️ (zablokowane)
- Raporty agentów w `_AGENT_REPORTS/`

### Kolejność Implementacji
1. ✅ Backend fundament + modele - COMPLETED
2. ✅ Dashboard + Panel produktów - COMPLETED
3. ✅ Panel admina (FAZA A, B, C) - COMPLETED
4. ⏳ Integracja Baselinker - IN PROGRESS
5. API Prestashop
6. Frontend z prawdziwymi danymi
7. System dostaw (przyszłość)
8. System reklamacji (przyszłość)
n### FAZA C: System Administration - COMPLETED 2025-01-09
- ✅ SystemSettings - Centralized application configuration
- ✅ BackupManager - Automated backup system z monitoring
- ✅ DatabaseMaintenance - Maintenance tools i health monitoring
- ✅ Enterprise Security - Encrypted settings i audit trail
- 📍 **Routes**: /admin/system-settings, /admin/backup, /admin/maintenance

## Uwagi Specjalne
- **Hosting data**: [dane_hostingu.md](dane_hostingu.md)
- **Laravel path**: `/domains/ppm.mpptrade.pl/public_html/` (bezpośrednio w public_html)
- **Workflow**: Lokalne dev → deploy SSH → test https://ppm.mpptrade.pl
- **Environment**: Windows + PowerShell 7 (unikać polskich znaków)
- **Zakazy**: Wersje plików (_v1, _v2), hardcoded values, mock data
- **OAuth**: Ostatni krok implementacji

## 🤖 SYSTEM AGENTÓW CLAUDE CODE

**STATUS:** ✅ 13 agentów aktywnych (`.claude/agents/`, raporty: `_AGENT_REPORTS/`)

**Core (5):** architect, ask, debugger, coding-style-agent, documentation-reader
**Domain (8):** laravel-expert, livewire-specialist, prestashop-api-expert, erp-integration-expert, import-export-specialist, deployment-specialist, frontend-specialist

**Workflow:**
- New Feature: architect → docs → specialist → coding-style → deploy
- Bug Fix: debugger → specialist → coding-style
- ETAP: architect → specialists → deployment → status update

**ZASADY:**
1. Agents dla non-trivial tasks
2. JEDEN agent in_progress
3. MANDATORY reports w `_AGENT_REPORTS/`
4. coding-style-agent PRZED completion
5. Context7 integration MANDATORY

**📖 PRZEWODNIK:** [`_DOCS/AGENT_USAGE_GUIDE.md`](_DOCS/AGENT_USAGE_GUIDE.md)

## 🎯 CLAUDE CODE SKILLS

**STATUS:** ✅ 9 Skills aktywnych (`C:\Users\kamil\.claude\skills\`)

**Skills (model-invoked capabilities):**
1. **hostido-deployment** - Auto deploy to production
2. **livewire-troubleshooting** - Known issues diagnosis (9 patterns)
3. **frontend-verification** - ⚠️ MANDATORY UI screenshots
4. **agent-report-writer** - ⚠️ MANDATORY reports in `_AGENT_REPORTS/`
5. **project-plan-manager** - Plan tracking z emoji statusy
6. **context7-docs-lookup** - ⚠️ MANDATORY docs verification
7. **issue-documenter** - Complex issues (>2h debug)
8. **debug-log-cleanup** - Production cleanup po confirmation
9. **ppm-architecture-compliance** - ⚠️ MANDATORY PPM docs check

**ZASADY:**
- ppm-architecture-compliance MANDATORY przed PPM features
- agent-report-writer MANDATORY po completion
- context7-docs-lookup MANDATORY przed new patterns
- frontend-verification MANDATORY przed UI completion

**📖 PRZEWODNIK:** [`_DOCS/SKILLS_USAGE_GUIDE.md`](_DOCS/SKILLS_USAGE_GUIDE.md)

## 📚 CONTEXT7 INTEGRATION

**STATUS:** ✅ Connected - API: `ctx7sk-dea6...675c3` - Coverage: 100% agentów

**Biblioteki:**

- Laravel 12.x: `/websites/laravel_12_x` (4927 snippets, trust 7.5)
- Livewire 3.x: `/livewire/livewire` (867 snippets, trust 7.4)
- Alpine.js: `/alpinejs/alpine` (364 snippets, trust 6.6)
- PrestaShop: `/prestashop/docs` (3289 snippets, trust 8.2)

**ZASADY:**
1. PRZED implementacją: `mcp__context7__get-library-docs`
2. ZAWSZE weryfikuj patterns
3. Używaj właściwych library IDs

**📖 PRZEWODNIK:** [`_DOCS/CONTEXT7_INTEGRATION_GUIDE.md`](_DOCS/CONTEXT7_INTEGRATION_GUIDE.md)

## Super Admin Account

**Testing Account:** `admin@mpptrade.pl / Admin123!MPP` (User ID: 8, wszystkie 47 permissions)

**Admin Routes:** /admin (dashboard), /admin/shops, /admin/integrations, /admin/system-settings, /admin/backup, /admin/maintenance, /admin/notifications, /admin/reports, /admin/api, /admin/customization

**Last Verified:** 2025-09-09 - All operational