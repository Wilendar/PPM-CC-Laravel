# ETAP_09.5: Integracja Sfera GT - Tworzenie/Aktualizacja Produktów

**Data:** 2026-01-22
**Status:** 🛠️ Do implementacji
**Zależności:** ETAP_08 (BaseLinker ERP Integration) ✅

---

## 1. CEL

Umożliwienie tworzenia i aktualizacji produktów w Subiekt GT z poziomu PPM przez Sfera API.

**Obecne ograniczenie:** REST API na sapi.mpptrade.pl ma tylko endpointy GET (read-only).

---

## 2. REKOMENDOWANA ARCHITEKTURA

### Opcja B: Rozszerzenie REST API o Sfera COM Bridge

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

**Dlaczego NIE direct SQL?**
- Dokumentacja InsERT: "NIGDY nie modyfikuj danych przez SQL bez Sfera"
- Problem z sekwencjami ID (wymaga `spIdentyfikator`)
- Brak walidacji biznesowej

---

## 3. PLAN IMPLEMENTACJI

### ETAP 1: Infrastruktura Sfera na Windows Server (3-5 dni)

#### 1.1 Weryfikacja środowiska
- [ ] Sprawdzenie licencji Sfera GT na serwerze EXEA
- [ ] Utworzenie operatora API w Subiekt GT (Panel GT → Administracja → Operatorzy)
- [ ] Test połączenia COM z poziomu .NET console app

#### 1.2 Nowe pliki do utworzenia
```
_TOOLS/SubiektGT_REST_API_DotNet/
├── SferaService.cs           # Wrapper dla Sfera COM
├── SferaProductWriter.cs     # Tworzenie/aktualizacja produktów
├── ProductWriteRequest.cs    # DTO request
└── ProductWriteResponse.cs   # DTO response
```

#### 1.3 SferaService.cs (szkielet)
```csharp
public class SferaService : IDisposable
{
    private dynamic _gt;
    private dynamic _subiekt;

    public async Task<bool> InitializeAsync(SferaConfig config)
    {
        _gt = Activator.CreateInstance(Type.GetTypeFromProgID("Insert.gt"));
        _gt.Produkt = 1; // Subiekt GT
        _gt.Serwer = config.Server;
        _gt.Baza = config.Database;
        _gt.Autentykacja = 1; // SQL Auth
        _gt.Uzytkownik = config.User;
        _gt.UzytkownikHaslo = config.Password;

        _subiekt = _gt.Uruchom(0, 4); // Hidden, background
        return _subiekt != null;
    }
}
```

#### 1.4 Konfiguracja appsettings.json
```json
{
  "Sfera": {
    "Server": "(local)\\INSERTGT",
    "Database": "MPP_TRADE",
    "User": "API_OPERATOR",
    "Password": "***",
    "Timeout": 60
  }
}
```

---

### ETAP 2: Operacje UPDATE (2-3 dni)

#### 2.1 Nowy endpoint PUT w Program.cs
```csharp
app.MapPut("/api/products/sku/{sku}", async (string sku, ProductWriteRequest req, ISferaProductWriter writer) =>
{
    var result = await writer.UpdateProductBySkuAsync(sku, req);
    return result.Success ? Results.Ok(result) : Results.BadRequest(result);
}).RequireAuthorization();
```

#### 2.2 Aktualizacja SubiektRestApiClient.php
```php
public function updateProductBySku(string $sku, array $data): array
{
    return $this->request('PUT', "/api/products/sku/{$sku}", $data);
}
```

#### 2.3 Modyfikacja SubiektGTService::syncProductViaRestApi()
```php
// Jeśli produkt istnieje → UPDATE
if ($findResult['found']) {
    $updateData = $this->mapPpmToSubiekt($product);
    $result = $client->updateProductBySku($product->sku, $updateData);
    return ['success' => true, 'action' => 'updated', ...];
}
```

---

### ETAP 3: Operacje CREATE (3-4 dni)

#### 3.1 Nowy endpoint POST w Program.cs
```csharp
app.MapPost("/api/products", async (ProductWriteRequest req, ISferaProductWriter writer) =>
{
    var result = await writer.CreateProductAsync(req);
    return result.Success
        ? Results.Created($"/api/products/{result.ProductId}", result)
        : Results.BadRequest(result);
}).RequireAuthorization();
```

#### 3.2 SferaProductWriter.CreateProductAsync()
```csharp
var towar = sfera.TowaryManager.Dodaj();
towar.Symbol = request.Sku;
towar.Nazwa = request.Name;
// ... set other fields ...
towar.Zapisz();
return new ProductWriteResponse { Success = true, ProductId = towar.Id };
```

#### 3.3 Aktualizacja SubiektRestApiClient.php
```php
public function createProduct(array $data): array
{
    return $this->request('POST', '/api/products', $data);
}
```

---

### ETAP 4: Walidacja i Error Handling (2 dni)

