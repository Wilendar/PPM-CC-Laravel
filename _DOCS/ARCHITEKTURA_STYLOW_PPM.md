# 🎨 ARCHITEKTURA STYLÓW W PPM-CC-LARAVEL

## Przegląd Technologii

PPM-CC-Laravel używa **hybrydowego** podejścia do stylowania:
- **Tailwind CSS** - utility classes (flex, px-4, bg-gray-800)
- **Custom CSS** - komponenty wielokrotnego użytku (.shop-tab-active, .category-add-btn)
- **Vite** - build tool (tylko lokalnie, NIE MA na produkcji!)
- **Blade** - templates używające MIX Tailwind + Custom classes

---

## 1. VITE - Build Tool (tylko lokalnie)

**Rola:** Kompiluje i bundluje wszystkie CSS/JS assets

**Lokalizacja:** `vite.config.js`

```javascript
input: [
    'resources/css/app.css',                    // ← Entry point z @tailwind
    'resources/css/admin/layout.css',           // ← Custom CSS
    'resources/css/admin/components.css',       // ← Custom CSS (5000+ linii)
    'resources/css/products/category-form.css', // ← Custom CSS
    'resources/css/components/category-picker.css', // ← Custom CSS
    'resources/js/app.js',
]
```

**⚠️ KRYTYCZNE:** Vite NIE ISTNIEJE na produkcji (Hostido)! Build robimy LOKALNIE:

```
[LOCAL WINDOWS]                  [PRODUCTION HOSTIDO]
1. Edit CSS files                4. Laravel @vite() helper
2. npm run build (Vite)          5. Reads manifest.json
3. pscp upload build/ →          6. Serves static files
```

---

## 2. TAILWIND CSS - Utility Framework

**Rola:**
- Przetwarza directives `@tailwind base/components/utilities`
- Generuje utility classes z content (Blade, Livewire PHP)
- Dostarcza spacing, colors, typography utilities

**Konfiguracja:** `tailwind.config.js`

```javascript
content: [
  "./resources/**/*.blade.php",
  "./resources/**/*.js",
  "./app/Http/Livewire/**/*.php",  // ← Skanuje PHP dla classes!
],
colors: {
  'mpp-orange': '#e0ac7e',        // Można używać: text-mpp-orange
  'mpp-orange-dark': '#d1975a',
  'brand': {                       // Można używać: bg-brand-500
    500: '#e0ac7e',  // Main MPP Orange
    600: '#d1975a',
  }
}
```

**Entry Point:** `resources/css/app.css`

```css
@tailwind base;       /* Reset CSS + base styles */
@tailwind components; /* Component layer classes */
@tailwind utilities;  /* Utility classes (flex, px-4, etc.) */

:root {
  /* CSS Custom Properties */
  --primary-gold: #e0ac7e;
  --primary-gold-dark: #d1975a;
  --shadow-enterprise: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
```

**Co Tailwind Generuje:**
- `flex`, `grid`, `items-center` - layout
- `px-4`, `py-2`, `gap-3` - spacing
- `bg-gray-800`, `text-white` - colors
- `rounded-lg`, `shadow-md` - borders & shadows
- `hover:bg-blue-700`, `focus:ring-2` - states

---

## 3. CUSTOM CSS FILES - Nasze własne komponenty

**Główne pliki:**

### `resources/css/admin/components.css` (5000+ linii)
Wszystkie custom komponenty UI:
```css
.shop-tab-active { ... }
.category-add-btn { ... }
.field-status-inherited { ... }
.category-checkbox { ... }
```

### `resources/css/admin/layout.css`
Layout, grid, sidebar, responsive:
```css
.admin-layout { ... }
.sidebar-collapsed { ... }
```

### `resources/css/products/category-form.css`
Formularze produktów:
```css
.category-form-container { ... }
.form-input-enterprise { ... }
```

