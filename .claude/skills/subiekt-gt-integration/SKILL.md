---
name: subiekt-gt-integration
description: Integracja z ERP Subiekt GT (InsERT) - SQL Server, Sfera API, REST wrapper. Uzyj przy synchronizacji produktow, kontrahentow, zamowien z systemem ERP.
version: 1.2.0
author: Kamil Wilinski
created: 2026-01-19
updated: 2026-01-23
tags: [subiekt, insert, erp, sql-server, sfera, integracja, magazyn, ceny, sync]
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

tw_Cena         - Ceny produktow (11 poziomow cenowych!)
  tc_TowId      - ID produktu (FK)
  tc_CenaNetto0..10 - Ceny netto poziom 0-10 (DECIMAL)
  tc_CenaBrutto0..10 - Ceny brutto poziom 0-10 (DECIMAL)

tw_Parametr     - NAZWY POZIOMOW CENOWYCH (KRYTYCZNE!)
  twp_Id        - ID (zawsze 1, jeden wiersz)
  twp_NazwaCeny1..10 - Nazwy poziomow cen
```

#### MAPOWANIE POZIOMOW CENOWYCH (tw_Parametr) - KRYTYCZNE!

⚠️ **POZIOM 0 (tc_CenaNetto0) JEST NIEUZYWANY** przy konfiguracji z grupami cenowymi!

| Kolumna tw_Parametr | Level API | Kolumna tw_Cena | Przyklad nazwy |
|--------------------|-----------|-----------------|----------------|
| - | 0 | tc_CenaNetto0 | **(Nieuzywany)** |
| twp_NazwaCeny1 | 1 | tc_CenaNetto1 | Detaliczna |
| twp_NazwaCeny2 | 2 | tc_CenaNetto2 | MRF-MPP |
| twp_NazwaCeny3 | 3 | tc_CenaNetto3 | Szkółka-Komis-Drop |
| twp_NazwaCeny4 | 4 | tc_CenaNetto4 | z magazynu |
| twp_NazwaCeny5 | 5 | tc_CenaNetto5 | Warsztat |
| twp_NazwaCeny6 | 6 | tc_CenaNetto6 | Standard |
| twp_NazwaCeny7 | 7 | tc_CenaNetto7 | Premium |
| twp_NazwaCeny8 | 8 | tc_CenaNetto8 | HuHa |
| twp_NazwaCeny9 | 9 | tc_CenaNetto9 | Warsztat Premium |
| twp_NazwaCeny10 | 10 | tc_CenaNetto10 | Pracownik |

**PRAWIDLOWE MAPOWANIE:** `twp_NazwaCeny[N]` → `tc_CenaNetto[N]` → API Level N

**KRYTYCZNE:**
- Poziom 0 zawsze pomijaj przy synchronizacji cen z PPM
- REST API `/api/price-levels` zwraca TYLKO poziomy 1-10 (bez 0)
- Przy PUT/UPDATE cen wysylaj klucze 1-10, NIE 0-9

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

## 5. REST API Wrapper - MPP TRADE (sapi.mpptrade.pl)

### Konfiguracja dla PPM-CC-Laravel

| Parametr | Wartosc |
|----------|---------|
| Base URL | `https://sapi.mpptrade.pl` |
| Auth Header | `X-API-Key` |
| SSL Verify | `false` (self-signed certificate!) |
| Timeout | 30s |
| Retry | 3 attempts |

### Dostepne Endpointy

#### Odczyty (GET)
| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/health` | GET | Connection test + DB stats |
| `/api/stats` | GET | Database statistics |
| `/api/products` | GET | Lista produktow (paginated) |
| `/api/products/{id}` | GET | Produkt po ID |
| `/api/products/sku/{sku}` | GET | Produkt po SKU |
| `/api/stock` | GET | Stany magazynowe |
| `/api/stock/{productId}` | GET | Stan produktu |
| `/api/stock/sku/{sku}` | GET | Stan po SKU |
| `/api/prices/{productId}` | GET | Ceny produktu (wszystkie 11 poziomow) |
| `/api/prices/sku/{sku}` | GET | Ceny po SKU |
| `/api/warehouses` | GET | Lista magazynow |
| `/api/price-levels` | GET | Nazwy poziomow cenowych 1-10 (BEZ poziomu 0!) |
| `/api/vat-rates` | GET | Stawki VAT |
| `/api/manufacturers` | GET | Producenci |
| `/api/product-groups` | GET | Grupy produktow |
| `/api/units` | GET | Jednostki miary |

#### Zapisy (PUT) - DirectSQL
| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/products/sku/{sku}` | PUT | Aktualizacja produktu przez SKU |

