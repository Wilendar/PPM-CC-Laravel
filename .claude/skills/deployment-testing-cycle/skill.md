---
name: deployment-testing-cycle
description: Universal deployment-testing workflow for Laravel/Livewire applications. Use AUTOMATICALLY after ANY code creation, update, or refactoring - includes deploy to production, manual testing, plan updates, and continuous decision loop.
version: 1.0.0
author: Claude Code + User Workflow
created: 2025-10-30
updated: 2025-10-30
category: workflow
tags: [deployment, testing, production, laravel, livewire, workflow, cycle, mandatory]
project: PPM-CC-Laravel
auto_trigger: true
---

# Deployment-Testing-Cycle Skill

## 🎯 OVERVIEW

**UNIWERSALNY SKILL** dla projektu PPM-CC-Laravel (Laravel 12.x + Livewire 3.x + Blade + Alpine.js + Tailwind CSS).

**⚠️ CRITICAL:** Ten skill **MUSI BYĆ UŻYTY** po KAŻDEJ zmianie kodu:
- ✅ Nowy kod (new feature, new component, new functionality)
- ✅ Aktualizacja kodu (enhancement, update, modification)
- ✅ Refactoring kodu (optimization, cleanup, restructuring)

**FUNDAMENTALNA ZASADA**:
> **Nie można testować bez deployment!**
> Środowisko produkcyjne (ppm.mpptrade.pl) jest JEDYNYM miejscem testowania w PPM-CC-Laravel.

**Complete Workflow Cycle:**
```
New/Updated/Refactored Code
         ↓
    Deploy to Production
         ↓
    Manual Testing on Production
         ↓
    Update Project Plan
         ↓
    Decision: Continue / Fix
         ↓ (if issues found)
    Fix Issues → LOOP BACK to Deploy
         ↑___________________|
         ↓ (if all OK)
    Next Task / TODO
```

---

## 🚀 KIEDY UŻYWAĆ TEGO SKILLA (AUTO-TRIGGER)

### ✅ ZAWSZE używaj gdy:

**1. Nowy Kod (New Code):**
- Utworzono nowy Livewire component
- Utworzono nowy Model
- Utworzono nowy Service/Repository
- Utworzono nową Blade view
- Utworzono nową migration
- Utworzono nowy CSS file
- Utworzono nową functionality

**2. Aktualizacja Kodu (Updated Code):**
- Zmodyfikowano istniejący Livewire component
- Zaktualizowano Model (nowe relationships, methods)
- Zaktualizowano Service logic
- Zmodyfikowano Blade template
- Dodano/zmodyfikowano CSS classes
- Zaktualizowano JavaScript/Alpine.js logic
- Zaktualizowano validation rules

**3. Refactoring Kodu (Refactored Code):**
- Extracted method/trait/service
- Reorganizowano file structure
- Simplified complex logic
- Improved performance
- Split large files (>300 lines per CLAUDE.md)
- Renamed classes/methods/variables
- Updated documentation

### ❌ NIE używaj gdy:
- Tylko czytasz kod (no changes made)
- Tylko analizujesz/planowujesz (no implementation)
- Zmiany są work-in-progress (not ready for deployment)
- Brak dostępu do Hostido (deployment impossible)

---

## 📋 COMPLETE WORKFLOW

### PHASE 0: PRE-FLIGHT CHECK ✈️

**Czas:** 2-3 min

**Checklist przed deployment:**

```markdown
CODE READINESS:
- [ ] All files saved (no unsaved changes)
- [ ] Syntax errors fixed (PHP: php -l, Blade: basic check)
- [ ] No hardcoded values (compliance check)
- [ ] File size <300 lines per file (CLAUDE.md compliance)
- [ ] Polish language for user-facing strings

DEPLOYMENT READINESS:
- [ ] Know which files changed (list ready)
- [ ] Assets built if CSS/JS changes (`npm run build` done)
- [ ] Migrations ready if DB changes
- [ ] SSH access available (Hostido key accessible)

TIME READINESS:
- [ ] Have 20-40 min for complete cycle (deploy + test + update)
- [ ] Ready for manual testing (testing plan prepared)
- [ ] Can fix issues if found (not end of day!)

ENVIRONMENT:
- [ ] Working directory: D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel
- [ ] SSH key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk exists
- [ ] Production URL accessible: https://ppm.mpptrade.pl
```

**If ANY checkbox = NO:** STOP! Fix issues before proceeding.

**Proceed to Phase 1 ↓**

---

### PHASE 1: DEPLOYMENT 🚀

**Czas:** 5-15 min (depending on change type)

#### 1.1. Identify Change Type

**Determine what changed:**

| Change Type | Files Affected | Examples |
|-------------|----------------|----------|
| **PHP Only** | `*.php` | Models, Controllers, Services, Livewire components |
| **Blade Only** | `*.blade.php` | Views, partials, layouts |
| **CSS/JS** | `*.css`, `*.js` + assets | Styles, Alpine.js scripts, Tailwind classes |
| **Migration** | `database/migrations/*.php` | Database schema changes |
| **Mixed** | Multiple types | Full feature implementation |

#### 1.2. Build Assets (if CSS/JS changed)

```bash
# Build Vite assets
npm run build
```

