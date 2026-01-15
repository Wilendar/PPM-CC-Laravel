# Checkbox Reset Bug - Implementation Guide

**Status**: Ready for Implementation
**Complexity**: Low (2 files, ~10 minutes)
**Risk Level**: Minimal (isolated changes, no data loss)

---

## Quick Summary

### Problem
After bulk deleting categories, checkboxes remain "selected" on other rows.

### Root Cause
Missing `wire:key` attributes in Livewire list + improper array key handling in PHP

### Solution
1. Add `wire:key` identifiers to blade template
2. Fix array key management in PHP method

---

## Implementation Steps

### Option A: Automated (Recommended)

```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# Preview changes (safe, no modifications)
.\_TOOLS\apply_checkbox_reset_fix.ps1 -DryRun

# Apply fixes
.\_TOOLS\apply_checkbox_reset_fix.ps1
```

**Time**: 2 minutes

---

### Option B: Manual (Detailed)

#### Step 1: Update Blade Template (3 minutes)

**File**: `resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php`

**Change 1** - Find line with `<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"`

Replace:
```blade
<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"
       style="overflow: visible !important;"
       @if($viewMode === 'tree')
```

With:
```blade
<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"
       style="overflow: visible !important;"
       wire:key="category-list-{{ $viewMode }}"
       @if($viewMode === 'tree')
```

---

**Change 2** - Find `@forelse($categories as $category)` followed by `<tr class="transition-colors"`

Replace:
```blade
@forelse($categories as $category)
    <tr class="transition-colors category-row {{ ... }}"
        data-category-id="{{ $category->id }}"
```

With:
```blade
@forelse($categories as $category)
    <tr wire:key="category-row-{{ $category->id }}"
        class="transition-colors category-row {{ ... }}"
        data-category-id="{{ $category->id }}"
```

---

#### Step 2: Update PHP Method (3 minutes)

**File**: `app/Http/Livewire/Products/Categories/CategoryTree.php`

**Find method** (around line 481):
```php
public function toggleSelection(int $categoryId): void
{
    if (in_array($categoryId, $this->selectedCategories)) {
        $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
    } else {
        $this->selectedCategories[] = $categoryId;
    }
}
```

**Replace with**:
```php
/**
 * Select/deselect category for bulk operations
 *
 * @param int $categoryId
 */
public function toggleSelection(int $categoryId): void
{
    if (in_array($categoryId, $this->selectedCategories)) {
        // Remove category from selection and reset numeric keys
        $this->selectedCategories = array_values(
            array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
        );
    } else {
        // Add category to selection
        $this->selectedCategories[] = $categoryId;
    }
}
```

---

### Step 3: Build and Deploy (2 minutes)

```bash
# Build frontend assets
npm run build

# Verify build succeeded
# Expected: "✓ built in X.XXs"
```

### Step 4: Deploy to Production

Using your standard deployment method:
```powershell
# Option 1: Deploy script
.\_TOOLS\deploy.ps1

# Option 2: Manual pscp + cache clear
# Upload public/build/assets/*
# Then: php artisan cache:clear
```

### Step 5: Verify in Chrome DevTools (2 minutes)

```javascript
// Navigate to Admin → Categories (https://ppm.mpptrade.pl/admin/categories)

// 1. Open Chrome DevTools (F12) → Console

// 2. Verify structure
snapshot = await fetch(...) // Take snapshot
// Should NOT contain literal "wire:snapshot"

// 3. Test checkbox state
mcp__chrome-devtools__evaluate_script({
  function: "() => ({
    checkedBoxes: document.querySelectorAll('input[type=checkbox]:checked').length,
    totalRows: document.querySelectorAll('tbody tr').length
  })"
})
// Should return: {checkedBoxes: 0, totalRows: 7+}

// 4. Select 2-3 categories
// Verify: Bulk action bar shows correct count

// 5. Click "Delete selected"
// Confirm deletion

// 6. After deletion, verify: {checkedBoxes: 0} ✅
```

