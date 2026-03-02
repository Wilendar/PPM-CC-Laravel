# PPM - PPM Manager Module Documentation

> **Wersja:** 2.0.0
> **Data:** 2026-02-13
> **Status:** Production Ready
> **Changelog:** Przebudowa dokumentacji do standardu projektu. Aktualizacja do wersji 2.0.0 (ppmmanager) z per-variant covers, hookami frontendowymi i class override.

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Endpoint API](#3-endpoint-api)
4. [Akcje API](#4-akcje-api)
5. [Hooki PrestaShop](#5-hooki-prestashop)
6. [Class Override](#6-class-override)
7. [Schema Bezpieczenstwa](#7-schema-bezpieczenstwa)
8. [Integracja z PPM Laravel](#8-integracja-z-ppm-laravel)
9. [Instalacja i Konfiguracja](#9-instalacja-i-konfiguracja)
10. [Troubleshooting](#10-troubleshooting)
11. [Changelog](#11-changelog)

---

## 1. Overview

### 1.1 Opis modulu

PPM Manager to **most API** miedzy systemem PPM (ppm.mpptrade.pl) a sklepami PrestaShop. Zapewnia endpointy REST do operacji **nieobslugiwanych** przez natywne PrestaShop Web Services API, takich jak zarzadzanie pozycjami obrazkow, per-wariantowe okladki (cover images) oraz health check polaczenia.

Modul jest **baza rozszerzalnosci** dla komunikacji PPM <> PrestaShop. Dziala jako front controller (`ModuleFrontController`) z autentykacja API Key.

**URL Endpointu:** `POST {shop_url}/module/ppmmanager/api`

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Pliki PHP | 3 |
| Pliki konfiguracyjne | 1 (config.xml) |
| Linie kodu (lacznie) | ~994 |
| Akcje API | 5 (ping, getPositions, updatePositions, setCombinationCovers, getCombinationCovers) |
| Hooki PS | 3 (actionGetProductPropertiesAfter, actionPresentProduct, displayHeader) |
| Class Overrides | 1 (Combination::getImages) |
| Pliki PPM Laravel integrujace | 6 |
| Schemat DB (modyfikacje) | 1 kolumna (`cover` w `ps_product_attribute_image`) |

### 1.3 Kluczowe funkcjonalnosci

- **Image Position Management** - Odczyt/zapis kolejnosci obrazkow produktu (ps_image.position)
- **Per-Variant Cover Images** - Dedykowany cover per kombinacja (wariant) produktu
- **Health Check (ping)** - Weryfikacja polaczenia PPM <> modul bez koniecznosci product_id
- **Frontend Gallery Reordering** - JS hook przesuwajacy cover na poczatek galerii po zmianie wariantu
- **Combination Override** - Nadpisanie `Combination::getImages()` sortujace cover na poczatek
- **Automatic Cache Clearing** - Czyszczenie cache PS po kazdej modyfikacji obrazkow

### 1.4 Ewolucja modulu

```
v1.0.0 (ppmimagemanager)    v1.1.0 (ppmimagemanager)    v2.0.0 (ppmmanager)
  - updatePositions            - + ping action               - NOWA nazwa techniczna
  - getPositions               - health check fix            - + setCombinationCovers
                                                              - + getCombinationCovers
                                                              - + 3 hooki frontend
                                                              - + Combination override
                                                              - + cover column w DB
```

---

## 2. Architektura Plikow

### 2.1 Struktura katalogu (v2.0.0 - ppmmanager)

```
ppmmanager/                                          994 linii lacznie
├── ppmmanager.php                                   517 linii  # Glowna klasa modulu
├── controllers/
│   └── front/
│       └── api.php                                  420 linii  # API Controller (5 akcji)
├── override/
│   └── classes/
│       └── Combination.php                           45 linii  # Override getImages()
└── config.xml                                        12 linii  # Metadane modulu PS
```

### 2.2 Struktura katalogu (v1.1.0 - ppmimagemanager, legacy)

```
ppmimagemanager/                                     382 linii lacznie
├── ppmimagemanager.php                              126 linii  # Glowna klasa modulu
├── controllers/
│   └── front/
│       └── api.php                                  242 linii  # API Controller (3 akcje)
└── config.xml                                        12 linii  # Metadane modulu PS
```

### 2.3 Opis plikow

| Plik | Klasa | Opis |
|------|-------|------|
| `ppmmanager.php` | `PpmManager extends Module` | Instalacja, deinstalacja, konfiguracja, hooki frontend, schema DB |
| `controllers/front/api.php` | `PpmManagerApiModuleFrontController` | Router akcji API, autentykacja, handlery, cache clearing |
| `override/classes/Combination.php` | `Combination extends CombinationCore` | Override `getImages()` - sortowanie cover DESC |
| `config.xml` | - | Metadane modulu dla PS Module Manager |

---

## 3. Endpoint API

### 3.1 URL

| Typ URL | Format |
|---------|--------|
| Friendly URLs (ON) | `POST {shop_url}/module/ppmmanager/api` |
| Friendly URLs (OFF) | `POST {shop_url}/index.php?fc=module&module=ppmmanager&controller=api` |

### 3.2 Autentykacja

| Header | Wartosc | Wymagany |
|--------|---------|----------|
| `X-PPM-Api-Key` | 32-znakowy hex klucz API | Tak |
| `Content-Type` | `application/json` | Tak |

Klucz API jest przechowywany w konfiguracji PrestaShop jako `Configuration::get('PPM_MANAGER_API_KEY')`.

Walidacja uzywa `hash_equals()` (timing-safe comparison) z fallbackiem na `$_SERVER['HTTP_X_PPM_API_KEY']` dla serwerow nieobslugujacych `getallheaders()`.

### 3.3 Wspolne kody bledow

| HTTP Status | Znaczenie | Kiedy |
|-------------|-----------|-------|
| 200 | Sukces | Akcja wykonana poprawnie |
| 400 | Bad Request | Brak `action`, brak `product_id`, pusta tablica danych |
| 401 | Unauthorized | Brakujacy lub nieprawidlowy klucz API |
| 404 | Not Found | Produkt nie istnieje w PrestaShop |
| 500 | Server Error | Brak kolumny `cover` w tabeli (setCombinationCovers/getCombinationCovers) |

---

## 4. Akcje API

### 4.1 `ping` - Health Check

Sprawdza czy modul jest zainstalowany i odpowiada. **Nie wymaga `product_id`.**

**Request:**
```json
{
    "action": "ping"
}
```

**Response (200):**
```json
{
    "success": true,
    "module": "ppmmanager",
    "version": "2.0.0",
    "display_name": "PPM Manager",
    "ps_version": "8.1.3",
    "timestamp": "2026-02-13T14:30:00+01:00"
}
```

**Uzycie w PPM:** Test polaczenia w Krok 3 wizarda dodawania sklepu (`AddShop::testPpmModuleConnection()`).

---

### 4.2 `getPositions` - Pobierz pozycje obrazkow

Zwraca aktualne pozycje (sort_order) obrazkow produktu z tabeli `ps_image`.

**Request:**
```json
{
    "action": "getPositions",
    "product_id": 8280
}
```

**Response (200):**
```json
{
    "success": true,
    "product_id": 8280,
    "positions": {
        "45231": 1,
        "45232": 2,
        "45233": 3
    },
    "count": 3
}
```

**Kody bledow:**

| HTTP | Opis |
|------|------|
| 400 | Brak `product_id` lub wartosc <= 0 |
| 404 | Produkt nie istnieje w PrestaShop |

**SQL:**
```sql
SELECT id_image, position
FROM ps_image
WHERE id_product = {product_id}
ORDER BY position ASC
```

---

### 4.3 `updatePositions` - Zaktualizuj pozycje obrazkow

Zmienia kolejnosc (position) obrazkow produktu w bazie PrestaShop. Po aktualizacji automatycznie czysci cache produktu.

**Request:**
```json
{
    "action": "updatePositions",
    "product_id": 8280,
    "positions": {
        "45231": 3,
        "45232": 1,
        "45233": 2
    }
}
```

**Response (200):**
```json
{
    "success": true,
    "updated": 3,
    "total": 3,
    "errors": []
}
```

**Walidacja per-image:**
1. `image_id` musi byc integer > 0
2. Obraz musi nalezec do podanego `product_id` (weryfikacja w `ps_image`)
3. Obrazy nieistniejace sa raportowane w tablicy `errors[]`

**Cache clearing po aktualizacji:**
- `Cache::clean('objectmodel_image_*')` - cache obiektow obrazkow
- `Tools::clearSmartyCache()` - cache szablonow Smarty
- `Product::resetStaticCache()` - cache statyczny produktu

---

### 4.4 `setCombinationCovers` - Ustaw cover per wariant

Ustawia dedykowany obraz okladki (cover) dla kazdej kombinacji (wariantu) produktu. Operuje na kolumnie `cover` w tabeli `ps_product_attribute_image` (dodanej przez modul).

**Request:**
```json
{
    "action": "setCombinationCovers",
    "product_id": 8280,
    "covers": {
        "1234": 45231,
        "1235": 45232
    }
}
```

Gdzie klucz to `combination_id` (id_product_attribute), wartosc to `image_id` (id_image).

**Response (200):**
```json
{
    "success": true,
    "updated": 2,
    "total": 2,
    "errors": []
}
```

**Walidacja per-cover:**
1. `combination_id` i `image_id` musza byc integer > 0
2. Kombinacja musi nalezec do podanego `product_id` (weryfikacja w `ps_product_attribute`)
3. Obraz musi byc przypisany do kombinacji (weryfikacja w `ps_product_attribute_image`)

**Logika ustawiania:**
1. Reset wszystkich coverow dla kombinacji: `UPDATE ps_product_attribute_image SET cover = 0 WHERE id_product_attribute = {combination_id}`
2. Ustawienie nowego covera: `UPDATE ps_product_attribute_image SET cover = 1 WHERE id_product_attribute = {combination_id} AND id_image = {image_id}`

**Kody bledow:**

| HTTP | Opis |
|------|------|
| 400 | Pusta tablica `covers` |
| 404 | Produkt nie istnieje |
| 500 | Brak kolumny `cover` w tabeli (modul wymaga reinstalacji) |

---

### 4.5 `getCombinationCovers` - Pobierz covery wariantow

Zwraca mape coverow dla wszystkich kombinacji produktu ktore maja ustawiony cover.

**Request:**
```json
{
    "action": "getCombinationCovers",
    "product_id": 8280
}
```

**Response (200):**
```json
{
    "success": true,
    "product_id": 8280,
    "covers": {
        "1234": 45231,
        "1235": 45232
    },
    "count": 2
}
```

**SQL:**
```sql
SELECT pai.id_product_attribute, pai.id_image
FROM ps_product_attribute_image pai
INNER JOIN ps_product_attribute pa
    ON pa.id_product_attribute = pai.id_product_attribute
WHERE pa.id_product = {product_id}
AND pai.cover = 1
```

---

## 5. Hooki PrestaShop

Modul rejestruje 3 hooki frontendowe do obslugi per-wariantowych coverow na stronie produktu.

### 5.1 `actionGetProductPropertiesAfter`

**Cel:** Nadpisanie `cover_image_id` w danych produktu dla listingow.

**Kiedy:** Po zaladowaniu wlasciwosci produktu przez PrestaShop.

**Logika:**
1. Sprawdza czy produkt ma aktywna kombinacje (`id_product_attribute > 0`)
2. Weryfikuje istnienie kolumny `cover` (z cache)
3. Pobiera `id_image` z `ps_product_attribute_image WHERE cover = 1`
4. Jesli znaleziony, nadpisuje `$params['product']['cover_image_id']`

### 5.2 `actionPresentProduct`

**Cel:** Nadpisanie `default_image` i `cover` w prezentowanym produkcie.

**Kiedy:** Po utworzeniu `ProductLazyArray` przez PrestaShop.

**Logika:**
1. Pobiera cover image ID dla aktywnej kombinacji (jak wyzej)
2. Przeszukuje tablice `$product['images']` po `id_image`
3. Ustawia znaleziony obraz jako `default_image` i `cover`

### 5.3 `displayHeader`

**Cel:** Wstrzykniecie JavaScript do galerii produktu na frontendzie.

**Kiedy:** Tylko na stronach produktu (`controller === 'product'`).

**Logika wstrzyknietego JS:**
1. Laduje mape coverow z ukrytego `<script type="application/json">` (wygenerowanego z DB)
2. Nasluchuje na event `prestashop.on('updatedProduct')` (zmiana wariantu)
3. Po zmianie wariantu:
   - Znajduje slide ze zdjeciem covera w Swiper.js
   - Przenosi go na poczatek galerii (miniaturki + glowny obraz)
   - Aktualizuje `src` glownego zdjecia i link do zoomu
   - Wywoluje `swiper.update()` i `swiper.slideTo(0)`
4. Obsluguje tez inicjalizacje z preselekcja wariantu z URL

**Kompatybilnosc:** Warehouse theme (Swiper.js), standardowe szablony PS 8.x.

---

## 6. Class Override

### 6.1 `Combination::getImages()`

**Plik:** `override/classes/Combination.php`

**Cel:** Sortowanie obrazkow kombinacji z coverem na poczatku.

**Oryginalne zapytanie PS:**
```sql
ORDER BY pai.id_image ASC
```

**Nadpisane zapytanie:**
```sql
ORDER BY pai.cover DESC, pai.id_image ASC
```

**Efekt:** Gdy PrestaShop wywoluje `Combination::getImages()` (np. w variant switcher), obraz oznaczony jako cover (`cover = 1`) jest zawsze pierwszy na liscie.

---

## 7. Schema Bezpieczenstwa

### 7.1 Mechanizmy autentykacji

| Mechanizm | Szczegoly |
|-----------|-----------|
| API Key | 32-znakowy hex, generowany `bin2hex(random_bytes(16))` przy instalacji |
| Timing-safe comparison | `hash_equals($configuredKey, $providedKey)` - odporne na timing attacks |
| Header extraction | `getallheaders()` z fallbackiem na `$_SERVER['HTTP_X_PPM_API_KEY']` |
| Config storage | `Configuration::get('PPM_MANAGER_API_KEY')` (PS Configuration table) |

### 7.2 Walidacja danych

| Warstwa | Mechanizm |
|---------|-----------|
| Product existence | `Validate::isLoadedObject(new Product($id))` |
| Image ownership | `SELECT ... WHERE id_image = X AND id_product = Y` |
| Combination ownership | `SELECT ... WHERE id_product_attribute = X AND id_product = Y` |
| Image-Combination link | `SELECT COUNT(*) FROM ps_product_attribute_image WHERE ...` |
| Type casting | Wszystkie ID sa castowane do `(int)` |
| DB queries | `Db::getInstance()` z parametryzacja PrestaShop |

### 7.3 Panel konfiguracyjny

| Funkcja | Opis |
|---------|------|
| View API Key | Wyswietlenie aktualnego klucza w formularzu |
| Update API Key | Walidacja: minimum 16 znakow |
| Regenerate Key | Nowy klucz `bin2hex(random_bytes(16))` |

---

## 8. Integracja z PPM Laravel

### 8.1 Pliki PPM korzystajace z modulu

| Plik | Metoda / Property | Opis |
|------|-------------------|------|
| `app/Http/Livewire/Admin/Shops/AddShop.php` | `testPpmModuleConnection()` | Test polaczenia ping w wizardzie sklepu (Krok 3) |
| `app/Http/Livewire/Admin/Shops/AddShop.php` | `$ppmModuleApiKey` | Przechowywanie klucza API w formularzu |
| `app/Services/PrestaShop/PrestaShop8Client.php` | `updateImagePositions()` | Wysylanie pozycji obrazkow via PPM Manager |
| `app/Services/PrestaShop/PrestaShop8Client.php` | `setCombinationCovers()` | Ustawianie coverow per wariant via PPM Manager |
| `app/Services/Media/SmartMediaSyncService.php` | `updatePositions()` | Orkiestracja smart diff sync - krok 4 |
| `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` | `syncMediaIfEnabled()` | Wywolanie SmartMediaSyncService w ramach product sync |
| `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php` | create/update variant | Wywolanie setCombinationCovers po sync wariantow |
| `app/DTOs/Media/MediaSyncDiff.php` | `positionUpdates` | DTO z mapa pozycji do aktualizacji |

### 8.2 Konfiguracja klucza API w PPM

Klucz API modulu jest przechowywany w kolumnie `sync_settings` (JSON) modelu `PrestaShopShop`:

```php
// Odczyt
$apiKey = $shop->sync_settings['ppm_module_api_key'] ?? null;

// Zapis (w AddShop.php przy tworzeniu/edycji sklepu)
$syncSettings['ppm_module_api_key'] = $this->ppmModuleApiKey;
$shop->sync_settings = $syncSettings;
$shop->save();
```

**Lokalizacja w UI:** Admin > Sklepy > Dodaj/Edytuj sklep > Krok 2 (Dane autoryzacji API).

### 8.3 Workflow: Smart Media Sync

```
ProductSyncStrategy::syncMediaIfEnabled()
    └── SmartMediaSyncService::syncImages()
        ├── MediaDiffCalculator::calculateDiff()   // Oblicza roznice desired vs current
        ├── STEP 1: Delete removed (PS Web Services API)
        ├── STEP 2: Upload new (PS Web Services API)
        ├── STEP 3: Set cover if changed (PS Web Services API)
        └── STEP 4: updatePositions() → PPM Manager module  // Pozycje via modul!
```

**Warunek:** `SystemSetting::get('media.auto_sync_on_product_sync')` musi byc wlaczony.

### 8.4 Workflow: Per-Variant Cover Sync

```
SyncShopVariantsToPrestaShopJob::handle()
    ├── Create/Update combination (PS Web Services API)
    ├── setCombinationImages() (PS Web Services API)
    └── setCombinationCovers() → PPM Manager module  // Cover per wariant!
```

**Warunek:** Wariant musi miec przypisany `coverImageId` i niepuste `imageIds`.

### 8.5 Workflow: Test Polaczenia (AddShop)

```
AddShop::runDiagnostics()
    ├── Test WebService API connection
    ├── Check API features
    └── IF ppmModuleApiKey is set:
        └── testPpmModuleConnection()
            └── POST {shop_url}/module/ppmmanager/api { action: "ping" }
                ├── Success → "PPM Manager v2.0.0 polaczony"
                └── Failure → "Modul nie odpowiada" (with details)
```

### 8.6 URL modulu w kliencie Laravel

Klient `PrestaShop8Client` buduje URL jako:
```php
$moduleUrl = rtrim($this->shop->url, '/') . '/module/ppmmanager/api';
```

---

## 9. Instalacja i Konfiguracja

### 9.1 Wymagania

| Wymaganie | Wartosc |
|-----------|---------|
| PrestaShop | 8.0.0+ |
| PHP | 7.4+ (zalecane 8.1+) |
| Poprzednia wersja | Odinstalowac `ppmimagemanager` jesli zainstalowany |

### 9.2 Krok 1: Upload modulu

Skopiuj folder `ppmmanager/` do `/modules/` na serwerze PrestaShop:

```
/modules/ppmmanager/
├── ppmmanager.php
├── controllers/
│   └── front/
│       └── api.php
├── override/
│   └── classes/
│       └── Combination.php
└── config.xml
```

### 9.3 Krok 2: Instalacja w panelu

1. PrestaShop Admin > Modules > Module Manager
2. Szukaj "PPM Manager"
3. Kliknij "Install"
4. Modul automatycznie:
   - Generuje 32-znakowy klucz API
   - Dodaje kolumne `cover` do `ps_product_attribute_image`
   - Rejestruje 3 hooki frontendowe
   - Instaluje override `Combination::getImages()`

### 9.4 Krok 3: Konfiguracja

1. PrestaShop Admin > Modules > PPM Manager > Configure
2. Skopiuj wyswietlony klucz API
3. W PPM: Admin > Sklepy > Edytuj sklep > Krok 2 > Wklej klucz API do pola "PPM Module API Key"

### 9.5 Krok 4: Weryfikacja

1. PPM: Admin > Sklepy > Edytuj sklep > Krok 3 (Test polaczenia)
2. Diagnostyka powinna pokazac:
   - "PPM Manager v2.0.0 polaczony"
   - "PS 8.x.x | Endpoint aktywny"

### 9.6 Upgrade z v1.x (ppmimagemanager)

1. Odinstalowac `ppmimagemanager` w PrestaShop Admin > Modules
2. Usunac folder `/modules/ppmimagemanager/`
3. Upload nowego folderu `/modules/ppmmanager/`
4. Zainstalowac "PPM Manager" w Module Manager
5. Skopiowac NOWY klucz API do PPM (klucz sie zmienia przy nowej instalacji)
6. Przetestowac polaczenie w PPM Admin > Sklepy

---

## 10. Troubleshooting

### 10.1 Modul nie odpowiada (HTTP 404)

**Przyczyna:** Friendly URLs wylaczone lub `.htaccess` nieprawidlowy.

**Rozwiazanie:** Uzyj pelnego URL:
```
{shop_url}/index.php?fc=module&module=ppmmanager&controller=api
```

### 10.2 Bledny klucz API (HTTP 401)

**Przyczyna:** Klucz w PPM nie zgadza sie z kluczem w PrestaShop.

**Rozwiazanie:**
1. PrestaShop Admin > Modules > PPM Manager > Configure
2. Skopiuj aktualny klucz API
3. PPM Admin > Sklepy > Edytuj > Krok 2 > Wklej nowy klucz
4. Zapisz i przetestuj polaczenie (Krok 3)

### 10.3 Test pokazuje "Modul nie zainstalowany"

**Przyczyna (v1.0.0):** Test uzywal `getPositions` z `product_id=1` ktory mogl nie istniec.

**Fix (v1.1.0+):** Test uzywa akcji `ping` ktora nie wymaga `product_id`.

### 10.4 Pozycje obrazkow sie nie zmieniaja

**Przyczyna:** Cache PrestaShop.

**Rozwiazanie:** Modul automatycznie czysci cache po `updatePositions`. Jesli problem utrzymuje sie:
1. PrestaShop Admin > Advanced Parameters > Performance > Clear cache
2. Sprawdz czy klucz API jest prawidlowy (brak logu `[IMAGE API] PPM module API key not configured`)

### 10.5 setCombinationCovers zwraca HTTP 500

**Przyczyna:** Brak kolumny `cover` w tabeli `ps_product_attribute_image`.

**Rozwiazanie:**
1. Odinstalowac modul w PrestaShop Admin
2. Zainstalowac ponownie (kolumna zostanie utworzona automatycznie)
3. Alternatywnie, dodac kolumne recznie:
```sql
ALTER TABLE ps_product_attribute_image
ADD COLUMN cover TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
```

### 10.6 Per-wariantowy cover nie wyswietla sie na frontendzie

**Przyczyny i rozwiazania:**

| Przyczyna | Rozwiazanie |
|-----------|-------------|
| Brak hookow | Sprawdz w PS Admin > Design > Positions czy `ppmmanager` jest zarejestrowany na 3 hookach |
| Override nie aktywny | Usun `/var/cache/prod/` i `/var/cache/dev/` w katalogu PS |
| JS nie laduje sie | Sprawdz w DevTools czy element `<script id="ppm-variant-covers">` jest obecny na stronie produktu |
| Swiper nie aktualizuje sie | Sprawdz kompatybilnosc z tematem (modul obsluguje Warehouse theme) |

### 10.7 Modul dziala na starym URL (ppmimagemanager)

**Przyczyna:** Migracja z v1.x nie zostala ukonczona - stary modul nadal zainstalowany.

**Rozwiazanie:**
1. Sprawdz `/modules/ppmimagemanager/` - jesli istnieje, odinstaluj stary modul
2. Upewnij sie ze PPM klient uzywa URL `/module/ppmmanager/api` (nie `/module/ppmimagemanager/api`)
3. Sprawdz w `PrestaShop8Client.php` ze `$moduleUrl` zawiera `ppmmanager`

---

## 11. Changelog

### v2.0.0 (2026-02-13)

**BREAKING:** Nowa nazwa techniczna modulu: `ppmmanager` (zamiast `ppmimagemanager`).

- **Nowe akcje API:** `setCombinationCovers`, `getCombinationCovers` - per-wariantowe okladki
- **Nowy hook:** `actionGetProductPropertiesAfter` - nadpisanie cover_image_id w listingach
- **Nowy hook:** `actionPresentProduct` - nadpisanie default_image w prezentowanym produkcie
- **Nowy hook:** `displayHeader` - JS reordering galerii po zmianie wariantu
- **Nowy override:** `Combination::getImages()` - sortowanie cover DESC
- **Schema DB:** Dodana kolumna `cover TINYINT(1)` do `ps_product_attribute_image`
- **Config key:** Zmiana z `PPM_IMAGE_API_KEY` na `PPM_MANAGER_API_KEY`
- **Upgrade support:** Metoda `upgrade()` rejestruje brakujace hooki przy aktualizacji

### v1.1.0 (2026-02-06)

- **Nowa akcja:** `ping` - health check bez wymagania product_id
- **Fix:** Test polaczenia w AddShop nie wymaga istniejacego produktu

### v1.0.0 (2025-12-01)

- **Inicjalna wersja** jako `ppmimagemanager`
- Akcje: `updatePositions`, `getPositions`
- Autentykacja X-PPM-Api-Key
- Integracja z SmartMediaSyncService
