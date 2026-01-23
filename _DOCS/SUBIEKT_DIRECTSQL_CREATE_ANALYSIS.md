# Analiza DirectSQL CREATE dla Subiekt GT

**Data analizy:** 2026-01-23
**Wersja:** 1.0
**Autor:** Claude Agent (erp/directsql-create-analysis)

---

## 1. Podsumowanie Wykonawcze

### Rekomendacja: **NO-GO dla DirectSQL CREATE**

| Aspekt | Ocena | Uzasadnienie |
|--------|-------|--------------|
| Techniczna wykonalność | Możliwa | Struktura tabel jest znana |
| Generowanie ID | **RYZYKOWNE** | Brak pewności co do spIdentyfikator |
| Integralność danych | **WYSOKIE RYZYKO** | Triggery, walidacje, relacje |
| Wsparcie producenta | **BRAK** | InsERT nie wspiera DirectSQL |
| Alternatywa | **ZALECANA** | Sfera API lub import CSV |

---

## 2. Analiza spIdentyfikator

### 2.1 Status w dokumentacji

**Wynik przeszukania:** Procedura `spIdentyfikator` **NIE ZOSTAŁA ZNALEZIONA** w:
- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md`
- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json`
- `_TOOLS/SubiektGT_REST_API_DotNet/SubiektRepository.cs`

### 2.2 SQL do weryfikacji na serwerze

```sql
-- Sprawdź czy procedura istnieje
SELECT name, type_desc, create_date, modify_date
FROM sys.procedures
WHERE name LIKE '%Identyfikator%' OR name LIKE '%NextId%' OR name LIKE '%GetId%'
ORDER BY name;

-- Sprawdź sekwencje (SQL Server 2012+)
SELECT name, current_value, increment
FROM sys.sequences
WHERE name LIKE '%tw%' OR name LIKE '%Towar%';

-- Sprawdź triggery na tw__Towar
SELECT t.name AS trigger_name,
       OBJECT_NAME(t.parent_id) AS table_name,
       t.is_instead_of_trigger,
       t.is_disabled
FROM sys.triggers t
WHERE OBJECT_NAME(t.parent_id) = 'tw__Towar';

-- Sprawdź czy tw_Id ma IDENTITY
SELECT c.name, c.is_identity, ic.seed_value, ic.increment_value
FROM sys.columns c
LEFT JOIN sys.identity_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
WHERE OBJECT_NAME(c.object_id) = 'tw__Towar' AND c.name = 'tw_Id';
```

### 2.3 Hipotezy generowania ID

| Metoda | Prawdopodobieństwo | Implikacja |
|--------|-------------------|------------|
| IDENTITY column | Średnie | Automatyczne, bezpieczne |
| Stored procedure | Wysokie | Wymaga wywołania EXEC |
| Tabela sekwencji | Średnie | Wymaga UPDATE + SELECT |
| MAX(id)+1 | Niskie | **NIEBEZPIECZNE** - race conditions |

**Wniosek:** Bez dostępu do serwera nie można potwierdzić mechanizmu. Zakładamy najgorszy scenariusz.

---

## 3. Wymagane pola NOT NULL dla tw__Towar

### 3.1 Analiza 101 kolumn

Na podstawie schematu zidentyfikowano **63 pola NOT NULL** (bez domyślnej wartości).

### 3.2 Pola krytyczne (wymagane do INSERT)

| Kolumna | Typ | Max Length | Opis |
|---------|-----|------------|------|
| `tw_Id` | int | - | **PRIMARY KEY** - wymaga generowania |
| `tw_Symbol` | varchar | 20 | **SKU/Symbol produktu - UNIQUE** |
| `tw_Nazwa` | varchar | 50 | Nazwa produktu |
| `tw_Rodzaj` | int | - | Typ: 1=towar, 2=usługa |
| `tw_JednMiary` | varchar | 10 | Jednostka miary (np. "szt", "kg") |
| `tw_Zablokowany` | bit | - | 0=aktywny, 1=zablokowany |
| `tw_Usuniety` | bit | - | 0=nie usunięty, 1=usunięty |

### 3.3 Pola z domyślnymi wartościami (przyjęte założenia)

