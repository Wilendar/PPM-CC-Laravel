# STRUKTURA BAZY DANYCH PPM-CC-Laravel

**Data utworzenia:** 2025-09-29
**Wersja:** 1.0
**Autor:** Claude Code - Dokumentacja systemowa
**Baza danych:** MariaDB 10.11.13 (host379076_ppm@localhost)

## 📋 SPIS TREŚCI

- [Wizualna Mapa Relacji](#wizualna-mapa-relacji)
- [Szczegółowy Opis Tabel](#szczegółowy-opis-tabel)
- [Mapowanie do ETAP-ów](#mapowanie-do-etap-ów)
- [Indeksy i Optymalizacje](#indeksy-i-optymalizacje)
- [Status Migracji](#status-migracji)

---

## 🗂️ WIZUALNA MAPA RELACJI

```
                    ┌─────────────┐
                    │    USERS    │
                    │(Użytkownicy)│
                    └──────┬──────┘
                           │
                           │ 1:N
                           ▼
    ┌──────────────┐  ┌────────────────┐  ┌─────────────────┐
    │  CATEGORIES  │  │   PRODUCTS     │  │ PRESTASHOP_SHOPS│
    │ (Kategorie)  │◄─┤   (Produkty)   ├─►│   (Sklepy)      │
    │ - Tree 5lvl  │N:M└────────┬───────┘M:N└─────────┬───────┘
    └──────────────┘           │                      │
                               │                      │
                               │ 1:N              1:N │
                               ▼                      ▼
    ┌──────────────┐  ┌────────────────┐  ┌─────────────────┐
    │ PRICE_GROUPS │  │PRODUCT_VARIANTS│  │PRODUCT_SHOP_DATA│
    │(Grupy Cenowe)│◄─┤  (Warianty)    │  │(Dane per Sklep) │
    └──────┬───────┘  └────────────────┘  └─────────────────┘
           │
           │ 1:N
           ▼
    ┌──────────────┐  ┌────────────────┐  ┌─────────────────┐
    │PRODUCT_PRICES│  │ PRODUCT_STOCK  │  │  ERP_CONNECTIONS│
    │   (Ceny)     │  │   (Stany)      │  │ (Integracje ERP)│
    └──────────────┘  └────────┬───────┘  └─────────────────┘
                               │
                               │ N:1
                               ▼
                    ┌─────────────────┐
                    │   WAREHOUSES    │
                    │  (Magazyny)     │
                    └─────────────────┘

        ┌─────────────────────┐  ┌─────────────────────┐
        │    MEDIA/UPLOADS    │  │   ADMIN SYSTEMS     │
        │   (Multimedia)      │  │  (Panel Admina)     │
        └─────────────────────┘  └─────────────────────┘
```

---

## 📊 SZCZEGÓŁOWY OPIS TABEL

### 🏗️ CORE PRODUCT SYSTEM (ETAP_02)

#### **products** - Główna tabela produktów
```sql
- id (PK) - Serial primary key
- sku (UNIQUE) - Kod produktu (główny identyfikator)
- slug (UNIQUE) - URL-friendly slug
- name - Nazwa produktu (max 500 znaków)
- short_description - Krótki opis (TEXT)
- long_description - Długi opis (LONGTEXT)
- product_type - ENUM: vehicle, spare_part, clothing, other
- manufacturer - Producent (max 200 znaków)
- supplier_code - Kod dostawcy (indeksowany)
- weight, height, width, length - Wymiary fizyczne
- ean - Kod EAN
- tax_rate - Stawka VAT (domyślnie 23.00%)
- is_active - Status aktywności (indeksowany)
- is_variant_master - Czy ma warianty
- sort_order - Kolejność sortowania
- meta_title, meta_description - SEO
- publishing_schedule - Harmonogram publikacji (JSON)
- is_featured - Czy produkt wyróżniony
- timestamps, soft_deletes
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED
**Indeksy:** FULLTEXT na name+short_description, code_search na sku+supplier_code

---

#### **categories** - System kategorii (5-poziomowy)
```sql
- id (PK) - Serial primary key
- parent_id (FK) - Self-reference dla drzewa
- name - Nazwa kategorii (max 300 znaków)
- slug - URL slug
- description - Opis kategorii
- short_description - Krótki opis dla listing
- level - Poziom zagnieżdżenia (0-4)
- path - Path optimization ('/1/2/5')
- sort_order - Kolejność w kategorii
- is_active - Status aktywności
- is_featured - Kategoria wyróżniona
- icon - Ikona kategorii
- icon_path - Ścieżka do ikony
- banner_path - Ścieżka do banera
- meta_title, meta_description - SEO
- meta_keywords - Słowa kluczowe SEO
- canonical_url - Canonical URL
- og_title, og_description, og_image - OpenGraph
- visual_settings - Ustawienia wizualne (JSON)
- visibility_settings - Ustawienia widoczności (JSON)
- default_values - Wartości domyślne (JSON)
- timestamps, soft_deletes
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED + ENHANCED (2025-09-24)
**Constrainty:** max_level(0-4), no_self_parent, cascade delete
**Indeksy:** FULLTEXT na content dla zaawansowanego wyszukiwania

---

#### **product_categories** - Relacja produkty-kategorie (PIVOT TABLE)
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- category_id (FK) - ID kategorii
- shop_id (FK, NULLABLE) - ID sklepu PrestaShop (NULL = dane domyślne) **⚡ NEW 2025-10-13**
- is_primary - Czy kategoria domyślna per (product, shop)
- sort_order - Kolejność wyświetlania w kategorii
- timestamps - created_at, updated_at
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED + PER-SHOP SUPPORT (2025-10-13)

**ARCHITEKTURA PER-SHOP CATEGORIES (2025-10-13):**
- `shop_id = NULL` → "Dane domyślne" (z pierwszego importu produktu)
- `shop_id = X` → Per-shop override (różne kategorie na różnych sklepach)
- First import z Shop A → tworzy categories z `shop_id=NULL`
- Re-import z Shop B → tworzy categories z `shop_id=B` (nie nadpisuje default!)

**Unique Constraints:**
- `unique_product_category_per_shop (product_id, category_id, shop_id)` - Prevents duplicates
- MySQL treats NULL as distinct → Multiple products can have category with shop_id=NULL

**Foreign Keys:**
- `product_id` → products(id) CASCADE DELETE
- `category_id` → categories(id) CASCADE DELETE
- `shop_id` → prestashop_shops(id) CASCADE DELETE

**Triggers:**
- `tr_product_categories_primary_per_shop_insert` - Enforce single is_primary per (product, shop)
- `tr_product_categories_primary_per_shop_update` - Maintain is_primary integrity

**Business Logic:**
- Jeden produkt może mieć max 10 kategorii (per shop)
- is_primary=true → tylko jedna per (product_id, shop_id)
- Query default: `WHERE shop_id IS NULL`
- Query per-shop: `WHERE shop_id = X`
- Fallback: Per-shop categories → default if no shop-specific exist

**Indeksy Performance:**
- `idx_product_id` - Lookup kategorii dla produktu
- `idx_category_id` - Lookup produktów w kategorii
- `idx_shop_id` - Per-shop filtering **⚡ NEW 2025-10-13**
- `idx_category_sort` (category_id, sort_order) - Sortowanie
- `idx_is_primary` - Lookup kategorii domyślnych

**Migration:** `2025_10_13_000004_add_shop_id_to_product_categories.php`

---

#### **product_variants** - Warianty produktów
```sql
- id (PK) - Serial primary key
- parent_product_id (FK) - Link do produktu głównego
- variant_sku (UNIQUE) - SKU wariantu
- variant_name - Nazwa wariantu
- variant_attributes - JSON atrybutów wariantu
- price_modifier - Modyfikator ceny
- stock_modifier - Modyfikator stanu
- is_active - Status aktywności
- sort_order - Kolejność wyświetlania
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED

---

### 💰 PRICE & STOCK SYSTEM (ETAP_02)

#### **price_groups** - Grupy cenowe
```sql
- id (PK) - Serial primary key
- name - Nazwa grupy (Detaliczna, Dealer Standard, etc.)
- code - Kod grupy (unique)
- description - Opis grupy
- markup_percentage - Narzut procentowy
- is_default - Czy domyślna grupa
- is_active - Status aktywności
- sort_order - Kolejność wyświetlania
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED
**Grupy:** Detaliczna, Dealer Standard/Premium, Warsztat/Premium, Szkółka-Komis-Drop, Pracownik

---

#### **product_prices** - Ceny produktów per grupa
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- price_group_id (FK) - ID grupy cenowej
- price - Cena (DECIMAL 10,2)
- cost_price - Cena zakupu
- currency - Waluta (domyślnie PLN)
- valid_from, valid_to - Ważność ceny
- is_active - Status aktywności
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED
**Unique:** product_id + price_group_id

---

#### **warehouses** - Magazyny
```sql
- id (PK) - Serial primary key
- name - Nazwa magazynu
- code - Kod magazynu (unique)
- description - Opis magazynu
- address - Adres magazynu
- is_default - Czy domyślny magazyn
- is_active - Status aktywności
- contact_info - Dane kontaktowe (JSON)
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED
**Magazyny:** MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje

---

#### **product_stock** - Stany magazynowe
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- warehouse_id (FK) - ID magazynu
- quantity_available - Stan dostępny
- quantity_reserved - Stan zarezerwowany
- quantity_incoming - Stan przychodzący
- reorder_level - Poziom ponownego zamówienia
- max_stock_level - Maksymalny stan
- last_updated_at - Ostatnia aktualizacja
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED
**Unique:** product_id + warehouse_id

---

### 🏪 MULTI-STORE SYSTEM (ETAP_04, ETAP_07)

#### **prestashop_shops** - Konfiguracja sklepów PrestaShop
```sql
- id (PK) - Serial primary key
- name - Nazwa sklepu (max 200)
- url - URL sklepu (max 500, unique)
- description - Opis sklepu
- is_active - Status aktywności

// API Configuration
- api_key - Klucz API (encrypted, max 200)
- api_version - Wersja API (domyślnie 1.7)
- ssl_verify - Weryfikacja SSL
- timeout_seconds - Timeout połączenia (domyślnie 30s)
- rate_limit_per_minute - Limit zapytań (domyślnie 60/min)

// Connection Health
- connection_status - ENUM: connected, disconnected, error, maintenance
- last_connection_test - Ostatni test
- last_response_time - Czas odpowiedzi (ms)
- consecutive_failures - Liczba niepowodzeń z rzędu
- last_error_message - Ostatni błąd

// PrestaShop Compatibility
- prestashop_version - Wykryta wersja
- version_compatible - Czy kompatybilna
- supported_features - Lista funkcji (JSON)

// Sync Configuration
- sync_frequency - ENUM: realtime, hourly, daily, manual
- sync_settings - Ustawienia (JSON)
- auto_sync_products, auto_sync_categories, auto_sync_prices, auto_sync_stock

// Conflict Resolution
- conflict_resolution - ENUM: ppm_wins, prestashop_wins, manual, newest_wins

// Mappings
- category_mappings - Mapowanie kategorii (JSON)
- price_group_mappings - Mapowanie grup cenowych (JSON)
- warehouse_mappings - Mapowanie magazynów (JSON)
- custom_field_mappings - Custom fields (JSON)

// Statistics
- last_sync_at - Ostatnia synchronizacja
- products_synced - Liczba zsynchronizowanych produktów
- sync_success_count, sync_error_count - Statystyki
- avg_response_time - Średni czas odpowiedzi

// Performance
- api_quota_used, api_quota_limit - Quota API
- quota_reset_at - Reset quota

// Notifications
- notification_settings - Ustawienia powiadomień (JSON)
- notify_on_errors, notify_on_sync_complete
- timestamps
```

**ETAP:** ETAP_04 ✅, ETAP_07 ❌ **Status:** PLANNED

---

#### **product_shop_data** - Dane produktów per sklep
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu w PPM
- shop_id (FK) - ID sklepu PrestaShop

// Per-shop Product Data (overrides)
- name - Nazwa specyficzna dla sklepu
- slug - Slug specyficzny dla sklepu
- short_description - Krótki opis per sklep
- long_description - Długi opis per sklep
- meta_title, meta_description - SEO per sklep

// Shop-specific Mappings
- category_mappings - Mapowanie kategorii (JSON)
- attribute_mappings - Mapowanie atrybutów (JSON)
- image_settings - Ustawienia zdjęć (JSON)

// Synchronization Control
- sync_status - ENUM: pending, synced, error, conflict, disabled
- last_sync_at - Ostatnia synchronizacja
- last_sync_hash - Hash danych (wykrywanie zmian)
- sync_errors - Błędy synchronizacji (JSON)
- conflict_data - Dane konfliktu (JSON)
- conflict_detected_at - Kiedy wykryto konflikt

// Publishing Control
- is_published - Czy opublikowany na sklepie
- published_at, unpublished_at - Timeline publikacji
- external_id - ID w systemie PrestaShop ⚠️ VARCHAR (zostanie zastąpione przez prestashop_product_id BIGINT)
- external_reference - Dodatkowa referencja
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED | ⏳ **REFACTORING IN PROGRESS** (2025-10-13)
**Unique:** product_id + shop_id

**🔧 PLANOWANA KONSOLIDACJA (2025-10-13):**
Tabela zostanie rozszerzona o kolumny sync tracking z `product_sync_status`:
- ✅ `prestashop_product_id` (BIGINT UNSIGNED) - zastąpi `external_id` (VARCHAR)
- ✅ `last_success_sync_at` (TIMESTAMP) - dodatkowy timestamp sukcesu
- ✅ `sync_direction` (ENUM: ppm_to_ps, ps_to_ppm, bidirectional) - kierunek synchronizacji
- ✅ `retry_count` (TINYINT UNSIGNED) - mechanizm retry
- ✅ `max_retries` (TINYINT UNSIGNED DEFAULT 3) - limit prób
- ✅ `priority` (TINYINT UNSIGNED DEFAULT 5) - priorytet sync (1-10)
- ✅ `checksum` (VARCHAR 64) - MD5 hash do wykrywania zmian
- ✅ `error_message` (TEXT) - zamieni `sync_errors` (JSON → TEXT)

**CEL:** Single source of truth dla danych per sklep (content + sync tracking)
**STATUS:** Konsolidacja w toku - `product_sync_status` zostanie usunięta po migracji

---

### 🔗 ERP INTEGRATION SYSTEM (ETAP_08)

#### **erp_connections** - Połączenia z systemami ERP
```sql
- id (PK) - Serial primary key

// ERP Identification
- erp_type - ENUM: baselinker, subiekt_gt, dynamics, insert, custom
- instance_name - Nazwa instancji (multi-instance support)
- description - Opis połączenia
- is_active - Status aktywności
- priority - Priorytet synchronizacji (1=najwyższy)

// Connection Configuration (encrypted JSON)
- connection_config - Konfiguracja połączenia
/*
  Baselinker: {"api_token": "...", "inventory_id": "...", "warehouse_mappings": {...}}
  Subiekt GT: {"dll_path": "...", "database_name": "...", "server": "...", "credentials": {...}}
  Dynamics: {"tenant_id": "...", "client_id": "...", "client_secret": "...", "odata_url": "..."}
*/

// Authentication
- auth_status - ENUM: authenticated, expired, failed, pending
- auth_expires_at, last_auth_at - Timeline uwierzytelnienia

// Connection Health
- connection_status - ENUM: connected, disconnected, error, maintenance, rate_limited
- last_health_check - Ostatnie sprawdzenie
- last_response_time - Czas odpowiedzi (ms)
- consecutive_failures - Liczba niepowodzeń z rzędu
- last_error_message - Ostatni błąd

// API Rate Limiting
- rate_limit_per_minute - Limit zapytań
- current_api_usage - Aktualne użycie
- rate_limit_reset_at - Reset limitu

// Synchronization
- sync_mode - ENUM: bidirectional, push_only, pull_only, disabled
- sync_settings - Ustawienia (JSON)
- auto_sync_products, auto_sync_stock, auto_sync_prices, auto_sync_orders

// Data Mapping
- field_mappings - Mapowanie pól (JSON)
- transformation_rules - Reguły transformacji (JSON)
- validation_rules - Reguły walidacji (JSON)

// Statistics
- last_sync_at, next_scheduled_sync - Timeline synchronizacji
- sync_success_count, sync_error_count - Statystyki
- records_synced_total - Łączna liczba rekordów
- avg_sync_time - Średni czas synchronizacji (s)
- data_volume_mb - Wolumen danych (MB)

// Error Handling
- max_retry_attempts - Maksymalne próby (domyślnie 3)
- retry_delay_seconds - Opóźnienie (domyślnie 60s)
- auto_disable_on_errors - Auto wyłączenie przy błędach
- error_threshold - Próg błędów (domyślnie 10)

// Webhook Support
- webhook_url - URL webhooka
- webhook_secret - Secret weryfikacji
- webhook_enabled - Status webhooka

// Notifications
- notification_settings - Ustawienia powiadomień (JSON)
- notify_on_errors, notify_on_sync_complete, notify_on_auth_expire
- timestamps
```

**ETAP:** ETAP_08 ⏳ **Status:** IN PROGRESS
**Unique:** erp_type + instance_name

---

### 📊 ADVANCED FEATURES (ETAP_05+)

#### **price_history** - Historia zmian cen
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- price_group_id (FK) - ID grupy cenowej
- old_price, new_price - Stara i nowa cena
- change_reason - Powód zmiany
- changed_by_user_id (FK) - Kto zmienił
- effective_date - Data wejścia w życie
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED

---

#### **stock_movements** - Ruchy magazynowe
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- warehouse_id (FK) - ID magazynu
- movement_type - ENUM: in, out, transfer, adjustment, reserved, unreserved
- quantity - Ilość
- unit_cost - Koszt jednostkowy
- reference_type - Typ referencji (order, transfer, etc.)
- reference_id - ID referencji
- notes - Notatki
- created_by_user_id (FK) - Kto utworzył
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED

---

#### **stock_reservations** - Rezerwacje stanów
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- warehouse_id (FK) - ID magazynu
- reserved_quantity - Ilość zarezerwowana
- reservation_type - Typ rezerwacji
- reference_id - ID referencji (order, etc.)
- reserved_until - Data wygaśnięcia rezerwacji
- status - ENUM: active, expired, released
- created_by_user_id (FK) - Kto zarezerwował
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED

---

#### **product_types** - Typy produktów
```sql
- id (PK) - Serial primary key
- name - Nazwa typu
- code - Kod typu (unique)
- description - Opis typu
- icon - Ikona typu
- color - Kolor typu (hex)
- attributes_schema - Schema atrybutów (JSON)
- is_active - Status aktywności
- sort_order - Kolejność wyświetlania
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED

---

#### **product_shop_categories** - Kategorie per sklep
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu
- shop_id (FK) - ID sklepu
- category_id (FK) - ID kategorii
- is_primary - Kategoria główna per sklep
- sort_order - Kolejność w kategorii per sklep
- timestamps
```

**ETAP:** ETAP_05 ✅ **Status:** COMPLETED
**Unique:** product_id + shop_id + category_id

---

### 👤 USER & ADMIN SYSTEM (ETAP_03, ETAP_04)

#### **users** - Użytkownicy systemu (extended Laravel)
```sql
-- Laravel standard fields --
- id (PK), name, email, email_verified_at, password, remember_token, timestamps

-- PPM Extensions --
- first_name, last_name - Pełne imię i nazwisko
- company - Firma użytkownika
- phone - Telefon kontaktowy
- avatar - Ścieżka do avatara
- role - ENUM: admin, manager, editor, warehouse, sales, complaints, user
- is_active - Status konta
- last_login_at - Ostatnie logowanie
- language - Język interfejsu (domyślnie 'pl')
- timezone - Strefa czasowa (domyślnie 'Europe/Warsaw')

-- OAuth Integration --
- google_id - Google OAuth ID
- microsoft_id - Microsoft OAuth ID
- oauth_token - Token OAuth (encrypted)
- oauth_refresh_token - Refresh token (encrypted)

-- Dashboard Preferences --
- dashboard_preferences - Ustawienia dashboardu (JSON)

-- Security --
- two_factor_enabled - 2FA włączone
- two_factor_secret - 2FA secret (encrypted)
- two_factor_recovery_codes - Kody recovery (encrypted)
```

**ETAP:** ETAP_03 ✅ **Status:** COMPLETED

---

#### **audit_logs** - Logi audytu
```sql
- id (PK) - Serial primary key
- user_id (FK) - ID użytkownika
- action - Akcja (create, update, delete, etc.)
- model_type - Typ modelu
- model_id - ID modelu
- old_values - Stare wartości (JSON)
- new_values - Nowe wartości (JSON)
- ip_address - Adres IP
- user_agent - User Agent
- timestamps
```

**ETAP:** ETAP_03 ✅ **Status:** COMPLETED

---

#### **system_settings** - Ustawienia systemu
```sql
- id (PK) - Serial primary key
- key - Klucz ustawienia (unique)
- value - Wartość (JSON)
- description - Opis ustawienia
- is_encrypted - Czy wartość jest szyfrowana
- category - Kategoria ustawienia
- is_public - Czy dostępne dla wszystkich
- timestamps
```

**ETAP:** ETAP_04 ✅ **Status:** COMPLETED

---

### 📁 MULTIMEDIA & FILES (ETAP_02)

#### **media** - Pliki multimedialne
```sql
- id (PK) - Serial primary key
- filename - Nazwa pliku
- original_filename - Oryginalna nazwa
- mime_type - Typ MIME
- size - Rozmiar w bajtach
- path - Ścieżka do pliku
- disk - Dysk storage
- alt_text - Tekst alternatywny
- title - Tytuł multimedia
- is_active - Status aktywności
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED

---

#### **file_uploads** - Historia uploadów
```sql
- id (PK) - Serial primary key
- user_id (FK) - Kto uploadował
- filename - Nazwa pliku
- original_filename - Oryginalna nazwa
- mime_type - Typ MIME
- size - Rozmiar
- path - Ścieżka
- upload_session - Sesja uploadu
- status - Status uploadu
- error_message - Błąd uploadu
- timestamps
```

**ETAP:** ETAP_02 ✅ **Status:** COMPLETED

---

## 🎯 MAPOWANIE DO ETAP-ÓW

### ✅ ETAP_01 - Fundament (COMPLETED)
**Tabele:** Laravel migration table, Laravel standard tables
**Status:** Środowisko skonfigurowane

---

### ✅ ETAP_02 - Modele Bazy (COMPLETED)
**Tabele:**
- ✅ `products` - Główna tabela produktów
- ✅ `categories` - System kategorii 5-poziomowy
- ✅ `product_variants` - Warianty produktów
- ✅ `product_categories` - Relacja N:M produkty-kategorie
- ✅ `price_groups` - Grupy cenowe
- ✅ `warehouses` - Magazyny
- ✅ `product_prices` - Ceny per grupa
- ✅ `product_stock` - Stany magazynowe
- ✅ `media` - System plików multimedialnych
- ✅ `file_uploads` - Historia uploadów
- ✅ `product_attributes` - Atrybuty produktów
- ✅ `product_attribute_values` - Wartości atrybutów
- ✅ `integration_mappings` - Mapowania integracji

**Status:** 42 migracje wdrożone na production

---

### ✅ ETAP_03 - Autoryzacja (COMPLETED)
**Tabele:**
- ✅ `users` (extended) - System użytkowników
- ✅ `audit_logs` - Logi audytu
- ✅ `notifications` - Powiadomienia
- ✅ `oauth_audit_logs` - OAuth audit

**Status:** Pełny system uprawnień z 7 rolami

---

### ✅ ETAP_04 - Panel Admin (COMPLETED)
**Tabele:**
- ✅ `prestashop_shops` - Konfiguracja sklepów
- ✅ `erp_connections` - Połączenia ERP
- ✅ `sync_jobs` - Zadania synchronizacji
- ✅ `integration_logs` - Logi integracji
- ✅ `system_settings` - Ustawienia systemu
- ✅ `backup_jobs` - Zadania backup
- ✅ `maintenance_tasks` - Zadania maintenance
- ✅ `admin_notifications` - Powiadomienia admin
- ✅ `system_reports` - Raporty systemowe
- ✅ `api_usage_logs` - Logi użycia API
- ✅ `admin_themes` - Motywy admin

**Status:** Kompletny panel administratora

---

### ✅ ETAP_05 - Produkty (COMPLETED)
**Tabele:**
- ✅ `price_history` - Historia zmian cen
- ✅ `stock_movements` - Ruchy magazynowe
- ✅ `stock_reservations` - Rezerwacje stanów
- ✅ `product_types` - Typy produktów
- ✅ `product_shop_data` - Dane per sklep
- ✅ `product_shop_categories` - Kategorie per sklep

**Status:** Zaawansowany system zarządzania produktami

---

### ❌ ETAP_06 - Import/Export (PLANNED)
**Tabele planowane:**
- ❌ `import_jobs` - Zadania importu
- ❌ `export_jobs` - Zadania eksportu
- ❌ `import_mappings` - Mapowania kolumn importu
- ❌ `export_templates` - Szablony eksportu
- ❌ `container_shipments` - Przesyłki kontenerowe
- ❌ `import_logs` - Logi importu

**Status:** Do implementacji

---

### 🛠️ ETAP_07 - PrestaShop API (IN PROGRESS - FAZA 1)

#### **shop_mappings** - Mapowania między PPM a PrestaShop
```sql
- id (PK) - Serial primary key
- shop_id (FK) - ID sklepu PrestaShop
- mapping_type - ENUM: category, attribute, feature, warehouse, price_group, tax_rule
- ppm_value - Wartość w PPM (VARCHAR 255)
- prestashop_id - ID w PrestaShop
- prestashop_value - Wartość w PrestaShop (VARCHAR 255)
- is_active - Status aktywności
- created_at, updated_at - Timestamps
```

**ETAP:** ETAP_07 🛠️ **Status:** FAZA 1 - TO IMPLEMENT
**Unique:** shop_id + mapping_type + ppm_value
**Indeksy:** idx_shop_type (shop_id, mapping_type), idx_ppm_value (mapping_type, ppm_value)

**Przykłady mapowań:**
- Kategoria PPM "Motocykle" → PrestaShop category_id 5
- Grupa cenowa "Detaliczna" → PrestaShop specific_price group_id 2
- Magazyn "MPPTRADE" → PrestaShop warehouse_id 1

---

#### **product_sync_status** - Status synchronizacji produktów ⚠️ **DEPRECATED - DO USUNIĘCIA**
```sql
- id (PK) - Serial primary key
- product_id (FK) - ID produktu w PPM
- shop_id (FK) - ID sklepu PrestaShop
- prestashop_product_id - ID produktu w PrestaShop
- sync_status - ENUM: pending, syncing, synced, error, conflict, disabled
- last_sync_at - Ostatnia próba synchronizacji
- last_success_sync_at - Ostatnia udana synchronizacja
- sync_direction - ENUM: ppm_to_ps, ps_to_ppm, bidirectional
- error_message - Komunikat błędu (TEXT)
- conflict_data - Dane konfliktu (JSON)
- retry_count - Liczba prób ponowienia
- max_retries - Maksymalna liczba prób (domyślnie 3)
- priority - Priorytet synchronizacji (1=najwyższy, 10=najniższy)
- checksum - MD5 hash dla wykrywania zmian (VARCHAR 64)
- created_at, updated_at - Timestamps
```

**ETAP:** ETAP_07 ⚠️ **Status:** DEPRECATED (2025-10-13)
**Unique:** product_id + shop_id
**Indeksy:**
- idx_sync_status (sync_status)
- idx_shop_status (shop_id, sync_status)
- idx_priority (priority, sync_status)
- idx_retry (retry_count, max_retries)

**⚠️ ARCHITEKTURA ISSUE (2025-10-13):**
Ta tabela powoduje **DUPLIKACJĘ** kolumn z `product_shop_data` (sync_status, conflict_data, last_sync_at).
**PLAN KONSOLIDACJI:** Kolumny sync tracking zostaną przeniesione do `product_shop_data`, a ta tabela usunięta.
**PRZYCZYNA:** Utworzono jako osobną tabelę (ETAP_07 październik 2025) zamiast rozszerzyć istniejącą `product_shop_data` (FAZA 1.5 wrzesień 2025).
**REFACTORING:** OPCJA B - konsolidacja w toku (2025-10-13)

---

#### **sync_logs** - Szczegółowe logi operacji synchronizacji
```sql
- id (PK) - Serial primary key
- shop_id (FK) - ID sklepu PrestaShop
- product_id (FK) - ID produktu (NULL dla operacji globalnych)
- operation - ENUM: sync_product, sync_category, sync_image, sync_stock, sync_price, webhook
- direction - ENUM: ppm_to_ps, ps_to_ppm
- status - ENUM: started, success, error, warning
- message - Komunikat operacji (TEXT)
- request_data - Dane wysłane do API (JSON)
- response_data - Odpowiedź z API (JSON)
- execution_time_ms - Czas wykonania w milisekundach
- api_endpoint - Endpoint API (VARCHAR 500)
- http_status_code - Kod odpowiedzi HTTP (SMALLINT)
- created_at - Timestamp operacji
```

**ETAP:** ETAP_07 🛠️ **Status:** FAZA 1 - TO IMPLEMENT
**Indeksy:**
- idx_shop_operation (shop_id, operation)
- idx_status_created (status, created_at)
- idx_product_logs (product_id, created_at)
- idx_operation_direction (operation, direction)

**Cel:** Szczegółowe monitorowanie wszystkich operacji API z PrestaShop

---

**Rozszerzenia istniejących tabel:**
- 🛠️ `prestashop_shops` - Dodanie kolumn: sync_frequency, sync_settings (JSON), webhook_url, webhook_secret, webhook_enabled, rate_limit_per_minute, api_quota_used

**Planowane (FAZA 2 - Webhooks + Conflicts):**
- ❌ `webhook_events` - Odbieranie webhooków z PrestaShop
- ❌ `prestashop_conflicts` - Zarządzanie konfliktami synchronizacji

**Status:** FAZA 1 IN PROGRESS - Panel konfiguracyjny + sync produktów/kategorii (bez zdjęć)

---

### ⏳ ETAP_08 - ERP Integracje (IN PROGRESS)
**Tabele:**
- 🛠️ `erp_connections` - Rozbudowa (częściowo done)
- ❌ `erp_sync_logs` - Szczegółowe logi ERP
- ❌ `erp_field_mappings` - Mapowania pól ERP
- ❌ `baselinker_products` - Cache produktów Baselinker
- ❌ `subiekt_inventory` - Cache stanów Subiekt GT

**Status:** W trakcie implementacji

---

### ❌ ETAP_09 - Wyszukiwanie (PLANNED)
**Tabele planowane:**
- ❌ `search_indexes` - Indeksy wyszukiwania
- ❌ `search_logs` - Logi wyszukiwania
- ❌ `search_suggestions` - Sugestie wyszukiwania

**Status:** Do implementacji

---

### ❌ ETAP_10 - Dostawy (PLANNED)
**Tabele planowane:**
- ❌ `deliveries` - Dostawy
- ❌ `delivery_items` - Pozycje dostaw
- ❌ `delivery_tracking` - Tracking dostaw
- ❌ `containers` - Kontenery
- ❌ `container_contents` - Zawartość kontenerów

**Status:** Do implementacji

---

### ❌ ETAP_11 - Dopasowania (PLANNED)
**Tabele planowane:**
- ❌ `vehicle_models` - Modele pojazdów
- ❌ `product_vehicle_compatibility` - Kompatybilność z pojazdami
- ❌ `vehicle_characteristics` - Cechy pojazdów

**Status:** Do implementacji

---

### ❌ ETAP_12 - UI/Deploy (PLANNED)
**Tabele pomocnicze:**
- ❌ `ui_preferences` - Preferencje UI użytkowników
- ❌ `deployment_logs` - Logi deployment
- ❌ `performance_metrics` - Metryki wydajności

**Status:** Do implementacji

---

## ⚡ INDEKSY I OPTYMALIZACJE

### Performance Indexes (aktywne)

#### **products** table:
- `UNIQUE INDEX` na `sku` - primary lookup
- `FULLTEXT INDEX` na `name, short_description` - search
- `FULLTEXT INDEX` na `sku, supplier_code` - code search
- `COMPOUND INDEX` na `is_active, product_type` - filtering
- `INDEX` na `manufacturer` - frequent filtering
- `INDEX` na `created_at` - chronological sorting

#### **categories** table:
- `INDEX` na `parent_id` - tree traversal
- `INDEX` na `path` - CRITICAL dla tree queries
- `INDEX` na `level, sort_order` - level-based sorting
- `INDEX` na `is_active, level` - active categories per level

#### **prestashop_shops** table:
- `INDEX` na `is_active` - active shops filtering
- `INDEX` na `connection_status` - health monitoring
- `INDEX` na `sync_frequency` - scheduled sync jobs
- `INDEX` na `last_sync_at` - sync timeline
- `INDEX` na `consecutive_failures` - error monitoring

#### **product_shop_data** table:
- `UNIQUE INDEX` na `product_id, shop_id` - primary relationship
- `INDEX` na `sync_status` - sync monitoring
- `INDEX` na `shop_id, sync_status` - shop dashboard
- `INDEX` na `is_published` - published products
- `INDEX` na `external_id` - reverse lookup

### Database Constraints

```sql
-- Categories tree constraints
ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 4);
ALTER TABLE categories ADD CONSTRAINT chk_no_self_parent CHECK (id != parent_id);

-- Price validation
ALTER TABLE product_prices ADD CONSTRAINT chk_positive_price CHECK (price >= 0);

-- Stock validation
ALTER TABLE product_stock ADD CONSTRAINT chk_non_negative_stock CHECK (quantity_available >= 0);
```

### Query Optimization Patterns

```sql
-- Produkty aktywne z cenami dla grupy
SELECT p.*, pp.price
FROM products p
JOIN product_prices pp ON p.id = pp.product_id
WHERE p.is_active = 1 AND pp.price_group_id = ?
INDEX HINT: USE INDEX(idx_active_type, idx_price_group)

-- Tree query dla kategorii
SELECT * FROM categories
WHERE path LIKE '/1/2/%'
INDEX HINT: USE INDEX(idx_path)

-- Synchronizacja status per sklep
SELECT shop_id, sync_status, COUNT(*)
FROM product_shop_data
WHERE shop_id IN (1,2,3)
GROUP BY shop_id, sync_status
INDEX HINT: USE INDEX(idx_shop_sync_status)
```

---

## 📈 STATUS MIGRACJI

### Migration Status (2025-09-29)
```bash
php artisan migrate:status
```

**Batch 1-3:** Core Laravel + PPM Foundation (2024-01-01)
**Batch 4-8:** ETAP_02 Core Product System
**Batch 9-14:** ETAP_03 Auth + ETAP_04 Admin Panel
**Batch 15-26:** ETAP_05 Advanced Product Features

**Total:** 42 migracje ✅ **Status:** ALL MIGRATED

### Lista Migracji (chronologicznie)

#### **Core System (2024-01-01)** - Batch 1-14
1. `2024_01_01_000001_create_products_table` - Główna tabela produktów
2. `2024_01_01_000002_create_categories_table` - System kategorii
3. `2024_01_01_000003_create_product_variants_table` - Warianty produktów
4. `2024_01_01_000004_add_core_performance_indexes` - Indeksy wydajnościowe
5. `2024_01_01_000005_create_product_categories_table` - Relacja N:M
6. `2024_01_01_000006_create_price_groups_table` - Grupy cenowe
7. `2024_01_01_000007_create_warehouses_table` - Magazyny
8. `2024_01_01_000008_create_product_prices_table` - Ceny produktów
9. `2024_01_01_000009_create_product_stock_table` - Stany magazynowe
10. `2024_01_01_000010_create_media_table` - Pliki multimedialne
11. `2024_01_01_000011_create_file_uploads_table` - Historia uploadów
12. `2024_01_01_000012_create_product_attributes_table` - Atrybuty
13. `2024_01_01_000013_create_product_attribute_values_table` - Wartości atrybutów
14. `2024_01_01_000014_create_integration_mappings_table` - Mapowania

#### **User & Admin System (2024-01-01)** - Batch 8-14
15. `2024_01_01_000015_add_media_relations_performance_indexes` - Indeksy media
16. `2024_01_01_000016_extend_users_table` - Rozszerzenie users
17. `2024_01_01_000017_create_audit_logs_table` - Logi audytu
18. `2024_01_01_000018_create_notifications_table` - Powiadomienia
19. `2024_01_01_000019_add_oauth_fields_to_users_table` - OAuth fields
20. `2024_01_01_000020_create_oauth_audit_logs_table` - OAuth audit
21. `2024_01_01_000025_add_dashboard_preferences_to_users` - Dashboard prefs

#### **Integration System (2024-01-01)** - Batch 10-12
22. `2024_01_01_000026_create_prestashop_shops_table` - Sklepy PrestaShop
23. `2024_01_01_000027_create_erp_connections_table` - Połączenia ERP
24. `2024_01_01_000028_create_sync_jobs_table` - Zadania sync
25. `2024_01_01_000029_create_integration_logs_table` - Logi integracji

#### **Admin System (2024-01-01)** - Batch 12-14
26. `2024_01_01_000030_create_system_settings_table` - Ustawienia systemu
27. `2024_01_01_000031_create_backup_jobs_table` - Zadania backup
28. `2024_01_01_000032_create_maintenance_tasks_table` - Maintenance
29. `2024_01_01_000033_create_admin_notifications_table` - Admin powiadomienia
30. `2024_01_01_000034_create_system_reports_table` - Raporty systemowe
31. `2024_01_01_000035_create_api_usage_logs_table` - Logi API
32. `2024_01_01_000036_create_admin_themes_table` - Motywy admin

#### **Advanced Features (2025-09)** - Batch 15-26
33. `2025_09_15_090129_extend_api_key_column_in_prestashop_shops_table` - Rozszerzenie API key
34. `2025_09_17_000001_create_price_history_table` - Historia cen
35. `2025_09_17_000002_create_stock_movements_table` - Ruchy magazynowe
36. `2025_09_17_000003_create_stock_reservations_table` - Rezerwacje
37. `2025_09_18_000001_create_product_types_table` - Typy produktów
38. `2025_09_18_000002_migrate_products_to_product_types` - Migracja typów
39. `2025_09_18_000003_create_product_shop_data_table` - Dane per sklep
40. `2025_09_19_000001_extend_product_shop_data_with_all_product_fields` - Rozszerzenie
41. `2025_09_22_000001_add_publishing_schedule_to_products_table` - Harmonogram
42. `2025_09_22_000002_add_is_featured_to_products_table` - Featured produkty
43. `2025_09_22_000003_create_product_shop_categories_table` - Kategorie per sklep
44. `2025_09_23_113329_remove_product_shop_categories_triggers` - Usunięcie triggerów
45. `2025_09_24_000001_extend_categories_table_for_advanced_form` - Rozszerzenie kategorii

---

## 🔄 AKTUALIZACJA DOKUMENTACJI

**ZASADA:** Ta dokumentacja MUSI być aktualizowana przy każdej nowej migracji w ramach ETAP-ów.

**PROCES:**
1. Przed rozpoczęciem ETAP → przeanalizuj planowane tabele
2. Dodaj planowane tabele do sekcji danego ETAP z statusem ❌
3. Po utworzeniu migracji → zaktualizuj strukturę tabeli ze szczegółami
4. Po wdrożeniu na production → zmień status na ✅
5. Dodaj nowe indeksy i constrainty do sekcji optymalizacji

**OSTATNIA AKTUALIZACJA:** 2025-10-13 ✅ Dodano per-shop categories support (shop_id w product_categories)
**AKTUALNY STATUS:** ETAP_07 FAZA 3A completed ✅, ETAP_08 in progress ⏳, Per-Shop Categories ⚡ NEW

---

**AUTOR:** Claude Code System
**PROJEKT:** PPM-CC-Laravel
**WERSJA:** Enterprise 1.0