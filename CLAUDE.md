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

### 🏗️ Build & Deployment Architecture

**⚠️ KRYTYCZNA ZASADA:** Vite działa TYLKO na lokalnej maszynie development!

**LOKALNE (Development Machine - Windows):**
- ✅ Node.js + npm
- ✅ Vite 5.4.20
- ✅ `npm run build` - buduje assets lokalnie
- ✅ Output: `public/build/` (hashed filenames + manifest.json)

**PRODUKCJA (Hostido Server):**
- ❌ **Brak Node.js** (nie jest dostępny/zainstalowany)
- ❌ **Brak Vite** (nie istnieje na serwerze)
- ❌ **Brak npm** (nie można buildować na serwerze)
- ✅ **TYLKO zbudowane pliki** uploadowane z lokalnej maszyny

**WORKFLOW:**
```
[Local] → npm run build → [public/build/*] → pscp upload → [Production Server]
        ↓                                                           ↓
    Vite builds assets                                    Laravel @vite() helper
    Creates manifest.json                                 Reads manifest.json
    Hashes filenames                                      Serves static files
```

**Laravel Vite Helper (na produkcji):**
- Odczytuje `public/build/.vite/manifest.json`
- Mapuje entry points (np. `resources/css/app.css`) → hashed filenames (np. `assets/app-Ct0f_zUF.css`)
- Generuje `<link>` i `<script>` tagi ze ścieżkami do zbudowanych plików

### 🚨 KRYTYCZNE: Vite Manifest - Dwie Lokalizacje!

**⚠️ PROBLEM:** Vite tworzy manifest w DWÓCH miejscach, ale Laravel używa TYLKO jednego!

**Lokalizacje manifestu:**
```
public/build/
├── .vite/
│   └── manifest.json          ❌ TEN PLIK JEST IGNOROWANY!
└── manifest.json               ✅ TEGO UŻYWA LARAVEL!
```

**ROOT CAUSE:**
- Vite 5.x domyślnie tworzy manifest w `.vite/manifest.json` (subdirectory)
- Laravel Vite plugin (`@vite()` directive w Blade) szuka manifestu w `public/build/manifest.json` (ROOT)
- Jeśli wgrasz TYLKO `.vite/manifest.json`, Laravel go NIE ZNAJDZIE i użyje starego ROOT manifestu!

**OBJAWY problemu:**
- ✅ `npm run build` działa lokalnie
- ✅ Upload `.vite/manifest.json` zakończony sukcesem
- ✅ `php artisan cache:clear` wykonany
- ❌ Przeglądarka ładuje STARE pliki CSS/JS (z datą sprzed tygodni)
- ❌ Zmiany CSS nie są widoczne po hard refresh
- ❌ Manifest wskazuje na nieistniejące lub stare pliki

**✅ ROZWIĄZANIE: Wgrywaj OBA manifesty (lub tylko ROOT)!**

```powershell
# ❌ BŁĄD: Upload tylko .vite/manifest.json
pscp -i $HostidoKey -P 64321 `
  "public/build/.vite/manifest.json" `
  host379076@...:public/build/.vite/manifest.json

# ✅ POPRAWNIE: Upload ROOT manifest.json
pscp -i $HostidoKey -P 64321 `
  "public/build/.vite/manifest.json" `
  host379076@...:public/build/manifest.json

# LUB oba (bezpieczniej):
pscp -i $HostidoKey -P 64321 `
  "public/build/.vite/manifest.json" `
  host379076@...:public/build/.vite/manifest.json

pscp -i $HostidoKey -P 64321 `
  "public/build/.vite/manifest.json" `
  host379076@...:public/build/manifest.json
```

**KRYTYCZNE:** Deploy MUSI wgrać manifest do ROOT lokalizacji `public/build/manifest.json` (Laravel Vite helper wymaga ROOT, nie subdirectory)

**WERYFIKACJA po deployment:**
```powershell
# Sprawdź który plik ładuje przeglądarka
# DevTools → Network → CSS filter → sprawdź nazwę pliku

# Sprawdź ROOT manifest na produkcji
plink ... -batch "cat domains/.../public/build/manifest.json | grep components.css"

# Powinno pokazać AKTUALNY hash (np. components-BF7GTy66.css)
# Jeśli pokazuje STARY hash (np. components-wc8O_2Rd.css) = manifest nie został wgrany!
```

