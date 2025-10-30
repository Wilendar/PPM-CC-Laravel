# 03. Routing Table

[◀ Powrót do spisu treści](README.md)

---

## 🗺️ Kompletna Tabela Routingu

### Dashboard & Core

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/dashboard` | dashboard | DashboardController@index | auth | Dashboard (role-based content) |

---

### Sklepy PrestaShop

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/shops` | admin.shops.index | ShopController@index | auth, role:manager+ | Lista sklepów |
| `/admin/shops/create` | admin.shops.create | ShopController@create | auth, role:admin | Dodaj sklep |
| `/admin/shops/{id}/edit` | admin.shops.edit | ShopController@edit | auth, role:admin | Edytuj sklep |
| `/admin/shops/sync` | admin.shops.sync | ShopSyncController@index | auth, role:manager+ | Synchronizacja |

**Zmiany v2.0:**
- ❌ Usunięto: `/admin/shops/export` (Eksport masowy)
- ✅ Funkcja dostępna jako przycisk w Lista Produktów

---

### Produkty

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/products` | admin.products.index | ProductController@index | auth | Lista produktów + Eksport do CSV |
| `/admin/products/create` | admin.products.create | ProductController@create | auth, role:manager+ | Dodaj produkt |
| `/admin/products/{id}/edit` | admin.products.edit | ProductController@edit | auth | Edytuj produkt |
| `/admin/products/categories` | admin.products.categories | CategoryController@index | auth, role:manager+ | Zarządzanie kategoriami |
| `/admin/products/import` | admin.products.import | ImportController@index | auth, role:manager+ | Import z pliku (CSV/XLSX) |
| `/admin/products/import-history` | admin.products.import.history | ImportHistoryController@index | auth, role:manager+ | Historie importów |
| `/admin/products/search` | admin.products.search | ProductSearchController@index | auth | Wyszukiwarka |

**Zmiany v2.0:**
- ✅ NOWY: `/admin/products/import` - Unified CSV + XLSX import
- ✅ NOWY: `/admin/products/import-history` - Historie (przeniesione z ZARZĄDZANIE)
- ✅ `/admin/products` - Dodany przycisk "Eksportuj wszystko do CSV"

---

### Cennik

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/price-management/price-groups` | admin.price.groups | PriceGroupController@index | auth, role:manager+ | Grupy cenowe |
| `/admin/price-management/product-prices` | admin.price.products | ProductPriceController@index | auth, role:manager+ | Ceny produktów |
| `/admin/price-management/bulk-updates` | admin.price.bulk | BulkPriceController@index | auth, role:manager+ | Aktualizacja masowa |

---

### Warianty & Cechy

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/variants` | admin.variants.index | VariantController@index | auth, role:manager+ | Zarządzanie wariantami |
| `/admin/features/vehicles` | admin.features.vehicles | VehicleFeatureController@index | auth, role:manager+ | Cechy pojazdów |
| `/admin/compatibility` | admin.compatibility.index | CompatibilityController@index | auth, role:manager+ | Dopasowania części |

---

### Dostawy & Kontenery

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/deliveries` | admin.deliveries.index | DeliveryController@index | auth, role:magazynier+ | Lista dostaw |
| `/admin/deliveries/containers/{id}` | admin.deliveries.container | ContainerController@show | auth, role:magazynier+ | Szczegóły kontenera |
| `/admin/deliveries/receiving` | admin.deliveries.receiving | ReceivingController@index | auth, role:magazynier+ | Przyjęcia magazynowe |
| `/admin/deliveries/documents` | admin.deliveries.documents | DeliveryDocumentController@index | auth, role:magazynier+ | Dokumenty odpraw |

---

### Zamówienia

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/orders` | admin.orders.index | OrderController@index | auth, role:handlowiec+ | Lista zamówień |
| `/admin/orders/reservations` | admin.orders.reservations | ReservationController@index | auth, role:handlowiec+ | Rezerwacje z kontenera |
| `/admin/orders/history` | admin.orders.history | OrderHistoryController@index | auth, role:handlowiec+ | Historia zamówień |

---

### Reklamacje

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/claims` | admin.claims.index | ClaimController@index | auth, role:reklamacje+ | Lista reklamacji |
| `/admin/claims/create` | admin.claims.create | ClaimController@create | auth, role:reklamacje+ | Nowa reklamacja |
| `/admin/claims/archive` | admin.claims.archive | ClaimController@archive | auth, role:reklamacje+ | Archiwum |

---

### Raporty & Statystyki

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/reports/products` | admin.reports.products | ProductReportController@index | auth, role:manager+ | Raporty produktowe |
| `/admin/reports/financial` | admin.reports.financial | FinancialReportController@index | auth, role:manager+ | Raporty finansowe |
| `/admin/reports/warehouse` | admin.reports.warehouse | WarehouseReportController@index | auth, role:manager+ | Raporty magazynowe |
| `/admin/reports/export` | admin.reports.export | ReportExportController@index | auth, role:manager+ | Eksport raportów |

---

### System (Admin Panel)

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/system-settings` | admin.system.settings | SystemSettingsController@index | auth, role:admin | Ustawienia systemu |
| `/admin/users` | admin.users.index | UserController@index | auth, role:admin | Zarządzanie użytkownikami |
| `/admin/integrations` | admin.integrations.index | IntegrationController@index | auth, role:admin | Lista integracji ERP (dynamiczna) |
| `/admin/integrations/{slug}` | admin.integrations.show | IntegrationController@show | auth, role:admin | Szczegóły integracji ERP |
| `/admin/integrations/{slug}/configure` | admin.integrations.configure | IntegrationController@configure | auth, role:admin | Konfiguracja integracji ERP |
| `/admin/backup` | admin.backup.index | BackupController@index | auth, role:admin | Backup & Restore |
| `/admin/maintenance` | admin.maintenance.index | MaintenanceController@index | auth, role:admin | Konserwacja bazy |
| `/admin/logs` | admin.logs.index | LogController@index | auth, role:admin | Logi systemowe |
| `/admin/monitoring` | admin.monitoring.index | MonitoringController@index | auth, role:admin | Monitoring |
| `/admin/api` | admin.api.index | APIController@index | auth, role:admin | API Management |

