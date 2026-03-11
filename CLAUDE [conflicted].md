# CLAUDE.md

## Projekt: PPM-CC-Laravel (Prestashop Product Manager)

Centralny hub produktów dla MPP TRADE - zarządzanie produktami na wielu sklepach Prestashop.

## Środowisko Techniczne

### Stack Technologiczny
- **Backend**: PHP 8.3 + Laravel 12.x
- **UI**: Blade + Livewire 3.x + Alpine.js
- **Build**: Vite 5.4.20 (**TYLKO lokalnie** - produkcja bez Node.js!)
- **DB**: MySQL (MariaDB 10.11.13)
- **Cache/Kolejki**: Redis (fallback: database driver)
- **Import**: Laravel-Excel (PhpSpreadsheet)
- **Autoryzacja**: Laravel Socialite (Google Workspace + Microsoft Entra ID) - ostatni krok
- **Dane hostingu**: `_DOCS/dane_hostingu.md`

### Deployment Environment
| Parametr | Wartość |
|----------|---------|
| Domena | ppm.mpptrade.pl |
| Hosting | Hostido.net.pl (shared, **brak Node.js/npm**) |
| SSH | `host379076@host379076.hostido.net.pl:64321` |
| SSH Key | `D:\SSH\Hostido\HostidoSSHNoPass.ppk` |
| Laravel Path | `domains/ppm.mpptrade.pl/public_html/` |
| PHP | 8.3.23 |
| Composer | 2.8.5 |

### 🏗️ Build & Deployment

**⚠️ KRYTYCZNE:** Vite lokalnie → upload zbudowanych plików → produkcja serwuje statyczne assets

```
[Local] npm run build → public/build/ → pscp upload → [Production] Laravel vite() reads manifest.json
```

**🚨 MANIFEST ISSUE:** Laravel wymaga `public/build/manifest.json` (ROOT), Vite tworzy `.vite/manifest.json`

```powershell
# MANDATORY: Upload manifest do ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json
```

**DEPLOYMENT CHECKLIST:**
1. ✅ `npm run build` (sprawdź "✓ built in X.XXs")
2. ✅ Upload ALL: `pscp -r public/build/assets/* → remote/assets/`
3. ✅ Upload manifest: `.vite/manifest.json → build/manifest.json`
4. ✅ Clear cache: `php artisan view:clear && cache:clear && config:clear`
5. ✅ HTTP 200 verify: `curl -I "https://ppm.mpptrade.pl/public/build/assets/app-X.css"`
6. ✅ Chrome DevTools verification

**Objawy błędnego deployment:** Stare CSS/JS mimo uploadu → sprawdź manifest hash

**Reference:** `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md`

## Architektura Aplikacji

### 🔑 SKU jako Główny Klucz Produktu

**SKU = UNIWERSALNY IDENTYFIKATOR** (stały dla produktu, w przeciwieństwie do zmiennych ID)

- **PRIMARY**: Wyszukiwanie, conflict detection, import/export, sync → SKU
- **FALLBACK**: External IDs
### KRYTYCZNA ZASADA PROJEKTU PPM: Zakładka SHOP TAB w ProductForm ma pokazywać wyłącznie dane pobrane z prestashop! Jeżeli zostaną dodane/usuniete/edytowane jakies dane w SHOP TAB TO SĄ ONE OZNACZONE JAKO PENDING CHANGES DO CZASU ZAPISANIA ZMIAN, UTWORZENIA JOBA I ZAKONCZENIA JOBA SYNC TO PRESTASHOP

### KRYTYCZNA ZASADA: ZAKAZ BLOKOWANIA OPERACJI PRZEZ PRZYPISANE PRODUKTY

**NIGDY** nie blokuj usuwania/edycji encji (marek, cech, grup cech, szablonów, kategorii itp.) z powodu przypisanych produktów!

