# UVE Property Panel Rules (MANDATORY)

## KRYTYCZNA ZASADA 1: BIDIRECTIONAL CSS SYNC

**Kontrolki Property Panel MUSZĄ być zsynchronizowane bidirektionalnie z CSS PrestaShop:**

```
[PrestaShop CSS] <---> [Property Panel Controls] <---> [UVE Rendered HTML]
```

### Przy ZAŁADOWANIU opisu:
1. Parser odczytuje HTML + CSS z PrestaShop
2. Kontrolki Property Panel MUSZĄ pokazywać AKTUALNE wartości z CSS
3. Jeśli element ma `font-size: 24px` w CSS → kontrolka Typografia pokazuje "24px"
4. Jeśli element ma `background-image: url(...)` → kontrolka Background pokazuje ten URL

### Przy ZAPISANIU:
1. Zmiany w kontrolkach → generowanie CSS rules
2. CSS sync do PrestaShop via FTP
3. HTML pozostaje bez inline styles (CSS-First Architecture)

## KRYTYCZNA ZASADA 2: KONTROLKI MUSZĄ ODPOWIADAĆ ELEMENTOM CSS

**Każda kontrolka Property Panel MUSI:**
1. Odpowiadać konkretnemu selektorowi CSS w bloku
2. Być logicznie powiązana z typem elementu (NIE Typografia na obrazku!)
3. Umożliwiać edycję WSZYSTKICH edytowalnych właściwości danego elementu

### ZABRONIONE:
- Kontrolka `typography` na elementach bez tekstu (obrazy, kontenery)
- Brak kontrolek dla elementów, które są edytowalne w CSS
- Kontrolki bez odpowiadającego selektora CSS

### WYMAGANE:
- `image-settings` dla KAŻDEGO `<img>` lub `background-image`
- `list-settings` dla KAŻDEGO kontenera list (`.pd-merits`, `.pd-asset-list`)
- `typography` TYLKO dla elementów tekstowych (`h1-h6`, `p`, `span`, `a`)
- `background` z obsługą `background-image` dla parallax/cover

## ZASADA 3: MAPOWANIE SELEKTOR → KONTROLKI

| Typ elementu | Wymagane kontrolki |
|--------------|-------------------|
| Tekst (h1-h6, p) | typography, color-picker |
| Obrazek (img) | image-settings, border, effects |
| Background image | background (z image-picker!), parallax-settings |
| Lista | list-settings, layout-grid/flex |
| List item | box-model, background, border |
| Button | button-settings, typography, color-picker |
| Container | box-model, background, layout-flex/grid |

## ZASADA 4: INICJALIZACJA KONTROLEK

Przy załadowaniu bloku, kontrolki MUSZĄ być zainicjalizowane z:

```php
// CssRuleGenerator lub PropertyPanelService
public function getControlValues(string $blockId, string $selector): array
{
    $cssRules = $this->getCssRulesForElement($blockId, $selector);

    return [
        'font-size' => $cssRules['font-size'] ?? null,
        'color' => $cssRules['color'] ?? null,
        'background-image' => $cssRules['background-image'] ?? null,
        // ... wszystkie edytowalne właściwości
    ];
}
```

## PRZYKŁAD PRAWIDŁOWEGO MAPOWANIA

### PdParallaxBlock:
```php
public array $propertyPanelControls = [
    // ROOT: kontener parallax - NIE typografia!
    'root' => ['box-model', 'size'],

    // Background image - WYMAGANE image-picker!
    '.pd-pseudo-parallax' => ['background-image', 'parallax-settings'],

    // Overlay - tło, przezroczystość
    '.pd-pseudo-parallax__overlay' => ['background', 'effects'],

    // Teksty - TUTAJ typografia
    '.pd-pseudo-parallax__title' => ['typography', 'color-picker'],
    '.pd-pseudo-parallax__subtitle' => ['typography', 'color-picker'],

    // Button
    '.pd-pseudo-parallax__btn' => ['button-settings', 'typography'],
];
```

### PdMeritsBlock (lista):
```php
public array $propertyPanelControls = [
    'root' => ['list-settings', 'layout-grid', 'box-model'],

    // KAŻDY list item osobno!
    '.pd-merit' => ['box-model', 'background', 'border'],
    '.pd-merit__icon' => ['color-picker', 'size'],
    '.pd-merit__title' => ['typography', 'color-picker'],
    '.pd-merit__text' => ['typography', 'color-picker'],
];
```

## KRYTYCZNA ZASADA 5: SRCSET HANDLING (FIX #8)

**Problem:** Przeglądarka preferuje `srcset` nad `src`! Jeśli zaktualizujesz tylko `src`, obrazek się NIE zmieni.

### Przy zmianie obrazka MUSISZ zaktualizować:
1. `src` - główny URL obrazka
2. `srcset` - jeśli istnieje, podmień na nowy URL
3. `<source>` w `<picture>` - wszystkie elementy srcset