**DEPLOYMENT CHECKLIST:**
1. ✅ Lokalnie: `npm run build`
2. ✅ Upload CSS/JS files: `pscp public/build/assets/* → remote/assets/`
3. ✅ Upload manifest do ROOT: `pscp public/build/.vite/manifest.json → remote/build/manifest.json`
4. ✅ Clear cache: `php artisan view:clear && php artisan cache:clear && php artisan config:clear`
5. ✅ Hard refresh przeglądarki: Ctrl+Shift+R
6. ✅ DevTools verification: sprawdź które pliki CSS/JS się ładują

**Data wykrycia problemu:** 2025-10-14 (Modal z-index fix deployment)

### Środowisko Deployment
- **Domena**: ppm.mpptrade.pl
- **Hosting**: Hostido.net.pl (shared hosting - **brak Node.js/npm/Vite**)
- **SSH**: host379076@host379076.hostido.net.pl:64321 (klucz SSH wymagany)
- **SSH Key Path**: `D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk`
- **Laravel Root Path**: `domains/ppm.mpptrade.pl/public_html/` (bezpośrednio w public_html, bez podfolderu)
- **Baza**: host379076_ppm@localhost (MariaDB 10.11.13)
- **PHP**: 8.3.23 (natywnie dostępny)
- **Composer**: 2.8.5 (preinstalowany)
- **Node.js/npm**: ❌ NIE DOSTĘPNE (build tylko lokalnie!)

## Architektura Aplikacji

### 🔑 KRYTYCZNA ZASADA ARCHITEKTURY: SKU jako Główny Klucz Produktu

**⚠️ FUNDAMENTALNA REGUŁA:** SKU (Stock Keeping Unit) jest UNIWERSALNYM IDENTYFIKATOREM produktu w całej aplikacji.

**DLACZEGO SKU?**
- ✅ ZAWSZE ten sam SKU dla produktu fizycznego
- ❌ Różne ID w różnych sklepach PrestaShop
- ❌ Różne ID w różnych systemach ERP
- ❌ Możliwy brak external ID (produkt ręczny)

**ZASADA SKU FIRST:**
- ✅ Wyszukiwanie produktu → PRIMARY: SKU
- ✅ Conflict detection → SKU
- ✅ Import/export → SKU
- ✅ Multi-store sync → SKU
- ❌ External IDs → SECONDARY/FALLBACK only

**📖 SZCZEGÓŁOWY PRZEWODNIK:** [`_DOCS/SKU_ARCHITECTURE_GUIDE.md`](_DOCS/SKU_ARCHITECTURE_GUIDE.md)
- Przykłady prawidłowych/błędnych patterns
- Database schema
- Scenariusze użycia (first import, re-import, multi-store)
- Checklist implementacji

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

**⚠️ KRYTYCZNA ZASADA:** ZAWSZE weryfikuj poprawność layout, styles i frontend PRZED informowaniem użytkownika!

**WORKFLOW:**
1. Wprowadź zmiany (CSS/Blade/HTML)
2. Build assets: `npm run build`
3. Deploy na produkcję
4. **⚠️ KRYTYCZNE:** Screenshot verification
5. Jeśli problem → FIX → powtórz 1-4
6. Dopiero gdy OK → informuj użytkownika

**NARZĘDZIA:**
```bash
# Screenshot verification
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products

# lub slash command
/analizuj_strone
```

**📖 PEŁNY PRZEWODNIK WERYFIKACJI:** [`_DOCS/FRONTEND_VERIFICATION_GUIDE.md`](_DOCS/FRONTEND_VERIFICATION_GUIDE.md)
- Automated verification hook (PowerShell script)
- Kiedy używać weryfikacji (layout, CSS, Blade, responsive)
- Obowiązkowy workflow (krok po kroku)
- Narzędzia (screenshot, DOM check, computed styles)
- Przykłady dobrego/złego workflow
- Przypadki użycia (sidebar fix, modal z-index, responsive)
- Integration z agents (frontend-specialist, livewire-specialist)
- Checklist weryfikacji

### 🔍 DEBUG LOGGING BEST PRACTICES

**⚠️ KRYTYCZNA ZASADA:** Development = Extensive logging → Production = Minimal logging

**WORKFLOW:**
1. **Development:** Dodaj `Log::debug()` z pełnym kontekstem (types, BEFORE/AFTER state)
2. **Deploy & Test:** User weryfikuje funkcjonalność
3. **User Confirmation:** ✅ "działa idealnie"
4. **Cleanup:** Usuń `Log::debug()`, zostaw tylko `Log::info/warning/error`
5. **Final Deploy:** Clean version