**Verify build:**
- ✅ Console output: `✓ built in X.XXs`
- ✅ `public/build/assets/` contains files with NEW hashes
- ✅ `public/build/.vite/manifest.json` updated
- ✅ No build errors

**If build fails:** Fix errors before proceeding!

#### 1.3. Upload PHP Files

**Single PHP File:**
```bash
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'LOCAL_PATH\File.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/REMOTE_PATH/File.php'"
```

**Examples:**
```bash
# Livewire Component
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'app\Http\Livewire\Admin\MyComponent.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/MyComponent.php'"

# Model
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'app\Models\Product.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Models/Product.php'"

# Service
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'app\Services\MyService.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/MyService.php'"

# Trait
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'app\Http\Livewire\Admin\Traits\MyTrait.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/Traits/MyTrait.php'"
```

**Multiple PHP Files in Directory:**
```bash
# Upload entire directory (recursive)
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 -r 'app\Http\Livewire\Admin\MyModule\*' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/MyModule/'"
```

#### 1.4. Upload Blade Files

**Single Blade View:**
```bash
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'resources\views\livewire\admin\my-component.blade.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/admin/my-component.blade.php'"
```

**Multiple Blade Partials:**
```bash
# Upload all partials in directory
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 -r 'resources\views\livewire\admin\partials\*' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/admin/partials/'"
```

#### 1.5. Upload CSS/JS Assets (if changed)

**⚠️ CRITICAL RULE:** Upload **ALL** assets from `public/build/assets/`, not just "changed" files!

**Why?** Vite content-based hashing = EVERY build regenerates hashes for ALL files, even if content unchanged!

```bash
# 1. Upload ALL hashed assets
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 -r 'public\build\assets\*' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/'"

# 2. Upload manifest.json to ROOT location (MANDATORY!)
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'public\build\.vite\manifest.json' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json'"
```

**⚠️ DOUBLE CHECK:** Manifest MUST go to ROOT (`public/build/manifest.json`), NOT subdirectory!

**Reference:** `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md`

#### 1.6. Upload Migrations (if DB changes)

```bash
# Upload migration file
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'database\migrations\2025_XX_XX_XXXXXX_migration_name.php' 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/migrations/'"
```

#### 1.7. Clear Caches (MANDATORY!)

```bash
pwsh -NoProfile -Command "plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -batch 'cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear'"
```

**Expected:** Commands execute without errors (exit code 0)

**Why clear caches:**
- `view:clear` - Blade compiled views
- `cache:clear` - Application cache (Livewire components, config)
- `config:clear` - Config cache (if env changes)

#### 1.8. Run Migrations (if DB changes)

```bash
pwsh -NoProfile -Command "plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -batch 'cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force'"
```

**Verify migration success:**
- ✅ Output shows "Migrating: ..." and "Migrated: ..."
- ✅ No errors about duplicate columns/tables
- ✅ Exit code 0

#### 1.9. Verify Deployment (HTTP 200 Checks)

**⚠️ MANDATORY for CSS/JS changes:**

```bash
# Verify each CSS file returns HTTP 200
pwsh -NoProfile -Command "@('app-HASH.css', 'components-HASH.css', 'layout-HASH.css') | ForEach-Object { curl -I 'https://ppm.mpptrade.pl/public/build/assets/$_' }"
```

**Replace HASH** with actual hashes from `npm run build` output or manifest.json!

**Expected:** ALL files return `HTTP/1.1 200 OK`

**If ANY returns 404:**
- Deployment INCOMPLETE!
- Re-upload assets (step 1.5)
- Re-upload manifest (step 1.5)
- Clear caches (step 1.7)
- Re-verify

#### 1.10. Quick Smoke Test

```bash
# Verify production site is up
curl -I https://ppm.mpptrade.pl
```

**Expected:** `HTTP/1.1 200 OK` (not 500, not 404, not 503)

**If 500 error:**
- Check Laravel logs immediately (step 2.5)
- May need rollback!

**✅ Deployment Phase Complete!**

**Proceed to Phase 2 ↓**

---

### PHASE 2: MANUAL TESTING 🧪

**Czas:** 10-30 min (depending on change scope)

#### 2.1. Login to Production

```
URL: https://ppm.mpptrade.pl/login
Email: admin@mpptrade.pl
Password: Admin123!MPP
Role: Admin (full permissions)
```

#### 2.2. Create Testing Checklist (Dynamically)

**Based on what changed, create appropriate checklist:**

**Example: New Livewire Component**
```markdown
UI VERIFICATION:
- [ ] Navigate to component route: /admin/[route]
- [ ] Component loads without errors (no blank page)
- [ ] Layout renders correctly (header, sidebar, content)
- [ ] CSS styles applied correctly (no unstyled elements)
- [ ] Alpine.js functionality works (modals, dropdowns, etc.)

FUNCTIONALITY TESTING:
- [ ] Primary action works (e.g., "Create" button)
- [ ] Form validation works (required fields, format validation)
- [ ] CRUD operations work (Create, Read, Update, Delete)
- [ ] Error messages display correctly (Polish language)
- [ ] Success messages display correctly

DATA VERIFICATION:
- [ ] Data saves to database correctly
- [ ] Data displays correctly after refresh
- [ ] Related data updates (if applicable)

LIVEWIRE SPECIFIC:
- [ ] Wire:model bindings work (two-way binding)
- [ ] Wire:click events work (button actions)
- [ ] Wire:loading states work (loading indicators)
- [ ] $dispatch events work (component communication)
```

