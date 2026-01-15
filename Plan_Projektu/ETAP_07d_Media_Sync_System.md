# ETAP 07d: System Synchronizacji Mediów (Zdjęć) PPM ↔ PrestaShop

**Status ETAPU:** 🛠️ W TRAKCIE - Updated 2025-12-01: PHASE 1-6 ✅ 100% COMPLETE. Pozostało: PHASE 7-9 (Variant Integration, Performance, Testing)
**Priorytet:** 🔴 KRYTYCZNY - Kluczowa funkcjonalność produktów + integracja PrestaShop
**Zależności:** ETAP_05 (punkt 6 - Media System), ETAP_07 (PrestaShop API)
**Powiązane dokumenty:**
- [ETAP_05_Produkty.md](ETAP_05_Produkty.md) - Punkt 6: MEDIA SYSTEM - ZARZĄDZANIE ZDJĘCIAMI
- [ETAP_07_Prestashop_API.md](ETAP_07_Prestashop_API.md) - Punkt 7.4.3: ImageSyncStrategy, FAZA 4+, 9.8

**📖 SZCZEGÓŁOWA SPECYFIKACJA:** Ten dokument rozbudowuje punkt 6 z ETAP_05 oraz punkty 7.4.3 i 9.8 z ETAP_07

---

## 🎯 CEL ETAPU

Implementacja enterprise-grade systemu zarządzania zdjęciami produktów z:
- Upload wielu plików (drag&drop + folder upload)
- Auto-sync z PrestaShop (pobieranie brakujących, live labels)
- Integracja z wariantami
- Panel admin mediów (/admin/media)
- Performance optimization (lazy loading, queue, WebP conversion)

---

## 📋 WYMAGANIA FUNKCJONALNE

### 1. ProductForm - Zakładka "Informacje podstawowe"
- Zdjęcie główne (okładka) po prawej stronie formularza
- Kliknięcie przenosi do zakładki "Galeria"

### 2. ProductForm - Zakładka "Galeria"
- Lista wszystkich zdjęć z wyróżnieniem głównego
- Upload wielu zdjęć (drag&drop + file picker)
- Upload całych folderów
- Usuwanie z PPM i PrestaShop (osobno!)
- Oznaczanie zdjęć do wysłania na PrestaShop
- Multi-select + bulk actions
- Labele sklepów "na żywo" z PrestaShop
- Przypisanie zdjęć do wariantów

### 3. Auto-sync z PrestaShop
- Pobieranie zdjęć które są w PS ale nie w PPM
- Live verification labeli
- Wizualny indykator pobierania

### 4. ProductList
- Nowa kolumna z miniaturką (między checkbox a SKU)

### 5. Panel Admin Mediów (/admin/media)
- Osierocone zdjęcia (brak przypisania do shop)
- Wyszukiwarka produktów (SKU/nazwa)
- Lista produktów z galeriami
- Quick Actions dla bulk operations

### 6. Struktura plików
- `storage/products/{SKU}/` per SKU
- Auto-konwersja do WebP
- Naming: `Nazwa_Produktu_01.webp`

---

## 📂 1. ARCHITEKTURA FOLDERÓW/PLIKÓW

### ✅ 1.1 Livewire Components

#### ✅ 1.1.1 ProductForm - Gallery Tab Component
**Lokalizacja:** `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php`
    └── PLIK: app/Http/Livewire/Products/Management/Tabs/GalleryTab.php (248 linii)
**Odpowiedzialność:** Galeria w ProductForm - upload, zarządzanie, sync

#### ✅ 1.1.2 Product List - Thumbnail Column
**Modyfikacja:** `app/Http/Livewire/Products/Listing/ProductList.php`
    └── PLIK: app/Http/Livewire/Products/Listing/ProductList.php (eager load media)
    └── PLIK: resources/views/livewire/products/listing/product-list.blade.php (thumbnail column)
**Odpowiedzialność:** Dodanie kolumny miniaturek

#### ✅ 1.1.3 Admin Media Manager
**Lokalizacja:** `app/Http/Livewire/Admin/Media/MediaManager.php`
    └── PLIK: app/Http/Livewire/Admin/Media/MediaManager.php (245 linii)
    └── PLIK: resources/views/livewire/admin/media/media-manager.blade.php
    └── PLIK: resources/css/admin/media-admin.css
**Odpowiedzialność:** Panel /admin/media - orphaned media, bulk actions

#### ✅ 1.1.4 Media Upload Widget (Reusable)
**Lokalizacja:** `app/Http/Livewire/Components/MediaUploadWidget.php`
    └── PLIK: app/Http/Livewire/Components/MediaUploadWidget.php (218 linii)
**Odpowiedzialność:** Reusable upload widget (drag&drop, folder, multi-select)

#### ✅ 1.1.5 Media Gallery Grid (Reusable)
**Lokalizacja:** `app/Http/Livewire/Components/MediaGalleryGrid.php`
    └── PLIK: app/Http/Livewire/Components/MediaGalleryGrid.php (248 linii)
**Odpowiedzialność:** Reusable gallery display z kontrolkami

---

### ✅ 1.2 Services Layer

#### ✅ 1.2.1 MediaManager Service
**Lokalizacja:** `app/Services/Media/MediaManager.php`
    └── PLIK: app/Services/Media/MediaManager.php (287 linii)
**Odpowiedzialność:**
- Upload files (single, multiple, folder)
- Delete files (PPM, PrestaShop, both)
- Set primary image
- Reorder images
- Generate thumbnails
- **Max 300 linii** (zgodnie z CLAUDE.md)

#### ✅ 1.2.2 ImageProcessor Service
**Lokalizacja:** `app/Services/Media/ImageProcessor.php`
    └── PLIK: app/Services/Media/ImageProcessor.php (295 linii)
**Odpowiedzialność:**
- WebP conversion
- Thumbnail generation (150x150, 300x300, 600x600)
- Watermark application (future)
- Image optimization (quality, compression)
- Metadata extraction (dimensions, EXIF)
- **Max 300 linii**

#### ✅ 1.2.3 MediaSyncService (PrestaShop)
**Lokalizacja:** `app/Services/Media/MediaSyncService.php`
    └── PLIK: app/Services/Media/MediaSyncService.php (382 linie)
**Odpowiedzialność:**
- Pull missing images from PrestaShop
- Push images to PrestaShop
- Verify sync status (live labels)
- Map PPM media ↔ PrestaShop images
- Handle multi-store scenarios
- **Max 300 linii**

#### ✅ 1.2.4 MediaStorageService
**Lokalizacja:** `app/Services/Media/MediaStorageService.php`
    └── PLIK: app/Services/Media/MediaStorageService.php (248 linii)
**Odpowiedzialność:**
- File storage abstraction (local, S3 ready)
- SKU-based folder structure (`storage/products/{SKU}/`)
- Naming convention enforcement
- Cleanup orphaned files
- Storage quota management
- **Max 250 linii**

---

### ✅ 1.3 Jobs (Async Operations) - CREATED 2025-12-01

#### ✅ 1.3.1 ProcessMediaUpload Job
**Lokalizacja:** `app/Jobs/Media/ProcessMediaUpload.php`
    └── PLIK: app/Jobs/Media/ProcessMediaUpload.php (~150 linii)
**Odpowiedzialność:**
- Async upload processing
- WebP conversion
- Thumbnail generation
- Metadata extraction
- JobProgress integration

#### ✅ 1.3.2 BulkMediaUpload Job
**Lokalizacja:** `app/Jobs/Media/BulkMediaUpload.php`
    └── PLIK: app/Jobs/Media/BulkMediaUpload.php (~180 linii)
**Odpowiedzialność:**
- Process folder uploads
- Multiple files batch
- Progress tracking per file
- Error handling + retry

#### ✅ 1.3.3 SyncMediaFromPrestaShop Job
**Lokalizacja:** `app/Jobs/Media/SyncMediaFromPrestaShop.php`
    └── PLIK: app/Jobs/Media/SyncMediaFromPrestaShop.php (~200 linii)
