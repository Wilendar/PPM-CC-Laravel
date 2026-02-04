# Subiekt GT REST API Wrapper

Lekki REST API wrapper dla bazy danych Subiekt GT. Przeznaczony do uruchomienia na serwerze Windows z dostepem do SQL Server.

## Wymagania

### Serwer EXEA (Windows)
- Windows Server 2016+ lub Windows 10+
- PHP 8.1+ z rozszerzeniami:
  - `pdo_sqlsrv` (Microsoft SQL Server Driver for PHP)
  - `sqlsrv`
  - `json`
- IIS lub Apache z mod_rewrite
- Dostep do bazy danych Subiekt GT (SQL Server)
- Certyfikat SSL (zalecany Let's Encrypt)

### Klient (PPM na Hostido)
- PHP 8.x z `curl` i `json`
- Dostep HTTPS do serwera EXEA

## Instalacja na EXEA

### 1. Zainstaluj sterowniki SQL Server dla PHP

Pobierz z: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server

```powershell
# Pobierz i rozpakuj sterowniki
# Skopiuj pliki .dll do katalogu ext PHP
# np. C:\PHP\ext\php_pdo_sqlsrv_82_ts_x64.dll

# Edytuj php.ini i dodaj:
extension=pdo_sqlsrv
extension=sqlsrv
```

Zrestartuj IIS/Apache po zmianach.

### 2. Skopiuj pliki API

```powershell
# Utworz katalog dla API
mkdir C:\inetpub\wwwroot\subiekt-api

# Skopiuj pliki
copy config.example.php C:\inetpub\wwwroot\subiekt-api\config.php
copy SubiektRepository.php C:\inetpub\wwwroot\subiekt-api\
copy index.php C:\inetpub\wwwroot\subiekt-api\

# Utworz katalogi storage
mkdir C:\inetpub\wwwroot\subiekt-api\storage
mkdir C:\inetpub\wwwroot\subiekt-api\storage\logs
mkdir C:\inetpub\wwwroot\subiekt-api\storage\rate_limits
```

### 3. Skonfiguruj API

Edytuj `config.php`:

```php
return [
    'database' => [
        'host' => '(local)\INSERTGT',  // lub nazwa instancji SQL Server
        'port' => '1433',
        'database' => 'TWOJA_FIRMA',    // Nazwa bazy Subiekt GT
        'username' => 'sa',
        'password' => 'TWOJE_HASLO',    // <-- ZMIEN!
    ],

    'api' => [
        'keys' => [
            // Wygeneruj bezpieczny klucz: bin2hex(random_bytes(32))
            'TWOJ_BEZPIECZNY_KLUCZ_64_ZNAKI' => [
                'name' => 'PPM Production',
                'permissions' => ['read', 'write'],
                'ip_whitelist' => [
                    // IP serwera Hostido (opcjonalne)
                    // '123.456.789.xxx',
                ],
            ],
        ],
    ],
];
```

### 4. Konfiguracja IIS

#### Utworz nowa witryne lub aplikacje

1. Otworz IIS Manager
2. Kliknij prawym na "Sites" > "Add Website"
3. Ustawienia:
   - Site name: `SubiektAPI`
   - Physical path: `C:\inetpub\wwwroot\subiekt-api`
   - Binding: HTTPS, port 443, hostname: `api.twoja-domena.pl`
   - SSL Certificate: Wybierz certyfikat

#### Wlacz URL Rewrite

Zainstaluj modul URL Rewrite: https://www.iis.net/downloads/microsoft/url-rewrite

Utworz plik `web.config` w katalogu API:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="API Routing" stopProcessing="true">
                    <match url="^(.*)$" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        <handlers>
            <add name="PHP" path="*.php" verb="*" modules="FastCgiModule"
                 scriptProcessor="C:\PHP\php-cgi.exe"
                 resourceType="Either" requireAccess="Script" />
        </handlers>
        <defaultDocument>
            <files>
                <add value="index.php" />
            </files>
        </defaultDocument>
    </system.webServer>
</configuration>
```

### 5. Uprawnienia

```powershell
# Nadaj uprawnienia IIS do katalogu storage
icacls "C:\inetpub\wwwroot\subiekt-api\storage" /grant "IIS_IUSRS:(OI)(CI)F"
icacls "C:\inetpub\wwwroot\subiekt-api\storage" /grant "IUSR:(OI)(CI)F"
```

### 6. Konfiguracja Firewall

```powershell
# Otworz port 443 dla HTTPS
netsh advfirewall firewall add rule name="SubiektAPI HTTPS" dir=in action=allow protocol=TCP localport=443

# Opcjonalnie: Ogranicz dostep do konkretnych IP
netsh advfirewall firewall add rule name="SubiektAPI HTTPS" dir=in action=allow protocol=TCP localport=443 remoteip=123.456.789.xxx
```

### 7. Testowanie

```powershell
# Test lokalny
curl -H "X-API-Key: TWOJ_KLUCZ" http://localhost/api/health

# Test zdalny (z VPN)
curl -H "X-API-Key: TWOJ_KLUCZ" https://api.twoja-domena.pl/api/health
```

Oczekiwana odpowiedz:
```json
{
    "success": true,
    "timestamp": "2026-01-20T12:00:00+01:00",
    "status": "ok",
    "database": "TWOJA_FIRMA",
    "server_version": "Microsoft SQL Server 2019...",
    "response_time_ms": 15.5
}
```

## Endpointy API

### Health & Stats

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/health` | GET | Test polaczenia z baza |
| `/api/stats` | GET | Statystyki bazy danych |

### Produkty

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/products` | GET | Lista produktow (z paginacja) |
| `/api/products/{id}` | GET | Produkt po ID |
| `/api/products/sku/{sku}` | GET | Produkt po SKU |

**Parametry dla `/api/products`:**
- `page` - Numer strony (domyslnie: 1)
- `page_size` - Ilosc na strone (domyslnie: 100, max: 500)
- `price_type_id` - ID rodzaju ceny (domyslnie: 1)
- `warehouse_id` - ID magazynu (domyslnie: 1)
- `sku` - Filtr po SKU (LIKE)
- `name` - Filtr po nazwie (LIKE)
- `ean` - Filtr po kodzie EAN
- `modified_since` - Produkty zmienione po dacie (format: Y-m-d H:i:s)
- `active_only` - Tylko aktywne (domyslnie: 1)

### Stany magazynowe

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/stock` | GET | Wszystkie stany (z paginacja) |
| `/api/stock/{id}` | GET | Stany produktu po ID |
| `/api/stock/sku/{sku}` | GET | Stany produktu po SKU |

### Ceny

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/prices/{id}` | GET | Ceny produktu po ID |
| `/api/prices/sku/{sku}` | GET | Ceny produktu po SKU |

### Dane referencyjne

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/warehouses` | GET | Lista magazynow |
| `/api/price-types` | GET | Rodzaje cen |
| `/api/vat-rates` | GET | Stawki VAT |
| `/api/manufacturers` | GET | Producenci |
| `/api/product-groups` | GET | Grupy towarowe |
| `/api/units` | GET | Jednostki miary |

## Autentykacja

Wszystkie requesty musza zawierac naglowek:

```
X-API-Key: TWOJ_KLUCZ_API
```

Przyklad:
```bash
curl -H "X-API-Key: abc123..." https://api.example.com/api/products
```

## Rate Limiting

Domyslnie: 60 requestow na minute per API key.

Naglowki odpowiedzi:
- `X-RateLimit-Limit: 60`
- `X-RateLimit-Remaining: 59`

Po przekroczeniu limitu: HTTP 429 Too Many Requests

## Formaty odpowiedzi

### Sukces

```json
{
    "success": true,
    "timestamp": "2026-01-20T12:00:00+01:00",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "page_size": 100,
        "total_items": 1500,
        "total_pages": 15,
        "has_next": true,
        "has_previous": false
    }
}
```

### Blad

```json
{
    "success": false,
    "error": {
        "code": 404,
        "message": "Product not found"
    },
    "timestamp": "2026-01-20T12:00:00+01:00"
}
```

## Bezpieczenstwo

### Zalecenia

1. **Uzywaj HTTPS** - Nigdy nie wystawiaj API po HTTP
2. **Zmien domyslne klucze API** - Wygeneruj bezpieczne klucze
3. **Uzyj IP whitelist** - Ogranicz dostep do znanych IP
4. **Monitoruj logi** - Sprawdzaj `/storage/logs/` regularnie
5. **Aktualizuj PHP** - Uzywaj najnowszej wersji PHP
6. **Backup konfiguracji** - Nie commituj `config.php` do repo

### Generowanie bezpiecznego klucza API

```php
<?php
echo bin2hex(random_bytes(32));
// Wynik: 64-znakowy hex string
```

## Troubleshooting

### Blad polaczenia z baza

1. Sprawdz czy SQL Server jest uruchomiony
2. Sprawdz dane polaczenia w `config.php`
3. Sprawdz czy uzytkownik ma uprawnienia do bazy
4. Sprawdz logi w `/storage/logs/`

### Blad 500 Internal Server Error

1. Sprawdz logi PHP: `C:\PHP\logs\php_errors.log`
2. Sprawdz logi IIS: Event Viewer > Windows Logs > Application
3. Wlacz `display_errors` w php.ini (tylko do debugowania!)

### Blad 401 Unauthorized

1. Sprawdz czy naglowek `X-API-Key` jest wysylany
2. Sprawdz czy klucz istnieje w `config.php`
3. Sprawdz IP whitelist

### Blad 429 Too Many Requests

1. Poczekaj 60 sekund
2. Zwieksz limit w konfiguracji
3. Rozloz requesty w czasie

## Integracja z PPM

Po uruchomieniu API, skonfiguruj polaczenie w PPM:

1. Przejdz do: Admin > Integracje > Subiekt GT
2. Wybierz tryb polaczenia: "REST API"
3. Wprowadz:
   - URL API: `https://api.twoja-domena.pl`
   - Klucz API: `TWOJ_KLUCZ`
4. Kliknij "Test polaczenia"

## Wsparcie

W razie problemow:
- Sprawdz logi w `/storage/logs/`
- Skontaktuj sie z administratorem serwera EXEA

---

**Wersja:** 1.0.0
**Autor:** PPM Development Team
**Licencja:** Proprietary
