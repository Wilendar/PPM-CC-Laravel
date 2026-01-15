# RAPORT ARCHITEKTONICZNY: Property Panel Controls Analysis

**Data:** 2026-01-14
**Agent:** Opus 4.5 (analiza bezposrednia)
**Temat:** Analiza zgodnosci kontrolek Property Panel z zasadami UVE CSS-First

---

## KRYTYCZNE ZASADY UVE (MANDATORY)

### ZASADA 1: Bidirectional CSS Sync
```
[PrestaShop CSS] <---> [Property Panel Controls] <---> [UVE Rendered HTML]
```
- Przy ZALADOWANIU: kontrolki MUSZA pokazywac aktualne wartosci z CSS
- Przy ZAPISANIU: zmiany w kontrolkach → generowanie CSS rules → sync do PrestaShop

### ZASADA 2: Kontrolki MUSZA odpowiadac elementom CSS
- Kazda kontrolka = konkretny selektor CSS
- Typografia TYLKO na elementach tekstowych
- Background-image dla parallax/cover
- List-settings dla kontenerow list

---

## WYKRYTE PROBLEMY

### PROBLEM 1: PrestashopSectionBlock - BRAK DYNAMICZNYCH KONTROLEK

**Lokalizacja:** `app/Services/VisualEditor/Blocks/PrestaShop/PrestashopSectionBlock.php`

**Opis:** Ten blok obsluguje ROZNE typy sekcji (cover, parallax, merits, etc.) ale ma TYLKO statyczne kontrolki:

```php
public array $propertyPanelControls = [
    'root' => ['box-model', 'background', 'border'],
];
```

**Problem:**
- Blok Cover NIE MA kontrolki do edycji `background-image`!
- Blok Parallax NIE MA kontrolek dla overlay, tytulu, podtytulu
- Blok Merits NIE MA kontrolek dla pojedynczych merit items

**Rozwiazanie:** Dynamiczne mapowanie kontrolek na podstawie `section_type`:

```php
public function getPropertyPanelControlsForType(string $sectionType): array
{
    $controls = ['root' => ['box-model']];

    switch ($sectionType) {
        case 'cover':
        case 'parallax':
            $controls['.pd-pseudo-parallax'] = ['background', 'parallax-settings', 'size'];
            $controls['.pd-pseudo-parallax__overlay'] = ['background', 'effects'];
            $controls['.pd-pseudo-parallax__title'] = ['typography', 'color-picker'];
            $controls['.pd-pseudo-parallax__subtitle'] = ['typography', 'color-picker'];
            break;

        case 'merits':
            $controls['.pd-merits'] = ['list-settings', 'layout-grid'];
            $controls['.pd-merit'] = ['box-model', 'background', 'border'];
            $controls['.pd-merit .pd-icon'] = ['color-picker', 'size'];
            $controls['.pd-merit h4'] = ['typography', 'color-picker'];
            $controls['.pd-merit p'] = ['typography', 'color-picker'];
            break;

        // ... etc
    }

    return $controls;
}
```

---

### PROBLEM 2: Bloki uzywaja INLINE STYLES zamiast CSS Classes

**Lokalizacja:**
- `app/Services/VisualEditor/Blocks/Media/ParallaxImageBlock.php`
- `app/Services/VisualEditor/Blocks/Content/MeritListBlock.php`

**Przyklad z ParallaxImageBlock (linie 59-66):**
```php
$containerStyle = $this->inlineStyles([
    'height' => $settings['height'],
    'background-image' => "url('{$image}')",
    'background-position' => $settings['background_position'],
    'background-attachment' => 'fixed',
    'background-size' => 'cover',
]);
```

**Przyklad z MeritListBlock (linia 117):**
```php
style="color: {$settings['icon_color']}; font-size: {$settings['icon_size']};"
```

**Problem:** TO NARUSZA ZASADE CSS-FIRST!
- Inline styles NIE SA edytowalne przez Property Panel CSS rules
- Inline styles NIE synchronizuja sie z PrestaShop CSS
- Zmiany w kontrolkach NIE wplywaja na wyglad (bo inline styles maja wyzsza specyficznosc)

**Rozwiazanie:**
1. Uzyc CSS classes zamiast inline styles
2. Generowac CSS rules przez CssRuleGenerator
3. Kontrolki edytuja CSS rules, nie inline styles

---

### PROBLEM 3: Nielogiczne kontrolki na blokach

| Blok | Problem | Rozwiazanie |
|------|---------|-------------|
| Cover (PrestashopSectionBlock) | Brak `background-image` picker | Dodac `background` kontrolke z image picker |
| ParallaxImageBlock | Ma `typography` na root (NIE MA tekstu na root!) | Usunac typography z root, dodac do `.pd-parallax__title` |
| PrestashopSectionBlock | Tylko `root` kontrolki | Dynamiczne kontrolki per section_type |
| PdMeritsBlock | Brak kontrolek dla KAZDEGO item osobno | Dodac `item-editor` lub `repeater-controls` |

---

### PROBLEM 4: Brak inicjalizacji kontrolek z CSS

**Opis:** Kontrolki Property Panel NIE SA inicjalizowane z aktualnych wartosci CSS!

Przy zaladowaniu bloku, kontrolki powinny pokazywac:
- font-size z CSS → kontrolka Typografia
- background-image z CSS → kontrolka Background
- color z CSS → kontrolka Color Picker