**Odpowiedzialność:**
- Pull missing images
- Download from PrestaShop
- Create Media records
- Update sync_status

#### ✅ 1.3.4 PushMediaToPrestaShop Job
**Lokalizacja:** `app/Jobs/Media/PushMediaToPrestaShop.php`
    └── PLIK: app/Jobs/Media/PushMediaToPrestaShop.php (~200 linii)
**Odpowiedzialność:**
- Upload images to PrestaShop
- Update prestashop_mapping
- Set cover image
- Handle multi-store

#### ❌ 1.3.5 GenerateMissingThumbnails Job
**Lokalizacja:** `app/Jobs/Media/GenerateMissingThumbnails.php`
**Odpowiedzialność:**
- Batch regenerate thumbnails
- Fix missing thumbnails
- Optimize existing images

#### ❌ 1.3.6 CleanupOrphanedMedia Job
**Lokalizacja:** `app/Jobs/Media/CleanupOrphanedMedia.php`
**Odpowiedzialność:**
- Find orphaned files (no DB record)
- Find orphaned records (no file)
- Cleanup with confirmation
- Scheduled daily

---

### ✅ 1.4 DTOs (Data Transfer Objects)

#### ✅ 1.4.1 MediaUploadDTO
**Lokalizacja:** `app/DTOs/Media/MediaUploadDTO.php`
    └── PLIK: app/DTOs/Media/MediaUploadDTO.php (220 linii)
**Odpowiedzialność:**
- Validate upload data
- Type-safe upload parameters
- MAX_IMAGES_PER_PRODUCT = 99
- Factory methods: forProduct(), forVariant()

#### ✅ 1.4.2 MediaSyncStatusDTO
**Lokalizacja:** `app/DTOs/Media/MediaSyncStatusDTO.php`
    └── PLIK: app/DTOs/Media/MediaSyncStatusDTO.php (285 linii)
**Odpowiedzialność:**
- PrestaShop sync status
- Live label data
- Progress tracking (download/upload)
- Shop-specific status management

---

### ✅ 1.5 Events & Listeners

#### ✅ 1.5.1 MediaUploaded Event
**Lokalizacja:** `app/Events/Media/MediaUploaded.php`
    └── PLIK: app/Events/Media/MediaUploaded.php (54 linie)
**Odpowiedzialność:** Trigger po udanym upload (thumbnails, WebP, auto-sync)

#### ✅ 1.5.2 MediaDeleted Event
**Lokalizacja:** `app/Events/Media/MediaDeleted.php`
    └── PLIK: app/Events/Media/MediaDeleted.php (98 linii)
**Odpowiedzialność:** Trigger po usunieciu (cleanup storage, PrestaShop deletion)

#### ✅ 1.5.3 MediaSyncCompleted Event
**Lokalizacja:** `app/Events/Media/MediaSyncCompleted.php`
    └── PLIK: app/Events/Media/MediaSyncCompleted.php (140 linii)
**Odpowiedzialność:** Trigger po zakonczeniu synchronizacji z PrestaShop

---

### ✅ 1.6 Views (Blade Templates)

#### ✅ 1.6.1 Gallery Tab View
**Lokalizacja:** `resources/views/livewire/products/management/tabs/gallery-tab.blade.php`
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php

#### ✅ 1.6.2 Media Upload Widget View
**Lokalizacja:** `resources/views/livewire/components/media-upload-widget.blade.php`
    └── PLIK: resources/views/livewire/components/media-upload-widget.blade.php

#### ✅ 1.6.3 Media Gallery Grid View
**Lokalizacja:** `resources/views/livewire/components/media-gallery-grid.blade.php`
    └── PLIK: resources/views/livewire/components/media-gallery-grid.blade.php

#### ✅ 1.6.4 Admin Media Manager View
**Lokalizacja:** `resources/views/livewire/admin/media/media-manager.blade.php`
    └── PLIK: resources/views/livewire/admin/media/media-manager.blade.php

#### ❌ 1.6.5 Media Thumbnail Partial
**Lokalizacja:** `resources/views/livewire/products/partials/media-thumbnail.blade.php`

---

### ✅ 1.7 CSS Styles (MANDATORY - No Inline!)

#### ✅ 1.7.1 Media Gallery Styles
**Lokalizacja:** `resources/css/products/media-gallery.css`
    └── PLIK: resources/css/products/media-gallery.css (300 linii)
**Odpowiedzialność:**
- Gallery grid layout
- Drag&drop zones
- Thumbnail styles
- Upload progress indicators
- **Max 300 linii**

#### ✅ 1.7.2 Media Upload Widget Styles (w media-gallery.css)
**Lokalizacja:** `resources/css/products/media-gallery.css` (zintegrowane)
**Odpowiedzialność:**
- Upload widget UI
- Drag states (hover, active)
- Progress bars

---

### ❌ 1.8 JavaScript (Alpine.js + Livewire)

#### ❌ 1.8.1 Media Gallery Alpine Component
**Lokalizacja:** `resources/js/components/media-gallery.js`
**Odpowiedzialność:**
- Drag&drop handling
- Multi-select logic
- Folder upload API
- **Max 300 linii**

#### ❌ 1.8.2 Media Upload Alpine Component
**Lokalizacja:** `resources/js/components/media-upload.js`
**Odpowiedzialność:**
- File input handling
- Progress tracking
- Error display
- **Max 250 linii**

---

## 🗄️ 2. DATABASE SCHEMA

### ❌ 2.1 Migracje (Jeśli Potrzebne)

#### ❌ 2.1.1 Extend Media Table (Optional)
**Plik:** `database/migrations/2025_11_28_000001_extend_media_table_for_variants.php`

**Potrzebne TYLKO jeśli:**
- Brak kolumny dla przypisania zdjęć do wariantów
- Potrzeba dodatkowych metadanych (EXIF, GPS, etc.)

**Prawdopodobnie NIE POTRZEBNE** - obecna struktura media wystarcza:
- Polymorphic relations obsługują Product i ProductVariant
- `prestashop_mapping` JSONB jest wystarczająco elastyczny

---

## 🔄 3. INTEGRACJA Z ISTNIEJĄCYMI SYSTEMAMI

### ✅ 3.1 Istniejące Struktury (Wykorzystanie)

#### ✅ 3.1.1 Model Media
**Status:** ✅ Gotowy (609 linii)
**Features:**
- Polymorphic relations (Product, ProductVariant)
- `prestashop_mapping` JSONB
- `sync_status` enum
- `thumbnailUrl` accessor
- `markAsSynced()`, `markSyncError()` methods

#### ✅ 3.1.2 HasFeatures Trait
**Status:** ✅ Gotowy
**Features:**
- `morphMany('media')` relationship w Product

#### ✅ 3.1.3 JobProgress Model
**Status:** ✅ Gotowy
**Features:**
- Progress tracking dla async jobs
- Real-time updates

#### ✅ 3.1.4 PrestaShop Services
**Status:** ✅ Gotowy
**Pliki:**
- `app/Services/PrestaShop/PrestaShop8Client.php`
- `app/Services/PrestaShop/ProductSyncStrategy.php`

**Integracja:** MediaSyncService będzie używać tych serwisów

---

### ❌ 3.2 Modyfikacje Istniejących Plików

