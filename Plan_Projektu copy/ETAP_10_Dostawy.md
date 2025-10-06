# ❌ ETAP 10: SYSTEM DOSTAW I KONTENERÓW

**UWAGA** WYŁĄCZ autoryzację AdminMiddleware na czas developmentu!

**Szacowany czas realizacji:** 50 godzin  
**Priorytet:** 🟡 WYSOKI  
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński  
**Wymagane zasoby:** Laravel 12.x, MySQL, API dla Android, System plików  

---

## 🎯 CEL ETAPU

Implementacja kompletnego systemu zarządzania dostawami, kontenerami i logistyką magazynową. System musi obsługiwać cały przepływ: od zamówienia dostawy, przez śledzenie kontenerów, po przyjęcie i lokalizację towarów w magazynie. Zawiera również API dla aplikacji magazynowej Android do mobilnego zarządzania dostawami.

### Kluczowe rezultaty:
- ✅ System zarządzania kontenerami i dostawami
- ✅ Śledzenie statusów dostaw i dokumentów odpraw
- ✅ System lokalizacji magazynowych (regały, półki, miejsca)
- ✅ Panel zarządzania dostawami dla Magazynierów
- ✅ API dla aplikacji magazynowej Android
- ✅ Automatyczne aktualizacje stanów po przyjęciu dostaw
- ✅ System alertów i powiadomień o dostawach
- ✅ Raportowanie i analityka dostaw
- ✅ Integracja z systemem produktów i stanów magazynowych

---

## ❌ 10.1 ANALIZA I ARCHITEKTURA SYSTEMU DOSTAW

### ❌ 10.1.1 Wymagania funkcjonalne dostaw
#### ❌ 10.1.1.1 Przepływ procesu dostawy
- ❌ 10.1.1.1.1 Zamówienie dostawy (ręczne lub z ERP)
- ❌ 10.1.1.1.2 Śledzenie statusu przesyłki
- ❌ 10.1.1.1.3 Przygotowanie dokumentów odprawy
- ❌ 10.1.1.1.4 Przyjęcie dostawy w magazynie
- ❌ 10.1.1.1.5 Lokalizacja produktów w magazynie

#### ❌ 10.1.1.2 Zarządzanie kontenerami
- ❌ 10.1.1.2.1 Rejestracja nowych kontenerów
- ❌ 10.1.1.2.2 Przypisanie produktów do kontenerów
- ❌ 10.1.1.2.3 Śledzenie statusów kontenerów
- ❌ 10.1.1.2.4 Historia przemieszczeń kontenerów
- ❌ 10.1.1.2.5 Rozdzielanie i konsolidacja kontenerów

#### ❌ 10.1.1.3 System lokalizacji magazynowej
- ❌ 10.1.1.3.1 Hierarchia lokalizacji (magazyn → sektor → regał → półka)
- ❌ 10.1.1.3.2 Automatyczne przypisanie optymalnych lokalizacji
- ❌ 10.1.1.3.3 Zarządzanie pojemnością lokalizacji
- ❌ 10.1.1.3.4 Optymalizacja tras poboru (picking routes)
- ❌ 10.1.1.3.5 System kodów kreskowych dla lokalizacji

### ❌ 10.1.2 Wymagania aplikacji magazynowej Android
#### ❌ 10.1.2.1 Funkcjonalności mobilne
- ❌ 10.1.2.1.1 Skanowanie kodów kreskowych produktów i lokalizacji
- ❌ 10.1.2.1.2 Przyjmowanie dostaw z walidacją
- ❌ 10.1.2.1.3 Przemieszczanie produktów między lokalizacjami  
- ❌ 10.1.2.1.4 Inwentaryzacja i korekty stanów
- ❌ 10.1.2.1.5 Offline mode z synchronizacją

#### ❌ 10.1.2.2 API Requirements
- ❌ 10.1.2.2.1 RESTful API z autentykacją JWT
- ❌ 10.1.2.2.2 Endpoints dla wszystkich operacji magazynowych
- ❌ 10.1.2.2.3 Batch operations dla synchronizacji offline
- ❌ 10.1.2.2.4 Real-time notifications via WebSocket
- ❌ 10.1.2.2.5 File upload dla zdjęć dokumentów

### ❌ 10.1.3 Integracje systemu dostaw
#### ❌ 10.1.3.1 Integracja z kurierami
- ❌ 10.1.3.1.1 API DPD dla śledzenia przesyłek
- ❌ 10.1.3.1.2 API InPost dla paczek
- ❌ 10.1.3.1.3 API UPS/FedEx dla przesyłek międzynarodowych
- ❌ 10.1.3.1.4 Uniwersalny adapter dla różnych kurierów
- ❌ 10.1.3.1.5 Webhook endpoints dla aktualizacji statusów

#### ❌ 10.1.3.2 Integracja z ERP
- ❌ 10.1.3.2.1 Automatyczny import zamówień z BaseLinker
- ❌ 10.1.3.2.2 Sync dostawni z Subiekt GT
- ❌ 10.1.3.2.3 Purchase Orders z Microsoft Dynamics
- ❌ 10.1.3.2.4 Automatyczne aktualizacje stanów po przyjęciu
- ❌ 10.1.3.2.5 Powiadomienia o otrzymanych dostawach

---

## ❌ 10.2 MODELE I MIGRACJE DOSTAW

### ❌ 10.2.1 Tabele główne systemu dostaw
#### ❌ 10.2.1.1 Tabela shipments (dostawy)
```sql
CREATE TABLE shipments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shipment_number VARCHAR(255) NOT NULL UNIQUE,
    
    -- Basic shipment info
    supplier_name VARCHAR(255) NOT NULL,
    supplier_contact JSON NULL, -- Phone, email, address
    
    -- Shipment details
    shipment_type ENUM('purchase_order', 'return', 'transfer', 'sample') DEFAULT 'purchase_order',
    total_value DECIMAL(15,2) NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    
    -- Dates
    ordered_at TIMESTAMP NULL,
    estimated_delivery_at TIMESTAMP NULL,
    actual_delivery_at TIMESTAMP NULL,
    
    -- Status tracking
    status ENUM(
        'ordered', 'confirmed', 'shipped', 'in_transit', 
        'customs', 'out_for_delivery', 'delivered', 
        'partially_received', 'fully_received', 'cancelled'
    ) DEFAULT 'ordered',
    
    -- Tracking info
    tracking_number VARCHAR(255) NULL,
    carrier_name VARCHAR(255) NULL,
    carrier_service VARCHAR(255) NULL,
    tracking_url VARCHAR(500) NULL,
    
    -- Warehouse assignment
    destination_warehouse_id BIGINT UNSIGNED NULL,
    assigned_to_user_id BIGINT UNSIGNED NULL, -- Magazynier odpowiedzialny
    
    -- Documents
    has_invoice BOOLEAN DEFAULT FALSE,
    has_packing_list BOOLEAN DEFAULT FALSE,
    has_customs_declaration BOOLEAN DEFAULT FALSE,
    documents_path VARCHAR(500) NULL, -- Folder z dokumentami
    
    -- Notes and additional info
    notes TEXT NULL,
    special_instructions TEXT NULL,
    
    -- ERP integration
    erp_source ENUM('manual', 'baselinker', 'subiekt_gt', 'dynamics') DEFAULT 'manual',
    erp_reference_id VARCHAR(255) NULL,
    erp_data JSON NULL,
    
    -- Metadata
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_shipment_number (shipment_number),
    INDEX idx_status (status),
    INDEX idx_supplier (supplier_name),
    INDEX idx_tracking (tracking_number),
    INDEX idx_delivery_dates (estimated_delivery_at, actual_delivery_at),
    INDEX idx_warehouse_assigned (destination_warehouse_id, assigned_to_user_id),
    INDEX idx_erp_source (erp_source, erp_reference_id)
);
```