- **Usuwanie marki** → odłącz (`manufacturer_id = null`) od produktów, potem usuń
- **Usuwanie cechy (FeatureType)** → usuń ProductFeature powiązania, potem usuń cechę
- **Usuwanie grupy cech** → kaskadowo: usuń cechy z produktów, usuń cechy, usuń grupę
- **Usuwanie szablonu** → zawsze dozwolone (w tym predefiniowane)
- **ZAWSZE** informuj użytkownika ile produktów zostało odłączonych w flash message
- **ZAWSZE** pokazuj ostrzeżenie w modalu potwierdzenia ile produktów jest przypisanych
- **ZAKAZ** wzorca `canDelete()` który blokuje operację - zamiast tego `detach + delete`

**📖 Przewodnik:** `_DOCS/SKU_ARCHITECTURE_GUIDE.md`

### Kluczowe Encje
- **Produkty**: SKU, kategorie 5-poziomowe, ceny grupowe, warianty
- **Grupy Cenowe**: Detaliczna, Dealer Standard/Premium, Warsztat/Premium, Szkółka-Komis-Drop, Pracownik
- **Magazyny**: MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje
- **Sklepy**: Multi-store z dedykowanymi opisami/kategoriami per sklep
- **ERP**: Baselinker (priorytet), Subiekt GT, Microsoft Dynamics

### System Użytkowników
1. **Admin** - pełny dostęp + zarządzanie
2. **Menadżer** - produkty + import/eksport
3. **Redaktor** - edycja opisów/zdjęć (bez usuwania)
4. **Magazynier** - panel dostaw
5. **Handlowiec** - rezerwacje (bez cen zakupu)
6. **Reklamacje** - panel reklamacji
7. **Użytkownik** - odczyt

## Quick Reference

**Development:**
```bash
php artisan serve     # Local server
php artisan migrate   # Migrations
npm run build        # Build assets
php artisan test     # Tests
```

**Deployment:**
```powershell
$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
pscp -i $HostidoKey -P 64321 "local/file" host379076@host379076.hostido.net.pl:remote/path
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"
```

**📖 Pełny przewodnik:** `_DOCS/DEPLOYMENT_GUIDE.md`

## 🎨 OBOWIĄZKOWA WERYFIKACJA FRONTEND

**⚠️ WORKFLOW:** Zmiany → Build → Deploy → **Claude in Chrome MCP** → (OK) → informuj użytkownika

### Claude in Chrome MCP / MCP chrome devtools z flagą --isolated (MANDATORY) 

### UWAGA! jeżeli Claude in Chrome nie działa użyj MCP chrome devtools z flagą --isolated jako narzędzia fallback, Stosuj się bezwzględnie do zasad stosowania chrome-devtools "D:\Skrypty\PPM-CC-Laravel\.claude\rules\verification\chrome-devtools.md" aby uniknąć nadmiarowego użycia tokenów

**🚨 KRYTYCZNE: LOGOWANIE WYŁĄCZONE!** Na czas developmentu logowanie jest wyłączone - wszystkie strony mają wyłączone middleware auth. NIE próbuj się logować, używaj bezpośrednich linków do podstron.

**Token Optimization:** `read_page()` może zwrócić dużo danych. Używaj targeted approaches.

**OPTIMIZED PATTERNS:**
```javascript
// 0. Get tab context first (MANDATORY!)
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })

// 1. Navigation
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/products" })

// 2. Find elements (50-300 tokens)
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "disabled buttons" })

// 3. JavaScript checks
mcp__claude-in-chrome__javascript_tool({
  tabId: TAB_ID, action: "javascript_exec",
  text: "({disabled: document.querySelectorAll('[disabled]').length})"
})

// 4. Console/Network
mcp__claude-in-chrome__read_console_messages({ tabId: TAB_ID, onlyErrors: true })
mcp__claude-in-chrome__read_network_requests({ tabId: TAB_ID, urlPattern: "/api/" })

// 5. Screenshot
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })

// 6. DOM (with depth control!)
mcp__claude-in-chrome__read_page({ tabId: TAB_ID, depth: 5, filter: "interactive" })
```

**ANTI-PATTERNS:**
- ❌ `read_page()` bez depth limit (może być duże!)
- ❌ Brak `tabs_context_mcp()` na początku
- ❌ Informowanie użytkownika BEZ Chrome verification
- ❌ Zakładanie "działa bo build przeszedł"

