# UVE Property Panel - Integracja Kontrolek z Canvas

## Architektura

```
[Control Blade] -> $wire.updateControlValue(controlId, value)
       |
       v
[UVE_PropertyPanel.php] -> updateControlValue() -> formatToCss()
       |
       v
[CssValueFormatter.php] -> formatTypography/formatBorder/... -> CSS array
       |
       v
[syncToIframe()] -> $this->js("window.uveApplyStyles({data})")
       |
       v
[unified-visual-editor.blade.php] -> window.uveApplyStyles() -> iframe element.style
```

## Kluczowe Pliki

| Plik | Rola |
|------|------|
| `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php` | PHP Trait - obsługa updateControlValue, syncToIframe |
| `app/Services/VisualEditor/PropertyPanel/CssValueFormatter.php` | Konwersja wartości kontrolek na CSS |
| `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php` | window.uveApplyStyles() - aplikacja styli w iframe |
| `resources/views/livewire/products/visual-description/controls/*.blade.php` | Kontrolki Property Panel |

## Rozwiązane Problemy

### 1. $this->dispatch() nie docierał do JavaScript

**Problem:** `$this->dispatch('sync-to-iframe', ...)` w Livewire 3.x nie docierał do Alpine.js listeners.

**Rozwiązanie:** Użycie `$this->js()` do bezpośredniego wywołania JavaScript:
```php
$this->js("
    if (window.uveApplyStyles) {
        window.uveApplyStyles({$jsData});
    }
");
```

### 2. Iframe nie znaleziony

**Problem:** Selektor `#edit-preview-frame` nie działał - iframe ma dynamiczne ID.

**Rozwiązanie:** Użycie selektora klasy `.uve-edit-iframe`:
```javascript
const iframe = document.querySelector('.uve-edit-iframe');
```

### 3. Element nie znaleziony w iframe

**Problem:** `data-element-id` nie istnieje - elementy używają `data-uve-id`.

**Rozwiązanie:** Prawidłowy selektor:
```javascript
const element = iframe.contentDocument.querySelector(`[data-uve-id="${elementId}"]`);
```

### 4. Font-size resetował się do 16px

**Problem:** Wszystkie właściwości były aplikowane, włącznie z domyślnymi wartościami.

**Rozwiązanie:** Smart style application - pomijanie wartości domyślnych:
```javascript
const defaultValues = {
    'font-size': '16px',
    'font-weight': '400',
    'line-height': '',
    'letter-spacing': ''
};

// Tylko aplikuj jeśli NIE jest wartością domyślną
if (defaultValues[prop] !== undefined && value === defaultValues[prop]) {
    console.log('Skipping default value:', prop, '=', value);
    return;
}
```

## Kontrolki i ich formaty

### typography
```javascript
emitChange() {
    const value = {
        fontSize: '24px',
        fontWeight: '700',
        fontFamily: 'inherit',
        lineHeight: '1.5',
        letterSpacing: '0.02em',
        textTransform: 'uppercase',
        textDecoration: 'underline',
        textAlign: 'center',
    };
    this.$wire.updateControlValue('typography', value);
}
```

### color-picker
```javascript
// UWAGA: Wysyła string, nie obiekt!
emitChange() {
    let value = '#ff0000'; // lub 'transparent' lub 'rgba(255,0,0,0.5)'
    this.$wire.updateControlValue(this.property, value);
    // gdzie this.property = 'color' lub 'background-color'
}
```

### background
```javascript
emitChange() {
    const value = {
        backgroundColor: '#ffffff',
        backgroundImage: "url('...')",
        backgroundSize: 'cover',
        backgroundPosition: 'center center',
        backgroundRepeat: 'no-repeat',
        backgroundAttachment: 'scroll',
    };
    this.$wire.updateControlValue('background', value);
}
```

### border
```javascript
emitChange() {
    const value = {
        width: '1px',
        style: 'solid',
        color: '#000000',
        radius: '0.5rem',
    };
    this.$wire.updateControlValue('border', value);
}
```

### box-model
```javascript
emitChange() {
    const value = {
        margin: { top: '10px', right: '10px', bottom: '10px', left: '10px', linked: true },
        padding: { top: '20px', right: '20px', bottom: '20px', left: '20px', linked: true },
        borderRadius: { top: '8px', right: '8px', bottom: '8px', left: '8px', linked: true }
    };
    this.$wire.updateControlValue('box-model', value);
}
```

## CssValueFormatter - Obsługiwane typy

| controlType | Metoda formatowania |
|-------------|---------------------|
| `typography` | formatTypography() |
| `box-model` | formatBoxModel() |
| `gradient-editor` | formatGradient() |
| `layout-flex` | formatFlex() |
| `layout-grid` | formatGrid() |
| `effects` | formatEffects() |
| `transform` | formatTransform() |
| `transition` | formatTransition() |
| `border` | formatBorder() |
| `background` | formatBackground() |
| `position` | formatPosition() |
| `size` | formatSize() |
| `color` | **BRAK - trafia do formatGeneric()** |
| inne | formatGeneric() |

## Znane Problemy do Naprawy

### color-picker - formatGeneric() nie obsługuje stringów
```php
// CssValueFormatter::formatGeneric()
public function formatGeneric(mixed $value): array
{
    if (!is_array($value)) {
        return []; // <-- PROBLEM! Zwraca pusty array dla stringów!
    }
    // ...
}
```

**Rozwiązanie:** Dodać case 'color' do match statement:
```php
'color' => is_string($value) ? ['color' => $value] : [],
'background-color' => is_string($value) ? ['background-color' => $value] : [],
```

## Safe Properties vs Properties with Defaults

W `window.uveApplyStyles()`:

**Safe to Apply (zawsze):**
- `text-transform`
- `text-decoration`
- `text-align`
- `color`
- `background-color`
- `border-*`
- `margin-*`
- `padding-*`

**Properties with Defaults (tylko jeśli NIE domyślne):**
- `font-size` (default: 16px)
- `font-weight` (default: 400)
- `line-height` (default: '')
- `letter-spacing` (default: '')
