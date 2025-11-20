# MANUAL TEST: Zapisywanie kategorii w zakładce sklepu

**Data:** 2025-11-20
**Cel:** Weryfikacja poprawki FIX #1 - zapis kategorii PrestaShop w zakładce sklepu
**Bug:** Kategorie nie zapisywały się do bazy danych (foreign key constraint)
**Fix:** ProductFormSaver - normalizacja PrestaShop IDs → PPM IDs przed walidacją

---

## ✅ PRZYGOTOWANIE

**1. Verify mappings exist (DONE):**
```
✅ PS ID 1 → PPM ID 1 (Baza)
✅ PS ID 2 → PPM ID 36 (Wszystko)
✅ PS ID 12 → PPM ID 41 (PITGANG)
✅ PS ID 23 → PPM ID 43 (Pit Bike)
✅ PS ID 800 → PPM ID 42 (Pojazdy)
```

**2. Deployed files:**
- ✅ ProductFormSaver.php (fromPrestaShopFormat + auto-inject roots)
- ✅ Cache cleared

**3. Test product:**
- Product ID: 11034 (SKU: Test Product)
- Shop: B2B Test DEV (shop_id=1)

---

## 🧪 TEST SCENARIO 1: Zapisz pojedynczą kategorię

**Steps:**
1. Otwórz produkt 11034: https://ppm.mpptrade.pl/admin/products/11034/edit
2. Przejdź do zakładki **"B2B Test DEV"** (shop tab)
3. W sekcji **"Kategorie produktu"**:
   - Odznacz wszystkie kategorie (jeśli są zaznaczone)
   - Zaznacz **TYLKO** kategorię **"PITGANG"** (PS ID 12)
4. Kliknij **"Zapisz zmiany"**
5. Poczekaj na komunikat sukcesu

**Expected:**
- ✅ Komunikat: "Produkt został zapisany pomyślnie"
- ✅ BRAK błędu: "foreign key constraint fails"
- ✅ Strona odświeża się, kategoria "PITGANG" nadal zaznaczona

**Verify in database:**
```bash
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"
\$psd = DB::table('product_shop_data')->where('product_id', 11034)->where('shop_id', 1)->first();
echo 'category_mappings: ' . \$psd->category_mappings . PHP_EOL;
\""
```

**Expected output:**
```json
{
  "ui": {
    "selected": [1, 36, 41],
    "primary": 1
  },
  "mappings": {
    "1": 1,
    "36": 2,
    "41": 12
  },
  "metadata": {
    "last_updated": "2025-11-20T...",
    "source": "manual"
  }
}
```

**Legend:**
- PPM ID 1 = Root "Baza" (auto-injected)
- PPM ID 36 = Root "Wszystko" (auto-injected)
- PPM ID 41 = User selected "PITGANG"

---

## 🧪 TEST SCENARIO 2: Zapisz wiele kategorii

**Steps:**
1. W tej samej zakładce **"B2B Test DEV"**
2. Zaznacz **dodatkowo**:
   - "Pit Bike" (PS ID 23)
   - "Pojazdy" (PS ID 800)
3. Kliknij **"Zapisz zmiany"**

**Expected:**
- ✅ Komunikat sukcesu
- ✅ Wszystkie 3 kategorie zaznaczone (PITGANG + Pit Bike + Pojazdy)

**Verify in database:**
```bash
plink ... "php artisan tinker --execute=\"
\$psd = DB::table('product_shop_data')->where('product_id', 11034)->where('shop_id', 1)->first();
echo json_encode(json_decode(\$psd->category_mappings), JSON_PRETTY_PRINT);
\""
```

**Expected:**
```json
{
  "ui": {
    "selected": [1, 36, 41, 43, 42],
    "primary": 1
  },
  "mappings": {
    "1": 1,
    "36": 2,
    "41": 12,
    "43": 23,
    "42": 800
  }
}
```

---

## 🧪 TEST SCENARIO 3: Odznacz wszystkie kategorie

**Steps:**
1. Odznacz WSZYSTKIE kategorie (również PITGANG, Pit Bike, Pojazdy)
2. Kliknij **"Zapisz zmiany"**

**Expected:**
- ✅ Komunikat sukcesu
- ✅ Tylko rooty auto-injected (Baza + Wszystko)

**Verify:**
```json
{
  "ui": {
    "selected": [1, 36],
    "primary": 1
  },
  "mappings": {
    "1": 1,
    "36": 2
  }
}
```

---

## 🧪 TEST SCENARIO 4: Sync job creation

**Steps:**
1. Po zapisaniu kategorii (Scenario 2), kliknij **"Aktualizuj aktualny sklep"**
2. Poczekaj na job processing

**Verify job:**
```bash
plink ... "php artisan tinker --execute=\"
\$job = DB::table('sync_jobs')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->orderBy('id', 'desc')
    ->first();
echo 'Job ID: ' . \$job->id . PHP_EOL;
echo 'Status: ' . \$job->status . PHP_EOL;
echo 'Fields: ' . \$job->fields_to_sync . PHP_EOL;
\""
```

**Expected:**
- ✅ Job created with status "pending" or "processing"
- ✅ fields_to_sync contains "categories"

---

## ⚠️ TROUBLESHOOTING

**Jeśli błąd "foreign key constraint":**
1. Check logs:
   ```bash
   plink ... "tail -100 domains/.../storage/logs/laravel.log | grep -A 5 'SQLSTATE\[23000\]'"
   ```
2. Verify `fromPrestaShopFormat()` is called (not `fromUiFormat()`)
3. Check if mappings exist for selected categories

**Jeśli kategorie nie zapisują się:**
1. Check logs:
   ```bash
   plink ... "tail -100 storage/logs/laravel.log | grep -A 10 'ETAP_07b.*ProductFormSaver'"
   ```
2. Verify CategoryMapper returns valid PPM IDs
3. Check if CategoryMappingsValidator passes

**Jeśli auto-inject nie działa:**
1. Verify mappings for PS ID 1 & 2 exist
2. Check ProductFormSaver lines 236-243 (auto-inject logic)

---

## 📊 SUCCESS CRITERIA

✅ Wszystkie 4 scenariusze zakończone sukcesem
✅ BRAK błędów foreign key constraint
✅ category_mappings zapisane w canonical format (Option A)
✅ Auto-inject roots działa (PPM 1 + 36)
✅ Sync job utworzony poprawnie

---

## 📝 NOTES

**Auto-inject behavior:**
- Roots (Baza + Wszystko) są ZAWSZE dodawane automatycznie
- User nie musi ich zaznaczać w UI
- Są ukryte w UI ale obecne w JSON

**Mapping lookup:**
- PrestaShop ID → PPM ID via CategoryMapper
- Brakujące mappings są skipowane (log warning)
- Sync job będzie próbował je utworzyć później

**Next steps after success:**
- Fix #2: ProductMultiStoreManager - ładuj PrestaShop IDs do UI
- Verify sync job actually syncs categories to PrestaShop
- Test on real products (not just test product)
