# ERP: Subiekt GT API Connection

## Overview
Subiekt GT REST API wrapper działa na serwerze `sapi.mpptrade.pl` (Windows Server + IIS).

## Connection Configuration

### REST API (Recommended)
| Parameter | Value |
|-----------|-------|
| Base URL | `https://sapi.mpptrade.pl` |
| Auth Header | `X-API-Key` |
| SSL Verify | `false` (self-signed certificate) |
| Timeout | 30s |
| Retry | 3 attempts |

### Laravel Configuration
```php
// SubiektRestApiClient initialization
$client = new SubiektRestApiClient([
    'base_url' => 'https://sapi.mpptrade.pl',
    'api_key' => $config['rest_api_key'],
    'timeout' => 30,
    'connect_timeout' => 10,
    'retry_times' => 3,
    'retry_delay' => 100,
    'verify_ssl' => false,  // CRITICAL: sapi.mpptrade.pl uses self-signed cert
]);
```

## Available Endpoints

### Health & Stats
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/health` | GET | Connection test + DB stats |
| `/api/stats` | GET | Database statistics |

### Products
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/products` | GET | List products (paginated) |
| `/api/products/{id}` | GET | Single product by ID |
| `/api/products/sku/{sku}` | GET | Single product by SKU |

### Stock
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/stock` | GET | All stock levels |
| `/api/stock/{productId}` | GET | Stock for product |
| `/api/stock/sku/{sku}` | GET | Stock by SKU |

### Prices
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/prices/{productId}` | GET | All prices for product |
| `/api/prices/sku/{sku}` | GET | Prices by SKU |

### Reference Data
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/warehouses` | GET | All warehouses (sl_Magazyn) |
| `/api/price-levels` | GET | Price types (sl_RodzCeny) |
| `/api/vat-rates` | GET | VAT rates |
| `/api/manufacturers` | GET | Manufacturers |
| `/api/product-groups` | GET | Product groups |
| `/api/units` | GET | Measurement units |

## Query Parameters

### Products Endpoint
```
GET /api/products?page=1&pageSize=100&priceLevel=0&warehouseId=1&sku=ABC&name=test
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `pageSize` | int | 100 | Items per page (max 500) |
| `priceLevel` | int | 0 | Price column (0-10 = tc_CenaNetto0..10) |
| `warehouseId` | int | 1 | Warehouse for stock |
| `sku` | string | - | Filter by SKU (LIKE) |
| `name` | string | - | Filter by name (LIKE) |

## Response Format

### Success Response
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

### Error Response
```json
{
    "success": false,
    "error": "Error message",
    "timestamp": "2026-01-20T17:28:32.666Z"
}
```

## SSL Certificate Issue

**Problem:** `sapi.mpptrade.pl` uses self-signed SSL certificate.

**Solution:** Always set `verify_ssl => false` in configuration:
```php
// ERPManager.php - buildConnectionConfig()
case 'subiekt_gt':
    return array_merge($this->subiektConfig, [
        'rest_api_verify_ssl' => false,  // CRITICAL!
    ]);
```

## Caching Strategy

Reference data endpoints cache results for 1 hour:
- warehouses
- price-levels
- vat-rates
- manufacturers
- product-groups
- units

Use `$client->clearCache()` to invalidate cache.

## Error Handling

```php
try {
    $result = $client->getProducts(['page' => 1]);
} catch (SubiektApiException $e) {
    // API-level error (4xx, 5xx)
    Log::error('Subiekt API error', [
        'message' => $e->getMessage(),
        'http_status' => $e->getHttpStatusCode(),
    ]);
} catch (ConnectionException $e) {
    // Network error
    Log::error('Connection failed', ['error' => $e->getMessage()]);
}
```

## Files Reference
- `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` - HTTP client
- `app/Services/ERP/SubiektGTService.php` - Business logic
- `app/Http/Livewire/Admin/ERP/ERPManager.php` - UI controller
