# PPM Styling Workflow & Deployment Checklist

**Version:** 1.0.0
**Last Updated:** 2025-11-19

Complete workflow for adding new styles and deploying to PPM production environment.

---

## Workflow: Adding New Styles

### Step 1: Check Existing Classes

**BEFORE creating new CSS, search for existing patterns!**

```bash
# Search in admin components
grep -r ".btn-" resources/css/admin/components.css

# Search in product styles
grep -r ".category-" resources/css/products/

# Find similar patterns
grep -r "badge" resources/css/
```

**Using Claude Code Tools:**
```
Grep pattern: "\.btn-enterprise-" in resources/css/
Glob pattern: "**/*.css" to see all CSS files
Read: resources/css/admin/components.css
```

**Questions to ask:**
- Does a similar component already exist?
- Can I extend an existing class with a modifier (e.g., `.btn-enterprise-primary--large`)?
- Should this be a new variant or a completely new component?

---

### Step 2: Decide on File Location

**File Organization:**
```
resources/css/
├── app.css                          # Main entry (Tailwind directives + imports)
├── admin/
│   ├── layout.css                   # Grid, containers, spacing (120 lines)
│   └── components.css               # Reusable UI components (350 lines)
├── products/
│   ├── category-form.css            # Category picker, form layouts (280 lines)
│   └── variant-management.css       # Variant UI styles (TBD)
└── components/
    ├── category-picker.css          # Standalone picker component (150 lines)
    └── modals.css                   # Generic modal styles (TBD)
```

**Decision Rules:**

**Add to EXISTING file if:**
- File has < 300 lines (standard limit)
- Style is related to existing content
- Component is a variant of existing pattern

**Create NEW file if:**
- New major feature requiring > 200 lines
- Standalone component used across multiple pages
- Different concern (e.g., print styles, email templates)

**Example Decision:**
```
Task: Style product variant selector

✅ ADD TO: products/variant-management.css (new file)
   - Product-specific feature
   - Will have ~150-200 lines
   - Used only in product forms

❌ DON'T ADD TO: admin/components.css
   - Already 350 lines (near limit)
   - Not admin-wide component
```

---

### Step 3: Define Styles Using Tokens

**RULE:** ALL values MUST use CSS Custom Properties.

**Color Usage:**
```css
/* ✅ CORRECT */
.new-component {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-default);
}

.new-component:hover {
    background: var(--bg-card-hover);
    border-color: var(--border-hover);
}

/* ❌ WRONG */
.new-component {
    background: #1e293b;  /* Use var(--bg-card) */
    color: #f8fafc;       /* Use var(--text-primary) */
}
```

**Spacing Usage:**
```css
/* ✅ CORRECT - Use standard spacing scale */
.card {
    padding: 24px;      /* Standard card padding */
    margin-bottom: 32px;
    gap: 16px;
}

/* ❌ WRONG - Arbitrary values */
.card {
    padding: 23px;      /* Why 23? Use 24px */
    margin-bottom: 31px; /* Use 32px */
}
```

**Z-Index Usage:**
```css
/* ✅ CORRECT - Use layer system */
.modal-overlay {
    z-index: var(--z-overlay, 200); /* Fallback to 200 */
}

/* OR use class */
.modal-overlay {
    /* No z-index property */
}
/* In Blade: */
<div class="modal-overlay layer-overlay">

/* ❌ WRONG */
.modal-overlay {
    z-index: 9999;  /* Magic number! */
}
```

---

### Step 4: Naming Conventions

**Component Naming Pattern:**
```
.[namespace]-[component]-[element]--[modifier]

Examples:
.enterprise-card               # Base component
.enterprise-card__header       # Element (BEM)
.enterprise-card__footer       # Element
.enterprise-card--warning      # Modifier (variant)
.enterprise-card--success      # Modifier

.btn-enterprise                # Namespace + component
.btn-enterprise-primary        # Component + variant
.btn-enterprise-sm             # Component + size
```

**Namespace Guidelines:**
- `enterprise-` = Reusable enterprise components
- `btn-enterprise-` = Button system
- `form-` = Form components
- `badge-enterprise-` = Badge system
- `tabs-enterprise` = Tab system
- `progress-enterprise` = Progress bars

**Element vs Modifier:**
```css
/* Element: Part of component (uses __) */
.card__header { }
.card__body { }
.card__footer { }

/* Modifier: Variation of component (uses --) */
.card--warning { }
.card--success { }
.card--large { }
```

---

### Step 5: Import in app.css (if new file)

**If you created a NEW CSS file**, add import to `app.css`:

```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Admin styles */
@import './admin/layout.css';
@import './admin/components.css';

/* Product styles */
@import './products/category-form.css';
@import './products/variant-management.css';  /* ← NEW IMPORT */

/* Component styles */
@import './components/category-picker.css';
@import './components/modals.css';            /* ← NEW IMPORT */
```

**IMPORTANT:** Order matters! Import base styles first, then specific components.

---

### Step 6: Build & Verify Locally

