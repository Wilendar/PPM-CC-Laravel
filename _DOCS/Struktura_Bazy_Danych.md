# 🗄️ STRUKTURA BAZY DANYCH - PPM Laravel

**Projekt:** PPM (PrestaShop Product Manager) - Enterprise Laravel Application  
**Wersja dokumentacji:** 1.0  
**Data utworzenia:** 2025-09-11  
**Status:** ✅ **UKOŃCZONE** - Kompletna implementacja bazy danych  
**Środowisko:** MySQL/MariaDB 10.11.13 (Hostido shared hosting)

---

## 📊 PODSUMOWANIE SYSTEMU

### Statystyki struktury bazy:
- **Liczba tabel:** 31 tabel głównych
- **Liczba migracji:** 32 pliki migracji 
- **Liczba modeli:** 25 modeli Eloquent
- **Relacje:** 50+ relacji między tabelami
- **Indeksy:** 80+ indeksów wydajnościowych
- **System EAV:** ✅ Zaimplementowany dla atrybutów produktów
- **Audit Trail:** ✅ Kompletne śledzenie zmian
- **Multi-store:** ✅ Wsparcie dla wielu sklepów PrestaShop
- **ERP Integration:** ✅ Uniwersalne mapowania dla Baselinker, Subiekt GT, Dynamics

---

## 🏗️ ARCHITEKTURA SYSTEMU

### 1. **CORE PRODUCT MANAGEMENT**
Centralne tabele zarządzania produktami i kategoriami:

#### 📦 `products` - Główna tabela produktów
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(100) UNIQUE NOT NULL,              -- SKU jako klucz główny
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE,                      -- URL-friendly nazwa
    short_description TEXT,                        -- max 800 znaków
    long_description TEXT,                         -- max 21844 znaków
    product_type ENUM('vehicle', 'spare_part', 'clothing', 'other'),
    manufacturer VARCHAR(200),
    supplier_code VARCHAR(100),                    -- kod dostawcy
    
    -- Wymiary fizyczne
    weight DECIMAL(8,3),                          -- kg
    height DECIMAL(8,2),                          -- cm
    width DECIMAL(8,2),                           -- cm  
    length DECIMAL(8,2),                          -- cm
    ean VARCHAR(20),                              -- kod EAN
    tax_rate DECIMAL(5,2) DEFAULT 23.00,          -- stawka VAT
    
    -- Status i metadane
    is_active BOOLEAN DEFAULT TRUE,
    is_variant_master BOOLEAN DEFAULT FALSE,       -- czy ma warianty
    sort_order INTEGER DEFAULT 0,
    meta_title VARCHAR(300),                       -- SEO
    meta_description VARCHAR(300),                 -- SEO
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL                      -- soft delete
);

-- Indeksy wydajnościowe
CREATE UNIQUE INDEX idx_products_sku ON products(sku);
CREATE UNIQUE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_products_type ON products(product_type);
```

#### 🏷️ `categories` - Kategorie wielopoziomowe (5 poziomów)
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id BIGINT UNSIGNED NULL,               -- self-referencing
    name VARCHAR(300) NOT NULL,
    slug VARCHAR(300),
    description TEXT,
    level INTEGER DEFAULT 0,                      -- poziom 0-4
    path VARCHAR(500),                            -- '/1/2/5' dla szybkich queries
    
    -- Metadane
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    icon VARCHAR(200),                            -- ikona kategorii
    meta_title VARCHAR(300),
    meta_description VARCHAR(300),
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Indeksy dla tree queries
CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_categories_path ON categories(path);
CREATE INDEX idx_categories_level ON categories(level);
```