#### ❌ 10.2.1.2 Tabela containers (kontenery)
```sql
CREATE TABLE containers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    container_number VARCHAR(255) NOT NULL UNIQUE,
    shipment_id BIGINT UNSIGNED NOT NULL,
    
    -- Container details
    container_type ENUM('box', 'pallet', 'crate', 'envelope', 'tube', 'custom') DEFAULT 'box',
    dimensions JSON NULL, -- {width, height, depth, unit}
    weight_kg DECIMAL(8,2) NULL,
    max_weight_kg DECIMAL(8,2) NULL,
    
    -- Status
    status ENUM('prepared', 'shipped', 'received', 'unpacked', 'empty') DEFAULT 'prepared',
    
    -- Location tracking
    current_location_id BIGINT UNSIGNED NULL, -- Current warehouse location
    location_history JSON NULL, -- Array of location changes with timestamps
    
    -- Container contents
    expected_items_count INT UNSIGNED DEFAULT 0,
    received_items_count INT UNSIGNED DEFAULT 0,
    is_fully_received BOOLEAN DEFAULT FALSE,
    
    -- Handling info
    fragile BOOLEAN DEFAULT FALSE,
    requires_refrigeration BOOLEAN DEFAULT FALSE,
    hazardous_materials BOOLEAN DEFAULT FALSE,
    handling_instructions TEXT NULL,
    
    -- Quality control
    needs_inspection BOOLEAN DEFAULT FALSE,
    inspection_completed_at TIMESTAMP NULL,
    inspection_notes TEXT NULL,
    
    -- Timestamps
    packed_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    unpacked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (current_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    
    INDEX idx_container_number (container_number),
    INDEX idx_shipment (shipment_id),
    INDEX idx_status (status),
    INDEX idx_location (current_location_id),
    INDEX idx_received_status (is_fully_received, received_at),
    INDEX idx_inspection (needs_inspection, inspection_completed_at)
);
```

### ❌ 10.2.2 Tabele lokalizacji magazynowej
#### ❌ 10.2.2.1 Tabela warehouse_locations (lokalizacje)
```sql
CREATE TABLE warehouse_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(100) NOT NULL UNIQUE, -- e.g., "W01-A-05-03"
    
    -- Hierarchy
    warehouse_id BIGINT UNSIGNED NOT NULL,
    parent_location_id BIGINT UNSIGNED NULL, -- For hierarchical structure
    
    -- Location details
    location_type ENUM('warehouse', 'zone', 'aisle', 'rack', 'shelf', 'bin', 'floor') NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Physical properties
    dimensions JSON NULL, -- {width, height, depth, unit}
    max_weight_kg DECIMAL(10,2) NULL,
    capacity_cubic_cm BIGINT NULL,
    
    -- Current usage
    current_weight_kg DECIMAL(10,2) DEFAULT 0,
    current_volume_cubic_cm BIGINT DEFAULT 0,
    items_count INT UNSIGNED DEFAULT 0,
    
    -- Access and restrictions
    access_level ENUM('public', 'restricted', 'quarantine', 'maintenance') DEFAULT 'public',
    requires_special_equipment BOOLEAN DEFAULT FALSE,
    temperature_controlled BOOLEAN DEFAULT FALSE,
    
    -- Barcode and identification
    barcode VARCHAR(255) NULL,
    qr_code VARCHAR(255) NULL,
    
    -- Location coordinates (for warehouse mapping)
    coordinate_x DECIMAL(10,2) NULL,
    coordinate_y DECIMAL(10,2) NULL,
    coordinate_z DECIMAL(10,2) NULL, -- Floor/height level
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_pickable BOOLEAN DEFAULT TRUE, -- Can items be picked from this location
    
    -- Optimization data
    picking_priority INT DEFAULT 0, -- Higher = picked first
    distance_from_entrance_m DECIMAL(8,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_location_code (location_code),
    INDEX idx_warehouse_hierarchy (warehouse_id, parent_location_id),
    INDEX idx_location_type (location_type, is_active),
    INDEX idx_barcode (barcode),
    INDEX idx_capacity (capacity_cubic_cm, current_volume_cubic_cm),
    INDEX idx_picking (is_pickable, picking_priority),
    INDEX idx_coordinates (coordinate_x, coordinate_y, coordinate_z)
);
```

#### ❌ 10.2.2.2 Tabela shipment_items (pozycje w dostawach)
```sql
CREATE TABLE shipment_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shipment_id BIGINT UNSIGNED NOT NULL,
    container_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- Quantities
    expected_quantity INT UNSIGNED NOT NULL,
    received_quantity INT UNSIGNED DEFAULT 0,
    confirmed_quantity INT UNSIGNED DEFAULT 0, -- After quality check
    damaged_quantity INT UNSIGNED DEFAULT 0,
    
    -- Pricing (from purchase order)
    unit_cost DECIMAL(10,4) NULL,
    total_cost DECIMAL(12,2) NULL,
    
    -- Location assignment
    assigned_location_id BIGINT UNSIGNED NULL,
    
    -- Status
    status ENUM('pending', 'partially_received', 'fully_received', 'quality_check', 'confirmed', 'rejected') DEFAULT 'pending',
    
    -- Quality control
    requires_inspection BOOLEAN DEFAULT FALSE,
    inspection_passed BOOLEAN NULL,
    inspection_notes TEXT NULL,
    
    -- Batch/Serial tracking
    batch_number VARCHAR(255) NULL,
    expiry_date DATE NULL,
    serial_numbers JSON NULL, -- Array of serial numbers if applicable
    
    -- Reception details
    received_by BIGINT UNSIGNED NULL,
    received_at TIMESTAMP NULL,
    reception_notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_shipment_product (shipment_id, product_id),
    INDEX idx_container (container_id),
    INDEX idx_status (status),
    INDEX idx_location (assigned_location_id),
    INDEX idx_received (received_by, received_at),
    INDEX idx_batch (batch_number),
    INDEX idx_quality (requires_inspection, inspection_passed)
);
```

### ❌ 10.2.3 Tabele śledzenia i historii
#### ❌ 10.2.3.1 Tabela shipment_status_history
```sql
CREATE TABLE shipment_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shipment_id BIGINT UNSIGNED NOT NULL,
    
    -- Status change details
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    
    -- Context
    changed_by BIGINT UNSIGNED NULL, -- User who changed, NULL for automatic
    change_source ENUM('manual', 'api', 'webhook', 'system') DEFAULT 'manual',
    
    -- Additional data
    notes TEXT NULL,
    tracking_data JSON NULL, -- Raw tracking data from courier
    location_info JSON NULL, -- Current location from tracking
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_shipment_status (shipment_id, created_at),
    INDEX idx_status_change (old_status, new_status),
    INDEX idx_change_source (change_source, created_at)
);
```