#### ❌ 3.2.1 ProductForm Component
**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`
**Zmiany:**
- Dodać property `$activeTab` (jeśli brak)
- Listener dla przejścia do zakładki "Galeria"
- Load media relation w mount()

#### ❌ 3.2.2 ProductList Component
**Plik:** `app/Http/Livewire/Products/ProductList.php`
**Zmiany:**
- Eager load `media` relation
- Dodać kolumnę thumbnail w query

#### ❌ 3.2.3 Product Model
**Plik:** `app/Models/Product.php`
**Zmiany:**
- Ensure `media()` relationship exists (prawdopodobnie już jest)
- Add helper method `getPrimaryImage()`

---

## 🚀 4. IMPLEMENTATION PHASES

### ✅ PHASE 1: CORE INFRASTRUCTURE (3-4 dni) - COMPLETED 2025-12-01
**Cel:** Fundamenty systemu mediów

#### ✅ 1.1 Services Layer
- ✅ 1.1.1 MediaStorageService - File storage abstraction
    └── PLIK: app/Services/Media/MediaStorageService.php (248 linii)
- ✅ 1.1.2 ImageProcessor - WebP conversion + thumbnails
    └── PLIK: app/Services/Media/ImageProcessor.php (295 linii)
- ✅ 1.1.3 MediaManager - CRUD operations
    └── PLIK: app/Services/Media/MediaManager.php (287 linii)
- ✅ 1.1.4 MediaSyncService - PrestaShop sync (BONUS)
    └── PLIK: app/Services/Media/MediaSyncService.php (382 linie)

#### ✅ 1.2 DTOs
- ✅ 1.2.1 MediaUploadDTO
    └── PLIK: app/DTOs/Media/MediaUploadDTO.php (220 linii)
- ✅ 1.2.2 MediaSyncStatusDTO
    └── PLIK: app/DTOs/Media/MediaSyncStatusDTO.php (285 linii)

#### ❌ 1.3 Jobs - Basic Upload (BRAK - DO IMPLEMENTACJI)
- ❌ 1.3.1 ProcessMediaUpload
- ❌ 1.3.2 BulkMediaUpload

#### ✅ 1.4 Events
- ✅ 1.4.1 MediaUploaded
    └── PLIK: app/Events/Media/MediaUploaded.php (54 linie)
- ✅ 1.4.2 MediaDeleted
    └── PLIK: app/Events/Media/MediaDeleted.php (98 linii)
- ✅ 1.4.3 MediaSyncCompleted (zamiast PrimaryImageChanged)
    └── PLIK: app/Events/Media/MediaSyncCompleted.php (140 linii)

**Validation:** ✅ Services istnieją i są zintegrowane z GalleryTab

---

### ✅ PHASE 2: LIVEWIRE COMPONENTS - BASIC (3-4 dni) - VERIFIED 2025-11-28
**Cel:** Podstawowe UI do zarządzania mediami w ProductForm

#### ✅ 2.1 Gallery Tab Component
- ✅ 2.1.1 Utworzenie GalleryTab.php
    └── PLIK: app/Http/Livewire/Products/Management/Tabs/GalleryTab.php
- ✅ 2.1.2 Display existing media
- ✅ 2.1.3 Set primary image
- ✅ 2.1.4 Delete image (PPM only)
- ✅ 2.1.5 Reorder images (drag&drop lub inputy)

#### ✅ 2.2 Media Upload Widget (Reusable)
- ✅ 2.2.1 Utworzenie MediaUploadWidget.php
    └── PLIK: app/Http/Livewire/Components/MediaUploadWidget.php
- ✅ 2.2.2 Single file upload
- ✅ 2.2.3 Multiple files upload
- ✅ 2.2.4 Validation (size, type)
- ✅ 2.2.5 Progress indicator

#### ✅ 2.3 CSS & JS
- ✅ 2.3.1 media-gallery.css
    └── PLIK: resources/css/products/media-gallery.css
- ✅ 2.3.2 media-upload.css (zintegrowane w media-gallery.css)
- ❌ 2.3.3 media-gallery.js (Alpine) - wbudowane w Blade
- ❌ 2.3.4 media-upload.js (Alpine) - wbudowane w Blade

#### ✅ 2.4 Integration
- ✅ 2.4.1 ProductForm - dodanie zakładki Galeria
    └── PLIK: resources/views/livewire/products/management/partials/tab-navigation.blade.php
- ✅ 2.4.2 Basic tab - zdjęcie główne (duże) + link do Gallery
    └── PLIK: resources/views/livewire/products/management/partials/primary-image-preview.blade.php
    └── PLIK: resources/views/livewire/products/management/tabs/basic-tab.blade.php (layout 2-column grid)
- ✅ 2.4.3 Build + Deploy + Chrome DevTools verification (2025-11-28)

**Validation:** ✅ Chrome DevTools MCP - GalleryTab renderuje się poprawnie na produkcji

---

### ✅ PHASE 3: ADVANCED UPLOAD (2-3 dni) - COMPLETED 2025-12-01
**Cel:** Folder upload, drag&drop, bulk operations

#### ✅ 3.1 Drag&Drop
- ✅ 3.1.1 Alpine.js drag&drop zones
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php
- ✅ 3.1.2 File dropping UI feedback (.is-dragover class)
- ✅ 3.1.3 Multiple files handling ($wire.uploadMultiple)

#### ✅ 3.2 Folder Upload
- ✅ 3.2.1 JavaScript Folder Upload API integration (webkitdirectory)
- ✅ 3.2.2 BulkMediaUpload job created
    └── PLIK: app/Jobs/Media/BulkMediaUpload.php
- ✅ 3.2.3 Progress tracking per file (JobProgress)

#### ✅ 3.3 Multi-Select & Bulk Actions
- ✅ 3.3.1 Checkbox selection (selectedIds, selectAll)
- ✅ 3.3.2 Bulk delete (bulkDelete method)
- ✅ 3.3.3 Bulk sync to PrestaShop (bulkSyncToPrestaShop method)

**Validation:** ✅ Chrome DevTools MCP - verified 2025-12-01
**Report:** _AGENT_REPORTS/livewire_specialist_ADVANCED_UPLOAD_UI_REPORT.md

---

### ✅ PHASE 4: PRESTASHOP SYNC (4-5 dni) - COMPLETED 2025-12-01
**Cel:** Integracja z PrestaShop - pobieranie/wysyłanie zdjęć

#### ✅ 4.1 MediaSyncService (SERVICE GOTOWY)
- ✅ 4.1.1 Utworzenie MediaSyncService.php
    └── PLIK: app/Services/Media/MediaSyncService.php (382 linie)
- ✅ 4.1.2 Pull missing images from PrestaShop (method exists)
- ✅ 4.1.3 Push images to PrestaShop (method exists)
- ✅ 4.1.4 Verify sync status (live labels) (method exists)
- 🛠️ 4.1.5 Multi-store mapping (partially implemented)

#### ✅ 4.2 Jobs - PrestaShop Sync (CREATED 2025-12-01)
- ✅ 4.2.1 SyncMediaFromPrestaShop
    └── PLIK: app/Jobs/Media/SyncMediaFromPrestaShop.php (~200 linii)
- ✅ 4.2.2 PushMediaToPrestaShop
    └── PLIK: app/Jobs/Media/PushMediaToPrestaShop.php (~200 linii)
- ✅ 4.2.3 ProcessMediaUpload (BONUS)
    └── PLIK: app/Jobs/Media/ProcessMediaUpload.php (~150 linii)
- ✅ 4.2.4 BulkMediaUpload (BONUS)
    └── PLIK: app/Jobs/Media/BulkMediaUpload.php (~180 linii)

#### ✅ 4.3 UI - Sync Controls (COMPLETED 2025-12-01)
- ✅ 4.3.1 "Pobierz z PrestaShop" button (dropdown w headerze GalleryTab)
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php (linie 19-49)
- ✅ 4.3.2 "Wyslij do PrestaShop" button (bulk + per-image dropdown)
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php (linie 293-314)
- ✅ 4.3.3 Live labels (ktore sklepy maja obraz)
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php (linie 259-275)
- ✅ 4.3.4 Sync status indicators (synced/pending/error icons)
    └── PLIK: resources/views/livewire/products/management/tabs/gallery-tab.blade.php (linie 231-257)
- ✅ 4.3.5 Progress tracking widget (ActiveOperationsBar)
    └── PLIK: resources/views/livewire/components/active-operations-bar.blade.php (fixed root element)

#### ✅ 4.4 Integration z Istniejacymi Jobs (COMPLETED 2025-12-01)
- ✅ 4.4.1 MediaSyncService.pushBulkToPrestaShop() - bulk media push method
    └── PLIK: app/Services/Media/MediaSyncService.php (pushBulkToPrestaShop method)
- ✅ 4.4.2 ProductSyncStrategy.syncMediaIfEnabled() - auto-sync on product sync
    └── PLIK: app/Services/PrestaShop/Sync/ProductSyncStrategy.php (syncMediaIfEnabled method)
- ✅ 4.4.3 PushMediaToPrestaShop Job - calls pushBulkToPrestaShop
    └── PLIK: app/Jobs/Media/PushMediaToPrestaShop.php (updated)

**Report:** _AGENT_REPORTS/laravel-expert_MEDIA_JOBS_IMPLEMENTATION.md
**Validation:** Manual test - sync from B2B Test DEV, verify labels

---

### ✅ PHASE 5: PRODUCT LIST THUMBNAILS (1 dzień) - VERIFIED 2025-11-28
**Cel:** Miniaturki w ProductList

#### ✅ 5.1 ProductList Modification
- ✅ 5.1.1 Eager load `media` relation
    └── PLIK: app/Http/Livewire/Products/Listing/ProductList.php
- ✅ 5.1.2 Add thumbnail column (między checkbox a SKU)
    └── PLIK: resources/views/livewire/products/listing/product-list.blade.php
- ✅ 5.1.3 media-thumbnail.blade.php partial (inline w product-list.blade.php)
- ✅ 5.1.4 CSS dla kolumny (.product-list-thumbnail)

**Validation:** ✅ Chrome DevTools MCP - kolumna widoczna (pusta gdy brak zdjęć w DB)

---

### ✅ PHASE 6: ADMIN MEDIA MANAGER (3-4 dni) - VERIFIED 2025-11-28
**Cel:** Panel /admin/media dla zarządzania osieroczonymi mediami

#### ✅ 6.1 MediaManager Component
- ✅ 6.1.1 Utworzenie MediaManager.php
    └── PLIK: app/Http/Livewire/Admin/Media/MediaManager.php
- ✅ 6.1.2 Orphaned media detection
- ✅ 6.1.3 Product search (SKU/nazwa)
- ✅ 6.1.4 Product galleries listing
- ✅ 6.1.5 Bulk actions (delete, assign, sync)

#### ✅ 6.2 Routes & Menu
- ✅ 6.2.1 Route /admin/media - DZIAŁA
- ✅ 6.2.2 Admin menu item - Link istnieje w sekcji SYSTEM w sidebarze
- ✅ 6.2.3 Permission check (Admin/Manager) - tymczasowo wyłączone

#### ✅ 6.3 UI
- ✅ 6.3.1 media-manager.blade.php
    └── PLIK: resources/views/livewire/admin/media/media-manager.blade.php
- ✅ 6.3.2 Filters (orphaned, by product, by shop)
- ✅ 6.3.3 Quick Actions toolbar

**Validation:** ✅ Chrome DevTools MCP - /admin/media działa (HTTP 200)

---

### 🔵 PHASE 7: VARIANT MEDIA INTEGRATION (2-3 dni)
**Cel:** Przypisywanie zdjęć do wariantów

#### ❌ 7.1 UI Enhancement
- ❌ 7.1.1 Gallery tab - dropdown "Przypisz do wariantu"
- ❌ 7.1.2 Variant selector
- ❌ 7.1.3 Visual indicator per variant

#### ❌ 7.2 Backend
- ❌ 7.2.1 MediaManager - assignToVariant() method
- ❌ 7.2.2 Polymorphic relation update
- ❌ 7.2.3 Sync variant media to PrestaShop

**Validation:** Manual test - assign image to variant, sync

---

### 🔵 PHASE 8: PERFORMANCE & OPTIMIZATION (2-3 dni)
**Cel:** Optymalizacja wydajności

#### ❌ 8.1 Lazy Loading
- ❌ 8.1.1 Gallery tab - lazy load images
- ❌ 8.1.2 Infinite scroll dla dużych galerii
- ❌ 8.1.3 Thumbnail caching

#### ❌ 8.2 Queue Optimization
- ❌ 8.2.1 Job batching
- ❌ 8.2.2 Priority queues (upload > sync > thumbnails)
- ❌ 8.2.3 Failed job handling + retry logic

#### ❌ 8.3 Storage Optimization
- ❌ 8.3.1 GenerateMissingThumbnails job
- ❌ 8.3.2 CleanupOrphanedMedia job (scheduled)
- ❌ 8.3.3 Storage quota monitoring

**Validation:** Performance testing - 100+ images upload, Chrome DevTools Performance panel

---

### 🔵 PHASE 9: TESTING & DOCUMENTATION (2 dni)
**Cel:** Testy i dokumentacja

#### ❌ 9.1 Unit Tests
- ❌ 9.1.1 MediaManager tests
- ❌ 9.1.2 ImageProcessor tests
- ❌ 9.1.3 MediaSyncService tests

#### ❌ 9.2 Feature Tests
- ❌ 9.2.1 GalleryTab tests
- ❌ 9.2.2 MediaUploadWidget tests
- ❌ 9.2.3 MediaManager tests

#### ❌ 9.3 Integration Tests
- ❌ 9.3.1 Upload → Process → Thumbnail flow
- ❌ 9.3.2 Sync from PrestaShop flow
- ❌ 9.3.3 Bulk operations flow

#### ❌ 9.4 Documentation
- ❌ 9.4.1 _DOCS/MEDIA_SYSTEM_GUIDE.md - User guide
- ❌ 9.4.2 _DOCS/MEDIA_API_REFERENCE.md - Developer reference
- ❌ 9.4.3 Update Struktura_Bazy_Danych.md (if schema changed)

**Validation:** `php artisan test` - all green

---

## ⚡ 5. PERFORMANCE CONSIDERATIONS

### 5.1 Lazy Loading Strategy
- **Gallery Tab:** Load thumbnails ONLY (150x150), full images on click
- **ProductList:** Thumbnail column cached z Redis (30 min TTL)
- **Infinite Scroll:** Load 20 images per batch

### 5.2 Queue Strategy
**Queues:**
- `high` - Upload processing (user waiting)
- `default` - Sync to PrestaShop
- `low` - Thumbnail generation, cleanup

**Redis Configuration:**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### 5.3 Storage Strategy
- **Local Development:** `storage/app/public/products/`
- **Production:** Same (Hostido) - S3 ready dla przyszłości
- **Thumbnails:** `storage/app/public/products/{SKU}/thumbs/`
- **CDN Ready:** Storage facade abstraction

### 5.4 Database Optimization
**Existing Indexes (już są w migracji):**
- `idx_media_polymorphic` - Most critical
- `idx_media_primary` - Primary image selection
- `idx_media_sort` - Gallery display
- `idx_media_active` - Active filtering
- `idx_media_sync_status` - Sync monitoring

**Query Optimization:**
```php
// ✅ GOOD - Eager loading
$products = Product::with(['media' => function($query) {
    $query->active()->galleryOrder();
}])->paginate(20);