```sql
-- Pola VARCHAR NOT NULL - domyślnie puste stringi
tw_Opis = ''
tw_PKWiU = ''
tw_SWW = ''
tw_DostSymbol = ''
tw_UrzNazwa = ''
tw_PodstKodKresk = ''
tw_WWW = ''
tw_Pole1..8 = ''
tw_Uwagi = ''
tw_JednMiaryZak = 'szt'
tw_JednMiarySprz = 'szt'
tw_AkcyzaMarkaWyrobow = ''
tw_AkcyzaWielkoscProducenta = ''
tw_WegielOpisPochodzenia = ''

-- Pola BIT NOT NULL - domyślnie 0
tw_JakPrzySp = 0
tw_PrzezWartosc = 0
tw_CenaOtwarta = 0
tw_KontrolaTW = 0
tw_SklepInternet = 0
tw_JMZakInna = 0
tw_JMSprzInna = 0
tw_SerwisAukcyjny = 0
tw_SprzedazMobilna = 0
tw_Akcyza = 0
tw_AkcyzaZaznacz = 0
tw_ObrotMarza = 0
tw_OdwrotneObciazenie = 0
tw_DodawalnyDoZW = 0
tw_MechanizmPodzielonejPlatnosci = 0
tw_OplCukrowaPodlega = 0
tw_OplCukrowaInneSlodzace = 0
tw_OplCukrowaSok = 0
tw_OplCukrowaKofeinaPodlega = 0
tw_OplCukrowaNapojWeglElektr = 0
tw_WegielPodlegaOswiadczeniu = 0
tw_PodlegaOplacieNaFunduszOchronyRolnictwa = 0
tw_ObjetySysKaucyjnym = 0

-- Pola INT NOT NULL
tw_ProgKwotowyOO = 0
tw_KomunikatDokumenty = 0
tw_GrupaJpkVat = 0
```

---

## 4. Foreign Keys i Zależności

### 4.1 Zidentyfikowane klucze obce (nullable)

| Kolumna FK | Tabela docelowa | Kolumna docelowa | Wymagane |
|------------|-----------------|------------------|----------|
| `tw_IdVatSp` | sl_StawkaVAT | vat_Id | Nie (nullable) |
| `tw_IdVatZak` | sl_StawkaVAT | vat_Id | Nie |
| `tw_IdGrupa` | sl_GrupaTw | grt_Id | Nie |
| `tw_IdPodstDostawca` | kh__Kontrahent | kh_Id | Nie |
| `tw_IdProducenta` | kh__Kontrahent | kh_Id | Nie |
| `tw_IdRabat` | (tabela rabatów) | - | Nie |
| `tw_IdOpakowanie` | (tabela opakowań) | - | Nie |
| `tw_IdKrajuPochodzenia` | sl_KrajPochodzenia | - | Nie |
| `tw_IdUJM` | (jednostki miary) | - | Nie |

### 4.2 Tabele zależne (wymagające INSERT po tw__Towar)

| Tabela | Kolumna FK | Wymagane | Opis |
|--------|-----------|----------|------|
| `tw_Cena` | tc_IdTowar | **TAK** | Ceny (11 poziomów) |
| `tw_Stan` | st_TowId | **TAK** | Stany magazynowe |
| `tw_KodKreskowy` | - | Nie | Dodatkowe kody EAN |
| `tw_CechaTw` | cht_IdTowar | Nie | Cechy produktu |
| `tw_JednMiary` | - | Nie | Przeliczniki jednostek |
| `tw_ZdjecieTw` | - | Nie | Zdjęcia produktu |

---

## 5. Szablony SQL INSERT

### 5.1 INSERT do tw__Towar (minimalny)