#### ❌ 10.2.3.2 Tabela location_movements (przemieszczenia)
```sql
CREATE TABLE location_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- What was moved
    movable_type VARCHAR(255) NOT NULL, -- Product, Container, etc.
    movable_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NULL, -- For products, NULL for containers
    
    -- Movement details
    from_location_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('receive', 'transfer', 'pick', 'putaway', 'adjustment', 'return') NOT NULL,
    
    -- Context
    related_type VARCHAR(255) NULL, -- Shipment, Order, Adjustment, etc.
    related_id BIGINT UNSIGNED NULL,
    
    -- Who and when
    performed_by BIGINT UNSIGNED NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Additional info
    reason TEXT NULL,
    notes TEXT NULL,
    
    -- Mobile app data
    scanned_from_barcode VARCHAR(255) NULL,
    scanned_to_barcode VARCHAR(255) NULL,
    device_info JSON NULL, -- Mobile device details
    
    FOREIGN KEY (from_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (to_location_id) REFERENCES warehouse_locations(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_movable (movable_type, movable_id),
    INDEX idx_locations (from_location_id, to_location_id),
    INDEX idx_movement_type (movement_type, performed_at),
    INDEX idx_related (related_type, related_id),
    INDEX idx_performer (performed_by, performed_at)
);
```

---

## ❌ 10.3 SHIPMENT SERVICE LAYER

### ❌ 10.3.1 ShipmentService - główny serwis dostaw
#### ❌ 10.3.1.1 Klasa ShipmentService
```php
<?php
namespace App\Services\Delivery;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Container;
use App\Models\Product;
use App\Models\WarehouseLocation;
use App\Services\Delivery\LocationOptimizationService;
use App\Services\Delivery\TrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentService
{
    protected LocationOptimizationService $locationOptimizer;
    protected TrackingService $trackingService;
    
    public function __construct(
        LocationOptimizationService $locationOptimizer,
        TrackingService $trackingService
    ) {
        $this->locationOptimizer = $locationOptimizer;
        $this->trackingService = $trackingService;
    }
    
    public function createShipment(array $shipmentData, array $items = []): Shipment
    {
        try {
            DB::beginTransaction();
            
            // Generate unique shipment number
            $shipmentNumber = $this->generateShipmentNumber();
            
            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'supplier_name' => $shipmentData['supplier_name'],
                'supplier_contact' => $shipmentData['supplier_contact'] ?? null,
                'shipment_type' => $shipmentData['shipment_type'] ?? 'purchase_order',
                'total_value' => $shipmentData['total_value'] ?? null,
                'currency' => $shipmentData['currency'] ?? 'PLN',
                'ordered_at' => $shipmentData['ordered_at'] ?? now(),
                'estimated_delivery_at' => $shipmentData['estimated_delivery_at'] ?? null,
                'destination_warehouse_id' => $shipmentData['destination_warehouse_id'] ?? null,
                'notes' => $shipmentData['notes'] ?? null,
                'erp_source' => $shipmentData['erp_source'] ?? 'manual',
                'erp_reference_id' => $shipmentData['erp_reference_id'] ?? null,
                'created_by' => auth()->id()
            ]);
            
            // Add shipment items
            if (!empty($items)) {
                $this->addItemsToShipment($shipment, $items);
            }
            
            // Create default container if not specified
            if (empty($shipmentData['containers'])) {
                $this->createDefaultContainer($shipment);
            } else {
                foreach ($shipmentData['containers'] as $containerData) {
                    $this->createContainer($shipment, $containerData);
                }
            }
            
            // Log status change
            $this->logStatusChange($shipment, null, 'ordered');
            
            DB::commit();
            
            Log::info('Shipment created', ['shipment_id' => $shipment->id, 'number' => $shipmentNumber]);
            
            return $shipment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create shipment', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function updateShipmentStatus(Shipment $shipment, string $newStatus, array $data = []): bool
    {
        try {
            $oldStatus = $shipment->status;
            
            $updateData = ['status' => $newStatus, 'updated_by' => auth()->id()];
            
            // Handle status-specific updates
            switch ($newStatus) {
                case 'shipped':
                    if (!empty($data['tracking_number'])) {
                        $updateData['tracking_number'] = $data['tracking_number'];
                    }
                    if (!empty($data['carrier_name'])) {
                        $updateData['carrier_name'] = $data['carrier_name'];
                    }
                    break;
                    
                case 'delivered':
                    $updateData['actual_delivery_at'] = $data['delivery_time'] ?? now();
                    break;
                    
                case 'fully_received':
                    $updateData['actual_delivery_at'] = $updateData['actual_delivery_at'] ?? now();
                    $this->markAllItemsReceived($shipment);
                    break;
            }
            
            $shipment->update($updateData);
            
            // Log status change
            $this->logStatusChange($shipment, $oldStatus, $newStatus, $data);
            
            // Trigger automatic actions
            $this->handleStatusChangeActions($shipment, $oldStatus, $newStatus);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to update shipment status', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus ?? null,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function receiveShipmentItem(ShipmentItem $item, int $receivedQuantity, array $options = []): bool
    {
        try {
            DB::beginTransaction();
            
            // Validate received quantity
            $maxReceivable = $item->expected_quantity - $item->received_quantity;
            $actualReceived = min($receivedQuantity, $maxReceivable);
            
            // Update item
            $item->update([
                'received_quantity' => $item->received_quantity + $actualReceived,
                'status' => $this->determineItemStatus($item, $actualReceived),
                'received_by' => auth()->id(),
                'received_at' => now(),
                'reception_notes' => $options['notes'] ?? null
            ]);
            
            // Assign optimal location if not set
            if (!$item->assigned_location_id && !empty($options['auto_assign_location'])) {
                $location = $this->locationOptimizer->findOptimalLocation(
                    $item->product, 
                    $actualReceived,
                    $item->shipment->destination_warehouse_id
                );
                
                if ($location) {
                    $item->update(['assigned_location_id' => $location->id]);
                }
            }
            
            // Update product stock
            $this->updateProductStock($item->product, $actualReceived, $item->assigned_location_id);
            
            // Log movement
            $this->logLocationMovement(
                Product::class,
                $item->product_id,
                $actualReceived,
                null, // from location (receiving)
                $item->assigned_location_id,
                'receive',
                $item->shipment
            );
            
            // Check if shipment is fully received
            $this->checkShipmentCompletion($item->shipment);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to receive shipment item', [
                'item_id' => $item->id,
                'received_quantity' => $receivedQuantity,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    protected function generateShipmentNumber(): string
    {
        $prefix = 'SHP';
        $date = now()->format('Ymd');
        
        $lastNumber = Shipment::where('shipment_number', 'LIKE', "{$prefix}{$date}%")
            ->latest('id')
            ->value('shipment_number');
            
        if ($lastNumber) {
            $sequence = intval(substr($lastNumber, -4)) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
    
    protected function addItemsToShipment(Shipment $shipment, array $items): void
    {
        foreach ($items as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);
            
            ShipmentItem::create([
                'shipment_id' => $shipment->id,
                'product_id' => $product->id,
                'expected_quantity' => $itemData['expected_quantity'],
                'unit_cost' => $itemData['unit_cost'] ?? null,
                'total_cost' => ($itemData['unit_cost'] ?? 0) * $itemData['expected_quantity'],
                'requires_inspection' => $itemData['requires_inspection'] ?? false,
                'batch_number' => $itemData['batch_number'] ?? null,
                'expiry_date' => $itemData['expiry_date'] ?? null
            ]);
        }
    }
    
    protected function createDefaultContainer(Shipment $shipment): Container
    {
        return Container::create([
            'container_number' => $this->generateContainerNumber($shipment),
            'shipment_id' => $shipment->id,
            'container_type' => 'box',
            'status' => 'prepared'
        ]);
    }
    
    protected function createContainer(Shipment $shipment, array $containerData): Container
    {
        return Container::create([
            'container_number' => $containerData['container_number'] ?? $this->generateContainerNumber($shipment),
            'shipment_id' => $shipment->id,
            'container_type' => $containerData['container_type'] ?? 'box',
            'dimensions' => $containerData['dimensions'] ?? null,
            'weight_kg' => $containerData['weight_kg'] ?? null,
            'max_weight_kg' => $containerData['max_weight_kg'] ?? null,
            'fragile' => $containerData['fragile'] ?? false,
            'handling_instructions' => $containerData['handling_instructions'] ?? null,
            'status' => 'prepared'
        ]);
    }
    
    protected function generateContainerNumber(Shipment $shipment): string
    {
        $shipmentNumber = substr($shipment->shipment_number, -6);
        $containerCount = $shipment->containers()->count() + 1;
        
        return "CNT-{$shipmentNumber}-" . sprintf('%02d', $containerCount);
    }
    
    protected function determineItemStatus(ShipmentItem $item, int $newlyReceived): string
    {
        $totalReceived = $item->received_quantity + $newlyReceived;
        
        if ($totalReceived >= $item->expected_quantity) {
            return $item->requires_inspection ? 'quality_check' : 'fully_received';
        } else {
            return 'partially_received';
        }
    }
    
    protected function updateProductStock(Product $product, int $quantity, ?int $locationId): void
    {
        if (!$locationId) {
            return; // Can't update stock without location
        }
        
        $location = WarehouseLocation::find($locationId);
        if (!$location) {
            return;
        }
        
        // Update or create stock record
        $product->stock()->updateOrCreate(
            ['warehouse_code' => $location->warehouse->code],
            [
                'quantity' => DB::raw("quantity + {$quantity}"),
                'location_id' => $locationId,
                'last_updated_at' => now()
            ]
        );
    }
    
    protected function logLocationMovement($movableType, $movableId, $quantity, $fromLocationId, $toLocationId, $movementType, $related): void
    {
        \App\Models\LocationMovement::create([
            'movable_type' => $movableType,
            'movable_id' => $movableId,
            'quantity' => $quantity,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'movement_type' => $movementType,
            'related_type' => get_class($related),
            'related_id' => $related->id,
            'performed_by' => auth()->id(),
            'performed_at' => now()
        ]);
    }
    
    protected function logStatusChange(Shipment $shipment, ?string $oldStatus, string $newStatus, array $data = []): void
    {
        \App\Models\ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'change_source' => 'manual',
            'notes' => $data['notes'] ?? null,
            'tracking_data' => $data['tracking_data'] ?? null,
            'location_info' => $data['location_info'] ?? null
        ]);
    }
    
    protected function checkShipmentCompletion(Shipment $shipment): void
    {
        $totalItems = $shipment->items()->count();
        $fullyReceivedItems = $shipment->items()
            ->whereIn('status', ['fully_received', 'confirmed'])
            ->count();
            
        if ($totalItems > 0) {
            if ($fullyReceivedItems === $totalItems) {
                $this->updateShipmentStatus($shipment, 'fully_received');
            } elseif ($fullyReceivedItems > 0) {
                $this->updateShipmentStatus($shipment, 'partially_received');
            }
        }
    }
    
    protected function handleStatusChangeActions(Shipment $shipment, ?string $oldStatus, string $newStatus): void
    {
        // Automatic tracking updates
        if ($newStatus === 'shipped' && $shipment->tracking_number) {
            $this->trackingService->startTracking($shipment);
        }
        
        // Notify assigned user
        if ($shipment->assigned_to_user_id) {
            // Send notification (implement notification service)
        }
        
        // ERP sync
        if ($shipment->erp_source !== 'manual') {
            // Queue ERP sync job (implement ERP sync)
        }
    }
}
```

