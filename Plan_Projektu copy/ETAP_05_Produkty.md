# ❌ ETAP_05: Moduł Produktów - Rdzeń Aplikacji

**UWAGA** WYŁĄCZ autoryzację AdminMiddleware na czas developmentu!

**Status ETAPU:** ❌ **NIE ROZPOCZĘTE**  
**Szacowany czas:** 60 godzin  
**Priorytet:** 🔴 KRYTYCZNY  
**Zależności:** ETAP_04_Panel_Admin.md (ukończony)  
**Następny etap:** ETAP_06_Import_Export.md  

---

## 🎯 OPIS ETAPU

Piąty etap budowy aplikacji PPM to implementacja głównego modułu produktów - serca całego systemu PIM. Obejmuje kompletny interfejs CRUD dla produktów, zaawansowany system kategorii, zarządzanie wariantami, cenami, stanami magazynowymi, mediami oraz system atrybutów EAV. To najważniejszy etap całego projektu.

### 🏗️ **GŁÓWNE KOMPONENTY MODUŁU PRODUKTÓW:**
- **📦 Product Management** - Kompleksny CRUD produktów
- **📂 Category System** - Wielopoziomowe kategorie (5 poziomów)
- **🔄 Product Variants** - System wariantów z dziedziczeniem
- **💰 Price Management** - 7 grup cenowych z marżami
- **📊 Stock Management** - Wielomagazynowe stany
- **🖼️ Media System** - Galeria do 20 zdjęć per produkt
- **🏷️ Attribute System** - EAV dla cech i parametrów
- **🔍 Advanced Search** - Wyszukiwanie i filtrowanie
- **⚡ Bulk Operations** - Masowe operacje
- **📋 Templates** - Szablony produktów

### Kluczowe osiągnięcia etapu:
- ✅ Kompletny system CRUD produktów z wszystkimi polami
- ✅ Wielopoziomowy system kategorii z drag & drop
- ✅ System wariantów z dziedziczeniem parametrów
- ✅ Zarządzanie 7 grupami cenowymi i marżami
- ✅ Wielomagazynowy system stanów z rezerwacjami
- ✅ Galeria zdjęć z upload, crop, optimization
- ✅ EAV system dla atrybutów i cech produktów
- ✅ Zaawansowane wyszukiwanie i filtrowanie

---

## 📋 SZCZEGÓŁOWY PLAN ZADAŃ

