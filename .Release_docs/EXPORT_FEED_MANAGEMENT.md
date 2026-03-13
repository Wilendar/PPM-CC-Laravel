# PPM - Export & Feed Management Documentation

> **Wersja:** 1.2.0
> **Data:** 2026-03-13
> **Status:** Production Ready
> **Changelog:** Vehicle Compatibility we wszystkich formatach eksportu (PS XML features, CSV czytelny format, Google/Ceneo atrybuty)

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Routes i Endpointy](#4-routes-i-endpointy)
5. [Formaty Eksportu i Generatory](#5-formaty-eksportu-i-generatory)
6. [Profil Eksportu - Konfiguracja](#6-profil-eksportu-konfiguracja)
7. [Feed Controller - Publiczny Dostep](#7-feed-controller-publiczny-dostep)
8. [Feed Tracking i Bot Detection](#8-feed-tracking-i-bot-detection)
9. [Harmonogram i Scheduler](#9-harmonogram-i-scheduler)
10. [UI - ExportManager Panel](#10-ui-exportmanager-panel)
11. [UI - ExportProfileForm Wizard](#11-ui-exportprofileform-wizard)
12. [Troubleshooting](#12-troubleshooting)
13. [Changelog](#13-changelog)

---

## 1. Overview

### 1.1 Opis modulu

Export & Feed Management to **centralny system eksportu produktow** w PPM. Umozliwia tworzenie **konfigurowalnych profili eksportu** w roznych formatach (CSV, JSON, XML Google/Ceneo/PrestaShop) z elastycznym wyborem pol, filtrow, grup cenowych i magazynow. Profile moga byc udostepniane jako **publiczne feedy** z tokenem URL dla zewnetrznych konsumentow (Google Merchant Center, Ceneo.pl, systemy ERP).

System zawiera **rozszerzony tracking** dostepu do feedow: wykrywanie botow (Googlebot, CeneoBot, Bingbot itp.), pomiar czasu odpowiedzi, identyfikacja zrodla (cache vs on-the-fly), referer tracking oraz agregowane statystyki per profil.

**URL Panelu:** `/admin/export`
**URL Feedow:** `/feed/{token}` (publiczne, bez autoryzacji)

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Komponenty Livewire | 2 (ExportManager, ExportProfileForm) |
| Traits | 2 (ProfileFormFields, ProfileFormFilters) |
| Modele | 2 (ExportProfile, ExportProfileLog) |
| Serwisy | 4 (ExportProfileService, ProductExportService, FeedGeneratorFactory, FeedSchedulerService) |
| Generatory feedow | 5 (CSV, JSON, XML Google, XML Ceneo, XML PrestaShop) |
| Controllers | 1 (FeedController) |
| Blade Views | 2 (export-manager, export-profile-form) |
| Tabele DB | 2 (export_profiles, export_profile_logs) |
| Migracje | 3 |
| Linie kodu (backend) | ~3280 |
| Linie kodu (frontend) | ~990 |
| **RAZEM** | **~4270 linii** |

### 1.3 Kluczowe funkcjonalnosci

- **Multi-format Export** - CSV, JSON, XML Google Shopping, XML Ceneo, XML PrestaShop
- **5-krokowy Wizard** - Tworzenie profili: info, pola, filtry, ceny/magazyny, podglad
- **Publiczne Feedy** - Token-based URL bez autoryzacji dla zewnetrznych konsumentow
- **Auto-cache** - Serwowanie z cache gdy plik jest swiezy, generacja on-the-fly gdy potrzebna
- **Bot Detection** - Automatyczne wykrywanie 10 typow botow (Googlebot, CeneoBot, Bingbot, itp.)
- **Response Tracking** - Pomiar czasu odpowiedzi, zrodlo (cache/on_the_fly), HTTP status, Content-Type
- **Agregowane Statystyki** - Mini-stats per profil: requesty, unique IP, boty, avg ms, cache hit%
- **Harmonogram** - Reczny lub automatyczny (1h/6h/12h/24h) z dispatching GenerateFeedJob
- **Dynamiczne pola** - Grupy cenowe i magazyny ladowane z DB (nie hardcoded)
- **Filtrowanie logów** - Po profilu, akcji, dacie, typie (boty/ludzie)

### 1.4 Workflow

```
[Tworzenie profilu]     [Publiczny dostep]      [Monitoring]
       |                       |                      |
  5-step wizard          GET /feed/{token}      Tab "Logi"
       |                       |                      |
  Konfiguracja           Cache fresh?           Tabela logow
  pol/filtrow            /         \            + mini-stats
       |               TAK        NIE           per profil
       v                |          |
  Generuj feed    Serve cached   Generate
       |           + log         on-the-fly
       v           "cache"       + log "on_the_fly"
  Plik na dysku         \        /
  + log "generated"     Detect bot
                        + response_time_ms
                        + referer
```

---

## 2. Architektura Plikow

### 2.1 Livewire Components

| Plik | Linie | Opis |
|------|-------|------|
| `app/Http/Livewire/Admin/Export/ExportManager.php` | ~564 | Panel glowny: lista profili, logi, statystyki, filtry |
| `app/Http/Livewire/Admin/Export/ExportProfileForm.php` | ~403 | 5-krokowy wizard tworzenia/edycji profilu |

### 2.2 Traits

| Plik | Linie | Opis |
|------|-------|------|
| `app/Http/Livewire/Admin/Export/Traits/ProfileFormFields.php` | ~149 | Operacje na polach eksportu (toggle, select all, grouped) |
| `app/Http/Livewire/Admin/Export/Traits/ProfileFormFilters.php` | ~147 | Zarzadzanie filtrami profilu |

### 2.3 Modele

| Model | Plik | Linie | Tabela |
|-------|------|-------|--------|
| ExportProfile | `app/Models/ExportProfile.php` | ~218 | `export_profiles` |
| ExportProfileLog | `app/Models/ExportProfileLog.php` | ~108 | `export_profile_logs` |

### 2.4 Serwisy

| Serwis | Plik | Linie | Przeznaczenie |
|--------|------|-------|---------------|
| ExportProfileService | `app/Services/Export/ExportProfileService.php` | ~187 | CRUD profili, definicje pol/filtrow/formatow |
| ProductExportService | `app/Services/Export/ProductExportService.php` | ~289 | Query building, field mapping, eager loading |
| FeedGeneratorFactory | `app/Services/Export/FeedGeneratorFactory.php` | ~41 | Factory pattern: format -> generator |
| FeedSchedulerService | `app/Services/Export/FeedSchedulerService.php` | ~107 | Harmonogram automatycznej generacji feedow |

### 2.5 Generatory Feedow

| Generator | Plik | Linie | Format |
|-----------|------|-------|--------|
| CsvFeedGenerator | `app/Services/Export/Generators/CsvFeedGenerator.php` | ~115 | CSV (separator `;`) |
| JsonFeedGenerator | `app/Services/Export/Generators/JsonFeedGenerator.php` | ~105 | JSON |
| XmlGoogleShoppingGenerator | `app/Services/Export/Generators/XmlGoogleShoppingGenerator.php` | ~326 | XML Google Merchant |
| XmlCeneoGenerator | `app/Services/Export/Generators/XmlCeneoGenerator.php` | ~302 | XML Ceneo.pl |
| PrestaShopXmlGenerator | `app/Services/Export/Generators/PrestaShopXmlGenerator.php` | ~296 | XML PrestaShop import |

**Interface:** `app/Services/Export/Generators/FeedGeneratorInterface.php` (~23 linii)

```php
interface FeedGeneratorInterface
{
    public function generate(array $products, ExportProfile $profile): string;
    public function getContentType(): string;
    public function getFileExtension(): string;
}
```

### 2.6 Controllers

| Controller | Plik | Linie | Opis |
|------------|------|-------|------|
| FeedController | `app/Http/Controllers/FeedController.php` | ~217 | Publiczny endpoint feed + bot detection |

### 2.7 Blade Views

```
resources/views/livewire/admin/export/
+-- export-manager.blade.php          # Panel glowny (profile + logi) ~549 linii
+-- export-profile-form.blade.php     # 5-step wizard ~441 linii
```

### 2.8 Migracje

| Migracja | Opis |
|----------|------|
| `2026_03_08_000001_create_export_profiles_table.php` | Tabela profili eksportu |
| `2026_03_08_000002_create_export_profile_logs_table.php` | Tabela logow |
| `2026_03_08_100001_add_tracking_columns_to_export_profile_logs.php` | Rozszerzony tracking |

---

## 3. Schema Bazy Danych

### 3.1 Tabela: `export_profiles`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `name` | VARCHAR(255) | NO | - | Nazwa profilu |
| `slug` | VARCHAR(255) | NO | - | URL-friendly slug (UNIQUE) |
| `token` | VARCHAR(64) | NO | - | Token dostepu do feed (UNIQUE, random 64 chars) |
| `format` | ENUM | NO | - | csv, xlsx, json, xml_google, xml_ceneo, xml_prestashop |
| `field_config` | JSON | YES | NULL | Mapa wybranych pol {key: true/false} |
| `filter_config` | JSON | YES | NULL | Konfiguracja filtrow |
| `price_groups` | JSON | YES | NULL | Wybrane grupy cenowe |
| `warehouses` | JSON | YES | NULL | Wybrane magazyny |
| `shop_ids` | JSON | YES | NULL | Wybrane sklepy PrestaShop |
| `schedule` | ENUM | NO | manual | manual, 1h, 6h, 12h, 24h |
| `is_active` | TINYINT(1) | NO | 1 | Czy profil aktywny |
| `is_public` | TINYINT(1) | NO | 0 | Czy feed publiczny (token URL) |
| `file_path` | VARCHAR(500) | YES | NULL | Sciezka do ostatnio wygenerowanego pliku |
| `file_size` | BIGINT UNSIGNED | YES | NULL | Rozmiar pliku w bajtach |
| `product_count` | INT UNSIGNED | YES | NULL | Liczba produktow w eksporcie |
| `generation_duration` | INT UNSIGNED | YES | NULL | Czas generacji w ms |
| `last_generated_at` | TIMESTAMP | YES | NULL | Ostatnia generacja |
| `next_generation_at` | TIMESTAMP | YES | NULL | Nastepna zaplanowana generacja |
| `created_by` | BIGINT UNSIGNED | YES | NULL | FK -> users.id |
| `updated_by` | BIGINT UNSIGNED | YES | NULL | FK -> users.id |
| `created_at` | TIMESTAMP | YES | NULL | Laravel timestamp |
| `updated_at` | TIMESTAMP | YES | NULL | Laravel timestamp |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indeksy:** `idx_token` (INDEX), `slug` (UNIQUE), `token` (UNIQUE)
**FK:** `created_by` -> `users.id` (SET NULL), `updated_by` -> `users.id` (SET NULL)

### 3.2 Tabela: `export_profile_logs`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `export_profile_id` | BIGINT UNSIGNED | NO | - | FK -> export_profiles.id |
| `action` | ENUM | NO | - | generated, downloaded, accessed, error |
| `user_id` | BIGINT UNSIGNED | YES | NULL | FK -> users.id |
| `ip_address` | VARCHAR(45) | YES | NULL | Adres IP (IPv4/IPv6) |
| `user_agent` | VARCHAR(500) | YES | NULL | User-Agent przegladarki/klienta |
| `product_count` | INT UNSIGNED | YES | NULL | Liczba produktow |
| `file_size` | BIGINT UNSIGNED | YES | NULL | Rozmiar pliku w bajtach |
| `duration` | INT UNSIGNED | YES | NULL | Czas operacji w sekundach |
| `error_message` | TEXT | YES | NULL | Komunikat bledu (dla akcji error) |
| `response_time_ms` | INT UNSIGNED | YES | NULL | Czas odpowiedzi HTTP w ms |
| `served_from` | ENUM | YES | NULL | cache, generated, on_the_fly |
| `http_status` | SMALLINT UNSIGNED | YES | NULL | Kod HTTP odpowiedzi |
| `content_type` | VARCHAR(100) | YES | NULL | Content-Type odpowiedzi |
| `referer` | VARCHAR(500) | YES | NULL | HTTP Referer header |
| `is_bot` | TINYINT(1) | NO | 0 | Czy request od bota |
| `bot_name` | VARCHAR(100) | YES | NULL | Nazwa wykrytego bota |
| `created_at` | TIMESTAMP | YES | NULL | Laravel timestamp |
| `updated_at` | TIMESTAMP | YES | NULL | Laravel timestamp |

**Indeksy:** `idx_profile_action` (export_profile_id, action), `idx_created` (created_at), `idx_is_bot` (is_bot), `idx_served_from` (served_from)
**FK:** `export_profile_id` -> `export_profiles.id` (CASCADE DELETE), `user_id` -> `users.id` (SET NULL)

### 3.3 Relacje miedzy tabelami

```
export_profiles
    |--- 1:N ---> export_profile_logs (cascade delete)
    |--- N:1 ---> users (created_by, updated_by)

export_profile_logs
    |--- N:1 ---> export_profiles
    |--- N:1 ---> users (user_id, nullable)
```

---

## 4. Routes i Endpointy

### 4.1 Admin Routes (wymagaja autoryzacji)

| Method | URL | Komponent/Controller | Nazwa | Opis |
|--------|-----|---------------------|-------|------|
| GET | `/admin/export` | ExportManager | `admin.export.index` | Panel glowny |
| GET | `/admin/export/create` | ExportProfileForm | `admin.export.create` | Nowy profil (wizard) |
| GET | `/admin/export/{profile}/edit` | ExportProfileForm | `admin.export.edit` | Edycja profilu (wizard) |

### 4.2 Public Feed Routes (bez autoryzacji, token-based)

| Method | URL | Controller | Nazwa | Opis |
|--------|-----|-----------|-------|------|
| GET | `/feed/{token}` | FeedController@show | `feed.show` | Serwowanie feeda (inline) |
| GET | `/feed/{token}/download` | FeedController@download | `feed.download` | Pobieranie feeda (attachment) |

---

## 5. Formaty Eksportu i Generatory

### 5.1 Wspierane formaty

| Format | Generator | Content-Type | Rozszerzenie | Opis |
|--------|-----------|-------------|-------------|------|
| `csv` | CsvFeedGenerator | `text/csv` | `.csv` | CSV z separatorem `;` |
| `json` | JsonFeedGenerator | `application/json` | `.json` | JSON array |
| `xml_google` | XmlGoogleShoppingGenerator | `application/xml` | `.xml` | Google Merchant Center feed |
| `xml_ceneo` | XmlCeneoGenerator | `application/xml` | `.xml` | Ceneo.pl feed |
| `xml_prestashop` | PrestaShopXmlGenerator | `application/xml` | `.xml` | PrestaShop import format |

### 5.2 Factory Pattern

```php
// FeedGeneratorFactory::make()
$generator = match ($format) {
    'csv'            => new CsvFeedGenerator(),
    'json'           => new JsonFeedGenerator(),
    'xml_google'     => new XmlGoogleShoppingGenerator(),
    'xml_ceneo'      => new XmlCeneoGenerator(),
    'xml_prestashop' => new PrestaShopXmlGenerator(),
};
```

### 5.3 Interface

Kazdy generator implementuje `FeedGeneratorInterface`:
- `generate(array $products, ExportProfile $profile): string` - generuje plik, zwraca sciezke
- `getContentType(): string` - MIME type dla HTTP response
- `getFileExtension(): string` - rozszerzenie pliku

### 5.4 Dopasowania pojazdow (Vehicle Compatibility) w feedach

System eksportuje dane z tabeli `vehicle_compatibility` (37K+ rekordow) do wszystkich formatow.
Eager loading: `vehicleCompatibility.vehicleModel` + `vehicleCompatibility.compatibilityAttribute`.

**Chunked loading:** Profile z polami compatibility uzywaja chunked query (50 produktow/chunk) aby uniknac memory exhaustion.

| Format | Pole | Wynik w feedzie |
|--------|------|----------------|
| **XML PrestaShop** | `compatibility_full` (auto-injected) | `<associations><product_features>` z `<id>`, `<name>` (Oryginal/Model/Zamiennik), `<value>` (nazwa pojazdu) |
| **CSV** | `compatibility_full` | Czytelny format: `KAYO AU150 (Oryginal) \| MRF E150 (Zamiennik)` |
| **JSON** | `compatibility_full` | JSON array: `[{feature:"Model", value:"..."}, {feature:"Typ", value:"..."}]` |
| **XML Google** | `compatible_vehicles` | `<g:product_detail>` z sekcja "Dopasowania", atrybut "Kompatybilne pojazdy" |
| **XML Ceneo** | `compatible_vehicles` | `<a name="Kompatybilne pojazdy">` w sekcji `<attrs>` |

**PrestaShop Feature IDs:**

| ID | Nazwa | Stala w kodzie |
|----|-------|---------------|
| 431 | Oryginal | `PS_FEATURE_ORYGINAL` |
| 432 | Model | `PS_FEATURE_MODEL` |
| 433 | Zamiennik | `PS_FEATURE_ZAMIENNIK` |

**Logika grupowania (PrestaShop XML):** Pojazdy sa grupowane wg typu dopasowania.
Feature 431 = pojazdy oryginalne, 433 = zamienniki, 432 = unia obu (wszystkie modele).
Mapowanie identyczne z `VehicleCompatibilitySyncService::transformToPrestaShopFeatures()`.

---

## 6. Profil Eksportu - Konfiguracja

### 6.1 Dostepne pola eksportu

Pola sa pogrupowane i ladowane dynamicznie przez `ExportProfileService::getAvailableFields()`:

| Grupa | Pola |
|-------|------|
| **Podstawowe** | sku, name, slug, short_description, long_description, ean, weight, height, width, length, tax_rate, manufacturer, supplier_code |
| **Status** | is_active, is_featured, is_variant_master, created_at, updated_at |
| **SEO** | meta_title, meta_description |
| **Ceny** | Dynamiczne z DB: price_net_{code}, price_gross_{code} per grupa cenowa |
| **Stany** | Dynamiczne z DB: stock_{code}, reserved_{code} per magazyn |
| **Kategorie** | category_path, category_primary |
| **Media** | image_url_main, image_urls_all |
| **Dopasowania** | compatible_vehicles, compatible_vehicles_count, compatibility_types, compatibility_full |

### 6.2 Dostepne filtry

| Filtr | Typ | Opis |
|-------|-----|------|
| `is_active` | select | Aktywne / Nieaktywne / Wszystkie |
| `category_ids` | multiselect | Filtrowanie po kategoriach |
| `manufacturer` | text | Filtrowanie po producencie |
| `has_stock` | select | Z dostepnym stanem / Bez stanu |
| `shop_ids` | multiselect | Filtrowanie po sklepach PrestaShop |

### 6.3 Harmonogramy

| Schedule | Minuty | Opis |
|----------|--------|------|
| `manual` | null | Tylko reczna generacja |
| `1h` | 60 | Co godzine |
| `6h` | 360 | Co 6 godzin |
| `12h` | 720 | Co 12 godzin |
| `24h` | 1440 | Co 24 godziny |

---

## 7. Feed Controller - Publiczny Dostep

### 7.1 Logika show() - serwowanie feeda

```
GET /feed/{token}
    |
    v
Znajdz profil (active + public + token)
    |
    v
Detect bot (User-Agent)
    |
    v
Plik istnieje i swiezy?
   /             \
  TAK            NIE
   |              |
Serve z cache  Generate on-the-fly
   |              |
Log: served_from  Log: served_from
    = "cache"     = "on_the_fly"
   \             /
    v           v
Log: response_time_ms, http_status,
     content_type, referer, is_bot, bot_name
```

### 7.2 Logika download() - pobieranie feeda

Identyczna jak `show()`, ale serwuje jako attachment (`Content-Disposition: attachment`).

### 7.3 HTTP Response Headers

```
Content-Type: [z generatora]
X-Feed-Generated: [ISO 8601 timestamp]
X-Product-Count: [liczba produktow]
X-Served-From: cache | on_the_fly
Cache-Control: public, max-age=300
```

---

## 8. Feed Tracking i Bot Detection

### 8.1 Wykrywane boty

Metoda `FeedController::detectBot()` rozpoznaje 10 typow botow:

| Bot Name | Pattern | Przyklad User-Agent |
|----------|---------|---------------------|
| Googlebot | `/googlebot/i` | Googlebot/2.1 (+http://www.google.com/bot.html) |
| Bingbot | `/bingbot/i` | Mozilla/5.0 (compatible; bingbot/2.0) |
| CeneoBot | `/ceneo/i` | Mozilla/5.0 (compatible; CeneoBot/1.0) |
| FacebookBot | `/facebookexternalhit/i` | facebookexternalhit/1.1 |
| YandexBot | `/yandexbot/i` | Mozilla/5.0 (compatible; YandexBot/3.0) |
| AhrefsBot | `/ahrefsbot/i` | Mozilla/5.0 (compatible; AhrefsBot/7.0) |
| SemrushBot | `/semrushbot/i` | Mozilla/5.0 (compatible; SemrushBot/7) |
| Curl | `/curl\//i` | curl/7.88.1 |
| Wget | `/wget\//i` | Wget/1.21.4 |
| Python | `/python-requests\|urllib/i` | python-requests/2.31.0 |

### 8.2 Logowane dane trackingowe

| Pole | Opis | Przyklad |
|------|------|---------|
| `response_time_ms` | Czas od poczatku requestu do odpowiedzi | 45 |
| `served_from` | Zrodlo pliku | cache, on_the_fly |
| `http_status` | Kod HTTP | 200 |
| `content_type` | MIME type | application/xml |
| `referer` | HTTP Referer | https://www.google.com/search?q=parts |
| `is_bot` | Czy bot | true/false |
| `bot_name` | Nazwa bota | Googlebot |

### 8.3 Agregowane statystyki per profil

Metoda `ExportManager::getFeedStatsProperty()` oblicza per profil:

```sql
SELECT
    export_profile_id,
    COUNT(*) as total_requests,
    COUNT(DISTINCT ip_address) as unique_ips,
    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_requests,
    SUM(CASE WHEN is_bot = 0 THEN 1 ELSE 0 END) as human_requests,
    AVG(response_time_ms) as avg_response_ms,
    SUM(CASE WHEN served_from = 'cache' THEN 1 ELSE 0 END) as cache_hits,
    SUM(CASE WHEN served_from IS NOT NULL AND served_from != 'cache' THEN 1 ELSE 0 END) as cache_misses,
    MAX(created_at) as last_access
WHERE action IN ('accessed', 'downloaded')
GROUP BY export_profile_id
```

### 8.4 Model Scopes

```php
ExportProfileLog::bots()    // ->where('is_bot', true)
ExportProfileLog::humans()  // ->where('is_bot', false)
```

---

## 9. Harmonogram i Scheduler

### 9.1 FeedSchedulerService

Orchestruje automatyczna generacje feedow:

| Metoda | Opis |
|--------|------|
| `getProfilesDueForGeneration()` | Zwraca profile gotowe do generacji |
| `generateFeed($profile)` | Dispatchuje `GenerateFeedJob` |
| `calculateNextGenerationTime($profile)` | Oblicza nastepny termin |
| `processScheduledFeeds()` | Przetwarza wszystkie zaplanowane feedy |

### 9.2 Warunki generacji

Profil jest "due" gdy:
1. `is_active = true`
2. `schedule != 'manual'`
3. `next_generation_at <= now()` LUB (`next_generation_at IS NULL` AND `last_generated_at IS NULL`)

### 9.3 Freshness check

```php
// ExportProfile::isFeedFresh()
// manual schedule -> zawsze fresh (generuj tylko recznie)
// timed schedule -> fresh jesli last_generated_at < schedule minutes ago
```

---

## 10. UI - ExportManager Panel

### 10.1 Taby

| Tab | Zawartosc |
|-----|-----------|
| **Profile eksportu** | Lista profili z kartami, statystyki, filtry, mini-stats per profil |
| **Logi** | Tabela logow z rozszerzonymi kolumnami, filtry |

### 10.2 Karty statystyk (gora)

6 kart: Profile, Aktywne, Publiczne, Produktow, Rozmiar plikow, Pobrania

### 10.3 Filtry profili

- Szukaj (nazwa, slug, format)
- Format (dropdown)
- Harmonogram (dropdown)

### 10.4 Karta profilu

Kazda karta profilu zawiera:
- Nazwa + badges (format, harmonogram, ostatnia generacja, produkty, rozmiar)
- Status badges (Aktywny/Nieaktywny, Publiczny)
- Feed URL (jesli publiczny) + przycisk kopiowania
- **Mini-statystyki:** requesty, unique IP, boty, avg ms, cache hit%
- Akcje: Generuj, Pobierz, Edytuj, Dezaktywuj, Ukryj feed, Duplikuj, Usun

### 10.5 Tabela logow - kolumny

| Kolumna | Opis |
|---------|------|
| Data | Timestamp |
| Profil | Nazwa profilu |
| Akcja | Badge: Wygenerowano/Pobrano/Dostep/Blad |
| Uzytkownik / IP | Nazwa uzytkownika lub adres IP |
| Bot | Ikona robota + nazwa bota LUB ikona czlowieka |
| Zrodlo | Badge: Cache (zielony) / On-the-fly (zolty) / Generacja (niebieski) |
| Czas odp. | response_time_ms (np. "45ms", "1.2s") |
| Referer | Skrocony referer (domena) lub "-" |
| Produktow | Liczba produktow |
| Rozmiar | Rozmiar pliku |

### 10.6 Filtry logow

- Profil (dropdown)
- Akcja (dropdown)
- Od / Do (date pickers)
- **Typ:** Wszystkie / Tylko boty / Tylko ludzie

---

## 11. UI - ExportProfileForm Wizard

### 11.1 Kroki wizarda

| Krok | Nazwa | Zawartosc |
|------|-------|-----------|
| 1 | Basic Info | Nazwa, format (6 opcji), harmonogram (5 opcji) |
| 2 | Field Selection | Pogrupowane pola z toggle i select all |
| 3 | Filters | Active, stock, categories, manufacturer, shops |
| 4 | Price Groups & Warehouses | Wybor grup cenowych i magazynow |
| 5 | Preview | Podglad 5 pierwszych produktow |

### 11.2 Architektura

- **Glowny komponent:** `ExportProfileForm` - lifecycle, nawigacja, zapis
- **Trait `ProfileFormFields`** - toggle pol, select/deselect all, grouped fields
- **Trait `ProfileFormFilters`** - zarzadzanie filtrami

---

## 12. Troubleshooting

### 12.1 Feed zwraca 404

**Przyczyna:** Profil nie jest aktywny lub nie jest publiczny, albo token jest nieprawidlowy.

**Rozwiazanie:**
1. Sprawdz `is_active = 1` i `is_public = 1` w bazie
2. Zweryfikuj token w URL vs `export_profiles.token`
3. Sprawdz czy profil nie jest soft-deleted (`deleted_at IS NULL`)

### 12.2 Feed serwuje stare dane

**Przyczyna:** Plik jest oznaczony jako "fresh" wg harmonogramu.

**Rozwiazanie:**
1. Kliknij "Generuj" w panelu admina aby wymusic regeneracje
2. Lub zmien harmonogram na czestszy
3. Sprawdz `last_generated_at` w bazie

### 12.3 Brak danych w kolumnach trackingowych (Logi)

**Przyczyna:** Logi utworzone przed migracja `2026_03_08_100001` nie maja nowych pol.

**Rozwiazanie:** To jest oczekiwane zachowanie. Nowe kolumny beda wypelniane tylko dla requestow po migracji. Stare logi pokazuja "-".

### 12.4 Bot nie jest wykrywany

**Przyczyna:** User-Agent bota nie pasuje do zadnego z 10 zdefiniowanych patternow.

**Rozwiazanie:**
1. Sprawdz `user_agent` w logach
2. Dodaj nowy pattern do `FeedController::detectBot()`:
```php
'NazwaBota' => '/pattern/i',
```

### 12.5 Generacja feeda trwa dlugo

**Przyczyna:** Duza liczba produktow lub skomplikowane filtry.

**Rozwiazanie:**
1. Ogranicz liczbe produktow filtrami (kategoria, aktywnosc, stock)
2. Ogranicz wybrane pola (mniej pol = mniej eager loading)
3. Sprawdz `generation_duration` w profilu
4. Rozwazyc czestszy harmonogram aby cache byl zawsze swiezy

---

## 13. Changelog

### v1.2.0 (2026-03-13)

- **Nowa funkcja:** Dopasowania pojazdow (Vehicle Compatibility) we WSZYSTKICH formatach eksportu
- **XML PrestaShop:** `<associations><product_features>` z feature ID + `<name>` (Oryginal/Model/Zamiennik) + `<value>` (nazwa pojazdu)
- **CSV:** `compatibility_full` konwertowany z JSON blob na czytelny format `Nazwa (Typ) | Nazwa (Typ)`
- **XML Google Shopping:** `<g:product_detail>` z sekcja "Dopasowania" i atrybutem "Kompatybilne pojazdy"
- **XML Ceneo:** `<a name="Kompatybilne pojazdy">` w sekcji `<attrs>`
- **JSON:** Passthrough `compatibility_full` jako JSON array (bez zmian)
- **Fix:** Eager loading `vehicleCompatibility.compatibilityAttribute` (wczesniej brakowalo, dzialalo przez `$with` w modelu)
- **Fix:** Chunked product loading (50/chunk) dla profili z dopasowaniami - zapobiega memory exhaustion przy 37K+ rekordow
- **Pliki zmienione:** ProductExportService, PrestaShopXmlGenerator, CsvFeedGenerator, XmlGoogleShoppingGenerator, XmlCeneoGenerator

### v1.1.0 (2026-03-08)

- **Nowa funkcja:** Enhanced Feed Tracking - bot detection (10 typow botow)
- **Nowa funkcja:** Response time measurement (response_time_ms)
- **Nowa funkcja:** Served from tracking (cache/generated/on_the_fly)
- **Nowa funkcja:** Referer tracking z HTTP header
- **Nowa funkcja:** Agregowane mini-statystyki per profil (req, IP, boty, avg ms, cache%)
- **Nowa funkcja:** Filtr "Tylko boty / Tylko ludzie" w tabeli logow
- **Nowa funkcja:** 4 nowe kolumny w tabeli logow (Bot, Zrodlo, Czas odp., Referer)
- **Model:** Nowe pola w ExportProfileLog (response_time_ms, served_from, http_status, content_type, referer, is_bot, bot_name)
- **Model:** Scopes `scopeBots()`, `scopeHumans()`, stala `SERVED_FROM`
- **Migracja:** `2026_03_08_100001_add_tracking_columns_to_export_profile_logs`

### v1.0.0 (2026-03-08)

- **Inicjalna wersja** systemu Export & Feed Management
- 5 generatorow feedow (CSV, JSON, XML Google, XML Ceneo, XML PrestaShop)
- ExportManager panel z lista profili i logami
- ExportProfileForm 5-step wizard
- Publiczne feedy z token-based URL
- FeedSchedulerService z automatyczna generacja
- Pelny system logowania (generated, downloaded, accessed, error)

---

## Appendix A: Kluczowe pliki

| Typ | Sciezka |
|-----|---------|
| Panel glowny | `app/Http/Livewire/Admin/Export/ExportManager.php` |
| Wizard profilu | `app/Http/Livewire/Admin/Export/ExportProfileForm.php` |
| Model profilu | `app/Models/ExportProfile.php` |
| Model logow | `app/Models/ExportProfileLog.php` |
| CRUD serwis | `app/Services/Export/ExportProfileService.php` |
| Query serwis | `app/Services/Export/ProductExportService.php` |
| Generator factory | `app/Services/Export/FeedGeneratorFactory.php` |
| Scheduler | `app/Services/Export/FeedSchedulerService.php` |
| Feed controller | `app/Http/Controllers/FeedController.php` |
| Blade - panel | `resources/views/livewire/admin/export/export-manager.blade.php` |
| Blade - wizard | `resources/views/livewire/admin/export/export-profile-form.blade.php` |
