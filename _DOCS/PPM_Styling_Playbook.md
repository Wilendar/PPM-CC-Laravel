# PPM Styling Playbook
Przewodnik operacyjny dla agumentów PPM-CC, bazujący na `_DOCS/PPM_Color_Style_Guide.md`. Cel: zapewnić jednolite UI (Blade + Livewire + Alpine) bez inline CSS i przypadkowych kolorów.

---

## 1. Paleta bazowa (CSS Custom Properties)
| Token | Wartość | Zastosowanie |
| --- | --- | --- |
| `--mpp-primary` / `--mpp-primary-dark` | `#e0ac7e` / `#d1975a` | CTA, aktywne linki, highlighty |
| `--ppm-primary` / `--ppm-primary-dark` | `#2563eb` / `#1d4ed8` | Akcje systemowe (np. sync) |
| `--ppm-secondary` / `--ppm-secondary-dark` | `#059669` / `#047857` | Sukcesy i status „online” |
| `--ppm-accent` / `--ppm-accent-dark` | `#dc2626` / `#b91c1c` | Błędy, destrukcyjne akcje |
| Backgrounds | `--bg-card`, `--bg-card-hover`, `--bg-nav` | Karty, panele, sidebar |
| Teksty | `--text-primary`, `--text-secondary`, `--text-muted`, `--text-disabled` | Hierarchia tekstu |

**Zasady:**
- Nowe kolory definiujemy jako zmienne; w klasach używamy `color: var(--mpp-primary);`.
- Gradienty i box-shadowy (CTA) muszą korzystać z brandowych wartości.
- Zabronione jest wstrzykiwanie hexów w Blade – nawet, jeśli odpowiadają brandowi.

---

## 2. Komponenty wspólne
### Przyciski
- `.btn-enterprise-primary` – główny CTA (gradient pomarańczowy). Nie kopiujemy gradientu w innych klasach.
- `.btn-enterprise-secondary` – neutralny przycisk (ciemne tło + border).
- `.btn-enterprise-sm` – kompaktowa wersja; nie stylujemy małych przycisków ręcznie.

**Dobre praktyki:**  
Używaj `@class` do dokładania stanów (`disabled`, `loading`). Dodatkowe efekty animacji implementuj przez pseudo-elementy `.btn-enterprise-primary::before` (już istnieje w CSS).

### Karty i panele
- `.enterprise-card` oraz `.category-form-right-column` zapewniają spójne paddingi i cienie.
- Jeśli potrzebne są warianty (ostrzeżenie, sukces), użyj klas `.enterprise-card-warning`, `.enterprise-card-success` i tylko w CSS zmieniaj akcenty.

### Badge i statusy
- Sync/queue badge korzystają z klas `.sync-status-*` – zrefaktoryzuj je na tokeny (zob. TODO).
- Nowe badge tworzymy jako wariant `.badge-enterprise` z modyfikatorem (`.badge-enterprise--warning`).

### Wiersze tabel i subrows
- `.variant-subrow` – wiersz wariantu produktu (zagnieżdżony pod produktem głównym).
  ```css
  .variant-subrow {
      background: #151a238c;
      border-left: 3px solid var(--ppm-primary);
  }
  .variant-subrow:hover {
      background: rgba(31, 41, 55, 0.7);
  }
  ```
- Lokalizacja: `resources/css/admin/components.css` (linia ~6828)
- **Zasada:** Stylowanie wierszy tabeli TYLKO przez klasy CSS, NIGDY przez inline Tailwind (`bg-gray-800/30` itp.)
- Hover state definiujemy w CSS, nie w Blade (`hover:bg-*` zabronione dla subrows)

---

## 3. Formularze i kontrolki
### Pola wejściowe
- Klasa bazowa: `.form-input-enterprise` (definiowana w `resources/css/admin/components.css`).
- Focus ring zawsze `box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), .35)`.

### Checkbox / radio
- Dodaj klasę `.checkbox-enterprise` z `accent-color: var(--mpp-primary)`.
- Zakaz `style="accent-color: ..."`. Jeśli potrzebny inny kolor (np. destructive), utwórz `.checkbox-danger`.

### Progress / paski stanu
- Używamy struktury:
  ```html
  <div class="progress-enterprise" data-progress="{{ $percent }}">
      <div class="progress-enterprise__fill"></div>
  </div>
  ```
- W CSS: `.progress-enterprise__fill { transform: scaleX(var(--progress, 1)); }`. Aktualny procent ustawiamy przy pomocy Alpine/Livewire (`$el.style.setProperty('--progress', value/100)`).

---

## 4. Layout i spacing
- Gridy i kontenery powinny bazować na `max-w-4xl`, `max-w-6xl`, `px-4 xl:px-8` – identycznie jak w ProductForm.
- Sidebar + content: Flex `lg:grid lg:grid-cols-[2fr_1fr]`.
- Minimalne paddingi kart: `p-4` (mobile), `p-6` (desktop). Nie używamy `style="margin: ..."` do centrowania – korzystamy z `mx-auto`, `space-y-*`.
- Wysokości obrazów/logotypów (np. login/welcome) kontrolujemy klasami `h-52 sm:h-48` + `object-contain`.

