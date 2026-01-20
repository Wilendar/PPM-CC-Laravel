# Subiekt GT REST API (.NET 8)

Lekkie REST API dla bazy danych Subiekt GT. Natywne dla Windows, bez dodatkowych instalacji.

## Wymagania

- .NET 8.0 SDK (juz zainstalowany!)
- Dostep do SQL Server z baza Subiekt GT

## Szybki Start

### 1. Skopiuj pliki na serwer EXEA

Skopiuj caly folder `SubiektGT_REST_API_DotNet` do np. `C:\SubiektApi\`

### 2. Skonfiguruj polaczenie

Edytuj `appsettings.json`:

```json
{
  "ConnectionStrings": {
    "SubiektGT": "Server=10.9.20.100;Database=NAZWA_TWOJEJ_BAZY;User Id=sa;Password=TWOJE_HASLO;TrustServerCertificate=True;"
  },
  "ApiKeys": [
    "ppm_subiekt_api_key_change_in_production_64characters_minimum!"
  ]
}
```

### 3. Zbuduj i uruchom

```powershell
cd C:\SubiektApi

# Przywroc pakiety i zbuduj
dotnet restore
dotnet build --configuration Release

# Uruchom na porcie 8081
dotnet run --urls "http://localhost:8081"
```

### 4. Testuj

```powershell
# Test bez klucza (powinien zwrocic 401)
Invoke-RestMethod -Uri "http://localhost:8081/api/health"

# Test z kluczem
$headers = @{ "X-API-Key" = "ppm_subiekt_api_key_change_in_production_64characters_minimum!" }
Invoke-RestMethod -Uri "http://localhost:8081/api/health" -Headers $headers
```

## Uruchomienie jako Windows Service

### Opcja A: Kestrel bezposrednio

```powershell
# Publikuj jako self-contained
dotnet publish -c Release -o C:\SubiektApi\publish

# Zainstaluj jako usluge
sc.exe create "SubiektApi" binPath="C:\SubiektApi\publish\SubiektApi.exe --urls http://*:8081"
sc.exe config "SubiektApi" start=auto
sc.exe start "SubiektApi"
```

### Opcja B: Za IIS (reverse proxy)

1. Zainstaluj modul ASP.NET Core Hosting Bundle:
   https://dotnet.microsoft.com/download/dotnet/8.0

2. Utworz aplikacje w IIS wskazujaca na folder `publish`

## Endpointy API

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/api/health` | GET | Test polaczenia + statystyki |
| `/api/products` | GET | Lista produktow (paginacja) |
| `/api/products/{id}` | GET | Produkt po ID |
| `/api/products/sku/{sku}` | GET | Produkt po SKU |
| `/api/stock` | GET | Stany magazynowe |
| `/api/stock/{id}` | GET | Stany produktu |
| `/api/warehouses` | GET | Lista magazynow |
| `/api/price-types` | GET | Rodzaje cen |
| `/api/vat-rates` | GET | Stawki VAT |

### Parametry dla `/api/products`

- `page` - Numer strony (domyslnie: 1)
- `pageSize` - Ilosc na strone (domyslnie: 100)
- `priceTypeId` - ID rodzaju ceny (domyslnie: 1)
- `warehouseId` - ID magazynu (domyslnie: 1)
- `sku` - Filtr po SKU (LIKE)
- `name` - Filtr po nazwie (LIKE)
- `modifiedSince` - Data minimalnej modyfikacji (ISO 8601)

## Autentykacja

Wszystkie requesty musza zawierac naglowek:

```
X-API-Key: twoj_klucz_api
```

## Przyklad odpowiedzi

### /api/health
```json
{
  "success": true,
  "timestamp": "2026-01-20T14:30:00.000+01:00",
  "status": "ok",
  "database": "MojaFirma",
  "server_version": "16.00.1000",
  "products_count": 1500,
  "response_time_ms": 15.5
}
```

### /api/products
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "PROD001",
      "name": "Produkt testowy",
      "priceNet": 100.00,
      "priceGross": 123.00,
      "stock": 50,
      "vatRate": 23
    }
  ],
  "pagination": {
    "current_page": 1,
    "page_size": 100,
    "total_items": 1500,
    "total_pages": 15,
    "has_next": true
  }
}
```

## Troubleshooting

### Blad polaczenia z baza
1. Sprawdz connection string w `appsettings.json`
2. Sprawdz czy SQL Server akceptuje polaczenia TCP/IP
3. Sprawdz firewall (port 1433)

### Blad 401 Unauthorized
1. Sprawdz czy naglowek `X-API-Key` jest wysylany
2. Sprawdz czy klucz jest w tablicy `ApiKeys` w `appsettings.json`

---

**Wersja:** 1.0.0
**Technologia:** .NET 8, Minimal API, Dapper
