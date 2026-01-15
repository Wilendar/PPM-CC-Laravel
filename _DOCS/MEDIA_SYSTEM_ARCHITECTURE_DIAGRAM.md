# Media System Architecture - Visual Diagrams

**Project:** PPM-CC-Laravel
**Document:** Media Management System Architecture
**Created:** 2025-11-28
**Version:** 1.0

---

## 🏗️ SYSTEM ARCHITECTURE OVERVIEW

```mermaid
graph TB
    subgraph "UI LAYER - Livewire Components"
        A1[GalleryTab]
        A2[MediaUploadWidget]
        A3[MediaGalleryGrid]
        A4[MediaManager Admin]
        A5[ProductList Thumbnail]
    end

    subgraph "SERVICE LAYER"
        B1[MediaManager]
        B2[ImageProcessor]
        B3[MediaSyncService]
        B4[MediaStorageService]
    end

    subgraph "JOB LAYER - Async Queue"
        C1[ProcessMediaUpload]
        C2[BulkMediaUpload]
        C3[SyncMediaFromPrestaShop]
        C4[PushMediaToPrestaShop]
        C5[GenerateMissingThumbnails]
        C6[CleanupOrphanedMedia]
    end

    subgraph "MODEL LAYER"
        D1[Media Model]
        D2[Product Model]
        D3[ProductVariant Model]
        D4[JobProgress Model]
    end

    subgraph "EXTERNAL SYSTEMS"
        E1[PrestaShop 8.x/9.x]
        E2[Laravel Storage]
        E3[Redis Cache]
        E4[Redis Queue]
    end

    A1 --> B1
    A2 --> B1
    A3 --> B1
    A4 --> B1

    B1 --> B4
    B1 --> B2
    B1 --> B3
    B1 --> C1
    B1 --> C2

    B3 --> C3
    B3 --> C4

    C1 --> B2
    C1 --> B4
    C1 --> D1

    C2 --> C1
    C2 --> D4

    C3 --> E1
    C3 --> B4
    C3 --> D1

    C4 --> E1
    C4 --> D1

    D1 --> D2
    D1 --> D3

    B4 --> E2
    B1 --> E3
    C1 --> E4
    C2 --> E4
```

---

## 📤 UPLOAD FLOW DIAGRAM

```mermaid
sequenceDiagram
    participant User
    participant UI as MediaUploadWidget
    participant MM as MediaManager
    participant Job as BulkMediaUpload
    participant PJob as ProcessMediaUpload
    participant IP as ImageProcessor
    participant MS as MediaStorageService
    participant Model as Media Model
    participant Event as MediaUploaded

    User->>UI: Drag & Drop 10 files
    UI->>MM: upload(files)
    MM->>Job: dispatch(files)
    Job-->>User: JobProgress: 0/10

    loop For each file
        Job->>PJob: dispatch(file)
        PJob->>MS: store(file, 'products/{SKU}/')
        MS-->>PJob: file_path
        PJob->>IP: convertToWebP(file)
        IP-->>PJob: webp_path
        PJob->>IP: generateThumbnails(webp)
        IP-->>PJob: thumbnail_paths
        PJob->>Model: create(media_data)
        Model-->>PJob: media_id
        PJob->>Event: dispatch(media)
        PJob-->>Job: Progress: +1
        Job-->>User: JobProgress: 1/10, 2/10, ...
    end

    Job-->>User: JobProgress: 10/10 Complete
    Event->>UI: refreshGallery()
    UI-->>User: Updated Gallery
```

---

## 🔄 PRESTASHOP SYNC FLOW

