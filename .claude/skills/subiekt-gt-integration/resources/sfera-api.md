# Subiekt GT - Sfera API Dokumentacja

## Spis Tresci
1. [Wprowadzenie](#wprowadzenie)
2. [Inicjalizacja](#inicjalizacja)
3. [TowaryManager](#towarymanager)
4. [KontrahenciManager](#kontrahencimanager)
5. [DokumentyManager](#dokumentymanager)
6. [MagazynyManager](#magazynymanager)
7. [Obsluga Bledow](#obsluga-bledow)

---

## Wprowadzenie

### Czym jest Sfera?

**Sfera** to oficjalne API obiektowe dla programow InsERT GT (Subiekt GT, Rachmistrz GT, Rewizor GT). Umozliwia bezpieczna integracje z systemem poprzez obiekty COM/OLE.

### Wymagania

- Windows (Sfera dziala tylko na Windows)
- Zainstalowany Subiekt GT
- Licencja Sfera GT (osobno od licencji Subiekt)
- PHP z rozszerzeniem `com_dotnet`

### Wlaczenie rozszerzenia COM w PHP

```ini
; php.ini
extension=com_dotnet
```

### Zalety Sfera vs SQL

| Aspekt | Sfera | Bezposredni SQL |
|--------|-------|-----------------|
| Bezpieczenstwo | Walidacja danych | Brak walidacji |
| Integralnosc | Automatyczna | Reczna |
| Wsparcie | Oficjalne | Brak |
| Wydajnosc | Wolniejsze | Szybsze |
| Zapis danych | Bezpieczny | Ryzykowny |

**Zasada**: SQL do odczytow, Sfera do zapisow.

---

## Inicjalizacja

### Podstawowe polaczenie

```php
<?php

class SubiektSfera
{
    private $gt;
    private $subiekt;

    public function __construct()
    {
        try {
            // Utworzenie obiektu Insert.GT
            $this->gt = new COM('Insert.gt');

            // Konfiguracja polaczenia
            $this->gt->Produkt = 1;  // 1 = Subiekt GT
            $this->gt->Serwer = '(local)\INSERTGT';
            $this->gt->Baza = 'NazwaFirmy';

            // Autentykacja
            $this->gt->Autentykacja = 0;  // 0=Windows, 1=SQL
            $this->gt->Uzytkownik = 'sa';
            $this->gt->UzytkownikHaslo = '';

            // Uruchomienie w tle
            // Parametry: (okno, tryb)
            // okno: 0=ukryte, 1=widoczne
            // tryb: 0=normalne, 4=w tle
            $this->subiekt = $this->gt->Uruchom(0, 4);

        } catch (com_exception $e) {
            throw new Exception('Blad polaczenia z Sfera: ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        // Zamkniecie polaczenia
        if ($this->subiekt) {
            $this->subiekt->Zakoncz();
        }
    }

    public function getSubiekt()
    {
        return $this->subiekt;
    }
}
```

### Tryby uruchomienia

```php
// Tryb normalny (widoczne okno)
$subiekt = $gt->Uruchom(1, 0);

// Tryb w tle (ukryte okno) - ZALECANE dla integracji
$subiekt = $gt->Uruchom(0, 4);

// Tryb tylko odczyt
$subiekt = $gt->Uruchom(0, 8);
```

---

## TowaryManager

### Pobranie managera

```php
$towary = $subiekt->TowaryManager;
```

### Wczytanie produktu

```php
// Po symbolu
$towar = $towary->Wczytaj('SYMBOL123');

// Po ID
$towar = $towary->WczytajPoId(12345);

// Po EAN
$towar = $towary->WczytajPoEan('5901234567890');

if ($towar) {
    echo "Nazwa: " . $towar->Nazwa;
    echo "Symbol: " . $towar->Symbol;
    echo "Cena detal: " . $towar->CenaDetaliczna;
    echo "Stan: " . $towar->Stan;
    echo "Jednostka: " . $towar->JednostkaMiary;
}
```

### Wlasciwosci obiektu Towar

| Wlasciwosc | Typ | Opis |
|------------|-----|------|
| Id | int | ID produktu |
| Symbol | string | Symbol produktu |
| Nazwa | string | Nazwa |
| NazwaPelna | string | Pelna nazwa |
| Opis | string | Opis |
| JednostkaMiary | string | JM |
| EAN | string | Kod EAN |
| PKWiU | string | Kod PKWiU |
| StawkaVat | decimal | Stawka VAT |
| CenaDetaliczna | decimal | Cena detaliczna brutto |
| CenaHurtowa | decimal | Cena hurtowa netto |
| Stan | decimal | Stan na magazynie |
| StanMinimalny | decimal | Stan minimalny |
| StanMaksymalny | decimal | Stan maksymalny |
| Aktywny | bool | Czy aktywny |

### Dodanie nowego produktu

```php
$nowyTowar = $towary->Dodaj();

$nowyTowar->Symbol = 'NOWY001';
$nowyTowar->Nazwa = 'Nowy produkt';
$nowyTowar->JednostkaMiary = 'szt';
$nowyTowar->StawkaVat = 23;
$nowyTowar->CenaDetaliczna = 123.45;

// Przypisanie do grupy
$nowyTowar->GrupaId = 5;

// Zapisanie
try {
    $nowyTowar->Zapisz();
    echo "Utworzono produkt ID: " . $nowyTowar->Id;
} catch (com_exception $e) {
    echo "Blad: " . $e->getMessage();
}
```

### Aktualizacja produktu

```php
$towar = $towary->Wczytaj('SYMBOL123');

if ($towar) {
    $towar->Nazwa = 'Zaktualizowana nazwa';
    $towar->CenaDetaliczna = 150.00;

    try {
        $towar->Zapisz();
        echo "Zaktualizowano produkt";
    } catch (com_exception $e) {
        echo "Blad: " . $e->getMessage();
    }
}
```

### Wyszukiwanie produktow

```php
// Pobranie listy wszystkich aktywnych
$lista = $towary->WczytajListe();

foreach ($lista as $towar) {
    echo $towar->Symbol . " - " . $towar->Nazwa;
}

// Filtrowanie
$filtr = $towary->UtworzFiltr();
$filtr->Aktywny = true;
$filtr->GrupaId = 5;
$filtr->NazwaZawiera = 'klawiatura';

$lista = $towary->WczytajListe($filtr);
```

---

## KontrahenciManager

### Pobranie managera

```php
$kontrahenci = $subiekt->KontrahenciManager;
```

### Wczytanie kontrahenta

```php
// Po symbolu
$kh = $kontrahenci->Wczytaj('KH001');

// Po NIP
$kh = $kontrahenci->WczytajPoNip('1234567890');

// Po ID
$kh = $kontrahenci->WczytajPoId(100);

if ($kh) {
    echo "Nazwa: " . $kh->Nazwa;
    echo "NIP: " . $kh->Nip;
    echo "Email: " . $kh->Email;
}
```

### Wlasciwosci obiektu Kontrahent

| Wlasciwosc | Typ | Opis |
|------------|-----|------|
| Id | int | ID kontrahenta |
| Symbol | string | Symbol |
| Nazwa | string | Nazwa skrocona |
| NazwaPelna | string | Pelna nazwa |
| Nip | string | NIP |
| Regon | string | REGON |
| Email | string | Email |
| Telefon | string | Telefon |
| Fax | string | Fax |
| WWW | string | Strona WWW |
| NrKonta | string | Numer konta |
| Aktywny | bool | Czy aktywny |
| Adresy | collection | Kolekcja adresow |

### Dodanie kontrahenta

```php
$nowyKh = $kontrahenci->Dodaj();

$nowyKh->Symbol = 'KH999';
$nowyKh->Nazwa = 'Nowa Firma Sp. z o.o.';
$nowyKh->NazwaPelna = 'Nowa Firma Spolka z ograniczona odpowiedzialnoscia';
$nowyKh->Nip = '1234567890';
$nowyKh->Email = 'kontakt@firma.pl';
$nowyKh->Telefon = '123456789';

// Dodanie adresu
$adres = $nowyKh->Adresy->Dodaj();
$adres->TypAdresuId = 1;  // Adres glowny
$adres->Ulica = 'Przykladowa';
$adres->NrDomu = '10';
$adres->Miasto = 'Warszawa';
$adres->KodPocztowy = '00-001';
$adres->Glowny = true;

try {
    $nowyKh->Zapisz();
    echo "Utworzono kontrahenta ID: " . $nowyKh->Id;
} catch (com_exception $e) {
    echo "Blad: " . $e->getMessage();
}
```

### Aktualizacja kontrahenta

```php
$kh = $kontrahenci->Wczytaj('KH001');

if ($kh) {
    $kh->Email = 'nowy@email.pl';
    $kh->Telefon = '987654321';

    try {
        $kh->Zapisz();
    } catch (com_exception $e) {
        echo "Blad: " . $e->getMessage();
    }
}
```

---

## DokumentyManager

### Pobranie managera

```php
$dokumenty = $subiekt->DokumentyManager;
```

### Typy dokumentow

| Metoda | Opis |
|--------|------|
| DodajFaktureSprzedazy() | Faktura VAT |
| DodajFaktureKorygujaca() | Korekta faktury |
| DodajParagon() | Paragon fiskalny |
| DodajZamowienieOdKlienta() | Zamowienie od klienta |
| DodajZamowienieDoDostawcy() | Zamowienie do dostawcy |
| DodajPZ() | Przyjecie zewnetrzne |
| DodajWZ() | Wydanie zewnetrzne |

### Utworzenie faktury sprzedazy

```php
// Utworzenie nowej faktury
$faktura = $dokumenty->DodajFaktureSprzedazy();

// Ustawienie kontrahenta
$kh = $kontrahenci->Wczytaj('KH001');
$faktura->Kontrahent = $kh;
// lub przez ID:
// $faktura->KontrahentId = 100;

// Daty
$faktura->DataWystawienia = date('Y-m-d');
$faktura->DataSprzedazy = date('Y-m-d');

// Dodanie pozycji
$pozycja1 = $faktura->Pozycje->Dodaj();
$pozycja1->TowarSymbol = 'PROD001';  // lub TowarId
$pozycja1->Ilosc = 2;
$pozycja1->CenaNetto = 100.00;
$pozycja1->Rabat = 5;  // 5% rabatu

$pozycja2 = $faktura->Pozycje->Dodaj();
$pozycja2->TowarSymbol = 'PROD002';
$pozycja2->Ilosc = 1;
$pozycja2->CenaBrutto = 246.00;

// Ustawienie platnosci
$faktura->FormaPlatnosciId = 1;  // Gotowka
$faktura->TerminPlatnosci = date('Y-m-d', strtotime('+14 days'));

// Uwagi
$faktura->Uwagi = 'Zamowienie online #12345';

// Zapisanie
try {
    $faktura->Zapisz();
    echo "Utworzono fakture: " . $faktura->NumerPelny;
    echo "ID: " . $faktura->Id;
} catch (com_exception $e) {
    echo "Blad: " . $e->getMessage();
}
```

### Wlasciwosci pozycji dokumentu

| Wlasciwosc | Typ | Opis |
|------------|-----|------|
| TowarId | int | ID produktu |
| TowarSymbol | string | Symbol produktu |
| Nazwa | string | Nazwa (nadpisanie) |
| Ilosc | decimal | Ilosc |
| JednostkaMiary | string | JM |
| CenaNetto | decimal | Cena netto |
| CenaBrutto | decimal | Cena brutto |
| Rabat | decimal | Rabat % |
| StawkaVat | decimal | Stawka VAT |

### Utworzenie zamowienia

```php
$zamowienie = $dokumenty->DodajZamowienieOdKlienta();

$zamowienie->KontrahentId = 100;
$zamowienie->DataWystawienia = date('Y-m-d');
$zamowienie->TerminRealizacji = date('Y-m-d', strtotime('+7 days'));
$zamowienie->Uwagi = 'Zamowienie z e-commerce';

// Pozycje
$poz = $zamowienie->Pozycje->Dodaj();
$poz->TowarSymbol = 'PROD001';
$poz->Ilosc = 5;
$poz->CenaNetto = 100.00;

$zamowienie->Zapisz();
echo "Zamowienie: " . $zamowienie->NumerPelny;
```

### Konwersja zamowienia na fakture

```php
// Wczytanie zamowienia
$zamowienie = $dokumenty->WczytajDokument($zamowienieId);

// Konwersja
$faktura = $dokumenty->KonwertujNaFakture($zamowienie);

// Modyfikacja jesli potrzeba
$faktura->DataWystawienia = date('Y-m-d');

$faktura->Zapisz();
```

### Wczytanie dokumentu

```php
// Po ID
$dokument = $dokumenty->WczytajDokument(12345);

// Po numerze
$dokument = $dokumenty->WczytajPoNumerze('FV/001/2026');

if ($dokument) {
    echo "Numer: " . $dokument->NumerPelny;
    echo "Wartosc: " . $dokument->WartoscBrutto;
    echo "Kontrahent: " . $dokument->Kontrahent->Nazwa;

    // Pozycje
    foreach ($dokument->Pozycje as $poz) {
        echo $poz->Nazwa . " x " . $poz->Ilosc;
    }
}
```

---

## MagazynyManager

### Pobranie managera

```php
$magazyny = $subiekt->MagazynyManager;
```

### Lista magazynow

```php
$listaMag = $magazyny->WczytajListe();

foreach ($listaMag as $mag) {
    echo $mag->Symbol . " - " . $mag->Nazwa;
}
```

### Pobranie stanu

```php
// Stan produktu na magazynie
$stan = $magazyny->PobierzStan('PROD001', 1);  // symbol, magazynId
echo "Stan: " . $stan->Ilosc;
echo "Zarezerwowany: " . $stan->IloscZarezerwowana;
echo "Dostepny: " . $stan->IloscDostepna;
```

### Operacje magazynowe

```php
// Przyjecie wewnetrzne
$pw = $dokumenty->DodajPW();
$pw->MagazynId = 1;
$pw->DataWystawienia = date('Y-m-d');
$pw->Uwagi = 'Przyjecie towaru';

$poz = $pw->Pozycje->Dodaj();
$poz->TowarSymbol = 'PROD001';
$poz->Ilosc = 100;
$poz->CenaNetto = 50.00;

$pw->Zapisz();
```

---

## Obsluga Bledow

### Typowe wyjatki

```php
try {
    $towar = $towary->Wczytaj('NIEISTNIEJACY');
    $towar->Zapisz();

} catch (com_exception $e) {
    $kod = $e->getCode();
    $msg = $e->getMessage();

    switch ($kod) {
        case 0x80004005:  // E_FAIL
            echo "Blad ogolny: " . $msg;
            break;

        case 0x80070057:  // E_INVALIDARG
            echo "Nieprawidlowy argument";
            break;

        case 0x8007000E:  // E_OUTOFMEMORY
            echo "Brak pamieci";
            break;

        default:
            echo "Blad COM: " . $msg . " (kod: " . dechex($kod) . ")";
    }
}
```

### Walidacja przed zapisem

```php
function zapisTowar($towary, $dane) {
    // Walidacja
    if (empty($dane['symbol'])) {
        throw new InvalidArgumentException('Symbol jest wymagany');
    }

    if (strlen($dane['symbol']) > 40) {
        throw new InvalidArgumentException('Symbol max 40 znakow');
    }

    // Sprawdzenie czy juz istnieje
    $istniejacy = $towary->Wczytaj($dane['symbol']);
    if ($istniejacy) {
        throw new Exception('Produkt o tym symbolu juz istnieje');
    }

    // Utworzenie
    $towar = $towary->Dodaj();
    $towar->Symbol = $dane['symbol'];
    $towar->Nazwa = $dane['nazwa'];
    // ...

    try {
        $towar->Zapisz();
        return $towar->Id;
    } catch (com_exception $e) {
        throw new Exception('Blad zapisu: ' . $e->getMessage());
    }
}
```

### Logowanie operacji

```php
class SubiektLogger
{
    public static function log($operacja, $obiekt, $wynik, $blad = null)
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operacja' => $operacja,
            'obiekt' => $obiekt,
            'wynik' => $wynik,
            'blad' => $blad
        ];

        // Zapis do pliku lub bazy
        file_put_contents(
            'subiekt_operations.log',
            json_encode($log) . "\n",
            FILE_APPEND
        );
    }
}

// Uzycie
try {
    $towar->Zapisz();
    SubiektLogger::log('utworzenie_produktu', $towar->Symbol, 'sukces');
} catch (com_exception $e) {
    SubiektLogger::log('utworzenie_produktu', $towar->Symbol, 'blad', $e->getMessage());
    throw $e;
}
```

---

## Przyklady Pelnej Integracji

### Laravel Service z Sfera

```php
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class SubiektSferaService
{
    private $gt;
    private $subiekt;
    private $connected = false;

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            $this->gt = new \COM('Insert.gt');
            $this->gt->Produkt = 1;
            $this->gt->Serwer = config('subiekt.server');
            $this->gt->Baza = config('subiekt.database');
            $this->gt->Autentykacja = config('subiekt.auth_type');
            $this->gt->Uzytkownik = config('subiekt.username');
            $this->gt->UzytkownikHaslo = config('subiekt.password');

            $this->subiekt = $this->gt->Uruchom(0, 4);
            $this->connected = true;

            Log::info('Polaczono z Sfera GT');

        } catch (\com_exception $e) {
            Log::error('Blad polaczenia Sfera: ' . $e->getMessage());
            throw new Exception('Nie mozna polaczyc z Subiekt GT');
        }
    }

    public function disconnect(): void
    {
        if ($this->connected && $this->subiekt) {
            $this->subiekt->Zakoncz();
            $this->connected = false;
            Log::info('Rozlaczono z Sfera GT');
        }
    }

    public function createInvoice(array $data): array
    {
        $this->connect();

        try {
            $dokumenty = $this->subiekt->DokumentyManager;
            $faktura = $dokumenty->DodajFaktureSprzedazy();

            // Kontrahent
            $faktura->KontrahentId = $data['kontrahent_id'];

            // Daty
            $faktura->DataWystawienia = $data['data_wystawienia'] ?? date('Y-m-d');
            $faktura->DataSprzedazy = $data['data_sprzedazy'] ?? date('Y-m-d');

            // Pozycje
            foreach ($data['pozycje'] as $poz) {
                $pozycja = $faktura->Pozycje->Dodaj();
                $pozycja->TowarSymbol = $poz['symbol'];
                $pozycja->Ilosc = $poz['ilosc'];
                $pozycja->CenaNetto = $poz['cena_netto'];

                if (isset($poz['rabat'])) {
                    $pozycja->Rabat = $poz['rabat'];
                }
            }

            // Platnosc
            $faktura->FormaPlatnosciId = $data['forma_platnosci_id'] ?? 1;

            // Uwagi
            if (isset($data['uwagi'])) {
                $faktura->Uwagi = $data['uwagi'];
            }

            $faktura->Zapisz();

            Log::info('Utworzono fakture: ' . $faktura->NumerPelny);

            return [
                'success' => true,
                'id' => $faktura->Id,
                'numer' => $faktura->NumerPelny,
                'wartosc_brutto' => $faktura->WartoscBrutto
            ];

        } catch (\com_exception $e) {
            Log::error('Blad tworzenia faktury: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
```

---

## Zrodla

- [InsERT - Sfera GT](https://www.insert.com.pl/programy_dla_firm/sprzedaz/subiekt_gt_sfera/opis.html)
- [GitHub - asocial-media/subiekt-sfera](https://github.com/asocial-media/subiekt-sfera)
- [Sellintegro - Czym jest Sfera](https://www.sellintegro.pl/wiki/czym-jest-sfera-dla-subiekta-gt)