**Example: Updated Model**
```markdown
RELATIONSHIP TESTING:
- [ ] Existing relationships still work
- [ ] New relationships work correctly
- [ ] Eager loading works (no N+1 queries)
- [ ] Cascade deletes work if applicable

METHOD TESTING:
- [ ] New methods return expected results
- [ ] Existing methods still work (no regression)
- [ ] Computed properties work correctly

DATABASE VERIFICATION:
- [ ] Schema matches migration
- [ ] Foreign keys exist
- [ ] Indexes created correctly
```

**Example: CSS Changes**
```markdown
VISUAL VERIFICATION:
- [ ] New styles applied correctly
- [ ] Existing styles not broken (regression check)
- [ ] Responsive design works (mobile/tablet/desktop)
- [ ] Dark mode compatibility (if applicable)
- [ ] Browser compatibility (Chrome, Firefox, Safari)

PERFORMANCE:
- [ ] No layout shifts (CLS)
- [ ] No flickering on load
- [ ] Animations smooth (60fps)
```

#### 2.3. Execute Testing Checklist

**Go through EVERY item** in your dynamically created checklist.

**For each item:**
1. ✅ Mark PASS if works correctly
2. ❌ Mark FAIL if doesn't work
3. ⚠️ Mark PARTIAL if works with issues
4. Document ANY unexpected behavior (even minor!)

#### 2.4. Browser DevTools Check

**Open Chrome DevTools (F12):**

**Console Tab:**
- [ ] No JavaScript errors
- [ ] No Livewire errors
- [ ] No Alpine.js errors

**Network Tab:**
- [ ] No 404 errors (missing assets)
- [ ] No 500 errors (server errors)
- [ ] CSS/JS files load correctly (200 OK)
- [ ] Images load correctly

**Performance Tab (optional):**
- [ ] No long tasks (>50ms)
- [ ] No excessive re-renders (Livewire)

#### 2.5. Laravel Logs Check

**SSH to server and check logs:**

```bash
# Connect to server
pwsh -NoProfile -Command "plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk'"

# Navigate to Laravel root
cd domains/ppm.mpptrade.pl/public_html

# Check last 50 lines of logs
tail -n 50 storage/logs/laravel.log

# Exit SSH
exit
```

**Look for:**
- ❌ PHP errors related to your changes
- ❌ SQL errors (queries failing)
- ❌ Livewire exceptions
- ⚠️ Deprecation warnings
- ⚠️ N+1 query warnings (if query logging enabled)

**Expected:** No new errors related to your deployment

#### 2.6. Screenshot Verification (for UI changes)

**⚠️ MANDATORY dla UI changes!**

**Use frontend-verification skill:**
```bash
# Invoke skill for screenshot
Skill: frontend-verification
```

**Or manual screenshot:**
- Take screenshot of affected pages
- Save to `_TOOLS/screenshots/` with descriptive name
- Compare with expected design/mockup

#### 2.7. Calculate Success Rate

**Formula:**
```
Success Rate = (Passed Items / Total Items) × 100%
```

**Example:**
- Total checklist items: 20
- Passed: 19
- Failed: 1
- Success Rate: 95%

**Rating:**
- ✅ 100%: PERFECT - No issues
- ✅ 95-99%: EXCELLENT - Minor issues only
- ⚠️ 80-94%: GOOD - Some issues, fixable
- ⚠️ 70-79%: FAIR - Multiple issues, needs attention
- ❌ <70%: POOR - Major issues, rollback consideration

**✅ Testing Phase Complete!**

**Proceed to Phase 3 ↓**

---

### PHASE 3: UPDATE PROJECT PLAN 📝

**Czas:** 5-10 min

#### 3.1. Identify Plan File to Update

**Common plan files:**
- `Plan_Projektu/ETAP_05a_Produkty.md`
- `Plan_Projektu/ETAP_05b_Produkty_Warianty.md`
- `Plan_Projektu/ETAP_05c_Produkty_Cechy.md`
- `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md`
- etc.

**Determine which ETAP/Phase your changes belong to**

#### 3.2. Update Based on Success Rate

**If Success Rate = 100% (Perfect):**

```markdown
### ✅ [Phase/Section Name]

**Status:** ✅ **COMPLETED** (2025-10-30)
**Deployed:** 2025-10-30 HH:MM UTC
**Testing:** Manual testing PASSED - 100% success rate

#### Deliverables:
└── 📁 FILES DEPLOYED:
    ├── app/Path/To/File1.php (NEW/MODIFIED)
    ├── app/Path/To/File2.php (NEW/MODIFIED)
    ├── resources/views/path/to/view.blade.php (NEW/MODIFIED)
    └── resources/css/path/to/style.css (NEW/MODIFIED)

#### Functionality Verified:
- ✅ [Feature 1] works correctly
- ✅ [Feature 2] works correctly
- ✅ [Feature 3] works correctly

#### Time Tracking:
- Estimated: Xh
- Actual: Xh
- Status: On budget / Over budget
```

