# RAPORT KOORDYNACJI: Shop Data Sync Issue Implementation

**Data:** 2025-11-06 12:00
**Agent:** architect (Planning Manager & Project Plan Keeper)
**Zadanie:** Koordynacja implementacji Shop Data Sync Fix
**Model:** Claude Sonnet 4.5
**Issue Source:** `_ISSUES_FIXES/SHOP_DATA_SYNC_ISSUE.md` (created 2025-11-06 11:46)

---

## STATUS WYKONANIA

**⚠️ AGENTS NOT STARTED YET**

Analiza wykazała że:
- Issue został udokumentowany DZISIAJ (2025-11-06 11:46)
- Brak raportów agentów w `_AGENT_REPORTS/`
- Zadanie NIE ZOSTAŁO jeszcze zdelegowane do subagentów

**Niniejszy raport zawiera:**
1. Plan delegacji dla 3 agentów (frontend, livewire, laravel)
2. Integration verification checklist
3. Deployment sequence
4. Risk assessment
5. Instrukcje dla orchestratora

---

## PROBLEM SUMMARY

### Root Cause
Formularz produktów (`ProductForm.php`) **pobiera** dane z PrestaShop API poprawnie, ale **NIE POKAZUJE** ich w UI. Zamiast tego:
- UI pokazuje inherited default values (products.name)
- Brak porównania PPM vs PrestaShop
- Brak conflict indicators
- Status "zgodne" BEZ weryfikacji z PrestaShop
- Użytkownik widzi iluzję zgodności

### User Impact
- User zmienił nazwę produktu w "Dane domyślne": `Test` → `[ZMIANA] Test`
- Shop TAB pokazuje `[ZMIANA] Test` (inherited)
- PrestaShop ma nadal `Test` (stare dane)
- Sync status = "Oczekuje" (correct, waiting for sync)
- UI pokazuje "zgodne" (INCORRECT - should show conflict!)

### Business Impact
- **HIGH SEVERITY** - Incorrect UI state leading to user confusion
- User cannot see actual PrestaShop data
- Cannot make informed decisions (Keep PPM vs Use PrestaShop)
- Sync conflicts hidden from user

---

## SOLUTION DESIGN (6 PHASES)

### Phase 1: UI Comparison Panel (frontend-specialist)
**Scope:** Add visual comparison panel in shop TAB
- Display: PPM value vs PrestaShop value side-by-side
- Show conflict indicator when values differ
- Show "zgodne" only when values actually match
- Add conflict resolution buttons

**Files:**
- `resources/views/livewire/products/management/product-form.blade.php` (add comparison panel)
- `resources/css/admin/components.css` (add CSS classes)

**Estimated Time:** 1-1.5h

---

### Phase 2: Conflict Resolution Methods (livewire-specialist)
**Scope:** Add methods to handle user conflict resolution
- `usePPMData(shopId)` - Keep PPM value, mark for sync to PrestaShop
- `usePrestaShopData(shopId)` - Pull PrestaShop value to PPM, update form

**Files:**
- `app/Http/Livewire/Products/Management/ProductForm.php` (add 2 methods)

**Estimated Time:** 1h

---

### Phase 3: Button Refactoring (laravel-expert)
**Scope:** Fix "Zapisz zmiany" button behavior
- **Default mode:** Save locally ONLY (NO sync job)
- **Shop mode:** Save + create sync job ONLY for THIS shop

**Current behavior (INCORRECT):**
- Always creates sync job for ALL shops

**New behavior:**
- Refactor `ProductFormSaver::save()`
- Split into `saveDefaultMode()` and `saveShopMode(shopId)`
- Create job ONLY for specific shop in shop mode

