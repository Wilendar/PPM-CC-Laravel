# PPM-CC-Laravel - Architektura Stron i Menu

**Projekt:** PrestaShop Product Manager (PPM)
**Klient:** MPP TRADE
**Wersja Dokumentu:** 1.0
**Data Utworzenia:** 2025-10-22
**Ostatnia Aktualizacja:** 2025-10-22

---

## 📋 Spis Treści

1. [Cel Dokumentu](#cel-dokumentu)
2. [Główna Struktura Menu](#główna-struktura-menu)
3. [Routing Table](#routing-table)
4. [Macierz Uprawnień](#macierz-uprawnień)
5. [Szczegółowy Opis Stron](#szczegółowy-opis-stron)
   - [Dashboard](#1-dashboard)
   - [Sklepy PrestaShop](#2-sklepy-prestashop)
   - [Produkty](#3-produkty)
   - [Cennik](#4-cennik)
   - [Warianty & Cechy](#5-warianty--cechy)
   - [Zarządzanie (Import/Export)](#6-zarządzanie-importexport)
   - [Integracje ERP](#7-integracje-erp)
   - [Dostawy & Kontenery](#8-dostawy--kontenery)
   - [Zamówienia](#9-zamówienia)
   - [Reklamacje](#10-reklamacje)
   - [System (Admin Panel)](#11-system-admin-panel)
   - [Raporty & Statystyki](#12-raporty--statystyki)
   - [Profil Użytkownika](#13-profil-użytkownika)
   - [Pomoc](#14-pomoc)
6. [UI/UX Guidelines](#uiux-guidelines)
7. [Design System](#design-system)
8. [Responsive Design](#responsive-design)
9. [Implementation Checklist](#implementation-checklist)
10. [Status Implementacji](#status-implementacji)

---

## 🎯 Cel Dokumentu

Zaprojektowana kompleksowa struktura menu i stron aplikacji PPM (PrestaShop Product Manager) bazująca na:

- **12 ETAPÓW** planu projektu z `Plan_Projektu/`
- **Specyfikacja** z `_init.md` - wymagania klienta MPP TRADE
- **Obecny stan** implementacji z `routes/web.php` i navigation
- **7-poziomowy system** uprawnień (Admin → Użytkownik)

---

## 🏠 Główna Struktura Menu

### Sidebar Navigation (Hierarchiczna)

```
┌────────────────────────────────────────────────┐
│  🏠 DASHBOARD                                  │ [Wszyscy]
├────────────────────────────────────────────────┤
│  🏪 SKLEPY PRESTASHOP                          │ [Menadżer+]
│    ├─ Lista sklepów                            │
│    ├─ Dodaj sklep                              │
│    ├─ Synchronizacja                           │
│    └─ Eksport masowy                           │
├────────────────────────────────────────────────┤
│  📦 PRODUKTY                                   │ [Wszyscy]
│    ├─ Lista produktów                          │
│    ├─ Dodaj produkt                            │
│    ├─ Kategorie                                │
│    └─ Wyszukiwarka                             │
├────────────────────────────────────────────────┤
│  💰 CENNIK                                     │ [Menadżer+]
│    ├─ Grupy cenowe                             │
│    ├─ Ceny produktów                           │
│    └─ Aktualizacja masowa                      │
├────────────────────────────────────────────────┤
│  🎨 WARIANTY & CECHY                           │ [Menadżer+]
│    ├─ Zarządzanie wariantami                   │
│    ├─ Cechy pojazdów                           │
│    └─ Dopasowania części                       │
├────────────────────────────────────────────────┤
│  📂 ZARZĄDZANIE                                │ [Menadżer+]
│    ├─ CSV Import/Export                        │ [NEW]
│    ├─ Import XLSX                              │
│    └─ Historie importów                        │
├────────────────────────────────────────────────┤
│  🔗 INTEGRACJE ERP                             │ [Admin]
│    ├─ BaseLinker                               │
│    ├─ Subiekt GT                               │
│    └─ Microsoft Dynamics                       │
├────────────────────────────────────────────────┤
│  🚚 DOSTAWY & KONTENERY                        │ [Magazynier+]
│    ├─ Lista dostaw                             │
│    ├─ Kontenery                                │
│    ├─ Przyjęcia magazynowe                     │
│    └─ Dokumenty odpraw                         │
├────────────────────────────────────────────────┤
│  📋 ZAMÓWIENIA                                 │ [Handlowiec+]
│    ├─ Lista zamówień                           │
│    ├─ Rezerwacje z kontenera                   │
│    └─ Historia zamówień                        │
├────────────────────────────────────────────────┤
│  ⚠️ REKLAMACJE                                 │ [Reklamacje+]
│    ├─ Lista reklamacji                         │
│    ├─ Nowa reklamacja                          │
│    └─ Archiwum                                 │
├────────────────────────────────────────────────┤
│  ⚙️ SYSTEM                                     │ [Admin]
│    ├─ Ustawienia systemu                       │
│    ├─ Zarządzanie użytkownikami                │
│    ├─ Backup & Restore                         │
│    ├─ Konserwacja bazy                         │
│    ├─ Logi systemowe                           │
│    ├─ Monitoring                               │
│    └─ API Management                           │
├────────────────────────────────────────────────┤
│  📊 RAPORTY & STATYSTYKI                       │ [Menadżer+]
│    ├─ Raporty produktowe                       │
│    ├─ Raporty finansowe                        │
│    ├─ Raporty magazynowe                       │
│    └─ Eksport raportów                         │
├────────────────────────────────────────────────┤
│  👤 PROFIL UŻYTKOWNIKA                         │ [Wszyscy]
│    ├─ Edycja profilu                           │
│    ├─ Aktywne sesje                            │
│    ├─ Historia aktywności                      │
│    └─ Ustawienia powiadomień                   │
├────────────────────────────────────────────────┤
│  ❓ POMOC                                      │ [Wszyscy]
│    ├─ Dokumentacja                             │
│    ├─ Skróty klawiszowe                        │
│    └─ Wsparcie techniczne                      │
└────────────────────────────────────────────────┘
```

---

## 🗺️ Routing Table

### Dashboard & Core

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/dashboard` | dashboard | DashboardController@index | auth | Główny dashboard |

### Sklepy PrestaShop

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/shops` | admin.shops.index | ShopController@index | auth, role:manager+ | Lista sklepów |
| `/admin/shops/create` | admin.shops.create | ShopController@create | auth, role:admin | Dodaj sklep |
| `/admin/shops/{id}/edit` | admin.shops.edit | ShopController@edit | auth, role:admin | Edytuj sklep |
| `/admin/shops/sync` | admin.shops.sync | ShopSyncController@index | auth, role:manager+ | Synchronizacja |
| `/admin/shops/export` | admin.shops.export | ShopExportController@index | auth, role:manager+ | Eksport masowy |

### Produkty

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/products` | admin.products.index | ProductController@index | auth | Lista produktów |
| `/admin/products/create` | admin.products.create | ProductController@create | auth, role:manager+ | Dodaj produkt |
| `/admin/products/{id}/edit` | admin.products.edit | ProductController@edit | auth | Edytuj produkt |
| `/admin/products/categories` | admin.products.categories | CategoryController@index | auth, role:manager+ | Zarządzanie kategoriami |
| `/admin/products/search` | admin.products.search | ProductSearchController@index | auth | Wyszukiwarka |

### Cennik

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/price-management/price-groups` | admin.price.groups | PriceGroupController@index | auth, role:manager+ | Grupy cenowe |
| `/admin/price-management/product-prices` | admin.price.products | ProductPriceController@index | auth, role:manager+ | Ceny produktów |
| `/admin/price-management/bulk-updates` | admin.price.bulk | BulkPriceController@index | auth, role:manager+ | Aktualizacja masowa |

### Warianty & Cechy

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/variants` | admin.variants.index | VariantController@index | auth, role:manager+ | Zarządzanie wariantami |
| `/admin/features/vehicles` | admin.features.vehicles | VehicleFeatureController@index | auth, role:manager+ | Cechy pojazdów |
| `/admin/compatibility` | admin.compatibility.index | CompatibilityController@index | auth, role:manager+ | Dopasowania części |

### Zarządzanie (Import/Export)

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/csv/import` | admin.csv.import | CSVImportController@index | auth, role:manager+ | CSV Import/Export |
| `/admin/import` | admin.import.index | ImportController@index | auth, role:manager+ | Import XLSX |
| `/admin/import-history` | admin.import.history | ImportHistoryController@index | auth, role:manager+ | Historie importów |

### Integracje ERP

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/integrations/baselinker` | admin.erp.baselinker | BaseLinkerController@index | auth, role:admin | BaseLinker |
| `/admin/integrations/subiekt` | admin.erp.subiekt | SubiektController@index | auth, role:admin | Subiekt GT |
| `/admin/integrations/dynamics` | admin.erp.dynamics | DynamicsController@index | auth, role:admin | Microsoft Dynamics |

### Dostawy & Kontenery

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/deliveries` | admin.deliveries.index | DeliveryController@index | auth, role:magazynier+ | Lista dostaw |
| `/admin/deliveries/containers/{id}` | admin.deliveries.container | ContainerController@show | auth, role:magazynier+ | Szczegóły kontenera |
| `/admin/deliveries/receiving` | admin.deliveries.receiving | ReceivingController@index | auth, role:magazynier+ | Przyjęcia magazynowe |
| `/admin/deliveries/documents` | admin.deliveries.documents | DeliveryDocumentController@index | auth, role:magazynier+ | Dokumenty odpraw |

### Zamówienia

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/orders` | admin.orders.index | OrderController@index | auth, role:handlowiec+ | Lista zamówień |
| `/admin/orders/reservations` | admin.orders.reservations | ReservationController@index | auth, role:handlowiec+ | Rezerwacje z kontenera |
| `/admin/orders/history` | admin.orders.history | OrderHistoryController@index | auth, role:handlowiec+ | Historia zamówień |

### Reklamacje

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/claims` | admin.claims.index | ClaimController@index | auth, role:reklamacje+ | Lista reklamacji |
| `/admin/claims/create` | admin.claims.create | ClaimController@create | auth, role:reklamacje+ | Nowa reklamacja |
| `/admin/claims/archive` | admin.claims.archive | ClaimController@archive | auth, role:reklamacje+ | Archiwum |

### System (Admin Panel)

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/system-settings` | admin.system.settings | SystemSettingsController@index | auth, role:admin | Ustawienia systemu |
| `/admin/users` | admin.users.index | UserController@index | auth, role:admin | Zarządzanie użytkownikami |
| `/admin/backup` | admin.backup.index | BackupController@index | auth, role:admin | Backup & Restore |
| `/admin/maintenance` | admin.maintenance.index | MaintenanceController@index | auth, role:admin | Konserwacja bazy |
| `/admin/logs` | admin.logs.index | LogController@index | auth, role:admin | Logi systemowe |
| `/admin/monitoring` | admin.monitoring.index | MonitoringController@index | auth, role:admin | Monitoring |
| `/admin/api` | admin.api.index | APIController@index | auth, role:admin | API Management |

### Raporty & Statystyki

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/admin/reports/products` | admin.reports.products | ProductReportController@index | auth, role:manager+ | Raporty produktowe |
| `/admin/reports/financial` | admin.reports.financial | FinancialReportController@index | auth, role:manager+ | Raporty finansowe |
| `/admin/reports/warehouse` | admin.reports.warehouse | WarehouseReportController@index | auth, role:manager+ | Raporty magazynowe |
| `/admin/reports/export` | admin.reports.export | ReportExportController@index | auth, role:manager+ | Eksport raportów |

### Profil Użytkownika

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/profile/edit` | profile.edit | ProfileController@edit | auth | Edycja profilu |
| `/profile/sessions` | profile.sessions | SessionController@index | auth | Aktywne sesje |
| `/profile/activity` | profile.activity | ActivityController@index | auth | Historia aktywności |
| `/profile/notifications` | profile.notifications | NotificationController@index | auth | Ustawienia powiadomień |

### Pomoc

| Route Path | Name | Controller/Component | Middleware | Opis |
|------------|------|---------------------|------------|------|
| `/help` | help.index | HelpController@index | auth | Dokumentacja |
| `/help/shortcuts` | help.shortcuts | HelpController@shortcuts | auth | Skróty klawiszowe |
| `/help/support` | help.support | SupportController@index | auth | Wsparcie techniczne |

---

## 🔐 Macierz Uprawnień

### 7-Poziomowy System Ról

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| **CORE** |
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **SKLEPY PRESTASHOP** |
| Sklepy PrestaShop | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **PRODUKTY** |
| Lista produktów (odczyt) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dodaj/Usuń produkt | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edycja produktu | ✅ | ✅ | ✅ (bez usuwania) | ❌ | ❌ | ❌ | ❌ |
| **CENNIK** |
| Grupy cenowe | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Ceny widoczne | ✅ | ✅ | ✅ | ✅ | 🟡 (bez zakupu) | ✅ | ✅ |
| **WARIANTY & CECHY** |
| Warianty & Cechy | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **ZARZĄDZANIE** |
| CSV Import/Export | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **INTEGRACJE ERP** |
| Integracje ERP | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **DOSTAWY & KONTENERY** |
| Dostawy & Kontenery | ✅ | ✅ | ❌ | ✅ (edycja) | 🟡 (rezerwacje) | ❌ | ❌ |
| **ZAMÓWIENIA** |
| Zamówienia | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **REKLAMACJE** |
| Reklamacje | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **SYSTEM** |
| System Settings | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Użytkownicy | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **RAPORTY** |
| Raporty | ✅ | ✅ | ❌ | ✅ (magazynowe) | ❌ | ❌ | ❌ |

**Legenda:**
- ✅ Pełny dostęp
- 🟡 Ograniczony dostęp (szczegóły w opisie)
- ❌ Brak dostępu

---

## 📄 Szczegółowy Opis Stron

### 1. Dashboard

**Route:** `/dashboard`
**Uprawnienia:** Wszyscy zalogowani użytkownicy

#### Zawartość

**KPI Cards (4-column grid):**
- Liczba produktów w systemie
- Liczba sklepów PrestaShop
- Stany magazynowe (suma)
- Oczekujące dostawy

**Quick Actions Panel:**
- "Dodaj produkt" (Menadżer+)
- "Import CSV" (Menadżer+)
- "Wyszukaj produkt" (Wszyscy)
- "Nowa dostawa" (Magazynier+)

**Recent Activity (timeline):**
- Ostatnie zmiany produktów
- Ostatnie synchronizacje
- Ostatnie importy

**Wykresy statystyk (interactive charts):**
- Trend stanów magazynowych (7 dni)
- Produkty per kategoria (pie chart)
- Status synchronizacji (bar chart)

**Alerts & Notifications:**
- Niski stan magazynowy
- Błędy synchronizacji
- Oczekujące zadania

---

### 2. Sklepy PrestaShop

#### 2.1 Lista Sklepów

**Route:** `/admin/shops`
**Uprawnienia:** Admin, Menadżer, Redaktor

**Zawartość:**

**Tabela sklepów:**
- Nazwa sklepu
- URL + Logo
- Wersja PrestaShop (8.x / 9.x)
- Status połączenia (online/offline)
- Ostatnia synchronizacja
- Liczba produktów
- Akcje (Edit, Sync, Delete)

**Header Actions:**
- Przycisk "Dodaj sklep"
- Przycisk "Test wszystkich połączeń"
- Filter: Status (wszystkie / aktywne / nieaktywne)

**Bulk Operations:**
- Masowa synchronizacja wybranych
- Masowy eksport produktów

---

#### 2.2 Dodaj/Edytuj Sklep

**Route:** `/admin/shops/create`, `/admin/shops/{id}/edit`
**Uprawnienia:** Admin

**Formularz konfiguracji (Tabs):**

**Tab 1: Dane połączenia**
- Nazwa sklepu (tekstowe)
- URL sklepu (tekstowe, walidacja URL)
- Klucz API (password, test connection button)
- Wersja PrestaShop (dropdown: 8.x / 9.x)
- Status aktywny/nieaktywny

**Tab 2: Mapowania**
- Mapowanie grup cenowych PPM → PrestaShop
- Mapowanie magazynów PPM → PrestaShop (single select)
- Mapowanie kategorii (tree view picker)

**Tab 3: Dopasowania**
- Wybór marek pojazdów dla tego sklepu
- Filtrowanie: Producent/Marka pojazdu
- Lista wybranych marek z opcją usunięcia

**Tab 4: Ustawienia synchronizacji**
- Częstotliwość auto-sync (disabled / co 15min / co 1h / co 6h / co 24h)
- Synchronizuj zdjęcia (checkbox)
- Synchronizuj stany magazynowe (checkbox)
- Synchronizuj ceny (checkbox)
- Synchronizuj kategorie (checkbox)

**Przyciski akcji:**
- Zapisz i testuj połączenie
- Zapisz
- Anuluj

---

#### 2.3 Synchronizacja

**Route:** `/admin/shops/sync`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Status Panel (per sklep):**
- Nazwa sklepu
- Ostatnia synchronizacja (timestamp)
- Status: Success / In Progress / Failed
- Progress bar (real-time)

**Sync Actions:**
- "Synchronizuj wszystkie sklepy"
- "Synchronizuj tylko zmiany"
- "Full synchronizacja" (produkty + kategorie + ceny + stany)

**Sync Logs (tabela):**
- Timestamp
- Sklep
- Typ operacji (export/import/update)
- Status (success/error)
- Szczegóły / Error message
- Przycisk "Retry"

---

#### 2.4 Eksport Masowy

**Route:** `/admin/shops/export`
**Uprawnienia:** Admin, Menadżer, Redaktor

**Wizard eksportu:**

**Krok 1:** Wybór sklepu (multi-select)

**Krok 2:** Wybór produktów
- Wszystkie produkty
- Produkty z kategorii (tree picker)
- Produkty według filtrów (SKU, Producent, Typ)
- Lista wybrana (z preview)

**Krok 3:** Opcje eksportu
- Eksportuj zdjęcia (checkbox)
- Eksportuj kategorie (checkbox)
- Eksportuj cechy i dopasowania (checkbox)
- Nadpisz istniejące (checkbox)

**Krok 4:** Podsumowanie i eksport
- Preview: X produktów → Y sklepów
- Szacowany czas eksportu
- Przycisk "Rozpocznij eksport"
- Progress tracking (real-time)

---

### 3. Produkty

#### 3.1 Lista Produktów

**Route:** `/admin/products`
**Uprawnienia:**
- Odczyt: Wszyscy
- Edycja/Dodawanie: Admin, Menadżer
- Edycja bez usuwania: Redaktor

**Zawartość:**

**Filtry i wyszukiwarka (sticky header):**
- Quick search (SKU, Nazwa, Producent)
- Filtr: Kategoria (dropdown z hierarchią)
- Filtr: Typ produktu (Pojazd / Część / Odzież / Inne)
- Filtr: Status (Aktywny / Nieaktywny)
- Filtr: Sklep PrestaShop (multi-select)
- Advanced: Stany magazynowe, Grupy cenowe

**Tabela produktów (sortowalna, paginowana):**
- Checkbox (bulk select)
- Miniatura zdjęcia
- SKU (klikalne, link do edycji)
- Nazwa produktu
- Kategoria (breadcrumb)
- Typ produktu (badge)
- Status (Active/Inactive badge)
- Cena detaliczna
- Stan magazynowy (suma wszystkich)
- Label sklepów (badges: YCF, PitBike, etc.)
- Status sync (ikony: synced/pending/error)
- Akcje (Edit, Duplicate, Delete)

**Bulk Operations Bar (wyświetla się po zaznaczeniu):**
- Eksportuj na sklepy (multi-select modal)
- Masowa edycja cen
- Masowa edycja stanów
- Masowa zmiana kategorii
- Masowa zmiana statusu
- Usuń zaznaczone (z potwierdzeniem)

**Header Actions:**
- Przycisk "Dodaj produkt" (duży, primary)
- Przycisk "Import CSV"
- Przycisk "Eksport do CSV"
- Toggle widoku (tabela / karty)

**Pagination:**
- 25 / 50 / 100 / 200 per page
- Informacja: "Pokazuję X-Y z Z produktów"

---

#### 3.2 Dodaj/Edytuj Produkt

**Route:** `/admin/products/create`, `/admin/products/{id}/edit`
**Uprawnienia:**
- Pełny dostęp: Admin, Menadżer
- Edycja bez usuwania zdjęć/produktu: Redaktor
- Tylko odczyt: pozostali

**Formularz produktu (multi-tab interface):**

**Tab 1: DANE PODSTAWOWE**
- SKU (tekstowe, unique, required)
- Nazwa produktu (tekstowe, required)
- Typ produktu (dropdown: Pojazd / Część Zamienna / Odzież / Inne)
- Producent (searchable dropdown z opcją dodania nowego)
- Status (aktywny/nieaktywny toggle)
- Widoczny (tak/nie toggle)
- EAN (tekstowe)
- Symbol dostawcy (tekstowe, multi-value z separatorem ";")
- Stawka VAT (dropdown: 23% / 8% / 5% / 0% / zw.)

**Tab 2: KATEGORIE**

*Dane domyślne (global):*
- Category tree picker (5 poziomów: Kategoria → Kategoria4)
- Checkbox "Oznacz najgłębszą jako domyślną"

*Per-Shop Categories (tabs per sklep):*
- Shop 1 (YCF): Category tree picker
- Shop 2 (PitBike): Category tree picker
- Przycisk "Użyj kategorii domyślnych"

**Tab 3: OPISY**

*Dane domyślne (global):*
- Opis krótki (WYSIWYG editor, max 800 znaków)
- Opis długi (WYSIWYG editor, max 21844 znaków)

*Per-Shop Descriptions (tabs per sklep):*
- Shop 1: Krótki + Długi opis
- Shop 2: Krótki + Długi opis
- Przycisk "Użyj opisów domyślnych"

**Tab 4: CENY (grid layout)**

Grupy cenowe (editable table):

| Grupa Cenowa | Cena Netto | Cena Brutto | Marża % |
|--------------|------------|-------------|---------|
| Detaliczna | input | auto | input |
| Dealer Standard | input | auto | input |
| Dealer Premium | input | auto | input |
| Warsztat | input | auto | input |
| Warsztat Premium | input | auto | input |
| Szkółka-Komis-Drop | input | auto | input |
| Pracownik | input | auto | input |

*Kalkulator marży (sidebar):*
- Cena zakup netto (input)
- Marża domyślna (%) (input)
- Auto-calculate dla wszystkich grup

*Przyciski:*
- "Zastosuj marżę do wszystkich"
- "Kopiuj z innego produktu"

**Tab 5: STANY MAGAZYNOWE (grid layout)**

Magazyny (editable table):

| Magazyn | Stan | Lokalizacja | Status dostawy | Data dostawy |
|---------|------|-------------|----------------|--------------|
| MPPTRADE | input | input | dropdown | datepicker |
| Pitbike.pl | input | input | dropdown | datepicker |
| Cameraman | input | input | dropdown | datepicker |

*Status dostawy dropdown:*
- Zamówione
- Nie zamówione
- Anulowany
- W kontenerze (nr kontenera - autocomplete)
- Opóźnienie (show delay counter)
- W trakcie przyjęcia

*Bulk actions:*
- "Ustaw status dla wszystkich magazynów"
- "Kopiuj stany z innego produktu"

*Alert:* Stan minimalny (input + email notification toggle)

**Tab 6: WARIANTY (jeśli produkt ma warianty)**

Lista wariantów (tabela):

| SKU Wariantu | Atrybuty | Cena | Stan | Zdjęcia | Status | Akcje |
|--------------|----------|------|------|---------|--------|-------|
| PROD-001-RED | Kolor: Czerwony | 150 | 10 | 3 | Active | Edit/Delete |

- Przycisk "Dodaj wariant"
- Modal dodawania wariantu:
  - SKU wariantu (auto-generate option)
  - Wybór atrybutów (Kolor, Rozmiar, etc.)
  - Dziedzicz ceny z produktu matki (checkbox)
  - Dziedzicz stany (checkbox)
  - Własne zdjęcia (upload)

**Tab 7: ZDJĘCIA (max 20)**

*Upload zone (drag & drop):*
- Formaty: JPG, JPEG, PNG, WEBP
- Max size: 5MB per file
- Bulk upload (multi-select)

*Gallery grid (sortable):*
- Thumbnails z preview
- Drag & drop reorder
- Oznacz główne zdjęcie (star icon)
- Akcje per zdjęcie: View, Delete
- Label: "Na sklepach: YCF, PitBike"

- Przycisk "Kopiuj z innego produktu"

**Tab 8: CECHY POJAZDÓW (jeśli Typ = Pojazd)**

*Template selector:*
- Dropdown: Pojazdy elektryczne / Pojazdy spalinowe / Custom

*Lista cech (dynamic form):*

| Cecha | Wartość |
|-------|---------|
| VIN | input |
| Rok produkcji | input (number) |
| Engine No. | input |
| Przebieg | input (number) + jednostka (km/mile) |

- Przycisk "Dodaj cechę" (custom cecha)

**Tab 9: DOPASOWANIA CZĘŚCI (jeśli Typ = Część Zamienna)**

*Filtr sklepu:* Dropdown (All / YCF / PitBike / ...)

*Sekcja ORYGINAŁ:*
- Multi-select searchable (produkty Typ=Pojazd, filtered by Producent)
- Lista wybranych z możliwością usunięcia

*Sekcja ZAMIENNIK:*
- Multi-select searchable (produkty Typ=Pojazd, excluding Oryginał)
- Lista wybranych z możliwością usunięcia

*Sekcja MODEL (auto-generated, read-only):*
- Lista wszystkich (Oryginał + Zamiennik)
- Info: "Auto-generowane z Oryginał + Zamiennik"

*Per-Shop dopasowania (tabs per sklep):*
- Shop 1: Oryginał / Zamiennik / Model
- Shop 2: Oryginał / Zamiennik / Model
- Filtrowanie: tylko pojazdy z Producentem określonym dla sklepu

**Tab 10: META & SEO (opcjonalne)**
- Meta Title (tekstowe, max 70 znaków)
- Meta Description (textarea, max 160 znaków)
- URL Key (tekstowe, slug format)
- Tagi (multi-input, separator ";")

**Tab 11: NOTATKI WEWNĘTRZNE**
- Notatki (textarea, unlimited)
- Historia zmian (timeline):
  - Kto zmienił
  - Co zmienił
  - Kiedy zmienił

**Tab 12: DANE ZE SKLEPÓW (per-shop tabs)**

*Shop 1 (YCF) (read-only preview):*
- PrestaShop ID
- URL do produktu
- Status synchronizacji
- Ostatnia synchronizacja
- Różnice (diff view):
  - Nazwa: PPM vs PrestaShop
  - Cena: PPM vs PrestaShop
  - Stan: PPM vs PrestaShop
- Przycisk "Synchronizuj teraz"
- Przycisk "Pobierz dane ze sklepu"

**Footer Actions (sticky):**
- Przycisk "Zapisz" (primary, duży)
- Przycisk "Zapisz i eksportuj na sklepy" (dropdown z wyborem sklepów)
- Przycisk "Duplikuj produkt"
- Przycisk "Anuluj"

---

#### 3.3 Kategorie

**Route:** `/admin/products/categories`
**Uprawnienia:** Admin, Menadżer, Redaktor

**Zawartość:**

**Category Tree View (hierarchical, 5 poziomów):**
- Expand/collapse nodes
- Drag & drop reorder (within level)
- Liczba produktów per kategoria (badge)
- Akcje per kategoria: Edit, Add Child, Delete

**Breadcrumb (current selection):**
- Kategoria → Kategoria1 → Kategoria2 → ...

**Category Form (sidebar lub modal):**
- Nazwa kategorii (required)
- Kategoria nadrzędna (dropdown, 4 poziomy)
- Opis kategorii (textarea)
- Status aktywny/nieaktywny
- Zdjęcie kategorii (upload)
- Sortowanie (number, position)

**Category Actions:**
- Przycisk "Dodaj kategorię główną"
- Przycisk "Import kategorii z PrestaShop" (wybór sklepu)
- Przycisk "Eksport kategorii do CSV"

**Bulk Operations (po zaznaczeniu):**
- Masowa zmiana statusu
- Masowe przeniesienie (zmiana parent)
- Masowe usunięcie (z walidacją czy są produkty)

---

#### 3.4 Wyszukiwarka

**Route:** `/admin/products/search`
**Uprawnienia:** Wszyscy

**Inteligentna wyszukiwarka (fullscreen):**

**Search Bar (główny input):**
- Placeholder: "Wyszukaj po SKU, nazwie, kategorii, producencie..."
- Live autocomplete suggestions (dropdown):
  - Produkty (SKU + Nazwa)
  - Kategorie
  - Producenci
- Ikony: Search, Clear, Advanced filters

**Search Mode:**
- Toggle: "Wyszukaj dokładnie" (exact match)
- Default: Fuzzy search (tolerancja błędów, literówki)

**Advanced Filters (collapsible panel):**
- Typ produktu (multi-select)
- Kategoria (tree picker)
- Producent (multi-select)
- Zakres cen (slider: min-max)
- Zakres stanów (slider: min-max)
- Status (aktywny / nieaktywny / wszystkie)
- Sklepy (multi-select: tylko produkty na wybranych)
- Data dodania (date range)

**Search Results:**
- Liczba wyników (badge)
- Sortowanie: Relevance / Nazwa / SKU / Cena / Stan
- View mode: List / Grid
- Tabela/Grid produktów (jak w Lista Produktów)
- Pagination

**Default View (przed wyszukaniem):**

*Statystyki PPM (KPI cards):*
- Total Products
- Total Categories
- Products by Type (pie chart)
- Low Stock Alerts (number)

- Komunikat: "Wyszukaj towar, aby zobaczyć szczegóły"

---

### 4. Cennik

#### 4.1 Grupy Cenowe

**Route:** `/admin/price-management/price-groups`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Lista grup cenowych (tabela):**

| Nazwa Grupy | Opis | Domyślna Marża % | Liczba Produktów | Status | Akcje |
|-------------|------|------------------|------------------|--------|-------|
| Detaliczna | Cena dla klientów detalicznych | 60% | 1245 | Active | Edit |
| Dealer Standard | ... | 40% | 890 | Active | Edit |

**Header Actions:**
- Przycisk "Dodaj grupę cenową" (custom group)
- Przycisk "Import grup z PrestaShop"

**Formularz grupy (modal lub sidebar):**
- Nazwa grupy (required)
- Opis (textarea)
- Domyślna marża % (number)
- Status aktywny/nieaktywny
- Mapowanie PrestaShop ID (per sklep)

**Bulk Actions:**
- Masowa zmiana marży dla grupy
- Masowy eksport cen do CSV

---

#### 4.2 Ceny Produktów

**Route:** `/admin/price-management/product-prices`
**Uprawnienia:** Admin, Menadżer, Redaktor

**Zawartość:**

**Filtry:**
- Szukaj produktu (SKU, Nazwa)
- Kategoria
- Grupa cenowa (multi-select)
- Zakres cen (slider)

**Tabela cen (editable inline):**

| SKU | Nazwa | Detaliczna | Dealer Std | Dealer Premium | Warsztat | ... | Akcje |
|-----|-------|------------|------------|----------------|----------|-----|-------|
| PROD-001 | Test | [edit] 150 | [edit] 120 | [edit] 110 | [edit] 130 | ... | Save |

**Inline Editing:**
- Click to edit price
- Auto-calculate marża (pokazuje obok)
- Enter to save, Esc to cancel

**Bulk Operations Bar:**
- "Zastosuj marżę X% do zaznaczonych"
- "Kopiuj ceny z produktu X"
- "Eksportuj do CSV"
- "Importuj z CSV"

---

#### 4.3 Aktualizacja Masowa

**Route:** `/admin/price-management/bulk-updates`
**Uprawnienia:** Admin, Menadżer

**Wizard aktualizacji cen:**

**Krok 1:** Wybór produktów
- Wszystkie produkty
- Produkty z kategorii (tree picker)
- Produkty według filtrów (Producent, Typ)
- Import listy SKU (textarea lub CSV upload)

**Krok 2:** Wybór grup cenowych
- Multi-select grup cenowych

**Krok 3:** Akcja
- Ustaw marżę % (input)
- Zwiększ o % (input)
- Zmniejsz o % (input)
- Ustaw cenę stałą (input)

**Krok 4:** Preview zmian
- Tabela: SKU | Stara cena | Nowa cena | Różnica %
- Podsumowanie: X produktów, Y grup cenowych

**Krok 5:** Wykonaj
- Przycisk "Zastosuj zmiany"
- Progress bar (real-time)
- Log operacji

---

### 5. Warianty & Cechy

#### 5.1 Zarządzanie Wariantami

**Route:** `/admin/variants`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Filtry:**
- Produkt rodzic (searchable)
- Typ atrybutu (Kolor, Rozmiar, etc.)

**Tabela wariantów:**

| SKU Wariantu | Produkt Rodzic | Atrybuty | Cena | Stan | Zdjęcia | Status | Akcje |
|--------------|----------------|----------|------|------|---------|--------|-------|
| PROD-001-RED | PROD-001 Test | Kolor: Czerwony | 150 | 10 | 3 | Active | Edit/Del |

**Header Actions:**
- Przycisk "Generuj warianty automatycznie"
- Przycisk "Import wariantów z CSV"

**Auto-Generate Modal:**
- Wybór produktu rodzica
- Wybór atrybutów (Kolor, Rozmiar)
- Wartości atrybutów (multi-input)
- Auto-generate SKU pattern (PARENT-ATTR1-ATTR2)
- Preview: wygeneruje X wariantów

**Bulk Operations:**
- Masowa zmiana cen wariantów
- Masowa zmiana stanów
- Masowe przypisanie zdjęć

---

#### 5.2 Cechy Pojazdów

**Route:** `/admin/features/vehicles`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Template Management:**

*Lista templateów (cards):*
- Pojazdy elektryczne (15 cech)
- Pojazdy spalinowe (20 cech)
- Custom templates (user-defined)
- Przycisk "Dodaj template"

**Template Editor (modal):**

*Nazwa template (required)*

*Lista cech (sortable, drag & drop):*

| Nazwa Cechy | Typ Wartości | Wymagana | Domyślna Wartość | Akcje |
|-------------|--------------|----------|------------------|-------|
| VIN | text | Yes | - | Delete |
| Rok produkcji | number | Yes | 2024 | Delete |
| Engine No. | text | No | - | Delete |

- Przycisk "Dodaj cechę"

**Feature Library (sidebar):**

*Gotowe cechy do wyboru:*
- VIN
- Rok produkcji
- Engine No.
- Przebieg
- Typ silnika
- Moc (KM)
- Pojemność (cm3)
- ... (50+ cech standardowych)

**Bulk Assign:**
- "Zastosuj template do produktów" (wybór produktów + template)

---

#### 5.3 Dopasowania Części

**Route:** `/admin/compatibility`
**Uprawnienia:** Admin, Menadżer, Redaktor

**Zawartość:**

**Dedicated Panel:**

**Filtry:**
- Część zamienna (searchable SKU/Name)
- Sklep PrestaShop (dropdown)
- Producent pojazdu (multi-select)
- Status dopasowania (Pełne / Częściowe / Brak)

**Tabela części:**

| SKU Części | Nazwa | Oryginał (count) | Zamiennik (count) | Model (count) | Status | Akcje |
|------------|-------|------------------|-------------------|---------------|--------|-------|
| PART-001 | Filtr | 5 | 3 | 8 | Full | Edit |

**Bulk Edit Modal:**
- Wybór części (multi-select)
- Dodaj do Oryginał (searchable pojazdów)
- Dodaj do Zamiennik (searchable pojazdów)
- Usuń z dopasowań (multi-select pojazdów)
- Preview zmian
- Zastosuj

**Vehicle List per Part (expand row):**

*Oryginał:*
- Lista pojazdów (badges)
- Przycisk "Dodaj pojazd"
- Remove icon per pojazd

*Zamiennik:*
- Lista pojazdów (badges)
- Przycisk "Dodaj pojazd"
- Remove icon per pojazd

*Model (auto-generated, read-only):*
- Suma Oryginał + Zamiennik (badges)

**Import/Export:**
- Przycisk "Import dopasowań z CSV"
- Przycisk "Eksport dopasowań do CSV"
- Przycisk "Generuj szablon CSV"

---

### 6. Zarządzanie (Import/Export)

#### 6.1 CSV Import/Export

**Route:** `/admin/csv/import`
**Uprawnienia:** Admin, Menadżer, Redaktor
**Status:** NEW - FAZA 6

**Zawartość:**

**Upload Section:**
- Drag & drop zone (CSV, XLSX, TXT)
- Max size: 10MB
- Wybór typu importu (dropdown):
  - Produkty (complete)
  - Warianty
  - Cechy
  - Dopasowania

**Template Download:**

*Przyciski "Pobierz szablon":*
- Szablon: Produkty
- Szablon: Warianty
- Szablon: Cechy
- Szablon: Dopasowania

**Import Wizard (steps):**

**Step 1:** Upload pliku

**Step 2:** Podgląd i walidacja
- Preview pierwszych 10 wierszy
- Column mapping (auto-detect + manual adjust)
- Validation errors (red highlighting)
- Conflict resolution (skip / overwrite / update)

**Step 3:** Import
- Progress bar (real-time)
- Statistics: Success / Failed / Skipped
- Error report (downloadable CSV)

---

#### 6.2 Import XLSX

**Route:** `/admin/import`
**Uprawnienia:** Admin, Menadżer
**Status:** ETAP 6

**Zawartość:**

**Template Selector:**
- Dropdown: POJAZDY / CZĘŚCI
- Load saved mapping (dropdown user templates)

**Upload Zone:**
- Drag & drop XLSX files
- Max size: 50MB (larger files)

**Column Mapping (interactive):**
- Source column (from XLSX) → Target field (in PPM)
- Auto-mapping based on template
- Manual adjust (drag & drop)
- Ignore column (checkbox)
- Preview mapping (sample data)

**Import Options:**
- ID kontenera (textbox lub dropdown existing)
- Data dostawy (datepicker)
- Conflict resolution:
  - Skip duplicates
  - Overwrite existing
  - Update existing
- Advanced:
  - Import zdjęcia (checkbox + URL column mapping)
  - Import dokumentów (checkbox)

**Validation & Import:**
- Przycisk "Waliduj dane"
- Preview errors (table with row number + error description)
- Przycisk "Rozpocznij import"
- Real-time progress (bar + statistics)
- Download error report (CSV)

---

#### 6.3 Historie Importów

**Route:** `/admin/import-history`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Tabela importów:**

| Data | Użytkownik | Typ Importu | Plik | Status | Sukces | Błędy | Akcje |
|------|------------|-------------|------|--------|--------|-------|-------|
| 2025-10-20 | admin@mpptrade.pl | XLSX Części | parts_2025.xlsx | Completed | 450 | 12 | View/Download |

**Filtry:**
- Data (date range)
- Użytkownik (dropdown)
- Status (Success / Failed / Partial)
- Typ (XLSX / CSV)

**Akcje per import:**
- View details (modal z full log)
- Download error report
- Re-import (retry failed rows)
- Delete history entry

---

### 7. Integracje ERP

**Status:** ETAP 8

#### 7.1 BaseLinker

**Route:** `/admin/integrations/baselinker`
**Uprawnienia:** Admin

**Zawartość:**

**Connection Status Panel:**
- Status: Connected / Disconnected (badge)
- API Key (masked, edit button)
- Last sync: timestamp
- Sync frequency: dropdown (15min / 1h / 6h / 24h)
- Test connection button

**Sync Settings:**

*Products Sync (toggle + options):*
- Direction: PPM → BL / BL → PPM / Bidirectional
- Auto-sync (checkbox)
- Sync zdjęcia (checkbox)
- Sync stany (checkbox)
- Sync ceny (checkbox)

*Warehouses Mapping:*
- Tabela: PPM Warehouse → BaseLinker Warehouse (dropdown)

*Price Groups Mapping:*
- Tabela: PPM Price Group → BaseLinker Price Type (dropdown)

*Categories Mapping:*
- Tree view: PPM Category → BaseLinker Category

**Sync Actions:**
- Przycisk "Synchronizuj produkty teraz"
- Przycisk "Import produktów z BaseLinker"
- Przycisk "Export produktów do BaseLinker"

**Sync Logs (tabela):**
- Timestamp | Direction | Entity | Status | Details
- Pagination

---

#### 7.2 Subiekt GT

**Route:** `/admin/integrations/subiekt`
**Uprawnienia:** Admin

**Zawartość:**

**Connection Status Panel:**
- Status: Connected / Disconnected
- Connection type: DLL / COM / API
- Database path (textbox)
- Version: Subiekt GT 2024 (detected)
- Test connection button

**Sync Settings (similar to BaseLinker):**
- Products sync direction
- Warehouses mapping
- Price groups mapping
- Orders sync (toggle)
- Documents sync (toggle)

**Advanced Settings:**
- Document types mapping (Zamówienie, Faktura, etc.)
- Lokalizacja magazynowa sync (checkbox)

**Sync Actions:**
- "Synchronizuj dane teraz"
- "Import z Subiekt GT"
- "Export do Subiekt GT"

**Sync Logs**

---

#### 7.3 Microsoft Dynamics

**Route:** `/admin/integrations/dynamics`
**Uprawnienia:** Admin

**Zawartość:**

**Connection Status Panel:**
- Status: Connected / Disconnected
- Tenant ID (textbox)
- Client ID (textbox)
- Client Secret (password, masked)
- OData API URL (textbox)
- OAuth Token status (Valid / Expired)
- Refresh token button

**Sync Settings (similar to others)**

**Entity Mappings (advanced):**
- PPM Products → Dynamics Items
- PPM Orders → Dynamics Sales Orders
- PPM Customers → Dynamics Customers

**Sync Actions**

**Sync Logs**

---

### 8. Dostawy & Kontenery

**Status:** ETAP 10

#### 8.1 Lista Dostaw

**Route:** `/admin/deliveries`
**Uprawnienia:** Admin, Menadżer, Magazynier

**Zawartość:**

**Filtry:**
- Status dostawy (dropdown: Wszystkie / Zamówione / W kontenerze / Opóźnienie / W trakcie przyjęcia / Zakończone)
- Data dostawy (date range)
- Dostawca (dropdown)
- ID Kontenera (search)

**Tabela dostaw:**

| ID Kontenera | Dostawca | Data Zamówienia | Data Dostawy | Status | Liczba ORDER | Liczba Produktów | Wartość | Akcje |
|--------------|----------|-----------------|--------------|--------|--------------|------------------|---------|-------|
| CNT-2025-001 | Supplier A | 2025-08-01 | 2025-09-15 | W kontenerze | 5 | 450 | 125,000 PLN | View/Edit |

**Status badges (color-coded):**
- Zamówione (blue)
- W kontenerze (orange)
- Opóźnienie (red + days counter)
- W trakcie przyjęcia (yellow)
- Zakończone (green)

**Header Actions:**
- Przycisk "Nowa dostawa"
- Przycisk "Import z XLSX"

---

#### 8.2 Kontenery

**Route:** `/admin/deliveries/containers/{id}`
**Uprawnienia:**
- Admin, Menadżer: Full access
- Magazynier: Edit quantities, upload documents

**Szczegóły kontenera:**

**Header Info:**
- ID Kontenera (duży nagłówek)
- Status badge
- Data zamówienia / Data dostawy
- Dostawca
- Liczba ORDER / produktów
- Wartość całkowita

**Tabs:**

**Tab 1: ORDERS**

*Lista ORDER (collapsible cards):*
- ORDER ID (header)
- Liczba produktów w ORDER
- Status (per ORDER)
- Expand: Lista produktów w ORDER (tabela)

| SKU | Nazwa | Qty Zamówiona | Qty Rzeczywista | Status | Uwagi |
|-----|-------|---------------|-----------------|--------|-------|
| ... | ... | ... | editable | dropdown | editable |

**Tab 2: DOKUMENTY ODPRAW**

*Upload zone (multiple files):*
- Formaty: ZIP, XLSX, PDF, XML
- Max size per file: 20MB

*Lista dokumentów (table):*

| Nazwa Pliku | Typ | Rozmiar | Data Uploadu | Akcje |
|-------------|-----|---------|--------------|-------|
| odprawa.zip | ZIP | 15MB | 2025-09-10 | Download/Delete |

**Tab 3: HISTORIA PRZYJĘĆ**
- Timeline zmian statusów
- Kto przyjmował
- Różnice ilościowe
- Uwagi magazynu
- Zdjęcia (jeśli dodane przez app Android)

**Actions Footer:**
- Przycisk "Zamknij dostawę" (creates document in ERP)
- Przycisk "Eksportuj do CSV"
- Przycisk "Drukuj raport"

---

#### 8.3 Przyjęcia Magazynowe

**Route:** `/admin/deliveries/receiving`
**Uprawnienia:** Magazynier, Admin, Menadżer

**Zawartość:**

**Active Receipts (cards):**
- Kontener ID
- Data rozpoczęcia przyjęcia
- Progress bar (% produktów zweryfikowanych)
- Użytkownik przyjmujący
- Akcje: Continue / Complete / Cancel

**Receiving Interface (per kontener):**

*Scanner Integration:*
- Barcode input (auto-focus)
- Manual SKU input (fallback)
- Match status (Found / Not Found)

*Product Verification (current product):*
- SKU (big display)
- Nazwa produktu
- Zdjęcie produktu
- Qty zamówiona (display)
- Qty rzeczywista (editable):
  - Zgodne / Niezgodne toggle
  - Manual input (if Niezgodne)
  - +/- buttons
- Uwagi (textarea)
- Upload zdjęcia (opcjonalne, camera button)
- Przyciski: Potwierdź / Pomiń

*Progress Tracking:*

*Lista produktów (sidebar):*
- Zweryfikowane (green check)
- Niezgodne (red alert)
- Pozostałe (grey)

**Actions:**
- Przycisk "Zakończ przyjęcie"
- Przycisk "Wstrzymaj przyjęcie" (save progress)

---

#### 8.4 Dokumenty Odpraw

**Route:** `/admin/deliveries/documents`
**Uprawnienia:** Admin, Menadżer, Magazynier

**Zawartość:**

**Filtry:**
- Kontener (dropdown)
- Typ dokumentu (ZIP / PDF / XLSX / XML)
- Data (date range)

**Grid dokumentów (cards with preview):**
- Thumbnail (if image/PDF)
- Nazwa pliku
- Typ
- Kontener
- Data uploadu
- Rozmiar
- Akcje: Download / View / Delete

**Bulk Actions:**
- Download selected (as ZIP)
- Delete selected

---

### 9. Zamówienia

**Status:** ETAP 10

#### 9.1 Lista Zamówień

**Route:** `/admin/orders`
**Uprawnienia:** Admin, Menadżer, Handlowiec

**Zawartość:**

**Filtry:**
- Status zamówienia (Pending / Confirmed / Shipped / Delivered / Cancelled)
- Data zamówienia (date range)
- Klient (search)
- Źródło (Manual / BaseLinker / PrestaShop / ...)

**Tabela zamówień:**

| Nr Zamówienia | Data | Klient | Źródło | Status | Liczba Pozycji | Wartość | Akcje |
|---------------|------|--------|--------|--------|----------------|---------|-------|
| ORD-2025-001 | 2025-10-20 | Jan Kowalski | PrestaShop YCF | Pending | 5 | 1,250 PLN | View/Edit |

**Header Actions:**
- Przycisk "Nowe zamówienie" (manual)
- Przycisk "Import z BaseLinker"

---

#### 9.2 Rezerwacje z Kontenera

**Route:** `/admin/orders/reservations`
**Uprawnienia:**
- Admin, Menadżer: Full access
- Handlowiec: Can reserve, no purchase prices

**Zawartość:**

**Kontener Selector:**
- Dropdown: Kontenery "W kontenerze" lub "W trakcie przyjęcia"
- Info: Data dostawy, Liczba produktów dostępnych

**Available Products (from selected container):**

Tabela:

| SKU | Nazwa | Qty Dostępna | Qty Zarezerwowana | Qty Do Rezerwacji | Klient | Akcje |
|-----|-------|--------------|-------------------|-------------------|--------|-------|
| PROD-001 | Test | 50 | 10 | [input] | [dropdown] | Reserve |

**Reservations List (existing):**

Tabela:

| SKU | Nazwa | Qty | Klient | Data Rezerwacji | Status | Akcje |
|-----|-------|-----|--------|-----------------|--------|-------|
| PROD-002 | ... | 5 | Jan Kowalski | 2025-10-15 | Active | Cancel |

**Reservation Actions:**
- Przycisk "Dodaj rezerwację"
- Przycisk "Anuluj rezerwację"

**Restrictions for Handlowiec:**
- Brak widoczności cen zakupu
- Tylko ceny detaliczne i Dealer

---

#### 9.3 Historia Zamówień

**Route:** `/admin/orders/history`
**Uprawnienia:** Admin, Menadżer, Handlowiec

**Zawartość:**
- Filtry (jak w Lista Zamówień)
- Tabela (archived orders)

**Export:**
- Przycisk "Eksportuj do CSV"
- Przycisk "Eksportuj do PDF"

---

### 10. Reklamacje

**Status:** ETAP 10 - System Reklamacji

#### 10.1 Lista Reklamacji

**Route:** `/admin/claims`
**Uprawnienia:** Admin, Menadżer, Reklamacje

**Zawartość:**

**Filtry:**
- Status (Nowa / W trakcie / Zamknięta / Odrzucona)
- Data zgłoszenia (date range)
- Klient (search)
- Produkt (SKU search)
- Typ reklamacji (Produkt wadliwy / Niezgodność / Reklamacja dostawcy / ...)

**Tabela reklamacji:**

| Nr Reklamacji | Data | Klient | Produkt (SKU) | Typ | Status | Priorytet | Akcje |
|---------------|------|--------|---------------|-----|--------|-----------|-------|
| RMA-2025-001 | 2025-10-18 | Jan Kowalski | PROD-123 | Wadliwy | W trakcie | High | View/Edit |

**Status badges (color-coded):**
- Nowa (blue)
- W trakcie (orange)
- Zamknięta (green)
- Odrzucona (red)

**Header Actions:**
- Przycisk "Nowa reklamacja"

---

#### 10.2 Nowa Reklamacja

**Route:** `/admin/claims/create`
**Uprawnienia:** Admin, Menadżer, Reklamacje

**Formularz reklamacji:**

**Dane podstawowe:**
- Numer zamówienia (autocomplete)
- Klient (autocomplete lub ręczne)
- Produkt (SKU autocomplete)

**Szczegóły reklamacji:**
- Typ reklamacji (dropdown)
- Priorytet (Low / Medium / High / Critical)
- Opis problemu (textarea, wymagane)
- Ilość reklamowanych sztuk (number)

**Załączniki:**
- Upload zdjęć (multiple, drag & drop)
- Upload dokumentów (PDF, DOCX)

**Akcje:**
- Przyciski: Zapisz / Zapisz i wyślij email / Anuluj

---

#### 10.3 Archiwum Reklamacji

**Route:** `/admin/claims/archive`
**Uprawnienia:** Admin, Menadżer, Reklamacje

**Zawartość:**
- Filtry (jak w Lista Reklamacji + Rok)
- Tabela (archived claims, read-only)

**Export:**
- "Eksportuj do CSV"
- "Raport reklamacji" (PDF)

---

### 11. System (Admin Panel)

**Status:** COMPLETED (ETAP_04 - 5 faz)

#### 11.1 Ustawienia Systemu

**Route:** `/admin/system-settings`
**Uprawnienia:** Admin only

**Kategoryzowane ustawienia (tabs):**

**Tab 1: OGÓLNE**
- Nazwa aplikacji (textbox)
- Logo aplikacji (upload)
- Język domyślny (dropdown: PL / EN)
- Strefa czasowa (dropdown)
- Format daty (dropdown)
- Format liczb (dropdown)

**Tab 2: EMAIL & NOTYFIKACJE**
- SMTP Server (textbox)
- SMTP Port (number)
- SMTP User (textbox)
- SMTP Password (password)
- Email nadawcy (email)
- Test email button
- Powiadomienia email:
  - Niski stan (checkbox)
  - Błędy synchronizacji (checkbox)
  - Nowe reklamacje (checkbox)

**Tab 3: CENY & MAGAZYNY**
- Domyślna marża % (number)
- Przesunięcie daty dostawy (days, number)
- Próg niskiego stanu (number)
- Lista magazynów (editable table):

| Nazwa Magazynu | Aktywny | Sortowanie | Akcje |
|----------------|---------|------------|-------|
| MPPTRADE | Yes | 1 | Edit/Delete |

- Przycisk "Dodaj magazyn"

**Tab 4: INTEGRACJE**

*PrestaShop:*
- Domyślny timeout (seconds)
- Retry attempts (number)

*BaseLinker:*
- API Key (password)
- Test connection

*Webhooks:*
- Lista webhooks (URL, Event, Active)

**Tab 5: ZAAWANSOWANE**
- Debug mode (toggle)
- Maintenance mode (toggle)
- Cache TTL (minutes)
- Session timeout (minutes)
- Max upload size (MB)

**Footer Actions:**
- Przycisk "Zapisz wszystkie ustawienia"
- Przycisk "Resetuj do domyślnych"

---

#### 11.2 Zarządzanie Użytkownikami

**Route:** `/admin/users`
**Uprawnienia:** Admin only

**Zawartość:**

**Tabela użytkowników:**

| Nazwa | Email | Rola | Status | Data Utworzenia | Ostatnie Logowanie | Akcje |
|-------|-------|------|--------|-----------------|-------------------|-------|
| Jan Kowalski | jan@example.com | Menadżer | Active | 2025-01-15 | 2025-10-20 10:30 | Edit/Delete |

**Filtry:**
- Rola (multi-select)
- Status (Active / Inactive / All)
- Data utworzenia (date range)

**Header Actions:**
- Przycisk "Dodaj użytkownika"
- Przycisk "Zaproś przez email"

**User Form (modal):**
- Imię i nazwisko (required)
- Email (required, unique)
- Rola (dropdown: 7 poziomów)
- Status (Active / Inactive)
- Generuj hasło (button) lub Manual input
- Wyślij zaproszenie email (checkbox)

**Bulk Actions:**
- Masowa zmiana roli
- Masowa dezaktywacja

---

#### 11.3 Backup & Restore

**Route:** `/admin/backup`
**Uprawnienia:** Admin only

**Zawartość:**

**Backup Schedule:**
- Auto backup (toggle)
- Częstotliwość (dropdown: Daily / Weekly / Monthly)
- Czas wykonania (time picker)
- Miejsce zapisu (dropdown):
  - Google Drive (OAuth connect)
  - SharePoint (OAuth connect)
  - NAS Synology (IP + credentials)

**Manual Backup:**
- Przycisk "Utwórz backup teraz"
- Opcje:
  - Full backup (database + files)
  - Database only
  - Files only
- Progress bar (real-time)

**Backup History (tabela):**

| Data | Typ | Rozmiar | Lokalizacja | Status | Akcje |
|------|-----|---------|-------------|--------|-------|
| 2025-10-20 | Full | 2.5 GB | Google Drive | Success | Download/Restore/Delete |

**Restore Interface:**
- Select backup (dropdown)
- Preview backup info
- Restore options:
  - Full restore
  - Database only
  - Selective restore (choose files/tables)
- Przycisk "Rozpocznij restore" (with confirmation modal)

---

#### 11.4 Konserwacja Bazy

**Route:** `/admin/maintenance`
**Uprawnienia:** Admin only

**Zawartość:**

**Database Health (cards):**
- Database size (GB)
- Number of tables
- Number of records
- Index health (% optimized)
- Fragmentation level (%)

**Maintenance Tasks:**
- Przycisk "Optimize Tables" (with progress)
- Przycisk "Repair Tables" (with confirmation)
- Przycisk "Clear Old Logs" (older than X days, input)
- Przycisk "Rebuild Indexes"
- Przycisk "Vacuum Database" (PostgreSQL) / "Optimize Database" (MySQL)

**Scheduled Tasks (cron jobs):**

Lista zadań (table):

| Task Name | Schedule | Last Run | Next Run | Status | Actions |
|-----------|----------|----------|----------|--------|---------|
| Daily Cleanup | Daily 2 AM | 2025-10-20 02:00 | 2025-10-21 02:00 | Success | Edit |

- Przycisk "Add Task"

**Query Monitor (slow queries):**
- Tabela slow queries (> 1s)
- Query text
- Execution time
- Frequency
- Recommendation (auto-generated)

---

#### 11.5 Logi Systemowe

**Route:** `/admin/logs`
**Uprawnienia:** Admin only

**Zawartość:**

**Log Viewer (filterable):**

*Filtry:*
- Level (Debug / Info / Warning / Error / Critical)
- Date range
- Module (Auth / Products / Sync / ERP / ...)
- User (dropdown)

*Tabela logów:*

| Timestamp | Level | Module | User | Message | Details |
|-----------|-------|--------|------|---------|---------|
| 2025-10-20 10:45:32 | ERROR | PrestaShop | admin@mpptrade.pl | Sync failed: Connection timeout | View |

- Pagination (infinite scroll lub standard)

**Log Details Modal:**
- Full message
- Stack trace (if error)
- Context data (JSON formatted)
- Related logs (timeline)

**Actions:**
- Przycisk "Export logs to CSV"
- Przycisk "Clear old logs" (older than X days)
- Przycisk "Download full log file"

---

#### 11.6 Monitoring

**Route:** `/admin/monitoring`
**Uprawnienia:** Admin only

**Real-time system monitoring:**

**Server Metrics (live updating cards):**
- CPU Usage (gauge chart)
- Memory Usage (gauge chart)
- Disk Space (progress bar)
- Network I/O (line chart)

**Application Metrics:**
- Active Users (number + list)
- Request Rate (requests/min, line chart)
- Response Time (ms, line chart)
- Error Rate (%, line chart)

**Queue Metrics:**
- Jobs Pending (number)
- Jobs Processing (number)
- Jobs Failed (number + list)
- Average Processing Time (seconds)

**Database Metrics:**
- Active Connections (number)
- Query Time (average ms)
- Slow Queries Count (link to Query Monitor)
- Database Size (GB)

**Integration Health (status cards):**
- PrestaShop Shops (X/Y online)
- BaseLinker (online/offline)
- Subiekt GT (online/offline)
- Microsoft Dynamics (online/offline)

**Alerts (timeline):**
- Recent alerts (color-coded by severity)
- Auto-refresh (30s interval)

---

#### 11.7 API Management

**Route:** `/admin/api`
**Uprawnienia:** Admin only

**Zawartość:**

**API Keys (tabela):**

| Key Name | Key (masked) | Created | Last Used | Permissions | Status | Actions |
|----------|--------------|---------|-----------|-------------|--------|---------|
| Mobile App | sk_live_••••••1234 | 2025-01-10 | 2025-10-20 | Read Products, Write Orders | Active | Edit/Revoke |

**Header Actions:**
- Przycisk "Generate New API Key"

**API Key Form (modal):**
- Key name (required)
- Permissions (checklist):
  - Read Products
  - Write Products
  - Read Orders
  - Write Orders
  - Admin Access
- Expiration (dropdown: Never / 30 days / 90 days / 1 year)
- IP Whitelist (textarea, one IP per line)

**API Usage Statistics:**
- Chart: Requests per endpoint (bar chart)
- Chart: Requests over time (line chart)
- Table: Top endpoints by usage

**API Documentation:**
- Link: "View API Documentation" (opens Swagger/OpenAPI UI)

---

### 12. Raporty & Statystyki

**Status:** Menadżer+

#### 12.1 Raporty Produktowe

**Route:** `/admin/reports/products`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Report Builder:**

*Wybór typu raportu (dropdown):*
- Products by Category
- Products by Manufacturer
- Products by Type
- Low Stock Report
- Products without Images
- Products not on any Shop

*Filtry (dynamic based on report type):*
- Date range
- Category
- Manufacturer
- Shop

*Group by (dropdown):* Category / Manufacturer / Shop / None

*Sort by (dropdown):* Name / SKU / Price / Stock

**Preview (button):**
- Tabela z danymi (sample 10 rows)
- Summary statistics (cards)

**Export (buttons):**
- Export to CSV
- Export to XLSX
- Export to PDF
- Schedule Report (email delivery)

---

#### 12.2 Raporty Finansowe

**Route:** `/admin/reports/financial`
**Uprawnienia:** Admin, Menadżer

**Zawartość:**

**Report Types:**
- Sales by Period
- Sales by Product
- Sales by Customer
- Profit Margins
- Price Group Analysis

**Filters:**
- Date range (required)
- Shop (multi-select)
- Product category
- Price group

**Visualizations:**
- Revenue trend (line chart)
- Sales by category (pie chart)
- Top products (bar chart)

**Export Options**

---

#### 12.3 Raporty Magazynowe

**Route:** `/admin/reports/warehouse`
**Uprawnienia:** Admin, Menadżer, Magazynier

**Zawartość:**

**Report Types:**
- Stock Levels Report
- Stock Movement Report
- Low Stock Alert Report
- Delivery Performance
- Container Status Report

**Filters:**
- Date range
- Warehouse (multi-select)
- Category
- Status

**Visualizations:**
- Stock levels by warehouse (stacked bar)
- Movement trend (line chart)

**Export Options**

---

#### 12.4 Eksport Raportów

**Route:** `/admin/reports/export`
**Uprawnienia:** Admin, Menadżer

**Scheduled Reports Manager:**

**Scheduled Reports List:**

| Report Name | Type | Schedule | Recipients | Last Sent | Next Send | Status | Actions |
|-------------|------|----------|------------|-----------|-----------|--------|---------|
| Daily Sales | Financial | Daily 8 AM | admin@mpptrade.pl | 2025-10-20 08:00 | 2025-10-21 08:00 | Active | Edit/Delete |

**Add Scheduled Report:**
- Report type (dropdown)
- Report name (textbox)
- Schedule (cron expression builder):
  - Daily / Weekly / Monthly / Custom
  - Time (time picker)
  - Days of week (if weekly)
- Recipients (email multi-input)
- Format (CSV / XLSX / PDF)
- Filters (dynamic based on report type)

---

### 13. Profil Użytkownika

#### 13.1 Edycja Profilu

**Route:** `/profile/edit`
**Uprawnienia:** Wszyscy (własny profil)

**Zawartość:**

**Personal Information:**
- Avatar (upload, circular crop)
- Imię i nazwisko (textbox)
- Email (textbox, disabled if OAuth)
- Telefon (textbox, optional)
- Firma (textbox, optional)

**Security:**

*Zmiana hasła (if not OAuth):*
- Current password (password)
- New password (password)
- Confirm password (password)

*Two-Factor Authentication:*
- Enable 2FA (toggle)
- QR Code (if enabled)
- Backup Codes (generate/download)

**Preferences:**
- Język interfejsu (dropdown)
- Motyw (Light / Dark / Auto)
- Strefa czasowa (dropdown)
- Powiadomienia email (checklist):
  - Product updates
  - Sync errors
  - Low stock alerts
  - New orders

**Actions:**
- Przycisk "Zapisz zmiany"
- Przycisk "Anuluj"

---

#### 13.2 Aktywne Sesje

**Route:** `/profile/sessions`
**Uprawnienia:** Wszyscy (własne sesje)

**Zawartość:**

**Current Session (highlighted card):**
- Device info (icon + name)
- Browser
- IP address
- Location (if detected)
- Login time
- Badge: "Current Session"

**Other Active Sessions (cards):**
- Device info
- Browser
- IP address
- Location
- Last activity
- Przycisk "Wyloguj tę sesję"

**Actions:**
- Przycisk "Wyloguj wszystkie inne sesje"

---

#### 13.3 Historia Aktywności

**Route:** `/profile/activity`
**Uprawnienia:** Wszyscy (własna aktywność)

**Zawartość:**

**Activity Timeline:**

*Filtry:*
- Date range
- Action type (Login / Edit Product / Sync / Export / ...)

*Timeline items (infinite scroll):*

| Icon | Timestamp | Action | Details |
|------|-----------|--------|---------|
| 🔐 | 2025-10-20 10:30 | Login | IP: 192.168.1.1, Device: Chrome Windows |
| ✏️ | 2025-10-20 10:45 | Edited Product | SKU: PROD-123 "Test Product" |
| 📤 | 2025-10-20 11:00 | Exported to PrestaShop | 5 products → Shop YCF |

**Export Activity:**
- Przycisk "Export activity log to CSV"

---

#### 13.4 Ustawienia Powiadomień

**Route:** `/profile/notifications`
**Uprawnienia:** Wszyscy

**Zawartość:**

**Email Notifications (checklist):**
- Product changes (when someone edits products I own)
- Sync errors (for shops I manage)
- Low stock alerts (for products I track)
- New orders (if Handlowiec)
- New deliveries (if Magazynier)
- New claims (if Reklamacje)
- System announcements

**In-App Notifications (checklist):**
- Real-time notifications (browser push)
- Toast notifications
- Badge counters

**Notification Frequency:**
- Real-time (as they happen)
- Daily digest (email)
- Weekly summary (email)

**Actions:**
- Przycisk "Zapisz ustawienia"

---

### 14. Pomoc

#### 14.1 Dokumentacja

**Route:** `/help`
**Uprawnienia:** Wszyscy

**Zawartość:**

**Search Bar (prominent):**
- Placeholder: "Szukaj w dokumentacji..."
- Live search results (dropdown)

**Categories (cards grid):**
- Getting Started
- Products Management
- Shop Integration
- ERP Integration
- Import/Export
- Reports
- Administration
- Troubleshooting

**Popular Articles (links list):**
- "How to add a new product"
- "How to sync with PrestaShop"
- "How to import products from XLSX"
- "How to map price groups"
- "Understanding product variants"

**Video Tutorials (if available):**
- Embedded videos lub links

---

#### 14.2 Skróty Klawiszowe

**Route:** `/help/shortcuts`
**Uprawnienia:** Wszyscy

**Zawartość:**

**Global Shortcuts (table):**

| Skrót | Akcja |
|-------|-------|
| Ctrl+K | Quick Search |
| Ctrl+N | New Product |
| Ctrl+S | Save (in forms) |
| Ctrl+/ | Show Shortcuts |
| Esc | Close Modal |

**Page-Specific Shortcuts:**

*Products List:*
- Ctrl+A: Select All
- Ctrl+D: Deselect All
- Delete: Delete Selected

*Product Form:*
- Ctrl+Enter: Save and Close
- Ctrl+Shift+S: Save and Export

---

#### 14.3 Wsparcie Techniczne

**Route:** `/help/support`
**Uprawnienia:** Wszyscy

**Zawartość:**

**Contact Form:**
- Subject (textbox, required)
- Category (dropdown: Bug / Feature Request / Question / Other)
- Priority (dropdown: Low / Medium / High / Critical)
- Description (textarea, required, rich text)
- Attachments (upload, multiple files)
- Include system info (checkbox, auto-collects PHP version, Laravel version, etc.)
- Przycisk "Wyślij zgłoszenie"

**Support Tickets (my tickets):**

Tabela:

| Ticket ID | Subject | Status | Priority | Created | Last Update | Actions |
|-----------|---------|--------|----------|---------|-------------|---------|
| TICK-001 | Bug in sync | Open | High | 2025-10-15 | 2025-10-18 | View |

**Contact Information:**
- Email: support@mpptrade.pl
- Phone: +48 123 456 789
- Business Hours: Mon-Fri 9:00-17:00

---

## 🎨 UI/UX Guidelines

### Global Navigation (Top Bar)

**Left Section:**
- Logo PPM (link do dashboard)
- Sidebar toggle button (hamburger icon)

**Center Section:**
- Global Quick Search (Ctrl+K):
  - Placeholder: "Szukaj produktów, SKU, kategorii..."
  - Live results dropdown:
    - Recent searches
    - Products
    - Categories
    - Help articles

**Right Section:**
- Notifications icon (bell) + badge counter
- User menu (avatar + dropdown):
  - Profile
  - Sessions
  - Activity
  - Settings
  - Help
  - Logout

---

### Notifications Panel (Slide-in)

**Header:**
- "Powiadomienia" (title)
- Mark all as read
- Settings icon

**Tabs:**
- All
- Unread
- Mentions

**Notification Items (timeline):**
- Icon + badge (type-specific)
- Title
- Description
- Timestamp
- Actions (per notification):
  - Mark as read
  - Delete
  - Go to related item

**Footer:**
- "Zobacz wszystkie"

---

### Modal Standards

**Header:**
- Title (left)
- Close button (right, X icon)

**Body:**
- Content (forms, tables, etc.)
- Scrollable if long

**Footer:**
- Cancel (left, secondary)
- Primary action (right, primary button)

---

### Toast Notifications

**Types (color-coded):**
- Success (green)
- Error (red)
- Warning (orange)
- Info (blue)

**Position:** Top-right

**Auto-dismiss:** 5 seconds (configurable per type)

**Actions:**
- Dismiss button (X)
- Optional: Undo / View details

---

### Forms Guidelines

**Validation:**
- Real-time validation (on blur)
- Error messages below inputs
- Success indicators (green check)

**Required Fields:**
- Marked with asterisk (*)
- Cannot submit until filled

**Help Text:**
- Below inputs
- Subtle color (gray-500)

**Buttons:**
- Primary: Main action (right-aligned)
- Secondary: Cancel/Back (left-aligned)
- Destructive: Delete/Remove (red, confirmation required)

---

### Tables Guidelines

**Sortable Columns:**
- Click header to sort
- Icon indicators (↑/↓)

**Pagination:**
- Bottom of table
- Items per page selector
- Page numbers + Previous/Next

**Bulk Selection:**
- Checkbox in header (select all)
- Checkboxes per row
- Bulk actions bar appears when items selected

**Row Actions:**
- Icons or dropdown menu (...)
- Common: Edit, View, Delete

---

### Loading States

**Spinners:**
- Full page: Center spinner overlay
- Component: Inline spinner
- Button: Spinner inside button (disabled state)

**Skeleton Screens:**
- For data tables
- For cards/lists
- Preserve layout structure

**Progress Bars:**
- For file uploads
- For sync operations
- Show percentage + estimated time

---

## 🎨 Design System

### Colors (MPP TRADE Brand)

```css
--color-primary: #E0AC7E;        /* Orange/Gold - Primary brand */
--color-secondary: #1F2937;      /* Dark Gray - Secondary */
--color-success: #10B981;        /* Green - Success states */
--color-warning: #F59E0B;        /* Orange - Warnings */
--color-error: #EF4444;          /* Red - Errors */
--color-info: #3B82F6;           /* Blue - Info */

/* Neutrals */
--color-gray-50: #F9FAFB;
--color-gray-100: #F3F4F6;
--color-gray-200: #E5E7EB;
--color-gray-300: #D1D5DB;
--color-gray-400: #9CA3AF;
--color-gray-500: #6B7280;
--color-gray-600: #4B5563;
--color-gray-700: #374151;
--color-gray-800: #1F2937;
--color-gray-900: #111827;

/* Backgrounds */
--color-bg-primary: #FFFFFF;
--color-bg-secondary: #F9FAFB;
--color-bg-tertiary: #F3F4F6;
```

---

### Typography

**Font Family:**
```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

**Headings:**
```css
h1 { font-size: 2.25rem; font-weight: 700; line-height: 2.5rem; }   /* 36px */
h2 { font-size: 1.875rem; font-weight: 600; line-height: 2.25rem; } /* 30px */
h3 { font-size: 1.5rem; font-weight: 600; line-height: 2rem; }      /* 24px */
h4 { font-size: 1.25rem; font-weight: 600; line-height: 1.75rem; }  /* 20px */
h5 { font-size: 1.125rem; font-weight: 600; line-height: 1.75rem; } /* 18px */
h6 { font-size: 1rem; font-weight: 600; line-height: 1.5rem; }      /* 16px */
```

**Body:**
```css
body {
  font-size: 14px;    /* Base */
  line-height: 1.5;
}

.text-sm { font-size: 0.875rem; }   /* 14px */
.text-base { font-size: 1rem; }     /* 16px */
.text-lg { font-size: 1.125rem; }   /* 18px */
```

---

### Spacing

**Unit:** 4px base

**Scale:**
```css
--spacing-1: 4px;
--spacing-2: 8px;
--spacing-3: 12px;
--spacing-4: 16px;
--spacing-5: 20px;
--spacing-6: 24px;
--spacing-8: 32px;
--spacing-10: 40px;
--spacing-12: 48px;
--spacing-16: 64px;
```

**Usage:**
- Padding between elements: 16px (--spacing-4)
- Section padding: 24px (--spacing-6)
- Page margins: 32px (--spacing-8)

---

### Buttons

**Primary:**
```css
.btn-primary {
  background: var(--color-primary);
  color: white;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s;
}
.btn-primary:hover {
  background: #D19B6D; /* Darker shade */
}
```

**Secondary:**
```css
.btn-secondary {
  background: var(--color-gray-200);
  color: var(--color-gray-700);
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
}
```

**Destructive:**
```css
.btn-destructive {
  background: var(--color-error);
  color: white;
  padding: 10px 20px;
  border-radius: 6px;
}
```

---

### Cards

```css
.card {
  background: white;
  border: 1px solid var(--color-gray-200);
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
```

---

### Badges

```css
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-success { background: #D1FAE5; color: #065F46; }
.badge-warning { background: #FEF3C7; color: #92400E; }
.badge-error { background: #FEE2E2; color: #991B1B; }
.badge-info { background: #DBEAFE; color: #1E40AF; }
```

---

### Icons

**Icon Library:** Heroicons (https://heroicons.com/)

**Sizes:**
- Small: 16px (inline text)
- Medium: 20px (buttons, UI elements)
- Large: 24px (headings, featured)

---

## 📱 Responsive Design

### Breakpoints

```css
/* Mobile */
@media (max-width: 767px) { /* < 768px */ }

/* Tablet */
@media (min-width: 768px) and (max-width: 1023px) { /* 768px - 1024px */ }

/* Desktop */
@media (min-width: 1024px) { /* > 1024px */ }
```

---

### Mobile Adaptations (< 768px)

**Navigation:**
- Bottom tab bar (Dashboard, Products, Search, Profile, More)
- Sidebar: Drawer (swipe from left)

**Tables:**
- Horizontal scroll
- OR Card view (stacked)

**Forms:**
- Full-width inputs
- Stacked layout (no columns)

**Modals:**
- Full-screen on mobile
- Slide-up animation

**Grid Layouts:**
- 4-column → 2-column → 1-column

---

### Tablet Adaptations (768px - 1024px)

**Sidebar:**
- Collapsible (icon-only when collapsed)
- Overlay mode (drawer)

**Grid Layouts:**
- 4-column → 3-column
- 2-column unchanged

**Tables:**
- Full-width scrollable
- Reduce padding/font size slightly

---

### Desktop (> 1024px)

**Sidebar:**
- Always visible
- Full-width (250px)

**Grid Layouts:**
- Full column count (3-4 columns)

**Tables:**
- Full features (sortable, filterable, inline editing)

---

## ✅ Implementation Checklist

### Phase 1: Core Foundation ✅ COMPLETED

- [x] Laravel 12.x installation
- [x] Authentication system (Laravel Breeze)
- [x] Database migrations (users, roles, permissions)
- [x] Basic routing structure
- [x] Blade layouts (admin, guest)
- [x] Navigation component
- [x] Dashboard scaffolding

---

### Phase 2: User Management ✅ COMPLETED

- [x] 7-level role system
- [x] User CRUD operations
- [x] Permission assignment
- [x] User profile pages
- [x] Session management
- [x] Activity logging

---

### Phase 3: Products Module 🛠️ IN PROGRESS

- [x] Product model + migrations
- [x] Category tree structure
- [x] Product CRUD (basic)
- [ ] Product form (12 tabs)
- [ ] Category picker component
- [ ] Image upload/management
- [ ] Price groups integration
- [ ] Stock management
- [ ] Multi-shop support
- [ ] Variants system
- [ ] Features/compatibility

---

### Phase 4: PrestaShop Integration ⏳ PLANNED

- [ ] Shop connection management
- [ ] API client (8.x/9.x support)
- [ ] Mapping interfaces (categories, warehouses, prices)
- [ ] Sync engine
- [ ] Conflict resolution
- [ ] Export wizard
- [ ] Sync logs/monitoring

---

### Phase 5: Import/Export System ⏳ BACKEND DONE

- [x] CSV service layer
- [x] XLSX import engine
- [ ] Template management UI
- [ ] Column mapping interface
- [ ] Import wizard
- [ ] Validation & preview
- [ ] Error reporting
- [ ] Import history

---

### Phase 6: ERP Integrations ⏳ PLANNED

- [ ] BaseLinker connection
- [ ] Subiekt GT integration
- [ ] Microsoft Dynamics connector
- [ ] Entity mappings
- [ ] Sync configuration
- [ ] Webhook handlers

---

### Phase 7: Deliveries & Containers ⏳ PLANNED

- [ ] Delivery management
- [ ] Container tracking
- [ ] Receiving interface
- [ ] Document management
- [ ] ORDER tracking
- [ ] Barcode scanning support

---

### Phase 8: Orders & Reservations ⏳ PLANNED

- [ ] Order management
- [ ] Reservation system
- [ ] Container reservations
- [ ] Order history
- [ ] Multi-source orders (PrestaShop, BaseLinker, Manual)

---

### Phase 9: Claims System ⏳ PLANNED

- [ ] Claims CRUD
- [ ] Priority management
- [ ] Attachment uploads
- [ ] Status workflow
- [ ] Email notifications
- [ ] Archive system

---

### Phase 10: Reports & Analytics ⏳ PLANNED

- [ ] Product reports
- [ ] Financial reports
- [ ] Warehouse reports
- [ ] Report builder
- [ ] Scheduled reports
- [ ] Export formats (CSV, XLSX, PDF)

---

### Phase 11: System Administration ✅ COMPLETED

- [x] System settings
- [x] Backup & restore
- [x] Database maintenance
- [x] System logs
- [x] Monitoring dashboard
- [x] API management

---

### Phase 12: UI/UX Polish ⏳ FINAL PHASE

- [ ] Responsive design implementation
- [ ] Dark mode (optional)
- [ ] Accessibility (WCAG 2.1 AA)
- [ ] Performance optimization
- [ ] Browser testing (Chrome, Firefox, Edge, Safari)
- [ ] Mobile testing (iOS, Android)

---

## 📊 Status Implementacji

### Ukończone Etapy ✅

1. **ETAP_01**: Fundament (Laravel + Auth) - COMPLETED
2. **ETAP_02**: Dashboard - COMPLETED
3. **ETAP_03**: Basic Navigation - COMPLETED
4. **ETAP_04**: Admin Panel (FAZA A-E) - COMPLETED

### W Trakcie 🛠️

5. **ETAP_05**: Produkty (podstawy) - IN PROGRESS
6. **ETAP_05a**: Warianty & Cechy - IN PROGRESS

### Zaplanowane ⏳

7. **ETAP_06**: Import/Export (backend done, UI pending)
8. **ETAP_07**: PrestaShop Integration
9. **ETAP_08**: ERP Integrations
10. **ETAP_09**: Wyszukiwarka
11. **ETAP_10**: Dostawy & Zamówienia & Reklamacje
12. **ETAP_11**: Raporty
13. **ETAP_12**: UI/UX Polish

---

## 📝 Uwagi Końcowe

### Priorytety Implementacji

1. **Wysokie:** Produkty (ETAP_05 + 05a) → CSV Import UI (ETAP_06) → PrestaShop Sync (ETAP_07)
2. **Średnie:** ERP Integrations (ETAP_08) → Wyszukiwarka (ETAP_09)
3. **Niskie:** Dostawy/Zamówienia/Reklamacje (ETAP_10) → Raporty (ETAP_11)

### Kluczowe Założenia Architektoniczne

- **SKU jako klucz główny** produktu (nie external IDs)
- **Multi-store support** z dedykowanymi danymi per sklep
- **Enterprise-grade** - bez hardcode, pełna konfiguracja
- **Livewire 3.x** dla wszystkich interaktywnych UI
- **Context7 MANDATORY** przed implementacją nowych patterns

### Dokumenty Referencyjne

- **CLAUDE.md** - Project rules & guidelines
- **_DOCS/AGENT_USAGE_GUIDE.md** - Agent delegation patterns
- **_DOCS/CONTEXT7_INTEGRATION_GUIDE.md** - Context7 usage rules
- **_DOCS/DEPLOYMENT_GUIDE.md** - SSH deployment procedures
- **_DOCS/SKU_ARCHITECTURE_GUIDE.md** - SKU-first architecture
- **Plan_Projektu/** - ETAP files with task breakdown

---

**Dokument stworzony:** 2025-10-22
**Ostatnia aktualizacja:** 2025-10-22
**Wersja:** 1.0
**Autor:** Claude Code + Kamil Wiliński
