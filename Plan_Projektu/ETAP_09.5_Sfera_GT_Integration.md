# ETAP 09.5: Integracja Sfera GT - Tworzenie/Aktualizacja Produktów

**Data utworzenia:** 2026-01-22
**Status:** ✅ ZAIMPLEMENTOWANE (kod gotowy, wymaga deployment na EXEA)
**Zależności:** ETAP_08 (BaseLinker ERP Integration) ✅

---

## 1. CEL

Umożliwienie tworzenia i aktualizacji produktów w Subiekt GT z poziomu PPM przez Sfera API.

---

## 2. ARCHITEKTURA

### Przepływ danych

```
┌─────────────────────────────────┐
│   PPM-CC-Laravel (Hostido)      │
│   SubiektGTService.php          │
│         │ HTTP POST/PUT         │
└─────────┼───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│   REST API (sapi.mpptrade.pl)   │
│   Program.cs + SferaService.cs  │
│         │ COM/OLE               │
└─────────┼───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│   Subiekt GT + Sfera GT         │
│   tw__Towar, tw_Cena            │
└─────────────────────────────────┘
```

### Tryby pracy

| Tryb | Sfera Enabled | Możliwości |
|------|---------------|------------|
| **DirectSQL** | `false` | UPDATE: tylko pola podstawowe (Name, Description, EAN, Weight) |
| **Sfera** | `true` | CREATE + UPDATE: wszystkie pola + ceny |

---

## 3. ZAIMPLEMENTOWANE PLIKI

### REST API (.NET 8) - sapi.mpptrade.pl

| Plik | Status | Opis |
|------|--------|------|
| `SferaService.cs` | ✅ NOWY | Wrapper COM dla Sfera GT |
| `SferaProductWriter.cs` | ✅ NOWY | Logika CRUD produktów (Sfera + DirectSQL fallback) |
| `ProductWriteModels.cs` | ✅ NOWY | DTOs: ProductWriteRequest, ProductWriteResponse |
| `Program.cs` | ✅ ZAKTUALIZOWANY | Nowe endpointy: POST/PUT products, GET sfera/health |
| `appsettings.json` | ✅ ZAKTUALIZOWANY | Konfiguracja Sfera |
| `appsettings.Production.json.template` | ✅ NOWY | Template dla produkcji |

### Laravel (ppm.mpptrade.pl)

| Plik | Status | Opis |
|------|--------|------|
| `SubiektRestApiClient.php` | ✅ ZAKTUALIZOWANY | Metody: createProduct(), updateProductBySku(), checkSferaHealth() |
| `SubiektGTService.php` | ✅ ZAKTUALIZOWANY | syncProductViaRestApi() z obsługą CREATE/UPDATE |

---

## 4. NOWE ENDPOINTY REST API

### Sfera Health Check
```
GET /api/sfera/health

Response:
{
    "success": true,
    "sfera_enabled": true,
    "mode": "Sfera",
    "status": "connected"
}
```

### Create Product
```
POST /api/products
Content-Type: application/json
X-API-Key: YOUR_KEY

{
    "Sku": "TEST-SKU",
    "Name": "Nazwa produktu",
    "Description": "Opis",
    "Ean": "1234567890123",
    "Unit": "szt",
    "Weight": 1.5,
    "VatRateId": 1,
    "Prices": {
        "0": {"Net": 100.00, "Gross": 123.00},
        "1": {"Net": 90.00}
    }
}

Response (201 Created):
{
    "success": true,
    "data": {
        "product_id": 12345,
        "sku": "TEST-SKU",
        "action": "created"
    }
}
```

### Update Product by SKU
```
PUT /api/products/sku/{sku}
Content-Type: application/json
X-API-Key: YOUR_KEY

{
    "Name": "Nowa nazwa",
    "Prices": {
        "0": {"Net": 110.00}
    }
}

Response:
{
    "success": true,
    "data": {
        "product_id": 12345,
        "sku": "TEST-SKU",
        "action": "updated"
    }
}
```

### Update Product by ID
```
PUT /api/products/{id}
```

### Check if Product Exists
```
GET /api/products/sku/{sku}/exists

Response:
{
    "exists": true,
    "product_id": 12345,
    "sku": "TEST-SKU"
}
```

---

## 5. KONFIGURACJA SFERA (appsettings.json)

```json
{
  "Sfera": {
    "Enabled": true,
    "Server": "MPPTRADE-SQL01\\INSERTGT",
    "Database": "MRF",
    "UseWindowsAuth": false,
    "User": "sa",
    "Password": "***",
    "Operator": "Integracja BL",
    "OperatorPassword": "***",
    "Timeout": 60
  }
}
```

---

## 6. KONFIGURACJA PPM (ERPConnection)

Nowe opcje w `connection_config`:

