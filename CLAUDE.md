# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt: PPM-CC-Laravel (Prestashop Product Manager)

Aplikacja klasy enterprise do zarządzania produktami na wielu sklepach Prestashop jednocześnie, będąca centralnym hubem produktów dla organizacji MPP TRADE.

## Środowisko Techniczne

### Stack Technologiczny
- **Backend**: PHP 8.3 + Laravel 12.x
- **UI**: Blade + Livewire 3.x + Alpine.js 
- **Build**: Vite (tylko lokalne buildy)
- **DB**: MySQL SQL
- **Cache/Kolejki**: Redis (lub driver database jako fallback)
- **Import XLSX**: Laravel-Excel (PhpSpreadsheet)
- **Autoryzacja**: Laravel Socialite (Google Workspace + Microsoft Entra ID) - implementacja na końcu

### Środowisko Deployment
- **Domena**: ppm.mpptrade.pl
- **Hosting**: Hostido.net.pl
- **SSH**: host379076@host379076.hostido.net.pl:64321 (klucz SSH wymagany)
- **SSH Key Path**: `D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk`
- **Laravel Root Path**: `domains/ppm.mpptrade.pl/public_html/` (bezpośrednio w public_html, bez podfolderu)
- **Baza**: host379076_ppm@localhost (MariaDB 10.11.13)
- **PHP**: 8.3.23 (natywnie dostępny)
- **Composer**: 2.8.5 (preinstalowany)

## Architektura Aplikacji

### 🔑 KRYTYCZNA ZASADA ARCHITEKTURY: SKU jako Główny Klucz Produktu

**⚠️ FUNDAMENTALNA REGUŁA - ZAWSZE PRZESTRZEGAJ:**

**SKU (Stock Keeping Unit) jest UNIWERSALNYM IDENTYFIKATOREM produktu w całej aplikacji PPM-CC-Laravel.**

#### **DLACZEGO SKU, nie external ID?**

Produkt w PPM może mieć:
- ❌ **Różne ID w różnych sklepach PrestaShop** (`prestashop_product_id`: 4017, 5234, 1092...)
- ❌ **Różne ID w różnych systemach ERP** (Baselinker ID, Subiekt GT ID, Dynamics ID...)
- ❌ **Brak external ID** (produkt dodany ręcznie przez użytkownika)
- ✅ **ZAWSZE TEN SAM SKU** - jedyny wspólny wyznacznik tego samego produktu fizycznego!

#### **OBOWIĄZKOWA ZASADA: SKU FIRST**

**✅ ZAWSZE używaj SKU jako PRIMARY lookup method dla:**
- Wyszukiwania produktu w bazie PPM
- Porównywania produktów między sklepami
- Conflict detection podczas importu/re-importu
- Synchronizacji danych między systemami (PrestaShop ↔ PPM ↔ ERP)
- Mapowania produktów z external systems

**❌ External IDs są WTÓRNE (secondary/fallback lookup):**
- `ProductShopData.prestashop_product_id` - tylko dla konkretnego sklepu
- `ERP mappings` - tylko dla konkretnego systemu ERP
- **Użyj ich TYLKO jeśli produkt nie ma SKU** (ekstremalnie rzadkie!)

#### **PRZYKŁAD PRAWIDŁOWEGO WORKFLOW:**

```php
// ✅ CORRECT: PRIMARY - Search by SKU from PrestaShop reference
$sku = $prestashopProduct['reference'] ?? null; // SKU from PrestaShop
if ($sku) {
    $product = Product::where('sku', $sku)->first();

    if ($product) {
        // ✅ Product EXISTS in PPM
        // Może być:
        // - Dodany ręcznie (bez ProductShopData)
        // - Z innego sklepu (ProductShopData.shop_id !== $currentShopId)
        // - Z tego samego sklepu (RE-IMPORT)
        // → CONFLICT DETECTION scenario
    } else {
        // ✅ Product NOT in PPM
        // → FIRST IMPORT scenario
    }
}

// ❌ FALLBACK: Only if product has NO SKU (extremely rare)
if (!$product) {
    $productShopData = ProductShopData::where('prestashop_product_id', $prestashopProductId)->first();
    if ($productShopData) {
        $product = Product::find($productShopData->product_id);
    }
}
```

