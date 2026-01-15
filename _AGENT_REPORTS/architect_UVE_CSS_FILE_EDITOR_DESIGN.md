# UVE CSS File Editor - Projekt Architektoniczny

**Data:** 2026-01-13
**Agent:** architect
**Zadanie:** Zaprojektowanie CSS File Editor bezpośrednio w UVE

## Cel

Umożliwić użytkownikom edycję plików CSS z poziomu UVE bez konieczności dostępu do FTP lub zewnętrznych narzędzi. Editor powinien:
1. Wyświetlać aktualny CSS z PrestaShop (`custom.css`)
2. Pozwalać na edycję z podglądem na żywo
3. Zapisywać zmiany przez istniejący mechanizm FTP sync

## Architektura

### Komponent Lokalizacji

CSS File Editor będzie dostępny jako:
1. **Zakładka w Property Panel** - "CSS" obok Style/Layout/Advanced/Classes
2. **Pełnoekranowy Modal** - dla zaawansowanej edycji

### Przepływ Danych

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   UVE Editor    │    │  CSS File Editor │    │    PrestaShop   │
│                 │───▶│                  │───▶│   custom.css    │
│  (Livewire)     │    │   (Livewire)     │    │    (via FTP)    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
        │                       │                       │
        │                       ▼                       │
        │              ┌──────────────────┐             │
        │              │ CssSyncOrchestra-│             │
        └─────────────▶│      tor         │◀────────────┘
                       └──────────────────┘
```

## Komponenty

### 1. UVE_CssFileEditor Trait

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssFileEditor.php`

```php
<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Services\VisualEditor\CssSyncOrchestrator;

trait UVE_CssFileEditor
{
    public bool $showCssEditor = false;
    public string $cssEditorContent = '';
    public string $cssEditorOriginal = '';
    public bool $cssEditorHasChanges = false;
    public ?string $cssEditorError = null;

    /**
     * Open CSS file editor and load current CSS.
     */
    public function openCssEditor(): void
    {
        $orchestrator = app(CssSyncOrchestrator::class);
        $shop = $this->getActiveShop();

        if (!$shop) {
            $this->cssEditorError = 'Brak aktywnego sklepu';
            return;
        }

        // Fetch current CSS from FTP (force fresh fetch)
        $result = $orchestrator->fetchExistingCssWithValidation($shop, true);

        if (!$result['success']) {
            $this->cssEditorError = $result['error'] ?? 'Nie można pobrać pliku CSS';
            return;
        }

        $this->cssEditorContent = $result['css'];
        $this->cssEditorOriginal = $result['css'];
        $this->cssEditorHasChanges = false;
        $this->cssEditorError = null;
        $this->showCssEditor = true;
    }

    /**
     * Close CSS editor without saving.
     */
    public function closeCssEditor(): void
    {
        $this->showCssEditor = false;
        $this->cssEditorContent = '';
        $this->cssEditorOriginal = '';
        $this->cssEditorHasChanges = false;
    }

    /**
     * Update CSS content (called on input).
     */
    public function updateCssEditorContent(string $content): void
    {
        $this->cssEditorContent = $content;
        $this->cssEditorHasChanges = ($content !== $this->cssEditorOriginal);
    }

    /**
     * Save CSS changes to PrestaShop via FTP.
     */
    public function saveCssEditor(): void
    {
        $orchestrator = app(CssSyncOrchestrator::class);
        $shop = $this->getActiveShop();

        if (!$shop) {
            $this->cssEditorError = 'Brak aktywnego sklepu';
            return;
        }

        // Validate CSS (no global leaks)
        $validation = $orchestrator->validateCssForLeaks($this->cssEditorContent);
        if (!$validation['valid']) {
            $this->cssEditorError = 'CSS zawiera niedozwolone selektory: ' .
                implode(', ', array_column($validation['leaks'], 'selector'));
            return;
        }

        // Upload CSS via FTP
        $result = $orchestrator->uploadCss($shop, $this->cssEditorContent);

        if (!$result['success']) {
            $this->cssEditorError = $result['error'] ?? 'Błąd zapisu CSS';
            return;
        }

        // Update cache
        $shop->update([
            'cached_custom_css' => $this->cssEditorContent,
            'css_last_fetched_at' => now(),
        ]);

        $this->cssEditorOriginal = $this->cssEditorContent;
        $this->cssEditorHasChanges = false;
        $this->cssEditorError = null;

        // Notify iframe to reload CSS
        $this->dispatch('cssFileUpdated');
    }

    /**
     * Apply CSS changes to preview without saving.
     */
    public function previewCssChanges(): void
    {
        // Inject CSS into iframe for preview
        $this->dispatch('previewCss', [
            'css' => $this->cssEditorContent,
        ]);
    }

    /**
     * Revert CSS editor to original content.
     */
    public function revertCssEditor(): void
    {
        $this->cssEditorContent = $this->cssEditorOriginal;
        $this->cssEditorHasChanges = false;
    }
}
```