**PRODUCTION RULES:**
- ✅ ZOSTAW: `Log::info()` (operacje biznesowe), `Log::warning()` (nietypowe), `Log::error()` (błędy)
- ❌ USUŃ: `Log::debug()`, "BEFORE/AFTER", `gettype()`, "CALLED/COMPLETED"

**📖 SZCZEGÓŁOWY PRZEWODNIK:** [`_DOCS/DEBUG_LOGGING_GUIDE.md`](_DOCS/DEBUG_LOGGING_GUIDE.md)
- Development phase (co i jak logować)
- Production phase (co pozostawić/usunąć)
- Workflow (development → production)
- Production logging rules (info/warning/error)
- Przykłady before/after (verbose vs clean)
- Monitoring production logs
- Cleanup checklist

### 🚫 KRYTYCZNE ZASADY CSS I STYLÓW

#### ⛔ KATEGORYCZNY ZAKAZ INLINE STYLES

**❌ ABSOLUTNIE ZABRONIONE:**
```html
<!-- NIGDY TAK NIE RÓB! -->
<div style="z-index: 9999; background: #1f2937;">...</div>
<div class="z-[9999] bg-gray-800">...</div>  <!-- Tailwind arbitrary values dla z-index -->
<button style="color: red; margin-top: 10px;">...</button>
```

**✅ ZAWSZE TAK:**
```css
/* resources/css/components/my-component.css */
.my-component-modal {
    z-index: 11;
    background: var(--color-bg-primary);
}
```

```html
<div class="my-component-modal">...</div>
```

**DLACZEGO ZAKAZ:**
- ❌ Inline styles = niemożność maintainability
- ❌ Tailwind arbitrary values (z-[9999]) = trudne do śledzenia
- ❌ Brak consistency w całej aplikacji
- ❌ Niemożliwość implementacji dark mode
- ❌ Trudniejsze debugging CSS issues
- ✅ CSS classes = centralized, cacheable, maintainable

#### 🚨 VITE MANIFEST ISSUE - DODAWANIE NOWYCH PLIKÓW CSS

**⚠️ WAŻNE WYJAŚNIENIE:** Vite **NIE ISTNIEJE** na serwerze produkcyjnym (Hostido)! Build robimy LOKALNIE, a na serwer wysyłamy GOTOWE zbudowane pliki.

**WORKFLOW NORMALNY:**
```
[Local Windows]                           [Production Hostido]
1. Edit CSS files                         4. Laravel @vite() helper
2. npm run build (Vite)                   5. Reads manifest.json
3. pscp upload public/build/ →            6. Serves static files
```

**PROBLEM występuje w kroku 5-6:** Laravel Vite helper (`@vite()` directive w Blade) na produkcji ma problemy z odczytaniem/cache manifest.json przy dodawaniu NOWYCH plików CSS:

```blade
{{-- resources/views/layouts/admin.blade.php --}}
@vite([
    'resources/css/app.css',
    'resources/css/admin/components.css',
    'resources/css/components/new-file.css'  // ← NOWY PLIK!
])
```

**OBJAWY:**
- `Illuminate\Foundation\ViteException`
- "Unable to locate file in Vite manifest: resources/css/components/new-file.css"
- ✅ Build lokalnie działa (`npm run build`)
- ✅ Manifest zawiera entry nowego pliku
- ✅ Plik istnieje w `public/build/assets/`
- ✅ Cache wyczyszczony (`php artisan view:clear && cache:clear`)
- ❌ Laravel Vite helper nadal wyrzuca exception

**ROOT CAUSE:** Laravel Vite helper aggressive caching manifest.json + race condition przy nowych entries

**✅ ROZWIĄZANIE: Dodawaj style do ISTNIEJĄCYCH plików CSS**

Zamiast tworzyć nowe pliki CSS, dodaj swoje style do odpowiedniego istniejącego pliku:

```css
/* resources/css/admin/components.css */

/* ... existing styles ... */

/* ========================================
   YOUR NEW COMPONENT STYLES
   ======================================== */

.your-new-component {
    /* your styles here */
}
```

**ISTNIEJĄCE PLIKI CSS (bezpieczne do rozszerzania):**
- `resources/css/admin/components.css` - Admin UI components
- `resources/css/admin/layout.css` - Admin layout i grid
- `resources/css/products/category-form.css` - Product forms
- `resources/css/components/category-picker.css` - Category picker