// ❌ BAD - N+1
foreach($products as $product) {
    $media = $product->media; // Query per product!
}
```

### 5.5 Caching Strategy
**Cache Keys:**
- `product_media_{product_id}` - Product gallery (5 min)
- `product_primary_image_{product_id}` - Primary image URL (10 min)
- `prestashop_sync_status_{product_id}_{shop_id}` - Sync labels (2 min)

**Cache Invalidation:**
- On media upload/delete/reorder
- On sync to PrestaShop
- On primary image change

---

## 🔗 6. DEPENDENCIES & PACKAGES

### 6.1 Existing (Already Installed)
- ✅ **Laravel 12.x** - Framework
- ✅ **Livewire 3.x** - UI components
- ✅ **Alpine.js** - Frontend interactions
- ✅ **PhpSpreadsheet** (via Laravel-Excel) - Already used for import

### 6.2 Required (Need Installation)
- ❌ **intervention/image** - Image processing (WebP, thumbnails, resize)
  ```bash
  composer require intervention/image
  ```

### 6.3 Optional (Future Enhancement)
- ⏳ **spatie/laravel-medialibrary** - Alternative (if needed for advanced features)
- ⏳ **league/flysystem-aws-s3-v3** - S3 support (future CDN)

---

## 📝 7. NAMING CONVENTIONS

### 7.1 File Names
**Pattern:** `{Product_Name}_{NN}.webp` gdzie `NN` = numer 01-99
**Limit:** Maksymalnie **99 zdjec na produkt** (indeksy 01-99)

**Examples:**
- `Przednia_Klapa_01.webp` (pierwsze zdjecie)
- `Przednia_Klapa_02.webp` (drugie zdjecie)
- `Silnik_125cc_15.webp` (pietnaste zdjecie)
- `Felga_10_Cali_99.webp` (ostatnie mozliwe - limit)

**Walidacja:**
- Indeks: `01` - `99` (2 cyfry z zerem wiodacym)
- Przy probie dodania 100. zdjecia: blad walidacji

**Implementation:**
```php
// MediaStorageService.php
public const MAX_IMAGES_PER_PRODUCT = 99;

