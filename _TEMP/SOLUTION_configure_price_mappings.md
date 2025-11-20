# ROZWIĄZANIE: Konfiguracja Mapowań Grup Cenowych

**Problem**: Brak specific_prices w PrestaShop - produkty synchronizowane BEZ cen grupowych
**Przyczyna**: Sklep "B2B Test DEV" nie ma skonfigurowanych mapowań grup cenowych (Price Group Mappings)
**Sklep**: B2B Test DEV (ID: 1) - https://dev.mpptrade.pl/

---

## OPCJA 1: Przez UI (ZALECANA)

### Krok 1: Otwórz formularz edycji sklepu

```
Admin → Shops → Edit "B2B Test DEV"
```

### Krok 2: Przejdź do Step 4: Price Group Mapping

Kliknij w nawigacji wizarda na "Step 4" lub przewiń do sekcji "Mapowanie grup cenowych"

### Krok 3: Pobierz grupy cenowe z PrestaShop

Kliknij przycisk: **"Pobierz grupy cenowe z PrestaShop"**

System automatycznie pobierze listę grup klientów (customer groups) z PrestaShop API.

### Krok 4: Zmapuj grupy PPM → PrestaShop

Dla każdej grupy PrestaShop wybierz odpowiadającą grupę PPM z dropdown:

**Przykładowe mapowanie** (dostosuj do swoich potrzeb):

| Grupa PrestaShop | ID | Grupa PPM |
|-----------------|-----|-----------|
| Customer | 3 | Detaliczna (retail) |
| Dealer | 4 | Dealer Standard |
| Premium | 5 | Dealer Premium |
| Workshop | 6 | Warsztat Standard |

**Uwaga**: NIE musisz mapować WSZYSTKICH grup - zmapuj tylko te, które faktycznie używasz w PrestaShop.

### Krok 5: Zapisz

Kliknij "Next Step" lub "Save" w wizardzie.

System automatycznie utworzy rekordy w tabeli `shop_mappings`.

### Krok 6: Re-sync produkty testowe

Po zapisaniu mapowań, poczekaj na automatyczną synchronizację lub ręcznie uruchom:

```bash
# Na serwerze produkcyjnym (SSH)
cd domains/ppm.mpptrade.pl/public_html
php artisan queue:work --queue=prestashop_sync --once
```

Lub przez UI:
```
Admin → Shops → B2B Test DEV → Sync Products
```

---

## OPCJA 2: Przez Tinker (AWARYJNE)

**Użyj jeśli UI nie działa lub potrzebujesz szybkiego fix-u**

### Krok 1: Połącz się z serwerem

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey
```

### Krok 2: Uruchom Tinker

```bash
cd domains/ppm.mpptrade.pl/public_html
php artisan tinker
```

### Krok 3: Utwórz mapowania

**WAŻNE**: Najpierw sprawdź jakie grupy klientów są w PrestaShop!

```php
// Pobierz sklep
$shop = PrestaShopShop::find(1);

// Test połączenia
$client = PrestaShopClientFactory::create($shop);
$groups = $client->getCustomerGroups();

// Wyświetl grupy PrestaShop
foreach ($groups as $g) {
    echo "ID: {$g['id']}, Name: {$g['name']}\n";
}
```

**Następnie utwórz mapowania:**

```php
// Inicjalizuj mapper
$mapper = app(PriceGroupMapper::class);

// Mapowanie 1: Detaliczna (PPM ID: 1) → Customer (PrestaShop ID: 3)
$mapper->createMapping(1, $shop, 3, 'Customer');

// Mapowanie 2: Dealer Standard (PPM ID: 2) → Dealer (PrestaShop ID: 4)
$mapper->createMapping(2, $shop, 4, 'Dealer');

// Mapowanie 3: Dealer Premium (PPM ID: 3) → Premium (PrestaShop ID: 5)
$mapper->createMapping(3, $shop, 5, 'Premium');

// Dodaj więcej mapowań według potrzeb...