**PROCES DODAWANIA STYLÓW:**
1. ✅ Znajdź odpowiedni istniejący plik CSS (wg. funkcjonalności)
2. ✅ Dodaj sekcję z komentarzem opisującym co stylizujesz
3. ✅ Zdefiniuj klasy CSS (NIGDY inline styles!)
4. ✅ Build: `npm run build`
5. ✅ Deploy pliku CSS + built assets
6. ✅ Clear cache: `php artisan view:clear && php artisan cache:clear`

**KIEDY MOŻNA utworzyć NOWY plik CSS:**
- Tylko dla DUŻYCH, nowych modułów (>200 linii stylów)
- Po konsultacji z użytkownikiem
- Z pełną świadomością potencjalnych problemów Vite manifest
- Z testem na produkcji PRZED mergem

**ZASADA SPÓJNOŚCI:**
- Kolory: Paleta MPP TRADE (var(--color-primary))
- Komponenty: `.enterprise-card`, `.tabs-enterprise`, `.btn-enterprise-*`
- Layout: Consistent spacing/padding/margins
- Typography: Inter font, text-h1/h2/h3 hierarchy
- Animations: `.transition-standard`

**REFERENCJA:** CategoryForm = wzorzec dla wszystkich formularzy

**📖 KOMPLETNY PRZEWODNIK CSS:** [`_DOCS/CSS_STYLING_GUIDE.md`](_DOCS/CSS_STYLING_GUIDE.md)
- Absolutny zakaz inline styles (dlaczego, przykłady)
- Proces tworzenia stylów (krok po kroku)
- Vite manifest issue i rozwiązanie
- Zasada spójności stylów (kolory, komponenty, layout, typography)
- Common use cases (modals, responsive, dynamic colors)
- Code review red flags
- Testing checklist

### Issues & Fixes - Szczegółowe rozwiązania problemów

**📁 LOKALIZACJA**: `_ISSUES_FIXES/` - Szczegółowe raporty wszystkich znanych problemów i rozwiązań

#### 🔥 Krytyczne Issues (wymagają natychmiastowej uwagi)
- **[wire:snapshot Problem](_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md)** - Livewire renderowanie surowego kodu zamiast UI
- **[wire:poll Conditional Rendering](_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md)** - wire:poll wewnątrz @if nie działa
- **[x-teleport + wire:id Issue](_ISSUES_FIXES/LIVEWIRE_X_TELEPORT_WIRE_ID_ISSUE.md)** - wire:click nie działa w x-teleport bez wire:id
- **[Dependency Injection Issue](_ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md)** - Non-nullable properties w Livewire 3.x powodują DI conflict
- **[Hardcodowanie i symulacja](_ISSUES_FIXES/HARDCODE_SIMULATION_ISSUE.md)** - Zasady enterprise fallback bez mylenia użytkowników
- **[API Integration Pattern](_ISSUES_FIXES/API_INTEGRATION_PATTERN_ISSUE.md)** - Prawdziwe połączenia z fallback
- **[Livewire 3.x Events](_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md)** - Migracja emit() → dispatch()
- **[Type Juggling](_ISSUES_FIXES/PHP_TYPE_JUGGLING_ISSUE.md)** - Mixed int/string types w array operations

#### 🎨 UI/UX Issues
- **[CSS Stacking Context](_ISSUES_FIXES/CSS_STACKING_CONTEXT_ISSUE.md)** - Dropdown chowające się pod komponenty
- **[Category Picker Cross-Contamination](_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md)** - Checkboxy kategorii pokazujące się w innych kontekstach sklepów
- **[Sidebar Grid Layout Fix](_ISSUES_FIXES/SIDEBAR_GRID_LAYOUT_FIX.md)** - Sidebar overlaying content na desktop - CSS Grid solution

#### 🔧 Development Practices
- **[Debug Logging Best Practices](_ISSUES_FIXES/DEBUG_LOGGING_BEST_PRACTICES.md)** - Extensive logging podczas dev, minimal w production
- **[Vite Manifest New CSS Files](_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md)** - Problem z dodawaniem nowych plików CSS do Vite manifest na produkcji
- **[CSS Import Missing from Layout](_ISSUES_FIXES/CSS_IMPORT_MISSING_FROM_LAYOUT.md)** - CSS file nie ładuje się, bo brak w @vite() directive

