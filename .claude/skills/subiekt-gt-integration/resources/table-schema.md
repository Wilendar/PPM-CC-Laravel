# Subiekt GT - Struktura Bazy Danych

## Spis Tresci
1. [Towary](#towary)
2. [Kontrahenci](#kontrahenci)
3. [Dokumenty](#dokumenty)
4. [Magazyny](#magazyny)
5. [Slowniki](#slowniki)
6. [System](#system)

---

## Towary

### tw__Towar (Glowna tabela produktow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| tw_Id | INT | Klucz glowny |
| tw_Symbol | VARCHAR(40) | Symbol/SKU produktu |
| tw_Nazwa | VARCHAR(100) | Nazwa produktu |
| tw_NazwaPelna | VARCHAR(255) | Pelna nazwa |
| tw_Opis | TEXT | Opis produktu |
| tw_JM | VARCHAR(10) | Jednostka miary |
| tw_EAN | VARCHAR(20) | Kod EAN glowny |
| tw_PKWiU | VARCHAR(20) | Kod PKWiU |
| tw_SWW | VARCHAR(20) | Kod SWW |
| tw_Typ | INT | Typ towaru |
| tw_GrupaId | INT | FK do sl_GrupaTowarow |
| tw_StawkaVatId | INT | FK do sl_StawkaVat |
| tw_ProducentId | INT | FK do kh__Kontrahent |
| tw_DostawcaId | INT | FK do kh__Kontrahent |
| tw_Waga | DECIMAL | Waga produktu |
| tw_WagaJM | VARCHAR(10) | Jednostka wagi |
| tw_Aktywny | BIT | Czy aktywny |
| tw_DataUtworzenia | DATETIME | Data utworzenia |
| tw_DataMod | DATETIME | Data modyfikacji |
| tw_KodKreskowy | VARCHAR(50) | Kod kreskowy |

### tw_Stan (Stany magazynowe)

| Kolumna | Typ | Opis |
|---------|-----|------|
| st_Id | INT | Klucz glowny |
| st_TowId | INT | FK do tw__Towar |
| st_MagId | INT | FK do sl_Magazyn |
| st_Stan | DECIMAL(18,4) | Aktualna ilosc |
| st_StanRez | DECIMAL(18,4) | Ilosc zarezerwowana |
| st_StanMin | DECIMAL(18,4) | Stan minimalny |
| st_StanMax | DECIMAL(18,4) | Stan maksymalny |
| st_DataMod | DATETIME | Data modyfikacji |

### tw_Cena (Ceny produktow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| tc_Id | INT | Klucz glowny |
| tc_TowId | INT | FK do tw__Towar |
| tc_RodzCenyId | INT | FK do sl_RodzajCeny |
| tc_CenaNetto | DECIMAL(18,4) | Cena netto |
| tc_CenaBrutto | DECIMAL(18,4) | Cena brutto |
| tc_Waluta | VARCHAR(3) | Kod waluty (PLN) |
| tc_DataOd | DATETIME | Cena obowiazuje od |
| tc_DataDo | DATETIME | Cena obowiazuje do |
| tc_DataMod | DATETIME | Data modyfikacji |

### tw_EAN (Dodatkowe kody EAN)

| Kolumna | Typ | Opis |
|---------|-----|------|
| te_Id | INT | Klucz glowny |
| te_TowId | INT | FK do tw__Towar |
| te_EAN | VARCHAR(20) | Kod EAN |
| te_JM | VARCHAR(10) | Jednostka miary |
| te_Przelicznik | DECIMAL | Przelicznik |

### tw_Atrybut (Atrybuty produktow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| ta_Id | INT | Klucz glowny |
| ta_TowId | INT | FK do tw__Towar |
| ta_AtrybutId | INT | FK do sl_Atrybut |
| ta_Wartosc | VARCHAR(255) | Wartosc atrybutu |

---

## Kontrahenci

### kh__Kontrahent (Glowna tabela kontrahentow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| kh_Id | INT | Klucz glowny |
| kh_Symbol | VARCHAR(20) | Symbol kontrahenta |
| kh_Nazwa | VARCHAR(100) | Nazwa skrocona |
| kh_NazwaPelna | VARCHAR(255) | Nazwa pelna |
| kh_Nip | VARCHAR(20) | NIP (bez myslnikow) |
| kh_Regon | VARCHAR(20) | REGON |
| kh_Pesel | VARCHAR(11) | PESEL |
| kh_Email | VARCHAR(100) | Email |
| kh_Telefon | VARCHAR(50) | Telefon |
| kh_Fax | VARCHAR(50) | Fax |
| kh_WWW | VARCHAR(100) | Strona WWW |
| kh_NrKonta | VARCHAR(50) | Numer konta |
| kh_NrKontaNazwa | VARCHAR(100) | Nazwa banku |
| kh_Typ | INT | Typ kontrahenta |
| kh_GrupaId | INT | FK do sl_GrupaKontrahentow |
| kh_DostawcaId | INT | Domyslny dostawca |
| kh_Aktywny | BIT | Czy aktywny |
| kh_DataUtworzenia | DATETIME | Data utworzenia |
| kh_DataMod | DATETIME | Data modyfikacji |

### adr__Ewid (Adresy)

| Kolumna | Typ | Opis |
|---------|-----|------|
| adr_Id | INT | Klucz glowny |
| adr_IdObiektu | INT | ID obiektu (kontrahenta) |
| adr_TypObiektu | INT | Typ obiektu (1=kontrahent) |
| adr_TypAdresuId | INT | FK do sl_TypAdresu |
| adr_Nazwa | VARCHAR(100) | Nazwa adresu |
| adr_Ulica | VARCHAR(100) | Ulica |
| adr_NrDomu | VARCHAR(20) | Numer domu |
| adr_NrLokalu | VARCHAR(20) | Numer lokalu |
| adr_Miasto | VARCHAR(50) | Miasto |
| adr_KodPoczt | VARCHAR(10) | Kod pocztowy |
| adr_Poczta | VARCHAR(50) | Poczta |
| adr_Kraj | VARCHAR(50) | Kraj |
| adr_Wojewodztwo | VARCHAR(50) | Wojewodztwo |
| adr_Powiat | VARCHAR(50) | Powiat |
| adr_Gmina | VARCHAR(50) | Gmina |
| adr_Glowny | BIT | Czy adres glowny |

### kh_Osoba (Osoby kontaktowe)

| Kolumna | Typ | Opis |
|---------|-----|------|
| ko_Id | INT | Klucz glowny |
| ko_KhId | INT | FK do kh__Kontrahent |
| ko_Imie | VARCHAR(50) | Imie |
| ko_Nazwisko | VARCHAR(50) | Nazwisko |
| ko_Stanowisko | VARCHAR(50) | Stanowisko |
| ko_Email | VARCHAR(100) | Email |
| ko_Telefon | VARCHAR(50) | Telefon |

---

## Dokumenty

### dok__Dokument (Naglowki dokumentow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| dok_Id | INT | Klucz glowny |
| dok_NrPelny | VARCHAR(50) | Pelny numer dokumentu |
| dok_NrKolejny | INT | Numer kolejny |
| dok_Typ | INT | Typ dokumentu |
| dok_TypNazwa | VARCHAR(50) | Nazwa typu |
| dok_OdbiorcaId | INT | FK kontrahent |
| dok_DostawcaId | INT | FK dostawca |
| dok_MagazynId | INT | FK magazyn |
| dok_DataWyst | DATETIME | Data wystawienia |
| dok_DataSprzed | DATETIME | Data sprzedazy |
| dok_DataPlatn | DATETIME | Termin platnosci |
| dok_WartoscNetto | DECIMAL | Wartosc netto |
| dok_WartoscVat | DECIMAL | Wartosc VAT |
| dok_WartoscBrutto | DECIMAL | Wartosc brutto |
| dok_Waluta | VARCHAR(3) | Waluta |
| dok_Kurs | DECIMAL | Kurs waluty |
| dok_Status | INT | Status dokumentu |
| dok_Uwagi | TEXT | Uwagi |
| dok_DataUtworzenia | DATETIME | Data utworzenia |
| dok_DataMod | DATETIME | Data modyfikacji |

#### Wartosci dok_Typ

| Typ | Opis |
|-----|------|
| 1 | Faktura VAT sprzedazy |
| 2 | Faktura korygujaca |
| 3 | Paragon |
| 4 | Zamowienie od klienta (ZK) |
| 5 | Zamowienie do dostawcy (ZD) |
| 6 | Przyjecie zewnetrzne (PZ) |
| 7 | Wydanie zewnetrzne (WZ) |
| 8 | Przesunicie miedzymagazynowe (MM) |
| 9 | Przyjecie wewnetrzne (PW) |
| 10 | Wydanie wewnetrzne (RW) |

### dok_Pozycja (Pozycje dokumentow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| ob_Id | INT | Klucz glowny |
| ob_DokHanId | INT | FK do dok__Dokument |
| ob_TowId | INT | FK do tw__Towar |
| ob_Lp | INT | Numer pozycji |
| ob_Nazwa | VARCHAR(100) | Nazwa (kopia z towaru) |
| ob_Symbol | VARCHAR(40) | Symbol (kopia) |
| ob_JM | VARCHAR(10) | Jednostka miary |
| ob_Ilosc | DECIMAL(18,4) | Ilosc |
| ob_CenaNetto | DECIMAL(18,4) | Cena jednostkowa netto |
| ob_CenaBrutto | DECIMAL(18,4) | Cena jednostkowa brutto |
| ob_Wartosc | DECIMAL(18,2) | Wartosc netto |
| ob_WartoscBrutto | DECIMAL(18,2) | Wartosc brutto |
| ob_WartoscVat | DECIMAL(18,2) | Wartosc VAT |
| ob_StawkaVat | DECIMAL(5,2) | Stawka VAT % |
| ob_Rabat | DECIMAL(5,2) | Rabat % |

### dok_Platnosc (Platnosci dokumentow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| dp_Id | INT | Klucz glowny |
| dp_DokId | INT | FK do dok__Dokument |
| dp_FormaPlatnId | INT | FK do sl_FormaPlatnosci |
| dp_Kwota | DECIMAL | Kwota platnosci |
| dp_TerminPlatn | DATETIME | Termin platnosci |
| dp_DataZaplaty | DATETIME | Data zaplaty |
| dp_Zaplacono | BIT | Czy zaplacone |

---

## Magazyny

### sl_Magazyn (Magazyny)

| Kolumna | Typ | Opis |
|---------|-----|------|
| mag_Id | INT | Klucz glowny |
| mag_Symbol | VARCHAR(10) | Symbol magazynu |
| mag_Nazwa | VARCHAR(50) | Nazwa magazynu |
| mag_Opis | VARCHAR(255) | Opis |
| mag_Adres | VARCHAR(255) | Adres |
| mag_Domyslny | BIT | Czy domyslny |
| mag_Aktywny | BIT | Czy aktywny |

---

## Slowniki

### sl_RodzajCeny (Rodzaje cen)

| Kolumna | Typ | Opis |
|---------|-----|------|
| rc_Id | INT | Klucz glowny |
| rc_Symbol | VARCHAR(10) | Symbol |
| rc_Nazwa | VARCHAR(50) | Nazwa |
| rc_Typ | INT | Typ ceny (netto/brutto) |
| rc_Kolejnosc | INT | Kolejnosc wyswietlania |
| rc_Aktywny | BIT | Czy aktywny |

### sl_StawkaVat (Stawki VAT)

| Kolumna | Typ | Opis |
|---------|-----|------|
| sv_Id | INT | Klucz glowny |
| sv_Symbol | VARCHAR(5) | Symbol (A, B, C...) |
| sv_Nazwa | VARCHAR(20) | Nazwa |
| sv_Stawka | DECIMAL(5,2) | Stawka procentowa |
| sv_Aktywny | BIT | Czy aktywny |

### sl_GrupaTowarow (Kategorie produktow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| grt_Id | INT | Klucz glowny |
| grt_ParentId | INT | FK rodzica (hierarchia) |
| grt_Symbol | VARCHAR(20) | Symbol |
| grt_Nazwa | VARCHAR(100) | Nazwa |
| grt_Opis | TEXT | Opis |
| grt_Poziom | INT | Poziom zaglebienia |
| grt_Aktywny | BIT | Czy aktywny |

### sl_GrupaKontrahentow (Grupy kontrahentow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| grk_Id | INT | Klucz glowny |
| grk_ParentId | INT | FK rodzica |
| grk_Symbol | VARCHAR(20) | Symbol |
| grk_Nazwa | VARCHAR(100) | Nazwa |
| grk_Aktywny | BIT | Czy aktywny |

### sl_FormaPlatnosci (Formy platnosci)

| Kolumna | Typ | Opis |
|---------|-----|------|
| fp_Id | INT | Klucz glowny |
| fp_Symbol | VARCHAR(10) | Symbol |
| fp_Nazwa | VARCHAR(50) | Nazwa |
| fp_TerminDni | INT | Domyslny termin (dni) |
| fp_Aktywny | BIT | Czy aktywny |

### sl_TypAdresu (Typy adresow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| ta_Id | INT | Klucz glowny |
| ta_Symbol | VARCHAR(10) | Symbol |
| ta_Nazwa | VARCHAR(50) | Nazwa |

Typowe wartosci:
- 1 = Adres glowny
- 2 = Adres korespondencyjny
- 3 = Adres dostawy

### sl_Atrybut (Definicje atrybutow)

| Kolumna | Typ | Opis |
|---------|-----|------|
| at_Id | INT | Klucz glowny |
| at_Symbol | VARCHAR(20) | Symbol |
| at_Nazwa | VARCHAR(100) | Nazwa |
| at_Typ | INT | Typ (tekst/liczba/data) |
| at_DlaTowarow | BIT | Dla produktow |
| at_DlaKontrahentow | BIT | Dla kontrahentow |

---

## System

### ins_ident (Sekwencje ID)

| Kolumna | Typ | Opis |
|---------|-----|------|
| ii_Tabela | VARCHAR(50) | Nazwa tabeli |
| ii_Kolumna | VARCHAR(50) | Nazwa kolumny |
| ii_Identyfikator | INT | Ostatni uzyty ID |

### Procedura spIdentyfikator

```sql
-- Generowanie nowego ID
DECLARE @nowe_id INT
EXEC spIdentyfikator 'tw__towar', 1, @nowe_id OUTPUT
-- @nowe_id zawiera kolejny dostepny ID

-- Parametry:
-- 1: Nazwa tabeli (np. 'tw__towar', 'kh__kontrahent', 'dok__dokument')
-- 2: Ilosc ID do zarezerwowania (zwykle 1)
-- 3: OUTPUT - zwrocony nowy ID
```

**WAZNE**: Zawsze uzywaj tej procedury do generowania ID przy INSERT. Nigdy nie uzywaj MAX(id)+1!

---

## Relacje Kluczowe

```
tw__Towar
  ├── tw_Stan (1:N) - stany na magazynach
  ├── tw_Cena (1:N) - ceny
  ├── tw_EAN (1:N) - dodatkowe kody EAN
  ├── tw_Atrybut (1:N) - atrybuty
  └── sl_GrupaTowarow (N:1) - kategoria

kh__Kontrahent
  ├── adr__Ewid (1:N) - adresy
  ├── kh_Osoba (1:N) - osoby kontaktowe
  └── sl_GrupaKontrahentow (N:1) - grupa

dok__Dokument
  ├── dok_Pozycja (1:N) - pozycje
  ├── dok_Platnosc (1:N) - platnosci
  ├── kh__Kontrahent (N:1) - odbiorca
  └── sl_Magazyn (N:1) - magazyn
```

---

## Indeksy

### Najwazniejsze indeksy dla wydajnosci

```
tw__Towar:
  - PK: tw_Id
  - IX: tw_Symbol (UNIQUE)
  - IX: tw_EAN
  - IX: tw_GrupaId
  - IX: tw_Aktywny

kh__Kontrahent:
  - PK: kh_Id
  - IX: kh_Symbol (UNIQUE)
  - IX: kh_Nip
  - IX: kh_Aktywny

dok__Dokument:
  - PK: dok_Id
  - IX: dok_NrPelny
  - IX: dok_DataWyst
  - IX: dok_OdbiorcaId
  - IX: dok_Typ
```

---

## Uwagi

1. **Prefiksy tabel**:
   - `tw_` - towary
   - `kh_` - kontrahenci
   - `dok_` - dokumenty
   - `sl_` - slowniki
   - `adr_` - adresy
   - `ins_` - system

2. **Konwencje nazewnictwa kolumn**:
   - `_Id` - klucz glowny
   - `_*Id` - klucz obcy
   - `_Aktywny` - flaga aktywnosci
   - `_DataMod` - data ostatniej modyfikacji
   - `_DataUtworzenia` - data utworzenia

3. **Wersje**:
   Struktura moze sie roznic miedzy wersjami Subiekt GT.
   Te informacje dotycza wersji 1.45+.
