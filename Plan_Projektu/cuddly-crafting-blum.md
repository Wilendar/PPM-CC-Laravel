# PLAN: UVE CSS Export Bugs - Naprawienie Eksportu do PrestaShop

**Data:** 2026-01-16
**Status:** PLAN DO ZATWIERDZENIA
**Powiązane:** UVE Visual Editor, PrestaShop CSS Sync

---

## PODSUMOWANIE PROBLEMOW

| # | Typ | Problem | Priorytet | Root Cause |
|---|-----|---------|-----------|------------|
| 1 | BUG | pd-asset-list kolor BIALY zamiast CZARNEGO | CRITICAL | Hardcoded `color: #fff` w getLayoutFixCss() |
| 2 | BUG | pd-merits brak marginesow - tekst od krawedzi | CRITICAL | `padding: '2rem 0'` bez bokow |
| 3 | BUG | Image URL nie aktualizuje canvas | HIGH | Brak "Zastosuj" button + brak sync |

**Porownanie stron:**
- Oryginal: https://sklep.kayomoto.pl/buggy/4016-buggy-kayo-s200.html
- Test (zly): https://test.kayomoto.pl/buggy/4016-buggy-kayo-s200.html

---

## FAZA 1: BUG FIX - pd-asset-list kolor (CRITICAL)

**Problem:** Na test.kayomoto.pl tekst listy jest BIALY (`rgb(255,255,255)`) zamiast CZARNY (`rgb(0,0,0)`) jak na oryginale.

### Root Cause

**Plik:** `app/Services/VisualEditor/CssSyncOrchestrator.php` (linia ~701)

W metodzie `getLayoutFixCss()` jest hardcoded:

```css
.uve-content .pd-asset-list,
.product-description .pd-asset-list {
  color: #fff;  /* <-- HARDCODED BIALY! */
}
```

### 1.1 Naprawa - Usuniecie hardcoded koloru

**Plik:** `app/Services/VisualEditor/CssSyncOrchestrator.php`

**Zmiana:** Usunac `color: #fff;` z reguły `.pd-asset-list` w `getLayoutFixCss()`:

```php
// PRZED (linia ~701):
.uve-content .pd-asset-list,
.product-description .pd-asset-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 48px;
  padding: 96px 32px;
  margin: 0 auto;
  max-width: 1300px;
  list-style: none;
  color: #fff;  // <-- USUNAC!
}

// PO:
.uve-content .pd-asset-list,
.product-description .pd-asset-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 48px;
  padding: 96px 32px;
  margin: 0 auto;
  max-width: 1300px;
  list-style: none;
  /* color: inherit - dziedzicz z oryginalu */
}
```

### 1.2 Weryfikacja

1. Zapisz zmiany
2. Wykonaj sync CSS dla produktu 4016 na test.kayomoto.pl
3. Sprawdz Chrome DevTools:
   ```javascript
   document.querySelector('.pd-asset-list').style.color
   // Oczekiwane: "" (dziedziczone) lub "rgb(0, 0, 0)"
   ```

---

## FAZA 2: BUG FIX - pd-merits marginesy (CRITICAL)

**Problem:** Na test.kayomoto.pl sekcja "Ekonomia w terenowym wydaniu" (pd-merits) nie ma marginesow - tekst zaczyna sie od krawedzi ekranu.

### Root Cause

**Plik:** `app/Services/VisualEditor/PrestaShopCssDefinitions.php` (linia 158-163)

```php
'pd-merits' => [
    'display' => 'grid',
    'gridTemplateColumns' => 'repeat(auto-fit, minmax(280px, 1fr))',
    'gap' => '1.5rem',
    'padding' => '2rem 0',  // <-- TYLKO TOP/BOTTOM!
],
```

### 2.1 Naprawa - Dodanie horizontalnych marginesow

**Plik:** `app/Services/VisualEditor/PrestaShopCssDefinitions.php`

**Zmiana:**

```php
// PRZED:
'pd-merits' => [
    'display' => 'grid',
    'gridTemplateColumns' => 'repeat(auto-fit, minmax(280px, 1fr))',
    'gap' => '1.5rem',
    'padding' => '2rem 0',
],

// PO:
'pd-merits' => [
    'display' => 'grid',
    'gridTemplateColumns' => 'repeat(auto-fit, minmax(280px, 1fr))',
    'gap' => '1.5rem',
    'padding' => '2rem 32px',  // Dodane marginesy 32px na bokach
    'maxWidth' => '1300px',
    'margin' => '0 auto',      // Centrowanie
],
```

### 2.2 Dodanie CSS w getLayoutFixCss()

**Plik:** `app/Services/VisualEditor/CssSyncOrchestrator.php`

**Dodac po sekcji `.pd-asset-list`:**

```css
/* Merits list - SCOPED with margins */
.uve-content .pd-merits,
.product-description .pd-merits {
  max-width: 1300px;
  margin: 0 auto;
  padding-left: 32px;
  padding-right: 32px;
}
```

### 2.3 Weryfikacja

1. Sprawdz Chrome DevTools na test.kayomoto.pl:
   ```javascript
   const merits = document.querySelector('.pd-merits');
   getComputedStyle(merits).paddingLeft  // Oczekiwane: "32px"
   getComputedStyle(merits).maxWidth     // Oczekiwane: "1300px"
   ```

---

## FAZA 3: BUG FIX - Image URL nie aktualizuje canvas (HIGH)

**Problem:** Wklejajac URL obrazka w Property Panel:
1. Klikniecie "Zastosuj" - nic sie nie dzieje
2. Miniaturka sie zmienia ale canvas NIE

### Root Cause

1. **Brak przycisku "Zastosuj URL"** w `image-settings.blade.php`
2. **`setExternalUrl()` nie jest wywolywana** z direct URL input
3. **Brak synchronizacji Alpine -> Canvas**