---

## ❌ 10.4 LOCATION OPTIMIZATION SERVICE

### ❌ 10.4.1 LocationOptimizationService
#### ❌ 10.4.1.1 Inteligentne przypisywanie lokalizacji
```php
<?php
namespace App\Services\Delivery;

use App\Models\Product;
use App\Models\WarehouseLocation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

class LocationOptimizationService
{
    public function findOptimalLocation(Product $product, int $quantity, ?int $warehouseId = null): ?WarehouseLocation
    {
        $query = WarehouseLocation::where('is_active', true)
            ->where('is_pickable', true)
            ->where('access_level', 'public');
            
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        // Calculate space requirements
        $requiredSpace = $this->calculateRequiredSpace($product, $quantity);
        
        // Find locations with sufficient space
        $availableLocations = $query->whereRaw('(capacity_cubic_cm - current_volume_cubic_cm) >= ?', [$requiredSpace])
            ->get();
            
        if ($availableLocations->isEmpty()) {
            return null;
        }
        
        // Score locations based on optimization criteria
        $scoredLocations = $availableLocations->map(function ($location) use ($product, $quantity) {
            return [
                'location' => $location,
                'score' => $this->calculateLocationScore($location, $product, $quantity)
            ];
        });
        
        // Return location with highest score
        $bestLocation = $scoredLocations->sortByDesc('score')->first();
        
        return $bestLocation['location'] ?? null;
    }
    
    protected function calculateRequiredSpace(Product $product, int $quantity): float
    {
        // Calculate based on product dimensions and quantity
        $volume = ($product->width ?? 10) * ($product->height ?? 10) * ($product->depth ?? 10); // cm³
        return $volume * $quantity;
    }
    
    protected function calculateLocationScore(WarehouseLocation $location, Product $product, int $quantity): float
    {
        $score = 0;
        
        // Prefer locations closer to entrance (for faster access)
        if ($location->distance_from_entrance_m) {
            $score += (100 - $location->distance_from_entrance_m) * 0.1;
        }
        
        // Prefer locations with higher picking priority
        $score += $location->picking_priority * 2;
        
        // Prefer locations with more available space (but not too much to avoid waste)
        $availableSpace = $location->capacity_cubic_cm - $location->current_volume_cubic_cm;
        $requiredSpace = $this->calculateRequiredSpace($product, $quantity);
        
        if ($availableSpace > $requiredSpace) {
            $spaceUtilization = $requiredSpace / $availableSpace;
            $score += $spaceUtilization * 30; // Prefer 70-90% utilization
        }
        
        // Prefer locations that already have similar products (for organization)
        $similarProductsCount = $this->countSimilarProductsInLocation($location, $product);
        $score += $similarProductsCount * 5;
        
        // Prefer locations in the same zone/aisle for consolidation
        if ($product->preferred_location_zone && $location->parent_location_id) {
            $parent = $location->parent;
            if ($parent && str_contains($parent->location_code, $product->preferred_location_zone)) {
                $score += 20;
            }
        }
        
        return $score;
    }
    
    protected function countSimilarProductsInLocation(WarehouseLocation $location, Product $product): int
    {
        // Count products in the same category at this location
        return $product->stock()
            ->where('location_id', $location->id)
            ->whereHas('product', function ($query) use ($product) {
                $query->where('category_id', $product->category_id);
            })
            ->count();
    }
    
    public function suggestLocationReorganization(int $warehouseId): array
    {
        $suggestions = [];
        
        // Find overcrowded locations
        $overcrowdedLocations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->whereRaw('current_volume_cubic_cm > capacity_cubic_cm * 0.95')
            ->with(['stock.product'])
            ->get();
            
        foreach ($overcrowdedLocations as $location) {
            $suggestions[] = [
                'type' => 'overcrowded',
                'location' => $location,
                'message' => "Location {$location->location_code} is overcrowded ({$location->items_count} items)",
                'recommendation' => 'Move some items to less utilized locations'
            ];
        }
        
        // Find underutilized locations
        $underutilizedLocations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->whereRaw('current_volume_cubic_cm < capacity_cubic_cm * 0.20')
            ->where('items_count', '>', 0)
            ->get();
            
        foreach ($underutilizedLocations as $location) {
            $suggestions[] = [
                'type' => 'underutilized',
                'location' => $location,
                'message' => "Location {$location->location_code} is underutilized",
                'recommendation' => 'Consider consolidating items from this location'
            ];
        }
        
        return $suggestions;
    }
    
    public function generatePickingRoute(Collection $items, int $warehouseId): array
    {
        // Optimize picking route to minimize travel distance
        $locations = $items->map(function ($item) {
            return $item->assignedLocation;
        })->filter()->unique('id');
        
        if ($locations->isEmpty()) {
            return [];
        }
        
        // Simple nearest-neighbor optimization
        // In a real implementation, you might use more sophisticated algorithms
        $route = [];
        $unvisited = $locations->values();
        $current = $unvisited->shift(); // Start with first location
        $route[] = $current;
        
        while ($unvisited->isNotEmpty()) {
            $nearest = $this->findNearestLocation($current, $unvisited);
            $route[] = $nearest;
            $unvisited = $unvisited->reject(function ($loc) use ($nearest) {
                return $loc->id === $nearest->id;
            });
            $current = $nearest;
        }
        
        return $route->toArray();
    }
    
    protected function findNearestLocation(WarehouseLocation $current, Collection $locations): WarehouseLocation
    {
        return $locations->sortBy(function ($location) use ($current) {
            return $this->calculateDistance($current, $location);
        })->first();
    }
    
    protected function calculateDistance(WarehouseLocation $loc1, WarehouseLocation $loc2): float
    {
        // Simple Euclidean distance calculation
        if (!$loc1->coordinate_x || !$loc2->coordinate_x) {
            return 0; // No coordinates available
        }
        
        $dx = $loc1->coordinate_x - $loc2->coordinate_x;
        $dy = $loc1->coordinate_y - $loc2->coordinate_y;
        $dz = ($loc1->coordinate_z ?? 0) - ($loc2->coordinate_z ?? 0);
        
        return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
    }
}
```