### `resources/css/components/category-picker.css`
Category picker modal:
```css
.category-picker-container { ... }
.category-tree { ... }
```

---

## 4. BLADE TEMPLATES - HTML + Classes

**Rola:** Używają ZARÓWNO Tailwind utilities JAK I custom CSS classes

**✅ POPRAWNIE: Mix Tailwind + Custom**

```blade
{{-- Layout utilities (Tailwind) + Component class (Custom) --}}
<button class="flex items-center gap-2 px-4 py-2 category-add-btn">
    {{--      ↑ Tailwind utilities        ↑ Custom CSS class --}}
    <i class="fas fa-plus"></i>
    Dodaj kategorię
</button>

{{-- Pure Tailwind utilities --}}
<div class="flex flex-col gap-4 p-6 bg-gray-800 rounded-lg">
    <h1 class="text-2xl font-bold text-white">Tytuł</h1>
</div>

{{-- Pure Custom CSS --}}
<div class="enterprise-card">
    <h2 class="card-title">Tytuł</h2>
</div>
```

**❌ ZABRONIONE: Inline styles**

```blade
{{-- NIGDY TAK NIE RÓB! --}}
<button style="background: #e0ac7e; padding: 10px;">  {{-- ❌ --}}
<div class="z-[9999] bg-[#e0ac7e]">  {{-- ❌ Arbitrary values --}}
```

---

## 📋 ZASADY PROJEKTU (z CLAUDE.md)

### ⛔ KATEGORYCZNY ZAKAZ INLINE STYLES

**Dlaczego zakaz:**
- ❌ Inline styles = niemożność maintainability
- ❌ Tailwind arbitrary values (z-[9999]) = trudne do śledzenia
- ❌ Brak consistency w całej aplikacji
- ❌ Niemożliwość implementacji dark mode
- ❌ Trudniejsze debugging CSS issues
- ✅ CSS classes = centralized, cacheable, maintainable

**Dozwolone:**
```blade
{{-- ✅ Standard Tailwind utilities --}}
<div class="flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg">

{{-- ✅ Custom CSS classes --}}
<div class="enterprise-card shop-tab-active">

{{-- ✅ Mix obu --}}
<button class="flex items-center px-4 category-add-btn">
```

**Zabronione:**
```blade
{{-- ❌ Inline styles --}}
<div style="display: flex; padding: 1rem;">

{{-- ❌ Arbitrary values dla z-index, colors, custom values --}}
<div class="z-[9999] bg-[#e0ac7e] shadow-[0_10px_20px_rgba(0,0,0,0.3)]">

{{-- ⚠️ WYJĄTEK: Arbitrary values dla spacing/sizing czasem OK --}}
<div class="w-[350px] h-[calc(100vh-200px)]">  {{-- Akceptowalne --}}
```

---

## 🔄 WORKFLOW DODAWANIA STYLÓW

### Scenariusz 1: Prosty layout/spacing

**Użyj Tailwind utilities:**
```blade
<div class="flex items-center gap-4 px-6 py-4 bg-gray-800 rounded-lg">
    <i class="fas fa-icon text-mpp-orange"></i>
    <span class="text-white font-medium">Tekst</span>
</div>
```

**Kiedy:** Spacing, layout, typography, basic colors

---

### Scenariusz 2: Złożony komponent wielokrotnego użytku

**Stwórz custom CSS class:**

**1. Dodaj CSS do `resources/css/admin/components.css`:**
```css
/* ========================================
   MY NEW COMPONENT
   Description of component
   ======================================== */

.my-component-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #e0ac7e, #d1975a);
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(224, 172, 126, 0.2);
}

.my-component-btn:hover {
    background: linear-gradient(135deg, #d1975a, #c08449);
    box-shadow: 0 4px 8px rgba(224, 172, 126, 0.3);
}
```

**2. Build assets:**
```bash
npm run build
```

