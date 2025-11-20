# VARIANT MANUAL TESTING GUIDE

**Quick Start Guide for User Testing**

Version: 1.0
Date: 2025-10-31
Feature: Phase 6 Wave 2 - Variant CRUD Operations

---

## QUICK START (5 Minutes)

### Login

1. Open: https://ppm.mpptrade.pl/login
2. Email: `admin@mpptrade.pl`
3. Password: `Admin123!MPP`
4. Click "Zaloguj"

### Navigate to Test Product

1. Open: https://ppm.mpptrade.pl/admin/products/10969/edit
2. Click tab: **"Warianty Produktu"**
3. You should see:
   - ✅ "Dodaj Wariant" button (orange, top right)
   - ✅ Variants table with columns (SKU, Nazwa, Atrybuty, Status, Akcje)
   - ✅ Sections: Ceny Wariantów, Stany Magazynowe, Zdjęcia

---

## TEST 1: CREATE VARIANT (2 min)

**Objective:** Verify new variant creation

### Steps

1. Click **"Dodaj Wariant"** button
2. Modal opens with form
3. Fill fields:
   - **SKU:** `TEST_[YOUR_NAME]_001` (e.g., `TEST_KAMIL_001`)
   - **Nazwa:** `Test Variant [YOUR_NAME]`
   - **Aktywny:** ✅ (leave checked)
4. Click **"Zapisz Wariant"**

### Expected Result

- ✅ Modal closes
- ✅ Green success message
- ✅ New variant appears in table
- ✅ Table shows your SKU + name
- ✅ Status badge: "Aktywny" (green)

### If it FAILS

- 📸 Take screenshot of error
- 🔍 Open DevTools (F12) → Console tab
- 📋 Copy any red error messages
- ❌ Report error to agent

---

## TEST 2: EDIT VARIANT (2 min)

**Objective:** Verify variant editing

### Steps

1. Find your test variant in table (SKU: `TEST_[YOUR_NAME]_001`)
2. Click **"Edytuj"** button (pencil icon) in Akcje column
3. Modal opens with pre-filled data
4. Modify **Nazwa** field: Append " EDITED" to name
5. Click **"Zapisz Zmiany"**

### Expected Result

- ✅ Modal closes
- ✅ Green success message
- ✅ Table updates with new name (includes "EDITED")
- ✅ SKU remains unchanged

### If it FAILS

- Check if name changed in table
- If no change = save didn't work
- Report error with screenshot

---

## TEST 3: SET DEFAULT VARIANT (1 min)

**Objective:** Verify default variant flag

### Steps

1. Find your test variant in table
2. Click **"Ustaw jako Domyślny"** button (star icon)
3. Confirm action (if prompted)

### Expected Result

- ✅ Green success message
- ✅ Star icon filled/highlighted
- ✅ Badge "Domyślny" appears on variant row

### If it FAILS

- Check if visual indicator changed
- Report if no change after click

---

## TEST 4: DUPLICATE VARIANT (2 min)

**Objective:** Verify variant duplication

### Steps

1. Find your test variant in table
2. Click **"Duplikuj"** button (copy icon)
3. Confirm action

### Expected Result

- ✅ Green success message
- ✅ New variant created with SKU: `TEST_[YOUR_NAME]_001_COPY`
- ✅ Same name as original (+ " (Copy)" suffix)
- ✅ 2 variants in table now

### If it FAILS

- Check if duplicate appeared
- Check SKU has `_COPY` suffix
- Report if duplicate missing

---

## TEST 5: DELETE VARIANT (1 min)

**Objective:** Verify variant deletion (soft delete)

### Steps

1. Find duplicate variant (SKU: `TEST_[YOUR_NAME]_001_COPY`)
2. Click **"Usuń"** button (trash icon, red)
3. Confirmation modal appears
4. Click **"Tak, usuń"** (or confirm button)

### Expected Result

