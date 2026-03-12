<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

/**
 * Warehouse Seeder - Production-ready data dla PPM-CC-Laravel
 * 
 * FAZA B: Pricing & Inventory System - Warehouses Initialization
 * 
 * Business Data:
 * - 6 głównych magazynów PPM zgodnie z rzeczywistą strukturą organizacyjną
 * - MPPTRADE jako magazyn główny (default)
 * - Integration-ready mapping dla PrestaShop stores i ERP systems
 * - Realistic operational settings i contact information
 * 
 * @package Database\Seeders
 * @version FAZA B
 * @since 2024-09-09
 */
class WarehouseSeeder extends Seeder
{
    /**
     * Production-ready warehouses dla MPP TRADE
     * 
     * Struktura zgodna z rzeczywistymi magazynami organizacji:
     * - MPPTRADE - magazyn główny (default)
     * - Specialized warehouses dla różnych brand'ów
     * - Reklamacje - dedicated warehouse dla returns
     */
    private const WAREHOUSES = [
        [
            'name' => 'MPPTRADE',
            'code' => 'mpptrade',
            'is_default' => true, // Magazyn główny
            'address' => 'ul. Przykładowa 123',
            'city' => 'Warszawa',
            'postal_code' => '00-123',
            'country' => 'PL',
            'sort_order' => 1,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 5,
            'contact_person' => 'Jan Kowalski',
            'phone' => '+48 22 123 45 67',
            'email' => 'magazyn@mpptrade.pl',
            'operating_hours' => 'Poniedziałek-Piątek: 8:00-16:00',
            'notes' => 'Magazyn główny MPP TRADE - centrum dystrybucyjne',
        ],
        [
            'name' => 'Pitbike.pl',
            'code' => 'pitbike',
            'is_default' => false,
            'address' => 'ul. Motocyklowa 45',
            'city' => 'Kraków',
            'postal_code' => '30-456',
            'country' => 'PL',
            'sort_order' => 2,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 3,
            'contact_person' => 'Anna Nowak',
            'phone' => '+48 12 345 67 89',
            'email' => 'magazyn@pitbike.pl',
            'operating_hours' => 'Poniedziałek-Piątek: 9:00-17:00',
            'special_instructions' => 'Specjalizacja w części do pitbike i ATV',
            'notes' => 'Magazyn dedykowany dla brand\'u Pitbike.pl',
        ],
        [
            'name' => 'Cameraman',
            'code' => 'cameraman',
            'is_default' => false,
            'address' => 'ul. Fotograficzna 67',
            'city' => 'Gdańsk',
            'postal_code' => '80-789',
            'country' => 'PL',
            'sort_order' => 3,
            'allow_negative_stock' => true, // Allows pre-orders
            'auto_reserve_stock' => false,  // Manual reservation
            'default_minimum_stock' => 1,
            'contact_person' => 'Marek Wiśniewski',
            'phone' => '+48 58 123 45 67',
            'email' => 'warehouse@cameraman.pl',
            'operating_hours' => 'Poniedziałek-Sobota: 10:00-18:00',
            'special_instructions' => 'Ostrożne handling - sprzęt fotograficzny',
            'notes' => 'Magazyn sprzętu fotograficznego i video',
        ],
        [
            'name' => 'Otopit',
            'code' => 'otopit',
            'is_default' => false,
            'address' => 'ul. Samochodowa 89',
            'city' => 'Wrocław',
            'postal_code' => '50-012',
            'country' => 'PL',
            'sort_order' => 4,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 2,
            'contact_person' => 'Piotr Zieliński',
            'phone' => '+48 71 234 56 78',
            'email' => 'magazyn@otopit.pl',
            'operating_hours' => 'Poniedziałek-Piątek: 8:30-16:30',
            'special_instructions' => 'Części automotive - kontrola jakości wymagana',
            'notes' => 'Magazyn części samochodowych Otopit',
        ],
        [
            'name' => 'INFMS',
            'code' => 'infms',
            'is_default' => false,
            'address' => 'ul. Przemysłowa 234',
            'city' => 'Łódź',
            'postal_code' => '90-345',
            'country' => 'PL',
            'sort_order' => 5,
            'allow_negative_stock' => false,
            'auto_reserve_stock' => true,
            'default_minimum_stock' => 10,
            'contact_person' => 'Katarzyna Lis',
            'phone' => '+48 42 345 67 89',
            'email' => 'warehouse@infms.pl',
            'operating_hours' => 'Poniedziałek-Piątek: 7:00-15:00',
            'notes' => 'Magazyn INFMS - części przemysłowe',
        ],
        [
            'name' => 'Reklamacje',
            'code' => 'returns',
            'is_default' => false,
            'address' => 'ul. Serwisowa 12',
            'city' => 'Poznań',
            'postal_code' => '60-678',
            'country' => 'PL',
            'sort_order' => 6,
            'allow_negative_stock' => true,  // Allows negative for return processing
            'auto_reserve_stock' => false,   // Manual handling for returns
            'default_minimum_stock' => 0,    // No minimum stock for returns
            'contact_person' => 'Tomasz Dąbrowski',
            'phone' => '+48 61 456 78 90',
            'email' => 'reklamacje@mpptrade.pl',
            'operating_hours' => 'Poniedziałek-Piątek: 9:00-15:00',
            'special_instructions' => 'Magazyn dedykowany dla reklamacji i zwrotów - specjalne procedury',
            'notes' => 'Magazyn reklamacyjny - izolacja i weryfikacja zwrotów',
        ],
    ];