#### 4.1 Walidacja REST API
- SKU max 20 znaków
- Name max 50 znaków
- Price levels 0-10
- VatRateId musi istnieć

#### 4.2 Error codes do tłumaczenia
| Code | Komunikat PL |
|------|--------------|
| DUPLICATE_SKU | Produkt o tym SKU już istnieje |
| INVALID_VAT_RATE | Nieprawidłowa stawka VAT |
| SFERA_CONNECTION_FAILED | Nie można połączyć z Sfera GT |
| SFERA_SAVE_FAILED | Błąd zapisu w Sfera GT |

---

## 4. MAPOWANIE PÓL PPM → SUBIEKT GT

### 4.1 Pola podstawowe
| PPM Product | Subiekt tw__Towar |
|-------------|-------------------|
| `sku` | `tw_Symbol` |
| `name` | `tw_Nazwa` |
| `short_description` | `tw_Opis` |
| `ean` | `tw_PodstKodKresk` |
| `weight` | `tw_Masa` |
| `tax_rate` | `tw_IdVatSp` (FK) |

### 4.2 Ceny (11 poziomów)
| Poziom | Nazwa (tw_Parametr) | Kolumna |
|--------|---------------------|---------|
| 0 | Detaliczna | tc_CenaNetto0 |
| 1 | MRF-MPP | tc_CenaNetto1 |
| 2 | Szkółka-Komis-Drop | tc_CenaNetto2 |
| ... | ... | ... |
| 9 | Pracownik | tc_CenaNetto9 |

---

## 5. PLIKI DO MODYFIKACJI

### Windows Server (sapi.mpptrade.pl)
| Plik | Akcja |
|------|-------|
| `Program.cs` | Dodanie POST/PUT endpoints |
| `appsettings.json` | Konfiguracja Sfera |
| `SferaService.cs` | **NOWY** - COM wrapper |
| `SferaProductWriter.cs` | **NOWY** - logika CRUD |
| `ProductWriteRequest.cs` | **NOWY** - DTO |
| `ProductWriteResponse.cs` | **NOWY** - DTO |

### PPM Laravel (ppm.mpptrade.pl)
| Plik | Akcja |
|------|-------|
| `SubiektRestApiClient.php` | Metody createProduct(), updateProductBySku() |
| `SubiektGTService.php` | Rozszerzenie syncProductViaRestApi() |

---

## 6. WYMAGANIA WSTĘPNE

- [ ] **Licencja Sfera GT** - weryfikacja na serwerze EXEA
- [ ] **Operator API** - utworzenie w Subiekt GT
- [ ] **Backup bazy** - przed pierwszymi testami
- [ ] **Mapowanie grup cenowych** - konfiguracja w ERPConnection

---

## 7. RYZYKA

| Ryzyko | Mitigation |
|--------|------------|
| Sfera COM nie działa pod IIS | Uruchomienie jako Windows Service |
| Timeout przy dużych operacjach | Async processing, zwiększenie timeout |
| Duplikaty SKU | Walidacja przed CREATE |

---

## 8. WERYFIKACJA

### Testy manualne
1. **Test UPDATE**: Zmień cenę produktu w PPM → sprawdź w Subiekt GT
2. **Test CREATE**: Dodaj nowy produkt w PPM → sprawdź w Subiekt GT
3. **Test ERROR**: Spróbuj dodać duplikat SKU → sprawdź komunikat błędu

### Endpointy do przetestowania
```bash
# Health check
curl -k -H "X-API-Key: KEY" https://sapi.mpptrade.pl/api/health

# UPDATE product
curl -k -X PUT -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"Name": "Test Update"}' \
  https://sapi.mpptrade.pl/api/products/sku/TEST-SKU

# CREATE product
curl -k -X POST -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"Sku": "NEW-SKU", "Name": "New Product"}' \
  https://sapi.mpptrade.pl/api/products
```

---

## 9. TIMELINE

| Etap | Czas | Kumulatywnie |
|------|------|--------------|
| ETAP 1: Infrastruktura Sfera | 3-5 dni | 3-5 dni |
| ETAP 2: Operacje UPDATE | 2-3 dni | 5-8 dni |
| ETAP 3: Operacje CREATE | 3-4 dni | 8-12 dni |
| ETAP 4: Walidacja | 2 dni | 10-14 dni |

**TOTAL: 10-14 dni roboczych**

---

## 10. UWAGI KOŃCOWE

1. **Stany magazynowe NIE są aktualizowane przez API produktów** - zmieniają się tylko przez dokumenty (PZ, WZ, MM)

2. **Deployment REST API** - użytkownik wgrywa przez RDP na EXEA:
   ```powershell
   dotnet publish -c Release -o ./publish
   # Upload publish/ → sapi.mpptrade.pl via RDP
   # Restart IIS App Pool
   ```

3. **Fallback** - jeśli Sfera COM nie działa w IIS, plan B to Windows Service z kolejką
