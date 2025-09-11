# ❌ ETAP 11: SYSTEM DOPASOWAŃ I WARIANTÓW

**Szacowany czas realizacji:** 40 godzin  
**Priorytet:** 🟢 ŚREDNI  
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński  
**Wymagane zasoby:** Laravel 12.x, MySQL, Elasticsearch (opcjonalnie), API zewnętrzne  

---

## 🎯 CEL ETAPU

Implementacja zaawansowanego systemu dopasowań części zamiennych dla branży motoryzacyjnej, systemu wariantów produktów oraz inteligentnego systemu rekomendacji. System musi obsługiwać złożone relacje między produktami, pojazdami i ich kompatybilnością, oferując klientom dokładne dopasowanie części do ich pojazdów.

### Kluczowe rezultaty:
- ✅ System dopasowań części zamiennych (Oryginał ↔ Zamiennik)
- ✅ Katalog pojazdów z hierarhią (Marka → Model → Generacja → Silnik)
- ✅ System kompatybilności części z pojazdami
- ✅ Warianty produktów z dedykowanymi cenami i stanami
- ✅ Inteligentny system rekomendacji i cross-selling
- ✅ Wyszukiwarka części po numerze VIN i rejestracji
- ✅ System grup produktowych i zestawów
- ✅ API dla zewnętrznych katalogów części (TecDoc, etc.)
- ✅ Panel zarządzania dopasowaniami dla ekspertów

---

## ❌ 11.1 ANALIZA I ARCHITEKTURA SYSTEMU DOPASOWAŃ

### ❌ 11.1.1 Wymagania funkcjonalne systemu
#### ❌ 11.1.1.1 System części zamiennych
- ❌ 11.1.1.1.1 Relacje Oryginał → Zamienniki (1:N)
- ❌ 11.1.1.1.2 Relacje krzyżowe Zamiennik ↔ Zamiennik (N:N)
- ❌ 11.1.1.1.3 Poziomy kompatybilności (identyczny, kompatybilny, częściowy)
- ❌ 11.1.1.1.4 Grupy funkcjonalne części (hamulce, silnik, zawieszenie)
- ❌ 11.1.1.1.5 System priorytetów dopasowań (preferowane marki)

#### ❌ 11.1.1.2 Katalog pojazdów
- ❌ 11.1.1.2.1 Hierarchia: Marka → Model → Generacja → Wersja silnikowa
- ❌ 11.1.1.2.2 Kody VIN i ich dekodowanie
- ❌ 11.1.1.2.3 Okresy produkcji i zmiany faceliftów
- ❌ 11.1.1.2.4 Specyfikacje techniczne (moc, pojemność, paliwo)
- ❌ 11.1.1.2.5 Synonime i nazwy handlowe

#### ❌ 11.1.1.3 System kompatybilności
- ❌ 11.1.1.3.1 Macierze dopasowań Część × Pojazd
- ❌ 11.1.1.3.2 Ograniczenia czasowe (od/do rocznika)
- ❌ 11.1.1.3.3 Ograniczenia według specyfikacji (moc, typ silnika)
- ❌ 11.1.1.3.4 Wyjątki i specjalne wymagania
- ❌ 11.1.1.3.5 Poziomy pewności dopasowania (100%, 95%, 80%)

### ❌ 11.1.2 Wymagania systemu wariantów
#### ❌ 11.1.2.1 Warianty produktów
- ❌ 11.1.2.1.1 Warianty kolorystyczne (lakiery, tkaniny)
- ❌ 11.1.2.1.2 Warianty rozmiarowe (średnice, długości)
- ❌ 11.1.2.1.3 Warianty specyfikacji (lewy/prawy, przód/tył)
- ❌ 11.1.2.1.4 Warianty jakościowe (OEM, OES, aftermarket)
- ❌ 11.1.2.1.5 Dedykowane SKU, ceny i stany dla wariantów

#### ❌ 11.1.2.2 System grupowania produktów
- ❌ 11.1.2.2.1 Zestawy części (kompletne hamulce, kit rozrządu)
- ❌ 11.1.2.2.2 Akcesoria i części dodatkowe
- ❌ 11.1.2.2.3 Części serwisowe i eksploatacyjne
- ❌ 11.1.2.2.4 Dynamiczne zestawy oparte na dopasowaniach
- ❌ 11.1.2.2.5 Pakiety promocyjne i bundle offers

### ❌ 11.1.3 Integracje zewnętrzne
#### ❌ 11.1.3.1 TecDoc Integration
- ❌ 11.1.3.1.1 Pobieranie danych pojazdów z TecDoc API
- ❌ 11.1.3.1.2 Synchronizacja katalogu części
- ❌ 11.1.3.1.3 Mapowanie dopasowań TecDoc → PPM
- ❌ 11.1.3.1.4 Aktualizacje incrementalne
- ❌ 11.1.3.1.5 Walidacja spójności danych

#### ❌ 11.1.3.2 VIN Decoder API
- ❌ 11.1.3.2.1 Dekodowanie numerów VIN na specyfikacje
- ❌ 11.1.3.2.2 Pobieranie danych z baz CEPiK (polskie rejestracje)
- ❌ 11.1.3.2.3 Integracja z serwisami międzynarodowymi
- ❌ 11.1.3.2.4 Cache i optymalizacja zapytań
- ❌ 11.1.3.2.5 Obsługa błędów i fallback mechanisms

---

## ❌ 11.2 MODELE I MIGRACJE SYSTEMU DOPASOWAŃ

### ❌ 11.2.1 Tabele katalogu pojazdów
#### ❌ 11.2.1.1 Tabela vehicle_brands (marki pojazdów)
```sql
CREATE TABLE vehicle_brands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NULL,
    country VARCHAR(2) NULL, -- ISO country code
    
    -- Brand details
    logo_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    founded_year YEAR NULL,
    
    -- Status and sorting
    is_active BOOLEAN DEFAULT TRUE,
    is_popular BOOLEAN DEFAULT FALSE, -- For quick access
    display_order INT DEFAULT 0,
    
    -- External IDs
    tecdoc_brand_id INT NULL,
    external_ids JSON NULL, -- Other catalog IDs
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_name (name),
    INDEX idx_active_popular (is_active, is_popular),
    INDEX idx_display_order (display_order),
    INDEX idx_tecdoc (tecdoc_brand_id)
);
```

#### ❌ 11.2.1.2 Tabela vehicle_models (modele pojazdów)
```sql
CREATE TABLE vehicle_models (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    brand_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NULL,
    
    -- Model details
    body_type ENUM('sedan', 'hatchback', 'wagon', 'suv', 'coupe', 'convertible', 'van', 'truck', 'motorcycle', 'other') NULL,
    segment VARCHAR(10) NULL, -- A, B, C, D, E, F, etc.
    
    -- Production period
    production_start YEAR NULL,
    production_end YEAR NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_popular BOOLEAN DEFAULT FALSE,
    
    -- External IDs
    tecdoc_model_id INT NULL,
    external_ids JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (brand_id) REFERENCES vehicle_brands(id) ON DELETE CASCADE,
    UNIQUE KEY unique_brand_model (brand_id, name),
    INDEX idx_brand_active (brand_id, is_active),
    INDEX idx_body_type (body_type),
    INDEX idx_production_period (production_start, production_end),
    INDEX idx_tecdoc (tecdoc_model_id)
);
```