    /**
     * Run the warehouses seeder
     * 
     * Creates production-ready warehouses z realistic business data
     */
    public function run(): void
    {
        $this->command->info('🏢 Creating PPM Warehouses...');
        
        foreach (self::WAREHOUSES as $warehouseData) {
            $warehouse = Warehouse::create([
                'name' => $warehouseData['name'],
                'code' => $warehouseData['code'],
                'is_default' => $warehouseData['is_default'],
                'address' => $warehouseData['address'],
                'city' => $warehouseData['city'],
                'postal_code' => $warehouseData['postal_code'],
                'country' => $warehouseData['country'],
                'is_active' => true,
                'sort_order' => $warehouseData['sort_order'],
                'allow_negative_stock' => $warehouseData['allow_negative_stock'],
                'auto_reserve_stock' => $warehouseData['auto_reserve_stock'],
                'default_minimum_stock' => $warehouseData['default_minimum_stock'],
                'contact_person' => $warehouseData['contact_person'],
                'phone' => $warehouseData['phone'],
                'email' => $warehouseData['email'],
                'operating_hours' => $warehouseData['operating_hours'],
                'special_instructions' => $warehouseData['special_instructions'] ?? null,
                'notes' => $warehouseData['notes'],
                'prestashop_mapping' => $this->generatePrestaShopMapping($warehouseData['code']),
                'erp_mapping' => $this->generateErpMapping($warehouseData['code']),
            ]);

            $status = $warehouse->is_default ? '(DEFAULT)' : '';
            $this->command->info("✅ Created warehouse: {$warehouse->name} in {$warehouse->city} {$status}");
        }

        // Validate business constraints
        $this->validateWarehouses();
        
        $this->command->info('✅ Warehouses seeded successfully!');
        $this->command->info('📊 Total warehouses: ' . Warehouse::count());
        $this->command->info('🎯 Default warehouse: ' . Warehouse::getDefault()?->name ?? 'NONE');
    }

    /**
     * Generate PrestaShop mapping structure for integration
     * 
     * @param string $code
     * @return array|null
     */
    private function generatePrestaShopMapping(string $code): ?array
    {
        // Template mapping structure dla PrestaShop warehouses/locations
        // Każdy sklep PrestaShop może mieć różne mapowanie magazynów
        return [
            'shop_1' => [
                'warehouse_id' => null, // To be mapped during PrestaShop integration
                'location_id' => null,
                'stock_available_id' => null,
                'name' => $this->getDisplayNameForCode($code),
                'sync_enabled' => false, // Disabled until configured
            ],
            'shop_2' => [
                'warehouse_id' => null,
                'location_id' => null,
                'stock_available_id' => null,
                'name' => $this->getDisplayNameForCode($code),
                'sync_enabled' => false,
            ],
        ];
    }

    /**
     * Generate ERP mapping structure for integration
     * 
     * @param string $code
     * @return array|null
     */
    private function generateErpMapping(string $code): ?array
    {
        return [
            'baselinker' => [
                'warehouse_id' => null, // To be set during Baselinker integration
                'warehouse_name' => $this->getDisplayNameForCode($code),
                'sync_enabled' => false,
            ],
            'subiekt_gt' => [
                'magazyn_symbol' => strtoupper($code),
                'magazyn_nazwa' => $this->getDisplayNameForCode($code),
                'magazyn_id' => null,
                'sync_enabled' => false,
            ],
            'dynamics' => [
                'location_code' => strtoupper($code),
                'location_name' => $this->getDisplayNameForCode($code),
                'location_id' => null,
                'sync_enabled' => false,
            ],
        ];
    }

    /**
     * Get display name for warehouse code
     * 
     * @param string $code
     * @return string
     */
    private function getDisplayNameForCode(string $code): string
    {
        $names = [
            'mpptrade' => 'MPP TRADE Main',
            'pitbike' => 'Pitbike Store',
            'cameraman' => 'Cameraman Warehouse',
            'otopit' => 'Otopit Storage',
            'infms' => 'INFMS Industrial',
            'returns' => 'Returns Processing',
        ];

        return $names[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * Validate warehouses business constraints
     * 
     * @throws \Exception
     */
    private function validateWarehouses(): void
    {
        // Check if exactly one default warehouse exists
        $defaultCount = Warehouse::where('is_default', true)->count();
        if ($defaultCount !== 1) {
            throw new \Exception("Invalid warehouses setup: Found {$defaultCount} default warehouses, expected exactly 1");
        }

        // Check if all codes are unique
        $totalWarehouses = Warehouse::count();
        $uniqueCodes = Warehouse::distinct('code')->count();
        if ($totalWarehouses !== $uniqueCodes) {
            throw new \Exception('Invalid warehouses setup: Duplicate codes found');
        }

        // Validate minimum stock ranges
        $invalidMinStock = Warehouse::where('default_minimum_stock', '<', 0)->count();
        if ($invalidMinStock > 0) {
            throw new \Exception('Invalid warehouses setup: Negative minimum stock found');
        }

        $this->command->info('✅ Warehouses validation passed');
    }
}