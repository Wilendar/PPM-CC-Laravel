# Subiekt GT: Produkty utworzone przez DirectSQL nie sa widoczne w GUI

**Data:** 2026-01-23
**Status:** ZIDENTYFIKOWANY
**Priorytet:** KRYTYCZNY
**Dotyczy:** `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs`

---

## Problem

Produkty utworzone przez API REST (`POST /api/products`) przy uzyciu `DirectSqlProductWriter` istnieja w bazie danych, ale NIE SA WIDOCZNE w GUI Subiekt GT (kartoteka towarow).

### Utworzone produkty testowe:
- **PPM-TEST-001** (ID: 23224)
- **PPM-TEST-002** (ID: 23225)

### Objawy:
1. API zwraca produkt poprawnie: `GET /api/products/23224` - OK
2. Produkt ma `isActive: true` (tw_Usuniety=0, tw_Zablokowany=0)
3. Produkt NIE pojawia sie w kartotece towarow GUI
4. Produkt NIE mozna wybrac przy wystawianiu dokumentow

---

## Przyczyna Glowna: BRAK REKORDU W tw_Stan

**KRYTYCZNE:** GUI Subiekt GT wymaga istnienia przynajmniej jednego rekordu w tabeli `tw_Stan` (stany magazynowe) dla kazdego produktu!

### Dowod:
```bash
# Sprawdzenie stanow dla produktu testowego
curl -k -s -H "X-API-Key: ..." "https://sapi.mpptrade.pl/api/stock/23224"
# Wynik: {"data":[]}  <-- PUSTO!

# Sprawdzenie stanow dla istniejacego produktu
curl -k -s -H "X-API-Key: ..." "https://sapi.mpptrade.pl/api/stock/100"
# Wynik: {"data":[{"productId":100,"warehouseId":1,"quantity":5.0,...}]}  <-- MA REKORD!
```

### Aktualny kod (bledny):
```csharp
// SferaProductWriter.cs, linia 469-471
// NOTE: We do NOT insert into tw_Stan
// Stock levels should be 0 by default and changed only through documents (PZ, WZ)
// Direct manipulation of tw_Stan can break inventory integrity
```

**Ten komentarz jest NIEPOPRAWNY** - bez rekordu w `tw_Stan` produkt jest "odlaczony" od systemu magazynowego i niewidoczny w GUI.

---

## Dodatkowe Przyczyny (nizszy priorytet)

### 2. Brak domyslnej stawki VAT
- `tw_IdVatSp` moze byc NULL jesli `request.VatRateId` nie przekazany
- Niektore widoki/raporty wymagaja ustawionej stawki VAT

### 3. Brak domyslnej grupy towarowej
- `tw_IdGrupa` moze byc NULL jesli `request.GroupId` nie przekazany
- Widoki filtrujace po grupach nie pokaza produktu

### 4. Brak VAT dla zakupu
- `tw_IdVatZak` nie jest ustawiany w kodzie
- Dla pelnej poprawnosci powinien byc rowny `tw_IdVatSp`

---

## Wymagany Fix

### 1. KRYTYCZNY: Dodac INSERT do tw_Stan

W pliku `SferaProductWriter.cs`, w metodzie `CreateProductAsync()`, po INSERT do `tw_Cena` (linia ~465), PRZED `transaction.Commit()`:

```csharp
// CRITICAL: Insert into tw_Stan for default warehouse(s)
// Without this record, product won't be visible in Subiekt GT GUI!
var defaultWarehouseIds = new[] { 1, 4 }; // "Sprzedaz" i "Stany"

foreach (var warehouseId in defaultWarehouseIds)
{
    var insertStanSql = @"
        INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
        VALUES (@productId, @warehouseId, 0, 0, 0, 0)";

    await conn.ExecuteAsync(insertStanSql, new {
        productId = newTwId,
        warehouseId = warehouseId
    }, transaction);
}

_logger.LogInformation(
    "Inserted tw_Stan for product ID={Id}, warehouses: {Warehouses}",
    newTwId, string.Join(", ", defaultWarehouseIds));
```

### 2. ZALECANY: Domyslne wartosci dla VatRateId i GroupId

