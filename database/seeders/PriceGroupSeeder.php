<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceGroup;

/**
 * PriceGroup Seeder - Production-ready data dla PPM-CC-Laravel
 * 
 * FAZA B: Pricing & Inventory System - Price Groups Initialization
 * 
 * Business Data:
 * - 8 grup cenowych PPM zgodnie z rzeczywistą strukturą biznesową
 * - Domyślne marże oparte na strategii cenowej firmy
 * - Integration-ready struktura dla PrestaShop i ERP
 * 
 * @package Database\Seeders
 * @version FAZA B
 * @since 2024-09-09
 */
class PriceGroupSeeder extends Seeder
{
    /**
     * Production-ready price groups dla MPP TRADE
     * 
     * Struktura zgodna z rzeczywistymi grupami cenowymi:
     * - Margin percentages oparte na strategii biznesowej
     * - Sort order od najdroższej do najtańszej grupy
     * - Codes prepared dla integration z external systems
     */
    private const PRICE_GROUPS = [
        [
            'name' => 'Detaliczna',
            'code' => 'retail',
            'is_default' => true, // Domyślna grupa cenowa
            'margin_percentage' => 45.00,
            'sort_order' => 1,
            'description' => 'Ceny detaliczne dla klientów końcowych - najwyższa marża',
        ],
        [
            'name' => 'Dealer Standard',
            'code' => 'dealer_std',
            'is_default' => false,
            'margin_percentage' => 30.00,
            'sort_order' => 2,
            'description' => 'Ceny dla dealerów standardowych - średnia marża',
        ],
        [
            'name' => 'Dealer Premium',
            'code' => 'dealer_premium',
            'is_default' => false,
            'margin_percentage' => 25.00,
            'sort_order' => 3,
            'description' => 'Ceny dla dealerów premium - obniżona marża za volumen',
        ],
        [
            'name' => 'Warsztat Standard',
            'code' => 'workshop_std',
            'is_default' => false,
            'margin_percentage' => 35.00,
            'sort_order' => 4,
            'description' => 'Ceny dla warsztatów standardowych - marża warsztatowa',
        ],
        [
            'name' => 'Warsztat Premium',
            'code' => 'workshop_premium',
            'is_default' => false,
            'margin_percentage' => 28.00,
            'sort_order' => 5,
            'description' => 'Ceny dla warsztatów premium - preferencyjna marża',
        ],
        [
            'name' => 'Szkółka-Komis-Drop',
            'code' => 'school_drop',
            'is_default' => false,
            'margin_percentage' => 18.00,
            'sort_order' => 6,
            'description' => 'Ceny dla szkółek, komisów i dropshippingu - minimalna marża',
        ],
        [
            'name' => 'Pracownik',
            'code' => 'employee',
            'is_default' => false,
            'margin_percentage' => 8.00,
            'sort_order' => 7,
            'description' => 'Ceny pracownicze - benefit dla zespołu MPP',
        ],
        [
            'name' => 'HuHa',
            'code' => 'huha',
            'is_default' => false,
            'margin_percentage' => 12.00,
            'sort_order' => 8,
            'description' => 'Ceny specjalne HuHa - grupa cenowa B2B',
            'is_active' => false, // Nieaktywna na start - do aktywacji przez Admin
        ],
    ];

    /**
     * Run the price groups seeder
     * 
     * Creates production-ready price groups z proper business constraints
     */
    public function run(): void
    {
        $this->command->info('🏷️  Creating PPM Price Groups...');
        
        foreach (self::PRICE_GROUPS as $groupData) {
            $priceGroup = PriceGroup::create([
                'name' => $groupData['name'],
                'code' => $groupData['code'],
                'is_default' => $groupData['is_default'],
                'margin_percentage' => $groupData['margin_percentage'],
                'is_active' => $groupData['is_active'] ?? true,
                'sort_order' => $groupData['sort_order'],
                'description' => $groupData['description'],
                'prestashop_mapping' => $this->generatePrestaShopMapping($groupData['code']),
                'erp_mapping' => $this->generateErpMapping($groupData['code']),
            ]);

            $this->command->info("✅ Created price group: {$priceGroup->name} ({$priceGroup->margin_percentage}% margin)");
        }

        // Validate business constraints
        $this->validatePriceGroups();
        
        $this->command->info('✅ Price Groups seeded successfully!');
        $this->command->info('📊 Total groups: ' . PriceGroup::count());
        $this->command->info('🎯 Default group: ' . PriceGroup::getDefault()?->name ?? 'NONE');
    }