### 3.1 Dodanie przycisku "Zastosuj" dla URL

**Plik:** `resources/views/livewire/products/visual-description/controls/image-settings.blade.php`

**Znalezc sekcje URL input (~linia 247) i dodac przycisk:**

```blade
{{-- URL Input sekcja --}}
<div class="uve-control-row">
    <label class="uve-control-label">URL obrazka</label>
    <div class="flex gap-2">
        <input
            type="text"
            x-model="imageUrl"
            class="uve-input flex-1"
            placeholder="https://..."
        />
        {{-- NOWY PRZYCISK "Zastosuj" --}}
        <button
            type="button"
            @click="applyExternalUrl()"
            class="uve-btn uve-btn-primary uve-btn-sm"
            :disabled="!imageUrl || imageUrl.trim() === ''"
        >
            Zastosuj
        </button>
    </div>
</div>
```

### 3.2 Dodanie metody Alpine `applyExternalUrl()`

**Plik:** `resources/js/app.js` - w komponencie `uveImageSettingsControl()`

**Dodac metode:**

```javascript
applyExternalUrl() {
    if (!this.imageUrl || this.imageUrl.trim() === '') return;

    // Wywolaj Livewire method
    $wire.applyExternalImageUrl(this.imageUrl);
},
```

### 3.3 Dodanie metody Livewire `applyExternalImageUrl()`

**Plik:** `app/Http/Livewire/Products/VisualDescription/Traits/UVE_MediaPicker.php`

**Dodac metode:**

```php
/**
 * Apply external URL to selected element
 * Called from image-settings control "Zastosuj" button
 */
public function applyExternalImageUrl(string $url): void
{
    if (empty($url) || empty($this->selectedElementId)) {
        return;
    }

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $this->dispatch('toast', message: 'Nieprawidlowy URL', type: 'error');
        return;
    }

    // Apply to element (reuse existing logic)
    $this->applyMediaToElement($this->selectedElementId, [
        'url' => $url,
        'type' => 'external',
    ]);

    // Dispatch update for Property Panel
    $this->dispatch('uve-image-url-updated', url: $url);

    // Sync to canvas
    $this->syncToIframe();
}
```

### 3.4 Weryfikacja

1. Otworz UVE
2. Kliknij na obrazek
3. W Property Panel wpisz URL
4. Kliknij "Zastosuj"
5. Canvas powinien pokazac nowy obrazek
6. Miniaturka w Property Panel tez powinna byc aktualna

---

## PLIKI DO MODYFIKACJI

| Faza | Plik | Zmiana |
|------|------|--------|
| 1.1 | `app/Services/VisualEditor/CssSyncOrchestrator.php` | Usunac `color: #fff` z pd-asset-list |
| 2.1 | `app/Services/VisualEditor/PrestaShopCssDefinitions.php` | Dodac padding/maxWidth do pd-merits |
| 2.2 | `app/Services/VisualEditor/CssSyncOrchestrator.php` | Dodac CSS dla pd-merits |
| 3.1 | `resources/views/.../controls/image-settings.blade.php` | Przycisk "Zastosuj" |
| 3.2 | `resources/js/app.js` | Alpine metoda `applyExternalUrl()` |
| 3.3 | `app/Http/Livewire/.../UVE_MediaPicker.php` | Metoda `applyExternalImageUrl()` |

---

## KOLEJNOSC IMPLEMENTACJI

1. **FAZA 1** (CRITICAL) - pd-asset-list kolor - 30min
2. **FAZA 2** (CRITICAL) - pd-merits marginesy - 30min
3. **FAZA 3** (HIGH) - Image URL sync - 1-2h
4. **Deploy + Test** - 30min

**TOTAL:** ~3h

---

## WERYFIKACJA KONCOWA

### Test 1: pd-asset-list kolor
```bash
# Na test.kayomoto.pl po sync:
# DevTools Console:
getComputedStyle(document.querySelector('.pd-asset-list')).color
# Oczekiwane: "rgb(0, 0, 0)" lub dziedziczone z parent
```

### Test 2: pd-merits marginesy
```bash
# Na test.kayomoto.pl po sync:
# DevTools Console:
const m = document.querySelector('.pd-merits');
console.log(getComputedStyle(m).paddingLeft);  // "32px"
console.log(getComputedStyle(m).maxWidth);     // "1300px"
```

### Test 3: Image URL w UVE
1. Otworz UVE dla produktu
2. Kliknij obrazek
3. Wpisz URL: `https://mm.mpptrade.pl/kayo/bg-kayo-s200/sklep/kayo-s200-transparent.webp`
4. Kliknij "Zastosuj"
5. Canvas powinien pokazac nowy obrazek

### Test 4: Porownanie stron
- Wykonaj sync CSS do test.kayomoto.pl
- Porownaj wizualnie z sklep.kayomoto.pl
- Wszystkie kolory i marginesy powinny sie zgadzac

---

## DEFINITION OF DONE

- [ ] pd-asset-list ma CZARNY kolor tekstu (nie bialy)
- [ ] pd-merits ma marginesy 32px na bokach
- [ ] pd-merits jest wycentrowane (max-width: 1300px)
- [ ] Przycisk "Zastosuj" dziala dla URL obrazka
- [ ] Canvas aktualizuje sie po wpisaniu URL
- [ ] Chrome DevTools: 0 console errors
- [ ] Deployment na produkcje

---

## UWAGI

- **NIE USUWAC** innych regul z `getLayoutFixCss()` - tylko `color: #fff` z pd-asset-list
- **SPRAWDZIC** czy inne bloki nie maja podobnego problemu
- **PRZETESTOWAC** na kilku produktach przed pelnym deployem
