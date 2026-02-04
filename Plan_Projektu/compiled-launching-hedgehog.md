# PLAN: Subiekt GT - Analiza DirectSQL CREATE (Workaround bez Sfera)

**Data:** 2026-01-23
**Status:** Do akceptacji
**Poprzedni plan:** ETAP A-E wykonany (UPDATE działa)

---

## Cel analizy

Zbadanie możliwości tworzenia NOWYCH produktów w Subiekt GT przez DirectSQL, bez licencji Sfera GT.

---

## Wynik analizy: MOŻLIWE Z OGRANICZENIAMI

### Kluczowe odkrycia

| Aspekt | Status | Opis |
|--------|--------|------|
| **spIdentyfikator** | ⚠️ DO TESTÓW | Stored procedure generująca ID - może działać bez Sfera |
| **Struktura INSERT** | ✅ ZNANA | 3 tabele: tw__Towar, tw_Cena, tw_Stan |
| **Ryzyko** | ⚠️ ŚREDNIE | Brak trigger'ów Sfera, możliwe problemy integralności |

---

## Workaround: spIdentyfikator + DirectSQL INSERT

### Koncepcja

```sql
-- 1. Bezpieczne generowanie ID
DECLARE @newId INT
EXEC spIdentifikator 'tw__towar', 1, @newId OUTPUT

-- 2. INSERT do głównej tabeli
INSERT INTO tw__Towar (
    tw_Id, tw_Symbol, tw_Nazwa, tw_Opis, tw_Aktywny,
    tw_Usuniety, tw_Zablokowany, tw_JednMiary
)
VALUES (
    @newId, 'SKU-001', 'Nazwa produktu', 'Opis', 1,
    0, 0, 'szt.'
)

-- 3. INSERT do tabeli cen (OBOWIĄZKOWE!)
INSERT INTO tw_Cena (tc_Id, tc_TowId, tc_CenaNetto0, tc_CenaBrutto0)
VALUES (@newId, @newId, 100.00, 123.00)

-- 4. INSERT do tabeli stanów (dla każdego magazynu)
INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanRez)
VALUES (@newId, 1, 0, 0)
```

### Wymagane pola (NOT NULL)

#### tw__Towar
| Pole | Typ | Default | Opis |
|------|-----|---------|------|
| tw_Id | INT | spIdentyfikator | Primary Key |
| tw_Symbol | VARCHAR(20) | - | SKU (UNIQUE!) |
| tw_Nazwa | VARCHAR(50) | - | Nazwa produktu |
| tw_Opis | VARCHAR(255) | '' | Opis |
| tw_Aktywny | BIT | 1 | Czy aktywny |
| tw_Usuniety | BIT | 0 | Soft delete |
| tw_Zablokowany | BIT | 0 | Blokada |
| tw_JednMiary | VARCHAR(10) | 'szt.' | Jednostka |

#### tw_Cena (11 poziomów cenowych)
| Pole | Typ | Default | Opis |
|------|-----|---------|------|
| tc_TowId | INT | FK | Foreign Key do tw__Towar |
| tc_CenaNetto0..9 | MONEY | 0 | Ceny netto poziom 0-9 |
| tc_CenaBrutto0..9 | MONEY | 0 | Ceny brutto poziom 0-9 |

#### tw_Stan (per magazyn)
| Pole | Typ | Default | Opis |
|------|-----|---------|------|
| st_TowId | INT | FK | Foreign Key do tw__Towar |
| st_MagId | INT | FK | Foreign Key do sl_Magazyn |
| st_Stan | MONEY | 0 | Ilość na stanie |
| st_StanRez | MONEY | 0 | Zarezerwowane |

---

## Plan implementacji

### ETAP 1: Test spIdentyfikator (KRYTYCZNY!)

**Cel:** Sprawdzić czy spIdentyfikator działa bez Sfera COM

**Test na EXEA Windows Server:**
```sql
-- Wykonaj w SQL Server Management Studio
DECLARE @newId INT
EXEC spIdentifikator 'tw__towar', 1, @newId OUTPUT
SELECT @newId as 'New ID'
```

**Możliwe wyniki:**
| Wynik | Znaczenie | Dalsze kroki |
|-------|-----------|--------------|
| Zwraca INT | ✅ Działa bez Sfera | Implementuj CREATE |
| ERROR COM | ❌ Wymaga Sfera | Porzuć workaround |
| NULL | ⚠️ Nieznany stan | Dalsze badania |

### ETAP 2: Implementacja CreateProductAsync (jeśli ETAP 1 ✅)

**Plik:** `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs`