#### **❌ BŁĘDNY PATTERN (DO UNIKANIA):**

```php
// ❌ WRONG: Search by shop-specific ID FIRST
$productShopData = ProductShopData::where('shop_id', $shopId)
    ->where('prestashop_product_id', $prestashopProductId)
    ->first();
// To POMIJA:
// - Produkty ręcznie dodane (brak ProductShopData)
// - Produkty z innych sklepów (inny shop_id)
// - Cross-shop scenarios
// → FALSE "first import" when it's actually RE-IMPORT!
```

#### **KONSEKWENCJE dla kodu:**

- ✅ **Conflict detection** MUSI używać SKU jako PRIMARY
- ✅ **Import/export** MUSI używać SKU jako PRIMARY
- ✅ **Synchronizacja multi-store** MUSI używać SKU jako PRIMARY
- ✅ **ERP integration** MUSI używać SKU jako PRIMARY
- ✅ **Product lookup** ZAWSZE SKU first, external ID fallback

#### **Database Schema:**

```
products table:
- id (INT) - Technical primary key (auto-increment)
- sku (VARCHAR) - ✅ UNIQUE, NOT NULL - BUSINESS PRIMARY KEY
- name, description, etc.

product_shop_data table (pivot):
- id (INT)
- product_id (FK → products.id)
- shop_id (FK → prestashop_shops.id)
- prestashop_product_id (INT) - Secondary mapping per shop

ERP mappings:
- Similar pattern - secondary mappings per ERP system
```

**PAMIĘTAJ:** SKU to fundament architektury PPM-CC-Laravel. Bez przestrzegania tej zasady system nie działa poprawnie!

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

### Development Workflow
```bash
# Lokalne środowisko development
php artisan serve
php artisan migrate
php artisan db:seed

# Build assets
npm install
npm run dev       # Development
npm run build     # Production

# Testy
php artisan test
./vendor/bin/phpunit
```

### Deployment na Hostido
```powershell
# SSH z kluczem PuTTY (ścieżka do klucza)
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Test połączenia
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "php -v"

# Upload pojedynczego pliku
pscp -i $HostidoKey -P 64321 "local/path/file.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/path/file.php

# Deployment commands
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev"

# Migracje i cache (zawsze po upload plików)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan view:clear && php artisan cache:clear"
```

### 🚀 Quick Commands Reference
```powershell
# Szybki upload i cache clear pattern:
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\path\to\file" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/path/to/file
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

### Ręczne połączenie SSH
```bash
# Wymaga klucza SSH (HostidoSSHNoPass.ppk)
ssh -p 64321 host379076@host379076.hostido.net.pl
```

### Baza Danych
```bash
# Migracje
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

# Seeders
php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

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

**⚠️ KRYTYCZNA ZASADA:** ZAWSZE weryfikuj poprawność layout, styles i frontend PRZED informowaniem użytkownika o ukończeniu!

#### **AUTOMATED VERIFICATION HOOK**

```powershell
# Po każdym wdrożeniu frontend/layout/styles changes
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "https://ppm.mpptrade.pl/admin/products"

# Z automatycznym otwarciem screenshot
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "https://ppm.mpptrade.pl/admin/products" -OpenReport

# Skip specific checks (jeśli potrzebne)
pwsh _TOOLS/verify_frontend_changes.ps1 -Url "..." -SkipScreenshot -SkipDOM
```

**Hook automatycznie sprawdza:**
- ✅ Screenshot viewport (1920x1080)
- ✅ DOM structure (Grid, parent hierarchy, positioning)
- ✅ Header/spacing issues (overlay, gaps)
- ❌ Exit code 1 jeśli wykryto problemy (nie informuj użytkownika!)
- ✅ Exit code 0 jeśli wszystko OK

#### **KIEDY UŻYWAĆ `/analizuj_strone`:**

**OBOWIĄZKOWO** po każdej zmianie dotyczącej:
- ✅ Layout (flexbox, grid, positioning)
- ✅ CSS styles (inline styles, classes, media queries)
- ✅ Blade templates (struktura DOM, divs balance)
- ✅ Responsive design (mobile/desktop breakpoints)
- ✅ Z-index / stacking context issues
- ✅ Sidebar, header, footer positioning
- ✅ Modals, dropdowns, overlays
- ✅ Any component that affects page layout

