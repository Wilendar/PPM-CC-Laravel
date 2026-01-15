# PLAN: Integracja CSS/JS PrestaShop z PPM

**Data:** 2025-12-16
**Cel:** Opis wizualny w PPM = Opis na stronie sklepu PrestaShop
**Autor:** Architect Agent

## ANALIZA STANU OBECNEGO

### PrestaShop - Struktura CSS
```
themes/warehouse/assets/css/custom.css  <- GŁÓWNY PLIK (klasy pd-*, grid-row, bg-brand)
modules/iqitthemeeditor/views/css/custom_s_1.css  <- Theme Editor (per shop)
```

### Klasy CSS używane w opisach produktów
| Klasa | Typ | Opis |
|-------|-----|------|
| `pd-base-grid` | Layout | Główna siatka opisu |
| `pd-intro`, `pd-intro__heading`, `pd-intro__text` | Content | Sekcja wstępna |
| `pd-cover`, `pd-cover__picture` | Media | Zdjęcie główne |
| `pd-block`, `pd-block__heading` | Section | Blok sekcji |
| `pd-slider`, `pd-slide` | Interactive | Slider Splide |
| `pd-pseudo-parallax` | Effect | Efekt parallax |
| `pd-asset-list` | List | Lista cech |
| `grid-row` | Layout | Full-width row |
| `bg-brand`, `bg-neutral-accent` | Color | Kolory tła |

### PPM - Stan obecny
- `CssDeploymentService` - generuje CSS lokalnie, może deployować przez FTP
- `ShopStyleset` - konfiguracja stylów per sklep
- **BRAK:** Odczytu CSS z PrestaShop
- **BRAK:** Podglądu opisu z rzeczywistym CSS PrestaShop

## ARCHITEKTURA ROZWIĄZANIA

### FAZA 1: CSS URL Sync (Szybkie wdrożenie)
**Cel:** Pobieranie CSS z PrestaShop przez URL

```
PrestaShop                           PPM
┌──────────────────┐               ┌──────────────────┐
│ custom.css       │──── URL ────>│ CssFetcher       │
│ (publiczny URL)  │               │ ↓                │
└──────────────────┘               │ Cache (1h)       │
                                   │ ↓                │
                                   │ Podgląd opisu    │
                                   └──────────────────┘
```

**Zmiany:**
1. Nowe pole w `prestashop_shops`: `custom_css_url`
2. Nowy serwis: `PrestaShopCssFetcher`
3. Aktualizacja podglądu opisu w `VisualDescriptionEditor`

### FAZA 2: FTP/SFTP Integration (Pełna kontrola)
**Cel:** Odczyt i zapis CSS przez FTP/SFTP

```
PPM                                  PrestaShop Server
┌──────────────────┐               ┌──────────────────┐
│ CssDeployment    │               │ /themes/.../css/ │
│ Service          │               │                  │
│                  │◄──── FTP ────►│ custom.css       │
│ - Read CSS       │               │ custom.js        │
│ - Write CSS      │               └──────────────────┘
│ - Backup         │
└──────────────────┘
```

**Zmiany:**
1. Nowe pola w `prestashop_shops`:
   - `ftp_host`, `ftp_port`, `ftp_user`, `ftp_password` (encrypted)
   - `ftp_path_css`, `ftp_path_js`
2. Rozszerzenie `CssDeploymentService`:
   - `fetchFromPrestaShop()`
   - `uploadToPrestaShop()`
   - `backupRemoteCss()`
3. UI: Panel CSS/JS w konfiguracji sklepu

## IMPLEMENTACJA - FAZA 1

### 1. Migracja bazy danych
```php
// 2025_12_16_000001_add_css_sync_to_prestashop_shops.php
Schema::table('prestashop_shops', function (Blueprint $table) {
    // CSS URL Sync
    $table->string('custom_css_url')->nullable()->after('sync_settings');
    $table->string('custom_js_url')->nullable()->after('custom_css_url');
    $table->timestamp('css_last_fetched_at')->nullable();
    $table->text('cached_custom_css')->nullable();

    // FTP Configuration (Faza 2)
    $table->json('ftp_config')->nullable()->after('cached_custom_css');
});
```

### 2. PrestaShopCssFetcher Service
```php
class PrestaShopCssFetcher
{
    public function fetchCss(PrestaShopShop $shop): ?string
    {
        if (!$shop->custom_css_url) return null;

        // Check cache (1 hour)
        if ($shop->css_last_fetched_at?->diffInMinutes(now()) < 60) {
            return $shop->cached_custom_css;
        }

        // Fetch from URL
        $response = Http::timeout(10)->get($shop->custom_css_url);
        if (!$response->successful()) return null;

        // Update cache
        $shop->update([
            'cached_custom_css' => $response->body(),
            'css_last_fetched_at' => now(),
        ]);

        return $response->body();
    }

    public function refreshCache(PrestaShopShop $shop): bool
    {
        $shop->update(['css_last_fetched_at' => null]);
        return $this->fetchCss($shop) !== null;
    }
}
```

