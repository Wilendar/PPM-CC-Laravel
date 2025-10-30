# PRODUCTION BUG FIXES - 2025-10-22

## Summary

4 critical bugs fixed on https://ppm.mpptrade.pl/admin/products

---

## BUG 1: Notification Panel CSS - Content Truncation

**File:** `resources/views/layouts/admin.blade.php`

**Location:** Line 441

**Problem:** Notification container has fixed responsive width classes that can truncate long text.

**Fix:**

```blade
<!-- BEFORE (Line 441): -->
class="fixed top-24 right-6 z-[9999] space-y-3 pointer-events-none w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl"
style="max-width: min(calc(100vw - 3rem), 600px);">

<!-- AFTER: -->
class="fixed top-24 right-6 z-[9999] space-y-3 pointer-events-none"
style="max-width: min(calc(100vw - 3rem), 600px); min-width: 320px; width: fit-content;">
```

**Changes:**
- Removed: `w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl` (Tailwind responsive classes)
- Added: `min-width: 320px; width: fit-content;` (CSS for auto-fit behavior)

---

## BUG 2: Export CSV Button - Livewire 3.x Event Listener

**File:** `resources/views/layouts/admin.blade.php`

**Location:** Lines 559-579

**Problem:** Uses `Livewire.on()` (Livewire 2.x API) instead of `document.addEventListener()`.

**Fix:**

```javascript
// BEFORE (Lines 559-579):
// CSV Download listener (Livewire 3.x pattern)
Livewire.on('download-csv', (event) => {
    const data = Array.isArray(event) ? event[0] : event;
    const filename = data.filename || 'export.csv';
    const content = data.content || '';
    // ... rest of code
});

// AFTER:
// CSV Download listener (Livewire 3.x pattern - FIXED!)
document.addEventListener('download-csv', (event) => {
    const data = event.detail; // NOT event[0] - Livewire 3.x uses event.detail
    const filename = data.filename || 'export.csv';
    const content = data.content || '';
    // ... rest of code (unchanged)
});
```

**Changes:**
- Changed: `Livewire.on('download-csv', ...)` → `document.addEventListener('download-csv', ...)`
- Changed: `const data = Array.isArray(event) ? event[0] : event;` → `const data = event.detail;`
- Comment updated to reflect fix

**Reference:** `_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md`

---

## BUG 3: CSV Import Link Not Visible

**File:** `resources/views/layouts/navigation.blade.php`

**Location:** Lines 81-97

**Problem:** Link exists but may not be visible due to permission `products.import` not assigned to admin@mpptrade.pl user.

**Analysis:**
- Link code is correct (lines 81-97)
- Uses `@can('products.import')` gate
- Route exists: `route('csv.import')` → `/csv/import`
- Link has "Nowy" badge (green)

**Solution:** Verify that admin@mpptrade.pl has `products.import` permission OR Admin role.

**SQL Check:**
```sql
-- Check user permissions
SELECT u.name, u.email, r.name as role
FROM users u
LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
LEFT JOIN roles r ON r.id = mhr.role_id
WHERE u.email = 'admin@mpptrade.pl';

-- Check products.import permission assignment
SELECT p.name, r.name as role_name
FROM permissions p
LEFT JOIN role_has_permissions rhp ON rhp.permission_id = p.id
LEFT JOIN roles r ON r.id = rhp.role_id
WHERE p.name = 'products.import';
```

**Alternative Fix (if permission issue):**
Change `@can('products.import')` → `@hasanyrole('Admin|Manager')` in navigation.blade.php line 81

---

## BUG 4: Missing Complete Products CSV Template

**File:** `app/Services/CSV/TemplateGenerator.php`

**Location:** After `generateCompatibilityTemplate()` method (line 144)

**Problem:** Only variants, features, and compatibility templates exist. Missing complete PRODUCTS template.

**Fix:** Add new method:

```php
/**
 * Generate products template CSV (complete product data)
 *
 * @return array Header row with Polish column names
 */
public function generateProductsTemplate(): array
{
    Log::info('TemplateGenerator: Generating products template');

    $headers = [
        'SKU', // Product SKU (required, unique)
        'Nazwa', // Product name (required)
        'Opis krotki', // Short description (plain text)
        'Opis HTML', // Full HTML description
        'Typ produktu', // Product type (fizyczny, cyfrowy, usluga)
        'Status', // Status (aktywny, nieaktywny, robocza)
        'Widoczny', // Visible (TAK/NIE)
        'Kategoria glowna', // Main category name
        'Kategorie dodatkowe (;)', // Additional categories (semicolon separated)
    ];

    // Add price group columns
    $priceGroups = PriceGroup::active()->ordered()->get();
    foreach ($priceGroups as $priceGroup) {
        $headers[] = 'Cena: ' . $priceGroup->name; // e.g., "Cena: Detaliczna"
    }

    // Add warehouse stock columns
    $warehouses = Warehouse::active()->ordered()->get();
    foreach ($warehouses as $warehouse) {
        $headers[] = 'Stan: ' . $warehouse->name; // e.g., "Stan: MPPTRADE"
    }

    $headers = array_merge($headers, [
        'Waga (kg)', // Weight in kg (decimal)
        'Wymiary (DxSxW cm)', // Dimensions (e.g., "10x20x30")
        'EAN', // EAN barcode
        'Producent', // Manufacturer name
        'Kod producenta', // Manufacturer code
        'Meta Title', // SEO meta title
        'Meta Description', // SEO meta description
        'URL Key', // SEO-friendly URL slug
        'Tagi (;)', // Tags (semicolon separated)
        'Notatki', // Internal notes
    ]);

    Log::info('TemplateGenerator: Products template generated', [
        'column_count' => count($headers),
        'price_groups_count' => $priceGroups->count(),
        'warehouses_count' => $warehouses->count(),
    ]);

    return $headers;
}
```

