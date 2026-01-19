---
name: subiekt-gt-integration
description: Integracja z ERP Subiekt GT (InsERT) - SQL Server, Sfera API, REST wrapper. Uzyj przy synchronizacji produktow, kontrahentow, zamowien z systemem ERP.
version: 1.0.0
author: Kamil Wilinski
created: 2026-01-19
updated: 2026-01-19
tags: [subiekt, insert, erp, sql-server, sfera, integracja, magazyn]
category: integration
status: active
---

# Subiekt GT Integration Skill

## Overview

Skill zawiera kompletna dokumentacje i wzorce integracji z systemem **Subiekt GT** - popularnym polskim programem ERP firmy InsERT. Obejmuje trzy glowne metody integracji oraz szczegolowa dokumentacje struktury bazy danych.

## Kiedy uzywac tego Skilla

Uzyj tego skilla gdy:
- Integrujesz aplikacje Laravel/PHP z Subiekt GT
- Synchronizujesz produkty, stany magazynowe lub ceny
- Importujesz/eksportujesz kontrahentow
- Tworzysz dokumenty sprzedazy (faktury, paragony)
- Potrzebujesz informacji o strukturze bazy danych Subiekt GT
- Konfigurujesz polaczenie z MS SQL Server InsERT

---

## Metody Integracji

### Porownienie metod

| Metoda | Zalety | Wady | Kiedy uzywac |
|--------|--------|------|--------------|
| **Bezposredni SQL** | Szybkie odczyty, pelna kontrola | Ryzyko przy zapisie | Odczyty, raporty, sync stanow |
| **Sfera GT (COM/OLE)** | Bezpieczne, wspierane | Wymaga licencji, tylko Windows | Tworzenie dokumentow, zapis danych |
| **REST API wrapper** | Nowoczesne, cross-platform | Wymaga serwera | Aplikacje webowe, API |

---

## 1. Polaczenie z Baza Danych (SQL Server)

### Connection String

```
Server=(local)\INSERTGT;Database=NAZWA_FIRMY;User Id=sa;Password=;TrustServerCertificate=True
```

### Domyslne dane polaczenia

| Parametr | Wartosc domyslna |
|----------|------------------|
| Serwer | `(local)\INSERTGT` |
| Port | 1433, 1434 |
| Uzytkownik SQL | `sa` |
| Haslo | (puste domyslnie) |
| Config | `C:\ProgramData\InsERT\InsERT GT\admin.xml` |

### Laravel .env konfiguracja

```env
SUBIEKT_DB_CONNECTION=sqlsrv
SUBIEKT_DB_HOST=(local)\INSERTGT
SUBIEKT_DB_PORT=1433
SUBIEKT_DB_DATABASE=NazwaFirmy
SUBIEKT_DB_USERNAME=sa
SUBIEKT_DB_PASSWORD=
```

### Laravel config/database.php

```php
'subiekt' => [
    'driver' => 'sqlsrv',
    'host' => env('SUBIEKT_DB_HOST', '(local)\INSERTGT'),
    'port' => env('SUBIEKT_DB_PORT', '1433'),
    'database' => env('SUBIEKT_DB_DATABASE', 'Firma'),
    'username' => env('SUBIEKT_DB_USERNAME', 'sa'),
    'password' => env('SUBIEKT_DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'encrypt' => 'no',
    'trust_server_certificate' => true,
],
```

### Wymagane rozszerzenia PHP

```ini
extension=pdo_sqlsrv
extension=sqlsrv
```

Instalacja (Windows):
```powershell
# Pobierz sterowniki Microsoft SQL Server dla PHP
# https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
```

---

## 2. Struktura Bazy Danych - Quick Reference

### Glowne tabele

