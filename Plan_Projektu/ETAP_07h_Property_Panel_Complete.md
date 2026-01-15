# ETAP_07h: Property Panel - Kompletna Implementacja dla Wszystkich Blokow

**Status:** 🛠️ W trakcie
**Priorytet:** KRYTYCZNY (wymagane przed FAZA 7 - szablony)
**Zaleznosci:** `Plan_Projektu/ETAP_07h_UVE_CSS_First.md` (ukonczone)

## Problem

Obecnie tylko czesc blokow ma w pelni dzialajacy panel wlasciwosci. Dla wielu elementow panel nie pokazuje odpowiednich kontrolek lub brakuje kontrolek specyficznych dla bloku.

**ROOT CAUSE (historycznie):** `PropertyPanelService::getBaseControlsForElement()` opieral sie glownie o typ HTML elementu, a nie typ bloku/sekcji.

## Cel

**No-code edycja wizualna wszystkich blokow** - uzytkownik moze kliknac dowolny element i edytowac jego wlasciwosci w panelu. Parametry musza odpowiadac 1:1 stylom CSS i strukturze HTML z PrestaShop.

## Architektura rozwiazania

### Podejscie: Block-Specific Controls

Zamiast opierac sie wylacznie na typie HTML elementu, system powinien:
1. Rozpoznawac **typ bloku** (np. `image`, `slider`, `merit-list`)
2. Przypisywac **kontrolki specyficzne dla bloku** zdefiniowane w schemacie bloku
3. Laczyc je z **kontrolkami bazowymi** wynikajacymi z typu HTML elementu

## UVE Definition of DONE:
⦁	Każdy blok ma poprawnie działający panel właściwości z odpowiednimi dla danego bloku parametrami
⦁	UVE umożliwa wyświetlenie i edycję .css z prestashop. Zapisanie zmian w css prez UVE jest automatycznie zapisywane w prestashop.
⦁	Wszystkie bloki z opisu prestashop są widoczne na liscie warstw w PPM
⦁	Każdy blok ma opcję zapisania go jako szablon. Szablony są zapisywane per shop prestashop ze względu na używane przez nie style css, nie można użyć szablonu bloku z opisu jednego sklepu prestashop na drugim.
⦁	Po otworzeniu UVE każdy blok powinien mieć zdefiniowane parmetry w panelu właściwości na podstawie kodu HTML + CSS pobranych z prestashop. Panel właściwosci nie może się wczytać bez zdefiniowanych parametrów.
⦁	Panel Warstwy powinien pokazywać też zagniezdżone bloki wewnątrz większych bloków, każdy zagnieżdżony blok powinien być też edytowalny.
⦁	Każda zmiana parametrów w panelu właściwości jest od razu odzwierciedlana w HTML i CSS które są przesyłane natychmiast na prestashop w momencie zapisania zmian w PPM.
⦁	Każdy parametr w Panelu właściwości musi być odzwierciedlony w Canvas 1:1. Paramtery i canvas muszą być zsynchronizowane.
⦁	Po zapisaniu zmian w opisie, opis na prestashop jest 1:1 z opisem w canva/pogląd PPM

---

## Analiza blokow - wymagane kontrolki (wycinek)

### 1. LAYOUT BLOCKS

#### 1.1 HeroBannerBlock (`hero-banner`)
- Element: `<div class="pd-hero-banner">`
- Obecne: `layout-flex`, `background`, `box-model`, `size`
- Brakujace:
  - `parallax-settings` (min-height, background-attachment)
  - `effects` (box-shadow, opacity, filter)
  - `position` (overlay positioning)

#### 1.2 GridSectionBlock (`grid-section`)
- Element: `<div class="pd-grid">`
- Brakujace:
  - `layout-grid` (grid-template-columns, gap)

#### 1.3 TwoColumnBlock (`two-column`)
- Element: `<div class="pd-two-column">`
- Brakujace:
  - `layout-flex` (flex-direction, gap, align-items)
  - `size` (width proportions)

