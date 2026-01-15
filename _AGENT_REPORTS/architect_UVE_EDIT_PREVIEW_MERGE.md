# UVE Edit-Preview Merge Architecture Report

**Data**: 2025-12-23
**Agent**: architect
**Zadanie**: Analiza architektury UVE w celu osiagniecia EDIT MODE = PREVIEW 1:1

---

## 1. STRESZCZENIE WYKONAWCZE

### Cel
Przeksztalcic tryb edycji UVE (Unified Visual Editor) tak, aby wyswietlal bloki identycznie jak Preview (iframe 1:1 z PrestaShop), jednoczesnie umozliwiajac klikanie i edycje elementow.

### Rekomendacja
**Podejscie B: Iframe z PostMessage** - najlepszy balans miedzy wiernoscza renderowania a zlozonoscia implementacji.

---

## 2. ANALIZA AKTUALNEJ ARCHITEKTURY

### 2.1 Struktura UVE

```
UnifiedVisualEditor.php
  |-- Traits/UVE_Preview.php       (iframe preview, CSS fetching)
  |-- Traits/UVE_BlockManagement.php
  |-- Traits/UVE_ElementEditing.php
  |-- Traits/UVE_CssSync.php
```

### 2.2 Tryby Widoku

| Tryb | Rendering | CSS PrestaShop | Edycja |
|------|-----------|----------------|--------|
| **Edit** | Blade + Livewire | NIE | TAK |
| **Preview** | iframe srcdoc | TAK | NIE |
| **Code** | pre/code | N/A | NIE |

### 2.3 Problem Fundamentalny

**Edit Mode:**
- Renderuje bloki przez `uve-block-item.blade.php`
- Uzywa lokalnych klas CSS (`.uve-block`, `.uve-block-content`)
- Brak CSS z PrestaShop (theme.css, custom.css)
- Wizualnie ROZNI SIE od Preview

**Preview Mode:**
- Renderuje przez iframe srcdoc
- Laduje pelny CSS z PrestaShop
- Wiernie odwzorowuje wyglad na sklepie
- ZERO interaktywnosci edycji

### 2.4 Istniejace Zasoby

**PrestaShopCssFetcher** (app/Services/VisualEditor/PrestaShopCssFetcher.php):
- `getFullCssForPreview()` - pelny CSS dla preview
- `fetchAllFromCssFiles()` - multi-file CSS support
- Cache CSS z URL lub FTP
- Scoped CSS juz istnieje w `UVE_Preview::canvasPreviewCss()`

**VBB (Visual Block Builder)** - odniesienie:
- Renderuje elementy przez `element-renderer.blade.php`
- Inline styles + CSS classes
- Previewuje CSS z `previewCss()` w BlockBuilderCanvas

---

## 3. ANALIZA PODEJSC

### PODEJSCIE A: Scoped CSS w Canvas

**Koncepcja:**
Wstrzyknac PrestaShop CSS do strony PPM, scope'ujac selektory do `.uve-canvas`.

```php
// UVE_Preview.php - juz istnieje!
#[Computed]
public function canvasPreviewCss(): string
{
    $baseCss = $this->shopPreviewCss;

    // Scope all selectors to .uve-canvas
    $scopedCss = preg_replace_callback(
        '/([^{]+)\{/',
        fn($m) => '.uve-canvas ' . trim($m[1]) . ' {',
        $baseCss
    );

    return $scopedCss;
}
```

**Zalety:**
- Najprostsze do implementacji (mechanizm juz istnieje)
- Zero iframe = pelna kontrola Livewire
- Natywne wire:click, wire:model, etc.
- Niska latencja interakcji

**Wady:**
- CSS conflicts z admin CSS
- Imperfect scoping (pseudo-elements, @media, @keyframes)
- Brak izolacji CSS variables (:root)
- Potencjalne "wyciekanie" stylów
- NIE bedzie 100% wierne Preview

**Ocena wiernosci:** 70-85%

---

### PODEJSCIE B: Iframe z PostMessage (REKOMENDOWANE)

**Koncepcja:**
Uzyc iframe jak w Preview, ale dodac warste interaktywnosci przez postMessage.

```
PPM (Parent)                    IFRAME (Child)
    |                               |
    |-- postMessage(select, id) --> |
    |<-- postMessage(clicked, id) --|
    |-- postMessage(update, data) ->|
    |<-- postMessage(changed) ------|
```

**Architektura:**

