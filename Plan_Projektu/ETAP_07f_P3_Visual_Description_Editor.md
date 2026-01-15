# ETAP_07f_P3: Visual Description Editor - PrestaShop CSS Integration & Dedicated Blocks

**Status**: 🛠️ W TRAKCIE
**Priorytet**: KRYTYCZNY
**Zależności**: ETAP_07f_P1, ETAP_07f_P2

---

## CEL GLOWNY

**Odwzorowanie srodowiska PrestaShop w PPM:**
1. Edytor CSS/JS pokazuje WSZYSTKIE pliki CSS/JS z PrestaShop (theme.css, custom.css, modules)
2. Dedykowane bloki korzystajace z klas CSS PrestaShop per shop
3. Podglad opisu produktu 1:1 zgodny z PrestaShop
4. Mozliwosc konwersji prestashop-section -> dedykowany blok do powielania

---

## FAZA 0: FTP Integration & File Scanning (NOWA!) [UKONCZONA]

**Cel:** Polaczenie FTP do PrestaShop i skanowanie plikow CSS/JS

### ✅ 0.1 FTP Configuration in AddShop.php

**Plik:** `app/Http/Livewire/Admin/Shops/AddShop.php`

Zaimplementowano:
- Panel konfiguracji FTP (host, port, user, password, protocol)
- Szyfrowanie hasel FTP (Laravel Crypt)
- Test polaczenia FTP
- Przycisk "Skanuj pliki CSS/JS"

**UI:**
```
[FTP Configuration]
Host:     [test.kayomoto.pl        ]
Port:     [21                      ]
User:     [ftp_user                ]
Password: [********                ]
Protocol: [FTP/SFTP                ]
[Test polaczenia]

[Skanuj pliki CSS/JS]
```

### ✅ 0.2 CSS/JS File Scanning Service

**Plik:** `app/Services/VisualEditor/PrestaShopCssFetcher.php`

Metoda: `scanFilesViaFtp(array $config, string $shopUrl): array`

**Funkcjonalnosc:**
1. Laczenie FTP do PrestaShop
2. Skanowanie katalogow `/themes/*/assets/css/` i `/themes/*/assets/js/`
3. Kategoryzacja plikow (theme, custom, module)
4. Zapis wynikow do `css_files` i `js_files` w tabeli `prestashop_shops`

### ✅ 0.3 Scanned Files Storage

**Kolumny w tabeli `prestashop_shops`:**
- `css_files` - JSON array ze zeskanowanymi plikami CSS
- `js_files` - JSON array ze zeskanowanymi plikami JS

**Struktura elementu:**
```php
[
    'url' => 'https://shop.com/themes/warehouse/assets/css/theme.css',
    'filename' => 'theme.css',
    'category' => 'theme', // theme | custom | module
    'enabled' => true,
    'cached_content' => '...',
    'last_fetched_at' => '2025-12-17T10:00:00Z',
]
```

### ✅ 0.4 URL to FTP Path Conversion

**Plik:** `app/Services/VisualEditor/PrestaShopCssFetcher.php`

```php
public function urlToFtpPath(string $url, string $shopUrl): string
{
    // https://test.kayomoto.pl/themes/warehouse/assets/css/custom.css
    // -> /themes/warehouse/assets/css/custom.css
}
```

### ✅ 0.5 Dynamic File Path Resolution

**Metody:**
- `getDefaultCssPath(PrestaShopShop $shop)` - znajduje custom.css ze skanowania
- `getDefaultJsPath(PrestaShopShop $shop)` - znajduje custom.js ze skanowania
- `getFilePathFromUrl(PrestaShopShop $shop, string $url)` - konwersja URL->FTP

**Priorytet:**
1. custom.css/js (edytowalne)
2. theme.css/js (fallback)

---

## FAZA 1: CSS/JS Editor Modal [UKONCZONA]

**Cel:** Edytor CSS/JS pokazuje pełna liste plikow CSS/JS z PrestaShop z mozliwoscia edycji

### ✅ 1.1 CssJsEditorModal Component - Base

**Plik:** `app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php`

Zaimplementowano:
- Modal z zakladkami: Files, Editor, Analysis
- Ladowanie listy plikow z assetManifest
- Przelaczanie miedzy CSS/JS
- Tracking aktualnie edytowanego pliku (`editingFilePath`, `editingFileName`)