---

## ❌ 10.5 TRACKING SERVICE I COURIER API

### ❌ 10.5.1 TrackingService
#### ❌ 10.5.1.1 Główny serwis śledzenia przesyłek
```php
<?php
namespace App\Services\Delivery\Tracking;

use App\Models\Shipment;
use App\Services\Delivery\Tracking\Couriers\CourierInterface;
use App\Services\Delivery\Tracking\Couriers\DPDCourier;
use App\Services\Delivery\Tracking\Couriers\InPostCourier;
use App\Services\Delivery\Tracking\Couriers\UPSCourier;
use App\Jobs\Delivery\UpdateTrackingInfo;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    protected array $couriers = [];
    
    public function __construct()
    {
        $this->initializeCouriers();
    }
    
    protected function initializeCouriers(): void
    {
        $this->couriers = [
            'dpd' => app(DPDCourier::class),
            'inpost' => app(InPostCourier::class),
            'ups' => app(UPSCourier::class)
        ];
    }
    
    public function startTracking(Shipment $shipment): bool
    {
        if (!$shipment->tracking_number || !$shipment->carrier_name) {
            return false;
        }
        
        $courier = $this->getCourierForShipment($shipment);
        if (!$courier) {
            Log::warning('No courier handler found', [
                'shipment_id' => $shipment->id,
                'carrier' => $shipment->carrier_name
            ]);
            return false;
        }
        
        try {
            // Queue tracking update job
            UpdateTrackingInfo::dispatch($shipment)->delay(now()->addMinutes(5));
            
            Log::info('Started tracking for shipment', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->carrier_name
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to start tracking', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function updateTrackingInfo(Shipment $shipment): ?array
    {
        $courier = $this->getCourierForShipment($shipment);
        if (!$courier) {
            return null;
        }
        
        try {
            $trackingData = $courier->getTrackingInfo($shipment->tracking_number);
            
            if (!$trackingData) {
                return null;
            }
            
            // Update shipment status based on tracking data
            $this->updateShipmentFromTrackingData($shipment, $trackingData);
            
            return $trackingData;
            
        } catch (\Exception $e) {
            Log::error('Failed to update tracking info', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    protected function getCourierForShipment(Shipment $shipment): ?CourierInterface
    {
        $carrierName = strtolower($shipment->carrier_name);
        
        foreach ($this->couriers as $name => $courier) {
            if (str_contains($carrierName, $name) || $courier->matchesCarrier($carrierName)) {
                return $courier;
            }
        }
        
        return null;
    }
    
    protected function updateShipmentFromTrackingData(Shipment $shipment, array $trackingData): void
    {
        $newStatus = $this->mapTrackingStatusToShipmentStatus($trackingData['status']);
        
        if ($newStatus && $newStatus !== $shipment->status) {
            $shipment->update([
                'status' => $newStatus,
                'tracking_url' => $trackingData['tracking_url'] ?? $shipment->tracking_url
            ]);
            
            // Log status change with tracking data
            \App\Models\ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'old_status' => $shipment->getOriginal('status'),
                'new_status' => $newStatus,
                'changed_by' => null, // Automatic update
                'change_source' => 'api',
                'tracking_data' => $trackingData,
                'location_info' => $trackingData['location'] ?? null
            ]);
        }
    }
    
    protected function mapTrackingStatusToShipmentStatus(string $courierStatus): ?string
    {
        $statusMappings = [
            'shipped' => 'shipped',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'exception' => 'in_transit', // Keep as in_transit until resolved
            'customs' => 'customs'
        ];
        
        return $statusMappings[$courierStatus] ?? null;
    }
    
    public function handleWebhook(string $carrierName, array $webhookData): bool
    {
        $courier = $this->couriers[strtolower($carrierName)] ?? null;
        
        if (!$courier) {
            Log::warning('Webhook received for unknown carrier', ['carrier' => $carrierName]);
            return false;
        }
        
        try {
            $trackingNumber = $courier->extractTrackingNumberFromWebhook($webhookData);
            
            if (!$trackingNumber) {
                Log::warning('Could not extract tracking number from webhook', [
                    'carrier' => $carrierName,
                    'data' => $webhookData
                ]);
                return false;
            }
            
            $shipment = Shipment::where('tracking_number', $trackingNumber)->first();
            
            if (!$shipment) {
                Log::info('Webhook received for unknown shipment', [
                    'carrier' => $carrierName,
                    'tracking_number' => $trackingNumber
                ]);
                return false;
            }
            
            // Process webhook data
            $trackingData = $courier->parseWebhookData($webhookData);
            $this->updateShipmentFromTrackingData($shipment, $trackingData);
            
            Log::info('Webhook processed successfully', [
                'carrier' => $carrierName,
                'shipment_id' => $shipment->id,
                'tracking_number' => $trackingNumber
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to process webhook', [
                'carrier' => $carrierName,
                'error' => $e->getMessage(),
                'data' => $webhookData
            ]);
            
            return false;
        }
    }
}
```