echo "Mappings created!\n";
```

**Weryfikacja:**

```php
// Sprawdź utworzone mapowania
$mappings = $mapper->getAllMappingsForShop($shop);
foreach ($mappings as $m) {
    echo "PPM: {$m->ppm_value} → PS: {$m->prestashop_id}\n";
}
```

### Krok 4: Re-sync produkty

```php
// Option A: Pojedynczy produkt
$product = Product::find(11033); // PB-KAYO-E-KMB
SyncProductToPrestaShop::dispatch($product, $shop);

// Option B: Wszystkie produkty sklepu
BulkSyncProducts::dispatch($shop);
```

Lub z wiersza poleceń:

```bash
php artisan queue:work --queue=prestashop_sync --once
```

---

## WERYFIKACJA

### Krok 1: Sprawdź logi Laravel

```bash
tail -50 storage/logs/laravel.log | grep "PRICE EXPORT"
```

**Oczekiwane wyniki:**
```
[PRICE EXPORT] Price export completed {"created":3,"updated":0,"deleted":0,"skipped":0}
```

- `created > 0` = SUCCESS! ✅
- `skipped = 0` = Wszystkie grupy zmapowane ✅

### Krok 2: Sprawdź bazę PrestaShop

```sql
SELECT * FROM ps_specific_price WHERE id_product IN (1830, 1831);
```

Powinny istnieć rekordy dla każdej zmapowanej grupy cenowej.

### Krok 3: Sprawdź UI PrestaShop

```
PrestaShop Admin → Catalog → Products → "PB-KAYO-E-KMB" → Prices tab
```

Powinny być widoczne ceny dla grup klientów (Customer group prices).

---

## DOSTĘPNE GRUPY CENOWE W PPM

```
ID: 1, Code: retail, Name: Detaliczna
ID: 2, Code: dealer_standard, Name: Dealer Standard
ID: 3, Code: dealer_premium, Name: Dealer Premium
ID: 4, Code: workshop_std, Name: Warsztat Standard
ID: 5, Code: workshop_premium, Name: Warsztat Premium
ID: 6, Code: school_drop, Name: Szkółka-Komis-Drop
ID: 7, Code: employee, Name: Pracownik
```

**Uwaga**: Mapuj tylko te grupy, które faktycznie używasz!

---

## TYPOWE GRUPY W PRESTASHOP (DEFAULT)

```
ID: 1, Name: Visitor (nie mapuj - użytkownicy niezalogowani)
ID: 2, Name: Guest (nie mapuj - goście)
ID: 3, Name: Customer (mapuj na Detaliczna)
```

Dodatkowe grupy (jeśli utworzone w PrestaShop):
- Dealer, Premium, Workshop, itp.

**WAŻNE**: ID grup w PrestaShop mogą się różnić w zależności od konfiguracji!

---

## TROUBLESHOOTING

### Problem: "Nie mogę znaleźć Step 4 w wizardzie edycji sklepu"

**Rozwiązanie**: Użyj OPCJA 2 (Tinker) do ręcznego utworzenia mapowań.

### Problem: "Fetch price groups zwraca błąd"

**Możliwe przyczyny:**
- Niepoprawny API key
- PrestaShop API nie ma uprawnień do customer_groups
- Firewall blokuje połączenie

**Rozwiązanie**: Sprawdź logi Laravel dla szczegółów błędu, użyj Tinker do manualnego mapowania.

### Problem: "Po re-sync nadal brak specific_prices"

**Sprawdź:**
1. Cache: `php artisan cache:clear`
2. Logi: `tail -100 storage/logs/laravel.log | grep "PRICE EXPORT"`
3. Czy produkt ma product_prices w PPM: `Product::find(11033)->prices()->count()`

---

## KONTAKT

Jeśli napotkasz problemy:
- Sprawdź pełny raport diagnostyczny: `_AGENT_REPORTS/debugger_specific_prices_missing_2025-11-14_REPORT.md`
- Uruchom diagnostic script: `pwsh _TEMP/check_price_mappings_prod.ps1`

---

**Data utworzenia**: 2025-11-14
**Agent**: debugger
**Issue**: PRESTASHOP_PRICE_SYNC_MISSING_MAPPINGS