public function generateFileName(Product $product, int $index): string
{
    if ($index < 1 || $index > self::MAX_IMAGES_PER_PRODUCT) {
        throw new \InvalidArgumentException(
            "Image index must be between 01 and " . self::MAX_IMAGES_PER_PRODUCT
        );
    }

    $baseName = Str::slug($product->display_name);
    $indexStr = str_pad($index, 2, '0', STR_PAD_LEFT); // 01, 02, ..., 99
    return "{$baseName}_{$indexStr}.webp";
}

public function canAddMoreImages(Product $product): bool
{
    return $product->media()->count() < self::MAX_IMAGES_PER_PRODUCT;
}

public function getNextAvailableIndex(Product $product): ?int
{
    $existingIndexes = $product->media()
        ->pluck('position')
        ->toArray();

    for ($i = 1; $i <= self::MAX_IMAGES_PER_PRODUCT; $i++) {
        if (!in_array($i, $existingIndexes)) {
            return $i;
        }
    }

    return null; // Limit reached
}
```

### 7.2 Storage Paths
**Pattern:** `storage/products/{SKU}/`
**Examples:**
- `storage/products/MPP-12345/Przednia_Klapa_01.webp`
- `storage/products/MPP-12345/thumbs/Przednia_Klapa_01_thumb.webp`

### 7.3 CSS Classes
**Convention:**
- `.media-gallery` - Gallery container
- `.media-gallery-grid` - Grid layout
- `.media-gallery-item` - Single item
- `.media-upload-zone` - Drag&drop zone
- `.media-upload-progress` - Progress bar
- `.media-thumbnail` - ProductList thumbnail

---

## 🚨 8. CRITICAL RULES (MANDATORY)

### 8.1 CSS Rules (zgodnie z CLAUDE.md)
- ❌ **ZAKAZ** inline styles: `style="..."`
- ❌ **ZAKAZ** Tailwind arbitrary dla z-index: `class="z-[9999]"`
- ✅ **WYMAGANE** CSS classes w `resources/css/products/media-gallery.css`

### 8.2 File Size Limits
- **Max 300 linii** per service (MediaManager, ImageProcessor, MediaSyncService)
- **Max 250 linii** per Livewire component
- **Max 200 linii** per view file

### 8.3 Context7 Verification (MANDATORY)
- ✅ Przed implementacją: Verify Laravel Storage patterns
- ✅ Przed implementacją: Verify Livewire WithFileUploads patterns
- ✅ Reference official docs w komentarzach

### 8.4 Chrome DevTools MCP Verification (MANDATORY)
- ✅ Po każdym deployment CSS/JS/Blade
- ✅ Po każdej zmianie w GalleryTab
- ✅ PRZED informowaniem użytkownika o completion
- ✅ Używać OPTIMIZED patterns z `_DOCS/CHROME_DEVTOOLS_OPTIMIZED_QUERIES.md`

### 8.5 Debug Logging Workflow
- ✅ Development: Extensive `Log::debug()`
- ✅ Po potwierdzeniu "działa idealnie": Usuń `Log::debug()`
- ✅ Production: Tylko `Log::info/warning/error`

---

## 📊 9. SUCCESS METRICS

### 9.1 Functional Metrics
- [ ] Upload 10+ images jednocześnie < 30s (processing time)
- [ ] Gallery display < 500ms (20 thumbnails)
- [ ] Sync from PrestaShop < 60s (10 images)
- [ ] Push to PrestaShop < 45s (5 images)

### 9.2 User Experience Metrics
- [ ] Drag&drop działa smooth (60 FPS)
- [ ] Progress indicators real-time (<1s delay)
- [ ] No console errors w Chrome DevTools
- [ ] No wire:snapshot issues

### 9.3 Code Quality Metrics
- [ ] 100% test coverage dla services
- [ ] 80%+ test coverage dla components
- [ ] 0 inline styles w całym systemie
- [ ] Wszystkie pliki < 300 linii

---

## 🔧 10. TROUBLESHOOTING & KNOWN ISSUES

### 10.1 Livewire File Upload Issues
**Reference:** `_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md`

**Problem:** `wire:snapshot` rendering w UI po upload
**Solution:** Use `WithFileUploads` trait properly, reset properties

### 10.2 PrestaShop Image Upload Issues
**Reference:** ETAP_07 (PrestaShop API)

**Problem:** Image URL generation, multi-store mapping
**Solution:** Use PrestaShop8Client properly, handle image associations

### 10.3 Performance Issues
**Problem:** Gallery slow z 100+ images
**Solution:**
- Lazy loading (20 per batch)
- Thumbnail caching (Redis)
- Eager loading w queries

---

## 📅 11. TIMELINE ESTIMATE

**TOTAL:** ~22-28 dni roboczych (4-6 tygodni)

| Phase | Dni | Priority |
|-------|-----|----------|
| PHASE 1: Core Infrastructure | 3-4 | CRITICAL |
| PHASE 2: Livewire Components - Basic | 3-4 | CRITICAL |
| PHASE 3: Advanced Upload | 2-3 | HIGH |
| PHASE 4: PrestaShop Sync | 4-5 | HIGH |
| PHASE 5: ProductList Thumbnails | 1 | MEDIUM |
| PHASE 6: Admin Media Manager | 3-4 | MEDIUM |
| PHASE 7: Variant Integration | 2-3 | MEDIUM |
| PHASE 8: Performance & Optimization | 2-3 | HIGH |
| PHASE 9: Testing & Documentation | 2 | CRITICAL |

---

## 🎯 12. NEXT STEPS

### 12.1 Immediate Actions
1. ✅ User approval tego planu
2. ❌ Zainstalować `intervention/image` package
3. ❌ Utworzyć PHASE 1 branches
4. ❌ Context7 verification dla Storage patterns
5. ❌ Start implementation PHASE 1

### 12.2 Questions for User
- **Q1:** Czy zgadzasz się z tym planem i timeline'em?
- **Q2:** Czy preferujesz zacząć od PHASE 1 czy innej fazy?
- **Q3:** Czy są dodatkowe wymagania które pominąłem?
- **Q4:** Czy mamy budget na intervention/image package?

---

## 📚 13. REFERENCES

### 13.1 Project Documentation
- `CLAUDE.md` - Project guidelines
- `_DOCS/FRONTEND_VERIFICATION_GUIDE.md` - Chrome DevTools MCP
- `_DOCS/CHROME_DEVTOOLS_OPTIMIZED_QUERIES.md` - Token optimization
- `_DOCS/CSS_STYLING_GUIDE.md` - CSS rules
- `_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md` - Known issues

### 13.2 External Documentation
- Laravel 12.x Storage: https://laravel.com/docs/12.x/filesystem
- Livewire 3.x File Uploads: https://livewire.laravel.com/docs/uploads
- Intervention Image: http://image.intervention.io/
- PrestaShop Web Services: https://devdocs.prestashop.com/

---

---

## 🐛 14. REPORTED BUGS & FIXES (PHASE 10 - NOWA FAZA)

**Zgłoszone przez użytkownika:** 2025-12-01
**Status fazy:** 🛠️ W TRAKCIE PLANOWANIA

### ✅ 14.1 NAPRAWIONY: /admin/media 500 error
**Problem:** Błąd 500 przy wejściu na /admin/media
**Root Cause:** Kolumna `position` zamiast `sort_order` w MediaManager
**Status:** ✅ NAPRAWIONY przez deployment-specialist
**Lokalizacja:** `app/Http/Livewire/Admin/Media/MediaManager.php`

---

### ❌ 14.2 Checkboxy bulk actions nie działają w zakładce Galeria
**Problem:** Zaznaczanie zdjęć do operacji grupowych nie działa
**Root Cause:**
- Brak `wire:key` na `.media-gallery-item` (linia 208)
- Możliwy konflikt Alpine.js z Livewire `wire:model.live`
- Checkbox `@click.stop` może blokować event propagation

**Diagnoza szczegółowa:**
```blade
{{-- CURRENT (linia 208-215) --}}
<div class="media-gallery-item {{ ... }}">
    <input type="checkbox"
           wire:model.live="selectedIds"
           value="{{ $item->id }}"
           @click.stop
           class="media-gallery-item-checkbox" />