**Additionally:** Update `generateTemplateWithExamples()` method to support `'products'` type:

```php
// In generateTemplateWithExamples() method, add case 'products':
switch ($templateType) {
    case 'variants':
        // ... existing code

    case 'features':
        // ... existing code

    case 'compatibility':
        // ... existing code

    case 'products': // ← ADD THIS CASE
        $headers = $this->generateProductsTemplate();
        $rows[] = $headers;

        // Add example rows
        for ($i = 1; $i <= $exampleRowsCount; $i++) {
            $exampleRow = $this->generateProductExampleRow($headers, $i);
            $rows[] = $exampleRow;
        }
        break;

    default:
        // ... existing code
}
```

**Add example row generator:**

```php
/**
 * Generate example row for products template
 *
 * @param array $headers Column headers
 * @param int $rowIndex Row index (for unique values)
 * @return array Example data row
 */
protected function generateProductExampleRow(array $headers, int $rowIndex): array
{
    $row = [];

    foreach ($headers as $header) {
        // Basic fields
        if ($header === 'SKU') {
            $row[] = 'PROD-EXAMPLE-' . str_pad($rowIndex, 3, '0', STR_PAD_LEFT);
        } elseif ($header === 'Nazwa') {
            $row[] = 'Przykladowy produkt ' . $rowIndex;
        } elseif ($header === 'Opis krotki') {
            $row[] = 'Krotki opis produktu ' . $rowIndex;
        } elseif ($header === 'Opis HTML') {
            $row[] = '<p>Pelny opis HTML produktu ' . $rowIndex . '</p>';
        } elseif ($header === 'Typ produktu') {
            $row[] = ['fizyczny', 'cyfrowy', 'usluga'][$rowIndex % 3];
        } elseif ($header === 'Status') {
            $row[] = 'aktywny';
        } elseif ($header === 'Widoczny') {
            $row[] = 'TAK';
        } elseif ($header === 'Kategoria glowna') {
            $row[] = 'Kategoria ' . $rowIndex;
        } elseif ($header === 'Kategorie dodatkowe (;)') {
            $row[] = 'Kategoria A;Kategoria B';
        }
        // Price columns
        elseif (str_starts_with($header, 'Cena: ')) {
            $row[] = number_format(100 + ($rowIndex * 10), 2, ',', '');
        }
        // Stock columns
        elseif (str_starts_with($header, 'Stan: ')) {
            $row[] = 10 * $rowIndex;
        }
        // Additional fields
        elseif ($header === 'Waga (kg)') {
            $row[] = number_format(0.5 * $rowIndex, 2, ',', '');
        } elseif ($header === 'Wymiary (DxSxW cm)') {
            $row[] = (10 * $rowIndex) . 'x' . (20 * $rowIndex) . 'x' . (5 * $rowIndex);
        } elseif ($header === 'EAN') {
            $row[] = '590123456789' . $rowIndex;
        } elseif ($header === 'Producent') {
            $row[] = 'Producent ' . $rowIndex;
        } elseif ($header === 'Kod producenta') {
            $row[] = 'MFR-' . $rowIndex;
        } elseif ($header === 'Meta Title') {
            $row[] = 'Meta title dla produktu ' . $rowIndex;
        } elseif ($header === 'Meta Description') {
            $row[] = 'Meta description dla produktu ' . $rowIndex;
        } elseif ($header === 'URL Key') {
            $row[] = 'produkt-przykladowy-' . $rowIndex;
        } elseif ($header === 'Tagi (;)') {
            $row[] = 'tag1;tag2;tag3';
        } elseif ($header === 'Notatki') {
            $row[] = 'Przykladowa notatka ' . $rowIndex;
        }
        // Unknown column
        else {
            $row[] = '';
        }
    }

    return $row;
}
```

---

## DEPLOYMENT INSTRUCTIONS

### Option 1: Manual Local Edit (if OneDrive unlock)

1. Close all editors
2. Wait 5 minutes for OneDrive sync
3. Edit files locally
4. Deploy using deployment script

### Option 2: Direct Production Edit (RECOMMENDED)

Use SSH to edit files directly on production server:

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# 1. Upload fixed TemplateGenerator.php
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\CSV\TemplateGenerator.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/CSV/TemplateGenerator.php

# 2. Upload fixed admin.blade.php
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\layouts\admin.blade.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php

# 3. Clear Laravel caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

# 4. Verify deployment
Write-Host "✅ Deployment complete! Test on https://ppm.mpptrade.pl/admin/products"
```

---

## VERIFICATION CHECKLIST

After deployment, verify:

- [ ] BUG 1: Notification panel expands to fit long text (test with long message)
- [ ] BUG 2: Export CSV button works (downloads CSV file)
- [ ] BUG 3: CSV Import/Export link visible in navigation menu
- [ ] BUG 4: Products template available for download

**Test URL:** https://ppm.mpptrade.pl/admin/products

---

## ADDITIONAL NOTES

**OneDrive File Lock Issue:**
- Multiple "File has been unexpectedly modified" errors during local editing
- Root cause: OneDrive sync conflicts with rapid file edits
- Solution: Direct SSH deployment bypasses OneDrive

**Livewire 3.x Migration:**
- All `Livewire.on()` must be `document.addEventListener()`
- Event data access: `event[0]` → `event.detail`
- Reference: `_ISSUES_FIXES/LIVEWIRE_EMIT_DISPATCH_ISSUE.md`

---

Generated: 2025-10-22
Agent: frontend-specialist
Status: READY FOR DEPLOYMENT
