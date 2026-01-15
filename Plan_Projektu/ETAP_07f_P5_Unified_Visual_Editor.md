# ETAP 07f P5: Unified Visual Editor (UVE) - Fuzja VE + VBB

## Status: 🛠️ W TRAKCIE
**Data rozpoczęcia**: 2025-12-22
**Szacowany czas**: 28 dni roboczych

## Cel
Połączenie Visual Editora i Visual Block Buildera w jeden spójny system edycji opisów produktów z pełną synchronizacją CSS z PrestaShop.

## Decyzje Architektoniczne
- **CSS Sync**: Przy zapisie opisu (automatyczna synchronizacja do PrestaShop)
- **Szablony**: Global + Per Shop (globalne + sklepy mogą mieć własne)
- **Stary VBB**: Usunąć po integracji (brak duplikacji)

## UVE Definition of DONE:
⦁	Każdy blok ma poprawnie działający panel właściwości z odpowiednimi dla danego bloku parametrami
⦁	UVE umożliwa wyświetlenie i edycję .css z prestashop. Zapisanie zmian w css prez UVE jest automatycznie zapisywane w prestashop.
⦁	Wszystkie bloki z opisu prestashop są widoczne na liscie warstw w PPM
⦁	Każdy blok ma opcję zapisania go jako szablon. Szablony są zapisywane per shop prestashop ze względu na używane przez nie style css, nie można użyć szablonu bloku z opisu jednego sklepu prestashop na drugim.
⦁	Po otworzeniu UVE każdy blok powinien mieć zdefiniowane parmetry w panelu właściwości na podstawie kodu HTML + CSS pobranych z prestashop. Panel właściwosci nie może się wczytać bez zdefiniowanych parametrów.
⦁	Panel Warstwy powinien pokazywać też zagniezdżone bloki wewnątrz większych bloków, każdy zagnieżdżony blok powinien być też edytowalny.
⦁	Każda zmiana parametrów w panelu właściwości jest od razu odzwierciedlana w HTML i CSS które są przesyłane natychmiast na prestashop w momencie zapisania zmian w PPM.
⦁	Po zapisaniu zmian w opisie, opis na prestashop jest 1:1 z opisem w canva/pogląd PPM
---

## Architektura Docelowa

### Koncepcja UI
```
┌─────────────────────────────────────────────────────────────────┐
│ TOOLBAR: [Save] [Undo] [Redo] [Preview] [Code] [Import] [Sync] │
├───────────────┬───────────────────────────┬─────────────────────┤
│  LEFT PANEL   │      MAIN CANVAS          │    RIGHT PANEL      │
│               │                           │                     │
│ [Blocks Mode] │  ┌───────────────────┐    │  [Properties]       │
│ - Dodaj blok  │  │ BLOK 1 (locked)   │    │  - Typography       │
│ - Szablony    │  │ [Edit][Dup][Del]  │    │  - Spacing          │
│               │  └───────────────────┘    │  - Colors           │
│ [Elements]    │                           │  - CSS Classes      │
│ (gdy editing) │  ┌───────────────────┐    │                     │
│ - Heading     │  │ BLOK 2 (EDITING)  │    │  [Layers]           │
│ - Text        │  │ ┌─────────────┐   │    │  - Hierarchia       │
│ - Image       │  │ │ element*    │   │    │  - Visibility       │
│ - Button      │  │ │ element     │   │    │  - Locking          │
│ - Container   │  │ └─────────────┘   │    │                     │
│               │  └───────────────────┘    │                     │
└───────────────┴───────────────────────────┴─────────────────────┘
```

### Flow: Zamrożony → Odmrożony
1. **Locked (domyślnie)**: Blok renderowany jako HTML, klikanie w elementy = NIC
2. **Klik [Edit]**: Blok odmrożony, można klikać elementy wewnątrz
3. **Lewy panel**: Zmienia się z "Bloki" na "Elementy" (do wstawiania)
4. **Prawy panel**: Pokazuje właściwości wybranego elementu
5. **Klik [✓] lub poza blok**: Zamrożenie z powrotem, zapisanie zmian