```csharp
// Defaults (na poczatku CreateProductAsync)
const int DEFAULT_VAT_RATE_ID = 100001;  // 23% VAT
const int DEFAULT_GROUP_ID = 1;          // Do ustalenia - pierwsza dostepna grupa

// Przy INSERT do tw__Towar
tw_IdVatSp = request.VatRateId ?? DEFAULT_VAT_RATE_ID,
tw_IdVatZak = request.VatRateId ?? DEFAULT_VAT_RATE_ID,  // NOWE - VAT zakupu
tw_IdGrupa = request.GroupId ?? DEFAULT_GROUP_ID,
```

---

## Dostepne Magazyny w Systemie

| ID | Symbol | Nazwa |
|----|--------|-------|
| 1 | 1 | Sprzedaz |
| 3 | 3 | Wirtualny |
| 4 | 2 | Stany |
| 5 | 4 | Magazyn zewnetrzny |
| 6 | 5 | Aplikacja VIN |
| 7 | 6 | Dostawy |
| 8 | 7 | Reklamacje |
| 9 | 8 | Detal |
| 11 | 9 | Marketing |
| 12 | 10 | Do wyjasnienia |
| 13 | 11 | Stany - niedopuszczone do sprzedazy |

**Rekomendacja:** Domyslnie utworzyc rekord dla magazynu ID=1 (Sprzedaz) lub ID=4 (Stany).

---

## Dostepne Stawki VAT

| ID | Symbol | Stawka |
|----|--------|--------|
| 100001 | 23 | 23% |
| 100002 | 8 | 8% |
| 8 | 5 | 5% |
| 4 | 0 | 0% |
| 5 | zw | zwolniony |

**Rekomendacja:** Domyslna stawka VAT = 100001 (23%)

---

## Schemat Tabeli tw_Stan

```sql
CREATE TABLE tw_Stan (
    st_TowId INT NOT NULL,     -- FK do tw__Towar.tw_Id
    st_MagId INT NOT NULL,     -- FK do sl_Magazyn.mag_Id
    st_Stan MONEY NOT NULL,    -- Stan dostepny
    st_StanMin MONEY NOT NULL, -- Stan minimalny
    st_StanRez MONEY NOT NULL, -- Stan zarezerwowany
    st_StanMax MONEY NOT NULL, -- Stan maksymalny
    PRIMARY KEY (st_TowId, st_MagId)
);
```

---

## Weryfikacja Po Naprawie

Po wdrozeniu fix, przetestowac:

```bash
# 1. Utworzenie nowego produktu
curl -k -X POST \
  -H "X-API-Key: ..." \
  -H "Content-Type: application/json" \
  -d '{"Sku":"PPM-TEST-FIX","Name":"Test Fix Visibility"}' \
  https://sapi.mpptrade.pl/api/products

# 2. Sprawdzenie czy ma rekord w tw_Stan
curl -k -H "X-API-Key: ..." "https://sapi.mpptrade.pl/api/stock/{NEW_ID}"
# Oczekiwane: {"data":[{"warehouseId":1,"quantity":0,...}]}

# 3. Weryfikacja w GUI Subiekt GT
# - Kartoteka towarow -> szukaj po SKU "PPM-TEST-FIX"
# - Powinien byc widoczny!
```

---

## Naprawa Istniejacych Produktow Testowych

```sql
-- Reczne dodanie rekordow tw_Stan dla PPM-TEST-001 i PPM-TEST-002
INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
VALUES (23224, 1, 0, 0, 0, 0);

INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
VALUES (23225, 1, 0, 0, 0, 0);

-- Lub dla obu magazynow (Sprzedaz i Stany)
INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
VALUES (23224, 4, 0, 0, 0, 0), (23225, 4, 0, 0, 0, 0);
```

---

## Pliki do Modyfikacji

1. `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs`
   - Metoda `CreateProductAsync()` - dodac INSERT do tw_Stan
   - Dodac domyslne wartosci dla VatRateId, GroupId

2. `_TOOLS/SubiektGT_REST_API_DotNet/ProductWriteModels.cs` (opcjonalnie)
   - Dodac pole `DefaultWarehouseIds` do konfiguracji

3. `_TOOLS/SubiektGT_REST_API_DotNet/appsettings.json` (opcjonalnie)
   - Sekcja z domyslnymi wartosciami (VAT, Magazyn, Grupa)

---

## Zalaczniki

- Query API do testow: `_DOCS/SUBIEKT_API_TESTING_QUERIES.md`
- Schemat bazy: `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md`
- Skill integracji: `.claude/skills/subiekt-gt-integration/`