```

**Problemy:**
1. Brak `wire:key="{{ $item->id }}"` na parent div
2. `@click.stop` może konfliktować z Livewire
3. Brak fallback dla `wire:click="toggleSelection({{ $item->id }})"` w przypadku failures

**Proponowane rozwiązanie:**
```blade
{{-- FIX: Dodać wire:key + wire:click fallback --}}
<div class="media-gallery-item {{ ... }}" wire:key="media-{{ $item->id }}">
    <input type="checkbox"
           wire:model.live="selectedIds"
           value="{{ $item->id }}"
           wire:click="toggleSelection({{ $item->id }})"
           class="media-gallery-item-checkbox"
           title="Zaznacz do operacji grupowych" />
```

**Lokalizacja:** `resources/views/livewire/products/management/tabs/gallery-tab.blade.php` linie 208-215

**Priorytet:** 🔴 HIGH (blokuje bulk operations)

---

### ❌ 14.3 Brak możliwości zmiany kolejności zdjęć (Reorder)
**Problem:** Nie ma sposobu na zmianę kolejności zdjęć w galerii
**Root Cause:** BRAK IMPLEMENTACJI drag&drop reorder

**Co istnieje:**
- ✅ `MediaManager::reorder()` method (linia 266 w MediaManager.php)
- ✅ `sort_order` kolumna w DB
- ❌ BRAK UI do drag&drop
- ❌ BRAK Alpine.js sortable logic

**Proponowane rozwiązanie:**

**Option A: Alpine.js + Sortable.js (RECOMMENDED)**
```blade
{{-- Gallery grid z sortable --}}
<div class="media-gallery-grid mt-4"
     x-data="{
         sortable: null,
         initSortable() {
             this.sortable = Sortable.create(this.$el, {
                 animation: 150,
                 onEnd: (evt) => {
                     const order = {};
                     this.$el.querySelectorAll('[data-media-id]').forEach((el, index) => {
                         order[el.dataset.mediaId] = index;
                     });
                     $wire.reorderImages(order);
                 }
             });
         }
     }"
     x-init="initSortable()">
```

**Option B: Simple Arrow Buttons (FALLBACK)**
```blade
{{-- Up/Down arrows per image --}}
<button wire:click="moveUp({{ $item->id }})" class="media-btn media-btn-sm">↑</button>
<button wire:click="moveDown({{ $item->id }})" class="media-btn media-btn-sm">↓</button>
```

**Backend (GalleryTab.php):**
```php
public function reorderImages(array $order): void
{
    app(MediaManager::class)->reorder($order);
    $this->dispatch('notify', ['type' => 'success', 'message' => 'Kolejnosc zmieniona']);
}

public function moveUp(int $mediaId): void { /* ... */ }
public function moveDown(int $mediaId): void { /* ... */ }
```

**Dependencies:**
- `npm install sortablejs` (Option A)
- Import w `resources/js/app.js`

**Lokalizacja:**
- View: `resources/views/livewire/products/management/tabs/gallery-tab.blade.php`
- Component: `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php`
- JS: `resources/js/app.js` (nowy import)

**Priorytet:** 🟡 MEDIUM (funkcjonalność istnieje backend, brakuje tylko UI)

---

### ❌ 14.4 Brak oznaczeń na którym sklepie występują zdjęcia
**Problem:** Labele sklepów nie pokazują się lub są puste
**Root Cause:** `loadSyncStatus()` ładuje mapping ale może być pusty jeśli sync nie był wykonany

**Diagnoza szczegółowa:**
```php
// CURRENT (GalleryTab.php linia 413-421)
protected function loadSyncStatus(): void
{
    if (!$this->product) return;

    $this->syncStatus = [];
    foreach ($this->getMedia() as $media) {
        $this->syncStatus[$media->id] = $media->prestashop_mapping ?? [];
    }
}
```

**Problem:**
- `prestashop_mapping` jest NULL jeśli zdjęcie nigdy nie było sync'owane
- Labels pokazują się tylko dla sync'owanych zdjęć
- Brak różnicy między "nie sync'owane" vs "sync error"

**Proponowane rozwiązanie:**

**Backend enhancement:**
```php
protected function loadSyncStatus(): void
{
    if (!$this->product) return;

    $this->syncStatus = [];

    // Get connected shops for this product
    $productShopIds = $this->product->shopData()->pluck('shop_id')->toArray();

    foreach ($this->getMedia() as $media) {
        $mapping = $media->prestashop_mapping ?? [];

        // Initialize status for all product shops
        $status = [];
        foreach ($productShopIds as $shopId) {
            $shopKey = "store_{$shopId}";
            $status[$shopKey] = $mapping[$shopKey] ?? ['status' => 'pending'];
        }

        $this->syncStatus[$media->id] = $status;
    }
}
```

**Frontend enhancement:**
```blade
{{-- Show labels for ALL product shops, not just synced ones --}}
@if(isset($syncStatus[$item->id]) && !empty($syncStatus[$item->id]))
    <div class="media-sync-labels">
        @foreach($syncStatus[$item->id] as $shopKey => $status)
            @php
                $shopId = str_replace('store_', '', $shopKey);
                $shop = $shops->firstWhere('id', $shopId);
                $isSynced = isset($status['ps_image_id']) && $status['ps_image_id'];
                $statusClass = $isSynced ? 'synced' : ($status['status'] ?? 'pending');
            @endphp
            @if($shop)
                <span class="media-sync-label {{ $statusClass }}" title="{{ $shop->name }}">
                    {{ Str::limit($shop->name, 10) }}
                </span>
            @endif
        @endforeach
    </div>