#### 🔄 `product_variants` - Warianty produktów
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_sku VARCHAR(100) UNIQUE NOT NULL,
    variant_name VARCHAR(200),
    ean VARCHAR(20),                              -- EAN wariantu
    sort_order INTEGER DEFAULT 0,
    
    -- Dziedziczenie właściwości
    inherit_prices BOOLEAN DEFAULT TRUE,           -- czy dziedziczy ceny
    inherit_stock BOOLEAN DEFAULT FALSE,           -- czy dziedziczy stany
    inherit_attributes BOOLEAN DEFAULT TRUE,       -- czy dziedziczy cechy
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX idx_variant_sku ON product_variants(variant_sku);
CREATE INDEX idx_variant_product ON product_variants(product_id);
```

---

### 2. **PRICING & INVENTORY SYSTEM**
System wielowymiarowych cen i stanów magazynowych:

#### 💰 `price_groups` - Grupy cenowe (8 typów)
```sql
CREATE TABLE price_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,                   -- Detaliczna, Dealer Standard, etc.
    code VARCHAR(50) UNIQUE NOT NULL,             -- retail, dealer_std, etc.
    is_default BOOLEAN DEFAULT FALSE,             -- grupa domyślna
    margin_percentage DECIMAL(5,2),               -- domyślna marża
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Predefiniowane grupy:
-- retail, dealer_standard, dealer_premium, workshop, workshop_premium, 
-- nursery_commission_drop, employee, special
```

#### 🏷️ `product_prices` - Ceny produktów per grupa
```sql
CREATE TABLE product_prices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,      -- opcjonalnie dla wariantów
    price_group_id BIGINT UNSIGNED NOT NULL,
    
    price_net DECIMAL(10,2) NOT NULL,             -- cena netto
    price_gross DECIMAL(10,2) NOT NULL,           -- cena brutto
    cost_price DECIMAL(10,2),                     -- cena zakupu (tylko Admin/Manager)
    
    currency VARCHAR(3) DEFAULT 'PLN',
    valid_from TIMESTAMP,
    valid_to TIMESTAMP,
    margin_percentage DECIMAL(5,2),               -- obliczona marża
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_price (product_id, product_variant_id, price_group_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (price_group_id) REFERENCES price_groups(id)
);
```

#### 🏪 `warehouses` - Magazyny (6+ lokalizacji)
```sql
CREATE TABLE warehouses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,                   -- MPPTRADE, Pitbike.pl, etc.
    code VARCHAR(50) UNIQUE NOT NULL,             -- mpptrade, pitbike, etc.
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    
    -- Integracje
    erp_mapping JSON,                             -- mapowanie z ERP
    prestashop_mapping JSON,                      -- mapowanie z PrestaShop
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Predefiniowane magazyny:
-- MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje
```

#### 📦 `product_stock` - Stany magazynowe
```sql
CREATE TABLE product_stock (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    
    quantity INTEGER DEFAULT 0,
    reserved_quantity INTEGER DEFAULT 0,          -- zarezerwowane
    minimum_stock INTEGER DEFAULT 0,              -- próg minimalny
    
    -- Lokalizacje i metadane
    warehouse_location TEXT,                      -- lokalizacja w magazynie
    last_delivery_date DATE,                      -- data ostatniej dostawy
    delivery_status ENUM('ordered', 'not_ordered', 'cancelled', 'in_container', 
                         'delayed', 'receiving', 'available'),
    notes TEXT,                                   -- uwagi magazynu
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_stock (product_id, product_variant_id, warehouse_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);
```

---

### 3. **MEDIA & FILES MANAGEMENT**
System zarządzania plikami i zdjęciami:

#### 🖼️ `media` - Zdjęcia (Polymorphic)
```sql
CREATE TABLE media (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mediable_type VARCHAR(100),                   -- Product, ProductVariant
    mediable_id BIGINT UNSIGNED,
    
    file_name VARCHAR(300) NOT NULL,
    original_name VARCHAR(300),
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER,                            -- rozmiar w bajtach
    
    -- Metadane obrazów
    mime_type VARCHAR(100),                       -- jpg, png, webp
    width INTEGER,                                -- szerokość
    height INTEGER,                               -- wysokość
    alt_text VARCHAR(300),                        -- tekst alternatywny
    sort_order INTEGER DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,             -- główne zdjęcie
    
    -- Integracje
    prestashop_mapping JSON,                      -- mapowanie per sklep
    sync_status ENUM('pending', 'synced', 'error', 'ignored'),
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_media_polymorphic ON media(mediable_type, mediable_id);
CREATE INDEX idx_media_primary ON media(is_primary);
```

#### 📄 `file_uploads` - Dokumenty (Polymorphic)
```sql
CREATE TABLE file_uploads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uploadable_type VARCHAR(100),                 -- Container, Order, Product
    uploadable_id BIGINT UNSIGNED,
    
    file_name VARCHAR(300) NOT NULL,
    original_name VARCHAR(300),
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),                       -- pdf, xlsx, zip, xml
    
    file_type ENUM('document', 'spreadsheet', 'archive', 'other'),
    access_level ENUM('admin', 'manager', 'all'), -- poziom dostępu
    uploaded_by BIGINT UNSIGNED,                  -- kto uploadował
    description TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
```

---

### 4. **EAV SYSTEM (Entity-Attribute-Value)**
Elastyczny system atrybutów produktów:

#### 🏷️ `product_attributes` - Definicje atrybutów
```sql
CREATE TABLE product_attributes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,                   -- Model, Oryginał, Zamiennik
    code VARCHAR(100) UNIQUE NOT NULL,            -- model, original, replacement
    attribute_type ENUM('text', 'number', 'boolean', 'select', 'multiselect', 'date'),
    
    is_required BOOLEAN DEFAULT FALSE,
    is_filterable BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    
    -- Ograniczenia i walidacja
    validation_rules JSON,                        -- reguły walidacji
    options JSON,                                 -- opcje dla select/multiselect
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 📝 `product_attribute_values` - Wartości atrybutów
```sql
CREATE TABLE product_attribute_values (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    
    -- Różne typy wartości
    value_text TEXT,                              -- dla tekstów
    value_number DECIMAL(15,6),                   -- dla liczb
    value_boolean BOOLEAN,                        -- dla tak/nie
    value_date DATE,                              -- dla dat
    value_json JSON,                              -- dla złożonych struktur
    
    is_inherited BOOLEAN DEFAULT FALSE,           -- czy dziedziczy z produktu głównego
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_attribute (product_id, product_variant_id, attribute_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES product_attributes(id)
);
```

---

### 5. **INTEGRATION SYSTEM**
Uniwersalny system mapowań i integracji:

#### 🔄 `integration_mappings` - Mapowania zewnętrzne
```sql
CREATE TABLE integration_mappings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mappable_type VARCHAR(100),                   -- Product, Category, PriceGroup, Warehouse
    mappable_id BIGINT UNSIGNED,
    integration_type ENUM('prestashop', 'baselinker', 'subiekt_gt', 'dynamics'),
    integration_identifier VARCHAR(200),          -- klucz w systemie zewnętrznym
    external_id INTEGER,                          -- ID w systemie zewnętrznym
    external_data JSON,                           -- pełne dane z systemu zewnętrznego
    
    -- Status synchronizacji
    sync_status ENUM('pending', 'synced', 'error', 'conflict'),
    last_sync_at TIMESTAMP,
    sync_direction ENUM('both', 'to_external', 'from_external', 'disabled'),
    error_message TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_mapping (mappable_type, mappable_id, integration_type, integration_identifier)
);
```

#### 🏪 `prestashop_shops` - Sklepy PrestaShop
```sql
CREATE TABLE prestashop_shops (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    shop_name VARCHAR(200) NOT NULL,
    shop_url VARCHAR(500) NOT NULL,
    api_key VARCHAR(255),                         -- zaszyfrowany klucz API
    
    -- Konfiguracja połączenia
    is_active BOOLEAN DEFAULT TRUE,
    connection_status ENUM('connected', 'disconnected', 'error'),
    last_sync_at TIMESTAMP,
    
    -- Mapowania sklepowe
    default_category_mapping JSON,                -- domyślne mapowanie kategorii
    price_group_mappings JSON,                    -- mapowanie grup cenowych
    warehouse_mappings JSON,                      -- mapowanie magazynów
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 🔧 `erp_connections` - Połączenia ERP
```sql
CREATE TABLE erp_connections (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    connection_name VARCHAR(200) NOT NULL,
    erp_type ENUM('baselinker', 'subiekt_gt', 'dynamics'),
    
    -- Konfiguracja połączenia
    connection_config JSON,                       -- dane połączenia (zaszyfrowane)
    is_active BOOLEAN DEFAULT TRUE,
    connection_status ENUM('connected', 'disconnected', 'error'),
    last_sync_at TIMESTAMP,
    
    -- Mapowania ERP
    field_mappings JSON,                          -- mapowanie pól
    sync_settings JSON,                           -- ustawienia synchronizacji
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

### 6. **USER MANAGEMENT & PERMISSIONS**
7-poziomowy system uprawnień:

#### 👤 `users` - Rozszerzona tabela użytkowników
```sql
ALTER TABLE users ADD COLUMN (
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    company VARCHAR(200),
    position VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP,
    avatar VARCHAR(300),
    
    -- Preferencje użytkownika
    preferred_language VARCHAR(5) DEFAULT 'pl',
    timezone VARCHAR(50) DEFAULT 'Europe/Warsaw',
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    ui_preferences JSON,                          -- ustawienia interfejsu
    notification_settings JSON,
    
    -- OAuth fields
    google_id VARCHAR(255),
    microsoft_id VARCHAR(255),
    oauth_provider VARCHAR(50),
    oauth_avatar VARCHAR(500),
    
    -- Dashboard preferences
    dashboard_layout VARCHAR(50) DEFAULT 'default',
    dashboard_widgets JSON
);
```

#### 🔐 Role i uprawnienia (Spatie Laravel Permission)
**7 poziomów ról:**
1. **Admin** - pełne uprawnienia (47 permissions)
2. **Manager** - CRUD produktów + import/export
3. **Editor** - edycja opisów, zdjęć, kategorii  
4. **Warehouseman** - panel dostaw
5. **Salesperson** - zamówienia + rezerwacje (bez cen zakupu)
6. **Claims** - reklamacje
7. **User** - tylko odczyt

**Granularne uprawnienia:**
- `products.*` (create, read, update, delete, export, import)
- `categories.*` (create, read, update, delete)
- `media.*` (create, read, update, delete, upload)
- `prices.*` (read, update) - tylko Admin/Manager
- `integrations.*` (read, sync, config)

---

### 7. **AUDIT & MONITORING**
Kompletny system śledzenia zmian:

#### 📊 `audit_logs` - Śledzenie zmian
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,                 -- kto wykonał
    auditable_type VARCHAR(100),                  -- Product, Category, etc.
    auditable_id BIGINT UNSIGNED,                 -- ID obiektu
    event VARCHAR(50),                            -- created, updated, deleted
    
    old_values JSON,                              -- stare wartości
    new_values JSON,                              -- nowe wartości
    
    -- Metadane
    ip_address VARCHAR(45),
    user_agent TEXT,
    source ENUM('web', 'api', 'import', 'sync'),  -- źródło zmiany
    comment TEXT,                                 -- opcjonalny komentarz
    
    created_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_audit_type_id ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_created ON audit_logs(created_at);
```

#### 🔔 `notifications` - System powiadomień
```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,                      -- UUID (Laravel format)
    type VARCHAR(200),                            -- klasa powiadomienia
    notifiable_type VARCHAR(100),                 -- User
    notifiable_id BIGINT UNSIGNED,                -- user_id
    data JSON,                                    -- dane powiadomienia
    read_at TIMESTAMP NULL,                       -- kiedy przeczytane
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

### 8. **RELATIONS SUMMARY**
Główne relacje między tabelami:

#### **Product Relations:**
- `Product` hasMany `ProductVariant`
- `Product` belongsToMany `Category` (przez `product_categories`)
- `Product` hasMany `ProductPrice`
- `Product` hasMany `ProductStock`
- `Product` morphMany `Media`
- `Product` hasMany `ProductAttributeValue`
- `Product` morphMany `IntegrationMapping`

#### **Category Relations:**
- `Category` belongsTo `Category` (parent)
- `Category` hasMany `Category` (children)  
- `Category` belongsToMany `Product`

#### **Price & Stock Relations:**
- `ProductPrice` belongsTo `Product`, `ProductVariant`, `PriceGroup`
- `ProductStock` belongsTo `Product`, `ProductVariant`, `Warehouse`

#### **EAV Relations:**
- `ProductAttributeValue` belongsTo `Product`, `ProductVariant`, `ProductAttribute`

#### **Integration Relations:**
- `IntegrationMapping` morphTo (Product, Category, PriceGroup, Warehouse)
- `PrestaShopShop` hasMany `IntegrationMapping`
- `ERPConnection` hasMany `IntegrationMapping`

---

## 🚀 PERFORMANCE OPTIMIZATIONS

### **Indeksy strategiczne:**
- **Core:** SKU, slug indeksy na products/variants
- **Relations:** Foreign key indeksy na wszystkich relations
- **Search:** Path indeksy na categories
- **Performance:** Composite indeksy na często łączone tabele
- **JSONB:** GIN indeksy na JSON fields dla szybkich searches

### **Query optimizations:**
- Eager loading configuration dla relationships
- Query scopes dla często używanych filtrów  
- Partial indeksy dla active=true records
- Strategiczne denormalizacje (path w categories)

---

## 🔄 MIGRATION STATUS

### **Ukończone migracje:** ✅ 32/32
1. `2024_01_01_000001` - products table
2. `2024_01_01_000002` - categories table
3. `2024_01_01_000003` - product_variants table
4. `2024_01_01_000004` - core performance indexes
5. `2024_01_01_000005` - product_categories pivot
6. `2024_01_01_000006` - price_groups table
7. `2024_01_01_000007` - warehouses table
8. `2024_01_01_000008` - product_prices table
9. `2024_01_01_000009` - product_stock table
10. `2024_01_01_000010` - media table
11. `2024_01_01_000011` - file_uploads table
12. `2024_01_01_000012` - product_attributes table
13. `2024_01_01_000013` - product_attribute_values table
14. `2024_01_01_000014` - integration_mappings table
15. `2024_01_01_000015` - media relations performance indexes
16. `2024_01_01_000016` - extend users table
17. `2024_01_01_000017` - audit_logs table
18. `2024_01_01_000018` - notifications table
19. `2024_01_01_000019` - oauth fields to users
20. `2024_01_01_000020` - oauth_audit_logs table
21. `2024_01_01_000025` - dashboard preferences to users
22. `2024_01_01_000026` - prestashop_shops table
23. `2024_01_01_000027` - erp_connections table
24. `2024_01_01_000028` - sync_jobs table
25. `2024_01_01_000029` - integration_logs table
26. `2024_01_01_000030` - system_settings table
27. `2024_01_01_000031` - backup_jobs table
28. `2024_01_01_000032` - maintenance_tasks table
29. `2024_01_01_000033` - admin_notifications table
30. `2024_01_01_000034` - system_reports table
31. `2024_01_01_000035` - api_usage_logs table
32. `2024_01_01_000036` - admin_themes table

### **Eloquent Models:** ✅ 25/25
Wszystkie modele z pełnymi relacjami, accessors/mutators, i business logic.

---

## 🎯 BUSINESS RULES

### **SKU Management:**
- SKU jest unikalny globalnie (products + variants)
- Format: PREFIX-CATEGORY-NUMBER (np. VEH-QUAD-001, PART-BRAKE-123)
- Auto-generation dla nowych produktów

### **Category Hierarchy:**
- Maximum 5 poziomów zagnieżdżenia (0-4)
- Path automatycznie aktualizowana przy zmianach
- Soft delete z cascade prevention

### **Pricing Rules:**  
- Każdy produkt może mieć ceny w każdej grupie cenowej
- Ceny wariantów mogą być dziedziczone lub własne
- Margin calculation: (price_net - cost_price) / cost_price * 100

### **Stock Management:**
- Stany per magazyn per produkt/wariant
- Reserved quantity dla zamówień
- Low stock alerts oparte na minimum_stock

### **Media Handling:**
- Primary image per produkt/wariant  
- Support dla jpg, png, webp
- Auto-resize i thumbnail generation (planned)

---

## 🔧 MAINTENANCE & MONITORING

### **Backup Strategy:**
- Daily automated backups via `BackupJob` model
- 30-day retention policy
- Critical table prioritization

### **Performance Monitoring:**
- Slow query logging
- Index usage statistics  
- Connection monitoring
- Storage usage alerts

### **Data Archival:**
- `audit_logs` archival po 12 miesiącach
- `notifications` cleanup po 6 miesiącach
- Soft deleted records cleanup po 2 latach

---

## 🚨 KRYTYCZNE UWAGI DEPLOYMENT

### **Production Requirements:**
1. **MySQL/MariaDB 10.11+** z JSON support
2. **PHP 8.3+** dla nowszych JSON functions
3. **InnoDB engine** dla foreign keys i transactions
4. **UTF8MB4 charset** dla emoji support
5. **Minimum 2GB RAM** dla cache i indexes

### **Security:**
- Wszystkie JSON fields sanitized przed zapisem
- Encrypted fields: `api_key`, `connection_config`
- Audit logs dla wszystkich CRUD operations
- Rate limiting na API endpoints

### **Hostido Specific:**
- Shared hosting limitations: no root access
- MySQL shared pool - optimalizacja queries krytyczna
- File permissions: 755 dla directories, 644 dla plików
- Backup space limitation: compress before backup

---

**🏢 MPP TRADE - Enterprise Product Management System**  
**📅 Dokumentacja utworzona: 2025-09-11**  
**🔧 Wersja bazy danych: 1.0.0**  
**🌐 Środowisko produkcyjne: https://ppm.mpptrade.pl**

---

*Ten dokument jest żywą dokumentacją i będzie aktualizowany wraz z rozwojem systemu.*