#### ❌ 11.2.1.3 Tabela vehicle_generations (generacje/wersje)
```sql
CREATE TABLE vehicle_generations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    model_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(100) NOT NULL, -- e.g., "Golf VII", "E90", "W204"
    generation_code VARCHAR(50) NULL, -- Internal manufacturer code
    
    -- Generation period
    generation_start YEAR NOT NULL,
    generation_end YEAR NULL,
    
    -- Facelift information
    is_facelift BOOLEAN DEFAULT FALSE,
    facelift_year YEAR NULL,
    
    -- Technical specifications
    platform VARCHAR(100) NULL, -- e.g., "MQB", "CLAR", "MLB Evo"
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- External IDs
    tecdoc_generation_id INT NULL,
    external_ids JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    INDEX idx_model_generation (model_id, generation_start),
    INDEX idx_period (generation_start, generation_end),
    INDEX idx_active (is_active),
    INDEX idx_tecdoc (tecdoc_generation_id)
);
```

#### ❌ 11.2.1.4 Tabela vehicle_engines (silniki)
```sql
CREATE TABLE vehicle_engines (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    generation_id BIGINT UNSIGNED NOT NULL,
    
    -- Engine identification
    engine_code VARCHAR(50) NOT NULL, -- e.g., "2.0 TDI", "M57D30", "B48B20"
    internal_code VARCHAR(50) NULL, -- Manufacturer internal code
    
    -- Engine specifications
    displacement_cc INT NULL, -- Engine displacement in cc
    cylinder_count TINYINT NULL,
    fuel_type ENUM('petrol', 'diesel', 'hybrid', 'electric', 'lpg', 'cng', 'other') NOT NULL,
    aspiration ENUM('naturally_aspirated', 'turbo', 'supercharged', 'twin_turbo') NULL,
    
    -- Power specifications
    power_hp INT NULL, -- Horsepower
    power_kw INT NULL, -- Kilowatts
    torque_nm INT NULL, -- Torque in Nm
    
    -- Production period
    production_start YEAR NULL,
    production_end YEAR NULL,
    
    -- Additional specifications
    valve_configuration VARCHAR(20) NULL, -- e.g., "DOHC", "SOHC"
    emission_standard VARCHAR(20) NULL, -- e.g., "Euro 6"
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- External IDs
    tecdoc_engine_id INT NULL,
    external_ids JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (generation_id) REFERENCES vehicle_generations(id) ON DELETE CASCADE,
    INDEX idx_generation_engine (generation_id, engine_code),
    INDEX idx_fuel_type (fuel_type),
    INDEX idx_displacement (displacement_cc),
    INDEX idx_power (power_hp, power_kw),
    INDEX idx_production (production_start, production_end),
    INDEX idx_tecdoc (tecdoc_engine_id)
);
```

### ❌ 11.2.2 Tabele systemu dopasowań
#### ❌ 11.2.2.1 Tabela product_alternatives (części zamienne)
```sql
CREATE TABLE product_alternatives (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    original_product_id BIGINT UNSIGNED NOT NULL, -- Original/OEM part
    alternative_product_id BIGINT UNSIGNED NOT NULL, -- Alternative/aftermarket part
    
    -- Compatibility level
    compatibility_level ENUM('identical', 'compatible', 'partial', 'conditional') DEFAULT 'compatible',
    compatibility_percentage TINYINT UNSIGNED DEFAULT 100, -- 0-100%
    
    -- Alternative type
    alternative_type ENUM('oem', 'oes', 'aftermarket', 'remanufactured', 'universal') DEFAULT 'aftermarket',
    
    -- Quality and priority
    quality_rating TINYINT UNSIGNED NULL, -- 1-5 stars
    priority_score INT DEFAULT 0, -- Higher = more preferred
    
    -- Conditions and notes
    conditions TEXT NULL, -- Special conditions for compatibility
    notes TEXT NULL, -- Internal notes
    
    -- Validation
    validated_by BIGINT UNSIGNED NULL, -- User who validated this match
    validated_at TIMESTAMP NULL,
    validation_status ENUM('pending', 'approved', 'rejected', 'needs_review') DEFAULT 'pending',
    
    -- Usage statistics
    view_count INT UNSIGNED DEFAULT 0,
    conversion_count INT UNSIGNED DEFAULT 0, -- How many times this alternative was chosen
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (original_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (alternative_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_alternative (original_product_id, alternative_product_id),
    INDEX idx_original (original_product_id, compatibility_level),
    INDEX idx_alternative (alternative_product_id),
    INDEX idx_compatibility (compatibility_level, compatibility_percentage),
    INDEX idx_priority (priority_score, quality_rating),
    INDEX idx_validation (validation_status, validated_at)
);
```

#### ❌ 11.2.2.2 Tabela vehicle_part_compatibility (kompatybilność części)
```sql
CREATE TABLE vehicle_part_compatibility (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- Vehicle specification (can match at different levels)
    brand_id BIGINT UNSIGNED NULL, -- If compatible with all brand models
    model_id BIGINT UNSIGNED NULL, -- If compatible with all model generations
    generation_id BIGINT UNSIGNED NULL, -- If compatible with all generation engines
    engine_id BIGINT UNSIGNED NULL, -- Specific engine compatibility
    
    -- Time constraints
    compatible_from YEAR NULL, -- Compatible from this year
    compatible_to YEAR NULL, -- Compatible until this year
    
    -- Additional constraints
    constraints JSON NULL, -- Additional conditions (e.g., specific options, markets)
    
    -- Mounting position (for positional parts)
    position ENUM('front', 'rear', 'left', 'right', 'front_left', 'front_right', 'rear_left', 'rear_right', 'all') NULL,
    
    -- Compatibility confidence
    confidence_level ENUM('confirmed', 'probable', 'possible', 'uncertain') DEFAULT 'probable',
    
    -- Source of information
    source ENUM('manual', 'tecdoc', 'manufacturer', 'customer_feedback', 'expert') DEFAULT 'manual',
    source_reference VARCHAR(255) NULL, -- External reference/catalog number
    
    -- Validation
    validated_by BIGINT UNSIGNED NULL,
    validated_at TIMESTAMP NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES vehicle_brands(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    FOREIGN KEY (generation_id) REFERENCES vehicle_generations(id) ON DELETE CASCADE,
    FOREIGN KEY (engine_id) REFERENCES vehicle_engines(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for fast lookups
    INDEX idx_product (product_id, is_active),
    INDEX idx_brand (brand_id, is_active),
    INDEX idx_model (model_id, is_active),
    INDEX idx_generation (generation_id, is_active),
    INDEX idx_engine (engine_id, is_active),
    INDEX idx_vehicle_hierarchy (brand_id, model_id, generation_id, engine_id),
    INDEX idx_compatibility (confidence_level, compatible_from, compatible_to),
    INDEX idx_source (source, validated_at)
);
```