### 3. Podgląd opisu z CSS
```blade
{{-- W VisualDescriptionEditor lub ProductForm --}}
<div class="description-preview">
    <style>
        /* Izolowany scope dla podglądu */
        .ps-preview-scope {
            {{ $prestashopCss }}
        }
    </style>
    <div class="ps-preview-scope">
        {!! $description_html !!}
    </div>
</div>
```

### 4. Formularz konfiguracji sklepu
```blade
{{-- W formularzu dodawania/edycji sklepu --}}
<div class="card">
    <h3>Synchronizacja CSS/JS</h3>

    <x-input
        label="URL do custom.css"
        name="custom_css_url"
        placeholder="https://sklep.pl/themes/theme/assets/css/custom.css"
        helper="Plik CSS będzie pobierany i cachowany (odświeżanie co 1h)"
    />

    <x-input
        label="URL do custom.js"
        name="custom_js_url"
        placeholder="https://sklep.pl/themes/theme/assets/js/custom.js"
    />

    <button type="button" wire:click="refreshCssCache">
        Odśwież cache CSS
    </button>
</div>
```

## IMPLEMENTACJA - FAZA 2

### 1. Konfiguracja FTP w formularzu sklepu
```blade
<div class="card" x-show="enableFtpSync">
    <h3>Konfiguracja FTP/SFTP</h3>

    <x-select label="Protokół" name="ftp_config.protocol">
        <option value="ftp">FTP</option>
        <option value="sftp">SFTP</option>
    </x-select>

    <x-input label="Host" name="ftp_config.host" />
    <x-input label="Port" name="ftp_config.port" type="number" />
    <x-input label="Użytkownik" name="ftp_config.user" />
    <x-input label="Hasło" name="ftp_config.password" type="password" />

    <x-input
        label="Ścieżka do custom.css"
        name="ftp_config.css_path"
        placeholder="/themes/theme-name/assets/css/custom.css"
    />

    <button type="button" wire:click="testFtpConnection">
        Testuj połączenie
    </button>
</div>
```

### 2. Rozszerzenie CssDeploymentService
```php
public function fetchFromFtp(PrestaShopShop $shop): ?string
{
    $config = $shop->ftp_config;
    if (!$config) return null;

    return match($config['protocol']) {
        'ftp' => $this->ftpRead($config),
        'sftp' => $this->sftpRead($config),
        default => null,
    };
}

public function uploadToFtp(PrestaShopShop $shop, string $css): bool
{
    $config = $shop->ftp_config;
    if (!$config) return false;

    // Backup first
    $this->backupRemoteCss($shop);

    return match($config['protocol']) {
        'ftp' => $this->ftpWrite($config, $css),
        'sftp' => $this->sftpWrite($config, $css),
        default => false,
    };
}
```

## PLAN WDROŻENIA

### Tydzień 1: Faza 1 - CSS URL Sync
| # | Zadanie | Czas |
|---|---------|------|
| 1.1 | Migracja DB (custom_css_url, cached_custom_css) | 1h |
| 1.2 | PrestaShopCssFetcher service | 2h |
| 1.3 | UI w formularzu sklepu | 2h |
| 1.4 | Podgląd opisu z CSS w ProductForm | 3h |
| 1.5 | Testy + deploy | 2h |

### Tydzień 2: Faza 2 - FTP Integration
| # | Zadanie | Czas |
|---|---------|------|
| 2.1 | Migracja DB (ftp_config) | 1h |
| 2.2 | FTP/SFTP connection w CssDeploymentService | 4h |
| 2.3 | UI konfiguracji FTP | 3h |
| 2.4 | Panel edycji CSS w PPM | 4h |
| 2.5 | Backup + versioning | 2h |
| 2.6 | Testy + deploy | 2h |

## KONFIGURACJA DLA KAYO TEST

Po wdrożeniu Fazy 1, należy skonfigurować:

```
Sklep: KAYO TEST
URL: https://test.kayomoto.pl
Custom CSS URL: https://test.kayomoto.pl/themes/warehouse/assets/css/custom.css
```

## OCZEKIWANY REZULTAT

Po pełnym wdrożeniu:
1. PPM automatycznie pobiera CSS z PrestaShop
2. Podgląd opisu w PPM wygląda identycznie jak na PrestaShop
3. (Faza 2) Możliwość edycji CSS z PPM i deploy do PrestaShop

## ALTERNATYWA: Iframe Preview

Jeśli synchronizacja CSS okaże się niewystarczająca (brakuje JS, fontów, itp.):

```blade
<iframe
    src="{{ $shop->url }}/module/ppmpreview/product?id={{ $product->ps_id }}&token={{ $previewToken }}"
    class="description-preview-iframe"
></iframe>
```

Wymaga dedykowanego modułu PrestaShop `ppmpreview`.