### ✅ 1.2 File Loading via FTP

**Metoda:** `loadEditorContent()`

```php
$result = $fetcher->getCustomCss($shop, $this->editingFilePath);
// lub
$result = $fetcher->getCustomJs($shop, $this->editingFilePath);
```

### ✅ 1.3 File Saving via FTP

**Metoda:** `saveContent()`

```php
$result = $fetcher->saveCustomCss($shop, $this->editorContent, $this->editingFilePath);
// lub
$result = $fetcher->saveCustomJs($shop, $this->editorContent, $this->editingFilePath);
```

### ✅ 1.4 View Any File

**Metoda:** `viewFile(string $url)`

- Konwersja URL -> FTP path
- Ladowanie przez FTP (edytowalne) lub HTTP (tylko odczyt)
- Automatyczne ustawienie `editingType` na podstawie rozszerzenia

### ✅ 1.5 Files Tab - Lista ze skanowania

**Plik:** `resources/views/livewire/products/visual-description/partials/css-js-editor-modal.blade.php`

**Zaimplementowano:**
- Pokazywanie listy plikow z `$shop->css_files` i `$shop->js_files`
- Kategorie: THEME, CUSTOM (edytowalne), MODULES
- Przycisk "Edytuj" przy plikach custom.*
- Przycisk "Podglad" przy pozostalych plikach
- Fix kategoryzacji: `$file['type'] ?? $file['category'] ?? 'other'`

**Weryfikacja:** Screenshot `_TOOLS/screenshots/css_js_editor_categories_FIXED.jpg`
- THEME CSS (2 pliki)
- CUSTOM (custom.css, custom.js z przyciskami "Edytuj")
- MODULES CSS (4 pliki)
- THEME JS (2 pliki)
- MODULES JS (5 plikow)

### ✅ 1.6 Integration z Asset Discovery

**Plik:** `app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php`

Computed properties `cssFilesByCategory()` i `jsFilesByCategory()`:
- Priorytet 1: Dane z `$shop->css_files`/`$shop->js_files` (skanowanie FTP)
- Fix klucza kategorii: `'type'` (AddShop) z fallback na `'category'`

### ✅ 1.7 Empty State Handling

**Plik:** `app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php`

Computed property `hasScannedFiles()`:
- Sprawdza czy istnieja zeskanowane pliki CSS lub JS
- UI wyswietla komunikat gdy brak plikow

---

## FAZA 2: Dedicated Blocks System [UKONCZONA]

**Cel:** Konwersja prestashop-section -> dedykowane bloki z parametrami per shop

### ✅ 2.1 BlockDefinition Model

**Plik:** `database/migrations/2025_12_17_121019_create_block_definitions_table.php`

Zaimplementowano pełny schemat tabeli z polami:
- shop_id, type, name, category, icon, description
- schema (JSON), render_template, css_classes (JSON)
- sample_html, is_active, usage_count
- created_by, updated_by, timestamps
- Unique constraint na (shop_id, type)

**Model:** `app/Models/BlockDefinition.php`
- Relationships: shop(), creator(), updater()
- Scopes: forShop(), active(), byCategory(), ordered()
- Accessors: contentFields, settingsFields, cssClassesString
- Methods: render(), incrementUsage(), generateTypeSlug(), validateTemplate()
- Import/Export: exportAsJson(), importFromJson()

### ✅ 2.2 DynamicBlock Class

**Plik:** `app/Services/VisualEditor/Blocks/DynamicBlock.php`

Klasa rozszerzająca BaseBlock:
- Constructor przyjmuje BlockDefinition
- render() deleguje do BlockDefinition::render()
- getSchema() zwraca schema z definicji
- Metody pomocnicze: getCssClasses(), getSampleHtml(), getShopId()
- Factory methods: fromDefinitionId(), forShop()

### ✅ 2.3 BlockRegistry Enhancement

**Plik:** `app/Services/VisualEditor/BlockRegistry.php`

Dodane metody:
- loadShopBlocks(int $shopId) - ładuje dynamiczne bloki z bazy
- unloadShopBlocks() - usuwa dynamiczne bloki z rejestru
- getBlocksForShop(int $shopId) - zwraca wszystkie bloki dla sklepu
- getDynamicBlocksForShop(int $shopId) - tylko dynamiczne bloki
- isDynamicBlock(string $type) - sprawdza czy blok jest dynamiczny
- getBlockDefinition(string $type) - pobiera model definicji
- reloadShopBlocks() - przeładowuje bloki (po edycji)