- ❌ **1. PRODUCT CRUD INTERFACE - PODSTAWA SYSTEMU**
  - ❌ **1.1 Product List View - Lista Produktów**
    - ❌ **1.1.1 Main Product Listing Component**
      - ❌ **1.1.1.1 Livewire ProductList Component**
        - ❌ 1.1.1.1.1 ProductList component z advanced filtering
        - ❌ 1.1.1.1.2 Server-side pagination z per-page options (25, 50, 100, 200)
        - ❌ 1.1.1.1.3 Sortowanie po wszystkich głównych kolumnach
        - ❌ 1.1.1.1.4 Search box z real-time filtering (SKU, nazwa, kod dostawcy)
        - ❌ 1.1.1.1.5 Bulk selection z checkbox all/none
      - ❌ **1.1.1.2 Advanced Filtering System**
        - ❌ 1.1.1.2.1 Category tree filter z expand/collapse
        - ❌ 1.1.1.2.2 Status filters (active/inactive, published/draft)
        - ❌ 1.1.1.2.3 Stock status filters (in_stock, low_stock, out_of_stock)
        - ❌ 1.1.1.2.4 Price range slider filter
        - ❌ 1.1.1.2.5 Date range filters (created, updated, last_sync)
        - ❌ 1.1.1.2.6 Product type filter (vehicle, spare_part, clothing, other)
        - ❌ 1.1.1.2.7 Integration status filter (synced, pending, error)
        - ❌ 1.1.1.2.8 Media status filter (has_images, no_images, primary_image)

    - ❌ **1.1.2 Product List Display Options**
      - ❌ **1.1.2.1 Display Modes**
        - ❌ 1.1.2.1.1 Table view z customizable columns
        - ❌ 1.1.2.1.2 Grid view z product cards
        - ❌ 1.1.2.1.3 Compact list view
        - ❌ 1.1.2.1.4 View preferences persistence per user
        - ❌ 1.1.2.1.5 Column visibility toggles
      - ❌ **1.1.2.2 Quick Actions**
        - ❌ 1.1.2.2.1 Quick edit modal dla podstawowych pól
        - ❌ 1.1.2.2.2 Quick status toggle (active/inactive)
        - ❌ 1.1.2.2.3 Quick duplicate product
        - ❌ 1.1.2.2.4 Quick sync z integracjami
        - ❌ 1.1.2.2.5 Quick view product details

  - ❌ **1.2 Product Create/Edit Form**
    - ❌ **1.2.1 Main Product Form**
      - ❌ **1.2.1.1 Basic Information Tab**
        - ❌ 1.2.1.1.1 Livewire ProductForm component z tab system
        - ❌ 1.2.1.1.2 SKU field z validation i uniqueness check
        - ❌ 1.2.1.1.3 Product name z live slug generation
        - ❌ 1.2.1.1.4 Product type selection z conditional fields
        - ❌ 1.2.1.1.5 Manufacturer selection/add z autocomplete
        - ❌ 1.2.1.1.6 Supplier code field
        - ❌ 1.2.1.1.7 EAN field z barcode validation
      - ❌ **1.2.1.2 Description Tab**
        - ❌ 1.2.1.2.1 Short description WYSIWYG editor (max 800 chars)
        - ❌ 1.2.1.2.2 Long description WYSIWYG editor (max 21844 chars)
        - ❌ 1.2.1.2.3 Character counter z warnings
        - ❌ 1.2.1.2.4 HTML preview mode
        - ❌ 1.2.1.2.5 Template insertion dla common descriptions
        - ❌ 1.2.1.2.6 SEO meta fields (title, description)
      - ❌ **1.2.1.3 Physical Properties Tab**
        - ❌ 1.2.1.3.1 Dimensions fields (height, width, length) z unit selection
        - ❌ 1.2.1.3.2 Weight field z automatic calculations
        - ❌ 1.2.1.3.3 Tax rate selection z default 23%
        - ❌ 1.2.1.3.4 Physical properties validation
        - ❌ 1.2.1.3.5 Volume calculation display

    - ❌ **1.2.2 Advanced Product Settings**
      - ❌ **1.2.2.1 Status & Publishing**
        - ❌ 1.2.2.1.1 Active/inactive toggle z confirmation
        - ❌ 1.2.2.1.2 Visibility settings per integration
        - ❌ 1.2.2.1.3 Publishing schedule (available from/to dates)
        - ❌ 1.2.2.1.4 Sort order field dla listings
        - ❌ 1.2.2.1.5 Featured product toggle
      - ❌ **1.2.2.2 Advanced Options**
        - ❌ 1.2.2.2.1 Custom fields dla specific product types
        - ❌ 1.2.2.2.2 Notes field dla internal use (Admin/Manager only)
        - ❌ 1.2.2.2.3 Tags system dla organization
        - ❌ 1.2.2.2.4 Related products selection
        - ❌ 1.2.2.2.5 Cross-sell/up-sell products

- ❌ **2. CATEGORY SYSTEM - WIELOPOZIOMOWE KATEGORIE**
  - ❌ **2.1 Category Tree Management**
    - ❌ **2.1.1 Category Tree Component**
      - ❌ **2.1.1.1 Interactive Category Tree**
        - ❌ 2.1.1.1.1 Livewire CategoryTree component
        - ❌ 2.1.1.1.2 Nested sortable tree (max 5 levels deep)
        - ❌ 2.1.1.1.3 Drag & drop reordering z live updates
        - ❌ 2.1.1.1.4 Expand/collapse nodes z state persistence
        - ❌ 2.1.1.1.5 Search within category tree
      - ❌ **2.1.1.2 Category Tree Actions**
        - ❌ 2.1.1.2.1 Add subcategory at any level
        - ❌ 2.1.1.2.2 Edit category inline lub via modal
        - ❌ 2.1.1.2.3 Delete category z product reassignment
        - ❌ 2.1.1.2.4 Move category to different parent
        - ❌ 2.1.1.2.5 Bulk category operations

    - ❌ **2.1.2 Category Form Management**
      - ❌ **2.1.2.1 Category Create/Edit Form**
        - ❌ 2.1.2.1.1 Livewire CategoryForm component
        - ❌ 2.1.2.1.2 Category name z slug auto-generation
        - ❌ 2.1.2.1.3 Parent category selection z tree widget
        - ❌ 2.1.2.1.4 Category description field
        - ❌ 2.1.2.1.5 Category icon selection/upload
        - ❌ 2.1.2.1.6 Sort order field
      - ❌ **2.1.2.2 Category SEO & Settings**
        - ❌ 2.1.2.2.1 SEO meta title i description
        - ❌ 2.1.2.2.2 Category visibility settings
        - ❌ 2.1.2.2.3 Category-specific attributes configuration
        - ❌ 2.1.2.2.4 Default values dla products w kategorii
        - ❌ 2.1.2.2.5 Category image/banner upload

  - ❌ **2.2 Product-Category Assignment**
    - ❌ **2.2.1 Category Assignment Interface**
      - ❌ **2.2.2.1 Product Category Selection**
        - ❌ 2.2.2.1.1 Multiple category assignment per product
        - ❌ 2.2.2.1.2 Primary category designation dla PrestaShop
        - ❌ 2.2.2.1.3 Category tree selector w product form
        - ❌ 2.2.2.1.4 Breadcrumb display dla selected categories
        - ❌ 2.2.2.1.5 Category inheritance rules
      - ❌ **2.2.2.2 Bulk Category Operations**
        - ❌ 2.2.2.2.1 Bulk assign categories to products
        - ❌ 2.2.2.2.2 Bulk remove categories from products
        - ❌ 2.2.2.2.3 Bulk move products between categories
        - ❌ 2.2.2.2.4 Category merge functionality
        - ❌ 2.2.2.2.5 Category deletion z product reassignment