**SUCCESS PATTERN:** tabs_context → Navigate → find/javascript checks → Console/Network → Screenshot → Report

**📖 Full Guide:** `.claude/rules/verification/chrome-devtools.md`

### 🚫 KRYTYCZNE ZASADY CSS

**⛔ ZAKAZ INLINE STYLES:** `style="..."`, `class="z-[9999]"` → użyj CSS classes

```css
/* resources/css/components/my-component.css */
.my-component-modal { z-index: 11; background: var(--color-bg-primary); }
```

**ISTNIEJĄCE PLIKI CSS (bezpieczne):**
- `resources/css/admin/components.css` - Admin UI
- `resources/css/admin/layout.css` - Layout/grid
- `resources/css/products/category-form.css` - Product forms
- `resources/css/components/category-picker.css` - Pickers

**NOWY PLIK CSS:** Tylko >200 linii + konsultacja + test produkcji

**📖 Przewodnik:** `_DOCS/CSS_STYLING_GUIDE.md`

### 🔍 DEBUG LOGGING

**ZASADA:** Dev = `Log::debug()` → User potwierdza → Usuń debug → Deploy

**📖 Przewodnik:** `_DOCS/DEBUG_LOGGING_GUIDE.md`

## Issues & Fixes

**📁 Lokalizacja:** `_ISSUES_FIXES/`

**🔥 Krytyczne Livewire:**
- [wire:snapshot](_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md) - Surowy kod zamiast UI
- [wire:poll + conditional](_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md)
- [x-teleport + wire:id](_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md)
- [DI Conflict](_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md) - Non-nullable properties
- [emit() → dispatch()](_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md)

**🎨 UI/CSS:**
- [CSS Incomplete Deploy](_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md)
- [CSS Stacking](_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md)
- [Vite Manifest](_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md)

**💡 Quick Reference:**
```php
// ❌ BŁĘDY → ✅ POPRAWNIE
Route::get('/path', Component::class);     // → fn() => view('wrapper')
$this->emit('event');                       // → $this->dispatch('event')
style="z-index: 9999;"                      // → CSS classes
public int $progressId;                     // → public ?int $progressId = null;
pscp "components-X.css"                     // → pscp -r "public/build/assets/*"
```

## System Planowania

- **ZAWSZE** w zadaniach TODO wpisujesz aktualny punkt planu nad którym pracujesz 
- **KRYTYCZNE ZAWSZE** po ukończeniu zadania z TODO oznacz punkt w planie ❌ -> ✅
- **Plan:** `Plan_Projektu/` - każdy etap osobny plik
- **Statusy:** ❌ (nie rozpoczęte), 🛠️ (w trakcie), ✅ (ukończone), ⚠️ (zablokowane)
- **Raporty:** `_AGENT_REPORTS/`

### Status Implementacji
1. ✅ Backend + modele
2. ✅ Dashboard + Panel produktów
3. ✅ Panel admina (FAZA A, B, C)
4. ⏳ Integracja Baselinker
5. ❌ API Prestashop
6. ❌ Frontend z prawdziwymi danymi

### FAZA C: System Administration ✅ 2025-01-09
- SystemSettings, BackupManager, DatabaseMaintenance, Enterprise Security
- Routes: /admin/system-settings, /admin/backup, /admin/maintenance

## 🤖 SYSTEM AGENTÓW

**STATUS:** 13 agentów aktywnych (`.claude/agents/`)

**Core:** architect, ask, debugger, coding-style-agent, documentation-reader
**Domain:** laravel-expert, livewire-specialist, prestashop-api-expert, erp-integration-expert, import-export-specialist, deployment-specialist, frontend-specialist, refactoring-specialist

**Workflow:**
- New Feature: architect → docs → specialist → coding-style → deploy
- Bug Fix: debugger → specialist → coding-style
- ETAP: architect → specialists → deployment

### KRYTYCZNA ZASADA WORKFLOW: Agent Teams