**Files:**
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`

**Estimated Time:** 1h

---

### Phase 4: Immediate Sync Button (livewire-specialist)
**Scope:** Refactor "Synchronizuj sklepy" button
- Pull current data from PrestaShop → PPM (immediate, forced)
- Refresh UI without closing form
- Work independently from background jobs
- No "trwa aktualizacja" blocking message

**Files:**
- `app/Http/Livewire/Products/Management/ProductForm.php` (add `syncShopsImmediate()` method)
- `resources/views/livewire/products/management/product-form.blade.php` (update button binding)

**Estimated Time:** 1h

---

### Phase 5: Background Pull Job (laravel-expert)
**Scope:** Cyclic PrestaShop → PPM data pull
- Create `PullProductsFromPrestaShop` job
- Schedule every 6 hours for all active shops
- Update `product_shop_data` with fresh PrestaShop values

**Files:**
- `app/Jobs/PullProductsFromPrestaShop.php` (new file)
- `app/Console/Kernel.php` (add schedule)

**Estimated Time:** 1h

---

### Phase 6: Database Migration (laravel-expert)
**Scope:** Add `last_pulled_at` timestamp column
- Track when PrestaShop data was last pulled to PPM
- Differentiate from `last_sync_at` (PPM → PrestaShop push)

**Files:**
- `database/migrations/2025_11_06_XXXXXX_add_last_pulled_at_to_product_shop_data.php` (new)

**Estimated Time:** 15 min

---

## DELEGATION PLAN

### Agent 1: frontend-specialist

**Task:** Phase 1 - UI Comparison Panel

**Priority:** CRITICAL (user-facing, blocks other phases)

**Deliverables:**
1. Comparison panel in `product-form.blade.php`
2. CSS classes in `resources/css/admin/components.css`
3. Screenshot verification with `_TOOLS/full_console_test.cjs`
4. Report: `_AGENT_REPORTS/frontend_specialist_shop_comparison_ui_2025-11-06_REPORT.md`

**Integration Points:**
- Must use `$loadedShopData[$activeShopId]` (already fetched by ProductForm)
- Must call `wire:click="usePPMData({{ $activeShopId }})"` (Phase 2 dependency)
- Must call `wire:click="usePrestaShopData({{ $activeShopId }})"` (Phase 2 dependency)

**Requirements:**
- NO inline styles (use dedicated CSS classes)
- NO arbitrary Tailwind values
- Follow frontend-dev-guidelines skill
- Mandatory screenshot verification

**Dependencies:**
- None (can start immediately)

**Estimated Time:** 1-1.5h

---

### Agent 2: livewire-specialist

**Task:** Phase 2 + Phase 4
- Phase 2: Conflict Resolution Methods (`usePPMData`, `usePrestaShopData`)
- Phase 4: Immediate Sync Button (`syncShopsImmediate`)

**Priority:** HIGH (business logic, required by Phase 1 UI)

**Deliverables:**
1. Two conflict resolution methods in `ProductForm.php`
2. `syncShopsImmediate()` method in `ProductForm.php`
3. Updated button binding in `product-form.blade.php`
4. Log verification (Log::info for operations)
5. Report: `_AGENT_REPORTS/livewire_specialist_conflict_resolution_2025-11-06_REPORT.md`

**Integration Points:**
- Methods MUST exist before Phase 1 UI can wire:click bind them
- `syncShopsImmediate()` updates `$this->loadedShopData[$shopId]` (refreshes UI)
- Must call `loadShopDataToForm($this->activeShopId)` after sync

**Requirements:**
- NO constructor DI (use app() helper)
- Use `$this->dispatch()` not `$this->emit()` (Livewire 3.x)
- Follow livewire-dev-guidelines skill
- Add proper error handling (try-catch blocks)

**Dependencies:**
- None (can start immediately)

**Estimated Time:** 2h (Phase 2: 1h, Phase 4: 1h)

---

### Agent 3: laravel-expert

**Task:** Phase 3 + Phase 5 + Phase 6
- Phase 3: Button Refactoring (ProductFormSaver)
- Phase 5: Background Pull Job
- Phase 6: Database Migration

**Priority:** MEDIUM (backend logic, not blocking UI)

**Deliverables:**
1. Refactored `ProductFormSaver.php` with `saveDefaultMode()` and `saveShopMode()`
2. New `PullProductsFromPrestaShop` job
3. Updated `Kernel.php` scheduler
4. Migration for `last_pulled_at` column
5. Report: `_AGENT_REPORTS/laravel_expert_button_refactoring_2025-11-06_REPORT.md`

**Integration Points:**
- `ProductFormSaver::save()` must check `$this->component->activeShopId`
- `saveShopMode()` creates job ONLY for `$shopId` (not all shops)
- `PullProductsFromPrestaShop` updates `product_shop_data.last_pulled_at`

**Requirements:**
- Follow Laravel 12.x patterns
- Use proper Eloquent relationships
- Add proper error handling
- Use Log::info for tracking

**Dependencies:**
- None (can start immediately)

**Estimated Time:** 2.25h (Phase 3: 1h, Phase 5: 1h, Phase 6: 0.25h)

---

## INTEGRATION VERIFICATION CHECKLIST

### 1. UI ↔ Livewire Methods Binding

**Check:**
- [ ] Blade template has `wire:click="usePPMData({{ $activeShopId }})"` button
- [ ] Blade template has `wire:click="usePrestaShopData({{ $activeShopId }})"` button
- [ ] Blade template has `wire:click="syncShopsImmediate"` button
- [ ] ProductForm.php has `public function usePPMData(int $shopId)` method
- [ ] ProductForm.php has `public function usePrestaShopData(int $shopId)` method
- [ ] ProductForm.php has `public function syncShopsImmediate()` method

**Risk:** UI buttons call non-existent methods → 500 error

**Mitigation:** livewire-specialist MUST implement methods BEFORE frontend-specialist deploys UI

---

### 2. ProductFormSaver Refactoring

**Check:**
- [ ] `ProductFormSaver::save()` checks `$this->component->activeShopId`
- [ ] If `activeShopId === null` → calls `saveDefaultMode()` (NO sync job)
- [ ] If `activeShopId !== null` → calls `saveShopMode($activeShopId)` (sync job for ONE shop)
- [ ] `saveShopMode()` uses `SyncProductToPrestaShop::dispatch($product, $shop)` (not loop)
- [ ] Default mode does NOT create sync jobs

**Risk:** Wrong button behavior persists → sync jobs created for ALL shops in default mode

**Mitigation:** laravel-expert MUST test both modes (default vs shop)

---

### 3. Sync Data Flow

**Check:**
- [ ] `syncShopsImmediate()` fetches from PrestaShop API
- [ ] Updates `product_shop_data` with fresh values
- [ ] Updates `$this->loadedShopData[$shopId]` cache
- [ ] Calls `loadShopDataToForm($this->activeShopId)` to refresh form
- [ ] UI comparison panel re-renders with new values

**Risk:** Sync doesn't refresh UI → user sees stale data

**Mitigation:** livewire-specialist MUST update both DB and component state

---

### 4. Background Job Integration

**Check:**
- [ ] `PullProductsFromPrestaShop` job exists
- [ ] Scheduled in `Kernel.php` (every 6 hours)
- [ ] Uses `PrestaShopClientFactory::create($shop)`
- [ ] Updates `product_shop_data.last_pulled_at` timestamp
- [ ] Sets `sync_status = 'synced'` after successful pull

**Risk:** Job runs but doesn't update timestamps → can't track last pull

**Mitigation:** laravel-expert MUST test job execution manually

---

### 5. Database Schema

**Check:**
- [ ] Migration adds `last_pulled_at` column to `product_shop_data`
- [ ] Column is `nullable()` (existing records have NULL)
- [ ] Column has `comment('Last time PrestaShop data was pulled to PPM')`
- [ ] Migration can rollback (`dropColumn('last_pulled_at')`)

**Risk:** Migration fails → blocks deployment

**Mitigation:** laravel-expert MUST test migration locally first

---

### 6. CSS Styling

**Check:**
- [ ] All CSS classes defined in `resources/css/admin/components.css`
- [ ] NO inline styles in Blade template
- [ ] NO arbitrary Tailwind values (`z-[9999]`, `bg-[#...]`)
- [ ] CSS follows existing component patterns
- [ ] Screenshot verification PASSED (no layout issues)

**Risk:** Broken styles → poor UX

**Mitigation:** frontend-specialist MUST run screenshot verification BEFORE deployment

---

## DEPLOYMENT PLAN

### Step 1: Code Integration (LOCAL)

**Execute in this order:**

1. **Frontend files** (frontend-specialist deliverables)
   ```bash
   # Verify files exist and have proper content
   cat resources/views/livewire/products/management/product-form.blade.php
   cat resources/css/admin/components.css
   ```

2. **Livewire methods** (livewire-specialist deliverables)
   ```bash
   # Verify methods exist in ProductForm.php
   grep -n "function usePPMData" app/Http/Livewire/Products/Management/ProductForm.php
   grep -n "function usePrestaShopData" app/Http/Livewire/Products/Management/ProductForm.php
   grep -n "function syncShopsImmediate" app/Http/Livewire/Products/Management/ProductForm.php
   ```

3. **Laravel backend** (laravel-expert deliverables)
   ```bash
   # Verify ProductFormSaver refactoring
   grep -n "function saveDefaultMode" app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
   grep -n "function saveShopMode" app/Http/Livewire/Products/Management/Services/ProductFormSaver.php

   # Verify job exists
   cat app/Jobs/PullProductsFromPrestaShop.php

   # Verify scheduler updated
   grep -n "PullProductsFromPrestaShop" app/Console/Kernel.php
   ```

4. **Integration testing**
   ```bash
   # PHPStan (static analysis)
   composer phpstan

   # Local server
   php artisan serve

   # Visit: http://localhost:8000/admin/products/{product_id}/edit
   # Test: Switch to shop TAB → verify comparison panel visible
   ```

---

### Step 2: Migration Execution (PRODUCTION)

**⚠️ CRITICAL:** Migration FIRST (before code deployment)

```powershell
# 1. Upload migration file
pscp -i $HostidoKey -P 64321 `
  "database\migrations\2025_11_06_*_add_last_pulled_at_to_product_shop_data.php" `
  "$HostidoHost:$HostidoPath/database/migrations/"

# 2. Run migration
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
  "cd $HostidoPath && php artisan migrate --force"

# 3. Verify column exists
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
  "cd $HostidoPath && php artisan tinker --execute='DB::select(\"SHOW COLUMNS FROM product_shop_data LIKE \"%last_pulled_at%\"\")'"
```

**Expected output:**
```
Migration table created successfully.
Migrated: 2025_11_06_XXXXXX_add_last_pulled_at_to_product_shop_data
```

---

### Step 3: Code Deployment (PRODUCTION)

**Deploy in this order:**

1. **Backend files** (NO frontend yet - avoid broken wire:click bindings)
   ```powershell
   # 1. ProductForm.php (with new methods)
   pscp -i $HostidoKey -P 64321 `
     "app\Http\Livewire\Products\Management\ProductForm.php" `
     "$HostidoHost:$HostidoPath/app/Http/Livewire/Products/Management/"

   # 2. ProductFormSaver.php (refactored)
   pscp -i $HostidoKey -P 64321 `
     "app\Http\Livewire\Products\Management\Services\ProductFormSaver.php" `
     "$HostidoHost:$HostidoPath/app/Http/Livewire/Products/Management/Services/"

   # 3. PullProductsFromPrestaShop job
   pscp -i $HostidoKey -P 64321 `
     "app\Jobs\PullProductsFromPrestaShop.php" `
     "$HostidoHost:$HostidoPath/app/Jobs/"

   # 4. Kernel.php (scheduler)
   pscp -i $HostidoKey -P 64321 `
     "app\Console\Kernel.php" `
     "$HostidoHost:$HostidoPath/app/Console/"
   ```

2. **Clear backend caches**
   ```powershell
   plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
     "cd $HostidoPath && php artisan cache:clear && php artisan config:clear"
   ```

3. **Frontend files** (AFTER backend deployed)
   ```powershell
   # 1. Build locally (regenerates ALL hashes)
   npm run build

   # 2. Deploy ALL assets (not selective!)
   pscp -i $HostidoKey -P 64321 -r `
     "public\build\assets\*" `
     "$HostidoHost:$HostidoPath/public/build/assets/"

   # 3. Deploy manifest to ROOT location
   pscp -i $HostidoKey -P 64321 `
     "public\build\.vite\manifest.json" `
     "$HostidoHost:$HostidoPath/public/build/manifest.json"
   ```

4. **Clear frontend caches**
   ```powershell
   plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
     "cd $HostidoPath && php artisan view:clear"
   ```

5. **Verify HTTP 200** (all assets accessible)
   ```powershell
   curl -I https://ppm.mpptrade.pl/public/build/assets/app-*.css
   curl -I https://ppm.mpptrade.pl/public/build/assets/components-*.css
   # All must return HTTP 200
   ```

---

### Step 4: Verification (PRODUCTION)

1. **Screenshot verification**
   ```bash
   node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products/11018/edit" --show
   ```

   **Check:**
   - [ ] Comparison panel visible in shop TAB
   - [ ] "PPM (Dane)" column shows current form value
   - [ ] "PrestaShop (Aktualnie)" column shows fetched value
   - [ ] Conflict indicator visible (if values differ)
   - [ ] NO console errors

2. **Manual testing** (TEST-AUTOFIX-1762422647 product)
   - [ ] Open product 11018
   - [ ] Switch to shop TAB (Shop 1)
   - [ ] Verify comparison panel shows:
     - PPM: `[ZMIANA] Test Auto-Fix Required Fields 1762422647`
     - PrestaShop: `Test Auto-Fix Required Fields 1762422647`
     - Conflict indicator: ⚠️ KONFLIKT
   - [ ] Click "← Użyj PPM" → verify sync status = "pending"
   - [ ] Click "→ Użyj PrestaShop" → verify form value changes to PrestaShop value
   - [ ] Click "🔄 Synchronizuj sklepy" → verify immediate pull completes
   - [ ] Test "Zapisz zmiany" in default mode → verify NO sync job created
   - [ ] Test "Zapisz zmiany" in shop mode → verify sync job ONLY for this shop

3. **Background job testing** (OPTIONAL)
   ```bash
   # Manually trigger job (test execution)
   php artisan tinker
   > $shop = App\Models\PrestaShopShop::find(1);
   > App\Jobs\PullProductsFromPrestaShop::dispatch($shop);
   > exit

   # Check logs
   tail -f storage/logs/laravel.log | grep "PrestaShop → PPM pull"
   ```

4. **Database verification**
   ```sql
   -- Verify last_pulled_at column exists and updates
   SELECT id, shop_id, name, sync_status, last_pulled_at, last_sync_at
   FROM product_shop_data
   WHERE product_id = 11018
   LIMIT 5;
   ```

---

## ROLLBACK PLAN

### If Deployment Fails:

**Step 1: Revert frontend files**
```powershell
# Re-deploy previous build
pscp -i $HostidoKey -P 64321 -r `
  "D:\Backups\PPM_2025-11-06_pre-shop-sync\public\build\assets\*" `
  "$HostidoHost:$HostidoPath/public/build/assets/"

# Clear caches
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
  "cd $HostidoPath && php artisan view:clear && php artisan cache:clear"
```

**Step 2: Revert backend files**
```powershell
# Re-deploy previous versions
pscp -i $HostidoKey -P 64321 `
  "D:\Backups\PPM_2025-11-06_pre-shop-sync\app\Http\Livewire\Products\Management\ProductForm.php" `
  "$HostidoHost:$HostidoPath/app/Http/Livewire/Products/Management/"

pscp -i $HostidoKey -P 64321 `
  "D:\Backups\PPM_2025-11-06_pre-shop-sync\app\Http\Livewire\Products\Management\Services\ProductFormSaver.php" `
  "$HostidoHost:$HostidoPath/app/Http/Livewire/Products/Management/Services/"
```

**Step 3: Rollback migration** (ONLY if migration caused issues)
```powershell
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
  "cd $HostidoPath && php artisan migrate:rollback --step=1"
```

**Step 4: Verify rollback**
```bash
curl -I https://ppm.mpptrade.pl
# Should return HTTP 200

node _TOOLS/full_console_test.cjs "https://ppm.mpptrade.pl/admin/products/11018/edit"
# Should show old UI (no comparison panel)
```

---

## RISK ASSESSMENT

### HIGH RISK

**1. Wire:click Binding Mismatch**
- **Problem:** UI calls method that doesn't exist yet
- **Impact:** 500 error when user clicks button
- **Probability:** MEDIUM (if deployment order wrong)
- **Mitigation:** Deploy backend BEFORE frontend, verify methods exist

**2. Vite Hash Mismatch**
- **Problem:** Deploy only changed CSS file, not ALL files
- **Impact:** HTTP 404 for app.css, no styles on page
- **Probability:** HIGH (most common deployment error)
- **Mitigation:** ALWAYS deploy ALL `public/build/assets/*` files

---

### MEDIUM RISK

**3. ProductFormSaver Logic Error**
- **Problem:** Wrong condition in `save()` method
- **Impact:** Sync jobs created in wrong mode
- **Probability:** LOW (simple logic)
- **Mitigation:** Unit test both modes before deployment

**4. Sync Data Not Refreshing UI**
- **Problem:** `syncShopsImmediate()` updates DB but not component state
- **Impact:** User sees stale data until page reload
- **Probability:** MEDIUM (common Livewire issue)
- **Mitigation:** Test UI refresh manually before deployment

---

### LOW RISK

**5. Migration Failure**
- **Problem:** Column already exists or DB permissions issue
- **Impact:** Deployment blocked
- **Probability:** LOW (simple migration)
- **Mitigation:** Test migration locally first, can rollback easily

**6. Background Job Not Running**
- **Problem:** Scheduler not active or job fails silently
- **Impact:** No automatic pulls, but manual sync still works
- **Probability:** LOW (job is simple)
- **Mitigation:** Test job manually with `php artisan tinker`

---

## SUCCESS CRITERIA

### Must Pass (CRITICAL)

- [ ] **UI Comparison Panel Visible:** Opening shop TAB shows "PPM vs PrestaShop" comparison
- [ ] **Conflict Detection Works:** When values differ, ⚠️ KONFLIKT indicator visible
- [ ] **Resolution Buttons Work:** "Użyj PPM" and "Użyj PrestaShop" buttons functional
- [ ] **Immediate Sync Works:** "Synchronizuj sklepy" pulls fresh data without closing form
- [ ] **Save Button Fixed:** "Zapisz zmiany" in default mode does NOT create sync jobs
- [ ] **Save Button Fixed:** "Zapisz zmiany" in shop mode creates job ONLY for THIS shop
- [ ] **No Console Errors:** Screenshot verification shows 0 console errors
- [ ] **HTTP 200 Status:** All CSS/JS assets return HTTP 200

### Should Pass (HIGH PRIORITY)

- [ ] **UI Responsive:** Comparison panel looks good on desktop and laptop
- [ ] **Loading States:** Sync button shows "Pobieranie danych..." during execution
- [ ] **Success Messages:** Flash messages confirm operations ("Zapisano", "Pobrano")
- [ ] **Error Handling:** Try-catch blocks catch errors, show user-friendly messages
- [ ] **Logs Present:** Log::info tracks all operations (usePPMData, sync, etc)

### Nice to Have (MEDIUM PRIORITY)

- [ ] **Background Job Running:** PullProductsFromPrestaShop job executes every 6 hours
- [ ] **Timestamp Tracking:** `last_pulled_at` column updates correctly
- [ ] **Migration Clean:** Can rollback migration without issues
- [ ] **Performance:** Sync completes in <5 seconds for typical shop

---

## TIMELINE ESTIMATE

### Sequential Approach (One agent at a time)
- **Total:** 5.75h
  - frontend-specialist: 1.5h
  - livewire-specialist: 2h (after frontend complete)
  - laravel-expert: 2.25h (after livewire complete)

### Parallel Approach (All agents simultaneously)
- **Total:** 2.25h (max agent time = laravel-expert)
  - frontend-specialist: 1.5h
  - livewire-specialist: 2h
  - laravel-expert: 2.25h
  - **Integration:** 0.5h (coordination, verification)
  - **Deployment:** 1h (migration + code + verification)

**Recommended:** **PARALLEL APPROACH** (agents can work independently)

**Total project time:** ~3.75h (2.25h development + 0.5h integration + 1h deployment)

---

## NEXT STEPS

### For Orchestrator (User or Coordination Agent)

**1. Create backups** (before starting work)
```powershell
# Backup current production state
$BackupDir = "D:\Backups\PPM_2025-11-06_pre-shop-sync"
mkdir $BackupDir

# Download current files via pscp
pscp -i $HostidoKey -P 64321 -r `
  "$HostidoHost:$HostidoPath/app/Http/Livewire/Products/Management/" `
  "$BackupDir\app\Http\Livewire\Products\Management\"

pscp -i $HostidoKey -P 64321 -r `
  "$HostidoHost:$HostidoPath/public/build/" `
  "$BackupDir\public\build\"
```

**2. Delegate to 3 agents IN PARALLEL**

Create 3 agent instructions:

**A. frontend-specialist:**
```
Task: Implement UI Comparison Panel for Shop Data Sync Issue
Reference: _ISSUES_FIXES/SHOP_DATA_SYNC_ISSUE.md (Phase 1)
Coordination: _AGENT_REPORTS/architect_shop_data_sync_coordination_2025-11-06_REPORT.md

Requirements:
- Add comparison panel in product-form.blade.php (after shop TAB selector)
- Add CSS classes in components.css (NO inline styles!)
- Wire buttons to: usePPMData, usePrestaShopData
- Screenshot verification MANDATORY (full_console_test.cjs)
- Report: frontend_specialist_shop_comparison_ui_2025-11-06_REPORT.md

Estimated time: 1-1.5h
```

**B. livewire-specialist:**
```
Task: Implement Conflict Resolution Methods for Shop Data Sync Issue
Reference: _ISSUES_FIXES/SHOP_DATA_SYNC_ISSUE.md (Phase 2 + Phase 4)
Coordination: _AGENT_REPORTS/architect_shop_data_sync_coordination_2025-11-06_REPORT.md

Requirements:
- Add usePPMData(shopId) method to ProductForm.php
- Add usePrestaShopData(shopId) method to ProductForm.php
- Add syncShopsImmediate() method to ProductForm.php
- Update syncShops button binding in blade
- NO constructor DI, use app() helper
- Report: livewire_specialist_conflict_resolution_2025-11-06_REPORT.md

Estimated time: 2h
```

**C. laravel-expert:**
```
Task: Refactor Button Logic + Background Job for Shop Data Sync Issue
Reference: _ISSUES_FIXES/SHOP_DATA_SYNC_ISSUE.md (Phase 3 + Phase 5 + Phase 6)
Coordination: _AGENT_REPORTS/architect_shop_data_sync_coordination_2025-11-06_REPORT.md

Requirements:
- Refactor ProductFormSaver::save() - split default/shop mode
- Create PullProductsFromPrestaShop job
- Update Kernel.php scheduler (every 6h)
- Create migration for last_pulled_at column
- Report: laravel_expert_button_refactoring_2025-11-06_REPORT.md

Estimated time: 2.25h
```

**3. Monitor agent progress**
```bash
# Check for reports every 30 min
ls -lt _AGENT_REPORTS/ | head -5
```

**4. When all 3 reports exist:**
- Run integration verification (checklist above)
- Execute deployment sequence (steps 1-4 above)
- Perform user acceptance testing
- Update Plan_Projektu status

---

## FOLLOW-UP TASKS

### After Successful Deployment

**1. User Manual Testing** (20 min)
- Test product TEST-AUTOFIX-1762422647
- Verify all 8 success criteria
- Confirm: "działa idealnie"

**2. Update Plan_Projektu** (5 min)
- Mark Shop Data Sync Issue as ✅ RESOLVED
- Add file paths to completed tasks
- Update ETAP progress

**3. Debug Log Cleanup** (OPTIONAL, if logs added)
- Wait for user confirmation
- Remove Log::debug() calls
- Keep only Log::info and Log::error

**4. Documentation Update** (10 min)
- Add to TROUBLESHOOTING.md
- Link to SHOP_DATA_SYNC_ISSUE.md
- Add to known issues list

---

## ZALĄCZNIKI

### Source Documentation
1. `_ISSUES_FIXES/SHOP_DATA_SYNC_ISSUE.md` (810 lines) - Root cause analysis + solution design
2. `_DOCS/PROJECT_KNOWLEDGE.md` - Architecture reference
3. `_DOCS/TROUBLESHOOTING.md` - Known issues
4. `_DOCS/DEPLOYMENT_GUIDE.md` - Deployment procedures

### Skills Referenced
1. `.claude/skills/guidelines/frontend-dev-guidelines/` - CSS rules
2. `.claude/skills/guidelines/livewire-dev-guidelines/` - Livewire patterns
3. `.claude/skills/hostido-deployment/` - Deployment automation

### Tools
1. `_TOOLS/full_console_test.cjs` - Screenshot verification (MANDATORY)
2. `_TOOLS/upload_*.ps1` - Deployment scripts

---

**Raport utworzony przez:** architect (Planning Manager & Project Plan Keeper)
**Status:** ✅ COORDINATION PLAN READY - Agents NOT YET STARTED
**Recommended Action:** Delegate to 3 agents IN PARALLEL for 2.25h development + 1h deployment
**Total Estimated Time:** 3.75h (end-to-end)
**Timestamp:** 2025-11-06 12:00:00