- ❌ **3. PRODUCT VARIANTS - SYSTEM WARIANTÓW**
  - ❌ **3.1 Variant Management Interface**
    - ❌ **3.1.1 Variant List & Creation**
      - ❌ **3.1.1.1 Product Variants Tab**
        - ❌ 3.1.1.1.1 Livewire ProductVariants component
        - ❌ 3.1.1.1.2 Variants table z inheritance indicators
        - ❌ 3.1.1.1.3 Add variant button z quick form
        - ❌ 3.1.1.1.4 Variant status toggles (active/inactive)
        - ❌ 3.1.1.1.5 Variant sort order management
      - ❌ **3.1.1.2 Variant Configuration**
        - ❌ 3.1.1.2.1 Variant SKU generation rules
        - ❌ 3.1.1.2.2 Variant name/title field
        - ❌ 3.1.1.2.3 Variant EAN field
        - ❌ 3.1.1.2.4 Inheritance toggles (prices, stock, attributes, media)
        - ❌ 3.1.1.2.5 Variant-specific attributes

    - ❌ **3.1.2 Variant Data Management**
      - ❌ **3.1.2.1 Inheritance System**
        - ❌ 3.1.2.1.1 Price inheritance z override capability
        - ❌ 3.1.2.1.2 Stock inheritance z separate tracking
        - ❌ 3.1.2.1.3 Attribute inheritance z variant-specific values
        - ❌ 3.1.2.1.4 Media inheritance z additional variant images
        - ❌ 3.1.2.1.5 Category inheritance od parent product
      - ❌ **3.1.2.2 Variant-Specific Data**
        - ❌ 3.1.2.2.1 Dedicated prices per variant per price group
        - ❌ 3.1.2.2.2 Separate stock levels per variant per warehouse
        - ❌ 3.1.2.2.3 Variant-specific warehouse locations
        - ❌ 3.1.2.2.4 Variant delivery status tracking
        - ❌ 3.1.2.2.5 Variant sync status per integration

