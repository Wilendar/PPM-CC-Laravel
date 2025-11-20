<?php

namespace Database\Factories;

use App\Models\ConflictLog;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ConflictLogFactory - Factory for generating conflict log test data
 *
 * Enterprise-grade factory for ConflictLog model:
 * - Realistic conflict scenarios (duplicate SKU, validation errors, missing dependencies)
 * - Resolution workflow states
 * - Data comparison patterns
 * - Business logic compliant conflicts
 *
 * Usage:
 * ConflictLog::factory()->create() - single conflict
 * ConflictLog::factory()->duplicateSku()->create() - SKU conflict
 * ConflictLog::factory()->resolved()->create() - resolved conflict
 * ConflictLog::factory()->pending()->create() - unresolved conflict
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConflictLog>
 * @package Database\Factories
 * @version 1.0
 * @since ETAP_07 FAZA 5 - Import/Export System
 */
class ConflictLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ConflictLog::class;

    /**
     * Define the model's default state.
     *
     * Generates realistic conflict log data for PPM-CC-Laravel:
     * - Random conflict types
     * - Realistic data differences
     * - Resolution states
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conflictType = $this->faker->randomElement(['duplicate_sku', 'validation_error', 'missing_dependency']);
        $sku = 'SKU' . $this->faker->unique()->numberBetween(10000, 99999);

        $existingData = $this->generateExistingData($conflictType);
        $newData = $this->generateNewData($conflictType, $existingData);

        return [
            'import_batch_id' => ImportBatch::factory(),
            'sku' => $sku,
            'conflict_type' => $conflictType,
            'existing_data' => $existingData,
            'new_data' => $newData,
            'resolution_status' => 'pending',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Conflict Types
    |--------------------------------------------------------------------------
    */

    /**
     * Duplicate SKU conflict
     */
    public function duplicateSku(): static
    {
        return $this->state(function (array $attributes) {
            $sku = 'SKU' . $this->faker->numberBetween(10000, 99999);

            return [
                'sku' => $sku,
                'conflict_type' => 'duplicate_sku',
                'existing_data' => [
                    'sku' => $sku,
                    'name' => 'Existing Product Name',
                    'price' => $this->faker->randomFloat(2, 50, 500),
                    'stock' => $this->faker->numberBetween(0, 100),
                    'category' => 'Vehicles',
                ],
                'new_data' => [
                    'sku' => $sku,
                    'name' => 'New Product Name (Different)',
                    'price' => $this->faker->randomFloat(2, 50, 500),
                    'stock' => $this->faker->numberBetween(0, 100),
                    'category' => 'Spare Parts',
                ],
            ];
        });
    }

    /**
     * Validation error conflict
     */
    public function validationError(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'conflict_type' => 'validation_error',
                'existing_data' => [],
                'new_data' => [
                    'sku' => $this->faker->word(),
                    'name' => '', // Missing required field
                    'price' => -10, // Invalid price
                    'stock' => 'invalid', // Invalid type
                ],
            ];
        });
    }

    /**
     * Missing dependency conflict
     */
    public function missingDependency(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'conflict_type' => 'missing_dependency',
                'existing_data' => [],
                'new_data' => [
                    'sku' => 'SKU' . $this->faker->numberBetween(10000, 99999),
                    'name' => 'Product Name',
                    'category_id' => 999, // Non-existent category
                    'price_group_id' => 888, // Non-existent price group
                ],
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Resolution Status
    |--------------------------------------------------------------------------
    */

    /**
     * Pending conflict (unresolved)
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'resolution_status' => 'pending',
                'resolved_by_user_id' => null,
                'resolved_at' => null,
                'resolution_notes' => null,
            ];
        });
    }

    /**
     * Resolved conflict
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $strategies = [
                'use_new' => 'Used new data from import',
                'use_existing' => 'Kept existing database data',
                'merge' => 'Merged both data sources',
                'manual_edit' => 'Manually edited and resolved',
            ];

            $strategy = $this->faker->randomElement(array_keys($strategies));

            return [
                'resolution_status' => 'resolved',
                'resolved_by_user_id' => User::factory(),
                'resolved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'resolution_notes' => "Strategy: {$strategy}. " . $strategies[$strategy],
            ];
        });
    }

    /**
     * Ignored conflict
     */
    public function ignored(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'resolution_status' => 'ignored',
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Special Scenarios
    |--------------------------------------------------------------------------
    */

    /**
     * Conflict for specific import batch
     */
    public function forBatch(ImportBatch $batch): static
    {
        return $this->state(function (array $attributes) use ($batch) {
            return [
                'import_batch_id' => $batch->id,
            ];
        });
    }

    /**
     * Conflict with specific SKU
     */
    public function withSku(string $sku): static
    {
        return $this->state(function (array $attributes) use ($sku) {
            return [
                'sku' => $sku,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Data Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate existing data based on conflict type
     */
    private function generateExistingData(string $conflictType): array
    {
        if ($conflictType === 'validation_error' || $conflictType === 'missing_dependency') {
            return []; // No existing data for these types
        }

        // duplicate_sku
        return [
            'id' => $this->faker->numberBetween(1, 10000),
            'sku' => 'SKU' . $this->faker->numberBetween(10000, 99999),
            'name' => 'Existing: ' . $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 50, 1000),
            'stock' => $this->faker->numberBetween(0, 200),
            'category' => $this->faker->randomElement(['Vehicles', 'Spare Parts', 'Accessories', 'Tools']),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate new data based on conflict type and existing data
     */
    private function generateNewData(string $conflictType, array $existingData): array
    {
        switch ($conflictType) {
            case 'duplicate_sku':
                // Same SKU, but different other fields
                return [
                    'sku' => $existingData['sku'],
                    'name' => 'New: ' . $this->faker->words(3, true),
                    'price' => $this->faker->randomFloat(2, 50, 1000),
                    'stock' => $this->faker->numberBetween(0, 200),
                    'category' => $this->faker->randomElement(['Vehicles', 'Spare Parts', 'Accessories', 'Tools']),
                ];

            case 'validation_error':
                // Invalid data
                return [
                    'sku' => $this->faker->optional(0.5)->word(), // Sometimes missing
                    'name' => $this->faker->optional(0.3)->word(), // Sometimes missing
                    'price' => $this->faker->randomElement([-10, 'invalid', null]), // Invalid values
                    'stock' => $this->faker->randomElement(['invalid', -5, null]),
                ];

            case 'missing_dependency':
                // Valid structure but non-existent references
                return [
                    'sku' => 'SKU' . $this->faker->numberBetween(10000, 99999),
                    'name' => $this->faker->words(3, true),
                    'category_id' => 999, // Non-existent
                    'price_group_id' => 888, // Non-existent
                    'warehouse_id' => 777, // Non-existent
                ];

            default:
                return [];
        }
    }
}