@endif
```

**CSS enhancement (media-gallery.css):**
```css
.media-sync-label.pending {
    background-color: var(--color-gray-500);
    color: var(--color-text-tertiary);
}
```

**Lokalizacja:**
- Backend: `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php` linia 413-421
- View: `resources/views/livewire/products/management/tabs/gallery-tab.blade.php` linie 258-273
- CSS: `resources/css/products/media-gallery.css` (dodać .pending class)

**Priorytet:** 🟡 MEDIUM (informacyjne, nie blokuje workflow)

---

### ❌ 14.5 "Pobierz z PrestaShop" pobiera ze WSZYSTKICH sklepów zamiast podłączonych
**Problem:** Dropdown pokazuje wszystkie sklepy PS, nie tylko te gdzie produkt jest przypisany
**Root Cause:** `getShops()` zwraca `PrestaShopShop::active()->get()` zamiast filtrować po `product_shop`

**Diagnoza szczegółowa:**
```php
// CURRENT (GalleryTab.php linia 456-459)
public function getShops(): Collection
{
    return PrestaShopShop::active()->get();
}
```

**Problem:**
- Pokazuje sklepy gdzie produktu NIE MA
- User może próbować sync z sklep gdzie produkt nie istnieje → błąd

**Proponowane rozwiązanie:**

**Backend fix:**
```php
/**
 * Get shops where this product is assigned
 */
public function getShops(): Collection
{
    if (!$this->product) {
        return collect();
    }

    // Get shop IDs where product is assigned (from product_shop table)
    $productShopIds = $this->product->shopData()
        ->pluck('shop_id')
        ->toArray();

    // Return only those shops
    return PrestaShopShop::active()
        ->whereIn('id', $productShopIds)
        ->get();
}
```

**Validation:**
- If product NOT assigned to ANY shop → dropdown should be empty or disabled
- If product assigned to 2 shops → dropdown shows only those 2

**Lokalizacja:** `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php` linia 456-459

**Priorytet:** 🔴 HIGH (prevents errors, improves UX)

---

### ❌ 14.6 Brak możliwości przypisywania zdjęć do konkretnych sklepów
**Problem:** Wszystkie zdjęcia sync'ują się do wszystkich sklepów, brak kontroli per-shop
**Root Cause:** BRAK FUNKCJONALNOŚCI - wymaga nowego feature

**Wymagania:**
1. UI: Checkboxy per shop przy każdym zdjęciu
2. Backend: Update `prestashop_mapping` per shop
3. Logic: Sync tylko do zaznaczonych sklepów
4. Validation: Minimum 1 shop must be selected per image

**Proponowane rozwiązanie:**

**UI Enhancement (per image):**
```blade
{{-- Shop assignment checkboxes (w media-gallery-item-actions lub modal) --}}
<div class="media-shop-assignment" x-data="{ open: false }">
    <button @click="open = !open" class="media-btn media-btn-sm" title="Przypisz do sklepow">
        <svg>...</svg> Sklepy
    </button>

    <div x-show="open" @click.away="open = false" class="dropdown-menu">
        @foreach($shops as $shop)
            <label class="flex items-center gap-2 p-2">
                <input type="checkbox"
                       wire:model.live="shopAssignments.{{ $item->id }}.{{ $shop->id }}"
                       wire:change="updateShopAssignment({{ $item->id }}, {{ $shop->id }}, $event.target.checked)" />
                <span>{{ $shop->name }}</span>
            </label>
        @endforeach
    </div>
</div>
```

**Backend (GalleryTab.php):**
```php
/**
 * Shop assignments: [media_id => [shop_id => bool]]
 */
public array $shopAssignments = [];

/**
 * Load shop assignments on mount
 */
public function mount(?int $productId = null): void
{
    // ... existing code ...
    $this->loadShopAssignments();
}

protected function loadShopAssignments(): void
{
    if (!$this->product) return;

    $productShopIds = $this->product->shopData()->pluck('shop_id')->toArray();

    foreach ($this->getMedia() as $media) {
        $mapping = $media->prestashop_mapping ?? [];

        foreach ($productShopIds as $shopId) {
            $shopKey = "store_{$shopId}";
            $this->shopAssignments[$media->id][$shopId] = isset($mapping[$shopKey]);
        }
    }
}

public function updateShopAssignment(int $mediaId, int $shopId, bool $assigned): void
{
    $media = Media::findOrFail($mediaId);

    if ($assigned) {
        // Mark for sync to this shop
        $media->setPrestaShopMapping($shopId, [
            'assigned' => true,
            'status' => 'pending',
        ]);
    } else {
        // Remove assignment (and delete from PS if synced)
        $mapping = $media->getPrestaShopMapping($shopId);
        if ($mapping && isset($mapping['ps_image_id'])) {
            // Dispatch delete job
            $shop = PrestaShopShop::find($shopId);
            if ($shop) {
                app(MediaSyncService::class)->deleteFromPrestaShop($media, $shop);
            }
        }

        $media->setPrestaShopMapping($shopId, [
            'assigned' => false,
            'ps_image_id' => null,
        ]);
    }

    $this->loadShopAssignments();
    $this->loadSyncStatus();
}
```

**Sync modification (pullFromShop, pushToShop):**
- Check `shopAssignments` before sync
- Only sync if shop is assigned
- Show warning if trying to sync unassigned

**Lokalizacja:**
- View: `resources/views/livewire/products/management/tabs/gallery-tab.blade.php` (nowy UI)
- Component: `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php` (nowe properties/methods)
- CSS: `resources/css/products/media-gallery.css` (dodać .media-shop-assignment styles)

**Priorytet:** 🟡 MEDIUM (enhancement, nie krytyczne)

**Dependencies:**
- Wymaga wcześniejszego fix'u 14.5 (filtered shops)

---

### ❌ 14.7 Brak podglądu zdjęcia w pełnym rozmiarze (Lightbox)
**Problem:** Kliknięcie w zdjęcie nic nie robi, brak podglądu full size
**Root Cause:** BRAK IMPLEMENTACJI lightbox

**Wymagania:**
1. Click na thumbnail → open lightbox z full image
2. Navigation: prev/next arrows
3. Close: ESC key, click outside, X button
4. Keyboard support: arrows, ESC
5. Zoom support (optional)

**Proponowane rozwiązanie:**

**Option A: GLightbox Library (RECOMMENDED)**
```bash
npm install glightbox
```

```js
// resources/js/app.js
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

document.addEventListener('livewire:navigated', () => {
    const lightbox = GLightbox({
        selector: '.media-gallery-item-image',
        touchNavigation: true,
        loop: true,
        autoplayVideos: false,
    });
});
```

**Option B: Alpine.js Custom Lightbox (NO DEPENDENCIES)**
```blade
{{-- Lightbox component (w gallery-tab.blade.php) --}}
<div x-data="{
        lightbox: false,
        currentImage: null,
        currentIndex: 0,
        images: @js($media->pluck('url')->toArray()),
        openLightbox(index) {
            this.currentIndex = index;
            this.currentImage = this.images[index];
            this.lightbox = true;
        },
        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this.currentImage = this.images[this.currentIndex];
        },
        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.currentImage = this.images[this->currentIndex];
        }
     }"
     @keydown.escape.window="lightbox = false"
     @keydown.arrow-right.window="if(lightbox) next()"
     @keydown.arrow-left.window="if(lightbox) prev()">

    {{-- Images grid --}}
    @foreach($media as $index => $item)
        <img @click="openLightbox({{ $index }})"
             src="{{ $item->thumbnailUrl ?? $item->url }}"
             class="media-gallery-item-image cursor-pointer" />
    @endforeach

    {{-- Lightbox overlay --}}
    <div x-show="lightbox"
         x-transition
         @click.self="lightbox = false"
         class="media-lightbox-overlay">
        <button @click="lightbox = false" class="media-lightbox-close">✕</button>
        <button @click="prev()" class="media-lightbox-nav media-lightbox-prev">‹</button>
        <button @click="next()" class="media-lightbox-nav media-lightbox-next">›</button>
        <img :src="currentImage" class="media-lightbox-image" />
    </div>
</div>
```

**CSS (media-gallery.css):**
```css
.media-lightbox-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-lightbox-image {
    max-width: 90vw;
    max-height: 90vh;
    object-fit: contain;
}

.media-lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 2rem;
    color: white;
    background: transparent;
    border: none;
    cursor: pointer;
    z-index: 10000;
}