```sql
-- UWAGA: tw_Id musi być wygenerowany przez spIdentyfikator lub IDENTITY!
-- Ten szablon zakłada ręczne podanie ID (NIEBEZPIECZNE!)

INSERT INTO tw__Towar (
    -- PRIMARY KEY
    tw_Id,

    -- WYMAGANE pola biznesowe
    tw_Symbol,          -- SKU (UNIQUE)
    tw_Nazwa,           -- Nazwa produktu
    tw_Rodzaj,          -- 1=towar, 2=usługa
    tw_JednMiary,       -- Jednostka miary

    -- WYMAGANE pola statusowe
    tw_Zablokowany,
    tw_Usuniety,

    -- WYMAGANE pola z domyślnymi wartościami
    tw_Opis,
    tw_PKWiU,
    tw_SWW,
    tw_DostSymbol,
    tw_UrzNazwa,
    tw_PodstKodKresk,
    tw_WWW,
    tw_JakPrzySp,
    tw_PrzezWartosc,
    tw_CenaOtwarta,
    tw_KontrolaTW,
    tw_SklepInternet,
    tw_Pole1, tw_Pole2, tw_Pole3, tw_Pole4,
    tw_Pole5, tw_Pole6, tw_Pole7, tw_Pole8,
    tw_Uwagi,
    tw_JednMiaryZak,
    tw_JMZakInna,
    tw_JednMiarySprz,
    tw_JMSprzInna,
    tw_SerwisAukcyjny,
    tw_SprzedazMobilna,
    tw_Akcyza,
    tw_AkcyzaZaznacz,
    tw_ObrotMarza,
    tw_OdwrotneObciazenie,
    tw_ProgKwotowyOO,
    tw_DodawalnyDoZW,
    tw_KomunikatDokumenty,
    tw_MechanizmPodzielonejPlatnosci,
    tw_GrupaJpkVat,
    tw_OplCukrowaPodlega,
    tw_OplCukrowaInneSlodzace,
    tw_OplCukrowaSok,
    tw_OplCukrowaKofeinaPodlega,
    tw_OplCukrowaNapojWeglElektr,
    tw_WegielPodlegaOswiadczeniu,
    tw_WegielOpisPochodzenia,
    tw_PodlegaOplacieNaFunduszOchronyRolnictwa,
    tw_ObjetySysKaucyjnym,
    tw_AkcyzaMarkaWyrobow,
    tw_AkcyzaWielkoscProducenta
)
VALUES (
    -- PRIMARY KEY (WYMAGA GENEROWANIA!)
    @tw_Id,

    -- Pola biznesowe
    @sku,               -- varchar(20), UNIQUE
    @nazwa,             -- varchar(50)
    1,                  -- 1 = towar
    @jednostka,         -- varchar(10), np. 'szt'

    -- Statusy
    0,                  -- nie zablokowany
    0,                  -- nie usunięty

    -- Domyślne wartości
    @opis,              -- varchar(255)
    '',                 -- PKWiU
    '',                 -- SWW
    '',                 -- DostSymbol
    @nazwa,             -- UrzNazwa (nazwa urzędowa)
    @ean,               -- PodstKodKresk
    '',                 -- WWW
    0, 0, 0, 0, 0,      -- flagi bit
    '', '', '', '', '', '', '', '',  -- Pole1-8
    '',                 -- Uwagi
    @jednostka,         -- JednMiaryZak
    0,                  -- JMZakInna
    @jednostka,         -- JednMiarySprz
    0,                  -- JMSprzInna
    0, 0, 0, 0, 0, 0,   -- flagi bit
    0,                  -- ProgKwotowyOO
    0,                  -- DodawalnyDoZW
    0,                  -- KomunikatDokumenty
    0,                  -- MechanizmPodzielonejPlatnosci
    0,                  -- GrupaJpkVat
    0, 0, 0, 0, 0, 0,   -- opłata cukrowa flagi
    '',                 -- WegielOpisPochodzenia
    0,                  -- Fundusz Ochrony Rolnictwa
    0,                  -- System kaucyjny
    '',                 -- AkcyzaMarkaWyrobow
    ''                  -- AkcyzaWielkoscProducenta
);
```

### 5.2 INSERT do tw_Cena

```sql
-- Wymagany dla każdego produktu!
INSERT INTO tw_Cena (
    tc_Id,              -- wymaga generowania
    tc_IdTowar,         -- FK do tw__Towar

    -- Ceny netto (11 poziomów: 0-10)
    tc_CenaNetto0, tc_CenaNetto1, tc_CenaNetto2, tc_CenaNetto3, tc_CenaNetto4,
    tc_CenaNetto5, tc_CenaNetto6, tc_CenaNetto7, tc_CenaNetto8, tc_CenaNetto9, tc_CenaNetto10,

    -- Ceny brutto (11 poziomów)
    tc_CenaBrutto0, tc_CenaBrutto1, tc_CenaBrutto2, tc_CenaBrutto3, tc_CenaBrutto4,
    tc_CenaBrutto5, tc_CenaBrutto6, tc_CenaBrutto7, tc_CenaBrutto8, tc_CenaBrutto9, tc_CenaBrutto10,

    -- Waluty (domyślnie PLN)
    tc_IdWaluta0, tc_IdWaluta1, tc_IdWaluta2, tc_IdWaluta3, tc_IdWaluta4,
    tc_IdWaluta5, tc_IdWaluta6, tc_IdWaluta7, tc_IdWaluta8, tc_IdWaluta9, tc_IdWaluta10,

    tc_WalutaJedn
)
VALUES (
    @tc_Id,
    @tw_Id,

    -- Ceny netto
    @cenaNetto, @cenaNetto, @cenaNetto, @cenaNetto, @cenaNetto,
    @cenaNetto, @cenaNetto, @cenaNetto, @cenaNetto, @cenaNetto, @cenaNetto,

    -- Ceny brutto (netto * 1.23 dla 23% VAT)
    @cenaBrutto, @cenaBrutto, @cenaBrutto, @cenaBrutto, @cenaBrutto,
    @cenaBrutto, @cenaBrutto, @cenaBrutto, @cenaBrutto, @cenaBrutto, @cenaBrutto,

    -- Waluty
    'PLN', 'PLN', 'PLN', 'PLN', 'PLN',
    'PLN', 'PLN', 'PLN', 'PLN', 'PLN', 'PLN',

    'szt'
);
```