```blade
{{-- Edit mode with interactive iframe --}}
<div class="uve-canvas-wrapper">
    <iframe
        id="uve-edit-iframe-{{ $this->getId() }}"
        srcdoc="{{ $this->editableIframeContent }}"
        class="uve-edit-iframe"
    ></iframe>

    {{-- Overlay for selection indicators --}}
    <div class="uve-selection-overlay" x-ref="selectionOverlay"></div>
</div>
```

```javascript
// W iframe (injected script)
document.addEventListener('click', (e) => {
    const element = e.target.closest('[data-uve-id]');
    if (element) {
        e.preventDefault();
        e.stopPropagation();
        parent.postMessage({
            type: 'uve:element-clicked',
            elementId: element.dataset.uveId,
            rect: element.getBoundingClientRect()
        }, '*');
    }
});

// Listener na update
window.addEventListener('message', (e) => {
    if (e.data.type === 'uve:update-element') {
        const el = document.querySelector(`[data-uve-id="${e.data.elementId}"]`);
        if (el) {
            // Apply changes
            el.innerHTML = e.data.content;
            el.style.cssText = e.data.styles;
        }
    }
});
```

```javascript
// W PPM (Alpine.js)
function uveEditableCanvas() {
    return {
        init() {
            window.addEventListener('message', (e) => {
                if (e.data.type === 'uve:element-clicked') {
                    this.$wire.selectElementById(e.data.elementId);
                    this.updateSelectionOverlay(e.data.rect);
                }
            });
        },

        updateSelectionOverlay(rect) {
            const overlay = this.$refs.selectionOverlay;
            overlay.style.left = rect.left + 'px';
            overlay.style.top = rect.top + 'px';
            overlay.style.width = rect.width + 'px';
            overlay.style.height = rect.height + 'px';
        }
    }
}
```

**Zalety:**
- 100% wiernosc renderowania (ten sam iframe co Preview)
- Pelna izolacja CSS
- Responsywne breakpoints dzialaja naturalnie
- Sliders (Splide) dzialaja jak na produkcji
- Mozliwosc inline editing w iframe

**Wady:**
- Wieksza zlozonosc implementacji
- Komunikacja async przez postMessage
- Latency przy edycji (ok. 10-50ms)
- Trzeba zarzadzac stanem w dwoch kontekstach

**Ocena wiernosci:** 99-100%

---

### PODEJSCIE C: Shadow DOM

**Koncepcja:**
Enkapsulowac kazdy blok w Shadow DOM z wlasnym CSS.

```javascript
class UveBlock extends HTMLElement {
    connectedCallback() {
        const shadow = this.attachShadow({ mode: 'open' });
        shadow.innerHTML = `
            <style>${this.getAttribute('shop-css')}</style>
            ${this.getAttribute('html-content')}
        `;
    }
}
customElements.define('uve-block', UveBlock);
```

**Zalety:**
- Pelna izolacja CSS per-blok
- Natywna interakcja DOM
- Nowoczesne podejscie

**Wady:**
- Livewire nie dziala dobrze z Shadow DOM
- wire:click nie przechodzi przez shadow boundary
- Wymaga custom element registration
- Slaba kompatybilnosc z Alpine.js
- Problemy z nested components

**Ocena wiernosci:** 90-95%

---

## 4. POROWNANIE PODEJSC

| Kryterium | A: Scoped CSS | B: Iframe+PostMessage | C: Shadow DOM |
|-----------|---------------|----------------------|---------------|
| Wiernosc renderowania | 70-85% | 99-100% | 90-95% |
| Zlozonosc implementacji | Niska | Srednia | Wysoka |
| Kompatybilnosc Livewire | Pelna | Ograniczona | Slaba |
| Izolacja CSS | Slaba | Pelna | Pelna |
| Responsywnosc | Slaba | Pelna | Srednia |
| Splide/Sliders | Problemy | Dzialaja | Problemy |
| Inline editing | Latwe | Srednie | Trudne |
| Czas implementacji | 2-3 dni | 5-7 dni | 7-10 dni |

---

## 5. REKOMENDACJA: PODEJSCIE B (Iframe + PostMessage)

### 5.1 Uzasadnienie

1. **100% Wiernosc** - Ten sam mechanizm renderowania co Preview
2. **Udowodniony Pattern** - Uzywany przez Figma, Webflow, Framer
3. **CSS Isolation** - Zero konfliktow z admin panel
4. **Responsywnosc** - Device breakpoints dzialaja naturalnie
5. **Skalowalne** - Latwo dodac inline editing, drag-drop