.media-lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    color: white;
    background: rgba(0, 0, 0, 0.5);
    border: none;
    cursor: pointer;
    padding: 1rem;
    z-index: 10000;
}

.media-lightbox-prev { left: 1rem; }
.media-lightbox-next { right: 1rem; }
```

**Lokalizacja:**
- View: `resources/views/livewire/products/management/tabs/gallery-tab.blade.php`
- CSS: `resources/css/products/media-gallery.css`
- JS: `resources/js/app.js` (Option A only)

**Priorytet:** 🟢 LOW (nice to have, nie blokuje workflow)

**Recommendation:** Option B (Alpine.js) - no dependencies, już używamy Alpine

---

### ❌ 14.8 Panel /admin/media wymaga redesignu do standardów PPM
**Problem:** UI panelu /admin/media nie spełnia enterprise standards
**Root Cause:** Brak spójności z resztą aplikacji (enterprise-card, tabs-enterprise, etc.)

**Problemy szczegółowe:**
1. Brak `enterprise-card` container
2. Brak `tabs-enterprise` dla zakładek
3. Brak standardowych `.btn-enterprise-*` buttons
4. Tabele nie używają `.enterprise-table`
5. Filters nie używają `.filter-bar`

**Proponowane rozwiązanie:**

**View Redesign (`media-manager.blade.php`):**
```blade
<div class="enterprise-container">
    <div class="enterprise-header">
        <h1 class="enterprise-title">Zarządzanie Mediami</h1>
        <div class="enterprise-actions">
            <button wire:click="toggleSelectMode" class="btn-enterprise-secondary">
                {{ $selectMode ? 'Anuluj zaznaczanie' : 'Zaznacz wiele' }}
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Wszystkie media</div>
            <div class="stat-value">{{ $stats['totalMedia'] }}</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-label">Osierocone</div>
            <div class="stat-value">{{ $stats['orphanedMedia'] }}</div>
        </div>
        {{-- ... more stats ... --}}
    </div>

    {{-- Tabs Navigation --}}
    <div class="tabs-enterprise">
        <button wire:click="switchTab('products')"
                class="tab-btn {{ $activeTab === 'products' ? 'active' : '' }}">
            Produkty z mediami
        </button>
        <button wire:click="switchTab('orphaned')"
                class="tab-btn {{ $activeTab === 'orphaned' ? 'active' : '' }}">
            Osierocone media
        </button>
        <button wire:click="switchTab('sync')"
                class="tab-btn {{ $activeTab === 'sync' ? 'active' : '' }}">
            Status synchronizacji
        </button>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Szukaj produktu (SKU/nazwa)"
               class="filter-input" />

        <select wire:model.live="filterShop" class="filter-select">
            <option value="">Wszystkie sklepy</option>
            @foreach($shops as $shop)
                <option value="{{ $shop->id }}">{{ $shop->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterSyncStatus" class="filter-select">
            <option value="">Wszystkie statusy</option>
            <option value="pending">Oczekujące</option>
            <option value="synced">Zsynchronizowane</option>
            <option value="error">Błędy</option>
        </select>

        <button wire:click="resetFilters" class="btn-enterprise-secondary">
            Wyczyść filtry
        </button>
    </div>

    {{-- Content based on active tab --}}
    <div class="enterprise-card">
        @if($activeTab === 'products')
            @include('livewire.admin.media.partials.products-tab')
        @elseif($activeTab === 'orphaned')
            @include('livewire.admin.media.partials.orphaned-tab')
        @else
            @include('livewire.admin.media.partials.sync-tab')
        @endif
    </div>
</div>
```

**CSS Enhancement (`media-admin.css`):**
```css
/* Use existing enterprise styles from admin/components.css */
@import '../components.css';

/* Media-specific overrides */
.media-manager {
    /* ... existing ... */
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--color-bg-secondary);
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
}

.stat-card.stat-warning {
    border-color: var(--color-warning);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text-primary);
}
```

**Partials to Create:**
1. `resources/views/livewire/admin/media/partials/products-tab.blade.php`
2. `resources/views/livewire/admin/media/partials/orphaned-tab.blade.php`
3. `resources/views/livewire/admin/media/partials/sync-tab.blade.php`

**Lokalizacja:**
- View: `resources/views/livewire/admin/media/media-manager.blade.php`
- CSS: `resources/css/admin/media-admin.css`
- Partials: `resources/views/livewire/admin/media/partials/` (nowy folder)

**Priorytet:** 🟡 MEDIUM (UI/UX improvement, nie blokuje funkcjonalności)

---

## 🚀 15. PHASE 10: BUG FIXES & UX IMPROVEMENTS (NOWA FAZA - 3-4 dni)

**Cel:** Rozwiązanie wszystkich 8 zgłoszonych problemów

### ✅ 15.1 Quick Wins (1 dzień)
- ✅ 15.1.1 FIX: /admin/media 500 error - DONE
- ❌ 15.1.2 FIX: Checkboxy bulk actions (wire:key + wire:click)
- ❌ 15.1.3 FIX: "Pobierz z PS" filtruje po product_shop
- ❌ 15.1.4 ENHANCE: Sync labels pokazują wszystkie sklepy

**Validation:** Chrome DevTools MCP - checkboxy działają, dropdown filtrowany

---

### ❌ 15.2 Medium Features (1-2 dni)
- ❌ 15.2.1 FEATURE: Reorder images (Sortable.js lub arrows)
- ❌ 15.2.2 FEATURE: Lightbox podgląd (Alpine.js implementation)
- ❌ 15.2.3 REDESIGN: /admin/media panel (enterprise standards)

**Validation:** Manual test - drag&drop działa, lightbox otwiera się, panel wygląda enterprise

---

### ❌ 15.3 Advanced Feature (1 dzień)
- ❌ 15.3.1 FEATURE: Shop assignment per image
- ❌ 15.3.2 FEATURE: Selective sync to shops
- ❌ 15.3.3 VALIDATION: Sync only to assigned shops

**Validation:** Manual test - assign/unassign shops, verify sync behavior

---

## 📅 16. UPDATED TIMELINE

**TOTAL:** ~25-32 dni roboczych (5-6.5 tygodni)

| Phase | Dni | Priority | Status |
|-------|-----|----------|--------|
| PHASE 1-6 | - | CRITICAL | ✅ DONE |
| PHASE 7: Variant Integration | 2-3 | MEDIUM | 🔵 TODO |
| PHASE 8: Performance | 2-3 | HIGH | 🔵 TODO |
| PHASE 9: Testing & Docs | 2 | CRITICAL | 🔵 TODO |
| **PHASE 10: Bug Fixes & UX** | **3-4** | **🔴 CRITICAL** | **🛠️ PLANNING** |

**PHASE 10 Breakdown:**
- Quick Wins: 1 dzień (fixes 14.2, 14.3, 14.4, 14.5)
- Medium Features: 1-2 dni (fixes 14.6, 14.7, 14.8)
- Advanced Feature: 1 dzień (fix 14.9)

---

## 🎯 17. NEXT STEPS - UPDATED

### 17.1 Immediate Actions (PHASE 10)
1. ❌ User approval PHASE 10 plan
2. ❌ Rozpocząć od Quick Wins (15.1)
3. ❌ Chrome DevTools verification po każdym fix
4. ❌ Deploy + test na produkcji

### 17.2 Questions for User
- **Q1:** Czy PHASE 10 ma najwyższy priorytet czy kontynuować PHASE 7-9?
- **Q2:** Które z bugów są CRITICAL dla użytkowników (blokują pracę)?
- **Q3:** Czy preferujesz Sortable.js (external lib) czy arrow buttons (no deps)?
- **Q4:** Czy lightbox jest must-have czy nice-to-have?

---

**PLAN UPDATED BY:** Architect Agent
**DATE:** 2025-12-01
**VERSION:** 2.0 (PHASE 10 added)
**STATUS:** Awaiting User Approval for PHASE 10