### ❌ 11.2.3 Tabele wariantów produktów
#### ❌ 11.2.3.1 Tabela product_variants (warianty)
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_product_id BIGINT UNSIGNED NOT NULL,
    
    -- Variant identification
    variant_sku VARCHAR(255) NOT NULL UNIQUE,
    variant_name VARCHAR(255) NULL, -- e.g., "Lewy", "Czerwony", "XL"
    
    -- Variant attributes (structured)
    variant_attributes JSON NOT NULL, -- {"color": "red", "size": "L", "position": "left"}
    
    -- Individual properties
    individual_pricing BOOLEAN DEFAULT FALSE, -- Has separate pricing
    individual_stock BOOLEAN DEFAULT TRUE, -- Has separate stock
    individual_images BOOLEAN DEFAULT FALSE, -- Has separate images
    
    -- Pricing (if different from parent)
    price_adjustment_type ENUM('none', 'fixed', 'percentage') DEFAULT 'none',
    price_adjustment_value DECIMAL(10,4) NULL, -- +10.00 or +5% 
    
    -- Physical properties (if different from parent)
    weight_g INT NULL,
    dimensions JSON NULL, -- {width, height, depth, unit}
    
    -- Availability
    is_available BOOLEAN DEFAULT TRUE,
    availability_date DATE NULL, -- When will be available if not now
    
    -- Ordering
    display_order INT DEFAULT 0,
    
    -- Stock tracking
    track_stock BOOLEAN DEFAULT TRUE,
    low_stock_threshold INT DEFAULT 5,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_product_id, is_available),
    INDEX idx_sku (variant_sku),
    INDEX idx_availability (is_available, availability_date),
    INDEX idx_display_order (display_order)
);
```

#### ❌ 11.2.3.2 Tabela product_bundles (zestawy produktów)
```sql
CREATE TABLE product_bundles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- Bundle identification
    bundle_sku VARCHAR(255) NOT NULL UNIQUE,
    bundle_name VARCHAR(255) NOT NULL,
    bundle_description TEXT NULL,
    
    -- Bundle type
    bundle_type ENUM('fixed', 'flexible', 'recommendation', 'accessory') DEFAULT 'fixed',
    
    -- Pricing strategy
    pricing_strategy ENUM('sum', 'discount_percentage', 'discount_fixed', 'custom') DEFAULT 'sum',
    discount_value DECIMAL(10,4) NULL, -- Discount amount or percentage
    
    -- Bundle constraints
    min_items INT DEFAULT 1,
    max_items INT NULL,
    
    -- Availability rules
    all_items_required BOOLEAN DEFAULT TRUE, -- All items must be available
    
    -- Auto-generation rules (for dynamic bundles)
    auto_generated BOOLEAN DEFAULT FALSE,
    generation_rules JSON NULL, -- Rules for automatic bundle creation
    
    -- Display settings
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_bundle_sku (bundle_sku),
    INDEX idx_bundle_type (bundle_type, is_active),
    INDEX idx_auto_generated (auto_generated),
    INDEX idx_featured (is_featured, display_order)
);
```

#### ❌ 11.2.3.3 Tabela product_bundle_items (elementy zestawów)
```sql
CREATE TABLE product_bundle_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bundle_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- Item configuration
    quantity INT UNSIGNED DEFAULT 1,
    is_required BOOLEAN DEFAULT TRUE,
    is_default_selected BOOLEAN DEFAULT TRUE,
    
    -- Item constraints
    min_quantity INT UNSIGNED DEFAULT 1,
    max_quantity INT UNSIGNED NULL,
    
    -- Pricing for this item in bundle
    individual_price DECIMAL(10,4) NULL, -- Override price for this bundle
    discount_percentage DECIMAL(5,2) NULL, -- Discount % for this item in bundle
    
    -- Display
    display_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_bundle_product (bundle_id, product_id),
    INDEX idx_bundle (bundle_id, display_order),
    INDEX idx_required (is_required, is_default_selected)
);
```

---

## ❌ 11.3 VEHICLE SERVICE LAYER

### ❌ 11.3.1 VehicleService - główny serwis pojazdów
#### ❌ 11.3.1.1 Klasa VehicleService
```php
<?php
namespace App\Services\Vehicle;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleGeneration;
use App\Models\VehicleEngine;
use App\Services\Vehicle\VINDecoder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class VehicleService
{
    protected VINDecoder $vinDecoder;
    
    public function __construct(VINDecoder $vinDecoder)
    {
        $this->vinDecoder = $vinDecoder;
    }
    
    public function searchVehicles(string $query): Collection
    {
        $query = trim(strtolower($query));
        
        if (strlen($query) < 2) {
            return collect();
        }
        
        $cacheKey = "vehicle_search_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(2), function () use ($query) {
            return $this->performVehicleSearch($query);
        });
    }
    
    protected function performVehicleSearch(string $query): Collection
    {
        $results = collect();
        
        // Search brands
        $brands = VehicleBrand::where('name', 'LIKE', "%{$query}%")
            ->where('is_active', true)
            ->orderBy('is_popular', 'desc')
            ->limit(10)
            ->get();
            
        foreach ($brands as $brand) {
            $results->push([
                'type' => 'brand',
                'id' => $brand->id,
                'name' => $brand->name,
                'full_name' => $brand->full_name,
                'logo_url' => $brand->logo_url
            ]);
        }
        
        // Search models
        $models = VehicleModel::with('brand')
            ->where('name', 'LIKE', "%{$query}%")
            ->where('is_active', true)
            ->orderBy('is_popular', 'desc')
            ->limit(15)
            ->get();
            
        foreach ($models as $model) {
            $results->push([
                'type' => 'model',
                'id' => $model->id,
                'name' => $model->brand->name . ' ' . $model->name,
                'brand_name' => $model->brand->name,
                'model_name' => $model->name,
                'production_period' => $this->formatProductionPeriod($model)
            ]);
        }
        
        // Search generations
        $generations = VehicleGeneration::with(['model.brand'])
            ->where('name', 'LIKE', "%{$query}%")
            ->where('is_active', true)
            ->limit(10)
            ->get();
            
        foreach ($generations as $generation) {
            $results->push([
                'type' => 'generation',
                'id' => $generation->id,
                'name' => $generation->model->brand->name . ' ' . $generation->model->name . ' ' . $generation->name,
                'brand_name' => $generation->model->brand->name,
                'model_name' => $generation->model->name,
                'generation_name' => $generation->name,
                'period' => $generation->generation_start . '-' . ($generation->generation_end ?? 'now')
            ]);
        }
        
        // Search engines
        $engines = VehicleEngine::with(['generation.model.brand'])
            ->where('engine_code', 'LIKE', "%{$query}%")
            ->where('is_active', true)
            ->limit(10)
            ->get();
            
        foreach ($engines as $engine) {
            $results->push([
                'type' => 'engine',
                'id' => $engine->id,
                'name' => $engine->generation->model->brand->name . ' ' . 
                         $engine->generation->model->name . ' ' . 
                         $engine->engine_code,
                'brand_name' => $engine->generation->model->brand->name,
                'model_name' => $engine->generation->model->name,
                'engine_code' => $engine->engine_code,
                'specifications' => $this->formatEngineSpecs($engine)
            ]);
        }
        
        return $results->sortBy('name')->values();
    }
    
    public function getVehicleHierarchy(?int $brandId = null, ?int $modelId = null, ?int $generationId = null): array
    {
        $result = [];
        
        // Get brands
        if (!$brandId) {
            $result['brands'] = VehicleBrand::where('is_active', true)
                ->orderBy('is_popular', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'full_name', 'logo_url', 'is_popular']);
        } else {
            $result['selected_brand'] = VehicleBrand::find($brandId);
            
            // Get models for selected brand
            if (!$modelId) {
                $result['models'] = VehicleModel::where('brand_id', $brandId)
                    ->where('is_active', true)
                    ->orderBy('is_popular', 'desc')
                    ->orderBy('name')
                    ->get(['id', 'name', 'full_name', 'body_type', 'production_start', 'production_end']);
            } else {
                $result['selected_model'] = VehicleModel::find($modelId);
                
                // Get generations for selected model
                if (!$generationId) {
                    $result['generations'] = VehicleGeneration::where('model_id', $modelId)
                        ->where('is_active', true)
                        ->orderBy('generation_start', 'desc')
                        ->get(['id', 'name', 'generation_code', 'generation_start', 'generation_end', 'platform']);
                } else {
                    $result['selected_generation'] = VehicleGeneration::find($generationId);
                    
                    // Get engines for selected generation
                    $result['engines'] = VehicleEngine::where('generation_id', $generationId)
                        ->where('is_active', true)
                        ->orderBy('fuel_type')
                        ->orderBy('displacement_cc')
                        ->get(['id', 'engine_code', 'displacement_cc', 'fuel_type', 'power_hp', 'power_kw', 'production_start', 'production_end']);
                }
            }
        }
        
        return $result;
    }
    
    public function decodeVIN(string $vin): ?array
    {
        if (strlen($vin) !== 17) {
            return null;
        }
        
        $cacheKey = "vin_decode_" . $vin;
        
        return Cache::remember($cacheKey, now()->addDays(30), function () use ($vin) {
            return $this->vinDecoder->decode($vin);
        });
    }
    
    public function findVehicleByVIN(string $vin): ?VehicleEngine
    {
        $decodedData = $this->decodeVIN($vin);
        
        if (!$decodedData) {
            return null;
        }
        
        // Try to match decoded data with our vehicle database
        $brand = VehicleBrand::where('name', 'LIKE', '%' . $decodedData['brand'] . '%')->first();
        
        if (!$brand) {
            return null;
        }
        
        $model = VehicleModel::where('brand_id', $brand->id)
            ->where('name', 'LIKE', '%' . $decodedData['model'] . '%')
            ->first();
            
        if (!$model) {
            return null;
        }
        
        // Try to match generation by year
        $generation = VehicleGeneration::where('model_id', $model->id)
            ->where('generation_start', '<=', $decodedData['year'])
            ->where(function ($query) use ($decodedData) {
                $query->whereNull('generation_end')
                      ->orWhere('generation_end', '>=', $decodedData['year']);
            })
            ->first();
            
        if (!$generation) {
            return null;
        }
        
        // Try to match engine
        $engine = VehicleEngine::where('generation_id', $generation->id);
        
        if (!empty($decodedData['engine_code'])) {
            $engine->where('engine_code', 'LIKE', '%' . $decodedData['engine_code'] . '%');
        }
        
        if (!empty($decodedData['displacement'])) {
            $engine->where('displacement_cc', $decodedData['displacement']);
        }
        
        if (!empty($decodedData['fuel_type'])) {
            $engine->where('fuel_type', $decodedData['fuel_type']);
        }
        
        return $engine->first();
    }
    
    public function getPopularVehicles(int $limit = 20): Collection
    {
        return Cache::remember('popular_vehicles', now()->addHours(6), function () use ($limit) {
            // Get popular vehicles based on part searches/sales
            $popularEngines = VehicleEngine::with(['generation.model.brand'])
                ->select('vehicle_engines.*')
                ->join('vehicle_part_compatibility', 'vehicle_engines.id', '=', 'vehicle_part_compatibility.engine_id')
                ->selectRaw('COUNT(vehicle_part_compatibility.id) as compatibility_count')
                ->groupBy('vehicle_engines.id')
                ->orderBy('compatibility_count', 'desc')
                ->limit($limit)
                ->get();
                
            return $popularEngines->map(function ($engine) {
                return [
                    'id' => $engine->id,
                    'name' => $engine->generation->model->brand->name . ' ' . 
                             $engine->generation->model->name . ' ' . 
                             $engine->engine_code,
                    'brand' => $engine->generation->model->brand->name,
                    'model' => $engine->generation->model->name,
                    'generation' => $engine->generation->name,
                    'engine' => $engine->engine_code,
                    'period' => $engine->production_start . '-' . ($engine->production_end ?? 'now'),
                    'parts_count' => $engine->compatibility_count ?? 0
                ];
            });
        });
    }
    
    protected function formatProductionPeriod(VehicleModel $model): string
    {
        $start = $model->production_start;
        $end = $model->production_end ?? 'now';
        
        return "{$start}-{$end}";
    }
    
    protected function formatEngineSpecs(VehicleEngine $engine): string
    {
        $specs = [];
        
        if ($engine->displacement_cc) {
            $specs[] = number_format($engine->displacement_cc / 1000, 1) . 'L';
        }
        
        if ($engine->power_hp) {
            $specs[] = $engine->power_hp . 'HP';
        }
        
        if ($engine->fuel_type) {
            $specs[] = ucfirst($engine->fuel_type);
        }
        
        return implode(' • ', $specs);
    }
}
```

---

## ❌ 11.4 PRODUCT MATCHING SERVICE

### ❌ 11.4.1 ProductMatchingService
#### ❌ 11.4.1.1 Serwis dopasowań części
```php
<?php
namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductAlternative;
use App\Models\VehiclePartCompatibility;
use App\Models\VehicleEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductMatchingService
{
    public function findAlternatives(Product $product, array $options = []): Collection
    {
        $cacheKey = "alternatives_{$product->id}_" . md5(serialize($options));
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($product, $options) {
            return $this->performAlternativeSearch($product, $options);
        });
    }
    
    protected function performAlternativeSearch(Product $product, array $options): Collection
    {
        $query = Product::select('products.*', 'product_alternatives.*')
            ->join('product_alternatives', function ($join) use ($product) {
                $join->on('products.id', '=', 'product_alternatives.alternative_product_id')
                     ->where('product_alternatives.original_product_id', $product->id)
                     ->where('product_alternatives.is_active', true);
            })
            ->where('products.is_active', true);
            
        // Filter by compatibility level
        if (!empty($options['min_compatibility'])) {
            $compatibilityLevels = $this->getCompatibilityHierarchy();
            $minLevel = $compatibilityLevels[$options['min_compatibility']] ?? 0;
            
            $query->whereRaw('
                CASE 
                    WHEN product_alternatives.compatibility_level = "identical" THEN 4
                    WHEN product_alternatives.compatibility_level = "compatible" THEN 3
                    WHEN product_alternatives.compatibility_level = "partial" THEN 2
                    ELSE 1
                END >= ?
            ', [$minLevel]);
        }
        
        // Filter by alternative type
        if (!empty($options['alternative_types'])) {
            $query->whereIn('product_alternatives.alternative_type', $options['alternative_types']);
        }
        
        // Filter by price range
        if (!empty($options['max_price_difference'])) {
            $basePrice = $product->prices->where('price_group', 'detaliczna')->first()?->price ?? 0;
            
            $query->whereHas('prices', function ($priceQuery) use ($basePrice, $options) {
                $maxPrice = $basePrice * (1 + $options['max_price_difference'] / 100);
                $priceQuery->where('price_group', 'detaliczna')
                          ->where('price', '<=', $maxPrice);
            });
        }
        
        // Order by priority and compatibility
        $query->orderByDesc('product_alternatives.priority_score')
              ->orderByDesc('product_alternatives.compatibility_percentage')
              ->orderBy('product_alternatives.alternative_type')
              ->orderBy('products.name');
        
        $alternatives = $query->limit($options['limit'] ?? 20)->get();
        
        // Enrich with additional data
        return $alternatives->map(function ($alternative) use ($product) {
            return [
                'product' => $alternative,
                'compatibility_level' => $alternative->compatibility_level,
                'compatibility_percentage' => $alternative->compatibility_percentage,
                'alternative_type' => $alternative->alternative_type,
                'quality_rating' => $alternative->quality_rating,
                'price_difference' => $this->calculatePriceDifference($product, $alternative),
                'availability' => $this->checkAvailability($alternative),
                'recommendation_score' => $this->calculateRecommendationScore($alternative, $product)
            ];
        });
    }
    
    public function findCompatibleParts(VehicleEngine $engine, ?string $category = null): Collection
    {
        $cacheKey = "compatible_parts_{$engine->id}" . ($category ? "_{$category}" : '');
        
        return Cache::remember($cacheKey, now()->addHours(2), function () use ($engine, $category) {
            return $this->performCompatibilitySearch($engine, $category);
        });
    }
    
    protected function performCompatibilitySearch(VehicleEngine $engine, ?string $category): Collection
    {
        $query = Product::select('products.*', 'vpc.confidence_level', 'vpc.position', 'vpc.constraints')
            ->join('vehicle_part_compatibility as vpc', 'products.id', '=', 'vpc.product_id')
            ->where('vpc.is_active', true)
            ->where('products.is_active', true);
            
        // Match by engine, generation, model, or brand (hierarchical matching)
        $query->where(function ($q) use ($engine) {
            $q->where('vpc.engine_id', $engine->id) // Direct engine match (most specific)
              ->orWhere('vpc.generation_id', $engine->generation_id) // Generation match
              ->orWhere('vpc.model_id', $engine->generation->model_id) // Model match
              ->orWhere('vpc.brand_id', $engine->generation->model->brand_id); // Brand match (least specific)
        });
        
        // Filter by production year compatibility
        $engineYear = $engine->production_start;
        if ($engineYear) {
            $query->where(function ($q) use ($engineYear) {
                $q->whereNull('vpc.compatible_from')
                  ->orWhere('vpc.compatible_from', '<=', $engineYear);
            })->where(function ($q) use ($engineYear) {
                $q->whereNull('vpc.compatible_to')
                  ->orWhere('vpc.compatible_to', '>=', $engineYear);
            });
        }
        
        // Filter by category if specified
        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', 'LIKE', "%{$category}%")
                  ->orWhere('slug', $category);
            });
        }
        
        // Order by specificity and confidence
        $query->orderByRaw('
            CASE vpc.confidence_level
                WHEN "confirmed" THEN 4
                WHEN "probable" THEN 3
                WHEN "possible" THEN 2
                ELSE 1
            END DESC
        ')
        ->orderByRaw('
            CASE 
                WHEN vpc.engine_id IS NOT NULL THEN 4
                WHEN vpc.generation_id IS NOT NULL THEN 3
                WHEN vpc.model_id IS NOT NULL THEN 2
                ELSE 1
            END DESC
        ')
        ->orderBy('products.name');
        
        return $query->get()->map(function ($product) {
            return [
                'product' => $product,
                'confidence_level' => $product->confidence_level,
                'position' => $product->position,
                'constraints' => json_decode($product->constraints, true),
                'compatibility_specificity' => $this->calculateSpecificity($product)
            ];
        });
    }
    
    public function suggestCrossSelling(Product $product, ?VehicleEngine $engine = null): Collection
    {
        // Find products that are commonly bought together
        $relatedProducts = collect();
        
        // 1. Find complementary parts for the same vehicle
        if ($engine) {
            $complementaryParts = $this->findComplementaryParts($product, $engine);
            $relatedProducts = $relatedProducts->concat($complementaryParts);
        }
        
        // 2. Find parts in the same functional group
        $sameCategoryParts = $this->findSameCategoryAlternatives($product);
        $relatedProducts = $relatedProducts->concat($sameCategoryParts);
        
        // 3. Find accessories and consumables
        $accessories = $this->findAccessories($product);
        $relatedProducts = $relatedProducts->concat($accessories);
        
        // 4. Find frequently bought together (from order history)
        $frequentlyBought = $this->findFrequentlyBoughtTogether($product);
        $relatedProducts = $relatedProducts->concat($frequentlyBought);
        
        // Remove duplicates and original product
        $relatedProducts = $relatedProducts->unique('id')
            ->reject(function ($item) use ($product) {
                return $item['product']->id === $product->id;
            });
            
        // Score and sort recommendations
        return $relatedProducts->map(function ($item) use ($product, $engine) {
            $item['recommendation_score'] = $this->calculateCrossSellingScore($item, $product, $engine);
            return $item;
        })->sortByDesc('recommendation_score')->take(10);
    }
    
    public function createAlternativeMapping(int $originalProductId, int $alternativeProductId, array $data): ProductAlternative
    {
        return ProductAlternative::create([
            'original_product_id' => $originalProductId,
            'alternative_product_id' => $alternativeProductId,
            'compatibility_level' => $data['compatibility_level'] ?? 'compatible',
            'compatibility_percentage' => $data['compatibility_percentage'] ?? 100,
            'alternative_type' => $data['alternative_type'] ?? 'aftermarket',
            'quality_rating' => $data['quality_rating'] ?? null,
            'priority_score' => $data['priority_score'] ?? 0,
            'conditions' => $data['conditions'] ?? null,
            'notes' => $data['notes'] ?? null,
            'validation_status' => 'needs_review'
        ]);
    }
    
    public function validateAlternative(ProductAlternative $alternative, bool $approved, ?string $notes = null): bool
    {
        $alternative->update([
            'validation_status' => $approved ? 'approved' : 'rejected',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
            'notes' => $notes ?? $alternative->notes
        ]);
        
        return true;
    }
    
    protected function getCompatibilityHierarchy(): array
    {
        return [
            'identical' => 4,
            'compatible' => 3,
            'partial' => 2,
            'conditional' => 1
        ];
    }
    
    protected function calculatePriceDifference(Product $original, Product $alternative): float
    {
        $originalPrice = $original->prices->where('price_group', 'detaliczna')->first()?->price ?? 0;
        $alternativePrice = $alternative->prices->where('price_group', 'detaliczna')->first()?->price ?? 0;
        
        if ($originalPrice == 0) {
            return 0;
        }
        
        return (($alternativePrice - $originalPrice) / $originalPrice) * 100;
    }
    
    protected function checkAvailability(Product $product): array
    {
        $totalStock = $product->stock->sum('quantity');
        
        return [
            'in_stock' => $totalStock > 0,
            'quantity' => $totalStock,
            'estimated_delivery' => $totalStock > 0 ? 'immediate' : '7-14 days'
        ];
    }
    
    protected function calculateRecommendationScore(Product $alternative, Product $original): int
    {
        $score = 0;
        
        // Compatibility score
        $score += match($alternative->compatibility_level) {
            'identical' => 40,
            'compatible' => 30,
            'partial' => 20,
            'conditional' => 10,
            default => 0
        };
        
        // Quality rating score
        if ($alternative->quality_rating) {
            $score += $alternative->quality_rating * 5; // 5-25 points
        }
        
        // Priority score
        $score += min($alternative->priority_score / 10, 20); // Max 20 points
        
        // Availability score
        $availability = $this->checkAvailability($alternative);
        $score += $availability['in_stock'] ? 15 : 5;
        
        return $score;
    }
    
    protected function calculateSpecificity(Product $product): string
    {
        if ($product->engine_id) return 'engine_specific';
        if ($product->generation_id) return 'generation_specific';  
        if ($product->model_id) return 'model_specific';
        return 'brand_generic';
    }
    
    protected function findComplementaryParts(Product $product, VehicleEngine $engine): Collection
    {
        // Find parts that are typically replaced together
        $complementaryCategories = $this->getComplementaryCategories($product->category_id);
        
        if (empty($complementaryCategories)) {
            return collect();
        }
        
        return $this->findCompatibleParts($engine)
            ->whereIn('product.category_id', $complementaryCategories)
            ->map(function ($item) {
                $item['relationship_type'] = 'complementary';
                return $item;
            });
    }
    
    protected function findSameCategoryAlternatives(Product $product): Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(5)
            ->get()
            ->map(function ($relatedProduct) {
                return [
                    'product' => $relatedProduct,
                    'relationship_type' => 'same_category'
                ];
            });
    }
    
    protected function findAccessories(Product $product): Collection
    {
        // Find accessories based on product attributes or category
        return collect(); // Simplified for this example
    }
    
    protected function findFrequentlyBoughtTogether(Product $product): Collection
    {
        // Analyze order history to find frequently bought combinations
        return collect(); // Would require order analysis
    }
    
    protected function calculateCrossSellingScore(array $item, Product $original, ?VehicleEngine $engine): int
    {
        $score = 0;
        
        // Relationship type score
        $score += match($item['relationship_type'] ?? '') {
            'complementary' => 30,
            'frequently_bought' => 25,
            'accessory' => 20,
            'same_category' => 15,
            default => 10
        };
        
        // Vehicle compatibility score
        if ($engine && isset($item['confidence_level'])) {
            $score += match($item['confidence_level']) {
                'confirmed' => 25,
                'probable' => 20,
                'possible' => 10,
                default => 5
            };
        }
        
        // Price appropriateness (not too expensive)
        $priceDiff = $this->calculatePriceDifference($original, $item['product']);
        if ($priceDiff < 50) { // Less than 50% more expensive
            $score += 15;
        } elseif ($priceDiff < 100) {
            $score += 10;
        }
        
        // Availability
        $availability = $this->checkAvailability($item['product']);
        $score += $availability['in_stock'] ? 10 : 5;
        
        return $score;
    }
    
    protected function getComplementaryCategories(int $categoryId): array
    {
        // Define complementary categories (could be database-driven)
        $complementaryMap = [
            // Example: brake pads -> brake discs, brake fluid
            1 => [2, 3], // If category 1 (brake pads), suggest categories 2 (discs) and 3 (fluid)
            // Add more mappings based on automotive parts relationships
        ];
        
        return $complementaryMap[$categoryId] ?? [];
    }
}
```

---

## ❌ 11.5 VARIANT SERVICE LAYER

### ❌ 11.5.1 ProductVariantService
#### ❌ 11.5.1.1 Zarządzanie wariantami produktów
```php
<?php
namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function createVariant(Product $parentProduct, array $variantData): ProductVariant
    {
        try {
            DB::beginTransaction();
            
            $variantSku = $this->generateVariantSku($parentProduct, $variantData['variant_attributes']);
            
            $variant = ProductVariant::create([
                'parent_product_id' => $parentProduct->id,
                'variant_sku' => $variantSku,
                'variant_name' => $variantData['variant_name'] ?? null,
                'variant_attributes' => $variantData['variant_attributes'],
                'individual_pricing' => $variantData['individual_pricing'] ?? false,
                'individual_stock' => $variantData['individual_stock'] ?? true,
                'individual_images' => $variantData['individual_images'] ?? false,
                'price_adjustment_type' => $variantData['price_adjustment_type'] ?? 'none',
                'price_adjustment_value' => $variantData['price_adjustment_value'] ?? null,
                'weight_g' => $variantData['weight_g'] ?? null,
                'dimensions' => $variantData['dimensions'] ?? null,
                'display_order' => $variantData['display_order'] ?? 0
            ]);
            
            // Create initial stock records if individual stock tracking
            if ($variant->individual_stock) {
                $this->createVariantStockRecords($variant);
            }
            
            // Create individual prices if needed
            if ($variant->individual_pricing) {
                $this->createVariantPrices($variant, $variantData['prices'] ?? []);
            }
            
            DB::commit();
            
            return $variant;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function updateVariant(ProductVariant $variant, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $variant->update([
                'variant_name' => $data['variant_name'] ?? $variant->variant_name,
                'variant_attributes' => $data['variant_attributes'] ?? $variant->variant_attributes,
                'price_adjustment_type' => $data['price_adjustment_type'] ?? $variant->price_adjustment_type,
                'price_adjustment_value' => $data['price_adjustment_value'] ?? $variant->price_adjustment_value,
                'weight_g' => $data['weight_g'] ?? $variant->weight_g,
                'dimensions' => $data['dimensions'] ?? $variant->dimensions,
                'is_available' => $data['is_available'] ?? $variant->is_available,
                'display_order' => $data['display_order'] ?? $variant->display_order
            ]);
            
            // Update prices if provided
            if (isset($data['prices']) && $variant->individual_pricing) {
                $this->updateVariantPrices($variant, $data['prices']);
            }
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
    
    public function getVariantsForProduct(Product $product): Collection
    {
        return ProductVariant::where('parent_product_id', $product->id)
            ->where('is_available', true)
            ->orderBy('display_order')
            ->orderBy('variant_name')
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->variant_sku,
                    'name' => $variant->variant_name,
                    'attributes' => $variant->variant_attributes,
                    'price' => $this->calculateVariantPrice($variant),
                    'stock' => $this->getVariantStock($variant),
                    'images' => $this->getVariantImages($variant),
                    'availability' => [
                        'is_available' => $variant->is_available,
                        'availability_date' => $variant->availability_date
                    ]
                ];
            });
    }
    
    public function createBundle(array $bundleData, array $items): ProductBundle
    {
        try {
            DB::beginTransaction();
            
            $bundleSku = $this->generateBundleSku($bundleData['bundle_name']);
            
            $bundle = ProductBundle::create([
                'bundle_sku' => $bundleSku,
                'bundle_name' => $bundleData['bundle_name'],
                'bundle_description' => $bundleData['bundle_description'] ?? null,
                'bundle_type' => $bundleData['bundle_type'] ?? 'fixed',
                'pricing_strategy' => $bundleData['pricing_strategy'] ?? 'sum',
                'discount_value' => $bundleData['discount_value'] ?? null,
                'min_items' => $bundleData['min_items'] ?? 1,
                'max_items' => $bundleData['max_items'] ?? null,
                'all_items_required' => $bundleData['all_items_required'] ?? true,
                'is_featured' => $bundleData['is_featured'] ?? false,
                'display_order' => $bundleData['display_order'] ?? 0
            ]);
            
            // Add items to bundle
            foreach ($items as $index => $item) {
                ProductBundleItem::create([
                    'bundle_id' => $bundle->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'is_required' => $item['is_required'] ?? true,
                    'is_default_selected' => $item['is_default_selected'] ?? true,
                    'min_quantity' => $item['min_quantity'] ?? 1,
                    'max_quantity' => $item['max_quantity'] ?? null,
                    'individual_price' => $item['individual_price'] ?? null,
                    'discount_percentage' => $item['discount_percentage'] ?? null,
                    'display_order' => $index
                ]);
            }
            
            DB::commit();
            
            return $bundle;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function calculateBundlePrice(ProductBundle $bundle, array $selectedItems = []): array
    {
        $bundleItems = $bundle->items()->with('product.prices')->get();
        
        if (empty($selectedItems)) {
            $selectedItems = $bundleItems->where('is_default_selected', true)->pluck('id')->toArray();
        }
        
        $totalPrice = 0;
        $totalOriginalPrice = 0;
        $itemDetails = [];
        
        foreach ($bundleItems as $item) {
            if (in_array($item->id, $selectedItems)) {
                $quantity = $item->quantity;
                
                // Get item price
                if ($item->individual_price) {
                    $itemPrice = $item->individual_price;
                } else {
                    $basePrice = $item->product->prices->where('price_group', 'detaliczna')->first()?->price ?? 0;
                    
                    if ($item->discount_percentage) {
                        $itemPrice = $basePrice * (1 - $item->discount_percentage / 100);
                    } else {
                        $itemPrice = $basePrice;
                    }
                }
                
                $itemTotal = $itemPrice * $quantity;
                $originalItemTotal = ($item->product->prices->where('price_group', 'detaliczna')->first()?->price ?? 0) * $quantity;
                
                $totalPrice += $itemTotal;
                $totalOriginalPrice += $originalItemTotal;
                
                $itemDetails[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'quantity' => $quantity,
                    'unit_price' => $itemPrice,
                    'total_price' => $itemTotal
                ];
            }
        }
        
        // Apply bundle discount
        switch ($bundle->pricing_strategy) {
            case 'discount_percentage':
                $finalPrice = $totalPrice * (1 - ($bundle->discount_value / 100));
                break;
            case 'discount_fixed':
                $finalPrice = max(0, $totalPrice - $bundle->discount_value);
                break;
            case 'custom':
                $finalPrice = $bundle->discount_value ?? $totalPrice;
                break;
            default: // sum
                $finalPrice = $totalPrice;
        }
        
        return [
            'original_price' => $totalOriginalPrice,
            'bundle_price' => $totalPrice,
            'final_price' => $finalPrice,
            'total_savings' => $totalOriginalPrice - $finalPrice,
            'savings_percentage' => $totalOriginalPrice > 0 ? (($totalOriginalPrice - $finalPrice) / $totalOriginalPrice) * 100 : 0,
            'items' => $itemDetails
        ];
    }
    
    protected function generateVariantSku(Product $parentProduct, array $attributes): string
    {
        $baseSku = $parentProduct->sku;
        $attributeString = '';
        
        foreach ($attributes as $key => $value) {
            $attributeString .= substr(strtoupper($key), 0, 2) . substr(strtoupper($value), 0, 2);
        }
        
        return $baseSku . '-' . $attributeString;
    }
    
    protected function generateBundleSku(string $bundleName): string
    {
        $prefix = 'BDL';
        $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $bundleName), 0, 6));
        
        $lastBundle = ProductBundle::where('bundle_sku', 'LIKE', "{$prefix}-{$nameCode}%")
            ->orderBy('id', 'desc')
            ->first();
            
        $sequence = 1;
        if ($lastBundle) {
            $lastSequence = intval(substr($lastBundle->bundle_sku, -3));
            $sequence = $lastSequence + 1;
        }
        
        return sprintf('%s-%s%03d', $prefix, $nameCode, $sequence);
    }
    
    protected function createVariantStockRecords(ProductVariant $variant): void
    {
        $warehouses = \App\Models\Warehouse::active()->get();
        
        foreach ($warehouses as $warehouse) {
            \App\Models\ProductStock::create([
                'product_id' => $variant->parent_product_id,
                'variant_id' => $variant->id,
                'warehouse_code' => $warehouse->code,
                'quantity' => 0
            ]);
        }
    }
    
    protected function createVariantPrices(ProductVariant $variant, array $prices): void
    {
        $priceGroups = \App\Models\PriceGroup::active()->get();
        
        foreach ($priceGroups as $priceGroup) {
            $price = $prices[$priceGroup->name] ?? $this->calculateVariantPrice($variant, $priceGroup->name);
            
            \App\Models\ProductPrice::create([
                'product_id' => $variant->parent_product_id,
                'variant_id' => $variant->id,
                'price_group' => $priceGroup->name,
                'price' => $price
            ]);
        }
    }
    
    protected function updateVariantPrices(ProductVariant $variant, array $prices): void
    {
        foreach ($prices as $priceGroup => $price) {
            \App\Models\ProductPrice::updateOrCreate([
                'product_id' => $variant->parent_product_id,
                'variant_id' => $variant->id,
                'price_group' => $priceGroup
            ], [
                'price' => $price
            ]);
        }
    }
    
    protected function calculateVariantPrice(ProductVariant $variant, string $priceGroup = 'detaliczna'): float
    {
        if (!$variant->individual_pricing) {
            // Use parent product price with adjustments
            $parentPrice = $variant->parentProduct->prices->where('price_group', $priceGroup)->first()?->price ?? 0;
            
            switch ($variant->price_adjustment_type) {
                case 'fixed':
                    return $parentPrice + ($variant->price_adjustment_value ?? 0);
                case 'percentage':
                    return $parentPrice * (1 + ($variant->price_adjustment_value ?? 0) / 100);
                default:
                    return $parentPrice;
            }
        }
        
        // Individual pricing
        return $variant->prices->where('price_group', $priceGroup)->first()?->price ?? 0;
    }
    
    protected function getVariantStock(ProductVariant $variant): int
    {
        if (!$variant->individual_stock) {
            return $variant->parentProduct->stock->sum('quantity');
        }
        
        return \App\Models\ProductStock::where('product_id', $variant->parent_product_id)
            ->where('variant_id', $variant->id)
            ->sum('quantity');
    }
    
    protected function getVariantImages(ProductVariant $variant): Collection
    {
        if (!$variant->individual_images) {
            return $variant->parentProduct->images;
        }
        
        return \App\Models\ProductImage::where('product_id', $variant->parent_product_id)
            ->where('variant_id', $variant->id)
            ->get();
    }
}
```

---

## ❌ 11.6 LIVEWIRE COMPONENTS

### ❌ 11.6.1 VehicleSelector Component
#### ❌ 11.6.1.1 Komponent wyboru pojazdu
```php
<?php
namespace App\Livewire\Vehicle;