**Obecny stan:** Kontrolki pokazuja defaultowe wartosci, NIE wartosci z CSS!

**Rozwiazanie:** Potrzebny PropertyPanelInitializer service:

```php
class PropertyPanelInitializer
{
    public function initializeControls(string $blockId, array $cssRules): array
    {
        $values = [];
        foreach ($cssRules as $selector => $properties) {
            foreach ($properties as $property => $value) {
                $values[$selector][$property] = $value;
            }
        }
        return $values;
    }
}
```

---

## DOSTEPNE KONTROLKI (z ControlDefinitions i AdvancedControlDefinitions)

| Kontrolka | CSS Properties | Uzycie |
|-----------|----------------|--------|
| `typography` | font-size, font-weight, font-family, line-height, text-align | TYLKO na elementach tekstowych (h1-h6, p, span) |
| `color-picker` | color, background-color, border-color | Na dowolnych elementach |
| `background` | background-image, background-color, background-position, background-size | Na kontenerach, parallax, cover |
| `box-model` | margin, padding, border-radius | Na dowolnych elementach |
| `layout-flex` | display, flex-direction, justify-content, align-items, gap | Na kontenerach list |
| `layout-grid` | grid-template-columns, gap | Na gridach |
| `border` | border-width, border-style, border-color, border-radius | Na dowolnych |
| `effects` | box-shadow, text-shadow, opacity | Na dowolnych |
| `size` | width, height, min-width, max-width | Na kontenerach |
| `parallax-settings` | min-height, background-attachment, backgroundImage | Na sekcjach parallax |
| `media-picker` | background-image | Na elementach z obrazkiem tla |

---

## PRAWIDLOWE MAPOWANIE KONTROLEK

### PrestashopSectionBlock (section_type='cover')
```php
public array $propertyPanelControls = [
    'root' => ['box-model', 'size'],  // NIE typography!
    '.pd-cover, .pd-pseudo-parallax' => ['background', 'parallax-settings'],  // Z IMAGE PICKER!
    '.pd-cover__overlay' => ['background', 'effects'],
    '.pd-cover__title' => ['typography', 'color-picker'],  // TU typografia!
    '.pd-cover__subtitle' => ['typography', 'color-picker'],
];
```

### PdMeritsBlock
```php
public array $propertyPanelControls = [
    'root' => ['list-settings', 'layout-grid', 'box-model'],  // NIE typography!
    '.pd-merit' => ['box-model', 'background', 'border'],  // Dla KAZDEGO item
    '.pd-merit .pd-icon' => ['color-picker', 'size'],
    '.pd-merit h4' => ['typography', 'color-picker'],  // TU typografia!
    '.pd-merit p' => ['typography', 'color-picker'],
];
```

### ParallaxImageBlock (WYMAGA REFAKTORU)
```php
// USUŃ INLINE STYLES Z render()!
// Użyj CSS classes i CssRuleGenerator

public array $propertyPanelControls = [
    'root' => ['size'],  // Tylko rozmiar, NIE typography!
    '.pd-parallax' => ['background', 'parallax-settings'],  // Z IMAGE PICKER!
    '.pd-parallax__overlay' => ['background', 'effects'],
    '.pd-parallax__title' => ['typography', 'color-picker'],
    '.pd-parallax__subtitle' => ['typography', 'color-picker'],
];
```

---

## PLAN NAPRAWY

### FAZA 1: Rules & Documentation (DONE)
- [x] Zapisac zasady Property Panel do `.claude/rules/uve/property-panel-rules.md`

### FAZA 2: Napraw PrestashopSectionBlock
- [ ] Dodac dynamiczne mapowanie kontrolek per section_type
- [ ] Zaimplementowac getPropertyPanelControlsForType()
- [ ] Przetestowac dla cover, parallax, merits

### FAZA 3: Usun inline styles z blokow
- [ ] ParallaxImageBlock - refaktor render() na CSS classes
- [ ] MeritListBlock - refaktor render() na CSS classes
- [ ] Inne bloki uzywajace $this->inlineStyles()

### FAZA 4: Inicjalizacja kontrolek z CSS
- [ ] Utworzyc PropertyPanelInitializer service
- [ ] Integracja z CssRuleGenerator
- [ ] Testowanie bidirectional sync

### FAZA 5: Weryfikacja i testy
- [ ] Chrome DevTools verification
- [ ] Test edycji parallax background-image
- [ ] Test edycji merit list items
- [ ] Test sync do PrestaShop

---

## PRIORYTET

**KRYTYCZNE:**
1. PrestashopSectionBlock - dynamiczne kontrolki (bo to glowny blok do importu z PS)
2. Usunac inline styles z ParallaxImageBlock (narusza CSS-First)

**WYSOKIE:**
3. Inicjalizacja kontrolek z CSS
4. MeritListBlock refaktor

---

## PLIKI DO MODYFIKACJI

1. `app/Services/VisualEditor/Blocks/PrestaShop/PrestashopSectionBlock.php`
2. `app/Services/VisualEditor/Blocks/Media/ParallaxImageBlock.php`
3. `app/Services/VisualEditor/Blocks/Content/MeritListBlock.php`
4. `app/Services/VisualEditor/PropertyPanel/PropertyPanelInitializer.php` (NOWY)
5. Wszystkie bloki z nielogicznymi kontrolkami
