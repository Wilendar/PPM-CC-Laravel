# DIAGRAMY ARCHITEKTURY: System IMPORT DO PPM

**Agent:** architect
**Data:** 2025-12-08
**Powiązany raport:** architect_PPM_IMPORT_SYSTEM_ARCHITECTURE.md

---

## 📊 DIAGRAM 1: Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ PENDING_PRODUCTS : creates
    USERS ||--o{ IMPORT_SESSIONS : initiates
    USERS ||--o{ PUBLISH_HISTORY : publishes

    IMPORT_SESSIONS ||--o{ PENDING_PRODUCTS : contains
    PENDING_PRODUCTS ||--o| PRODUCTS : "publishes to"
    PENDING_PRODUCTS ||--o{ PUBLISH_HISTORY : tracked_by

    PRODUCTS ||--o{ PRODUCT_SHOP_DATA : has
    PRODUCTS ||--o{ PRODUCT_CATEGORIES : "belongs to"
    CATEGORIES ||--o{ PRODUCT_CATEGORIES : contains

    PRESTASHOP_SHOPS ||--o{ PRODUCT_SHOP_DATA : manages

    PENDING_PRODUCTS {
        int id PK
        string sku UK
        string name
        int product_type_id FK
        json category_ids
        json shop_ids
        json temp_media_paths
        json completion_status
        int completion_percentage
        bool is_ready_for_publish
        int import_session_id FK
        int imported_by FK
        timestamp published_at
        int published_as_product_id FK
    }

    IMPORT_SESSIONS {
        int id PK
        string uuid UK
        string session_name
        enum import_method
        string import_source_file
        json parsed_data
        int total_rows
        int products_created
        int products_published
        int products_failed
        enum status
        json error_log
        int imported_by FK
    }

    PUBLISH_HISTORY {
        int id PK
        int pending_product_id FK
        int product_id FK
        int published_by FK
        json published_shops
        json published_categories
        int published_media_count
        json sync_jobs_dispatched
        enum sync_status
    }
```

---

## 🔄 DIAGRAM 2: Import Workflow (Sequence Diagram)

```mermaid
sequenceDiagram
    actor User
    participant UI as Import Panel
    participant Wizard as ImportWizard
    participant Processor as ImportProcessor
    participant Validator as ValidationService
    participant DB as Database
    participant Service as PendingProductService

    User->>UI: Click "Nowy Import"
    UI->>Wizard: Open modal

    alt Import Mode: Paste SKU
        User->>Wizard: Paste list of SKUs
        Wizard->>Processor: parseSingleColumn(text)
        Processor->>Validator: detectDuplicates(skus)
        Validator-->>Processor: conflicts: [SKU-002]
        Processor-->>Wizard: valid: [SKU-001, SKU-003]
        Wizard->>User: Show preview (2 valid, 1 duplicate)
        User->>Wizard: Confirm import
    else Import Mode: CSV/Excel
        User->>Wizard: Upload file + map columns
        Wizard->>Processor: parseCsvFile(file, mapping)
        Processor->>Validator: validateImportData(rows)
        Validator-->>Processor: valid + invalid rows
        Processor-->>Wizard: Show preview
        User->>Wizard: Confirm import
    end

    Wizard->>DB: Create ImportSession
    DB-->>Wizard: session_id

    loop For each valid row
        Wizard->>Service: create(data)
        Service->>DB: Insert PendingProduct
        Service->>Service: calculateCompletion()
        DB-->>Service: pending_product_id
    end

    Wizard->>DB: Update ImportSession stats
    Wizard->>UI: Redirect to list
    UI->>User: Show imported products
```

---

## 📤 DIAGRAM 3: Publish Workflow (State Machine)

```mermaid
stateDiagram-v2
    [*] --> DRAFT: Import SKU/CSV

    DRAFT --> INCOMPLETE: completion < 100%
    DRAFT --> READY: is_ready_for_publish = true

    INCOMPLETE --> EDITING: User clicks "Edytuj"
    EDITING --> INCOMPLETE: Save (still missing data)
    EDITING --> READY: Save (all required fields filled)

    READY --> VALIDATION: User clicks "Publikuj"
    VALIDATION --> READY: Validation failed
    VALIDATION --> PUBLISHING: Validation passed

    PUBLISHING --> CREATE_PRODUCT: DB Transaction start
    CREATE_PRODUCT --> SYNC_CATEGORIES: Product created
    SYNC_CATEGORIES --> MOVE_MEDIA: Categories synced
    MOVE_MEDIA --> CREATE_SHOP_DATA: Media moved
    CREATE_SHOP_DATA --> MARK_PUBLISHED: ShopData created
    MARK_PUBLISHED --> DISPATCH_JOBS: Transaction commit
    DISPATCH_JOBS --> PUBLISHED: Jobs dispatched

    PUBLISHED --> SYNCING: PrestaShop sync in progress
    SYNCING --> SYNCED: All shops synced
    SYNCED --> [*]

    note right of DRAFT
        PendingProduct
        completion_percentage = 20%
    end note

    note right of READY
        PendingProduct
        is_ready_for_publish = true
        completion_percentage >= 85%
    end note

    note right of PUBLISHED
        Product created
        Moved to ProductList
        Jobs dispatched to queue
    end note
```

---

## 🏗️ DIAGRAM 4: Component Architecture (C4 Model - Container)

```mermaid
graph TB
    subgraph "PPM Application"
        UI[Import Panel UI<br/>Livewire Components]
        API[Controllers Layer<br/>REST/Livewire Actions]

        subgraph "Services Layer"
            IMP[ImportProcessor<br/>Parse SKU/CSV]
            VAL[ValidationService<br/>Business Rules]
            PPS[PendingProductService<br/>CRUD Operations]
            PUB[PublishService<br/>DRAFT→PUBLISHED]
        end

        subgraph "Data Layer"
            PP[(PendingProduct<br/>Model)]
            IS[(ImportSession<br/>Model)]
            PH[(PublishHistory<br/>Model)]
            PR[(Product<br/>Model)]
        end

        subgraph "Queue Jobs"
            PPJ[PublishProductJob]
            BSP[BulkSyncProducts]
        end
    end

    subgraph "External Systems"
        PS[(PrestaShop API)]
        STOR[(Storage<br/>Media Files)]
    end

    UI --> API
    API --> IMP
    API --> VAL
    API --> PPS
    API --> PUB

    IMP --> PP
    VAL --> PP
    PPS --> PP
    PPS --> IS

    PUB --> PP
    PUB --> PR
    PUB --> PH
    PUB --> PPJ

    PPJ --> BSP
    BSP --> PS

    PUB --> STOR

    style UI fill:#e1f5ff
    style IMP fill:#fff4e1
    style VAL fill:#fff4e1
    style PPS fill:#fff4e1
    style PUB fill:#fff4e1
    style PP fill:#e8f5e9
    style PR fill:#e8f5e9
    style PS fill:#ffebee
    style STOR fill:#ffebee
```

---

## 🔀 DIAGRAM 5: Data Flow - CSV Import Example

```mermaid
flowchart TD
    A[User uploads products.csv] --> B{Parse CSV}
    B --> C[Extract rows]
    C --> D{Map columns}
    D --> E[SKU → A<br/>Name → B<br/>Manufacturer → C]

    E --> F{Validate rows}
    F -->|Valid| G[Create ImportSession]
    F -->|Invalid| H[Show errors to user]
    H --> A

    G --> I{For each valid row}
    I --> J[Check SKU conflict]
    J -->|Exists in Products| K[Skip row]
    J -->|Exists in Pending| L[Skip row]
    J -->|Unique| M[Create PendingProduct]

    M --> N[Calculate completion %]
    N --> O{More rows?}
    O -->|Yes| I
    O -->|No| P[Update ImportSession stats]

    P --> Q[Display in Import Panel]
    Q --> R[User edits incomplete products]
    R --> S[User selects products]
    S --> T{Bulk actions?}

    T -->|Yes| U[Apply bulk updates]
    U --> V[Recalculate completion]
    V --> W{Ready to publish?}

    T -->|No| W
    W -->|No| R
    W -->|Yes| X[User clicks Publikuj]

    X --> Y{Validate publish}
    Y -->|Failed| Z[Show validation errors]
    Z --> R

    Y -->|Success| AA[PublishService.bulkPublish]
    AA --> AB[DB Transaction]
    AB --> AC[Create Product records]
    AC --> AD[Sync categories]
    AD --> AE[Move media files]
    AE --> AF[Create ProductShopData]
    AF --> AG[Mark as published]
    AG --> AH[Commit transaction]

    AH --> AI[Dispatch sync jobs]
    AI --> AJ[Products appear in ProductList]
    AJ --> AK[PrestaShop sync starts]
    AK --> AL[End]

    style A fill:#e3f2fd
    style M fill:#c8e6c9
    style AC fill:#c8e6c9
    style AJ fill:#c8e6c9
    style K fill:#ffcdd2
    style L fill:#ffcdd2
    style Z fill:#ffcdd2
```

---

## 🎨 DIAGRAM 6: UI Component Tree

```mermaid
graph TD
    ROOT[/admin/import/products]

    ROOT --> LIST[PendingProductsList Component]
    ROOT --> WIZARD[ImportWizard Modal]
    ROOT --> FORM[PendingProductForm Modal]
    ROOT --> BULK[BulkActions Dropdown]

    LIST --> TABLE[Table with inline editing]
    LIST --> ACTIONS[Row actions menu]

    WIZARD --> MODE[Select import mode]
    MODE --> PASTE_SKU[Paste SKU textarea]
    MODE --> PASTE_TWO[Paste SKU+Name textarea]
    MODE --> UPLOAD[CSV/Excel upload]

    UPLOAD --> MAP[Column mapping]
    MAP --> PREVIEW[Preview 10 rows]
    PREVIEW --> CONFIRM[Confirm import button]

    FORM --> TABS[Tab navigation]
    TABS --> TAB_BASIC[Podstawowe]
    TABS --> TAB_CAT[Kategorie<br/>CategoryTree picker]
    TABS --> TAB_VAR[Warianty<br/>→ ProductForm integration]
    TABS --> TAB_FEAT[Cechy/Dopasowania<br/>→ CompatibilityManagement]
    TABS --> TAB_MEDIA[Zdjęcia<br/>Drag&drop upload]
    TABS --> TAB_SHOPS[Sklepy<br/>Tile selector]

    BULK --> BULK_CAT[Przypisz kategorie]
    BULK --> BULK_PREFIX[Dodaj prefix/suffix]
    BULK --> BULK_TYPE[Ustaw typ produktu]
    BULK --> BULK_SHOPS[Wybierz sklepy]
    BULK --> BULK_PUB[Publikuj zaznaczone]

    style ROOT fill:#e1f5ff
    style LIST fill:#fff9c4
    style WIZARD fill:#f3e5f5
    style FORM fill:#e8f5e9
    style BULK fill:#ffccbc
```

---

## 🔄 DIAGRAM 7: Integration Points

```mermaid
graph LR
    subgraph "Import System"
        PPI[PendingProduct<br/>Import]
        PPF[PendingProduct<br/>Form]
        PUB[Publish<br/>Service]
    end

    subgraph "Existing Systems"
        PF[ProductForm<br/>Warianty]
        CM[CompatibilityManagement<br/>Dopasowania]
        CT[CategoryTree<br/>Picker]
        PSData[ProductShopData<br/>Per-shop tracking]
        BSP[BulkSyncProducts<br/>PrestaShop export]
    end

    PPF -->|dispatch event| PF
    PPF -->|redirect| CM
    PPF -->|embedded| CT

    PUB -->|creates| PSData
    PUB -->|dispatches| BSP

    PF -->|saves to| PPI
    CM -->|saves to| PPI
    CT -->|emits selection| PPF

    style PPI fill:#e3f2fd
    style PPF fill:#e3f2fd
    style PUB fill:#e3f2fd
    style PF fill:#fff9c4
    style CM fill:#fff9c4
    style CT fill:#fff9c4
```

---

## 📋 DIAGRAM 8: Permission Matrix

```mermaid
graph TB
    subgraph "User Roles & Permissions"
        ADMIN[Admin<br/>Full Access]
        MANAGER[Manager<br/>Import + Publish]
        EDITOR[Editor<br/>Import only]
        WAREHOUSE[Magazynier<br/>No access]
    end

    subgraph "Import Actions"
        IMP[Import SKU/CSV]
        EDIT[Edit pending products]
        BULK[Bulk actions]
        PUB[Publish to ProductList]
        HIST[View history]
    end

    ADMIN --> IMP
    ADMIN --> EDIT
    ADMIN --> BULK
    ADMIN --> PUB
    ADMIN --> HIST

    MANAGER --> IMP
    MANAGER --> EDIT
    MANAGER --> BULK
    MANAGER --> PUB
    MANAGER -.->|read only| HIST

    EDITOR --> IMP
    EDITOR --> EDIT
    EDITOR -.->|limited| BULK
    EDITOR -.x.->|denied| PUB
    EDITOR -.x.->|denied| HIST

    WAREHOUSE -.x.->|denied| IMP
    WAREHOUSE -.x.->|denied| EDIT

    style ADMIN fill:#4caf50
    style MANAGER fill:#8bc34a
    style EDITOR fill:#ffc107
    style WAREHOUSE fill:#f44336
    style PUB fill:#2196f3
```

---

## 🚀 DIAGRAM 9: Deployment Architecture

```mermaid
graph TB
    subgraph "Client Browser"
        UI[Import Panel UI<br/>Livewire + Alpine.js]
    end

    subgraph "Hostido.net.pl Server"
        WEB[Nginx/Apache<br/>Web Server]
        PHP[PHP 8.3<br/>Laravel App]

        subgraph "Laravel Services"
            LIVE[Livewire Components]
            SERV[Services Layer]
            QUEUE[Queue Worker<br/>database driver]
        end

        DB[(MariaDB 10.11<br/>Database)]
        STOR[Storage<br/>public/products/]
        CACHE[Cache<br/>Redis/File]
    end

    subgraph "External"
        PS[PrestaShop Shops<br/>8.x/9.x]
    end

    UI -->|HTTPS| WEB
    WEB --> PHP
    PHP --> LIVE
    LIVE --> SERV
    SERV --> DB
    SERV --> STOR
    SERV --> QUEUE

    QUEUE -->|Sync Jobs| PS

    SERV --> CACHE

    style UI fill:#e3f2fd
    style PHP fill:#fff9c4
    style DB fill:#c8e6c9
    style QUEUE fill:#ffccbc
    style PS fill:#f8bbd0
```

---

## 📈 DIAGRAM 10: Performance Flow

```mermaid
sequenceDiagram
    participant User
    participant UI
    participant Cache
    participant DB
    participant Queue
    participant PS as PrestaShop

    Note over User,PS: IMPORT PHASE (Fast)
    User->>UI: Paste 100 SKUs
    UI->>DB: Bulk insert (batches of 50)
    DB-->>UI: Success
    UI->>User: Flash: "100 products imported" (~3s)

    Note over User,PS: EDIT PHASE (Interactive)
    User->>UI: Edit product inline
    UI->>Cache: Check completion %
    Cache-->>UI: Cached (if exists)
    UI->>DB: Update single field
    DB-->>UI: Success
    UI->>UI: Recalculate completion
    UI->>Cache: Update cache
    UI->>User: Reflect change (~500ms)

    Note over User,PS: PUBLISH PHASE (Async)
    User->>UI: Bulk publish 50 products
    UI->>DB: Create Products (transaction)
    DB-->>UI: 50 Product IDs
    UI->>Queue: Dispatch 50 jobs (2 shops each)
    Queue-->>UI: 100 job UUIDs
    UI->>User: Flash: "Published, 100 jobs queued" (~5s)

    Note over Queue,PS: BACKGROUND SYNC (Async)
    loop For each job
        Queue->>PS: Export product
        PS-->>Queue: Success/Fail
        Queue->>DB: Update sync status
    end

    Queue->>User: Email notification (completion)
```

---

**KONIEC DIAGRAMÓW**