### 5.3 INSERT do tw_Stan (per magazyn)

```sql
-- Wymagany dla każdego magazynu!
INSERT INTO tw_Stan (
    st_TowId,           -- FK do tw__Towar
    st_MagId,           -- FK do sl_Magazyn
    st_Stan,            -- ilość na stanie
    st_StanMin,         -- stan minimalny
    st_StanRez,         -- stan zarezerwowany
    st_StanMax          -- stan maksymalny
)
VALUES (
    @tw_Id,
    @magazynId,         -- np. 1 = główny magazyn
    0.0,                -- początkowy stan
    0.0,                -- min
    0.0,                -- rezerwacja
    0.0                 -- max
);
```

---

## 6. Zidentyfikowane Ryzyka

### 6.1 Ryzyka krytyczne (STOP)

| ID | Ryzyko | Prawdopodobieństwo | Wpływ | Mitygacja |
|----|--------|-------------------|-------|-----------|
| R1 | **Nieznany mechanizm generowania ID** | Wysokie | Krytyczny | Wymaga weryfikacji na serwerze |
| R2 | **Triggery INSERT mogą odrzucić** | Średnie | Krytyczny | Testowanie na kopii bazy |
| R3 | **Brak integralności referencyjnej** | Wysokie | Wysoki | Sfera API gwarantuje |
| R4 | **Korupcja indeksów** | Niskie | Krytyczny | Backup przed operacjami |

### 6.2 Ryzyka średnie (UWAGA)

| ID | Ryzyko | Opis |
|----|--------|------|
| R5 | Pominięcie wymaganych pól | 101 kolumn - łatwo coś przeoczyć |
| R6 | Niepoprawne typy danych | money vs decimal, varchar encoding |
| R7 | Brak walidacji biznesowej | Sfera waliduje SKU, nazwy, ceny |
| R8 | Problemy z transakcyjnością | 3 tabele = 3 INSERT = ryzyko partial commit |

### 6.3 Ryzyka operacyjne

| ID | Ryzyko | Opis |
|----|--------|------|
| R9 | Brak wsparcia InsERT | Problemy = brak pomocy producenta |
| R10 | Aktualizacje Subiekt GT | Nowe kolumny mogą być NOT NULL |
| R11 | Audit trail | Brak logowania zmian jak w Sfera |

---

## 7. Rekomendowane Alternatywy

### 7.1 Sfera API (ZALECANE)

```csharp
// Przykład z Sfera API
using InsERT.Moria.Sfera;

var subiekt = new Subiekt();
var towar = subiekt.TowaryManager.DodajTowar();
towar.Symbol = "SKU-001";
towar.Nazwa = "Nowy produkt";
towar.JednostkaMiary = "szt";
towar.CenaNettoPoziom0 = 100.00m;
towar.Zapisz();  // Automatyczne ID, walidacja, triggery
```

**Zalety:**
- Automatyczne generowanie ID
- Walidacja biznesowa
- Obsługa triggerów
- Wsparcie producenta

**Wady:**
- Wymaga licencji Sfera
- Wymaga .NET Framework na serwerze

### 7.2 Import CSV przez Subiekt GT

```csv
Symbol;Nazwa;JednMiary;CenaNetto;Stawka VAT
SKU-001;Nowy produkt;szt;100.00;23%
SKU-002;Inny produkt;kg;50.00;23%
```

**Zalety:**
- Wbudowane w Subiekt GT
- Pełna walidacja
- Nie wymaga programowania

**Wady:**
- Ręczny proces
- Nie nadaje się do automatyzacji

