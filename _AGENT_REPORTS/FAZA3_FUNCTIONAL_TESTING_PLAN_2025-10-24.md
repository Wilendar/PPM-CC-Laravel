# FAZA 3: FUNCTIONAL BUTTONS TESTING PLAN

**Date:** 2025-10-24
**Agent:** Coordination (Main)
**Phase:** ETAP_05c FAZA 3 - Functional Buttons Testing
**URL:** https://ppm.mpptrade.pl/admin/features/vehicles

---

## 📋 TEST PLAN

### ✅ PRE-VERIFICATION: Code Analysis

**Component:** `app/Http/Livewire/Admin/Features/VehicleFeatureManagement.php`

**Implemented Methods:**
1. ✅ editTemplate(int $templateId) - Lines 231-248
2. ✅ deleteTemplate(int $templateId) - Lines 253-294
3. ✅ saveTemplate() - Lines 299-361
4. ✅ addFeatureToTemplate(string $featureName) - Lines 388-411
5. ✅ bulkAssign() - Lines 544-602

**Blade Bindings:** `resources/views/livewire/admin/features/vehicle-feature-management.blade.php`
- ✅ Edit: `wire:click="editTemplate({{ $template->id }})"`
- ✅ Delete: `wire:click="deleteTemplate({{ $template->id }})"`
- ✅ Add Feature: `wire:click="addFeatureToTemplate('{{ $feature['name'] }}')"`
- ✅ Save: `wire:click="saveTemplate"`
- ✅ Bulk Assign: `wire:click="bulkAssign"`

---

## 🧪 FUNCTIONAL TESTS

### TEST 1: Edit Template Button
**Method:** `editTemplate(int $templateId)`
**Steps:**
1. Click "Edit" button on any template card
2. Verify modal opens with template editor
3. Verify templateName is populated
4. Verify templateFeatures array is populated
5. Verify feature list is displayed in modal

**Expected:**
- Modal opens (`showTemplateEditor = true`)
- Form populated with template data from database
- Edit mode active (`editingTemplateId` set)

**Success Criteria:**
- ✅ Modal visible
- ✅ Template name field shows correct value
- ✅ Features table shows template features
- ✅ No console errors

---

### TEST 2: Delete Template Button (Predefined)
**Method:** `deleteTemplate(int $templateId)`
**Steps:**
1. Click "Del" button on predefined template (Pojazdy Elektryczne or Pojazdy Spalinowe)
2. Verify error message appears

**Expected:**
- Error: "Cannot delete predefined templates."
- Template NOT deleted from database
- User remains on page

**Success Criteria:**
- ✅ Error message displayed
- ✅ Template still visible after error
- ✅ No database changes

---

### TEST 3: Delete Template Button (Custom)
**Method:** `deleteTemplate(int $templateId)`
**Steps:**
1. Create custom template first (via "Dodaj Template" + Save)
2. Click "Del" button on newly created custom template
3. Verify success message
4. Verify template removed from UI

**Expected:**
- Success: "Template deleted successfully."
- Template removed from database
- UI refreshed (custom templates reloaded)

**Success Criteria:**
- ✅ Success flash message
- ✅ Template no longer visible in list
- ✅ Database record deleted

**Note:** Currently NO confirmation modal (TODO FUTURE)

---

### TEST 4: Create New Template
**Method:** `saveTemplate()` (create mode)
**Steps:**
1. Click "Dodaj Template" button
2. Fill in template name (e.g., "Test Template")
3. Add features using "Add Feature from Library"
4. Click "Zapisz" button
5. Verify template appears in custom templates section

**Expected:**
- Validation passes (name required, features required min:1)
- Database insert successful
- Flash message: "Template saved successfully."
- Modal closes
- New template visible in UI

**Success Criteria:**
- ✅ New template created in DB
- ✅ Success flash message
- ✅ Modal closed
- ✅ Template visible with correct name/features

---

### TEST 5: Edit Existing Template
**Method:** `saveTemplate()` (update mode)
**Steps:**
1. Click "Edit" on custom template
2. Change template name
3. Add/remove features
4. Click "Zapisz"
5. Verify changes persisted

**Expected:**
- Database update successful
- Flash message: "Template saved successfully."
- Modal closes
- Updated template visible with changes

**Success Criteria:**
- ✅ Template updated in DB
- ✅ Success flash message
- ✅ Changes visible immediately

---