### 2. Widok CSS File Editor

**Plik:** `resources/views/livewire/products/visual-description/partials/css-file-editor.blade.php`

```blade
{{-- CSS File Editor Modal --}}
<div x-data="{
    expanded: false,
    lineNumbers: true
}"
    x-show="$wire.showCssEditor"
    x-cloak
    class="css-file-editor layer-modal"
    @keydown.escape="$wire.closeCssEditor()"
>
    {{-- Header --}}
    <div class="css-file-editor__header">
        <div class="css-file-editor__title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            CSS Editor - {{ $activeShop->name ?? 'Shop' }}
        </div>

        <div class="css-file-editor__actions">
            {{-- Expand/Collapse --}}
            <button type="button"
                @click="expanded = !expanded"
                class="btn-enterprise-secondary btn-enterprise-sm"
                :title="expanded ? 'Zwiń' : 'Rozwiń'">
                <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                <svg x-show="expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Close --}}
            <button type="button"
                wire:click="closeCssEditor"
                class="btn-enterprise-secondary btn-enterprise-sm">
                Zamknij
            </button>
        </div>
    </div>

    {{-- Error Message --}}
    @if($cssEditorError)
        <div class="css-file-editor__error">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $cssEditorError }}
        </div>
    @endif

    {{-- Editor Container --}}
    <div class="css-file-editor__body" :class="{ 'expanded': expanded }">
        {{-- Toolbar --}}
        <div class="css-file-editor__toolbar">
            <label class="css-file-editor__option">
                <input type="checkbox" x-model="lineNumbers">
                <span>Numery linii</span>
            </label>

            <div class="css-file-editor__info">
                <span>Linie: {{ substr_count($cssEditorContent, "\n") + 1 }}</span>
                <span>Znaki: {{ strlen($cssEditorContent) }}</span>
            </div>
        </div>

        {{-- Code Editor --}}
        <div class="css-file-editor__editor-wrapper">
            <textarea
                wire:model.live.debounce.500ms="cssEditorContent"
                class="css-file-editor__textarea"
                :class="{ 'with-line-numbers': lineNumbers }"
                spellcheck="false"
                placeholder="/* CSS styles */"></textarea>
        </div>
    </div>

    {{-- Footer --}}
    <div class="css-file-editor__footer">
        <div class="css-file-editor__status">
            @if($cssEditorHasChanges)
                <span class="badge-enterprise--warning">Niezapisane zmiany</span>
            @else
                <span class="badge-enterprise">Brak zmian</span>
            @endif
        </div>

        <div class="css-file-editor__buttons">
            <button type="button"
                wire:click="revertCssEditor"
                class="btn-enterprise-secondary btn-enterprise-sm"
                @disabled(!$cssEditorHasChanges)>
                Cofnij zmiany
            </button>

            <button type="button"
                wire:click="previewCssChanges"
                class="btn-enterprise-secondary btn-enterprise-sm"
                @disabled(!$cssEditorHasChanges)>
                Podgląd
            </button>

            <button type="button"
                wire:click="saveCssEditor"
                class="btn-enterprise-primary btn-enterprise-sm"
                @disabled(!$cssEditorHasChanges)>
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Zapisz na serwer
            </button>
        </div>
    </div>
</div>
```

### 3. Style CSS dla Editora

**Plik:** `resources/css/admin/css-file-editor.css`

```css
/* CSS File Editor Styles */
.css-file-editor {
    position: fixed;
    bottom: 0;
    right: 0;
    width: 50%;
    max-width: 800px;
    min-width: 400px;
    height: 400px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem 0 0 0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
    transition: height 0.3s ease, width 0.3s ease;
}

.css-file-editor .expanded {
    height: calc(100vh - 100px);
    width: 70%;
}

.css-file-editor__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--bg-nav);
    border-bottom: 1px solid var(--border-color);
    border-radius: 0.5rem 0 0 0;
}

.css-file-editor__title {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: var(--text-primary);
}

.css-file-editor__actions {
    display: flex;
    gap: 0.5rem;
}

.css-file-editor__error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    font-size: 0.875rem;
}

.css-file-editor__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.css-file-editor__toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--bg-card-hover);
    border-bottom: 1px solid var(--border-color);
    font-size: 0.75rem;
}

.css-file-editor__option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.css-file-editor__info {
    display: flex;
    gap: 1rem;
    color: var(--text-muted);
}

.css-file-editor__editor-wrapper {
    flex: 1;
    position: relative;
    overflow: hidden;
}

.css-file-editor__textarea {
    width: 100%;
    height: 100%;
    padding: 1rem;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    background: #1a1a2e;
    color: #e0e0e0;
    border: none;
    resize: none;
    outline: none;
    tab-size: 2;
}

.css-file-editor__textarea::placeholder {
    color: var(--text-muted);
}

.css-file-editor__textarea.with-line-numbers {
    padding-left: 4rem;
}

.css-file-editor__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--bg-nav);
    border-top: 1px solid var(--border-color);
}

.css-file-editor__status {
    font-size: 0.875rem;
}

.css-file-editor__buttons {
    display: flex;
    gap: 0.5rem;
}
```