#### **WORKFLOW OBOWIĄZKOWY:**

```bash
# 1. Wprowadź zmiany w kodzie (CSS/Blade/HTML)
# 2. Build assets (jeśli CSS)
npm run build

# 3. Deploy na produkcję
pscp/plink upload & cache clear

# 4. ⚠️ KRYTYCZNE: Zweryfikuj przez screenshot
/analizuj_strone

# 5. Jeśli screenshot pokazuje problem → FIX → powtórz 1-4
# 6. Dopiero gdy screenshot OK → informuj użytkownika
```

#### **NARZĘDZIA WERYFIKACJI:**

**Screenshot Verification (PODSTAWOWE):**
```bash
node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
```

**DOM Structure Check:**
```bash
node _TOOLS/check_dom_structure_new.cjs https://ppm.mpptrade.pl/admin/products
```

**Computed Styles Analysis:**
```javascript
// _TOOLS/check_sidebar_styles.cjs (przykład)
const computed = window.getComputedStyle(element);
console.log({
    position: computed.position,
    display: computed.display,
    width: computed.width,
    zIndex: computed.zIndex
});
```

#### **PRZYKŁAD ZŁEGO WORKFLOW (ZABRONIONE):**

```
❌ BAD:
1. Zmiana admin.blade.php (sidebar lg:relative)
2. Upload na produkcję
3. Clear cache
4. "✅ Sidebar naprawiony!" ← BEZ WERYFIKACJI!

User: "Nie widzę żadnych zmian"
Claude: "Przepraszam, sprawdzam..." ← ZA PÓŹNO!
```

#### **PRZYKŁAD DOBREGO WORKFLOW (WYMAGANE):**

```
✅ GOOD:
1. Zmiana admin.blade.php (sidebar lg:relative)
2. npm run build (jeśli CSS)
3. Upload na produkcję
4. Clear cache
5. node _TOOLS/screenshot_page.cjs <URL>
6. Analiza screenshota → sidebar NADAL fixed
7. Root cause: Tailwind @media nie działa
8. Fix: Dodaj CSS !important w layout.css
9. Rebuild → Upload → Screenshot
10. Screenshot pokazuje sidebar OK ✅
11. Teraz inform user: "✅ Sidebar naprawiony (zweryfikowane)"
```

#### **CO SPRAWDZAĆ NA SCREENSHOT:**

- ✅ Sidebar NIE zasłania content
- ✅ Wszystkie kolumny widoczne i klikalne
- ✅ Responsive breakpoints działają
- ✅ Modals renderują się na wierzchu
- ✅ Dropdowns nie chowają się pod content
- ✅ Layout spójny na różnych szerokościach ekranu
- ✅ Teksty nie są ucięte (word-wrap)
- ✅ No horizontal scroll (chyba że zamierzone)

#### **SCREENSHOT STORAGE:**

```
_TOOLS/screenshots/
├── page_full_2025-10-08T11-32-00.png      # Full page
├── page_viewport_2025-10-08T11-32-00.png  # Viewport (1920x1080)
└── ...
```

**Zachowuj screenshoty PRZED i PO zmianach** dla porównania!

#### **PRZYPADKI UŻYCIA:**

**Case 1: Sidebar Layout Fix**
```bash
# Problem: Sidebar zasłania content
/analizuj_strone
# → Screenshot pokazuje sidebar fixed zamiast relative
# → Fix CSS → Rebuild → Deploy → Screenshot again
# → Verify sidebar position: relative w computed styles
```

**Case 2: Modal Z-Index Issue**
```bash
# Problem: Modal chowa się pod header
/analizuj_strone
# → Check z-index hierarchy
# → Fix: Header z-50, Modal z-999999
# → Screenshot verification
```

**Case 3: Responsive Breakpoints**
```bash
# Test multiple viewports:
node _TOOLS/screenshot_page.cjs --width 375   # Mobile
node _TOOLS/screenshot_page.cjs --width 768   # Tablet
node _TOOLS/screenshot_page.cjs --width 1920  # Desktop
```

#### **INTEGRATION Z AGENTS:**

