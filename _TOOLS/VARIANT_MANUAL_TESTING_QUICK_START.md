# VARIANT MANUAL TESTING - QUICK START GUIDE

**Time Required:** ~10 minutes
**Purpose:** Verify Variant CRUD functionality + Checkbox persistence

---

## SETUP (1 min)

### 1. Open DevTools Console
- Press **F12** in browser
- Click **Console** tab
- Keep open during entire test

### 2. Navigate to test product
```
URL: https://ppm.mpptrade.pl/admin/products/11018/edit
```

### 3. Open testing checklist
```
File: _TOOLS/VARIANT_MANUAL_TESTING_CHECKLIST.md
```

---

## TESTING WORKFLOW (8 min)

Follow checklist in order:

1. **Scenario #1** - Checkbox Check → Add Variant (~2 min)
2. **Scenario #2** - Checkbox Uncheck → Conversion Modal (~2 min)
3. **Scenario #3** - Edit Variant (~1.5 min)
4. **Scenario #4** - Delete Variant (Last) (~1 min)
5. **Scenario #5** - Multiple Variants (~2 min)
6. **Scenario #6** - SKU Uniqueness Validation (~1 min)
7. **Scenario #7** - Prices & Stock Per Warehouse (~1.5 min)
8. **Scenario #8** - Console Error Check (continuous)

**Total:** ~11 min (including setup)

---

## AUTOMATED VERIFICATION (OPTIONAL)

### Run console verification:
```bash
node _TOOLS/full_console_test.cjs 'https://ppm.mpptrade.pl/admin/products/11018/edit' --tab=Warianty --verify-variants --show
```

**Output:**
- Console monitoring
- Screenshot capture
- Variant CRUD checks (7 checks)
- Overall PASS/FAIL status

### Capture screenshots only:
```bash
node _TOOLS/screenshot_variant_test.cjs 11018
```

**Output:**
- `screenshots/variant_test_01_initial_*.png`
- `screenshots/variant_test_02_warianty_tab_*.png`
- `screenshots/variant_test_03_checkbox_checked_*.png`
- `screenshots/variant_test_04_add_button_*.png`
- `screenshots/variant_test_05_variant_list_*.png`

---

## EXPECTED RESULTS

### ✅ ALL PASS means:
- Checkbox state persists after page reload
- Variants CRUD operations work (add, edit, delete)
- Conversion modal works (uncheck → delete variants)
- SKU validation works (duplicate rejected)
- Prices & stock per warehouse persist
- ZERO console errors

### ❌ ANY FAIL means:
- Report to developer with:
  - Failed scenario number
  - Steps to reproduce
  - Screenshot from DevTools
  - Console errors

---

## POST-TEST CLEANUP (1 min)

1. Delete all test variants (Variant B, C, D)
2. Uncheck "Produkt ma warianty"
3. Save product
4. Verify product returned to initial state

---

## SUMMARY REPORT

After testing, fill in:

```
**Test Completed:** [Date and time]
**Scenarios Passed:** [X / 8]
**Scenarios Failed:** [X / 8]

**Failed Scenarios:**
- Scenario #X - [Problem description]

**Console Errors:**
- [List errors if any]

**Next Steps:**
- IF all pass → Approve debug log cleanup
- IF failures → Developer fixes issue
```

---

## TOOLS REFERENCE

| Tool | Purpose | Time |
|------|---------|------|
| `VARIANT_MANUAL_TESTING_CHECKLIST.md` | Step-by-step testing guide | 10 min |
| `full_console_test.cjs --verify-variants` | Automated verification (7 checks) | 30 sec |
| `screenshot_variant_test.cjs` | Screenshot automation | 30 sec |

---

## TROUBLESHOOTING

### "Cannot find Warianty tab"
- Wait for page to load fully (5 sec)
- Check if logged in (admin@mpptrade.pl)
- Refresh page (Ctrl+F5)

### "Checkbox not found"
- Check if on correct product (11018)
- Verify Warianty tab is clicked
- Try different browser (Chrome/Edge)

### "Modal doesn't open"
- Check console for errors
- Verify Livewire initialized (`window.Livewire` exists)
- Clear browser cache

### "Screenshots not saved"
- Check `_TOOLS/screenshots/` directory exists
- Run with admin permissions
- Check disk space

---

**Questions?** Contact: developer team

**Last Updated:** 2025-11-12
**Version:** 1.0