- ❌ **4. PRICE MANAGEMENT - 7 GRUP CENOWYCH**
  - ❌ **4.1 Price Group Management**
    - ❌ **4.1.1 Price Groups Configuration**
      - ❌ **4.1.1.1 Price Group Setup**
        - ❌ 4.1.1.1.1 Livewire PriceGroups management component
        - ❌ 4.1.1.1.2 7 predefined price groups (Detaliczna, Dealer Standard, Premium, etc.)
        - ❌ 4.1.1.1.3 Price group activation/deactivation
        - ❌ 4.1.1.1.4 Default margin settings per group
        - ❌ 4.1.1.1.5 Price group ordering i display names
      - ❌ **4.1.1.2 Price Group Rules**
        - ❌ 4.1.1.2.1 Minimum margin enforcement
        - ❌ 4.1.1.2.2 Maximum discount limits
        - ❌ 4.1.1.2.3 Price rounding rules
        - ❌ 4.1.1.2.4 Currency handling (PLN default)
        - ❌ 4.1.1.2.5 Price approval workflows dla large changes

    - ❌ **4.1.2 Product Price Management**
      - ❌ **4.1.2.1 Price Entry Interface**
        - ❌ 4.1.2.1.1 Livewire ProductPrices component
        - ❌ 4.1.2.1.2 Price grid z all price groups
        - ❌ 4.1.2.1.3 Cost price field (visible dla Admin/Manager only)
        - ❌ 4.1.2.1.4 Automatic margin calculation i display
        - ❌ 4.1.2.1.5 Price history tracking
      - ❌ **4.1.2.2 Advanced Pricing Features**
        - ❌ 4.1.2.2.1 Bulk price updates z percentage/fixed adjustments
        - ❌ 4.1.2.2.2 Price import from cost prices z margin application
        - ❌ 4.1.2.2.3 Price comparison z competitor data
        - ❌ 4.1.2.2.4 Price alerts dla margin violations
        - ❌ 4.1.2.2.5 Price scheduling (effective from/to dates)

  - ❌ **4.2 Advanced Pricing Tools**
    - ❌ **4.2.1 Pricing Analytics**
      - ❌ **4.2.1.1 Price Analysis Dashboard**
        - ❌ 4.2.1.1.1 Margin analysis per product/category
        - ❌ 4.2.1.1.2 Price distribution histograms
        - ❌ 4.2.1.1.3 Pricing competitiveness indicators
        - ❌ 4.2.1.1.4 Price change impact analysis
        - ❌ 4.2.1.1.5 Price group performance metrics
      - ❌ **4.2.1.2 Pricing Recommendations**
        - ❌ 4.2.1.2.1 Margin optimization suggestions
        - ❌ 4.2.1.2.2 Price gap analysis
        - ❌ 4.2.1.2.3 Market positioning recommendations
        - ❌ 4.2.1.2.4 Price elasticity analysis (future)
        - ❌ 4.2.1.2.5 Automated repricing rules (future)

- ❌ **5. STOCK MANAGEMENT - WIELOMAGAZYNOWY SYSTEM**
  - ❌ **5.1 Stock Level Management**
    - ❌ **5.1.1 Stock Interface**
      - ❌ **5.1.1.1 Stock Management Component**
        - ❌ 5.1.1.1.1 Livewire ProductStock component
        - ❌ 5.1.1.1.2 Stock grid z all warehouses
        - ❌ 5.1.1.1.3 Available quantity calculation (quantity - reserved)
        - ❌ 5.1.1.1.4 Minimum stock level alerts
        - ❌ 5.1.1.1.5 Stock movement history
      - ❌ **5.1.1.2 Stock Operations**
        - ❌ 5.1.1.2.1 Manual stock adjustments z reason codes
        - ❌ 5.1.1.2.2 Stock transfers between warehouses
        - ❌ 5.1.1.2.3 Stock reservations dla orders
        - ❌ 5.1.1.2.4 Bulk stock updates
        - ❌ 5.1.1.2.5 Stock import from ERP systems

    - ❌ **5.1.2 Warehouse Location Management**
      - ❌ **5.1.2.1 Location Tracking**
        - ❌ 5.1.2.1.1 Multi-value location field (separated by ';')
        - ❌ 5.1.2.1.2 Location autocomplete z suggestions
        - ❌ 5.1.2.1.3 Location mapping per warehouse
        - ❌ 5.1.2.1.4 Location-based picking lists
        - ❌ 5.1.2.1.5 Location occupancy reports
      - ❌ **5.1.2.2 Delivery Status Tracking**
        - ❌ 5.1.2.2.1 Delivery status selection (ordered, in_container, etc.)
        - ❌ 5.1.2.2.2 Expected delivery date tracking
        - ❌ 5.1.2.2.3 Container assignment i tracking
        - ❌ 5.1.2.2.4 Delivery delay alerts
        - ❌ 5.1.2.2.5 Receiving workflow integration

  - ❌ **5.2 Stock Analytics & Reporting**
    - ❌ **5.2.1 Stock Reports**
      - ❌ **5.2.1.1 Inventory Reports**
        - ❌ 5.2.1.1.1 Current stock levels per warehouse
        - ❌ 5.2.1.1.2 Low stock alerts i recommendations
        - ❌ 5.2.1.1.3 Overstock identification
        - ❌ 5.2.1.1.4 Stock valuation reports
        - ❌ 5.2.1.1.5 Stock aging analysis
      - ❌ **5.2.1.2 Movement Analysis**
        - ❌ 5.2.1.2.1 Stock movement velocity analysis
        - ❌ 5.2.1.2.2 Seasonal stock patterns
        - ❌ 5.2.1.2.3 Stock turnover ratios
        - ❌ 5.2.1.2.4 Dead stock identification
        - ❌ 5.2.1.2.5 Reorder point recommendations