- **frontend-specialist**: ZAWSZE używa `/analizuj_strone` po zmianach
- **livewire-specialist**: Weryfikuje components rendering
- **coding-style-agent**: Sprawdza inline styles violations

**DOKUMENTACJA PEŁNA:** `.claude/commands/analizuj_strone.md`

### 🔍 DEBUG LOGGING BEST PRACTICES

**⚠️ KRYTYCZNA ZASADA:** Podczas developmentu używaj zaawansowanych logów, po weryfikacji je usuń!

#### **DEVELOPMENT PHASE - Extensive Logging**

**KIEDY:** Podczas implementacji nowej funkcjonalności lub debugowania problemu

**CO LOGOWAĆ:**
```php
// ✅ DEVELOPMENT - Zaawansowane logi z pełnym kontekstem
Log::debug('removeFromShop CALLED', [
    'shop_id' => $shopId,
    'shop_id_type' => gettype($shopId),
    'exportedShops_BEFORE' => $this->exportedShops,
    'exportedShops_types' => array_map('gettype', $this->exportedShops),
    'shopsToRemove_BEFORE' => $this->shopsToRemove,
]);

Log::debug('Save: Filtering shops to create', [
    'exportedShops' => $this->exportedShops,
    'shopsToRemove' => $this->shopsToRemove,
    'shopsToCreate' => $shopsToCreate,
]);
```

**ZALETY:**
- Możliwość śledzenia typu danych (int vs string)
- Pełny stan przed/po operacji
- Łatwiejsze zidentyfikowanie root cause
- Szybsze debugowanie na produkcji

#### **PRODUCTION PHASE - Minimal Logging**

**KIEDY:** Po weryfikacji przez użytkownika że wszystko działa

**CO POZOSTAWIĆ:**
```php
// ✅ PRODUCTION - Tylko istotne operacje i błędy
Log::info('Shop marked for DB deletion on save', [
    'product_id' => $this->product?->id,
    'shop_id' => $shopId,
    'shopData_id' => $this->shopData[$shopId]['id'],
]);

Log::warning('removeFromShop ABORTED - shop not found', [
    'shop_id' => $shopId,
]);

Log::error('Product save failed', [
    'error' => $e->getMessage(),
    'product_id' => $this->product?->id,
]);
```

**CO USUNĄĆ:**
```php
// ❌ USUŃ po weryfikacji
Log::debug('...'); // Wszystkie logi debug
Log::debug('exportedShops_BEFORE', ...); // Stan przed operacją
Log::debug('exportedShops_types', ...); // Informacje o typach
```

#### **WORKFLOW:**

1. **Development:** Dodaj `Log::debug()` z pełnym kontekstem
2. **Deploy na produkcję:** Wszystkie logi zostają (dla testów)
3. **User Testing:** Użytkownik weryfikuje funkcjonalność
4. **User Confirmation:** ✅ "działa idealnie"
5. **Cleanup:** Usuń `Log::debug()`, zostaw tylko `Log::info/warning/error`
6. **Final Deploy:** Clean version bez debug logów

#### **PRODUCTION LOGGING RULES:**

**ZOSTAW:**
- ✅ `Log::info()` - Ważne operacje biznesowe (create, update, delete)
- ✅ `Log::warning()` - Nietypowe sytuacje które nie są błędami
- ✅ `Log::error()` - Wszystkie błędy i exceptions

**USUŃ:**
- ❌ `Log::debug()` - Wszelkie debug logi
- ❌ Logi typu "BEFORE/AFTER"
- ❌ Logi z typami danych (`gettype()`, `array_map('gettype')`)
- ❌ Logi "CALLED/COMPLETED"

#### **EXAMPLE - Before/After:**