**OBOWIAZKOWY WORKFLOW PO ZATWIERDZENIU PLANU (ExitPlanMode):**

```
ExitPlanMode -> Przeczytaj plan -> >=3 zadania? -> UTWORZ ZESPOL -> Parallel Work ->
-> Monitoruj -> Verify -> Cleanup -> User Confirm
```

**KROK PO KROKU (wykonaj NATYCHMIAST po ExitPlanMode):**

1. **PRZECZYTAJ** zatwierdzony plan z pliku planu
2. **POLICZ** niezalezne zadania
3. **<3 zadania** -> wykonaj samodzielnie (bez zespolu)
4. **>=3 zadania** -> UTWORZ ZESPOL:
   - `Teammate(operation="spawnTeam", team_name="plan-<opis>")`
   - `TaskCreate` dla kazdego punktu planu
   - `TaskUpdate(addBlockedBy)` dla zadan z zaleznosciami
   - Uruchom wspolpracownikow: `Task(team_name=..., subagent_type=...)`
   - Uzyj agentow z `.claude/agents/` jesli pasuja, fallback: `general-purpose`
   - Model `haiku` dla prostych, `sonnet` dla zlozonych
   - `TaskUpdate(owner=...)` - przydziel zadania
   - Monitoruj `TaskList`, reaguj na problemy
   - Po zakonczeniu: `shutdown_request` + `Teammate(cleanup)`

**ZASADY:**

- **Max 20 agentow** rownoczesnie
- **Wszystkie Task calls w JEDNEJ wiadomosci** = PARALLEL execution!
- **ROZNE PLIKI** per agent (unikaj konfliktow!)
- **TEN SAM PLIK** -> CZEKAJ na WSZYSTKICH agentow!
- **NIGDY** nie pytaj usera bez weryfikacji (testy, build, Chrome)

## 🎯 SKILLS

**STATUS:** 9 Skills aktywnych

| Skill | Opis | Mandatory |
|-------|------|-----------|
| hostido-deployment | Deploy to production | |
| livewire-troubleshooting | Known issues (9 patterns) | |
| frontend-verification | UI screenshots | ⚠️ przed UI completion |
| agent-report-writer | Reports | ⚠️ po completion |
| context7-docs-lookup | Docs verification | ⚠️ przed new patterns |
| ppm-architecture-compliance | PPM docs check | ⚠️ przed PPM features |
| project-plan-manager | Plan tracking | |
| ppm-modals | Modal dialogs (z-index, centrowanie) | ⚠️ przy tworzeniu modali |
| issue-documenter | Complex issues (>2h debug) | |
| debug-log-cleanup | Production cleanup | |

**📖 Przewodnik:** `_DOCS/SKILLS_USAGE_GUIDE.md`

## 📚 CONTEXT7

**STATUS:** ✅ Connected (100% agentów)

| Library | ID | Snippets |
|---------|-----|----------|
| Laravel 12.x | `/websites/laravel_12_x` | 4927 |
| Livewire 3.x | `/livewire/livewire` | 867 |
| Alpine.js | `/alpinejs/alpine` | 364 |
| PrestaShop | `/prestashop/docs` | 3289 |

**ZASADY:** PRZED implementacją → `mcp__context7__get-library-docs`

**📖 Przewodnik:** `_DOCS/CONTEXT7_INTEGRATION_GUIDE.md`

## Super Admin Account

**Testing:** `admin@mpptrade.pl / Admin123!MPP` (User ID: 8, 47 permissions)

**Routes:** /admin, /admin/shops, /admin/integrations, /admin/system-settings, /admin/backup, /admin/maintenance, /admin/notifications, /admin/reports, /admin/api, /admin/customization

## Uwagi Specjalne

- **Workflow:** Lokalne dev → deploy SSH → test https://ppm.mpptrade.pl
- **Environment:** Windows + PowerShell 7 (unikać polskich znaków)
- **Zakazy:** Wersje plików (_v1, _v2), hardcoded values, mock data
- **OAuth:** Ostatni krok implementacji