- ❌ **6. MEDIA SYSTEM - ZARZĄDZANIE ZDJĘCIAMI**
  - ❌ **6.1 Media Upload & Management**
    - ❌ **6.1.1 Image Upload System**
      - ❌ **6.1.1.1 Upload Interface**
        - ❌ 6.1.1.1.1 Livewire MediaManager component
        - ❌ 6.1.1.1.2 Drag & drop image upload (max 20 per product)
        - ❌ 6.1.1.1.3 Bulk image upload z batch processing
        - ❌ 6.1.1.1.4 Supported formats (jpg, jpeg, png, webp)
        - ❌ 6.1.1.1.5 File size validation i compression
      - ❌ **6.1.1.2 Image Processing**
        - ❌ 6.1.1.2.1 Automatic image resizing dla różnych użyć
        - ❌ 6.1.1.2.2 Image optimization dla web performance
        - ❌ 6.1.1.2.3 Thumbnail generation
        - ❌ 6.1.1.2.4 WebP conversion dla modern browsers
        - ❌ 6.1.1.2.5 Image cropping tool

    - ❌ **6.1.2 Media Organization**
      - ❌ **6.1.2.1 Image Management**
        - ❌ 6.1.2.1.1 Image gallery z sortable thumbnails
        - ❌ 6.1.2.1.2 Primary image designation
        - ❌ 6.1.2.1.3 Alt text dla każdego obrazu (SEO/accessibility)
        - ❌ 6.1.2.1.4 Image tagging i categorization
        - ❌ 6.1.2.1.5 Image usage tracking across integrations
      - ❌ **6.1.2.2 Advanced Media Features**
        - ❌ 6.1.2.2.1 Image duplication detection
        - ❌ 6.1.2.2.2 Bulk image operations (delete, tag, optimize)
        - ❌ 6.1.2.2.3 Image CDN integration preparation
        - ❌ 6.1.2.2.4 Image backup i recovery
        - ❌ 6.1.2.2.5 Image analytics (views, clicks, conversions)

  - ❌ **6.2 Media Sync & Integration**
    - ❌ **6.2.1 Integration Media Management**
      - ❌ **6.2.1.1 PrestaShop Image Sync**
        - ❌ 6.2.1.1.1 Image upload to PrestaShop per shop
        - ❌ 6.2.1.1.2 PrestaShop image structure compliance
        - ❌ 6.2.1.1.3 Image sync status tracking
        - ❌ 6.2.1.1.4 Failed image upload handling
        - ❌ 6.2.1.1.5 Image URL mapping i storage
      - ❌ **6.2.1.2 Multi-Platform Media**
        - ❌ 6.2.1.2.1 Different images per integration
        - ❌ 6.2.1.2.2 Image format conversion per platform
        - ❌ 6.2.1.2.3 Platform-specific image optimization
        - ❌ 6.2.1.2.4 Image version control
        - ❌ 6.2.1.2.5 Rollback capabilities dla image changes

- ❌ **7. ATTRIBUTE SYSTEM - EAV IMPLEMENTACJA**
  - ❌ **7.1 Attribute Definition System**
    - ❌ **7.1.1 Attribute Types & Configuration**
      - ❌ **7.1.1.1 Attribute Management**
        - ❌ 7.1.1.1.1 Livewire AttributeManager component
        - ❌ 7.1.1.1.2 Attribute types (text, number, boolean, select, multiselect, date)
        - ❌ 7.1.1.1.3 Attribute validation rules configuration
        - ❌ 7.1.1.1.4 Required/optional attribute settings
        - ❌ 7.1.1.1.5 Attribute groups i categorization
      - ❌ **7.1.1.2 Vehicle-Specific Attributes**
        - ❌ 7.1.1.2.1 Model attribute (multi-value dla części zamiennych)
        - ❌ 7.1.1.2.2 Oryginał attribute (compatibility list)
        - ❌ 7.1.1.2.3 Zamiennik attribute (alternative parts)
        - ❌ 7.1.1.2.4 VIN number tracking
        - ❌ 7.1.1.2.5 Engine number i year tracking

    - ❌ **7.1.2 Attribute Value Management**
      - ❌ **7.1.2.1 Value Entry Interface**
        - ❌ 7.1.2.1.1 Dynamic attribute forms per product type
        - ❌ 7.1.2.1.2 Attribute value suggestions i autocomplete
        - ❌ 7.1.2.1.3 Multi-value attribute support
        - ❌ 7.1.2.1.4 Attribute inheritance dla variants
        - ❌ 7.1.2.1.5 Attribute value validation i formatting
      - ❌ **7.1.2.2 Attribute Templates**
        - ❌ 7.1.2.2.1 Product type templates (vehicle, spare_part, etc.)
        - ❌ 7.1.2.2.2 Category-specific attribute sets
        - ❌ 7.1.2.2.3 Template-based product creation
        - ❌ 7.1.2.2.4 Attribute set copying between products
        - ❌ 7.1.2.2.5 Custom attribute templates

  - ❌ **7.2 Advanced EAV Features**
    - ❌ **7.2.1 Attribute Search & Filtering**
      - ❌ **7.2.1.1 Searchable Attributes**
        - ❌ 7.2.1.1.1 Full-text search w attribute values
        - ❌ 7.2.1.1.2 Attribute-based product filtering
        - ❌ 7.2.1.1.3 Range searches dla numeric attributes
        - ❌ 7.2.1.1.4 Boolean attribute filtering
        - ❌ 7.2.1.1.5 Multi-select attribute filtering
      - ❌ **7.2.1.2 Performance Optimization**
        - ❌ 7.2.1.2.1 EAV query optimization strategies
        - ❌ 7.2.1.2.2 Attribute value indexing
        - ❌ 7.2.1.2.3 Caching strategies dla attributes
        - ❌ 7.2.1.2.4 Denormalization dla często używanych attributes
        - ❌ 7.2.1.2.5 Bulk attribute operations optimization