```php
// ❌ DEVELOPMENT VERSION (verbose)
public function removeFromShop(int $shopId): void
{
    $shopId = (int) $shopId;

    Log::debug('removeFromShop CALLED', [
        'shop_id' => $shopId,
        'shop_id_type' => gettype($shopId),
        'exportedShops_BEFORE' => $this->exportedShops,
        'exportedShops_types' => array_map('gettype', $this->exportedShops),
    ]);

    // ... logic ...

    Log::debug('removeFromShop COMPLETED', [
        'exportedShops_AFTER' => $this->exportedShops,
        'shopsToRemove_AFTER' => $this->shopsToRemove,
    ]);
}

// ✅ PRODUCTION VERSION (clean)
public function removeFromShop(int $shopId): void
{
    $shopId = (int) $shopId;

    $key = array_search($shopId, $this->exportedShops, false);
    if ($key === false) {
        Log::warning('Shop removal failed - not in list', ['shop_id' => $shopId]);
        return;
    }

    // ... logic ...

    if (isset($this->shopData[$shopId]['id']) && $this->shopData[$shopId]['id'] !== null) {
        $this->shopsToRemove[] = $shopId;
        Log::info('Shop marked for deletion', [
            'product_id' => $this->product?->id,
            'shop_id' => $shopId,
        ]);
    }
}
```

**BENEFITS:**
- Production logs są czytelne i zwięzłe
- Nie zaśmiecamy storage logami debug
- Łatwiejszy monitoring w production
- Zachowujemy ważne informacje o operacjach biznesowych

### 🚫 KRYTYCZNE ZASADY CSS I STYLÓW

#### **ABSOLUTNY ZAKAZ STYLÓW INLINE**

**⚠️ BEZWZGLĘDNY ZAKAZ** używania atrybutu `style=""` w HTML/Blade templates!

**❌ ZABRONIONE:**
```html
<div style="z-index: 9999; background: #1f2937;">Content</div>
<button style="color: red; margin-top: 10px;">Button</button>
```

**✅ POPRAWNIE:**
```css
/* resources/css/components/my-component.css */
.my-component-header {
    z-index: 1;
    background: #1f2937;
}
```

```html
<div class="my-component-header">Content</div>
```

**DLACZEGO:**
- Konsystencja wyglądu w całej aplikacji
- Łatwiejsze zarządzanie stylami (maintainability)
- Lepsze performance (cachowanie CSS)
- Łatwiejsza implementacja dark mode
- Reusability klas CSS
- Enterprise quality standard

**PROCES:**
1. Sprawdź `_DOCS/PPM_Color_Style_Guide.md` czy klasa już istnieje
2. Stwórz dedykowany plik CSS w `resources/css/` jeśli potrzebny
3. Dodaj build entry do `vite.config.js` dla nowego pliku
4. Zbuduj assets: `npm run build`
5. Użyj klasy CSS w Blade template
6. NIGDY nie używaj `style=""` attribute

#### **ZASADA SPÓJNOŚCI STYLÓW**

**WSZYSTKIE** panele administracyjne, formularze i komponenty MUSZĄ używać identycznych:
- Kolorów (paleta MPP TRADE z PPM_Color_Style_Guide.md)
- Komponentów (`.enterprise-card`, `.tabs-enterprise`, `.btn-enterprise-*`)
- Layoutów (consistent spacing/padding/margins)
- Typografii (Inter font, hierarchia text-h1/h2/h3)
- Animacji (transitions, hover effects)

**CEL:** Użytkownik NIE powinien dostrzec różnic wizualnych między różnymi sekcjami aplikacji.

**CHECKLIST:**
- [ ] Header i breadcrumbs identyczne jak CategoryForm
- [ ] Tabs używają `.tabs-enterprise`
- [ ] Przyciski używają `.btn-enterprise-primary/secondary`
- [ ] Karty używają `.enterprise-card`
- [ ] Sidepanel "Szybkie akcje" w identycznym miejscu
- [ ] Dark mode colors zgodne z paletą
- [ ] NO inline styles (`style=""` attributes)

**REFERENCJA:** CategoryForm (`resources/views/livewire/products/categories/category-form.blade.php`) jest wzorcem dla wszystkich formularzy w aplikacji.

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