### ❌ 10.5.2 Courier Interfaces
#### ❌ 10.5.2.1 CourierInterface
```php
<?php
namespace App\Services\Delivery\Tracking\Couriers;

interface CourierInterface
{
    public function getTrackingInfo(string $trackingNumber): ?array;
    public function matchesCarrier(string $carrierName): bool;
    public function extractTrackingNumberFromWebhook(array $webhookData): ?string;
    public function parseWebhookData(array $webhookData): array;
    public function validateTrackingNumber(string $trackingNumber): bool;
}
```

#### ❌ 10.5.2.2 DPDCourier
```php
<?php
namespace App\Services\Delivery\Tracking\Couriers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DPDCourier implements CourierInterface
{
    protected string $apiUrl = 'https://tracking.dpd.de/rest/plc/en_US/';
    protected string $apiKey;
    
    public function __construct()
    {
        $this->apiKey = config('delivery.couriers.dpd.api_key');
    }
    
    public function getTrackingInfo(string $trackingNumber): ?array
    {
        try {
            $response = Http::timeout(30)
                ->get($this->apiUrl . $trackingNumber);
                
            if (!$response->successful()) {
                return null;
            }
            
            $data = $response->json();
            
            return [
                'status' => $this->mapDPDStatus($data['statusCode'] ?? ''),
                'description' => $data['statusText'] ?? '',
                'location' => $data['location'] ?? null,
                'estimated_delivery' => $data['estimatedDeliveryTime'] ?? null,
                'tracking_url' => "https://tracking.dpd.de/parcellifecycle?p1={$trackingNumber}",
                'raw_data' => $data
            ];
            
        } catch (\Exception $e) {
            Log::error('DPD tracking API error', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    protected function mapDPDStatus(string $dpdStatus): string
    {
        $statusMap = [
            '01' => 'shipped',
            '02' => 'in_transit', 
            '03' => 'out_for_delivery',
            '04' => 'delivered',
            '05' => 'exception'
        ];
        
        return $statusMap[$dpdStatus] ?? 'in_transit';
    }
    
    public function matchesCarrier(string $carrierName): bool
    {
        return str_contains(strtolower($carrierName), 'dpd');
    }
    
    public function extractTrackingNumberFromWebhook(array $webhookData): ?string
    {
        return $webhookData['parcelNumber'] ?? null;
    }
    
    public function parseWebhookData(array $webhookData): array
    {
        return [
            'status' => $this->mapDPDStatus($webhookData['statusCode'] ?? ''),
            'description' => $webhookData['statusText'] ?? '',
            'location' => $webhookData['location'] ?? null,
            'timestamp' => $webhookData['timestamp'] ?? now()->toISOString(),
            'raw_data' => $webhookData
        ];
    }
    
    public function validateTrackingNumber(string $trackingNumber): bool
    {
        // DPD tracking numbers are typically 14 digits
        return preg_match('/^\d{14}$/', $trackingNumber);
    }
}
```

---

## ❌ 10.6 WAREHOUSE MOBILE API