#### TOWARY (Produkty)
```
tw__Towar       - Glowna tabela produktow
  tw_Id         - ID produktu (INT, PK)
  tw_Symbol     - Symbol/SKU (VARCHAR)
  tw_Nazwa      - Nazwa produktu (VARCHAR)
  tw_Opis       - Opis (TEXT)
  tw_Aktywny    - Czy aktywny (BIT)

tw_Stan         - Stany magazynowe
  st_TowId      - ID produktu (FK)
  st_Stan       - Ilosc na stanie (DECIMAL)
  st_MagId      - ID magazynu (FK)

tw_Cena         - Ceny produktow
  tc_TowId      - ID produktu (FK)
  tc_CenaNetto  - Cena netto (DECIMAL)
  tc_CenaBrutto - Cena brutto (DECIMAL)
  tc_RodzCenyId - ID rodzaju ceny (FK)
```

#### KONTRAHENCI
```
kh__Kontrahent  - Glowna tabela kontrahentow
  kh_Id         - ID kontrahenta (INT, PK)
  kh_Symbol     - Symbol kontrahenta (VARCHAR)
  kh_Nazwa      - Nazwa/firma (VARCHAR)
  kh_Nip        - NIP (VARCHAR)
  kh_Aktywny    - Czy aktywny (BIT)

adr__Ewid       - Adresy
  adr_IdObiektu - ID kontrahenta (FK)
  adr_Nazwa     - Nazwa adresu (VARCHAR)
  adr_Ulica     - Ulica (VARCHAR)
  adr_Miasto    - Miasto (VARCHAR)
  adr_KodPoczt  - Kod pocztowy (VARCHAR)
```

#### DOKUMENTY
```
dok__Dokument   - Naglowki dokumentow
  dok_Id        - ID dokumentu (INT, PK)
  dok_NrPelny   - Pelny numer dokumentu (VARCHAR)
  dok_OdbiorcaId- ID kontrahenta (FK)
  dok_DataWyst  - Data wystawienia (DATETIME)
  dok_Typ       - Typ dokumentu (INT)

dok_Pozycja     - Pozycje dokumentow
  ob_Id         - ID pozycji (INT, PK)
  ob_DokHanId   - ID dokumentu (FK)
  ob_TowId      - ID produktu (FK)
  ob_Ilosc      - Ilosc (DECIMAL)
  ob_CenaNetto  - Cena netto (DECIMAL)
  ob_Wartosc    - Wartosc pozycji (DECIMAL)
```

### Generowanie ID - KRYTYCZNE!

**NIGDY nie uzywaj MAX(id)+1** - Subiekt ma wlasny mechanizm!

```sql
DECLARE @nowe_id INT
EXEC spIdentyfikator 'tw__towar', 1, @nowe_id OUTPUT
-- @nowe_id zawiera kolejny dostepny ID
```

Tabela pomocnicza: `ins_ident` - przechowuje sekwencje ID

---

## 3. Najczestsze Zapytania SQL

### Pobranie produktow z cenami i stanami

```sql
SELECT
    t.tw_Id,
    t.tw_Symbol,
    t.tw_Nazwa,
    c.tc_CenaNetto,
    c.tc_CenaBrutto,
    ISNULL(s.st_Stan, 0) as Stan
FROM tw__Towar t
LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId AND c.tc_RodzCenyId = 1
LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = 1
WHERE t.tw_Aktywny = 1
```

### Pobranie kontrahentow z adresami

```sql
SELECT
    k.kh_Id,
    k.kh_Symbol,
    k.kh_Nazwa,
    k.kh_Nip,
    a.adr_Ulica,
    a.adr_Miasto,
    a.adr_KodPoczt
FROM kh__Kontrahent k
LEFT JOIN adr__Ewid a ON k.kh_Id = a.adr_IdObiektu AND a.adr_TypAdresuId = 1
WHERE k.kh_Aktywny = 1
```

### Sprawdzenie stanu magazynowego

```sql
SELECT
    t.tw_Symbol,
    t.tw_Nazwa,
    s.st_Stan,
    m.mag_Nazwa
FROM tw__Towar t
JOIN tw_Stan s ON t.tw_Id = s.st_TowId
JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
WHERE t.tw_Symbol = @symbol
```