    /**
     * Generate PrestaShop mapping structure for integration
     * 
     * @param string $code
     * @return array|null
     */
    private function generatePrestaShopMapping(string $code): ?array
    {
        // Template mapping structure dla PrestaShop specific_prices
        // To be configured per shop during integration setup
        return [
            'shop_1' => [
                'group_id' => null, // To be mapped during PrestaShop integration
                'reduction_type' => 'percentage',
                'reduction' => $this->calculateReductionForCode($code),
                'from_quantity' => 1,
                'sync_enabled' => false, // Disabled until configured
            ],
            // Additional shops can be added here
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
        // Template mapping structure dla ERP systems
        return [
            'baselinker' => [
                'price_group_id' => null, // To be mapped during Baselinker setup
                'name' => $this->getErpNameForCode($code),
                'sync_enabled' => false,
            ],
            'subiekt_gt' => [
                'cennik_symbol' => strtoupper($code),
                'cennik_nazwa' => $this->getErpNameForCode($code),
                'sync_enabled' => false,
            ],
            'dynamics' => [
                'price_list_code' => strtoupper($code),
                'price_list_name' => $this->getErpNameForCode($code),
                'sync_enabled' => false,
            ],
        ];
    }

    /**
     * Calculate reduction percentage for PrestaShop based on margin
     * 
     * @param string $code
     * @return float
     */
    private function calculateReductionForCode(string $code): float
    {
        $reductions = [
            'retail' => 0.0,      // No reduction - full price
            'dealer_std' => 10.0,  // 10% reduction from retail
            'dealer_premium' => 15.0, // 15% reduction from retail
            'workshop_std' => 8.0,    // 8% reduction from retail
            'workshop_premium' => 12.0, // 12% reduction from retail
            'school_drop' => 20.0,    // 20% reduction from retail
            'employee' => 30.0,       // 30% reduction from retail
            'huha' => 25.0,          // 25% reduction from retail
        ];

        return $reductions[$code] ?? 0.0;
    }

    /**
     * Get ERP-friendly name for price group code
     * 
     * @param string $code
     * @return string
     */
    private function getErpNameForCode(string $code): string
    {
        $names = [
            'retail' => 'Retail',
            'dealer_std' => 'Dealer Standard',
            'dealer_premium' => 'Dealer Premium',
            'workshop_std' => 'Workshop Standard',
            'workshop_premium' => 'Workshop Premium',
            'school_drop' => 'School/Drop',
            'employee' => 'Employee',
            'huha' => 'HuHa Special',
        ];

        return $names[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * Validate price groups business constraints
     * 
     * @throws \Exception
     */
    private function validatePriceGroups(): void
    {
        // Check if exactly one default group exists
        $defaultCount = PriceGroup::where('is_default', true)->count();
        if ($defaultCount !== 1) {
            throw new \Exception("Invalid price groups setup: Found {$defaultCount} default groups, expected exactly 1");
        }

        // Check if all codes are unique
        $totalGroups = PriceGroup::count();
        $uniqueCodes = PriceGroup::distinct('code')->count();
        if ($totalGroups !== $uniqueCodes) {
            throw new \Exception('Invalid price groups setup: Duplicate codes found');
        }

        // Validate margin ranges
        $invalidMargins = PriceGroup::where('margin_percentage', '<', -100)
                                   ->orWhere('margin_percentage', '>', 999)
                                   ->count();
        if ($invalidMargins > 0) {
            throw new \Exception('Invalid price groups setup: Margin percentage out of range');
        }

        $this->command->info('✅ Price groups validation passed');
    }
}