- ✅ Confirmation modal before delete
- ✅ Green success message after confirmation
- ✅ Variant removed from table
- ✅ Only original variant remains

### If it FAILS

- Check if variant still visible
- Report if no confirmation modal
- Report if delete doesn't work

---

## TEST 6: MANAGE PRICES (3 min)

**Objective:** Verify variant price management

### Steps

1. Scroll down to **"Ceny Wariantów per Grupa Cenowa"** section
2. Find your test variant row (SKU: `TEST_[YOUR_NAME]_001`)
3. Enter prices in grid:
   - **DETALICZNA:** `100.00`
   - **DEALER STANDARD:** `90.00`
   - **DEALER PREMIUM:** `85.00`
   - **WARSZTAT:** `95.00`
   - **WARSZTAT PREMIUM:** `88.00`
   - (continue for remaining groups)
4. Click **"Zapisz Ceny"** button

### Expected Result

- ✅ Green success message
- ✅ Values saved (reload page to verify)
- ✅ Grid shows entered prices

### If it FAILS

- Check if values persisted after reload
- Report if save button doesn't work
- Report if values not saved

---

## TEST 7: MANAGE STOCK (3 min)

**Objective:** Verify variant stock management

### Steps

1. Scroll to **"Stany Magazynowe Wariantów"** section
2. Find your test variant row
3. Enter stock quantities:
   - **MPPTRADE:** `50`
   - **Pitbike.pl:** `20`
   - **Cameraman:** `10`
   - **Otopit:** `5`
   - (continue for remaining warehouses)
4. Click **"Zapisz Stany"** button

### Expected Result

- ✅ Green success message
- ✅ Values saved (reload page to verify)
- ✅ Grid shows entered stock

### If it FAILS

- Check if values persisted
- Report if save fails
- Check for validation errors (negative stock?)

---

## TEST 8: UPLOAD IMAGE (2 min)

**Objective:** Verify variant image upload

### Steps

1. Scroll to **"Zdjęcia Wariantów"** section
2. Find your test variant row
3. Click **"Wybierz Pliki"** button
4. Select image file (JPG/PNG, <5MB)
5. Click **"Wyślij Zdjęcia"**

### Expected Result

- ✅ Upload progress indicator
- ✅ Green success message
- ✅ Image thumbnail appears in gallery
- ✅ Image visible in variant row

### If it FAILS

- Check file size (<5MB)
- Check file format (JPG/PNG only)
- Report upload errors
- Check if thumbnail displayed

---

## TESTING CHECKLIST

Copy this checklist and mark completed tests:

```
✅ TEST 1: CREATE VARIANT - PASSED
✅ TEST 2: EDIT VARIANT - PASSED
✅ TEST 3: SET DEFAULT VARIANT - PASSED
✅ TEST 4: DUPLICATE VARIANT - PASSED
✅ TEST 5: DELETE VARIANT - PASSED
✅ TEST 6: MANAGE PRICES - PASSED
✅ TEST 7: MANAGE STOCK - PASSED
✅ TEST 8: UPLOAD IMAGE - PASSED

OVERALL STATUS: ✅ ALL TESTS PASSED
```

Replace ✅ with ❌ if test failed, then provide details below:

```
FAILED TESTS:

TEST [NUMBER]: [NAME]
Error: [Description of error]
Screenshot: [Attach screenshot]
Console Errors: [Copy JS errors from DevTools]
Expected: [What should happen]
Actual: [What happened]
```

---

## COMMON ISSUES & FIXES

### Issue 1: Modal doesn't close after save

**Symptoms:**
- Click "Zapisz" but modal stays open
- No success message

**Possible Causes:**
- Validation error (check form for red error messages)
- Duplicate SKU (SKU already exists)
- Network error (check internet connection)

**Fix:**
1. Check for validation errors in modal
2. Try different SKU
3. Check browser console for errors
4. Refresh page and retry

---

### Issue 2: Variant not appearing in table