```bash
# Build Vite assets
npm run build
```

**Expected Output:**
```
vite v5.4.20 building for production...
✓ 425 modules transformed.
✓ built in 3.45s

public/build/assets/app-C7f3nhBa.css           155.23 kB │ gzip: 21.45 kB
public/build/assets/components-BVjlDskM.css     54.67 kB │ gzip:  8.12 kB
public/build/assets/layout-CBQLZIVc.css         12.34 kB │ gzip:  2.45 kB
public/build/assets/app-DqJ8kBx9.js            245.89 kB │ gzip: 78.23 kB

✓ built in 3.45s
```

**Verify Files Created:**
```bash
# Check manifest exists
ls public/build/.vite/manifest.json

# Check hashed assets
ls public/build/assets/
```

**Common Build Errors:**
```
Error: Cannot find module './products/variant-management.css'
Fix: Check file path, ensure import statement correct

Error: Unexpected token @
Fix: Check CSS syntax, remove any @ rules not supported

Error: Unknown property 'z-index-layer'
Fix: Use correct property name (z-index), use var(--z-layer)
```

---

### Step 7: Deploy to Production (Hostido)

**CRITICAL:** Vite regenerates hashes for **ALL files** on every build!

#### PowerShell Setup

```powershell
# Define SSH key variable
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
```

#### Upload ALL Assets

```powershell
# Upload ALL assets (NOT just new files!)
pscp -r -i $HostidoKey -P 64321 "public/build/assets/*" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/
```

**Why ALL assets?**
- Vite changes hashes for ALL files on every build
- Even unchanged CSS/JS files get new hashes
- Old hashes become invalid → 404 errors

#### Upload Manifest to ROOT (CRITICAL!)

```powershell
# Upload manifest to ROOT of public/build/ (NOT .vite/ subdirectory!)
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json
```

**Why ROOT location critical?**
- Laravel Vite helper looks in `public/build/manifest.json`
- `.vite/manifest.json` is IGNORED by Laravel
- Wrong location = `ViteException: Unable to locate file`

#### Clear Laravel Caches

```powershell
# Clear all caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
```

**Why clear caches?**
- View cache stores compiled Blade templates (with old asset hashes)
- Config cache may store old Vite settings
- Application cache may store asset paths

---

### Step 8: HTTP 200 Verification (MANDATORY!)

**BEFORE screenshot verification, verify ALL CSS files return HTTP 200!**

```powershell
# Get current CSS hashes from manifest
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && cat public/build/manifest.json | grep -o 'assets/[^\"]*\.css' | sort -u"

# Output example:
# assets/app-C7f3nhBa.css
# assets/layout-CBQLZIVc.css
# assets/components-BVjlDskM.css
# assets/category-form-DqJ8kBx9.css

# Test each file
curl -I "https://ppm.mpptrade.pl/public/build/assets/app-C7f3nhBa.css"
curl -I "https://ppm.mpptrade.pl/public/build/assets/layout-CBQLZIVc.css"
curl -I "https://ppm.mpptrade.pl/public/build/assets/components-BVjlDskM.css"
curl -I "https://ppm.mpptrade.pl/public/build/assets/category-form-DqJ8kBx9.css"
```

**Expected Response:**
```
HTTP/2 200
content-type: text/css; charset=UTF-8
content-length: 158954
```

**If 404 Found:**
```
HTTP/2 404
```

**Action: Re-upload missing files!**
```powershell
# Re-upload ALL assets (full deployment)
pscp -r -i $HostidoKey -P 64321 "public/build/assets/*" host379076@...
```

---

### Step 9: Screenshot Verification

**Use PPM Verification Tool:**

```bash
# Full verification (headless, all tabs)
node _TOOLS/full_console_test.cjs

# Specific URL
node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products/11033"

# Visible browser (debugging)
node _TOOLS/full_console_test.cjs --show

# Specific tab
node _TOOLS/full_console_test.cjs --tab="Cechy"
```

**Tool Output:**
```
Starting PPM Console Monitoring Tool...
✓ Navigated to: https://ppm.mpptrade.pl/admin/products/11033
✓ Waiting for Livewire initialization...
✓ Livewire initialized successfully
✓ Console logs captured (0 errors, 0 warnings)
✓ Screenshot saved: _TOOLS/screenshots/verification_full_2025-11-19T10-30-45.png
```

**What to Check:**
- ✅ Layout correct (no gigantic elements)
- ✅ Colors applied (not all black/white)
- ✅ Spacing looks professional (not cramped)
- ✅ Typography rendered correctly
- ✅ Components styled as expected
- ✅ No console errors (red text)
- ✅ Livewire initialized
- ✅ Body height reasonable (<10000px)

**Red Flags:**
- ❌ Gigantic icons/shapes (font-size:10rem+ = CSS not loaded)
- ❌ All black/white (Tailwind not loaded)
- ❌ Broken grid layout (layout.css missing)
- ❌ Body height >50000px (overflow issue)
- ❌ Console errors: `Failed to load resource: net::ERR_FILE_NOT_FOUND`