**PUT Request Body:**
```json
{
  "Name": "Nazwa produktu",
  "Description": "Opis",
  "IsActive": true,
  "PricesNet": {"1": 100.00, "2": 99.19, "3": 180.49},
  "PricesGross": {"1": 123.00, "2": 122.00, "3": 222.00}
}
```

**PUT Response:**
```json
{
  "success": true,
  "data": {
    "product_id": 23228,
    "sku": "BL-22652-176692083",
    "action": "updated",
    "rows_affected": 3,
    "message": "Product updated successfully"
  }
}
```

⚠️ **WAZNE:** Klucze w PricesNet/PricesGross to poziomy 1-10, NIE 0-9!

### Query Parameters (/api/products)

| Parametr | Typ | Default | Opis |
|----------|-----|---------|------|
| `page` | int | 1 | Numer strony |
| `pageSize` | int | 100 | Produktow na strone (max 500) |
| `priceLevel` | int | 0 | Poziom ceny (0-9 = tc_CenaNetto0..9) |
| `warehouseId` | int | 1 | ID magazynu dla stanu |
| `sku` | string | - | Filtr po SKU (LIKE) |
| `name` | string | - | Filtr po nazwie (LIKE) |

### Response Format

```json
{
    "success": true,
    "timestamp": "2026-01-20T17:28:32.666Z",
    "data": [...],
    "pagination": {
        "page": 1,
        "page_size": 100,
        "total": 12717,
        "total_pages": 128,
        "has_next": true,
        "has_prev": false
    }
}
```

### Laravel Client (SubiektRestApiClient)

```php
use App\Services\ERP\SubiektGT\SubiektRestApiClient;

$client = new SubiektRestApiClient([
    'base_url' => 'https://sapi.mpptrade.pl',
    'api_key' => $config['rest_api_key'],
    'timeout' => 30,
    'verify_ssl' => false,  // CRITICAL: self-signed cert!
]);

// Pobranie produktow
$products = $client->getProducts(['page' => 1, 'pageSize' => 100]);

// Poziomy cenowe z prawdziwymi nazwami (z tw_Parametr)
// UWAGA: Poziom 0 jest NIEUZYWANY - zwracane sa tylko 1-10!
$priceLevels = $client->getPriceLevels();
// [
//   {id: 1, name: "Detaliczna"},      // tc_CenaNetto1
//   {id: 2, name: "MRF-MPP"},         // tc_CenaNetto2
//   {id: 3, name: "Szkółka-Komis-Drop"}, // tc_CenaNetto3
//   {id: 4, name: "z magazynu"},      // tc_CenaNetto4
//   {id: 5, name: "Warsztat"},        // tc_CenaNetto5
//   {id: 6, name: "Standard"},        // tc_CenaNetto6
//   {id: 7, name: "Premium"},         // tc_CenaNetto7
//   {id: 8, name: "HuHa"},            // tc_CenaNetto8
//   {id: 9, name: "Warsztat Premium"}, // tc_CenaNetto9
//   {id: 10, name: "Pracownik"}       // tc_CenaNetto10
// ]

// Aktualizacja produktu (PUT)
$result = $client->updateProductBySku('SKU-001', [
    'name' => 'Nowa nazwa',
    'prices' => [
        1 => ['net' => 100.00, 'gross' => 123.00],  // Poziom 1 (Detaliczna)
        2 => ['net' => 99.19, 'gross' => 122.00],   // Poziom 2 (MRF-MPP)
    ],
]);
```

### ERPConnection Config (PPM-CC-Laravel)

⚠️ **KRYTYCZNE dla synchronizacji PUSH (PPM → Subiekt):**