**Zmiana w DirectSqlProductWriter:**
```csharp
public async Task<ProductWriteResponse> CreateProductAsync(ProductWriteRequest request)
{
    using var conn = new SqlConnection(_connectionString);
    await conn.OpenAsync();
    using var transaction = conn.BeginTransaction();

    try
    {
        // 1. Generuj ID przez spIdentifikator
        var newId = await conn.ExecuteScalarAsync<int>(
            "DECLARE @id INT; EXEC spIdentifikator 'tw__towar', 1, @id OUTPUT; SELECT @id",
            transaction: transaction
        );

        // 2. INSERT tw__Towar
        var insertSql = @"
            INSERT INTO tw__Towar (
                tw_Id, tw_Symbol, tw_Nazwa, tw_Opis, tw_Aktywny,
                tw_Usuniety, tw_Zablokowany, tw_JednMiary, tw_PodstKodKresk, tw_Masa
            ) VALUES (
                @id, @sku, @name, @desc, 1,
                0, 0, @unit, @ean, @weight
            )";

        await conn.ExecuteAsync(insertSql, new {
            id = newId,
            sku = request.Sku,
            name = request.Name,
            desc = request.Description ?? "",
            unit = request.Unit ?? "szt.",
            ean = request.Ean ?? "",
            weight = request.Weight ?? 0
        }, transaction);

        // 3. INSERT tw_Cena
        var insertCenaSql = @"
            INSERT INTO tw_Cena (tc_Id, tc_TowId, tc_CenaNetto0, tc_CenaBrutto0)
            VALUES (@id, @id, @net, @gross)";

        var defaultPrice = request.Prices?.GetValueOrDefault(0);
        await conn.ExecuteAsync(insertCenaSql, new {
            id = newId,
            net = defaultPrice?.Net ?? 0m,
            gross = defaultPrice?.Gross ?? 0m
        }, transaction);

        // 4. INSERT tw_Stan (dla magazynu głównego)
        var insertStanSql = @"
            INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanRez, st_StanMin, st_StanMax)
            VALUES (@id, 1, 0, 0, 0, 0)";

        await conn.ExecuteAsync(insertStanSql, new { id = newId }, transaction);

        await transaction.CommitAsync();

        return new ProductWriteResponse
        {
            Success = true,
            ProductId = newId,
            Message = $"Product created via DirectSQL [ID: {newId}]",
            Action = "created",
            Sku = request.Sku
        };
    }
    catch (Exception ex)
    {
        await transaction.RollbackAsync();
        return new ProductWriteResponse
        {
            Success = false,
            Error = ex.Message,
            ErrorCode = "DIRECT_SQL_CREATE_FAILED"
        };
    }
}
```

### ETAP 3: Endpoint w REST API

**Plik:** `_TOOLS/SubiektGT_REST_API_DotNet/Program.cs`

```csharp
app.MapPost("/api/products", async (IProductWriter writer, ProductWriteRequest request) =>
{
    var result = await writer.CreateProductAsync(request);
    return result.Success
        ? Results.Created($"/api/products/{result.ProductId}", result)
        : Results.BadRequest(result);
}).RequireAuthorization();
```

### ETAP 4: Laravel Client

**Plik:** `app/Services/ERP/SubiektGT/SubiektRestApiClient.php`

```php
public function createProduct(array $data): array
{
    return $this->post('/api/products', [
        'sku' => $data['sku'],
        'name' => $data['name'],
        'description' => $data['description'] ?? '',
        'ean' => $data['ean'] ?? null,
        'unit' => $data['unit'] ?? 'szt.',
        'weight' => $data['weight'] ?? null,
        'prices' => $data['prices'] ?? [],
    ]);
}
```

---

## Ryzyka i ograniczenia

### ⚠️ ZNANE RYZYKA

| Ryzyko | Wpływ | Mitigacja |
|--------|-------|-----------|
| **Brak trigger'ów Sfera** | Średni | Ręczna walidacja danych przed INSERT |
| **Subiekt Desktop może nie widzieć** | Niski | Test po każdym CREATE |
| **Integralność referencyjna** | Średni | Transakcja SQL z ROLLBACK |
| **spIdentyfikator wymaga Sfera** | KRYTYCZNY | Test ETAP 1 przed implementacją |

### ❌ OGRANICZENIA

- **Brak full validation** - Sfera waliduje dane biznesowe, DirectSQL nie
- **Brak historii zmian** - Subiekt GT może nie logować operacji
- **Potencjalne problemy z update** - Jeśli Sfera oczekuje pewnych pól

---

## Pliki do modyfikacji

| Plik | Zmiana |
|------|--------|
| `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs` | Nowa metoda CreateProductAsync w DirectSqlProductWriter |
| `_TOOLS/SubiektGT_REST_API_DotNet/Program.cs` | Nowy endpoint POST /api/products |
| `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` | Nowa metoda createProduct() |
| `app/Services/ERP/SubiektGTService.php` | Nowa metoda createProductInErp() |

---

## Weryfikacja

### Test 1: spIdentyfikator (PRZED IMPLEMENTACJĄ!)
```sql
-- Na EXEA Windows Server w SSMS
DECLARE @id INT
EXEC spIdentifikator 'tw__towar', 1, @id OUTPUT
SELECT @id -- Powinno zwrócić INT
```

### Test 2: POST /api/products
```bash
curl -X POST https://sapi.mpptrade.pl/api/products \
  -H "X-API-Key: YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Test Product","unit":"szt."}'
```

### Test 3: Weryfikacja w Subiekt GT Desktop
1. Otwórz Subiekt GT
2. Wyszukaj produkt po SKU "TEST-001"
3. Sprawdź czy wszystkie pola są poprawne
4. Sprawdź czy można edytować/usunąć produkt

---

## Decyzja

**WARUNEK KONIECZNY:** Test spIdentyfikator (ETAP 1) musi przejść!

| Wynik testu | Decyzja |
|-------------|---------|
| ✅ spIdentyfikator działa | Implementuj ETAP 2-4 |
| ❌ spIdentyfikator wymaga Sfera | Porzuć workaround, rozważ licencję Sfera |

---

## Alternatywy (jeśli workaround nie zadziała)

1. **Licencja Sfera GT** (~2000-5000 PLN/rok) - pełne wsparcie
2. **Ręczne tworzenie w Subiekt** - produkty tworzone w Desktop, PPM tylko synchronizuje
3. **lukegpl/api-subiekt-gt** - zewnętrzne API (może też wymagać Sfera)
