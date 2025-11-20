<?php

namespace Database\Factories;

use App\Models\ExportBatch;
use App\Models\PrestaShopShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ExportBatchFactory - Factory for generating export batch test data
 *
 * Enterprise-grade factory for ExportBatch model:
 * - XLSX and PrestaShop API export scenarios
 * - Realistic progress tracking states
 * - Status transition patterns
 * - Filter configurations
 *
 * Usage:
 * ExportBatch::factory()->create() - single batch
 * ExportBatch::factory()->xlsx()->create() - XLSX export batch
 * ExportBatch::factory()->prestashopApi()->create() - PrestaShop API batch
 * ExportBatch::factory()->completed()->create() - completed batch
 * ExportBatch::factory()->withFilters()->create() - batch with filters
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExportBatch>
 * @package Database\Factories
 * @version 1.0
 * @since ETAP_07 FAZA 5 - Import/Export System
 */
class ExportBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ExportBatch::class;

    /**
     * Define the model's default state.
     *
     * Generates realistic export batch data for PPM-CC-Laravel:
     * - Random export type (xlsx or prestashop_api)
     * - Realistic progress counters
     * - Status progression
     * - Filter configurations
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exportType = $this->faker->randomElement(['xlsx', 'prestashop_api']);
        $totalProducts = $this->faker->numberBetween(10, 500);
        $exportedProducts = $this->faker->numberBetween(0, $totalProducts);
        $failedProducts = $totalProducts - $exportedProducts;

        return [
            'user_id' => User::factory(),
            'export_type' => $exportType,
            'shop_id' => $exportType === 'prestashop_api' ? PrestaShopShop::factory() : null,
            'filename' => $exportType === 'xlsx' ? 'export_' . $this->faker->dateTime()->format('Y-m-d_His') . '.xlsx' : null,
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'total_products' => $totalProducts,
            'exported_products' => $exportedProducts,
            'failed_products' => $failedProducts,
            'filters' => null,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Export Type States
    |--------------------------------------------------------------------------
    */

    /**
     * XLSX export batch
     */
    public function xlsx(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'export_type' => 'xlsx',
                'filename' => 'products_export_' . $this->faker->dateTime()->format('Y-m-d_His') . '.xlsx',
                'shop_id' => null,
            ];
        });
    }

    /**
     * PrestaShop API export batch
     */
    public function prestashopApi(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'export_type' => 'prestashop_api',
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
                'exported_products' => 0,
                'failed_products' => 0,
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
            $totalProducts = $attributes['total_products'] ?? 100;
            $exportedProducts = $this->faker->numberBetween(1, $totalProducts - 1);

            return [
                'status' => 'processing',
                'exported_products' => $exportedProducts,
                'failed_products' => $this->faker->numberBetween(0, (int)(($totalProducts - $exportedProducts) * 0.1)),
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
            $totalProducts = $attributes['total_products'] ?? 100;
            $exportedProducts = (int)($totalProducts * 0.95); // 95% success rate
            $failedProducts = $totalProducts - $exportedProducts;

            $startedAt = $this->faker->dateTimeBetween('-24 hours', '-1 hour');
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');

            return [
                'status' => 'completed',
                'exported_products' => $exportedProducts,
                'failed_products' => $failedProducts,
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
            $totalProducts = $attributes['total_products'] ?? 100;
            $exportedProducts = $this->faker->numberBetween(0, (int)($totalProducts * 0.5)); // Failed mid-way
            $failedProducts = $totalProducts - $exportedProducts;

            $startedAt = $this->faker->dateTimeBetween('-24 hours', '-1 hour');
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');

            $errors = [
                'PrestaShop API connection timeout',
                'File write permission denied',
                'Disk space exceeded',
                'Invalid authentication credentials',
                'PrestaShop API rate limit exceeded',
                'Export canceled by user',
            ];

            return [
                'status' => 'failed',
                'exported_products' => $exportedProducts,
                'failed_products' => $failedProducts,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'error_message' => $this->faker->randomElement($errors),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Filter Configurations
    |--------------------------------------------------------------------------
    */

    /**
     * Export with filters
     */
    public function withFilters(): static
    {
        return $this->state(function (array $attributes) {
            $filterTypes = [
                [
                    'has_variants' => true,
                ],
                [
                    'category_id' => $this->faker->numberBetween(1, 20),
                ],
                [
                    'price_group' => $this->faker->randomElement(['detaliczna', 'dealer_standard', 'dealer_premium']),
                ],
                [
                    'warehouse' => $this->faker->randomElement(['MPPTRADE', 'Pitbike.pl', 'Cameraman']),
                ],
                [
                    'has_variants' => true,
                    'category_id' => $this->faker->numberBetween(1, 20),
                    'is_active' => true,
                ],
            ];

            return [
                'filters' => $this->faker->randomElement($filterTypes),
            ];
        });
    }

    /**
     * Export variants only
     */
    public function variantsOnly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'filters' => [
                    'has_variants' => true,
                ],
            ];
        });
    }

    /**
     * Export by category
     */
    public function byCategory(int $categoryId): static
    {
        return $this->state(function (array $attributes) use ($categoryId) {
            return [
                'filters' => [
                    'category_id' => $categoryId,
                ],
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Special Scenarios
    |--------------------------------------------------------------------------
    */

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
     * Large export batch (500+ products)
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_products' => $this->faker->numberBetween(500, 2000),
            ];
        });
    }

    /**
     * Small export batch (<50 products)
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_products' => $this->faker->numberBetween(5, 50),
            ];
        });
    }
}
