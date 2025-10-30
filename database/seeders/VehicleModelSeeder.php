<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * VehicleModelSeeder
 *
 * Seeds vehicle_models table with example vehicles.
 *
 * ETAP_05a FAZA 1 - Seeder 5/5
 *
 * PURPOSE:
 * - Provide initial set of example vehicles for testing compatibility
 * - Enable immediate compatibility assignment after migration
 *
 * VEHICLE MODELS:
 * - Honda CBR 600 RR (2013-2020, 600cc)
 * - Yamaha YZF-R1 (2015-2019, 1000cc)
 * - Kawasaki Ninja 650 (2017-2023, 650cc)
 * - Suzuki GSX-R 750 (2011-2020, 750cc)
 * - BMW S 1000 RR (2019-2023, 1000cc)
 * - Ducati Panigale V4 (2018-2023, 1100cc)
 * - KTM Duke 390 (2017-2023, 373cc)
 * - Triumph Street Triple RS (2020-2023, 765cc)
 * - Aprilia RSV4 (2015-2021, 1000cc)
 * - MV Agusta F3 800 (2013-2019, 798cc)
 */
class VehicleModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $vehicleModels = [
            [
                'sku' => 'VEH-HONDA-CBR600RR-2013',
                'brand' => 'Honda',
                'model' => 'CBR 600',
                'variant' => 'RR',
                'year_from' => 2013,
                'year_to' => 2020,
                'engine_code' => 'PC40E',
                'engine_capacity' => 600,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-YAMAHA-R1-2015',
                'brand' => 'Yamaha',
                'model' => 'YZF-R1',
                'variant' => null,
                'year_from' => 2015,
                'year_to' => 2019,
                'engine_code' => 'MT-09',
                'engine_capacity' => 1000,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-KAWASAKI-NINJA650-2017',
                'brand' => 'Kawasaki',
                'model' => 'Ninja 650',
                'variant' => null,
                'year_from' => 2017,
                'year_to' => 2023,
                'engine_code' => 'ER6N',
                'engine_capacity' => 650,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-SUZUKI-GSXR750-2011',
                'brand' => 'Suzuki',
                'model' => 'GSX-R 750',
                'variant' => null,
                'year_from' => 2011,
                'year_to' => 2020,
                'engine_code' => 'K11',
                'engine_capacity' => 750,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-BMW-S1000RR-2019',
                'brand' => 'BMW',
                'model' => 'S 1000 RR',
                'variant' => null,
                'year_from' => 2019,
                'year_to' => 2023,
                'engine_code' => 'K67',
                'engine_capacity' => 1000,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-DUCATI-PANIGALEV4-2018',
                'brand' => 'Ducati',
                'model' => 'Panigale V4',
                'variant' => null,
                'year_from' => 2018,
                'year_to' => 2023,
                'engine_code' => 'Desmosedici Stradale',
                'engine_capacity' => 1100,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-KTM-DUKE390-2017',
                'brand' => 'KTM',
                'model' => 'Duke 390',
                'variant' => null,
                'year_from' => 2017,
                'year_to' => 2023,
                'engine_code' => '373',
                'engine_capacity' => 373,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-TRIUMPH-STREETTRIPLE-2020',
                'brand' => 'Triumph',
                'model' => 'Street Triple',
                'variant' => 'RS',
                'year_from' => 2020,
                'year_to' => 2023,
                'engine_code' => 'TT09',
                'engine_capacity' => 765,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-APRILIA-RSV4-2015',
                'brand' => 'Aprilia',
                'model' => 'RSV4',
                'variant' => null,
                'year_from' => 2015,
                'year_to' => 2021,
                'engine_code' => 'V4',
                'engine_capacity' => 1000,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'VEH-MVAGUSTA-F3800-2013',
                'brand' => 'MV Agusta',
                'model' => 'F3 800',
                'variant' => null,
                'year_from' => 2013,
                'year_to' => 2019,
                'engine_code' => 'F3',
                'engine_capacity' => 798,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('vehicle_models')->insert($vehicleModels);

        $this->command->info('✅ VehicleModelSeeder: Seeded 10 vehicle models');
    }
}
