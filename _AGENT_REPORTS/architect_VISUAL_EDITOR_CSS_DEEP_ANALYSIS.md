# VISUAL EDITOR CSS DEEP ANALYSIS REPORT

**Agent:** architect
**Date:** 2025-12-16
**Task:** Analyze PrestaShop KAYO shop CSS structure for PPM Visual Editor preview replication

---

## 1. EXECUTIVE SUMMARY

This report provides a complete analysis of the CSS architecture used in KAYO PrestaShop shop product descriptions. The goal is to understand EXACTLY how to replicate the shop appearance in PPM's visual editor preview.

**Key Findings:**
- Product descriptions use a sophisticated **CSS Grid layout system** with named grid lines
- Custom CSS classes prefixed with `pd-` (product description) control all styling
- Theme uses **Montserrat** font family from Google Fonts
- Color palette centered around **orange brand color** (#ef8248)
- Heavy use of CSS custom properties (variables) for theming
- **Splide.js** library for slider/carousel functionality

---

## 2. CSS FILES HIERARCHY

### 2.1 Loaded Stylesheets (in order)

| Priority | File | Purpose | Size |
|----------|------|---------|------|
| 1 | `/themes/warehouse/assets/css/theme.css` | Core theme (Bootstrap 5.3.3 + warehouse base) | ~200KB |
| 2 | `/themes/warehouse/assets/css/custom.css` | **CRITICAL: All pd-* classes** | ~25KB |
| 3 | `/modules/iqitthemeeditor/views/css/custom_s_1.css` | Theme editor overrides | ~5KB |
| 4 | `fonts.googleapis.com/css?family=Montserrat:400,700` | Typography | External |

### 2.2 FTP Access to CSS Files

```
Server: host226673.hostido.net.pl
Login: test@test.kayomoto.pl
Password: PmnQ6JDe7m8fry4hC46w
Path: /home/host226673/domains/test.kayomoto.pl/themes/warehouse/assets/css/
```

**Key Files:**
- `custom.css` - All product description styles (pd-* classes)
- `theme.css` - Base Bootstrap + theme styles

---

## 3. HTML STRUCTURE DIAGRAM

```
#description
  └── .product-description
        └── .rte-content (CSS Grid Container)
              │
              ├── .pd-base-grid.pd-intro
              │     ├── h2.pd-intro__heading.pd-model
              │     │     ├── span.pd-model__type ("Buggy")
              │     │     └── span.pd-model__name ("KAYO S200")
              │     ├── p.pd-intro__text
              │     ├── div.pd-cover.grid-row
              │     │     └── picture.pd-cover__picture
              │     │           └── img (product image)
              │     └── div.grid-row.bg-brand
              │           └── ul.pd-asset-list (feature bullets)
              │
              ├── .pd-pseudo-parallax (full-viewport parallax image)
              │     └── picture > img.pd-pseudo-parallax__img
              │
              ├── .pd-block.bg-neutral-accent.pd-slider (feature slider)
              │     ├── h2.pd-block__heading.pd-slider__heading
              │     │     └── span.pd-text-secondary
              │     ├── div.splide__arrows (navigation arrows)
              │     ├── div.splide__track.pd-slider__track
              │     │     └── ul.splide__list
              │     │           └── li.pd-slide.splide__slide (x4)
              │     │                 ├── img.pd-slide__img
              │     │                 ├── h3.pd-slide__title
              │     │                 └── div.pd-slide__desc
              │     └── ul.splide__pagination
              │
              ├── .pd-block.pd-brand-backdrop (orange overlay section)
              │     ├── h2.pd-block__heading
              │     ├── div.pd-text-block
              │     └── ul.pd-where-2-ride
              │
              ├── .pd-block (merits/benefits section)
              │     ├── h2.pd-block__heading
              │     └── ul.pd-merits.pd-merits--dividers
              │           └── li.pd-merit.pd-icon--* (x3)
              │
              ├── .pd-pseudo-parallax (another parallax image)
              │
              ├── .pd-block.pd-specification (specifications table)
              │     ├── h2.pd-block__heading
              │     └── div.pd-spec
              │           └── div.pd-spec__block (x2)
              │                 ├── h3.pd-spec__name
              │                 └── dl.pd-spec__list
              │
              ├── aside.pd-block-row.pd-more-links
              │     └── a.pd-more-link (x2)
              │
              └── footer.pd-block-row.pd-schema-neutral-accent
                    └── final CTA section
```

---

## 4. CRITICAL CSS CLASSES

### 4.1 Grid System (MOST IMPORTANT!)

The entire layout is based on a **named grid column system**:

```css
.product-description .rte-content, .pd-base-grid {
  --block-breakout: calc((var(--max-content-width) - var(--max-text-width)) / 2);
  display: grid;
  grid-column: 1 / -1;
  justify-content: center;
  grid-template-columns:
    [row-start] minmax(var(--inline-padding, 1rem), 1fr)
    [block-start] minmax(0, var(--block-breakout))
    [text-start] min(var(--max-text-width), 100% - 2 * var(--inline-padding))
    [text-end] minmax(0, var(--block-breakout))
    [block-end] minmax(var(--inline-padding, 1rem), 1fr)
    [row-end];
}

/* Child elements placement */
:where(.rte-content) > * { grid-column: text; }          /* Default: text column */
:where(.rte-content) > :is(div, section) { grid-column: block; }  /* Divs: block width */
.grid-row { grid-column: 1 / -1; }                       /* Full width */
.pd-text-block { grid-column: text; }                    /* Explicit text */
```

**Grid Column Names:**
- `row` = Full viewport width (1/-1)
- `block` = Content + margins (block-start to block-end)
- `text` = Main text content (text-start to text-end)

### 4.2 Header Section (pd-intro)

```css
.pd-intro {
  grid-column: 1 / -1;
  padding-top: 0;
  padding-inline: 0;
}

.pd-intro__heading {
  display: grid;
  grid-template-columns: minmax(4ch, 50%) auto;
  column-gap: 1rem;
  padding-block: clamp(2.5rem, 2rem + 4vw, 6rem) clamp(3rem, 2rem + 5vw, 8rem);
  width: max-content;
  max-width: 100%;
  font-size: clamp(2rem, 2rem + 2vw, 3.5rem);
  line-height: 1;
  justify-self: start;
}

/* Orange underline decoration */
.pd-intro__heading::before {
  content: "";
  display: block;
  grid-row: 2 / 3;
  height: 0.75rem;
  align-self: center;
  background: #eb5e20;  /* ORANGE ACCENT */
}

.pd-model__type {
  grid-area: 2 / 2 / 3 / -1;
  font-size: 0.7em;
  font-weight: 400;
}

.pd-model__name {
  grid-column: 1 / -1;
  font-weight: 800;
}
```

### 4.3 Background Classes

```css
/* Orange brand background */
.bg-brand {
  --bg-color: #ef8248;
  background-color: rgb(239, 130, 72);
}

/* Dark/black background */
.bg-neutral-accent {
  background-color: rgb(0, 0, 0);
}

/* Orange backdrop with blur */
.pd-brand-backdrop {
  background: rgba(239, 130, 72, 0.85);
  backdrop-filter: blur(4px);
  outline: rgba(239, 130, 72, 0.85) solid 4px;
  outline-offset: 0.5rem;
  color: rgb(250, 250, 250);
  text-shadow: black 0px 0px 1px;
  font-size: 1.2em;
}
```

### 4.4 Product Image with Gradient

```css
.pd-cover__picture {
  width: 100%;
  display: block;
  background: linear-gradient(#f6f6f6 70%, #ef8248 70%);  /* Gray to orange at 70% */
}

.pd-cover__picture img {
  margin-inline: auto;
  display: block;
}
```

### 4.5 Parallax Effect

```css
.pd-pseudo-parallax {
  display: grid;
  grid-column: 1 / -1;
  height: 100dvh;
  width: 100%;
  position: relative;
  clip-path: border-box;
  z-index: 0;
}

.pd-pseudo-parallax img.pd-pseudo-parallax__img {
  height: max(100dvh, 100vh, 100%);
  width: 100%;
  object-fit: cover;
  position: fixed;
  top: 0;
  left: 0;
  z-index: -1;
}
```

### 4.6 Block Sections

```css
.pd-block, .pd-block-row {
  padding-block: min(10rem, 2rem + 5vw);
  color: rgb(from var(--text-color) r g b / var(--text-color-alpha, 1));
  background-color: rgb(from var(--bg-color) r g b / var(--bg-color-alpha, 1));
}

.pd-block__heading {
  font-size: clamp(1.75rem, 1.125rem + 2vw, 3rem);
  font-weight: 700;
  max-width: min(100%, 1300px);
  margin-inline: auto;
  margin-block-end: min(1em, 3rem);
}
```

### 4.7 Feature Slider (Splide)

```css
.pd-slider {
  display: grid;
  padding-inline: var(--_inline-padding);
  grid-template-columns: minmax(min-content, 50%) 1fr auto;
  column-gap: 2rem;
  color: #fff;
}

.pd-slide {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto 1fr;
  grid-template-areas: "img" "title" "desc";
}

.pd-slide__title {
  grid-area: title;
  background-color: rgba(0, 0, 0, 0.67);
  padding: 1rem;
  font-size: 28px;
  font-weight: 700;
}

/* Responsive: side-by-side on large screens */
@media (min-width: 960px) {
  .pd-slide {
    grid-template-columns: minmax(0, calc(50% - 1rem)) 1fr;
    grid-template-areas: "img title" "img desc";
  }
}
```

### 4.8 Specifications Table

```css
.pd-spec {
  display: grid;
  row-gap: 2rem;
}

@media (min-width: 760px) {
  .pd-spec {
    grid-template-columns: 1fr 1fr;
    column-gap: 4rem;
  }

  .pd-spec__block {
    border: 1px solid #e7e7e7;
    box-shadow: -4px 4px 8px #e7e7e7;
    padding: 2rem;
  }
}

.pd-spec__name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #dd4819;  /* Dark orange */
  border-bottom: 1px solid #d1d1d1;
}

.pd-spec__list dt {
  font-weight: 400;
  text-transform: uppercase;
}

.pd-spec__list dd {
  font-weight: 600;
}
```

---

## 5. COLOR PALETTE

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Primary Orange | `#ef8248` | rgb(239, 130, 72) | Main brand, backgrounds |
| Accent Orange | `#eb5e20` | rgb(235, 94, 32) | Links, underlines, hovers |
| Dark Orange | `#dd4819` | rgb(221, 72, 25) | Headings, emphasis |
| Light Orange | `#f5ad7c` | rgb(245, 173, 124) | Dividers, soft accents |
| Black | `#000000` | rgb(0, 0, 0) | Dark sections |
| White | `#ffffff` | rgb(255, 255, 255) | Text on dark |
| Gray Light | `#f6f6f6` | rgb(246, 246, 246) | Backgrounds |
| Gray Medium | `#d1d1d1` | rgb(209, 209, 209) | Borders, dividers |
| Gray Dark | `#3d3d3d` | rgb(61, 61, 61) | Secondary text |
| Text Primary | `#212529` | rgb(33, 37, 41) | Body text |

---

## 6. TYPOGRAPHY

### 6.1 Font Family

```css
font-family: Montserrat, sans-serif;
```

**Google Fonts Import:**
```html
<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
```

### 6.2 Font Sizes (Fluid Typography)

| Element | CSS | Min | Max |
|---------|-----|-----|-----|
| Model Name | `clamp(2rem, 2rem + 2vw, 3.5rem)` | 32px | 56px |
| Block Heading | `clamp(1.75rem, 1.125rem + 2vw, 3rem)` | 28px | 48px |
| Slide Title | `28px` | - | - |
| Secondary Text | `0.5em` of parent | - | - |
| Body | `16px` base | - | - |
| Intro Text | `18px` | - | - |

### 6.3 Font Weights

- **400** - Regular (body, type label)
- **600** - Semi-bold (subheadings)
- **700** - Bold (headings)
- **800** - Extra-bold (model name)

---

## 7. RESPONSIVE BREAKPOINTS

| Name | Width | Usage |
|------|-------|-------|
| sm | 500px | Asset list flex wrap |
| md | 680px | More links layout, merits grid |
| lg | 760px | Spec grid 2 columns |
| xl | 960px | Slide side-by-side, color grid |
| xxl | 1024px | Merits 4 columns |
| xxxl | 1300px | Layered bg padding |

---

## 8. RECOMMENDATIONS FOR PPM VISUAL EDITOR

### 8.1 CSS Scoping Strategy

**Option A: Prefixed Isolation (RECOMMENDED)**

Wrap all shop-specific CSS with a scoped prefix:

```css
.ppm-preview-kayo .pd-intro__heading { ... }
.ppm-preview-kayo .bg-brand { ... }
```

**Option B: Shadow DOM**

Use Shadow DOM for complete style isolation:

```javascript
const preview = document.querySelector('#visual-editor-preview');
const shadow = preview.attachShadow({ mode: 'open' });
shadow.innerHTML = `
  <style>@import url('/css/shops/kayo-preview.css');</style>
  <div class="product-description">...</div>
`;
```

### 8.2 Required CSS Files for Preview

1. **Base Grid System** (from custom.css):
   - `.rte-content`, `.pd-base-grid` grid definitions
   - Grid column name system (row, block, text)

2. **Component Styles** (from custom.css):
   - All `pd-*` classes (~150 rules)
   - Background utilities (`bg-brand`, `bg-neutral-accent`)
   - Grid utilities (`.grid-row`)

3. **External Dependencies**:
   - Google Fonts: Montserrat 400, 700
   - Splide.js CSS (if using sliders)

### 8.3 CSS Variables to Define

```css
:root {
  /* Layout */
  --max-content-width: 1300px;
  --max-text-width: 760px;
  --inline-padding: 1rem;

  /* Colors */
  --brand-color: #ef8248;
  --accent-color: #eb5e20;
  --dark-accent: #dd4819;
  --light-accent: #f5ad7c;
  --bg-neutral: #f6f6f6;
  --text-color: #212529;

  /* Typography */
  --font-family: 'Montserrat', sans-serif;
}
```

### 8.4 Minimal Preview CSS

For a lightweight preview without full fidelity:

```css
/* Minimal KAYO Preview - ~50 rules instead of 150+ */
.kayo-preview {
  font-family: 'Montserrat', sans-serif;
  font-size: 16px;
  line-height: 1.4;
  color: #212529;
}

.kayo-preview .pd-intro__heading {
  font-size: 2.5rem;
  font-weight: 700;
  padding: 3rem 0 4rem;
  border-bottom: 0.75rem solid #eb5e20;
  display: inline-block;
}

.kayo-preview .pd-model__type {
  font-size: 0.7em;
  font-weight: 400;
  display: block;
}

.kayo-preview .pd-model__name {
  font-weight: 800;
}

.kayo-preview .bg-brand {
  background-color: #ef8248;
  padding: 3rem;
}

.kayo-preview .bg-neutral-accent {
  background-color: #000;
  color: #fff;
  padding: 3rem;
}

.kayo-preview .pd-block__heading {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 2rem;
}

.kayo-preview .pd-text-secondary {
  font-size: 0.5em;
  opacity: 0.5;
  display: block;
}
```

---

## 9. COMPLETE pd-* CLASS LIST

### Layout Classes
- `pd-base-grid` - Grid container
- `pd-intro` - Introduction section
- `pd-cover` - Product image cover
- `pd-block` - Content block
- `pd-block-row` - Full-width block
- `pd-text-block` - Text column width
- `pd-pseudo-parallax` - Parallax image

### Typography Classes
- `pd-intro__heading` - Main heading
- `pd-intro__text` - Intro paragraph
- `pd-model` - Model name container
- `pd-model__type` - Product type (small)
- `pd-model__name` - Model name (large)
- `pd-block__heading` - Section heading
- `pd-text-secondary` - Secondary text (smaller, muted)

### Component Classes
- `pd-cover__picture` - Picture with gradient
- `pd-pseudo-parallax__img` - Fixed parallax image
- `pd-asset-list` - Feature bullet list
- `pd-slider` - Slider container
- `pd-slider__heading` - Slider heading
- `pd-slider__track` - Slider track
- `pd-slide` - Individual slide
- `pd-slide__img` - Slide image
- `pd-slide__title` - Slide title
- `pd-slide__desc` - Slide description

### Benefits/Merits Classes
- `pd-merits` - Merits grid
- `pd-merits--dividers` - With divider lines
- `pd-merits--cards` - Card style
- `pd-merit` - Single merit item
- `pd-merit__heading` - Merit heading
- `pd-merit__desc` - Merit description
- `pd-icon--wallet` - Wallet icon
- `pd-icon--tick` - Checkmark icon
- `pd-icon--gift` - Gift icon
- `pd-icon--package` - Package icon

### Specification Classes
- `pd-specification` - Specs container
- `pd-spec` - Spec grid
- `pd-spec__block` - Spec column
- `pd-spec__name` - Spec section title
- `pd-spec__list` - Definition list

### Link Classes
- `pd-more-links` - Links footer
- `pd-more-link` - Individual link
- `pd-more-link--pin` - Pin icon link
- `pd-more-link--globe` - Globe icon link

### Special Effects
- `pd-brand-backdrop` - Orange blur overlay
- `pd-layered-bg` - Layered background
- `pd-blur-layer` - Blur effect layer
- `pd-content-layer` - Content over image
- `pd-stack-grid` - Stacked content
- `pd-where-2-ride` - Ride location list

### Utility Classes
- `pd-content` - Content wrapper
- `pd-text-shadow` - Text shadow
- `pd-full-width-video` - Video container
- `pd-color-grid` - Color columns
- `pdb-pi-fill` - Padding inline fill
- `pdb-pi-0` - Zero inline padding

---

## 10. ELEMENT COUNT

| Element Type | Count |
|--------------|-------|
| Total Elements | 185 |
| Unique Classes | 87 |
| Unique Tags | 23 |
| CSS Rules (pd-*) | ~150 |
| Inline Styles | 10 (Splide.js only) |

---

## 11. FILES TO DOWNLOAD FOR FULL REPLICATION

### From KAYO FTP:
1. `/themes/warehouse/assets/css/custom.css` - **CRITICAL** (all pd-* styles)
2. `/themes/warehouse/assets/css/theme.css` - Base theme (Bootstrap + warehouse)

### External:
1. Google Fonts Montserrat: `https://fonts.googleapis.com/css?family=Montserrat:400,700`
2. Splide.js CSS (optional, for sliders): `https://cdn.jsdelivr.net/npm/@splidejs/splide/dist/css/splide.min.css`

### Icons (if needed):
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/wallet.svg`
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/tick.svg`
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/gift.svg`
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/package.svg`
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/pin.svg`
- `https://mm.mpptrade.pl/ps-themes/kayo/icons/globe.svg`

---

## 12. NEXT STEPS

1. **Download custom.css** from KAYO FTP server
2. **Extract pd-* rules** into `resources/css/shops/kayo-preview.css`
3. **Add CSS scoping** with `.ppm-preview-kayo` prefix
4. **Define CSS variables** for shop-specific values
5. **Test preview** with sample product description HTML
6. **Implement shop selector** to load appropriate CSS per shop

---

**Report Generated:** 2025-12-16
**Agent:** architect
**Status:** ANALYSIS COMPLETE - NO IMPLEMENTATION