```mermaid
sequenceDiagram
    participant User
    participant UI as GalleryTab
    participant MSS as MediaSyncService
    participant Job as SyncMediaFromPrestaShop
    participant PS as PrestaShop8Client
    participant API as PrestaShop API
    participant MS as MediaStorageService
    participant IP as ImageProcessor
    participant Model as Media Model

    User->>UI: Click "Pobierz z PrestaShop"
    UI->>MSS: pullFromPrestaShop(product, shop)
    MSS->>Job: dispatch(product, shop)

    Job->>PS: getProductImages(product_id, shop_id)
    PS->>API: GET /api/products/{id}/images
    API-->>PS: images_xml[]
    PS-->>Job: images_data[]

    loop For each PrestaShop image
        Job->>Job: Check if exists in PPM
        alt Image NOT in PPM
            Job->>API: Download image URL
            API-->>Job: image_binary
            Job->>MS: store(image, 'products/{SKU}/')
            MS-->>Job: file_path
            Job->>IP: convertToWebP(image)
            IP-->>Job: webp_path
            Job->>IP: generateThumbnails(webp)
            IP-->>Job: thumbnail_paths
            Job->>Model: create with prestashop_mapping
            Model-->>Job: media_id
        end
    end

    Job-->>UI: Sync Complete
    UI->>UI: refreshGallery()
    UI->>UI: updateLiveLabels()
    UI-->>User: Updated Gallery + Labels
```

---

## 🎨 COMPONENT RELATIONSHIPS

```mermaid
graph LR
    subgraph "ProductForm Component"
        A1[Basic Tab<br/>Primary Image]
        A2[Gallery Tab<br/>Full Gallery]
    end

    subgraph "Reusable Components"
        B1[MediaUploadWidget]
        B2[MediaGalleryGrid]
    end

    subgraph "Admin Panel"
        C1[MediaManager<br/>/admin/media]
    end

    subgraph "Product List"
        D1[ProductList<br/>Thumbnail Column]
    end

    A1 -.Click.-> A2
    A2 --> B1
    A2 --> B2
    C1 --> B1
    C1 --> B2
    D1 -.Eager Load.-> E[Media Model]
    A2 -.Eager Load.-> E
    C1 -.Query.-> E
```

---

## 📁 FILE STORAGE STRUCTURE

```mermaid
graph TD
    A[storage/products/] --> B1[MPP-12345/]
    A --> B2[MPP-67890/]
    A --> B3[MPP-11111/]

    B1 --> C1[Przednia_Klapa_01.webp]
    B1 --> C2[Przednia_Klapa_02.webp]
    B1 --> C3[thumbs/]

    C3 --> D1[Przednia_Klapa_01_thumb.webp]
    C3 --> D2[Przednia_Klapa_02_thumb.webp]

    B2 --> E1[Silnik_125cc_01.webp]
    B2 --> E2[thumbs/]

    style A fill:#e1f5ff
    style B1 fill:#fff3cd
    style B2 fill:#fff3cd
    style B3 fill:#fff3cd
    style C3 fill:#d4edda
    style E2 fill:#d4edda
```

---

## 🗄️ DATABASE SCHEMA RELATIONSHIPS

```mermaid
erDiagram
    MEDIA ||--o{ PRODUCT : "mediable_type/id"
    MEDIA ||--o{ PRODUCT_VARIANT : "mediable_type/id"
    PRODUCT ||--o{ PRODUCT_VARIANT : "has many"

    MEDIA {
        bigint id PK
        string mediable_type
        bigint mediable_id
        string file_name
        string file_path
        int file_size
        string mime_type
        int width
        int height
        string alt_text
        int sort_order
        bool is_primary
        json prestashop_mapping
        enum sync_status
        bool is_active
    }

    PRODUCT {
        bigint id PK
        string sku UK
        string display_name
        string product_type
    }

    PRODUCT_VARIANT {
        bigint id PK
        bigint product_id FK
        string variant_name
        string sku UK
    }
```

---

## 🚀 IMPLEMENTATION PHASES TIMELINE

```mermaid
gantt
    title Media System Implementation Timeline
    dateFormat YYYY-MM-DD
    section PHASE 1
    Core Infrastructure (Services, Jobs, DTOs) :p1, 2025-12-01, 4d
    section PHASE 2
    Livewire Components Basic :p2, after p1, 4d
    section PHASE 3
    Advanced Upload (Drag&Drop, Folder) :p3, after p2, 3d
    section PHASE 4
    PrestaShop Sync :p4, after p3, 5d
    section PHASE 5
    ProductList Thumbnails :p5, after p4, 1d
    section PHASE 6
    Admin Media Manager :p6, after p5, 4d
    section PHASE 7
    Variant Integration :p7, after p6, 3d
    section PHASE 8
    Performance & Optimization :p8, after p7, 3d
    section PHASE 9
    Testing & Documentation :p9, after p8, 2d
```

