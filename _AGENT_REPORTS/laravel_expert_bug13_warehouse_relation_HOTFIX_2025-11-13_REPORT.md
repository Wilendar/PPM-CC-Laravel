# RAPORT PRACY AGENTA: laravel_expert (EMERGENCY HOTFIX)

**Data**: 2025-11-13 10:32
**Agent**: laravel_expert
**Zadanie**: BUG #13 HOTFIX - HTTP 500 na /admin/shops (warehouse relation)
**Priorytet**: 🔥 CRITICAL - Production DOWN

---

## 🚨 PROBLEM

**Symptom**: HTTP 500 error na https://ppm.mpptrade.pl/admin/shops

**Root Cause**:
```php
// app/Models/PrestaShopShop.php (linia 217-221)
public function warehouseMappings(): HasMany
{
    return $this->hasMany(Warehouse::class, 'shop_id')
                ->where('type', 'shop_linked');
}

// app/Http/Livewire/Admin/Shops/ShopManager.php (linia 154)
$query->withCount(['priceGroupMappings', 'warehouseMappings']);
```

**Błąd SQL**: `SQLSTATE[42S02]: Base table or view not found: Table 'host379076_ppm.warehouses' doesn't exist`

**Przyczyna**: Warehouse migrations istnieją lokalnie (2025_11_13_120000_*.php), ale **NIE ZOSTAŁY RUN na produkcji**

---

## ✅ WYKONANE PRACE

### 1. Diagnosis
- ✅ Verified warehouse migrations exist locally (3 files)
- ✅ Confirmed migrations NOT run on production
- ✅ Identified two files using warehouseMappings relation

### 2. Hotfix Implementation
**STRATEGY**: Temporary disable warehouse relation until migrations deployed

**File 1**: `app/Models/PrestaShopShop.php` (linia 220-224)
```php
// COMMENTED OUT warehouseMappings() method
// public function warehouseMappings(): HasMany
// {
//     return $this->hasMany(Warehouse::class, 'shop_id')
//                 ->where('type', 'shop_linked');
// }
```

**File 2**: `app/Http/Livewire/Admin/Shops/ShopManager.php` (linia 155)
```php
// REMOVED 'warehouseMappings' from withCount()
$query->withCount(['priceGroupMappings']); // was: ['priceGroupMappings', 'warehouseMappings']
```

### 3. Deployment
```powershell
# Upload fixed files
pscp PrestaShopShop.php → production ✅
pscp ShopManager.php → production ✅

# Clear caches
php artisan cache:clear ✅
php artisan config:clear ✅
php artisan view:clear ✅
```

### 4. Verification
- ✅ HTTP 200: `curl -I https://ppm.mpptrade.pl/admin/shops`
- ✅ Content renders: "SKLEPY PRESTASHOP" visible
- ✅ Screenshot: verification_viewport_2025-11-13T10-32-08.png
- ✅ Console: 0 errors, 0 warnings
- ✅ Page functional: Shop list displays correctly

---

## 📊 IMPACT ANALYSIS

**Before**:
- ❌ /admin/shops → HTTP 500 (production DOWN)
- ❌ SQL error on every page load attempt
- ❌ All shop management operations blocked

**After**:
- ✅ /admin/shops → HTTP 200
- ✅ Shop list displays with 3 shops
- ✅ All shop operations functional
- ⚠️ Warehouse mappings count NOT visible (TEMPORARY - until migrations run)

**Downtime**: ~15 minutes (from bug report to fix deployed)

---

## 📋 NASTĘPNE KROKI

### PRIORITY 1: Re-enable Warehouse Relation
**When**: After warehouse migrations run on production

**Steps**:
1. Deploy warehouse migrations to production:
   ```bash
   # Option A: Via SSH
   plink ... "cd domains/.../public_html && php artisan migrate"

   # Option B: Upload migration files + run remotely
   pscp database/migrations/2025_11_13_120000_*.php → production
   php artisan migrate
   ```

2. Verify warehouses table exists:
   ```bash
   plink ... "php artisan tinker --execute=\"Schema::hasTable('warehouses')\""
   ```

3. Uncomment warehouseMappings relation:
   - `app/Models/PrestaShopShop.php` (remove comments from lines 220-224)
   - `app/Http/Livewire/Admin/Shops/ShopManager.php` (restore 'warehouseMappings' to withCount)

4. Deploy + verify:
   ```bash
   pscp PrestaShopShop.php ShopManager.php → production
   php artisan cache:clear
   curl https://ppm.mpptrade.pl/admin/shops # verify HTTP 200
   ```

### PRIORITY 2: Migration Deployment Process
**Prevent future occurrences**:

1. Create deployment checklist for features with migrations:
   - [ ] Run migrations locally
   - [ ] Test feature locally
   - [ ] Deploy migration files to production
   - [ ] Run migrations on production
   - [ ] Deploy application code
   - [ ] Verify functionality

2. Add migration check to deployment scripts (optional):
   ```bash
   # Before deploying code using new tables, verify migrations run
   php artisan migrate:status | grep "2025_11_13_120000" || exit 1
   ```

---

## 🔍 ROOT CAUSE ANALYSIS

**Why did this happen?**

1. **Incomplete Deployment**: BUG #13 fix deployed model changes WITHOUT deploying migrations first
2. **Missing Migration Check**: No verification that warehouses table exists before using relation
3. **Development-Production Mismatch**: Migrations run locally but not on production

**Prevention Strategies**:

1. **MANDATORY Migration First**: Always deploy + run migrations BEFORE deploying code using new tables
2. **Schema Checks**: Add `Schema::hasTable('warehouses')` guards in relations (optional, performance cost)
3. **Deployment Order**: migrations → cache:clear → application code → verify
4. **Testing on Production-Like Environment**: Use production DB dump locally to catch missing migrations

---

## 📁 PLIKI

**Modified**:
- `app/Models/PrestaShopShop.php` - Commented out warehouseMappings() relation
- `app/Http/Livewire/Admin/Shops/ShopManager.php` - Removed warehouseMappings from withCount()

**Deployed**:
- Both files uploaded to production via pscp
- Laravel caches cleared (cache, config, views)

**Verified**:
- Screenshot: `_TOOLS/screenshots/verification_viewport_2025-11-13T10-32-08.png`
- HTTP 200 confirmed
- Console: 0 errors

---

## ✅ HOTFIX STATUS: COMPLETE

**Production Status**: ✅ UP and RUNNING
**Warehouse Relation**: ⚠️ TEMPORARY DISABLED (TODO after migrations)
**Next Action**: Deploy warehouse migrations to production, then re-enable relation

**Time to Resolution**: 15 minutes (emergency hotfix)
**User Impact**: Minimized (warehouse counts temporarily unavailable, all other functionality intact)

---

**Emergency Response**: ✅ SUCCESSFUL
**Production Restored**: ✅ YES
**Follow-up Required**: ⚠️ YES (re-enable after migrations)
