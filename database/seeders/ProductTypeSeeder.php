<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductType Seeder
 *
 * Tworzy domyślne typy produktów zastępując hardcoded ENUM
 *
 * @package Database\Seeders
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('ProductTypeSeeder: Creating default product types...');

        try {
            DB::beginTransaction();

            // Get default types from model
            $defaultTypes = ProductType::getDefaultTypes();

            foreach ($defaultTypes as $typeData) {
                // Check if type already exists (by slug)
                $existing = ProductType::where('slug', $typeData['slug'])->first();

                if (!$existing) {
                    ProductType::create($typeData);
                    Log::info("ProductTypeSeeder: Created type '{$typeData['name']}'");
                } else {
                    Log::info("ProductTypeSeeder: Type '{$typeData['name']}' already exists, skipping");
                }
            }

            DB::commit();

            $totalTypes = ProductType::count();
            Log::info("ProductTypeSeeder: Completed. Total product types in database: {$totalTypes}");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('ProductTypeSeeder: Error creating product types', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }
}