---

## 🔐 SECURITY & PERMISSIONS FLOW

```mermaid
graph TD
    A[User Request] --> B{Check Permission}
    B -->|Admin| C[Full Access]
    B -->|Manager| D[Upload + Edit + Delete PPM]
    B -->|Editor| E[Upload + Edit Only]
    B -->|Other| F[Read Only]

    C --> G[MediaManager]
    D --> G
    E --> G
    F --> G

    G --> H{Action Type}
    H -->|Upload| I[ProcessMediaUpload Job]
    H -->|Delete| J{Delete From?}
    H -->|Sync| K[MediaSyncService]

    J -->|PPM Only| L[Soft Delete Media]
    J -->|PrestaShop Only| M[PushDeleteToPrestaShop]
    J -->|Both| N[L + M]

    K --> O[SyncMediaFromPrestaShop Job]
    K --> P[PushMediaToPrestaShop Job]
```

---

## 📊 PERFORMANCE OPTIMIZATION STRATEGY

```mermaid
graph TB
    subgraph "Request Level"
        A1[Lazy Load Gallery]
        A2[Thumbnail Cache Redis]
        A3[Infinite Scroll]
    end

    subgraph "Processing Level"
        B1[Queue High Priority<br/>Upload]
        B2[Queue Default<br/>Sync]
        B3[Queue Low<br/>Thumbnails/Cleanup]
    end

    subgraph "Storage Level"
        C1[WebP Compression]
        C2[Multiple Thumbnail Sizes]
        C3[SKU-based Folders]
    end

    subgraph "Database Level"
        D1[Strategic Indexes<br/>polymorphic]
        D2[Eager Loading<br/>with()]
        D3[Soft Deletes]
    end

    A1 --> E[Fast UI < 500ms]
    A2 --> E
    A3 --> E

    B1 --> F[Non-Blocking UX]
    B2 --> F
    B3 --> F

    C1 --> G[Small File Sizes]
    C2 --> G
    C3 --> G

    D1 --> H[Fast Queries]
    D2 --> H
    D3 --> H
```

---

## 🔄 JOB DEPENDENCIES & PRIORITIES

```mermaid
graph LR
    subgraph "High Priority Queue"
        A1[ProcessMediaUpload]
        A2[BulkMediaUpload]
    end

    subgraph "Default Priority Queue"
        B1[SyncMediaFromPrestaShop]
        B2[PushMediaToPrestaShop]
    end

    subgraph "Low Priority Queue"
        C1[GenerateMissingThumbnails]
        C2[CleanupOrphanedMedia]
    end

    A2 --> A1
    B1 --> A1
    B2 -.uses.-> D1[PrestaShop8Client]
    B1 -.uses.-> D1

    C1 -.runs.-> E1[Daily Scheduled]
    C2 -.runs.-> E2[Daily Scheduled]

    style A1 fill:#ff6b6b
    style A2 fill:#ff6b6b
    style B1 fill:#4ecdc4
    style B2 fill:#4ecdc4
    style C1 fill:#95e1d3
    style C2 fill:#95e1d3
```

---

## 🎯 SUCCESS METRICS DASHBOARD

```mermaid
graph TB
    subgraph "Performance Metrics"
        A1[Upload 10 images<br/>Target: < 30s]
        A2[Gallery Display 20 thumbs<br/>Target: < 500ms]
        A3[Sync from PS 10 images<br/>Target: < 60s]
        A4[Push to PS 5 images<br/>Target: < 45s]
    end

    subgraph "Code Quality Metrics"
        B1[Services Test Coverage<br/>Target: 100%]
        B2[Components Test Coverage<br/>Target: 80%+]
        B3[File Size<br/>Target: < 300 lines]
        B4[Inline Styles<br/>Target: 0]
    end

    subgraph "User Experience Metrics"
        C1[Drag&Drop FPS<br/>Target: 60 FPS]
        C2[Progress Delay<br/>Target: < 1s]
        C3[Console Errors<br/>Target: 0]
        C4[wire:snapshot Issues<br/>Target: 0]
    end

    A1 --> D[✅ Performance OK]
    A2 --> D
    A3 --> D
    A4 --> D

    B1 --> E[✅ Quality OK]
    B2 --> E
    B3 --> E
    B4 --> E

    C1 --> F[✅ UX OK]
    C2 --> F
    C3 --> F
    C4 --> F

    D --> G[🎉 SYSTEM READY]
    E --> G
    F --> G
```