### 4. JavaScript dla Preview

**Plik:** Dodać do `resources/js/visual-editor.js`

```javascript
// CSS Preview in iframe
document.addEventListener('livewire:initialized', () => {
    Livewire.on('previewCss', (data) => {
        const iframe = document.getElementById('uve-preview-iframe');
        if (!iframe || !iframe.contentDocument) return;

        // Find or create preview style element
        let styleEl = iframe.contentDocument.getElementById('uve-preview-css');
        if (!styleEl) {
            styleEl = iframe.contentDocument.createElement('style');
            styleEl.id = 'uve-preview-css';
            iframe.contentDocument.head.appendChild(styleEl);
        }

        // Update CSS content
        styleEl.textContent = data.css;
    });

    Livewire.on('cssFileUpdated', () => {
        // Reload iframe to get fresh CSS from server
        const iframe = document.getElementById('uve-preview-iframe');
        if (iframe) {
            iframe.src = iframe.src;
        }
    });
});
```

## Integracja z UVE

### Przycisk w Toolbar

W głównym widoku UVE dodać przycisk do otwierania CSS Editor:

```blade
{{-- W uve-toolbar.blade.php --}}
<button type="button"
    wire:click="openCssEditor"
    class="btn-enterprise-secondary btn-enterprise-sm"
    title="Edytor CSS">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
    </svg>
    CSS
</button>
```

### Use Trait w UnifiedVisualEditor

```php
class UnifiedVisualEditor extends Component
{
    use UVE_BlockManagement;
    use UVE_PropertyPanel;
    use UVE_CssClassGeneration;
    use UVE_CssSync;
    use UVE_CssFileEditor;  // NEW
    // ...
}
```

## Funkcjonalności

### MVP (Faza 1)
1. ✅ Otwarcie edytora z pobraniem aktualnego CSS
2. ✅ Edycja tekstu z podglądem
3. ✅ Walidacja CSS (no global leaks)
4. ✅ Zapis na serwer via FTP
5. ✅ Podgląd zmian w iframe

### Rozszerzenia (Faza 2)
1. ❌ Syntax highlighting (CodeMirror/Monaco)
2. ❌ Autouzupełnianie CSS properties
3. ❌ CSS minification przed zapisem
4. ❌ Historia zmian (undo/redo)
5. ❌ Import/Export CSS

### Opcjonalne (Faza 3)
1. ❌ CSS validation (csslint)
2. ❌ Formatowanie kodu
3. ❌ Snippets/Templates
4. ❌ Dark/Light theme toggle

## Bezpieczeństwo

### Walidacja CSS
Przed zapisem sprawdzać:
- Brak globalnych selektorów (`:root`, `body`, `html`, `*`)
- Brak unscoped classes (`.container`, `.btn`, `.nav`)
- Tylko scoped selektory dozwolone

### FTP Safety
- Lock mechanism przed sync
- Backup przed zapisem
- Rollback przy błędzie

## Szacowany Czas Implementacji

| Faza | Czas | Opis |
|------|------|------|
| Trait + View | 1 dzień | Podstawowa struktura |
| CSS Styles | 0.5 dnia | Stylizacja editora |
| JS Preview | 0.5 dnia | Live preview w iframe |
| Testing | 1 dzień | Testy end-to-end |
| **TOTAL** | **3 dni** | MVP |

## Pliki do Utworzenia/Modyfikacji

### Nowe pliki:
1. `app/Http/Livewire/Products/VisualDescription/Traits/UVE_CssFileEditor.php`
2. `resources/views/livewire/products/visual-description/partials/css-file-editor.blade.php`
3. `resources/css/admin/css-file-editor.css`

### Modyfikowane pliki:
1. `app/Http/Livewire/Products/VisualDescription/UnifiedVisualEditor.php` - dodać use trait
2. `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php` - dodać include
3. `resources/js/visual-editor.js` - dodać event handlers
4. `resources/css/app.css` - import nowego CSS
5. `vite.config.js` - dodać nowy CSS do build