### 7.3 REST API z DirectSQL UPDATE only

```
Current state (WORKING):
  REST API (sapi.mpptrade.pl) → READ/UPDATE → SQL Server

Recommended extension:
  REST API → Sfera API → CREATE products
```

**Workflow:**
1. PPM tworzy produkt lokalnie (draft)
2. REST API wywołuje Sfera API do CREATE
3. Sfera zwraca tw_Id
4. REST API dalej używa DirectSQL do UPDATE

---

## 8. Plan Testowania (jeśli GO)

### 8.1 Środowisko testowe

```sql
-- 1. Utworzenie kopii bazy testowej
BACKUP DATABASE MPP_TRADE TO DISK = 'MPP_TRADE_TEST.bak';
RESTORE DATABASE MPP_TRADE_TEST FROM DISK = 'MPP_TRADE_TEST.bak';

-- 2. Test generowania ID
DECLARE @newId INT;
-- Sprawdź czy IDENTITY
INSERT INTO tw__Towar (...) VALUES (...);
SET @newId = SCOPE_IDENTITY();  -- NULL jeśli nie IDENTITY

-- Alternatywnie: spIdentyfikator
EXEC spIdentyfikator 'tw__Towar', @newId OUTPUT;
```

### 8.2 Checklist testów

- [ ] Weryfikacja spIdentyfikator na serwerze
- [ ] Test INSERT na kopii bazy
- [ ] Sprawdzenie triggerów
- [ ] Walidacja w GUI Subiekt GT
- [ ] Test tworzenia dokumentów z nowym produktem
- [ ] Test synchronizacji z innymi modułami

---

## 9. Decyzja i Uzasadnienie

### **REKOMENDACJA: NO-GO dla DirectSQL CREATE**

**Uzasadnienie:**

1. **Nieznany mechanizm ID** - Bez weryfikacji na serwerze nie można bezpiecznie generować tw_Id
2. **Wysoki koszt błędu** - Korupcja bazy produkcyjnej ERP = krytyczny business impact
3. **Dostępne alternatywy** - Sfera API lub import CSV są bezpieczniejsze
4. **Brak ROI** - Czas na debugowanie > czas na wdrożenie Sfera

### **ZALECANE DZIAŁANIA:**

1. **Krótkoterminowo:** Używaj REST API tylko do READ/UPDATE (działa)
2. **Średnioterminowo:** Rozważ licencję Sfera do CREATE operations
3. **Długoterminowo:** Integracja PPM → Sfera API → Subiekt GT

---

## 10. Załączniki

### A. SQL do weryfikacji na serwerze

```sql
-- Pełny skrypt diagnostyczny
-- Uruchom na MPP_TRADE przez SSMS

PRINT '=== DIAGNOSTYKA SUBIEKT GT - DIRECTSQL CREATE ===';
PRINT '';

-- 1. Sprawdź procedury
PRINT '1. Procedury składowane (Identyfikator):';
SELECT name, type_desc FROM sys.procedures
WHERE name LIKE '%Identyfikator%' OR name LIKE '%NextId%';

-- 2. Sprawdź IDENTITY
PRINT '2. Kolumna tw_Id - IDENTITY:';
SELECT c.name, c.is_identity
FROM sys.columns c
WHERE OBJECT_NAME(c.object_id) = 'tw__Towar' AND c.name = 'tw_Id';

-- 3. Sprawdź triggery
PRINT '3. Triggery na tw__Towar:';
SELECT name, is_instead_of_trigger FROM sys.triggers
WHERE OBJECT_NAME(parent_id) = 'tw__Towar';

-- 4. Sprawdź MAX(tw_Id)
PRINT '4. Aktualny MAX(tw_Id):';
SELECT MAX(tw_Id) AS max_id, COUNT(*) AS total_products FROM tw__Towar;

-- 5. Sprawdź unique constraints
PRINT '5. Unique constraints:';
SELECT i.name, c.name AS column_name
FROM sys.indexes i
JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = 'tw__Towar' AND i.is_unique = 1;
```

### B. Referencja do istniejących dokumentów

- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md` - Pełny schemat bazy
- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json` - Schemat w formacie JSON
- `.claude/rules/erp/subiekt-database-schema.md` - Zasady pracy ze schematem
- `.claude/rules/erp/subiekt-api-connection.md` - Konfiguracja REST API

---

**Koniec dokumentu**

*Wygenerowano przez: erp/directsql-create-analysis agent*
*Data: 2026-01-23*