**3. Deploy:**
```powershell
# Upload ALL assets (Vite content-based hashing!)
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"

# Upload manifest to ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"

# Clear cache
plink ... "cd domains/.../public_html && php artisan view:clear && php artisan cache:clear"
```

**4. Użyj w Blade:**
```blade
<button class="my-component-btn">
    <i class="fas fa-icon mr-2"></i>
    Kliknij mnie
</button>
```

**Kiedy:** Gradients, complex shadows, multi-state hover effects, reusable components

---

## 🎯 KIEDY CO UŻYWAĆ?

| Potrzebuję | Użyj | Przykład |
|------------|------|----------|
| **Layout** | Tailwind | `flex items-center gap-4` |
| **Spacing** | Tailwind | `px-6 py-4 mb-3` |
| **Typography** | Tailwind | `text-2xl font-bold text-white` |
| **Basic colors** | Tailwind | `bg-gray-800 text-mpp-orange` |
| **Borders** | Tailwind | `border border-gray-600 rounded-lg` |
| **Gradients** | Custom CSS | `.category-add-btn` |
| **Complex shadows** | Custom CSS | `.enterprise-card` |
| **Hover states** | Mix | `hover:bg-gray-700` lub custom class |
| **Transitions** | Custom CSS | `.transition-all duration-200` (Tailwind) + custom effects |
| **Reusable component** | Custom CSS | `.shop-tab-active` |

---

## ⚙️ BUILD PROCESS (szczegóły)