```php
// ERPConnection model - connection_config (encrypted JSON)
[
    'connection_mode' => 'rest_api',
    'rest_api_url' => 'https://sapi.mpptrade.pl',
    'rest_api_key' => 'YOUR_API_KEY',
    'rest_api_verify_ssl' => false,
    'sync_direction' => 'bidirectional',  // WYMAGANE dla PUSH!
    'default_price_type_id' => '1',       // Domyslny poziom ceny (1-10, NIE 0!)
    'warehouse_mappings' => [...],
    'price_group_mappings' => [...],
]
```

**sync_direction opcje:**
- `pull` - tylko pobieranie z Subiekt (domyslne)
- `push` - tylko wysylanie do Subiekt
- `bidirectional` - obie strony

**UWAGA:** Jesli sync_direction = 'pull', operacje PUSH beda pomijane!

### Kod zrodlowy REST API (.NET 8)

Lokalizacja: `_TOOLS/SubiektGT_REST_API_DotNet/`

Build & Deploy:
```powershell
cd "_TOOLS/SubiektGT_REST_API_DotNet"
dotnet publish -c Release -o ./publish
# Upload publish/ folder to sapi.mpptrade.pl (IIS Windows Server)
```

---

## 6. REST API Open Source (Lukegpl)

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

## 7. Laravel Service Class - Wzorzec

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

1. **DirectSQL UPDATE dla cen jest BEZPIECZNY** - tabela tw_Cena moze byc aktualizowana przez SQL
2. **DirectSQL INSERT wymaga Sfera** - tworzenie nowych produktow przez spIdentyfikator + INSERT moze nie dzialac bez licencji Sfera
3. **Uzywaj spIdentyfikator do generowania ID** - nie MAX(id)+1
4. **Zawsze testuj na kopii bazy** - nigdy na produkcji bez backup
5. **Sprawdzaj wersje Subiekt** - struktura moze sie roznic miedzy wersjami
6. **Poziom ceny 0 jest NIEUZYWANY** - przy konfiguracji z grupami cenowymi pomijaj tc_CenaNetto0

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

#### v1.2.0 (2026-01-23) - KRYTYCZNA KOREKTA MAPOWANIA CEN
- [BREAKING] **KOREKTA MAPOWANIA:** `twp_NazwaCeny[N]` → `tc_CenaNetto[N]` (NIE N-1!)
- [BREAKING] **Poziom 0 (tc_CenaNetto0) jest NIEUZYWANY** - zawsze pomijaj przy sync
- [FEATURE] Nowy endpoint `PUT /api/products/sku/{sku}` dla aktualizacji produktow
- [FEATURE] DirectSQL UPDATE dla cen - bezpieczne bez Sfera
- [FEATURE] Dokumentacja ERPConnection config z sync_direction
- [FIX] REST API `/api/price-levels` zwraca poziomy 1-10 (bez 0)
- [FIX] Ceny wysylane z kluczami 1-10 (nie 0-9)
- [DOCS] Przyklad PUT request/response
- [DOCS] Ostrzezenia o sync_direction = 'bidirectional' dla PUSH
- [VERIFIED] Synchronizacja cen PPM → Subiekt GT dziala poprawnie

#### v1.1.0 (2026-01-20)
- [FEATURE] Dodano dokumentacje REST API MPP TRADE (sapi.mpptrade.pl)
- [FEATURE] Odkryto tabele tw_Parametr z nazwami poziomow cenowych
- [FIX] REST API /api/price-levels zwraca prawdziwe nazwy z bazy
- [DOCS] Dodano wszystkie endpointy REST API
- [DOCS] Zaktualizowano strukture bazy o tw_Parametr

#### v1.0.0 (2026-01-19)
- [INIT] Poczatkowa wersja skilla
- [FEATURE] Dokumentacja trzech metod integracji (SQL/Sfera/REST)
- [FEATURE] Quick reference struktury bazy danych
- [FEATURE] Przyklady zapytan SQL
- [FEATURE] Wzorzec Laravel Service Class
- [DOCS] Ostrzezenia i dobre praktyki