**Symptoms:**
- Success message displayed
- But variant not in table

**Possible Causes:**
- Page needs refresh
- Livewire reactivity issue
- Filter/search hiding variant

**Fix:**
1. Refresh page (F5)
2. Check if search/filter active
3. Scroll table (variant might be at bottom)
4. Check database (ask agent to verify)

---

### Issue 3: Prices/Stock not saving

**Symptoms:**
- Click "Zapisz" but values reset
- No success message

**Possible Causes:**
- Invalid format (non-numeric)
- Negative values (stock must be >= 0)
- Network timeout

**Fix:**
1. Use valid numbers only (e.g., 100.00, not "sto")
2. No negative stock
3. Try smaller batch (save fewer fields)
4. Check console for errors

---

### Issue 4: Image upload fails

**Symptoms:**
- Upload button doesn't work
- Error message: "File too large" or "Invalid format"

**Possible Causes:**
- File size > 5MB
- Wrong format (not JPG/PNG)
- Upload timeout

**Fix:**
1. Compress image (use online tools)
2. Convert to JPG/PNG
3. Try smaller image (<2MB)
4. Check internet speed

---

## BROWSER CONSOLE DEBUGGING

If any test fails, check browser console:

### How to Open Console

1. Press **F12** (or Right-click → "Inspect")
2. Click **"Console"** tab
3. Look for red error messages

### What to Report

Copy and paste:
- ❌ Red error messages (full text)
- ⚠️ Yellow warnings (if relevant)
- 🔍 Network errors (failed requests)

**Example Error:**
```
Uncaught TypeError: Cannot read property 'sku' of undefined
    at ProductForm.saveVariant (ProductForm.js:123)
```

---

## REPORTING RESULTS

After completing all tests, report to agent:

### If ALL TESTS PASSED

Send message:
```
✅ TESTING COMPLETED - ALL PASSED

All 8 tests completed successfully:
- ✅ CREATE VARIANT
- ✅ EDIT VARIANT
- ✅ SET DEFAULT
- ✅ DUPLICATE
- ✅ DELETE
- ✅ PRICES
- ✅ STOCK
- ✅ IMAGES

No errors encountered. Ready for Wave 3.
```

---

### If ANY TEST FAILED

Send message with details:
```
⚠️ TESTING COMPLETED - ISSUES FOUND

PASSED (X/8):
- ✅ [List passing tests]

FAILED (Y/8):
- ❌ TEST [NUMBER]: [NAME]
  Error: [Description]
  Screenshot: [Attach]
  Console: [Copy errors]

NEEDS FIXING BEFORE WAVE 3.
```

---

## NEXT STEPS

### After Testing

**IF ALL PASSED:**
1. Agent marks Phase 6 Wave 2 as ✅ COMPLETED
2. Plan updated with completion status
3. Proceed to **Phase 6 Wave 3** implementation

**IF ISSUES FOUND:**
1. Agent investigates errors
2. Fixes deployed to production
3. Re-test failed scenarios
4. Repeat until all pass

---

## ESTIMATED TIME

**Total testing time:** ~20-25 minutes

- TEST 1 (Create): 2 min
- TEST 2 (Edit): 2 min
- TEST 3 (Default): 1 min
- TEST 4 (Duplicate): 2 min
- TEST 5 (Delete): 1 min
- TEST 6 (Prices): 3 min
- TEST 7 (Stock): 3 min
- TEST 8 (Images): 2 min
- **Buffer:** 5 min (for screenshots, reporting)

---

## SUPPORT

If stuck or confused:

1. **Check:** Common Issues section above
2. **Screenshot:** Error messages + console
3. **Report:** Send details to agent
4. **Wait:** Agent will investigate and respond

**Contact:** Reply to agent with testing results

---

**Document Version:** 1.0
**Last Updated:** 2025-10-31
**Feature:** Phase 6 Wave 2 - Variant CRUD
**Status:** Ready for User Testing

---