---

## 5. Tekst i typografia
- Czcionka globalna: `Inter`. Klasy `text-h1`, `text-h2`, `text-h3` mapują do nagłówków w `resources/css/app.css`.
- Kolory:
  - Primary copy: `text-dark-primary` (biały).
  - Secondary: `text-dark-secondary` (szary 200).
  - Muted: `text-dark-muted`.
- Cytaty, hasła brandowe („/// TWORZYMY PASJE ///”) otrzymują klasę `.brand-mantra` definiującą kolor i letter-spacing; nie przypisujemy `style="color:#e0ac7e"`.

---

## 6. Gradienty, cienie i ikony
- Jedyny dopuszczalny gradient CTA: `linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%)` – zapisany w `.btn-enterprise-primary`.
- Ikony w kółkach (Welcome page, karty) używają klasy `.icon-chip`:
  ```css
  .icon-chip {
      width: 3rem;
      height: 3rem;
      border-radius: 0.75rem;
      background: rgba(var(--mpp-primary-rgb), 0.18);
      color: var(--mpp-primary);
  }
  ```
- Zabronione: `style="background-color: rgba(224, 172, 126, 0.2)"` dla każdego kafla osobno.

---

## 7. Z-index i warstwy
- Warstwy UI muszą korzystać z klasyfikacji:
  | Nazwa | Z-index |
  | --- | --- |
  | `.layer-base` | `1` |
  | `.layer-panel` | `10` |
  | `.layer-modal` | `100` |
  | `.layer-overlay` | `200` |
  | `.layer-debug` (tylko w DEV) | `999` |
- Nie używamy arbitralnych Tailwindowych `z-[9999]` ani `style="z-index: ..."` – zamiast tego przypisujemy klasę `layer-*`.

---

## 8. Workflow dodawania stylu
1. **Sprawdź istniejące klasy** w `resources/css/admin/components.css`, `resources/css/products/category-form.css`.
2. **Dodaj** nowy moduł CSS tylko gdy potrzebuje >200 linii (zgodnie z AGENTS.md).
3. **Definiuj** kolory jako `var(--token)`; gradienty i cienie również opieraj o zmienne.
4. **Nie zapisuj** stylów w Blade – nawet tymczasowo. Jeśli potrzebujesz dynamicznych wartości (np. procent progresu), użyj:
   ```html
   <div x-data="{ value: @entangle('progress') }"
        x-effect="$refs.bar.style.setProperty('--progress', value / 100)">
        <span class="progress-enterprise__fill" x-ref="bar"></span>
   </div>
   ```
5. **Build & deploy:** `npm run build` → upload manifest (zgodnie z AGENTS.md) → `_TOOLS/full_console_test.cjs`.

---

## 9. Livewire Loading Overlays (KRYTYCZNE!)

**Problem:** `wire:loading` bez `wire:target` pokazuje loading overlay przy KAŻDYM Livewire request - w tym przy `$wire.$set()`, zmianach zaznaczenia, itp. Powoduje to migotanie/flickering UI.

**Rozwiązanie:** ZAWSZE używaj `wire:target` dla loading overlays:

```html
{{-- ZLE - loading przy KAZDYM uzyciu Livewire --}}
<div wire:loading.flex class="loading-overlay">
    <div class="spinner"></div>
</div>

{{-- DOBRZE - loading TYLKO dla dlugich operacji --}}
<div wire:loading.flex wire:target="save, syncCss, executeImport" class="loading-overlay">
    <div class="spinner"></div>
</div>
```

**Zasady:**
- `wire:target` MUSI zawierac liste metod ktore rzeczywiscie wymagaja loading (np. `save`, `import`, `sync`)
- NIE dodawaj do `wire:target` metod ktore sa szybkie (selekcja elementow, zmiana tabow, itp.)
- Dla szybkich interakcji uzywaj `$wire.$set()` zamiast metod - eliminuje to pełny re-render

**Typowe metody wymagajace loading:**
- `save`, `saveAll`, `saveDraft`
- `syncCss`, `syncToPrestaShop`
- `executeImport`, `importFromPrestaShop`
- `compileAllBlocks`, `generateHtml`
- `bulkOperation`, `deleteAll`

---

## 10. Checklist przed merge/deploy
- [ ] Zero `style="..."` w nowych/zmienionych plikach Blade.
- [ ] Wszystkie kolory odnoszą się do tokenów.
- [ ] Przyciski używają `.btn-enterprise-*`.
- [ ] Checkboxy, radio, progresy używają klas zdefiniowanych w CSS.
- [ ] Layout skalowalny (brak sztywnych pikselowych szerokości w atrybutach).
- [ ] Dodano opis zmian w `_DOCS/TODOs` jeśli styl wymaga refaktoru innych modułów.

---

**Ostatnia aktualizacja:** 2025-12-23
**Autor:** Codex (na bazie PPM_Color_Style_Guide.md)