### ❌ 10.6.1 MobileWarehouseController
#### ❌ 10.6.1.1 API dla aplikacji Android
```php
<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Delivery\ShipmentService;
use App\Services\Delivery\LocationOptimizationService;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\WarehouseLocation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class MobileWarehouseController extends Controller
{
    protected ShipmentService $shipmentService;
    protected LocationOptimizationService $locationService;
    
    public function __construct(
        ShipmentService $shipmentService,
        LocationOptimizationService $locationService
    ) {
        $this->shipmentService = $shipmentService;
        $this->locationService = $locationService;
        $this->middleware('auth:mobile');
    }
    
    public function getPendingShipments(): JsonResponse
    {
        $shipments = Shipment::with([
            'items.product',
            'containers',
            'destinationWarehouse'
        ])
        ->whereIn('status', ['delivered', 'partially_received'])
        ->where('assigned_to_user_id', auth()->id())
        ->orWhere('destination_warehouse_id', auth()->user()->default_warehouse_id)
        ->orderBy('estimated_delivery_at')
        ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $shipments->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'supplier_name' => $shipment->supplier_name,
                    'status' => $shipment->status,
                    'estimated_delivery' => $shipment->estimated_delivery_at,
                    'items_count' => $shipment->items->count(),
                    'received_items_count' => $shipment->items->where('status', 'fully_received')->count(),
                    'containers' => $shipment->containers->map(function ($container) {
                        return [
                            'id' => $container->id,
                            'container_number' => $container->container_number,
                            'type' => $container->container_type,
                            'status' => $container->status
                        ];
                    })
                ];
            })
        ]);
    }
    
    public function getShipmentDetails(int $shipmentId): JsonResponse
    {
        $shipment = Shipment::with([
            'items.product.images',
            'items.assignedLocation',
            'containers'
        ])->findOrFail($shipmentId);
        
        // Check access permissions
        if (!$this->canAccessShipment($shipment)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'supplier_name' => $shipment->supplier_name,
                'status' => $shipment->status,
                'notes' => $shipment->notes,
                'items' => $shipment->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'sku' => $item->product->sku,
                            'name' => $item->product->name,
                            'image_url' => $item->product->images->first()?->url
                        ],
                        'expected_quantity' => $item->expected_quantity,
                        'received_quantity' => $item->received_quantity,
                        'status' => $item->status,
                        'assigned_location' => $item->assignedLocation ? [
                            'id' => $item->assignedLocation->id,
                            'code' => $item->assignedLocation->location_code,
                            'name' => $item->assignedLocation->name
                        ] : null,
                        'requires_inspection' => $item->requires_inspection
                    ];
                })
            ]
        ]);
    }
    
    public function receiveItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:shipment_items,id',
            'received_quantity' => 'required|integer|min:1',
            'location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'notes' => 'nullable|string|max:1000',
            'scanned_barcode' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $item = ShipmentItem::findOrFail($request->item_id);
        
        // Check access permissions
        if (!$this->canAccessShipment($item->shipment)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }
        
        // Validate quantity
        $remainingQuantity = $item->expected_quantity - $item->received_quantity;
        if ($request->received_quantity > $remainingQuantity) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot receive {$request->received_quantity} items. Only {$remainingQuantity} remaining."
            ], 422);
        }
        
        // Auto-assign location if not provided
        $locationId = $request->location_id;
        if (!$locationId && !$item->assigned_location_id) {
            $location = $this->locationService->findOptimalLocation(
                $item->product,
                $request->received_quantity,
                $item->shipment->destination_warehouse_id
            );
            $locationId = $location?->id;
        }
        
        $success = $this->shipmentService->receiveShipmentItem($item, $request->received_quantity, [
            'notes' => $request->notes,
            'scanned_barcode' => $request->scanned_barcode,
            'auto_assign_location' => !$request->location_id,
            'device_info' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]
        ]);
        
        if ($success) {
            $item->refresh();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Item received successfully',
                'data' => [
                    'item_id' => $item->id,
                    'received_quantity' => $item->received_quantity,
                    'status' => $item->status,
                    'assigned_location' => $item->assignedLocation ? [
                        'id' => $item->assignedLocation->id,
                        'code' => $item->assignedLocation->location_code,
                        'name' => $item->assignedLocation->name
                    ] : null
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to receive item'
            ], 500);
        }
    }
    
    public function scanBarcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:255',
            'scan_type' => 'required|in:product,location,container'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $barcode = $request->barcode;
        $scanType = $request->scan_type;
        
        switch ($scanType) {
            case 'product':
                $product = Product::where('sku', $barcode)
                    ->orWhere('ean', $barcode)
                    ->orWhere('manufacturer_code', $barcode)
                    ->first();
                    
                if ($product) {
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'type' => 'product',
                            'id' => $product->id,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'image_url' => $product->images->first()?->url
                        ]
                    ]);
                }
                break;
                
            case 'location':
                $location = WarehouseLocation::where('barcode', $barcode)
                    ->orWhere('qr_code', $barcode)
                    ->orWhere('location_code', $barcode)
                    ->first();
                    
                if ($location) {
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'type' => 'location',
                            'id' => $location->id,
                            'code' => $location->location_code,
                            'name' => $location->name,
                            'available_space' => $location->capacity_cubic_cm - $location->current_volume_cubic_cm
                        ]
                    ]);
                }
                break;
                
            case 'container':
                $container = \App\Models\Container::where('container_number', $barcode)->first();
                
                if ($container) {
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'type' => 'container',
                            'id' => $container->id,
                            'container_number' => $container->container_number,
                            'shipment_number' => $container->shipment->shipment_number,
                            'status' => $container->status
                        ]
                    ]);
                }
                break;
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Barcode not found'
        ], 404);
    }
    
    public function getLocations(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id ?? auth()->user()->default_warehouse_id;
        
        $locations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('location_code')
            ->get(['id', 'location_code', 'name', 'location_type']);
            
        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }
    
    public function moveItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'from_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'to_location_id' => 'required|integer|exists:warehouse_locations,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Validate movement is allowed
            $product = Product::findOrFail($request->product_id);
            $toLocation = WarehouseLocation::findOrFail($request->to_location_id);
            
            // Check if target location has enough space
            $requiredSpace = ($product->width ?? 10) * ($product->height ?? 10) * ($product->depth ?? 10) * $request->quantity;
            $availableSpace = $toLocation->capacity_cubic_cm - $toLocation->current_volume_cubic_cm;
            
            if ($requiredSpace > $availableSpace) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough space in target location'
                ], 422);
            }
            
            // Record movement
            \App\Models\LocationMovement::create([
                'movable_type' => Product::class,
                'movable_id' => $product->id,
                'quantity' => $request->quantity,
                'from_location_id' => $request->from_location_id,
                'to_location_id' => $request->to_location_id,
                'movement_type' => 'transfer',
                'reason' => $request->reason,
                'performed_by' => auth()->id(),
                'device_info' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            // Update product stock locations
            if ($request->from_location_id) {
                // Move from specific location
                $fromStock = $product->stock()->where('location_id', $request->from_location_id)->first();
                if ($fromStock && $fromStock->quantity >= $request->quantity) {
                    $fromStock->decrement('quantity', $request->quantity);
                }
            }
            
            // Add to target location
            $product->stock()->updateOrCreate(
                ['location_id' => $request->to_location_id],
                ['warehouse_code' => $toLocation->warehouse->code]
            )->increment('quantity', $request->quantity);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Item moved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to move item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    protected function canAccessShipment(Shipment $shipment): bool
    {
        $user = auth()->user();
        
        // Admin and managers can access all shipments
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }
        
        // Assigned user can access
        if ($shipment->assigned_to_user_id === $user->id) {
            return true;
        }
        
        // Users from the same warehouse can access
        if ($shipment->destination_warehouse_id === $user->default_warehouse_id) {
            return true;
        }
        
        return false;
    }
}
```

---

## ❌ 10.7 LIVEWIRE COMPONENTS DOSTAW

