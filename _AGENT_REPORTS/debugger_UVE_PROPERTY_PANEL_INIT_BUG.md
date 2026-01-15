# AGENT REPORT: debugger - UVE Property Panel Initialization Bug

**Data**: 2026-01-14 14:30
**Zadanie**: Diagnoza problemu inicjalizacji wartosci Property Panel oraz selekcji pd-pseudo-parallax

---

## SYMPTOMY

1. **Property Panel pokazuje puste/domyslne wartosci** zamiast aktualnych wartosci z CSS PrestaShop
   - Zakladka "Obraz" w sekcji "Tlo" pokazuje placeholder "https://..." zamiast rzeczywistego URL background-image
   - Kontrolki maja wyzerowane wartosci mimo ze element ma style CSS w PrestaShop

2. **Elementy pd-pseudo-parallax nie sa selectable na canvas**
   - Klikniecie na sekcje parallax nie pozwala na jej edycje
   - Canvas nie rejestruje selekcji dla tych elementow

---

## ROOT CAUSE #1: pd-pseudo-parallax NIE JEST w liscie wykrywalnych blokow

**Lokalizacja**: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` linia 714

**Problem**: XPath query w `injectEditableMarkers()` NIE zawiera klasy `pd-pseudo-parallax`:

```php
$blocks = $xpath->query('//*[(contains(@class, "pd-block") or contains(@class, "pd-intro") or contains(@class, "pd-merits") or contains(@class, "pd-specification") or contains(@class, "pd-features") or contains(@class, "pd-cover") or contains(@class, "pd-slider")) and not(contains(@class, "__"))]');
```

**Brakujace klasy**:
- `pd-pseudo-parallax`
- `pd-parallax`
- `pd-more-links`
- `pd-footer`
- `pd-header`

**Efekt**: Elementy z tymi klasami NIE otrzymuja atrybutu `data-uve-id`, wiec JavaScript w iframe NIE moze ich wykryc ani wyslac do Livewire przy kliknieciu.

---

## ROOT CAUSE #2: CSS computed styles z PrestaShop NIE sa poprawnie parsowane do kontrolek

**Lokalizacja**: JavaScript w iframe (`getEditModeScript()` w UVE_Preview.php) oraz flow w Livewire.

### Problem A: `getElementStyles()` w JavaScript NIE ekstrahuje URL z background-image

Funkcja JavaScript `getElementStyles()` (linie 888-935) pobiera wartosci CSS:

```javascript
const visualProps = [
    'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
    ...
];
```

**ALE**: `computed.backgroundImage` zwraca pelny CSS value typu:
```
url("https://example.com/image.jpg")
```

A kontrolka `background` w Property Panel oczekuje tylko URL:
```
https://example.com/image.jpg
```

### Problem B: Brak parsowania CSS url() do czystego URL

W `CssValueFormatter.php` NIE ma logiki do ekstrakcji URL z CSS background-image.

Kontrolka `background` (AdvancedControlDefinitions.php) definiuje:
```php
'defaultValue' => [
    'type' => 'color', 'color' => '', 'image' => '',  // <-- 'image' oczekuje czystego URL
    ...
],
```

### Problem C: Flow styles -> Panel nie transformuje wartosci

W `UVE_PropertyPanel.php::onElementSelectedForPanel()` style z canvasStyles sa kopiowane bezposrednio:
```php
if (!empty($canvasStyles)) {
    $this->elementStyles = $canvasStyles;
    $this->elementStylesCache[$elementId] = $canvasStyles;
}
```

Ale `canvasStyles` zawiera `backgroundImage: url("...")` zamiast czystego URL.

---

## WYMAGANE POPRAWKI

### FIX #1: Dodanie brakujacych klas do XPath query

**Plik**: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php`

**Linia 714** - dodac:
- `pd-pseudo-parallax`
- `pd-parallax`
- `pd-more-links`
- `pd-footer`
- `pd-header`

### FIX #2: Parsowanie CSS url() w JavaScript getElementStyles()

**Plik**: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` (getEditModeScript)

Po linii 935, dodac parsowanie URL:

```javascript
// Parse background-image URL
if (styles.backgroundImage && styles.backgroundImage.startsWith('url(')) {
    styles.backgroundImage = styles.backgroundImage
        .replace(/^url\(['"]?/, '')
        .replace(/['"]?\)$/, '');
}
```

### FIX #3: Parsowanie CSS url() w PHP (backup)

**Plik**: `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php`

W `onElementSelectedForPanel()`, dodac transformacje:

```php
// Parse CSS url() values to clean URLs
if (isset($canvasStyles['backgroundImage']) && str_starts_with($canvasStyles['backgroundImage'], 'url(')) {
    $canvasStyles['backgroundImage'] = preg_replace('/^url\([\'"]?|[\'"]?\)$/', '', $canvasStyles['backgroundImage']);
}
```

---

## ZASADA UVE NARUSZONA

```
RULE 1 (LOAD): When loading description, controls MUST show current values FROM PrestaShop CSS
```

System NIE spelnia tej zasady poniewaz:
1. Nie wszystkie elementy sa oznaczane jako edytowalne (brak pd-pseudo-parallax w XPath)
2. Wartosci CSS nie sa prawidlowo transformowane z formatu CSS (`url("...")`) do formatu kontrolek (czysty URL)

---

## PRIORYTET

**WYSOKI** - Uniemozliwia edycje kluczowych elementow opisu produktu (sekcje parallax) oraz blokuje poprawna inicjalizacje wartosci kontrolek.

---

## PLIKI DO EDYCJI

| Plik | Zmiana |
|------|--------|
| `app/Http/Livewire/Products/VisualDescription/Traits/UVE_Preview.php` | Rozszerzyc XPath query + parsowanie URL w JS |
| `app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php` | Backup parsowanie URL w PHP |

---

## NASTEPNE KROKI

1. **Potwierdzenie diagnozy z uzytkownikiem** - czy opisane symptomy odpowiadaja obserwowanemu problemowi?
2. Po potwierdzeniu: implementacja FIX #1 (XPath) jako pierwsze
3. Nastepnie: FIX #2 i #3 (parsowanie URL)
4. Deploy i weryfikacja z Claude in Chrome
5. Dokumentacja w `_ISSUES_FIXES/` jesli debugowanie trwalo >2h