### TEST 6: Edit Predefined Template (Should Fail)
**Method:** `saveTemplate()` (update mode with is_predefined=true)
**Steps:**
1. Click "Edit" on predefined template (Pojazdy Elektryczne)
2. Change name
3. Click "Zapisz"
4. Verify error message

**Expected:**
- Error: "Cannot edit predefined templates"
- Database NOT updated
- Modal remains open with error

**Success Criteria:**
- ✅ Error message displayed
- ✅ No database changes
- ✅ Predefined template unchanged

---

### TEST 7: Add Feature to Template
**Method:** `addFeatureToTemplate(string $featureName)`
**Steps:**
1. Open template editor (Edit or Create new)
2. Expand Feature Library
3. Click "+" button next to any feature (e.g., "Engine Type")
4. Verify feature added to template features table

**Expected:**
- Feature added to `$templateFeatures` array
- Flash message: "Feature 'Engine Type' added to template."
- Feature row appears in modal table

**Success Criteria:**
- ✅ New row in features table
- ✅ Feature data populated correctly
- ✅ Flash message confirmation

---

### TEST 8: Remove Feature from Template
**Method:** `removeFeature(int $index)`
**Steps:**
1. Open template editor with existing features
2. Click remove button (X) on any feature row
3. Verify feature removed from table

**Expected:**
- Feature removed from `$templateFeatures` array
- Table row disappears
- Array re-indexed

**Success Criteria:**
- ✅ Row removed from UI
- ✅ No console errors
- ✅ Other features remain intact

---

### TEST 9: Bulk Assign - All Vehicles
**Method:** `bulkAssign()`
**Steps:**
1. Click "Zastosuj Template do Produktów" button
2. Select template from dropdown
3. Select scope: "All Vehicles"
4. Select action: "Replace Features" OR "Add Features"
5. Click "Zastosuj" button
6. Verify success message with count

**Expected:**
- Query: `Product::where('is_vehicle', true)->get()`
- FeatureManager service called for each product
- Flash message: "Template applied to X products successfully."
- Modal closes

**Success Criteria:**
- ✅ Success flash message with count
- ✅ Modal closed
- ✅ Database updated (verify via ProductForm later)

---

### TEST 10: Bulk Assign - By Category
**Method:** `bulkAssign()`
**Steps:**
1. Click "Zastosuj Template do Produktów"
2. Select template
3. Select scope: "By Category"
4. Select category from dropdown
5. Verify products count updates dynamically
6. Click "Zastosuj"
7. Verify only products from selected category updated

**Expected:**
- Query: `Product::where('is_vehicle', true)->where('category_id', $id)->get()`
- Dynamic count display
- Only matching products updated

**Success Criteria:**
- ✅ Products count accurate
- ✅ Success message with correct count
- ✅ Only category products updated

---

### TEST 11: Feature Library Search
**Method:** `getFilteredFeatureLibraryProperty()`
**Steps:**
1. Open Feature Library sidebar
2. Type search query in "Szukaj cechy" input
3. Verify filtered results display

**Expected:**
- Real-time filtering as user types
- Only matching features visible
- Groups with no matches hidden

**Success Criteria:**
- ✅ Search filters correctly
- ✅ Results update dynamically
- ✅ Empty search shows all features

---

## 📊 TEST EXECUTION

### Environment
- **URL:** https://ppm.mpptrade.pl/admin/features/vehicles
- **Database:** Production (ppm.mpptrade.pl)
- **Authentication:** Disabled (development mode)

### Test Sequence
1. Visual verification (screenshots)
2. Laravel logs check (errors/warnings)
3. Database state verification (before/after)
4. Flash messages verification
5. UI state verification (modals open/close)

---

## ✅ PASS CRITERIA

**All tests must pass:**
- No console JavaScript errors
- No Laravel exceptions in logs
- Flash messages display correctly
- Database changes persist correctly
- UI updates reflect database state
- Modals open/close correctly
- Livewire wire:click events fire

---

## 📝 NOTES

**Discovered During Analysis:**
- Lines 25, 28, 43, 46 in Blade have hardcoded template IDs (1, 2)
- Should be replaced with `@foreach($predefinedTemplates as $template)` loop
- Current implementation works but not DRY

**TODO FUTURE (from code comments):**
- deleteTemplate(): Check usage_count before deletion
- Confirmation modal for delete action
- Progress indicator for bulk assign (large product counts)

---

**Test Plan Created:** 2025-10-24
**Ready for Execution:** YES
**Estimated Test Time:** 30-45 minutes