**If Success Rate = 95-99% (Minor Issues):**

```markdown
### ⚠️ [Phase/Section Name]

**Status:** ✅ **COMPLETED WITH MINOR ISSUES** (2025-10-30)
**Deployed:** 2025-10-30 HH:MM UTC
**Testing:** Manual testing PASSED - 95% success rate (19/20 tests)

#### Deliverables:
└── 📁 FILES DEPLOYED:
    [same as above]

#### Functionality Verified:
- ✅ [Feature 1] works correctly
- ✅ [Feature 2] works correctly
- ⚠️ [Feature 3] has minor issue (workaround available)

#### Known Issues (Minor):
1. **Issue:** [Description of issue]
   - Severity: LOW
   - Workaround: [Temporary workaround]
   - Fix planned: [When will fix]
   - Tracked: [Issue tracker link if applicable]

#### Time Tracking:
- Estimated: Xh
- Actual: Xh (including fix time: +Xh)
- Status: Slightly over budget
```

**If Success Rate = 70-94% (Multiple Issues):**

```markdown
### ⚠️ [Phase/Section Name]

**Status:** ⚠️ **DEPLOYED WITH ISSUES** (2025-10-30)
**Deployed:** 2025-10-30 HH:MM UTC
**Testing:** Manual testing PARTIAL - 85% success rate (17/20 tests)

#### Deliverables:
└── 📁 FILES DEPLOYED:
    [same as above]

#### Functionality Verified:
- ✅ [Feature 1] works correctly
- ⚠️ [Feature 2] partially works (some edge cases fail)
- ❌ [Feature 3] doesn't work (critical issue)

#### Issues Found:
1. **[Feature 3] Critical Issue**
   - Severity: HIGH
   - Error: [Error message/description]
   - Reproduction: [Steps to reproduce]
   - Fix needed: ASAP (blocking users)
   - Assigned: [Who will fix]

2. **[Feature 2] Edge Case**
   - Severity: MEDIUM
   - Error: [Error message/description]
   - Workaround: [Temporary workaround if available]
   - Fix needed: Within 24h

#### Action Plan:
1. [ ] Fix critical issue 1 (Priority: URGENT)
2. [ ] Fix medium issue 2 (Priority: HIGH)
3. [ ] Re-deploy fixes
4. [ ] Re-test affected functionality
5. [ ] Update plan with fix results

#### Time Tracking:
- Estimated: Xh
- Actual: Xh (debugging: +Xh, fix pending: +Xh estimated)
- Status: Over budget due to issues
```

**If Success Rate <70% (Major Failure):**

```markdown
### ❌ [Phase/Section Name]

**Status:** ❌ **DEPLOYMENT FAILED** (2025-10-30)
**Deployed:** 2025-10-30 HH:MM UTC
**Testing:** Manual testing FAILED - 65% success rate (13/20 tests)
**Action:** ROLLBACK RECOMMENDED

#### Critical Issues:
1. **Application Broken**
   - Severity: CRITICAL
   - Error: [500 errors, white screen, etc.]
   - Impact: Users cannot access functionality
   - Rollback: IMMEDIATE

2. **Data Corruption Risk**
   - Severity: CRITICAL
   - Error: [Data saving incorrectly]
   - Impact: Database integrity at risk
   - Rollback: IMMEDIATE

#### Rollback Performed:
- [ ] Reverted PHP files to previous version
- [ ] Reverted Blade files to previous version
- [ ] Rolled back migration (if applicable)
- [ ] Cleared caches
- [ ] Verified production working again

#### Root Cause Analysis:
- [What went wrong]
- [Why wasn't it caught earlier]
- [How to prevent in future]

#### Fix Plan:
1. [ ] Analyze root cause thoroughly
2. [ ] Fix issues in development
3. [ ] Test locally (if possible)
4. [ ] Re-deploy with extra verification
5. [ ] Extended testing period

#### Time Tracking:
- Estimated: Xh
- Actual: Xh (wasted: Xh due to rollback)
- Status: Significantly over budget - needs replanning
```

#### 3.3. Update Progress Metrics

**Update at top of plan file:**

```markdown
**Progress:** 65% → 70% (+5% from this deployment)
**Czas rzeczywisty:** 57.5h → 67.5h (+10h from this task)
**Status:** On track / Behind schedule / Ahead of schedule
```

**Update timeline table:**

```markdown
| Phase | Estimated | Actual | Status |
|-------|-----------|--------|--------|
| Phase X | 10h | 10h | ✅ COMPLETED (2025-10-30) |
| Phase Y | 8h | 12h | ✅ COMPLETED WITH ISSUES (2025-10-30) |
| Phase Z | 5h | TBD | ⏳ IN PROGRESS |
```

**✅ Plan Update Phase Complete!**

**Proceed to Phase 4 ↓**

---

### PHASE 4: DECISION & NEXT STEPS 🎯

**Czas:** 2-5 min

#### 4.1. Decision Tree