**If Issues Found:**
1. Check HTTP 200 for all CSS files (Step 8)
2. Clear browser cache (Ctrl+Shift+Delete)
3. Re-upload assets if 404 found
4. Clear Laravel caches again
5. Re-screenshot

---

## Pre-Deployment Checklist

**Code Quality:**
- [ ] Zero `style="..."` in Blade files
- [ ] Zero arbitrary Tailwind values (`z-[9999]`, `bg-[#...]`)
- [ ] All colors use CSS tokens (`var(--token)`)
- [ ] All spacing uses standard scale (16px, 24px, 32px)
- [ ] Z-index uses layer system (`.layer-*` or `var(--z-*)`)
- [ ] Component naming follows conventions (`.enterprise-*`, `.btn-enterprise-*`)
- [ ] New CSS file imported in `app.css` (if applicable)
- [ ] File size < 300 lines (or justified exception)

**Build:**
- [ ] `npm run build` successful
- [ ] `public/build/manifest.json` exists
- [ ] All hashed assets in `public/build/assets/`
- [ ] No build errors or warnings

**Deployment:**
- [ ] ALL assets uploaded (not just changed files!)
- [ ] Manifest uploaded to ROOT (`public/build/manifest.json`)
- [ ] Laravel caches cleared (view, cache, config)
- [ ] HTTP 200 verification for all CSS files
- [ ] Screenshot verification passed
- [ ] No console errors in browser DevTools
- [ ] Responsive design tested (mobile, tablet, desktop)

**Documentation:**
- [ ] New component added to `resources/components.md` (if reusable)
- [ ] New color token added to `resources/color-palette.md` (if applicable)
- [ ] Workflow documented if non-standard

---

## Troubleshooting Common Issues

### Issue: CSS Not Loading (404 Errors)

**Symptoms:**
- Browser console: `Failed to load resource: 404`
- Page has no styles (black/white only)
- Gigantic elements

**Diagnosis:**
```powershell
# Check manifest on production
plink ... -batch "cat domains/.../public/build/manifest.json | grep app.css"

# Expected: "file": "assets/app-C7f3nhBa.css"
# If different hash = incomplete deployment
```

**Fix:**
```powershell
# Re-upload ALL assets
pscp -r -i $HostidoKey -P 64321 "public/build/assets/*" host379076@...

# Re-upload manifest to ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json

# Clear caches
plink ... -batch "cd ... && php artisan view:clear && php artisan cache:clear"
```

---

### Issue: Old Styles Still Showing

**Symptoms:**
- Changes not visible on production
- Browser shows old CSS
- Manifest has new hash but browser loads old file

**Diagnosis:**
```bash
# Check browser DevTools Network tab
# Look for CSS file hash
# Compare with manifest.json hash
```

**Fix:**
```bash
# 1. Clear browser cache (hard refresh)
Ctrl + Shift + Delete → Clear cached images and files

# 2. Verify manifest uploaded correctly
plink ... -batch "cat domains/.../public/build/manifest.json | grep components"

# 3. Clear Laravel view cache (compiled Blade templates)
plink ... -batch "php artisan view:clear"

# 4. Test in incognito mode (fresh cache)
```

---

### Issue: Vite Manifest Not Found

**Symptoms:**
- `ViteException: Unable to locate file in Vite manifest`
- Page renders but error in logs

**Diagnosis:**
```powershell
# Check manifest location
plink ... -batch "ls -la domains/.../public/build/"

# Expected: manifest.json in ROOT
# NOT: .vite/manifest.json only
```

**Fix:**
```powershell
# Upload manifest to ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json

# Verify
plink ... -batch "cat domains/.../public/build/manifest.json | head -5"
```

---

### Issue: New CSS File Not Recognized

**Symptoms:**
- Styles from new file not applied
- No 404 error
- File exists on production

**Diagnosis:**
```css
/* Check app.css imports */
@import './products/new-file.css';  /* Is this line present? */
```

**Fix:**
```css
/* Add import to resources/css/app.css */
@import './products/new-file.css';

/* Rebuild */
npm run build

/* Re-upload ALL assets */
pscp -r ... public/build/assets/*
```

---

## Reference Documentation

**Related Guides:**
- `_DOCS/DEPLOYMENT_GUIDE.md` - Complete deployment procedures
- `_DOCS/FRONTEND_VERIFICATION_GUIDE.md` - Screenshot testing workflow
- `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md` - 404 troubleshooting
- `_ISSUES_FIXES/VITE_MANIFEST_NEW_CSS_FILES_ISSUE.md` - Manifest caching

**Skills:**
- `frontend-dev-guidelines` - Generic frontend rules
- `frontend-verification` - MANDATORY screenshot testing
- `hostido-deployment` - Production deployment automation

---

**Last Updated:** 2025-11-19
**Maintained By:** PPM DevOps Team
**Reference:** `_DOCS/PPM_Styling_Playbook.md` (section 8)