---

## 🛠️ DEVELOPMENT WORKFLOW

```mermaid
sequenceDiagram
    participant Dev as Developer
    participant Branch as Feature Branch
    participant Tests as PHPUnit Tests
    participant Build as npm run build
    participant Deploy as Deployment
    participant CDT as Chrome DevTools MCP
    participant User as End User

    Dev->>Branch: git checkout -b feature/media-phase-X
    Dev->>Dev: Write code (Services/Components)
    Dev->>Tests: php artisan test

    alt Tests Fail
        Tests-->>Dev: ❌ Fix issues
        Dev->>Dev: Debug & Fix
        Dev->>Tests: php artisan test
    end

    Tests-->>Dev: ✅ All Green
    Dev->>Build: npm run build
    Build-->>Dev: ✅ Assets built

    Dev->>Deploy: pscp upload files
    Deploy->>Deploy: Clear cache
    Deploy-->>Dev: ✅ Deployed

    Dev->>CDT: navigate_page + verify
    CDT->>CDT: Check console/network
    CDT->>CDT: Take snapshot
    CDT->>CDT: Verify no errors

    alt CDT Verification Fail
        CDT-->>Dev: ❌ Issues found
        Dev->>Dev: Fix issues
        Dev->>Build: npm run build
        Dev->>Deploy: Re-deploy
        Dev->>CDT: Re-verify
    end

    CDT-->>Dev: ✅ Verification OK
    Dev->>User: ✅ Feature Complete
```

---

## 📚 DOCUMENTATION STRUCTURE

```mermaid
graph TD
    A[Media System Docs] --> B1[_DOCS/MEDIA_SYSTEM_GUIDE.md<br/>User Guide]
    A --> B2[_DOCS/MEDIA_API_REFERENCE.md<br/>Developer Reference]
    A --> B3[_DOCS/MEDIA_SYSTEM_ARCHITECTURE_DIAGRAM.md<br/>This Document]
    A --> B4[Plan_Projektu/ETAP_05e_Media_Management_System.md<br/>Implementation Plan]
    A --> B5[_AGENT_REPORTS/architect_MEDIA_SYSTEM_ARCHITECTURE_REPORT.md<br/>Architecture Report]

    B1 --> C1[How to Upload]
    B1 --> C2[How to Sync]
    B1 --> C3[How to Manage]

    B2 --> D1[Service APIs]
    B2 --> D2[Job APIs]
    B2 --> D3[Event Listeners]

    style A fill:#e1f5ff
    style B1 fill:#d4edda
    style B2 fill:#fff3cd
    style B3 fill:#f8d7da
    style B4 fill:#d1ecf1
    style B5 fill:#e2e3e5
```

---

## 🔗 INTEGRATION POINTS

```mermaid
graph TB
    subgraph "Media System"
        A[Media Manager]
    end

    subgraph "Existing Systems"
        B1[ProductForm<br/>ETAP_05a]
        B2[ProductVariant<br/>ETAP_05b]
        B3[PrestaShop Sync<br/>ETAP_07]
        B4[JobProgress<br/>FAZA C]
        B5[Product List<br/>ETAP_05a]
    end

    A <--> B1
    A <--> B2
    A <--> B3
    A --> B4
    A --> B5

    B3 --> C[PrestaShop8Client]
    B3 --> D[ProductSyncStrategy]

    style A fill:#ff6b6b
    style B1 fill:#4ecdc4
    style B2 fill:#4ecdc4
    style B3 fill:#4ecdc4
    style B4 fill:#4ecdc4
    style B5 fill:#4ecdc4
```

---

**DOCUMENT END**

*Generated by: architect agent*
*Date: 2025-11-28*
*Version: 1.0*
*Status: Complete - Ready for Implementation*