#### 1.4 ThreeColumnBlock (`three-column`)
- Element: `<div class="pd-three-column">`
- Brakujace: analogicznie do TwoColumnBlock

#### 1.5 FullWidthBlock (`full-width`)
- Element: `<div class="pd-full-width">`
- Brakujace:
  - `background`
  - `size` (min-height, max-width)

---

### 2. CONTENT BLOCKS

#### 2.1 HeadingBlock (`heading`) ✅
- Element: `<h1-h6>`
- Kontrolki: `typography`, `color-picker`, `box-model`, `size`

#### 2.2 TextBlock (`text`) ✅
- Element: `<p>`, `<div class="pd-text">`
- Kontrolki: `typography`, `color-picker`, `box-model`, `size`

#### 2.3 FeatureCardBlock (`feature-card`)
- Element: `<div class="pd-feature-card">`
- Brakujace (przyklad): `background`, `border`, `effects`, `typography`, `color-picker`

#### 2.4 SpecTableBlock (`spec-table`)
- Element: `<table class="pd-spec-table">`
- Brakujace (przyklad): `border`, `typography`, `color-picker`

#### 2.5 MeritListBlock (`merit-list`)
- Element: `<div class="pd-merit-list">`
- Brakujace (przyklad): `layout-flex`, `layout-grid`, `color-picker`, `typography`

#### 2.6 InfoCardBlock (`info-card`)
- Element: `<div class="pd-info-card">`
- Brakujace (przyklad): `background`, `border`, `effects`

---

### 3. MEDIA BLOCKS

#### 3.1 ImageBlock (`image`)
- Element: `<figure class="pd-image">`, `<img>`
- Kontrolki (docelowo): `image-settings`, `border`, `effects`

**Kontrolka: `image-settings`**
- Rozmiar: `full|large|medium|small|custom`
- Wyrownanie: `left|center|right`
- Dopasowanie: `object-fit`
- Opcje: `lightbox`, `lazyLoad`

#### 3.2 ImageGalleryBlock (`image-gallery`)
- Element: `<div class="pd-gallery">`
- Brakujace (przyklad): `layout-grid`, `effects`

#### 3.3 VideoEmbedBlock (`video-embed`)
- Element: `<div class="pd-video">`
- Brakujace (przyklad): `size`, `border`

#### 3.4 ParallaxImageBlock (`parallax-image`)
- Element: `<div class="pd-parallax">`
- Brakujace (przyklad): `parallax-settings`, `background`, `effects`

#### 3.5 PictureElementBlock (`picture-element`)
- Element: `<picture>`, `<source>`, `<img>`
- Brakujace (przyklad): `media-picker`, `size`

---

### 4. INTERACTIVE BLOCKS

#### 4.1 SliderBlock (`slider`)
- Element: `<div class="pd-slider splide">`
- Brakujace (przyklad): `slider-settings`, `size`

---

## Plan implementacji

### FAZA PP.0: Critical Bug Fixes (BLOCKING - 1-2 dni)

**Status:** ✅ Ukonczone (2026-01-14)
**Priorytet:** KRYTYCZNY - blokuje inne fazy

#### PP.0.6: Naprawa JS `uveImageSettingsControl is not defined` ✅

#### PP.0.7: Weryfikacja Chrome - 0 bledow konsoli ✅

#### PP.0.8: UVE image-settings - desync size/alignment (clientSeq) ✅

**Problem:** Po kilku zmianach Rozmiar/Wyrownanie panel potrafil pokazac inny preset niz canvas.
**Fix:** Globalny, monotoniczny `_clientSeq` po stronie JS (window.__uveClientSeq) + server-side guard.
**Weryfikacja:** Chrome DevTools stress-test (60 zmian) -> panel == canvas.

### FAZA PP.1: Infrastructure Enhancement (2-3 dni)

**Status:** ✅ Ukonczone (2026-01-14)

---