#### 💡 Quick Reference - Najczęstsze problemy
```php
// ❌ BŁĘDY DO UNIKANIA
Route::get('/path', ComponentWithLayout::class); // wire:snapshot issue
$this->emit('event'); // Livewire 3.x błąd
'value' => 150.0; // hardcoded fake wartość
style="z-index: 9999;" // w komponencie Livewire
@foreach($items as $item) <div>{{ $item->name }}</div> @endforeach // brak wire:key
<input id="category_{{ $item->id }}"> // nieunikalny ID w multi-context
@if($condition) <div wire:poll.3s>...</div> @endif // wire:poll wewnątrz @if nie działa
<template x-teleport="body"><div><button wire:click="method"></button></div></template> // x-teleport z wire:click nie działa
public int $progressId; // Livewire DI conflict - non-nullable type

// ✅ POPRAWNE ROZWIĄZANIA
Route::get('/path', fn() => view('wrapper')); // blade wrapper
$this->dispatch('event'); // Livewire 3.x API
'value' => mt_rand(80, 300); // realistyczne losowe
// z-index w admin header, nie komponencie
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

**STATUS:** ✅ AKTYWNY (wdrożony 2025-09-27)

Projekt PPM-CC-Laravel został wyposażony w kompletny system specjalistycznych agentów Claude Code do efektywnego zarządzania złożonością enterprise-class aplikacji.

### Struktura Agentów

**📁 Lokalizacja:** `.claude/agents/` (13 agentów specjalistycznych)
**📚 Dokumentacja:** `_DOCS/AGENT_USAGE_GUIDE.md` (przewodnik obowiązkowy)
**📊 Raporty:** `_AGENT_REPORTS/` (wszystkie wykonane prace)

### 🏗️ Agenci Bazowi (Core Team)

| Agent | Model | Specjalizacja | Kiedy używać |
|-------|-------|---------------|--------------|
| **architect** | sonnet | Planowanie, architektura, zarządzanie Plan_Projektu/ | ZAWSZE przed nowym ETAP-em, planowanie funkcjonalności |
| **ask** | sonnet | Odpowiedzi techniczne, analiza kodu, wyjaśnianie | Pytania bez implementacji, analiza istniejącego kodu |
| **debugger** | sonnet | Systematyczne debugowanie, diagnostyka problemów | Błędy aplikacji, problemy integracji, konflikty |
| **coding-style-agent** | sonnet | Standardy kodowania, Context7 integration | ZAWSZE przed completion, code review, compliance |
| **documentation-reader** | sonnet | Zgodność z dokumentacją, weryfikacja requirements | PRZED implementacją, sprawdzanie dependencies |

### 🔧 Agenci Specjaliści (Domain Experts)

| Agent | Model | Specjalizacja | ETAP Integration |
|-------|-------|---------------|------------------|
| **laravel-expert** | sonnet | Laravel 12.x, Eloquent, Service Layer, Queue | Wszystkie ETAP-y (fundament) |
| **livewire-specialist** | sonnet | Livewire 3.x, Alpine.js, reactive UI | ETAP_04, ETAP_05 (panele admin) |
| **prestashop-api-expert** | sonnet | PrestaShop API v8/v9, multi-store sync | **ETAP_07** (PrestaShop API) |
| **erp-integration-expert** | sonnet | BaseLinker, Subiekt GT, Microsoft Dynamics | **ETAP_08** ⏳ IN PROGRESS |
| **import-export-specialist** | sonnet | XLSX processing, column mapping | **ETAP_06** (Import/Export) |
| **deployment-specialist** | sonnet | SSH, PowerShell, Hostido, CI/CD | Wszystkie ETAP-y (deployment) |
| **frontend-specialist** | sonnet | Blade templates, Alpine.js, responsive design | ETAP_12 (UI/UX) |

### 🔄 Workflow Patterns

**PATTERN 1: Nowa Funkcjonalność**
```
1. documentation-reader → sprawdź requirements
2. architect → zaplanuj implementację
3. [Specjalista dziedziny] → implementuj
4. coding-style-agent → code review
5. deployment-specialist → deploy
```

**PATTERN 2: Debugging Problem**
```
1. debugger → diagnoza problemu
2. [Specjalista dziedziny] → implementacja fix
3. coding-style-agent → weryfikacja
```

**PATTERN 3: ETAP Implementation**
```
1. architect → aktualizacja planu ETAP
2. documentation-reader → compliance requirements
3. [Multiple specialists] → implementacja sekcji
4. coding-style-agent → final review
5. deployment-specialist → production deploy
6. architect → update plan status ✅
```

### ⚠️ KRYTYCZNE ZASADY UŻYCIA

1. **ZAWSZE** używaj systemu agentów dla zadań powyżej prostych poprawek
2. **TYLKO JEDEN** agent in_progress w danym momencie
3. **OBOWIĄZKOWE** raportowanie w `_AGENT_REPORTS/`
4. **WYMAGANE** użycie coding-style-agent przed completion
5. **CONTEXT7 INTEGRATION:** coding-style-agent MUSI używać MCP Context7

### 🎯 Quick Reference

**🔥 Emergency:** debugger → deployment-specialist
**🆕 New Feature:** architect → documentation-reader → [specialist] → coding-style-agent
**🐛 Bug Fix:** debugger → [specialist] → coding-style-agent
**📦 ETAP Work:** architect → [multiple specialists] → deployment-specialist

### 📊 Agent Performance Metrics

- **Utworzonych agentów:** 13 (5 bazowych + 8 specjalistów)
- **Pokrycie dziedzin:** 100% (wszystkie kluczowe obszary PPM-CC-Laravel)
- **Enterprise compliance:** ✅ (wszystkie agenci uwzględniają enterprise patterns)
- **Context7 integration:** ✅ (coding-style-agent z MCP)

**DOKUMENTACJA:** Szczegółowe instrukcje użycia w `_DOCS/AGENT_USAGE_GUIDE.md`

**MAINTENANCE:** System agentów będzie rozwijany wraz z ewolucją projektu PPM-CC-Laravel

## 📚 CONTEXT7 INTEGRATION SYSTEM

**STATUS:** ✅ AKTYWNY (wdrożony 2025-09-27)

PPM-CC-Laravel używa MCP Context7 server dla dostępu do aktualnej dokumentacji bibliotek i best practices. Wszystkich agentów zaktualizowano z obowiązkową integracją Context7.

### 🎯 Wybrane Biblioteki Context7

| Technologia | Library ID | Snippets | Trust | Agent Integration |
|-------------|------------|----------|-------|-------------------|
| **Laravel 12.x** | `/websites/laravel_12_x` | 4927 | 7.5 | laravel-expert, architect, debugger |
| **Livewire 3.x** | `/livewire/livewire` | 867 | 7.4 | livewire-specialist, debugger |
| **Alpine.js** | `/alpinejs/alpine` | 364 | 6.6 | frontend-specialist, livewire-specialist |
| **PrestaShop** | `/prestashop/docs` | 3289 | 8.2 | prestashop-api-expert |

### ⚠️ MANDATORY Context7 Usage Rules

**WSZYSTKICH AGENTÓW ZAKTUALIZOWANO** z obowiązkową integracją Context7:

1. **PRZED każdą implementacją** agent MUSI użyć `mcp__context7__get-library-docs`
2. **ZAWSZE weryfikować** aktualne patterns z oficjalnych źródeł
3. **REFERENCOWAĆ** oficjalną dokumentację w odpowiedziach
4. **UŻYWAĆ** właściwych library IDs dla każdej technologii

### 🔧 Context7 MCP Configuration

```bash
# Context7 MCP Server już skonfigurowany
claude mcp list
# context7: https://mcp.context7.com/mcp (HTTP) - ✓ Connected
```

**API Key:** `ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3` (już skonfigurowany)

### 📋 Agent Context7 Implementation Status

| Agent | Context7 Status | Primary Library | Updated |
|-------|----------------|-----------------|---------|
| **laravel-expert** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **livewire-specialist** | ✅ ACTIVE | `/livewire/livewire` | 2025-09-27 |
| **prestashop-api-expert** | ✅ ACTIVE | `/prestashop/docs` | 2025-09-27 |
| **frontend-specialist** | ✅ ACTIVE | `/alpinejs/alpine` | 2025-09-27 |
| **coding-style-agent** | ✅ ACTIVE | Multiple libraries | Pre-configured |
| **documentation-reader** | ✅ ACTIVE | All libraries | 2025-09-27 |
| **ask** | ✅ ACTIVE | Multiple libraries | 2025-09-27 |
| **debugger** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **architect** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **erp-integration-expert** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **import-export-specialist** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **deployment-specialist** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |

**REZULTAT:** 100% agentów ma aktywną integrację Context7 dla zapewnienia aktualnych informacji i best practices.

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