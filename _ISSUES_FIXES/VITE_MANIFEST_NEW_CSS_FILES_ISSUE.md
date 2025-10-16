# VITE MANIFEST ISSUE - Dodawanie Nowych Plików CSS

**Data Discovered:** 2025-10-14
**Severity:** 🔥 CRITICAL
**Status:** ✅ RESOLVED (workaround implemented)
**Related Files:** `vite.config.js`, `resources/views/layouts/admin.blade.php`, all CSS files

---

## 🚨 PROBLEM DESCRIPTION

**⚠️ WAŻNE WYJAŚNIENIE:** Vite **NIE ISTNIEJE** na serwerze produkcyjnym (Hostido - shared hosting bez Node.js/npm)! Build robimy **LOKALNIE** na Windows, a na serwer wysyłamy **GOTOWE zbudowane pliki** z `public/build/`.

**Problem:** Laravel Vite helper (`@vite()` directive) na produkcji ma problem z odczytaniem/cache `manifest.json` przy dodawaniu NOWYCH plików CSS do `vite.config.js`. Pomimo prawidłowego lokalnego buildu, uploaded manifest i wyczyszczonego cache, Laravel nie może zlokalizować nowego pliku CSS w manifeście produkcyjnym.

### Symptoms

```
Illuminate\Foundation\ViteException

Unable to locate file in Vite manifest: resources/css/components/category-preview-modal.css
```

**Charakterystyka:**
- ✅ Build lokalnie działa (npm run build)
- ✅ Manifest zawiera entry nowego pliku
- ✅ Plik CSS istnieje w `public/build/assets/`
- ✅ Cache wyczyszczony (`php artisan view:clear && php artisan cache:clear`)
- ❌ Laravel nadal wyrzuca ViteException

### Environment Specifics

- **Local:** Windows + Vite 5.4.20 - działa bez problemu
- **Production:** Hostido (PHP 8.3.23, Laravel 12.x) - ViteException
- **Występuje:** Tylko przy dodawaniu NOWYCH plików CSS do `vite.config.js`
- **Nie występuje:** Przy modyfikacji istniejących plików CSS

---

## 🔍 ROOT CAUSE ANALYSIS

### Attempted Solutions (Failed)

1. **Multiple cache clears:**
   ```bash
   php artisan view:clear
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan optimize:clear
   rm -rf bootstrap/cache/*.php
   rm -rf storage/framework/views/*.php
   ```
   ❌ Nie pomogło

2. **Manifest timestamp update:**
   ```bash
   touch public/build/.vite/manifest.json
   ```
   ❌ Nie pomogło

3. **Complete re-upload:**
   - Re-upload `vite.config.js`
   - Re-upload `admin.blade.php` (z nowym @vite entry)
   - Re-upload całego `public/build/` directory
   - Re-upload nowego pliku CSS source
   ❌ Nie pomogło

### Build & Deployment Workflow (Context)

```
[Local Windows Machine]                    [Production Hostido Server]
1. Edit resources/css/...                  5. Laravel boots
2. npm run build (Vite)                    6. @vite() helper executes
   ├─ Compiles CSS                         7. Reads manifest.json
   ├─ Hashes filenames                     8. Maps entries → hashed files
   └─ Creates manifest.json                9. Generates <link> tags
3. Built assets in public/build/
4. pscp upload → Hostido                   ❌ ViteException if mapping fails!
```

**⚠️ KRYTYCZNE:** Vite NIE JEST zainstalowany na Hostido! Problem występuje w **Laravel Vite helper** (PHP), nie w Vite (JavaScript/Node.js).

### Suspected Cause

**Teoria:** Laravel Vite helper/PHP na produkcji ma aggressive caching lub race condition przy wykrywaniu nowych entries w manifeście. Możliwe że:

1. **PHP Opcache** cache'uje stary manifest.json w pamięci
2. **Laravel's internal Vite manifest cache** nie invaliduje się poprawnie przy nowych entries
3. **Filesystem cache delay** na Hostido (shared hosting)
4. **Laravel Vite helper bug** przy dodawaniu nowych entries do manifest (edge case)

**Brak możliwości głębszego debug** ze względu na shared hosting environment:
- ❌ Brak dostępu do PHP opcache controls
- ❌ Brak możliwości restart PHP-FPM
- ❌ Brak dostępu do server logs (tylko Laravel logs)
- ❌ Brak możliwości profiling PHP execution

---

## ✅ SOLUTION: Add Styles to Existing CSS Files

**Zamiast** tworzyć nowe pliki CSS, **dodawaj style do istniejących plików**.

### Implementation

#### ❌ BEFORE (Problematic Approach)

```javascript
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/components.css',
                'resources/css/components/category-preview-modal.css', // ← NEW FILE
            ],
        }),
    ],
});
```

```blade
{{-- resources/views/layouts/admin.blade.php --}}
@vite([
    'resources/css/app.css',
    'resources/css/admin/components.css',
    'resources/css/components/category-preview-modal.css', // ← ViteException!
])
```

#### ✅ AFTER (Working Solution)