Dodana kategoria: 'shop-custom' => 'Dedykowane bloki'

### ✅ 2.4 BlockAutoGenerator Service

**Plik:** `app/Services/VisualEditor/BlockGenerator/BlockAutoGenerator.php`

Metody:
- generateFromHtml() - główna metoda analizy HTML
- analyzeStructure() - analiza struktury DOM
- extractCssClasses() - ekstrakcja klas CSS
- detectRepeaters() - wykrywanie powtarzających się elementów
- detectContentFields() - wykrywanie pól edytowalnych
- generateSchema() - generowanie schematu bloku
- generateRenderTemplate() - generowanie szablonu z placeholderami
- suggestIcon() - sugerowanie ikony na podstawie zawartości

**Plik:** `app/Services/VisualEditor/BlockGenerator/BlockAnalysisResult.php`
- DTO dla wyników analizy
- Metody: isValid(), getSummary(), toArray()

### ✅ 2.5 Przycisk "Utworz dedykowany blok"

**Plik:** `resources/views/livewire/products/visual-description/partials/block-canvas.blade.php`

Dodany przycisk w toolbarze bloku (tylko dla prestashop-section):
- Amber color scheme
- wire:click="openBlockGenerator({{ $index }})"
- Ikona plus

**Plik:** `app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php`
- Metoda openBlockGenerator(int $index)
- Metoda closeBlockGenerator()
- Computed property blockGeneratorSourceHtml
- Dispatch event do BlockGeneratorModal

### ✅ 2.6 BlockGeneratorModal Component

**Plik:** `app/Http/Livewire/Products/VisualDescription/BlockGeneratorModal.php`

Livewire component z 4-krokowym wizard:
1. Analiza - parsing HTML, wykrywanie struktury
2. Konfiguracja - nazwa, typ, ikona, pola
3. Podglad - edycja szablonu, preview
4. Zapis - potwierdzenie i zapis do bazy

**Plik:** `resources/views/livewire/products/visual-description/partials/block-generator-modal.blade.php`

UI modalu:
- Step indicator z progress
- Analysis summary (stats grid)
- Form fields (name, type, icon, description)
- Detected content fields list
- Template editor (textarea)
- Preview pane
- Navigation buttons (back/next/save)

---

## FAZA 3: Preview 1:1 z PrestaShop [UKONCZONA]

**Cel:** IFRAME preview z pelnym CSS z PrestaShop

### ✅ 3.1 Enhanced EditorPreview Trait

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/EditorPreview.php`

Zaimplementowano:
- `getShopPreviewCssProperty()` - pobiera CSS dla preview (linia 231-260)
- `getIframeContent()` - generuje kompletny HTML dla IFRAME (linia 344-474)
- Integracja z `PrestaShopCssFetcher::getCssForPreview()`

### ✅ 3.2 CSS Files Caching

**Plik:** `app/Services/VisualEditor/PrestaShopCssFetcher.php`

Zaimplementowano:
- `getCssForPreview()` - priorytetowe pobieranie CSS (linia 458-485)
- `fetchAllFromCssFiles()` - cache i pobieranie wszystkich wlaczonych plikow CSS (linia 496-540)
- Automatyczne cache'owanie przy pierwszym otwarciu preview
- HTTP fetch z timeout 10s i zapis do `cached_content`

**Weryfikacja:** Screenshots porownujace PPM preview z PrestaShop:
- `_TOOLS/screenshots/visual_editor_preview_mode.jpg`
- `_TOOLS/screenshots/prestashop_description_tab.jpg`

### ❌ 3.3 CSS Comparison Tool (OPCJONALNE)

Porownywanie CSS cache w PPM z aktualnym na PrestaShop:
- Wykrywanie zmian
- Diff view
- Auto-refresh opcja

---

## FAZA 4: Block Library per Shop

**Cel:** Biblioteka dedykowanych blokow z mozliwoscia zarzadzania

### ❌ 4.1 BlockLibrary Livewire Component

Route: `/admin/visual-editor/blocks/{shop}`

### ❌ 4.2 UI Biblioteki Blokow

- Grid z kartami blokow
- Wyszukiwanie, filtrowanie po kategorii
- Akcje: Edytuj, Usun, Duplikuj, Eksportuj

### ❌ 4.3 Import/Export Blokow

Eksport bloku do JSON, import do innego sklepu.

---

## FAZA 5: CSS Sync System

**Cel:** Dwukierunkowa synchronizacja CSS miedzy PPM a PrestaShop

### ❌ 5.1 CssSyncService

- `pullFromPrestaShop()` - odswiez cache
- `pushToPrestaShop()` - wyslij zmiany
- `detectConflicts()` - wykryj konflikty
- `resolveConflict()` - rozwiaz konflikt

### ❌ 5.2 CSS Sync Log Table

Historia synchronizacji z checksumami.

### ❌ 5.3 Sync Controls UI

Panel synchronizacji w CSS/JS Editor Modal.

---

## ARCHITEKTURA FTP

```
                    PPM
                     |
        [AddShop - FTP Config]
                     |
                     v
    [PrestaShopCssFetcher.scanFilesViaFtp()]
                     |
          +--------------------+
          |                    |
          v                    v
    css_files JSON       js_files JSON
    (prestashop_shops)   (prestashop_shops)
          |                    |
          +--------------------+
                     |
                     v
    [CssJsEditorModal.loadAssetManifest()]
                     |
                     v
           Files Tab (UI lista)
                     |
             [Edytuj] button
                     |
                     v
    [urlToFtpPath()] - konwersja URL -> FTP path
                     |
                     v
    [getCustomCss($shop, $filePath)]
                     |
                     v
           [ftpRead($config)]
                     |
                     v
           Editor Tab (textarea)
                     |
             [Zapisz] button
                     |
                     v
    [saveCustomCss($shop, $content, $filePath)]
                     |
                     v
           [ftpWrite($config, $content)]
                     |
                     v
         PrestaShop custom.css updated!