```php
// UVE_MediaPicker.php - updateImageInHtml()
if ($tagName === 'img') {
    $element->setAttribute('src', $newSrc);

    // KRYTYCZNE: Aktualizuj też srcset!
    if ($element->hasAttribute('srcset')) {
        $element->setAttribute('srcset', $newSrc);
    }

    // Jeśli img jest w <picture>, zaktualizuj też <source>
    $parent = $element->parentNode;
    if ($parent && strtolower($parent->nodeName) === 'picture') {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeName === 'source') {
                $child->setAttribute('srcset', $newSrc);
            }
        }
    }
}
```

### ZABRONIONE:
- ❌ Aktualizacja tylko `src` bez `srcset`
- ❌ Usuwanie `srcset` (niszczy responsive images)

### WYMAGANE:
- ✅ Podmiana URL w `src`, `srcset` i `<source srcset>`

## KRYTYCZNA ZASADA 6: ELEMENT INDEXING - GLOBAL INDEX (FIX #10)

**UWAGA:** System używa GLOBAL indexing (jeden licznik dla wszystkich typów)!

### Format ID: `block-{blockNum}-{type}-{globalIndex}`

```
block-6-heading-0 → pierwszy element w bloku (heading)
block-6-text-1    → drugi element w bloku (paragraph)
block-6-image-2   → trzeci element w bloku (img)
block-6-button-3  → czwarty element w bloku (button)
```

**Indeks jest GLOBALNY** - wszystkie typy elementów dzielą jeden licznik!

### Implementacja w markChildElements() i findElementInContext():
```php
$elementIndex = 0;  // JEDEN licznik dla wszystkich typów!

foreach ($headings as $heading) {
    $heading->setAttribute('data-uve-id', "{$blockId}-heading-{$elementIndex}");
    $elementIndex++;  // 0, 1, 2...
}
foreach ($paragraphs as $p) {
    $p->setAttribute('data-uve-id', "{$blockId}-text-{$elementIndex}");
    $elementIndex++;  // kontynuuje: 3, 4, 5...
}
foreach ($images as $img) {
    $img->setAttribute('data-uve-id', "{$blockId}-image-{$elementIndex}");
    $elementIndex++;  // kontynuuje: 6, 7, 8...
}
```

### WYMAGANE:
- ✅ Ten sam globalny licznik w `markChildElements()` (UVE_Preview) i `findElementInContext()` (UVE_MediaPicker)

## KRYTYCZNA ZASADA 7: XPATH UNIFICATION (FIX #11)

**Problem:** Różne XPath queries w `injectEditableMarkers()` i `findElementByStructuralMatching()` powodują że bloki są różnie liczone!

### OBOWIĄZKOWY XPath dla visual blocks:
```php
// MUSI być IDENTYCZNY w obu miejscach!
$blockXPath = '//*[contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-cover")]';
```

### Pliki do synchronizacji:
- `UVE_Preview.php::injectEditableMarkers()` - przypisuje data-uve-id
- `UVE_MediaPicker.php::findElementByStructuralMatching()` - szuka elementów

### ZABRONIONE:
- ❌ `'//*[contains(@class, "pd-") and not(contains(@class, "__"))]'` - za szeroki!
- ❌ Różne XPath w różnych plikach

## KRYTYCZNA ZASADA 8: ALPINE WIRE:IGNORE SYNC (FIX #12)

**Problem:** Kontrolki Alpine z `wire:ignore.self` NIE są reinicjalizowane przy Livewire update!

### Symptom:
Kliknięcie na inny obrazek → Property Panel pokazuje stary URL (nie aktualizuje się)

### Przyczyna:
`image-settings.blade.php` ma `wire:ignore.self` → Alpine zachowuje stary state

### Rozwiązanie:
`onElementSelectedForPanel()` MUSI dispatch'ować event z nowym imageUrl:

```php
// UVE_PropertyPanel.php - onElementSelectedForPanel()
$imageUrl = $this->elementStyles['imageUrl'] ?? $this->elementStyles['src'] ?? null;
if ($imageUrl) {
    $this->dispatch('uve-image-url-updated', url: $imageUrl);
}
```

### Alpine listener (app.js):
```javascript
init() {
    Livewire.on('uve-image-url-updated', (data) => {
        const newUrl = data?.url || data[0]?.url;
        if (newUrl !== undefined) {
            this.imageUrl = newUrl;
        }
    });
}
```

### WYMAGANE dla każdej kontrolki z wire:ignore.self:
- ✅ Dispatch Livewire event przy zmianie elementu
- ✅ Alpine listener w `init()` aktualizujący state

## CHECKLIST PRZED DEPLOYMENT

- [ ] Każdy selektor ma logiczne kontrolki
- [ ] Typografia TYLKO na elementach tekstowych
- [ ] Background-image dla parallax/cover
- [ ] List-settings dla kontenerów list
- [ ] Kontrolki dla KAŻDEGO list item
- [ ] Inicjalizacja wartości z CSS PrestaShop
- [ ] **srcset aktualizowany razem z src** (FIX #8)
- [ ] **GLOBAL indexing** w markChildElements i findElementInContext (FIX #10)
- [ ] **XPath IDENTYCZNY** w injectEditableMarkers i findElementByStructuralMatching (FIX #11)
- [ ] **Dispatch imageUrl** dla kontrolek z wire:ignore.self (FIX #12)