| Pole | Typ | Opis |
|------|-----|------|
| `sync_direction` | string | `pull`, `push`, `bidirectional` |
| `create_in_erp` | bool | Czy tworzyć nowe produkty w Subiekt GT |
| `default_price_level` | int | Domyślny poziom ceny (0-9) |
| `price_group_mappings` | array | Mapowanie PPM grup cenowych → Subiekt poziomów |
| `vat_rate_mapping` | array | Mapowanie stawek VAT |
| `unit_mapping` | array | Mapowanie jednostek miary |

---

## 7. DEPLOYMENT

### Krok 1: Build lokalny (✅ WYKONANE)
```powershell
cd "_TOOLS/SubiektGT_REST_API_DotNet"
dotnet publish -c Release -o ./publish
```

### Krok 2: Upload na EXEA (⏳ WYMAGA UŻYTKOWNIKA)

1. Połącz się z serwerem EXEA przez RDP
2. Zatrzymaj Application Pool dla sapi.mpptrade.pl
3. Skopiuj zawartość `publish/` do katalogu IIS
4. Edytuj `appsettings.json` - uzupełnij hasła:
   - `ConnectionStrings.SubiektGT` - hasło SA
   - `Sfera.Password` - hasło SA
   - `Sfera.OperatorPassword` - hasło operatora "Integracja BL"
5. Uruchom Application Pool
6. Przetestuj: `curl -k -H "X-API-Key: KEY" https://sapi.mpptrade.pl/api/sfera/health`

### Krok 3: Deploy Laravel na Hostido (⏳ DO WYKONANIA)
```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload zaktualizowanych plików PHP
pscp -i $HostidoKey -P 64321 `
  "app/Services/ERP/SubiektGT/SubiektRestApiClient.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/ERP/SubiektGT/

pscp -i $HostidoKey -P 64321 `
  "app/Services/ERP/SubiektGTService.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/ERP/

# Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"
```

---

## 8. ERROR CODES

| Kod | Opis | Rozwiązanie |
|-----|------|-------------|
| `VALIDATION_ERROR` | Błędne dane wejściowe | Sprawdź format danych |
| `DUPLICATE_SKU` | SKU już istnieje | Użyj UPDATE zamiast CREATE |
| `PRODUCT_NOT_FOUND` | Produkt nie znaleziony | Sprawdź SKU |
| `SFERA_REQUIRED` | Sfera nie jest włączona | Włącz Sfera w appsettings.json |
| `SFERA_CONNECTION_FAILED` | Błąd połączenia Sfera | Sprawdź konfigurację i licencję |
| `SFERA_ERROR` | Błąd operacji Sfera | Zobacz logi aplikacji |

---

## 9. UWAGI

### Stock (stany magazynowe)
**Stock NIE jest aktualizowany przez API produktów!**

Stany magazynowe w Subiekt GT zmieniają się tylko przez dokumenty:
- PZ (Przyjęcie Zewnętrzne) - zwiększa stan
- WZ (Wydanie Zewnętrzne) - zmniejsza stan
- MM (Przesunięcie Międzymagazynowe) - przenosi stan

### Licencja Sfera GT
Sfera GT wymaga osobnej licencji od InsERT. Bez licencji API będzie działać w trybie DirectSQL (tylko podstawowe aktualizacje).

### Operator API
W Subiekt GT należy utworzyć dedykowanego operatora dla API:
1. Panel GT → Administracja → Operatorzy
2. Dodaj operatora np. "API_PPM"
3. Nadaj uprawnienia do produktów
4. Ustaw hasło
5. Wpisz dane do appsettings.json

---

## 10. TESTY

### Test Sfera Health
```bash
curl -k -H "X-API-Key: YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb" \
  https://sapi.mpptrade.pl/api/sfera/health
```

### Test Update (DirectSQL fallback)
```bash
curl -k -X PUT \
  -H "X-API-Key: YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb" \
  -H "Content-Type: application/json" \
  -d '{"Name": "Test Name Update"}' \
  https://sapi.mpptrade.pl/api/products/sku/TEST-SKU
```

### Test Create (wymaga Sfera)
```bash
curl -k -X POST \
  -H "X-API-Key: YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb" \
  -H "Content-Type: application/json" \
  -d '{"Sku": "NEW-TEST", "Name": "Nowy Produkt"}' \
  https://sapi.mpptrade.pl/api/products
```

---

## 11. NASTĘPNE KROKI

1. ⏳ **Deploy REST API na EXEA** (wymaga RDP przez użytkownika)
2. ⏳ **Deploy Laravel na Hostido**
3. ⏳ **Test integracji end-to-end**
4. ⏳ **Konfiguracja ERPConnection w UI** (sync_direction, create_in_erp)
5. ❌ **Batch sync** (wiele produktów naraz)
6. ❌ **Queue support** (synchronizacja w tle)