> **Wiecej przykladow:** Zobacz `SQL_EXAMPLES.md`

---

## 4. Sfera GT - API Obiektowe

### Inicjalizacja polaczenia (PHP)

```php
// Wymaga rozszerzenia com_dotnet
$gt = new COM('Insert.gt');
$gt->Produkt = 1; // 1 = Subiekt GT
$gt->Serwer = '(local)\INSERTGT';
$gt->Baza = 'NazwaFirmy';
$gt->Autentykacja = 0; // 0 = Windows Auth, 1 = SQL Auth
$gt->Uzytkownik = 'sa';
$gt->UzytkownikHaslo = '';

$subiekt = $gt->Uruchom(0, 4); // 0 = ukryty, 4 = w tle
```

### Glowne managery Sfera

| Manager | Opis |
|---------|------|
| `TowaryManager` | Zarzadzanie produktami |
| `KontrahenciManager` | Zarzadzanie kontrahentami |
| `DokumentyManager` | Tworzenie/edycja dokumentow |
| `MagazynyManager` | Operacje magazynowe |

### Przyklad: Pobranie produktu

```php
$towary = $subiekt->TowaryManager;
$towar = $towary->Wczytaj($symbol);

if ($towar) {
    echo $towar->Nazwa;
    echo $towar->CenaDetaliczna;
    echo $towar->Stan; // Stan na domyslnym magazynie
}
```

### Przyklad: Utworzenie dokumentu

```php
$dokumenty = $subiekt->DokumentyManager;
$faktura = $dokumenty->DodajFaktureSprzedazy();

$faktura->KontrahentId = $kh_id;
$faktura->DataWystawienia = date('Y-m-d');

// Dodanie pozycji
$pozycja = $faktura->Pozycje->Dodaj();
$pozycja->TowarId = $tw_id;
$pozycja->Ilosc = 2;
$pozycja->CenaNetto = 100.00;

$faktura->Zapisz();
echo "Utworzono: " . $faktura->NumerPelny;
```

> **Pelna dokumentacja:** Zobacz `resources/sfera-api.md`

---

## 5. REST API Wrapper (Lukegpl)

### Zrodlo
https://github.com/Lukegpl/api-subiekt-gt

### Dostepne endpointy

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/order/add` | POST | Dodanie zamowienia |
| `/api/order/makesaledoc` | POST | Konwersja na dok. sprzedazy |
| `/api/order/get` | POST | Pobranie zamowienia |
| `/api/order/getpdf` | POST | Export PDF |
| `/api/document/get` | POST | Pobranie dokumentu |
| `/api/document/getstate` | POST | Status dokumentu |
| `/api/product/add` | POST | Dodanie produktu |
| `/api/product/get` | POST | Pobranie produktu |

### Przyklad uzycia (Laravel)

```php
use Illuminate\Support\Facades\Http;

$response = Http::post('http://localhost:8080/api/product/get', [
    'symbol' => 'PROD001'
]);

