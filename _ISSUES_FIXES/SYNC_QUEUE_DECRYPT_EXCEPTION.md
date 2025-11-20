# SYNC QUEUE - DECRYPT EXCEPTION

**Data zgłoszenia:** 2025-11-05
**Status:** ✅ ROZWIĄZANY
**Priorytet:** 🔴 KRYTYCZNY
**Kategoria:** Queue System, Encryption, PrestaShop Integration
**Czas debugowania:** ~1h

---

## 📋 PROBLEM

### Objawy

```
Synchronizacje utknęły na statusie "Oczekuje"
Queue jobs failują z błędem:
Illuminate\Contracts\Encryption\DecryptException: The payload is invalid.
```

Produkty pozostają w statusie "pending" mimo że queue worker działa.

### Kontekst

- **ETAP_07 FAZA 3B** - Export/Sync PPM → PrestaShop
- Po naprawieniu "quantity not writable" error
- Queue worker działa prawidłowo
- Jobs są dispatched i uruchamiane
- Ale failują przy próbie odczytu `$shop->api_key`

---

## 🔍 ROOT CAUSE ANALYSIS

### Przyczyna #1: DecryptException w PrestaShopShop Model

**PrestaShopShop.php:258** używa akcesora z `decrypt()`:

```php
protected function apiKey(): Attribute
{
    return Attribute::make(
        get: fn (string $value) => decrypt($value),
        set: fn (string $value) => encrypt($value),
    );
}
```

**Problem:**
- API keys były zaszyfrowane za pomocą **starego APP_KEY**
- APP_KEY na produkcji **zmienił się** (regeneracja lub zmiana .env)
- Laravel nie może zdekryptować starych wartości
- `decrypt($value)` rzuca `DecryptException: The payload is invalid`

### Przyczyna #2: Missing Import w ShopManager

**ShopManager.php:766** używał `SyncJob` class bez importu:

```php
// ❌ BŁĄD - brak use App\Models\SyncJob;
$activeSyncJobs = SyncJob::where('target_type', SyncJob::TYPE_PRESTASHOP)
```

**Efekt:**
- Error: `Class "App\Http\Livewire\Admin\Shops\SyncJob" not found`
- Nie można usunąć sklepów z UI
- Nie można zarządzać shopami przez admin panel

---

## ✅ ROZWIĄZANIE

### Fix #1: Missing Import w ShopManager

**app/Http/Livewire/Admin/Shops/ShopManager.php:8**

```php
namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PrestaShopShop;
use App\Models\SyncJob;  // ✅ DODANE
use App\Services\PrestaShop\PrestaShopSyncService;
```

### Fix #2: Usunięcie Shops z Broken API Keys

**Identyfikacja problemowych shopów:**

| Shop ID | Nazwa | Decrypt Status | Akcja |
|---------|-------|----------------|-------|
| 1 | B2B Test DEV | ✅ OK | Pozostawiony |
| 2 | Test Shop 1 | ❌ FAILED | ❌ Usunięty |
| 3 | Test Shop 2 | ❌ FAILED | ❌ Usunięty |
| 4 | Demo Shop | ❌ FAILED | ❌ Usunięty |
| 5 | Test KAYO | ✅ OK | Pozostawiony |
| 6 | TEST YCF | ✅ OK | Pozostawiony |

**Shops #2, #3, #4 usunięte** przez panel admin/shops po naprawieniu ShopManager.

---

## 📊 IMPACT

### Przed Fix

- ❌ Synchronizacje stuck na "Oczekuje"
- ❌ Queue jobs failują z DecryptException
- ❌ Nie można usunąć sklepów z UI
- ❌ 3 shops z broken encryption blokują wszystkie sync operations

### Po Fix

