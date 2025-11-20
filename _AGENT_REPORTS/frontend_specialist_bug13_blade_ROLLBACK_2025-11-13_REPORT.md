# RAPORT PRACY AGENTA: frontend-specialist
**Data**: 2025-11-13 12:30
**Agent**: frontend-specialist
**Zadanie**: Update Blade template dla Bug #13 (mappings count) - **ROLLBACK REQUIRED**

---

## 🚨 CRITICAL: PRODUCTION DOWN - HTTP 500

### ❌ PROBLEM ZIDENTYFIKOWANY

**Backend Error:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'warehouses.shop_id' in 'WHERE'
SQL: select prestashop_shops.*,
     (select count(*) from warehouses
      where prestashop_shops.id = warehouses.shop_id
      and type = shop_linked) as warehouse_mappings_count
```

**Root Cause:** laravel-expert zaimplementował relację `warehouseMappings()` z niewłaściwym foreign key:

```php
// ❌ BŁĄD w app/Models/PrestaShopShop.php (linia 219)
public function warehouseMappings(): HasMany
{
    return $this->hasMany(Warehouse::class, 'shop_id')  // ❌ warehouses.shop_id NIE ISTNIEJE!
                ->where('type', 'shop_linked');
}
```

**Rzeczywista struktura database:**
- ✅ `warehouses` table: NIE MA kolumny `shop_id`
- ✅ `shop_mappings` table: pivot table z `shop_id`, `mapping_type`, `ppm_value`
- ✅ `prestashop_shop_price_mappings` table: dedykowana dla price mappings (dobrze działa)

**Warehouses table columns:**
```
id, name, code, address, city, postal_code, country, is_default, is_active,
sort_order, allow_negative_stock, auto_reserve_stock, default_minimum_stock,
prestashop_mapping (JSON), erp_mapping (JSON), contact_person, phone, email,
operating_hours, special_instructions, notes, created_at, updated_at
```

**shop_mappings table columns:**
```
id, shop_id, mapping_type, ppm_value, prestashop_id, prestashop_value,
is_active, created_at, updated_at
```

---

## ✅ WYKONANE PRACE

### 1. Blade Template Update (✅ DONE - ale bez efektu przez backend error)

**Zmiany:**
- Desktop table: `{{ is_array($shop->price_group_mappings) ? count(...) : 0 }}` → `{{ $shop->price_group_mappings_count ?? 0 }}`
- Desktop table: `{{ is_array($shop->warehouse_mappings) ? count(...) : 0 }}` → `{{ $shop->warehouse_mappings_count ?? 0 }}`
- Mobile cards: Analogiczne zmiany

**Deployment:**
- ✅ Upload: `pscp shop-manager.blade.php`
- ✅ Cache clear: `php artisan view:clear`
- ❌ HTTP 500 po deployment

### 2. Error Investigation (✅ DONE)

**Kroki:**
1. ✅ Verify HTTP response: `curl -I` → HTTP 500
2. ✅ Check Laravel logs: `tail -200 laravel.log`
3. ✅ Identify SQL error: Column `warehouses.shop_id` not found
4. ✅ Verify table structure: `Schema::getColumnListing('warehouses')`
5. ✅ Find pivot table: `shop_mappings` z `mapping_type`

### 3. Rollback (✅ DONE)

**Actions:**
- ✅ Save changes: `git diff > _TEMP/blade_changes_bug13.patch`
- ✅ Rollback Blade: `git checkout shop-manager.blade.php`
- ✅ Upload old Blade: `pscp shop-manager.blade.php`
- ✅ Clear cache: `php artisan cache:clear`
- ❌ Still HTTP 500 (problem jest w backend PHP, nie w Blade)

### 4. Root Cause Analysis (✅ DONE)

**Znaleziono:**
- ❌ `app/Models/PrestaShopShop.php` (linia 217-221): `warehouseMappings()` uses `Warehouse::class, 'shop_id'`
- ❌ `app/Http/Livewire/Admin/Shops/ShopManager.php` (linia 154): `withCount(['warehouseMappings'])`

**Production verification:**
```bash
grep -n "withCount" domains/.../ShopManager.php
# 154:        $query->withCount(['priceGroupMappings', 'warehouseMappings']);
```

Backend został zdeployowany przez laravel-expert i to jest źródło błędu.

---

## ⚠️ PROBLEMY/BLOKERY

### 🚨 CRITICAL: Production Down

**Status:** HTTP 500 na `/admin/shops`
**Impact:** Użytkownik nie może zarządzać sklepami PrestaShop
**Blocker:** Backend model używa niewłaściwej relacji

### NIEPRAWIDŁOWA RELACJA: warehouseMappings()

**Problem:**
```php
// ❌ NIEPRAWIDŁOWE (app/Models/PrestaShopShop.php)
public function warehouseMappings(): HasMany
{
    return $this->hasMany(Warehouse::class, 'shop_id')  // Kolumna NIE ISTNIEJE!
                ->where('type', 'shop_linked');
}
```

**Powód błędu:**
1. `warehouses` table NIE MA kolumny `shop_id`
2. Mapping działa przez `shop_mappings` pivot table
3. `shop_mappings.mapping_type` określa typ (warehouse, price_group, category)

**Prawidłowa struktura (wymagane):**

**OPTION A: Użyj shop_mappings (RECOMMENDED)**
```php
public function warehouseMappings(): HasMany
{
    return $this->hasMany(ShopMapping::class, 'shop_id')
                ->where('mapping_type', 'warehouse')
                ->where('is_active', true);
}
```

**OPTION B: JSON parsing (fallback jeśli brak shop_mappings)**
```php
// Jeśli shop_mappings nie zawiera warehouse mappings,
// mogą być przechowywane w warehouses.prestashop_mapping JSON column
```

**Verification needed:**
```sql
-- Check if warehouse mappings exist in shop_mappings
SELECT COUNT(*) FROM shop_mappings
WHERE mapping_type = 'warehouse';