### Nowa Struktura Danych
```php
$blocks = [
    [
        'id' => 'blk_001',
        'type' => 'pd-intro',
        'locked' => true,                    // Domyślnie zamrożony
        'document' => [                      // VBB document structure
            'root' => ['id', 'type', 'children' => [...], 'styles', 'classes'],
            'variables' => [],
            'cssClasses' => ['pd-intro', 'pd-base-grid']
        ],
        'compiled_html' => '<div>...</div>', // Cache HTML
        'meta' => ['created_from' => 'import', 'source_shop_id' => 1]
    ]
]
```

---

## Fazy Implementacji

### FAZA 1: Infrastruktura (2 dni) ✅ UKONCZONA 2025-12-22
- ✅ Migration: dodanie `blocks_v2`, `format_version` do product_descriptions
- ✅ Migration: rozszerzenie templates o `source_type`, `source_shop_id`, `source_product_id`, `structure_signature`, `document_json`, `labels`, `variables`, `css_classes`, `usage_count`
- ✅ Model ProductDescription: accessor z auto-migracją (getBlocksAttribute, setBlocksAttribute, convertLegacyBlocksToUve)
- ✅ Model DescriptionTemplate: nowe relacje (sourceShop, sourceProduct), scopes (bySourceType, imported, autoGenerated, manual, bySignature, withLabel), accessors (document, isUveFormat, isImported, isAutoGenerated), metody UVE (generateSignature, findSimilar, incrementUsage, addLabel, removeLabel, updateDocument, createFromProductDescription)

**PLIKI:**
- `database/migrations/2025_12_22_100001_add_unified_editor_fields_to_product_descriptions.php`
- `database/migrations/2025_12_22_100002_add_unified_editor_fields_to_description_templates.php`
- `app/Models/ProductDescription.php`
- `app/Models/DescriptionTemplate.php`

---

### FAZA 2: UnifiedVisualEditor Component (4 dni) ✅ UKONCZONA 2025-12-22
- ✅ Utworzenie UVE.php z właściwościami ($blocks, $editingBlockIndex, $selectedElementId)
- ✅ Trait UVE_BlockManagement (addBlock, removeBlock, duplicateBlock, moveBlock, freeze/unfreeze)
- ✅ Trait UVE_Preview (generatePreviewHtml, getIframeContent, getCachedShopCss)
- ✅ Trait UVE_UndoRedo (captureState, undo, redo, max 50 states)
- ✅ Blade: unified-visual-editor.blade.php + 10 partials
- ✅ Route: /admin/visual-editor/uve/{product}/shop/{shop}
- ✅ Zainstalowany blade-heroicons na produkcji

**PLIKI:**
- `app/Http/Livewire/Products/VisualDescription/UnifiedVisualEditor.php`
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_BlockManagement.php`
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php`
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_UndoRedo.php`
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-block-palette.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-block-item.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-block-properties.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-element-palette.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-element-properties.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-element-renderer.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-layers-panel.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-layer-element.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-import-modal.blade.php`
- `resources/views/admin/visual-editor/unified-editor.blade.php`
- `routes/web.php` (dodany route dla UVE)

---

