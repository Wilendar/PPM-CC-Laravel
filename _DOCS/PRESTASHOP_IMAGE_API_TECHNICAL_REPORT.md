# PrestaShop Image API - Raport Techniczny dla PPM

**Data:** 2025-11-28
**Autor:** PrestaShop API Expert Agent
**Przeznaczenie:** System Media Management w PPM-CC-Laravel

---

## Spis Treści

1. [Executive Summary](#executive-summary)
2. [API Endpoints - Struktura](#api-endpoints---struktura)
3. [Operacje na Obrazach](#operacje-na-obrazach)
4. [Struktura Bazy Danych](#struktura-bazy-danych)
5. [Multi-Store Considerations](#multi-store-considerations)
6. [Formaty i Limity](#formaty-i-limity)
7. [Best Practices & Implementacja](#best-practices--implementacja)
8. [Potencjalne Problemy i Rozwiązania](#potencjalne-problemy-i-rozwiązania)
9. [Rekomendacje dla PPM](#rekomendacje-dla-ppm)

---

## Executive Summary

PrestaShop API dla zarządzania obrazami produktów oferuje podstawowe operacje CRUD poprzez REST endpoint `/api/images/products/{id}`. System obsługuje:

- ✅ **Upload obrazów** - POST multipart/form-data
- ✅ **Pobieranie obrazów** - GET z image types
- ✅ **Usuwanie obrazów** - DELETE
- ✅ **Aktualizacja** - PUT (cover designation)
- ⚠️ **Multi-store** - Brak dedykowanej tabeli `ps_image_shop` (obrazy wspólne dla wszystkich sklepów)

**KLUCZOWA INFORMACJA:** PrestaShop 8.x/9.x **NIE** posiada tabeli `ps_image_shop`, co oznacza, że obrazy produktów są **wspólne dla wszystkich sklepów** w multi-store setup. Nie można mieć różnych obrazów per shop!

---

## API Endpoints - Struktura

### 1. Główne Endpointy

```
GET    /api/images                        # Lista wszystkich typów obrazów
GET    /api/images/products               # Lista obrazów produktów
GET    /api/images/products/{product_id}  # Obrazy konkretnego produktu
POST   /api/images/products/{product_id}  # Upload nowego obrazu
PUT    /api/images/products/{product_id}  # Aktualizacja (wymaga ?ps_method=PUT)
DELETE /api/images/products/{product_id}/{image_id}  # Usunięcie obrazu
```

### 2. Inne Typy Obrazów

```
/api/images/general          # Ogólne obrazy sklepu
/api/images/categories       # Obrazy kategorii
/api/images/manufacturers    # Obrazy producentów
/api/images/suppliers        # Obrazy dostawców
/api/images/stores           # Obrazy sklepów
/api/images/customizations   # Obrazy personalizacji
```

### 3. Parametry Query

```
?schema=blank     # Pusty szablon XML
?schema=synopsis  # Szczegółowy schemat z opisami pól
?ps_method=PUT    # Symulacja PUT request (dla serverów bez PUT support)
```

---

## Operacje na Obrazach

### 1. POST - Upload Nowego Obrazu

**Endpoint:** `POST /api/images/products/{product_id}`

**Request:**
```http
POST /api/images/products/10 HTTP/1.1
Host: example.com
Authorization: Basic [base64(API_KEY:)]
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="image"; filename="product.jpg"
Content-Type: image/jpeg

[Binary image data]
------WebKitFormBoundary--
```

**PHP Implementation (Laravel):**
```php
use Illuminate\Support\Facades\Http;

public function uploadProductImage(int $prestashopProductId, string $imagePath): array
{
    $url = "{$this->shopUrl}/api/images/products/{$prestashopProductId}";

    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
    ])
    ->attach('image', file_get_contents($imagePath), basename($imagePath))
    ->timeout(60) // Image upload może trwać dłużej
    ->retry(2, 2000)
    ->post($url);

    if (!$response->successful()) {
        throw new PrestaShopAPIException(
            "Image upload failed: {$response->status()} - {$response->body()}",
            $response->status()
        );
    }

    return $response->json();
}
```

**cURL Example:**
```php
<?php
$urlImage = 'http://example.com/api/images/products/10/';
$key = 'ZR92FNY5UFRERNI3O9Z5QDHWKTP3YIIT';
$image_path = '/path/to/image.jpg';
$image_mime = 'image/jpg';

$args['image'] = new CurlFile($image_path, $image_mime);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlImage);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, $key . ':');
curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (200 == $httpCode) {
    echo 'Image uploaded successfully';
}
```

**Response (Success - HTTP 200):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <image>
        <id>123</id>
        <id_product>10</id_product>
        <position>1</position>
        <cover>0</cover>
    </image>
</prestashop>
```

**KLUCZOWE INFORMACJE:**
- ✅ Tylko **JEDEN** obraz per request
- ✅ Obraz automatycznie resize'owany do wszystkich image types
- ✅ Position automatycznie przypisywany (ostatni + 1)
- ✅ Cover domyślnie `0` (false)

---

### 2. GET - Pobieranie Obrazów Produktu

**Endpoint:** `GET /api/images/products/{product_id}`

**Request:**
```http
GET /api/images/products/10 HTTP/1.1
Host: example.com
Authorization: Basic [base64(API_KEY:)]
Accept: application/json
```

**PHP Implementation:**
```php
public function getProductImages(int $prestashopProductId): array
{
    $url = "{$this->shopUrl}/api/images/products/{$prestashopProductId}";

    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
        'Accept' => 'application/json'
    ])
    ->timeout(30)
    ->get($url);

    if (!$response->successful()) {
        throw new PrestaShopAPIException(
            "Failed to get images: {$response->status()}",
            $response->status()
        );
    }

    // Parse XML response
    $xml = simplexml_load_string($response->body());
    $images = [];

    foreach ($xml->image as $image) {
        $images[] = [
            'id' => (int) $image->id,
            'position' => (int) $image->position,
            'cover' => (bool) $image->cover,
        ];
    }

    return $images;
}
```

**Response (XML):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <images>
        <image id="123" xlink:href="http://example.com/api/images/products/10/123">
            <id>123</id>
            <id_product>10</id_product>
            <position>1</position>
            <cover>1</cover>
        </image>
        <image id="124" xlink:href="http://example.com/api/images/products/10/124">
            <id>124</id>
            <id_product>10</id_product>
            <position>2</position>
            <cover>0</cover>
        </image>
    </images>
</prestashop>
```

**Pobieranie konkretnego image type:**
```http
GET /api/images/products/10/123/small_default HTTP/1.1
```

Zwraca binarny obraz w wybranym rozmiarze.

---

### 3. PUT - Aktualizacja (Cover Designation)

**UWAGA:** PrestaShop API wymaga `?ps_method=PUT` w URL dla serverów bez natywnego PUT support.

**Endpoint:** `PUT /api/images/products/{product_id}/{image_id}?ps_method=PUT`

**Use Case:** Ustawienie obrazu jako cover (główne zdjęcie produktu)

**PHP Implementation:**
```php
public function setImageAsCover(int $prestashopProductId, int $imageId): bool
{
    // Najpierw pobierz XML obrazu
    $url = "{$this->shopUrl}/api/images/products/{$prestashopProductId}/{$imageId}";
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
    ])->get($url);

    $xml = simplexml_load_string($response->body());

    // Ustaw cover = 1
    $xml->image->cover = 1;

    // PUT request
    $updateUrl = "{$url}?ps_method=PUT";
    $updateResponse = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
        'Content-Type' => 'application/xml',
    ])
    ->withBody($xml->asXML(), 'application/xml')
    ->post($updateUrl); // POST z ps_method=PUT

    return $updateResponse->successful();
}
```

**WAŻNE:**
- ⚠️ Tylko **JEDEN** obraz może mieć `cover = 1` per produkt
- ⚠️ PrestaShop automatycznie ustawia poprzedni cover na `0`
- ⚠️ Wymaga pełnego XML (GET → modify → PUT)

---

### 4. DELETE - Usuwanie Obrazu

**Endpoint:** `DELETE /api/images/products/{product_id}/{image_id}`

**Request:**
```http
DELETE /api/images/products/10/123 HTTP/1.1
Host: example.com
Authorization: Basic [base64(API_KEY:)]
```

**PHP Implementation:**
```php
public function deleteProductImage(int $prestashopProductId, int $imageId): bool
{
    $url = "{$this->shopUrl}/api/images/products/{$prestashopProductId}/{$imageId}";

    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
    ])
    ->timeout(30)
    ->delete($url);

    if (!$response->successful()) {
        // 404 = obraz już nie istnieje (traktujemy jako sukces)
        if ($response->status() === 404) {
            return true;
        }

        throw new PrestaShopAPIException(
            "Failed to delete image: {$response->status()}",
            $response->status()
        );
    }

    return true;
}
```

**Response (Success - HTTP 200):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"/>
```

**UWAGI:**
- ✅ Usunięcie cover automatycznie ustawia inny obraz jako cover
- ✅ Wszystkie image types (thumbnails) usuwane automatycznie
- ⚠️ Operacja nieodwracalna!

---

## Struktura Bazy Danych

### 1. Tabela `ps_image` (Główna Tabela Obrazów)

```sql
CREATE TABLE `ps_image` (
  `id_image` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_product` int(10) unsigned NOT NULL,
  `position` smallint(2) unsigned NOT NULL DEFAULT '0',
  `cover` tinyint(1) unsigned NULL DEFAULT NULL,
  PRIMARY KEY (`id_image`),
  KEY `image_product` (`id_product`),
  UNIQUE KEY `id_product_cover` (`id_product`, `cover`),
  UNIQUE KEY `idx_product_image` (`id_image`, `id_product`, `cover`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolumny:**
- `id_image` - Unique ID obrazu (AUTO_INCREMENT)
- `id_product` - Foreign key do `ps_product`
- `position` - Pozycja w galerii (1, 2, 3...)
- `cover` - Czy to główne zdjęcie? (NULL lub 1, NIGDY 0!)

**KLUCZOWA INFORMACJA - Constraint `cover`:**
```sql
UNIQUE KEY `id_product_cover` (`id_product`, `cover`)
```

To oznacza:
- ✅ Tylko **JEDEN** obraz może mieć `cover = 1` per produkt
- ✅ Wszystkie inne obrazy mają `cover = NULL` (nie `0`!)
- ⚠️ Próba ustawienia `cover = 1` na drugim obrazie = MySQL UNIQUE constraint violation

---

### 2. Tabela `ps_image_lang` (Tłumaczenia - Legend)

```sql
CREATE TABLE `ps_image_lang` (
  `id_image` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `legend` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id_image`, `id_lang`),
  KEY `id_image` (`id_image`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Zastosowanie:**
- `legend` - Alt text / opis obrazu w różnych językach
- SEO optimization (alt attribute)

---

### 3. Tabela `ps_image_type` (Definicje Rozmiarów)

```sql
CREATE TABLE `ps_image_type` (
  `id_image_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `width` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `products` tinyint(1) NOT NULL DEFAULT '1',
  `categories` tinyint(1) NOT NULL DEFAULT '1',
  `manufacturers` tinyint(1) NOT NULL DEFAULT '1',
  `suppliers` tinyint(1) NOT NULL DEFAULT '1',
  `stores` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_image_type`),
  KEY `image_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Domyślne Image Types:**
```yaml
cart_default:       80x80    # Koszyk
small_default:      125x125  # Miniaturka
medium_default:     300x300  # Średni rozmiar
large_default:      500x500  # Duży rozmiar
home_default:       250x250  # Strona główna
thickbox_default:   800x800  # Lightbox/zoom
category_default:   960x350  # Kategorie
```

**Automatyczne Resize:**
- Upload jednego obrazu = automatyczny resize do **wszystkich** typów
- Przechowywane w `img/p/{id_image_structure}/`

---

### 4. ⚠️ BRAK Tabeli `ps_image_shop` (Multi-Store)

**KRYTYCZNA INFORMACJA:** PrestaShop 8.x/9.x **NIE** posiada dedykowanej tabeli `ps_image_shop`!

**Konsekwencje:**
- ❌ Obrazy produktów są **wspólne dla wszystkich sklepów**
- ❌ Nie można mieć różnych obrazów per shop
- ✅ Oszczędność miejsca (jeden zestaw obrazów)
- ⚠️ Ograniczenie dla multi-store z różnymi rynkami

**Workaround dla PPM:**
Jeśli potrzebujemy różnych obrazów per shop:
1. Rozwiązanie A: Osobne produkty per shop
2. Rozwiązanie B: Warianty produktu z różnymi obrazami
3. Rozwiązanie C: Custom module z własną logiką

---

## Multi-Store Considerations

### 1. Aktualny Stan (PrestaShop 8.x/9.x)

**Obrazy są GLOBALNE:**
```
Product ID: 10
├── Image ID: 123 (cover) ──► Widoczny we WSZYSTKICH sklepach
├── Image ID: 124          ──► Widoczny we WSZYSTKICH sklepach
└── Image ID: 125          ──► Widoczny we WSZYSTKICH sklepach
```

**Brak możliwości:**
- ❌ Shop A: obraz_PL.jpg
- ❌ Shop B: obraz_EN.jpg
- ❌ Shop C: obraz_DE.jpg

**Możliwe rozwiązania:**

#### Opcja 1: Legend per Language (Частичное Rozwiązanie)
```php
// Różne alt texts per język, ale TE SAME obrazy
ps_image_lang:
  - id_image: 123, id_lang: 1 (PL), legend: "Produkt - widok z przodu"
  - id_image: 123, id_lang: 2 (EN), legend: "Product - front view"
```

#### Opcja 2: Custom Module (Pełna Kontrola)
Stworzenie własnego modułu, który:
1. Dodaje tabelę `ps_image_shop_custom`
2. Overrides PrestaShop image rendering
3. Wymaga maintenance przy update PrestaShop

#### Opcja 3: Separate Products per Shop (Rekomendowane dla PPM)
```php
// PPM Strategy
Product (PPM): SKU-12345
├── PrestaShop Shop A: product_id = 100 (własne obrazy)
├── PrestaShop Shop B: product_id = 200 (własne obrazy)
└── PrestaShop Shop C: product_id = 300 (własne obrazy)

// Mapping w PPM
product_sync_status:
  - product_id: 1, shop_id: 1, prestashop_product_id: 100
  - product_id: 1, shop_id: 2, prestashop_product_id: 200
  - product_id: 1, shop_id: 3, prestashop_product_id: 300
```

**Zalety Opcji 3:**
- ✅ Pełna kontrola nad obrazami per shop
- ✅ Różne opisy, ceny, stock per shop
- ✅ Nie modyfikuje PrestaShop core
- ⚠️ Więcej rekordów w PrestaShop DB

---

### 2. Strategia dla PPM Media System

**Rekomendowane podejście:**

```php
// Model Media w PPM
Media {
    id: 1,
    product_id: 10,
    file_path: 'products/SKU-12345/image1.jpg',
    is_cover: true,
    position: 1,

    // Per-shop mapping
    prestashop_mapping: [
        {
            shop_id: 1,
            prestashop_product_id: 100,
            prestashop_image_id: 123,
            synced_at: '2025-11-28 10:00:00',
            checksum: 'abc123...'
        },
        {
            shop_id: 2,
            prestashop_product_id: 200,
            prestashop_image_id: 456,
            synced_at: '2025-11-28 10:05:00',
            checksum: 'abc123...'
        }
    ]
}
```

**Workflow:**
1. User uploada obraz w PPM
2. PPM zapisuje w `storage/app/products/{SKU}/`
3. Sync service uploaduje do każdego PrestaShop shop osobno
4. Mapping zapisywany w `prestashop_mapping` JSON
5. Checksum do wykrywania zmian

---

## Formaty i Limity

### 1. Dozwolone MIME Types

**Oficjalnie Wspierane:**
```php
$allowedMimeTypes = [
    'image/gif',
    'image/jpg',
    'image/jpeg',
    'image/pjpeg',  // Progressive JPEG
    'image/png',
    'image/x-png',
];
```

**UWAGA:** WebP oficjalnie **NIE** wspierany przez API (może działać w nowszych wersjach, wymaga testów)

---

### 2. Maksymalne Rozmiary

**PHP Settings (server-side):**
```ini
; php.ini
upload_max_filesize = 64M      # Max single file
post_max_size = 64M            # Max POST request
max_execution_time = 300       # 5 minut dla dużych obrazów
memory_limit = 256M            # Resize wymaga pamięci
```

**PrestaShop Settings (admin panel):**
```
Advanced Parameters → Performance → Upload Quota
- Maximum size for a product's image: [X] MB
```

**Rekomendacje dla PPM:**
```php
// config/prestashop.php
return [
    'image' => [
        'max_size_mb' => 8,  // 8MB max per image
        'max_dimension' => 4000, // 4000x4000px max
        'timeout' => 60, // 60s timeout dla upload
        'chunk_size' => 1024 * 1024, // 1MB chunks dla dużych obrazów
    ],
];
```

---

### 3. Limity Ilościowe

**Maximum Images per Product:**
- ⚠️ Brak oficjalnego limitu API
- ✅ Praktyczny limit: ~100 obrazów (performance considerations)
- 📁 Filesystem limit: ~9,999,999 obrazów total (`.htaccess` mod_rewrite limit)

**Reference:**
> "The limitation of the .htaccess file prevents the use of more than 9,999,999 images because there is a limit of 9 back references (2, 9) in mod_rewrite."

---

### 4. Image Storage Structure

PrestaShop zapisuje obrazy w strukturze:
```
img/p/
├── 1/                          # id_image = 1
│   └── 1.jpg
├── 2/                          # id_image = 2-9
│   └── 2.jpg
├── 1/0/                        # id_image = 10-99
│   └── 10.jpg
├── 1/0/0/                      # id_image = 100-999
│   └── 100.jpg
└── 1/2/3/                      # id_image = 123
    ├── 123.jpg                 # Original
    ├── 123-cart_default.jpg
    ├── 123-small_default.jpg
    ├── 123-medium_default.jpg
    ├── 123-large_default.jpg
    └── 123-thickbox_default.jpg
```

**Algorytm:**
```php
// id_image = 123
str_split('123') = ['1', '2', '3']
Path = "img/p/1/2/3/123.jpg"

// id_image = 1
Path = "img/p/1/1.jpg"
```

---

## Best Practices & Implementacja

### 1. Upload Optimization

**A) Image Validation PRZED Upload**
```php
use Intervention\Image\Facades\Image;

public function validateImageBeforeUpload(UploadedFile $file): void
{
    // 1. MIME type check
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file->getMimeType(), $allowedMimes)) {
        throw new ValidationException('Unsupported image format');
    }

    // 2. Size check
    $maxSizeMB = config('prestashop.image.max_size_mb', 8);
    if ($file->getSize() > $maxSizeMB * 1024 * 1024) {
        throw new ValidationException("Image too large (max {$maxSizeMB}MB)");
    }

    // 3. Dimension check
    $image = Image::make($file);
    $maxDimension = config('prestashop.image.max_dimension', 4000);

    if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
        throw new ValidationException("Image dimensions too large (max {$maxDimension}px)");
    }

    // 4. Corruption check
    if (!@imagecreatefromstring(file_get_contents($file))) {
        throw new ValidationException('Corrupted image file');
    }
}
```

**B) Resize PRZED Upload (Oszczędność Bandwidth)**
```php
public function optimizeImageForUpload(UploadedFile $file): string
{
    $image = Image::make($file);

    // Resize jeśli za duży
    $maxDimension = 2000;
    if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
        $image->resize($maxDimension, $maxDimension, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }

    // Optimize quality
    $tempPath = storage_path('app/temp/' . Str::uuid() . '.jpg');
    $image->save($tempPath, 85); // 85% quality (good balance)

    return $tempPath;
}
```

---

### 2. Bulk Upload Strategy

**Problem:** Upload 50 obrazów = 50 API calls = długi czas

**Rozwiązanie: Queue + Batch Processing**
```php
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyncProductImageToPrestaShop;

public function syncProductImages(Product $product, PrestaShopShop $shop): void
{
    $images = $product->media()->orderBy('position')->get();

    // Batch wszystkie obrazy do queue
    $batch = [];
    foreach ($images as $media) {
        $batch[] = new SyncProductImageToPrestaShop($media, $shop);
    }

    // Dispatch z rate limiting
    foreach ($batch as $index => $job) {
        $job->delay(now()->addSeconds($index * 2)); // 2s między uploadami
        Queue::push($job);
    }
}
```

**Job Implementation:**
```php
class SyncProductImageToPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays

    public function __construct(
        public Media $media,
        public PrestaShopShop $shop
    ) {}

    public function handle(PrestaShopImageSyncService $syncService): void
    {
        try {
            $syncService->syncImageToShop($this->media, $this->shop);

        } catch (PrestaShopAPIException $e) {
            // Log error
            Log::error('Image sync failed', [
                'media_id' => $this->media->id,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);

            // Retry jeśli to temporary error
            if ($e->getCode() >= 500) {
                $this->release(30); // Retry za 30s
            }

            throw $e;
        }
    }
}
```

---

### 3. Rate Limiting

**PrestaShop API Limits:**
- ⚠️ Brak oficjalnego publicznego limitu
- ⚠️ Zależy od server configuration (Apache/Nginx limits)
- ✅ Rekomendacja: max 30 requests/minute (bezpieczny limit)

**Implementation:**
```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PrestaShopRateLimiter
{
    protected int $maxAttempts = 30; // requests
    protected int $decayMinutes = 1; // per minute

    public function checkLimit(PrestaShopShop $shop): void
    {
        $key = "prestashop_api_limit:{$shop->id}";

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw new RateLimitException(
                "PrestaShop API rate limit exceeded. Retry in {$seconds}s"
            );
        }

        RateLimiter::hit($key, $this->decayMinutes * 60);
    }
}
```

---

### 4. Error Handling & Retry Logic

```php
public function uploadImageWithRetry(
    int $prestashopProductId,
    string $imagePath,
    int $maxRetries = 3
): array {
    $attempt = 0;
    $lastException = null;

    while ($attempt < $maxRetries) {
        try {
            $attempt++;

            // Rate limiting check
            $this->rateLimiter->checkLimit($this->shop);

            // Actual upload
            return $this->uploadProductImage($prestashopProductId, $imagePath);

        } catch (PrestaShopAPIException $e) {
            $lastException = $e;

            // Nie retry dla client errors (4xx)
            if ($e->getCode() >= 400 && $e->getCode() < 500) {
                throw $e;
            }

            // Exponential backoff dla server errors (5xx)
            if ($attempt < $maxRetries) {
                $delay = pow(2, $attempt) * 1000; // 2s, 4s, 8s
                usleep($delay * 1000);

                Log::warning('Image upload retry', [
                    'attempt' => $attempt,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    throw $lastException ?? new PrestaShopAPIException('Upload failed');
}
```

---

### 5. Checksum & Change Detection

**Problem:** Jak wykryć, czy obraz się zmienił?

**Rozwiązanie: MD5 Checksum**
```php
public function calculateImageChecksum(Media $media): string
{
    $filePath = storage_path('app/' . $media->file_path);
    return md5_file($filePath);
}

public function shouldSyncImage(Media $media, PrestaShopShop $shop): bool
{
    // Pobierz mapping
    $mapping = collect($media->prestashop_mapping)
        ->firstWhere('shop_id', $shop->id);

    if (!$mapping) {
        return true; // Nie zsynchronizowany wcześniej
    }

    // Porównaj checksum
    $currentChecksum = $this->calculateImageChecksum($media);
    $lastChecksum = $mapping['checksum'] ?? null;

    if ($currentChecksum !== $lastChecksum) {
        Log::info('Image changed, re-sync required', [
            'media_id' => $media->id,
            'old_checksum' => $lastChecksum,
            'new_checksum' => $currentChecksum,
        ]);
        return true;
    }

    return false; // Obraz nie zmieniony
}
```

---

### 6. Cover Image Management

**Problem:** Tylko jeden obraz może być cover per produkt

**Implementation:**
```php
public function syncCoverImage(Product $product, PrestaShopShop $shop): void
{
    // 1. Znajdź cover w PPM
    $coverMedia = $product->media()
        ->where('is_cover', true)
        ->first();

    if (!$coverMedia) {
        return; // Brak cover
    }

    // 2. Pobierz prestashop_image_id z mapping
    $mapping = collect($coverMedia->prestashop_mapping)
        ->firstWhere('shop_id', $shop->id);

    if (!$mapping || !isset($mapping['prestashop_image_id'])) {
        Log::warning('Cover image not synced to PrestaShop yet');
        return;
    }

    $imageId = $mapping['prestashop_image_id'];
    $productId = $mapping['prestashop_product_id'];

    // 3. Set as cover via API
    $this->setImageAsCover($productId, $imageId);

    Log::info('Cover image synced', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'prestashop_image_id' => $imageId,
    ]);
}
```

---

## Potencjalne Problemy i Rozwiązania

### 1. Problem: Timeout podczas Upload Dużych Obrazów

**Symptom:**
```
cURL error 28: Operation timed out after 30000 milliseconds
```

**Rozwiązanie:**
```php
// A) Zwiększ timeout
Http::timeout(120) // 2 minuty

// B) Resize PRZED upload
$optimizedPath = $this->optimizeImageForUpload($file);

// C) Chunk upload (custom implementation)
// PrestaShop API nie wspiera natywnie, ale można:
$this->uploadImageInChunks($largeImagePath);
```

---

### 2. Problem: Rate Limit Exceeded

**Symptom:**
```
HTTP 429 Too Many Requests
```

**Rozwiązanie:**
```php
// A) Queue z delay
SyncProductImageToPrestaShop::dispatch($media, $shop)
    ->delay(now()->addSeconds($index * 2));

// B) Batch processing z throttling
foreach ($images as $index => $media) {
    if ($index > 0 && $index % 10 === 0) {
        sleep(60); // Pauza co 10 obrazów
    }
    $this->syncImage($media, $shop);
}

// C) Implement exponential backoff
```

---

### 3. Problem: Cover Image Conflict

**Symptom:**
```
MySQL UNIQUE constraint violation: id_product_cover
```

**Przyczyna:** Próba ustawienia `cover = 1` na drugim obrazie

**Rozwiązanie:**
```php
// KROK 1: Najpierw usuń cover z innych obrazów
$currentImages = $this->getProductImages($productId);
foreach ($currentImages as $img) {
    if ($img['cover'] && $img['id'] !== $newCoverId) {
        $this->removeCover($productId, $img['id']);
    }
}

// KROK 2: Ustaw nowy cover
$this->setImageAsCover($productId, $newCoverId);
```

---

### 4. Problem: Image Not Found po Upload

**Symptom:**
```
GET /api/images/products/10/123 → HTTP 404
```

**Możliwe Przyczyny:**
1. Upload się powiódł, ale filesystem permissions
2. PrestaShop cache
3. Incorrect image ID parsing

**Rozwiązanie:**
```php
// A) Verify upload success
$uploadResponse = $this->uploadProductImage($productId, $imagePath);
$imageId = (int) $uploadResponse['image']['id'];

// B) Wait for filesystem sync
sleep(2);

// C) Verify image exists
try {
    $this->getProductImages($productId);
} catch (PrestaShopAPIException $e) {
    if ($e->getCode() === 404) {
        // Retry upload
        Log::warning('Image not found after upload, retrying');
        return $this->uploadImageWithRetry($productId, $imagePath);
    }
}
```

---

### 5. Problem: Multi-Store Image Duplication

**Symptom:** Ten sam obraz uploadowany 3x dla 3 shopów

**Przyczyna:** Brak `ps_image_shop` - obrazy są globalne

**Rozwiązanie: Conditional Upload**
```php
public function syncImageToShops(Media $media, array $shops): void
{
    // Check if ALREADY synced to ANY shop
    $existingMapping = collect($media->prestashop_mapping)->first();

    if ($existingMapping && isset($existingMapping['prestashop_image_id'])) {
        // Obraz już istnieje w PrestaShop (globalnie)
        $imageId = $existingMapping['prestashop_image_id'];

        // Tylko update mapping dla innych shopów
        foreach ($shops as $shop) {
            $this->updateMappingWithoutUpload($media, $shop, $imageId);
        }

    } else {
        // Pierwszy upload - wybierz JEDEN shop
        $firstShop = $shops[0];
        $imageId = $this->uploadImage($media, $firstShop);

        // Update mapping dla wszystkich shopów
        foreach ($shops as $shop) {
            $this->updateMapping($media, $shop, $imageId);
        }
    }
}
```

---

### 6. Problem: Corrupted Image after Upload

**Symptom:** Obraz wyświetla się jako broken/corrupted

**Przyczyny:**
1. Nieprawidłowy MIME type
2. Binary corruption podczas transfer
3. PrestaShop resize failure

**Rozwiązanie:**
```php
// A) Validate PRZED upload
if (!@imagecreatefromstring(file_get_contents($imagePath))) {
    throw new ValidationException('Corrupted image');
}

// B) Re-encode to ensure clean format
$image = Image::make($imagePath);
$cleanPath = storage_path('app/temp/clean_' . basename($imagePath));
$image->save($cleanPath, 90, 'jpg'); // Force JPEG

// C) Upload clean version
$this->uploadProductImage($productId, $cleanPath);

// D) Cleanup
unlink($cleanPath);
```

---

### 7. Problem: Slow Sync for Products with Many Images

**Symptom:** Sync 100 produktów x 5 obrazów = 500 API calls = 30+ minut

**Rozwiązanie: Parallel Processing**
```php
use Illuminate\Support\Facades\Bus;

public function syncAllProductsImages(Collection $products, PrestaShopShop $shop): void
{
    $batches = $products->chunk(10); // 10 produktów per batch

    foreach ($batches as $batchIndex => $batch) {
        $jobs = $batch->map(function ($product) use ($shop) {
            return new SyncProductImagesToPrestaShop($product, $shop);
        });

        Bus::batch($jobs)
            ->then(function () use ($batchIndex) {
                Log::info("Batch {$batchIndex} completed");
            })
            ->catch(function ($batch, $e) {
                Log::error('Batch failed', ['error' => $e->getMessage()]);
            })
            ->dispatch();

        // Delay między batches (rate limiting)
        if ($batchIndex > 0) {
            sleep(60); // 1 min między batches
        }
    }
}
```

---

## Rekomendacje dla PPM

### 1. Architektura Sync Service

```php
namespace App\Services\PrestaShop\Sync;

use App\Models\Media;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageSyncStrategy implements ISyncStrategy
{
    public function __construct(
        protected PrestaShopClientFactory $clientFactory,
        protected PrestaShopRateLimiter $rateLimiter
    ) {}

    public function syncToPrestaShop(Media $media, PrestaShopShop $shop): bool
    {
        try {
            DB::beginTransaction();

            // 1. Validate image exists
            $filePath = storage_path('app/' . $media->file_path);
            if (!file_exists($filePath)) {
                throw new \Exception("Image file not found: {$filePath}");
            }

            // 2. Check if needs sync (checksum comparison)
            if (!$this->shouldSyncImage($media, $shop)) {
                Log::info('Image already synced, skipping', [
                    'media_id' => $media->id,
                    'shop_id' => $shop->id,
                ]);
                return true;
            }

            // 3. Get PrestaShop product ID
            $productId = $this->getPrestaShopProductId($media->product, $shop);
            if (!$productId) {
                throw new \Exception('Product not synced to PrestaShop yet');
            }

            // 4. Optimize image
            $optimizedPath = $this->optimizeImageForUpload($filePath);

            // 5. Rate limiting check
            $this->rateLimiter->checkLimit($shop);

            // 6. Upload to PrestaShop
            $client = $this->clientFactory->create($shop);
            $response = $client->uploadProductImage($productId, $optimizedPath);

            $prestashopImageId = (int) $response['image']['id'];

            // 7. Update mapping
            $this->updateMapping($media, $shop, $productId, $prestashopImageId);

            // 8. Set as cover if needed
            if ($media->is_cover) {
                $client->setImageAsCover($productId, $prestashopImageId);
            }

            // 9. Cleanup
            if ($optimizedPath !== $filePath) {
                unlink($optimizedPath);
            }

            DB::commit();

            Log::info('Image synced successfully', [
                'media_id' => $media->id,
                'shop_id' => $shop->id,
                'prestashop_image_id' => $prestashopImageId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Image sync failed', [
                'media_id' => $media->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function updateMapping(
        Media $media,
        PrestaShopShop $shop,
        int $productId,
        int $imageId
    ): void {
        $mapping = collect($media->prestashop_mapping ?? []);

        // Update lub add mapping dla tego shopu
        $updated = $mapping->map(function ($item) use ($shop, $productId, $imageId, $media) {
            if ($item['shop_id'] === $shop->id) {
                return [
                    'shop_id' => $shop->id,
                    'prestashop_product_id' => $productId,
                    'prestashop_image_id' => $imageId,
                    'synced_at' => now()->toIso8601String(),
                    'checksum' => $this->calculateImageChecksum($media),
                ];
            }
            return $item;
        });

        // Jeśli nie znaleziono, dodaj nowy
        if (!$updated->contains('shop_id', $shop->id)) {
            $updated->push([
                'shop_id' => $shop->id,
                'prestashop_product_id' => $productId,
                'prestashop_image_id' => $imageId,
                'synced_at' => now()->toIso8601String(),
                'checksum' => $this->calculateImageChecksum($media),
            ]);
        }

        $media->update(['prestashop_mapping' => $updated->all()]);
    }

    protected function shouldSyncImage(Media $media, PrestaShopShop $shop): bool
    {
        $mapping = collect($media->prestashop_mapping ?? [])
            ->firstWhere('shop_id', $shop->id);

        if (!$mapping) {
            return true; // Nigdy nie zsynchronizowany
        }

        // Porównaj checksum
        $currentChecksum = $this->calculateImageChecksum($media);
        return $currentChecksum !== ($mapping['checksum'] ?? null);
    }

    protected function calculateImageChecksum(Media $media): string
    {
        $filePath = storage_path('app/' . $media->file_path);
        return md5_file($filePath);
    }

    protected function optimizeImageForUpload(string $filePath): string
    {
        $image = \Intervention\Image\Facades\Image::make($filePath);

        // Resize jeśli za duży
        $maxDimension = config('prestashop.image.max_dimension', 2000);
        if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
            $image->resize($maxDimension, $maxDimension, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Save optimized
        $tempPath = storage_path('app/temp/' . \Str::uuid() . '.jpg');
        $image->save($tempPath, 85);

        return $tempPath;
    }

    protected function getPrestaShopProductId(Product $product, PrestaShopShop $shop): ?int
    {
        $syncStatus = ProductSyncStatus::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        return $syncStatus?->prestashop_product_id;
    }
}
```

---

### 2. Database Migration dla Media Model

```php
// database/migrations/2025_11_28_000000_add_prestashop_mapping_to_media.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->json('prestashop_mapping')->nullable()->after('file_path');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('prestashop_mapping');
        });
    }
};
```

**Struktura `prestashop_mapping` JSON:**
```json
[
    {
        "shop_id": 1,
        "prestashop_product_id": 100,
        "prestashop_image_id": 123,
        "synced_at": "2025-11-28T10:00:00+00:00",
        "checksum": "abc123def456..."
    },
    {
        "shop_id": 2,
        "prestashop_product_id": 200,
        "prestashop_image_id": 456,
        "synced_at": "2025-11-28T10:05:00+00:00",
        "checksum": "abc123def456..."
    }
]
```

---

### 3. Configuration

```php
// config/prestashop.php

return [
    'image' => [
        // Upload limits
        'max_size_mb' => env('PRESTASHOP_IMAGE_MAX_SIZE_MB', 8),
        'max_dimension' => env('PRESTASHOP_IMAGE_MAX_DIMENSION', 2000),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/gif'],

        // Optimization
        'optimize_before_upload' => env('PRESTASHOP_IMAGE_OPTIMIZE', true),
        'jpeg_quality' => env('PRESTASHOP_IMAGE_QUALITY', 85),

        // Sync behavior
        'timeout' => env('PRESTASHOP_IMAGE_TIMEOUT', 60),
        'retry_attempts' => env('PRESTASHOP_IMAGE_RETRY', 3),
        'retry_delay' => env('PRESTASHOP_IMAGE_RETRY_DELAY', 5), // seconds

        // Rate limiting
        'rate_limit_requests' => env('PRESTASHOP_RATE_LIMIT_REQUESTS', 30),
        'rate_limit_minutes' => env('PRESTASHOP_RATE_LIMIT_MINUTES', 1),

        // Queue
        'use_queue' => env('PRESTASHOP_IMAGE_QUEUE', true),
        'queue_delay' => env('PRESTASHOP_IMAGE_QUEUE_DELAY', 2), // seconds between jobs
    ],
];
```

---

### 4. Livewire Component dla Upload UI

```php
// app/Http/Livewire/Products/Management/ImageGallery.php

namespace App\Http\Livewire\Products\Management;

use App\Models\Media;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ImageGallery extends Component
{
    use WithFileUploads;

    public Product $product;
    public $newImages = [];
    public $uploadProgress = 0;

    protected $rules = [
        'newImages.*' => 'image|max:8192|mimes:jpg,jpeg,png,gif',
    ];

    public function updatedNewImages()
    {
        $this->validate();

        foreach ($this->newImages as $image) {
            // Store locally
            $path = $image->store("products/{$this->product->sku}", 'local');

            // Create Media record
            $media = Media::create([
                'product_id' => $this->product->id,
                'file_path' => $path,
                'mime_type' => $image->getMimeType(),
                'size' => $image->getSize(),
                'position' => $this->product->media()->max('position') + 1,
                'is_cover' => $this->product->media()->count() === 0,
            ]);

            // Queue sync to PrestaShop (jeśli product już zsynchronizowany)
            if ($this->product->isSyncedToAnyShop()) {
                $this->queueImageSync($media);
            }
        }

        $this->newImages = [];
        $this->emit('imagesUpdated');
    }

    protected function queueImageSync(Media $media): void
    {
        $shops = $this->product->syncedShops();

        foreach ($shops as $shop) {
            \App\Jobs\SyncProductImageToPrestaShop::dispatch($media, $shop)
                ->delay(now()->addSeconds(2));
        }
    }

    public function setAsCover(int $mediaId): void
    {
        DB::transaction(function () use ($mediaId) {
            // Remove cover from all images
            $this->product->media()->update(['is_cover' => false]);

            // Set new cover
            $media = $this->product->media()->findOrFail($mediaId);
            $media->update(['is_cover' => true]);

            // Sync to PrestaShop
            $this->queueImageSync($media);
        });
    }

    public function deleteImage(int $mediaId): void
    {
        $media = $this->product->media()->findOrFail($mediaId);

        // Delete from PrestaShop first
        foreach ($media->prestashop_mapping ?? [] as $mapping) {
            \App\Jobs\DeletePrestaShopImage::dispatch(
                $mapping['shop_id'],
                $mapping['prestashop_product_id'],
                $mapping['prestashop_image_id']
            );
        }

        // Delete local file
        Storage::delete($media->file_path);

        // Delete record
        $media->delete();

        $this->emit('imagesUpdated');
    }

    public function render()
    {
        return view('livewire.products.management.image-gallery', [
            'images' => $this->product->media()->orderBy('position')->get(),
        ]);
    }
}
```

---

### 5. Testing Strategy

```php
// tests/Feature/PrestaShop/ImageSyncTest.php

namespace Tests\Feature\PrestaShop;

use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\Sync\ImageSyncStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;
    protected PrestaShopShop $shop;
    protected ImageSyncStrategy $syncStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create();
        $this->shop = PrestaShopShop::factory()->create();
        $this->syncStrategy = app(ImageSyncStrategy::class);

        Storage::fake('local');
    }

    /** @test */
    public function it_uploads_image_to_prestashop()
    {
        // Arrange
        $image = UploadedFile::fake()->image('product.jpg', 800, 600);
        $path = $image->store("products/{$this->product->sku}");

        $media = Media::create([
            'product_id' => $this->product->id,
            'file_path' => $path,
            'position' => 1,
            'is_cover' => true,
        ]);

        // Mock PrestaShop client
        $this->mockPrestaShopClient([
            'uploadProductImage' => [
                'image' => [
                    'id' => 123,
                    'id_product' => 100,
                    'position' => 1,
                    'cover' => 1,
                ],
            ],
        ]);

        // Act
        $result = $this->syncStrategy->syncToPrestaShop($media, $this->shop);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
        ]);

        $media->refresh();
        $mapping = collect($media->prestashop_mapping)->firstWhere('shop_id', $this->shop->id);

        $this->assertNotNull($mapping);
        $this->assertEquals(123, $mapping['prestashop_image_id']);
        $this->assertNotNull($mapping['checksum']);
    }

    /** @test */
    public function it_skips_sync_if_checksum_unchanged()
    {
        // Arrange
        $media = Media::factory()->create([
            'product_id' => $this->product->id,
            'prestashop_mapping' => [
                [
                    'shop_id' => $this->shop->id,
                    'prestashop_image_id' => 123,
                    'checksum' => md5_file(storage_path('app/' . $media->file_path)),
                ],
            ],
        ]);

        // Mock client - nie powinien być wywołany
        $clientMock = $this->mockPrestaShopClient();
        $clientMock->shouldNotReceive('uploadProductImage');

        // Act
        $result = $this->syncStrategy->syncToPrestaShop($media, $this->shop);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_rate_limit_errors()
    {
        // Arrange
        $media = Media::factory()->create(['product_id' => $this->product->id]);

        // Mock rate limiter to throw exception
        $this->mock(PrestaShopRateLimiter::class)
            ->shouldReceive('checkLimit')
            ->andThrow(new RateLimitException('Too many requests'));

        // Act & Assert
        $this->expectException(RateLimitException::class);
        $this->syncStrategy->syncToPrestaShop($media, $this->shop);
    }
}
```

---

### 6. Monitoring & Logging

```php
// app/Services/PrestaShop/ImageSyncMonitor.php

namespace App\Services\PrestaShop;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImageSyncMonitor
{
    public function recordSuccess(int $mediaId, int $shopId, int $prestashopImageId): void
    {
        $this->incrementCounter('success');

        Log::info('Image sync success', [
            'media_id' => $mediaId,
            'shop_id' => $shopId,
            'prestashop_image_id' => $prestashopImageId,
            'timestamp' => now(),
        ]);
    }

    public function recordFailure(int $mediaId, int $shopId, string $error): void
    {
        $this->incrementCounter('failure');

        Log::error('Image sync failure', [
            'media_id' => $mediaId,
            'shop_id' => $shopId,
            'error' => $error,
            'timestamp' => now(),
        ]);
    }

    public function recordSkipped(int $mediaId, int $shopId, string $reason): void
    {
        $this->incrementCounter('skipped');

        Log::debug('Image sync skipped', [
            'media_id' => $mediaId,
            'shop_id' => $shopId,
            'reason' => $reason,
        ]);
    }

    protected function incrementCounter(string $type): void
    {
        $key = "prestashop_image_sync:{$type}:today";
        Cache::increment($key);
        Cache::expire($key, now()->endOfDay());
    }

    public function getStats(): array
    {
        return [
            'success' => Cache::get('prestashop_image_sync:success:today', 0),
            'failure' => Cache::get('prestashop_image_sync:failure:today', 0),
            'skipped' => Cache::get('prestashop_image_sync:skipped:today', 0),
        ];
    }
}
```

---

## Podsumowanie i Next Steps

### ✅ Kluczowe Wnioski

1. **API Endpoints:**
   - ✅ POST `/api/images/products/{id}` - Upload
   - ✅ GET `/api/images/products/{id}` - Pobieranie
   - ✅ DELETE `/api/images/products/{id}/{image_id}` - Usuwanie
   - ✅ PUT z `?ps_method=PUT` - Update cover

2. **Multi-Store Limitation:**
   - ⚠️ Brak tabeli `ps_image_shop`
   - ❌ Obrazy są globalne (wspólne dla wszystkich sklepów)
   - ✅ Rozwiązanie: Osobne produkty per shop w PPM mapping

3. **Formaty:**
   - ✅ JPEG, PNG, GIF wspierane
   - ⚠️ WebP nie oficjalnie wspierany
   - ✅ Max 8MB per image (rekomendacja)

4. **Performance:**
   - ✅ Rate limiting: max 30 req/min (bezpieczny limit)
   - ✅ Queue + batch processing dla bulk sync
   - ✅ Checksum dla change detection

### 📋 Implementacja w PPM - Roadmap

**ETAP 1: Database & Models** (1-2 dni)
- [ ] Migration: `prestashop_mapping` do tabeli `media`
- [ ] Media model: accessor/mutator dla mapping
- [ ] Config: `config/prestashop.php` image settings

**ETAP 2: Service Layer** (3-4 dni)
- [ ] `ImageSyncStrategy` - główna logika sync
- [ ] `PrestaShopRateLimiter` - rate limiting
- [ ] `ImageSyncMonitor` - monitoring/logging
- [ ] Integration w `PrestaShopClientFactory`

**ETAP 3: Jobs & Queue** (2-3 dni)
- [ ] `SyncProductImageToPrestaShop` job
- [ ] `DeletePrestaShopImage` job
- [ ] Batch processing dla bulk sync

**ETAP 4: UI Components** (3-4 dni)
- [ ] `ImageGallery` Livewire component
- [ ] Upload interface z preview
- [ ] Drag & drop reordering
- [ ] Sync status indicators

**ETAP 5: Testing & Optimization** (2-3 dni)
- [ ] Unit tests dla sync strategy
- [ ] Integration tests z mock API
- [ ] Performance testing (bulk upload)
- [ ] Error handling scenarios

**TOTAL ESTIMATE:** 11-16 dni roboczych

---

### 📚 Dodatkowe Zasoby

- **PrestaShop API Docs:** https://devdocs.prestashop-project.org/8/webservice/
- **Image Management Guide:** https://devdocs.prestashop-project.org/8/webservice/tutorials/advanced-use/image-management/
- **Database Schema:** https://github.com/PrestaShop/PrestaShop/blob/8.1.x/install-dev/data/db_structure.sql

---

**KONIEC RAPORTU**

*Dla pytań lub clarifications, skonsultuj się z PrestaShop API Expert Agent*
