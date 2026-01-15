# Raport: UVE – naprawa kontrolek obrazu (rozmiar + wyrównanie)

## Kontekst
- Widok: `https://ppm.mpptrade.pl/admin/visual-editor/uve/11183/shop/5`
- Problem dotyczył panelu **Właściwości → Ustawienia obrazu → Rozmiar / Wyrównanie**.

## Objawy
- Kliknięcie presetów **Rozmiar** / **Wyrównanie** nie dawało trwałego efektu w canvas.
- Po każdej zmianie następował **reload iframe** (reset selekcji/scrolla), więc style “znikały”.

## Przyczyna
- Livewire podczas re-renderu aktualizował atrybut `srcdoc` iframe, co powodowało przeładowanie iframe i utratę stylów aplikowanych “na żywo” przez `window.uveApplyStyles()`.
- Dodatkowo `window.uveApplyStyles()` używał `querySelector()` (tylko pierwszy match), co może być problematyczne dla elementów duplikowanych (np. klony w sliderach).

## Zmiany (kod)
- `resources/views/livewire/products/visual-description/unified-visual-editor.blade.php`
  - Dodano `wire:ignore` na iframe edycji, aby Livewire **nie ruszał** `srcdoc` podczas re-renderów.
  - Zmieniono `window.uveApplyStyles()` na `querySelectorAll()` i aplikowanie stylów do **wszystkich** matchy `[data-uve-id="..."]` (bezpieczne dla klonów slidera).
  - Poprawiono selector w `applyStylesToElement()` z `data-element-id` → `data-uve-id`.

## Deploy
- Wgrano zmieniony plik Blade na produkcję (Hostido).
- Wykonano `php artisan view:clear && cache:clear && config:clear`.

## Weryfikacja (Chrome)
- Po deployu potwierdzono w UI:
  - Zmiana **Rozmiar** faktycznie modyfikuje `style="width: ..."` na zaznaczonym `IMG`.
  - Zmiana **Wyrównanie** faktycznie modyfikuje marginesy (`margin-left/right`) i przesuwa obraz.
  - Brak ponownego **reloadu iframe** po klikaniu kontrolek (brak kolejnych logów “Edit mode initialized”).


## Dodatkowy problem (2026-01-14): rozjazd po wielu kliknieciach

### Objaw
- Po kilku/kilkunastu zmianach presetow Rozmiar/Wyrownanie panel potrafil pokazywac inny stan niz canvas (np. panel: 25% / canvas: 100%).

### Przyczyna
- Serwerowy guard bazowal na `_clientSeq`, ale licznik byl lokalny dla Alpine i mogl sie resetowac przy re-renderze.
- Po resecie kolejne klikniecia mialy niski `_clientSeq`, wiec byly ignorowane jako "stare" -> panel (Alpine) zmienial sie, ale Livewire nie aktualizowal stylow w iframe.

### Poprawka (JS)
- `resources/js/app.js`: `_clientSeq` liczony globalnie i monotonicznie:
  - `window.__uveClientSeq = Math.max(window.__uveClientSeq || 0, Date.now()) + 1;`

### Deploy
- Vite build -> nowy bundle: `public/build/assets/app-CRNBg3Bs.js`
- Upload: `public/build/assets/*` + `.vite/manifest.json` -> `public/build/manifest.json`
- Cache clear: `php artisan view:clear && cache:clear && config:clear`

### Weryfikacja (Chrome DevTools)
- Strona laduje nowy asset: `.../public/build/assets/app-CRNBg3Bs.js`.
- On-load: obraz ma `custom (960px)` (panel nie udaje 100%).
- Stress-test (60 zmian rozmiar/wyrownanie): brak mismatch (panel == Livewire == iframe style).