```

---

## PRIORYTET IMPLEMENTACJI

| Priorytet | Faza | Status | Szacowany czas |
|-----------|------|--------|----------------|
| 🟢 DONE | 0.1-0.5 FTP Integration | ✅ | - |
| 🟢 DONE | 1.1-1.4 Editor Core | ✅ | - |
| 🟢 DONE | 1.5-1.7 Files Tab UI | ✅ | - |
| 🟢 DONE | 3.1-3.2 Preview 1:1 | ✅ | - |
| 🟢 DONE | 2.1-2.6 Dedicated Blocks | ✅ | - |
| 🟡 SREDNI | 4.1-4.3 Block Library | ❌ | 6h |
| 🟢 NISKI | 5.1-5.3 CSS Sync | ❌ | 8h |

---

## METRYKI SUKCESU

| Metryka | Cel |
|---------|-----|
| Pliki CSS widoczne w edytorze | 100% plikow ze skanowania FTP |
| Zgodnosc podgladu 1:1 | >98% wizualnej zgodnosci |
| Edycja custom.css via FTP | Dziala bez bledow |
| Czas konwersji na dedykowany blok | <2 min |

---

## PLIKI ZMIENIONE (FAZA 1) - UKONCZONE

1. `resources/views/livewire/products/visual-description/partials/css-js-editor-modal.blade.php` ✅
   - Sekcje dla plikow ze skanowania (THEME, CUSTOM, MODULES)
   - Przyciski "Edytuj"/"Podglad" per plik

2. `app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php` ✅
   - Computed properties: `scannedCssFiles`, `scannedJsFiles`, `hasScannedFiles`
   - Computed properties: `cssFilesByCategory()`, `jsFilesByCategory()`
   - Fix kategoryzacji: `$file['type'] ?? $file['category'] ?? 'other'`

---

## PLIKI ZMIENIONE (FAZA 3) - UKONCZONE

1. `app/Http/Livewire/Products/VisualDescription/Traits/EditorPreview.php` ✅
   - `getShopPreviewCssProperty()` - pobiera CSS dla preview
   - `getIframeContent()` - generuje kompletny HTML dla IFRAME

2. `app/Services/VisualEditor/PrestaShopCssFetcher.php` ✅
   - `getCssForPreview()` - priorytetowe pobieranie CSS
   - `fetchAllFromCssFiles()` - cache i pobieranie wszystkich wlaczonych plikow CSS

---

**Utworzono**: 2025-12-17
**Zaktualizowano**: 2025-12-17
**Autor**: architect (na podstawie wymagan uzytkownika + implementacji FTP)