### 5.2 Plan Implementacji

#### FAZA 1: Infrastruktura (1-2 dni)

**1.1 Nowy computed property w UVE_Preview.php:**
```php
#[Computed]
public function editableIframeContent(): string
{
    $html = $this->previewHtml;
    $css = $this->shopPreviewCss;
    $editScript = $this->getEditModeScript();

    // Add data-uve-id to all editable elements
    $html = $this->injectEditableMarkers($html);

    return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <style>{$css}</style>
    <style>
        /* Edit mode indicators */
        [data-uve-id]:hover {
            outline: 2px dashed rgba(224, 172, 126, 0.5) !important;
            cursor: pointer;
        }
        [data-uve-id].uve-selected {
            outline: 3px solid #e0ac7e !important;
        }
    </style>
</head>
<body>
    <div id="description">
        <div class="product-description">
            <div class="rte-content">
                {$html}
            </div>
        </div>
    </div>
    <script>{$editScript}</script>
</body>
</html>
HTML;
}
```

**1.2 JavaScript komunikacji (inject do iframe):**
```javascript
// _TOOLS/uve-edit-mode.js
(function() {
    // Highlight on hover
    document.addEventListener('mouseover', (e) => {
        const el = e.target.closest('[data-uve-id]');
        if (el) el.classList.add('uve-hover');
    });

    document.addEventListener('mouseout', (e) => {
        const el = e.target.closest('[data-uve-id]');
        if (el) el.classList.remove('uve-hover');
    });

    // Click to select
    document.addEventListener('click', (e) => {
        e.preventDefault();
        const el = e.target.closest('[data-uve-id]');
        if (el) {
            // Clear previous selection
            document.querySelectorAll('.uve-selected').forEach(s =>
                s.classList.remove('uve-selected')
            );
            el.classList.add('uve-selected');

            parent.postMessage({
                type: 'uve:select',
                elementId: el.dataset.uveId,
                elementType: el.dataset.uveType,
                rect: el.getBoundingClientRect(),
                content: el.innerHTML
            }, '*');
        }
    });

    // Listen for updates from parent
    window.addEventListener('message', (e) => {
        const { type, elementId, content, styles } = e.data;

        if (type === 'uve:update') {
            const el = document.querySelector(`[data-uve-id="${elementId}"]`);
            if (el) {
                if (content !== undefined) el.innerHTML = content;
                if (styles) Object.assign(el.style, styles);

                parent.postMessage({ type: 'uve:updated', elementId }, '*');
            }
        }

        if (type === 'uve:deselect') {
            document.querySelectorAll('.uve-selected').forEach(s =>
                s.classList.remove('uve-selected')
            );
        }
    });

    console.log('[UVE] Edit mode initialized');
})();
```

#### FAZA 2: Integracja z Livewire (2-3 dni)

**2.1 Modyfikacja widoku Blade:**
```blade
{{-- unified-visual-editor.blade.php --}}
@if($viewMode === 'edit')
    <div class="uve-canvas-wrapper" x-data="uveEditCanvas()">
        {{-- Interactive iframe --}}
        <iframe
            x-ref="editFrame"
            id="uve-edit-iframe-{{ $this->getId() }}"
            srcdoc="{{ $this->editableIframeContent }}"
            class="uve-edit-iframe"
            @load="onFrameLoad()"
        ></iframe>

        {{-- Selection overlay (outside iframe for better control) --}}
        <div
            class="uve-selection-overlay"
            x-ref="overlay"
            x-show="selectedRect"
            x-bind:style="overlayStyle"
        >
            <div class="uve-selection-actions">
                <button @click="editElement">Edit</button>
                <button @click="duplicateElement">Duplicate</button>
                <button @click="deleteElement">Delete</button>
            </div>
        </div>
    </div>
@endif
```