```css
/* resources/css/admin/components.css */

/* ... existing styles ... */

/* ========================================
   CATEGORY MODALS Z-INDEX (ETAP_07 FAZA 3D)
   ======================================== */

/* Main category preview modal - base z-index */
.modal-category-preview-root {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    overflow-y: auto;
    z-index: 10;
}

/* Conflict resolution modal - must appear above main modal */
.modal-conflict-resolution-root {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    overflow-y: auto;
    z-index: 11;
}
```

```blade
{{-- resources/views/layouts/admin.blade.php --}}
@vite([
    'resources/css/app.css',
    'resources/css/admin/components.css', // ← Style added HERE (existing file)
    'resources/css/products/category-form.css',
])
```

```html
<!-- resources/views/livewire/components/category-preview-modal.blade.php -->
<div class="modal-category-preview-root">
    <!-- Main modal content -->
</div>

<div class="modal-conflict-resolution-root">
    <!-- Conflict modal content -->
</div>
```

### Benefits of This Approach

✅ **No Vite Manifest Issues:** Existing files already tracked by Vite
✅ **Simpler Deployment:** Fewer files to manage
✅ **Faster Build Times:** Less entry points = faster builds
✅ **Better Organization:** Related styles grouped together
✅ **Easier Maintenance:** All component styles in one place

---

## 🛡️ PREVENTION RULES

### ⚠️ BEFORE Adding ANY CSS

**Ask yourself:**
1. Can I add these styles to an existing CSS file? (95% yes)
2. Is this a new large module (>200 lines of styles)? (rarely)
3. Did I consult with user about creating new CSS file? (mandatory)

### Existing CSS Files (Safe to Extend)

- `resources/css/admin/components.css` - Admin UI components, modals, forms
- `resources/css/admin/layout.css` - Admin layout, grid, sidebar
- `resources/css/products/category-form.css` - Product forms & pickers
- `resources/css/components/category-picker.css` - Category selection components

### When Creating NEW CSS File is Acceptable

- ✅ **LARGE new module** (>200 lines of dedicated styles)
- ✅ **After user consultation** (explicit approval)
- ✅ **Full production test** completed BEFORE merge
- ✅ **Documented fallback plan** if Vite manifest fails

---

## 📋 DEPLOYMENT CHECKLIST

When adding CSS styles (to existing files):

- [ ] Identify appropriate existing CSS file
- [ ] Add section comment describing styles purpose
- [ ] Define CSS classes (NO inline styles!)
- [ ] Build locally: `npm run build`
- [ ] Test locally
- [ ] Deploy CSS source file to production
- [ ] Deploy `public/build/` assets
- [ ] Clear caches: `php artisan view:clear && php artisan cache:clear`
- [ ] Screenshot verification (mandatory)
- [ ] User verification (mandatory)

---

## 💡 EXAMPLES FROM PROJECT

### Case Study: Category Preview Modal Z-Index Fix

**Original Attempt (Failed):**
1. Created `resources/css/components/category-preview-modal.css`
2. Added to `vite.config.js`
3. Built locally ✅
4. Deployed to production
5. **Result:** ViteException ❌

**Working Solution:**
1. Added styles to `resources/css/admin/components.css` (existing file)
2. Removed new file from `vite.config.js`
3. Built locally ✅
4. Deployed to production
5. **Result:** Works perfectly ✅

**Files Changed:**
- `resources/css/admin/components.css` (+30 lines CSS)
- `resources/views/livewire/components/category-preview-modal.blade.php` (added CSS classes)
- `resources/views/layouts/admin.blade.php` (NO changes - uses existing @vite entry)

**Deployment Time:** ~10 minutes (vs 2+ hours debugging Vite manifest issue)

---

## 🔗 RELATED ISSUES

- **[CSS Styling Guide](_DOCS/CSS_STYLING_GUIDE.md)** - Complete CSS styling rules
- **[Frontend Verification Guide](_DOCS/FRONTEND_VERIFICATION_GUIDE.md)** - Screenshot verification workflow
- **[Deployment Guide](_DOCS/DEPLOYMENT_GUIDE.md)** - Production deployment best practices

---

## 📝 LESSONS LEARNED

1. **Vite działa TYLKO lokalnie:** Na produkcji (Hostido) nie ma Node.js/Vite - tylko zbudowane pliki!
2. **Problem w Laravel, nie w Vite:** ViteException występuje w PHP (Laravel Vite helper), nie w JavaScript (Vite)
3. **Shared hosting limitations:** Brak dostępu do PHP opcache/server controls = brak możliwości deep debug
4. **Workaround > Fix:** Czasami lepiej obejść problem niż go naprawiać (gdy root cause nieznany i brak dostępu do internals)
5. **Simplicity wins:** Mniej plików CSS = mniej problemów z manifest tracking = łatwiejszy deployment
6. **Test production early:** Laravel Vite helper behavior może różnić się między dev/production environments
7. **Document workarounds:** Jeśli coś działa, udokumentuj to dla przyszłych przypadków (especially w shared hosting scenarios)

---

**Last Updated:** 2025-10-14
**Verified Solution:** ✅ Working on production (ppm.mpptrade.pl)
**Status:** Workaround implemented, root cause unknown (suspected Vite/Hostido caching issue)
