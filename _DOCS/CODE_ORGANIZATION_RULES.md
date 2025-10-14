# ZASADY ORGANIZACJI KODU DLA AGENTÓW

**KRYTYCZNE ZASADY STRUKTURY KODU:**

## 🔧 MODULARNOŚĆ I ORGANIZACJA PLIKÓW

### 1. **ZAKAZ PISANIA BARDZO DUŻEGO KODU W JEDNYM PLIKU**
- **Maksymalne limity:** 
  - Controller: max 300 linii (idealnie 150-200)
  - Service/Repository: max 500 linii (idealnie 200-300)
  - Livewire Component: max 400 linii (idealnie 150-250)
  - Blade template: max 200 linii (idealnie 50-100)

- **Gdy plik przekracza limity:**
  - Rozbij na mniejsze klasy/komponenty
  - Wyodrębnij logikę biznesową do Service classes
  - Stwórz oddzielne TraitY dla wspólnej funkcjonalności
  - Użyj wzorca Strategy/Factory dla złożonej logiki

### 2. **OBOWIĄZEK ROZBIJANIA FUNKCJI NA ODDZIELNE PLIKI**

**Controllers - Podział odpowiedzialności:**
```php
// ŹŹLE: Jeden wielki controller
class ProductController extends Controller {
    // 50+ metod, 1000+ linii
}

// DOBRZE: Podział na mniejsze controllery
ProductController.php      // CRUD operations (show, store, update, destroy)
ProductSearchController.php // Search & filtering
ProductExportController.php // Export functionality  
ProductSyncController.php   // Synchronization with external APIs
```

**Services - Separacja logiki:**
```php
// ŹŹLE: Jeden gigantyczny service
class ProductService {
    // Wszystka logika w jednym pliku - 2000+ linii
}

// DOBRZE: Podział na wyspecjalizowane services
ProductService.php           // Core product operations
ProductPriceService.php      // Price calculations & management
ProductStockService.php      // Stock management
ProductSyncService.php       // External API synchronization
ProductValidationService.php // Business validation rules
ProductSearchService.php     // Search algorithms
```

**Livewire Components - Podział na mniejsze komponenty:**
```php
// ŹŹLE: Jeden wielki komponent
class ProductManagement extends Component {
    // Lista, formularz, eksport, synchronizacja - wszystko w jednym
}

// DOBRZE: Wyspecjalizowane komponenty
ProductList.php              // Tabela/lista produktów
ProductForm.php              // Formularz tworzenia/edycji
ProductSearch.php            // Wyszukiwarka i filtry
ProductBulkActions.php       // Akcje masowe
ProductSyncStatus.php        // Status synchronizacji
```

### 3. **WZORCE ORGANIZACJI KODU**

**A) Repository + Service Pattern:**
```php
// Repository - dostęp do danych
ProductRepository.php
├── Basic CRUD operations
├── Simple queries
└── Data mapping

// Service - logika biznesowa  
ProductService.php
├── Complex business logic
├── Orchestration
└── Transaction handling
```

**B) Traits dla wspólnej funkcjonalności:**
```php
// Traits dla współdzielonego kodu
app/Traits/
├── HasPrices.php           // Price calculations
├── HasStock.php            // Stock management
├── HasCategories.php       // Category relationships
├── HasSyncStatus.php       // Synchronization status
└── HasBulkActions.php      // Mass operations
```

**C) Events & Listeners dla luźnego sprzężenia:**
```php
// Events
app/Events/
├── ProductCreated.php
├── ProductUpdated.php
├── ProductSynced.php

// Listeners
app/Listeners/
├── SyncProductToPrestashop.php
├── UpdateProductCache.php
├── SendProductNotification.php
```

### 4. **STRUKTURA FOLDERÓW - PODZIAŁ WEDŁUG FUNKCJONALNOŚCI**

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Controllers dla panel admina
│   │   ├── Products/        # Controllers produktów
│   │   ├── Import/          # Controllers importu
│   │   └── Export/          # Controllers eksportu
│   │
│   └── Livewire/
│       ├── Dashboard/       # Komponenty dashboard
│       ├── Products/        # Komponenty produktów
│       ├── Categories/      # Komponenty kategorii
│       └── Admin/           # Komponenty admin panel
│
├── Services/                # Business logic services
│   ├── Products/
│   ├── Import/
│   ├── Export/
│   └── Sync/
│
├── Repositories/            # Data access layer
│   ├── Products/
│   ├── Categories/
│   └── Users/
│
└── Actions/                 # Single-purpose action classes
    ├── Products/
    ├── Import/
    └── Sync/
```

### 5. **PRAKTYCZNE ZASADY IMPLEMENTACJI**

**A) Single Responsibility Principle:**
- Każda klasa = jedna odpowiedzialność
- Każda metoda = jedna operacja
- Jeśli klasa robi "więcej niż jedno" - ROZDZIEL

**B) Nazwy plików odzwierciedlają funkcjonalność:**
```php
// DOBRZE - jasne nazwy
CreateProductAction.php
UpdateProductPricesAction.php  
SyncProductToPrestashopAction.php
ValidateProductDataAction.php

// ŹŹLE - niejasne nazwy
ProductHelper.php
ProductUtil.php
ProductManager.php
```

**C) Dependency Injection zamiast statycznych wywołań:**
```php
// DOBRZE
class ProductController {
    public function __construct(
        private ProductService $productService,
        private ProductValidationService $validationService
    ) {}
}

// ŹŹLE
class ProductController {
    public function store() {
        ProductService::create($data);  // Static call
    }
}
```

### 6. **CHECKLISTY DLA AGENTÓW**

**Przed napisaniem kodu ZAWSZE sprawdź:**
✅ Czy klasa ma mniej niż określony limit linii?
✅ Czy metoda robi tylko jedną rzecz?
✅ Czy można wyodrębnić część logiki do osobnej klasy?
✅ Czy nazwa pliku jasno określa jego funkcjonalność?
✅ Czy kod jest testowalny (nie za bardzo skomplikowany)?

**Po napisaniu kodu ZAWSZE zrób refactor jeśli:**
❌ Klasa ma więcej niż 300 linii
❌ Metoda ma więcej niż 50 linii
❌ Widzisz powtarzający się kod w kilku miejscach
❌ Klasa ma więcej niż 10 publicznych metod
❌ Trudno jest napisać test jednostkowy

### 7. **NARZĘDZIA MONITOROWANIA JAKOŚCI KODU**

```bash
# Sprawdzanie złożoności cyklomatycznej
./vendor/bin/phpmd app text codesize,unusedcode,naming

# Sprawdzanie duplikatów kodu  
./vendor/bin/phpcpd app/

# Analiza metryki kodu
./vendor/bin/phpmetrics --report-html=metrics/ app/
```

**PAMIĘTAJ:** Code quality = Long-term maintainability = Business success ✨