**2.2 Alpine.js Component:**
```javascript
// uveEditCanvas()
function uveEditCanvas() {
    return {
        selectedElementId: null,
        selectedRect: null,

        get overlayStyle() {
            if (!this.selectedRect) return '';
            const r = this.selectedRect;
            return `left:${r.left}px;top:${r.top}px;width:${r.width}px;height:${r.height}px`;
        },

        init() {
            window.addEventListener('message', this.handleMessage.bind(this));
        },

        handleMessage(e) {
            if (e.data.type === 'uve:select') {
                this.selectedElementId = e.data.elementId;
                this.selectedRect = e.data.rect;
                this.$wire.selectElementById(e.data.elementId);
            }
        },

        editElement() {
            this.$wire.startInlineEdit(this.selectedElementId);
        },

        updateInIframe(elementId, content, styles) {
            this.$refs.editFrame.contentWindow.postMessage({
                type: 'uve:update',
                elementId,
                content,
                styles
            }, '*');
        },

        onFrameLoad() {
            // Frame ready - can send initial state
            console.log('[UVE] Edit frame loaded');
        }
    }
}
```

#### FAZA 3: Inline Editing (2-3 dni)

**3.1 contenteditable w iframe:**
```javascript
// Dodac do edit-mode.js
window.addEventListener('message', (e) => {
    if (e.data.type === 'uve:start-edit') {
        const el = document.querySelector(`[data-uve-id="${e.data.elementId}"]`);
        if (el) {
            el.contentEditable = true;
            el.focus();

            // Select all text
            const range = document.createRange();
            range.selectNodeContents(el);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);

            el.addEventListener('blur', () => {
                el.contentEditable = false;
                parent.postMessage({
                    type: 'uve:content-changed',
                    elementId: e.data.elementId,
                    content: el.innerHTML
                }, '*');
            }, { once: true });
        }
    }
});
```

#### FAZA 4: Drag & Drop (opcjonalnie, 2-3 dni)

Reorder blokow przez drag handles nad iframe.

---

## 6. RYZYKA I MITYGACJE

| Ryzyko | Prawdopodobienstwo | Wplyw | Mitygacja |
|--------|-------------------|-------|-----------|
| Latency postMessage | Niskie | Niski | Debounce updates, optimistic UI |
| Cross-origin issues | Srednie | Wysoki | Same-origin sandbox, srcdoc |
| Memory leaks | Niskie | Sredni | Cleanup event listeners |
| Mobile touch events | Srednie | Sredni | Touch event handling w iframe |
| Undo/Redo state | Srednie | Sredni | Centralizacja stanu w Livewire |

---

## 7. METRYKI SUKCESU

1. **Wiernosc renderowania**: Edit mode identyczny jak Preview (100%)
2. **Responsywnosc**: Przelaczanie desktop/tablet/mobile dziala
3. **Edycja inline**: Klikniecie -> edycja tekstu < 200ms
4. **Brak CSS leak**: Zero konfliktow z admin panel
5. **Splide sliders**: Dzialaja w edit mode

---

## 8. PLIKI DO MODYFIKACJI

```
MODYFIKOWANE:
- app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php
- resources/views/livewire/products/visual-description/unified-visual-editor.blade.php
- resources/views/livewire/products/visual-description/partials/uve-block-item.blade.php

NOWE:
- resources/js/uve-edit-mode.js
- resources/css/visual-editor/uve-edit-canvas.css

OPCJONALNE:
- app/Http/Livewire/Products/VisualDescription/Traits/UVE_InlineEditing.php
```

---

## 9. KOLEJNE KROKI

1. **Natychmiast**: Zatwierdzenie podejscia przez PM/uzytkownika
2. **Sprint 1**: FAZA 1 + FAZA 2 (infrastruktura + integracja)
3. **Sprint 2**: FAZA 3 (inline editing)
4. **Backlog**: FAZA 4 (drag & drop)

---

## 10. DIAGRAM ARCHITEKTURY

```
+---------------------------+
|   PPM Admin Panel         |
|  +---------------------+  |
|  | UnifiedVisualEditor |  |
|  | (Livewire Component)|  |
|  +----------+----------+  |
|             |              |
|   +---------v---------+    |
|   |   uve-canvas      |    |
|   | +--------------+  |    |
|   | |   IFRAME     |<-+-------- postMessage: uve:select
|   | | (srcdoc)     |  |    |
|   | | - shop CSS   |  |    |
|   | | - edit.js    +--+-------- postMessage: uve:update
|   | | - data-uve-* |  |    |
|   | +--------------+  |    |
|   |                   |    |
|   | [Selection        |    |
|   |  Overlay]         |    |
|   +-------------------+    |
|             |              |
|   +---------v---------+    |
|   | Properties Panel  |    |
|   | (Right sidebar)   |    |
|   +-------------------+    |
+---------------------------+
```

---

**Raport przygotowany przez:** architect agent
**Wersja:** 1.0
**Status:** Gotowy do review