```
Testing Success Rate?
│
├─ 100% (Perfect)
│  └─> Decision: CONTINUE to next TODO
│      └─> Update TODO list
│      └─> Ask user: "What next?"
│
├─ 95-99% (Minor Issues)
│  ├─> Minor issues documented?
│  │   └─> YES: CONTINUE with workaround
│  │       └─> Plan fix for later (non-blocking)
│  │       └─> Update TODO list
│  │       └─> Ask user: "Continue or fix now?"
│  └─> NO: Document issues first → CONTINUE
│
├─ 80-94% (Multiple Issues)
│  ├─> Critical issues present?
│  │   ├─> YES: FIX IMMEDIATELY
│  │   │   └─> Create fix plan
│  │   │   └─> LOOP BACK to Phase 1 (Deploy) after fix
│  │   └─> NO (only medium/low issues):
│  │       └─> Ask user: "Fix now or continue?"
│  │           ├─> Fix now: Create fix plan → LOOP BACK
│  │           └─> Continue: Document issues → Next TODO
│  │
└─ <80% (Major Failure)
   └─> ROLLBACK DECISION
       ├─> Rollback possible?
       │   ├─> YES: Execute rollback → Analyze → Fix → LOOP BACK
       │   └─> NO: Emergency fix → Deploy ASAP
       └─> Create detailed post-mortem
```

#### 4.2. Update TODO List (Success Case)

```markdown
✅ [Previous task] - COMPLETED
✅ Deploy + Test [current task] - COMPLETED (Success Rate: 100%)
✅ Update plan for [current task] - COMPLETED

⏳ CURRENT TASK:
→ [Next task from plan]
→ Estimated time: Xh
→ Priority: High/Medium/Low
→ Blockers: None / [List blockers]
```

#### 4.3. Create Fix Plan (Issues Case)

**If issues found and user chooses to fix:**

```markdown
# FIX PLAN - [Issue Name]

## Issues to Fix:
1. **Issue 1:** [Description]
   - Severity: CRITICAL/HIGH/MEDIUM/LOW
   - File affected: [path/to/file.php]
   - Estimated fix time: Xh

2. **Issue 2:** [Description]
   - Severity: [level]
   - File affected: [path]
   - Estimated fix time: Xh

## Total Estimated Fix Time: Xh

## Fix Workflow:
1. [ ] Analyze root cause (Xh)
2. [ ] Implement fix for Issue 1 (Xh)
3. [ ] Implement fix for Issue 2 (Xh)
4. [ ] Local testing (if possible)
5. [ ] LOOP BACK to Phase 1 (Deploy)
6. [ ] Re-test ONLY affected functionality
7. [ ] Update plan with fix results
8. [ ] Continue to next TODO

Ready to start fixing? (yes/no)
```

**If user says YES:**
- Start fixing issues
- After fixes: LOOP BACK to Phase 1 (Deploy)
- Re-run FULL cycle for fixes

**If user says NO:**
- Document issues clearly
- Continue to next TODO
- Schedule fix for later

#### 4.4. Ask User for Next Step

**Template:**

```markdown
═══════════════════════════════════════════════════════
✅ DEPLOYMENT-TESTING-CYCLE COMPLETE!
═══════════════════════════════════════════════════════

SUMMARY:
- Deployed: [List of files]
- Tested: [X/Y tests passed]
- Success Rate: [X%]
- Plan Updated: ✅
- Issues Found: [None / X issues (severity)]

═══════════════════════════════════════════════════════

NEXT STEPS OPTIONS:

1️⃣ CONTINUE TO NEXT TODO
   → Next task: [Task name from plan]
   → Estimated: Xh
   → Priority: [level]

2️⃣ FIX ISSUES FOUND (if applicable)
   → [X] issues to fix
   → Estimated fix time: Xh
   → Will loop back to deploy after fix

3️⃣ USER DECISION
   → Custom task
   → Specify what you want to do next

═══════════════════════════════════════════════════════

What would you like to do? (1/2/3 or describe)
```

**Wait for user response.**

**Based on response:**
- Option 1: Continue to next TODO → Update TODO list → Start next task
- Option 2: Create fix plan → Fix → LOOP BACK to Phase 1
- Option 3: Follow user instructions

**✅ Workflow Cycle Complete!**

---

## 🎯 SUCCESS METRICS

**Cykl deployment-testing jest sukcesem gdy:**

- ✅ Deployment executed bez błędów (pscp/plink exit code 0)
- ✅ Wszystkie pliki uploaded (pscp confirms)
- ✅ HTTP 200 verification PASSED (dla CSS/JS)
- ✅ Manual testing completed (checklist created and executed)
- ✅ Success rate calculated accurately
- ✅ Laravel logs checked (no new errors)
- ✅ Plan updated with accurate status
- ✅ TODO list current (reflects next steps)
- ✅ Decision made (continue/fix)
- ✅ User informed and next step clear

**Target Metrics:**
- Deployment time: <15 min
- Testing time: <30 min
- Plan update time: <10 min
- Decision time: <5 min
- **Total cycle time: <60 min**

**Quality Metrics:**
- Success rate target: >95%
- Regression rate: <5% (existing functionality broken)
- Rollback rate: <2% (need to rollback)

---

## 🔧 TROUBLESHOOTING

### Problem: Deployment fails (pscp errors)

**Symptoms:**
- `pscp` returns non-zero exit code
- "Permission denied" errors
- "Connection refused"

