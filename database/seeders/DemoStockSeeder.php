<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Services\StockTransferService;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

/**
 * Demo Stock Seeder - Stock Management System Testing Data
 *
 * STOCK MANAGEMENT SYSTEM - Comprehensive test data dla development/testing
 *
 * Business Logic:
 * - Realistic stock levels dla all MPP warehouses
 * - Complete movement history z różnymi typami operacji
 * - Active reservations z priority levels
 * - Cost tracking i delivery status simulation
 * - Low stock scenarios dla alert testing
 * - Transfer history between warehouses
 *
 * Data Scope:
 * - ~50 products z stock records
 * - 6 warehouses z diversified stock levels
 * - 200+ stock movements (last 90 days)
 * - 30+ active reservations
 * - Various delivery statuses i scenarios
 *
 * @package Database\Seeders
 * @version STOCK MANAGEMENT SYSTEM
 * @since 2025-09-17
 */
class DemoStockSeeder extends Seeder
{
    /**
     * Faker instance dla realistic data generation
     *
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * Stock transfer service dla realistic transfer simulation
     *
     * @var \App\Services\StockTransferService
     */
    private $transferService;

    /**
     * Reference data arrays
     */
    private array $containerNumbers = [];
    private array $deliveryReasons = [];
    private array $reservationReasons = [];