- ✅ Synchronizacje działają poprawnie
- ✅ Queue jobs complete successfully
- ✅ Można zarządzać shopami przez UI
- ✅ Tylko shops z działającymi API keys (#1, #5, #6)

### Przykład z Logów (Po Fix)

```
[2025-11-05 13:12:12] Product sync job started
  {"product_id":10986,"shop_id":1,"shop_name":"B2B Test DEV"}

[2025-11-05 13:12:13] Product synced successfully to PrestaShop
  {"product_id":10986,"shop_id":1,"external_id":9759,"operation":"update"}

[2025-11-05 13:12:13] Product sync job completed successfully
  {"execution_time_ms":174.95}

[2025-11-05 13:12:13] Product sync job started
  {"product_id":10986,"shop_id":5,"shop_name":"Test KAYO"}

[2025-11-05 13:12:13] Product synced successfully to PrestaShop
  {"product_id":10986,"shop_id":5,"external_id":4019,"operation":"update"}

[2025-11-05 13:12:13] Product sync job completed successfully
  {"execution_time_ms":63.32}
```

**BRAK błędów DecryptException!** ✅

### Files Modified

```
app/Http/Livewire/Admin/Shops/ShopManager.php (linia 8)
_ISSUES_FIXES/SYNC_QUEUE_DECRYPT_EXCEPTION.md (nowy plik)
_TOOLS/fix_encrypted_api_keys.php (diagnostic script)
_TOOLS/check_shops_status.php (diagnostic script)
```

---

## 🔧 ENCRYPTION KEY MANAGEMENT

### Jak Uniknąć Problemu w Przyszłości

**Nigdy nie zmieniaj APP_KEY na produkcji bez re-enkrypcji danych!**

1. **Przed zmianą APP_KEY:**
   - Backup wszystkich encrypted fields (api_key, passwords, tokens)
   - Zapisz plain-text values w bezpiecznym miejscu

2. **Po zmianie APP_KEY:**
   - Re-enkryptuj wszystkie wartości używając nowego klucza
   - Test decrypt dla wszystkich encrypted fields

3. **Deployment workflow:**
   ```bash
   # 1. Backup encrypted data
   php artisan tinker < backup_encrypted_data.php

   # 2. Deploy nowy .env z nowym APP_KEY

   # 3. Re-encrypt data
   php artisan tinker < reencrypt_data.php

   # 4. Verify
   php artisan tinker < verify_decrypt.php
   ```

### Diagnostic Script

**_TOOLS/check_shops_status.php:**

```php
$shops = DB::table('prestashop_shops')
    ->select('id', 'name', 'api_key')
    ->get();

foreach ($shops as $shop) {
    try {
        decrypt($shop->api_key);
        echo "Shop #{$shop->id}: ✅ Decrypt OK\n";
    } catch (\Exception $e) {
        echo "Shop #{$shop->id}: ❌ Decrypt FAILED\n";
    }
}
```

Run po każdej zmianie APP_KEY!

---

## 🚨 PREVENTION CHECKLIST

**Przed deployment na produkcję:**

- [ ] Sprawdź czy APP_KEY jest ten sam co w deployment poprzednim
- [ ] Jeśli APP_KEY zmieniony - zaplanuj re-enkrypcję
- [ ] Test decrypt dla wszystkich models z encrypted fields:
  - [ ] PrestaShopShop::api_key
  - [ ] User::password (jeśli custom encryption)
  - [ ] IntegrationSettings (jeśli encrypted)
- [ ] Uruchom diagnostic script: `_TOOLS/check_shops_status.php`

**Po deployment na produkcję:**

- [ ] Verify queue jobs działają (check logs)
- [ ] Verify brak DecryptException w logach
- [ ] Test sync operations przez UI
- [ ] Monitor failed_jobs table

---

## 📚 REFERENCES

**Dokumentacja:**
- [Laravel Encryption](https://laravel.com/docs/12.x/encryption) - APP_KEY management
- [Laravel Queue](https://laravel.com/docs/12.x/queues) - Job error handling
- [Eloquent Mutators](https://laravel.com/docs/12.x/eloquent-mutators) - Attribute casting

**Related Files:**
- `app/Models/PrestaShopShop.php` - Model z encrypted api_key
- `app/Http/Livewire/Admin/Shops/ShopManager.php` - Shop management UI
- `app/Jobs/PrestaShop/SyncProductToPrestaShop.php` - Sync job that failed

**Related Issues:**
- [PRESTASHOP_QUANTITY_READONLY_FIELD.md](_ISSUES_FIXES/PRESTASHOP_QUANTITY_READONLY_FIELD.md) - Previous sync issue

---

## ✅ VERIFICATION

**Test Case 1: Decrypt All Shops**

```bash
php artisan tinker < _TOOLS/check_shops_status.php
```

Expected output:
```
Shop #1: ✅ Decrypt OK
Shop #5: ✅ Decrypt OK
Shop #6: ✅ Decrypt OK
```

**Test Case 2: Sync Job Completion**

```bash
# Trigger sync z UI
# Check logs:
tail -f storage/logs/laravel.log | grep "sync"
```

Expected:
- `Product sync job started`
- `Product synced successfully to PrestaShop`
- `Product sync job completed successfully`
- NO `DecryptException`

**Test Case 3: Shop Management UI**

1. Navigate to `/admin/shops`
2. Click "Usuń" on any shop
3. Expected: Shop deleted successfully
4. Expected: NO "Class SyncJob not found" error

---

## 📝 LESSONS LEARNED

1. **Always import classes explicitly** - PHP nie rzuca błędu do runtime
2. **APP_KEY changes are destructive** - wymaga migration wszystkich encrypted data
3. **Diagnostic scripts są essential** - pozwalają szybko zidentyfikować problem
4. **Queue failures mogą być silent** - trzeba active monitoring logów
5. **Encryption errors are cascading** - jeden broken shop blokuje wszystkie operations

---

**Author:** Claude Code AI (PPM-CC-Laravel)
**Reviewed:** Kamil Wiliński
**Status:** ✅ Verified Working (2025-11-05 13:12)
**Next Steps:**
- Monitor queue health
- Document APP_KEY change procedure
- Consider encrypted field migration strategy
