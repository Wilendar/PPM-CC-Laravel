# Subiekt GT - Przyklady Zapytan SQL

## Spis Tresci
1. [Produkty (Towary)](#produkty-towary)
2. [Stany Magazynowe](#stany-magazynowe)
3. [Ceny](#ceny)
4. [Kontrahenci](#kontrahenci)
5. [Dokumenty](#dokumenty)
6. [Kategorie i Grupy](#kategorie-i-grupy)
7. [Zapytania Zaawansowane](#zapytania-zaawansowane)

---

## Produkty (Towary)

### Pobranie wszystkich aktywnych produktow

```sql
SELECT
    tw_Id,
    tw_Symbol,
    tw_Nazwa,
    tw_Opis,
    tw_JM,           -- Jednostka miary
    tw_PKWiU,        -- Kod PKWiU
    tw_SWW,          -- Kod SWW
    tw_EAN           -- Kod EAN
FROM tw__Towar
WHERE tw_Aktywny = 1
ORDER BY tw_Nazwa
```

### Pobranie produktu po symbolu

```sql
SELECT *
FROM tw__Towar
WHERE tw_Symbol = @symbol
  AND tw_Aktywny = 1
```

### Pobranie produktow ze zmianami po dacie

```sql
SELECT
    tw_Id,
    tw_Symbol,
    tw_Nazwa,
    tw_DataMod      -- Data modyfikacji
FROM tw__Towar
WHERE tw_DataMod > @data_od
  AND tw_Aktywny = 1
ORDER BY tw_DataMod DESC
```

### Wyszukiwanie produktow po nazwie (LIKE)

```sql
SELECT
    tw_Id,
    tw_Symbol,
    tw_Nazwa
FROM tw__Towar
WHERE tw_Nazwa LIKE '%' + @szukana_fraza + '%'
  AND tw_Aktywny = 1
ORDER BY tw_Nazwa
```

### Produkty z kodami EAN

```sql
SELECT
    t.tw_Id,
    t.tw_Symbol,
    t.tw_Nazwa,
    t.tw_EAN as EAN_Glowny,
    e.te_EAN as EAN_Dodatkowy
FROM tw__Towar t
LEFT JOIN tw_EAN e ON t.tw_Id = e.te_TowId
WHERE t.tw_Aktywny = 1
  AND (t.tw_EAN IS NOT NULL OR e.te_EAN IS NOT NULL)
```

---

## Stany Magazynowe

### Stan wszystkich produktow na magazynie

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    m.mag_Symbol,
    m.mag_Nazwa,
    s.st_Stan,
    s.st_StanRez,    -- Stan zarezerwowany
    s.st_StanMin,    -- Stan minimalny
    s.st_StanMax     -- Stan maksymalny
FROM tw__Towar t
JOIN tw_Stan s ON t.tw_Id = s.st_TowId
JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
WHERE t.tw_Aktywny = 1
ORDER BY t.tw_Symbol
```

### Stan pojedynczego produktu na wszystkich magazynach

```sql
SELECT
    m.mag_Symbol,
    m.mag_Nazwa,
    ISNULL(s.st_Stan, 0) as Stan,
    ISNULL(s.st_StanRez, 0) as Zarezerwowany,
    ISNULL(s.st_Stan, 0) - ISNULL(s.st_StanRez, 0) as Dostepny
FROM sl_Magazyn m
LEFT JOIN tw_Stan s ON m.mag_Id = s.st_MagId
    AND s.st_TowId = (SELECT tw_Id FROM tw__Towar WHERE tw_Symbol = @symbol)
WHERE m.mag_Aktywny = 1
```

### Produkty ponizej stanu minimalnego

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    m.mag_Nazwa,
    s.st_Stan as Stan_Aktualny,
    s.st_StanMin as Stan_Minimalny,
    s.st_StanMin - s.st_Stan as Do_Zamowienia
FROM tw__Towar t
JOIN tw_Stan s ON t.tw_Id = s.st_TowId
JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
WHERE t.tw_Aktywny = 1
  AND s.st_Stan < s.st_StanMin
  AND s.st_StanMin > 0
ORDER BY (s.st_StanMin - s.st_Stan) DESC
```

### Sumaryczny stan na wszystkich magazynach

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    SUM(ISNULL(s.st_Stan, 0)) as Stan_Calkowity,
    SUM(ISNULL(s.st_StanRez, 0)) as Zarezerwowany_Calkowity
FROM tw__Towar t
LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId
WHERE t.tw_Aktywny = 1
GROUP BY t.tw_Id, t.tw_Symbol, t.tw_Nazwa
ORDER BY t.tw_Symbol
```

---

## Ceny

### Ceny detaliczne produktow

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    c.tc_CenaNetto,
    c.tc_CenaBrutto,
    c.tc_Waluta
FROM tw__Towar t
JOIN tw_Cena c ON t.tw_Id = c.tc_TowId
JOIN sl_RodzajCeny rc ON c.tc_RodzCenyId = rc.rc_Id
WHERE rc.rc_Nazwa = 'Detaliczna'
  AND t.tw_Aktywny = 1
```

### Wszystkie rodzaje cen dla produktu

```sql
SELECT
    rc.rc_Nazwa as Rodzaj_Ceny,
    c.tc_CenaNetto,
    c.tc_CenaBrutto,
    c.tc_Waluta,
    c.tc_DataOd,
    c.tc_DataDo
FROM tw_Cena c
JOIN sl_RodzajCeny rc ON c.tc_RodzCenyId = rc.rc_Id
WHERE c.tc_TowId = @towar_id
ORDER BY rc.rc_Kolejnosc
```

### Lista rodzajow cen (stara metoda)

```sql
SELECT
    rc_Id,
    rc_Symbol,
    rc_Nazwa,
    rc_Aktywny
FROM sl_RodzajCeny
WHERE rc_Aktywny = 1
ORDER BY rc_Kolejnosc
```

### NAZWY POZIOMOW CENOWYCH z tw_Parametr (REKOMENDOWANE!)

```sql
-- Ta tabela zawiera prawdziwe nazwy poziomow cenowych
-- KRYTYCZNE: twp_NazwaCeny[N] odpowiada tc_CenaNetto[N-1]!
SELECT TOP 1
    twp_NazwaCeny1 AS PriceLevel0,   -- tc_CenaNetto0
    twp_NazwaCeny2 AS PriceLevel1,   -- tc_CenaNetto1
    twp_NazwaCeny3 AS PriceLevel2,   -- tc_CenaNetto2
    twp_NazwaCeny4 AS PriceLevel3,   -- tc_CenaNetto3
    twp_NazwaCeny5 AS PriceLevel4,   -- tc_CenaNetto4
    twp_NazwaCeny6 AS PriceLevel5,   -- tc_CenaNetto5
    twp_NazwaCeny7 AS PriceLevel6,   -- tc_CenaNetto6
    twp_NazwaCeny8 AS PriceLevel7,   -- tc_CenaNetto7
    twp_NazwaCeny9 AS PriceLevel8,   -- tc_CenaNetto8
    twp_NazwaCeny10 AS PriceLevel9   -- tc_CenaNetto9
FROM tw_Parametr

-- Przyklad wynikow (MPP TRADE):
-- PriceLevel0 = "Detaliczna"
-- PriceLevel1 = "MRF-MPP"
-- PriceLevel2 = "Szkółka-Komis-Drop"
-- PriceLevel3 = "z magazynu"
-- PriceLevel4 = "Warsztat"
-- PriceLevel5 = "Standard"
-- PriceLevel6 = "Premium"
-- PriceLevel7 = "HuHa"
-- PriceLevel8 = "Warsztat Premium"
-- PriceLevel9 = "Pracownik"
```

### Produkty z cenami i marza

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    c.tc_CenaNetto as Cena_Zakupu,
    cs.tc_CenaNetto as Cena_Sprzedazy,
    cs.tc_CenaNetto - c.tc_CenaNetto as Marza_Kwota,
    CASE
        WHEN c.tc_CenaNetto > 0
        THEN ((cs.tc_CenaNetto - c.tc_CenaNetto) / c.tc_CenaNetto) * 100
        ELSE 0
    END as Marza_Procent
FROM tw__Towar t
JOIN tw_Cena c ON t.tw_Id = c.tc_TowId AND c.tc_RodzCenyId = 1  -- Zakupowa
JOIN tw_Cena cs ON t.tw_Id = cs.tc_TowId AND cs.tc_RodzCenyId = 2 -- Sprzedazowa
WHERE t.tw_Aktywny = 1
```

---

## Kontrahenci

### Lista wszystkich kontrahentow

```sql
SELECT
    kh_Id,
    kh_Symbol,
    kh_Nazwa,
    kh_NazwaPelna,
    kh_Nip,
    kh_Regon,
    kh_Email,
    kh_Telefon
FROM kh__Kontrahent
WHERE kh_Aktywny = 1
ORDER BY kh_Nazwa
```

### Kontrahent z adresami

```sql
SELECT
    k.kh_Symbol,
    k.kh_Nazwa,
    k.kh_Nip,
    ta.ta_Nazwa as Typ_Adresu,
    a.adr_Ulica,
    a.adr_NrDomu,
    a.adr_NrLokalu,
    a.adr_Miasto,
    a.adr_KodPoczt,
    a.adr_Kraj
FROM kh__Kontrahent k
LEFT JOIN adr__Ewid a ON k.kh_Id = a.adr_IdObiektu AND a.adr_TypObiektu = 1
LEFT JOIN sl_TypAdresu ta ON a.adr_TypAdresuId = ta.ta_Id
WHERE k.kh_Aktywny = 1
  AND k.kh_Symbol = @symbol
```

### Wyszukiwanie po NIP

```sql
SELECT
    kh_Id,
    kh_Symbol,
    kh_Nazwa,
    kh_Nip
FROM kh__Kontrahent
WHERE kh_Nip = REPLACE(REPLACE(@nip, '-', ''), ' ', '')
  AND kh_Aktywny = 1
```

### Kontrahenci z obrotami

```sql
SELECT
    k.kh_Symbol,
    k.kh_Nazwa,
    COUNT(DISTINCT d.dok_Id) as Liczba_Dokumentow,
    SUM(d.dok_WartoscNetto) as Obroty_Netto,
    SUM(d.dok_WartoscBrutto) as Obroty_Brutto
FROM kh__Kontrahent k
JOIN dok__Dokument d ON k.kh_Id = d.dok_OdbiorcaId
WHERE k.kh_Aktywny = 1
  AND d.dok_DataWyst >= @data_od
  AND d.dok_DataWyst <= @data_do
  AND d.dok_Typ IN (1, 2) -- Faktury sprzedazy
GROUP BY k.kh_Id, k.kh_Symbol, k.kh_Nazwa
ORDER BY SUM(d.dok_WartoscNetto) DESC
```

---

## Dokumenty

### Lista dokumentow sprzedazy

```sql
SELECT
    d.dok_Id,
    d.dok_NrPelny,
    d.dok_DataWyst,
    d.dok_DataSprzed,
    k.kh_Nazwa as Kontrahent,
    d.dok_WartoscNetto,
    d.dok_WartoscBrutto,
    d.dok_WartoscVat
FROM dok__Dokument d
LEFT JOIN kh__Kontrahent k ON d.dok_OdbiorcaId = k.kh_Id
WHERE d.dok_Typ = 1  -- Faktura sprzedazy
ORDER BY d.dok_DataWyst DESC
```

### Pozycje dokumentu

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    p.ob_Ilosc,
    p.ob_JM,
    p.ob_CenaNetto,
    p.ob_CenaBrutto,
    p.ob_Wartosc,
    p.ob_WartoscBrutto,
    p.ob_StawkaVat
FROM dok_Pozycja p
JOIN tw__Towar t ON p.ob_TowId = t.tw_Id
WHERE p.ob_DokHanId = @dokument_id
ORDER BY p.ob_Lp
```

### Dokumenty z okresu z podsumowaniem

```sql
SELECT
    d.dok_NrPelny,
    d.dok_DataWyst,
    k.kh_Nazwa,
    d.dok_WartoscNetto,
    d.dok_WartoscVat,
    d.dok_WartoscBrutto,
    (
        SELECT COUNT(*)
        FROM dok_Pozycja p
        WHERE p.ob_DokHanId = d.dok_Id
    ) as Liczba_Pozycji
FROM dok__Dokument d
LEFT JOIN kh__Kontrahent k ON d.dok_OdbiorcaId = k.kh_Id
WHERE d.dok_DataWyst BETWEEN @data_od AND @data_do
  AND d.dok_Typ IN (1, 2)
ORDER BY d.dok_DataWyst DESC
```

### Typy dokumentow

```sql
-- Typowe wartosci dok_Typ:
-- 1 = Faktura VAT sprzedazy
-- 2 = Faktura korygujaca
-- 3 = Paragon
-- 4 = Zamowienie od klienta
-- 5 = Zamowienie do dostawcy
-- 6 = Przyjecie zewnetrzne (PZ)
-- 7 = Wydanie zewnetrzne (WZ)
-- 8 = Przesunicie miedzymagazynowe (MM)

SELECT DISTINCT dok_Typ, dok_TypNazwa
FROM dok__Dokument
ORDER BY dok_Typ
```

---

## Kategorie i Grupy

### Drzewo kategorii produktow

```sql
WITH KategorieTree AS (
    SELECT
        grt_Id,
        grt_Nazwa,
        grt_ParentId,
        0 as Poziom,
        CAST(grt_Nazwa as VARCHAR(1000)) as Sciezka
    FROM sl_GrupaTowarow
    WHERE grt_ParentId IS NULL

    UNION ALL

    SELECT
        g.grt_Id,
        g.grt_Nazwa,
        g.grt_ParentId,
        k.Poziom + 1,
        CAST(k.Sciezka + ' > ' + g.grt_Nazwa as VARCHAR(1000))
    FROM sl_GrupaTowarow g
    JOIN KategorieTree k ON g.grt_ParentId = k.grt_Id
)
SELECT * FROM KategorieTree
ORDER BY Sciezka
```

### Produkty w kategorii (z podkategoriami)

```sql
WITH KategorieTree AS (
    SELECT grt_Id
    FROM sl_GrupaTowarow
    WHERE grt_Id = @kategoria_id

    UNION ALL

    SELECT g.grt_Id
    FROM sl_GrupaTowarow g
    JOIN KategorieTree k ON g.grt_ParentId = k.grt_Id
)
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    g.grt_Nazwa as Kategoria
FROM tw__Towar t
JOIN sl_GrupaTowarow g ON t.tw_GrupaId = g.grt_Id
WHERE t.tw_GrupaId IN (SELECT grt_Id FROM KategorieTree)
  AND t.tw_Aktywny = 1
```

---

## Zapytania Zaawansowane

### Pelne dane produktu (wszystkie relacje)

```sql
SELECT
    -- Dane podstawowe
    t.tw_Id,
    t.tw_Symbol,
    t.tw_Nazwa,
    t.tw_Opis,
    t.tw_JM,
    t.tw_EAN,

    -- Kategoria
    g.grt_Nazwa as Kategoria,

    -- Cena detaliczna
    cd.tc_CenaNetto as Cena_Detal_Netto,
    cd.tc_CenaBrutto as Cena_Detal_Brutto,

    -- Cena hurtowa
    ch.tc_CenaNetto as Cena_Hurt_Netto,
    ch.tc_CenaBrutto as Cena_Hurt_Brutto,

    -- Stan magazynowy
    ISNULL(s.st_Stan, 0) as Stan,
    ISNULL(s.st_StanRez, 0) as Zarezerwowany,

    -- Stawka VAT
    v.sv_Stawka as VAT_Procent,

    -- Daty
    t.tw_DataUtworzenia,
    t.tw_DataMod

FROM tw__Towar t
LEFT JOIN sl_GrupaTowarow g ON t.tw_GrupaId = g.grt_Id
LEFT JOIN tw_Cena cd ON t.tw_Id = cd.tc_TowId AND cd.tc_RodzCenyId = 1
LEFT JOIN tw_Cena ch ON t.tw_Id = ch.tc_TowId AND ch.tc_RodzCenyId = 2
LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = 1
LEFT JOIN sl_StawkaVat v ON t.tw_StawkaVatId = v.sv_Id
WHERE t.tw_Symbol = @symbol
  AND t.tw_Aktywny = 1
```

### Raport sprzedazy produktu

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    YEAR(d.dok_DataWyst) as Rok,
    MONTH(d.dok_DataWyst) as Miesiac,
    SUM(p.ob_Ilosc) as Sprzedana_Ilosc,
    SUM(p.ob_Wartosc) as Wartosc_Netto,
    SUM(p.ob_WartoscBrutto) as Wartosc_Brutto
FROM dok_Pozycja p
JOIN dok__Dokument d ON p.ob_DokHanId = d.dok_Id
JOIN tw__Towar t ON p.ob_TowId = t.tw_Id
WHERE d.dok_Typ IN (1, 3) -- Faktury i paragony
  AND d.dok_DataWyst >= @data_od
  AND d.dok_DataWyst <= @data_do
  AND t.tw_Symbol = @symbol
GROUP BY t.tw_Symbol, t.tw_Nazwa, YEAR(d.dok_DataWyst), MONTH(d.dok_DataWyst)
ORDER BY Rok DESC, Miesiac DESC
```

### TOP 10 najlepiej sprzedajacych sie produktow

```sql
SELECT TOP 10
    t.tw_Symbol,
    t.tw_Nazwa,
    SUM(p.ob_Ilosc) as Sprzedana_Ilosc,
    SUM(p.ob_WartoscBrutto) as Wartosc_Sprzedazy,
    COUNT(DISTINCT d.dok_Id) as Liczba_Transakcji
FROM dok_Pozycja p
JOIN dok__Dokument d ON p.ob_DokHanId = d.dok_Id
JOIN tw__Towar t ON p.ob_TowId = t.tw_Id
WHERE d.dok_Typ IN (1, 3)
  AND d.dok_DataWyst >= DATEADD(MONTH, -1, GETDATE())
GROUP BY t.tw_Id, t.tw_Symbol, t.tw_Nazwa
ORDER BY SUM(p.ob_WartoscBrutto) DESC
```

### Synchronizacja - produkty zmienione od ostatniego sync

```sql
DECLARE @ostatni_sync DATETIME = '2026-01-01 00:00:00'

SELECT
    t.tw_Id,
    t.tw_Symbol,
    t.tw_Nazwa,
    t.tw_DataMod,
    'towar' as Typ_Zmiany
FROM tw__Towar t
WHERE t.tw_DataMod > @ostatni_sync

UNION ALL

SELECT
    c.tc_TowId,
    t.tw_Symbol,
    t.tw_Nazwa,
    c.tc_DataMod,
    'cena' as Typ_Zmiany
FROM tw_Cena c
JOIN tw__Towar t ON c.tc_TowId = t.tw_Id
WHERE c.tc_DataMod > @ostatni_sync

UNION ALL

SELECT
    s.st_TowId,
    t.tw_Symbol,
    t.tw_Nazwa,
    s.st_DataMod,
    'stan' as Typ_Zmiany
FROM tw_Stan s
JOIN tw__Towar t ON s.st_TowId = t.tw_Id
WHERE s.st_DataMod > @ostatni_sync

ORDER BY tw_DataMod DESC
```

---

## Uwagi

### Bezpieczenstwo

1. **NIGDY** nie wykonuj INSERT/UPDATE/DELETE bez Sfera API
2. Uzywaj parametryzowanych zapytan - unikaj SQL Injection
3. Testuj zapytania na kopii bazy

### Wydajnosc

1. Uzywaj indeksow (tw_Symbol, tw_EAN, kh_Nip sa indeksowane)
2. Ogranicz SELECT * - pobieraj tylko potrzebne kolumny
3. Uzywaj TOP/OFFSET dla duzych zbiorow danych
4. Cache wynikow po stronie aplikacji

### Wersje Subiekt

Struktura bazy moze sie roznic miedzy wersjami Subiekt GT. Te przyklady sa dla wersji 1.45+.