- ❌ **8. ADVANCED SEARCH & FILTERING**
  - ❌ **8.1 Product Search Engine**
    - ❌ **8.1.1 Search Interface**
      - ❌ **8.1.1.1 Search Component**
        - ❌ 8.1.1.1.1 Livewire ProductSearch component
        - ❌ 8.1.1.1.2 Global search box z real-time suggestions
        - ❌ 8.1.1.1.3 Advanced search modal z multiple criteria
        - ❌ 8.1.1.1.4 Search history i saved searches
        - ❌ 8.1.1.1.5 Search autocomplete z product suggestions
      - ❌ **8.1.1.2 Search Capabilities**
        - ❌ 8.1.1.2.1 Full-text search w names, descriptions, SKUs
        - ❌ 8.1.1.2.2 Fuzzy matching dla typos
        - ❌ 8.1.1.2.3 Partial matches i wildcards
        - ❌ 8.1.1.2.4 Search result ranking i relevance
        - ❌ 8.1.1.2.5 Search term highlighting w results

    - ❌ **8.1.2 Advanced Filtering System**
      - ❌ **8.1.2.1 Filter Categories**
        - ❌ 8.1.2.1.1 Category filter z hierarchical selection
        - ❌ 8.1.2.1.2 Price range filters z sliders
        - ❌ 8.1.2.1.3 Stock status filters
        - ❌ 8.1.2.1.4 Date range filters (created, updated)
        - ❌ 8.1.2.1.5 Integration status filters
      - ❌ **8.1.2.2 Dynamic Filters**
        - ❌ 8.1.2.2.1 Attribute-based filters (auto-generated)
        - ❌ 8.1.2.2.2 Filter combinations z AND/OR logic
        - ❌ 8.1.2.2.3 Filter presets dla common searches
        - ❌ 8.1.2.2.4 Filter result counts
        - ❌ 8.1.2.2.5 Filter state persistence

- ❌ **9. BULK OPERATIONS - MASOWE OPERACJE**
  - ❌ **9.1 Bulk Action System**
    - ❌ **9.1.1 Bulk Selection Interface**
      - ❌ **9.1.1.1 Selection Controls**
        - ❌ 9.1.1.1.1 Select all/none functionality
        - ❌ 9.1.1.1.2 Select by filter criteria
        - ❌ 9.1.1.1.3 Selection counter i memory
        - ❌ 9.1.1.1.4 Cross-page selection support
        - ❌ 9.1.1.1.5 Selection preview i confirmation
      - ❌ **9.1.1.2 Bulk Action Menu**
        - ❌ 9.1.1.2.1 Action availability based na user permissions
        - ❌ 9.1.1.2.2 Action confirmation dialogs
        - ❌ 9.1.1.2.3 Progress indicators dla long operations
        - ❌ 9.1.1.2.4 Rollback capabilities dla reversible actions
        - ❌ 9.1.1.2.5 Action history i audit trail

    - ❌ **9.1.2 Bulk Operations**
      - ❌ **9.1.2.1 Data Operations**
        - ❌ 9.1.2.1.1 Bulk status changes (activate/deactivate)
        - ❌ 9.1.2.1.2 Bulk category assignment/removal
        - ❌ 9.1.2.1.3 Bulk price updates (percentage/fixed amounts)
        - ❌ 9.1.2.1.4 Bulk attribute updates
        - ❌ 9.1.2.1.5 Bulk tag assignment
      - ❌ **9.1.2.2 Integration Operations**
        - ❌ 9.1.2.2.1 Bulk sync z PrestaShop stores
        - ❌ 9.1.2.2.2 Bulk sync z ERP systems
        - ❌ 9.1.2.2.3 Bulk export operations
        - ❌ 9.1.2.2.4 Bulk media operations
        - ❌ 9.1.2.2.5 Bulk deletion z safety checks