#### 💡 Quick Reference - Najczęstsze problemy
```php
// ❌ BŁĘDY DO UNIKANIA
Route::get('/path', ComponentWithLayout::class); // wire:snapshot issue
$this->emit('event'); // Livewire 3.x błąd
'value' => 150.0; // hardcoded fake wartość
style="z-index: 9999; background: #1f2937;" // ❌ INLINE STYLES - KATEGORYCZNIE ZABRONIONE!
class="z-[9999] bg-gray-800" // ❌ Tailwind arbitrary values dla z-index - ZABRONIONE!
<div style="color: red;">...</div> // ❌ JAKIEKOLWIEK inline styles - ZABRONIONE!
@foreach($items as $item) <div>{{ $item->name }}</div> @endforeach // brak wire:key
<input id="category_{{ $item->id }}"> // nieunikalny ID w multi-context
@if($condition) <div wire:poll.3s>...</div> @endif // wire:poll wewnątrz @if nie działa
<template x-teleport="body"><div><button wire:click="method"></button></div></template> // x-teleport z wire:click nie działa
public int $progressId; // Livewire DI conflict - non-nullable type

// ✅ POPRAWNE ROZWIĄZANIA
Route::get('/path', fn() => view('wrapper')); // blade wrapper
$this->dispatch('event'); // Livewire 3.x API
'value' => mt_rand(80, 300); // realistyczne losowe
class="modal-root" /* CSS: .modal-root { z-index: 11; } */ // ✅ Style przez CSS!
@foreach($items as $item) <div wire:key="ctx-{{ $context }}-{{ $item->id }}"> // unikalny wire:key
<input id="category_{{ $context }}_{{ $item->id }}"> // kontekstowy ID
<div wire:poll.3s> @if($condition)...</@if> </div> // wire:poll POZA @if
<template x-teleport="body"><div><button @click="$wire.method()"></button></div></template> // z $wire
public ?int $progressId = null; // Nullable property dla Livewire params
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
- w pliku [dane_hostingu.md](dane_hostingu.md) masz wszystkie potrzebne dane do instalacji i uploadu aplikacji na serwerze Hostido.net.pl. Zgodnie z założeniami projektu nie tworzymy lokalnego środowiska, tylko piszemy tu kod. Całe gotowe środowisko znajduje się na serwerze Hostido gdzie zawsze po napisaniu lub aktualizacji kodu eksportujesz pliki przez SSH, a nastepnie przeprowadzasz weryfikacje poprawności ich działania na stronie https://ppm.mpptrade.pl
- **Laravel lokalizacja**: `/domains/ppm.mpptrade.pl/public_html/` - Laravel bezpośrednio w public_html (bez podfolderu)
- **Hybrydowy workflow**: Lokalne development → deploy na serwer → testy na ppm.mpptrade.pl
- **PowerShell environment**: Windows + PowerShell 7, unikać polskich znaków w skryptach
- **Nie tworzyć**: Niepotrzebnych wersji plików (_v1, _v2, etc.)
- **OAuth**: Implementować jako ostatni krok (Google Workspace + Microsoft)
- **Pierwszy admin**: Setup przez bezpośrednie wprowadzenie danych (email, hasło, firma)
- **NIGDY** nie hardcodujesz na sztywno wpisanych wartości w kodzie, chyba, że użytkownik Cię o to wyraźnie poprosi.
- **KRYTYCZNE** masz zakaz tworzenia danych mockowych! Jezeli dane do których odnoszą sie panele / funkcje jeszcze nie istnieja, to musisz je stworzyć w pierwszej kolejności i następnie powrócić do tego zadania aby je ukończyć!

## 🤖 SYSTEM AGENTÓW CLAUDE CODE

**STATUS:** ✅ AKTYWNY - 13 specjalistycznych agentów (wdrożony 2025-09-27)

### Struktura

- **Lokalizacja:** `.claude/agents/`
- **Raporty:** `_AGENT_REPORTS/`
- **Pokrycie:** 100% kluczowych obszarów PPM-CC-Laravel

### Agenci (Quick Reference)

**Core Team (5):**
- architect, ask, debugger, coding-style-agent, documentation-reader

**Domain Experts (8):**
- laravel-expert, livewire-specialist, prestashop-api-expert, erp-integration-expert
- import-export-specialist, deployment-specialist, frontend-specialist

### Workflow Patterns

**🆕 New Feature:** architect → documentation-reader → [specialist] → coding-style-agent → deploy
**🐛 Bug Fix:** debugger → [specialist] → coding-style-agent
**📦 ETAP:** architect → [multiple specialists] → deployment-specialist → architect (update status)

### ⚠️ KRYTYCZNE ZASADY

1. Używaj agentów dla zadań powyżej prostych poprawek
2. TYLKO JEDEN agent in_progress jednocześnie
3. OBOWIĄZKOWE raportowanie w `_AGENT_REPORTS/`
4. coding-style-agent PRZED completion (ZAWSZE)
5. Context7 integration MANDATORY

**📖 SZCZEGÓŁOWA DOKUMENTACJA:** [`_DOCS/AGENT_USAGE_GUIDE.md`](_DOCS/AGENT_USAGE_GUIDE.md)
- Lista wszystkich agentów z specjalizacjami
- Workflow patterns (szczegółowe)
- Kiedy którego agenta używać
- Agent delegation best practices
- Raportowanie i tracking

## 📚 CONTEXT7 INTEGRATION SYSTEM

**STATUS:** ✅ AKTYWNY - 100% agentów z Context7 (wdrożony 2025-09-27)

### Wybrane Biblioteki

- **Laravel 12.x:** `/websites/laravel_12_x` (4927 snippets, trust: 7.5)
- **Livewire 3.x:** `/livewire/livewire` (867 snippets, trust: 7.4)
- **Alpine.js:** `/alpinejs/alpine` (364 snippets, trust: 6.6)
- **PrestaShop:** `/prestashop/docs` (3289 snippets, trust: 8.2)

### ⚠️ MANDATORY Rules

1. **PRZED implementacją:** `mcp__context7__get-library-docs`
2. **ZAWSZE weryfikuj** aktualne patterns
3. **REFERENCUJ** dokumentację
4. **UŻYWAJ** właściwych library IDs

### Configuration

- **API Key:** `ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3`
- **Status:** ✓ Connected
- **Coverage:** 100% agentów (12/12)

**📖 SZCZEGÓŁOWY PRZEWODNIK:** [`_DOCS/CONTEXT7_INTEGRATION_GUIDE.md`](_DOCS/CONTEXT7_INTEGRATION_GUIDE.md)
- Pełna lista bibliotek z Library IDs
- Mandatory usage rules (szczegółowe)
- Agent implementation status (tabela 12 agentów)
- Usage patterns (przykłady dla każdego agenta)
- Expected behavior (correct vs incorrect)
- Troubleshooting Context7 issues

## Super Admin Account - Testing & Verification

**KONTO SUPER ADMINISTRATORA** (dla Claude Code testing i verification):

```
URL: https://ppm.mpptrade.pl/login
Email: admin@mpptrade.pl
Password: Admin123!MPP
Role: Admin (pełne uprawnienia)
User ID: 8
Created: 2025-09-09
Status: Active
```

**Admin Panel Routes:**
- Dashboard: `/admin` - Main admin dashboard z widgets
- Shop Management: `/admin/shops` - PrestaShop connections
- ERP Integration: `/admin/integrations` - Baselinker, Subiekt GT, Dynamics  
- System Settings: `/admin/system-settings` - Application configuration
- Backup Management: `/admin/backup` - Automated backup system
- Maintenance: `/admin/maintenance` - Database maintenance tools
- Notifications: `/admin/notifications` - Real-time notification center
- Reports: `/admin/reports` - Business Intelligence analytics
- API Management: `/admin/api` - API monitoring i usage statistics
- Customization: `/admin/customization` - Theme i layout management

**Permissions:** Wszystkie 47 admin permissions assigned
**Features:** Complete ETAP_04 Panel Administracyjny (5 faz: A, B, C, D, E)

**Last Verified:** 2025-09-09 - All admin functions operational

## 🔧 Issues & Fixes Documentation

**Katalog `_ISSUES_FIXES/`** zawiera szczegółowe raporty wszystkich znanych problemów, ich przyczyn i rozwiązań. Każdy raport zawiera:

- 🚨 Szczegółowy opis problemu i objawów
- ✅ Krok po kroku rozwiązanie
- 🛡️ Zasady zapobiegania
- 📋 Checklista implementacji/naprawy
- 💡 Przykłady z projektu
- 🔗 Powiązane pliki i komponenty

**ZASADA**: Gdy napotkasz nowy problem wymagający >2h debugowania, stwórz raport w `_ISSUES_FIXES/` i zaktualizuj CLAUDE.md z odnośnikiem.