-- Check structure of warehouses.prestashop_mapping
SELECT prestashop_mapping FROM warehouses LIMIT 1;
```

---

## 📋 NASTĘPNE KROKI

### 🔥 URGENT: Fix Backend Relation (laravel-expert)

**Task:** Napraw `warehouseMappings()` relation w `app/Models/PrestaShopShop.php`

**Actions:**
1. Verify mapping structure:
   - Check `shop_mappings` table dla `mapping_type = 'warehouse'`
   - Check `warehouses.prestashop_mapping` JSON structure
2. Implement correct relation (OPTION A or B)
3. Update `ShopManager.php` query jeśli potrzeba
4. Test locally
5. Deploy to production
6. Verify HTTP 200

**Priority:** 🔥🔥🔥 CRITICAL - Production down!

### THEN: Re-apply Blade Changes (frontend-specialist)

Po naprawie backend:
1. Apply patch: `git apply _TEMP/blade_changes_bug13.patch`
2. Upload Blade template
3. Clear cache
4. Verify counts display correctly

---

## 📁 PLIKI

### Created:
- `_TOOLS/verify_mappings_count_fix.cjs` - Playwright verification script (not used - production down)
- `_TEMP/blade_changes_bug13.patch` - Saved Blade changes for re-apply
- `_AGENT_REPORTS/frontend_specialist_bug13_blade_ROLLBACK_2025-11-13_REPORT.md` - Ten raport

### Modified (rolled back):
- `resources/views/livewire/admin/shops/shop-manager.blade.php` - Rollback to previous version

### Production Status:
- ❌ `/admin/shops` → HTTP 500
- ❌ Backend relation error: `warehouses.shop_id` not found
- ⚠️ Requires laravel-expert fix URGENTLY

---

## 📊 STATISTICS

**Time spent:** ~30 min
**Files modified:** 1 (rolled back)
**Tests run:** 0 (production down prevents testing)
**Deployment:** ✅ Upload successful, ❌ Runtime error
**Production status:** 🚨 DOWN - HTTP 500

---

## 🎯 SUCCESS CRITERIA (NOT MET)

- ❌ Desktop table shows correct mappings counts
- ❌ Mobile cards show correct mappings counts
- ❌ No console errors
- ❌ No HTTP errors
- ❌ User confirms correct display

**Blocked by:** Backend relation error (warehouses.shop_id)

---

## 📝 NOTES

**For laravel-expert:**
- Blade template changes są POPRAWNE i gotowe do re-apply
- Problem jest WYŁĄCZNIE w backend relation
- `priceGroupMappings()` działa dobrze (używa dedykowanej tabeli)
- `warehouseMappings()` potrzebuje fixa (niewłaściwy foreign key)
- Patch saved in `_TEMP/blade_changes_bug13.patch` for later

**Database structure insights:**
- `shop_mappings` to universal pivot table (category, warehouse, price_group)
- `prestashop_shop_price_mappings` to dedykowana tabela (działa dobrze)
- `warehouses.prestashop_mapping` to JSON column (może być fallback)

**Resolution path:**
1. laravel-expert fixes `warehouseMappings()` relation
2. Deploy backend fix
3. Verify HTTP 200
4. frontend-specialist applies Blade patch
5. Test & verify counts display