---

## Testing Checklist

### Pre-Deployment

- [ ] Read this guide completely
- [ ] Backup current files (git commit)
- [ ] Apply fixes (manual or automated)
- [ ] Run `npm run build` (verify success)

### Local Testing (Optional)

- [ ] Start: `php artisan serve`
- [ ] Navigate to `/admin/categories`
- [ ] Select multiple categories
- [ ] Delete them
- [ ] Verify checkboxes are clear

### Production Testing

- [ ] Deploy to production
- [ ] Wait 5 seconds for cache clear
- [ ] Navigate to `https://ppm.mpptrade.pl/admin/categories`
- [ ] Select 2-3 categories
- [ ] Open bulk delete confirmation
- [ ] Confirm deletion
- [ ] **VERIFY**: No checkboxes should be selected ✅
- [ ] Select different categories - should work normally
- [ ] Test switching between tree/flat view modes
- [ ] Verify bulk actions bar updates correctly

### Rollback Plan (if issues)

```bash
# If problems occur:
git revert HEAD  # Revert last commit
npm run build
./_TOOLS/deploy.ps1
```

---

## Technical Details

### What Changed

**Blade Template**:
- Added `wire:key="category-list-{{ $viewMode }}"` to `<tbody>`
- Added `wire:key="category-row-{{ $category->id }}"` to each `<tr>`

**PHP Method**:
- Changed from: `array_diff()` (creates sparse keys)
- Changed to: `array_filter()` + `array_values()` (clean sequential keys)

### Why This Fixes It

**Before**: Livewire tracked list items by array position [0, 1, 2, ...]
- When you delete item at position 1, item at position 2 shifts to position 1
- Checkbox HTML stays at position 1, but now represents different category
- User sees wrong checkboxes selected

**After**: Livewire tracks list items by unique key (category ID)
- When you delete category 5, its key `category-row-5` is removed
- Other categories keep their keys intact
- Checkboxes correctly represent their categories

---

## FAQ

### Q: Will this affect performance?
**A**: No, it actually improves performance slightly by allowing Livewire to make minimal DOM updates instead of full re-renders.

### Q: Do I need to clear cache?
**A**: Yes, after deployment run: `php artisan cache:clear`

### Q: Will this break existing functionality?
**A**: No, this is a pure bug fix with no behavior changes (only fixes broken behavior).

### Q: What about other bulk operations (activate/deactivate)?
**A**: They will also benefit from this fix. Checkboxes will properly clear after any bulk operation.

### Q: Can I revert if something breaks?
**A**: Yes, simply `git revert` and redeploy. Changes are isolated to two methods.

---

## Success Criteria

After deployment, you should be able to:

1. ✅ Select multiple categories
2. ✅ Delete them via bulk action
3. ✅ See NO checkboxes selected after deletion
4. ✅ Continue selecting other categories normally
5. ✅ No JavaScript console errors (F12)
6. ✅ No `wire:snapshot` artifacts in DOM

---

## Support Docs

- **Full Explanation**: `_DOCS/CHECKBOX_RESET_BUG_EXPLANATION.md`
- **Copy-Paste Fixes**: `_DOCS/CHECKBOX_RESET_BUG_FIXES.md`
- **Agent Report**: `_AGENT_REPORTS/livewire_specialist_CHECKBOX_RESET_BUG_FIX.md`
- **Automation Script**: `_TOOLS/apply_checkbox_reset_fix.ps1`

---

## Related Issues

- Pattern: `_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md`
- Similar component: ETAP05d Category System - Tree rendering

---

## Estimated Time

| Task | Duration |
|------|----------|
| Automated fix + build | 5 min |
| Manual fix + build | 10 min |
| Deployment | 2 min |
| Testing | 5 min |
| **Total** | **12-17 min** |

---

**Status**: ✅ Ready to implement
**Last Updated**: 2025-12-09
**Author**: Livewire Specialist Agent
