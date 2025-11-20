<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\PrestaShopShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ImportBatchFactory - Factory for generating import batch test data
 *
 * Enterprise-grade factory for ImportBatch model:
 * - XLSX and PrestaShop API import scenarios
 * - Realistic progress tracking states
 * - Status transition patterns
 * - Business logic compliant generation
 *
 * Usage:
 * ImportBatch::factory()->create() - single batch
 * ImportBatch::factory()->xlsx()->create() - XLSX import batch
 * ImportBatch::factory()->prestashopApi()->create() - PrestaShop API batch
 * ImportBatch::factory()->completed()->create() - completed batch
 * ImportBatch::factory()->withConflicts()->create() - batch with conflicts
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportBatch>
 * @package Database\Factories
 * @version 1.0
 * @since ETAP_07 FAZA 5 - Import/Export System
 */
class ImportBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ImportBatch::class;

    /**
     * Define the model's default state.
     *
     * Generates realistic import batch data for PPM-CC-Laravel:
     * - Random import type (xlsx or prestashop_api)
     * - Realistic progress counters
     * - Status progression
     * - Conflict detection
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $importType = $this->faker->randomElement(['xlsx', 'prestashop_api']);
        $totalRows = $this->faker->numberBetween(10, 500);
        $processedRows = $this->faker->numberBetween(0, $totalRows);
        $importedProducts = $this->faker->numberBetween(0, $processedRows);
        $conflictsCount = $this->faker->numberBetween(0, (int)($processedRows * 0.1)); // Max 10% conflicts
        $failedProducts = $processedRows - $importedProducts - $conflictsCount;

        return [
            'user_id' => User::factory(),
            'import_type' => $importType,
            'filename' => $importType === 'xlsx' ? 'import_' . $this->faker->dateTime()->format('Y-m-d') . '.xlsx' : null,
            'shop_id' => $importType === 'prestashop_api' ? PrestaShopShop::factory() : null,
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'total_rows' => $totalRows,
            'processed_rows' => $processedRows,
            'imported_products' => $importedProducts,
            'failed_products' => max(0, $failedProducts),
            'conflicts_count' => $conflictsCount,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Import Type States
    |--------------------------------------------------------------------------
    */

    /**
     * XLSX import batch
     */
    public function xlsx(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'import_type' => 'xlsx',
                'filename' => 'variants_' . $this->faker->dateTime()->format('Y-m-d_His') . '.xlsx',
                'shop_id' => null,
            ];
        });
    }

    /**
     * PrestaShop API import batch
     */
    public function prestashopApi(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'import_type' => 'prestashop_api',
                'filename' => null,
                'shop_id' => PrestaShopShop::factory(),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Status States
    |--------------------------------------------------------------------------
    */

    /**
     * Pending batch (not started)
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'processed_rows' => 0,
                'imported_products' => 0,
                'failed_products' => 0,
                'conflicts_count' => 0,
                'started_at' => null,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Processing batch (in progress)
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            $totalRows = $attributes['total_rows'] ?? 100;
            $processedRows = $this->faker->numberBetween(1, $totalRows - 1);
            $importedProducts = (int)($processedRows * 0.85); // 85% success rate

            return [
                'status' => 'processing',
                'processed_rows' => $processedRows,
                'imported_products' => $importedProducts,
                'failed_products' => $processedRows - $importedProducts,
                'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
                'completed_at' => null,
            ];
        });
    }

    /**
     * Completed batch (successfully finished)
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $totalRows = $attributes['total_rows'] ?? 100;
            $importedProducts = (int)($totalRows * 0.90); // 90% success rate
            $conflictsCount = (int)($totalRows * 0.05); // 5% conflicts
            $failedProducts = $totalRows - $importedProducts - $conflictsCount;

            $startedAt = $this->faker->dateTimeBetween('-24 hours', '-1 hour');
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');

            return [
                'status' => 'completed',
                'processed_rows' => $totalRows,
                'imported_products' => $importedProducts,
                'failed_products' => $failedProducts,
                'conflicts_count' => $conflictsCount,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ];
        });
    }

    /**
     * Failed batch (error occurred)
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $totalRows = $attributes['total_rows'] ?? 100;
            $processedRows = $this->faker->numberBetween(0, (int)($totalRows * 0.5)); // Failed mid-way
            $importedProducts = $processedRows > 0 ? (int)($processedRows * 0.7) : 0;

            $startedAt = $this->faker->dateTimeBetween('-24 hours', '-1 hour');
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');

            $errors = [
                'Database connection lost',
                'Invalid file format',
                'Memory limit exceeded',
                'Required column missing: SKU',
                'PrestaShop API connection timeout',
                'Invalid authentication credentials',
            ];

            return [
                'status' => 'failed',
                'processed_rows' => $processedRows,
                'imported_products' => $importedProducts,
                'failed_products' => $processedRows - $importedProducts,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'error_message' => $this->faker->randomElement($errors),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Special Scenarios
    |--------------------------------------------------------------------------
    */

    /**
     * Batch with conflicts (duplicate SKUs)
     */
    public function withConflicts(): static
    {
        return $this->state(function (array $attributes) {
            $totalRows = $attributes['total_rows'] ?? 100;
            $conflictsCount = $this->faker->numberBetween(5, (int)($totalRows * 0.2)); // 5-20% conflicts

            return [
                'conflicts_count' => $conflictsCount,
            ];
        });
    }

    /**
     * Recent batch (last 7 days)
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Large import batch (500+ rows)
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_rows' => $this->faker->numberBetween(500, 2000),
            ];
        });
    }

    /**
     * Small import batch (<50 rows)
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_rows' => $this->faker->numberBetween(5, 50),
            ];
        });
    }
}