**Zmiany v2.0:**
- ✅ NOWY: `/admin/integrations` - Dynamiczna lista ERP (przeniesione z top-level)
- ✅ NOWY: `/admin/integrations/{slug}` - Uniwersalny endpoint (baselinker, subiekt, dynamics, custom)
- ✅ Plugin-based architecture (możliwość dodawania custom integrations)

**Dynamiczne Integracje - Przykłady {slug}:**
- `baselinker` → BaseLinker integration
- `subiekt-gt` → Subiekt GT integration
- `microsoft-dynamics` → Microsoft Dynamics integration
- `custom-erp-xyz` → Custom ERP (dodane przez admina)

---

### Profil Użytkownika

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/profile/edit` | profile.edit | ProfileController@edit | auth | Edycja profilu |
| `/profile/sessions` | profile.sessions | SessionController@index | auth | Aktywne sesje |
| `/profile/activity` | profile.activity | ActivityController@index | auth | Historia aktywności |
| `/profile/notifications` | profile.notifications | NotificationController@index | auth | Ustawienia powiadomień |

---

### Pomoc

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/help` | help.index | HelpController@index | auth | Dokumentacja |
| `/help/shortcuts` | help.shortcuts | HelpController@shortcuts | auth | Skróty klawiszowe |
| `/help/support` | help.support | SupportController@index | auth | Wsparcie techniczne |

---

## 📊 Statystyki Routingu

### Liczba Route'ów per Sekcja

| Sekcja | Liczba Route'ów |
|--------|-----------------|
| Dashboard | 1 |
| Sklepy PrestaShop | 4 |
| Produkty | 7 |
| Cennik | 3 |
| Warianty & Cechy | 3 |
| Dostawy & Kontenery | 4 |
| Zamówienia | 3 |
| Reklamacje | 3 |
| Raporty & Statystyki | 4 |
| System (Admin Panel) | 10 |
| Profil Użytkownika | 4 |
| Pomoc | 3 |
| **TOTAL** | **49** |

### Middleware per Poziom Uprawnień

| Middleware | Liczba Route'ów | Przykłady |
|------------|-----------------|-----------|
| `auth` (wszyscy zalogowani) | 13 | Dashboard, Produkty (odczyt), Pomoc, Profil |
| `auth, role:admin` | 14 | Sklepy (create/edit), System, Integracje ERP |
| `auth, role:manager+` | 18 | Produkty (edycja), Cennik, Warianty, Raporty |
| `auth, role:magazynier+` | 4 | Dostawy, Kontenery, Przyjęcia |
| `auth, role:handlowiec+` | 3 | Zamówienia, Rezerwacje |
| `auth, role:reklamacje+` | 3 | Reklamacje |

---

## 🔧 RESTful Patterns

### Resource Routes (Laravel Convention)

**Przykład: Produkty**
```php
Route::resource('products', ProductController::class)
    ->middleware(['auth'])
    ->names('admin.products');

// Generuje:
// GET    /products              -> index   (lista)
// GET    /products/create       -> create  (formularz dodawania)
// POST   /products              -> store   (zapis nowego)
// GET    /products/{id}         -> show    (szczegóły)
// GET    /products/{id}/edit    -> edit    (formularz edycji)
// PUT    /products/{id}         -> update  (aktualizacja)
// DELETE /products/{id}         -> destroy (usunięcie)
```

**Custom Routes (Non-RESTful):**
```php
// Import/Export
Route::get('/products/import', [ImportController::class, 'index'])
    ->name('admin.products.import');

// Search
Route::get('/products/search', [ProductSearchController::class, 'index'])
    ->name('admin.products.search');

// Categories
Route::get('/products/categories', [CategoryController::class, 'index'])
    ->name('admin.products.categories');
```

---

## 🌐 API Routes (dla przyszłości)

**Prefix:** `/api/v1/`

**Authentication:** Laravel Sanctum (Token-based)

**Przykładowe Endpointy:**

```php
// Products API
GET    /api/v1/products              -> Products.index
GET    /api/v1/products/{sku}        -> Products.show
POST   /api/v1/products              -> Products.store
PUT    /api/v1/products/{sku}        -> Products.update
DELETE /api/v1/products/{sku}        -> Products.destroy

// Sync API
POST   /api/v1/shops/{id}/sync       -> ShopSync.trigger
GET    /api/v1/shops/{id}/sync/status -> ShopSync.status

// ERP Integration API
POST   /api/v1/integrations/{slug}/sync -> Integration.sync
GET    /api/v1/integrations/{slug}/status -> Integration.status
```

**Rate Limiting:**
- Authenticated: 60 requests/minute
- Unauthenticated: 10 requests/minute

---

## 📖 Nawigacja

- **Poprzedni moduł:** [02. Struktura Menu](02_STRUKTURA_MENU.md)
- **Następny moduł:** [04. Macierz Uprawnień](04_MACIERZ_UPRAWNIEN.md)
- **Powrót:** [Spis treści](README.md)