$product = $response->json();
```

---

## 6. Laravel Service Class - Wzorzec

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubiektService
{
    protected $connection = 'subiekt';

    public function getProducts(array $filters = [])
    {
        $query = DB::connection($this->connection)
            ->table('tw__Towar as t')
            ->leftJoin('tw_Cena as c', function($join) {
                $join->on('t.tw_Id', '=', 'c.tc_TowId')
                     ->where('c.tc_RodzCenyId', '=', 1);
            })
            ->leftJoin('tw_Stan as s', function($join) {
                $join->on('t.tw_Id', '=', 's.st_TowId')
                     ->where('s.st_MagId', '=', 1);
            })
            ->select([
                't.tw_Id as id',
                't.tw_Symbol as symbol',
                't.tw_Nazwa as nazwa',
                'c.tc_CenaNetto as cena_netto',
                'c.tc_CenaBrutto as cena_brutto',
                DB::raw('ISNULL(s.st_Stan, 0) as stan')
            ])
            ->where('t.tw_Aktywny', 1);

        if (!empty($filters['symbol'])) {
            $query->where('t.tw_Symbol', 'LIKE', "%{$filters['symbol']}%");
        }

        return $query->get();
    }

    public function getProductBySymbol(string $symbol)
    {
        return DB::connection($this->connection)
            ->table('tw__Towar')
            ->where('tw_Symbol', $symbol)
            ->where('tw_Aktywny', 1)
            ->first();
    }

    public function getStock(string $symbol, int $magazynId = 1)
    {
        return DB::connection($this->connection)
            ->table('tw__Towar as t')
            ->join('tw_Stan as s', 't.tw_Id', '=', 's.st_TowId')
            ->where('t.tw_Symbol', $symbol)
            ->where('s.st_MagId', $magazynId)
            ->value('s.st_Stan') ?? 0;
    }
}
```

---

## Ostrzezenia i Dobre Praktyki

### KRYTYCZNE

1. **NIGDY nie modyfikuj danych przez SQL INSERT/UPDATE bez Sfera** - mozesz uszkodzic integralnosc bazy
2. **Uzywaj spIdentyfikator do generowania ID** - nie MAX(id)+1
3. **Zawsze testuj na kopii bazy** - nigdy na produkcji bez backup
4. **Sprawdzaj wersje Subiekt** - struktura moze sie roznic miedzy wersjami

### ZALECANE

1. **Tylko ODCZYTY przez SQL** - zapisy przez Sfera lub REST API
2. **Cache wynikow** - baza Subiekt moze byc wolna przy duzych zapytaniach
3. **Loguj wszystkie operacje** - dla debugowania i audytu
4. **Uzywaj transakcji** - przy wielu operacjach

### BEZPIECZENSTWO

1. **Nie uzywaj konta SA na produkcji** - utworz dedykowane konto z ograniczonymi uprawnieniami
2. **Szyfruj polaczenia** - uzywaj TLS gdzie mozliwe
3. **Nie przechowuj hasel w kodzie** - uzywaj .env

---

## Zasoby Dodatkowe

- `SQL_EXAMPLES.md` - Rozbudowane przyklady zapytan SQL
- `resources/table-schema.md` - Pelna struktura tabel
- `resources/sfera-api.md` - Dokumentacja Sfera API

## Zrodla Zewnetrzne

- [InsERT - Struktura bazy danych](https://www.insert.com.pl/dla_uzytkownikow/e-pomoc_techniczna/7877,gdzie-znalezc-strukture-bazy-danych-programow-serii-insert-gt.html)
- [GitHub - asocial-media/subiekt-sfera](https://github.com/asocial-media/subiekt-sfera)
- [GitHub - Lukegpl/api-subiekt-gt](https://github.com/Lukegpl/api-subiekt-gt)
- [Sellintegro - Czym jest Sfera](https://www.sellintegro.pl/wiki/czym-jest-sfera-dla-subiekta-gt)

---

## System Uczenia Sie (Automatyczny)

### Tracking Informacji
Ten skill automatycznie zbiera:
- Czas wykonania kazdego kroku
- Status sukces/porazka
- Napotkane bledy
- Feedback uzytkownika

### Metryki Sukcesu
- Success rate target: 95%
- Max execution time: 60s
- User satisfaction target: 4.5/5

### Historia Ulepszen
#### v1.0.0 (2026-01-19)
- [INIT] Poczatkowa wersja skilla
- [FEATURE] Dokumentacja trzech metod integracji (SQL/Sfera/REST)
- [FEATURE] Quick reference struktury bazy danych
- [FEATURE] Przyklady zapytan SQL
- [FEATURE] Wzorzec Laravel Service Class
- [DOCS] Ostrzezenia i dobre praktyki
