# Przewodnik: Tworzenie Bloków Szablonów PrestaShop

## Spis treści
1. [Wprowadzenie](#wprowadzenie)
2. [Struktura HTML bloków](#struktura-html-bloków)
3. [Klasy CSS PrestaShop](#klasy-css-prestashop)
4. [Tworzenie nowych bloków](#tworzenie-nowych-bloków)
5. [Testowanie bloków](#testowanie-bloków)

---

## Wprowadzenie

Bloki szablonów PrestaShop używają specjalnych klas CSS (`pd-*`) które są stylowane przez `custom.css` sklepu. W PPM Visual Editor bloki te są renderowane w trybie **passthrough** - HTML jest wyświetlany 1:1 bez modyfikacji.

### Architektura CSS

```
PrestaShop custom.css → VBB getScopedBaseCss() → VE preview CSS
                                    ↓
                         Wszystkie muszą być ZGODNE 1:1
```

---

## Struktura HTML bloków

### pd-intro__heading (Nagłówek produktu)

**Struktura:**
```html
<div class="pd-intro__heading pd-model">
    <span class="pd-model__type">Buggy</span>
    <span class="pd-model__name">KAYO S200</span>
</div>
```

**CSS Grid Layout:**
- Row 1: Nazwa modelu (full width)
- Row 2: Pomarańczowy pasek (::before, 160x12px) + Typ modelu

**Ważne:** Kolejność w HTML: Type przed Name, ale CSS grid-row ustawia Name w Row 1!

```css
.pd-intro__heading {
    display: grid;
    grid-template-columns: 160px auto;
    grid-template-rows: auto auto;
    gap: 0 16px;
}

.pd-intro__heading::before {
    content: "";
    width: 160px;
    height: 12px;
    background-color: #eb5e20;
    grid-column: 1 / 2;
    grid-row: 2 / 3;
}

.pd-model__name {
    grid-column: 1 / -1;
    grid-row: 1 / 2;
}

.pd-model__type {
    grid-column: 2 / -1;
    grid-row: 2 / 3;
}
```

---

### pd-asset-list (Lista parametrów)

**Struktura (ul/li):**
```html
<div class="bg-brand">
    <ul class="pd-asset-list">
        <li>pojemność silnika<b>200 cm<sup>3</sup></b></li>
        <li>automatyczna skrzynia biegów<b>R-N-F</b></li>
        <li>z przodu pojazdu<b>lampy LED</b></li>
        <li>pojemności zbiornika<b>15 litrów</b></li>
        <li>zalecany wiek<b>od 14 lat</b></li>
    </ul>
</div>
```

**CSS Flex Layout:**
```css
.pd-asset-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0 3rem;
    padding: 1.5rem 1rem;
    margin: 0;
    list-style: none;
}

.pd-asset-list > li {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    font-size: 1.125rem;
    min-width: 100px;
}

/* CRITICAL: order: -1 makes bold value appear FIRST */
.pd-asset-list > li b,
.pd-asset-list > li strong {
    display: block;
    font-size: 2.25rem;
    font-weight: 700;
    order: -1;
}
```

**Ważne na bg-brand:** Tekst musi być CZARNY (#000), nie biały!

```css
.bg-brand .pd-asset-list,
.bg-brand .pd-asset-list > li,
.bg-brand .pd-asset-list > li b {
    color: #000 !important;
}
```

---

### pd-cover (Zdjęcie główne)

**Struktura:**
```html
<div class="pd-cover">
    <picture class="pd-cover__picture">
        <img src="product-image.png" alt="Product">
    </picture>
</div>
```

**CSS:**
```css
.pd-cover {
    grid-column: 1 / -1;
    display: flex;
    justify-content: center;
}

.pd-cover__picture {
    background: linear-gradient(#f6f6f6 70%, #ef8248 70%);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    width: 100%;
    max-width: 960px;
}
```

---

### bg-brand (Tło pomarańczowe)

**Użycie:**
```html
<div class="bg-brand">
    <!-- Content with orange background -->
</div>
```

**CSS:**
```css
.bg-brand {
    background-color: #ef8248 !important;
}
```

---

## Klasy CSS PrestaShop

### Prefiksy pd-*

| Klasa | Opis |
|-------|------|
| `pd-intro` | Sekcja wprowadzająca |
| `pd-intro__heading` | Nagłówek z grid layout |
| `pd-model` | Alias dla heading |
| `pd-model__name` | Nazwa produktu |
| `pd-model__type` | Typ produktu |
| `pd-cover` | Zdjęcie główne |
| `pd-asset-list` | Lista parametrów |
| `pd-merits` | Sekcja zalet |
| `pd-specification` | Tabela specyfikacji |
| `pd-features` | Lista cech |
| `pd-slider` | Karuzela zdjęć |
| `pd-parallax` | Efekt parallax |

### Klasy tła

| Klasa | Kolor |
|-------|-------|
| `bg-brand` | #ef8248 (pomarańczowy) |
| `bg-neutral-accent` | #f6f6f6 (szary) |
| `bg-dark` | #1a1a1a (ciemny) |

---

## Tworzenie nowych bloków

### Krok 1: Skopiuj HTML z PrestaShop

1. Otwórz stronę produktu w PrestaShop
2. DevTools → Inspect element
3. Skopiuj HTML sekcji którą chcesz jako blok

### Krok 2: Zidentyfikuj klasy pd-*

Generator automatycznie wykrywa klasy PrestaShop i ustawia **passthrough mode**.

### Krok 3: Użyj Block Generator

W VE → Bloki → "Nowy blok dedykowany" lub kliknij + na prestashop-section

Generator:
1. Wykryje klasy pd-*
2. Ustawi passthrough mode
3. Stworzy schema z polem `html`
4. Zapisze jako dedykowany blok sklepu

### Krok 4: Edytuj w VBB

Otwórz blok w Visual Block Builder:
- Zmień content
- Dostosuj parametry
- Podgląd 1:1 z PrestaShop

---

## Testowanie bloków

### Checklist przed publikacją

- [ ] Blok renderuje się identycznie w VBB i VE
- [ ] Blok renderuje się identycznie na PrestaShop
- [ ] Kolory tekstu poprawne (czarny na pomarańczowym tle)
- [ ] pd-intro__heading ma pomarańczowy pasek po lewej
- [ ] pd-asset-list ma wartości nad etykietami (order: -1)
- [ ] Responsive design działa

### Porównanie wizualne

1. VBB canvas screenshot
2. VE preview screenshot
3. PrestaShop frontend screenshot
4. Wszystkie 3 muszą być **identyczne**

---

## Pliki źródłowe CSS

| Plik | Opis |
|------|------|
| `BlockBuilderCanvas.php::getScopedBaseCss()` | CSS dla VBB canvas |
| `PrestaShopCssDefinitions.php` | Definicje stylów dla CssClassStyleResolver |
| `resources/css/admin/components.css` | CSS dla VE preview (.ve-canvas) |

**CRITICAL:** Wszystkie 3 pliki muszą mieć ZGODNE definicje!

---

## FAQ

### Dlaczego tekst na bg-brand jest biały zamiast czarnego?

Sprawdź czy CSS ma regułę:
```css
.bg-brand .pd-asset-list { color: #000 !important; }
```

### Dlaczego pd-intro__heading nie ma pomarańczowego paska?

Sprawdź czy CSS ma ::before pseudo-element z:
- `content: ""`
- `background-color: #eb5e20`
- `grid-column: 1 / 2`
- `grid-row: 2 / 3`

### Dlaczego wartości są pod etykietami zamiast nad?

Sprawdź czy CSS ma:
```css
.pd-asset-list > li b { order: -1; }
```

---

## Changelog

| Data | Zmiana |
|------|--------|
| 2025-12-19 | Utworzenie dokumentacji |
| 2025-12-19 | Fix pd-intro__heading grid + ::before |
| 2025-12-19 | Fix pd-asset-list order:-1 + black text |
| 2025-12-19 | VBB canvas full width (usunięcie 720px constraint) |