use App\Services\Vehicle\VehicleService;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleGeneration;
use App\Models\VehicleEngine;
use Livewire\Component;

class VehicleSelector extends Component
{
    public ?int $selectedBrand = null;
    public ?int $selectedModel = null;
    public ?int $selectedGeneration = null;
    public ?int $selectedEngine = null;
    
    public string $vinNumber = '';
    public bool $showVinSearch = false;
    public bool $useVinSearch = false;
    
    public array $brands = [];
    public array $models = [];
    public array $generations = [];
    public array $engines = [];
    
    public array $searchResults = [];
    public string $searchQuery = '';
    
    protected VehicleService $vehicleService;
    
    public function boot(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }
    
    public function mount()
    {
        $this->loadBrands();
    }
    
    public function render()
    {
        return view('livewire.vehicle.vehicle-selector');
    }
    
    public function loadBrands()
    {
        $this->brands = VehicleBrand::where('is_active', true)
            ->orderBy('is_popular', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'full_name', 'logo_url', 'is_popular'])
            ->toArray();
    }
    
    public function updatedSelectedBrand($brandId)
    {
        $this->selectedModel = null;
        $this->selectedGeneration = null;
        $this->selectedEngine = null;
        $this->models = [];
        $this->generations = [];
        $this->engines = [];
        
        if ($brandId) {
            $this->models = VehicleModel::where('brand_id', $brandId)
                ->where('is_active', true)
                ->orderBy('is_popular', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'full_name', 'body_type', 'production_start', 'production_end'])
                ->toArray();
        }
        
        $this->emitSelectionChanged();
    }
    
    public function updatedSelectedModel($modelId)
    {
        $this->selectedGeneration = null;
        $this->selectedEngine = null;
        $this->generations = [];
        $this->engines = [];
        
        if ($modelId) {
            $this->generations = VehicleGeneration::where('model_id', $modelId)
                ->where('is_active', true)
                ->orderBy('generation_start', 'desc')
                ->get(['id', 'name', 'generation_code', 'generation_start', 'generation_end', 'platform'])
                ->toArray();
        }
        
        $this->emitSelectionChanged();
    }
    
    public function updatedSelectedGeneration($generationId)
    {
        $this->selectedEngine = null;
        $this->engines = [];
        
        if ($generationId) {
            $this->engines = VehicleEngine::where('generation_id', $generationId)
                ->where('is_active', true)
                ->orderBy('fuel_type')
                ->orderBy('displacement_cc')
                ->get(['id', 'engine_code', 'displacement_cc', 'fuel_type', 'power_hp', 'power_kw', 'production_start', 'production_end'])
                ->toArray();
        }
        
        $this->emitSelectionChanged();
    }
    
    public function updatedSelectedEngine($engineId)
    {
        $this->emitSelectionChanged();
    }
    
    public function searchVehicles()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }
        
        $this->searchResults = $this->vehicleService->searchVehicles($this->searchQuery)->toArray();
    }
    
    public function selectFromSearch(array $vehicle)
    {
        switch ($vehicle['type']) {
            case 'brand':
                $this->selectedBrand = $vehicle['id'];
                $this->updatedSelectedBrand($vehicle['id']);
                break;
                
            case 'model':
                $this->selectedBrand = VehicleModel::find($vehicle['id'])->brand_id;
                $this->updatedSelectedBrand($this->selectedBrand);
                $this->selectedModel = $vehicle['id'];
                $this->updatedSelectedModel($vehicle['id']);
                break;
                
            case 'generation':
                $generation = VehicleGeneration::with('model')->find($vehicle['id']);
                $this->selectedBrand = $generation->model->brand_id;
                $this->updatedSelectedBrand($this->selectedBrand);
                $this->selectedModel = $generation->model_id;
                $this->updatedSelectedModel($this->selectedModel);
                $this->selectedGeneration = $vehicle['id'];
                $this->updatedSelectedGeneration($vehicle['id']);
                break;
                
            case 'engine':
                $engine = VehicleEngine::with('generation.model')->find($vehicle['id']);
                $this->selectedBrand = $engine->generation->model->brand_id;
                $this->updatedSelectedBrand($this->selectedBrand);
                $this->selectedModel = $engine->generation->model_id;
                $this->updatedSelectedModel($this->selectedModel);
                $this->selectedGeneration = $engine->generation_id;
                $this->updatedSelectedGeneration($this->selectedGeneration);
                $this->selectedEngine = $vehicle['id'];
                $this->updatedSelectedEngine($vehicle['id']);
                break;
        }
        
        $this->searchQuery = '';
        $this->searchResults = [];
    }
    
    public function searchByVin()
    {
        if (strlen($this->vinNumber) !== 17) {
            session()->flash('error', 'Numer VIN musi mieć 17 znaków');
            return;
        }
        
        try {
            $engine = $this->vehicleService->findVehicleByVIN($this->vinNumber);
            
            if ($engine) {
                $this->selectedBrand = $engine->generation->model->brand_id;
                $this->updatedSelectedBrand($this->selectedBrand);
                $this->selectedModel = $engine->generation->model_id;
                $this->updatedSelectedModel($this->selectedModel);
                $this->selectedGeneration = $engine->generation_id;
                $this->updatedSelectedGeneration($this->selectedGeneration);
                $this->selectedEngine = $engine->id;
                $this->updatedSelectedEngine($engine->id);
                
                session()->flash('message', 'Pojazd został znaleziony na podstawie numeru VIN');
            } else {
                session()->flash('error', 'Nie można znaleźć pojazdu dla podanego numeru VIN');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd dekodowania VIN: ' . $e->getMessage());
        }
    }
    
    public function clearSelection()
    {
        $this->selectedBrand = null;
        $this->selectedModel = null;
        $this->selectedGeneration = null;
        $this->selectedEngine = null;
        $this->models = [];
        $this->generations = [];
        $this->engines = [];
        $this->vinNumber = '';
        
        $this->emitSelectionChanged();
    }
    
    public function getSelectedVehicle(): ?array
    {
        if (!$this->selectedEngine) {
            return null;
        }
        
        $engine = VehicleEngine::with(['generation.model.brand'])->find($this->selectedEngine);
        
        return [
            'brand' => $engine->generation->model->brand,
            'model' => $engine->generation->model,
            'generation' => $engine->generation,
            'engine' => $engine,
            'full_name' => $engine->generation->model->brand->name . ' ' . 
                         $engine->generation->model->name . ' ' . 
                         $engine->generation->name . ' ' . 
                         $engine->engine_code
        ];
    }
    
    protected function emitSelectionChanged()
    {
        $this->dispatch('vehicleSelectionChanged', [
            'brand_id' => $this->selectedBrand,
            'model_id' => $this->selectedModel,
            'generation_id' => $this->selectedGeneration,
            'engine_id' => $this->selectedEngine,
            'vehicle' => $this->getSelectedVehicle()
        ]);
    }
    
    public function updatedSearchQuery()
    {
        $this->searchVehicles();
    }
    
    public function toggleVinSearch()
    {
        $this->showVinSearch = !$this->showVinSearch;
        $this->vinNumber = '';
    }
}
```

---

## ❌ 11.7 TESTY I DOKUMENTACJA

### ❌ 11.7.1 Testy systemu dopasowań
#### ❌ 11.7.1.1 ProductMatchingTest
```php
<?php
namespace Tests\Feature\Product;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductAlternative;
use App\Models\VehicleEngine;
use App\Services\Product\ProductMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductMatchingTest extends TestCase
{
    use RefreshDatabase;
    
    protected ProductMatchingService $matchingService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->matchingService = app(ProductMatchingService::class);
    }
    
    public function testCanFindAlternatives()
    {
        $originalProduct = Product::factory()->create();
        $alternativeProduct = Product::factory()->create();
        
        ProductAlternative::create([
            'original_product_id' => $originalProduct->id,
            'alternative_product_id' => $alternativeProduct->id,
            'compatibility_level' => 'compatible',
            'compatibility_percentage' => 95,
            'alternative_type' => 'aftermarket'
        ]);
        
        $alternatives = $this->matchingService->findAlternatives($originalProduct);
        
        $this->assertCount(1, $alternatives);
        $this->assertEquals($alternativeProduct->id, $alternatives->first()['product']->id);
    }
    
    public function testCanFindCompatibleParts()
    {
        $engine = VehicleEngine::factory()->create();
        $product = Product::factory()->create();
        
        \App\Models\VehiclePartCompatibility::create([
            'product_id' => $product->id,
            'engine_id' => $engine->id,
            'confidence_level' => 'confirmed'
        ]);
        
        $compatibleParts = $this->matchingService->findCompatibleParts($engine);
        
        $this->assertGreaterThan(0, $compatibleParts->count());
        $this->assertEquals($product->id, $compatibleParts->first()['product']->id);
    }
}
```

---

## 📊 METRYKI ETAPU

**Szacowany czas realizacji:** 40 godzin  
**Liczba plików do utworzenia:** ~20  
**Liczba testów:** ~12  
**Liczba tabel MySQL:** 10 głównych + indeksy  
**API endpoints:** ~8  

---

## 🔍 DEFINICJA GOTOWOŚCI (DoD)

Etap zostanie uznany za ukończony gdy:

- ✅ Wszystkie zadania mają status ✅
- ✅ System dopasowań części zamiennych działa kompletnie
- ✅ Katalog pojazdów jest zaimplementowany z hierarchią
- ✅ System kompatybilności części z pojazdami funkcjonuje
- ✅ Warianty produktów działają z dedykowanymi cenami i stanami
- ✅ Inteligentny system rekomendacji generuje trafne propozycje
- ✅ VIN decoder i wyszukiwarka po rejestracji działają
- ✅ System zestawów i bundli jest funkcjonalny
- ✅ Wszystkie testy przechodzą poprawnie
- ✅ Kod przesłany na serwer produkcyjny i przetestowany
- ✅ Dokumentacja dopasowań jest kompletna

---

**Autor:** Claude Code AI  
**Data utworzenia:** 2025-09-05  
**Ostatnia aktualizacja:** 2025-09-05  
**Status:** ❌ NIEROZPOCZĘTY