    /**
     * Run the demo stock seeder
     */
    public function run(): void
    {
        $this->faker = Faker::create('pl_PL');
        $this->transferService = new StockTransferService();

        $this->initializeReferenceData();

        $this->command->info('🏭 Creating Demo Stock Management Data...');

        try {
            DB::transaction(function () {
                // Step 1: Create initial stock records
                $this->createInitialStockRecords();

                // Step 2: Generate historical movements
                $this->generateStockMovementHistory();

                // Step 3: Create active reservations
                $this->createActiveReservations();

                // Step 4: Simulate transfers between warehouses
                $this->simulateWarehouseTransfers();

                // Step 5: Create low stock scenarios
                $this->createLowStockScenarios();

                // Step 6: Update delivery statuses
                $this->updateDeliveryStatuses();
            });

            $this->verifyDemoData();

        } catch (\Exception $e) {
            $this->command->error("❌ Demo stock seeding failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Initialize reference data arrays
     */
    private function initializeReferenceData(): void
    {
        $this->containerNumbers = [
            'CONT-2025-001', 'CONT-2025-002', 'CONT-2025-003',
            'ASIA-240815', 'ASIA-240916', 'ASIA-241015',
            'EUR-250101', 'EUR-250201', 'EUR-250301',
            'SPEC-DEL-001', 'SPEC-DEL-002'
        ];

        $this->deliveryReasons = [
            'Dostawa z magazynu głównego',
            'Import z Azji - kontener',
            'Zakup od dostawcy lokalnego',
            'Zwrot od klienta - sprawny',
            'Transfer między magazynami',
            'Korekta inwentaryzacyjna',
            'Uzupełnienie stock minimum',
            'Naprawa gwarancyjna - wymiana'
        ];

        $this->reservationReasons = [
            'Zamówienie klienta B2B',
            'Rezerwacja na targach',
            'Próbka dla klienta premium',
            'Zamówienie przedpłatowe',
            'Rezerwacja warsztatowa',
            'Transfer do oddziału',
            'Zamówienie specjalne',
            'Prezent firmowy'
        ];
    }

    /**
     * Create initial stock records dla all products in warehouses
     */
    private function createInitialStockRecords(): void
    {
        $this->command->info('📦 Creating initial stock records...');

        $products = Product::limit(50)->get();
        $warehouses = Warehouse::active()->get();

        $stockCount = 0;

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // Skip some combinations dla realistic distribution
                if ($this->faker->boolean(20)) { // 20% chance to skip
                    continue;
                }

                $baseQuantity = $this->getRealisticStockQuantity($warehouse);
                $minimumStock = $this->getRealisticMinimumStock($warehouse);

                $stock = ProductStock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $baseQuantity,
                    'reserved_quantity' => 0,
                    'minimum_stock' => $minimumStock,
                    'maximum_stock' => $minimumStock * 5,
                    'reorder_point' => intval($minimumStock * 1.5),
                    'reorder_quantity' => $minimumStock * 3,
                    'warehouse_location' => $this->generateWarehouseLocation(),
                    'bin_location' => $this->faker->bothify('?##-??##'),
                    'delivery_status' => $this->faker->randomElement(['available', 'in_transit', 'ordered']),
                    'average_cost' => $this->faker->randomFloat(2, 10, 500),
                    'last_cost' => $this->faker->randomFloat(2, 8, 550),
                    'last_cost_update' => $this->faker->dateTimeBetween('-30 days'),
                    'low_stock_alert' => true,
                    'out_of_stock_alert' => true,
                    'is_active' => true,
                    'track_stock' => true,
                    'allow_negative' => $warehouse->allow_negative_stock,
                    'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
                    'created_by' => 1,
                ]);

                $stockCount++;

                // Create initial stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'product_stock_id' => $stock->id,
                    'movement_type' => 'in',
                    'quantity_before' => 0,
                    'quantity_change' => $baseQuantity,
                    'quantity_after' => $baseQuantity,
                    'reserved_before' => 0,
                    'reserved_after' => 0,
                    'unit_cost' => $stock->average_cost,
                    'total_cost' => $baseQuantity * $stock->average_cost,
                    'currency' => 'PLN',
                    'reference_type' => 'inventory',
                    'reference_id' => 'INITIAL-STOCK-' . date('Ymd'),
                    'reason' => 'Początkowy stan magazynowy',
                    'is_automatic' => false,
                    'created_by' => 1,
                    'movement_date' => $this->faker->dateTimeBetween('-60 days', '-30 days'),
                ]);
            }
        }

        $this->command->info("✅ Created {$stockCount} stock records");
    }

    /**
     * Generate historical stock movements
     */
    private function generateStockMovementHistory(): void
    {
        $this->command->info('📈 Generating stock movement history...');

        $stockRecords = ProductStock::with(['product', 'warehouse'])->limit(100)->get();
        $movementCount = 0;

        foreach ($stockRecords as $stock) {
            $movementsToCreate = $this->faker->numberBetween(3, 8);

            for ($i = 0; $i < $movementsToCreate; $i++) {
                $movementType = $this->faker->randomElement([
                    'in', 'in', 'in', // More inbound than outbound
                    'out', 'out',
                    'adjustment',
                    'return'
                ]);

                $quantityChange = $this->getRealisticQuantityChange($movementType);
                $newQuantity = max(0, $stock->quantity + $quantityChange);

                // Update stock dla next movement
                $quantityBefore = $stock->quantity;
                $stock->quantity = $newQuantity;

                $movement = StockMovement::create([
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'product_stock_id' => $stock->id,
                    'movement_type' => $movementType,
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $quantityChange,
                    'quantity_after' => $newQuantity,
                    'reserved_before' => $stock->reserved_quantity,
                    'reserved_after' => $stock->reserved_quantity,
                    'unit_cost' => $this->faker->randomFloat(2, 5, 400),
                    'currency' => 'PLN',
                    'reference_type' => $this->getMovementReferenceType($movementType),
                    'reference_id' => $this->generateReferenceId($movementType),
                    'container_number' => $this->faker->boolean(40) ? $this->faker->randomElement($this->containerNumbers) : null,
                    'delivery_date' => $movementType === 'in' ? $this->faker->dateTimeBetween('-30 days') : null,
                    'reason' => $this->faker->randomElement($this->deliveryReasons),
                    'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
                    'is_automatic' => $this->faker->boolean(30),
                    'created_by' => $this->faker->randomElement([1, 2, 3]),
                    'movement_date' => $this->faker->dateTimeBetween('-90 days', 'now'),
                ]);

                $movement->total_cost = abs($quantityChange) * $movement->unit_cost;
                $movement->save();

                $movementCount++;
            }

            $stock->save(); // Update final stock quantity
        }

        $this->command->info("✅ Generated {$movementCount} stock movements");
    }

    /**
     * Create active reservations
     */
    private function createActiveReservations(): void
    {
        $this->command->info('🔒 Creating active reservations...');

        $stockRecords = ProductStock::where('quantity', '>', 5)->limit(30)->get();
        $reservationCount = 0;

        foreach ($stockRecords as $stock) {
            if ($this->faker->boolean(60)) { // 60% chance to have reservation
                $maxReservable = intval($stock->available_quantity * 0.7); // Reserve up to 70%
                $quantityToReserve = $this->faker->numberBetween(1, max(1, $maxReservable));

                $reservation = StockReservation::create([
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'product_stock_id' => $stock->id,
                    'reservation_type' => $this->faker->randomElement(['order', 'quote', 'pre_order', 'sample']),
                    'quantity_requested' => $quantityToReserve,
                    'quantity_reserved' => $quantityToReserve,
                    'quantity_fulfilled' => 0,
                    'status' => $this->faker->randomElement(['pending', 'confirmed', 'confirmed', 'partial']),
                    'priority' => $this->faker->randomElement([1, 2, 3, 4, 5, 5, 6, 7]),
                    'auto_release' => $this->faker->boolean(80),
                    'reference_type' => 'sales_order',
                    'reference_id' => 'SO-' . $this->faker->unique()->numerify('2025-####'),
                    'customer_id' => $this->faker->numerify('CUST-####'),
                    'customer_name' => $this->faker->company(),
                    'sales_person' => $this->faker->name(),
                    'unit_price' => $this->faker->randomFloat(2, 20, 800),
                    'currency' => 'PLN',
                    'requested_delivery_date' => $this->faker->dateTimeBetween('now', '+30 days'),
                    'promised_delivery_date' => $this->faker->dateTimeBetween('+5 days', '+45 days'),
                    'reason' => $this->faker->randomElement($this->reservationReasons),
                    'allow_partial' => $this->faker->boolean(70),
                    'notify_expiry' => true,
                    'reserved_by' => $this->faker->randomElement([1, 2, 3]),
                    'reserved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                    'expires_at' => $this->faker->dateTimeBetween('+1 day', '+14 days'),
                ]);

                $reservation->total_value = $quantityToReserve * $reservation->unit_price;
                $reservation->save();

                // Update stock reserved quantity
                $stock->reserved_quantity += $quantityToReserve;
                $stock->save();

                $reservationCount++;
            }
        }

        $this->command->info("✅ Created {$reservationCount} reservations");
    }

    /**
     * Simulate realistic transfers between warehouses
     */
    private function simulateWarehouseTransfers(): void
    {
        $this->command->info('🔄 Simulating warehouse transfers...');

        $transfersCreated = 0;
        $warehouses = Warehouse::active()->get();

        // Create some realistic transfer scenarios
        $stocksForTransfer = ProductStock::where('quantity', '>', 20)->limit(15)->get();

        foreach ($stocksForTransfer as $sourceStock) {
            if ($this->faker->boolean(50)) { // 50% chance dla transfer
                $targetWarehouse = $warehouses->where('id', '!=', $sourceStock->warehouse_id)
                                            ->random();

                $transferQuantity = $this->faker->numberBetween(2, intval($sourceStock->quantity * 0.3));

                $result = $this->transferService->transferProduct(
                    $sourceStock->product_id,
                    null, // no variant
                    $sourceStock->warehouse_id,
                    $targetWarehouse->id,
                    $transferQuantity,
                    [
                        'reason' => 'Redistribution między magazynami',
                        'reference_id' => 'TRANSFER-' . date('Ymd') . '-' . $transfersCreated,
                        'notes' => 'Automatyczny transfer - test data',
                        'user_id' => 1,
                        'is_automatic' => false,
                    ]
                );

                if ($result['status'] === 'success') {
                    $transfersCreated++;
                }
            }
        }

        $this->command->info("✅ Completed {$transfersCreated} transfers");
    }

    /**
     * Create low stock scenarios dla alert testing
     */
    private function createLowStockScenarios(): void
    {
        $this->command->info('⚠️  Creating low stock scenarios...');

        $stocksToModify = ProductStock::where('quantity', '>', 5)->limit(10)->get();
        $lowStockCount = 0;

        foreach ($stocksToModify as $stock) {
            // Make some items low stock
            $newQuantity = $this->faker->numberBetween(0, $stock->minimum_stock);
            $quantityChange = $newQuantity - $stock->quantity;

            // Create adjustment movement
            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'product_stock_id' => $stock->id,
                'movement_type' => 'adjustment',
                'quantity_before' => $stock->quantity,
                'quantity_change' => $quantityChange,
                'quantity_after' => $newQuantity,
                'reserved_before' => $stock->reserved_quantity,
                'reserved_after' => $stock->reserved_quantity,
                'reference_type' => 'adjustment',
                'reference_id' => 'LOW-STOCK-TEST-' . $stock->id,
                'reason' => 'Tworzenie scenariusza niskiego stanu (test data)',
                'is_automatic' => false,
                'created_by' => 1,
                'movement_date' => now(),
            ]);

            $stock->quantity = $newQuantity;
            $stock->save();

            $lowStockCount++;
        }

        $this->command->info("✅ Created {$lowStockCount} low stock scenarios");
    }

    /**
     * Update delivery statuses realistically
     */
    private function updateDeliveryStatuses(): void
    {
        $this->command->info('🚚 Updating delivery statuses...');

        $statuses = [
            'available' => 60,    // 60% probability
            'in_transit' => 15,   // 15% probability
            'ordered' => 10,      // 10% probability
            'receiving' => 8,     // 8% probability
            'in_container' => 7,  // 7% probability
        ];

        $stocks = ProductStock::all();
        $updatedCount = 0;

        foreach ($stocks as $stock) {
            $randomValue = $this->faker->numberBetween(1, 100);
            $cumulativeProbability = 0;

            foreach ($statuses as $status => $probability) {
                $cumulativeProbability += $probability;
                if ($randomValue <= $cumulativeProbability) {
                    $stock->delivery_status = $status;

                    // Add realistic delivery context
                    if ($status === 'in_container') {
                        $stock->container_number = $this->faker->randomElement($this->containerNumbers);
                        $stock->expected_delivery_date = $this->faker->dateTimeBetween('+5 days', '+20 days');
                    } elseif ($status === 'in_transit') {
                        $stock->expected_delivery_date = $this->faker->dateTimeBetween('+1 day', '+7 days');
                    } elseif ($status === 'receiving') {
                        $stock->last_delivery_date = $this->faker->dateTimeBetween('-3 days', 'now');
                    }

                    $stock->save();
                    $updatedCount++;
                    break;
                }
            }
        }

        $this->command->info("✅ Updated {$updatedCount} delivery statuses");
    }

    /**
     * Verify demo data creation
     */
    private function verifyDemoData(): void
    {
        $this->command->info('🔍 Verifying demo data...');

        $stats = [
            'stock_records' => ProductStock::count(),
            'total_movements' => StockMovement::count(),
            'active_reservations' => StockReservation::active()->count(),
            'low_stock_items' => ProductStock::lowStock()->count(),
            'products_with_stock' => ProductStock::distinct('product_id')->count(),
            'warehouses_with_stock' => ProductStock::distinct('warehouse_id')->count(),
        ];

        $this->command->info('📊 DEMO DATA STATISTICS:');
        $this->command->info('────────────────────────────────────────');

        foreach ($stats as $metric => $value) {
            $this->command->info(sprintf('%-20s: %d', ucwords(str_replace('_', ' ', $metric)), $value));
        }

        // Test stock management features
        $this->testStockManagementFeatures();

        $this->command->info('────────────────────────────────────────');
        $this->command->info('✅ Demo Stock Management Data created successfully!');
        $this->command->info('🎯 Ready for testing Stock Management System functionality');
    }

    /**
     * Test key stock management features
     */
    private function testStockManagementFeatures(): void
    {
        $this->command->info('🧪 Testing Stock Management features...');

        // Test product stock methods
        $product = Product::with('activeStock')->first();
        if ($product) {
            $totalStock = $product->getTotalAvailableStock();
            $warehousesWithStock = $product->getWarehousesWithStock()->count();
            $this->command->info("Sample product total stock: {$totalStock} across {$warehousesWithStock} warehouses");
        }

        // Test warehouse methods
        $warehouse = Warehouse::with('stock')->first();
        if ($warehouse) {
            $totalProducts = $warehouse->total_products;
            $this->command->info("Sample warehouse has {$totalProducts} different products");
        }

        // Test transfer service
        $stockTransferService = new StockTransferService();
        if ($product) {
            $availableStock = $stockTransferService->getAvailableStock($product->id);
            $this->command->info("Transfer service: Product available in {$availableStock['total_available']} units");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    private function getRealisticStockQuantity(Warehouse $warehouse): int
    {
        // Different stock levels per warehouse type
        $baseRanges = [
            'mpptrade' => [20, 100],   // Main warehouse - higher stock
            'pitbike' => [5, 50],      // Specialized - medium stock
            'cameraman' => [1, 20],    // High-value items - lower stock
            'otopit' => [10, 80],      // Automotive - varied stock
            'infms' => [5, 30],        // Industrial - medium stock
            'returns' => [0, 10],      // Returns - low stock
        ];

        $range = $baseRanges[$warehouse->code] ?? [5, 50];
        return $this->faker->numberBetween($range[0], $range[1]);
    }

    private function getRealisticMinimumStock(Warehouse $warehouse): int
    {
        $baseMins = [
            'mpptrade' => [5, 15],
            'pitbike' => [2, 8],
            'cameraman' => [1, 3],
            'otopit' => [3, 12],
            'infms' => [2, 8],
            'returns' => [0, 2],
        ];

        $range = $baseMins[$warehouse->code] ?? [2, 8];
        return $this->faker->numberBetween($range[0], $range[1]);
    }

    private function generateWarehouseLocation(): string
    {
        $locations = [];
        $locationCount = $this->faker->numberBetween(1, 3);

        for ($i = 0; $i < $locationCount; $i++) {
            $locations[] = $this->faker->bothify('?##-?##');
        }

        return implode(';', $locations);
    }

    private function getRealisticQuantityChange(string $movementType): int
    {
        $ranges = [
            'in' => [1, 50],
            'out' => [-30, -1],
            'adjustment' => [-10, 10],
            'return' => [1, 5],
        ];

        $range = $ranges[$movementType] ?? [1, 10];
        return $this->faker->numberBetween($range[0], $range[1]);
    }

    private function getMovementReferenceType(string $movementType): string
    {
        $types = [
            'in' => ['purchase_order', 'delivery', 'container', 'return'],
            'out' => ['order', 'adjustment', 'damage'],
            'adjustment' => ['adjustment', 'inventory'],
            'return' => ['return'],
        ];

        $typeOptions = $types[$movementType] ?? ['adjustment'];
        return $this->faker->randomElement($typeOptions);
    }

    private function generateReferenceId(string $movementType): string
    {
        $prefixes = [
            'in' => ['PO-', 'DEL-', 'CONT-', 'RET-'],
            'out' => ['SO-', 'ADJ-', 'DAM-'],
            'adjustment' => ['ADJ-', 'INV-'],
            'return' => ['RET-'],
        ];

        $prefixOptions = $prefixes[$movementType] ?? ['REF-'];
        $prefix = $this->faker->randomElement($prefixOptions);

        return $prefix . date('Y') . '-' . $this->faker->numerify('####');
    }
}