- ❌ **10. PRODUCT TEMPLATES - SZABLONY PRODUKTÓW**
  - ❌ **10.1 Template System**
    - ❌ **10.1.1 Template Creation & Management**
      - ❌ **10.1.1.1 Template Builder**
        - ❌ 10.1.1.1.1 Livewire TemplateBuilder component
        - ❌ 10.1.1.1.2 Template creation from existing products
        - ❌ 10.1.1.1.3 Template field configuration
        - ❌ 10.1.1.1.4 Default value settings
        - ❌ 10.1.1.1.5 Required field definitions
      - ❌ **10.1.1.2 Template Categories**
        - ❌ 10.1.1.2.1 Vehicle templates (cars, motorcycles, etc.)
        - ❌ 10.1.1.2.2 Spare part templates (engine, suspension, etc.)
        - ❌ 10.1.1.2.3 Clothing templates (sizes, colors, materials)
        - ❌ 10.1.1.2.4 Custom templates for specific use cases
        - ❌ 10.1.1.2.5 Template sharing i organization

    - ❌ **10.1.2 Template Usage**
      - ❌ **10.1.2.1 Product Creation from Template**
        - ❌ 10.1.2.1.1 Template selection during product creation
        - ❌ 10.1.2.1.2 Pre-filled forms z template defaults
        - ❌ 10.1.2.1.3 Template field overrides
        - ❌ 10.1.2.1.4 Template version control
        - ❌ 10.1.2.1.5 Template usage statistics
      - ❌ **10.1.2.2 Bulk Template Application**
        - ❌ 10.1.2.2.1 Apply template to existing products
        - ❌ 10.1.2.2.2 Template field mapping
        - ❌ 10.1.2.2.3 Conflict resolution during application
        - ❌ 10.1.2.2.4 Preview changes before application
        - ❌ 10.1.2.2.5 Rollback template applications

- ❌ **11. VALIDATION & BUSINESS RULES**
  - ❌ **11.1 Data Validation System**
    - ❌ **11.1.1 Field-Level Validation**
      - ❌ **11.1.1.1 Basic Validation Rules**
        - ❌ 11.1.1.1.1 SKU uniqueness validation
        - ❌ 11.1.1.1.2 Required field validation
        - ❌ 11.1.1.1.3 Data type validation (numeric, date, etc.)
        - ❌ 11.1.1.1.4 Format validation (EAN, dimensions)
        - ❌ 11.1.1.1.5 Range validation (min/max values)
      - ❌ **11.1.1.2 Advanced Validation**
        - ❌ 11.1.1.2.1 Cross-field validation (price consistency)
        - ❌ 11.1.1.2.2 Category-specific validation rules
        - ❌ 11.1.1.2.3 Integration-specific validation
        - ❌ 11.1.1.2.4 Custom validation rules per product type
        - ❌ 11.1.1.2.5 Validation rule inheritance

    - ❌ **11.1.2 Business Logic Rules**
      - ❌ **11.1.2.1 Pricing Rules**
        - ❌ 11.1.2.1.1 Minimum margin enforcement
        - ❌ 11.1.2.1.2 Price group consistency rules
        - ❌ 11.1.2.1.3 Cost price validation
        - ❌ 11.1.2.1.4 Price change approval workflows
        - ❌ 11.1.2.1.5 Competitive pricing alerts
      - ❌ **11.1.2.2 Stock Rules**
        - ❌ 11.1.2.2.1 Negative stock prevention
        - ❌ 11.1.2.2.2 Reserved quantity validation
        - ❌ 11.1.2.2.3 Warehouse capacity limits
        - ❌ 11.1.2.2.4 Minimum stock alerts
        - ❌ 11.1.2.2.5 Stock transfer validation