### FAZA 3: Block Freeze/Unfreeze (3 dni) ✅ UKONCZONA 2025-12-22
- ✅ Metoda unfreezeBlock(index) - w UVE_BlockManagement
- ✅ Metoda freezeBlock(index, save) - w UVE_BlockManagement
- ✅ UI: Block wrapper z conditional rendering (uve-block-item.blade.php)
- ✅ Toolbar: [Edit], [Dup], [Del] / [✓], [✗] (dark theme)
- ✅ Keyboard: Escape (freeze), Enter (unfreeze), Delete, Ctrl+D (duplicate), Ctrl+Z/Y, Ctrl+S
- ✅ Trait UVE_ElementEditing: addElement, removeElement, duplicateElement, moveElement, updateElementProperty/Styles/Classes
- ✅ Dark theme CSS dla UVE (PPM brand colors: #e0ac7e)

**PLIKI:**
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_ElementEditing.php`
- `resources/views/livewire/products/visual-description/partials/uve-block-item.blade.php`
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php` (keyboard shortcuts + dark theme CSS)

---

### FAZA 4: Element Editing (4 dni) ✅ UKONCZONA 2025-12-22
- ✅ Element palette (heading, text, image, button, container, icon) - dark theme CSS
- ✅ Element selection: klik → property panel - dark theme CSS
- ✅ Property panel: typography, spacing, colors, classes - dark theme CSS
- ✅ Element renderer: PPM brand colors dla selekcji
- ✅ FIX: Przeniesienie CSS z @push do inline style (Livewire single root requirement)
- ✅ Drag & drop elementów (HTML5 Drag & Drop API + window.dispatchEvent)
- ✅ WYSIWYG inline editing (contenteditable + toolbar)
- ✅ FIX: Alpine.js event dispatch - window.dispatchEvent zamiast $dispatch (nested scope issue)

**PLIKI:**
- `resources/views/livewire/products/visual-description/partials/uve-element-palette.blade.php` ✅
- `resources/views/livewire/products/visual-description/partials/uve-element-properties.blade.php` ✅
- `resources/views/livewire/products/visual-description/partials/uve-element-renderer.blade.php` ✅
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php` ✅ (inline CSS/JS)

---

### FAZA 4.5: Edit Mode = Preview 1:1 (5-7 dni) 🛠️ W TRAKCIE
**Cel**: Tryb edycji wyświetla identycznie jak Preview (iframe 1:1 z PrestaShop) z możliwością klikania i edycji elementów.

**Decyzja architektoniczna**: Iframe + PostMessage (100% wierność renderowania)

```
PPM (Parent)                    IFRAME (Child)
    |                               |
    |-- postMessage(select, id) --> |
    |<-- postMessage(clicked, id) --|
    |-- postMessage(update, data) ->|
    |<-- postMessage(changed) ------|
```

#### FAZA 4.5.1: Infrastruktura (1-2 dni) ✅ UKONCZONA 2025-12-23
- ✅ Computed `editableIframeContent()` w UVE_Preview.php
- ✅ Metoda `injectEditableMarkers($html)` - dodaje `data-uve-id` do elementów
- ✅ Metoda `markChildElements()` - oznacza elementy potomne (heading, text, image, button, listitem, cell)
- ✅ Metoda `getEditModeScript()` - JavaScript dla postMessage communication
- ✅ CSS dla edit mode indicators (hover: dashed outline, selected: solid outline, editing: blue)
- ✅ Alpine.js component `uveEditCanvas()` - obsługa postMessage z iframe
- ✅ Blade template: edit mode z iframe + device selector + selection overlay
- ✅ Block type labels (data-uve-block-type) widoczne przy hover

**PLIKI:**
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` (rozszerzone)
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php` (edit canvas + Alpine.js)

#### FAZA 4.5.2: Integracja Livewire + Alpine.js (2-3 dni) ✅ UKONCZONA 2025-12-23
- ✅ Modyfikacja blade dla trybu edit z iframe (DONE w 4.5.1)
- ✅ Alpine.js component `uveEditCanvas()` - nasłuchuje postMessage (DONE w 4.5.1)
- ✅ Selection overlay nad iframe (akcje: Edit, Scroll) (DONE w 4.5.1)
- ✅ Synchronizacja stanu: iframe ↔ Livewire ($wire.$set() zamiast method call)
- ✅ FIX: wire:loading flickering - dodano wire:target dla długich operacji
- ✅ Mapowanie data-uve-id → block index (parseBlockIndex() w Alpine.js)

**PLIKI:**
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php`
- `_DOCS/PPM_Styling_Playbook.md` (sekcja 9: Livewire Loading Overlays)

#### FAZA 4.5.3: Layers Panel + Properties Sync (2-3 dni) ✅ UKONCZONA 2025-12-23
- ✅ 4.5.3.1: Property panel → iframe synchronization (bez flickeringu)
- ✅ 4.5.3.2: Raw-html layer parsing (parseRawHtmlLayers() - pokazuje div elements z pd-* classes i z-index)
- ✅ Dark theme styling dla paneli Właściwości i Warstwy (zgodne z PPM Styling Playbook)
- ✅ Usunięcie klas Tailwind na rzecz dedykowanych klas CSS

**PLIKI:**
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` (parseRawHtmlLayers, getBlockLayers)
- `resources/views/livewire/products/visual-description/partials/uve-layers-panel.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-block-properties.blade.php`
- `resources/views/livewire/products/visual-description/partials/uve-element-properties.blade.php`

#### FAZA 4.5.4: Inline Editing w Iframe (2-3 dni) ✅ UKONCZONA 2025-12-23
- ✅ contenteditable w iframe po double-click na element tekstowy
- ✅ Inline toolbar z przyciskami B/I/U/Link/Unlink (dark theme)
- ✅ Keyboard shortcuts: Ctrl+B (bold), Ctrl+I (italic), Ctrl+U (underline)
- ✅ Escape przywraca oryginalną treść (cancel edit)
- ✅ Enter kończy edycję i zapisuje zmiany
- ✅ Synchronizacja zmian tekstu: iframe → Livewire (postMessage: uve:content-changed)
- ✅ updateElementContentFromIframe() - odbiera content z iframe
- ✅ refreshIframeContent() - explicit refresh po zmianach w property panel

**PLIKI:**
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_ElementEditing.php` (updateElementContentFromIframe, refreshIframeContent)
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` (getEditModeScript rozszerzone o inline toolbar)

#### FAZA 4.5.5: Drag & Drop (opcjonalne, 2-3 dni) ❌
- ❌ Drag handles nad iframe dla reorder bloków
- ❌ Drop zones między blokami
- ❌ Visual feedback podczas drag

**Raport architekta:** `_AGENT_REPORTS/architect_UVE_EDIT_PREVIEW_MERGE.md`

---

### FAZA PP: Property Panel System (18-22 dni) ✅ UKONCZONA 2025-12-23

**Cel**: Kompletny panel właściwości z kontrolkami CSS na podstawie klas PrestaShop (31 klas pd-*).

**PODSUMOWANIE:** Zaimplementowano 36 plików (8 serwisów PHP + 17 kontrolek Blade + 8 Traits PHP + 3 modyfikacje istniejących plików).

#### FAZA PP.1: Infrastruktura i Registry (3-4 dni) ✅ UKONCZONA 2025-12-23
- ✅ PP.1.1: PropertyControlRegistry - rejestr typów kontrolek (19 typów)
- ✅ PP.1.2: CssClassControlMapper - mapowanie 40+ klas pd-* na kontrolki
- ✅ PP.1.3: PropertyPanelService - budowanie konfiguracji panelu
- ✅ PP.1.4: PropertyControlInterface + ControlDefinitions + CssValueFormatter

**PLIKI:**
- `app/Contracts/VisualEditor/PropertyPanel/PropertyControlInterface.php` (150 linii)
- `app/Services/VisualEditor/PropertyPanel/PropertyControlRegistry.php` (221 linii)
- `app/Services/VisualEditor/PropertyPanel/ControlDefinitions.php` (321 linii)
- `app/Services/VisualEditor/PropertyPanel/AdvancedControlDefinitions.php` (387 linii)
- `app/Services/VisualEditor/PropertyPanel/CssClassControlMapper.php` (278 linii)
- `app/Services/VisualEditor/PropertyPanel/CssClassMappingDefinitions.php` (393 linii)
- `app/Services/VisualEditor/PropertyPanel/PropertyPanelService.php` (504 linii)
- `app/Services/VisualEditor/PropertyPanel/CssValueFormatter.php` (584 linii)

---

#### FAZA PP.2: Podstawowe kontrolki UI (5-6 dni) ✅ UKONCZONA 2025-12-23
- ✅ PP.2.1: Box Model control (padding/margin 4 strony + linked toggle)
- ✅ PP.2.2: Typography controls (font-size, weight, line-height, letter-spacing, transform, decoration, align)
- ✅ PP.2.3: Color Picker z presetami PPM + hex/rgba
- ✅ PP.2.4: Gradient Editor (linear/radial)
- ✅ PP.2.5: Layout Flex controls (direction, wrap, justify, align, gap)
- ✅ PP.2.6: Layout Grid controls (columns, rows, gap)
- ✅ PP.2.7: Border controls (width, style, color, radius)
- ✅ PP.2.8: Background controls (color, image, position, size, repeat, attachment)
- ✅ PP.2.9: Effects controls (box-shadow, text-shadow, opacity)
- ✅ PP.2.10: Transform controls (rotate, scale, translate, origin)
- ✅ PP.2.11: Position controls (type, top/right/bottom/left, z-index)
- ✅ PP.2.12: Size controls (width, height, min/max)

**PLIKI** (w `resources/views/livewire/products/visual-description/controls/`):
- `box-model.blade.php` ✅
- `typography.blade.php` ✅
- `color-picker.blade.php` ✅
- `gradient-editor.blade.php` ✅
- `layout-flex.blade.php` ✅
- `layout-grid.blade.php` ✅
- `border.blade.php` ✅
- `background.blade.php` ✅
- `effects.blade.php` ✅
- `transform.blade.php` ✅
- `position.blade.php` ✅
- `size.blade.php` ✅

---

#### FAZA PP.3: Kontrolki specjalne PrestaShop + Media (5-6 dni) ✅ UKONCZONA 2025-12-23
- ✅ PP.3.1: Slider Settings (Splide.js: type, perPage, autoplay, arrows, pagination)
- ✅ PP.3.2: Parallax Settings (height, overlay color/opacity, text position)
- ✅ PP.3.3: Media Picker - TAB Galeria (integracja z MediaManager)
- ✅ PP.3.4: Media Picker - TAB Upload (drag&drop + progress)
- ✅ PP.3.5: Media Picker - TAB URL (input + preview)
- ✅ PP.3.6: Responsive images (różne obrazki per breakpoint)
- ✅ PP.3.7: Cover/gradient editor (pd-cover__picture)
- ✅ PP.3.8: **BUG FIX (2026-01-14):** Integracja `UVE_MediaPicker` trait z `UnifiedVisualEditor`
      └── Dodano import i `use UVE_MediaPicker` do komponentu
      └── Naprawiono `$clientSeq undefined` w `UVE_PropertyPanel.php:764`
      └── Media Picker Modal - zweryfikowane: Galeria + Upload + URL

**PLIKI:**
- `resources/views/livewire/products/visual-description/controls/slider-settings.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/parallax-settings.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/media-picker.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/responsive-images.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/responsive-wrapper.blade.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_SliderEditing.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_ParallaxEditing.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_MediaPicker.php` ✅

---

#### FAZA PP.4: Integracja, Hover States i Responsive (5-6 dni) ✅ UKONCZONA 2025-12-23
- ✅ PP.4.1: Główny panel z 4 zakładkami (Style, Layout, Advanced, Classes)
- ✅ PP.4.2: Dynamiczne ładowanie kontrolek na podstawie klas CSS
- ✅ PP.4.3: Synchronizacja panel → Livewire → iframe (postMessage)
- ✅ PP.4.4: Hover States - toggle Normal/Hover
- ✅ PP.4.5: Hover States - duplikat kontrolek dla :hover + presets
- ✅ PP.4.6: Transition settings (duration, timing-function, delay, properties, cubic-bezier editor)
- ✅ PP.4.7: Device Switcher (Desktop/Tablet/Mobile) w toolbarze
- ✅ PP.4.8: Responsive styles storage (per breakpoint)
- ✅ PP.4.9: Preview resize dla device preview
- ✅ PP.4.10: Real-time preview w iframe

**PLIKI:**
- `resources/views/livewire/products/visual-description/partials/uve-property-panel-v2.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/hover-states.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/transition.blade.php` ✅
- `resources/views/livewire/products/visual-description/controls/device-switcher.blade.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_ResponsiveStyles.php` ✅
- `app/Http/Livewire/Products/VisualDescription/UnifiedVisualEditor.php` (modyfikacja) ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` (modyfikacja) ✅

**Raport architekta:** `C:\Users\kamil\.claude\plans\humble-wobbling-hearth.md`

---

### FAZA 5: CSS Synchronizacja (4 dni) ✅ UKONCZONA 2025-01-07
- ✅ CssPropertyMapper: VBB styles → CSS (512 linii, pełne mapowanie camelCase → kebab-case)
- ✅ CssRuleGenerator: generowanie CSS rules + hover styles + responsive media queries
- ✅ CssSyncOrchestrator: fetch → modify → upload (505 linii)
- ✅ Integracja z save() - afterSaveCssSync() wywoływane automatycznie
- ✅ UI feedback - przycisk "CSS Sync" w toolbarze + status badge
- ✅ PrestaShopCssFetcher: pobieranie CSS z FTP (1356 linii)
- ✅ UVE_CssSync trait: syncCss(), previewCss(), testCssConnection(), toggleAutoSyncCss()

**PLIKI:**
- `app/Services/VisualEditor/CssSyncOrchestrator.php` ✅
- `app/Services/VisualEditor/CssPropertyMapper.php` ✅
- `app/Services/VisualEditor/CssRuleGenerator.php` ✅ (+ hover styles 2025-01-07)
- `app/Services/VisualEditor/PrestaShopCssFetcher.php` ✅
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssSync.php` ✅

---

### FAZA 6: System Szablonów (3 dni) ❌
- ❌ TemplatePlaceholderService
- ❌ Quick action "Zapisz jako szablon"
- ❌ Template browser: global + per-shop
- ❌ Load template
- ❌ Variables schema

**PLIKI:**
- `app/Services/VisualEditor/TemplatePlaceholderService.php`
- `resources/views/livewire/products/visual-description/partials/template-browser.blade.php`
- `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Templates.php`

---

### FAZA 7: Auto-szablony z Importu (2 dni) ❌
- ❌ AutoTemplateService
- ❌ Deduplikacja (structure_signature)
- ❌ Hook w import flow
- ❌ Labels auto-generated

**PLIKI:**
- `app/Services/VisualEditor/AutoTemplateService.php`
- `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php`

---

### FAZA 8: Slider/JS Elements (3 dni) ❌
- ❌ Slider element type
- ❌ Property panel dla slidera
- ❌ Slides editor
- ❌ Splide.js w preview
- ❌ Edit mode: slajdy jako lista

**PLIKI:**
- `resources/views/livewire/products/visual-description/partials/slider-properties.blade.php`

---

### FAZA 9: Migracja i Cleanup (3 dni) ❌
- ❌ Skrypt migracji blocks_json → blocks_v2
- ❌ Backward compatibility
- ❌ Usunięcie starego VBB
- ❌ Redirect starych URL
- ❌ E2E testy
- ❌ Dokumentacja

**PLIKI DO USUNIĘCIA:**
- `app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php`
- `resources/views/livewire/products/visual-description/block-builder/`

---

## Timeline

| Faza | Dni | Status |
|------|-----|--------|
| 1. Infrastruktura | 2 | ✅ |
| 2. UVE Component | 4 | ✅ |
| 3. Freeze/Unfreeze | 3 | ✅ |
| 4. Element Editing | 4 | ✅ |
| 4.5.1 Iframe Infrastructure | 2 | ✅ |
| 4.5.2 Livewire + Alpine.js | 2 | ✅ |
| 4.5.3 Layers + Properties | 2 | ✅ |
| 4.5.4 Inline Editing | 2-3 | ✅ |
| 4.5.5 Drag & Drop (opt) | 2-3 | ❌ |
| **PP.1 Infrastruktura Registry** | **3-4** | ✅ |
| **PP.2 Podstawowe kontrolki** | **5-6** | ✅ |
| **PP.3 Kontrolki PrestaShop + Media** | **5-6** | ✅ |
| **PP.4 Integracja + Hover + Responsive** | **5-6** | ✅ |
| 5. CSS Sync | 4 | ❌ |
| 6. Szablony | 3 | ❌ |
| 7. Auto-szablony | 2 | ❌ |
| 8. Slider/JS | 3 | ❌ |
| 9. Migracja | 3 | ❌ |
| **TOTAL** | **54-62** | **~65% done** |

---

## Definition of Done
- [x] Preview działa 1:1 (IFRAME + CSS PrestaShop) ✅ 2025-12-23
- [x] Bloki domyślnie zamrożone, klik Edit → odmrożenie
- [x] Można klikać elementy wewnątrz odmrożonego bloku
- [x] Property panel edytuje właściwości elementu (podstawowe)
- [x] Drag & drop elementów wewnątrz bloku
- [x] WYSIWYG inline editing (double-click)
- [x] **Edit Mode = Preview 1:1 (Iframe + PostMessage)** ✅ 2025-12-23
- [x] Layers panel pokazuje strukturę raw-html ✅ 2025-12-23
- [x] Dark theme UI zgodny z PPM Styling Playbook ✅ 2025-12-23
- [x] **Property Panel: Kontrolki CSS na podstawie klas pd-*** ✅ 2025-12-23
- [x] **Property Panel: Box Model, Typography, Colors, Layout, Border, Background, Effects, Transform** ✅ 2025-12-23
- [x] **Property Panel: Slider Settings (Splide.js)** ✅ 2025-12-23
- [x] **Property Panel: Parallax Settings** ✅ 2025-12-23
- [x] **Property Panel: Media Picker (galeria + upload + URL)** ✅ 2025-12-23
- [x] **Property Panel: Hover States + Transition** ✅ 2025-12-23
- [x] **Property Panel: Responsive breakpoints (Desktop/Tablet/Mobile)** ✅ 2025-12-23
- [ ] CSS synchronizowany przy zapisie opisu
- [ ] Szablony globalne + per-shop
- [ ] Auto-szablony przy imporcie
- [ ] Slidery działają i są konfigurowalne
- [ ] Stary VBB usunięty
- [ ] Dokumentacja kompletna

---

## Bugfixy i Hotfixy

### 2025-12-23: Preview Fix + Multi-Store
- ✅ FIX: Preview używał prostego szablonu iframe - naprawiono na pełny template z EditorPreview
- ✅ FIX: UVE ładowało globalny `Product.long_description` zamiast per-shop `ProductShopData.long_description`
- ✅ FIX: Shop switcher - dodano dropdown do przełączania między sklepami
- ✅ FIX: `switchShop()` void return error

### 2025-12-23: Flickering Fix + Layers Panel + Dark Theme Styling
- ✅ FIX: wire:loading bez wire:target powodował flickering przy każdym Livewire request
  - Rozwiązanie: `wire:target="save, syncCss, executeImport, compileAllBlocks"`
  - Dokumentacja: `_DOCS/PPM_Styling_Playbook.md` sekcja 9
- ✅ FIX: Selection change via `$wire.$set()` zamiast method call (eliminuje full re-render)
- ✅ FEATURE: parseRawHtmlLayers() - parsing raw-html bloków na warstwy (pd-* classes, z-index)
- ✅ FIX: Dark theme styling dla paneli Właściwości i Warstwy
  - Zamiana jasnych kolorów (#f3f4f6, #374151) na ciemne (#334155, #e2e8f0)
  - Usunięcie klas Tailwind (text-gray-*) na rzecz dedykowanych CSS classes
  - Spójność z PPM brand colors (#e0ac7e)