**Diagnosis:**
```bash
# Test SSH connection
pwsh -NoProfile -Command "plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -batch 'pwd'"

# Expected: /home/host379076
```

**Solutions:**
1. Verify SSH key exists and has correct permissions
2. Check network connection (firewall, VPN)
3. Verify Hostido server status
4. Try manual pscp with verbose: add `-v` flag
5. Contact Hostido support if persistent

---

### Problem: HTTP 200 verification fails (404)

**Symptoms:**
- `curl -I` returns 404 for CSS files
- Production site missing styles
- Console errors: "Failed to load resource"

**Root Cause:** Incomplete asset deployment or manifest mismatch

**Diagnosis:**
```bash
# Check manifest on server
pwsh -NoProfile -Command "plink ... -batch 'cat domains/.../public/build/manifest.json | grep components'"

# Compare with local
cat public/build/.vite/manifest.json | grep components

# If hashes different: Deployment incomplete!
```

**Solutions:**
1. Re-upload ALL assets: `pscp -r public\build\assets\* ...`
2. Re-upload manifest to ROOT: `pscp ... manifest.json .../public/build/manifest.json`
3. Clear caches: `php artisan view:clear && cache:clear`
4. Re-verify HTTP 200

**Prevention:** Always upload ALL assets, not just "changed" files!

**Reference:** `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md`

---

### Problem: Production shows 500 error after deployment

**Symptoms:**
- White screen
- "Whoops, something went wrong"
- HTTP 500 response

**URGENT ACTION:**
1. **DO NOT PANIC** - This is fixable
2. **Check Laravel logs IMMEDIATELY**
3. **Consider rollback if critical**

**Diagnosis:**
```bash
# SSH and check logs
pwsh -NoProfile -Command "plink ... 'tail -n 100 domains/.../storage/logs/laravel.log'"

# Look for:
# - PHP fatal errors
# - Class not found
# - Call to undefined method
# - SQL errors
```

**Common Causes:**
- Syntax error in PHP (missed during pre-flight)
- Missing dependency (trait/class not uploaded)
- Migration failed (DB schema mismatch)
- Cache issue (old cached version)

**Solutions:**
1. If syntax error: Fix → Re-deploy
2. If missing dependency: Upload missing file → Clear cache
3. If migration failed: Check migration, fix, re-run
4. If cache: Clear ALL caches + restart PHP-FPM (if possible)

**If can't fix quickly:** Rollback!

---

### Problem: Testing reveals bugs (functions don't work)

**Symptoms:**
- Buttons don't work
- Forms don't submit
- Data not saving
- Validation not working

**Diagnosis:**
1. Browser DevTools Console → Check for JavaScript errors
2. Network tab → Check for failed requests (500, 404)
3. Laravel logs → Check for PHP errors
4. Livewire logs → Check for component errors

**Solutions:**
1. **Minor bugs (cosmetic):** Document → Fix later
2. **Medium bugs (functional but has workaround):** Document → Ask user (fix now vs later)
3. **Critical bugs (functionality broken):** Fix immediately → Re-deploy
4. **Showstopper bugs (app unusable):** Rollback → Fix thoroughly → Re-deploy

---

### Problem: Plan update confusion (what status to use?)

**Use this guide:**

| Success Rate | Status | Next Action |
|--------------|--------|-------------|
| 100% | ✅ COMPLETED | Continue to next TODO |
| 95-99% | ✅ COMPLETED WITH MINOR ISSUES | Document issues → Continue (or fix if quick) |
| 80-94% | ⚠️ DEPLOYED WITH ISSUES | Create fix plan → Fix → Re-deploy |
| 70-79% | ⚠️ PARTIAL SUCCESS | Evaluate: Fix now vs Continue with major workarounds |
| <70% | ❌ FAILED | Rollback → Analyze → Plan proper fix → Re-deploy |

**When in doubt:** Use ⚠️ status and document issues clearly

---

## 💡 BEST PRACTICES

### ✅ DO:

**Before Deployment:**
- ✅ Complete pre-flight checklist EVERY TIME
- ✅ List all files that will be deployed
- ✅ Build assets if CSS/JS changed
- ✅ Have testing plan ready

**During Deployment:**
- ✅ Upload ALL assets (not just "changed")
- ✅ Upload manifest to ROOT location
- ✅ Clear caches after every deployment
- ✅ Verify HTTP 200 for ALL assets
- ✅ Check quick smoke test (curl)

**During Testing:**
- ✅ Create specific testing checklist (not generic)
- ✅ Test EVERY item on checklist
- ✅ Document ALL issues (even minor)
- ✅ Check Laravel logs for errors
- ✅ Calculate accurate success rate

**After Testing:**
- ✅ Update plan IMMEDIATELY (while fresh)
- ✅ Be honest about success rate
- ✅ Document issues with severity
- ✅ Update TODO list with next steps
- ✅ Make clear decision (continue/fix)

**General:**
- ✅ Use this workflow EVERY TIME code changes
- ✅ Deploy often (small batches better than large)
- ✅ Test on production (only real environment)
- ✅ Keep plan current (single source of truth)

### ❌ DON'T:

**Before Deployment:**
- ❌ Skip pre-flight checklist
- ❌ Deploy untested code ("it should work")
- ❌ Deploy at end of day (no time to fix issues)
- ❌ Deploy without backup plan

**During Deployment:**
- ❌ Upload only "changed" CSS files
- ❌ Upload manifest to .vite/ subdirectory
- ❌ Skip cache clearing
- ❌ Skip HTTP 200 verification
- ❌ Assume deployment succeeded without verification

**During Testing:**
- ❌ Test without checklist (ad-hoc testing)
- ❌ Skip items on checklist
- ❌ Ignore minor issues ("will fix later")
- ❌ Skip Laravel logs check
- ❌ Inflate success rate (be honest!)

**After Testing:**
- ❌ Delay plan update ("will do later")
- ❌ Mark as completed with known issues
- ❌ Continue to next task with critical bugs
- ❌ Leave TODO list outdated
- ❌ Make no decision (limbo state)

**General:**
- ❌ "Test locally" instead of production
- ❌ Skip testing ("looks fine")
- ❌ Accumulate large changes (deploy often!)
- ❌ Keep plan outdated (plan = reality)

---

## 📖 PRZYKŁADY UŻYCIA

### Przykład 1: New Livewire Component

**Context:** Created new `ProductVariantManager` Livewire component

**Workflow:**

```markdown
Phase 0: Pre-flight Check
- [x] Files: ProductVariantManager.php + product-variant-manager.blade.php
- [x] No syntax errors (php -l passed)
- [x] Assets not changed (no CSS/JS)
- [x] Ready to deploy and test

Phase 1: Deployment (8 min)
- Upload: ProductVariantManager.php to app/Http/Livewire/Products/
- Upload: product-variant-manager.blade.php to resources/views/livewire/products/
- Clear caches: view:clear + cache:clear
- Smoke test: ✅ 200 OK

Phase 2: Manual Testing (15 min)
Checklist:
- [x] Navigate to /admin/products/variants
- [x] Component renders (no 500 error)
- [x] Layout correct (header, sidebar, content)
- [x] "Add Variant" button works
- [x] Form appears on click
- [x] Form validation works
- [x] Save creates variant in DB
- [x] Success message appears (Polish)
- [x] Variant appears in list
- [x] Edit variant works
- [x] Delete variant works (soft delete)
- [x] Laravel logs clean

Success Rate: 12/12 = 100%

Phase 3: Update Plan (5 min)
File: Plan_Projektu/ETAP_05b_Produkty_Warianty.md
Updated: Phase 6, Task 3.2 marked as ✅ COMPLETED
Progress: 60% → 65%

Phase 4: Decision (2 min)
Success Rate: 100% → CONTINUE to next TODO
Next: Task 3.3 - Variant Price Management

Total Time: 30 min
```

---

### Przykład 2: CSS Update (New Styles)

**Context:** Added new CSS for variant management styling

**Workflow:**

```markdown
Phase 0: Pre-flight Check
- [x] File: resources/css/products/variant-management.css (NEW)
- [x] npm run build executed ✅
- [x] All assets have new hashes
- [x] Ready to deploy

Phase 1: Deployment (12 min)
- Upload: variant-management.css to resources/css/products/
- Build: npm run build (✅ 2.39s)
- Upload: ALL assets from public/build/assets/* (15 files)
- Upload: manifest.json to ROOT public/build/manifest.json
- Clear caches: view:clear + cache:clear + config:clear
- HTTP 200 verification:
  - app-HASH.css → ✅ 200
  - components-HASH.css → ✅ 200
  - layout-HASH.css → ✅ 200
  - variant-management-HASH.css → ✅ 200
- Smoke test: ✅ 200 OK

Phase 2: Manual Testing (12 min)
Checklist:
- [x] Navigate to /admin/products/variants
- [x] New styles applied (buttons, cards, tables)
- [x] No unstyled elements
- [x] Responsive design works (mobile/tablet/desktop)
- [x] Colors match PPM palette (#e0ac7e)
- [x] Spacing correct (min 20px padding)
- [x] Typography correct (Inter font)
- [x] DevTools: No CSS errors
- [x] DevTools: All CSS files 200 OK
- Screenshot saved: _TOOLS/screenshots/variant-management-2025-10-30.png

Success Rate: 9/9 = 100%

Phase 3: Update Plan (5 min)
File: Plan_Projektu/ETAP_05b_Produkty_Warianty.md
Updated: CSS Task marked as ✅ COMPLETED
Added: Screenshot reference

Phase 4: Decision (2 min)
Success Rate: 100% → CONTINUE
Next: Backend methods implementation

Total Time: 31 min
```

---

### Przykład 3: Refactoring (Extract Trait)

**Context:** Extracted ProductFormVariants methods into separate trait

**Workflow:**