- ❌ **12. TESTING & DEPLOYMENT**
  - ❌ **12.1 Product Module Testing**
    - ❌ **12.1.1 Functional Testing**
      - ❌ **12.1.1.1 CRUD Operations Testing**
        - ❌ 12.1.1.1.1 Product creation z all field types
        - ❌ 12.1.1.1.2 Product editing z validation
        - ❌ 12.1.1.1.3 Product deletion z safety checks
        - ❌ 12.1.1.1.4 Product search i filtering
        - ❌ 12.1.1.1.5 Bulk operations testing
      - ❌ **12.1.1.2 Integration Testing**
        - ❌ 12.1.1.2.1 Category assignment testing
        - ❌ 12.1.1.2.2 Price management testing
        - ❌ 12.1.1.2.3 Stock operations testing
        - ❌ 12.1.1.2.4 Media upload i management testing
        - ❌ 12.1.1.2.5 Attribute system testing

    - ❌ **12.1.2 Performance Testing**
      - ❌ **12.1.2.1 Load Testing**
        - ❌ 12.1.2.1.1 Large product datasets (10K+ products)
        - ❌ 12.1.2.1.2 Concurrent user operations
        - ❌ 12.1.2.1.3 Search performance z complex filters
        - ❌ 12.1.2.1.4 Bulk operation performance
        - ❌ 12.1.2.1.5 Media upload performance

  - ❌ **12.2 Production Deployment**
    - ❌ **12.2.1 Module Deployment**
      - ❌ **12.2.1.1 Deployment Verification**
        - ❌ 12.2.1.1.1 All product features functional
        - ❌ 12.2.1.1.2 Search i filtering working
        - ❌ 12.2.1.1.3 Media upload operational
        - ❌ 12.2.1.1.4 Bulk operations working
        - ❌ 12.2.1.1.5 Performance within SLA
      - ❌ **12.2.1.2 Data Migration**
        - ❌ 12.2.1.2.1 Sample product data setup
        - ❌ 12.2.1.2.2 Category structure creation
        - ❌ 12.2.1.2.3 Price group configuration
        - ❌ 12.2.1.2.4 Warehouse setup
        - ❌ 12.2.1.2.5 Template configuration

---

## ✅ CRITERIA AKCEPTACJI ETAPU

Etap uznajemy za ukończony gdy:

1. **Product CRUD System:**
   - ✅ Kompletny interfejs tworzenia/edycji produktów
   - ✅ Advanced search i filtering functionality
   - ✅ Bulk operations dla selected products
   - ✅ Product templates system operational

2. **Category Management:**
   - ✅ 5-poziomowa hierarchia kategorii z drag & drop
   - ✅ Product-category assignment working
   - ✅ Category-based filtering i organization
   - ✅ SEO i metadata dla categories

3. **Variants & Pricing:**
   - ✅ Product variants z inheritance system
   - ✅ 7 grup cenowych z margin management
   - ✅ Price history i analytics
   - ✅ Bulk pricing operations

4. **Stock & Media:**
   - ✅ Wielomagazynowy system stanów
   - ✅ Stock reservations i location tracking
   - ✅ Media gallery do 20 images per product
   - ✅ Image optimization i sync preparation

5. **Advanced Features:**
   - ✅ EAV attribute system operational
   - ✅ Business rules i validation working
   - ✅ Performance optimized dla large datasets
   - ✅ Full test coverage > 85%

---

## 🚨 POTENCJALNE PROBLEMY I ROZWIĄZANIA

### Problem 1: Performance z dużą ilością produktów i atrybutów
**Rozwiązanie:** Elasticsearch integration, database indexing, query optimization, lazy loading

### Problem 2: Complex EAV queries dla filtering
**Rozwiązanie:** Denormalization strategies, cached attribute values, optimized joins

### Problem 3: Media storage i optimization na shared hosting
**Rozwiązanie:** Efficient image compression, CDN preparation, progressive loading

### Problem 4: Bulk operations timeout na large datasets
**Rozwiązanie:** Queue-based processing, batch operations, progress tracking

---

## 📊 METRYKI SUKCESU ETAPU

- ⏱️ **Czas wykonania:** Max 60 godzin
- 📈 **Performance:** Product list loading < 2s, search < 1s
- 📦 **Functionality:** All product management features operational
- 🔍 **Search:** Advanced filtering z < 500ms response time
- 📊 **Scale:** Support dla 50K+ products z good performance

---

## 🔄 PRZYGOTOWANIE DO ETAP_06

Po ukończeniu ETAP_05 będziemy mieli:
- **Kompletny system produktów** z all features PIM
- **Zaawansowane category management** z hierarchią
- **Pricing system** z 7 grupami cenowymi
- **Stock management** z multiple warehouses
- **Media system** gotowy dla integrations
- **Search i filtering** dla power users

**Następny etap:** [ETAP_06_Import_Export.md](ETAP_06_Import_Export.md) - system importu/eksportu XLSX z dynamicznym mapowaniem kolumn.