### ❌ 10.7.1 ShipmentManager Component
#### ❌ 10.7.1.1 Główny komponent zarządzania dostawami
```php
<?php
namespace App\Livewire\Delivery;

use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\Delivery\ShipmentService;
use Livewire\Component;
use Livewire\WithPagination;

class ShipmentManager extends Component
{
    use WithPagination;
    
    public $selectedStatus = '';
    public $selectedWarehouse = '';
    public $searchTerm = '';
    public $dateFrom = '';
    public $dateTo = '';
    
    // Modal properties
    public $showCreateModal = false;
    public $editingShipment = null;
    
    // Form properties
    public $supplierName = '';
    public $shipmentType = 'purchase_order';
    public $estimatedDelivery = '';
    public $notes = '';
    public $destinationWarehouse = '';
    
    protected $queryString = [
        'selectedStatus' => ['except' => ''],
        'selectedWarehouse' => ['except' => ''],
        'searchTerm' => ['except' => '']
    ];
    
    protected $rules = [
        'supplierName' => 'required|min:3|max:255',
        'shipmentType' => 'required|in:purchase_order,return,transfer,sample',
        'estimatedDelivery' => 'nullable|date|after:today',
        'destinationWarehouse' => 'required|exists:warehouses,id',
        'notes' => 'nullable|max:1000'
    ];
    
    public function mount()
    {
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->addWeek()->format('Y-m-d');
    }
    
    public function render()
    {
        $query = Shipment::with(['destinationWarehouse', 'assignedToUser'])
            ->when($this->selectedStatus, fn($q) => $q->where('status', $this->selectedStatus))
            ->when($this->selectedWarehouse, fn($q) => $q->where('destination_warehouse_id', $this->selectedWarehouse))
            ->when($this->searchTerm, function($q) {
                $q->where(function($query) {
                    $query->where('shipment_number', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('supplier_name', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('tracking_number', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->when($this->dateFrom, fn($q) => $q->where('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc');
            
        $shipments = $query->paginate(20);
        
        $warehouses = Warehouse::active()->get(['id', 'name']);
        
        $statusCounts = [
            'ordered' => Shipment::where('status', 'ordered')->count(),
            'shipped' => Shipment::where('status', 'shipped')->count(),
            'delivered' => Shipment::where('status', 'delivered')->count(),
            'partially_received' => Shipment::where('status', 'partially_received')->count(),
        ];
        
        return view('livewire.delivery.shipment-manager', [
            'shipments' => $shipments,
            'warehouses' => $warehouses,
            'statusCounts' => $statusCounts
        ]);
    }
    
    public function createShipment(ShipmentService $shipmentService)
    {
        $this->validate();
        
        try {
            $shipmentData = [
                'supplier_name' => $this->supplierName,
                'shipment_type' => $this->shipmentType,
                'estimated_delivery_at' => $this->estimatedDelivery ? \Carbon\Carbon::parse($this->estimatedDelivery) : null,
                'destination_warehouse_id' => $this->destinationWarehouse,
                'notes' => $this->notes
            ];
            
            $shipment = $shipmentService->createShipment($shipmentData);
            
            session()->flash('message', 'Dostawa została utworzona: ' . $shipment->shipment_number);
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas tworzenia dostawy: ' . $e->getMessage());
        }
    }
    
    public function updateShipmentStatus($shipmentId, $newStatus, ShipmentService $shipmentService)
    {
        try {
            $shipment = Shipment::findOrFail($shipmentId);
            $success = $shipmentService->updateShipmentStatus($shipment, $newStatus);
            
            if ($success) {
                session()->flash('message', 'Status dostawy został zaktualizowany');
            } else {
                session()->flash('error', 'Nie udało się zaktualizować statusu');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd: ' . $e->getMessage());
        }
    }
    
    public function assignToUser($shipmentId, $userId)
    {
        try {
            $shipment = Shipment::findOrFail($shipmentId);
            $shipment->update([
                'assigned_to_user_id' => $userId,
                'updated_by' => auth()->id()
            ]);
            
            session()->flash('message', 'Dostawa została przypisana');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd przypisania: ' . $e->getMessage());
        }
    }
    
    public function deleteShipment($shipmentId)
    {
        try {
            $shipment = Shipment::findOrFail($shipmentId);
            
            // Only allow deletion of ordered shipments
            if ($shipment->status !== 'ordered') {
                session()->flash('error', 'Można usuwać tylko dostawy w statusie "Zamówiono"');
                return;
            }
            
            $shipment->delete();
            session()->flash('message', 'Dostawa została usunięta');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd usuwania: ' . $e->getMessage());
        }
    }
    
    public function exportShipments()
    {
        // Queue export job
        \App\Jobs\Delivery\ExportShipmentsJob::dispatch(
            auth()->id(),
            $this->getFilters()
        );
        
        session()->flash('message', 'Eksport został rozpoczęty. Otrzymasz powiadomienie gdy będzie gotowy.');
    }
    
    protected function getFilters(): array
    {
        return [
            'status' => $this->selectedStatus,
            'warehouse' => $this->selectedWarehouse,
            'search' => $this->searchTerm,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo
        ];
    }
    
    protected function resetForm()
    {
        $this->supplierName = '';
        $this->shipmentType = 'purchase_order';
        $this->estimatedDelivery = '';
        $this->notes = '';
        $this->destinationWarehouse = '';
        $this->showCreateModal = false;
        $this->editingShipment = null;
    }
    
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }
    
    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }
    
    public function updatedSelectedWarehouse()
    {
        $this->resetPage();
    }
}
```

---

## ❌ 10.8 JOBS I AUTOMATYZACJA

### ❌ 10.8.1 UpdateTrackingInfo Job
#### ❌ 10.8.1.1 Automatyczne aktualizacje śledzenia
```php
<?php
namespace App\Jobs\Delivery;

use App\Models\Shipment;
use App\Services\Delivery\Tracking\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTrackingInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected Shipment $shipment;
    
    public int $tries = 3;
    public int $timeout = 60;
    
    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }
    
    public function handle(TrackingService $trackingService): void
    {
        // Skip if shipment is already delivered or cancelled
        if (in_array($this->shipment->status, ['fully_received', 'cancelled'])) {
            return;
        }
        
        $trackingData = $trackingService->updateTrackingInfo($this->shipment);
        
        if ($trackingData) {
            Log::info('Tracking info updated', [
                'shipment_id' => $this->shipment->id,
                'status' => $trackingData['status']
            ]);
            
            // Schedule next update if not delivered
            if (!in_array($trackingData['status'], ['delivered', 'exception'])) {
                self::dispatch($this->shipment)->delay(now()->addHours(2));
            }
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('Tracking update failed', [
            'shipment_id' => $this->shipment->id,
            'error' => $exception->getMessage()
        ]);
        
        // Retry with longer delay
        self::dispatch($this->shipment)->delay(now()->addHours(6));
    }
}
```

---

## ❌ 10.9 TESTY I DOKUMENTACJA

### ❌ 10.9.1 Testy dostaw
#### ❌ 10.9.1.1 ShipmentServiceTest
```php
<?php
namespace Tests\Feature\Delivery;

use Tests\TestCase;
use App\Models\Shipment;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Delivery\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShipmentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected ShipmentService $shipmentService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->shipmentService = app(ShipmentService::class);
    }
    
    public function testCanCreateShipment()
    {
        $warehouse = Warehouse::factory()->create();
        
        $shipmentData = [
            'supplier_name' => 'Test Supplier',
            'shipment_type' => 'purchase_order',
            'destination_warehouse_id' => $warehouse->id,
            'notes' => 'Test shipment'
        ];
        
        $shipment = $this->shipmentService->createShipment($shipmentData);
        
        $this->assertNotNull($shipment);
        $this->assertDatabaseHas('shipments', [
            'supplier_name' => 'Test Supplier',
            'status' => 'ordered'
        ]);
    }
    
    public function testCanUpdateShipmentStatus()
    {
        $shipment = Shipment::factory()->create(['status' => 'ordered']);
        
        $result = $this->shipmentService->updateShipmentStatus($shipment, 'shipped', [
            'tracking_number' => 'TEST123456'
        ]);
        
        $this->assertTrue($result);
        $this->assertEquals('shipped', $shipment->fresh()->status);
        $this->assertEquals('TEST123456', $shipment->fresh()->tracking_number);
    }
}
```

---

## 📊 METRYKI ETAPU

**Szacowany czas realizacji:** 50 godzin  
**Liczba plików do utworzenia:** ~25  
**Liczba testów:** ~15  
**Liczba tabel MySQL:** 7 głównych + indeksy  
**API endpoints:** ~15  
**Mobile API endpoints:** ~8  

---

## 🔍 DEFINICJA GOTOWOŚCI (DoD)

Etap zostanie uznany za ukończony gdy:

- ✅ Wszystkie zadania mają status ✅
- ✅ System zarządzania dostawami i kontenerami działa kompletnie
- ✅ API mobile dla Android jest funkcjonalne i przetestowane
- ✅ Śledzenie przesyłek przez kurierów działa automatycznie
- ✅ System lokalizacji magazynowych optymalizuje rozmieszczenie
- ✅ Integracje z ERP synchronizują dostawy
- ✅ Panel Livewire dla Magazynierów jest funkcjonalny
- ✅ Wszystkie testy przechodzą poprawnie
- ✅ Kod przesłany na serwer produkcyjny i przetestowany
- ✅ Dokumentacja API mobile jest kompletna

---

**Autor:** Claude Code AI  
**Data utworzenia:** 2025-09-05  
**Ostatnia aktualizacja:** 2025-09-05  
**Status:** ❌ NIEROZPOCZĘTY