```markdown
Phase 0: Pre-flight Check
- [x] Files: ProductFormVariants.php (NEW), ProductForm.php (MODIFIED)
- [x] No syntax errors
- [x] Trait properly imported in ProductForm
- [x] Methods moved correctly (18 methods)
- [x] Ready to deploy and test

Phase 1: Deployment (7 min)
- Upload: ProductFormVariants.php to app/.../Traits/
- Upload: ProductForm.php (modified - added "use ProductFormVariants;")
- Clear caches: view:clear + cache:clear
- Smoke test: ✅ 200 OK

Phase 2: Manual Testing (20 min)
Checklist (Regression Testing):
- [x] Navigate to /admin/products/edit/123
- [x] Variants tab appears
- [x] createVariant() works
- [x] updateVariant() works
- [x] deleteVariant() works
- [x] duplicateVariant() works
- [x] setDefaultVariant() works
- [x] Price grid works
- [x] Stock grid works
- [x] Image upload works
- [x] All 18 methods functional
- [x] No regression (existing functionality works)
- [x] Laravel logs clean

Success Rate: 12/12 = 100%

Phase 3: Update Plan (5 min)
File: Plan_Projektu/ETAP_05b_Produkty_Warianty.md
Updated: Refactoring section added
Note: Code quality improved (300 lines → 990 lines in separate trait)
Compliance: ✅ CLAUDE.md (file size <300 lines for ProductForm.php)

Phase 4: Decision (2 min)
Success Rate: 100% → CONTINUE
Refactoring successful, no issues
Next: Continue feature development

Total Time: 34 min
```

---

### Przykład 4: Bug Fix (Issues Found)

**Context:** Fixed bug in uploadVariantImages() - PNG thumbnails failing

**Workflow:**

```markdown
Phase 0: Pre-flight Check
- [x] File: ProductFormVariants.php (MODIFIED - added GD fallback)
- [x] Fix tested locally (unable to fully test - need production)
- [x] Ready to deploy

Phase 1: Deployment (6 min)
- Upload: ProductFormVariants.php
- Clear caches
- Smoke test: ✅ 200 OK

Phase 2: Manual Testing (10 min)
Checklist (Focus on fix):
- [x] Navigate to variant image manager
- [x] Upload JPG → ✅ Works (thumbnail generated)
- [x] Upload PNG → ✅ Works NOW (was failing before)
- [x] Upload WEBP → ✅ Works
- [x] Thumbnail quality good (200x200)
- [x] Storage correct (variants/{id}/)
- [x] Database record created
- [x] Laravel logs clean (no Intervention\Image errors)

Success Rate: 8/8 = 100%

Phase 3: Update Plan (5 min)
File: Plan_Projektu/ETAP_05b_Produkty_Warianty.md
Updated: Issue 1 from "Issues Found" section → ✅ FIXED
Removed from known issues list
Fix time: 1.5h

Phase 4: Decision (2 min)
Bug fixed successfully
Other issues remain? NO (this was last issue)
→ CONTINUE to next TODO

Total Time: 23.5 min (+ 1.5h fix time = 2h total)
```

---

## 📊 MONITORING & METRICS

**Track these metrics over time:**

```markdown
DEPLOYMENT METRICS:
- Average deployment time: [X min]
- Deployment success rate: [X%]
- Average assets size: [X MB]
- Cache clear time: [X sec]

TESTING METRICS:
- Average testing time: [X min]
- Average success rate: [X%]
- Regression rate: [X%] (existing features broken)
- Bug discovery rate: [X bugs per deployment]

CYCLE METRICS:
- Average total cycle time: [X min]
- Cycles per day: [X]
- Fix rate: [X%] (deployments requiring fixes)
- Rollback rate: [X%] (deployments rolled back)

QUALITY METRICS:
- Plan accuracy: [X%] (estimated vs actual time)
- Documentation completeness: [X%]
- Issue resolution time: [X hours]
```

**Goal: Improve metrics over time through learning and optimization**

---

## 📝 CHANGELOG

### v1.0.0 (2025-10-30)
- [INIT] Universal deployment-testing-cycle skill
- [FEATURE] Complete workflow for Laravel/Livewire applications
- [FEATURE] Auto-trigger after ANY code change (new/update/refactor)
- [FEATURE] 4-phase workflow: Deploy → Test → Update → Decision
- [FEATURE] Dynamic testing checklist creation
- [FEATURE] Success rate calculation
- [FEATURE] Decision tree for next steps
- [FEATURE] Fix plan creation and loop-back mechanism
- [FEATURE] Comprehensive troubleshooting guide
- [FEATURE] 4 detailed usage examples
- [FEATURE] Best practices and anti-patterns
- [FEATURE] Metrics tracking framework
- [DOCS] Complete documentation (4000+ words)

---

## 🎯 SUMMARY

**Deployment-Testing-Cycle** to UNIWERSALNY, OBOWIĄZKOWY workflow dla projektu PPM-CC-Laravel.

**Kluczowe Zasady:**
✅ Use AUTOMATICALLY po każdej zmianie kodu
✅ Deploy FIRST, then test (production = only real environment)
✅ Complete ALL 4 phases (Deploy → Test → Update → Decision)
✅ Be HONEST about success rate (don't inflate!)
✅ Update plan IMMEDIATELY (while context fresh)
✅ Make CLEAR decision (continue/fix/rollback)
✅ LOOP BACK jeśli issues found and user chooses to fix

**Cel:**
- Consistent deployment workflow
- Reliable production testing
- Accurate project tracking
- Quick issue detection and resolution
- Continuous improvement through metrics

**Remember:** This is NOT optional - use EVERY TIME code changes! 🚀

---

**Happy Deploying & Testing! 🎉**
