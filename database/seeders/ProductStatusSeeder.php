<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Aktywny',
                'slug' => 'aktywny',
                'color' => '#22c55e',
                'icon' => 'check-circle',
                'is_active_equivalent' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Nieaktywny',
                'slug' => 'nieaktywny',
                'color' => '#ef4444',
                'icon' => 'x-circle',
                'is_active_equivalent' => false,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Wygaszany',
                'slug' => 'wygaszany',
                'color' => '#f59e0b',
                'icon' => 'clock',
                'is_active_equivalent' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
        ];

        $integrationTypes = ['prestashop', 'baselinker', 'subiekt_gt'];

        foreach ($statuses as $statusData) {
            $existing = DB::table('product_statuses')
                ->where('slug', $statusData['slug'])
                ->first();

            if ($existing) {
                $statusId = $existing->id;
            } else {
                $statusId = DB::table('product_statuses')->insertGetId(
                    array_merge($statusData, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            // Create integration mappings
            foreach ($integrationTypes as $type) {
                DB::table('product_status_integration_mappings')->updateOrInsert(
                    [
                        'product_status_id' => $statusId,
                        'integration_type' => $type,
                    ],
                    [
                        'maps_to_active' => $statusData['is_active_equivalent'],
                        'description' => $statusData['name'] . ' -> ' . ($statusData['is_active_equivalent'] ? 'Active' : 'Inactive') . ' in ' . $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Setup auto-transition: Wygaszany -> Nieaktywny on stock depletion
        $this->setupStockDepletionTransition();
    }

    /**
     * Configure "Wygaszany" to auto-transition to "Nieaktywny" when stock = 0
     */
    private function setupStockDepletionTransition(): void
    {
        $wygaszany = DB::table('product_statuses')->where('slug', 'wygaszany')->first();
        $nieaktywny = DB::table('product_statuses')->where('slug', 'nieaktywny')->first();

        if ($wygaszany && $nieaktywny && Schema::hasColumn('product_statuses', 'transition_on_stock_depleted')) {
            DB::table('product_statuses')
                ->where('id', $wygaszany->id)
                ->update([
                    'transition_on_stock_depleted' => true,
                    'transition_to_status_id' => $nieaktywny->id,
                    'depletion_warehouse_id' => null, // default warehouse
                    'updated_at' => now(),
                ]);
        }
    }
}