```
┌─────────────────────────────────────────────────────────────┐
│ LOCAL MACHINE (Windows)                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 1. EDIT CSS FILES                                          │
│    resources/css/admin/components.css                      │
│    ├─ .shop-tab-active { ... }                            │
│    └─ .category-add-btn { ... }                           │
│                                                             │
│ 2. npm run build                                           │
│    ├─ Vite reads vite.config.js                           │
│    ├─ Tailwind processes @tailwind directives             │
│    ├─ Scans Blade/PHP for class names                     │
│    ├─ Bundles all CSS                                      │
│    └─ Outputs to public/build/                            │
│                                                             │
│ 3. OUTPUT                                                   │
│    public/build/assets/                                    │
│    ├─ components-D7YdhX11.css (hashed filename)           │
│    ├─ layout-CBQLZIVc.css                                 │
│    └─ app-NQjTxbFs.css                                    │
│                                                             │
│    public/build/.vite/manifest.json                        │
│    {                                                        │
│      "resources/css/admin/components.css": {              │
│        "file": "assets/components-D7YdhX11.css"           │
│      }                                                      │
│    }                                                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ pscp upload
                          ▼
┌─────────────────────────────────────────────────────────────┐
│ PRODUCTION SERVER (Hostido)                                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ public/build/                                              │
│ ├─ assets/                                                 │
│ │  ├─ components-D7YdhX11.css  ◄─── Uploaded             │
│ │  ├─ layout-CBQLZIVc.css                                │
│ │  └─ app-NQjTxbFs.css                                   │
│ └─ manifest.json              ◄─── Uploaded to ROOT!      │
│                                                             │
│ Laravel Blade Template:                                    │
│ @vite(['resources/css/admin/components.css'])             │
│    │                                                        │
│    ├─ Reads manifest.json                                 │
│    ├─ Finds: "assets/components-D7YdhX11.css"            │
│    └─ Outputs: <link href="/build/assets/components-..."> │
│                                                             │
│ Browser receives:                                          │
│ <link href="/build/assets/components-D7YdhX11.css">       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**⚠️ KRYTYCZNE:**
- Manifest MUSI być w ROOT: `public/build/manifest.json`
- Laravel @vite() NIE CZYTA `public/build/.vite/manifest.json`
- Deploy WSZYSTKIE pliki z `public/build/assets/*` (Vite regeneruje hashe!)

---

## 🚨 NAJCZĘSTSZE BŁĘDY

### Błąd 1: Deployment tylko jednego pliku CSS

```powershell
# ❌ BŁĄD
pscp "public/build/assets/components-D7YdhX11.css" host:/path/

# Problem: Inne pliki mają NOWE hashe, ale nie zostały wgrane!
# Vite regeneruje hashe dla WSZYSTKICH plików przy każdym build.
```

**✅ ROZWIĄZANIE:**
```powershell
# Upload WSZYSTKICH assets
pscp -r "public/build/assets/*" host:/path/assets/
```

---

### Błąd 2: Manifest w złej lokalizacji

```powershell
# ❌ BŁĄD
pscp "public/build/.vite/manifest.json" host:/path/.vite/manifest.json

# Problem: Laravel @vite() czyta manifest z ROOT!
```

**✅ ROZWIĄZANIE:**
```powershell
# Upload do ROOT
pscp "public/build/.vite/manifest.json" host:/path/manifest.json
```

---

### Błąd 3: Inline styles zamiast CSS classes

```blade
{{-- ❌ BŁĄD --}}
<button style="background: linear-gradient(45deg, #e0ac7e, #d1975a); padding: 10px;">
```

**✅ ROZWIĄZANIE:**
```css
/* resources/css/admin/components.css */
.my-btn {
    background: linear-gradient(45deg, #e0ac7e, #d1975a);
    padding: 0.625rem;
}
```

```blade
<button class="my-btn">
```

---

### Błąd 4: Brak cache clear po deployment

```powershell
# ❌ BŁĄD - deploy bez cache clear
pscp ... && # Upload finished, done!

# Problem: Laravel serwuje stare cached views!
```

**✅ ROZWIĄZANIE:**
```powershell
# ZAWSZE clear cache po deployment
plink ... "php artisan view:clear && php artisan cache:clear && php artisan config:clear"
```

---

## 📊 PODSUMOWANIE - Quick Reference

| Technologia | Rola | Gdzie działa | Output |
|-------------|------|--------------|--------|
| **Tailwind CSS** | Utility classes generator | Lokalnie (build time) | Wbudowane w bundled CSS |
| **Vite** | CSS/JS bundler | Lokalnie (build time) | `public/build/assets/*.css` |
| **Custom CSS** | Component styles | Lokalnie (write) + Produkcja (serve) | Part of bundled CSS |
| **Blade** | HTML templates | Produkcja (runtime) | HTML z class names |
| **Laravel @vite()** | Asset loader | Produkcja (runtime) | `<link>` tags |

**NAJWAŻNIEJSZE ZASADY:**

1. ✅ **Tailwind utilities** w Blade = OK (flex, px-4, bg-gray-800)
2. ✅ **Custom CSS classes** w Blade = OK (.category-add-btn)
3. ✅ **Mix obu** = OK i ZALECANE
4. ❌ **Inline styles** = ZABRONIONE
5. ❌ **Arbitrary values** dla z-index/colors = ZABRONIONE
6. ⚠️ **Deploy WSZYSTKIE assets** po każdym build (content-based hashing)
7. ⚠️ **Manifest do ROOT** (`public/build/manifest.json`)
8. ⚠️ **Cache clear** po każdym deployment

**WORKFLOW:**
```
Edit CSS → npm run build → Deploy ALL assets + manifest → Cache clear → Verify
```

---

## 📚 Dodatkowe Zasoby

- **CSS Styling Guide:** `_DOCS/CSS_STYLING_GUIDE.md`
- **Deployment Guide:** `_DOCS/DEPLOYMENT_GUIDE.md`
- **Frontend Verification:** `_DOCS/FRONTEND_VERIFICATION_GUIDE.md`
- **Issues & Fixes:** `_ISSUES_FIXES/CSS_*.md`

---

**Ostatnia aktualizacja:** 2025-10-29
**Wersja dokumentu:** 1.0
**Autor:** PPM-CC-Laravel Development Team
